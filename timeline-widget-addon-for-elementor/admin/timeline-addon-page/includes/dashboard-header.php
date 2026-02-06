<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * This php file render HTML header for addons dashboard page
 */
if ( ! isset( $this->main_menu_slug ) ) :
	return;
endif;

$twae_cool_plugins_docs      = 'https://cooltimeline.com/docs/?utm_source=twae_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard';
$twae_cool_plugins_more_info = TWAE_DEMO_URL;
?>

<div id="cool-plugins-container" class="cool-plugins-timeline-addon">
	<div class="cool-header">
		<h2 style=""><?php echo esc_html( $this->dashboar_page_heading ); ?></h2>
	<a href="<?php echo esc_url( $twae_cool_plugins_docs ); ?>" target="_docs" class="button"><?php 
	// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
	echo esc_html__( 'Docs', 'twae' ); ?></a>
	<a href="<?php echo esc_url( $twae_cool_plugins_more_info ); ?>" target="_info" class="button"><?php 
	// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
	echo esc_html__( 'Demos', 'twae' ); ?></a>
</div>
