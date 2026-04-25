<?php
/**
 * Plugin Name: WP Plugins Core
 * Description: A core library for TMM WordPress plugins which contains common APIs.
 * Version:     0.1.52
 * Text Domain: tmm-wp-plugins-core
 * Author:      TMM Technology
 * Author URI:  https://tmm.ventures/
 * Requires PHP: 7.4
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

if ( function_exists( 'add_action' ) ) {
    if ( ! class_exists( 'WP_Plugins_Core\Versions', false ) ) {
        require_once __DIR__ . '/src/core-init/class-versions.php';
        add_action( 'plugins_loaded', [ 'WP_Plugins_Core\Versions', 'initialize_latest_version' ], 1, 0 );
    }

    add_action(
        'plugins_loaded',
        function() {
            Versions::instance()->register(
                get_file_data( __FILE__, [ 'Version' ] )[0],
                function() {
                    if ( ! class_exists( 'WP_Plugins_Core\WP_Plugins_Core', false ) ) {
                        require_once __DIR__ . '/src/class-wp-plugins-core.php';
                    }
                }
            );
        },
        0,
        0
    );
}
