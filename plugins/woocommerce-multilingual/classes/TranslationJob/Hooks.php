<?php

namespace WCML\TranslationJob;

use WPML\FP\Obj;
use WPML\FP\Fns;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks as WPHooks;
use WPML\TM\Jobs\FieldId;
use WCML\Utilities\WCTaxonomies;

use function WPML\FP\spreadArgs;

class Hooks implements \IWPML_Backend_Action, \IWPML_REST_Action {

	const TOP_LEVEL_GROUP       = 'wp_product';
	const TOP_LEVEL_GROUP_LABEL = 'WooCommerce Product';

	const LINKED_PRODUCTS_GROUP       = 'wp_product_linked_products';
	const LINKED_PRODUCTS_GROUP_LABEL = 'Linked Products';

	public function add_hooks() {
		WPHooks::onFilter( 'wpml_tm_adjust_translation_fields', 10, 2 )
			   ->then( spreadArgs( [ $this, 'adjustFields' ] ) );
		WPHooks::onFilter( 'wpml_tm_adjust_translation_job', 10, 2 )
			   ->then( spreadArgs( [ $this, 'adjustGlobalAttributes' ] ) );
	}

	/**
	 * @param array[]   $fields
	 * @param \stdClass $job
	 *
	 * @return array[]
	 */
	public function adjustFields( $fields, $job ) {
		if ( ! self::isProduct( $job ) ) {
			return $fields;
		}

		foreach ( $fields as &$field ) {
			$field = $this->adjustField( $field );
		}

		return $fields;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function adjustField( $field ) {
		$typeStartsWith = Str::startsWith( Fns::__, Obj::prop( 'field_type', $field ) );

		if ( $typeStartsWith( 'wc_attribute_name:' ) ) {
			$field = $this->handleAttribute( 'Name', $field );
		} elseif ( $typeStartsWith( 'wc_attribute_value:' ) ) {
			$field = $this->handleAttribute( 'Value', $field );
		} elseif ( $typeStartsWith( 'wc_variation_field:' ) ) {
			$field = $this->handleVariationField( $field );
		} elseif ( $typeStartsWith( \WCML_TP_Support::PACKAGE_IMAGE_KEY_PREFIX ) ) {
			$field = $this->handleImage( $field );
		} elseif ( $typeStartsWith( 'field-_downloadable_files-' ) ) {
			$field = $this->handleDownloadableFile( $field );
		}

		return $field;
	}

	/**
	 * @param string $title
	 * @param array  $field
	 *
	 * @return array
	 */
	private function handleAttribute( $title, $field ) {
		$parts = explode( ':', $field['field_type'] );
		$group = end( $parts ) . '-attribute';

		$field['title'] = $title;

		$field['group']           = self::getTopLevelGroup();
		$field['group'][ $group ] = apply_filters( 'wpml_labelize_string', $group, 'TranslationJob' );

		return $field;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function handleVariationField( $field ) {
		$parts = explode( ':', $field['field_type'] );
		array_shift( $parts );

		$field['title'] = implode( ' #', $parts );

		$field['group']                  = self::getTopLevelGroup();
		$field['group']['wc_variations'] = 'Variations Data';

		return $field;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function handleImage( $field ) {
		list( , $imageId, $title ) = Str::match( '/^' . preg_quote( \WCML_TP_Support::PACKAGE_IMAGE_KEY_PREFIX, '/' ) . '(\d+)-(.*)$/', $field['field_type'] );

		$field['title'] = $title;
		$field['image'] = wp_get_attachment_url( $imageId );

		$field['group']                           = self::getTopLevelGroup();
		$field['group'][ 'wc_image_' . $imageId ] = 'Image';

		return $field;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function handleDownloadableFile( $field ) {
		list( , $fileNo, $fileId, $title ) = \WCML_Downloadable_Products::parseDownloadableFileField( $field['field_type'] );

		$labelNo = self::convertDownloadableFileIdToNumber( $fileId );

		$ex = explode( ':', $title );
		if ( isset( $ex[1] ) ) {
			// Product Variant
			$title    = $ex[0];
			$labelNo .= ' [' . $ex[1] . ']';
		}

		$field['title'] = $title;
		$field['group'] = self::getTopLevelGroup();
		$field['group'][ sprintf( 'wc_downloadable_file_%s_%s', $fileNo, $fileId ) ] = sprintf( '#%s Downloadable File', $labelNo );

		return $field;
	}

	/**
	 * @param array[]   $fields
	 * @param \stdClass $job
	 *
	 * @return array
	 */
	public function adjustGlobalAttributes( $fields, $job ) {
		if ( ! self::isProduct( $job ) ) {
			return $fields;
		}

		$isGlobalAttribute = Str::startsWith( WCTaxonomies::TAXONOMY_PREFIX_ATTRIBUTE );

		foreach ( $fields as &$field ) {
			$title = Obj::path( [ 'attributes', 'resname' ], $field );
			$key   = Obj::path( [ 'attributes', 'id' ], $field );
			if ( $isGlobalAttribute( $title ) && FieldId::is_any_term_field( $key ) ) {
				$title       = apply_filters( 'wpml_labelize_string', substr( $title, 3 ), 'TranslationJob' );
				$group       = self::getTopLevelGroup();
				$group['pa'] = 'Attributes';

				$extradata = $field['extradata'] ?? [];

				$extradata['unit']     = $title;
				$extradata['group']    = implode( '/', array_values( $group ) );
				$extradata['group_id'] = implode( '/', array_keys( $group ) );

				$field['extradata'] = $extradata;
			}
		}

		return $fields;
	}

	/**
	 * @param \stdClass $job
	 *
	 * @return bool
	 */
	public static function isProduct( $job ) {
		return 'post_product' === $job->original_post_type;
	}

	/**
	 * @return string[]
	 */
	public static function getTopLevelGroup() {
		return [ self::TOP_LEVEL_GROUP => self::TOP_LEVEL_GROUP_LABEL ];
	}

	/**
	 * @param string $fileId
	 */
	private function convertDownloadableFileIdToNumber( $fileId ): int  {
		static $downloadableFileNo = [];

		if ( ! isset( $downloadableFileNo[ $fileId ] ) ) {
			$downloadableFileNo[ $fileId ] = count( $downloadableFileNo ) + 1;
		}

		return $downloadableFileNo[ $fileId ];
	}
}
