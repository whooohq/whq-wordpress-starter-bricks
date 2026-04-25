<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Query_Filters {
	const INDEX_TABLE_NAME   = 'bricks_filters_index';
	const ELEMENT_TABLE_NAME = 'bricks_filters_element';

	private static $instance = null;
	private static $index_table_name;
	private static $element_table_name;

	public static $filter_object_ids       = [];
	public static $active_filters          = [];
	public static $page_filters            = [];
	public static $query_vars_before_merge = [];
	public static $is_saving_post          = false;

	public function __construct() {
		global $wpdb;
		self::$index_table_name   = $wpdb->prefix . self::INDEX_TABLE_NAME;
		self::$element_table_name = $wpdb->prefix . self::ELEMENT_TABLE_NAME;

		if ( Helpers::enabled_query_filters() ) {
			add_action( 'wp', [ $this, 'maybe_set_page_filters' ], 100 );

			// Capture filter elements and index if needed
			add_action( 'update_post_meta', [ $this, 'maybe_update_element' ], 10, 4 );

			/** Hooks to listen so we can add new index record. Use largest priority */
			// Post
			add_action( 'save_post', [ $this, 'save_post' ], PHP_INT_MAX - 10, 2 );
			add_action( 'delete_post', [ $this, 'delete_post' ] );
			add_filter( 'wp_insert_post_parent', [ $this, 'wp_insert_post_parent' ], 10, 4 );
			add_action( 'set_object_terms', [ $this, 'set_object_terms' ], PHP_INT_MAX - 10, 6 );

			// Term
			add_action( 'edited_term', [ $this, 'edited_term' ], PHP_INT_MAX - 10, 3 );
			add_action( 'delete_term', [ $this, 'delete_term' ], 10, 4 );
		}
	}

	/**
	 * Singleton - Get the instance of this class
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Query_Filters();
		}

		return self::$instance;
	}

	public static function get_table_name( $table_name = 'index' ) {
		if ( $table_name === 'element' ) {
			return self::$element_table_name;
		}

		return self::$index_table_name;
	}

	public static function check_managed_db_access() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Create custom database table for storing filter index
	 */
	public function maybe_create_tables() {
		if ( ! self::check_managed_db_access() ) {
			return;
		}

		$this->create_index_table();
		$this->create_element_table();
	}

	private function create_index_table() {
		global $wpdb;

		$index_table_name = self::get_table_name();

		// Return: Table already exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $index_table_name ) ) === $index_table_name ) {
			return;
		}

		// Create table
		$charset_collate = $wpdb->get_charset_collate();

		/**
		 * Table columns:
		 * filter_id: The unique 6-character filter element ID
		 * object_id: The ID of the post/page
		 * object_type: The type of object (post, page, etc.)
		 * filter_value: The value of the filter
		 * filter_value_display: The value of the filter (displayed)
		 * filter_value_id: The ID of the filter value (if applicable)
		 * filter_value_parent: The parent ID of the filter value (if applicable)
		 *
		 * Indexes:
		 * filter_id_idx (filter_id)
		 * object_id_idx (object_id)
		 * filter_id_object_id_idx (filter_id, object_id)
		 */
		$sql = "CREATE TABLE {$index_table_name} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			filter_id CHAR(6) NOT NULL,
			object_id INT UNSIGNED,
			object_type VARCHAR(50),
			filter_value VARCHAR(255),
			filter_value_display VARCHAR(255),
			filter_value_id INT UNSIGNED default '0',
			filter_value_parent INT UNSIGNED default '0',
			PRIMARY KEY  (id),
			KEY filter_id_idx (filter_id),
			KEY object_id_idx (object_id),
			KEY filter_id_object_id_idx (filter_id, object_id)
    ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}

	private function create_element_table() {
		global $wpdb;

		$element_table_name = self::get_table_name( 'element' );

		// Return: Table already exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $element_table_name ) ) === $element_table_name ) {
			return;
		}

		// Create table
		$charset_collate = $wpdb->get_charset_collate();

		/**
		 * This table is used to store all filter elements created across the site
		 *
		 * When a post update or save, we will loop through all filter elements and update the index table
		 * Table columns:
		 * filter_id: The unique 6-character filter element ID
		 * filter_action: The action of the filter element (filter, sort)
		 * status: The status of the filter element (0, 1)
		 * indexable: Whether this filter element is indexable (0, 1)
		 * settings: The settings of the filter element
		 * post_id: The ID of this filter element located in
		 *
		 * Indexes:
		 * filter_id_idx (filter_id)
		 * filter_action_idx (filter_action)
		 * status_idx (status)
		 * indexable_idx (indexable)
		 * post_id_idx (post_id)
		 */

		$sql = "CREATE TABLE {$element_table_name} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			filter_id CHAR(6) NOT NULL,
			filter_action VARCHAR(50),
			status INT UNSIGNED default '0',
			indexable INT UNSIGNED default '0',
			settings LONGTEXT,
			post_id INT UNSIGNED,
			PRIMARY KEY  (id),
			KEY filter_id_idx (filter_id),
			KEY filter_action_idx (filter_action),
			KEY status_idx (status),
			KEY indexable_idx (indexable),
			KEY post_id_idx (post_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}

	/**
	 * Return array of element names that support query filters
	 *
	 * These elements have filter settings.
	 * Pagination is one of them but it's filter setting handled in /includes/elements/pagination.php set_ajax_attributes()
	 */
	public static function supported_element_names() {
		return [
			'filter-checkbox',
			'filter-datepicker',
			'filter-radio',
			'filter-range',
			'filter-search',
			'filter-select',
			'filter-submit',
			'filter-range',
		];
	}

	/**
	 * Dynamic update elements names
	 * - These elements will be updated dynamically when the filter AJAX is called
	 */
	public static function dynamic_update_elements() {
		return [
			'filter-checkbox',
			'filter-datepicker',
			'filter-radio',
			'filter-range',
			'filter-search',
			'filter-select',
			'filter-range',
		];
	}

	/**
	 * Indexable elements names
	 * - These elements will be indexed in the index table
	 */
	public static function indexable_elements() {
		return [
			'filter-checkbox',
			'filter-datepicker',
			'filter-radio',
			'filter-select',
			'filter-range',
		];
	}

	/**
	 * Set page filters manually on wp hook:
	 * Example: In archive page, taxonomy page, etc.
	 */
	public function maybe_set_page_filters() {
		// Check if this is taxonomy page
		if ( is_tax() || is_category() || is_tag() || is_post_type_archive() ) {
			// What is current taxonomy?
			$queried_object = get_queried_object();

			$taxonomy = $queried_object->taxonomy ?? false;

			if ( ! $taxonomy ) {
				return;
			}

			// Set current page filters so each filter element can disabled as needed
			self::$page_filters[ $taxonomy ] = $queried_object->slug;
		}
	}

	/**
	 * Hook into update_post_meta, if filter element found, update the index table
	 */
	public function maybe_update_element( $meta_id, $object_id, $meta_key, $meta_value ) {
		// Only listen to header, content, footer
		if ( ! in_array( $meta_key, [ BRICKS_DB_PAGE_HEADER, BRICKS_DB_PAGE_CONTENT, BRICKS_DB_PAGE_FOOTER ], true ) ) {
			return;
		}

		$filter_elements = [];
		// Get all filter elements from meta_value
		foreach ( $meta_value as $element ) {
			$element_id = $element['id'] ?? false;

			if ( ! $element_id ) {
				continue;
			}

			$element_name = $element['name'] ?? false;

			if ( ! in_array( $element_name, self::supported_element_names(), true ) ) {
				continue;
			}

			$filter_elements[ $element_id ] = $element;
		}

		// Update element table
		$updated_data = $this->update_element_table( $filter_elements, $object_id );

		// Now we need to update the index table by using the updated_data
		$this->update_index_table( $updated_data );
	}

	/**
	 *  Decide whether create, update or delete elements in the element table
	 *  Return: array of new_elements, updated_elements, deleted_elements
	 *  Index table will use the return data to decide what to do
	 */
	private function update_element_table( $elements, $post_id ) {
		// Get all elements from element table where post_id = $post_id
		$all_db_elements = $this->get_elements_from_element_table(
			[
				'post_id' => $post_id,
			]
		);

		// Just get the filter_id
		$all_db_elements_ids = array_column( $all_db_elements, 'filter_id' );

		$update_data = [
			'new_elements'     => [],
			'updated_elements' => [],
			'deleted_elements' => [],
		];

		// Loop through all elements from element table
		foreach ( $all_db_elements_ids as $key => $db_element_id ) {
			// If this element is not in the new elements, delete it
			if ( ! isset( $elements[ $db_element_id ] ) ) {
				$this->delete_element( $db_element_id );
				$update_data['deleted_elements'][] = $all_db_elements[ $key ];
			}
		}

		// Loop through all elements, create or update them into element table
		foreach ( $elements as $element ) {
			$element_id = $element['id'] ?? false;

			if ( ! $element_id ) {
				continue;
			}

			$filter_settings = $element['settings'] ?? [];
			$filter_action   = $filter_settings['filterAction'] ?? 'filter';
			$indexable       = in_array( $element['name'], self::indexable_elements(), true ) && 'filter' === $filter_action ? 1 : 0;

			$element_data = [
				'filter_id'     => $element_id,
				'filter_action' => $filter_action,
				'status'        => true,
				'indexable'     => $indexable,
				'settings'      => wp_json_encode( $filter_settings ),
				'post_id'       => $post_id,
			];

			// If this element is not in the db elements, create it
			if ( ! in_array( $element_id, $all_db_elements_ids, true ) ) {
				$this->create_element( $element_data );
				$update_data['new_elements'][] = $element_data;
			} else {
				// If this element is in the db elements, update it
				$this->update_element( $element_data );
				// TODO: We should check if filter-related settings are changed, to avoid always reindexing.
				$update_data['updated_elements'][] = $element_data;
			}
		}

		return $update_data;
	}

	/**
	 * Remove index DB table and recreate it.
	 * Retrieve all indexable elements from element table.
	 * Index based on the element settings.
	 */
	public function reindex() {
		if ( ! self::check_managed_db_access() ) {
			return [ 'error' => 'Access denied (current user can\'t manage_options)' ];
		}

		global $wpdb;

		$table_name = self::get_table_name();

		// Drop table
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Create table so latest DB structure is used
		$this->create_index_table();

		// Exit if table does not exist
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return [ 'error' => "Table {$table_name} does not exist" ];
		}

		// Get all indexable elements from element table
		$indexable_elements = $this->get_elements_from_element_table(
			[
				'indexable' => 1,
				'status'    => 1,
			]
		);

		$element_data = [
			'new_elements'     => [],
			'deleted_elements' => [],
			'updated_elements' => $indexable_elements,
		];

		$this->update_index_table( $element_data );

		return true;
	}

	/**
	 * Update index table based on the updated_data
	 * updated_data holds new_elements, updated_elements, deleted_elements
	 * 1. Remove all rows related to the deleted_elements
	 * 2. Generate index for all new_elements and updated_elements
	 */
	private function update_index_table( $updated_data ) {
		// STEP: Handle deleted elements
		foreach ( $updated_data['deleted_elements'] as $deleted_element ) {
			$id = $deleted_element['filter_id'] ?? false;
			if ( ! $id ) {
				continue;
			}

			// Remove rows related to this filter_id
			self::remove_index_rows( [ 'filter_id' => $id ] );
		}

		// STEP: Handle new elements & updated elements (we can retrieve from database again but we already have the data)
		$elements = array_merge( $updated_data['new_elements'], $updated_data['updated_elements'] );

		// Only get elements that are indexable, status is 1, and filter_action is filter
		$indexable_elements = array_filter(
			$elements,
			function ( $element ) {
				$indexable     = $element['indexable'] ?? false;
				$status        = $element['status'] ?? false;
				$filter_action = $element['filter_action'] ?? false;

				if ( ! $indexable || ! $status || $filter_action !== 'filter' ) {
					return false;
				}

				return true;
			}
		);

		// Loop through all indexable elements and group them up by filter_source
		$grouped_elements = [];

		foreach ( $indexable_elements as $element ) {
			// filter_settings is json string
			$filter_settings = json_decode( $element['settings'], true );
			$filter_source   = $filter_settings['filterSource'] ?? false;

			if ( ! $filter_source ) {
				continue;
			}

			// Update filter_settings properly
			$element['settings'] = $filter_settings;

			if ( $filter_source === 'taxonomy' ) {
				$filter_taxonomy = $filter_settings['filterTaxonomy'] ?? false;
				if ( ! $filter_taxonomy ) {
					continue;
				}
				$key                        = $filter_source . '|' . $filter_taxonomy;
				$grouped_elements[ $key ][] = $element;
			} else {
				// wpField, customField
				$grouped_elements[ $filter_source ][] = $element;
			}
		}

		// Loop through all grouped elements and generate index
		foreach ( $grouped_elements as $source => $elements ) {
			// NOTE: 'exclude_from_search' => false not in use as we might miss some post types which are excluded from search
			$post_types = get_post_types();

			$args = [
				'post_type'      => $post_types,
				'post_status'    => 'any', // cannot use 'publish' as we might miss some posts
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'cache_results'  => false,
				'no_found_rows'  => true,
			];

			$rows_to_insert = [];
			// Build $rows
			switch ( $source ) {
				case 'wpField':
					$post_fields = [];
					foreach ( $elements as $element ) {
						// Check what is the selected field
						$filter_settings = $element['settings'];
						$field_type      = $filter_settings['sourceFieldType'] ?? 'post';

						if ( ! $field_type ) {
							continue;
						}

						$selected_field = false;
						switch ( $field_type ) {
							case 'post':
								$selected_field = $filter_settings['wpPostField'] ?? false;

								if ( ! $selected_field ) {
									continue 2;
								}

								if ( isset( $post_fields[ $selected_field ] ) ) {
									$post_fields[ $selected_field ][] = $element['filter_id'];
								} else {
									$post_fields[ $selected_field ] = [ $element['filter_id'] ];
								}

								break;

							case 'user':
								$selected_field = $filter_settings['wpUserField'] ?? false;
								break;

							case 'term':
								$selected_field = $filter_settings['wpTermField'] ?? false;
								break;
						}
					}

					if ( ! empty( $post_fields ) ) {
						unset( $args['fields'] );
						$query = new \WP_Query( $args );
						$posts = $query->posts;

						// Generate rows for each post_field
						foreach ( $post_fields as $post_field => $filter_ids ) {
							$rows_for_this_post_field = self::generate_post_field_index_rows( $posts, $post_field );

							// Build $rows_to_insert
							if ( ! empty( $rows_for_this_post_field ) && ! empty( $filter_ids ) ) {
								// Add filter_id to each row, row is the standard template, do not overwrite it.
								foreach ( $filter_ids as $filter_id ) {
									$rows_to_insert = array_merge(
										$rows_to_insert,
										array_map(
											function( $row ) use ( $filter_id ) {
												$row['filter_id'] = $filter_id;

												return $row;
											},
											$rows_for_this_post_field
										)
									);
								}
							}

							// Execute remove_index_rows for each filter_id
							foreach ( $filter_ids as $filter_id ) {
								self::remove_index_rows(
									[
										'filter_id' => $filter_id,
									]
								);
							}
						}
					}
					break;

				case 'customField':
					$meta_keys = [];

					// Gather all meta keys from each element settings
					foreach ( $elements as $element ) {
						// filter_settings is json string
						$filter_settings = $element['settings'];
						$meta_key        = $filter_settings['customFieldKey'] ?? false;

						if ( ! $meta_key ) {
							continue;
						}

						// Add filter_id to existing meta_key, so we can add filter_id for each row later
						if ( isset( $meta_keys[ $meta_key ] ) ) {
							$meta_keys[ $meta_key ][] = $element['filter_id'];
						} else {
							$meta_keys[ $meta_key ] = [ $element['filter_id'] ];
						}
					}

					if ( empty( $meta_keys ) ) {
						continue 2;
					}

					// Generate rows for each meta_key
					foreach ( $meta_keys as $meta_key => $filter_ids ) {
						// Add meta query
						$args['meta_query'] = [
							[
								'key'     => $meta_key,
								'compare' => 'EXISTS'
							],
						];

						$query = new \WP_Query( $args );

						$post_ids = $query->posts;

						$rows_for_this_meta_key = self::generate_custom_field_index_rows( $post_ids, $meta_key );

						// Build $rows_to_insert
						if ( ! empty( $rows_for_this_meta_key ) && ! empty( $filter_ids ) ) {
							// Add filter_id to each row, row is the standard template, do not overwrite it. insert rows_to_insert instead after foreach loop
							foreach ( $filter_ids as $filter_id ) {
								$rows_to_insert = array_merge(
									$rows_to_insert,
									array_map(
										function( $row ) use ( $filter_id ) {
											$row['filter_id'] = $filter_id;

											return $row;
										},
										$rows_for_this_meta_key
									)
								);
							}
						}

						// Execute remove_index_rows for each filter_id
						foreach ( $filter_ids as $filter_id ) {
							self::remove_index_rows(
								[
									'filter_id' => $filter_id,
								]
							);
						}
					}

					break;

				default:
				case 'taxonomy|xxx':
					// explode the key
					$keys            = explode( '|', $source );
					$filter_source   = $keys[0] ?? false;
					$filter_taxonomy = $keys[1] ?? false;

					if ( ! $filter_source || ! $filter_taxonomy ) {
						continue 2;
					}

					// Add taxonomy query
					$args['tax_query'] = [
						[
							'taxonomy' => $filter_taxonomy,
							'operator' => 'EXISTS'
						],
					];

					$query    = new \WP_Query( $args );
					$post_ids = $query->posts;

					$rows_for_this_taxonomy = self::generate_taxonomy_index_rows( $post_ids, $filter_taxonomy );

					// Add filter_id to each row, filter_ids are inside $elements
					$filter_ids = array_column( $elements, 'filter_id' );

					// Build $rows_to_insert
					if ( ! empty( $rows_for_this_taxonomy ) && ! empty( $filter_ids ) ) {
						foreach ( $filter_ids as $filter_id ) {
							// Add filter_id to each row, row is the standard template, do not overwrite it. insert rows_to_insert instead after foreach loop
							$rows_to_insert = array_merge(
								$rows_to_insert,
								array_map(
									function( $row ) use ( $filter_id ) {
										$row['filter_id'] = $filter_id;

										return $row;
									},
									$rows_for_this_taxonomy
								)
							);
						}
					}

					// Execute remove_index_rows for each filter_id
					foreach ( $filter_ids as $filter_id ) {
						self::remove_index_rows(
							[
								'filter_id' => $filter_id,
							]
						);
					}
					break;

			}

			// Insert rows into database
			if ( ! empty( $rows_to_insert ) ) {
				self::insert_index_rows( $rows_to_insert );
			}
		}
	}

	/**
	 * Get all elements from element table where post_id = $post_id
	 */
	private function get_elements_from_element_table( $args = [] ) {
		global $wpdb;

		$table_name = self::get_table_name( 'element' );

		// Initialize an empty array to store placeholders and values
		$placeholders = [];
		$values       = [];
		$where_clause = '';

		// Loop through all args and build where clause
		foreach ( $args as $key => $value ) {
			$placeholders[] = $key . ' = %s';
			$values[]       = $value;
		}

		// If we have placeholders, build where clause
		if ( ! empty( $placeholders ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $placeholders );
		}

		$query = "SELECT * FROM {$table_name} {$where_clause}";

		$all_elements = $wpdb->get_results( $wpdb->prepare( $query, $values ), ARRAY_A );

		return $all_elements ?? [];
	}

	/**
	 * Delete element from element table
	 */
	private function delete_element( $element_id ) {
		global $wpdb;

		$table_name = self::get_table_name( 'element' );

		$wpdb->delete(
			$table_name,
			[
				'filter_id' => $element_id,
			]
		);
	}

	/**
	 * Create element in element table
	 */
	private function create_element( $element_data ) {
		global $wpdb;

		$table_name = self::get_table_name( 'element' );

		$element_id = $element_data['filter_id'] ?? false;

		if ( ! $element_id ) {
			return;
		}

		// Insert element into element table
		$wpdb->insert( $table_name, $element_data );
	}

	/**
	 * Update element in element table
	 */
	private function update_element( $element_data ) {
		global $wpdb;

		$table_name = self::get_table_name( 'element' );

		$element_id = $element_data['filter_id'] ?? false;

		if ( ! $element_id ) {
			return;
		}

		// Update element in element table
		$wpdb->update(
			$table_name,
			$element_data,
			[
				'filter_id' => $element_id,
			]
		);
	}

	/**
	 * Generate index records for a given taxonomy
	 */
	public static function generate_taxonomy_index_rows( $all_posts_ids, $taxonomy ) {

		$rows = [];
		// Loop through all posts
		foreach ( $all_posts_ids as $post_id ) {
			$terms = get_the_terms( $post_id, $taxonomy );

			// If no terms, skip
			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}

			// Loop through all terms
			foreach ( $terms as $term ) {
				// Populate rows
				$rows[] = [
					'filter_id'            => '',
					'object_id'            => $post_id,
					'object_type'          => 'post',
					'filter_value'         => $term->slug,
					'filter_value_display' => $term->name,
					'filter_value_id'      => $term->term_id,
					'filter_value_parent'  => $term->parent ?? 0,
				];
			}
		}

		return $rows;

	}

	/**
	 * Remove rows from database
	 */
	public static function remove_index_rows( $args = [] ) {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( empty( $args ) ) {
			return;
		}

		// Remove rows
		$wpdb->delete( $table_name, $args );
	}

	/**
	 * Insert rows into database
	 */
	public static function insert_index_rows( $rows ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$insert_values = [];

		foreach ( $rows as $row ) {
			$insert_values[] = $wpdb->prepare(
				'( %s, %d, %s, %s, %s, %d, %d )',
				$row['filter_id'],
				$row['object_id'],
				$row['object_type'],
				$row['filter_value'],
				$row['filter_value_display'],
				$row['filter_value_id'],
				$row['filter_value_parent']
			);
		}

		if ( ! empty( $insert_values ) ) {
			$insert_query = "INSERT INTO {$table_name}
			( filter_id, object_id, object_type, filter_value, filter_value_display, filter_value_id, filter_value_parent )
			VALUES " . implode( ', ', $insert_values );

			$wpdb->query( $insert_query );
		}

	}

	/**
	 * Generate index records for a given custom field
	 */
	public static function generate_custom_field_index_rows( $post_ids, $meta_key ) {
		$rows = [];

		// Loop through all posts
		foreach ( $post_ids as $post_id ) {
			$meta_value = get_post_meta( $post_id, $meta_key, true );

			// Populate rows
			$rows[] = [
				'filter_id'            => '',
				'object_id'            => $post_id,
				'object_type'          => 'post',
				'filter_value'         => $meta_value,
				'filter_value_display' => $meta_value,
				'filter_value_id'      => 0,
				'filter_value_parent'  => 0,
			];
		}

		return $rows;

	}

	/**
	 * Generate index records for a given post field.
	 *
	 * @param array  $posts Array of post objects
	 * @param string $post_field The post field to be used
	 */
	public static function generate_post_field_index_rows( $posts, $post_field ) {

		$rows = [];

		// Change field name if needed so we can get it from post object
		$post_field = $post_field === 'post_id' ? 'ID' : $post_field;

		// Loop through all posts and get the post fields value
		foreach ( $posts as $post ) {
			if ( ! is_a( $post, 'WP_Post' ) ) {
				continue;
			}

			// Populate rows
			$value         = $post->$post_field ?? false;
			$display_value = $value ?? 'None';

			// If post field is post_author, get the author name
			if ( $post_field === 'post_author' ) {
				$author        = get_user_by( 'id', $value );
				$display_value = $author->display_name ?? 'None';
			}

			$rows[] = [
				'filter_id'            => '',
				'object_id'            => $post->ID,
				'object_type'          => 'post',
				'filter_value'         => $value,
				'filter_value_display' => $display_value,
				'filter_value_id'      => 0,
				'filter_value_parent'  => 0,
			];
		}

		return $rows;

	}

	/**
	 * Updated filters to be used in frontend after each filter ajax request
	 */
	public static function get_updated_filters( $filters = [], $post_id = 0, $query = null ) {
		$updated_filters = [];

		// Loop through all filter_ids and gather elements that need to be updated
		$valid_elements = [];
		$active_filters = [];

		foreach ( $filters as $filter_id => $current_value ) {
			$element_data   = Helpers::get_element_data( $post_id, $filter_id );
			$filter_element = $element_data['element'] ?? false;

			// Check if $filter_element exists
			if ( ! $filter_element || empty( $filter_element ) ) {
				continue;
			}

			if ( ! in_array( $filter_element['name'], self::dynamic_update_elements(), true ) ) {
				continue;
			}

			$filter_settings = $filter_element['settings'] ?? [];
			$filter_action   = $filter_settings['filterAction'] ?? 'filter';

			// Skip: filter_action is not set to filter
			if ( $filter_action !== 'filter' ) {
				continue;
			}

			$has_value = false;

			// $current_value can be an array, check value is not empty too
			if ( is_array( $current_value ) ) {
				// Ensure all values are not empty
				$values_in_array = array_filter( $current_value, 'strlen' );
				if ( ! empty( $values_in_array ) ) {
					$has_value = true;
				}
			} elseif ( ! empty( $current_value ) ) {
				$has_value = true;
			}

			// Has value, set it as active filter and update filter element settings
			if ( $has_value ) {
				$filter_element['settings']['filterValue'] = $current_value;
				$active_filters[ $filter_id ]              = $current_value;
			}

			// Valid elements will regenerate new HTML
			$valid_elements[ $filter_id ] = $filter_element;
		}

		// Set active filters (ensure unique filters)
		self::$active_filters = empty( self::$active_filters ) ? $active_filters : array_unique( array_merge( self::$active_filters, $active_filters ) );

		// Loop through all valid elements and generate index
		foreach ( $valid_elements as $filter_id => $element ) {
			$updated_filters[ $filter_id ] = Frontend::render_element( $element );
		}

		return $updated_filters;
	}

	/**
	 * Get filtered data from index table
	 */
	public static function get_filtered_data_from_index( $filter_id = '', $object_ids = [] ) {
		if ( empty( $filter_id ) ) {
			return [];
		}

		global $wpdb;

		$table_name = self::get_table_name();

		$where_clause = '';
		$params       = [ $filter_id ];

		// If object_ids is set, add to where clause
		if ( ! empty( $object_ids ) ) {
			$placeholders = array_fill( 0, count( $object_ids ), '%d' );
			$placeholders = implode( ',', $placeholders );
			$where_clause = "AND object_id IN ({$placeholders})";
			$params       = array_merge( $params, $object_ids );
		}

		$sql = "SELECT filter_value, filter_value_display, filter_value_id, filter_value_parent, COUNT(DISTINCT object_id) AS count
		FROM {$table_name}
		WHERE filter_id = %s {$where_clause}
		GROUP BY filter_value, filter_value_display, filter_value_id, filter_value_parent";

		// Get all filter values for this filter_id
		$filter_values = $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				$params
			),
			ARRAY_A
		);

		return $filter_values ?? [];
	}

	/**
	 * Get all possible object ids from a query
	 * To be used in get_filtered_data()
	 * Each query_id will only be queried once
	 *
	 * @param string $query_id
	 * @return array $all_posts_ids
	 */
	public static function get_filter_object_ids( $query_id = '', $source = 'history' ) {
		if ( empty( $query_id ) ) {
			return [];
		}

		$cache_key = $query_id . '_' . $source;
		// Check if query_id is inside self::$filter_object_ids, if yes, return the object_ids
		if ( isset( self::$filter_object_ids[ $cache_key ] ) ) {
			return self::$filter_object_ids[ $cache_key ];
		}

		$query_data = Query::get_query_by_element_id( $query_id );

		// Return empty array if query_data is empty
		if ( ! $query_data ) {
			return [];
		}

		$query_vars = $query_data->query_vars ?? [];

		if ( $source === 'original' && isset( self::$query_vars_before_merge[ $query_id ] ) ) {
			$query_vars = self::$query_vars_before_merge[ $query_id ];
		}

		$query_type = $query_data->object_type ?? 'post';

		// Beta only support post query type
		if ( $query_type !== 'post' ) {
			return [];
		}

		// Use the query_vars and get all possible post ids
		$all_posts_args = array_merge(
			$query_vars,
			[
				'paged'                  => 1,
				'posts_per_page'         => -1,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'cache_results'          => false,
				'no_found_rows'          => true,
				'nopaging'               => true,
				'fields'                 => 'ids',
			]
		);

		$all_posts = new \WP_Query( $all_posts_args );

		$all_posts_ids = $all_posts->posts;

		// Store the object_ids in self::$filter_object_ids
		self::$filter_object_ids[ $cache_key ] = $all_posts_ids;

		return $all_posts_ids;
	}

	/**
	 * Generate index when a post is saved
	 */
	public function save_post( $post_id, $post ) {

		// Revision
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// auto-draft
		if ( $post->post_status === 'auto-draft' ) {
			return;
		}

		$this->index_post( $post_id );
		self::$is_saving_post = false;
	}

	/**
	 * Remove index when a post is deleted
	 */
	public function delete_post( $post_id ) {
		// Remove rows related to this post_id
		self::remove_index_rows(
			[
				'object_id'   => $post_id,
				'object_type' => 'post',
			]
		);
	}

	/**
	 * Set is_saving_post to true when a post is assigned to a parent to avoid reindexing
	 * Triggered when using wp_insert_post()
	 */
	public function wp_insert_post_parent( $post_parent, $post_id, $new_postarr, $postarr ) {
		// Set is_saving_post to true
		self::$is_saving_post = true;

		return $post_parent;
	}

	/**
	 * Generate index when a post is assigened to a term
	 * Triggered when using wp_set_post_terms() or wp_set_object_terms()
	 */
	public function set_object_terms( $object_id ) {
		if ( self::$is_saving_post ) {
			return;
		}

		$this->index_post( $object_id );
	}

	/**
	 * Generate index records for a given post
	 * TODO: Maybe some of the code can be refactored (update_index_table)
	 *
	 * @param int $post_id
	 */
	public function index_post( $post_id ) {
		if ( empty( $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );

		// Get all indexable and active filter elements from element table
		$indexable_elements = $this->get_elements_from_element_table(
			[
				'indexable' => 1,
				'status'    => 1
			]
		);

		if ( empty( $indexable_elements ) ) {
			return;
		}

		// Loop through all indexable elements and group them up by filter_source
		$grouped_elements = [];

		foreach ( $indexable_elements as $element ) {
			// filter_settings is json string
			$filter_settings = json_decode( $element['settings'], true );
			$filter_source   = $filter_settings['filterSource'] ?? false;

			if ( ! $filter_source ) {
				continue;
			}

			// Update filter_settings properly
			$element['settings'] = $filter_settings;

			if ( $filter_source === 'taxonomy' ) {
				$filter_taxonomy = $filter_settings['filterTaxonomy'] ?? false;
				if ( ! $filter_taxonomy ) {
					continue;
				}
				$key                        = $filter_source . '|' . $filter_taxonomy;
				$grouped_elements[ $key ][] = $element;
			} else {
				$grouped_elements[ $filter_source ][] = $element;
			}

		}

		// Loop through all grouped elements and generate index
		foreach ( $grouped_elements as $source => $elements ) {
			$rows_to_insert = [];

			// Build $rows
			switch ( $source ) {
				case 'wpField':
					$post_fields = [];
					foreach ( $elements as $element ) {
						// check what is the selected field
						$filter_settings = $element['settings'];
						$field_type      = $filter_settings['sourceFieldType'] ?? 'post';

						if ( ! $field_type ) {
							continue;
						}

						$selected_field = false;
						switch ( $field_type ) {
							case 'post':
								$selected_field = $filter_settings['wpPostField'] ?? false;

								if ( ! $selected_field ) {
									continue 2;
								}

								if ( isset( $post_fields[ $selected_field ] ) ) {
									$post_fields[ $selected_field ][] = $element['filter_id'];
								} else {
									$post_fields[ $selected_field ] = [ $element['filter_id'] ];
								}

								break;

							case 'user':
								$selected_field = $filter_settings['wpUserField'] ?? false;
								break;

							case 'term':
								$selected_field = $filter_settings['wpTermField'] ?? false;
								break;
						}
					}

					if ( ! empty( $post_fields ) ) {
						// Generate rows for each post_field
						foreach ( $post_fields as $post_field => $filter_ids ) {

							$rows_for_this_post_field = self::generate_post_field_index_rows( [ $post ], $post_field );

							// Build $rows_to_insert
							if ( ! empty( $rows_for_this_post_field ) && ! empty( $filter_ids ) ) {
								// Add filter_id to each row, row is the standard template, do not overwrite it.
								foreach ( $filter_ids as $filter_id ) {
									$rows_to_insert = array_merge(
										$rows_to_insert,
										array_map(
											function( $row ) use ( $filter_id ) {
												$row['filter_id'] = $filter_id;

												return $row;
											},
											$rows_for_this_post_field
										)
									);
								}
							}

							// Remove rows related to this filter_id and post_id
							foreach ( $filter_ids as $filter_id ) {
								self::remove_index_rows(
									[
										'filter_id' => $filter_id,
										'object_id' => $post_id,
									]
								);
							}
						}

					}

					break;

				case 'customField':
					$meta_keys = [];

					// Gather all meta keys from each element settings
					foreach ( $elements as $element ) {
						// filter_settings is json string
						$filter_settings = $element['settings'];
						$meta_key        = $filter_settings['customFieldKey'] ?? false;

						if ( ! $meta_key ) {
							continue;
						}

						// Add filter_id to existing meta_key, so we can add filter_id for each row later
						if ( isset( $meta_keys[ $meta_key ] ) ) {
							$meta_keys[ $meta_key ][] = $element['filter_id'];
						} else {
							$meta_keys[ $meta_key ] = [ $element['filter_id'] ];
						}
					}

					if ( empty( $meta_keys ) ) {
						continue 2;
					}

					// Generate rows for each meta_key
					foreach ( $meta_keys as $meta_key => $filter_ids ) {
						// Check if this meta_key exists on $post_id
						if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
							continue;
						}

						$rows_for_this_meta_key = self::generate_custom_field_index_rows( [ $post_id ], $meta_key );

						// Build $rows_to_insert
						if ( ! empty( $rows_for_this_meta_key ) && ! empty( $filter_ids ) ) {
							// Add filter_id to each row, row is the standard template, do not overwrite it. insert rows_to_insert instead after foreach loop
							foreach ( $filter_ids as $filter_id ) {
								$rows_to_insert = array_merge(
									$rows_to_insert,
									array_map(
										function( $row ) use ( $filter_id ) {
											$row['filter_id'] = $filter_id;

											return $row;
										},
										$rows_for_this_meta_key
									)
								);
							}
						}

						// Remove rows related to this filter_id and post_id
						foreach ( $filter_ids as $filter_id ) {
							self::remove_index_rows(
								[
									'filter_id' => $filter_id,
									'object_id' => $post_id,
								]
							);
						}

					}

					break;

				default:
				case 'taxonomy|xxx':
					// explode the key
					$keys            = explode( '|', $source );
					$filter_source   = $keys[0] ?? false;
					$filter_taxonomy = $keys[1] ?? false;

					if ( ! $filter_source || ! $filter_taxonomy ) {
						continue 2;
					}

					$rows_for_this_taxonomy = self::generate_taxonomy_index_rows( [ $post_id ], $filter_taxonomy );

					// Add filter_id to each row, filter_ids are inside $elements
					$filter_ids = array_column( $elements, 'filter_id' );

					// Build $rows_to_insert
					if ( ! empty( $rows_for_this_taxonomy ) && ! empty( $filter_ids ) ) {
						foreach ( $filter_ids as $filter_id ) {
							// Add filter_id to each row, row is the standard template, do not overwrite it. insert rows_to_insert instead after foreach loop
							$rows_to_insert = array_merge(
								$rows_to_insert,
								array_map(
									function( $row ) use ( $filter_id ) {
										$row['filter_id'] = $filter_id;

										return $row;
									},
									$rows_for_this_taxonomy
								)
							);
						}
					}

					// Remove rows related to this filter_id and post_id
					foreach ( $filter_ids as $filter_id ) {
						self::remove_index_rows(
							[
								'filter_id' => $filter_id,
								'object_id' => $post_id,
							]
						);
					}

					break;
			}

			// Insert rows into database
			if ( ! empty( $rows_to_insert ) ) {
				self::insert_index_rows( $rows_to_insert );
			}
		}

	}

	/**
	 * Update indexed records when a term is amended (slug, name)
	 */
	public function edited_term( $term_id, $tt_id, $taxonomy ) {

		// Get all indexable and active filter elements from element table
		$indexable_elements = $this->get_elements_from_element_table(
			[
				'indexable' => 1,
				'status'    => 1
			]
		);

		if ( empty( $indexable_elements ) ) {
			return;
		}

		// Only get filter elements that use taxonomy as filter source and filter taxonomy is the same as $taxonomy
		$taxonomy_elements = array_filter(
			$indexable_elements,
			function( $element ) use ( $taxonomy ) {
				$filter_settings = json_decode( $element['settings'], true );
				$filter_source   = $filter_settings['filterSource'] ?? false;
				$filter_taxonomy = $filter_settings['filterTaxonomy'] ?? false;

				if ( $filter_source !== 'taxonomy' || $filter_taxonomy !== $taxonomy ) {
					return false;
				}

				return true;
			}
		);

		if ( empty( $taxonomy_elements ) ) {
			return;
		}

		global $wpdb;
		$table_name    = self::get_table_name();
		$placeholders  = array_fill( 0, count( $taxonomy_elements ), '%s' );
		$placeholders  = implode( ',', $placeholders );
		$term          = get_term( $term_id, $taxonomy );
		$value         = $term->slug;
		$display_value = $term->name;
		$filter_ids    = array_column( $taxonomy_elements, 'filter_id' );

		// Update index table
		$query = "UPDATE {$table_name}
		SET filter_value = %s, filter_value_display = %s
		WHERE filter_id IN ($placeholders) AND filter_value_id = %d";

		$wpdb->query(
			$wpdb->prepare(
				$query,
				array_merge( [ $value, $display_value ], $filter_ids, [ $term_id ] )
			)
		);
	}

	/**
	 * Update indexed records when a term is deleted
	 */
	public function delete_term( $term_id, $tt_id, $taxonomy, $deleted_term ) {
		// Get all indexable and active filter elements from element table
		$indexable_elements = $this->get_elements_from_element_table(
			[
				'indexable' => 1,
				'status'    => 1
			]
		);

		if ( empty( $indexable_elements ) ) {
			return;
		}

		// Only get filter elements that use taxonomy as filter source and filter taxonomy is the same as $taxonomy
		$taxonomy_elements = array_filter(
			$indexable_elements,
			function( $element ) use ( $taxonomy ) {
				$filter_settings = json_decode( $element['settings'], true );
				$filter_source   = $filter_settings['filterSource'] ?? false;
				$filter_taxonomy = $filter_settings['filterTaxonomy'] ?? false;

				if ( $filter_source !== 'taxonomy' || $filter_taxonomy !== $taxonomy ) {
					return false;
				}

				return true;
			}
		);

		if ( empty( $taxonomy_elements ) ) {
			return;
		}

		global $wpdb;
		$table_name   = self::get_table_name();
		$filter_ids   = array_column( $taxonomy_elements, 'filter_id' );
		$placeholders = array_fill( 0, count( $taxonomy_elements ), '%s' );
		$placeholders = implode( ',', $placeholders );

		// Remove rows related to this term_id
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE filter_id IN ({$placeholders}) AND filter_value_id = %d",
				array_merge( $filter_ids, [ $term_id ] )
			)
		);
	}

}
