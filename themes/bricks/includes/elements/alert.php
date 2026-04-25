<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Alert extends Element {
	public $category = 'general';
	public $name     = 'alert';
	public $icon     = 'ti-alert';

	public function get_label() {
		return esc_html__( 'Alert', 'bricks' );
	}

	public function set_controls() {
		$this->controls['content'] = [
			'tab'     => 'content',
			'type'    => 'editor',
			'default' => esc_html__( 'I am an alert.', 'bricks' ),
		];

		$this->controls['type'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Type', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'info'    => esc_html__( 'Info', 'bricks' ),
				'success' => esc_html__( 'Success', 'bricks' ),
				'warning' => esc_html__( 'Warning', 'bricks' ),
				'danger'  => esc_html__( 'Danger', 'bricks' ),
				'muted'   => esc_html__( 'Muted', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'default'     => 'info',
		];

		$this->controls['dismissable'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Dismissable', 'bricks' ),
			'type'  => 'checkbox',
		];
	}

	public function render() {
		$classes   = [ 'alert' ];
		$close_svg = '';

		if ( ! empty( $this->settings['type'] ) ) {
			$classes[] = $this->settings['type'];
		}

		if ( isset( $this->settings['dismissable'] ) ) {
			$close_svg = Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/close.svg' );
			$classes[] = 'dismissable';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( 'content', 'class', [ 'content' ] );

		$output = "<div {$this->render_attributes( '_root' )}>";

		$content = ! empty( $this->settings['content'] ) ? $this->settings['content'] : false;

		if ( $content ) {
			$content = $this->render_dynamic_data( $content );

			$output .= "<div {$this->render_attributes( 'content' )}>" . Helpers::parse_editor_content( $content ) . '</div>';
		}

		if ( $close_svg ) {
			$output .= $close_svg;
		}

		$output .= '</div>';

		echo $output;
	}
}
