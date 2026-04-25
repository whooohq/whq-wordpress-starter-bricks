<?php

class WCML_Multilingual_UI extends WCML_Templates_Factory {

	public function get_model() {
		return [
			'menu'               => [
				'translation' => [
					'title'       => __( 'Translate Products', 'woocommerce-multilingual' ),
					'description' => esc_html__( 'Go to WPML &rarr; Translation Management to translate Products', 'woocommerce-multilingual' ),
					'icon'        => 'otgs-ico-basket',
					'button'      => [
						'url'   => \WCML\Utilities\AdminUrl::getWPMLTMDashboardProducts(),
						'label' => __( 'Translate Products', 'woocommerce-multilingual' ),
					],
				],
				'taxonomy'    => [
					'title'       => __( 'Translate Taxonomy', 'woocommerce-multilingual' ),
					'description' => esc_html__( 'Go to WPML &rarr; Taxonomy Translation to translate Taxonomy terms manually', 'woocommerce-multilingual' ),
					'icon'        => 'otgs-ico-tag',
					'button'      => [
						'url'   => \WCML\Utilities\AdminUrl::getWPMLTaxonomyTranslation(),
						'label' => __( 'Translate Taxonomy', 'woocommerce-multilingual' ),
					],
				],
			],
			'translate_manually' => [
				'toggle_text'        => __( 'Prefer to translate manually?', 'woocommerce-multilingual' ),
				'products'           => sprintf(
					/* translators: %1$s and %2$s are opening and closing HTML link tags */
					__( 'To manually translate products, go to %1$sProducts%2$s and click on the "plus" icon.', 'woocommerce-multilingual' ),
					'<a href="' . \WCML\Utilities\AdminUrl::getWooProductAll() . '">',
					'</a>'
				),
				'product_categories' => sprintf(
					/* translators: %1$s and %2$s are opening and closing HTML link tags */
					__( 'To manually translate product categories, tags and attributes, go to %1$sWPML &rarr; Taxonomy Translation%2$s.', 'woocommerce-multilingual' ),
					'<a href="' . \WCML\Utilities\AdminUrl::getWPMLTaxonomyTranslation() . '">',
					'</a>'
				),
			],
		];
	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/multilingual/',
		];
	}

	public function get_template() {
		return 'multilingual.twig';
	}

}
