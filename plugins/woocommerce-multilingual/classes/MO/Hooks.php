<?php

namespace WCML\MO;

use WPML\LIB\WP\WordPress;
use function WPML\Container\make;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	const WC_DOMAIN = 'woocommerce';

	private $isLoading = false;

	public function add_hooks() {
		add_action( 'wpml_language_has_switched', [ $this, 'forceRemoveUnloadedDomain' ], 0 );

		// The WP RC version is `6.5-RC3` which is an improper format for comparison (we'd need `6.5.0-RC3`).
		if ( WordPress::versionCompare( '>', '6.4.999' ) ) {
			add_filter( 'override_unload_textdomain', [ $this, 'forceUnloadWCTextdomainWithReloadableArg' ], 10, 3 );
			add_filter( 'pre_load_textdomain', [ $this, 'preLoadTextDomainFilter' ], 10, 4 );
		}
	}

	public function forceRemoveUnloadedDomain() {
		if ( isset( $GLOBALS['l10n_unloaded'][ self::WC_DOMAIN ] ) ) {
			unset( $GLOBALS['l10n_unloaded'][ self::WC_DOMAIN ] );
		}
	}

	/**
	 * @param bool   $override
	 * @param string $domain
	 * @param bool   $reloadable
	 *
	 * @return bool
	 */
	public function forceUnloadWCTextdomainWithReloadableArg( $override, $domain, $reloadable ) {
		if ( self::WC_DOMAIN === $domain && ! $reloadable ) {
			unload_textdomain( self::WC_DOMAIN, true );
			\WP_Translation_Controller::get_instance()->unload_textdomain( self::WC_DOMAIN );
			return true;
		}

		return $override;
	}

	/**
	 * @param bool|null   $loaded
	 * @param string      $domain
	 * @param string      $mofile
	 * @param string|null $locale
	 *
	 * @return bool|null
	 */
	public function preLoadTextDomainFilter( $loaded, $domain, $mofile, $locale ) {
		if ( self::WC_DOMAIN === $domain && ! $this->isLoading ) {
			$this->forceRemoveUnloadedDomain();

			if ( ! $locale ) {
				$this->maybeFixDiscrepancyBetweenFileLocaleAndControllerLocale( $loaded, $domain, $mofile );
			}

		}

		return $loaded;
	}

	/**
	 * This will prevent from having translation files loaded in the `\WP_Translations_Controller`
	 * with the wrong locale (e.g. `de_DE` translations loaded under `en_US`).
	 *
	 * Passing the `$locale` parameter to `load_textdomain` will also force to set
	 * the correct locale in `\WP_Translations_Controller` (matching with the file).
	 *
	 * @param bool|null $loaded
	 * @param string    $domain
	 * @param string    $mofile
	 *
	 * @return bool|null
	 */
	private function maybeFixDiscrepancyBetweenFileLocaleAndControllerLocale( $loaded, $domain, $mofile ) {
		$fileLocale     = make( \WPML_ST_Translations_File_Locale::class );
		$localeFromFile = $fileLocale ? $fileLocale->get( $mofile, $domain ) : null;

		if ( $localeFromFile && $localeFromFile !== \WP_Translation_Controller::get_instance()->get_locale() ) {
			$this->isLoading = true;
			$loaded = load_textdomain( $domain, $mofile, $localeFromFile );
			$this->isLoading = false;
		}

		return $loaded;
	}
}
