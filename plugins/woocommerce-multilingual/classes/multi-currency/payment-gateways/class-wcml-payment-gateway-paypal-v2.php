<?php

use WPML\FP\Obj;
use WPML\FP\Fns;
use function WCML\functions\getClientCurrency;

/**
 * @see https://wordpress.org/plugins/woocommerce-paypal-payments/
 */
class WCML_Payment_Gateway_PayPal_V2 extends WCML_Payment_Gateway_PayPal {

	const ID = 'ppcp-gateway';

	const FIELDS = [
		'merchant_email',
		'merchant_id',
		'client_id',
		'client_secret',
		'currency',
	];

	const BEARER_TOKEN_TRANSIENT = 'ppcp-paypal-bearerppcp-bearer';

	public function get_output_model() {
		return [
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'isSupported' => true,
			'settings'    => $this->get_currencies_details(),
			'tooltip'     => '',
			'strings'     => [
				'labelCurrency'       => __( 'Currency', 'woocommerce-multilingual' ),
				'labelPayPalEmail'    => __( 'PayPal Email', 'woocommerce-multilingual' ),
				'labelMerchantId'     => __( 'Merchant ID', 'woocommerce-multilingual' ),
				'labelClientId'       => __( 'Client ID', 'woocommerce-multilingual' ),
				'labelSecretKey'      => __( 'Secret Key', 'woocommerce-multilingual' ),
				// translators: %s is currency code.
				'tooltipNotSupported' => __( 'This gateway does not support %s. To show this gateway please select another currency.', 'woocommerce-multilingual' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function get_currencies_details() {
		$currencies_details     = [];
		$default_currency       = wcml_get_woocommerce_currency_option();
		$woocommerce_currencies = get_woocommerce_currencies();

		foreach ( $woocommerce_currencies as $code => $currency ) {
			if ( $default_currency === $code ) {
				$getSetting = Obj::propOr( '', Fns::__, $this->get_gateway()->settings );
			} else {
				$getSetting = Obj::propOr( '', Fns::__, $this->get_setting( $code ) );
			}

			foreach ( self::FIELDS as $key ) {
				$currencies_details[ $code ][ $key ] = $getSetting( $key );
			}

			$currencies_details[ $code ]['isValid'] = $this->is_valid_for_use( $code );
		}

		return $currencies_details;
	}

	public function add_hooks() {
		$client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();

		if ( $this->is_payment_in_currency_not_supported_by_paypal( $client_currency ) ) {
			if ( $this->is_client_currency_possible_to_convert_to_valid_paypal_currency( $client_currency ) ) {
				add_filter( 'woocommerce_paypal_payments_localized_script_data', [
					$this,
					'paypal_express_checkout_convert_to_supported_currency'
				] );
				add_filter( 'ppcp_create_order_request_body_data', [
					$this,
					'paypal_checkout_convert_to_supported_currency'
				] );
				add_filter( 'ppcp_patch_order_request_body_data', [
					$this,
					'paypal_order_patches_convert_to_supported_currency'
				] );
			}
		}
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function filter_ppcp_args( $settings ) {
		if ( is_admin() ) {
			return $settings;
		}

		$gateway = Obj::prop( getClientCurrency(), get_option( self::OPTION_KEY . self::ID, [] ) );

		if ( $gateway ) {
			$getSetting = Obj::prop( Fns::__, $gateway );

			foreach ( self::FIELDS as $key ) {
				$settings[ $key ] = $getSetting( $key ) ?: $settings[ $key ];
			}
		}

		return $settings;
	}

	/**
	 * Sets the PayPal JS API to use the changed currency
	 *
	 * @param array $localize
	 *
	 * @return array
	 */
	public function paypal_express_checkout_convert_to_supported_currency( $localize ) {
		if ( ! is_array( $localize ) ) {
			return $localize;
		}

		$client_currency = $localize['currency'] ?? null;

		if ( null === $client_currency ) {
			return $localize;
		}

		$gateway_setting = $this->get_setting( $client_currency );

		if ( $gateway_setting['isValid'] ) {
			return $localize;
		}

		$convert_to_currency = $this->try_convert_client_currency_using_gateway_to_supported_by_paypal( $client_currency );
		if ( false === $convert_to_currency ) {
			return $localize;
		}

		if ( ! empty( $localize['url'] ) ) {
			$localize['url'] = add_query_arg( 'currency', $convert_to_currency, $localize['url'] );
		}
		if ( ! empty( $localize['currency'] ) ) {
			$localize['currency'] = $convert_to_currency;
		}
		if ( ! empty( $localize['button']['url'] ) ) {
			$localize['button']['url'] = add_query_arg( 'currency', $convert_to_currency, $localize['button']['url'] );
		}
		if ( ! empty( $localize['url_params']['currency'] ) ) {
			$localize['url_params']['currency'] = $convert_to_currency;
		}

		return $localize;
	}

	/**
	 * @param string $convert_to_currency
	 *
	 * @return callable(array):array
	 */
	private function get_convert_price_callable( $convert_to_currency ): callable {
		return function ( array $price_params ) use ( $convert_to_currency ): array {
			$value    = $price_params['value'];
			$currency = $price_params['currency_code'];

			$price_default   = $this->woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $value, $currency );
			$price_converted = $this->woocommerce_wpml->multi_currency->prices->convert_price_amount( $price_default, $convert_to_currency );

			$price_params['value']         = $price_converted;
			$price_params['currency_code'] = $convert_to_currency;

			return $price_params;
		};
	}

	/**
	 * Converts the data that will be transferred to PayPal to use the changed currency - at this stage the user confirms the payment (by logging into their account)
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function paypal_checkout_convert_to_supported_currency( $data ) {
		$client_currency = Obj::path( [ 'purchase_units', 0, 'amount', 'currency_code' ], $data );
		$gateway_setting = $this->get_setting( $client_currency );

		if ( $gateway_setting['isValid'] ) {
			return $data;
		}

		$convert_to_currency = $this->try_convert_client_currency_using_gateway_to_supported_by_paypal( $client_currency );
		if ( false === $convert_to_currency ) {
			return $data;
		}


			if ( $client_currency !== $convert_to_currency ) {
				$convert_price = $this->get_convert_price_callable( $convert_to_currency );

				if ( isset( $data['purchase_units'] ) && is_array( $data['purchase_units'] ) ) {
					foreach ( $data['purchase_units'] as &$purchaseUnit ) {
						if ( isset( $purchaseUnit['amount'] ) && is_array( $purchaseUnit['amount'] ) ) {
							$purchaseUnit['amount'] = array_merge( $purchaseUnit['amount'], $convert_price( $purchaseUnit['amount'] ) );

							if ( isset( $purchaseUnit['amount']['breakdown']['item_total'] ) && is_array( $purchaseUnit['amount']['breakdown']['item_total'] ) ) {
								$purchaseUnit['amount']['breakdown']['item_total'] = $convert_price( $purchaseUnit['amount']['breakdown']['item_total'] );
							}

							if ( isset( $purchaseUnit['amount']['breakdown']['shipping'] ) && is_array( $purchaseUnit['amount']['breakdown']['shipping'] ) ) {
								$purchaseUnit['amount']['breakdown']['shipping'] = $convert_price( $purchaseUnit['amount']['breakdown']['shipping'] );
							}

							if ( isset( $purchaseUnit['amount']['breakdown']['tax_total'] ) && is_array( $purchaseUnit['amount']['breakdown']['tax_total'] ) ) {
								$purchaseUnit['amount']['breakdown']['tax_total'] = $convert_price( $purchaseUnit['amount']['breakdown']['tax_total'] );
							}
						}

						if ( isset( $purchaseUnit['items'] ) && is_array( $purchaseUnit['items'] ) ) {
							foreach ( $purchaseUnit['items'] as &$item ) {
								if ( isset( $item['unit_amount'] ) && is_array( $item['unit_amount'] ) ) {
									$item['unit_amount'] = $convert_price( $item['unit_amount'] );
								}
							}
						}
					}
				}
			}


		return $data;
	}

	/**
	 * Converts the data that will be compared with the one returned from PayPal to use the changed currency - on this basis, our order will know whether PayPal confirmed its payment
	 *
	 * @param array $patches_array
	 *
	 * @return array
	 */
	public function paypal_order_patches_convert_to_supported_currency( $patches_array ) {
		if ( ! is_array( $patches_array ) ) {
			return $patches_array;
		}

		$client_currency = Obj::path( [ 0, 'value', 'amount', 'currency_code' ], $patches_array );
		$gateway_setting = $this->get_setting( $client_currency );

		if ( $gateway_setting['isValid'] ) {
			return $patches_array;
		}

		$convert_to_currency = $this->try_convert_client_currency_using_gateway_to_supported_by_paypal( $client_currency );
		if ( false === $convert_to_currency ) {
			return $patches_array;
		}

		$convert_price = $this->get_convert_price_callable( $convert_to_currency );

		foreach ( $patches_array as &$patch ) {
			if ( isset( $patch['value'] ) && is_array( $patch['value'] ) ) {
				$patch['value']['amount'] = array_merge( $patch['value']['amount'], $convert_price( $patch['value']['amount'] ) );

				if ( isset( $patch['value']['amount']['breakdown'] ) && is_array( $patch['value']['amount']['breakdown'] ) ) {
					$patch['value']['amount']['breakdown']['item_total'] = $convert_price( $patch['value']['amount']['breakdown']['item_total'] );
					$patch['value']['amount']['breakdown']['shipping']   = $convert_price( $patch['value']['amount']['breakdown']['shipping'] );
					$patch['value']['amount']['breakdown']['tax_total']  = $convert_price( $patch['value']['amount']['breakdown']['tax_total'] );
				}

				if ( isset( $patch['value']['items'] ) && is_array( $patch['value']['items'] ) ) {
					foreach ( $patch['value']['items'] as $item_key => $item ) {
						$patch['value']['items'][ $item_key ]['unit_amount'] = $convert_price( $patch['value']['items'][ $item_key ]['unit_amount'] );
					}
				}
			}
		}

		return $patches_array;
	}

	/**
	 * @return false|string false when not found
	 */
	private function try_convert_client_currency_using_gateway_to_supported_by_paypal( string $client_currency ) {
		$gateway_setting = $this->get_setting( $client_currency );

		if ( false === $gateway_setting['isValid'] ) {
			return $gateway_setting['currency'];
		}

		return false;
	}

	private function is_payment_in_currency_not_supported_by_paypal( string $client_currency ): bool {
		return ! $this->is_valid_for_use( $client_currency );
	}

	private function is_client_currency_possible_to_convert_to_valid_paypal_currency( string $client_currency ): bool {
		$newCurrency = $this->try_convert_client_currency_using_gateway_to_supported_by_paypal( $client_currency );

		if ( false === $newCurrency ) {
			return false;
		}

		if ( $this->is_payment_in_currency_not_supported_by_paypal( $newCurrency ) ) {
			return true;
		}

		return true;
	}
}
