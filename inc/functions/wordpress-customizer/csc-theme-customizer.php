<?php
new CSC_Theme_Customizer1();
/**
 *
 */
class CSC_Theme_Customizer1 {
	public function __construct() {
		add_action( 'customize_register', array( $this, 'customizer_manager' ) );
		// add_action( 'customize_register', array( $this, 'prefix_customizer_register' ) );
	}

	/**
	 * Customizer manager demo
	 *
	 * @param  WP_Customizer_Manager $wp_manager
	 * @return void
	 */
	public function customizer_manager( $wp_manager ) {
		$this->domain_mapping( $wp_manager );
	}

	public function include_toggle() {
		include( 'customizer-toggle-customizer.php' );
	}

	private function domain_mapping( $wp_manager ) {
		// if ( ! is_main_site() ) {
		$this->include_toggle();

		$wp_manager->add_panel( 'panel_id', array(
		    'priority' => 10,
		    'capability' => 'edit_theme_options',
		    'theme_supports' => '',
		    'title' => __( 'Admin Panel', 'textdomain' ),
		    'description' => __( 'Description of what this panel does.', 'textdomain' ),
		) );

		$wp_manager->add_section( 'domain_mapping_section', array(
			'title'          => 'Domain Mapping',
			'priority'       => 13,
		    'panel'          => 'panel_id',

		) );

		$wp_manager->add_setting( 'wds_force_image_https',
			array(
				'default'        => false,
			)
		);

		$wp_manager->add_control( new Customizer_Toggle_Control(
			$wp_manager, 'wds_force_image_https',
			array(
				'settings'   => 'wds_force_image_https',
				'label'      => __( 'WDS Force Image https URL' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
			)
		) );

		$wp_manager->add_setting( 'fix_header_image',
			array(
				'default'        => false,
			)
		);

		$wp_manager->add_control( new Customizer_Toggle_Control(
			$wp_manager, 'fix_header_image',
			array(
				'settings'   => 'fix_header_image',
				'label'      => __( 'Fix Header Image URL' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
			)
		) );

		$wp_manager->add_setting( 'fix_background_image',
			array(
				'default'        => false,
			)
		);

		$wp_manager->add_control( new Customizer_Toggle_Control(
			$wp_manager, 'fix_background_image',
			array(
				'settings'   => 'fix_background_image',
				'label'      => __( 'Fix Background Image URL' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
			)
		) );

		$wp_manager->add_setting( 'fix_meta_image',
			array(
				'default'        => false,
			)
		);

		$wp_manager->add_control( new Customizer_Toggle_Control(
			$wp_manager, 'fix_meta_image_url',
			array(
				'settings'   => 'fix_meta_image',
				'label'      => __( 'Fix Meta Data Image URL' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
			)
		) );

		$wp_manager->add_setting( 'fix_upload_url',
			array(
				'default'        => false,
			)
		);

		$wp_manager->add_control( new Customizer_Toggle_Control(
			$wp_manager, 'fix_upload_url',
			array(
				'settings'   => 'fix_upload_url',
				'label'      => __( 'Fix Media Upload URL' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
			)
		) );

		$wp_manager->add_setting( 'fix_nav_item_url',
			array(
				'default'        => false,
			)
		);

		$wp_manager->add_control( new Customizer_Toggle_Control(
			$wp_manager, 'fix_nav_item_url',
			array(
				'settings'   => 'fix_nav_item_url',
				'label'      => __( 'Fix Nav Item URL' ),
				'section'    => 'domain_mapping_section',
				'type'       => 'ios',
			)
		) );

		// $wp_manager->add_setting( 'url_field_id', array(
		// 'default' => '',
		// 'type' => 'theme_mod',
		// 'capability' => 'edit_theme_options',
		// 'transport' => '',
		// 'sanitize_callback' => 'esc_url',
		// ) );
		// $wp_manager->add_control( 'url_field_id', array(
		// 'type' => 'url',
		// 'priority' => 10,
		// 'section' => 'domain_mapping_section',
		// 'label' => __( 'URL Field', 'textdomain' ),
		// 'description' => '',
		// ) );
		$wp_manager->add_section( 'section_id', array(
		    'priority' => 20,
		    'capability' => 'edit_theme_options',
		    'theme_supports' => '',
		    'title' => __( 'Diagnostics Section', 'textdomain' ),
		    'description' => '',
		    'panel' => 'panel_id',
		) );

		$wp_manager->add_setting( 'show_diagnostics',
			array(
				'default'    => false,
			)
		);

		$wp_manager->add_control( new Customizer_Toggle_Control(
			$wp_manager, 'show_diagnostics',
			array(
				'settings'    => 'show_diagnostics',
				'label'       => __( 'Domain Mapping Diagnostics' ),
				'description' => 'Adds a button in upper right corner of front end pages to toggle diagnostic infomation.',
				'section'     => 'section_id',
				'type'        => 'ios',
			)
		) );

		if ( true === get_theme_mod( 'show_diagnostics' ) ) {

			$wp_manager->add_setting( 'diagnostic_type',
				array(
					'capability' => 'edit_theme_options',
					'default'    => 'mapping',
					// 'sanitize_callback' => array( __CLASS__, 'themeslug_customizer_sanitize_radio',
				)
			);

			$wp_manager->add_control( 'diagnostic_type',
				array(
					'type'        => 'radio',
					'section'     => 'section_id',
					'label'       => __( 'Diagnostic Selection' ),
					'description' => __( 'This is a custom radio input.' ),
					'choices'     => array(
						'mapping'    => __( 'Domain Mapping' ),
						'mods'       => __( 'Theme Mods' ),
						// 'green'   => __( 'Green' ),
					),
				)
			);
		}

	}

	/**
	 * A section to show how you use the default customizer controls in WordPress
	 *
	 * @param  Obj $wp_manager - WP Manager
	 *
	 * @return Void
	 */
	private static function sanitize_select( $input, $setting ) {

		// Ensure input is a slug.
		$input = sanitize_key( $input );

		// Get list of choices from the control associated with the setting.
		$choices = $setting->manager->get_control( $setting->id )->choices;

		// If the input is a valid key, return it; otherwise, return the default.
		return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
	}

	/**
	 * A section to show how you use the default customizer controls in WordPress
	 *
	 * @param  Obj $wp_manager - WP Manager
	 *
	 * @return Void
	 */
	private static function sanitize_text( $input ) {
		return wp_kses_post( force_balance_tags( $input ) );
	}

}
