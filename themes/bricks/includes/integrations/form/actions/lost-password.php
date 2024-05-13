<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Lost_Password extends Base {
	/**
	 * Lost password
	 */
	public function run( $form ) {
		$form_settings = $form->get_settings();
		$form_fields   = $form->get_fields();

		$user_identifier = isset( $form_settings['lostPasswordEmailUsername'] ) && isset( $form_fields[ "form-field-{$form_settings['lostPasswordEmailUsername']}" ] ) ? $form_fields[ "form-field-{$form_settings['lostPasswordEmailUsername']}" ] : false;

		// STEP: Get user by email or username
		$user = is_email( $user_identifier ) ? get_user_by( 'email', $user_identifier ) : get_user_by( 'login', $user_identifier );

		// Use default success message, if no custom message is set
		$message = ! isset( $form_settings['successMessage'] ) ? esc_html__( 'If this account exists, a password reset link will be sent to the associated email address.', 'bricks' ) : '';

		// Set success message
		$form->set_result(
			[
				'action'  => $this->name,
				'type'    => 'success',
				'message' => $message,
			]
		);

		// Send password reset email
		if ( $user && ! is_wp_error( $user ) ) {
			retrieve_password( $user->user_login );
		}

		return;
	}
}
