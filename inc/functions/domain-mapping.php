<?php

/**
 * [dm_text_domain description]
 *
 * @return [type] [description]
 */
function dm_text_domain() {
	load_plugin_textdomain( 'domain-mapping-updated', basename( dirname( __FILE__ ) ) . 'languages', 'domain-mapping-updated/languages' );
}
add_action( 'init', 'dm_text_domain' );

// temp_enqueue_style
/**
 * [dm_enqueue_style description]
 *
 * @return [type] [description]
 */
function dm_enqueue_style( $hook ) {

	if ( 'settings_page_dm_domains_admin' === $hook || 'settings_page_dm_admin_page' === $hook || 'tools_page_domainmapping' === $hook ) {
		 wp_enqueue_style( 'domain-mapping', plugins_url( 'domain-mapping.css', __FILE__ ) );
	}

}
// add_action( 'admin_enqueue_scripts', 'dm_enqueue_style' );
/**
 * [dm_add_pages description]
 *
 * @return [type] [description]
 */
function dm_add_pages() {
	global $current_site, $wpdb, $wp_db_version, $wp_version;

	if ( ! isset( $current_site ) && $wp_db_version >= 15260 ) { // WP 3.0 network hasn't been configured
		add_action( 'admin_notices', 'domain_mapping_warning' );
		return false;
	}

	if ( '/' !== $current_site->path ) {
		install_in_root_warning();
	}

	if ( get_site_option( 'dm_user_settings' ) && $current_site->blog_id != $wpdb->blogid && ! dm_sunrise_warning( false ) ) {
		add_management_page( __( 'Domain Mapping', 'domain-mapping-updated' ), __( 'Domain Mapping', 'domain-mapping-updated' ), 'manage_options', 'domainmapping', 'dm_manage_page' );
	}
}
add_action( 'admin_menu', 'dm_add_pages' );
/**
 * [dm_network_pages description]
 *
 * @return [type] [description]
 */
function dm_network_pages() {
	add_submenu_page( 'settings.php', '0ld Domain Mapping', '0ld Domain Mapping', 'manage_options', 'dm_admin_page', 'dm_admin_page' );
	add_submenu_page( 'settings.php', '0ld Domains', '0ld Domains', 'manage_options', 'dm_domains_admin', 'dm_domains_admin' );
}
add_action( 'network_admin_menu', 'dm_network_pages' );


/**
 * [maybe_create_db description]
 *
 * @return [type] [description]
 */
function maybe_create_db() {
	global $wpdb;

	get_dm_hash(); // initialise the remote login hash

	$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
	$wpdb->dmtablelogins = $wpdb->base_prefix . 'domain_mapping_logins';
	if ( dm_site_admin() ) {
		$created = 0;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->dmtable}'" ) != $wpdb->dmtable ) {
			$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->dmtable}` (
				`id` bigint(20) NOT NULL auto_increment,
				`blog_id` bigint(20) NOT NULL,
				`domain` varchar(255) NOT NULL,
				`active` tinyint(4) default '1',
				PRIMARY KEY  (`id`),
				KEY `blog_id` (`blog_id`,`domain`,`active`)
			);");
			$created = 1;
		}
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->dmtablelogins}'" ) != $wpdb->dmtablelogins ) {
			$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->dmtablelogins}` (
				`id` varchar(32) NOT NULL,
				`user_id` bigint(20) NOT NULL,
				`blog_id` bigint(20) NOT NULL,
				`t` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				PRIMARY KEY  (`id`)
			);");
			$created = 1;
		}
		if ( $created ) {
			?> <div id="message" class="updated fade"><p><strong><?php _e( 'Domain mapping database table created.', 'domain-mapping-updated' ) ?></strong></p></div> <?php

		}
	}
}

/**
 * [dm_domains_admin description]
 *
 * @return [type] [description]
 */
function dm_domains_admin() {
	global $wpdb, $current_site;
	// paranoid? moi?
	if ( false === dm_site_admin() ) {
		return false;
	}
	echo '<div class="wrap">';
	dm_sunrise_warning();

	if ( '/' !== $current_site->path ) {
		install_in_root_warning();
	}

	echo '<h2>' . __( 'Adding/Editing Mapped Domains', 'domain-mapping-updated' ) . '</h2>';

	if ( ! empty( $_POST['action'] ) ) {
		check_admin_referer( 'domain_mapping' );
		$domain = strtolower( $_POST['domain'] );
		switch ( $_POST['action'] ) {
			case 'edit':
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->dmtable} WHERE domain = %s", $domain ) );
				if ( $row ) {
					dm_edit_domain( $row );
				} else {
					echo '<h3>' . __( 'Domain not found', 'domain-mapping-updated' ) . '</h3>';
				}
			break;
			case 'save':
				if ( $_POST['blog_id'] != 0 and
					$_POST['blog_id'] != 1 and
					null === $wpdb->get_var( $wpdb->prepare( "SELECT domain FROM {$wpdb->dmtable} WHERE blog_id != %d AND domain = %s", $_POST['blog_id'], $domain ) )
				) {
					if ( '' === $_POST['orig_domain'] ) {
						// Notice: Undefined index: active in /Applications/MAMP/htdocs/second-site/wp-content/plugins/domain-mapping-updated/inc/functions/domain-mapping.php on line 184
						$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dmtable} ( `blog_id`, `domain`, `active` ) VALUES ( %d, %s, %d )", $_POST['blog_id'], $domain, $_POST['active'] ) );
						echo '<p><strong>' . __( 'Domain Add', 'domain-mapping-updated' ) . '</strong></p>';
					} elseif ( $_POST['orig_domain'] == '' ) {
						$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dmtable} ( `blog_id`, `domain`, `active` ) VALUES ( %d, %s, %d )", $_POST['blog_id'], $domain, $_POST['active'] ) );
						echo '<p><strong>' . __( 'Domain Add', 'domain-mapping-updated' ) . '</strong></p>';
					} else {
						// Notice: Undefined index: active in /Applications/MAMP/htdocs/domain-maap/wp-content/plugins/domain-mapping-updated/inc/functions/domain-mapping.php on line 180
						$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->dmtable} SET blog_id = %d, domain = %s, active = %d WHERE domain = %s", $_POST['blog_id'], $domain, $_POST['active'], $_POST['orig_domain'] ) );
						echo '<p><strong>' . __( 'Domain Updated', 'domain-mapping-updated' ) . '</strong></p>';
					}
				}
			break;
			case 'del':
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtable} WHERE domain = %s", $domain ) );
				echo '<p><strong>' . __( 'Domain Deleted', 'domain-mapping-updated' ) . '</strong></p>';
			break;
			case 'search':
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->dmtable} WHERE domain LIKE %s", $domain ) );
				dm_domain_listing( $rows, sprintf( __( 'Searching for %s', 'domain-mapping-updated' ), esc_html( $domain ) ) );
			break;
		}// End switch().

		if ( $_POST['action'] == 'update' ) {
			if ( preg_match( '|^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$|', $_POST['ipaddress'] ) ) {
				update_site_option( 'dm_ipaddress', $_POST['ipaddress'] );
			}

			if ( ! preg_match( '/(--|\.\.)/', $_POST['cname'] ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $_POST['cname'] ) ) {
				update_site_option( 'dm_cname', stripslashes( $_POST['cname'] ) );
			} else {
				update_site_option( 'dm_cname', '' );
			}

			update_site_option( 'dm_301_redirect', intval( $_POST['permanent_redirect'] ) );
		}
	}// End if().
	echo '<div class="container-full"><div class="container-left">';
	new_search_listed_domains();
	echo '</div><div class="container-right">';
	dm_edit_domain();
	echo '</div><div class="container-inner">';
	new_list_mapped_domains();
	echo '</div>';
}//end dm_domains_admin()

/**
 * [new_search_listed_domains description]
 *
 * @return [type] [description]
 */
function new_search_listed_domains() {
	global $wpdb, $current_site;
	if ( false == dm_site_admin() ) { // paranoid? moi?
		return false;
	}

	echo '<h3>' . __( 'Search Existing Domains', 'domain-mapping-updated' ) . '</h3><div class="container-padding">';
	echo '<form method="POST">';
	wp_nonce_field( 'domain_mapping' );
	echo '<input type="hidden" name="action" value="search" />';
	echo '<p>';
	echo _e( 'Enter a mapped domain:', 'domain-mapping-updated' );
	echo " <input type='text' name='domain' value='' /></p>";
	echo "<p><input type='submit' class='button-secondary' value='" . __( 'Search', 'domain-mapping-updated' ) . "' /></p>";
	echo '</form><br></div>';
}
/**
 * [new_list_mapped_domains description]
 *
 * @return [type] [description]
 */
function new_list_mapped_domains() {
	global $wpdb, $current_site;
	$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->dmtable} ORDER BY id DESC LIMIT 0,20" );
	dm_domain_listing( $rows );
	echo '<p>' . sprintf( __( '<strong>Note:</strong> %s', 'domain-mapping-updated' ), dm_idn_warning() ) . '</p>';
}
/**
 * [dm_edit_domain description]
 *
 * @param  boolean $row [description]
 * @return [type]       [description]
 */
function dm_edit_domain( $row = false ) {
	if ( is_object( $row ) ) {
		echo '<h3>' . __( 'Edit Domain', 'domain-mapping-updated' ) . '</h3>';
	} else {
		echo '<h3>' . __( 'New Domain', 'domain-mapping-updated' ) . '</h3>';
		$row = new stdClass();
		$row->blog_id = '';
		$row->domain = '';
		$_POST['domain'] = '';
		$row->active = 1;
	}

	echo '<div class="container-padding"><form method="POST"><input type="hidden" name="action" value="save" /><input type="hidden" name="orig_domain" value="' . esc_attr( $_POST['domain'] ) . '" />';
	wp_nonce_field( 'domain_mapping' );
	echo "<table class='form-table'>\n";
	echo '<tr><th>' . __( 'Site ID', 'domain-mapping-updated' ) . "</th><td><input type='text' name='blog_id' value='{$row->blog_id}' /></td></tr>\n";
	echo '<tr><th>' . __( 'Domain', 'domain-mapping-updated' ) . "</th><td><input type='text' name='domain' value='{$row->domain}' /></td></tr>\n";
	if ( '1' === get_site_option( 'dm_no_primary_domain' ) ) {
		echo '<tr><th>' . __( '<span class="gray">Primary disabled</span>', 'domain-mapping-updated' ) . "</th><td><input type='checkbox' name='active' disabled value='1' ";
		echo $row->active == 1 ? 'checked=0 ' : ' ';
	} else {
		echo '<tr><th>' . __( 'Set as Primary', 'domain-mapping-updated' ) . "</th><td><input type='checkbox' name='active' value='1' ";
		echo $row->active == 1 ? 'checked=1 ' : ' ';
	}
	echo "/></td></tr>\n";

	echo '</table>';

	echo "<p><input type='submit' class='button-primary' value='" . __( 'Save', 'domain-mapping-updated' ) . "' /></p></form><br><br></div>";
}

/**
 * [dm_domain_listing description]
 *
 * @param  [type] $rows    [description]
 * @param  string $heading [description]
 * @return [type]          [description]
 */
function dm_domain_listing( $rows, $heading = '' ) {
	if ( $rows ) {
		if ( file_exists( ABSPATH . 'wp-admin/network/site-info.php' ) ) {
			$edit_url = network_admin_url( 'site-info.php' );
		} elseif ( file_exists( ABSPATH . 'wp-admin/sites.php' ) ) {
			$edit_url = admin_url( 'sites.php' );
		} else {
			$edit_url = admin_url( 'wpmu-blogs.php' );
		}
		if ( $heading != '' ) {
			echo "<h3>$heading</h3>";
		}
		echo '<table class="widefat" cellspacing="0"><thead><tr><th>' . __( 'Site ID', 'domain-mapping-updated' ) . '</th><th>' . __( 'Domain', 'domain-mapping-updated' ) . '</th><th>' . __( 'Primary', 'domain-mapping-updated' ) . '</th><th>' . __( 'Edit', 'domain-mapping-updated' ) . '</th><th>' . __( 'Delete', 'domain-mapping-updated' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo "<tr><td><a href='" . add_query_arg( array(
				'action' => 'editblog',
				'id' => $row->blog_id,
			), $edit_url) . "'>{$row->blog_id}</a></td><td><a href='//{$row->domain}/' target='_blank' >{$row->domain}</a></td><td>";
			echo $row->active == 1 ? __( 'Yes', 'domain-mapping-updated' ) : __( 'No', 'domain-mapping-updated' );
			echo "</td><td><form method='POST'><input type='hidden' name='action' value='edit' /><input type='hidden' name='domain' value='{$row->domain}' />";
			wp_nonce_field( 'domain_mapping' );
			echo "<input type='submit' class='button-secondary' value='" . __( 'Edit', 'domain-mapping-updated' ) . "' /></form></td><td><form method='POST'><input type='hidden' name='action' value='del' /><input type='hidden' name='domain' value='{$row->domain}' />";
			wp_nonce_field( 'domain_mapping' );
			echo "<input type='submit' class='button-secondary' value='" . __( 'Del', 'domain-mapping-updated' ) . "' /></form>";
			echo '</td></tr>';
		}
		echo '</table>';
		if ( '1' === get_site_option( 'dm_no_primary_domain' ) ) {
			primary_domains_disabled_salmon();
		}
		echo '</div>';
	}
}

/**
 * [dm_admin_page description]
 *
 * @return [type] [description]
 */
function dm_admin_page() {
	global $wpdb, $current_site;
	// paranoid? moi?
	if ( false === dm_site_admin() ) {
		return false;
	}

	if ( '/' !== $current_site->path ) {
		install_in_root_warning();
	}

	dm_sunrise_warning();

	maybe_create_db();

	dm_admin_page_config();
}

/**
 * [dm_admin_page_config description]
 *
 * @return [type] [description]
 */
function dm_admin_page_config() {
	echo '<div class=wrap>';
	echo '<h2>' . __( 'Configuring Domain Mapping Settings', 'domain-mapping-updated' ) . '</h2>';

	echo '<div class="container-full"><div class="container-inner"><div class="container-padding">';

	echo '<h3>' . __( 'Domain Mapping Configuration', 'domain-mapping-updated' ) . '</h3>';

	echo '<form method="POST">';
	echo '<input type="hidden" name="action" value="update" />';
	echo '<p>' . __( "As a super admin on this network you can set the IP address users need to point their DNS A records at <em>or</em> the domain to point CNAME record at. If you don't know what the IP address is, ping this blog to get it.", 'domain-mapping-updated' ) . '</p>';
	echo '<p>' . __( 'The information you enter here will be shown to your users so they can configure their DNS correctly. It is for informational purposes only', 'domain-mapping-updated' ) . '</p>';

	echo '</div></div>';

	general_domain_mapping_settings();

}

function general_domain_mapping_settings() {
	// function something_other() {
		global $wpdb, $current_site;

	// set up some defaults
	if ( 'NA' === get_site_option( 'dm_remote_login', 'NA' ) ) {
		add_site_option( 'dm_remote_login', 1 );
	}
	if ( 'NA' === get_site_option( 'dm_redirect_admin', 'NA' ) ) {
		add_site_option( 'dm_redirect_admin', 1 );
	}
	if ( 'NA' === get_site_option( 'dm_user_settings', 'NA' ) ) {
		add_site_option( 'dm_user_settings', 1 );
	}

	if ( ! empty( $_POST['action'] ) ) {
		check_admin_referer( 'domain_mapping' );
		if ( $_POST['action'] == 'update' ) {
			$ipok = true;
			$ipaddresses = explode( ',', $_POST['ipaddress'] );
			foreach ( $ipaddresses as $address ) {
				if ( ($ip = trim( $address )) && ! preg_match( '|^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$|', $ip ) ) {
					$ipok = false;
					break;
				}
			}
			if ( $ipok ) {
				update_site_option( 'dm_ipaddress', $_POST['ipaddress'] );
			}
			if ( intval( $_POST['always_redirect_admin'] ) == 0 ) {
				$_POST['dm_remote_login'] = 0; // disable remote login if redirecting to mapped domain
			}
			// Notice: Undefined index: dm_remote_login in /Applications/MAMP/htdocs/second-site/wp-content/plugins/domain-mapping-updated/inc/functions/domain-mapping.php on line 397
			// if ( ! empty( $_POST['dm_remote_login'] ) ) {
				update_site_option( 'dm_remote_login', intval( $_POST['dm_remote_login'] ) );
			// }
			if ( ! preg_match( '/(--|\.\.)/', $_POST['cname'] ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $_POST['cname'] ) ) {
				update_site_option( 'dm_cname', stripslashes( $_POST['cname'] ) );
			} else {
				update_site_option( 'dm_cname', '' );
			}
			update_site_option( 'dm_301_redirect', isset( $_POST['permanent_redirect'] ) ? intval( $_POST['permanent_redirect'] ) : 0 );
			update_site_option( 'dm_redirect_admin', isset( $_POST['always_redirect_admin'] ) ? intval( $_POST['always_redirect_admin'] ) : 0 );
			update_site_option( 'dm_user_settings', isset( $_POST['dm_user_settings'] ) ? intval( $_POST['dm_user_settings'] ) : 0 );
			update_site_option( 'dm_no_primary_domain', isset( $_POST['dm_no_primary_domain'] ) ? intval( $_POST['dm_no_primary_domain'] ) : 0 );
		}
	}

	echo '<div class="container-left">';
	echo '<h3>' . __( 'Server Configuration', 'domain-mapping-updated' ) . '</h3>';

	echo '<div class="container-padding"><p>' . __( 'If you use round robin DNS or another load balancing technique with more than one IP, enter each address, separating them by commas.', 'domain-mapping-updated' ) . '</p>';
	_e( 'Server IP Address: ', 'domain-mapping-updated' );
	echo "<input type='text' name='ipaddress' value='" . get_site_option( 'dm_ipaddress' ) . "' /><br>";

	// Using a CNAME is a safer method than using IP adresses for some people (IMHO)
	echo '<p>' . __( 'If you prefer the use of a CNAME record, you can set the domain here. This domain must be configured with an A record or ANAME pointing at an IP address. Visitors may experience problems if it is a CNAME of another domain.', 'domain-mapping-updated' ) . '</p>';
	echo '<p>' . __( 'NOTE, this voids the use of any IP address set above', 'domain-mapping-updated' ) . '</p>';
	_e( 'Server CNAME domain: ', 'domain-mapping-updated' );
	echo "<input type='text' name='cname' value='" . get_site_option( 'dm_cname' ) . "' /> <br>" . dm_idn_warning() . '<br>';

	echo '</div></div><div class="container-right">';

	echo '<h3>' . __( 'Domain Options', 'domain-mapping-updated' ) . '</h3>';
	echo '<div class="container-padding"><ol><li><input type="checkbox" name="dm_remote_login" value="1" ';
	echo get_site_option( 'dm_remote_login' ) == 1 ? "checked='checked'" : '';
	echo ' /> ' . __( 'Remote Login', 'domain-mapping-updated' ) . '</li>';
	echo "<li><input type='checkbox' name='permanent_redirect' value='1' ";
	echo get_site_option( 'dm_301_redirect' ) == 1 ? "checked='checked'" : '';
	echo ' /> ' . __( 'Permanent redirect (better for your blogger\'s pagerank)', 'domain-mapping-updated' ) . '</li>';
	echo "<li><input type='checkbox' name='dm_user_settings' value='1' ";
	echo get_site_option( 'dm_user_settings' ) == 1 ? "checked='checked'" : '';
	echo ' /> ' . __( 'User domain mapping page', 'domain-mapping-updated' ) . '</li> ';
	echo "<li><input type='checkbox' name='always_redirect_admin' value='1' ";
	echo get_site_option( 'dm_redirect_admin' ) == 1 ? "checked='checked'" : '';
	echo ' /> ' . __( "Redirect administration pages to site's original domain (remote login disabled if this redirect is disabled)", 'domain-mapping-updated' ) . '</li>';
	echo "<li><input type='checkbox' name='dm_no_primary_domain' value='1' ";
	echo get_site_option( 'dm_no_primary_domain' ) == 1 ? "checked='checked'" : '';
	echo ' /> ' . __( 'Disable primary domain check. Sites will not redirect to one domain name. May cause duplicate content issues.', 'domain-mapping-updated' ) . '</li></ol>';
	wp_nonce_field( 'domain_mapping' );
	echo "<p><input class='button-primary' type='submit' value='" . __( 'Save', 'domain-mapping-updated' ) . "' /></p>";
	echo '</form>';
	echo '<br><br><br><br></div></div>&nbsp;</div></div>';
}

/**
 * Wordpress function 'get_site_option' and 'get_option'
 *
 * @param  [type] $option_name [description]
 * @return [type]              [description]
 */
function get_this_plugin_option( $option_name ) {
	if ( true === PLUGIN_NETWORK_ACTIVATED ) {
		// Get network site option
		return get_site_option( $option_name );
	} else {
		// Get blog option
		return get_option( $option_name );
	}
}

/**
 * [update_this_plugin_option description]
 *
 * @return [type] [description]
 */
function update_this_plugin_option( $option_name, $option_value ) {
	if ( true === PLUGIN_NETWORK_ACTIVATED ) {
		// Update network site option
		return update_site_option( $option_name, $option_value );
	} else {
		// Update blog option
		return update_option( $option_name, $option_value );
	}
}

/**
 * [get_domain_mapping_network_option description]
 *
 * @return [type] [description]
 */
function get_domain_mapping_network_option() {
}

/**
 * [dm_handle_actions description]
 *
 * @return [type] [description]
 */
function dm_handle_actions() {
	global $wpdb, $parent_file;
	$url = add_query_arg(array(
		'page' => 'domainmapping',
	), admin_url( $parent_file ));
	if ( ! empty( $_POST['action'] ) ) {
		// $domain = $wpdb->escape( $_POST['domain'] );
		$domain = esc_sql( $_POST['domain'] );
		if ( $domain == '' ) {
			wp_die( 'You must enter a domain' );
		}
		check_admin_referer( 'domain_mapping' );
		do_action( 'dm_handle_actions_init', $domain );
		switch ( $_POST['action'] ) {
			case 'add':
				do_action( 'dm_handle_actions_add', $domain );
				if ( null === $wpdb->get_row( "SELECT blog_id FROM {$wpdb->blogs} WHERE domain = '$domain'" ) && null === $wpdb->get_row( "SELECT blog_id FROM {$wpdb->dmtable} WHERE domain = '$domain'" ) ) {
					if ( $_POST['primary'] ) {
						$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->dmtable} SET active = 0 WHERE blog_id = %d", $wpdb->blogid ) );
					}
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dmtable} ( `id` , `blog_id` , `domain` , `active` ) VALUES ( NULL, %d, %s, %d )", $wpdb->blogid, $domain, $_POST['primary'] ) );
					wp_redirect(add_query_arg(array(
						'updated' => 'add',
					), $url));
					exit;
				} else {
					wp_redirect(add_query_arg(array(
						'updated' => 'exists',
					), $url));
					exit;
				}
			break;
			case 'primary':
				do_action( 'dm_handle_actions_primary', $domain );
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->dmtable} SET active = 0 WHERE blog_id = %d", $wpdb->blogid ) );
				$orig_url = parse_url( get_original_url( 'siteurl' ) );
				if ( $domain != $orig_url['host'] ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->dmtable} SET active = 1 WHERE domain = %s", $domain ) );
				}
				wp_redirect(add_query_arg(array(
					'updated' => 'primary',
				), $url));
				exit;
			break;
		}
	} elseif ( array_key_exists( 'action', $_GET ) && $_GET['action'] == 'delete' ) {
		// $domain = $wpdb->escape( $_GET['domain'] );
		$domain = esc_sql( $_GET['domain'] );
		if ( $domain == '' ) {
			wp_die( __( 'You must enter a domain', 'domain-mapping-updated' ) );
		}
		check_admin_referer( 'delete' . $_GET['domain'] );
		do_action( 'dm_handle_actions_del', $domain );
		$wpdb->query( "DELETE FROM {$wpdb->dmtable} WHERE domain = '$domain'" );
		wp_redirect(add_query_arg(array(
			'updated' => 'del',
		), $url));
		exit;
	}// End if().
}
if ( isset( $_GET['page'] ) && $_GET['page'] == 'domainmapping' ) {
	add_action( 'admin_init', 'dm_handle_actions' );
}

/**
 * [dm_manage_page description]
 *
 * @return [type] [description]
 */
function dm_manage_page() {
	global $wpdb, $parent_file;

	if ( isset( $_GET['updated'] ) ) {
		do_action( 'dm_echo_updated_msg' );
	}

	dm_sunrise_warning();

	echo '<div class="wrap">';

	echo '<h2>' . __( 'Domain Mapping', 'domain-mapping-updated' ) . '</h2>';

	if ( false == get_site_option( 'dm_ipaddress' ) && false == get_site_option( 'dm_cname' ) ) {
		if ( dm_site_admin() ) {
			echo sprintf( 'Please set the IP address or CNAME of your server in the <a href="%s" target="_blank">site admin page</a>.', network_admin_url( $path = 'settings.php?page=dm_admin_page' ), 'domain-mapping-updated' );
		} else {
			_e( 'This plugin has not been configured correctly yet.', 'domain-mapping-updated' );
		}
		echo '</div>';
		return false;
	}
	echo '<div class="container-full"><div class="container-left">';
	echo '<h3>' . __( 'Add new domain', 'domain-mapping-updated' ) . '</h3>';
	echo '<div class="container-padding"><form method="POST">';
	echo '<input type="hidden" name="action" value="add" />';
	echo "<p>http(s)://<input type='text' name='domain' value='' />/<br>";
	wp_nonce_field( 'domain_mapping' );

	if ( '1' === get_site_option( 'dm_no_primary_domain' ) ) {

		echo  __( '<p class="gray">Domains can be added, but not set as Primary</p>', 'domain-mapping-updated' ) . '</p>';
	} else {
		echo '<input type="checkbox" name="primary" value="1"  /> ' . __( 'Set as Primary domain for this site.', 'domain-mapping-updated' ) . '</p>';
	}

	echo '<p><input type="submit" class="button-secondary" value="' . __( 'Add', 'domain-mapping-updated' ) . '" /></p>';
	echo '</form><br>';
	echo '</div></div><div class="container-right">';

	$protocol = is_ssl() ? 'https://' : 'http://';

	$domains = $wpdb->get_results( "SELECT * FROM {$wpdb->dmtable} WHERE blog_id = '{$wpdb->blogid}'", ARRAY_A );
	if ( is_array( $domains ) && ! empty( $domains ) ) {
		$orig_url = parse_url( get_original_url( 'siteurl' ) );
		$domains[] = array(
			'domain' => $orig_url['host'],
			'path' => $orig_url['path'],
			'active' => 0,
		);

		echo '<h3>' . __( 'Active domains on this blog', 'domain-mapping-updated' ) . '</h3>';
		echo '<div class="container-padding"><form method="POST">';
		echo '<table><tr><th>' . __( 'Primary', 'domain-mapping-updated' ) . '</th><th>' . __( 'Domain', 'domain-mapping-updated' ) . '</th><th>' . __( 'Delete', 'domain-mapping-updated' ) . "</th></tr>\n";
		$primary_found = 0;
		$del_url = add_query_arg(array(
			'page' => 'domainmapping',
			'action' => 'delete',
		), admin_url( $parent_file ));
		foreach ( $domains as $details ) {
			$details['path'] = array_key_exists( 'path', $details ) ? $details['path'] : '';
			if ( 0 == $primary_found && $details['domain'] == $orig_url['host'] ) {
				$details['active'] = 1;
			}
			echo '<tr><td>';
			echo "<input type='radio' name='domain' value='{$details[ 'domain' ]}' ";
			if ( $details['active'] == 1 ) {
				echo "checked='1' ";
			}
			echo '/>';
			$url = "{$protocol}{$details[ 'domain' ]}{$details[ 'path' ]}";
			echo "</td><td><a href='$url' target='_blank'>$url</a></td><td style='text-align: center'>";
			if ( $details['domain'] != $orig_url['host'] && $details['active'] != 1 ) {
				echo "<a href='" . wp_nonce_url(add_query_arg(array(
					'domain' => $details['domain'],
				), $del_url), 'delete' . $details['domain']) . "'>Del</a>";
			}
			echo '</td></tr>';
			if ( 0 === $primary_found ) {
				$primary_found = $details['active'];
			}
		} ?></table><?php

		echo '<p class="gray">' . __( 'Neither the original domain nor a primary domain can be deleted.', 'domain-mapping-updated' ) . '</p>';
		echo '<input type="hidden" name="action" value="primary" />';
if ( '1' === get_site_option( 'dm_no_primary_domain' ) ) {
	echo '<p><input type="submit" disabled class="button-primary" value="' . __( 'Set Primary Domain', 'domain-mapping-updated' ) . '" /></p>';
} else {
	echo '<p><input type="submit" class="button-primary" value="' . __( 'Set Primary Domain', 'domain-mapping-updated' ) . '" /></p>';
}
		wp_nonce_field( 'domain_mapping' );
		echo '</form></div>';

if ( '1' === get_site_option( 'dm_no_primary_domain' ) ) {
	primary_domains_disabled_salmon();
}
	}// End if().
	echo '</div><div class="container-inner">';

	if ( get_site_option( 'dm_cname' ) ) {
		$dm_cname = get_site_option( 'dm_cname' );
		echo '<p>' . sprintf( __( 'If you want to redirect a domain you will need to add a DNS "CNAME" record pointing to the following domain name for this server: <strong>%s</strong>', 'domain-mapping-updated' ), $dm_cname ) . '</p>';
		echo '<p>' . __( 'Google have published <a href="//www.google.com/support/blogger/bin/answer.py?hl=en&answer=58317" target="_blank">instructions</a> for creating CNAME records on various hosting platforms such as GoDaddy and others.', 'domain-mapping-updated' ) . '</p>';
	} else {
		echo '<p>' . __( 'If your domain name includes a hostname like "www", "blog" or some other prefix before the actual domain name you will need to add a CNAME for that hostname in your DNS pointing at this blog URL.', 'domain-mapping-updated' ) . '</p>';
		$dm_ipaddress = get_site_option( 'dm_ipaddress', 'IP not set by admin yet.' );
		if ( strpos( $dm_ipaddress, ',' ) ) {
			echo '<p>' . sprintf( __( 'If you want to redirect a domain you will need to add DNS "A" records pointing at the IP addresses of this server: <strong>%s</strong>', 'domain-mapping-updated' ), $dm_ipaddress ) . '</p>';
		} else {
			echo '<p>' . sprintf( __( 'If you want to redirect a domain you will need to add a DNS "A" record pointing at the IP address of this server: <strong>%s</strong>', 'domain-mapping-updated' ), $dm_ipaddress ) . '</p>';
		}
	}

	echo '<p>' . sprintf( __( '<strong>Note:</strong> %s', 'domain-mapping-updated' ), dm_idn_warning() ) . '</p>';
	echo '</div></div>';
	echo '</div>';
}
/**
 * [domain_mapping_siteurl description]
 *
 * @param  [type] $setting [description]
 * @return [type]          [description]
 */
function domain_mapping_siteurl( $setting ) {
	global $wpdb, $current_blog;

	// To reduce the number of database queries, save the results the first time we encounter each blog ID.
	static $return_url = array();

	$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';

	if ( ! isset( $return_url[ $wpdb->blogid ] ) ) {
		$s = $wpdb->suppress_errors();

		if ( get_site_option( 'dm_no_primary_domain' ) == 1 ) {
			// RMURPHY - Update to allow hosting both sub-domains and sub-directories.
			global $override_domain;
			if ( isset( $override_domain ) ) {
				$domain = $override_domain;
			} else {
				$query = $wpdb->prepare( "SELECT domain FROM {$wpdb->dmtable} WHERE blog_id = %d AND domain = %s LIMIT 1", $wpdb->blogid, $_SERVER['HTTP_HOST'] );
				$domain = $wpdb->get_var(
					$wpdb->prepare( "SELECT domain FROM {$wpdb->dmtable} WHERE blog_id = %d AND domain = %s LIMIT 1", $wpdb->blogid, $_SERVER['HTTP_HOST'] )
				);
			}
			// RMURPHY End Update
			if ( null === $domain ) {
				$return_url[ $wpdb->blogid ] = untrailingslashit( get_original_url( 'siteurl' ) );
				return $return_url[ $wpdb->blogid ];
			}
		} else {
			// RMURPHY - Update to allow hosting both sub-domains and sub-directories.
			global $override_domain;
			if ( isset( $override_domain ) ) {
				$domain = $override_domain;
			} else {
				// get primary domain, if we don't have one then return original url.
				$domain = $wpdb->get_var(
					$wpdb->prepare( 'SELECT domain FROM %s WHERE blog_id = %d AND active = 1 LIMIT 1', $wpdb->dmtable, $wpdb->blogid )
				);
			}
			// RMURPHY End Update.
			if ( null === $domain ) {
				$return_url[ $wpdb->blogid ] = untrailingslashit( get_original_url( 'siteurl' ) );
				return $return_url[ $wpdb->blogid ];
			}
		}// End if().

		$wpdb->suppress_errors( $s );
		$protocol = is_ssl() ? 'https://' : 'http://';
		if ( $domain ) {
			$return_url[ $wpdb->blogid ] = untrailingslashit( $protocol . $domain );
			$setting = $return_url[ $wpdb->blogid ];
		} else {
			$return_url[ $wpdb->blogid ] = false;
		}
	} elseif ( false !== $return_url[ $wpdb->blogid ] ) {
		$setting = $return_url[ $wpdb->blogid ];
	}// End if().

	return $setting;
}

/**
 * url is siteurl or home
 *
 * @param  [type]  $url     [description]
 * @param  integer $blog_id [description]
 * @return [type]           [description]
 */
function get_original_url( $url, $blog_id = 0 ) {
	global $wpdb;

	if ( $blog_id != 0 ) {
		$id = $blog_id;
	} else {
		$id = $wpdb->blogid;
	}

	static $orig_urls = array();
	if ( ! isset( $orig_urls[ $id ] ) ) {
		if ( defined( 'DOMAIN_MAPPING' ) ) {
			remove_filter( 'pre_option_' . $url, 'domain_mapping_' . $url );
		}
		if ( $blog_id == 0 ) {
			$orig_url = get_option( $url );
		} else {
			$orig_url = get_blog_option( $blog_id, $url );
		}
		if ( is_ssl() ) {
			$orig_url = str_replace( 'http://', 'https://', $orig_url );
		} else {
			$orig_url = str_replace( 'https://', 'http://', $orig_url );
		}
		if ( $blog_id == 0 ) {
			$orig_urls[ $wpdb->blogid ] = $orig_url;
		} else {
			$orig_urls[ $blog_id ] = $orig_url;
		}
		if ( defined( 'DOMAIN_MAPPING' ) ) {
			add_filter( 'pre_option_' . $url, 'domain_mapping_' . $url );
		}
	}
	return $orig_urls[ $id ];
}
/**
 * [domain_mapping_adminurl description]
 *
 * @param  [type]  $url     [description]
 * @param  [type]  $path    [description]
 * @param  integer $blog_id [description]
 * @return [type]           [description]
 */
function domain_mapping_adminurl( $url, $path, $blog_id = 0 ) {
	$index = strpos( $url, '/wp-admin' );
	if ( false !== $index ) {
		$url = get_original_url( 'siteurl', $blog_id ) . substr( $url, $index );

		// make sure admin_url is ssl if current page is ssl, or admin ssl is forced
		if ( (is_ssl() || force_ssl_admin()) && 0 === strpos( $url, 'http://' ) ) {
			$url = 'https://' . substr( $url, 7 );
		}
	}
	return $url;
}
/**
 * [domain_mapping_post_content description]
 *
 * @param  [type] $post_content [description]
 * @return [type]               [description]
 */
function domain_mapping_post_content( $post_content ) {
	global $wpdb;

	$orig_url = get_original_url( 'siteurl' );

	$url = domain_mapping_siteurl( 'NA' );
	if ( $url == 'NA' ) {
		return $post_content;
	}
	return str_replace( $orig_url, $url, $post_content );
}
/**
 * [dm_redirect_admin description]
 *
 * @return [type] [description]
 */
function dm_redirect_admin() {
	// don't redirect admin ajax calls
	if ( strpos( $_SERVER['REQUEST_URI'], 'wp-admin/admin-ajax.php' ) !== false ) {
		return;
	}

	if ( get_site_option( 'dm_redirect_admin' ) ) {
		// redirect mapped domain admin page to original url
		$url = get_original_url( 'siteurl' );
		if ( false === strpos( $url, $_SERVER['HTTP_HOST'] ) ) {
			wp_redirect( untrailingslashit( $url ) . $_SERVER['REQUEST_URI'] );
			exit;
		}
	} else {
		global $current_blog;
		// redirect original url to primary domain wp-admin/ - remote login is disabled!
		$url = domain_mapping_siteurl( false );
		$request_uri = str_replace( $current_blog->path, '/', $_SERVER['REQUEST_URI'] );
		if ( false === strpos( $url, $_SERVER['HTTP_HOST'] ) ) {
			wp_redirect( str_replace( '//wp-admin', '/wp-admin', trailingslashit( $url ) . $request_uri ) );
			exit;
		}
	}
}

function redirect_login_to_orig() {
	if ( ! get_site_option( 'dm_remote_login' ) || $_GET['action'] == 'logout' || isset( $_GET['loggedout'] ) ) {
		return false;
	}
	$url = get_original_url( 'siteurl' );
	if ( $url != site_url() ) {
		$url .= '/wp-login.php';
		echo "<script type='text/javascript'>\nwindow.location = '$url'</script>";
	}
}

/**
 * fixes the plugins_url
 *
 * @param  [type] $full_url [description]
 * @param  [type] $path     [description]
 * @param  [type] $plugin   [description]
 * @return [type]           [description]
 */
function domain_mapping_plugins_uri( $full_url, $path = null, $plugin = null ) {
	return get_option( 'siteurl' ) . substr( $full_url, stripos( $full_url, PLUGINDIR ) - 1 );
}
/**
 * [domain_mapping_themes_uri description]
 *
 * @param  [type] $full_url [description]
 * @return [type]           [description]
 */
function domain_mapping_themes_uri( $full_url ) {
	return str_replace( get_original_url( 'siteurl' ), get_option( 'siteurl' ), $full_url );
}

if ( defined( 'DOMAIN_MAPPING' ) ) {
	add_filter( 'plugins_url', 'domain_mapping_plugins_uri', 1 );
	add_filter( 'theme_root_uri', 'domain_mapping_themes_uri', 1 );
	add_filter( 'pre_option_siteurl', 'domain_mapping_siteurl' );
	add_filter( 'pre_option_home', 'domain_mapping_siteurl' );
	add_filter( 'the_content', 'domain_mapping_post_content' );
	add_action( 'wp_head', 'remote_login_js_loader' );
	add_action( 'login_head', 'redirect_login_to_orig' );
	add_action( 'wp_logout', 'remote_logout_loader', 9999 );

	add_filter( 'stylesheet_uri', 'domain_mapping_post_content' );
	add_filter( 'stylesheet_directory', 'domain_mapping_post_content' );
	add_filter( 'stylesheet_directory_uri', 'domain_mapping_post_content' );
	add_filter( 'template_directory', 'domain_mapping_post_content' );
	add_filter( 'template_directory_uri', 'domain_mapping_post_content' );
	add_filter( 'plugins_url', 'domain_mapping_post_content' );
} else {
	add_filter( 'admin_url', 'domain_mapping_adminurl', 10, 3 );
}
add_action( 'admin_init', 'dm_redirect_admin' );
if ( isset( $_GET['dm'] ) ) {
	add_action( 'template_redirect', 'remote_login_js' );
}
/**
 * [remote_logout_loader description]
 *
 * @return [type] [description]
 */
function remote_logout_loader() {
	global $current_site, $current_blog, $wpdb;
	$wpdb->dmtablelogins = $wpdb->base_prefix . 'domain_mapping_logins';
	$protocol = is_ssl() ? 'https://' : 'http://';
	$hash = get_dm_hash();
	$key = md5( time() );
	$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dmtablelogins} ( `id`, `user_id`, `blog_id`, `t` ) VALUES( %s, 0, %d, NOW() )", $key, $current_blog->blog_id ) );
	if ( get_site_option( 'dm_redirect_admin' ) ) {
		wp_redirect( $protocol . $current_site->domain . $current_site->path . "?dm={$hash}&action=logout&blogid={$current_blog->blog_id}&k={$key}&t=" . mt_rand() );
		exit;
	}
}
/**
 * [redirect_to_mapped_domain description]
 *
 * @return [type] [description]
 */
function redirect_to_mapped_domain() {
	global $current_blog, $wpdb;

	// don't redirect the main site
	if ( is_main_site() ) {
		return;
	}
	// don't redirect post previews
	if ( isset( $_GET['preview'] ) && $_GET['preview'] == 'true' ) {
		return;
	}

	// don't redirect theme customizer (WP 3.4)
	if ( isset( $_POST['customize'] ) && isset( $_POST['theme'] ) && $_POST['customize'] == 'on' ) {
		return;
	}

	$protocol = is_ssl() ? 'https://' : 'http://';
	$url = domain_mapping_siteurl( false );
	if ( $url && $url != untrailingslashit( $protocol . $current_blog->domain . $current_blog->path ) ) {
		$redirect = get_site_option( 'dm_301_redirect' ) ? '301' : '302';
		if ( (defined( 'VHOST' ) && constant( 'VHOST' ) != 'yes') || (defined( 'SUBDOMAIN_INSTALL' ) && constant( 'SUBDOMAIN_INSTALL' ) == false) ) {
			$_SERVER['REQUEST_URI'] = str_replace( $current_blog->path, '/', $_SERVER['REQUEST_URI'] );
		}
		header( "Location: {$url}{$_SERVER[ 'REQUEST_URI' ]}", true, $redirect );
		exit;
	}
}
add_action( 'template_redirect', 'redirect_to_mapped_domain' );
/**
 * [get_dm_hash description]
 *
 * @return [type] [description]
 */
function get_dm_hash() {
	$remote_login_hash = get_site_option( 'dm_hash' );
	if ( null === $remote_login_hash ) {
		$remote_login_hash = md5( time() );
		update_site_option( 'dm_hash', $remote_login_hash );
	}
	return $remote_login_hash;
}
/**
 * [remote_login_js description]
 *
 * @return [type] [description]
 */
function remote_login_js() {
	global $current_blog, $current_user, $wpdb;

	if ( 0 == get_site_option( 'dm_remote_login' ) ) {
		return false;
	}

	$wpdb->dmtablelogins = $wpdb->base_prefix . 'domain_mapping_logins';
	$hash = get_dm_hash();
	$protocol = is_ssl() ? 'https://' : 'http://';
	if ( $_GET['dm'] == $hash ) {
		if ( $_GET['action'] == 'load' ) {
			if ( ! is_user_logged_in() ) {
				exit;
			}
			$key = md5( time() . mt_rand() );
			$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dmtablelogins} ( `id`, `user_id`, `blog_id`, `t` ) VALUES( %s, %d, %d, NOW() )", $key, $current_user->ID, $_GET['blogid'] ) );
			$url = add_query_arg(array(
				'action' => 'login',
				'dm' => $hash,
				'k' => $key,
				't' => mt_rand(),
			), $_GET['back']);
			echo "window.location = '$url'";
			exit;
		} elseif ( $_GET['action'] == 'login' ) {
			if ( $details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->dmtablelogins} WHERE id = %s AND blog_id = %d", $_GET['k'], $wpdb->blogid ) ) ) {
				if ( $details->blog_id == $wpdb->blogid ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtablelogins} WHERE id = %s", $_GET['k'] ) );
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtablelogins} WHERE t < %d", (time() - 120) ) ); // remote logins survive for only 2 minutes if not used.
					wp_set_auth_cookie( $details->user_id );
					wp_redirect( remove_query_arg( array( 'dm', 'action', 'k', 't', $protocol . $current_blog->domain . $_SERVER['REQUEST_URI'] ) ) );
					exit;
				} else {
					wp_die( __( 'Incorrect or out of date login key', 'domain-mapping-updated' ) );
				}
			} else {
				wp_die( __( 'Unknown login key', 'domain-mapping-updated' ) );
			}
		} elseif ( $_GET['action'] == 'logout' ) {
			if ( $details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->dmtablelogins} WHERE id = %d AND blog_id = %d", $_GET['k'], $_GET['blogid'] ) ) ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtablelogins} WHERE id = %s", $_GET['k'] ) );
				$blog = get_blog_details( $_GET['blogid'] );
				wp_clear_auth_cookie();
				wp_redirect( trailingslashit( $blog->siteurl ) . 'wp-login.php?loggedout=true' );
				exit;
			} else {
				wp_die( __( 'Unknown logout key', 'domain-mapping-updated' ) );
			}
		}// End if().
	}// End if().
}
/**
 * [remote_login_js_loader description]
 *
 * @return [type] [description]
 */
function remote_login_js_loader() {
	global $current_site, $current_blog;

	if ( 0 == get_site_option( 'dm_remote_login' ) || is_user_logged_in() ) {
		return false;
	}

	$protocol = is_ssl() ? 'https://' : 'http://';
	$hash = get_dm_hash();
	echo "<script src='{$protocol}{$current_site->domain}{$current_site->path}?dm={$hash}&amp;action=load&amp;blogid={$current_blog->blog_id}&amp;siteid={$current_blog->site_id}&amp;t=" . mt_rand() . '&amp;back=' . urlencode( $protocol . $current_blog->domain . $_SERVER['REQUEST_URI'] ) . "' type='text/javascript'></script>";
}

/**
 * delete mapping if blog is deleted
 *
 * @param  [type] $blog_id [description]
 * @param  [type] $drop    [description]
 * @return [type]          [description]
 */
function delete_blog_domain_mapping( $blog_id, $drop ) {
	global $wpdb;
	$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
	if ( $blog_id && $drop ) {
		// Get an array of domain names to pass onto any delete_blog_domain_mapping actions
		$domains = $wpdb->get_col( $wpdb->prepare( "SELECT domain FROM {$wpdb->dmtable} WHERE blog_id  = %d", $blog_id ) );
		do_action( 'dm_delete_blog_domain_mappings', $domains );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtable} WHERE blog_id  = %d", $blog_id ) );
	}
}
add_action( 'delete_blog', 'delete_blog_domain_mapping', 1, 2 );

/**
 * [dm_site_admin description]
 *
 * @return [type] [description]
 */
function dm_site_admin() {
	if ( function_exists( 'is_super_admin' ) ) {
		return is_super_admin();
	} elseif ( function_exists( 'is_site_admin' ) ) {
		return is_site_admin();
	} else {
		return true;
	}
}

include( 'domain-mapping-notices.php' );
