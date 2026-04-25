<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Breakpoints
 *
 * Default behavior: From largest to smallest breakpoints via max-width rules.
 *
 * Mobile-first possible via custom breakpoints:
 * Set a small breakpoint as 'base' to use min-width rules.
 *
 * Custom breakpoints @since 1.5.1
 */
class Breakpoints {
	public static $breakpoints     = [];
	public static $base_key        = 'desktop';
	public static $base_width      = 0;
	public static $is_mobile_first = false;

	public function __construct() {
		// To get localized strings for builder
		add_action( 'init', [ $this, 'init_breakpoints' ] );

		add_action( 'admin_notices', [ $this, 'admin_notice_regenerate_bricks_css_files' ] );
		add_action( 'wp_ajax_bricks_regenerate_bricks_css_files', [ $this, 'regenerate_bricks_css_files' ] );
		add_action( 'wp_ajax_bricks_update_breakpoints', [ $this, 'update_breakpoints' ] );
	}

	/**
	 * Calculate the breakpoints on init to get the proper breakpoints translations
	 *
	 * @since 1.5.1
	 */
	public static function init_breakpoints() {
		self::$breakpoints = self::get_breakpoints();
	}

	/**
	 * Automatically regenerate Bricks CSS files after theme update
	 *
	 * @since 1.5.1
	 */
	public function admin_notice_regenerate_bricks_css_files() {
		// Return: Custom breakpoints not active
		if ( ! Database::get_setting( 'customBreakpoints', false ) ) {
			return;
		}

		// STEP: Return if Bricks CSS files have been generated for this version (meaning options entry matches installed version of Bricks)
		$breakpoint_css_files_last_generated_in_version = get_option( BRICKS_BREAKPOINTS_LAST_GENERATED );

		if ( version_compare( BRICKS_VERSION, $breakpoint_css_files_last_generated_in_version, '==' ) ) {
			return;
		}

		// STEP: Regenerate Bricks CSS files for custom breakpoints (if db entry found)
		$custom_breakpoints = get_option( BRICKS_DB_BREAKPOINTS, false );

		if ( $custom_breakpoints ) {
			self::regenerate_bricks_css_files();
		}
	}

	/**
	 * Regenerate Bricks CSS files (via Bricks > Settings > General)
	 *
	 * E.g. frontend.min.css, element & woo CSS files, etc.
	 *
	 * Manual trigger: "Regenerate CSS files" button
	 * Auto trigger: After theme update (compare version number in db against current theme version)
	 *
	 * @since 1.5.1
	 */
	public static function regenerate_bricks_css_files() {
		if ( bricks_is_ajax_call() ) {
			Ajax::verify_nonce( 'bricks-nonce-admin' );
		}

		$all_breakpoints     = self::$breakpoints;
		$default_breakpoints = self::get_default_breakpoints();

		foreach ( $all_breakpoints as $breakpoint ) {
			foreach ( $default_breakpoints as $default_breakpoint ) {
				if ( $breakpoint['key'] === $default_breakpoint['key'] && $default_breakpoint['key'] !== 'desktop' ) {
					$css_files_updated = self::update_media_rule_width_in_css_files( $default_breakpoint['width'], $breakpoint['width'], $default_breakpoint['width'] );
				}
			}
		}

		$updated = update_option( BRICKS_BREAKPOINTS_LAST_GENERATED, BRICKS_VERSION );

		if ( bricks_is_ajax_call() ) {
			wp_send_json_success(
				[
					'default_breakpoints' => $default_breakpoints,
					'all_breakpoints'     => $all_breakpoints,
				]
			);
		}
	}

	/**
	 * Create breakpoint
	 *
	 * @since 1.5.1
	 */
	public function update_breakpoints() {
		Ajax::verify_nonce( 'bricks-nonce-builder' );

		// Only users with full access can update breakpoints (@since 1.5.4)
		if ( ! Capabilities::current_user_has_full_access() ) {
			wp_send_json_error( 'verify_request: Sorry, you are not allowed to perform this action.' );
		}

		$do            = ! empty( $_POST['do'] ) ? sanitize_text_field( $_POST['do'] ) : false;
		$key           = ! empty( $_POST['key'] ) ? sanitize_text_field( $_POST['key'] ) : false;
		$label         = ! empty( $_POST['label'] ) ? sanitize_text_field( $_POST['label'] ) : false;
		$icon          = ! empty( $_POST['icon'] ) ? sanitize_text_field( $_POST['icon'] ) : false;
		$base          = ! empty( $_POST['base'] ) ? sanitize_text_field( $_POST['base'] ) : false;
		$width         = ! empty( $_POST['width'] ) ? intval( $_POST['width'] ) : false;
		$width_builder = ! empty( $_POST['widthBuilder'] ) ? intval( $_POST['widthBuilder'] ) : false;

		// Format key: All lowercase letters + underscores
		$key = strtolower( $key );
		$key = str_replace( '-', '_', $key );
		$key = str_replace( ' ', '_', $key );

		if ( $do !== 'configure' ) {
			if ( ! $key ) {
				wp_send_json_error( [ 'error' => esc_html__( 'Error', 'bricks' ) . ' (key)' ] );
			}

			if ( ! $label ) {
				wp_send_json_error( [ 'error' => esc_html__( 'Error', 'bricks' ) . ' (label)' ] );
			}
		}

		$breakpoints = self::get_breakpoints();
		$index       = array_search( $key, array_column( $breakpoints, 'key' ) );
		$breakpoint  = ! empty( $breakpoints[ $index ] ) ? $breakpoints[ $index ] : [];

		switch ( $do ) {
			// STEP: Create breakpoint
			case 'create':
				// Return: 'key' already exists
				if ( is_int( $index ) ) {
					wp_send_json_error( [ 'error' => esc_html__( 'Breakpoint already exists', 'bricks' ) . ' (' . $breakpoints[ $index ]['label'] . ')' ] );
				}

				// Return: 'label' already exists
				$index = array_search( $label, array_column( $breakpoints, 'label' ) );

				if ( is_int( $index ) ) {
					wp_send_json_error( [ 'error' => esc_html__( 'Breakpoint already exists', 'bricks' ) . ' (' . $breakpoints[ $index ]['label'] . ')' ] );
				}

				// Return: 'width' already exists
				$index = array_search( $width, array_column( $breakpoints, 'width' ) );

				if ( is_int( $index ) ) {
					wp_send_json_error( [ 'error' => esc_html__( 'Breakpoint already exists', 'bricks' ) . ' (' . $breakpoints[ $index ]['label'] . ')' ] );
				}

				// Return: 'widthBuilder' already exists
				$index = array_search( $width_builder, array_column( $breakpoints, 'widthBuilder' ) );

				if ( is_int( $index ) ) {
					wp_send_json_error( [ 'error' => esc_html__( 'Breakpoint already exists', 'bricks' ) . ' (' . $breakpoints[ $index ]['label'] . ')' ] );
				}

				$new_breakpoint = [
					'key'    => $key,
					'label'  => $label,
					'width'  => $width,
					'icon'   => $icon,
					'custom' => true, // To show delete icon
				];

				// Optional: Default width builder (when switching to breakpoint)
				if ( $width_builder ) {
					$new_breakpoint['widthBuilder'] = $width_builder;
				}

				$breakpoints[] = $new_breakpoint;
				break;

			// STEP: Edit breakpoint
			case 'edit':
				if ( $label ) {
					$breakpoints[ $index ]['label'] = $label;
				}

				if ( $width ) {
					// STEP: Width of breakpoint changed
					$old_width = ! empty( $breakpoint['width'] ) ? intval( $breakpoint['width'] ) : 0;

					if ( $old_width && $old_width != $width ) {
						$default_width = ! isset( $breakpoint['custom'] ) && $old_width ? $old_width : 0;

						$css_files_updated = self::update_media_rule_width_in_css_files( $old_width, $width, $default_width );
					}

					$breakpoints[ $index ]['width'] = $width;
				}

				if ( $width_builder ) {
					$breakpoints[ $index ]['widthBuilder'] = $width_builder;
				} else {
					unset( $breakpoints[ $index ]['widthBuilder'] );
				}

				if ( $icon ) {
					$breakpoints[ $index ]['icon'] = $icon;
				}

				// Is default breakpoint: Mark as 'edited' (to show reset icon in breakpoint manager)
				if ( ! isset( $breakpoint['custom'] ) ) {
					$breakpoints[ $index ]['edited'] = true;
				}
				break;

			// STEP: Pause custom breakpoint (if it's not 'base' OR 'desktop' breakpoint as desktop holds all non-CSS settings)
			case 'pause':
				if ( $breakpoint && isset( $breakpoint['custom'] ) && ! isset( $breakpoint['base'] ) && $breakpoint['key'] !== 'desktop' ) {
					if ( isset( $breakpoint['paused'] ) ) {
						unset( $breakpoint['paused'] );
					} else {
						$breakpoint['paused'] = true;
					}

					$breakpoints[ $index ] = $breakpoint;
				}
				break;

			// STEP: Reset (default) breakpoint
			case 'reset':
				$default_breakpoints      = self::get_default_breakpoints();
				$default_breakpoint_index = array_search( $key, array_column( $default_breakpoints, 'key' ) );
				$default_breakpoint       = $default_breakpoints[ $default_breakpoint_index ];

				$css_files_updated = self::update_media_rule_width_in_css_files( $width, $default_breakpoint['width'] );

				// Resetted base breakpoint
				if ( isset( $breakpoint['base'] ) ) {
					foreach ( $breakpoints as $i => $bp ) {
						if ( $bp['key'] === 'desktop' ) {
							$breakpoints[ $i ]['base'] = true;
						}
					}
				}

				$base = false;

				$breakpoints[ $index ] = $default_breakpoint;
				break;

			// STEP: Delete (custom) breakpoint
			case 'delete':
				unset( $breakpoints[ $index ] );

				$breakpoints = array_values( $breakpoints );
				break;

			// STEP: Configure breakpoints
			case 'configure':
				$default_breakpoints = self::get_default_breakpoints();

				// Reset width for all default breakpoint @media rules (skip 'desktop' as its the default base breakpoint and has no CSS file rules)
				foreach ( $breakpoints as $breakpoint ) {
					foreach ( $default_breakpoints as $default_breakpoint ) {
						if ( $breakpoint['key'] === $default_breakpoint['key'] && $default_breakpoint['key'] !== 'desktop' ) {
							$css_files_updated = self::update_media_rule_width_in_css_files( $breakpoint['width'], $default_breakpoint['width'] );
						}
					}
				}

				// Delete breakpoints from database: Delete custom & reset default breakpoints
				$updated = delete_option( BRICKS_DB_BREAKPOINTS );

				$breakpoints = $default_breakpoints;
				break;
		}

		// STEP: Set breakpoint as 'base'
		if ( $base == 'true' ) {
			// Loop over breakpoints to unset old & set new 'base' breakpoint
			foreach ( $breakpoints as $i => $bp ) {
				if ( $bp['key'] === $key ) {
					$breakpoints[ $i ]['base'] = true;
				} else {
					unset( $breakpoints[ $i ]['base'] );
				}
			}
		}

		// Sort breakpoints (descending by 'width')
		$widths = array_column( $breakpoints, 'width' );
		array_multisort( $widths, SORT_DESC, $breakpoints );

		// Update breakpoints in database
		if ( $do !== 'configure' ) {
			$updated = update_option( BRICKS_DB_BREAKPOINTS, $breakpoints );
		}

		// Get fresh breakpoint data to update $is_mobile_first to return breakpoints in correct order
		$breakpoints = self::get_breakpoints();

		$widths = array_column( $breakpoints, 'width' );
		array_multisort( $widths, self::$is_mobile_first ? SORT_ASC : SORT_DESC, $breakpoints );

		/**
		 * STEP: Breakpoints update: Now regenerate external files!
		 *
		 * As certain element CSS files contain @media default breakpoint rules.
		 * E.g.: layout element 'flex-wrap', .pricing-tables, etc.
		 */
		if ( Database::get_setting( 'cssLoading' ) === 'file' ) {
			$css_files_list = Assets_Files::get_css_files_list( true );

			foreach ( $css_files_list as $css_file_index => $css_file ) {
				$file_name = Assets_Files::regenerate_css_file( $css_file, $css_file_index, true );
			}
		}

		wp_send_json_success(
			[
				'key'             => $key,
				'label'           => $label,
				'width'           => $width,
				'widthBuilder'    => $width_builder,
				'icon'            => $icon,
				'base'            => $base,
				'updated'         => $updated,
				'breakpoints'     => $breakpoints,
				'is_mobile_first' => self::$is_mobile_first,
			]
		);
	}

	/**
	 * Update @media rules for breakpoint in CSS files
	 *
	 * When changing default breakpoint width OR default breakpoint reset.
	 */
	public static function update_media_rule_width_in_css_files( $current_width = 0, $new_width = 0, $default_width = 0 ) {
		/**
		 * STEP: Update @media rules in all Bricks CSS files
		 *
		 * frontend CSS, elements, integrations files
		 */
		if ( $current_width && $new_width ) {
			$css_file_paths = [
				BRICKS_PATH_ASSETS . 'css/frontend.min.css', // CSS loading method: Inline styles
				BRICKS_PATH_ASSETS . 'css/frontend-light.min.css', // CSS loading method: External files
			];

			// Add element CSS files to list
			$elements_css_file_paths = glob( BRICKS_PATH_ASSETS . 'css/elements/*.min.css' );
			$css_file_paths          = array_merge( $css_file_paths, $elements_css_file_paths );

			// Add integrations CSS files to list (WooCommerce, etc.)
			$integrations_css_file_paths = glob( BRICKS_PATH_ASSETS . 'css/integrations/*.min.css' );
			$css_file_paths              = array_merge( $css_file_paths, $integrations_css_file_paths );

			foreach ( $css_file_paths as $css_file_path ) {
				$css_file_content = file_get_contents( $css_file_path );

				$css_file_content = str_replace(
					"{$current_width}px)",
					"{$new_width}px)",
					$css_file_content
				);

				// Also try updating from default width in case CSS file doesn't have breakpoint database width (e.g. after updating Bricks, etc.)
				if ( $default_width && $default_width != $new_width ) {
					$css_file_content = str_replace(
						"{$default_width}px)",
						"{$new_width}px)",
						$css_file_content
					);
				}

				$css_file_updated = file_put_contents( $css_file_path, $css_file_content );
			}
		}
	}

	/**
	 * Default breakpoints
	 *
	 * - desktop (default base breakpoint)
	 * - tablet_portrait
	 * - mobile_landscape
	 * - mobile_portrait
	 *
	 * @return Array
	 */
	public static function get_default_breakpoints() {
		return [
			[
				'base'  => true, // 'base' marks breakpoint as base breakpoint
				'key'   => 'desktop',
				'label' => esc_html__( 'Desktop', 'bricks' ),
				'width' => 1279,
				'icon'  => 'laptop',
			],
			[
				'key'   => 'tablet_portrait',
				'label' => esc_html__( 'Tablet portrait', 'bricks' ),
				'width' => 991,
				'icon'  => 'tablet-portrait',
			],
			[
				'key'   => 'mobile_landscape',
				'label' => esc_html__( 'Mobile landscape', 'bricks' ),
				'width' => 767,
				'icon'  => 'phone-landscape',
			],
			[
				'key'   => 'mobile_portrait',
				'label' => esc_html__( 'Mobile portrait', 'bricks' ),
				'width' => 478,
				'icon'  => 'phone-portrait',
			],
		];
	}

	/**
	 * Get all breakpoints (default & custom)
	 */
	public static function get_breakpoints() {
		$default_breakpoints = self::get_default_breakpoints();

		// Get breakpoints from database (default & custom)
		$breakpoints = get_option( BRICKS_DB_BREAKPOINTS, false );

		// Use default breakpoints (no breakpoints found in database OR custom breakpoints disabled)
		if ( empty( $breakpoints ) || ! is_array( $breakpoints ) || ! Database::get_setting( 'customBreakpoints', false ) ) {
			$breakpoints = $default_breakpoints;
		}

		// STEP: Get base width
		foreach ( $breakpoints as $index => $breakpoint ) {
			if ( isset( $breakpoint['base'] ) ) {
				self::$base_key   = $breakpoint['key'];
				self::$base_width = intval( $breakpoint['width'] );

				// Is mobile first: Smallest breakpoint is base breakpoint
				self::$is_mobile_first = $index === count( $breakpoints ) - 1;
			}

			// Fallback to desktop as base breakpoint
			elseif ( self::$base_width === 0 && $breakpoint['key'] === 'desktop' ) {
				self::$base_key   = $breakpoint['key'];
				self::$base_width = intval( $breakpoint['width'] );
			}
		}

		// Sort breakpoints by width
		$widths = array_column( $breakpoints, 'width' );
		array_multisort( $widths, self::$is_mobile_first ? SORT_ASC : SORT_DESC, $breakpoints );

		return $breakpoints;
	}

	/**
	 * Get breakpoint by key
	 */
	public static function get_breakpoint_by( $key = 'key', $value = '' ) {
		$index = array_search( $value, array_column( self::$breakpoints, $key ) );

		if ( is_int( $index ) ) {
			return self::$breakpoints[ $index ];
		}
	}
}
