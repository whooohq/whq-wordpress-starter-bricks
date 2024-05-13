<?php

namespace WCML\Multicurrency\Shipping;

class UnsupportedShipping implements ShippingMode {

	public function getMethodId() {
		return null;
	}

	public function getFieldTitle( $currencyCode ) {
		return null;
	}

	public function getFieldDescription( $currencyCode ) {
		return null;
	}

	public function getSettingsFormKey( $currencyCode ) {
		return null;
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return $amount;
	}

	public function isManualPricingEnabled( $instance = false ) {
		return false;
	}

	public function getMinimalOrderAmountKey( $currencyCode ) {
		// TODO: Implement getMinAmountKey() method.
	}

	public function getShippingCostValue( $rate, $currency ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		return $rate->cost;
	}
}
