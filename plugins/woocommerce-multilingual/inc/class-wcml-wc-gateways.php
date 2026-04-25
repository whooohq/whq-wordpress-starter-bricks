<?php

use function WCML\functions\isStandAlone;
use WCML\PaymentGateways\Strings;
use WCML\StandAlone\NullSitePress;
use WCML\Utilities\WcAdminPages;
use WPML\API\Sanitize;
use WPML\Collect\Support\Collection;
use WPML\Core\ISitePress;
use WPML\FP\Fns;

class WCML_WC_Gateways {

	const WCML_BACS_ACCOUNTS_CURRENCIES_OPTION = 'wcml_bacs_accounts_currencies';

	/** @var string */
	private $current_language;

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var SitePress|NullSitePress */
	private $sitepress;

	public function __construct( woocommerce_wpml $woocommerce_wpml, ISitePress $sitepress ) {
		/* @phpstan-ignore assign.propertyType */
		$this->sitepress        = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;

		/* @phpstan-ignore method.notFound */
		$this->current_language = $this->sitepress->get_current_language();
		if ( 'all' === $this->current_language ) {
			/* @phpstan-ignore method.notFound */
			$this->current_language = $this->sitepress->get_default_language();
		}
	}

	public function add_hooks() {
		if ( isStandAlone() ) {
			if ( WcAdminPages::isPaymentSettings() ) {
				add_action( 'init', [ $this, 'load_bacs_gateway_currency_selector_hooks' ], 11 );
			}
		} else {
			add_action( 'init', [ $this, 'on_init_hooks' ], 11 );
			add_filter( 'woocommerce_payment_gateways', Fns::withoutRecursion( Fns::identity(), [ $this, 'loaded_woocommerce_payment_gateways' ] ) );
		}
	}

	public function on_init_hooks() {
		add_filter( 'woocommerce_gateway_title', [ $this, 'translate_gateway_title' ], 10, 2 );
		add_filter( 'woocommerce_gateway_description', [ $this, 'translate_gateway_description' ], 10, 2 );
		add_filter( 'woocommerce_paypal_payments_gateway_description', [ $this, 'translate_paypal_payments_gateway_description' ], 10, 2 );

		if ( WcAdminPages::isPaymentSettings() ) {
			$this->load_bacs_gateway_currency_selector_hooks();
		}
	}

	public function load_bacs_gateway_currency_selector_hooks() {
		if ( WcAdminPages::isSection( WcAdminPages::SECTION_BACS ) && wcml_is_multi_currency_on() ) {
			$this->set_bacs_gateway_currency();
			add_action( 'admin_footer', [ $this, 'append_currency_selector_to_bacs_account_settings' ] );
		}
	}

	public function loaded_woocommerce_payment_gateways( $load_gateways ) {

		foreach ( $load_gateways as $key => $gateway ) {

			$load_gateway = $gateway;

			if ( is_string( $gateway ) ) {
				if ( class_exists( $gateway ) ) {
					$load_gateway = new $gateway();
				} else {
					continue;
				}
			}

			$this->register_gateway_settings_strings( $load_gateway->id, $load_gateway->settings );
			$this->payment_gateways_filters( $load_gateway );
			$load_gateways[ $key ] = $load_gateway;
		}

		return $load_gateways;
	}

	/**
	 * @param string $gateway_id
	 * @param array  $settings
	 */
	public function register_gateway_settings_strings( $gateway_id, $settings ) {
		if ( isset( $settings['enabled'] ) && 'yes' === $settings['enabled'] ) {
			foreach ( $this->get_gateway_text_keys_to_translate() as $text_key ) {
				if ( isset( $settings[ $text_key ] ) && ! $this->get_gateway_string_id( $settings[ $text_key ], $gateway_id, $text_key ) ) {
					icl_register_string(
						Strings::TRANSLATION_DOMAIN,
						Strings::getStringName( $gateway_id, $text_key ),
						$settings[ $text_key ],
						false,
						$this->sitepress->get_default_language()
					);
				}
			}
		}
	}

	/**
	 * @param WC_Payment_Gateway $gateway
	 */
	public function payment_gateways_filters( $gateway ) {
		/* @phpstan-ignore isset.property */
		if ( isset( $gateway->id ) ) {
			$this->translate_gateway_strings( $gateway );
		}

	}

	public function translate_gateway_strings( WC_Payment_Gateway $gateway ) {
		// @todo debug
		/* @phpstan-ignore isset.property */
		if ( isset( $gateway->enabled ) && 'no' !== $gateway->enabled ) {
			if ( isset( $gateway->instructions ) ) {
				$gateway->instructions = $this->translate_gateway_instructions( $gateway->instructions, $gateway->id );
			}
			/* @phpstan-ignore isset.property */
			if ( isset( $gateway->description ) ) {
				$gateway->description = $this->translate_gateway_description( $gateway->description, $gateway->id );
			}
			/* @phpstan-ignore isset.property */
			if ( isset( $gateway->title ) ) {
				$gateway->title = $this->translate_gateway_title( $gateway->title, $gateway->id );
			}
		}

	}

	public function translate_gateway_title( $title, $gateway_id ) {
		return $this->get_translated_gateway_string( $title, $gateway_id, 'title' );
	}

	/**
	 * @since WooCommerce PayPal Payments 3.3.0
	 *
	 * @param string $description Gateway description (already sanitized with wp_kses_post).
	 * @param object $gateway     Gateway instance.
	 * @see \WooCommerce\PayPalCommerce\WcGateway\Gateway\PayPalGateway
	 *
	 * @return string
	 */
	public function translate_paypal_payments_gateway_description( $description, $gateway ) {
		$id = \WPML\FP\Obj::prop( 'id', $gateway );
		if ( is_null( $id ) ) {
			return $description;
		}

		return $this->translate_gateway_description( $description, $id );
	}

	public function translate_gateway_description( $description, $gateway_id ) {
		return $this->get_translated_gateway_string( $description, $gateway_id, 'description' );
	}

	public function translate_gateway_instructions( $instructions, $gateway_id ) {
		return $this->get_translated_gateway_string( $instructions, $gateway_id, 'instructions' );
	}

	public function get_translated_gateway_string( $string, $gateway_id, $name ) {
		if ( ! is_string( $string ) ) { /** @see https://onthegosystems.myjetbrains.com/youtrack/issue/wcml-4735 */
			return $string;
		}

		$gatewayLanguage  = $this->get_current_gateway_language();
		$translatedString = apply_filters(
			'wpml_translate_single_string',
			$string,
			Strings::TRANSLATION_DOMAIN,
			Strings::getStringName( $gateway_id, $name ),
			$gatewayLanguage
		);

		if ( $translatedString !== $string ) {
			return $translatedString;
		}

		if ( $this->isSendingOrderDetails() ) {
			$this->sitepress->switch_lang( $gatewayLanguage );
		}

		if ( 'cheque' === $gateway_id && 'title' === $name ) {
			/* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText */
			$translatedString = _x( $string, 'Check payment method', 'woocommerce' );
		} else {
			/* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText */
			$translatedString = __( $string, 'woocommerce' );
		}

		return $translatedString;
	}

	/**
	 * @return string
	 */
	private function get_current_gateway_language() {
		/* phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected */
		$postData = wpml_collect( $_POST );
		if ( $postData->isNotEmpty() ) {
			if ( $this->is_user_order_note( $postData ) ) {
				$current_gateway_language = WCML_Orders::getLanguage( (int) $postData->get( 'post_id' ) );
			} elseif ( $this->is_refund_line_item( $postData ) ) {
				$current_gateway_language = WCML_Orders::getLanguage( (int) $postData->get( 'order_id' ) );
			} else {
				$current_gateway_language = $this->get_order_action_gateway_language( $postData );
			}
		} else {
			$current_gateway_language = $this->get_order_ajax_action_gateway_language();
		}

		/**
		 * Filters the current gateway language
		 *
		 * @since 4.9.0
		 *
		 * @param string $current_gateway_language
		 */
		return apply_filters( 'wcml_current_gateway_language', $current_gateway_language );
	}

	/**
	 * @param Collection $postData
	 *
	 * @return bool
	 */
	private function is_user_order_note( Collection $postData ) {
		return 'woocommerce_add_order_note' === $postData->get( 'action' ) && 'customer' === $postData->get( 'note_type' );
	}

	/**
	 * @param Collection $postData
	 *
	 * @return bool
	 */
	private function is_refund_line_item( Collection $postData ){
		return 'woocommerce_refund_line_items' === $postData->get( 'action' );
	}

	/**
	 * @return bool
	 */
	private function isSendingOrderDetails() {
		/* phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected */
		$postData = wpml_collect( $_POST );
		return $postData->get( 'post_ID' )
			&& 'shop_order' === $postData->get( 'post_type' )
			&& 'send_order_details' === $postData->get( 'wc_order_action' );
	}


	/**
	 * @param Collection $postData
	 *
	 * @return string
	 */
	private function get_order_action_gateway_language( Collection $postData ) {

		if ( $postData->get( 'post_ID' ) ) {

			$is_saving_new_order = wpml_collect( [
				'auto-draft',
				'draft'
			] )->contains( $postData->get( 'post_status' ) )
				&& 'editpost' === $postData->get( 'action' )
				&& $postData->get( 'save' );
			if ( $is_saving_new_order && isset( $_COOKIE[ WCML_Orders::DASHBOARD_COOKIE_NAME ] ) ) {
				return $_COOKIE[ WCML_Orders::DASHBOARD_COOKIE_NAME ];
			}

			$is_order_emails_status = wpml_collect( [
				'wc-completed',
				'wc-processing',
				'wc-refunded',
				'wc-on-hold'
			] )->contains( $postData->get( 'order_status' ) );

			$is_send_order_details_action = $this->isSendingOrderDetails();
			if ( $is_order_emails_status || $is_send_order_details_action ) {
				return WCML_Orders::getLanguage( (int) $postData->get( 'post_ID' ) );
			}
		}

		return $this->current_language;
	}

	/**
	 * @return string
	 */
	private function get_order_ajax_action_gateway_language(){

		$getData = wpml_collect( $_GET );
		if ( $getData->isNotEmpty() ) {
			$is_order_ajax_action = 'woocommerce_mark_order_status' === $getData->get( 'action' ) && wpml_collect( [
					'completed',
					'processing'
				] )->contains( $getData->get( 'status' ) );
			if ( $is_order_ajax_action && $getData->get( 'order_id' ) ) {
				return WCML_Orders::getLanguage( (int) $getData->get( 'order_id' ) );
			}
		}

		return $this->current_language;
	}

	private function get_gateway_string_id( $value, $gateway_id, $name ) {
		return icl_get_string_id( $value, Strings::TRANSLATION_DOMAIN, $gateway_id . '_gateway_' . $name );
	}

	public function set_bacs_gateway_currency() {
		foreach ( $_POST as $key => $value ) {

			if ( '_enabled' === substr( $key, -8 ) ) {
				$gateway = str_replace( '_enabled', '', $key );
			}
		}

		if ( isset( $gateway ) ) {
			if ( 'woocommerce_bacs' === $gateway && isset( $_POST['bacs-currency'] ) ) {
				update_option( self::WCML_BACS_ACCOUNTS_CURRENCIES_OPTION, array_map( [ Sanitize::class, 'string' ], $_POST['bacs-currency'] ) );
			}
		}

	}

	public function get_gateway_text_keys_to_translate() {
		return apply_filters( 'wcml_gateway_text_keys_to_translate', Strings::TRANSLATABLE_SETTINGS );
	}

	public function append_currency_selector_to_bacs_account_settings() {

		$template_loader        = new WPML_Twig_Template_Loader( [ $this->sitepress->get_wp_api()->constant( 'WCML_PLUGIN_PATH' ) . '/templates/multi-currency/' ] );
		$currencies_dropdown_ui = new WCML_Currencies_Dropdown_UI( $template_loader );

		list( $default_dropdown, $currencies_output ) = $this->get_dropdown( $currencies_dropdown_ui );

		wp_enqueue_script( 'wcml-bacs-accounts-currencies', WCML_PLUGIN_URL . '/res/js/bacs-accounts-currencies' . WCML_JS_MIN . '.js', [ 'jquery' ], WCML_VERSION, true );
		wp_localize_script(
			'wcml-bacs-accounts-currencies',
			'wcml_data',
			[
				'currencies_dropdown' => $currencies_output,
				'label'               => __( 'Currency', 'woocommerce-multilingual' ),
				'default_dropdown'    => $default_dropdown,
			]
		);
	}

	/**
	 * @param WCML_Currencies_Dropdown_UI $currencies_dropdown_ui
	 *
	 * @return array
	 */
	public function get_dropdown( $currencies_dropdown_ui ) {

		$bacs_settings            = get_option( 'woocommerce_bacs_accounts', [] );
		$active_currencies        = $this->woocommerce_wpml->multi_currency->get_currency_codes();
		$default_currency         = wcml_get_woocommerce_currency_option();
		$bacs_accounts_currencies = get_option( self::WCML_BACS_ACCOUNTS_CURRENCIES_OPTION, [] );
		$currencies_output        = [];

		$default_dropdown = $currencies_dropdown_ui->get( $active_currencies, $default_currency );

		if ( $bacs_settings ) {
			foreach ( $bacs_settings as $id => $account_settings ) {
				$currencies_output[ $id ] = isset( $bacs_accounts_currencies[ $id ] ) ? $currencies_dropdown_ui->get( $active_currencies, $bacs_accounts_currencies[ $id ] ) : $default_dropdown;
			}
		} else {
			$currencies_output[] = $default_dropdown;
		}

		return [ $default_dropdown, $currencies_output ];
	}
}
