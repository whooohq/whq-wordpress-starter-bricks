<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Revisions {
	/**
	 * Bricks-specific revisions for header, content and footer data saved in post meta table
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'add_revisions_to_all_bricks_enabled_post_types' ], 999 );

		add_filter( 'wp_revisions_to_keep', [ $this, 'wp_revisions_to_keep' ], 10, 2 );

		add_action( 'wp_ajax_bricks_get_revisions', [ $this, 'ajax_get_revisions' ] );
		add_action( 'wp_ajax_bricks_get_revision_data', [ $this, 'ajax_get_revision_data' ] );
		add_action( 'wp_ajax_bricks_delete_revision', [ $this, 'ajax_delete_revision' ] );
		add_action( 'wp_ajax_bricks_delete_all_revisions_of_post_id', [ $this, 'ajax_delete_all_revisions_of_post_id' ] );
	}

	/**
	 * Get all revisions of a specific post via AJAX
	 *
	 * @uses get_revisions()
	 *
	 * @since 1.0
	 */
	public static function ajax_get_revisions() {
		Ajax::verify_request( 'bricks-nonce-builder' );

		$post_id = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;

		if ( ! $post_id ) {
			wp_die();
		}

		$revisions = self::get_revisions( $post_id );

		wp_send_json_success( $revisions );
	}

	/**
	 * Get revision data
	 *
	 * @since 1.0
	 */
	public static function ajax_get_revision_data() {
		Ajax::verify_request( 'bricks-nonce-builder' );

		$area        = ! empty( $_POST['area'] ) ? sanitize_text_field( $_POST['area'] ) : '';
		$post_id     = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;
		$revision_id = ! empty( $_POST['revisionId'] ) ? intval( $_POST['revisionId'] ) : 0;

		if ( ! $area || ! $post_id || ! $revision_id ) {
			wp_die();
		}

		$revision_data = Database::get_data( $revision_id, $area );

		wp_send_json_success( $revision_data );
	}

	/**
	 * Delete specific revision
	 *
	 * @uses get_revisions()
	 *
	 * @return array Post revisions.
	 *
	 * @since 1.0
	 */
	public static function ajax_delete_revision() {
		Ajax::verify_request( 'bricks-nonce-builder' );

		$post_id     = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;
		$revision_id = ! empty( $_POST['revisionId'] ) ? intval( $_POST['revisionId'] ) : 0;

		if ( ! $post_id || ! $revision_id ) {
			wp_die();
		}

		$deleted_revision = wp_delete_post_revision( $revision_id );

		$revisions = self::get_revisions( $post_id );

		wp_send_json_success( $revisions );
	}

	/**
	 * Delete all revisions of specific post
	 *
	 * @return array Post revisions.
	 *
	 * @since 1.0
	 */
	public static function ajax_delete_all_revisions_of_post_id() {
		Ajax::verify_request( 'bricks-nonce-builder' );

		$post_id = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;

		// Return: No post ID
		if ( ! $post_id ) {
			return;
		}

		global $wpdb;
		$query = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = $post_id AND post_type = %s", 'revision' ) ); // phpcs:ignore

		if ( $query ) {
			foreach ( $query as $id ) {
				wp_delete_post_revision( (int) $id );
			}
		}

		// Should be an empty array if all revisions for that post have been deleted succsessfully
		$revisions = self::get_revisions( $post_id );

		wp_send_json_success( [ 'revisions' => $revisions ] );
	}

	/**
	 * Get all revisions of a specific post
	 *
	 * @param int   $post_id
	 * @param array $query_args
	 *
	 * @since 1.0
	 */
	public static function get_revisions( $post_id, $query_args = [] ) {
		$default_query_args = [
			'posts_per_page' => BRICKS_MAX_REVISIONS_TO_KEEP,
		];

		$query_args = wp_parse_args( $query_args, $default_query_args );

		$posts = wp_get_post_revisions( $post_id, $query_args );

		$revisions = [];

		$current_time = current_time( 'timestamp' );

		foreach ( $posts as $revision ) {
			$human_time_diff = human_time_diff( strtotime( $revision->post_modified ), $current_time );

			$author = get_the_author_meta( 'display_name', $revision->post_author );

			$revisions[] = [
				'type'      => strpos( $revision->post_name, 'autosave' ) === false ? 'revision' : 'autosave',
				'id'        => $revision->ID,
				'author'    => $author,
				// translators: %s: human-readable time difference
				'humanDate' => sprintf( esc_html__( '%s ago', 'bricks' ), $human_time_diff ),
				'date'      => date_i18n( _x( 'M j @ H:i', 'revision date format', 'bricks' ), strtotime( $revision->post_modified ) ),
				'avatar'    => get_avatar_url( $revision->post_author, [ 'size' => 30 ] ),
			];
		}

		return $revisions;
	}

	/**
	 * Add revisions to all Bricks builder enabled post types
	 *
	 * @since 1.0
	 */
	public static function add_revisions_to_all_bricks_enabled_post_types() {
		$supported_post_types = Helpers::get_supported_post_types();

		foreach ( $supported_post_types as $post_type => $label ) {
			add_post_type_support( $post_type, 'revisions' );
		}
	}

	/**
	 * Max. number of revisions to store in db
	 *
	 * @param int    $num
	 * @param string $post
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public static function wp_revisions_to_keep( $num, $post ) {
		$supported_post_types = Helpers::get_supported_post_types();
		$supported_post_types = array_keys( $supported_post_types );

		// For Bricks templates and all Bricks enabled post types
		if ( $post->post_type === BRICKS_DB_TEMPLATE_SLUG || ( in_array( $post->post_type, $supported_post_types ) && Helpers::get_editor_mode( $post->ID ) === 'bricks' ) ) {
			$num = BRICKS_MAX_REVISIONS_TO_KEEP;
		}

		return $num;
	}
}
