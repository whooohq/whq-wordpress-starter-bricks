<?php

use WCML\StandAlone\NullSitePress;
use WCML\Utilities\DB;
use WPML\Core\ISitePress;
use WPML\FP\Fns;
use WPML\FP\Obj;
use function WCML\functions\isStandAlone;
use function WPML\FP\partial;
use function WPML\FP\pipe;

class WCML_Products {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var SitePress */
	private $sitepress;
	/** @var WPML_Post_Translation */
	private $post_translations;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_Cache */
	private $wpml_cache;

	/**
	 * @param woocommerce_wpml        $woocommerce_wpml
	 * @param SitePress|NullSitePress $sitepress
	 * @param WPML_Post_Translation   $post_translations
	 * @param wpdb                    $wpdb
	 * @param WPML_WP_Cache           $wpml_cache
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, ISitePress $sitepress, WPML_Post_Translation $post_translations = null, wpdb $wpdb, WPML_WP_Cache $wpml_cache = null ) {
		$this->woocommerce_wpml  = $woocommerce_wpml;
		$this->sitepress         = $sitepress;
		$this->post_translations = $post_translations;
		$this->wpdb              = $wpdb;
		$this->wpml_cache        = $wpml_cache ?: new WPML_WP_Cache( 'WCML_Products' );
	}

	public function add_hooks() {
		if ( is_admin() ) {
			if ( ! isStandAlone() ) {
				add_filter( 'woocommerce_json_search_found_products', [ $this, 'filter_wc_searched_products_on_admin' ] );
				add_action( 'wp_ajax_wpml_switch_post_language', [ $this, 'switch_product_variations_language' ], 9 );
				add_filter( 'post_row_actions', [ $this, 'filter_product_actions' ], 10, 2 );
				add_filter( 'woocommerce_product_type_query', [ $this, 'override_product_type_query' ], 10, 2 );
			}
		} else {
			add_filter( 'woocommerce_related_products_args', [ $this, 'filter_related_products_args' ] );

			if ( ! isStandAlone() ) {
				add_filter( 'woocommerce_json_search_found_products', [ $this, 'filter_wc_searched_products_on_front' ] );
				add_filter( 'woocommerce_product_related_posts_query', [ $this, 'filter_related_products_query' ] );
				add_filter( 'woocommerce_shortcode_products_query', [ $this, 'add_lang_to_shortcode_products_query' ] );
				add_filter( 'woocommerce_product_file_download_path', [ $this, 'filter_file_download_path' ] );
				add_filter( 'woocommerce_product_add_to_cart_url', [ $this, 'maybe_add_language_parameter' ] );
			}
		}

		if ( ! isStandAlone() ) {
			add_filter( 'woocommerce_upsell_crosssell_search_products', [ $this, 'filter_woocommerce_upsell_crosssell_posts_by_language' ] );
			add_action( 'woocommerce_after_product_ordering', [ $this, 'update_all_products_translations_ordering' ] );
			add_filter( 'wpml_copy_from_original_custom_fields', [ $this, 'filter_excerpt_field_content_copy' ] );
			add_filter( 'wpml_override_is_translator', [ $this, 'wcml_override_is_translator' ], 10, 3 );
			add_filter( 'wpml_user_can_translate', [ $this, 'wcml_user_can_translate' ], 10, 2 );
			add_filter( 'wc_product_has_unique_sku', [ $this, 'check_product_sku' ], 10, 3 );
			add_filter( 'get_product_search_form', [ $this->sitepress, 'get_search_form_filter' ] );
			add_filter( 'woocommerce_pre_customer_bought_product', Fns::withoutRecursion( Fns::identity(), [ $this, 'is_customer_bought_product' ] ), 10, 4 );
		}

		add_filter( 'get_post_metadata', [ $this, 'filter_product_data' ], 10, 3 );
		add_filter( 'woocommerce_can_reduce_order_stock', [ $this, 'remove_post_meta_data_filter_on_checkout_stock_update' ] );
	}

	/**
	 * @param int|string $product_id
	 *
	 * @return bool
	 */
	public function is_original_product( $product_id ) {
		return ! $this->post_translations || null === $this->post_translations->get_source_lang_code( $product_id );
	}

	/**
	 * @param int|string $product_id
	 *
	 * @return null|string
	 */
	public function get_original_product_language( $product_id ) {
		return $this->post_translations
			? $this->post_translations->get_element_lang_code( $this->get_original_product_id( $product_id ) )
			: $this->sitepress->get_default_language();
	}

	/**
	 * @param int|string $product_id
	 *
	 * @return int|string
	 */
	public function get_original_product_id( $product_id ) {

		$original_product_id = $this->post_translations ? $this->post_translations->get_original_element( $product_id ) : null;

		return $original_product_id ?: $product_id;
	}

	public function is_variable_product( $product_id ) {
		$cache_key        = $product_id;
		$cache_group      = 'is_variable_product';
		$temp_is_variable = wp_cache_get( $cache_key, $cache_group );
		if ( $temp_is_variable ) {
			return $temp_is_variable;
		}

		$get_variation_term_taxonomy_ids = $this->wpdb->get_col( "SELECT tt.term_taxonomy_id FROM {$this->wpdb->terms} AS t LEFT JOIN {$this->wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable' AND tt.taxonomy = 'product_type'" );
		$get_variation_term_taxonomy_ids = apply_filters( 'wcml_variation_term_taxonomy_ids', (array) $get_variation_term_taxonomy_ids );

		$is_variable_product = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT count(object_id) FROM {$this->wpdb->term_relationships} WHERE object_id = %d AND term_taxonomy_id IN (" . DB::prepareIn( $get_variation_term_taxonomy_ids, '%d' ) . ')', $product_id ) );
		$is_variable_product = apply_filters( 'wcml_is_variable_product', $is_variable_product, $product_id );

		wp_cache_set( $cache_key, $is_variable_product, $cache_group );

		return $is_variable_product;
	}

	public function is_downloadable_product( $product ) {

		$product_id = $product->get_id();
		$cache_key  = 'is_downloadable_product_' . $product_id;

		$found           = false;
		$is_downloadable = $this->wpml_cache->get( $cache_key, $found );
		if ( ! $found ) {
			if ( $product->is_downloadable() ) {
				$is_downloadable = true;
			} elseif ( $this->is_variable_product( $product_id ) ) {
				foreach ( $product->get_available_variations() as $variation ) {
					if ( $variation['is_downloadable'] ) {
						$is_downloadable = true;
						break;
					}
				}
			}
			$this->wpml_cache->set( $cache_key, $is_downloadable );
		}

		return $is_downloadable;
	}

	public function is_grouped_product( $product_id ) {
		$get_variation_term_taxonomy_id = $this->wpdb->get_var( "SELECT tt.term_taxonomy_id FROM {$this->wpdb->terms} AS t LEFT JOIN {$this->wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE t.name = 'grouped'" );
		$is_grouped_product             = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT count(object_id) FROM {$this->wpdb->term_relationships} WHERE object_id = %d AND term_taxonomy_id = %d ", $product_id, $get_variation_term_taxonomy_id ) );

		return $is_grouped_product;
	}

	public function get_translation_flags( $active_languages, $slang = false, $job_language = false ) {
		$available_languages = [];

		foreach ( $active_languages as $key => $language ) {
			if ( $job_language && $language['code'] != $job_language ) {
				continue;
			} elseif ( ! $slang ||
				(
					( $slang != $language['code'] ) &&
					( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ||
						wpml_check_user_is_translator( $slang, $language['code'] ) ) &&
					( ! isset( $_POST['translation_status_lang'] ) ||
						( isset( $_POST['translation_status_lang'] ) &&
							( $_POST['translation_status_lang'] == $language['code'] ) ||
							$_POST['translation_status_lang'] == '' )
					)
				)
			) {
				$available_languages[ $key ]['name']     = $language['english_name'];
				$available_languages[ $key ]['flag_url'] = $this->sitepress->get_flag_url( $language['code'] );
			}
		}

		return $available_languages;
	}

	public function get_translation_statuses( $original_product_id, $product_translations, $active_languages, $slang = false, $trid = false, $job_language = false ) {
		$status_display_factory = new WPML_Post_Status_Display_Factory( $this->sitepress );
		$status_display = $status_display_factory->create();
		foreach ( $active_languages as $language ) {
			if ( $job_language && $language['code'] != $job_language ) {
				continue;
			} elseif ( isset( $product_translations[ $language['code'] ] ) && $product_translations[ $language['code'] ]->original ) { ?>
				<span title="<?php echo $language['english_name'] . ': ' . __( 'Original language', 'woocommerce-multilingual' ); ?>">
					<i class="otgs-ico-original"></i>
				</span>
				<?php
			} elseif (
					$slang != $language['code'] &&
					( ! isset( $_POST['translation_status_lang'] ) ||
						( isset( $_POST['translation_status_lang'] ) &&
							$_POST['translation_status_lang'] == $language['code'] ||
							$_POST['translation_status_lang'] == ''
						)
					)
				) {
				if ( isset( $product_translations[ $language['code'] ] ) ) {
					$job_id = $this->wpdb->get_var(
						$this->wpdb->prepare(
							"SELECT MAX(tj.job_id) FROM {$this->wpdb->prefix}icl_translate_job AS tj
                                         LEFT JOIN {$this->wpdb->prefix}icl_translation_status AS ts
                                         ON tj.rid = ts.rid WHERE ts.translation_id=%d",
							$product_translations[ $language['code'] ]->translation_id
						)
					);
				} else {
					$job_id = false;
				}

				if ( ! current_user_can( 'wpml_manage_woocommerce_multilingual' ) && isset( $product_translations[ $language['code'] ] ) ) {
					/** @var stdClass */
					$tr_status = $this->wpdb->get_row(
						$this->wpdb->prepare(
							"SELECT status,translator_id FROM {$this->wpdb->prefix}icl_translation_status
                                            WHERE translation_id = %d",
							$product_translations[ $language['code'] ]->translation_id
						)
					);

					if ( ! is_null( $tr_status ) && get_current_user_id() != $tr_status->translator_id ) {
						if ( $tr_status->status == ICL_TM_IN_PROGRESS ) {
							?>
								<a title="<?php _e( 'Translation in progress', 'woocommerce-multilingual' ); ?>"><i
										class="otgs-ico-in-progress"></i></a>
							<?php
							continue;
						} elseif ( $tr_status->status == ICL_TM_WAITING_FOR_TRANSLATOR && ! $job_id ) {
							$tr_job_id              = $this->wpdb->get_var(
								$this->wpdb->prepare(
									"SELECT j.job_id FROM {$this->wpdb->prefix}icl_translate_job j
                                                    JOIN {$this->wpdb->prefix}icl_translation_status s ON j.rid = s.rid
                                                    WHERE s.translation_id = %d",
									$product_translations[ $language['code'] ]->translation_id
								)
							);
							$translation_queue_page = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . $tr_job_id );
							$edit_url               = apply_filters( 'icl_job_edit_url', $translation_queue_page, $tr_job_id );
							?>
								<a href="<?php echo $edit_url; ?>" title="<?php echo $language['english_name'] . ': ' . __( 'Take this and edit', 'woocommerce-multilingual' ); ?>">
									<i class="otgs-ico-add"></i>
								</a>
												<?php
												continue;
						}
					}
					wpml_tm_load_status_display_filter();
				}
				echo $status_display->get_status_html( $original_product_id, $language['code'] );
			}
		}
	}

	/**
	 * @param bool  $is_translator
	 * @param int   $user_id
	 * @param array $args
	 *
	 * @return bool
	 */
	public function wcml_override_is_translator( $is_translator, $user_id, $args ) {
		if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
			if ( ! ( isset( $args['post_id'] ) && $args['post_id'] ) ) {
				return true;
			}
			$wc_post_types = [ 'product', 'product_variation', 'shop_coupon', 'shop_order', 'shop_order_refund' ];
			$post_type     = get_post_type( $args['post_id'] );
			if ( in_array( $post_type, $wc_post_types, true ) ) {
				return true;
			}
		}

		return $is_translator;
	}

	/**
	 * @param bool    $user_can_translate
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function wcml_user_can_translate( $user_can_translate, $user ) {
		if ( user_can( $user, 'wpml_operate_woocommerce_multilingual' ) ) {
			return true;
		}

		return $user_can_translate;
	}

	// product quickedit.
	public function filter_product_actions( $actions, $post ) {
		if (
			$post->post_type == 'product' &&
			! $this->is_original_product( $post->ID ) &&
			isset( $actions['inline hide-if-no-js'] ) ) {
			$new_actions = [];
			foreach ( $actions as $key => $action ) {
				if ( $key == 'inline hide-if-no-js' ) {
					$new_actions['quick_hide'] = '<a href="#TB_inline?width=200&height=150&inlineId=quick_edit_notice" class="thickbox" title="' .
													__( 'Edit this item inline', 'woocommerce-multilingual' ) . '">' .
													__( 'Quick Edit', 'woocommerce-multilingual' ) . '</a>';
				} else {
					$new_actions[ $key ] = $action;
				}
			}
			return $new_actions;
		}
		return $actions;
	}

	/**
	 * Takes off translated products from the Up-sells/Cross-sells tab.
	 */
	public function filter_woocommerce_upsell_crosssell_posts_by_language( $posts ) {
		foreach ( $posts as $key => $post ) {
			$post_id   = $posts[ $key ]->ID;
			$post_data = $this->wpdb->get_row(
				$this->wpdb->prepare(
					"SELECT * FROM {$this->wpdb->prefix}icl_translations
                                WHERE element_id = %d ",
					$post_id
				),
				ARRAY_A
			);

			if ( $post_data['language_code'] !== $this->sitepress->get_current_language() ) {
				unset( $posts[ $key ] );
			}
		}

		return $posts;
	}


	/**
	 * Filters products by language
	 *
	 * @param array $found_products
	 * @param bool  $language
	 *
	 * @return array
	 */
	private function filter_found_products_by_language( $found_products, $language = false ) {

		if ( ! $language ) {
			$language = $this->sitepress->get_current_language();
		}

		foreach ( $found_products as $product_id => $product_name ) {

			if ( $this->post_translations->get_element_lang_code( $product_id ) !== $language ) {
				unset( $found_products[ $product_id ] );
			}
		}

		return $found_products;
	}

	/**
	 * @param array $found_products
	 *
	 * @return array
	 */
	public function filter_wc_searched_products_on_front( $found_products ) {
		return $this->filter_found_products_by_language( $found_products );
	}

	/**
	 * @param array $found_products
	 *
	 * @return array
	 */
	public function filter_wc_searched_products_on_admin( $found_products ) {

		if ( isset( $_COOKIE['_wcml_dashboard_order_language'] ) ) {
			$found_products = $this->filter_found_products_by_language( $found_products, $_COOKIE['_wcml_dashboard_order_language'] );
		} else {
			$found_products = $this->filter_found_products_by_language( $found_products );
		}

		return $found_products;
	}

	// update menu_order fro translations after ordering original products.
	public function update_all_products_translations_ordering() {
		if ( $this->woocommerce_wpml->settings['products_sync_order'] ) {
			$current_language = $this->sitepress->get_current_language();
			if ( $current_language == $this->sitepress->get_default_language() ) {
				$products = $this->wpdb->get_results(
					$this->wpdb->prepare(
						"SELECT p.ID FROM {$this->wpdb->posts} AS p
                                    LEFT JOIN {$this->wpdb->prefix}icl_translations AS icl
                                    ON icl.element_id = p.id
                                    WHERE p.post_type = 'product'
                                      AND p.post_status IN ( 'publish', 'future', 'draft', 'pending', 'private' )
                                      AND icl.element_type= 'post_product'
                                      AND icl.language_code = %s",
						$current_language
					)
				);

				foreach ( $products as $product ) {
					$this->update_order_for_product_translations( (int)$product->ID );
				}
			}
		}
	}

	/**
	 * update menu_order fro translations after ordering original products
	 *
	 * @param int $product_id
	 */
	public function update_order_for_product_translations( $product_id ) {
		if ( isset( $this->woocommerce_wpml->settings['products_sync_order'] ) && $this->woocommerce_wpml->settings['products_sync_order'] ) {
			$current_language = $this->sitepress->get_current_language();

			if ( $current_language == $this->sitepress->get_default_language() ) {
				$menu_order   = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT menu_order FROM {$this->wpdb->posts} WHERE ID = %d", $product_id ) );
				$translations = $this->post_translations->get_element_translations( $product_id );

				foreach ( $translations as $translation ) {
					if ( (int)$translation !== $product_id ) {
						$this->wpdb->update( $this->wpdb->posts, [ 'menu_order' => $menu_order ], [ 'ID' => $translation ] );
					}
				}
			}
		}
	}

	public function filter_excerpt_field_content_copy( $elements ) {

		if ( $elements['post_type'] == 'product' ) {
			$elements['excerpt'] ['editor_type'] = 'editor';
		}
		if ( function_exists( 'format_for_editor' ) ) {
			// WordPress 4.3 uses format_for_editor
			$elements['excerpt']['value'] = htmlspecialchars_decode( format_for_editor( $elements['excerpt']['value'], $_POST['excerpt_type'] ) );
		} else {
			// Backwards compatible for WordPress < 4.3
			if ( $_POST['excerpt_type'] == 'rich' ) {
				$elements['excerpt']['value'] = htmlspecialchars_decode( wp_richedit_pre( $elements['excerpt']['value'] ) );
			} else {
				$elements['excerpt']['value'] = htmlspecialchars_decode( wp_htmledit_pre( $elements['excerpt']['value'] ) );
			}
		}
		return $elements;
	}

	public function filter_related_products_args( $args ) {
		if ( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
			isset( $this->woocommerce_wpml->settings['display_custom_prices'] ) &&
			$this->woocommerce_wpml->settings['display_custom_prices'] ) {

			$client_currency      = $this->woocommerce_wpml->multi_currency->get_client_currency();
			$woocommerce_currency = wcml_get_woocommerce_currency_option();

			if ( $client_currency != $woocommerce_currency ) {
				$args['meta_query'][] = [
					'key'     => '_wcml_custom_prices_status',
					'value'   => 1,
					'compare' => '=',
				];
			}
		}
		return $args;
	}

	/**
	 * @param array $query
	 *
	 * @return array
	 */
	public function filter_related_products_query( $query ) {

		$query['join']  .= " LEFT JOIN {$this->wpdb->prefix}icl_translations AS icl ON icl.element_id = p.ID ";
		$query['where'] .= $this->wpdb->prepare( ' AND icl.language_code = %s ', $this->sitepress->get_current_language() );

		return $query;
	}

	/*
	 * get meta ids for multiple values post meta key
	 */
	public function get_mid_ids_by_key( $post_id, $meta_key ) {
		$ids = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT meta_id FROM {$this->wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key ) );
		if ( $ids ) {
			return $ids;
		}

		return false;
	}

	// count "in progress" and "waiting on translation" as untranslated too
	public function get_untranslated_products_count( $language ) {

		$count = 0;

		$products = $this->wpdb->get_results(
			"
                      SELECT p.ID, t.trid, t.language_code
                      FROM {$this->wpdb->posts} AS p
                      LEFT JOIN {$this->wpdb->prefix}icl_translations AS t ON t.element_id = p.id
                      WHERE p.post_type = 'product' AND t.element_type = 'post_product' AND t.source_language_code IS NULL
                  "
		);

		foreach ( $products as $product ) {
			if ( $product->language_code == $language ) {
				continue;
			}

			$translation_status = apply_filters( 'wpml_translation_status', null, $product->trid, $language );

			if ( in_array( $translation_status, [ ICL_TM_NOT_TRANSLATED, ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS ] ) ) {
				$count++;
			}
		}

		return $count;
	}

	public function is_hide_resign_button() {
		global $iclTranslationManagement;

		$hide_resign = false;

		if ( isset( $_GET['source_language_code'] ) && isset( $_GET['language_code'] ) ) {

			$from_lang = $_GET['source_language_code'];
			$to_lang   = $_GET['language_code'];

		} elseif ( isset( $_GET['job_id'] ) ) {

			$job = $iclTranslationManagement->get_translation_job( $_GET['job_id'] );
			if ( $job ) {
				$from_lang = $job->source_language_code;
				$to_lang   = $job->language_code;
			}
		}

		if ( isset( $from_lang, $to_lang ) ) {

			$translators = $iclTranslationManagement->get_blog_translators(
				[
					'from' => $from_lang,
					'to'   => $to_lang,
				]
			);

			if ( empty( $translators ) || ( sizeof( $translators ) == 1 && $translators[0]->ID == get_current_user_id() ) ) {
				$hide_resign = true;
			}
		}

		return $hide_resign;
	}

	public function switch_product_variations_language() {

		$lang_to = false;
		$post_id = false;

		if ( isset( $_POST['wpml_to'] ) ) {
			$lang_to = $_POST['wpml_to'];
		}
		if ( isset( $_POST['wpml_post_id'] ) ) {
			$post_id = $_POST['wpml_post_id'];
		}

		if ( $post_id && $lang_to && get_post_type( $post_id ) == 'product' ) {
			$product_variations = $this->woocommerce_wpml->sync_variations_data->get_product_variations( $post_id );
			foreach ( $product_variations as $product_variation ) {
				$trid                      = $this->sitepress->get_element_trid( $product_variation->ID, 'post_product_variation' );
				$current_prod_variation_id = apply_filters( 'translate_object_id', $product_variation->ID, 'product_variation', false, $lang_to );
				if ( is_null( $current_prod_variation_id ) ) {
					$this->sitepress->set_element_language_details( $product_variation->ID, 'post_product_variation', $trid, $lang_to );

					foreach ( get_post_custom( $product_variation->ID ) as $meta_key => $meta ) {
						foreach ( $meta as $meta_value ) {
							if ( substr( $meta_key, 0, 10 ) == 'attribute_' ) {
								$trn_post_meta = $this->woocommerce_wpml->attributes->get_translated_variation_attribute_post_meta( $meta_value, $meta_key, $product_variation->ID, $product_variation->ID, $lang_to );
								update_post_meta( $product_variation->ID, $trn_post_meta['meta_key'], $trn_post_meta['meta_value'] );
							}
						}
					}
				}
			}
		}
	}

	public function check_product_sku( $sku_found, $product_id, $sku ) {

		if ( $sku_found ) {

			$product_trid = $this->post_translations->get_element_trid( $product_id );

			$products = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"
                SELECT p.ID
                FROM {$this->wpdb->posts} as p
                LEFT JOIN {$this->wpdb->postmeta} as pm ON ( p.ID = pm.post_id )
                WHERE p.post_type IN ( 'product', 'product_variation' )
                AND p.post_status = 'publish'
                AND pm.meta_key = '_sku' AND pm.meta_value = '%s'
             ",
					wp_slash( $sku )
				)
			);

			$sku_found = false;

			foreach ( (array) $products as $product ) {
				$trid = $this->post_translations->get_element_trid( $product->ID );
				if ( $product_trid !== $trid ) {
					$sku_found = true;
					break;
				}
			}
		}

		return $sku_found;
	}

	public function add_lang_to_shortcode_products_query( $query_args ) {

		$query_args['lang'] = $this->sitepress->get_current_language();

		return $query_args;
	}


	/**
	 * Get file download path in correct domain
	 *
	 * @param string $file_path file path URL
	 * @return string
	 */
	public function filter_file_download_path( $file_path ) {

		$is_per_domain = $this->sitepress->get_wp_api()->constant( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN' ) === (int) $this->sitepress->get_setting( 'language_negotiation_type' );

		if ( $is_per_domain ) {
			$wpml_url_helper = new WPML_URL_Converter_Url_Helper();

			if ( strpos( trim( $file_path ), $wpml_url_helper->get_abs_home() ) === 0 ) {
				$file_path = $this->sitepress->convert_url( $file_path );
			}
		}

		return $file_path;

	}


	/**
	 *
	 * @return bool
	 */
	public function is_product_display_as_translated_post_type() {
		return apply_filters( 'wpml_is_display_as_translated_post_type', false, 'product' );
	}

	/**
	 * @param bool   $value
	 * @param string $customer_email
	 * @param int    $user_id
	 * @param int    $product_id
	 *
	 * @return bool
	 */
	public function is_customer_bought_product( $value, $customer_email, $user_id, $product_id ) {
        if ( $value ) {
            return $value;
        }

        $post_type = get_post_type( $product_id );
        $trid      = apply_filters( 'wpml_element_trid', 0, $product_id, 'post_' . $post_type );

        // $has_bought_original_or_translation :: object -> bool
        $has_bought_original_or_translation = pipe(
            Obj::prop( 'element_id' ),
            partial( 'wc_customer_bought_product', $customer_email, $user_id )
        );

        return (bool) wpml_collect(
            apply_filters( 'wpml_get_element_translations', [], $trid, $post_type )
        )->first( $has_bought_original_or_translation );
	}

	public function filter_product_data( $data, $product_id, $meta_key ) {

		if ( ! $meta_key ) {

			$post_type = get_post_type( $product_id );

			if ( in_array( $post_type, [ 'product', 'product_variation' ], true ) ) {

				remove_filter( 'get_post_metadata', [ $this, 'filter_product_data' ], 10 );

				$data = get_post_meta( $product_id );

				$meta_keys_to_filter = [];
				$is_mc_enabled       = (int) $this->woocommerce_wpml->settings['enable_multi_currency'] === (int) $this->sitepress->get_wp_api()->constant( 'WCML_MULTI_CURRENCIES_INDEPENDENT' );

				if ( $is_mc_enabled ) {
					$meta_keys_to_filter = wcml_price_custom_fields( $product_id );
				}

				if ( ! is_admin() && ! isStandAlone() ) {
					if ( 'product' === $post_type ) {
						$meta_keys_to_filter[] = WCML_Comments::WC_RATING_COUNT_KEY;
						$meta_keys_to_filter[] = WCML_Comments::WC_REVIEW_COUNT_KEY;
						$meta_keys_to_filter[] = WCML_Comments::WC_AVERAGE_RATING_KEY;
					}

					$is_original_product = $this->woocommerce_wpml->products->is_original_product( $product_id );
					if ( ! $is_original_product ) {
						$meta_keys_to_filter[] = '_thumbnail_id';
						if ( 'product' === $post_type && is_product() ) {
							$meta_keys_to_filter[] = '_product_image_gallery';
						}
					}
				}

				foreach ( $meta_keys_to_filter as $meta_key ) {
					$data[ $meta_key ][0] = get_post_meta( $product_id, $meta_key, true );
				}

				add_filter( 'get_post_metadata', [ $this, 'filter_product_data' ], 10, 3 );
			}
		}

		return $data;
	}

	/**
	 * @param int $product_id
	 *
	 * @return null|string
	 */
	public function get_product_price_from_db( $product_id ) {

		return $this->wpdb->get_var( $this->wpdb->prepare( "SELECT meta_value FROM {$this->wpdb->postmeta} WHERE `meta_key` = '_price' AND post_id = %d ", $product_id ) );
	}

	/**
	 * return not cached value for product
	 *
	 * @param bool $product_type
	 * @param int  $product_id
	 *
	 * @return bool|string
	 */
	public function override_product_type_query( $product_type, $product_id ) {

		if ( 'product' === get_post_type( $product_id ) ) {
			$product_type = 'simple';
			$terms        = get_the_terms( $product_id, 'product_type' );
			if ( $terms ) {
				$product_type = sanitize_title( current( $terms )->name );
			}
		}

		return $product_type;
	}

	/**
	 * @param bool $reduce_stock
	 *
	 * @return bool
	 */
	public function remove_post_meta_data_filter_on_checkout_stock_update( $reduce_stock ) {
		if ( isset( $_GET['wc-ajax'] ) && 'checkout' === $_GET['wc-ajax'] ) {
			remove_filter( 'get_post_metadata', [ $this, 'filter_product_data' ], 10 );
		}
		return $reduce_stock;
	}

	public function maybe_add_language_parameter( $url ) {

		if (
			'no' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) &&
			constant( 'WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER' ) === (int) $this->sitepress->get_setting( 'language_negotiation_type' )
		) {
			$current_language = $this->sitepress->get_current_language();
			if ( $current_language !== $this->sitepress->get_default_language() ) {
				$url .= '&lang=' . $this->sitepress->get_current_language();
			}
		}

		return $url;
	}

	/**
	 * @param int    $product_id
	 * @param string $status
	 */
	public function update_stock_status( $product_id, $status ) {
		update_post_meta( $product_id, '_stock_status', $status );
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->wc_product_meta_lookup} SET stock_status = %s WHERE product_id = %d",
				$status,
				$product_id
			)
		);
	}
}
