<?php
namespace Bricks\Integrations\Form;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Submission_Table extends \WP_List_Table {
	const PAGE_NAME           = 'bricks-form-submissions';
	const SCREEN_PER_PAGE     = 'bricks_page_form_entries_per_page';
	const DELETE_FORM_ENTRIES = 'bricks_delete_form_entries';
	const EXPORT_FORM_ENTRIES = 'bricks_export_form_entries';
	public $view;
	public $form_id;
	public $submission_db;
	public $query_args;

	public function __construct() {
		parent::__construct(
			[
				'singular' => 'submission',
				'plural'   => 'submissions',
				'ajax'     => false,
			]
		);

		$this->init();
	}

	private function init() {
		// Set form ID
		$form_id       = isset( $_GET['form_id'] ) ? sanitize_text_field( $_GET['form_id'] ) : '';
		$this->form_id = $form_id;

		// Reset query args
		$this->query_args = [];

		// Set view
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

		if ( $action === self::EXPORT_FORM_ENTRIES ) {
			$this->view = 'export_form_entries';
		} else {
			$this->view = $form_id ? 'form_entries' : 'overview';
		}

		// Init submission database
		$this->submission_db = Submission_Database::get_instance();
	}

	// Add screen options
	public static function add_screen_options() {
		$screen = get_current_screen();

		if ( ! is_object( $screen ) || $screen->id !== 'bricks_page_' . self::PAGE_NAME ) {
			return;
		}

		$args = [
			'label'   => esc_html__( 'Entries per page', 'bricks' ),
			'default' => 10,
			'option'  => self::SCREEN_PER_PAGE,
		];

		add_screen_option( 'per_page', $args );
	}

	// Set screen options so it will be saved in user meta
	public static function set_screen_option( $status, $option, $value ) {
		if ( $option === self::SCREEN_PER_PAGE ) {
			return $value;
		}
		return $status;
	}

	// Set screen columns for user to hide/show
	public static function screen_columns( $header_columns ) {
		return [
			'created_at' => esc_html__( 'Date', 'bricks' ),
			'browser'    => esc_html__( 'Browser', 'bricks' ),
			'ip'         => esc_html__( 'IP address', 'bricks' ),
			'os'         => 'OS',
			'referrer'   => esc_html__( 'Referrer', 'bricks' ),
			'user_id'    => esc_html__( 'User', 'bricks' ),
		];
	}

	// Define your columns
	public function get_columns() {
		// Not in use but required by WP_List_Table
		return [];
	}

	// Display data for each column
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
	}

	// Checkbox column (screen: Entries)
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="row_id[]" value="%s" />',
			$item['id']
		);
	}

	// Column: entries (screen: Overview)
	public function column_entries( $item ) {
		return $item['entries'] ?? 0;
	}

	// Column: actions (screen: Overview)
	public function column_actions( $item ) {
		return '<a class="button button-small" data-form-id="' . esc_attr( $item['form_id'] ) . '">' . esc_html__( 'Delete', 'bricks' ) . '</a>';
	}

	// Column: form_id
	public function column_form_id( $item ) {
		return '<code>' . $item['form_id'] . '</code>';
	}

	// Column: form_name
	public function column_form_name( $item ) {
		$form_id   = $item['form_id'];
		$form_name = $this->submission_db::get_form_name_by_id( $form_id );

		// No form name set
		if ( ! $form_name ) {
			$form_name = '[' . esc_html__( 'No name', 'bricks' ) . ']';
		}

		return '<a href="' . admin_url( 'admin.php?page=' . self::PAGE_NAME . "&form_id=$form_id" ) . '">' . $form_name . '</a>';
	}

	// Column: created_at (Datetime)
	public function column_created_at( $item ) {
		// Format used on posts & pages screen
		// $date_time_format = 'Y/m/d \a\t g:i a';

		// Use WordPress date and time format
		$date_time_format = get_option( 'date_format', 'F j, Y' ) . ' ' . get_option( 'time_format', 'g:i a' );

		// $item['created_at'] in UTC, need to convert to WordPress timezone
		$output = wp_date( $date_time_format, strtotime( $item['created_at'] ) );

		return $output;
	}

	public function column_id( $item ) {
		$output = '<span>' . $item['id'] . '</span>';

		// NOTE: Prepared, but not in use
		// $output .= isset( $item['favorite'] ) ? '<span data-balloon="' . esc_html__( 'Favorite', 'bricks' ) . '" data-balloon-pos="top"><i class="dashicons dashicons-star-filled"></i></span>' : '<span data-balloon="' . esc_html__( 'Favorite', 'bricks' ) . '" data-balloon-pos="top"><i class="dashicons dashicons-star-empty"></i></span>';
		// $output .= isset( $item['status'] ) ? '<span data-balloon="' . esc_html__( 'Read', 'bricks' ) . '" data-balloon-pos="top"><i class="dashicons dashicons-visibility"></i></span>' : '<span data-balloon="' . esc_html__( 'Unread', 'bricks' ) . '" data-balloon-pos="top"><i class="dashicons dashicons-hidden"></i></span>';

		return $output;
	}

	// Column: post_id
	public function column_post_id( $item ) {
		$output = '<a href="' . get_edit_post_link( $item['post_id'] ) . '" target="_blank">' . $item['post_id'] . '</a>';
		// Post title
		$post_title = get_the_title( $item['post_id'] );

		if ( ! empty( $post_title ) ) {
			// Max length of 20 characters
			$post_title = strlen( $post_title ) > 20 ? substr( $post_title, 0, 20 ) . '...' : $post_title;
			$output    .= ' <span class="post-title">' . $post_title . '</span>';
		}

		return $output;
	}

	// Column: browser (Browser)
	public function column_browser( $item ) {
		return $item['browser'] ?? '';
	}

	// Column: ip (IP Address)
	public function column_ip( $item ) {
		return $item['ip'] ?? '';
	}

	// Column: os (Operating System)
	public function column_os( $item ) {
		return $item['os'] ?? '';
	}

	// Column: referrer (Referrer)
	public function column_referrer( $item ) {
		$referrer_url     = esc_url( $item['referrer'] ?? '' );
		$referrer_post_id = url_to_postid( $referrer_url );
		$referrer         = $referrer_post_id ? get_the_title( $referrer_post_id ) : $referrer_url;

		return '<a href="' . $referrer_url . '" target="_blank">' . $referrer . '</a>';
	}

	// Column: user_id
	public function column_user_id( $item ) {
		$user_id = $item['user_id'] ?? '';

		if ( $user_id ) {
			$user = get_user_by( 'ID', $user_id );

			if ( $user ) {
				return '<a href="' . admin_url( "user-edit.php?user_id=$user_id" ) . '">' . $user->display_name . '</a>';
			}
		}

		return $user_id ? $user_id : '';
	}

	// Info column (not in use)
	public function column_info( $item ) {
		return $item['info'] ?? '';
	}

	// Prepare data for display
	public function prepare_items() {
		$this->maybe_set_form_id_args();
		$this->maybe_set_order_args();
		$this->maybe_set_pagination_args();

		// Core logic for setting the items
		$items = $this->get_custom_items();

		// Set the column headers and items
		$this->_column_headers = $items['headers'];
		$this->items           = $items['items'];
	}

	/**
	 * Only set form ID args if
	 * view = form_entries || export_form_entries
	 * To be used in the query (get_custom_items)
	 */
	private function maybe_set_form_id_args() {
		if ( $this->view === 'overview' ) {
			return;
		}

		// Set the form ID arg
		$this->query_args['form_id'] = $this->form_id;
	}

	/**
	 * Only set order args if
	 * view = form_entries
	 * To be used in the query (get_custom_items)
	 */
	private function maybe_set_order_args() {
		if ( ! $this->view === 'form_entries' ) {
			return;
		}

		// Order by = default id DESC, created_at is sortable
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

		$this->query_args['orderby'] = $orderby;
		$this->query_args['order']   = $order;
	}

	/**
	 * Only set pagination args if
	 * view = form_entries || overview
	 * To be used in the query (get_custom_items)
	 */
	private function maybe_set_pagination_args() {
		if ( $this->view === 'export_form_entries' ) {
			return;
		}

		// Pagination - default 10 items per page
		$per_page     = $this->get_items_per_page( self::SCREEN_PER_PAGE, 10 );
		$current_page = $this->get_pagenum();

		// Set the query args
		$this->query_args['per_page'] = $per_page;
		$this->query_args['limit']    = ( $current_page - 1 ) * $per_page;

		if ( $this->view === 'overview' ) {
			$total_items = $this->submission_db::get_overview_count();
		} else {
			$total_items = $this->submission_db::get_entries_count( $this->form_id );
		}

		// WP List Table method
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);
	}

	/**
	 * Core logic for setting the items
	 */
	private function get_custom_items() {
		$data = $this->submission_db::get_entries( $this->query_args );

		$items = [
			'headers' => [],
			'items'   => [],
		];

		$headers          = [];
		$sortable_columns = [];
		$hidden_columns   = [];

		if ( ! empty( $data ) ) {
			switch ( $this->view ) {
				case 'form_entries':
				case 'export_form_entries':
					/**
					 * Populate the data and column headers
					 * form_data is json encoded, we need to decode it (each key will be a column)
					 */
					$new_data       = [];
					$headers        = [];
					$screen_columns = self::screen_columns( [] ); // Get the screen columns
					$hidden_columns = $this->view === 'form_entries' ? get_hidden_columns( get_current_screen() ) : [];

					foreach ( $data as $i => $row ) {
						// $row is an array and the keys are the column names
						foreach ( $row as $key => $value ) {
							switch ( $key ) {
								case 'id':
									if ( $i === 0 ) {
										$headers[ $key ] = 'ID';
									}

									$new_data[ $i ][ $key ] = $value;
									break;

								case 'post_id':
									$new_data[ $i ][ $key ] = $value;
									// No header needed
									break;

								case 'created_at':
									// Populate column header (but just once)
									if ( $i === 0 ) {
										$headers[ $key ] = $screen_columns[ $key ];
									}

									$new_data[ $i ][ $key ] = $value;
									break;

								case 'browser':
								case 'ip':
								case 'os':
								case 'referrer':
								case 'user_id':
									// Populate column header (but just once)
									if ( $i === 0 ) {
										$column_title = '';

										switch ( $key ) {
											case 'os':
												if ( $this->view === 'export_form_entries' ) {
													$column_title = $screen_columns[ $key ];
												} else {
													$column_title = '<span title="' . esc_html__( 'Operating system', 'bricks' ) . '">' . $screen_columns[ $key ] . '</span>';
												}
												break;

											case 'ip':
											case 'browser':
											case 'referrer':
												$column_title = $screen_columns[ $key ];
												break;

											case 'user_id':
												$column_title = $this->view === 'export_form_entries' ? 'User ID' : $screen_columns[ $key ];
												break;
										}

										$headers[ $key ] = $column_title;
									}

									if ( $key === 'browser' ) {
										$value = $value === 'msie' ? 'Internet Explorer' : ucfirst( $value );
									}

									$new_data[ $i ][ $key ] = $value;
									break;

								case 'form_data':
									$form_data = json_decode( $value, true );

									foreach ( $form_data as $field_key => $field_data ) {
										$field_value = $field_data['value'];

										// Populate the column
										if ( is_array( $field_value ) ) {
											// File is an array of files containing file (path), url, type, name data
											if ( $field_data['type'] === 'file' ) {
												$files_value = [];

												if ( is_array( $field_value ) ) {
													foreach ( $field_value as $file ) {
														$save_location = $file['location'] ?? false;

														// No save location: Just display the file name (no link)
														if ( ! $save_location ) {
															$files_value[] = $file['name'];
															continue;
														}

														if ( $save_location === 'attachment' ) {
															$file_url = $file['url']; // Media library URL

															// Get attachment ID by URL
															$attachment_id = attachment_url_to_postid( $file_url );
															if ( $attachment_id ) {
																$file_url      = admin_url( "post.php?post=$attachment_id&action=edit" );
																$file['name'] .= ' (ID: ' . $attachment_id . ')';
															}
														}

														// Saved in uploads directory (Bricks default)
														elseif ( $save_location === 'directory' ) {
															$upload_dir = wp_upload_dir();
															$file_url   = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file['file'] );
														}

														// Saved in custom directory (via 'bricks/form/file_directory' filter)
														else {
															/**
															 * $file['file'] holds the actual path ("/var/www/html/bricks_tt1/wp-content/c-2/dummy.pdf""
															 *
															 * Remove the absolute path
															 *
															 * NOTE: ABSPATH might not work for certain servers like Flywheel
															 */
															$file_url = str_replace( ABSPATH, '', $file['file'] );

															// Remove the first slash
															$file_url = ltrim( $file_url, '/' );

															// Add the site URL
															$file_url = site_url( $file_url );
														}

														// NOTE: Undocumented (in case file_url is not correct via custom directory)
														$file_url = apply_filters( 'bricks/form/submission-table/file_url', $file_url, $file, $field_key );

														$files_value[] = $this->view === 'export_form_entries' ? $file['name'] : "<a href=\"$file_url\" target=\"_blank\">{$file['name']}</a>";
													}
												}

												$field_value = implode( ', ', $files_value );
											} else {
												$field_value = implode( ', ', $field_value );
											}
										}

										$new_data[ $i ][ $field_key ] = $field_value;

										// Escape HTML if view is form_entries
										if ( $this->view === 'form_entries' ) {
											if ( $field_data['type'] !== 'file' ) {
												$new_data[ $i ][ $field_key ] = esc_html( $new_data[ $i ][ $field_key ] );
											}
										}

										// Add the field id OR label to the column headers
										if ( ! isset( $headers[ $field_key ] ) ) {
											$headers[ $field_key ] = $field_key;

											// Get form field 'label' from form settings
											$form_settings  = Submission_Database::get_form_settings( $row['post_id'], $row['form_id'] );
											$field_settings = $form_settings['fields'] ?? [];

											foreach ( $field_settings as $field ) {
												if ( (string) $field['id'] === (string) $field_key && ! empty( $field['label'] ) ) {
													$headers[ $field_key ] = $field['label'];
												}
											}
										}
									}
									break;
							}
						}
					}

					// Set the new data
					$data = $new_data;

					/**
					 * Set the sortable columns
					 * Array format:
					 * [ column_name => [ $orderby, $desc_first, $abbr, $orderby_text, $initial_order ] ]
					 * /wp-admin/includes/class-wp-list-table.php print_column_headers()
					 */
					$sortable_columns = [
						'id' => [ 'id', true, '', '', 'desc' ],
					];

					/**
					 * Rearrange columns:
					 *
					 * Checkbox, id, created_at, form_data..., browser, ip, os, referrer, user_id
					 */

					// First columns
					$column_id         = $headers['id'];
					$column_created_at = $headers['created_at'];

					unset( $headers['id'] );
					unset( $headers['created_at'] );

					$headers = [ 'id' => $column_id ] +
					[ 'created_at' => $column_created_at ] +
					$headers;

					// In-between columns: form_data

					// Last columns: browser, ip, os, referrer, user_id
					$column_browser  = $headers['browser'];
					$column_ip       = $headers['ip'];
					$column_os       = $headers['os'];
					$column_referrer = $headers['referrer'];
					$column_user_id  = $headers['user_id'];

					unset( $headers['browser'] );
					unset( $headers['ip'] );
					unset( $headers['os'] );
					unset( $headers['referrer'] );
					unset( $headers['user_id'] );

					$headers = $headers +
					[ 'browser' => $column_browser ] +
					[ 'ip' => $column_ip ] +
					[ 'os' => $column_os ] +
					[ 'referrer' => $column_referrer ] +
					[ 'user_id' => $column_user_id ];

					if ( $this->view === 'form_entries' ) {
						// Checkbox column, cannot use array_merge as it will reindex the array
						$headers = [ 'cb' => '<input type="checkbox" />' ] + $headers;
					}

					break;

				case 'overview':
				default:
					$hidden_columns = get_hidden_columns( get_current_screen() );
					$headers        = [
						'form_id'   => esc_html__( 'Form ID', 'bricks' ),
						'form_name' => esc_html__( 'Form name', 'bricks' ),
						'entries'   => esc_html__( 'Entries', 'bricks' ),
						'actions'   => esc_html__( 'Actions', 'bricks' ),
					];

					break;
			}
		}

		$items['headers'] = [ $headers, $hidden_columns, $sortable_columns ];
		$items['items']   = $data;

		return $items;
	}

	// Display page title
	public function display_page_title() {
		$page_title = esc_html__( 'Form Submissions', 'bricks' );

		if ( $this->view === 'form_entries' ) {
			$form_name = $this->submission_db::get_form_name_by_id( $this->form_id );
			$form_id   = '<span>ID: ' . $this->form_id . '</span>';

			$page_title .= $form_name ? ": $form_name ($form_id)" : ": $form_id";
		}

		echo $page_title;
	}

	// Display the top bar
	public function display_top_bar() {
		if ( $this->view === 'form_entries' ) {
			echo '<a href="' . admin_url( 'admin.php?' . http_build_query( [ 'page' => self::PAGE_NAME ] ) ) . '" class="button">' . esc_html__( 'Back', 'bricks' ) . '</a>';

			echo '<input type="hidden" name="form_id" value="' . $this->form_id . '" />';

			// Button: Download all submissions for current form as CSV file
			$expost_url_args = [
				'page'     => self::PAGE_NAME,
				'action'   => self::EXPORT_FORM_ENTRIES,
				'form_id'  => $this->form_id,
				'_wpnonce' => wp_create_nonce( self::EXPORT_FORM_ENTRIES ),
			];

			echo '<a href="' . admin_url( 'admin.php?' . http_build_query( $expost_url_args ) ) . '" class="button">' . esc_html__( 'Download', 'bricks' ) . ' (CSV)' . '</a>';

			/**
			 * Search box: Search form data (json string)
			 *
			 * @see admin.js for placeholder "Form data".
			 */
			$this->search_box( esc_html__( 'Search', 'bricks' ), 'form_id' );
		}
	}

	// Bulk actions
	public function get_bulk_actions() {
		return $this->view === 'form_entries' && $this->submission_db::check_managed_db_access() ? [
			'bricks_delete_form_entries' => esc_html__( 'Delete', 'bricks' ),
		] : [];
	}

	/**
	 * Handle custom actions
	 * - Delete form entries
	 * - Export form entries as CSV
	 * Currently called in admin.php before add_submenu_page
	 * Otherwise the admin notice will not show as the class is not instantiated yet
	 */
	public static function handle_custom_actions() {
		$submission_db = Submission_Database::get_instance();

		if ( ! $submission_db::check_managed_db_access() ) {
			return;
		}

		// Delete form entries
		$delete_entries = ! empty( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : false;
		$selected_items = $_POST['row_id'] ?? [];

		if ( $delete_entries === self::DELETE_FORM_ENTRIES && ! empty( $selected_items ) ) {
			// Delete each item
			$deleted = [];
			foreach ( $selected_items as $item_id ) {
				$result = $submission_db::delete_data( $item_id );

				if ( $result ) {
					$deleted[] = $item_id;
				}
			}

			$message = esc_html__( 'Deleted', 'bricks' ) . ': ' . count( $deleted );

			// Trigger admin notice
			add_action(
				'admin_notices',
				function() use ( $message ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
				}
			);
		}

		// Export form entries as CSV file
		$export_entries = ! empty( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : false;
		$form_id        = isset( $_GET['form_id'] ) ? sanitize_text_field( $_GET['form_id'] ) : '';
		$nonce          = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( $_GET['_wpnonce'] ) : '';

		if ( $export_entries === self::EXPORT_FORM_ENTRIES && $form_id && wp_verify_nonce( $nonce, self::EXPORT_FORM_ENTRIES ) ) {
			$submission_table = new Submission_Table();
			$submission_table->prepare_items();

			$headers = $submission_table->_column_headers[0];
			$items   = $submission_table->items;

			// Return if no items or headers
			if ( empty( $items ) || empty( $headers ) ) {
				return;
			}

			// Set the filename
			$form_name = $submission_db::get_form_name_by_id( $form_id );
			$form_name = ! empty( $form_name ) ? "$form_id-($form_name)" : $form_id;
			$filename  = sanitize_title( 'form-submissions-' . $form_name . '-' . date( 'Y-m-d' ) ) . '.csv';

			// Set the headers
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=' . $filename );

			// Create a file pointer connected to the output stream
			$output = fopen( 'php://output', 'w' );

			// Output the column headers
			fputcsv( $output, $headers );

			// Output each row of the data
			foreach ( $items as $item ) {
				$row = [];

				foreach ( $headers as $key => $value ) {
					$row[] = isset( $item[ $key ] ) ? $item[ $key ] : '';
				}

				fputcsv( $output, $row );
			}

			// Close the file pointer
			fclose( $output );

			// Stop the script
			die();
		}
	}
}
