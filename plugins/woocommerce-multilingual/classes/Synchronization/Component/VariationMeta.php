<?php

namespace WCML\Synchronization\Component;

use WCML\Utilities\DB;
use WCML\Utilities\SyncHash;
use WPML\FP\Obj;
use WPML_Post_Custom_Field_Setting_Keys;

class VariationMeta extends SynchronizerForMeta {

	/**
	 * @param \WP_Post          $variation
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function run( $variation, $translationsIds, $translationsLanguages ) {
		$delayedFields = [];
		foreach ( $translationsLanguages as $translationId => $language ) {
			$delayedFields = $this->synchronizeVariationMeta( $variation->ID, $translationId, $language, $delayedFields );
		}
		$this->processDelayedFields( $delayedFields, $translationsIds );
		$this->deleteOrphanedFields( $variation->ID, $translationsIds );
	}

	/**
	 * @param int    $variationId
	 * @param int    $translationId
	 * @param string $language
	 * @param array  $delayedFields
	 *
	 * @return array
	 */
	protected function synchronizeVariationMeta( $variationId, $translationId, $language, $delayedFields ) {
		$variationMeta = get_post_custom( $variationId );
		unset( $variationMeta[ SyncHash::META_KEY ] );
		$currentHash  = $this->getCurrentHash( $variationMeta, $variationId, $translationId, $language );
		$isSyncNeeded = $this->syncHashManager->isNewGroupValue( $translationId, SyncHash::GROUP_FIELDS, $currentHash );

		if ( ! $isSyncNeeded ) {
			return [];
		}

		global $iclTranslationManagement;
		$settings     = $iclTranslationManagement->settings['custom_fields_translation'];
		$excludedKeys = WPML_Post_Custom_Field_Setting_Keys::get_excluded_keys();

		foreach ( $variationMeta as $metaKey => $meta ) {
			if ( in_array( $metaKey, $excludedKeys, true ) ) {
				continue;
			}

			$metaValue = reset( $meta );
			if ( false === $metaValue ) {
				$metaValue = '';
			}

			if ( substr( $metaKey, 0, 10 ) === 'attribute_' ) {
				if ( '' !== $metaValue ) {
					$trn_post_meta = $this->woocommerceWpml->attributes->get_translated_variation_attribute_post_meta( $metaValue, $metaKey, $variationId, $translationId, $language );
					$metaValue     = $trn_post_meta['meta_value'];
					$metaKey       = $trn_post_meta['meta_key'];
				} else {
					$metaValue = '';
				}
				$delayedFields[] = [
					'post_id'    => $translationId,
					'meta_key'   => $metaKey,
					'meta_value' => maybe_unserialize( $metaValue ),
				];
				continue;
			}

			if ( ! isset( $settings[ $metaKey ] ) || (int) $settings[ $metaKey ] === WPML_IGNORE_CUSTOM_FIELD ) {
				continue;
			}

			if (
				in_array( $metaKey, [ '_sale_price', '_regular_price', '_price' ] ) &&
				(int) $this->woocommerceWpml->settings['enable_multi_currency'] === WCML_MULTI_CURRENCIES_INDEPENDENT
			) {
				$delayedFields[] = [
					'post_id'    => $translationId,
					'meta_key'   => $metaKey,
					'meta_value' => $metaValue,
				];
				continue;
			}

			if ( (int) Obj::prop( $metaKey, $settings ) === WPML_COPY_CUSTOM_FIELD ) {
				$delayedFields[] = [
					'post_id'    => $translationId,
					'meta_key'   => $metaKey,
					'meta_value' => maybe_unserialize( $metaValue ),
				];
				continue;
			}
		}

		$this->syncHashManager->updateGroupValue( $translationId, SyncHash::GROUP_FIELDS, $currentHash );

		return $delayedFields;
	}

	/**
	 * @param array  $delayedFields
	 * @param int[]  $translationsIds
	 */
	private function processDelayedFields( $delayedFields, $translationsIds ) {
		if ( empty( $delayedFields ) ) {
			return;
		}

		$metaRawData = $this->wpdb->get_results(
			"
			SELECT meta_id, post_id, meta_key
			FROM {$this->wpdb->postmeta}
			WHERE post_id IN (" . DB::prepareIn( $translationsIds, '%d' ) . ")
			"
		);

		$metaData = [];
		foreach ( $metaRawData as $metaEntry ) {
			$metaData[ $metaEntry->post_id ][ $metaEntry->meta_id ] = $metaEntry->meta_key;
		}

		$delayedFieldsActions = [];
		foreach ( $delayedFields as $delayedFieldData ) {
			$fieldPostId                                         = $delayedFieldData['post_id'];
			$fieldMetaKey                                        = $delayedFieldData['meta_key'];
			$fieldMetaValue                                      = $delayedFieldData['meta_value'];
			$delayedFieldsActions[ $fieldMetaKey ]['meta_value'] = $fieldMetaValue;
			$metaDataByVariationId                               = Obj::propOr( [], $fieldPostId, $metaData );
			if ( in_array( $fieldMetaKey, $metaDataByVariationId, true ) ) {
				$fieldMetaIds = array_keys( $metaDataByVariationId, $fieldMetaKey, true );
				if ( count( $fieldMetaIds ) > 1 ) {
					$delayedFieldsActions[ $fieldMetaKey ]['delete'][ $fieldPostId ] = $fieldMetaIds;
					$delayedFieldsActions[ $fieldMetaKey ]['insert'][ $fieldPostId ] = $fieldMetaValue;
				} else {
					$fieldMetaValueHash                                                                     = md5( maybe_serialize( $fieldMetaValue ) );
					$delayedFieldsActions[ $fieldMetaKey ]['update'][ $fieldMetaValueHash ][ $fieldPostId ] = $fieldMetaValue;
				}
			} else {
				$delayedFieldsActions[ $fieldMetaKey ]['insert'][ $fieldPostId ] = $fieldMetaValue;
			}
		}

		// Perform delete/insert/update actions.
		foreach ( $delayedFieldsActions as $delayedFieldMetaKey => $delayedFieldMetaData ) {
			// Delete all entries that have duplicated values:
			// all the related meta fields should have unique values.
			$dataToDelete = Obj::propOr( [], 'delete', $delayedFieldMetaData );
			if ( ! empty( $dataToDelete ) ) {
				$metaIdsToDelete = [];
				foreach ( $dataToDelete as $itemMetaIdsToDelete ) {
					$metaIdsToDelete = array_merge( $metaIdsToDelete, $itemMetaIdsToDelete );
				}
				$this->deleteMetaByIds( $metaIdsToDelete );
				// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
				$this->wpdb->query(
					"
					DELETE FROM {$this->wpdb->postmeta}
					WHERE meta_id IN (" . DB::prepareIn( $metaIdsToDelete, '%d' ) . ")
					"
				);
				// phpcs:enable
			}

			// Insert all post_id/meta_key/meta_value groups at once, per meta_key.
			// For each meta key, data is made of pairs [ post ID => metaValue ] for easier insertion.
			// This ensures that the number of values inserted on each batch is, at most, the number of variations.
			$dataToInsert = Obj::propOr( [], 'insert', $delayedFieldMetaData );
			if ( ! empty( $dataToInsert )) {
				$this->insertMeta( $delayedFieldMetaKey, $dataToInsert );
			}

			// Update all variations at once.
			// For each meta key, data is made of pairs [ meta value => list of affected post IDs ] so it is easier to compose IN statements.
			$dataToUpdate = Obj::propOr( [], 'update', $delayedFieldMetaData );
			if ( ! empty( $dataToUpdate ) ) {
				foreach ( $dataToUpdate as $itemsPerValue ) {
					$idsToUpdate     = array_values( array_unique( array_map( 'intval', array_keys( $itemsPerValue ) ) ) );
					$updateMetaValue = reset( $itemsPerValue );
					$this->unifyMeta( $delayedFieldMetaKey, $updateMetaValue, $idsToUpdate );
				}
			}
		}
	}

	/**
	 * @param array  $variationMeta
	 * @param int    $variationId
	 * @param int    $translationId
	 * @param string $language
	 *
	 * @return string
	 */
	private function getCurrentHash( $variationMeta, $variationId, $translationId, $language ) {
		$translationMeta = $variationMeta;
		foreach ( $variationMeta as $metaKey => $meta ) {
			if ( substr( $metaKey, 0, 10 ) !== 'attribute_' ) {
				continue;
			}
			$metaValue = reset( $meta );
			if ( false === $metaValue ) {
				continue;
			}
			$trn_post_meta               = $this->woocommerceWpml->attributes->get_translated_variation_attribute_post_meta( $metaValue, $metaKey, $variationId, $translationId, $language );
			$metaValue                   = $trn_post_meta['meta_value'];
			$metaKey                     = $trn_post_meta['meta_key'];
			$translationMeta[ $metaKey ] = [ $metaValue ];
		}
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		return md5( serialize( $translationMeta ) );
	}

}
