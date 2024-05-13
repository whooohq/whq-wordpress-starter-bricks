<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_View_Order extends Woo_Element {
	public $category        = 'woocommerce';
	public $name            = 'woocommerce-account-view-order';
	public $icon            = 'ti-layout-list-thumb';
	public $panel_condition = [ 'templateType', '=', 'wc_account_view_order' ];

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'View order', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['info'] = [
			'title' => esc_html__( 'Info', 'bricks' ),
		];

		$this->control_groups['notes'] = [
			'title' => esc_html__( 'Order updates', 'bricks' ),
		];

		$this->control_groups['downloads'] = [
			'title' => esc_html__( 'Downloads', 'bricks' ),
		];

		$this->control_groups['orderDetails'] = [
			'title' => esc_html__( 'Order details', 'bricks' ),
		];

		$this->control_groups['customerDetails'] = [
			'title' => esc_html__( 'Customer details', 'bricks' ),
		];
	}

	public function set_controls() {
		// Preview order ID
		$this->controls['previewOrderId'] = [
			'type'     => 'number',
			'label'    => esc_html__( 'Preview order ID', 'bricks' ),
			'info'     => esc_html__( 'Fallback', 'bricks' ) . ': ' . esc_html__( 'Last order', 'bricks' ),
			'rerender' => true,
		];

		// INFO

		$this->controls['orderInfoHide'] = [
			'group' => 'info',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Hide', 'bricks' ),
			'css'   => [
				[
					'selector' => '> p:first-child',
					'property' => 'display',
					'value'    => 'none',
				]
			],
		];

		$this->controls['orderInfoTypography'] = [
			'group' => 'info',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '> p:first-child',
				]
			],
		];

		// Mark

		$this->controls['orderInfoMarkSep'] = [
			'group' => 'info',
			'type'  => 'separator',
			'label' => esc_html__( 'Mark', 'bricks' ),
		];

		$this->controls['orderMarkPadding'] = [
			'group' => 'info',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => 'mark',
				]
			],
		];

		$this->controls['orderMarkBackgroundColor'] = [
			'group' => 'info',
			'type'  => 'color',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => 'mark',
				]
			],
		];

		$this->controls['orderMarkTypography'] = [
			'group' => 'info',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => 'mark',
				]
			],
		];

		// NOTES

		$notes_controls = $this->generate_standard_controls( 'notes', '.woocommerce-OrderUpdates' );
		$notes_controls = $this->controls_grouping( $notes_controls, 'notes' );
		$this->controls = array_merge( $this->controls, $notes_controls );

		// Notes - title
		$this->controls['notesTitleSep'] = [
			'group' => 'notes',
			'type'  => 'separator',
			'label' => esc_html__( 'Title', 'bricks' ),
		];

		$this->controls['notesTitleHide'] = [
			'group' => 'notes',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Hide', 'bricks' ),
			'css'   => [
				[
					'selector' => '> h2',
					'property' => 'display',
					'value'    => 'none',
				]
			],
		];

		$notes_title_controls = $this->generate_standard_controls( 'notesTitle', '> h2' );
		$notes_title_controls = $this->controls_grouping( $notes_title_controls, 'notes' );
		$this->controls       = array_merge( $this->controls, $notes_title_controls );

		// Notes - meta
		$this->controls['notesMetaSep'] = [
			'group' => 'notes',
			'type'  => 'separator',
			'label' => esc_html__( 'Meta', 'bricks' ),
		];

		$notes_meta_controls = $this->generate_standard_controls( 'notesMeta', '.woocommerce-OrderUpdate-meta' );
		$notes_meta_controls = $this->controls_grouping( $notes_meta_controls, 'notes' );
		$this->controls      = array_merge( $this->controls, $notes_meta_controls );

		// Notes - description
		$this->controls['notesDescriptionSep'] = [
			'group' => 'notes',
			'type'  => 'separator',
			'label' => esc_html__( 'Description', 'bricks' ),
		];

		$notes_description_controls = $this->generate_standard_controls( 'notesDescription', '.woocommerce-OrderUpdate-description' );
		$notes_description_controls = $this->controls_grouping( $notes_description_controls, 'notes' );
		$this->controls             = array_merge( $this->controls, $notes_description_controls );

		// DOWNLOADS

		$download_controls = $this->generate_standard_controls( 'downloads', '.woocommerce-table--order-downloads' );
		$download_controls = $this->controls_grouping( $download_controls, 'downloads' );
		$this->controls    = array_merge( $this->controls, $download_controls );

		// DOWNLOADS - TITLE
		$this->controls['downloadsTitleSep'] = [
			'group' => 'downloads',
			'type'  => 'separator',
			'label' => esc_html__( 'Title', 'bricks' ),
		];

		$download_title_controls = $this->generate_standard_controls( 'downloadsTitle', '.woocommerce-order-downloads__title' );
		$download_title_controls = $this->controls_grouping( $download_title_controls, 'downloads' );
		$this->controls          = array_merge( $this->controls, $download_title_controls );

		// TABLE - HEAD
		$this->controls['downloadsTheadSep'] = [
			'group' => 'downloads',
			'type'  => 'separator',
			'label' => esc_html__( 'Table', 'bricks' ) . ' - ' . esc_html__( 'Head', 'bricks' ),
		];

		$download_thead_controls = $this->generate_standard_controls( 'downloadsThead', '.woocommerce-order-downloads thead th, .woocommerce-order-downloads tbody td::before' );
		unset( $download_thead_controls['downloadsTheadMargin'] );
		unset( $download_thead_controls['downloadsTheadBoxShadow'] );

		$download_thead_controls = $this->controls_grouping( $download_thead_controls, 'downloads' );
		$this->controls          = array_merge( $this->controls, $download_thead_controls );

		// TABLE - BODY
		$this->controls['downloadsTbodySep'] = [
			'group' => 'downloads',
			'type'  => 'separator',
			'label' => esc_html__( 'Table', 'bricks' ) . ' - ' . esc_html__( 'Body', 'bricks' ),
		];

		$download_tbody_controls = $this->generate_standard_controls( 'downloadsTbody', '.woocommerce-order-downloads tbody td' );
		unset( $download_tbody_controls['downloadsTbodyMargin'] );
		unset( $download_tbody_controls['downloadsTbodyBoxShadow'] );

		$download_tbody_controls = $this->controls_grouping( $download_tbody_controls, 'downloads' );
		$this->controls          = array_merge( $this->controls, $download_tbody_controls );

		// BUTTON
		$this->controls['downloadsButtonSep'] = [
			'group' => 'downloads',
			'type'  => 'separator',
			'label' => esc_html__( 'Button', 'bricks' ),
		];

		$download_button_controls = $this->generate_standard_controls( 'downloadsButton', '.woocommerce-MyAccount-downloads-file.button' );
		$download_button_controls = $this->controls_grouping( $download_button_controls, 'downloads' );
		$this->controls           = array_merge( $this->controls, $download_button_controls );

		// ORDER DETAILS

		$order_details_controls = $this->generate_standard_controls( 'orderDetails', '.woocommerce-order-details' );
		$order_details_controls = $this->controls_grouping( $order_details_controls, 'orderDetails' );
		$this->controls         = array_merge( $this->controls, $order_details_controls );

		// TITLE
		$this->controls['orderDetailsTitleSep'] = [
			'group' => 'orderDetails',
			'type'  => 'separator',
			'label' => esc_html__( 'Title', 'bricks' ),
		];

		$order_details_title_controls = $this->generate_standard_controls( 'orderDetailsTitle', '.woocommerce-order-details__title' );
		$order_details_title_controls = $this->controls_grouping( $order_details_title_controls, 'orderDetails' );
		$this->controls               = array_merge( $this->controls, $order_details_title_controls );

		// TABLE - BODY
		$this->controls['orderDetailsTbodySep'] = [
			'group' => 'orderDetails',
			'type'  => 'separator',
			'label' => esc_html__( 'Table', 'bricks' ) . ' - ' . esc_html__( 'Body', 'bricks' ),
		];

		$order_details_tbody_controls = $this->generate_standard_controls( 'orderDetailsTbody', '.woocommerce-order-details tbody td' );
		unset( $order_details_tbody_controls['orderDetailsTbodyMargin'] );
		unset( $order_details_tbody_controls['orderDetailsTbodyBoxShadow'] );

		$order_details_tbody_controls = $this->controls_grouping( $order_details_tbody_controls, 'orderDetails' );
		$this->controls               = array_merge( $this->controls, $order_details_tbody_controls );

		// TABLE - FOOT
		$this->controls['orderDetailsTfootSep'] = [
			'group' => 'orderDetails',
			'type'  => 'separator',
			'label' => esc_html__( 'Table', 'bricks' ) . ' - ' . esc_html__( 'Foot', 'bricks' ),
		];

		$order_details_tfoot_controls = $this->generate_standard_controls( 'orderDetailsTfoot', '.woocommerce-order-details tfoot' );
		$order_details_tfoot_controls['orderDetailsTfootPadding']['css'][0]['selector']    .= ' tr > *';
		$order_details_tfoot_controls['orderDetailsTfootTypography']['css'][0]['selector'] .= ' td';

		unset( $order_details_tfoot_controls['orderDetailsTfootMargin'] );
		unset( $order_details_tfoot_controls['orderDetailsTfootBoxShadow'] );

		$order_details_tfoot_controls = $this->controls_grouping( $order_details_tfoot_controls, 'orderDetails' );
		$this->controls               = array_merge( $this->controls, $order_details_tfoot_controls );

		$this->controls['orderDetailsTfootHeadingTypography'] = [
			'group' => 'orderDetails',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ) . ' (' . esc_html__( 'Heading', 'bricks' ) . ')',
			'css'   => [
				[
					'property' => 'typography',
					'selector' => '.woocommerce-order-details tfoot th',
				]
			]
		];

		// BUTTON
		$this->controls['orderAgainButtonSep'] = [
			'group' => 'orderDetails',
			'type'  => 'separator',
			'label' => esc_html__( 'Button', 'bricks' ),
		];

		$this->controls['orderAgainButtonWidth'] = [
			'group' => 'orderDetails',
			'type'  => 'number',
			'units' => true,
			'label' => esc_html__( 'Width', 'bricks' ),
			'css'   => [
				[
					'property' => 'width',
					'selector' => '.order-again a.button',
				]
			],
		];

		$order_again_button_controls = $this->generate_standard_controls( 'orderAgainButton', '.order-again a.button' );
		$order_again_button_controls = $this->controls_grouping( $order_again_button_controls, 'orderDetails' );
		$this->controls              = array_merge( $this->controls, $order_again_button_controls );

		// CUSTOMER DETAILS

		$customer_details_controls = $this->generate_standard_controls( 'customerDetails', '.woocommerce-customer-details' );
		$customer_details_controls = $this->controls_grouping( $customer_details_controls, 'customerDetails' );
		$this->controls            = array_merge( $this->controls, $customer_details_controls );

		// TITLE
		$this->controls['customerDetailsTitleSep'] = [
			'group' => 'customerDetails',
			'type'  => 'separator',
			'label' => esc_html__( 'Title', 'bricks' ),
		];

		$customer_details_title_controls = $this->generate_standard_controls( 'customerDetailsTitle', '.woocommerce-customer-details h2' );
		$customer_details_title_controls = $this->controls_grouping( $customer_details_title_controls, 'customerDetails' );
		$this->controls                  = array_merge( $this->controls, $customer_details_title_controls );

		// ADDRESS
		$this->controls['customerDetailsAddressSep'] = [
			'group' => 'customerDetails',
			'type'  => 'separator',
			'label' => esc_html__( 'Address', 'bricks' ),
		];

		$customer_details_address_controls = $this->generate_standard_controls( 'customerDetailsAddress', '.woocommerce-customer-details address' );
		$customer_details_address_controls = $this->controls_grouping( $customer_details_address_controls, 'customerDetails' );
		$this->controls                    = array_merge( $this->controls, $customer_details_address_controls );
	}

	public function render() {
		/**
		 * STEP: Get the view order Woo template
		 *
		 * Pass required $order, $order_id to Woo template
		 */
		$order    = $this->get_order();
		$order_id = $order ? $order->get_id() : 0;

		ob_start();

		wc_get_template(
			'myaccount/view-order.php',
			[
				'order'    => $order,
				'order_id' => $order_id,
			]
		);

		$view_order_template = ob_get_clean();

		// Render Woo template
		echo "<div {$this->render_attributes( '_root' )}>{$view_order_template}</div>";
	}
}
