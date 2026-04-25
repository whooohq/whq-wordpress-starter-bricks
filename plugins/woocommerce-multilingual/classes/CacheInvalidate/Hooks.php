<?php

namespace WCML\CacheInvalidate;

use WPML\LIB\WP\Hooks as WpHooks;
use function WPML\FP\spreadArgs;

class Hooks implements \IWPML_Backend_Action, \IWPML_REST_Action, \IWPML_DIC_Action {
	/** @var \woocommerce_wpml */
	private $woocommerce_wpml;

	/**
	 * @param \woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( \woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function add_hooks() {
		WpHooks::onAction( 'wpml_tm_ate_jobs_downloaded', 10, 0 )
		       ->then( spreadArgs( [ $this, 'invalidateUntranslatedTaxonomyTerms' ] ) );
	}

	/**
	 * the class (@see \WCML_Terms) operating on this data checks if it already has "saved/available"
	 * - if it doesn't have them and needs them, it will rebuild them
	 */
	public function invalidateUntranslatedTaxonomyTerms() {
		$wcml_settings = $this->woocommerce_wpml->get_settings();

		$wcml_settings['untranstaled_terms'] = [];

		$this->woocommerce_wpml->update_settings( $wcml_settings );
	}
}