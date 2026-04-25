<?php

namespace WCML\Compatibility\FacebookForWc;

use WPML\LIB\WP\Hooks as WPHooks;
use function WPML\FP\spreadArgs;

class MultilingualHooks implements \IWPML_Action {
	/**
	 * Supported values
	 * - null - settings not retrieved from filter
	 * - [] - don't filter - allow products in all languages
	 * - [ 'en', 'fr' ] - leave only products in these languages
	 *
	 * @var ?array
	 */
	private $cache_sync_product_only_allow_these_languages = null;
	/**
	 * @var int[]
	 */
	private $cache_sync_product_list = [];

	public function add_hooks() {
		WPHooks::onFilter( 'facebook_for_woocommerce_integration_prepare_product', 11, 2 )
			->then( spreadArgs( [ $this, 'facebook_product_url_with_correct_language' ] ) );

		WPHooks::onFilter( 'wc_facebook_should_sync_product', 10, 2 )
			->then( spreadArgs( [ $this, 'facebook_sync_product_filtered_by_language' ] ) );
	}

	/**
	 * @param array $product_data An array of product data.
	 * @param int   $id           Woocommerce product id.
	 *
	 * @retur array
	 */
	public function facebook_product_url_with_correct_language( $product_data, $id ) {
		if ( empty( $product_data['url'] ) ) {
			return $product_data;
		}

		$product_lang = apply_filters( 'wpml_post_language_details', null, $id );
		if ( ! empty( $product_lang['language_code'] ) ) {
			$product_data['url'] = apply_filters( 'wpml_permalink', $product_data['url'], $product_lang['language_code'] );
		}

		return $product_data;
	}

	/**
	 * @param bool        $should_sync
	 * @param \WC_Product $product the product object.
	 *
	 * @return bool
	 */
	public function facebook_sync_product_filtered_by_language( $should_sync, $product ) {
		if ( null === $this->cache_sync_product_only_allow_these_languages ) {
			/**
			 * [] - default: don't filter - allow products in all languages
			 * [ 'en', 'fr' ] - leave only products in these languages
			 *
			 * @param array $allow_languages
			 *
			 * @return array
			 */
			$this->cache_sync_product_only_allow_these_languages = apply_filters( 'wcml_facebook_sync_products_languages', [] );
		}

		/** @phpstan-ignore booleanNot.alwaysFalse */
		if ( empty( $this->cache_sync_product_only_allow_these_languages ) || ! is_array( $this->cache_sync_product_only_allow_these_languages ) ) {
			return $should_sync;
		}

		$product_id = (int) $product->get_id();

		if ( \in_array( $product_id, $this->cache_sync_product_list, true ) ) {
			return false;
		}

		$product_lang      = apply_filters( 'wpml_post_language_details', null, $product->get_id() );
		$product_lang_code = $product_lang['language_code'] ?? null;

		if ( ! in_array( $product_lang_code, $this->cache_sync_product_only_allow_these_languages, true ) ) {
			$this->cache_sync_product_list[] = $product_id;

			return false;
		}

		return $should_sync;
	}
}
