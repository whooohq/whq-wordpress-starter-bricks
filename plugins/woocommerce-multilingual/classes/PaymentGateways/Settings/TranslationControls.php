<?php

namespace WCML\PaymentGateways\Settings;

use WCML\PaymentGateways\Strings;
use WCML\TranslationControls\Hooks as TranslationControlsBase;
use WCML\Utilities\WcAdminPages;
use WPML\FP\Obj;
use WPML\FP\Str;

/**
 * Since WooCommerce 9.9.0, Payment Gateways default settings pages are using a React-based user interface.
 *
 * In those cases, we will not show language controls next to translatable strings for payment gateways:
 * we just link to the Translation Dashboard instead.
 *
 * There might be third-party payment gateways still using the legacy settings page API.
 *
 * This class also manages saving and updating translatable string values, on POST or REST modes.
 */
class TranslationControls extends TranslationControlsBase {

	public function add_hooks() {
		parent::add_hooks();
		if ( wpml_is_rest_request() ) {
			$this->addRestHooks();
		}
	}

	protected function isAdminPage() {
		return WcAdminPages::isPaymentSettings() && WcAdminPages::hasSection();
	}

	protected function addAdminPageHooks() {
		add_action( 'woocommerce_after_settings_checkout', [ $this, 'translationInstructions' ] );
		add_action( 'woocommerce_after_settings_checkout', [ $this, 'translationControls' ] );
		add_action( 'woocommerce_update_options_checkout', [ $this, 'registerStringsOnSave' ], 9 );
	}

	private function addRestHooks() {
		add_filter( 'woocommerce_rest_prepare_payment_gateway', [ $this, 'registerStringsOnRestSave' ], 10, 3 );
	}

	/**
	 * Checks whether the payment gateway disabled the natural submit button.
	 *
	 * Note that this is usually defined at payment gateway options page render time, not before.
	 * Used by WooCommerce in its settings page template, at /includes/admin/views/html-admin-settings.php.
	 *
	 * @return bool
	 */
	private function isSubmitButtonHidden() {
		return ! empty( $GLOBALS['hide_save_button'] );
	}

	/**
	 * @param string $domain
	 * @param string $search
	 *
	 * @return string
	 */
	protected function getInstructionsWithRegisteredStrings( $domain, $search = '' ) {
		return Strings::getTranslationInstructions();
	}

	public function translationInstructions() {
		if ( $this->isSubmitButtonHidden() ) {
			return;
		}

		$gateway = $this->getCurrentGateway();
		if ( is_null( $gateway ) ) {
			return;
		}

		$this->pointerFactory
			->create( [
				'content'    => $this->getInstructions( Strings::TRANSLATION_DOMAIN, Strings::getStringName( $gateway->id, '' ) ),
				'selectorId' => 'wpbody-content .woocommerce table.form-table:nth-of-type(1)',
				'method'     => 'before',
			] )->show();
	}

	/**
	 * @return \WC_Payment_Gateway|null
	 */
	private function getCurrentGateway() {
		static $currentGateway = false;
		if ( false !== $currentGateway ) {
			return $currentGateway;
		}

		$gatewaysManager = \WC_Payment_Gateways::instance();
		$gateways        = $gatewaysManager->payment_gateways();

		/**
		 * Checks if a given gateway provides content for the payments settings section.
		 *
		 * @param \WC_Payment_Gateway $gateway
		 *
		 * @return bool
		 */
		$isCurrentGatewaySection = function( $gateway ) {
			return WcAdminPages::isSection( $gateway->id );
		};

		$currentGateway = wpml_collect( $gateways )->filter( $isCurrentGatewaySection )->first();
		return $currentGateway;
	}

	/**
	 * @return array
	 */
	protected function getTranslationControls() {
		$translationControls = [];

		if ( $this->isSubmitButtonHidden() ) {
			return $translationControls;
		}

		$gateway = $this->getCurrentGateway();
		if ( is_null( $gateway ) ) {
			return $translationControls;
		}

		$textKeys = $this->getGatewayTextKeys();
		foreach ( $textKeys as $textKey ) {
			if ( '' === $textKey ) {
				$settingValue = $gateway->description;
			} elseif ( isset( $gateway->settings[ $textKey ] ) ) {
				$settingValue = $gateway->settings[ $textKey ];
			} else {
				$settingValue = Obj::prop( $textKey, $gateway );
			}

			$gatewayKey            = $gateway->plugin_id . $gateway->id;
			$translationControls[] = $this->getTranslationControl(
				$gatewayKey,
				$textKey,
				$settingValue,
				Strings::TRANSLATION_DOMAIN,
				$this->getStringName( $gateway->id, $textKey )
			);
		}

		return $translationControls;
	}

	public function registerStringsOnSave() {
		$gateway = $this->getCurrentGateway();
		if ( is_null( $gateway ) ) {
			return;
		}

		$gatewayKey                 = $gateway->plugin_id . $gateway->id;
		$languageSelectorNamePrefix = $this->getLanguageSelectorNamePrefix( $gateway->plugin_id . $gateway->id );
		/* phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected */
		$itemsToProcess = wpml_collect( $_POST )
			->filter( function( $value, $key ) use ( $languageSelectorNamePrefix ) {
				return Str::startsWith( $languageSelectorNamePrefix, $key );
			} )
			->toArray();

		array_walk( $itemsToProcess, function( $language, $key ) use ( $gateway, $gatewayKey, $languageSelectorNamePrefix ) {
			$textKey                = str_replace( $languageSelectorNamePrefix, '', $key );
			$name                   = $this->getStringName( $gateway->id, $textKey );
			$stringValue            = wp_kses_post( Obj::propOr(
				'',
				$this->getInputName( $gatewayKey, $textKey ),
				$_POST
			) );
			if ( empty( $stringValue ) ) {
				return;
			}
			$this->replaceStringAndLanguage( $stringValue, Strings::TRANSLATION_DOMAIN, $name, $language );
		} );
	}

	/**
	 * @param \WP_REST_Response   $response The response object.
	 * @param \WC_Payment_Gateway $gateway  Payment gateway object.
	 * @param \WP_REST_Request    $request  Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function registerStringsOnRestSave( $response, $gateway, $request ) {
		$requestMethod = $request->get_method();
		if ( ! in_array( $requestMethod, [ 'POST', 'PUT', 'PATCH' ], true ) ) {
			return $response;
		}

		$textKeys = $this->getGatewayTextKeys();
		foreach ( $textKeys as $textKey ) {
			$name = $this->getStringName( $gateway->id, $textKey );
			if ( '' === $textKey ) {
				$stringValue = $gateway->description;
			} elseif ( isset( $gateway->settings[ $textKey ] ) ) {
				$stringValue = $gateway->settings[ $textKey ];
			} else {
				$stringValue = Obj::prop( $textKey, $gateway );
			}

			$this->replaceStringAndLanguage( $stringValue, Strings::TRANSLATION_DOMAIN, $name );
		}
		return $response;
	}

	/**
	 * Gets the keys for email options that relate to translatable strings.
	 *
	 * @return array
	 */
	private function getGatewayTextKeys() {
		/**
		 * @param array Strings::TRANSLATABLE_SETTINGS
		 *
		 * @return array
		 */
		return apply_filters( 'wcml_gateway_text_keys_to_translate', Strings::TRANSLATABLE_SETTINGS ); // @phpstan-ignore-line
	}

	/**
	 * @param string $gatewayId
	 * @param string $textKey
	 *
	 * @return string
	 */
	protected function getStringName( $gatewayId, $textKey ) {
		return Strings::getStringName( $gatewayId, $textKey );
	}

	/**
	 * Gets the id attribute value of the input node holding a translatable string.
	 *
	 * @param string $gatewayKey
	 * @param string $textKey
	 *
	 * @return string
	 */
	protected function getInputId( $gatewayKey, $textKey ) {
		return $gatewayKey . '_' . $textKey;
	}

	/**
	 * @param string $gatewayKey
	 * @param string $textKey
	 *
	 * @return string
	 */
	protected function getLanguageSelectorId( $gatewayKey, $textKey ) {
		return $gatewayKey . '_settings_' . $textKey . '_' . self::LANGUAGE_SELECTOR_ID_SUFFIX;
	}

	/**
	 * @param string $gatewayKey
	 *
	 * @return string
	 */
	private function getLanguageSelectorNamePrefix( $gatewayKey ) {
		return self::KEY_PREFIX . '-' . $gatewayKey . '_settings-';
	}

	/**
	 * @param string $gatewayKey
	 * @param string $textKey
	 *
	 * @return string
	 */
	protected function getLanguageSelectorName( $gatewayKey, $textKey ) {
		return $this->getLanguageSelectorNamePrefix( $gatewayKey ) . $textKey;
	}

}
