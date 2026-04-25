<?php

/**
 * Class WCML_Payment_Gateway_Stripe
 */
class WCML_Payment_Gateway_Stripe extends WCML_Payment_Gateway {

	const ID = 'stripe';

	public function get_output_model() {
		return [
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'isSupported' => true,
			'settings'    => $this->get_currencies_details(),
			'tooltip'     => '',
			'strings'     => [
				'labelCurrency'           => __( 'Currency', 'woocommerce-multilingual' ),
				'labelLivePublishableKey' => __( 'Live Publishable Key', 'woocommerce-multilingual' ),
				'labelLiveSecretKey'      => __( 'Live Secret Key', 'woocommerce-multilingual' ),
				'labelTestPublishableKey' => __( 'Test Publishable Key', 'woocommerce-multilingual' ),
				'labelTestSecretKey'      => __( 'Test Secret Key', 'woocommerce-multilingual' ),
			],
		];
	}

	public function add_hooks() {
		$client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();

		if ( $this->is_client_currency_supported( $client_currency ) ) {
			add_filter( 'wc_stripe_generate_create_intent_request', [
				$this,
				'convert_stripe_payment_request'
			], 10, 3 );
		}
	}

	/**
	 * Convert currency to the one set in payment gateway
	 *
	 * @param array    $request
	 * @param WC_Order $order
	 * @param object   $source
	 */
	public function convert_stripe_payment_request( $request, $order, $source ) {
		$client_currency = $request ['currency'] ?? null;
		$client_currency = strtoupper( $client_currency );

		$convert_to_currency = $this->maybe_convert_currency( $client_currency );
		if ( null === $convert_to_currency ) {
			return $request;
		}

		if ( $client_currency !== $convert_to_currency ) {
			$convert_price = $this->get_convert_price_callable( $convert_to_currency );

			$request = $convert_price( $request );
		}

		return $request;
	}

	/**
	 * @return array
	 */
	public function get_currencies_details() {
		$currencies_details = [];
		$default_currency   = wcml_get_woocommerce_currency_option();
		$active_currencies  = get_woocommerce_currencies();

		foreach ( $active_currencies as $code => $currency ) {

			if ( $default_currency === $code ) {
				$currencies_details[ $code ]['currency']             = $code;
				$currencies_details[ $code ]['publishable_key']      = $this->get_gateway()->settings['publishable_key'];
				$currencies_details[ $code ]['secret_key']           = $this->get_gateway()->settings['secret_key'];
				$currencies_details[ $code ]['test_publishable_key'] = $this->get_gateway()->settings['test_publishable_key'];
				$currencies_details[ $code ]['test_secret_key']      = $this->get_gateway()->settings['test_secret_key'];
			} else {
				$currency_gateway_setting                            = $this->get_setting( $code );
				$currencies_details[ $code ]['currency']             = $currency_gateway_setting['currency'] ?? '';
				$currencies_details[ $code ]['publishable_key']      = $currency_gateway_setting['publishable_key'] ?? '';
				$currencies_details[ $code ]['secret_key']           = $currency_gateway_setting['secret_key'] ?? '';
				$currencies_details[ $code ]['test_publishable_key'] = $currency_gateway_setting['test_publishable_key'] ?? '';
				$currencies_details[ $code ]['test_secret_key']      = $currency_gateway_setting['test_secret_key'] ?? '';
			}
		}

		return $currencies_details;

	}

	/**
	 * Filter Stripe settings before WC initialized them
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function filter_stripe_settings( $settings ) {
		if ( is_admin() ) {
			return $settings;
		}

		global $woocommerce_wpml;

		$client_currency  = $woocommerce_wpml->multi_currency->get_client_currency();
		$gateway_settings = get_option( self::OPTION_KEY . self::ID, [] );

		if ( $gateway_settings && isset( $gateway_settings[ $client_currency ] ) ) {
			$gateway_setting = $gateway_settings[ $client_currency ];
			if ( ! empty( $gateway_setting['publishable_key'] ) && ! empty( $gateway_setting['secret_key'] ) ) {
				$settings['publishable_key'] = $gateway_setting['publishable_key'];
				$settings['secret_key']      = $gateway_setting['secret_key'];
			}
			if ( ! empty( $gateway_setting['test_publishable_key'] ) && ! empty( $gateway_setting['test_secret_key'] ) ) {
				$settings['test_publishable_key'] = $gateway_setting['test_publishable_key'];
				$settings['test_secret_key']      = $gateway_setting['test_secret_key'];
			}
		}

		return $settings;
	}

	/**
	 * @param string $convert_to_currency
	 *
	 * @return callable(array):array
	 */
	private function get_convert_price_callable( $convert_to_currency ): callable {
		return function ( array $price_params ) use ( $convert_to_currency ): array {
			$value    = $price_params['amount'];
			$currency = strtoupper( $price_params['currency'] );

			$price_default   = $this->woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $value, $currency );
			$price_converted = $this->woocommerce_wpml->multi_currency->prices->convert_price_amount( $price_default, $convert_to_currency );

			$price_params['amount']   = (int) $price_converted;
			$price_params['currency'] = strtolower( $convert_to_currency );

			return $price_params;
		};
	}

	/**
	 * @return null|string nul when not found
	 */
	private function maybe_convert_currency( string $client_currency ) {
		$gateway_setting = $this->get_setting( $client_currency );

		return $gateway_setting['currency'] ?? null;
	}

	private function is_client_currency_supported( string $client_currency ): bool {
		$newCurrency = $this->maybe_convert_currency( $client_currency );

		if ( null === $newCurrency ) {
			return false;
		}

		return true;
	}
}
