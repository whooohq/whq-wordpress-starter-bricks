<?php

namespace WCML\Synchronization\Component;

class Linked extends SynchronizerForMeta {

	const LINKED_META_KEYS = [
		'_upsell_ids',
		'_cross_sell_ids',
		'_children',
	];

	const TRANSIENTS_PREFIXES = [
		'wc_product_children_%s', // Note that this is also removed in the Stock management (!?).
		'_transient_wc_product_children_ids_%s',
	];

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function run( $product, $translationsIds, $translationsLanguages ) {
		foreach ( self::LINKED_META_KEYS as $metaKey ) {
			$this->syncLinkType( $product->ID, $translationsIds, $translationsLanguages, $metaKey );
		}
		foreach ( $translationsIds as $translationId ) {
			$translationParentId = wp_get_post_parent_id( $translationId );
			if ( $translationParentId ) {
				foreach ( self::TRANSIENTS_PREFIXES as $transientPrefix ) {
					delete_transient( sprintf( $transientPrefix, $translationParentId ) );
				}
			}
		}
	}

	/**
	 * @param int               $productId
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 * @param string            $metaKey
	 */
	private function syncLinkType( $productId, $translationsIds, $translationsLanguages, $metaKey ) {
		$productsIds  = array_merge( [ $productId ], $translationsIds );
		$storedLinks  = $this->getMeta( $metaKey, $productsIds );
		$productLinks = $storedLinks[ $productId ] ?? null;

		if ( null === $productLinks ) {
			$translationsIdsToClear = array_intersect( $translationsIds, array_keys( $storedLinks ) );
			$this->clearTranslationsValue( $translationsIdsToClear, $metaKey );
			return;
		}

		if ( empty( $productLinks ) ) {
			$this->spreadEmptyValue( $translationsIds, $storedLinks, $metaKey );
			return;
		}

		$translatedLinks = [];
		foreach ( $translationsLanguages as $translationId => $language ) {
			$translatedLinks[ $translationId ] = $this->translateLinks( $productLinks, $language );
		}

		$metaToInsert = [];
		$metaToUpdate = [];
		foreach ( $translatedLinks as $translationId => $translationLinks ) {
			if ( ! array_key_exists( $translationId, $storedLinks ) ) {
				$metaToInsert[ $translationId ] = $translationLinks;
				continue;
			}
			if ( maybe_serialize( $translationLinks ) === maybe_serialize( $storedLinks[ $translationId ] ) ) {
				continue;
			}
			$metaToUpdate[ $translationId ] = $translationLinks;
		}

		$this->insertMeta( $metaKey, $metaToInsert );
		$this->updateMeta( $metaKey, $metaToUpdate );
	}

	/**
	 * @param int[]  $productLinks
	 * @param string $language
	 *
	 * @return int[]
	 *
	 * @todo Request from Core a mechanism to translate multiple IDs belonging to the same post type.
	 */
	private function translateLinks( $productLinks, $language ) {
		$translatedLinkedProducts = [];
		foreach ( $productLinks as $linkedProduct ) {
			$translatedLinkedProducts[] = apply_filters( 'wpml_object_id', $linkedProduct, get_post_type( $linkedProduct ), false, $language );
		}
		return $translatedLinkedProducts;
	}

}
