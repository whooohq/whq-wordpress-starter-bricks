<?php
/**
 * A class to extend WP CLI with custom Cache Warmer commands.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use WP_CLI;

/**
 * Extends WP_CLI with commands.
 */
final class Extend_WP_CLI {

    /**
     * Constructor.
     */
    public function __construct() {
        WP_CLI::add_command( 'cache-warmer start', [ $this, 'start' ] );
        WP_CLI::add_command( 'cache-warmer stop', [ $this, 'stop' ] );

        add_action( 'cache-warmer-start-from-cli', [ '\Cache_Warmer\AJAX', 'start_warm_up' ] );
        add_action( 'cache-warmer-stop-from-cli', [ '\Cache_Warmer\AJAX', 'stop_warm_up' ] );
    }

    /**
     * Start the cache warm-up process.
     *
     * @when after_wp_load
     */
    public function start() {
        $do = function() {
            as_enqueue_async_action( 'cache-warmer-start-from-cli', [ false ] );
            WP_CLI::success( __( 'Cache warm-up process scheduled to start.', 'cache-warmer' ) );
        };

        if ( did_action( 'action_scheduler_init' ) ) {
            $do();
        } else {
            add_action( 'action_scheduler_init', $do );
        }
    }

    /**
     * Stop the cache warm-up process.
     *
     * @when after_wp_load
     */
    public function stop() {
        $do = function() {
            as_enqueue_async_action( 'cache-warmer-stop-from-cli', [ false ] );
            WP_CLI::success( __( 'Cache warm-up process scheduled to stop.', 'cache-warmer' ) );
        };

        if ( did_action( 'action_scheduler_init' ) ) {
            $do();
        } else {
            add_action( 'action_scheduler_init', $do );
        }
    }
}
