<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Notice extends Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-notice';
	public $icon     = 'ti-announcement';

	public function get_label() {
		return esc_html__( 'Notice', 'bricks' );
	}

	public function get_keywords() {
		return [ 'alert', 'message', 'woo' ];
	}

	public function set_control_groups() {
		$this->control_groups['error'] = [
			'title' => esc_html__( 'Type', 'bricks' ) . ' - ' . esc_html__( 'Error', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['success'] = [
			'title' => esc_html__( 'Type', 'bricks' ) . ' - ' . esc_html__( 'Success', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['notice'] = [
			'title' => esc_html__( 'Type', 'bricks' ) . ' - ' . esc_html__( 'Notice', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['info'] = [
			'tab'     => 'content',
			'type'    => 'info',
			'content' => esc_html__( 'Style notices globally under Settings > Theme Styles > WooCommerce - Notice.', 'bricks' ),
		];

		$this->controls['previewType'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Preview notice type', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'all'     => esc_html__( 'All', 'bricks' ),
				'success' => esc_html__( 'Success', 'bricks' ),
				'notice'  => esc_html__( 'Notice', 'bricks' ),
				'error'   => esc_html__( 'Error', 'bricks' ),
			],
			'default'     => 'all',
			'inline'      => true,
			'clearable'   => false,
			'description' => esc_html__( 'Only applied in builder and template preview.', 'bricks' ),
		];

		$sections = [
			'layout' => [
				'success' => '.woocommerce-message',
				'notice'  => '.woocommerce-info',
				'error'   => '.woocommerce-error',
			],
			'link'   => [
				'success' => '.woocommerce-message a, .woocommerce-message a.button',
				'notice'  => '.woocommerce-info a, .woocommerce-info a.button',
				'error'   => '.woocommerce-error a, .woocommerce-error a.button',
			],
		];

		foreach ( $sections as $section => $types ) {
			foreach ( $types as $type => $selector ) {
				// successMargin, successLinkMargin, noticeMargin, noticeLinkMargin, errorMargin, errorLinkMargin
				$control_prefix = $type;

				if ( $section === 'link' ) {
					$control_prefix = "{$control_prefix}Link";

					$this->controls[ $control_prefix . 'Separator' ] = [
						'tab'   => 'content',
						'label' => esc_html__( 'Link', 'bricks' ),
						'group' => $type,
						'type'  => 'separator',
					];
				}

				$this->controls[ $control_prefix . 'Margin' ] = [
					'tab'   => 'content',
					'label' => esc_html__( 'Margin', 'bricks' ),
					'group' => $type,
					'type'  => 'spacing',
					'css'   => [
						[
							'property' => 'margin',
							'selector' => $selector,
						],
					],
				];

				$this->controls[ $control_prefix . 'Padding' ] = [
					'tab'   => 'content',
					'label' => esc_html__( 'Padding', 'bricks' ),
					'group' => $type,
					'type'  => 'spacing',
					'css'   => [
						[
							'property' => 'padding',
							'selector' => $selector,
						],
					],
				];

				$this->controls[ $control_prefix . 'BackgroundColor' ] = [
					'tab'   => 'content',
					'label' => esc_html__( 'Background color', 'bricks' ),
					'group' => $type,
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
							'selector' => $selector,
						],
					],
				];

				$this->controls[ $control_prefix . 'Border' ] = [
					'tab'   => 'content',
					'label' => esc_html__( 'Border', 'bricks' ),
					'group' => $type,
					'type'  => 'border',
					'css'   => [
						[
							'property' => 'border',
							'selector' => $selector,
						],
					],
				];

				$this->controls[ $control_prefix . 'BoxShadow' ] = [
					'tab'   => 'content',
					'label' => esc_html__( 'Box shadow', 'bricks' ),
					'group' => $type,
					'type'  => 'box-shadow',
					'css'   => [
						[
							'property' => 'box-shadow',
							'selector' => $selector,
						],
					],
				];

				$this->controls[ $control_prefix . 'Typography' ] = [
					'tab'   => 'content',
					'group' => $type,
					'label' => esc_html__( 'Typography', 'bricks' ),
					'type'  => 'typography',
					'css'   => [
						[
							'property' => 'font',
							'selector' => $selector,
						],
					],
				];
			}
		}
	}

	public function render() {
		$notices = $this->get_woo_notices_or_populate_builder_notices();

		$this->set_attribute( '_root', 'class', 'woocommerce-notices-wrapper' );

		echo "<div {$this->render_attributes( '_root' )}>" . $notices . '</div>';
	}

	/**
	 * Populate some notices for the builder and template preview or return the WooCommerce notices
	 *
	 * @return string
	 */
	public function get_woo_notices_or_populate_builder_notices() {
		// In Rest API, wc frontend function is not available (@since 1.9.4)
		if ( ! function_exists( 'wc_print_notices' ) || ! function_exists( 'wc_clear_notices' ) ) {
			return '';
		}

		// Return & render actual WooCommerce notices on the frontend
		if (
			! bricks_is_builder_main() &&
			! bricks_is_builder_iframe() &&
			! bricks_is_builder_call() &&
			! isset( $_GET['bricks_preview'] )
		) {
			return wc_print_notices( true );
		}

		$notices = '';

		// To clear any notices that may have been set by WooCommerce as we are populating the builder with some dummy notices
		wc_clear_notices();

		$dummy_messages = [
			'success' => [
				[
					'text' => '<a href="#" tabindex="1" class="button wc-forward wp-element-button">View cart</a> This is a success notice.',
					'data' => [],
				],
			],

			'notice'  => [
				[
					'text' => 'This is a notice. <a href="#" class="showcoupon">Click here to enter your code</a>',
					'data' => [],
				],
			],

			'error'   => [
				[
					'text' => 'This is an error notice. <a href="#" class="button wc-forward wp-element-button">View cart</a>',
					'data' => [],
				],
				[
					'text' => '<strong>Billing Postcode / ZIP</strong> is a required field.',
					'data' => [ 'id' => 'billing_postcode' ],
				],
				[
					'text' => '<strong>Billing Phone</strong> is a required field.',
					'data' => [ 'id' => 'billing_phone' ],
				],
			],
		];

		$preview_type = ! empty( $this->settings['previewType'] ) ? $this->settings['previewType'] : '';

		switch ( $preview_type ) {
			case 'all':
				break;

			case 'notice':
				unset( $dummy_messages['success'], $dummy_messages['error'] );
				break;

			case 'error':
				unset( $dummy_messages['success'], $dummy_messages['notice'] );
				break;

			default:
			case 'success':
				unset( $dummy_messages['notice'], $dummy_messages['error'] );
				break;
		}

		foreach ( $dummy_messages as $type => $messages ) {
			foreach ( $messages as $message ) {
				wc_add_notice( $message['text'], $type, $message['data'] );
			}
		}

		ob_start();
		wc_print_notices();
		return ob_get_clean();
	}
}
