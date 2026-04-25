<?php

namespace WCML\Synchronization\Component;

use WCML\Utilities\DB;

class Post extends Synchronizer {

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function run( $product, $translationsIds, $translationsLanguages ) {
		$productsIds = array_merge( [ $product->ID ], $translationsIds );
		$fields      = [ 'ID', 'post_parent' ];

		if ( isset( $this->woocommerceWpml->settings['products_sync_order'] ) && $this->woocommerceWpml->settings['products_sync_order'] ) {
			$fields[] = 'menu_order';
		}
		if ( ! empty( $this->woocommerceWpml->settings['products_sync_date'] ) ) {
			$fields[] = 'post_date';
			$fields[] = 'post_date_gmt';
		}

		$fieldsInQuery = implode( ',', $fields );

		// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		$productsData = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT {$fieldsInQuery}
				FROM {$this->wpdb->posts}
				WHERE ID IN (" . DB::prepareIn( $productsIds ) . ")
				LIMIT %d
				",
				count( $productsIds )
			),
			OBJECT_K
		);
		// phpcs:enable

		$productData = $productsData[ $product->ID ] ?? null;
		if ( ! $productData ) {
			return;
		}

		unset( $productsData[ $product->ID ] );

		$this->managePostParent( $productData, $productsData, $translationsLanguages );
		$this->manageMenuOrder( $productData, $productsData );
		$this->manageDate( $productData, $productsData );
	}

	/**
	 * @param object            $productData
	 * @param array<int,object> $translationsData
	 * @param array<int,string> $translationsLanguages
	 */
	private function managePostParent( $productData, $translationsData, $translationsLanguages ) {
		$productParent = $productData->post_parent;
		if ( ! $productParent ) {
			return;
		}

		$productParentTranslations = $this->elementTranslations->get_element_translations( $productParent, false, true );
		foreach ( $translationsData as $translationID => $translationData ) {
			if ( ! in_array( (int) $translationData->post_parent, $productParentTranslations, true ) ) {
				$translationLanguage = $translationsLanguages[ $translationID ];
				$translationParentId = (int) $this->elementTranslations->element_id_in( $productParent, $translationLanguage, false );
				$this->wpdb->update(
					$this->wpdb->posts,
					[ 'post_parent' => $translationParentId ],
					[ 'id' => $translationID ]
				);
			}
		}
	}

	/**
	 * @param object            $productData
	 * @param array<int,object> $translationsData
	 */
	private function manageMenuOrder( $productData, $translationsData ) {
		if ( ! isset( $this->woocommerceWpml->settings['products_sync_order'] ) || !$this->woocommerceWpml->settings['products_sync_order'] ) {
			return;
		}

		$productMenuOrder     = $productData->menu_order;
		$translationsToUpdate = [];

		foreach ( $translationsData as $translationID => $translationData ) {
			if ( $translationData->menu_order !== $productMenuOrder ) {
				$translationsToUpdate[] = $translationID;
			}
		}

		if ( ! empty( $translationsToUpdate ) ) {
			// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
			$this->wpdb->query(
				$this->wpdb->prepare(
					"
					UPDATE {$this->wpdb->posts}
					SET menu_order = %d
					WHERE ID IN (" . DB::prepareIn( $translationsToUpdate ) . ")
					",
					$productMenuOrder
				)
			);
			// phpcs:enable
		}
	}

	/**
	 * @param object            $productData
	 * @param array<int,object> $translationsData
	 */
	private function manageDate( $productData, $translationsData ) {
		if ( empty( $this->woocommerceWpml->settings['products_sync_date'] ) ) {
			return;
		}

		$productDate          = $productData->post_date;
		$productDateGmt       = $productData->post_date_gmt;
		$translationsToUpdate = [];

		foreach ( $translationsData as $translationID => $translationData ) {
			if ( $translationData->post_date !== $productDate || $translationData->post_date_gmt !== $productDateGmt ) {
				$translationsToUpdate[] = $translationID;
			}
		}

		if ( ! empty( $translationsToUpdate ) ) {
			// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
			$this->wpdb->query(
				$this->wpdb->prepare(
					"
					UPDATE {$this->wpdb->posts}
					SET post_date = %s, post_date_gmt = %s
					WHERE ID IN (" . DB::prepareIn( $translationsToUpdate ) . ")
					",
					$productDate,
					$productDateGmt
				)
			);
			// phpcs:enable
		}
	}

}
