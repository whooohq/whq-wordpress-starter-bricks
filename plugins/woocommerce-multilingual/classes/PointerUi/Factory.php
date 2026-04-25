<?php

namespace WCML\PointerUi;

use WPML\FP\Obj;
use WCML_Pointer_UI;

class Factory {

	/**
	 * @param array $args
	 *
	 * @return WCML_Pointer_UI
	 */
	public static function create( $args ) {
		$defaultArgs = [
			'anchor'     => __( 'How to translate this?', 'woocommerce-multilingual' ),
			'content'    => '',
			'docLink'    => null,
			'selectorId' => null,
			'method'     => 'after',
		];
		$args        = wp_parse_args( $args, $defaultArgs );

		return new WCML_Pointer_UI(
			Obj::prop( 'content', $args ),
			Obj::prop( 'docLink', $args ),
			Obj::prop( 'selectorId', $args ),
			Obj::prop( 'method', $args ),
			Obj::prop( 'anchor', $args )
		);
	}

}
