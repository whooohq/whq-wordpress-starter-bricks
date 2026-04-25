<?php

namespace WCML\OrderItems\LineItem;

use WCML\OrderItems\Translator;

class Variation implements Translator {

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
		}

		$variationId           = $item->get_variation_id();
		$translatedVariationId = apply_filters( 'wpml_object_id', $variationId, 'product_variation', true, $targetLanguage );
		if ( $variationId && $variationId !== $translatedVariationId ) {
			$item->set_variation_id( $translatedVariationId );
			$item->set_name( wc_get_product( $translatedVariationId )->get_name() );
			$this->update_attribute_item_meta_value( $item, $translatedVariationId );
		}
	}

	/**
	 * @param \WC_Order_Item_Product $item
	 * @param int                    $variationId
	 */
	private function update_attribute_item_meta_value( $item, $variationId ) {
		foreach ( $item->get_meta_data() as $meta_data ) {
			$data            = $meta_data->get_data();
			$attributeExists = metadata_exists( 'post', $variationId, 'attribute_' . $data['key'] );
			if ( $attributeExists ) {
				$attributeValue = get_post_meta( $variationId, 'attribute_' . $data['key'], true );

				if ( '' === $attributeValue ) {
					$productId = $item->get_product_id();
					$options   = $this->get_attribute_options( $productId, $data['key'] );

					$orderLanguage  = $item->get_order()->get_meta( \WCML_Orders::KEY_LANGUAGE );
					$orderProductId = apply_filters( 'wpml_object_id', $productId, 'product', false, $orderLanguage );
					$orderOptions   = $this->get_attribute_options( $orderProductId, $data['key'] );

					$position = array_search( $data['value'], $orderOptions, true );
					if ( false !== $position ) {
						$attributeValue = $options[ $position ];
					}
				}

				if ( $attributeValue ) {
					$item->update_meta_data( $data['key'], $attributeValue, $data['id'] ?? 0 );
				}
			}
		}
	}

	/**
	 * @param int    $productId
	 * @param string $attribute
	 *
	 * @return array
	 */
	private function get_attribute_options( $productId, $attribute ) {
		$product    = wc_get_product( $productId );
		$attributes = $product->get_attributes();
		return $attributes[ $attribute ]->get_options();
	}

}
