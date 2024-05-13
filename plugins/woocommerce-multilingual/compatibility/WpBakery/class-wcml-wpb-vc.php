<?php
/*
 * WPBakery Page Builder ( formerly Visual Composer ) Compatibility class
 */

class WCML_Wpb_Vc implements \IWPML_Action {

	public function add_hooks() {

		add_filter( 'wcml_is_localize_woocommerce_on_ajax', [ $this, 'is_localize_woocommerce_on_ajax' ], 10, 2 );
	}

	public function is_localize_woocommerce_on_ajax( $localize, $action ) {

		if ( 'vc_edit_form' === $action ) {
			$localize = false;
		}

		return $localize;
	}

}
