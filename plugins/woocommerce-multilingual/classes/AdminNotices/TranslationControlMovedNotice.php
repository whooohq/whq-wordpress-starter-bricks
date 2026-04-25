<?php

namespace WCML\AdminNotices;

use WPML\LIB\WP\User;
use WPML_Notices;
use IWPML_Backend_Action;
use IWPML_Frontend_Action;
use IWPML_DIC_Action;

class TranslationControlMovedNotice implements IWPML_Backend_Action, IWPML_Frontend_Action, IWPML_DIC_Action {
	const NOTICE_ID = 'wcml-translation-control-moved';
	const NOTICE_GROUP = 'wcml-admin-notices';

	const NOTICE_CLASSES = [
		'wcml-translation-control-moved-notice',
	];

	/** @var WPML_Notices $wpmlNotices */
	private $wpmlNotices;

	/**
	 * @param WPML_Notices $wpmlNotices
	 */
	public function __construct( WPML_Notices $wpmlNotices ) {
		$this->wpmlNotices = $wpmlNotices;
	}

	public function add_hooks() {
		add_action( 'admin_notices', [ $this, 'addNoticeWhenWPMLIsActive' ] );
	}

	public function addNoticeWhenWPMLIsActive() {

		$notice = $this->wpmlNotices->get_new_notice(
			self::NOTICE_ID,
			$this->getNoticeText(),
			self::NOTICE_GROUP
		);

		if ( $this->wpmlNotices->is_notice_dismissed( $notice ) ) {
			return;
		}

		$notice->set_css_class_types( 'info' );
		$notice->set_css_classes( self::NOTICE_CLASSES );
		$notice->set_dismissible( true );
		$notice->add_user_restriction( User::getCurrentId() );
		$notice->set_restrict_to_screen_ids( [ 'woocommerce_page_wpml-wcml' ] );

		$this->wpmlNotices->add_notice( $notice );
	}

	private function getNoticeText(): string {

		$text = '';
		$text .= '<h2>' . esc_html__( 'The Translation Controls Have Moved', 'woocommerce-multilingual' ) . '</h2>';

		$text .= '<p>' . esc_html__( 'We updated WPML Multilingual & Multicurrency for WooCommerce to streamline your translation work.', 'woocommerce-multilingual' ) . '</p>';

		$text .= '<p><strong>';

		$text .= sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
			esc_html__( 'Translate your whole site\'s content, including products, from %1$sWPML &rarr; Translation Management%2$s.', 'woocommerce-multilingual' ),
			'<a href="' . \WCML\Utilities\AdminUrl::getWPMLTMDashboard() . '">',
			'</a>'
		);

		$text .= '</strong>';
		$text .= '<span class="recommended-badge">Recommended</span></p>';

		return $text;
	}
}
