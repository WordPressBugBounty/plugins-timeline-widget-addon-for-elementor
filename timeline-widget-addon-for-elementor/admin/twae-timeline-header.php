<?php

// phpcs:disable WordPress.Security.NonceVerification.Recommended ,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
/**
 * Timeline Widget for Elementor — global header screen check and hook wiring.
 *
 * @package TimelineWidget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'twae_is_timeline_header_page' ) ) {
	/**
	 * Whether the current admin screen should show the TWAE global header.
	 *
	 * @return bool
	 */
	function twae_is_timeline_header_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		return 'twae-getting-started' === $page;
	}
}

require_once __DIR__ . '/timeline-global-header.php';

add_action(
	'admin_enqueue_scripts',
	static function () {
		if ( ! twae_is_timeline_header_page() ) {
			return;
		}

		cp_timeline_header_enqueue_styles( defined( 'TWAE_VERSION' ) ? TWAE_VERSION : '1.0.0' );

		// Hide Cool Timeline Free (legacy) header when it also targets shared addon pages.
		wp_add_inline_style(
			'cp-timeline-global-header',
			'body.twae-timeline-header-page .ctl-global-timeline-header{display:none!important;}'
		);
	},
	99
);

// Remove Cool Timeline Free's legacy admin_notices header before it fires.
add_action(
	'admin_head',
	static function () {
		if ( ! twae_is_timeline_header_page() ) {
			return;
		}

		global $wp_filter;

		if ( empty( $wp_filter['admin_notices'] ) || empty( $wp_filter['admin_notices']->callbacks ) ) {
			return;
		}

		foreach ( $wp_filter['admin_notices']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $hook ) {
				if ( empty( $hook['function'] ) || ! is_array( $hook['function'] ) || ! isset( $hook['function'][1] ) ) {
					continue;
				}

				if ( 'maybe_render_global_header' === $hook['function'][1] ) {
					remove_action( 'admin_notices', $hook['function'], (int) $priority );
				}
			}
		}
	}
);

add_filter(
	'admin_body_class',
	static function ( $classes ) {
		if ( twae_is_timeline_header_page() ) {
			$classes .= ' twae-timeline-header-page ctl-timeline-addon-page';
		}

		return $classes;
	}
);

add_action(
	'in_admin_header',
	static function () {
		if ( ! twae_is_timeline_header_page() ) {
			return;
		}

		if($_GET['page']=='twae-getting-started' && isset($_GET['mode']) && $_GET['mode'] === 'onboarding'){
			return;
		}	

		$utm_params = '?utm_source=twae_plugin&utm_medium=inside&utm_campaign=docs&utm_content=global-header';
		cp_timeline_header_render(
			array(
				'heading'       => __( 'Timeline Addons', 'timeline-widget-addon-for-elementor' ),
				'icon_url'      => TWAE_URL . 'assets/images/timeline-icon.svg',
				'docs_url'      => 'https://cooltimeline.com/docs/timeline-widget-pro-addon-for-elementor/'.$utm_params,
				'support_url'   => 'https://coolplugins.net/support/'.$utm_params,
				'docs_label'    => __( 'Check Docs', 'timeline-widget-addon-for-elementor' ),
				'support_label' => __( 'Get Support', 'timeline-widget-addon-for-elementor' ),
				'text_domain'   => 'timeline-widget-addon-for-elementor',
			)
		);
	}
);
