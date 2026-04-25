<?php

namespace WCML\Synchronization\Component;

use WCML\Terms\SuspendWpmlFiltersFactory;
use WCML\Utilities\DB;
use WPML_Non_Persistent_Cache;
class Taxonomies extends Synchronizer {

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function run( $product, $translationsIds, $translationsLanguages ) {
		$filtersSuspend = SuspendWpmlFiltersFactory::create();
		foreach ( $translationsLanguages as $translationId => $language ) {
			$this->runForTranslation( $product->ID, $translationId, $language );
		}
		$filtersSuspend->resume();
	}

	/**
	 * @param int    $productId
	 * @param int    $translationId
	 * @param string $language
	 */
	private function runForTranslation( $productId, $translationId, $language ) {
		$taxonomyExceptions = [ 'product_type', 'product_visibility' ]; // ?
		$taxonomySyncEmpty  = [ \WCML_Terms::PRODUCT_SHIPPING_CLASS ];
		$taxonomies         = $taxonomyExceptions;
		if ( $this->sitepress->get_setting( 'sync_post_taxonomies' ) ) {
			$taxonomies = get_object_taxonomies( 'product' );
		}

		$found    = false;
		$allTerms = WPML_Non_Persistent_Cache::get( $productId, __CLASS__, $found );
		if ( ! $found ) {
			$allTerms = wp_get_object_terms( $productId, $taxonomies );
			WPML_Non_Persistent_Cache::set( $productId, $allTerms, __CLASS__ );
		}
		if ( ! is_wp_error( $allTerms ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( ! apply_filters( 'wpml_is_translated_taxonomy', false, $taxonomy ) ) {
					$taxonomyExceptions[] = $taxonomy;
				}
				$ttIds   = [];
				$ttNames = [];
				$terms   = array_filter(
					$allTerms,
					function ( $term ) use ( $taxonomy ) {
						return $term->taxonomy === $taxonomy;
					}
				);
				if ( ! $terms && ! in_array( $taxonomy, $taxonomySyncEmpty, true ) ) {
					continue;
				}
				foreach ( $terms as $term ) {
					if ( in_array( $term->taxonomy, $taxonomyExceptions, true ) ) {
						$ttNames[] = $term->name;
						continue;
					}
					$ttIds[] = $term->term_taxonomy_id;
				}

				if ( $this->woocommerceWpml->terms->is_translatable_wc_taxonomy( $taxonomy ) && ! in_array( $taxonomy, $taxonomyExceptions, true ) ) {
					$this->setTranslatedTerms( $ttIds, $language, $taxonomy, $translationId );
				} else {
					wp_set_post_terms( $translationId, $ttNames, $taxonomy );
				}
			}
		}

	}

	/**
	 * @param int[]  $ttIds    An array of term_taxonomy_id values - NOT term_id values!!!!
	 * @param string $language
	 * @param string $taxonomy
	 * @param int    $translationId
	 */
	private function setTranslatedTerms( $ttIds, $language, $taxonomy, $translationId ) {
		$ttIdsTrans = [];

		foreach ( $ttIds as $ttId ) {
			// Avoid the wpml_object_id filter to escape from the WPML_Term_Translations::maybe_warm_term_id_cache() hell
			// given that we invalidate the cache at every step on wp_set_post_terms().
			$ttIdTrans = $this->elementTranslations->element_id_in( $ttId, $language );
			if ( $ttIdTrans ) {
				$ttIdsTrans[] = $ttIdTrans;
			}
		}

		$ttIdsTrans = array_values( array_unique( array_map( 'intval', $ttIdsTrans ) ) );
		
		if ( empty( $ttIdsTrans ) ) {
			return;
		}

		if ( in_array( $taxonomy, [ 'product_cat', 'product_tag' ] ) ) {
			$this->sitepress->switch_lang( $language );
			wp_update_term_count( $ttIdsTrans, $taxonomy );
			$this->sitepress->switch_lang();
		}

		// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		$termIds = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT term_id FROM {$this->wpdb->term_taxonomy} WHERE term_taxonomy_id IN (" . DB::prepareIn( $ttIdsTrans, '%d' ) . ") LIMIT %d",
				count( $ttIdsTrans )
			)
		);
		// phpcs:enable

		$termIds = array_unique( array_map( 'intval', $termIds ) );
		wp_set_post_terms( $translationId, $termIds, $taxonomy );
	}

}
