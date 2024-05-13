<?php

use WCML\Utilities\DB;
use WPML\FP\Obj;
use function WPML\Container\make;

class WCML_Troubleshooting {

	const ITEMS_PER_AJAX = 5;

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
		add_action( 'wp_ajax_register_reviews_in_st', [ $this, 'register_reviews_in_st' ] );
		add_action( 'wp_ajax_trbl_update_count', [ $this, 'trbl_update_count' ] );
		add_action( 'wp_ajax_trbl_sync_categories', [ $this, 'trbl_sync_categories' ] );
		add_action( 'wp_ajax_trbl_duplicate_terms', [ $this, 'trbl_duplicate_terms' ] );
		add_action( 'wp_ajax_trbl_fix_product_type_terms', [ $this, 'trbl_fix_product_type_terms' ] );
		add_action( 'wp_ajax_trbl_sync_stock', [ $this, 'trbl_sync_stock' ] );
		add_action( 'wp_ajax_fix_translated_variations_relationships', [ $this, 'fix_translated_variations_relationships' ] );
		add_action( 'wp_ajax_sync_deleted_meta', [ $this, 'sync_deleted_meta' ] );
	}

	public function wcml_count_products_with_variations() {
		return count( get_option( 'wcml_products_to_sync' ) );
	}

	public function trbl_update_count() {
		self::checkNonce( 'trbl_update_count' );

		$this->wcml_sync_variations_update_option();

		$result = [
			'count' => $this->wcml_count_products_with_variations(),
		];

		wp_send_json_success( $result );
	}

	public function wcml_sync_variations_update_option() {

		$get_variation_term_taxonomy_ids = $this->wpdb->get_var( "SELECT tt.term_taxonomy_id FROM {$this->wpdb->terms} AS t LEFT JOIN {$this->wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable'" );
		$get_variation_term_taxonomy_ids = apply_filters( 'wcml_variation_term_taxonomy_ids', (array) $get_variation_term_taxonomy_ids );

		$get_variables_products = $this->wpdb->get_results(
			"SELECT tr.element_id as id,tr.language_code as lang FROM {$this->wpdb->prefix}icl_translations AS tr LEFT JOIN {$this->wpdb->term_relationships} as t ON tr.element_id = t.object_id LEFT JOIN {$this->wpdb->posts} AS p ON tr.element_id = p.ID
				WHERE p.post_status = 'publish' AND tr.source_language_code is NULL AND tr.element_type = 'post_product' AND t.term_taxonomy_id IN (" . DB::prepareIn( $get_variation_term_taxonomy_ids, '%d' ) . ") ORDER BY tr.element_id",
			ARRAY_A
		);

		update_option( 'wcml_products_to_sync', $get_variables_products );
	}

	public function wcml_count_products() {

		$get_products_count = $this->wpdb->get_var( "SELECT count(ID) FROM {$this->wpdb->posts} AS p LEFT JOIN {$this->wpdb->prefix}icl_translations AS tr ON tr.element_id = p.ID WHERE p.post_status = 'publish' AND p.post_type =  'product' AND tr.source_language_code is NULL" );
		return $get_products_count;
	}

	public function wcml_count_products_for_gallery_sync() {
		$all_products = $this->get_products_needs_gallery_sync( false );

		return count( $all_products );
	}

	public function wcml_count_product_categories() {

		$get_product_categories = $this->get_product_categories_needs_sync();

		return count( $get_product_categories );
	}


	public function trbl_sync_variations() {
		self::checkNonce( 'trbl_sync_variations' );

		$get_variables_products = get_option( 'wcml_products_to_sync' );
		$all_active_lang        = $this->sitepress->get_active_languages();
		$unset_keys             = [];
		$products_for_one_ajax  = array_slice( $get_variables_products, 0, 3, true );

		foreach ( $products_for_one_ajax as $key => $product ) {
			foreach ( $all_active_lang as $language ) {
				if ( $language['code'] != $product['lang'] ) {
					$tr_product_id = apply_filters( 'translate_object_id', $product['id'], 'product', false, $language['code'] );

					if ( ! is_null( $tr_product_id ) ) {
						$this->woocommerce_wpml->sync_variations_data->sync_product_variations( $product['id'], $tr_product_id, $language['code'], [ 'is_troubleshooting' => true ] );
					}
					if ( ! in_array( $key, $unset_keys ) ) {
						$unset_keys[] = $key;
					}
				}
			}
		}

		foreach ( $unset_keys as $unset_key ) {
			unset( $get_variables_products[ $unset_key ] );
		}

		update_option( 'wcml_products_to_sync', $get_variables_products );

		$wcml_settings = get_option( '_wcml_settings' );
		if ( isset( $wcml_settings['notifications'] ) && isset( $wcml_settings['notifications']['varimages'] ) ) {
			$wcml_settings['notifications']['varimages']['show'] = 0;
			update_option( '_wcml_settings', $wcml_settings );
		}

		wp_send_json_success();
	}

	public function trbl_gallery_images() {
		self::checkNonce( 'trbl_gallery_images' );

		$all_products = $this->get_products_needs_gallery_sync( true );

		foreach ( $all_products as $product ) {
			$this->woocommerce_wpml->media->sync_product_gallery( $product->ID );
			add_post_meta( $product->ID, 'gallery_sync', true );
		}

		wp_send_json_success();

	}
	
	public function register_reviews_in_st() {
		self::checkNonce( 'register_reviews_in_st' );

		make( \WCML\Reviews\Translations\Mapper::class )->registerMissingReviewStrings();
		
		wp_send_json_success();
	}

	public function get_products_needs_gallery_sync( $limit = false ) {

		$sql = "SELECT p.ID FROM {$this->wpdb->posts} AS p
                 LEFT JOIN {$this->wpdb->prefix}icl_translations AS tr
                 ON tr.element_id = p.ID
                 WHERE p.post_status = 'publish' AND p.post_type = 'product' AND tr.source_language_code is NULL
                 AND ( SELECT COUNT( pm.meta_key ) FROM {$this->wpdb->postmeta} AS pm WHERE pm.post_id = p.ID AND pm.meta_key = 'gallery_sync' ) = 0 ";

		if ( $limit ) {
			$sql .= 'ORDER BY p.ID LIMIT ' . self::ITEMS_PER_AJAX;
		}

		$all_products = $this->wpdb->get_results( $sql );

		return $all_products;
	}

	public function trbl_sync_categories() {
		self::checkNonce( 'trbl_sync_categories' );

		$all_categories = $this->get_product_categories_needs_sync( true );

		foreach ( $all_categories as $category ) {
			add_option( 'wcml_sync_category_' . $category->term_taxonomy_id, true );
			$trid         = $this->sitepress->get_element_trid( $category->term_taxonomy_id, 'tax_product_cat' );
			$translations = $this->sitepress->get_element_translations( $trid, 'tax_product_cat' );
			$type         = get_term_meta( $category->term_id, 'display_type', true );
			$thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
			foreach ( $translations as $translation ) {
				if ( $translation->language_code != $category->language_code ) {
					update_term_meta( $translation->term_id, 'display_type', $type );
					update_term_meta( $translation->term_id, 'thumbnail_id', apply_filters( 'translate_object_id', $thumbnail_id, 'attachment', true, $translation->language_code ) );
				}
			}
		}

		wp_send_json_success();

	}


	public function get_product_categories_needs_sync( $limit = false ) {

		$sql = "SELECT t.term_taxonomy_id,t.term_id,tr.language_code FROM {$this->wpdb->term_taxonomy} AS t
                 LEFT JOIN {$this->wpdb->prefix}icl_translations AS tr
                 ON tr.element_id = t.term_taxonomy_id
                 WHERE t.taxonomy = 'product_cat' AND tr.element_type = 'tax_product_cat' AND tr.source_language_code is NULL
                 AND ( SELECT COUNT( option_id ) FROM {$this->wpdb->options} WHERE option_name = CONCAT( 'wcml_sync_category_',t.term_taxonomy_id ) ) = 0 ";

		if ( $limit ) {
			$sql .= 'ORDER BY t.term_taxonomy_id LIMIT ' . self::ITEMS_PER_AJAX;
		}

		$all_categories = $this->wpdb->get_results( $sql );

		return $all_categories;
	}


	public function trbl_duplicate_terms() {
		self::checkNonce( 'trbl_duplicate_terms' );

		$attr = isset( $_POST['attr'] ) ? $_POST['attr'] : false;

		if ( $attr ) {
			$terms     = get_terms( $attr, 'hide_empty=0' );
			$i         = 0;
			$languages = $this->sitepress->get_active_languages();
			foreach ( $terms as $term ) {
				foreach ( $languages as $language ) {
					$tr_id = apply_filters( 'translate_object_id', $term->term_id, $attr, false, $language['code'] );

					if ( is_null( $tr_id ) ) {
						$term_args = [];
						// hierarchy - parents.
						if ( is_taxonomy_hierarchical( $attr ) ) {
							// fix hierarchy.
							if ( $term->parent ) {
								$original_parent_translated = apply_filters( 'translate_object_id', $term->parent, $attr, false, $language['code'] );
								if ( $original_parent_translated ) {
									$term_args['parent'] = $original_parent_translated;
								}
							}
						}

						$term_name         = $term->name;
						$slug              = $term->name . '-' . $language['code'];
						$slug              = WPML_Terms_Translations::term_unique_slug( $slug, $attr, $language['code'] );
						$term_args['slug'] = $slug;

						$new_term = wp_insert_term( $term_name, $attr, $term_args );
						if ( $new_term && ! is_wp_error( $new_term ) ) {
							$tt_id = $this->sitepress->get_element_trid( $term->term_taxonomy_id, 'tax_' . $attr );
							$this->sitepress->set_element_language_details( $new_term['term_taxonomy_id'], 'tax_' . $attr, $tt_id, $language['code'] );
						}
					}
				}
			}
		}

		wp_send_json_success();
	}

	public function trbl_fix_product_type_terms() {
		self::checkNonce( 'trbl_product_type_terms' );

		WCML_Install::check_product_type_terms();

		wp_send_json_success();
	}

	public function wcml_count_products_and_variations() {

		$results = $this->get_original_products_and_variations();

		return count( $results );
	}

	public function trbl_sync_stock() {
		self::checkNonce( 'trbl_sync_stock' );

		$results = $this->get_original_products_and_variations();

		foreach ( $results as $product ) {

			if ( get_post_meta( $product->ID, '_manage_stock', true ) === 'yes' ) {

				$translations = $this->sitepress->get_element_translations( $product->trid, $product->element_type );

				$min_stock    = false;
				$stock_status = 'instock';

				// collect min stock.
				foreach ( $translations as $translation ) {
					$stock = get_post_meta( $translation->element_id, '_stock', true );
					if ( ! $min_stock || $stock < $min_stock ) {
						$min_stock    = $stock;
						$stock_status = get_post_meta( $translation->element_id, '_stock_status', true );
					}
				}

				// update stock value.
				foreach ( $translations as $translation ) {
					update_post_meta( $translation->element_id, '_stock', $min_stock );
					update_post_meta( $translation->element_id, '_stock_status', $stock_status );
				}
			}
		}

		wp_send_json_success();
	}

	public function get_original_products_and_variations() {

		$results = $this->wpdb->get_results(
			"
                        SELECT p.ID, t.trid, t.element_type
                        FROM {$this->wpdb->posts} p
                        JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
                        WHERE p.post_type in ('product', 'product_variation') AND t.source_language_code IS NULL
                    "
		);

		return $results;
	}

	public function fix_translated_variations_relationships() {
		self::checkNonce( 'fix_relationships' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access error' );
		}

		$translated_variations = get_option( 'wcml_trbl_translated_variations' );

		if ( ! $translated_variations ) {
			$translated_variations = $this->get_products_variations_needs_fix_relationships();
		}

		foreach ( array_slice( $translated_variations, 0, self::ITEMS_PER_AJAX, true ) as $key => $translated_variation ) {
			// check relationships.
			$tr_info_for_original_variation = $this->get_translation_info_for_element( $translated_variation->meta_value, 'post_product_variation' );

			$language = $this->sitepress->get_language_for_element( wp_get_post_parent_id( $translated_variation->meta_value ), 'post_product' );

			if ( ! $language ) {
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

			unset( $translated_variations[ $key ] );
		}

		update_option( 'wcml_trbl_translated_variations', $translated_variations );

		wp_send_json_success();
	}

	/**
	 * @param int    $element_id
	 * @param string $element_type
	 *
	 * @return object|null
	 */
	private function get_translation_info_for_element( $element_id, $element_type ) {
		return $this->wpdb->get_row( $this->wpdb->prepare( "SELECT trid, translation_id FROM {$this->wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = %s", $element_id, $element_type ) );
	}

	private function get_products_variations_needs_fix_relationships() {
		return $this->wpdb->get_results( "SELECT post_id, meta_value FROM {$this->wpdb->postmeta} WHERE meta_key ='_wcml_duplicate_of_variation'" );
	}

	public function wcml_count_product_fix_relationships() {

		$results = $this->get_products_variations_needs_fix_relationships();

		return count( $results );
	}
	
	public function wcml_count_unregistered_reviews() {
		return make( \WCML\Reviews\Translations\Mapper::class )->countMissingReviewStrings();
	}


	public function sync_deleted_meta() {
		self::checkNonce( 'sync_deleted_meta' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access error' );
		}

		$products_needs_fix_postmeta = get_option( 'wcml_trbl_products_needs_fix_postmeta' );

		if ( ! $products_needs_fix_postmeta ) {
			$products_needs_fix_postmeta = $this->get_original_products_and_variations();
		}

		$iclTranslationManagement = wpml_load_core_tm();
		$settings_factory         = new WPML_Custom_Field_Setting_Factory( $iclTranslationManagement );

		foreach ( array_slice( $products_needs_fix_postmeta, 0, self::ITEMS_PER_AJAX, true ) as $key => $product ) {

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

			unset( $products_needs_fix_postmeta[ $key ] );
		}

		update_option( 'wcml_trbl_products_needs_fix_postmeta', $products_needs_fix_postmeta );

		wp_send_json_success();
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
