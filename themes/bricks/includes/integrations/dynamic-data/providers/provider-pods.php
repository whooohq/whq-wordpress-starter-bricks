<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Pods extends Base {
	public static function load_me() {
		return class_exists( 'PodsInit' );
	}

	public function register_tags() {
		$fields   = self::get_fields();
		$contexts = self::get_fields_by_context();

		foreach ( $fields as $field ) {
			$type = $field['type'];

			if ( ! isset( $contexts[ $type ] ) ) {
				continue;
			}

			foreach ( $contexts[ $type ] as $context ) {

				$key = 'pods_' . $field['object'] . '_' . $field['name'];

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
		if ( ! function_exists( 'pods_api' ) ) {
			return [];
		}

		$strict = false;

		// Loads all the pods
		$pods = pods_api()->load_pods();

		$pods_fields = [];

		foreach ( $pods as $pod ) {
			// Allow Pod's fields for posts, terms and users @Since 1.5.1
			if ( ! isset( $pod['fields'] ) ) {
				continue;
			}

			foreach ( $pod['fields'] as $field ) {
				$args = [
					'label'       => $field['label'],
					'name'        => $field['name'],
					'type'        => $field['type'],
					'group'       => 'Pods (' . $pod['label'] . ')',
					'object'      => $pod['name'], // The slug of the object (e.g. project, category, user)
					'pod_id'      => $pod['id'],
					'object_type' => $pod['type'], // post_type, taxonomy or user
					'args'        => $field,
				];

				if ( $field['type'] == 'pick' ) {
					$args['pick_object'] = $field['pick_object'];
					$args['pick_val']    = isset( $field['pick_val'] ) ? $field['pick_val'] : '';
				}

				$pods_fields[] = $args;
			}
		}

		return $pods_fields;
	}

	public function get_tag_value( $tag, $post, $args, $context ) {
		$post_id = isset( $post->ID ) ? $post->ID : '';

		$field = $this->tags[ $tag ]['field'];

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// STEP: Get the value
		$value = $this->get_raw_value( $field, $post_id, $filters );

		switch ( $field['type'] ) {
			case 'code':
				$theme_styles = \Bricks\Theme_Styles::$active_settings;
				$classes      = isset( $theme_styles['code']['prettify'] ) ? 'prettyprint ' . $theme_styles['code']['prettify'] : '';

				if ( is_array( $value ) ) {
					$value = implode( "\n", $value );
				};

				$value = '<pre class="' . $classes . '"><code>' . esc_html( $value ) . '</code></pre>';
				break;

			case 'file':
				$filters['object_type'] = 'media';

				if ( empty( $filters['image'] ) ) {
					$filters['link'] = true;
				}

				$value = isset( $value['ID'] ) ? [ $value['ID'] ] : wp_list_pluck( $value, 'ID' );
				break;

			case 'pick':
				if ( isset( $field['pick_object'] ) ) {
					// No need to prepare the custom simple type
					// $field['pick_object'] == 'custom-simple'

					// List of posts
					if ( $field['pick_object'] == 'post_type' ) {
						$filters['object_type'] = 'post';

						$value = isset( $value['ID'] ) ? [ $value['ID'] ] : wp_list_pluck( $value, 'ID' );
					}

					// List of terms
					elseif ( $field['pick_object'] == 'taxonomy' ) {
						$filters['object_type'] = 'term';
						$filters['taxonomy']    = $field['pick_val'];

						$value = isset( $value['term_id'] ) ? [ $value['term_id'] ] : wp_list_pluck( $value, 'term_id' );
					}

					// List of users
					elseif ( $field['pick_object'] == 'user' ) {
						$filters['object_type'] = 'user';

						$value = isset( $value['ID'] ) ? [ $value['ID'] ] : wp_list_pluck( $value, 'ID' );
					}
				}

				break;

			// @since 1.9.3
			case 'date':
				if ( ! empty( $filters['meta_key'] ) && ! empty( $value ) ) {
					$pod_field = $field['args'];
					$date_type = $pod_field->get_arg( 'date_type' );

					if ( $date_type === 'wp' ) {
						// Use the WP date format
						$format = get_option( 'date_format' );
					} elseif ( $date_type === 'format' ) {
						$format = $pod_field->get_arg( 'date_format' );
						$format = self::convert_to_proper_php_format( $format );
					} elseif ( $date_type === 'custom' ) {
						$format = $pod_field->get_arg( 'date_format_custom' );
					}

					$date = \DateTime::createFromFormat( $format, $value );

					if ( $date instanceof \DateTime ) {
						$value                  = $date->format( 'U' );
						$filters['object_type'] = 'date';
					}
				}
				break;

			// @since 1.9.3
			case 'time':
				if ( ! empty( $filters['meta_key'] ) && ! empty( $value ) ) {
					$pod_field = $field['args'];
					$time_type = $pod_field->get_arg( 'time_type' );

					if ( $time_type === 'wp' ) {
						// Use the WP time format
						$format = get_option( 'time_format' );
					} elseif ( $time_type === '12' || $time_type === '24' ) {
						$format = $pod_field->get_arg( 'time_format' );
						$format = self::convert_to_proper_php_format( $format );
					} elseif ( $time_type === 'custom' ) {
						$format = $pod_field->get_arg( 'time_format_custom' );
					}

					$date = \DateTime::createFromFormat( $format, $value );

					if ( $date instanceof \DateTime ) {
						$value                  = $date->format( 'U' );
						$filters['object_type'] = 'datetime';
					}
				}
				break;

			// @since 1.9.3
			case 'datetime':
				if ( ! empty( $filters['meta_key'] ) && ! empty( $value ) ) {
					$pod_field      = $field['args'];
					$date_time_type = $pod_field->get_arg( 'datetime_type' );

					if ( $date_time_type === 'wp' ) {
						// Use the WP date time format
						$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
					} elseif ( $date_time_type === 'format' ) {
						$date_format = self::convert_to_proper_php_format( $pod_field->get_arg( 'datetime_format' ) );
						$time_format = self::convert_to_proper_php_format( $pod_field->get_arg( 'datetime_time_format' ) );
						$format      = $pod_field->get_arg( 'datetime_format' ) == 'c' ? $date_format : $date_format . ' ' . $time_format;
					} elseif ( $date_time_type === 'custom' ) {
						$format = $pod_field->get_arg( 'datetime_format_custom' );
					}

					$date = \DateTime::createFromFormat( $format, $value );

					if ( $date instanceof \DateTime ) {
						$value                  = $date->format( 'U' );
						$filters['object_type'] = 'datetime';
					}
				}
				break;

		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	/**
	 * Convert the Pod's date, time and datetime formats to PHP date formats
	 *
	 * @since 1.9.3
	 */
	public static function convert_to_proper_php_format( $format ) {
		$php_format = 'Y-m-d H:i:s';

		switch ( $format ) {
			case 'mdy':
				$php_format = 'm/d/Y';
				break;
			case 'mdy_dash':
				$php_format = 'm-d-Y';
				break;
			case 'mdy_dot':
				$php_format = 'm.d.Y';
				break;
			case 'ymd_slash':
				$php_format = 'Y/m/d';
				break;
			case 'ymd_dash':
				$php_format = 'Y-m-d';
				break;
			case 'ymd_dot':
				$php_format = 'Y.m.d';
				break;
			case 'fjy':
				$php_format = 'F j, Y';
				break;
			case 'fjsy':
				$php_format = 'F jS, Y';
				break;
			case 'y':
				$php_format = 'Y';
				break;
			case 'dmy':
				$php_format = 'd/m/Y';
				break;
			case 'dmy_dash':
				$php_format = 'd-m-Y';
				break;
			case 'dmy_dot':
				$php_format = 'd.m.Y';
				break;
			case 'dMy':
				$php_format = 'd/M/Y';
				break;
			case 'dMy_dash':
				$php_format = 'd-M-Y';
				break;
			case 'h_mm_A':
				$php_format = 'h:i A';
				break;
			case 'h_mm_ss_A':
				$php_format = 'h:i:s A';
				break;
			case 'hh_mm_A':
				$php_format = 'H:i A';
				break;
			case 'hh_mm_ss_A':
				$php_format = 'H:i:s A';
				break;
			case 'h_mma':
				$php_format = 'h:ia';
				break;
			case 'hh_mma':
				$php_format = 'H:ia';
				break;
			case 'h_mm':
				$php_format = 'h:i';
				break;
			case 'h_mm_ss':
				$php_format = 'h:i:s';
				break;
			case 'hh_mm':
				$php_format = 'H:i';
				break;
			case 'hh_mm_ss':
				$php_format = 'H:i:s';
				break;
			case 'c':
				// 2023-10-24T08:17:33+00:00
				$php_format = 'Y-m-d\TH:i:sP';
				break;
		}

		return $php_format;
	}

	/**
	 * Get the field value
	 *
	 * @param array $field
	 * @param int   $post_id
	 * @param array $filters
	 *
	 * @since 1.5.2
	 */
	public function get_raw_value( $field, $post_id, $filters = [] ) {
		// Post, Term or User ID
		$object_id = $this->get_object_id( $field, $post_id );

		// Boolean flag to determine when to use pods_field_display() instead of pods_field()
		$use_display = in_array( $field['type'], [ 'boolean', 'date', 'datetime', 'time' ] ) ||
		( $field['type'] === 'pick' && isset( $field['pick_object'] ) && $field['pick_object'] === 'custom-simple' && ! array_key_exists( 'value', $filters ) );

		// Use appropriate method depending on the flag
		$value = $use_display
			? pods_field_display( $field['object'], $object_id, $field['name'] )
			: pods_field( $field['object'], $object_id, $field['name'] );

		return $value;
	}

	/**
	 * Calculate the object ID to be used when fetching the field value
	 *
	 * @since 1.5.2
	 *
	 * @param array $field
	 * @param int   $post_id
	 * @return mixed
	 */
	public function get_object_id( $field, $post_id ) {
		if ( $field['object_type'] === 'taxonomy' ) {
			$object_type = 'term';
		} elseif ( $field['object_type'] === 'user' ) {
			$object_type = 'user';
		} else {
			$object_type = 'post';
		}

		if ( \Bricks\Query::is_looping() ) {
			$loop_type = \Bricks\Query::get_loop_object_type();
			$object_id = \Bricks\Query::get_loop_object_id();

			// loop type is the same as the field object type (term, user, post)
			if ( $loop_type == $object_type ) {
				return $object_id;
			}
		}

		$queried_object = \Bricks\Helpers::get_queried_object( $post_id );

		if ( $object_type == 'term' && is_a( $queried_object, 'WP_Term' ) ) {
			return isset( $queried_object->term_id ) ? $queried_object->term_id : 0;
		}

		if ( $object_type == 'user' ) {

			if ( is_a( $queried_object, 'WP_User' ) && isset( $queried_object->ID ) ) {
				return $queried_object->ID;
			}

			return get_current_user_id();
		}

		// By default
		return $post_id;
	}

	/**
	 * Get all fields supported and their contexts
	 *
	 * @return array
	 */
	private static function get_fields_by_context() {
		$fields = [
			// Text
			'text'      => [ self::CONTEXT_TEXT ],
			'website'   => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'phone'     => [ self::CONTEXT_TEXT ],
			'email'     => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'password'  => [ self::CONTEXT_TEXT ],

			// Paragraph
			'paragraph' => [ self::CONTEXT_TEXT ],
			'wysiwyg'   => [ self::CONTEXT_TEXT ],
			'code'      => [ self::CONTEXT_TEXT ],

			// Date/Time
			'datetime'  => [ self::CONTEXT_TEXT ],
			'date'      => [ self::CONTEXT_TEXT ],
			'time'      => [ self::CONTEXT_TEXT ],

			// Number
			'number'    => [ self::CONTEXT_TEXT ],
			'currency'  => [ self::CONTEXT_TEXT ],

			// Relationship / Media
			'file'      => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_IMAGE, self::CONTEXT_VIDEO, self::CONTEXT_MEDIA ],
			'oembed'    => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_VIDEO, self::CONTEXT_MEDIA ],
			'pick'      => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ], // relationship

			// Other
			'boolean'   => [ self::CONTEXT_TEXT ],
			'color'     => [ self::CONTEXT_TEXT ],
		];

		return $fields;
	}

}
