<?php
/*
 * Plugin Name: Domain Mapping Updated
 *
 * URI: https://github.com/pbrocks/domain-mapping-updated
 * Description: WordPress MU Domain Mapping (patched). Map any blog on a WordPress website to another domain.
 * Version: 1.9.4
 * Network: true
 * Author: Donncha O Caoimh & pbrocks
 * AuthorURI https://github.com/pbrocks
 */

namespace Domain_Mapping_Updated;

/**
 * Define a constant to check if site is network activated
 */
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	// Makes sure the plugin is defined before trying to use it
	include_once ABSPATH . '/wp-admin/includes/plugin.php';
}

if ( is_plugin_active_for_network( 'domain-mapping-updated/domain-mapping-updated.php' ) ) {
	// path to plugin folder and main file
	define( 'PLUGIN_NETWORK_ACTIVATED', true );
} else {
	define( 'PLUGIN_NETWORK_ACTIVATED', false );
}

if ( is_plugin_active_for_network( 'connect-core-wp/connect-core-wp.php' ) ) {
	// path to plugin folder and main file
	define( 'CONNECT_CORE', true );
} else {
	define( 'CONNECT_CORE', false );
}

require 'inc/functions/domain-mapping.php';

require_once 'autoload.php';

inc\classes\CSC_Theme_Customizer::init();
inc\classes\Domain_Mapping::init();
inc\classes\Domain_Mapping_Additions::init();
