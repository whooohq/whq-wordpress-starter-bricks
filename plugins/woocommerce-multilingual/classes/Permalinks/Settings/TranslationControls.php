<?php

namespace WCML\Permalinks\Settings;

use WCML\Permalinks\Strings;
use WCML\TranslationControls\Hooks as TranslationControlsBase;
use WCML\Utilities\AdminUrl;
use WCML\Utilities\WpAdminPages;

class TranslationControls extends TranslationControlsBase {

	const PERMALINK_BASES = [
		'tag_base'       => 'product_tag',
		'category_base'  => 'product_cat',
		'attribute_base' => 'attribute',
		'product_base'   => 'product',
	];

	const INSTRUCTIONS_FOR_PRODUCTS           = 'products';
	const INSTRUCTIONS_FOR_PRODUCT_TAXONOMIES = 'product-taxonomies';

	protected function addAdminPageHooks() {
		add_action( 'admin_footer', [ $this, 'translationInstructions' ] );
		add_action( 'admin_footer', [ $this, 'translationControls' ] );
		$this->registerStringsOnSave();
	}

	protected function isAdminPage() {
		return WpAdminPages::isPermalinksSettings();
	}

	/**
	 * @param string $domain
	 * @param string $search
	 *
	 * @return string
	 */
	protected function getInstructionsLink( $domain, $search = '' ) {
		return AdminUrl::getStoreURLTab();
	}

	/**
	 * @param string $variation
	 *
	 * @return string
	 */
	private function getInstructionsVariation( $variation ) {
		if ( ! $this->hasStringsInDomain( Strings::TRANSLATION_DOMAIN ) ) {
			return $this->getInstructionsWithoutRegisteredStrings( Strings::TRANSLATION_DOMAIN );
		}
		switch ( $variation ) {
			case self::INSTRUCTIONS_FOR_PRODUCT_TAXONOMIES:
				return sprintf(
					/* translators: %1$s and %2$s are opening and closing HTML link tags */
					esc_html__( 'To translate permalinks for product taxonomies, go to %1$sWPML Multilingual & Multicurrency for WooCommerce → Store URLs%2$s.', 'woocommerce-multilingual' ),
					'<a href="' . esc_url( $this->getInstructionsLink( Strings::TRANSLATION_DOMAIN ) ) . '">',
					'</a>'
				);
			case self::INSTRUCTIONS_FOR_PRODUCTS:
			default:
				return sprintf(
					/* translators: %1$s and %2$s are opening and closing HTML link tags */
					esc_html__( 'To translate product permalinks, go to %1$sWPML Multilingual & Multicurrency for WooCommerce → Store URLs%2$s.', 'woocommerce-multilingual' ),
					'<a href="' . esc_url( $this->getInstructionsLink( Strings::TRANSLATION_DOMAIN ) ) . '">',
					'</a>'
				);
		}
	}

	public function translationInstructions() {
		$this->pointerFactory
			->create( [
				'anchor'     => esc_html__( 'How to translate permalinks for product taxonomies', 'woocommerce-multilingual' ),
				'content'    => $this->getInstructionsVariation( self::INSTRUCTIONS_FOR_PRODUCT_TAXONOMIES ),
				'selectorId' => 'wpbody-content h2:nth-of-type(2) + p',
			] )->show();

			$this->pointerFactory
			->create( [
				'anchor'     => esc_html__( 'How to translate product permalinks', 'woocommerce-multilingual' ),
				'content'    => $this->getInstructionsVariation( self::INSTRUCTIONS_FOR_PRODUCTS ),
				'selectorId' => 'wpbody-content table.form-table.wc-permalink-structure',
				'method'     => 'before',
			] )->show();
	}

	/**
	 * @return array
	 */
	protected function getTranslationControls() {
		$translationControls = [];
		$permalink_options   = get_option( 'woocommerce_permalinks' );

		foreach ( self::PERMALINK_BASES as $baseKey => $base ) {

			switch ( $base ) {
				case 'product_tag':
					$value = ! empty( $permalink_options['tag_base'] )
						? $permalink_options['tag_base']
						: Strings::DEFAULT_PRODUCT_TAG_BASE;
					break;
				case 'product_cat':
					$value = ! empty( $permalink_options['category_base'] )
						? $permalink_options['category_base']
						: Strings::DEFAULT_PRODUCT_CATEGORY_BASE;
					break;
				case 'attribute':
					$value = ! empty( $permalink_options['attribute_base'] )
						? $permalink_options['attribute_base']
						: Strings::DEFAULT_PRODUCT_ATTRIBUTE_BASE;
					break;
				case 'product':
					$value = ! empty( $permalink_options['product_base'] )
						? trim( $permalink_options['product_base'], '/' )
						/* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText */
						: _x( Strings::DEFAULT_PRODUCT_BASE, 'default-slug', 'woocommerce' );
					break;
				default:
					$value = '';
			}

			$translationControls[] = $this->getTranslationControl(
				'',
				$baseKey,
				trim( $value, '/' ),
				Strings::TRANSLATION_DOMAIN,
				$this->getStringName( '', $base )
			);
		}

		return $translationControls;
	}

	public function registerStringsOnSave() {
		add_filter( 'pre_update_option_woocommerce_permalinks', [ $this, 'registerStringsOnPreUpdate' ] );
	}

	/**
	 * Delegated into WCML_Url_Translation::register_product_and_taxonomy_bases().
	 *
	 * @param array $wcPermalinks
	 *
	 * @return array
	 */
	public function registerStringsOnPreUpdate( $wcPermalinks ) {
		return $this->wcmlStrings->getUrlTranslation()->register_product_and_taxonomy_bases( $wcPermalinks );
	}

	/**
	 * @param string $dummyContext
	 * @param string $base
	 *
	 * @return string
	 */
	protected function getStringName( $dummyContext, $base ) {
		return Strings::getStringName( $base );
	}

	/**
	 * Gets the id attribute value of the input node holding a translatable string.
	 *
	 * @param string $dummyContext
	 * @param string $baseKey
	 *
	 * @return string
	 */
	protected function getInputName( $dummyContext, $baseKey ) {
		switch ( $baseKey ) {
			case 'tag_base':
				return 'woocommerce_product_tag_slug';
			case 'category_base':
				return 'woocommerce_product_category_slug';
			case 'attribute_base':
				return 'woocommerce_product_attribute_slug';
			case 'product_base':
				return 'product_permalink_structure';
		}

		return '';
	}

	/**
	 * Gets the id attribute value of the input node holding a translatable string.
	 *
	 * @param string $dummyContext
	 * @param string $baseKey
	 *
	 * @return string
	 */
	protected function getInputId( $dummyContext, $baseKey ) {
		return '';
	}

	/**
	 * @param string $dummyContext
	 * @param string $baseKey
	 *
	 * @return string
	 */
	protected function getLanguageSelectorId( $dummyContext, $baseKey ) {
		return $baseKey . '_' . self::LANGUAGE_SELECTOR_ID_SUFFIX;
	}



	/**
	 * @param string $dummy
	 * @param string $baseKey
	 *
	 * @return string
	 */
	protected function getLanguageSelectorName( $dummy, $baseKey ) {
		return $baseKey . '_language';
	}

}
