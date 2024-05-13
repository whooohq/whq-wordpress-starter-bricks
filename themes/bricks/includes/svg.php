<?php
namespace Bricks;

use Bricks\Integrations\Svg_Sanitizer\Allowed_Tags as Allowed_Tags;
use Bricks\Integrations\Svg_Sanitizer\Allowed_Attributes as Allowed_Attributes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Svg {
	/**
	 * Enable SVGs uploads
	 *
	 * https://enshrined.co.uk/2018/04/29/securing-svg-uploads-in-wordpress/
	 */
	public function __construct() {
		add_filter( 'upload_mimes', [ $this, 'svg_enable_upload' ] );
		add_filter( 'wp_check_filetype_and_ext', [ $this, 'disable_real_mime_check' ], 10, 4 );
		add_filter( 'wp_get_attachment_image_src', [ $this, 'svg_one_pixel_fix' ], 10, 4 );

		// Tries to sanitize a SVG file
		add_filter( 'wp_handle_upload_prefilter', [ $this, 'maybe_sanitize_svg' ] );
	}

	/**
	 * Enable SVG uploads
	 *
	 * @since 1.0
	 */
	public function svg_enable_upload( $mimes ) {
		if ( ! Capabilities::current_user_can_upload_svg() ) {
			return $mimes;
		}

		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * Disable real MIME check (introduced in WordPress 4.7.1)
	 *
	 * https://wordpress.stackexchange.com/a/252296/44794
	 *
	 * @since 1.0
	 */
	public function disable_real_mime_check( $data, $file, $filename, $mimes ) {
		global $wp_version;

		$filetype = wp_check_filetype( $filename, $mimes );

		return [
			'ext'             => $filetype['ext'],
			'type'            => $filetype['type'],
			'proper_filename' => $data['proper_filename']
		];
	}

	/**
	 * Remove img width and height attributes for SVG files, which are set to 1px
	 *
	 * @since 1.0
	 */
	public function svg_one_pixel_fix( $image, $attachment_id, $size, $icon ) {
		if ( get_post_mime_type( $attachment_id ) == 'image/svg+xml' ) {
			$image['1'] = false;
			$image['2'] = false;
		}

		return $image;
	}

	public function maybe_sanitize_svg( $file ) {
		if ( empty( $file['type'] ) || $file['type'] !== 'image/svg+xml' ) {
			return $file;
		}

		// NOTE: Undocumented. Bypass the svg sanitization process
		$bypass_sanitization = apply_filters( 'bricks/svg/bypass_sanitization', false, $file );

		if ( ! $bypass_sanitization ) {
			// Load the sanitizer
			self::load_libraries();

			if ( ! $this->sanitize( $file['tmp_name'] ) ) {
				$file['error'] = __( 'File not uploaded due to a sanitization error. Please verify the SVG file or get in touch.', 'bricks' );
			}
		}

		return $file;
	}

	/**
	 * Uses https://github.com/darylldoyle/svg-sanitizer library
	 *
	 * @param array $file
	 */
	protected function sanitize( $file ) {
		$sanitizer = new \enshrined\svgSanitize\Sanitizer();
		$sanitizer->minify( true );

		$file_content = file_get_contents( $file );
		$is_gzipped   = $this->is_file_gzipped( $file_content );

		if ( $is_gzipped ) {
			$file_content = gzdecode( $file_content );

			if ( $file_content === false ) {
				return false;
			}
		}

		// These two classes add hooks to filter tags and attributes
		$sanitizer->setAllowedTags( new Allowed_Tags() );
		$sanitizer->setAllowedAttrs( new Allowed_Attributes() );

		$file_clean = $sanitizer->sanitize( $file_content );

		if ( $file_clean === false ) {
			return false;
		}

		// Zip file if needed
		if ( $is_gzipped ) {
			$file_clean = gzencode( $file_clean );
		}

		file_put_contents( $file, $file_clean );

		return true;
	}


	/**
	 * Checks if content is gzipped
	 *
	 * @param string $contents
	 *
	 * @return boolean
	 */
	protected function is_file_gzipped( $contents ) {
		if ( function_exists( 'mb_strpos' ) ) {
			return mb_strpos( $contents, "\x1f\x8b\x08" ) === 0;
		} else {
			return strpos( $contents, "\x1f\x8b\x08" ) === 0;
		}
	}

	public static function load_libraries() {
		require_once BRICKS_PATH . 'includes/integrations/svg-sanitizer/library/vendor/autoload.php';
	}
}
