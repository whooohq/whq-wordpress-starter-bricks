<?php

use WCML\Utilities\AdminPages;

use function WCML\functions\getSetting;

class WCML_Menus_Wrap extends WCML_Menu_Wrap_Base {
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var array
	 */
	private $sitepress_settings;

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress $sitepress
	 * @param array $sitepress_settings
	 */
	public function __construct( $woocommerce_wpml, $sitepress, $sitepress_settings ) {
		parent::__construct( $woocommerce_wpml );
		$this->sitepress          = $sitepress;
		$this->sitepress_settings = $sitepress_settings;
	}

	/**
	 * @return array
	 */
	protected function get_child_model() {
		$current_tab = AdminPages::getTabToDisplay();

		$model = [
			'strings'            => [
				'title'              => WCML_Admin_Menus::getWcmlLabel(),
				'untranslated_terms' => __( 'You have untranslated terms!', 'woocommerce-multilingual' ),
			],
			'menu'               => [
				'translations'    => [
					'title'  => __( 'Multilingual', 'woocommerce-multilingual' ),
					'url'    => \WCML\Utilities\AdminUrl::getMultilingualTab(),
					'active' => $current_tab == AdminPages::TAB_MULTILINGUAL ? 'nav-tab-active' : '',
				],
				\WCML\Utilities\AdminUrl::TAB_SETTINGS => [
					'name'   => __( 'Settings', 'woocommerce-multilingual' ),
					'active' => \WCML\Utilities\AdminUrl::TAB_SETTINGS == $current_tab ? 'nav-tab-active' : '',
					'url'    => \WCML\Utilities\AdminUrl::getSettingsTab(),
				],
				'multi_currency'  => [
					'name'   => __( 'Multicurrency', 'woocommerce-multilingual' ),
					'active' => \WCML\Utilities\AdminUrl::TAB_MULTICURRENCY == $current_tab ? 'nav-tab-active' : '',
					'url'    => \WCML\Utilities\AdminUrl::getMultiCurrencyTab(),
				],
				\WCML\Utilities\AdminUrl::TAB_STORE_URL => [
					'name'   => __( 'Store URLs', 'woocommerce-multilingual' ),
					'active' => \WCML\Utilities\AdminUrl::TAB_STORE_URL == $current_tab ? 'nav-tab-active' : '',
					'url'    => \WCML\Utilities\AdminUrl::getStoreURLTab(),
				],
				\WCML\Utilities\AdminUrl::TAB_STATUS => [
					'name'   => __( 'Status', 'woocommerce-multilingual' ),
					'active' => \WCML\Utilities\AdminUrl::TAB_STATUS == $current_tab ? 'nav-tab-active' : '',
					'url'    => \WCML\Utilities\AdminUrl::getStatusTab(),
				],
				\WCML\Utilities\AdminUrl::TAB_TROUBLESHOOTING => [
					'name'   => __( 'Troubleshooting', 'woocommerce-multilingual' ),
					'active' => \WCML\Utilities\AdminUrl::TAB_TROUBLESHOOTING == $current_tab ? 'nav-tab-active' : '',
					'url'    => \WCML\Utilities\AdminUrl::getTroubleshootingTab(),
				],
			],
			'set_up_wizard_run'  => getSetting( 'set_up_wizard_run' ),
			'can_manage_options' => current_user_can( 'wpml_manage_woocommerce_multilingual' ),
			'content'            => $this->get_current_menu_content( $current_tab ),
		];

		return $model;
	}

	protected function get_current_menu_content( $current_tab ) {

		switch ( $current_tab ) {

			case AdminPages::TAB_MULTILINGUAL_STANDALONE:
				wcml_safe_redirect( \WCML\Utilities\AdminUrl::getMultilingualTab() );
				break;

			case AdminPages::TAB_MULTILINGUAL:
				$wcml_products_ui = new WCML_Multilingual_UI();

				return $wcml_products_ui->get_view();

			case AdminPages::TAB_MULTICURRENCY:
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
					$wcml_mc_ui = new WCML_Multi_Currency_UI( $this->woocommerce_wpml, $this->sitepress );

					return $wcml_mc_ui->get_view();
				}
				break;

			case \WCML\Utilities\AdminUrl::TAB_STORE_URL:
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
					$wcml_store_urls = new WCML_Store_URLs_UI( $this->woocommerce_wpml, $this->sitepress );

					return $wcml_store_urls->get_view();
				}
				break;

			case \WCML\Utilities\AdminUrl::TAB_STATUS:
				if ( current_user_can( 'wpml_manage_woocommerce_multilingual' ) ) {
					$wcml_status = new WCML_Status_UI( $this->woocommerce_wpml, $this->sitepress, $this->sitepress_settings );

					return $wcml_status->get_view();
				}
				break;

			case \WCML\Utilities\AdminUrl::TAB_TROUBLESHOOTING:
				if ( current_user_can( 'wpml_manage_woocommerce_multilingual' ) ) {
					$wcml_troubleshooting = new WCML_Troubleshooting_UI( $this->woocommerce_wpml );

					return $wcml_troubleshooting->get_view();
				}
				break;

			case \WCML\Utilities\AdminUrl::TAB_SETTINGS:
				if ( current_user_can( 'wpml_manage_woocommerce_multilingual' ) ) {
					$wcml_settings_ui = new WCML_Settings_UI( $this->woocommerce_wpml, $this->sitepress );

					return $wcml_settings_ui->get_view();
				}
				break;

		}

		/**
		 * Support for legacy urls
		 * This functionality has been moved from WCML to WPML
		 * Links point to WPML equivalents of the screens - preserved so that users can still use the "old links"
		 */
		switch ( $current_tab ) {

			case \WCML\Utilities\AdminUrl::TAB_PRODUCTS:
				wcml_safe_redirect( \WCML\Utilities\AdminUrl::getWPMLTMDashboardProducts() );
				break;

			case 'product-attributes':
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
					$this->redirectToWPMLTaxonomyTranslation( $_GET['taxonomy'] ?? null );
				}
				break;

			case 'product_cat':
			case 'product_tag':
			case 'product_shipping_class':
			case 'custom-taxonomies':
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
					$this->redirectToWPMLTaxonomyTranslation( $current_tab );
				}
				break;

			default:
				$taxonomy_names = $this->getAllProductTaxomyNames();
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) && in_array( $current_tab, $taxonomy_names ) ) {
					$this->redirectToWPMLTaxonomyTranslation( $current_tab );
				}
		}

		return '';
	}

	private function getAllProductTaxomyNames() : array {
		$product_builtin_taxonomy_names = [
			'product_cat',
			'product_tag',
			'product_shipping_class',
			'product_type',
			'translation_priority',
		];

		$product_extra_taxonomy_names = [];

		$product_attribute_names = $this->getProductAttributeNames();
		$product_taxonomies      = get_object_taxonomies( 'product', 'objects' );
		foreach ( $product_taxonomies as $product_taxonomy_name => $product_taxonomy_object ) {
			if (
				! in_array( $product_taxonomy_name, $product_builtin_taxonomy_names ) &&
				! in_array( $product_taxonomy_name, $product_attribute_names ) &&
				is_taxonomy_translated( $product_taxonomy_name )
			) {
				$product_extra_taxonomy_names[] = $product_taxonomy_name;
			}
		}

		return array_merge( $product_builtin_taxonomy_names, $product_extra_taxonomy_names );
	}

	private function getProductAttributeNames(): array {
		$product_attribute_names = [];

		$product_attributes = $this->woocommerce_wpml->attributes->get_translatable_attributes();
		if ( $product_attributes ) {
			foreach ( $product_attributes as $product_attribute ) {
				$product_attribute_names[] = \WCML\Utilities\WCTaxonomies::TAXONOMY_PREFIX_ATTRIBUTE . $product_attribute->attribute_name;
			}
		}

		return $product_attribute_names;
	}

	/**
	 * @param ?string $taxonomy
	 */
	private function redirectToWPMLTaxonomyTranslation( $taxonomy = null ) {
		wcml_safe_redirect( \WCML\Utilities\AdminUrl::getWPMLTaxonomyTranslation( $taxonomy ) );
	}
}
