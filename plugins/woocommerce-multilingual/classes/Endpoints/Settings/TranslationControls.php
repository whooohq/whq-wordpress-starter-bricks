<?php

namespace WCML\Endpoints\Settings;

use WCML\TranslationControls\Hooks as TranslationControlsBase;
use WCML\Utilities\WcAdminPages;
use WCML_Tracking_Link;
use WPML\FP\Obj;
use WPML\FP\Str;

class TranslationControls extends TranslationControlsBase {

	const TRANSLATION_DOMAIN = 'WP Endpoints';

	const OPTION_NAMES = [
		'order-pay'                  => 'woocommerce_checkout_pay_endpoint',
		'order-received'             => 'woocommerce_checkout_order_received_endpoint',
		'add-payment-method'         => 'woocommerce_myaccount_add_payment_method_endpoint',
		'delete-payment-method'      => 'woocommerce_myaccount_delete_payment_method_endpoint',
		'set-default-payment-method' => 'woocommerce_myaccount_set_default_payment_method_endpoint',
		'orders'                     => 'woocommerce_myaccount_orders_endpoint',
		'view-order'                 => 'woocommerce_myaccount_view_order_endpoint',
		'downloads'                  => 'woocommerce_myaccount_downloads_endpoint',
		'edit-account'               => 'woocommerce_myaccount_edit_account_endpoint',
		'edit-address'               => 'woocommerce_myaccount_edit_address_endpoint',
		'payment-methods'            => 'woocommerce_myaccount_payment_methods_endpoint',
		'lost-password'              => 'woocommerce_myaccount_lost_password_endpoint',
		'customer-logout'            => 'woocommerce_logout_endpoint',
	];

	protected function addAdminPageHooks() {
		add_action( 'woocommerce_after_settings_advanced', [ $this, 'translationControls' ] );
		add_action( 'woocommerce_update_options_advanced', [ $this, 'registerStringsOnSave' ] );
	}

	/**
	 * @return bool
	 */
	protected function isAdminPage() {
		return WcAdminPages::isAdvancedSettings();
	}

	public function translationInstructions() {}

	private function getOptionNames() {
		/**
		 * Register WooCommerce endpoints that should get language controls, as a key => input ID pair.
		 *
		 * The key should match the query var used to define the endpoint.
		 * The input ID should match the ID of the input holding the endpoint value in the WooCommerce advanced settings tab..
		 *
		 * @since 5.5.3
		 *
		 * @param array<string,string> $keysToOptions An array of key => input ID pairs.
		 *
		 * @return array<string,string>
		 */
		return apply_filters( 'wcml_endpoints_translation_controls', self::OPTION_NAMES );
	}

	/**
	 * @return array
	 */
	protected function getTranslationControls() {
		$optionNames         = $this->getOptionNames();
		$translationControls = [];
		foreach ( $optionNames as $stringName => $optionName ) {
			$translationControls[] = $this->getTranslationControl(
				$stringName,
				$optionName,
				get_option( $optionName, '' ),
				self::TRANSLATION_DOMAIN,
				$this->getStringName( $stringName, $optionName )
			);
		}

		return $translationControls;
	}

	public function registerStringsOnSave() {
		$optionNames = $this->getOptionNames();

		/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
		wpml_collect( $_POST )
			->filter( function( $language, $key ) {
				return Str::startsWith( self::KEY_PREFIX . '-', $key );
			} )
			->map( function( $language, $key ) use ( $optionNames ) {
				$stringName = str_replace( self::KEY_PREFIX . '-', '', $key );
				if ( ! array_key_exists( $stringName, $optionNames ) ) {
					return;
				}

				$optionName  = $optionNames[ $stringName ];
				$domain      = self::TRANSLATION_DOMAIN;
				$name        = $this->getStringName( $stringName, $optionName );
				$stringValue = wp_kses_post( Obj::propOr(
					'',
					$this->getInputName( $stringName, $optionName ),
					/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
					$_POST
				) );
				if ( empty( $stringValue ) ) {
					return;
				}
				$this->replaceStringAndLanguage( $stringValue, $domain, $name, $language );
			} );
	}

	/**
	 * @param string $stringName
	 * @param string $optionName
	 *
	 * @return string
	 */
	protected function getStringName( $stringName, $optionName ) {
		return $stringName;
	}

	/**
	 * @param string $stringName
	 * @param string $optionName
	 *
	 * @return string
	 */
	protected function getInputId( $stringName, $optionName ) {
		return $optionName;
	}

	/**
	 * @param string $stringName
	 * @param string $optionName
	 *
	 * @return string
	 */
	protected function getLanguageSelectorId( $stringName, $optionName ) {
		return $stringName . '_' . self::LANGUAGE_SELECTOR_ID_SUFFIX;
	}

	/**
	 * @param string $stringName
	 * @param string $optionName
	 *
	 * @return string
	 */
	protected function getLanguageSelectorName( $stringName, $optionName ) {
		return self::KEY_PREFIX . '-' . $stringName;
	}
}
