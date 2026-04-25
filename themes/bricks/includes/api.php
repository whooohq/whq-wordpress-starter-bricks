<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Api {

	const API_NAMESPACE = 'bricks/v1';

	/**
	 * WordPress REST API help docs:
	 *
	 * https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'rest_api_init_custom_endpoints' ] );
	}

	/**
	 * Custom REST API endpoints
	 */
	public function rest_api_init_custom_endpoints() {
		// Server-side render (SSR) for builder elements via window.fetch API requests
		register_rest_route(
			self::API_NAMESPACE,
			'render_element',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'render_element' ],
				'permission_callback' => [ $this, 'render_element_permissions_check' ],
			]
		);

		// Get all templates data (templates, authors, bundles, tags etc.)
		register_rest_route(
			self::API_NAMESPACE,
			'/get-templates-data/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_templates_data' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/get-templates/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_templates' ],
				'permission_callback' => '__return_true',
			]
		);

		// Get individual template by ID
		register_rest_route(
			self::API_NAMESPACE,
			'/get-templates/(?P<args>[a-zA-Z0-9-=&]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_templates' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'args' => [
						'required' => true
					],
				],
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/get-template-authors/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_template_authors' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/get-template-bundles/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_template_bundles' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/get-template-tags/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_template_tags' ],
				'permission_callback' => '__return_true',
			]
		);

		/**
		 * Query loop: Infinite scroll
		 *
		 * @since 1.5
		 */
		register_rest_route(
			self::API_NAMESPACE,
			'load_query_page',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'render_query_page' ],
				'permission_callback' => [ $this, 'render_query_page_permissions_check' ],
			]
		);

		/**
		 * Ajax Popup
		 *
		 * @since 1.9.4
		 */
		register_rest_route(
			self::API_NAMESPACE,
			'load_popup_content',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'render_popup_content' ],
				'permission_callback' => [ $this, 'render_popup_content_permissions_check' ],
			]
		);

		/**
		 * Query loop: Query result
		 *
		 * For load more, AJAX pagination, sort, filter, live search.
		 *
		 * @since 1.9.6
		 */
		register_rest_route(
			self::API_NAMESPACE,
			'query_result',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'render_query_result' ],
				'permission_callback' => [ $this, 'render_query_result_permissions_check' ],
			]
		);
	}

	/**
	 * Return element HTML retrieved via Fetch API
	 *
	 * @since 1.5
	 */
	public static function render_element( $request ) {
		$data             = $request->get_json_params();
		$post_id          = $data['postId'] ?? false;
		$element          = $data['element'] ?? [];
		$element_name     = $element['name'] ?? '';
		$element_settings = $element['settings'] ?? '';

		if ( $post_id ) {
			Database::set_page_data( $post_id );
		}

		// Include WooCommerce frontend classes and hooks to enable the WooCommerce element preview inside the builder (since 1.5)
		if ( Woocommerce::$is_active ) {
			WC()->frontend_includes();

			Woocommerce_Helpers::maybe_load_cart();
		}

		// Get rendered element HTML
		$html = Ajax::render_element( $data );

		// Prepare response
		$response = [ 'html' => $html ];

		// Template element (send template elements to run template element scripts on the canvas)
		if ( $element_name === 'template' ) {
			$template_id = $element_settings['template'] ?? false;
			if ( $template_id ) {
				$additional_data = Element_Template::get_builder_call_additional_data( $template_id );
				$response        = array_merge( $response, $additional_data );
			}
		}

		return [ 'data' => $response ];
	}

	/**
	 * Element render permission check
	 *
	 * @since 1.5
	 */
	public function render_element_permissions_check( $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['postId'] ) || empty( $data['element'] ) || empty( $data['nonce'] ) ) {
			return new \WP_Error( 'bricks_api_missing', __( 'Missing parameters' ), [ 'status' => 400 ] );
		}

		// Return: Current user can not access builder
		if ( Capabilities::current_user_has_no_access() ) {
			return new \WP_Error( 'rest_current_user_can_not_use_builder', __( 'Permission error' ), [ 'status' => 403 ] );
		}

		return true;
	}

	/**
	 * Return all templates data in one call (templates, authors, bundles, tags, theme style)
	 *
	 * @param  array $data
	 * @return array
	 *
	 * @since 1.0
	 */
	public function get_templates_data( $data ) {
		$templates_args = $data['args'] ?? [];

		// STEP: Get templates metadata or all data
		$templates = $this->get_templates( $templates_args );

		// STEP: Check for template error
		if ( isset( $templates['error'] ) ) {
			return $templates;
		}

		$theme_styles   = get_option( BRICKS_DB_THEME_STYLES, false );
		$global_classes = get_option( BRICKS_DB_GLOBAL_CLASSES, [] );

		// STEP: Add theme style to template data to import when inserting a template (@since 1.3.2)
		foreach ( $templates as $index => $template ) {
			$theme_style_id = Theme_Styles::set_active_style( $template['id'], true );
			$theme_style    = $theme_styles[ $theme_style_id ] ?? false;

			if ( $theme_style ) {
				// Remove theme style conditions
				if ( isset( $theme_style['settings']['conditions'] ) ) {
					unset( $theme_style['settings']['conditions'] );
				}

				$theme_style['id']                 = $theme_style_id;
				$templates[ $index ]['themeStyle'] = $theme_style;
			}

			/**
			 * Loop over all template elements to add 'global_classes' data to remote template data
			 *
			 * To import global classes when importing remote template locally.
			 *
			 * @since 1.5
			 */
			if ( count( $global_classes ) ) {
				$template_classes  = [];
				$template_elements = [];

				if ( ! empty( $template['content'] ) && is_array( $template['content'] ) ) {
					$template_elements = $template['content'];
				} elseif ( ! empty( $template['header'] ) && is_array( $template['header'] ) ) {
					$template_elements = $template['header'];
				} elseif ( ! empty( $template['footer'] ) && is_array( $template['footer'] ) ) {
					$template_elements = $template['footer'];
				}

				foreach ( $template_elements as $element ) {
					if ( ! empty( $element['settings']['_cssGlobalClasses'] ) ) {
						$template_classes = array_unique( array_merge( $template_classes, $element['settings']['_cssGlobalClasses'] ) );
					}
				}

				if ( count( $template_classes ) ) {
					$templates[ $index ]['global_classes'] = [];

					foreach ( $template_classes as $template_class ) {
						foreach ( $global_classes as $global_class ) {
							if ( $global_class['id'] === $template_class ) {
								$templates[ $index ]['global_classes'][] = $global_class;
							}
						}
					}
				}
			}
		}

		// Return all templates data
		$templates_data = [
			'timestamp' => current_time( 'timestamp' ),
			'date'      => current_time( get_option( 'date_format' ) . ' (' . get_option( 'time_format' ) . ')' ),
			'templates' => $templates,
			'authors'   => Templates::get_template_authors(),
			'bundles'   => Templates::get_template_bundles(),
			'tags'      => Templates::get_template_tags(),
			'get'       => $_GET, // Pass URL params to perform additional checks (e.g. 'password' as license key, etc.) @since 1.5.5
		];

		$templates_data = apply_filters( 'bricks/api/get_templates_data', $templates_data );

		// Remove 'get' data (to avoid storing it in database)
		unset( $templates_data['get'] );

		return $templates_data;
	}

	/**
	 * Return templates array OR specific template by array index
	 *
	 * @since 1.0
	 *
	 * @param  array $data
	 *
	 * @return array
	 */
	public function get_templates( $data ) {
		$parameters         = $_GET;
		$templates_response = Templates::can_get_templates( $parameters );

		// Check for templates error (no site/password etc. provided)
		if ( isset( $templates_response['error'] ) ) {
			return $templates_response;
		}

		$templates_args = $data['args'] ?? [];

		// Merge $parameters with $templates_response args
		$templates_args = array_merge( $templates_args, $templates_response );

		$templates = Templates::get_templates( $templates_args );

		return $templates;
	}

	/**
	 * Get API endpoint
	 *
	 * Use /api to get Bricks Community Templates
	 * Default: Use /wp-json (= default WP REST API prefix)
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $base_url Base URL.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public static function get_endpoint( $endpoint = 'get-templates', $base_url = BRICKS_REMOTE_URL ) {
		$api_prefix = $base_url === BRICKS_REMOTE_URL ? 'api' : rest_get_url_prefix();

		return trailingslashit( $base_url ) . trailingslashit( $api_prefix ) . trailingslashit( self::API_NAMESPACE ) . $endpoint;
	}

	/**
	 * Get the Bricks REST API url
	 *
	 * @since 1.5
	 *
	 * @return string
	 */
	public static function get_rest_api_url() {
		return trailingslashit( get_rest_url( null, '/' . self::API_NAMESPACE ) );
	}

	/**
	 * Check if current endpoint is Bricks API endpoint
	 *
	 * @param string $endpoint E.g. 'render_element' or 'load_query_page' for our infinite scroll.
	 *
	 * @since 1.8.1
	 *
	 * @return bool
	 */
	public static function is_current_endpoint( $endpoint ) {
		if ( ! $endpoint ) {
			return false;
		}

		global $wp;

		// REST route (example: /bricks/v1/load_query_page)
		$current_rest_route = isset( $wp->query_vars['rest_route'] ) ? $wp->query_vars['rest_route'] : '';

		if ( ! $current_rest_route ) {
			return false;
		}

		// Example: /bricks/v1/load_query_page
		$bricks_rest_route = '/' . self::API_NAMESPACE . '/' . $endpoint;

		return $current_rest_route === $bricks_rest_route;
	}

	/**
	 * Get template authors
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_template_authors() {
		return Templates::get_template_authors();
	}

	/**
	 * Get template bundles
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_template_bundles() {
		return Templates::get_template_bundles();
	}

	/**
	 * Get template tags
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_template_tags() {
		return Templates::get_template_tags();
	}

	/**
	 * Get news feed
	 *
	 * NOTE: Not in use.
	 *
	 * @return array
	 */
	public static function get_feed() {
		$remote_base_url = BRICKS_REMOTE_URL;
		$feed_url        = trailingslashit( $remote_base_url ) . trailingslashit( rest_get_url_prefix() ) . trailingslashit( self::API_NAMESPACE ) . trailingslashit( 'feed' );

		$response = Helpers::remote_get( $feed_url );

		if ( is_wp_error( $response ) ) {
			return [];
		} else {
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}
	}

	/**
	 * Query loop: Infinite scroll permissions callback
	 *
	 * @since 1.5
	 */
	public function render_query_page_permissions_check( $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['queryElementId'] ) || empty( $data['nonce'] ) || empty( $data['page'] ) ) {
			return new \WP_Error( 'bricks_api_missing', __( 'Missing parameters' ), [ 'status' => 400 ] );
		}

		$result = wp_verify_nonce( $data['nonce'], 'bricks-nonce' );

		if ( $result === false ) {
			return new \WP_Error( 'rest_cookie_invalid_nonce', __( 'Bricks cookie check failed' ), [ 'status' => 403 ] );
		}

		return true;
	}

	/**
	 * Query loop: Infinite scroll callback
	 *
	 * @since 1.5
	 */
	public function render_query_page( $request ) {
		$request_data = $request->get_json_params();

		$query_element_id = $request_data['queryElementId'];
		$post_id          = $request_data['postId'];
		$page             = $request_data['page'];
		$query_vars       = json_decode( $request_data['queryVars'], true );

		// Set post_id for use in prepare_query_vars_from_settings
		Database::$page_data['preview_or_post_id'] = $post_id;

		$data = Helpers::get_element_data( $post_id, $query_element_id );

		if ( empty( $data['elements'] ) ) {
			return rest_ensure_response(
				[
					'html'   => '',
					'styles' => '',
					'error'  => 'Template data not found',
				]
			);
		}

		// STEP: Build the flat list index
		$indexed_elements = [];

		foreach ( $data['elements'] as $element ) {
			$indexed_elements[ $element['id'] ] = $element;
		}

		if ( ! array_key_exists( $query_element_id, $indexed_elements ) ) {
			return rest_ensure_response(
				[
					'html'   => '',
					'styles' => '',
					'error'  => 'Element not found',
				]
			);
		}

		// STEP: Set the query element pagination
		$query_element = $indexed_elements[ $query_element_id ];

		/**
		 * STEP: Use hook to merge query_vars from the request instead of '_merge_vars' (@pre 1.9.5)
		 *
		 * Reason: _merge_vars not in use
		 * - not reliable as it is using wp_parse_args(), only merge if the key is not set
		 * - logic only occurs in post query, term and user not supported
		 *
		 * @since 1.9.5
		 */
		$object_type = $query_element['settings']['query']['objectType'] ?? 'post';

		if ( in_array( $object_type, [ 'term', 'user' ] ) ) {
			// Don't use request's offset, Term and User query offset should be calculated Query::inside prepare_query_vars_from_settings()
			unset( $query_vars['offset'] );
		}

		// Set the page number which comes from the request
		$query_vars['paged'] = $page;

		// Set the page number - This is needed for term query
		$query_element['settings']['query']['paged'] = $page;

		add_filter(
			"bricks/{$object_type}s/query_vars",
			function( $vars, $settings, $element_id ) use ( $query_element_id, $query_vars ) {
				if ( $element_id !== $query_element_id ) {
					return $vars;
				}

				// Merge the query vars
				$merged_query_vars = Query::merge_query_vars( $vars, $query_vars );

				return $merged_query_vars;
			},
			10,
			3
		);

		// Remove the parent
		if ( ! empty( $query_element['parent'] ) ) {
			$query_element['parent']       = 0;
			$query_element['_noRootClass'] = 1;
		}

		// STEP: Get the query loop elements (main and children)
		$loop_elements = [ $query_element ];

		$children = $query_element['children'];

		while ( ! empty( $children ) ) {
			$child_id = array_shift( $children );

			if ( array_key_exists( $child_id, $indexed_elements ) ) {
				$loop_elements[] = $indexed_elements[ $child_id ];

				if ( ! empty( $indexed_elements[ $child_id ]['children'] ) ) {
					$children = array_merge( $children, $indexed_elements[ $child_id ]['children'] );
				}
			}
		}

		// Set Theme Styles (for correct preview of query loop nodes)
		Theme_Styles::load_set_styles( $post_id );

		// STEP: Generate the styles again to catch dynamic data changes (eg. background-image)
		$scroll_query_page_id = "scroll_{$query_element_id}_{$page}";

		Assets::generate_css_from_elements( $loop_elements, $scroll_query_page_id );

		$inline_css = ! empty( Assets::$inline_css[ $scroll_query_page_id ] ) ? Assets::$inline_css[ $scroll_query_page_id ] : '';

		// STEP: Render the element after styles are generated as data-query-loop-index might be inserted through hook in Assets class (@since 1.7.2)
		$html = Frontend::render_data( $loop_elements );

		// Add popup HTML plus styles (@since 1.7.1)
		$popups = Popups::$looping_popup_html;

		// STEP: Add dynamic data styles after render_data() to catch dynamic data changes (eg. background-image) (@since 1.8.2)
		$inline_css .= Assets::$inline_css_dynamic_data;

		$styles = ! empty( $inline_css ) ? "\n<style>/* INFINITE SCROLL CSS */\n{$inline_css}</style>\n" : '';

		return rest_ensure_response(
			[
				'html'   => $html,
				'styles' => $styles,
				'popups' => $popups,
			]
		);
	}

	/**
	 * AJAX popup callback
	 *
	 * @since 1.9.4
	 */
	public function render_popup_content( $request ) {
		$request_data = $request->get_json_params();

		$post_id            = $request_data['postId'] ?? false;
		$popup_id           = $request_data['popupId'] ?? false;
		$popup_loop_id      = $request_data['popupLoopId'] ?? false;
		$popup_context_id   = $request_data['popupContextId'] ?? false;
		$popup_context_type = $request_data['popupContextType'] ?? false;
		$poup_is_looping    = $request_data['isLooping'] ?? false;
		$query_element_id   = $request_data['queryElementId'] ?? false;

		/**
		 * Default: Current context (query element ID)
		 * Re-run the query loop
		 * Inaccurate, might be empty if inside a nested loop or repeater
		 */
		if ( $query_element_id ) {
			// Set page_data via filter
			add_filter(
				'bricks/builder/data_post_id',
				function( $id ) use ( $post_id ) {
					return $post_id;
				}
			);

			// Preview ID or post ID is very important in popup as it's a template, so we need to set separately
			Database::$page_data['preview_or_post_id'] = $post_id;

			// This popup inside a loop
			$data = Helpers::get_element_data( $post_id, $query_element_id );

			if ( empty( $data['elements'] ) ) {
				return rest_ensure_response(
					[
						'html'   => '',
						'styles' => '',
						'popups' => [],
						'error'  => esc_html__( 'Popup data not found', 'bricks' ),
					]
				);
			}

			// STEP: Build the flat list index
			$indexed_elements = [];

			foreach ( $data['elements'] as $element ) {
				$indexed_elements[ $element['id'] ] = $element;
			}

			if ( ! array_key_exists( $query_element_id, $indexed_elements ) ) {
				return rest_ensure_response(
					[
						'html'   => '',
						'styles' => '',
						'popups' => [],
						'error'  => esc_html__( 'Element not found', 'bricks' ),
					]
				);
			}

			// STEP: Set the query element pagination
			$query_element = $indexed_elements[ $query_element_id ];

			// Get the target object ID from popupId string, separated by ':'
			if ( $popup_loop_id ) {
				$popup_id_parts = explode( ':', $popup_loop_id );

				if ( count( $popup_id_parts ) === 4 ) {
					$query_object_type = $popup_id_parts[2];
					$query_object_id   = $popup_id_parts[3];
					$new_popup_loop_id = $popup_loop_id;

					switch ( $query_object_type ) {
						case 'post':
							$query_element['settings']['query']['p'] = $query_object_id;
							$new_popup_loop_id                       = "{$query_element_id}:0:{$query_object_type}:{$query_object_id}";
							break;
						case 'term':
							$query_element['settings']['query']['include'] = $query_object_id;
							$new_popup_loop_id                             = "{$query_element_id}:0:{$query_object_type}:{$query_object_id}";
							break;
						case 'user':
							$query_element['settings']['query']['include'] = $query_object_id;
							$new_popup_loop_id                             = "{$query_element_id}:0:{$query_object_type}:{$query_object_id}";
							break;
						default:
						case 'unknown':
							// Unable to detect query object type, this is inside repeater... query all ?
							// $query_element['settings']['query']['post_per_page'] = -1;
							// Return error and indicate not supported
							return rest_ensure_response(
								[
									'html'   => '',
									'styles' => '',
									'popups' => [],
									'error'  => esc_html__( 'Query object type not supported', 'bricks' ),
								]
							);

							break;
					}
				}
			}

			// Remove the parent
			if ( ! empty( $query_element['parent'] ) ) {
				$query_element['parent']       = 0;
				$query_element['_noRootClass'] = 1;
			}

			// STEP: Get the query loop elements (main and children)
			$loop_elements = [ $query_element ];

			$children = $query_element['children'];

			while ( ! empty( $children ) ) {
				$child_id = array_shift( $children );

				if ( array_key_exists( $child_id, $indexed_elements ) ) {
					$loop_elements[] = $indexed_elements[ $child_id ];

					if ( ! empty( $indexed_elements[ $child_id ]['children'] ) ) {
						$children = array_merge( $children, $indexed_elements[ $child_id ]['children'] );
					}
				}
			}

			// Set Theme Styles (for correct preview of query loop nodes)
			Theme_Styles::load_set_styles( $post_id );

			// STEP: Generate the styles again to catch dynamic data changes (eg. background-image)
			$looping_popup_id = "popup_{$query_element_id}_{$post_id}";

			Assets::generate_css_from_elements( $loop_elements, $looping_popup_id );

			$inline_css = ! empty( Assets::$inline_css[ $looping_popup_id ] ) ? Assets::$inline_css[ $looping_popup_id ] : '';

			Frontend::render_data( $loop_elements );

			$popups = Popups::$ajax_popup_contents;

			// Use $new_popup_loop_id to get popup content
			$popup_content = $popups[ $new_popup_loop_id ][ $popup_id ]['html'] ?? '';

			// STEP: Add dynamic data styles after render_data() to catch dynamic data changes (eg. background-image)
			$inline_css .= Assets::$inline_css_dynamic_data;

			$styles = ! empty( $inline_css ) ? "\n<style>/*AJAX POPUP CSS */\n{$inline_css}</style>\n" : '';
		}

		/**
		 * Use user defined context (popupContextId)
		 * More reliable than query_element_id way
		 */
		else {
			// Set page_data via filter
			add_filter(
				'bricks/builder/data_post_id',
				function( $id ) use ( $post_id, $popup_context_id ) {
					// Use popup_context_id if not false
					return $popup_context_id ? $popup_context_id : $post_id;
				}
			);

			// Preview or post id is very important in popup as it's a template, so we need to set separately
			Database::$page_data['preview_or_post_id'] = $popup_context_id ? $popup_context_id : $post_id;

			if ( $post_id ) {
				global $wp_query;
				global $post;
				$post = get_post( $post_id );
				setup_postdata( $post );

				/**
				 * Set necessary global variables so we can use get_queried_object(), get_the_ID() etc.
				 */
				switch ( $popup_context_type ) {
					case 'post':
						if ( $popup_context_id ) {
							// Override the global post
							$post = get_post( $popup_context_id );
							setup_postdata( $post );
						}

						$wp_query->queried_object    = $post;
						$wp_query->queried_object_id = $post->ID;
						$wp_query->is_singular       = true;
						break;

					case 'term':
						$term                        = get_term( $popup_context_id ? $popup_context_id : $post_id );
						$wp_query->queried_object    = get_term( $popup_context_id ? $popup_context_id : $post_id );
						$wp_query->queried_object_id = $term->term_id;
						$wp_query->is_tax            = true;
						break;

					case 'user':
						$user                        = get_user_by( 'id', $popup_context_id ? $popup_context_id : $post_id );
						$wp_query->queried_object    = $user;
						$wp_query->queried_object_id = $user->ID;
						$wp_query->is_author         = true;
						break;
				}
			}

			if ( $poup_is_looping ) {
				// Simulate Query::is_looping() as we skipped the query loop
				add_filter( 'bricks/query/force_is_looping', '__return_true' );

				// Simulate Query::get_loop_index() as we skipped the query loop
				add_filter(
					'bricks/query/force_loop_index',
					function( $index ) {
						return 0;
					}
				);
			}

			// Get popup via popup ID
			$elements = Database::get_data( $popup_id );

			if ( empty( $elements ) ) {
				return rest_ensure_response(
					[
						'html'   => '',
						'styles' => '',
						'popups' => [],
						'error'  => esc_html__( 'Popup data not found', 'bricks' ),
					]
				);
			}

			// Set active templates
			Database::set_active_templates( $post_id );

			// Set Theme Styles (for correct preview of query loop nodes)
			Theme_Styles::load_set_styles( $post_id );

			// STEP: Generate the styles again to catch dynamic data changes (eg. background-image)
			$popup_page_id = "popup_{$post_id}";

			Assets::generate_css_from_elements( $elements, $popup_page_id );

			$inline_css = Assets::$inline_css[ $popup_page_id ] ?? '';

			$popup_content = Frontend::render_data( $elements, 'popup' );

			$inline_css .= Assets::$inline_css_dynamic_data;

			$styles = ! empty( $inline_css ) ? "\n<style>/* AJAX POPUP CSS */\n{$inline_css}</style>\n" : '';
		}

		$looping_popup_html = [];

		if ( ! empty( Popups::$looping_ajax_popup_ids ) ) {
			/**
			 * In certain scenario, some popup templates inserted inside a query loop which is inside another AJAX popup template
			 * Generate each looping AJAX popup html holder, we could use this to add into the DOM if it's not there yet
			 */
			foreach ( Popups::$looping_ajax_popup_ids as $looping_popup_id ) {
				$html                                    = Popups::generate_popup_html( $looping_popup_id );
				$looping_popup_html[ $looping_popup_id ] = $html;
			}
		}

		return rest_ensure_response(
			[
				'html'   => $popup_content,
				'styles' => $styles,
				'popups' => $looping_popup_html,
			]
		);
	}

	/**
	 * Ajax Popup permissions callback
	 *
	 * @since 1.9.4
	 */
	public function render_popup_content_permissions_check( $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['popupId'] ) || empty( $data['nonce'] ) ) {
			return new \WP_Error( 'bricks_api_missing', __( 'Missing parameters' ), [ 'status' => 400 ] );
		}

		$result = wp_verify_nonce( $data['nonce'], 'bricks-nonce' );

		if ( $result === false ) {
			return new \WP_Error( 'rest_cookie_invalid_nonce', __( 'Bricks cookie check failed' ), [ 'status' => 403 ] );
		}

		return true;
	}

	/**
	 * Similar like render_query_page() but for AJAX query result
	 *
	 * For load more, AJAX pagination, infinite scroll, sort, filter, live search.
	 *
	 * @since 1.9.6
	 */
	public function render_query_result( $request ) {
		$request_data = $request->get_json_params();

		$query_element_id = $request_data['queryElementId'];
		$post_id          = $request_data['postId'];
		$filters          = $request_data['filters'] ?? [];
		$query_vars       = $request_data['queryArgs'] ?? [];
		$page_filters     = $request_data['pageFilters'] ?? [];
		$base_url         = $request_data['baseUrl'] ?? '';
		$page             = isset( $query_vars['paged'] ) ? sanitize_text_field( $query_vars['paged'] ) : 1;

		// Set post_id for use in prepare_query_vars_from_settings
		Database::$page_data['preview_or_post_id'] = $post_id;

		$data = Helpers::get_element_data( $post_id, $query_element_id );

		if ( empty( $data['elements'] ) ) {
			return rest_ensure_response(
				[
					'html'   => '',
					'styles' => '',
					'error'  => 'Template data not found',
				]
			);
		}

		// STEP: Build the flat list index
		$indexed_elements = [];

		foreach ( $data['elements'] as $element ) {
			$indexed_elements[ $element['id'] ] = $element;
		}

		if ( ! array_key_exists( $query_element_id, $indexed_elements ) ) {
			return rest_ensure_response(
				[
					'html'   => '',
					'styles' => '',
					'error'  => 'Element not found',
				]
			);
		}

		// STEP: Set the query element pagination
		$query_element = $indexed_elements[ $query_element_id ];

		// TODO: Check if the query_vars are valid, sanitize and validate

		// Check if the $query_element objectType is 'post' or '' (empty)
		// Beta only support post query
		$query_object_type = isset( $query_element['settings']['query']['objectType'] ) ? sanitize_text_field( $query_element['settings']['query']['objectType'] ) : 'post';

		if ( ! in_array( $query_object_type, [ 'post' ] ) ) {
			return rest_ensure_response(
				[
					'html'   => '',
					'styles' => '',
					'error'  => 'Query object type not supported',
				]
			);
		}

		// STEP: set page filters
		Query_Filters::$page_filters = $page_filters;

		// STEP: set paged query var if exists
		$query_element['settings']['query']['paged'] = $page;

		// STEP: Merge the query vars via filter, so we can override WooCommerce query vars, queryEditor query vars, etc.
		add_filter(
			"bricks/{$query_object_type}s/query_vars",
			function( $vars, $settings, $element_id ) use ( $query_vars, $query_element_id, &$query_vars_before_merge ) {
				if ( $element_id !== $query_element_id ) {
					return $vars;
				}

				// STEP: save the query vars before merge
				Query_Filters::$query_vars_before_merge[ $query_element_id ] = $vars;

				// STEP: merge the query vars
				return Query::merge_query_vars( $vars, $query_vars );
			},
			11,
			3
		);

		// Remove the parent
		if ( ! empty( $query_element['parent'] ) ) {
			$query_element['parent']       = 0;
			$query_element['_noRootClass'] = 1;
		}

		// STEP: Get the query loop elements (main and children)
		$loop_elements = [ $query_element ];

		$children = $query_element['children'];

		while ( ! empty( $children ) ) {
			$child_id = array_shift( $children );

			if ( array_key_exists( $child_id, $indexed_elements ) ) {
				$loop_elements[] = $indexed_elements[ $child_id ];

				if ( ! empty( $indexed_elements[ $child_id ]['children'] ) ) {
					$children = array_merge( $children, $indexed_elements[ $child_id ]['children'] );
				}
			}
		}

		// Set Theme Styles (for correct preview of query loop nodes)
		Theme_Styles::load_set_styles( $post_id );

		// STEP: Generate the styles again to catch dynamic data changes (eg. background-image)
		$query_identifier = "ajax_query_{$query_element_id}";

		Assets::generate_css_from_elements( $loop_elements, $query_identifier );

		$inline_css = ! empty( Assets::$inline_css[ $query_identifier ] ) ? Assets::$inline_css[ $query_identifier ] : '';

		// STEP: Render the element after styles are generated as data-query-loop-index might be inserted through hook in Assets class
		$html = Frontend::render_data( $loop_elements );

		// Add popup HTML plus styles
		$popups = Popups::$looping_popup_html;

		// STEP: Add dynamic data styles after render_data() to catch dynamic data changes (eg. background-image)
		$inline_css .= Assets::$inline_css_dynamic_data;

		$styles = ! empty( $inline_css ) ? "\n<style>/* AJAX QUERY RESULT CSS */\n{$inline_css}</style>\n" : '';

		// STEP: Latest pagination HTML
		$pagination_element = [
			'name'     => 'pagination',
			'settings' => [
				'queryId' => $query_element_id,
				'ajax'    => true,
			],
		];

		if ( ! empty( $base_url ) ) {
			add_filter(
				'bricks/paginate_links_args',
				function( $args ) use ( $base_url, $page ) {
					$args['base']    = $base_url . '%_%';
					$args['current'] = $page;
					return $args;
				}
			);
		}

		$pagination_html = Frontend::render_element( $pagination_element );

		// STEP: Query data
		$query_data = Query::get_query_by_element_id( $query_element_id );

		// Remove settings, query_result, loop_index, loop_object, is_looping properties
		unset( $query_data->settings );
		unset( $query_data->query_result );
		unset( $query_data->loop_index );
		unset( $query_data->loop_object );
		unset( $query_data->is_looping );

		$updated_filters = Query_Filters::get_updated_filters( $filters, $post_id, $query_data );

		return rest_ensure_response(
			[
				'html'            => $html,
				'styles'          => $styles,
				'popups'          => $popups,
				'pagination'      => $pagination_html,
				'updated_filters' => $updated_filters,
				'updated_query'   => $query_data,
				// 'page_filters'    => Query_Filters::$page_filters,
				// 'filter_object_ids' => Query_Filters::$filter_object_ids,
				// 'active_filters'  => Query_Filters::$active_filters,
			]
		);
	}

	/**
	 * Query loop: Query result permissions callback
	 *
	 * @since 1.9.6
	 */
	public function render_query_result_permissions_check( $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['queryElementId'] ) || empty( $data['nonce'] ) ) {
			return new \WP_Error( 'bricks_api_missing', __( 'Missing parameters' ), [ 'status' => 400 ] );
		}

		$result = wp_verify_nonce( $data['nonce'], 'bricks-nonce' );

		if ( $result === false ) {
			return new \WP_Error( 'rest_cookie_invalid_nonce', __( 'Bricks cookie check failed' ), [ 'status' => 403 ] );
		}

		return true;
	}
}
