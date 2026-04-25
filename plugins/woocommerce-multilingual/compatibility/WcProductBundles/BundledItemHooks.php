<?php

namespace WCML\Compatibility\WcProductBundles;

class BundledItemHooks implements \IWPML_Action {

	const EARLY_PRIORITY = 0;
	const LATE_PRIORITY  = 100;

	public function add_hooks() {
		add_action( 'woocommerce_before_bundled_items', [ $this, 'beforeFrontendBundledItems' ] );
	}

	public function beforeFrontendBundledItems() {
		add_action( 'woocommerce_bundled_item_details', [ $this, 'trackBundledItem' ], self::EARLY_PRIORITY );
	}

	/**
	 * @param \WC_Bundled_Item $bundleItem
	 */
	public function trackBundledItem( $bundleItem ) {
		/**
		 * @param int $productId
		 *
		 * @uses \WC_Bundled_Item $bundleItem
		 *
		 * @return int
		 */
		$setProductId = function( $productId ) use ( $bundleItem ) {
			return $bundleItem->get_product()->get_id();
		};

		/**
		 * @uses callable $setProductId
		 */
		$unsetProductId = function() use ( $setProductId ) {
			remove_filter( 'wcml_translated_attribute_label_product_id', $setProductId );
		};

		add_filter( 'wcml_translated_attribute_label_product_id', $setProductId );
		add_action( 'woocommerce_bundled_item_details', $unsetProductId, self::LATE_PRIORITY );
	}

}
