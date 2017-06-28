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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'domain_mapping_css' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'domain_mapping_css1' ) );
		// add_action( 'admin_menu', array( __CLASS__, 'domain_mapping_menu' ) );
		// add_action( 'network_admin_menu', array( __CLASS__, 'domain_mapping_network_menu' ) );
		add_action( 'network_admin_menu', array( __CLASS__, 'domain_mapping_network_menus' ) );
		add_action( 'admin_menu', array( __CLASS__, 'domain_mapping_subsite_menus' ) );

		add_action( 'manage_sites_custom_column', array( __CLASS__, 'add_column_for_aliases' ), 10, 2 );
		add_action( 'manage_blogs_custom_column', array( __CLASS__, 'add_column_for_aliases' ), 10, 2 );
		add_action( 'admin_footer', array( __CLASS__, 'set_column_width' ) );
		add_filter( 'wpmu_blogs_columns', array( __CLASS__, 'add_domain_mapping_column_label' ) );

	}
	/**
	 * [domain_mapping_filters description]
	 *
	 * @return [type] [description]
	 */
	public static function domain_mapping_css( $hook ) {
		if ( 'settings_page_dm_domains_admin' === $hook || 'settings_page_dm_admin_page' === $hook || 'tools_page_domainmapping' === $hook ) {
			wp_enqueue_style( 'domain-mapping', plugins_url( '../css/domain-mapping.css', __FILE__ ) );
		}
	}

	/**
	 * [domain_mapping_filters description]
	 *
	 * @return [type] [description]
	 */
	public static function domain_mapping_css1() {
		$screen = get_current_screen();
		if ( 'mapping-aliases_page_domain-mapping-admin-network' === $screen->id ) {
			wp_enqueue_style( 'domain-mapping', plugins_url( '../css/domain-mapping.css', __FILE__ ) );
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
				} else {
					update_site_option( 'dm_cname', '' );
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
	public static function dm_handle_actions() {
		global $wpdb, $parent_file;
		$url = add_query_arg(array(
			'page' => 'domainmapping',
		), admin_url( $parent_file ));
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
					$orig_url = parse_url( self::get_original_url( 'siteurl' ) );
					// pbrocks hyp.
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
			$domain = esc_sql( $_GET['domain'] );
			if ( $domain == '' ) {
				wp_die( __( 'You must enter a domain', 'cmpbl-domain-mapping' ) );
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
			$del_url = add_query_arg(array(
				'page' => 'domainmapping',
				'action' => 'delete',
			), admin_url( $parent_file ));
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
					echo "<a href='" . wp_nonce_url(add_query_arg(array(
						'domain' => $details['domain'],
					), $del_url), 'delete' . $details['domain']) . "'>Del</a>";
				}
				echo '</td></tr>';
				if ( 0 == $primary_found ) {
					$primary_found = $details['active'];
				}
			} ?></table><?php
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
			if ( (defined( 'VHOST' ) && constant( 'VHOST' ) != 'yes') || (defined( 'SUBDOMAIN_INSTALL' ) && constant( 'SUBDOMAIN_INSTALL' ) == false) ) {
				$_SERVER['REQUEST_URI'] = str_replace( $current_blog->path, '/', $_SERVER['REQUEST_URI'] );
			}
			header( "Location: {$url}{$_SERVER[ 'REQUEST_URI' ]}", true, $redirect );
			exit;
		}
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
		echo '<style>#blogname, #alias { width: 30%; } .alias > h4 { margin: 0 0 .2rem; } #users, #blog_id { width: 10%; }</style>';
	}

	/**
	 *	function dm_network_pages() {
	 * Adding menus
	 * dashicons-admin-multisite
	 */
	public static function domain_mapping_network_menu() {
		add_menu_page( 'Mapping Aliases', 'Mapping Aliases', 'manage_options', 'map-your-domain.php', array( __CLASS__, 'main_domain_mapping_page' ), 'dashicons-networking', 9 );
	}

	public static function domain_mapping_menu() {
		add_menu_page( 'Mapping Aliases', 'Mapping Aliases', 'manage_options', 'map-your-domain.php', array( __CLASS__, 'main_dubdomain_mapping_page' ), 'dashicons-networking', 9 );
	}

	/**
	 *	function dm_network_pages() {
	 * Adding menus
	 * csc.
	 */
	public static function domain_mapping_subsite_menus() {
		add_menu_page( 'Mapping Aliases', 'Mapping Aliases', 'manage_options', 'map-your-domain1.php', array( __CLASS__, 'main_subdomain_mapping_page' ), 'dashicons-networking', 9 );
		// add_submenu_page( 'map-your-domain.php', 'Sub Domain Mapping', 'Sub Domain Mapping', 'manage_options', 'dm-sub-admin-page', array( __CLASS__, 'network_mapping_configuration' ) );
		// add_submenu_page( 'map-your-domain.php', 'Sub Domains', 'Sub Domains', 'manage_options', 'dm_domains_admin', array( __CLASS__, 'dm_domains_admin' ) );
	}

	public static function network_mapping_configuration( $hook ) {
		global $pagenow;
		echo '<div class="wrap">';
		$screen = get_current_screen();
		echo '<h2>' . $screen->id . '<h2>';
		echo '<h2>' . $pagenow . '<h2>';
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
		add_menu_page( 'Mapping Aliases', 'Mapping Aliases', 'manage_options', 'map-your-domain.php', array( __CLASS__, 'main_domain_mapping_page' ), 'dashicons-networking', 9 );

		// add_submenu_page( 'map-your-domain.php', 'Mapping your Domain', 'Mapping your Domain', 'manage_options', 'dm_admin_page', array( __CLASS__, 'mapped_subsite_page' ) );
		// add_submenu_page( 'map-your-domain.php', 'Mapt Domains', 'Mapt Domains', 'manage_options', 'dm_domains_admin', array( __CLASS__, 'dm_domains_admin' ) );
		add_submenu_page( 'map-your-domain.php', 'Network Domains', 'Network Domains', 'manage_options', 'dm_domains_admin', array( __CLASS__, 'network_assigning_mapping' ) );
		add_submenu_page( 'map-your-domain.php', 'Network Domain Mapping', 'Network Domain Mapping', 'manage_options', 'domain-mapping-admin.php', array( __CLASS__, 'network_mapping_configuration' ) );
	}
	/**
	 * Add Submenu page
	 **/
	public static function main_domain_mapping_page() {

		echo '<div class="wrap">';
		echo '<h2>' . __FUNCTION__ . '</h2>';
		echo '<h2>something' . $hook . '</h2>';
		echo '</div>';
		if ( is_network_admin() ) {
			echo '<h3 style="color:#700;">You are viewing the WordPress MultiSite network administration paffffge ' . $hook . '</h3>';
		} else {
			$blog_id = get_current_blog_id();
			echo '<h3 style="color:#700;">You are viewing a WordPress MultiSite subsite #' . $blog_id . ' admin page' . $hook . '</h3>';
			if ( $blog_id ) {
				echo $hook;
				dm_manage_page();
				echo '<pre>';
				// var_dump( $var );
				// print_r( get_blog_details( $blog_id ) );
				echo '</pre>';
			}
		}

	}

	/**
	 * Add Submenu page
	 **/
	public static function main_subdomain_mapping_page() {
		global $current_site, $wpdb;
		$screen = get_current_screen();
		echo '<div class="wrap">';
		echo '<h2>' . __FUNCTION__ . '</h2>';
		if ( 'edit.php' !== $screen->id ) {
			echo '<h2>' . $screen->id . '</h2>';
		}

		if ( is_network_admin() ) {
			echo '<h3 style="color:#700;">You are viewing the WordPress MultiSite network administration paffffge ' . $screen->id . '</h3>';
		} else {
			$blog_id = get_current_blog_id();
			echo '<h3 style="color:#700;">You are viewing a WordPress MultiSite subsite #' . $blog_id . ' admin page' . $hook . '</h3>';
			if ( $blog_id ) {
				echo $hook;
				dm_manage_page();
				echo '<pre>';
				// var_dump( $var );
				// print_r( get_blog_details( $blog_id ) );
				echo '</pre>';
			}
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
		add_submenu_page( 'map-your-domain.php', 'Domain Mapping Settings', 'Mapping Settings', 'manage_options', 'domain-mapping-diagnostics.php', array( __CLASS__, 'mapped_subsite_diag_page' ) );
	}

	/**
	 * Do we need to set a text domain?
	 */
	public static function mapped_subsite_diag_page() {
		echo '<h2>' . __FUNCTION__ . '</h2>';
		// self::dm_manage_page();
		dm_manage_page();
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
	}

}
