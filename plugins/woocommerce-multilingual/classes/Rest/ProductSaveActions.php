<?php

namespace WCML\Rest;

use function WCML\functions\getId;

/**
 * We need to reuse the `after_save_post` protected method
 * of the \WPML_Post_Translation and the only way is by inheritance.
 * The abstract methods are not used in the parent, but
 * we need implement it to respect the contract.
 * We are also including the WCML logic to `synchronize_products`.
 */
class ProductSaveActions extends \WPML_Post_Translation {

	/** @var \SitePress $sitepress */
	private $sitepress;

	/** @var \woocommerce_wpml $woocommerceWpml */
	private $woocommerceWpml;

	public function __construct(
		array $settings,
		\wpdb $wpdb,
		\SitePress $sitepress,
		\woocommerce_wpml $woocommerceWpml
	) {
		parent::__construct( $settings, $wpdb );
		$this->sitepress       = $sitepress;
		$this->woocommerceWpml = $woocommerceWpml;
	}

	/**
	 * @param object|\WC_Abstract_Legacy_Product $product
	 * @param int|null                           $trid
	 * @param string                             $langCode
	 * @param int|null                           $translationOf
	 * @param ?\WP_REST_Request                  $wpRestRequest
	 */
	public function run( $product, $trid, $langCode, $translationOf, $wpRestRequest = null ) {
		$productId      = getId( $product );
		$trid           = $trid ?: $this->get_save_post_trid( $productId, null );
		$langCode       = $langCode ?: parent::get_save_post_lang( $productId, $this->sitepress );
		$sourceLangCode = $this->get_element_lang_code( $translationOf );

		if ( ! $this->synchronize_product_stock_only( $wpRestRequest ) ) {
			$this->after_save_post( $trid, get_post( $productId, ARRAY_A ), $langCode, $sourceLangCode );
		}

		$originalProductId = $translationOf ?: $productId;
		do_action( \WCML\Synchronization\Hooks::HOOK_SYNCHRONIZE_PRODUCT_TRANSLATIONS, get_post( $originalProductId ), [], [] );
		if ( WCML_MULTI_CURRENCIES_INDEPENDENT === $this->woocommerceWpml->settings['enable_multi_currency'] ) {
			$this->woocommerceWpml->multi_currency->custom_prices->save_custom_prices_on_rest( $originalProductId, $wpRestRequest );
		}
	}

	/**
	 * @param ?\WP_REST_Request $wpRestRequest
	 *
	 * @return bool
	 */
	private function synchronize_product_stock_only( $wpRestRequest ) {
		if ( $wpRestRequest instanceof \WP_REST_Request ) {
			$stockSyncFields = [
				'id',
				'manage_stock',
				'stock_quantity',
			];

			$requestParamsArray = $wpRestRequest->get_params();
			$requestFields      = array_keys( $requestParamsArray );

			$nonStockFields = array_diff( $requestFields, $stockSyncFields );

			return empty( $nonStockFields );
		}

		return false;
	}

	public function save_post_actions( $postId, $post ) {
		throw new \Exception( 'This method should not be called, use `run` instead.' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_save_post_trid( $postId, $post_status ) {
		return $this->get_element_trid( $postId );
	}

	/**
	 * @inheritDoc
	 */
	protected function get_save_post_source_lang( $trid, $language_code, $default_language ) {
		return $this->get_source_lang_code( $this->get_element_id( $language_code, $trid ) );
	}
}
