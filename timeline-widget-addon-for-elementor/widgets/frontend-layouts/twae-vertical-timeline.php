<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$twae_widget_id = $this->get_id();
$twae_countItem = 1;
$twae_data      = isset( $settings['twae_list'] ) ? $settings['twae_list'] : array();

$this->add_render_attribute(
	'twae-wrapper',
	array(
		'id'    => 'twae-wrapper-' . esc_attr( $twae_widget_id ),
		'class' => array( 'twae-vertical', 'twae-wrapper ', esc_attr( $timeline_layout_wrapper ) ),
	)
);

$this->add_render_attribute(
	'twae-timeline',
	array(
		'id'    => 'twea-timeline-' . esc_attr( $twae_widget_id ),
		'class' => array( 'twae-timeline' ),
	)
);


$twae_html = '<!-- ========= Timeline Widget Addon For Elementor ' . TWAE_VERSION . ' ========= -->
<div ' . $this->get_render_attribute_string( 'twae-wrapper' ) . '>   
    <div class="twae-start"></div>    
    <div ' . $this->get_render_attribute_string( 'twae-timeline' ) . ' >';

$twae_story_loop_obj = new Twae_Story_Loop( $settings );
if ( is_array( $twae_data ) ) {
	foreach ( $twae_data as $twae_index => $twae_content ) {
		$twae_story_alignment = 'twae-story-right';
		if ( $layout == 'centered' ) {
			if ( $twae_countItem % 2 == 0 ) {
				$twae_story_alignment = 'twae-story-left';
			}
		}

		$twae_story_id = isset( $twae_content['_id'] ) ? esc_attr( $twae_content['_id'] ) : '';

		$twae_icon_type = isset( $twae_content['twae_icon_type'] ) ? esc_attr( $twae_content['twae_icon_type'] ) : 'icon';

		$twae_title_key = $this->get_repeater_setting_key( 'twae_story_title', 'twae_list', $twae_index );

		$twae_date_label_key  = $this->get_repeater_setting_key( 'twae_date_label', 'twae_list', $twae_index );
		$twae_sub_label_key   = $this->get_repeater_setting_key( 'twae_extra_label', 'twae_list', $twae_index );
		$twae_description_key = $this->get_repeater_setting_key( 'twae_description', 'twae_list', $twae_index );

		$this->add_inline_editing_attributes( $twae_title_key, 'none' );
		$this->add_inline_editing_attributes( $twae_date_label_key, 'none' );
		$this->add_inline_editing_attributes( $twae_sub_label_key, 'none' );
		$this->add_inline_editing_attributes( $twae_description_key, 'advanced' );

		$this->add_render_attribute( $twae_title_key, array( 'class' => esc_attr( 'twae-title' ) ) );
		$this->add_render_attribute( $twae_date_label_key, array( 'class' => esc_attr( 'twae-label-big' ) ) );
		$this->add_render_attribute( $twae_sub_label_key, array( 'class' => esc_attr( 'twae-label-small' ) ) );
		$this->add_render_attribute( $twae_description_key, array( 'class' => esc_attr( 'twae-description' ) ) );

		$twae_article_key = 'twae-article-' . esc_attr( $twae_story_id );

		$this->add_render_attribute(
			$twae_article_key,
			array(
				'id'    => 'story-' . esc_attr( $twae_story_id ),
				'class' => array(
					esc_html( 'twae-story' ),
					esc_html( 'twae-repeater-item' ),
					esc_attr( $twae_story_alignment ),
					'dot' === $twae_icon_type ? esc_html( 'twae-story-no-icon' ) : esc_html( 'twae-story-no-icon' ),
				),
			)
		);

		$twae_repeater_attributes = array(
			$twae_article_key     => $this->get_render_attribute_string( $twae_article_key ),
			$twae_title_key       => $this->get_render_attribute_string( $twae_title_key ),
			$twae_date_label_key  => $this->get_render_attribute_string( $twae_date_label_key ),
			$twae_sub_label_key   => $this->get_render_attribute_string( $twae_sub_label_key ),
			$twae_description_key => $this->get_render_attribute_string( $twae_description_key ),
		);

		$twae_repeater_key = array(
			'article_key'    => $twae_article_key,
			'title_key'      => $twae_title_key,
			'date_label_key' => $twae_date_label_key,
			'sublabel_key'   => $twae_sub_label_key,
			'desc_key'       => $twae_description_key,
		);

		$twae_html .= $twae_story_loop_obj->twae_story_loop( $twae_content, $twae_repeater_key, $twae_repeater_attributes );

		$twae_countItem = $twae_countItem + 1;
	}
}

$twae_story_styles .= $twae_story_loop_obj->twae_story_style();

$twae_html .= '</div>
    <div class="twae-end"></div>
    </div>';
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo  $twae_html ;
