<?php

namespace WCML\Synchronization\Component;

use WCML\Utilities\DB;
use WCML\Utilities\WCTaxonomies;
use WPML\FP\Lst;
use WPML\FP\Obj;

class Attributes extends SynchronizerForMeta {

	const DEFAULT_ATTRIBUTES_META_KEY = '_default_attributes';
	const PRODUCT_ATTRIBUTES_META_KEY = '_product_attributes';

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function run( $product, $translationsIds, $translationsLanguages ) {
		$productsIds      = array_merge( [ $product->ID ], $translationsIds );
		$storedAttributes = $this->getMeta( self::PRODUCT_ATTRIBUTES_META_KEY, $productsIds );

		$this->runForAttributes( $product->ID, $translationsIds, $translationsLanguages, $storedAttributes );
		$this->runForDefaultAttributes( $product->ID, $translationsIds, $translationsLanguages, $storedAttributes );
	}

	private function runForAttributes( $productId, $translationsIds, $translationsLanguages, $storedAttributes ) {
		$productAttributes = $storedAttributes[ $productId ] ?? null;

		if ( null === $productAttributes ) {
			$translationsIdsToClear = array_intersect( $translationsIds, array_keys( $storedAttributes ) );
			$this->clearTranslationsValue( $translationsIdsToClear, self::PRODUCT_ATTRIBUTES_META_KEY );
			return;
		}

		if ( empty( $productAttributes ) ) {
			$this->spreadEmptyValue( $translationsIds, $storedAttributes, self::PRODUCT_ATTRIBUTES_META_KEY );
			return;
		}

		$hasLocalAttributes = (bool) Lst::find(
			function( $attributeData ) {
				return (bool) Obj::prop( 'is_taxonomy', $attributeData ) === false;
			},
			$productAttributes
		);

		$duplicationsIds = [];
		if ( $hasLocalAttributes ) {
			// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
			$duplicationsIds = $this->wpdb->get_col(
				$this->wpdb->prepare(
					"
					SELECT post_id
					FROM {$this->wpdb->postmeta}
					WHERE meta_key = %s
					AND post_id IN (" . DB::prepareIn( $translationsIds ) . ")
					LIMIT %d
					",
					'_icl_lang_duplicate_of',
					count( $translationsIds )
				)
			);
			// phpcs:enable
		}

		$sanitizedAttributeNames = [];
		foreach ( $productAttributes as $attribute => $attributeData ) {
			$sanitizedAttributeNames[ $attribute ] = $attribute;
			$sanitizedAttribute                    = $this->woocommerceWpml->attributes->getAttributeNameToSave( $attribute, $attributeData, $productId );
			if ( $attribute !== $sanitizedAttribute ) {
				$sanitizedAttributeNames[ $sanitizedAttribute ] = $attribute;
				$productAttributes[ $sanitizedAttribute ]       = $attributeData;
				unset( $productAttributes[ $attribute ] );
			}
		}

		$translationsIdsToInsert = [];
		$translationsIdsToUpdate = [];
		foreach ( $translationsIds as $translationId ) {
			$translationAttributes       = $productAttributes;
			$storedTranslationAttributes = $storedAttributes[ $translationId ] ?? [];
			foreach ( $translationAttributes as $attribute => $attributeData ) {
				if ( $attributeData['is_taxonomy'] || in_array( $translationId, $duplicationsIds, true ) ) {
					continue;
				}

				$attributeToSave = $sanitizedAttributeNames[ $attribute ];
				if ( isset( $storedTranslationAttributes[ $attribute ] ) ) {
					$translationAttributes[ $attributeToSave ]['value'] = $storedTranslationAttributes[ $attribute ]['value'];
				} else if ( isset( $storedTranslationAttributes[ $attributeToSave ] ) ) {
					$translationAttributes[ $attributeToSave ]['value'] = $storedTranslationAttributes[ $attributeToSave ]['value'];
				} else if ( ! empty( $storedTranslationAttributes ) ) {
					unset( $translationAttributes[ $attribute ] );
				}
			}
			if ( maybe_serialize( $translationAttributes ) === maybe_serialize( $storedTranslationAttributes ) ) {
				continue;
			}
			if ( array_key_exists( $translationId, $storedAttributes ) ) {
				$translationsIdsToUpdate[ $translationId ] = $translationAttributes;
			} else {
				$translationsIdsToInsert[ $translationId ] = $translationAttributes;
			}
		}

		$this->insertMeta( self::PRODUCT_ATTRIBUTES_META_KEY, $translationsIdsToInsert );
		$this->updateMeta( self::PRODUCT_ATTRIBUTES_META_KEY, $translationsIdsToUpdate );
	}

	/**
	 * @param int               $productId
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 * @param array<int,array>  $storedAttributes
	 */
	private function runForDefaultAttributes( $productId, $translationsIds, $translationsLanguages, $storedAttributes ) {
		$productsIds             = array_merge( [ $productId ], $translationsIds );
		$storedDefaultAttributes = $this->getMeta( self::DEFAULT_ATTRIBUTES_META_KEY, $productsIds );
		$defaultAttributes       = $storedDefaultAttributes[ $productId ] ?? null;

		if ( null === $defaultAttributes ) {
			$translationsIdsToClear = array_intersect( $translationsIds, array_keys( $storedDefaultAttributes ) );
			$this->clearTranslationsValue( $translationsIdsToClear, self::DEFAULT_ATTRIBUTES_META_KEY );
			return;
		}

		if ( empty( $defaultAttributes ) ) {
			$this->spreadEmptyValue( $translationsIds, $storedDefaultAttributes, self::DEFAULT_ATTRIBUTES_META_KEY );
			return;
		}

		$defaultAttributesToUpdate = [];
		foreach ( $defaultAttributes as $attribute => $defaultAttributeValue ) {
			if ( WCTaxonomies::isProductAttribute( $attribute ) ) {
				if ( $this->woocommerceWpml->attributes->is_translatable_attribute( $attribute ) ) {
					$sanitizedAttributeName  = wc_sanitize_taxonomy_name( $attribute );
					$defaultTerm             = $this->woocommerceWpml->terms->wcml_get_term_by_slug( $defaultAttributeValue, $sanitizedAttributeName );
					$defaultTermTranslations = $defaultTerm
						? $this->elementTranslations->get_element_translations( $defaultTerm->term_taxonomy_id, false, false )
						: [];

					foreach ( $translationsLanguages as $translationId => $language ) {
						$translatedDefaultAttributes     = $storedDefaultAttributes[ $translationId ] ?? [];
						$translatedDefaultTermTaxonomyId = $defaultTermTranslations[ $language ] ?? null;
						$translatedDefaultTerm           = $translatedDefaultTermTaxonomyId
							? $this->woocommerceWpml->terms->wcml_get_term_by_taxonomy_id( $translatedDefaultTermTaxonomyId, $sanitizedAttributeName )
							: null;
						$translatedDefaultAttributeValue = $translatedDefaultTerm
							? $translatedDefaultTerm->slug
							: 0;
						if (
							! array_key_exists( $attribute, $translatedDefaultAttributes )
							|| $translatedDefaultAttributes[ $attribute ] !== $translatedDefaultAttributeValue
						) {
							$defaultAttributesToUpdate[ $translationId ][ $attribute ] = $translatedDefaultAttributeValue;
						}
					}
				} else {
					foreach ( $translationsIds as $translationId ) {
						$translatedDefaultAttributes = $storedDefaultAttributes[ $translationId ] ?? [];
						if (
							! array_key_exists( $attribute, $translatedDefaultAttributes )
							|| $translatedDefaultAttributes[ $attribute ] !== $defaultAttributeValue
						) {
							$defaultAttributesToUpdate[ $translationId ][ $attribute ] = $defaultAttributeValue;
						}
					}
				}
				unset( $defaultAttributes[ $attribute ] );
			}
		}

		if ( ! empty( $defaultAttributes ) ) {
			$productAttributes = $storedAttributes[ $productId ] ?? [];

			foreach ( $defaultAttributes as $attribute => $defaultAttributeValue ) {
				if ( ! array_key_exists( $attribute, $productAttributes ) ) {
					continue;
				}

				$productAttributeValues = explode( '|', $productAttributes[ $attribute ]['value'] );
				$productAttributeValues = array_map( 'trim', $productAttributeValues );

				foreach ( $productAttributeValues as $attributeIndex => $attributeValue ) {
					$attributeValueSanitized = strtolower( sanitize_title( $attributeValue ) );
					if (
						$attributeValueSanitized !== $defaultAttributeValue
						&& trim( $attributeValue ) !== trim( $defaultAttributeValue )
					) {
						continue;
					}
					foreach ( $translationsIds as $translationId ) {
						$translatedStoredAttributes = $storedAttributes[ $translationId ] ?? [];
						if ( ! array_key_exists( $attribute, $translatedStoredAttributes ) ) {
							continue;
						}
						$translatedAttributeValues = explode( '|', $translatedStoredAttributes[ $attribute ]['value'] );
						if ( ! isset( $translatedAttributeValues[ $attributeIndex ] ) ) {
							$defaultAttributesToUpdate[ $translationId ][ $attribute ] = '';
							continue;
						}
						if ( $attributeValueSanitized === $defaultAttributeValue ) {
							$translatedAttributeValue = strtolower( sanitize_title( trim( $translatedAttributeValues[ $attributeIndex ] ) ) );
						} else {
							$translatedAttributeValue = trim( $translatedAttributeValues[ $attributeIndex ] );
						}
						if ( $translatedAttributeValue !== Obj::path( [ $translationId, $attribute ], $storedDefaultAttributes ) ) {
							$defaultAttributesToUpdate[ $translationId ][ $attribute ] = $translatedAttributeValue;
						}
					}
				}
			}
		}

		if ( ! empty( $defaultAttributesToUpdate ) ) {
			$metaToInsert = [];
			$metaToUpdate = [];
			foreach ( $defaultAttributesToUpdate as $translationId => $translationAttributes ) {
				if ( ! array_key_exists( $translationId, $storedDefaultAttributes ) ) {
					$metaToInsert[ $translationId ] = $translationAttributes;
					continue;
				}
				$translationDefaultAttributes = $storedDefaultAttributes[ $translationId ];
				if ( ! empty( $translationDefaultAttributes ) ) {
					$metaToUpdate[ $translationId ] = $storedDefaultAttributes[ $translationId ];
					foreach ( $translationAttributes as $attribute => $attributeValue ) {
						$metaToUpdate[ $translationId ][ $attribute ] = $attributeValue;
					}
				}
			}

			$this->insertMeta( self::DEFAULT_ATTRIBUTES_META_KEY, $metaToInsert );
			$this->updateMeta( self::DEFAULT_ATTRIBUTES_META_KEY, $metaToUpdate );
		}
	}

}
