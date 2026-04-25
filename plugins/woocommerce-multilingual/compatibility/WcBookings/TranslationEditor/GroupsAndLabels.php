<?php

namespace WCML\Compatibility\WcBookings\TranslationEditor;

use WCML_Bookings;
use WCML\Compatibility\WcBookings\SharedHooks;
use WCML\TranslationJob\Hooks as TranslationJobHooks;
use WPML\FP\Obj;
use WPML\FP\Fns;
use WPML\FP\Str;

class GroupsAndLabels implements \IWPML_Action {

	const PERSON_GROUP_SUFFIX   = '/person';
	const PERSON_GROUP_LABEL    = 'Person';
	const RESOURCE_GROUP_SUFFIX = '/resource';
	const RESOURCE_GROUP_LABEL  = 'Resource';

	/**
	 * Adds hooks.
	 */
	public function add_hooks() {
		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'adjustFields' ], 10, 2 );
	}

	/**
	 * @param array[]   $fields
	 * @param \stdClass $job
	 *
	 * @return array[]
	 */
	public function adjustFields( $fields, $job ) {
		if ( ! SharedHooks::isBooking( $job->original_doc_id ) ) {
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

		$getGroup = function( $item, $prefix ) {
			switch ( $prefix ) {
				case WCML_Bookings::PERSON_FIELD_PREFIX:
					return $item . self::PERSON_GROUP_SUFFIX;
				case WCML_Bookings::RESOURCE_FIELD_PREFIX:
					return $item . self::RESOURCE_GROUP_SUFFIX;
			}
			return $item;
		};

		$getGroupLabel = function( $prefix, $default ) {
			switch ( $prefix ) {
				case WCML_Bookings::PERSON_FIELD_PREFIX:
					return self::PERSON_GROUP_LABEL;
				case WCML_Bookings::RESOURCE_FIELD_PREFIX:
					return self::RESOURCE_GROUP_LABEL;
			}
			return $default;
		};

		$addTitlesAndGroups = function( $item, $prefix ) use ( $getGroup, $getGroupLabel ) {
			$fieldData  = Str::replace( $prefix, '', $item['field_type'] );
			$fieldParts = explode( ':', $fieldData );
			$title      = end( $fieldParts );
			$group      = reset( $fieldParts );
			$group      = $getGroup( $group, $prefix );

			$item['title']           = apply_filters( 'wpml_labelize_string', $title, 'TranslationJob' );
			$item['group']           = TranslationJobHooks::getTopLevelGroup();
			$item['group'][ $group ] = $getGroupLabel( $prefix, $group );

			return $item;
		};

		if ( $typeStartsWith( WCML_Bookings::PERSON_FIELD_PREFIX ) ) {
			$field = $addTitlesAndGroups( $field, WCML_Bookings::PERSON_FIELD_PREFIX );
		} elseif ( $typeStartsWith( WCML_Bookings::RESOURCE_FIELD_PREFIX ) ) {
			$field = $addTitlesAndGroups( $field, WCML_Bookings::RESOURCE_FIELD_PREFIX );
		}

		return $field;
	}

}
