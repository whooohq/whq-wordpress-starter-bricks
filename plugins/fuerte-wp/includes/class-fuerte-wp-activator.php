<?php

/**
 * Fired during plugin activation.
 *
 * @link       https://actitud.xyz
 * @since      1.3.0
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.3.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */
class Fuerte_Wp_Activator
{
    /**
     * Database version for login security feature.
     * Uses plugin version to ensure consistency.
     *
     * @since 1.7.3
     */
    public static function get_db_version()
    {
        return defined('FUERTEWP_VERSION') ? FUERTEWP_VERSION : '1.0.0';
    }

    /**
     * Short Description. (use period).
     *
     * Long Description.
     *
     * @since    1.3.0
     */
    public static function activate()
    {
        // Check if this is a plugin update and clear cache if version changed
        self::handle_plugin_update();

        self::create_login_security_tables();
        self::schedule_cron_jobs();
        self::setup_initial_super_user();
        self::setup_default_status();
    }

    /**
     * Set up initial super user from current admin user.
     * This prevents lockout when the plugin is first activated.
     * Uses standardized Config class methods to avoid Carbon Fields timing issues.
     *
     * @since 1.7.2
     */
    public static function setup_initial_super_user()
    {
        // Use our standardized Config class methods instead of Carbon Fields
        if (!class_exists('Fuerte_Wp_Config')) {
            return;
        }

        // Check if super_users is already set
        $existing_super_users = Fuerte_Wp_Config::get_field('super_users', [], true);

        if (empty($existing_super_users)) {
            // Get current user if available (works during activation)
            $current_user = wp_get_current_user();

            if ($current_user && $current_user->ID > 0) {
                // Add current user as super user using our standardized method
                Fuerte_Wp_Config::set_field('super_users', [$current_user->user_email], true);
            }
        }
    }

    /**
     * Ensure the plugin status is set to 'enabled' by default.
     * This prevents the plugin from being disabled on fresh installations.
     *
     * @since 1.9.1
     */
    public static function setup_default_status()
    {
        if (!class_exists('Fuerte_Wp_Config')) {
            return;
        }

        $current_status = Fuerte_Wp_Config::get_field('status');

        if (empty($current_status)) {
            Fuerte_Wp_Config::set_field('status', 'enabled');
        }
    }

    /**
     * Create database tables for login security feature.
     * Safe to call multiple times - checks if tables exist first.
     *
     * @since 1.7.0
     */
    public static function create_login_security_tables()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Login attempts table
        $table_attempts = $wpdb->prefix . 'fuertewp_login_attempts';
        $sql_attempts = "CREATE TABLE IF NOT EXISTS $table_attempts (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            username VARCHAR(255) NOT NULL,
            attempt_time DATETIME NOT NULL,
            status ENUM('success', 'failed', 'blocked') NOT NULL,
            user_agent VARCHAR(500) NULL,
            result_message VARCHAR(255) NULL,
            PRIMARY KEY  (id),
            KEY ip_time (ip_address, attempt_time),
            KEY username_time (username, attempt_time),
            KEY status_time (status, attempt_time),
            KEY cleanup_idx (attempt_time)
        ) $charset_collate;";

        // Login lockouts table
        $table_lockouts = $wpdb->prefix . 'fuertewp_login_lockouts';
        $sql_lockouts = "CREATE TABLE IF NOT EXISTS $table_lockouts (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            username VARCHAR(255) NULL,
            lockout_time DATETIME NOT NULL,
            unlock_time DATETIME NOT NULL,
            attempt_count INT(11) NOT NULL DEFAULT 1,
            reason VARCHAR(255) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY ip_lock (ip_address),
            UNIQUE KEY username_lock (username),
            KEY unlock_time (unlock_time)
        ) $charset_collate;";

        // IP whitelist/blacklist table
        $table_ips = $wpdb->prefix . 'fuertewp_login_ips';
        $sql_ips = "CREATE TABLE IF NOT EXISTS $table_ips (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_or_range VARCHAR(255) NOT NULL,
            type ENUM('whitelist', 'blacklist') NOT NULL,
            range_type ENUM('single', 'range', 'cidr') NOT NULL,
            note VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY ip_range (ip_or_range, type),
            KEY type_idx (type)
        ) $charset_collate;";

        // Execute SQL
        dbDelta($sql_attempts);
        dbDelta($sql_lockouts);
        dbDelta($sql_ips);

        // Store database version
        add_option('_fuertewp_login_db_version', self::get_db_version());
    }

    /**
     * Schedule cron jobs for maintenance tasks.
     *
     * @since 1.7.0
     */
    public static function schedule_cron_jobs()
    {
        // Daily cleanup of old login logs
        if (!wp_next_scheduled('fuertewp_cleanup_login_logs')) {
            wp_schedule_event(time(), 'daily', 'fuertewp_cleanup_login_logs');
        }
    }

    /**
     * Handle plugin updates and clear configuration cache when needed.
     *
     * @since 1.7.1
     */
    public static function handle_plugin_update()
    {
        $option_name = 'fuertewp_version';
        $current_version = defined('FUERTEWP_VERSION') ? FUERTEWP_VERSION : '1.0.0';
        $previous_version = get_option($option_name, '1.0.0');

        // If version has changed, clear all relevant caches
        if ($previous_version !== $current_version) {
            // Clear the configuration cache
            delete_transient('fuertewp_cache_config');

            // Clear the new simple config cache if class exists
            if (class_exists('Fuerte_Wp_Config')) {
                Fuerte_Wp_Config::invalidate_cache();
            }

            // Clear any other plugin-related transients
            delete_transient('fuertewp_login_attempts_cache');
            delete_transient('fuertewp_ip_whitelist_cache');

            // Update the stored version
            update_option($option_name, $current_version);

            // Log the version update for debugging
            if (function_exists('Fuerte_Wp_Logger') && class_exists('Fuerte_Wp_Logger')) {
                $logger = new Fuerte_Wp_Logger();
                $logger->log("Plugin updated from {$previous_version} to {$current_version} - caches cleared");
            }
        }
    }
}
