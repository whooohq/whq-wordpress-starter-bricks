<?php

namespace WCML\Utilities;

use WPML\FP\Lst;
use WPML\FP\Obj;

class WcAdminPages {

	const SECTION_BACS = 'bacs';

	/**
	 * @param string|array $sections A single section (string) or one of multiple sections (array).
	 *
	 * @return bool
	 */
	public static function isSection( $sections ) {
		// phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		return Lst::includes( Obj::prop( 'section', $_GET ), (array) $sections );
	}

	/**
	 * @return bool
	 */
	public static function hasSection() {
		return (bool) Obj::prop( 'section', $_GET );
	}

	/**
	 * @return bool
	 */
	private static function isSettingsPage() {
		return self::isAdminPhpPage( AdminUrl::PAGE_WOO_SETTINGS );
	}

	/**
	 * @return bool
	 */
	public static function isHomeScreen() {
		return self::isAdminPhpPage( 'wc-admin' );
	}

	/**
	 * @return bool
	 */
	public static function isPaymentSettings() {
		return self::isSettingsPage() && AdminPages::isTab( 'checkout' );
	}

	/**
	 * @return bool
	 */
	public static function isEmailSettings() {
		return self::isSettingsPage() &&  AdminPages::isTab( 'email' );
	}

	/**
	 * @return bool
	 */
	public static function isShippingSettings() {
		return self::isSettingsPage() && AdminPages::isTab( 'shipping' );
	}

	/**
	 * @return bool
	 */
	public static function isAdvancedSettings() {
		return self::isSettingsPage() && AdminPages::isTab( 'advanced' );
	}

	/**
	 * @param string $page
	 *
	 * @return bool
	 */
	private static function isAdminPhpPage( $page ) {
		global $pagenow;

		return is_admin() && 'admin.php' === $pagenow && AdminPages::isPage( $page );
	}

}
