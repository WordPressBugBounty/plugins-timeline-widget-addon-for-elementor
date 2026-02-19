<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class TWE_Migration_Core {

    private static $instance = null;

    public static function instance() {

        if ( self::$instance === null ) {

            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Convert a single free repeater item to Pro repeater format.
     *
     * @param array $free_item Free widget repeater item.
     * @return array Converted pro story item.
     */

    public function convert_free_item_to_free( $free_item ) {

        $pro_item = array();

        // Map obvious fields
        $pro_item['twae_story_title']              = isset( $free_item['list_title'] ) ? $free_item['list_title'] : '';
        $pro_item['twae_description']              = isset( $free_item['list_content'] ) ? $free_item['list_content'] : '';
        $pro_item['twae_image']                    = isset( $free_item['image'] ) ? $free_item['image'] : array( 'id' => '', 'url' => '' );
        $pro_item['twae_thumbnail_size']           = isset( $free_item['thumbnail_size'] ) ? $free_item['thumbnail_size'] : 'thumbnail';
        $pro_item['twae_thumbnail_custom_dimension']= isset( $free_item['thumbnail_custom_dimension'] ) ? $free_item['thumbnail_custom_dimension'] : '';
        //Fields not present in free widget -> defaults for Pro
        $pro_item['twae_year']                     = '';
        $pro_item['twae_date_label']               = '';
        $pro_item['twae_extra_label']              = '';
        $pro_item['twae_video_url']                = '';
        $pro_item['twae_icon_type']                = 'dot'; // icon, image, customtext, none...
        $pro_item['twae_story_icon']               = array( 'value' => 'dot' ); // object as used by elementor helpers
        $pro_item['twae_icon_text']                = '';
        $pro_item['twae_display_icon']             = '';
        $pro_item['twae_custom_story_bgcolor']     = '';
        $pro_item['twae_custom_cbox_connector_bg_color'] = '';
        $pro_item['twae_show_year_label']          = 'no';

        // Preserve the repeater _id if present so elementor shows same item id
        if ( isset( $free_item['_id'] ) ) {
            $pro_item['_id'] = $free_item['_id'];
        } else {
            $pro_item['_id'] = wp_generate_password( 8, false, false );
        }

        return $pro_item;
    }

    /**
     * Convert the whole free widget settings -> pro settings.
     *
     * @param array $free_settings Free widget settings array.
     * @return array Converted pro settings array.
     */

    public function convert_free_settings_to_free( $free_settings ) {

        $pro = array();

        $pro['twae_list'] = array();

        if ( ! empty( $free_settings['list'] ) && is_array( $free_settings['list'] ) ) {
            foreach ( $free_settings['list'] as $free_item ) {
                $pro['twae_list'][] = $this->convert_free_item_to_free( $free_item );
            }
        }
        $pro['twae_cbox_border_width'] = array(
            'top'    => 1,
            'right'  => 1,
            'bottom' => 1,
            'left'   => 1,
            'unit'   => 'px',
        );

      
        $border_color = ! empty( $free_settings['theme_color'] )
        ? $free_settings['theme_color']
        : '#d2e3f9';

        $pro['twae_cbox_border_color'] = $border_color;
        $pro['twae_icon_bgcolor']     = $border_color;

            if ( isset( $free_settings['twe_layout'] ) && $free_settings['twe_layout'] === 'one-sided' ) {
            $pro['twae_layout'] = 'one-sided';   
        } else {
            $pro['twae_layout'] = 'centered';
        }
        $pro['twae_icon_border_popover'] = 'yes';
        $pro['twae_icon_border_color']   = '#e6e6e6';
        $pro['twae_icon_boxsize'] = [
            'size' => 40,
            'unit' => 'px',
        ];

       
         $pro['twae_story_title_color'] = $free_settings['title_color'] ?? '#333333';

        if ( isset( $free_settings['content_color'] ) ) {
            $pro['twae_description_color'] = $free_settings['content_color'];
        }

        $has_free_typo = false;

        foreach ($free_settings as $k => $v) {
            if (strpos($k, 'content_typography_') === 0) {
                $has_free_typo = true;
                break;
            }
        }

        if ( $has_free_typo ) {
            $pro['twae_description_typography_typography']     = $free_settings['content_typography_typography'] ?? '';
            $pro['twae_description_typography_font_family']    = $free_settings['content_typography_font_family'] ?? '';
            $pro['twae_description_typography_font_size']      = $free_settings['content_typography_font_size'] ?? ['size'=>'','unit'=>'px'];
            $pro['twae_description_typography_font_weight']    = $free_settings['content_typography_font_weight'] ?? '';
            $pro['twae_description_typography_font_style']     = $free_settings['content_typography_font_style'] ?? '';
            $pro['twae_description_typography_line_height']    = $free_settings['content_typography_line_height'] ?? ['size'=>'','unit'=>'px'];
            $pro['twae_description_typography_letter_spacing'] = $free_settings['content_typography_letter_spacing'] ?? ['size'=>'','unit'=>'px'];
            $pro['twae_description_typography_text_transform'] = $free_settings['content_typography_text_transform'] ?? '';
            $pro['twae_description_typography_color']          = $free_settings['content_color'] ?? '';
        }
        
        if ( isset( $free_settings['button_box_shadow_box_shadow'] )
            && is_array( $free_settings['button_box_shadow_box_shadow'] ) ) {

            $bs = $free_settings['button_box_shadow_box_shadow'];

            $pro['twae_cbox_border_shadow_popover'] = 'yes';

            $pro['twae_cbox_border_shadow'] = array(
                'horizontal' => isset($bs['horizontal']) ? $bs['horizontal'] : 0,
                'vertical'   => isset($bs['vertical'])   ? $bs['vertical']   : 0,
                'blur'       => isset($bs['blur'])       ? $bs['blur']       : 0,
                'spread'     => isset($bs['spread'])     ? $bs['spread']     : 0,
                'color'      => isset($bs['color'])      ? $bs['color']      : 'rgba(0,0,0,0.3)',
            );
        }

        if ( isset( $free_settings['header_size'] ) ) {

            $tag_sizes = [
                'h1' => 38,
                'h2' => 32,
                'h3' => 28,
                'h4' => 24,
                'h5' => 20,
                'h6' => 16,
                'p'  => 16,
                'div'=> 16,
                'span'=>16,
            ];

            $tag = $free_settings['header_size'];

            if ( isset( $tag_sizes[ $tag ] ) ) {
                // Enable custom typography
                $pro['twae_title_typography_typography'] = 'custom';

                // Set the actual font size
                $pro['twae_title_typography_font_size'] = [
                    'size' => $tag_sizes[$tag],
                    'unit' => 'px',
                ];
            }
        }
        // Box / background mapping
        if ( isset( $free_settings['bg_color'] ) ) {
            $pro['twae_story_bgcolor'] = $free_settings['bg_color'];
        }
        if ( isset( $free_settings['theme_color'] ) ) {
            $pro['twae_line_color'] = $free_settings['theme_color'];
        }
        if ( isset( $free_settings['circle_color'] ) ) {
            $pro['twae_icon_bgcolor'] = $free_settings['circle_color'];
            $pro['twae_cbox_connector_bd_color']=$free_settings['circle_color'];
        }
        // Center line filling default -> disabled
        $pro['center_line_filling'] = 'no';

        return $pro;
    }

    /**
     * Recursively scan Elementor JSON structure and replace free widget with Pro widget
     *
     * @param array $elements Elementor elements array (by ref - will modify).
     * @param bool  $made_change Reference flag if changes made.
     * @return void
     */

    public function scan_and_replace_widgets( &$elements, &$made_change ) {

        if ( ! is_array( $elements ) ) {
            return;
        }

        foreach ( $elements as $key => &$element ) {

            if (isset( $element['elType'], $element['widgetType'] ) && $element['elType'] === 'widget' && $element['widgetType'] === 'be-timeline') {

                $free_settings = isset( $element['settings'] ) ? $element['settings'] : array();

                $pro_settings = $this->convert_free_settings_to_free( $free_settings );

                // Replace the widget
                $element['widgetType'] = 'timeline-widget-addon';
                $element['settings']   = $pro_settings;

                $made_change = true;
            }

            if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $this->scan_and_replace_widgets( $element['elements'], $made_change );
            }
        }
    }

    /**
     * Migrate a single post's elementor data (runs once per post).
     *
     * @param int $post_id Post ID to migrate.
     * @return bool True if migration happened, false otherwise.
     */

    public function migrate_post_elementor_data( $post_id ) {

        if ( empty( $post_id ) ) {
            return false;
        }

        $elementor_data_raw = get_post_meta( $post_id, '_elementor_data', true );

        if ( empty( $elementor_data_raw ) ) {
            return false;
        }

        $json = json_decode( $elementor_data_raw, true );
        if ( ! is_array( $json ) ) {
            return false;
        }
        
        $made_change = false;
        $this->scan_and_replace_widgets( $json, $made_change );

        if ( $made_change ) {
            // Save back changed data. Use wp_slash to preserve quotes for update_post_meta.
            update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $json ) ) );

            return true;
        }

        return false;
    }

    /**
     * Run migration over all posts/pages (keeps same function name semantics).
     *
     * @return int migrated count
     */
    public function twae_run_migration() {

        $manager = TWE_Migration_Notice_Manager::instance(); 

        if (  !$manager->twae_has_legacy_timeline_widgets() ) {
          
            return 0;
        }

        $args = array(
            'post_type'      => array( 'post', 'page' ),
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        );

        $all_posts = get_posts( $args );
        $migrated_count = 0;

        if ( ! empty( $all_posts ) ) {
            foreach ( $all_posts as $post_id ) {

                if ( $this->migrate_post_elementor_data( $post_id ) ) {
                    $migrated_count++;
                }
            }
        }

        return $migrated_count;
    }
} 

TWE_Migration_Core::instance();