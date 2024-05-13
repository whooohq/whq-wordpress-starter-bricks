<?php

use WCML\Multicurrency\Transient\Hooks as TransientHooks;

/**
 * Class WCML_Currencies_Payment_Gateways
 */
class WCML_Currencies_Payment_Gateways {

	const OPTION_KEY = 'wcml_custom_payment_gateways_for_currencies';

	/** @var WCML_Payment_Gateway[] */
	private $payment_gateways;

	/** @var array */
	private $available_gateways;

	/** @var array */
	private $supported_gateways;

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var WPML_WP_API */
	private $wp_api;

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param WPML_WP_API      $wp_api
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, WPML_WP_API $wp_api ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wp_api           = $wp_api;
	}

	public function add_hooks() {
		add_action( 'wp_loaded', [ $this, 'init_gateways' ] );

		add_filter( 'woocommerce_gateway_description', [ $this, 'filter_gateway_description' ], 10, 2 );
		add_filter( 'option_woocommerce_stripe_settings', [ 'WCML_Payment_Gateway_Stripe', 'filter_stripe_settings' ] );
		add_filter( 'option_woocommerce-ppcp-settings', [ 'WCML_Payment_Gateway_PayPal_V2', 'filter_ppcp_args' ] );

		TransientHooks::addHooks( WCML_Payment_Gateway_PayPal_V2::BEARER_TOKEN_TRANSIENT );

		if ( ! is_admin() && wcml_is_multi_currency_on() ) {
			add_filter(
				'woocommerce_paypal_supported_currencies',
				[ 'WCML_Payment_Gateway_PayPal', 'filter_supported_currencies' ]
			);
		}
	}

	/**
	 * @param string $currency
	 *
	 * @return bool
	 */
	public function is_enabled( $currency ) {
		$gateway_enabled_settings = $this->get_settings();

		if ( isset( $gateway_enabled_settings[ $currency ] ) ) {
			return $gateway_enabled_settings[ $currency ];
		}

		return false;
	}

	/**
	 * @param string $currency
	 * @param bool   $value
	 */
	public function set_enabled( $currency, $value ) {
		$gateway_enabled_settings              = $this->get_settings();
		$gateway_enabled_settings[ $currency ] = $value;

		update_option( self::OPTION_KEY, $gateway_enabled_settings );
	}

	/**
	 * @return array
	 */
	private function get_settings() {
		return get_option( self::OPTION_KEY, [] );
	}

	public function init_gateways() {
		if ( null !== $this->payment_gateways ) {
			return;
		}

		$this->payment_gateways   = [];
		$this->available_gateways = [];
		$this->supported_gateways = [];

		do_action( 'wcml_before_init_currency_payment_gateways' );

		$this->available_gateways = $this->get_available_payment_gateways();

		$this->supported_gateways = [
			'bacs'         => 'WCML_Payment_Gateway_Bacs',
			'paypal'       => 'WCML_Payment_Gateway_PayPal',
			'ppcp-gateway' => 'WCML_Payment_Gateway_PayPal_V2',
			'stripe'       => 'WCML_Payment_Gateway_Stripe',
		];
		$this->supported_gateways = apply_filters( 'wcml_supported_currency_payment_gateways', $this->supported_gateways );

		$this->store_supported_gateways();
		$this->store_non_supported_gateways();
	}

	/**
	 * @return array
	 */
	public function get_gateways() {
		$this->init_gateways();

		return $this->payment_gateways;
	}

	/**
	 * @return array
	 */
	public function get_supported_gateways() {
		$this->init_gateways();

		return $this->supported_gateways;
	}

	/**
	 * @param string $description
	 * @param string $id
	 *
	 * @return string
	 */
	public function filter_gateway_description( $description, $id ) {
		$this->init_gateways();

		if ( in_array( $id, array_keys( $this->supported_gateways ), true ) ) {

			$client_currency  = $this->woocommerce_wpml->multi_currency->get_client_currency();
			$default_currency = wcml_get_woocommerce_currency_option();

			if ( $client_currency === $default_currency ) {
				return $description;
			}

			$gateway_setting   = $this->payment_gateways[ $id ]->get_setting( $client_currency );
			$active_currencies = $this->woocommerce_wpml->multi_currency->get_currency_codes();

			if (
				$this->is_enabled( $client_currency ) &&
				$gateway_setting &&
				$client_currency !== $gateway_setting['currency'] &&
				in_array( $gateway_setting['currency'], $active_currencies, true )
			) {
				$cart_total = $this->woocommerce_wpml->cart->format_converted_cart_total_in_currency( $gateway_setting['currency'] );

				$description .= '<p>';
				$description .= sprintf(
					// translators: 1: Currency, 2: Cart total.
					__( 'Please note that the payment will be made in %1$s. Your total will be approximately %2$s, depending on the current exchange rate.', 'woocommerce-multilingual' ),
					$gateway_setting['currency'],
					$cart_total
				);
				$description .= '</p>';
			}
		}

		return $description;
	}

	/**
	 * @param string $id
	 * @param object $supported_gateway
	 *
	 * @return bool
	 */
	private function is_a_valid_gateway( $id, $supported_gateway ) {
		return is_subclass_of( $supported_gateway, 'WCML_Payment_Gateway' ) && array_key_exists( $id, $this->available_gateways );
	}

	private function store_supported_gateways() {
		if ( is_array( $this->supported_gateways ) ) {
			$client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();
			foreach ( $this->supported_gateways as $id => $supported_gateway ) {
				if ( $this->is_a_valid_gateway( $id, $supported_gateway ) ) {
					$this->payment_gateways[ $id ] = new $supported_gateway(
						$this->available_gateways[ $id ],
						$this->woocommerce_wpml
					);
					if ( $this->is_enabled( $client_currency ) ) {
						$this->payment_gateways[ $id ]->add_hooks();
					}
				}
			}
		}
	}

	private function store_non_supported_gateways() {
		$non_supported_gateways = array_diff( array_keys( $this->available_gateways ), array_keys( $this->payment_gateways ) );

		/** @var int $non_supported_gateway */
		foreach ( $non_supported_gateways as $non_supported_gateway ) {
			$this->payment_gateways[ $non_supported_gateway ] = new WCML_Not_Supported_Payment_Gateway( $this->available_gateways[ $non_supported_gateway ], $this->woocommerce_wpml );
		}
	}

	/**
	 * @return array
	 */
	private function get_available_payment_gateways() {
		return WC()->payment_gateways()->get_available_payment_gateways();
	}
}
