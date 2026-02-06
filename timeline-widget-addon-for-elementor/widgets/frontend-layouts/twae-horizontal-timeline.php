<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$twae_widget_id   = $this->get_id();
$twae_isRTL       = is_rtl();
$twae_dir         = $twae_isRTL ? 'rtl' : '';
$twae_data        = isset( $settings['twae_list'] ) ? $settings['twae_list'] : array();
$twae_sidesToShow = isset( $settings['twae_slides_to_show'] ) && ! empty( $settings['twae_slides_to_show'] ) ? $settings['twae_slides_to_show'] : 2;
$twae_sidesHeight = isset( $settings['twae_slides_height'] ) ? $settings['twae_slides_height'] : 'no-height';
$twae_autoplay    = isset( $settings['twae_autoplay'] ) ? $settings['twae_autoplay'] : 'false';

$this->add_render_attribute(
	'twae-wrapper',
	array(
		'id'    => 'twae-wrapper-' . esc_attr( $twae_widget_id ),
		'class' => array( 'twae-wrapper', esc_attr( $timeline_layout_wrapper ) ),
	)
);

$this->add_render_attribute(
	'twae-slider-container',
	array(
		'id'                => 'twae-slider-container-' . esc_attr( $twae_widget_id ),
		'data-dir'          => esc_attr( $twae_dir ),
		'data-slidestoshow' => esc_attr( $twae_sidesToShow ),
		'data-autoplay'     => esc_attr( $twae_autoplay ),
		'data-auto-height'  => $twae_sidesHeight === 'no-height' ? 'true' : 'false',
		'class'             => array( 'twae-slider-container', 'swiper-container' ),
	)
);


	$twae_story_loop_obj = new Twae_Story_Loop( $settings );
		// Default Style
	$twae_html = '<!-- ========= Timeline Widget  Addon For Elementor ' . TWAE_VERSION . ' ========= -->
<div ' . $this->get_render_attribute_string( 'twae-wrapper' ) . '>
<div class="twae-wrapper-inside">
 <div ' . $this->get_render_attribute_string( 'twae-slider-container' ) . '>
 <div  class="twae-slider-wrapper swiper-wrapper ' . esc_attr( $twae_sidesHeight ) . '">';

if ( is_array( $twae_data ) ) {
	foreach ( $twae_data as $twae_index => $twae_content ) {

		$twae_story_id             = $twae_content['_id'];
		$twae_icon_type            = isset( $twae_content['twae_icon_type'] ) ? $twae_content['twae_icon_type'] : 'icon';

		$this->add_render_attribute( 'twae_story_title', array( 'class' => esc_html( 'twae-title' ) ) );
		$this->add_render_attribute( 'twae_date_label', array( 'class' => esc_html( 'twae-label-big' ) ) );
		$this->add_render_attribute( 'twae_extra_label', array( 'class' => esc_html( 'twae-label-small' ) ) );
		$this->add_render_attribute( 'twae_description', array( 'class' => esc_html( 'twae-description' ) ) );

		$twae_article_key = 'twae-article-' . esc_attr( $twae_story_id );

		$this->add_render_attribute(
			$twae_article_key,
			array(
				'id'    => esc_attr( $twae_article_key ),
				'class' => array(
					esc_html( 'twae-repeater-item' ),
					esc_html( 'twae-story' ),
					esc_html( 'swiper-slide' ),
					'dot' === $twae_icon_type ? esc_html( 'twae-story-no-icon' ) : esc_html( 'twae-story-no-icon' ),
				),
			)
		);

		$twae_repeater_attributes = array(
			$twae_article_key       => $this->get_render_attribute_string( $twae_article_key ),
			'twae_story_title' => $this->get_render_attribute_string( 'twae_story_title' ),
			'twae_date_label'  => $this->get_render_attribute_string( 'twae_date_label' ),
			'twae_extra_label' => $this->get_render_attribute_string( 'twae_extra_label' ),
			'twae_description' => $this->get_render_attribute_string( 'twae_description' ),
		);

		$twae_repeater_key = array(
			'article_key'    => $twae_article_key,
			'title_key'      => 'twae_story_title',
			'date_label_key' => 'twae_date_label',
			'sublabel_key'   => 'twae_extra_label',
			'desc_key'       => 'twae_description',
		);

		$twae_html .= $twae_story_loop_obj->twae_story_loop( $twae_content, $twae_repeater_key, $twae_repeater_attributes );
	}
}

$twae_story_styles .= $twae_story_loop_obj->twae_story_style();

$twae_html .= '</div></div></div>';
$twae_html .= ' <!-- Add Arrows -->
       <div class="twae-button-prev"><i class="fas fa-chevron-left"></i></div>
       <div class="twae-button-next"><i class="fas fa-chevron-right"></i></div>
       <div class="twae-h-line"></div>
       <div class="twae-line-fill"></div>
    </div>';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $twae_html ;




