<?php

namespace WCML\OrderItems\LineItem;

use function WPML\Container\make;
use WCML\OrderItems\Translator;
use WCML\OrderItems\TranslatorFactory;

class Factory implements TranslatorFactory {

	const ORDER_ITEM_TYPE = 'line_item';

	/**
	 * @param \WC_Order_Item $item
	 *
	 * @return Translator|null
	 */
	public function getTranslator( $item ) {
		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return null;
		}

		if ( self::ORDER_ITEM_TYPE !== $item->get_type() ) {
			return null;
		}

		if ( $item->get_variation_id() ) {
			return make( Variation::class );
		}

		return make( Product::class );
	}
}
