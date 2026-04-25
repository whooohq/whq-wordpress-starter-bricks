<?php

use WCML\Terms\SuspendWpmlFiltersFactory;
use WCML\Utilities\DB;
use WCML\Utilities\SyncHash;
use WPML\FP\Fns;
use WPML\FP\Obj;

class WCML_Synchronize_Variations_Data {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var SitePress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;

	public function __construct( woocommerce_wpml $woocommerce_wpml, $sitepress, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {

		add_action( 'wp_ajax_woocommerce_remove_variations', [ $this, 'remove_translations_for_variations' ], 9 );

		/**
		 * @deprecated This AJAX call was removed in WPML 3.2 on 2015.
		 * @todo Remove this action and its public callback.
	 	 * @see https://git.onthegosystems.com/wpml/sitepress-multilingual-cms/-/commit/f4b9a84211ee789b7f9a0c028a807188f8334e5c
		 */
		add_action( 'wp_ajax_wpml_tt_save_term_translation', [ $this, 'update_taxonomy_in_variations' ], 7 );

		/**
		 * @deprecated This AJAX call was removed in WooCommerce 2.3.0 on 2014.
		 * @todo Remove this action and its public callback.
	 	 * @see https://github.com/woocommerce/woocommerce/commit/2c1c9896c5e5cdc8223c2ef253c188520b3e074c
		 */
		add_action( 'wp_ajax_woocommerce_remove_variation', [ $this, 'remove_variation_ajax' ], 9 );

	}

	/**
	 * @param string $bulk_action
	 * @param array  $data
	 * @param int    $product_id
	 *
	 * @deprecated Use \WCML\Synchronization\Hooks::synchronizeProductVariationsOnBulkEdit
	 */
	public function sync_product_variations_on_bulk_edit( $bulk_action, $data, $product_id ) {
		$this->sync_product_variations_action( $product_id );
	}

	/**
	 * @param int $productId
	 *
	 * @deprecated Use \WCML\Synchronization\Hooks::synchronizeProductVariationsOnAjax
	 */
	public function sync_product_variations_action( $productId ) {
		$isOriginal = $this->woocommerce_wpml->products->is_original_product( $productId );

		if ( ! $isOriginal ) {
			return;
		}

		$trid = $this->sitepress->get_element_trid( $productId, 'post_product' );
		if ( empty( $trid ) ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$trid = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT trid FROM {$this->wpdb->prefix}icl_translations
																	WHERE element_id = %d AND element_type = 'post_product'",
					$productId
				)
			);
			// phpcs:enable
		}
		if ( empty( $trid ) ) {
			return;	
		}

		$translationsIds       = [];
		$translationsLanguages = [];
		$translations          = $this->sitepress->get_element_translations( $trid, 'post_product' );
		foreach ( $translations as $translation ) {
			if ( ! $translation->original ) {
				$translationsIds[]                                 = $translation->element_id;
				$translationsLanguages[ $translation->element_id ] = $translation->language_code;
			}
		}

		if ( empty( $translationsIds ) ) {
			return;
		}

		$product = get_post( $productId );
		do_action( \WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_COMPONENT, $product, $translationsIds, $translationsLanguages, \WCML\Synchronization\Store::COMPONENT_VARIATIONS );
		do_action( \WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_COMPONENT, $product, $translationsIds, $translationsLanguages, \WCML\Synchronization\Store::COMPONENT_ATTRIBUTES );
	}

	/**
	 * @param int $product_id
	 *
	 * @deprecated The logic now lives in WCML_Downloadable_Products::saveProductMode and WCML_Custom_Prices::sync_product_variations_custom_prices
	 */
	public function sync_product_variations_custom_data( $product_id ) {

		$is_variable_product = $this->woocommerce_wpml->products->is_variable_product( $product_id );
		if ( $is_variable_product ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$get_all_post_variations = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT * FROM {$this->wpdb->posts}
																								WHERE post_status IN ('publish','private')
																									AND post_type = 'product_variation'
																									AND post_parent = %d
																								ORDER BY ID",
					$product_id
				)
			);
			// phpcs:enable

			foreach ( $get_all_post_variations as $post_data ) {

				// We need a single mechanism to sync prices in a product and in its variations.
				if ( (int) $this->woocommerce_wpml->settings['enable_multi_currency'] === WCML_MULTI_CURRENCIES_INDEPENDENT ) {
					$this->woocommerce_wpml->multi_currency->custom_prices->sync_product_variations_custom_prices( $post_data->ID );
				}

				// save files option.
				$this->woocommerce_wpml->downloadable->save_files_option( $post_data->ID );

			}
		}
	}

	/**
	 * @param int    $product_id
	 * @param int    $tr_product_id
	 * @param string $lang
	 * @param array  $args
	 *
	 * @todo Still used by the WCML_Editor_UI_Product_Job CTE editor manager
	 * @todo Still usd by the troubleshooting mechanism, see https://onthegosystems.myjetbrains.com/youtrack/issue/wcml-4904
	 */
	public function sync_product_variations( $product_id, $tr_product_id, $lang, $args = [] ) {
		global $wpml_post_translations;

		$default_args = [
			'editor_translations' => [],
			'is_troubleshooting'  => false,
			'is_duplicate'        => false,
		];
		$args         = wp_parse_args( $args, $default_args );

		$is_variable_product = $this->woocommerce_wpml->products->is_variable_product( $product_id );

		if ( ! $is_variable_product ) {
			return;
		}
	
		$all_variations = $this->get_product_variations( $product_id );
		if ( empty( $all_variations ) ) {
			return;
		}

		remove_action( 'save_post', [ $wpml_post_translations, 'save_post_actions' ], 100 );

		$translation_variations = [
			'insert' => [],
			'update' => [],
		];
		$delayedFields      = [];
		$current_variations = $this->get_product_variations( $tr_product_id );
		$wcml_data_store    = wcml_product_data_store_cpt();

		foreach ( $all_variations as $post_data ) {
			$original_variation_id    = $post_data->ID;
			$isNewTranslatedVariation = false;
			// save files option.
			$this->woocommerce_wpml->downloadable->save_files_option( $original_variation_id );

			$variation_id = $this->get_variation_id_by_lang( $lang, $original_variation_id );

			if ( ! empty( $variation_id ) ) {
				// Update variation.
				$this->wpdb->update(
					$this->wpdb->posts,
					[
						'post_status'       => $post_data->post_status,
						'post_modified'     => $post_data->post_modified,
						'post_modified_gmt' => $post_data->post_modified_gmt,
						'post_parent'       => $tr_product_id, // current post ID.
						'menu_order'        => $post_data->menu_order,
					],
					[ 'ID' => $variation_id ]
				);
				$translation_variations['update'][] = (int) $variation_id;
			} else {
				// Add new variation.
				$replaced_guid = str_replace( $product_id, $tr_product_id, $post_data->guid );
				$replaced_slug = str_replace( $product_id, $tr_product_id, $post_data->post_name );
				$variation_id  = wp_insert_post(
					[
						'post_author'           => $post_data->post_author,
						'post_date_gmt'         => $post_data->post_date_gmt,
						'post_content'          => $post_data->post_content,
						'post_title'            => $post_data->post_title,
						'post_excerpt'          => $post_data->post_excerpt,
						'post_status'           => $post_data->post_status,
						'comment_status'        => $post_data->comment_status,
						'ping_status'           => $post_data->ping_status,
						'post_password'         => $post_data->post_password,
						'post_name'             => $replaced_slug,
						'to_ping'               => $post_data->to_ping,
						'pinged'                => $post_data->pinged,
						'post_modified'         => $post_data->post_modified,
						'post_modified_gmt'     => $post_data->post_modified_gmt,
						'post_content_filtered' => $post_data->post_content_filtered,
						'post_parent'           => $tr_product_id, // current post ID.
						'guid'                  => $replaced_guid,
						'menu_order'            => $post_data->menu_order,
						'post_type'             => $post_data->post_type,
						'post_mime_type'        => $post_data->post_mime_type,
						'comment_count'         => $post_data->comment_count,
					]
				);
				$trid = $this->sitepress->get_element_trid( $original_variation_id, 'post_product_variation' );
				$this->sitepress->set_element_language_details( $variation_id, 'post_product_variation', $trid, $lang );
				$isNewTranslatedVariation           = true;
				$translation_variations['insert'][] = (int) $variation_id;
				$delayedFields[]                    = [
					'post_id'    => $variation_id,
					'meta_key'   => '_wcml_duplicate_of_variation',
					'meta_value' => $original_variation_id,
				];
			}

			$variationDelayedFields = $this->duplicate_variation_data( $original_variation_id, $variation_id, $args['editor_translations'], $lang, $args['is_troubleshooting'], $isNewTranslatedVariation );
			$delayedFields = array_merge( $delayedFields, $variationDelayedFields );

			// sync taxonomies.
			$this->sync_variations_taxonomies( $original_variation_id, $variation_id, $lang, $isNewTranslatedVariation );

			// sync description.
			if ( $args['is_duplicate'] ) {
				$delayedFields[] = [
					'post_id'    => $variation_id,
					'meta_key'   => '_variation_description',
					'meta_value' => get_post_meta( $original_variation_id, '_variation_description', true ),
				];
			}

			if ( isset( $args['editor_translations'][ md5( '_variation_description' . $original_variation_id ) ] ) ) {
				$delayedFields[] = [
					'post_id'    => $variation_id,
					'meta_key'   => '_variation_description',
					'meta_value' => $args['editor_translations'][ md5( '_variation_description' . $original_variation_id ) ],
				];
			}

			// sync media.
			$this->woocommerce_wpml->media->sync_variation_thumbnail_id( $original_variation_id, $variation_id, $lang );

			// sync file_paths.
			$this->woocommerce_wpml->downloadable->sync_files_to_translations( $original_variation_id, $variation_id, $args['editor_translations'] );

			$this->delete_removed_variation_attributes( $product_id, $variation_id );

			$this->woocommerce_wpml->sync_product_data->sync_product_stock( wc_get_product( $original_variation_id ), wc_get_product( $variation_id ) );

			$wcml_data_store->update_lookup_table_data( $variation_id );

		}

		$this->processDelayedFields( $delayedFields, $translation_variations['update'] );

		// Delete variations that no longer exist.
		foreach ( $current_variations as $current_post_variation ) {
			if ( ! in_array( (int) $current_post_variation->ID, $translation_variations['update'] ) ) {
				wp_delete_post( $current_post_variation->ID, true );
			}
		}

		// refresh parent-children transients.
		delete_transient( 'wc_product_children_' . $tr_product_id );
		delete_transient( '_transient_wc_product_children_ids_' . $tr_product_id );

		// This is independent of variations translations
		// Might be managed in the higher level with variations prices and downloadable options.
		$this->sync_prices_variation_ids( $product_id, $tr_product_id, $lang );

		add_action( 'save_post', [ $wpml_post_translations, 'save_post_actions' ], 100, 2 );

		foreach ( $current_variations as $current_post_variation ) {
			if ( in_array( (int) $current_post_variation->ID, $translation_variations['update'], true ) ) {
				wp_cache_delete( $current_post_variation->ID, 'post_meta' );
			}
		}
	}

	/**
	 * @param string $lang
	 * @param int    $original_variation_id
	 *
	 * @return int|null
	 */
	public function get_variation_id_by_lang( $lang, $original_variation_id ) {
		return $this->sitepress->get_object_id( $original_variation_id, 'product_variation', false, $lang );
	}

	/**
	 * @param int    $original_variation_id
	 * @param int    $tr_variation_id
	 * @param string $lang
	 * @param bool   $isNewTranslatedVariation
	 *
	 * @deprecated Use \WCML\Synchronization\Component\VariationTaxonomies::run
	 * @todo Still used by the variation synchronization main method here.
	 */
	public function sync_variations_taxonomies( $original_variation_id, $tr_variation_id, $lang, $isNewTranslatedVariation = false ) {
		global $wpml_term_translations;
		$returnTrue = Fns::always( true );
		add_filter( 'wpml_disable_term_adjust_id', $returnTrue );
		$filtersSuspend = SuspendWpmlFiltersFactory::create();

		$taxonomies       = get_object_taxonomies( 'product_variation' );
		$taxonomiesToSync = array_values( array_diff( $taxonomies, [ 'translation_priority' ] ) );
		/**
		 * Filters the taxonomy objects to synchronize.
		 *
		 * @since 5.2.0
		 *
		 * @param string[]   $taxonomiesToSync
		 * @param int|string $original_variation_id
		 * @param int|string $tr_variation_id
		 * @param string     $lang
		 */
		$taxonomiesToSync = apply_filters( 'wcml_product_variations_taxonomies_to_sync', $taxonomiesToSync, $original_variation_id, $tr_variation_id, $lang );
		$found      = false;
		$all_terms  = WPML_Non_Persistent_Cache::get( $original_variation_id, __CLASS__, $found );
		if ( ! $found ) {
			$all_terms  = wp_get_object_terms( $original_variation_id, $taxonomiesToSync );
			if ( is_wp_error( $all_terms ) ) {
				$all_terms = [];
			}
			WPML_Non_Persistent_Cache::set( $original_variation_id, $all_terms, __CLASS__ );
		}

		foreach ( $taxonomiesToSync as $taxonomy ) {
			$terms = array_filter(
				$all_terms,
				function ( $term ) use ( $taxonomy ) {
					return $term->taxonomy === $taxonomy;
				}
			);

			if ( empty( $terms ) ) {
				if (
					! $isNewTranslatedVariation &&
					! $this->woocommerce_wpml->terms->is_translatable_wc_taxonomy( $taxonomy )
				) {
					wp_set_object_terms( $tr_variation_id, [], $taxonomy );
				}
				continue;
			}

			$tt_ids         = [];
			$tt_ids_trans   = [];
			$term_ids       = [];
			$term_ids_trans = [];

			foreach ( $terms as $term ) {
				if ( $this->sitepress->is_translated_taxonomy( $taxonomy ) ) {
					$tt_ids[] = $term->term_taxonomy_id;
				} else {
					$term_ids[] = $term->term_id;
				}
			}

			foreach ( $tt_ids as $tt_id ) {
				// Avoid the wpml_object_id filter to escape from the WPML_Term_Translations::maybe_warm_term_id_cache() hell
				// given that we invalidate the cache at every step on wp_set_post_terms().
				$tt_id_trans = $wpml_term_translations->element_id_in( $tt_id, $lang );
				if ( $tt_id_trans ) {
					$tt_ids_trans[] = $tt_id_trans;
				}
			}

			$tt_ids_trans = array_values( array_unique( array_map( 'intval', $tt_ids_trans ) ) );
			if ( ! empty( $tt_ids_trans ) ) {
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				// phpcs:disable Squiz.Strings.DoubleQuoteUsage.NotRequired
				$term_ids_trans = $this->wpdb->get_col(
					$this->wpdb->prepare(
						"SELECT term_id FROM {$this->wpdb->term_taxonomy} WHERE term_taxonomy_id IN (" . DB::prepareIn( $tt_ids_trans, '%d' ) . ") LIMIT %d",
						count( $tt_ids_trans )
					)
				);
				// phpcs:enable
			}

			$terms_to_sync = array_merge( $term_ids, $term_ids_trans );
			$terms_to_sync = array_unique( array_map( 'intval', $terms_to_sync ) );

			if ( empty( $terms_to_sync ) ) {
				continue;
			}
			// set the fourth parameter in 'true' because we need to add new terms, instead of replacing all.
			wp_set_object_terms( $tr_variation_id, $terms_to_sync, $taxonomy, true );
		}

		remove_filter( 'wpml_disable_term_adjust_id', $returnTrue );
		$filtersSuspend->resume();
	}

	/**
	 * @param int    $original_variation_id
	 * @param int    $variation_id
	 * @param array  $data
	 * @param string $lang
	 * @param bool   $trbl
	 * @param bool   $deprecatedBool
	 *
	 * @return array
	 */
	public function duplicate_variation_data( $original_variation_id, $variation_id, $data, $lang, $trbl, $deprecatedBool = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $iclTranslationManagement;
		$settings      = $iclTranslationManagement->settings['custom_fields_translation'];
		$all_meta      = get_post_custom( $original_variation_id );
		$post_fields   = null;
		$excludedKeys  = WPML_Post_Custom_Field_Setting_Keys::get_excluded_keys();
		$delayedFields = [];

		unset( $all_meta[ SyncHash::META_KEY ] );

		foreach ( $all_meta as $meta_key => $meta ) {
			if ( in_array( $meta_key, $excludedKeys, true ) ) {
				continue;
			}

			$meta_value = reset( $meta );
			if ( ! $meta_value ) {
				$meta_value = '';
			}

			if ( substr( $meta_key, 0, 10 ) === 'attribute_' ) {
				if ( '' !== $meta_value ) {
					$trn_post_meta = $this->woocommerce_wpml->attributes->get_translated_variation_attribute_post_meta( $meta_value, $meta_key, $original_variation_id, $variation_id, $lang );
					$meta_value    = $trn_post_meta['meta_value'];
					$meta_key      = $trn_post_meta['meta_key'];
				} else {
					$meta_value = '';
				}
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				$delayedFields[] = [
					'post_id'    => $variation_id,
					'meta_key'   => $meta_key,
					'meta_value' => maybe_unserialize( $meta_value ),
				];
				// phpcs:enable
				continue;
			}

			if ( ! isset( $settings[ $meta_key ] ) || WPML_IGNORE_CUSTOM_FIELD === (int) $settings[ $meta_key ] ) {
				continue;
			}

			if (
				in_array( $meta_key, [ '_sale_price', '_regular_price', '_price' ], true ) &&
				( $trbl || WCML_MULTI_CURRENCIES_INDEPENDENT === (int) $this->woocommerce_wpml->settings['enable_multi_currency'] )
			) {
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				$delayedFields[] = [
					'post_id'    => $variation_id,
					'meta_key'   => $meta_key,
					'meta_value' => $meta_value,
				];
				// phpcs:enable
				continue;
			}

			if ( (int) Obj::prop( $meta_key, $settings ) === WPML_COPY_CUSTOM_FIELD ) {
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				$delayedFields[] = [
					'post_id'    => $variation_id,
					'meta_key'   => $meta_key,
					'meta_value' => $meta_value,
				];
				// phpcs:enable
				continue;
			}

			if ( (int) Obj::prop( $meta_key, $settings ) === WPML_TRANSLATE_CUSTOM_FIELD ) {
				$post_fields = $this->woocommerce_wpml->sync_product_data->sync_custom_field_value( $meta_key, $data, $variation_id, $post_fields, $original_variation_id, true );
				continue;
			}
		}

		WCML_Synchronize_Product_Data::syncDeletedCustomFields( $original_variation_id, $variation_id );

		return $delayedFields;
	}

	public function delete_removed_variation_attributes( $orig_product_id, $variation_id ) {

		$original_product_attr = get_post_meta( $orig_product_id, '_product_attributes', true );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$get_all_variation_attributes = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE 'attribute_%%' ",
				$variation_id
			)
		);
		// phpcs:enable

		foreach ( $get_all_variation_attributes as $variation_attribute ) {
			$attribute_name = substr( $variation_attribute->meta_key, 10 );
			if ( ! isset( $original_product_attr[ $attribute_name ] ) ) {
				delete_post_meta( $variation_id, $variation_attribute->meta_key );
			}
		}

	}

	public function get_product_variations( $product_id ) {

		$cache_key               = $product_id;
		$cache_group             = 'product_variations';
		$temp_product_variations = wp_cache_get( $cache_key, $cache_group );
		if ( $temp_product_variations ) {
			return $temp_product_variations;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$variations = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->wpdb->posts}
								 WHERE post_status IN ('publish','private')
									AND post_type = 'product_variation'
									AND post_parent = %d ORDER BY ID",
				$product_id
			)
		);
		// phpcs:enable

		wp_cache_set( $cache_key, $variations, $cache_group );

		return $variations;
	}

	public function remove_translations_for_variations() {
		check_ajax_referer( 'delete-variations', 'security' );

		if ( ! current_user_can( 'edit_products' ) ) {
			die( -1 );
		}
		$variation_ids = (array) $_POST['variation_ids'];

		foreach ( $variation_ids as $variation_id ) {
			$trid         = $this->sitepress->get_element_trid( $variation_id, 'post_product_variation' );
			$translations = $this->sitepress->get_element_translations( $trid, 'post_product_variation' );

			foreach ( $translations as $translation ) {
				if ( ! $translation->original ) {
					wp_delete_post( $translation->element_id );
				}
			}
		}
	}

	/**
	 * Update taxonomy in variations.
	 *
	 * @deprecated This AJAX call was removed in WPML 3.2 on 2015.
	 * @see https://git.onthegosystems.com/wpml/sitepress-multilingual-cms/-/commit/f4b9a84211ee789b7f9a0c028a807188f8334e5c
	 */
	public function update_taxonomy_in_variations() {
		_doing_it_wrong(
			'WCML_Synchronize_Variations_Data::update_taxonomy_in_variations',
			__( 'This method is no longer executed by WPML.' ),
			'5.3.5'
		);
	}

	/**
	 * Remove single variation.
	 *
	 * @deprecated This AJAX call was removed in WooCommerce 2.3.0 on 2014.
	 * @see https://github.com/woocommerce/woocommerce/commit/2c1c9896c5e5cdc8223c2ef253c188520b3e074c
	 *
	 * We can add the original nonce validation.
	 */
	public function remove_variation_ajax() {
		check_ajax_referer( 'delete-variation', 'security' );

		if ( ! current_user_can( 'edit_products' ) ) {
			die( -1 );
		}

		if ( isset( $_POST['variation_id'] ) ) {
			$trid = $this->sitepress->get_element_trid( (int) filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT ), 'post_product_variation' );
			if ( $trid ) {
				$translations = $this->sitepress->get_element_translations( $trid, 'post_product_variation' );
				if ( $translations ) {
					foreach ( $translations as $translation ) {
						if ( ! $translation->original ) {
							wp_delete_post( $translation->element_id, true );
						}
					}
				}
			}
		}
	}

	/**
	 * Synchronize prices variation ids for product
	 *
	 * @param int    $product_id
	 * @param int    $tr_product_id
	 * @param string $language
	 */
	public function sync_prices_variation_ids( $product_id, $tr_product_id, $language ) {

		$prices_variation_ids_fields = [
			'_min_price_variation_id',
			'_min_regular_price_variation_id',
			'_min_sale_price_variation_id',
			'_max_price_variation_id',
			'_max_regular_price_variation_id',
			'_max_sale_price_variation_id',
		];

		foreach ( $prices_variation_ids_fields as $price_field ) {

			$original_price_variation_id = get_post_meta( $product_id, $price_field, true );

			if ( $original_price_variation_id ) {
				$translated_price_variation_id = apply_filters( 'wpml_object_id', $original_price_variation_id, 'product_variation', false, $language );
				if ( ! is_null( $translated_price_variation_id ) ) {
					update_post_meta( $tr_product_id, $price_field, $translated_price_variation_id );
				}
			}
		}
	}

	/**
	 * @param array $delayedFields
	 * @param array $existingVariationTranslations
	 */
	private function processDelayedFields( $delayedFields, $existingVariationTranslations ) {
		if ( empty( $delayedFields ) ) {
			return;
		}

		// Get all pairs post_id/meta_key from variation translations already existing.
		// Group them by variation ID.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$allMetaData = empty( $existingVariationTranslations) ? [] : $this->wpdb->get_results(
			"SELECT meta_id, post_id, meta_key FROM {$this->wpdb->postmeta} where post_id IN (" . DB::prepareIn( $existingVariationTranslations, '%d' ) . ")",
			ARRAY_A
		);
		// phpcs:enable
		$metaDataByVariationIds = [];
		foreach ( $allMetaData as $metaData ) {
			$metaDataByVariationIds[ $metaData['post_id'] ][ $metaData['meta_id'] ] = $metaData['meta_key'];
		}

		// Loop over the delated fields to clasify them by the field status:
		// - if the meta key exists for its variation ID, it is set to update.
		// - otherwise, it is set to insert.
		$delayedFieldsActions = [];
		foreach ( $delayedFields as $delayedFieldData ) {
			$fieldPostId                                         = $delayedFieldData['post_id'];
			$fieldMetaKey                                        = $delayedFieldData['meta_key'];
			$fieldMetaValue                                      = $delayedFieldData['meta_value'];
			$delayedFieldsActions[ $fieldMetaKey ]['meta_value'] = $fieldMetaValue;
			$metaDataByVariationId                               = Obj::propOr( [], $fieldPostId, $metaDataByVariationIds );
			if ( in_array( $fieldMetaKey, $metaDataByVariationId, true ) ) {
				$fieldMetaIds = array_keys( $metaDataByVariationId, $fieldMetaKey );
				if ( count( $fieldMetaIds ) > 1 ) {
					$delayedFieldsActions[ $fieldMetaKey ]['delete'][ $fieldPostId ] = $fieldMetaIds;
					$delayedFieldsActions[ $fieldMetaKey ]['insert'][ $fieldPostId ] = $fieldMetaValue;
				} else {
					$delayedFieldsActions[ $fieldMetaKey ]['update'][ $fieldMetaValue ][ $fieldPostId ] = $fieldPostId;
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
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
				$this->wpdb->query(
						"DELETE FROM {$this->wpdb->postmeta}
						WHERE meta_id IN (" . DB::prepareIn( $metaIdsToDelete, '%d' ) . ")"
				);
				// phpcs:enable
			}

			// Insert all post_id/meta_key/meta_value groups at once, per meta_key.
			// For each meta key, data is made of pairs [ post ID => metaValue ] for easier insertion.
			// This ensures that the number of values inserted on each batch is, at most, the number of variations.
			$dataToInsert = Obj::propOr( [], 'insert', $delayedFieldMetaData );
			if ( ! empty( $dataToInsert )) {
				$insertValues = [];
				foreach ( $dataToInsert as $idToInsert => $valueToInsert ) {
					$insertValues[] = $this->wpdb->prepare( "(%d,%s,%s)", $idToInsert, $delayedFieldMetaKey, $valueToInsert );
				}
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
				$this->wpdb->query(
						"INSERT INTO {$this->wpdb->postmeta}
						(`post_id`,`meta_key`,`meta_value`)
						VALUES " . implode( ',', $insertValues )
				);
				// phpcs:enable
			}

			// Update all variations at once.
			// For each meta key, data is made of pairs [ meta value => list of affected post IDs ] so it is easier to compose IN statements.
			$dataToUpdate = Obj::propOr( [], 'update', $delayedFieldMetaData );
			if ( ! empty( $dataToUpdate ) ) {
				foreach ( $dataToUpdate as $updateMetaValue => $idsToUpdate ) {
					$idsToUpdate = array_values( array_unique( array_map( 'intval', $idsToUpdate ) ) );
					if ( ! empty( $idsToUpdate ) ) {
						// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
						$this->wpdb->query(
							$this->wpdb->prepare(
								"UPDATE {$this->wpdb->postmeta}
								SET meta_value = %s
								WHERE meta_key = %s
								AND post_id IN (" . DB::prepareIn( $idsToUpdate, '%d' ) . ")",
								$updateMetaValue,
								$delayedFieldMetaKey
							)
						);
						// phpcs:enable
					}
				}
			}
		}
	}

}
