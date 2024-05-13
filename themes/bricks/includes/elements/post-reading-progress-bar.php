<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Reading_Progress_Bar extends Element {
	public $category = 'single';
	public $name     = 'post-reading-progress-bar';
	public $icon     = 'ti-line-double';
	public $scripts  = [ 'bricksPostReadingProgressBar' ];

	public function get_label() {
		return esc_html__( 'Reading progress bar', 'bricks' );
	}

	public function set_controls() {
		$this->controls['contentSelector'] = [
			'label'       => esc_html__( 'Content selector', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 'body',
		];

		$this->controls['barPosition'] = [
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'top'    => esc_html__( 'Top', 'bricks' ),
				'bottom' => esc_html__( 'Bottom', 'bricks' ),
				'custom' => esc_html__( 'Custom', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Top', 'bricks' ),
		];

		$this->controls['barHeight'] = [
			'label'       => esc_html__( 'Bar height', 'bricks' ),
			'type'        => 'number',
			'min'         => 0,
			'units'       => true,
			'large'       => true,
			'placeholder' => '12px',
			'css'         => [
				[
					'property' => 'height',
					'selector' => '',
				],
			],
		];

		$this->controls['barColor'] = [
			'label' => esc_html__( 'Bar color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '&::-webkit-progress-value',
				],
				[
					'property' => 'background-color',
					'selector' => '&::-moz-progress-bar',
				],
			],
		];

		$this->controls['barBackgroundColor'] = [
			'label' => esc_html__( 'Bar background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '&::-webkit-progress-bar',
				],
				[
					'property' => 'background-color',
					'selector' => '',
				],
			],
		];
	}

	public function render() {
		if ( ! empty( $this->settings['contentSelector'] ) ) {
			$this->set_attribute( '_root', 'data-content-selector', $this->settings['contentSelector'] );
		}

		$this->set_attribute( '_root', 'data-pos', ! empty( $this->settings['barPosition'] ) ? esc_attr( $this->settings['barPosition'] ) : 'top' );

		echo "<progress {$this->render_attributes( '_root' )} value=\"0\" max=\"100\"></progress>";
	}
}
