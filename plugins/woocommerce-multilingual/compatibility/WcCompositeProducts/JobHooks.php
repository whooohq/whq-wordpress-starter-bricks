<?php

namespace WCML\Compatibility\WcCompositeProducts;

use WCML\TranslationJob\Hooks;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks as WPHooks;

use function WPML\FP\spreadArgs;

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
		if ( Str::startsWith( \WCML_Composite_Products::FIELD_TYPE_PREFIX . ':', $field['field_type'] ) ) {
			$parts = explode( ':', $field['field_type'] );

			$field['title'] = apply_filters( 'wpml_labelize_string', end( $parts ), 'TranslationJob' );
			$field['group'] = Hooks::getTopLevelGroup();

			if ( 4 === count( $parts ) ) {
				$field['group']['wc_composite_scenarios'] = 'Composite Scenarios';
			} else {
				$field['group']['wc_composite_components'] = 'Composite Components';
			}
		}

		return $field;
	}

}
