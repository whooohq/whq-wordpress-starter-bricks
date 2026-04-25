<?php

namespace WCML\OrderItems\Shipping;

use WCML\OrderItems\Translator;
use woocommerce_wpml;
use wpdb;
class ShippingMethod implements Translator {

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

		$shippingTitle      = $item->get_method_title();
		$shippingInstanceId = $item->get_instance_id();

		$canGetStoredShippingMethods = class_exists( '\WC_Shipping_Zones' );
		if ( ! $canGetStoredShippingMethods ) {
			return;
		}

		$shippingMethod = \WC_Shipping_Zones::get_shipping_method( $shippingInstanceId );
		if ( false === $shippingMethod ) {
			return;
		}
		if ( $shippingTitle === $shippingMethod->get_title() ) {
			$item->set_method_title(
				$this->woocommerce_wpml->shipping->translate_shipping_method_title(
					$shippingTitle,
					$shippingId . $shippingInstanceId,
					$targetLanguage
				)
			);
			return;
		}

		// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		$itemShippingMethodData = $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT s.value as originalMethodTitle, st.language as itemMethodTitleLanguage FROM {$this->wpdb->prefix}icl_strings AS s
				LEFT JOIN {$this->wpdb->prefix}icl_string_translations AS st
				ON st.string_id = s.id
				WHERE s.context = %s
				AND st.value = %s
				AND s.name = %s
				LIMIT 1
			",
			\WCML_WC_Shipping::STRINGS_CONTEXT,
			$shippingTitle,
			str_replace( ':', '', $shippingId . $shippingInstanceId ) . \WCML_WC_Shipping::NAME_SUFFIX
		) );
		// phpcs:enable

		if ( ! $itemShippingMethodData ) {
			return;
		}

		$this->maybeSaveItem( $item, $itemShippingMethodData->originalMethodTitle, $targetLanguage );

		if ( $targetLanguage === $itemShippingMethodData->itemMethodTitleLanguage ) {
			return;
		}

		$item->set_method_title(
			$this->woocommerce_wpml->shipping->translate_shipping_method_title(
				$itemShippingMethodData->originalMethodTitle,
				$shippingId . $shippingInstanceId,
				$targetLanguage
			)
		);
	}

}
