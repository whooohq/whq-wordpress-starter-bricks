<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Products_Pagination extends Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-products-pagination';
	public $icon     = 'ti-angle-double-right';

	public function get_label() {
		return esc_html__( 'Products pagination', 'bricks' );
	}

	public function set_controls() {
		$this->controls['justifyContent'] = [
			'tab'          => 'content',
			'label'        => esc_html__( 'Alignment', 'bricks' ),
			'type'         => 'align-items',
			'exclude'      => 'stretch',
			'css'          => [
				[
					'selector' => '',
					'property' => 'align-self',
				],
			],
			'isHorizontal' => false,
			'inline'       => true,
		];

		$this->controls['prevIcon'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Previous Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['nextIcon'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Next Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['endSize'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'End Size', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'description' => esc_html__( 'How many numbers on either the start and the end list edges.', 'bricks' ),
		];

		$this->controls['midSize'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Mid Size', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'description' => esc_html__( 'How many numbers to either side of the current page.', 'bricks' ),
		];
	}

	public function render() {
		$settings = $this->settings;

		$is_archive_product = Woocommerce_Helpers::is_archive_product();

		if ( bricks_is_builder_call() || bricks_is_builder() || ! $is_archive_product ) {
			$query = Woocommerce_Helpers::get_products_element_query( $this->post_id );

			if ( ! $query ) {
				return $this->render_element_placeholder(
					[
						// translators: %s: element name
						'title' => sprintf( esc_html__( 'Element %s not found.', 'bricks' ), '"' . esc_html__( 'Products', 'bricks' ) . '"' ),
					]
				);
			}

			wc_setup_loop(
				[
					'name'         => 'bricks-products',
					'is_shortcode' => true,
					'is_search'    => false,
					'is_paginated' => true,
					'total'        => (int) $query->found_posts,
					'total_pages'  => (int) $query->max_num_pages,
					'per_page'     => (int) $query->get( 'posts_per_page' ),
					'current_page' => (int) max( 1, $query->get( 'paged', 1 ) ),
				]
			);
		}

		// Hooks
		add_filter( 'woocommerce_pagination_args', [ $this, 'woocommerce_pagination_args' ] );

		// Render
		echo "<div {$this->render_attributes( '_root' )}>";

		woocommerce_pagination();

		echo '</div>';

		// Reset hooks
		remove_filter( 'woocommerce_pagination_args', [ $this, 'woocommerce_pagination_args' ] );

		if ( bricks_is_builder_call() || bricks_is_builder() || ! $is_archive_product ) {
			wc_reset_loop();
		}
	}

	public function woocommerce_pagination_args( $args ) {
		$settings = $this->settings;

		if ( ! empty( $settings['prevIcon'] ) ) {
			$args['prev_text'] = self::render_icon( $settings['prevIcon'] );
		}

		if ( ! empty( $settings['nextIcon'] ) ) {
			$args['next_text'] = self::render_icon( $settings['nextIcon'] );
		}

		if ( ! empty( $settings['endSize'] ) ) {
			$args['end_size'] = $settings['endSize'];
		}

		if ( ! empty( $settings['midSize'] ) ) {
			$args['mid_size'] = $settings['midSize'];
		}

		return $args;
	}
}
