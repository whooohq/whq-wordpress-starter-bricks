<?php

class WCML_Status_Products_UI extends WCML_Templates_Factory {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var SitePress */
	private $sitepress;

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress        $sitepress
	 */
	public function __construct( $woocommerce_wpml, $sitepress ) {
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
	}

	/**
	 * @return array
	 *
	 * @throws \Error if WPML is not active, since \WPML\UIPage depends on it.
	 */
	public function get_model() {
		$model = [
			'products'   => $this->get_untranslated_products(),
			'trnsl_link' => admin_url( \WPML\UIPage::getTMDashboard() ),
			'strings'    => [
				'products_missing' => __( 'Products Missing Translations', 'woocommerce-multilingual' ),
				/* translators: %1$s is a number of products and %2$s is a product name */
				'miss_trnsl_one'   => __( '%1$d %2$s translation missing.', 'woocommerce-multilingual' ),
				/* translators: %1$s is a number of products and %2$s is a product name */
				'miss_trnsl_more'  => __( '%1$d %2$s translations missing.', 'woocommerce-multilingual' ),
				'transl'           => __( 'Translate Products', 'woocommerce-multilingual' ),
				'not_to_trnsl'     => __( 'Right now, there are no products needing translation.', 'woocommerce-multilingual' ),
			],
		];

		return $model;

	}

	/** @return array */
	private function get_untranslated_products() {
		$products = [];

		foreach ( $this->sitepress->get_active_languages() as $language ) {

			$products_count = $this->woocommerce_wpml->products->get_untranslated_products_count( $language['code'] );
			if ( $products_count ) {
				$products[ $language['code'] ]['count']        = $this->woocommerce_wpml->products->get_untranslated_products_count( $language['code'] );
				$products[ $language['code'] ]['flag']         = $this->sitepress->get_flag_img( $language['code'] );
				$products[ $language['code'] ]['display_name'] = $language['display_name'];
			}
		}

		return $products;
	}

	public function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/status/',
		];
	}

	public function get_template() {
		return 'products.twig';
	}

}
