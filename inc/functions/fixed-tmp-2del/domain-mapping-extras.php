<?php

function dm_fix_upload_url( $uploaddir ) {
	$siteurl = get_bloginfo( 'wpurl' );
	$origurl = get_original_url( 'siteurl' );

	$uploaddir['baseurl'] = str_replace( $origurl, $siteurl, $uploaddir['baseurl'] );
	$uploaddir['url'] = str_replace( $origurl, $siteurl, $uploaddir['url'] );

	return $uploaddir;
}

function dm_fix_wp_get_attachment_url( $url ) {
	$siteurl = get_bloginfo( 'wpurl' );
	$origurl = get_original_url( 'siteurl' );

	return str_replace( $origurl, $siteurl, $url );
}

function dm_fix_nav_menu_item_url( $atts, $item, $args ) {
	$siteurl = get_bloginfo( 'wpurl' );
	$origurl = get_original_url( 'siteurl' );
	if ( array_key_exists( 'href', $atts ) ) {
		$atts['href'] = str_replace( $origurl, $siteurl, $atts['href'] );
	}
	return $atts;
}

add_filter( 'nav_menu_link_attributes', 'dm_fix_nav_menu_item_url', 11, 3 );
add_filter( 'upload_dir', 'dm_fix_upload_url' );
add_filter( 'wp_get_attachment_url', 'dm_fix_wp_get_attachment_url' );
add_filter( 'wp_get_attachment_thumb_url', 'dm_fix_wp_get_attachment_url' );
// add_filter( 'theme_mod_background_image', 'dm_fix_wp_get_attachment_url' );
// add_filter( 'theme_mod_header_image', 'dm_fix_wp_get_attachment_url' );
add_filter( 'shortcode_content', 'domain_mapping_post_content' );
