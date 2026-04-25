<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Meta extends Element {
	public $category = 'single';
	public $name     = 'post-meta';
	public $icon     = 'ti-receipt';

	public function get_label() {
		return esc_html__( 'Meta Data', 'bricks' );
	}

	public function set_controls() {
		$this->controls['meta'] = [
			'tab'           => 'content',
			'type'          => 'repeater',
			'titleProperty' => 'dynamicData',
			'placeholder'   => esc_html__( 'Meta', 'bricks' ),
			'fields'        => [
				'dynamicData' => [
					'label' => esc_html__( 'Dynamic data', 'bricks' ),
					'type'  => 'text',
				],
			],
			'default'       => [
				[
					'dynamicData' => '{author_name}',
					'id'          => Helpers::generate_random_id( false ),
				],
				[
					'dynamicData' => '{post_date}',
					'id'          => Helpers::generate_random_id( false ),
				],
				[
					'dynamicData' => '{post_comments}',
					'id'          => Helpers::generate_random_id( false ),
				],
			],
		];

		$this->controls['direction'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Direction', 'bricks' ),
			'type'   => 'direction',
			'css'    => [
				[
					'property' => 'flex-direction',
					'selector' => '',
				],
			],
			'inline' => true,
		];

		$this->controls['gutter'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Gap', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '',
				],
			],
			'placeholder' => 20,
		];

		$this->controls['separator'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Separator', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'small'          => true,
			'hasDynamicData' => false,
		];

		$this->controls['separatorColor'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Separator color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.separator',
				],
			],
			'required' => [ 'separator', '!=', '' ],
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( empty( $settings['meta'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No meta data selected.', 'bricks' ),
				]
			);
		}

		global $post;

		$post_id   = $this->post_id;
		$post      = get_post( $post_id );
		$meta_data = [];

		foreach ( $settings['meta'] as $index => $meta ) {
			$meta_html = '<span class="item">';

			if ( ! empty( $meta['dynamicData'] ) ) {
				$meta_html .= bricks_render_dynamic_data( $meta['dynamicData'], $post_id );
			}

			$meta_html .= '</span>';

			$meta_data[] = $meta_html;
		}

		$this->set_attribute( '_root', 'class', 'post-meta' );

		echo "<div {$this->render_attributes( '_root' )}>";

		$separator = isset( $settings['separator'] ) ? '<span class="separator">' . $settings['separator'] . '</span>' : '';

		echo join( $separator, $meta_data );

		echo '</div>';
	}
}
