<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Products_Total_Results extends Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-products-total-results';
	public $icon     = 'ti-info';

	public function get_label() {
		return esc_html__( 'Products total results', 'bricks' );
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

		echo "<div {$this->render_attributes( '_root' )}>";

		woocommerce_result_count();

		echo '</div>';

		if ( bricks_is_builder_call() || bricks_is_builder() || ! $is_archive_product ) {
			wc_reset_loop();
		}
	}
}
