<?php

use WPML\FP\Obj;

class WCML_Setup_Handlers {

	/** @var  woocommerce_wpml */
	private $woocommerce_wpml;

	public function __construct( woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function save_attributes( array $data ) {

		if ( isset( $data['attributes'] ) ) {
			$this->woocommerce_wpml->attributes->set_translatable_attributes( $data['attributes'] );
		}

	}

	public function save_multi_currency( array $data ) {

		$this->woocommerce_wpml->get_multi_currency();

		if ( Obj::prop( WCML_Setup::MULTI_CURRENCY_STATUS_GET_KEY, $data ) ) {
			$this->woocommerce_wpml->multi_currency->enable();
		} else {
			$this->woocommerce_wpml->multi_currency->disable();
		}
	}

	public function install_store_pages( array $data ) {

		if ( ! empty( $data['install_missing_pages'] ) ) {
			WC_Install::create_pages();
		}

		if ( ! empty( $data['install_missing_pages'] ) || ! empty( $data['create_pages'] ) ) {
			$this->woocommerce_wpml->store->create_missing_store_pages_with_redirect();
		}

	}
}
