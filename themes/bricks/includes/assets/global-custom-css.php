<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Assets_Global_Custom_Css {
	public function __construct() {
		add_action( 'add_action_bricks_global_settings', [ $this, 'added' ], 10, 2 );
		add_action( 'update_option_bricks_global_settings', [ $this, 'updated' ], 10, 3 );
	}

	public function added( $option_name, $value ) {
		self::generate_css_file( $value );
	}

	public function updated( $old_value, $value, $option_name ) {
		$old_css_loading_method = ! empty( $old_value['cssLoading'] ) ? $old_value['cssLoading'] : '';
		$new_css_loading_method = ! empty( $value['cssLoading'] ) ? $value['cssLoading'] : '';

		$global_custom_css_old = ! empty( $old_value['customCss'] ) ? $old_value['customCss'] : '';
		$global_custom_css_new = ! empty( $value['customCss'] ) ? $value['customCss'] : '';

		if ( $global_custom_css_old !== $global_custom_css_new ) {
			self::generate_css_file( $value );
		}
	}

	public static function generate_css_file( $global_settings ) {
		$global_css = ! empty( $global_settings['customCss'] ) ? trim( $global_settings['customCss'] ) : false;

		/**
		 * When saving the Bricks settings the CSS is parsed through wp_slash before calling update_option so it gets saved properly in the database.
		 *
		 * This method runs when add_option or update_option is called, so we get the value with slashes, therefore we need to unslash.
		 *
		 * @since 1.5.4 (#2zx3hnc)
		 */
		$global_css = wp_unslash( $global_css );
		$global_css = Assets::minify_css( $global_css );

		// Not is use as global custom CSS ould contain that CSS vars that we don't want to skip.
		// $global_css = Helpers::parse_css( $global_css );

		$file_name     = 'global-custom-css.min.css';
		$css_file_path = Assets::$css_dir . "/$file_name";

		if ( $global_css ) {
			$file = fopen( $css_file_path, 'w' );
			fwrite( $file, $global_css );
			fclose( $file );

			// https://academy.bricksbuilder.io/article/action-bricks-generate_css_file (@since 1.9.5)
			do_action( 'bricks/generate_css_file', 'global-custom-css', $file_name );

			return $file_name;
		} else {
			if ( file_exists( $css_file_path ) ) {
				unlink( $css_file_path );
			}
		}
	}
}
