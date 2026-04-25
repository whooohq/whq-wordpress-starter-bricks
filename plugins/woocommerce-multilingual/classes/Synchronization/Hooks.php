<?php

namespace WCML\Synchronization;

use WCML\Utilities\SyncHash;
use WPML\FP\Obj;
use function WCML\functions\isCli;

//class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_AJAX_Action, \IWPML_REST_Action, \IWPML_CLI_Action, \IWPML_DIC_Action {
class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const HOOK_SYNCHRONIZE_PRODUCT_TRANSLATIONS           = 'wcml_synchronize_product_translations';
	const HOOK_SYNCHRONIZE_PRODUCT_VARIATION_TRANSLATIONS = 'wcml_synchronize_product_variation_translations';
	const HOOK_SYNCHRONIZE_PRODUCT_COMPONENT              = 'wcml_synchronize_product_component';

	const PRIORITY_BEFORE_STOCK_EMAIL_TRIGGER = 9;

	/** @var \woocommerce_wpml */
	protected $woocommerceWpml;

	/** @var \SitePress */
	protected $sitepress;

	/** @var Manager */
	private $manager;

	public function __construct(
		\woocommerce_wpml $woocommerceWpml,
		\SitePress        $sitepress,
		\wpdb             $wpdb,
		SyncHash          $syncHashManager
	) {
		$this->woocommerceWpml = $woocommerceWpml;
		$this->sitepress       = $sitepress;
		$this->manager         = new Manager(
			new Store(
				$woocommerceWpml,
		    $sitepress,
				$wpdb,
				$syncHashManager
			)
		);
	}

	public function add_hooks() {
		if ( is_admin() || isCli() ) {
			add_action( 'save_post', [ $this, 'synchronizeProductTranslationsOnSave' ], PHP_INT_MAX, 2 ); // After WPML.
			add_action( 'icl_make_duplicate', [ $this, 'synchronizeProductDuplication' ], 110, 4 );

			add_action( 'woocommerce_product_quick_edit_save', [ $this, 'synchronizeOnEditSave' ] );
			add_action( 'woocommerce_product_bulk_edit_save', [ $this, 'synchronizeOnEditSave' ] );

			add_action( 'wpml_translation_update', [ $this, 'synchronizeConnectedTranslations' ] );
		}

		if ( is_admin() || wpml_is_rest_request() ) {
			add_action( 'wpml_pro_translation_completed', [ $this, 'synchronizeProductTranslation' ] );
		}

		add_action( self::HOOK_SYNCHRONIZE_PRODUCT_TRANSLATIONS, [ $this, 'synchronizeProductTranslations' ], 10, 3 );
		add_action( self::HOOK_SYNCHRONIZE_PRODUCT_VARIATION_TRANSLATIONS, [ $this, 'synchronizeProductVariationTranslations' ], 10, 3 );
		add_action( self::HOOK_SYNCHRONIZE_PRODUCT_COMPONENT, [ $this, 'synchronizeProductComponent' ], 10, 4 );

		add_action( 'woocommerce_ajax_save_product_variations', [ $this, 'synchronizeProductVariationsOnAjax' ], 11 );
		add_action( 'woocommerce_bulk_edit_variations', [ $this, 'synchronizeProductVariationsOnBulkEdit' ], 10, 3 );

		add_action( 'woocommerce_product_set_stock', [ $this, 'syncProductStock' ], self::PRIORITY_BEFORE_STOCK_EMAIL_TRIGGER );
		add_action( 'woocommerce_variation_set_stock', [ $this, 'syncProductStock' ], self::PRIORITY_BEFORE_STOCK_EMAIL_TRIGGER );
	}

	/**
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	private function canRunProductSynchronization( $post ) {
		if ( 'product' !== $post->post_type ) {
			return false;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return false;
		}

		/* phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected */
		if ( isset( $_POST['autosave'] ) ) {
			return false;
		}

		if ( isset( $_GET['action'] ) && 'trash' === $_GET['action'] ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	private function isProductSynchronizationValidContext() {
		global $pagenow, $wp;
		// exceptions.
		/* phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected */
		$isDuplicating  = ( ! empty( $_POST['icl_ajx_action'] ) && 'make_duplicates' === $_POST['icl_ajx_action'] );
		$isApiRequest   = ! empty( $wp->query_vars['wc-api-version'] );
		$isValidContext = isCli()
			|| $isDuplicating
			|| $isApiRequest
			|| in_array( $pagenow, [ 'post.php', 'post-new.php', 'admin.php' ], true );

		return apply_filters( 'wcml_product_synchronization_on_save_is_valid_context', $isValidContext );
	}

	/**
	 * @param int    $postId
	 * @param \WP_Post $post
	 */
	public function synchronizeProductTranslationsOnSave( $postId, $post ) {
		if ( 'product_variation' === $post->post_type ) {
			$this->setVariationLanguageDetails( $postId, $post );
			return;
		}

		if ( ! $this->canRunProductSynchronization( $post ) ) {
			return;
		}
		if ( ! $this->isProductSynchronizationValidContext() ) {
			return;
		}

		$originalProduct = $this->manager->getOriginalProduct( $post );
		if ( $this->woocommerceWpml->is_wpml_prior_4_2() ) {
			$is_using_native_editor = ! $this->woocommerceWpml->settings['trnsl_interface'];
		} else {
			$is_using_native_editor = ! \WPML_TM_Post_Edit_TM_Editor_Mode::is_using_tm_editor( $this->sitepress, $originalProduct->ID );
		}

		if ( $is_using_native_editor ) {
			// WARNING!!!
			// This depends on the stored/selected setting, not the actual editor being used for editing a translation.
			// Keeping it for backward compatibility since this is the original logic.
			$this->synchronizeProductTranslationsOnSaveInNativeEditor( $postId, $post );
			return;
		}

		$this->manager->run( $post );
	}

	/**
	 * @param int      $variationId
	 * @param \WP_Post $variation
	 */
	private function setVariationLanguageDetails( $variationId, $variation ) {
		$productId                  = (int) $variation->post_parent;
		$productLanguage            = $this->sitepress->get_language_for_element( $productId, 'post_product' );
		if ( ! $productLanguage ) {
			return;
		}
		$productInOriginalLanguage = $this->woocommerceWpml->products->is_original_product( $productId );
		if ( ! $productInOriginalLanguage ) {
			return;
		}
		$variationLanguageDetails = $this->sitepress->get_element_language_details( $variationId, 'post_product_variation' );
		if ( ! is_object( $variationLanguageDetails ) ) {
			$this->sitepress->set_element_language_details( $variationId, 'post_product_variation', null, $productLanguage );
		}
	}

	/**
	 * Legacy logic dealing with the native editor as the preferred translation editor.
	 *
	 * Copied verbatim from the original implementation.
	 *
	 * @param int      $postId
	 * @param \WP_Post $post
	 */
	private function synchronizeProductTranslationsOnSaveInNativeEditor( $postId, $post ) {
		$originalProduct = $this->manager->getOriginalProduct( $post );
		if ( $originalProduct->ID === $postId ) {
			$this->manager->run( $post );
			return;
		}

		$originalLanguage = $this->manager->getElementLanguage( $originalProduct->ID );
		$currentLanguage  = $this->sitepress->get_current_language();

		if ( $originalLanguage === $currentLanguage ) {
			$this->manager->run( $post );
			return;
		}

		if ( ! empty( $_POST['wp-preview'] ) ) {
			return;
		}

		$postId = apply_filters( 'wpml_object_id', $postId, 'product', false, $currentLanguage );

		$this->manager->run( $originalProduct, [ $postId ], [ $postId => $currentLanguage ] );
	}

	/**
	 * @param int    $productId
	 * @param string $language
	 * @param array  $duplicatedPostData
	 * @param int    $duplicatedProductId
	 */
	public function synchronizeProductDuplication( $productId, $language, $duplicatedPostData, $duplicatedProductId ) {
		if ( 'product' !== $duplicatedPostData['post_type'] ) {
			return;
		}

		// Duplication should clone de variation description field.
		global $iclTranslationManagement;
		$customFieldSettings              = $iclTranslationManagement->settings['custom_fields_translation'];
		$variationDescriptionFieldSetting = Obj::prop( '_variation_description', $customFieldSettings );

		$iclTranslationManagement->settings['custom_fields_translation']['_variation_description'] = WPML_COPY_CUSTOM_FIELD;

		$product         = get_post( $productId );
		$originalProduct = $this->manager->getOriginalProduct( $product );

		$this->manager->runProductComponents( $originalProduct, [ $duplicatedProductId ], [ $duplicatedProductId => $language ] );

		// Restore the variation description field original setting.
		if ( null === $variationDescriptionFieldSetting ) {
			unset( $iclTranslationManagement->settings['custom_fields_translation']['_variation_description'] );
		} else {
			$iclTranslationManagement->settings['custom_fields_translation']['_variation_description'] = $variationDescriptionFieldSetting;
		}
	}

	/**
	 * @param int $translatedProductId
	 */
	public function synchronizeProductTranslation( $translatedProductId ) {
		if ( 'product' !== get_post_type( $translatedProductId ) ) {
			return;
		}

		$translatedProduct = get_post( $translatedProductId );
		$originalProduct   = $this->manager->getOriginalProduct( $translatedProduct );

		if ( $originalProduct ) {
			$translationsLanguages = [
				$translatedProductId => $this->manager->getElementLanguage( $translatedProductId ),
			];
			$this->manager->runProductComponents( $originalProduct, [ $translatedProductId ], $translationsLanguages );
		}
	}

	/**
	 * @todo There is a regression here that we need to investigate.
	 *
	 * @see https://git.onthegosystems.com/glue-plugins/wpml/woocommerce-multilingual/-/merge_requests/1028
	 * @see https://git.onthegosystems.com/glue-plugins/wpml/woocommerce-multilingual/-/commit/e64378a8656bdf938f1e20565c2f65c94a26a3bb
	 */
	public function synchronizeConnectedTranslations() {
		if ( 'connect_translations' !== Obj::prop( 'icl_ajx_action', $_POST ) ) {
			return;
		}

		$postType = Obj::prop( 'post_type', $_POST );
		if ( 'product' !== $postType ) {
			return;
		}

		$newTrid      = Obj::prop( 'new_trid', $_POST );
		$translations = $this->sitepress->get_element_translations( $newTrid, 'post_' . $postType );
		if ( ! $translations ) {
			return;
		}

		$postId      = Obj::prop( 'post_id', $_POST );
		$setAsSource = Obj::prop( 'set_as_source', $_POST );

		remove_action( 'wpml_translation_update', [ $this, 'synchronizeConnectedTranslations' ] );
		// Before the referenced commit:
		// 		* If the translations being looped reached the original, syncing it to the post being sent to the AJAX call,
		//					unless there is a mandatory setting to set the post being sent as original, in which case we update the previous original to be in sync with the new (?)
		// 		* For every other translation, if there is a mandatory setting to set the post being sent as original, sync this sent post into the translation
		// After the referended commit:
		//  	* We loop over the tranbslations but we act over just the first one that matches any of the two criteria.
		foreach ( $translations as $translation ) {
			if ( $setAsSource && ! $translation->original ) {
				$productId           = $postId;
				$translatedProductId = $translation->element_id;
				$language            = $translation->language_code;
				break;
			} elseif ( ! $setAsSource && $translation->original ) {
				$productId           = $translation->element_id;
				$translatedProductId = $postId;
				$language            = $this->sitepress->get_current_language();
				break;
			}
		}

		if ( isset( $productId, $translatedProductId, $language ) ) {
			$product = get_post( $productId );
			$this->manager->runProductComponents( $product, [ $translatedProductId ], [ $translatedProductId => $language ] );
			$this->sitepress->copy_custom_fields( $productId, $translatedProductId );
			$this->woocommerceWpml->translation_editor->create_product_translation_package( $productId, $newTrid, $language, ICL_TM_COMPLETE );
		}
		add_action( 'wpml_translation_update', [ $this, 'synchronizeConnectedTranslations' ] );
	}

	/**
	 * @param \WC_Product $productObject
	 */
	public function synchronizeOnEditSave( $productObject ) {
		$productId       = $productObject->get_id();
		$product         = get_post( $productId );
		$isOriginal      = $this->manager->isOriginalProduct( $product );
		$originalProduct = $this->manager->getOriginalProduct( $product );
		$translations    = $this->manager->getElementTranslations( $productId );

		if ( ! $translations ) {
			return;
		}

		if ( ! $isOriginal ) {
			$language = $this->manager->getElementLanguage( $productId );
			$this->manager->runProductComponents( $originalProduct, [ $productId ], [ $productId => $language ] );
			return;
		}

		$translationsLanguages = [];
		foreach ( $translations as $index => $translation ) {
			if ( $productId === (int) $translation ) {
				unset( $translations[ $index ] );
			} else {
				$translationsLanguages[ $translation ] = $this->manager->getElementLanguage( $translation );
			}
		}

		if ( empty( $translations ) ) {
			return;
		}

		$this->manager->runProductComponents( $originalProduct, $translations, $translationsLanguages );
	}

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function synchronizeProductTranslations( $product, $translationsIds = [], $translationsLanguages = [] ) {
		if ( ! $this->canRunProductSynchronization( $product ) ) {
			return;
		}
		$this->manager->run( $product, $translationsIds, $translationsLanguages );
	}

	/**
	 * @param \WP_Post          $variation
	 * @param int[]             $variationTranslations
	 * @param array<int,string> $variationTranslationsLanguages
	 */
	public function synchronizeProductVariationTranslations( $variation, $variationTranslations, $variationTranslationsLanguages ) {
		$this->manager->runProductVariationComponents( $variation, $variationTranslations, $variationTranslationsLanguages );
	}

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 * @param string            $componentName
	 */
	public function synchronizeProductComponent( $product, $translationsIds, $translationsLanguages, $componentName ) {
		if ( ! $this->canRunProductSynchronization( $product ) ) {
			return;
		}
		$this->manager->runComponent( $product, $translationsIds, $translationsLanguages, $componentName );
	}

	/**
	 * @param int $productId
	 */
	public function synchronizeProductVariationsOnAjax( $productId ) {
		$product    = get_post( $productId );
		$isOriginal = $this->manager->isOriginalProduct( $product );

		if ( ! $isOriginal ) {
			return;
		}

		$translations = $this->manager->getElementTranslations( $productId );
		if ( empty( $translations ) ) {
			return;
		}

		$translationsLanguages = [];
		foreach ( $translations as $index => $translation ) {
			if ( $productId === (int) $translation ) {
				unset( $translations[ $index ] );
			} else {
				$translationsLanguages[ $translation ] = $this->manager->getElementLanguage( $translation );
			}
		}

		$this->manager->runComponent( $product, $translations, $translationsLanguages, Store::COMPONENT_VARIATIONS );
		$this->manager->runComponent( $product, $translations, $translationsLanguages, Store::COMPONENT_ATTRIBUTES );
	}

	/**
	 * @param string $bulkAction
	 * @param array  $data
	 * @param int    $productId
	 */
	public function synchronizeProductVariationsOnBulkEdit( $bulkAction, $data, $productId ) {
		$this->synchronizeProductVariationsOnAjax( $productId );
	}

	/**
	 * @param \WC_Product $product
	 */
	public function syncProductStock( $product ) {
		$productId    = $product->get_id();
		$translations = $this->manager->getElementTranslations( $productId, false, false );
		if ( empty( $translations ) ) {
			return;
		}

		$translationsLanguages = [];
		foreach ( $translations as $index => $translation ) {
			if ( $productId === (int) $translation ) {
				unset( $translations[ $index ] );
			} else {
				$translationsLanguages[ $translation ] = $this->manager->getElementLanguage( $translation );
			}
		}

		$this->manager->runComponent( get_post( $productId ), $translations, $translationsLanguages, Store::COMPONENT_STOCK );

		$wcml_data_store = wcml_product_data_store_cpt();
		wp_cache_delete( $productId, 'post_meta' );
		wp_cache_delete( 'product-' . $productId, 'products' );
		delete_transient( 'wc_product_children_' . $productId );
		$wcml_data_store->update_lookup_table_data( $productId );

		foreach( $translations as $translation ) {
			wp_cache_delete( $translation, 'post_meta' );
			wp_cache_delete( 'product-' . $translation, 'products' );
			delete_transient( 'wc_product_children_' . $translation );
			$wcml_data_store->update_lookup_table_data( $translation );
		}

		delete_transient( 'wc_low_stock_count' );
		delete_transient( 'wc_outofstock_count' );
	}

}
