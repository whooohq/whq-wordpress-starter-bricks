<?php
namespace Bricks\Integrations\Form\Actions;

use Bricks\Integrations\Form\Submission_Database;
use Bricks\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Save_Submission extends Base {
	/**
	 * Add contact to list
	 *
	 * @since 1.9.2
	 */
	public function run( $form ) {
		$form_settings  = $form->get_settings();
		$submission_db  = Submission_Database::get_instance();
		$submitted_data = $form->get_fields();

		$post_id = isset( $submitted_data['postId'] ) ? absint( $submitted_data['postId'] ) : 0;
		$form_id = isset( $submitted_data['formId'] ) ? sanitize_text_field( $submitted_data['formId'] ) : '';

		// Use global element ID as form ID
		if ( ! empty( $submitted_data['globalId'] ) ) {
			$form_id = sanitize_text_field( $submitted_data['globalId'] );
		}

		// Return: post ID or form ID are not valid
		if ( ! $post_id || ! $form_id ) {
			$form->set_result(
				[
					'action'  => $this->name,
					'type'    => 'error',
					'message' => esc_html__( 'Invalid post ID or form ID.', 'bricks' ),
				]
			);

			return;
		}

		// STEP: Populate form data according to form setting fields
		$form_fields = $form_settings['fields'] ?? [];
		$form_data   = [];

		foreach ( $form_fields as $field ) {
			$field_id    = $field['id'] ?? '';
			$field_type  = $field['type'] ?? '';
			$field_value = $form->get_field_value( $field_id );

			// Skip: Form field type 'html'
			if ( $field_type === 'html' ) {
				continue;
			}

			// File: Get files data and add to form data
			if ( $field_type === 'file' ) {
				$uploaded_files = $form->get_uploaded_files();

				if ( is_array( $uploaded_files ) ) {
					// Check: Custom field 'name' in use
					$compare_field_key = $field['name'] ?? $field_id;

					foreach ( $uploaded_files as $field_key => $file_data ) {
						if ( $compare_field_key && $compare_field_key === str_replace( 'form-field-', '', $field_key ) ) {
							// $file_data contains file (path), url, type, name
							$field_value = $file_data;
						}
					}
				}
			}

			$form_data[ $field_id ] = [
				'type'  => $field_type,
				'value' => $field_value,
			];
		}

		// STEP: Check max entries
		$max_entries = isset( $form_settings['submissionMaxEntries'] ) ? absint( $form_settings['submissionMaxEntries'] ) : 0;

		if ( $max_entries ) {
			$entries_count = $submission_db::get_entries_count( $form_id );

			if ( $entries_count >= $max_entries ) {
				$message = esc_html__( 'Maximum number of entries reached.', 'bricks' );

				if ( ! empty( $form_settings['submissionMaxEntriesErrorMessage'] ) ) {
					$message = bricks_render_dynamic_data( $form_settings['submissionMaxEntriesErrorMessage'], $post_id );
				}

				// Return: Max. entries reached
				$form->set_result(
					[
						'action'  => $this->name,
						'type'    => 'error',
						'message' => $message,
					]
				);

				return;
			}
		}

		// Undocumented - Allow other plugins to modify the form data before inserting to the database
		$form_data = apply_filters( 'bricks/form/save-submission/form_data', $form_data, $form_id, $post_id );

		// Create form submisstion database entry
		$insert_data = [
			'post_id'   => $post_id,
			'form_id'   => $form_id,
			'form_data' => $form_data,
		];

		/**
		 * STEP: Additional information to save
		 *
		 * - browser
		 * - ip
		 * - os
		 * - referrer
		 * - user_id
		 */
		$browser = Helpers::user_agent_to_browser( $_SERVER['HTTP_USER_AGENT'] );
		if ( $browser ) {
			$insert_data['browser'] = $browser;
		}

		$ip = isset( $form_settings['submissionSaveIp'] ) ? Helpers::user_ip_address() : false;
		if ( $ip ) {
			$insert_data['ip'] = $ip;
		}

		$os = Helpers::user_agent_to_os( $_SERVER['HTTP_USER_AGENT'] );
		if ( $os ) {
			$insert_data['os'] = $os;
		}

		$referrer = $form->get_field_value( 'referrer_url' );
		if ( $referrer ) {
			$insert_data['referrer'] = $referrer;
		}

		$user_id = get_current_user_id();
		if ( $user_id ) {
			$insert_data['user_id'] = $user_id;
		}

		// STEP: Check duplicate entries
		$check_duplicate_entries = $form_settings['submissionDupEntries'] ?? [];

		if ( ! empty( $check_duplicate_entries ) ) {
			/**
			 * Get the user's input field IDs so we can check against the database
			 * $check_duplicate_entries is an array. Eg: [ [ 'id' => 'evhryu', 'field_id' => '666e17' ], [ 'id' => 'asdw12', 'field_id' => '323cd6' ] ]
			 */
			$field_ids = array_map(
				function( $field ) {
					return $field['field_id'] ?? '';
				},
				$check_duplicate_entries
			);

			// Remove empty field IDs
			$field_ids = array_filter( $field_ids );

			if ( $submission_db::is_duplicated_entry( $form_id, $field_ids, $form_data, $ip ) ) {
				$message = esc_html__( 'Duplicate entries not allowed.', 'bricks' );

				if ( ! empty( $form_settings['submissionDupEntriesErrorMessage'] ) ) {
					$message = bricks_render_dynamic_data( $form_settings['submissionDupEntriesErrorMessage'], $post_id );
				}

				$form->set_result(
					[
						'action'  => $this->name,
						'type'    => 'error',
						'message' => $message,
					]
				);

				return;
			}
		}

		// STEP: Add new database entry with form submission data
		// Data is sanitized and escaped before inserting it to the database.
		$result = $submission_db::insert_data( $insert_data );

		$form->set_result(
			[
				'action' => $this->name,
				'type'   => $result ? 'success' : 'error',
			]
		);
	}
}
