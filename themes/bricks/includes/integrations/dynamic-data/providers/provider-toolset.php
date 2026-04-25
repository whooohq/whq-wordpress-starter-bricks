<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Toolset extends Base {
	public static function load_me() {
		return function_exists( 'wpcf_admin_fields_get_groups' );
	}

	public function register_tags() {
		$fields   = self::get_fields();
		$supports = self::get_supported_field_types();

		foreach ( $fields as $field ) {
			$type = $field['type'];

			if ( ! in_array( $type, $supports ) ) {
				continue;
			}

			$name = 'ts_' . $field['slug'];

			$this->tags[ $name ] = [
				'name'     => '{' . $name . '}',
				'label'    => $field['name'],
				'group'    => $field['group_name'],
				'field'    => $field,
				'provider' => $this->name,
			];
		}
	}

	public static function get_fields() {
		$fields = [];

		$groups = wpcf_admin_fields_get_groups();

		foreach ( $groups as $group ) {
			$group_fields = wpcf_admin_fields_get_fields_by_group( $group['id'] );

			if ( ! is_array( $group_fields ) ) {
				continue;
			}

			foreach ( $group_fields as $key => $field ) {
				// Skip if $field is not an array, Example: '_repeatable_group_451' (@since 1.8.2)
				if ( ! is_array( $field ) ) {
					continue;
				}

				$field['group_name'] = $group['name'];
				$fields[ $key ]      = $field;
			}
		}

		return $fields;
	}

	public function get_tag_value( $tag, $post, $args, $context ) {
		$post_id = isset( $post->ID ) ? $post->ID : '';
		$field   = $this->tags[ $tag ]['field'];

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// STEP: Get the value
		if ( in_array( $field['type'], [ 'checkboxes', 'checkbox', 'radio', 'select', 'wysiwyg' ] ) ) {
			$value = types_render_field( $field['id'], [ 'post_id' => $post_id ] );
		} else {
			$value = types_render_field(
				$field['id'],
				[
					'output'    => 'raw',
					'separator' => '%BriCkS$',
					'post_id'   => $post_id
				]
			);

			$value = explode( '%BriCkS$', $value );
		}

		$filters['separator'] = '<br>';

		switch ( $field['type'] ) {
			case 'textarea':
				$value = array_map( 'nl2br', $value );
				// $filters['separator'] = '<br>';
				break;

			case 'image':
				$filters['object_type'] = 'media';
				$filters['separator']   = '';

				$value = array_map( 'attachment_url_to_postid', $value );
				break;

			case 'audio':
				$filters['object_type'] = 'media';
				$value                  = array_map( 'attachment_url_to_postid', $value );
				break;

			case 'embed':
				if ( $context === 'text' ) {
					$filters['separator']     = '';
					$filters['skip_sanitize'] = true;

					foreach ( $value as $key => $item ) {
						$value[ $key ] = wp_oembed_get( esc_url( $item ) );
					}
				}
				break;

			case 'file':
			case 'video':
				$filters['object_type'] = 'media';
				$filters['link']        = true;
				$value                  = array_map( 'attachment_url_to_postid', $value );
				break;

			case 'wysiwyg':
				$value = \Bricks\Helpers::parse_editor_content( $value );
				break;

			case 'date':
				$filters['object_type'] = isset( $field['data']['date_and_time'] ) && 'and_time' == $field['data']['date_and_time'] ? 'datetime' : 'date';
				break;

			case 'post':
				$filters['object_type'] = 'post';
				$filters['link']        = true;
				break;

		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	/**
	 * Get all fields supported
	 *
	 * @return array
	 */
	private static function get_supported_field_types() {
		return [
			'textfield',
			'textarea',
			'numeric',
			'date',
			'phone',
			'email',
			'url',
			'colorpicker',
			'skype',

			'image',
			'file',
			'video',
			'audio',

			'post',

			'checkbox',
			'checkboxes',
			'select',
			'radio',

			'embed',
			'google_address',
			'wysiwyg',
		];
	}
}
