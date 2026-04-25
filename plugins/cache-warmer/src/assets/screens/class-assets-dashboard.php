<?php
/**
 * Assets for dashboard
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer\Assets;

use Cache_Warmer\DB;

/**
 * Assets class.
 */
final class Dashboard {

    /**
     * Inits.
     */
    public static function init() {
        $class = __CLASS__;
        add_action(
            'admin_enqueue_scripts',
            function () use ( $class ) {
                new $class();
            }
        );
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->styles();
        $this->scripts();
    }

    /**
     * Loads styles.
     */
    private function styles() {
        wp_enqueue_style(
            'cache-warmer-admin-dashboard-style',
            CACHE_WARMER_URL . 'assets-build/admin/dashboard.css',
            [],
            CACHE_WARMER_VERSION
        );
    }

    /**
     * Returns average page load time for warm-up id.
     *
     * For the last 30 days.
     *
     * @return array Average loading time or false if no logs for the warm-up.
     */
    public static function get_logs_avg_load_times_before_and_after_the_warmup() {
        global $wpdb;

        $logs_table = DB::get_tables_prefix() . 'warm_ups_logs';
        $list_table = DB::get_tables_prefix() . 'warm_ups_list';

        $min_date = wp_date( 'Y-m-d H:i:s', time() - MONTH_IN_SECONDS );

        return $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT 
                    t1.`date`,
                    t1.`time`,
                    t2.`time_after`
                FROM
                    (
                        SELECT
                            $list_table.warmed_at as `date`,
                            ROUND(AVG($logs_table.log_time_spent), 2) as `time`
                        FROM
                            $logs_table
                        INNER JOIN
                            $list_table ON $logs_table.list_id=$list_table.id
                        WHERE
                            $list_table.warmed_at > %s AND
                            $logs_table.log_visit_type != 'after' AND
                            $logs_table.log_time_spent IS NOT NULL
                        GROUP BY
                            $list_table.warmed_at
                    ) as t1
                LEFT JOIN
                    (
                        SELECT
                            `date`,
                            ROUND(AVG(avg_log_time), 2) as `time_after`
                        FROM (
                            SELECT
                                $list_table.warmed_at as `date`,
                                $logs_table.log_time_afterwards as avg_log_time
                            FROM
                                $logs_table
                            INNER JOIN
                                $list_table ON $logs_table.list_id=$list_table.id
                            WHERE
                                $list_table.warmed_at > %s AND
                                $logs_table.log_visit_type != 'after' AND
                                $logs_table.log_time_spent IS NOT NULL AND
                                $logs_table.log_time_afterwards IS NOT NULL
                            
                            UNION ALL
                            
                            SELECT
                                $list_table.warmed_at as `date`,
                                $logs_table.log_time_spent as avg_log_time
                            FROM
                                $logs_table
                            INNER JOIN
                                $list_table ON $logs_table.list_id=$list_table.id
                            WHERE
                                $list_table.warmed_at > %s AND
                                $logs_table.log_visit_type = 'after' AND
                                $logs_table.log_time_spent IS NOT NULL
                        ) as t
                        GROUP BY `date`
                    ) as t2
                ON t1.`date` = t2.`date`
                WHERE
                    t2.`time_after` IS NOT NULL
        ",
                $min_date,
                $min_date,
                $min_date
            ),
            ARRAY_A
        );
    }

    /**
     * Loads scripts.
     */
    private function scripts() {
        wp_register_script(
            'cache-warmer-lib-apexcharts',
            CACHE_WARMER_URL . 'libs/apexcharts/apexcharts.min.js',
            [],
            '3.35.1',
            true
        );

        wp_register_script(
            'cache-warmer-admin-script',
            CACHE_WARMER_URL . 'assets-build/admin/index.js',
            [],
            CACHE_WARMER_VERSION,
            true
        );

        wp_enqueue_script(
            'cache-warmer-admin-dashboard-script',
            CACHE_WARMER_URL . 'assets-build/admin/dashboard.js',
            [ 'cache-warmer-admin-script', 'jquery', 'cache-warmer-lib-apexcharts' ],
            CACHE_WARMER_VERSION,
            true
        );

        wp_localize_script(
            'cache-warmer-admin-script',
            'wpCacheWarmerChart',
            [
                'jQuery'          => 'jQuery',
                'disable_pro'     => '1',
                'disable_banners' => '',
                'l10n'            => [
                    'time'          => __( 'Time', 'cache-warmer' ),
                    'no_data'       => __( 'Not enough warm-ups logged, yet.', 'cache-warmer' ),
                    'after_warm_up' => __( 'After warming', 'cache-warmer' ),
                ],
                'data'            => self::get_logs_avg_load_times_before_and_after_the_warmup(),
            ]
        );
    }
}
