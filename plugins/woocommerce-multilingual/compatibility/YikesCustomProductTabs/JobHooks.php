<?php

namespace WCML\Compatibility\YikesCustomProductTabs;

use WCML\TranslationJob\Hooks;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks as WPHooks;

use function WPML\FP\spreadArgs;

/**
 * IMPORTANT NOTICE !!!
 * This target plugin is not maintained anymore.
 * We are stopping our compatibility maintenance too.
 *
 * @deprecated
 */
class JobHooks {

	public function add_hooks() {
		WPHooks::onFilter( 'wpml_tm_adjust_translation_fields', 10, 2 )
			->then( spreadArgs( [ $this, 'setGroupsAndLabels' ] ) );
	}

	/**
	 * @param array[]   $fields
	 * @param \stdClass $job
	 *
	 * @return array[]
	 */
	public function setGroupsAndLabels( $fields, $job ) {
		if ( ! Hooks::isProduct( $job ) ) {
			return $fields;
		}

		foreach ( $fields as $key => $field ) {
			$fields[ $key ] = $this->processField( $field );
		}

		return $fields;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function processField( $field ) {
		if ( Str::startsWith( \WCML_YIKES_Custom_Product_Tabs::CUSTOM_TABS_FIELD . ':', $field['field_type'] ) ) {
			$parts = explode( ':', $field['field_type'] );

			$field['title'] = apply_filters( 'wpml_labelize_string', end( $parts ), 'TranslationJob' );

			$field['group']                      = Hooks::getTopLevelGroup();
			$field['group']['yikes_custom_tabs'] = 'Custom Tabs';
		}

		return $field;
	}

}
