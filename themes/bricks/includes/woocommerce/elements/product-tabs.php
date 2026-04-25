<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Tabs extends Element {
	public $category     = 'woocommerce_product';
	public $name         = 'product-tabs';
	public $icon         = 'ti-layout-tab';
	public $css_selector = '.woocommerce-tabs';
	public $rerender     = false;

	public function get_label() {
		return esc_html__( 'Product tabs', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['tabs'] = [
			'title' => esc_html__( 'Tabs', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['activeTab'] = [
			'title' => esc_html__( 'Active Tab', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['panel'] = [
			'title' => esc_html__( 'Panel', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// WRAPPER

		$this->controls['direction'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Direction', 'bricks' ),
			'type'     => 'direction',
			'css'      => [
				[
					'property' => 'flex-direction',
				],
			],
			'inline'   => true,
			'rerender' => false,
		];

		// TABS

		$this->controls['tabsDirection'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Direction', 'bricks' ),
			'type'     => 'direction',
			'css'      => [
				[
					'selector' => '.wc-tabs',
					'property' => 'flex-direction',
				],
			],
			'inline'   => true,
			'rerender' => false,
		];

		$this->controls['tabsJustifyContent'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Alignment', 'bricks' ),
			'type'     => 'justify-content',
			'css'      => [
				[
					'selector' => '.wc-tabs',
					'property' => 'justify-content',
				],
			],
			'required' => [ 'tabsDirection', '!=', [ 'column', 'column-reverse' ] ],
		];

		$this->controls['tabsPadding'] = [
			'tab'         => 'content',
			'group'       => 'tabs',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'selector' => '.wc-tabs li',
					'property' => 'padding',
				],
			],
			'placeholder' => [
				'top'    => 15,
				'right'  => 30,
				'bottom' => 15,
				'left'   => 30,
			],
		];

		$this->controls['tabsTypography'] = [
			'tab'   => 'content',
			'group' => 'tabs',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.wc-tabs',
					'property' => 'font',
				],
			],
		];

		$this->controls['tabsBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'tabs',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.wc-tabs',
					'property' => 'background-color',
				],
			],
		];

		$this->controls['tabsBorder'] = [
			'tab'   => 'content',
			'group' => 'tabs',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'selector' => '.wc-tabs',
					'property' => 'border',
				],
			],
		];

		// ACTIVE TAB

		$this->controls['tabActiveTypography'] = [
			'tab'   => 'content',
			'group' => 'activeTab',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.wc-tabs .active',
					'property' => 'font',
				],
			],
		];

		$this->controls['tabActiveBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'activeTab',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.wc-tabs .active',
					'property' => 'background-color',
				],
			],
		];

		// PANEL

		$this->controls['panelPadding'] = [
			'tab'         => 'content',
			'group'       => 'panel',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'selector' => '.panel',
					'property' => 'padding',
				],
			],
			'placeholder' => [
				'top'    => 30,
				'right'  => 30,
				'bottom' => 30,
				'left'   => 30,
			],
		];

		$this->controls['panelTypography'] = [
			'tab'   => 'content',
			'group' => 'panel',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.panel',
					'property' => 'color',
				],
			],
		];

		$this->controls['panelBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'panel',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.panel',
					'property' => 'background-color',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		global $product;

		$product = wc_get_product( $this->post_id );

		// When using REST API we need to set the global $post to prevent PHP errors on woocommerce_default_product_tabs() - since 1.5
		if ( bricks_is_builder_call() ) {
			global $post;
			$post = get_post( $this->post_id );
		}

		if ( empty( $product ) ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		wc_get_template( 'single-product/tabs/tabs.php' );

		echo '</div>';
	}
}
