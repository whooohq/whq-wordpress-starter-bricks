<?php

namespace WCML\Utilities;

class WCTaxonomies {

	const TAXONOMY_PREFIX_ATTRIBUTE = 'pa_';
	const TAXONOMY_PRODUCT_CATEGORY = 'product_cat';
	const TAXONOMY_PRODUCT_TAG = 'product_tag';

	/**
	 * @param string $taxonomy
	 */
	public static function isProductAttribute( $taxonomy ) : bool {
		return substr( $taxonomy, 0, 3 ) === self::TAXONOMY_PREFIX_ATTRIBUTE;
	}

	/**
	 * @param string $taxonomy
	 */
	public static function isProductCategory( $taxonomy ) : bool {
		return self::TAXONOMY_PRODUCT_CATEGORY === $taxonomy;
	}

	/**
	 * @param string $taxonomy
	 */
	public static function isProductTag( $taxonomy ) : bool {
		return self::TAXONOMY_PRODUCT_TAG === $taxonomy;
	}	

	/**
	 * @param string $taxonomy
	 */
	public static function isProductCategoryOrAttribute( $taxonomy ) : bool {
		return self::isProductAttribute( $taxonomy ) || self::isProductCategory( $taxonomy );
	}
}
