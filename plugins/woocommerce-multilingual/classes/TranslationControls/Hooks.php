<?php

namespace WCML\TranslationControls;

use IWPML_Backend_Action;
use IWPML_DIC_Action;
use IWPML_REST_Action;
use SitePress;
use WCML\PointerUi\Factory;
use WCML\StandAlone\NullSitePress;
use WCML\Utilities\AdminUrl;
use WCML_WC_Strings;
use wpdb;
use WPML\Core\ISitePress;
use WPML\FP\Obj;
use WPML_Simple_Language_Selector;

abstract class Hooks implements IWPML_Backend_Action, IWPML_DIC_Action, IWPML_REST_Action {

	const KEY_PREFIX                  = 'wcml_lang';
	const LANGUAGE_SELECTOR_ID_SUFFIX = 'language_selector';

	/** @var SitePress|NullSitePress */
	protected $sitepress;

	/** @var WCML_WC_Strings */
	protected $wcmlStrings;

	/** @var Factory */
	protected $pointerFactory;

	/** @var wpdb $wpdb */
	protected $wpdb;

	/**
	 * @param SitePress|NullSitePress $sitepress
	 * @param WCML_WC_Strings         $wcmlStrings
	 * @param Factory                 $pointerFactory
	 * @param wpdb                    $wpdb
	 */
	public function __construct(
		ISitePress $sitepress,
		WCML_WC_Strings $wcmlStrings,
		Factory $pointerFactory,
		wpdb $wpdb
	) {
		$this->sitepress      = $sitepress;
		$this->wcmlStrings    = $wcmlStrings;
		$this->pointerFactory = $pointerFactory;
		$this->wpdb           = $wpdb;
	}

	public function add_hooks() {
		if ( $this->isAdminPage() ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'loadAssets' ] );
			$this->addAdminPageHooks();
		}
	}

	/**
	 * @return bool
	 */
	abstract protected function isAdminPage();

	abstract protected function addAdminPageHooks();

	public function loadAssets() {
		wp_register_style( 'wcml_tc', WCML_PLUGIN_URL . '/res/css/translation-controls.css', [], WCML_VERSION );
		wp_enqueue_style( 'wcml_tc' );
	}

	/**
	 * @param string $domain
	 * @param string $namePrefix
	 *
	 * @return bool
	 */
	protected function hasStringsInDomain( $domain, $namePrefix = '' ) {
		$args = [ $domain ];
		$and  = 'AND s.context = %s';
		if ( $namePrefix ) {
			$args[] = $namePrefix . '%';
			$and   .= ' AND s.name LIKE %s';
		}

		// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		// phpcs:disable WordPress.VIP.DirectDatabaseQuery.NoCaching
		$results = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT context
			FROM {$this->wpdb->prefix}icl_strings s
			WHERE TRIM(s.value) <> ''
			{$and}
			LIMIT 1",
			$args
		) );
		// phpcs:enable

		return ! empty( $results );
	}

	abstract public function translationInstructions();

	/**
	 * @param string $domain
	 * @param string $search
	 *
	 * @return string
	 */
	protected function getInstructionsLink( $domain, $search = '' ) {
		return AdminUrl::getWPMLTMDashboardStringDomain( $domain );
	}

	/**
	 * @param string $domain
	 * @param string $search
	 *
	 * @return string
	 */
	protected function getInstructionsWithRegisteredStrings( $domain, $search = '' ) {
		return sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
			esc_html__( 'To translate this content, go to the %1$sTranslation Dashboard%2$s.', 'woocommerce-multilingual' ),
			'<a href="' . esc_url( $this->getInstructionsLink( $domain, $search ) ) . '">',
			'</a>'
		);
	}

	/**
	 * @param string $domain
	 * @param string $search
	 *
	 * @return string
	 */
	protected function getInstructionsWithoutRegisteredStrings( $domain, $search = '' ) {
		return esc_html__( 'To see how to translate this text, save your changes here first.', 'woocommerce-multilingual' );
	}

	/**
	 * @param string $domain
	 * @param string $search
	 *
	 * @return string
	 */
	protected function getInstructions( $domain, $search = '' ) {
		if ( $this->hasStringsInDomain( $domain, $search ) ) {
			return $this->getInstructionsWithRegisteredStrings( $domain, $search );
		}
		return $this->getInstructionsWithoutRegisteredStrings( $domain, $search );
	}

	/**
	 * @param string $contextKey
	 * @param string $itemKey
	 * @param string $value
	 * @param string $domain
	 * @param string $stringName
	 *
	 * @return array
	 */
	protected function getTranslationControl( $contextKey, $itemKey, $value, $domain, $stringName ) {
		return [
			'inputId'   => $this->getInputId( $contextKey, $itemKey ),
			'inputName' => $this->getInputName( $contextKey, $itemKey ),
			'id'        => $this->getLanguageSelectorId( $contextKey, $itemKey ),
			'name'      => $this->getLanguageSelectorName( $contextKey, $itemKey ),
			'language'  => $this->wcmlStrings->get_string_language(
				$value,
				$domain,
				$stringName
			),
		];
	}

	/**
	 * @return array
	 */
	abstract protected function getTranslationControls();

	public function translationControls() {
		$controls = $this->getTranslationControls();
		if ( empty( $controls ) ) {
			return;
		}

		$languageSelector = new WPML_Simple_Language_Selector( $this->sitepress );
		foreach ( $controls as $control ) {
			$languageSelector->render(
				[
					'id'                 => $control['id'],
					'name'               => $control['name'],
					'selected'           => $control['language'] ?? $this->sitepress->get_default_language(),
					'show_please_select' => false,
					'echo'               => true,
				]
			);
		}

		$getInputSelector = function( $controlItem ) {
			$controlItemId = Obj::prop( 'inputId', $controlItem );
			if ( ! empty( $controlItemId ) ) {
				return '#' . $controlItemId;
			}
			$controlItemName = Obj::prop( 'inputName', $controlItem );
			if ( ! empty( $controlItemName ) ) {
				return "input[name='" . $controlItemName . "']";
			}
			return '';
		};
		?>
		<script>
			if ( typeof window.wcmlSetTranslationControls === 'undefined' ) {
				window.wcmlSetTranslationControls = function( $inputSelector, $languageSelector ) {
					var input = jQuery( $inputSelector );
					if ( input.length ) {
						var container = input.parent();
						container.append( '<div class="translation_controls"></div>' );
						jQuery( $languageSelector ).prependTo( container.find( '.translation_controls' ) );
					} else {
						jQuery( $languageSelector ).remove();
					}
				}
			}
			<?php
			foreach ( $controls as $control ) {
				$inputSelector = $getInputSelector( $control );
				if ( empty( $inputSelector ) ) {
					continue;
				}
				?>
				wcmlSetTranslationControls( '<?php echo esc_js( $inputSelector ); ?>', '#<?php echo esc_html( $control['id'] ); ?>' );
				<?php
			}
			?>
		</script>
		<?php
	}

	abstract public function registerStringsOnSave();

	/**
	 * We can not use WCML_WC_Strings::get_string_language() because it generates a language cache by context and name,
	 * and we are using this for gathering the language of a string before eventually changing it.
	 *
	 * @param string $context
	 * @param string $name
	 *
	 * @return string|null
	 */
	protected function getStringLanguage( $context, $name ) {
		// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		// phpcs:disable WordPress.VIP.DirectDatabaseQuery.NoCaching
		return $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT language
			FROM {$this->wpdb->prefix}icl_strings
			WHERE context = %s AND name = %s
			LIMIT 1",
			[ $context, $name ]
		) );
		// phpcs:enable
	}

	/**
	 * @param string      $value
	 * @param string      $context
	 * @param string      $name
	 * @param string|null $language
	 */
	protected function replaceStringAndLanguage( $value, $context, $name, $language = null ) {
		$previousStringLanguage = $this->getStringLanguage( $context, $name ) ?? $this->sitepress->get_default_language();
		// First, register or update the POSTed value for translation. Keep language from the original value, if there is one.
		// Respecting the language from the original value, if there is one, is crucial here:
		// it will avoid some side effects when combining same or different values for the POSTed language, the admin language, and that language from the original string.
		// See WPML_Register_String_Filter::register_string().
		do_action( 'wpml_register_single_string', $context, $name, $value, false, $previousStringLanguage );
		// Set the POSTed value language.
		if ( $language ) {
			$this->wcmlStrings->set_string_language( $value, $context, $name, $language );
		}

		$this->flushWpmlStringTranslationCache( $context, $name );
	}

	/**
	 * Flush specific WPML string translation cache key
	 * Flush 'WPML_Register_String_Filter::' . $domain cache group
	 * Flush domain cache key from 'WPML_Register_String_Filter' group
	 *
	 * @param string $domain The string domain (context).
	 * @param string $name   The string name.
	 */
	protected function flushWpmlStringTranslationCache( $domain, $name ) {
		$key = md5( $domain . '_' . $name );

		wp_cache_delete( $key, 'wpml-string-translation' );

		if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'WPML_Register_String_Filter::' . $domain );
		}

		wp_cache_delete( $domain, 'WPML_Register_String_Filter' );

		wp_cache_delete( \WCML_Endpoints::STRING_CONTEXT, \WCML_Endpoints::class );
		if ( class_exists( \WPML_Endpoints_Support::class ) ) {
			wp_cache_delete( \WPML_Endpoints_Support::STRING_CONTEXT, \WPML_Endpoints_Support::class );
		}
	}

	/**
	 * @param string $contextKey
	 * @param string $itemKey
	 *
	 * @return string
	 */
	abstract protected function getStringName( $contextKey, $itemKey );

	/**
	 * Gets the name attribute value of the input node holding a translatable string.
	 *
	 * @param string $contextKey
	 * @param string $itemKey
	 *
	 * @return string
	 */
	protected function getInputName( $contextKey, $itemKey ) {
		return $this->getInputId( $contextKey, $itemKey );
	}

	/**
	 * Gets the id attribute value of the input node holding a translatable string.
	 *
	 * @param string $contextKey
	 * @param string $itemKey
	 *
	 * @return string
	 */
	abstract protected function getInputId( $contextKey, $itemKey );

	/**
	 * @param string $contextKey
	 * @param string $itemKey
	 *
	 * @return string
	 */
	abstract protected function getLanguageSelectorId( $contextKey, $itemKey );

	/**
	 * @param string $contextKey
	 * @param string $itemKey
	 *
	 * @return string
	 */
	abstract protected function getLanguageSelectorName( $contextKey, $itemKey );

}
