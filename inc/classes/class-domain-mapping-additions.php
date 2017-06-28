<?php

namespace Domain_Mapping_Updated\inc\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * An example of how to write code to PEAR's standards
 *
 * Docblock comments start with "/**" at the top.  Notice how the "/"
 * lines up with the normal indenting and the asterisks on subsequent rows
 * are in line with the first asterisk.  The last line of comment text
 * should be immediately followed on the next line by the closing
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Original Author <author@example.com>
 */
class Domain_Mapping_Additions {

	/**
	 * Description
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'domain_mapping_scripts' ) );
		add_action( 'wp_head', array( __CLASS__, 'fixing_header_images' ) );
		// add_action( 'wp_head', array( __CLASS__, 'fixing_background_images' ) );
		// add_action( 'wp_head', array( __CLASS__, 'fixing_meta_images' ) );
		// add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'dm_fix_nav_menu_item_url', 11, 3 ) );
		// add_filter( 'upload_dir', array( __CLASS__, 'dm_fix_upload_url' ) );
		// add_filter( 'wp_get_attachment_url', array( __CLASS__, 'dm_fix_wp_get_attachment_url' ) );
		// add_filter( 'wp_get_attachment_thumb_url', array( __CLASS__, 'dm_fix_wp_get_attachment_url' ) );
		// add_action( 'wp_enqueue_scripts', array( __CLASS__, 'domain_mapping_scripts' ) );
		// add_action( 'wp_head', array( __CLASS__, 'spit_out_stuff' ) );
		// add_action( 'admin_menu', array( __CLASS__, 'mapped_aliases_dashboard_menu' ) );
	}

	/**
	 * Description
	 *
	 * @return string j.
	 */
	public static function fixing_header_images() {
		if ( true === get_theme_mod( 'fix_header_image' ) ) {
			add_filter( 'theme_mod_header_image', array( __CLASS__, 'dm_fix_wp_get_attachment_url' ) );
		}
	}

	/**
	 * Description
	 *
	 * @return string j.
	 */
	public static function wds_force_image_https() {
		if ( true === get_theme_mod( 'wds_force_image_https' ) ) {
			add_filter( 'wp_calculate_image_srcset', array( __CLASS__, 'wds_force_https_in_srcset_images' ) );
		}
	}

	/**
	 * Description
	 *
	 * @return string j.
	 */
	public static function fixing_background_images() {
		if ( true === get_theme_mod( 'fix_background_image' ) ) {
			// echo '<h2 style="color:#700;text-align:center;">Booya Background</h2>' . __FILE__;
			add_filter( 'theme_mod_background_image', array( __CLASS__, 'dm_fix_wp_get_attachment_url' ), 99 );
		}
	}

	/**
	 * Description
	 *
	 * @return string j.
	 */
	public static function fixing_meta_images() {
		if ( true === get_theme_mod( 'fix_meta_image' ) ) {
			// echo '<h2 style="color:#700;text-align:center;">Booya Background</h2>' . __FILE__;
			add_filter( 'wp_get_attachment_metadata', array( __CLASS__, 'dm_fix_wp_get_attachment_url' ) );
		}
	}

	/**
	 * Description
	 *
	 * @param string $uploaddir Altered URL.
	 *
	 * @return type
	 */
	public static function dm_fix_upload_url( $uploaddir ) {
		$siteurl = get_bloginfo( 'wpurl' );
		$origurl = Domain_Mapping::get_original_url( 'siteurl' );

		$uploaddir['baseurl'] = str_replace( $origurl, $siteurl, $uploaddir['baseurl'] );
		$uploaddir['url']     = str_replace( $origurl, $siteurl, $uploaddir['url'] );

		return $uploaddir;
	}

	/**
	 * Description
	 *
	 * @return string j.
	 */
	public static function dm_fix_wp_get_attachment_url( $url ) {
		$siteurl = get_bloginfo( 'wpurl' );
		$origurl = Domain_Mapping::get_original_url( 'siteurl' );

		return str_replace( $origurl, $siteurl, $url );
	}

	/**
	 * Description
	 *
	 * @return string j.
	 */
	public static function dm_fix_nav_menu_item_url( $atts, $item, $args ) {
		$siteurl = get_bloginfo( 'wpurl' );
		$origurl = Domain_Mapping::get_original_url( 'siteurl' );
		if ( array_key_exists( 'href', $atts ) ) {
			$atts['href'] = str_replace( $origurl, $siteurl, $atts['href'] );
		}
		return $atts;
	}

	/**
	 * Forces all srcset images to be HTTPS.
	 *
	 * @author Brad Parbs
	 * @param  array $sources array of sources to use for scrset images.
	 * @return array          modified array of sources with HTTPS, or original array
	 */
	public function wds_force_https_in_srcset_images( $sources ) {

		// Allow user to disable this on local installations or non HTTPS servers.
		$should_filter = apply_filters( 'wds_force_https_in_srcset_images', true );

		// Verify type.
		if ( is_array( $sources ) && $should_filter ) {

			// Set up an a return array.
			$return_sources = [];

			// Loop through each source, and add back to our original return array.
			foreach ( $sources as $key => $value ) {

				// If we got a url, then we want to filter that to be HTTPS.
				if ( isset( $value['url'] ) ) {

					// Actually make the replacement to force https.
					$value['url'] = str_replace( 'http:', 'https:', $value['url'] );

					// Allow for further filtering.
					$value['url'] = apply_filters( 'wds_additional_srcset_filters', $value['url'] );
				}

				// Make sure we actually have some data before saving it back into an array.
				$return_value = isset( $value )?$value:array();
				$return_key   = isset( $key )?$key:'';

				// Save that so we can return it.
				$return_sources[ $return_key ] = $return_value;
			}

			return $return_sources;
		}

		return $sources;
	}

	/**
	 * Description
	 *
	 * @return void
	 */
	public static function domain_mapping_scripts() {
		wp_enqueue_style( 'show-stuff', plugins_url( '../css/show-stuff.css', __FILE__ ) );
		wp_enqueue_script( 'show-stuff', plugins_url( '../js/show-stuff.js', __FILE__ ), array( 'jquery' ) );
	}

	/**
	 * Description
	 *
	 * @return array
	 */
	public static function domain_mapping_domains() {
		$domains = $wpdb->get_results( "SELECT * FROM {$wpdb->dmtable} WHERE blog_id = '{$wpdb->blogid}'", ARRAY_A );
		return $domains;
	}

	/**
	 * Description
	 *
	 * @return array
	 */
	public static function prepare_domain_mapping_domains() {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->dmtable} WHERE blog_id = '{$wpdb->blogid}'", ARRAY_A );
		return $domains;
	}

	/**
	 * Example from the new codex https://developer.wordpress.org/reference/classes/wpdb/get_results/.
	 *
	 * @param type $some_parameter
	 * @return array
	 */
	public function some_function( $some_parameter ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT count(ID) as total FROM {$wpdb->prefix}your_table_without_prefix WHERE some_field_in_your_table=%d", $some_parameter )
		);
		return $results;
	}
	/**
	 * Creating the text domain
	 * Will change to csc.
	 */
	public static function window_size_scripts() {
		wp_enqueue_script( 'window-size', plugins_url( '../js/window-size.js', __FILE__ ), array( 'jquery' ) );
	}

	public static function mapped_aliases_dashboard_menu() {
		add_dashboard_page( __( 'Mapped Aliases Dashboard', 'textdomain' ), __( 'Mapped Aliases', 'textdomain' ), 'read', 'mapped-aliases-page', array( __CLASS__, 'mapped_domains_array_page' ) );
	}

	/**
	 * Description
	 *
	 * @return type
	 */
	public static function spit_out_stuff() {
		global $override_domain;

		$blog_id = get_current_blog_id();
		$home    = get_blog_option( $blog_id, 'home' );
		$siteurl = get_blog_option( $blog_id, 'siteurl' );

		if ( function_exists( 'pbrx_get_authoring_domain' ) ) {
			$auth_domain = pbrx_get_authoring_domain();
		}

		$mods = get_theme_mods();

		if ( true === get_theme_mod( 'show_diagnostics' ) ) {

			echo '<div class="csc-target"><pre>';
			// var_dump( $var );
			if ( 'mods' === get_theme_mod( 'diagnostic_type' ) ) {
				print_r( $mods );
			} else {
				print_r( $auth_domain );
			}
			echo '</pre></div>';
			echo '<div class="csc-trigger">Diagnostics';
			echo '</div>';
		}
	}

	public static function mapped_domains_array_page() {
		echo '<div class="wrap"><h2>' . __FUNCTION__ . '</h2>';

		$mapped_alias = Domain_Mapping::get_mapped_domains_array();

		echo '<pre>mapped_alias ';
		print_r( $mapped_alias );
		echo '</pre>';
		echo '</div>';
	}

}
