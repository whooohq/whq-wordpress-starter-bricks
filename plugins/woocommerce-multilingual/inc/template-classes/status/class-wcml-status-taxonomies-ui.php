<?php

class WCML_Status_Taxonomies_UI extends WCML_Templates_Factory {

	private $woocommerce_wpml;
	private $sitepress;

	public function __construct( $sitepress, $woocommerce_wpml ) {
		parent::__construct();

		$this->sitepress        = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function get_model() {

		$model = [
			'taxonomies' => $this->get_taxonomies_data(),
			'strings'    => [
				'tax_missing'       => __( 'Taxonomies Missing Translations', 'woocommerce-multilingual' ),
				'run_site'          => __( 'To run a fully translated site, you should translate all taxonomy terms. Some store elements, such as variations, depend on taxonomy translation.', 'woocommerce-multilingual' ),
				/* translators: %s is a taxonomy name */
				'not_req_trnsl'     => __( '%s do not require translation.', 'woocommerce-multilingual' ),
				'req_trnsl'         => __( 'This taxonomy requires translation.', 'woocommerce-multilingual' ),
				/* translators: %1$d is a number of taxonomy and %2$d is a taxonomy name */
				'miss_trnsl_one'    => __( '%1$d %2$s is missing translations.', 'woocommerce-multilingual' ),
				/* translators: %1$d is a number of taxonomy and %2$d is a taxonomy name */
				'miss_trnsl_more'   => __( '%1$d %2$s are missing translations.', 'woocommerce-multilingual' ),
				/* translators: %s is a taxonomy name */
				'trnsl'             => __( 'Translate %s', 'woocommerce-multilingual' ),
				'doesnot_req_trnsl' => __( 'This taxonomy does not require translation.', 'woocommerce-multilingual' ),
				/* translators: %s is a taxonomy name */
				'all_trnsl'         => __( 'All %s are translated.', 'woocommerce-multilingual' ),
				'not_to_trnsl'      => __( 'Right now, there are no taxonomy terms needing translation.', 'woocommerce-multilingual' ),
				/* translators: %1$s and %2$s are opening and closing HTML link tags */
				'conf_warning'      => sprintf( __( 'To configure product taxonomies or attributes as translatable or not translatable, go to the %1$sMultilingual Content Setup%2$s', 'woocommerce-multilingual' ), '<a href="' . admin_url( 'admin.php?page=wpml-translation-management%2Fmenu%2Fmain.php&sm=mcsetup#ml-content-setup-sec-8' ) . '">', '</a>' ),
			],
			'nonces'     => [
				'ignore_tax' => wp_create_nonce( 'wcml_ingore_taxonomy_translation_nonce' ),
			],
		];

		return $model;

	}

	private function get_taxonomies_data() {
		$taxonomies      = $this->woocommerce_wpml->terms->get_wc_taxonomies();
		$taxonomies_data = [];

		foreach ( $taxonomies as $key => $taxonomy ) {
			if (
				'translation_priority' === $taxonomy ||
				! is_taxonomy_translated( $taxonomy ) ||
				$this->sitepress->is_display_as_translated_taxonomy( $taxonomy )
			) {
				continue;
			}
			$taxonomies_data[ $key ]['tax']           = $taxonomy;
			$taxonomies_data[ $key ]['untranslated']  = $this->woocommerce_wpml->terms->get_untranslated_terms_number( $taxonomy );
			$taxonomies_data[ $key ]['fully_trans']   = $this->woocommerce_wpml->terms->is_fully_translated( $taxonomy );
			$taxonomy_object                          = get_taxonomy( $taxonomy );
			$taxonomies_data[ $key ]['name']          = ucfirst( ! empty( $taxonomy_object->labels->name ) ? $taxonomy_object->labels->name : $taxonomy_object->labels->singular_name );
			$taxonomies_data[ $key ]['name_singular'] = ucfirst( $taxonomy_object->labels->singular_name );

			if ( substr( $taxonomy, 0, 3 ) == 'pa_' ) {
				$taxonomies_data[ $key ]['url'] = admin_url( 'admin.php?page=wpml-wcml&tab=product-attributes&taxonomy=' . $taxonomy );
			} else {
				$taxonomies_data[ $key ]['url'] = admin_url( 'admin.php?page=wpml-wcml&tab=' . $taxonomy );
			}
		}

		return $taxonomies_data;
	}


	public function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/status/',
		];
	}

	public function get_template() {
		return 'taxonomies.twig';
	}

}
