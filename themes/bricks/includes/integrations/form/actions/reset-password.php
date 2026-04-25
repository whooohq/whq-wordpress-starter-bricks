<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit;

class Reset_Password extends Base {
	public function run( $form ) {
		$form_settings = $form->get_settings();
		$form_fields   = $form->get_fields();

		// STEP: Extract reset key and login from the form data
		$key   = $form_fields['form-field-key'] ?? '';
		$login = $form_fields['form-field-login'] ?? '';
		$user  = check_password_reset_key( $key, $login );

		if ( is_wp_error( $user ) ) {
			// Invalid key
			$form->set_result(
				[
					'action'  => $this->name,
					'type'    => 'error',
					'message' => esc_html__( 'Invalid password reset key', 'bricks' ),
				]
			);
			return;
		}

		$new_password = isset( $form_settings['resetPasswordNew'] ) && isset( $form_fields[ "form-field-{$form_settings['resetPasswordNew']}" ] ) ? $form_fields[ "form-field-{$form_settings['resetPasswordNew']}" ] : '';

		// Return: No password provided
		if ( ! $new_password ) {
			$form->set_result(
				[
					'action'  => $this->name,
					'type'    => 'error',
					'message' => esc_html__( 'Please provide a new password', 'bricks' ),
				]
			);
			return;
		}

		// STEP: Update user password
		reset_password( $user, $new_password );

		// STEP: Return a success message
		$form->set_result(
			[
				'action' => $this->name,
				'type'   => 'success',
			]
		);
	}
}
