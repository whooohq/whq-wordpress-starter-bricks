<?php

namespace WCML\Permalinks;

class Strings {

	const DEFAULT_PRODUCT_BASE           = 'product';
	const DEFAULT_PRODUCT_CATEGORY_BASE  = 'product-category';
	const DEFAULT_PRODUCT_TAG_BASE       = 'product-tag';
	const DEFAULT_PRODUCT_ATTRIBUTE_BASE = '';

	const TRANSLATION_DOMAIN = 'WordPress';

	/**
	 * @param string $baseType
	 * @param string $attributeSlug
	 *
	 * @return string
	 */
	public static function getStringName( $baseType, $attributeSlug = '' ) {
		switch ( $baseType ) {
			case 'product':
				return sprintf( 'URL slug: %s', $baseType );
			case 'product_cat':
			case 'product_tag':
			case 'attribute':
				return sprintf( 'URL %s tax slug', $baseType );
			case 'attribute_slug':
				return sprintf( 'URL attribute slug: %s', $attributeSlug );
		}

		return '';
	}
}
