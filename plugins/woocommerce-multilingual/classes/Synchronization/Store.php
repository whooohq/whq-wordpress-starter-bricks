<?php

namespace WCML\Synchronization;

use WCML\Utilities\SyncHash;

class Store {

	const COMPONENT_ATTACHMENTS           = 'attachments';
	const COMPONENT_ATTRIBUTES            = 'attributes';
	const COMPONENT_DOWNLOADABLE_FILES    = 'downloadableFiles';
	const COMPONENT_LINKED                = 'linked';
	const COMPONENT_META                	= 'meta';
	const COMPONENT_POST                  = 'post';
	const COMPONENT_STOCK                 = 'stock';
	const COMPONENT_TAXONOMIES            = 'taxonomies';
	const COMPONENT_VARIATIONS            = 'variations';
	const COMPONENT_VARIATION_ATTACHMENTS = 'variationAttachments';
	const COMPONENT_VARIATION_TAXONOMIES  = 'variationTaxonomies';
	const COMPONENT_VARIATION_META        = 'variationMeta';

	/** @var \woocommerce_wpml */
	protected $woocommerceWpml;

	/** @var \SitePress */
	protected $sitepress;

	/** @var \WPML_Post_Translation */
	protected $postTranslations;

	/** @var \WPML_Term_Translation */
	protected $termTranslations;

	/** @var \wpdb */
	protected $wpdb;

	/** @var SyncHash */
	protected $syncHashManager;

	/**
	 * @param \woocommerce_wpml      $woocommerceWpml
	 * @param \SitePress             $sitepress
	 * @param \wpdb                  $wpdb
	 * @param SyncHash               $syncHashManager
	 */
	public function __construct(
		\woocommerce_wpml $woocommerceWpml,
		\SitePress        $sitepress,
		\wpdb             $wpdb,
		SyncHash          $syncHashManager
	) {
		$this->woocommerceWpml  = $woocommerceWpml;
		$this->sitepress        = $sitepress;
		$this->wpdb             = $wpdb;
		$this->syncHashManager  = $syncHashManager;

		global $wpml_post_translations, $wpml_term_translations;
		$this->postTranslations = $wpml_post_translations;
		$this->termTranslations = $wpml_term_translations;
	}

	/**
	 * @param string $component
	 *
	 * @return \WCML\Synchronization\Component\Synchronizer
	 *
	 * @throws \Exception
	 */
	public function getComponent( $component ) {
		switch ( $component ) {
			case self::COMPONENT_ATTACHMENTS:
				return new Component\Attachments(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->postTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_ATTRIBUTES:
				return new Component\Attributes(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->termTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_DOWNLOADABLE_FILES:
				return new Component\DownloadableFiles(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->postTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_LINKED:
				return new Component\Linked(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->postTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_META:
				return new Component\Meta(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->postTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_POST:
				return new Component\Post(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->postTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_STOCK:
				return new Component\Stock(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->postTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_TAXONOMIES:
				return new Component\Taxonomies(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->termTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_VARIATIONS:
				return new Component\Variations(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->postTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_VARIATION_ATTACHMENTS:
				return new Component\VariationAttachments(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->termTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_VARIATION_TAXONOMIES:
				return new Component\VariationTaxonomies(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->termTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
			case self::COMPONENT_VARIATION_META:
				return new Component\VariationMeta(
					$this->woocommerceWpml,
					$this->sitepress,
					$this->termTranslations,
					$this->wpdb,
					$this->syncHashManager
				);
		}

		throw new \Exception( 'Unknown synchronization component.' );
	}

}
