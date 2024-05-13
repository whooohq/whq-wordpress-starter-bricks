<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Maintenance {
	private static $instance    = null;
	private static $mode        = false;
	private static $template_id = false;

	public function __construct() {
		self::$mode = Database::get_setting( 'maintenanceMode' );

		if ( self::$mode ) {
			// Admin area: Add maintenance mode indicator to admin bar
			if ( is_admin() ) {
				add_action( 'admin_bar_menu', [ $this, 'add_maintenance_mode_indicator_to_admin_bar' ], 100 );
			}

			// Run on priority 9 (before Database::set_active_templates is called)
			add_action( 'wp', [ $this, 'apply_maintenance_mode' ], 9 );
		}
	}

	/**
	 * Get the current maintenance mode
	 */
	public static function get_mode() {
		return self::$mode;
	}

	/**
	 * Initialize and return the Maintenance class
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new Maintenance();
		}

		return self::$instance;
	}

	/**
	 * Determine whether or not to enforce maintenance mode
	 */
	public function apply_maintenance_mode() {
		// Return: Is admin OR builder
		if ( is_admin() || bricks_is_builder() ) {
			return;
		}

		$login_page_id = Database::get_setting( 'login_page' );

		// Return: Is login page
		if ( $login_page_id && $login_page_id == get_the_ID() ) {
			return;
		}

		// Return: User not allowed to bypass maintenance mode
		if ( Capabilities::current_user_can_bypass_maintenance_mode() ) {
			return;
		}

		// STEP: Serve the maintenance page content instead of the requested page
		if ( self::$mode === 'maintenance' ) {
			status_header( 503 );
		} else {
			status_header( 200 );
		}

		$maintenance_template_id = Database::get_setting( 'maintenanceTemplate' );

		// Use default maintenance page if no maintenance template is selected
		if ( ! $maintenance_template_id ) {
			$this->get_default_maintenance_page_html();
			exit;
		}

		// Ensure the maintenance template is published and has Bricks data
		$maintenance_template = Helpers::get_all_bricks_post_ids(
			[
				'p'              => $maintenance_template_id,
				'post_status'    => 'publish',
				'post_type'      => BRICKS_DB_TEMPLATE_SLUG,
				'posts_per_page' => 1,
			]
		);

		// Use default maintenance page if the maintenance template is not published or has no Bricks data
		if ( empty( $maintenance_template ) ) {
			$this->get_default_maintenance_page_html();
			exit;
		}

		// Use maintenance template. Set the template ID
		self::$template_id = $maintenance_template_id;

		// Reset the active templates
		Database::$active_templates = [
			'header'  => 0,
			'footer'  => 0,
			'content' => 0,
			'section' => 0,
			'archive' => 0,
			'error'   => 0,
			'search'  => 0,
			'popup'   => [],
		];

		// NOTE: Set the active templates
		add_filter( 'bricks/active_templates', [ $this, 'set_user_maintenance_template' ], 9999, 3 );
	}

	/**
	 * Flag whether or not a user custom template should be used
	 * After checking user capabilities and maintenance mode
	 * Should use only after 'wp' action 'apply_maintenance_mode' method
	 *
	 * @since 1.9.5
	 */
	public static function use_custom_template() {
		return self::$template_id && self::$mode;
	}

	public function set_user_maintenance_template( $active_templates ) {
		if ( ! self::use_custom_template() ) {
			return $active_templates;
		}

		// Disable the header, footer
		$active_templates['header']  = 0;
		$active_templates['footer']  = 0;
		$active_templates['search']  = 0;
		$active_templates['archive'] = 0;
		$active_templates['error']   = 0;

		// Set as a single content template
		$active_templates['content_type'] = 'content';
		$active_templates['content']      = self::$template_id;

		// Replace the global query with the maintenance template
		$GLOBALS['wp_query'] = new \WP_Query(
			[
				'p'              => self::$template_id,
				'post_type'      => 'bricks_template',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			]
		);

		// Replace the global post with the maintenance template
		$GLOBALS['post'] = $GLOBALS['wp_query']->post;

		return $active_templates;
	}

	/**
	 * Default maintenance page HTML
	 *
	 * Use when no Bricks template set while maintenance mode is active or template has no Bricks data.
	 */
	private function get_default_maintenance_page_html() {
		$title = self::$mode === 'maintenance' ? esc_html__( 'Maintenance', 'bricks' ) : esc_html__( 'Coming soon', 'bricks' );
		?>
		<!DOCTYPE html>
		<html lang="<?php echo esc_attr( substr( get_locale(), 0, 2 ) ); ?>">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo $title; ?></title>
			<style>
				body {
					display: flex;
					justify-content: center;
					align-items: center;
					height: 100vh;
					font-family: Arial, sans-serif;
				}
			</style>
		</head>
		<body>
			<h1><?php echo $title; ?></h1>
		</body>
		</html>
		<?php
	}

	public function add_maintenance_mode_indicator_to_admin_bar( $admin_bar ) {
		$title  = esc_html__( 'Mode', 'bricks' ) . ': ';
		$title .= self::$mode === 'maintenance' ? esc_html__( 'Maintenance', 'bricks' ) : esc_html__( 'Coming soon', 'bricks' );

		$admin_bar->add_node(
			[
				'id'    => 'bricks_maintenance_mode_notice',
				'title' => $title,
				'href'  => admin_url( 'admin.php?page=bricks-settings#tab-maintenance' ),
				'meta'  => [
					'title' => 'Bricks > ' . esc_html__( 'Settings', 'bricks' ) . ' > ' . esc_html__( 'Maintenance mode', 'bricks' ),
					'class' => 'maintenance-mode-active'
				],
			]
		);
	}
}
