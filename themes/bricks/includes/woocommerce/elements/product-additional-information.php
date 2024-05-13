<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Additional_Information extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-additional-information';
	public $icon     = 'ti-info';

	public function get_label() {
		return esc_html__( 'Product additional information', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['heading'] = [
			'title' => esc_html__( 'Heading', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['label'] = [
			'title' => esc_html__( 'Label', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['details'] = [
			'title' => esc_html__( 'Details', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// HEADING

		// NOTE: Heading text in builder misses last updated character change
		$this->controls['headingText'] = [
			'tab'     => 'content',
			'group'   => 'heading',
			'label'   => esc_html__( 'Heading', 'bricks' ),
			'type'    => 'text',
			'default' => esc_html__( 'Additional information', 'bricks' ),
		];

		$this->controls['headingTypography'] = [
			'tab'   => 'content',
			'group' => 'heading',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => 'h2',
					'property' => 'font',
				],
			],
		];

		// LABEL

		$this->controls['labelWidth'] = [
			'tab'   => 'content',
			'group' => 'label',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'selector' => 'th',
					'property' => 'width',
				],
			],
		];

		$this->controls['labelTypography'] = [
			'tab'   => 'content',
			'group' => 'label',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => 'th',
					'property' => 'font',
				],
			],
		];

		// DETAILS

		$this->controls['detailsWidth'] = [
			'tab'   => 'content',
			'group' => 'details',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'selector' => 'td',
					'property' => 'width',
				],
			],
		];

		$this->controls['detailsTypography'] = [
			'tab'   => 'content',
			'group' => 'details',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => 'td',
					'property' => 'font',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

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

		// Get additional information
		ob_start();

		wc_get_template( 'single-product/tabs/additional-information.php' );

		$additional_information = trim( ob_get_clean() );

		if ( ! $additional_information ) {
			return $this->render_element_placeholder( [ 'title' => esc_html__( 'No additional information to show.', 'bricks' ) ] );
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		if ( ! empty( $settings['headingText'] ) ) {
			echo "<h2>{$settings['headingText']}</h2>";
		}

		echo $additional_information;

		echo '</div>';
	}
}
