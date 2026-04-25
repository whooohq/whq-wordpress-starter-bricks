<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Cmb2 extends Base {
	public static function load_me() {
		return class_exists( 'CMB2' );
	}

	public function register_tags() {
		if ( ! is_admin() ) {
			// We need to load the user metaboxes and fields on frontend to know about them
			do_action( 'cmb2_admin_init' );
		}

		$fields = self::get_fields();

		$contexts = self::get_fields_by_context();

		foreach ( $fields as $field ) {
			$type = $field['type'];

			if ( ! isset( $contexts[ $type ] ) ) {
				continue;
			}

			foreach ( $contexts[ $type ] as $context ) {
				$key = 'cmb2_' . $field['name'];

				$name = self::CONTEXT_TEXT === $context ? $key : $key . '_' . $context;

				$this->tags[ $name ] = [
					'name'     => '{' . $name . '}',
					'label'    => $field['label'],
					'group'    => $field['group'],
					'field'    => $field,
					'provider' => $this->name,
				];

				if ( self::CONTEXT_TEXT !== $context ) {
					$this->tags[ $name ]['deprecated'] = 1;
				}
			}
		}
	}

	public static function get_fields() {
		$metaboxes = \CMB2_Boxes::get_all();

		$cmb2_fields = [];

		foreach ( $metaboxes as $metabox_id => $metabox ) {

			$fields = $metabox->prop( 'fields' );

			$cmb2_fields = self::add_fields_to_list( $cmb2_fields, $fields );

		}

		return $cmb2_fields;
	}

	/**
	 * Helper function to map raw field object to internal format
	 *
	 * @param array $list Final list.
	 * @param array $fields raw.
	 *
	 * @return array
	 */
	public static function add_fields_to_list( $list = [], $fields = [] ) {
		if ( empty( $fields ) ) {
			return $list;
		}

		foreach ( $fields as $field ) {
			if ( ! isset( $field['type'] ) ) {
				continue;
			}

			$new = [
				'label' => isset( $field['name'] ) ? $field['name'] : $field['id'],
				'name'  => $field['id'],
				'type'  => $field['type'],
				'group' => 'CMB2',
			];

			if ( isset( $field['options'] ) ) {
				$new['options'] = $field['options'];
			}

			if ( isset( $field['repeatable'] ) ) {
				$new['repeatable'] = $field['repeatable'];
			}

			if ( isset( $field['taxonomy'] ) ) {
				$new['taxonomy'] = $field['taxonomy'];
			}

			$list[] = $new;
		}

		return $list;
	}

	public function get_tag_value( $tag, $post, $args, $context ) {
		$post_id = isset( $post->ID ) ? $post->ID : '';

		$field = $this->tags[ $tag ]['field'];

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// STEP: Get the value
		$value = 'file' == $field['type'] ? get_post_meta( $post_id, $field['name'] . '_id', true ) : get_post_meta( $post_id, $field['name'], true );

		$value = ! is_array( $value ) ? [ $value ] : $value;

		// Legacy from previous code (before Bricks 1.3.5)
		$filters['separator'] = '<br>';

		switch ( $field['type'] ) {
			case 'file_list':
				$filters['object_type'] = 'media';
				$filters['link']        = true;
				$value                  = ! empty( $value ) && is_array( $value ) ? array_keys( $value ) : [];
				break;

			case 'file':
				$filters['object_type'] = 'media';
				if ( empty( $filters['image'] ) ) {
					$filters['link'] = true;
				}
				break;

			case 'textarea':
			case 'textarea_small':
				$value = array_map( 'nl2br', $value );
				break;

			case 'textarea_code':
				$theme_styles = \Bricks\Theme_Styles::$active_settings;
				$classes      = isset( $theme_styles['code']['prettify'] ) ? 'prettyprint ' . $theme_styles['code']['prettify'] : '';

				foreach ( $value as $key => $item ) {
					$value[ $key ] = '<pre class="' . $classes . '"><code>' . $item . '</code></pre>';
				}
				break;

			case 'text_date_timestamp':
			case 'text_datetime_timestamp':
			case 'text_datetime_timestamp_timezone':
				$filters['object_type'] = 'date';

				if ( ! isset( $filters['meta_key'] ) && strpos( $field['type'], 'datetime' ) ) {
					$filters['meta_key'] = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				}
				break;

			case 'radio':
			case 'radio_inline':
			case 'select':
			case 'multicheck':
			case 'multicheck_inline':
				foreach ( $value as $key => $item ) {
					$item          = (array) $item;
					$item          = array_intersect_key( $field['options'], array_fill_keys( $item, '' ) );
					$value[ $key ] = implode( ', ', $item );
				}
				break;

			case 'checkbox':
				foreach ( $value as $key => $item ) {
					$original_value = $item;
					$item           = $original_value == 'on' ? esc_html__( 'Yes', 'bricks' ) : esc_html__( 'No', 'bricks' );

					/**
					 * NOTE: Undocumented
					 */
					$value[ $key ] = apply_filters( 'bricks/cmb2/checkbox_value', $item, $original_value, $field, $post );
				}
				break;

			case 'wysiwyg':
				$filters['separator'] = ' ';

				foreach ( $value as $key => $item ) {
					$value[ $key ] = \Bricks\Helpers::parse_editor_content( $item );
				}
				break;

			case 'oembed':
				if ( $context === 'text' ) {
					$filters['separator']     = '';
					$filters['skip_sanitize'] = true;

					foreach ( $value as $key => $item ) {
						$value[ $key ] = wp_oembed_get( esc_url( $item ) );
					}
				}
				break;
		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	/**
	 * Get all fields supported and their contexts
	 *
	 * @return array
	 */
	private static function get_fields_by_context() {
		$fields = [
			// Note: Taxonomy fields are not saved as custom fields
			'text'                             => [ self::CONTEXT_TEXT ],
			'text_small'                       => [ self::CONTEXT_TEXT ],
			'text_medium'                      => [ self::CONTEXT_TEXT ],
			'text_email'                       => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'text_url'                         => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'text_money'                       => [ self::CONTEXT_TEXT ],
			'textarea'                         => [ self::CONTEXT_TEXT ],
			'textarea_small'                   => [ self::CONTEXT_TEXT ],
			'textarea_code'                    => [ self::CONTEXT_TEXT ],

			'text_time'                        => [ self::CONTEXT_TEXT ],
			'select_timezone'                  => [ self::CONTEXT_TEXT ],
			'text_date'                        => [ self::CONTEXT_TEXT ],
			'text_date_timestamp'              => [ self::CONTEXT_TEXT ],
			'text_datetime_timestamp'          => [ self::CONTEXT_TEXT ],
			'text_datetime_timestamp_timezone' => [ self::CONTEXT_TEXT ],

			'colorpicker'                      => [ self::CONTEXT_TEXT ],

			'radio'                            => [ self::CONTEXT_TEXT ],
			'radio_inline'                     => [ self::CONTEXT_TEXT ],
			'select'                           => [ self::CONTEXT_TEXT ],
			'checkbox'                         => [ self::CONTEXT_TEXT ],
			'multicheck'                       => [ self::CONTEXT_TEXT ],
			'multicheck_inline'                => [ self::CONTEXT_TEXT ],

			'wysiwyg'                          => [ self::CONTEXT_TEXT ],

			'file'                             => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_IMAGE, self::CONTEXT_VIDEO, self::CONTEXT_MEDIA ],
			'file_list'                        => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_IMAGE, self::CONTEXT_VIDEO, self::CONTEXT_MEDIA ],
			'oembed'                           => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_VIDEO, self::CONTEXT_MEDIA ],
		];

		return $fields;
	}
}
