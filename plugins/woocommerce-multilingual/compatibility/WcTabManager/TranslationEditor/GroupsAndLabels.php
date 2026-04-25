<?php

namespace WCML\Compatibility\WcTabManager\TranslationEditor;

use WCML_Tab_Manager;
use WCML\TranslationJob\Hooks as TranslationJobHooks;
use WPML\FP\Obj;
use WPML\FP\Str;

class GroupsAndLabels implements \IWPML_Action {

	const TAB_GROUP_SLUG  = 'product-tab';
	const TAB_GROUP_LABEL = 'Product Tab';

	const DEFAULT_FIELD_LABEL = 'Field';
	const TAB_CONTENT_LABEL   = 'Content';

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
			if ( Str::startsWith( WCML_Tab_Manager::TAB_FIELD_PREFIX, Obj::prop( 'field_type', $field ) ) ) {
				$field = $this->adjustField( $field );
			}
		}

		return $fields;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function adjustField( $field ) {
		$fieldData = Str::replace( WCML_Tab_Manager::TAB_FIELD_PREFIX, '', $field['field_type'] );

		if ( Str::startsWith( WCML_Tab_Manager::TAB_FIELD_CORE_INTERFIX, $fieldData ) ) {
			$field = $this->adjustCoreTabField( $field );
		} elseif ( Str::startsWith( WCML_Tab_Manager::TAB_FIELD_PRODUCT_INTERFIX, $fieldData ) ) {
			$field = $this->adjustProductTabField( $field );
		}

		return $field;
	}

	/**
	 * Those fields follow a structure of product_tabs:core_tab_{fieldName}:{tabId}
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function adjustCoreTabField( $field ) {
		$fieldData  = Str::replace( WCML_Tab_Manager::TAB_FIELD_PREFIX . WCML_Tab_Manager::TAB_FIELD_CORE_INTERFIX, '', $field['field_type'] );
		$fieldParts = explode( ':', $fieldData );
		$name       = reset( $fieldParts );
		$tabId      = count( $fieldParts ) > 1 ? end( $fieldParts ) : false;

		return $this->adjustTabField( $field, $name, $tabId );
	}

	/**
	 * Those fields follow a structure of product_tabs:product_tab:{tabId}:{fieldName}
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function adjustProductTabField( $field ) {
		$fieldData  = Str::replace( WCML_Tab_Manager::TAB_FIELD_PREFIX . WCML_Tab_Manager::TAB_FIELD_PRODUCT_INTERFIX, '', $field['field_type'] );
		$fieldParts = explode( ':', $fieldData );
		$name       = count( $fieldParts ) > 1 ? end( $fieldParts ) : false;
		$tabId      = reset( $fieldParts );

		return $this->adjustTabField( $field, $name, $tabId );
	}

	/**
	 * @param array        $field
	 * @param string|false $name
	 * @param string|false $tabId
	 *
	 * @return array
	 */
	private function adjustTabField( $field, $name, $tabId ) {
		$getTitle = function( $slug ) {
			if ( empty( $slug ) ) {
				return self::DEFAULT_FIELD_LABEL;
			}
			if ( 'description' === $slug ) {
				return self::TAB_CONTENT_LABEL;
			}
			return apply_filters( 'wpml_labelize_string', $slug, 'TranslationJob' );
		};

		$getGroup = function( $id ) {
			if ( empty( $id ) ) {
				return self::TAB_GROUP_SLUG . '-0';
			}
			return self::TAB_GROUP_SLUG . '-' . $id;
		};

		$group = $getGroup( $tabId );

		$field['title']           = $getTitle( $name );
		$field['group']           = TranslationJobHooks::getTopLevelGroup();
		$field['group'][ $group ] = self::TAB_GROUP_LABEL;

		return $field;
	}

}
