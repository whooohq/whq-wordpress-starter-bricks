<?php
/**
 * Class to handle posts warming enqueue interval.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use Exception;

/**
 * Manages Posts warming interval.
 */
final class Posts_Warming_Interval {

    /**
     * Constructor.
     */
    public function __construct() {

        // Unschedule interval action on plugin deactivation.
        register_deactivation_hook( CACHE_WARMER_FILE, [ __CLASS__, 'unschedule' ] );
    }

    /**
     * Schedules the interval action if it isn't scheduled.
     *
     *  @param int $value The new interval value. If false, uses the option.
     *
     * @throws Exception Exception.
     */
    public static function schedule( $value = false ) {
        $interval = ( $value ? $value : (int) Cache_Warmer::$options->get( 'cache-warmer-setting-posts-warming-enqueue-interval' ) ) * 60;

        if ( $interval ) {
            Utils::schedule_the_undrifting_interval(
                $interval,
                Posts_Enqueue::INTERVAL_HOOK_NAME
            );
        }
    }

    /**
     * Plugin deactivation handler.
     */
    public static function unschedule() {
        as_unschedule_all_actions( Posts_Enqueue::INTERVAL_HOOK_NAME );
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
