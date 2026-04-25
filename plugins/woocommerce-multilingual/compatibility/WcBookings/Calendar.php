<?php

namespace WCML\Compatibility\WcBookings;

use WPML\FP\Fns;
use WPML\LIB\WP\Hooks;

use function WPML\FP\spreadArgs;

class Calendar implements \IWPML_Action {

	public function add_hooks() {
		Hooks::onFilter( 'woocommerce_bookings_gcalendar_sync' )
			->then( spreadArgs( Fns::tap( [ $this, 'deactivateAfterSync' ] ) ) );
	}

	public function deactivateAfterSync() {
		Hooks::onFilter( 'option_wc_bookings_gcalendar_refresh_token' )
			->then( Fns::always( false ) );
	}

}
