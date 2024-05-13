<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Related extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-related';
	public $icon     = 'ti-layers';

	public function get_label() {
		return esc_html__( 'Related products', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['button'] = [
			'title' => esc_html__( 'Button', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['headingText'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Heading', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['headingTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Heading typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'selector' => '.related.products > h2',
					'property' => 'font',
				],
			],
			'required' => [ 'headingText', '!=', '' ],
		];

		$this->controls['count'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Max. products', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 4,
			'placeholder' => 4,
		];

		$this->controls['columns'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Columns', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 4,
			'css'         => [
				[
					'selector'  => '.products',
					'property'  => 'grid-template-columns',
					'value'     => 'repeat(%s, 1fr)', // NOTE: Undocumented (@since 1.3)
					'important' => true,
				],
			],
			'placeholder' => 4,
		];

		$this->controls['orderby'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Order by', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['queryOrderBy'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Random', 'bricks' )
		];

		$this->controls['order'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Order', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['queryOrder'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Descending', 'bricks' )
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Gap', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '.products',
				],
			],
			'placeholder' => '30px',
		];

		// @since 1.5.7
		$this->controls['textAlign'] = [
			'label'   => esc_html__( 'Align', 'bricks' ),
			'type'    => 'text-align',
			'inline'  => true,
			'exclude' => [ 'justify' ],
			'css'     => [
				[
					'property' => 'text-align',
					'selector' => '.product',
				],
			],
		];

		// @since 1.5.7
		$this->controls['imageHeight'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Image height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'height',
					'selector' => '.product img',
				],
			],
		];

		// BUTTON

		$this->controls['buttonPadding'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'selector' => '.button',
					'property' => 'padding',
				],
			],
		];

		$this->controls['buttonBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.button',
					'property' => 'background-color',
				],
			],
		];

		$this->controls['buttonBorder'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.button',
				],
			],
		];

		$this->controls['buttonTypography'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.button',
					'property' => 'font',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		global $product;

		$product = wc_get_product( $this->post_id );

		if ( ! $product ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'No product selected', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		$args = [
			'posts_per_page' => ! empty( $settings['count'] ) ? $settings['count'] : 4,
			'columns'        => ! empty( $settings['columns'] ) ? $settings['columns'] : 4,
			'orderby'        => ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'rand',
			'order'          => ! empty( $settings['order'] ) ? $settings['order'] : 'DESC',
		];

		$related_products = wc_get_related_products( $product->get_id(), $args['posts_per_page'], $product->get_upsell_ids() );

		if ( ! $related_products ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No related products to show.', 'bricks' ),
				]
			);
		}

		add_filter( 'woocommerce_product_related_products_heading', [ $this, 'render_heading' ] );

		echo "<div {$this->render_attributes( '_root' )}>";

		woocommerce_related_products( $args );

		echo '</div>';

		remove_filter( 'woocommerce_product_related_products_heading', [ $this, 'render_heading' ] );
	}

	public function render_heading( $heading = '' ) {
		return ! empty( $this->settings['headingText'] ) ? $this->settings['headingText'] : false;
	}
}
