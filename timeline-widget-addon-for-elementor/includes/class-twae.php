<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Add a custom category for panel widgets
add_action(
	'elementor/init',
	function() {
		\Elementor\Plugin::$instance->elements_manager->add_category(
			'twae',              // the name of the category
			array(
				// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'title' => esc_html__( 'Timeline Widget Addon For Elementor', 'twae' ),
				'icon'  => 'fa fa-header', // default icon
			),
			1 // position
		);
	}
);

/**
 * Main Plugin Class
 *
 * Register new elementor widget.
 *
 * @since 1.0.0
 */
class TWAE_WidgetClass {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		add_filter( 'elementor/editor/localize_settings', array( $this, 'twae_localize_settings' ) );
		$this->twae_add_actions();
	}

	public function twae_localize_settings( $config ) {
		 $promotion_widgets = array();

		if ( isset( $config['promotionWidgets'] ) ) {
			$promotion_widgets = $config['promotionWidgets'];
		}

		$combine_array = array_merge(
			$promotion_widgets,
			array(
				array(
					'name'        => 'twae-post-timeline',
					// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'title'       => esc_html__( 'Post Timeline', 'twae' ),
					// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'description' => esc_html__( 'it is testing fine here', 'twae' ), 
					
					'icon'        => 'twae-pro eicon-time-line',
					'categories'  => '["twae"]',
				),       
			)
		);

		$config['promotionWidgets'] = $combine_array;

		return $config;
	}
	/**
	 * Add Actions
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function twae_add_actions() {
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'twae_on_widgets_registered' ) );
	}

	/**
	 * On Widgets Registered
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function twae_on_widgets_registered() {
		$this->twae_widget_includes();
	}

	/**
	 * Includes
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function twae_widget_includes() {
		require_once TWAE_PATH . 'widgets/twae-widget.php';
	}

}

new TWAE_WidgetClass();
