<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Checkout_Order_Review extends Element {
	public $category        = 'woocommerce';
	public $name            = 'woocommerce-checkout-order-review';
	public $icon            = 'ti-view-list-alt';
	public $panel_condition = [ 'templateType', '=', 'wc_form_checkout' ];

	public function get_label() {
		return esc_html__( 'Checkout order review', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['orderReview'] = [
			'title' => esc_html__( 'Order review', 'bricks' ),
			'tab'   => 'content',
		];

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
		// TITLE

		$this->controls['hideTitle'] = [
			'tab'   => 'content',
			'group' => 'orderReview',
			'label' => esc_html__( 'Hide title', 'bricks' ),
			'type'  => 'checkbox',
			'css'   => [
				[
					'selector' => '#order_review_heading',
					'property' => 'display',
					'value'    => 'none',
				],
			],
		];

		$this->controls['orderTitle'] = [
			'tab'         => 'content',
			'group'       => 'orderReview',
			'label'       => esc_html__( 'Title', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Your order', 'woocommerce' ),
			'required'    => [ 'hideTitle' , '=', '' ],
		];

		$this->controls['orderTitleTypography'] = [
			'tab'      => 'content',
			'group'    => 'orderReview',
			'label'    => esc_html__( 'Title typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '#order_review_heading',
				],
			],
			'required' => [ 'hideTitle' , '=', '' ],
		];

		$this->controls['orderSubtitlesTypography'] = [
			'tab'   => 'content',
			'group' => 'orderReview',
			'label' => esc_html__( 'Subtitles typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.shop_table thead',
				],
				[
					'property' => 'font',
					'selector' => '.shop_table tfoot',
				],
			],
		];

		// ITEMS

		$this->controls['cartItemsSeparator'] = [
			'tab'   => 'content',
			'group' => 'orderReview',
			'type'  => 'separator',
			'label' => esc_html__( 'Items', 'bricks' ),
		];

		$this->controls['cartItemsTypography'] = [
			'tab'   => 'content',
			'group' => 'orderReview',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.shop_table tbody td',
				],
			],
		];

		$this->controls['cartItemsPadding'] = [
			'tab'         => 'content',
			'group'       => 'orderReview',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.shop_table thead th',
				],
				[
					'property' => 'padding',
					'selector' => '.shop_table tbody td',
				],
			],
			'placeholder' => [
				'top'    => 20,
				'right'  => 20,
				'bottom' => 20,
				'left'   => 20,
			],
		];

		$this->controls['cartItemsBorder'] = [
			'tab'   => 'content',
			'group' => 'orderReview',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.shop_table tbody td',
				],
			],
		];

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
			'tab'         => 'content',
			'group'       => 'payment',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '#payment',
				],
			],
			'placeholder' => [
				'top'    => 20,
				'right'  => 20,
				'bottom' => 20,
				'left'   => 20,
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

		$this->controls['_border']['css'] = [
			[
				'selector' => '.woocommerce-checkout-review-order',
				'property' => 'border',
			],
		];
	}

	public function render() {
		Woocommerce_Helpers::maybe_populate_cart_contents();

		$settings = $this->settings;

		echo '<div ' . $this->render_attributes( '_root' ) . '>';

		// Title
		$title = ! empty( $settings['orderTitle'] ) ? $settings['orderTitle'] : esc_html__( 'Your order', 'woocommerce' );

		// Render WooCommerce part
		do_action( 'woocommerce_checkout_before_order_review_heading' );

		if ( ! empty( $title ) ) {
			echo '<h3 id="order_review_heading">' . esc_html( $title ) . '</h3>';
		}

		do_action( 'woocommerce_checkout_before_order_review' );

		echo '<div id="order_review" class="woocommerce-checkout-review-order">';

		do_action( 'woocommerce_checkout_order_review' );

		echo '</div>';

		do_action( 'woocommerce_checkout_after_order_review' );

		echo '</div>';
	}
}
