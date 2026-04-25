<?php

use WCML\Compatibility\WcCheckoutAddons\OptionIterator;

/**
 * Compatibility class for  wc_checkout_addons plugin.
 *
 * @author konrad
 */
class WCML_Checkout_Addons implements \IWPML_Action {

	const PACKAGE_KIND = 'WooCommerce Checkout Add-On';
	const PACKAGE_KIND_SLUG = 'woocommerce-checkout-add-on';
	const PACKAGE_NAME = 'wc-checkout-woocommerce-addons-%s';
	const PACKAGE_TITLE = 'WooCommerce Checkout Add-On: %s';

	/** @var array */
	private $packages;

	/**
	 * @param string $checkoutAddOnId
	 * @param string $checkoutAddOnName
	 */
	public function createPackage( $checkoutAddOnId, $checkoutAddOnName ): stdClass {
		if ( ! isset( $this->packages[ $checkoutAddOnId ][ $checkoutAddOnName ] ) ) {
			return $this->packages[ $checkoutAddOnId ][ $checkoutAddOnName ] = (object) [
				'kind'      => self::PACKAGE_KIND,
				'kind_slug' => self::PACKAGE_KIND_SLUG,
				'name'      => sprintf( self::PACKAGE_NAME, $checkoutAddOnId ),
				'title'     => sprintf( self::PACKAGE_TITLE, $checkoutAddOnName ),
			];
		}

		return $this->packages[ $checkoutAddOnId ][ $checkoutAddOnName ];
	}

	public function add_hooks() {
		add_filter( 'option_wc_checkout_add_ons', [ $this, 'option_wc_checkout_add_ons' ] );
	}

	/**
	 * @param array|mixed $option_value
	 *
	 * @return array|mixed
	 */
	public function option_wc_checkout_add_ons( $option_value ) {
		return OptionIterator::apply( [ $this, 'handle_option_part' ], $option_value );
	}

	public function handle_option_part( $index, $conf, $checkoutAddOnName, $checkoutAddOnId ) {
		$conf = $this->register_or_translate( 'label', $conf, $index, $checkoutAddOnName, $checkoutAddOnId );
		$conf = $this->register_or_translate( 'description', $conf, $index, $checkoutAddOnName, $checkoutAddOnId );

		return $conf;
	}

	private function register_or_translate( $element, $conf, $index, $checkoutAddOnName, $checkoutAddOnId ) {
		if ( isset( $conf[ $element ] ) ) {
			$package = $this->createPackage( $checkoutAddOnId, $checkoutAddOnName );
			$string  = $conf[ $element ];
			$key     = sprintf( '%s_%s', $index, $element );
			if ( $this->is_default_language() ) {
				do_action(
					'wpml_register_string',
					$string,
					$key,
					$package,
					sprintf( '%s %s', $string, ucfirst( $element ) ),
					$package->kind
				);

			} else {
				$conf[ $element ] = $this->translate( $string, $key, $package );
			}
		}

		return $conf;
	}

	private function translate( $text, $key, $package ) {
		$translation = apply_filters(
			'wpml_translate_string',
			$text,
			$key,
			$package
		);

		if ( $text === $translation ) {
			$key .= '_' . md5( $text );

			$translation = apply_filters(
				'wpml_translate_single_string',
				$text,
				'wc_checkout_addons',
				$key
			);
		}

		return $translation;
	}

	private function is_default_language() {
		return apply_filters( 'wpml_current_language', null ) === apply_filters( 'wpml_default_language', null );
	}
}
