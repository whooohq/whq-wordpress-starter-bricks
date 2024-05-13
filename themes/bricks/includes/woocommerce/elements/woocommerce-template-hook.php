<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Template_Hook extends Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-template-hook';
	public $icon     = 'fas fa-anchor';

	public function get_label() {
		return esc_html__( 'WooCommerce Template Hook', 'bricks' );
	}

	public function get_keywords() {
		return [ 'hook', 'woo', 'template' ];
	}

	public function set_controls() {
		$this->controls['template'] = [
			'tab'       => 'content',
			'label'     => esc_html__( 'Template', 'bricks' ),
			'type'      => 'select',
			'options'   => [
				'content-single-product' => esc_html__( 'Single product template', 'bricks' ),
				'content-product'        => esc_html__( 'Shop template', 'bricks' ),
			],
			'default'   => 'content-single-product',
			'clearable' => false,
			'inline'    => true,
		];

		// TODO: Function name changed, must recheck this element in the future if this element is still needed
		$supported_template_hooks = Woocommerce_Helpers::repeated_wc_template_hooks();

		$supported_single_product_hooks = [];
		// Populate single product hooks options array for select control
		if ( isset( $supported_template_hooks['content-single-product'] ) ) {
			$supported_single_product_hooks = array_keys( $supported_template_hooks['content-single-product'] );
			$supported_single_product_hooks = array_combine(
				$supported_single_product_hooks,
				array_map(
					function( $hook_details ) {
						return $hook_details['label'];
					},
					$supported_template_hooks['content-single-product']
				)
			);
		}

		$supported_shop_hooks = [];
		// Populate shop hooks options array for select control
		if ( isset( $supported_template_hooks['content-product'] ) ) {
			$supported_shop_hooks = array_keys( $supported_template_hooks['content-product'] );
			$supported_shop_hooks = array_combine(
				$supported_shop_hooks,
				array_map(
					function( $hook_details ) {
						return $hook_details['label'];
					},
					$supported_template_hooks['content-product']
				)
			);
		}

		$this->controls['singleProductHook'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Hook', 'bricks' ),
			'type'        => 'select',
			'options'     => $supported_single_product_hooks,
			'inline'      => true,
			'placeholder' => esc_html( 'Before single product', 'bricks' ),
			'required'    => [ 'template', '=', 'content-single-product' ]
		];

		$this->controls['shopHook'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Hook', 'bricks' ),
			'type'        => 'select',
			'options'     => $supported_shop_hooks,
			'inline'      => true,
			'placeholder' => esc_html( 'Before shop loop item', 'bricks' ),
			'required'    => [ 'template', '=', 'content-product' ],
		];

		$this->controls['showTips'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Show tips', 'bricks' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'A list of native actions on the selected hook that will be removed by Bricks.', 'bricks' ),
		];
	}

	public function render() {
		$settings = $this->settings;
		$template = $settings['template'];

		if ( empty( $template ) || ! isset( $template ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No template selected.', 'bricks' ),
				]
			);
		}

		$hook = ( 'content-single-product' === $template ) ? $settings['singleProductHook'] : $settings['shopHook'];

		if ( empty( $hook ) || ! isset( $hook ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No hook selected.', 'bricks' ),
				]
			);
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		// Remove native actions to avoid duplicate content
		Woocommerce_Helpers::execute_actions_in_wc_template( $template, 'remove', $hook );

		$output  = $this->maybe_show_tips();
		$output .= bricks_render_dynamic_data( "{do_action:{$hook}}" );

		if ( $output ) {
			echo $output;
		}

		// Restore native actions
		Woocommerce_Helpers::execute_actions_in_wc_template( $template, 'add', $hook );

		echo '</div>';
	}

	/**
	 * Show native actions that fired on this hook in builder mode only.
	 *
	 * @return string
	 */
	private function maybe_show_tips() {
		if ( ! bricks_is_builder_main() && ! bricks_is_builder_iframe() && ! bricks_is_builder_call() ) {
			return;
		}

		$settings = $this->settings;

		if ( empty( $settings['showTips'] ) ) {
			return;
		}

		$template = $settings['template'];
		$hook     = ( 'content-single-product' === $template ) ? $settings['singleProductHook'] : $settings['shopHook'];
		$hooks    = Woocommerce_Helpers::repeated_wc_template_hooks( $template );

		// No hooks found or no actions key found
		if ( empty( $hooks ) || ! isset( $hooks[ $hook ] ) || ! isset( $hooks[ $hook ]['actions'] ) ) {
			return;
		}

		$actions = $hooks[ $hook ]['actions'];

		$output = '<div class="bricks-woocommerce-template-hook-tips">';

		if ( count( $actions ) > 0 ) {
			$output .= '<h4>' . esc_html__( 'Native actions on this hook', 'bricks' ) . '</h4>';
			$output .= '<ul>';

			foreach ( $actions as $action ) {
				$output .= '<li>' . $action['callback'] . '</li>';
			}

			$output .= '</ul>';
		} else {
			$output .= '<h4>' . esc_html__( 'No native actions on this hook', 'bricks' ) . '</h4>';
		}

		$output .= '</div>';

		return $output;
	}
}
