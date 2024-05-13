<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Define constants
 *
 * @since 1.0
 */
define( 'BRICKS_VERSION', '1.9.7.1' );
define( 'BRICKS_NAME', 'Bricks' );
define( 'BRICKS_TEMP_DIR', 'bricks-temp' ); // Template import/export (JSON & ZIP)
define( 'BRICKS_PATH', trailingslashit( get_template_directory() ) );    // require_once files
define( 'BRICKS_PATH_ASSETS', trailingslashit( BRICKS_PATH . 'assets' ) );
define( 'BRICKS_URL', trailingslashit( get_template_directory_uri() ) ); // WP enqueue files
define( 'BRICKS_URL_ASSETS', trailingslashit( BRICKS_URL . 'assets' ) );
define( 'BRICKS_REMOTE_URL', 'https://bricksbuilder.io/' );
define( 'BRICKS_REMOTE_ACCOUNT', BRICKS_REMOTE_URL . 'account/' );

define( 'BRICKS_BUILDER_PARAM', 'bricks' );
define( 'BRICKS_BUILDER_IFRAME_PARAM', 'brickspreview' );
define( 'BRICKS_DEFAULT_IMAGE_SIZE', 'large' );

define( 'BRICKS_DB_PANEL_WIDTH', 'bricks_panel_width' );
define( 'BRICKS_DB_BUILDER_SCALE_OFF', 'bricks_builder_scale_off' );
define( 'BRICKS_DB_BUILDER_WIDTH_LOCKED', 'bricks_builder_width_locked' );

define( 'BRICKS_DB_COLOR_PALETTE', 'bricks_color_palette' );
define( 'BRICKS_DB_BREAKPOINTS', 'bricks_breakpoints' );
define( 'BRICKS_DB_GLOBAL_SETTINGS', 'bricks_global_settings' );
define( 'BRICKS_DB_GLOBAL_ELEMENTS', 'bricks_global_elements' );
define( 'BRICKS_DB_GLOBAL_CLASSES', 'bricks_global_classes' );
define( 'BRICKS_DB_GLOBAL_CLASSES_CATEGORIES', 'bricks_global_classes_categories' );
define( 'BRICKS_DB_GLOBAL_CLASSES_LOCKED', 'bricks_global_classes_locked' );
define( 'BRICKS_DB_PSEUDO_CLASSES', 'bricks_global_pseudo_classes' );
define( 'BRICKS_DB_PINNED_ELEMENTS', 'bricks_pinned_elements' );
define( 'BRICKS_DB_SIDEBARS', 'bricks_sidebars' );
define( 'BRICKS_DB_THEME_STYLES', 'bricks_theme_styles' );
define( 'BRICKS_DB_ADOBE_FONTS', 'bricks_adobe_fonts' );

define( 'BRICKS_DB_EDITOR_MODE', '_bricks_editor_mode' );
define( 'BRICKS_BREAKPOINTS_LAST_GENERATED', 'bricks_breakpoints_last_generated' );

define( 'BRICKS_CSS_FILES_LAST_GENERATED', 'bricks_css_files_last_generated' );
define( 'BRICKS_CSS_FILES_LAST_GENERATED_TIMESTAMP', 'bricks_css_files_last_generated_timestamp' );
define( 'BRICKS_CSS_FILES_ADMIN_NOTICE', 'bricks_css_files_admin_notice' );

define( 'BRICKS_CODE_SIGNATURES_LAST_GENERATED', 'bricks_code_signatures_last_generated' );
define( 'BRICKS_CODE_SIGNATURES_LAST_GENERATED_TIMESTAMP', 'bricks_code_signatures_last_generated_timestamp' );
define( 'BRICKS_CODE_SIGNATURES_ADMIN_NOTICE', 'bricks_code_signatures_admin_notice' );

/**
 * Syntax since 1.2 (container element)
 *
 * Pre 1.2: '_bricks_page_{$content_type}'
 */
define( 'BRICKS_DB_PAGE_HEADER', '_bricks_page_header_2' );
define( 'BRICKS_DB_PAGE_CONTENT', '_bricks_page_content_2' );
define( 'BRICKS_DB_PAGE_FOOTER', '_bricks_page_footer_2' );
define( 'BRICKS_DB_PAGE_SETTINGS', '_bricks_page_settings' );

define( 'BRICKS_DB_REMOTE_TEMPLATES', 'bricks_remote_templates' );
define( 'BRICKS_DB_TEMPLATE_SLUG', 'bricks_template' );
define( 'BRICKS_DB_TEMPLATE_TAX_BUNDLE', 'template_bundle' );
define( 'BRICKS_DB_TEMPLATE_TAX_TAG', 'template_tag' );
define( 'BRICKS_DB_TEMPLATE_TYPE', '_bricks_template_type' );
define( 'BRICKS_DB_TEMPLATE_SETTINGS', '_bricks_template_settings' );

define( 'BRICKS_DB_CUSTOM_FONTS', 'bricks_fonts' );
define( 'BRICKS_DB_CUSTOM_FONT_FACES', 'bricks_font_faces' );
define( 'BRICKS_DB_CUSTOM_FONT_FACE_RULES', 'bricks_font_face_rules' ); // @since 1.7.2

define( 'BRICKS_EXPORT_TEMPLATES', 'brick_export_templates' );

define( 'BRICKS_ADMIN_PAGE_URL_LICENSE', admin_url( 'admin.php?page=bricks-license' ) );

define( 'BRICKS_AUTH_CHECK_INTERVAL', 30 );

if ( ! defined( 'BRICKS_DEBUG ' ) ) {
	define( 'BRICKS_DEBUG', false );
}

if ( ! defined( 'BRICKS_MAX_REVISIONS_TO_KEEP' ) ) {
	define( 'BRICKS_MAX_REVISIONS_TO_KEEP', 100 );
}

/**
 * Multisite constants
 *
 * @since 1.0
 */

// Global data: Color palette
if ( ! defined( 'BRICKS_MULTISITE_USE_MAIN_SITE_COLOR_PALETTE' ) ) {
	define( 'BRICKS_MULTISITE_USE_MAIN_SITE_COLOR_PALETTE', false );
}

// Global data: Global classes
if ( ! defined( 'BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES' ) ) {
	define( 'BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES', false );
}

// Global data: Global classes categories
if ( ! defined( 'BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES_CATEGORIES' ) ) {
	define( 'BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES_CATEGORIES', false );
}

// Global data: Global elements
if ( ! defined( 'BRICKS_MULTISITE_USE_MAIN_SITE_GLOBAL_ELEMENTS' ) ) {
	define( 'BRICKS_MULTISITE_USE_MAIN_SITE_GLOBAL_ELEMENTS', false );
}

/**
 * Use minified assets when SCRIPT_DEBUG is off
 *
 * @since 1.0
 */
if ( BRICKS_DEBUG || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
	define( 'BRICKS_ASSETS_SUFFIX', '' );
} else {
	define( 'BRICKS_ASSETS_SUFFIX', '.min' );
}

/**
 * Admin notice if PHP version is older than 5.4
 *
 * Required due to: array shorthand, array dereferencing etc.
 *
 * @since 1.0
 */
if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
	require_once BRICKS_PATH . 'includes/init.php';
} else {
	add_action(
		'admin_notices',
		function() {
			// translators: %s: PHP version number
			$message = sprintf( esc_html__( 'Bricks requires PHP version %s+.', 'bricks' ), '5.4' );
			$html    = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
			echo wp_kses_post( $html );
		}
	);
}

/**
 * Builder check
 *
 * @since 1.0
 */
function bricks_is_builder() {
	return ( ! is_admin() && isset( $_GET[ BRICKS_BUILDER_PARAM ] ) );
}

function bricks_is_builder_iframe() {
	return ( bricks_is_builder() && isset( $_GET[ BRICKS_BUILDER_IFRAME_PARAM ] ) );
}

function bricks_is_builder_main() {
	return ( bricks_is_builder() && ! isset( $_GET[ BRICKS_BUILDER_IFRAME_PARAM ] ) );
}

function bricks_is_frontend() {
	return ! bricks_is_builder();
}

/**
 * Is AJAX call check
 *
 * @since 1.0
 */
function bricks_is_ajax_call() {
	return defined( 'DOING_AJAX' ) && DOING_AJAX;
}

/**
 * Is WP REST API call check
 *
 * @since 1.5
 */
function bricks_is_rest_call() {
	return defined( 'REST_REQUEST' ) && REST_REQUEST;
}

/**
 * Is builder call (AJAX OR REST API)
 *
 * @since 1.5
 */
function bricks_is_builder_call() {
	// Use PHP constant BRICKS_IS_BUILDER @since 1.5.5 to perform builder check logic only once
	if ( ! defined( 'BRICKS_IS_BUILDER' ) ) {
		define( 'BRICKS_IS_BUILDER', \Bricks\Builder::is_builder_call() );
	}

	return BRICKS_IS_BUILDER;
}


/**
 * Render dynamic data tags inside of a content string
 *
 * Example: Inside an executing Code element, custom plugin, etc.
 *
 * Academy: https://academy.bricksbuilder.io/article/function-bricks_render_dynamic_data/
 *
 * @since 1.5.5
 *
 * @param string $content The content (including dynamic data tags).
 * @param int    $post_id The post ID.
 * @param string $context text, image, link, etc.
 *
 * @return string
 */
function bricks_render_dynamic_data( $content, $post_id = 0, $context = 'text' ) {
	return \Bricks\Integrations\Dynamic_Data\Providers::render_content( $content, $post_id, $context );
}
