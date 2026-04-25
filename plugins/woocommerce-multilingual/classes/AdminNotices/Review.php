<?php

namespace WCML\AdminNotices;

use WCML\StandAlone\IStandAloneAction;
use WPML_Notices;
use IWPML_Backend_Action;
use IWPML_Frontend_Action;
use IWPML_DIC_Action;
use function WCML\functions\isStandAlone;

class Review implements IWPML_Backend_Action, IWPML_Frontend_Action, IWPML_DIC_Action, IStandAloneAction {

	const OPTION_NAME = 'wcml-rate-notice';

	/** @var WPML_Notices $wpmlNotices */
	private $wpmlNotices;

	/**
	 * @param WPML_Notices $wpmlNotices
	 */
	public function __construct( WPML_Notices $wpmlNotices ) {
		$this->wpmlNotices = $wpmlNotices;
	}

	public function add_hooks() {
		if ( isStandAlone() ) {
			add_action( 'admin_notices', [ $this, 'addNotice' ] );
			add_action( 'woocommerce_after_order_object_save', [ $this, 'onNewOrder' ] );
		}
	}

	public function addNotice() {

		if ( $this->shouldDisplayNotice() ) {
			$notice = $this->wpmlNotices->get_new_notice( 'wcml-rate', $this->getNoticeText(), 'wcml-admin-notices' );

			if ( $this->wpmlNotices->is_notice_dismissed( $notice ) ) {
				return;
			}

			$notice->set_css_class_types( 'info' );
			$notice->set_css_classes( [ 'otgs-notice-wcml-rating' ] );
			$notice->set_dismissible( true );

			$reviewLink   = 'https://wordpress.org/support/plugin/woocommerce-multilingual/reviews/?filter=5#new-post';
			$reviewButton = $this->wpmlNotices->get_new_notice_action( esc_html__( 'Review WPML Multilingual & Multicurrency for WooCommerce', 'woocommerce-multilingual' ), $reviewLink, false, false, true );
			$notice->add_action( $reviewButton );

			$notice->set_restrict_to_screen_ids( RestrictedScreens::get() );
			$notice->add_capability_check( [ 'manage_options', 'wpml_manage_woocommerce_multilingual' ] );
			$this->wpmlNotices->add_notice( $notice );
		}
	}

	private function getNoticeText(): string {
		$text = '<h2>';
		$text .= esc_html__( 'Congrats on making your first multicurrency sale!', 'woocommerce-multilingual' );
		$text .= '</h2>';

		$text .= '<p>';
		$text .= sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
			esc_html__( 'Want to help <strong>WPML Multilingual & Multicurrency for WooCommerce</strong> plugin? %1$sGive us a review%2$s!', 'woocommerce-multilingual' ),
			'<a href="https://wordpress.org/support/plugin/woocommerce-multilingual/reviews/?filter=5#new-post" class="wpml-external-link" target="_blank">',
			'</a>'
		);
		$text .= '</p>';

		return $text;
	}

	private function shouldDisplayNotice(): bool {
		return get_option( self::OPTION_NAME, false );
	}

	/**
	 * @param \WC_Order $order
	 */
	public function onNewOrder( $order ) {
		if ( ! $this->shouldDisplayNotice() ) {
			$this->maybeAddOptionToShowNotice( $order );
		}
	}

	private function maybeAddOptionToShowNotice( \WC_Order $order ) {
		if ( $order->is_paid() && $this->isOrderInSecondCurrency( $order ) ) {
			add_option( self::OPTION_NAME, true );
		}
	}

	private function isOrderInSecondCurrency( \WC_Order $order ): bool {
		return wcml_is_multi_currency_on()
		       && $order->get_currency() !== wcml_get_woocommerce_currency_option();
	}
}
