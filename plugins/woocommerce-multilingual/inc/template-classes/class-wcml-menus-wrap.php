<?php

use WCML\Utilities\AdminPages;

use function WCML\functions\getSetting;

class WCML_Menus_Wrap extends WCML_Menu_Wrap_Base {

	private $product_attribute_names = [];

	// Is 'product_type' is used for tags?
	private $product_builtin_taxonomy_names = [
		'product_cat',
		'product_tag',
		'product_shipping_class',
		'product_type',
		'translation_priority',
	];

	private $product_extra_taxonomy_names = [];

	private $product_attributes       = [];
	private $product_extra_taxonomies = [];

	private $selected_attribute;
	private $selected_taxonomy;

	/**
	 * WCML_Menus_Wrap constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( $woocommerce_wpml ) {
		// @todo Cover by tests, required for wcml-3037.
		parent::__construct( $woocommerce_wpml );

		$this->product_attributes = $this->woocommerce_wpml->attributes->get_translatable_attributes();
		if ( $this->product_attributes ) {
			foreach ( $this->product_attributes as $product_attribute ) {
				$this->product_attribute_names[] = 'pa_' . $product_attribute->attribute_name;
			}
		}

		$product_taxonomies = get_object_taxonomies( 'product', 'objects' );
		foreach ( $product_taxonomies as $product_taxonomy_name => $product_taxonomy_object ) {
			if (
				! in_array( $product_taxonomy_name, $this->product_builtin_taxonomy_names ) &&
				! in_array( $product_taxonomy_name, $this->product_attribute_names ) &&
				is_taxonomy_translated( $product_taxonomy_name )
			) {
				$this->product_extra_taxonomies[ $product_taxonomy_name ] = $product_taxonomy_object;
				$this->product_extra_taxonomy_names[]                     = $product_taxonomy_name;
			}
		}

		$taxonomy = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : false;

		if ( $this->product_attributes ) {
			if ( ! empty( $taxonomy ) ) {
				foreach ( $this->product_attributes as $attribute ) {
					if ( $attribute->attribute_name == $taxonomy ) {
						$this->selected_attribute = $attribute;
						break;
					}
				}
			}
			if ( empty( $selected_attribute ) ) {
				$this->selected_attribute = current( $this->product_attributes );
			}
		}

		if ( $this->product_extra_taxonomies ) {
			if ( ! empty( $taxonomy ) ) {
				foreach ( $this->product_extra_taxonomies as $taxonomy ) {
					if ( $taxonomy->name == $taxonomy ) {
						$this->selected_taxonomy = $taxonomy;
						break;
					}
				}
			}
			if ( empty( $this->selected_taxonomy ) ) {
				$this->selected_taxonomy = current( $this->product_extra_taxonomies );
			}
		}

	}

	/**
	 * @return array
	 */
	protected function get_child_model() {
		$current_tab = AdminPages::getTabToDisplay();

		$model = [

			'strings'             => [
				'title'              => WCML_Admin_Menus::getWcmlLabel(),
				'untranslated_terms' => __( 'You have untranslated terms!', 'woocommerce-multilingual' ),
			],
			'menu'                => [
				'products'          => [
					'title'  => __( 'Products', 'woocommerce-multilingual' ),
					'url'    => admin_url( 'admin.php?page=wpml-wcml' ),
					'active' => $current_tab == 'products' ? 'nav-tab-active' : '',
				],
				'taxonomies'        => [
					'product_cat' => [
						'name'            => __( 'Categories', 'woocommerce-multilingual' ),
						'title'           => ! $this->woocommerce_wpml->terms->is_fully_translated( 'product_cat' ) ? __( 'You have untranslated terms!', 'woocommerce-multilingual' ) : '',
						'active'          => $current_tab == 'product_cat' ? 'nav-tab-active' : '',
						'url'             => admin_url( 'admin.php?page=wpml-wcml&tab=product_cat' ),
						'translated'      => $this->woocommerce_wpml->terms->is_fully_translated( 'product_cat' ),
						'is_translatable' => is_taxonomy_translated( 'product_cat' ),
					],
					'product_tag' => [
						'name'            => __( 'Tags', 'woocommerce-multilingual' ),
						'title'           => ! $this->woocommerce_wpml->terms->is_fully_translated( 'product_tag' ) ? __( 'You have untranslated terms!', 'woocommerce-multilingual' ) : '',
						'active'          => $current_tab == 'product_tag' ? 'nav-tab-active' : '',
						'url'             => admin_url( 'admin.php?page=wpml-wcml&tab=product_tag' ),
						'translated'      => $this->woocommerce_wpml->terms->is_fully_translated( 'product_tag' ),
						'is_translatable' => is_taxonomy_translated( 'product_tag' ),
					],
				],
				'custom_taxonomies' => [
					'name'       => __( 'Custom Taxonomies', 'woocommerce-multilingual' ),
					'active'     => $current_tab == 'custom-taxonomies' ? 'nav-tab-active' : '',
					'url'        => admin_url( 'admin.php?page=wpml-wcml&tab=custom-taxonomies' ),
					'translated' => ! $this->product_extra_taxonomies || ( isset( $this->selected_taxonomy ) && $this->woocommerce_wpml->terms->is_fully_translated( $this->selected_taxonomy->name ) ),
					'show'       => ! empty( $this->product_extra_taxonomies ),
				],
				'attributes'        => [
					'name'       => __( 'Attributes', 'woocommerce-multilingual' ),
					'active'     => $current_tab == 'product-attributes' ? 'nav-tab-active' : '',
					'url'        => admin_url( 'admin.php?page=wpml-wcml&tab=product-attributes' ),
					'translated' => $this->woocommerce_wpml->attributes->is_attributes_fully_translated(),
				],
				'shipping_classes'  => [
					'name'            => __( 'Shipping Classes', 'woocommerce-multilingual' ),
					'title'           => ! $this->woocommerce_wpml->terms->is_fully_translated( 'product_shipping_class' ) ? __( 'You have untranslated terms!', 'woocommerce-multilingual' ) : '',
					'active'          => $current_tab == 'product_shipping_class' ? 'nav-tab-active' : '',
					'url'             => admin_url( 'admin.php?page=wpml-wcml&tab=product_shipping_class' ),
					'translated'      => $this->woocommerce_wpml->terms->is_fully_translated( 'product_shipping_class' ),
					'is_translatable' => is_taxonomy_translated( 'product_shipping_class' ),
				],
				'settings'          => [
					'name'   => __( 'Settings', 'woocommerce-multilingual' ),
					'active' => $current_tab == 'settings' ? 'nav-tab-active' : '',
					'url'    => admin_url( 'admin.php?page=wpml-wcml&tab=settings' ),
				],
				'multi_currency'    => [
					'name'   => __( 'Multicurrency', 'woocommerce-multilingual' ),
					'active' => $current_tab == 'multi-currency' ? 'nav-tab-active' : '',
					'url'    => admin_url( 'admin.php?page=wpml-wcml&tab=multi-currency' ),
				],
				'slugs'             => [
					'name'   => __( 'Store URLs', 'woocommerce-multilingual' ),
					'active' => $current_tab == 'slugs' ? 'nav-tab-active' : '',
					'url'    => admin_url( 'admin.php?page=wpml-wcml&tab=slugs' ),
				],
				'status'            => [
					'name'   => __( 'Status', 'woocommerce-multilingual' ),
					'active' => $current_tab == 'status' ? 'nav-tab-active' : '',
					'url'    => admin_url( 'admin.php?page=wpml-wcml&tab=status' ),
				],
				'troubleshooting'   => [
					'name'   => __( 'Troubleshooting', 'woocommerce-multilingual' ),
					'active' => $current_tab == 'troubleshooting' ? 'nav-tab-active' : '',
					'url'    => admin_url( 'admin.php?page=wpml-wcml&tab=troubleshooting' ),
				],
			],
			'set_up_wizard_run'   => getSetting( 'set_up_wizard_run' ),
			'can_manage_options'  => current_user_can( 'wpml_manage_woocommerce_multilingual' ),
			'content'             => $this->get_current_menu_content( $current_tab ),
		];

		return $model;
	}

	protected function get_current_menu_content( $current_tab ) {
		global $sitepress, $sitepress_settings;

		$woocommerce_wpml = $this->woocommerce_wpml;

		$content = '';

		switch ( $current_tab ) {

			case 'products':
				$wcml_products_ui = new WCML_Products_UI( $woocommerce_wpml, $sitepress );
				$content          = $wcml_products_ui->get_view();

				break;

			case 'multi-currency':
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
					$wcml_mc_ui = new WCML_Multi_Currency_UI( $woocommerce_wpml, $sitepress );
					$content    = $wcml_mc_ui->get_view();
				}

				break;

			case 'product-attributes':
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
					$attribute_translation_ui = new WCML_Attribute_Translation_UI( $woocommerce_wpml, $sitepress );
					$content                  = $attribute_translation_ui->get_view();
				}
				break;

			case 'custom-taxonomies':
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
					$custom_taxonomy_translation_ui = new WCML_Custom_Taxonomy_Translation_UI( $woocommerce_wpml, $sitepress );
					$content                        = $custom_taxonomy_translation_ui->get_view();
				}
				break;

			case 'slugs':
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
					$wcml_store_urls = new WCML_Store_URLs_UI( $woocommerce_wpml, $sitepress );
					$content         = $wcml_store_urls->get_view();
				}
				break;

			case 'status':
				if ( current_user_can( 'wpml_manage_woocommerce_multilingual' ) ) {
					$wcml_status = new WCML_Status_UI( $woocommerce_wpml, $sitepress, $sitepress_settings );
					$content     = $wcml_status->get_view();
				}
				break;

			case 'troubleshooting':
				if ( current_user_can( 'wpml_manage_woocommerce_multilingual' ) ) {
					$wcml_troubleshooting = new WCML_Troubleshooting_UI( $woocommerce_wpml );
					$content              = $wcml_troubleshooting->get_view();
				}
				break;

			case 'settings':
				if ( current_user_can( 'wpml_manage_woocommerce_multilingual' ) ) {
					$wcml_settings_ui = new WCML_Settings_UI( $woocommerce_wpml, $sitepress );
					$content          = $wcml_settings_ui->get_view();
				}
				break;

			default:
				$taxonomy_names = array_merge( $this->product_builtin_taxonomy_names, $this->product_extra_taxonomy_names );
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) && in_array( $current_tab, $taxonomy_names ) ) {
					$WPML_Translate_Taxonomy = new WPML_Taxonomy_Translation(
						$current_tab,
						[
							'taxonomy_selector' => false,
							'status'            => WPML_TT_TAXONOMIES_ALL,
						]
					);
					ob_start();
					?>
					<div class="wpml-loading-taxonomy"><span
								class="spinner is-active"></span><?php echo __( 'Loading ...', 'woocommerce-multilingual' ); ?>
					</div>
					<div class="wpml_taxonomy_loaded" style="display:none">
						<?php
						$WPML_Translate_Taxonomy->render();
						?>
					</div>
					<?php

					$content = ob_get_contents();
					ob_end_clean();

				}
		}

		return $content;

	}


}
