<?php

/**
 * Class WCML_Payment_Gateway
 */
abstract class WCML_Payment_Gateway {

	const OPTION_KEY = 'wcml_payment_gateway_';

	/**
	 * @var string
	 */
	protected $current_currency;

	/**
	 * @var string
	 */
	protected $default_currency;

	/**
	 * @var array
	 */
	protected $active_currencies;

	/**
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	/**
	 * @var array
	 */
	private $settings = [];

	/**
	 * @var woocommerce_wpml
	 */
	protected $woocommerce_wpml;

	/**
	 * @param WC_Payment_Gateway $gateway
	 * @param woocommerce_wpml   $woocommerce_wpml
	 */
	public function __construct( WC_Payment_Gateway $gateway, woocommerce_wpml $woocommerce_wpml ) {
		$this->gateway          = $gateway;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->settings         = get_option( self::OPTION_KEY . $this->get_id(), [] );
	}

	abstract public function get_output_model();

	/**
	 * @return WC_Payment_Gateway
	 */
	public function get_gateway() {
		return $this->gateway;
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return $this->gateway->id;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->gateway->title;
	}

	/**
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	private function save_settings() {
		update_option( self::OPTION_KEY . $this->get_id(), $this->settings );
	}

	/**
	 * @param string $currency
	 *
	 * @return array|null
	 */
	public function get_setting( $currency ) {
		$setting = $this->settings[ $currency ] ?? null;

		return $this->set_currency( $setting, $currency );
	}

	/**
	 * Make sure settings include the currency key.
	 *
	 * @param array|null $setting
	 * @param string     $currency
	 *
	 * @return array|null
	 */
	private function set_currency( $setting, $currency ) {
		if ( is_array( $setting ) && empty( $setting['currency'] ) ) {
			$setting['currency'] = $currency;
		}

		return $setting;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	public function save_setting( $key, $value ) {
		$this->settings[ $key ] = $value;
		$this->save_settings();
	}

	public function get_active_currencies() {

		$active_currencies = $this->active_currencies;

		if ( ! in_array( $this->current_currency, array_keys( $active_currencies ), true ) ) {
			$active_currencies[ $this->current_currency ] = [];
		}

		return $active_currencies;
	}

}
