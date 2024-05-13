<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Reading_Time extends Element {
	public $category = 'single';
	public $name     = 'post-reading-time';
	public $icon     = 'ti-time';
	public $scripts  = [ 'bricksPostReadingTime' ];

	public function get_label() {
		return esc_html__( 'Reading time', 'bricks' );
	}

	public function set_controls() {
		$this->controls['contentSelector'] = [
			'label'       => esc_html__( 'Content selector', 'bricks' ),
			'type'        => 'text',
			'placeholder' => '.brxe-post-content',
			'description' => esc_html__( 'Fallback', 'bricks' ) . ': #brx-content',
		];

		$this->controls['prefix'] = [
			'label'   => esc_html__( 'Prefix', 'bricks' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Reading time: ',
		];

		$this->controls['suffix'] = [
			'label'   => esc_html__( 'Suffix', 'bricks' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => ' minutes',
		];

		$this->controls['wordsPerMinute'] = [
			'label'       => esc_html__( 'Words per minutes', 'bricks' ),
			'type'        => 'number',
			'inline'      => true,
			'large'       => true,
			'placeholder' => 200,
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( ! empty( $settings['prefix'] ) ) {
			$this->set_attribute( '_root', 'data-prefix', $settings['prefix'] );
		}

		if ( ! empty( $settings['suffix'] ) ) {
			$this->set_attribute( '_root', 'data-suffix', $settings['suffix'] );
		}

		if ( ! empty( $settings['wordsPerMinute'] ) ) {
			$this->set_attribute( '_root', 'data-wpm', $settings['wordsPerMinute'] );
		}

		if ( ! empty( $settings['contentSelector'] ) ) {
			$this->set_attribute( '_root', 'data-content-selector', $settings['contentSelector'] );
		}

		echo "<div {$this->render_attributes( '_root' )}></div>";
	}
}
