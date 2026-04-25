<?php

namespace WCML\Compatibility\WcProductAddons;

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
		if ( Str::startsWith( \WCML_Product_Addons::ADDON_PREFIX, $field['field_type'] ) ) {
			$parts = explode( '_', $field['field_type'] );

			if ( count( $parts ) > 3 ) {
				$title = 'Option ' . end( $parts );
			} else {
				$title = end( $parts );
			}

			$field['title'] = apply_filters( 'wpml_labelize_string', $title, 'TranslationJob' );

			$field['group']                      = Hooks::getTopLevelGroup();
			$field['group']['wc_product_addons'] = 'Add-ons';
		}

		return $field;
	}

}
