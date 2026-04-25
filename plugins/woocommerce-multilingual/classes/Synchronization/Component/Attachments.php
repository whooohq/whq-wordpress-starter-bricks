<?php

namespace WCML\Synchronization\Component;

class Attachments extends Synchronizer {

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 *
	 * @todo This is all postmeta-based logic, we could get rid of the sync_thumbnail_id and sync_product_gallery methods.
	 */
	public function run( $product, $translationsIds, $translationsLanguages ) {
		foreach ( $translationsLanguages as $translationId => $language ) {
			$this->woocommerceWpml->media->sync_thumbnail_id( $product->ID, $translationId, $language );
			$this->woocommerceWpml->media->sync_product_gallery( $product->ID, $translationId, $language );
		}
	}

}
