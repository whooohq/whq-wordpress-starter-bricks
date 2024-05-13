<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Assets_Files {
	public function __construct() {
		if ( ! bricks_is_builder() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'load_css_files' ] );
		}

		add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
		add_action( 'deleted_post', [ $this, 'deleted_post' ], 10, 2 );

		add_action( 'wp_ajax_bricks_get_css_files_list', [ $this, 'get_css_files_list' ] );
		add_action( 'wp_ajax_bricks_regenerate_css_file', [ $this, 'regenerate_css_file' ] );

		// add_action( 'upgrader_process_complete', [ $this, 'upgrader_process_complete' ], 10, 2 );
		add_action( 'upgrader_post_install', [ $this, 'upgrader_post_install' ], 10, 3 );

		// Cron job to regenerate CSS files after theme update using the code base of the uploaded, not the installed version
		add_action( 'bricks_regenerate_css_files', [ $this, 'regenerate_css_files' ] );
	}

	/**
	 * Auto-regenerate CSS files after theme update
	 *
	 * Runs after updating the theme via the one-click updater!
	 *
	 * NOTE: Not in use
	 *
	 * @since 1.8.1
	 */
	public function __upgrader_process_complete( $upgrader, $hook_extra ) { // phpcs:ignore
		if ( $hook_extra['action'] === 'update' && $hook_extra['type'] === 'theme' ) {
			$theme = wp_get_theme();

			if ( $theme->get( 'Name' ) === 'Bricks' ) {
				if ( Database::get_setting( 'cssLoading' ) === 'file' ) {
					self::schedule_css_file_regeneration();

					// Show admin notice after theme update
					update_option( BRICKS_CSS_FILES_ADMIN_NOTICE, time() );
				}
			}
		}
	}

	/**
	 * Auto-regenerate CSS files after theme update
	 *
	 * Runs after manual theme upload!
	 *
	 * @since 1.8.1
	 */
	public function upgrader_post_install( $response, $hook_extra, $result ) {
		$is_bricks = false;

		// Manual upload
		if ( isset( $hook_extra['type'] ) ? $hook_extra['type'] === 'theme' : false ) {
			$theme = wp_get_theme();

			$active_theme_name = $theme->parent() ? $theme->parent()->get( 'Name' ) : $theme->get( 'Name' );
			$is_bricks         = $active_theme_name === 'Bricks';
		}

		// One-click update
		elseif ( isset( $hook_extra['theme'] ) ? $hook_extra['theme'] === 'bricks' : false ) {
			$is_bricks = true;
		}

		if ( $is_bricks ) {
			if ( Database::get_setting( 'cssLoading' ) === 'file' ) {
				self::schedule_css_file_regeneration();

				// Show admin notice after theme update
				update_option( BRICKS_CSS_FILES_ADMIN_NOTICE, time() );
			}
		}

		return $result; // true
	}

	/**
	 * Schedule single WP cron job to regenerate CSS files after theme update (one-click & manual upload)
	 *
	 * Runs 'bricks_regenerate_css_files' after 1 second to make sure the theme is updated.
	 *
	 * @since 1.8.1
	 */
	public static function schedule_css_file_regeneration() {
		// Regenerate CSS files immediately after theme update
		if ( ! wp_next_scheduled( 'bricks_regenerate_css_files' ) ) {
			$timestamp = time() + 1;
			$hook      = 'bricks_regenerate_css_files';
			wp_schedule_single_event( $timestamp, $hook );
		}
	}

	/**
	 * Regenerate CSS files automatically after theme update
	 *
	 * @since 1.8.1
	 */
	public static function regenerate_css_files() {
		$css_files                = self::get_css_files_list( true );
		$generated_css_file_names = [];

		if ( is_array( $css_files ) ) {
			foreach ( $css_files as $index => $css_file ) {
				$file_name = self::regenerate_css_file( $css_file, $index, true );

				if ( is_array( $file_name ) ) {
					foreach ( $file_name as $name ) {
						$generated_css_file_names[] = $name;
					}
				}

				// Single post, etc.
				else {
					if ( $file_name ) {
						$generated_css_file_names[] = $file_name;
					}
				}
			}
		}

		return $generated_css_file_names;
	}

	/**
	 * Regenerate CSS file on every post save
	 *
	 * Catches Bricks builder & WordPress editor saves (CU #3kavbt2)
	 *
	 * Example: User updates a custom field like ACF color, etc. in WP editor
	 *
	 * @since 1.5.7
	 */
	public function save_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post ) ) {
			return;
		}

		if ( Database::get_setting( 'cssLoading' ) !== 'file' ) {
			return;
		}

		if ( ! Helpers::render_with_bricks( $post_id ) ) {
			return;
		}

		$area = Templates::get_template_type( $post_id );

		$elements = Database::get_data( $post_id, $area );

		self::generate_post_css_file( $post_id, $area, $elements );
	}

	/**
	 * Post deleted: Delete post CSS file
	 *
	 * @param int    $post_id The post ID.
	 * @param object $post The post object.
	 *
	 * @since 1.3.4
	 */
	public function deleted_post( $post_id, $post ) {
		$post_css_file_dir = Assets::$css_dir . "/post-$post_id.min.css";

		if ( file_exists( $post_css_file_dir ) ) {
			unlink( $post_css_file_dir );
		}
	}

	/**
	 * Frontend: Load assets (CSS & JS files) on requested page
	 *
	 * @since 1.3.4
	 */
	public function load_css_files() {
		// STEP: Color palettes
		$color_palettes_css_file_dir = Assets::$css_dir . '/color-palettes.min.css';
		$color_palettes_css_file_url = Assets::$css_url . '/color-palettes.min.css';

		if ( file_exists( $color_palettes_css_file_dir ) ) {
			wp_enqueue_style( 'bricks-color-palettes', $color_palettes_css_file_url, [], filemtime( $color_palettes_css_file_dir ) );
		}

		// STEP: Global settings "Custom CSS"
		$global_custom_css_file_dir = Assets::$css_dir . '/global-custom-css.min.css';
		$global_custom_css_file_url = Assets::$css_url . '/global-custom-css.min.css';

		if ( file_exists( $global_custom_css_file_dir ) ) {
			wp_enqueue_style( 'bricks-global-custom-css', $global_custom_css_file_url, [], filemtime( $global_custom_css_file_dir ) );
		}

		// STEP: Active theme style
		$active_theme_style = Theme_Styles::$active_id;

		if ( $active_theme_style ) {
			$theme_style_file_dir = Assets::$css_dir . "/theme-style-$active_theme_style.min.css";
			$theme_style_file_url = Assets::$css_url . "/theme-style-$active_theme_style.min.css";

			if ( file_exists( $theme_style_file_dir ) ) {
				wp_enqueue_style( "bricks-theme-style-$active_theme_style", $theme_style_file_url, [], filemtime( $theme_style_file_dir ) );
			}
		}

		// STEP: Enqueue header, content, footer, popup (= array) CSS files
		$types = [
			'header',
			'content',
			'footer',
			'popup',
		];

		foreach ( $types as $type ) {
			$template_id = Database::$active_templates[ $type ];

			// Skip enqueuing CSS file for disabled header/footer template (@since 1.7)
			if ( ( $type === 'header' || $type === 'footer' ) && Database::is_template_disabled( $type ) ) {
				continue;
			}

			// Template type 'popup' is array of template IDs
			$template_ids = is_array( $template_id ) ? $template_id : [ $template_id ];

			foreach ( $template_ids as $template_id ) {
				$css_file_dir = Assets::$css_dir . "/post-$template_id.min.css";
				$css_file_url = Assets::$css_url . "/post-$template_id.min.css";

				if ( file_exists( $css_file_dir ) ) {
					wp_enqueue_style( "bricks-post-$template_id", $css_file_url, [], filemtime( $css_file_dir ) );
				}
			}
		}

		// STEP: Load CSS files for default layouts (frontpage, single post, archive)
		if ( ! Helpers::get_bricks_data() ) {
			if (
				is_home() ||
				is_archive() ||
				is_search()
			) {
				wp_enqueue_style( 'bricks-element-posts', BRICKS_URL_ASSETS . 'css/elements/posts.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/elements/posts.min.css' ) );
				wp_enqueue_style( 'bricks-isotope' );
			} elseif ( is_single() ) {
				wp_enqueue_style( 'bricks-element-post-author', BRICKS_URL_ASSETS . 'css/elements/post-author.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/elements/post-author.min.css' ) );
				wp_enqueue_style( 'bricks-element-post-comments', BRICKS_URL_ASSETS . 'css/elements/post-comments.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/elements/post-comments.min.css' ) );
				wp_enqueue_style( 'bricks-element-post-navigation', BRICKS_URL_ASSETS . 'css/elements/post-navigation.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/elements/post-navigation.min.css' ) );
				wp_enqueue_style( 'bricks-element-post-sharing', BRICKS_URL_ASSETS . 'css/elements/post-sharing.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/elements/post-sharing.min.css' ) );
				wp_enqueue_style( 'bricks-element-post-taxonomy', BRICKS_URL_ASSETS . 'css/elements/post-taxonomy.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/elements/post-taxonomy.min.css' ) );
				wp_enqueue_style( 'bricks-element-related-posts', BRICKS_URL_ASSETS . 'css/elements/related-posts.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/elements/related-posts.min.css' ) );
				wp_enqueue_style( 'bricks-element-post-content', BRICKS_URL_ASSETS . 'css/elements/post-content.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/elements/post-content.min.css' ) );
			} elseif ( is_404() ) {
				wp_enqueue_style( 'bricks-404' );
			}
		}

		// Prepare to load CSS file for Template elements or Post Content element (with render as Bricks)
		$content_template_id = Database::$active_templates['content'];

		$content_elements = get_post_meta( $content_template_id, BRICKS_DB_PAGE_CONTENT, true );

		$this->load_content_extra_css_files( $content_elements ); // Recursive
	}

	/**
	 * Check inside template elements and post content for other CSS file needs
	 *
	 * @since 1.5.7
	 */
	public function load_content_extra_css_files( $content_elements = [] ) {
		if ( empty( $content_elements ) ) {
			return;
		}

		static $extra_files_ids = [];

		foreach ( $content_elements as $element ) {
			$check_content_id = 0;

			// STEP: Load CSS file for "Template" elements used on this page (check for content should be enough)
			$template_id = $element['name'] === 'template' && ! empty( $element['settings']['template'] ) ? $element['settings']['template'] : 0;

			if ( $template_id ) {
				$template_css_file_dir = Assets::$css_dir . "/post-$template_id.min.css";
				$template_css_file_url = Assets::$css_url . "/post-$template_id.min.css";

				// Generate template inline CSS to load template webfonts (load_webfonts)
				$template_inline_css = Assets::generate_inline_css( $template_id );

				if ( file_exists( $template_css_file_dir ) ) {
					wp_enqueue_style( "bricks-post-$template_id", $template_css_file_url, [], filemtime( $template_css_file_dir ) );
				}

				$check_content_id = $template_id;
			}

			// STEP: Check for Post Content elements (with render as Bricks) inside the content (check for content should be enough)
			if ( $element['name'] === 'post-content' && isset( $element['settings']['dataSource'] ) && $element['settings']['dataSource'] === 'bricks' ) {
				$post_id = Database::$page_data['preview_or_post_id'];

				// Do not remove this line to avoid infinite loops
				if ( get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
					$post_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );
				}

				$css_file_dir = Assets::$css_dir . "/post-$post_id.min.css";
				$css_file_url = Assets::$css_url . "/post-$post_id.min.css";

				// Generate template inline CSS to load template webfonts (load_webfonts)
				$page_inline_css = Assets::generate_inline_css( $post_id );

				if ( file_exists( $css_file_dir ) ) {
					wp_enqueue_style( "bricks-post-$post_id", $css_file_url, [], filemtime( $css_file_dir ) );
				}

				$check_content_id = $post_id;
			}

			// STEP: Check inside the template (or post-content) content elements for other template elements (@since 1.5.7)
			if ( ! empty( $check_content_id ) && ! in_array( $check_content_id, $extra_files_ids ) ) {
				$extra_files_ids[] = $check_content_id;

				$template_content = get_post_meta( $check_content_id, BRICKS_DB_PAGE_CONTENT, true );

				$this->load_content_extra_css_files( $template_content ); // Recursive
			}
		}
	}

	/**
	 * Builder: Generate page-specific CSS file (on builder save)
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content_type header/content/footer (to get correct Bricks post meta data).
	 * @param array  $elements Array of elements.
	 *
	 * @return void|string File name
	 *
	 * @since 1.3.4
	 */
	public static function generate_post_css_file( $post_id, $content_type, $elements ) {
		// Check: Only run when "CSS loading method" is set to "External Files"
		if ( Database::get_setting( 'cssLoading' ) !== 'file' ) {
			return;
		}

		// Directory doesn't exist: Create recursively
		if ( ! is_dir( Assets::$css_dir ) ) {
			$directory_created = wp_mkdir_p( Assets::$css_dir );

			if ( ! $directory_created ) {
				return;
			}
		}

		// Set the post_id
		Assets::$post_id = $post_id;

		$element_css         = '';
		$element_css_default = [];

		// STEP: Page settings
		$page_settings = get_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, true );

		if ( $page_settings ) {
			if ( ! isset( Settings::$controls['page'] ) ) {
				Settings::set_controls();
			}

			$page_settings_controls = Settings::get_controls_data( 'page' );
			$element_css           .= Assets::generate_inline_css_from_element(
				[ 'settings' => $page_settings ],
				$page_settings_controls['controls'],
				'page'
			);
		}

		// STEP: Page settings: Custom CSS
		$page_settings_css = ! empty( $page_settings['customCss'] ) ? trim( $page_settings['customCss'] ) : false;
		$page_settings_css = Helpers::parse_css( $page_settings_css );

		// Add if not already added via Assets::generate_inline_css_from_element() (@since 1.7)
		if ( $page_settings_css && strpos( $element_css, $page_settings_css ) === false ) {
			$element_css .= $page_settings_css;
		}

		// Header template settings (scrolling background, etc.)
		if ( $content_type === 'header' ) {
			$template_header_settings = Helpers::get_template_settings( $post_id );

			if ( $template_header_settings ) {
				if ( ! isset( Settings::$controls['template'] ) ) {
					Settings::set_controls();
				}

				$template_settings_controls = Settings::get_controls_data( 'template' );
				$element_css               .= Assets::generate_inline_css_from_element(
					[ 'settings' => $template_header_settings ],
					$template_settings_controls['controls'],
					'template'
				);
			}
		}

		// STEP: Load style files of every element of requested page
		if ( is_array( $elements ) ) {
			foreach ( $elements as $element ) {
				if ( ! isset( $element_css_default[ $element['name'] ] ) ) {
					$element_css_file_path = BRICKS_PATH_ASSETS . 'css/elements/' . $element['name'] . '.min.css';

					if ( file_exists( $element_css_file_path ) ) {
						$element_css_default[ $element['name'] ] = file_get_contents( $element_css_file_path );
					}
				}
			}
		}

		// STEP: Generate final elements CSS string (default styles first, then individual element styles)
		foreach ( $element_css_default as $element_name => $default_css_string ) {
			// Audio element control assets URL
			if ( $element_name === 'audio' ) {
				$default_css_string = str_replace( 'url(../svg/audio/control-', 'url(' . BRICKS_URL_ASSETS . 'svg/audio/control-', $default_css_string );
			}

			$element_css = $default_css_string . $element_css;
		}

		// STEP: Add individual element styles
		Assets::$inline_css[ $content_type ] = '';

		Assets::generate_css_from_elements( $elements, $content_type );

		$element_css .= Assets::$inline_css[ $content_type ];

		$element_css = Assets::minify_css( $element_css );

		// STEP: Update OR delete CSS files
		$file_name     = "post-$post_id.min.css";
		$css_file_path = Assets::$css_dir . "/$file_name";

		// Delete empty post CSS file
		if ( ! $element_css && file_exists( $css_file_path ) ) {
			unlink( $css_file_path );
		}

		// Create/update CSS file (fopen more performant than file_put_contents as it doesn't read the content)
		else {
			$file = fopen( $css_file_path, 'w' );
			fwrite( $file, $element_css );
			fclose( $file );

			// https://academy.bricksbuilder.io/article/action-bricks-generate_css_file (@since 1.9.5)
			do_action( 'bricks/generate_css_file', 'post', $file_name );

			return $file_name;
		}
	}

	/**
	 * Generate individual CSS file
	 *
	 * @param string $data The type of CSS file to generate: colorPalettes, themeStyles, individual post ID, etc.
	 * @param string $index The index of the CSS file to generate (e.g. 0 = colorPalettes, 1 = themeStyles, etc.).
	 * @param bool   $return Whether to return the generated CSS file name or not.
	 *
	 * Trigger 1: Click on "Regenerate CSS files" button under "CSS loading method - External Files" in Bricks settings.
	 * Trigger 2: Edit default breakpoint 'width' (@since 1.5.1)
	 * Trigger 3: CLI command: wp bricks regenerate_assets (@since 1.8.1)
	 * Trigger 4: Theme update (one-click & manual upload) (@since 1.8.1)
	 */
	public static function regenerate_css_file( $data = false, $index = false, $return = false ) {
		if ( isset( $_POST['data'] ) ) {
			$data = sanitize_text_field( $_POST['data'] );
		}

		if ( isset( $_POST['index'] ) ) {
			$index = sanitize_text_field( $_POST['index'] );
		}

		if ( $data === false || $index === false ) {
			wp_send_json_success(
				[
					'data'  => $data,
					'index' => $index,
					'abort' => true,
				]
			);
		}

		// Flush unique inline CSS so same styles in different CSS files can be generated again (@since 1.9.4)
		Assets::$unique_inline_css = [];

		$file_name = '';

		if ( $index == 0 ) {
			// STEP Directory doesn't exist: Create recursively
			if ( ! is_dir( Assets::$css_dir ) ) {
				$directory_created = wp_mkdir_p( Assets::$css_dir );

				if ( ! $directory_created ) {
					wp_send_json_error(
						[
							'abort'     => true,
							'file_name' => esc_html__( 'Your uploads directory writing permissions are insufficient.', 'bricks' ),
						]
					);
				}
			}

			// STEP: Delete all exsiting CSS files
			array_map( 'unlink', array_filter( (array) glob( Assets::$css_dir . '/*' ) ) );
		}

		switch ( $data ) {
			case 'colorPalettes':
				$file_name = Assets_Color_Palettes::generate_css_file( get_option( BRICKS_DB_COLOR_PALETTE, [] ) );
				break;

			case 'globalCustomCss':
				$file_name = Assets_Global_Custom_Css::generate_css_file( get_option( BRICKS_DB_GLOBAL_SETTINGS, [] ) );
				break;

			case 'globalElements':
				$file_name = Assets_Global_Elements::generate_css_file( get_option( BRICKS_DB_GLOBAL_ELEMENTS, [] ) );
				break;

			case 'themeStyles':
				$file_name = Assets_Theme_Styles::generate_css_file( get_option( BRICKS_DB_THEME_STYLES, [] ) );
				break;

			// Individual post
			default:
				$generating_type = 'post';
				$post_id         = $data;
				$post_type       = get_post_type( $post_id );
				$elements        = false;

				// Get content type (header, content, footer) & elements
				if ( $post_type === BRICKS_DB_TEMPLATE_SLUG ) {
					$elements = get_post_meta( $post_id, BRICKS_DB_PAGE_HEADER, true );

					if ( $elements ) {
						$content_type = 'header';
					} else {
						$elements = get_post_meta( $post_id, BRICKS_DB_PAGE_FOOTER, true );

						if ( $elements ) {
							$content_type = 'footer';
						}
					}
				}

				// No 'header', nor footer' data: Check for 'content' post meta
				if ( ! $elements ) {
					$elements = get_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT, true );

					if ( $elements ) {
						$content_type = 'content';
					}
				}

				if ( $elements && $content_type ) {
					$file_name = self::generate_post_css_file( $post_id, $content_type, $elements );

					if ( $file_name ) {
						$post_type_object = get_post_type_object( $post_type );
						$post_type        = $post_type_object ? $post_type_object->labels->singular_name : $post_type;

						$file_name .= " ($post_type)";
					}
				}
		}

		// STEP: Set installed theme version in options table (to hide admin_notice_regenerate_css_files)
		update_option( BRICKS_CSS_FILES_LAST_GENERATED, BRICKS_VERSION );

		if ( $return ) {
			return $file_name;
		}

		wp_send_json_success( [ 'file_name' => $file_name ] );
	}

	/**
	 * Return CSS files list to frontend for processing one-by-one via AJAX 'bricks_regenerate_css_file'
	 *
	 * NOTE: Generate global CSS classes inline (need to know which element(s) a global class is actually set for).
	 *
	 * @return array
	 */
	public static function get_css_files_list( $return = false ) {
		// Generic CSS files
		$list = [
			'colorPalettes',
			'globalCustomCss',
			'globalElements',
			'themeStyles',
		];

		$custom_args = [
			'lang' => '', // Polylang: Get posts of any language (@since 1.8, @see #86782d47m)
		];

		// Get all Bricks template IDs
		$template_ids = Templates::get_all_template_ids( $custom_args );
		$list         = array_merge( $list, $template_ids );

		// Get IDs of all Bricks-enabled post types
		$post_ids = Helpers::get_all_bricks_post_ids( $custom_args );
		$list     = array_merge( $list, $post_ids );

		// Add option table entry with timestamp of last CSS files generation (@since 1.8.1)
		update_option( BRICKS_CSS_FILES_LAST_GENERATED_TIMESTAMP, time() );

		if ( $return ) {
			return $list;
		}

		wp_send_json_success( $list );
	}
}
