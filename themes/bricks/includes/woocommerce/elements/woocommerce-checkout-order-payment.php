<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Checkout_Order_Payment extends Element {
	public $category        = 'woocommerce';
	public $name            = 'woocommerce-checkout-order-payment';
	public $icon            = 'ti-menu-alt';
	public $panel_condition = [ 'templateType', '=', 'wc_form_pay' ];

	public function get_label() {
		return esc_html__( 'Checkout order payment', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['payment'] = [
			'title' => esc_html__( 'Payment', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['terms'] = [
			'title' => esc_html__( 'Terms', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// PAYMENT

		$this->controls['paymentMargin'] = [
			'tab'   => 'content',
			'group' => 'payment',
			'type'  => 'spacing',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '#payment',
				],
			],
		];

		$this->controls['paymentPadding'] = [
			'tab'   => 'content',
			'group' => 'payment',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '#payment',
				],
			],
		];

		$this->controls['paymentBackground'] = [
			'tab'   => 'content',
			'group' => 'payment',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '#payment',
				],
			],
		];

		$this->controls['paymentBorder'] = [
			'tab'   => 'content',
			'group' => 'payment',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '#payment',
				],
			],
		];

		$this->controls['paymentMethodLabelTypography'] = [
			'tab'   => 'content',
			'group' => 'payment',
			'label' => esc_html__( 'Label typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '#payment .payment_methods label',
				],
			],
		];

		// DESCRIPTION

		$this->controls['paymentDescriptionSeparator'] = [
			'tab'   => 'content',
			'group' => 'payment',
			'type'  => 'separator',
			'label' => esc_html__( 'Description', 'bricks' ),
		];

		$this->controls['paymentDescriptionMargin'] = [
			'tab'         => 'content',
			'group'       => 'payment',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '#payment .payment_methods .payment_box',
				],
			],
			'placeholder' => [
				'top'    => 15,
				'right'  => 0,
				'bottom' => 15,
				'left'   => 0,
			],
		];

		$this->controls['paymentDescriptionPadding'] = [
			'tab'         => 'content',
			'group'       => 'payment',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '#payment .payment_methods .payment_box',
				],
			],
			'placeholder' => [
				'top'    => 10,
				'right'  => 15,
				'bottom' => 10,
				'left'   => 15,
			],
		];

		$this->controls['paymentDescriptionBackground'] = [
			'tab'   => 'content',
			'group' => 'payment',
			'type'  => 'color',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '#payment .payment_methods .payment_box',
				],
			],
		];

		$this->controls['paymentMethodDescriptionTypography'] = [
			'tab'   => 'content',
			'group' => 'payment',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '#payment .payment_methods .payment_box',
				],
			],
		];

		// TERMS

		$this->controls['privacySeparator'] = [
			'tab'   => 'content',
			'group' => 'terms',
			'type'  => 'separator',
			'label' => esc_html__( 'Privacy', 'bricks' ),
		];

		$this->controls['privacyMargin'] = [
			'tab'         => 'content',
			'group'       => 'terms',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.woocommerce-privacy-policy-text',
				],
			],
			'placeholder' => [
				'top'    => 10,
				'right'  => 0,
				'bottom' => 10,
				'left'   => 0,
			],
		];

		$this->controls['privacyTypography'] = [
			'tab'   => 'content',
			'group' => 'terms',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-privacy-policy-text',
				],
			],
		];

		// BUTTON

		$this->controls['buttonSeparator'] = [
			'tab'   => 'content',
			'group' => 'terms',
			'type'  => 'separator',
			'label' => esc_html__( 'Button', 'bricks' ),
		];

		$this->controls['buttonWidth'] = [
			'tab'         => 'content',
			'group'       => 'terms',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => 'button[type="submit"]',
				]
			],
			'placeholder' => '100%',
		];

		$this->controls['buttonAlign'] = [
			'tab'          => 'content',
			'group'        => 'terms',
			'label'        => esc_html__( 'Align', 'bricks' ),
			'type'         => 'align-items',
			'exclude'      => 'stretch',
			'css'          => [
				[
					'selector' => 'button[type="submit"]',
					'property' => 'align-self',
				],
			],
			'inline'       => true,
			'isHorizontal' => false,
			'required'     => [ 'buttonWidth' ],
		];

		$this->controls['buttonMargin'] = [
			'tab'         => 'content',
			'group'       => 'terms',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'css'         => [
				[
					'property' => 'margin',
					'selector' => 'button[type="submit"]',
				],
			],
			'placeholder' => [
				'top'    => 30,
				'right'  => 0,
				'bottom' => 0,
				'left'   => 0,
			],
		];

		$this->controls['buttonPadding'] = [
			'tab'         => 'content',
			'group'       => 'terms',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => 'button[type="submit"]',
				],
			],
			'placeholder' => [
				'top'    => 10,
				'right'  => 20,
				'bottom' => 10,
				'left'   => 20,
			],
		];

		$this->controls['buttonBackground'] = [
			'tab'   => 'content',
			'group' => 'terms',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => 'button[type="submit"]',
				],
			],
		];

		$this->controls['buttonBorder'] = [
			'tab'   => 'content',
			'group' => 'terms',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => 'button[type="submit"]',
				],
			],
		];

		$this->controls['buttonTypography'] = [
			'tab'   => 'content',
			'group' => 'terms',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => 'button[type="submit"]',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;
		$order    = false;

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
		?>

		<div <?php echo $this->render_attributes( '_root' ); ?>>
			<div id="payment">
				<?php if ( $order && $order->needs_payment() ) { ?>
					<ul class="wc_payment_methods payment_methods methods">
						<?php
						$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

						if ( ! empty( $available_gateways ) ) {
							foreach ( $available_gateways as $gateway ) {
								wc_get_template( 'checkout/payment-method.php', [ 'gateway' => $gateway ] );
							}
						} else {
							echo '<li>';
							wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ), 'notice' );
							echo '</li>';
						}
						?>
					</ul>
				<?php } ?>
				<div class="form-row">
					<input type="hidden" name="woocommerce_pay" value="1" />

					<?php
					wc_get_template( 'checkout/terms.php' );

					do_action( 'woocommerce_pay_order_before_submit' );

					$order_button_text = apply_filters( 'woocommerce_pay_order_button_text', __( 'Pay for order', 'woocommerce' ) );

					echo apply_filters( 'woocommerce_pay_order_button_html', '<button type="submit" class="button alt" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // @codingStandardsIgnoreLine

					do_action( 'woocommerce_pay_order_after_submit' );

					wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' );
					?>
				</div>
			</div>
		</div>
		<?php
	}
}
