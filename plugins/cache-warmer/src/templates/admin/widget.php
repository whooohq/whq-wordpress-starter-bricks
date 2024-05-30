<?php
/**
 * Dashboard widget template.
 *
 * @package Cache-Warmer
 */
?>

<div id="widget-cache-warmer-stats">

    <ul>
        <li>
            <a class="active" href="#" data-chart="time">
                <?php esc_html_e( 'Average Page Load Time', 'cache-warmer' ); ?>
            </a>
        </li>
        <li style="margin-left: auto;">
            <a href="<?php echo admin_url( 'admin.php?page=cache-warmer-settings' ); ?>">
                <?php esc_html_e( 'Settings', 'cache-warmer' ); ?>
            </a>
        </li>
    </ul>

    <div id="cache-warmer-stats-chart"></div>
</div>
