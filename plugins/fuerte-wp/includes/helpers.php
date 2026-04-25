<?php

/**
 * Fuerte-WP Helpers.
 *
 * @link       https://actitud.xyz
 * @since      1.3.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

// Static cache for performance
static $fuertewp_admin_users_cache = null;
static $fuertewp_wp_roles_cache = null;
static $fuertewp_plugins_cache = null;
static $fuertewp_themes_cache = null;

/**
 * Get WordPress admin users (cached).
 */
function fuertewp_get_admin_users()
{
    global $fuertewp_admin_users_cache;

    if (null === $fuertewp_admin_users_cache) {
        $users = get_users(['role__in' => ['administrator']]);
        $admins = [];

        foreach ($users as $user) {
            $admins[$user->user_email] = $user->user_login . '[' . $user->user_email . ']';
        }

        $fuertewp_admin_users_cache = $admins;
    }

    return $fuertewp_admin_users_cache;
}

/**
 * Get a list of WordPress roles (cached).
 */
function fuertewp_get_wp_roles()
{
    global $fuertewp_wp_roles_cache;

    if (null === $fuertewp_wp_roles_cache) {
        global $wp_roles;

        $roles = $wp_roles->roles;
        // https://developer.wordpress.org/reference/hooks/editable_roles/
        $editable_roles = apply_filters('editable_roles', $roles);

        // We only need the role slug (id) and name
        $returned_roles = [];

        foreach ($editable_roles as $id => $role) {
            $returned_roles[$id] = $role['name'];
        }

        $fuertewp_wp_roles_cache = $returned_roles;
    }

    return $fuertewp_wp_roles_cache;
}

/**
 * Check if an option exists.
 *
 * https://core.trac.wordpress.org/ticket/51699
 */
function fuertewp_option_exists($option_name, $site_wide = false)
{
    global $wpdb;

    return (bool) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM ' . ($site_wide ? $wpdb->base_prefix : $wpdb->prefix) . 'options WHERE option_name = %s LIMIT 1',
        $option_name
    ));
}

/**
 * Customizer disable Additional CSS editor.
 */
function fuertewp_customizer_remove_css_editor($wp_customize)
{
    $wp_customize->remove_section('custom_css');
}

/**
 * REST API restrict access to logged in users only.
 * https://developer.wordpress.org/rest-api/frequently-asked-questions/#require-authentication-for-all-requests.
 */
function fuertewp_restapi_loggedin_only($result)
{
    // If a previous authentication check was applied,
    // pass that result along without modification.
    if (true === $result || is_wp_error($result)) {
        return $result;
    }

    // Exclude JWT auth token endpoints URLs
    if (false !== stripos($_SERVER['REQUEST_URI'], 'jwt-auth')) {
        return $result;
    }

    // No authentication has been performed yet.
    // Return an error if user is not logged in.
    if (!is_user_logged_in()) {
        return new WP_Error(
            'rest_not_logged_in',
            __('You are not currently logged in.'),
            ['status' => 401]
        );
    }

    // Our custom authentication check should have no effect
    // on logged-in requests
    return $result;
}

/**
 * Declare a new interval for scheduled tasks
 * Every 6 hours.
 */
function fuertewp_scheduled_tasks_interval($schedules)
{
    $schedules['six_hours'] = [
        'interval' => 21600,
        'display' => __('Six Hours'),
    ];

    return $schedules;
}
add_filter('cron_schedules', 'fuertewp_scheduled_tasks_interval');

/**
 * Get list of installed plugins (cached).
 *
 * @since 1.8.0
 *
 * @return array Array of plugins with plugin file as key and name as value
 */
function fuertewp_get_installed_plugins()
{
    global $fuertewp_plugins_cache;

    if (null === $fuertewp_plugins_cache) {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $plugin_list = [];

        foreach ($plugins as $plugin_file => $plugin_data) {
            $plugin_list[$plugin_file] = $plugin_data['Name'];
        }

        $fuertewp_plugins_cache = $plugin_list;
    }

    return $fuertewp_plugins_cache;
}

/**
 * Get list of installed themes (cached).
 *
 * @since 1.8.0
 *
 * @return array Array of themes with theme slug as key and name as value
 */
function fuertewp_get_installed_themes()
{
    global $fuertewp_themes_cache;

    if (null === $fuertewp_themes_cache) {
        $themes = wp_get_themes();
        $theme_list = [];

        foreach ($themes as $theme_slug => $theme) {
            $theme_list[$theme_slug] = $theme->get('Name');
        }

        $fuertewp_themes_cache = $theme_list;
    }

    return $fuertewp_themes_cache;
}

/*
 * Write log
 *
 * @param mixed $log
 *
 * @return void
 */
if (!function_exists('write_log')) {
    function write_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                // Debug logging removed for production
            } else {
                // Debug logging removed for production
            }
        }
    }
}
