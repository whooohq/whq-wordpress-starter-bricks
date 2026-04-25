<?php

namespace WCML\Rest\Store;

class MulticurrencyHooks implements \IWPML_Action {

	const BEFORE_REST_API_LOADED = 0;

	public function add_hooks() {
		add_action( 'init', [ $this, 'initializeSession' ], self::BEFORE_REST_API_LOADED );
	}

	public function initializeSession() {
		if ( ! ( WC()->session instanceof \WC_Session ) ) {
			WC()->initialize_session();
		}
	}
}
