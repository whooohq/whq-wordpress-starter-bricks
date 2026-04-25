<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Autoloads plugin classes using PSR-4.
 */
class Autoloader {
	/**
	 * Handle autoloading of PHP classes
	 *
	 * @param String $class
	 * @return void
	 */
	public static function autoload( $class ) {
		if ( strpos( $class, __NAMESPACE__ ) !== 0 ) {
			return;
		}

		$class                  = substr( $class, strlen( __NAMESPACE__ ) );
		$class                  = strtolower( $class );
		$class                  = str_ireplace( '_', '-', $class );
		$file_parts             = explode( '\\', $class );
		$len                    = count( $file_parts );
		$file_parts[ $len - 1 ] = $file_parts[ $len - 1 ];
		$file                   = dirname( __FILE__ ) . implode( '/', $file_parts ) . '.php';

		if ( is_file( $file ) ) {
			require_once $file;
		}
	}

	public static function load_functions() {
		foreach ( glob( BRICKS_PATH . 'includes/utilities/*.php' ) as $filename ) {
			require_once $filename;
		}
	}

	/**
	 * Register SPL autoloader
	 *
	 * @param bool $prepend
	 */
	public static function register( $prepend = false ) {
		self::load_functions();

		spl_autoload_register( [ new self(), 'autoload' ], true, $prepend );
	}
}
