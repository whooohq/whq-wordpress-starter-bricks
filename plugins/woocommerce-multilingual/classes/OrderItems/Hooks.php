<?php

namespace WCML\OrderItems;

use IWPML_Backend_Action;
use IWPML_DIC_Action;
use IWPML_Frontend_Action;
use SitePress;
use WCML\Rest\Functions;
use WCML\StandAlone\NullSitePress;
use WCML\Utilities\ActionScheduler;
use WPML\Core\ISitePress;

class Hooks implements IWPML_Backend_Action, IWPML_Frontend_Action, IWPML_DIC_Action {

	/** @var SitePress|NullSitePress $sitepress */
	private $sitepress;

	/** @var LineItem\Factory */
	private $lineItemFactory;

	/** @var Shipping\Factory */
	private $shippingFactory;

	/**
	 * @param SitePress|NullSitePress $sitepress
	 * @param LineItem\Factory        $lineItemFactory
	 * @param Shipping\Factory        $shippingFactory
	 */
	public function __construct(
		ISitePress $sitepress,
		LineItem\Factory $lineItemFactory,
		Shipping\Factory $shippingFactory
	) {
		$this->sitepress       = $sitepress;
		$this->lineItemFactory = $lineItemFactory;
		$this->shippingFactory = $shippingFactory;
	}

	public function add_hooks() {
		add_filter( 'woocommerce_order_get_items', [ $this, 'getOrderItems' ], 10, 2 );
	}

	/**
	 * @param \WC_Order_Item[] $items
	 * @param \WC_Order        $order
	 *
	 * @return \WC_Order_Item[]
	 */
	public function getOrderItems( $items, $order ) {
		if ( ! $items ) {
			return $items;
		}

		$shouldTranslateOrderItems = $this->shouldTranslateOrderItems();
		/**
		 * This filter hook allows to override if we need to translate order items.
		 *
		 * @since 4.11.0
		 *
		 * @param bool             $shouldTranslateOrderItems
		 * @param \WC_Order_Item[] $items
		 * @param \WC_Order        $order
		 */
		$shouldTranslateOrderItems = apply_filters( 'wcml_should_translate_order_items', $shouldTranslateOrderItems, $items, $order );
		if ( ! $shouldTranslateOrderItems ) {
			return $items;
		}

		$targetLanguage = $this->getTargetLanguage( $order );
		if ( ! $targetLanguage ) {
			return $items;
		}

		$this->translateOrderItems( $items, $targetLanguage );

		return $items;
	}

	/**
	 * Translate order items in some frontend pages, in all of the backend, and over REST API requests,
	 * except when running WooCommerce scheduled async actions.
	 *
	 * @return bool
	 */
	private function shouldTranslateOrderItems() {
		if ( ActionScheduler::isWcRunningFromAsyncActionScheduler() ) {
			return false;
		}
	
		if (
			is_admin() ||
			is_view_order_page() ||
			is_order_received_page() ||
			Functions::isRestApiRequest()
		) {
			return true;
		}

		return false;
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	private function getTargetLanguage( $order ) {
		if ( $this->isOrderEditPage() ) {
			$language = $this->sitepress->get_user_admin_language( get_current_user_id(), true );
		} elseif ( $this->isOrderActionTriggeredForCustomer() ) {
			$language = \WCML_Orders::getLanguage( $order->get_id() ) ?: $this->sitepress->get_default_language();
		} else {
			$language = $this->sitepress->get_current_language();
		}

		/**
		 * Override the target language to translate order items to.
		 *
		 * @since 4.11.0
		 *
		 * @param string|false|null $language Order item language.
		 * @param \WC_Order         $order
		 */
		return apply_filters( 'wcml_get_order_items_language', $language, $order );
	}

	/**
	 * @return bool
	 */
	private function isOrderEditPage() {
		// phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		return ( \WCML\COT\Helper::isOrderEditAdminScreen() && empty( $_POST ) ) || ( isset( $_GET['post'] ) && 'shop_order' === get_post_type( $_GET['post'] ) );
	}

	/**
	 * @return bool
	 */
	private function isOrderActionTriggeredForCustomer() {
		return isset( $_GET['action'] ) && wpml_collect(
			[ 'woocommerce_mark_order_complete', 'woocommerce_mark_order_status', 'mark_processing' ]
		)->contains( $_GET['action'] );
	}

	/**
	 * @param \WC_Order_Item[] $items
	 * @param string           $targetLanguage
	 */
	private function translateOrderItems( $items, $targetLanguage ) {

		if ( ! $targetLanguage ) {
			$targetLanguage = $this->sitepress->get_current_language();
		}

		foreach ( $items as $item ) {
			if ( $item instanceof \WC_Order_Item_Product ) {
				$this->translateLineItem( $item, $targetLanguage );
			} elseif ( $item instanceof \WC_Order_Item_Shipping ) {
				$this->translateShipping( $item, $targetLanguage );
			}
		}
	}

	/**
	 * @param \WC_Order_Item $item
	 * @param string         $targetLanguage
	 */
	private function translateLineItem( $item, $targetLanguage ) {
		$translator = $this->lineItemFactory->getTranslator( $item );
		if ( null === $translator ) {
			return;
		}
		$translator->translateItem( $item, $targetLanguage );
	}

	/**
	 * @param \WC_Order_Item $item
	 * @param string         $targetLanguage
	 */
	private function translateShipping( $item, $targetLanguage ) {
		$translator = $this->shippingFactory->getTranslator( $item );
		if ( null === $translator ) {
			return;
		}

		$translator->translateItem( $item, $targetLanguage );
	}

}
