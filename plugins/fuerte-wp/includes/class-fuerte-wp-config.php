<?php

/**
 * Simple Configuration Manager for Fuerte-WP.
 *
 * Uses WordPress transients for configuration storage and caching.
 * Much simpler than the previous complex config cache system.
 *
 * @link       https://actitud.xyz
 * @since      1.7.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Simple configuration manager class.
 *
 * @since 1.7.0
 */
class Fuerte_Wp_Config
{
    /**
     * Transient key for configuration cache.
     *
     * @since 1.7.0
     *
     * @var string
     */
    private static $transient_key = 'fuertewp_config';

    /**
     * Cache expiration time in seconds (12 hours).
     *
     * @since 1.7.0
     *
     * @var int
     */
    private static $cache_expiration = 12 * HOUR_IN_SECONDS;

    /**
     * Get configuration value.
     *
     * @since 1.7.0
     *
     * @param string $key Configuration key (dot notation supported)
     * @param mixed $default Default value if key doesn't exist
     *
     * @return mixed Configuration value
     */
    public static function get($key, $default = null)
    {
        $config = self::get_config();

        // Support dot notation for nested keys
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $config;

            foreach ($keys as $k) {
                if (!isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }

            return $value;
        }

        return $config[$key] ?? $default;
    }

    /**
     * Get the entire configuration array.
     *
     * @since 1.7.0
     *
     * @param bool $bypass_cache Force loading from database, bypassing transient cache
     *
     * @return array Configuration array
     */
    public static function get_config($bypass_cache = false)
    {
        // Bypass cache if requested or if on settings page for admin users
        $force_bypass = $bypass_cache || self::should_bypass_cache();

        // Try to get from transient first (unless bypassing)
        if (!$force_bypass) {
            $cached_config = get_transient(self::$transient_key);

            if ($cached_config !== false) {
                return $cached_config;
            }
        }

        // Load from file first if it exists (file takes priority)
        $config = self::load_from_file();

        if (empty($config)) {
            // Fallback to database if no file config
            $config = self::load_from_database();
        }

        // Only save to cache if not bypassing
        if (!$force_bypass) {
            self::save_config($config);
        }

        return $config;
    }

    /**
     * Load configuration from wp-config-fuerte.php file.
     *
     * @since 1.7.0
     *
     * @return array Configuration array from file, empty array if file doesn't exist
     */
    private static function load_from_file()
    {
        if (!file_exists(ABSPATH . 'wp-config-fuerte.php')) {
            return [];
        }

        // Include the file to get the $fuertewp global variable
        global $fuertewp;

        // Store current state to avoid conflicts
        $original_fuertewp = $fuertewp ?? [];

        // Clear and reload the file
        $fuertewp = [];
        require_once ABSPATH . 'wp-config-fuerte.php';

        // Get the configuration
        $config = is_array($fuertewp) ? $fuertewp : [];

        // Restore original state
        $fuertewp = $original_fuertewp;

        return $config;
    }

    /**
     * Save configuration to transient.
     *
     * @since 1.7.0
     *
     * @param array $config Configuration array
     *
     * @return bool Success status
     */
    public static function save_config($config)
    {
        return set_transient(self::$transient_key, $config, self::$cache_expiration);
    }

    /**
     * Invalidate configuration cache.
     *
     * @since 1.7.0
     */
    public static function invalidate_cache()
    {
        delete_transient(self::$transient_key);
    }

    /**
     * Force configuration refresh from database.
     * Useful for debugging cache issues.
     *
     * @since 1.7.2
     *
     * @return array Fresh configuration from database
     */
    public static function force_refresh()
    {
        self::invalidate_cache();

        return self::get_config(true);
    }

    /**
     * Debug method to check what's in the cache vs database.
     * Only works for admin users and when WP_DEBUG is enabled.
     *
     * @since 1.7.2
     *
     * @return array Debug information
     */
    public static function debug_cache()
    {
        if (!current_user_can('manage_options') || !defined('WP_DEBUG') || !WP_DEBUG) {
            return [];
        }

        $cached = get_transient(self::$transient_key);
        $fresh = self::get_config(true);

        return [
            'cache_key' => self::$transient_key,
            'cache_exists' => $cached !== false,
            'cache_super_users' => $cached['super_users'] ?? 'not_set',
            'fresh_super_users' => $fresh['super_users'] ?? 'not_set',
            'cache_match' => json_encode($cached) === json_encode($fresh),
            'current_url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'current_screen' => (function_exists('get_current_screen') && get_current_screen()) ? get_current_screen()->id : 'function_not_available_or_no_screen',
        ];
    }

    /**
     * Load configuration from database.
     *
     * @since 1.7.0
     *
     * @return array Configuration from database
     */
    private static function load_from_database()
    {
        // Get settings from HyperFields storage
        $fuertewp_settings = get_option('fuertewp_settings', []);

        // Load legacy settings
        $legacy_settings = self::load_from_legacy_database();

        if (!empty($fuertewp_settings) && is_array($fuertewp_settings)) {
            $normalized_hf = self::normalize_settings($fuertewp_settings);

            // Remove null values recursively to avoid overwriting defaults
            $normalized_hf = self::array_filter_recursive($normalized_hf);

            // Merge: HyperFields takes precedence
            return array_replace_recursive($legacy_settings, $normalized_hf);
        }

        return $legacy_settings;
    }

    /**
     * Normalize settings from HyperFields to the internal config structure.
     *
     * @since 1.7.0
     *
     * @param array $settings Settings from fuertewp_settings option
     *
     * @return array Normalized config array
     */
    private static function normalize_settings($settings)
    {
        return [
            'status' => $settings['fuertewp_status'] ?? 'enabled',
            'super_users' => $settings['fuertewp_super_users'] ?? [],
            'general' => [
                'access_denied_message' => $settings['fuertewp_access_denied_message'] ?? 'Access denied.',
                'recovery_email' => $settings['fuertewp_recovery_email'] ?? '',
                'sender_email_enable' => $settings['fuertewp_sender_email_enable'] ?? true,
                'sender_email' => $settings['fuertewp_sender_email'] ?? '',
                'autoupdate_core' => $settings['fuertewp_autoupdate_core'] ?? false,
                'autoupdate_plugins' => $settings['fuertewp_autoupdate_plugins'] ?? false,
                'autoupdate_themes' => $settings['fuertewp_autoupdate_themes'] ?? false,
                'autoupdate_translations' => $settings['fuertewp_autoupdate_translations'] ?? false,
                'autoupdate_frequency' => $settings['fuertewp_autoupdate_frequency'] ?? 'daily',
            ],
            'restrictions' => [
                'restapi_loggedin_only' => $settings['fuertewp_restrictions_restapi_loggedin_only'] ?? null,
                'restapi_disable_app_passwords' => $settings['fuertewp_restrictions_restapi_disable_app_passwords'] ?? null,
                'disable_xmlrpc' => $settings['fuertewp_restrictions_disable_xmlrpc'] ?? null,
                'htaccess_security_rules' => $settings['fuertewp_restrictions_htaccess_security_rules'] ?? null,
                'disable_admin_create_edit' => $settings['fuertewp_restrictions_disable_admin_create_edit'] ?? null,
                'disable_weak_passwords' => $settings['fuertewp_restrictions_disable_weak_passwords'] ?? null,
                'force_strong_passwords' => $settings['fuertewp_restrictions_force_strong_passwords'] ?? null,
                'disable_admin_bar_roles' => $settings['fuertewp_restrictions_disable_admin_bar_roles'] ?? null,
                'restrict_permalinks' => $settings['fuertewp_restrictions_restrict_permalinks'] ?? null,
                'restrict_acf' => $settings['fuertewp_restrictions_restrict_acf'] ?? null,
                'disable_theme_editor' => $settings['fuertewp_restrictions_disable_theme_editor'] ?? null,
                'disable_plugin_editor' => $settings['fuertewp_restrictions_disable_plugin_editor'] ?? null,
                'disable_theme_install' => $settings['fuertewp_restrictions_disable_theme_install'] ?? null,
                'disable_plugin_install' => $settings['fuertewp_restrictions_disable_plugin_install'] ?? null,
                'disable_customizer_css' => $settings['fuertewp_restrictions_disable_customizer_css'] ?? null,
            ],
            'advanced_restrictions' => [
                'restricted_scripts' => self::parse_textarea($settings['fuertewp_restricted_scripts'] ?? null),
                'restricted_pages' => self::parse_textarea($settings['fuertewp_restricted_pages'] ?? null),
                'removed_menus' => self::parse_textarea($settings['fuertewp_removed_menus'] ?? null),
                'removed_submenus' => self::parse_textarea($settings['fuertewp_removed_submenus'] ?? null),
                'removed_adminbar_menus' => self::parse_textarea($settings['fuertewp_removed_adminbar_menus'] ?? null),
            ],
            'ip_lists' => [
                'username_whitelist' => self::parse_textarea($settings['fuertewp_username_whitelist'] ?? null),
                'block_default_users' => $settings['fuertewp_block_default_users'] ?? null,
                'username_blacklist' => self::parse_textarea($settings['fuertewp_username_blacklist'] ?? null),
                'registration_protect' => $settings['fuertewp_registration_protect'] ?? null,
            ],
            'login_security' => [
                'login_enable' => $settings['fuertewp_login_enable'] ?? null,
                'registration_enable' => $settings['fuertewp_registration_enable'] ?? null,
                'login_max_attempts' => $settings['fuertewp_login_max_attempts'] ?? null,
                'login_lockout_duration' => $settings['fuertewp_login_lockout_duration'] ?? null,
                'login_increasing_lockout' => $settings['fuertewp_login_increasing_lockout'] ?? null,
                'login_ip_headers' => self::parse_textarea($settings['fuertewp_login_ip_headers'] ?? null),
                'login_gdpr_message' => $settings['fuertewp_login_gdpr_message'] ?? null,
                'login_data_retention' => $settings['fuertewp_login_data_retention'] ?? null,
                'login_url_hiding_enabled' => $settings['fuertewp_login_url_hiding_enabled'] ?? null,
                'custom_login_slug' => $settings['fuertewp_custom_login_slug'] ?? null,
                'login_url_type' => $settings['fuertewp_login_url_type'] ?? null,
                'redirect_invalid_logins' => $settings['fuertewp_redirect_invalid_logins'] ?? null,
                'redirect_invalid_logins_url' => $settings['fuertewp_redirect_invalid_logins_url'] ?? null,
            ],
            'deferred_updates' => [
                'plugins' => $settings['fuertewp_deferred_plugins'] ?? null,
                'themes' => $settings['fuertewp_deferred_themes'] ?? null,
            ],
        ];
    }

    /**
     * Parse textarea string to array.
     *
     * @param mixed $value Textarea value
     *
     * @return mixed Array if string, original value otherwise
     */
    private static function parse_textarea($value)
    {
        if (is_string($value)) {
            return array_filter(array_map('trim', explode("\n", $value)));
        }

        return $value;
    }

    /**
     * Load configuration from legacy database structure.
     *
     * @since 1.7.0
     *
     * @return array Configuration from database
     */
    private static function load_from_legacy_database()
    {
        $config = [];

        // Load status from _fuertewp_status option
        $config['status'] = get_option('_fuertewp_status', 'enabled');

        // Load super users with special handling for multiselect field
        $config['super_users'] = self::load_multiselect_field('fuertewp_super_users');

        // Load general settings
        $config['general'] = [
            'access_denied_message' => get_option('_fuertewp_access_denied_message', 'Access denied.'),
            'recovery_email' => get_option('_fuertewp_recovery_email', ''),
            'sender_email_enable' => get_option('_fuertewp_sender_email_enable', true),
            'sender_email' => get_option('_fuertewp_sender_email', ''),
            'autoupdate_core' => get_option('_fuertewp_autoupdate_core', false),
            'autoupdate_plugins' => get_option('_fuertewp_autoupdate_plugins', false),
            'autoupdate_themes' => get_option('_fuertewp_autoupdate_themes', false),
            'autoupdate_translations' => get_option('_fuertewp_autoupdate_translations', false),
            'autoupdate_frequency' => get_option('_fuertewp_autoupdate_frequency', 'daily'),
        ];

        // Load login security settings
        $config['login_security'] = [
            'login_enable' => get_option('_fuertewp_login_enable', 'enabled'),
            'registration_enable' => get_option('_fuertewp_registration_enable', 'enabled'),
            'login_max_attempts' => intval(get_option('_fuertewp_login_max_attempts', 5)),
            'login_lockout_duration' => intval(get_option('_fuertewp_login_lockout_duration', 60)),
            'login_increasing_lockout' => get_option('_fuertewp_login_increasing_lockout', false),
            'login_ip_headers' => get_option('_fuertewp_login_ip_headers', ''),
            'login_gdpr_message' => get_option('_fuertewp_login_gdpr_message', ''),
            'login_data_retention' => intval(get_option('_fuertewp_login_data_retention', 30)),
            'login_url_hiding_enabled' => get_option('_fuertewp_login_url_hiding_enabled', false) ? '1' : '',
            'custom_login_slug' => get_option('_fuertewp_custom_login_slug', 'secure-login'),
            'login_url_type' => get_option('_fuertewp_login_url_type', 'query_param'),
            'redirect_invalid_logins' => get_option('_fuertewp_redirect_invalid_logins', 'home_404'),
            'redirect_invalid_logins_url' => get_option('_fuertewp_redirect_invalid_logins_url', ''),
        ];

        // Load restrictions
        $config['restrictions'] = [
            'restapi_loggedin_only' => get_option('_fuertewp_restrictions_restapi_loggedin_only', false),
            'restapi_disable_app_passwords' => get_option('_fuertewp_restrictions_restapi_disable_app_passwords', true),
            'disable_xmlrpc' => get_option('_fuertewp_restrictions_disable_xmlrpc', true),
            'htaccess_security_rules' => get_option('_fuertewp_restrictions_htaccess_security_rules', true),
            'disable_admin_create_edit' => get_option('_fuertewp_restrictions_disable_admin_create_edit', true),
            'disable_weak_passwords' => get_option('_fuertewp_restrictions_disable_weak_passwords', true),
            'force_strong_passwords' => get_option('_fuertewp_restrictions_force_strong_passwords', false),
            'disable_admin_bar_roles' => get_option('_fuertewp_restrictions_disable_admin_bar_roles', ['subscriber', 'customer']),
            'restrict_permalinks' => get_option('_fuertewp_restrictions_restrict_permalinks', true),
            'restrict_acf' => get_option('_fuertewp_restrictions_restrict_acf', true),
            'disable_theme_editor' => get_option('_fuertewp_restrictions_disable_theme_editor', true),
            'disable_plugin_editor' => get_option('_fuertewp_restrictions_disable_plugin_editor', true),
            'disable_theme_install' => get_option('_fuertewp_restrictions_disable_theme_install', true),
            'disable_plugin_install' => get_option('_fuertewp_restrictions_disable_plugin_install', true),
            'disable_customizer_css' => get_option('_fuertewp_restrictions_disable_customizer_css', true),
        ];

        // Load email settings
        $config['emails'] = [
            'fatal_error' => get_option('_fuertewp_emails_fatal_error', true),
            'automatic_updates' => get_option('_fuertewp_emails_automatic_updates', false),
            'comment_awaiting_moderation' => get_option('_fuertewp_emails_comment_awaiting_moderation', false),
            'comment_has_been_published' => get_option('_fuertewp_emails_comment_has_been_published', false),
            'user_reset_their_password' => get_option('_fuertewp_emails_user_reset_their_password', false),
            'user_confirm_personal_data_export_request' => get_option('_fuertewp_emails_user_confirm_personal_data_export_request', false),
            'new_user_created' => get_option('_fuertewp_emails_new_user_created', true),
            'network_new_site_created' => get_option('_fuertewp_emails_network_new_site_created', false),
            'network_new_user_site_registered' => get_option('_fuertewp_emails_network_new_user_site_registered', false),
            'network_new_site_activated' => get_option('_fuertewp_emails_network_new_site_activated', false),
        ];

        // Load tweaks
        $config['tweaks'] = [
            'use_site_logo_login' => get_option('_fuertewp_tweaks_use_site_logo_login', false),
        ];

        // Load deferred updates
        $config['deferred_plugins'] = self::load_multiselect_field('fuertewp_deferred_plugins');
        $config['deferred_themes'] = self::load_multiselect_field('fuertewp_deferred_themes');

        // Load blocked updates
        $config['blocked_plugins'] = self::load_multiselect_field('fuertewp_blocked_plugins');
        $config['blocked_themes'] = self::load_multiselect_field('fuertewp_blocked_themes');

        // Load restricted lists
        $config['restricted_scripts'] = self::load_multiselect_field('fuertewp_restricted_scripts');
        $config['restricted_pages'] = self::load_multiselect_field('fuertewp_restricted_pages');
        $config['removed_menus'] = self::load_multiselect_field('fuertewp_removed_menus');
        $config['removed_submenus'] = self::load_multiselect_field('fuertewp_removed_submenus');
        $config['removed_adminbar_menus'] = self::load_multiselect_field('fuertewp_removed_adminbar_menus');

        return $config;
    }

    /**
     * Check if cache should be bypassed for the current request.
     * Bypasses cache for admin users on settings pages to ensure fresh data.
     *
     * @since 1.7.2
     *
     * @return bool True if cache should be bypassed
     */
    private static function should_bypass_cache()
    {
        // Only bypass for admin requests
        if (!is_admin()) {
            return false;
        }

        // Check if current user can manage options (admin level)
        if (!current_user_can('manage_options')) {
            return false;
        }

        // Bypass for any page with fuerte-wp in the URL
        if (isset($_GET['page']) && strpos($_GET['page'], 'fuerte-wp') !== false) {
            return true;
        }

        // Only check screen if we're in admin area and function exists
        if (function_exists('get_current_screen')) {
            $current_screen = get_current_screen();

            if ($current_screen) {
                // Bypass for Fuerte-WP settings pages
                if ($current_screen->id === 'settings_page_fuerte-wp-options' ||
                    $current_screen->id === 'toplevel_page_fuerte-wp-options') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Standardized method to get any configuration value with proper fallback logic.
     * Handles both direct options and Carbon Fields multiselect patterns automatically.
     *
     * @since 1.7.2
     *
     * @param string $key Configuration key (without _fuertewp prefix)
     * @param mixed $default Default value if option doesn't exist
     * @param bool $is_multiselect Whether this field uses Carbon Fields multiselect pattern
     *
     * @return mixed Configuration value
     */
    public static function get_field($key, $default = null, $is_multiselect = false)
    {
        // Try HyperFields storage first
        $settings = get_option('fuertewp_settings', []);
        $hf_key = "fuertewp_{$key}";

        if (isset($settings[$hf_key])) {
            $value = $settings[$hf_key];

            // Handle textarea format if it's one of the known textarea fields
            $textarea_fields = [
                'username_blacklist',
                'username_whitelist',
                'restricted_scripts',
                'restricted_pages',
                'removed_menus',
                'removed_submenus',
                'removed_adminbar_menus',
                'login_ip_headers',
            ];

            if (in_array($key, $textarea_fields)) {
                return self::parse_textarea($value);
            }

            return $value;
        }

        // Fallback to legacy structure
        $option_name = "_fuertewp_{$key}";

        if ($is_multiselect) {
            return self::load_multiselect_field("fuertewp_{$key}");
        }

        return get_option($option_name, $default);
    }

    /**
     * Standardized method to set any configuration value.
     *
     * @since 1.7.2
     *
     * @param string $key Configuration key (without _fuertewp prefix)
     * @param mixed $value Value to set
     * @param bool $is_multiselect Whether this field uses Carbon Fields multiselect pattern
     *
     * @return bool Success status
     */
    public static function set_field($key, $value, $is_multiselect = false)
    {
        // Save to HyperFields storage
        $settings = get_option('fuertewp_settings', []);
        $hf_key = "fuertewp_{$key}";
        $settings[$hf_key] = $value;
        $result = update_option('fuertewp_settings', $settings);

        // Also update legacy if needed for backwards compatibility during migration
        $option_name = "_fuertewp_{$key}";

        if ($is_multiselect && is_array($value)) {
            self::save_multiselect_field("fuertewp_{$key}", $value);
        } else {
            update_option($option_name, $value);
        }

        return $result;
    }

    /**
     * Save multiselect field values using Carbon Fields compatible pattern.
     *
     * @since 1.7.2
     *
     * @param string $field_name Base field name without prefix
     * @param array $values Array of values to save
     *
     * @return bool Success status
     */
    public static function save_multiselect_field($field_name, $values)
    {
        global $wpdb;

        // First, delete all existing entries for this field
        $option_name_prefix = "_{$field_name}|||";
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $option_name_prefix . '%'
        ));

        // Now insert the new values
        $success = true;

        foreach ($values as $index => $value) {
            $option_name = "_{$field_name}|||{$index}|value";
            $result = update_option($option_name, $value);

            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Clean up duplicate storage patterns from database.
     * Removes old Carbon Fields storage patterns while preserving _fuertewp_* data.
     *
     * @since 1.7.2
     *
     * @return array Cleanup results
     */
    public static function cleanup_duplicate_patterns()
    {
        global $wpdb;

        $cleanup_results = [
            'deleted_fuertewp_options' => 0,
            'deleted_carbon_fields_options' => 0,
            'preserved_fuertewp_options' => 0,
        ];

        // Only proceed if user has admin capabilities
        if (!current_user_can('manage_options')) {
            return $cleanup_results;
        }

        // Get all fuerte-related options
        $fuerte_options = $wpdb->get_results(
            "SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE '%fuerte%'
             ORDER BY option_name ASC"
        );

        foreach ($fuerte_options as $option) {
            $option_name = $option->option_name;

            // Delete old fuertewp_* options (without underscore prefix)
            if (strpos($option_name, 'fuertewp_') === 0 && strpos($option_name, '_fuertewp_') !== 0) {
                // Special handling for login_db_version migration
                if ($option_name === 'fuertewp_login_db_version') {
                    $underscored_name = '_fuertewp_login_db_version';

                    if (!get_option($underscored_name)) {
                        $current_version = defined('FUERTEWP_VERSION') ? FUERTEWP_VERSION : '1.0.0';
                        update_option($underscored_name, $current_version); // Set to current plugin version
                        $cleanup_results['preserved_fuertewp_options']++;
                    }
                } else {
                    // First, migrate data to _fuertewp_ version if it doesn't exist
                    $underscored_name = '_' . $option_name;

                    if (!get_option($underscored_name)) {
                        update_option($underscored_name, $option->option_value);
                        $cleanup_results['preserved_fuertewp_options']++;
                    }
                }

                // Delete the old option
                delete_option($option_name);
                $cleanup_results['deleted_fuertewp_options']++;
                continue;
            }

            // Delete Carbon Fields compacted options
            if (strpos($option_name, '_carbon_fields_theme_options_fuerte-wp') === 0) {
                delete_option($option_name);
                $cleanup_results['deleted_carbon_fields_options']++;
                continue;
            }

            // Delete double underscore options created by Carbon Fields field definitions
            if (strpos($option_name, '__fuertewp_') === 0) {
                // First, migrate data to single underscore version if it doesn't exist
                $single_underscore_name = '_' . substr($option_name, 2); // Remove one underscore

                if (!get_option($single_underscore_name)) {
                    update_option($single_underscore_name, $option->option_value);
                    $cleanup_results['preserved_fuertewp_options']++;
                }

                // Delete the double underscore option
                delete_option($option_name);
                $cleanup_results['deleted_fuertewp_options']++;
                continue;
            }
        }

        // Clear the cache after cleanup
        self::invalidate_cache();

        return $cleanup_results;
    }

    /**
     * Get configuration batch for enforcer class using standardized methods.
     * Replaces all direct get_option/carbon_get_theme_option calls.
     *
     * @since 1.7.2
     *
     * @return array Configuration options array
     */
    public static function get_enforcer_config()
    {
        $config = self::get_config();

        // Map internal config to enforcer expected keys
        return [
            'fuertewp_status' => $config['status'] ?? 'enabled',
            'fuertewp_super_users' => $config['super_users'] ?? [],

            'fuertewp_access_denied_message' => $config['general']['access_denied_message'] ?? 'Access denied.',
            'fuertewp_recovery_email' => $config['general']['recovery_email'] ?? '',
            'fuertewp_sender_email_enable' => $config['general']['sender_email_enable'] ?? true,
            'fuertewp_sender_email' => $config['general']['sender_email'] ?? '',
            'fuertewp_autoupdate_core' => $config['general']['autoupdate_core'] ?? false,
            'fuertewp_autoupdate_plugins' => $config['general']['autoupdate_plugins'] ?? false,
            'fuertewp_autoupdate_themes' => $config['general']['autoupdate_themes'] ?? false,
            'fuertewp_autoupdate_translations' => $config['general']['autoupdate_translations'] ?? false,
            'fuertewp_autoupdate_frequency' => $config['general']['autoupdate_frequency'] ?? 'daily',

            'fuertewp_login_enable' => $config['login_security']['login_enable'] ?? 'enabled',
            'fuertewp_registration_enable' => $config['login_security']['registration_enable'] ?? 'enabled',
            'fuertewp_login_max_attempts' => $config['login_security']['login_max_attempts'] ?? 5,
            'fuertewp_login_lockout_duration' => $config['login_security']['login_lockout_duration'] ?? 60,
            'fuertewp_login_increasing_lockout' => $config['login_security']['login_increasing_lockout'] ?? false,
            'fuertewp_login_ip_headers' => $config['login_security']['login_ip_headers'] ?? '',
            'fuertewp_login_gdpr_message' => $config['login_security']['login_gdpr_message'] ?? '',
            'fuertewp_login_data_retention' => $config['login_security']['login_data_retention'] ?? 30,
            'fuertewp_login_url_hiding_enabled' => $config['login_security']['login_url_hiding_enabled'] ?? false,
            'fuertewp_custom_login_slug' => $config['login_security']['custom_login_slug'] ?? 'secure-login',
            'fuertewp_login_url_type' => $config['login_security']['login_url_type'] ?? 'query_param',
            'fuertewp_redirect_invalid_logins' => $config['login_security']['redirect_invalid_logins'] ?? 'home_404',
            'fuertewp_redirect_invalid_logins_url' => $config['login_security']['redirect_invalid_logins_url'] ?? '',

            'fuertewp_emails_fatal_error' => $config['emails']['fatal_error'] ?? true,
            'fuertewp_emails_automatic_updates' => $config['emails']['automatic_updates'] ?? false,
            'fuertewp_emails_comment_awaiting_moderation' => $config['emails']['comment_awaiting_moderation'] ?? false,
            'fuertewp_emails_comment_has_been_published' => $config['emails']['comment_has_been_published'] ?? false,
            'fuertewp_emails_user_reset_their_password' => $config['emails']['user_reset_their_password'] ?? false,
            'fuertewp_emails_user_confirm_personal_data_export_request' => $config['emails']['user_confirm_personal_data_export_request'] ?? false,
            'fuertewp_emails_new_user_created' => $config['emails']['new_user_created'] ?? true,
            'fuertewp_emails_network_new_site_created' => $config['emails']['network_new_site_created'] ?? false,
            'fuertewp_emails_network_new_user_site_registered' => $config['emails']['network_new_user_site_registered'] ?? false,
            'fuertewp_emails_network_new_site_activated' => $config['emails']['network_new_site_activated'] ?? false,

            'fuertewp_restrictions_restapi_loggedin_only' => $config['restrictions']['restapi_loggedin_only'] ?? false,
            'fuertewp_restrictions_restapi_disable_app_passwords' => $config['restrictions']['restapi_disable_app_passwords'] ?? true,
            'fuertewp_restrictions_disable_xmlrpc' => $config['restrictions']['disable_xmlrpc'] ?? true,
            'fuertewp_restrictions_htaccess_security_rules' => $config['restrictions']['htaccess_security_rules'] ?? true,
            'fuertewp_restrictions_disable_admin_create_edit' => $config['restrictions']['disable_admin_create_edit'] ?? true,
            'fuertewp_restrictions_disable_weak_passwords' => $config['restrictions']['disable_weak_passwords'] ?? true,
            'fuertewp_restrictions_force_strong_passwords' => $config['restrictions']['force_strong_passwords'] ?? false,
            'fuertewp_restrictions_disable_admin_bar_roles' => $config['restrictions']['disable_admin_bar_roles'] ?? ['subscriber', 'customer'],
            'fuertewp_restrictions_restrict_permalinks' => $config['restrictions']['restrict_permalinks'] ?? true,
            'fuertewp_restrictions_restrict_acf' => $config['restrictions']['restrict_acf'] ?? true,
            'fuertewp_restrictions_disable_theme_editor' => $config['restrictions']['disable_theme_editor'] ?? true,
            'fuertewp_restrictions_disable_plugin_editor' => $config['restrictions']['disable_plugin_editor'] ?? true,
            'fuertewp_restrictions_disable_theme_install' => $config['restrictions']['disable_theme_install'] ?? true,
            'fuertewp_restrictions_disable_plugin_install' => $config['restrictions']['disable_plugin_install'] ?? true,
            'fuertewp_restrictions_disable_customizer_css' => $config['restrictions']['disable_customizer_css'] ?? true,

            'fuertewp_tweaks_use_site_logo_login' => $config['tweaks']['use_site_logo_login'] ?? false,

            'fuertewp_restricted_scripts' => $config['restricted_scripts'] ?? [],
            'fuertewp_restricted_pages' => $config['restricted_pages'] ?? [],
            'fuertewp_removed_menus' => $config['removed_menus'] ?? [],
            'fuertewp_removed_submenus' => $config['removed_submenus'] ?? [],
            'fuertewp_removed_adminbar_menus' => $config['removed_adminbar_menus'] ?? [],
            'fuertewp_deferred_plugins' => $config['deferred_plugins'] ?? [],
            'fuertewp_deferred_themes' => $config['deferred_themes'] ?? [],
            'fuertewp_blocked_plugins' => $config['blocked_plugins'] ?? [],
            'fuertewp_blocked_themes' => $config['blocked_themes'] ?? [],
        ];
    }

    /**
     * Load multiselect field values from database.
     * Handles the Carbon Fields _field_name|||index|value pattern.
     *
     * @since 1.7.2
     *
     * @param string $field_name Base field name without prefix
     *
     * @return array Array of field values
     */
    private static function load_multiselect_field($field_name)
    {
        global $wpdb;

        $option_name_prefix = "_{$field_name}|||";
        $values = [];

        // Get all options matching the multiselect pattern
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE %s
             ORDER BY option_name ASC",
            $option_name_prefix . '%'
        ));

        foreach ($results as $result) {
            // Parse the option name to get the index
            // Pattern: _fuertewp_super_users|||0|value
            if (preg_match('/\|\|\|(\d+)\|value$/', $result->option_name, $matches)) {
                $values[intval($matches[1])] = $result->option_value;
            }
        }

        // Sort by index and re-index array
        ksort($values);

        return array_values($values);
    }
    /**
     * Remove null values from array recursively.
     *
     * @param array $array Array to filter
     *
     * @return array Filtered array
     */
    private static function array_filter_recursive($array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = self::array_filter_recursive($value);

                if (empty($value)) {
                    unset($array[$key]);
                }
            } elseif ($value === null) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}
