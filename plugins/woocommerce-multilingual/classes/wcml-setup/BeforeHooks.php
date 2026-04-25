<?php

namespace WCML\Setup;

use WPML\FP\Fns;

class BeforeHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var  \woocommerce_wpml */
	private $woocommerce_wpml;

	public function __construct( \woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function add_hooks() {
		$pendingWizard = ! $this->woocommerce_wpml->get_setting( 'set_up_wizard_run' );
		if ( $pendingWizard ) {
			add_filter( 'get_translatable_documents_all', [ __CLASS__, 'blockProductTranslation' ] );

			if ( $this->isStringTranslationActive() ) {
				add_filter( 'wpml_wizard_display_wcml_messages', Fns::always( true ) );
			}
		}
	}

	/**
	 * @param array $translatablePostTypes
	 *
	 * @return array
	 */
	public static function blockProductTranslation( $translatablePostTypes ) {
		unset( $translatablePostTypes['product'], $translatablePostTypes['product_variation'] );
		return $translatablePostTypes;
	}

	private function isStringTranslationActive(): bool {
		return defined( 'WPML_ST_VERSION' );
	}
}
