<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Responsible for handling the custom redirection logic for authentication-related pages.
 *
 * Login page
 * Registration page
 * Lost password page
 * Reset password page
 *
 * @since 1.9.2
 */
class Auth_Redirects {
	public function __construct() {
		add_action( 'wp_loaded', [ $this, 'handle_auth_redirects' ] );
		add_action( 'wp_login', [ $this, 'clear_bypass_auth_cookie' ] );
	}

	/**
	 * Main function to handle authentication redirects
	 *
	 * Depending on the current URL and the action parameter, decides which page to redirect to.
	 */
	public function handle_auth_redirects() {
		/**
		 * STEP: Set the bypass cookie (expires in 5 minutes)
		 *
		 * If the 'use_default_wp' URL parameter is set and the Global setting 'brx_use_wp_login' is not disabled.
		 *
		 * @since 1.9.4
		 */
		if ( isset( $_GET['brx_use_wp_login'] ) && ! Database::get_setting( 'disable_brx_use_wp_login' ) ) {
			setcookie(
				'brx_use_wp_login',
				'1',
				[
					'expires'  => time() + 5 * 60, // Expires in 5 minutes
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Strict',
				]
			);
		}

		// STEP: Check if the bypass cookie is set, and if so, bypass redirects (@since 1.9.4)
		if ( isset( $_COOKIE['brx_use_wp_login'] ) && $_COOKIE['brx_use_wp_login'] === '1' ) {
			return;
		}

		$request_uri      = esc_url_raw( $_SERVER['REQUEST_URI'] ?? '' );
		$current_url_path = wp_parse_url( home_url( $request_uri ), PHP_URL_PATH );

		$wp_login_url_path         = wp_parse_url( wp_login_url(), PHP_URL_PATH );
		$wp_registration_url_path  = wp_parse_url( wp_registration_url(), PHP_URL_PATH );
		$wp_lost_password_url_path = wp_parse_url( wp_lostpassword_url(), PHP_URL_PATH );

		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : null;

		// STEP: Filter to allow custom logic for redirects
		$custom_redirect_url = apply_filters( 'bricks/auth/custom_redirect_url', null, $current_url_path );

		if ( ! is_null( $custom_redirect_url ) ) {
			wp_safe_redirect( $custom_redirect_url );
			exit;
		}

		if ( $current_url_path === $wp_login_url_path ) { // Login page & actions
			switch ( $action ) {
				case null:
					$this->redirect_to_custom_login_page();
					break;
				case 'lostpassword':
					$this->redirect_to_custom_lost_password_page();
					break;
				case 'register':
					$this->redirect_to_custom_registration_page();
					break;
				case 'rp': // Reset password
					$this->redirect_to_custom_reset_password_page();
					break;
			}
		} elseif ( $current_url_path === $wp_registration_url_path ) { // Registration page fallback
			$this->redirect_to_custom_registration_page();
		} elseif ( $current_url_path === $wp_lost_password_url_path ) { // Lost password page fallback
			$this->redirect_to_custom_lost_password_page();
		}
	}

	/**
	 * Clears the bypass cookie when the user logs in.
	 */
	public function clear_bypass_auth_cookie() {
		if ( isset( $_COOKIE['brx_use_wp_login'] ) ) {
			   // Ensure the path and domain match where the cookie was set
			setcookie(
				'brx_use_wp_login',
				'',
				[
					'expires'  => time() - 3600,
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Strict'
				]
			);

			unset( $_COOKIE['brx_use_wp_login'] );
		}
	}

	/**
	 * Redirects to the custom login page if it's set and valid.
	 */
	private function redirect_to_custom_login_page() {
		$selected_login_page_id = Database::get_setting( 'login_page' );

		 // Filter for the login page redirect
		$selected_login_page_id = apply_filters( 'bricks/auth/custom_login_redirect', $selected_login_page_id );

		$this->redirect_if_valid_page( $selected_login_page_id );
	}

	/**
	 * Redirects to the custom lost password page if it's set and valid.
	 */
	private function redirect_to_custom_lost_password_page() {
		$selected_lost_password_page_id = Database::get_setting( 'lost_password_page' );

		// Filter for the lost password page redirect
		$selected_lost_password_page_id = apply_filters( 'bricks/auth/custom_lost_password_redirect', $selected_lost_password_page_id );

		$this->redirect_if_valid_page( $selected_lost_password_page_id );
	}

	/**
	 * Redirects to the custom registration page if it's set and valid.
	 */
	private function redirect_to_custom_registration_page() {
		$selected_registration_page_id = Database::get_setting( 'registration_page' );

		// Filter for the registration page redirect
		$selected_registration_page_id = apply_filters( 'bricks/auth/custom_registration_redirect', $selected_registration_page_id );

		$this->redirect_if_valid_page( $selected_registration_page_id );
	}

	/**
	 * Redirects to the custom reset password page if it's set and valid.
	 */
	private function redirect_to_custom_reset_password_page() {
		$selected_reset_password_page_id = Database::get_setting( 'reset_password_page' );

		// Filter for the reset password page redirect
		$selected_reset_password_page_id = apply_filters( 'bricks/auth/custom_reset_password_redirect', $selected_reset_password_page_id );

		$this->redirect_if_valid_page( $selected_reset_password_page_id );
	}

	/**
	 * Helper function to redirect to the provided page if it's valid.
	 * If the page is not valid, redirects to a default URL if provided.
	 *
	 * @param int $selected_page_id The ID of the page to redirect to.
	 */
	private function redirect_if_valid_page( $selected_page_id ) {
		if ( $this->is_custom_page_valid( $selected_page_id ) ) {
			$custom_url = get_permalink( $selected_page_id );

			// Preserve query parameters
			if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
				$custom_url = add_query_arg( $_GET, $custom_url );

				$parameters = $_GET;
				if ( is_array( $parameters ) ) {
					foreach ( $parameters as $key => $value ) {
						$parameters[ $key ] = Helpers::sanitize_value( $value );
					}

					$custom_url = add_query_arg( $key, $value, $custom_url );
				}
			}

			if ( $custom_url ) {
				wp_safe_redirect( $custom_url );
				exit;
			}
		}
	}

	/**
	 * Checks if the custom page is valid.
	 *
	 * @param int $page_id
	 *
	 * @return bool
	 */
	private function is_custom_page_valid( $page_id ) {
		return $page_id && get_post_status( $page_id ) === 'publish';
	}
}
