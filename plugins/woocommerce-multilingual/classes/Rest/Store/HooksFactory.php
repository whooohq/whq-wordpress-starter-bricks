<?php

namespace WCML\Rest\Store;

use WCML\Rest\Functions;
use WCML\StandAlone\IStandAloneAction;
use function WCML\functions\isStandAlone;

class HooksFactory implements \IWPML_REST_Action_Loader, IStandAloneAction {

	/**
	 * @return \IWPML_Action[]
	 */
	public function create() {
		global $woocommerce_wpml;

		$hooks = [];

		if ( Functions::isStoreAPIRequest() ) {

			if ( ! isStandAlone() ) {
				$hooks[] = new ReviewsHooks();
			}

			if ( wcml_is_multi_currency_on() ) {
				$hooks[] = new MulticurrencyHooks();
				$hooks[] = new PriceRangeHooks( $woocommerce_wpml );
			}
		}

		return $hooks;
	}
}
