<?php
/**
 * Short description for file
 *
 * Long description for file (if any)...

 * @since      File available since Release 1.2.0
 * @deprecated File deprecated in Release 2.0.0
 */

namespace Domain_Mapping_Updated\inc\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * An example of how to write code to PEAR's standards
 *
 * Docblock comments start with "/**" at the top.  Notice
 *
 * @since      Class available since Release 1.2.0
 * @deprecated Class deprecated in Release 2.0.0
 */
class Domain_Mapping {
	/**
	 * Description
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_head', array( __CLASS__, 'echo_current_subsite' ) );
		add_action( 'admin_menu', array( __CLASS__, 'domain_mapping_menu' ) );
		add_action( 'network_admin_menu', array( __CLASS__, 'domain_mapping_menu' ) );
		add_action( 'network_admin_menu', array( __CLASS__, 'domain_mapping_network_menus' ) );
		// add_action( 'admin_menu', array( __CLASS__, 'domain_mapping_subsite_menus' ) );
		add_action( 'admin_menu', array( __CLASS__, 'diagnositc_submenu_page' ) );
		// add_action( 'init', array( __CLASS__, 'domain_mapping_filters' ) );
		add_action( 'manage_sites_custom_column', array( __CLASS__, 'add_column_for_aliases' ), 10, 2 );
		add_action( 'manage_blogs_custom_column', array( __CLASS__, 'add_column_for_aliases' ), 10, 2 );
		add_action( 'admin_footer', array( __CLASS__, 'set_column_width' ) );
		add_filter( 'wpmu_blogs_columns', array( __CLASS__, 'add_domain_mapping_column_label' ) );
		// add_action( 'delete_blog', array( __CLASS__, 'delete_blog_domain_mapping', 1, 2 ) );
		// add_action( 'template_redirect', array( __CLASS__, 'redirect_to_mapped_domain' ) );
		// add_action( 'dm_echo_updated_msg', array( __CLASS__, 'dm_echo_default_updated_msg' ) );
		// add_action( 'admin_init', array( __CLASS__, 'dm_redirect_admin' ) );
	}

	public static function domain_mapping_filters() {
		if ( defined( 'DOMAIN_MAPPING' ) ) {
			add_filter( 'plugins_url', array( __CLASS__, 'domain_mapping_plugins_uri', 1 ) );
			add_filter( 'theme_root_uri', array( __CLASS__, 'domain_mapping_themes_uri', 1 ) );
			add_filter( 'pre_option_siteurl', array( __CLASS__, 'domain_mapping_siteurl' ) );
			add_filter( 'pre_option_home', array( __CLASS__, 'domain_mapping_siteurl' ) );
			add_filter( 'the_content', array( __CLASS__, 'domain_mapping_post_content' ) );
			add_action( 'wp_head', array( __CLASS__, 'remote_login_js_loader' ) );
			add_action( 'login_head', array( __CLASS__, 'redirect_login_to_orig' ) );
			add_action( 'wp_logout', array( __CLASS__, 'remote_logout_loader', 9999 ) );
			add_action( 'template_redirect', array( __CLASS__, 'redirect_to_mapped_domain' ) );
			add_filter( 'stylesheet_uri', array( __CLASS__, 'domain_mapping_post_content' ) );
			add_filter( 'stylesheet_directory', array( __CLASS__, 'domain_mapping_post_content' ) );
			add_filter( 'stylesheet_directory_uri', array( __CLASS__, 'domain_mapping_post_content' ) );
			add_filter( 'template_directory', array( __CLASS__, 'domain_mapping_post_content' ) );
			add_filter( 'template_directory_uri', array( __CLASS__, 'domain_mapping_post_content' ) );
			add_filter( 'plugins_url', array( __CLASS__, 'domain_mapping_post_content' ) );
		} else {
			// add_filter( 'admin_url', array( __CLASS__, 'domain_mapping_adminurl', 10, 3 ) );
		}
		if ( isset( $_GET['dm'] ) ) {
			add_action( 'template_redirect', 'remote_login_js' );
		}
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'domainmapping' ) {
			add_action( 'admin_init', 'dm_handle_actions' );
		}
	}

	/**
	 * Description
	 *
	 * @return type her.
	 */
	public static function echo_current_subsite() {
		$subsite = get_current_blog_id();
		if ( is_network_admin() ) {
			echo '<h3 style="position:absolute; top:2rem; right:10%; color:#700;">You are viewing the WordPress MultiSite network administration page</h3>';
		} else {
			$blog_id = get_current_blog_id();
			echo '<h3 style="position:absolute; top:2rem; right:10%; color:#700;">You are viewing a WordPress MultiSite Subsite #' . $blog_id . ' admin page</h3>';
		}
	}

	/**
	 * Description
	 *
	 * @return type her.
	 */
	public static function dm_add_pages() {
		global $current_site, $wpdb, $wp_db_version, $wp_version;

		if ( $current_site->path != '/' ) {
			wp_die( __( 'The domain mapping plugin only works if the site is installed in /. This is a limitation of how virtual servers work and is very difficult to work around.', 'cmpbl-domain-mapping' ) );
		}

		if ( get_site_option( 'dm_user_settings' ) && $current_site->blog_id != $wpdb->blogid && ! self::dm_sunrise_warning( false ) ) {
			add_management_page( __( 'Domain Mapping', 'cmpbl-domain-mapping' ), __( 'Domain Mapping', 'cmpbl-domain-mapping' ), 'manage_options', 'domainmapping', array( __CLASS__, 'dm_manage_page' ) );
		}

	}

	// Default Messages for the users Domain Mapping management page
	// This can now be replaced by using:
	// remove_action('dm_echo_updated_msg','dm_echo_default_updated_msg');
	// add_action('dm_echo_updated_msg','my_custom_updated_msg_function');
	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_echo_default_updated_msg() {
		switch ( $_GET['updated'] ) {
			case 'add':
				$msg = __( 'New domain added.', 'cmpbl-domain-mapping' );
				break;
			case 'exists':
				$msg = __( 'New domain already exists.', 'cmpbl-domain-mapping' );
				break;
			case 'primary':
				$msg = __( 'New primary domain.', 'cmpbl-domain-mapping' );
				break;
			case 'del':
				$msg = __( 'Domain deleted.', 'cmpbl-domain-mapping' );
				break;
		}
		echo "<div class='updated fade'><p>$msg</p></div>";
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function maybe_create_db() {
		global $wpdb;

		self::get_dm_hash(); // initialise the remote login hash

		$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
		$wpdb->dmtablelogins = $wpdb->base_prefix . 'domain_mapping_logins';
		if ( self::dm_site_admin() ) {
			$created = 0;
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->dmtable}'" ) != $wpdb->dmtable ) {
				$wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->dmtable}` (
					`id` bigint(20) NOT NULL auto_increment,
					`blog_id` bigint(20) NOT NULL,
					`domain` varchar(255) NOT NULL,
					`active` tinyint(4) default '1',
					PRIMARY KEY  (`id`),
					KEY `blog_id` (`blog_id`,`domain`,`active`)
				);" );
				$created = 1;
			}
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->dmtablelogins}'" ) != $wpdb->dmtablelogins ) {
				$wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->dmtablelogins}` (
					`id` varchar(32) NOT NULL,
					`user_id` bigint(20) NOT NULL,
					`blog_id` bigint(20) NOT NULL,
					`t` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
					PRIMARY KEY  (`id`)
				);" );
				$created = 1;
			}
			if ( $created ) {
				?> <div id="message" class="updated fade"><p><strong><?php _e( 'Domain mapping database table created.', 'cmpbl-domain-mapping' ) ?></strong></p></div> <?php
			}
		}
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_domains_admin() {
		global $wpdb, $current_site;
		if ( false == self::dm_site_admin() ) { // paranoid? moi?
			return false;
		}

		self::dm_sunrise_warning();

		if ( $current_site->path != '/' ) {
			wp_die( sprintf( __( '<strong>Warning!</strong> This plugin will only work if WordPress is installed in the root directory of your webserver. It is currently installed in &#8217;%s&#8217;.', 'cmpbl-domain-mapping' ), $current_site->path ) );
		}

		echo '<h2>' . __( 'Domain Mapping: Domains', 'cmpbl-domain-mapping' ) . '</h2>';
		if ( ! empty( $_POST['action'] ) ) {
			check_admin_referer( 'domain_mapping' );
			$domain = strtolower( $_POST['domain'] );
			switch ( $_POST['action'] ) {
				case 'edit':
					$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->dmtable} WHERE domain = %s", $domain ) );
					if ( $row ) {
						self::dm_edit_domain( $row );
					} else {
						echo '<h3>' . __( 'Domain not found', 'cmpbl-domain-mapping' ) . '</h3>';
					}
				break;
				case 'save':
					if ( $_POST['blog_id'] != 0 and
						$_POST['blog_id'] != 1 and
						null == $wpdb->get_var( $wpdb->prepare( "SELECT domain FROM {$wpdb->dmtable} WHERE blog_id != %d AND domain = %s", $_POST['blog_id'], $domain ) )
					) {
						if ( $_POST['orig_domain'] == '' ) {
							$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dmtable} ( `blog_id`, `domain`, `active` ) VALUES ( %d, %s, %d )", $_POST['blog_id'], $domain, $_POST['active'] ) );
							echo '<p><strong>' . __( 'Domain Add', 'cmpbl-domain-mapping' ) . '</strong></p>';
						} else {
							$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->dmtable} SET blog_id = %d, domain = %s, active = %d WHERE domain = %s", $_POST['blog_id'], $domain, $_POST['active'], $_POST['orig_domain'] ) );
							echo '<p><strong>' . __( 'Domain Updated', 'cmpbl-domain-mapping' ) . '</strong></p>';
						}
					}
				break;
				case 'del':
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtable} WHERE domain = %s", $domain ) );
					echo '<p><strong>' . __( 'Domain Deleted', 'cmpbl-domain-mapping' ) . '</strong></p>';
				break;
				case 'search':
					$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->dmtable} WHERE domain LIKE %s", $domain ) );
					self::dm_domain_listing( $rows, sprintf( __( 'Searching for %s', 'cmpbl-domain-mapping' ), esc_html( $domain ) ) );
				break;
			}
			if ( $_POST['action'] == 'update' ) {
				if ( preg_match( '|^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$|', $_POST['ipaddress'] ) ) {
					update_site_option( 'dm_ipaddress', $_POST['ipaddress'] );
				}

				if ( ! preg_match( '/(--|\.\.)/', $_POST['cname'] ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $_POST['cname'] ) ) {
					update_site_option( 'dm_cname', stripslashes( $_POST['cname'] ) );
				} else { update_site_option( 'dm_cname', '' );
				}

				update_site_option( 'dm_301_redirect', intval( $_POST['permanent_redirect'] ) );
			}
		}// End if().

		echo '<h3>' . __( 'Search Domains', 'cmpbl-domain-mapping' ) . '</h3>';
		echo '<form method="POST">';
		wp_nonce_field( 'domain_mapping' );
		echo '<input type="hidden" name="action" value="search" />';
		echo '<p>';
		echo _e( 'Domain:', 'cmpbl-domain-mapping' );
		echo " <input type='text' name='domain' value='' /></p>";
		echo "<p><input type='submit' class='button-secondary' value='" . __( 'Search', 'cmpbl-domain-mapping' ) . "' /></p>";
		echo '</form><br>';
		self::dm_edit_domain();
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->dmtable} ORDER BY id DESC LIMIT 0,20" );
		self::dm_domain_listing( $rows );
		echo '<p>' . sprintf( __( '<strong>Note:</strong> %s', 'cmpbl-domain-mapping' ), self::dm_idn_warning() ) . '</p>';
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_edit_domain( $row = false ) {
		if ( is_object( $row ) ) {
			echo '<h3>' . __( 'Edit Domain', 'cmpbl-domain-mapping' ) . '</h3>';
		} else {
			echo '<h3>' . __( 'New Domain', 'cmpbl-domain-mapping' ) . '</h3>';
			// $row = new stdClass();
			$row->blog_id = '';
			$row->domain = '';
			$_POST['domain'] = '';
			$row->active = 1;
		}

		echo "<form method='POST'><input type='hidden' name='action' value='save' /><input type='hidden' name='orig_domain' value='" . esc_attr( $_POST['domain'] ) . "' />";
		wp_nonce_field( 'domain_mapping' );
		echo "<table class='form-table'>\n";
		echo '<tr><th>' . __( 'Site ID', 'cmpbl-domain-mapping' ) . "</th><td><input type='text' name='blog_id' value='{$row->blog_id}' /></td></tr>\n";
		echo '<tr><th>' . __( 'Domain', 'cmpbl-domain-mapping' ) . "</th><td><input type='text' name='domain' value='{$row->domain}' /></td></tr>\n";
		echo '<tr><th>' . __( 'Primary', 'cmpbl-domain-mapping' ) . "</th><td><input type='checkbox' name='active' value='1' ";
		echo $row->active == 1 ? 'checked=1 ' : ' ';
		echo "/></td></tr>\n";
		if ( get_site_option( 'dm_no_primary_domain' ) == 1 ) {
			echo "<tr><td colspan='2'>" . __( '<strong>Warning!</strong> Primary domains are currently disabled.', 'cmpbl-domain-mapping' ) . '</td></tr>';
		}
		echo '</table>';
		echo "<p><input type='submit' class='button-primary' value='" . __( 'Save', 'cmpbl-domain-mapping' ) . "' /></p></form><br><br>";
	}

	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_domain_listing( $rows, $heading = '' ) {
		if ( $rows ) {
			if ( file_exists( ABSPATH . 'wp-admin/network/site-info.php' ) ) {
				$edit_url = network_admin_url( 'site-info.php' );
			} elseif ( file_exists( ABSPATH . 'wp-admin/ms-sites.php' ) ) {
				$edit_url = admin_url( 'ms-sites.php' );
			} else {
				$edit_url = admin_url( 'wpmu-blogs.php' );
			}
			if ( $heading != '' ) {
				echo "<h3>$heading</h3>";
			}
			echo '<table class="widefat" cellspacing="0"><thead><tr><th>' . __( 'Site ID', 'cmpbl-domain-mapping' ) . '</th><th>' . __( 'Domain', 'cmpbl-domain-mapping' ) . '</th><th>' . __( 'Primary', 'cmpbl-domain-mapping' ) . '</th><th>' . __( 'Edit', 'cmpbl-domain-mapping' ) . '</th><th>' . __( 'Delete', 'cmpbl-domain-mapping' ) . '</th></tr></thead><tbody>';
			foreach ( $rows as $row ) {
				echo "<tr><td><a href='" . add_query_arg( array(
					'action' => 'editblog',
					'id' => $row->blog_id,
				), $edit_url ) . "'>{$row->blog_id}</a></td><td><a href='http://{$row->domain}/'>{$row->domain}</a></td><td>";
				echo $row->active == 1 ? __( 'Yes',  'cmpbl-domain-mapping' ) : __( 'No',  'cmpbl-domain-mapping' );
				echo "</td><td><form method='POST'><input type='hidden' name='action' value='edit' /><input type='hidden' name='domain' value='{$row->domain}' />";
				wp_nonce_field( 'domain_mapping' );
				echo "<input type='submit' class='button-secondary' value='" . __( 'Edit', 'cmpbl-domain-mapping' ) . "' /></form></td><td><form method='POST'><input type='hidden' name='action' value='del' /><input type='hidden' name='domain' value='{$row->domain}' />";
				wp_nonce_field( 'domain_mapping' );
				echo "<input type='submit' class='button-secondary' value='" . __( 'Del', 'cmpbl-domain-mapping' ) . "' /></form>";
				echo '</td></tr>';
			}
			echo '</table>';
			if ( get_site_option( 'dm_no_primary_domain' ) == 1 ) {
				echo '<p>' . __( '<strong>Warning!</strong> Primary domains are currently disabled.', 'cmpbl-domain-mapping' ) . '</p>';
			}
		}
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_admin_page() {
		global $wpdb, $current_site;
		if ( false == self::dm_site_admin() ) { // paranoid? moi?
			return false;
		}

		self::dm_sunrise_warning();
		self::maybe_create_db();

		if ( $current_site->path != '/' ) {
			wp_die( sprintf( __( '<strong>Warning!</strong> This plugin will only work if WordPress is installed in the root directory of your webserver. It is currently installed in &#8217;%s&#8217;.', 'cmpbl-domain-mapping' ), $current_site->path ) );
		}

		// set up some defaults
		if ( get_site_option( 'dm_remote_login', 'NA' ) == 'NA' ) {
			add_site_option( 'dm_remote_login', 1 );
		}
		if ( get_site_option( 'dm_redirect_admin', 'NA' ) == 'NA' ) {
			add_site_option( 'dm_redirect_admin', 1 );
		}
		if ( get_site_option( 'dm_user_settings', 'NA' ) == 'NA' ) {
			add_site_option( 'dm_user_settings', 1 );
		}

		if ( ! empty( $_POST['action'] ) ) {
			check_admin_referer( 'domain_mapping' );
			if ( $_POST['action'] == 'update' ) {
				$ipok = true;
				$ipaddresses = explode( ',', $_POST['ipaddress'] );
				foreach ( $ipaddresses as $address ) {
					if ( ( $ip = trim( $address ) ) && ! preg_match( '|^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$|', $ip ) ) {
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
				update_site_option( 'dm_remote_login', intval( $_POST['dm_remote_login'] ) );
				if ( ! preg_match( '/(--|\.\.)/', $_POST['cname'] ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $_POST['cname'] ) ) {
					update_site_option( 'dm_cname', stripslashes( $_POST['cname'] ) );
				} else { update_site_option( 'dm_cname', '' );
				}
				update_site_option( 'dm_301_redirect', isset( $_POST['permanent_redirect'] ) ? intval( $_POST['permanent_redirect'] ) : 0 );
				update_site_option( 'dm_redirect_admin', isset( $_POST['always_redirect_admin'] ) ? intval( $_POST['always_redirect_admin'] ) : 0 );
				update_site_option( 'dm_user_settings', isset( $_POST['dm_user_settings'] ) ? intval( $_POST['dm_user_settings'] ) : 0 );
				update_site_option( 'dm_no_primary_domain', isset( $_POST['dm_no_primary_domain'] ) ? intval( $_POST['dm_no_primary_domain'] ) : 0 );
			}
		}

		echo '<h3>' . __( 'Domain Mapping Configuration', 'cmpbl-domain-mapping' ) . '</h3>';
		echo '<form method="POST">';
		echo '<input type="hidden" name="action" value="update" />';
		echo '<p>' . __( "As a super admin on this network you can set the IP address users need to point their DNS A records at <em>or</em> the domain to point CNAME record at. If you don't know what the IP address is, ping this blog to get it.", 'cmpbl-domain-mapping' ) . '</p>';
		echo '<p>' . __( 'If you use round robin DNS or another load balancing technique with more than one IP, enter each address, separating them by commas.', 'cmpbl-domain-mapping' ) . '</p>';
		_e( 'Server IP Address: ', 'cmpbl-domain-mapping' );
		echo "<input type='text' name='ipaddress' value='" . get_site_option( 'dm_ipaddress' ) . "' /><br>";

		// Using a CNAME is a safer method than using IP adresses for some people (IMHO)
		echo '<p>' . __( 'If you prefer the use of a CNAME record, you can set the domain here. This domain must be configured with an A record or ANAME pointing at an IP address. Visitors may experience problems if it is a CNAME of another domain.', 'cmpbl-domain-mapping' ) . '</p>';
		echo '<p>' . __( 'NOTE, this voids the use of any IP address set above', 'cmpbl-domain-mapping' ) . '</p>';
		_e( 'Server CNAME domain: ', 'cmpbl-domain-mapping' );
		echo "<input type='text' name='cname' value='" . get_site_option( 'dm_cname' ) . "' /> (" . self::dm_idn_warning() . ')<br>';
		echo '<p>' . __( 'The information you enter here will be shown to your users so they can configure their DNS correctly. It is for informational purposes only', 'cmpbl-domain-mapping' ) . '</p>';

		echo '<h3>' . __( 'Domain Options', 'cmpbl-domain-mapping' ) . '</h3>';
		echo "<ol><li><input type='checkbox' name='dm_remote_login' value='1' ";
		echo get_site_option( 'dm_remote_login' ) == 1 ? "checked='checked'" : '';
		echo ' /> ' . __( 'Remote Login', 'cmpbl-domain-mapping' ) . '</li>';
		echo "<li><input type='checkbox' name='permanent_redirect' value='1' ";
		echo get_site_option( 'dm_301_redirect' ) == 1 ? "checked='checked'" : '';
		echo ' /> ' . __( "Permanent redirect (better for your blogger's pagerank)", 'cmpbl-domain-mapping' ) . '</li>';
		echo "<li><input type='checkbox' name='dm_user_settings' value='1' ";
		echo get_site_option( 'dm_user_settings' ) == 1 ? "checked='checked'" : '';
		echo ' /> ' . __( 'User domain mapping page', 'cmpbl-domain-mapping' ) . '</li> ';
		echo "<li><input type='checkbox' name='always_redirect_admin' value='1' ";
		echo get_site_option( 'dm_redirect_admin' ) == 1 ? "checked='checked'" : '';
		echo ' /> ' . __( "Redirect administration pages to site's original domain (remote login disabled if this redirect is disabled)", 'cmpbl-domain-mapping' ) . '</li>';
		echo "<li><input type='checkbox' name='dm_no_primary_domain' value='1' ";
		echo get_site_option( 'dm_no_primary_domain' ) == 1 ? "checked='checked'" : '';
		echo ' /> ' . __( 'Disable primary domain check. Sites will not redirect to one domain name. May cause duplicate content issues.', 'cmpbl-domain-mapping' ) . '</li></ol>';
		wp_nonce_field( 'domain_mapping' );
		echo "<p><input class='button-primary' type='submit' value='" . __( 'Save', 'cmpbl-domain-mapping' ) . "' /></p>";
		echo '</form><br>';
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_handle_actions() {
		global $wpdb, $parent_file;
		$url = add_query_arg( array(
			'page' => 'domainmapping',
		), admin_url( $parent_file ) );
		if ( ! empty( $_POST['action'] ) ) {
			$domain = esc_sql( $_POST['domain'] );
			if ( $domain == '' ) {
				wp_die( 'You must enter a domain' );
			}
			check_admin_referer( 'domain_mapping' );
			do_action( 'dm_handle_actions_init', $domain );
			switch ( $_POST['action'] ) {
				case 'add':
					do_action( 'dm_handle_actions_add', $domain );
					if ( null == $wpdb->get_row( "SELECT blog_id FROM {$wpdb->blogs} WHERE domain = '$domain'" ) && null == $wpdb->get_row( "SELECT blog_id FROM {$wpdb->dmtable} WHERE domain = '$domain'" ) ) {
						if ( $_POST['primary'] ) {
							$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->dmtable} SET active = 0 WHERE blog_id = %d", $wpdb->blogid ) );
						}
						$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dmtable} ( `id` , `blog_id` , `domain` , `active` ) VALUES ( NULL, %d, %s, %d )", $wpdb->blogid, $domain, $_POST['primary'] ) );
						wp_redirect( add_query_arg( array(
							'updated' => 'add',
						), $url ) );
						exit;
					} else {
						wp_redirect( add_query_arg( array(
							'updated' => 'exists',
						), $url ) );
						exit;
					}
				break;
				case 'primary':
					do_action( 'dm_handle_actions_primary', $domain );
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->dmtable} SET active = 0 WHERE blog_id = %d", $wpdb->blogid ) );
					$orig_url = parse_url( self::get_original_url( 'siteurl' ) );
					// pbrocks hyp.
					if ( $domain != $orig_url['host'] ) {
						$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->dmtable} SET active = 1 WHERE domain = %s", $domain ) );
					}
					wp_redirect( add_query_arg( array(
						'updated' => 'primary',
					), $url ) );
					exit;
				break;
			}
		} elseif ( array_key_exists( 'action', $_GET ) && $_GET['action'] == 'delete' ) {
			$domain = esc_sql( $_GET['domain'] );
			if ( $domain == '' ) {
				wp_die( __( 'You must enter a domain', 'cmpbl-domain-mapping' ) );
			}
			check_admin_referer( 'delete' . $_GET['domain'] );
			do_action( 'dm_handle_actions_del', $domain );
			$wpdb->query( "DELETE FROM {$wpdb->dmtable} WHERE domain = '$domain'" );
			wp_redirect( add_query_arg( array(
				'updated' => 'del',
			), $url ) );
			exit;
		}// End if().

	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_sunrise_warning( $die = true ) {
		if ( ! file_exists( WP_CONTENT_DIR . '/sunrise.php' ) ) {
			if ( ! $die ) {
				return true;
			}

			if ( self::dm_site_admin() ) {
				wp_die( sprintf( __( 'Please copy sunrise.php to %1\$s/sunrise.php and ensure the SUNRISE definition is in %2\$swp-config.php', 'cmpbl-domain-mapping' ), WP_CONTENT_DIR, ABSPATH ) );
			} else {
				wp_die( __( 'This plugin has not been configured correctly yet.', 'cmpbl-domain-mapping' ) );
			}
		} elseif ( ! defined( 'SUNRISE' ) ) {
			if ( ! $die ) {
				return true;
			}

			if ( self::dm_site_admin() ) {
				wp_die( sprintf( __( "Please uncomment the line <em>define( 'SUNRISE', 'on' );</em> or add it to your %swp-config.php", 'cmpbl-domain-mapping' ), ABSPATH ) );
			} else {
				wp_die( __( 'This plugin has not been configured correctly yet.', 'cmpbl-domain-mapping' ) );
			}
		} elseif ( ! defined( 'SUNRISE_LOADED' ) ) {
			if ( ! $die ) {
				return true;
			}

			if ( self::dm_site_admin() ) {
				wp_die( sprintf( __( "Please edit your %swp-config.php and move the line <em>define( 'SUNRISE', 'on' );</em> above the last require_once() in that file or make sure you updated sunrise.php.", 'cmpbl-domain-mapping' ), ABSPATH ) );
			} else {
				wp_die( __( 'This plugin has not been configured correctly yet.', 'cmpbl-domain-mapping' ) );
			}
		}
		return false;
	}



	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_manage_page() {
		global $wpdb, $parent_file;

		if ( isset( $_GET['updated'] ) ) {
			do_action( 'dm_echo_updated_msg' );
		}

		self::dm_sunrise_warning();

		echo "<div class='wrap'><h2>" . __( 'Domain Mapping', 'cmpbl-domain-mapping' ) . '</h2>';

		if ( false == get_site_option( 'dm_ipaddress' ) && false == get_site_option( 'dm_cname' ) ) {
			if ( self::dm_site_admin() ) {
				_e( "Please set the IP address or CNAME of your server in the <a href='wpmu-admin.php?page=dm_admin_page'>site admin page</a>.", 'cmpbl-domain-mapping' );
			} else {
				_e( 'This plugin has not been configured correctly yet.', 'cmpbl-domain-mapping' );
			}
			echo '</div>';
			return false;
		}

		$protocol = is_ssl() ? 'https://' : 'http://';
		$domains = $wpdb->get_results( "SELECT * FROM {$wpdb->dmtable} WHERE blog_id = '{$wpdb->blogid}'", ARRAY_A );
		if ( is_array( $domains ) && ! empty( $domains ) ) {
			$orig_url = parse_url( self::get_original_url( 'siteurl' ) );
			$domains[] = array(
				// pbrocks hyp.
				'domain' => $orig_url['host'],
				'path' => $orig_url['path'],
				'active' => 0,
			);
			echo '<h3>' . __( 'Active domains on this blog', 'cmpbl-domain-mapping' ) . '</h3>';
			echo '<form method="POST">';
			echo '<table><tr><th>' . __( 'Primary', 'cmpbl-domain-mapping' ) . '</th><th>' . __( 'Domain', 'cmpbl-domain-mapping' ) . '</th><th>' . __( 'Delete', 'cmpbl-domain-mapping' ) . "</th></tr>\n";
			$primary_found = 0;
			$del_url = add_query_arg( array(
				'page' => 'domainmapping',
				'action' => 'delete',
			), admin_url( $parent_file ) );
			foreach ( $domains as $details ) {
				$details['path'] = array_key_exists( 'path', $details ) ? $details['path'] : '';
					// pbrocks hyp.
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
				echo "</td><td><a href='$url'>$url</a></td><td style='text-align: center'>";
					// pbrocks hyp.
				if ( $details['domain'] != $orig_url['host'] && $details['active'] != 1 ) {
					echo "<a href='" . wp_nonce_url( add_query_arg( array(
						'domain' => $details['domain'],
					), $del_url ), 'delete' . $details['domain'] ) . "'>Del</a>";
				}
				echo '</td></tr>';
				if ( 0 == $primary_found ) {
					$primary_found = $details['active'];
				}
			}
			?></table><?php
			echo '<input type="hidden" name="action" value="primary" />';
			echo "<p><input type='submit' class='button-primary' value='" . __( 'Set Primary Domain', 'cmpbl-domain-mapping' ) . "' /></p>";
			wp_nonce_field( 'domain_mapping' );
			echo '</form>';
			echo '<p>' . __( '* The primary domain cannot be deleted.', 'cmpbl-domain-mapping' ) . '</p>';
if ( get_site_option( 'dm_no_primary_domain' ) == 1 ) {
	echo __( '<strong>Warning!</strong> Primary domains are currently disabled.', 'cmpbl-domain-mapping' );
}
		}// End if().
		echo '<h3>' . __( 'Add new domain', 'cmpbl-domain-mapping' ) . '</h3>';
		echo '<form method="POST">';
		echo '<input type="hidden" name="action" value="add" />';
		echo "<p>http://<input type='text' name='domain' value='' />/<br>";
		wp_nonce_field( 'domain_mapping' );
		echo "<input type='checkbox' name='primary' value='1' /> " . __( 'Primary domain for this blog', 'cmpbl-domain-mapping' ) . '</p>';
		echo "<p><input type='submit' class='button-secondary' value='" . __( 'Add', 'cmpbl-domain-mapping' ) . "' /></p>";
		echo '</form><br>';

		if ( get_site_option( 'dm_cname' ) ) {
			$dm_cname = get_site_option( 'dm_cname' );
			echo '<p>' . sprintf( __( 'If you want to redirect a domain you will need to add a DNS "CNAME" record pointing to the following domain name for this server: <strong>%s</strong>', 'cmpbl-domain-mapping' ), $dm_cname ) . '</p>';
			echo '<p>' . __( 'Google have published <a href="http://www.google.com/support/blogger/bin/answer.py?hl=en&answer=58317" target="_blank">instructions</a> for creating CNAME records on various hosting platforms such as GoDaddy and others.', 'cmpbl-domain-mapping' ) . '</p>';
		} else {
			echo '<p>' . __( 'If your domain name includes a hostname like "www", "blog" or some other prefix before the actual domain name you will need to add a CNAME for that hostname in your DNS pointing at this blog URL.', 'cmpbl-domain-mapping' ) . '</p>';
			$dm_ipaddress = get_site_option( 'dm_ipaddress', 'IP not set by admin yet.' );
			if ( strpos( $dm_ipaddress, ',' ) ) {
				echo '<p>' . sprintf( __( 'If you want to redirect a domain you will need to add DNS "A" records pointing at the IP addresses of this server: <strong>%s</strong>', 'cmpbl-domain-mapping' ), $dm_ipaddress ) . '</p>';
			} else {
				echo '<p>' . sprintf( __( 'If you want to redirect a domain you will need to add a DNS "A" record pointing at the IP address of this server: <strong>%s</strong>', 'cmpbl-domain-mapping' ), $dm_ipaddress ) . '</p>';
			}
		}
		echo '<p>' . sprintf( __( '<strong>Note:</strong> %s', 'cmpbl-domain-mapping' ), self::dm_idn_warning() ) . '</p>';
		echo '</div>';

	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function domain_mapping_siteurl( $setting ) {
		global $wpdb, $current_blog;
		$setting = 'http://domain.maap/';
		return $setting;
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function domain_mapping_siteurl_old( $setting ) {
		global $wpdb, $current_blog;

		// To reduce the number of database queries, save the results the first time we encounter each blog ID.
		static $return_url = array();

		$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';

		if ( ! isset( $return_url[ $wpdb->blogid ] ) ) {
			$s = $wpdb->suppress_errors();

			if ( get_site_option( 'dm_no_primary_domain' ) == 1 ) {
				// RMURPHy Update to allow hosting both sub-domains and sub-directories.
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
				if ( null == $domain ) {
					$return_url[ $wpdb->blogid ] = untrailingslashit( self::get_original_url( 'siteurl' ) );
					return $return_url[ $wpdb->blogid ];
				}
			} else {
				// RMURPHY Update to allow hosting both sub-domains and sub-directories
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
				if ( null == $domain ) {
					$return_url[ $wpdb->blogid ] = untrailingslashit( self::get_original_url( 'siteurl' ) );
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
		} elseif ( $return_url[ $wpdb->blogid ] !== false ) {
			$setting = $return_url[ $wpdb->blogid ];
		}// End if().

		return $setting;
	}

	/**
	 * Do we need to set a text domain?
	 * url is siteurl or home
	 */
	public static function get_original_url( $url, $blog_id = 0 ) {
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
				// array( __CLASS__, .
				add_filter( 'pre_option_' . $url, 'domain_mapping_' . $url );
			}
		}
		return $orig_urls[ $id ];
	}

	/**
	 * Do we need to set a text domain?
	 */
	public static function domain_mapping_adminurl( $url, $path, $blog_id = 0 ) {
		$index = strpos( $url, '/wp-admin' );
		if ( $index !== false ) {
			$url = self::get_original_url( 'siteurl', $blog_id ) . substr( $url, $index );

			// make sure admin_url is ssl if current page is ssl, or admin ssl is forced
			if ( ( is_ssl() || force_ssl_admin() ) && 0 === strpos( $url, 'http://' ) ) {
				$url = 'https://' . substr( $url, 7 );
			}
		}
		return $url;
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function domain_mapping_post_content( $post_content ) {
		global $wpdb;

		$orig_url = self::get_original_url( 'siteurl' );

		$url = self::domain_mapping_siteurl( 'NA' );
		if ( $url == 'NA' ) {
			return $post_content;
		}
		return str_replace( $orig_url, $url, $post_content );
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_redirect_admin() {
		// don't redirect admin ajax calls
		if ( strpos( $_SERVER['REQUEST_URI'], 'wp-admin/admin-ajax.php' ) !== false ) {
			return;
		}

		if ( get_site_option( 'dm_redirect_admin' ) ) {
			// redirect mapped domain admin page to original url
			$url = self::get_original_url( 'siteurl' );
			if ( false === strpos( $url, $_SERVER['HTTP_HOST'] ) ) {
				wp_redirect( untrailingslashit( $url ) . $_SERVER['REQUEST_URI'] );
				exit;
			}
		} else {
			global $current_blog;
			// redirect original url to primary domain wp-admin/ - remote login is disabled!
			$url = self::domain_mapping_siteurl( false );
			$request_uri = str_replace( $current_blog->path, '/', $_SERVER['REQUEST_URI'] );
			if ( false === strpos( $url, $_SERVER['HTTP_HOST'] ) ) {
				wp_redirect( str_replace( '//wp-admin', '/wp-admin', trailingslashit( $url ) . $request_uri ) );
				exit;
			}
		}
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function redirect_login_to_orig() {
		if ( ! get_site_option( 'dm_remote_login' ) || $_GET['action'] == 'logout' || isset( $_GET['loggedout'] ) ) {
			return false;
		}
		$url = self::get_original_url( 'siteurl' );
		if ( $url != site_url() ) {
			$url .= '/wp-login.php';
			echo "<script type='text/javascript'>\nwindow.location = '$url'</script>";
		}
	}

	// fixes the plugins_url
	/**
	 * Do we need to set a text domain?
	 */
	public static function domain_mapping_plugins_uri( $full_url, $path = null, $plugin = null ) {
		return get_option( 'siteurl' ) . substr( $full_url, stripos( $full_url, PLUGINDIR ) - 1 );
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function domain_mapping_themes_uri( $full_url ) {
		return str_replace( self::get_original_url( 'siteurl' ), get_option( 'siteurl' ), $full_url );
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function remote_logout_loader() {
		global $current_site, $current_blog, $wpdb;
		$wpdb->dmtablelogins = $wpdb->base_prefix . 'domain_mapping_logins';
		$protocol = is_ssl() ? 'https://' : 'http://';
		$hash = self::get_dm_hash();
		$key = md5( time() );
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dmtablelogins} ( `id`, `user_id`, `blog_id`, `t` ) VALUES( %s, 0, %d, NOW() )", $key, $current_blog->blog_id ) );
		if ( get_site_option( 'dm_redirect_admin' ) ) {
			wp_redirect( $protocol . $current_site->domain . $current_site->path . "?dm={$hash}&action=logout&blogid={$current_blog->blog_id}&k={$key}&t=" . mt_rand() );
			exit;
		}
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function redirect_to_mapped_domain() {
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
		$url = self::domain_mapping_siteurl( false );
		if ( $url && $url != untrailingslashit( $protocol . $current_blog->domain . $current_blog->path ) ) {
			$redirect = get_site_option( 'dm_301_redirect' ) ? '301' : '302';
			if ( ( defined( 'VHOST' ) && constant( 'VHOST' ) != 'yes' ) || ( defined( 'SUBDOMAIN_INSTALL' ) && constant( 'SUBDOMAIN_INSTALL' ) == false ) ) {
				$_SERVER['REQUEST_URI'] = str_replace( $current_blog->path, '/', $_SERVER['REQUEST_URI'] );
			}
			header( "Location: {$url}{$_SERVER[ 'REQUEST_URI' ]}", true, $redirect );
			exit;
		}
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function get_dm_hash() {
		$remote_login_hash = get_site_option( 'dm_hash' );
		if ( null == $remote_login_hash ) {
			$remote_login_hash = md5( time() );
			update_site_option( 'dm_hash', $remote_login_hash );
		}
		return $remote_login_hash;
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function remote_login_js() {
		global $current_blog, $current_user, $wpdb;

		if ( 0 == get_site_option( 'dm_remote_login' ) ) {
			return false;
		}

		$wpdb->dmtablelogins = $wpdb->base_prefix . 'domain_mapping_logins';
		$hash = self::get_dm_hash();
		$protocol = is_ssl() ? 'https://' : 'http://';
		if ( $_GET['dm'] == $hash ) {
			if ( $_GET['action'] == 'load' ) {
				if ( ! is_user_logged_in() ) {
					exit;
				}
				$key = md5( time() . mt_rand() );
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dmtablelogins} ( `id`, `user_id`, `blog_id`, `t` ) VALUES( %s, %d, %d, NOW() )", $key, $current_user->ID, $_GET['blogid'] ) );
				$url = add_query_arg( array(
					'action' => 'login',
					'dm' => $hash,
					'k' => $key,
					't' => mt_rand(),
				), $_GET['back'] );
				echo "window.location = '$url'";
				exit;
			} elseif ( $_GET['action'] == 'login' ) {
				if ( $details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->dmtablelogins} WHERE id = %s AND blog_id = %d", $_GET['k'], $wpdb->blogid ) ) ) {
					if ( $details->blog_id == $wpdb->blogid ) {
						$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtablelogins} WHERE id = %s", $_GET['k'] ) );
						$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtablelogins} WHERE t < %d", ( time() - 120 ) ) ); // remote logins survive for only 2 minutes if not used.
						wp_set_auth_cookie( $details->user_id );
						wp_redirect( remove_query_arg( array( 'dm', 'action', 'k', 't', $protocol . $current_blog->domain . $_SERVER['REQUEST_URI'] ) ) );
						exit;
					} else {
						wp_die( __( 'Incorrect or out of date login key', 'cmpbl-domain-mapping' ) );
					}
				} else {
					wp_die( __( 'Unknown login key', 'cmpbl-domain-mapping' ) );
				}
			} elseif ( $_GET['action'] == 'logout' ) {
				if ( $details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->dmtablelogins} WHERE id = %d AND blog_id = %d", $_GET['k'], $_GET['blogid'] ) ) ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtablelogins} WHERE id = %s", $_GET['k'] ) );
					$blog = get_blog_details( $_GET['blogid'] );
					wp_clear_auth_cookie();
					wp_redirect( trailingslashit( $blog->siteurl ) . 'wp-login.php?loggedout=true' );
					exit;
				} else {
					wp_die( __( 'Unknown logout key', 'cmpbl-domain-mapping' ) );
				}
			}// End if().
		}// End if().
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function remote_login_js_loader() {
		global $current_site, $current_blog;

		if ( 0 == get_site_option( 'dm_remote_login' ) || is_user_logged_in() ) {
			return false;
		}

		$protocol = is_ssl() ? 'https://' : 'http://';
		$hash = self::get_dm_hash();
		echo "<script src='{$protocol}{$current_site->domain}{$current_site->path}?dm={$hash}&amp;action=load&amp;blogid={$current_blog->blog_id}&amp;siteid={$current_blog->site_id}&amp;t=" . mt_rand() . '&amp;back=' . urlencode( $protocol . $current_blog->domain . $_SERVER['REQUEST_URI'] ) . "' type='text/javascript'></script>";
	}

	// delete mapping if blog is deleted
	/**
	 * Do we need to set a text domain?
	 */
	public static function delete_blog_domain_mapping( $blog_id, $drop ) {
		global $wpdb;
		$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
		if ( $blog_id && $drop ) {
			// Get an array of domain names to pass onto any delete_blog_domain_mapping actions
			$domains = $wpdb->get_col( $wpdb->prepare( "SELECT domain FROM {$wpdb->dmtable} WHERE blog_id  = %d", $blog_id ) );
			do_action( 'dm_delete_blog_domain_mappings', $domains );

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dmtable} WHERE blog_id  = %d", $blog_id ) );
		}
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_site_admin() {
		if ( function_exists( 'is_super_admin' ) ) {
			return is_super_admin();
		} elseif ( function_exists( 'is_site_admin' ) ) {
			return is_site_admin();
		} else {
			return true;
		}
	}


	/**
	 * Adding menus
	 * csc.
	 */
	public static function get_mapped_domains_array() {
		global $wpdb;
		$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
		$mapped_alias = $wpdb->get_results( "SELECT blog_id, domain FROM {$wpdb->dmtable} ORDER BY blog_id" );

		return $mapped_alias;
	}


	/**
	 * Adding menus
	 * csc.
	 */
	public static function add_column_for_aliases( $column_name, $blog_id ) {

		$mapped_alias = self::get_mapped_domains_array();

		if ( 'blog_id' === $column_name ) {
			echo $blog_id;
		} elseif ( 'alias' === $column_name ) {
			foreach ( $mapped_alias as $value ) {
				if ( $blog_id === $value->blog_id ) {
					echo '<h4><a href="' . esc_url( $value->domain ) . '" target="_blank">' . $value->domain . '</h4>';
				}
			}
		}
		return $column_name;
	}

	/**
	 * Adding mapping on site admin blogs screen.
	 *  menus
	 * csc.
	 */
	public static function add_domain_mapping_column_label( $columns ) {
		$columns['alias'] = __( 'Alias(es)' );
		$columns['blog_id'] = __( 'SubSite' );
		return $columns;
	}

	public static function set_column_width() {
		echo '<style>#id { width: 5%; }</style>';
	}

	/**
	 *	function dm_network_pages() {
	 * Adding menus
	 * dashicons-admin-multisite
	 */
	public static function domain_mapping_menu() {
		add_menu_page( 'Mapping Aliases', 'Mapping Aliases', 'manage_options', 'map-your-domain.php',  array( __CLASS__, 'main_domain_mapping_page' ), 'dashicons-networking', 9 );
	}

	/**
	 *	function dm_network_pages() {
	 * Adding menus
	 * csc.
	 */
	public static function domain_mapping_subsite_menus() {
		add_submenu_page( 'map-your-domain.php', 'Domain Mapping', 'Domain Mapping', 'manage_options', 'dm_admin_page', array( __CLASS__, 'dm_admin_page' ) );
		add_submenu_page( 'map-your-domain.php', 'Mapt Domains', 'Mapt Domains', 'manage_options', 'dm_domains_admin', array( __CLASS__, 'dm_domains_admin' ) );
	}

	public static function network_mapping_configuration() {
		echo '<div class="wrap">';
		echo '<h2>' . __FUNCTION__ . '<h2>';
		dm_admin_page();
		echo '</div>';
	}

	public static function network_assigning_mapping() {
		echo '<div class="wrap">';
		echo '<h2>' . __FUNCTION__ . '<h2>';
		dm_domains_admin();
		echo '</div>';
	}
	/**
	 *	function dm_network_pages() {
	 * Adding menus
	 * csc.
	 */
	// public static function domain_mapping_subsite_menus() {
	public static function domain_mapping_network_menus() {
		// add_submenu_page( 'map-your-domain.php', 'Mapping your Domain', 'Mapping your Domain', 'manage_options', 'dm_admin_page', array( __CLASS__, 'mapped_subsite_page' ) );
		// add_submenu_page( 'map-your-domain.php', 'Mapt Domains', 'Mapt Domains', 'manage_options', 'dm_domains_admin', array( __CLASS__, 'dm_domains_admin' ) );
		add_submenu_page( 'map-your-domain.php', 'Mapt Domains', 'Mapt Domains', 'manage_options', 'dm_domains_admin', array( __CLASS__, 'network_assigning_mapping' ) );
		add_submenu_page( 'map-your-domain.php', 'Domain Mapping', 'Domain Mapping', 'manage_options', 'dm_admin_page', array( __CLASS__, 'network_mapping_configuration' ) );

	}

	/**
	 * Add Submenu page
	 **/
	public static function main_domain_mapping_page() {
		echo '<div class="wrap">';
		echo '<h2>' . __FUNCTION__ . '<h2>';
			global $current_site, $wpdb;

		if ( is_network_admin() ) {
			// echo '<h3 style="color:#700;">You are viewing the WordPress MultiSite network administration page</h3>';
		} else {
			$blog_id = get_current_blog_id();
			// echo '<h3 style="color:#700;">You are viewing a WordPress MultiSite subsite #' . $blog_id . ' admin page</h3>';
			if ( $blog_id ) {
				echo '<pre>';
				// var_dump( $var );
				print_r( get_blog_details( $blog_id ) );
				echo '</pre>';
			}
		}

		if ( get_site_option( 'dm_user_settings' ) && $current_site->blog_id != $wpdb->blogid && ! self::dm_sunrise_warning( false ) ) {
			add_management_page( __( 'Domain Mapping', 'cmpbl-domain-mapping' ), __( 'Domain Mapping', 'cmpbl-domain-mapping' ), 'manage_options', 'domainmapping', array( __CLASS__, 'dm_manage_page' ) );
		}
		echo '</div>';
	}

	public static function get_domain_mapping_settings() {
		$settings = array();
		$settings['dm_user_settings'] = get_site_option( 'dm_user_settings' );
		$settings['dm_no_primary_domain'] = get_site_option( 'dm_no_primary_domain' );
		$settings['dm_remote_login'] = get_site_option( 'dm_remote_login' );
		$settings['dm_redirect_admin'] = get_site_option( 'dm_redirect_admin' );
		$settings['dm_user_settings'] = get_site_option( 'dm_user_settings' );
		$settings['dm_ipaddress'] = get_site_option( 'dm_ipaddress' );
		$settings['dm_cname'] = get_site_option( 'dm_cname' );
		$settings['dm_301_redirect'] = get_site_option( 'dm_301_redirect' );
		$settings['dm_user_settings'] = get_site_option( 'dm_user_settings' );

		return $settings;
	}

	public static function diagnositc_submenu_page() {
		add_submenu_page( 'map-your-domain.php', 'Domain Mapping Settings', 'Mapping Settings', 'manage_options', 'domain-mapping-diagnostics.php',  array( __CLASS__, 'mapped_subsite_diag_page' ) );
	}

	/**
	 * Do we need to set a text domain?
	 */
	public static function mapped_subsite_diag_page() {
		echo '<h2>' . __FUNCTION__ . '</h2>';
		self::dm_manage_page();
		echo '<div class="container" style="width:100%;"><div class="left" style="width:50%;float:left;">';
		$settings = self::get_domain_mapping_settings();
		echo '<pre>';
		print_r( $settings );
		echo '</pre>';
		echo '</div><div class="right" style="width:50%; float:left;">';
		$mapped_alias = self::get_mapped_domains_array();
		echo '<pre>';
		print_r( $mapped_alias );
		echo '</pre>';
		echo '</div></div>';
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function mapped_subsite_page() {
		echo '<h2>' . __FUNCTION__ . '</h2>';
		// self::dm_manage_page();
		dm_manage_page();

		// $blog_id = get_current_blog_id();
		// echo 'Blog_id ' . $blog_id . ' is type ' . gettype( $blog_id ) . '<br>';
		// $mapped_alias = self::get_mapped_domains_array();
		// echo '<h3>Mapped Aliases</h3>';
		// foreach ( $mapped_alias as $key => $value ) {
		// echo '$mapped_alias->blog_id ' . $value->blog_id . ' is type ' . gettype( $value->blog_id ) . '<br>';
		// echo '$mapped_alias->blog_id ' . $value->blog_id . ' is type ' . gettype( intval( $value->blog_id ) ) . '<br>';
		// $alias_id = intval( $value->blog_id );
		// if ( $blog_id == $alias_id ) {
		// echo '<h4><a href="' . esc_url( $value->domain ) . '" target="_blank">' . esc_url( $value->domain ) . '</a></h4>';
		// }
		// }
	}


	/**
	 * Do we need to set a text domain?
	 */
	public static function dm_idn_warning() {
		return sprintf( __( 'International Domain Names should be in <a href="%s">punycode</a> format.', 'cmpbl-domain-mapping' ), 'http://api.webnic.cc/idnconversion.html' );
	}
}
