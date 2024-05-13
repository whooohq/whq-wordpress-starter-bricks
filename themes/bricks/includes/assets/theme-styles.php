<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Assets_Theme_Styles {
	public function __construct() {
		/**
		 * Note: On a fresh install the theme styles don't exist so we might need to
		 *       generate the Theme Styles file on the fly on the first builder save
		 *       which will create the option for the first time (add_option)
		 *
		 * @see https://developer.wordpress.org/reference/functions/add_option/
		 */
		add_action( 'add_option_bricks_theme_styles', [ $this, 'updated' ], 10, 2 );

		add_action( 'update_option_bricks_theme_styles', [ $this, 'updated' ], 10, 2 );
	}

	/**
	 * Theme Styles updated in database: Regenerate CSS files for every theme style
	 *
	 * @since 1.3.4
	 */
	public function updated( $mix, $value ) {
		self::generate_css_file( $value );
	}

	/**
	 * Generate/delete theme style CSS files
	 *
	 * Naming convention: theme-style-{theme_style_name}.min.css
	 *
	 * @since 1.3.4
	 *
	 * @return array File names
	 */
	public static function generate_css_file( $theme_styles ) {
		$file_names = [];

		foreach ( $theme_styles as $theme_style_name => $theme_style ) {
			// NOTE: Undocumented (@since 1.5 see #2kgn9hf)
			$theme_style_name = apply_filters( 'bricks/theme_style_name', $theme_style_name, $theme_style );

			$settings      = ! empty( $theme_style['settings'] ) ? $theme_style['settings'] : false;
			$file_name     = "theme-style-$theme_style_name.min.css";
			$css_file_path = Assets::$css_dir . "/$file_name";

			// Create/update theme style CSS file
			if ( $settings ) {
				$css = Assets::generate_inline_css_theme_style( $settings );
				$css = Assets::minify_css( $css );

				$file = fopen( $css_file_path, 'w' );
				fwrite( $file, $css );
				fclose( $file );

				$file_names[] = $file_name;

				// https://academy.bricksbuilder.io/article/action-bricks-generate_css_file (@since 1.9.5)
				do_action( 'bricks/generate_css_file', 'theme-styles', $file_name );
			}

			// Delete empty theme style CSS file
			else {
				if ( file_exists( $css_file_path ) ) {
					unlink( $css_file_path );
				}
			}
		}

		// STEP: Delete theme style files for non-existing theme styles
		foreach ( glob( Assets::$css_dir . '/theme-style-*.min.css' ) as $file_path ) {
			$file_name = basename( $file_path );

			if ( ! in_array( $file_name, $file_names ) ) {
				unlink( Assets::$css_dir . "/$file_name" );
			}
		}

		return $file_names;
	}
}
