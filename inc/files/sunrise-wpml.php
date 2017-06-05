<?php
/**
 * Define the stuff that needs to happen before MultiSite is loaded.
 *
 * @package Domain Mapping
 */

if ( ! defined( 'SUNRISE_LOADED' ) ) { define( 'SUNRISE_LOADED', 1 ); }

if ( ! defined( 'AUTHORING_DOMAIN' ) ) { define( 'AUTHORING_DOMAIN', DOMAIN_CURRENT_SITE ); }

if ( defined( 'COOKIE_DOMAIN' ) ) {
	die( 'The constant "COOKIE_DOMAIN" is defined (probably in wp-config.php). Please remove or comment out that define() line.' );
}
global $override_domain;

/**
 * Description let the site admin page catch the VHOST == 'no'
 *
 * @return type
 */
$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';

$dm_domain = $_SERVER['HTTP_HOST'];

if ( $dm_domain !== ( $no_www = preg_replace( '|^www\.|', '', $dm_domain ) ) ) {
	$where = $wpdb->prepare( 'domain IN (%s,%s)', $dm_domain, $no_www );
} else {
	$where = $wpdb->prepare( 'domain = %s', $dm_domain );
}

/**
 * Description = RMURPHY customization to allow both sub-domains and sub-directories.
 *
 * @param string $domain
 *
 * @return type
 */
function __isAuthoringDomain( $domain ) {
	return strncmp( AUTHORING_DOMAIN, $domain, strlen( AUTHORING_DOMAIN ) ) == 0;
}

// Ensure that this isn't the authoring domain, in which case we don't need to
// map domains
if ( ! __isAuthoringDomain( $dm_domain ) ) {
	$uri = $_SERVER['REQUEST_URI'];
	$host = $_SERVER['HTTP_HOST'];
	$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->dmtable} WHERE domain LIKE '{$host}%' ORDER BY LENGTH(domain) DESC, active DESC" );


	foreach ( $rows as $index => $map ) {
		$map->domain = trim( $map->domain );

		// Checking if this is a sub-directory based mapping, not on the authoring
		// domain
		if ( ! __isAuthoringDomain( $map->domain )
			&& strpos( $map->domain, '/' ) !== false ) {

			$sub_dir = trim( strstr( $map->domain, '/' ), '/' );
			$bad_sub_dir = sprintf( '/%1$s', $sub_dir );
			$sub_dir = sprintf( '/%1$s/', $sub_dir );

			$bad_sub_dir_esc = preg_quote( $bad_sub_dir, '/' );
			$sub_dir_esc = preg_quote( $sub_dir, '/' );

			$map_domain = substr( $map->domain, 0, strpos( $map->domain, '/' ) );
			$map_domain = strtolower( trim( $map_domain, '/' ) );

			$req_domain = strtolower( trim( $host, '/' ) );

			if ( $map_domain == $req_domain && preg_match( "/^$sub_dir_esc/", $uri ) ) {
				$override_domain = rtrim( $map->domain, '/' ) . '/';
				$domain_mapping_id = $map->blog_id;
				break;
			} elseif ( $map_domain == $req_domain && preg_match( "/^$bad_sub_dir_esc$/i", $uri ) ) {
				$is_https = array_key_exists( 'HTTPS', $_SERVER );
				$uri = 'http' . ( $is_https ? 's' : '') . '://' . $host . preg_replace( "/^$bad_sub_dir_esc$/i", $sub_dir, $uri );
				header( 'HTTP/1.1 301 Moved Permanently' );
				header( 'Location: ' . $uri );
				die();
			} elseif ( $map_domain == $req_domain && preg_match( "/^$sub_dir_esc/i", $uri ) ) {
				$is_https = array_key_exists( 'HTTPS', $_SERVER );
				$uri = 'http' . ( $is_https ? 's' : '') . '://' . $host . preg_replace( "/^$sub_dir_esc$/i", $sub_dir, $uri );
				header( 'HTTP/1.1 301 Moved Permanently' );
				header( 'Location: ' . $uri );
				die();
			}
		}
	}// End foreach().
}// End if().


//
// RMURPHY end customization
//
$wpdb->suppress_errors();
// RMURPHY begin customization - wrapping this in an if block to check if $domain_mapping_id was already set up above
if ( ! isset( $domain_mapping_id ) ) {
	$domain_mapping_id = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->dmtable} WHERE {$where} ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1" );
}
// RMURPHY - end customization
$wpdb->suppress_errors( false );
if ( $domain_mapping_id ) {
	$current_blog = $wpdb->get_row( "SELECT * FROM {$wpdb->blogs} WHERE blog_id = '$domain_mapping_id' LIMIT 1" );
	$current_blog->domain = $_SERVER['HTTP_HOST'];
	//
	// RMURPHY begin customization
	if ( $override_domain ) {
		$current_blog->path = substr( $override_domain, strpos( $override_domain, '/' ) );
	} else {
		$current_blog->path = '/';
	}
	//
	// RMURPHY end customization
	//
	$blog_id = $domain_mapping_id;
	$site_id = $current_blog->site_id;

	define( 'COOKIE_DOMAIN', $_SERVER['HTTP_HOST'] );

	$current_site = $wpdb->get_row( "SELECT * from {$wpdb->site} WHERE id = '{$current_blog->site_id}' LIMIT 0,1" );
	$current_site->blog_id = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->blogs} WHERE domain='{$current_site->domain}' AND path='{$current_site->path}'" );
	if ( function_exists( 'get_site_option' ) ) {
		$current_site->site_name = get_site_option( 'site_name' );
	} elseif ( function_exists( 'get_current_site_name' ) ) {
		$current_site = get_current_site_name( $current_site );
	}

	define( 'DOMAIN_MAPPING', 1 );
}

define( 'WPML_SUNRISE_MULTISITE_DOMAINS', true );
add_filter( 'query', 'sunrise_wpml_filter_queries' );
/**
 * Experimental feature
 * BAC.11/28/2016. Added WPML Configuration per https://wpml.org/documentation/support/multisite-support/languages-in-domains-for-wordpress-multisite-mode/
 * Version 1.0beta
 * Place this script in the wp-content folder and add "define('SUNRISE', 'on');" in wp-config.php in order to enable using different domains for different languages in multisite mode
 * WPML Sunrise Script - START
 *
 * @param $q Filter text strings for translation.
 * @return string
 */
function sunrise_wpml_filter_queries( $q ) {
	global $wpdb, $table_prefix, $current_blog;

	static $no_recursion;

	if ( empty( $current_blog ) && empty( $no_recursion ) ) {

		$no_recursion = true;

		$domain_found = preg_match( "#SELECT \\* FROM {$wpdb->blogs} WHERE domain = '(.*)'#", $q, $matches ) || preg_match( "#SELECT  blog_id FROM {$wpdb->blogs}  WHERE domain IN \\( '(\S*)' \\)#", $q, $matches );

		if ( $domain_found ) {

			if ( ! $wpdb->get_row( $q ) ) {

				$icl_blogs = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
				foreach ( $icl_blogs as $blog_id ) {
					$prefix = $blog_id > 1 ? $table_prefix . $blog_id . '_' : $table_prefix;
					$icl_settings = $wpdb->get_var( "SELECT option_value FROM {$prefix}options WHERE option_name='icl_sitepress_settings'" );
					if ( $icl_settings ) {
						$icl_settings = unserialize( $icl_settings );
						if ( $icl_settings && $icl_settings['language_negotiation_type'] == 2 ) {
							if ( in_array( 'http://' . $matches[1], $icl_settings['language_domains'] ) ) {
								$found_blog_id = $blog_id;
								break;
							}
							if ( in_array( $matches[1], $icl_settings['language_domains'] ) ) {
								$found_blog_id = $blog_id;
								break;
							}
						}
					}
				}

				if ( isset( $found_blog_id ) && $found_blog_id ) {
					$q = $wpdb->prepare( "SELECT * FROM {$wpdb->blogs} WHERE blog_id = %d ", $found_blog_id );
				}
			}
		}

		$no_recursion = false;

    }// End if().

    return $q;
} // WPML Sunrise Script - END

/**
 * Description
 *
 * @return type
 */
function pbrx_get_authoring_domain() {
	global $wpdb;
	$array = array();
	$array['file'] = basename( __FILE__ );
	$array['authoring_domain'] = AUTHORING_DOMAIN;
	$array['cookie_domain'] = COOKIE_DOMAIN;
	$array['domain_current_site'] = DOMAIN_CURRENT_SITE;
	$array['concat_request'] = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$array['http_host'] = $_SERVER['HTTP_HOST'];
	$array['esc_url'] = esc_url( $_SERVER['HTTP_HOST'] );
	$array['esc_url_raw'] = esc_url_raw( $_SERVER['HTTP_HOST'] );
	$array['esc_sql'] = esc_sql( $_SERVER['HTTP_HOST'] );
	$array['wpdb_escape'] = $_SERVER['HTTP_HOST'];
	$array['request_uri'] = $_SERVER['REQUEST_URI'];
	$array['blog_id'] = get_current_blog_id();

	$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->dmtable} WHERE blog_id LIKE '{$array['blog_id']}%' " );
	foreach ( $rows as $index => $map ) {
		$mapped_domain = trim( $map->domain );
		$mapped_blog = $map->blog_id;
		$array[ $index ] = 'Subsite ' . $mapped_blog . ' = ' . $mapped_domain;
	}

	$array['rows'] = $rows;
	return $array;
}

