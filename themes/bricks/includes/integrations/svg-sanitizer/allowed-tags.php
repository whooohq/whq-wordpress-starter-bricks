<?php
namespace Bricks\Integrations\Svg_Sanitizer;

class Allowed_Tags extends \enshrined\svgSanitize\data\AllowedTags {

	/**
	 * Returns an array of tags
	 *
	 * @return array
	 */
	public static function getTags() {
		/**
		 * NOTE: Undocumented
		 */
		return apply_filters( 'bricks/svg/allowed_tags', parent::getTags() );
	}
}
