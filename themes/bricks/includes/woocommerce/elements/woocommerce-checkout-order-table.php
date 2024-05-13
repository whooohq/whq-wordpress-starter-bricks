<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Checkout_Order_Table extends Element {
	public $category        = 'woocommerce';
	public $name            = 'woocommerce-checkout-order-table';
	public $icon            = 'ti-menu-alt';
	public $panel_condition = [ 'templateType', '=', 'wc_form_pay' ];

	public function get_label() {
		return esc_html__( 'Checkout order table', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['header'] = [
			'title' => esc_html__( 'Header', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['product'] = [
			'title' => esc_html__( 'Product', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['footer'] = [
			'title' => esc_html__( 'Footer', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// HEADER

		$this->controls['headPadding'] = [
			'tab'         => 'content',
			'group'       => 'header',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.shop_table thead th',
				],
			],
			'placeholder' => [
				'top'    => 20,
				'right'  => 0,
				'bottom' => 20,
				'left'   => 0,
			],
		];

		$this->controls['headBackground'] = [
			'tab'   => 'content',
			'group' => 'header',
			'type'  => 'color',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.shop_table thead',
				],
			],
		];

		$this->controls['headBorder'] = [
			'tab'   => 'content',
			'group' => 'header',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.shop_table thead',
				],
			],
		];

		$this->controls['headTypography'] = [
			'tab'   => 'content',
			'group' => 'header',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.shop_table thead th',
				],
			],
		];

		// PRODUCT

		$this->controls['productPadding'] = [
			'tab'         => 'content',
			'group'       => 'product',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.shop_table th',
				],
				[
					'property' => 'padding',
					'selector' => '.shop_table td',
				],
			],
			'placeholder' => [
				'top'    => 20,
				'right'  => 0,
				'bottom' => 20,
				'left'   => 0,
			],
		];

		$this->controls['productBackground'] = [
			'tab'   => 'content',
			'group' => 'product',
			'type'  => 'color',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.shop_table tbody',
				],
			],
		];

		$this->controls['productBorder'] = [
			'tab'   => 'content',
			'group' => 'product',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.shop_table tbody tr',
				],
			],
		];

		$this->controls['productTypography'] = [
			'tab'   => 'content',
			'group' => 'product',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.shop_table tbody td',
				],
			],
		];

		// FOOTER

		$this->controls['footPadding'] = [
			'tab'         => 'content',
			'group'       => 'footer',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.shop_table tfoot th',
				],
				[
					'property' => 'padding',
					'selector' => '.shop_table tfoot td',
				],
			],
			'placeholder' => [
				'top'    => 20,
				'right'  => 0,
				'bottom' => 20,
				'left'   => 0,
			],
		];

		$this->controls['footBackground'] = [
			'tab'   => 'content',
			'group' => 'footer',
			'type'  => 'color',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.shop_table tfoot',
				],
			],
		];

		$this->controls['footBorder'] = [
			'tab'   => 'content',
			'group' => 'footer',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.shop_table tfoot tr',
				],
			],
		];

		$this->controls['footerTypography'] = [
			'tab'   => 'content',
			'group' => 'footer',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.shop_table tfoot th',
				],
				[
					'property' => 'font',
					'selector' => '.shop_table tfoot td',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;
		$order    = false;

		// Populate the template with the last order
		if ( Helpers::is_bricks_preview() ) {
			$orders = wc_get_orders(
				[
					'limit' => 1,
				]
			);

			if ( isset( $orders[0] ) ) {
				$order = $orders[0];
			}
		}

		// Logic from WC_Shortcode_Checkout::order_received()
		else {
			$order_id  = get_query_var( 'order-pay', false );
			$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';

			if ( $order_id > 0 ) {
				$order = wc_get_order( $order_id );

				if ( ! $order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
					$order = false;
				}
			}
		}

		// Render WooCommerce part templates/checkout/form-pay.php
		$totals = $order ? $order->get_order_item_totals() : false;
		?>

		<div <?php echo $this->render_attributes( '_root' ); ?>>
			<table class="shop_table">
				<thead>
					<tr>
						<th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
						<th class="product-quantity"><?php esc_html_e( 'Qty', 'woocommerce' ); ?></th>
						<th class="product-total"><?php esc_html_e( 'Totals', 'woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( is_a( $order, 'WC_Order' ) && is_array( $order->get_items() ) && count( $order->get_items() ) > 0 ) { ?>
						<?php foreach ( $order->get_items() as $item_id => $item ) { ?>
							<?php
							if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
								continue;
							}
							?>
							<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
								<td class="product-name">
									<?php
										echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );

										do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );

										wc_display_item_meta( $item );

										do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
									?>
								</td>
								<td class="product-quantity"><?php echo apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', esc_html( $item->get_quantity() ) ) . '</strong>', $item ); ?></td><?php // @codingStandardsIgnoreLine ?>
								<td class="product-subtotal"><?php echo $order->get_formatted_line_subtotal( $item ); ?></td><?php // @codingStandardsIgnoreLine ?>
							</tr>
						<?php } ?>
					<?php } ?>
				</tbody>
				<tfoot>
					<?php if ( $totals ) { ?>
						<?php foreach ( $totals as $total ) { ?>
							<tr>
								<th scope="row" colspan="2"><?php echo $total['label']; ?></th><?php // @codingStandardsIgnoreLine ?>
								<td class="product-total"><?php echo $total['value']; ?></td><?php // @codingStandardsIgnoreLine ?>
							</tr>
						<?php } ?>
					<?php } ?>
				</tfoot>
			</table>
		</div>
		<?php
	}
}
