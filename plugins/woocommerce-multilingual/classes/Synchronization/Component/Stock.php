<?php

namespace WCML\Synchronization\Component;

class Stock extends SynchronizerForMeta {

	const STOCK_META_KEY        = '_stock';
	const STOCK_STATUS_META_KEY = '_stock_status';

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function run( $product, $translationsIds, $translationsLanguages ) {
		$productObject          = wc_get_product( $product->ID );
		$productIdManagingStock = $productObject->get_stock_managed_by_id();

		// Assume that the translation of the product managing stock is the product managing stock of the translations.
		$translationsManagingStock = $translationsIds;
		if ( $productIdManagingStock !== $product->ID ) {
			$translationsManagingStock = $this->elementTranslations->get_element_translations( $productIdManagingStock, false, false );
		}

		$this->synchronizeMeta( $productIdManagingStock, $translationsManagingStock, self::STOCK_META_KEY );
		$this->synchronizeMeta( $product->ID, $translationsIds, self::STOCK_STATUS_META_KEY );

		if ( $productIdManagingStock !== $product->ID ) {
			$this->deleteCache( $productIdManagingStock );
			foreach ( $translationsManagingStock as $translationManagingStock ) {
				$this->deleteCache( $translationManagingStock );
			}
		}

		delete_transient( 'wc_low_stock_count' );
		delete_transient( 'wc_outofstock_count' );
	}

	/**
	 * @param int $productId
	 *
	 * @todo Collect IDs that need cache deleting, and delete on shutdown. Note that variations might be firing this multiple times for their parent product.
	 */
	private function deleteCache( $productId ) {
		wp_cache_delete( $productId, 'post_meta' );
		wp_cache_delete( 'product-' . $productId, 'products' );
		delete_transient( 'wc_product_children_' . $productId );
		$wcml_data_store = wcml_product_data_store_cpt();
		$wcml_data_store->update_lookup_table_data( $productId );
	}

}
