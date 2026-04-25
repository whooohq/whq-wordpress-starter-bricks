<?php

namespace WCML\Synchronization\Component;

use WCML\Synchronization\Hooks;
use WCML\Utilities\DB;
use WCML\Utilities\SyncHash;
use WPML\FP\Obj;

class Variations extends SynchronizerForMeta {

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function run( $product, $translationsIds, $translationsLanguages ) {
		$isVariableProduct = $this->woocommerceWpml->products->is_variable_product( $product->ID );
		if ( ! $isVariableProduct ) {
			return;
		}

		$productsIds         = array_merge( [ $product->ID ], $translationsIds );
		$storedVariations    = [];
		$storedRawVariations = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT * FROM {$this->wpdb->posts}
					WHERE post_status IN ('publish','private')
					AND post_type = %s
					AND post_parent IN (" . DB::prepareIn( $productsIds ) . ")
				",
					'product_variation'
			)
		);
		foreach ( $storedRawVariations as $rawVariation ) {
			$storedVariations[ $rawVariation->post_parent ][ $rawVariation->ID ] = $rawVariation;
		}

		$productVariations = $storedVariations[ $product->ID ] ?? null;
		if ( ! $productVariations ) {
			return;
		}

		$variationsTranslations               = [];
		$preparedVariationsToSetAsDuplication = [];

		remove_action( 'save_post', [ $this->elementTranslations, 'save_post_actions' ], 100 );

		foreach ( $productVariations as $productVariation ) {
			foreach ( $translationsLanguages as $translationId => $language ) {
				$variationTranslationId = $this->elementTranslations->element_id_in( $productVariation->ID, $language, false );
				if ( $variationTranslationId ) {
					$this->wpdb->update(
						$this->wpdb->posts,
						[
							'post_status'       => $productVariation->post_status,
							'post_modified'     => $productVariation->post_modified,
							'post_modified_gmt' => $productVariation->post_modified_gmt,
							'post_parent'       => $translationId,// This should be already set! We can try and see... and update all other post_* and menu_order at once.
							'menu_order'        => $productVariation->menu_order,
						],
						[ 'ID' => $variationTranslationId ]
					);
					unset( $storedVariations[ $translationId ][ $variationTranslationId ] );
					$this->syncHashManager->initialize( $variationTranslationId, SyncHash::SOURCE_META );
				} else {
					$translationGui         = str_replace( (string) $product->ID, (string) $translationId, $productVariation->guid );
					$translationSlug        = str_replace( (string) $product->ID, (string) $translationId, $productVariation->post_name );
					$variationTranslationId = wp_insert_post(
						[
							'post_author'           => $productVariation->post_author,
							'post_date_gmt'         => $productVariation->post_date_gmt,
							'post_content'          => $productVariation->post_content,
							'post_title'            => $productVariation->post_title,
							'post_excerpt'          => $productVariation->post_excerpt,
							'post_status'           => $productVariation->post_status,
							'comment_status'        => $productVariation->comment_status,
							'ping_status'           => $productVariation->ping_status,
							'post_password'         => $productVariation->post_password,
							'post_name'             => $translationSlug,
							'to_ping'               => $productVariation->to_ping,
							'pinged'                => $productVariation->pinged,
							'post_modified'         => $productVariation->post_modified,
							'post_modified_gmt'     => $productVariation->post_modified_gmt,
							'post_content_filtered' => $productVariation->post_content_filtered,
							'post_parent'           => $translationId,
							'guid'                  => $translationGui,
							'menu_order'            => $productVariation->menu_order,
							'post_type'             => $productVariation->post_type,
							'post_mime_type'        => $productVariation->post_mime_type,
							'comment_count'         => $productVariation->comment_count,
						]
					);
					// Set language details and connection for the new variation translation.
					$trid = $this->sitepress->get_element_trid( $productVariation->ID, 'post_product_variation' );
					$this->sitepress->set_element_language_details( $variationTranslationId, 'post_product_variation', $trid, $language );
					// Declare that the new variation translation is a duplicate of the product variation.
					$preparedVariationsToSetAsDuplication[] = $this->wpdb->prepare( "(%d,%s,%s)", $variationTranslationId, '_wcml_duplicate_of_variation', $productVariation->ID );
					$this->syncHashManager->initialize( $variationTranslationId, SyncHash::SOURCE_EMPTY, true );
				}
				$variationsTranslations[ $productVariation->ID ][ $variationTranslationId ] = $language;
			}
		}

		if ( ! empty( $preparedVariationsToSetAsDuplication ) ) {
			$this->wpdb->query(
				"INSERT INTO {$this->wpdb->postmeta}
				(`post_id`,`meta_key`,`meta_value`)
				VALUES " . implode( ',', $preparedVariationsToSetAsDuplication )
			);
		}

		foreach ( $translationsIds as $translationId ) {
			$orphanedTranslationVariations = $storedVariations[ $translationId ] ?? [];
			foreach ( $orphanedTranslationVariations as $orphanedTranslationVariationId => $orphanedTranslationVariation ) {
				wp_delete_post( $orphanedTranslationVariationId, true );
			}
		}

		$this->removeOrphanedVariationAttributes( $product->ID, array_keys( $productVariations ) );
		$this->synchronizeMinMaxPrices( $product->ID, $translationsLanguages, $variationsTranslations );

		$wcmlProductDataStore = wcml_product_data_store_cpt();
		foreach ( $variationsTranslations as $variationId => $variationTranslationsLanguages ) {
			$variationTranslations = array_keys( $variationTranslationsLanguages );
			do_action( Hooks::HOOK_SYNCHRONIZE_PRODUCT_VARIATION_TRANSLATIONS, $productVariations[ $variationId ], $variationTranslations, $variationTranslationsLanguages );
			foreach ( $variationTranslations as $variationTranslationId ) {
				// NOTE This is still potentially expensive.
				//$wcmlProductDataStore->update_lookup_table_data( $variationTranslationId );
				$this->syncHashManager->saveHash( $variationTranslationId, true );
			}
		}

		add_action( 'save_post', [ $this->elementTranslations, 'save_post_actions' ], 100, 2 );
	}

	/**
	 * @param int   $productId
	 * @param int[] $productVariations
	 */
	private function removeOrphanedVariationAttributes( $productId, $productVariations ) {
		$productAttributes    = get_post_meta( $productId, '_product_attributes', true );
		$variationsAttributes = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT meta_id, meta_key
				FROM {$this->wpdb->postmeta}
				WHERE meta_key LIKE %s
				AND post_id IN (" . DB::prepareIn( $productVariations ) . ")
				",
				'attribute_%%'
			),
			OBJECT_K
		);

		$metaIdsToDelete = [];
		foreach ( $variationsAttributes as $variationsAttribute ) {
			$attributeName = substr( $variationsAttribute->meta_key, 10 );
			if ( ! isset( $productAttributes[ $attributeName ] ) ) {
				$metaIdsToDelete[] = $variationsAttribute->meta_id;
			}
		}

		if ( empty( $metaIdsToDelete ) ) {
			return;
		}

		$this->deleteMetaByIds( $metaIdsToDelete );
	}

	/**
	 * @param int               $productId
	 * @param array<int,string> $translationsLanguages
	 * @param array<int,array>  $variationsTranslations
	 */
	private function synchronizeMinMaxPrices( $productId, $translationsLanguages, $variationsTranslations ) {
		$productsIds = array_merge( [ $productId ], array_keys( $translationsLanguages ) );
		
		$storedRawData = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT post_id, meta_key, meta_value
				FROM {$this->wpdb->postmeta}
				WHERE meta_key IN (
					'_min_price_variation_id',
					'_max_price_variation_id',
					'_min_regular_price_variation_id',
					'_max_regular_price_variation_id',
					'_min_sale_price_variation_id',
					'_max_sale_price_variation_id'
				)
				AND post_id IN (" . DB::prepareIn( $productsIds ) . ")
				LIMIT %d
				",
				count( $productsIds ) * 6
			)
		);

		$storedData = [];
		foreach ( $storedRawData as $rawData ) {
			$storedData[ $rawData->post_id ][ $rawData->meta_key ] = $rawData->meta_value;
		}

		$productMinMaxVariationsData = $storedData[ $productId ] ?? null;

		if ( null === $productMinMaxVariationsData ) {
			return;
		}

		$metaToInsert = [];
		$metaToUpdate = [];
		foreach ( $translationsLanguages as $translationId => $language ) {
			$translationMinMaxVariationsData = $storedData[ $translationId ] ?? [];
			foreach ( $productMinMaxVariationsData as $minMaxKey => $minMaxVariationId ) {
				$minMaxVariationTranslations           = $variationsTranslations[ $minMaxVariationId ] ?? [];
				$minMaxVariationTranslationsByLanguage = array_flip( $minMaxVariationTranslations );
				if ( ! Obj::prop( $language, $minMaxVariationTranslationsByLanguage ) ) {
					continue;
				}
				$translationMinMaxVariationId          = $translationMinMaxVariationsData[ $minMaxKey ] ?? null;
				if ( null === $translationMinMaxVariationId ) {
					$metaToInsert[ $minMaxKey ][ $translationId ] = Obj::prop( $language, $minMaxVariationTranslationsByLanguage );
					continue;
				}
				
				if ( $language === Obj::prop( $translationMinMaxVariationId, $minMaxVariationTranslations ) ) {
					continue;
				}
				$metaToUpdate[ $minMaxKey ][ $translationId ] = Obj::prop( $language, $minMaxVariationTranslationsByLanguage );
			}
		}

		foreach ( $metaToInsert as $metaKey => $metaData ) {
			$this->insertMeta( $metaKey, $metaData );
		}

		foreach ( $metaToUpdate as $metaKey => $metaData ) {
			$this->updateMeta( $metaKey, $metaData );
		}
	}

}
