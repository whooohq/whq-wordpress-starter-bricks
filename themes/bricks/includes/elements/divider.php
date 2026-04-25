<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Divider extends Element {
	public $category = 'general';
	public $name     = 'divider';
	public $icon     = 'ti-layout-line-solid';

	public function get_label() {
		return esc_html__( 'Divider', 'bricks' );
	}

	public function set_controls() {
		unset( $this->controls['_alignSelf'] );

		$this->controls['height'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => 1,
			'css'         => [
				[
					'selector' => '.line',
					'property' => 'height',
				],
				[
					'selector' => '&.horizontal .line',
					'property' => 'border-top-width',
				],
			],
		];

		$this->controls['width'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'selector' => '&.horizontal .line',
					'property' => 'width',
				],
				[
					'selector' => '&.vertical .line',
					'property' => 'border-right-width',
				],
			],
		];

		$this->controls['style'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['borderStyle'],
			'css'         => [
				[
					'property' => 'border-top-style',
					'selector' => '&.horizontal .line',
				],
				[
					'property' => 'border-right-style',
					'selector' => '&.vertical .line',
				],
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Solid', 'bricks' ),
		];

		$this->controls['direction'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Direction', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'horizontal' => esc_html__( 'Horizontal', 'bricks' ),
				'vertical'   => esc_html__( 'Vertical', 'bricks' ),
			],
			'inline'      => true,
			'rerender'    => true,
			'placeholder' => esc_html__( 'Horizontal', 'bricks' ),
		];

		$this->controls['justifyContent'] = [
			'tab'       => 'content',
			'label'     => esc_html__( 'Align', 'bricks' ),
			'type'      => 'justify-content',
			'css'       => [
				[
					'selector' => '&.horizontal',
					'property' => 'justify-content',
				],
				[
					'selector' => '&.vertical',
					'property' => 'align-self',
				],
			],
			'inline'    => true,
			'direction' => 'row',
			'exclude'   => 'space',
		];

		$this->controls['color'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-top-color',
					'selector' => '&.horizontal .line',
				],
				[
					'property' => 'border-right-color',
					'selector' => '&.vertical .line',
				],
				[
					'property' => 'color',
					'selector' => '.icon',
				],
			],
		];

		// Icon

		$this->controls['iconSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['icon'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['iconTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.icon',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconAlignItems'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Align', 'bricks' ),
			'type'     => 'align-items',
			'exclude'  => 'stretch',
			'inline'   => true,
			'css'      => [
				[
					'selector' => '',
					'property' => 'align-items',
				],
			],
			'required' => [ 'icon', '!=', '' ],
		];

		$this->controls['iconPosition'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'left'   => esc_html__( 'Start', 'bricks' ),
				'center' => esc_html__( 'Center', 'bricks' ),
				'right'  => esc_html__( 'End', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Center', 'bricks' ),
			'required'    => [ 'icon', '!=', '' ],
		];

		$this->controls['iconSpacing'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
				],
			],
			'placeholder' => 30,
			'required'    => [ 'icon', '!=', '' ],
		];

		$this->controls['link'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Link', 'bricks' ),
			'type'     => 'link',
			'required' => [ 'icon', '!=', '' ],
		];

	}

	public function render() {
		$settings = $this->settings;

		// Direction (@since 1.4)
		$this->set_attribute( '_root', 'class', ! empty( $settings['direction'] ) ? $settings['direction'] : 'horizontal' );

		// Icon
		$icon = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'], [ 'icon' ] ) : false;

		// Render
		$output = "<div {$this->render_attributes( '_root' )}>";

		if ( ! $icon || ! isset( $settings['iconPosition'] ) || $settings['iconPosition'] !== 'left' ) {
			$output .= '<div class="line"></div>';
		}

		if ( $icon ) {
			if ( ! empty( $settings['link'] ) ) {
				$this->set_link_attributes( 'a', $settings['link'] );

				$output .= "<a {$this->render_attributes( 'a' )}>";
			}

			$output .= $icon;

			if ( ! empty( $settings['link'] ) ) {
				$output .= '</a>';
			}

			if ( $icon && ( ! isset( $settings['iconPosition'] ) || $settings['iconPosition'] !== 'right' ) ) {
				$output .= '<div class="line"></div>';
			}
		}

		$output .= '</div>';

		echo $output;
	}
}
