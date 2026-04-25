<?php
/**
 * A class for actions scheduling (using Action Scheduler).
 *
 * A wrapper for Action Scheduler method.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

/**
 * Class Action_Scheduler
 */
final class Action_Scheduler {

    /**
     * DO NOT USE ANYMORE.
     *
     * I returned this method for back-compatability.
     *
     * Schedule an interval, if it wasn't scheduled yet.
     *
     * And delete duplicate intervals.
     *
     * Handles a case when duplicate actions were scheduled, and deletes a file.
     *
     * NOTE: Does nothing if Action Scheduler is not loaded.
     *
     * @param int    $timestamp           When the first instance of the job will run.
     * @param int    $interval_in_seconds How long to wait between runs.
     * @param string $hook                The hook to trigger.
     * @param array  $args Arguments to pass when the hook triggers.
     * @param string $group The group to assign this job to.
     * @param bool   $unique Whether the action should be unique.
     * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
     */
    public static function schedule_an_interval( $timestamp, $interval_in_seconds, $hook, $args = [], $group = '', $unique = false, $priority = 10 ) {
        if ( ! class_exists( 'ActionScheduler' ) ) {
            return;
        }

        /**
         * Do not run the interval for the actions which are already running.
         */

        $interval_scheduling_transient_name = 'wp-plugins-core-interval-scheduling-' . $hook;

        // Unset to save & get transients from options table instead of cache.
        $prev_obj_cache_val = wp_using_ext_object_cache( false );

        if ( 'yes' !== get_transient( $interval_scheduling_transient_name ) ) {
            set_transient( $interval_scheduling_transient_name, 'yes', MINUTE_IN_SECONDS );
        } else {
            return;
        }
        wp_using_ext_object_cache( $prev_obj_cache_val );

        $function = function() use ( $timestamp, $interval_in_seconds, $hook, $args, $group, $unique, $priority, $interval_scheduling_transient_name ) {
            // Unset to save & get transients from options table instead of cache.
            $prev_obj_cache_val = wp_using_ext_object_cache( false );

            $interval_scheduled_transient_name = 'wp-plugins-core-interval-scheduled-' . $hook;
            $interval_value                    = $interval_in_seconds . 's';

            if ( get_transient( $interval_scheduled_transient_name ) !== $interval_value ) {
                if ( false === as_next_scheduled_action( $hook, $args ? $args : null, $group ) ) {
                    as_schedule_recurring_action(
                        $timestamp,
                        $interval_in_seconds,
                        $hook,
                        $args,
                        $group,
                        $unique,
                        $priority
                    );
                } elseif ( self::is_duplicate_scheduled( $hook, $args, $group ) ) {
                    as_unschedule_all_actions( $hook, $args, $group );

                    as_schedule_recurring_action(
                        $timestamp,
                        $interval_in_seconds,
                        $hook,
                        $args,
                        $group,
                        $unique,
                        $priority
                    );
                }

                set_transient(
                    $interval_scheduled_transient_name,
                    $interval_value,
                    MINUTE_IN_SECONDS * 5
                );

                delete_transient( $interval_scheduling_transient_name );
            }

            wp_using_ext_object_cache( $prev_obj_cache_val );
        };

        if ( did_action( 'action_scheduler_init' ) ) {
            $function();
        } else {
            add_action( 'action_scheduler_init', $function );
        }
    }

    /**
     * Schedule a single action if it wasn't scheduled yet.
     *
     * @param int    $timestamp When the action will run.
     * @param string $hook      The hook to trigger.
     * @param array  $args      Arguments to pass when the hook triggers.
     * @param string $group     The group to assign this job to.
     * @param bool   $unique    Whether the action should be unique.
     * @param int    $priority  Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
     */
    public static function schedule_single_action( $timestamp, $hook, $args = [], $group = '', $unique = false, $priority = 10 ) {
        if ( ! class_exists( 'ActionScheduler' ) ) {
            return;
        }

        $function = function() use ( $timestamp, $hook, $args, $group, $unique, $priority ) {
            if ( false === as_next_scheduled_action( $hook, $args, $group ) ) {
                as_schedule_single_action(
                    $timestamp,
                    $hook,
                    $args,
                    $group,
                    $unique,
                    $priority
                );
            } elseif ( self::is_duplicate_scheduled( $hook, $args, $group ) ) {
                as_unschedule_all_actions( $hook, $args, $group );

                as_schedule_single_action(
                    $timestamp,
                    $hook,
                    $args,
                    $group,
                    $unique,
                    $priority
                );
            }
        };

        if ( did_action( 'action_scheduler_init' ) ) {
            $function();
        } else {
            add_action( 'action_scheduler_init', $function );
        }
    }

    /**
     * Check if there are duplicate scheduled actions.
     *
     * @param string $hook  Hook name of the scheduled action.
     * @param array  $args  Args that would have been passed to the job.
     * @param string $group The group the job is assigned to.
     *
     * @return bool True if duplicate actions found, false otherwise.
     */
    private static function is_duplicate_scheduled( $hook, $args = [], $group = '' ) {
        $actions = as_get_scheduled_actions(
            [
                'hook'   => $hook,
                'args'   => $args,
                'group'  => $group,
                'status' => \ActionScheduler_Store::STATUS_PENDING,
            ],
            'ids'
        );

        return count( $actions ) > 1;
    }
}
