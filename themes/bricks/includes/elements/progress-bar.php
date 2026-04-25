<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Progress_Bar extends Element {
	public $category     = 'general';
	public $name         = 'progress-bar';
	public $icon         = 'ti-line-double';
	public $css_selector = '.bar';
	public $scripts      = [ 'bricksProgressBar' ];

	public function get_label() {
		return esc_html__( 'Progress Bar', 'bricks' );
	}

	public function set_controls() {
		$this->controls['_padding']['css'][0]['selector'] = '';

		// Group: 'bars'

		$this->controls['bars'] = [
			'tab'         => 'content',
			'type'        => 'repeater',
			'placeholder' => esc_html__( 'Bar', 'bricks' ),
			'selector'    => '.bar-wrapper',
			'fields'      => [
				'title'      => [
					'label' => esc_html__( 'Label', 'bricks' ),
					'type'  => 'text',
				],

				'percentage' => [
					'label'          => esc_html__( 'Percentage', 'bricks' ),
					'type'           => 'number',
					'min'            => 0,
					'max'            => 100,
					'step'           => 1,
					'hasDynamicData' => 'text',
				],
				'color'      => [
					'label' => esc_html__( 'Bar color', 'bricks' ),
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
							'selector' => '.bar span',
						],
					],
				],
			],
			'default'     => [
				[
					'title'      => esc_html__( 'Web design', 'bricks' ),
					'percentage' => 80,
				],
				[
					'title'      => esc_html__( 'SEO', 'bricks' ),
					'percentage' => 90,
				],
			],
			'rerender'    => true,
		];

		// SETTINGS

		$this->controls['height'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
				]
			],
			'placeholder' => 8,
		];

		$this->controls['barSpacing'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '',
				]
			],
			'placeholder' => 20,
		];

		$this->controls['showPercentage'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Show percentage', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['barColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Bar color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bar span',
				],
			],
		];

		$this->controls['barBackgroundColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Bar background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bar',
				],
			],
		];

		$this->controls['barBorder'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Bar border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bar',
				],
			],
		];

		$this->controls['labelTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Label typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.label',
				],
			],
		];

		$this->controls['percentageTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Percentage typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.percentage',
				],
			],
			'required' => [ 'showPercentage', '=', true ],
		];
	}

	public function render() {
		if ( empty( $this->settings['bars'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No progress bar created.', 'bricks' ),
				]
			);
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		foreach ( $this->settings['bars'] as $index => $bar ) {
			$percentage = isset( $bar['percentage'] ) ? $this->render_dynamic_data( $bar['percentage'] ) : 0;
			$percentage = $percentage ? $percentage : 0;

			$this->set_attribute( "bar-inner-{$index}", 'data-width', "{$percentage}%" );

			echo '<div class="bar-wrapper">';

			echo '<label>';

			$title = isset( $bar['title'] ) ? $this->render_dynamic_data( $bar['title'] ) : null;

			if ( $title ) {
				echo '<span class="label">' . $title . '</span>';
			}

			if ( isset( $this->settings['showPercentage'] ) ) {
				echo '<span class="percentage">' . $percentage . '%</span>';
			}

			echo '</label>';

			echo '<div class="bar"><span ' . $this->render_attributes( "bar-inner-{$index}" ) . '></span></div>';

			echo '</div>';
		}

		echo '</div>';
	}
}
