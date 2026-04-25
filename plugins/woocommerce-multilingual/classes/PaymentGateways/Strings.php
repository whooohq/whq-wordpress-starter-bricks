<?php

namespace WCML\PaymentGateways;

use WCML\Utilities\AdminUrl;

class Strings {

	const TRANSLATION_DOMAIN = 'admin_texts_woocommerce_gateways';

	const TRANSLATABLE_SETTINGS = [
		'title',
		'description',
		'instructions',
	];

	/**
	 * @param string $gatewayId
	 * @param string $stringName
	 *
	 * @return string
	 */
	public static function getStringName( $gatewayId, $stringName ) {
		return $gatewayId . '_gateway_' . $stringName;
	}

	/**
	 * @return string
	 */
	public static function getTranslationInstructions() {
		return sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
			esc_html__( 'To translate custom text for payment methods, go to the %1$sTranslation Dashboard%2$s.', 'woocommerce-multilingual' ),
			'<a href="' . esc_url( AdminUrl::getWPMLTMDashboardStringDomain( self::TRANSLATION_DOMAIN ) ) . '">',
			'</a>'
		);
	}
}
