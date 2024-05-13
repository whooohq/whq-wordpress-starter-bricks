<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Templates {
	public static $template_images      = [];
	public static $template_element_ids = [];

	// All template IDs used on requested URL (@since 1.8.1)
	public static $rendered_template_ids_on_page = [];

	// All generated inline CSS identifiers (@since 1.9.1)
	public static $generated_inline_identifier = [];

	public function __construct() {
		add_filter( 'init', [ $this, 'register_post_type' ] );

		// Run on 'wp' and priority 20 to ensure set_active_templates && set_page_data in database.php ran first (@since 1.9.2)
		add_action( 'wp', [ $this, 'assign_templates_to_hooks' ], 20 );

		add_shortcode( 'bricks_template', [ $this, 'render_shortcode' ] );

		// Builder
		add_action( 'wp_ajax_bricks_create_template', [ $this, 'create_template' ] );
		add_action( 'wp_ajax_bricks_save_template', [ $this, 'save_template' ] );
		add_action( 'wp_ajax_bricks_delete_template', [ $this, 'delete_template' ] );

		// Admin & builder
		add_action( 'wp_ajax_bricks_import_template', [ $this, 'import_template' ] );
		add_action( 'wp_ajax_bricks_export_template', [ $this, 'export_template' ] );

		add_action( 'wp_ajax_bricks_convert_template', [ $this, 'convert_template' ] );

		add_action( 'save_post', [ $this, 'flush_templates_cache' ] );

		add_filter( 'wp_sitemaps_post_types', [ $this, 'remove_templates_from_wp_sitemap' ] );

		add_filter( 'wp_sitemaps_taxonomies', [ $this, 'remove_template_taxonomies_from_wp_sitemap' ] );
	}

	/**
	 * Register custom post types
	 *
	 * post_type: bricks_template
	 * taxonomies: template_tag, template_bundle
	 *
	 * @since 1.0
	 */
	public function register_post_type() {
		// Register post type: bricks_template
		register_post_type(
			BRICKS_DB_TEMPLATE_SLUG,
			[
				'labels'              => [
					'name'               => esc_html__( 'My Templates', 'bricks' ),
					'singular_name'      => esc_html__( 'Template', 'bricks' ),
					'add_new'            => esc_html__( 'Add New', 'bricks' ),
					'add_new_item'       => esc_html__( 'Add New Template', 'bricks' ),
					'edit_item'          => esc_html__( 'Edit Template', 'bricks' ),
					'new_item'           => esc_html__( 'New Template', 'bricks' ),
					'view_item'          => esc_html__( 'View Template', 'bricks' ),
					'view_items'         => esc_html__( 'View Templates', 'bricks' ),
					'search_items'       => esc_html__( 'Search Templates', 'bricks' ),
					'not_found'          => esc_html__( 'No Templates found', 'bricks' ),
					'not_found_in_trash' => esc_html__( 'No Template found in Trash', 'bricks' ),
					'all_items'          => esc_html__( 'All Templates', 'bricks' ),
					'menu_name'          => esc_html__( 'My Templates', 'bricks' ),
				],
				'public'              => true,
				'rewrite'             => [ 'slug' => 'template' ],
				/**
				 * Exclude Bricks templates from search resuls on the frontend if Bricks setting "Public Templates" is not enabled
				 *
				 * @since 1.9.3
				 */
				'exclude_from_search' => ! bricks_is_builder() && ! Database::get_setting( 'publicTemplates', false ),
				'hierarchical'        => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => true,
				'capability_type'     => 'post',
				'supports'            => [
					'author',
					'revisions',
					'thumbnail',
					'title',
				],
				'taxonomies'          => [ BRICKS_DB_TEMPLATE_TAX_TAG ],
			]
		);

		// Register template taxomony: 'template_tag'
		register_taxonomy(
			BRICKS_DB_TEMPLATE_TAX_TAG,
			BRICKS_DB_TEMPLATE_SLUG,
			[
				'labels' => [
					'name'          => esc_html__( 'Template Tags', 'bricks' ),
					'singular_name' => esc_html__( 'Template Tag', 'bricks' ),
					'all_items'     => esc_html__( 'All Template Tags', 'bricks' ),
					'edit_item'     => esc_html__( 'Edit Template Tag', 'bricks' ),
					'view_item'     => esc_html__( 'View Template Tag', 'bricks' ),
					'update_item'   => esc_html__( 'Update Template Tag', 'bricks' ),
					'add_new_item'  => esc_html__( 'Add New Template Tag', 'bricks' ),
					'new_item_name' => esc_html__( 'New Template Name', 'bricks' ),
					'search_items'  => esc_html__( 'Search Template Tags', 'bricks' ),
					'not_found'     => esc_html__( 'No Template Tag found', 'bricks' ),
					'name'          => esc_html__( 'Template Tag', 'bricks' ),
				],
			]
		);

		// Register template taxomony: 'template_bundle'
		register_taxonomy(
			BRICKS_DB_TEMPLATE_TAX_BUNDLE,
			BRICKS_DB_TEMPLATE_SLUG,
			[
				'labels' => [
					'name'          => esc_html__( 'Template Bundles', 'bricks' ),
					'singular_name' => esc_html__( 'Template Bundle', 'bricks' ),
					'all_items'     => esc_html__( 'All Template Bundles', 'bricks' ),
					'edit_item'     => esc_html__( 'Edit Template Bundle', 'bricks' ),
					'view_item'     => esc_html__( 'View Template Bundle', 'bricks' ),
					'update_item'   => esc_html__( 'Update Template Bundle', 'bricks' ),
					'add_new_item'  => esc_html__( 'Add New Template Bundle', 'bricks' ),
					'new_item_name' => esc_html__( 'New Template Name', 'bricks' ),
					'search_items'  => esc_html__( 'Search Template Bundles', 'bricks' ),
					'not_found'     => esc_html__( 'No Template Bundle found', 'bricks' ),
					'name'          => esc_html__( 'Template Bundle', 'bricks' ),
				],
			]
		);
	}

	/**
	 * Render shortcode: [bricks_template]
	 */
	public function render_shortcode( $attributes = [] ) {
		$template_id = ! empty( $attributes['id'] ) ? intval( $attributes['id'] ) : false;
		// @since 1.9.1 - To indicate that the shortcode is rendered on the hook, might need to add inline styles later
		$is_on_hook = ! empty( $attributes['on_hook'] ) ? true : false;

		if ( ! $template_id ) {
			return;
		}

		$original_post_id = ! empty( Database::$page_data['original_post_id'] ) ? Database::$page_data['original_post_id'] : '';

		// post_id at this stage could be template preview post ID (populated content)
		$post_id = get_the_ID();

		// Avoid loops: Shortcode rendering inside of itself
		// Ensure $original_post_id is a bricks template when use for comparison, it might be a term ID (#862k7jcn7)
		if ( $template_id == $post_id || ( Helpers::is_bricks_template( $original_post_id ) && $template_id == $original_post_id ) ) {
			return Helpers::get_element_placeholder(
				[
					'title' => esc_html__( 'Not allowed: Infinite template loop.', 'bricks' ),
				]
			);
		}

		$elements = get_post_meta( $template_id, BRICKS_DB_PAGE_CONTENT, true );

		if ( empty( $elements ) || ! is_array( $elements ) ) {
			return Helpers::get_element_placeholder(
				[
					'title' => esc_html__( 'Your selected template is empty.', 'bricks' ),
				]
			);
		}

		$html = '';

		/**
		 * STEP: Generate template CSS (builder, inline styles or external files)
		 *
		 * Non-loop templates only as in-loop CSS is generated in assets.php line 2535
		 */

		// Collect all rendered template IDs for requested URL (@since 1.8.1)
		if ( ! in_array( $template_id, self::$rendered_template_ids_on_page ) ) {
			self::$rendered_template_ids_on_page[] = $template_id;
		}

		// Check for icon fonts and global elements
		Assets::enqueue_setting_specific_scripts( $elements );

		$template_inline_css = self::generate_inline_css( $template_id, $elements );

		// STEP: Builder (append template CSS as inline <style> to element HTML)
		if ( bricks_is_builder() || bricks_is_builder_call() ) {
			// Use 'data-template-id' to get template ID in builder to generate global classes CSS of Template element (@since 1.8.2)
			$template_inline_css .= Assets::$inline_css_dynamic_data;
			$html                .= "<style data-template-id=\"{$template_id}\" id=\"bricks-inline-css-template-{$template_id}\">{$template_inline_css}</style>";
		}

		// STEP: CSS loading method: External files
		elseif ( Database::get_setting( 'cssLoading' ) === 'file' ) {
			$template_css_file_dir = Assets::$css_dir . "/post-$template_id.min.css";
			$template_css_file_url = Assets::$css_url . "/post-$template_id.min.css";

			if ( file_exists( $template_css_file_dir ) ) {
				wp_enqueue_style( "bricks-post-$template_id", $template_css_file_url, [], filemtime( $template_css_file_dir ) );
			}

			// When assign section template to hook, some ID level styles are missing when using external files and is looping (@since 1.9.1)
			if ( $is_on_hook && Query::is_any_looping() ) {
				Assets::$inline_css_dynamic_data .= $template_inline_css;
			}
		}

		// STEP: CSS loading method: Inline styles (default)
		else {
			// Get dynamic data styles to add as inline CSS on the frontend (@since 1.8.2)
			Assets::$inline_css_dynamic_data .= $template_inline_css;
		}

		// STEP: Avoid infinite template loops
		static $rendered_shortcode_template_ids = [];

		if ( ! in_array( $template_id, $rendered_shortcode_template_ids ) ) {
			// Add template ID to avoid infinite loops (reset below after template has been rendered)
			$rendered_shortcode_template_ids[] = $template_id;

			// Store the current main render_data self::$elements
			$store_elements = Frontend::$elements;

			$html .= Frontend::render_data( $elements );

			// Reset the main render_data self::$elements
			Frontend::$elements = $store_elements;

			// Reset template ID by removing last template ID from the array
			array_pop( $rendered_shortcode_template_ids );
		}

		/**
		 * Build looping popup HTML (render in footer)
		 *
		 * @since 1.7.1
		 */
		if ( self::get_template_type( $template_id ) === 'popup' ) {
			Popups::build_looping_popup_html( $template_id );

			return;
		}

		return $html;
	}

	/**
	 * Generate the inline CSS for template rendered in shortcode element
	 */
	public static function generate_inline_css( $template_id, $elements ) {
		if ( empty( $template_id ) ) {
			return;
		}

		// Return: Template has not been published (@since 1.7.1)
		if ( $template_id && get_post_status( $template_id ) !== 'publish' ) {
			return;
		}

		$inline_css = '';

		Assets::generate_css_from_elements( $elements, "template_$template_id" );

		// Check as template_{id} is not set when using inline CSS loading method (see template.php line 77)
		$template_inline_css = Assets::$inline_css[ "template_$template_id" ] ?? '';

		if ( $template_inline_css ) {

			$looping_query_id = Query::is_any_looping();

			if ( $looping_query_id ) {
				$unique_loop_id = [
					$template_id,
					Query::get_query_element_id( $looping_query_id ),
					Query::get_loop_object_type( $looping_query_id ),
				];
			}

			// Unique identifier for inline template inside query loop (@since 1.9.1)
			$generated_inline_identifier = $looping_query_id ? implode( ':', $unique_loop_id ) : $template_id;

			/**
			 * Add template inline CSS, if:
			 * 1. Non-loop template that has not been added already
			 * 2. Is in-loop template index 0
			 * 2b. Cannot use index 0 as if we are in second page and using assign section hook, the styles not generated. Use $generated_inline_identifier as workaround - @since 1.9.1
			 *
			 * @since 1.8.2
			 */
			if (
				( ! Query::is_looping() && ! in_array( $template_id, Assets::$page_settings_post_ids ) ) ||
				( $looping_query_id && ! in_array( $generated_inline_identifier, self::$generated_inline_identifier ) )
			) {
				$inline_css .= "\n/* TEMPLATE SHORTCODE CSS (ID: {$template_id}) */\n";
				$inline_css .= $template_inline_css;
				// Add generated inline identifier to avoid duplicate inline CSS (@since 1.9.1)
				self::$generated_inline_identifier[] = $generated_inline_identifier;
			}
		}

		// Add page settings of this template
		if ( ! in_array( $template_id, Assets::$page_settings_post_ids ) ) {
			Assets::$page_settings_post_ids[] = $template_id;
		}

		/**
		 * Builder: Generate global classes & page settings CSS of Template element
		 *
		 * Frontend: Global classes in template added in wp_footer via enqueue_footer_inline_css
		 *
		 * @since 1.8.2
		 */
		$global_classes_css = Assets::generate_global_classes();

		if ( bricks_is_builder_call() ) {
			$page_css = Assets::generate_inline_css_page_settings();

			if ( $page_css ) {
				$inline_css .= "\n/* PAGE CSS */\n" . $page_css;
			}
		}

		// Webfonts
		Assets::load_webfonts( $inline_css );

		return $inline_css;
	}

	/**
	 * Keep the timestamp of the latest change in the templates post type to force the cache flush
	 *
	 * @param int $post_id Post ID.
	 */
	public function flush_templates_cache( $post_id ) {
		if ( get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
			wp_cache_set( 'last_changed', microtime(), 'bricks_' . BRICKS_DB_TEMPLATE_SLUG );
		}
	}

	/**
	 * Check if remote site can get templates
	 *
	 * @see Api::get_templates()
	 * @return array Array with 'error' key on error. Array with 'site', 'password', 'licenseKey' on success.
	 *
	 * @since 1.0
	 */
	public static function can_get_templates( $parameters ) {
		// STEP: Admin setting 'myTemplatesAccess' blocked
		if ( ! Database::get_setting( 'myTemplatesAccess' ) ) {
			return [
				'error' => [
					'code'    => 'my_templates_access_disabled',
					'message' => esc_html__( 'The site you are requesting templates from has access to their templates disabled.', 'bricks' ),
				],
			];
		}

		$site_url = ! empty( $parameters['site'] ) ? esc_url( $parameters['site'] ) : false;

		// STEP: Check 'site' provided (mandatory)
		if ( ! $site_url ) {
			return [
				'error' => [
					'code'    => 'no_site_url',
					'message' => esc_html__( 'Sorry, but no site URL has been provided.', 'bricks' ),
				],
			];
		}

		// STEP: Admin setting 'myTemplatesWhitelist' lists requesting 'site'
		$my_templates_whitelist_urls = Database::get_setting( 'myTemplatesWhitelist', [] );

		if ( $my_templates_whitelist_urls ) {
			$my_templates_whitelist_urls = array_map( 'trim', explode( "\n", $my_templates_whitelist_urls ) );

			$my_templates_whitelist_urls = array_map( 'trailingslashit', $my_templates_whitelist_urls );

			$site_url = trailingslashit( $site_url );

			if ( ! in_array( $site_url, $my_templates_whitelist_urls ) ) {
				return [
					'error' => [
						'code'    => 'not_whitelisted',
						// translators: %1$s: site URL, %2$s: current site URL
						'message' => sprintf( esc_html__( 'Your website (%1$s) has no permission to access templates from %2$s', 'bricks' ), $site_url, get_site_url() ),
					],
				];
			}
		}

		// STEP: Admin setting 'myTemplatesPassword'
		$my_templates_password = Database::get_setting( 'myTemplatesPassword' );
		$password              = isset( $parameters['password'] ) ? sanitize_text_field( $parameters['password'] ) : false;

		if ( $my_templates_password ) {
			if ( ! $password ) {
				return [
					'error' => [
						'code'    => 'remote_templates_password_required',
						'message' => esc_html__( 'The site you are requesting templates from requires a remote templates password.', 'bricks' ),
					],
				];
			}

			if ( $password !== $my_templates_password ) {
				return [
					'error' => [
						'code'    => 'remote_templates_password_incorrect',
						'message' => esc_html__( 'Your remote templates password is incorrect.', 'bricks' ),
					],
				];
			}
		}

		// STEP: ALl checks pass

		// Pass 'site' for 'bricks/get_templates' filter check
		$templates_args = [ 'site' => $site_url ];

		// Pass license key if provided
		if ( isset( $parameters['licenseKey'] ) ) {
			$templates_args['licenseKey'] = sanitize_text_field( $parameters['licenseKey'] );
		}

		// Pass templates password if provided
		if ( isset( $password ) ) {
			$templates_args['password'] = $password;
		}

		// Success: Return template_args
		return $templates_args;
	}

	/**
	 * Create template
	 *
	 * @since 1.0
	 */
	public static function get_remote_template_settings() {
		$all_remote_templates = [];

		// Get community templates
		$all_remote_templates[] = [ 'url' => BRICKS_REMOTE_URL ];

		// Get single remote template (Bricks > Settings > Templates) @pre 1.9.4
		$single_remote_template_url      = Database::get_setting( 'remoteTemplatesUrl' );
		$single_remote_template_password = Database::get_setting( 'remoteTemplatesPassword' );

		if ( $single_remote_template_url ) {
			$all_remote_templates[] = [
				'url'      => $single_remote_template_url,
				'password' => $single_remote_template_password,
			];
		}

		// Get remote templates (Bricks > Settings > Templates) @since 1.9.4
		$remote_templates = Database::get_setting( 'remoteTemplates' ) ?? false;

		if ( is_array( $remote_templates ) ) {
			// Append remote templates to all remote templates
			$all_remote_templates = array_merge( $all_remote_templates, $remote_templates );
		}

		return $all_remote_templates;
	}

	/**
	 * Builder templates: Get all remote templates data (templates, authors, bundles, tags)
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public static function get_remote_templates_data() {
		$source                   = $_POST['source'] ?? '';
		$remote_template_settings = self::get_remote_template_settings();
		$remote_template_url      = '';
		$remote_template_password = '';

		// Get remote template 'url' and 'password'
		foreach ( $remote_template_settings as $template_settings ) {
			if ( isset( $template_settings['url'] ) && $template_settings['url'] === $source ) {
				$remote_template_url      = $template_settings['url'];
				$remote_template_password = $template_settings['password'] ?? '';
			}
		}

		$request_url = Api::get_endpoint( 'get-templates-data', $source );
		$request_url = add_query_arg( [ 'site' => get_site_url() ], $request_url );

		if ( $remote_template_password ) {
			$request_url = add_query_arg( [ 'password' => urlencode( $remote_template_password ) ], $request_url );
		}

		// Community templates: Send license key
		// TODO NOTE: Currently not being checked on Bricks community templates site
		if ( $source == BRICKS_REMOTE_URL ) {
			$request_url = add_query_arg( [ 'licenseKey' => License::$license_key ], $request_url );
		}

		$request_url = add_query_arg( [ 'time' => time() ], $request_url );

		if ( strpos( $request_url, 'bricksbuilder.io/wp-json' ) !== false ) {
			$request_url = str_replace( 'bricksbuilder.io/wp-json', 'bricksbuilder.io/api/', $request_url );
		}

		$response = Helpers::remote_get( $request_url );

		// Return error to show in builder templates manager
		if ( is_wp_error( $response ) ) {
			return [
				'error'       => $response->get_error_message(),
				'request_url' => $request_url,
			];
		}

		$remote_templates = json_decode( wp_remote_retrieve_body( $response ), true );
		$remote_templates = apply_filters( 'bricks/get_remote_templates_data', $remote_templates );

		if ( ! empty( $remote_templates['error']['message'] ) ) {
			return [
				'error'       => $remote_templates['error']['message'],
				'request_url' => $request_url,
			];
		}

		return $remote_templates;
	}

	/**
	 * Get templates query based on custom args
	 *
	 * @since 1.0
	 *
	 * @param array $custom_args
	 * @return WP_Query
	 */
	public static function get_templates_query( $custom_args = [] ) {
		$last_changed = wp_cache_get_last_changed( 'bricks_' . BRICKS_DB_TEMPLATE_SLUG );

		$cache_key = md5( 'get_templates_query_' . $last_changed . wp_json_encode( $custom_args ) );

		$query = wp_cache_get( $cache_key, 'bricks' );

		if ( $query === false ) {
			$default_args = [
				'post_type'      => BRICKS_DB_TEMPLATE_SLUG,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			];

			$merged_args = wp_parse_args( $custom_args, $default_args );

			$query = new \WP_Query( $merged_args );

			wp_cache_set( $cache_key, $query, 'bricks', DAY_IN_SECONDS );
		}

		return $query;
	}

	/**
	 * Get all the template IDs of a specific type
	 */
	public static function get_templates_by_type( $template_type = '' ) {
		$query_args = [
			'meta_query' => [
				[
					'key'   => BRICKS_DB_TEMPLATE_TYPE,
					'value' => $template_type,
				],
			],
			'fields'     => 'ids',
		];

		$query = self::get_templates_query( $query_args );

		return ! empty( $query->found_posts ) ? $query->posts : [];
	}

	/**
	 * Get my templates
	 *
	 * @since 1.0
	 */
	public static function get_templates( $custom_args = [] ) {
		$templates_query = self::get_templates_query( $custom_args );

		$templates = [];

		if ( $templates_query->have_posts() ) {
			foreach ( $templates_query->get_posts() as $template ) {
				// Template bundles
				$template_bundles = wp_get_object_terms( $template->ID, BRICKS_DB_TEMPLATE_TAX_BUNDLE, [ 'fields' => 'slugs' ] );
				$bundles          = [];

				if ( $template_bundles ) {
					foreach ( $template_bundles as $bundle ) {
						$bundles[] = $bundle;
					}
				}

				// Template tags
				$template_tags = wp_get_object_terms( $template->ID, BRICKS_DB_TEMPLATE_TAX_TAG, [ 'fields' => 'slugs' ] );
				$tags          = [];

				if ( $template_tags ) {
					foreach ( $template_tags as $tag ) {
						$tags[] = $tag;
					}
				}

				$author_name = get_the_author_meta( 'display_name', $template->post_author );

				// Check if my template thumbnail exists locally in WP root 'template-screenshots' folder
				$template_thumbnail_path = ABSPATH . trailingslashit( 'template-screenshots' ) . $template->post_name . '.jpg';

				if ( file_exists( $template_thumbnail_path ) ) {
					$template_thumbnail = get_site_url( null, '/' ) . trailingslashit( 'template-screenshots' ) . $template->post_name . '.jpg';
				}

				// Fallback: Check template featured image
				else {
					$template_thumbnail = has_post_thumbnail( $template->ID ) ? get_the_post_thumbnail_url( $template->ID, 'bricks_medium' ) : false;
				}

				$template_data = [
					'id'             => $template->ID,
					'name'           => $template->post_name,
					'title'          => $template->post_title,
					'date'           => $template->post_date,
					'date_formatted' => date( get_option( 'date_format' ), strtotime( $template->post_date ) ),
					'author'         => [
						'name'   => $author_name,
						'avatar' => get_avatar_url( $template->post_author, [ 'size' => 60 ] ),
						'url'    => get_the_author_meta( 'user_url', $template->post_author ),
					],
					'permalink'      => get_permalink( $template->ID ),
					'thumbnail'      => $template_thumbnail,
					'bundles'        => $bundles,
					'tags'           => $tags,
					'type'           => self::get_template_type( $template->ID ),
				];

				$template_elements = [];
				$area              = false;

				if ( is_array( get_post_meta( $template->ID, BRICKS_DB_PAGE_HEADER, true ) ) ) {
					$template_elements       = get_post_meta( $template->ID, BRICKS_DB_PAGE_HEADER, true );
					$template_data['header'] = $template_elements;
					$area                    = 'header';
				}

				if ( is_array( get_post_meta( $template->ID, BRICKS_DB_PAGE_CONTENT, true ) ) ) {
					$template_elements        = get_post_meta( $template->ID, BRICKS_DB_PAGE_CONTENT, true );
					$template_data['content'] = $template_elements;
					$area                     = 'content';
				}

				if ( is_array( get_post_meta( $template->ID, BRICKS_DB_PAGE_FOOTER, true ) ) ) {
					$template_elements       = get_post_meta( $template->ID, BRICKS_DB_PAGE_FOOTER, true );
					$template_data['footer'] = $template_elements;
					$area                    = 'footer';
				}

				// Remove 'signature' from remote template element settings
				if ( $area && ! isset( $custom_args['remove_code_signature'] ) ) {
					foreach ( $template_elements as $index => $template_element ) {
						if ( ! empty( $template_element['name'] ) && in_array( $template_element['name'], [ 'code', 'svg' ] ) && isset( $template_element['settings']['signature'] ) ) {
							unset( $template_elements[ $index ]['settings']['signature'] );
						}

						if ( isset( $template_element['settings']['query']['signature'] ) ) {
							unset( $template_elements[ $index ]['settings']['query']['signature'] );
						}
					}

					$template_data[ $area ] = $template_elements;
				}

				$template_page_settings = get_post_meta( $template->ID, BRICKS_DB_PAGE_SETTINGS, true );

				if ( $template_page_settings ) {
					$template_data['pageSettings'] = $template_page_settings;
				}

				$templates[] = $template_data;
			}
		}

		// Filter templates
		$templates = apply_filters( 'bricks/get_templates', $templates, $custom_args );

		return $templates;
	}

	/**
	 * Get template authors
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_template_authors() {
		$template_ids = get_posts(
			[
				'post_type'      => BRICKS_DB_TEMPLATE_SLUG,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		$template_authors = [];

		foreach ( $template_ids as $template_id ) {
			$template_author_id = get_post_field( 'post_author', $template_id );
			$template_author    = get_the_author_meta( 'display_name', $template_author_id );

			if ( ! in_array( $template_author, $template_authors ) ) {
				$template_authors[] = $template_author;
			}
		}

		// Filter template authors
		$template_authors = apply_filters( 'bricks/get_template_authors', $template_authors );

		return $template_authors;
	}

	/**
	 * Get template bundles
	 *
	 * @since 1.0
	 */
	public static function get_template_bundles() {
		$terms = get_terms(
			[
				'taxonomy' => BRICKS_DB_TEMPLATE_TAX_BUNDLE,
			]
		);

		if ( ! is_array( $terms ) ) {
			return false;
		}

		$template_bundles = [];

		foreach ( $terms as $term ) {
			$term_obj                        = get_term( $term );
			$template_bundles[ $term->slug ] = $term->name;
		}

		// Filter template bundles
		$template_bundles = apply_filters( 'bricks/get_template_bundles', $template_bundles );

		return $template_bundles;
	}

	/**
	 * Get template tags
	 *
	 * @since 1.0
	 */
	public static function get_template_tags() {
		$terms = get_terms( [ 'taxonomy' => BRICKS_DB_TEMPLATE_TAX_TAG ] );

		if ( ! is_array( $terms ) ) {
			return false;
		}

		$template_tags = [];

		foreach ( $terms as $term ) {
			$term_obj                     = get_term( $term );
			$template_tags[ $term->slug ] = $term->name;
		}

		// Filter template bundles
		$template_tags = apply_filters( 'bricks/get_template_tags', $template_tags );

		return $template_tags;
	}

	/**
	 * Get template type via post_meta
	 *
	 * @param int $post_id
	 *
	 * @since 1.0
	 */
	public static function get_template_type( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$template_type = get_post_meta( $post_id, BRICKS_DB_TEMPLATE_TYPE, true );

		if ( isset( $template_type ) ) {
			return $template_type;
		}

		// Fallback: Check for content type if no template type post meta found
		if ( get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
			// Check for header template
			$header_template = get_post_meta( $post_id, BRICKS_DB_PAGE_HEADER, true );

			if ( is_array( $header_template ) ) {
				return 'header';
			}

			// Check for content template
			$content_template = get_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT, true );

			if ( is_array( $content_template ) ) {
				return 'content';
			}

			// Check for footer template
			$footer_template = get_post_meta( $post_id, BRICKS_DB_PAGE_FOOTER, true );

			if ( is_array( $footer_template ) ) {
				return 'footer';
			}
		} else {
			// Post type other than bricks_template
			return 'content';
		}

		return;
	}

	/**
	 * Get template by ID
	 *
	 * @since 1.0
	 */
	public static function get_template_by_id( $template_id ) {
		$template = self::get_templates(
			[
				'p'           => $template_id,
				'post_status' => 'any', // @since 1.5.1
			]
		);

		// Check if template match found
		if ( count( $template ) === 1 ) {
			$template = $template[0];
		} else {
			$template = false;
		}

		return $template;
	}

	/**
	 * Builder: Create template
	 *
	 * @since 1.0
	 */
	public function create_template() {
		Ajax::verify_request( 'bricks-nonce-builder' );

		if ( ! Capabilities::current_user_has_full_access() ) {
			wp_send_json_error( 'verify_request: Sorry, you are not allowed to perform this action.' );
		}

		$template_data = $_POST['templateData'] ?? [];

		// Insert new template into db
		$insert_post_data = [
			'post_status' => current_user_can( 'publish_posts' ) ? 'publish' : 'pending',
			'post_title'  => ! empty( $template_data['templateTitle'] ) ? esc_html( $template_data['templateTitle'] ) : esc_html__( '(no title)', 'bricks' ),
			'post_type'   => BRICKS_DB_TEMPLATE_SLUG,
		];

		$insert_post_data['tax_input'] = [];

		// Save template bundle term
		if ( isset( $template_data['templateBundle'] ) ) {
			$insert_post_data['tax_input'][ BRICKS_DB_TEMPLATE_TAX_BUNDLE ] = $template_data['templateBundle'];
		}

		// Save template tags
		if ( isset( $template_data['templateTags'] ) ) {
			$insert_post_data['tax_input'][ BRICKS_DB_TEMPLATE_TAX_TAG ] = $template_data['templateTags'];
		}

		$template_id = wp_insert_post( $insert_post_data );

		// Save template type in post meta
		if ( isset( $template_data['templateType'] ) ) {
			update_post_meta(
				$template_id,
				BRICKS_DB_TEMPLATE_TYPE,
				$template_data['templateType']
			);
		}

		$my_templates = self::get_templates(
			[
				'post_status' => 'any',
			]
		);

		wp_send_json_success( $my_templates );
	}

	/**
	 * Builder: Save template
	 *
	 * @since 1.0
	 */
	public function save_template() {
		Ajax::verify_request( 'bricks-nonce-builder' );

		$template_data = Ajax::decode( $_POST['templateData'] ?? [] );

		// Insert new template into db
		$insert_post_data = [
			'post_status' => current_user_can( 'publish_posts' ) ? 'publish' : 'pending',
			'post_title'  => ! empty( $template_data['templateTitle'] ) ? $template_data['templateTitle'] : esc_html__( '(no title)', 'bricks' ),
			'post_type'   => BRICKS_DB_TEMPLATE_SLUG,
		];

		$insert_post_data['tax_input'] = [];

		// Save template bundle term
		if ( isset( $template_data['templateBundle'] ) ) {
			$insert_post_data['tax_input'][ BRICKS_DB_TEMPLATE_TAX_BUNDLE ] = $template_data['templateBundle'];
		}

		// Save template tags
		if ( isset( $template_data['templateTags'] ) ) {
			$insert_post_data['tax_input'][ BRICKS_DB_TEMPLATE_TAX_TAG ] = $template_data['templateTags'];
		}

		$template_id = wp_insert_post( $insert_post_data );

		switch ( $template_data['templateType'] ) {
			case 'header':
				$meta_key          = BRICKS_DB_PAGE_HEADER;
				$template_elements = $template_data['header'];
				break;

			case 'footer':
				$meta_key          = BRICKS_DB_PAGE_FOOTER;
				$template_elements = $template_data['footer'];
				break;

			default:
				$meta_key          = BRICKS_DB_PAGE_CONTENT;
				$template_elements = $template_data['content'];
				break;
		}

		// Save data
		update_post_meta( $template_id, $meta_key, $template_elements );

		// Save template type
		update_post_meta( $template_id, BRICKS_DB_TEMPLATE_TYPE, $template_data['templateType'] );

		// Fetch all templates
		$my_templates = self::get_templates(
			[
				'post_status' => 'any',
			]
		);

		wp_send_json_success(
			[
				'templateId'  => $template_id,
				'myTemplates' => $my_templates,
			]
		);
	}

	/**
	 * Builder: Move template to trash
	 *
	 * @since 1.0
	 */
	public function delete_template() {
		Ajax::verify_request( 'bricks-nonce-builder' );

		if ( ! Capabilities::current_user_has_full_access() ) {
			wp_send_json_error( 'verify_request: Sorry, you are not allowed to perform this action.' );
		}

		$template_id = ! empty( $_POST['templateId'] ) ? intval( $_POST['templateId'] ) : false;

		// Double-check if user is allowed to delete template
		if ( ! Capabilities::current_user_can_use_builder( $template_id ) ) {
			$my_templates = self::get_templates(
				[
					'post_status' => 'any',
				]
			);

			wp_send_json_success( $my_templates );
		}

		if ( $template_id ) {
			wp_trash_post( $template_id );
		}

		$my_templates = self::get_templates( [ 'post_status' => 'any' ] );

		wp_send_json_success( $my_templates );
	}

	/**
	 * Admin & builder: Import template
	 *
	 * @since 1.0
	 */
	public function import_template() {
		if ( isset( $_POST['builder'] ) ) {
			Ajax::verify_nonce( 'bricks-nonce-builder' );
		} else {
			Ajax::verify_nonce( 'bricks-nonce-admin' );
		}

		if ( ! Capabilities::current_user_has_full_access() ) {
			wp_send_json_error( 'verify_request: Sorry, you are not allowed to perform this action.' );
		}

		/**
		 * Builder: Get global classes via 'globalClasses'
		 *
		 * @since 1.7.1 - json_decode instead of Ajax::decode to not run wp_slash
		 */
		$global_classes = ! empty( $_POST['globalClasses'] ) ? json_decode( $_POST['globalClasses'] ) : false;

		// Fallback: Get global classes from database
		if ( ! is_array( $global_classes ) || ( is_array( $global_classes ) && ! count( $global_classes ) ) ) {
			$global_classes = get_option( BRICKS_DB_GLOBAL_CLASSES, [] );
		}

		// Load WP_WP_Filesystem for temp file URL access
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';

			WP_Filesystem();
		}

		$templates = [];

		$is_zip_file = isset( $_FILES['files']['name'][0] ) ? pathinfo( $_FILES['files']['name'][0], PATHINFO_EXTENSION ) === 'zip' : false;

		if ( $is_zip_file ) {
			// Check if ZipArchive PHP extension exists
			if ( ! class_exists( '\ZipArchive' ) ) {
				wp_send_json_error( [ 'error' => 'Error: ZipArchive PHP extension does not exist.' ] );
			}

			$zip = new \ZipArchive();

			$wp_upload_dir = wp_upload_dir();

			$temp_path = trailingslashit( $wp_upload_dir['basedir'] ) . BRICKS_TEMP_DIR;

			// Create temp path if it doesn't exist
			wp_mkdir_p( $temp_path );

			if ( isset( $_FILES['files']['tmp_name'][0] ) ) {
				$zip->open( $_FILES['files']['tmp_name'][0] );
			}

			// Extract JSON files to temp directory
			$zip->extractTo( $temp_path );

			$zip->close();

			// Get all extracted JSON files (exclude '.' system files and reset array with array_values)
			$file_names = array_values( preg_grep( '/^([^.])/', scandir( $temp_path ) ) );

			foreach ( $file_names as $file_name ) {
				$templates[] = json_decode( $wp_filesystem->get_contents( trailingslashit( $temp_path ) . $file_name ), true );

				// Remove JSON file
				unlink( trailingslashit( $temp_path ) . $file_name );
			}

			// Remove temp directory
			rmdir( $temp_path );
		} else {
			// Import single JSON file
			$files = $_FILES['files']['tmp_name'] ?? [];

			foreach ( $files as $file ) {
				$templates[] = json_decode( $wp_filesystem->get_contents( $file ), true );
			}
		}

		foreach ( $templates as $template_data ) {
			$insert_post_data = [
				'post_status' => current_user_can( 'publish_posts' ) ? 'publish' : 'pending',
				'post_title'  => ! empty( $template_data['title'] ) ? $template_data['title'] : esc_html__( '(no title)', 'bricks' ),
				'post_type'   => BRICKS_DB_TEMPLATE_SLUG,
			];

			// Template tags (terms)
			if ( is_array( $template_data['tags'] ) ) {
				if ( count( $template_data['tags'] ) ) {
					$insert_post_data['tax_input'] = [ BRICKS_DB_TEMPLATE_TAX_TAG => $template_data['tags'] ];
				}
			}

			// Template bundles (terms)
			if ( is_array( $template_data['bundles'] ) ) {
				if ( count( $template_data['bundles'] ) ) {
					$insert_post_data['tax_input'] = [ BRICKS_DB_TEMPLATE_TAX_BUNDLE => $template_data['bundles'] ];
				}
			}

			$new_template_id = wp_insert_post( $insert_post_data );
			$area            = 'content';
			$meta_key        = BRICKS_DB_PAGE_CONTENT;
			$elements        = false;

			if ( ! empty( $template_data['templateType'] ) ) {
				update_post_meta( $new_template_id, BRICKS_DB_TEMPLATE_TYPE, $template_data['templateType'] );
			}

			if ( ! empty( $template_data['header'] ) ) {
				$area     = 'header';
				$meta_key = BRICKS_DB_PAGE_HEADER;
			} elseif ( ! empty( $template_data['footer'] ) ) {
				$area     = 'footer';
				$meta_key = BRICKS_DB_PAGE_FOOTER;
			}

			if ( ! empty( $template_data[ $area ] ) ) {
				$elements = $template_data[ $area ];
			}

			if ( isset( $template_data['pageSettings'] ) ) {
				update_post_meta( $new_template_id, BRICKS_DB_PAGE_SETTINGS, $template_data['pageSettings'] );
			}

			// Add template settings (@since 1.8.1)
			if ( isset( $template_data['templateSettings'] ) ) {
				Helpers::set_template_settings( $new_template_id, $template_data['templateSettings'] );
			}

			// STEP: Add global classes used in template to global classes in this database
			$template_global_classes         = ! empty( $template_data['global_classes'] ) ? $template_data['global_classes'] : [];
			$map_classes                     = []; // @see PopupTemplates.vue (@since 1.5.1)
			$maybe_pseudo_class_setting_keys = [];

			foreach ( $template_global_classes as $template_class ) {
				// STEP: Add template setting keys to create missing pseudo class from (@since 1.7.1)
				if ( ! empty( $template_class['settings'] ) ) {
					$maybe_pseudo_class_setting_keys = array_merge( $maybe_pseudo_class_setting_keys, array_keys( $template_class['settings'] ) );
				}

				// Skip: Class with same unique 'id' exists locally
				$class_index = array_search( $template_class['id'], array_column( $global_classes, 'id' ) );

				if ( $class_index !== false ) {
					continue;
				}

				// Add to map_classes, then skip (global class with this 'name' already exists in this installation)
				$class_index = array_search( $template_class['name'], array_column( $global_classes, 'name' ) );

				if ( $class_index !== false ) {
					$map_classes[ $template_class['id'] ] = $global_classes[ $class_index ]['id'];

					continue;
				}

				// Update global classes in database
				$global_classes[] = $template_class;
			}

			// Loop over all mapped classes to replace template element class id's with local class id's
			foreach ( $map_classes as $template_class_id => $local_class_id ) {
				foreach ( $elements as $index => $element ) {
					$element_classes = ! empty( $element['settings']['_cssGlobalClasses'] ) ? $element['settings']['_cssGlobalClasses'] : [];

					if ( count( $element_classes ) ) {
						foreach ( $element_classes as $class_index => $element_class_id ) {
							if ( $element_class_id === $template_class_id ) {
								$element_classes[ $class_index ] = $local_class_id;
							}
						}

						$elements[ $index ]['settings']['_cssGlobalClasses'] = $element_classes;
					}
				}
			}

			// STEP: Update global classes in db
			$global_classes_updated = Helpers::save_global_classes_in_db( $global_classes, 'import_template' );

			// STEP: Save final template elements
			$elements = Helpers::sanitize_bricks_data( $elements );

			// Add back slashes to element settings (needed for '_content' HTML entities, and Custom CSS) @since 1.7.1
			foreach ( $elements as $index => $element ) {
				$element_settings = ! empty( $element['settings'] ) ? $element['settings'] : [];

				foreach ( $element_settings as $setting_key => $setting_value ) {
					if ( is_string( $setting_value ) ) {
						$elements[ $index ]['settings'][ $setting_key ] = addslashes( $setting_value );
					}
				}
			}

			update_post_meta( $new_template_id, $meta_key, $elements );

			// STEP: Generate CSS file for imported template
			if ( Database::get_setting( 'cssLoading' ) === 'file' && $elements ) {
				$template_css_file_name = Assets_Files::generate_post_css_file( $new_template_id, $area, $elements );
			}

			// STEP: Add pseudo elements & classes used in the template to the database (@since 1.7.1)

			// Get latest pseudo classes from builder
			$pseudo_classes = ! empty( $_POST['pseudoClasses'] ) ? Ajax::decode( $_POST['pseudoClasses'], false ) : [];

			// Add element setting keys to create missing pseudo class from
			foreach ( $elements as $element ) {
				if ( ! empty( $element['settings'] ) ) {
					$maybe_pseudo_class_setting_keys = array_merge( $maybe_pseudo_class_setting_keys, array_keys( $element['settings'] ) );
				}
			}

			$all_pseudo_classes = self::template_import_create_missing_pseudo_classes( $pseudo_classes, $maybe_pseudo_class_setting_keys );
			$all_pseudo_classes = array_unique( $all_pseudo_classes );

			// Update pseudo classes db entry (if we got more items than before)
			if ( count( $all_pseudo_classes ) > count( $pseudo_classes ) ) {
				update_option( BRICKS_DB_PSEUDO_CLASSES, $all_pseudo_classes );
			}
		}

		$my_templates = self::get_templates(
			[
				'post_status' => 'any',
			]
		);

		wp_send_json_success(
			[
				'my_templates'   => $my_templates,
				'global_classes' => $global_classes,
				'pseudo_classes' => $all_pseudo_classes,
			]
		);
	}

	/**
	 * STEP: Check global class setting key for occurence of pseudo element to create pseudo element in local installtion
	 *
	 * @since 1.7.1
	 */
	public static function template_import_create_missing_pseudo_classes( $pseudo_classes, $setting_keys = [] ) {
		// Pseudo elements source of truth: https://developer.mozilla.org/en-US/docs/Web/CSS/Pseudo-elements
		$valid_pseudo_elements = [
			'::after',
			':after',

			'::backdrop',
			':backdrop',

			'::before',
			':before',

			'::cue',
			':cue',

			'::cue-region',
			':cue-region',

			'::first-letter',
			':first-letter',

			'::first-line',
			':first-line',

			'::file-selector-button',
			':file-selector-button',

			'::grammar-error',
			':grammar-error',

			'::marker',
			':marker',

			// '::part(',

			'::placeholder',
			':placeholder',

			'::selection',
			':selection',

			// '::slotted(',

			'::spelling-error',
			':spelling-error',

			'::target-text',
			':target-text',
		];

		// Pseudo classes source of truth: https://developer.mozilla.org/en-US/docs/Web/CSS/Pseudo-classes
		$valid_pseudo_classes = [
			':active',
			':any-link',
			':autofill',
			':blank', // Experimental
			':checked',
			':current', // Experimental
			':default',
			':defined',
			':dir(', // Experimental
			':disabled',
			':empty',
			':enabled',
			':first',
			':first-child',
			':first-of-type',
			':fullscreen',
			':future', // Experimental
			':focus',
			':focus-visible',
			':focus-within',
			':has(', // Experimental
			':host',
			':host(',
			':host-context(', // Experimental
			':hover',
			':indeterminate',
			':in-range',
			':invalid',
			':is(',
			':lang(',
			':last-child',
			':last-of-type',
			':left',
			':link',
			':local-link', // Experimental
			':modal',
			':not(',
			':nth-child(',
			':nth-col(', // Experimental
			':nth-last-child(',
			':nth-last-col(', // Experimental
			':nth-last-of-type(',
			':nth-of-type(',
			':only-child',
			':only-of-type',
			':optional',
			':out-of-range',
			':past', // Experimental
			':picture-in-picture',
			':placeholder-shown',
			':paused',
			':playing',
			':read-only',
			':read-write',
			':required',
			':right',
			':root',
			':scope',
			':state(', // Experimental
			':target',
			':target-within', // Experimental
			':user-invalid', // Experimental
			':valid',
			':visited',
			':where(',
		];

		// Loop over all settings keys to find pseudo classes & pseudo elements
		foreach ( $setting_keys as $setting_key ) {
			// Pseudo class is always the last setting key part
			$setting_key_parts  = explode( ':', $setting_key );
			$maybe_pseudo_class = ':' . end( $setting_key_parts );

			// STEP: Detect pseudo classes
			foreach ( $valid_pseudo_classes as $pseudo_class ) {
				// Pseudo class with arguments: :nth-child(even)
				if ( strpos( $pseudo_class, '(' ) !== false ) {
					if (
						strpos( $maybe_pseudo_class, $pseudo_class ) !== false &&
						! in_array( $maybe_pseudo_class, $pseudo_classes )
					) {
						$pseudo_classes[] = $maybe_pseudo_class;
						break;
					}
				}

				// All other pseudo classes
				elseif (
					substr( $setting_key, -strlen( $pseudo_class ) ) === $pseudo_class && // setting key ends with pseudo clas
					substr( $setting_key, strpos( $setting_key, $pseudo_class ) - 1, 1 ) !== ':' && // charcter before pseudo clas is not a ':'
					! in_array( $pseudo_class, $pseudo_classes ) // pseudo class not part of global pseudo classes array
				) {
					$pseudo_classes[] = $pseudo_class;
					break;
				}
			}

			// STEP: Detect pseudo elements
			foreach ( $valid_pseudo_elements as $pseudo_element ) {
				if (
					substr( $setting_key, -strlen( $pseudo_element ) ) === $pseudo_element && // setting key ends with pseudo element
					substr( $setting_key, strpos( $setting_key, $pseudo_element ) - 1, 1 ) !== ':' && // charcter before pseudo element is not a ':'
					! in_array( $pseudo_element, $pseudo_classes ) // pseudo element not part of global pseudo classes array
				) {
					$pseudo_classes[] = $pseudo_element;
					break;
				}
			}
		}

		return $pseudo_classes;
	}

	/**
	 * Export template as JSON file
	 *
	 * @param int $template_id Provided if bulk action export.
	 * @see: admin.php:export_templates()
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function export_template( $template_id = 0 ) {
		// No template_id passed: Admin or builder call
		if ( ! $template_id ) {
			if ( isset( $_GET['builder'] ) ) {
				Ajax::verify_nonce( 'bricks-nonce-builder' );
			} else {
				Ajax::verify_nonce( 'bricks-nonce-admin' );
			}

			$template_id = isset( $_GET['templateId'] ) ? intval( $_GET['templateId'] ) : 0;
		}

		if ( ! Capabilities::current_user_has_full_access() ) {
			wp_send_json_error( 'verify_request: Sorry, you are not allowed to perform this action.' );
		}

		if ( ! $template_id ) {
			wp_send_json_error( 'export_template:error: no templateId provided' );
		}

		$template_data = self::get_template_by_id( $template_id );

		$template_type = get_post_meta( $template_id, BRICKS_DB_TEMPLATE_TYPE, true );

		$template_data['templateType'] = $template_type;

		/**
		 * STEP: Add global CSS classes used in template to template data (so it can later be imported as well)
		 *
		 * @since 1.4
		 */
		if ( $template_type === 'header' || $template_type === 'footer' ) {
			$template_elements = isset( $template_data[ $template_type ] ) ? $template_data[ $template_type ] : [];
		} else {
			$template_elements = isset( $template_data['content'] ) ? $template_data['content'] : [];
		}

		/**
		 * STEP: Add template settings to template data
		 *
		 * NOTE: Should we remove 'templatePreview...' settings too?
		 *
		 * @since 1.8.1
		 */
		$template_settings = Helpers::get_template_settings( $template_id );

		// Remove template conditions
		if ( isset( $template_settings['templateConditions'] ) ) {
			unset( $template_settings['templateConditions'] );
		}

		// Save as templateSettings, to be imported later
		if ( is_array( $template_settings ) && ! empty( $template_settings ) ) {
			$template_data['templateSettings'] = $template_settings;
		}

		$template_classes = [];

		foreach ( $template_elements as $element ) {
			if ( ! empty( $element['settings']['_cssGlobalClasses'] ) ) {
				$template_classes = array_unique( array_merge( $template_classes, $element['settings']['_cssGlobalClasses'] ) );
			}
		}

		// Add class definition to template data
		$global_classes        = get_option( BRICKS_DB_GLOBAL_CLASSES, [] );
		$global_classes_to_add = [];

		foreach ( $global_classes as $global_class ) {
			if ( in_array( $global_class['id'], $template_classes ) ) {
				$global_classes_to_add[] = $global_class;
			}
		}

		if ( count( $global_classes_to_add ) ) {
			$template_data['global_classes'] = $global_classes_to_add;
		}

		// Lowercase
		$file_name = ! empty( $template_data['title'] ) ? strtolower( $template_data['title'] ) : 'no-title';

		// Make alphanumeric (removes all other characters)
		$file_name = preg_replace( '/[^a-z0-9_\s-]/', '', $file_name );

		// Clean up multiple dashes or whitespaces
		$file_name = preg_replace( '/[\s-]+/', ' ', $file_name );

		// Convert whitespaces and underscore to dashes
		$file_name = preg_replace( '/[\s_]/', '-', $file_name );

		// Final file name
		$file_name = 'template-' . $file_name . '-' . date( 'Y-m-d' ) . '.json';

		if ( bricks_is_builder_call() ) {
			// Download individual template
			header( 'Content-Type:application/json; charset=utf-8' );
			header( "Content-Disposition: attachment; filename=$file_name" );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Expires: 0' );
			header( 'Pragma: public' );

			// Disable zlib compression to avoid empty template (#31nepuc @since 1.6)
			ini_set( 'zlib.output_compression', '0' );

			@ob_end_flush();

			echo wp_json_encode( $template_data );
			die;
		} else {
			// Bulk action: Export
			return [
				'name'    => $file_name,
				'content' => wp_json_encode( $template_data ),
			];
		}
	}

	/**
	 * Check if setting value has image/svg properties
	 *
	 * @since 1.3.2
	 */
	public static function is_image( $setting ) {
		return isset( $setting['id'] ) && isset( $setting['url'] ) &&
			( isset( $setting['size'] ) && isset( $setting['full'] ) ) ||
			( isset( $setting['url'] ) && strpos( $setting['url'], '.svg' ) !== false );
	}

	/**
	 * Recursive function: Import remote element images from template data
	 *
	 * @since 1.3.2
	 */
	public static function import_images( $settings, $import_images ) {
		foreach ( $settings as $key => $value ) {
			if ( self::is_image( $value ) ) {
				self::import_image( $value, $import_images );
			} elseif ( is_array( $value ) ) {
				self::import_images( $value, $import_images );
			}
		}
	}

	public static function import_image( $image, $import_images ) {
		if ( ! $image ) {
			return [ 'error' => 'No image provided.' ];
		}

		if ( ! isset( $image['url'] ) ) {
			return [ 'error' => 'No image URL provided.' ];
		}

		// Check if SVG (SVG has no 'full' and 'size' attributes)
		$is_svg = pathinfo( $image['url'], PATHINFO_EXTENSION ) === 'svg';

		// STEP: No image import requested: Return placeholder image
		if ( ! $import_images && ! $is_svg ) {
			// Add to instance property to replace templateData before returning it to Vue
			$placeholder_image = [
				'url'  => Builder::get_template_placeholder_image(),
				'full' => Builder::get_template_placeholder_image(),
			];

			self::$template_images[] = [
				'old' => $image,
				'new' => $placeholder_image,
			];

			return $placeholder_image;
		}

		// Not allowed to upload SVG: Remove 'file' value
		elseif ( $import_images && $is_svg && ! Capabilities::current_user_can_upload_svg() && ! empty( $image['url'] ) ) {
			$svg_blank = $image;
			unset( $svg_blank['url'] );

			if ( isset( $svg_blank['filename'] ) ) {
				unset( $svg_blank['filename'] );
			}

			self::$template_images[] = [
				'old' => $image,
				'new' => $svg_blank,
			];
		}

		if ( ! isset( $image['id'] ) ) {
			return [ 'error' => 'No image ID provided (i.e. it is a placeholder image).' ];
		}

		if ( ! $is_svg && ! isset( $image['full'] ) ) {
			return [ 'error' => 'No full URL provided.' ];
		}

		if ( ! $is_svg && ! isset( $image['size'] ) ) {
			return [ 'error' => 'No image size provided.' ];
		}

		$image_size     = $is_svg ? 'full' : $image['size'];
		$image_full_url = $is_svg ? $image['url'] : $image['full'];
		$filename       = basename( $image_full_url );

		// Check if image has been downloaded before (by post meta '_bricks_image_origin_url' against image 'full' URL)
		global $wpdb;

		// Return existing image ID if match found in db
		$existing_image_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_bricks_image_origin_url' AND meta_value = '$image_full_url'" );

		// Check for existing image ID by URL
		if ( ! $existing_image_id ) {
			$existing_image_id = attachment_url_to_postid( $image_full_url );
		}

		if ( $existing_image_id ) {
			$new_image_full_url = wp_get_attachment_image_url( $existing_image_id, 'full' );
		}

		// Return existing image as an object
		if ( $existing_image_id && $new_image_full_url ) {
			$existing_image = [
				'id'       => $existing_image_id,
				'filename' => $filename,
				'size'     => $image_size,
				'full'     => $new_image_full_url,
				'url'      => wp_get_attachment_image_url( $existing_image_id, $image_size ),
			];

			// Add to instance property to replace templateData before returning it to Vue
			self::$template_images[] = [
				'old' => $image,
				'new' => $existing_image,
			];

			return $existing_image;
		}

		// Image not found in db: Download new image, then return new image object
		$remote_image = Helpers::remote_get( $image_full_url );

		$type = wp_remote_retrieve_header( $remote_image, 'content-type' );

		$mirror = wp_upload_bits( $filename, null, wp_remote_retrieve_body( $remote_image ) );

		$new_attachment = [
			'post_title'     => $filename,
			'post_mime_type' => $type,
		];

		if ( ! isset( $mirror['file'] ) ) {
			return [
				'error'  => 'Error: wp_upload_bits failed (no "file" passed)',
				'mirror' => $mirror,
			];
		}

		$new_attachment_id = wp_insert_attachment( $new_attachment, $mirror['file'] );

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$new_attachment_metadata = wp_generate_attachment_metadata( $new_attachment_id, $mirror['file'] );

		wp_update_attachment_metadata( $new_attachment_id, $new_attachment_metadata );

		update_post_meta( $new_attachment_id, '_bricks_image_origin_url', $image_full_url );

		$new_image = [
			'id'       => $new_attachment_id,
			'filename' => $filename,
			'size'     => $image_size,
			'full'     => wp_get_attachment_image_url( $new_attachment_id, 'full' ),
			'url'      => wp_get_attachment_image_url( $new_attachment_id, $image_size ),
		];

		// Add to instance property to replace templateData before returning it to Vue
		self::$template_images[] = [
			'old' => $image,
			'new' => $new_image,
		];

		return $new_image;
	}

	/**
	 * Builder: Convert template data to new container layout structure
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function convert_template() {
		Ajax::verify_nonce( 'bricks-nonce-builder' );

		if ( ! Capabilities::current_user_has_full_access() ) {
			wp_send_json_error( 'verify_request: Sorry, you are not allowed to perform this action.' );
		}

		$elements      = ! empty( $_POST['templateData'] ) ? Ajax::decode( $_POST['templateData'], false ) : [];
		$import_images = isset( $_POST['importImages'] ) && $_POST['importImages'] == 'true';

		// Reset template instance data for new template insert
		self::$template_images      = [];
		self::$template_element_ids = [];

		/**
		 * STEP: Convert container-based layout structure (@pre 1.5) to 'section', 'container', 'block' structure (@since 1.5)
		 *
		 * NOTE: Removed "Convert templates" Bricks setting @since 1.9.4
		 *
		 * On template import & insert.
		 */
		if ( isset( Database::$global_settings['convertTemplates'] ) ) {
			$converter_response = Converter::convert_container_to_section_block_element( $elements );

			if ( ! empty( $converter_response['elements'] ) ) {
				$elements = $converter_response['elements'];
			}
		}

		foreach ( $elements as $index => $element ) {
			$old_id = ! empty( $element['id'] ) ? $element['id'] : '';
			$new_id = Helpers::generate_random_id( false );

			// STEP: Generate & set new element ID
			if ( $old_id ) {
				self::$template_element_ids[ $old_id ] = $new_id;

				foreach ( Breakpoints::$breakpoints as $bp ) {
					$breakpoint_key = $bp['key'];

					$custom_css_setting_key = $breakpoint_key === 'desktop' ? '_cssCustom' : "_cssCustom:{$breakpoint_key}";

					// Update custom CSS element ID
					$custom_css = ! empty( $element['settings'][ $custom_css_setting_key ] ) ? $element['settings'][ $custom_css_setting_key ] : false;

					if ( $custom_css ) {
						$custom_css = str_replace( $old_id, $new_id, $custom_css );

						// @since 1.4 Use new Bricks class name prefix: 'brxe-'
						$custom_css = str_replace( "bricks-element-$new_id", "brxe-$new_id", $custom_css );

						$elements[ $index ]['settings'][ $custom_css_setting_key ] = $custom_css;
					}
				}
			}

			if ( empty( $element['settings'] ) ) {
				$elements[ $index ]['settings'] = [];
			}

			// STEP: Import element images & update template data with local image data
			else {
				self::import_images( $element['settings'], $import_images );
			}

			if ( ! isset( $element['children'] ) ) {
				$elements[ $index ]['children'] = [];
			}
		}

		// STEP: Replace remote image data with imported/existing image data
		if ( count( self::$template_images ) ) {
			$elements_encoded = wp_json_encode( $elements );

			foreach ( self::$template_images as $template_image ) {
				$elements_encoded = str_replace(
					wp_json_encode( $template_image['old'] ),
					wp_json_encode( $template_image['new'] ),
					$elements_encoded
				);
			}

			$elements = json_decode( $elements_encoded, true );
		}

		// STEP: Replace old element IDs and 'child' IDs with newly generated ones
		foreach ( self::$template_element_ids as $old_id => $new_id ) {
			foreach ( $elements as $index => $element ) {
				// Replace element ID
				if ( $element['id'] === $old_id ) {
					$elements[ $index ]['id'] = $new_id;
				}

				// STEP: Replace element parent IDs
				if ( ! empty( $element['parent'] ) && $element['parent'] === $old_id ) {
					$elements[ $index ]['parent'] = $new_id;
				} elseif ( isset( $element['parent'] ) && $element['parent'] === '0' ) {
					// Make sure parentless elements are integer 0 value
					$elements[ $index ]['parent'] = 0;
				}

				// STEP: Replace element children IDs
				if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
					foreach ( $element['children'] as $child_index => $child_id ) {
						if ( $child_id === $old_id ) {
							$elements[ $index ]['children'][ $child_index ] = $new_id;
						}
					}
				}
			}
		}

		wp_send_json_success( [ 'elements' => $elements ] );
	}

	/**
	 * Get the Templates list for the Template element (for the moment only Section and Content/Single template types)
	 */
	public static function get_templates_list( $template_types = '', $exclude_template_id = '' ) {
		$templates = self::get_templates_by_type( $template_types );

		$list = [];

		foreach ( $templates as $template_id ) {
			if ( $exclude_template_id == $template_id ) {
				continue;
			}

			$list[ $template_id ] = get_the_title( $template_id );
		}

		return $list;
	}

	/**
	 * Get IDs of all templates
	 *
	 * @see admin.php get_converter_items()
	 * @see files.php get_css_files_list()
	 *
	 * @param array $custom_args array Custom get_posts() arguments (@since 1.8; @see get_css_files_list).
	 *
	 * @since 1.4
	 */
	public static function get_all_template_ids( $custom_args = [] ) {
		$args = array_merge(
			[
				'post_type'              => BRICKS_DB_TEMPLATE_SLUG,
				'posts_per_page'         => -1,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => [
					'relation' => 'OR',
					[
						'key'     => BRICKS_DB_PAGE_HEADER,
						'value'   => '',
						'compare' => '!=',
					],
					[
						'key'     => BRICKS_DB_PAGE_CONTENT,
						'value'   => '',
						'compare' => '!=',
					],
					[
						'key'     => BRICKS_DB_PAGE_FOOTER,
						'value'   => '',
						'compare' => '!=',
					],
				],
			],
			$custom_args
		);

		return get_posts( $args );
	}

	/**
	 * Remove templates from /wp-sitemap.xml if not set to "Public templates" in Bricks settings
	 *
	 * @since 1.4
	 */
	public function remove_templates_from_wp_sitemap( $post_types ) {
		if ( ! isset( Database::$global_settings['publicTemplates'] ) ) {
			unset( $post_types[ BRICKS_DB_TEMPLATE_SLUG ] );
		}

		return $post_types;
	}

	/**
	 * Remove template taxonomies from /wp-sitemap.xml if not set to "Public templates" in Bricks settings
	 *
	 * @since 1.8
	 */
	public function remove_template_taxonomies_from_wp_sitemap( $taxonomies ) {
		if ( ! isset( Database::$global_settings['publicTemplates'] ) ) {
			unset( $taxonomies[ BRICKS_DB_TEMPLATE_TAX_TAG ] );
			unset( $taxonomies[ BRICKS_DB_TEMPLATE_TAX_BUNDLE ] );
		}

		return $taxonomies;
	}

	/**
	 * Frontend: Assign templates to hooks
	 *
	 * @since 1.9.1
	 */
	public function assign_templates_to_hooks() {
		// Return: In builder
		if ( bricks_is_builder() ) {
			return;
		}

		// STEP: Get all section templates
		$section_templates = self::get_templates_query(
			[
				'meta_query' => [
					[
						'key'     => BRICKS_DB_TEMPLATE_TYPE,
						'value'   => 'section',
						'compare' => '=',
					],
				],
			]
		);

		// Return: No section templates found
		if ( ! $section_templates->have_posts() ) {
			return;
		}

		// STEP: Loop over all section templates with 'Assign to hook' setting
		foreach ( $section_templates->posts as $section_template ) {
			$template_id       = $section_template->ID;
			$template_settings = Helpers::get_template_settings( $template_id );

			// Skip: Previewing the template itself
			if ( $template_id == get_the_ID() ) {
				continue;
			}

			$template_conditions = $template_settings['templateConditions'] ?? [];

			if ( empty( $template_conditions ) ) {
				continue;
			}

			// We need to group each condition by hookName and hookPriority
			$arranged_conditions = [];
			// STEP: rearrange conditions, group by hookName and hookPriority
			foreach ( $template_conditions as $condition ) {
				$hook_name     = $condition['hookName'] ?? false;
				$hook_priority = $condition['hookPriority'] ?? 10;

				// If hook name is not set, we skip this condition
				if ( ! $hook_name ) {
					continue;
				}

				$key = $hook_name . '|' . $hook_priority;

				if ( ! isset( $arranged_conditions[ $key ] ) ) {
					$arranged_conditions[ $key ] = [];
				}

				// Backward compatibility: If $condition['main'] === 'hook', set it as 'any' (run in entire website)
				$condition['main']             = $condition['main'] === 'hook' ? 'any' : $condition['main'];
				$arranged_conditions[ $key ][] = $condition;
			}

			// STEP: Decide if we need to add the template to the hook
			foreach ( $arranged_conditions as $key => $conditions ) {
				$hook_name     = explode( '|', $key )[0];
				$hook_priority = explode( '|', $key )[1];

				$run_hook = self::run_template_on_hook( $conditions );

				if ( ! $run_hook ) {
					continue;
				}

				// STEP: Add template to hook
				add_action(
					$hook_name,
					function() use ( $template_id ) {
						// Use [bricks_template] shortcode to render the template content (included styles)
						echo do_shortcode( "[bricks_template id='$template_id' on_hook='1']" );
					},
					$hook_priority
				);
			}
		}

	}

	/**
	 * Check if template should be run on hook
	 *
	 * @since 1.9.2
	 *
	 * @param array $arranged_conditions
	 *
	 * @return bool
	 */
	public static function run_template_on_hook( $arranged_conditions = [] ) {
		if ( empty( $arranged_conditions ) ) {
			return false;
		}

		$preview_type = '';
		$post_id      = Database::$page_data['post_id'];

		// Check if currently previewing a template
		if ( is_singular( BRICKS_DB_TEMPLATE_SLUG ) && isset( Database::$page_data['preview_or_post_id'] ) ) {
			$preview_type = Helpers::get_template_setting( 'templatePreviewType', Database::$page_data['preview_or_post_id'] );
			$post_id      = Database::$page_data['preview_or_post_id'];
		}

		$post_type = get_post_type( $post_id ); // Considered template preview as well

		$results = [
			'include' => [],
			'exclude' => [],
		];

		// STEP: Loop over all template conditions: If they are met, store results in $results
		foreach ( $arranged_conditions as $condition ) {
			if ( ! isset( $condition['main'] ) ) {
				continue;
			}

			// Reset condition met
			$condition_met = false;

			/**
			 * Possible values:
			 * any, frontpage, postType, archiveType, search, error, terms, ids
			 */
			$condition_type = $condition['main'];

			switch ( $condition_type ) {
				// Entire website
				case 'any':
					$condition_met = true;
					break;

				// Check for front page
				case 'frontpage':
					if ( bricks_is_ajax_call() || bricks_is_rest_call() ) {
						$front_page_id = get_option( 'page_on_front' );
						$is_front_page = absint( $post_id ) == absint( $front_page_id );
					} else {
						$is_front_page = is_front_page();
					}

					if ( $is_front_page ) {
						$condition_met = true;
					}
					break;

				// Check for a specific post type
				case 'postType':
					// Did not set any post types, skip
					if ( ! isset( $condition['postType'] ) ) {
						break;
					}

					// Check if the current post type matches any of the selected post types. $post_type considered template preview as well
					if ( in_array( $post_type, $condition['postType'] ) ) {
						$condition_met = true;
					}
					break;

				// Archive (any/author/data/term)
				case 'archiveType':
					if ( ! isset( $condition['archiveType'] ) ) {
						break;
					}

					// Archive pages include category, tag, author, date, custom post type, and custom taxonomy based archives.
					if ( in_array( 'any', $condition['archiveType'] ) && ( is_archive() || strpos( $preview_type, 'archive' ) !== false ) ) {
						$condition_met = true;
					}

					// Post type archive
					elseif ( in_array( 'postType', $condition['archiveType'] ) && ( is_post_type_archive() || $preview_type === 'archive-cpt' ) ) {
						if ( empty( $condition['archivePostTypes'] ) ) {
							// no post types set, any post type archive matches
							$condition_met = true;
						} else {

							// Previewing a template with content set to a CPT archive
							if ( $preview_type === 'archive-cpt' ) {
								$preview_cpt = Helpers::get_template_setting( 'templatePreviewPostType', $post_id );
								if ( $preview_cpt && in_array( $preview_cpt, $condition['archivePostTypes'] ) ) {
									$condition_met = true;
								}
							}
							// or, check if the post type archive matches the post type condition
							elseif ( is_post_type_archive( $condition['archivePostTypes'] ) ) {
								$condition_met = true;
							}

						}
					}

					// Author archive
					elseif ( in_array( 'author', $condition['archiveType'] ) && ( is_author() || $preview_type === 'archive-author' ) ) {
						$condition_met = true;
					}

					// Date archive
					elseif ( in_array( 'date', $condition['archiveType'] ) && ( is_date() || $preview_type === 'archive-date' ) ) {
						$condition_met = true;
					}

					// Term archive
					elseif ( in_array( 'term', $condition['archiveType'] ) && ( is_category() || is_tag() || is_tax() || $preview_type === 'archive-term' ) ) {
						if ( empty( $condition['archiveTerms'] ) ) {
							// no taxonomies set, any taxonomy archive matches
							$condition_met = true;
						} elseif ( is_array( $condition['archiveTerms'] ) ) {

							// Previewing a template, with populate content set to archive of term
							if ( $preview_type === 'archive-term' ) {
								// Note the post_id here is the template post Id (because in this archive situation the preview_id was not set)
								$preview_term = Helpers::get_template_setting( 'templatePreviewTerm', $post_id );

								if ( ! empty( $preview_term ) ) {
									$preview_term     = explode( '::', $preview_term );
									$queried_taxonomy = isset( $preview_term[0] ) ? $preview_term[0] : '';
									$queried_term_id  = isset( $preview_term[1] ) ? intval( $preview_term[1] ) : '';
								}
							}

							// All the other situations in frontend: is_category() || is_tag() || is_tax()
							else {
								$queried_object = get_queried_object();

								if ( is_object( $queried_object ) ) {
									$queried_term_id  = intval( $queried_object->term_id );
									$queried_taxonomy = $queried_object->taxonomy;
								}
							}

							// Check if queried taxonomy and term_id matches any of the selected archive terms
							if ( ! empty( $queried_term_id ) && ! empty( $queried_taxonomy ) ) {
								foreach ( $condition['archiveTerms'] as $archive_term ) {
									$term_parts = explode( '::', $archive_term );
									$taxonomy   = $term_parts[0];
									$term_id    = $term_parts[1];

									if ( $queried_taxonomy === $taxonomy ) {
										if ( $queried_term_id === intval( $term_id ) ) {
											$condition_met = true;
											break;
										}

										// Applied for taxonomy::all (all terms of a taxonomy)
										elseif ( 'all' == $term_id ) {
											$condition_met = true;
											break;
										}

										// The condition includes child terms, check if the queried term id is child of the term id set in the condition
										elseif ( isset( $condition['archiveTermsIncludeChildren'] ) && term_is_ancestor_of( $term_id, $queried_term_id, $queried_taxonomy ) ) {
											$condition_met = true;
											break;
										}
									}
								}
							}
						}
					}
					break;

				// Check for search
				case 'search':
					if ( is_search() || $preview_type === 'search' ) {
						$condition_met = true;
					}
					break;

				// Check for error
				case 'error':
					if ( is_404() || $preview_type === 'error' ) {
						$condition_met = true;
					}
					break;

				// Check for a specific term assigned to the post
				case 'terms':
					// Did not set any terms, skip
					if ( ! isset( $condition['terms'] ) || empty( $post_id ) ) {
						break;
					}

					$terms = $condition['terms'];

					foreach ( $terms as $term ) {
						$tax_term = explode( '::', $term );
						$taxonomy = $tax_term[0];
						$term     = $tax_term[1];

						$post_terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );

						if ( is_array( $post_terms ) && in_array( $term, $post_terms ) ) {
							$condition_met = true;
							break;
						}
					}
					break;

				// Check for a specific post ID or children
				case 'ids':
					// Did not set any post IDs, skip
					if ( ! isset( $condition['ids'] ) || empty( $post_id ) ) {
						break;
					}

					// Specif post ID
					if ( in_array( $post_id, $condition['ids'] ) ) {
						$condition_met = true;
						break;
					}

					// Apply to child pages
					elseif ( isset( $condition['idsIncludeChildren'] ) ) {
						$ancestors = get_post_ancestors( $post_id );

						foreach ( $ancestors as $ancestor_id ) {
							if ( in_array( $ancestor_id, $condition['ids'] ) ) {
								$condition_met = true;
								break;
							}
						}
					}
					break;
			}

			// Store condition result
			$exclude                          = isset( $condition['exclude'] );
			$include_or_exclude               = $exclude ? 'exclude' : 'include';
			$results[ $include_or_exclude ][] = [
				'condition_type' => $condition_type,
				'condition_met'  => $condition_met,
				'exclude'        => $exclude,
			];
		} // end foreach

		// STEP: Analyze results
		// If exclude is empty: user wants to insert the section to certain criteria. We return true if any of the conditions is true
		if ( empty( $results['exclude'] ) ) {
			$run_template = false;

			foreach ( $results['include'] as $result ) {
				if ( $result['condition_met'] ) {
					$run_template = true;
					break;
				}
			}
		}

		// If include is empty: user wants to insert the section to all pages, except certain criteria. We return true if all of the conditions are true
		elseif ( empty( $results['include'] ) ) {
			$run_template = true;

			foreach ( $results['exclude'] as $result ) {
				if ( $result['condition_met'] ) {
					$run_template = false;
					break;
				}
			}
		}

		// If both include and exclude are set, we return true if any of the include conditions is true and none of the exclude conditions is true
		else {
			$run_template = false;

			foreach ( $results['include'] as $result ) {
				if ( $result['condition_met'] ) {
					$run_template = true;
					break;
				}
			}

			foreach ( $results['exclude'] as $result ) {
				if ( $result['condition_met'] ) {
					$run_template = false;
					break;
				}
			}
		}

		return $run_template;
	}
}
