<?php

use WPML\FP\Obj;

class WCML_Emails {

	const PRIORITY_AFTER_STATUS_CHANGE_EMAIL = 11;
	const PRIORITY_BEFORE_EMAIL_SET_LANGUAGE = 9;

	/** @var int|false $order_id */
	private $order_id = false;

	/** @var string|false $locale */
	private $locale = false;

	/** @var string|false $admin_language */
	private $admin_language = false;

	/** @var null|string $rest_language */
	private $rest_language;

	/** @var WCML_WC_Strings */
	private $wcmlStrings;

	/** @var SitePress */
	private $sitepress;

	/** @var WooCommerce $woocommerce */
	private $woocommerce;

	public function __construct( WCML_WC_Strings $wcmlStrings, SitePress $sitepress, WooCommerce $woocommerce ) {
		$this->wcmlStrings = $wcmlStrings;
		$this->sitepress   = $sitepress;
		$this->woocommerce = $woocommerce;
	}

	public function add_hooks() {
		// Wrappers for email's header.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action(
				'woocommerce_order_status_completed_notification',
				[
					$this,
					'email_heading_completed',
				],
				self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE
			);
			add_action( 'woocommerce_order_status_changed', [ $this, 'comments_language' ], 10 );

			$this->add_hooks_to_restore_language_for_admin_notes();
		}

		add_action( 'woocommerce_new_customer_note_notification', [ $this, 'email_heading_note' ], self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE );

		add_action( 'wp_ajax_woocommerce_mark_order_status', [ $this, 'email_refresh_in_ajax' ], self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE );

		foreach ( [ 'pending', 'failed', 'cancelled', 'on-hold' ] as $state ) {
			add_action(
				'woocommerce_order_status_' . $state . '_to_processing_notification',
				[
					$this,
					'email_heading_processing',
				],
				self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE
			);

			add_action(
				'woocommerce_order_status_' . $state . '_to_processing_notification',
				[
					$this,
					'refresh_email_lang',
				],
				self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE
			);
			add_action(
				'woocommerce_order_status_failed',
				[
					$this,
					'refresh_email_lang',
				],
				self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE
			);
		}

		foreach ( [ 'pending', 'failed', 'cancelled' ] as $state ) {
			add_action(
				'woocommerce_order_status_' . $state . '_to_on-hold_notification',
				[
					$this,
					'email_heading_on_hold',
				],
				self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE
			);
		}

		// Wrappers for email's body.
		add_action( 'woocommerce_before_resend_order_emails', [ $this, 'email_header' ] );
		add_action( 'woocommerce_after_resend_order_email', [ $this, 'email_footer' ] );

		// Filter string language before for emails.
		add_filter( 'icl_current_string_language', [ $this, 'icl_current_string_language' ], 10, 2 );

		// Change order status.
		add_action( 'woocommerce_order_status_completed', [ $this, 'refresh_email_lang_complete' ], self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE );

		add_action(
			'woocommerce_order_status_pending_to_on-hold_notification',
			[
				$this,
				'refresh_email_lang',
			],
			self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE
		);
		add_action( 'woocommerce_new_customer_note', [ $this, 'refresh_email_lang' ], self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE );

		foreach ( [ 'pending', 'failed' ] as $from_state ) {
			foreach ( [ 'processing', 'completed', 'on-hold' ] as $to_state ) {
				add_action(
					'woocommerce_order_status_' . $from_state . '_to_' . $to_state . '_notification',
					[
						$this,
						'new_order_admin_email',
					],
					self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE
				);
			}
		}

		add_action( 'woocommerce_before_resend_order_emails', [ $this, 'backend_new_order_admin_email' ], self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE );

		add_filter( 'plugin_locale', [ $this, 'set_locale_for_emails' ], 10, 2 );
		add_filter( 'woocommerce_countries', [ $this, 'translate_woocommerce_countries' ] );

		add_filter(
			'woocommerce_allow_send_queued_transactional_email',
			[
				$this,
				'send_queued_transactional_email',
			],
			10,
			3
		);

		add_action( 'woocommerce_order_partially_refunded_notification', [ $this, 'refresh_email_lang' ], self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE );
		add_action( 'woocommerce_order_fully_refunded_notification', [ $this, 'refresh_email_lang' ], self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE );
		add_filter( 'woocommerce_email_get_option', [ $this, 'filter_emails_strings' ], 10, 4 );

		add_filter( 'woocommerce_email_setup_locale', '__return_false' );
		add_filter( 'woocommerce_email_restore_locale', '__return_false' );

		add_filter( 'woocommerce_email_heading_new_order', [ $this, 'new_order_email_heading' ] );
		add_filter( 'woocommerce_email_subject_new_order', [ $this, 'new_order_email_subject' ] );

		add_filter(
			'woocommerce_email_heading_customer_on_hold_order',
			[
				$this,
				'customer_on_hold_order_heading',
			]
		);
		add_filter(
			'woocommerce_email_subject_customer_on_hold_order',
			[
				$this,
				'customer_on_hold_order_subject',
			]
		);

		add_filter(
			'woocommerce_email_heading_customer_processing_order',
			[
				$this,
				'customer_processing_order_heading',
			]
		);
		add_filter(
			'woocommerce_email_subject_customer_processing_order',
			[
				$this,
				'customer_processing_order_subject',
			]
		);

		add_action( 'woocommerce_low_stock_notification', [ $this, 'low_stock_admin_notification' ], self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE );
		add_action( 'woocommerce_no_stock_notification', [ $this, 'no_stock_admin_notification' ], self::PRIORITY_BEFORE_EMAIL_SET_LANGUAGE );

		add_filter( 'woocommerce_rest_pre_insert_shop_order_object', [ $this, 'set_rest_language' ], 10, 2 );
	}

	private function add_hooks_to_restore_language_for_admin_notes() {
		wpml_collect( [
			'pending',
			'processing',
			'on-hold',
			'completed',
			'cancelled',
			'refunded',
			'failed',
		] )->each( function ( $status ) {
			add_action( 'woocommerce_order_status_' . $status, [ $this, 'comments_language' ], self::PRIORITY_AFTER_STATUS_CHANGE_EMAIL );
		} );
	}

	public function email_refresh_in_ajax() {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		if ( ! check_admin_referer( 'woocommerce-mark-order-status' ) ) {
			return;
		}
		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		if ( isset( $_GET['order_id'] ) ) {
			$this->refresh_email_lang( (int) $_GET['order_id'] );

			if ( isset( $_GET['status'] ) && 'completed' === $_GET['status'] ) {
				$this->email_heading_completed( (int) $_GET['order_id'], true );
			}

			return true;
		}
		/* phpcs:enable WordPress.Security.NonceVerification.Recommended */
	}

	public function refresh_email_lang_complete( $order_id ) {

		$this->order_id = $order_id;
		$this->refresh_email_lang( $order_id );
		$this->email_heading_completed( $order_id, true );
	}

	/**
	 * Translate WooCommerce emails.
	 *
	 * @param array|object $order
	 */
	public function email_header( $order ) {

		if ( is_array( $order ) ) {
			$order = $order['order_id'];
		} elseif ( is_object( $order ) ) {
			$order = method_exists( 'WC_Order', 'get_id' ) ? $order->get_id() : $order->id;
		}

		$this->refresh_email_lang( $order );
	}


	public function refresh_email_lang( $order_id ) {

		$language = $this->get_order_language( $order_id );

		if ( ! empty( $language ) ) {
			$this->change_email_language( $language );
		}

		$this->force_translating_admin_options_in_backend();
	}

	/**
	 * Run before preparing the email to get admin options in the correct language.
	 */
	private function force_translating_admin_options_in_backend() {

		do_action(
			'wpml_st_force_translate_admin_options',
			[
				'woocommerce_checkout_privacy_policy_text',
				'woocommerce_email_footer_text',
				'woocommerce_email_from_address',
				'woocommerce_email_from_name',
				'woocommerce_price_decimal_sep',
				'woocommerce_price_thousand_sep',
				'woocommerce_registration_privacy_policy_text',
			]
		);
	}

	/**
	 * @param array|int $order_id
	 *
	 * @return null|string
	 */
	public function get_order_language( $order_id ) {

		if ( is_array( $order_id ) ) {
			if ( isset( $order_id['order_id'] ) ) {
				$order_id = $order_id['order_id'];
			} else {
				return null;
			}
		}

		$language = WCML_Orders::getLanguage( $order_id );

		if ( ! $language ) {
			$language = $this->rest_language;
		}

		return $language;
	}

	/**
	 * After email translation switch language to default.
	 */
	public function email_footer() {
		$this->sitepress->switch_lang( $this->sitepress->get_default_language() );
	}

	public function comments_language() {

		if ( is_admin() && false !== $this->admin_language ) {
			$this->change_email_language( $this->admin_language );
		} else {
			$this->change_email_language( $this->wcmlStrings->get_domain_language( 'woocommerce' ) );
		}
	}


	public function email_heading_completed( $order_id, $no_checking = false ) {
		$email = $this->getEmailObject( WC_Email_Customer_Completed_Order::class, $no_checking );

		if ( $email instanceof WC_Email_Customer_Completed_Order ) {
			$translate = $this->getTranslatorFor(
				'admin_texts_woocommerce_customer_completed_order_settings',
				'[woocommerce_customer_completed_order_settings]',
				WCML_Orders::getLanguage( $order_id ),
				$email
			);

			$email->heading              = $translate( 'heading' );
			$email->subject              = $translate( 'subject' );
			@$email->heading_downloadable = $translate( 'heading_downloadable' );
			@$email->subject_downloadable = $translate( 'subject_downloadable' );
			$original_enabled_state      = $email->enabled;
			$email->enabled              = 'no';
			$email->trigger( $order_id );
			$email->enabled = $original_enabled_state;
		}
	}

	public function email_heading_processing( $order_id ) {
		$this->translate_email_headings( $order_id, 'WC_Email_Customer_Processing_Order', 'woocommerce_customer_processing_order_settings' );
	}

	public function customer_processing_order_heading( $heading ) {
		return $this->get_translated_order_strings( 'heading', $heading, 'WC_Email_Customer_Processing_Order' );
	}

	public function customer_processing_order_subject( $subject ) {
		return $this->get_translated_order_strings( 'subject', $subject, 'WC_Email_Customer_Processing_Order' );
	}


	public function email_heading_on_hold( $order_id ) {
		$this->translate_email_headings( $order_id, 'WC_Email_Customer_On_Hold_Order', 'woocommerce_customer_on_hold_order_settings' );
	}

	/**
	 * @param int|string $order_id
	 * @param string     $class_name
	 * @param string     $string_name
	 */
	private function translate_email_headings( $order_id, $class_name, $string_name ) {
		$email = $this->getEmailObject( $class_name );

		if ( $email ) {
			$translate = $this->getTranslatorFor(
				'admin_texts_' . $string_name,
				'[' . $string_name . ']',
				WCML_Orders::getLanguage( $order_id ),
				$email
			);

			$email->heading         = $translate( 'heading' );
			$email->subject         = $translate( 'subject' );
			$original_enabled_state = $email->enabled;
			$email->enabled         = 'no';
			if ( method_exists( $email, 'trigger' ) ) {
				$email->trigger( $order_id );
			}
			$email->enabled = $original_enabled_state;
		}
	}

	public function customer_on_hold_order_heading( $heading ) {
		return $this->get_translated_order_strings( 'heading', $heading, 'WC_Email_Customer_On_Hold_Order' );
	}

	public function customer_on_hold_order_subject( $subject ) {
		return $this->get_translated_order_strings( 'subject', $subject, 'WC_Email_Customer_On_Hold_Order' );
	}

	public function email_heading_note( $args ) {
		$email = $this->getEmailObject( WC_Email_Customer_Note::class );

		if ( $email instanceof WC_Email_Customer_Note ) {
			$translate = $this->getTranslatorFor(
				'admin_texts_woocommerce_customer_note_settings',
				'[woocommerce_customer_note_settings]',
				null,
				$email
			);

			$email->heading         = $translate( 'heading' );
			$email->subject         = $translate( 'subject' );
			$original_enabled_state = $email->enabled;
			$email->enabled         = 'no';
			$email->trigger( $args );
			$email->enabled = $original_enabled_state;
		}
	}

	/**
	 * @param string   $value
	 * @param WC_Email $email
	 * @param string   $old_value
	 * @param string   $key
	 *
	 * @return mixed
	 */
	public function filter_emails_strings( $value, WC_Email $email, $old_value, $key ) {

		$translated_value = false;
		$emailStrings     = wpml_collect( [
			'subject',
			'subject_downloadable',
			'subject_partial',
			'subject_full',
			'subject_paid',
			'heading',
			'heading_paid',
			'heading_downloadable',
			'heading_partial',
			'heading_full',
			'additional_content',
		] );

		if (
			/* @phpstan-ignore isset.property */
			isset( $email->object ) &&
			$emailStrings->contains( $key )
		) {

			$isAdminEmail = wpml_collect([
				'new_order',
				'cancelled_order',
				'failed_order',
			])->contains( $email->id );

			$translated_value = $this->get_email_translated_string( $key, $email, $isAdminEmail, $value );
		}

		return $translated_value ?: $value;
	}

	/**
	 * @param string      $key
	 * @param WC_Email    $email
	 * @param bool        $isAdminEmail
	 * @param string|null $originalValue
	 * @param string      $originalDomain
	 *
	 * @return string
	 */
	public function get_email_translated_string( $key, $email, $isAdminEmail, $originalValue = null, $originalDomain = 'woocommerce' ) {

		list( $context, $name ) = $this->get_email_context_and_name( $email );
		$orderId                = $this->get_order_id_from_email_object( $email );

		$language = $isAdminEmail
			? $this->get_admin_language_by_email( $email->recipient, $orderId )
			: WCML_Orders::getLanguage( $orderId );

		return $this->getStringTranslation( $context, $name . $key, $language, $originalValue, $originalDomain );
	}

	/**
	 * @param WC_Email $emailObject
	 *
	 * @return array
	 */
	public function get_email_context_and_name( $emailObject ) {

		$emailId = $emailObject->id;

		if ( $emailObject instanceof WC_Email_Customer_Refunded_Order ) {
			$emailId = 'customer_refunded_order';
		}

		$context = 'admin_texts_woocommerce_' . $emailId . '_settings';
		$name    = '[woocommerce_' . $emailId . '_settings]';

		return [ $context, $name ];
	}

	/**
	 * @param WC_Email $email
	 *
	 * @return bool|string|int
	 */
	private function get_order_id_from_email_object( $email ) {
		if ( is_callable( [ $email->object, 'get_id' ] ) ) {
			return $email->object->get_id();
		}
		/* @phpstan-ignore isset.offset, booleanAnd.leftAlwaysFalse */
		if ( is_array( $email->object ) && isset( $email->object['ID'] ) ) {
			return $email->object['ID'];
		}

		return false;
	}

	public function new_order_admin_email( $order_id ) {
		$email = $this->getEmailObject( WC_Email_New_Order::class, true );

		if ( $email instanceof WC_Email_New_Order ) {
			$recipients = explode( ',', $email->get_recipient() );

			$allowResendForAllRecipients = function() use ( $recipients ) {
				static $numberOfAllowedResend;

				if ( null === $numberOfAllowedResend ) {
					$numberOfAllowedResend = count( $recipients );
				} else {
					$numberOfAllowedResend--;
				}

				return (bool) $numberOfAllowedResend;
			};

			add_filter( 'woocommerce_new_order_email_allows_resend', $allowResendForAllRecipients, 20 );

			foreach ( $recipients as $recipient ) {
				$admin_language = $this->get_admin_language_by_email( $recipient, $order_id );

				$this->change_email_language( $admin_language );

				$translate = $this->getTranslatorFor(
					'admin_texts_woocommerce_new_order_settings',
					'[woocommerce_new_order_settings]',
					$admin_language ?: WCML_Orders::getLanguage( $order_id ),
					$email
				);

				$email->heading   = $translate( 'heading' );
				$email->subject   = $translate( 'subject' );
				$email->recipient = $recipient;

				$email->trigger( $order_id );
			}

			add_filter(
				'woocommerce_email_enabled_new_order',
				self::getPreventDuplicatedNewOrderEmail( $order_id ),
				PHP_INT_MAX,
				2
			);

			$this->refresh_email_lang( $order_id );
		}
	}

	/**
	 * @param int $processedOrderId
	 *
	 * @return Closure ( bool, \WC_Order ) -> bool
	 */
	public static function getPreventDuplicatedNewOrderEmail( $processedOrderId ) {
		return function( $isEmailEnabled, $order ) use ( $processedOrderId ) {
			return $order->get_id() === $processedOrderId ? false : $isEmailEnabled;
		};
	}

	/**
	 * @param string       $recipient
	 * @param integer|bool $order_id
	 *
	 * @return string
	 */
	private function get_admin_language_by_email( $recipient, $order_id = false ) {
		$user = get_user_by( 'email', $recipient );
		if ( $user ) {
			$language = $this->sitepress->get_user_admin_language( $user->ID, true );
		} else {
			$language = $this->sitepress->get_default_language();
		}

		/**
		 * @deprecated since 4.12.0, use `wcml_get_admin_language_by_email` instead.
		 */
		$language = apply_filters( 'wcml_new_order_admin_email_language', $language, $recipient, $order_id );

		/**
		 * Filter admin email language for recipient
		 *
		 * @since 4.12.0
		 *
		 * @param string $admin_language Admin language
		 * @param string $recipient      Admin email
		 * @param int    $order_id       Order ID
		 */
		return apply_filters( 'wcml_get_admin_language_by_email', $language, $recipient, $order_id );
	}

	public function new_order_email_heading( $heading ) {
		return $this->get_translated_order_strings( 'heading', $heading, 'WC_Email_New_Order' );
	}

	public function new_order_email_subject( $subject ) {
		return $this->get_translated_order_strings( 'subject', $subject, 'WC_Email_New_Order' );
	}

	/**
	 * @param string $type
	 * @param string $order_string
	 * @param string $class_name
	 *
	 * @return string
	 */
	private function get_translated_order_strings( $type, $order_string, $class_name ) {
		$email = $this->getEmailObject( $class_name );

		if ( 'heading' === $type ) {
			$translated_string = $email->heading;
		} elseif ( 'subject' === $type ) {
			$translated_string = $email->subject;
		} else {
			return $order_string;
		}

		return $translated_string ? $email->format_string( $translated_string ) : $order_string;
	}

	public function backend_new_order_admin_email( $order_id ) {
		if ( isset( $_POST['wc_order_action'] ) && in_array(
			$_POST['wc_order_action'],
			[
				'send_email_new_order',
				'send_order_details_admin',
			]
		) ) {
			$this->new_order_admin_email( $order_id );
		}
	}

	public function change_email_language( $lang ) {
		if ( ! $this->admin_language ) {
			$this->admin_language = $this->sitepress->get_user_admin_language( get_current_user_id(), true );
		}

		$this->sitepress->switch_lang( $lang, true );
		$this->locale = $this->sitepress->get_locale( $lang );
	}

	/**
	 * @deprecated since WCML 4.12, use `getStringTranslation` instead.
	 *
	 * @param string $context
	 * @param string $name
	 * @param int|false  $order_id
	 * @param string|null   $language_code
	 *
	 * @return string|false
	 */
	public function wcml_get_translated_email_string( $context, $name, $order_id = false, $language_code = null ) {

		if ( $order_id && ! $language_code ) {
			$order_language = WCML_Orders::getLanguage( $order_id );
			if ( $order_language ) {
				$language_code = $order_language;
			}
		}

		return $this->wcmlStrings->get_translated_string_by_name_and_context( $context, $name, $language_code );
	}

	/**
	 * First we try to get the string translation from admin string.
	 * If falsy, we try to translate the string with the default gettext.
	 *
	 * @param string      $domain
	 * @param string      $name
	 * @param string|null $lang
	 * @param string|null $originalValue
	 * @param string      $originalDomain
	 *
	 * @return string
	 */
	public function getStringTranslation( $domain, $name, $lang = null, $originalValue = null, $originalDomain = 'woocommerce' ) {
		return $this->wcmlStrings->get_translated_string_by_name_and_context( $domain, $name, $lang ) ?: $this->getStringTranslationWithGettext( $originalValue, $originalDomain, $lang );
	}

	/**
	 * @param string $value
	 * @param string $domain
	 * @param string $lang
	 *
	 * @return string
	 */
	private function getStringTranslationWithGettext( $value, $domain, $lang ) {
		if ( $value && $lang ) {
			$switchLang  = new WPML_Temporary_Switch_Language( $this->sitepress, $lang );
			$translation = __( $value, $domain );
			$switchLang->restore_lang();

			return $translation;
		}

		return $value;
	}

	public function icl_current_string_language( $current_language, $name ) {
		$order_id = false;

		/* phpcs:disable WordPress.Security.NonceVerification */
		if ( isset( $_POST['action'] ) && 'editpost' === $_POST['action'] && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] && isset( $_POST['wc_order_action'] ) && 'send_email_new_order' !== $_POST['wc_order_action'] ) {
			$order_id = filter_input( INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT );
		} elseif ( isset( $_POST['action'] ) && 'woocommerce_add_order_note' === $_POST['action'] && isset( $_POST['note_type'] ) && 'customer' === $_POST['note_type'] ) {
			$order_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		} elseif ( isset( $_GET['action'] ) && isset( $_GET['order_id'] ) && ( 'woocommerce_mark_order_complete' === $_GET['action'] || 'woocommerce_mark_order_status' === $_GET['action'] ) ) {
			$order_id = filter_input( INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		} elseif ( WCML\COT\Helper::isOrderEditAdminScreen() ) {
			$order_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
		} elseif ( isset( $_GET['action'] ) && 'mark_completed' === $_GET['action'] && $this->order_id ) {
			$order_id = $this->order_id;
		} elseif ( isset( $_POST['action'] ) && 'woocommerce_refund_line_items' === $_POST['action'] ) {
			$order_id = filter_input( INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		} elseif ( empty( $_POST ) && isset( $_GET['page'] ) && \WCML\Utilities\AdminUrl::PAGE_WOO_SETTINGS === $_GET['page'] && isset( $_GET['tab'] ) && 'email' === $_GET['tab'] && '[woocommerce' === substr( $name, 0, 12 ) ) {
			$email_string = explode( ']', str_replace( '[', '', $name ) );
			$email_option = get_option( $email_string[0], [] );
			$context      = 'admin_texts_' . $email_string[0];

			$text = $email_option[ $email_string[1] ] ?? null;
			if ( null !== $text ) {
				$current_language = $this->wcmlStrings->get_string_language( $text, $context, $name );
			}
		} elseif ( $this->order_id ) {
			$order_id = $this->order_id;
		}
		/* phpcs:enable WordPress.Security.NonceVerification */

		$order_id = apply_filters( 'wcml_send_email_order_id', $order_id );

		if ( $order_id ) {
			$order_language = WCML_Orders::getLanguage( $order_id );
			if ( $order_language ) {
				$current_language = $order_language;
			} else {
				$current_language = $this->sitepress->get_current_language();
			}
		}

		return apply_filters( 'wcml_email_language', $current_language, $order_id );
	}

	/**
	 * Set correct locale code for emails.
	 *
	 * @param string $locale
	 * @param string $domain
	 *
	 * @return string
	 */
	public function set_locale_for_emails( $locale, $domain ) {

		if ( 'woocommerce' === $domain && $this->locale ) {
			$locale = $this->locale;
		}

		return $locale;
	}

	public function translate_woocommerce_countries( $countries ) {

		/* phpcs:disable WordPress.Security.NonceVerification.Missing */
		if ( isset( $_POST['wc_order_action'] ) && 'send_email_new_order' !== $_POST['wc_order_action'] && isset( $_POST['post_ID'] ) ) {
			$current_language = $this->sitepress->get_current_language();
			$this->refresh_email_lang( (int) $_POST['post_ID'] );
			$countries = include WC()->plugin_path() . '/i18n/countries.php';
			$this->change_email_language( $current_language );
		}
		/* phpcs:enable WordPress.Security.NonceVerification.Missing */

		return $countries;
	}


	public function send_queued_transactional_email( $allow, $filter, $args ) {
		$this->order_id = $args[0];

		return $allow;
	}

	/**
	 * @param string $emailClass
	 * @param bool   $ignoreClassExists
	 *
	 * @return WC_Email|null
	 */
	private function getEmailObject( $emailClass, $ignoreClassExists = false ) {

		$wcEmails = $this->woocommerce->mailer();
		if (
			( $ignoreClassExists || class_exists( $emailClass ) )
			&& isset( $wcEmails->emails[ $emailClass ] )
		) {
			return $wcEmails->emails[ $emailClass ];
		}

		return null;
	}

	/**
	 * @param string      $domain
	 * @param string      $namePrefix
	 * @param string|null $languageCode
	 * @param WC_Email    $email
	 *
	 * @return Closure
	 */
	private function getTranslatorFor( $domain, $namePrefix, $languageCode, $email ) {
		return function( $field ) use ( $domain, $namePrefix, $languageCode, $email ) {
			return $this->getStringTranslation( $domain, $namePrefix . $field, $languageCode, Obj::prop( $field, $email ) );
		};
	}

	/**
	 * @param WC_Product $product
	 */
	public function low_stock_admin_notification( $product ) {
		$this->admin_notification( $product, 'woocommerce_low_stock_notification', 'low_stock' );
	}

	/**
	 * @param WC_Product $product
	 */
	public function no_stock_admin_notification( $product ) {
		$this->admin_notification( $product, 'woocommerce_no_stock_notification', 'no_stock' );
	}

	/**
	 * @param WC_Product $product
	 * @param string     $action
	 * @param string     $method
	 */
	private function admin_notification( $product, $action, $method ) {

		$wcEmails = $this->woocommerce->mailer();

		remove_action( $action, [ $wcEmails, $method ] );

		if ( method_exists( $wcEmails, $method ) ) {
			$admin_language               = $this->get_admin_language_by_email( get_option( 'woocommerce_stock_email_recipient' ) );
			$product_id_in_admin_language = wpml_object_id_filter(
				$product->get_id(),
				'product',
				true,
				$admin_language
			);

			$this->sitepress->switch_lang( $admin_language );
			$wcEmails->$method( wc_get_product( $product_id_in_admin_language ) );
			$this->sitepress->switch_lang();
		}
	}

	/**
	 * @param WC_Data         $order    Object object.
	 * @param WP_REST_Request $request  Request object.
	 *
	 * @return WC_Data
	 */
	public function set_rest_language( $order, $request ) {

		$this->rest_language = isset( $request['lang'] ) ? $request['lang'] : null;

		return $order;
	}
}
