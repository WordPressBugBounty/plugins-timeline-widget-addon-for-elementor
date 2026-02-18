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
	echo esc_html__( 'Docs', 'timeline-widget-addon-for-elementor' ); ?></a>
	<a href="<?php echo esc_url( $twae_cool_plugins_more_info ); ?>" target="_info" class="button"><?php 
	echo esc_html__( 'Demos', 'timeline-widget-addon-for-elementor' ); ?></a>
</div>
