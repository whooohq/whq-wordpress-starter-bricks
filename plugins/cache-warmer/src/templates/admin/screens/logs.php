<?php
/**
 * Template for admin menu
 *
 * @package Cache-Warmer
 */

use Cache_Warmer\Logging;

?>

<div class="wrap">
    <h1 class="cache-warmer-header">
        <?php esc_html_e( 'Cache Warmer', 'cache-warmer' ); ?> <?php esc_html_e( 'Logs', 'cache-warmer' ); ?>
    </h1>

    <div class="cache-warmer-container">
        <div class="wp-plugins-core-tabs-container">
            <div class="wp-plugins-core-tab-content" data-tab-name="scheduled">
                <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Scheduled', 'cache-warmer' ); ?></h2>

                <div class="cache-warmer-row cache-warmer-logs-row-container">
                    <div class="cache-warmer-column" id="cache-warmer-warm-ups-list">
                        <?php echo Logging::get_logs_list_html(); // @codingStandardsIgnoreLine ?>
                    </div>
                    <div class="cache-warmer-column cache-warmer-log-content-block" data-current-page="1" data-log-name="<?php echo Logging::get_latest_warmed_at(); ?>"><?php // @codingStandardsIgnoreLine
                        echo Logging::format_log_content_array_into_string( Logging::get_latest_warmed_at(), Logging::get_latest_log_content() ); // @codingStandardsIgnoreLine ?></div>
                </div>
                <div class="cache-warmer-row <?php echo ! Logging::get_warm_ups_logs_list() ? 'cache-warmer-hidden' : ''; ?>">
                    <input type="submit"
                           class="button button-primary cache-warmer-delete-all-logs cache-warmer-button-red"
                           value="<?php esc_html_e( 'Delete all logs.', 'cache-warmer' ); ?>">
                </div>
            </div>

            <div class="wp-plugins-core-tab-content" data-tab-name="unscheduled">
                <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Triggered', 'cache-warmer' ); ?></h2>

                <div class="cache-warmer-row cache-warmer-logs-row-container">
                    <div class="cache-warmer-column cache-warmer-log-content-block" data-current-page="1" data-log-name="unscheduled"><?php // @codingStandardsIgnoreLine
                        echo Logging::format_log_content_array_into_string( 0, Logging::get_log_content( 0 ) ); // @codingStandardsIgnoreLine ?></div>
                </div>
                <div class="cache-warmer-row <?php echo ! Logging::get_log_content( 0 ) ? 'cache-warmer-hidden' : ''; ?>">
                    <input type="submit"
                        class="button button-primary cache-warmer-delete-unscheduled-logs cache-warmer-button-red"
                        value="<?php esc_html_e( 'Delete unscheduled logs.', 'cache-warmer' ); ?>">
                </div>
            </div>

            <div class="wp-plugins-core-tab-content" data-tab-name="external-warmer">
                <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'External Warmer', 'cache-warmer' ); ?></h2>

                <div class="cache-warmer-row cache-warmer-logs-row-container">
                    <div class="cache-warmer-column cache-warmer-log-content-block" data-current-page="1" data-log-name="external-warmer"><?php // @codingStandardsIgnoreLine
                        echo Logging::format_log_content_array_into_string( '2000-01-01', Logging::get_log_content( '2000-01-01' ) ); // @codingStandardsIgnoreLine ?></div>
                </div>
                <div class="cache-warmer-row <?php echo ! Logging::get_log_content( '2000-01-01' ) ? 'cache-warmer-hidden' : ''; ?>">
                    <input type="submit"
                        class="button button-primary cache-warmer-delete-external-warmer-logs cache-warmer-button-red"
                        value="<?php esc_html_e( 'Delete external warmer logs.', 'cache-warmer' ); ?>">
                </div>
            </div>
        </div>
    </div>
</div>
