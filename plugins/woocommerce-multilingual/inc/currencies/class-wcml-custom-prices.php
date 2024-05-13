<?php

use WCML\Utilities\DB;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use function WCML\functions\isStandAlone;
use function WPML\FP\invoke;

class WCML_Custom_Prices {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var wpdb */
	private $wpdb;

	public function __construct( woocommerce_wpml $woocommerce_wpml, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {
		add_filter( 'init', [ $this, 'custom_prices_init' ] );
	}

	public function custom_prices_init() {

		if ( is_admin() ) {
			if ( isStandAlone() ) {
				// In the full mode, this is done in the product sync logic.
				add_action( 'save_post_product', [ $this, 'save_custom_prices' ] );
				add_action( 'save_post_product_variation', [ $this, 'sync_product_variations_custom_prices' ] );
				add_action( 'woocommerce_ajax_save_product_variations', [ $this, 'sync_product_variations_custom_prices_on_ajax' ] );
			}

			add_action( 'woocommerce_variation_options', [ $this, 'add_individual_variation_nonce' ], 10, 3 );

			// custom prices for different currencies for products/variations [BACKEND].
			add_action( 'woocommerce_product_options_pricing', [ $this, 'woocommerce_product_options_custom_pricing' ] );
			add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'woocommerce_product_after_variable_attributes_custom_pricing' ], 10, 3 );

		} else {
			add_filter( 'woocommerce_product_is_on_sale', [ $this, 'filter_product_is_on_sale' ], 10, 2 );
		}

		add_action( 'woocommerce_variation_is_visible', [ $this, 'filter_product_variations_with_custom_prices' ], 10, 2 );

		add_filter( 'loop_shop_post_in', [ $this, 'filter_products_with_custom_prices' ], 100 );
		add_filter( 'woocommerce_is_purchasable', [ $this, 'check_product_with_custom_prices' ], 10, 2 );

		add_action( 'wc_after_products_starting_sales', [ $this, 'maybe_set_sale_prices' ] );
		add_action( 'wc_after_products_ending_sales', [ $this, 'maybe_remove_sale_prices' ] );
	}

	public function add_individual_variation_nonce( $loop, $variation_data, $variation ) {

		wp_nonce_field( 'wcml_save_custom_prices_variation_' . $variation->ID, '_wcml_custom_prices_variation_' . $variation->ID . '_nonce' );

	}

	/**
	 * @param int  $product_id
	 * @param bool $currency
	 *
	 * @return array|false
	 */
	public function get_product_custom_prices( $product_id, $currency = false ) {
		if ( empty( $currency ) ) {
			$currency = $this->woocommerce_wpml->multi_currency->get_client_currency();
		}

		if ( wcml_get_woocommerce_currency_option() === $currency ) {
			return false;
		}

		$cache_key           = $product_id . '_' . $currency;
		$cache_group         = 'product_custom_prices';
		$cache_found         = false;
		$cache_custom_prices = wp_cache_get( $cache_key, $cache_group, false, $cache_found );
		if ( $cache_found ) {
			return $cache_custom_prices;
		}

		$product_meta  = get_post_custom( $this->woocommerce_wpml->products->get_original_product_id( $product_id ) );
		$custom_prices = [];

		if ( ! empty( $product_meta['_wcml_custom_prices_status'][0] ) ) {
			foreach ( wcml_price_custom_fields( $product_id ) as $key ) {
				if ( isset( $product_meta[ $key . '_' . $currency ][0] ) ) {
					$custom_prices[ $key ] = $product_meta[ $key . '_' . $currency ][0];
				}
			}
		}

		if ( ! isset( $custom_prices['_price'] ) ) {
			return false;
		}

		$current__price_value = $custom_prices['_price'];

		if ( $this->is_date_range_set( $product_meta, $currency ) && ! $this->is_on_sale_date_range( $product_meta, $currency ) ) {
			$custom_prices['_sale_price'] = '';
			$custom_prices['_price']      = $custom_prices['_regular_price'];
		}

		if ( $custom_prices['_price'] !== $current__price_value ) {
			update_post_meta( $product_id, '_price_' . $currency, $custom_prices['_price'] );
		}

		// detemine min/max variation prices.
		if ( ! empty( $product_meta['_min_variation_price'] ) ) {

			static $product_min_max_prices = [];

			if ( ! isset( $product_min_max_prices[ $product_id ] ) && 'product' === get_post_type( $product_id ) ) {

				$product_min_max_prices[ $product_id ] = [];

				// get variation ids.
				$variation_ids = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT ID FROM {$this->wpdb->posts} WHERE post_parent = %d", $product_id ) );

				// get all prices for the above variations.
				$rows = $this->wpdb->get_results(
					"SELECT post_id, meta_key, meta_value FROM {$this->wpdb->postmeta}
					WHERE meta_key IN ('_price', '_regular_price', '_sale_price', '_price_$currency', '_regular_price_$currency', '_sale_price_$currency')
						AND post_id IN (" . DB::prepareIn( $variation_ids, '%d' ) . ')'
				);

				// $extractPricesByType :: array, string => array
				$extractPricesByType = function( $rows, $key ) use ( $currency, $variation_ids ) {
					$prices   = wp_list_pluck( wp_list_filter( $rows, [ 'meta_key' => $key . '_' . $currency ] ), $key . '_' . $currency, 'post_id' );
					$defaults = wp_list_pluck( wp_list_filter( $rows, [ 'meta_key' => $key ] ), $key, 'post_id' );

					// calculate missing prices automatically.
					foreach ( $variation_ids as $id ) {
						if ( empty( $prices[ $id ] ) && isset( $defaults[ $id ] ) ) {
							$prices[ $id ] = apply_filters( 'wcml_raw_price_amount', $defaults[ $id ] );
						}
					}

					return $prices;
				};

				$regular_prices = $extractPricesByType( $rows, '_regular_price' );
				$sale_prices    = $extractPricesByType( $rows, '_sale_price' );
				$prices         = $extractPricesByType( $rows, '_price' );

				if ( ! empty( $regular_prices ) ) {
					$product_min_max_prices[ $product_id ]['_min_variation_regular_price'] = min( $regular_prices );
					$product_min_max_prices[ $product_id ]['_max_variation_regular_price'] = max( $regular_prices );
				}

				if ( ! empty( $sale_prices ) ) {
					$product_min_max_prices[ $product_id ]['_min_variation_sale_price'] = min( $sale_prices );
					$product_min_max_prices[ $product_id ]['_max_variation_sale_price'] = max( $sale_prices );
				}

				if ( ! empty( $prices ) ) {
					$product_min_max_prices[ $product_id ]['_min_variation_price'] = min( $prices );
					$product_min_max_prices[ $product_id ]['_max_variation_price'] = max( $prices );
				}
			}

			if ( isset( $product_min_max_prices[ $product_id ]['_min_variation_regular_price'] ) ) {
				$custom_prices['_min_variation_regular_price'] = $product_min_max_prices[ $product_id ]['_min_variation_regular_price'];
			}

			if ( isset( $product_min_max_prices[ $product_id ]['_max_variation_regular_price'] ) ) {
				$custom_prices['_max_variation_regular_price'] = $product_min_max_prices[ $product_id ]['_max_variation_regular_price'];
			}

			if ( isset( $product_min_max_prices[ $product_id ]['_min_variation_sale_price'] ) ) {
				$custom_prices['_min_variation_sale_price'] = $product_min_max_prices[ $product_id ]['_min_variation_sale_price'];
			}
			if ( isset( $product_min_max_prices[ $product_id ]['_max_variation_sale_price'] ) ) {
				$custom_prices['_max_variation_sale_price'] = $product_min_max_prices[ $product_id ]['_max_variation_sale_price'];
			}

			if ( isset( $product_min_max_prices[ $product_id ]['_min_variation_price'] ) ) {
				$custom_prices['_min_variation_price'] = $product_min_max_prices[ $product_id ]['_min_variation_price'];
			}
			if ( isset( $product_min_max_prices[ $product_id ]['_max_variation_price'] ) ) {
				$custom_prices['_max_variation_price'] = $product_min_max_prices[ $product_id ]['_max_variation_price'];
			}
		}

		$custom_prices = apply_filters( 'wcml_product_custom_prices', $custom_prices, $product_id, $currency );

		wp_cache_set( $cache_key, $custom_prices, $cache_group );

		return $custom_prices;
	}

	private function is_date_range_set( $product_meta, $currency ) {

		$get_currency_schedule = function ( $suffix ) use ( $product_meta ) {
			return Obj::path( [
					'_sale_price_dates_from' . $suffix,
					0
				], $product_meta ) || Obj::path( [ '_sale_price_dates_to' . $suffix, 0 ], $product_meta );
		};

		return $get_currency_schedule( '' ) || $get_currency_schedule( "_$currency" );
	}

	private function is_on_sale_date_range( $product_meta, $currency ) {

		if (
			isset( $product_meta[ '_sale_price_dates_from_' . $currency ] ) &&
			isset( $product_meta[ '_sale_price_dates_to_' . $currency ] ) &&
			$product_meta[ '_sale_price_dates_from_' . $currency ][0] &&
			$product_meta[ '_sale_price_dates_to_' . $currency ][0]
		) {

			if (
				current_time( 'timestamp' ) > $product_meta[ '_sale_price_dates_from_' . $currency ][0] &&
				current_time( 'timestamp' ) < $product_meta[ '_sale_price_dates_to_' . $currency ][0]
			) {
				return true;
			}
		} elseif (
			isset( $product_meta['_sale_price_dates_from'] ) &&
			isset( $product_meta['_sale_price_dates_to'] ) &&
			current_time( 'timestamp' ) > $product_meta['_sale_price_dates_from'][0] &&
			current_time( 'timestamp' ) < $product_meta['_sale_price_dates_to'][0]
		) {
			return true;
		}

		return false;
	}

	public function woocommerce_product_options_custom_pricing() {
		global $pagenow;

		$this->load_custom_prices_js_css();

		if ( ( isset( $_GET['post'] ) && ( get_post_type( $_GET['post'] ) !== 'product' || ! $this->woocommerce_wpml->products->is_original_product( $_GET['post'] ) ) ) ||
			( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'product' && isset( $_GET['source_lang'] ) ) ) {
			return;
		}

		$product_id = 'new';

		if ( $pagenow === 'post.php' && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'product' ) {
			$product_id = $_GET['post'];
		}

		$this->custom_pricing_output( $product_id );

		do_action( 'wcml_after_custom_prices_block', $product_id );

		wp_nonce_field( 'wcml_save_custom_prices', '_wcml_custom_prices_nonce' );

	}

	public function woocommerce_product_after_variable_attributes_custom_pricing( $loop, $variation_data, $variation ) {

		if ( $this->woocommerce_wpml->products->is_original_product( $variation->post_parent ) ) {

			echo '<tr><td>';
			$this->custom_pricing_output( $variation->ID );
			echo '</td></tr>';

		}

	}

	private function load_custom_prices_js_css() {
		wp_register_style( 'wpml-wcml-prices', WCML_PLUGIN_URL . '/res/css/wcml-prices.css', null, WCML_VERSION );
		wp_register_script( 'wcml-tm-scripts-prices', WCML_PLUGIN_URL . '/res/js/prices' . WCML_JS_MIN . '.js', [ 'jquery' ], WCML_VERSION, true );

		wp_enqueue_style( 'wpml-wcml-prices' );
		wp_enqueue_script( 'wcml-tm-scripts-prices' );
	}

	private function custom_pricing_output( $post_id = false ) {

		$custom_prices_ui = new WCML_Custom_Prices_UI( $this->woocommerce_wpml, $post_id );
		$custom_prices_ui->show();

	}

	public function check_product_with_custom_prices( $is_purchasable, WC_Product $product ) {

		if ( $this->is_filtering_products_with_custom_prices_enabled() && is_product() ) {

			$original_id = $this->woocommerce_wpml->products->get_original_product_id( $product->get_id() );

			if ( ! $this->is_custom_prices_set_for_product( $original_id ) ) {
				return false;
			}
		}

		return $is_purchasable;
	}

	public function filter_product_variations_with_custom_prices( $is_visible, $variation_id ) {

		if ( $this->is_filtering_products_with_custom_prices_enabled() && is_product() ) {

			$orig_child_id = $this->woocommerce_wpml->products->get_original_product_id( $variation_id );

			if ( ! $this->is_custom_prices_set_for_product( $orig_child_id ) ) {
				return false;
			}
		}

		return $is_visible;
	}

	// display products with custom prices only if enabled "Show only products with custom prices in secondary currencies" option on settings page.
	public function filter_products_with_custom_prices( $filtered_posts ) {

		if ( $this->is_filtering_products_with_custom_prices_enabled() ) {

			$matched_products       = [];
			$matched_products_query = $this->wpdb->get_results(
				"
	        	SELECT DISTINCT ID, post_parent, post_type FROM {$this->wpdb->posts}
				INNER JOIN {$this->wpdb->postmeta} ON ID = post_id
				WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = '_wcml_custom_prices_status' AND meta_value = 1
			",
				OBJECT_K
			);

			if ( $matched_products_query ) {

				$client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();

				remove_filter( 'get_post_metadata', [ $this->woocommerce_wpml->multi_currency->prices, 'product_price_filter' ], 10 );
				foreach ( $matched_products_query as $product ) {
					if ( ! get_post_meta( $product->ID, '_price_' . $client_currency, true ) ) {
						continue;
					}
					if ( $product->post_type == 'product' ) {
						$matched_products[] = apply_filters( 'translate_object_id', $product->ID, 'product', true );
					}
					if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) ) {
						$matched_products[] = apply_filters( 'translate_object_id', $product->post_parent, get_post_type( $product->post_parent ), true );
					}
				}
				add_filter( 'get_post_metadata', [ $this->woocommerce_wpml->multi_currency->prices, 'product_price_filter' ], 10, 4 );
			}

			// Filter the id's.
			if ( sizeof( $filtered_posts ) == 0 ) {
				$filtered_posts   = $matched_products;
				$filtered_posts[] = 0;
			} else {
				$filtered_posts   = array_intersect( $filtered_posts, $matched_products );
				$filtered_posts[] = 0;
			}
		}

		return $filtered_posts;
	}

	/**
	 * @return bool
	 */
	private function is_filtering_products_with_custom_prices_enabled() {

		return wcml_is_multi_currency_on() &&
		       isset( $this->woocommerce_wpml->settings['display_custom_prices'] ) &&
		       $this->woocommerce_wpml->settings['display_custom_prices'] &&
		       $this->woocommerce_wpml->multi_currency->get_client_currency() !== wcml_get_woocommerce_currency_option();
	}

	public function save_custom_prices( $post_id ) {
		$nonce = filter_input( INPUT_POST, '_wcml_custom_prices_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( isset( $_POST['_wcml_custom_prices'] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices' ) && ! $this->woocommerce_wpml->products->is_variable_product( $post_id ) ) {
			if ( isset( $_POST['_wcml_custom_prices'][ $post_id ] ) || isset( $_POST['_wcml_custom_prices']['new'] ) ) {
				$wcml_custom_prices_option = isset( $_POST['_wcml_custom_prices'][ $post_id ] ) ? $_POST['_wcml_custom_prices'][ $post_id ] : $_POST['_wcml_custom_prices']['new'];
			} else {
				$current_option            = get_post_meta( $post_id, '_wcml_custom_prices_status', true );
				$wcml_custom_prices_option = $current_option ? $current_option : 0;
			}
			update_post_meta( $post_id, '_wcml_custom_prices_status', $wcml_custom_prices_option );

			if ( $wcml_custom_prices_option == 1 ) {
				$currencies = $this->woocommerce_wpml->multi_currency->get_currencies();
				foreach ( $currencies as $code => $currency ) {
					$sale_price    = wc_format_decimal( $_POST['_custom_sale_price'][ $code ] );
					$regular_price = wc_format_decimal( $_POST['_custom_regular_price'][ $code ] );
					$schedule      = $_POST['_wcml_schedule'][ $code ];
					$date_from     = $schedule && isset( $_POST['_custom_sale_price_dates_from'][ $code ] ) ? strtotime( $_POST['_custom_sale_price_dates_from'][ $code ] ) : '';
					$date_to       = $schedule && isset( $_POST['_custom_sale_price_dates_to'][ $code ] ) ? strtotime( $_POST['_custom_sale_price_dates_to'][ $code ] ) : '';

					$custom_prices = apply_filters(
						'wcml_update_custom_prices_values',
						[
							'_regular_price'         => $regular_price,
							'_sale_price'            => $sale_price,
							'_wcml_schedule'         => $schedule,
							'_sale_price_dates_from' => $date_from,
							'_sale_price_dates_to'   => $date_to,
						],
						$code,
						$post_id
					);
					$product_price = $this->update_custom_prices( $post_id, $custom_prices, $code );

					do_action( 'wcml_after_save_custom_prices', $post_id, $product_price, $custom_prices, $code );
				}
			}
		}
	}

	public function update_custom_prices( $post_id, $custom_prices, $code ) {
		$price = null;

		$defaults = [
			'_sale_price_dates_to'   => '',
			'_sale_price_dates_from' => '',
			'_sale_price'            => '',
		];

		$custom_prices = array_merge( $defaults, $custom_prices );

		foreach ( $custom_prices as $custom_price_key => $custom_price_value ) {
			update_post_meta( $post_id, $custom_price_key . '_' . $code, $custom_price_value );
		}

		if ( $this->is_sale_price_valid( $custom_prices ) ) {
			$price = stripslashes( $custom_prices['_sale_price'] );
		} else {
			$price = stripslashes( $custom_prices['_regular_price'] );
			$this->validate_and_update_sale_price_dates( $post_id, $code, $custom_prices );
		}

		update_post_meta( $post_id, '_price_' . $code, $price ? $price : null );

		return $price;
	}

	/**
	 * @param int    $post_id
	 * @param string $code
	 * @param array  $custom_prices
	 */
	private function validate_and_update_sale_price_dates( $post_id, $code, array $custom_prices ) {
		$date_from = $custom_prices['_sale_price_dates_from'];
		$date_to   = $custom_prices['_sale_price_dates_to'];
		if ( $date_to ) {
			if ( $date_to < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $post_id, '_sale_price_dates_from_' . $code, '' );
				update_post_meta( $post_id, '_sale_price_dates_to_' . $code, '' );
			} elseif ( ! $date_from ) {
				update_post_meta(
					$post_id,
					'_sale_price_dates_from_' . $code,
					strtotime( 'NOW', current_time( 'timestamp' ) )
				);
			}
		}
	}

	/**
	 * @param array $custom_prices
	 *
	 * @return bool
	 */
	protected function is_sale_price_valid( array $custom_prices ) {
		$sale_price_dates_from = $custom_prices['_sale_price_dates_from'];
		$sale_price_dates_to   = $custom_prices['_sale_price_dates_to'];
		$not_depend_on_date    = $sale_price_dates_to === '' && $sale_price_dates_from === '';
		$valid_sale_date       = $sale_price_dates_from < strtotime( 'NOW', current_time( 'timestamp' ) );

		return $custom_prices['_sale_price'] !== '' && ( $not_depend_on_date || $valid_sale_date );
	}

	/**
	 * @param int $product_id
	 */
	public function sync_product_variations_custom_prices_on_ajax( $product_id ) {
		Maybe::fromNullable( wc_get_product( $product_id ) )
			->map( invoke( 'get_children' ) )
			->map( Fns::map( [ $this, 'sync_product_variations_custom_prices' ] ) );
	}

	/**
	 * @param int $product_id
	 */
	public function sync_product_variations_custom_prices( $product_id ) {

		if ( isset( $_POST['_wcml_custom_prices'][ $product_id ] ) ) {

			// save custom prices for variation.
			$nonce = filter_input( INPUT_POST, '_wcml_custom_prices_variation_' . $product_id . '_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( isset( $_POST['_wcml_custom_prices'][ $product_id ] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices_variation_' . $product_id ) ) {
				update_post_meta( $product_id, '_wcml_custom_prices_status', $_POST['_wcml_custom_prices'][ $product_id ] );
				$currencies = $this->woocommerce_wpml->multi_currency->get_currencies();

				if ( $_POST['_wcml_custom_prices'][ $product_id ] == 1 ) {
					foreach ( $currencies as $code => $currency ) {
						$sale_price    = wc_format_decimal( $_POST['_custom_variation_sale_price'][ $code ][ $product_id ] );
						$regular_price = wc_format_decimal( $_POST['_custom_variation_regular_price'][ $code ][ $product_id ] );
						$date_from     = strtotime( $_POST['_custom_variation_sale_price_dates_from'][ $code ][ $product_id ] );
						$date_to       = strtotime( $_POST['_custom_variation_sale_price_dates_to'][ $code ][ $product_id ] );
						$schedule      = $_POST['_wcml_schedule'][ $code ][ $product_id ];

						$custom_prices = apply_filters(
							'wcml_update_custom_prices_values',
							[
								'_regular_price'         => $regular_price,
								'_sale_price'            => $sale_price,
								'_wcml_schedule'         => $schedule,
								'_sale_price_dates_from' => $date_from,
								'_sale_price_dates_to'   => $date_to,
							],
							$code,
							$product_id
						);
						$price         = $this->update_custom_prices( $product_id, $custom_prices, $code );
					}
				}
			}
		}
	}

	/**
	 * @param WC_Product $product_object
	 *
	 * @return bool
	 */
	private function is_on_sale( $product_object ) {
		$custom_prices = $this->get_product_custom_prices( $product_object->get_id() );

		return $custom_prices
			   && array_key_exists( '_sale_price', $custom_prices )
			   && array_key_exists( '_regular_price', $custom_prices )
			   && '' !== $custom_prices['_sale_price']
			   && $custom_prices['_sale_price'] !== $custom_prices['_regular_price'];
	}

	/**
	 * @param bool       $on_sale
	 * @param WC_Product $product_object
	 *
	 * @return bool
	 */
	public function filter_product_is_on_sale( $on_sale, $product_object ) {
		if (
			! $on_sale &&
			WCML_MULTI_CURRENCIES_INDEPENDENT === $this->woocommerce_wpml->settings['enable_multi_currency'] &&
			$this->is_custom_prices_set_for_product( $product_object->get_id() )
		) {
			$on_sale = $this->is_on_sale( $product_object );
		}

		return $on_sale;
	}

	/**
	 * @param int $product_id
	 *
	 * @return mixed
	 */
	private function is_custom_prices_set_for_product( $product_id ) {
		return get_post_meta( $product_id, '_wcml_custom_prices_status', true );
	}

	/**
	 * WC when starts the sale copies price from _sale_price into _price field
	 * we should do the same for _sale_price_{currency} and _price_{currency}
	 *
	 * @param array $product_ids
	 */
	public function maybe_set_sale_prices( $product_ids ) {
		foreach ( $product_ids as $product_id ) {
			if ( $this->is_custom_prices_set_for_product( $product_id ) ) {
				foreach ( $this->woocommerce_wpml->multi_currency->get_currencies() as $code => $currency ) {
					update_post_meta( $product_id, '_price_' . $code, get_post_meta( $product_id, '_sale_price_' . $code, true ) );
				}
			}
		}
	}

	/**
	 * @param array $product_ids
	 */
	public function maybe_remove_sale_prices( $product_ids ) {

		foreach ( $product_ids as $product_id ) {

			$product            = wc_get_product( $product_id );
			$is_product_on_sale = $product && $product->get_sale_price();

			if ( $is_product_on_sale || ! $this->is_custom_prices_set_for_product( $product_id ) ) {
				continue;
			}

			foreach ( $this->woocommerce_wpml->multi_currency->get_currencies() as $code => $currency ) {

				$is_auto_schedule_set = ! get_post_meta( $product_id, '_wcml_schedule_' . $code, true );

				if ( $is_auto_schedule_set ) {
					update_post_meta( $product_id, '_price_' . $code, get_post_meta( $product_id, '_regular_price_' . $code, true ) );
					update_post_meta( $product_id, '_sale_price_' . $code, '' );
				}
			}
		}
	}
}
