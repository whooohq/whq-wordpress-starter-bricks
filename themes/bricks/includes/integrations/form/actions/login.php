<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Login extends Base {
	/**
	 * User login
	 *
	 * @since 1.0
	 */
	public function run( $form ) {
		$form_settings = $form->get_settings();
		$form_fields   = $form->get_fields();

		$user_login    = isset( $form_settings['loginName'] ) && isset( $form_fields[ "form-field-{$form_settings['loginName']}" ] ) ? $form_fields[ "form-field-{$form_settings['loginName']}" ] : false;
		$user_password = isset( $form_settings['loginPassword'] ) && isset( $form_fields[ "form-field-{$form_settings['loginPassword']}" ] ) ? $form_fields[ "form-field-{$form_settings['loginPassword']}" ] : false;
		$remember      = isset( $form_settings['loginRemember'] ) && isset( $form_fields[ "form-field-{$form_settings['loginRemember']}" ] );

		// Login response: WP_User on success, WP_Error on failure
		$login_response = wp_signon(
			[
				'user_login'    => $user_login,
				'user_password' => $user_password,
				'remember'      => $remember,
			]
		);

		// Login error
		if ( is_wp_error( $login_response ) ) {
			$form->set_result(
				[
					'action'  => $this->name,
					'type'    => 'error',
					'message' => $form_settings['loginErrorMessage'] ?? $login_response->get_error_message(),
				]
			);

			return;
		}

		// Check for the 'redirect_to' URL parameter
		$redirect_to = esc_url_raw( $form_fields['form-field-redirect_to'] ?? '' );

		// Validate and redirect if 'redirect_to' is present (@since 1.9.4)
		if ( $redirect_to && wp_http_validate_url( $redirect_to ) ) {
			$form->set_result(
				[
					'action'     => $this->name,
					'type'       => 'redirect',
					'redirectTo' => $redirect_to,
				]
			);
		} else {
			$form->set_result(
				[
					'action'         => $this->name,
					'type'           => 'success',
					'login_response' => $login_response,
				]
			);
		}
	}
}
