<?php

namespace WCML\Compatibility\WcBookings;

use function WPML\FP\spreadArgs;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;

class Slots implements \IWPML_Action {

	public function add_hooks() {
		Hooks::onAction( 'woocommerce_after_product_object_save' )
			->then( spreadArgs( [ $this, 'deleteTranslationsSlotsTransients' ] ) );
	}

	/**
	 * @param \WC_Product $product
	 */
	public function deleteTranslationsSlotsTransients( $product ) {
		if ( ! $product instanceof \WC_Product_Booking ) {
			return;
		}
		$productId    = (int) $product->get_id();
		$type         = apply_filters( 'wpml_element_type', get_post_type( $productId ) );
		$trid         = apply_filters( 'wpml_element_trid', false, $productId, $type );
		$translations = apply_filters( 'wpml_get_element_translations', [], $trid, $type );

		wpml_collect( $translations )
			->filter( function( $translation ) use ( $productId ) {
				return (int) Obj::prop( 'element_id', $translation ) !== $productId;
			} )
			->map( function( $translation ) {
				\WC_Bookings_Cache::delete_booking_slots_transient( $translation->element_id );
			} );
	}

}
