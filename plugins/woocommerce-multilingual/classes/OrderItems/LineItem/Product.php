<?php

namespace WCML\OrderItems\LineItem;

use WCML\OrderItems\Translator;

class Product implements Translator {

	/**
	 * @param \WC_Order_Item $item
	 * @param string         $targetLanguage
	 */
	public function translateItem( $item, $targetLanguage ) {
		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}

		$productId           = $item->get_product_id();
		$translatedProductId = apply_filters( 'wpml_object_id', $productId, 'product', true, $targetLanguage );
		if ( $productId && $productId !== $translatedProductId ) {
			$item->set_product_id( $translatedProductId );
			$item->set_name( get_post( $translatedProductId )->post_title );
			$item->apply_changes();
		}
	}
}
