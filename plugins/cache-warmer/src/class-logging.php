<?php
/**
 * A class for logging.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

/**
 * Handles logging of the warm-ups.
 */
final class Logging {

    /**
     * Returns User-Agent ID.
     *
     * @param string $value A User-Agent string (trimmed to 500 characters).
     *
     * @return int Meta ID.
     */
    private static function get_user_agent_id( string $value ): int {
        global $wpdb;

        $table_name = DB::get_tables_prefix() . 'warm_ups_user_agents';

        $value = substr( $value, 0, 500 );

        // 1. Check if the row exists.

        $existing_id = (int) $wpdb->get_var( // @codingStandardsIgnoreLine
            $wpdb->prepare(
                "
                SELECT id 
                FROM $table_name 
                WHERE value = %s
                ",
                $value
            )
        );

        // If the row exists, return its ID.
        if ( $existing_id ) {
            return $existing_id;
        }

        // 2. Add the row if it doesn't exist.

        $wpdb->query( // @codingStandardsIgnoreLine
            $wpdb->prepare(
                "
                    INSERT IGNORE INTO $table_name (
                       `value`
                   )
                    VALUES (
                        %s
                    )
                ",
                $value
            )
        );

        return $wpdb->insert_id;
    }

    /**
     * Logs a warming entity.
     *
     * @param string     $warmed_at                 The date of the warm-up start.
     * @param bool       $log_is_success            Whether the log is for successful or failed fetch.
     * @param int        $log_depth                 The depth of the URL that was fetched.
     * @param float|null $log_fetch_time            Time spent to fetch the page.
     * @param float|null $afterwards_fetch_time     Time spent to fetch the page after the warming.
     * @param string     $log_url                   The URL that was fetched.
     * @param string     $log_extra                 Extra log information (for success - page code (usually 200), for failure - error description).
     * @param int        $log_phase                 Log phase. @see Cache_Warmer\DB::get_schema() method for description of this column.
     * @param string     $user_agent                User-agent the page was visited with.
     * @param array      $external_warmer_results   External warmer results.
     * @param string     $log_content_type          Content type.
     * @param string     $log_content_length        Content length.
     * @param string     $log_cf_cache_status       "cf-cache-status" response header.
     * @param string     $log_wp_super_cache_status "X-WP-Super-Cache" response header.
     * @param string     $x_cache_header            "x-cache" response header.
     * @param string     $visit_type                There can be 3 visit types: 'before', 'after' and '' (default: for success - another request after the main is done when typing headers are lacking; for failure - nothing else is done, obviously).
     * @param string     $canonical                 Canonical on the page, which leads to a different URL.
     */
    private static function log(
        $warmed_at,
        $log_is_success,
        $log_depth,
        $log_fetch_time,
        $afterwards_fetch_time,
        $log_url,
        $log_extra,
        $log_phase = 0,
        $user_agent = '',
        array $external_warmer_results = [],
        $log_content_type = '',
        $log_content_length = '',
        $log_cf_cache_status = '',
        $log_wp_super_cache_status = '',
        $x_cache_header = '',
        $visit_type = '',
        string $canonical = ''
    ) {
        global $wpdb;

        $table_name = DB::get_tables_prefix() . 'warm_ups_logs';

        $log_date       = wp_date( 'Y-m-d H:i:s' );
        $log_is_success = (int) $log_is_success;

        $warm_up_id = DB::get_warm_up_id_from_warm_up_date( $warmed_at );
        if ( ! $warm_up_id ) {
            $warm_up_id = self::create_a_warm_up( $warmed_at ); // This line is added to avoid problems when logs are cleaned during the currently running warm-up.
        }

        // Post ID.

        $post_id = url_to_postid( $log_url );

        $log_time_spent_value            = null === $log_fetch_time ? 'NULL' : $log_fetch_time;
        $log_time_spent_afterwards_value = null === $afterwards_fetch_time ? 'NULL' : $afterwards_fetch_time;

        $query = $wpdb->prepare(
            "
                INSERT INTO $table_name (
                   list_id, log_is_success, log_date, log_depth, log_url, log_post_id,
                   log_time_spent, log_time_afterwards, log_extra, log_phase, log_content_type, log_content_length, 
                   log_cf_cache_status, log_wp_super_cache_status, log_x_cache_header, log_visit_type, user_agent_id, canonical,
                   external_warmer_results
               )
                VALUES (
                    %d, %d, %s, %d, %s, %d,
                    $log_time_spent_value, $log_time_spent_afterwards_value, %s, %d, %s, %s,
                    %s, %s, %s, %s, %s, %s,
                    %s
                )
            ",
            $warm_up_id,
            $log_is_success,
            $log_date,
            $log_depth,
            $log_url,
            $post_id,
            $log_extra,
            $log_phase,
            $log_content_type,
            $log_content_length,
            $log_cf_cache_status,
            $log_wp_super_cache_status,
            $x_cache_header,
            $visit_type,
            self::get_user_agent_id( $user_agent ),
            $canonical,
            wp_json_encode( $external_warmer_results ),
        );

        $wpdb->query( $query ); // @codingStandardsIgnoreLine
    }

    /**
     * Logs successful link retrieval.
     *
     * @param string     $link                      Link.
     * @param string     $warm_up_id                The ID of the warm-up.
     * @param string     $depth                     Depth.
     * @param float|null $fetch_time                Time spent to fetch the page.
     * @param float|null $afterwards_fetch_time     Time spent to fetch the page after the warming.
     * @param string     $response_code             Response code.
     * @param int        $log_phase                 Log phase. @see Cache_Warmer\DB::get_schema() method for description of this column.
     * @param string     $user_agent                User-agent the page was visited with.
     * @param array      $external_warmer_results   External warmer results.
     * @param string     $log_content_type          Content type.
     * @param string     $log_content_length        Content length.
     * @param string     $log_cf_cache_status       "cf-cache-status" response header.
     * @param string     $log_wp_super_cache_status "X-WP-Super-Cache" response header.
     * @param string     $x_cache_header            "x-cache" response header.
     * @param string     $visit_type                There can be 3 visit types: 'before', 'after' and '' (default: for success - another request after the main is done when typing headers are lacking; for failure - nothing else is done, obviously).
     * @param string     $canonical                 Canonical on the page, which leads to a different URL.
     */
    public static function log_success(
        $link,
        $warm_up_id,
        $depth,
        $fetch_time,
        $afterwards_fetch_time,
        $response_code,
        $log_phase = 0,
        $user_agent = '',
        array $external_warmer_results = [],
        $log_content_type = '',
        $log_content_length = '',
        $log_cf_cache_status = '',
        $log_wp_super_cache_status = '',
        $x_cache_header = '',
        $visit_type = '',
        string $canonical = ''
    ) {
        self::log(
            $warm_up_id,
            1,
            $depth,
            $fetch_time,
            $afterwards_fetch_time,
            $link,
            $response_code,
            $log_phase,
            $user_agent,
            $external_warmer_results,
            $log_content_type,
            $log_content_length,
            $log_cf_cache_status,
            $log_wp_super_cache_status,
            $x_cache_header,
            $visit_type,
            $canonical,
        );
    }

    /**
     * Logs failed link retrieval.
     *
     * @param string     $link              Link.
     * @param string     $warm_up_id        The ID of the warm-up.
     * @param string     $depth             Depth.
     * @param float|null $fetch_time        Time spent to fetch the page.
     * @param string     $error_description Error description.
     * @param int        $log_phase         Log phase. @see Cache_Warmer\DB::get_schema() method for description of this column.
     * @param string     $user_agent        User-agent the page was visited with.
     * @param array      $external_warmer_results External warmer results.
     */
    public static function log_failure(
        $link,
        $warm_up_id,
        $depth,
        $fetch_time,
        $error_description,
        $log_phase = 0,
        $user_agent = '',
        array $external_warmer_results = []
    ) {
        self::log(
            $warm_up_id,
            0,
            $depth,
            $fetch_time,
            null,
            $link,
            $error_description,
            $log_phase,
            $user_agent,
            $external_warmer_results
        );
    }

    /**
     * Returns the logs list, sorted by name (date).
     *
     * @return string[] Logs list.
     */
    public static function get_warm_ups_logs_list() {
        global $wpdb;

        $table_name = DB::get_tables_prefix() . 'warm_ups_list';
        $results    = $wpdb->get_results( "SELECT warmed_at FROM $table_name ORDER BY warmed_at DESC;", ARRAY_A ); // @codingStandardsIgnoreLine

        $to_exclude = [ '0000-00-00 00:00:00', '2000-01-01 00:00:00' ]; // Exclude warm-up 0 which for unscheduled warm-ups.

        return $results ? array_diff( array_column( $results, 'warmed_at' ), $to_exclude ) : [];
    }

    /**
     * Returns logs list HTML.
     *
     * @return string Logs list HTML for the left logs column.
     */
    public static function get_logs_list_html() {
        $html = '';

        $log_files = self::get_warm_ups_logs_list();

        if ( $log_files ) {
            foreach ( $log_files as $i => $log_file ) {
                $selected_class = 0 === $i ? ' selected' : '';
                $html          .= '<span class="log-entity' . $selected_class . '">' . esc_html( $log_file ) . '</span>';
            }
        }

        return $html;
    }

    /**
     * Logs per page.
     */
    const LOGS_PER_PAGE = 100;

    /**
     * Returns log content.
     *
     * @param string $warmed_at File name to get the log content for.
     * @param int    $page       For which page to retrieve the content.
     *
     * @return array Log content.
     */
    public static function get_log_content( $warmed_at, $page = 1 ) {
        global $wpdb;

        $offset = ( $page - 1 ) * self::LOGS_PER_PAGE;
        $limit  = self::LOGS_PER_PAGE;

        $logs_table        = DB::get_tables_prefix() . 'warm_ups_logs';
        $list_table        = DB::get_tables_prefix() . 'warm_ups_list';
        $user_agents_table = DB::get_tables_prefix() . 'warm_ups_user_agents';

        $results = $wpdb->get_results( // @codingStandardsIgnoreLine
            $wpdb->prepare(
                "
                    SELECT 
                        logs.*,
                        list.warmed_at,
                        user_agents.value AS user_agent
                    FROM $logs_table AS logs
                    LEFT JOIN $list_table AS list ON logs.list_id = list.id
                    LEFT JOIN $user_agents_table AS user_agents ON logs.user_agent_id = user_agents.id
                    WHERE list.warmed_at = %s
                    ORDER BY logs.id ASC
                    LIMIT %d OFFSET %d
                ",
                $warmed_at,
                $limit,
                $offset
            ),
            ARRAY_A
        ); // @codingStandardsIgnoreLine

        foreach ( $results as &$result ) { // If log visit type is "after" then it has obviously afterwards visit time.
            if ( 'after' === $result['log_visit_type'] ) {
                $result['log_time_afterwards'] = $result['log_time_spent'];
                $result['log_time_spent']      = '';
            }
        }

        return $results ? $results : [];
    }

    /**
     * Returns the ID the latest warm-up.
     *
     * @return bool|string Latest warm-up ID or false if no warm-ups.
     */
    public static function get_latest_warmed_at() {
        $warm_ups_list = self::get_warm_ups_logs_list();
        if ( $warm_ups_list ) {
            return reset( $warm_ups_list );
        } else {
            return false;
        }
    }

    /**
     * Returns the content of the last log file.
     *
     * @param int $page       For which page to retrieve the content.
     *
     * @return array Content of the latest log file.
     */
    public static function get_latest_log_content( $page = 1 ) {
        $latest_warmed_at = self::get_latest_warmed_at();
        if ( $latest_warmed_at ) {
            return self::get_log_content( $latest_warmed_at, $page );
        } else {
            return [];
        }
    }

    /**
     * Returns the HTML string for the warm log.
     *
     * @param string $warmed_at  File name to get the log content for.
     * @param array  $log_content  Log content.
     * @param int    $current_page For which page to retrieve the content.
     *
     * @return string Log content HTML string.
     */
    public static function format_log_content_array_into_string( $warmed_at, $log_content, $current_page = 1 ) {
        if ( ! $log_content ) {
            return __( 'No data here yet.', 'cache-warmer' );
        }

        $total_visits                  = Summary::get_log_rows_count( $warmed_at );
        $successful                    = Summary::get_log_success_count( $warmed_at );
        $failed                        = Summary::get_log_fail_count( $warmed_at );
        $skipped                       = Summary::get_log_skipped_count( $warmed_at );
        $avg_page_load_time            = Summary::get_warm_up_page_average_load_time( $warmed_at );
        $avg_page_load_time_afterwards = Summary::get_warm_up_page_average_load_time_afterwards( $warmed_at );

        $warmed_at_first_part = explode( ' ', $warmed_at )[0];

        if ( ! in_array( $warmed_at_first_part, [ External_Warmer::WARMUP_ID, '0', '0000-00-00' ] ) ) {
            $duration = Summary::get_warm_up_duration( $warmed_at );
            $speed    = Summary::get_warm_up_speed( $warmed_at );
        }

        $cf_status_values_count = array_count_values( array_column( $log_content, 'log_cf_cache_status' ) );
        $has_cf_cache_statuses  = ! array_key_exists( '', $cf_status_values_count ) || count( $cf_status_values_count ) > 1;

        $wp_super_cache_status_values_count = array_count_values( array_column( $log_content, 'log_wp_super_cache_status' ) );
        $has_wp_super_cache_statuses        = ! array_key_exists( '', $wp_super_cache_status_values_count ) || count( $wp_super_cache_status_values_count ) > 1;

        $x_cache_values_count = array_count_values( array_column( $log_content, 'log_x_cache_header' ) );
        $has_x_cache_statuses = ! array_key_exists( '', $x_cache_values_count ) || count( $x_cache_values_count ) > 1;

        $content_lengths_count = array_count_values( array_column( $log_content, 'log_content_length' ) );
        $has_content_length    = count( $content_lengths_count ) > ( array_key_exists( '', $content_lengths_count ) ? 2 : 1 );

        $content_types_count = array_count_values( array_column( $log_content, 'log_content_type' ) );
        $has_content_type    = count( $content_types_count ) > ( array_key_exists( '', $content_types_count ) ? 2 : 1 );

        $has_several_user_agents = count( array_unique( array_values( array_column( $log_content, 'user_agent' ) ) ) ) > 1;

        $times_spent = array_filter(
            array_unique( array_values( array_column( $log_content, 'log_time_spent' ) ) ),
            function( $val ) {
                return ! in_array( $val, [ '0', '' ], true );
            }
        );

        $times_afterwards = array_filter(
            array_unique( array_values( array_column( $log_content, 'log_time_afterwards' ) ) ),
            function( $val ) {
                return ! in_array( $val, [ '0', '' ], true );
            }
        );

        $has_skipped_warms = array_filter(
            array_column( $log_content, 'log_phase' ),
            function( $phase ) {
                return 2 === (int) $phase;
            }
        );

        $has_retry = array_filter(
            array_column( $log_content, 'log_phase' ),
            function( $phase ) {
                return 1 === (int) $phase;
            }
        );

        ob_start();
        ?>

        <div class="cache-warmer-log-summary">
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Total Visits', 'cache-warmer' ); ?></th>
                        <th><?php esc_html_e( 'Successful', 'cache-warmer' ); ?></th>
                        <th><?php esc_html_e( 'Failed', 'cache-warmer' ); ?></th>
                        <?php if ( $has_skipped_warms ) : ?>
                            <th><?php esc_html_e( 'Skipped', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                        <?php if ( $times_spent ) : ?>
                            <th><?php esc_html_e( 'Avg. page load (sec.)', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                        <?php if ( $times_afterwards && External_Warmer::WARMUP_ID !== $warmed_at_first_part ) : ?>
                            <th><?php esc_html_e( 'Avg. page load after warming (sec.)', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                        <?php if ( ! in_array( $warmed_at_first_part, [ External_Warmer::WARMUP_ID, '0', '0000-00-00' ] ) ) : ?>
                            <th><?php esc_html_e( 'Duration', 'cache-warmer' ); ?></th>
                            <th><?php esc_html_e( 'Speed (pages / minute)', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo esc_html( $total_visits ); ?></td>
                        <td><?php echo esc_html( $successful ); ?></td>
                        <td><?php echo esc_html( $failed ); ?></td>
                        <?php if ( $has_skipped_warms ) : ?>
                            <td><?php echo esc_html( $skipped ); ?></td>
                        <?php endif; ?>
                        <?php if ( $times_spent ) : ?>
                            <td><?php echo esc_html( $avg_page_load_time ); ?></td>
                        <?php endif; ?>
                        <?php if ( $times_afterwards && External_Warmer::WARMUP_ID !== $warmed_at_first_part ) : ?>
                            <td><?php echo esc_html( $avg_page_load_time_afterwards ); ?></td>
                        <?php endif; ?>
                        <?php if ( ! in_array( $warmed_at_first_part, [ External_Warmer::WARMUP_ID, '0', '0000-00-00' ] ) ) : ?>
                            <td><?php echo esc_html( $duration ); ?></td>
                            <td><?php echo esc_html( $speed ); ?></td>
                        <?php endif; ?>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="cache-warmer-log-content">
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Time', 'cache-warmer' ); ?></th>
                        <?php
                        if ( ! in_array( $warmed_at_first_part, [ External_Warmer::WARMUP_ID, '0', '0000-00-00' ], true ) ) :
                            ?>
                            <th><?php esc_html_e( 'Depth', 'cache-warmer' ); ?></th>
                            <?php
                        endif;
                        ?>
                        <th><?php esc_html_e( 'URL', 'cache-warmer' ); ?></th>
                        <?php
                        if ( External_Warmer::WARMUP_ID === $warmed_at_first_part ) :
                            ?>
                            <th><?php esc_html_e( 'External Server', 'cache-warmer' ); ?></th>
                            <?php
                        endif;
                        ?>
                        <?php if ( $has_several_user_agents ) : ?>
                            <th><?php esc_html_e( 'User-Agent', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                        <?php if ( $times_spent ) : ?>
                            <th><?php esc_html_e( 'Loading time', 'cache-warmer' ); ?><br><?php esc_html_e( '(seconds)', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                        <?php if ( $times_afterwards && External_Warmer::WARMUP_ID !== $warmed_at_first_part ) : ?>
                            <th><?php esc_html_e( 'Loading time afterwards', 'cache-warmer' ); ?><br><?php esc_html_e( '(seconds)', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                        <?php
                        $warming_results_for_heading = array_column( $log_content, 'external_warmer_results' );
                        if ( $warming_results_for_heading ) {
                            $servers = array_values(
                                array_unique(
                                    array_column(
                                        array_merge(
                                            ...array_map(
                                                fn( $result ) => (array) json_decode( $result, true ),
                                                $warming_results_for_heading
                                            )
                                        ),
                                        'server'
                                    )
                                )
                            );

                            foreach ( $servers as $server ) {
                                echo '<th>' . esc_html__( 'External Warmer', 'cache-warmer' ) .
                                    '<br>' . esc_html__( '(seconds)', 'cache-warmer' ) .
                                    '<br><br>' . strtoupper( $server ) . '<br>' .
                                    '</th>';
                            }
                        }
                        ?>
                        <th><?php echo str_replace( '/', '/<br>', esc_html__( 'Response code / Error text', 'cache-warmer' ) ); // @codingStandardsIgnoreLine ?></th>
                        <?php if ( $has_content_type ) : ?>
                            <th>content-type</th>
                        <?php endif; ?>
                        <?php if ( $has_content_length ) : ?>
                            <th>content-length</th>
                        <?php endif; ?>
                        <?php if ( $has_cf_cache_statuses ) : ?>
                            <th>cf-cache-status<br><?php esc_html_e( 'header', 'cache-warmer' ); ?><sup><i>1</i></sup></th>
                        <?php endif; ?>
                        <?php if ( $has_wp_super_cache_statuses ) : ?>
                            <th>X-WP-Super-Cache<br><?php esc_html_e( 'header', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                        <?php if ( $has_x_cache_statuses ) : ?>
                            <th>X-Cache<br><?php esc_html_e( 'header', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                        <?php if ( $has_retry ) : ?>
                            <th><?php esc_html_e( 'Retry', 'cache-warmer' ); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ( $log_content as $log_entity ) :
                    $external_warmer_results = (array) json_decode( $log_entity['external_warmer_results'], true );

                    ?>
                    <tr class="<?php echo ( ! $log_entity['log_is_success'] && 2 !== (int) $log_entity['log_phase'] ) ? 'cache-warmer-log-failure' : ( 2 === (int) $log_entity['log_phase'] ? 'cache-warmer-log-skipped' : '' ); ?>">
                        <td><?php echo str_replace( '-', '&#x2011;', esc_html( $log_entity['log_date'] ) ); // @codingStandardsIgnoreLine ?></td>
                        <?php
                        if ( ! in_array( $warmed_at_first_part, [ External_Warmer::WARMUP_ID, '0', '0000-00-00' ], true ) ) :
                            ?>
                            <td><?php echo esc_html( $log_entity['log_depth'] ); ?></td>
                            <?php
                        endif;
                        ?>
                        <td>
                        <a href="
                        <?php
                        echo esc_attr(
                            External_Warmer::WARMUP_ID !== $warmed_at_first_part ? $log_entity['log_url'] : explode( ' ', $log_entity['log_url'] )[1]
                        );
                        ?>
                        " target="_blank">
                        <?php
                            echo '<span class="cache-warmer-link-content">' . preg_replace(
                                '@/@',
                                '/<wbr>',
                                esc_html( External_Warmer::WARMUP_ID !== $warmed_at_first_part ? $log_entity['log_url'] : explode( ' ', $log_entity['log_url'] )[1] )
                            ) . '</span>' . (
                                    $log_entity['canonical'] ?
                                        '<span class="cache-warmer-popup-message" data-popup-message="' .
                                        /* translators: %s is the canonical URL. */
                                        esc_attr( sprintf( __( 'Has a canonical: %s', 'cache-warmer' ), $log_entity['canonical'] ) ) .
                                        '"> ⚠</span>️' : ''
                                );
                        ?>
                        </a>
                        </td>
                        <?php
                        if ( External_Warmer::WARMUP_ID === $warmed_at_first_part ) :
                            ?>
                            <td><?php echo esc_html( strtoupper( explode( ' ', $log_entity['log_url'] )[0] ) ); ?></td>
                            <?php
                        endif;
                        ?>
                        <?php if ( $has_several_user_agents ) : ?>
                            <td><?php echo esc_html( $log_entity['user_agent'] ); ?></td>
                        <?php endif; ?>
                        <?php if ( $times_spent ) : ?>
                            <td><?php echo esc_html( $log_entity['log_time_spent'] ); ?></td>
                        <?php endif; ?>
                        <?php if ( $times_afterwards && External_Warmer::WARMUP_ID !== $warmed_at_first_part ) : ?>
                            <td><?php echo esc_html( $log_entity['log_time_afterwards'] ); ?></td>
                        <?php endif; ?>
                        <?php

                        $external_warmer_results_servers = array_column( $external_warmer_results, 'server' );

                        foreach ( $servers as $server ) {
                            $server_index = array_search( $server, $external_warmer_results_servers );

                            if ( false !== $server_index ) {
                                $external_warmer_result = $external_warmer_results[ $server_index ];

                                echo '<td class="' . ( 200 !== $external_warmer_result['code'] ? 'cache-warmer-log-failure' : '' ) . '">' .
                                    '<span class="cache-warmer-popup-message" data-popup-message="' . esc_attr( wp_date( 'Y-m-d H:i:s', $external_warmer_result['at'] ) ) . '">' . esc_html(
                                        200 === $external_warmer_result['code'] ?
                                            $external_warmer_result['time'] :
                                            (
                                            $external_warmer_result['status'] ?
                                                $external_warmer_result['status'] :
                                                '-'
                                            )
                                    ) . '</span></td>';
                            } else {
                                echo '<td>-</td>';
                            }
                        }
                        ?>
                        <td>
                        <?php
                            // @codingStandardsIgnoreLine
                            echo $log_entity['log_extra']; // No escaping for a purpose: Because we print HTML.
                        ?>
                        </td>
                        <?php if ( $has_content_type ) : ?>
                            <td><?php echo esc_html( $log_entity['log_content_type'] ); ?></td>
                        <?php endif; ?>
                        <?php if ( $has_content_length ) : ?>
                            <td><?php echo esc_html( $log_entity['log_content_length'] ); ?></td>
                        <?php endif; ?>
                        <?php if ( $has_cf_cache_statuses ) : ?>
                            <td><?php echo esc_html( $log_entity['log_cf_cache_status'] ); ?></td>
                        <?php endif; ?>
                        <?php if ( $has_wp_super_cache_statuses ) : ?>
                            <td><?php echo esc_html( $log_entity['log_wp_super_cache_status'] ); ?></td>
                        <?php endif; ?>
                        <?php if ( $has_x_cache_statuses ) : ?>
                            <td><?php echo esc_html( $log_entity['log_x_cache_header'] ); ?></td>
                        <?php endif; ?>
                        <?php if ( $has_retry ) : ?>
                            <td><?php echo esc_html( $log_entity['log_phase'] ); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php

        $max_page = (int) ( floor( $total_visits / self::LOGS_PER_PAGE ) + ( $total_visits % self::LOGS_PER_PAGE ? 1 : 0 ) );

        if ( $total_visits > self::LOGS_PER_PAGE ) :
            /*
             * Adds pagination HTML.
             */
            $previous_page = 1 === $current_page ? 1 : $current_page - 1;

            $next_page = $current_page === $max_page ? $max_page : $current_page + 1;

            $PAGES_VIEW_RANGE = 2;

            ?>
                <div class="cache-warmer-pagination-container">
                    <div class="cache-warmer-pagination">
                        <a data-page="<?php echo esc_attr( $previous_page ); ?>" rel="prev" class="cache-warmer-pagination-element">
                            <?php echo esc_attr( __( 'Previous', 'cache-warmer' ) ); ?>
                        </a>
            <?php

            $were_three_dots_added_start = false;
            $were_three_dots_added_end   = false;

            $print_three_dots = function () {
                ?>
                <span class="cache-warmer-pagination-dots">...</span>
                <?php
            };

            $page = 1;
            for ( $remained_logs = $total_visits; $remained_logs > 0; $remained_logs -= self::LOGS_PER_PAGE ) {
                $is_page_in_start_range      = $page <= $PAGES_VIEW_RANGE;
                $is_page_around_current_page = $page >= $current_page - $PAGES_VIEW_RANGE && $page <= $current_page + $PAGES_VIEW_RANGE;
                $is_page_in_end_range        = $page > $max_page - $PAGES_VIEW_RANGE;

                if ( $is_page_in_start_range || $is_page_around_current_page || $is_page_in_end_range ) {
                    if ( $current_page === $page ) {
                        ?>
                        <a data-page="<?php echo esc_attr( $page ); ?>" class="cache-warmer-pagination-element cache-warmer-pagination-digit active">
                            <?php echo esc_attr( $page ); ?>
                        </a>
                        <?php
                    } else {
                        ?>
                        <a data-page="<?php echo esc_attr( $page ); ?>" class="cache-warmer-pagination-element cache-warmer-pagination-digit">
                            <?php echo esc_attr( $page ); ?>
                        </a>
                        <?php
                    }
                } else {
                    if ( $page < $current_page && ! $were_three_dots_added_start ) {
                        $print_three_dots();
                        $were_three_dots_added_start = true;
                    }
                    if ( $page > $current_page && ! $were_three_dots_added_end ) {
                        $print_three_dots();
                        $were_three_dots_added_end = true;
                    }
                }

                $page ++;
            }

            ?>
                    <a data-page="<?php echo esc_attr( $next_page ); ?>" rel="next" class="cache-warmer-pagination-element">
                        <?php echo esc_attr( __( 'Next', 'cache-warmer' ) ); ?>
                    </a>
                </div>
            </div>
            <?php
        endif;
        ?>

        <?php if ( $has_cf_cache_statuses ) : ?>
            <div class="cache-warmer-row mt-5">
                <p class="cache-warmer-comment">
                    <b>1</b><?php esc_html_e( ' -', 'cache-warmer' ); ?> <a href="https://developers.cloudflare.com/cache/about/default-cache-behavior/#cloudflare-cache-responses">https://developers.cloudflare.com/cache/about/default-cache-behavior/#cloudflare-cache-responses</a>
                </p>
            </div>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }

    /**
     * Creates a warm-up.
     *
     * @param string $warm_up_start_date Warm-up start date (ID).
     *
     * @return int ID of the inserted warm-up.
     */
    public static function create_a_warm_up( $warm_up_start_date ) {
        global $wpdb;

        $prev = $wpdb->suppress_errors( true );

        $table_name = DB::get_tables_prefix() . 'warm_ups_list';

        // Delete excess warm-ups, according to the "For how many days to keep the logs" setting.
        self::delete_excess_warm_ups( $warm_up_start_date );

        $wpdb->query( // @codingStandardsIgnoreLine
            $wpdb->prepare(
                "INSERT INTO $table_name (warmed_at) VALUES (%s);",
                $warm_up_start_date
            )
        );

        $wpdb->suppress_errors( $prev );

        return $wpdb->insert_id;
    }

    /**
     * Delete excess warm-ups.
     *
     * @param string $warm_up_start_date War up ID which is being created.
     */
    public static function delete_excess_warm_ups( string $warm_up_start_date ) {
        global $wpdb;

        $for_how_many_days_to_retain_warm_ups = (int) Cache_Warmer::$options->get( 'setting-for-how-many-days-to-keep-the-logs' );
        $list_table                           = DB::get_tables_prefix() . 'warm_ups_list';
        $logs_table                           = DB::get_tables_prefix() . 'warm_ups_logs';
        $user_agents_table                    = DB::get_tables_prefix() . 'warm_ups_user_agents';

        $date_threshold = wp_date( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * max( 1, $for_how_many_days_to_retain_warm_ups ) );

        if ( 0 === $for_how_many_days_to_retain_warm_ups ) {
            $wpdb->query( // Retain only the current one.
                $wpdb->prepare(
                    "
                        DELETE FROM $list_table
                        WHERE warmed_at NOT IN (%s, '0000-00-00 00:00:00', '2000-01-01')
                    ",
                    $warm_up_start_date
                )
            );
        } else {
            $wpdb->query(
                $wpdb->prepare(
                    "
                    DELETE FROM $list_table
                    WHERE warmed_at < %s
                    AND warmed_at NOT IN ('0000-00-00 00:00:00', '2000-01-01')
                    ",
                    $date_threshold
                )
            );
        }

        // Delete unscheduled.

        $wpdb->query(
            $wpdb->prepare(
                "
                        DELETE $list_table
                        FROM $list_table
                        INNER JOIN $logs_table ON $list_table.id = $logs_table.list_id
                        WHERE $logs_table.log_date < %s
                        AND $list_table.warmed_at = '0000-00-00 00:00:00'
                        ",
                $date_threshold
            )
        );

        // Delete External Warmups.

        $wpdb->query(
            $wpdb->prepare(
                "
                        DELETE $list_table
                        FROM $list_table
                        INNER JOIN $logs_table ON $list_table.id = $logs_table.list_id
                        WHERE $logs_table.log_date < %s
                        AND $list_table.warmed_at = '2000-01-01 00:00:00'
                        ",
                $date_threshold
            )
        );

        // Delete user agents with no references.

        $wpdb->query(
            "
            DELETE FROM $user_agents_table
            WHERE id NOT IN (
                SELECT DISTINCT user_agent_id
                FROM $logs_table
                WHERE user_agent_id IS NOT NULL
            );
            "
        );
    }
}
