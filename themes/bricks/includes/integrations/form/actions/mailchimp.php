<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Mailchimp extends Base {
	const DB_OPTIONS_KEY = 'bricks_mailchimp_lists';

	public static $url     = '';
	public static $args    = [];
	public static $api_key = '';

	public static function prepare_request() {
		self::$api_key = \Bricks\Database::get_setting( 'apiKeyMailchimp', false );

		// Part 0: API key / Part 1: Data center
		$data_center = '';

		if ( self::$api_key ) {
			$api_key_parts = explode( '-', self::$api_key );
			$data_center   = $api_key_parts[1] ?? '';
		}

		self::$url = self::$api_key && $data_center ? "https://{$data_center}.api.mailchimp.com/3.0/" : false;

		// Basic HTTP authentication: Enter any string as your username and supply your API Key as the password
		self::$args = [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( 'user:' . self::$api_key ),
			],
		];
	}

	/**
	 * Builder: Get list options
	 */
	public static function get_list_options() {
		return bricks_is_builder() ? get_option( self::DB_OPTIONS_KEY, [] ) : [];
	}

	/**
	 * API request
	 *
	 * http://developer.mailchimp.com/documentation/mailchimp/guides/get-started-with-mailchimp-api-3/
	 * http://developer.mailchimp.com/documentation/mailchimp/reference/overview/
	 *
	 * @param string $resource What kind of information to request (i.e. 'lists', 'groups' etc.).
	 *
	 * @since 1.0
	 */
	public static function get_response_body( $resource ) {
		$response      = \Bricks\Helpers::remote_get( self::$url . $resource, self::$args );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		return $response_body;
	}

	/**
	 * API request: Get lists and groups
	 *
	 * @since 1.0 Store lists and groups in options table (previously requested on each builder ControlSelect.vue render)
	 * @since 1.0
	 */
	public static function sync_lists() {
		self::prepare_request();

		// Don't sync if the API key doesn't exist.
		if ( empty( self::$api_key ) ) {
			return [];
		}

		$response_body = self::get_response_body( 'lists' );

		$lists = [];

		if ( ! empty( $response_body['lists'] ) ) {
			foreach ( $response_body['lists'] as $list ) {
				$list_id = $list['id'];

				$lists[ $list_id ] = [
					'name'   => $list['name'],
					'groups' => self::sync_groups( $list_id ),
				];
			}
		}

		update_option( self::DB_OPTIONS_KEY, $lists );

		return $lists;
	}

	/**
	 * API request: Get list groups (i.e.: 'interest-categories')
	 *
	 * @since 1.0
	 */
	public static function sync_groups( $list_id ) {
		$response_groups = self::get_response_body( "lists/$list_id/interest-categories" );
		$categories      = isset( $response_groups['categories'] ) && is_array( $response_groups['categories'] ) ? $response_groups['categories'] : [];

		// Get groups (i.e.: categories + interests)
		$groups = [];

		foreach ( $categories as $category ) {
			$category_id   = $category['id'];
			$response_body = self::get_response_body( "lists/$list_id/interest-categories/$category_id/interests" );
			$interests     = isset( $response_body['interests'] ) && is_array( $response_body['interests'] ) ? $response_body['interests'] : [];

			foreach ( $interests as $interest ) {
				$groups[ $interest['id'] ] = $category['title'] . ' - ' . $interest['name'];
			}
		}

		return $groups;
	}

	/**
	 * Subscribe to list and groups
	 *
	 * @since 1.0
	 */
	public function run( $form ) {
		$form_settings = $form->get_settings();
		$form_fields   = $form->get_fields();

		$email_address = isset( $form_fields[ "form-field-{$form_settings['mailchimpEmail']}" ] ) ? $form_fields[ "form-field-{$form_settings['mailchimpEmail']}" ] : false;

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

		// https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/
		$resource = sprintf(
			'lists/%s/members/%s',              // Endpoint to add subscriber
			$form_settings['mailchimpList'],    // List ID
			md5( strtolower( $email_address ) ) // Subscriber hash
		);

		$new_subscriber = [
			'email_address' => $email_address,
			'status'        => isset( $form_settings['mailchimpDoubleOptIn'] ) ? 'pending' : 'subscribed',
		];

		if ( isset( $form_settings['mailchimpFirstName'] ) ) {
			$new_subscriber['merge_fields']['FNAME'] = $form_fields[ "form-field-{$form_settings['mailchimpFirstName']}" ];
		}

		if ( isset( $form_settings['mailchimpLastName'] ) ) {
			$new_subscriber['merge_fields']['LNAME'] = $form_fields[ "form-field-{$form_settings['mailchimpLastName']}" ];
		}

		if ( isset( $form_settings['mailchimpGroups'] ) && is_array( $form_settings['mailchimpGroups'] ) ) {
			foreach ( $form_settings['mailchimpGroups'] as $interest_id ) {
				$new_subscriber['interests'][ $interest_id ] = true;
			}
		}

		$args['body'] = wp_json_encode( $new_subscriber );

		// Subscribe to Mailchimp
		self::prepare_request();
		$response = $this->post( $resource, $args );

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		// Pending
		if ( isset( $form_settings['mailchimpDoubleOptIn'] ) ) {
			$type    = 'info';
			$message = isset( $form_settings['mailchimpPendingMessage'] ) ? $form_settings['mailchimpPendingMessage'] : '';
			$body    = $response_body;
		}

		// Success (subscribed)
		elseif ( $response_code === 200 ) {
			$type    = 'success';
			$message = '';
			$body    = $response_body;
		}

		// Error
		else {
			$type    = 'error';
			$message = isset( $form_settings['mailchimpErrorMessage'] ) ? $form_settings['mailchimpErrorMessage'] : '';
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

	/**
	 * POST request to Mailchimp API
	 *
	 * @param string $resource
	 * @param rray   $args
	 *
	 * @since 1.0
	 */
	public function post( $resource, $args ) {
		self::$url  .= $resource;
		self::$args += $args;

		self::$args['headers']['Content-Type'] = 'application/json; charset=utf-8';
		self::$args['method']                  = 'PUT';

		$response = wp_remote_post( self::$url, self::$args );

		return $response;
	}
}
