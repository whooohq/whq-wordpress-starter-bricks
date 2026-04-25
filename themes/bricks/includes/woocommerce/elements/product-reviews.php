<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Reviews extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-reviews';
	public $icon     = 'ti-pencil-alt';
	public $scripts  = [ 'bricksWooStarRating' ];

	public function get_label() {
		return esc_html__( 'Product reviews', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['form'] = [
			'title' => esc_html__( 'Form', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['review'] = [
			'title' => esc_html__( 'Review', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['stars'] = [
			'title' => esc_html__( 'Stars', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['productTabsInfo'] = [
			'tab'     => 'content',
			'content' => esc_html__( 'Make sure not to use the "Product tabs" element on the same page.', 'bricks' ),
			'type'    => 'info',
		];

		// FORM

		$this->controls['formTitleTypography'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Title', 'bricks' ) . ': ' . esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.comment-reply-title',
					'property' => 'font',
				],
			],
		];

		$this->controls['formLabelTypography'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Label', 'bricks' ) . ': ' . esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => 'form label',
					'property' => 'font',
				],
			],
		];

		// REVIEW

		$this->controls['authorTypography'] = [
			'tab'   => 'content',
			'group' => 'review',
			'label' => esc_html__( 'Author', 'bricks' ) . ': ' . esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.meta .woocommerce-review__author',
					'property' => 'font',
				],
			],
		];

		$this->controls['dateTypography'] = [
			'tab'   => 'content',
			'group' => 'review',
			'label' => esc_html__( 'Date', 'bricks' ) . ': ' . esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.meta .woocommerce-review__published-date',
					'property' => 'font',
				],
			],
		];

		$this->controls['descriptionTypography'] = [
			'tab'   => 'content',
			'group' => 'review',
			'label' => esc_html__( 'Description', 'bricks' ) . ': ' . esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.description',
					'property' => 'font',
				],
			],
		];

		// STARS

		$this->controls['starsSize'] = [
			'tab'   => 'content',
			'group' => 'stars',
			'label' => esc_html__( 'Size', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				// Form
				[
					'selector' => '.stars',
					'property' => 'font-size',
				],
				[
					'selector' => '.stars a',
					'property' => 'height',
				],
				[
					'selector' => '.stars a',
					'property' => 'width',
				],

				// Review
				[
					'selector' => '.star-rating span',
					'property' => 'padding-top',
				],
				[
					'selector' => '.star-rating',
					'property' => 'font-size',
				],
				[
					'selector' => '.star-rating',
					'property' => 'height',
				],
				[
					'selector' => '.star-rating',
					'property' => 'width',
					'value'    => 'calc(5 * %s)',
				],
			],
		];

		$this->controls['starsBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'stars',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => 'form .stars a::before, form .stars.selected a.active ~ a::before',
					'property' => 'color',
				],
				[
					'selector' => '.star-rating',
					'property' => 'color',
				],
			],
		];

		$this->controls['starsFillColor'] = [
			'tab'   => 'content',
			'group' => 'stars',
			'label' => esc_html__( 'Fill color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => 'form .stars.selected a.active::before, form .stars.selected a:not(.active)::before',
					'property' => 'color',
				],
				[
					'selector' => '.star-rating span::before',
					'property' => 'color',
				],
			],
		];
	}

	public function render() {
		global $product, $post;

		$product = wc_get_product( $this->post_id );

		// When using REST API we need to set the global $post to prevent PHP errors
		if ( bricks_is_builder_call() ) {
			$post = get_post( $this->post_id );

			/**
			 * WC_Template_Loader not initialized in the builder
			 *
			 * comments_template( '/woocommerce/single-product-reviews.php' ) used below will not work without this filter.
			 *
			 * @since 1.9.2
			 */
			add_filter( 'comments_template', [ 'WC_Template_Loader', 'comments_template_loader' ] );
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

		comments_template( '/woocommerce/single-product-reviews.php' );

		echo '</div>';
	}
}
