<?php

/**
 * Migration Utility for Fuerte-WP.
 *
 * Migrates data from Carbon Fields individual options to HyperFields single array storage.
 *
 * @since      1.8.0
 */

// No access outside WP
defined('ABSPATH') || die();

class Fuerte_Wp_Migrator
{
    /**
     * Run the migration from legacy options to HyperFields.
     *
     * @return array Migration results
     */
    public static function migrate()
    {
        $settings = get_option('fuertewp_settings', []);
        $migrated_count = 0;
        $legacy_found = false;

        // List of all keys to migrate (without prefix)
        $keys = [
            'status',
            'super_users',
            'access_denied_message',
            'recovery_email',
            'sender_email_enable',
            'sender_email',
            'autoupdate_core',
            'autoupdate_plugins',
            'autoupdate_themes',
            'autoupdate_translations',
            'autoupdate_frequency',
            'login_enable',
            'registration_enable',
            'login_max_attempts',
            'login_lockout_duration',
            'login_increasing_lockout',
            'login_ip_headers',
            'login_gdpr_message',
            'login_data_retention',
            'login_url_hiding_enabled',
            'custom_login_slug',
            'login_url_type',
            'redirect_invalid_logins',
            'redirect_invalid_logins_url',
            'restrictions_restapi_loggedin_only',
            'restrictions_restapi_disable_app_passwords',
            'restrictions_disable_xmlrpc',
            'restrictions_htaccess_security_rules',
            'restrictions_disable_admin_create_edit',
            'restrictions_disable_weak_passwords',
            'restrictions_force_strong_passwords',
            'restrictions_disable_admin_bar_roles',
            'restrictions_restrict_permalinks',
            'restrictions_restrict_acf',
            'restrictions_disable_theme_editor',
            'restrictions_disable_plugin_editor',
            'restrictions_disable_theme_install',
            'restrictions_disable_plugin_install',
            'restrictions_disable_customizer_css',
            'emails_fatal_error',
            'emails_automatic_updates',
            'emails_comment_awaiting_moderation',
            'emails_comment_has_been_published',
            'emails_user_reset_their_password',
            'emails_user_confirm_personal_data_export_request',
            'emails_new_user_created',
            'emails_network_new_site_created',
            'emails_network_new_user_site_registered',
            'emails_network_new_site_activated',
            'tweaks_use_site_logo_login',
            'restricted_scripts',
            'restricted_pages',
            'removed_menus',
            'removed_submenus',
            'removed_adminbar_menus',
            'deferred_plugins',
            'deferred_themes',
            'blocked_plugins',
            'blocked_themes',
        ];

        // Multiselect keys that used Carbon Fields pattern
        $multiselect_keys = [
            'super_users',
            'restrictions_disable_admin_bar_roles',
            'restricted_scripts',
            'restricted_pages',
            'removed_menus',
            'removed_submenus',
            'removed_adminbar_menus',
            'deferred_plugins',
            'deferred_themes',
            'blocked_plugins',
            'blocked_themes',
        ];

        foreach ($keys as $key) {
            $hf_key = "fuertewp_{$key}";

            // Only migrate if not already in HyperFields
            if (isset($settings[$hf_key])) {
                continue;
            }

            $value = null;
            $is_multiselect = in_array($key, $multiselect_keys);

            if ($is_multiselect) {
                $value = self::load_legacy_multiselect("fuertewp_{$key}");
            } else {
                $value = get_option("_fuertewp_{$key}");
            }

            if ($value !== false && $value !== null) {
                $settings[$hf_key] = $value;
                $migrated_count++;
                $legacy_found = true;
            }
        }

        if ($legacy_found) {
            update_option('fuertewp_settings', $settings);
            Fuerte_Wp_Config::invalidate_cache();
        }

        // Ensure status is always set, defaulting to 'enabled' if not found
        if (!isset($settings['fuertewp_status']) || empty($settings['fuertewp_status'])) {
            $settings['fuertewp_status'] = 'enabled';
            update_option('fuertewp_settings', $settings);
            Fuerte_Wp_Config::invalidate_cache();
        }

        return [
            'migrated_keys' => $migrated_count,
            'settings_updated' => $legacy_found,
        ];
    }

    /**
     * Load multiselect values from Carbon Fields pattern.
     */
    private static function load_legacy_multiselect($field_name)
    {
        global $wpdb;

        $option_name_prefix = "_{$field_name}|||";
        $values = [];

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE %s
             ORDER BY option_name ASC",
            $option_name_prefix . '%'
        ));

        if (empty($results)) {
            return null;
        }

        foreach ($results as $result) {
            if (preg_match('/\|\|\|(\d+)\|value$/', $result->option_name, $matches)) {
                $values[intval($matches[1])] = $result->option_value;
            }
        }

        ksort($values);

        return array_values($values);
    }
}
