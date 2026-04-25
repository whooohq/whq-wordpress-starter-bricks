<?php
namespace Bricks\Integrations\Form;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Init {
	protected $uploaded_files;
	protected $form_settings;
	protected $form_fields;
	protected $results;

	public function __construct() {
		add_action( 'wp_ajax_bricks_form_submit', [ $this, 'form_submit' ] );
		add_action( 'wp_ajax_nopriv_bricks_form_submit', [ $this, 'form_submit' ] );
	}

	/**
	 * Element Form: Submit
	 *
	 * @since 1.0
	 */
	public function form_submit() {
		// Return: Invalid form nonce
		if ( ! check_ajax_referer( 'bricks-nonce-form', 'nonce', false ) ) {
			wp_send_json_error(
				[
					'action'  => '',
					'code'    => 'invalid_nonce', // special code for invalid nonce (@since 1.9.6)
					'type'    => 'error',
					'message' => esc_html__( 'Invalid form token.', 'bricks' ),
				]
			);
		}

		$post_id         = isset( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;
		$form_element_id = isset( $_POST['formId'] ) ? sanitize_text_field( $_POST['formId'] ) : '';

		$this->form_settings = \Bricks\Helpers::get_element_settings( $post_id, $form_element_id );

		// Return: No form action set
		if ( empty( $this->form_settings['actions'] ) ) {
			wp_send_json_error(
				[
					'code'    => 400,
					'action'  => '',
					'type'    => 'error',
					'message' => esc_html__( 'No action has been set for this form.', 'bricks' ),
				]
			);
		}

		/**
		 * STEP: Google reCAPTCHA v3 (invisible)
		 */
		if ( isset( $this->form_settings['enableRecaptcha'] ) ) {
			$recaptcha_secret_key = \Bricks\Database::get_setting( 'apiSecretKeyGoogleRecaptcha', false );
			$recaptcha_token      = ! empty( $_POST['recaptchaToken'] ) ? sanitize_text_field( $_POST['recaptchaToken'] ) : false;
			$recaptcha_verified   = false;

			// Verify token @see https://developers.google.com/recaptcha/docs/verify
			if ( $recaptcha_token && $recaptcha_secret_key ) {
				$url                = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret_key&response=$recaptcha_token";
				$recaptcha_response = \Bricks\Helpers::remote_get( $url );

				if ( ! is_wp_error( $recaptcha_response ) && wp_remote_retrieve_response_code( $recaptcha_response ) === 200 ) {
					$recaptcha = json_decode( wp_remote_retrieve_body( $recaptcha_response ) );

					/*
					 * Google reCAPTCHA v3 returns a score
					 *
					 * 1.0 is very likely a good interaction. 0.0 is very likely a bot.
					 *
					 * https://academy.bricksbuilder.io/article/form-element/#spam
					 */
					$score = apply_filters( 'bricks/form/recaptcha_score_threshold', 0.5 );

					// Action was set on the grecaptcha.execute (@see frontend.js)
					if ( $recaptcha->success && $recaptcha->score >= $score && $recaptcha->action == 'bricks_form_submit' ) {
						$recaptcha_verified = true;
					}
				}
			}

			if ( ! $recaptcha_verified ) {
				$error = 'reCAPTCHA: ' . esc_html__( 'Validation failed', 'bricks' );

				if ( ! empty( $recaptcha->{'error-codes'} ) ) {
					$error .= ' [' . implode( ',', $recaptcha->{'error-codes'} ) . ']';
				}

				wp_send_json_error(
					[
						'code'    => 400,
						'action'  => '',
						'type'    => 'error',
						'message' => $error,
					]
				);
			}
		}

		/**
		 * STEP: Verify visible hCaptcha
		 *
		 * @since 1.9.2
		 */
		// hCaptcha enabled: Verify response
		if ( isset( $this->form_settings['enableHCaptcha'] ) ) {
			$hcaptcha_secret_key = \Bricks\Database::get_setting( 'apiSecretKeyHCaptcha' );
			$hcaptcha_response   = isset( $_POST['h-captcha-response'] ) ? sanitize_text_field( $_POST['h-captcha-response'] ) : false;
			$hcaptcha_verified   = false;

			// Verify token
			if ( $hcaptcha_response && $hcaptcha_secret_key ) {
				$url          = "https://hcaptcha.com/siteverify?secret=$hcaptcha_secret_key&response=$hcaptcha_response";
				$hcaptcha_res = \Bricks\Helpers::remote_get( $url );

				if ( ! is_wp_error( $hcaptcha_res ) && wp_remote_retrieve_response_code( $hcaptcha_res ) === 200 ) {
					$hcaptcha = json_decode( wp_remote_retrieve_body( $hcaptcha_res ) );

					// Check hCaptcha response (https://docs.hcaptcha.com/#verify-the-user-response-server-side)
					if ( $hcaptcha->success ) {
						$hcaptcha_verified = true;
					}
				}
			}

			if ( ! $hcaptcha_verified ) {
				$error = 'hCaptcha: ' . esc_html__( 'Validation failed', 'bricks' );

				if ( ! empty( $hcaptcha->{'error-codes'} ) ) {
					$error .= ' [' . implode( ',', $hcaptcha->{'error-codes'} ) . ']';
				}

				wp_send_json_error(
					[
						'code'    => 400,
						'action'  => '',
						'type'    => 'error',
						'message' => $error,
					]
				);
			}
		}

		/**
		 * STEP: Verify Turnstile captcha
		 *
		 * https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
		 *
		 * @since 1.9.2
		 */
		if ( isset( $this->form_settings['enableTurnstile'] ) ) {
			$turnstile_secret_key = \Bricks\Database::get_setting( 'apiSecretKeyTurnstile' );
			$turnstile_response   = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : false;
			$turnstile_data       = [];

			if ( $turnstile_secret_key && $turnstile_response ) {
				$url  = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
				$args = [
					'body' => [
						'secret'   => $turnstile_secret_key,
						'response' => $turnstile_response,
						// 'remoteip' => $_SERVER['REMOTE_ADDR'], // We can optionally send the user's IP address but it's not required
					],
				];

				$turnstile_verified = false;
				$response           = \Bricks\Helpers::remote_post( $url, $args );

				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
					$turnstile_data     = json_decode( wp_remote_retrieve_body( $response ), true );
					$turnstile_verified = isset( $turnstile_data['success'] ) && $turnstile_data['success'] === true;
				}

				if ( ! $turnstile_verified ) {
					$error = 'Turnstile: ' . esc_html__( 'Validation failed', 'bricks' );

					if ( isset( $turnstile_data['error-codes'] ) && ! empty( $turnstile_data['error-codes'] ) ) {
						$error .= ' [' . implode( ',', $turnstile_data['error-codes'] ) . ']';
					}

					wp_send_json_error(
						[
							'code'    => 400,
							'action'  => '',
							'type'    => 'error',
							'message' => $error,
						]
					);
				}
			}
		}

		$this->form_fields = stripslashes_deep( $_POST );

		/**
		 * STEP: Check that each submitted field's ID is in the list of valid field IDs
		 *
		 * Initialize an empty array to keep track of processed field IDs
		 *
		 * @since 1.9.2
		 */
		$processed_ids = [];

		// Get valid field IDs from form_settings
		$valid_ids = [];

		foreach ( $this->form_settings['fields'] as $key => $field ) {
			if ( ! empty( $field['id'] ) ) {
				// Get & set 'id' from custom 'name' (e.g.: 'post-{post_id} to 'form-field-{{field_id}}')
				if ( ! empty( $field['name'] ) ) {
					$field_name = bricks_render_dynamic_data( $field['name'], isset( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0 );

					// Update the parsed name back to the form_fields array so no need to render dynamic data again (@since 1.9.5)
					$this->form_settings['fields'][ $key ]['name'] = $field_name;

					if ( isset( $this->form_fields[ $field_name ] ) ) {
						$field_value                                      = $this->form_fields[ $field_name ];
						$this->form_fields[ "form-field-{$field['id']}" ] = $field_value;
					}
				}

				$valid_ids[] = $field['id'];
			}
		}

		/**
		 * Initialize an array for field IDs that we skip the form field check
		 *
		 * Password reset action: key, login
		 *
		 * @since 1.9.3
		 */
		$skip_check_for_field_ids = [];

		// Check if 'reset-password' is among the set actions for the form
		if ( in_array( 'reset-password', $this->form_settings['actions'], true ) ) {
			array_push( $skip_check_for_field_ids, 'key', 'login' );
		}

		// Check if 'login' is among the set actions for the form
		if ( in_array( 'login', $this->form_settings['actions'], true ) ) {
			array_push( $skip_check_for_field_ids, 'redirect_to' );
		}

		foreach ( array_keys( $this->form_fields ) as $key ) {
			// Check if submitted form field ID is valid
			if ( strpos( $key, 'form-field-' ) === 0 ) {
				$field_id = str_replace( 'form-field-', '', $key );

				// Skip: Field ID has already been processed (e.g.: HTML simply duplicated on the front end)
				if ( in_array( $field_id, $processed_ids, true ) ) {
					// Reject the submission as potentially malicious
					$this->set_error_messages( esc_html__( 'An error occurred, please try again later.', 'bricks' ) );
					$this->maybe_stop_processing();
				}

				// Add field ID to list of processed IDs
				$processed_ids[] = $field_id;

				if ( ! in_array( $field_id, $valid_ids, true ) && ! in_array( $field_id, $skip_check_for_field_ids, true ) ) {
					// Reject the submission as potentially malicious
					$this->set_error_messages( esc_html__( 'An error occurred, please try again later.', 'bricks' ) );
					$this->maybe_stop_processing();
				}
			}
		}

		// STEP: Handle files
		$this->uploaded_files = $this->handle_files();

		// STEP: Validate form submission via filter
		$validation_errors = [];

		$validation_errors = apply_filters( 'bricks/form/validate', $validation_errors, $this );

		// STEP: Validate required fields
		$validation_errors = $this->validate_required_fields( $validation_errors );

		// STEP: Validate submitted form
		if ( is_array( $validation_errors ) && count( $validation_errors ) ) {
			// Set validation error messages
			$this->set_error_messages( $validation_errors );

			// Halts execution if an action reported an error (to run validator before running the form action)
			$this->maybe_stop_processing();
		}

		// STEP: Run selected form submit 'actions'
		$available_actions = self::get_available_actions();

		foreach ( $this->form_settings['actions'] as $form_action ) {
			if ( ! array_key_exists( $form_action, $available_actions ) ) {
				continue;
			}

			$action_class = 'Bricks\Integrations\Form\Actions\\' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $form_action ) ) );

			$action = new $action_class( $form_action );

			if ( ! method_exists( $action_class, 'run' ) ) {
				continue;
			}

			$action->run( $this );

			// Halts execution if an action reported an error
			$this->maybe_stop_processing();
		}

		// All fine, success
		$this->finish();
	}

	/**
	 * If there are any errors, stop execution
	 *
	 * @return void
	 */
	private function maybe_stop_processing() {
		$errors = ! empty( $this->results['error'] ) && is_array( $this->results['error'] ) ? $this->results['error'] : [];

		// type 'danger' used before 1.7.1
		if ( ! count( $errors ) && ! empty( $this->results['danger'] ) && is_array( $this->results['danger'] ) ) {
			$errors = $this->results['danger'];
		}

		if ( ! count( $errors ) ) {
			return;
		}

		// Get last error
		$error = array_pop( $errors );

		// Remove uploaded files, if exist
		$this->remove_files();

		// Leave
		wp_send_json_error( $error );
	}

	private function finish() {
		$form_settings = $this->form_settings;

		// Handle uploaded files after finishing all actions (@since 1.9.2)
		$this->handle_uploaded_files();

		// Basic response
		$response = [
			'type'    => 'success',
			'message' => isset( $form_settings['successMessage'] ) ? $this->render_data( $form_settings['successMessage'] ) : esc_html__( 'Success', 'bricks' ),
		];

		if ( empty( $this->results ) ) {
			wp_send_json_success( $response );
		}

		// Check for redirects
		if ( ! empty( $this->results['redirect'] ) ) {
			$redirect                    = array_pop( $this->results['redirect'] );
			$post_id                     = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : get_the_ID();
			$response['redirectTo']      = ! empty( $redirect['redirectTo'] ) ? bricks_render_dynamic_data( $redirect['redirectTo'], $post_id ) : '';
			$response['redirectTimeout'] = $redirect['redirectTimeout'] ?? 0;
		}

		// Check for 'info' messages (e.g. Mailchimp pending message)
		if ( ! empty( $this->results['info'] ) ) {
			foreach ( $this->results['info'] as $info ) {
				if ( ! empty( $info['message'] ) ) {
					$response['info'][] = $info['message'];
				}
			}
		}

		// Check for 'success' messages (e.g. custom bricks/form/validate) (@since 1.7.1)
		if ( ! empty( $this->results['success'] ) ) {
			foreach ( $this->results['success'] as $success ) {
				if ( ! empty( $success['message'] ) ) {
					$response['message'] = $success['message'];
				}
			}
		}

		// NOTE: Undocumented
		$response = apply_filters( 'bricks/form/response', $response, $this );

		// Evaluate results
		wp_send_json_success( $response );
	}

	/**
	 * Set action result
	 *
	 * type: success OR danger
	 *
	 * @param array $result
	 * @return void
	 */
	public function set_result( $result ) {
		$type                     = isset( $result['type'] ) ? $result['type'] : 'success';
		$this->results[ $type ][] = $result;
	}

	/**
	 * Getters
	 */
	public function get_settings() {
		return $this->form_settings;
	}

	public function get_fields() {
		return $this->form_fields;
	}

	public function get_uploaded_files() {
		return $this->uploaded_files;
	}

	public function get_results() {
		return $this->results;
	}

	/**
	 * Helper function to convert a comma-separated list of file extensions to an array of MIME types
	 *
	 * @param string $extensions Comma-separated list of file extensions.
	 * @return array Array of corresponding MIME types.
	 *
	 * @since 1.9.3
	 */
	public function extensions_to_mime_types( $extensions ) {
		$all_mime_types     = get_allowed_mime_types(); // Retrieve list of allowed mime types and file extensions
		$extensions_array   = array_map( 'trim', explode( ',', $extensions ) ); // Convert the comma-separated string to an array
		$allowed_mime_types = [];

		foreach ( $extensions_array as $extension ) {
			// Loop through the array to find the MIME type for each extension. (e.g. 'jpg' => 'image/jpeg' & 'pdf' => 'application/pdf')
			foreach ( $all_mime_types as $ext_pattern => $mime ) {
				if ( preg_match( "!^($ext_pattern)$!i", $extension ) ) {
					$allowed_mime_types[] = $mime;
					break;
				}
			}
		}

		return $allowed_mime_types;
	}


	/**
	 * Handle with any files uploaded with form
	 */
	public function handle_files() {
		if ( empty( $_FILES ) ) {
			return [];
		}

		// https://developer.wordpress.org/reference/functions/wp_handle_upload/
		$overrides = [ 'action' => 'bricks_form_submit' ];

		$uploaded_files = [];

		$all_mime_types = get_allowed_mime_types();

		// Each form may have more than one input file type, each may have multiple files
		foreach ( $_FILES as $input_name => $files ) {
			if ( empty( $files['name'] ) ) {
				continue;
			}

			// Retrieve allowed mime types for this input field from form_settings (@since 1.9.3)
			$field_id                     = str_replace( 'form-field-', '', $input_name );
			$allowed_mime_types_for_field = $all_mime_types; // Default to default mime types if no mime types are set in the form field settings

			foreach ( $this->form_settings['fields'] as $field ) {
				// Maybe custom field name in used
				if ( ( $field['id'] === $field_id ) ||
					( ! empty( $field['name'] ) && $field['name'] === $field_id )
				) {
					// Retrieve allowed file extensions if any are set
					if ( ! empty( $field['fileUploadAllowedTypes'] ) ) {
						$allowed_file_extensions = $field['fileUploadAllowedTypes'] ?? '';

						// Convert the extensions to mime types
						$allowed_mime_types_for_field = $this->extensions_to_mime_types( $allowed_file_extensions );
					}

					break;
				}
			}

			foreach ( $files['name'] as $key => $value ) {
				$finfo          = finfo_open( FILEINFO_MIME_TYPE );
				$real_mime_type = finfo_file( $finfo, $files['tmp_name'][ $key ] );

				finfo_close( $finfo );

				// Check mime type (@since 1.9.3)
				if ( ! in_array( $real_mime_type, $allowed_mime_types_for_field, true ) ) {
					$this->set_error_messages( esc_html__( 'Uploaded file type is not allowed.', 'bricks' ) );
					$this->maybe_stop_processing();
					continue;
				}

				if ( empty( $files['name'][ $key ] ) || $files['error'][ $key ] !== UPLOAD_ERR_OK ) {
					continue;
				}

				$file = [
					'name'     => $files['name'][ $key ],
					'type'     => $files['type'][ $key ],
					'tmp_name' => $files['tmp_name'][ $key ],
					'error'    => $files['error'][ $key ],
					'size'     => $files['size'][ $key ],
				];

				// Temporarily upload file to 'uploads' folder to sent as email attachment, etc. (no sizes are generated)
				$uploaded = wp_handle_upload( $file, $overrides );

				// Upload success: Uploaded to 'uploads' folder
				if ( $uploaded && ! isset( $uploaded['error'] ) ) {
					/**
					 * STEP: Save uploaded file in custom directory (if set in form field setting)
					 *
					 * @since 1.9.2
					 */

					// Get file settings
					$save_file      = false;
					$field_id       = str_replace( 'form-field-', '', $input_name );
					$fields         = $this->form_settings['fields'] ?? [];
					$directory_name = false;

					foreach ( $fields as $field ) {
						if ( ( $field['id'] === $field_id ) ||
							( ! empty( $field['name'] ) && $field['name'] === $field_id )
						) {
							$save_file = $field['fileUploadStorage'] ?? false;

							if ( $save_file === 'directory' && ! empty( $field['fileUploadStorageDirectory'] ) ) {
								$directory_name = $field['fileUploadStorageDirectory'];
							}
						}
					}

					// Get directory path (e.g.: uploads/{directory_name})
					if ( $save_file === 'directory' ) {
						$directory_name = sanitize_file_name( $directory_name );
						$wp_upload_dir  = wp_upload_dir();
						$directory_path = $wp_upload_dir['basedir'] . "/$directory_name";
						$original_path  = $directory_path;

						// Apply Bricks filter for custom path (https://academy.bricksbuilder.io/article/filter-bricks-form-file_directory/)
						$directory_path = apply_filters( 'bricks/form/file_directory', $directory_path, $this, $input_name );

						// Directory path changed via filter above: Set $save_file to 'filter'
						if ( $directory_path !== $original_path ) {
							$save_file = 'filter';
						}

						// Create custom directory if needed
						if ( $directory_path && ! file_exists( $directory_path ) ) {
							wp_mkdir_p( $directory_path );
						}

						// Copy uploaded file to custom directory & remove the file if copy success from 'uploads' folder
						if ( $directory_path && is_dir( $directory_path ) && is_writable( $directory_path ) ) {
							$new_file_name = wp_unique_filename( $directory_path, $file['name'] );
							$new_path      = "$directory_path/$new_file_name";
							$copy          = copy( $uploaded['file'], $new_path );

							if ( $copy ) {
								// Remove the file if copy success
								@unlink( $uploaded['file'] );

								// Update file path to the new path (use in email attachment or handle_uploaded_files())
								$uploaded['file'] = $new_path;
							}
						}
					}

					// Add type, name to the uploaded file
					$uploaded['type'] = $file['type'];
					$uploaded['name'] = $file['name'];

					// For use in form submissions table (attachment, directory, filter)
					$uploaded['location'] = $save_file;

					$uploaded_files[ $input_name ][] = $uploaded;
				}
			}
		}

		return $uploaded_files;
	}

	/**
	 * Remove (default), keep uploaded or move files to media library (as attachment)
	 *
	 * @since 1.9.2
	 */
	public function handle_uploaded_files() {
		$uploaded_files = $this->get_uploaded_files();

		if ( empty( $uploaded_files ) ) {
			return;
		}

		// Loop through uploaded files
		foreach ( $uploaded_files as $input_name => $files ) {
			// Get file settings
			$save_file = false;
			$field_id  = str_replace( 'form-field-', '', $input_name );
			$fields    = $this->form_settings['fields'] ?? [];

			foreach ( $fields as $field ) {
				if ( ( $field['id'] === $field_id ) ||
					( ! empty( $field['name'] ) && $field['name'] === $field_id )
				) {
					$save_file = $field['fileUploadStorage'] ?? 'no';
				}
			}

			switch ( $save_file ) {
				case 'attachment':
					// Move uploaded files to media library as attachment
					foreach ( $files as $file ) {
						$attachment = [
							'post_mime_type' => $file['type'],
							'post_title'     => sanitize_file_name( $file['name'] ),
							'post_content'   => '',
							'post_status'    => 'inherit',
						];

						// Insert file as attachment
						$attachment_id = wp_insert_attachment( $attachment, $file['file'] );

						if ( $attachment_id ) {
							// Add attachment metadata from file
							$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file['file'] );
							wp_update_attachment_metadata( $attachment_id, $attachment_data );
						}
					}
					break;

				case 'directory':
					// Move uploaded file to custom directory
					break;

				default:
					// Default: Remove uploaded files
					foreach ( $files as $file ) {
						@unlink( $file['file'] );
					}
					break;
			}
		}
	}

	/**
	 * Eventually remove uploaded files
	 */
	public function remove_files() {
		$uploaded_files = $this->get_uploaded_files();

		if ( empty( $uploaded_files ) ) {
			return;
		}

		// Remove uploaded files
		foreach ( $uploaded_files as $input_name => $files ) {
			foreach ( $files as $file ) {
				@unlink( $file['file'] );
			}
		}
	}

	/**
	 * Replace any {{field_id}} by the submitted form field content and after renders dynamic data
	 *
	 * @param string $content
	 */
	public function render_data( $content ) {
		// \w: Matches any word character (alphanumeric & underscore).
		// Only matches low-ascii characters (no accented or non-roman characters).
		// Equivalent to [A-Za-z0-9_]
		// https://regexr.com/

		preg_match_all( '/{{(\w+)}}/', $content, $matches );

		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[1] as $key => $field_id ) {
				// Format: '{{zjkcdw}}' // Dynamic email data format
				$tag = $matches[0][ $key ];

				$value = $this->get_field_value( $field_id );

				$value = ! empty( $value ) && is_array( $value ) ? implode( ', ', $value ) : $value;

				$content = str_replace( $tag, $value, $content );
			}
		}

		$fields  = $this->get_fields();
		$post_id = isset( $fields['postId'] ) ? $fields['postId'] : 0;

		// Render dynamic data
		$content = bricks_render_dynamic_data( $content, $post_id );

		return $content;
	}

	/**
	 * Get value of individual form field by field ID
	 *
	 * @param string $field_id Field ID.
	 */
	public function get_field_value( $field_id = '' ) {
		$form_fields = $this->get_fields();

		// NOTE: Undocumented {{referrer_url}}
		if ( $field_id === 'referrer_url' && isset( $_POST['referrer'] ) ) {
			return esc_url( $_POST['referrer'] );
		}

		if ( empty( $field_id ) || ! array_key_exists( "form-field-{$field_id}", $form_fields ) ) {
			return '';
		}

		return $form_fields[ "form-field-{$field_id}" ];
	}

	/**
	 * Available actions after form submission
	 *
	 * @return array
	 */
	public static function get_available_actions() {
		$actions = [
			'custom'         => esc_html__( 'Custom', 'bricks' ),
			'email'          => esc_html__( 'Email', 'bricks' ),
			'redirect'       => esc_html__( 'Redirect', 'bricks' ),
			'mailchimp'      => 'Mailchimp',
			'sendgrid'       => 'SendGrid',
			'login'          => esc_html__( 'User login', 'bricks' ),
			'registration'   => esc_html__( 'User registration', 'bricks' ),
			'lost-password'  => esc_html__( 'Lost password', 'bricks' ),
			'reset-password' => esc_html__( 'Reset password', 'bricks' ),
		];

		// Save form submission (@since 1.9.2)
		if ( \Bricks\Database::get_setting( 'saveFormSubmissions', false ) ) {
			$actions['save-submission'] = esc_html__( 'Save submission', 'bricks' );
		}

		return $actions;
	}

	/**
	 * Set form submit error messages
	 *
	 * @param array $error_messages
	 *
	 * @since 1.7.1
	 */
	public function set_error_messages( $error_messages ) {
		if ( empty( $error_messages ) ) {
			return;
		}

		if ( is_string( $error_messages ) ) {
			$error_messages = [ $error_messages ];
		}

		// One error: Return error message as string
		if ( count( $error_messages ) === 1 ) {
			$this->set_result(
				[
					'type'    => 'error',
					'message' => $error_messages,
				]
			);

			return;
		}

		// More than one error: Return error messages as unordered list
		$message = '<ul>';

		// Combine $error_messages into a single string
		foreach ( $error_messages as $error_message ) {
			$message .= "<li>{$error_message}</li>";
		}

		$message .= '</ul>';

		$this->set_result(
			[
				'type'    => 'error',
				'message' => $message,
			]
		);
	}

	/**
	 * Validate required fields
	 *
	 * @param array|string $custom_validation_errors Custom validation errors adding via filter 'bricks_form_validation_errors'.
	 *
	 * @return array
	 *
	 * @since 1.7.1
	 */
	public function validate_required_fields( $custom_validation_errors = [] ) {
		$submitted_fields     = $this->get_fields();
		$uploaded_files       = $this->get_uploaded_files();
		$form_settings        = $this->get_settings();
		$form_settings_fields = ! empty( $form_settings['fields'] ) ? $form_settings['fields'] : [];

		$errors = [];

		foreach ( $form_settings_fields as $form_settings_field ) {
			// Skip if field is not required
			if ( empty( $form_settings_field['required'] ) ) {
				continue;
			}

			$error = false;

			// File field: Check if file is uploaded
			if ( $form_settings_field['type'] === 'file' ) {
				$field_name = "form-field-{$form_settings_field['id']}"; // default field name

				// Check for and use custom field name
				if ( ! empty( $form_settings_field['name'] ) ) {
					$field_name = $form_settings_field['name'];
				}

				if ( empty( $uploaded_files[ $field_name ] ) ) {
					$error = true;
				}
			}

			// All other field types
			else {
				if (
					! isset( $submitted_fields[ "form-field-{$form_settings_field['id']}" ] ) ||
					$submitted_fields[ "form-field-{$form_settings_field['id']}" ] === ''
				) {
					$error = true;
				}
			}

			if ( $error ) {
				// Field is required & empty: Add error message
				$field_label = ! empty( $form_settings_field['label'] ) ? $form_settings_field['label'] : $form_settings_field['type'];

				$errors[] = esc_html__( 'Required', 'bricks' ) . ": $field_label";
			}
		}

		// Custom validation error is a string: Convert to array
		if ( $custom_validation_errors && is_string( $custom_validation_errors ) ) {
			$custom_validation_errors = [ $custom_validation_errors ];
		}

		// Filter out empty error strings
		if ( is_array( $custom_validation_errors ) && count( $custom_validation_errors ) ) {
			$custom_validation_errors = array_filter( $custom_validation_errors );

			$errors = array_merge( $errors, $custom_validation_errors );
		}

		// Return: Array of validation errors (each error as a string, representing a single error message)
		return $errors;
	}
}
