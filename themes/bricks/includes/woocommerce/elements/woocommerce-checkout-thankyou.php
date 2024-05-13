<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Checkout_Thankyou extends Element {
	public $category        = 'woocommerce';
	public $name            = 'woocommerce-checkout-thankyou';
	public $icon            = 'ti-check-box';
	public $panel_condition = [ 'templateType', '=', 'wc_thankyou' ];

	public function get_label() {
		return esc_html__( 'Checkout thank you', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['message'] = [
			'title' => esc_html__( 'Message', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['overview'] = [
			'title' => esc_html__( 'Order overview', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['details'] = [
			'title' => esc_html__( 'Order details', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['address'] = [
			'title' => esc_html__( 'Billing address', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// MESSAGE

		$this->controls['hideMessage'] = [
			'tab'   => 'content',
			'group' => 'message',
			'label' => esc_html__( 'Hide message', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['message'] = [
			'tab'         => 'content',
			'group'       => 'message',
			'type'        => 'text',
			'placeholder' => esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ),
			'required'    => [ 'hideMessage' , '=', '' ],
		];

		$this->controls['messageMargin'] = [
			'tab'         => 'content',
			'group'       => 'message',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.woocommerce-notice',
				],
			],
			'placeholder' => [
				'top'    => 30,
				'right'  => 0,
				'bottom' => 30,
				'left'   => 0,
			],
			'required'    => [ 'hideMessage' , '=', '' ],
		];

		$this->controls['messagePadding'] = [
			'tab'      => 'content',
			'group'    => 'message',
			'label'    => esc_html__( 'Padding', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'padding',
					'selector' => '.woocommerce-notice',
				],
			],
			'required' => [ 'hideMessage' , '=', '' ],
		];

		$this->controls['messageBackground'] = [
			'tab'      => 'content',
			'group'    => 'message',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.woocommerce-notice',
				],
			],
			'required' => [ 'hideMessage' , '=', '' ],
		];

		$this->controls['messageBorder'] = [
			'tab'      => 'content',
			'group'    => 'message',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.woocommerce-notice',
				],
			],
			'required' => [ 'hideMessage' , '=', '' ],
		];

		$this->controls['messageTypography'] = [
			'tab'      => 'content',
			'group'    => 'message',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-thankyou-order-received',
				],
			],
			'required' => [ 'hideMessage' , '=', '' ],
		];

		// ORDER OVERVIEW

		$this->controls['overviewMargin'] = [
			'tab'         => 'content',
			'group'       => 'overview',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.woocommerce-order-overview.order_details',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => 15,
				'left'   => 0,
			],
		];

		$this->controls['overviewBackground'] = [
			'tab'   => 'content',
			'group' => 'overview',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.woocommerce-order-overview.order_details',
				],
			],
		];

		$this->controls['overviewBorder'] = [
			'tab'   => 'content',
			'group' => 'overview',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.woocommerce-order-overview.order_details',
				],
			],
		];

		$this->controls['overviewBorderItem'] = [
			'tab'         => 'content',
			'group'       => 'overview',
			'label'       => esc_html__( 'Border', 'bricks' ) . ' (' . esc_html__( 'Item', 'bricks' ) . ')',
			'type'        => 'border',
			'css'         => [
				[
					'property' => 'border',
					'selector' => '.woocommerce-order-overview.order_details li',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 1,
				'bottom' => 1,
				'left'   => 0,
			],
		];

		$this->controls['overviewLabelTypography'] = [
			'tab'   => 'content',
			'group' => 'overview',
			'label' => esc_html__( 'Label typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-order-overview.order_details li',
				],
			],
		];

		$this->controls['overviewTextTypography'] = [
			'tab'   => 'content',
			'group' => 'overview',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-order-overview.order_details li strong',
				],
			],
		];

		// ORDER DETAILS

		$this->controls['detailsMargin'] = [
			'tab'         => 'content',
			'group'       => 'details',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.woocommerce-order-details',
				],
			],
			'placeholder' => [
				'top'    => 30,
				'right'  => 0,
				'bottom' => 30,
				'left'   => 0,
			],
		];

		$this->controls['detailsPadding'] = [
			'tab'         => 'content',
			'group'       => 'details',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'type'        => 'spacing',
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
				'right'  => 20,
				'bottom' => 20,
				'left'   => 20,
			],
		];

		$this->controls['detailsBackground'] = [
			'tab'   => 'content',
			'group' => 'details',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.woocommerce-order-details table',
				],
			],
		];

		$this->controls['detailsBorder'] = [
			'tab'   => 'content',
			'group' => 'details',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.woocommerce-order-details table',
				],
			],
		];

		$this->controls['detailsBackgroundFooter'] = [
			'tab'   => 'content',
			'group' => 'details',
			'label' => esc_html__( 'Background', 'bricks' ) . ' (' . esc_html__( 'Footer', 'bricks' ) . ')',
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.shop_table tfoot',
				],
			],
		];

		// BILLING ADDRESS

		$this->controls['addressMargin'] = [
			'tab'         => 'content',
			'group'       => 'address',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.woocommerce-customer-details',
				],
			],
			'placeholder' => [
				'top'    => 30,
				'right'  => 0,
				'bottom' => 30,
				'left'   => 0,
			],
		];

		$this->controls['addressTypography'] = [
			'tab'   => 'content',
			'group' => 'address',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-customer-details address',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		// Populate the template with the last order
		if ( bricks_is_builder() || bricks_is_builder_call() ) {
			$orders = wc_get_orders(
				[
					'limit' => 1,
				]
			);

			$order = $orders ? $orders[0] : false;
		}

		// Logic from WC_Shortcode_Checkout::order_received()
		else {
			$order_id = get_query_var( 'order-received', false );

			$order = false;

			// Get the order.
			$order_id  = apply_filters( 'woocommerce_thankyou_order_id', absint( $order_id ) );
			$order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : wc_clean( wp_unslash( $_GET['key'] ) ) );

			if ( $order_id > 0 && ! bricks_is_builder() ) {
				$order = wc_get_order( $order_id );
				if ( ! $order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
					$order = false;
				}
			}
		}

		$thankyou_message = ! empty( $settings['message'] ) ? $settings['message'] : __( 'Thank you. Your order has been received.', 'woocommerce' );

		if ( isset( $settings['hideMessage'] ) ) {
			$thankyou_message = false;
		}

		$this->render_attributes( '_root', 'class', 'woocommerce-order' );

		// Render WooCommerce part templates/checkout/thankyou.php
		?>
		<div <?php echo $this->render_attributes( '_root' ); ?>>
			<?php
			if ( $order ) {
				do_action( 'woocommerce_before_thankyou', $order->get_id() );
				?>

				<?php if ( $order->has_status( 'failed' ) ) { ?>
					<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

					<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
						<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
						<?php if ( is_user_logged_in() ) { ?>
							<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
						<?php } ?>
					</p>

				<?php } else { ?>
					<?php if ( $thankyou_message ) { ?>
					<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
						<?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html( $thankyou_message ), $order ); ?>
					</p>
					<?php } ?>

					<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
						<li class="woocommerce-order-overview__order order">
							<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
							<strong><?php echo $order->get_order_number(); ?></strong>
						</li>

						<li class="woocommerce-order-overview__date date">
							<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
							<strong><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
						</li>

						<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) { ?>
							<li class="woocommerce-order-overview__email email">
								<?php esc_html_e( 'Email:', 'woocommerce' ); ?>
								<strong><?php echo $order->get_billing_email(); ?></strong>
							</li>
						<?php } ?>

						<li class="woocommerce-order-overview__total total">
							<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
							<strong><?php echo $order->get_formatted_order_total(); ?></strong>
						</li>

						<?php if ( $order->get_payment_method_title() ) { ?>
							<li class="woocommerce-order-overview__payment-method method">
								<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
								<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
							</li>
						<?php } ?>
					</ul>
					<?php
				}

				do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
				do_action( 'woocommerce_thankyou', $order->get_id() );
			} else {
				?>
				<?php if ( $thankyou_message ) { ?>
				<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
					<?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html( $thankyou_message ), false ); ?>
				</p>
				<?php } ?>
			<?php } ?>
		</div>
		<?php
	}
}
