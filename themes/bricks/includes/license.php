<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class License {
	public static $license_key     = '';
	public static $license_status  = '';
	public static $remote_base_url = 'https://bricksbuilder.io/api/commerce/';

	public function __construct() {
		self::$license_key = get_option( 'bricks_license_key', false );

		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'check_for_update' ] );

		add_action( 'wp_ajax_bricks_activate_license', [ $this, 'activate_license' ] );
		add_action( 'wp_ajax_bricks_deactivate_license', [ $this, 'deactivate_license' ] );

		add_action( 'admin_notices', [ $this, 'admin_notices_license_activation' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices_license_mismatch' ] );
	}

	/**
	 * Check remotely if newer version of Bricks is available
	 *
	 * @param string $transient Transient for WordPress theme updates.
	 */
	public static function check_for_update( $transient ) {
		// 'checked' is an array with all installed themes and their version numbers
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$license_key = self::$license_key;

		if ( ! $license_key ) {
			return $transient;
		}

		// Installed theme data
		$theme_data        = wp_get_theme();
		$installed_version = $theme_data->Version; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Check if Bricks is parent theme (i.e. Bricks child theme in use)
		if ( wp_get_theme()->parent() ) {
			$installed_version = wp_get_theme()->parent()->get( 'Version' );
		}

		// Build theme update request URL with license_key and domain parameters
		$update_url = add_query_arg(
			[
				'license_key'       => $license_key,
				'domain'            => get_site_url(),
				'time'              => time(), // To avoid caching remote response
				'installed_version' => $installed_version,
			],
			self::$remote_base_url . 'download/get_update_data'
		);

		$request = Helpers::remote_get( $update_url );

		// Check if remote GET request has been successful (better than using is_wp_error)
		if ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			return $transient;
		}

		$request = json_decode( wp_remote_retrieve_body( $request ), true );

		// Check remotely if newer version of Bricks is available
		$latest_version          = isset( $request['new_version'] ) ? $request['new_version'] : $installed_version;
		$newer_version_available = version_compare( $latest_version, $installed_version, '>' );

		if ( $newer_version_available ) {
			// Save Bricks-specific update data in transient
			$transient->response['bricks'] = $request;
		}

		return $transient;
	}

	/**
	 * Check license status when loading builder
	 *
	 * @see template_redirect
	 */
	public static function license_is_valid() {
		// Skip license check for builder iframe (check happens in builder panel)
		if ( bricks_is_builder_iframe() ) {
			return true;
		}

		// Return: No license key found in db options table
		if ( ! self::$license_key ) {
			return false;
		}

		// Valid license status'
		return in_array(
			self::get_license_status(),
			[
				'active',       // Active license
				'processed',    // Order processed
				'past_due',     // Payment past due (subscription)
				'error_remote', // Remote server error (bricksbuilder.io)
			]
		);
	}

	/**
	 * Get license status (stored locally in transient: bricks_license_status)
	 *
	 * If transient has expired (after 168h) then get it remotely from Bricks server.
	 *
	 * @return array
	 */
	public static function get_license_status() {
		$license_key = self::$license_key;

		if ( ! $license_key ) {
			return false;
		}

		// Check license transient (expires after 168 hours)
		$transient_timeout          = get_option( '_transient_timeout_bricks_license_status' );
		$transient_timeout_in_hours = ( intval( $transient_timeout ) - time() ) / 60 / 60;

		$license_status = get_transient( 'bricks_license_status' );

		// No valid transient found: Get license status remotely
		if ( ! $transient_timeout || $transient_timeout_in_hours > 168 || false === $license_status ) {
			delete_transient( 'bricks_license_status' );

			$url = add_query_arg(
				[
					'license_key' => $license_key,
					'site'        => get_site_url(),
					'version'     => BRICKS_VERSION,
					'time'        => time(), // Avoid getting a cached remote response
				],
				self::$remote_base_url . 'license/get_status'
			);

			$response = Helpers::remote_get( $url );

			if ( is_wp_error( $response ) ) {
				$license_status = 'error_remote';
			}

			$response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $response['status'] ) ) {
				$license_status = $response['status'];
			}

			// Save license status in transient (expires after 168 hours)
			self::set_license_status( $license_status );
		}

		// Invalid license: Activate license on server (avoid having to deactivate & reactivate license for cloned sites, etc.)
		$invalid_license = ! in_array(
			$license_status,
			[
				'active',       // Active license
				'processed',    // Order processed
				'past_due',     // Payment past due (subscription)
				'error_remote', // Remote server error (bricksbuilder.io)
			]
		);

		if ( $invalid_license ) {
			$license_status = self::activate_license();

			return $license_status;
		}

		return $license_status;
	}

	/**
	 * Save license status in transient (expires after 168 hours)
	 */
	public static function set_license_status( $license_status ) {
		$expiration_time = 168 * HOUR_IN_SECONDS;

		set_transient( 'bricks_license_status', $license_status, $expiration_time );
	}

	/**
	 * Activate license under "Bricks > License" (AJAX call on "Activate license" click)
	 *
	 * Also runs via PHP in 'get_license_status' to avoid having to deactivate & reactivate license (when cloning staging site, etc.)
	 *
	 * @return array
	 */
	public static function activate_license() {
		$license_key = self::$license_key;
		$is_ajax     = bricks_is_ajax_call();

		if ( $is_ajax ) {
			Ajax::verify_nonce( 'bricks-nonce-admin' );

			if ( ! Capabilities::current_user_has_full_access() ) {
				wp_send_json_error( 'verify_request: Sorry, you are not allowed to perform this action.' );
			}

			if ( ! empty( $_POST['licenseKey'] ) ) {
				$license_key = sanitize_text_field( $_POST['licenseKey'] );
			}
		}

		// Remove all HTML tags from license key
		$license_key = $license_key ? trim( $license_key ) : '';
		$license_key = $license_key ? html_entity_decode( $license_key ) : '';
		$license_key = $license_key ? strip_tags( $license_key ) : '';

		// Return: No license key found/submitted
		if ( ! $license_key ) {
			if ( $is_ajax ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Invalid license key.', 'bricks' ) ] );
			} else {
				return;
			}
		}

		// Activate license
		$response = Helpers::remote_post(
			self::$remote_base_url . 'license/activate_license',
			[
				'sslverify' => false,
				'timeout'   => 30,
				'body'      => [
					'license_key' => $license_key,
					'site'        => get_site_url(),
					'version'     => BRICKS_VERSION,
				],
			]
		);

		// Check for remote error(s)
		if ( is_wp_error( $response ) ) {
			if ( $is_ajax ) {
				wp_send_json_error(
					[
						'message'  => $response->get_error_message(),
						'response' => $response,
					]
				);
			} else {
				return;
			}
		}

		$license_status = false;
		$response_code  = wp_remote_retrieve_response_code( $response );

		if ( $response_code != 200 ) {
			// Handle CloudFlare 403 "Forbidden" response
			if ( $response_code === 403 ) {
				$license_status = 'active';
			} elseif ( $is_ajax ) {
				wp_send_json_error(
					[
						'code'     => $response_code,
						'message'  => wp_remote_retrieve_response_message( $response ),
						'response' => $response,
					]
				);
			} else {
				return;
			}
		}

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $response['status'] ) ) {
			$license_status = $response['status'];
		}

		// Return remote error
		if ( $response['type'] === 'error' && isset( $response['message'] ) ) {
			if ( $is_ajax ) {
				wp_send_json_error( [ 'message' => $response['message'] ] );
			} else {
				return;
			}
		}

		// Return if no license status was sent back
		if ( ! $license_status ) {
			if ( $is_ajax ) {
				wp_send_json_error( [ 'message' => esc_html__( 'No license for provided license key found.', 'bricks' ) ] );
			} else {
				return;
			}
		}

		// Save license key in db options table
		update_option( 'bricks_license_key', $license_key );

		// Save license status in transient (expires after 168 hours)
		self::set_license_status( $license_status );

		if ( $is_ajax ) {
			wp_send_json_success(
				[
					'message' => esc_html__( 'License activated.', 'bricks' ),
					'status'  => $license_status,
				]
			);
		} else {
			return $license_status;
		}
	}

	/**
	 * Deactivate license
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public static function deactivate_license() {
		Ajax::verify_nonce( 'bricks-nonce-admin' );

		// Only a user with full access can deactivate the license (@since 1.5.4)
		if ( ! Capabilities::current_user_has_full_access() ) {
			wp_send_json_error( 'verify_request: Sorry, you are not allowed to perform this action.' );
		}

		// Deactivate license
		$response = Helpers::remote_post(
			self::$remote_base_url . 'license/deactivate_license',
			[
				'sslverify' => false,
				'timeout'   => 30,
				'body'      => [
					'license_key' => self::$license_key,
					'site'        => get_site_url(),
					'version'     => BRICKS_VERSION,
				],
			]
		);

		delete_option( 'bricks_license_key' );
		delete_transient( 'bricks_license_status' );
	}

	/**
	 * Admin notice to activate license
	 *
	 * @return null/string
	 */
	public static function admin_notices_license_activation() {
		// Show license key admin notice only to user roles which are allowed to use the builder
		if ( ! Capabilities::current_user_can_use_builder() ) {
			return;
		}

		// Don't show license admin notice on license page itself
		if ( get_current_screen()->id === 'bricks_page_bricks-license' ) {
			return;
		}

		// Check if license has been activated by checking for license key
		$license_key = self::$license_key;

		// Check: License activated (local)
		if ( isset( $license_key ) && ! empty( $license_key ) ) {
			return;
		}
		?>
		<div class="notice notice-info notice-license-activation">
			<div class="content-wrapper">
				<h4 class="title"><?php esc_html_e( 'Welcome to Bricks', 'bricks' ); ?></h4>
				<p><?php echo esc_html__( 'Activate your license to edit with Bricks, receive one-click updates, and access to all community templates.', 'bricks' ); ?></p>
			</div>

			<a class="button button-primary" href="<?php echo esc_url( BRICKS_ADMIN_PAGE_URL_LICENSE ); ?>"><?php esc_html_e( 'Activate license', 'bricks' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Admin notice to activate license
	 *
	 * @return null/string
	 */
	public static function admin_notices_license_mismatch() {
		// Show license key admin notice only to user roles which are allowed to use the builder
		if ( ! Capabilities::current_user_can_use_builder() ) {
			return;
		}

		// Don't show license admin notice on license page itself
		if ( get_current_screen()->id === 'bricks_page_bricks-license' ) {
			return;
		}

		// Check for license status 'website_inactive'
		$license_status = get_transient( 'bricks_license_status' );

		$license_error_title       = false;
		$license_error_description = false;

		switch ( $license_status ) {
			case 'license_key_invalid':
				$license_error_title       = esc_html__( 'Error: Invalid license key', 'bricks' );
				$license_error_description = esc_html__( 'Your provided license key is invalid. Please deactivate and then reactivate your license.', 'bricks' );
				break;

			case 'website_inactive':
				$license_error_title       = esc_html__( 'Error: License mismatch', 'bricks' );
				$license_error_description = esc_html__( 'Your website does not match your license key. Please deactivate and then reactivate your license.', 'bricks' );
				break;
		}

		if ( $license_error_title && $license_error_description ) {
			?>
		<div class="notice notice-error notice-license-mismatch">
			<div class="content-wrapper">
				<h4 class="title"><?php echo esc_html( $license_error_title ); ?></h4>
				<p><?php echo esc_html( $license_error_description ); ?></p>
			</div>
		</div>
			<?php
		}
	}

}
