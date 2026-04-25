<?php

namespace WCML\MultiCurrency\Resolver;

use WCML\MultiCurrency\Geolocation;
use WCML\MultiCurrency\Settings;
use WCML\StandAlone\NullSitePress;
use WPML\FP\Fns;
use function WCML\functions\getSitePress;

class HelperByLanguage {

	/** @var null|callable $getCurrency */
	private static $getCurrency;

	/**
	 * @param string $currentLang
	 *
	 * @return string|null
	 */
	public static function getCurrencyByUserCountry( $currentLang ) {
		if ( ! self::$getCurrency ) {
			self::$getCurrency = Fns::memorize( function() use ( $currentLang ) {
				$clientCountry = Geolocation::getUserCountry();
				$currency      = Geolocation::getOfficialCurrencyCodeByCountry( $clientCountry );

				if ( ! Settings::isValidCurrencyForLang( $currency, $currentLang ) ) {
					$currency = Settings::getFirstAvailableCurrencyForLang( $currentLang );
				}

				return $currency ?: null;
			} );
		}

		return call_user_func( self::$getCurrency );
	}


	/**
	 * @return string
	 */
	public static function getCurrentLanguage() {
		/** @var string|null|false $currentLang */
		$currentLang = getSitePress()->get_current_language();

		if ( in_array( $currentLang, [ 'all', null, false ], true ) ) {
			/** @var string|null|false $currentLang */
			$currentLang = getSitePress()->get_default_language();
		}

		if ( ! is_string( $currentLang ) ) {
			/** @var string $currentLang - WPML default language not set/detected, returns the language as if WPML was not active */
			$currentLang = ( new NullSitePress() )->get_current_language();
		}

		return $currentLang;
	}
}
