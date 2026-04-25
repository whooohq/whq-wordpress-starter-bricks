<?php

use WCML\Terms\SuspendWpmlFiltersFactory;
use WCML\Utilities\DB;
use WCML\Utilities\SyncHash;
use function WCML\functions\isCli;

class WCML_Synchronize_Product_Data {

	const CUSTOM_FIELD_KEY_SEPARATOR = ':::';

	const PRIORITY_BEFORE_STOCK_EMAIL_TRIGGER = 9;

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var SitePress */
	private $sitepress;
	/** @var WPML_Post_Translation */
	private $post_translations;
	/** @var wpdb */
	private $wpdb;

	/**
	 * WCML_Synchronize_Product_Data constructor.
	 *
	 * @param woocommerce_wpml      $woocommerce_wpml
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translations
	 * @param wpdb                  $wpdb
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, SitePress $sitepress, WPML_Post_Translation $post_translations, wpdb $wpdb ) {
		$this->woocommerce_wpml  = $woocommerce_wpml;
		$this->sitepress         = $sitepress;
		$this->post_translations = $post_translations;
		$this->wpdb              = $wpdb;
	}

	public function add_hooks() {
		if ( is_admin() || isCli() ) {
			add_action( 'deleted_term_relationships', [ $this, 'delete_term_relationships_update_term_count' ], 10, 2 );
		}

		add_action( 'woocommerce_product_set_visibility', [ $this, 'sync_product_translations_visibility' ] );

		add_action( 'woocommerce_recorded_sales', [ $this, 'sync_product_total_sales' ] );

		add_action( 'woocommerce_product_set_stock_status', [ $this, 'sync_stock_status_for_translations' ], 100, 2 );
		add_action( 'woocommerce_variation_set_stock_status', [ $this, 'sync_stock_status_for_translations' ], 10, 2 );

		add_filter( 'future_product', [ $this, 'set_schedule_for_translations' ], 10, 2 );
	}

	/**
	 * This function takes care of synchronizing products
	 *
	 * @param int               $post_id
	 * @param WP_Post           $post
	 * @param bool              $force_valid_context
	 * @param ?\WP_REST_Request $wpRestRequest
	 *
	 * @deprecated Use \WCML\Synchronization\Hooks::synchronizeProductTranslations
	 */
	public function synchronize_products( $post_id, $post, $force_valid_context = false, $wpRestRequest = null ) {
		do_action( \WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_TRANSLATIONS, $post, [], [] );
	}

	/**
	 * @param int   $original_product_id
	 * @param int   $tr_product_id
	 * @param string $lang
	 *
	 * @deprecated Use \WCML\Synchronization\Hooks::synchronizeProductTranslations 
	 */
	public function sync_product_data( $original_product_id, $tr_product_id, $lang, $duplicate = false ) {
		do_action( \WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_TRANSLATIONS, get_post( $original_product_id ), [ $tr_product_id ], [ $tr_product_id => $lang ] );
	}

	/**
	 * @param int   $original_product_id
	 * @param int   $tr_product_id
	 * @param string $lang
	 *
	 * @todo This will be reviewed in https://onthegosystems.myjetbrains.com/youtrack/issue/wcml-4904 so we can remove the sync legacy calls.
	 */
	public function sync_product_taxonomies( $original_product_id, $tr_product_id, $lang ) {
		do_action( \WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_COMPONENT, get_post( $original_product_id ), [ $tr_product_id ], [ $tr_product_id => $lang ], \WCML\Synchronization\Store::COMPONENT_TAXONOMIES );
	}

	/**
	 * @param int   $object_id
	 * @param int[] $tt_ids    An array of term taxonomy IDs.
	 */
	public function delete_term_relationships_update_term_count( $object_id, $tt_ids ) {

		if ( get_post_type( $object_id ) === 'product' ) {

			$original_product_id = $this->post_translations->get_original_element( $object_id );
			$translations        = $this->post_translations->get_element_translations( $original_product_id, false, true );

			$filtersSuspend = SuspendWpmlFiltersFactory::create();
			foreach ( $translations as $translation ) {
				$this->wcml_update_term_count_by_ids( $tt_ids, $this->post_translations->get_element_lang_code( $translation ) );
			}
			$filtersSuspend->resume();
		}
	}


	/**
	 * @param int[]     $tt_ids    An array of term_taxonomy_id values - NOT term_id values!!!!
	 * @param string    $language
	 * @param string    $taxonomy
	 * @param int|false $tr_product_id
	 */
	public function wcml_update_term_count_by_ids( $tt_ids, $language, $taxonomy = '', $tr_product_id = false ) {
		global $wpml_term_translations;
		$tt_ids_trans = [];

		foreach ( $tt_ids as $tt_id ) {
			// Avoid the wpml_object_id filter to escape from the WPML_Term_Translations::maybe_warm_term_id_cache() hell
			// given that we invalidate the cache at every step on wp_set_post_terms().
			$tt_id_trans = $wpml_term_translations->element_id_in( $tt_id, $language );
			if ( $tt_id_trans ) {
				$tt_ids_trans[] = $tt_id_trans;
			}
		}

		$tt_ids_trans = array_values( array_unique( array_map( 'intval', $tt_ids_trans ) ) );
		
		if ( empty( $tt_ids_trans ) ) {
			return;
		}

		if ( in_array( $taxonomy, [ 'product_cat', 'product_tag' ] ) ) {
			$this->sitepress->switch_lang( $language );
			wp_update_term_count( $tt_ids_trans, $taxonomy );
			$this->sitepress->switch_lang();
		}

		if ( $tr_product_id ) {
			$t_ids = $this->wpdb->get_col(
				$this->wpdb->prepare(
					"SELECT term_id FROM {$this->wpdb->term_taxonomy} WHERE term_taxonomy_id IN (" . DB::prepareIn( $tt_ids_trans, '%d' ) . ") LIMIT %d",
					count( $tt_ids_trans )
				)
			);
			// Make sure that $t_ids is int[], otherwise wp_set_post_terms will try to insert new terms for non-hierarchical taxonomies.
			$t_ids = array_unique( array_map( 'intval', $t_ids ) );
			wp_set_post_terms( $tr_product_id, $t_ids, $taxonomy );
		}
	}

	/**
	 * @param int    $product_id
	 * @param int    $translated_product_id
	 * @param string $lang
	 *
	 * @deprecated Use \WCML\Synchronization\Component\LinkedProducts::run
	 */
	public function sync_linked_products( $product_id, $translated_product_id, $lang ) {

		$this->sync_up_sells_products( $product_id, $translated_product_id, $lang );
		$this->sync_cross_sells_products( $product_id, $translated_product_id, $lang );
		$this->sync_grouped_products( $product_id, $translated_product_id, $lang );

		// refresh parent-children transients (e.g. this child goes to private or draft)
		$translated_product_parent_id = wp_get_post_parent_id( $translated_product_id );
		if ( $translated_product_parent_id ) {
			// Those store the list of variations for a variable product
			// Considering that this is NOT running when syncing variations...
			// ... when is this running, and what for?
			// Keeping for backward compatibility, just in case.
			delete_transient( 'wc_product_children_' . $translated_product_parent_id );
			delete_transient( '_transient_wc_product_children_ids_' . $translated_product_parent_id );
		}

	}

	public function sync_up_sells_products( $product_id, $translated_product_id, $lang ) {

		$original_up_sells = maybe_unserialize( get_post_meta( $product_id, '_upsell_ids', true ) );
		$trnsl_up_sells    = [];
		if ( $original_up_sells ) {
			foreach ( $original_up_sells as $original_up_sell_product ) {
				$trnsl_up_sells[] = apply_filters( 'wpml_object_id', $original_up_sell_product, get_post_type( $original_up_sell_product ), false, $lang );
			}
		}
		update_post_meta( $translated_product_id, '_upsell_ids', $trnsl_up_sells );

	}

	public function sync_cross_sells_products( $product_id, $translated_product_id, $lang ) {

		$original_cross_sells = maybe_unserialize( get_post_meta( $product_id, '_crosssell_ids', true ) );
		$trnsl_cross_sells    = [];
		if ( $original_cross_sells ) {
			foreach ( $original_cross_sells as $original_cross_sell_product ) {
				$trnsl_cross_sells[] = apply_filters( 'wpml_object_id', $original_cross_sell_product, get_post_type( $original_cross_sell_product ), false, $lang );
			}
		}
		update_post_meta( $translated_product_id, '_crosssell_ids', $trnsl_cross_sells );

	}

	public function sync_grouped_products( $product_id, $translated_product_id, $lang ) {

		$original_children   = maybe_unserialize( get_post_meta( $product_id, '_children', true ) );
		$translated_children = [];
		if ( $original_children ) {
			foreach ( $original_children as $original_children_product ) {
				$translated_children[] = apply_filters( 'wpml_object_id', $original_children_product, get_post_type( $original_children_product ), false, $lang );
			}
		}
		update_post_meta( $translated_product_id, '_children', $translated_children );

	}

	/**
	 * @param WC_Product       $product
	 * @param WC_Product|false $translatedProduct
	 *
	 * @deprecated Use \WCML\Synchronization\Hooks::syncProductStock
	 */
	public function sync_product_stock( $product, $translatedProduct = false ) {
		$productId = $product->get_id();

		if ( $translatedProduct ) {
			$translatedProductId = $translatedProduct->get_id();
			$translations = [ $translatedProductId ];
			$translationsLanguages = [ $translatedProductId => $this->post_translations->get_element_lang_code( $translatedProductId ) ];
		} else {
			$translations = $this->post_translations->get_element_translations( $productId );
			if ( empty( $translations ) ) {
				return;
			}

			$translationsLanguages = [];
			foreach ( $translations as $index => $translation ) {
				if ( $productId === (int) $translation ) {
					unset( $translations[ $index ] );
				} else {
					$translationsLanguages[ $translation ] = $this->post_translations->get_element_lang_code( $translation );
				}
			}
		}

		do_action( \WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_COMPONENT, get_post( $productId ), $translations, $translationsLanguages, \WCML\Synchronization\Store::COMPONENT_STOCK );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @deprecated Use \WCML\Synchronization\Hooks::syncProductStock
	 */
	public function sync_product_stock_hook( $product ) {
		$productId = $product->get_id();
		$translations = $this->post_translations->get_element_translations( $productId );
		if ( empty( $translations ) ) {
			return;
		}

		$translationsLanguages = [];
		foreach ( $translations as $index => $translation ) {
			if ( $productId === (int) $translation ) {
				unset( $translations[ $index ] );
			} else {
				$translationsLanguages[ $translation ] = $this->post_translations->get_element_lang_code( $translation );
			}
		}

		do_action( \WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_COMPONENT, get_post( $productId ), $translations, $translationsLanguages, \WCML\Synchronization\Store::COMPONENT_STOCK );
	}

	/**
	 * @param int $order_id
	 */
	public function sync_product_total_sales( $order_id ) {

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {

			if ( $item instanceof WC_Order_Item_Product ) {
				$product_id = $item->get_product_id();
				$qty        = $item->get_quantity();
			} else {
				$product_id = $item['product_id'];
				$qty        = $item['qty'];
			}

			$qty = apply_filters( 'wcml_order_item_quantity', $qty, $order, $item );

			/** @var WC_Product_Data_Store_CPT */
			$data_store   = WC_Data_Store::load( 'product' );
			$translations = $this->post_translations->get_element_translations( $product_id );
			foreach ( $translations as $translation ) {
				if ( $product_id !== (int) $translation ) {
					$data_store->update_product_sales( (int) $translation, absint( $qty ), 'increase' );
				}
			}
		}
	}

	public function sync_stock_status_for_translations( $product_id, $status ) {

		if ( $this->woocommerce_wpml->products->is_original_product( $product_id ) ) {

			$translations = $this->post_translations->get_element_translations( $product_id, false, true );

			foreach ( $translations as $translation ) {
				$this->woocommerce_wpml->products->update_stock_status( $translation, $status );
				$this->wc_taxonomies_recount_after_stock_change( $translation );
			}
		}
	}

	/**
	 * @param int $product_id
	 */
	private function wc_taxonomies_recount_after_stock_change( $product_id ) {

		remove_filter( 'get_term', [ $this->sitepress, 'get_term_adjust_id' ], 1 );

		wp_cache_delete( $product_id, 'product_cat_relationships' );
		wp_cache_delete( $product_id, 'product_tag_relationships' );

		wc_recount_after_stock_change( $product_id );

		add_filter( 'get_term', [ $this->sitepress, 'get_term_adjust_id' ], 1, 1 );

	}

	/**
	 * @param int    $original_product_id
	 * @param int    $tr_product_id
	 * @param string $lang
	 *
	 * @deprecated Use \WCML\Synchronization\Component\Post::run
	 */
	public function sync_date_and_parent( $original_product_id, $tr_product_id, $lang ) {
		$tr_parent_id = apply_filters( 'wpml_object_id', wp_get_post_parent_id( $original_product_id ), 'product', false, $lang );
		$tr_parent_id = is_null( $tr_parent_id ) ? 0 : (int) $tr_parent_id;
		$args         = [];
		if ( wp_get_post_parent_id( $tr_product_id ) !== $tr_parent_id ) {
			$args['post_parent'] = $tr_parent_id;
		}
		// sync product date
		if ( ! empty( $this->woocommerce_wpml->settings['products_sync_date'] ) ) {
			$orig_product      = get_post( $original_product_id );
			$args['post_date'] = $orig_product->post_date;
		}
		if ( ! empty( $args ) ) {
			$this->wpdb->update(
				$this->wpdb->posts,
				$args,
				[ 'id' => $tr_product_id ]
			);
		}
	}

	public function set_schedule_for_translations( $deprecated, $post ) {

		if ( $this->woocommerce_wpml->products->is_original_product( $post->ID ) ) {
			$translations = $this->post_translations->get_element_translations( $post->ID, false, true );
			foreach ( $translations as $translation ) {
				wp_clear_scheduled_hook( 'publish_future_post', [ $translation ] );
				wp_schedule_single_event( strtotime( get_gmt_from_date( $post->post_date ) . ' GMT' ), 'publish_future_post', [ $translation ] );
			}
		}
	}

	/**
	 * @param int $tr_product_id
	 *
	 * @deprecated Use \WCML\Synchronization\Hooks::synchronizeProductTranslation
	 */
	public function icl_pro_translation_completed( $tr_product_id ) {}

	/**
	 * @param int    $master_post_id
	 * @param string $lang
	 * @param array  $postarr
	 * @param int    $id
	 *
	 * @deprecated Use \WCML\Synchronization\Hooks::synchronizeProductDuplication
	 */
	public function icl_make_duplicate( $master_post_id, $lang, $postarr, $id ) {}

	/**
	 * @param \WC_Product $product
	 *
	 * @deprecated Use Use \WCML\Synchronization\Hooks::synchronizeOnEditSave
	 */
	public function woocommerce_product_quick_edit_save( $product ) {}

	/**
	 * @param int $originalProductId
	 * @param int $translationId
	 *
	 * @since 5.4.2 Split from duplicate_product_post_meta to avoid the expensive condition on changed values.
	 *
	 * @deprecated Use \WCML\Synchronization\Component\DownloadableFiles::run
	 */
	public function sync_downloadable_files( $originalProductId, $translationId ) {
		global $iclTranslationManagement;
		$settingFactory       = new WPML_Custom_Field_Setting_Factory( $iclTranslationManagement );
		$postmetaFieldSetting = $settingFactory->post_meta_setting( '_downloadable_files' );
		$postmetaFieldStatus  = $postmetaFieldSetting->status();
		if ( WPML_IGNORE_CUSTOM_FIELD === $postmetaFieldStatus ) {
			return;
		}
		$this->woocommerce_wpml->downloadable->sync_files_to_translations( $originalProductId, $translationId );

		self::syncDeletedCustomFields( $originalProductId, $translationId );

		// Legacy from the duplicate_product_post_meta split, used by compatibility addons.
		// Keep it, declare the third parameter as legacy and not used
		// Port callbacks to wcml_after_sync_product_data if possible, I think they do!
		do_action( 'wcml_after_duplicate_product_post_meta', $originalProductId, $translationId, false );
	}

	/**
	 * Duplicate the postmeta of a product into one of its translaitons.
	 *
	 * The name here is missleading, since it does more than just duplicating.
	 * When provided with extra $data, it applies it as the new translation.
	 *
	 * Keeping the logic, and $data as optional for backward compatibility.
	 *
	 * @param int         $original_product_id
	 * @param int         $translated_product_id
	 * @param array|false $data
	 *
	 * @todo Deprecate and clone into the WCML_Editor_UI_Product_Job class.
	 */
	public function duplicate_product_post_meta( $original_product_id, $translated_product_id, $data = false ) {
		if ( ! $data ) {
			$this->sync_downloadable_files( $original_product_id, $translated_product_id );
			$wcml_data_store = wcml_product_data_store_cpt();
			$wcml_data_store->update_lookup_table_data( $translated_product_id );
			return;
		}

		global $iclTranslationManagement;
		$custom_fields    = get_post_custom( $original_product_id );
		$post_fields      = null;
		$settings_factory = new WPML_Custom_Field_Setting_Factory( $iclTranslationManagement );

		unset( $custom_fields['_thumbnail_id'] );
		unset( $custom_fields[ SyncHash::META_KEY ] );

		foreach ( $custom_fields as $key => $meta ) {
			$setting = $settings_factory->post_meta_setting( $key );

			if ( WPML_IGNORE_CUSTOM_FIELD === $setting->status() ) {
				continue;
			}

			if ( '_downloadable_files' === $key ) {
				$this->woocommerce_wpml->downloadable->sync_files_to_translations( $original_product_id, $translated_product_id, $data );
			} elseif ( WPML_TRANSLATE_CUSTOM_FIELD === $setting->status() ) {
				$post_fields = $this->sync_custom_field_value( $key, $data, $translated_product_id, $post_fields, $original_product_id );
			}
		}

		self::syncDeletedCustomFields( $original_product_id, $translated_product_id );

		$wcml_data_store = wcml_product_data_store_cpt();
		$wcml_data_store->update_lookup_table_data( $translated_product_id );

		do_action( 'wcml_after_duplicate_product_post_meta', $original_product_id, $translated_product_id, $data );
	}

	public function sync_custom_field_value( $custom_field, $translation_data, $trnsl_product_id, $post_fields, $original_product_id = false, $is_variation = false ) {

		if ( is_null( $post_fields ) ) {
			$post_fields = [];
			if ( isset( $_POST['data'] ) && ! is_array( $_POST['data'] ) ) {
				$job_data = [];
				parse_str( $_POST['data'], $job_data );
				$post_fields = $job_data['fields'];
			}
		}

		$custom_filed_key = $is_variation && $original_product_id ? $custom_field . $original_product_id : $custom_field;

		if ( isset( $translation_data[ md5( $custom_filed_key ) ] ) ) {
			$meta_value = $translation_data[ md5( $custom_filed_key ) ];
			$meta_value = apply_filters( 'wcml_meta_value_before_add', $meta_value, $custom_filed_key );
			update_post_meta( $trnsl_product_id, $custom_field, $meta_value );
			unset( $post_fields[ $custom_filed_key ] );
		} else {
			foreach ( $post_fields as $post_field_key => $post_field ) {

				if ( 1 === preg_match( '/field-' . $custom_field . '-.*?/', $post_field_key ) ) {
					delete_post_meta( $trnsl_product_id, $custom_field );

					$custom_fields = get_post_meta( $original_product_id, $custom_field );
					$single        = count( $custom_fields ) === 1;
					$custom_fields = $single ? $custom_fields[0] : $custom_fields;

					$filtered_custom_fields = array_filter( $custom_fields );
					$custom_fields_values   = array_values( $filtered_custom_fields );
					$custom_fields_keys     = array_keys( $filtered_custom_fields );

					foreach ( $custom_fields_values as $custom_field_index => $custom_field_value ) {
						$custom_fields_values =
							$this->get_translated_custom_field_values(
								$custom_fields_values,
								$translation_data,
								$custom_field,
								$custom_field_value,
								$custom_field_index
							);
					}

					$custom_fields_translated = $custom_fields;

					foreach ( $custom_fields_values as $index => $value ) {
						if ( ! $single ) {
							add_post_meta( $trnsl_product_id, $custom_field, $value, $single );
						} else {
							$custom_fields_translated[ $custom_fields_keys[ $index ] ] = $value;
						}
					}
					if ( $single ) {
						update_post_meta( $trnsl_product_id, $custom_field, $custom_fields_translated );
					}
				} else {
					$meta_value = $translation_data[ md5( $post_field_key ) ];
					$field_key  = explode( ':', $post_field_key );
					if ( $field_key[0] == $custom_filed_key ) {
						if ( 'new' === substr( $field_key[1], 0, 3 ) ) {
							add_post_meta( $trnsl_product_id, $custom_field, $meta_value );
						} else {
							update_meta( $field_key[1], $custom_field, $meta_value );
						}
						unset( $post_fields[ $post_field_key ] );
					}
				}
			}
		}

		return $post_fields;
	}

	public function get_translated_custom_field_values( $custom_fields_values, $translation_data, $custom_field, $custom_field_value, $custom_field_index ) {

		if ( is_scalar( $custom_field_value ) ) {
			$key_index            = $custom_field . '-' . $custom_field_index;
			$cf                   = 'field-' . $key_index;
			$meta_keys            = explode( '-', $custom_field_index );
			$meta_keys            = array_map( [ $this, 'replace_separator' ], $meta_keys );
			$custom_fields_values = $this->insert_under_keys(
				$meta_keys,
				$custom_fields_values,
				$translation_data[ md5( $cf ) ]
			);
		} else {
			foreach ( $custom_field_value as $ind => $value ) {
				$field_index          = $custom_field_index . '-' . str_replace( '-', self::CUSTOM_FIELD_KEY_SEPARATOR, $ind );
				$custom_fields_values = $this->get_translated_custom_field_values( $custom_fields_values, $translation_data, $custom_field, $value, $field_index );
			}
		}

		return $custom_fields_values;

	}

	private function replace_separator( $el ) {
		return str_replace( self::CUSTOM_FIELD_KEY_SEPARATOR, '-', $el );
	}

	/**
	 * Inserts an element into an array, nested by keys.
	 * Input ['a', 'b'] for the keys, an empty array for $array and $x for the value would lead to
	 * [ 'a' => ['b' => $x ] ] being returned.
	 *
	 * @param array $keys indexes ordered from highest to lowest level
	 * @param array $array array into which the value is to be inserted
	 * @param mixed $value to be inserted
	 *
	 * @return array
	 */
	private function insert_under_keys( $keys, $array, $value ) {
		$array[ $keys[0] ] = count( $keys ) === 1
			? $value
			: $this->insert_under_keys(
				array_slice( $keys, 1 ),
				( $array[ $keys[0] ] ?? [] ),
				$value
			);

		return $array;
	}

	/**
	 * @deprecated See \WCML\Synchronize\Hooks::synchronizeConnectedTranslations
	 */
	public function icl_connect_translations_action() {}

	/**
	 * @deprecated 5.4.2 Use the \WCML\Utilities\SyncHash utility instead.
	 */
	public function check_if_product_fields_sync_needed( $original_id, $trnsl_post_id, $fields_group ) {}

	public function sync_product_translations_visibility( $product_id ) {
		$translations = $this->post_translations->get_element_translations( $product_id, false, true );
		if ( $translations ) {

			$product = wc_get_product( $product_id );
			$terms   = [];

			if ( $this->woocommerce_wpml->products->is_original_product( $product_id ) ) {
				if ( $product->is_featured() ) {
					$terms[] = 'featured';
				}
			}

			if ( 'outofstock' === $product->get_stock_status() ) {
				$terms[] = 'outofstock';
			}

			$rating = min( 5, round( $product->get_average_rating() ) );

			if ( $rating > 0 ) {
				$terms[] = 'rated-' . $rating;
			}

			foreach ( $translations as $translation ) {
				if ( $product_id !== (int) $translation ) {
					wp_set_post_terms( $translation, $terms, 'product_visibility', false );
				}
			}
		}
	}

	/**
	 * @param int $originalId
	 * @param int $translationId
	 */
	public static function syncDeletedCustomFields( $originalId, $translationId ) {
		$settingsFactory = wpml_load_core_tm()->settings_factory();

		// $isCopiedField :: string -> bool
		$isCopiedField = function( $field ) use ( $settingsFactory ) {
			return WPML_COPY_CUSTOM_FIELD === $settingsFactory->post_meta_setting( $field )->status();
		};

		// $deleteFieldInTranslation :: string -> void
		$deleteFieldInTranslation = function( $field ) use ( $translationId ) {
			delete_post_meta( $translationId, $field );
		};

		$deletedInOriginal = wpml_collect( array_diff(
			array_keys( get_post_custom( $translationId ) ),
			array_keys( get_post_custom( $originalId ) )
		) );

		$deletedInOriginal
			->filter( $isCopiedField )
			->map( $deleteFieldInTranslation );
	}
}
