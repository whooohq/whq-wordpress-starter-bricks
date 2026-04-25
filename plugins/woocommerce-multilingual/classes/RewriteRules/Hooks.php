<?php

namespace WCML\RewriteRules;

use WPML\Core\ISitePress;
use WCML\StandAlone\NullSitePress;
use SitePress;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var SitePress|NullSitePress $sitepress */
	private $sitepress;

	/**
	 * @param SitePress|NullSitePress $sitepress
	 */
	public function __construct( ISitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'pre_option_woocommerce_queue_flush_rewrite_rules', [ $this, 'preventFlushInNonDefaultLang' ] );
	}

	/**
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function preventFlushInNonDefaultLang( $value ) {
		if (
			$this->sitepress->get_current_language() !== $this->sitepress->get_default_language()
		) {
			return 'no';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['preview_woocommerce_mail'] ) && 'true' === $_REQUEST['preview_woocommerce_mail'] ) {
			return 'no';
		}
		if ( ! is_admin() ) {
			return 'no';
		}

		return $value;
	}
}
