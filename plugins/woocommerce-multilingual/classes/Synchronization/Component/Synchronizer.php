<?php

namespace WCML\Synchronization\Component;

use WCML\Utilities\SyncHash;

abstract class Synchronizer {

	/** @var \woocommerce_wpml */
	protected $woocommerceWpml;

	/** @var \SitePress */
	protected $sitepress;

	/** @var \WPML_Element_Translation */
	protected $elementTranslations;

	/** @var \wpdb */
	protected $wpdb;

	/** @var SyncHash */
	protected $syncHashManager;

	/**
	 * @param \woocommerce_wpml      $woocommerceWpml
	 * @param \SitePress             $sitepress
	 * @param \WPML_Element_Translation $elementTranslations
	 * @param \wpdb                  $wpdb
	 * @param SyncHash               $syncHashManager
	 */
	public function __construct(
		\woocommerce_wpml         $woocommerceWpml,
		\SitePress                $sitepress,
		\WPML_Element_Translation $elementTranslations,
		\wpdb                     $wpdb,
		SyncHash                  $syncHashManager
	) {
		$this->woocommerceWpml     = $woocommerceWpml;
		$this->sitepress           = $sitepress;
		$this->elementTranslations = $elementTranslations;
		$this->wpdb                = $wpdb;
		$this->syncHashManager     = $syncHashManager;
	}

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	abstract public function run( $product, $translationsIds, $translationsLanguages );

}

