<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Rating extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-rating';
	public $icon     = 'ti-medall';

	public function get_label() {
		return esc_html__( 'Product rating', 'bricks' );
	}

	public function set_controls() {
		$this->controls['starColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Star color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.star-rating span::before',
					'property' => 'color',
				],
			],
		];

		$this->controls['emptyStarColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Empty star color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.star-rating::before',
					'property' => 'color',
				],
			],
		];

		/**
		 * Show Reviews Link
		 *
		 * Disable the output of reviews link instead of hiding it (@see Woocommerce_Helpers::render_product_rating)
		 *
		 * @since 1.8
		 */
		$this->controls['hideReviewsLink'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Hide reviews link', 'bricks' ),
			'type'  => 'checkbox',
		];

		// NO RATINGS

		$this->controls['noRatings'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'No ratings', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['noRatingsText'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Text', 'bricks' ),
			'type'     => 'text',
			'required' => [ 'noRatingsStars', '=', '' ],
		];

		$this->controls['noRatingsStars'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Show empty stars', 'bricks' ),
			'type'  => 'checkbox',
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( ! wc_review_ratings_enabled() ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Product ratings are disabled.', 'bricks' ),
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

		$show_empty_stars  = isset( $settings['noRatingsStars'] );
		$hide_reviews_link = isset( $settings['hideReviewsLink'] );

		echo "<div {$this->render_attributes( '_root' )}>";

		if ( $show_empty_stars || $product->get_rating_count() ) {
			$params = [
				'wrapper'           => true,
				'show_empty_stars'  => $show_empty_stars,
				'hide_reviews_link' => $hide_reviews_link,
			];

			Woocommerce_Helpers::render_product_rating( $product, $params );
		}

		// No ratings txt
		elseif ( ! empty( $settings['noRatingsText'] ) ) {
			echo $settings['noRatingsText'];
		} else {
			$this->render_element_placeholder( [ 'title' => esc_html__( 'No ratings yet.', 'bricks' ) ] );
		}

		echo '</div>';
	}
}
