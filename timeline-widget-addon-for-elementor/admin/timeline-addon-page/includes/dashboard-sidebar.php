<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 *
 * Addon dashboard sidebar.
 */

if ( ! isset( $this->main_menu_slug ) ) :
	return false;
 endif;

 $twae_support_email = 'https://coolplugins.net/support/?utm_source=twae_plugin&utm_medium=inside&utm_campaign=support&utm_content=dashboard';
?>

 <div class="cool-body-right">
	<a href="https://coolplugins.net/?utm_source=twae_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=dashboard" target="_blank"><img src="<?php echo esc_url( TWAE_URL ) . 'admin/timeline-addon-page/assets/coolplugins-logo.png'; ?>"></a>
	<ul>
	  <li><?php 
	  echo esc_html__( 'Cool Plugins develops best timeline plugins for WordPress.','timeline-widget-addon-for-elementor' ); ?></li>
	  <li><?php 
	  /* translators: %1$s: opening bold tag, %2$s: closing bold tag */ printf( esc_html__( 'Our timeline plugins have %1$s50000+%2$s active installs.', 'timeline-widget-addon-for-elementor' ), '<b>', '</b>' ); ?></li>
	  <li>
		<?php 
		echo esc_html__( 'For any query or support, please contact plugin support team.', 'timeline-widget-addon-for-elementor' ); ?>
		<br><br>
		<a href="<?php echo esc_url( $twae_support_email ); ?>" target="_blank" class="button button-secondary">
			<?php 
			echo esc_html__( 'Premium Plugin Support', 'timeline-widget-addon-for-elementor' ); ?>
		</a>
		<br><br>
	  </li>
	</ul>
</div>

</div><!-- End of main container-->
