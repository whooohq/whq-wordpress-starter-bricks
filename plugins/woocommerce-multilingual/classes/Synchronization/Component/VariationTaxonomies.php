<?php

namespace WCML\Synchronization\Component;

use WCML\Terms\SuspendWpmlFiltersFactory;
use WCML\Utilities\DB;
use WCML\Utilities\SyncHash;
use WPML_Non_Persistent_Cache;

class VariationTaxonomies extends Synchronizer {

	/**
	 * @param \WP_Post          $variation
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function run( $variation, $translationsIds, $translationsLanguages ) {
		$filtersSuspend = SuspendWpmlFiltersFactory::create();
		foreach ( $translationsLanguages as $translationId => $language ) {
			$this->runForTranslation( $variation->ID, $translationId, $language );
		}
		$filtersSuspend->resume();
	}

	/**
	 * @param int    $variationId
	 * @param int    $translationId
	 * @param string $language
	 */
	private function runForTranslation( $variationId, $translationId, $language ) {
		$taxonomies       = get_object_taxonomies( 'product_variation' );
		$taxonomiesToSync = array_values( array_diff( $taxonomies, [ 'translation_priority' ] ) );
		/**
		 * Filters the taxonomy objects to synchronize.
		 *
		 * @since 5.2.0
		 * @todo This filter hass useless 3rd/4th parameters. Cache does not care about the translationId, so it does not matter what taxonomies we decide to change for each translationId! The same for $language!
		 * @todo We should deprecate this filter, or at least deprecate the last two parameters. So we can extract this out since it is shared for all translations!
		 *
		 * @param string[]   $taxonomiesToSync
		 * @param int|string $variationId
		 * @param int|string $translationId
		 * @param string     $language
		 */
		$taxonomiesToSync = apply_filters( 'wcml_product_variations_taxonomies_to_sync', $taxonomiesToSync, $variationId, $translationId, $language );
		$found            = false;
		$allTerms         = WPML_Non_Persistent_Cache::get( $variationId, __CLASS__, $found );
		if ( ! $found ) {
			$allTerms  = wp_get_object_terms( $variationId, $taxonomiesToSync );
			if ( is_wp_error( $allTerms ) ) {
				$allTerms = [];
			}
			WPML_Non_Persistent_Cache::set( $variationId, $allTerms, __CLASS__ );
		}

		$termIds     = wp_list_pluck( $allTerms, 'term_id' );
		$currentHash = md5( join( ',', $termIds ) );

		if ( $this->syncHashManager->isNewGroupValue( $translationId, SyncHash::GROUP_TAXONOMIES, $currentHash ) ) {
			foreach ( $taxonomiesToSync as $taxonomy ) {
				$terms = array_filter(
					$allTerms,
					function ( $term ) use ( $taxonomy ) {
						return $term->taxonomy === $taxonomy;
					}
				);

				if ( empty ( $terms ) ) {
					if ( ! $this->woocommerceWpml->terms->is_translatable_wc_taxonomy( $taxonomy ) ) {
						wp_set_object_terms( $translationId, [], $taxonomy );
					}
					continue;
				}

				$ttIds        = [];
				$ttIdsTrans   = [];
				$termIds      = [];
				$termIdsTrans = [];

				foreach ( $terms as $term ) {
					if ( $this->sitepress->is_translated_taxonomy( $taxonomy ) ) {
						$ttIds[] = $term->term_taxonomy_id;
					} else {
						$termIds[] = $term->term_id;
					}
				}

				foreach ( $ttIds as $ttId ) {
					// Avoid the wpml_object_id filter to escape from the WPML_Term_Translations::maybe_warm_term_id_cache() hell
					// given that we invalidate the cache at every step on wp_set_post_terms().
					$ttIdTrans = $this->elementTranslations->element_id_in( $ttId, $language );
					if ( $ttIdTrans ) {
						$ttIdsTrans[] = $ttIdTrans;
					}
				}

				$ttIdsTrans   = array_values( array_unique( array_map( 'intval', $ttIdsTrans ) ) );
				if ( ! empty( $ttIdsTrans ) ) {
					// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
					$termIdsTrans = $this->wpdb->get_col(
						$this->wpdb->prepare(
							"SELECT term_id FROM {$this->wpdb->term_taxonomy} WHERE term_taxonomy_id IN (" . DB::prepareIn( $ttIdsTrans, '%d' ) . ") LIMIT %d",
							count( $ttIdsTrans )
						)
					);
					// phpcs:enable
				}

				$termsToSync = array_merge( $termIds, $termIdsTrans );
				$termsToSync = array_unique( array_map( 'intval', $termsToSync ) );

				if ( empty( $termsToSync ) ) {
					continue;
				}
				// set the fourth parameter in 'true' because we need to add new terms, instead of replacing all.
				wp_set_object_terms( $translationId, $termsToSync, $taxonomy, true );
			}

			$this->syncHashManager->updateGroupValue( $translationId, SyncHash::GROUP_TAXONOMIES, $currentHash );
		}
	}

}
