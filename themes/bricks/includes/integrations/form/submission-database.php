<?php
namespace Bricks\Integrations\Form;

use Bricks\Helpers;
use Bricks\Database;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Submission_Database {
	const DB_TABLE_NAME      = 'bricks_form_submissions';
	private static $instance = null;
	private static $table_name;

	private function __construct() {
		self::$table_name = self::get_table_name();
	}

	// Singleton
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Submission_Database();
		}

		return self::$instance;
	}

	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . self::DB_TABLE_NAME;
	}

	public static function check_managed_db_access() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Create custom database table (bricks_form_submissions) to store form submissions
	 */
	public static function maybe_create_table() {
		if ( ! self::check_managed_db_access() ) {
			return;
		}

		global $wpdb;

		$table_name = self::get_table_name();

		// Return: Table already exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
			return;
		}

		// Create table
		$charset_collate = $wpdb->get_charset_collate();

		/**
		 * post_id: The ID of the post where the form has been submitted from (to get form settings, but not submit URL as not correct on non-single pages)
		 * form_id: The unique 6-character bricks Form element ID
		 * created_at: Datetime of the form submission
		 * form_data: The form data in JSON format
		 * browser: The browser name (see: user_agent_to_browser)
		 * ip: The IPv4/IPv6 address of the user (see: user_ip_address)
		 * os: The operating system name (see: user_agent_to_os)
		 * referrer: The referrer URL
		 * user_id: The user ID (if logged in)
		 * status: read, unread (= default if empty)
		 * favorite: 1 = favorite, 0 = not favorite
		 * info: Not in use (possible use for 'notes')
		 *
		 * Indexes:
		 * post_id
		 * form_id
		 */
		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) NOT NULL,
			form_id CHAR(6) NOT NULL,
			created_at datetime NOT NULL,
			form_data LONGTEXT,
			browser VARCHAR(20),
			ip VARCHAR(128),
			os VARCHAR(20),
			referrer VARCHAR(1024),
			user_id bigint(20),
			status VARCHAR(20),
			favorite TINYINT,
			info LONGTEXT,
			PRIMARY KEY  (id) ,
      KEY post_id (post_id),
      KEY form_id (form_id)
    ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}

	/**
	 * AJAX callback to drop bricks_form_submissions table
	 *
	 * Bricks > Settings > General
	 */
	public static function drop_table() {
		if ( ! self::check_managed_db_access() ) {
			return false;
		}

		global $wpdb;

		$table_name = self::get_table_name();

		// Exit if table does not exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
			return false;
		}

		$wpdb->query( "DROP TABLE {$table_name}" );

		return true;
	}

	/**
	 * AJAX callback to reset bricks_form_submissions table
	 *
	 * Bricks > Settings > General
	 */
	public static function reset_table() {
		if ( ! self::check_managed_db_access() ) {
			return false;
		}
		global $wpdb;

		$table_name = self::get_table_name();

		// Exit if table does not exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
			return false;
		}

		$wpdb->query( "TRUNCATE TABLE {$table_name}" );

		return true;
	}

	/**
	 * AJAX callback to remove rows of form_id in bricks_form_submissions table
	 *
	 * Bricks > Form Submissions
	 */
	public static function remove_form_id( $form_id ) {
		if ( ! self::check_managed_db_access() ) {
			return false;
		}

		global $wpdb;

		$table_name = self::get_table_name();

		// Delete all rows with form_id
		return $wpdb->delete( $table_name, [ 'form_id' => $form_id ] );
	}

	/**
	 * Sanitize the form submission data before inserting into the database
	 * - Add created_at value
	 */
	public static function sanitize_data( $data ) {
		$form_data           = ! empty( $data['form_data'] ) ? $data['form_data'] : [];
		$sanitized_form_data = [];

		foreach ( $form_data as $field_id => $field_data ) {
			// Sanitize type (although not really necessary)
			$sanitized_form_data[ $field_id ]['type'] = sanitize_text_field( trim( $field_data['type'] ) );

			/**
			 * Sanitize based on the field type
			 * Field types refer to includes/elements/form.php
			 * email, text, textarea, tel, number, url, checkbox, select, radio, file, password, datepicker, hidden
			 */
			switch ( $field_data['type'] ) {
				case 'email':
					$sanitized_form_data[ $field_id ]['value'] = sanitize_email( trim( $field_data['value'] ) );
					break;

				case 'url':
					$sanitized_form_data[ $field_id ]['value'] = esc_url_raw( trim( $field_data['value'] ) );
					break;

				// If it's empty, leave it empty
				case 'number':
					$sanitized_form_data[ $field_id ]['value'] = $field_data['value'] === '' ? '' : intval( $field_data['value'] );
					break;

				// Allow HTML tags
				case 'textarea':
					$sanitized_form_data[ $field_id ]['value'] = wp_kses_post( trim( $field_data['value'] ) );
					break;

				// Don't alter these field type
				case 'file': // Nested array values
				case 'password':
					$sanitized_form_data[ $field_id ]['value'] = $field_data['value'];
					break;

				default:
					// Sanitize function to be reused
					$esc_url_or_text = function( $value ) {
						return filter_var( $value, FILTER_VALIDATE_URL ) ? esc_url_raw( $value ) : sanitize_text_field( $value );
					};

					if ( is_array( $field_data['value'] ) ) {
						// Trim and sanitize
						$field_data['value']                       = array_map( 'trim', $field_data['value'] );
						$sanitized_form_data[ $field_id ]['value'] = array_map( $esc_url_or_text, $field_data['value'] );
					} else {
						// Trim and sanitize
						$field_data['value']                       = trim( $field_data['value'] );
						$sanitized_form_data[ $field_id ]['value'] = $esc_url_or_text( $field_data['value'] );
					}
					break;
			}
		}

		$sanitized_data = [
			'post_id'    => isset( $data['post_id'] ) ? intval( $data['post_id'] ) : 0,
			'form_id'    => isset( $data['form_id'] ) ? sanitize_text_field( $data['form_id'] ) : '',
			'created_at' => current_time( 'mysql', true ),
			'form_data'  => wp_json_encode( $sanitized_form_data ),
			'browser'    => isset( $data['browser'] ) ? sanitize_text_field( $data['browser'] ) : '',
			'ip'         => isset( $data['ip'] ) ? sanitize_text_field( $data['ip'] ) : '',
			'os'         => isset( $data['os'] ) ? sanitize_text_field( $data['os'] ) : '',
			'referrer'   => isset( $data['referrer'] ) ? esc_url_raw( $data['referrer'] ) : '',
			'user_id'    => isset( $data['user_id'] ) ? sanitize_text_field( $data['user_id'] ) : '',
		];

		return $sanitized_data;
	}

	/**
	 * Create new entry in the database
	 */
	public static function insert_data( $data ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$data = self::sanitize_data( $data );

		// Returns 1 (number of affected table rows) if successful, or false if not
		$result = $wpdb->insert( $table_name, $data );

		return $result;
	}

	// Delete single entry from the database
	public static function delete_data( $id ) {
		if ( ! self::check_managed_db_access() ) {
			return false;
		}

		global $wpdb;

		$table_name = self::get_table_name();

		$result = $wpdb->delete( $table_name, [ 'id' => $id ] );

		return $result;
	}

	// Get the post_id column by form_id (latest row)
	public static function get_post_id( $form_id ) {
		if ( empty( $form_id ) ) {
			return 0;
		}

		// Get the latest row with the form ID
		global $wpdb;

		$table_name = self::get_table_name();

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$table_name} WHERE form_id = %s ORDER BY id DESC LIMIT 1",
				$form_id
			)
		);

		return $post_id;
	}

	// Get total entries count by form_id
	public static function get_entries_count( $form_id ) {
		if ( empty( $form_id ) ) {
			return 0;
		}

		global $wpdb;

		$table_name = self::get_table_name();

		$sql = "SELECT COUNT(*) FROM {$table_name} WHERE form_id = %s";

		// Search form_data for 's' (search term)
		$search_term = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : false;
		if ( $search_term ) {
			$sql .= $wpdb->prepare( " AND form_data LIKE %s", '%' . $search_term . '%' ); // phpcs:ignore
		}

		$entries_count = $wpdb->get_var(
			$wpdb->prepare( $sql, $form_id )
		);

		return $entries_count;
	}

	public static function get_overview_count() {
		// Get total rows count after grouping by form_id
		global $wpdb;

		$table_name = self::get_table_name();

		$entries_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM (SELECT * FROM {$table_name} GROUP BY form_id) AS t"
		);

		return $entries_count;
	}

	// Get form name by 'id'
	public static function get_form_name_by_id( $form_id ) {
		$post_id       = self::get_post_id( $form_id );
		$global_id     = $form_id;
		$form_settings = self::get_form_settings( $post_id, $form_id, $global_id );

		return $form_settings['submissionFormName'] ?? '';
	}

	/**
	 * Get entries
	 *
	 * If form_id is empty, return all entries grouped by form_id (overview)
	 * If form_id is not empty, return all entries with the form_id
	 */
	public static function get_entries( $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'form_id'  => '',
				'order_by' => 'id',
				'order'    => 'DESC',
			]
		);

		$sql_args = [];

		$is_overview = true;

		// STEP : Sanitize arguments
		// Overview or single form
		if ( isset( $args['form_id'] ) && $args['form_id'] !== '' ) {
			$sql_args['form_id'] = sanitize_text_field( $args['form_id'] );
			$is_overview         = false;
		}

		// Limit
		if ( isset( $args['limit'] ) && is_numeric( $args['limit'] ) ) {
			$sql_args['limit'] = intval( $args['limit'] );
		}

		// Per page
		if ( isset( $args['per_page'] ) && is_numeric( $args['per_page'] ) ) {
			$sql_args['per_page'] = intval( $args['per_page'] );
		}

		// Order by
		if ( isset( $args['order_by'] ) && in_array( $args['order_by'], [ 'id', 'created_at' ] ) ) {
			$sql_args['order_by'] = sanitize_text_field( $args['order_by'] );
		}

		// Order
		if ( isset( $args['order'] ) && in_array( $args['order'], [ 'asc', 'desc', 'ASC' , 'DESC' ] ) ) {
			// uppercase
			$sql_args['order'] = strtoupper( sanitize_text_field( $args['order'] ) );
		}

		global $wpdb;

		$table_name = self::get_table_name();

		// STEP: Build SQL query
		if ( $is_overview ) {
			// phpcs:ignore
			$sql = "SELECT MAX(id) as id, form_id, MAX(post_id) as post_id, COUNT(*) AS entries FROM $table_name GROUP BY form_id ORDER BY id DESC LIMIT %d";

			// Add per_page
			if ( isset( $sql_args['per_page'] ) ) {
				$sql .= ", {$sql_args['per_page']}";
			}

			// Prepare (query argument placeholder required)
			$limit = $sql_args['limit'] ?? 0;
			$query = $wpdb->prepare( $sql, $limit );
		}

		// STEP: Get specific entry by 'form_id'
		else {
			// phpcs:ignore
			$sql = "SELECT * FROM $table_name WHERE form_id = %s";

			// Search form_data for 's' (search term)
			$search_term = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : false;
			if ( $search_term ) {
				$sql .= $wpdb->prepare( " AND form_data LIKE %s", '%' . $search_term . '%' ); // phpcs:ignore
			}

			// Add order by
			if ( isset( $sql_args['order_by'] ) && isset( $sql_args['order'] ) ) {
				$sql .= " ORDER BY {$sql_args['order_by']} {$sql_args['order']}";
			}

			// Add limit
			if ( isset( $sql_args['limit'] ) ) {
				$sql .= " LIMIT {$sql_args['limit']}";
			}

			// Add per_page
			if ( isset( $sql_args['per_page'] ) ) {
				$sql .= ", {$sql_args['per_page']}";
			}

			// Prepare
			$query = $wpdb->prepare( $sql, $sql_args['form_id'] );
		}

		// Prepare and execute SQL query
		$entries = $wpdb->get_results( $query, ARRAY_A );

		return $entries;
	}

	/**
	 * Check if the form submission is a duplicated entry
	 *
	 * @param string $form_id The form ID
	 * @param array  $fields The fields to check against
	 * @param array  $submitted_data The submitted data
	 * @param array  $ip The IP address
	 *
	 * @return bool
	 */
	public static function is_duplicated_entry( $form_id = '', $fields = [], $submitted_data = [], $ip = '' ) {
		if ( empty( $form_id ) || empty( $fields ) || empty( $submitted_data ) ) {
			return false;
		}

		global $wpdb;

		$table_name = self::get_table_name();

		// Get all rows with the form ID
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT form_data, ip FROM {$table_name} WHERE form_id = %s", $form_id ) ); // phpcs:ignore

		// Loop through each row
		foreach ( $rows as $row ) {
			$condition_met = [];

			// Decode the form data
			$form_data = json_decode( $row->form_data, true );

			// Loop through checking against each field
			foreach ( $fields as $field_id ) {
				if ( isset( $form_data[ $field_id ] ) && isset( $submitted_data[ $field_id ] ) ) {
					// Check if the field value matches (compare both in lowercase and trim whitespace)
					$user_input = strtolower( trim( $submitted_data[ $field_id ]['value'] ) );
					$db_record  = strtolower( trim( $form_data[ $field_id ]['value'] ) );

					if ( $user_input === $db_record ) {
						$condition_met[] = true;
					}
				}

				// Check if the IP address matches
				elseif ( $field_id === 'ip' && $row->ip && $ip ) {
					if ( $row->ip === $ip ) {
						$condition_met[] = true;
					}
				}
			}

			// Check if all conditions are met
			if ( count( $condition_met ) === count( $fields ) ) {
				return true;
			}
		}

		return false;
	}

	// Helper function to get form settings
	public static function get_form_settings( $post_id = 0, $form_id = 0, $global_id = 0 ) {
		if ( empty( $post_id ) || empty( $form_id ) ) {
			return [];
		}

		// Pass global_id as form_id === global element ID
		$global_id     = $global_id ? $global_id : $form_id;
		$form_settings = Helpers::get_element_settings( $post_id, $form_id, $global_id );

		if ( ! $form_settings ) {
			// Try to get the form settings from the Database class
			$bricks_data = Database::get_data( $post_id );

			if ( ! empty( $bricks_data ) ) {
				// Search which array's ID matches the form ID
				$form = array_filter(
					$bricks_data,
					function( $data ) use ( $form_id ) {
						return $data['id'] === $form_id;
					}
				);

				if ( ! empty( $form ) ) {
					$form          = array_values( $form );
					$form          = $form[0];
					$form_settings = $form['settings'];
				}
			}
		}

		return $form_settings;
	}
}
