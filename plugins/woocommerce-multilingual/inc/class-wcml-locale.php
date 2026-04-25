<?php

class WCML_Locale {
	private $sitepress;

	/**
	 * @param SitePress        $sitepress
	 */
	public function __construct( $sitepress ) {
		$this->sitepress = $sitepress;

		add_filter( 'locale', [ $this, 'update_product_action_locale_check' ] );
	}

	/**
	 * Loading the plugin's textdomain will register it, but not load (since WP 6.6).
	 * It will be loaded "just in time" when the first string of the domain is translated.
	 *
	 * @return bool
	 */
	public static function load_locale() {
		add_filter( 'lang_dir_for_domain', [ __CLASS__, 'force_path_for_embedded_translation_files' ], 10, 3 );
		return load_plugin_textdomain( 'woocommerce-multilingual', false, WCML_PLUGIN_FOLDER . '/locale' );
	}

	/**
	 * As WCML is also published on wordpress.org, it can have translation files from there.
	 * And the translations from `wp-content/languages/plugins` are taking precedence by default.
	 * We need to force the path for the translation files we are shipping with WCML.
	 *
	 * @param string $path
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return string
	 */
	public static function force_path_for_embedded_translation_files( $path, $domain, $locale ) {
		if ( 'woocommerce-multilingual' !== $domain ) {
			return $path;
		}

		$isEmbeddedTranslationFile = function( $locale ) {
			return in_array(
				$locale,
				[
					'de_DE',
					'el',
					'es_ES',
					'fr_FR',
					'he_IL',
					'it_IT',
					'ja',
					'nl_NL',
					'pl_PL',
					'pt_BR',
					'sv_SE',
					'zn_CN',
				],
				true
			);
		};

		if ( $isEmbeddedTranslationFile( $locale ) ) {
			return trailingslashit( WCML_LOCALE_PATH );
		}

		return $path;
	}

	/**
	 * @deprcated since 5.3.6.
	 *
	 * @param string|false $lang_code
	 *
	 * @return bool|void
	 */
	public function switch_locale( $lang_code = false ) {
		global $l10n, $st_gettext_hooks;
		static $original_l10n;

		if ( ! empty( $lang_code ) ) {
			if ( null !== $st_gettext_hooks ) {
				$st_gettext_hooks->switch_language_hook( $lang_code );
			}

			$original_l10n = $l10n['woocommerce-multilingual'] ?? null;
			if ( null !== $original_l10n ) {
				unset( $l10n['woocommerce-multilingual'] );
			}

			return load_textdomain(
				'woocommerce-multilingual',
				WCML_LOCALE_PATH . '/woocommerce-multilingual-' . $this->sitepress->get_locale( $lang_code ) . '.mo'
			);

		} else { // Switch back.
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$l10n['woocommerce-multilingual'] = $original_l10n;
		}
	}

	/**
	 * Change locale to saving language - needs for sanitize_title exception wcml-390
	 *
	 * @param string $locale
	 *
	 * @return false|string
	 */
	public function update_product_action_locale_check( $locale ) {
		if ( isset( $_POST['action'] ) && 'wpml_translation_dialog_save_job' === $_POST['action'] ) {
			return $this->sitepress->get_locale( $_POST['job_details']['target'] );
		}
		return $locale;
	}
}
