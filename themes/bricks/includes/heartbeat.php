<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// To create new nonces when user gets logged out

class Heartbeat {
	/**
	 * WordPress REST API help docs:
	 *
	 * https://developer.wordpress.org/plugins/javascript/heartbeat-api/
	 */
	public function __construct() {
		// Don't run on non-builder frontend (bricks_is_builder check in init.php not working)
		if ( ! is_admin() && ! isset( $_GET[ BRICKS_BUILDER_PARAM ] ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Add login form to page
		add_action( 'wp_print_footer_scripts', 'wp_auth_check_html', 5 );

		add_filter( 'heartbeat_settings', [ $this, 'heartbeat_settings' ] );
		add_filter( 'heartbeat_received', [ $this, 'heartbeat_received' ], 10, 2 );

		add_filter( 'wp_refresh_nonces', [ $this, 'refresh_nonces' ], 30, 2 );
	}

	/**
	 * Enqueue styles and scripts
	 *
	 * @since 1.0
	 */
	public function enqueue_scripts() {
		// Interim login form via WP Heartbeat API
		wp_enqueue_script( 'heartbeat' );
		wp_enqueue_script( 'wp_auth_check', '/wp-includes/js/wp-auth-check.js', [ 'heartbeat' ], false, 1 );

		wp_localize_script(
			'wp_auth_check',
			'authcheckL10n',
			[
				'interval' => apply_filters( 'wp_auth_check_interval', BRICKS_AUTH_CHECK_INTERVAL ), // Default: 180 (seconds)
			]
		);

		wp_enqueue_style( 'wp_auth_check', '/wp-includes/css/wp-auth-check.css', [ 'dashicons' ], null, 'all' ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}

	/**
	 * Heartbeat settings
	 *
	 * @since 1.0
	 *
	 * @param array $settings Heartbeat settings.
	 */
	public function heartbeat_settings( $settings ) {
		$settings['interval'] = BRICKS_AUTH_CHECK_INTERVAL; // Default: 15

		return $settings;
	}

	/**
	 * Receive Heartbeat data and respond
	 *
	 * Processes data received via a Heartbeat request, and returns additional data to pass back to the front end.
	 *
	 * @since 1.0
	 *
	 * @param array $response Heartbeat response data to pass back to front end.
	 * @param array $data Data received from the front end (unslashed).
	 *
	 * @return array Heartbeat received response.
	 */
	public function heartbeat_received( $response, $data ) {
		$post_id = ! empty( $data['bricks']['postId'] ) ? intval( $data['bricks']['postId'] ) : 0;

		if ( $post_id ) {
			$response['bricksNonce']    = wp_create_nonce( 'bricks-nonce-builder' );
			$response['heartbeatNonce'] = wp_create_nonce( 'heartbeat-nonce' );

			if ( ! function_exists( 'wp_check_post_lock' ) || ! function_exists( 'wp_set_post_lock' ) ) {
				require_once ABSPATH . 'wp-admin/includes/post.php';
			}

			// No other user is editing this post right now: Set post '_edit_lock' post meta to this user
			$locked_by_user_id = wp_check_post_lock( $post_id );

			if ( ! $locked_by_user_id || isset( $data['bricks_set_post_lock'] ) ) {
				// 'bricks_set_post_lock' is set with click on 'Take Over' button
				wp_set_post_lock( $post_id );
			}

			// Another user is already editing this post
			else {
				$locked_user = $locked_by_user_id ? get_user_by( 'id', $locked_by_user_id ) : false;

				if ( $locked_user ) {
					$response['lockedUser'] = $locked_user->display_name;
				}
			}
		}

		return $response;
	}

	/**
	 * Refresh builder and Heartbeat nonce
	 *
	 * @since 1.0
	 *
	 * @param array $response Heartbeat response.
	 * @param array $data Data received.
	 *
	 * @return array Newly created new nonces.
	 */
	public function refresh_nonces( $response, $data ) {
		if ( isset( $data['bricks']['postId'] ) ) {
			$response['bricks']['bricksNonce']    = wp_create_nonce( 'bricks-nonce-builder' );
			$response['bricks']['heartbeatNonce'] = wp_create_nonce( 'heartbeat-nonce' );
		}

		return $response;
	}
}
