<?php

use WCML\Reviews\Translations\FrontEndHooks;
use WCML\Utilities\AdminUrl;

class WCML_Troubleshooting_UI extends WCML_Templates_Factory {

	/** @var woocommerce_wpml $woocommerce_wpml */
	private $woocommerce_wpml;

	/**
	 * WCML_Troubleshooting_UI constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( $woocommerce_wpml ) {
		// @todo Cover by tests, required for wcml-3037.
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
	}


	public function get_model() {

		$translatedProductTypeTerms      = WCML_Install::translated_product_type_terms();
		$wcmlSettings                    = print_r( get_option( '_wcml_settings', [] ), true );
		$wpmlElementSyncSettingsFaxctory = new WPML_Element_Sync_Settings_Factory();

		$model = [
			'header' => [
				'title' => __( 'Troubleshooting', 'woocommerce-multilingual' ),
				'warning' => __( 'Please make a backup of your database before using these tools', 'woocommerce-multilingual' ),
			],
			'status' => [
				'title' => __( 'Status', 'woocommerce-multilingual' ),
				'items' => [
					/* translators: The placeholder is the number of products. */
					'total'      => sprintf( __( 'There are <strong>%s product(s)</strong> in total.', 'woocommerce-multilingual' ), $this->woocommerce_wpml->troubleshooting->countProducts() ),
					/* translators: The placeholder is the number of products. */
					'variable'   => sprintf( __( 'There are <strong>%s product(s)</strong> with variations.', 'woocommerce-multilingual' ), $this->woocommerce_wpml->troubleshooting->countVariableProducts() ),
					/* translators: The placeholder is the number of products. */
					'variations' => sprintf( __( 'There are <strong>%s variation(s)</strong> in total.', 'woocommerce-multilingual' ), $this->woocommerce_wpml->troubleshooting->countVariations() ),
				],
			],
			'synchronizeData' => [
				'title'         => __( 'Synchronize data into translations', 'woocommerce-multilingual' ),
				'items'         => [
					'variations'         => __( 'Synchronize product variations', 'woocommerce-multilingual' ),
					'gallery'            => __( 'Synchronize products image galleries', 'woocommerce-multilingual' ),
					'categoriesMetadata' => __( 'Synchronize metadata for product categories (display type, thumbnail)', 'woocommerce-multilingual' ),
					'stock'              => __( 'Synchronize stock for products and product variations', 'woocommerce-multilingual' ),
				],
				'galleryEnable' => $wpmlElementSyncSettingsFaxctory->create( 'post' )->is_sync( 'attachment' ),
			],
			'connect' => [
				'title' => __( 'Fix multilingual information', 'woocommerce-multilingual' ),
				'items' => [
					'variations' => __( 'Fix incorrect or missing translation links for product variations', 'woocommerce-multilingual' ),
				],
			],
			'taxonomies' => [
				'title'             => __( 'Fix product taxonomies', 'woocommerce-multilingual' ),
				'items'             => [
					'productType' => __( 'Set product types as not translatable and delete unwanted translations', 'woocommerce-multilingual' ),
					'attributes'  => __( 'Create missing translations for product attributes:', 'woocommerce-multilingual' ),
				],
				'productTypeEnable' => ! empty( $translatedProductTypeTerms ),
				'link'              => sprintf(
				/* translators: %1$s and %2$s are opening and closing HTML link tags */
					esc_html__( 'To translate product taxonomies, go to the %1$sTaxonomy Translation%2$s page.', 'woocommerce-multilingual' ),
					'<a href="' . esc_url( AdminUrl::getWPMLTaxonomyTranslation() ) . '">',
					'</a>'
				),
				'data' => [
					'attributes' => $this->get_all_products_taxonomies(),
					'none'       => __( 'none', 'woocommerce-multilingual' ),
				]
			],
			'reviews' => [
				'title'         => __( 'Fix missing product reviews', 'woocommerce-multilingual' ),
				'items'         => [
					'register' => __( 'Allow to translate missing product reviews', 'woocommerce-multilingual' ),
				],
				'reviewsEnable' => apply_filters( FrontEndHooks::HOOK_ENABLE, true ),
				'link'          => sprintf(
				  /* translators: %1$s and %2$s are opening and closing HTML link tags */
					esc_html__( 'To translate product reviews, go to the %1$sTranslation Dashboard%2$s.', 'woocommerce-multilingual' ),
					'<a href="' . esc_url( AdminUrl::getWPMLTMDashboardStringDomain( FrontEndHooks::CONTEXT ) ) . '">',
					'</a>'
				),
			],
			'cleanup' => [
				'title' => __( 'Cleanup tools', 'woocommerce-multilingual' ),
				'items' => [
					'orphanedMeta' => __( 'Remove unused custom fields from product and variation translations', 'woocommerce-multilingual' ),
				],
			],
			'run' => [
				'start'   => __( 'Run the selected tools', 'woocommerce-multilingual' ),
				'running' => __( 'Processing', 'woocommerce-multilingual' ),
			],
			'settings' => [
				'title' => __( 'WPML Multilingual & Multicurrency for WooCommerce settings', 'woocommerce-multilingual' ),
				'data'  => esc_html( $wcmlSettings ),
			],
			'counter' => sprintf(
				/* translators: The placeholder is the number of processed items. */
				__( '%s items processed', 'woocommerce-multilingual' ),
				'<span class="count"></span>'
			),
			'doing'  => __( 'Processing...', 'woocommerce-multilingual' ),
			'done'   => __( 'Completed', 'woocommerce-multilingual' ),
			'error'  => __( 'Something went wrong, please reload the page and try again', 'woocommerce-multilingual' ),
			'nonces' => [
				'trbl_sync_variations'    => wp_nonce_field( 'trbl_sync_variations', 'trbl_sync_variations_nonce', true, false ),
				'trbl_gallery_images'     => wp_nonce_field( 'trbl_gallery_images', 'trbl_gallery_images_nonce', true, false ),
				'trbl_sync_categories'    => wp_nonce_field( 'trbl_sync_categories', 'trbl_sync_categories_nonce', true, false ),
				'trbl_duplicate_terms'    => wp_nonce_field( 'trbl_duplicate_terms', 'trbl_duplicate_terms_nonce', true, false ),
				'trbl_product_type_terms' => wp_nonce_field( 'trbl_product_type_terms', 'trbl_product_type_terms_nonce', true, false ),
				'trbl_sync_stock'         => wp_nonce_field( 'trbl_sync_stock', 'trbl_sync_stock_nonce', true, false ),
				'fix_relationships'       => wp_nonce_field( 'fix_relationships', 'fix_relationships_nonce', true, false ),
				'sync_deleted_meta'       => wp_nonce_field( 'sync_deleted_meta', 'sync_deleted_meta_nonce', true, false ),
				'register_reviews_in_st'  => wp_nonce_field( 'register_reviews_in_st', 'register_reviews_in_st_nonce', true, false ),
			],
		];

		return $model;
	}

	public function get_all_products_taxonomies() {

		/** @var stdClass[] $all_products_taxonomies */
		$all_products_taxonomies = get_taxonomies( [ 'object_type' => [ 'product' ] ], 'objects' );
		unset(
			$all_products_taxonomies['product_type'],
			$all_products_taxonomies['product_cat'],
			$all_products_taxonomies['product_tag']
		);

		foreach ( $all_products_taxonomies as $key => $taxonomy ) {
			if ( is_taxonomy_translated( $key ) ) {
				$all_products_taxonomies[ $key ]->tax_key     = $key;
			} else {
				unset( $all_products_taxonomies[ $key ] );
			}
		}

		return $all_products_taxonomies;

	}

	public function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return 'troubleshooting.twig';
	}
}
