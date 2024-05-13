<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Assets_Color_Palettes {
	public function __construct() {
		/**
		 * Note: On a fresh install the color palette doesn't exist so we might need to
		 *       generate the Color Palette file on the fly on the first builder save
		 *       which will create the option for the first time (add_option)
		 *
		 * @see https://developer.wordpress.org/reference/functions/add_option/
		 */
		add_action( 'add_option_bricks_color_palette', [ $this, 'updated' ], 10, 2 );

		add_action( 'update_option_bricks_color_palette', [ $this, 'updated' ], 10, 2 );
	}

	/**
	 * Color palette database option updated: Generate/delete CSS file
	 *
	 * @since 1.3.4
	 */
	public function updated( $mix, $value ) {
		self::generate_css_file( $value );
	}

	/**
	 * Generate/delete color palettes CSS file
	 *
	 * @since 1.3.4
	 *
	 * @return void|string File name
	 */
	public static function generate_css_file( $color_palettes ) {
		$file_name     = 'color-palettes.min.css';
		$css_file_path = Assets::$css_dir . "/$file_name";

		if ( $color_palettes ) {
			$css = Assets::generate_inline_css_color_vars( $color_palettes );
			$css = Assets::minify_css( $css );

			$file = fopen( $css_file_path, 'w' );
			fwrite( $file, $css );
			fclose( $file );

			// https://academy.bricksbuilder.io/article/action-bricks-generate_css_file (@since 1.9.5)
			do_action( 'bricks/generate_css_file', 'global-color-palettes', $file_name );

			return $file_name;
		} else {
			if ( file_exists( $css_file_path ) ) {
				unlink( $css_file_path );
			}
		}
	}
}
