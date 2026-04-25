<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Builder {
	public static $dynamic_data = []; // key: DD tag; value: DD tag value (@since 1.7.1)

	public function __construct() {
		add_action( 'wp_print_styles', [ $this, 'remove_admin_bar_inline_styles' ] );

		add_filter( 'show_admin_bar', [ $this, 'show_admin_bar' ] );

		add_action( 'init', [ $this, 'set_language_direction' ] );
		add_filter( 'locale', [ $this, 'maybe_set_locale' ], 99999, 1 ); // Hook in after TranslatePress

		add_action( 'send_headers', [ $this, 'dont_cache_headers' ] );

		add_action( 'wp_footer', [ $this, 'element_x_templates' ] );

		add_action( 'bricks_before_site_wrapper', [ $this, 'before_site_wrapper' ] );
		add_action( 'bricks_after_site_wrapper', [ $this, 'after_site_wrapper' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		// add_action( 'wp_enqueue_scripts', [ $this, 'static_area_styles' ] );

		add_filter( 'tiny_mce_before_init', [ $this, 'tiny_mce_before_init' ] );

		add_action( 'template_redirect', [ $this, 'template_redirect' ] );

		// In the builder force our own template to avoid conflicts with other builders
		add_filter( 'template_include', [ $this, 'template_include' ], 1001 );
	}

	/**
	 * Remove 'admin-bar' inline styles
	 *
	 * Necessary for WordPress 6.4+ as html {margin-top: 32px !important} causes gap in builder.
	 *
	 * @since 1.9.3
	 */
	public function remove_admin_bar_inline_styles() {
		// Remove 'admin-bar' inline style
		if ( wp_style_is( 'admin-bar', 'enqueued' ) ) {
			wp_style_add_data( 'admin-bar', 'after', '' );
		}
	}

	/**
	 * Don't cache headers or browser history buffer in builder
	 *
	 * To fix browser back button issue.
	 *
	 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Caching
	 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
	 *
	 * "at present any pages using Cache-Control: no-store will not be eligible for bfcache."
	 * - https://web.dev/bfcache/#minimize-use-of-cache-control-no-store
	 *
	 * @since 1.6.2
	 */
	public function dont_cache_headers() {
		header_remove( 'Cache-Control' );

		header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0' ); // HTTP 1.1
		header( 'Pragma: no-cache' ); // HTTP 1.0
		header( 'Expires: 0' ); // HTTP 1.0 proxies
	}

	/**
	 * Remove admin bar and CSS
	 *
	 * @since 1.0
	 */
	public function show_admin_bar() {
		remove_action( 'wp_head', '_admin_bar_bump_cb' );

		return false;
	}

	/**
	 * Set a different language locale in builder if user has specified a different admin language
	 *
	 * @since 1.1.2
	 */
	public function maybe_set_locale( $locale ) {
		// Check for builder language
		$builder_locale = Database::get_setting( 'builderLocale', false );

		if ( $builder_locale && $builder_locale !== 'site-default' ) {
			return $builder_locale;
		}

		// Check for specific WP dashboard user language
		$user = wp_get_current_user();

		$locale = ! empty( $user->locale ) ? $user->locale : $locale;

		return $locale;
	}

	/**
	 * Set language direction in builder (panels)
	 *
	 * Apply only to main window (toolbar & panels). Canvas should use frontend direction.
	 *
	 * @since 1.5
	 */
	public function set_language_direction() {
		// Return: Window is not main builder window
		if ( ! bricks_is_builder_main() ) {
			return;
		}

		$direction = Database::get_setting( 'builderLanguageDirection', false );

		if ( ! $direction ) {
			$builder_locale = Database::get_setting( 'builderLocale', false );

			// If builderLocale is set to "site-default", get the site's default locale
			if ( $builder_locale == 'site-default' ) {
				$builder_locale = get_locale();
			}

			// Determine if the locale is a RTL or LTR language
			// NOTE: Best not to hardcode RTL languages if possible!
			$rtl_languages = [ 'ar', 'he', 'fa', 'ur', 'yi', 'ps', 'dv', 'ckb', 'sd', 'ug' ];

			// Apply filter to allow RTL languages to be added
			$rtl_languages = apply_filters( 'bricks/rtl_languages', $rtl_languages );

			$language_code = substr( $builder_locale, 0, 2 );
			$direction     = in_array( $language_code, $rtl_languages ) ? 'rtl' : 'ltr';
		}

		global $wp_locale, $wp_styles;

		$wp_locale->text_direction = $direction;

		if ( ! is_a( $wp_styles, 'WP_Styles' ) ) {
			$wp_styles = new \WP_Styles();
		}

		$wp_styles->text_direction = $direction;
	}

	/**
	 * Canvas: Add element x-template render scripts to wp_footer
	 */
	public function element_x_templates() {
		if ( ! bricks_is_builder_iframe() ) {
			return;
		}

		foreach ( Elements::$elements as $element ) {
			echo $element['class']::render_builder();
		}
	}

	/**
	 * Before site wrapper (opening tag to render builder)
	 *
	 * @since 1.0
	 */
	public function before_site_wrapper() {
		if ( bricks_is_builder_main() ) {
			echo '<div class="brx-body main">';
		} elseif ( bricks_is_builder_iframe() ) {
			echo '<div class="brx-body iframe">';
		}
	}

	/**
	 * After site wrapper (closing tag to render builder)
	 *
	 * @since 1.0
	 */
	public function after_site_wrapper() {
		if ( bricks_is_builder() ) {
			echo '</div>'; // END .brx-body
		}
	}

	/**
	 * Enqueue styles and scripts
	 *
	 * @since 1.0
	 */
	public function enqueue_scripts() {
		// Access MediaElementsJS (element: Audio) and to get global 'wp' object to open media library (control type 'image', 'audio' etc.)
		wp_enqueue_media();

		// Order matters for CSS flexbox (enqueue builder styles before frontend styles)
		if ( bricks_is_builder() ) {
			wp_enqueue_style( 'bricks-builder', BRICKS_URL_ASSETS . 'css/builder.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/builder.min.css' ) );

			if ( is_rtl() ) {
				wp_enqueue_style( 'bricks-builder-rtl', BRICKS_URL_ASSETS . 'css/builder-rtl.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/builder-rtl.min.css' ) );
			}

			// PopupDocs.vue: Load prettify
			wp_enqueue_script( 'bricks-prettify' );
			wp_enqueue_style( 'bricks-prettify' );

			// Builder isotope (PopupUnsplash.vue)
			wp_enqueue_script( 'bricks-isotope' );

			// Datepicker (form & countdown)
			wp_enqueue_script( 'bricks-flatpickr' );
			wp_enqueue_style( 'bricks-flatpickr' );

			add_filter( 'mce_buttons_2', [ $this, 'add_editor_buttons' ] );
		}

		if ( bricks_is_builder_main() ) {
			// Manually enqueue dashicons for 'wp_enqueue_media' as 'get_wp_editor' prevents dashicons enqueue
			wp_enqueue_style( 'bricks-dashicons', includes_url( '/css/dashicons.min.css' ), [], null );

			wp_enqueue_script( 'bricks-builder', BRICKS_URL_ASSETS . 'js/main.min.js', [ 'bricks-scripts', 'jquery' ], filemtime( BRICKS_PATH_ASSETS . 'js/main.min.js' ), true );

			// Builder Mode "Custom": Add CSS variables as inline CSS
			$builder_mode   = Database::get_setting( 'builderMode', 'dark' );
			$builder_ui_css = Database::get_setting( 'builderModeCss', '' );

			if ( $builder_mode === 'custom' && ! empty( $builder_ui_css ) ) {
				wp_add_inline_style( 'bricks-tooltips', $builder_ui_css );
			}
		}

		if ( bricks_is_builder_iframe() ) {
			// Load Adobe fonts file (@since 1.7.1)
			$adobe_fonts_project_id = ! empty( Database::get_setting( 'adobeFontsProjectId' ) ) ? Database::get_setting( 'adobeFontsProjectId' ) : false;

			if ( $adobe_fonts_project_id ) {
				wp_enqueue_style( "adobe-fonts-project-id-$adobe_fonts_project_id", "https://use.typekit.net/$adobe_fonts_project_id.css" );
			}

			wp_enqueue_script( 'bricks-countdown' );
			wp_enqueue_script( 'bricks-counter' );
			wp_enqueue_script( 'bricks-flatpickr' );
			wp_enqueue_script( 'bricks-google-maps' );
			wp_enqueue_script( 'bricks-piechart' );
			wp_enqueue_script( 'bricks-swiper' );
			wp_enqueue_script( 'bricks-typed' );
			wp_enqueue_script( 'bricks-tocbot' );

			wp_enqueue_script( 'bricks-builder', BRICKS_URL_ASSETS . 'js/iframe.min.js', [ 'bricks-scripts', 'jquery' ], filemtime( BRICKS_PATH_ASSETS . 'js/iframe.min.js' ), true );
		}

		$post_id        = get_the_ID();
		$featured_image = false;

		/**
		 * Get control options to ensure filter 'bricks/setup/control_options' ran
		 *
		 * Eaxmples: 'queryTypes', custom user control options, etc.
		 *
		 * @since 1.5.5
		 */
		$control_options = Setup::get_control_options();

		// NOTE: Set post ID to posts page
		if ( is_home() ) {
			$post_id = get_option( 'page_for_posts' );
		}

		// NOTE: Undocumented
		$post_id = apply_filters( 'bricks/builder/data_post_id', $post_id );

		if ( has_post_thumbnail( $post_id ) ) {
			$featured_image = [
				'id'  => get_post_thumbnail_id(),
				'url' => get_the_post_thumbnail_url( $post_id ),
			];

			$image_sizes = array_keys( $control_options['imageSizes'] );

			foreach ( $image_sizes as $image_size ) {
				$featured_image[ $image_size ] = get_the_post_thumbnail_url( $post_id, $image_size );
			}
		}

		wp_localize_script(
			'bricks-builder',
			'bricksData',
			[
				'loadData'                      => self::builder_data( $post_id ), // Initial data to bootstrap builder iframe
				'dynamicWrapper'                => apply_filters( 'bricks/builder/dynamic_wrapper', [] ),

				// Bricks settings
				'customBreakpoints'             => Database::get_setting( 'customBreakpoints', false ),
				'disableClassManager'           => Database::get_setting( 'disableClassManager', false ),
				'disableClassChaining'          => Database::get_setting( 'disableClassChaining', false ),
				'disableGlobalClasses'          => Database::get_setting( 'builderDisableGlobalClassesInterface', false ),
				'disablePanelAutoExpand'        => Database::get_setting( 'builderDisablePanelAutoExpand', false ),
				'disableElementSpacing'         => Database::get_setting( 'disableElementSpacing', false ),
				'canvasScrollIntoView'          => Database::get_setting( 'canvasScrollIntoView', false ),
				'structureAutoSync'             => Database::get_setting( 'structureAutoSync', false ),
				'structureDuplicateElement'     => Database::get_setting( 'structureDuplicateElement', false ),
				'structureDeleteElement'        => Database::get_setting( 'structureDeleteElement', false ),
				'structureCollapsed'            => Database::get_setting( 'structureCollapsed', false ),
				'builderDisableRestApi'         => Database::get_setting( 'builderDisableRestApi', false ),
				'builderWrapElement'            => Database::get_setting( 'builderWrapElement', 'block' ),
				'builderInsertElement'          => Database::get_setting( 'builderInsertElement', 'block' ),
				'builderInsertLayout'           => Database::get_setting( 'builderInsertLayout', 'block' ),
				'enableDynamicDataPreview'      => Database::get_setting( 'enableDynamicDataPreview', false ),
				'enableQueryFilters'            => Database::get_setting( 'enableQueryFilters', false ),
				'builderDynamicDropdownKey'     => Database::get_setting( 'builderDynamicDropdownKey', false ),
				'builderDynamicDropdownNoLabel' => Database::get_setting( 'builderDynamicDropdownNoLabel', false ),
				'builderDynamicDropdownExpand'  => Database::get_setting( 'builderDynamicDropdownExpand', false ),
				'autosave'                      => [
					'disabled' => Database::get_setting( 'builderAutosaveDisabled', false ),
					'interval' => Database::get_setting( 'builderAutosaveInterval', 60 ),
				],
				'toolbarLogoLink'               => Database::get_setting( 'builderToolbarLogoLink', 'current' ),
				'toolbarLogoLinkCustom'         => Database::get_setting( 'builderToolbarLogoLinkCustom', '' ),
				'toolbarLogoLinkNewTab'         => Database::get_setting( 'builderToolbarLogoLinkNewTab', '' ),
				'mode'                          => Database::get_setting( 'builderMode', 'dark' ),
				'featuredImage'                 => $featured_image,
				'panelWidth'                    => get_option( BRICKS_DB_PANEL_WIDTH, 300 ),
				'scaleOff'                      => get_user_meta( get_current_user_id(), BRICKS_DB_BUILDER_SCALE_OFF, true ),
				'widthLocked'                   => get_user_meta( get_current_user_id(), BRICKS_DB_BUILDER_WIDTH_LOCKED, true ),

				'wp'                            => self::get_wordpress_data(),
				'academy'                       => [
					'home'              => 'https://academy.bricksbuilder.io/',
					'layout'            => 'https://academy.bricksbuilder.io/article/layout/',
					'headerTemplate'    => 'https://academy.bricksbuilder.io/article/create-template/',
					'footerTemplate'    => 'https://academy.bricksbuilder.io/article/create-template/',
					'createElement'     => 'https://academy.bricksbuilder.io/article/create-your-own-elements/',
					'globalElement'     => 'https://academy.bricksbuilder.io/article/global-elements/',
					'keyboardShortcuts' => 'https://academy.bricksbuilder.io/article/keyboard-shortcuts/',
					'pseudoClasses'     => 'https://academy.bricksbuilder.io/article/pseudo-classes/',
					'conditions'        => 'https://academy.bricksbuilder.io/article/element-conditions/',
					'interactions'      => 'https://academy.bricksbuilder.io/article/interactions/',
					'popups'            => 'https://academy.bricksbuilder.io/article/popup-builder/',
				],

				'version'                       => BRICKS_VERSION,
				'debug'                         => isset( $_GET['debug'] ) && Capabilities::current_user_has_full_access() ? sanitize_text_field( $_GET['debug'] ) : false,
				'message'                       => isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : false,
				'breakpoints'                   => Breakpoints::$breakpoints,
				'builderPreviewParam'           => BRICKS_BUILDER_IFRAME_PARAM,
				'maxUploadSize'                 => wp_max_upload_size(),

				'dynamicTags'                   => Integrations\Dynamic_Data\Providers::get_dynamic_tags_list(),

				// URL to edit header/content/footer templates
				'editHeaderUrl'                 => ! empty( Database::$active_templates['header'] ) ? Helpers::get_builder_edit_link( Database::$active_templates['header'] ) : '',
				'editContentUrl'                => ! empty( Database::$active_templates['content'] ) ? Helpers::get_builder_edit_link( Database::$active_templates['content'] ) : '',
				'editFooterUrl'                 => ! empty( Database::$active_templates['footer'] ) ? Helpers::get_builder_edit_link( Database::$active_templates['footer'] ) : '',

				'locale'                        => get_locale(),
				'i18n'                          => self::i18n(),
				'nonce'                         => wp_create_nonce( 'bricks-nonce-builder' ),
				'ajaxUrl'                       => admin_url( 'admin-ajax.php' ),
				'restApiUrl'                    => Api::get_rest_api_url(),
				'homeUrl'                       => home_url( '/' ),
				'adminUrl'                      => admin_url(),
				'loginUrl'                      => wp_login_url(),
				'themeUrl'                      => BRICKS_URL,
				'assetsUrl'                     => BRICKS_URL_ASSETS,
				'editPostUrl'                   => get_edit_post_link( $post_id ),
				'previewUrl'                    => add_query_arg( 'bricks_preview', time(), get_the_permalink( $post_id ) ),
				'siteName'                      => get_bloginfo( 'name' ),
				'siteUrl'                       => get_site_url(),
				'settingsUrl'                   => Helpers::settings_url(),

				'defaultImageSize'              => 'large',
				'author'                        => get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ),
				'isAdmin'                       => current_user_can( 'manage_options' ),
				'isTemplate'                    => get_post_type() === BRICKS_DB_TEMPLATE_SLUG,
				'isRtl'                         => is_rtl(),
				'postId'                        => $post_id,
				'postStatus'                    => get_post_status( $post_id ),
				'postType'                      => get_post_type( $post_id ),
				'postTypeUrl'                   => admin_url( 'edit.php?post_type=' ) . get_post_type( $post_id ),
				'postTypesRegistered'           => Helpers::get_registered_post_types(),
				'postTypesSupported'            => Helpers::get_supported_post_types(),
				'postsPerPage'                  => get_option( 'posts_per_page' ),
				'elements'                      => Elements::$elements,
				'elementsCatFirst'              => $this->get_first_elements_category( $post_id ),
				'wpEditor'                      => $this->get_wp_editor(),
				'recaptchaIds'                  => [],
				'saveMessages'                  => $this->save_messages(),
				'builderParam'                  => BRICKS_BUILDER_PARAM,

				'animatedTypingInstances'       => [], // Necessary to destroy and then reinit TypedJS instances
				'videoInstances'                => [], // Necessary to destroy and then reinit Plyr instances
				'splideInstances'               => [], // Necessary to destroy and then reinit SplideJS instances
				'tocbotInstances'               => [], // Necessary to destroy and then reinit Tocbot instances
				'swiperInstances'               => [], // Necessary to destroy and then reinit SwiperJS instances
				'isotopeInstances'              => [], // Necessary to destroy and then reinit Isotope instances
				'filterInstances'               => [], // Necessary to destroy and then reinit query filter instances

				'icons'                         => self::get_icon_font_classes(),

				'controls'                      => [
					'themeStyles'  => Theme_Styles::get_controls_data(),
					'settings'     => Settings::get_controls_data(),
					'conditions'   => Conditions::get_controls_data(),
					'interactions' => Interactions::get_controls_data(),
				],

				'controlOptions'                => $control_options, // Static data

				'themeStyles'                   => Theme_Styles::$styles,

				'remoteTemplateSettings'        => Templates::get_remote_template_settings(),

				'template'                      => [
					'defaultTemplatesDisabled' => Database::get_setting( 'defaultTemplatesDisabled' ),
					'orderBy'                  => $control_options['templatesOrderBy'],
					'preview'                  => self::get_template_preview_data( $post_id ),

					'authors'                  => Templates::get_template_authors(),
					'bundles'                  => Templates::get_template_bundles(),
					'tags'                     => Templates::get_template_tags(),

					'types'                    => $control_options['templateTypes'],
				],

				'mailchimpLists'                => Integrations\Form\Actions\Mailchimp::get_list_options(),
				'wooCommerceActive'             => Woocommerce::$is_active,
				'googleFontsDisabled'           => Helpers::google_fonts_disabled(),
				'fonts'                         => self::get_fonts(), // @since 1.7.1
			]
		);

		/**
		 * Deregister wp-polyfill.min.js as it is causing performance issue for Firefox browser (in WordPress 6.4+)
		 *
		 * @since 1.9.5
		 */
		if ( ! Database::get_setting( 'builderWpPolyfill', false ) && wp_script_is( 'wp-polyfill', 'registered' ) ) {
			wp_deregister_script( 'wp-polyfill' );
			wp_register_script( 'wp-polyfill', false );
		}
	}

	/**
	 * Enqueue inline styles for static areas
	 *
	 * NOTE: Not in use (handled in StaticArea.vue line198). Keep for future reference.
	 *
	 * @since 1.8.2 (#862jzhynp)
	 */
	public function static_area_styles() {
		return;

		// Return: Is main window (static area styles only needed on iframe canvas)
		if ( ! bricks_is_builder_iframe() ) {
			return;
		}

		$static_areas  = [];
		$template_type = Templates::get_template_type();

		// Header template: Static areas are 'content' & 'footer'
		if ( $template_type === 'header' ) {
			$static_areas = [ 'content', 'footer' ];
		} elseif ( $template_type === 'content' ) {
			$static_areas = [ 'header', 'footer' ];
		} elseif ( $template_type === 'footer' ) {
			$static_areas = [ 'header', 'content' ];
		}

		foreach ( $static_areas as $static_area ) {
			$preview_id         = ! empty( Database::$active_templates[ $static_area ] ) ? Database::$active_templates[ $static_area ] : 0;
			$static_area_handle = "bricks-static-area-{$static_area}-{$preview_id}";

			if ( $preview_id ) {
				// Generate & use only inline styles for this static area
				Assets::generate_inline_css( $preview_id );
				$styles = ! empty( Assets::$inline_css[ $static_area ] ) ? Assets::$inline_css[ $static_area ] : '';

				// Dynamic background image inside query loop (@see assets.php (l1365))
				if ( Database::get_setting( 'cssLoading' ) === 'file' ) {
					$styles .= Assets::$inline_css_dynamic_data;
				}

				if ( ! $styles ) {
					continue;
				}

				wp_register_style( $static_area_handle, false );
				wp_enqueue_style( $static_area_handle );
				wp_add_inline_style( $static_area_handle, $styles );
			}
		}
	}

	/**
	 * Get WordPress data for use in builder x-template (to reduce AJAX calls)
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public static function get_wordpress_data() {
		return [
			'post' => [
				'title'         => Helpers::get_the_title( get_the_ID(), false ),
				'title_context' => Helpers::get_the_title( get_the_ID(), true ),
			],
		];
	}

	/**
	 * Get all fonts
	 *
	 * - Adobe fonts (@since 1.7.1)
	 * - Custom fonts
	 * - Google fonts
	 * - Standard fonts
	 *
	 * @since 1.2.1
	 *
	 * @return array
	 */
	public static function get_fonts() {
		$fonts = [];

		// Build font dropdown 'options' for ControlTypography.vue
		$options = [];

		// STEP: Adobe fonts
		$adobe_fonts = Database::$adobe_fonts;

		if ( is_array( $adobe_fonts ) && count( $adobe_fonts ) ) {
			$options['adobeFontsGroupTitle'] = 'Adobe fonts';

			foreach ( $adobe_fonts as $adobe_font ) {
				$adobe_font_family_name      = $adobe_font['name'] ?? '';
				$adobe_font_family_slug      = $adobe_font['slug'] ?? '';
				$adobe_font_family_css_names = $adobe_font['css_names'] ?? [];

				if ( ! $adobe_font_family_name ) {
					continue;
				}

				/**
				 * Segmented fonts: For legacy Adobe Fonts kits, a font may have multiple CSS names (is always an array, though) (@since 1.9.4)
				 *
				 * Example: Azo Sans (where 'slug' is 'azo-sans', but css_names[0] is 'azo-sans-web', the latter which we need).
				 *
				 * https://fonts.adobe.com/docs/api/css_names
				 */
				if ( is_array( $adobe_font_family_css_names ) && count( $adobe_font_family_css_names ) ) {
						// Concatenate CSS names
						$adobe_font_family_key = implode( ', ', $adobe_font_family_css_names );

						$options[ $adobe_font_family_key ] = $adobe_font_family_name;
				}

				// Fallack to font slug
				else {
					$options[ $adobe_font_family_slug ] = $adobe_font_family_name;
				}
			}

			if ( count( $adobe_fonts ) ) {
				$fonts['adobe'] = $adobe_fonts;
			}
		}

		// STEP: Custom fonts
		$custom_fonts = Custom_Fonts::get_custom_fonts();

		if ( $custom_fonts ) {
			$options['customFontsGroupTitle'] = esc_html__( 'Custom Fonts', 'bricks' );

			foreach ( $custom_fonts as $custom_font_id => $custom_font ) {
				$options[ $custom_font_id ] = $custom_font['family'];
			}

			$fonts['custom'] = $custom_fonts;
		}

		// STEP: Google fonts (if not disabled via filter OR settings)
		if ( ! Helpers::google_fonts_disabled() ) {
			$google_fonts = self::get_google_fonts();

			$options['googleFontsGroupTitle'] = 'Google fonts';

			foreach ( $google_fonts as $google_font ) {
				$options[ $google_font['family'] ] = $google_font['family'];
			}

			$fonts['google'] = $google_fonts;
		}

		// STEP: Standard fonts
		$standard_fonts = self::get_standard_fonts();

		$options['standardFontsGroupTitle'] = esc_html__( 'Standard fonts', 'bricks' );

		foreach ( $standard_fonts as $standard_font ) {
			$options[ $standard_font ] = $standard_font;
		}

		$fonts['standard'] = $standard_fonts;

		$fonts['options'] = $options;

		return $fonts;
	}

	/**
	 * Get standard (web safe) fonts
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_standard_fonts() {
		$standard_fonts = [
			'Arial',
			'Helvetica',
			'Helvetica Neue',
			'Times New Roman',
			'Times',
			'Georgia',
			'Courier New',
		];

		return apply_filters( 'bricks/builder/standard_fonts', $standard_fonts );
	}

	/**
	 * Get Google fonts
	 *
	 * Return fonts array with 'family' & 'variants' (to update font-weight for each font in builder)
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_google_fonts() {
		/**
		 * STEP: Generate Google fonts JSON file from API response
		 *
		 * We only need the 'family' & 'variants' properties from the Google fonts API response.
		 *
		 * DEV_ONLY: Set $google_fonts_generate below to true to generate the JSON file!
		 *
		 * @since 1.7.1
		 */
		$google_fonts_generate = false;

		if ( $google_fonts_generate ) {
			$google_fonts = file_get_contents( BRICKS_URL . 'src/assets/fonts/google-fonts.json' );
			$google_fonts = json_decode( $google_fonts, true );
			$google_fonts = ! empty( $google_fonts['items'] ) ? $google_fonts['items'] : [];

			$google_fonts_processed = [];

			foreach ( $google_fonts as $google_font ) {
				$family   = ! empty( $google_font['family'] ) ? $google_font['family'] : false;
				$variants = ! empty( $google_font['variants'] ) ? wp_json_encode( $google_font['variants'] ) : false;

				$variants = str_replace( 'regular', '400', $variants );

				if ( ! $family ) {
					continue;
				}

				$google_fonts_processed[] = [
					'family'   => $family,
					'variants' => json_decode( $variants, true ),
				];
			}

			// Encode into minified JSON format
			$json = wp_json_encode( $google_fonts_processed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

			// Save the modified JSON data back to the file
			file_put_contents( BRICKS_PATH . 'src/assets/fonts/google-fonts.min.json', $json );

			return [];
		}

		// STEP: Get contents of the Google fonts JSON file
		$google_fonts = Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'fonts/google-fonts.min.json' );

		// Return: Empty file OR not found
		if ( ! $google_fonts ) {
			return [];
		}

		// Decode the JSON data into a PHP object
		$google_fonts = json_decode( $google_fonts, true );

		return is_array( $google_fonts ) ? $google_fonts : [];
	}

	/**
	 * Template placeholder image (if importImages set to false)
	 *
	 * @since 1.0
	 */
	public static function get_template_placeholder_image() {
		return get_template_directory_uri() . '/assets/images/placeholder-image-800x600.jpg';
	}

	/**
	 * Template preview data
	 *
	 * @since 1.0
	 */
	public static function get_template_preview_data( $post_id ) {
		$preview_data = [];

		// Placeholder HTML
		$placeholder = '<section class="brxe-container brxe-alert" style="cursor: pointer">';

		$placeholder .= Helpers::get_element_placeholder(
			[
				'icon-class' => 'ti-layout',
				'text'       => esc_html__( 'Click to set preview content.', 'bricks' ),
			]
		);

		$placeholder .= '</section>';

		$preview_data['placeholder'] = $placeholder;

		// Only add the preview post id if there is a preview (@since 1.5.1)
		if ( Helpers::is_bricks_template( $post_id ) ) {
			$template_settings = Helpers::get_template_settings( $post_id );

			if ( ! empty( $template_settings['templatePreviewPostId'] ) ) {
				$post_id = intval( $template_settings['templatePreviewPostId'] );
			}

			$preview_data['postId'] = $post_id;
		}

		return $preview_data;
	}

	/**
	 * Post thumbnail data (for use in _background control)
	 *
	 * @since 1.0
	 */
	public function get_post_thumbnail() {
		return [
			'filename' => basename( get_attached_file( get_post_thumbnail_id( get_the_ID() ) ) ),
			'full'     => get_the_post_thumbnail_url( get_the_ID(), 'full' ),
			'id'       => get_post_thumbnail_id( get_the_ID() ),
			'size'     => BRICKS_DEFAULT_IMAGE_SIZE,
			'url'      => get_the_post_thumbnail_url( get_the_ID(), BRICKS_DEFAULT_IMAGE_SIZE ),
		];
	}

	/**
	 * Custom TinyMCE settings for builder
	 *
	 * @since 1.0
	 */
	public function tiny_mce_before_init( $in ) {
		// Remove certain TinyMCE plugins in builder
		$plugins = explode( ',', $in['plugins'] );
		$key     = array_search( 'fullscreen', $plugins );

		if ( isset( $plugins[ $key ] ) ) {
			unset( $plugins[ $key ] );
		}

		$in['plugins'] = join( ',', $plugins );

		return $in;
	}

	/**
	 * WordPress editor
	 *
	 * Without tag button, "Add media" button (use respective elements instead)
	 *
	 * @since 1.0
	 */
	public function get_wp_editor() {
		ob_start();

		$mce_buttons = add_filter(
			'mce_buttons',
			function( $buttons ) {
				// NOTE: Show all editor controls @since 1.3.6 ("Basic Text" element)
				// Remove formatselect button (paragraph/heading/preformatted etc.)
				// $buttons_to_remove = [ 'formatselect' ];

				// foreach ( $buttons as $index => $button_name ) {
				// if ( in_array( $button_name, $buttons_to_remove ) ) {
				// unset( $buttons[ $index ] );
				// }
				// }

				// Add dynamic tag picker dropdown button to tinyMCE editor
				$buttons[] = 'tagPickerButton';

				return $buttons;
			}
		);

		$content   = '%%BRICKS_EDITOR_CONTENT_PLACEHOLDER%%';
		$editor_id = 'brickswpeditor'; // No dashes, see https://codex.wordpress.org/Function_Reference/wp_editor
		$settings  = [
			'editor_class' => 'bricks-wp-editor',
			// 'media_buttons' => false, // Use image element instead
			'quicktags'    => [
				'buttons' => 'sup',
			// 'buttons' => 'strong,em,ul,ol,li,link,close', // No spaces
			],
		];

		wp_editor( $content, $editor_id, $settings );

		return ob_get_clean();
	}

	/**
	 * Add 'superscript' & 'subscript' button to TinyMCE in builder
	 *
	 * @since 1.4
	 */
	public function add_editor_buttons( $buttons ) {
		if ( ! in_array( 'superscript', $buttons ) ) {
			$buttons[] = 'superscript';
		}

		if ( ! in_array( 'subscript', $buttons ) ) {
			$buttons[] = 'subscript';
		}

		return $buttons;
	}

	/**
	 * Builder strings
	 *
	 * @since 1.0
	 */
	public static function i18n() {
		$post_type        = get_post_type();
		$post_type_object = get_post_type_object( $post_type );
		$post_type_name   = $post_type_object ? $post_type_object->labels->singular_name : $post_type;

		$i18n = [
			'actions'                          => esc_html__( 'Actions', 'bricks' ),
			'active'                           => esc_html__( 'Active', 'bricks' ),
			'add'                              => esc_html__( 'Add', 'bricks' ),
			'added'                            => esc_html__( 'added', 'bricks' ),
			'addClass'                         => esc_html__( 'Add class', 'bricks' ),
			'addImages'                        => esc_html__( 'Add images', 'bricks' ),
			'addItem'                          => esc_html__( 'Add Item', 'bricks' ),
			'addPrefix'                        => esc_html__( 'Add prefix', 'bricks' ),
			'addSuffix'                        => esc_html__( 'Add suffix', 'bricks' ),
			'ajaxLoader'                       => esc_html__( 'AJAX loader', 'bricks' ),
			'ajaxLoaderDesc'                   => esc_html__( 'Shows when using infinite scroll, load more interaction, AJAX pagination.', 'bricks' ),
			'ajaxLoaderSelector'               => esc_html__( 'CSS selector', 'bricks' ),
			'ajaxLoaderSelectorDesc'           => esc_html__( 'CSS selector of the element to insert the AJAX loader into.', 'bricks' ),
			'ajaxLoaderAnimation'              => esc_html__( 'Animation', 'bricks' ),

			'align'                            => esc_html__( 'Align', 'bricks' ),
			'alignItems'                       => esc_html__( 'Align items', 'bricks' ),
			'all'                              => esc_html__( 'All', 'bricks' ),
			'alpha'                            => esc_html__( 'Transparency', 'bricks' ),
			'alphabetically'                   => esc_html__( 'Alphabetically', 'bricks' ),
			'and'                              => esc_html__( 'And', 'bricks' ),
			'angle'                            => esc_html__( 'Angle in °', 'bricks' ),
			'any'                              => esc_html__( 'Any', 'bricks' ),
			'anyBreakpoint'                    => esc_html__( 'Any breakpoint', 'bricks' ),
			'apply'                            => esc_html__( 'Apply', 'bricks' ),
			'applyTo'                          => esc_html__( 'Apply to', 'bricks' ),
			'archive'                          => esc_html__( 'Archive', 'bricks' ),
			'arrows'                           => esc_html__( 'Arrows', 'bricks' ),
			'ascending'                        => esc_html__( 'Ascending', 'bricks' ),
			'author'                           => esc_html__( 'Author', 'bricks' ),
			'attachment'                       => esc_html__( 'Attachment', 'bricks' ),
			'attribute'                        => esc_html__( 'Attribute', 'bricks' ),
			'autosaveBy'                       => esc_html__( 'Autosave by', 'bricks' ),

			'background'                       => esc_html__( 'Background', 'bricks' ),
			'backgroundColor'                  => esc_html__( 'Background color', 'bricks' ),
			'backgroundCustomSize'             => esc_html__( 'Background custom size', 'bricks' ),
			'backgroundCustomSizePlaceholder'  => esc_html__( '50% or 200px', 'bricks' ),
			'backgroundPosition'               => esc_html__( 'Background position', 'bricks' ),
			'backgroundRepeat'                 => esc_html__( 'Background repeat', 'bricks' ),
			'backgroundSize'                   => esc_html__( 'Background size', 'bricks' ),
			'backgroundAttachment'             => esc_html__( 'Background attachment', 'bricks' ),
			'backgroundBlendMode'              => esc_html__( 'Background blend mode', 'bricks' ),
			'backgroundVideo'                  => esc_html__( 'Background video', 'bricks' ),
			'backgroundVideoAspectRatio'       => esc_html__( 'Aspect ratio', 'bricks' ),
			'backgroundVideoStartAt'           => esc_html__( 'Select smallest breakpoint that this video should play at. Preview on frontend.', 'bricks' ),
			'backgroundVideoDescription'       => esc_html__( 'YouTube, Vimeo or file URL.', 'bricks' ),
			'backToBuilder'                    => esc_html__( 'Back to builder', 'bricks' ),
			'baseBreakpoint'                   => esc_html__( 'Base breakpoint', 'bricks' ),
			'baseline'                         => esc_html__( 'Baseline', 'bricks' ),
			'basic'                            => esc_html__( 'Basic', 'bricks' ),
			'blockquote'                       => esc_html__( 'Blockquote', 'bricks' ),
			'align'                            => esc_html__( 'Align', 'bricks' ),
			'bulletedList'                     => esc_html__( 'Bullet List', 'bricks' ),
			'block'                            => esc_html__( 'Block', 'bricks' ),
			'blur'                             => esc_html__( 'Blur', 'bricks' ),
			'bold'                             => esc_html__( 'Bold', 'bricks' ),
			'border'                           => esc_html__( 'Border', 'bricks' ),
			'borderColor'                      => esc_html__( 'Border color', 'bricks' ),
			'bottom'                           => esc_html__( 'Bottom', 'bricks' ),
			'bottomLeft'                       => esc_html__( 'Bottom left', 'bricks' ),
			'bottomCenter'                     => esc_html__( 'Bottom center', 'bricks' ),
			'bottomRight'                      => esc_html__( 'Bottom right', 'bricks' ),
			'boxShadow'                        => esc_html__( 'Box shadow', 'bricks' ),
			'breakpoint'                       => esc_html__( 'Breakpoint', 'bricks' ),
			'breakpoints'                      => esc_html__( 'Breakpoints', 'bricks' ),
			'breakpointBaseMessage'            => esc_html__( 'Editing the base breakpoint width affects all media queries.', 'bricks' ),
			'breakpointDeleteDescription'      => esc_html__( 'Are you sure that you want to delete this breakpoint?', 'bricks' ),
			'bricksAcademy'                    => esc_html__( 'Bricks Academy', 'bricks' ),
			'brightness'                       => esc_html__( 'Brightness', 'bricks' ),
			'browse'                           => esc_html__( 'Browse', 'bricks' ),
			'browseMediaLibrary'               => esc_html__( 'Browse Media Library', 'bricks' ),
			'browseUnsplash'                   => esc_html__( 'Browse Unsplash', 'bricks' ),
			'builder'                          => esc_html__( 'Builder', 'bricks' ),
			'builderHelpTitle'                 => esc_html__( 'Need help? Found a bug? Suggest a feature?', 'bricks' ),
			'builderHelpDescription'           => sprintf(
				// translators: %s: Bricks support email address (link)
				__( 'Please use your Bricks account email address for all customer support requests. To attach larger files, please send an email directly to %1$s. To see what is currently in development or submit/upvote feature requests please visit our %2$s.', 'bricks' ),
				'<a href="mailto:help@bricksbuilder.io" target="_blank">help@bricksbuilder.io</a>',
				'<a href="https://bricksbuilder.io/roadmap/" target="_blank" rel="noopener">' . esc_html__( 'official roadmap', 'bricks' ) . '</a>'
			),
			// translators: %s: Max. upload size (e.g. 2 MB)
			'builderHelpUploadLimitExceeded'   => sprintf( esc_html__( 'Your attached files exceed your server max. upload size of %s.', 'bricks' ), size_format( wp_max_upload_size() ) ),
			'builderHelpGmailLimitExceeded'    => esc_html__( 'Your attached files exceed the max. upload limit of 25 MB.', 'bricks' ),
			'bulletedlist'                     => esc_html__( 'Bulleted list', 'bricks' ),
			'bulkActions'                      => esc_html__( 'Bulk actions', 'bricks' ),
			'by'                               => esc_html__( 'by', 'bricks' ),

			'cancel'                           => esc_html__( 'Cancel', 'bricks' ),
			'capitalize'                       => esc_html__( 'Capitalize', 'bricks' ),
			'categories'                       => esc_html__( 'Categories', 'bricks' ),
			'categorize'                       => esc_html__( 'Categorize', 'bricks' ),
			'category'                         => esc_html__( 'Category', 'bricks' ),
			'categoriesDeleted'                => esc_html__( 'Categories deleted', 'bricks' ),
			'category'                         => esc_html__( 'Category', 'bricks' ),
			'categoryNamePlaceholder'          => esc_html__( 'New category name', 'bricks' ),
			'center'                           => esc_html__( 'Center', 'bricks' ),
			'centerLeft'                       => esc_html__( 'Center left', 'bricks' ),
			'centerCenter'                     => esc_html__( 'Center center', 'bricks' ),
			'centerRight'                      => esc_html__( 'Center right', 'bricks' ),
			'childOf'                          => esc_html__( 'Child of', 'bricks' ),
			'childless'                        => esc_html__( 'Childless', 'bricks' ),
			'className'                        => esc_html__( 'Class name', 'bricks' ),
			'classNameExists'                  => esc_html__( 'Class name already exists', 'bricks' ),
			'classNamePlaceholder'             => esc_html__( 'New class name', 'bricks' ),
			'clickToDownload'                  => esc_html__( 'Click to download', 'bricks' ),
			'circle'                           => esc_html__( 'Circle', 'bricks' ),
			'chooseFiles'                      => esc_html__( 'Choose files', 'bricks' ),
			'chooseImage'                      => esc_html__( 'Choose image', 'bricks' ),
			'classes'                          => esc_html__( 'Classes', 'bricks' ),
			'classesDuplicated'                => esc_html__( 'Classes duplicated', 'bricks' ),
			'classesRenamed'                   => esc_html__( 'Classes renamed', 'bricks' ),
			'clearSearchControlFilters'        => esc_html__( 'Clear search filter', 'bricks' ),
			'clone'                            => esc_html__( 'Clone', 'bricks' ),
			'cloned'                           => esc_html__( 'cloned', 'bricks' ),
			'clean'                            => esc_html__( 'Clean', 'bricks' ),
			'clear'                            => esc_html__( 'Clear', 'bricks' ),
			'close'                            => esc_html__( 'Close', 'bricks' ),
			'closestSide'                      => esc_html__( 'Closest side', 'bricks' ),
			'closestCorner'                    => esc_html__( 'Closest corner', 'bricks' ),
			'closeEsc'                         => esc_html__( 'Close (ESC)', 'bricks' ),
			'collapse'                         => esc_html__( 'Collapse', 'bricks' ),
			'codeSignatures'                   => esc_html__( 'Code signatures', 'bricks' ),
			'codeSigned'                       => esc_html__( 'Code signed', 'bricks' ),
			'codeSignaturesDescription'        => esc_html__( 'Elements without code signature. Review, then sign your code one-by-one or all-at-once.', 'bricks' ),
			'color'                            => esc_html__( 'Color', 'bricks' ),
			'colors'                           => esc_html__( 'Colors', 'bricks' ),
			'colorStop'                        => esc_html__( 'Color stop', 'bricks' ),
			'colorPalette'                     => esc_html__( 'Color palette', 'bricks' ),
			'column'                           => esc_html__( 'Column', 'bricks' ),
			'commentCount'                     => esc_html__( 'Comment count', 'bricks' ),
			'community'                        => esc_html__( 'Community', 'bricks' ),
			'communityTemplates'               => esc_html__( 'Community templates', 'bricks' ),
			'compare'                          => esc_html__( 'Compare', 'bricks' ),
			'condition'                        => esc_html__( 'Condition', 'bricks' ),
			'conditions'                       => esc_html__( 'Conditions', 'bricks' ),
			'conditionSelect'                  => esc_html__( 'Select condition', 'bricks' ),
			'configure'                        => esc_html__( 'Configure', 'bricks' ),
			'confirm'                          => esc_html__( 'Confirm', 'bricks' ),
			'conic'                            => esc_html__( 'Conic', 'bricks' ),
			'contactUs'                        => esc_html__( 'Contact us', 'bricks' ),
			'contain'                          => esc_html__( 'Contain', 'bricks' ),
			'container'                        => esc_html__( 'Container', 'bricks' ),
			'content'                          => esc_html__( 'Content', 'bricks' ),
			'contrast'                         => esc_html__( 'Contrast', 'bricks' ),
			'copied'                           => esc_html__( 'Copied', 'bricks' ),
			'copiedToClipboard'                => esc_html__( 'Copied to clipboard', 'bricks' ),
			'copy'                             => esc_html__( 'Copy', 'bricks' ),
			'copyStyles'                       => esc_html__( 'Copy styles', 'bricks' ),
			'copyToClipboard'                  => esc_html__( 'Copy to clipboard', 'bricks' ),
			'copyElementSelector'              => esc_html__( 'Copy CSS selector', 'bricks' ),
			'contenteditablePlaceholder'       => esc_html__( 'Here goes my text ...', 'bricks' ),
			'convert'                          => esc_html__( 'Convert', 'bricks' ),
			'cover'                            => esc_html__( 'Cover', 'bricks' ),
			'currentPostAuthor'                => esc_html__( 'Current post author', 'bricks' ),
			'currentPostTerm'                  => esc_html__( 'Current post term', 'bricks' ),
			'create'                           => esc_html__( 'Create', 'bricks' ),
			'created'                          => esc_html__( 'Created', 'bricks' ),
			'createTemplate'                   => esc_html__( 'Create template', 'bricks' ),
			'createTemplateTitlePlaceholder'   => esc_html__( 'Template title', 'bricks' ),
			'createTemplateTitle'              => esc_html__( 'Create new template:', 'bricks' ),
			'createYourOwnElements'            => esc_html__( 'Create your own elements', 'bricks' ),
			'cssClass'                         => esc_html__( 'CSS class', 'bricks' ),
			'cssClassName'                     => esc_html__( 'Class name', 'bricks' ),
			'cssClassesTooltip'                => esc_html__( 'Separated by space. No leading dot "."', 'bricks' ),
			'cssIdTooltip'                     => esc_html__( 'No leading pound sign "#"', 'bricks' ),
			'cssFilter'                        => esc_html__( 'CSS filter', 'bricks' ),
			'cssFilterDescription'             => '<a target="_blank" href="https://developer.mozilla.org/en/docs/Web/CSS/filter?v=example">' . esc_html__( 'Enter CSS filters + value (learn more)', 'bricks' ) . '</a>',
			'cssSelector'                      => esc_html__( 'CSS selector', 'bricks' ),
			'currentLayout'                    => esc_html__( 'Current layout', 'bricks' ),
			'currentVersionBy'                 => esc_html__( 'Current version by', 'bricks' ),
			'currentWidth'                     => esc_html__( 'Current width', 'bricks' ),
			'custom'                           => esc_html__( 'Custom', 'bricks' ),
			'customCss'                        => esc_html__( 'Custom CSS', 'bricks' ),
			'customFields'                     => esc_html__( 'Custom fields', 'bricks' ),
			'customFont'                       => esc_html__( 'Custom font', 'bricks' ),

			'dashboard'                        => esc_html__( 'Dashboard', 'bricks' ),
			'default'                          => esc_html__( 'Default', 'bricks' ),
			// translators: %s: Default templates are enabled.
			'defaultTemplatesEnabled'          => sprintf( esc_html__( '%s. Template conditions precede default templates.', 'bricks' ), '<a href="' . admin_url( 'admin.php?page=bricks-settings#tab-templates' ) . '" target="_blank">' . esc_html__( 'Default templates are enabled', 'bricks' ) . '</a>' ),
			// translators: %s: Default templates are disabled.
			'defaultTemplatesDisabled'         => sprintf( esc_html__( '%s. Set template conditions or enable default templates.', 'bricks' ), '<a href="' . admin_url( 'admin.php?page=bricks-settings#tab-templates' ) . '" target="_blank">' . esc_html__( 'Default templates are disabled', 'bricks' ) . '</a>' ),
			'dashed'                           => esc_html__( 'dashed', 'bricks' ),
			'date'                             => esc_html__( 'Date', 'bricks' ),
			'delete'                           => esc_html__( 'Delete', 'bricks' ),
			'deleted'                          => esc_html__( 'Deleted', 'bricks' ),
			'deprecated'                       => esc_html__( 'Deprecated', 'bricks' ),
			'descending'                       => esc_html__( 'Descending', 'bricks' ),
			'descriptionCustomLayout'          => esc_html__( 'Number between 1 - 100 or auto', 'bricks' ),
			'descriptionLightboxVideo'         => esc_html__( 'YouTube, Vimeo or file URL.', 'bricks' ),
			'descriptionParallax'              => esc_html__( 'Set to "Fixed" for parallax effect.', 'bricks' ),
			'desktop'                          => esc_html__( 'Desktop', 'bricks' ),
			'direction'                        => esc_html__( 'Direction', 'bricks' ),
			'disabled'                         => esc_html__( 'Disabled', 'bricks' ),
			'disableQueryMerge'                => esc_html__( 'Disable query merge', 'bricks' ),
			'discard'                          => esc_html__( 'Discard', 'bricks' ),
			'div'                              => 'Div',
			'documentation'                    => esc_html__( 'Documentation', 'bricks' ),
			'dots'                             => esc_html__( 'Dots', 'bricks' ),
			'dotted'                           => esc_html__( 'Dotted', 'bricks' ),
			'double'                           => esc_html__( 'double', 'bricks' ),
			'download'                         => esc_html__( 'Download', 'bricks' ),
			'downloaded'                       => esc_html__( 'Downloaded', 'bricks' ),
			'downloading'                      => esc_html__( 'Downloading', 'bricks' ),
			'duplicate'                        => esc_html__( 'Duplicate', 'bricks' ),
			'dynamicData'                      => esc_html__( 'Dynamic Data', 'bricks' ),
			'dynamicDataIsEmpty'               => esc_html__( 'Dynamic data is empty.', 'bricks' ),
			'dynamicDataSelect'                => esc_html__( 'Select dynamic data', 'bricks' ),

			'edit'                             => esc_html__( 'Edit', 'bricks' ),
			'edited'                           => esc_html__( 'Edited', 'bricks' ),
			'editTemplate'                     => esc_html__( 'Edit Template', 'bricks' ),
			'editColorPalette'                 => esc_html__( 'Edit palette', 'bricks' ),
			'editing'                          => esc_html__( 'Editing', 'bricks' ),
			'editInWordPress'                  => esc_html__( 'Edit in WordPress', 'bricks' ),
			'effect'                           => esc_html__( 'Effect', 'bricks' ),
			'element'                          => esc_html__( 'Element', 'bricks' ),
			'elements'                         => esc_html__( 'Elements', 'bricks' ),
			'elementClasses'                   => esc_html__( 'Element classes', 'bricks' ),
			'elementId'                        => esc_html__( 'Element ID', 'bricks' ),
			'ellipse'                          => esc_html__( 'Ellipse', 'bricks' ),
			'equal'                            => esc_html__( 'Equal', 'bricks' ),

			// x-template element placeholder text
			'elementPlaceholder'               => [
				'default'               => esc_html__( 'No content', 'bricks' ),

				'accordion'             => esc_html__( 'No accordion item added.', 'bricks' ),
				'audio'                 => esc_html__( 'No audio file selected.', 'bricks' ),
				'code'                  => esc_html__( 'No code found.', 'bricks' ),
				'countdown'             => esc_html__( 'No date/fields set.', 'bricks' ),
				'facebook'              => esc_html__( 'No Facebook page URL provided.', 'bricks' ),
				'form'                  => esc_html__( 'No form field added.', 'bricks' ),
				'html'                  => esc_html__( 'No HTML markup defined.', 'bricks' ),
				'icon'                  => esc_html__( 'No icon selected.', 'bricks' ),
				'list'                  => esc_html__( 'No list items defined.', 'bricks' ),
				'map'                   => sprintf(
					// translators: %s: Link to Bricks Academy
					esc_html__( 'Google Maps API key required! Add key in dashboard under: %s', 'bricks' ),
					'<a href="' . Helpers::settings_url( '#tab-api-keys' ) . '" target="_blank">Bricks > ' . esc_html__( 'Settings', 'bricks' ) . ' > API keys</a>'
				),
				'pricing-table'         => esc_html__( 'No pricing table defined.', 'bricks' ),
				'progress-bar'          => esc_html__( 'No progress bar created.', 'bricks' ),
				'slider'                => esc_html__( 'No slide added.', 'bricks' ),
				'social-icons'          => esc_html__( 'No icon selected.', 'bricks' ),
				'svg'                   => esc_html__( 'No SVG selected.', 'bricks' ),
				'tabs'                  => esc_html__( 'No tabs added.', 'bricks' ),
				'team-members'          => esc_html__( 'No team members added.', 'bricks' ),
				'template'              => esc_html__( 'No template selected.', 'bricks' ),
				'testimonials'          => esc_html__( 'No testimonials added.', 'bricks' ),
				'text'                  => esc_html__( 'No text added.', 'bricks' ),
				'videoNoFileUrl'        => esc_html__( 'No file URL provided.', 'bricks' ),
				'videoNoVideo'          => esc_html__( 'No video selected.', 'bricks' ),
				'videoNoYoutubeId'      => esc_html__( 'No YouTube URL provided.', 'bricks' ),
				'videoNoVimeoId'        => esc_html__( 'No Vimeo URL provided.', 'bricks' ),
				'videoNoDynamicData'    => esc_html__( 'No dynamic data set.', 'bricks' ),
				'videoDynamicDataEmpty' => esc_html__( 'The dynamic data is empty.', 'bricks' ),
			],

			'emailAddress'                     => esc_html__( 'Email address', 'bricks' ),
			'end'                              => esc_html__( 'End', 'bricks' ),
			'endTime'                          => esc_html__( 'End time', 'bricks' ),
			'error'                            => esc_html__( 'Error', 'bricks' ),
			'errorBricksAcademy404'            => esc_html__( 'Articles could not be loaded. Please visit the official knowledge base:', 'bricks' ),
			'errorPage'                        => esc_html__( '404 Error Page', 'bricks' ),
			'excerptLength'                    => esc_html__( 'Excerpt length', 'bricks' ),
			'exclude'                          => esc_html__( 'Exclude', 'bricks' ),
			'excludeCurrent'                   => esc_html__( 'Exclude current post', 'bricks' ),
			'executeCode'                      => esc_html__( 'Execute code', 'bricks' ),
			'expand'                           => esc_html__( 'Expand', 'bricks' ),
			'expandAll'                        => esc_html__( 'Expand all', 'bricks' ),
			'experimental'                     => esc_html__( 'experimental', 'bricks' ),
			'export'                           => esc_html__( 'Export', 'bricks' ),
			'exportSelected'                   => esc_html__( 'Export selected', 'bricks' ),
			'external'                         => esc_html__( 'External URL', 'bricks' ),
			'extraLarge'                       => esc_html__( 'Extra large', 'bricks' ),
			'extraSmall'                       => esc_html__( 'Extra small', 'bricks' ),

			'fade'                             => esc_html__( 'Fade', 'bricks' ),
			'farthestSide'                     => esc_html__( 'Farthest side', 'bricks' ),
			'farthestCorner'                   => esc_html__( 'Farthest corner', 'bricks' ),
			'fallbackFont'                     => esc_html__( 'Fallback fonts', 'bricks' ),
			'false'                            => esc_html__( 'False', 'bricks' ),
			'field'                            => esc_html__( 'Field', 'bricks' ),
			'file'                             => esc_html__( 'File', 'bricks' ),
			'fileImported'                     => esc_html__( '"%s" imported.', 'bricks' ), // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			'fileNotImportedAlreadyExists'     => esc_html__( 'Import of "%s" failed: Name already exists.', 'bricks' ), // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			'fileNotImportedWrongFormat'       => esc_html__( 'Import of "%s" failed: Wrong format.', 'bricks' ), // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			'files'                            => esc_html__( 'Files', 'bricks' ),
			'fill'                             => esc_html__( 'Fill', 'bricks' ),
			'filter'                           => esc_html__( 'Filter', 'bricks' ),
			'noFilesChosen'                    => esc_html__( 'No files chosen', 'bricks' ),
			'fillDark'                         => esc_html__( 'Fill - Dark', 'bricks' ),
			'fillLight'                        => esc_html__( 'Fill - Light', 'bricks' ),
			'fillPrimary'                      => esc_html__( 'Fill - Primary', 'bricks' ),
			'find'                             => esc_html__( 'Find', 'bricks' ),
			'finish'                           => esc_html__( 'Finish', 'bricks' ),
			'fixed'                            => esc_html__( 'Fixed', 'bricks' ),
			'fontFamily'                       => esc_html__( 'Font family', 'bricks' ),
			'fontSize'                         => esc_html__( 'Font size', 'bricks' ),
			'fontStyle'                        => esc_html__( 'Font style', 'bricks' ),
			'fontWeight'                       => esc_html__( 'Font weight', 'bricks' ),
			'fontVariants'                     => esc_html__( 'Font variants', 'bricks' ),
			'footer'                           => esc_html__( 'Footer', 'bricks' ),
			'fullSize'                         => esc_html__( 'Full size', 'bricks' ),

			'galleryLayout'                    => esc_html__( 'Gallery layout', 'bricks' ),
			'general'                          => esc_html__( 'General', 'bricks' ),
			'ghostDark'                        => esc_html__( 'Outline - Dark', 'bricks' ),
			'ghostLight'                       => esc_html__( 'Outline - Light', 'bricks' ),
			'ghostPrimary'                     => esc_html__( 'Outline - Primary', 'bricks' ),
			'globalElement'                    => esc_html__( 'Global element', 'bricks' ),
			'globalClassManager'               => esc_html__( 'Global class manager', 'bricks' ),
			'globalClassManagerSearchInfo'     => esc_html__( 'Prefix with a dot to search for classes starting with the string, or suffix with a dot to search for classes ending with the string.', 'bricks' ),
			'globalClassManagerInfoTitle'      => esc_html__( 'How to use the global class manager', 'bricks' ),
			'globalClassManagerInfoCategory'   => esc_html__( 'Select one or multiple categories to filter your classes by them.', 'bricks' ),
			'globalClassManagerInfoClass'      => esc_html__( 'Select one or multiple classes to edit them.', 'bricks' ),
			'globalClassManagerInfoBulk'       => esc_html__( 'Press CMD/CTRL or SHIFT to select and edit multiple categories or classes.', 'bricks' ),
			'globalClassManagerInfoOrder'      => esc_html__( 'Drag any category or class up/down to order it.', 'bricks' ),
			'globalClassManagerInfoCategorize' => esc_html__( 'Categorize classes by dragging them into a specific category or into "Uncategorize" to uncategorize them.', 'bricks' ),
			'globalClassesActive'              => esc_html__( 'Active classes', 'bricks' ),
			'globalClassesImported'            => esc_html__( 'Global classes imported', 'bricks' ),
			'globalClassesEmptyDescription'    => esc_html__( 'Enter the name of your first global CSS class in the field above. Then hit enter to create it.', 'bricks' ) . ' (' . Helpers::article_link( 'global-css-classes', esc_html__( 'Learn more', 'bricks' ) ) . ')',
			'globalElements'                   => esc_html__( 'Global elements', 'bricks' ),
			'gradient'                         => esc_html__( 'Gradient', 'bricks' ),
			// translators: %s: Color stop, %s: Colors
			'gradientRepeatInfo'               => sprintf(
				esc_html__( 'Make sure to set "%1$s" in your "%2$s" definitions below.', 'bricks' ),
				esc_html__( 'Color stop', 'bricks' ),
				esc_html__( 'Colors', 'bricks' )
			),
			'gardientColorsDescription'        => esc_html__( 'Add at least two colors to create a gradient.', 'bricks' ),
			'goToSettingsPanel'                => esc_html__( 'Back to settings', 'bricks' ),
			'gotIt'                            => esc_html__( 'Got it', 'bricks' ),
			'goTo'                             => esc_html__( 'Go to', 'bricks' ),
			'grid'                             => esc_html__( 'Grid', 'bricks' ),
			'gutter'                           => esc_html__( 'Spacing', 'bricks' ),

			'hasStyles'                        => esc_html__( 'Has styles', 'bricks' ),
			'hasNoStyles'                      => esc_html__( 'Has no styles', 'bricks' ),
			'header'                           => esc_html__( 'Header', 'bricks' ),
			'height'                           => esc_html__( 'Height', 'bricks' ),
			'help'                             => esc_html__( 'Help', 'bricks' ),
			'hidden'                           => esc_html__( 'Hidden', 'bricks' ),
			'hideInfo'                         => esc_html__( 'Hide info', 'bricks' ),
			'history'                          => esc_html__( 'History', 'bricks' ),
			'historyDeleted'                   => esc_html__( 'History deleted', 'bricks' ),
			'home'                             => esc_html__( 'Home', 'bricks' ),
			'homePage'                         => esc_html__( 'Home page', 'bricks' ),
			'horizontal'                       => esc_html__( 'Horizontal', 'bricks' ),
			'howToCreateHeaderTemplate'        => esc_html__( 'How to create a header template', 'bricks' ),
			'howToCreateFooterTemplate'        => esc_html__( 'How to create a footer template', 'bricks' ),
			'html5AudioNoBrowserSupport'       => esc_html__( 'Your browser does not support the audio tag.', 'bricks' ),
			'html5VideoNoBrowserSupport'       => esc_html__( 'Your browser does not support the video tag.', 'bricks' ),
			'hue'                              => esc_html__( 'Hue', 'bricks' ),

			'icon'                             => esc_html__( 'Icon', 'bricks' ),
			'id'                               => esc_html__( 'ID', 'bricks' ),
			'ignoreStickyPosts'                => esc_html__( 'Ignore sticky posts', 'bricks' ),
			'image'                            => esc_html__( 'Image', 'bricks' ),
			'imageGalleryDescription'          => esc_html__( 'Hold down CMD/CRTL to select multiple images.', 'bricks' ),
			'imageNotFound'                    => esc_html__( 'Image not found', 'bricks' ),
			'imageSize'                        => esc_html__( 'Image size', 'bricks' ),
			'import'                           => esc_html__( 'Import', 'bricks' ),
			'importImages'                     => esc_html__( 'Import images', 'bricks' ),
			'importImagesDisabled'             => esc_html__( 'Disabled: Show placeholder images.', 'bricks' ),
			'importImagesEnabled'              => esc_html__( 'Enabled: Download template images to media library.', 'bricks' ),
			'importJsonDragAndDrop'            => esc_html__( 'Drop JSON file(s) in here', 'bricks' ),
			'imports'                          => esc_html__( 'Imports', 'bricks' ),
			'importTemplateDragAndDrop'        => esc_html__( 'Drag and drop .JSON or .ZIP template file(s) in here ..', 'bricks' ),
			'importTemplate'                   => esc_html__( 'Import template', 'bricks' ),
			'importNote'                       => esc_html__( 'Valid JSON data required to run the importer', 'bricks' ),
			'importTemplateThemeStyle'         => esc_html__( 'This template contains a theme style. Would you like to import it?', 'bricks' ),
			'importTemplateColorPalette'       => esc_html__( 'This template contains a color palette. Would you like to import it?', 'bricks' ),
			'include'                          => esc_html__( 'Include', 'bricks' ),
			'includeChildren'                  => esc_html__( 'Include children', 'bricks' ),
			'infinite'                         => esc_html__( 'Infinite', 'bricks' ),
			'infiniteScroll'                   => esc_html__( 'Infinite scroll', 'bricks' ),
			'info'                             => esc_html__( 'Info', 'bricks' ),
			'infoLightbox'                     => esc_html__( 'Customize lightbox: Settings > Theme Styles > General', 'bricks' ),
			'infoFullAccessRequired'           => esc_html__( 'Your builder access level doesn\'t allow you modify these settings.', 'bricks' ),
			'innerContainer'                   => esc_html__( 'Inner container', 'bricks' ),
			'insert'                           => esc_html__( 'Insert', 'bricks' ),
			'insertAfter'                      => esc_html__( 'Insert after', 'bricks' ),
			'insertMedia'                      => esc_html__( 'Insert media', 'bricks' ),
			'insertLayout'                     => esc_html__( 'Insert layout', 'bricks' ),
			'insertSection'                    => esc_html__( 'Insert section', 'bricks' ),
			'insertTemplate'                   => esc_html__( 'Insert template', 'bricks' ),
			'inset'                            => esc_html__( 'Inset', 'bricks' ),
			'interactionId'                    => esc_html__( 'Interaction ID', 'bricks' ),
			'interactions'                     => esc_html__( 'Interactions', 'bricks' ),
			'internal'                         => esc_html__( 'Internal post/page', 'bricks' ),
			'invert'                           => esc_html__( 'Invert', 'bricks' ),
			'isArchiveMainQuery'               => esc_html__( 'Is main query', 'bricks' ) . ' (' . esc_html__( 'Archive', 'bricks' ) . ', ' . esc_html__( 'Search', 'bricks' ) . ')',
			'isArchiveMainQueryDescription'    => esc_html__( 'Enable if your archive pagination is not working.', 'bricks' ),
			'italic'                           => esc_html__( 'Italic', 'bricks' ),
			'item'                             => esc_html__( 'Item', 'bricks' ),

			'joinUs'                           => esc_html__( 'Join Us', 'bricks' ),
			'justify'                          => esc_html__( 'Justify', 'bricks' ),
			'justifyContent'                   => esc_html__( 'Justify content', 'bricks' ),

			'key'                              => esc_html__( 'Key', 'bricks' ),
			'keyboardShortcuts'                => esc_html__( 'Keyboard shortcuts', 'bricks' ),

			'label'                            => esc_html__( 'Label', 'bricks' ),
			'language'                         => esc_html__( 'Language', 'bricks' ),
			'large'                            => esc_html__( 'Large', 'bricks' ),
			'laptop'                           => esc_html__( 'Laptop', 'bricks' ),
			'lastRefresh'                      => esc_html__( 'Last refresh', 'bricks' ),
			'latest'                           => esc_html__( 'Latest', 'bricks' ),
			'layout'                           => esc_html__( 'Layout', 'bricks' ),
			'learnMore'                        => esc_html__( 'Learn more', 'bricks' ),
			'left'                             => esc_html__( 'Left', 'bricks' ),
			'letterSpacing'                    => esc_html__( 'Letter spacing', 'bricks' ),
			'lightboxId'                       => esc_html__( 'Lightbox ID', 'bricks' ),
			'lightboxImage'                    => esc_html__( 'Lightbox Image', 'bricks' ),
			'lightboxVideo'                    => esc_html__( 'Lightbox Video', 'bricks' ),
			'lightness'                        => esc_html__( 'Lightness', 'bricks' ),
			'lineHeight'                       => esc_html__( 'Line height', 'bricks' ),
			'link'                             => esc_html__( 'Link', 'bricks' ),
			'linear'                           => esc_html__( 'Linear', 'bricks' ),
			'linked'                           => esc_html__( 'Linked', 'bricks' ),
			'list'                             => esc_html__( 'List', 'bricks' ),
			'liveSearch'                       => esc_html__( 'Live search', 'bricks' ),
			'liveSearchDescription'            => esc_html__( 'When enabled, this query only runs when a live search is performed.', 'bricks' ),
			'liveSearchInfo'                   => esc_html__( 'Provide the element ID that holds the live search results below.', 'bricks' ),
			'liveSearchWrapperSelector'        => esc_html__( 'Live search results', 'bricks' ),
			'liveSearchWrapperSelectorDesc'    => esc_html__( 'Element ID that holds the live search results. Only visible when the live search is performed.', 'bricks' ),
			'loadingTemplates'                 => esc_html__( 'Loading templates', 'bricks' ),
			'loadMore'                         => esc_html__( 'Load more', 'bricks' ),
			'lock'                             => esc_html__( 'Lock', 'bricks' ),
			'lockSelected'                     => esc_html__( 'Lock selected', 'bricks' ),
			'locked'                           => esc_html__( 'Locked', 'bricks' ),
			'lockedUserGoBack'                 => esc_html__( 'Go back', 'bricks' ),
			'lockedUserTakeOver'               => esc_html__( 'Take over', 'bricks' ),
			'lockedUserTitle'                  => esc_html__( 'This post is already being edited.', 'bricks' ),
			'lockedUserText'                   => esc_html__( '%s is currently working on this post, which means you cannot make changes, unless you take over.', 'bricks' ), // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			'login'                            => esc_html__( 'Login', 'bricks' ),
			'loop'                             => esc_html__( 'Loop', 'bricks' ),

			'linkStates'                       => [
				'unlinked'  => esc_html__( 'Unlinked', 'bricks' ),
				'opposites' => esc_html__( 'Opposites linked', 'bricks' ),
				'all'       => esc_html__( 'All sides linked', 'bricks' ),
			],

			'mainQuery'                        => esc_html__( 'Main query', 'bricks' ),
			'margin'                           => esc_html__( 'Margin', 'bricks' ),
			'masonry'                          => esc_html__( 'Masonry', 'bricks' ),
			// translators: %s: Max upload size
			'maxUploadSizeInfo'                => sprintf( esc_html__( 'Max upload size: %s', 'bricks' ), size_format( wp_max_upload_size() ) ),
			'media'                            => esc_html__( 'Media', 'bricks' ),
			'metaKey'                          => esc_html__( 'Meta key', 'bricks' ),
			'metaKeyOrder'                     => esc_html__( 'Order meta key', 'bricks' ),
			'metaValue'                        => esc_html__( 'Meta value', 'bricks' ),
			'metaValueNum'                     => esc_html__( 'Numeric meta value', 'bricks' ),
			'metaQuery'                        => esc_html__( 'Meta query', 'bricks' ),
			'menuOrder'                        => esc_html__( 'Menu order', 'bricks' ),
			'metro'                            => esc_html__( 'Metro', 'bricks' ),
			'mimeType'                         => esc_html__( 'Mime type', 'bricks' ),
			'mimeTypeDesc'                     => Helpers::article_link( 'query-loop/#media-query', esc_html__( 'Filter media by mime type', 'bricks' ) ),
			'mobile'                           => esc_html__( 'Mobile', 'bricks' ),
			'mobileFirst'                      => esc_html__( 'Mobile first', 'bricks' ),
			'mode'                             => esc_html__( 'Mode', 'bricks' ),
			'modified'                         => esc_html__( 'modified', 'bricks' ),
			'modifiedDate'                     => esc_html__( 'Modified date', 'bricks' ),
			'months'                           => [
				esc_html__( 'January', 'bricks' ),
				esc_html__( 'February', 'bricks' ),
				esc_html__( 'March', 'bricks' ),
				esc_html__( 'April', 'bricks' ),
				esc_html__( 'May', 'bricks' ),
				esc_html__( 'June', 'bricks' ),
				esc_html__( 'July', 'bricks' ),
				esc_html__( 'August', 'bricks' ),
				esc_html__( 'September', 'bricks' ),
				esc_html__( 'October', 'bricks' ),
				esc_html__( 'November', 'bricks' ),
				esc_html__( 'December', 'bricks' ),
			],
			'moreLayouts'                      => esc_html__( 'More layouts', 'bricks' ),
			'mostPopular'                      => esc_html__( 'Most Popular', 'bricks' ),
			'move'                             => esc_html__( 'Move', 'bricks' ),
			'moved'                            => esc_html__( 'Moved', 'bricks' ),
			'myTemplates'                      => esc_html__( 'My templates', 'bricks' ),
			'myAccount'                        => esc_html__( 'My account', 'bricks' ),

			'name'                             => esc_html__( 'Name', 'bricks' ),
			'new'                              => esc_html__( 'New', 'bricks' ),
			'newColorPalette'                  => esc_html__( 'New color palette name', 'bricks' ),
			'newColorPaletteCreateFirstColor'  => esc_html__( 'Add your first color to this palette by selecting a color value above and then click "Save".', 'bricks' ),
			'newImageName'                     => esc_html__( 'Type name, hit enter', 'bricks' ),
			'next'                             => esc_html__( 'Next', 'bricks' ),
			'noConditionsSet'                  => esc_html__( 'No conditions set. Click the "+" icon to add your render condition.', 'bricks' ),
			'noInteractionsSet'                => esc_html__( 'No interactions set. Click the "+" icon to add an interaction.', 'bricks' ),
			'noContent'                        => esc_html__( 'No content', 'bricks' ),
			'noSearchControlsFound'            => esc_html__( 'No matching settings found.', 'bricks' ),
			'noFileSelected'                   => esc_html__( 'No file selected.', 'bricks' ),
			'no'                               => esc_html__( 'No', 'bricks' ),
			'none'                             => esc_html__( 'None', 'bricks' ),
			'noRepeat'                         => esc_html__( 'No-repeat', 'bricks' ),
			'noResults'                        => esc_html__( 'Nothing found. Please try again with a different keyword!', 'bricks' ),
			'noResultsQuery'                   => esc_html__( 'No results', 'bricks' ),
			'noRevisions'                      => esc_html__( 'No revisions.', 'bricks' ),
			'normal'                           => esc_html__( 'Normal', 'bricks' ),
			'noDynamicDataFound'               => esc_html__( 'No dynamic data found.', 'bricks' ),
			'noTemplatesFound'                 => esc_html__( 'No templates found.', 'bricks' ),
			'notFound'                         => esc_html__( 'Not found', 'bricks' ),
			'nothingFound'                     => esc_html__( 'Nothing found.', 'bricks' ),
			'nothingToCopy'                    => esc_html__( 'Nothing to copy', 'bricks' ),
			'nothingToPaste'                   => esc_html__( 'Nothing to paste', 'bricks' ),
			'notifications'                    => [
				'autosave'        => [
					'button'      => esc_html__( 'Preview autosave', 'bricks' ),
					'description' => esc_html__( 'There is an autosave more recent than the version you are currently viewing.', 'bricks' ),
				],
				'svg'             => [
					'description' => esc_html__( 'SVG files not imported for security reasons.', 'bricks' ),
				],
				'templateBundle'  => [
					'button'      => esc_html__( 'Set template style', 'bricks' ),
					'description' => esc_html__( 'Inserted template uses theme style "%s"', 'bricks' ), // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
				],
				'populateContent' => [
					'button'      => esc_html__( 'Change content', 'bricks' ),
					'description' => esc_html__( 'Currently previewing content from "%s".', 'bricks' ), // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
				]
			],
			'number'                           => esc_html__( 'Number', 'bricks' ),
			'numberedlist'                     => esc_html__( 'Numbered list', 'bricks' ),

			'offset'                           => esc_html__( 'Offset', 'bricks' ),
			'oldest'                           => esc_html__( 'Oldest', 'bricks' ),
			'opacity'                          => esc_html__( 'Opacity', 'bricks' ),
			'openInNewTab'                     => esc_html__( 'Open in new tab', 'bricks' ),
			'or'                               => esc_html__( 'Or', 'bricks' ),
			'order'                            => esc_html__( 'Order', 'bricks' ),
			'orderBy'                          => esc_html__( 'Order by', 'bricks' ),
			'otherClasses'                     => esc_html__( 'Other classes', 'bricks' ),
			'overlay'                          => esc_html__( 'Overlay', 'bricks' ),
			'overwrite'                        => esc_html__( 'Overwrite', 'bricks' ),

			'padding'                          => esc_html__( 'Padding', 'bricks' ),
			'page'                             => esc_html__( 'Page', 'bricks' ),
			'pages'                            => esc_html__( 'Pages' ),
			'pageSettings'                     => esc_html__( 'Page settings', 'bricks' ),
			'panelRevisionsInfo'               => esc_html__( 'Select any revision to preview it. Click "Apply" to continue editing the selected revision. Click "Discard" to continue editing the current revision.', 'bricks' ),
			'parent'                           => esc_html__( 'Parent', 'bricks' ),
			'postParentId'                     => esc_html__( 'Insert post parent ID', 'bricks' ),
			'paste'                            => esc_html__( 'Paste', 'bricks' ),
			'pasted'                           => esc_html__( 'Pasted', 'bricks' ),
			'pasteStyles'                      => esc_html__( 'Paste styles', 'bricks' ),
			'paused'                           => esc_html__( 'Paused', 'bricks' ),
			'pin'                              => esc_html__( 'Pin', 'bricks' ),
			'pinnedElements'                   => esc_html__( 'Pinned elements', 'bricks' ),
			'placeholderEmptyCanvas'           => esc_html__( 'Click on any element to add it to your canvas.', 'bricks' ),
			'placeholderEmptyPopup'            => esc_html__( 'Click on any element to add it to your popup.', 'bricks' ),
			'placeholderFormMessage'           => esc_html__( 'Your message goes here. The more details, the better ;)', 'bricks' ),
			'placeholderImageInfo'             => esc_html__( 'Placeholder image shown.', 'bricks' ),
			'placeholderTitle'                 => esc_html__( 'Type title and hit enter', 'bricks' ),
			'placeholderTemplateName'          => esc_html__( 'Enter new template name', 'bricks' ),
			'placeholderSearchDocumentation'   => esc_html__( 'Search documentation', 'bricks' ),
			'placeholderSelectPost'            => esc_html__( 'Select post/page', 'bricks' ),
			'placeholderSelectLinkType'        => esc_html__( 'Select link type', 'bricks' ),
			'placeholderYourName'              => esc_html__( 'Your name (optional)', 'bricks' ),
			'playInteraction'                  => esc_html__( 'Play interaction', 'bricks' ),
			'playOnce'                         => esc_html__( 'Play once', 'bricks' ),
			'popular'                          => esc_html__( 'Popular', 'bricks' ),
			'popup'                            => esc_html__( 'Popup', 'bricks' ),
			'position'                         => esc_html__( 'Position', 'bricks' ),
			'post'                             => esc_html__( 'Post', 'bricks' ),
			'posts'                            => esc_html__( 'Posts', 'bricks' ),
			'postsOffsetDescription'           => esc_html__( 'Ignored when posts per page set to "-1".', 'bricks' ),
			'postsPerPage'                     => esc_html__( 'Posts per page', 'bricks' ),
			'postType'                         => esc_html__( 'Post type', 'bricks' ),
			// translators: %s: Link to Unsplash
			'poweredByUnsplash'                => sprintf( esc_html__( 'Powered by %s', 'bricks' ), '<a href="https://unsplash.com/?ref=bricksbuilderio" target="_blank">Unsplash</a>' ),
			'prev'                             => esc_html__( 'Prev', 'bricks' ),
			'preview'                          => esc_html__( 'Preview', 'bricks' ),
			'prefix'                           => esc_html__( 'Prefix', 'bricks' ),
			'previewMode'                      => esc_html__( 'Preview mode', 'bricks' ),
			'previewTemplate'                  => esc_html__( 'Preview template', 'bricks' ),
			'pseudoClassActive'                => esc_html__( 'Active pseudo-class', 'bricks' ),
			'pseudoClassCreated'               => esc_html__( 'Pseudo-class created', 'bricks' ),
			'pseudoClassDeleted'               => esc_html__( 'Pseudo-class deleted', 'bricks' ),
			'pseudoClassPlaceholder'           => esc_html__( 'Select or create pseudo-class', 'bricks' ),
			'pseudoClassTooltip'               => esc_html__( 'States (pseudo-classes)', 'bricks' ),
			'pseudoElementCreated'             => esc_html__( 'Pseudo-element created', 'bricks' ),
			'pseudoElementDeleted'             => esc_html__( 'Pseudo-element deleted', 'bricks' ),
			'publish'                          => esc_html__( 'Publish', 'bricks' ),
			'published'                        => esc_html__( 'Published', 'bricks' ),
			'publishedDate'                    => esc_html__( 'Published date', 'bricks' ),

			'queryEditor'                      => esc_html__( 'Query editor', 'bricks' ) . ' (PHP)',
			'queryEditorInfo'                  => sprintf(
				// translators: %s: Posts query link, %s: Terms query link, %s: Users query link
				esc_html__( 'Return query parameters in PHP array. Learn more about the query parameters for %1$s, %2$s, %3$s', 'bricks' ),
				sprintf( '<a href="https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters" target="_blank">%s</a>', esc_html__( 'Posts', 'bricks' ) ),
				sprintf( '<a href="https://developer.wordpress.org/reference/classes/wp_term_query/#source" target="_blank">%s</a>', esc_html__( 'Terms', 'bricks' ) ),
				sprintf( '<a href="https://developer.wordpress.org/reference/classes/wp_user_query/#parameters" target="_blank">%s</a>', esc_html__( 'Users', 'bricks' ) )
			),
			'queryEditorNoCodeExecutionInfo'   => esc_html__( 'Query editor in use. But not accessible due to lack of code execution rights.', 'bricks' ),
			'queryLoop'                        => esc_html__( 'Query loop', 'bricks' ),
			'quickNav'                         => esc_html__( 'Quick nav', 'bricks' ),

			'radius'                           => esc_html__( 'Radius', 'bricks' ),
			'radial'                           => esc_html__( 'Radial', 'bricks' ),
			'random'                           => esc_html__( 'Random', 'bricks' ),
			'randomSeedTtl'                    => esc_html__( 'Random seed TTL', 'bricks' ),
			'randomSeedTtlDescription'         => esc_html__( 'Time in minutes that the random seed will last. Avoid duplicate posts when using random order.', 'bricks' ),
			'raw'                              => esc_html__( 'Raw', 'bricks' ),
			'redo'                             => esc_html__( 'Redo', 'bricks' ),
			'reload'                           => esc_html__( 'Reload', 'bricks' ),
			'reloadCanvas'                     => esc_html__( 'Reload canvas', 'bricks' ),
			'remote'                           => esc_html__( 'Remote', 'bricks' ),
			'remoteTemplates'                  => esc_html__( 'Remote templates', 'bricks' ),
			'remove'                           => esc_html__( 'Remove', 'bricks' ),
			'removeFile'                       => esc_html__( 'Remove file', 'bricks' ),
			'rename'                           => esc_html__( 'Rename', 'bricks' ),
			'renameImages'                     => esc_html__( 'Rename images', 'bricks' ),
			'renameImagesDisabled'             => esc_html__( 'Disabled: Keep original image filename.', 'bricks' ),
			'renameImagesEnabled'              => esc_html__( 'Enabled: Rename image before download.', 'bricks' ),
			'linkRenderedAs'                   => esc_html__( 'Link rendered as', 'bricks' ),
			'repeat'                           => esc_html__( 'Repeat', 'bricks' ),
			'relation'                         => esc_html__( 'Relation', 'bricks' ),
			'replace'                          => esc_html__( 'Replace', 'bricks' ),
			'replaceContent'                   => esc_html__( 'Replace content', 'bricks' ),
			'replaceContentDisabled'           => esc_html__( 'Disabled: Insert below existing content.', 'bricks' ),
			'replaceContentEnabled'            => esc_html__( 'Enabled: Replace existing content with template data.', 'bricks' ),
			'replaceWith'                      => esc_html__( 'Replace with', 'bricks' ),
			'replaceWithThisString'            => esc_html__( 'Replace with this string', 'bricks' ),
			'responsiveBreakpoints'            => esc_html__( 'Responsive breakpoints', 'bricks' ),
			'reset'                            => esc_html__( 'Reset', 'bricks' ),
			'resetBreakpointsDescription'      => esc_html__( 'Resetting all breakpoints deletes all custom breakpoints and resets all default breakpoints.', 'bricks' ),
			'resetStyles'                      => esc_html__( 'Reset styles', 'bricks' ),
			'resetDynamicData'                 => esc_html__( 'Clear non-existing dynamic data', 'bricks' ),
			'results'                          => esc_html__( 'Results', 'bricks' ),
			'resultsFor'                       => esc_html__( 'results for:', 'bricks' ),
			'reverse'                          => esc_html__( 'Reverse', 'bricks' ),
			'revisionBy'                       => esc_html__( 'Revision by', 'bricks' ),
			'revisionDeleted'                  => esc_html__( 'Revision deleted', 'bricks' ),
			'revisionApplied'                  => esc_html__( 'Revision applied', 'bricks' ),
			'revisionDiscarded'                => esc_html__( 'Revision discarded', 'bricks' ),
			'revisions'                        => esc_html__( 'Revisions', 'bricks' ),
			'revisionsDeleted'                 => esc_html__( 'All revisions deleted', 'bricks' ),
			'right'                            => esc_html__( 'Right', 'bricks' ),
			'roles'                            => esc_html__( 'Roles', 'bricks' ),

			'saturation'                       => esc_html__( 'Saturation', 'bricks' ),
			'save'                             => esc_html__( 'Save', 'bricks' ),
			'saveDraft'                        => esc_html__( 'Save draft', 'bricks' ),
			'saveAsGlobalElement'              => esc_html__( 'Save as global element', 'bricks' ),
			'saveAsTemplate'                   => esc_html__( 'Save as template', 'bricks' ),
			'saveNewStyle'                     => esc_html__( 'Save new style', 'bricks' ),
			'saveStyle'                        => esc_html__( 'Save style', 'bricks' ),
			'saved'                            => esc_html__( 'Saved', 'bricks' ),
			'savedAsTemplate'                  => esc_html__( 'Saved as template', 'bricks' ),
			'scale'                            => esc_html__( 'Scale', 'bricks' ),
			'scroll'                           => esc_html__( 'Scroll', 'bricks' ),
			'searchElements'                   => esc_html__( 'Search elements ..', 'bricks' ),
			'searchFor'                        => esc_html__( 'Search for ..', 'bricks' ),
			'searchSettings'                   => esc_html__( 'Search settings', 'bricks' ),
			'search'                           => esc_html__( 'Search', 'bricks' ),
			'searchByTitle'                    => esc_html__( 'Search by title', 'bricks' ),
			'searchPages'                      => esc_html__( 'Search pages ..', 'bricks' ),
			'searchTemplates'                  => esc_html__( 'Search templates ..', 'bricks' ),
			'searchTag'                        => esc_html__( 'Search tag', 'bricks' ),
			'section'                          => esc_html__( 'Section', 'bricks' ),
			'select'                           => esc_html__( 'Select', 'bricks' ),
			'selection'                        => esc_html__( 'Selection', 'bricks' ),
			'selectColorPalette'               => esc_html__( 'Select color palette', 'bricks' ),
			'selectedClasses'                  => esc_html__( 'Selected classes', 'bricks' ),
			'setTemplateConditions'            => esc_html__( 'Set conditions', 'bricks' ),
			'selectFile'                       => esc_html__( 'Select file', 'bricks' ),
			'selectFilesToImport'              => esc_html__( 'Select file(s) to import', 'bricks' ),
			'selectIcon'                       => esc_html__( 'Select icon', 'bricks' ),
			'selectImage'                      => esc_html__( 'Select image', 'bricks' ),
			'selectLibrary'                    => esc_html__( 'Select library', 'bricks' ),
			'selectPostType'                   => esc_html__( 'Select post type', 'bricks' ),
			'selectPosts'                      => esc_html__( 'Select posts', 'bricks' ),
			'selectTaxonomies'                 => esc_html__( 'Select taxonomies', 'bricks' ),
			'selectTemplate'                   => esc_html__( 'Select template', 'bricks' ),
			'selectTemplateTags'               => esc_html__( 'Select template tags', 'bricks' ),
			'selectTemplateType'               => esc_html__( 'Select template type', 'bricks' ),
			'selectTemplateToUpdate'           => esc_html__( 'Select template to update', 'bricks' ),
			'selectTerms'                      => esc_html__( 'Select terms', 'bricks' ),
			'send'                             => esc_html__( 'Send', 'bricks' ),
			'sepia'                            => esc_html__( 'Sepia', 'bricks' ),
			'settings'                         => esc_html__( 'Settings', 'bricks' ),
			'settingsImported'                 => esc_html__( 'Settings imported', 'bricks' ),
			'settingsResetted'                 => esc_html__( 'Settings resetted', 'bricks' ),
			'solid'                            => esc_html__( 'Solid', 'bricks' ),
			'sort'                             => esc_html__( 'Sort', 'bricks' ),
			'settings'                         => esc_html__( 'Settings', 'bricks' ),
			'shape'                            => esc_html__( 'Shape', 'bricks' ),
			'showAuthor'                       => esc_html__( 'Show author', 'bricks' ),
			'showDate'                         => esc_html__( 'Show date', 'bricks' ),
			'showEmpty'                        => esc_html__( 'Show empty', 'bricks' ),
			'showExcerpt'                      => '<a href="https://codex.wordpress.org/Excerpt" target="_blank">' . esc_html__( 'Show excerpt', 'bricks' ) . '</a>',
			'showInfo'                         => esc_html__( 'Show info', 'bricks' ),
			'showFullscreen'                   => esc_html__( 'Show fullscreen', 'bricks' ),
			'showTitle'                        => esc_html__( 'Show title', 'bricks' ),
			'signAll'                          => esc_html__( 'Sign all', 'bricks' ),
			'signCode'                         => esc_html__( 'Sign code', 'bricks' ),
			'single'                           => esc_html__( 'Single', 'bricks' ),
			'site'                             => esc_html__( 'Site', 'bricks' ),
			'size'                             => esc_html__( 'Size', 'bricks' ),
			'skip'                             => esc_html__( 'Skip', 'bricks' ),
			'slide'                            => esc_html__( 'Slide', 'bricks' ),
			'slider-nested'                    => esc_html__( 'Slide', 'bricks' ), // ActionAdd.vue tooltip
			'small'                            => esc_html__( 'Small', 'bricks' ),
			'source'                           => esc_html__( 'Source', 'bricks' ),
			'spaceBetween'                     => esc_html__( 'Space between', 'bricks' ),
			'spaceAround'                      => esc_html__( 'Space around', 'bricks' ),
			'spaceEvenly'                      => esc_html__( 'Space evenly', 'bricks' ),
			'spread'                           => esc_html__( 'Spread', 'bricks' ),
			'square'                           => esc_html__( 'Square', 'bricks' ),
			'start'                            => esc_html__( 'Start', 'bricks' ),
			'startingAngle'                    => esc_html__( 'Starting angle in º', 'bricks' ),
			'startPlayAt'                      => esc_html__( 'Start play at', 'bricks' ),
			'startTime'                        => esc_html__( 'Start time', 'bricks' ),
			'stretch'                          => esc_html__( 'Stretch', 'bricks' ),
			'strike'                           => esc_html__( 'Strike', 'bricks' ),
			'strokeColor'                      => esc_html__( 'Stroke color', 'bricks' ),
			'strokeWidth'                      => esc_html__( 'Stroke width', 'bricks' ),
			'structure'                        => esc_html__( 'Structure', 'bricks' ),
			'style'                            => esc_html__( 'Style', 'bricks' ),
			'styles'                           => esc_html__( 'Styles', 'bricks' ),
			'subject'                          => esc_html__( 'Subject', 'bricks' ),
			'suffix'                           => esc_html__( 'Suffix', 'bricks' ),
			'sure'                             => esc_html__( 'Sure?', 'bricks' ),
			'switchTo'                         => esc_html__( 'Switch to', 'bricks' ),
			'svgUploadNotAllowed'              => esc_html__( 'You are not allowed to uploads SVG files.', 'bricks' ),

			'tablet'                           => esc_html__( 'Tablet', 'bricks' ),
			'tag'                              => esc_html__( 'Tag', 'bricks' ),
			'taxonomy'                         => esc_html__( 'Taxonomy', 'bricks' ),
			'taxonomies'                       => esc_html__( 'Taxonomies', 'bricks' ),
			'taxQuery'                         => esc_html__( 'Taxonomy query', 'bricks' ),
			'template'                         => esc_html__( 'Template', 'bricks' ),
			'templateBundle'                   => esc_html__( 'Template bundle', 'bricks' ),
			'templateCreated'                  => esc_html__( 'Template created', 'bricks' ),
			'templateDeleted'                  => esc_html__( 'Template deleted', 'bricks' ),
			'templateImportHint'               => esc_html__( 'Inserting any template overwrites your data. We recommend to save your changes first.', 'bricks' ),
			'templateImported'                 => esc_html__( 'Template imported', 'bricks' ),
			'templateInserted'                 => esc_html__( 'Template inserted', 'bricks' ),
			'templateInsertErrorNoData'        => esc_html__( 'Template insert failed: This template has no data', 'bricks' ),
			'templateOverridden'               => esc_html__( 'Template overridden', 'bricks' ),
			'templateReleaseAfterCancellation' => esc_html__( 'Released after you cancelled', 'bricks' ),
			'templateReleaseSinceCancellation' => esc_html__( 'new templates have been released since you cancelled Bricks.', 'bricks' ),
			'templateSaved'                    => esc_html__( 'Template saved', 'bricks' ),
			'templateSettings'                 => esc_html__( 'Template settings', 'bricks' ),
			'templateTag'                      => esc_html__( 'Template tag', 'bricks' ),
			'templateTags'                     => esc_html__( 'Template tags', 'bricks' ),
			'templateType'                     => esc_html__( 'Template type', 'bricks' ),
			'templateTypeDescription'          => esc_html__( 'Select the type of template you want to create:', 'bricks' ),
			'templates'                        => esc_html__( 'Templates', 'bricks' ),
			'terms'                            => esc_html__( 'Terms', 'bricks' ),
			'text'                             => esc_html__( 'Text', 'bricks' ),
			'textAlign'                        => esc_html__( 'Text align', 'bricks' ),
			'textDecoration'                   => esc_html__( 'Text decoration', 'bricks' ),
			'textShadow'                       => esc_html__( 'Text shadow', 'bricks' ),
			'textTransform'                    => esc_html__( 'Text transform', 'bricks' ),
			'themeStyles'                      => esc_html__( 'Theme Styles', 'bricks' ),
			'themeStyleActive'                 => esc_html__( 'Active style', 'bricks' ),
			'themeStyleActiveInfo'             => esc_html__( 'Set condition(s) to apply selected theme style to your entire website or certain areas.', 'bricks' ),
			'themeStyleNameExists'             => esc_html__( 'The style name entered already exists. Please choose a different name.', 'bricks' ),
			// translators: %s: Theme Styles link
			'themeStyleSelectInfo'             => sprintf( esc_html__( 'Select a theme style or create a new one to style your website (%s).', 'bricks' ), Helpers::article_link( 'theme-styles', esc_html__( 'learn more', 'bricks' ) ) ),
			'themeStyleCreated'                => esc_html__( 'Theme style created', 'bricks' ),
			'themeStyleDeleted'                => esc_html__( 'Theme style deleted', 'bricks' ),
			'themeStyleNewName'                => esc_html__( 'New theme style name', 'bricks' ),
			'themeStyleSaved'                  => esc_html__( 'Style saved', 'bricks' ),
			'title'                            => esc_html__( 'Title', 'bricks' ),
			'top'                              => esc_html__( 'Top', 'bricks' ),
			'topLeft'                          => esc_html__( 'Top left', 'bricks' ),
			'topCenter'                        => esc_html__( 'Top center', 'bricks' ),
			'topRight'                         => esc_html__( 'Top right', 'bricks' ),
			'thumbnail'                        => esc_html__( 'Thumbnail', 'bricks' ),
			'true'                             => esc_html__( 'True', 'bricks' ),
			'type'                             => esc_html__( 'Type', 'bricks' ),
			'typography'                       => esc_html__( 'Typography', 'bricks' ),
			'transform'                        => [
				'translateX' => esc_html__( 'Translate X', 'bricks' ),
				'translateY' => esc_html__( 'Translate Y', 'bricks' ),

				'scaleX'     => esc_html__( 'Scale X', 'bricks' ),
				'scaleY'     => esc_html__( 'Scale Y', 'bricks' ),

				'rotateX'    => esc_html__( 'Rotate X', 'bricks' ),
				'rotateY'    => esc_html__( 'Rotate Y', 'bricks' ),
				'rotateZ'    => esc_html__( 'Rotate Z', 'bricks' ),

				'skewX'      => esc_html__( 'Skew X', 'bricks' ),
				'skewY'      => esc_html__( 'Skew Y', 'bricks' ),
			],

			'underline'                        => esc_html__( 'Underline', 'bricks' ),
			'undo'                             => esc_html__( 'Undo', 'bricks' ),
			'unlink'                           => esc_html__( 'Unlink', 'bricks' ),
			'unlinked'                         => esc_html__( 'Unlinked', 'bricks' ),
			'unlock'                           => esc_html__( 'Unlock', 'bricks' ),
			'unlocked'                         => esc_html__( 'Unlocked', 'bricks' ),
			'unsplashErrorInvalidApiKey'       => esc_html__( 'Your Unsplash API key is not valid.', 'bricks' ),
			'unsplashErrorNoApiKey'            => sprintf(
				// translators: %s: API keys link
				esc_html__( 'Unsplash API key required! Add key in dashboard under: %s', 'bricks' ),
				'<a href="' . Helpers::settings_url( '#tab-api-keys' ) . '" target="_blank">Bricks > ' . esc_html__( 'Settings', 'bricks' ) . ' > API keys</a>'
			),
			'unsplashErrorRateLimitReached'    => esc_html__( 'Rate limit for this hour reached. Please wait until the next full hour for it to be resetted.', 'bricks' ),
			'unsplashSearchPlaceholder'        => esc_html__( 'Type keyword, hit enter.', 'bricks' ),

			'uncategorized'                    => esc_html__( 'Uncategorized', 'bricks' ),
			'understandingTheLayout'           => esc_html__( 'Understanding the layout', 'bricks' ),
			'unitTooltip'                      => esc_html__( 'Select/enter unit', 'bricks' ),
			'unpin'                            => esc_html__( 'Unpin', 'bricks' ),
			'unsignedCode'                     => esc_html__( 'Unsigned code', 'bricks' ),
			'unsplashSetApiKey'                => '<a href="' . Helpers::settings_url( '#tab-api-keys' ) . '" class="button" target="_blank">' . esc_html__( 'Set Unsplash API Key', 'bricks' ) . '</a>',
			'unusedOnThisPage'                 => esc_html__( 'Unused on this page', 'bricks' ),
			'update'                           => esc_html__( 'Update', 'bricks' ),
			'updated'                          => esc_html__( 'Updated', 'bricks' ),
			'uppercase'                        => esc_html__( 'Uppercase', 'bricks' ),
			'url'                              => esc_html__( 'URL', 'bricks' ),
			'usedOnThisPage'                   => esc_html__( 'Used on this page', 'bricks' ),
			'userProfile'                      => esc_html__( 'User profile', 'bricks' ),

			'vertical'                         => esc_html__( 'Vertical', 'bricks' ),
			'verticalAlignment'                => esc_html__( 'Vertical alignment', 'bricks' ),
			'video'                            => esc_html__( 'Video', 'bricks' ),
			'videoUrl'                         => esc_html__( 'Video URL', 'bricks' ),
			'viewOnFrontend'                   => esc_html__( 'View on frontend', 'bricks' ),
			'visitBricksAcademy'               => esc_html__( 'Visit Bricks Academy', 'bricks' ),
			'visitDocs'                        => esc_html__( 'Visit docs', 'bricks' ),

			'width'                            => esc_html__( 'Width', 'bricks' ),
			'woocommerce_product'              => esc_html__( 'Product', 'bricks' ),
			'wordpress'                        => esc_html__( 'WordPress', 'bricks' ),
			'wrap'                             => esc_html__( 'Wrap', 'bricks' ),
			'nowrap'                           => esc_html__( 'No wrap', 'bricks' ),
			'wrap-reverse'                     => esc_html__( 'Wrap reverse', 'bricks' ),

			'xAxis'                            => esc_html__( 'X axis', 'bricks' ),
			'yAxis'                            => esc_html__( 'Y axis', 'bricks' ),

			'yes'                              => esc_html__( 'Yes', 'bricks' ),
			'youAreMissingOut'                 => esc_html__( 'You are missing out!', 'bricks' ),

			'zIndex'                           => esc_html__( 'Z-index', 'bricks' ),
		];

		return apply_filters( 'bricks/builder/i18n', $i18n );
	}

	/**
	 * Custom save messages
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function save_messages() {
		$messages = [
			esc_html__( 'All right', 'bricks' ),
			esc_html__( 'Amazing', 'bricks' ),
			esc_html__( 'Aye', 'bricks' ),
			esc_html__( 'Beautiful', 'bricks' ),
			esc_html__( 'Brilliant', 'bricks' ),
			esc_html__( 'Champ', 'bricks' ),
			esc_html__( 'Cool', 'bricks' ),
			esc_html__( 'Congrats', 'bricks' ),
			esc_html__( 'Done', 'bricks' ),
			esc_html__( 'Excellent', 'bricks' ),
			esc_html__( 'Exceptional', 'bricks' ),
			esc_html__( 'Exquisite', 'bricks' ),
			esc_html__( 'Enjoy', 'bricks' ),
			esc_html__( 'Fantastic', 'bricks' ),
			esc_html__( 'Fine', 'bricks' ),
			esc_html__( 'Good', 'bricks' ),
			esc_html__( 'Grand', 'bricks' ),
			esc_html__( 'Impressive', 'bricks' ),
			esc_html__( 'Incredible', 'bricks' ),
			esc_html__( 'Magnificent', 'bricks' ),
			esc_html__( 'Marvelous', 'bricks' ),
			esc_html__( 'Neat', 'bricks' ),
			esc_html__( 'Nice job', 'bricks' ),
			esc_html__( 'Okay', 'bricks' ),
			esc_html__( 'Outstanding', 'bricks' ),
			esc_html__( 'Remarkable', 'bricks' ),
			esc_html__( 'Saved', 'bricks' ),
			esc_html__( 'Skillful', 'bricks' ),
			esc_html__( 'Stunning', 'bricks' ),
			esc_html__( 'Superb', 'bricks' ),
			esc_html__( 'Sure thing', 'bricks' ),
			esc_html__( 'Sweet', 'bricks' ),
			esc_html__( 'Top', 'bricks' ),
			esc_html__( 'Very well', 'bricks' ),
			esc_html__( 'Woohoo', 'bricks' ),
			esc_html__( 'Wonderful', 'bricks' ),
			esc_html__( 'Yeah', 'bricks' ),
			esc_html__( 'Yep', 'bricks' ),
			esc_html__( 'Yes', 'bricks' ),
		];

		$messages = apply_filters( 'bricks/builder/save_messages', $messages );

		return $messages;
	}

	/**
	 * Get icon font classes
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_icon_font_classes() {
		return [
			'close'         => 'ion-md-close',
			'undo'          => 'ion-ios-undo',
			'redo'          => 'ion-ios-redo',

			'arrowRight'    => 'ion-ios-arrow-forward',
			'arrowDown'     => 'ion-ios-arrow-down',
			'arrowLeft'     => 'ion-ios-arrow-back',
			'arrowUp'       => 'ion-ios-arrow-up',

			'preview'       => 'ion-ios-eye',
			'settings'      => 'ion-md-settings',
			'structure'     => 'ion-ios-albums',

			'publish'       => 'ion-ios-power',
			'templates'     => 'ion-ios-folder-open',
			'page'          => 'ion-md-document',

			'desktop'       => 'ion-md-desktop',
			'mobile'        => 'ion-md-phone-portrait',
			'globe'         => 'ion-md-globe',
			'documentation' => 'ion-ios-help-buoy',
			'panelMaximize' => 'ion-ios-qr-scanner',
			'panelMinimize' => 'ion-ios-qr-scanner',

			'add'           => 'ion-md-add',
			'addTi'         => 'ti-plus',
			'remove'        => 'ion-md-remove',
			'edit'          => 'ion-md-create',
			'clone'         => 'ion-ios-copy',
			'move'          => 'ion-md-move',
			'save'          => 'ion-md-save',
			'check'         => 'ion-md-checkmark',
			'trash'         => 'ion-md-trash',
			'trashTi'       => 'ti-trash',
			'newTab'        => 'ti-new-window',

			'brush'         => 'ion-md-brush',
			'image'         => 'ion-ios-image',
			'video'         => 'ion-md-videocam',
			'cssFilter'     => 'ion-md-color-filter',

			'faceSad'       => 'ti-face-sad',
			'heart'         => 'ion-md-heart',
			'refresh'       => 'ti-reload',
			'help'          => 'ti-help-alt',
			'helpIon'       => 'ion-md-help-circle',
			'hover'         => 'ti-hand-point-up',
			'more'          => 'ti-more-alt',
			'notifications' => 'ti-bell',
			'revisions'     => 'ion-md-time',
			'link'          => 'ion-ios-link',
			'docs'          => 'ti-agenda',
			'email'         => 'ion-ios-mail',

			'search'        => 'ti-search',
			'wordpress'     => 'ti-wordpress',

			'import'        => 'ti-import',
			'export'        => 'ti-export',
			'download'      => 'ti-download',
			'zoomIn'        => 'ti-zoom-in',
		];
	}

	/**
	 * Based on post_type or template type select the first elements category to show up on builder.
	 */
	public function get_first_elements_category( $post_id = 0 ) {
		$post_type = get_post_type( $post_id );

		// NOTE: Undocumented
		$category = apply_filters( 'bricks/builder/first_element_category', false, $post_id, $post_type );

		if ( $category ) {
			return $category;
		}

		if ( 'page' !== $post_type ) {
			return 'single';
		}

		return '';
	}

	/**
	 * Default color palette (https://www.materialui.co/colors)
	 *
	 * Only used if no custom colorPalette is saved in db.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function default_color_palette() {
		$colors = [
			// Grey
			[ 'hex' => '#f5f5f5' ],
			[ 'hex' => '#e0e0e0' ],
			[ 'hex' => '#9e9e9e' ],
			[ 'hex' => '#616161' ],
			[ 'hex' => '#424242' ],
			[ 'hex' => '#212121' ],

			// A200
			[ 'hex' => '#ffeb3b' ],
			[ 'hex' => '#ffc107' ],
			[ 'hex' => '#ff9800' ],
			[ 'hex' => '#ff5722' ],
			[ 'hex' => '#f44336' ],
			[ 'hex' => '#9c27b0' ],

			[ 'hex' => '#2196f3' ],
			[ 'hex' => '#03a9f4' ],
			[ 'hex' => '#81D4FA' ],
			[ 'hex' => '#4caf50' ],
			[ 'hex' => '#8bc34a' ],
			[ 'hex' => '#cddc39' ],
		];

		$colors = apply_filters( 'bricks/builder/color_palette', $colors );

		foreach ( $colors as $key => $color ) {
			$colors[ $key ]['id'] = Helpers::generate_random_id( false );
			// translators: %s: Color #
			$colors[ $key ]['name'] = sprintf( esc_html__( 'Color #%s', 'bricks' ), $key + 1 );
		}

		$palettes[] = [
			'id'     => Helpers::generate_random_id( false ),
			'name'   => esc_html__( 'Default', 'bricks' ),
			'colors' => $colors,
		];

		return $palettes;
	}

	/**
	 * Check permissions for a certain user to access the Bricks builder
	 *
	 * @since 1.0
	 */
	public function template_redirect() {
		// Redirect non-logged-in visitors to home page
		if ( ! is_user_logged_in() ) {
			wp_redirect( home_url() ); // @since 1.8.4
			// auth_redirect(); // redirect to login page (@pre 1.8.4)
			die;
		}

		// 1/3: Check for valid license
		$license_is_valid = License::license_is_valid();

		if ( ! $license_is_valid ) {
			wp_redirect( admin_url( 'admin.php?page=bricks-license' ) );
		}

		// 2/3: Check if current user can edit post
		if ( ! Capabilities::current_user_can_use_builder() ) {
			// Redirect users without builder capabilities back to WordPress admin area
			wp_redirect( admin_url( '/?action=edit&bricks_notice=error_role_manager' ) );
			die();
		}

		// NOTE: Don't check for template
		if ( is_home() || ( function_exists( 'is_shop' ) && is_shop() ) ) {
			return;
		}

		// 3/3: Check admin settings if post type is supported
		$current_post_type = get_post_type();

		$supported_post_types = Database::get_setting( 'postTypes', [] );

		// Bricks templates always have builder support
		if ( $current_post_type === BRICKS_DB_TEMPLATE_SLUG ) {
			$supported_post_types[] = BRICKS_DB_TEMPLATE_SLUG;
		}

		// NOTE: Undocumented
		$supported_post_types = apply_filters( 'bricks/builder/supported_post_types', $supported_post_types, $current_post_type );

		if ( ! in_array( $current_post_type, $supported_post_types ) ) {
			wp_redirect( admin_url( "/edit.php?post_type={$current_post_type}&bricks_notice=error_post_type" ) );
		}
	}

	/**
	 * Get page data for builder
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function builder_data( $post_id ) {
		$global_data              = Database::$global_data;
		$page_data                = Database::$page_data;
		$theme_styles             = Theme_Styles::$styles;
		$theme_style_active       = Theme_Styles::$active_id;
		$template_settings        = Helpers::get_template_settings( $post_id );
		$template_preview_post_id = ! empty( $template_settings['templatePreviewPostId'] ) ? $template_settings['templatePreviewPostId'] : 0;

		$load_data = [
			'breakpoints'           => Breakpoints::$breakpoints,
			'breakpointActive'      => Breakpoints::$base_key,
			'themeStyles'           => $theme_styles,
			'themeStyleActive'      => $theme_style_active,
			'pinnedElements'        => get_option( BRICKS_DB_PINNED_ELEMENTS, [] ),
			'codeExecutionEnabled'  => Helpers::code_execution_enabled(),
			'codeSignaturesEnabled' => Helpers::code_signatures_enabled(),
			'fullAccess'            => Capabilities::current_user_has_full_access(),
			'userCan'               => [
				'executeCode'  => Capabilities::current_user_can_execute_code(),
				'uploadSvg'    => Capabilities::current_user_can_upload_svg(),
				'publishPosts' => current_user_can( 'publish_posts' ),
				'publishPages' => current_user_can( 'publish_pages' ),
			],
		];

		// Add color palettes to load_data
		if ( ! empty( $global_data['colorPalette'] ) && is_array( $global_data['colorPalette'] ) ) {
			$load_data['colorPalette'] = $global_data['colorPalette'];
		} else {
			$load_data['colorPalette'] = self::default_color_palette();
		}

		// Add global classes
		if ( ! empty( $global_data['globalClasses'] ) ) {
			$load_data['globalClasses'] = $global_data['globalClasses'];
		}

		// Add global classes locked
		if ( ! empty( $global_data['globalClassesLocked'] ) ) {
			$load_data['globalClassesLocked'] = $global_data['globalClassesLocked'];
		}

		// Add global classes categories
		if ( ! empty( $global_data['globalClassesCategories'] ) ) {
			$load_data['globalClassesCategories'] = $global_data['globalClassesCategories'];
		}

		// Add pseudo classes
		if ( ! empty( $global_data['pseudoClasses'] ) ) {
			$load_data['pseudoClasses'] = $global_data['pseudoClasses'];
		} else {
			$load_data['pseudoClasses'] = [
				':hover',
				':active',
				':focus',
			];
		}

		// Add elements & global settings
		if ( ! empty( $global_data['elements'] ) ) {
			$load_data['globalElements'] = $global_data['elements'];
		}

		if ( ! empty( $global_data['settings'] ) ) {
			$load_data['globalSettings'] = $global_data['settings'];
		}

		// Add page data to load_data
		if ( ! empty( $page_data['header'] ) ) {
			$load_data['header'] = $page_data['header'];
		} else {
			// Check for header template
			$template_header_id = Database::$active_templates['header'];
			$header_template    = $template_header_id ? Database::get_data( $template_header_id, 'header' ) : Database::get_data( $post_id, 'header' );

			if ( ! empty( $header_template ) ) {
				$load_data['header'] = $header_template;

				// Sticky header, header postition etc.
				$load_data['templateHeaderSettings'] = Helpers::get_template_settings( $template_header_id );
			}
		}

		// Content
		$template_content_id = Database::$active_templates['content'];

		if ( count( $page_data['content'] ) ) {
			$load_data['content'] = $page_data['content'];
		}

		// If content still not populated, check if populated content was set to preview it
		if ( empty( $load_data['content'] ) && $template_preview_post_id ) {
			// Template preview
			$content              = get_post_meta( $template_preview_post_id, BRICKS_DB_PAGE_CONTENT, true );
			$load_data['content'] = empty( $content ) ? [] : $content;
		}

		// Last resort for getting content: WP blocks
		if ( empty( $load_data['content'] ) && Database::get_setting( 'wp_to_bricks' ) ) {
			$template_preview_post_id = $template_preview_post_id ? $template_preview_post_id : $post_id;

			// Convert Gutenberg blocks to Bricks element
			$converter           = new Blocks();
			$content_from_blocks = $converter->convert_blocks_to_bricks( $template_preview_post_id );

			if ( is_array( $content_from_blocks ) ) {
				$load_data['content'] = $content_from_blocks;

				// DEV_ONLY
				$post   = get_post( $template_content_id );
				$blocks = parse_blocks( $post->post_content );

				$load_data['blocks'] = $blocks;
			}
		}

		// Footer
		if ( ! empty( $page_data['footer'] ) ) {
			$load_data['footer'] = $page_data['footer'];
		} else {
			$template_footer_id = Database::$active_templates['footer'];

			// Check for footer template
			$footer_template = $template_footer_id ? Database::get_data( $template_footer_id, 'footer' ) : [];

			if ( ! empty( $footer_template ) ) {
				$load_data['footer'] = $footer_template;
			}
		}

		if ( ! empty( $page_data['settings'] ) ) {
			$load_data['pageSettings'] = $page_data['settings'];
		}

		// Template type
		$template_type = Templates::get_template_type( $post_id );

		// @since 1.7.1 - Default template type is 'content' (so listenHistory in builder can work properly)
		$load_data['templateType'] = ! empty( $template_type ) ? $template_type : 'content';

		// Template settings
		if ( $template_settings ) {
			$load_data['templateSettings'] = $template_settings;
		}

		// Parse elements to replace dynamic data (needed for background image)
		$template_preview_post_id = $template_preview_post_id ? $template_preview_post_id : $post_id;

		if ( $template_type !== 'header' && ! empty( $load_data['header'] ) && is_array( $load_data['header'] ) ) {
			$load_data['header'] = self::render_dynamic_data_on_elements( $load_data['header'], $template_preview_post_id );
		}

		if ( ! empty( $load_data['content'] ) && is_array( $load_data['content'] ) ) {
			$load_data['content'] = self::render_dynamic_data_on_elements( $load_data['content'], $template_preview_post_id );
		}

		if ( $template_type !== 'footer' && ! empty( $load_data['footer'] ) && is_array( $load_data['footer'] ) ) {
			$load_data['footer'] = self::render_dynamic_data_on_elements( $load_data['footer'], $template_preview_post_id );
		}

		// Generate element HTML strings in PHP for fast initial render (individual element HTML AJAX calls in builder are too slow)
		// Only load for dynamic data (not static areas)
		$load_data['elementsHtml'] = [];

		// Remove setting in builder to get 'elementsHtml' with element ID for all PHP elements (@since 1.7)
		unset( Database::$global_settings['elementAttsAsNeeded'] );

		if ( $template_type === 'header' && isset( $load_data['header'] ) && is_array( $load_data['header'] ) ) {
			$load_data['elementsHtml'] = array_merge( $load_data['elementsHtml'], self::query_content_type_for_elements_html( $load_data['header'], $template_preview_post_id ) );
		}

		if ( ! in_array( $template_type, [ 'header', 'footer' ] ) && isset( $load_data['content'] ) && is_array( $load_data['content'] ) ) {
			$load_data['elementsHtml'] = array_merge( $load_data['elementsHtml'], self::query_content_type_for_elements_html( $load_data['content'], $template_preview_post_id ) );
		}

		if ( $template_type === 'footer' && isset( $load_data['footer'] ) && is_array( $load_data['footer'] ) ) {
			$load_data['elementsHtml'] = array_merge( $load_data['elementsHtml'], self::query_content_type_for_elements_html( $load_data['footer'], $template_preview_post_id ) );
		}

		/**
		 * STEP: Pre-populate dynamic data to minimize AJAX requests on builder load
		 *
		 * Only if Bricks builder setting 'enableDynamicDataPreview' is enabled, we pre-populate.
		 *
		 * @see render_dynamic_data_on_elements
		 *
		 * @since 1.7.1
		 */
		if ( Database::get_setting( 'enableDynamicDataPreview', false ) && is_array( self::$dynamic_data ) && count( self::$dynamic_data ) ) {
			$load_data['dynamicData'] = self::$dynamic_data;
		}

		return $load_data;
	}

	/**
	 * Return array with HTML string of every single element for initial fast builder render
	 *
	 * @since 1.0
	 */
	public static function query_content_type_for_elements_html( $elements, $post_id ) {
		$elements_html = [];

		foreach ( $elements as $element ) {
			$element_name = $element['name'] ?? '';

			// Skip: Code element to prevent critical errors with code execution enabled on builder load
			if ( $element_name === 'code' ) {
				continue;
			}

			// Skip: Template element to render template inline CSS in builder
			if ( $element_name === 'template' ) {
				continue;
			}

			// STEP: Pre-populate dynamic data for all elements (@since 1.7.1)
			if ( Database::get_setting( 'enableDynamicDataPreview', false ) && ! empty( $element['settings'] ) ) {
				$settings_string = wp_json_encode( $element['settings'] );

				// Get all dynamic data tags inside element settings
				preg_match_all( '/\{([^{}"]+)\}/', $settings_string, $matches );
				$dynamic_data_tags = $matches[1];

				foreach ( $dynamic_data_tags as $dynamic_data_tag ) {
					$dynamic_data_value = \Bricks\Integrations\Dynamic_Data\Providers::render_tag( $dynamic_data_tag, $post_id );

					if ( $dynamic_data_value ) {
						self::$dynamic_data[ "{$dynamic_data_tag}" ] = $dynamic_data_value;
					}
				}
			}

			$element_class_name = isset( Elements::$elements[ $element_name ]['class'] ) ? Elements::$elements[ $element_name ]['class'] : false;

			if ( ! $element_class_name || ! class_exists( $element_class_name ) ) {
				continue;
			}

			$element_instance = new $element_class_name( $element );

			// Skip nestable elements
			if ( $element_instance->nestable ) {
				unset( $element_instance );
				continue;
			}

			// Check for and populate global element settings (@since 1.2.1)
			foreach ( Database::$global_data['elements'] as $index => $global_element ) {
				if ( ! empty( $global_element['global'] ) && ! empty( $element['global'] ) && $global_element['global'] === $element['global'] ) {
					unset( $element['settings'] );

					$element['settings'] = $global_element['settings'];
				}

				// Pre 1.2.1: Use 'id' instead of 'global' property
				elseif ( ! empty( $global_element['id'] ) && $global_element['id'] === $element['id'] ) {
					unset( $element['settings'] );

					$element['settings'] = $global_element['settings'];
				}

				// Last global element checked: The global element doesn't exist in this installation: Remove element.global property
				elseif ( $index + 1 === count( Database::$global_data['elements'] ) ) {
					unset( $element['global'] );
				}
			}

			$elements_html[ $element['id'] ] = Ajax::render_element( [ 'element' => $element ] );
		}

		return $elements_html;
	}

	/**
	 * Screens all elements and try to convert dynamic data to enhance builder experience
	 *
	 * @param array $elements
	 * @param int   $post_id
	 */
	public static function render_dynamic_data_on_elements( $elements, $post_id ) {
		if ( strpos( wp_json_encode( $elements ), 'useDynamicData' ) === false ) {
			return $elements;
		}

		foreach ( $elements as $index => $element ) {
			$elements[ $index ]['settings'] = self::render_dynamic_data_on_settings( $element['settings'], $post_id );
		}

		return $elements;
	}

	/**
	 * On the settings array, if _background exists and is set to image, get the image URL
	 * Needed when setting element background image
	 *
	 * @param array $settings
	 * @param int   $post_id
	 */
	public static function render_dynamic_data_on_settings( $settings, $post_id ) {
		// Return: Do not render dynamic data for elements inside a loop
		if ( isset( $settings['hasLoop'] ) ) {
			return $settings;
		}

		$background_image_dd_tag = ! empty( $settings['_background']['image']['useDynamicData'] ) ? $settings['_background']['image']['useDynamicData'] : false;

		if ( ! $background_image_dd_tag ) {
			return $settings;
		}

		$size     = ! empty( $settings['_background']['image']['size'] ) ? $settings['_background']['image']['size'] : BRICKS_DEFAULT_IMAGE_SIZE;
		$images   = Integrations\Dynamic_Data\Providers::render_tag( $background_image_dd_tag, $post_id, 'image', [ 'size' => $size ] );
		$image_id = ! empty( $images[0] ) ? $images[0] : false;

		if ( ! $image_id ) {
			unset( $settings['_background']['image']['id'], $settings['_background']['image']['url'] );

			return $settings;
		}

		if ( is_numeric( $image_id ) ) {
			$settings['_background']['image']['id']   = $image_id;
			$settings['_background']['image']['size'] = $size;
			$settings['_background']['image']['url']  = wp_get_attachment_image_url( $image_id, $size );
		} else {
			$settings['_background']['image']['url'] = $image_id;
		}

		return $settings;
	}


	/**
	 * Builder: Force Bricks template to avoid conflicts with other builders (Elementor PRO, etc.)
	 */
	public function template_include( $template ) {
		if ( bricks_is_builder() ) {
			$template = BRICKS_PATH . 'template-parts/builder.php';
		}

		return $template;
	}

	/**
	 * Helper function to check if a AJAX or REST API call comes from inside the builder
	 *
	 * NOTE: Use bricks_is_builder_call() to check if AJAX/REST API call inside the builder
	 *
	 * @since 1.5.5
	 *
	 * @return boolean
	 */
	public static function is_builder_call() {
		/**
		 * STEP: Builder AJAX call: Check data for 'bricks-is-builder'
		 *
		 * @since 1.5.5
		 */
		if ( bricks_is_ajax_call() ) {
			$action     = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
			$is_builder = isset( $_REQUEST['bricks-is-builder'] );

			if ( $is_builder ) {
				return true;
			}

			// Check if call action starts with 'bricks_'
			if ( strpos( $action, 'bricks_' ) === 0 ) {
				return true;
			}
		}

		/**
		 * STEP: REST API call
		 *
		 * Is default builder render.
		 */
		if ( bricks_is_rest_call() ) {
			return ! empty( $_SERVER['HTTP_X_BRICKS_IS_BUILDER'] );
		}

		/**
		 * STEP: Builder frontend preview (window opened via builder toolbar preview icon)
		 *
		 * Check needed as referrer check below is the builder.
		 *
		 * @since 1.6.2
		 */
		if ( isset( $_GET['bricks_preview'] ) ) {
			return false;
		}

		// STEP: Check query string of referer URL (@since 1.5.5)
		$referer          = ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : wp_get_referer();
		$url_parsed       = $referer ? wp_parse_url( $referer ) : '';
		$url_query_string = isset( $url_parsed['query'] ) ? $url_parsed['query'] : '';

		if ( $url_query_string && strpos( $url_query_string, 'bricks=run' ) !== false ) {
			return true;
		}

		return false;
	}
}
