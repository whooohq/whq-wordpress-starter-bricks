<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce {
	public static $product_categories = [];
	public static $product_tags       = [];
	public static $is_active          = false;

	public function __construct() {
		self::$is_active = self::is_woocommerce_active();

		if ( ! self::$is_active ) {
			// Make sure the WooCommerce templates are not loaded, in case CPT "product" is used
			add_filter( 'template_include', [ $this, 'no_woo_template_include' ], 1001 );

			return;
		}

		add_action( 'after_setup_theme', [ $this, 'add_theme_support' ] );

		add_action( 'init', [ $this, 'set_products_terms' ] );

		add_action( 'init', [ $this, 'init_elements' ] );

		add_action( 'init', [ $this, 'init_theme_styles' ], 9 );

		add_action( 'wp', [ $this, 'maybe_set_template_preview_content' ], 9 );

		// Disable default bricks title for WooCommerce pages if template is active (@since 1.8)
		add_filter( 'bricks/default_page_title', [ $this, 'default_page_title' ], 10, 2 );

		add_filter( 'bricks/element/maybe_set_aria_current_page', [ $this, 'maybe_set_aria_current_page' ], 10, 2 );

		add_filter( 'bricks/builder/supported_post_types', [ $this, 'bypass_builder_post_type_check' ], 10, 2 );

		// On the builder hook to set the panel elements first element category
		add_filter( 'bricks/builder/first_element_category', [ $this, 'set_first_element_category' ], 10, 3 );

		// Builder/Database: set the post id used to localize the builder data -> is_shop() page
		add_filter( 'bricks/builder/data_post_id', [ $this, 'maybe_set_post_id' ], 10, 1 );

		// Add Template Types to control options
		add_filter( 'bricks/setup/control_options', [ $this, 'add_template_types' ] );

		// Remove the template conditions for the Cart & Checkout template parts
		add_filter( 'builder/settings/template/controls_data', [ $this, 'remove_template_conditions' ], 9 );

		// During the active_templates search set proper content_type
		add_filter( 'bricks/database/content_type', [ $this, 'set_content_type' ], 10, 2 );

		// Remove default WooCommerce styles
		add_filter( 'woocommerce_enqueue_styles', '__return_false' );

		// Add WooCommerce specific link selectors to allow Theme Styles link styles to apply to WooCommerce elements (@since 1.5.7)
		add_filter( 'bricks/link_css_selectors', [ $this, 'link_css_selectors' ], 10, 1 );

		// Enqueue Bricks WooCommerce custom styles
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 10 );

		// add_action( 'wp_enqueue_scripts', [ $this, 'unload_photoswipe5_lightbox_assets' ] );

		// Product archive hooks
		add_action( 'bricks/archive_product/before', [ $this, 'setup_query' ], 10, 2 );
		add_action( 'bricks/archive_product/after', [ $this, 'reset_query' ], 10, 2 );

		// Mini cart fragments
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'update_mini_cart' ], 10, 1 );

		// Breadcrumb separator
		add_filter( 'woocommerce_breadcrumb_defaults', [ $this, 'breadcrumb_separator' ] );
		add_filter( 'woocommerce_get_breadcrumb', [ $this, 'add_breadcrumbs_from_filters' ], 10, 2 );

		/**
		 * Quantity input field: Add plus/minus buttons
		 *
		 * @since 1.7 - Render button after the input in order to hide it if input[type="hidden"]
		 */
		add_action( 'woocommerce_after_quantity_input_field', [ $this, 'quantity_input_field_add_minus_button' ] );
		add_action( 'woocommerce_after_quantity_input_field', [ $this, 'quantity_input_field_add_plus_button' ] );

		// Product tabs: Remove panel titles
		add_filter( 'woocommerce_product_description_heading', '__return_false' );
		add_filter( 'woocommerce_product_additional_information_heading', '__return_false' );
		add_filter( 'woocommerce_reviews_title', '__return_false' );

		// On Sale HTML
		add_filter( 'woocommerce_sale_flash', [ $this, 'badge_sale' ], 10, 3 );

		// Single product
		add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'badge_new' ], 9 );
		add_filter( 'woocommerce_product_review_comment_form_args', [ $this, 'product_review_comment_form_args' ] );

		// Query loop: using the query loop builder for products
		add_filter( 'bricks/posts/merge_query', [ $this, 'maybe_merge_query' ], 10, 2 );
		add_filter( 'bricks/posts/query_vars', [ $this, 'set_products_query_vars' ], 10, 3 );

		// Query: Add Woo Cart contents
		add_filter( 'bricks/setup/control_options', [ $this, 'add_control_options' ], 10, 1 );
		add_filter( 'bricks/query/run', [ $this, 'run_cart_query' ], 10, 2 );
		// Woo Phase 3
		// add_filter( 'bricks/query/run', [ $this, 'run_my_acc_menu_items' ], 10, 2 );
		add_filter( 'bricks/query/loop_object', [ $this, 'set_loop_object' ], 10, 3 );

		// TODO: Needed?
		add_filter( 'bricks/query/loop_object_id', [ $this, 'set_loop_object_id' ], 10, 3 );
		add_filter( 'bricks/query/loop_object_type', [ $this, 'set_loop_object_type' ], 10, 3 );

		add_filter( 'post_class', [ $this, 'post_class' ], 10, 3 );

		// Checkout: Make sure the fields removed by the user inside the builder are not required during the checkout process (@since 1.5.7)
		add_filter( 'woocommerce_checkout_fields', [ $this, 'woocommerce_checkout_fields' ], 99, 1 );

		// @since 1.6.1 - AJAX Add to cart
		if ( self::enabled_ajax_add_to_cart() ) {
			add_action( 'wc_ajax_bricks_add_to_cart', [ $this, 'add_to_cart' ] );
			add_action( 'wc_ajax_nopriv_bricks_add_to_cart', [ $this, 'add_to_cart' ] );
			add_filter( 'woocommerce_loop_add_to_cart_args', [ $this, 'overwrite_native_ajax_add_to_cart' ], 10, 2 );
		}

		// @since 1.7 - Remove / Restore Woo native hook actions when using {do_action}
		add_action( 'bricks/dynamic_data/before_do_action', [ $this, 'maybe_remove_woo_hook_actions' ], 10, 4 );
		add_action( 'bricks/dynamic_data/after_do_action', [ $this, 'maybe_restore_woo_hook_actions' ], 10, 5 );

		// @since 1.8.1 - Bricks WooCommerce Notice
		self::maybe_remove_native_woocommerce_notices_hooks();

		// Woo Phase 3 - Add body classes ('woo' or 'bricks')
		add_filter( 'body_class', [ $this, 'maybe_set_body_class' ], 10, 1 );

		// Woo Phase 3 - Add class when previewing a Woo template
		add_filter( 'bricks/content/attributes', [ $this, 'template_preview_main_classes' ], 10, 2 );

		// Woo Phase 3 - My account endpoints: Render Bricks template in account content areas (e.g. Oders, Downloads, Addresses, etc.)
		add_action( 'woocommerce_before_account_navigation', [ $this, 'remove_my_account_content' ] );
		add_action( 'woocommerce_account_content', [ $this, 'add_my_account_content' ] );

		// Woo Phase 3 - Set account navigation active class in builder
		add_filter( 'woocommerce_account_menu_item_classes', [ $this, 'woocommerce_account_menu_item_classes' ], 10, 2 );

		// @since 1.9 - Add quantity input field looping products
		if ( self::use_quantity_in_loop() ) {
			add_action( 'woocommerce_loop_add_to_cart_link', [ $this, 'add_quantity_input_field' ], 10, 2 );
		}

		// @since 1.9 - Sync Woocommerce product flexslider with Bricks thumbnail slider
		add_filter( 'woocommerce_single_product_carousel_options', [ $this, 'single_product_carousel_options' ] );

		add_filter( 'bricks/builder/dynamic_wrapper', [ $this, 'builder_dynamic_wrapper' ] );

		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
	}

	/**
	 * My account endpoint template preview: Redirect to actual my account endpoint
	 *
	 * To render entire my account area (navigation + content)
	 *
	 * @since 1.9
	 */
	public function template_redirect() {
		// Return: Not frontend nor a Bricks template
		if ( ! bricks_is_frontend() || ! is_singular( BRICKS_DB_TEMPLATE_SLUG ) ) {
			return;
		}

		$template_type = Templates::get_template_type();

		switch ( $template_type ) {
			case 'wc_account_dashboard':
				$redirect_url = add_query_arg( 'bricks_preview', time(), wc_get_account_endpoint_url( 'dashboard' ) );
				wp_safe_redirect( $redirect_url, 301 );
				break;

			case 'wc_account_orders':
				$redirect_url = add_query_arg( 'bricks_preview', time(), wc_get_account_endpoint_url( 'orders' ) );
				wp_safe_redirect( $redirect_url, 301 );
				break;

			case 'wc_account_view_order':
				// Get 'previewOrderId' from Bricks template data
				$elements = get_post_meta( get_the_ID(), BRICKS_DB_PAGE_CONTENT, true );
				$order_id = '';

				if ( is_array( $elements ) ) {
					foreach ( $elements as $element ) {
						if ( $element['name'] === 'woocommerce-account-view-order' && ! empty( $element['settings']['previewOrderId'] ) ) {
							$order_id = $element['settings']['previewOrderId'];
							break;
						}
					}
				}

				// No previewOrderId set: Get last order from WooCommerce
				if ( ! $order_id ) {
					$orders = wc_get_orders( [ 'limit' => 1 ] );

					if ( isset( $orders[0] ) ) {
						$order_id = $orders[0]->get_id();
					}
				}

				$redirect_url = wc_get_account_endpoint_url( 'view-order' );

				// Order ID found: Redirect to order view
				if ( $order_id ) {
					$redirect_url .= "/$order_id\/";
					$redirect_url  = add_query_arg( 'bricks_preview', time(), $redirect_url );
					wp_safe_redirect( $redirect_url, 301 );
				}

				break;

			case 'wc_account_downloads':
				$redirect_url = add_query_arg( 'bricks_preview', time(), wc_get_account_endpoint_url( 'downloads' ) );
				wp_safe_redirect( $redirect_url, 301 );
				break;

			case 'wc_account_addresses':
			case 'wc_account_form_edit_address':
				$redirect_url = add_query_arg( 'bricks_preview', time(), wc_get_account_endpoint_url( 'edit-address' ) );
				wp_safe_redirect( $redirect_url, 301 );
				break;

			case 'wc_account_form_edit_account':
				$redirect_url = add_query_arg( 'bricks_preview', time(), wc_get_account_endpoint_url( 'edit-account' ) );
				wp_safe_redirect( $redirect_url, 301 );
				break;
		}
	}

	/**
	 * Woo Phase 3: Get #brx-content HTML as rendered on the frontend
	 *
	 * To render complete my account (navigation + content)
	 * and move dynamic drag & drop area into my account content div.
	 *
	 * @since 1.9
	 */
	public function builder_dynamic_wrapper( $dynamic_area = [] ) {
		$template_type = Templates::get_template_type();

		if ( in_array(
			$template_type,
			[
				'wc_account_dashboard',
				'wc_account_orders',
				'wc_account_view_order',
				'wc_account_downloads',
				'wc_account_addresses',
				'wc_account_form_edit_address',
				'wc_account_form_edit_account',
			]
		)
		) {
			// STEP: Get My account page Bricks data
			$my_account_page_id = wc_get_page_id( 'myaccount' );
			$elements           = Helpers::render_with_bricks( $my_account_page_id ) ? get_post_meta( $my_account_page_id, BRICKS_DB_PAGE_CONTENT, true ) : false;

			if ( is_array( $elements ) && ! empty( $elements ) ) {
				ob_start();
				Frontend::render_content( $elements );
				$html = ob_get_clean();

				if ( $html ) {
					// Generate my account page CSS
					$css  = Templates::generate_inline_css( $my_account_page_id, $elements );
					$css .= Assets::$inline_css_dynamic_data;

					$dynamic_area = [
						'css'      => $css,
						'html'     => $html,
						'selector' => '.woocommerce-MyAccount-content',
					];
				}
			}

			/**
			 * STEP: Fallback: Use default WooCommerce my account shortcode
			 *
			 * Manually add main#brx-content as not available in TheDynamicArea.vue
			 */
			else {
				ob_start();
				echo '<main id="brx-content" class="wordpress" style="margin: 0 auto">';
				echo do_shortcode( '[woocommerce_my_account]' );
				echo '</main>';
				$html = ob_get_clean();

				$dynamic_area = [
					'css'      => '',
					'html'     => $html,
					'selector' => '.woocommerce-MyAccount-content',
				];
			}
		}

		return $dynamic_area;
	}

	/**
	 * Woo Phase 3 - Check if account dashboard requested
	 *
	 * is_account_page && no endpoint is in the query vars.
	 */
	public static function is_wc_account_dashboard() {
		global $wp;

		if ( is_account_page() ) {
			foreach ( WC()->query->get_query_vars() as $key => $value ) {
				if ( isset( $wp->query_vars[ $key ] ) ) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * My account: Remove default WooCommerce account template if Bricks template data is available
	 *
	 * @since 1.9
	 */
	public function remove_my_account_content() {
		// Orders
		if ( is_wc_endpoint_url( 'orders' ) ) {
			$template_data = self::get_template_data_by_type( 'wc_account_orders' );

			if ( $template_data ) {
				remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
			}
		}

		// Downloads
		elseif ( is_wc_endpoint_url( 'downloads' ) ) {
			$template_data = self::get_template_data_by_type( 'wc_account_downloads' );

			if ( $template_data ) {
				remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
			}
		}

		// Edit account
		elseif ( is_wc_endpoint_url( 'edit-account' ) ) {
			$template_data = self::get_template_data_by_type( 'wc_account_form_edit_account' );

			if ( $template_data ) {
				remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
			}
		}

		// View order
		elseif ( is_wc_endpoint_url( 'view-order' ) ) {
			$template_data = self::get_template_data_by_type( 'wc_account_view_order' );

			if ( $template_data ) {
				remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
			}
		}

		// Addresses
		elseif ( is_wc_endpoint_url( 'edit-address' ) ) {
			global $wp;

			// View addresses
			if ( empty( $wp->query_vars['edit-address'] ) ) {
				$template_data = self::get_template_data_by_type( 'wc_account_addresses' );
				if ( $template_data ) {
					remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
				}
			}

			// Edit address form requested (billing or shipping)
			else {
				$template_data = self::get_template_data_by_type( 'wc_account_form_edit_address' );
				if ( $template_data ) {
					remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
				}
			}
		}

		// Dashboard
		else {
			$template_data = self::get_template_data_by_type( 'wc_account_dashboard' );
			if ( $template_data ) {
				if ( self::is_wc_account_dashboard() ) {
					remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
				}
			}
		}
	}

	/**
	 * My account: Render Bricks template data if available
	 *
	 * @since 1.9
	 */
	public function add_my_account_content() {
		// Orders
		if ( is_wc_endpoint_url( 'orders' ) ) {
			$template_data = self::get_template_data_by_type( 'wc_account_orders' );

			if ( $template_data ) {
				echo $template_data;
			}
		}

		// Downloads
		elseif ( is_wc_endpoint_url( 'downloads' ) ) {
			$template_data = self::get_template_data_by_type( 'wc_account_downloads' );

			if ( $template_data ) {
				echo $template_data;
			}
		}

		// Edit account
		elseif ( is_wc_endpoint_url( 'edit-account' ) ) {
			$template_data = self::get_template_data_by_type( 'wc_account_form_edit_account' );

			if ( $template_data ) {
				echo $template_data;
			}
		}

		// View order
		elseif ( is_wc_endpoint_url( 'view-order' ) ) {
			$template_data = self::get_template_data_by_type( 'wc_account_view_order' );

			if ( $template_data ) {
				echo $template_data;
			}
		}

		// Addresses
		elseif ( is_wc_endpoint_url( 'edit-address' ) ) {
			global $wp;

			// View addresses
			if ( empty( $wp->query_vars['edit-address'] ) ) {
				$template_data = self::get_template_data_by_type( 'wc_account_addresses' );
				if ( $template_data ) {
					echo $template_data;
				}
			}

			// Edit address form requested (billing or shipping)
			else {
				$template_data = self::get_template_data_by_type( 'wc_account_form_edit_address' );
				if ( $template_data ) {
					echo $template_data;
				}
			}
		}

		// Dashboard
		else {
			$template_data = self::get_template_data_by_type( 'wc_account_dashboard' );
			if ( $template_data ) {
				if ( self::is_wc_account_dashboard() ) {
					echo $template_data;
				}
			}
		}
	}

	/**
	 * Woo Phase 3 - Set account navigation active class in builder
	 *
	 * @since 1.9
	 */
	public function woocommerce_account_menu_item_classes( $classes, $endpoint ) {
		if ( ! bricks_is_builder_iframe() ) {
			return $classes;
		}

		$template_type = Templates::get_template_type();

		// Endpoint: Orders
		if ( in_array( $template_type, [ 'wc_account_orders', 'wc_account_view_order' ] ) ) {
			if ( $endpoint === 'orders' ) {
				$classes[] = 'is-active';
			} else {
				// Filter out 'is-active' class
				$classes = array_filter(
					$classes,
					function( $class ) {
						return $class !== 'is-active';
					}
				);
			}
		}

		// Endpoint: Downloads
		if ( $template_type === 'wc_account_downloads' ) {
			if ( $endpoint === 'downloads' ) {
				$classes[] = 'is-active';
			} else {
				// Filter out 'is-active' class
				$classes = array_filter(
					$classes,
					function( $class ) {
						return $class !== 'is-active';
					}
				);
			}
		}

		// Endpoint: Addresses
		if ( in_array( $template_type, [ 'wc_account_addresses', 'wc_account_form_edit_address' ] ) ) {
			if ( $endpoint === 'edit-address' ) {
				$classes[] = 'is-active';
			} else {
				// Filter out 'is-active' class
				$classes = array_filter(
					$classes,
					function( $class ) {
						return $class !== 'is-active';
					}
				);
			}
		}

		// Endpoint: Edit account
		if ( $template_type === 'wc_account_form_edit_account' ) {
			if ( $endpoint === 'edit-account' ) {
				$classes[] = 'is-active';
			} else {
				// Filter out 'is-active' class
				$classes = array_filter(
					$classes,
					function( $class ) {
						return $class !== 'is-active';
					}
				);
			}
		}

		return $classes;
	}

	/**
	 * Sync Woocommerce product flexslider with Bricks thumbnail slider
	 *
	 * @since 1.9
	 */
	public function single_product_carousel_options( $options ) {
		$options['sync'] = '.brx-product-gallery-thumbnail-slider';

		return $options;
	}

	/**
	 * Checkout: Make sure the removed billing/shipping fields in the WooCommerce checkout customer details element are set to be not required
	 *
	 * @since 1.5.7
	 */
	public function woocommerce_checkout_fields( $fields ) {
		if ( ! is_checkout() ) {
			return $fields;
		}

		$templates = Templates::get_templates_by_type( 'wc_form_checkout' );

		if ( empty( $templates[0] ) ) {
			return $fields;
		}

		$elements = get_post_meta( $templates[0], BRICKS_DB_PAGE_CONTENT, true );

		if ( empty( $elements ) ) {
			return $fields;
		}

		$customer_details_settings = false;

		// Get settings of "Checkout customer details" element
		foreach ( $elements as $element ) {
			if ( $element['name'] === 'woocommerce-checkout-customer-details' && ! empty( $element['settings'] ) ) {
				$customer_details_settings = $element['settings'];
			}
		}

		// Mark removed billing fields to not-required
		if ( ! empty( $customer_details_settings['removeBillingFields'] ) && ! empty( $fields['billing'] ) ) {
			foreach ( $customer_details_settings['removeBillingFields'] as $field_id ) {
				if ( isset( $fields['billing'][ $field_id ] ) ) {
					$fields['billing'][ $field_id ]['required'] = 0;
				}
			}
		}

		// Mark removed shipping fields to not-required
		if ( ! empty( $customer_details_settings['removeShippingFields'] ) && ! empty( $fields['shipping'] ) ) {
			foreach ( $customer_details_settings['removeShippingFields'] as $field_id ) {
				if ( isset( $fields['shipping'][ $field_id ] ) ) {
					$fields['shipping'][ $field_id ]['required'] = 0;
				}
			}
		}

		return $fields;
	}

	/**
	 * Cart or checkout build with Bricks: Remove 'wordpress' post class to avoid auto-containing Bricks content
	 *
	 * @since 1.5.5
	 */
	public function post_class( $classes, $class, $post_id ) {
		$remove_wordpress_class = false;

		// Cart page
		if ( is_cart() ) {
			$count = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

			if (
				( $count && self::get_template_data_by_type( 'wc_cart', false ) ) || // Cart has items & Bricks template
				( ! $count && self::get_template_data_by_type( 'wc_cart_empty', false ) ) // Empty cart & Bricks template
			) {
				$remove_wordpress_class = true;
			}
		}

		// Checkout page
		if ( is_checkout() ) {
			// Order pay
			if ( get_query_var( 'order-pay' ) ) {
				if ( self::get_template_data_by_type( 'wc_form_pay', false ) ) {
					$remove_wordpress_class = true;
				}
			}

			// Order receipt (= thank you page)
			if ( get_query_var( 'order-received' ) ) {
				if ( self::get_template_data_by_type( 'wc_thankyou', false ) ) {
					$remove_wordpress_class = true;
				}
			}

			// Checkout page
			elseif ( self::get_template_data_by_type( 'wc_form_checkout', false ) ) {
				$remove_wordpress_class = true;
			}
		}

		// STEP: Remove 'wordpress' post class to avoid auto-containing Bricks content
		if ( $remove_wordpress_class ) {
			$index = array_search( 'wordpress', $classes );

			if ( isset( $classes[ $index ] ) ) {
				unset( $classes[ $index ] );
			}
		}

		return $classes;
	}

	/**
	 * If WooCommerce is not used, make sure the single and archive Woo templates are not used
	 *
	 * @since 1.5.1
	 *
	 * @param string $template
	 * @return string
	 */
	public function no_woo_template_include( $template ) {
		if ( empty( $template ) ) {
			return $template;
		}

		if ( strpos( $template, '/bricks/archive-product.php' ) ) {
			return get_query_template( 'archive', [ 'archive.php' ] );
		}

		if ( strpos( $template, '/bricks/single-product.php' ) ) {
			return get_query_template( 'single', [ 'single.php' ] );
		}

		return $template;
	}

	/**
	 * Sale badge HTML
	 *
	 * Show text or percentage.
	 */
	public function badge_sale( $html, $post, $product ) {
		$badge_type = Database::get_setting( 'woocommerceBadgeSale', false );

		// Type: ''
		if ( ! $badge_type ) {
			return;
		}

		// Type: text
		elseif ( $badge_type === 'text' ) {
			return '<span class="badge onsale">' . esc_html__( 'Sale', 'bricks' ) . '</span>';
		}

		// Type: percentage
		if ( $product->is_type( 'variable' ) ) {
			$percentages = [];

			// Get all variation prices
			$prices = $product->get_variation_prices();

			foreach ( $prices['price'] as $key => $price ) {
				if ( $prices['regular_price'][ $key ] !== $price ) {
					$percentages[] = round( 100 - ( floatval( $prices['sale_price'][ $key ] ) / floatval( $prices['regular_price'][ $key ] ) * 100 ) );
				}
			}

			// If unable to get any percentage, return the default HTML (@since 1.9.7)
			if ( empty( $percentages ) ) {
				return $html;
			}

			// Use highest discountvalue
			$percentage = max( $percentages ) . '%';
		} elseif ( $product->is_type( 'grouped' ) ) {
			$percentages = [];

			$children = $product->get_children();

			foreach ( $children as $child ) {
				$child_product = wc_get_product( $child );

				$regular_price = (float) $child_product->get_regular_price();
				$sale_price    = (float) $child_product->get_sale_price();

				if ( $sale_price != 0 || ! empty( $sale_price ) ) {
					$percentages[] = round( 100 - ( $sale_price / $regular_price * 100 ) );
				}
			}

			// If unable to get any percentage, return the default HTML (@since 1.9.7)
			if ( empty( $percentages ) ) {
				return $html;
			}

			// Use highest value
			$percentage = max( $percentages ) . '%';
		} else {
			$regular_price = (float) $product->get_regular_price();
			$sale_price    = (float) $product->get_sale_price();

			if ( $sale_price != 0 || ! empty( $sale_price ) ) {
				$percentage = round( 100 - ( $sale_price / $regular_price * 100 ) ) . '%';
			} else {
				return $html;
			}
		}

		return '<span class="badge onsale">-' . $percentage . '</span>';
	}

	public static function badge_new() {
		global $product;

		/**
		 * Avoid error if using on non product loop
		 *
		 * Replicate {do_action:woocommerce_before_shop_loop_item_title} on basic text element in non product loop.
		 *
		 * @since 1.9.4
		 */
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$newness_in_days = Database::get_setting( 'woocommerceBadgeNew', false );

		if ( ! $newness_in_days ) {
			return;
		}

		$newness_timestamp = time() - ( 60 * 60 * 24 * $newness_in_days );
		$created           = strtotime( $product->get_date_created() );
		$is_new            = $newness_timestamp < $created; // Created less than {$newness_in_days} days ago

		if ( $is_new ) {
			echo '<span class="badge new">' . esc_html__( 'New', 'bricks' ) . '</span>';
		}
	}

	/**
	 * Product review submit button: Add 'button' class to apply Woo button styles
	 */
	public function product_review_comment_form_args( $comment_form ) {
		$comment_form['class_submit'] = 'button';

		return $comment_form;
	}

	/**
	 * WooCommerce support sets WC_Template_Loader::$theme_support = true
	 */
	public function add_theme_support() {
		add_theme_support(
			'woocommerce',
			[
				'product_grid' => [
					'default_columns' => 4,
					'default_rows'    => 3,
					'min_columns'     => 1,
					'max_columns'     => 6,
					'min_rows'        => 1,
				],
			]
		);

		add_theme_support( 'wc-product-gallery-slider' );

		// Disable/enable product gallery zoom
		if ( Database::get_setting( 'woocommerceDisableProductGalleryZoom', false ) ) {
			remove_theme_support( 'wc-product-gallery-zoom' );
		} else {
			add_theme_support( 'wc-product-gallery-zoom' );
		}

		// Disable/enable product gallery lightbox (always disabled in builder)
		$disable_product_gallery_lightbox = Database::get_setting( 'woocommerceDisableProductGalleryLightbox', false );

		if ( $disable_product_gallery_lightbox || bricks_is_builder() ) {
			remove_theme_support( 'wc-product-gallery-lightbox' );
		} else {
			add_theme_support( 'wc-product-gallery-lightbox' );
		}
	}

	/**
	 * Get products terms (categories, tags) for in-builder product query controls
	 */
	public function set_products_terms() {
		if ( bricks_is_builder() ) {
			self::$product_categories = self::get_products_terms( 'product_cat' );
			self::$product_tags       = self::get_products_terms( 'product_tag' );
		}
	}

	/**
	 * Get terms for a given product taxonomy
	 */
	public static function get_products_terms( $taxonomy = null ) {
		if ( empty( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		$tags = [];

		foreach ( $terms as $term ) {
			$tags[ $term->term_id ] = $term->name;
		}

		return $tags;
	}

	/**
	 * Check if WooCommerce plugin is active
	 *
	 * @return boolean
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'woocommerce' ) && ! Database::get_setting( 'woocommerceDisableBuilder', false );
	}

	/**
	 * Init WooCommerce theme styles
	 */
	public function init_theme_styles() {
		$file = BRICKS_PATH . 'includes/woocommerce/theme-styles.php';

		if ( is_readable( $file ) ) {
			require_once $file;

			new Woocommerce_Theme_Styles();
		}
	}

	/**
	 * Init WooCommerce elements
	 */
	public function init_elements() {
		// Load WooCommerce helpers
		$helpers_file = BRICKS_PATH . 'includes/woocommerce/helpers.php';

		if ( is_readable( $helpers_file ) ) {
			require_once $helpers_file;
		}

		// Load woo element base class (Woo Phase 3)
		$woo_element_base = BRICKS_PATH . 'includes/woocommerce/elements/base.php';
		if ( is_readable( $woo_element_base ) ) {
			require_once $woo_element_base;
		}

		$woo_elements = [
			'woocommerce-breadcrumbs',
			'woocommerce-mini-cart',

			'product-title',
			'product-gallery',
			'product-short-description',
			'product-price',
			'product-stock',
			'product-meta',
			'product-rating',
			'product-content',
			'product-add-to-cart',
			'product-related',
			'product-reviews',
			'product-additional-information',
			'product-tabs',
			'product-upsells',

			'woocommerce-cart-collaterals',
			'woocommerce-cart-coupon',
			'woocommerce-cart-items',

			'woocommerce-checkout-customer-details',
			'woocommerce-checkout-order-review',
			'woocommerce-checkout-thankyou',
			'woocommerce-checkout-order-table',
			'woocommerce-checkout-order-payment',

			'woocommerce-products',
			'woocommerce-products-pagination',
			'woocommerce-products-orderby',
			'woocommerce-products-total-results',
			'woocommerce-products-filter',
			'woocommerce-products-archive-description',

			'woocommerce-notice',
			// 'woocommerce-template-hook', // NOTE: Not in use as action hooks can be added via the 'do_action' DD tag (@since 1.7)

			// Woo Phase 3 (@since 1.9)
			'woocommerce-account-page',

			'woocommerce-account-form-login',
			'woocommerce-account-form-register',
			'woocommerce-account-form-lost-password',
			'woocommerce-account-form-reset-password',

			'woocommerce-account-orders',
			'woocommerce-account-downloads',
			'woocommerce-account-addresses',
			'woocommerce-account-view-order',

			'woocommerce-account-form-edit-address',
			'woocommerce-account-form-edit-account',
		];

		foreach ( $woo_elements as $element_name ) {
			// @since 1.8.1 - Only register woocommerce-notice if user activated it
			if ( $element_name === 'woocommerce-notice' && ! self::use_bricks_woo_notice_element() ) {
				continue;
			}

			$woo_element_file = BRICKS_PATH . "includes/woocommerce/elements/$element_name.php";

			// Get the class name from the element name
			$class_name = str_replace( '-', '_', $element_name );
			$class_name = ucwords( $class_name, '_' );
			$class_name = "Bricks\\$class_name";

			if ( is_readable( $woo_element_file ) ) {
				Elements::register_element( $woo_element_file, $element_name, $class_name );
			}
		}
	}

	public function quantity_input_field_add_minus_button() {
		$html  = '<span class="action minus">';
		$html .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="12" x2="18" y2="12"></line></svg>';
		$html .= '</span>';

		echo $html;
	}

	public function quantity_input_field_add_plus_button() {
		$html  = '<span class="action plus">';
		$html .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="6" x2="12" y2="18"></line><line x1="6" y1="12" x2="18" y2="12"></line></svg>';
		$html .= '</span>';

		echo $html;
	}

	public function breadcrumb_separator( $defaults ) {
		$defaults['delimiter'] = '<span>/</span>';

		return $defaults;
	}

	/**
	 * Add search breadbrumb in the product archive if using Bricks search filter
	 *
	 * @param array         $crumbs
	 * @param WC_Breadcrumb $crumbs_obj
	 * @return array
	 */
	public function add_breadcrumbs_from_filters( $crumbs, $crumbs_obj ) {

		if ( ! empty( $_GET['b_search'] ) && Woocommerce_Helpers::is_archive_product() ) {
			$crumbs[] = [
				// translators: %s: search term
				sprintf( __( 'Search results for &ldquo;%s&rdquo;', 'woocommerce' ), wp_strip_all_tags( $_GET['b_search'] ) ),
				remove_query_arg( 'paged' )
			];
		}

		return $crumbs;
	}

	/**
	 * Bypass Builder post type check because page set to WooCommerce Shop fails
	 *
	 * @return boolean
	 */
	public function bypass_builder_post_type_check( $supported_post_types, $current_post_type ) {
		if ( in_array( 'page', $supported_post_types ) && ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) ) {
			$supported_post_types[] = 'product';
		}

		return $supported_post_types;
	}

	/**
	 * Builder: Set single product template & populate content (if needed)
	 */
	public function maybe_set_template_preview_content() {
		$post_id = get_the_ID();

		$template_type       = get_post_meta( $post_id, BRICKS_DB_TEMPLATE_TYPE, true );
		$template_preview_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

		if (
			strpos( $template_type, 'wc_' ) !== false ||
			wc_get_page_id( 'shop' ) == $template_preview_id ) {
			// Necessary to add 'woocommerce' to body_class for styling
			add_filter(
				'is_woocommerce',
				function() {
					return true;
				}
			);
		}

		// Remove 'woocommerce body class in builder panel
		if ( bricks_is_builder_main() ) {
			add_filter(
				'is_woocommerce',
				function() {
					return false;
				}
			);
		}

		// Form checkout template
		if (
			$template_type === 'wc_form_checkout' ||
			$template_type === 'wc_form_pay' ||
			$template_type === 'wc_cart'
		) {
			add_filter( 'body_class', [ $this, 'add_body_class' ], 9, 1 );
		}

		// Return: Not in builder nor template
		if ( ! bricks_is_builder() || ! Helpers::is_bricks_template( $post_id ) ) {
			return;
		}

		// Get the last product and save it as preview ID
		if ( $template_type === 'wc_product' ) {
			// Template has already a preview post ID: Leave
			$template_preview_post_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

			if ( $template_preview_post_id ) {
				return;
			}

			$products = wc_get_products(
				[
					'limit'   => 1,
					'orderby' => 'date',
					'order'   => 'DESC',
					'return'  => 'ids',
				]
			);

			if ( isset( $products[0] ) ) {
				Helpers::set_template_setting( $post_id, 'templatePreviewPostId', $products[0] );
				Helpers::set_template_setting( $post_id, 'templatePreviewType', 'single' );
				Helpers::set_template_setting( $post_id, 'templatePreviewAutoContent', 1 ); // This setting will be used to trigger a notification
			}
		}

		// TODO: Replace this logic by a generic template preview CPT Archive > CPT = products
		// elseif ( $template_type === 'wc_archive' ) {
		// $template_preview_type = Helpers::get_template_setting( 'templatePreviewType', $post_id );

		// if ( $template_preview_type ) {
		// return;
		// }

		// Helpers::set_template_setting( $post_id, 'templatePreviewType', 'archive-product' );
		// }
	}

	/**
	 * Cart page / Checkout page: Return no title if rendered via Bricks template
	 *
	 * @since 1.8
	 */
	public function default_page_title( $post_title, $post_id ) {
		if ( is_cart() ) {
			if ( WC()->cart->is_empty() ) {
				// Empty cart template
				$cart_template_ids = \Bricks\Templates::get_templates_by_type( 'wc_cart_empty' );
			} else {
				// Normal cart template
				$cart_template_ids = \Bricks\Templates::get_templates_by_type( 'wc_cart' );
			}

			return empty( $cart_template_ids ) ? $post_title : '';
		}

		if ( is_checkout() ) {
			$checkout_template_ids = \Bricks\Templates::get_templates_by_type( 'wc_form_checkout' );

			return empty( $checkout_template_ids ) ? $post_title : '';
		}

		// My account page (@since Woo Phase 3)
		if ( is_account_page() ) {
			// Get current endpoint
			$endpoint = WC()->query->get_current_endpoint();

			$navigation_ids = \Bricks\Templates::get_templates_by_type( 'wc_account_navigation' );

			switch ( $endpoint ) {
				case 'orders':
					$wc_template_ids         = \Bricks\Templates::get_templates_by_type( 'wc_account_orders' );
					$my_account_template_ids = array_merge( $wc_template_ids, $navigation_ids );

					break;
				case 'view-order':
					$wc_template_ids         = \Bricks\Templates::get_templates_by_type( 'wc_account_view_order' );
					$my_account_template_ids = array_merge( $wc_template_ids, $navigation_ids );

					break;
				case 'downloads':
					$wc_template_ids         = \Bricks\Templates::get_templates_by_type( 'wc_account_downloads' );
					$my_account_template_ids = array_merge( $wc_template_ids, $navigation_ids );

					break;
				case 'edit-address':
					// maybe wc_account_addresses or wc_account_form_edit_address
					global $wp;
					$is_edit_address = isset( $wp->query_vars['edit-address'] ) ? true : false;

					if ( $is_edit_address ) {
						$wc_template_ids = \Bricks\Templates::get_templates_by_type( 'wc_account_form_edit_address' );
					} else {
						$wc_template_ids = \Bricks\Templates::get_templates_by_type( 'wc_account_addresses' );
					}

					$my_account_template_ids = array_merge( $wc_template_ids, $navigation_ids );

					break;
				case 'edit-account':
					$wc_template_ids         = \Bricks\Templates::get_templates_by_type( 'wc_account_form_edit_account' );
					$my_account_template_ids = array_merge( $wc_template_ids, $navigation_ids );

					break;
				case 'lost-password':
					// maybe wc_account_form_lost_password or wc_account_reset_password or wc_account_form_lost_password_confirmation
					// check woocommerce/includes/shortcodes/class-wc-shortcode-my-account.php
					if ( ! empty( $_GET['reset-link-sent'] ) ) {
						$my_account_template_ids = \Bricks\Templates::get_templates_by_type( 'wc_account_form_lost_password_confirmation' );
					} elseif ( ! empty( $_GET['show-reset-form'] ) ) {
						$my_account_template_ids = \Bricks\Templates::get_templates_by_type( 'wc_account_reset_password' );
					} else {
						$my_account_template_ids = \Bricks\Templates::get_templates_by_type( 'wc_account_form_lost_password' );
					}

					break;
				case '':
					// maybe login form or wc_account_dashboard
					if ( is_user_logged_in() ) {
						$wc_template_ids         = \Bricks\Templates::get_templates_by_type( 'wc_account_dashboard' );
						$my_account_template_ids = array_merge( $wc_template_ids, $navigation_ids );
					} else {
						$my_account_template_ids = \Bricks\Templates::get_templates_by_type( 'wc_account_form_login' );
					}
					break;
			}

			return empty( $my_account_template_ids ) ? $post_title : '';
		}

		return $post_title;
	}

	/**
	 * Set aria-current="page" for WooCommerce
	 *
	 * @since 1.8
	 */
	public function maybe_set_aria_current_page( $set, $url ) {
		// WooCommerce shop page
		if ( is_shop() ) {
			$set = $url === get_permalink( wc_get_page_id( 'shop' ) );
		}

		// WooCommerce my account page (@since Woo Phase 3)
		if ( is_account_page() ) {

			/**
			 * Based on the $url of the link, we need to know which endpoint is currently active
			 * Then use the slugs of the endpoints to check if the $url contains the required paths
			 * Bear in mind that the $url might be a relative url, a full url, a url with query string, url with hash, etc.
			 */
			$wc_endpoints          = WC()->query->get_query_vars(); // array keys are the endpoints, values are the slug
			$current_endpoint      = WC()->query->get_current_endpoint();
			$current_endpoint_slug = isset( $wc_endpoints[ $current_endpoint ] ) ? $wc_endpoints[ $current_endpoint ] : '';

			$my_account_page_id   = wc_get_page_id( 'myaccount' );
			$my_account_page_slug = get_post_field( 'post_name', $my_account_page_id );

			// STEP: Get required paths in array format
			// My account page slug is always required
			$required_paths = [ $my_account_page_slug ];

			if ( ! empty( $current_endpoint_slug ) ) {
				// Add current endpoint slug if not empty
				$required_paths[] = $current_endpoint_slug;
			}

			// STEP: Get the path in array format
			$url_path = parse_url( $url, PHP_URL_PATH ); // Ex: /my-account/orders/, /subfolder/my-account/view-order/123/, /subfolder/xxx/my-account

			if ( $url_path ) {
				// Convert to array
				$url_path = explode( '/', $url_path ); // Ex: [ '', 'my-account', 'view-order', '123', '' ]

				// Remove empty items
				$url_path = array_filter( $url_path ); // Ex: [ 'my-account', 'view-order', '123' ]

				// Default, Set true if the URL contains the required paths
				$set = count( array_intersect( $required_paths, $url_path ) ) === count( $required_paths );

				if ( $current_endpoint === 'view-order' ) {
					// In view-order endpoint, should set true if the URL contains the orders endpoint slug as well (child endpoint)
					$set = $set || in_array( $wc_endpoints['orders'], $url_path );
				}

				if ( $current_endpoint === '' ) {
					// In dashboard endpoint, $my_account_page_slug must be the last item in $url_path (yourwebsite/subfolder/xxx/my-account/)
					$set = end( $url_path ) === $my_account_page_slug;
				}
			}
		}

		return $set;
	}

	public static function get_wc_endpoint_from_url( $url ) {
		// Get the base URL of the site.
		$site_url = get_site_url();

		// Check if the provided URL belongs to the current site.
		if ( strpos( $url, $site_url ) === false ) {
			return false;
		}

		// Get the path from the provided URL.
		$parsed_url = wp_parse_url( $url );
		$path       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';

		// Remove the trailing slash from the path.
		$path = untrailingslashit( $path );

		// Get the registered WooCommerce endpoints.
		$endpoints = WC()->query->get_query_vars();

		// Iterate through the endpoints and find the match.
		foreach ( $endpoints as $endpoint => $value ) {
			$endpoint_slug = untrailingslashit( wc_get_endpoint_url( $endpoint ) );

			// Check if the path matches the endpoint.
			if ( $path === $endpoint_slug ) {
				return $endpoint;
			}
		}

		return false;
	}

	/**
	 * Builder: Add body classes to Woo templates
	 *
	 * @param array $classes
	 */
	public function add_body_class( $classes ) {
		if ( get_post_type() !== BRICKS_DB_TEMPLATE_SLUG ) {
			return $classes;
		}

		if ( Templates::get_template_type() === 'wc_form_checkout' ) {
			$classes[] = 'woocommerce-checkout';
			$classes[] = 'woocommerce-page';
		} elseif ( Templates::get_template_type() === 'wc_form_pay' ) {
			$classes[] = 'woocommerce-checkout';
		} elseif ( Templates::get_template_type() === 'wc_cart' ) {
			$classes[] = 'woocommerce-cart';
			$classes[] = 'woocommerce-page';
		}

		return $classes;
	}

	/**
	 * On the builder, move up WooCommerce specific elements
	 *
	 * @since 1.2.1
	 *
	 * @param string $category
	 * @param int    $post_id
	 * @param string $post_type
	 *
	 * @return string
	 */
	public function set_first_element_category( $category, $post_id, $post_type ) {
		if ( BRICKS_DB_TEMPLATE_SLUG === $post_type ) {
			$template_type = get_post_meta( $post_id, BRICKS_DB_TEMPLATE_TYPE, true );

			if ( $template_type == 'wc_product' ) {
				return 'woocommerce_product';
			} elseif ( strpos( $template_type, 'wc_' ) !== false ) {
				return 'woocommerce';
			}
		} elseif ( is_post_type_archive( 'product' ) || $post_id == wc_get_page_id( 'shop' ) ) {
			return 'woocommerce';
		} elseif ( 'product' == $post_type ) {
			return 'woocommerce_product';
		}

		return $category;
	}

	/**
	 * Page marked as shop - is_shop() - has a global $post_id set to the first product (like is_home)
	 *
	 * In builder or when setting the active templates we need to replace the active post id by the page id
	 *
	 * @param int $post_id
	 */
	public function maybe_set_post_id( $post_id ) {
		// If launching bricks builder on page defined as shop
		if ( is_shop() && ! Helpers::is_bricks_template( $post_id ) ) {
			$page_id = wc_get_page_id( 'shop' );

			$post_id = ! empty( $page_id ) ? $page_id : $post_id;
		}

		return $post_id;
	}

	/**
	 * Add WooCommerce element link selectors to allow Theme Styles for the links
	 *
	 * @since 1.5.7
	 */
	public function link_css_selectors( $selectors ) {
		$selectors[] = '.brxe-product-content a';
		$selectors[] = '.brxe-product-short-description a';
		$selectors[] = '.brxe-product-tabs .woocommerce-Tabs-panel a';

		return $selectors;
	}

	/**
	 * NOTE: Not in use as we renamed the 'PhotoSwipe' class to 'Photoswipe5' to avoid conflicts with WooCommerce Photoswipe 4
	 */
	public function unload_photoswipe5_lightbox_assets() {
		// Remove Bricks lightbox (as Photoswipe 5 conflicts with Photoswipe 4, the latter which is used by WooCommerce)
		if ( is_product() && current_theme_supports( 'wc-product-gallery-lightbox' ) ) {
			wp_deregister_script( 'bricks-photoswipe' );
			wp_deregister_script( 'bricks-photoswipe-lightbox' );
			wp_deregister_style( 'bricks-photoswipe' );
		}
	}

	/**
	 * Remove WooCommerce scripts on non-WooCommerce pages
	 *
	 * @since 1.2.1
	 */
	public function wp_enqueue_scripts() {
		if ( bricks_is_builder_iframe() ) {
			// Required for product gallery & tabs
			wp_enqueue_script( 'wc-single-product' );
		}

		if ( ! bricks_is_builder_main() ) {
			wp_enqueue_script( 'bricks-woocommerce', BRICKS_URL_ASSETS . 'js/integrations/woocommerce.min.js', [ 'bricks-scripts' ], filemtime( BRICKS_PATH_ASSETS . 'js/integrations/woocommerce.min.js' ), true );
			wp_enqueue_style( 'bricks-woocommerce', BRICKS_URL_ASSETS . 'css/integrations/woocommerce.min.css', [ 'bricks-frontend' ], filemtime( BRICKS_PATH_ASSETS . 'css/integrations/woocommerce.min.css' ) );
		}

		if ( is_rtl() ) {
			wp_enqueue_style( 'bricks-woocommerce-rtl', BRICKS_URL_ASSETS . 'css/integrations/woocommerce-rtl.min.css', [ 'bricks-frontend' ], filemtime( BRICKS_PATH_ASSETS . 'css/integrations/woocommerce-rtl.min.css' ) );
		}

		// @since 1.6.1 - Bricks WooCommerce settings for frontend
		wp_localize_script(
			'bricks-scripts',
			'bricksWooCommerce',
			[
				'ajaxAddToCartEnabled' => self::enabled_ajax_add_to_cart(),
				'ajaxAddingText'       => self::global_ajax_adding_text(),
				'ajaxAddedText'        => self::global_ajax_added_text(),
				'addedToCartNotices'   => '',
				'showNotice'           => self::global_ajax_show_notice(),
				'scrollToNotice'       => self::global_ajax_scroll_to_notice(),
				'resetTextAfter'       => self::global_ajax_reset_text_after(),
				'useQtyInLoop'         => self::use_quantity_in_loop(),
			]
		);
	}

	/**
	 * Before Bricks searchs for the right template, set the content_type if needed
	 *
	 * @param string $content_type
	 * @param int    $post_id
	 */
	public static function set_content_type( $content_type, $post_id ) {
		// If using /?s=abc&post_type=product, will change the $active_template to unexpected template (@since 1.9.1)
		if ( is_search() ) {
			return $content_type;
		}

		// These will only kick in if user has defaultTemplatesDisabled = false
		if ( is_product() ) {
			$content_type = 'wc_product';
		} elseif ( is_shop() ) {
			$content_type = 'content';
		} elseif ( Woocommerce_Helpers::is_archive_product() ) {
			$content_type = 'wc_archive';
		}

		return $content_type;
	}

	/**
	 * Add template types to control options
	 *
	 * @param array $control_options
	 * @return array
	 *
	 * @since 1.4
	 */
	public function add_template_types( $control_options ) {
		$template_types = $control_options['templateTypes'];

		// @since 1.3: Product archive & single product templates
		$template_types['wc_archive'] = 'WooCommerce - ' . esc_html__( 'Product archive', 'bricks' );
		$template_types['wc_product'] = 'WooCommerce - ' . esc_html__( 'Single product', 'bricks' );

		// @since 1.4: Cart & checkout templates
		$template_types['wc_cart']          = 'WooCommerce - ' . esc_html__( 'Cart', 'bricks' );
		$template_types['wc_cart_empty']    = 'WooCommerce - ' . esc_html__( 'Empty cart', 'bricks' );
		$template_types['wc_form_checkout'] = 'WooCommerce - ' . esc_html__( 'Checkout', 'bricks' );
		$template_types['wc_form_pay']      = 'WooCommerce - ' . esc_html__( 'Pay', 'bricks' );
		$template_types['wc_thankyou']      = 'WooCommerce - ' . esc_html__( 'Thank you', 'bricks' );
		$template_types['wc_order_receipt'] = 'WooCommerce - ' . esc_html__( 'Order receipt', 'bricks' );

		// Woo Phase 3
		$template_types['wc_account_form_login']                      = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Login', 'bricks' );
		$template_types['wc_account_form_lost_password']              = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Lost password', 'bricks' );
		$template_types['wc_account_form_lost_password_confirmation'] = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Lost password', 'bricks' ) . ' (' . esc_html__( 'Confirmation', 'bricks' ) . ')';
		$template_types['wc_account_reset_password']                  = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Reset password', 'bricks' );
		$template_types['wc_account_dashboard']                       = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Dashboard', 'bricks' );
		$template_types['wc_account_orders']                          = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Orders', 'bricks' );
		$template_types['wc_account_view_order']                      = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'View order', 'bricks' );
		$template_types['wc_account_downloads']                       = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Downloads', 'bricks' );
		$template_types['wc_account_addresses']                       = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Addresses', 'bricks' );
		$template_types['wc_account_form_edit_address']               = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Edit address', 'bricks' );
		$template_types['wc_account_form_edit_account']               = 'WooCommerce - ' . esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Edit account', 'bricks' );

		$control_options['templateTypes'] = $template_types;

		return $control_options;
	}

	/**
	 * Remove "Template Conditions" & "Populate Content" panel controls for WooCommerce Cart & Checkout template parts
	 *
	 * @param array $settings
	 * @return array
	 *
	 * @since 1.4
	 */
	public function remove_template_conditions( $settings ) {
		$excluded_templates = [
			'wc_cart',
			'wc_cart_empty',
			'wc_form_checkout',
			'wc_form_pay',
			'wc_thankyou',
			'wc_order_receipt',

			// Woo Phase 3
			'wc_account_dashboard',
			'wc_account_orders',
			'wc_account_view_order',
			'wc_account_downloads',
			'wc_account_addresses',
			'wc_account_form_edit_address',
			'wc_account_form_edit_account',
			'wc_account_form_login',
			'wc_account_form_lost_password',
			'wc_account_form_lost_password_confirmation',
			'wc_account_reset_password',
		];

		if ( isset( $settings['controlGroups']['template-preview'] ) ) {
			$settings['controlGroups']['template-preview']['required'] = [ 'templateType', '!=', $excluded_templates, 'templateType' ];
		}

		if ( isset( $settings['controls']['templateConditionsInfo'] ) ) {
			$settings['controls']['templateConditionsInfo']['required'] = [ 'templateType', '!=', $excluded_templates, 'templateType' ];
		}

		if ( isset( $settings['controls']['templateConditions'] ) ) {
			$settings['controls']['templateConditions']['required'] = [ 'templateType', '!=', $excluded_templates, 'templateType' ];
		}

		$settings['controls'][] = [
			'group'    => 'template-conditions',
			'type'     => 'info',
			'content'  => esc_html__( 'This template type is automatically rendered on the correct page.', 'bricks' ),
			'required' => [ 'templateType', '=', $excluded_templates, 'templateType' ],
		];

		return $settings;
	}

	/**
	 * Get template data by template type
	 *
	 * For woocommerce templates inside Bricks theme.
	 *
	 * Return template data rendered via Bricks template shortcode.
	 *
	 * @since 1.8: Return template ID if render is false (to not trigger any hooks when we are not rendering the template)
	 * Example: do_shortcode will be execute in post_class filter, which will trigger the do_shortcode action,
	 * and causing wc_print_notices to be executed in post_class filter before the actual template is rendered.
	 * Resulted actual template rendering empty notices. (wc_print_notices() will erase the notices after it is executed)
	 *
	 * @see /includes/woocommerce/cart/cart.php (wc_cart), etc.
	 *
	 * @since 1.4
	 */
	public static function get_template_data_by_type( $type = '', $render = true ) {
		// Do not check for Database::get_setting( 'defaultTemplatesDisabled' )
		$template_ids = Templates::get_templates_by_type( $type );
		$template_id  = $template_ids[0] ?? false;

		// No template found
		if ( ! $template_id ) {
			return false;
		}

		// Return template id if render is false
		if ( ! $render ) {
			return $template_id;
		}

		$output = '';

		/**
		 * Add page settings custom CSS to return with rendered template
		 *
		 * For Woo cart, checkout, account pages, etc.
		 *
		 * @since 1.9.6
		 */
		$template_page_settings = get_post_meta( $template_id, BRICKS_DB_PAGE_SETTINGS, true );
		if ( $template_page_settings ) {
			$page_settings_controls = Settings::get_controls_data( 'page' );
			$page_settings_css      = Assets::generate_inline_css_from_element(
				[ 'settings' => $template_page_settings ],
				$page_settings_controls['controls'],
				'page'
			);

			// Add style to template output
			if ( $page_settings_css ) {
				$output .= "<style>$page_settings_css</style>";
			}
		}

		// Render template output
		$output .= do_shortcode( "[bricks_template id=\"$template_id\"]" );

		return $output;
	}

	/**
	 * Add Archive Product content type
	 *
	 * Note: Not in use
	 *
	 * @param array $types
	 */
	public function add_content_types( $types ) {
		$types['archive-product'] = esc_html__( 'Archive (products)', 'bricks' );

		return $types;
	}

	/**
	 * Setup the products query loop in the products archive, including is_shop page (frontend only)
	 *
	 * @param array  $data Elements list.
	 * @param string $post_id Post ID.
	 */
	public function setup_query( $data, $post_id ) {
		$query_element = Woocommerce_Helpers::get_products_element( $data );

		// No query element to merge, proceed with regular WooCommerce loop
		if ( ! $query_element ) {
			wc_setup_loop();

			return;
		}

		// Force the post type to feed the Bricks Query class
		if ( empty( $query_element['settings']['query'] ) ) {
			$query_element['settings']['query'] = [
				'post_type'           => [ 'product' ],
				'ignore_sticky_posts' => 1
			];
		}

		// Query
		$query_object = new Query( $query_element );

		$query = $query_object->query_result;

		// Destroy query to explicitly remove it from the global store
		$query_object->destroy();

		// Remove ordering query arguments which may have been added by 'get_catalog_ordering_args'
		WC()->query->remove_ordering_args();

		$columns = isset( $query_element['settings']['columns'] ) ? $query_element['settings']['columns'] : 4;

		wc_setup_loop(
			[
				'columns'      => $columns,
				'name'         => 'bricks-products',
				'is_shortcode' => true,
				'is_search'    => false,
				'is_paginated' => true,
				'total'        => (int) $query->found_posts,
				'total_pages'  => (int) $query->max_num_pages,
				'per_page'     => (int) $query->get( 'posts_per_page' ),
				'current_page' => (int) max( 1, $query->get( 'paged', 1 ) ),
			]
		);
	}

	public function reset_query( $sections, $post_id ) {
		wc_reset_loop();
	}

	/**
	 * Update the mini-cart fragments
	 *
	 * @param array $fragments
	 */
	public function update_mini_cart( $fragments ) {
		if ( ! is_object( WC()->cart ) ) {
			return;
		}

		// Cart Count
		$count = WC()->cart->get_cart_contents_count();

		$fragments['span.cart-count'] = '<span class="cart-count ' . ( $count == 0 ? 'hide' : 'show' ) . '">' . $count . '</span>';

		// Cart Subtotal
		$subtotal = WC()->cart->get_cart_subtotal();

		if ( $subtotal ) {
			$fragments['span.cart-subtotal'] = '<span class="cart-subtotal">' . $subtotal . '</span>';
		}

		return $fragments;
	}

	/**
	 * Check if the query loop is on Woo products, and if yes, check if we should merge the main query
	 *
	 * @since 1.5
	 *
	 * @param boolean $merge
	 * @param string  $element_id
	 * @return boolean
	 */
	public function maybe_merge_query( $merge, $element_id ) {
		$query = Query::get_query_for_element_id( $element_id );

		if ( ! isset( $query->query_vars['post_type'] ) ) {
			return $merge;
		}

		if ( is_array( $query->query_vars['post_type'] ) && ! in_array( 'product', $query->query_vars['post_type'] ) ) {
			return $merge;
		}

		return Woocommerce_Helpers::is_archive_product();
	}

	/**
	 * Add products query vars to the query loop
	 *
	 * @since 1.5
	 *
	 * @param array  $query_vars
	 * @param array  $settings
	 * @param string $element_id
	 * @return boolean
	 */
	public function set_products_query_vars( $query_vars, $settings, $element_id ) {
		if ( ! isset( $query_vars['post_type'] ) ) {
			return $query_vars;
		}

		// Convert post_type to array if it is a string (@since 1.9.6)
		if ( is_string( $query_vars['post_type'] ) ) {
			$query_vars['post_type'] = [ $query_vars['post_type'] ];
		}

		if ( is_array( $query_vars['post_type'] ) && ! in_array( 'product', $query_vars['post_type'] ) ) {
			return $query_vars;
		}

		/**
		 * Do not modify the query_vars if "FiboSearch - AJAX Search for WooCommerce" is active
		 *
		 * @since 1.9.1
		 */
		if ( is_search() && function_exists( 'dgoraAsfwFs' ) ) {
			return $query_vars;
		}

		/**
		 * Do not modify the query vars if 'disable_query_merge' is set
		 *
		 * @since 1.9.2
		 */
		if ( isset( $query_vars['disable_query_merge'] ) ) {
			return $query_vars;
		}

		$new_query_vars = $query_vars;

		$filter_args = Woocommerce_Helpers::filters_query_args( $settings );

		// Override the query settings by the filters (orderby, filters)
		foreach ( $filter_args as $key => $filter_value ) {
			if ( in_array( $key, [ 'meta_query', 'tax_query' ] ) && ! empty( $query_vars[ $key ] ) ) {
				continue;
			}

			$new_query_vars[ $key ] = $filter_value;
		}

		// STEP: Merge meta or/and tax query (if has conflicts)
		foreach ( [ 'meta_query', 'tax_query' ] as $type ) {
			if ( empty( $query_vars[ $type ] ) || empty( $filter_args[ $type ] ) ) {
				continue;
			}

			$relation_query_vars  = isset( $query_vars[ $type ]['relation'] ) ? $query_vars[ $type ]['relation'] : false;
			$relation_filter_args = isset( $filter_args[ $type ]['relation'] ) ? $filter_args[ $type ]['relation'] : false;

			// Both meta query sources have the relation key set and they are different
			if ( $relation_query_vars && $relation_filter_args && $relation_query_vars != $relation_filter_args ) {
				$new_query_vars[ $type ] = [
					'relation' => 'AND',
					0          => $query_vars[ $type ],
					1          => $filter_args[ $type ]
				];
			}

			// Relations are equal or not set
			else {
				$relation = $relation_query_vars ? $relation_query_vars : ( $relation_filter_args ? $relation_filter_args : false );

				unset( $query_vars[ $type ]['relation'] );
				unset( $filter_args[ $type ]['relation'] );

				$new_query_vars[ $type ] = array_merge( $query_vars[ $type ], $filter_args[ $type ] );

				if ( $relation ) {
					$new_query_vars[ $type ]['relation'] = $relation;
				}
			}
		}

		return $new_query_vars;
	}

	/**
	 * Adds the cart contents query to the Query Loop builder
	 *
	 * @param array $control_options
	 * @return array
	 */
	public function add_control_options( $control_options ) {
		$control_options['queryTypes']['wooCart'] = esc_html__( 'Cart contents', 'bricks' );

		return $control_options;
	}

	/**
	 * Returns the cart contents query
	 *
	 * @param array $results
	 * @param Query $query
	 * @return array
	 */
	public function run_cart_query( $results, $query ) {
		if ( $query->object_type !== 'wooCart' ) {
			return $results;
		}

		// Avoid Uncaught Error: Call to a member function get_cart() on null (@since 1.8.1)
		if ( is_null( WC()->cart ) ) {
			return [];
		}

		return WC()->cart->get_cart();
	}

	/**
	 * Sets the loop object (to WP_Post) in each query loop iteration
	 *
	 * @param array  $loop_object
	 * @param string $loop_key
	 * @param Query  $query
	 * @return array
	 */
	public function set_loop_object( $loop_object, $loop_key, $query ) {
		if ( $query->object_type !== 'wooCart' ) {
			return $loop_object;
		}

		// @see woocommerce/templates/cart/cart.php
		$_product   = apply_filters( 'woocommerce_cart_item_product', $loop_object['data'], $loop_object, $loop_key );
		$product_id = apply_filters( 'woocommerce_cart_item_product_id', $loop_object['product_id'], $loop_object, $loop_key );

		global $post;

		$post = get_post( $product_id );

		setup_postdata( $post );

		return $loop_object;
	}

	/**
	 * Returns the loop object id (for the cart query)
	 *
	 * @since 1.5.3
	 */
	public function set_loop_object_id( $object_id, $object, $query_id ) {
		$query_object_type = Query::get_query_object_type( $query_id );

		if ( $query_object_type !== 'wooCart' ) {
			return $object_id;
		}

		return get_the_ID();
	}

	/**
	 * Returns the loop object type (for the cart query)
	 *
	 * @since 1.5.3
	 */
	public function set_loop_object_type( $object_type, $object, $query_id ) {
		$query_object_type = Query::get_query_object_type( $query_id );

		if ( $query_object_type !== 'wooCart' ) {
			return $object_type;
		}

		return 'post';
	}

	/**
	 * Check if user enabled single ajax add to cart
	 *
	 * @return bool
	 * @since 1.6.1
	 */
	public static function enabled_ajax_add_to_cart() {
		return Database::get_setting( 'woocommerceEnableAjaxAddToCart', false );
	}

	/**
	 * Get global AJAX show notice setting
	 *
	 * @return string
	 * @since 1.9
	 */
	public static function global_ajax_show_notice() {
		return Database::get_setting( 'woocommerceAjaxShowNotice', false ) ? 'yes' : 'no';
	}

	/**
	 * Get global AJAX scroll to notice setting
	 *
	 * @return string
	 * @since 1.9
	 */
	public static function global_ajax_scroll_to_notice() {
		return Database::get_setting( 'woocommerceAjaxScrollToNotice', false ) ? 'yes' : 'no';
	}

	/**
	 * Get global AJAX reset text after setting
	 *
	 * @return int
	 * @since 1.9
	 */
	public static function global_ajax_reset_text_after() {
		$reset_after = absint( Database::get_setting( 'woocommerceAjaxResetTextAfter', 3 ) );
		return max( $reset_after, 1 );
	}

	/**
	 * Get global AJAX adding text setting
	 *
	 * @return string
	 * @since 1.9.2
	 */
	public static function global_ajax_adding_text() {
		return Database::get_setting( 'woocommerceAjaxAddingText', esc_html__( 'Adding', 'bricks' ) );
	}

	/**
	 * Get global AJAX added text setting
	 *
	 * @return string
	 * @since 1.9.2
	 */
	public static function global_ajax_added_text() {
		return Database::get_setting( 'woocommerceAjaxAddedText', esc_html__( 'Added', 'bricks' ) );
	}

	/**
	 * AJAX Add to cart
	 * Support product types: simple, variable, grouped
	 *
	 * @since 1.6.1
	 *
	 * @see woocommerce/includes/class-wc-ajax.php add_to_cart()
	 */
	public function add_to_cart() {
		ob_start();

		$product_id = isset( $_POST['product_id'] ) ? apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_POST['product_id'] ) ) : 0;

		if ( ! $product_id ) {
			return;
		}

		$product_status = get_post_status( $product_id );
		$product_type   = isset( $_POST['product_type'] ) ? sanitize_title( wp_unslash( $_POST['product_type'] ) ) : 'simple';
		$quantity       = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1;
		$variation_id   = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$variation      = isset( $_POST['variation'] ) ? (array) $_POST['variation'] : [];
		$products       = isset( $_POST['products'] ) ? (array) $_POST['products'] : [];

		switch ( $product_type ) {
			case 'grouped':
				// No products added
				if ( count( $products ) < 1 ) {
					return;
				}

				$passed = [];
				foreach ( $products as $id => $quantity ) {
					if ( $quantity > 0 ) {
						$each_passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $id, $quantity );
						if ( $each_passed_validation && false !== WC()->cart->add_to_cart( $id, $quantity ) && 'publish' === $product_status ) {
							do_action( 'woocommerce_ajax_added_to_cart', $id );
							$passed[ $id ] = $quantity;
						}
					}
				}

				// Overall passed validation for grouped products
				$passed_validation = count( $passed ) === count( $products );

				// When using Bricks AJAX add to cart, we always generate the notices
				if ( $passed_validation ) {
					foreach ( $passed as $id => $quantity ) {
						wc_add_to_cart_message( [ $id => $quantity ], true );
					}
				}
				break;

			default:
			case 'variable':
			case 'simple':
				$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );

				if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) && 'publish' === $product_status ) {
					do_action( 'woocommerce_ajax_added_to_cart', $product_id );

					// When using Bricks AJAX add to cart, we always generate the notices
					wc_add_to_cart_message( [ $product_id => $quantity ], true );
				} else {
					$passed_validation = false;
				}
				break;
		}

		// Return error
		if ( ! $passed_validation ) {
			// If there was an error adding to the cart, redirect to the product page to show any errors
			$data = [
				'error'       => true,
				'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id ),
			];

			// Send error json
			wp_send_json( $data );
		}

		/**
		 * No error, return fragments and cart hash (default)
		 * Notices only print if we are not redirecting to the cart page
		 */
		$response = [
			'fragments' => self::get_refreshed_fragments(),
			'cart_hash' => WC()->cart->get_cart_hash(),
			'notices'   => get_option( 'woocommerce_cart_redirect_after_add' ) !== 'yes' ? wc_print_notices( true ) : '',
		];

		wp_send_json( $response );
	}

	/**
	 * Same as WC_AJAX::get_refreshed_fragments() but without the cart_hash and cart_url fragments
	 *
	 * @since 1.8.4
	 */
	public static function get_refreshed_fragments() {
		ob_start();

		woocommerce_mini_cart();

		$mini_cart = ob_get_clean();

		return apply_filters(
			'woocommerce_add_to_cart_fragments',
			[
				'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
			]
		);
	}

	/**
	 * Take over the native WooCommerce AJAX add to cart button
	 *
	 * @since 7
	 */
	public function overwrite_native_ajax_add_to_cart( $args, $product ) {
		// Only for simple product, purchasable and in stock
		if ( ! ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) ) {
			return $args;
		}

		// Disable native ajax_add_to_cart class if enabled in WooCommerce settings
		$args['class'] = str_replace( 'ajax_add_to_cart', '', $args['class'] );

		// Add brx_ajax_add_to_cart class
		$args['class'] .= ' brx_ajax_add_to_cart';

		// Add product type attribute
		$args['attributes']['data-product_type'] = $product->get_type();

		return $args;
	}

	/**
	 * Check if use bricks woo notice element
	 *
	 * @since 1.8.1
	 * @return bool
	 */
	public static function use_bricks_woo_notice_element() {
		return Database::get_setting( 'woocommerceUseBricksWooNotice', false );
	}

	/**
	 * Remove all native woocommerce notices hooks if use Bricks woo notice element
	 * So user can control the location of notices via the Bricks woo notice element
	 *
	 * @since 1.8.1
	 * @see woocommerce/includes/wc-template-hooks.php Notices
	 */
	public static function maybe_remove_native_woocommerce_notices_hooks() {
		if ( ! self::use_bricks_woo_notice_element() ) {
			return;
		}

		// cart-empty.php
		remove_action( 'woocommerce_cart_is_empty', 'woocommerce_output_all_notices', 5 );

		remove_action( 'woocommerce_shortcode_before_product_cat_loop', 'woocommerce_output_all_notices', 10 );

		// archive-product.php
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
		remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices', 10 );

		// cart.php
		remove_action( 'woocommerce_before_cart', 'woocommerce_output_all_notices', 10 );

		// This hook is fired when using the [woocommerce_checkout] shortcode
		remove_action( 'woocommerce_before_checkout_form_cart_notices', 'woocommerce_output_all_notices', 10 );

		// form-checkout.php
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );

		// inside shortcode [woocommerce_checkout] order_pay
		remove_action( 'before_woocommerce_pay', 'woocommerce_output_all_notices', 10 );

		// my-account.php
		remove_action( 'woocommerce_account_content', 'woocommerce_output_all_notices', 5 );

		// myaccount/form-login.php
		remove_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10 );

		// myaccount/form-lost-password.php
		remove_action( 'woocommerce_before_lost_password_form', 'woocommerce_output_all_notices', 10 );

		// myaccount/form-reset-password.php
		remove_action( 'woocommerce_before_reset_password_form', 'woocommerce_output_all_notices', 10 );

	}

	/**
	 * Remove WooCommerce hook actions to avoid duplicate content
	 *
	 * @since 1.7
	 *
	 * @param string   $action
	 * @param array    $filters
	 * @param string   $context
	 * @param \WP_Post $post
	 */
	public function maybe_remove_woo_hook_actions( $action, $filters, $context, $post ) {
		$template = Woocommerce_Helpers::get_repeated_wc_template_hooks_by_action( $action );

		// STEP: Exit if not supported template
		if ( empty( $template ) ) {
			return;
		}

		$template_name = array_keys( $template )[0];

		// STEP: Remove native woo hook actions
		Woocommerce_Helpers::execute_actions_in_wc_template( $template_name, 'remove', $action );
	}

	/**
	 * Restore WooCommerce hooks
	 *
	 * @since 1.7
	 *
	 * @param string   $action
	 * @param array    $filters
	 * @param string   $context
	 * @param \WP_Post $post
	 * @param mixed    $value
	 */
	public function maybe_restore_woo_hook_actions( $action, $filters, $context, $post, $value ) {
		$template = Woocommerce_Helpers::get_repeated_wc_template_hooks_by_action( $action );

		// STEP: Exit if not supported template
		if ( empty( $template ) ) {
			return;
		}

		$template = array_keys( $template )[0];

		// STEP: Restore native woo hook actions
		Woocommerce_Helpers::execute_actions_in_wc_template( $template, 'add', $action );
	}

	/**
	 * Add bricks-woo-{template} body class to the body
	 * Add woocommerce body classes for templates (builder or preview)
	 *
	 * Woo Phase 3
	 */
	public function maybe_set_body_class( $classes ) {
		/**
		 * When editing or previewing Woo templates, the woo body classes are not added
		 *
		 * So we need to add them manually.
		 */
		$post_id = get_the_ID();

		if ( Helpers::is_bricks_template( $post_id ) ) {
			$type      = Templates::get_template_type( $post_id );
			$classes[] = "bricks-woo-$type";

			switch ( $type ) {
				case 'wc_cart':
				case 'wc_cart_empty':
					$classes[] = 'woocommerce-cart';
					break;

				case 'wc_form_checkout':
				case 'wc_form_pay':
				case 'wc_thankyou':
				case 'wc_order_receipt':
					$classes[] = 'woocommerce-checkout';
					break;

				case 'wc_account_dashboard':
				case 'wc_account_orders':
				case 'wc_account_view_order':
				case 'wc_account_downloads':
				case 'wc_account_addresses':
				case 'wc_account_form_edit_address':
				case 'wc_account_form_edit_account':
				case 'wc_account_form_login':
				case 'wc_account_form_lost_password':
				case 'wc_account_form_lost_password_confirmation':
				case 'wc_account_reset_password':
					$classes[] = 'woocommerce-account';
					break;
			}
		}

		return $classes;
	}

	/**
	 * Add .woocommerce class to the main tag (#brx-content) when previewing woo templates in frontend OR if the current page is my account page
	 *
	 * Otherwise not all Woo CSS & JS is applied. In builder, we add this class inside TheDynamicArea.vue
	 *
	 * Woo Phase 3
	 */
	public function template_preview_main_classes( $attributes ) {
		$post_id         = get_the_ID();
		$is_account_page = is_account_page();

		if ( ! Helpers::is_bricks_template( $post_id ) && ! $is_account_page ) {
			return $attributes;
		}

		$template_type = Templates::get_template_type( get_the_ID() );

		if ( strpos( $template_type, 'wc_' ) === false && ! $is_account_page ) {
			return $attributes;
		}

		// NOTE: Adds 20px padding to .woocommerce-cart .woocommerce (on tablet portrait), when previewing the template in frontend.
		$attributes['class'][] = 'woocommerce';

		return $attributes;
	}

	/**
	 * Check if use quantity in loop
	 *
	 * @return bool
	 * @since 1.9
	 */
	public static function use_quantity_in_loop() {
		return Database::get_setting( 'woocommerceUseQtyInLoop', false );
	}

	/**
	 * Add quantity input field to loop
	 *
	 * Support product types: simple
	 *
	 * @since 1.9
	 */
	public function add_quantity_input_field( $html, $product ) {
		if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
			$quantity_args = [
				'min_value' => 1,
				'max_value' => $product->get_max_purchase_quantity(),
			];

			$new_html  = '<form action="' . esc_url( $product->add_to_cart_url() ) . '" class="cart brx-loop-product-form" method="post" enctype="multipart/form-data">';
			$new_html .= woocommerce_quantity_input( $quantity_args, $product, false );
			$new_html .= $html;
			$new_html .= '</form>';

			return $new_html;
		}

		return $html;
	}

	/**
	 * Get $args for password reset form via
	 *
	 * Used in Account page & reset password form template.
	 *
	 * @see Woo core lost_password()
	 * @since 1.9
	 */
	public static function get_reset_password_args() {
		$args = [
			'key'   => '',
			'login' => '',
		];

		// Get key & login for Woo template $args from cookie (@see Woo core lost_password())
		if ( isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) && 0 < strpos( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ], ':' ) ) {
			list( $rp_id, $rp_key ) = array_map( 'wc_clean', explode( ':', wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ), 2 ) );
			$userdata               = get_userdata( absint( $rp_id ) );
			$rp_login               = $userdata ? $userdata->user_login : '';

			$args['key']   = $rp_key;
			$args['login'] = $rp_login;
		}

		return $args;
	}
}
