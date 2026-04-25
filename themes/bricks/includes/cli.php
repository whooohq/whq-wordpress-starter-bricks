<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP CLI commands for Bricks
 *
 * https://wp-cli.org/
 *
 * @since 1.8.1
 */
class CLI {
	public function __construct() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			// https://make.wordpress.org/cli/handbook/references/internal-api/wp-cli-add-command/
			\WP_CLI::add_command( 'bricks', '\Bricks\CLI' );
		}
	}

	public function regenerate_assets() {
		if ( Database::get_setting( 'cssLoading' ) !== 'file' ) {
			\WP_CLI::warning( 'EXIT: CSS loading method set to "Inline styles"' );
			return;
		}

		$generated_css_file_names = [];
		$css_files                = Assets_Files::get_css_files_list( true );

		if ( is_array( $css_files ) ) {
			foreach ( $css_files as $index => $css_file ) {
				$file_name = Assets_Files::regenerate_css_file( $css_file, $index, true );

				// Theme styles
				if ( is_array( $file_name ) ) {
					foreach ( $file_name as $name ) {
						if ( $name ) {
							$generated_css_file_names[] = $name;
							\WP_CLI::success( 'Generated CSS file: ' . $name );
						}
					}
				}

				// Single post, etc.
				else {
					if ( $file_name ) {
						$generated_css_file_names[] = $file_name;
						\WP_CLI::success( 'Generated CSS file: ' . $file_name );
					}
				}
			}
		}

		\WP_CLI::log( 'DONE! Generated CSS files: ' . count( $generated_css_file_names ) );
	}
}
