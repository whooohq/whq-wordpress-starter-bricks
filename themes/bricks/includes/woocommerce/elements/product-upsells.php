<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Upsells extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-upsells';
	public $icon     = 'ti-stats-up';

	public function get_label() {
		return esc_html__( 'Product up/cross-sells', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['button'] = [
			'title' => esc_html__( 'Button', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['type'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Type', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'upsells'     => esc_html__( 'Up-sells', 'woocommerce' ),
				'cross_sells' => esc_html__( 'Cross-sells', 'woocommerce' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Upsells', 'woocommerce' )
		];

		$this->controls['headingText'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Heading', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['headingTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Heading typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'selector' => '.up-sells > h2',
					'property' => 'font',
				],
				[
					'selector' => '.cross-sells > h2',
					'property' => 'font',
				],
			],
			'required' => [ 'headingText', '!=', '' ],
		];

		$this->controls['count'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Max. products', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 4,
			'placeholder' => 4,
		];

		$this->controls['columns'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Columns', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 4,
			'css'         => [
				[
					'selector'  => '.products',
					'property'  => 'grid-template-columns',
					'value'     => 'repeat(%s, 1fr)', // NOTE: Undocumented (@since 1.3)
					'important' => true,
				],
			],
			'placeholder' => 4,
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Gap', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '.products',
					'property' => 'gap',
				],
			],
			'placeholder' => '30px',
		];

		$this->controls['orderby'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Order by', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['queryOrderBy'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Random', 'bricks' )
		];

		$this->controls['order'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Order', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['queryOrder'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Descending', 'bricks' )
		];

		// BUTTON

		$this->controls['buttonPadding'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'selector' => '.button',
					'property' => 'padding',
				],
			],
		];

		$this->controls['buttonBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.button',
					'property' => 'background-color',
				],
			],
		];

		$this->controls['buttonBorder'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.button',
				],
			],
		];

		$this->controls['buttonTypography'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.button',
					'property' => 'font',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		$type = ! empty( $settings['type'] ) ? $settings['type'] : 'upsells';

		global $product;

		$product = wc_get_product( $this->post_id );

		if ( empty( $product ) ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'No product selected', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		$ids = $type == 'upsells' ? $product->get_upsell_ids() : $product->get_cross_sell_ids();

		if ( ! $ids ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'No products to show.', 'bricks' ),
					'description' => esc_html__( 'Edit linked products to add product upsells or cross-sells.', 'bricks' ),
				]
			);
		}

		$posts_per_page = isset( $settings['count'] ) ? $settings['count'] : -1;
		$columns        = isset( $settings['columns'] ) ? $settings['columns'] : 4;
		$orderby        = isset( $settings['orderby'] ) ? $settings['orderby'] : 'rand';
		$order          = isset( $settings['order'] ) ? $settings['order'] : 'DESC';

		// @hook woocommerce_product_cross_sells_products_heading
		// @hook woocommerce_product_upsells_products_heading
		add_filter( "woocommerce_product_{$type}_products_heading", [ $this, 'render_heading' ] );

		echo "<div {$this->render_attributes( '_root' )}>";

		if ( $type == 'cross_sells' ) {
			$this->woocommerce_cross_sell_display( $product, $posts_per_page, $columns, $orderby, $order );
		} else {
			woocommerce_upsell_display( $posts_per_page, $columns, $orderby, $order );
		}

		echo '</div>';

		remove_filter( "woocommerce_product_{$type}_products_heading", [ $this, 'render_heading' ] );
	}

	public function render_heading( $heading = '' ) {
		return ! empty( $this->settings['headingText'] ) ? esc_html( $this->settings['headingText'] ) : '';
	}

	/**
	 * Output cart cross-sells
	 *
	 * NOTE: Similar to original function but here to make sure it runs outside the checkout page and with product cross sells with cart empty.
	 *
	 * @see woocommerce/includes/wc-template-functions.php
	 *
	 * @param  WP_Product $product (extra parameter).
	 * @param  int        $limit (default: 2).
	 * @param  int        $columns (default: 2).
	 * @param  string     $orderby (default: 'rand').
	 * @param  string     $order (default: 'desc').
	 *
	 * @since 1.4
	 */
	public function woocommerce_cross_sell_display( $product, $limit = 2, $columns = 2, $orderby = 'rand', $order = 'desc' ) {
		// Get visible cross sells then sort them at random.
		$cross_sells = array_filter( array_map( 'wc_get_product', $product->get_cross_sell_ids() ), 'wc_products_array_filter_visible' );

		wc_set_loop_prop( 'name', 'cross-sells' );
		wc_set_loop_prop( 'columns', apply_filters( 'woocommerce_cross_sells_columns', $columns ) );

		// Handle orderby and limit results.
		$orderby     = apply_filters( 'woocommerce_cross_sells_orderby', $orderby );
		$order       = apply_filters( 'woocommerce_cross_sells_order', $order );
		$cross_sells = wc_products_array_orderby( $cross_sells, $orderby, $order );
		$limit       = apply_filters( 'woocommerce_cross_sells_total', $limit );
		$cross_sells = $limit > 0 ? array_slice( $cross_sells, 0, $limit ) : $cross_sells;

		wc_get_template(
			'cart/cross-sells.php',
			[
				'cross_sells'    => $cross_sells,
				// Not used now, but used in previous version of up-sells.php
				'posts_per_page' => $limit,
				'orderby'        => $orderby,
				'columns'        => $columns,
			]
		);
	}
}
