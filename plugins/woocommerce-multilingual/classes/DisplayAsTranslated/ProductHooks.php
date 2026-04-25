<?php

namespace WCML\DisplayAsTranslated;

use WPML\LIB\WP\Hooks;
use function WCML\functions\flushProductCachePrefixById;

class ProductHooks implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onAction( 'wp' )->then( [ $this, 'flush_current_product_cache_prefix' ] );
	}

	/**
	 * @return void
	 */
	public function flush_current_product_cache_prefix() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		$product_id = get_queried_object_id();
		if ( $product_id > 0 ) {
			flushProductCachePrefixById( $product_id );
		}
	}
}
