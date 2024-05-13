<?php
namespace Bricks\Integrations\Svg_Sanitizer;

class Allowed_Attributes extends \enshrined\svgSanitize\data\AllowedAttributes {

	/**
	 * Returns an array of attributes
	 *
	 * @return array
	 */
	public static function getAttributes() {
		/**
		 * NOTE: Undocumented
		 */
		return apply_filters( 'bricks/svg/allowed_attributes', parent::getAttributes() );
	}
}
