<?php
/**
 * A class to manage migrations.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

/**
 * Class Migrations.
 */
final class Migrations {

    /**
     * Constructor.
     *
     * @param int $last_handled_version_update Last handled version update.
     */
    public function __construct( $last_handled_version_update ) {
        if ( ! $last_handled_version_update ) { // No need to migrate for the fresh installation.
            return;
        }

        $methods_raw = array_values(
            array_filter(
                get_class_methods( $this ),
                function ( $method ) {
                    return str_starts_with( $method, 'v_' );
                }
            )
        );
        sort( $methods_raw );

        $versions_with_methods = [];
        foreach ( $methods_raw as $method ) {
            $version                           = str_replace( '_', '.', str_replace( 'v_', '', $method ) );
            $versions_with_methods[ $version ] = $method;
        }

        $versions_to_run_migrators_for = array_filter(
            array_keys( $versions_with_methods ),
            function ( $method ) use ( $last_handled_version_update ) {
                return 1 === version_compare( $method, $last_handled_version_update );
            }
        );

        foreach ( $versions_to_run_migrators_for as $version_to_run_migrator_for ) {
            $this->{$versions_with_methods[ $version_to_run_migrator_for ]}();
        }
    }

    /**
     * Fix intervals (run with arguments).
     */
    private function v_1_0_50() {
        add_action(
            'init',
            function() {
                $prev_interval = Cache_Warmer::$options->get( 'setting-interval' );
                Cache_Warmer::$options->delete( 'setting-interval' );
                Cache_Warmer::$options->set( 'setting-interval', $prev_interval );
            }
        );
    }

    /**
     * Migrate user agent.
     */
    private function v_1_0_55() {

        // User-Agent setting.

        $ua_setting = get_option( 'cache-warmer-setting-user-agent' );

        if ( $ua_setting ) {
            update_option( 'cache-warmer-setting-user-agents', [ [ 'value' => $ua_setting ] ] );
        }

        delete_option( 'cache-warmer-setting-user-agent' );

        /**
         * A constant migration to disable the logging.
         *
         * @see https://wordpress.org/support/topic/option-to-disable-logs/
         */

        if (
            defined( 'CACHE_WARMER_LOG_ENABLED' ) &&
            false === CACHE_WARMER_LOG_ENABLED
        ) {
            update_option( 'cache-warmer-setting-for-how-many-days-to-keep-the-logs', 0 );
        }
    }

    /**
     * Fix the depth.
     *
     * Increase the current depth by one.
     */
    private function v_1_1_9() {
        update_option(
            'cache-warmer-setting-depth',
            get_option( 'cache-warmer-setting-depth' ) + 1
        );
    }

    /**
     * Fix the scheduled interval.
     */
    private function v_1_3_4() {
        $function = function() {
            as_unschedule_all_actions( Cache_Warmer::INTERVAL_HOOK_NAME );
            Intervals_Scheduler::fix_missing_intervals();
        };

        if ( did_action( 'action_scheduler_init' ) ) {
            $function();
        } else {
            add_action( 'action_scheduler_init', $function );
        }
    }

    /**
     * Fix the scheduled interval.
     */
    private function v_1_3_8() {
        $function = function() {
            as_unschedule_all_actions( 'cache_warmer_fix_missing_intervals' );
        };

        if ( did_action( 'action_scheduler_init' ) ) {
            $function();
        } else {
            add_action( 'action_scheduler_init', $function );
        }
    }
}
