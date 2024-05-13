<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Woo extends Base {
	public static function load_me() {
		return class_exists( 'woocommerce' );
	}

	public function register_tags() {
		$tags = $this->get_tags_config();

		foreach ( $tags as $key => $tag ) {
			$this->tags[ $key ] = [
				'name'     => '{' . $key . '}',
				'label'    => $tag['label'],
				'group'    => $tag['group'],
				'provider' => $this->name,
			];

			if ( ! empty( $tag['render'] ) ) {
				$this->tags[ $key ]['render'] = $tag['render'];
			}
		}
	}

	public function get_tags_config() {
		$tags = [
			// Product
			'woo_product_price'         => [
				'label' => esc_html__( 'Product price', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_product_regular_price' => [
				'label' => esc_html__( 'Product regular price', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_product_sale_price'    => [
				'label' => esc_html__( 'Product sale price', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_product_excerpt'       => [
				'label' => esc_html__( 'Product short description', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_product_stock'         => [
				'label' => esc_html__( 'Product stock', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_product_sku'           => [
				'label' => esc_html__( 'Product SKU', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_product_rating'        => [
				'label' => esc_html__( 'Product rating', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_product_on_sale'       => [
				'label' => esc_html__( 'Product on sale', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_add_to_cart'           => [
				'label' => esc_html__( 'Add to cart', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_product_cat_image'     => [
				'label' => esc_html__( 'Product category image', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'woo_product_stock_status'  => [
				'label' => esc_html__( 'Product stock status', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],

			// Cart
			'woo_cart_product_name'     => [
				'label' => esc_html__( 'Cart product name', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'woo_cart_remove_link'      => [
				'label' => esc_html__( 'Cart remove product', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'woo_cart_quantity'         => [
				'label' => esc_html__( 'Cart product quantity', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'woo_cart_subtotal'         => [
				'label' => esc_html__( 'Cart product subtotal', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'woo_cart_update'           => [
				'label' => esc_html__( 'Cart update', 'bricks' ),
				'group' => 'WooCommerce',
			],

			// Checkout Order
			'woo_order_id'              => [
				'label' => esc_html__( 'Order id', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'woo_order_number'          => [
				'label' => esc_html__( 'Order number', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'woo_order_date'            => [
				'label' => esc_html__( 'Order date', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'woo_order_total'           => [
				'label' => esc_html__( 'Order total', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'woo_order_payment_title'   => [
				'label' => esc_html__( 'Order payment method', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'woo_order_email'           => [
				'label' => esc_html__( 'Order email', 'bricks' ),
				'group' => 'WooCommerce',
			],

			// Woo Phase 3
			// NOTE: Not in use
			// 'woo_my_account_endpoint'  => [
			// 'label' => esc_html__( 'My account endpoint', 'bricks' ),
			// 'group' => 'WooCommerce',
			// ],
		];

		return $tags;
	}

	/**
	 * Main function to render the tag value for WooCommerce provider
	 *
	 * @param [type] $tag
	 * @param [type] $post
	 * @param [type] $args
	 * @param [type] $context
	 */
	public function get_tag_value( $tag, $post, $args, $context ) {
		$post_id = isset( $post->ID ) ? $post->ID : '';
		$product = $post_id ? wc_get_product( $post_id ) : false;

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// STEP: Get the value
		$value = '';

		$render = isset( $this->tags[ $tag ]['render'] ) ? $this->tags[ $tag ]['render'] : str_replace( 'woo_', '', $tag );

		switch ( $render ) {
			case 'product_price':
				$loop_object_type = \Bricks\Query::is_looping() ? \Bricks\Query::get_query_object_type() : false;

				// Is inside of a cart loop (@since 1.5.3)
				if ( $loop_object_type === 'wooCart' ) {
					$loop_object   = \Bricks\Query::get_loop_object();
					$cart_item_key = isset( $loop_object['key'] ) ? $loop_object['key'] : false;
					$_product      = isset( $loop_object['data'] ) ? $loop_object['data'] : $product;

					$value = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $loop_object, $cart_item_key );
				}

				// default
				else {

					// Support ':value' filter to get the price value as a simple string (e.g.: 65.3, 2.5, 5 )
					if ( isset( $filters['value'] ) ) {
						$value = $product ? $product->get_price() : '';
					} else {
						$value = $product ? $product->get_price_html() : '';
					}

				}
				break;

			/**
			 *  Regular price - By default, output as html via wc_price.
			 *  Not for variable products
			 *  Use :plain filter to get the value without html (included symbol)
			 *  Use :value filter to get the value as a simple string (e.g.: 65.3, 2.5, 5 )
			 *
			 *  @since 1.8.4
			 */
			case 'product_regular_price':
				$value = $product ? $product->get_regular_price() : '';

				// default
				if ( ! isset( $filters['value'] ) ) {
					$value = $value ? wc_price( $value ) : '';
				}
				break;

			/**
			 *  Sale price - By default, output as html via wc_price.
			 *  Not for variable products, if the product has no sale price, empty string will be returned
			 *  Use :plain filter to get the value without html (included symbol)
			 *  Use :value filter to get the value as a simple string (e.g.: 65.3, 2.5, 5 )
			 *
			 *  @since 1.8.4
			 */
			case 'product_sale_price':
				$value = $product ? $product->get_sale_price() : '';

				// default
				if ( ! isset( $filters['value'] ) ) {
					$value = $value ? wc_price( $value ) : '';
				}
				break;

			case 'product_excerpt':
				// Product excerpt should keep HTML tags in dynamic data (@since 1.6)
				$keep_html = true;

				// @since 1.6.2 - To prevent the content from being trimmed again in format_value_for_text()
				$filters['trimmed'] = true;

				$value = \Bricks\Helpers::get_the_excerpt( $post, ! empty( $filters['num_words'] ) ? $filters['num_words'] : 55, null, $keep_html );
				$value = apply_filters( 'woocommerce_short_description', $value );
				break;

			case 'product_stock':
				if ( isset( $filters['value'] ) ) {
					// Return stock value only if value filter is set
					$value = $product ? $this->get_stock_amount( $product ) : 0;
				} else {
					$value = $product ? $this->get_stock_html( $product ) : '';
				}
				break;

			case 'product_sku':
				$value = $product && wc_product_sku_enabled() && $product->get_sku() ? $product->get_sku() : '';

				// Wrap with class "sku" so that the Woo fragments mechanism updates the SKU for the variable products
				// Apply filter ':value' to output as plain text (#37der3x) as ':raw' is not working.
				if ( ! isset( $filters['value'] ) ) {
					$value = "<span class=\"sku\">{$value}</span>";
				}
				break;

			case 'product_rating':
				if ( $product && wc_review_ratings_enabled() ) {
					if ( isset( $filters['value'] ) ) {
						$average = $product->get_average_rating();

						// Support ':value' filter to get the rating value as a simple string (e.g.: 0, 2.50, 5.00)
						$value = $average;
					} else {
						/**
						 * Use Brick's render_product_rating()
						 *
						 * Support ':format' filter to show empty stars even if the product has no rating
						 *
						 * @since 1.8
						 */
						$params = [
							'wrapper'           => false,
							'hide_reviews_link' => true,
							'show_empty_stars'  => isset( $filters['format'] ),
						];
						$value  = \Bricks\Woocommerce_Helpers::render_product_rating( $product, $params, false );
					}
				}
				break;

			case 'product_on_sale':
				$value = $product && $product->is_on_sale() ? apply_filters( 'woocommerce_sale_flash', '<span class="badge onsale">' . esc_html__( 'Sale!', 'bricks' ) . '</span>', $post, $product ) : '';
				break;

			case 'add_to_cart':
				/**
				 * Skip sanitize for add to cart button
				 *
				 * As user might add more HTML tags via woocommerce_loop_add_to_cart_link filter (only affects text context).
				 *
				 * @since 1.6.2
				 */
				$filters['skip_sanitize'] = true;

				$value = $this->get_add_to_cart_value( $product, $filters, $context );
				break;

			case 'product_cat_image':
				$filters['object_type'] = 'media';
				$filters['image']       = 'true';

				// Loop
				if ( \Bricks\Query::is_looping() && \Bricks\Query::get_loop_object_type() == 'term' ) {
					$term_id = \Bricks\Query::get_loop_object_id();
				}

				// Template preview
				elseif ( \Bricks\Helpers::is_bricks_template( $post_id ) ) {
					$template_preview_type = \Bricks\Helpers::get_template_setting( 'templatePreviewType', $post_id );

					if ( 'archive-term' === $template_preview_type ) {
						$template_preview_term          = \Bricks\Helpers::get_template_setting( 'templatePreviewTerm', $post_id );
						$template_preview_term_id_parts = ! empty( $template_preview_term ) ? explode( '::', $template_preview_term ) : '';

						$term_id = isset( $template_preview_term_id_parts[1] ) ? $template_preview_term_id_parts[1] : '';
					}
				}

				// Product Cat archive
				elseif ( is_tax( 'product_cat' ) ) {
					$queried_object = get_queried_object();
					$term_id        = isset( $queried_object->term_id ) ? $queried_object->term_id : '';
				}

				// Single product
				elseif ( is_singular( 'product' ) ) {
					$terms   = wp_get_post_terms( $post_id, 'product_cat' );
					$term_id = isset( $terms[0]->term_id ) ? $terms[0]->term_id : 0;
				}

				$value = ! empty( $term_id ) ? get_term_meta( $term_id, 'thumbnail_id', true ) : '';
				break;

			// Expected result: 'instock', 'outofstock', 'onbackorder' (@since 1.6.1)
			case 'product_stock_status':
				$value = $product ? $product->get_stock_status() : '';
				break;

			// Cart (@since 1.5.3)
			// @see /wp-content/plugins/woocommerce/templates/cart/cart.php
			case 'cart_product_name':
				$value = '';

				$loop_object_type = \Bricks\Query::is_looping() ? \Bricks\Query::get_query_object_type() : false;

				if ( $loop_object_type === 'wooCart' ) {
					$filters['skip_sanitize'] = true;

					$loop_object   = \Bricks\Query::get_loop_object();
					$cart_item_key = isset( $loop_object['key'] ) ? $loop_object['key'] : false;
					$_product      = isset( $loop_object['data'] ) ? $loop_object['data'] : $product;
					$_product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $loop_object, $cart_item_key );

					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $loop_object ) : '', $loop_object, $cart_item_key );

					if ( ! $product_permalink ) {
						$value = wp_kses_post( $_product_name . '&nbsp;' );
					} else {
						$value = wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $loop_object, $cart_item_key ) );
					}

					ob_start();
					do_action( 'woocommerce_after_cart_item_name', $loop_object, $cart_item_key );
					$value .= ob_get_clean();

					// Meta data.
					$value .= wc_get_formatted_cart_item_data( $loop_object );

					// Backorder notification.
					if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $loop_object['quantity'] ) ) {
						$value .= wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $_product->get_id() ) );
					}
				}

				break;

			case 'cart_remove_link':
				$value = '';

				$loop_object_type = \Bricks\Query::is_looping() ? \Bricks\Query::get_query_object_type() : false;

				// Is inside of a cart loop
				if ( $loop_object_type === 'wooCart' ) {
					$filters['skip_sanitize'] = true;
					$loop_object              = \Bricks\Query::is_looping() ? \Bricks\Query::get_loop_object() : false;

					$cart_item_key = isset( $loop_object['key'] ) ? $loop_object['key'] : false;
					$_product      = isset( $loop_object['data'] ) ? $loop_object['data'] : $product;

					// @since 1.8.1 - WooCommerce 7.8 compatibility
					$_product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $loop_object, $cart_item_key );

					$value = $_product && $cart_item_key ? apply_filters(
						'woocommerce_cart_item_remove_link',
						sprintf(
							// translators: %s Product name.
							'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
							esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
							// translators: %s Product name.
							esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $_product_name ) ) ),
							esc_attr( $_product->get_id() ),
							esc_attr( $_product->get_sku() )
						),
						$cart_item_key
					) : '';
				}

				break;

			case 'cart_quantity':
				$value = '';

				$loop_object_type = \Bricks\Query::is_looping() ? \Bricks\Query::get_query_object_type() : false;

				// Is inside of a cart loop
				if ( $loop_object_type === 'wooCart' ) {
					$filters['skip_sanitize'] = true;

					$loop_object   = \Bricks\Query::get_loop_object();
					$cart_item_key = isset( $loop_object['key'] ) ? $loop_object['key'] : false;
					$_product      = isset( $loop_object['data'] ) ? $loop_object['data'] : $product;

					// @since 1.8.1 - WooCommerce 7.8 compatibility
					$_product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $loop_object, $cart_item_key );

					if ( $_product->is_sold_individually() ) {
						$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
					} else {
						$product_quantity = woocommerce_quantity_input(
							[
								'input_name'   => "cart[{$cart_item_key}][qty]",
								'input_value'  => $loop_object['quantity'],
								'max_value'    => $_product->get_max_purchase_quantity(),
								'min_value'    => '0',
								'product_name' => $_product_name,
							],
							$_product,
							false
						);
					}

					$value = apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $loop_object );
					$value = "<div class=\"product-quantity\">{$value}</div>";
				}
				break;

			case 'cart_subtotal':
				$value = '';

				$loop_object_type = \Bricks\Query::is_looping() ? \Bricks\Query::get_query_object_type() : false;

				if ( $loop_object_type === 'wooCart' ) {
					$filters['skip_sanitize'] = true;
					$loop_object              = \Bricks\Query::get_loop_object();
					$cart_item_key            = isset( $loop_object['key'] ) ? $loop_object['key'] : false;
					$_product                 = isset( $loop_object['data'] ) ? $loop_object['data'] : $product;

					$value = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $loop_object['quantity'] ), $loop_object, $cart_item_key );
				}
				break;

			case 'cart_update':
				$filters['skip_sanitize'] = true;

				$value = '<button type="submit" class="button" name="update_cart" value="' . esc_attr__( 'Update cart', 'woocommerce' ) . '">' . esc_html__( 'Update cart', 'woocommerce' ) . '</button>';

				// @see https://developer.wordpress.org/reference/functions/wp_nonce_field/
				$value .= wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce', true, false );
				break;

			// Checkout order
			case 'order_id':
				$order = $this->get_order();
				$value = $order ? $order->get_id() : '';
				break;

			case 'order_number':
				$order = $this->get_order();
				$value = $order ? $order->get_order_number() : '';
				break;

			case 'order_date':
				$filters['object_type'] = 'date';

				$order = $this->get_order();
				$value = $order ? wc_format_datetime( $order->get_date_created(), 'U' ) : '';
				break;

			case 'order_total':
				$order = $this->get_order();
				$value = $order ? $order->get_formatted_order_total() : '';
				break;

			case 'order_payment_title':
				$order = $this->get_order();
				$value = $order ? $order->get_payment_method_title() : '';
				break;

			case 'order_email':
				$order = $this->get_order();
				$value = $order ? $order->get_billing_email() : '';
				break;

			/**
			 * Woo Phase 3 - default return endpoint Label, support :url
			 *
			 * Endpoints: dashboard, orders, downloads, edit-address, edit-account, customer-logout
			 *
			 * NOTE: Not in use!
			 */
			// case 'my_account_endpoint':
			// $filters['skip_sanitize'] = true;
			// $is_url = isset( $filters['url'] ) ? $filters['url'] : false;
			// $endpoint_from_user = isset( $filters['meta_key'] ) ? $filters['meta_key'] : false;
			// $endpoint = false;

			// if ( $endpoint_from_user ) {
			// User entered account endpoint such as {woo_my_account_endpoint:dashboard}
			// $endpoints = wc_get_account_menu_items();

			// Search the endpoint from the array key
			// $find_endpoint = array_filter( $endpoints, function( $key ) use ( $endpoint_from_user ) {
			// return $key === $endpoint_from_user;
			// }, ARRAY_FILTER_USE_KEY );

			// Once found, pick the endpoint as array with key and value
			// if ( count( $find_endpoint ) === 1 ) {
			// $endpoint['endpoint'] = $endpoint_from_user;
			// $endpoint['label']    = array_values( $find_endpoint )[0];
			// }
			// }

			// if ( ! $endpoint ) {
			// return '';
			// }

			// $value = $is_url ? esc_url( wc_get_account_endpoint_url( $endpoint['endpoint'] ) ) : esc_html( $endpoint['label'] );
			// break;
		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	public function get_order() {
		$order_id  = 0;
		$order     = false;
		$order_key = false;

		// Order pay
		if ( ! empty( get_query_var( 'order-pay' ) ) ) {
			$order_id  = absint( get_query_var( 'order-pay' ) );
			$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		}

		// Order received
		elseif ( ! empty( get_query_var( 'order-received' ) ) ) {
			$order_id = absint( get_query_var( 'order-received' ) );

			$order_id  = apply_filters( 'woocommerce_thankyou_order_id', $order_id );
			$order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : wc_clean( wp_unslash( $_GET['key'] ) ) );
		}

		// View order (my-account) (@since 1.9.6)
		elseif ( ! empty( get_query_var( 'view-order' ) ) ) {
			$order_id = absint( get_query_var( 'view-order' ) );
		}

		if ( $order_id > 0 ) {
			$order = wc_get_order( $order_id );

			// 'view-order' endpoint already checks the order key, so we don't need to check it again (@since 1.9.6)
			if ( ! is_wc_endpoint_url( 'view-order' ) && ( ! $order || ! hash_equals( $order->get_order_key(), $order_key ) ) ) {
				$order = false;
			}
		}

		return $order;
	}

	/**
	 * Same function as in WooCommerce wc_get_stock_html() but with the last resort calculation for variable products when the stock is managed at the variation level
	 *
	 * @since 1.5.7
	 */
	public function get_stock_html( $product ) {
		$html         = '';
		$availability = $product->get_availability();

		// Get all the product variations and sum up the stocks if needed - stock is managed in the variation level (@since 1.5.7)
		if ( empty( $availability['availability'] ) && $product->is_type( 'variable' ) ) {
			$stock_amount = $this->get_stock_amount( $product );

			$availability['availability'] = $this->format_stock_for_display( $product, $stock_amount );
		}

		if ( ! empty( $availability['availability'] ) ) {
			ob_start();

			wc_get_template(
				'single-product/stock.php',
				[
					'product'      => $product,
					'class'        => $availability['class'],
					'availability' => $availability['availability'],
				]
			);

			$html = ob_get_clean();
		}

		return apply_filters( 'woocommerce_get_stock_html', $html, $product );
	}

	/**
	 * Similar function to wc_format_stock_for_display but adapted to be possible to use the stock sum up of the product variations
	 *
	 * @since 1.5.7
	 */
	public function format_stock_for_display( $product, $stock_amount ) {
		$display = __( 'In stock', 'woocommerce' );

		switch ( get_option( 'woocommerce_stock_format' ) ) {
			case 'low_amount':
				if ( $stock_amount <= wc_get_low_stock_amount( $product ) ) {
					/* translators: %s: stock amount */
					$display = sprintf( __( 'Only %s left in stock', 'woocommerce' ), wc_format_stock_quantity_for_display( $stock_amount, $product ) );
				}
				break;
			case '':
				/* translators: %s: stock amount */
				$display = sprintf( __( '%s in stock', 'woocommerce' ), wc_format_stock_quantity_for_display( $stock_amount, $product ) );
				break;
		}

		if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
			$display .= ' ' . __( '(can be backordered)', 'woocommerce' );
		}

		return $display;
	}

	/**
	 * Get product stock amount value
	 *
	 * Previously in get_stock_html(), but refactored into a separate function for reusability and readability.
	 * Bare in mind if the product is not managed stock, the value will be 0 even stock status is instock.
	 *
	 * @param \WC_Product $product
	 * @return int
	 *
	 * @since 1.6.1
	 */
	public function get_stock_amount( $product ) {
		$stock_amount = $product->get_stock_quantity();

		if ( $product->is_type( 'variable' ) ) {
			$variations = $product->get_available_variations();

			foreach ( $variations as $variation ) {
				if ( empty( $variation['is_in_stock'] ) ) {
					continue;
				}

				$variation_obj = new \WC_Product_variation( $variation['variation_id'] );

				$stock_amount += $variation_obj->get_stock_quantity();
			}
		}

		return (int) $stock_amount; // @since 1.9.1 - Possible to return negative value when using backorders (#861n84vua)
	}

	/**
	 * Get the "Add to cart" button html
	 *
	 * @param WP_Product $product
	 * @param array      $filters
	 */
	public function get_add_to_cart_value( $product, $filters, $context ) {
		if ( ! $product ) {
			return '';
		}

		if ( $context == 'link' ) {
			return $product->add_to_cart_url();
		}

		$button_args = [];

		// @see woocommerce_template_loop_add_to_cart()
		$defaults = [
			'quantity'   => 1,
			'class'      => implode(
				' ',
				array_filter(
					[
						'button',
						'product_type_' . $product->get_type(),
						$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
						$product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
					]
				)
			),
			'attributes' => [
				'data-product_id'  => $product->get_id(),
				'data-product_sku' => $product->get_sku(),
				'aria-label'       => $product->add_to_cart_description(),
				'rel'              => 'nofollow',
			],
		];

		$button_args = apply_filters( 'woocommerce_loop_add_to_cart_args', wp_parse_args( $button_args, $defaults ), $product );

		if ( isset( $button_args['attributes']['aria-label'] ) ) {
			$button_args['attributes']['aria-label'] = wp_strip_all_tags( $button_args['attributes']['aria-label'] );
		}

		return apply_filters(
			'woocommerce_loop_add_to_cart_link',
			sprintf(
				'<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
				esc_url( $product->add_to_cart_url() ),
				esc_attr( isset( $button_args['quantity'] ) ? $button_args['quantity'] : 1 ),
				esc_attr( isset( $button_args['class'] ) ? $button_args['class'] : 'button' ),
				isset( $button_args['attributes'] ) ? wc_implode_html_attributes( $button_args['attributes'] ) : '',
				esc_html( $product->add_to_cart_text() )
			),
			$product,
			$button_args
		);
	}
}
