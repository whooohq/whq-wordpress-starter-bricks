<?php

namespace WCML\Compatibility\WcCheckoutAddons;

class OptionIterator {

	/**
	 * @param callable    $handler
	 * @param array|mixed $optionValue
	 *
	 * @return array|mixed
	 */
	public static function apply( callable $handler, $optionValue ) {
		if ( is_array( $optionValue ) ) {

			foreach ( $optionValue as $checkoutAddOnId => $addonConf ) {
				$checkoutAddOnName = $addonConf['name'] ?? '';
				$addonConf         = $handler( $checkoutAddOnId, $addonConf, $checkoutAddOnName, $checkoutAddOnId );
				if ( isset( $addonConf['options'] ) ) {
					foreach ( $addonConf['options'] as $index => $fields ) {
						$addonConf['options'][ $index ] = $handler( $index, $fields, $checkoutAddOnName, $checkoutAddOnId );
					}
				}

				$optionValue[ $checkoutAddOnId ] = $addonConf;
			}
		}

		return $optionValue;
	}
}
