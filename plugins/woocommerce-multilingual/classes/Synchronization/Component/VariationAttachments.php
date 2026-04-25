<?php

namespace WCML\Synchronization\Component;

class VariationAttachments extends Synchronizer {

	/**
	 * @param \WP_Post          $variation
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 *
	 * @todo This is all postmeta-based logic, we could get rid of the sync_variation_thumbnail_id method.
	 */
	public function run( $variation, $translationsIds, $translationsLanguages ) {
		foreach ( $translationsLanguages as $translationId => $language ) {
			$this->woocommerceWpml->media->sync_variation_thumbnail_id( $variation->ID, $translationId, $language );
		}
	}

}
