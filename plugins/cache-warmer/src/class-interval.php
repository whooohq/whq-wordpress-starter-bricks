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
     * Constructor.
     */
    public function __construct() {

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
        if ( $interval ) {
            Utils::schedule_the_undrifting_interval(
                $interval * 60,
                Cache_Warmer::INTERVAL_HOOK_NAME,
                [
                    'check_for_nonce'    => false,
                    'start_for_interval' => true,
                ]
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
