<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Counter extends Element {
	public $category = 'general';
	public $name     = 'counter';
	public $icon     = 'ti-dashboard';
	public $scripts  = [ 'bricksCounter' ];

	public function get_label() {
		return esc_html__( 'Counter', 'bricks' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-counter' );
	}

	public function set_controls() {
		$this->controls['countFrom'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Count from', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 0,
		];

		$this->controls['countTo'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Count to', 'bricks' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 1000,
		];

		$this->controls['duration'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Animation in ms', 'bricks' ),
			'type'           => 'number',
			'hasDynamicData' => true,
			'placeholder'    => 1000,
		];

		$this->controls['countTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.count',
				],
			],
		];

		// Prefix

		$this->controls['prefixSep'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Prefix', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['prefix'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Prefix', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['prefixTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.prefix',
				],
			],
		];

		// Suffix

		$this->controls['suffixSep'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Suffix', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['suffix'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Suffix', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['suffixTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.suffix',
				],
			],
		];

		// Thousand separator

		$this->controls['thousandSep'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Separator', 'bricks' ),
			'type'  => 'separator',
		];

		// Auto-set via JS: toLocaleString()
		$this->controls['thousandSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Thousand separator', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['separatorText'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Separator', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => ',',
			'required'    => [ 'thousandSeparator', '=', true ],
		];
	}

	public function render() {
		$settings       = $this->settings;
		$count_from     = ! empty( $settings['countFrom'] ) ? $this->render_dynamic_data( $settings['countFrom'] ) : 0;
		$count_to       = ! empty( $settings['countTo'] ) ? $this->render_dynamic_data( $settings['countTo'] ) : 100;
		$prefix         = ! empty( $settings['prefix'] ) ? $this->render_dynamic_data( $settings['prefix'] ) : false;
		$suffix         = ! empty( $settings['suffix'] ) ? $this->render_dynamic_data( $settings['suffix'] ) : false;
		$separator_text = ! empty( $settings['separatorText'] ) ? $this->render_dynamic_data( $settings['separatorText'] ) : '';
		$duration       = ! empty( $settings['duration'] ) ? intval( $this->render_dynamic_data( $settings['duration'] ) ) : 1000;
		$thousands      = ! empty( $settings['thousandSeparator'] ) ? $settings['thousandSeparator'] : '';

		$this->set_attribute(
			'_root',
			'data-bricks-counter-options',
			wp_json_encode(
				[
					'countFrom' => $count_from,
					'countTo'   => $count_to,
					'duration'  => $duration,
					'thousands' => $thousands,
					'separator' => esc_html( $separator_text ),
				]
			)
		);

		echo "<div {$this->render_attributes( '_root' )}>";

		if ( $prefix ) {
			echo "<span class=\"prefix\">$prefix</span>";
		}

		echo "<span class=\"count\">$count_from</span>";

		if ( $suffix ) {
			echo "<span class=\"suffix\">$suffix</span>";
		}

		echo '</div>';
	}
}
