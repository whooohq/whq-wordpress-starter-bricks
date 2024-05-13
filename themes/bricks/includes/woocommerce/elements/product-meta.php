<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Meta extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-meta';
	public $icon     = 'ti-receipt';

	public function get_label() {
		return esc_html__( 'Product meta', 'bricks' );
	}

	public function set_controls() {
		$this->controls['direction'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Direction', 'bricks' ),
			'type'        => 'direction',
			'css'         => [
				[
					'property' => 'flex-direction',
				],
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Horizontal', 'bricks' ),
		];

		$this->controls['gutter'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '',
				]
			],
			'placeholder' => 0,
		];

		$this->controls['separator'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Separator', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
			'small'  => true,
		];

		$this->controls['separatorColor'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Separator color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'selector' => '.separator',
					'property' => 'color',
				],
			],
			'required' => [ 'separator', '!=', '' ],
		];

		$this->controls['prefixTypography'] = [
			'tab'   => 'content',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ) . ': ' . esc_html__( 'Prefix', 'bricks' ),
			'css'   => [
				[
					'selector' => '.prefix',
					'property' => 'font',
				],
			],
		];

		$this->controls['suffixTypography'] = [
			'tab'   => 'content',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ) . ': ' . esc_html__( 'Suffix', 'bricks' ),
			'css'   => [
				[
					'selector' => '.suffix',
					'property' => 'font',
				],
			],
		];

		$this->controls['linkTypography'] = [
			'tab'   => 'content',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ) . ': ' . esc_html__( 'Link', 'bricks' ),
			'css'   => [
				[
					'selector' => 'a',
					'property' => 'font',
				],
			],
		];

		// FIELDS

		$this->controls['fields'] = [
			'tab'           => 'content',
			'type'          => 'repeater',
			'label'         => esc_html__( 'Fields', 'bricks' ),
			'titleProperty' => 'dynamicData',
			'fields'        => [
				'dynamicData' => [
					'label'          => esc_html__( 'Product meta', 'bricks' ),
					'type'           => 'text',
					'hasDynamicData' => 'text',
					'titleProperty'  => 'dynamicData',
				],

				'prefix'      => [
					'label'  => esc_html__( 'Prefix', 'bricks' ),
					'type'   => 'text',
					'inline' => true,
				],

				'suffix'      => [
					'label'  => esc_html__( 'Suffix', 'bricks' ),
					'type'   => 'text',
					'inline' => true,
				],
			],

			'default'       => [
				[
					'dynamicData' => '{woo_product_sku}',
					'id'          => Helpers::generate_random_id( false ),
					'prefix'      => 'SKU: ',
				],
				[
					'dynamicData' => '{post_terms_product_cat}',
					'id'          => Helpers::generate_random_id( false ),
					'prefix'      => 'Categories: ',
				],
				[
					'dynamicData' => '{post_terms_product_tag}',
					'id'          => Helpers::generate_random_id( false ),
					'prefix'      => 'Tags: ',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( empty( $settings['fields'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No meta data selected.', 'bricks' ),
				]
			);
		}

		global $product;

		$product = wc_get_product( $this->post_id );

		if ( empty( $product ) ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		$field_data = [];

		foreach ( $settings['fields'] as $index => $field ) {
			$value = ! empty( $field['dynamicData'] ) ? trim( $field['dynamicData'] ) : '';

			$value = bricks_render_dynamic_data( $value, $this->post_id );

			if ( ! $value ) {
				continue;
			}

			$field_html = '<span class="item">';

			if ( ! empty( $field['prefix'] ) ) {
				$field_html .= '<span class="prefix">' . $field['prefix'] . '</span>';
			}

			$field_html .= '<span class="text">' . $value . '</span>';

			if ( ! empty( $field['suffix'] ) ) {
				$field_html .= '<span class="suffix">' . $field['suffix'] . '</span>';
			}

			$field_html .= '</span>';

			if ( ! empty( $field_html ) ) {
				$field_data[] = $field_html;
			}
		}

		// Add Woo class "product_meta" to enable the Woo fragments (e.g. SKU for variations)
		$this->set_attribute( '_root', 'class', 'product_meta' );

		// Render HTML
		// @see: wc_get_template( 'single-product/meta.php' );

		echo "<div {$this->render_attributes( '_root' )}>";

		do_action( 'woocommerce_product_meta_start' );

		$separator = ! empty( $settings['separator'] ) ? '<span class="separator">' . $settings['separator'] . '</span>' : '';

		echo join( $separator, $field_data );

		do_action( 'woocommerce_product_meta_end' );

		echo '</div>';
	}
}
