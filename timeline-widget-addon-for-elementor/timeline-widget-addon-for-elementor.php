<?php
/**
 * Plugin Name: Timeline Widget For Elementor
 * Description: Best timeline widget for Elementor page builder to showcase your personal or business stories in beautiful vertical or horizontal timeline layouts. <strong>[Elementor Addon]</strong>
 * Plugin URI:  https://coolplugins.net
 * Version:     1.7.0
 * Author:      Cool Plugins
 * Author URI:  https://coolplugins.net/?utm_source=twae_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
 * Text Domain: timeline-widget-addon-for-elementor
 * License:GPLv2 or later 
 * License URI:http://www.gnu.org/licenses/gpl-2.0.html
 * Elementor tested up to: 4.2.0  
 * Elementor Pro tested up to: 4.2.0 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( defined( 'TWAE_VERSION' ) ) {
	return;
}

define( 'TWAE_VERSION', '1.7.0' );
define( 'TWAE_FILE', __FILE__ );
define( 'TWAE_PATH', plugin_dir_path( TWAE_FILE ) );
define( 'TWAE_URL', plugin_dir_url( TWAE_FILE ) );
define( 'TWAE_BUY_PRO_LINK', 'https://cooltimeline.com/plugin/elementor-timeline-widget-pro/?utm_source=twae_plugin&utm_medium=inside&utm_campaign=get_pro' );
define( 'TWAE_FEEDBACK_API', 'https://feedback.coolplugins.net/' );
if ( ! defined( 'TWAE_DEMO_URL' ) ) {
	define( 'TWAE_DEMO_URL', 'https://cooltimeline.com/demo/?utm_source=twae_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard' );
}


// Lightweight — only registers this copy as a version candidate.
require_once TWAE_PATH . 'admin/cp-onboarding/loader.php';
cpo_onboarding_register( '1.1.1', TWAE_PATH . 'admin/cp-onboarding' );


register_activation_hook( TWAE_FILE, array( 'Timeline_Widget_Addon', 'twae_activate' ) );
register_deactivation_hook( TWAE_FILE, array( 'Timeline_Widget_Addon', 'twae_deactivate' ) );

/**
 * Class Timeline_Widget_Addon
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
final class Timeline_Widget_Addon {


	/**
	 * Plugin instance.
	 *
	 * @var Timeline_Widget_Addon
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Timeline_Widget_Addon
	 * @static
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @access private
	 */
	private function __construct() {
		// Load the plugin after Elementor (and other plugins) are loaded.
		add_action( 'plugins_loaded', array( $this, 'twae_plugins_loaded' ) );
		add_action( 'plugins_loaded', array( $this, 'twae_load_addon' ) );
		add_action('init', array($this, 'twae_plugin_textdomain'));
		add_action( 'activated_plugin', array( $this, 'twae_plugin_redirection' ) );

	    $this->cpfm_load_file();
	}

	public function cpfm_load_file(){

        if(!class_exists('CPFM_Feedback_Notice')){
            require_once __DIR__ . '/admin/feedback/cpfm-feedback-notice.php';
        }
        require_once __DIR__ . '/includes/cron/class-cron.php';
    }

	/**
	 * Code you want to run when all other plugins loaded.
	 */
	public function twae_plugins_loaded() {

		// Notice if the Elementor is not active
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'twae_fail_to_load' ) );
			return;
		}
        
		require_once TWAE_PATH . 'includes/migration/twae-migration.php';
		require_once TWAE_PATH . 'includes/migration/twae-migration-ajax.php';

		if ( class_exists( '\Elementor\Plugin' ) ) {
			require_once TWAE_PATH . '/admin/marketing/twae-marketing-common.php';
		}
		
		if ( is_admin() ) {
			
			$pluginpath= plugin_basename( __FILE__ );
			/*** Plugin review notice file */
			require_once __DIR__ . '/admin/feedback-notice/twae-feedback-notice.php';
			new TWAEFeedbackNotice();
			require_once __DIR__ . '/admin/feedback/twae-admin-feedback-form.php';

			add_action( 'cpo_onboarding_loaded', function () {
			require TWAE_PATH . '/admin/cp-onboarding/onboarding-config.php';
			} );

			require_once TWAE_PATH . 'admin/twae-timeline-header.php';

			add_filter( "plugin_action_links_$pluginpath", array($this, 'twae_settings_link' ) );
			add_action( 'admin_print_scripts', array( $this, 'ctl_hide_unrelated_notices' ), 999 );

			if ( $this->twae_needs_standalone_settings_menu() ) {
				add_action( 'admin_menu', array( $this, 'twae_register_timeline_addons_menu' ), 9 );
				add_action( 'admin_head', array( $this, 'twae_hide_getting_started_settings_submenu_css' ) );
				add_filter( 'parent_file', array( $this, 'twae_highlight_addons_menu' ), 999 );
				add_filter( 'submenu_file', array( $this, 'twae_highlight_addons_submenu' ), 999 );
			}
		}

		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'twae_form_plugin_notice' ) );
		}

		add_action('cpfm_register_notice', function () {
            
			if (!class_exists('CPFM_Feedback_Notice') || !current_user_can('manage_options')) {
				return;
			}

			$notice = [
				'title' => __('Timeline Plugins by Cool Plugins', 'timeline-widget-addon-for-elementor'),
				'message' => __('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'timeline-widget-addon-for-elementor'),
				'pages' => ['twae-getting-started'],
				'always_show_on' => ['twae-getting-started'],
				'plugin_name'=>'twae'
			];
			

			CPFM_Feedback_Notice::cpfm_register_notice('cool-timeline', $notice);
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				if (!isset($GLOBALS['cool_plugins_feedback'])) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
					$GLOBALS['cool_plugins_feedback'] = [];
				}
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				$GLOBALS['cool_plugins_feedback']['cool-timeline'][] = $notice;
	   
		});
		add_action('cpfm_after_opt_in_twae', function($category) {

			if ($category === 'cool-timeline') {

				TWAE_cronjob::twae_send_data();
			}
		});

	}   // end of ctla_loaded()

	/**
	 * Load the plugin text domain for translation.
	 */
	public function twae_plugin_textdomain() {
		
		if (!get_option( 'twae_initial_save_version' ) ) {
                add_option( 'twae_initial_save_version', TWAE_VERSION );
            }
            if(!get_option( 'twae-install-date' ) ) {
                add_option( 'twae-install-date', gmdate('Y-m-d H:i:s') );
            }
	}

	function twae_plugin_redirection( $plugin ) {
		if ( plugin_basename( __FILE__ ) !== $plugin ) {
			return;
		}

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( is_plugin_active( 'elementor/elementor.php' ) ) {

			// Only redirect on a fresh first-time activation. The transient
			// is set in ctl_activate() and absent for upgrades/reactivations.
			if ( ! get_transient( 'twae_activation_redirect' ) ) {
				return;
			}
			delete_transient( 'twae_activation_redirect' );

			// Skip the redirect during bulk plugin activations so we don't
			// hijack a multi-plugin activate request.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check, no state change.
			if ( isset( $_GET['activate-multi'] ) ) {
				return;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=twae-getting-started&mode=onboarding' ) );
			exit;
		}
	}

    public function twae_settings_link( $links ) {

			$links[]='<a href="admin.php?page=twae-getting-started&mode=onboarding">Getting Started</a>';
			$links[] = '<a style="font-weight:bold; color:#852636;" href="https://cooltimeline.com/plugin/elementor-timeline-widget-pro/?utm_source=twae_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugins_list#pricing" target="_blank">Get Pro</a>';

			return $links;
		}

	/**
	 * Whether TWAE should register its own Settings > Timeline Addons menu (standalone only).
	 *
	 * @return bool
	 */
	private function twae_needs_standalone_settings_menu() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return ! is_plugin_active( 'cool-timeline/cooltimeline.php' )
			&& ! is_plugin_active( 'cool-timeline-pro/cool-timeline-pro.php' );
	}

	/**
	 * Register "Timeline Addons" under Settings (standalone TWAE).
	 *
	 * @return void
	 */
	public function twae_register_timeline_addons_menu() {
		global $_wp_real_parent_file;

		// Nested under Settings: remap so get_admin_page_parent() (called after the
		// parent_file filter in menu-header.php) highlights Settings, not the
		// virtual cool-plugins-timeline-addon group slug.
		$_wp_real_parent_file['cool-plugins-timeline-addon'] = 'options-general.php';

		$hook = add_submenu_page(
			'options-general.php',
			__( 'Timeline Addons', 'timeline-widget-addon-for-elementor' ),
			__( 'Timeline Addons', 'timeline-widget-addon-for-elementor' ),
			'manage_options',
			'cool-plugins-timeline-addon',
			'__return_null'
		);

		add_action( 'load-' . $hook, array( $this, 'twae_redirect_addons_menu_to_getting_started' ) );
	}

	

	/**
	 * Redirect the Timeline Addons submenu to Getting Started.
	 *
	 * @return void
	 */
	public function twae_redirect_addons_menu_to_getting_started() {
		wp_safe_redirect( admin_url( 'admin.php?page=twae-getting-started' ) );
		exit;
	}

	/**
	 * Hide the Getting Started row from the Settings submenu (standalone TWAE).
	 *
	 * The page stays registered and URL-accessible; only the sidebar link is hidden.
	 *
	 * @return void
	 */
	public function twae_hide_getting_started_settings_submenu_css() {
		echo '<style id="twae-hide-getting-started-settings-submenu">
/* Hide Getting Started submenu under Settings menu only */
#menu-settings .wp-submenu li:has(> a[href="options-general.php?page=twae-getting-started"]) {
	display: none !important;
}
</style>';
	}

	/**
	 * Highlight Timeline Addons parent menu on the Getting Started page.
	 *
	 * @param string $parent_file Current parent menu slug.
	 * @return string
	 */
	public function twae_highlight_addons_menu( $parent_file ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( in_array( $page, array( 'twae-getting-started', 'cool-plugins-timeline-addon' ), true ) ) {
			return 'options-general.php';
		}

		return $parent_file;
	}

	/**
	 * Highlight the Timeline Addons submenu under Settings.
	 *
	 * @param string|null $submenu_file Current submenu slug.
	 * @return string|null
	 */
	public function twae_highlight_addons_submenu( $submenu_file ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( in_array( $page, array( 'twae-getting-started', 'cool-plugins-timeline-addon' ), true ) ) {
			return 'cool-plugins-timeline-addon';
		}

		return $submenu_file;
	}

	function twae_load_addon() {
		// Load plugin file
		require_once TWAE_PATH . '/includes/class-twae-free-main.php';
		// Run the plugin
		TWAE_Free_Main::instance();

	}

	public function twae_form_plugin_notice() {
		if(class_exists('twae_free_form_plugin_notice')){
			
			twae_free_form_plugin_notice::instance('cool-form-free','Are you using the <strong>Elementor Form widget</strong> to create forms? Make your forms smarter with conditional fields and improve your form-building experience!<br><a href="'.esc_url(site_url().'/wp-admin/plugin-install.php?tab=plugin-information&plugin=conditional-fields-for-elementor-form&TB_iframe=true&width=772&height=885').'" class="thickbox button button-primary open-plugin-details-modal" style="margin-right: 10px; margin-top: 10px;">'.esc_html__( 'Install Plugin', 'timeline-widget-addon-for-elementor' ).'</a><a href="'.esc_url("https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=twae_plugin&utm_medium=inside&utm_campaign=plugins_list&utm_content=demo#demos").'" class="button button-primary" target="_blank" style="margin-right: 10px;">'.esc_html__( 'View Demos', 'timeline-widget-addon-for-elementor' ).'</a>',5);
		}
	}

	function twae_fail_to_load() {

		if ( ! is_plugin_active( 'elementor/elementor.php' ) ) : ?>
			<div class="notice notice-warning is-dismissible">
				<p><?php 
				/* translators: 1: Plugin name 2: PHP 3: Required PHP version */  echo wp_kses_post( sprintf( __( '<a href="%s"  target="_blank" >Elementor Page Builder</a>  must be installed and activated for "<strong>Timeline Widget Addon For Elementor</strong>" to work' , 'timeline-widget-addon-for-elementor' ), 'https://wordpress.org/plugins/elementor/' ) ); ?></p>
			</div>
			<?php
			deactivate_plugins( 'timeline-widget-addon-for-elementor/timeline-widget-addon-for-elementor.php' );
		endif;

	}

	/**
	 * On Timeline Widget dashboard pages, hide unrelated admin notices by pruning
	 * the core notice hooks. Cool Plugins / TWAE notices are kept.
	 *
	 * @return void
	 */
	public function ctl_hide_unrelated_notices() {
		if ( ! defined( 'TWAE_CTL_ADMIN_NOTICE_DISPATCHER_ADDED' ) ) {
			define( 'TWAE_CTL_ADMIN_NOTICE_DISPATCHER_ADDED', true );
			add_action(
				'admin_notices',
				array( $this, 'ctl_dash_admin_notices' ),
				PHP_INT_MAX
			);
		}

		if ( ! defined( 'CTL_ADMIN_NOTICE_HOOKED' ) ) {
			define( 'CTL_ADMIN_NOTICE_HOOKED', true );
		}

		if ( ! function_exists( 'twae_is_timeline_addon_page' ) || ! twae_is_timeline_addon_page() ) {
			return;
		}

		global $wp_filter;

		$rules = array(
			'user_admin_notices'    => array(),
			'admin_notices'         => array(),
			'all_admin_notices'     => array(),
			'network_admin_notices' => array(),
			'admin_footer'          => array(
				'render_delayed_admin_notices',
			),
		);

		foreach ( array_keys( $rules ) as $notice_type ) {
			if ( empty( $wp_filter[ $notice_type ] ) || empty( $wp_filter[ $notice_type ]->callbacks ) || ! is_array( $wp_filter[ $notice_type ]->callbacks ) ) {
				continue;
			}

			$remove_all = empty( $rules[ $notice_type ] );

			foreach ( $wp_filter[ $notice_type ]->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( ! isset( $arr['function'] ) ) {
						continue;
					}
					$fn = $arr['function'];

					if ( $remove_all ) {
						$keep  = false;
						$class = '';

						if ( is_array( $fn ) && ! empty( $fn[0] ) && is_object( $fn[0] ) ) {
							$class = strtolower( get_class( $fn[0] ) );
						} elseif ( is_object( $fn ) ) {
							$class = strtolower( get_class( $fn ) );
						}

						if ( $class ) {
							$keep = (
								false !== strpos( $class, 'cooltimeline' ) ||
								false !== strpos( $class, 'cool_plugins' ) ||
								false !== strpos( $class, 'ctl_admin' ) ||
								false !== strpos( $class, 'ctp_' ) ||
								false !== strpos( $class, 'license_helper' ) ||
								false !== strpos( $class, 'twae' ) ||
								false !== strpos( $class, 'cpfm' ) ||
								false !== strpos( $class, 'tmdivi' )
							);
						}

						if ( ! $keep && is_string( $fn ) ) {
							$keep = ( 0 === strpos( $fn, 'ctl_' ) || 0 === strpos( $fn, 'cool_' ) || 0 === strpos( $fn, 'twae_' ) || 0 === strpos( $fn, 'tmdivi_' ) );
						}

						if ( ! $keep ) {
							unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
						}
						continue;
					}

					$cb = is_array( $fn ) ? $fn[1] : $fn;
					if ( in_array( $cb, $rules[ $notice_type ], true ) ) {
						unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}
	}

	/**
	 * Dispatcher for Cool Plugins admin notices after third-party notices are pruned.
	 *
	 * @return void
	 */
	public function ctl_dash_admin_notices() {
		if ( did_action( 'ctl_display_admin_notices' ) > 0 ) {
			return;
		}

		do_action( 'ctl_display_admin_notices' );
	}

	/**
	 * Run when activate plugin.
	 */
	public static function twae_activate() {

		update_option( 'twae-free-v', sanitize_text_field( TWAE_VERSION ) );
		update_option( 'twae-type', 'FREE' );

			/**
			 * Detect if this is a new user.

			 * This prevents the redirect from firing every time an existing user
			 * deactivates/reactivates or updates the plugin.
			 */
			$is_new_user = ( false === get_option( 'twae-installDate' ) )
					&& ( false === get_option( 'twae_initial_save_version' ) );
					
			// Only show welcome redirect for genuine first-time installs.
			if ( $is_new_user ) {
				update_option( 'twae_is_new_user', 'yes' );
				update_option( 'twae_onboarding_method', 'default', false );
				set_transient( 'twae_activation_redirect', 1, 5 * MINUTE_IN_SECONDS );
			}

		update_option( 'twae-installDate',  gmdate( 'Y-m-d H:i:s' ) );

		
		if (!get_option( 'twae_initial_save_version' ) ) {
			add_option( 'twae_initial_save_version', TWAE_VERSION );
		}

		if(!get_option( 'twae-install-date' ) ) {
			add_option( 'twae-install-date', gmdate('Y-m-d H:i:s') );
		}
		$review_option = get_option( 'cpfm_opt_in_choice_cool-timeline' );
		if($review_option === 'yes'){

			if (!wp_next_scheduled('twae_extra_data_update')) {
	
				wp_schedule_event(time(), 'every_30_days', 'twae_extra_data_update');
	
			}
	}

	}

	/**
	 * Run when deactivate plugin.
	 */
	public static function twae_deactivate() {

		if (wp_next_scheduled('twae_extra_data_update')) {
			wp_clear_scheduled_hook('twae_extra_data_update');
		}

	}
}

function twae_get_plugin_instance() {
	return Timeline_Widget_Addon::get_instance();
}

/**
 * Whether the current admin screen is a Timeline Widget Free dashboard page.
 *
 * @return bool
 */
if ( ! function_exists( 'twae_is_timeline_addon_page' ) ) {
	function twae_is_timeline_addon_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		return in_array( $page, array( 'twae-getting-started', 'cool-plugins-timeline-addon' ), true );
	}
}

$GLOBALS['twae_plugin_instance'] = twae_get_plugin_instance();
