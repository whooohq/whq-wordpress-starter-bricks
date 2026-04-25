<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Logo extends Element {
	public $category = 'general';
	public $name     = 'logo';
	public $icon     = 'ti-home';

	public function get_label() {
		return esc_html__( 'Logo', 'bricks' );
	}

	public function get_keywords() {
		return [ 'image' ];
	}

	public function set_controls() {
		$this->controls['logo'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Logo', 'bricks' ),
			'type'        => 'image',
			'unsplash'    => false,
			'description' =>
				'<p>' . esc_html__( 'Min. dimension: Twice the value under logo height / logo width for proper display on retina devices.', 'bricks' ) . '</p>' .
				'<p>' . esc_html__( 'SVG logo: Set "Height" & "Width" in "px" value.', 'bricks' ) . '</p>',
		];

		$this->controls['logoInverse'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Logo inverse', 'bricks' ),
			'type'        => 'image',
			'unsplash'    => false,
			'description' => esc_html__( 'Use for sticky scrolling header etc.', 'bricks' ),
			'required'    => [ 'logo', '!=', '' ],
		];

		$this->controls['logoHeight'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.bricks-site-logo',
				]
			],
			'max'         => 400,
			'placeholder' => 'auto',
			'required'    => [ 'logo', '!=', '' ],
		];

		$this->controls['logoWidth'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.bricks-site-logo',
				]
			],
			'max'         => 999,
			'placeholder' => 'auto',
			'required'    => [ 'logo', '!=', '' ],
		];

		$this->controls['logoText'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Text', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'description' => esc_html__( 'Used if logo image isn\'t set or available.', 'bricks' ),
			'default'     => get_bloginfo( 'name' ),
		];

		$this->controls['logoLoading'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Loading', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'eager' => 'eager',
				'lazy'  => 'lazy',
			],
			'placeholder' => 'eager',
		];

		$this->controls['logoUrl'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Link to', 'bricks' ),
			'type'        => 'link',
			'placeholder' => esc_html__( 'Site Address', 'bricks' ),
		];
	}

	public function render() {
		$settings = $this->settings;

		// Builder: Set active templates to get template header ID for bricks logo inverse CSS classes
		if ( bricks_is_builder_call() ) {
			Database::set_active_templates( $this->post_id );
		}

		$template_header_id       = Database::$active_templates['header'];
		$template_header_settings = $template_header_id ? Helpers::get_template_settings( $template_header_id ) : [];

		$logo      = ! empty( $settings['logo'] ) ? $settings['logo'] : [];
		$logo_id   = ! empty( $logo['id'] ) ? $logo['id'] : false;
		$logo_size = ! empty( $logo['size'] ) ? $logo['size'] : 'full';
		$logo_url  = ! empty( $logo['url'] ) ? $logo['url'] : '';

		// Logo ID or URL from dynamic data (@since 1.7.2)
		if ( ! empty( $logo['useDynamicData'] ) ) {
			$logos = $this->render_dynamic_data_tag( $logo['useDynamicData'], 'image', [ 'size' => $logo_size ], $template_header_id );
			$logo  = isset( $logos[0] ) ? $logos[0] : false;

			if ( $logo ) {
				if ( is_numeric( $logo ) ) {
					$logo_id = $logo;
				} else {
					$logo_url = $logo;
				}
			}
		}

		// NOTE: Use WP function 'wp_get_attachment_image' to render image (easier responsive image implementation)
		if ( $logo_id ) {
			$image_atts['alt']   = ! empty( $settings['logoText'] ) ? esc_attr( $settings['logoText'] ) : get_bloginfo( 'name' );
			$image_atts['class'] = 'bricks-site-logo css-filter';

			// Sticky header
			if ( isset( $template_header_settings['headerSticky'] ) ) {
				$logo_image_src = wp_get_attachment_image_src( $logo_id, $logo_size );

				if ( ! empty( $logo_image_src[0] ) ) {
					$image_atts['data-bricks-logo'] = $logo_image_src[0];
				}

				// Logo inverse
				$logo_inverse_id   = ! empty( $settings['logoInverse']['id'] ) ? $settings['logoInverse']['id'] : false;
				$logo_inverse_size = ! empty( $settings['logoInverse']['size'] ) ? $settings['logoInverse']['size'] : 'full';

				if ( $logo_inverse_id ) {
					$logo_inverse_image_src = wp_get_attachment_image_src( $logo_inverse_id, $logo_inverse_size );

					if ( ! empty( $logo_inverse_image_src[0] ) ) {
						$image_atts['data-bricks-logo-inverse'] = $logo_inverse_image_src[0];
					}
				}
			}

			// Set 'loading' attribute: eager or lazy (@since 1.6.2)
			$image_atts['loading'] = ! empty( $settings['logoLoading'] ) ? $settings['logoLoading'] : 'eager';

			// Set logo dimensions explicitly: Needed when using SVG (@since 1.8.5)
			if ( isset( $settings['logoHeight'] ) && ( is_numeric( $settings['logoHeight'] ) || strpos( $settings['logoHeight'], 'px' ) !== false ) ) {
				$image_atts['height'] = intval( $settings['logoHeight'] );
			}

			if ( isset( $settings['logoWidth'] ) && ( is_numeric( $settings['logoWidth'] ) || strpos( $settings['logoWidth'], 'px' ) !== false ) ) {
				$image_atts['width'] = intval( $settings['logoWidth'] );
			}

			$logo = wp_get_attachment_image( $logo_id, $logo_size, false, $image_atts );
		}

		// External URL
		elseif ( isset( $settings['logo']['external'] ) && ! empty( $logo_url ) ) {
			$logo_url = $this->render_dynamic_data( $logo_url );

			$logo = "<img class=\"bricks-site-logo\" src=\"{$logo_url}\">";
		}

		// Logo text
		elseif ( ! empty( $settings['logoText'] ) ) {
			$logo = esc_html( $settings['logoText'] );
		}

		// Default: Site name
		else {
			$logo = get_bloginfo( 'name' );
		}

		// Link: Custom URL if provided (fallback: home_url)
		if ( ! empty( $settings['logoUrl'] ) ) {
			$this->set_link_attributes( '_root', $settings['logoUrl'] );
		} else {
			$this->set_attribute( '_root', 'href', home_url() );
		}

		echo "<a {$this->render_attributes( '_root' )}>{$logo}</a>";
	}
}
