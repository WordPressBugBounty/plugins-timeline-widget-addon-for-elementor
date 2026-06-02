<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class TWE_Migration_Notice_Manager {

    /**
     * Cached result of legacy Vertical Timeline (be-timeline) scan: yes|no.
     *
     * @var string
     */
    private const LEGACY_WIDGET_TRANSIENT = 'twae_legacy_be_timeline_v1';

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
        add_action( 'added_post_meta', array( $this, 'twae_invalidate_legacy_scan_on_elementor_data' ), 10, 4 );
        add_action( 'updated_post_meta', array( $this, 'twae_invalidate_legacy_scan_on_elementor_data' ), 10, 4 );
        add_action( 'deleted_post_meta', array( $this, 'twae_invalidate_legacy_scan_on_elementor_data' ), 10, 4 );
        add_action('before_delete_post', array($this, 'twae_invalidate_legacy_scan_before_delete'));
        add_action('activated_plugin', array($this, 'twae_flush_legacy_timeline_cache'));
        add_action('deactivated_plugin', array($this, 'twae_flush_legacy_timeline_cache'));
    }

    /**
     * Clear cached legacy-widget detection (e.g. after migration or content changes).
     *
     * @return void
     */
    public function twae_flush_legacy_timeline_cache() {

        delete_transient( self::LEGACY_WIDGET_TRANSIENT );
    }

    /**
     * Invalidate legacy scan cache only when Elementor document data changes.
     *
     * @param int    $meta_id    Meta ID.
     * @param int    $object_id  Post ID.
     * @param string $meta_key   Meta key.
     * @param mixed  $_meta_value Meta value (unused).
     * @return void
     */
    public function twae_invalidate_legacy_scan_on_elementor_data( $meta_id, $object_id, $meta_key, $_meta_value ) {

        unset( $meta_id, $_meta_value );

        if ( '_elementor_data' !== $meta_key ) {
            return;
        }

        $post = get_post( $object_id );
        if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
            return;
        }

        if ( wp_is_post_revision( $object_id ) || wp_is_post_autosave( $object_id ) ) {
            return;
        }

        $this->twae_flush_legacy_timeline_cache();
    }

    /**
     * @param int $post_id Post ID.
     * @return void
     */
    public function twae_invalidate_legacy_scan_before_delete( $post_id ) {

        $post = get_post( $post_id );
        if ( $post instanceof WP_Post && in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
            $this->twae_flush_legacy_timeline_cache();
        }
    }

    public function twae_hide_notice() {

        check_ajax_referer( 'twae_hide_migration_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
        }

        if ( ! isset($_POST['value']) ) {
            wp_send_json_error('Missing value parameter');
        }

        $val = sanitize_key( wp_unslash( $_POST['value'] ) );
        $allowed_values = array( 'twe', 'twae' );
        if ( ! in_array( $val, $allowed_values, true ) ) {
            wp_send_json_error( 'Invalid value', 400 );
        }
        update_option( $val . '_hide_migration_notice', 'yes' );
        wp_send_json_success();
    }
    
    public function enqueue_editor_scripts() {

        wp_enqueue_script(
            'twae-migration-js',
            TWAE_URL . 'includes/migration/assets/twae-migration.js',
            array('jquery'),
            TWAE_VERSION,
            true
        );

        wp_localize_script('twae-migration-js', 'twae_migration_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('twae_migration_nonce'),
            'hide_migration_nonce'      => wp_create_nonce('twae_hide_migration_nonce'),
        ));
    }
    
    function twae_has_legacy_timeline_widgets() {

        $cached = get_transient( self::LEGACY_WIDGET_TRANSIENT );
        if ( 'yes' === $cached ) {
            return true;
        }
        if ( 'no' === $cached ) {
            return false;
        }

        $found = $this->twae_scan_posts_for_legacy_be_timeline();

        set_transient( self::LEGACY_WIDGET_TRANSIENT, $found ? 'yes' : 'no', WEEK_IN_SECONDS );

        return $found;
    }

    /**
     * Find be-timeline widgets using a narrow meta_query, then confirm via JSON walk.
     *
     * @return bool
     */
    private function twae_scan_posts_for_legacy_be_timeline() {

        $per_page = 50;
        $paged    = 1;

        while ( true ) {

            $query = new WP_Query(
                array(
                    'post_type'              => array( 'post', 'page' ),
                    'post_status'            => 'any',
                    'posts_per_page'         => $per_page,
                    'paged'                  => $paged,
                    'fields'                 => 'ids',
                    'no_found_rows'          => true,
                    'orderby'                => 'ID',
                    'order'                  => 'ASC',
                    'meta_query'             => array(
                        array(
                            'key'     => '_elementor_data',
                            'value'   => 'be-timeline',
                            'compare' => 'LIKE',
                        ),
                    ),
                )
            );

            if ( empty( $query->posts ) ) {
                wp_reset_postdata();
                break;
            }

            foreach ( $query->posts as $post_id ) {

                $raw = get_post_meta( $post_id, '_elementor_data', true );

                if ( empty( $raw ) ) {
                    continue;
                }

                $data = json_decode( $raw, true );

                if ( ! is_array( $data ) ) {
                    continue;
                }

                if ( $this->twae_search_widgets_recursive( $data ) ) {
                    wp_reset_postdata();
                    return true;
                }
            }

            if ( count( $query->posts ) < $per_page ) {
                wp_reset_postdata();
                break;
            }

            $paged++;
            wp_reset_postdata();
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

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

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
            TWAE_VERSION,
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
                <span style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="button button-primary install-eventprime" aria-label="<?php esc_attr_e( 'Run timeline migration', 'timeline-widget-addon-for-elementor' ); ?>" id="twae-run-migration" data-default-label="<?php esc_attr_e( 'Migrate Now!', 'timeline-widget-addon-for-elementor' ); ?>"><?php esc_html_e( 'Migrate Now!', 'timeline-widget-addon-for-elementor' ); ?></button>
                    <span id="twae-migration-result" class="twae-migration-result"></span>
                </span>
                <span style="margin-top: 5px;"> We noticed you’re using the <strong>Vertical Timeline Widget for Elementor.</strong>  Upgrade your existing timelines to <a href="https://cooltimeline.com/elementor-widget/free-timeline/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=demo&utm_content=migration_notice"><Strong>Timeline Widget</strong></a> by Cool Plugins for enhanced features and a more refined design experience</span>
            </div>
        </div>
        <?php
    }
    
    function twae_run_migration_callback() {

        check_ajax_referer('twae_migration_nonce', 'nonce');

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
        }

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


