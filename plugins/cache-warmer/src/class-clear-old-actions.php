<?php
/**
 * Class to handle clearing of old Action Scheduler actions.
 *
 * Schedule an action to clear the table once per day for tasks from this plugin that are:
 *
 * - Complete or canceled longer than 2 days ago.
 * - Failed longer than 1 month ago.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Manages clearing of old Action Scheduler actions.
 */
final class Clear_Old_Actions {

    /**
     * Interval hook name.
     */
    const INTERVAL_HOOK_NAME = 'cache_warmer_clear_old_actions';

    /**
     * Constructor.
     */
    public function __construct() {

        // Unschedule interval action on plugin deactivation.
        register_deactivation_hook( CACHE_WARMER_FILE, [ $this, 'unschedule' ] );

        // Clear old actions.
        add_action( self::INTERVAL_HOOK_NAME, [ $this, 'clear' ] );
    }

    /**
     * Schedules the interval action if it isn't scheduled.
     *
     * @throws Exception Exception.
     */
    public static function schedule() {
        Utils::schedule_the_undrifting_interval(
            DAY_IN_SECONDS,
            self::INTERVAL_HOOK_NAME
        );
    }

    /**
     * Clears old actions from the Action Scheduler.
     */
    public static function clear() {
        global $wpdb;

        // Create a DateTime object for the cutoff date for complete and canceled actions.
        $cutoff_date_complete = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
        $cutoff_date_complete->modify( '-2 days' );
        $formatted_cutoff_complete = $cutoff_date_complete->format( 'Y-m-d H:i:s' );

        // Create a DateTime object for the cutoff date for failed actions.
        $cutoff_date_failed = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
        $cutoff_date_failed->modify( '-1 month' );
        $formatted_cutoff_failed = $cutoff_date_failed->format( 'Y-m-d H:i:s' );

        // Directly delete from the Action Scheduler logs tables.
        $wpdb->query(
            $wpdb->prepare(
                "
                    DELETE logs, actions 
                    FROM {$wpdb->prefix}actionscheduler_logs as logs 
                    JOIN {$wpdb->prefix}actionscheduler_actions as actions ON logs.action_id = actions.action_id 
                    WHERE actions.hook IN ('cache_warmer_process', 'cache_warmer_process_posts_enqueue') AND 
                    ((actions.status IN ('complete', 'canceled') AND actions.scheduled_date_gmt <= %s) OR 
                    (actions.status = 'failed' AND actions.scheduled_date_gmt <= %s))
                ",
                $formatted_cutoff_complete,
                $formatted_cutoff_failed
            )
        );
    }


    /**
     * Plugin deactivation handler.
     */
    public static function unschedule() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME );
    }
}
