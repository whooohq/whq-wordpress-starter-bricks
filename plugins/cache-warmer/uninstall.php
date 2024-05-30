<?php
/**
 * Cache_Warmer Uninstall
 *
 * Deletes Cache_Warmer options and other data.
 *
 * @package Cache-Warmer
 * @since 0.0.1
 */

use Cache_Warmer\DB;

// Security check.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

require_once __DIR__ . '/data/constants.php';

// Deletes options.

$all_options      = require __DIR__ . '/data/options.php';
$settings_options = [];

foreach ( $all_options as $option => $option_data ) {
    delete_option( $option );
}

delete_option( 'cache-warmer-updating' );

// Deletes tables.

require_once __DIR__ . '/src/class-databases.php';

global $wpdb;

$tables_prefix = DB::get_tables_prefix();

foreach ( DB::get_tables() as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$tables_prefix}{$table}" ); // @codingStandardsIgnoreLine
}
