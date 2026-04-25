<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woo_Element extends Element {
	public $category = 'woocommerce';

	/**
	 * Generate standard controls for:
	 * margin, padding, background-color, border, box-shadow, typography
	 *
	 * @param string $field_key - The field key to use for the control.
	 * @param string $selector - The selector to apply the control to.
	 * @param string $types (optional) - Array of control types to generate controls for.
	 *
	 * @return array
	 */
	protected function generate_standard_controls( $field_key, $selector, $types = [] ) {
		if ( ! $field_key || ! $selector ) {
			return [];
		}

		$controls = [
			'margin'           => [
				'suffix' => 'Margin',
				'label'  => esc_html__( 'Margin', 'bricks' ),
				'type'   => 'spacing',
				'css'    => [
					[
						'property' => 'margin',
						'selector' => $selector,
					],
				],
			],

			'padding'          => [
				'suffix' => 'Padding',
				'label'  => esc_html__( 'Padding', 'bricks' ),
				'type'   => 'spacing',
				'css'    => [
					[
						'property' => 'padding',
						'selector' => $selector,
					],
				],
			],

			'background-color' => [
				'suffix' => 'BackgroundColor',
				'label'  => esc_html__( 'Background color', 'bricks' ),
				'type'   => 'color',
				'css'    => [
					[
						'property' => 'background-color',
						'selector' => $selector,
					],
				],
			],

			'border'           => [
				'suffix' => 'Border',
				'label'  => esc_html__( 'Border', 'bricks' ),
				'type'   => 'border',
				'css'    => [
					[
						'property' => 'border',
						'selector' => $selector,
					],
				],
			],

			'box-shadow'       => [
				'suffix' => 'BoxShadow',
				'label'  => esc_html__( 'Box shadow', 'bricks' ),
				'type'   => 'box-shadow',
				'css'    => [
					[
						'property' => 'box-shadow',
						'selector' => $selector,
					],
				],
			],

			'typography'       => [
				'suffix' => 'Typography',
				'label'  => esc_html__( 'Typography', 'bricks' ),
				'type'   => 'typography',
				'css'    => [
					[
						'property' => 'font',
						'selector' => $selector,
					],
				],
			],
		];

		// Get controls for specified types
		if ( ! empty( $types ) ) {
			$controls = array_intersect_key( $controls, array_flip( $types ) );
		}

		// Build final controls
		$final_controls = [];

		foreach ( $controls as $key => $control ) {
			$final_controls[ $field_key . $control['suffix'] ] = $control;
		}

		return $final_controls;
	}

	/**
	 * Insert group key to controls
	 *
	 * @param array  $controls
	 * @param string $group
	 *
	 * @return array
	 */
	protected function controls_grouping( $controls, $group ) {
		if ( empty( $group ) || empty( $controls ) || ! is_array( $controls ) ) {
			return $controls;
		}

		foreach ( $controls as $key => $control ) {
			$controls[ $key ]['group'] = $group;
		}

		return $controls;
	}

	/**
	 * Woo Phase 3
	 */
	protected function get_woo_form_fields_controls( $selector = '' ) {
		$controls = [];

		$controls['fieldsAlignItems'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Align items', 'bricks' ),
			'type'    => 'align-items',
			'inline'  => true,
			'css'     => [
				[
					'property' => 'align-items',
					'selector' => $selector,
				],
			],
			'exclude' => [ 'stretch' ],
		];

		$controls['fieldsWidth'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => '.password-input, .woocommerce-Input',
				],
			],
		];

		$controls['fieldsGap'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'gap',
					'selector' => $selector,
				],
			],
		];

		$controls['hideLabels'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Hide labels', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['hidePlaceholders'] = [
			'tab'   => 'content',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Hide placeholders', 'bricks' ),
		];

		$controls['labelTypography'] = [
			'tab'      => 'content',
			'group'    => 'fields',
			'label'    => esc_html__( 'Label typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'label[for]', // Skip rememberme label
				],
			],
			'required' => [ 'hideLabels', '=', false ],
		];

		$controls['placeholderTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Placeholder typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '::placeholder',
				],
				[
					'property' => 'font',
					'selector' => 'select',
				],
			],
			'required' => [ 'hidePlaceholders', '=', false ],
		];

		// FIELD

		$controls['fieldsSep'] = [
			'tab'   => 'content',
			'type'  => 'separator',
			'label' => esc_html__( 'Field', 'bricks' ),
		];

		/**
		 * Generate standard controls for .woocommerce-Input
		 * (typography, margin, padding, background-color, border, box-shadow)
		 *
		 * 'input' selector required for edit address form, which has no .woocommerce-Input.
		 */
		$field_key         = 'fieldsInput';
		$selector          = 'input, .woocommerce-Input, .select2-selection.select2-selection--single';
		$standard_controls = $this->generate_standard_controls( $field_key, $selector );

		$controls = array_merge( $controls, $standard_controls );

		return $controls;
	}

	/**
	 * Woo Phase 3
	 */
	protected function get_woo_form_submit_controls() {
		$field_key         = 'submitButton';
		$selector          = 'button[type=submit]';
		$standard_controls = $this->generate_standard_controls( $field_key, $selector );

		$controls['submitButtonWidth'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => $selector,
				],
			],
		];

		// Merge standard controls
		$controls = array_merge( $controls, $standard_controls );

		return $controls;
	}

	protected function get_woo_form_fieldset_controls() {
		$field_key = 'fieldset';
		$selector  = 'fieldset';

		$standard_controls = $this->generate_standard_controls( $field_key, $selector );

		$controls['fieldsetGap'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'gap',
					'selector' => $selector,
				],
			],
		];

		// merge standard controls
		$controls = array_merge( $controls, $standard_controls );

		return $controls;
	}

	/**
	 * Get order
	 *
	 * Get order from 'previewOrderId' setting
	 *
	 * Default: Last order
	 *
	 * @return WC_Order|false
	 */
	protected function get_order() {
		if ( bricks_is_builder() || bricks_is_builder_call() || Helpers::is_bricks_template( get_the_ID() ) ) {
			$settings = $this->settings;
			$order    = false;

			$preview_order_id = ! empty( $settings['previewOrderId'] ) ? absint( $settings['previewOrderId'] ) : false;

			// Preview: Get order from 'previewOrderId'
			if ( $preview_order_id ) {
				$order = wc_get_order( $preview_order_id );
			}

			// No order found or no preview order ID, get the last order from orders
			if ( ! $order ) {
				$orders = wc_get_orders(
					[
						'limit' => 1,
					]
				);
				$order  = $orders ? $orders[0] : false;
			}

		} else {
			global $wp;
			$order_id = ( isset( $wp->query_vars['view-order'] ) ? absint( $wp->query_vars['view-order'] ) : 0 );
			$order    = wc_get_order( $order_id );
		}

		return $order;
	}
}
