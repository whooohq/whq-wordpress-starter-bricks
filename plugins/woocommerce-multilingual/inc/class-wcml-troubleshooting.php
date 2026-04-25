<?php

use WCML\Utilities\DB;
use WPML\FP\Obj;
use function WPML\Container\make;

class WCML_Troubleshooting {

	const ITEMS_PER_AJAX_MIN = 3;
	const ITEMS_PER_AJAX     = 5;

	const OPTION_PRODUCTS_WITH_VARIATIONS                 = 'wcml_products_to_sync';
	const OPTION_PRODUCTS_AND_VARIATIONS_FOR_STOCK_SYNC   = 'wcml_products_and_variations_for_stock_sync';
	const OPTION_VARIATIONS_FOR_LANGUAGE_ASSIGNMENT       = 'wcml_trbl_translated_variations';
	const OPTION_PRODUCTS_AND_VARIATIONS_FOR_META_CLEANUP = 'wcml_trbl_products_needs_fix_postmeta';
	const META_GALLERY_SYNC                               = 'wcml_gallery_sync';
	const META_GALLERY_SYNC_LEGACY                        = 'gallery_sync';
	const META_CAT_META_SYNC                              = 'wcml_cat_meta_sync';

	private $woocommerce_wpml;
	private $sitepress;
	private $wpdb;

	/**
	 * WCML_Troubleshooting constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress        $sitepress
	 * @param wpdb             $wpdb
	 */
	public function __construct( $woocommerce_wpml, $sitepress, $wpdb ) {

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
		$this->wpdb             = $wpdb;

		add_action( 'init', [ $this, 'init' ] );
	}

	public function init() {
		add_action( 'wp_ajax_trbl_sync_variations', [ $this, 'trbl_sync_variations' ] );
		add_action( 'wp_ajax_trbl_gallery_images', [ $this, 'trbl_gallery_images' ] );
		add_action( 'wp_ajax_trbl_sync_categories', [ $this, 'trbl_sync_categories' ] );
		add_action( 'wp_ajax_trbl_sync_stock', [ $this, 'trbl_sync_stock' ] );
		add_action( 'wp_ajax_fix_translated_variations_relationships', [ $this, 'fix_translated_variations_relationships' ] );
		add_action( 'wp_ajax_trbl_fix_product_type_terms', [ $this, 'trbl_fix_product_type_terms' ] );
		add_action( 'wp_ajax_trbl_duplicate_terms', [ $this, 'trbl_duplicate_terms' ] );
		add_action( 'wp_ajax_register_reviews_in_st', [ $this, 'register_reviews_in_st' ] );
		add_action( 'wp_ajax_sync_deleted_meta', [ $this, 'sync_deleted_meta' ] );
	}

	public function countProducts() {
		return $this->wpdb->get_var(
			"
			SELECT COUNT( DISTINCT p.ID ) FROM {$this->wpdb->posts} AS p
				LEFT JOIN {$this->wpdb->prefix}icl_translations AS tr
				ON tr.element_id = p.ID
				WHERE p.post_status = 'publish' AND p.post_type = 'product' AND tr.source_language_code is NULL
			"
		);
	}

	public function countVariations() {
		return $this->wpdb->get_var(
			"
			SELECT COUNT( DISTINCT p.ID ) FROM {$this->wpdb->posts} AS p
				LEFT JOIN {$this->wpdb->prefix}icl_translations AS tr
				ON tr.element_id = p.ID
				WHERE p.post_status = 'publish' AND p.post_type = 'product_variation' AND tr.source_language_code is NULL
			"
		);
	}

	public function getVariableProducts() {
		$get_variation_term_taxonomy_ids = $this->wpdb->get_var( "SELECT tt.term_taxonomy_id FROM {$this->wpdb->terms} AS t LEFT JOIN {$this->wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable'" );
		$get_variation_term_taxonomy_ids = apply_filters( 'wcml_variation_term_taxonomy_ids', (array) $get_variation_term_taxonomy_ids );

		return $this->wpdb->get_results(
			"
			SELECT tr.element_id as id,tr.language_code as lang FROM {$this->wpdb->prefix}icl_translations AS tr LEFT JOIN {$this->wpdb->term_relationships} as t ON tr.element_id = t.object_id LEFT JOIN {$this->wpdb->posts} AS p ON tr.element_id = p.ID
				WHERE p.post_status = 'publish' AND tr.source_language_code is NULL AND tr.element_type = 'post_product' AND t.term_taxonomy_id IN (" . DB::prepareIn( $get_variation_term_taxonomy_ids, '%d' ) . ") ORDER BY tr.element_id
			",
			ARRAY_A
		);
	}

	public function countVariableProducts() {
		$variableProducts = $this->getVariableProducts();
		return count( $variableProducts );
	}

	public function getRemainingVariableProducts() {
		$variableProducts = get_option( self::OPTION_PRODUCTS_WITH_VARIATIONS );
		if ( false === $variableProducts ) {
			$variableProducts = $this->getVariableProducts();
		}
		return $variableProducts;
	}

	public function setRemainingVariableProducts( $variableProducts ) {
		if ( empty( $variableProducts ) ) {
			delete_option( 'wcml_products_to_sync' );
		} else {
			update_option( 'wcml_products_to_sync', $variableProducts );
		}
	}

	public function trbl_sync_variations() {
		self::checkNonce( 'trbl_sync_variations' );

		$response = [
			'processed' => 0,
			'complete'  => false,
		];

		$all_active_lang          = $this->sitepress->get_active_languages();
		$variableProducts         = $this->getRemainingVariableProducts();
		$variableProductsForRound = array_slice( $variableProducts, 0, self::ITEMS_PER_AJAX_MIN, true );

		foreach ( $variableProductsForRound as $key => $product ) {
			$translationsLanguages = [];
			foreach ( $all_active_lang as $language ) {
				if ( $language['code'] != $product['lang'] ) {
					$translationId = apply_filters( 'wpml_object_id', $product['id'], 'product', false, $language['code'] );
					if ( ! is_null( $translationId ) ) {
						$translationsLanguages[ $translationId ] = $language['code'];
					}
				}
				unset( $variableProducts[ $key ] );
			}
			if ( ! empty( $translationsLanguages ) ) {
				do_action(
					\WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_COMPONENT,
					get_post( $product['id'] ),
					array_keys( $translationsLanguages ),
					$translationsLanguages,
					\WCML\Synchronization\Store::COMPONENT_VARIATIONS
				);
			}
		}

		$this->setRemainingVariableProducts( $variableProducts );

		$wcml_settings = get_option( '_wcml_settings' );
		if ( isset( $wcml_settings['notifications']['varimages'] ) ) {
			$wcml_settings['notifications']['varimages']['show'] = 0;
			update_option( '_wcml_settings', $wcml_settings );
		}

		$response['processed'] = count( $variableProductsForRound );
		$response['complete']  = empty( $variableProducts );
		wp_send_json_success( $response );
	}

	public function getProductsForGallerySync( $limit = false ) {
		$queryLimit= '';
		if ( $limit ) {
			$queryLimit = ' ORDER BY p.ID LIMIT ' . self::ITEMS_PER_AJAX;
		}
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT DISTINCT p.ID FROM {$this->wpdb->posts} AS p
					LEFT JOIN {$this->wpdb->prefix}icl_translations AS tr
					ON tr.element_id = p.ID
					WHERE p.post_status = 'publish' AND p.post_type = 'product' AND tr.source_language_code is NULL
					AND ( SELECT COUNT( pm.meta_key ) FROM {$this->wpdb->postmeta} AS pm WHERE pm.post_id = p.ID AND pm.meta_key = %s ) = 0
					{$queryLimit}
				",
				self::META_GALLERY_SYNC
			)
		);
	}

	public function countProductsForGallerySync() {
		$productsForGallerySync = $this->getProductsForGallerySync();
		return count( $productsForGallerySync );
	}

	public function trbl_gallery_images() {
		self::checkNonce( 'trbl_gallery_images' );

		$productsForGallerySync = $this->getProductsForGallerySync( true );

		foreach ( $productsForGallerySync as $product ) {
			$this->woocommerce_wpml->media->sync_product_gallery_to_all_languages( $product->ID );
			add_post_meta( $product->ID, self::META_GALLERY_SYNC, true );
		}

		$response = [
			'processed' => count( $productsForGallerySync ),
			'complete'  => count( $productsForGallerySync ) < self::ITEMS_PER_AJAX,
		];

		if ( $response ['complete'] ) {
			$this->wpdb->delete ( $this->wpdb->postmeta, [ 'meta_key' => self::META_GALLERY_SYNC ] );
			$this->wpdb->delete ( $this->wpdb->postmeta, [ 'meta_key' => self::META_GALLERY_SYNC_LEGACY ] );
		}

		wp_send_json_success( $response );
	}

	public function getProductCategoriesForTermMetaSync( $limit = false ) {
		$queryLimit = '';
		if ( $limit ) {
			$queryLimit = ' ORDER BY t.term_taxonomy_id LIMIT ' . self::ITEMS_PER_AJAX;
		}
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT t.term_taxonomy_id,t.term_id,tr.language_code FROM {$this->wpdb->term_taxonomy} AS t
					LEFT JOIN {$this->wpdb->prefix}icl_translations AS tr
					ON tr.element_id = t.term_taxonomy_id
					WHERE t.taxonomy = 'product_cat' AND tr.element_type = 'tax_product_cat' AND tr.source_language_code is NULL
					AND ( SELECT COUNT( tm.meta_key ) FROM {$this->wpdb->termmeta} AS tm WHERE tm.term_id = t.term_id AND tm.meta_key = %s ) = 0
					{$queryLimit}
				",
				self::META_CAT_META_SYNC
			)
		);
	}

	public function countProductCategoriesForTermMetaSync() {
		$productCategoriesForTermMetaSync = $this->getProductCategoriesForTermMetaSync();
		return count( $productCategoriesForTermMetaSync );
	}

	public function trbl_sync_categories() {
		self::checkNonce( 'trbl_sync_categories' );

		$productCategoriesForTermMetaSync = $this->getProductCategoriesForTermMetaSync( true );

		foreach ( $productCategoriesForTermMetaSync as $category ) {
			update_term_meta( $category->term_id, self::META_CAT_META_SYNC, true );
			$trid         = $this->sitepress->get_element_trid( $category->term_taxonomy_id, 'tax_product_cat' );
			$translations = $this->sitepress->get_element_translations( $trid, 'tax_product_cat' );
			$type         = get_term_meta( $category->term_id, 'display_type', true );
			$thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
			foreach ( $translations as $translation ) {
				if ( $translation->language_code != $category->language_code ) {
					update_term_meta( $translation->term_id, 'display_type', $type );
					update_term_meta( $translation->term_id, 'thumbnail_id', apply_filters( 'wpml_object_id', $thumbnail_id, 'attachment', true, $translation->language_code ) );
				}
			}
		}

		$response = [
			'processed' => count( $productCategoriesForTermMetaSync ),
			'complete'  => count( $productCategoriesForTermMetaSync ) < self::ITEMS_PER_AJAX,
		];

		if ( $response ['complete'] ) {
			$this->wpdb->delete ( $this->wpdb->termmeta, [ 'meta_key' => self::META_CAT_META_SYNC ] );
		}

		wp_send_json_success( $response );
	}

	public function getProductsAndVariationsForStockSync( $limit = false ) {
		$queryLimit = '';
		if ( $limit ) {
			$queryLimit = ' ORDER BY p.ID LIMIT ' . self::ITEMS_PER_AJAX_MIN;
		}
		return $this->wpdb->get_results(
			"
			SELECT p.ID, t.trid, t.element_type
				FROM {$this->wpdb->posts} p
				JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
				WHERE p.post_type in ('product', 'product_variation') AND t.source_language_code IS NULL
				{$queryLimit}
      "
		);
	}

	public function getRemainingProductsAndVariationsForStockSync() {
		$itemsForStockSync = get_option( self::OPTION_PRODUCTS_AND_VARIATIONS_FOR_STOCK_SYNC );
		if ( false === $itemsForStockSync ) {
			$itemsForStockSync = $this->getProductsAndVariationsForStockSync();
		}
		return $itemsForStockSync;
	}

	public function setRemainingProductsAndVariationsForStockSync( $itemsForStockSync ) {
		if ( empty( $itemsForStockSync ) ) {
			delete_option( self::OPTION_PRODUCTS_AND_VARIATIONS_FOR_STOCK_SYNC );
		} else {
			update_option( self::OPTION_PRODUCTS_AND_VARIATIONS_FOR_STOCK_SYNC, $itemsForStockSync );
		}
	}

	public function trbl_sync_stock() {
		self::checkNonce( 'trbl_sync_stock' );

		$response = [
			'processed' => 0,
			'complete'  => false,
		];

		$itemsForStockSync        = $this->getRemainingProductsAndVariationsForStockSync();
		$itemsForStockSyncInRound = array_slice( $itemsForStockSync, 0, self::ITEMS_PER_AJAX_MIN, true );

		foreach ( $itemsForStockSyncInRound as $key => $product ) {
			$translations          = $this->sitepress->get_element_translations( $product->trid, $product->element_type );
			$translationsIds       = [];
			$translationsLanguages = [];
			foreach ( $translations as $translation ) {
				if ( (int) $product->ID !== (int) $translation->element_id ) {
					$translationsIds[] = $translation->element_id;
					$translationsLanguages[ $translation->element_id ] = $translation->language_code;
				}
			}
			unset( $itemsForStockSync[ $key ] );
			do_action(
				\WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_COMPONENT,
				get_post( $product->ID ),
				$translationsIds,
				$translationsLanguages,
				\WCML\Synchronization\Store::COMPONENT_STOCK
			);
		}

		$this->setRemainingProductsAndVariationsForStockSync( $itemsForStockSync );

		$response['processed'] = count( $itemsForStockSyncInRound );
		$response['complete']  = empty( $itemsForStockSync );

		wp_send_json_success( $response );
	}

	public function getVariationsForLanguageAssignment( $limit = false ) {
		$queryLimit = '';
		if ( $limit ) {
			$queryLimit = ' ORDER BY post_id LIMIT ' . self::ITEMS_PER_AJAX;
		}
		return $this->wpdb->get_results(
			"
			SELECT post_id, meta_value FROM {$this->wpdb->postmeta}
				WHERE meta_key ='_wcml_duplicate_of_variation'
				{$queryLimit}
			"
		);
	}

	public function getRemainingVariationsForLanguageAssignment() {
		$itemsForLanguageAssignment = get_option( self::OPTION_VARIATIONS_FOR_LANGUAGE_ASSIGNMENT );
		if ( false === $itemsForLanguageAssignment ) {
			$itemsForLanguageAssignment = $this->getVariationsForLanguageAssignment();
		}
		return $itemsForLanguageAssignment;
	}

	public function setRemainingVariationsForLanguageAssignment( $itemsForLanguageAssignment ) {
		if ( empty( $itemsForLanguageAssignment ) ) {
			delete_option( self::OPTION_VARIATIONS_FOR_LANGUAGE_ASSIGNMENT );
		} else {
			update_option( self::OPTION_VARIATIONS_FOR_LANGUAGE_ASSIGNMENT, $itemsForLanguageAssignment );
		}
	}

	/**
	 * @param int    $element_id
	 * @param string $element_type
	 *
	 * @return object|null
	 */
	private function get_translation_info_for_element( $element_id, $element_type ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"
				SELECT trid, translation_id FROM {$this->wpdb->prefix}icl_translations
					WHERE element_id = %d AND element_type = %s
				",
				$element_id,
				$element_type
			)
		);
	}

	public function fix_translated_variations_relationships() {
		self::checkNonce( 'fix_relationships' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access error' );
		}

		$response = [
			'processed' => 0,
			'complete'  => false,
		];

		$translatedVariations        = $this->getRemainingVariationsForLanguageAssignment();
		$translatedVariationsInRound = array_slice( $translatedVariations, 0, self::ITEMS_PER_AJAX, true );

		foreach ( $translatedVariationsInRound as $key => $translated_variation ) {
			// check relationships.
			$tr_info_for_original_variation = $this->get_translation_info_for_element( $translated_variation->meta_value, 'post_product_variation' );

			$language = $this->sitepress->get_language_for_element( wp_get_post_parent_id( $translated_variation->meta_value ), 'post_product' );

			if ( ! $language ) {
				unset( $translatedVariations[ $key ] );
				continue;
			}

			$language_current = $this->sitepress->get_language_for_element( wp_get_post_parent_id( $translated_variation->post_id ), 'post_product' );

			$tr_info_for_current_variation = $this->get_translation_info_for_element( $translated_variation->post_id, 'post_product_variation' );

			// delete wrong element_type for exists variations.
			if ( ! $tr_info_for_current_variation ) {
				$tr_info_for_current_variation = $this->get_translation_info_for_element( $translated_variation->post_id, 'post_product' );
				if ( $tr_info_for_current_variation ) {
					$this->wpdb->update( $this->wpdb->prefix . 'icl_translations', [ 'element_type' => 'post_product_variation' ], [ 'translation_id' => $tr_info_for_current_variation->translation_id ] );
				}
			}

			$check_duplicated_post_type = $this->get_translation_info_for_element( $translated_variation->post_id, 'post_product' );
			if ( $check_duplicated_post_type ) {
				$this->wpdb->delete( $this->wpdb->prefix . 'icl_translations', [ 'translation_id' => $check_duplicated_post_type->translation_id ] );
			}

			// set language info for variation if not exists.
			if ( ! $tr_info_for_original_variation ) {

				$tr_info_for_original_variation = $this->get_translation_info_for_element( $translated_variation->meta_value, 'post_product' );
				if ( $tr_info_for_original_variation ) {
					$this->wpdb->update( $this->wpdb->prefix . 'icl_translations', [ 'element_type' => 'post_product_variation' ], [ 'translation_id' => $tr_info_for_original_variation->translation_id ] );
				} else {
					$this->sitepress->set_element_language_details( $translated_variation->meta_value, 'post_product_variation', $tr_info_for_current_variation->trid, $language );
					$tr_info_for_original_variation = $this->get_translation_info_for_element( $translated_variation->meta_value, 'post_product' );
				}

				$this->wpdb->update( $this->wpdb->prefix . 'icl_translations', [ 'source_language_code' => $language ], [ 'translation_id' => $tr_info_for_current_variation->translation_id ] );
			}

			if ( $tr_info_for_original_variation->trid != $tr_info_for_current_variation->trid ) {

				$this->wpdb->update(
					$this->wpdb->prefix . 'icl_translations',
					[
						'trid'                 => $tr_info_for_original_variation->trid,
						'language_code'        => $language_current,
						'source_language_code' => $language,
					],
					[ 'translation_id' => $tr_info_for_current_variation->translation_id ]
				);

			}

			unset( $translatedVariations[ $key ] );
		}

		$this->setRemainingVariationsForLanguageAssignment( $translatedVariations );

		$response['processed'] = count( $translatedVariationsInRound );
		$response['complete']  = empty( $translatedVariations );

		wp_send_json_success( $response );
	}
	
	public function trbl_fix_product_type_terms() {
		self::checkNonce( 'trbl_product_type_terms' );

		// Delete product_type terms translations and fix relationships.
		WCML_Install::check_product_type_terms();

		// Mark the product_type taxonomy as non-translatable.
		$sync_settings                 = $this->sitepress->get_setting( 'taxonomies_sync_option', [] );
		$sync_settings['product_type'] = 0;
		$this->sitepress->set_setting( 'taxonomies_sync_option', $sync_settings, true );

		$response = [
			'complete' => true,
		];
		wp_send_json_success( $response );
	}

	public function trbl_duplicate_terms() {
		self::checkNonce( 'trbl_duplicate_terms' );

		$attr  = $_POST['attr'] ?? false;
		$terms = [];

		if ( $attr ) {
			$terms     = get_terms( $attr, 'hide_empty=0' );
			$languages = $this->sitepress->get_active_languages();
			foreach ( $terms as $term ) {
				foreach ( $languages as $language ) {
					$tr_id = apply_filters( 'wpml_object_id', $term->term_id, $attr, false, $language['code'] );

					if ( is_null( $tr_id ) ) {
						$term_args = [];
						// hierarchy - parents.
						if ( is_taxonomy_hierarchical( $attr ) ) {
							// fix hierarchy.
							if ( $term->parent ) {
								$original_parent_translated = apply_filters( 'wpml_object_id', $term->parent, $attr, false, $language['code'] );
								if ( $original_parent_translated ) {
									$term_args['parent'] = $original_parent_translated;
								}
							}
						}

						// TODO It seems that WPML supports now using the same slug in multiple languages. Check, and adjust.
						$term_name         = $term->name;
						$slug              = $term->name . '-' . $language['code'];
						$slug              = WPML_Terms_Translations::term_unique_slug( $slug, $attr, $language['code'] );
						$term_args['slug'] = $slug;

						$new_term = wp_insert_term( $term_name, $attr, $term_args );
						if ( is_wp_error( $new_term ) ) {
							$tt_id = $this->sitepress->get_element_trid( $term->term_taxonomy_id, 'tax_' . $attr );
							$this->sitepress->set_element_language_details( $new_term['term_taxonomy_id'], 'tax_' . $attr, $tt_id, $language['code'] );
						}
					}
				}
			}
		}

		$response = [
			'processed' => count( $terms ),
			'complete'  => true,
		];
		wp_send_json_success( $response );
	}

	public function register_reviews_in_st() {
		self::checkNonce( 'register_reviews_in_st' );

		make( \WCML\Reviews\Translations\Mapper::class )->registerMissingReviewStrings();
		
		$response = [
			'complete' => true,
		];
		wp_send_json_success( $response );
	}

	public function getItemsForMetaCleanup( $limit = false ) {
		$queryLimit = '';
		if ( $limit ) {
			$queryLimit = ' ORDER BY p.ID LIMIT ' . self::ITEMS_PER_AJAX_MIN;
		}
		return $this->wpdb->get_results(
			"
			SELECT p.ID, t.trid, t.element_type
				FROM {$this->wpdb->posts} p
				JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
				WHERE p.post_type in ('product', 'product_variation') AND t.source_language_code IS NULL
				{$queryLimit}
      "
		);
	}

	public function getRemainingItemsForMetaCleanup() {
		$itemsForMetaCleanup = get_option( self::OPTION_PRODUCTS_AND_VARIATIONS_FOR_META_CLEANUP );
		if ( false === $itemsForMetaCleanup ) {
			$itemsForMetaCleanup = $this->getItemsForMetaCleanup();
		}
		return $itemsForMetaCleanup;
	}

	public function setRemainingItemsForMetaCleanup( $itemsForMetaCleanup ) {
		if ( empty( $itemsForMetaCleanup ) ) {
			delete_option( self::OPTION_PRODUCTS_AND_VARIATIONS_FOR_META_CLEANUP );
		} else {
			update_option( self::OPTION_PRODUCTS_AND_VARIATIONS_FOR_META_CLEANUP, $itemsForMetaCleanup );
		}
	}

	public function sync_deleted_meta() {
		self::checkNonce( 'sync_deleted_meta' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access error' );
		}

		$response = [
			'processed' => 0,
			'complete'  => false,
		];

		$itemsForMetaCleanup           = $this->getRemainingItemsForMetaCleanup();
		$getItemsForMetaCleanupInRound = array_slice( $itemsForMetaCleanup, 0, self::ITEMS_PER_AJAX, true );

		$iclTranslationManagement = wpml_load_core_tm();
		$settings_factory         = new WPML_Custom_Field_Setting_Factory( $iclTranslationManagement );

		foreach ( $getItemsForMetaCleanupInRound as $key => $product ) {

			$translations = $this->sitepress->get_element_translations( $product->trid, $product->element_type );

			foreach ( $translations as $translation ) {
				if ( ! $translation->original ) {
					$all_post_meta_keys = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT meta_key FROM {$this->wpdb->postmeta} WHERE post_id = %d", $translation->element_id ) );
					foreach ( $all_post_meta_keys as $meta_key ) {
						$setting = $settings_factory->post_meta_setting( $meta_key );
						if ( WPML_COPY_CUSTOM_FIELD === $setting->status() ) {
							if ( ! metadata_exists( 'post', $product->ID, $meta_key ) ) {
								delete_post_meta( $translation->element_id, $meta_key );
							}
						}
					}
				}
			}

			unset( $itemsForMetaCleanup[ $key ] );
		}

		$this->setRemainingItemsForMetaCleanup( $itemsForMetaCleanup );

		$response['processed'] = count( $getItemsForMetaCleanupInRound );
		$response['complete'] = empty( $itemsForMetaCleanup );

		wp_send_json_success( $response );
	}

	/**
	 * @param string $action
	 */
	private static function checkNonce( $action ) {
		$nonce = filter_var( Obj::prop( 'wcml_nonce', $_POST ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}
	}
}
