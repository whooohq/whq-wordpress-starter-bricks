<?php

namespace WCML\Products;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks as WpHooks;
use function WPML\FP\spreadArgs;

class Hooks implements \IWPML_Frontend_Action, \IWPML_Backend_Action {
	public function add_hooks() {
		WpHooks::onFilter( 'woocommerce_variable_children_args' )
		       ->then( spreadArgs( self::forceProductLanguageInQuery() ) );

		WpHooks::onAction( 'woocommerce_after_product_object_save' )
			->then( spreadArgs( [ $this, 'flushPostVariationCache' ] ) );
	}

	/**
	 * @return \Closure array -> array
	 */
	private static function forceProductLanguageInQuery() {
		return function( $args ) {
			return Obj::assoc(
				'wpml_lang',
				apply_filters( 'wpml_element_language_code', '', [ 'element_id' => Obj::prop( 'post_parent', $args ), 'element_type' => 'product_variation' ] ),
				$args
			);
		};
	}

	/**
	 * Flush post variation cache after saving a variable product.
	 *
	 * @param \WC_Product_Variable|mixed $product
	 * @return void
	 */
	public function flushPostVariationCache( $product ) {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return;
		}

		foreach ( (array) $product->get_children() as $variationId ) {
			wp_cache_delete( $variationId, 'posts' );
		}
	}
}
