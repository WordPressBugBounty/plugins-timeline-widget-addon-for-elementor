<?php
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (! class_exists('TWAEFeedbackNotice')) {
	class TWAEFeedbackNotice
	{
		/**
		 * The Constructor
		 */
		public function __construct()
		{
			// register actions

			if (is_admin()) {
				add_action('admin_notices', array($this, 'twae_admin_notice_for_reviews'));
				add_action('admin_enqueue_scripts', array($this, 'twae_load_script'));
				add_action('wp_ajax_twae_dismiss_notice', array($this, 'twae_dismiss_review_notice'));
			}
		}

		/**
		 * Load script to dismiss notices.
		 *
		 * @return void
		 */
		public function twae_load_script()
		{
			$already_rated = get_option( 'twae-alreadyRated', 'no' );

			// check user already rated
			if ( 'yes' === $already_rated ) {
				return;
			}

			wp_register_style( 'twae-feedback-notice', TWAE_URL . 'admin/feedback-notice/assets/css/twae-feedback-notice.css', array(), TWAE_VERSION, 'all' );

			wp_register_script( 'twae-admin-notice', TWAE_URL . 'admin/feedback-notice/assets/js/twae-feedback-notice.js', array( 'jquery' ), TWAE_VERSION, true );
		}
		// ajax callback for review notice
		public function twae_dismiss_review_notice()
		{
			if ( ! current_user_can( 'update_plugins' ) ) {
				wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
			}

			check_ajax_referer( 'twae_dismiss_nonce', 'nonce' );

			update_option( 'twae-alreadyRated', 'yes' );
			wp_send_json_success( array( 'success' => 'true' ) );
		}
		// admin notice
		public function twae_admin_notice_for_reviews()
		{
			if (! current_user_can('update_plugins')) {
				return;
			}
			// get installation dates and rated settings
			$installation_date = get_option('twae-installDate');
			$already_rated = get_option( 'twae-alreadyRated', 'no' );

			// check user already rated
			if ( 'yes' === $already_rated ) {
				return;
			}

			// Fall back to install date set on init if activation option is missing.
			if ( empty( $installation_date ) ) {
				$installation_date = get_option( 'twae-install-date' );
			}
			if ( empty( $installation_date ) ) {
				return;
			}

			$install_timestamp = strtotime( (string) $installation_date );
			if ( false === $install_timestamp ) {
				return;
			}

			$diff_days = (int) floor( ( time() - $install_timestamp ) / DAY_IN_SECONDS );

			// Show review notice after 3+ days since install.
			if ( $diff_days >= 3 ) {
			wp_enqueue_style( 'twae-feedback-notice' );
        	wp_enqueue_script( 'twae-admin-notice' );
			/*
			 * Do not wrap this in wp_kses() / wp_kses_post() on output: a full-document kses pass can strip
			 * or rewrite markup (e.g. javascript: hrefs, attributes) that this notice and its dismiss script rely on,
			 * which can break admin behavior. All dynamic values are escaped or wp_kses()'d inside twae_create_notice_content().
			 */
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled HTML; pieces escaped in twae_create_notice_content().
			echo $this->twae_create_notice_content();
		}
		}

		// generated review notice HTML
		function twae_create_notice_content()
		{
			$wp_nonce = esc_attr( wp_create_nonce( 'twae_dismiss_nonce' ) );
			$ajax_url      = esc_url( admin_url( 'admin-ajax.php' ) );
			$ajax_callback      = esc_attr( 'twae_dismiss_notice' );
			$wrap_cls           = esc_attr( 'notice notice-info is-dismissible' );
			$img_path      = esc_url( TWAE_URL . 'assets/images/timeline-widget-logo.png' );
			$p_name             = esc_html__( 'Timeline Widget Addon For Elementor', 'timeline-widget-addon-for-elementor' );
			$like_it_text       = esc_html__( 'Rate Now! ★★★★★', 'timeline-widget-addon-for-elementor' );
			$already_rated_text = esc_html__('Already Reviewed', 'timeline-widget-addon-for-elementor');
			$not_interested     = esc_html__('Not Interested', 'timeline-widget-addon-for-elementor');
			$not_like_it_text   = esc_html__('No, not good enough, i do not like to rate it!', 'timeline-widget-addon-for-elementor');
			$p_link             = esc_url('https://wordpress.org/support/plugin/timeline-widget-addon-for-elementor/reviews/#new-post');
			$pro_url            = esc_url('https://cooltimeline.com/plugin/elementor-timeline-widget-pro/');
			$raw_message = "Thanks for using <b>$p_name</b> WordPress plugin. We hope it meets your expectations! <br/>Please give us a quick rating, it works as a boost for us to keep working on more <a href='https://coolplugins.net' target='_blank'><strong>Cool Plugins</strong></a>!<br/>";
			$allowed_html = array(
				'b'      => array(),
				'br'     => array(),
				'a'      => array(
					'href'   => array(),
					'target' => array(),
				),
				'strong' => array(),
			);

			$message = wp_kses($raw_message, $allowed_html);


			$html = '<div data-ajax-url="%8$s"  data-ajax-callback="%9$s" class="cool-feedback-notice-wrapper %1$s" data-wp-nonce="%12$s">
        <div class="message_container">%4$s
        <div class="callto_action">
        <ul>
            <li class="love_it"><a href="%5$s" class="like_it_btn button button-primary" target="_new" title="%6$s">%6$s</a></li>
            <li class="already_rated"><a href="javascript:void(0);" class="already_rated_btn button twae_dismiss_notice" title="%7$s">%7$s</a></li>
            <li class="already_rated"><a href="javascript:void(0);" class="already_rated_btn button twae_dismiss_notice" title="%11$s">%11$s</a></li>
           
        </ul>
        <div class="clrfix"></div>
        </div>
        </div>
        </div>';

			return sprintf(
				$html,
				$wrap_cls,
				$img_path,
				$p_name,
				$message,
				$p_link,
				$like_it_text,
				$already_rated_text,
				$ajax_url, // 8
				$ajax_callback, // 9
				$pro_url, // 10
				$not_interested,
				$wp_nonce
			);
		}
	} //class end

}
