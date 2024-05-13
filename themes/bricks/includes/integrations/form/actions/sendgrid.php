<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Sendgrid extends Base {
	const DB_OPTIONS_KEY = 'bricks_sendgrid_lists';

	public static $api_base_url = 'https://api.sendgrid.com/v3/';

	/**
	 * Builder: Get list options
	 */
	public static function get_list_options() {
		return bricks_is_builder() ? get_option( self::DB_OPTIONS_KEY, [] ) : [];
	}

	public static function get_api_key() {
		return \Bricks\Database::get_setting( 'apiKeySendgrid', false );
	}

	/**
	 * Get headers
	 *
	 * https://sendgrid.api-docs.io/v3.0/how-to-use-the-sendgrid-v3-api/api-authentication
	 *
	 * @return array $headers
	 */
	public static function get_headers() {
		$headers = [
			'Authorization' => 'Bearer ' . self::get_api_key(),
			'Content-Type'  => 'application/json',
		];

		return $headers;
	}

	/**
	 * API request: Get lists
	 *
	 * https://sendgrid.api-docs.io/v3.0/lists/get-all-lists
	 *
	 * @since 1.0
	 */
	public static function sync_lists() {
		$api_key = self::get_api_key();

		// Don't sync if the API key doesn't exist.
		if ( empty( $api_key ) ) {
			return [];
		}

		$response = \Bricks\Helpers::remote_get(
			self::$api_base_url . 'marketing/lists',
			[
				'method'  => 'GET',
				'headers' => self::get_headers()
			]
		);

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Get lists
		$lists = [];

		if ( isset( $response_body['result'] ) ) {
			foreach ( $response_body['result'] as $list ) {
				$lists[ $list['id'] ] = $list['name'];
			}
		}

		update_option( self::DB_OPTIONS_KEY, $lists );

		return $lists;
	}

	/**
	 * Add contact to list
	 *
	 * @since 1.0
	 */
	public function run( $form ) {
		$form_settings = $form->get_settings();
		$form_fields   = $form->get_fields();

		$first_name    = isset( $form_settings['sendgridFirstName'] ) && isset( $form_fields[ "form-field-{$form_settings['sendgridFirstName']}" ] ) ? $form_fields[ "form-field-{$form_settings['sendgridFirstName']}" ] : false;
		$last_name     = isset( $form_settings['sendgridLastName'] ) && isset( $form_fields[ "form-field-{$form_settings['sendgridLastName']}" ] ) ? $form_fields[ "form-field-{$form_settings['sendgridLastName']}" ] : false;
		$email_address = isset( $form_settings['sendgridEmail'] ) && isset( $form_fields[ "form-field-{$form_settings['sendgridEmail']}" ] ) ? $form_fields[ "form-field-{$form_settings['sendgridEmail']}" ] : false;

		// Throw error if no email address provided
		if ( ! $email_address ) {
			$form->set_result(
				[
					'action'  => $this->name,
					'type'    => 'error',
					'message' => esc_html__( 'No email address provided.', 'bricks' )
				]
			);
			return;
		}

		$new_subscriber_data = [
			'email' => $email_address,
		];

		if ( $first_name ) {
			$new_subscriber_data['first_name'] = $first_name;
		}

		if ( $last_name ) {
			$new_subscriber_data['last_name'] = $last_name;
		}

		// Run double opt in action hook // NOTE: Not documented.
		do_action( 'bricks_sendgrid_double_opt_in_handler', $new_subscriber_data );

		// Add contact to Sendgrid via HTTP PUT method (https://sendgrid.api-docs.io/v3.0/contacts/add-or-update-a-contact)
		$body = [
			'method'   => 'PUT',
			'contacts' => [
				$new_subscriber_data
			],
		];

		if ( isset( $form_settings['sendgridList'] ) ) {
			$body['list_ids'] = [ $form_settings['sendgridList'] ];
		}

		$response = wp_remote_post(
			self::$api_base_url . 'marketing/contacts',
			[
				'method'  => 'PUT',
				'headers' => self::get_headers(),
				'body'    => wp_json_encode( $body ),
			]
		);

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		// Error
		if ( $response_code === 400 ) {
			$type    = 'error';
			$message = isset( $form_settings['sendgridErrorMessage'] ) ? $form_settings['sendgridErrorMessage'] : '';
			$body    = $response_body['errors'][0]['message'] . '.';
		}

		// Success
		else {
			$type    = 'success';
			$message = '';
			$body    = $response_body;
		}

		$form->set_result(
			[
				'action'  => $this->name,
				'type'    => $type,
				'message' => $message,
				'body'    => $body,
			]
		);
	}
}
