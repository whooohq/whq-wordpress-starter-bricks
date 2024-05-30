<?php
/**
 * Utils.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use WP_Plugins_Core\Action_Scheduler;

/**
 * Class Utils.
 */
final class Utils {

    /**
     * Merge 2 arrays recursively, without creating duplicates for the same values.
     *
     * Usually, used to merge 2 trees.
     *
     * @param array $array1 Array 1.
     * @param array $array2 Array 2.
     *
     * Source: @see https://stackoverflow.com/a/25712428.
     *
     * @return array
     */
    public static function array_merge_recursive_ex( array $array1, array $array2 ) {
        $merged = $array1;

        foreach ( $array2 as $key => & $value ) {
            if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
                $merged[ $key ] = self::array_merge_recursive_ex( $merged[ $key ], $value );
            } elseif ( is_numeric( $key ) ) {
                if ( ! in_array( $value, $merged, true ) ) {
                    $merged[] = $value;
                }
            } else {
                $merged[ $key ] = $value;
            }
        }

        return $merged;
    }

    /**
     * Get the latest post data.
     *
     * @param int $post_id Post ID.
     *
     * @return object
     */
    public static function get_post_latest_data( $post_id ) {
        global $wpdb;

        $table_name = DB::get_tables_prefix() . 'warm_ups_logs';

        $query = $wpdb->prepare(
            "
                SELECT * FROM $table_name
                WHERE log_post_id = %d
                ORDER BY `id` DESC LIMIT 1
            ",
            $post_id
        );

        $post_data = $wpdb->get_row( $query ); // @codingStandardsIgnoreLine

        return (object) [
            'log_date'            => $post_data ? $post_data->log_date : '-',
            'log_extra'           => $post_data ? $post_data->log_extra : '-',
            'log_depth'           => $post_data ? $post_data->log_depth : '-',
            /* translators: %s is the number of seconds. */
            'log_time_spent'      => $post_data ? sprintf( __( '%s s', 'cache-warmer' ), $post_data->log_time_spent ) : '-',
            /* translators: %s is the number of seconds. */
            'log_time_afterwards' => $post_data ? sprintf( __( '%s s', 'cache-warmer' ), $post_data->log_time_afterwards ) : '-',
        ];
    }

    /**
     * Schedule the undrifting interval.
     *
     * @param int    $interval_in_seconds How long to wait between runs.
     * @param string $hook                The hook to trigger.
     * @param array  $args                Arguments to pass when the hook triggers.
     */
    public static function schedule_the_undrifting_interval( $interval_in_seconds, $hook, $args = [] ) {
        $actions = as_get_scheduled_actions(
            [
                'hook'           => $hook,
                'status'         => \ActionScheduler_Store::STATUS_PENDING,
                'posts_per_page' => 1,
            ],
            'ids'
        );

        if ( ! $actions ) {
            $current_time = time();

            $option_name = "cache-warmer-interval-$hook-next-run-timestamp";

            $prev_next_run_time = get_option( $option_name );

            if ( ! $prev_next_run_time ) {
                $next_run_time = $current_time + $interval_in_seconds;
            } elseif ( $prev_next_run_time < $current_time ) {
                $next_run_time = $prev_next_run_time + $interval_in_seconds;

                while ( $next_run_time < $current_time ) {
                    $next_run_time += $interval_in_seconds;
                }
            } else {
                $next_run_time = $prev_next_run_time;
            }

            update_option( $option_name, $next_run_time );

            Action_Scheduler::schedule_single_action(
                $next_run_time,
                $hook,
                $args
            );
        }
    }
}
