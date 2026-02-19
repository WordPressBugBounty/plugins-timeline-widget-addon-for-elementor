<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class TWE_Migration_Notice_Manager {

    private static $instance = null;

    public static function instance() {

        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {

        add_action('admin_notices', array($this, 'twae_show_migration_notice'));
        add_action('wp_ajax_twae_run_migration', array($this,'twae_run_migration_callback'));
        add_action('wp_ajax_twae_hide_migration_notice', array($this, 'twae_hide_notice'));
        add_action('elementor/editor/after_enqueue_scripts', array($this, 'enqueue_editor_scripts'));
    }

    public function twae_hide_notice() {

        if ( ! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'twae_hide_migration_nonce') ) {
            wp_send_json_error('Invalid nonce');
        }
        
        if ( ! isset($_POST['value']) ) {
            wp_send_json_error('Missing value parameter');
        }
        
        $val = sanitize_text_field(wp_unslash($_POST['value']));
        update_option($val . '_hide_migration_notice', 'yes');
        wp_send_json_success();
    }
    
    public function enqueue_editor_scripts() {

        wp_enqueue_script(
            'twae-migration-js',
            TWAE_URL . 'includes/migration/assets/twae-migration.js',
            array('jquery'),
            '1.0',
            true
        );

        wp_localize_script('twae-migration-js', 'twae_migration_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('twae_migration_nonce'),
            'hide_migration_nonce'      => wp_create_nonce('twae_hide_migration_nonce'),
        ));
    }
    
    function twae_has_legacy_timeline_widgets() {

        $args = array(
            'post_type'      => array('post', 'page'),
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        );

        $posts = get_posts($args);

        if ( empty($posts) ) {
            return false;
        }

        foreach ($posts as $post_id) {

            $raw = get_post_meta($post_id, '_elementor_data', true);

            if ( empty($raw) ) continue;

            $data = json_decode($raw, true);

            if ( ! is_array($data) ) continue;

            if ( $this->twae_search_widgets_recursive($data) ) {
                return true;
            }
        }

        return false;
    }

    function twae_search_widgets_recursive($elements) {

        foreach ($elements as $el) {

            if (isset($el['widgetType']) && $el['widgetType'] === 'be-timeline') {
                return true;
            }

            if (!empty($el['elements']) && $this->twae_search_widgets_recursive($el['elements'])) {
                return true;
            }
        }

        return false;
    }


    function twae_show_migration_notice() {

        global $pagenow;

        $allowed_pages = array(
            'cool-plugins-timeline-addon',
            'timeline-addons-license',
            'twae-welcome-page',
        );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine admin page context, not processing form data.
        $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        
        if ( $pagenow !== 'plugins.php' && ! in_array( $current_page, $allowed_pages, true ) ) {
            return;
        }

        $active_plugins = get_option( 'active_plugins', [] );

        if (
            !in_array( '3r-elementor-timeline-widget/init.php', $active_plugins )
            || in_array( 'timeline-widget-addon-for-elementor-pro/timeline-widget-addon-pro-for-elementor.php', $active_plugins )
            || get_option('twae_hide_migration_notice') === 'yes'
            || !$this->twae_has_legacy_timeline_widgets()
        ) {
            return;
        }

        wp_enqueue_script(
            'twae-migration-js',
            TWAE_URL . 'includes/migration/assets/twae-migration.js',
            array('jquery'),
            '1.0',
            true
        );

        wp_localize_script('twae-migration-js', 'twae_migration_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('twae_migration_nonce'),
            'hide_migration_nonce'      => wp_create_nonce('twae_hide_migration_nonce'),
        ));

        ?>
        <div class="notice notice-info is-dismissible twae-migration-notice" data-tineline-mig="twae" style="min-height:30px; display:flex; align-items:center;">
            <div class="twae_eventprime_promotion-text" style="width: fit-content;padding: 5px 0px; display:flex; gap:10px;">
                <span><button class="button button-primary install-eventprime" aria-label="Install EventPrime Plugin" rel="noopener noreferrer" id="twae-run-migration">Migrate Now!</button></span>
                <span style="margin-top: 5px;"> We noticed you’re using the <strong>Vertical Timeline Widget for Elementor.</strong>  Upgrade your existing timelines to <a href="https://cooltimeline.com/elementor-widget/free-timeline/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=demo&utm_content=migration_notice"><Strong>Timeline Widget</strong></a> by Cool Plugins for enhanced features and a more refined design experience</span>
            </div>
            <div id="twae-migration-result"></div>
        </div>
        <?php
    }
    
    function twae_run_migration_callback() {

        check_ajax_referer('twae_migration_nonce', 'nonce');
    
        $manager = TWE_Migration_Core::instance(); 

        $migrated_count = $manager->twae_run_migration();
        if ($migrated_count > 0) {
            $message = "Migration completed successfully!";
        } else {
            $message = "No Free widgets found to migrate or already migrated.";
        }

        if ( class_exists('\Elementor\Plugin') ) {

            $plugin = \Elementor\Plugin::instance();

            if ( isset($plugin->files_manager) ) {
                $plugin->files_manager->clear_cache();

                if ( method_exists($plugin->files_manager, 'clear_fonts_cache') ) {
                    $plugin->files_manager->clear_fonts_cache();
                }
            }

            if ( method_exists($plugin, 'clear_cache') ) {
                $plugin->clear_cache();
            }
        }

        wp_send_json_success([
            'message' => $message,
            'migrated_count' => $migrated_count
        ]);
    }
}
TWE_Migration_Notice_Manager::instance();


