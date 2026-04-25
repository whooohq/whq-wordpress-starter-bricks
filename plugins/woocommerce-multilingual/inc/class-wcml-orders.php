<?php

use WCML\Orders\Helper as OrdersHelper;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WCML\Utilities\WCTaxonomies;
use function WPML\FP\curryN;
use function WPML\FP\invoke;

class WCML_Orders {

	const DASHBOARD_COOKIE_NAME = '_wcml_dashboard_order_language';
	const COOKIE_TTL            = 86400;
	const KEY_LANGUAGE          = 'wpml_language';

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var SitePress */
	private $sitepress;

	public function __construct( $woocommerce_wpml, $sitepress ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;

		add_action( 'init', [ $this, 'init' ] );

		// Checkout page.
		add_action( 'wp_ajax_woocommerce_checkout', [ $this, 'switch_to_current' ], 9 );
		add_action( 'wp_ajax_nopriv_woocommerce_checkout', [ $this, 'switch_to_current' ], 9 );

		add_action( 'wp_ajax_wcml_order_delete_items', [ $this, 'order_delete_items' ] );
	}

	public function init() {
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'set_order_language' ] );
		add_action( 'woocommerce_before_order_object_save', [ $this, 'setOrderLanguageBeforeSave' ] );

		add_filter( 'icl_lang_sel_copy_parameters', [ $this, 'append_query_parameters' ] );

		add_filter( 'the_comments', [ $this, 'get_filtered_comments' ], 10, 2 );

		add_action( 'woocommerce_process_shop_order_meta', [ $this, 'set_order_language_backend' ] );
		add_action(
			'woocommerce_order_actions_start',
			[ $this, 'order_language_dropdown' ],
			11
		); // After order currency drop-down.

		add_action( 'woocommerce_before_order_itemmeta', [ $this, 'backend_before_order_itemmeta' ], 100, 3 );
		add_action( 'woocommerce_after_order_itemmeta', [ $this, 'backend_after_order_itemmeta' ], 100, 3 );

		add_filter( 'woocommerce_get_item_downloads', [ $this, 'filter_downloadable_product_items' ], 10, 3 );
		add_filter(
			'woocommerce_customer_get_downloadable_products',
			[ $this, 'filter_customer_get_downloadable_products' ],
			10
		);

		if ( is_admin() ) {
			$this->maybe_set_dashboard_cookie();
		}
	}

	/**
	 * This method will try to convert the order notes in the current language
	 * if the user is identified (i.e. he has an ID).
	 *
	 * Note: I was not able to find the place where the strings are
	 * registered and maybe this code is not used anymore. This should
	 * be investigated in the future.
	 *
	 * @param \WP_Comment[] $comments
	 * @param \WP_Comment_Query $commentQuery
	 *
	 * @return \WP_Comment[]
	 */
	public function get_filtered_comments( $comments, $commentQuery ) {
		if ( 'order_note' !== Obj::path( [ 'query_vars', 'type' ], $commentQuery ) ) {
			return $comments;
		}

		// $ifIdentifiedUser :: void -> bool
		$ifIdentifiedUser = function () {
			return (bool) get_current_user_id();
		};

		// $translateInWoocommerce :: string -> string
		$translateInWoocommerce = \WPML\FP\partialRight( 'translate', 'woocommerce' );

		// $translateComment :: WP_Comment -> WP_Comment
		$translateComment = Obj::over( Obj::lensProp( 'comment_content' ), $translateInWoocommerce );

		return Maybe::of( $comments )
			->filter( $ifIdentifiedUser )
			->map( Fns::map( $translateComment ) )
			->getOrElse( $comments );
	}

	public function backend_before_order_itemmeta( $item_id, $item, $product ) {
		global $sitepress;

		if ( $this->get_order_language_by_item_id( $item_id ) != $sitepress->get_user_admin_language( get_current_user_id(), true ) ) {
			foreach ( $item['item_meta'] as $key => $item_meta ) {
				if ( taxonomy_exists( wc_attribute_taxonomy_name( $key ) ) || WCTaxonomies::isProductAttribute( $key ) ) {
					$item_meta = (array) $item_meta;
					foreach ( $item_meta as $value ) {
						$this->force_update_itemmeta( $item_id, $key, $value, $sitepress->get_user_admin_language( get_current_user_id(), true ) );
					}
				}
			}
		}
	}

	public function backend_after_order_itemmeta( $item_id, $item, $product ) {
		global $sitepress;

		$order_languge = $this->get_order_language_by_item_id( $item_id );
		if ( $order_languge != $sitepress->get_user_admin_language( get_current_user_id(), true ) ) {
			foreach ( $item['item_meta'] as $key => $item_meta ) {
				if ( taxonomy_exists( wc_attribute_taxonomy_name( $key ) ) || WCTaxonomies::isProductAttribute( $key ) ) {
					$item_meta = (array) $item_meta;
					foreach ( $item_meta as $value ) {
						$this->force_update_itemmeta( $item_id, $key, $value, $order_languge );
					}
				}
			}
		}
	}

	public function get_order_language_by_item_id( $item_id ) {
		global $wpdb;

		$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", $item_id ) );

		return self::getLanguage( $order_id );
	}

	// force update to display attribute in correct language on edit order page.
	public function force_update_itemmeta( $item_id, $key, $value, $languge ) {
		global $wpdb, $woocommerce_wpml;

		$taxonomy        = ! WCTaxonomies::isProductAttribute( $key ) ? wc_attribute_taxonomy_name( $key ) : $key;
		$term_id         = $woocommerce_wpml->terms->wcml_get_term_id_by_slug( $taxonomy, $value );
		$translated_term = $woocommerce_wpml->terms->wcml_get_translated_term( $term_id, $taxonomy, $languge );

		if ( $translated_term ) {
			$value = $translated_term->slug;
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_order_itemmeta',
				[ 'meta_value' => $value ],
				[
					'order_item_id' => $item_id,
					'meta_key'      => $key,
				]
			);
		}
	}

	/**
	 * Adds language to order post type.
	 *
	 * @param int $order_id
	 */
	public function set_order_language( $order_id ) {
		if ( ! self::getLanguage( $order_id ) ) {
			self::setLanguage( $order_id, ICL_LANGUAGE_CODE );
		}
	}

	/**
	 * @param WC_Abstract_Order $order
	 */
	public function setOrderLanguageBeforeSave( $order ) {
		if (
			! in_array( $order->get_status(), [ 'checkout-draft', 'auto-draft' ], true )
			&& ! $order->get_meta( self::KEY_LANGUAGE )
		) {
			/**
			 * We cannot use our helper to set the order language at this stage
			 * because the order is not saved yet, and it may not have an ID.
			 *
			 * @see \WCML_Orders::setLanguage()
			 */
			$order->add_meta_data( self::KEY_LANGUAGE, $this->sitepress->get_current_language(), true );
			wp_cache_delete( $order->generate_meta_cache_key( $order->get_id(), 'orders' ), 'orders' );
		}
	}

	public function append_query_parameters( $parameters ) {

		if ( is_order_received_page() || is_checkout() ) {
			if ( ! in_array( 'order', $parameters ) ) {
				$parameters[] = 'order';
			}
			if ( ! in_array( 'key', $parameters ) ) {
				$parameters[] = 'key';
			}
		}

		return $parameters;
	}

	public function switch_to_current() {
		$this->woocommerce_wpml->emails->change_email_language( $this->sitepress->get_current_language() );
	}

	public function order_language_dropdown( $order_id ) {
		if ( ! OrdersHelper::getCurrency( $order_id ) ) { // This is probably a bug, I don't see why we would check on the currency here.
			$languages     = apply_filters(
				'wpml_active_languages',
				[],
				[
					'skip_missing' => 0,
					'orderby'      => 'code',
				]
			);
			$selected_lang = $_COOKIE [ self::DASHBOARD_COOKIE_NAME ] ?? $this->sitepress->get_default_language();
			?>
			<li class="wide">
				<label><?php _e( 'Order language:' ); ?></label>
				<select id="dropdown_shop_order_language" name="wcml_shop_order_language">
					<?php if ( ! empty( $languages ) ) : ?>

						<?php foreach ( $languages as $l ) : ?>

							<option
									value="<?php echo esc_attr( $l['language_code'] ); ?>" <?php echo $selected_lang == $l['language_code'] ? 'selected="selected"' : ''; ?>><?php echo esc_html( $l['translated_name'] ); ?></option>

						<?php endforeach; ?>

					<?php endif; ?>
				</select>
			</li>
			<?php
			$wcml_set_dashboard_order_language_nonce   = esc_js( wp_create_nonce( 'set_dashboard_order_language' ) );
			$wcml_set_dashboard_order_language_message = esc_js( __( 'All the products will be removed from the current order in order to change the language', 'woocommerce-multilingual' ) );
			$wcml_set_dashboard_order_language_script  = <<<JS
                 var order_lang_current_value = jQuery('#dropdown_shop_order_language option:selected').val();

                 jQuery('#dropdown_shop_order_language').on('change', function(){
                    if(confirm('$wcml_set_dashboard_order_language_message')){
                        var lang = jQuery(this).val();

                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'post',
                            dataType: 'json',
                            data: {action: 'wcml_order_delete_items', order_id: woocommerce_admin_meta_boxes.post_id, lang: lang , wcml_nonce: '$wcml_set_dashboard_order_language_nonce' },
                            success: function( response ){
                                if(typeof response.error !== 'undefined'){
                                    alert(response.error);
                                }else{
                                    window.location = window.location.href;
                                }
                            }
                        });
                    }else{
                        jQuery(this).val( order_lang_current_value );
                        return false;
                    }
                });
JS;

			$handle = 'wcml_set_dashboard_order_language_dropdown';
			wp_register_script( $handle, '', [ 'jquery' ], WCML_VERSION, true );
			wp_enqueue_script( $handle );
			wp_add_inline_script( $handle, $wcml_set_dashboard_order_language_script );
		} else {
			$this->remove_dashboard_order_language_cookie();
		}
	}

	public function order_delete_items() {
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'set_dashboard_order_language' ) ) {
			echo json_encode( [ 'error' => __( 'Invalid nonce', 'woocommerce-multilingual' ) ] );
			die();
		}

		$this->set_dashboard_order_language_cookie( filter_input( INPUT_POST, 'lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
	}

	private function set_dashboard_order_language_cookie( $language ) {
		setcookie( self::DASHBOARD_COOKIE_NAME, $language, time() + self::COOKIE_TTL, COOKIEPATH, COOKIE_DOMAIN );
	}

	private function remove_dashboard_order_language_cookie() {
		setcookie( self::DASHBOARD_COOKIE_NAME, '', time() - self::COOKIE_TTL, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * @return void
	 */
	private function maybe_set_dashboard_cookie() {
		if ( ! isset( $_COOKIE [ self::DASHBOARD_COOKIE_NAME ] ) && OrdersHelper::isOrderCreateAdminScreen() ) {
			$this->set_dashboard_order_language_cookie( $this->sitepress->get_default_language() );
		}
	}

	/**
	 * @param int $orderId
	 *
	 * @return void
	 */
	public function set_order_language_backend( $orderId ) {
		if ( isset( $_POST['wcml_shop_order_language'] ) ) {
			self::setLanguage( $orderId, filter_input( INPUT_POST, 'wcml_shop_order_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		}
	}

	/**
	 * @param array                  $files
	 * @param \WC_Order_Item_Product $item
	 * @param \WC_Order|false        $object
	 *
	 * @return array
	 */
	public function filter_downloadable_product_items( $files, $item, $object ) {
		if ( ! $object ) {
			return $files;
		}

		$order_language = self::getLanguage( $object->get_id() );

		if ( $item->get_variation_id() > 0 ) {
			$translated_variation_id = apply_filters( 'wpml_object_id', $item->get_variation_id(), 'product_variation', false, $order_language );
			if ( ! is_null( $translated_variation_id ) ) {
				$item->set_variation_id( $translated_variation_id );
			}
		} else {
			$translated_product_id = apply_filters( 'wpml_object_id', $item->get_product_id(), 'product', false, $order_language );
			if ( ! is_null( $translated_product_id ) ) {
				$item->set_product_id( $translated_product_id );
			}
		}

		remove_filter( 'woocommerce_get_item_downloads', [ $this, 'filter_downloadable_product_items' ], 10 );

		$files = $item->get_item_downloads();

		add_filter( 'woocommerce_get_item_downloads', [ $this, 'filter_downloadable_product_items' ], 10, 3 );

		return $files;
	}

	public function filter_customer_get_downloadable_products( $downloads ) {

		foreach ( $downloads as $key => $download ) {

			$translated_id = apply_filters( 'wpml_object_id', $download['product_id'], get_post_type( $download['product_id'] ), false, $this->sitepress->get_current_language() );

			if ( $translated_id ) {
				$downloads[ $key ]['product_name'] = get_the_title( $translated_id );
			}
		}

		return $downloads;
	}

	/**
	 * Curried function to get the order language.
	 *
	 * @param int|null $orderId
	 *
	 * @return callable|string|false
	 */
	public static function getLanguage( $orderId = null ) {
		$getLanguage = function( $orderId ) {
			/**
			 * Allow adjusting the order ID before fetching the language.
			 *
			 * @since 5.3
			 *
			 * @param int $orderId
			 */
			$orderId = apply_filters( 'wcml_order_id_for_language', $orderId );

			return Maybe::fromNullable( \wc_get_order( $orderId ) )
				->map( invoke( 'get_meta' )->with( self::KEY_LANGUAGE ) )
				->getOrElse( false );
		};

		return call_user_func_array( curryN( 1, $getLanguage ), func_get_args() );
	}

	/**
	 * @param int    $orderId
	 * @param string $language
	 *
	 * @return void
	 */
	public static function setLanguage( $orderId, $language ) {
		Maybe::fromNullable( \wc_get_order( $orderId ) )
			->map( Fns::tap( invoke( 'update_meta_data' )->with( self::KEY_LANGUAGE, $language ) ) )
			->map( invoke( 'save' ) );
	}
}
