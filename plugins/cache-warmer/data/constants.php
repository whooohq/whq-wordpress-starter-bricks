<?php
/**
 * Defines plugin constants.
 *
 * @package Cache-Warmer
 */

$plugin_file = realpath( __DIR__ . '/../cache-warmer.php' );

/*
 * The URL to the plugin.
 */
define( 'CACHE_WARMER_URL', plugin_dir_url( $plugin_file ) );

/*
 * The filesystem directory path to the plugin.
 */
define( 'CACHE_WARMER_DIR', plugin_dir_path( $plugin_file ) );

/*
 * The version of the plugin.
 */
define( 'CACHE_WARMER_VERSION', get_file_data( $plugin_file, [ 'Version' ] )[0] );

/*
 * Plugin slug.
 */
define( 'CACHE_WARMER_SLUG', dirname( CACHE_WARMER_DIR ) );

/*
 * The filename of the plugin including the path.
 */
define( 'CACHE_WARMER_FILE', $plugin_file );

/*
 * Plugin basename.
 */
define( 'CACHE_WARMER_BASENAME', plugin_basename( CACHE_WARMER_FILE ) );

/*
 * Entry URL for the scan.
 */
define( 'CACHE_WARMER_ENTRY_URL', home_url() );

/*
 * Entry URL for the scan.
 */
define( 'CACHE_WARMER_DEFAULT_UA', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36' );

/*
 * Max request timeout.
 */
define( 'CACHE_WARMER_MAX_REQUEST_TIMEOUT', 30 );
