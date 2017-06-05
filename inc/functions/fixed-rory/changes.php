<?php

add_action( 'network_admin_menu', 'original_plugin_menu' );

function original_plugin_menu() {
	add_dashboard_page( 'Original Domain Mapping Plugin', 'Original Domain Mapping Plugin', 'manage_options', 'original-plugin-menu', 'original_plugin_information' );
}
function original_plugin_information() {

	echo '<div class="wrap"><h2>' . __FUNCTION__ . '</h2>';

	echo '<pre><strong>Line 329 </strong> $domain = $wpdb->escape( $_POST[\'domain\'] );</pre>';

	echo '</div><!-- wrap -->';
}
