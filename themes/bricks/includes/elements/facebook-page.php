<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Facebook_Page extends Element {
	public $category  = 'general';
	public $name      = 'facebook-page';
	public $icon      = 'ti-facebook';
	public $scripts   = [ 'bricksFacebookSDK' ];
	public $draggable = false;

	public function get_label() {
		return esc_html__( 'Facebook Page', 'bricks' );
	}

	public function set_controls() {
		$this->controls['href'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Facebook page URL', 'bricks' ),
			'type'        => 'text',
			// 'trigger'     => [ 'blur', 'enter' ],
			'placeholder' => 'https://facebook.com/facebook',
			'default'     => 'https://facebook.com/facebook',
			'rerender'    => true,
		];

		$this->controls['height'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Height', 'bricks' ) . ' (px)',
			'info'        => esc_html__( 'Min. height is 70.', 'bricks' ),
			'type'        => 'number',
			'min'         => 70,
			'max'         => 500,
			'placeholder' => 500,
			'rerender'    => true,
		];

		$this->controls['width'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Width', 'bricks' ) . ' (px)',
			'info'        => esc_html__( 'Enter width between 180 and 500.', 'bricks' ),
			'type'        => 'number',
			'min'         => 180,
			'max'         => 500,
			'placeholder' => 340,
			'rerender'    => true,
		];

		$this->controls['tabs'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Tabs', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'timeline' => esc_html__( 'Timeline', 'bricks' ),
				'events'   => esc_html__( 'Events', 'bricks' ),
				'messages' => esc_html__( 'Messages', 'bricks' ),
			],
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'multiple'    => true,
			'rerender'    => true,
		];

		$this->controls['hideCover'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Hide cover', 'bricks' ),
			'type'     => 'checkbox',
			'rerender' => true,
		];

		$this->controls['profilePhotos'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Show friends\' photos', 'bricks' ),
			'type'     => 'checkbox',
			'rerender' => true,
		];

		$this->controls['hideCta'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Hide CTA button', 'bricks' ),
			'type'     => 'checkbox',
			'rerender' => true,
		];

		$this->controls['smallHeader'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Small header', 'bricks' ),
			'type'     => 'checkbox',
			'rerender' => true,
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( empty( $settings['href'] ) ) {
			return $this->render_element_placeholder( [ 'title' => esc_html__( 'No Facebook page URL provided.', 'bricks' ) ] );
		}

		// https://developers.facebook.com/docs/plugins/page-plugin/
		$this->set_attribute( 'widget', 'class', [ 'fb-page' ] );
		$this->set_attribute( 'widget', 'data-href', $settings['href'] );

		if ( ! empty( $settings['width'] ) ) {
			$this->set_attribute( 'widget', 'data-width', $settings['width'] );
		}

		if ( ! empty( $settings['height'] ) ) {
			$this->set_attribute( 'widget', 'data-height', $settings['height'] );
		}

		$this->set_attribute( 'widget', 'data-tabs', isset( $settings['tabs'] ) ? join( ',', $settings['tabs'] ) : '' );
		$this->set_attribute( 'widget', 'data-hide-cover', isset( $settings['hideCover'] ) ? 'true' : 'false' );
		$this->set_attribute( 'widget', 'data-show-facepile', isset( $settings['profilePhotos'] ) ? 'true' : 'false' );
		$this->set_attribute( 'widget', 'data-hide-cta', isset( $settings['hideCta'] ) ? 'true' : 'false' );
		$this->set_attribute( 'widget', 'data-small-header', isset( $settings['smallHeader'] ) ? 'true' : 'false' );

		echo "<div {$this->render_attributes( '_root' )}>";
		echo "<div {$this->render_attributes( 'widget' )}></div>";
		echo '</div>';
	}
}
