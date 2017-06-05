<?php
/**
 * Customize for user select, extend the WP customizer
 */

if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return NULL;
}

class Sites_Dropdown_Custom_Control extends WP_Customize_Control {

	private $sites = false;

	public function __construct( $manager, $id, $args = array(), $options = array() ) {
		$this->sites = get_sites( $options );

		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Render the control's content.
	 *
	 * Allows the content to be overriden without having to rewrite the wrapper.
	 *
	 * @since   01/13/2013
	 * @return  void
	 */
	public function render_content() {
		if ( empty( $this->sites ) ) {
			return false;
		}

		?>
		<label>
			<span class="customize-control-title" ><?php echo esc_html( $this->label ); ?></span>
			<select <?php $this->link(); ?>>
				<?php foreach( $this->sites as $site ) {
					printf( '<option value="%s" %s>%s</option>',
						$site->id,
						selected( $this->value(), $site->id, false ),
						$site->blogname );
					}
			?></select>
			</label>
			<?php
	}
} // end class
