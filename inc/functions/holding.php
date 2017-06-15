<?php

		echo '<p class="gray">' . __( 'Neither the original domain nor a primary domain can be deleted.', 'domain-mapping-updated' ) . '</p>';
		echo '<input type="hidden" name="action" value="primary" />';
		echo '<p>' . __( '* The primary domain cannot be deleted.', 'domain-mapping-updated' ) . '</p>';
		echo '<p><input type="submit" class="button-primary" value="' . __( 'Set Primary Domain', 'domain-mapping-updated' ) . '" /></p>';
		wp_nonce_field( 'domain_mapping' );
		echo '</form>';



	echo sprintf( 'Please set the IP address or CNAME of your server in the <a href="%s" target="_blank">site admin page</a>.', network_admin_url( $path = 'settings.php?page=dm_admin_page' ), 'domain-mapping-updated' );
	echo '<br>get_site_option( \'dm_ipaddress\' ) = ' . get_site_option( 'dm_ipaddress' );
	echo '<br>get_site_option( \'dm_cname\' ) = ' . get_site_option( 'dm_cname' );

	echo '<p>' . network_admin_url( $path = 'settings.php?page=dm_admin_page', $scheme = 'admin' ) . '</p>';
if ( false !== get_site_option( 'dm_ipaddress' ) && false !== get_site_option( 'dm_cname' ) ) {
	if ( 2 == dm_site_admin() ) {
		sprintf( 'Please set the IP address or CNAME of your server in the <a href="%s" target="_blank">site admin page</a>.', network_admin_url( $path = 'settings.php?page=dm_admin_page' ), 'domain-mapping-updated' );
	} else {
		_e( 'This plugin has not been configured correctly yet.', 'domain-mapping-updated' );
	}
	echo '</div>';
	return false;
}
