<?php

use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use function WPML\FP\curryN;
use function WPML\FP\partialRight;

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

		add_filter( 'the_comments', [ $this, 'get_filtered_comments' ] );

		add_filter( 'woocommerce_order_get_items', [ $this, 'woocommerce_order_get_items' ], 10, 2 );

		add_action( 'woocommerce_process_shop_order_meta', [ $this, 'set_order_language_backend' ], 10, 2 );
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
			10,
			3
		);

		if ( is_admin() ) {
			$this->maybe_set_dashboard_cookie();
		}
	}

	/**
	 * This method will try to convert the comments in the current language
	 * if the user is identified (i.e. he has an ID).
	 *
	 * Note: I was not able to find the place where the strings are
	 * registered and maybe this code is not used anymore. This should
	 * be investigated in the future.
	 *
	 * @param \WP_Comment[] $comments
	 *
	 * @return \WP_Comment[]
	 */
	public function get_filtered_comments( $comments ) {
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

	/**
	 * @param WC_Order_Item[] $items
	 * @param WC_Order        $order
	 *
	 * @return WC_Order_Item[]
	 */
	public function woocommerce_order_get_items( $items, $order ) {

		$translate_order_items = is_admin() || is_view_order_page() || is_order_received_page() || \WCML\Rest\Functions::isRestApiRequest();
		/**
		 * This filter hook allows to override if we need to translate order items.
		 *
		 * @since 4.11.0
		 *
		 * @param bool            $translate_order_items True if we should to translate order items.
		 * @param WC_Order_Item[] $items                 Order items.
		 * @param WC_Order        $order                 WC Order.
		 */
		$translate_order_items = apply_filters( 'wcml_should_translate_order_items', $translate_order_items, $items, $order );

		if ( $items && $translate_order_items ) {

			$language_to_filter = $this->get_order_items_language_to_filter( $order );

			$this->adjust_order_item_in_language( $items, $language_to_filter );
		}

		return $items;
	}

	/**
	 * @param array       $items
	 * @param string|bool $language_to_filter
	 */
	public function adjust_order_item_in_language( $items, $language_to_filter = false ) {

		if ( ! $language_to_filter ) {
			$language_to_filter = $this->sitepress->get_current_language();
		}

		foreach ( $items as $index => $item ) {

			/**
			 * This filter hook allows to override if we need to save adjusted order item.
			 *
			 * @since 4.11.0
			 *
			 * @param bool          $true               True if we should save adjusted order item.
			 * @param WC_Order_Item $item
			 * @param string        $language_to_filter Language to filter.
			 */
			$save_adjusted_item = apply_filters( 'wcml_should_save_adjusted_order_item_in_language', true, $item, $language_to_filter );

			if ( $item instanceof WC_Order_Item_Product ) {
				if ( 'line_item' === $item->get_type() ) {
					$item_was_adjusted = $this->adjust_product_item_if_translated( $item, $language_to_filter );
					if ( $item->get_variation_id() ) {
						$item_was_adjusted = $this->adjust_variation_item_if_translated( $item, $language_to_filter );
					}
					if ( $item_was_adjusted && $save_adjusted_item ) {
						$item->save();
					}
				}
			} elseif ( $item instanceof WC_Order_Item_Shipping ) {
				$shipping_id = $item->get_method_id();
				if ( $shipping_id ) {

					if ( method_exists( $item, 'get_instance_id' ) ) {
						$shipping_id .= $item->get_instance_id();
					}

					$item->set_method_title(
						$this->woocommerce_wpml->shipping->translate_shipping_method_title(
							$item->get_method_title(),
							$shipping_id,
							$language_to_filter
						)
					);

					if ( $save_adjusted_item ) {
						$item->save();
					}
				}
			}
		}
	}

	/**
	 * @param WC_Order_Item_Product $item
	 * @param string                $language_to_filter
	 *
	 * @return bool
	 */
	private function adjust_product_item_if_translated( $item, $language_to_filter ) {

		$product_id            = $item->get_product_id();
		$translated_product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, $language_to_filter );
		if ( $product_id && $product_id !== $translated_product_id ) {
			$item->set_product_id( $translated_product_id );
			$item->set_name( get_post( $translated_product_id )->post_title );
			return true;
		}

		return false;
	}

	/**
	 * @param WC_Order_Item_Product $item
	 * @param string                $language_to_filter
	 *
	 * @return bool
	 */
	private function adjust_variation_item_if_translated( $item, $language_to_filter ) {

		$variation_id            = $item->get_variation_id();
		$translated_variation_id = apply_filters( 'translate_object_id', $variation_id, 'product_variation', true, $language_to_filter );
		if ( $variation_id && $variation_id !== $translated_variation_id ) {
			$item->set_variation_id( $translated_variation_id );
			$item->set_name( wc_get_product( $translated_variation_id )->get_name() );
			$this->update_attribute_item_meta_value( $item, $translated_variation_id );
			return true;
		}

		return false;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_order_items_language_to_filter( $order ) {

		if ( $this->is_on_order_edit_page() ) {
			$language = $this->sitepress->get_user_admin_language( get_current_user_id(), true );
		} elseif ( $this->is_order_action_triggered_for_customer() ) {
			$language = self::getLanguage( $order->get_id() ) ?: $this->sitepress->get_default_language();
		} else {
			$language = $this->sitepress->get_current_language();
		}

		/**
		 * This filter hook allows to override item language to filter.
		 *
		 * @since 4.11.0
		 *
		 * @param string   $language Order item language to filter.
		 * @param WC_Order $order
		 */
		return apply_filters( 'wcml_get_order_items_language', $language, $order );
	}

	/**
	 * @return bool
	 */
	private function is_on_order_edit_page() {
		return isset( $_GET['post'] ) && 'shop_order' === get_post_type( $_GET['post'] );
	}

	/**
	 * @return bool
	 */
	private function is_order_action_triggered_for_customer() {
		return isset( $_GET['action'] ) && wpml_collect(
			[ 'woocommerce_mark_order_complete', 'woocommerce_mark_order_status', 'mark_processing' ]
		)->contains( $_GET['action'] );
	}

	/**
	 * @param WC_Order_Item_Product $item
	 * @param int                   $variation_id
	 */
	private function update_attribute_item_meta_value( $item, $variation_id ) {
		foreach ( $item->get_meta_data() as $meta_data ) {
			$data            = $meta_data->get_data();
			$attribute_value = get_post_meta( $variation_id, 'attribute_' . $data['key'], true );
			if ( $attribute_value ) {
				$item->update_meta_data( $data['key'], $attribute_value, isset( $data['id'] ) ? $data['id'] : 0 );
			}
		}
	}

	public function backend_before_order_itemmeta( $item_id, $item, $product ) {
		global $sitepress;

		if ( $this->get_order_language_by_item_id( $item_id ) != $sitepress->get_user_admin_language( get_current_user_id(), true ) ) {
			foreach ( $item['item_meta'] as $key => $item_meta ) {
				if ( taxonomy_exists( wc_attribute_taxonomy_name( $key ) ) || substr( $key, 0, 3 ) === 'pa_' ) {
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
				if ( taxonomy_exists( wc_attribute_taxonomy_name( $key ) ) || substr( $key, 0, 3 ) === 'pa_' ) {
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

		$taxonomy        = substr( $key, 0, 3 ) !== 'pa_' ? wc_attribute_taxonomy_name( $key ) : $key;
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
			update_post_meta( $order_id, self::KEY_LANGUAGE, ICL_LANGUAGE_CODE );
		}
	}

	/**
	 * @param WC_Abstract_Order $order
	 */
	public function setOrderLanguageBeforeSave( $order ) {
		if (
			$order->get_status() !== 'checkout-draft'
			&& ! $order->get_meta( self::KEY_LANGUAGE )
		) {
			$order->add_meta_data( self::KEY_LANGUAGE, $this->sitepress->get_current_language(), true );
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
		if ( ! get_post_meta( $order_id, '_order_currency' ) ) {
			$languages     = apply_filters(
				'wpml_active_languages',
				[],
				[
					'skip_missing' => 0,
					'orderby'      => 'code',
				]
			);
			$selected_lang = isset( $_COOKIE [ self::DASHBOARD_COOKIE_NAME ] ) ? $_COOKIE [ self::DASHBOARD_COOKIE_NAME ] : $this->sitepress->get_default_language();
			?>
			<li class="wide">
				<label><?php _e( 'Order language:' ); ?></label>
				<select id="dropdown_shop_order_language" name="wcml_shop_order_language">
					<?php if ( ! empty( $languages ) ) : ?>

						<?php foreach ( $languages as $l ) : ?>

							<option
									value="<?php echo $l['language_code']; ?>" <?php echo $selected_lang == $l['language_code'] ? 'selected="selected"' : ''; ?>><?php echo $l['translated_name']; ?></option>

						<?php endforeach; ?>

					<?php endif; ?>
				</select>
			</li>
			<?php
			$wcml_set_dashboard_order_language_nonce = wp_create_nonce( 'set_dashboard_order_language' );
			wc_enqueue_js(
				"
                 var order_lang_current_value = jQuery('#dropdown_shop_order_language option:selected').val();

                 jQuery('#dropdown_shop_order_language').on('change', function(){
                    if(confirm('" . esc_js( __( 'All the products will be removed from the current order in order to change the language', 'woocommerce-multilingual' ) ) . "')){
                        var lang = jQuery(this).val();

                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'post',
                            dataType: 'json',
                            data: {action: 'wcml_order_delete_items', order_id: woocommerce_admin_meta_boxes.post_id, lang: lang , wcml_nonce: '" . $wcml_set_dashboard_order_language_nonce . "' },
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

            "
			);
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

	private function maybe_set_dashboard_cookie() {
		global $pagenow;

		if (
			! isset( $_COOKIE [ self::DASHBOARD_COOKIE_NAME ] ) &&
			( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'] )
		) {
			$this->set_dashboard_order_language_cookie( $this->sitepress->get_default_language() );
		}
	}

	public function set_order_language_backend( $post_id, $post ) {

		if ( isset( $_POST['wcml_shop_order_language'] ) ) {
			update_post_meta( $post_id, self::KEY_LANGUAGE, filter_input( INPUT_POST, 'wcml_shop_order_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		}
	}

	public function filter_downloadable_product_items( $files, $item, $object ) {

		$order_language = self::getLanguage( $object->get_id() );

		if ( $item->get_variation_id() > 0 ) {
			$translated_variation_id = apply_filters( 'translate_object_id', $item->get_variation_id(), 'product_variation', false, $order_language );
			if ( ! is_null( $translated_variation_id ) ) {
				$item->set_variation_id( $translated_variation_id );
			}
		} else {
			$translated_product_id = apply_filters( 'translate_object_id', $item->get_product_id(), 'product', false, $order_language );
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

			$translated_id = apply_filters( 'translate_object_id', $download['product_id'], get_post_type( $download['product_id'] ), false, $this->sitepress->get_current_language() );

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
		return call_user_func_array( curryN( 1, partialRight( 'get_post_meta', self::KEY_LANGUAGE, true ) ), func_get_args() );
	}
}
