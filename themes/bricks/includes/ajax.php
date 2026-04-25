<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Ajax {
	public function __construct() {
		// In builder
		add_action( 'wp_ajax_bricks_generate_code_signature', [ $this, 'generate_code_signature' ] );

		add_action( 'pre_update_option_' . BRICKS_DB_GLOBAL_ELEMENTS, [ $this, 'update_global_elements' ], 10, 3 );

		add_filter( 'sanitize_post_meta_' . BRICKS_DB_PAGE_CONTENT, [ $this, 'sanitize_bricks_postmeta' ], 10, 3 );
		add_filter( 'sanitize_post_meta_' . BRICKS_DB_PAGE_HEADER, [ $this, 'sanitize_bricks_postmeta' ], 10, 3 );
		add_filter( 'sanitize_post_meta_' . BRICKS_DB_PAGE_FOOTER, [ $this, 'sanitize_bricks_postmeta' ], 10, 3 );
		add_action( 'update_post_metadata', [ $this, 'update_bricks_postmeta' ], 10, 5 );

		add_action( 'wp_ajax_bricks_download_image', [ $this, 'download_image' ] );
		add_action( 'wp_ajax_bricks_get_image_metadata', [ $this, 'get_image_metadata' ] );
		add_action( 'wp_ajax_bricks_get_image_from_custom_field', [ $this, 'get_image_from_custom_field' ] );

		add_action( 'wp_ajax_bricks_get_dynamic_data_preview_content', [ $this, 'get_dynamic_data_preview_content' ] );

		add_action( 'wp_ajax_bricks_get_posts', [ $this, 'get_posts' ] );
		add_action( 'wp_ajax_bricks_get_terms_options', [ $this, 'get_terms_options' ] );
		add_action( 'wp_ajax_bricks_get_users', [ $this, 'get_users' ] );

		add_action( 'wp_ajax_bricks_render_data', [ $this, 'render_data' ] );

		add_action( 'wp_ajax_bricks_publish_post', [ $this, 'publish_post' ] );
		add_action( 'wp_ajax_bricks_save_post', [ $this, 'save_post' ] );
		add_action( 'wp_ajax_bricks_create_autosave', [ $this, 'create_autosave' ] );
		add_action( 'wp_ajax_bricks_get_builder_url', [ $this, 'get_builder_url' ] );

		add_action( 'wp_ajax_bricks_save_global_element', [ $this, 'save_global_element' ] );
		add_action( 'wp_ajax_bricks_save_color_palette', [ $this, 'save_color_palette' ] );
		add_action( 'wp_ajax_bricks_save_panel_width', [ $this, 'save_panel_width' ] );
		add_action( 'wp_ajax_bricks_save_builder_scale_off', [ $this, 'save_builder_scale_off' ] );
		add_action( 'wp_ajax_bricks_save_builder_width_locked', [ $this, 'save_builder_width_locked' ] );

		add_action( 'wp_ajax_bricks_render_element', [ $this, 'render_element' ] );

		add_action( 'wp_ajax_bricks_get_pages', [ $this, 'get_pages' ] );
		add_action( 'wp_ajax_bricks_create_new_page', [ $this, 'create_new_page' ] );

		add_action( 'wp_ajax_bricks_get_my_templates_data', [ $this, 'get_my_templates_data' ] );

		add_action( 'wp_ajax_bricks_get_remote_templates_data', [ $this, 'get_remote_templates_data' ] );

		add_action( 'wp_ajax_bricks_get_current_user_id', [ $this, 'get_current_user_id' ] );

		add_action( 'wp_ajax_bricks_query_loop_delete_random_seed_transient', [ $this, 'query_loop_delete_random_seed_transient' ] );

		// In Gutenberg
		add_action( 'wp_ajax_bricks_get_html_from_content', [ $this, 'get_html_from_content' ] );

		// Get template elements by template ID
		add_action( 'wp_ajax_bricks_get_template_elements_by_id', [ $this, 'get_template_elements_by_id' ] );

		// Get custom shape divider SVG from URL (@since 1.8.6)
		add_action( 'wp_ajax_bricks_get_custom_shape_divider', [ $this, 'get_custom_shape_divider' ] );

		// Frontend: Regenerate form nonce (@since 1.9.6)
		add_action( 'wp_ajax_bricks_regenerate_form_nonce', [ $this, 'regenerate_form_nonce' ] );
		add_action( 'wp_ajax_nopriv_bricks_regenerate_form_nonce', [ $this, 'regenerate_form_nonce' ] );
	}

	/**
	 * Builder: Generate code signature
	 *
	 * @since 1.9.7
	 */
	public function generate_code_signature() {
		self::verify_request( 'bricks-nonce-builder' );

		if (
			! empty( $_POST['element'] ) &&
			Helpers::code_execution_enabled() &&
			Capabilities::current_user_has_full_access() &&
			Capabilities::current_user_can_execute_code()
		) {
			$element  = self::decode( $_POST['element'], false );
			$elements = Admin::process_elements_for_signature( [ $element ] );

			wp_send_json_success( [ 'element' => $elements[0] ] );
		}

		wp_send_json_error( esc_html__( 'Not allowed', 'bricks' ) );
	}

	/**
	 * Decode stringified JSON data
	 *
	 * @since 1.0
	 */
	public static function decode( $data, $run_wp_slash = true ) {
		$data = stripslashes( $data );
		$data = json_decode( $data, true );
		$data = $run_wp_slash ? wp_slash( $data ) : $data; // Make sure we keep the good slashes on update_post_meta

		return $data;
	}

	/**
	 * Form element: Regenerate nonce
	 *
	 * @since 1.9.6
	 */
	public function regenerate_form_nonce() {
		echo wp_create_nonce( 'bricks-nonce-form' );
		wp_die();
	}

	/**
	 * Verify nonce (AJAX call)
	 *
	 * wp-admin: 'bricks-nonce-admin'
	 * builder:  'bricks-nonce-builder'
	 * frontend: 'bricks-nonce' (= default)
	 *
	 * @return void
	 */
	public static function verify_nonce( $nonce = 'bricks-nonce' ) {
		if ( ! check_ajax_referer( $nonce, 'nonce', false ) ) {
			wp_send_json_error( "verify_nonce: \"$nonce\" is invalid." );
		}
	}

	/**
	 * Verify request: nonce and user access
	 *
	 * Check for builder in order to not trigger on wp_auth_check
	 *
	 * @since 1.0
	 */
	public static function verify_request( $nonce = 'bricks-nonce' ) {
		self::verify_nonce( $nonce );

		// Verify user access (get_the_ID() returns 0 in AJAX call)
		$post_id = isset( $_POST['postId'] ) ? intval( $_POST['postId'] ) : get_the_ID();

		if ( ! Capabilities::current_user_can_use_builder( $post_id ) ) {
			wp_send_json_error( 'verify_request: User can not use builder (' . get_current_user_id() . ')' );
		}
	}

	/**
	 * Save color palette
	 *
	 * @since 1.0
	 */
	public function save_color_palette() {
		self::verify_request( 'bricks-nonce-builder' );

		if ( isset( $_POST['colorPalette'] ) ) {
			$color_palette_updated = update_option( BRICKS_DB_COLOR_PALETTE, stripslashes_deep( $_POST['colorPalette'] ) );
			wp_send_json_success( $color_palette_updated );
		} else {
			wp_send_json_error( [ 'message' => esc_html__( 'New color could not be saved.', 'bricks' ) ] );
		}
	}

	/**
	 * Save panel width
	 *
	 * @since 1.0
	 */
	public function save_panel_width() {
		self::verify_request( 'bricks-nonce-builder' );

		// Min. panel width check to fix disappearing panel issue
		$panel_width = isset( $_POST['panelWidth'] ) ? intval( $_POST['panelWidth'] ) : 0;

		if ( $panel_width >= 100 ) {
			$panel_width_updated = update_option( BRICKS_DB_PANEL_WIDTH, $panel_width );
			wp_send_json_success(
				[
					'panel_width_updated' => $panel_width_updated,
					'panel_width'         => $panel_width,
				]
			);
		} else {
			wp_send_json_error(
				[
					'message'     => esc_html__( 'Panel width could not be saved.', 'bricks' ),
					'panel_width' => $panel_width,
				]
			);
		}
	}

	/**
	 * Save builder state 'off' (enabled by default)
	 *
	 * @since 1.3.2
	 */
	public function save_builder_scale_off() {
		self::verify_request( 'bricks-nonce-builder' );

		$scale_off = isset( $_POST['off'] ) ? $_POST['off'] == 'true' : false;
		$user_id   = get_current_user_id();

		if ( $scale_off ) {
			update_user_meta( $user_id, BRICKS_DB_BUILDER_SCALE_OFF, true );
		} else {
			delete_user_meta( $user_id, BRICKS_DB_BUILDER_SCALE_OFF );
		}

		wp_send_json_success(
			[
				'scale_off' => $scale_off,
				'user_id'   => $user_id,
			]
		);
	}

	/**
	 * Save builder width locked state (disabled by default)
	 *
	 * Only apply for bas breakpoint. Allows users on smaller screen not having to set a custom width on every page load.
	 *
	 * @since 1.3.2
	 */
	public function save_builder_width_locked() {
		self::verify_request( 'bricks-nonce-builder' );

		$preview_width = isset( $_POST['width'] ) ? intval( $_POST['width'] ) : false;
		$user_id       = get_current_user_id();

		if ( $preview_width ) {
			update_user_meta( $user_id, BRICKS_DB_BUILDER_WIDTH_LOCKED, $preview_width );
		} else {
			delete_user_meta( $user_id, BRICKS_DB_BUILDER_WIDTH_LOCKED );
		}

		wp_send_json_success(
			[
				'preview_width' => $preview_width,
				'user_id'       => $user_id,
			]
		);
	}

	/**
	 * Get pages
	 *
	 * @since 1.0
	 */
	public function get_pages() {
		self::verify_request( 'bricks-nonce-builder' );

		$query_args = [
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'post_type'      => ! empty( $_GET['postType'] ) ? sanitize_text_field( $_GET['postType'] ) : 'page',
			'fields'         => 'ids'
		];

		// NOTE: Undocumented
		$query_args = apply_filters( 'bricks/ajax/get_pages_args', $query_args );

		$page_ids = get_posts( $query_args );

		$pages = [];

		foreach ( $page_ids as $page_id ) {
			$page_title = wp_kses_post( get_the_title( $page_id ) );

			// NOTE: Undocumented
			$page_title = apply_filters( 'bricks/builder/post_title', $page_title, $page_id );

			$page_data = [
				'id'      => $page_id,
				'title'   => $page_title,
				'status'  => get_post_status( $page_id ),
				'slug'    => get_post_field( 'post_name', $page_id ),
				'editUrl' => Helpers::get_builder_edit_link( $page_id ),
			];

			if ( has_post_thumbnail( $page_id ) ) {
				$image_size         = ! empty( $_GET['imageSize'] ) ? sanitize_text_field( $_GET['imageSize'] ) : 'large';
				$page_data['image'] = get_the_post_thumbnail_url( $page_id, $image_size );
			}

			$pages[] = $page_data;
		}

		wp_send_json_success( $pages );
	}

	/**
	 * Create new page
	 *
	 * @since 1.0
	 */
	public function create_new_page() {
		self::verify_request( 'bricks-nonce-builder' );

		$new_page_id = wp_insert_post(
			[
				'post_title' => ! empty( $_POST['title'] ) ? esc_html( $_POST['title'] ) : esc_html__( '(no title)', 'bricks' ),
				'post_type'  => ! empty( $_POST['postType'] ) ? esc_html( $_POST['postType'] ) : 'page',
			]
		);

		wp_send_json_success( $new_page_id );
	}

	/**
	 * Render element HTML from settings
	 *
	 * builder.php (query_content_type_for_elements_html to generate HTML for builder load)
	 * AJAX call / REST API call: In-builder (getHTML for PHP-rendered elements)
	 *
	 * @since 1.0
	 */
	public static function render_element( $data ) {
		$is_ajax = bricks_is_ajax_call();

		if ( $is_ajax && isset( $_POST ) ) {
			$data = $_POST;
		}

		$loop_element = $data['loopElement'] ?? false;
		$element      = $data['element'] ?? false;
		$element_name = $element['name'] ?? false;

		// AJAX call
		if ( $is_ajax ) {
			// Check: Current user can use builder
			self::verify_request( 'bricks-nonce-builder' );

			$element = stripslashes_deep( $element );
		}

		// REST API call (Permissions already checked in the API->render_element_permissions_check())
		elseif ( bricks_is_rest_call() ) {
		}

		// builder.php (query_content_type_for_elements_html)
		else {
		}

		/**
		 * Builder: Init Query to get the builder preview for the first loop item (e.g.: "Product Category Image" DD)
		 *
		 * @since 1.4
		 */
		if ( ! empty( $loop_element ) ) {
			$query = new Query( $loop_element );

			if ( ! empty( $query->count ) ) {
				$query->is_looping = true;

				// NOTE: Use array_shift because not all the results are sequential arrays (e.g. JetEngine)
				$query->loop_object = $query->object_type == 'post' ? $query->query_result->posts[0] : array_shift( $query->query_result );
			}
		}

		// Init element class (i.e. new Bricks\Element_Alert( $element ))
		$element_class_name = isset( Elements::$elements[ $element_name ]['class'] ) ? Elements::$elements[ $element_name ]['class'] : false;

		if ( class_exists( $element_class_name ) ) {
			$element['is_frontend'] = false;

			$element_instance = new $element_class_name( $element );
			$element_instance->load();

			// Init element: enqueue styles/scripts, render element
			ob_start();
			$element_instance->init();
			$response = ob_get_clean();
			// NOTE: stripslashes no longer in use (@since 1.8.5) as they caused unicode characters to be escaped (e.g. \u00a0) (#862jxcrde; #862jxw1w7)
			// $response = stripslashes( $response );
		}

		// Element doesn't exist
		else {
			// translators: %s: Element name
			$response = '<div class="bricks-element-placeholder no-php-class">' . sprintf( esc_html__( 'Element "%s" doesn\'t exist.', 'bricks' ), $element_name ) . '</div>';
		}

		if ( $is_ajax ) {
			// Template element: Add additional builder data (CSS & list of elements to run scripts (@since 1.5))
			if ( $element_name === 'template' ) {
				$template_id = ! empty( $element['settings']['template'] ) ? $element ['settings']['template'] : false;

				if ( $template_id ) {
					$additional_data = Element_Template::get_builder_call_additional_data( $template_id );

					$response = array_merge( [ 'html' => $response ], $additional_data );
				}
			}

			// Subsequent element render via AJAX call
			wp_send_json_success( $response );
		}

		// Initial element render via PHP or REST API
		else {
			return $response;
		}
	}

	/**
	 * Generate the HTML based on the builder content data (post Id or content)
	 *
	 * Used to feed Rank Math SEO analyses
	 *
	 * Note: This method doesn't generate styles
	 */
	public function get_html_from_content() {
		if ( bricks_is_builder() ) {
			$nonce = 'bricks-nonce-builder';
		} elseif ( is_admin() ) {
			$nonce = 'bricks-nonce-admin';
		} else {
			$nonce = 'bricks-nonce';
		}

		self::verify_request( $nonce );

		$post_id = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : false;
		$data    = ! empty( $_POST['content'] ) ? self::decode( $_POST['content'], false ) : get_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT, true );

		if ( is_array( $data ) ) {
			$data = array_map( 'Bricks\Helpers::set_is_frontend_to_false', $data );
		}

		$html = ! empty( $data ) ? Frontend::render_data( $data ) : '';

		wp_send_json_success( [ 'html' => $html ] );
	}

	/**
	 * Get template elements by template ID
	 *
	 * To generate global classes CSS in builder.
	 *
	 * @since 1.8.2
	 */
	public function get_template_elements_by_id() {
		self::verify_request( 'bricks-nonce-builder' );

		$template_ids            = ! empty( $_POST['templateIds'] ) ? self::decode( $_POST['templateIds'] ) : [];
		$template_elements_by_id = [];

		if ( is_array( $template_ids ) ) {
			foreach ( $template_ids as $template_id ) {
				$template_elements_by_id[ $template_id ] = get_post_meta( $template_id, BRICKS_DB_PAGE_CONTENT, true );
			}
		}

		wp_send_json_success( $template_elements_by_id );
	}

	/**
	 * Add/remove global element
	 *
	 * @since 1.0
	 */
	public function save_global_element() {
		self::verify_request( 'bricks-nonce-builder' );

		if ( ! Capabilities::current_user_has_full_access() ) {
			wp_send_json_error( esc_html__( 'Not allowed', 'bricks' ) );
		}

		// Get global elements from database
		$global_elements = Database::$global_data['elements'] ?? [];

		$new_global_element   = isset( $_POST['element'] ) ? stripslashes_deep( $_POST['element'] ) : false;
		$delete_element_index = isset( $_POST['index'] ) ? $_POST['index'] : false;

		// Save new global element
		if ( $new_global_element ) {
			if ( empty( $new_global_element['label'] ) ) {
				$new_global_element['label'] = ucwords( str_replace( '-', ' ', $new_global_element['name'] ) );
			}

			array_unshift( $global_elements, $new_global_element );
		}

		// Delete global element
		elseif ( is_numeric( $delete_element_index ) ) {
			array_splice( $global_elements, $delete_element_index, 1 );
		}

		if ( count( $global_elements ) === 0 ) {
			delete_option( BRICKS_DB_GLOBAL_ELEMENTS );
		} else {
			$global_elements = Helpers::security_check_elements_before_save( $global_elements, null, 'global' );
			update_option( BRICKS_DB_GLOBAL_ELEMENTS, $global_elements );
		}

		// Return updated global elements 'settings' array
		wp_send_json_success( $global_elements );
	}

	/**
	 * Update global elements options in database
	 */
	public function update_global_elements( $new_value, $old_value, $option ) {
		if ( $option === BRICKS_DB_GLOBAL_ELEMENTS ) {
			$new_value = Helpers::security_check_elements_before_save( $new_value, null, 'global' );
		}

		return $new_value;
	}

	/**
	 * Query control: Get posts
	 *
	 * @since 1.0
	 */
	public function get_posts() {
		self::verify_request( 'bricks-nonce-builder' );

		$post_type = 'any';

		// Get specific post type
		if ( ! empty( $_GET['postType'] ) ) {
			$post_type = array_map( 'sanitize_text_field', (array) $_GET['postType'] );
		}

		// Get all public post types
		else {
			$post_type = get_post_types( [ 'public' => true ] );
			$post_type = array_keys( $post_type );
		}

		// Set query args
		$query_args = [ 'post_type' => $post_type ];

		// Necessary to retrieve more than 2 posts initially
		if ( $post_type !== 'any' ) {
			$query_args['orderby'] = 'date';
		}

		if ( ! empty( $_GET['search'] ) ) {
			$query_args['s'] = stripslashes_deep( sanitize_text_field( $_GET['search'] ) );
		}

		$posts = Helpers::get_posts_by_post_id( $query_args );

		foreach ( $posts as $post_id => $post_title ) {
			// NOTE: Undocumented
			$posts[ $post_id ] = apply_filters( 'bricks/builder/post_title', $post_title, $post_id );
		}

		// If AJAX request contains "include" parameter, make sure some post_ids are included in the response
		if ( ! empty( $_GET['include'] ) ) {
			$include_post_ids = (array) $_GET['include'];
			$include_post_ids = array_map( 'intval', $include_post_ids );

			foreach ( $include_post_ids as $post_id ) {
				if ( ! array_key_exists( $post_id, $posts ) ) {
					$posts[ $post_id ] = get_the_title( $post_id );
				}
			}
		}

		wp_send_json_success( $posts );
	}

	/**
	 * Get users
	 *
	 * @since 1.2.2
	 *
	 * @return void
	 */
	public function get_users() {
		self::verify_request( 'bricks-nonce-builder' );

		$args = [
			'count_total' => false,
			'number'      => 50,
		];

		$search_term = ! empty( $_GET['search'] ) ? stripslashes_deep( sanitize_text_field( $_GET['search'] ) ) : '';
		if ( $search_term ) {
			$args['search'] = $search_term;
		}

		// Query users
		$users = Helpers::get_users_options( $args, true );

		if ( ! empty( $_GET['include'] ) ) {
			$include_user_ids = (array) $_GET['include'];
			$include_user_ids = array_map( 'intval', $include_user_ids );

			foreach ( $include_user_ids as $user_id ) {
				if ( ! array_key_exists( $user_id, $users ) ) {
					$user = get_userdata( $user_id );
					if ( $user ) {
						$users[ $user_id ] = $user->display_name;
					}
				}
			}
		}

		wp_send_json_success( $users );
	}

	/**
	 * Get terms
	 *
	 * @since 1.0
	 */
	public function get_terms_options() {
		self::verify_request( 'bricks-nonce-builder' );

		$post_types = ! empty( $_GET['postTypes'] ) ? array_map( 'sanitize_text_field', $_GET['postTypes'] ) : null;
		$taxonomy   = ! empty( $_GET['taxonomy'] ) ? sanitize_text_field( $_GET['taxonomy'] ) : null;
		$terms      = [];

		if ( ! empty( $post_types ) ) {
			foreach ( (array) $post_types as $post_type ) {
				$type_terms = Helpers::get_terms_options( $taxonomy, $post_type );

				if ( ! empty( $type_terms ) ) {
					$terms = array_merge( $terms, $type_terms );
				}
			}
		} elseif ( ! empty( $taxonomy ) ) {
			$terms = Helpers::get_terms_options( $taxonomy );
		}

		wp_send_json_success( $terms );
	}

	/**
	 * Render Bricks data for static header/content/footer and query loop preview HTML in builder
	 *
	 * @since 1.0
	 */
	public static function render_data() {
		self::verify_request( 'bricks-nonce-builder' );

		if ( empty( $_POST['elements'] ) ) {
			return;
		}

		$area     = ! empty( $_POST['area'] ) ? sanitize_text_field( $_POST['area'] ) : 'content';
		$post_id  = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;
		$elements = self::decode( $_POST['elements'], false );
		$elements = array_map( 'Bricks\Helpers::set_is_frontend_to_false', $elements );

		// Set Theme Styles (for correct preview of query loop nodes)
		Theme_Styles::load_set_styles( $post_id );

		// Use global elements data from builder (@since 1.7.1)
		$global_elements = ! empty( $_POST['globalElements'] ) ? $_POST['globalElements'] : [];

		if ( is_array( $global_elements ) && count( $global_elements ) ) {
			foreach ( $elements as $index => $element ) {
				$global_element_id = ! empty( $element['global'] ) ? $element['global'] : false;

				if ( $global_element_id ) {
					foreach ( $global_elements as $global_element ) {
						if ( ! empty( $global_element['global'] ) && $global_element['global'] == $global_element_id ) {
							$elements[ $index ]['settings'] = $global_element['settings'];

							// To skip getting element setting from db in Frontend::render_data() > render_element() later on
							$elements[ $index ]['global_settings_checked'] = true;
						}
					}
				}
			}
		}

		// Generate query loop styles for dynamic data (@since 1.8)
		$loop_name = "loop_{$post_id}";

		// Use loop element ID as loop_name if possible
		if ( isset( $elements[0]['id'] ) ) {
			$loop_name = "loop_{$elements[0]['id']}";
		}

		// Generate Assets before Frontend render to add 'data-query-loop-index' attribute successfully in builder
		Assets::generate_css_from_elements( $elements, $loop_name );

		$inline_css = Assets::$inline_css[ $loop_name ] ?? '';

		$html = Frontend::render_data( $elements, $area );

		$inline_css .= Assets::$inline_css_dynamic_data;

		/**
		 * Add missing global classes in builder preview if template element loop
		 *
		 * @since 1.8.2 If not static area (global classes are already added in dynamic area)
		 */
		if ( ! isset( $_POST['staticArea'] ) ) {
			$inline_css .= Assets::generate_global_classes();
		}

		$styles = ! empty( $inline_css ) ? "\n<style id=\"bricks-$loop_name\">/* {$loop_name} CSS */\n{$inline_css}</style>\n" : '';

		$data = [
			'html'   => $html,
			'styles' => $styles,
		];

		// Run query to get query results count in builder (@since 1.9.1)
		$element = ! empty( $_POST['element'] ) ? self::decode( $_POST['element'], false ) : false;

		if ( $element ) {
			$query               = new Query( $element );
			$query_results_count = $query->count;

			$data[ "query_results_count:{$element['id']}" ] = $query_results_count;
		}

		wp_send_json_success( $data );
	}

	/**
	 * Don't check for chnage when creating revision as all that changed is the postmeta
	 *
	 * @since 1.7
	 */
	public function dont_check_for_revision_changes() {
		return false;
	}

	/**
	 * Save post
	 *
	 * @since 1.0
	 */
	public function save_post() {
		self::verify_request( 'bricks-nonce-builder' );

		// Return: No post ID set
		$post_id = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( 'Error: No postId provided!' );
		}

		$post = get_post( $post_id );

		// Update post at the very end (@since 1.6)
		$the_post = false;

		/**
		 * Save revision in database
		 */
		$revision_id = 0;

		// Check: Bricks elements data changed. If not, don't save post & don't create revision (@since 1.7.1)
		$bricks_data_changed = isset( $_POST['header'] ) || isset( $_POST['content'] ) || isset( $_POST['footer'] );

		// Page settings changed: Re-generate external CSS file (@since 1.8)
		if ( ! $bricks_data_changed ) {
			$bricks_data_changed = isset( $_POST['pageSettings'] );
		}

		/**
		 * Create revision if data contains 'header', 'footer', or 'content'
		 *
		 * To avoid create false empty revision.
		 *
		 * @since 1.7.1 (if check added)
		 */
		if ( $bricks_data_changed ) {
			// Disabled WordPress content diff check
			add_filter( 'wp_save_post_revision_check_for_changes', [ $this, 'dont_check_for_revision_changes' ] );

			$revision_id = wp_save_post_revision( $post );

			// Delete autosave (@since 1.7)
			if ( $revision_id ) {
				$autosave = wp_get_post_autosave( $post_id );

				if ( $autosave ) {
					wp_delete_post_revision( $autosave );
				}
			}

			remove_filter( 'wp_save_post_revision_check_for_changes', [ $this, 'dont_check_for_revision_changes' ] );
		}

		// Check user capabilities (@since 1.5.4)
		$has_full_access = Capabilities::current_user_has_full_access();

		/**
		 * Save color palettes
		 *
		 * @since 1.4
		 */
		if ( isset( $_POST['colorPalette'] ) && $has_full_access ) {
			$color_palette = self::decode( $_POST['colorPalette'], false );

			if ( is_array( $color_palette ) && count( $color_palette ) ) {
				update_option( BRICKS_DB_COLOR_PALETTE, $color_palette );
			} else {
				delete_option( BRICKS_DB_COLOR_PALETTE );
			}
		}

		/**
		 * Save global classes
		 *
		 * @since 1.4
		 */
		if ( isset( $_POST['globalClasses'] ) && $has_full_access ) {
			$global_classes = self::decode( $_POST['globalClasses'], false );

			Helpers::save_global_classes_in_db( $global_classes, "ajax_save_post_id_$post_id" );
		}

		/**
		 * Save global classes locked
		 *
		 * @since 1.4
		 */
		if ( isset( $_POST['globalClassesLocked'] ) && $has_full_access ) {
			$global_classes_locked = self::decode( $_POST['globalClassesLocked'], false );

			if ( is_array( $global_classes_locked ) && count( $global_classes_locked ) ) {
				update_option( BRICKS_DB_GLOBAL_CLASSES_LOCKED, $global_classes_locked, false );
			} else {
				delete_option( BRICKS_DB_GLOBAL_CLASSES_LOCKED );
			}
		}

		/**
		 * Save global classes categories
		 *
		 * @since 1.9.4
		 */
		if ( isset( $_POST['globalClassesCategories'] ) && $has_full_access ) {
			$global_classes_categories = self::decode( $_POST['globalClassesCategories'], false );

			if ( is_array( $global_classes_categories ) && count( $global_classes_categories ) ) {
				update_option( BRICKS_DB_GLOBAL_CLASSES_CATEGORIES, $global_classes_categories, false );
			} else {
				delete_option( BRICKS_DB_GLOBAL_CLASSES_CATEGORIES );
			}
		}

		/**
		 * Save global elements
		 *
		 * @since 1.4
		 */
		if ( isset( $_POST['globalElements'] ) && $has_full_access ) {
			$global_elements = self::decode( $_POST['globalElements'], false );

			if ( is_array( $global_elements ) && count( $global_elements ) ) {
				$global_elements = Helpers::security_check_elements_before_save( $global_elements, null, 'global' );
				update_option( BRICKS_DB_GLOBAL_ELEMENTS, $global_elements );
			} else {
				delete_option( BRICKS_DB_GLOBAL_ELEMENTS );
			}
		}

		/**
		 * Save pinned elements
		 *
		 * @since 1.4
		 */

		if ( isset( $_POST['pinnedElements'] ) && $has_full_access ) {
			$pinned_elements = self::decode( $_POST['pinnedElements'], false );

			if ( is_array( $pinned_elements ) && count( $pinned_elements ) ) {
				update_option( BRICKS_DB_PINNED_ELEMENTS, $pinned_elements );
			} else {
				delete_option( BRICKS_DB_PINNED_ELEMENTS );
			}
		}

		/**
		 * Save pseudo-classes
		 *
		 * @since 1.4
		 */
		if ( isset( $_POST['pseudoClasses'] ) && $has_full_access ) {
			$global_pseudo_classes = self::decode( $_POST['pseudoClasses'] );

			if ( is_array( $global_pseudo_classes ) && count( $global_pseudo_classes ) ) {
				update_option( BRICKS_DB_PSEUDO_CLASSES, $global_pseudo_classes );
			} else {
				delete_option( BRICKS_DB_PSEUDO_CLASSES );
			}
		}

		/**
		 * Save theme styles
		 *
		 * @since 1.4
		 */
		if ( isset( $_POST['themeStyles'] ) && $has_full_access ) {
			$theme_styles = self::decode( $_POST['themeStyles'], false );

			foreach ( $theme_styles as $theme_style_id => $theme_style ) {
				// Remove empty settings 'group'
				if ( isset( $theme_style['settings'] ) ) {
					foreach ( $theme_style['settings'] as $group_key => $group_settings ) {
						if ( ! $group_settings || ( is_array( $group_settings ) && ! count( $group_settings ) ) ) {
							unset( $theme_styles[ $theme_style_id ]['settings'][ $group_key ] );
						}
					}
				}
			}

			if ( is_array( $theme_styles ) && count( $theme_styles ) ) {
				update_option( BRICKS_DB_THEME_STYLES, $theme_styles );
			} else {
				delete_option( BRICKS_DB_THEME_STYLES );
			}
		}

		/**
		 * Save page data (post meta table)
		 */
		$header  = isset( $_POST['header'] ) ? self::decode( $_POST['header'] ) : [];
		$content = isset( $_POST['content'] ) ? self::decode( $_POST['content'] ) : [];
		$footer  = isset( $_POST['footer'] ) ? self::decode( $_POST['footer'] ) : [];

		/**
		 * Save page setting
		 *
		 * @since 1.4
		 */
		if ( isset( $_POST['pageSettings'] ) && $has_full_access ) {
			$page_settings = self::decode( $_POST['pageSettings'] );

			if ( is_array( $page_settings ) && count( $page_settings ) ) {
				if ( ! empty( $page_settings['postName'] ) || ! empty( $page_settings['postTitle'] ) ) {
					$the_post['ID'] = $post_id;
				}

				// Update post name (slug)
				if ( ! empty( $page_settings['postName'] ) ) {
					$the_post['post_name'] = trim( $page_settings['postName'] );

					unset( $page_settings['postName'] );
				}

				// Update post title
				if ( ! empty( $page_settings['postTitle'] ) ) {
					$the_post['post_title'] = trim( $page_settings['postTitle'] );

					unset( $page_settings['postTitle'] );
				}

				update_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, $page_settings );
			} else {
				delete_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS );
			}
		}

		/**
		 * Bricks template
		 *
		 * @since 1.4
		 */
		$template_type = ! empty( $_POST['templateType'] ) ? sanitize_text_field( $_POST['templateType'] ) : false;

		if ( $template_type ) {
			update_post_meta( $post_id, BRICKS_DB_TEMPLATE_TYPE, $template_type );

			switch ( $template_type ) {
				// Header template
				case 'header':
					if ( isset( $_POST['header'] ) ) {
						// @since 1.5.4
						$header = Helpers::security_check_elements_before_save( $header, $post_id, 'header' );

						if ( is_array( $header ) && count( $header ) ) {
							// Save revision in post meta ('update_post_meta' can't process post type 'revision'
							if ( $revision_id ) {
								update_metadata( 'post', $revision_id, BRICKS_DB_PAGE_HEADER, $header );
							}

							update_post_meta( $post_id, BRICKS_DB_PAGE_HEADER, $header );
						} else {
							delete_post_meta( $post_id, BRICKS_DB_PAGE_HEADER );
						}
					}
					break;

				// Footer template
				case 'footer':
					if ( isset( $_POST['footer'] ) ) {
						// @since 1.5.4
						$footer = Helpers::security_check_elements_before_save( $footer, $post_id, 'footer' );

						if ( is_array( $footer ) && count( $footer ) ) {
							// Save revision in post meta ('update_post_meta' can't process post type 'revision'
							if ( $revision_id ) {
								update_metadata( 'post', $revision_id, BRICKS_DB_PAGE_FOOTER, $footer );
							}

							update_post_meta( $post_id, BRICKS_DB_PAGE_FOOTER, $footer );
						} else {
							delete_post_meta( $post_id, BRICKS_DB_PAGE_FOOTER );
						}
					}
					break;

				// Any other template type
				default:
					if ( isset( $_POST['content'] ) ) {
						// @since 1.5.4
						$content = Helpers::security_check_elements_before_save( $content, $post_id, 'content' );

						if ( is_array( $content ) && count( $content ) ) {
							// Save revision in post meta ('update_post_meta' can't process post type 'revision')
							if ( $revision_id ) {
								update_metadata( 'post', $revision_id, BRICKS_DB_PAGE_CONTENT, $content );
							}

							update_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT, $content );
						} else {
							delete_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT );
						}
					}
			}
		}

		/**
		 * Template settings
		 *
		 * @since 1.4
		 */
		if ( isset( $_POST['templateSettings'] ) && $has_full_access ) {
			$template_settings = self::decode( $_POST['templateSettings'], false );

			if ( is_array( $template_settings ) && count( $template_settings ) ) {
				// User saved template settings: Delete auto content notification
				unset( $template_settings['templatePreviewAutoContent'] );

				Helpers::set_template_settings( $post_id, $template_settings );
			} else {
				Helpers::delete_template_settings( $post_id );
			}
		}

		/**
		 * Content (not a Bricks template)
		 *
		 * @since 1.4
		 */
		if ( isset( $_POST['content'] ) && get_post_type( $post_id ) !== BRICKS_DB_TEMPLATE_SLUG ) {
			// @since 1.5.4
			$content = Helpers::security_check_elements_before_save( $content, $post_id, 'content' );

			if ( is_array( $content ) && count( $content ) ) {
				// Update empty or existing Gutenberg post_content (preserve Classic Editor data)
				$existing_post_content = $post->post_content;

				if ( Database::get_setting( 'bricks_to_wp' ) && ( ! $existing_post_content || has_blocks( get_post( $post_id ) ) ) ) {
					$new_post_content = Blocks::serialize_bricks_to_blocks( $content, $post_id );

					if ( $new_post_content ) {
						$the_post = (
							[
								'ID'           => $post_id,
								'post_content' => $new_post_content,
							]
						);
					}
				}

				// Save revision in post meta ('update_post_meta' can't process post type 'revision')
				if ( $revision_id ) {
					update_metadata( 'post', $revision_id, BRICKS_DB_PAGE_CONTENT, $content );
				}

				// Save content in post meta
				update_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT, $content );
			} else {
				delete_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT );
			}
		}

		// Set _bricks_editor_mode to 'bricks'
		update_post_meta( $post_id, BRICKS_DB_EDITOR_MODE, 'bricks' );

		/**
		 * STEP: Update post to (1) update post date & (2) re-generate CSS file via 'save_post' in files.php
		 *
		 * Check $wp_post_updated to ensure wp_update_post did not already ran above.
		 *
		 * @since 1.5.7
		 */
		if ( $bricks_data_changed ) {
			$post_id = $the_post ? wp_update_post( $the_post ) : wp_update_post( $post );
		}

		wp_send_json_success( $_POST );
	}

	/**
	 * Sanitize Bricks postmeta
	 */
	public function sanitize_bricks_postmeta( $meta_value, $meta_key, $object_type ) {
		$post_id = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : get_the_ID();

		// Return: No post ID set
		if ( ! $post_id ) {
			return $meta_value;
		}

		// Return: Is in-builder
		if ( check_ajax_referer( 'bricks-nonce-builder', 'nonce', false ) ) {
			return $meta_value;
		}

		if ( $meta_key === BRICKS_DB_PAGE_CONTENT ) {
			$meta_value = Helpers::security_check_elements_before_save( $meta_value, $post_id, 'content' );
		} elseif ( $meta_key === BRICKS_DB_PAGE_HEADER ) {
			$meta_value = Helpers::security_check_elements_before_save( $meta_value, $post_id, 'header' );
		} elseif ( $meta_key === BRICKS_DB_PAGE_FOOTER ) {
			$meta_value = Helpers::security_check_elements_before_save( $meta_value, $post_id, 'footer' );
		}

		return $meta_value;
	}

	/**
	 * Update postmeta: Prevent user without builder access from updating Bricks postmeta
	 */
	public function update_bricks_postmeta( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		// Return: Not Bricks postmeta
		$is_bricks_postmeta = in_array( $meta_key, [ BRICKS_DB_PAGE_CONTENT, BRICKS_DB_PAGE_HEADER, BRICKS_DB_PAGE_FOOTER ], true );
		if ( $is_bricks_postmeta && ! Capabilities::current_user_can_use_builder( $object_id ) ) {
			return false;
		}

		return $check;
	}

	/**
	 * Create autosave
	 *
	 * @since 1.0
	 */
	public static function create_autosave() {
		self::verify_request( 'bricks-nonce-builder' );

		$area     = ! empty( $_POST['area'] ) ? sanitize_text_field( $_POST['area'] ) : false;
		$post_id  = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;
		$elements = ! empty( $_POST['elements'] ) ? self::decode( $_POST['elements'] ) : false;

		if ( ! $area || ! $post_id || ! $elements ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		// 1/2: Create autosave
		$autosave_id = wp_create_post_autosave(
			[
				'post_ID'       => $post_id,
				'post_type'     => $post_type,
				'post_excerpt'  => '<!-- Built With Bricks -->', // Forces $autosave_is_different to 'true'
				'post_modified' => current_time( 'mysql' ),
			]
		);

		if ( is_wp_error( $autosave_id ) ) {
			wp_send_json_error( new \WP_Error( 'autosave_error', $autosave_id ) );
		}

		// 2/2: Save elements in db post meta with autosave post ID
		$elements = self::decode( $_POST['elements'] );

		$elements = Helpers::security_check_elements_before_save( $elements, $post_id, 'content' );

		if ( ! is_array( $elements ) ) {
			wp_send_json_error( new \WP_Error( 'element_error', 'No elements' ) );
		}

		switch ( $area ) {
			case 'header':
				update_metadata( 'post', $autosave_id, BRICKS_DB_PAGE_HEADER, $elements );
				break;

			case 'content':
				update_metadata( 'post', $autosave_id, BRICKS_DB_PAGE_CONTENT, $elements );
				break;

			case 'footer':
				update_metadata( 'post', $autosave_id, BRICKS_DB_PAGE_FOOTER, $elements );
				break;
		}

		// STEP: Generate post CSS file on autosave
		if ( Database::get_setting( 'cssLoading' ) === 'file' ) {
			Assets_Files::generate_post_css_file( $post_id, $area, $elements );
		}

		wp_send_json_success( [ 'autosave_id' => $autosave_id ] );
	}

	/**
	 * Get bulider URL
	 *
	 * To reload builder with newly saved postName/postTitle (page settigns)
	 *
	 * @since 1.0
	 */
	public function get_builder_url() {
		self::verify_request( 'bricks-nonce-builder' );

		wp_send_json_success( [ 'url' => Helpers::get_builder_edit_link( ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0 ) ] );
	}

	/**
	 * Publish post
	 *
	 * @since 1.0
	 */
	public function publish_post() {
		self::verify_request( 'bricks-nonce-builder' );

		// Return: Current user can not publish posts
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( 'Error: You do not have permission to publish posts' );
		}

		$post_id = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( 'Error: No postId provided.' );
		}

		$response = wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'publish',
			]
		);

		wp_send_json_success( $response );
	}

	/**
	 * Get image metadata
	 *
	 * @since 1.0
	 */
	public function get_image_metadata() {
		self::verify_request( 'bricks-nonce-builder' );

		$image_id   = ! empty( $_POST['imageId'] ) ? intval( $_POST['imageId'] ) : 0;
		$image_size = ! empty( $_POST['imageSize'] ) ? sanitize_text_field( $_POST['imageSize'] ) : '';

		if ( ! $image_id ) {
			wp_send_json_error( 'Error: No imageId provided.' );
		}

		$get_attachment_metadata = wp_get_attachment_metadata( $image_id );

		// SVG returns empty metadata
		if ( ! $get_attachment_metadata ) {
			wp_send_json_success();
		}

		$response = [
			'filename' => isset( $get_attachment_metadata['original_image'] ) ? $get_attachment_metadata['original_image'] : '',
			'full'     => [
				'width'  => isset( $get_attachment_metadata['width'] ) ? $get_attachment_metadata['width'] : '',
				'height' => isset( $get_attachment_metadata['height'] ) ? $get_attachment_metadata['height'] : '',
			],
			'sizes'    => isset( $get_attachment_metadata['sizes'] ) ? $get_attachment_metadata['sizes'] : [],
			'src'      => wp_get_attachment_image_src( $image_id, $image_size ),
		];

		wp_send_json_success( $response );
	}

	/**
	 * Get Image Id from a custom field
	 *
	 * @since 1.0
	 */
	public function get_image_from_custom_field() {
		self::verify_request( 'bricks-nonce-builder' );

		$image_id = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;
		$meta_key = ! empty( $_POST['metaKey'] ) ? sanitize_text_field( $_POST['metaKey'] ) : '';
		$size     = ! empty( $_POST['size'] ) ? sanitize_text_field( $_POST['size'] ) : BRICKS_DEFAULT_IMAGE_SIZE;

		if ( ! $image_id ) {
			wp_send_json_error( 'Error: No postId provided' );
		}

		if ( ! $meta_key ) {
			wp_send_json_error( 'Error: No postmeta key provided' );
		}

		// Get images from custom field
		$images = Integrations\Dynamic_Data\Providers::render_tag( $meta_key, $image_id, 'image', [ 'size' => $size ] );

		if ( empty( $images ) ) {
			wp_send_json_error( 'Error: Image not found' );
		}

		if ( is_numeric( $images[0] ) ) {
			$get_attachment_metadata = wp_get_attachment_metadata( $images[0] );

			if ( empty( $get_attachment_metadata ) ) {
				wp_send_json_error( 'Error: Image not found' );
			}

			$output = [
				'filename' => isset( $get_attachment_metadata['original_image'] ) ? $get_attachment_metadata['original_image'] : '',
				'id'       => $images[0],
				'size'     => $size,
				'url'      => wp_get_attachment_image_url( $images[0], $size ),
			];
		}

		// Might be a Gravatar image
		else {
			$output = [
				'url' => $images[0]
			];
		}

		wp_send_json_success( $output );
	}

	/**
	 * Download image to WordPress media libary (Unsplash)
	 *
	 * @since 1.0
	 */
	public function download_image() {
		self::verify_request( 'bricks-nonce-builder' );

		// http://www.codingduniya.com/2016/07/generate-featured-image-for-post-using.html
		$file_array = [];

		$tmp = download_url( ! empty( $_POST['download_url'] ) ? esc_url( $_POST['download_url'] ) : '' );

		$file_array['tmp_name'] = $tmp;

		// Manually add file extension as Unsplash download URL doesn't provide file extension
		$file_array['name'] = ! empty( $_POST['file_name'] ) ? $_POST['file_name'] . '.jpg' : '';

		// Check for download errors
		if ( is_wp_error( $tmp ) ) {
			wp_send_json_error( $tmp );
		}

		$id = media_handle_sideload( $file_array, 0 );

		// If error storing permanently, unlink
		if ( is_wp_error( $id ) ) {
			if ( isset( $file_array['tmp_name'] ) ) {
				@unlink( $file_array['tmp_name'] );
			}
		}

		wp_send_json_success( $id );
	}

	/**
	 * Parse content through dynamic data logic
	 *
	 * @since 1.5.1
	 */
	public function get_dynamic_data_preview_content() {
		self::verify_request( 'bricks-nonce-builder' );

		$post_id = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : 0;
		$content = ! empty( $_POST['content'] ) ? sanitize_text_field( $_POST['content'] ) : '';
		$context = ! empty( $_POST['context'] ) ? sanitize_text_field( $_POST['context'] ) : 'text';

		if ( ! $post_id ) {
			wp_send_json_error( 'Error: No post ID' );
		}

		if ( ! $content ) {
			wp_send_json_error( 'Error: No content' );
		}

		// Use stripslashes to unescape img URLs, etc. (@since 1.7)
		if ( is_string( $content ) ) {
			$content = stripslashes( $content );
		}

		// STEP: Set up post data so WP core function like get_the_ID() work inside custom PHP functions called via DD 'echo:'
		global $post;

		$post = get_post( $post_id );

		setup_postdata( $post );

		// Get content from custom field
		if ( is_array( $content ) ) {
			// Array format used to parse colors in the builder (@since 1.5.1)
			foreach ( $content as $key => $data ) {
				$content[ $key ]['value'] = bricks_render_dynamic_data( $data['value'], $post_id, $context );
			}
		} else {
			// Preview composed links e.g. "https://my-domain.com/?user={wp_user_id}" (@since 1.5.4)
			if ( $context == 'link' && ( strpos( $content, '{' ) !== 0 || substr_count( $content, '}' ) > 1 ) ) {
				$context = 'text';
			}

			$content = bricks_render_dynamic_data( $content, $post_id, $context );
		}

		wp_reset_postdata();

		if ( 'link' === $context ) {
			$content = esc_url( $content );
		}

		// When output a code field, extract the content
		elseif ( is_string( $content ) && strpos( $content, '<pre' ) === 0 ) {
			preg_match( '#<\s*?code\b[^>]*>(.*?)</code\b[^>]*>#s', $content, $matches );
			$content = isset( $matches[1] ) ? $matches[1] : $content;

			// esc_html to escape code tags
			$content = esc_html( $content );
		}

		/**
		 * Run additional checks for non-basic text elements like removing extra <p> tags, etc.
		 *
		 * ContentEditable.js provides the element name.
		 *
		 * @since 1.7
		 */
		$element_name = ! empty( $_POST['elementName'] ) ? sanitize_text_field( $_POST['elementName'] ) : false;

		if ( $element_name && $element_name !== 'text-basic' ) {
			$content = Helpers::parse_editor_content( $content );
		}

		// NOTE: We are not escaping text content since it could contain formatting tags like <strong> (@since 1.5.1 - preview dynamic data)
		wp_send_json_success( [ 'content' => $content ] );
	}

	/**
	 * Get latest remote templates data in builder (PopupTemplates.vue)
	 *
	 * @since 1.0
	 */
	public function get_remote_templates_data() {
		self::verify_request( 'bricks-nonce-builder' );

		$remote_templates = Templates::get_remote_templates_data();

		wp_send_json_success( $remote_templates );
	}

	/**
	 * Builder: Get "My templates" from db
	 *
	 * @since 1.4
	 */
	public function get_my_templates_data() {
		self::verify_request( 'bricks-nonce-builder' );

		wp_send_json_success(
			Templates::get_templates(
				[
					'post_status'           => 'any',
					'lang'                  => '', // Get all templates in builder for Polylang (@since 1.9.5)
					'remove_code_signature' => true, // Don't remove signature from local templates (@since 1.9.7)
				]
			)
		);
	}

	/**
	 * Get current user
	 *
	 * Verify logged-in user when builder is loaded on the frontend.
	 *
	 * @since 1.5
	 */
	public function get_current_user_id() {
		self::verify_request( 'bricks-nonce-builder' );

		wp_send_json_success( [ 'user_id' => get_current_user_id() ] );
	}


	/**
	 * Delete bricks query loop random seed transient
	 *
	 * @since 1.7.1
	 */
	public function query_loop_delete_random_seed_transient() {
		self::verify_request( 'bricks-nonce-builder' );

		$element_id = ! empty( $_POST['elementId'] ) ? sanitize_text_field( $_POST['elementId'] ) : false;

		if ( ! $element_id ) {
			wp_send_json_error( 'Error: No element ID' );
		}

		// @see Bricks\Query->set_bricks_query_loop_random_order_seed()
		$transient_name = "bricks_query_loop_random_seed_$element_id";

		delete_transient( $transient_name );

		wp_send_json_success();
	}

	/**
	 * Get custom shape divider (SVG) from attachment ID
	 *
	 * Only allow to select SVG files from the media library for security reasons.
	 *
	 * @since 1.8.6
	 */
	public function get_custom_shape_divider() {
		self::verify_request( 'bricks-nonce-builder' );

		$svg_path = ! empty( $_POST['id'] ) ? get_attached_file( intval( $_POST['id'] ) ) : false;
		$svg      = $svg_path ? Helpers::file_get_contents( $svg_path ) : false;

		wp_send_json_success( $svg );
	}
}
