<?php

use WCML\Utilities\WCTaxonomies;

class WCML_Taxonomy_Translation_Link_Filters {

	/**
	 * @var WCML_Attributes
	 */
	private $wcml_attributes;

	public function __construct( WCML_Attributes $wcml_attributes ) {
		$this->wcml_attributes = $wcml_attributes;
	}

	public function add_filters() {
		add_filter( 'wpml_notice_text', [ $this, 'override_translation_notice_text' ], 10, 2 );
		add_filter( 'wpml_taxonomy_slug_translation_ui', [ $this, 'slug_translation_ui_class' ], 10, 2 );
	}

	/**
	 * @param string $text
	 * @param array  $notice
	 *
	 * @return string
	 */
	public function override_translation_notice_text( $text, $notice ) {
		if ( 'taxonomy-term-help-notices' === $notice['group'] ) {
			$taxonomy            = get_taxonomy( $notice['id'] );
			$built_in_taxonomies = [ WCTaxonomies::TAXONOMY_PRODUCT_CATEGORY, WCTaxonomies::TAXONOMY_PRODUCT_TAG, WCML_Terms::PRODUCT_SHIPPING_CLASS ];
			if ( false !== $taxonomy && in_array( $notice['id'], $built_in_taxonomies ) ) {

				$link = sprintf(
					'<a href="%s">%s</a>',
					$this->get_screen_url( $taxonomy->name ),
					/* translators: %s is a taxonomy singular label */
					sprintf( esc_html__( '%s translation', 'woocommerce-multilingual' ), $taxonomy->labels->singular_name )
				);

				$text = sprintf(
					/* translators: %1$s is a taxonomy singular label and %2$s is an HTML link */
					esc_html__( 'Translating %1$s? Use the %2$s table for easier translation.', 'woocommerce-multilingual' ),
					$taxonomy->labels->name,
					$link
				);
			}
		}

		return $text;
	}

	/**
	 * @param string $taxonomy
	 *
	 * @return string|false
	 */
	public function get_screen_url( $taxonomy = '' ) {
		$built_in_taxonomies = [ WCTaxonomies::TAXONOMY_PRODUCT_CATEGORY, WCTaxonomies::TAXONOMY_PRODUCT_TAG, WCML_Terms::PRODUCT_SHIPPING_CLASS ];
		if ( in_array( $taxonomy, $built_in_taxonomies, true ) ) {
			return \WCML\Utilities\AdminUrl::getWPMLTaxonomyTranslation( $taxonomy );
		}

		$translatable_attributes = $this->get_translatable_attributes();
		if ( in_array( $taxonomy, $translatable_attributes, true ) ) {
			return \WCML\Utilities\AdminUrl::getWPMLTaxonomyTranslation( $taxonomy );
		}

		$custom_taxonomies       = get_object_taxonomies( 'product', 'objects' );
		$translatable_taxonomies = [];
		foreach ( $custom_taxonomies as $product_taxonomy_name => $product_taxonomy_object ) {
			if ( is_taxonomy_translated( $product_taxonomy_name ) ) {
				$translatable_taxonomies[] = $product_taxonomy_name;
			}
		}

		if ( in_array( $taxonomy, $translatable_taxonomies, true ) ) {
			return \WCML\Utilities\AdminUrl::getWPMLTaxonomyTranslation( $taxonomy );
		}

		return false;
	}

	private function get_translatable_attributes() {

		$translatable_attributes = [];
		foreach ( $this->wcml_attributes->get_translatable_attributes() as $attribute ) {
			$translatable_attributes[] = WCTaxonomies::TAXONOMY_PREFIX_ATTRIBUTE . $attribute->attribute_name;
		}

		return $translatable_attributes;
	}

	public function slug_translation_ui_class( $ui_class, $taxonomy ) {

		if ( in_array( $taxonomy, $this->get_translatable_attributes() ) ) {

			$ui_class = new WCML_St_Taxonomy_UI( get_taxonomy( $taxonomy ) );
		}

		return $ui_class;
	}
}
