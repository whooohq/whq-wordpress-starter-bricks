<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Acf extends Base {
	public static function load_me() {
		add_action( 'save_post_acf-field-group', [ __CLASS__, 'flush_cache' ] );
		return class_exists( 'ACF' );
	}

	public function register_tags() {
		$fields = self::get_fields();

		foreach ( $fields as $field ) {
			$this->register_tag( $field );
		}

		// Register custom function for ACF (@since 1.6.2)
		$this->register_dynamic_function_tag();
	}

	/**
	 * Collect nested parent group field name and locations
	 * To be used in get_acf_group_field_value()
	 *
	 * @since 1.9.1
	 *
	 * @param array $data
	 * @param array $parent_field
	 *
	 * @return array
	 */
	public function get_nested_parent_group_field_data( $data = [], $parent_field = [] ) {
		if ( ! isset( $data['name'] ) || ! isset( $parent_field['key'] ) ) {
			return $data;
		}
		$field_key = $parent_field['key'];

		// Concatenate the field name with the parent field name
		$data['name'] = $parent_field['name'] . '_' . $data['name'];

		// Record the parent field locations
		if ( ! empty( $parent_field['_bricks_locations'] ) ) {
			$data['_bricks_locations'] = array_merge( $data['_bricks_locations'], $parent_field['_bricks_locations'] );
		}

		// Record the parent group names
		if ( ! empty( $parent_field['name'] ) ) {
			$data['parent_group_names'][] = $parent_field['name'];
		}

		$found_tag = false;

		// Find from the tags that subfields has key same as $field_key
		foreach ( $this->tags as $tag ) {
			$sub_fields = $tag['field']['sub_fields'] ?? false;

			if ( ! $sub_fields ) {
				continue;
			}

			foreach ( $sub_fields as $sub_field ) {
				if ( $sub_field['key'] === $field_key ) {
					$found_tag = $tag;
					break 2; // Break out of both loops since the tag is found
				}
			}
		}

		if ( $found_tag ) {
			// $found_tag['field'] is the nested parent group field
			$data = $this->get_nested_parent_group_field_data( $data, $found_tag['field'] ); // Recursive
		}

		return $data;
	}

	public function register_tag( $field, $parent_field = [], $parent_tag = [] ) {
		$contexts = self::get_fields_by_context();

		$type = $field['type'];

		if ( ! isset( $contexts[ $type ] ) ) {
			return;
		}

		foreach ( $contexts[ $type ] as $context ) {
			// Add parent field name to the field name if needed
			$name = ! empty( $parent_field['name'] ) ? 'acf_' . $parent_field['name'] . '_' . $field['name'] : 'acf_' . $field['name'];

			// Add the context to the field name (legacy)
			if ( $context !== self::CONTEXT_TEXT && $context !== self::CONTEXT_LOOP && empty( $parent_field ) ) {
				$name .= '_' . $context;
			}

			$label = ! empty( $parent_field['label'] ) ? $field['label'] . ' (' . $parent_field['label'] . ')' : $field['label'];

			if ( $context === self::CONTEXT_LOOP ) {
				$label = 'ACF ' . ucfirst( $type ) . ': ' . $label;
			}

			$tag = [
				'group'    => 'ACF',
				'field'    => $field,
				'provider' => $this->name,
			];

			if ( ! empty( $parent_field ) ) {
				// Add the parent field attributes to the child tag so we could retrieve the value of group sub-fields
				$tag['parent'] = [
					'key'  => $parent_field['key'],
					'name' => $parent_field['name'],
					'type' => $parent_field['type'],
				];

				// Handle nested group field (@since 1.9.1)
				if ( $parent_field['type'] === 'group' ) {
					// Group by parent field, better visual in DD dropdown
					$tag['group'] = 'ACF: ' . $parent_field['label'];

					$data = [
						'name'               => $field['name'],
						'_bricks_locations'  => [],
						'parent_group_names' => [],
					];

					$nested_group_data = $this->get_nested_parent_group_field_data( $data, $parent_field );

					$nested_name = 'acf_' . $nested_group_data['name'];

					if ( $nested_name !== $name ) {
						// This is a nested group field
						$tag['nested_group'] = true;
						// Use the nested name
						$name = $nested_name;
						// Save the origin grand grand parent field
						$tag['_bricks_locations'] = $nested_group_data['_bricks_locations'];
						// Save the parent group names
						$tag['parent_group_names'] = $nested_group_data['parent_group_names'];
						// Group by nested parent field, better visual in DD dropdown
						$tag['group'] = 'ACF: ' . implode( ' > ', array_reverse( $nested_group_data['parent_group_names'] ) );
					}
				}

				if ( ! empty( $parent_field['_bricks_locations'] ) ) {
					$tag['parent']['_bricks_locations'] = $parent_field['_bricks_locations'];
				}

				// Include the parent layout name for flexible content sub-fields (@since 1.6.2)
				if ( $parent_field['type'] === 'flexible_content' ) {
					$parent_layout       = $field['parent_layout'];
					$parent_layout_name  = $parent_field['layouts'][ $parent_layout ]['name'];
					$parent_layout_label = $parent_field['layouts'][ $parent_layout ]['label'];

					// Change the name to include the parent layout name, ensure it's unique
					// e.g. acf_flexible_content_layout_name_sub_field_name
					$name = 'acf_' . $parent_field['name'] . '_' . $parent_layout_name . '_' . $field['name'];

					// Change the label to include the parent layout name
					$label = $field['label'] . ' (' . $parent_field['label'] . ') (' . $parent_layout_label . ')';
				}
			}

			// Set the tag name and label
			$tag['name']  = '{' . $name . '}';
			$tag['label'] = $label;

			/**
			 * Set 'duplicate' if this tag name has been registered before
			 *
			 * Meaning there is a field with the same 'name' in different field group.
			 *
			 * @since 1.8
			 */
			if ( isset( $this->tags[ $name ] ) ) {
				$tag['duplicate'] = true;
			}

			// Register fields for the Loop context ( e.g. Repeater, Relationship, Flexible content..)
			if ( $context === self::CONTEXT_LOOP || $type === 'group' ) {

				// Register the group field tag as deprecated to be used in case groups are nested inside a repeater (@since 1.5.1)
				if ( $type === 'group' ) {
					$this->tags[ $name ]               = $tag;
					$this->tags[ $name ]['deprecated'] = 1;
				} else {
					$this->loop_tags[ $name ] = $tag;
				}

				// Check for sub-fields (including group field sub-fields)
				if ( ! empty( $field['sub_fields'] ) ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$this->register_tag( $sub_field, $field, $tag ); // Recursive
					}
				}

				// Check for flexible content layouts, register their sub-fields (@since 1.6.2)
				if ( $type === 'flexible_content' && ! empty( $field['layouts'] ) ) {
					foreach ( $field['layouts'] as $layout ) {
						if ( ! empty( $layout['sub_fields'] ) ) {
							foreach ( $layout['sub_fields'] as $sub_field ) {
								$this->register_tag( $sub_field, $field, $tag ); // Recursive
							}
						}
					}
				}
			}

			// Only register fields from other contexts, other than CONTEXT_TEXT, if they are not sub-fields (legacy purposes)
			elseif ( $context === self::CONTEXT_TEXT || empty( $parent_field ) ) {
				$this->tags[ $name ] = $tag;

				if ( $context !== self::CONTEXT_TEXT ) {
					$this->tags[ $name ]['deprecated'] = 1;
				}

			}
		}
	}

	/**
	 * Register dynamic functions tags
	 *
	 * @since 1.6.2
	 */
	public function register_dynamic_function_tag() {
		$dynamic_functions = [
			'acf_get_row_layout' => [
				'name'     => '{acf_get_row_layout}',
				'label'    => esc_html__( 'ACF Get Row Layout', 'bricks' ),
				'group'    => 'ACF',
				'provider' => $this->name,
			],
		];

		$this->tags = array_merge( $this->tags, $dynamic_functions );
	}

	public static function get_fields() {
		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) || ! function_exists( 'get_field' ) ) {
			return [];
		}

		$last_changed = wp_cache_get_last_changed( 'bricks_acf-field-group' );
		$cache_key    = md5( 'acf_fields' . $last_changed );
		$acf_fields   = wp_cache_get( $cache_key, 'bricks' );

		if ( false === $acf_fields ) {
			// NOTE: Undocumented. This allows the user to remove some field groups from the picker
			$groups = apply_filters( 'bricks/acf/filter_field_groups', acf_get_field_groups() );

			if ( empty( $groups ) || ! is_array( $groups ) ) {
				return [];
			}

			$acf_fields = [];

			foreach ( $groups as $group ) {
				// Group fields
				$fields = acf_get_fields( $group );

				if ( ! is_array( $fields ) ) {
					continue;
				}

				$locations = self::get_fields_locations( $group );

				if ( ! empty( $locations ) ) {
					foreach ( $fields as $field ) {
						$field['_bricks_locations'] = $locations; // Save the field with a special bricks attribute
						$acf_fields[]               = $field;
					}

				} else {
					$acf_fields = array_merge( $acf_fields, $fields );
				}

			}

			// @since 1.7 - $acf_fields might include closures which cause fatal errors when using wp_cache_set (ACF Extended plugin)
			$acf_fields = wp_json_encode( $acf_fields );
			$acf_fields = json_decode( $acf_fields, true );

			wp_cache_set( $cache_key, $acf_fields, 'bricks', DAY_IN_SECONDS );
		}

		return $acf_fields;
	}

	public static function get_fields_locations( $group ) {
		if ( ! isset( $group['location'] ) || ! is_array( $group['location'] ) ) {
			return [];
		}

		$locations = [];

		foreach ( $group['location'] as $conditions ) {
			foreach ( $conditions as $condition ) {
				if ( ! isset( $condition['param'] ) ) {
					continue;
				}

				if ( $condition['param'] === 'options_page' ) {
					$locations['option'] = 1;
				}

				if ( in_array( $condition['param'], [ 'user_role', 'current_user', 'current_user_role', 'user_form' ] ) ) {
					$locations['user'] = 1;
				}

				if ( $condition['param'] === 'taxonomy' ) {
					$locations['term'] = 1;
				}

				// Without this, if multiple location rules set and contains a user related condition, get_object_id() wrongly return user meta fields instead of post (#32bp1n8)
				if ( $condition['param'] === 'post_type' ) {
					$locations['post'] = 1;
				}
			}
		}

		return array_keys( $locations );
	}

	/**
	 * Get tag value main function
	 *
	 * @param string  $tag The tag name (e.g. acf_my_field).
	 * @param WP_Post $post The post object.
	 * @param array   $args The dynamic data tag arguments.
	 * @param string  $context E.g. text, link, image.
	 *
	 * @return mixed The tag value.
	 */
	public function get_tag_value( $tag, $post, $args, $context ) {
		$post_id = isset( $post->ID ) ? $post->ID : '';

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// Default value
		$value = '';

		// For ACF fields
		if ( isset( $this->tags[ $tag ]['field'] ) ) {
			$field = $this->tags[ $tag ]['field'];

			/**
			 * Retrieve field settings from ACF:
			 *
			 * If field with the same 'name' has been registered already in another ACF field group.
			 *
			 * @since 1.8
			 */
			if ( isset( $this->tags[ $tag ]['duplicate'] ) ) {
				$actual_field = get_field_object( $field['name'], $post_id );

				if ( $actual_field === false && isset( $this->tags[ $tag ]['parent'] ) && $this->tags[ $tag ]['parent']['type'] === 'group' ) {
					// Handle duplicate group name and sub-field name (@see #862jkjubc @since 1.8.2)
					$parent_field_group = get_field_object( $this->tags[ $tag ]['parent']['name'], $post_id );

					// Get the sub-fields from the parent field group
					if ( $parent_field_group && isset( $parent_field_group['sub_fields'] ) ) {
						foreach ( $parent_field_group['sub_fields'] as $sub_field ) {
							if ( $sub_field['name'] === $field['name'] ) {
								// Found the actual sub-field, use it
								$actual_field = $sub_field;
								break;
							}
						}
					}
				}

				// Use the actual field settings if found (avoid error if $actual_field is false) (@see #862jkjubc @since 1.8.2)
				$field = $actual_field ? $actual_field : $field;
			}

			// STEP: Get the value
			$value = $this->get_raw_value( $tag, $post_id );

			$return_format = isset( $field['return_format'] ) ? $field['return_format'] : '';

			// @since 1.8 - New array_value filter. Once used, we don't want to process the field type logic
			if ( isset( $filters['array_value'] ) && is_array( $value ) ) {
				// Force context to text
				$context = 'text';
				$value   = $this->return_array_value( $value, $filters );
			}

			// Process field type logic
			else {
				switch ( $field['type'] ) {
					// Choice
					case 'select':
					case 'checkbox':
					case 'radio':
					case 'button_group':
						$value = $this->process_choices_fields( $value, $field, $filters );
						break;

					case 'true_false':
						// STEP: Return raw value for element conditions (@since 1.5.7)
						if ( isset( $filters['value'] ) ) {
							return is_array( $value ) ? implode( ', ', $value ) : $value;
						}

						$value = $value ? esc_html__( 'True', 'bricks' ) : esc_html__( 'False', 'bricks' );
						break;

					case 'user':
						$filters['object_type'] = 'user';

						// ACF allows for single or multiple users
						$value = $field['multiple'] ? $value : [ $value ];

						$value = $return_format === 'id' ? $value : wp_list_pluck( $value, 'ID' );

						break;

					case 'google_map':
						$value = $this->process_google_map_field( $value, $field );
						break;

					case 'taxonomy':
						$filters['object_type'] = 'term';
						$filters['taxonomy']    = $field['taxonomy'];

						// NOTE: Undocumented
						$show_as_link = apply_filters( 'bricks/acf/taxonomy/show_as_link', true, $value, $field );

						if ( $show_as_link ) {
							$filters['link'] = true;
						}

						$value = is_array( $value ) ? $value : [ $value ];

						$value = $return_format === 'id' ? $value : wp_list_pluck( $value, 'term_id' );
						break;

					case 'image':
					case 'gallery':
						$filters['object_type'] = 'media';
						$filters['separator']   = '';

						$value = empty( $value ) ? [] : (array) $value;

						if ( $return_format === 'array' ) {
							$value = isset( $value['id'] ) ? [ $value['id'] ] : wp_list_pluck( $value, 'id' );
						} elseif ( $return_format === 'url' ) {
							$value = array_map( 'attachment_url_to_postid', $value );
							$value = array_filter( $value );
						}
						break;

					case 'oembed':
						// if context is not text get the link value (instead of the oembed iframe)
						if ( 'text' !== $context ) {
							$value = get_post_meta( $post_id, $field['name'], true );
						} else {
							$filters['skip_sanitize'] = true;
						}
						break;

					case 'file':
						// @since 1.7.1 - Empty field should return empty array
						if ( empty( $value ) ) {
							$value = [];
						} else {
							$filters['object_type'] = 'media';
							$filters['link']        = true;

							// Return file 'id'
							if ( $return_format === 'array' && is_array( $value ) ) {
								$value = $value['id'];
							} elseif ( $return_format === 'url' ) {
								$value = attachment_url_to_postid( $value );
							}
						}
						break;

					case 'link':
						// Possible returns: url or array
						if ( $return_format === 'array' ) {
							$value = isset( $value['url'] ) ? $value['url'] : '';
						}
						break;

					case 'post_object':
					case 'relationship':
						// Only field is not empty then process
						if ( ! empty( $value ) && $return_format === 'object' ) {
							$filters['object_type'] = 'post';
							$filters['link']        = true;

							if ( isset( $value->ID ) ) {
								$value = $value->ID;
							} elseif ( is_array( $value ) ) {
								$value = wp_list_pluck( $value, 'ID' );
							}
						}
						break;

					// @see: https://www.advancedcustomfields.com/resources/date-picker/
					// @see: https://www.advancedcustomfields.com/resources/date-time-picker/
					case 'date_picker':
					case 'date_time_picker':
					case 'time_picker':
						if ( ! empty( $filters['meta_key'] ) && ! empty( $value ) ) {
							// Default format
							$default_format = $field['type'] == 'date_picker' ? 'Ymd' : 'Y-m-d H:i:s';

							// @since 1.8 - If return format is set, use it (from ACF setting)
							if ( ! empty( $return_format ) ) {
								$default_format = $return_format;
							}

							$date = \DateTime::createFromFormat( $default_format, $value );

							// @since 1.8 - Prevent error if date is not valid due to unexpected issue
							if ( $date instanceof \DateTime ) {
								$value                  = $date->format( 'U' );
								$filters['object_type'] = $field['type'] == 'date_picker' ? 'date' : 'datetime';
							}
						}
						break;

						// @since 1.5.1
					case 'color_picker':
						// Possible return formats: 'string', 'array' (rgba)
						if ( $return_format === 'array' ) {
							$red   = ! empty( $value['red'] ) ? $value['red'] : 0;
							$green = ! empty( $value['green'] ) ? $value['green'] : 0;
							$blue  = ! empty( $value['blue'] ) ? $value['blue'] : 0;
							$alpha = ! empty( $value['alpha'] ) ? $value['alpha'] : 0;

							$value = $red === 0 && $green === 0 && $blue === 0 && $alpha === 0 ? '' : "rgba({$red},{$green},{$blue},{$alpha})";
						}
						break;
				}
			}
		}

		// For ACF dynamic functions
		else {
			switch ( $tag ) {
				case 'acf_get_row_layout':
					$value = '';

					if ( \Bricks\Query::is_looping() ) {
						$looping_object = \Bricks\Query::get_loop_object();

						// Check if current loop object is an ACF flexible content: Must be an array & must have the 'acf_fc_layout' key (@since 1.8)
						if ( is_array( $looping_object ) && isset( $looping_object['acf_fc_layout'] ) ) {
							$value = $looping_object['acf_fc_layout'];
						}
					}
					break;
			}
		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	/**
	 * Get the field raw value
	 *
	 * @param array      $tag The tag name (e.g. acf_my_field).
	 * @param int|string $post_id The post ID.
	 */
	public function get_raw_value( $tag, $post_id ) {
		$tag_object = $this->tags[ $tag ];
		$field      = $tag_object['field'];

		// Check if field is belongs to options page
		$locations = isset( $field['_bricks_locations'] ) ? $field['_bricks_locations'] : [];
		$is_option = ! empty( $locations ) && in_array( 'option', $locations );

		if ( isset( $tag_object['parent']['_bricks_locations'] ) && ! empty( $tag_object['parent']['_bricks_locations'] ) && ! $is_option ) {
			// Check if parent field is belongs to options page
			$is_option = in_array( 'option', $tag_object['parent']['_bricks_locations'] );
		}

		if ( isset( $tag_object['nested_group'] ) && isset( $tag_object['_bricks_locations'] ) && ! empty( $tag_object['_bricks_locations'] ) && ! $is_option ) {
			// Check if nested group field is belongs to options page (@since 1.9.1.1)
			$is_option = in_array( 'option', $tag_object['_bricks_locations'] );
		}

		// STEP: Check if in a Relationship, or Repeater loop (could have nested groups inside - @since 1.5.1)
		if ( \Bricks\Query::is_looping() ) {
			$query_type = \Bricks\Query::get_query_object_type();

			// Loop belongs to this provider
			if ( array_key_exists( $query_type, $this->loop_tags ) ) {
				// Query Loop tag object
				$loop_tag_object = $this->loop_tags[ $query_type ];

				// Query Loop tag ACF field key
				$loop_tag_field_key = isset( $loop_tag_object['field']['key'] ) ? $loop_tag_object['field']['key'] : false;

				if ( empty( $loop_tag_field_key ) ) {
					return '';
				}

				/**
				 * Loop created by an ACF Relationship field (@since 1.5.1)
				 * OR an ACF Post Object field (@since 1.8.6)
				 */
				if (
					isset( $loop_tag_object['field']['type'] ) &&
					in_array( $loop_tag_object['field']['type'], [ 'relationship', 'post_object' ] )
				) {
					// The loop already sets the global $post
					$post_id = get_the_ID();

					// Is a Group sub-field
					if ( isset( $tag_object['parent']['type'] ) && $tag_object['parent']['type'] === 'group' ) {
						return $this->get_acf_group_field_value( $tag_object, $post_id );
					}

					// Is a regular field
					return $this->get_acf_field_value( $field, $post_id );
				}

				// NOTE: Bricks needs to build a path to get the final value from the $loop_object
				// This is needed as we don't use the group field, but only the sub-fields (which could also be groups = nested groups!)
				// The iteration starts on the field to which we need to get the value and iterates its parent until it is the query loop field.

				// Store the field names while iterating
				$value_path = [];

				// Get the first parent field object
				$parent_field = isset( $tag_object['parent']['key'] ) ? get_field_object( $tag_object['parent']['key'] ) : false;

				// Check if the parent field is the loop field; if not, iterate up
				while ( isset( $parent_field['key'] ) && $parent_field['key'] !== $loop_tag_field_key ) {
					if ( isset( $parent_field['name'] ) ) {
						$value_path[] = $parent_field['name'];

						// Get the parent field tag object (as registered in Bricks)
						$parent_tag = isset( $this->tags[ $parent_field['name'] ] ) ? $this->tags[ $parent_field['name'] ] : false;
					} else {
						$parent_tag = false;
					}

					// Get the parent of the parent
					$parent_field = isset( $parent_tag['parent']['key'] ) ? get_field_object( $parent_tag['parent']['key'] ) : false;
				}

				// The current loop object (array of values)
				$narrow_values = \Bricks\Query::get_loop_object();

				/**
				 * If the field is a nested group field, the $value_path should use the parent group names
				 * Eg. Flexible content > Layout > Group > Group > Field
				 * Eg. Repeater > Group > Group > Field
				 * (@since 1.9.1.1)
				 */
				if ( isset( $tag_object['nested_group'] ) && isset( $tag_object['parent_group_names'] ) ) {
					// Flexible content field is not supported inside a loop
					$value_path = $tag_object['parent_group_names'];
				}

				if ( ! empty( $value_path ) ) {
					// Start with the top parent field name, and go deeper (groups inside of groups..)
					$value_path = array_reverse( $value_path );

					foreach ( $value_path as $name ) {
						if ( isset( $narrow_values[ $name ] ) ) {
							$narrow_values = $narrow_values[ $name ];
						}
					}
				}

				$found_value = isset( $narrow_values[ $field['name'] ] ) ? $narrow_values[ $field['name'] ] : '';

				// Return the value if this is not an option page field (@since 1.8)
				if ( ! $is_option ) {
					return $found_value;
				}

				// This is option page field, and found in the $narrow_values, return it and stop here (@since 1.8)
				// Cannot use ! empty( $found_value ) as it could be empty string
				if ( isset( $narrow_values[ $field['name'] ] ) ) {
					return $found_value;
				}

				// Option page field inside the loop, but not found in $narrow_values will continue with group sub-field and regular logic below (@since 1.8)
				// Ex: Output an option page field inside a another repeater loop (#862jvqkma)
			}
		}

		// STEP: Is a Group sub-field
		if ( isset( $tag_object['parent']['type'] ) && $tag_object['parent']['type'] === 'group' ) {
			return $this->get_acf_group_field_value( $tag_object, $post_id );
		}

		// STEP: Still here, get the regular value for this field
		return $this->get_acf_field_value( $field, $post_id );
	}

	/**
	 * Get ACF group field value
	 *
	 * @since 1.5
	 */
	public function get_acf_group_field_value( $tag_object, $post_id ) {
		if ( isset( $tag_object['nested_group'] ) ) {
			/**
			 * Support nested groups
			 *
			 * @since 1.9.1
			 */
			// STEP: Remove the {acf_ and } from the tag name. Eg: groupa_groupb_groupc_field
			$field_selector = str_replace( '{acf_', '', $tag_object['name'] );
			$field_selector = str_replace( '}', '', $field_selector );

			// STEP: We need to know the acf_object_id when using get_field(), could be post, term, user, option etc..
			$acf_object_id = $this->get_object_id( $tag_object, $post_id );

			// STEP: Use ACF get_field() to get the value
			$value = get_field( $field_selector, $acf_object_id );
		} else {

			$field       = $tag_object['field'];
			$group_field = get_field_object( $tag_object['parent']['key'] );

			if ( ! empty( $tag_object['parent']['_bricks_locations'] ) ) {
				$group_field['_bricks_locations'] = $tag_object['parent']['_bricks_locations'];
			}

			$group_value = $this->get_acf_field_value( $group_field, $post_id );

			$value = isset( $group_value[ $field['name'] ] ) ? $group_value[ $field['name'] ] : '';
		}

		return $value;
	}

	/**
	 * Get ACF field value
	 *
	 * @param array      $field ACF field settings.
	 * @param int|string $post_id The post ID.
	 */
	public function get_acf_field_value( $field, $post_id ) {
		$acf_object_id = $this->get_object_id( $field, $post_id );
		$field_type    = ! empty( $field['type'] ) ? $field['type'] : '';

		// Don't remove 'acf_the_content' filter for 'wysiwyg' field type (@since 1.7)
		if ( $field_type !== 'wysiwyg' ) {
			remove_filter( 'acf_the_content', 'wpautop' );
		}

		/**
		 * Get ACF field value
		 *
		 * @see https://www.advancedcustomfields.com/resources/get_field/
		 *
		 * @since 1.7.1 Get field by 'name' instead of 'key' as the 'key' is not reliable for clone type fields (@see #862jb27g9)
		 */
		$field_selector = ! empty( $field['name'] ) ? $field['name'] : $field['key'];
		$value          = get_field( $field_selector, $acf_object_id );

		if ( $field_type !== 'wysiwyg' ) {
			add_filter( 'acf_the_content', 'wpautop' );
		}

		return $value;
	}

	/**
	 * Process the choice fields to return an array of choice labels
	 *
	 * @param [type] $value
	 * @param [type] $field
	 * @param [type] $filters
	 */
	public function process_choices_fields( $value, $field, $filters ) {
		$value = (array) $value;

		// If return format is set to "Both (array)" return 'label' by default
		if ( ! empty( $field['return_format'] ) ) {
			if ( $field['return_format'] === 'array' ) {
				// @since 1.7.2 - Support :value filter to return the value instead of the label
				$key          = isset( $filters['value'] ) ? 'value' : 'label';
				$unwanted_key = $key === 'label' ? 'value' : 'label';

				if ( isset( $value[ $unwanted_key ] ) ) {
					// For single choice field
					unset( $value[ $unwanted_key ] );
				} else {
					// For multiple choice field
					$value = wp_list_pluck( $value, $key );
				}
			}
		}

		return $value;
	}

	public function process_google_map_field( $value, $field ) {
		// NOTE: Undocumented. By default, the google map field will show as an address, if ACF version >= 5.6.8 @see https://www.advancedcustomfields.com/resources/google-map/
		$show_as_address = apply_filters( 'bricks/acf/google_map/show_as_address', defined( 'ACF_VERSION' ) && version_compare( ACF_VERSION, '5.6.8', '>=' ), $value, $field );

		$output = [];

		if ( $show_as_address ) {
			// NOTE: Undocumented. Filter or order the address parts
			$address_parts = apply_filters( 'bricks/acf/google_map/address_parts', [ 'street_name', 'street_number', 'city', 'state', 'post_code', 'country' ], $value, $field );

			foreach ( $address_parts as $key ) {
				if ( ! empty( $value[ $key ] ) ) {
					$output[] = sprintf( '<span class="acf-map-%s">%s</span>', $key, $value[ $key ] );
				}
			}

		} else {
			foreach ( [ 'lat', 'lng' ] as $key ) {
				if ( ! empty( $value[ $key ] ) ) {
					$output[] = sprintf( '<span class="acf-map-%s">%s</span>, ', $key, $value[ $key ] );
				}
			}
		}

		// NOTE: Undocumented.
		return apply_filters( 'bricks/acf/google_map/text_output', implode( ', ', $output ), $value, $field );
	}

	/**
	 * Get all fields supported and their contexts
	 *
	 * @return array
	 */
	private static function get_fields_by_context() {
		$fields = [
			// Basic
			'text'             => [ self::CONTEXT_TEXT ],
			'textarea'         => [ self::CONTEXT_TEXT ],
			'number'           => [ self::CONTEXT_TEXT ],
			'range'            => [ self::CONTEXT_TEXT ],
			'email'            => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'url'              => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'password'         => [ self::CONTEXT_TEXT ],

			// Content
			'image'            => [ self::CONTEXT_TEXT, self::CONTEXT_IMAGE ],
			'gallery'          => [ self::CONTEXT_TEXT, self::CONTEXT_IMAGE ],
			'file'             => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_VIDEO, self::CONTEXT_MEDIA ],
			'wysiwyg'          => [ self::CONTEXT_TEXT ],
			'oembed'           => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_VIDEO, self::CONTEXT_MEDIA ],

			// Choice
			'select'           => [ self::CONTEXT_TEXT ],
			'checkbox'         => [ self::CONTEXT_TEXT ],
			'radio'            => [ self::CONTEXT_TEXT ],
			'button_group'     => [ self::CONTEXT_TEXT ],
			'true_false'       => [ self::CONTEXT_TEXT ],

			// Relational
			'link'             => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'post_object'      => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_LOOP ], // Support Query Loop @since 1.8.6
			'page_link'        => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'relationship'     => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_LOOP ],
			'taxonomy'         => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'user'             => [ self::CONTEXT_TEXT ],

			// jQuery
			'google_map'       => [ self::CONTEXT_TEXT ],
			'date_picker'      => [ self::CONTEXT_TEXT ],
			'date_time_picker' => [ self::CONTEXT_TEXT ],
			'time_picker'      => [ self::CONTEXT_TEXT ],
			'color_picker'     => [ self::CONTEXT_TEXT ],

			'group'            => [ self::CONTEXT_TEXT ],
			'repeater'         => [ self::CONTEXT_LOOP ],
			'flexible_content' => [ self::CONTEXT_LOOP ],
		];

		return $fields;
	}

	/**
	 * Calculate the object ID to be used when fetching the field value
	 *
	 * @param array $field
	 * @param int   $post_id
	 */
	public function get_object_id( $field, $post_id ) {

		$locations = isset( $field['_bricks_locations'] ) ? $field['_bricks_locations'] : [];

		// This field belongs to a Options page
		if ( in_array( 'option', $locations ) ) {
			return 'option';
		}

		// In a Query Loop
		if ( \Bricks\Query::is_looping() ) {
			$object_type = \Bricks\Query::get_loop_object_type();
			$object_id   = \Bricks\Query::get_loop_object_id();

			// Terms loop
			if ( $object_type == 'term' && in_array( $object_type, $locations ) ) {
				$object = \Bricks\Query::get_loop_object();

				return isset( $object->taxonomy ) ? $object->taxonomy . '_' . $object_id : $post_id;
			}

			// Users loop
			if ( $object_type == 'user' && in_array( $object_type, $locations ) ) {
				return 'user_' . $object_id;
			}
		}

		$queried_object = \Bricks\Helpers::get_queried_object( $post_id );

		if ( in_array( 'term', $locations ) && is_a( $queried_object, 'WP_Term' ) ) {
			if ( isset( $queried_object->taxonomy ) && isset( $queried_object->term_id ) ) {
				return $queried_object->taxonomy . '_' . $queried_object->term_id;
			}
		}

		if ( in_array( 'user', $locations ) ) {
			if ( is_a( $queried_object, 'WP_User' ) && isset( $queried_object->ID ) ) {
				return 'user_' . $queried_object->ID;
			}

			if ( count( $locations ) == 1 ) {
				return 'user_' . get_current_user_id();
			}
		}

		// Default
		return $post_id;
	}

	/**
	 * Set the loop query if exists
	 *
	 * @param array $results
	 * @param Query $query
	 * @return array
	 */
	public function set_loop_query( $results, $query ) {
		if ( ! array_key_exists( $query->object_type, $this->loop_tags ) ) {
			return $results;
		}

		$tag_object = $this->loop_tags[ $query->object_type ];

		$field = $this->loop_tags[ $query->object_type ]['field'];

		$looping_query_id = \Bricks\Query::is_any_looping();

		if ( $looping_query_id ) {
			$loop_query_object_type = \Bricks\Query::get_query_object_type( $looping_query_id );
			$loop_object_type       = \Bricks\Query::get_loop_object_type( $looping_query_id );

			// Maybe it is a nested loop
			if ( array_key_exists( $loop_query_object_type, $this->loop_tags ) ) {
				$loop_object = \Bricks\Query::get_loop_object( $looping_query_id );

				// If this is a nested repeater
				if ( is_array( $loop_object ) && array_key_exists( $field['name'], $loop_object ) ) {
					return $loop_object[ $field['name'] ];
				}

				// Nested repeater inside nested group (@since 1.9.1)
				if ( isset( $tag_object['nested_group'] ) ) {
					// Parent group name first. Eg: ['general', 'actors']
					$parent_group_names = isset( $tag_object['parent_group_names'] ) ? $tag_object['parent_group_names'] : [];

					if ( empty( $parent_group_names ) ) {
						return []; // No parent group names, return empty array
					}

					// if nested group field is belongs to options page, use get_acf_group_field_value() to get the value (@since 1.9.1.1)
					if ( isset( $tag_object['_bricks_locations'] ) && ! empty( $tag_object['_bricks_locations'] ) && in_array( 'option', $tag_object['_bricks_locations'] ) ) {
						return $this->get_acf_group_field_value( $tag_object, 0 );
					}

					// Reverse the array to get the top parent group name first. Eg: ['actors', 'general']
					$parent_group_names = array_reverse( $parent_group_names );
					// Add the current group name to the array Eg: ['actors', 'general', 'lists']
					$parent_group_names[] = $field['name'];

					foreach ( $parent_group_names as $parent_group_name ) {
						if ( isset( $loop_object[ $parent_group_name ] ) ) {
							$loop_object = $loop_object[ $parent_group_name ];
						} else {
							return []; // Parent group name not found, return empty array
						}
					}

					return $loop_object;
				}

				// If this is a nested relationship
				if ( is_object( $loop_object ) && is_a( $loop_object, 'WP_Post' ) ) {
					$acf_object_id = get_the_ID();
				}

			}

			/**
			 * Check: Is it a post loop?
			 *
			 * @since 1.7: use $loop_object_type instead of $loop_query_object_type so that it works with user custom queries via PHP filters
			 */
			elseif ( $loop_object_type === 'post' ) {
				$acf_object_id = get_the_ID();
			}
		}

		if ( ! isset( $acf_object_id ) ) {
			// Get the $post_id or the template preview ID
			$post_id = \Bricks\Database::$page_data['preview_or_post_id'];

			$acf_object_id = $this->get_object_id( $field, $post_id );
		}

		// Check if it is a subfield of a group field (Repeater inside of a Group)
		if ( isset( $tag_object['parent']['type'] ) && $tag_object['parent']['type'] === 'group' ) {
			$post_id = isset( $loop_query_object_type ) && $loop_query_object_type === 'post' ? get_the_ID() : \Bricks\Database::$page_data['preview_or_post_id'];

			$results = $this->get_acf_group_field_value( $tag_object, $post_id );
		} else {
			/**
			 * Use $field['name'] instead of $field['key'], clone type fields $field['key'] is not reliable (see #862jb27g9; @since 1.7.1)
			 *
			 * @see https://www.advancedcustomfields.com/resources/get_field/
			 */
			$results = get_field( $field['name'], $acf_object_id );
		}

		/**
		 * If the field is a post_object, then the results could be a single post object or an array of post objects
		 *
		 * Transform the single post object into an array.
		 *
		 * @since 1.9
		 */
		if ( $field['type'] === 'post_object' && ! empty( $results ) ) {
			$results = is_array( $results ) ? $results : [ $results ];
		}

		return ! empty( $results ) ? $results : [];
	}

	/**
	 * Manipulate the loop object
	 *
	 * @param array  $loop_object
	 * @param string $loop_key
	 * @param Query  $query
	 * @return array
	 */
	public function set_loop_object( $loop_object, $loop_key, $query ) {
		if ( ! array_key_exists( $query->object_type, $this->loop_tags ) ) {
			return $loop_object;
		}

		// Check if the ACF field is relationship (list of posts)
		$field = $this->loop_tags[ $query->object_type ]['field'];

		// 'relationship' and 'post_object' needs to set the global $post (@since 1.8.6)
		if ( in_array( $field['type'], [ 'relationship','post_object' ] ) ) {
			global $post;
			$post = get_post( $loop_object );
			setup_postdata( $post );

			// The $loop_object could be a post ID or a post object, returning the post object (@since 1.5.3)
			return $post;
		}

		return $loop_object;
	}

	public static function flush_cache( $post_id ) {
		wp_cache_set( 'last_changed', microtime(), 'bricks_' . get_post_type( $post_id ) );
	}
}
