<?php

/**
 * Auto-Update Manager class.
 *
 * @link       https://actitud.xyz
 * @since      1.5.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Fuerte-WP Auto-Update Manager.
 */
class Fuerte_Wp_Auto_Update_Manager
{
    /**
     * Plugin instance.
     *
     * @see get_instance()
     *
     * @type object
     */
    protected static $instance = null;

    /**
     * Access this plugin instance.
     */
    public static function get_instance()
    {
        null === self::$instance and self::$instance = new self();

        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Hook into WordPress
        add_action('init', [$this, 'init']);
    }

    /**
     * Initialize the auto-update manager.
     */
    public function init()
    {
        // Add action hook for the cron event
        add_action('fuertewp_trigger_updates', [$this, 'trigger_updates']);

        // Add filters for deferred updates (must run before global __return_true filters)
        add_filter('auto_update_plugin', [$this, 'exclude_deferred_plugins'], 10, 2);
        add_filter('auto_update_theme', [$this, 'exclude_deferred_themes'], 10, 2);

        // Add filters to completely block updates for blocked plugins/themes
        add_filter('pre_site_transient_update_plugins', [$this, 'block_plugin_updates']);
        add_filter('pre_site_transient_update_themes', [$this, 'block_theme_updates']);

        // Hide update notices in admin for blocked items
        add_filter('plugin_row_meta', [$this, 'hide_plugin_update_notice'], 10, 2);
        add_filter('theme_row_meta', [$this, 'hide_theme_update_notice'], 10, 2);
    }

    /**
     * Manage auto-update configuration.
     *
     * @param array $fuertewp The Fuerte-WP configuration
     */
    public function manage_updates($fuertewp)
    {
        $some_updates_enabled = false;

        if (isset($fuertewp['general']['autoupdate_core']) && true === $fuertewp['general']['autoupdate_core']) {
            $some_updates_enabled = true;
        }

        if (isset($fuertewp['general']['autoupdate_plugins']) && true === $fuertewp['general']['autoupdate_plugins']) {
            $some_updates_enabled = true;
        }

        if (isset($fuertewp['general']['autoupdate_themes']) && true === $fuertewp['general']['autoupdate_themes']) {
            $some_updates_enabled = true;
        }

        if (isset($fuertewp['general']['autoupdate_translations']) && true === $fuertewp['general']['autoupdate_translations']) {
            $some_updates_enabled = true;
        }

        if ($some_updates_enabled === true) {
            // Updates enabled, register the cron with configured frequency
            $this->register_autoupdate_cron($fuertewp['general']['autoupdate_frequency']);
        } else {
            // No updates enabled, remove the cron
            $this->remove_autoupdate_cron();
        }
    }

    /**
     * Register autoupdate
     * Forces WordPress, via scheduled task, to perform the update routine
     * with configurable frequency.
     *
     * @param string $frequency The update frequency (six_hours, twelve_hours, daily, twodays)
     */
    protected function register_autoupdate_cron($frequency = 'twelve_hours')
    {
        // Default frequency if not provided
        if (empty($frequency)) {
            $frequency = 'twelve_hours';
        }

        // Clear existing schedule to ensure frequency change takes effect
        wp_clear_scheduled_hook('fuertewp_trigger_updates');

        // Check if event isn't already scheduled with the new frequency
        if (!wp_next_scheduled('fuertewp_trigger_updates')) {
            wp_schedule_event(time(), $frequency, 'fuertewp_trigger_updates');
        }
    }

    /**
     * Remove autoupdate cron.
     */
    protected function remove_autoupdate_cron()
    {
        wp_clear_scheduled_hook('fuertewp_trigger_updates');
    }

    /**
     * Do the updates
     * This method is called by the cronjob to perform scheduled updates.
     */
    public function trigger_updates()
    {
        // Get current configuration from enforcer
        $enforcer = Fuerte_Wp_Enforcer::get_instance();
        $fuertewp = $enforcer->config_setup();

        // Log
        if (function_exists('write_log')) {
            write_log('Fuerte-WP trigger_updates ran at ' . date('Y-m-d H:i:s'));
        }

        // Force fresh update checks by clearing caches
        if (function_exists('wp_clean_update_cache')) {
            wp_clean_update_cache();
        }

        // Clear specific update caches
        delete_site_transient('update_core');
        delete_site_transient('update_plugins');
        delete_site_transient('update_themes');

        // Force update checks
        if (isset($fuertewp['general']['autoupdate_core']) && true === $fuertewp['general']['autoupdate_core']) {
            wp_version_check(); // Force core update check
        }

        if (isset($fuertewp['general']['autoupdate_plugins']) && true === $fuertewp['general']['autoupdate_plugins']) {
            wp_update_plugins(); // Force plugin update check
        }

        if (isset($fuertewp['general']['autoupdate_themes']) && true === $fuertewp['general']['autoupdate_themes']) {
            wp_update_themes(); // Force theme update check
        }

        // Set up filters globally for the update process
        if (isset($fuertewp['general']['autoupdate_core']) && true === $fuertewp['general']['autoupdate_core']) {
            add_filter('auto_update_core', '__return_true', FUERTEWP_LATE_PRIORITY);
            add_filter('allow_minor_auto_core_updates', '__return_true', FUERTEWP_LATE_PRIORITY);
            add_filter('allow_major_auto_core_updates', '__return_true', FUERTEWP_LATE_PRIORITY);
        }

        if (isset($fuertewp['general']['autoupdate_plugins']) && true === $fuertewp['general']['autoupdate_plugins']) {
            add_filter('auto_update_plugin', '__return_true', FUERTEWP_LATE_PRIORITY);
        }

        if (isset($fuertewp['general']['autoupdate_themes']) && true === $fuertewp['general']['autoupdate_themes']) {
            add_filter('auto_update_theme', '__return_true', FUERTEWP_LATE_PRIORITY);
        }

        if (isset($fuertewp['general']['autoupdate_translations']) && true === $fuertewp['general']['autoupdate_translations']) {
            add_filter('autoupdate_translations', '__return_true', FUERTEWP_LATE_PRIORITY);
        }

        // Trigger WordPress auto-update process
        wp_maybe_auto_update();

        // Log completion
        if (function_exists('write_log')) {
            write_log('Fuerte-WP trigger_updates completed at ' . date('Y-m-d H:i:s'));
        }
    }

    /**
     * Exclude deferred plugins from auto-updates.
     *
     * @since 1.8.0
     *
     * @param bool $update Whether to update the plugin
     * @param object $item The plugin's update data object
     *
     * @return bool False if plugin is deferred, original value otherwise
     */
    public function exclude_deferred_plugins($update, $item)
    {
        $fuertewp = Fuerte_Wp_Config::get_config();
        $deferred = $fuertewp['deferred_plugins'] ?? [];

        if (!empty($deferred) && isset($item->plugin) && in_array($item->plugin, $deferred)) {
            return false; // Don't auto-update this plugin
        }

        return $update; // Let other filters decide
    }

    /**
     * Exclude deferred themes from auto-updates.
     *
     * @since 1.8.0
     *
     * @param bool $update Whether to update the theme
     * @param object $item The theme's update data object
     *
     * @return bool False if theme is deferred, original value otherwise
     */
    public function exclude_deferred_themes($update, $item)
    {
        $fuertewp = Fuerte_Wp_Config::get_config();
        $deferred = $fuertewp['deferred_themes'] ?? [];

        if (!empty($deferred) && isset($item->theme) && in_array($item->theme, $deferred)) {
            return false; // Don't auto-update this theme
        }

        return $update; // Let other filters decide
    }

    /**
     * Block updates for blocked plugins.
     * Removes blocked plugins from the update transient completely.
     *
     * @since 1.9.0
     *
     * @param object|bool $value The update transient value
     *
     * @return object|bool Modified transient with blocked plugins removed
     */
    public function block_plugin_updates($value)
    {
        $fuertewp = Fuerte_Wp_Config::get_config();
        $blocked = $fuertewp['blocked_plugins'] ?? [];

        if (empty($blocked)) {
            return $value;
        }

        // If value is false or not an object, return as-is
        if (false === $value || !is_object($value)) {
            return $value;
        }

        // Remove blocked plugins from the update list
        if (isset($value->response) && is_array($value->response)) {
            foreach ($blocked as $plugin_file) {
                unset($value->response[$plugin_file]);
            }
        }

        // Also remove from no_update and checked arrays
        if (isset($value->no_update) && is_array($value->no_update)) {
            foreach ($blocked as $plugin_file) {
                unset($value->no_update[$plugin_file]);
            }
        }

        if (isset($value->checked) && is_array($value->checked)) {
            foreach ($blocked as $plugin_file) {
                unset($value->checked[$plugin_file]);
            }
        }

        return $value;
    }

    /**
     * Block updates for blocked themes.
     * Removes blocked themes from the update transient completely.
     *
     * @since 1.9.0
     *
     * @param object|bool $value The update transient value
     *
     * @return object|bool Modified transient with blocked themes removed
     */
    public function block_theme_updates($value)
    {
        $fuertewp = Fuerte_Wp_Config::get_config();
        $blocked = $fuertewp['blocked_themes'] ?? [];

        if (empty($blocked)) {
            return $value;
        }

        // If value is false or not an object, return as-is
        if (false === $value || !is_object($value)) {
            return $value;
        }

        // Remove blocked themes from the update list
        if (isset($value->response) && is_array($value->response)) {
            foreach ($blocked as $theme_slug) {
                unset($value->response[$theme_slug]);
            }
        }

        // Also remove from no_update and checked arrays
        if (isset($value->no_update) && is_array($value->no_update)) {
            foreach ($blocked as $theme_slug) {
                unset($value->no_update[$theme_slug]);
            }
        }

        if (isset($value->checked) && is_array($value->checked)) {
            foreach ($blocked as $theme_slug) {
                unset($value->checked[$theme_slug]);
            }
        }

        return $value;
    }

    /**
     * Hide update notice for blocked plugins in the plugins list.
     *
     * @since 1.9.0
     *
     * @param array $plugin_meta An array of the plugin's metadata
     * @param string $plugin_file Path to the plugin file relative to the plugins directory
     *
     * @return array Modified metadata array with update notice removed
     */
    public function hide_plugin_update_notice($plugin_meta, $plugin_file)
    {
        $fuertewp = Fuerte_Wp_Config::get_config();
        $blocked = $fuertewp['blocked_plugins'] ?? [];

        if (empty($blocked)) {
            return $plugin_meta;
        }

        // Check if this plugin is blocked
        if (in_array($plugin_file, $blocked)) {
            // Filter out any update-related metadata
            return array_filter($plugin_meta, function ($meta) {
                return !is_array($meta) || !isset($meta['update']);
            });
        }

        return $plugin_meta;
    }

    /**
     * Hide update notice for blocked themes in the themes list.
     *
     * @since 1.9.0
     *
     * @param array $theme_meta An array of the theme's metadata
     * @param string $stylesheet Directory name of the theme
     *
     * @return array Modified metadata array with update notice removed
     */
    public function hide_theme_update_notice($theme_meta, $stylesheet)
    {
        $fuertewp = Fuerte_Wp_Config::get_config();
        $blocked = $fuertewp['blocked_themes'] ?? [];

        if (empty($blocked)) {
            return $theme_meta;
        }

        // Check if this theme is blocked
        if (in_array($stylesheet, $blocked)) {
            // Filter out any update-related metadata
            return array_filter($theme_meta, function ($meta) {
                return !is_array($meta) || !isset($meta['update']);
            });
        }

        return $theme_meta;
    }
} // Class Fuerte_Wp_Auto_Update_Manager
