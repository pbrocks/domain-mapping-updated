<?php


$sunrise = WP_PLUGIN_DIR . '/plugins/cmpbl-domain-mapping-csc/inc/files/sunrise.php';
// /Applications/MAMP/htdocs/domain-maap/wp-content/plugins/cmpbl-domain-mapping-csc/inc/files/sunrise.php
if ( is_readable( $sunrise ) ) {
	include $sunrise;
}
