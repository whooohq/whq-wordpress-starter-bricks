<?php
/**
 * A class for warm-up summary.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

/**
 * Handles logging of the warm-ups.
 */
final class Summary {

    /**
     * Returns average page load time for warm-up id.
     *
     * @param string $warmed_at Warm-up to get the data for.
     *
     * @return float|bool Average loading time or false if no logs for the warm-up.
     */
    public static function get_warm_up_page_average_load_time( $warmed_at ) {
        global $wpdb;

        $logs_table = DB::get_tables_prefix() . 'warm_ups_logs';
        $list_table = DB::get_tables_prefix() . 'warm_ups_list';

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                    ROUND(AVG($logs_table.log_time_spent), 2)
                FROM
                    $logs_table
                INNER JOIN
                    $list_table ON $logs_table.list_id=$list_table.id
                WHERE
                    $list_table.warmed_at = %s AND
                    $logs_table.log_visit_type != 'after' AND
                    $logs_table.log_time_spent IS NOT NULL
            ",
                $warmed_at
            ),
            ARRAY_N
        );

        return $result ? $result[0] : false;
    }

    /**
     * Returns final page average load time for warm-up ID after the warming.
     *
     * @param string $warmed_at Warm-up to get the data for.
     *
     * @return float|bool Average loading time or false if no logs for the warm-up.
     */
    public static function get_warm_up_page_average_load_time_afterwards( $warmed_at ) {
        global $wpdb;

        $logs_table = DB::get_tables_prefix() . 'warm_ups_logs';
        $list_table = DB::get_tables_prefix() . 'warm_ups_list';

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT ROUND(AVG(avg_log_time), 2)
                FROM (
                    SELECT
                        $logs_table.log_time_afterwards as avg_log_time
                    FROM
                        $logs_table
                    INNER JOIN
                        $list_table ON $logs_table.list_id=$list_table.id
                    WHERE
                        $list_table.warmed_at = %s AND
                        $logs_table.log_visit_type != 'after' AND
                        $logs_table.log_time_spent IS NOT NULL AND
                        $logs_table.log_time_afterwards IS NOT NULL

                    UNION ALL

                    SELECT
                        $logs_table.log_time_spent as avg_log_time
                    FROM
                        $logs_table
                    INNER JOIN
                        $list_table ON $logs_table.list_id=$list_table.id
                    WHERE
                        $list_table.warmed_at = %s AND
                        $logs_table.log_visit_type = 'after' AND
                        $logs_table.log_time_spent IS NOT NULL
                ) as t
            ",
                $warmed_at,
                $warmed_at
            ),
            ARRAY_N
        );

        return $result ? $result[0] : false;
    }

    /**
     * Log rows count
     *
     * @param string $warmed_at Warm-up to get the data for.
     *
     * @return int|bool
     */
    public static function get_log_rows_count( $warmed_at ) {
        global $wpdb;

        $logs_table = DB::get_tables_prefix() . 'warm_ups_logs';
        $list_table = DB::get_tables_prefix() . 'warm_ups_list';

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM $logs_table
                INNER JOIN $list_table ON $logs_table.list_id=$list_table.id
                WHERE $list_table.warmed_at = %s;
            ",
                $warmed_at
            ),
            ARRAY_N
        );

        return $result ? $result[0] : false;
    }

    /**
     * Log sucess rows count
     *
     * @param string $warmed_at Warm-up to get the data for.
     *
     * @return int|bool
     */
    public static function get_log_success_count( $warmed_at ) {
        global $wpdb;

        $logs_table = DB::get_tables_prefix() . 'warm_ups_logs';
        $list_table = DB::get_tables_prefix() . 'warm_ups_list';

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM $logs_table
                INNER JOIN $list_table ON $logs_table.list_id=$list_table.id
                WHERE $list_table.warmed_at = %s and $logs_table.log_is_success = 1;
            ",
                $warmed_at
            ),
            ARRAY_N
        );

        return $result ? $result[0] : false;
    }

    /**
     * Log failed rows count
     *
     * @param string $warmed_at Warm-up to get the data for.
     *
     * @return int|bool
     */
    public static function get_log_fail_count( $warmed_at ) {
        global $wpdb;

        $logs_table = DB::get_tables_prefix() . 'warm_ups_logs';
        $list_table = DB::get_tables_prefix() . 'warm_ups_list';

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "
                    SELECT COUNT(*)
                    FROM $logs_table
                    INNER JOIN $list_table ON $logs_table.list_id=$list_table.id
                    WHERE $list_table.warmed_at = %s and $logs_table.log_is_success = 0 and $logs_table.log_phase <> 2;
                ",
                $warmed_at
            ),
            ARRAY_N
        );

        return $result ? $result[0] : false;
    }

    /**
     * Log skipped rows count
     *
     * @param string $warmed_at Warm-up to get the data for.
     *
     * @return int|bool
     */
    public static function get_log_skipped_count( $warmed_at ) {
        global $wpdb;

        $logs_table = DB::get_tables_prefix() . 'warm_ups_logs';
        $list_table = DB::get_tables_prefix() . 'warm_ups_list';

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "
                    SELECT COUNT(*)
                    FROM $logs_table
                    INNER JOIN $list_table ON $logs_table.list_id=$list_table.id
                    WHERE $list_table.warmed_at = %s and $logs_table.log_is_success = 0 and $logs_table.log_phase = 2;
                ",
                $warmed_at
            ),
            ARRAY_N
        );

        return $result ? $result[0] : false;
    }

    /**
     * Log rows count
     *
     * @param string $warmed_at Warm-up to get the data for.
     *
     * @return array|bool
     */
    public static function get_first_and_last_log_fields( $warmed_at ) {
        global $wpdb;

        $logs_table = DB::get_tables_prefix() . 'warm_ups_logs';
        $list_table = DB::get_tables_prefix() . 'warm_ups_list';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
                    (SELECT *
                    FROM $logs_table
                    INNER JOIN $list_table ON $logs_table.list_id=$list_table.id
                    WHERE $list_table.warmed_at = %s
                    ORDER BY log_date DESC
                    LIMIT 1)
                    
                    UNION ALL
                    
                    (SELECT *
                    FROM $logs_table
                    INNER JOIN $list_table ON $logs_table.list_id=$list_table.id
                    WHERE $list_table.warmed_at = %s
                    ORDER BY log_date ASC    
                    LIMIT 1)
                ",
                $warmed_at,
                $warmed_at
            ),
            ARRAY_A
        );

        return $results ? $results : false;
    }

    /**
     * Log rows count
     *
     * @param string $warmed_at     Warm-up to get the data for.
     * @param bool   $get_timestamp Whether to get the timestamp.
     *
     * @return string
     */
    public static function get_warm_up_duration( $warmed_at, $get_timestamp = false ) {
        $first_and_last_rows = self::get_first_and_last_log_fields( $warmed_at );
        $diff                = strtotime( $first_and_last_rows[0]['log_date'] ) - strtotime( $first_and_last_rows[1]['log_date'] );
        return $get_timestamp ? $diff : ( $diff < 86400 ? date( 'H:i:s', $diff ) : date( 'z H:i:s', $diff ) );
    }

    /**
     * Get warm-up speed.
     *
     * @param string $warmed_at Warm-up to get the data for.
     *
     * @return float Warm-up speed (pages/minute).
     */
    public static function get_warm_up_speed( $warmed_at ) {
        $rows_count       = self::get_log_rows_count( $warmed_at ) - self::get_log_skipped_count( $warmed_at );
        $duration_minutes = self::get_warm_up_duration( $warmed_at, true ) / 60;
        return round( $duration_minutes ? ( $rows_count / $duration_minutes ) : $rows_count, 2 );
    }
}
