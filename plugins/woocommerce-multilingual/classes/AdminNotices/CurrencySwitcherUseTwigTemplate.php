<?php

namespace WCML\AdminNotices;

use woocommerce_wpml;
use WPML\FP\Obj;
use WPML_Notices;

/**
 * Requirements:
 * - multi-currency mode: On
 * - currency switcher does not have a PHP template (only TWIG is found)
 */
class CurrencySwitcherUseTwigTemplate implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	const NOTICE_ID = 'wcml-multi-currency-currency-switcher-php-template-missing4';

	/** @var woocommerce_wpml */
	private $wcml;

	/** @var WPML_Notices */
	private $notices;

	public function __construct( woocommerce_wpml $wcml, WPML_Notices $notices ) {
		$this->wcml    = $wcml;
		$this->notices = $notices;
	}

	public function add_hooks() {
		if ( wcml_is_multi_currency_on() ) {
			add_action( 'admin_init', [ $this, 'initNotice' ] );
		}
	}

	/**
	 * Add hooks to manage visibility of notice.
	 */
	public function initNotice() {
		$notice      = $this->notices->get_notice( self::NOTICE_ID );
		$needsNotice = wcml_is_multi_currency_on() && $this->hasUniqueCurrency();

		if ( $needsNotice && ! $notice ) {
			add_action( 'wpml_currency_switcher_uses_twig_templates', [ $this, 'addNotice' ] );
		} elseif ( ! $needsNotice && ( $notice instanceof \WPML_Notice ) ) {
			$this->removeNotice( $notice );
		}
	}

	public function addNotice( string $templateSlug ) {
		$activeTemplateNames = array_keys( $this->wcml->cs_templates->get_active_templates() );
		if ( ! in_array( $templateSlug, $activeTemplateNames, true ) ) {
			return;
		}

		$text = '<h2>' . esc_html__( 'Important: Action Needed for Your Currency Switcher', 'woocommerce-multilingual' ) . '</h2>';
		$text .= '<p>' . esc_html__( 'We detected that your site is using a Currency Switcher built with Twig templates. For security reasons, support for Twig-based templates will be discontinued in the upcoming version of WPML Multilingual & Multicurrency for WooCommerce 5.6.', 'woocommerce-multilingual' ) . '</p>';
		$text .= '<p>' . esc_html__( 'To ensure your Currency Switcher continues working smoothly:', 'woocommerce-multilingual' ) . '</p>';

		$text .= '<ul>';
		$text .= '<li>';
		$text .= sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
			esc_html__( '- Switch to a PHP-based template - you can select one from the %1$sconfiguration page%2$s.', 'woocommerce-multilingual' ),
			'<a href="' . \WCML\Utilities\AdminUrl::getMultiCurrencyTab( 'currency-switcher-product' ) . '">',
			'</a>'
		);
		$text .= '</li>';
		$text .= '<li>';
		$text .= sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
			esc_html__( '- Need a custom solution? %1$sContact our support%2$s - we’re here to help you migrate safely.', 'woocommerce-multilingual' ),
			'<a href="' . \WCML_Tracking_Link::getWpmlSupport() . '">',
			'</a>'
		);
		$text .= '</li>';
		$text .= '</ul>';
		$text .= '<p>' . esc_html__( 'We recommend updating your Currency Switcher as soon as possible to avoid disruptions when the new version of WPML Multilingual & Multicurrency for WooCommerce is released.', 'woocommerce-multilingual' ) . '</p>';

		$notice = $this->notices->create_notice( self::NOTICE_ID, $text );
		$notice->set_css_class_types( 'notice-warning' );
		$notice->set_restrict_to_screen_ids( RestrictedScreens::get() );
		$notice->set_dismissible( true );

		$this->notices->add_notice( $notice );
	}

	/**
	 * Remove the notice if the problem has been fixed
	 */
	private function removeNotice( \WPML_Notice $notice ) {
		$this->notices->remove_notice( $notice->get_group(), $notice->get_id() );
	}

	private function hasUniqueCurrency(): bool {
		return count( (array) Obj::path( [ 'settings', 'currency_options' ], $this->wcml ) ) >= 1;
	}
}
