<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Products_Orderby extends Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-products-orderby';
	public $icon     = 'ti-exchange-vertical';

	public function get_label() {
		return esc_html__( 'Products orderby', 'bricks' );
	}

	public function set_controls() {
		$this->controls['orderby'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Order by', 'bricks' ),
			'type'        => 'select',
			'options'     => Woocommerce_Helpers::get_default_orderby_control_options(),
			'multiple'    => true,
			'description' => esc_html__( 'Leave empty to use WooCommerce default list.', 'bricks' )
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

		add_filter( 'woocommerce_catalog_orderby', [ $this, 'woocommerce_catalog_orderby' ] );

		echo "<div {$this->render_attributes( '_root' )}>";

		woocommerce_catalog_ordering();

		echo '</div>';

		remove_filter( 'woocommerce_catalog_orderby', [ $this, 'woocommerce_catalog_orderby' ] );

		if ( bricks_is_builder_call() || bricks_is_builder() || ! $is_archive_product ) {
			wc_reset_loop();
		}
	}

	public function woocommerce_catalog_orderby( $orderby ) {
		// Check: Show user-selected sort by options
		if ( ! empty( $this->settings['orderby'] ) ) {
			$orderby = array_intersect_key( Woocommerce_Helpers::get_default_orderby_control_options(), array_fill_keys( $this->settings['orderby'], '' ) );
		}

		return $orderby;
	}
}
