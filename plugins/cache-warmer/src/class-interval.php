<?php
/**
 * Class to handle interval.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use Exception;

/**
 * Manages Interval.
 */
final class Interval {

    /**
     * Option name that contains the schedule time of the main Cache Warmer interval.
     */
    const INTERVAL_SCHEDULE_TIME_OPTION_NAME = 'cache-warmer-main-interval-schedule-time';

    /**
     * Constructor.
     */
    public function __construct() {
        // Schedule the interval.
        add_action( 'init', [ __CLASS__, 'schedule' ] );

        // Unschedule interval action on plugin deactivation.
        register_deactivation_hook( CACHE_WARMER_FILE, [ __CLASS__, 'unschedule' ] );
    }

    /**
     * Schedules the interval action if it isn't scheduled.
     *
     * @param int $value The new interval value. If false, uses the option.
     *
     * @throws Exception Exception.
     */
    public static function schedule( $value = false ) {
        $interval = $value ? $value : (int) Cache_Warmer::$options->get( 'cache-warmer-setting-interval' );
        if (
            $interval > 0
        ) {
            Utils::schedule_the_undrifting_interval(
                $interval * 60,
                Cache_Warmer::INTERVAL_HOOK_NAME,
                [ 'start_for_interval' => true ]
            );
        }
    }

    /**
     * Plugin deactivation handler.
     */
    public static function unschedule() {
        as_unschedule_all_actions( Cache_Warmer::INTERVAL_HOOK_NAME );
    }

    /**
     * Handles the interval changes.
     *
     * @param int $value The new interval value.
     *
     * @throws Exception Exception.
     */
    public static function handle_interval_change( $value ) {
        self::unschedule();
        self::schedule( $value );
    }
}
