<?php

namespace Domain_Mapping_Updated\inc\classes;

// use \Connect_Core_WP\inc\classes\Central_Toggle_Control as Toggle;
use Domain_Mapping_Updated\inc\classes\Customizer_Toggle_Control as Toggle;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class CSC_Theme_Customizer {

	public static function init() {
		add_action( 'customize_register', array( __CLASS__, 'domain_mapping_customizer' ) );
	}

	/**
	 * Customizer manager
	 *
	 * @param WP_Customizer_Manager $dm_customizer
	 * @return void
	 */
	public static function domain_mapping_customizer( $dm_customizer ) {
		// $plugin = 'connect-core-wp';
		// if ( ! is_plugin_active( $plugin . '/' . $plugin . '.php' ) ) {
		self::domain_mapping_solo( $dm_customizer );
			// self::domain_mapping_connect( $dm_customizer );
		// } else {
		// self::domain_mapping_connect( $dm_customizer );
		// self::domain_mapping_solo( $dm_customizer );
		// }
	}

	/**
	 * Customizer manager
	 *
	 * @param WP_Customizer_Manager $dm_customizer
	 * @return void
	 */
	public static function check_for_panels() {
		$toggle = array();
		if ( false === CONNECT_CORE ) {
		// if ( ! class_exists( 'Toggle' ) ) {
			$toggle['panel'] = 'domain_mapping_panel';
			$toggle['label'] = 'Domain Mapping';
			$toggle['control'] = 'Domain Mapping';
		} else {
			$toggle['panel'] = 'core_connect_panel';
			$toggle['label'] = 'Core Connect';
			$toggle['control'] = 'Domain Mapping';
		}
		return $toggle;
	}

	public static function define_toggle() {
		// use Domain_Mapping_Updated\inc\classes\Customizer_Toggle_Control as Toggle;	
	}
	/**
	 * Customizer manager
	 *
	 * @param  WP_Customizer_Manager $dm_customizer
	 * @return void
	 */
	private static function domain_mapping_connect( $dm_customizer ) {

		$toggle = self::check_for_panels();
		$panel = $toggle['panel'];
		$label = $toggle['label'];
		// $create = $toggle['toggle'];
		$dm_customizer->add_section( 'domain_mapping_section', array(
			'title'          => 'Domain Mapping',
			'priority'       => 13,
			'panel'          => $toggle['panel'],

			) );

		$dm_customizer->add_setting( 'wds_force_image_https',
			array(
				'default'        => false,
				)
			);
		$dm_customizer->add_control( new Toggle( $dm_customizer,
			'wds_force_image_https',
			array(
				'settings'   => 'wds_force_image_https',
				'label'      => __( 'WDS Force Image https URL', 'csc-domain-mapping' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
				)
			) );
		$dm_customizer->add_setting( 'fix_header_image',
			array(
				'default'        => false,
				)
			);
		$dm_customizer->add_control( new Toggle( $dm_customizer, 'fix_header_image',
			array(
				'settings'   => 'fix_header_image',
				'label'      => __( 'Fix Header Image URL', 'csc-domain-mapping' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
				)
			) );
		$dm_customizer->add_setting( 'fix_background_image',
			array(
				'default'        => false,
				)
			);
		$dm_customizer->add_control( new Toggle( $dm_customizer, 'fix_background_image',
			array(
				'settings'   => 'fix_background_image',
				'label'      => __( 'Fix Background Image URL', 'csc-domain-mapping' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
				)
			) );
		$dm_customizer->add_setting( 'fix_meta_image',
			array(
				'default'        => false,
				)
			);
		$dm_customizer->add_control( new Toggle( $dm_customizer,
			'fix_meta_image_url',
			array(
				'settings'   => 'fix_meta_image',
				'label'      => __( 'Fix Meta Data Image URL', 'csc-domain-mapping' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
				)
			) );
		$dm_customizer->add_setting( 'fix_upload_url',
			array(
				'default'        => false,
				)
			);

		$dm_customizer->add_control( new Toggle( $dm_customizer, 'fix_upload_url',
			array(
				'settings'   => 'fix_upload_url',
				'label'      => __( 'Fix Media Upload URL', 'csc-domain-mapping' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
				)
			) );

		$dm_customizer->add_setting( 'fix_nav_item_url',
			array(
				'default'        => false,
				)
			);

		$dm_customizer->add_control( new Toggle( $dm_customizer, 'fix_nav_item_url',
			array(
				'settings'   => 'fix_nav_item_url',
				'label'      => __( 'Fix Nav Item URL', 'csc-domain-mapping' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
				)
			) );

		$dm_customizer->add_section( 'section_id', array(
			'priority' => 20,
			'capability' => 'edit_theme_options',
			'theme_supports' => '',
			'title' => __( 'Diagnostics Section', 'csc-domain-mapping' ),
			'description' => '',
			'panel' => $toggle['panel'],
			) );

		$dm_customizer->add_setting( 'show_diagnostics',
			array(
				'default'    => false,
				)
			);

		$dm_customizer->add_control( new Toggle( $dm_customizer, 'show_diagnostics',
			array(
				'settings'    => 'show_diagnostics',
				'label'       => __( 'Domain Mapping Diagnostics' ),
				'description' => 'Adds a button in upper right corner of front end pages to toggle diagnostic infomation.',
				'section'     => 'section_id',
				'type'        => 'ios',
				)
			) );
		if ( true === get_theme_mod( 'show_diagnostics' ) ) {
			$dm_customizer->add_setting( 'diagnostic_type',
				array(
					'capability' => 'edit_theme_options',
					'default'    => 'mapping',
				// // 'sanitize_callback' => array(
				// // __CLASS__,
				// // 'customizer_sanitize_radio',
				// ),
					) );
			$dm_customizer->add_control( 'diagnostic_type',
				array(
					'type'        => 'radio',
					'section'     => 'section_id',
					'label'       => __( 'Diagnostic Selection' ),
					'description' => __( 'This is a custom radio input.' ),
					'choices'     => array(
						'mapping'    => __( 'Domain Mapping' ),
						'mods'       => __( 'Theme Mods' ),
						),
					)
				);
				// }
		}// End if().
	}

	/**
	 * Customizer manager
	 *
	 * @param  WP_Customizer_Manager $dm_customizer
	 * @return void
	 */
	private static function domain_mapping_solo( $dm_customizer ) {

		$toggle = self::check_for_panels();
		$panel = $toggle['panel'];
		$label = $toggle['label'];
		// $create = $toggle['toggle'];
		// $dm_customizer->add_panel( $toggle['panel'], array(
		$dm_customizer->add_panel( 'domain_mapping_panel', array(
			'title'          => __( $toggle['label'], 'csc-domain-mapping' ),
			'priority' => 10,
			'capability' => 'edit_theme_options',
			'theme_supports' => '',
			'description'          => $toggle['label'] . ' description',

			) );

		$dm_customizer->add_section( 'domain_mapping_section', array(
			'title'          => $toggle['label'],
			'priority'       => 13,
			'panel'          => 'domain_mapping_panel',
			'description'          => $toggle['label'] . ' description',
			) );

		$dm_customizer->add_setting( 'fix_nav_item_url',
			array(
				'default'        => false,
				)
			);

		$dm_customizer->add_control( new Toggle( $dm_customizer, 'fix_nav_item_url',
			array(
				'settings'   => 'fix_nav_item_url',
				'label'      => __( 'Fix Nav Item URL', 'csc-domain-mapping' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
				)
			) );

		$dm_customizer->add_setting( 'wds_force_image_https',
			array(
				'default'        => false,
				)
			);
		$dm_customizer->add_control( new Toggle( $dm_customizer, 'wds_force_image_https',
			array(
				'settings'   => 'wds_force_image_https',
				'label'      => __( 'WDS Force Image https URL', 'csc-domain-mapping' ),
				'section'    => 'domain_mapping_section',
				'type'      => 'ios',
				)
			) );
		$dm_customizer->add_setting( 'fix_header_image',
			array(
				'default'        => false,
				)
			);
		$dm_customizer->add_control(  new Toggle( $dm_customizer,
			'fix_header_image',
			array(
				'settings'   => 'fix_header_image',
				'label'      => __( 'Fix Header Image URL', 'csc-domain-mapping' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
				)
			) );
		$dm_customizer->add_setting( 'fix_background_image',
			array(
				'default'        => false,
				)
			);
		$dm_customizer->add_control(
			new Toggle( $dm_customizer,
				'fix_background_image',
				array(
					'settings'   => 'fix_background_image',
					'label'      => __( 'Fix Background Image URL', 'csc-domain-mapping' ),
					'section'    => 'domain_mapping_section',
					'type'       => 'ios',
					)
				) );
		$dm_customizer->add_setting( 'fix_meta_image',
			array(
				'default'        => false,
				)
			);
		$dm_customizer->add_control(
			new Toggle( $dm_customizer,
				'fix_meta_image_url',
				array(
					'settings'   => 'fix_meta_image',
					'label'      => __( 'Fix Meta Data Image URL', 'csc-domain-mapping' ),
					'section'    => 'domain_mapping_section',
					'type'       => 'ios',
					)
				) );
		$dm_customizer->add_setting( 'fix_upload_url',
			array(
				'default'        => false,
				)
			);

		$dm_customizer->add_control(
			new Toggle( $dm_customizer,
				'fix_upload_url',
				array(
					'settings'   => 'fix_upload_url',
					'label'      => __( 'Fix Media Upload URL', 'csc-domain-mapping' ),
					'section'    => 'domain_mapping_section',
					'type'       => 'ios',
					)
				) );

		$dm_customizer->add_setting( 'fix_nav_item_url',
			array(
				'default'        => false,
				)
			);

		$dm_customizer->add_control(
			new Toggle( $dm_customizer,
				'fix_nav_item_url',
				array(
					'settings'   => 'fix_nav_item_url',
					'label'      => __( 'Fix Nav Item URL', 'csc-domain-mapping' ),
					'section'    => 'domain_mapping_section',
					'type'       => 'ios',
					)
				) );

		$dm_customizer->add_section( 'section_id', array(
			'priority' => 20,
			'capability' => 'edit_theme_options',
			'theme_supports' => '',
			'title' => __( 'Diagnostics Section', 'csc-domain-mapping' ),
			'description' => '',
			'panel' => $toggle['panel'],
			) );

		$dm_customizer->add_setting( 'show_diagnostics',
			array(
				'default'    => false,
				)
			);

		$dm_customizer->add_control( new Toggle( $dm_customizer,
			'show_diagnostics',
			array(
				'settings'    => 'show_diagnostics',
				'label'       => __( 'Domain Mapping Diagnostics' ),
				'description' => 'Adds a button in upper right corner of front end pages to toggle diagnostic infomation.',
				'section'     => 'section_id',
				'type'       => 'ios',
				)
			)
		);

		if ( true === get_theme_mod( 'show_diagnostics' ) ) {
			$dm_customizer->add_setting( 'diagnostic_type',
				array(
					'capability' => 'edit_theme_options',
					'default'    => 'mapping',
				// // 'sanitize_callback' => array(
				// // __CLASS__,
				// // 'customizer_sanitize_radio',
				// ),
					) );
			$dm_customizer->add_control( 'diagnostic_type',
				array(
					'type'        => 'radio',
					'section'     => 'section_id',
					'label'       => __( 'Diagnostic Selection' ),
					'description' => __( 'This is a custom radio input.' ),
					'choices'     => array(
						'mapping'    => __( 'Domain Mapping' ),
						'mods'       => __( 'Theme Mods' ),
						),
					)
				);
			// }
		}// End if().
	}

	/**
	 * A section to show how you use the default customizer controls in WordPress
	 *
	 * @param  Obj $dm_customizer - WP Manager
	 *
	 * @return Void
	 */
	private static function themeslug_sanitize_select( $input, $setting ) {

		// Ensure input is a slug.
		$input = sanitize_key( $input );

		// Get list of choices from the control associated with the setting.
		$choices = $setting->manager->get_control( $setting->id )->choices;

		// If the input is a valid key, return it; otherwise, return the default.
		return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
	}
}
