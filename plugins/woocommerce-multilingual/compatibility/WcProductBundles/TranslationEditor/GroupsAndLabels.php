<?php

namespace WCML\Compatibility\WcProductBundles\TranslationEditor;

use WCML_Product_Bundles;
use WCML\TranslationJob\Hooks as TranslationJobHooks;
use WPML\FP\Obj;
use WPML\FP\Str;

class GroupsAndLabels implements \IWPML_Action {

	const BUNDLE_ITEM_GROUP_SUFFIX = '/bundled-product';
	const BUNDLE_ITEM_GROUP_LABEL  = 'Bundled Product';
	const BUNDLE_SELLS_FIELD_TITLE = 'Bundle-sells Title';

	/**
	 * Adds hooks.
	 */
	public function add_hooks() {
		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'adjustFields' ] );
	}

	/**
	 * @param array[] $fields
	 *
	 * @return array[]
	 */
	public function adjustFields( $fields ) {
		foreach ( $fields as &$field ) {
			if ( WCML_Product_Bundles::META_SELLS_TITLE === Obj::prop( 'field_type', $field ) ) {
				$field = $this->adjustBundleSellsField( $field );
			} elseif ( Str::startsWith( WCML_Product_Bundles::BUNDLE_FIELD_PREFIX, Obj::prop( 'field_type', $field ) ) ) {
				$field = $this->adjustBundleField( $field );
			}
		}

		return $fields;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function adjustBundleSellsField( $field ) {
		$field['title'] = self::BUNDLE_SELLS_FIELD_TITLE;
		$field['group'] = TranslationJobHooks::getTopLevelGroup();
		$field['group'][ TranslationJobHooks::LINKED_PRODUCTS_GROUP ] = TranslationJobHooks::LINKED_PRODUCTS_GROUP_LABEL;
		return $field;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function adjustBundleField( $field ) {
		$fieldData  = Str::replace( WCML_Product_Bundles::BUNDLE_FIELD_PREFIX, '', $field['field_type'] );
		$fieldParts = explode( ':', $fieldData );
		$title      = array_pop( $fieldParts );
		$group      = implode( ':', $fieldParts ) . self::BUNDLE_ITEM_GROUP_SUFFIX;

		$field['title']           = apply_filters( 'wpml_labelize_string', $title, 'TranslationJob' );
		$field['group']           = TranslationJobHooks::getTopLevelGroup();
		$field['group'][ $group ] = self::BUNDLE_ITEM_GROUP_LABEL;

		return $field;
	}

}
