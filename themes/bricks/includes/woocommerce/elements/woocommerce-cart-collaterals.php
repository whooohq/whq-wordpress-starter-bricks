<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Cart_Collaterals extends Element {
	public $category        = 'woocommerce';
	public $name            = 'woocommerce-cart-collaterals';
	public $icon            = 'ti-money';
	public $panel_condition = [ 'templateType', '=', [ 'wc_cart', 'wc_cart_empty' ] ];

	public function get_label() {
		return esc_html__( 'Cart totals', 'bricks' );
	}

	public function set_controls() {
		// CROSS SELLS

		$this->controls['disableCrossSells'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Disable cross sells', 'bricks' ),
			'type'  => 'checkbox',
		];

		// TITLE

		$this->controls['hideTitle'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Hide title', 'bricks' ),
			'type'  => 'checkbox',
			'css'   => [
				[
					'property' => 'display',
					'selector' => 'h2',
					'value'    => 'none',
				],
			],
		];

		$this->controls['titleTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Title', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'h2',
				],
			],
			'required' => [ 'hideTitle', '=', '' ],
		];

		$this->controls['subtotalTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Subtotal', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.cart-subtotal',
				],
			],
		];

		$this->controls['totalTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Total', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.order-total',
				],
			],
		];

		// TABLE

		$this->controls['tableSeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Table', 'bricks' ),
			'tab'   => 'content',
		];

		$this->controls['tableMargin'] = [
			'tab'         => 'content',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'css'         => [
				[
					'property' => 'margin',
					'selector' => 'table',
				],
				[
					'property' => 'margin',
					'selector' => 'table',
				],
			],
			'placeholder' => [
				'top'    => '0px',
				'right'  => '0px',
				'bottom' => '30px',
				'left'   => '0px',
			],
		];

		$this->controls['tablePadding'] = [
			'tab'         => 'content',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => 'table tbody th',
				],
				[
					'property' => 'padding',
					'selector' => 'table tbody td',
				],
			],
			'placeholder' => [
				'top'    => '15px',
				'right'  => '0px',
				'bottom' => '15px',
				'left'   => '0px',
			],
		];

		$this->controls['tableBorder'] = [
			'tab'         => 'content',
			'type'        => 'border',
			'label'       => esc_html__( 'Border', 'bricks' ),
			'css'         => [
				[
					'property' => 'border',
					'selector' => 'table',
				],
			],
			'placeholder' => [
				'top'    => '15px',
				'right'  => '0px',
				'bottom' => '15px',
				'left'   => '0px',
			],
		];

		// BUTTON

		$this->controls['buttonSeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Button', 'bricks' ),
			'tab'   => 'content',
		];

		$this->controls['buttonText'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'placeholder' => esc_html__( 'Proceed to checkout', 'woocommerce' ),
		];

		$this->controls['buttonBackground'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wc-proceed-to-checkout .button',
				],
			],
		];

		$this->controls['buttonBorder'] = [
			'tab'   => 'content',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.wc-proceed-to-checkout .button',
				],
			],
		];

		$this->controls['buttonTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wc-proceed-to-checkout .button',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		Woocommerce_Helpers::maybe_init_cart_context();

		add_filter( 'bricks/woocommerce/cart_proceed_label', [ $this, 'proceed_to_checkout_button' ], 10, 1 );

		// WooCommerce template
		do_action( 'woocommerce_before_cart_collaterals' );

		if ( isset( $settings['disableCrossSells'] ) ) {
			remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
		}

		$this->set_attribute( '_root', 'class', 'cart-collaterals' );
		?>

		<div <?php echo $this->render_attributes( '_root' ); ?>>
			<?php
				/**
				 * Cart collaterals hook.
				 *
				 * @hooked woocommerce_cross_sell_display
				 * @hooked woocommerce_cart_totals - 10
				 */
				do_action( 'woocommerce_cart_collaterals' );
			?>
		</div>
		<?php
		if ( isset( $settings['disableCrossSells'] ) ) {
			add_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
		}

		remove_filter( 'bricks/woocommerce/cart_proceed_label', [ $this, 'proceed_to_checkout_button' ], 10, 1 );
	}

	public function proceed_to_checkout_button( $label ) {
		return ! empty( $this->settings['buttonText'] ) ? $this->settings['buttonText'] : $label;
	}
}
