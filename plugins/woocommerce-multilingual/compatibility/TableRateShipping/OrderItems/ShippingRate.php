<?php

namespace WCML\Compatibility\TableRateShipping\OrderItems;

use WCML\OrderItems\Shipping\StoreInDefaultLanguage;
use WCML\OrderItems\Translator;
use WCML\Utilities\DB;
use woocommerce_wpml;
use wpdb;

class ShippingRate implements Translator {

	use StoreInDefaultLanguage;

	/** @var woocommerce_wpml $woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var wpdb $wpdb */
	private $wpdb;

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param wpdb             $wpdb
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
	}

	/**
	 * @param \WC_Order_Item $item
	 * @param string         $targetLanguage
	 */
	public function translateItem( $item, $targetLanguage ) {
		if ( ! $item instanceof \WC_Order_Item_Shipping ) {
			return;
		}

		$shippingId = $item->get_method_id();
		if ( ! $shippingId ) {
			return;
		}

		if ( \WCML_Table_Rate_Shipping::RATE_SHIPPING_METHOD_ID !== $shippingId ) {
			return;
		}

		$canGetStoredTableRates = class_exists( '\WooCommerce\Shipping\Table_Rate\Helpers' );
		if ( ! $canGetStoredTableRates ) {
			return;
		}

		$shippingTitle      = $item->get_method_title();
		$shippingInstanceId = (int) $item->get_instance_id();
		$ratesPerInstance   = \WooCommerce\Shipping\Table_Rate\Helpers::get_shipping_rates( $shippingInstanceId, ARRAY_A );
		if ( ! is_array( $ratesPerInstance ) || empty( $ratesPerInstance ) ) {
			return;
		}

		$ratesLabels    = wp_list_pluck( $ratesPerInstance, 'rate_label', 'rate_id' );
		$rateIdPerValue = array_search( $shippingTitle, $ratesLabels, true );
		if ( false !== $rateIdPerValue ) {
			$item->set_method_title(
				$this->woocommerce_wpml->shipping->translate_shipping_method_title(
					$shippingTitle,
					$shippingId . $shippingInstanceId . $rateIdPerValue,
					$targetLanguage
				)
			);
			return;
		}

		$ratesIds         = wp_list_pluck( $ratesPerInstance, 'rate_id' );
		$rateIdToName     = function( $rateId ) use ( $shippingInstanceId ) {
			return sprintf( \WCML_Table_Rate_Shipping::RATE_LABEL_NAME_FORMAT, $shippingInstanceId, $rateId );
		};
		$ratesStringNames = wpml_collect( $ratesIds )
			->map( $rateIdToName )
			->toArray();

		// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		// phpcs:disable Squiz.Strings.DoubleQuoteUsage.NotRequired
		$rateData = $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT s.value as originalRateTitle, st.language as rateTitleLanguage FROM {$this->wpdb->prefix}icl_strings AS s
				LEFT JOIN {$this->wpdb->prefix}icl_string_translations AS st
				ON st.string_id = s.id
				WHERE s.context = %s
				AND st.value = %s
				AND s.name IN (" . DB::prepareIn( $ratesStringNames ) . ")
				LIMIT 1
			",
			\WCML_WC_Shipping::STRINGS_CONTEXT,
			$shippingTitle
		) );
		// phpcs:enable

		if ( ! $rateData ) {
			return;
		}

		$foundRateIdPerValue = array_search( $rateData->originalRateTitle, $ratesLabels, true );
		if ( false === $foundRateIdPerValue ) {
			// The order item references a rate that no longer exists in this table rate shipping method.
			// We can not know the rate ID, so we can not translate.
			return;
		}

		$this->maybeSaveItem( $item, $rateData->originalRateTitle, $targetLanguage );

		if ( $targetLanguage === $rateData->rateTitleLanguage ) {
			return;
		}

		$item->set_method_title(
			$this->woocommerce_wpml->shipping->translate_shipping_method_title(
				$rateData->originalRateTitle,
				$shippingId . $shippingInstanceId . $foundRateIdPerValue,
				$targetLanguage
			)
		);
	}

}
