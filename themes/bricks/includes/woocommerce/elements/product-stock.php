<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Stock extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-stock';
	public $icon     = 'ti-package';

	public function get_label() {
		return esc_html__( 'Product stock', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['inStock'] = [
			'title' => esc_html__( 'In Stock', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['lowStock'] = [
			'title' => esc_html__( 'Low Stock / On backorder', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['outOfStock'] = [
			'title' => esc_html__( 'Out of Stock', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// In Stock

		$this->controls['inStockText'] = [
			'tab'            => 'content',
			'group'          => 'inStock',
			'type'           => 'text',
			'hasDynamicData' => 'text',
			'placeholder'    => esc_html__( 'Custom text', 'bricks' ),
		];

		$this->controls['inStockTypography'] = [
			'tab'   => 'content',
			'group' => 'inStock',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.in-stock',
				],
			],
		];

		$this->controls['inStockBackgroundColor'] = [
			'tab'   => 'style',
			'group' => 'inStock',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.in-stock',
				]
			],
		];

		// Low Stock

		$this->controls['lowStockText'] = [
			'tab'            => 'content',
			'group'          => 'lowStock',
			'type'           => 'text',
			'hasDynamicData' => 'text',
			'placeholder'    => esc_html__( 'Custom text', 'bricks' ),
		];

		$this->controls['lowStockTypography'] = [
			'tab'   => 'content',
			'group' => 'lowStock',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.low-stock, .available-on-backorder',
				],
			],
		];

		$this->controls['lowStockBackgroundColor'] = [
			'tab'   => 'style',
			'group' => 'lowStock',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.low-stock, .available-on-backorder',
				]
			],
		];

		// Out of Stock

		$this->controls['outOfStockText'] = [
			'tab'            => 'content',
			'group'          => 'outOfStock',
			'type'           => 'text',
			'hasDynamicData' => 'text',
			'placeholder'    => esc_html__( 'Custom text', 'bricks' ),
		];

		$this->controls['outOfStockTypography'] = [
			'tab'   => 'content',
			'group' => 'outOfStock',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.out-of-stock',
				],
			],
		];

		$this->controls['outOfStockBackgroundColor'] = [
			'tab'   => 'style',
			'group' => 'outOfStock',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.out-of-stock',
				]
			],
		];
	}

	public function render() {
		$settings = $this->settings;

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

		add_filter( 'woocommerce_get_availability', [ $this, 'woocommerce_get_availability' ], 10, 2 );

		$stock_html = wc_get_stock_html( $product );

		if ( ! $stock_html ) {
			remove_filter( 'woocommerce_get_availability', [ $this, 'woocommerce_get_availability' ], 10, 2 );

			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Stock management not enabled for this product.', 'bricks' ),
				]
			);
		}

		echo "<div {$this->render_attributes( '_root' )}>" . $stock_html . '</div>';

		remove_filter( 'woocommerce_get_availability', [ $this, 'woocommerce_get_availability' ], 10, 2 );
	}

	public function woocommerce_get_availability( $availability, $product ) {
		$settings        = $this->settings;
		$stock_quantity  = $product->get_stock_quantity();
		$is_manage_stock = $product->managing_stock();

		// Low stock amount should consider product level and WC Low stock threshold (@since 1.8.3)
		$product_low_stock_amount = $product->get_low_stock_amount(); // Maybe zero or empty string, can't use absint here or "0" will be ignored
		$wc_low_stock_amount      = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 0 ) );

		// If product level is not set, use WC Low stock threshold
		$low_stock_amount = $product_low_stock_amount === '' ? $wc_low_stock_amount : absint( $product_low_stock_amount );

		// Set availability class if stock is low and only if stock management is enabled, is_in_stock will be true if not managing stock (@since 1.8.3)
		if ( $product->is_in_stock() && $stock_quantity <= $low_stock_amount && $is_manage_stock ) {
			$availability['class'] = 'low-stock';
		}

		// Set availability text based on user input (@since 1.8.2)
		switch ( $availability['class'] ) {
			case 'in-stock':
				$availability['availability'] = ! empty( $settings['inStockText'] ) && $is_manage_stock ? $settings['inStockText'] : $availability['availability'];
				break;

			case 'available-on-backorder':
			case 'low-stock':
				$availability['availability'] = ! empty( $settings['lowStockText'] ) ? $settings['lowStockText'] : $availability['availability'];
				break;

			case 'out-of-stock':
				$availability['availability'] = ! empty( $settings['outOfStockText'] ) ? $settings['outOfStockText'] : $availability['availability'];
				break;
		}

		return $availability;
	}
}
