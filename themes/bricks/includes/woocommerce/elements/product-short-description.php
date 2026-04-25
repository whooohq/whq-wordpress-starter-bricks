<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Short_Description extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-short-description';
	public $icon     = 'ti-paragraph';

	public function get_label() {
		return esc_html__( 'Product short description', 'bricks' );
	}

	public function set_controls() {
		$edit_link = Helpers::get_preview_post_link( get_the_ID() );
		$label     = esc_html__( 'Edit product short description in WordPress.', 'bricks' );

		$this->controls['info'] = [
			'tab'     => 'content',
			'type'    => 'info',
			'content' => $edit_link ? '<a href="' . esc_url( $edit_link ) . '" target="_blank">' . $label . '</a>' : $label,
		];
	}

	public function render() {
		global $post;
		$post = get_post( $this->post_id );

		if ( empty( $post ) || $post->post_type !== 'product' ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		wc_get_template( 'single-product/short-description.php' );

		echo '</div>';
	}
}
