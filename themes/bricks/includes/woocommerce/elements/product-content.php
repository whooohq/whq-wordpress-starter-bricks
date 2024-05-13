<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Content extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-content';
	public $icon     = 'ti-wordpress';

	public function get_label() {
		return esc_html__( 'Product content', 'bricks' );
	}

	public function set_controls() {
		$edit_link = Helpers::get_preview_post_link( get_the_ID() );
		$label     = esc_html__( 'Edit product content in WordPress.', 'bricks' );

		$this->controls['info'] = [
			'tab'     => 'content',
			'type'    => 'info',
			'content' => $edit_link ? '<a href="' . esc_url( $edit_link ) . '" target="_blank">' . $label . '</a>' : $label,
		];
	}

	public function render() {
		$settings = $this->settings;

		$product = wc_get_product( $this->post_id );

		if ( empty( $product ) ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		$content = get_post_field( 'post_content', $this->post_id );

		if ( ! $content ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Product content is empty.', 'bricks' ),
				]
			);
		}

		$content = $this->render_dynamic_data( $content );
		$content = Helpers::parse_editor_content( $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		echo "<div {$this->render_attributes( '_root' )}>" . $content . '</div>';
	}
}
