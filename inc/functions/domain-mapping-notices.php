<?php

/**
 * [primary_disabled description]
 *
 * @return [type] [description]
 */
function primary_domains_disabled_gray() {
	echo '<h4 class="gray">' . __( 'Primary domains are currently disabled.', 'domain-mapping-updated' ) . '</h4>';
}

/**
 * [primary_domains_disabled_notice description]
 *
 * @return [type] [description]
 */
function primary_domains_disabled_salmon() {
	echo '<h3 class="salmon">' . __( 'Primary domains are currently disabled.', 'domain-mapping-updated' ) . '</h3>';
}

/**
 * [domain_mapping_warning description]
 *
 * @return [type] [description]
 */
function domain_mapping_warning() {
	echo "<div id='domain-mapping-warning' class='updated fade'><p><strong>" . __( 'Domain Mapping Disabled.', 'domain-mapping-updated' ) . '</strong> ' . sprintf( __( 'You must <a href="%1$s">create a network</a> for it to work.', 'domain-mapping-updated' ), 'http://codex.wordpress.org/Create_A_Network' ) . '</p></div>';
}


/**
 * Default Messages for the users Domain Mapping management page
 * This can now be replaced by using:
 * remove_action( 'dm_echo_updated_msg','dm_echo_default_updated_msg' );
 * add_action( 'dm_echo_updated_msg','my_custom_updated_msg_function' );
 **/
function dm_echo_default_updated_msg() {
	switch ( $_GET['updated'] ) {
		case 'add':
			$msg = __( 'New domain added.', 'domain-mapping-updated' );
			break;
		case 'exists':
			$msg = __( 'New domain already exists.', 'domain-mapping-updated' );
			break;
		case 'primary':
			$msg = __( 'New primary domain.', 'domain-mapping-updated' );
			break;
		case 'del':
			$msg = __( 'Domain deleted.', 'domain-mapping-updated' );
			break;
	}
	echo "<div class='updated fade'><p>$msg</p></div>";
}
// add_action( 'dm_echo_updated_msg', 'dm_echo_default_updated_msg' );
/**
 * [dm_sunrise_warning description]
 *
 * @param  boolean $die [description]
 * @return [type]       [description]
 */
function dm_sunrise_warning( $die = true ) {
	if ( ! file_exists( WP_CONTENT_DIR . '/sunrise.php' ) ) {
		if ( ! $die ) {
			return true;
		}

		if ( dm_site_admin() ) {
			wp_die( sprintf( __( 'Please copy sunrise.php to %1\$s/sunrise.php and ensure the SUNRISE definition is in %2\$swp-config.php', 'domain-mapping-updated' ), WP_CONTENT_DIR, ABSPATH ) );
		} else {
			wp_die( __( 'This plugin has not been configured correctly yet.', 'domain-mapping-updated' ) );
		}
	} elseif ( ! defined( 'SUNRISE' ) ) {
		if ( ! $die ) {
			return true;
		}

		if ( dm_site_admin() ) {
			wp_die( sprintf( __( '<h3 class="salmon">Please uncomment the line <em>define( \'SUNRISE\', \'on\' );</em> or add it to your %swp-config.php', 'domain-mapping-updated' ), ABSPATH ) );
		} else {
			wp_die( __( 'This plugin has not been configured correctly yet.', 'domain-mapping-updated' ) );
		}
	} elseif ( ! defined( 'SUNRISE_LOADED' ) ) {
		if ( ! $die ) {
			return true;
		}

		if ( dm_site_admin() ) {
			wp_die( sprintf( __( "Please edit your %swp-config.php and move the line <em>define( 'SUNRISE', 'on' );</em> above the last require_once() in that file or make sure you updated sunrise.php.", 'domain-mapping-updated' ), ABSPATH ) );
		} else {
			wp_die( __( 'This plugin has not been configured correctly yet.', 'domain-mapping-updated' ) );
		}
	}
	return false;
}

/**
 * [install_in_root_warning description]
 *
 * @return [type] [description]
 */
function install_in_root_warning() {
	global $wpdb, $current_site;

	$screen = get_current_screen();

	echo '<pre>';
	// print_r( $screen );
	echo '</pre>';

	wp_die( sprintf( __( '<h3 class="salmon" style=" line-height:2;padding:2rem 4rem;"><strong>Warning!</strong> This plugin will only work if WordPress is installed in the root directory of your webserver. It is currently installed in &#8217;%s&#8217;.</h3>', 'domain-mapping-updated' ), $current_site->path ) );
}

/**
 * [dm_idn_warning description]
 *
 * @return [type] [description]
 */
function dm_idn_warning() {
	return sprintf( __( ' International Domain Names should be in <a href="%s">punycode</a> format. ', 'domain-mapping-updated' ), 'http://api.webnic.cc/idnconversion.html' );
}
