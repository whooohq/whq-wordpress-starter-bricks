<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Products_Archive_Description extends Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-products-archive-description';
	public $icon     = 'ti-wordpress';

	public function get_label() {
		return esc_html__( 'Products archive description', 'bricks' );
	}

	public function set_controls() {
		$shop_page_link = get_edit_post_link( wc_get_page_id( 'shop' ) );

		$this->controls['info'] = [
			'tab'     => 'content',
			'type'    => 'info',
			// translators: %1$s: link to shop page, %2$s: link to shop page
			'content' => sprintf( esc_html__( 'Follow this %1$slink%2$s to edit the product archive description or edit the product category/tag descriptions', 'bricks' ), '<a href="' . $shop_page_link . '" target="_blank">', '</a>' ),
		];

		$this->controls['info2'] = [
			'tab'         => 'content',
			'type'        => 'info',
			'description' => esc_html__( 'For product category or product tag archive descriptions edit each term description.', 'bricks' ),
		];
	}

	public function render() {
		ob_start();

		// TODO: No product archive description in builder and template preview page!

		// global $wp_query;

		// wc_setup_loop( [
		// 'name'         => 'bricks-products',
		// 'is_shortcode' => true,
		// 'is_search'    => false,
		// 'is_paginated' => true,
		// 'total'        => (int) $wp_query->found_posts,
		// 'total_pages'  => (int) $wp_query->max_num_pages,
		// 'per_page'     => (int) $wp_query->get( 'posts_per_page' ),
		// 'current_page' => (int) max( 1, $wp_query->get( 'paged', 1 ) ),
		// ] );

		woocommerce_taxonomy_archive_description();
		woocommerce_product_archive_description();

		$product_archive_description = ob_get_clean();

		if ( $product_archive_description ) {
			echo "<div {$this->render_attributes( '_root' )}>" . $product_archive_description . '</div>';
		} else {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No product archive description found.', 'bricks' ),
				]
			);
		}
	}
}
