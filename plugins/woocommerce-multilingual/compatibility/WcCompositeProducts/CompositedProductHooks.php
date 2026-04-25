<?php

namespace WCML\Compatibility\WcCompositeProducts;

class CompositedProductHooks implements \IWPML_Action {

	const EARLY_PRIORITY = 0;
	const LATE_PRIORITY  = 100;

	public function add_hooks() {
		add_action( 'woocommerce_composited_product_variable', [ $this, 'beforeFrontendCompositedProduct' ], self::EARLY_PRIORITY );
	}

	public function beforeFrontendCompositedProduct() {
		add_action( 'woocommerce_composited_product_details', [ $this, 'trackCompositedProduct' ], self::EARLY_PRIORITY );
	}

	/**
	 * @param \WC_Product|\WC_Product_Variable $product
	 */
	public function trackCompositedProduct( $product ) {
		/**
		 * @param int $productId
		 *
		 * @uses \WC_Product|\WC_Product_Variable $productç
		 *
		 * @return int
		 */
		$setProductId = function( $productId ) use ( $product ) {
			return $product->get_id();
		};

		/**
		 * @uses callable $setProductId
		 */
		$unsetProductId = function() use ( $setProductId ) {
			remove_filter( 'wcml_translated_attribute_label_product_id', $setProductId );
		};

		add_filter( 'wcml_translated_attribute_label_product_id', $setProductId );
		add_action( 'woocommerce_composited_product_variable', $unsetProductId, self::LATE_PRIORITY );
	}

}
