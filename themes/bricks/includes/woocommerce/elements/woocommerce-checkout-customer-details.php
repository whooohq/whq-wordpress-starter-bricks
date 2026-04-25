<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Checkout_Customer_Details extends Element {
	public $category        = 'woocommerce';
	public $name            = 'woocommerce-checkout-customer-details';
	public $icon            = 'ti-user';
	public $panel_condition = [ 'templateType', '=', 'wc_form_checkout' ];

	public function get_label() {
		return esc_html__( 'Checkout customer details', 'bricks' );
	}

	public function set_controls() {

		// FIELDS

		// Only get the checkout fields in the builder to avoid conflicts with other plugins (@since 1.5)
		$checkout_fields = bricks_is_builder() ? WC()->checkout()->get_checkout_fields() : [];

		$billing_fields = [];

		if ( ! empty( $checkout_fields['billing'] ) && is_array( $checkout_fields['billing'] ) ) {
			foreach ( $checkout_fields['billing'] as $key => $field ) {
				if ( isset( $field['label'] ) ) {
					$billing_fields[ $key ] = $field['label'];
				}
			}
		}

		$this->controls['removeBillingFields'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Remove billing fields', 'bricks' ),
			'type'     => 'select',
			'options'  => $billing_fields,
			'multiple' => true,
		];

		$shipping_fields = [];

		if ( ! empty( $checkout_fields['shipping'] ) && is_array( $checkout_fields['shipping'] ) ) {
			foreach ( $checkout_fields['shipping'] as $key => $field ) {
				if ( isset( $field['label'] ) ) {
					$shipping_fields[ $key ] = $field['label'];
				}
			}
		}

		$this->controls['removeShippingFields'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Remove shipping fields', 'bricks' ),
			'type'     => 'select',
			'options'  => $shipping_fields,
			'multiple' => true,
		];

		// FORM

		$this->controls['titleSeparator'] = [
			'tab'   => 'content',
			'type'  => 'separator',
			'label' => esc_html__( 'Title', 'bricks' ),
		];

		$this->controls['hideTitle'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Hide', 'bricks' ),
			'type'  => 'checkbox',
			'css'   => [
				[
					'selector' => '.woocommerce-billing-fields h3',
					'property' => 'display',
					'value'    => 'none',
				],
			],
		];

		$this->controls['titleTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-billing-fields h3',
				],
			],
			'required' => [ 'hideTitle', '=', '' ],
		];

		// LABELS

		$this->controls['labelSeparator'] = [
			'tab'   => 'content',
			'type'  => 'separator',
			'label' => esc_html__( 'Labels', 'bricks' ),
		];

		$this->controls['hideLabels'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Hide', 'bricks' ),
			'type'     => 'checkbox',
			'css'      => [
				[
					'selector' => 'label',
					'property' => 'display',
					'value'    => 'none',
				],
			],
			'rerender' => true,
		];

		$this->controls['labelTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'label',
				],
			],
			'required' => [ 'hideLabels', '=', '' ],
		];

		$this->controls['labelMargin'] = [
			'tab'         => 'content',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'css'         => [
				[
					'property' => 'margin',
					'selector' => 'label',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => 5,
				'left'   => 0,
			],
			'required'    => [ 'hideLabels', '=', '' ],
		];

		// FIELDS

		$this->controls['fieldsSeparator'] = [
			'tab'   => 'content',
			'type'  => 'separator',
			'label' => esc_html__( 'Fields', 'bricks' ),
		];

		$this->controls['fieldTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => 'input:not([type=submit])',
				],
				[
					'property' => 'font',
					'selector' => 'select',
				],
				[
					'property' => 'font',
					'selector' => '.select2-selection__rendered',
				],
				[
					'property' => 'font',
					'selector' => 'textarea',
				],
			],
		];

		$this->controls['placeholderTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Placeholder typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '::placeholder',
				],
			],
		];

		$this->controls['hideAdditionalInformation'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Hide additional information', 'bricks' ),
			'type'  => 'checkbox',
			'css'   => [
				[
					'selector' => '.woocommerce-additional-fields',
					'property' => 'display',
					'value'    => 'none',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		// Get checkout object.
		$checkout = WC()->checkout();

		if ( ! $checkout->get_checkout_fields() ) {
			return $this->render_element_placeholder( [ 'title' => esc_html__( 'No checkout fields defined.', 'bricks' ) ] );
		}

		// Hooks
		add_filter( 'woocommerce_form_field_args', [ $this, 'woocommerce_form_field_args' ], 10, 3 );
		?>

		<div <?php echo $this->render_attributes( '_root' ); ?>>
			<?php
			/**
			 * Render WooCommerce part only on front end (avoid adding HTML outside of root node)
			 * As sibling of #customer_details like native WooCommerce (#862k74j78)
			 *
			 * @since 1.8.5
			 */
			if ( ! bricks_is_builder() && ! bricks_is_builder_call() ) {
				do_action( 'woocommerce_checkout_before_customer_details' );
			}
			?>
			<div class="col2-set" id="customer_details">
				<div class="col-1">
					<?php
					add_filter( 'woocommerce_form_field', [ $this, 'remove_checkout_billing_fields' ], 10, 2 );
					do_action( 'woocommerce_checkout_billing' );
					remove_filter( 'woocommerce_form_field', [ $this, 'remove_checkout_billing_fields' ], 10, 2 );
					?>
				</div>

				<div class="col-2">
					<?php
					add_filter( 'woocommerce_form_field', [ $this, 'remove_checkout_shipping_fields' ], 10, 2 );
					do_action( 'woocommerce_checkout_shipping' );
					remove_filter( 'woocommerce_form_field', [ $this, 'remove_checkout_shipping_fields' ], 10, 2 );
					?>
				</div>
			</div>
			<?php
			// Render WooCommerce part only on front end (avoid adding HTML outside of root node)
			if ( ! bricks_is_builder() && ! bricks_is_builder_call() ) {
				do_action( 'woocommerce_checkout_after_customer_details' );
			}
			?>
		</div>

		<?php
		// Remove hooks
		remove_filter( 'woocommerce_form_field_args', [ $this, 'woocommerce_form_field_args' ], 10, 3 );
	}

	public function remove_checkout_billing_fields( $field, $key ) {
		return isset( $this->settings['removeBillingFields'] ) && in_array( $key, $this->settings['removeBillingFields'] ) ? '' : $field;
	}

	public function remove_checkout_shipping_fields( $field, $key ) {
		return isset( $this->settings['removeShippingFields'] ) && in_array( $key, $this->settings['removeShippingFields'] ) ? '' : $field;
	}

	public function woocommerce_form_field_args( $args, $key, $value ) {
		if ( ! isset( $this->settings['hideLabels'] ) ) {
			return $args;
		}

		if ( empty( $args['placeholder'] ) ) {
			$args['placeholder'] = $args['label'];
		}

		return $args;
	}
}
