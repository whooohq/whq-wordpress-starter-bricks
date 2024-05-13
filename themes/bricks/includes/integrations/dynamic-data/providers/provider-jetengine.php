<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Jetengine extends Base {
	public static function load_me() {
		return class_exists( 'Jet_Engine' );
	}

	public function register_tags() {

		$fields = self::get_fields();

		foreach ( $fields as $field ) {
			$this->register_tag( $field );
		}

		// Register relationships to the query loop
		$this->register_relationships();
	}

	public function register_tag( $field, $parent_field = [] ) {
		$name = ! empty( $parent_field ) ? "je_{$field['_brx_object']}_{$parent_field['name']}_{$field['name']}" : "je_{$field['_brx_object']}_{$field['name']}";

		$label = ! empty( $parent_field['title'] ) ? "{$field['title']} ({$parent_field['title']})" : $field['title'];

		$tag = [
			'name'     => '{' . $name . '}',
			'label'    => $label,
			'group'    => "Jet Engine ({$field['_brx_group_label']})",
			'field'    => $field,
			'provider' => $this->name
		];

		if ( ! empty( $parent_field ) ) {
			// Add the parent field attributes to the child tag
			$tag['parent'] = [
				'id' => $parent_field['id'],
			];
		}

		// Repeater field (loop)
		if ( in_array( $field['type'], [ 'repeater', 'posts' ], true ) ) {

			// Add the 'posts' field to both loop and regular fields lists
			if ( $field['type'] === 'posts' ) {
				$this->tags[ $name ] = $tag;
			}

			$tag['label'] = 'JE ' . ucfirst( $field['type'] ) . ': ' . $label;

			$this->loop_tags[ $name ] = $tag;

			if ( ! empty( $field['repeater-fields'] ) ) {
				foreach ( $field['repeater-fields'] as $sub_field ) {

					$sub_field['_brx_type']        = $field['_brx_type'];
					$sub_field['_brx_object']      = $field['_brx_object'];
					$sub_field['_brx_group_label'] = $field['_brx_group_label'];

					$this->register_tag( $sub_field, $field ); // Recursive
				}
			}
		}

		// Regular fields
		else {
			$this->tags[ $name ] = $tag;
		}
	}

	public static function get_fields() {

		$fields = [];

		$supports = self::get_supported_field_types();

		// STEP: Post, Term and User metaboxes
		$field_groups = isset( jet_engine()->meta_boxes ) ? jet_engine()->meta_boxes->get_registered_fields() : [];

		// Remove the default user fields.
		unset( $field_groups['Default user fields'] );

		if ( ! empty( $field_groups ) ) {

			$post_types = get_post_types( [], 'objects' );
			$taxonomies = get_taxonomies( [], 'objects' );

			foreach ( $field_groups as $object => $meta_fields ) {

				// $object could be a cpt slug, tax slug or user
				if ( isset( $post_types[ $object ] ) ) {
					$group_label = $post_types[ $object ]->labels->name;
					$type        = 'post';
				} elseif ( isset( $taxonomies[ $object ] ) ) {
					$group_label = $taxonomies[ $object ]->labels->name;
					$type        = 'term';
				} else {
					$group_label = $object;
					$type        = 'user';
				}

				if ( empty( $group_label ) ) {
					continue;
				}

				foreach ( $meta_fields as $field ) {
					if ( $field['object_type'] !== 'field' || ! in_array( $field['type'], $supports, true ) ) {
						continue;
					}

					$field['_brx_type']        = $type; // post, term, or user
					$field['_brx_object']      = $object; // object slug or user
					$field['_brx_group_label'] = $group_label;

					$fields[] = $field;
				}
			}
		}

		// STEP: Options page fields
		$options_pages = jet_engine()->options_pages->data->get_items();

		foreach ( $options_pages as $option_page ) {
			if ( empty( $option_page['meta_fields'] ) || empty( $option_page['slug'] ) ) {
				continue;
			}

			$page_fields = maybe_unserialize( $option_page['meta_fields'] );
			$labels      = maybe_unserialize( $option_page['labels'] );
			$page_label  = ! empty( $labels['name'] ) ? $labels['name'] : $option_page['slug'];

			foreach ( $page_fields as $field ) {
				if ( $field['object_type'] !== 'field' || ! in_array( $field['type'], $supports, true ) ) {
					continue;
				}

				$field['_brx_type']        = 'page'; // post, term, or user
				$field['_brx_object']      = $option_page['slug']; // page slug
				$field['_brx_group_label'] = $labels['name'];

				$fields[] = $field;
			}
		}

		return $fields;
	}

	public function register_relationships() {
		$relations = jet_engine()->relations->data->get_item_for_register();

		if ( empty( $relations ) ) {
			return;
		}

		foreach ( $relations as $relation ) {
			$label = ! empty( $relation['args']['labels']['name'] ) ? $relation['args']['labels']['name'] : $relation['id'];

			$relation['_brx_type'] = 'relationship';

			$tag_key = 'je_relation_' . $relation['id'];

			$tag = [
				'name'     => '{' . $tag_key . '}',
				'label'    => "JE Relation: {$label}",
				'group'    => 'JetEngine',
				'field'    => $relation,
				'provider' => $this->name,
			];

			$this->loop_tags[ $tag_key ] = $tag;

			// the relation has meta fields
			if ( ! empty( $relation['args']['meta_fields'] ) ) {
				foreach ( $relation['args']['meta_fields'] as $sub_field ) {

					$sub_field['_brx_type']        = 'relationship';
					$sub_field['_brx_object']      = $relation['id']; // Relation ID
					$sub_field['_brx_group_label'] = $label;

					$parent = [
						'id'    => $relation['id'],
						'title' => $label,
						'name'  => $tag_key
					];

					$this->register_tag( $sub_field, $parent );
				}
			}
		}
	}

	public function get_tag_value( $tag, $post, $args, $context ) {
		$post_id = isset( $post->ID ) ? $post->ID : '';

		$field = $this->tags[ $tag ]['field'];

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// STEP: Get the value
		$value = $this->get_raw_value( $tag, $post_id );

		// @since 1.8 - New array_val filter. Once used, we don't want to process the field type logic
		if ( isset( $filters['array_value'] ) && is_array( $value ) ) {
			// Force context to text
			$context = 'text';
			$value   = $this->return_array_value( $value, $filters );
		}

		// Process field type logic
		else {
			switch ( $field['type'] ) {
				case 'date':
					if ( ! empty( $value ) ) {
						if ( ! isset( $field['is_timestamp'] ) || ! $field['is_timestamp'] ) {
							// The value is a date string, change to timestamp
							$date = \DateTime::createFromFormat( 'Y-m-d', $value );

							// Prevent error if date is not valid due to unexpected issue
							if ( $date instanceof \DateTime ) {
								$value = $date->format( 'U' );
							}
						}

						$filters['object_type'] = 'date';
					}
					break;

				case 'datetime-local':
					if ( ! empty( $value ) ) {
						if ( ! isset( $field['is_timestamp'] ) || ! $field['is_timestamp'] ) {
							// The value is a date string, change to timestamp
							$date = \DateTime::createFromFormat( 'Y-m-d\TH:i', $value );

							// Prevent error if date is not valid due to unexpected issue
							if ( $date instanceof \DateTime ) {
								$value = $date->format( 'U' );
							}
						}

						$filters['object_type'] = 'datetime';
					}
					break;

				case 'time':
					if ( ! empty( $value ) ) {
						// The value is always a string in 24-hour format, convert to timestamp
						$value = strtotime( $value );

						if ( empty( $filters['meta_key'] ) ) {
							// If no meta_key is set, we force the meta_key format so Bricks :time filter can work
							$filters['meta_key'] = 'H:i';
						}

						$filters['object_type'] = 'datetime';
					}
					break;

				case 'media':
					$filters['object_type'] = 'media';
					$filters['separator']   = '';

					if ( isset( $field['value_format'] ) ) {
						if ( $field['value_format'] === 'url' ) {
							$value = attachment_url_to_postid( $value );
						} elseif ( $field['value_format'] === 'both' ) {
							$value = isset( $value['id'] ) ? $value['id'] : '';
						}
					}

					// Empty field value should return empty array to avoid default post title in text context. @see $this->format_value_for_context()
					$value = ! empty( $value ) ? [ $value ] : [];
					break;

				case 'gallery':
					$filters['object_type'] = 'media';
					$filters['separator']   = '';

					if ( isset( $field['value_format'] ) ) {
						if ( $field['value_format'] === 'id' ) {
							$value = explode( ',', $value );
						} elseif ( $field['value_format'] === 'url' ) {
							$value = explode( ',', $value );
							$value = array_map( 'attachment_url_to_postid', $value );
							$value = array_filter( $value );
						} elseif ( $field['value_format'] === 'both' ) {
							$value = wp_list_pluck( $value, 'id' );
						}
					} else {
						// Empty field value should return empty array to avoid default post title in text context. @see $this->format_value_for_context()
						$value = ! empty( $value ) ? explode( ',', $value ) : [];
					}

					break;

				case 'posts':
					if ( ! empty( $value ) ) {
						$filters['object_type'] = 'post';
						$filters['link']        = true;
					}

					break;
			}
		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	public function get_raw_value( $tag, $post_id ) {
		$tag_object = $this->tags[ $tag ];
		$field      = $tag_object['field'];

		// STEP: Check if in a Repeater loop or Relationship sub-field
		if ( \Bricks\Query::is_looping() ) {
			$query_type = \Bricks\Query::get_query_object_type();

			// Check if this loop belongs to this provider
			if ( array_key_exists( $query_type, $this->loop_tags ) ) {

				$parent_tag = $this->loop_tags[ $query_type ];

				// Check if the field is a sub-field of this loop field
				if (
					isset( $parent_tag['field']['id'] ) &&
					isset( $tag_object['parent']['id'] ) &&
					$parent_tag['field']['id'] === $tag_object['parent']['id']
				) {

					// Sub-field belongs to a relationship
					if ( $field['_brx_type'] === 'relationship' ) {
						// Get the relation object (based on the _brx_object which contains the relation ID)
						$relation = jet_engine()->relations->get_active_relations( $field['_brx_object'] );

						if ( ! $relation ) {
							return '';
						}

						// Retrieve the relationship sub meta field content
						return $relation->get_meta( \Bricks\Database::$page_data['preview_or_post_id'], $post_id, $field['name'] );
					}

					// Or, sub-field belongs to a repater
					$query_loop_object = \Bricks\Query::get_loop_object();

					// Sub-field not found in the loop object (array)
					if ( ! is_array( $query_loop_object ) || ! array_key_exists( $field['name'], $query_loop_object ) ) {
						return '';
					}

					return $query_loop_object[ $field['name'] ];
				}
			}
		}

		// STEP: Still here, get the regular value for this field
		return $this->get_jetengine_value( $field, $post_id );
	}

	public function get_jetengine_value( $field, $post_id ) {
		if ( $field['_brx_type'] === 'page' ) {
			// Options page meta fields
			// @see: https://gist.github.com/MjHead/49ebe7ecc20bff9aaf8516417ed27c38
			$value = jet_engine()->listings->data->get_option( "{$field['_brx_object']}::{$field['name']}" );
		} else {
			// Post, Term or User meta fields
			$object = $this->get_object( $field, $post_id );

			// @see: wp-content/plugins/jet-engine/includes/components/listings/data.php
			$value = jet_engine()->listings->data->get_meta( $field['name'], $object );
		}

		return $value;
	}

	/**
	 * Calculate the object to be used when fetching the field value
	 *
	 * @param array $field
	 * @param int   $post_id
	 * @return WP_Term|WP_User|WP_Post
	 */
	public function get_object( $field, $post_id ) {
		$type        = $field['_brx_type']; // post, term, or user
		$object_slug = $field['_brx_object']; // object slug or user

		if ( \Bricks\Query::is_looping() ) {
			$object_type = \Bricks\Query::get_loop_object_type();

			if ( $object_type === $type ) {
				return \Bricks\Query::get_loop_object();
			}
		}

		$queried_object = \Bricks\Helpers::get_queried_object( $post_id );

		if ( $type === 'term' && is_a( $queried_object, 'WP_Term' ) ) {
			return $queried_object;
		}

		if ( $type === 'user' ) {
			if ( is_a( $queried_object, 'WP_User' ) ) {
				return $queried_object;
			}

			return wp_get_current_user();
		}

		return get_post( $post_id );
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

		$field = $this->loop_tags[ $query->object_type ]['field'];

		$looping_query_id = \Bricks\Query::is_any_looping();

		if ( ! empty( $looping_query_id ) && \Bricks\Query::get_loop_object_type( $looping_query_id ) === 'post' ) {
			$post_id = get_the_ID();
		} else {
			// Get the $post_id or the template preview ID
			$post_id = \Bricks\Database::$page_data['preview_or_post_id'];
		}

		// Relationship
		if ( $field['_brx_type'] === 'relationship' ) {

			$relation = jet_engine()->relations->get_active_relations( $field['id'] );

			if ( ! $relation ) {
				return [];
			}

			// Default results getter
			$direction_getter = 'get_children'; // or 'get_parents';

			// JetEngine uses jet_engine()->listings->data->get_current_object_id() to get the $object_id but Bricks has to set the preview inside templates
			$queried_object = \Bricks\Helpers::get_queried_object( $post_id );
			$object_id      = 0;

			// STEP: Calculate the direction_getter
			foreach ( [
				'posts' => 'WP_Post',
				'terms' => 'WP_Term',
				'mix'   => 'WP_User'
			] as $object_type => $object_class ) {
				foreach ( [ 'parent_object', 'child_object' ] as $direction ) {
					if ( ! isset( $field['args'][ $direction ] ) ) {
						continue;
					}

					// e.g. $field['parent_object'] = 'posts::page'
					$objects = explode( '::', $field['args'][ $direction ] );

					$type    = $objects[0]; // posts, terms, mix
					$subtype = $objects[1]; // page, ..., category, ..., users (mix::users)

					// Queried object type is the same as the field direction object type
					if ( is_a( $queried_object, $object_class ) && $type == $object_type ) {
						if ( $type == 'posts' && $queried_object->post_type == $subtype || $object_type == 'mix' && $subtype == 'users' ) {
							$object_id = $queried_object->ID;
						} elseif ( $type == 'terms' && $queried_object->taxonomy == $subtype ) {
							$object_id = $queried_object->term_id;
						}
					}

					if ( ! empty( $object_id ) ) {
						$direction_getter = $direction == 'child_object' ? 'get_parents' : 'get_children';

						break( 2 );
					}
				}
			}

			// Get results. E.g. $results = $relation->get_parents( $object_id, 'ids' );
			$results = $relation->{$direction_getter}( $object_id, 'ids' );

			// Convert IDs into Objects (WP_Post, WP_Term, WP_User) to use it in set_loop_object() or fetching DD tags
			if ( $results ) {
				$results_object_type = $direction_getter == 'get_parents' ? $field['args']['parent_object'] : $field['args']['child_object'];

				if ( strpos( $results_object_type, 'posts::' ) === 0 ) {
					foreach ( $results as $key => $post_id ) {
						$results[ $key ] = get_post( $post_id );
					}
				} elseif ( strpos( $results_object_type, 'terms::' ) === 0 ) {
					$taxonomy = explode( '::', $results_object_type )[1];

					foreach ( $results as $key => $term_id ) {
						$results[ $key ] = get_term( $term_id, $taxonomy );
					}
				} elseif ( $results_object_type === 'mix::users' ) {
					foreach ( $results as $key => $user_id ) {
						$results[ $key ] = get_user_by( 'id', $user_id );
					}
				}
			}
		}

		// Or, regular field
		else {
			$results = $this->get_jetengine_value( $field, $post_id );
		}

		// If the field type is 'post' and the value is not an array, wrap it in an array (@since 1.9.4)
		if ( ! empty( $results ) && ! is_array( $results ) && isset( $field['type'] ) && $field['type'] === 'posts' ) {
			$results = [ $results ];
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

		// Check if the JetEngine field is posts (list of posts)
		$field = $this->loop_tags[ $query->object_type ]['field'];

		if ( isset( $field['type'] ) && $field['type'] === 'posts' || is_a( $loop_object, 'WP_Post' ) ) {
			global $post;
			$post = get_post( $loop_object );
			setup_postdata( $post );
		}

		return $loop_object;
	}

	/**
	 * Get all fields supported
	 *
	 * @return array
	 */
	private static function get_supported_field_types() {
		return [
			'text',
			'textarea',
			'wysiwyg',
			'number',
			'html',

			'date',
			'time',
			'datetime-local',

			'switcher',
			'checkbox',
			'radio',
			'select',

			// 'iconpicker',
			'media',
			'gallery',

			'repeater', // Query Loop

			'posts', // Query Loop (and regular field)

			'colorpicker',
		];
	}
}
