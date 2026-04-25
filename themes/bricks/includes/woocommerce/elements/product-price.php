<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Price extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-price';
	public $icon     = 'ti-money';

	public function get_label() {
		return esc_html__( 'Product price', 'bricks' );
	}

	public function set_controls() {
		$this->controls['hideRegularPrice'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Hide regular price', 'bricks' ),
			'type'  => 'checkbox',
			'css'   => [
				[
					'selector' => 'del',
					'property' => 'display',
					'value'    => 'none',
				],
			],
		];

		$this->controls['regularPriceTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Regular price typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.price del, .price > span',
					'property' => 'font',
				],
			],
		];

		$this->controls['salePriceTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Sale price typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.price ins',
					'property' => 'font',
				],
			],
		];
	}

	public function render() {
		global $product;

		$product = wc_get_product( $this->post_id );

		if ( empty( $product ) ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		wc_get_template( 'single-product/price.php' );

		echo '</div>';
	}
}
