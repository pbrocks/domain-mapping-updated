<?php

/**
 * Check for WP_Customizer_Control existence before adding custom control because WP_Customize_Control
 * is loaded on customizer page only
 *
 * @see _wp_customize_include()
 */
if ( class_exists( 'WP_Customize_Control' ) ) {

	class Customizer_Toggle_Control extends WP_Customize_Control {
		public $type = 'ios';

		/**
		 * Enqueue scripts/styles.
		 *
		 * @since 3.4.0
		 */
		public function enqueue() {
			wp_enqueue_script( 'customizer-toggle-control', plugins_url( '/js/customizer-toggle-control.js',__FILE__ ), array( 'jquery' ), rand(), true );
			wp_enqueue_style( 'pure-css-toggle-buttons', plugins_url( '/pure-css-toggle-buttons/pure-css-togle-buttons.css', __FILE__ ), array(), rand() );

			$css = '
				.disabled-control-title {
					color: #a0a5aa;
				}
				input[type=checkbox].tgl-light:checked + .tgl-btn {
			  		background: #0085ba;
				}
				input[type=checkbox].tgl-light + .tgl-btn {
				  background: #a0a5aa;
			  	}
				input[type=checkbox].tgl-light + .tgl-btn:after {
				  background: #f7f7f7;
			  	}

				input[type=checkbox].tgl-ios:checked + .tgl-btn {
				  background: #0085ba;
				}

				input[type=checkbox].tgl-flat:checked + .tgl-btn {
				  border: 4px solid #0085ba;
				}
				input[type=checkbox].tgl-flat:checked + .tgl-btn:after {
				  background: #0085ba;
				}

			';
			wp_add_inline_style( 'pure-css-toggle-buttons' , $css );
		}

		/**
		 * Render the control's content.
		 *
		 * @author soderlind
		 * @version 1.2.0
		 */
		public function render_content() {
			?>
			<label>
				<div style="display:flex;flex-direction: row;justify-content: flex-start;">
					<span class="customize-control-title" style="flex: 2 0 0; vertical-align: middle;"><?php echo esc_html( $this->label ); ?></span>
					<input id="cb<?php echo $this->instance_number ?>" type="checkbox" class="tgl tgl-<?php echo $this->type?>" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); checked( $this->value() ); ?> />
					<label for="cb<?php echo $this->instance_number ?>" class="tgl-btn"></label>
				</div>
				<?php if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo $this->description; ?></span>
				<?php endif; ?>
			</label>
			<?php
		}
	}
// }


	/**
	 * Add to customizer.
	 *
	 * @author soderlind
	 * @version 1.0.0
	 * @param   WP_Customize_Manager    $wp_customize
	 */
	function customize_post_meta( $wp_customize ) {

		$wp_customize->add_section( 'post_meta', array(
				'title'    => _x( 'Post Meta', 'customizer menu section', '2016-customizer-demo' ),
				'priority' => 25,
		) );
		$wp_customize->add_setting( 'show_author', array(
				'default'    => '1',
				'capability' => 'manage_options',
				'transport' => 'postMessage',
		) );
		$wp_customize->add_control( new Customizer_Toggle_Control( $wp_customize, 'show_author', array(
				'settings' => 'show_author',
				'label'    => _x( 'Show Author', 'customizer menu setting', '2016-customizer-demo' ),
				'section'  => 'post_meta',
				'type'     => 'ios',
		) ) );
		$wp_customize->add_setting( 'show_date', array(
				'default'    => '1',
				'capability' => 'manage_options',
				'transport' => 'postMessage',
		) );
		$wp_customize->add_control( new Customizer_Toggle_Control( $wp_customize, 'show_date', array(
				'settings' => 'show_date',
				'label'    => _x( 'Show Date', 'customizer menu setting', '2016-customizer-demo' ),
				'section'  => 'post_meta',
				'type'     => 'ios',
		) ) );
		$wp_customize->add_section( 'svg_logo', array(
				'title'    => _x( 'SVG Logo', 'customizer menu section', '2016-customizer-demo' ),
				'priority' => 10,
		) );
		$wp_customize->add_setting( 'svg_logo_remove', array(
				'default'     => true,
				'capability'  => 'edit_theme_options',
				'transport'   => 'postMessage',
		) );
		$wp_customize->add_control( new Customizer_Toggle_Control( $wp_customize, 'svg_logo_remove', array(
				'label'	      => esc_html__( 'Display SVG logo', '2016-customizer-demo' ),
				'section'     => 'svg_logo',
				'settings'    => 'svg_logo_remove',
				'type'        => 'ios',// light, ios, flat
		) ) );
		$wp_customize->add_setting( 'svg_logo_url', array(
				'default'       => get_theme_mod( 'svg_logo_url', get_template_directory_uri() . '/svg/logo01.svg' ),
				'capability'    => 'edit_theme_options',
				'transport'     => 'postMessage',
		) );
		$wp_customize->add_control( new Customizer_SVG_Picker_Option( $wp_customize, 'svg_logo_url', array(
				'section'     => 'svg_logo',
				'settings'    => 'svg_logo_url',
				'type'        => 'svg',
		) ) );
		$wp_customize->add_setting( 'svg_logo_width', array(
				'default'       => get_theme_mod( 'svg_logo_width', '240' ),
				'capability'    => 'edit_theme_options',
				'transport'     => 'postMessage',
		) );

		$wp_customize->add_control( new Customizer_Range_Value_Control( $wp_customize, 'svg_logo_width', array(
				'type'     => 'range-value',
				'section'  => 'svg_logo',
				'settings' => 'svg_logo_width',
				'label'    => __( 'Logo Width' ),
				'input_attrs' => array(
					'min'    => 1,
					'max'    => 240,
					'step'   => 1,
					'suffix' => 'px',
			  ),
		) ) );

	}
	add_action( 'customize_register','customize_post_meta' );

}
