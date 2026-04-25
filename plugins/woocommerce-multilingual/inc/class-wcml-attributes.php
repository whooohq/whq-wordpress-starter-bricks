<?php

use WCML\Utilities\AdminPages;
use WPML\FP\Obj;
use WCML\Utilities\WCTaxonomies;
use WPML\FP\Str;

class WCML_Attributes {

	const PRIORITY_AFTER_WC_INIT   = 100;
	const CACHE_GROUP_VARIATION    = 'wpml-all-meta-product-variation';
	/** @deprecated use WCML_WC_Strings::TAXONOMY_GENERAL_VALUE_PREFIX */
	const PRODUCT_ATTRIBUTE_PREFIX = WCML_WC_Strings::TAXONOMY_GENERAL_VALUE_PREFIX;

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var SitePress */
	private $sitepress;
	/** @var WPML_Post_Translation */
	private $post_translations;
	/** @var WPML_Term_Translation */
	private $term_translations;
	/** @var wpdb */
	private $wpdb;

	/**
	 * WCML_Attributes constructor.
	 *
	 * @param woocommerce_wpml      $woocommerce_wpml
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translations
	 * @param WPML_Term_Translation $term_translations
	 * @param wpdb                  $wpdb
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, \WPML\Core\ISitePress $sitepress, WPML_Post_Translation $post_translations, WPML_Term_Translation $term_translations, wpdb $wpdb ) {
		$this->woocommerce_wpml  = $woocommerce_wpml;
		$this->sitepress         = $sitepress;
		$this->post_translations = $post_translations;
		$this->term_translations = $term_translations;
		$this->wpdb              = $wpdb;
		wp_cache_add_non_persistent_groups( self::CACHE_GROUP_VARIATION );
	}

	private function get_wcml_terms_instance() {
		return $this->woocommerce_wpml->terms;
	}

	private function get_wcml_products_instance() {
		return $this->woocommerce_wpml->products;
	}

	public function add_hooks() {

		add_action( 'init', [ $this, 'init' ] );

		add_filter(
			'wpml_tm_job_field_is_translatable',
			[
				$this,
				'set_custom_product_attributes_as_translatable_for_tm_job',
			],
			10,
			2
		);
		add_filter(
			'woocommerce_dropdown_variation_attribute_options_args',
			[
				$this,
				'filter_dropdown_variation_attribute_options_args',
			]
		);

		$this->wpml_ajax_custom_tax_sync_options();

		add_filter(
			'woocommerce_product_get_attributes',
			[
				$this,
				'filter_adding_to_cart_product_attributes_names',
			]
		);

		if ( $this->get_wcml_products_instance()->is_product_display_as_translated_post_type() ) {
			add_filter(
				'woocommerce_available_variation',
				[
					$this,
					'filter_available_variation_attribute_values_in_current_language',
				]
			);
			add_filter(
				'get_post_metadata',
				[
					$this,
					'filter_product_variation_post_meta_attribute_values_in_current_language',
				],
				10,
				4
			);
			add_action( 'added_post_meta', [ $this, 'invalidateVariationMetaCache' ], 10, 3 );
			add_filter(
				'woocommerce_product_get_default_attributes',
				[
					$this,
					'filter_product_variation_default_attributes',
				]
			);
		}
		add_action( 'update_post_meta', [ $this, 'set_translation_status_as_needs_update' ], 10, 3 );
		add_action( 'wc_ajax_get_variation', [ $this, 'maybe_filter_get_variation' ], 9 );

		add_filter( 'wpml_taxonomy_strings_source_language', [ $this, 'wcml_taxonomy_strings_source_language' ], self::PRIORITY_AFTER_WC_INIT, 3 );
		add_filter( 'wpml_st_register_slug_set_source_language', [ $this, 'wcml_taxonomy_slug_set_source_language' ], self::PRIORITY_AFTER_WC_INIT, 3 );
	}

	public function init() {
		$is_attr_page = apply_filters( 'wcml_is_attributes_page', AdminPages::isWcProductAttributesPage() );

		// phpcs:enable
		if ( $is_attr_page ) {
			add_action( 'admin_init', [ $this, 'not_translatable_html' ] );
			add_action(
				'woocommerce_attribute_added',
				[
					$this,
					'set_attribute_readonly_config_form_action',
				],
				self::PRIORITY_AFTER_WC_INIT,
				2
			);
			add_action(
				'woocommerce_attribute_updated',
				[
					$this,
					'set_attribute_readonly_config_form_action',
				],
				self::PRIORITY_AFTER_WC_INIT,
				3
			);
		}

		if ( wpml_is_rest_request() ) {
			add_action( 'woocommerce_attribute_added', [ $this, 'set_attribute_readonly_config_rest_action' ], self::PRIORITY_AFTER_WC_INIT, 3 );
			add_action( 'woocommerce_attribute_updated', [ $this, 'set_attribute_readonly_config_rest_action' ], self::PRIORITY_AFTER_WC_INIT, 3 );
		}
	}

	public function not_translatable_html() {
		$attr_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : false;

		$attr_is_tnaslt = new WCML_Not_Translatable_Attributes( $attr_id, $this->woocommerce_wpml );
		$attr_is_tnaslt->show();
	}

	public function get_attribute_terms( $attribute ) {

		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
                        SELECT * FROM {$this->wpdb->term_taxonomy} x JOIN {$this->wpdb->terms} t ON x.term_id = t.term_id WHERE x.taxonomy = %s",
				$attribute
			)
		);

	}

	/**
	 * REST - Attribute updated.
	 *
	 * @param int          $id        Added attribute ID.
	 * @param array        $attribute Attribute data.
	 * @param string|false $old_slug  Attribute old name - false when create new.
	 */
	public function set_attribute_readonly_config_rest_action( $id, $attribute, $old_slug = false ) {
		$attribute['wcml-is-translatable-attr'] = 1;

		$this->set_attribute_readonly_config( $id, $attribute, $old_slug );
	}

	/**
	 * WEB FORM - Attribute updated.
	 *
	 * @param int          $id        Added attribute ID.
	 * @param array        $attribute Attribute data.
	 * @param string|false $old_slug  Attribute old name - false when create new.
	 */
	public function set_attribute_readonly_config_form_action( $id, $attribute, $old_slug = false ) {
		$attribute['wcml-is-translatable-attr'] = isset( $_POST['wcml-is-translatable-attr'] ) ? 1 : 0;

		if ( isset( $_POST['save_attribute'] ) || isset( $_POST['add_new_attribute'] ) ) {
			$this->set_attribute_readonly_config( $id, $attribute, $old_slug );
		}
	}

	/**
	 * @param int          $id        Added attribute ID.
	 * @param array        $attribute Attribute data.
	 * @param string|false $old_slug  Attribute old name - false when create new.
	 */
	public function set_attribute_readonly_config( $id, $attribute, $old_slug = false ) {

		$is_translatable = (int) $attribute['wcml-is-translatable-attr'];

		if ( $attribute['attribute_name'] ) {
			$attribute_name = wc_sanitize_taxonomy_name( wp_unslash( $attribute['attribute_name'] ) );
		} else {
			$attribute_name = wc_sanitize_taxonomy_name( wc_clean( wp_unslash( $attribute['attribute_label'] ) ) );
		}

		$attribute_name = wc_attribute_taxonomy_name( $attribute_name );

		if ( 0 === $is_translatable ) {
			// delete all translated attributes terms if "Translatable?" option un-checked.
			$this->delete_translated_attribute_terms( $attribute_name );
			$this->set_variations_to_use_original_attributes( $attribute_name );
			$this->set_original_attributes_for_products( $attribute_name );
		}

		if ( $old_slug !== $attribute['attribute_name'] ) {
			$this->fix_attribute_slug_in_translations_table( wc_attribute_taxonomy_name( $old_slug ), $attribute_name );
		}

		$this->set_attribute_config_in_settings( $attribute_name, $is_translatable );
	}

	/**
	 * @param string $old_attribute_name
	 * @param string $new_attribute_name
	 */
	private function fix_attribute_slug_in_translations_table( $old_attribute_name, $new_attribute_name ) {
		$this->wpdb->update(
			$this->wpdb->prefix . 'icl_translations',
			[ 'element_type' => 'tax_' . $new_attribute_name ],
			[ 'element_type' => 'tax_' . $old_attribute_name ]
		);
	}

	public function set_attribute_config_in_settings( $attribute_name, $is_translatable ) {
		$this->set_attribute_config_in_wcml_settings( $attribute_name, $is_translatable );
		$this->set_attribute_config_in_wpml_settings( $attribute_name, $is_translatable );

		$this->get_wcml_terms_instance()->update_terms_translated_status( $attribute_name );
	}

	public function set_attribute_config_in_wcml_settings( $attribute_name, $is_translatable ) {
		$wcml_settings = $this->woocommerce_wpml->get_settings();
		$wcml_settings['attributes_settings'][ $attribute_name ] = $is_translatable;
		$this->woocommerce_wpml->update_settings( $wcml_settings );
	}

	public function set_attribute_config_in_wpml_settings( $attribute_name, $is_translatable ) {

		$sync_settings                    = $this->sitepress->get_setting( 'taxonomies_sync_option', [] );
		$sync_settings[ $attribute_name ] = $is_translatable;
		$this->sitepress->set_setting( 'taxonomies_sync_option', $sync_settings, true );
		$this->sitepress->verify_taxonomy_translations( $attribute_name );
	}

	public function delete_translated_attribute_terms( $attribute ) {
		$terms = $this->get_attribute_terms( $attribute );

		foreach ( $terms as $term ) {
			if ( $this->term_translations->get_source_lang_code( $term->term_id ) ) {
				wp_delete_term( $term->term_id, $attribute );
			}
		}

	}

	public function set_variations_to_use_original_attributes( $attribute ) {
		$terms = $this->get_attribute_terms( $attribute );

		foreach ( $terms as $term ) {
			if ( is_null( $this->term_translations->get_source_lang_code( $term->term_id ) ) ) {
				$variations = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key=%s AND meta_value = %s", 'attribute_' . $attribute, $term->slug ) );

				foreach ( $variations as $variation ) {
					// update taxonomy in translation of variation.
					foreach ( $this->sitepress->get_active_languages() as $language ) {

						$trnsl_variation_id = apply_filters( 'wpml_object_id', $variation->post_id, 'product_variation', false, $language['code'] );
						if ( ! is_null( $trnsl_variation_id ) ) {
							update_post_meta( $trnsl_variation_id, 'attribute_' . $attribute, $term->slug );
						}
					}
				}
			}
		}
	}

	public function set_original_attributes_for_products( $attribute ) {

		$terms            = $this->get_attribute_terms( $attribute );
		foreach ( $terms as $term ) {
			if ( is_null( $this->term_translations->get_source_lang_code( $term->term_id ) ) ) {
				$args = [
					'tax_query' => [
						[
							'taxonomy' => $attribute,
							'field'    => 'slug',
							'terms'    => $term->slug,
						],
					],
				];

				$products = get_posts( $args );

				foreach ( $products as $product ) {

					foreach ( $this->sitepress->get_active_languages() as $language ) {

						$trnsl_product_id = apply_filters( 'wpml_object_id', $product->ID, 'product', false, $language['code'] );

						if ( ! is_null( $trnsl_product_id ) ) {
							if ( ! in_array( $trnsl_product_id, $trnsl_product_id ) ) {
								wp_delete_object_term_relationships( $trnsl_product_id, $attribute );
							}
							wp_set_object_terms( $trnsl_product_id, $term->slug, $attribute, true );
						}
					}
				}
			}
		}
	}


	public function is_translatable_attribute( $attr_name ) {

		if ( ! isset( $this->woocommerce_wpml->settings['attributes_settings'][ $attr_name ] ) ) {
			$this->set_attribute_config_in_settings( $attr_name, 1 );
		}

		return $this->woocommerce_wpml->settings['attributes_settings'][ $attr_name ] ?? 1;
	}

	public function get_translatable_attributes() {
		$attributes = wc_get_attribute_taxonomies();

		$translatable_attributes = [];
		foreach ( $attributes as $attribute ) {
			if ( $this->is_translatable_attribute( wc_attribute_taxonomy_name( $attribute->attribute_name ) ) ) {
				$translatable_attributes[] = $attribute;
			}
		}

		return $translatable_attributes;
	}

	public function set_translatable_attributes( $attributes ) {

		foreach ( $attributes as $name => $is_translatable ) {

			$attribute_name = wc_attribute_taxonomy_name( $name );
			$this->set_attribute_config_in_settings( $attribute_name, $is_translatable );

		}
	}

	/**
	 * @param int          $original_product_id
	 * @param int          $tr_product_id
	 * @param string|false $language
	 * @param array|false  $data
	 *
	 * @todo Deprecate and clone into the WCML_Editor_UI_Product_Job class.
	 */
	public function sync_product_attr( $original_product_id, $tr_product_id, $language = false, $data = false ) {
		$orig_product_attrs  = $this->get_product_attributes( $original_product_id );
		$translated_labels   = $data ? $this->get_attr_label_translations( $tr_product_id ) : [];
		$trnsl_product_attrs = $data ? [] : $this->get_product_attributes( $tr_product_id );

		$update_translated_labels = false;

		foreach ( $orig_product_attrs as $key => $orig_product_attr ) {
			$key_to_save   = $key;
			$sanitized_key = $this->filter_attribute_name( $orig_product_attr['name'], $original_product_id, true );

			if ( $sanitized_key !== $key ) {
				$orig_product_attrs_buff = $orig_product_attrs[ $key ];
				unset( $orig_product_attrs[ $key ] );
				$orig_product_attrs[ $sanitized_key ] = $orig_product_attrs_buff;
				$key_to_save                          = $sanitized_key;
			}

			if ( $data ) {
				$translated_attribute_in_data = Obj::prop( md5( $key ), $data );
				if (
					null !== $translated_attribute_in_data &&
					! is_array( $translated_attribute_in_data ) &&
					Obj::pathOr( null, [ $key_to_save, 'value' ], $orig_product_attrs ) !== $translated_attribute_in_data
				) {
					$orig_product_attrs[ $key_to_save ]['value'] = $translated_attribute_in_data;
				}

				// get translation values from $data.
				$translated_labels_in_data = Obj::prop( md5( $key . '_name' ), $data );
				if (
					$language &&
					null !== $translated_labels_in_data &&
					! is_array( $translated_labels_in_data ) &&
					Obj::pathOr( null, [ $language, $key_to_save ], $translated_labels ) !== stripslashes( $translated_labels_in_data )
				) {
					$update_translated_labels                       = true;
					$translated_labels[ $language ][ $key_to_save ] = stripslashes( $translated_labels_in_data );
				}
			} elseif ( ! $orig_product_attr['is_taxonomy'] ) {
				$duplicate_of = get_post_meta( $tr_product_id, '_icl_lang_duplicate_of', true );
				if ( ! $duplicate_of ) {
					if ( isset( $trnsl_product_attrs[ $key ] ) ) {
						$orig_product_attrs[ $key_to_save ]['value'] = $trnsl_product_attrs[ $key ]['value'];
					} elseif ( ! empty( $trnsl_product_attrs ) ) {
						unset( $orig_product_attrs[ $key_to_save ] );
					}
				}
			}
		}

		if ( $update_translated_labels ) {
			update_post_meta( $tr_product_id, 'attr_label_translations', $translated_labels );
		}
		update_post_meta( $tr_product_id, '_product_attributes', $orig_product_attrs );
	}

	public function getAttributeNameToSave( $attributeKey, $attributeData, $productId ) {
		$sanitizedKey = $this->filter_attribute_name( $attributeData['name'], $productId, true );
		if ( $sanitizedKey === $attributeKey ) {
			return $attributeKey;
		}
		return $sanitizedKey;
	}

	public function get_product_attributes( $product_id ) {
		$attributes = get_post_meta( $product_id, '_product_attributes', true );
		if ( ! is_array( $attributes ) ) {
			$attributes = [];
		}

		return $attributes;
	}

	public function get_attr_label_translations( $product_id, $lang = false ) {
		$trnsl_labels = get_post_meta( $product_id, 'attr_label_translations', true );

		$remove_empty_values = function ( $values ) {
			return \wpml_collect( $values )->filter()->toArray();
		};

		if ( ! $lang && is_array( $trnsl_labels ) ) {
			return \wpml_collect( $trnsl_labels )
				->map( $remove_empty_values )
				->toArray();
		}

		if ( isset( $trnsl_labels[ $lang ] ) ) {
			return $remove_empty_values( $trnsl_labels[ $lang ] );
		}

		return [];
	}

	/**
	 * @param int    $orig_post_id
	 * @param int    $transl_post_id
	 * @param string $lang
	 *
	 * @todo Deprecate and clone into the WCML_Editor_UI_Product_Job class.
	 */
	public function sync_default_product_attr( $orig_post_id, $transl_post_id, $lang ) {
		$original_default_attributes = get_post_meta( $orig_post_id, '_default_attributes', true );

		if ( ! empty( $original_default_attributes ) ) {
			$unserialized_default_attributes = [];
			foreach ( maybe_unserialize( $original_default_attributes ) as $attribute => $default_term_slug ) {
				// get the correct language.
				if ( WCTaxonomies::isProductAttribute( $attribute ) ) {
					// attr is taxonomy.
					if ( $this->is_translatable_attribute( $attribute ) ) {
						$sanitized_attribute_name = wc_sanitize_taxonomy_name( $attribute );
						$default_term_id          = $this->get_wcml_terms_instance()->wcml_get_term_id_by_slug( $sanitized_attribute_name, $default_term_slug );
						$tr_id                    = apply_filters( 'wpml_object_id', $default_term_id, $sanitized_attribute_name, false, $lang );

						if ( $tr_id ) {
							$translated_term                               = $this->get_wcml_terms_instance()->wcml_get_term_by_id( $tr_id, $sanitized_attribute_name );
							$unserialized_default_attributes[ $attribute ] = $translated_term->slug;
						}
					} else {
						$unserialized_default_attributes[ $attribute ] = $default_term_slug;
					}
				} else {
					// custom attr.
					$orig_product_attributes              = get_post_meta( $orig_post_id, '_product_attributes', true );
					$unserialized_orig_product_attributes = maybe_unserialize( $orig_product_attributes );

					if ( isset( $unserialized_orig_product_attributes[ $attribute ] ) ) {
						$orig_attr_values = explode( '|', $unserialized_orig_product_attributes[ $attribute ]['value'] );
						$orig_attr_values = array_map( 'trim', $orig_attr_values );

						foreach ( $orig_attr_values as $key => $orig_attr_value ) {
							$orig_attr_value_sanitized = strtolower( sanitize_title( $orig_attr_value ) );

							if ( $orig_attr_value_sanitized == $default_term_slug || trim( $orig_attr_value ) == trim( $default_term_slug ) ) {
								$tnsl_product_attributes              = get_post_meta( $transl_post_id, '_product_attributes', true );
								$unserialized_tnsl_product_attributes = maybe_unserialize( $tnsl_product_attributes );

								if ( isset( $unserialized_tnsl_product_attributes[ $attribute ] ) ) {
									$trnsl_attr_values = explode( '|', $unserialized_tnsl_product_attributes[ $attribute ]['value'] );

									if ( $orig_attr_value_sanitized == $default_term_slug ) {
										$trnsl_attr_value = strtolower( sanitize_title( trim( $trnsl_attr_values[ $key ] ) ) );
									} else {
										$trnsl_attr_value = trim( $trnsl_attr_values[ $key ] );
									}
									$unserialized_default_attributes[ $attribute ] = $trnsl_attr_value;
								}
							}
						}
					}
				}
			}

			$data = [ 'meta_value' => maybe_serialize( $unserialized_default_attributes ) ];
		} else {
			$data = [ 'meta_value' => maybe_serialize( [] ) ];
		}

		$where = [
			'post_id'  => $transl_post_id,
			'meta_key' => '_default_attributes',
		];

		$translated_product_meta = get_post_meta( $transl_post_id );
		if ( isset( $translated_product_meta['_default_attributes'] ) ) {
			$this->wpdb->update( $this->wpdb->postmeta, $data, $where );
		} else {
			$this->wpdb->insert( $this->wpdb->postmeta, array_merge( $data, $where ) );
		}

	}

	/*
	 * get attribute translation
	 */
	public function get_custom_attribute_translation( $product_id, $attribute_key, $attribute, $lang_code ) {
		$tr_post_id = apply_filters( 'wpml_object_id', $product_id, 'product', false, $lang_code );
		$transl     = [];
		if ( $tr_post_id ) {
			if ( ! $attribute['is_taxonomy'] ) {
				$tr_attrs = get_post_meta( $tr_post_id, '_product_attributes', true );
				if ( $tr_attrs ) {
					foreach ( $tr_attrs as $key => $tr_attr ) {
						if ( $attribute_key == $key ) {
							$transl['value'] = $tr_attr['value'];
							$trnsl_labels    = $this->get_attr_label_translations( $tr_post_id );

							if ( isset( $trnsl_labels[ $lang_code ][ $attribute_key ] ) ) {
								$transl['name'] = $trnsl_labels[ $lang_code ][ $attribute_key ];
							} else {
								$transl['name'] = $tr_attr['name'];
							}

							return $transl;
						}
					}
				}

				return false;
			}
		}

		return false;
	}

	/*
	* Get custom attribute translation
	* Returned translated attribute or original if missed
	*/
	public function get_custom_attr_translation( $product_id, $tr_product_id, $taxonomy, $attribute ) {
		$orig_product_attributes              = get_post_meta( $product_id, '_product_attributes', true );
		$unserialized_orig_product_attributes = maybe_unserialize( $orig_product_attributes );

		foreach ( $unserialized_orig_product_attributes as $orig_attr_key => $orig_product_attribute ) {
			$orig_attr_key = urldecode( $orig_attr_key );
			if ( strtolower( $taxonomy ) == $orig_attr_key ) {
				$values = explode( '|', $orig_product_attribute['value'] );

				foreach ( $values as $key_id => $value ) {
					if ( trim( $value, ' ' ) == $attribute ) {
						$attr_key_id = $key_id;
					}
				}
			}
		}

		$trnsl_product_attributes              = get_post_meta( $tr_product_id, '_product_attributes', true );
		$unserialized_trnsl_product_attributes = maybe_unserialize( $trnsl_product_attributes );
		$taxonomy                              = sanitize_title( $taxonomy );
		$trnsl_attr_values                     = explode( '|', $unserialized_trnsl_product_attributes[ $taxonomy ]['value'] );

		if ( isset( $attr_key_id ) && isset( $trnsl_attr_values[ $attr_key_id ] ) ) {
			return trim( $trnsl_attr_values[ $attr_key_id ] );
		}

		return $attribute;
	}

	/**
	 * @param int|bool $translatable
	 * @param array $job_translate
	 *
	 * @return bool|int
	 */
	public function set_custom_product_attributes_as_translatable_for_tm_job( $translatable, $job_translate ) {

		if ( 'wc_attribute' === substr( $job_translate['field_type'], 0, 12 ) ) {
			return true;
		}

		return $translatable;
	}

	public function is_attributes_fully_translated() {

		$product_attributes = $this->get_translatable_attributes();

		$fully_translated = true;

		if ( $product_attributes ) {
			foreach ( $product_attributes as $attribute ) {
				$is_fully_translated = $this->get_wcml_terms_instance()->is_fully_translated( WCTaxonomies::TAXONOMY_PREFIX_ATTRIBUTE . $attribute->attribute_name );
				if ( ! $is_fully_translated ) {
					$fully_translated = false;
					break;
				}
			}
		}

		return $fully_translated;
	}

	public function get_translated_variation_attribute_post_meta( $meta_value, $meta_key, $original_variation_id, $variation_id, $lang ) {

		$original_product_attr = get_post_meta( wp_get_post_parent_id( $original_variation_id ), '_product_attributes', true );
		$tr_product_attr       = get_post_meta( wp_get_post_parent_id( $variation_id ), '_product_attributes', true );

		$tax = wc_sanitize_taxonomy_name( substr( $meta_key, 10 ) );
		if ( taxonomy_exists( $tax ) ) {
			$attid = $this->get_wcml_terms_instance()->wcml_get_term_id_by_slug( $tax, $meta_value );
			if ( $this->is_translatable_attribute( $tax ) && $attid ) {

				$term_obj      = $this->get_wcml_terms_instance()->wcml_get_term_by_id( $attid, $tax );
				$trnsl_term_id = apply_filters( 'wpml_object_id', $term_obj->term_id, $tax, false, $lang );

				if ( $trnsl_term_id ) {
					$trnsl_term_obj = $this->get_wcml_terms_instance()->wcml_get_term_by_id( $trnsl_term_id, $tax );
					$meta_value     = $trnsl_term_obj->slug;
				}
			}
		} else {
			if ( ! isset( $original_product_attr[ $tax ] ) ) {
				$tax = sanitize_title( $tax );
			}

			if ( isset( $original_product_attr[ $tax ] ) ) {
				if ( isset( $tr_product_attr[ $tax ] ) ) {
					$values_arrs    = array_map( 'trim', explode( '|', $original_product_attr[ $tax ]['value'] ) );
					$values_arrs_tr = array_map( 'trim', explode( '|', $tr_product_attr[ $tax ]['value'] ) );

					$position = array_search( $meta_value, $values_arrs, true );
					if ( ( false !== $position ) && isset( $values_arrs_tr[ $position ] )) {
						$meta_value = $values_arrs_tr[ $position ];
					}
				}
			}
			$meta_key = 'attribute_' . $tax;
		}

		return [
			'meta_value' => $meta_value,
			'meta_key'   => $meta_key,
		];
	}

	public function filter_dropdown_variation_attribute_options_args( $args ) {

		if ( isset( $args['attribute'] ) && isset( $args['product'] ) ) {
			$args['attribute'] = $this->filter_attribute_name( $args['attribute'], $args['product']->get_id() );

			if ( $this->get_wcml_products_instance()->is_product_display_as_translated_post_type() ) {
				foreach ( $args['options'] as $key => $attribute_value ) {
					$args['options'][ $key ] = $this->get_attribute_term_translation_in_current_language( $args['attribute'], $attribute_value );
				}
			}
		}

		return $args;
	}

	/*
	 * special case when original attribute language is German or Danish,
	 * needs handle special chars accordingly
	 */
	public function filter_attribute_name( $attribute_name, $product_id, $return_sanitized = false ) {

		$special_symbols_languages = [ 'de', 'da' ];
		$sanitize_in_origin        = false;
		$current_language          = $this->sitepress->get_current_language();

		if ( $product_id ) {
			$orig_lang = $this->get_wcml_products_instance()->get_original_product_language( $product_id );

			if ( in_array( $orig_lang, $special_symbols_languages, true ) && $current_language !== $orig_lang ) {
				$attribute_name = $this->sitepress->locale_utils->filter_sanitize_title( remove_accents( $attribute_name ), $attribute_name );
				$this->remove_wpml_locale_sanitize_title_filter();
			}

			$sanitize_in_origin = in_array( $current_language, $special_symbols_languages, true ) && $current_language !== $orig_lang;
			if ( $sanitize_in_origin && $return_sanitized ) {
				$this->remove_wpml_locale_sanitize_title_filter();
				$this->sitepress->switch_lang( $orig_lang );
			}
		}

		if ( $return_sanitized ) {
			$attribute_name = sanitize_title( $attribute_name );

			if ( $sanitize_in_origin ) {

				$this->sitepress->switch_lang( $current_language );
			}
		}

		return $attribute_name;
	}

	private function remove_wpml_locale_sanitize_title_filter() {
		remove_filter( 'sanitize_title', [ $this->sitepress->locale_utils, 'filter_sanitize_title' ], 10 );
	}

	public function filter_adding_to_cart_product_attributes_names( $attributes ) {

		if (
			( isset( $_REQUEST['add-to-cart'] ) ) ||
			( isset( $_REQUEST['wc-ajax'] ) && 'get_variation' === $_REQUEST['wc-ajax'] && isset( $_REQUEST['product_id'] ) )
		) {

			$product_id = $_REQUEST['add-to-cart'] ?? $_REQUEST['product_id'];

			foreach ( $attributes as $key => $attribute ) {
				$attributes[ $key ]['name'] = $this->filter_attribute_name( $attributes[ $key ]['name'], $product_id );
			}
		}

		return $attributes;
	}

	public function is_a_taxonomy( $attribute ) {

		if (
			(
				$attribute instanceof WC_Product_Attribute &&
				$attribute->is_taxonomy()
			) ||
			(
				is_array( $attribute ) &&
				$attribute['is_taxonomy']
			)
		) {
			return true;
		}

		return false;
	}

	/**
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function filter_available_variation_attribute_values_in_current_language( $args ) {

		foreach ( $args['attributes'] as $attribute_key => $attribute_value ) {

			$args['attributes'][ $attribute_key ] = $this->get_attribute_term_translation_in_current_language( substr( $attribute_key, 10 ), $attribute_value );
		}

		return $args;
	}

	/**
	 * @param null   $value
	 * @param int    $object_id
	 * @param string $meta_key
	 * @param bool   $single
	 *
	 * @return array|null
	 */
	public function filter_product_variation_post_meta_attribute_values_in_current_language( $value, $object_id, $meta_key, $single ) {

		if ( '' === $meta_key && 'product_variation' === get_post_type( $object_id ) ) {
			remove_filter(
				'get_post_metadata',
				[
					$this,
					'filter_product_variation_post_meta_attribute_values_in_current_language',
				],
				10
			);

			$all_meta = get_post_meta( $object_id );

			add_filter(
				'get_post_metadata',
				[
					$this,
					'filter_product_variation_post_meta_attribute_values_in_current_language',
				],
				10,
				4
			);

			if ( $all_meta ) {
				$wcml_meta = $this->get_product_variation_post_meta_attribute_translated_list( $object_id, $all_meta );

				return array_merge( $all_meta, $wcml_meta );
			}
		}

		return $value;
	}

	private function get_product_variation_post_meta_attribute_translated_list( $object_id, $all_meta ): array {
		$cache_key = $this->getCacheWCMLAttributeMetaKey( $object_id );

		$cached_value = wp_cache_get( $cache_key, self::CACHE_GROUP_VARIATION );

		if ( ! empty( $cached_value ) && is_array( $cached_value ) ) {
			return $cached_value;
		}

		$wcml_meta = [];
		foreach ( $all_meta as $meta_key => $meta_value ) {
			if ( self::isAttributeMeta( $meta_key ) ) {
				foreach ( $meta_value as $key => $value ) {
					$wcml_meta[ $meta_key ][ $key ] = $this->get_attribute_term_translation_in_current_language( substr( $meta_key, 10 ), $value );
				}
			}
		}

		wp_cache_add( $cache_key, $wcml_meta, self::CACHE_GROUP_VARIATION );

		return $wcml_meta;
	}

	/**
	 * @param int    $mid
	 * @param int    $objectId
	 * @param string $key
	 */
	public function invalidateVariationMetaCache( $mid, $objectId, $key ) {
		if ( self::isAttributeMeta( $key ) ) {
			wp_cache_delete( $this->getCacheWCMLAttributeMetaKey( $objectId ), self::CACHE_GROUP_VARIATION );
		}
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	private static function isAttributeMeta( $key ) {
		return 'attribute_' === substr( $key, 0, 10 );
	}

	/**
	 * @param int $variationId
	 *
	 * @return string
	 */
	private function getCacheWCMLAttributeMetaKey( $variationId ) {
		return sprintf(
			'product_variation_%d_post_meta_attribute_translated_%s',
			$variationId,
			$this->sitepress->get_current_language()
		);
	}

	/**
	 * @param array $default_attributes
	 *
	 * @return array
	 */
	public function filter_product_variation_default_attributes( $default_attributes ) {

		if ( $default_attributes ) {

			foreach ( $default_attributes as $attribute_key => $attribute_value ) {

				$default_attributes[ $attribute_key ] = $this->get_attribute_term_translation_in_current_language( $attribute_key, $attribute_value );

			}
		}

		return $default_attributes;
	}

	/**
	 *
	 * @param string $attribute_taxonomy
	 * @param string $attribute_value
	 *
	 * @return string
	 */
	private function get_attribute_term_translation_in_current_language( $attribute_taxonomy, $attribute_value ) {

		if ( taxonomy_exists( $attribute_taxonomy ) ) {
			$term = get_term_by( 'slug', $attribute_value, $attribute_taxonomy );
			if ( $term ) {
				$attribute_value = $term->slug;
			}
		}

		return $attribute_value;
	}

	/**
	 * @param int    $meta_id
	 * @param int    $object_id
	 * @param string $meta_key
	 */
	public function set_translation_status_as_needs_update( $meta_id, $object_id, $meta_key ) {
		if ( $meta_key === '_product_attributes' ) {

			if ( null === $this->post_translations->get_source_lang_code( $object_id ) ) {
				$status_helper = wpml_get_post_status_helper();

				foreach ( $this->post_translations->get_element_translations( $object_id ) as $translation ) {
					if ( null !== $this->post_translations->get_source_lang_code( $translation ) ) {
						$status_helper->set_update_status( $translation, 1 );
					}
				}
			}
		}
	}

	public function maybe_filter_get_variation() {

		if (
			isset( $_POST['product_id'] ) &&
			$this->woocommerce_wpml->products->is_product_display_as_translated_post_type() &&
			is_null( $this->post_translations->element_id_in( $_POST['product_id'], $this->sitepress->get_current_language() ) )
		) {
			foreach ( wp_unslash( $_POST ) as $key => $value ) {
				if ( substr( $key, 0, 13 ) == 'attribute_pa_' ) {
					$taxonomy        = substr( $key, 10 );
					$term_id         = $this->get_wcml_terms_instance()->wcml_get_term_id_by_slug( $taxonomy, $value );
					$translated_term = $this->get_wcml_terms_instance()->wcml_get_translated_term( $term_id, $taxonomy, $this->get_wcml_products_instance()->get_original_product_language( $_POST['product_id'] ) );
					$_POST[ $key ]   = $translated_term->slug;
				}
			}
		}
	}

	/**
	 * Triggered on WPML / Settings by ajax request
	 * Security [wcml-4927]: Data verification and authorization required
	 */
	private function wpml_ajax_custom_tax_sync_options() {
		if ( ! isset( $_POST['icl_ajx_action'] ) || $_POST['icl_ajx_action'] !== 'icl_custom_tax_sync_options' ) {
			return;
		}

		if ( ! isset( $_POST['icl_sync_tax'] ) || ! is_array( $_POST['icl_sync_tax'] ) ) {
			return;
		}

		if ( isset( $_POST['_icl_nonce'] ) && wp_verify_nonce( $_POST['_icl_nonce'], 'icl_custom_tax_sync_options_nonce' ) ) {

			foreach ( $_POST['icl_sync_tax'] as $taxonomyName => $translationPreference ) {
				$taxonomyName          = wc_sanitize_taxonomy_name( wp_unslash( $taxonomyName ) );
				$translationPreference = (int) $translationPreference;

				if ( WCTaxonomies::isProductAttribute( $taxonomyName ) ) {
					$this->set_attribute_config_in_wcml_settings( $taxonomyName, $translationPreference );
				}
			}
		}
	}

	/**
	 * @param string|null $source_lang
	 * @param string      $attr_value
	 * @param string      $attr_name
	 *
	 * @return string|null The filtered source language
	 */
	public function wcml_taxonomy_strings_source_language( $source_lang, $attr_value, $attr_name ) {
		$product_attr = $this->extract_product_attribute_taxonomy( $attr_value, $attr_name );

		if ( $product_attr ) {
			$attribute_taxonomies = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label' );

			if ( in_array( $product_attr, $attribute_taxonomies, true ) ) {
				return $this->sitepress->get_default_language();
			}
		}

		return $source_lang;
	}

	/**
	 * @param string $attr_value
	 * @param string $attr_name
	 *
	 * @return string|null
	 */
	private function extract_product_attribute_taxonomy( $attr_value, $attr_name ) {
		if ( Str::startsWith( WCML_WC_Strings::TAXONOMY_GENERAL_NAME_PREFIX, $attr_name ) ) {
			return substr( $attr_value, strlen( WCML_WC_Strings::TAXONOMY_GENERAL_VALUE_PREFIX ) );
		}

		if ( Str::startsWith( WCML_WC_Strings::TAXONOMY_SINGULAR_NAME_PREFIX, $attr_name ) ) {
			return $attr_value;
		}

		return null;
	}

	/**
	 * @param string|null $source_lang
	 * @param string      $type
	 * @param string      $slug
	 *
	 * @return string|null The filtered source language
	 */
	public function wcml_taxonomy_slug_set_source_language( $source_lang, $type, $slug ) {
		if ( WCTaxonomies::isProductAttribute( $slug ) ) {
			if ( taxonomy_exists( $slug ) ) {
				return $this->sitepress->get_default_language();
			}
		}

		return $source_lang;
	}
}
