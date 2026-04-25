<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Email extends Base {
	/**
	 * Send email
	 *
	 * @since 1.0
	 */
	public function run( $form ) {
		$form_settings = $form->get_settings();
		$form_fields   = $form->get_fields();

		// Email To
		if ( isset( $form_settings['emailTo'] ) && $form_settings['emailTo'] === 'custom' && ! empty( $form_settings['emailToCustom'] ) ) {
			$recipients = $form->render_data( $form_settings['emailToCustom'] );

			$recipients = explode( ',', $recipients );

			$recipients = array_map( 'trim', $recipients );

			$recipients = array_filter( $recipients, 'is_email' );
		}

		if ( empty( $recipients ) ) {
			$recipients = get_option( 'admin_email' );
		}

		// Email subject
		// translators: %s: Site name
		$subject = isset( $form_settings['emailSubject'] ) ? sanitize_text_field( $form->render_data( $form_settings['emailSubject'] ) ) : sprintf( esc_html__( '%s: New contact form message', 'bricks' ), get_bloginfo( 'name' ) );

		// Email content
		$line_break     = isset( $form_settings['htmlEmail'] ) ? '<br>' : "\n";
		$custom_message = ! empty( $form_settings['emailContent'] ) ? $form_settings['emailContent'] : '';
		$message        = $this->get_all_fields( $form_settings, $form );

		// Custom message
		if ( $custom_message ) {
			// Replace {{all_fields}} with all fields content (@since 1.9.2)
			$message = str_replace( '{{all_fields}}', $message, $custom_message );

			// Render email content (replace {{form_field_id}} with submitted value)
			$message = $form->render_data( $message );

			// Add line breaks if user didn't add an HTML template
			$message = isset( $form_settings['htmlEmail'] ) && strpos( $message, '<html' ) === false ? nl2br( wp_kses_post( $message ) ) : wp_kses_post( $message );
		}

		// Default message
		else {
			// Append referer
			if ( isset( $_POST['referrer'] ) ) {
				$message .= "{$line_break}{$line_break}" . esc_html__( 'Message sent from:', 'bricks' ) . ' ' . esc_url( $_POST['referrer'] );
			}
		}

		$email = [
			'to'      => $recipients,
			'subject' => $subject,
			'message' => $message,
		];

		// Email headers
		$headers = [];

		// Header: 'From'
		$from_email = ! empty( $form_settings['fromEmail'] ) ? sanitize_email( $form->render_data( $form_settings['fromEmail'] ) ) : false;

		if ( $from_email ) {
			$from_name = ! empty( $form_settings['fromName'] ) ? sanitize_text_field( $form->render_data( $form_settings['fromName'] ) ) : false;

			$headers[] = $from_name ? "From: $from_name <$from_email>" : "From: $from_email";
		}

		// Header: 'Content-Type'
		if ( isset( $form_settings['htmlEmail'] ) ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		// Header: 'Bcc'
		if ( isset( $form_settings['emailBcc'] ) ) {
			$headers[] = sprintf( 'Bcc: %s', $form->render_data( $form_settings['emailBcc'] ) );
		}

		// Header: 'Reply-To' email address
		$reply_to_email_address = ! empty( $form_settings['replyToEmail'] ) ? sanitize_email( $form->render_data( $form_settings['replyToEmail'] ) ) : '';

		if ( $reply_to_email_address ) {
			$headers[] = sprintf( 'Reply-To: %s', $reply_to_email_address );
		} else {
			// Use first valid email address found in submitted form data as 'Reply-To' email address (use for confirmation email too @since 1.7.2)
			foreach ( $form_fields as $key => $value ) {
				if ( is_string( $value ) && is_email( $value ) ) {
					$headers[]              = sprintf( 'Reply-To: %s', $value );
					$reply_to_email_address = $value;
					break;
				}
			}
		}

		// Add attachments if exist
		$attachments    = [];
		$uploaded_files = $form->get_uploaded_files() ?? [];

		if ( ! empty( $uploaded_files ) ) {
			foreach ( $uploaded_files as $input_name => $files ) {
				foreach ( $files as $file ) {
					$attachments[] = $file['file'];
				}
			}
		}

		// STEP: Send the email
		$email_sent = wp_mail( $email['to'], $email['subject'], $email['message'], $headers, $attachments );

		// STEP: Send confirmation email to submitted email address (@since 1.7.2)
		$confirmation_email_content = $form_settings['confirmationEmailContent'] ?? false;
		if ( $confirmation_email_content ) {
			// Replace {{all_fields}} with all fields content.
			$custom_message = str_replace( '{{all_fields}}', $message, $confirmation_email_content );

			// Render email content (replace {{form_field_id}} with submitted value)
			$confirmation_email_content = $form->render_data( $custom_message );
		}

		if ( $confirmation_email_content && $reply_to_email_address ) {
			$confirmation_email_to      = isset( $form_settings['confirmationEmailTo'] ) ? $form->render_data( $form_settings['confirmationEmailTo'] ) : $reply_to_email_address;
			$confirmation_email_subject = isset( $form_settings['confirmationEmailSubject'] ) ? $form->render_data( $form_settings['confirmationEmailSubject'] ) : get_bloginfo( 'name' ) . ': ' . esc_html__( 'Thank you for your message', 'bricks' );

			// Header: 'From'
			$confirmation_from_name  = isset( $form_settings['confirmationFromName'] ) ? $form->render_data( $form_settings['confirmationFromName'] ) : get_bloginfo( 'name' );
			$confirmation_from_email = isset( $form_settings['confirmationFromEmail'] ) ? $form->render_data( $form_settings['confirmationFromEmail'] ) : get_option( 'admin_email' );

			$confirmation_email_headers = [ "From: $confirmation_from_name <$confirmation_from_email>" ];

			// Header: 'Reply-To' email address (@since 1.9.5)
			$confirmation_email_reply_to_email = ! empty( $form_settings['confirmationReplyToEmail'] ) ? sanitize_email( $form->render_data( $form_settings['confirmationReplyToEmail'] ) ) : '';

			if ( $confirmation_email_reply_to_email ) {
				$confirmation_email_headers[] = sprintf( 'Reply-To: %s', $confirmation_email_reply_to_email );
			}

			if ( isset( $form_settings['confirmationEmailHTML'] ) ) {
				$confirmation_email_headers[] = 'Content-Type: text/html; charset=UTF-8';
			}

			// Send confirmation email
			$confirmation_sent = wp_mail(
				$confirmation_email_to,
				$confirmation_email_subject,
				$confirmation_email_content,
				$confirmation_email_headers
			);
		}

		// Error
		if ( ! $email_sent ) {
			$form->set_result(
				[
					'action'  => $this->name,
					'type'    => 'error',
					'message' => ! empty( $form_settings['emailErrorMessage'] ) ? $form_settings['emailErrorMessage'] : '',
					'content' => $message,
				]
			);
		} else {
			$form->set_result(
				[
					'action' => $this->name,
					'type'   => 'success',
				]
			);
		}
	}

	public function get_all_fields( $form_settings, $form ) {
		$line_break = isset( $form_settings['htmlEmail'] ) ? '<br>' : "\n";
		$message    = '';

		// @since 1.9.2 (#86bvxzgfc)
		foreach ( $form_settings['fields'] as $field ) {
			$field_label = $field['label'] ?? '';
			$field_id    = $field['id'] ?? '';
			$field_value = $form->get_field_value( $field_id );

			// Skip: Form field type 'file' and 'html'
			if ( $field['type'] === 'file' || $field['type'] === 'html' ) {
				continue;
			}

			if ( $field_label ) {
				$message .= "$field_label: ";
			}

			$value = is_array( $field_value ) && ! empty( $field_value ) ? implode( ', ', $field_value ) : $field_value;

			// Remove HTML tags from value (useful for 'textarea' field type) and add line breaks
			$message .= wp_kses_post( $value ) . $line_break;
		}

		return $message;
	}
}
