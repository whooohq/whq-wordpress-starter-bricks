<?php
/**
 * Defines plugins constants.
 *
 * @package WP-Plugins-Core
 */

$plugin_file = realpath( __DIR__ . '/../wp-plugins-core.php' );

/*
 * The URL to the plugin.
 */
define( 'TMM_WP_PLUGINS_CORE_URL', plugin_dir_url( $plugin_file ) );

/*
 * The filesystem directory path to the plugin.
 */
define( 'TMM_WP_PLUGINS_CORE_DIR', plugin_dir_path( $plugin_file ) );

/*
 * The version of the plugin.
 */
define( 'TMM_WP_PLUGINS_CORE_VERSION', get_file_data( $plugin_file, [ 'Version' ] )[0] );

/*
 * Plugin slug.
 */
define( 'TMM_WP_PLUGINS_CORE_SLUG', dirname( TMM_WP_PLUGINS_CORE_DIR ) );

/*
 * The filename of the plugin including the path.
 */
define( 'TMM_WP_PLUGINS_CORE_FILE', $plugin_file );

/*
 * Plugin basename.
 */
define( 'TMM_WP_PLUGINS_CORE_BASENAME', plugin_basename( TMM_WP_PLUGINS_CORE_FILE ) );
