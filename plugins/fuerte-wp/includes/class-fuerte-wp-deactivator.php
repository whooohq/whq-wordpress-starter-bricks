<?php

/**
 * Fired during plugin deactivation.
 *
 * @link       https://actitud.xyz
 * @since      1.3.0
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.3.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */
class Fuerte_Wp_Deactivator
{
    /**
     * Short Description. (use period).
     *
     * Long Description.
     *
     * @since    1.3.0
     */
    public static function deactivate()
    {
        self::clear_cron_jobs();
    }

    /**
     * Clear scheduled cron jobs on deactivation.
     *
     * @since 1.7.0
     */
    public static function clear_cron_jobs()
    {
        wp_clear_scheduled_hook('fuertewp_cleanup_login_logs');
    }
}
