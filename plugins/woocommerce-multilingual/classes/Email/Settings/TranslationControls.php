<?php

namespace WCML\Email\Settings;

use WCML\TranslationControls\Hooks as TranslationControlsBase;
use WCML\Utilities\WcAdminPages;
use WCML_Tracking_Link;
use WPML\FP\Obj;

class TranslationControls extends TranslationControlsBase {

	const OPTION_NAMES = [
		'woocommerce_new_order_settings',
		'woocommerce_cancelled_order_settings',
		'woocommerce_failed_order_settings',
		'woocommerce_customer_failed_order_settings',
		'woocommerce_customer_on_hold_order_settings',
		'woocommerce_customer_processing_order_settings',
		'woocommerce_customer_completed_order_settings',
		'woocommerce_customer_refunded_order_settings',
		'woocommerce_customer_invoice_settings',
		'woocommerce_customer_note_settings',
		'woocommerce_customer_reset_password_settings',
		'woocommerce_customer_new_account_settings',
	];

	const EMAIL_TEXT_KEYS = [
		'subject',
		'heading',
		'subject_downloadable',
		'heading_downloadable',
		'subject_full',
		'subject_partial',
		'heading_full',
		'heading_partial',
		'subject_paid',
		'heading_paid',
		'additional_content',
	];

	protected function addAdminPageHooks() {
		add_action( 'woocommerce_settings_email', [ $this, 'translationInstructions' ] );
		add_action( 'woocommerce_after_settings_email', [ $this, 'translationControls' ] );
		add_action( 'woocommerce_update_options_email', [ $this, 'registerStringsOnSave' ] );
	}

	/**
	 * @return bool
	 */
	protected function isAdminPage() {
		return WcAdminPages::isEmailSettings() && WcAdminPages::hasSection();
	}

	/**
	 * Gets the option name related to the current email settings section.
	 *
	 * Find the right option name with the structure woocommerce_{EMAIL_ID}_settings,
	 * matching the current settings page URL parameter wc_email_{EMAIL_ID}.
	 *
	 * @return string
	 */
	private function getCurrentEmailOptionName() {
		static $currentEmailOptionName = null;
		if ( ! is_null( $currentEmailOptionName ) ) {
			return $currentEmailOptionName;
		}

		/**
		 * @param array self::OPTION_NAMES
		 *
		 * @return array
		 *
		 * @see \WCML\Compatibility\WcBookings\Emails::add_hooks()
		 * @see \WCML_WC_Subscriptions::add_hooks()
		 */
		$optionNames = apply_filters( 'wcml_emails_options_to_translate', self::OPTION_NAMES ); // @phpstan-ignore-line

		/**
		 * Checks if a given option name provides content for the current email settings section.
		 *
		 * @param string $option
		 *
		 * @return bool
		 */
		$isCurrentOptionSection = function( $option ) {
			/**
			 * @param string 'wc_email_'
			 * @param string $option
			 *
			 * @return string
			 *
			 * @see \WCML_WC_Subscriptions::add_hooks()
			 */
			$sectionPrefix = apply_filters( 'wcml_emails_section_name_prefix', 'wc_email_', $option ); // @phpstan-ignore-line
			$sectionName   = str_replace( 'woocommerce_', $sectionPrefix, $option );
			$sectionName   = str_replace( '_settings', '', $sectionName );
			/**
			 * @param string $sectionName
			 *
			 * @return string
			 */
			$sectionName = apply_filters( 'wcml_emails_section_name_to_translate', $sectionName );
			return WcAdminPages::isSection( $sectionName );
		};

		$currentEmailOptionName = (string) wpml_collect( $optionNames )->filter( $isCurrentOptionSection )->first();
		return $currentEmailOptionName;
	}

	/**
	 * @param string $domain
	 * @param string $search
	 *
	 * @return string
	 */
	protected function getInstructionsWithRegisteredStrings( $domain, $search = '' ) {
		return sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
			esc_html__( 'To translate custom WooCommerce email notifications, go to the %1$sTranslation Dashboard%2$s.', 'woocommerce-multilingual' ),
			'<a href="' . esc_url( $this->getInstructionsLink( $domain, $search ) ) . '">',
			'</a>'
		);
	}

	public function translationInstructions() {
		$optionName = $this->getCurrentEmailOptionName();
		if ( ! $optionName ) {
			return;
		}

		$this->pointerFactory
			->create( [
				'content'    => $this->getInstructions( $this->getStringDomain( $optionName ) ),
				'selectorId' => 'wpbody-content .woocommerce table.form-table:nth-of-type(1)',
				'method'     => 'before',
				'docLink'    => WCML_Tracking_Link::getWcmlTranslateEmailsDoc(),
			] )->show();
	}

	/**
	 * @return array
	 */
	protected function getTranslationControls() {
		$translationControls = [];
		$optionName          = $this->getCurrentEmailOptionName();
		if ( ! $optionName ) {
			return $translationControls;
		}

		$emailSettings = $this->getEmailSettings( $optionName );

		foreach ( $emailSettings as $settingKey => $settingValue ) {
			$translationControls[] = $this->getTranslationControl(
				$optionName,
				$settingKey,
				$settingValue,
				$this->getStringDomain( $optionName ),
				$this->getStringName( $optionName, $settingKey )
			);
		}

		return $translationControls;
	}

	public function registerStringsOnSave() {
		/* phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected */
		$itemsToProcess = wpml_collect( $_POST )
			->filter( function( $value, $key ) {
				return substr( $key, 0, 9 ) === self::KEY_PREFIX;
			} )
			->toArray();

		array_walk( $itemsToProcess, function( $language, $key ) {
			$keyParts = explode( '-', $key );
			if ( count( $keyParts ) < 3 ) {
				return;
			}

			list( , $optionName, $emailTextKey ) = $keyParts;
			$domain                              = $this->getStringDomain( $optionName );
			$name                                = $this->getStringName( $optionName, $emailTextKey );
			$stringValue                         = wp_kses_post( Obj::propOr(
				'',
				$this->getInputName( $optionName, $emailTextKey ),
				$_POST
			) );
			if ( empty( $stringValue ) ) {
				return;
			}
			$this->replaceStringAndLanguage( $stringValue, $domain, $name, $language );
		} );
	}

	/**
	 * Gets the keys for email options that relate to translatable strings.
	 *
	 * @return array
	 */
	private function getEmailTextKeys() {
		/**
		 * @param array self::EMAIL_TEXT_KEYS
		 *
		 * @return array
		 *
		 * @see \WCML\Compatibility\WcBookings\Emails::add_hooks()
		 */
		return apply_filters( 'wcml_emails_text_keys_to_translate', self::EMAIL_TEXT_KEYS ); // @phpstan-ignore-line
	}

	/**
	 * Get the current email options, and return only those holding translatable strings.
	 *
	 * @param string $optionName
	 *
	 * @return array
	 */
	private function getEmailSettings( $optionName ) {
		$emailSettings = get_option( $optionName, [] );
		if ( ! is_array( $emailSettings ) ) {
			return [];
		}

		if ( ! isset( $emailSettings['additional_content'] ) ) {
			$emailSettings['additional_content'] = '';
		}

		$emailTextKeys    = $this->getEmailTextKeys();
		$relevantSettings = wpml_collect( $emailSettings )
			->filter( function( $settingValue, $setttingKey ) use ( $emailTextKeys ) {
				return in_array( $setttingKey, $emailTextKeys );
			} )
			->toArray();

		return $relevantSettings;
	}

	/**
	 * @param string $optionName
	 *
	 * @return string
	 */
	protected function getStringDomain( $optionName ) {
		return 'admin_texts_' . $optionName;
	}

	/**
	 * @param string $optionName
	 * @param string $emailTextKey
	 *
	 * @return string
	 */
	protected function getStringName( $optionName, $emailTextKey ) {
		return '[' . $optionName . ']' . $emailTextKey;
	}

	/**
	 * Gets the id attribute value of the input node holding a translatable string.
	 *
	 * @param string $optionName
	 * @param string $emailTextKey
	 *
	 * @return string
	 */
	protected function getInputId( $optionName, $emailTextKey ) {
		return str_replace( '_settings', '', $optionName ) . '_' . $emailTextKey;
	}

	/**
	 * @param string $optionName
	 * @param string $settingKey
	 *
	 * @return string
	 */
	protected function getLanguageSelectorId( $optionName, $settingKey ) {
		return $optionName . '_' . $settingKey . '_' . self::LANGUAGE_SELECTOR_ID_SUFFIX;
	}

	/**
	 * @param string $optionName
	 * @param string $settingKey
	 *
	 * @return string
	 */
	protected function getLanguageSelectorName( $optionName, $settingKey ) {
		return self::KEY_PREFIX . '-' . $optionName . '-' . $settingKey;
	}

}
