<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Frontend {
	public static $area = 'content';

	/**
	 * Elements requested for rendering
	 *
	 * key: ID
	 * value: element data
	 */
	public static $elements = [];

	/**
	 * Live search results selectors
	 *
	 * key: live search ID
	 * value: live search results CSS selector
	 *
	 * @since 1.9.6
	 */
	public static $live_search_wrapper_selectors = [];

	public function __construct() {
		add_action( 'wp_head', [ $this, 'add_seo_meta_tags' ], 1 );

		add_filter( 'document_title_parts', [ $this, 'set_seo_document_title' ] );

		add_filter( 'wp_get_attachment_image_attributes', [ $this, 'set_image_attributes' ], 10, 3 );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_inline_css' ], 11 );
		add_action( 'wp_footer', [ $this, 'enqueue_footer_inline_css' ] );

		add_action( 'bricks_after_site_wrapper', [ $this, 'one_page_navigation_wrapper' ] );

		// Load custom header body script (for analytics) only on the frontend
		add_action( 'wp_head', [ $this, 'add_header_scripts' ] );
		add_action( 'wp_head', [ $this, 'add_open_graph_meta_tags' ], 99999 );
		add_action( 'bricks_body', [ $this, 'add_body_header_scripts' ] );

		// Change the priority to 21 to load the custom scripts after the default Bricks scripts in the footer (@since 1.5)
		// @see core: add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
		add_action( 'wp_footer', [ $this, 'add_body_footer_scripts' ], 21 );

		add_action( 'template_redirect', [ $this, 'template_redirect' ] );

		add_action( 'bricks_body', [ $this, 'add_skip_link' ] );

		add_action( 'bricks_body', [ $this, 'remove_wp_hooks' ] );

		add_action( 'render_header', [ $this, 'render_header' ] );
		add_action( 'render_footer', [ $this, 'render_footer' ] );
	}

	/**
	 * Add header scripts
	 *
	 * Do not add template JS (we only want to provide content)
	 *
	 * @since 1.0
	 */
	public function add_header_scripts() {
		$header_scripts = '';

		// Global settings scripts
		if ( ! empty( Database::$global_settings['customScriptsHeader'] ) ) {
			$header_scripts .= stripslashes_deep( Database::$global_settings['customScriptsHeader'] ) . PHP_EOL;
		}

		// Page settings header scripts (@since 1.4)
		$header_scripts .= Assets::get_page_settings_scripts( 'customScriptsHeader' );

		echo $header_scripts;
	}

	/**
	 * Page settings: Add meta description, keywords and robots
	 */
	public function add_seo_meta_tags() {
		// NOTE: Undocumented
		$disable_seo = apply_filters( 'bricks/frontend/disable_seo', ! empty( Database::$global_settings['disableSeo'] ) );

		if ( $disable_seo ) {
			return;
		}

		$template_id = Database::$active_templates['content'];

		$template_settings = get_post_meta( $template_id, BRICKS_DB_PAGE_SETTINGS, true );

		$post_id = is_home() ? get_option( 'page_for_posts' ) : get_the_ID();

		if ( $template_id !== $post_id ) {
			$page_settings = get_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, true );
		}

		$seo_tags = [
			'metaDescription' => 'description',
			'metaKeywords'    => 'keywords',
			'metaRobots'      => 'robots',
		];

		foreach ( $seo_tags as $meta_key => $name ) {
			// Page settings preceeds Template settings
			$meta_value = ! empty( $page_settings[ $meta_key ] ) ? $page_settings[ $meta_key ] : ( ! empty( $template_settings[ $meta_key ] ) ? $template_settings[ $meta_key ] : false );

			if ( empty( $meta_value ) ) {
				continue;
			}

			if ( $meta_key == 'metaRobots' ) {
				$meta_value = join( ', ', $meta_value );
			} else {
				$meta_value = bricks_render_dynamic_data( $meta_value, $post_id );
			}

			echo '<meta name="' . $name . '" content="' . esc_attr( $meta_value ) . '">';
		}
	}

	/**
	 * Page settings: Set document title
	 *
	 * @param array $title
	 *
	 * @see https://developer.wordpress.org/reference/hooks/document_title_parts/
	 *
	 * @since 1.6.1
	 */
	public function set_seo_document_title( $title ) {
		// NOTE: Undocumented
		$disable_seo = apply_filters( 'bricks/frontend/disable_seo', ! empty( Database::$global_settings['disableSeo'] ) );

		if ( $disable_seo ) {
			return $title;
		}

		$template_id = Database::$active_templates['content'];

		$template_settings = get_post_meta( $template_id, BRICKS_DB_PAGE_SETTINGS, true );

		$post_id = is_home() ? get_option( 'page_for_posts' ) : get_the_ID();

		if ( $template_id !== $post_id ) {
			$page_settings = get_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, true );
		}

		// Page settings preceeds Template settings
		$meta_value = ! empty( $page_settings['documentTitle'] ) ? $page_settings['documentTitle'] : ( ! empty( $template_settings['documentTitle'] ) ? $template_settings['documentTitle'] : false );

		if ( empty( $meta_value ) ) {
			return $title;
		}

		$meta_value = bricks_render_dynamic_data( $meta_value, $post_id );

		if ( $meta_value ) {
			$title['title'] = $meta_value;
		}

		return $title;
	}

	/**
	 * Add Facebook Open Graph Meta Data
	 *
	 * https://ogp.me
	 *
	 * @since 1.0
	 */
	public function add_open_graph_meta_tags() {
		// NOTE: Undocumented
		$disable_og = apply_filters( 'bricks/frontend/disable_opengraph', ! empty( Database::$global_settings['disableOpenGraph'] ) );

		if ( $disable_og ) {
			return;
		}

		// STEP: Calculate OG settings
		$template_id = Database::$active_templates['content'];

		$template_settings = get_post_meta( $template_id, BRICKS_DB_PAGE_SETTINGS, true );

		$post_id = is_home() ? get_option( 'page_for_posts' ) : get_the_ID();
		$post_id = empty( $post_id ) ? null : $post_id; // Fix PHP notice on Error page

		if ( $template_id !== $post_id && ! empty( $post_id ) ) {
			$page_settings = get_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, true );
		}

		$settings = [];

		$og_tags = [
			'sharingTitle',
			'sharingDescription',
			'sharingImage',
		];

		foreach ( $og_tags as $meta_key ) {
			// Page settings preceeds Template settings
			$settings[ $meta_key ] = ! empty( $page_settings[ $meta_key ] ) ? $page_settings[ $meta_key ] : ( ! empty( $template_settings[ $meta_key ] ) ? $template_settings[ $meta_key ] : false );
		}

		// STEP: Render tags
		$open_graph_meta_tags = [ '<!-- Facebook Open Graph (by Bricks) -->' ];

		$facebook_app_id = isset( Database::$global_settings['facebookAppId'] ) && ! empty( Database::$global_settings['facebookAppId'] ) ? Database::$global_settings['facebookAppId'] : false;

		if ( $facebook_app_id ) {
			$open_graph_meta_tags[] = '<meta property="fb:app_id" content="' . $facebook_app_id . '" />';
		}

		$open_graph_meta_tags[] = '<meta property="og:url" content="' . get_permalink() . '" />';

		// Site Name
		$open_graph_meta_tags[] = '<meta property="og:site_name" content="' . get_bloginfo( 'name' ) . '" />';

		// Title
		if ( ! empty( $settings['sharingTitle'] ) ) {
			$sharing_title = bricks_render_dynamic_data( $settings['sharingTitle'], $post_id );
		} else {
			$sharing_title = get_the_title( $post_id );
		}

		$open_graph_meta_tags[] = '<meta property="og:title" content="' . esc_attr( trim( $sharing_title ) ) . '" />';

		// Description
		if ( ! empty( $settings['sharingDescription'] ) ) {
			$sharing_description = bricks_render_dynamic_data( $settings['sharingDescription'], $post_id );
		} else {
			$sharing_description = $post_id ? get_the_excerpt( $post_id ) : '';
		}

		if ( $sharing_description ) {
			$open_graph_meta_tags[] = '<meta property="og:description" content="' . esc_attr( trim( $sharing_description ) ) . '" />';
		}

		// Image
		$sharing_image     = ! empty( $settings['sharingImage'] ) ? $settings['sharingImage'] : false;
		$sharing_image_url = ! empty( $sharing_image['url'] ) ? $sharing_image['url'] : false;

		if ( $sharing_image ) {
			if ( ! empty( $sharing_image['useDynamicData'] ) ) {
				$images = Integrations\Dynamic_Data\Providers::render_tag( $sharing_image['useDynamicData'], $post_id, 'image' );

				if ( ! empty( $images[0] ) ) {
					$size              = ! empty( $sharing_image['size'] ) ? $sharing_image['size'] : BRICKS_DEFAULT_IMAGE_SIZE;
					$sharing_image_url = is_numeric( $images[0] ) ? wp_get_attachment_image_url( $images[0], $size ) : $images[0];
				}
			} else {
				$sharing_image_url = $sharing_image['url'];
			}
		} elseif ( has_post_thumbnail() ) {
			$sharing_image_url = get_the_post_thumbnail_url( get_the_ID(), 'large' );
		}

		if ( $sharing_image_url ) {
			$open_graph_meta_tags[] = '<meta property="og:image" content="' . esc_url( $sharing_image_url ) . '" />';
		}

		// Type
		if ( is_home() ) {
			$sharing_type = 'blog';
		} elseif ( get_post_type() === 'post' ) {
			$sharing_type = 'article';
		} else {
			$sharing_type = 'website';
		}

		$open_graph_meta_tags[] = '<meta property="og:type" content="' . $sharing_type . '" />';

		echo "\n" . join( "\n", $open_graph_meta_tags ) . "\n";
	}

	/**
	 * Add body header scripts
	 *
	 * NOTE: Do not add template JS (we only want to provide content)
	 *
	 * @since 1.0
	 */
	public function add_body_header_scripts() {
		$body_header_scripts = '';

		// Global settings scripts
		if ( isset( Database::$global_settings['customScriptsBodyHeader'] ) && ! empty( Database::$global_settings['customScriptsBodyHeader'] ) ) {
			$body_header_scripts .= stripslashes_deep( Database::$global_settings['customScriptsBodyHeader'] ) . PHP_EOL;
		}

		// Page settings scripts (@since 1.4)
		$body_header_scripts .= Assets::get_page_settings_scripts( 'customScriptsBodyHeader' );

		echo $body_header_scripts;
	}

	/**
	 * Add body footer scripts
	 *
	 * NOTE: Do not add template JS (only provide content)
	 *
	 * @since 1.0
	 */
	public function add_body_footer_scripts() {
		$body_footer_scripts = '';

		// Global settings scripts
		if ( isset( Database::$global_settings['customScriptsBodyFooter'] ) && ! empty( Database::$global_settings['customScriptsBodyFooter'] ) ) {
			$body_footer_scripts .= stripslashes_deep( Database::$global_settings['customScriptsBodyFooter'] ) . PHP_EOL;
		}

		// Page settings scripts (@since 1.4)
		$body_footer_scripts .= Assets::get_page_settings_scripts( 'customScriptsBodyFooter' );

		echo $body_footer_scripts;
	}

	/**
	 * Enqueue styles and scripts
	 */
	public function enqueue_scripts() {
		if ( is_admin_bar_showing() && Capabilities::current_user_has_full_access() ) {
			// Load admin.min.css to add styles to the quick edit links
			wp_enqueue_style( 'bricks-admin', BRICKS_URL_ASSETS . 'css/admin.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/admin.min.css' ) );
		}

		// No Bricks content: Load default post content styles (post header & content)
		$bricks_data = Helpers::get_bricks_data( get_the_ID(), 'content' );
		if ( ! $bricks_data ) {
			if ( is_search() || get_the_content() || is_singular( 'post' ) ) {
				wp_enqueue_style( 'bricks-default-content', BRICKS_URL_ASSETS . 'css/frontend/content-default.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/frontend/content-default.min.css' ) );
			}
		}

		// Remove .mejs from attachment page
		if ( is_attachment() ) {
			wp_deregister_script( 'wp-mediaelement' );
			wp_deregister_style( 'wp-mediaelement' );
		}

		global $wp;

		$base_url = home_url( $wp->request );

		// Check if the URL contains a paging path (/page/X)
		if ( preg_match( '/\/page\/\d+\/?$/', $base_url, $matches ) ) {
			$paging_path = $matches[0];
			$base_url    = str_replace( $paging_path, '', $base_url );
		}

		$base_url = trailingslashit( $base_url );

		wp_localize_script(
			'bricks-scripts',
			'bricksData',
			[
				'debug'                   => isset( $_GET['debug'] ),
				'locale'                  => get_locale(),
				'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
				'restApiUrl'              => Api::get_rest_api_url(),
				'nonce'                   => wp_create_nonce( 'bricks-nonce' ),
				'formNonce'               => wp_create_nonce( 'bricks-nonce-form' ),
				'wpRestNonce'             => wp_create_nonce( 'wp_rest' ),
				'postId'                  => Database::$page_data['preview_or_post_id'] ?? get_the_ID(),
				'recaptchaIds'            => [],
				'animatedTypingInstances' => [], // To destroy and then re-init TypedJS instances
				'videoInstances'          => [], // To destroy and then re-init Plyr instances
				'splideInstances'         => [], // Necessary to destroy and then reinit SplideJS instances
				'tocbotInstances'         => [], // Necessary to destroy and then reinit Tocbot instances
				'swiperInstances'         => [], // To destroy and then re-init SwiperJS instances
				'queryLoopInstances'      => [], // To hold the query data for infinite scroll + load more
				'interactions'            => [], // Holds all the interactions
				'filterInstances'         => [], // Holds all the filter instances (@since 1.9.6)
				'isotopeInstances'        => [], // Holds all the isotope instances (@since 1.9.6)
				'mapStyles'               => Setup::get_map_styles(),
				'facebookAppId'           => isset( Database::$global_settings['facebookAppId'] ) ? Database::$global_settings['facebookAppId'] : false,
				'headerPosition'          => Database::$header_position,
				'offsetLazyLoad'          => ! empty( Database::$global_settings['offsetLazyLoad'] ) ? Database::$global_settings['offsetLazyLoad'] : 300,
				'baseUrl'                 => $base_url, // @since 1.9.6
				'useQueryFilter'          => Helpers::enabled_query_filters(), // @since 1.9.6
				'pageFilters'             => Query_Filters::$page_filters, // @since 1.9.6
				'facebookAppId'           => Database::$global_settings['facebookAppId'] ?? false,
				'offsetLazyLoad'          => Database::$global_settings['offsetLazyLoad'] ?? 300,
				'headerPosition'          => Database::$header_position,
			]
		);
	}

	/**
	 * Enqueue inline CSS
	 *
	 * @since 1.8.2 using wp_footer instead of wp_enqueue_scripts to get all dynamic data styles & global classes
	 */
	public function enqueue_inline_css() {
		// Dummy style to load after woocommerce.min.css
		wp_register_style( 'bricks-frontend-inline', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'bricks-frontend-inline' );

		// Bricks settings: Smooth scroll (CSS) (@since 1.7.1)
		if ( Database::get_setting( 'smoothScroll' ) ) {
			wp_add_inline_style( 'bricks-frontend-inline', 'html {scroll-behavior: smooth}' );
		}

		// Bricks settings: AJAX Hide View Cart Button (@since 1.9)
		if ( WooCommerce::is_woocommerce_active() && Database::get_setting( 'woocommerceAjaxHideViewCart' ) ) {
			wp_add_inline_style( 'bricks-frontend-inline', '.added_to_cart.wc-forward {display: none}' );
		}

		// CSS loading method: Inline styles (= default)
		if ( Database::get_setting( 'cssLoading' ) !== 'file' ) {
			wp_add_inline_style( 'bricks-frontend-inline', Assets::generate_inline_css() );
		}

		// CSS loading method: External files
		else {
			// Global classes need to be loaded inline
			wp_add_inline_style( 'bricks-frontend-inline', Assets::$inline_css['global_classes'] );
		}

		// Clear global classes inline CSS to avoid adding duplicate classes in enqueue_footer_inline_css function below
		Assets::$inline_css['global_classes'] = '';
	}

	/**
	 * Enqueue inline CSS in wp_footer: Global classes (Template element) & dynamic data
	 *
	 * @since 1.8.2
	 */
	public function enqueue_footer_inline_css() {
		/**
		 * Add global classes in wp_footer
		 *
		 * Clear in enqueue_inline_css function above to avoid adding duplicate classes.
		 *
		 * Generated in Template element and therefore not available in enqueue_inline_css function above.
		 *
		 * @since 1.8.2
		 */
		$global_classes = Assets::$inline_css['global_classes'];

		if ( $global_classes ) {
			wp_register_style( 'bricks-global-classes-inline', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_style( 'bricks-global-classes-inline' );
			wp_add_inline_style( 'bricks-global-classes-inline', $global_classes );
		}

		// Get dynamic data CSS (for AJAX pagination only @since 1.8.2)
		$inline_css_dynamic_data = Assets::$inline_css_dynamic_data;

		if ( $inline_css_dynamic_data ) {
			// Replace for AJAX pagination (see frontend.js #bricks-dynamic-data)
			wp_register_style( 'bricks-dynamic-data', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_style( 'bricks-dynamic-data' );
			wp_add_inline_style( 'bricks-dynamic-data', $inline_css_dynamic_data );
		}
	}

	/**
	 * Get element content wrapper
	 */
	public static function get_content_wrapper( $settings, $fields, $post ) {
		$output = '';

		foreach ( $fields as $index => $field ) {
			if ( ! empty( $field['dynamicData'] ) ) {
				$content = bricks_render_dynamic_data( $field['dynamicData'], $post->ID );

				$content = do_shortcode( $content );

				if ( $content == '' ) {
					continue;
				}

				$tag      = ! empty( $field['tag'] ) ? esc_attr( $field['tag'] ) : 'div';
				$field_id = isset( $field['id'] ) ? $field['id'] : $index;

				$output .= "<{$tag} class=\"dynamic\" data-field-id=\"{$field_id}\">{$content}</{$tag}>";
			}
		}

		return $output;
	}

	/**
	 * Render element recursively
	 *
	 * @param array $element
	 */
	public static function render_element( $element ) {
		$element_name = $element['name'] ?? '';

		if ( ! $element_name ) {
			return;
		}

		$output = '';

		// Check: Get global element settings (skip if AJAX call is coming from builder via 'global_settings_checked')
		$global_settings = ! isset( $element['global_settings_checked'] ) ? Helpers::get_global_element( $element, 'settings' ) : false;

		if ( is_array( $global_settings ) ) {
			$element['settings'] = $global_settings;
		}

		// Init element class (e.g.: new Bricks\Element_Alert( $element ))
		$element_class_name = isset( Elements::$elements[ $element_name ]['class'] ) ? Elements::$elements[ $element_name ]['class'] : $element_name;

		if ( class_exists( $element_class_name ) ) {
			$element['themeStyleSettings'] = Theme_Styles::$active_settings;

			$element_instance = new $element_class_name( $element );
			$element_instance->load();

			// Enqueue element styles/scripts & render element
			ob_start();
			$element_instance->init();
			return ob_get_clean();
		}

		// Element doesn't exist: Show message to user with builder access
		if ( Capabilities::current_user_can_use_builder() ) {
			return sprintf( '<div class="bricks-element-placeholder no-php-class">%s: ' . $element_class_name . '</div>', esc_html__( 'PHP class does not exist', 'bricks' ) );
		}
	}

	/**
	 * Render element 'children' (= nestable element)
	 *
	 * @param array $element_instance Instance of the element.
	 *
	 * @since 1.5
	 */
	public static function render_children( $element_instance ) {
		$element = $element_instance->element;

		/**
		 * BUILDER: Replace children placeholder node with Vue components (in BricksElementPHP.vue)
		 *
		 * If not static builder area && not frontend && not a loop ghost node (loop index: 1, 2, 3, etc.)
		 *
		 * @since 1.7.1
		 */
		if ( ! isset( $element['staticArea'] ) && ! $element_instance->is_frontend && ! Query::get_loop_index() ) {
			return '<div class="brx-nestable-children-placeholder"></div>';
		}

		// FRONTEND: Return children HTML
		$children = ! empty( $element['children'] ) && is_array( $element['children'] ) ? $element['children'] : [];
		$output   = '';

		foreach ( $children as $child_id ) {
			$child = ! empty( self::$elements[ $child_id ] ) ? self::$elements[ $child_id ] : false;

			if ( $child ) {
				$output .= self::render_element( $child ); // Recursive
			}
		}

		return $output;
	}

	/**
	 * Return rendered elements (header/content/footer)
	 *
	 * @param array  $elements Array of Bricks elements.
	 * @param string $area     header/content/footer.
	 *
	 * @since 1.2
	 */
	public static function render_data( $elements = [], $area = 'content' ) {
		if ( ! is_array( $elements ) ) {
			return;
		}

		if ( ! count( $elements ) ) {
			return;
		}

		// NOTE: Undocumented. Useful to remove plugin actions/filters (@since 1.5.4)
		do_action( 'bricks/frontend/before_render_data', $elements, $area );

		self::$elements = [];
		self::$area     = $area;

		// Prepare flat list of elements for recursive calls
		foreach ( $elements as $element ) {
			if ( isset( $element['id'] ) ) {
				self::$elements[ $element['id'] ] = $element;

				/**
				 * Store live search results selectors
				 *
				 * To set element root data attribute 'data-brx-ls-wrapper' to hide live search wrapper on page load (@see container.php:902)
				 *
				 * @since 1.9.6
				 */
				if (
					Helpers::enabled_query_filters() &&
					! empty( $element['settings']['query']['is_live_search'] ) &&
					! empty( $element['settings']['query']['is_live_search_wrapper_selector'] )
				) {
					self::$live_search_wrapper_selectors[ $element['id'] ] = $element['settings']['query']['is_live_search_wrapper_selector'];
				}
			}
		}

		// Generate elements HTML
		$content = '';

		foreach ( $elements as $element ) {
			if ( ! empty( $element['parent'] ) ) {
				continue;
			}

			$content .= self::render_element( $element );
		}

		// NOTE: Undocumented. Useful to re-add plugin actions/filters (@since 1.5.4)
		do_action( 'bricks/frontend/after_render_data', $elements, $area );

		/**
		 * Check: Are we looping a template element
		 *
		 * @since 1.7: Use Query::get_loop_object_type() if check for a looping post, so user custom queries are also supported (@see #862j64bkn)
		 */
		$looping_query_id = Query::is_any_looping();
		$loop_object_type = Query::get_loop_object_type( $looping_query_id );

		$post_id = $loop_object_type === 'post' ? get_the_ID() : Database::$page_data['preview_or_post_id'];

		$post = get_post( $post_id );

		/**
		 * Filter Bricks content (incl. parsing of dynamic data)
		 *
		 * https://academy.bricksbuilder.io/article/filter-bricks-frontend-render_data/
		 *
		 * @since 1.5.4 ($area argument)
		 */
		$content = apply_filters( 'bricks/frontend/render_data', $content, $post, $area );

		self::$elements = [];

		return $content;
	}

	/**
	 * One Page Navigation Wrapper
	 */
	public function one_page_navigation_wrapper() {
		if ( isset( Database::$page_settings['onePageNavigation'] ) ) {
			echo '<ul id="bricks-one-page-navigation"></ul>';
		}
	}

	/**
	 * Lazy load via img data attribute
	 *
	 * https://developer.wordpress.org/reference/hooks/wp_get_attachment_image_attributes/
	 *
	 * @param array        $attr Image attributes.
	 * @param object       $attachment WP_POST object of image.
	 * @param string|array $size Requested image size.
	 *
	 * @return array
	 */
	public function set_image_attributes( $attr, $attachment, $size ) {
		// Disable lazy load for AJAX (builder & frontend) or REST API calls (builder) to ensure assets are always rendered properly
		// REST_REQUEST constant discussion: https://github.com/WP-API/WP-API/issues/926
		if ( bricks_is_ajax_call() || bricks_is_rest_call() ) {
			return $attr;
		}

		// Disable lazy load inside TranslatePress iframe (@since 1.6)
		if ( function_exists( 'trp_is_translation_editor' ) && trp_is_translation_editor( 'preview' ) ) {
			return $attr;
		}

		// Disable images lazy loading in the Product Gallery
		if ( isset( $attr['_brx_disable_lazy_loading'] ) ) {
			unset( $attr['_brx_disable_lazy_loading'] );

			return $attr;
		}

		// Check: Lazy load disabled
		if ( isset( Database::$global_settings['disableLazyLoad'] ) || isset( Database::$page_settings['disableLazyLoad'] ) ) {
			return $attr;
		}

		// Return: To disable lazy loading for all images with attribute loading="eager" (@since 1.6)
		if ( isset( $attr['loading'] ) && $attr['loading'] === 'eager' ) {
			return $attr;
		}

		$attr['class']    = $attr['class'] . ' bricks-lazy-hidden';
		$attr['data-src'] = $attr['src'];

		// Lazy load placeholder: URL-encoded SVG with image dimensions
		$attr['data-type'] = gettype( $size );

		if ( gettype( $size ) === 'string' ) {
			$image_src    = wp_get_attachment_image_src( $attachment->ID, $size );
			$image_width  = $image_src[1];
			$image_height = $image_src[2];
		} else {
			$image_width  = $size[0];
			$image_height = $size[1];
		}

		// Set SVG placeholder to preserve image aspect ratio to prevent browser content reflow when lazy loading the image
		// Encode spaces and use singlequotes instead of double quotes to avoid W3 "space" validator error (@since 1.5.1)
		$attr['src'] = "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20$image_width%20$image_height'%3E%3C/svg%3E";

		// Add data-sizes attribute for lazy load to avoid "sizes" W3 validator error (@since 1.5.1)
		if ( isset( $attr['sizes'] ) ) {
			$attr['data-sizes'] = $attr['sizes'];
			$attr['sizes']      = '';
			unset( $attr['sizes'] );
		}

		if ( isset( $attr['srcset'] ) ) {
			$attr['data-srcset'] = $attr['srcset'];
			$attr['srcset']      = '';
			unset( $attr['srcset'] );
		}

		return $attr;
	}

	/**
	 * Template frontend view: Permanently redirect users without Bricks editing permission to homepage
	 *
	 * Exclude template pages in search engine results.
	 *
	 * Overwrite via 'publicTemplates' setting
	 *
	 * @since 1.9.4: Exclude redirect if maintenance mode activated (to prevent endless redirect)
	 */
	public function template_redirect() {
		if (
			is_singular( BRICKS_DB_TEMPLATE_SLUG ) &&
			! Capabilities::current_user_can_use_builder() &&
			! isset( Database::$global_settings['publicTemplates'] ) &&
			! Maintenance::get_mode() ) {
			wp_safe_redirect( site_url(), 301 );
			die;
		}
	}

	public function add_skip_link() {
		if ( Database::get_setting( 'disableSkipLinks', false ) ) {
			return;
		}

		$template_footer_id = Database::$active_templates['footer'];
		?>
		<a class="skip-link" href="#brx-content" aria-label="<?php esc_html_e( 'Skip to main content', 'bricks' ); ?>"><?php esc_html_e( 'Skip to main content', 'bricks' ); ?></a>

		<?php if ( ! empty( $template_footer_id ) ) { ?>
			<a class="skip-link" href="#brx-footer" aria-label="<?php esc_html_e( 'Skip to footer', 'bricks' ); ?>"><?php esc_html_e( 'Skip to footer', 'bricks' ); ?></a>
			<?php
		}
	}

	/**
	 * Remove WP hooks on frontend
	 *
	 * @since 1.5.5
	 */
	public function remove_wp_hooks() {
		if ( is_attachment() && ! empty( Database::$active_templates['content'] ) ) {
			// Post type 'attachment' template: This filter prepends/adds the attachment to all Bricks elements that use the_content (@since 1.5.5)
			remove_filter( 'the_content', 'prepend_attachment' );
		}
	}

	/**
	 * Render header
	 *
	 * Bricks data exists & header is not disabled on this page.
	 *
	 * @since 1.3.2
	 */
	public function render_header() {
		$header_data = Database::get_template_data( 'header' );

		// Return: No header data exists
		if ( ! is_array( $header_data ) ) {
			return;
		}

		$settings = Helpers::get_template_settings( Database::$active_templates['header'] );
		$classes  = [];

		// Sticky header (top, not left or right)
		if ( ! isset( $settings['headerPosition'] ) && isset( $settings['headerSticky'] ) ) {
			$classes[] = 'sticky';

			if ( isset( $settings['headerStickyOnScroll'] ) ) {
				$classes[] = 'on-scroll';
			}
		}

		$attributes = [
			'id' => 'brx-header',
		];

		if ( count( $classes ) ) {
			$attributes['class'] = $classes;
		}

		if ( ! empty( $settings['headerStickySlideUpAfter'] ) ) {
			$attributes['data-slide-up-after'] = intval( $settings['headerStickySlideUpAfter'] );
		}

		// https://academy.bricksbuilder.io/article/filter-bricks-header-attributes/ (@since 1.5)
		$attributes = apply_filters( 'bricks/header/attributes', $attributes );

		$attributes = Helpers::stringify_html_attributes( $attributes );

		$header_html = "<header {$attributes}>" . self::render_data( $header_data, 'header' ) . '</header>';

		// NOTE: Undocumented
		echo apply_filters( 'bricks/render_header', $header_html );
	}

	/**
	 * Render Bricks content + surrounding 'main' tag
	 *
	 * For pages rendered with Bricks
	 *
	 * To allow customizing the 'main' tag attributes
	 *
	 * @since 1.5
	 */
	public static function render_content( $bricks_data = [], $attributes = [], $html_after_begin = '', $html_before_end = '', $tag = 'main' ) {
		// Merge custom attributes with default attributes ('id')
		if ( is_array( $attributes ) ) {
			$attributes = array_merge( [ 'id' => 'brx-content' ], $attributes );
		}

		// Return: Popup template preview
		if ( Templates::get_template_type() === 'popup' ) {
			return;
		}

		// https://academy.bricksbuilder.io/article/filter-bricks-content-attributes/ (@since 1.5)
		$attributes = apply_filters( 'bricks/content/attributes', $attributes );

		$attributes = Helpers::stringify_html_attributes( $attributes );

		if ( $tag ) {
			echo "<{$tag} {$attributes}>";
		}

		// https://academy.bricksbuilder.io/article/filter-bricks-content-html_after_begin/
		$html_after_begin = apply_filters( 'bricks/content/html_after_begin', $html_after_begin, $bricks_data, $attributes, $tag );

		if ( $html_after_begin ) {
			echo $html_after_begin;
		}

		if ( is_array( $bricks_data ) && count( $bricks_data ) ) {
			echo self::render_data( $bricks_data );
		}

		// https://academy.bricksbuilder.io/article/filter-bricks-content-html_before_end/
		$html_before_end = apply_filters( 'bricks/content/html_before_end', $html_before_end, $bricks_data, $attributes, $tag );

		if ( $html_before_end ) {
			echo $html_before_end;
		}

		if ( $tag ) {
			echo "</{$tag}>";
		}
	}

	/**
	 * Render footer
	 *
	 * To follow already available 'render_header' function syntax
	 *
	 * @since 1.5
	 */
	public function render_footer() {
		$footer_data = Database::get_template_data( 'footer' );

		if ( ! is_array( $footer_data ) ) {
			return;
		}

		// https://academy.bricksbuilder.io/article/filter-bricks-footer-attributes/ (@since 1.5)
		$attributes = apply_filters(
			'bricks/footer/attributes',
			[
				'id' => 'brx-footer',
			]
		);

		$attributes = Helpers::stringify_html_attributes( $attributes );

		$footer_html = "<footer {$attributes}>" . self::render_data( $footer_data, 'footer' ) . '</footer>';

		// NOTE: Undocumented
		echo apply_filters( 'bricks/render_footer', $footer_html );
	}
}
