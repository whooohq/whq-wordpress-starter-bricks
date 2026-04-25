<?php

use WCML\Utilities\WcAdminPages;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;

/**
 * Class WCML_Table_Rate_Shipping
 */
class WCML_Table_Rate_Shipping implements \IWPML_Action {

	/**
	 * @var SitePress
	 */
	public $sitepress;

	/**
	 * @var woocommerce_wpml
	 */
	public $woocommerce_wpml;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	const PRIORITY_BEFORE_DELETE = 5;

	// Database saving for rates happens on woocommerce_settings_shipping:10.
	const PRIORITY_REGISTER_RATE_LABELS = 11;

	const RATE_SHIPPING_METHOD_ID = 'table_rate';
	// The first placeholder is the instance ID and the second is the rate ID.
	const RATE_LABEL_NAME_FORMAT = 'table_rate%1$s%2$s_shipping_method_title';
	// The placeholder is the rate ID.
	const RATE_ABORT_REASON_NAME_FORMAT = 'table_rate_shipping_abort_reason_%s';

	/**
	 * WCML_Table_Rate_Shipping constructor.
	 *
	 * @param SitePress        $sitepress
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param wpdb             $wpdb
	 */
	public function __construct( SitePress $sitepress, woocommerce_wpml $woocommerce_wpml, wpdb $wpdb ) {
		$this->sitepress        = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {

		if ( ! is_admin() ) {
			add_filter( 'get_the_terms', [ $this, 'shipping_class_id_in_default_language' ], 10, 3 );
			add_filter( 'woocommerce_shipping_table_rate_is_available', [ $this, 'shipping_table_rate_is_available' ], 10, 3 );
		}

		if ( is_admin() ) {
			add_action( 'woocommerce_settings_shipping', [ $this, 'registerShippingRatesStrings' ], self::PRIORITY_REGISTER_RATE_LABELS );
			add_action( 'wp_ajax_woocommerce_table_rate_delete', [ $this, 'unregister_abort_messages_ajax' ], self::PRIORITY_BEFORE_DELETE );
			add_action( 'delete_product_shipping_class', [ $this, 'unregister_abort_messages_shipping_class' ], self::PRIORITY_BEFORE_DELETE );
		}
		add_filter( 'woocommerce_table_rate_query_rates', [ $this, 'translate_abort_messages' ] );

		add_filter( 'wcml_order_item_shipping_method_translators', [ $this, 'registerOrderShippingMethodTranslator' ] );
	}

	/**
	 * @todo We have a mechanism to unregister abort messages when deleting a rate; we could also unregister the label.
	 * @todo Note that the RATE_LABEL_NAME_FORMAT can produce non unique names: instanceId = 11, rateId = 1 versus instanceId = 1, rateId = 11.
	 */
	public function registerShippingRatesStrings() {
		// phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$isEditingShippingInstance         = WcAdminPages::isShippingSettings() && isset( $_GET['instance_id'] );
		$isSavingTRSInstanceWithTableRates = isset( $_POST['rate_id'] );
		$canGetStoredTableRates            = class_exists( '\WooCommerce\Shipping\Table_Rate\Helpers' );

		if ( ! (
			$isEditingShippingInstance
			&& $isSavingTRSInstanceWithTableRates
			&& $canGetStoredTableRates
		) ) {
			return;
		}

		$instanceId = (int) $_GET['instance_id'];
		$rates      = \WooCommerce\Shipping\Table_Rate\Helpers::get_shipping_rates( $instanceId, ARRAY_A );

		if ( ! is_array( $rates ) ) {
			return;
		}

		/** @var callable(array):void $registerLabel */
		$registerLabel = function( $rate ) use ( $instanceId ) {
			do_action(
				'wpml_register_single_string',
				WCML_WC_Shipping::STRINGS_CONTEXT,
				sprintf( self::RATE_LABEL_NAME_FORMAT, $instanceId, $rate['rate_id'] ),
				$rate['rate_label']
			);
		};

		wpml_collect( $rates )
			->filter( Obj::prop( 'rate_label' ) )
			->each( $registerLabel );

		/** @var callable $registerAbortReason */
		$registerAbortReason = function( $rate ) {
			do_action(
				'wpml_register_single_string',
				WCML_WC_Shipping::STRINGS_CONTEXT,
				sprintf( self::RATE_ABORT_REASON_NAME_FORMAT, $rate['rate_id'] ),
				$rate['rate_abort_reason']
			);
		};

		wpml_collect( $rates )
			->filter( Obj::prop( 'rate_abort_reason' ) )
			->each( $registerAbortReason );
	}

	/**
	 * @param WP_Term[] $terms
	 * @param int       $post_id
	 * @param string    $taxonomy
	 *
	 * @return WP_Term[]
	 */
	public function shipping_class_id_in_default_language( $terms, $post_id, $taxonomy ) {
		global $icl_adjust_id_url_filter_off;

		$is_product_object = 'product' === get_post_type( $post_id ) || 'product_variation' === get_post_type( $post_id );
		if ( $terms && $is_product_object && 'product_shipping_class' === $taxonomy ) {

			if ( is_admin() ) {
				$shipp_class_language = $this->woocommerce_wpml->products->get_original_product_language( $post_id );
			} else {
				$shipp_class_language = $this->sitepress->get_default_language();
			}

			$cache_key  = md5( wp_json_encode( $terms ) );
			$cache_key .= ':' . $post_id . $shipp_class_language;

			$cache_group = 'trnsl_shipping_class';
			$cache_terms = wp_cache_get( $cache_key, $cache_group );

			if ( $cache_terms ) {
				return $cache_terms;
			}

			foreach ( $terms as $k => $term ) {

				$shipping_class_id = apply_filters( 'wpml_object_id', $term->term_id, 'product_shipping_class', false, $shipp_class_language );

				$icl_adjust_id_url_filter     = $icl_adjust_id_url_filter_off;
				$icl_adjust_id_url_filter_off = true;

				$terms[ $k ] = get_term( $shipping_class_id, 'product_shipping_class' );

				$icl_adjust_id_url_filter_off = $icl_adjust_id_url_filter;
			}

			wp_cache_set( $cache_key, $terms, $cache_group );
		}

		return $terms;
	}

	public function show_pointer_info() {
		$pointerFactory = new WCML\PointerUi\Factory();
		$dashboardUrl   = \WCML\Utilities\AdminUrl::getWPMLTMDashboardStringDomain( \WCML_WC_Shipping::STRINGS_CONTEXT );

		$pointerFactory
			->create( [
				'content'    => sprintf(
					/* translators: %1$s and %2$s are opening and closing HTML link tags */
					esc_html__( 'To translate the Method Title, go to the %1$sTranslation Dashboard%2$s.', 'woocommerce-multilingual' ),
					'<a href="' . esc_url( $dashboardUrl ) . '">',
					'</a>'
				),
				'selectorId' => 'woocommerce_table_rate_title',
				'docLink'    => WCML_Tracking_Link::getWcmlTableRateShippingDoc(),
			] )
			->show();

		$pointerFactory
			->create( [
				'content'    => sprintf(
					/* translators: %1$s and %2$s are opening and closing HTML link tags */
					esc_html__( 'To translate Labels, go to the %1$sTranslation Dashboard%2$s.', 'woocommerce-multilingual' ),
					'<a href="' . esc_url( $dashboardUrl ) . '">',
					'</a>'
				),
				'selectorId' => 'shipping_rates',
				'docLink'    => WCML_Tracking_Link::getWcmlTableRateShippingDoc(),
			] )
			->show();
	}

	/**
	 * Unregister the deleted rate's shipping abort reasons when deleted via AJAX.
	 */
	public function unregister_abort_messages_ajax() {
		check_ajax_referer( 'delete-rate', 'security' );

		wpml_collect( (array) Obj::prop( 'rate_id', $_POST ) )
			->map( Fns::unary( 'intval' ) )
			->map( [ $this, 'unregister_abort_messages' ] );
	}

	/**
	 * Unregister the deleted rate's shipping abort reasons when the shipping class it's for is deleted.
	 *
	 * @param int $term_id
	 */
	public function unregister_abort_messages_shipping_class( $term_id ) {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$table = $this->wpdb->prefix . 'woocommerce_shipping_table_rates';
		$query = $this->wpdb->prepare(
			"SELECT rate_id FROM $table WHERE rate_class=%d",
			[ $term_id ]
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		wpml_collect( $this->wpdb->get_col( $query ) )
			->map( [ $this, 'unregister_abort_messages' ] );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Translate the rate's shipping abort reasons
	 *
	 * @param stdClass[] $rates
	 * @return stdClass[]
	 */
	public function translate_abort_messages( $rates ) {
		// translateAbortReason :: stdClass -> stdClass
		$translateAbortReason = function( $rate ) {
			return Obj::assoc(
				'rate_abort_reason',
				apply_filters(
					'wpml_translate_single_string',
					$rate->rate_abort_reason,
					WCML_WC_Shipping::STRINGS_CONTEXT,
					sprintf( self::RATE_ABORT_REASON_NAME_FORMAT, $rate->rate_id )
				),
				$rate
			);
		};

		return wpml_collect( $rates )
			->map( Logic::ifElse( Obj::prop( 'rate_abort_reason' ), $translateAbortReason, Fns::identity() ) )
			->toArray();
	}

	/**
	 * Unregister the deleted rate's shipping abort reasons for list of ids
	 *
	 * @param int $rate_id
	 */
	public function unregister_abort_messages( $rate_id ) {
		icl_unregister_string(
			WCML_WC_Shipping::STRINGS_CONTEXT,
			sprintf( self::RATE_ABORT_REASON_NAME_FORMAT, $rate_id )
		);
	}

	/**
	 * @param bool               $available
	 * @param array              $package
	 * @param WC_Shipping_Method $object
	 *
	 * @return bool
	 */
	public function shipping_table_rate_is_available( $available, $package, $object ) {

		add_filter(
			'option_woocommerce_table_rate_priorities_' . $object->instance_id,
			[ $this, 'filter_table_rate_priorities' ]
		);
		remove_filter(
			'woocommerce_shipping_table_rate_is_available',
			[ $this, 'shipping_table_rate_is_available' ],
			10
		);

		$available = $object->is_available( $package );

		add_filter(
			'woocommerce_shipping_table_rate_is_available',
			[ $this, 'shipping_table_rate_is_available' ],
			10,
			3
		);

		return $available;
	}

	/**
	 * @param array $priorities
	 *
	 * @return array
	 */
	public function filter_table_rate_priorities( $priorities ) {

		foreach ( $priorities as $slug => $priority ) {

			$shipping_class_term = get_term_by( 'slug', $slug, 'product_shipping_class' );
			if ( $shipping_class_term->slug !== $slug ) {
				unset( $priorities[ $slug ] );
				$priorities[ $shipping_class_term->slug ] = $priority;
			}
		}

		return $priorities;
	}

	/**
	 * Register the translator for shipping order items coming from table rates.
	 *
	 * @param array $translators
	 *
	 * @return array
	 */
	public function registerOrderShippingMethodTranslator( $translators ) {
		$translators[ self::RATE_SHIPPING_METHOD_ID ] = \WCML\Compatibility\TableRateShipping\OrderItems\ShippingRate::class;
		return $translators;
	}
}
