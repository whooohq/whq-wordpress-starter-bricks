<?php

namespace WCML\Utilities;

use function WCML\functions\isStandAlone;

class AdminUrl {
	const PAGE_WPML_WCML = \WCML_Admin_Menus::SLUG;

	const PAGE_WOO_SETTINGS = 'wc-settings';

	const TAB_PRODUCTS                = 'products';
	const TAB_MULTILINGUAL            = AdminPages::TAB_MULTILINGUAL;
	const TAB_MULTILINGUAL_STANDALONE = AdminPages::TAB_MULTILINGUAL_STANDALONE;
	const TAB_MULTICURRENCY           = AdminPages::TAB_MULTICURRENCY;
	const TAB_TROUBLESHOOTING         = 'troubleshooting';
	const TAB_STORE_URL               = 'slugs';
	const TAB_STATUS                  = 'status';

	const TAB_SETTINGS    = 'settings';
	const SRC_SETUP_LATER = 'setup_later';

	const DASHBOARD_PARAM_SECTIONS      = 'sections';
	const DASHBOARD_SECTION_PRODUCT     = 'post/product';
	const DASHBOARD_SECTION_STRING      = 'string';
	const DASHBOARD_PARAM_STRING_DOMAIN = 'predefinedStringDomain';

	private static function getAdminUrl( array $getParams ) : string {
		return add_query_arg(
			$getParams,
			admin_url( 'admin.php' )
		);
	}

	public static function getTab( $taxonomy ) : string {
		return self::getAdminUrl( [
			'page' => self::PAGE_WPML_WCML,
			'tab'  => $taxonomy,
		] );
	}

	public static function getMultilingualTab() : string {
		return self::getAdminUrl( [
			'page' => self::PAGE_WPML_WCML,
			'tab'  => isStandAlone() ? self::TAB_MULTILINGUAL_STANDALONE : self::TAB_MULTILINGUAL,
		] );
	}

	public static function getMultiCurrencyTab( string $anchor = '' ) : string {
		$url = self::getAdminUrl( [
			'page' => self::PAGE_WPML_WCML,
			'tab'  => self::TAB_MULTICURRENCY,
		] );

		if ( ! empty( $anchor ) ) {
			$url .= '#' . $anchor;
		}

		return $url;
	}

	public static function getStoreURLTab() : string {
		return self::getAdminUrl( [
			'page' => self::PAGE_WPML_WCML,
			'tab'  => self::TAB_STORE_URL,
		] );
	}

	public static function getStatusTab() : string {
		return self::getAdminUrl( [
			'page' => self::PAGE_WPML_WCML,
			'tab'  => self::TAB_STATUS,
		] );
	}

	public static function getSettingsTab() : string {
		return self::getAdminUrl( [
			'page' => self::PAGE_WPML_WCML,
			'tab'  => self::TAB_SETTINGS,
		] );
	}

	public static function getTroubleshootingTab() : string {
		return self::getAdminUrl( [
			'page' => self::PAGE_WPML_WCML,
			'tab'  => self::TAB_TROUBLESHOOTING,
		] );
	}

	public static function getSetupLater() : string {
		return self::getAdminUrl( [
			'page' => self::PAGE_WPML_WCML,
			'src'  => self::SRC_SETUP_LATER,
		] );
	}

	/**
	 * @param ?string $step
	 */
	public static function getSetup( $step = null ) : string {
		$args         = [];
		$args['page'] = \WCML_Setup_UI::SLUG;
		if ( ! is_null( $step ) ) {
			$args['step'] = $step;
		}

		return self::getAdminUrl( $args );
	}

	/**
	 * @param string[] $sections
	 * @param string   $stringDomain
	 *
	 * @return string
	 *
	 * @throws \Error if WPML is not active, since \WPML\UIPage depends on it.
	 */
	public static function getWPMLTMDashboard( array $sections = [], string $stringDomain = '' ) : string {
		$dashboardUrl = admin_url( \WPML\UIPage::getTMDashboard() );
		if ( empty( $sections ) ) {
			return $dashboardUrl;
		}

		$dashboardUrl = add_query_arg(
			[ self::DASHBOARD_PARAM_SECTIONS => implode( ',', $sections ) ],
			$dashboardUrl
		);

		if ( in_array( self::DASHBOARD_SECTION_STRING, $sections, true ) && $stringDomain ) {
			$dashboardUrl = add_query_arg(
				[ self::DASHBOARD_PARAM_STRING_DOMAIN => $stringDomain ],
				$dashboardUrl
			);
		}

		return $dashboardUrl;
	}

	/**
	 * @param string $domain
	 *
	 * @return string
	 *
	 * @throws \Error if WPML is not active, since \WPML\UIPage depends on it.
	 */
	public static function getWPMLTMDashboardStringDomain( string $domain ) : string {
		return self::getWPMLTMDashboard(
			[ self::DASHBOARD_SECTION_STRING ],
			$domain
		);
	}

	/**
	 * @return string
	 *
	 * @throws \Error if WPML is not active, since \WPML\UIPage depends on it.
	 */
	public static function getWPMLTMDashboardProducts() : string {
		return self::getWPMLTMDashboard( [ self::DASHBOARD_SECTION_PRODUCT ] );
	}

	/**
	 * @param ?string $taxonomy
	 *
	 * @return string
	 *
	 * @throws \Error if WPML is not active, since WPML_PLUGIN_FOLDER depends on it.
	 */
	public static function getWPMLTaxonomyTranslation( $taxonomy = null ) : string {
		$args = [
			'page'     => WPML_PLUGIN_FOLDER . '/menu/taxonomy-translation.php',
			'taxonomy' => $taxonomy,
		];

		return self::getAdminUrl( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 *
	 * @throws \Error if WPML is not active, since WPML_ST_FOLDER depends on it.
	 */
	public static function getWPMLStringTranslation( array $args = [] ) : string {
		$args = array_merge(
			[
				'page' => WPML_ST_FOLDER . '/menu/string-translation.php',
			],
			$args
		);

		return self::getAdminUrl( $args );
	}

	public static function getWooProductAll() : string {
		$args = [
			'post_type' => 'product',
		];

		return add_query_arg(
			$args,
			admin_url( 'edit.php' )
		);
	}

	/**
	 * @param ?string $tab
	 */
	public static function getWooSettings( $tab = null ) : string {
		$args = [
			'page' => self::PAGE_WOO_SETTINGS,
			'tab'  => $tab,
		];

		return self::getAdminUrl( $args );
	}

	/**
	 * @param ?string $tab
	 */
	public static function getWooStatus( $tab = null ) : string {
		$args = [
			'page' => 'wc-status',
			'tab'  => $tab,
		];

		return self::getAdminUrl( $args );
	}
}
