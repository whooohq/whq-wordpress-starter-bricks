<?php

namespace WCML\OrderItems\Shipping;

use function WPML\Container\make;
use WCML\OrderItems\Translator;
use WCML\OrderItems\TranslatorFactory;

class Factory implements TranslatorFactory {

	const ORDER_ITEM_TYPE = 'shipping';

	/**
	 * @param \WC_Order_Item $item
	 *
	 * @return Translator|null
	 */
	public function getTranslator( $item ) {
		if ( ! $item instanceof \WC_Order_Item_Shipping ) {
			return null;
		}

		if ( self::ORDER_ITEM_TYPE !== $item->get_type() ) {
			return null;
		}

		$shippingId = $item->get_method_id();
		if ( ! $shippingId ) {
			return null;
		}

		$orderItemShippingTranslators = [];
		/**
		 * Register specific translators for specific shipping order items, based on the shipping method ID.
		 *
		 * Translators must be registered within the array with their ID as key, adn the value
		 * should be a qualified class name implementing the \WCML\OrderItems\Translator interface.
		 *
		 * @param array $orderItemShippingTranslators
		 *
		 * @return array
		 */
		$orderItemShippingTranslators = apply_filters( 'wcml_order_item_shipping_method_translators', $orderItemShippingTranslators );

		if ( array_key_exists( $shippingId, $orderItemShippingTranslators ) ) {
			return make( $orderItemShippingTranslators[ $shippingId ] );
		}

		return make( ShippingMethod::class );
	}
}
