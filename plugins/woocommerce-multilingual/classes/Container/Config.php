<?php

namespace WCML\Container;

class Config {

	public static function getSharedInstances() {
		global $woocommerce_wpml;

		return [
			$woocommerce_wpml,
		];
	}

	public static function getSharedClasses() {
		return [
			\WCML_Currencies_Payment_Gateways::class,
			\WCML_Dependencies::class,
			\WCML_Exchange_Rates::class,
			\WCML_Multi_Currency::class,
		];
	}

	/**
	 * When WPML and WCML are active, but WC is not:
	 * - WCML needs very basic classes to function properly (e.g., checking requirements to display a message about the lack, or too old version of WC)
	 */
	public static function getSharedClassesWhenWooCommerceIsInactive(): array {
		return [
			\WCML_Dependencies::class,
		];
	}

	/**
	 * Replaces global $sitepress with null object.
	 *
	 * @return array
	 */
	public static function getAliases() {
		return [
			\WPML\Core\ISitePress::class => \WCML\functions\isStandAlone()
						? \WCML\StandAlone\NullSitePress::class
						: \SitePress::class,
		];
	}

	/**
	 * @return array
	 */
	public static function getDelegated() {
		return [
			\WCML_Exchange_Rates::class => [ \WCML_Exchange_Rates::class, 'create' ],
		];
	}
}
