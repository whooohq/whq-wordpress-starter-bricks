<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Toggle extends Element {
	public $category = 'general';
	public $name     = 'toggle';
	public $icon     = 'ti-hand-point-up';
	public $scripts  = [ 'bricksToggle' ];

	public function get_label() {
		return esc_html__( 'Toggle', 'bricks' );
	}

	public function get_keywords() {
		return [ 'menu', 'mobile' ];
	}

	public function set_controls() {
		$this->controls['icon'] = [
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['iconColor'] = [
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'required' => [ 'icon.icon', '!=', '' ],
			'css'      => [
				[
					'property' => 'color',
				],
				[
					'property' => 'fill',
				],
			],
		];

		$this->controls['iconSize'] = [
			'label'    => esc_html__( 'Size', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'required' => [ 'icon.icon', '!=', '' ],
			'css'      => [
				[
					'property' => 'font-size',
				],
			],
		];

		$this->controls['animation'] = [
			'label'       => esc_html__( 'Animation', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'options'     => [
				'boring'      => 'close (x)',
				'arrow'       => 'arrow (<-)',
				'arrow-r'     => 'arrow-r (->)',
				'arrowalt'    => 'arrowalt',
				'arrowalt-r'  => 'arrowalt-r',
				'arrowturn'   => 'arrowturn',
				'arrowturn-r' => 'arrowturn-r',
				'minus'       => 'minus (-)',
				'spin'        => 'spin',
				'spring'      => 'spring',
				'squeeze'     => 'squeeze',
				'vortex'      => 'vortex',
			],
			'required'    => [ 'icon', '=', '' ],
		];

		$this->controls['ariaLabel'] = [
			'label'       => 'aria-label',
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Open', 'bricks' ),
		];

		// TOGGLE

		$this->controls['toggleSeparator'] = [
			'label'       => esc_html__( 'Toggle', 'bricks' ),
			'type'        => 'separator',
			'description' => esc_html__( 'Copy the element ID you want to toggle and paste it into the "CSS selector" setting below.', 'bricks' ),
		];

		$this->controls['toggleSelector'] = [
			'label'       => esc_html__( 'CSS selector', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => '.brxe-offcanvas',
		];

		$this->controls['toggleAttribute'] = [
			'label'       => esc_html__( 'Attribute', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 'class',
		];

		$this->controls['toggleValue'] = [
			'label'       => esc_html__( 'Value', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 'brx-open',
		];

		// BAR
		$this->controls['barSeparator'] = [
			'required' => [ 'icon', '=', '' ],
			'label'    => esc_html__( 'Bar', 'bricks' ),
			'type'     => 'separator',
		];

		$this->controls['barScale'] = [
			'required'    => [ 'icon', '=', '' ],
			'label'       => esc_html__( 'Scale', 'bricks' ),
			'type'        => 'number',
			'step'        => '0.1',
			'placeholder' => 1,
			'css'         => [
				[
					'property' => '--brxe-toggle-scale',
					'selector' => '',
				],
			],
		];

		$this->controls['barHeight'] = [
			'required'    => [ 'icon', '=', '' ],
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '4px',
			'css'         => [
				[
					'property' => '--brxe-toggle-bar-height',
					'selector' => '.brxa-inner',
				],
			],
		];

		$this->controls['barRadius'] = [
			'required'    => [ 'icon', '=', '' ],
			'label'       => esc_html__( 'Radius', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '4px',
			'css'         => [
				[
					'property' => '--brxe-toggle-bar-radius',
					'selector' => '.brxa-inner',
				],
			],
		];

		$this->controls['barColor'] = [
			'required' => [ 'icon', '=', '' ],
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.brxa-inner',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		$this->set_attribute( '_root', 'aria-label', ! empty( $settings['ariaLabel'] ) ? esc_attr( $settings['ariaLabel'] ) : esc_html__( 'Open', 'bricks' ) );

		$this->set_attribute( '_root', 'aria-expanded', 'false' );

		if ( ! empty( $settings['animation'] ) ) {
			$this->set_attribute( '_root', 'class', sanitize_html_class( 'brxa--' . $settings['animation'] ) );
		}

		if ( ! empty( $settings['toggleSelector'] ) ) {
			$this->set_attribute( '_root', 'data-selector', esc_attr( $settings['toggleSelector'] ) );
		}

		if ( ! empty( $settings['toggleAttribute'] ) ) {
			$this->set_attribute( '_root', 'data-attribute', esc_attr( $settings['toggleAttribute'] ) );
		}

		if ( ! empty( $settings['toggleValue'] ) ) {
			$this->set_attribute( '_root', 'data-value', esc_attr( $settings['toggleValue'] ) );
		}

		$output = "<button {$this->render_attributes( '_root' )}>";

		// Use custom icon instead of default line bars
		$icon = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'], [] ) : false;

		if ( $icon ) {
			$output .= $icon;
		} else {
			$output .= '<span class="brxa-wrap">';
			$output .= '<span class="brxa-inner"></span>';
			$output .= '</span>';
		}

		$output .= '</button>';

		echo $output;
	}
}
