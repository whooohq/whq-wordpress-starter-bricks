<?php

namespace WCML\Multicurrency\WpQueryMcPrice;

abstract class AbstractPriceByPostMeta {

	const WCML_CUSTOM_PRICES_STATUS_ALIAS = 'wcml_mc_status';

	const WCML_PRICE_ALIAS = 'wcml_org_price';

	const WCML_MC_PRICE_ALIAS = 'wcml_mc_price';

	/**
	 * @var \woocommerce_wpml
	 */
	protected $woocommerce_wpml;

	/**
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * @var mixed
	 */
	protected $default_currency;

	/**
	 * @var string
	 */
	protected $client_currency;

	public function __construct( \woocommerce_wpml $woocommerce_wpml, \wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;

		$this->default_currency = wcml_get_woocommerce_currency_option();
		$this->client_currency  = $this->woocommerce_wpml->multi_currency->get_client_currency();
	}

	/**
	 * @param string $clausesJoinSQL
	 *
	 * @return string
	 */
	protected function buildWCMLMultiCurrencyQueryJoin( $clausesJoinSQL ) {
		if ( false === strpos( $clausesJoinSQL, ' ' . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ' ' ) ) {
			$clausesJoinSQL .= "\n LEFT JOIN {$this->wpdb->postmeta} AS " . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . " ON ({$this->wpdb->posts}.ID = " . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".post_id) and " . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".meta_key = '_wcml_custom_prices_status' ";
		}

		if ( false === strpos( $clausesJoinSQL, ' ' . self::WCML_PRICE_ALIAS . ' ' ) ) {
			$clausesJoinSQL .= "\n LEFT JOIN {$this->wpdb->postmeta} AS " . self::WCML_PRICE_ALIAS . " ON ({$this->wpdb->posts}.ID = " . self::WCML_PRICE_ALIAS . ".post_id) and " . self::WCML_PRICE_ALIAS . ".meta_key = '_price' ";
		}

		if ( false === strpos( $clausesJoinSQL, ' ' . self::WCML_MC_PRICE_ALIAS . ' ' ) ) {
			$clausesJoinSQL .= "\n LEFT JOIN {$this->wpdb->postmeta} AS " . self::WCML_MC_PRICE_ALIAS . " ON ({$this->wpdb->posts}.ID = " . self::WCML_MC_PRICE_ALIAS . ".post_id) and " . self::WCML_MC_PRICE_ALIAS . ".meta_key = '_price_{$this->client_currency}' ";
		}

		return $clausesJoinSQL;
	}
}
