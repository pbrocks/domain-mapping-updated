<?php
/*
 * Plugin Name: Domain Mapping Updated
 *
 * URI: https://github.com/pbrocks/domain-mapping-updated
 * Description: WordPress MU Domain Mapping (patched). Map any blog on a WordPress website to another domain.
 * Version: 1.9.2
 * Author: Donncha O Caoimh & pbrocks
 * AuthorURI https://github.com/pbrocks
 */


namespace Domain_Mapping_Updated;

include( 'inc/functions/fixed-rory/domain-mapping.php' );

require_once( 'autoload.php' );

inc\classes\CSC_Theme_Customizer::init();
inc\classes\Domain_Mapping::init();
// inc\classes\Domain_Mapping_Additions::init();
