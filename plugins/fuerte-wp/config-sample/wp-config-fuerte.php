<?php

/**
 * Fuerte-WP configuration.
 * Version: 1.7.0.
 *
 * Author: Esteban Cuevas
 * https://github.com/EstebanForge/Fuerte-WP
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * To debug or test Fuerte-WP.
 */
define('FUERTEWP_DISABLE', false);
define('FUERTEWP_FORCE', false);

/**
 * Edit this configuration array and set up as you like.
 */
$fuertewp = [
    /*
     Control Fuerte-WP status: enabled / disabled
    */
    'status' => 'enabled',
    /*
    Super Users accounts, by email address.
    This users will not be affected by Fuerte-WP's restrictions.
    Add one email per item inside the array.
    */
    'super_users' => [
        'esteban@attitude.cl',
        'esteban@actitud.xyz',
    ],
    /*
    General configuration.
    */
    'general' => [
        'access_denied_message' => 'Access denied.', // Default access denied message.
        'recovery_email' => '', // Admin recovery email. If empty, dev@wpdomain.tld will be used. See https://make.wordpress.org/core/2019/04/16/fatal-error-recovery-mode-in-5-2/
        'sender_email_enable' => true, // Enable custom email sender.
        'sender_email' => '', // Default site sender email. If empty, no-reply@wpdomain.tld will be used.
        'autoupdate_core' => true, // Auto update WP core.
        'autoupdate_plugins' => true, // Auto update plugins.
        'autoupdate_themes' => true, // Auto update themes.
        'autoupdate_translations' => true, // Auto update translations.
        'autoupdate_frequency' => 'twelve_hours', // Update check frequency: six_hours, twelve_hours, daily, twodays.
    ],
    /*
    Login Security - NEW in 1.7.0
    Comprehensive login protection and URL hiding features.
    */
    'login_security' => [
        // Login Security Enable/Disable
        'login_enable' => 'enabled', // Enable login attempt limiting and IP blocking
        'registration_enable' => 'enabled', // Enable registration attempt limiting and bot blocking

        // Rate Limiting & Lockouts
        'login_max_attempts' => 5, // Number of failed attempts before lockout (3-10)
        'login_lockout_duration' => 60, // How long to lock out after max attempts (5-1440 minutes)
        'login_increasing_lockout' => '', // Increase lockout duration exponentially (2x, 4x, 8x, etc.)

        // IP Detection Configuration
        'login_ip_headers' => '', // Custom IP headers (one per line)

        // GDPR Compliance
        'login_gdpr_message' => '', // Custom GDPR privacy message

        // Data Retention
        'login_data_retention' => 30, // Number of days to keep login logs (1-365)

        // Login URL Hiding
        'login_url_hiding_enabled' => false, // Hide wp-login.php and wp-admin access
        'custom_login_slug' => 'secure-login', // Custom login slug (e.g., 'secure-login')
        'login_url_type' => 'query_param', // URL type: 'query_param' (?secure-login) or 'pretty_url' (/secure-login/)

        // Invalid Login Redirect
        'redirect_invalid_logins' => 'home_404', // Where to redirect invalid login attempts: 'home_404' or 'custom_page'
        'redirect_invalid_logins_url' => '', // Custom redirect URL when redirect_invalid_logins is 'custom_page'
    ],
        /*
    Tweaks
    */
    'tweaks' => [
        'use_site_logo_login' => true, // Use customizer logo as WP login logo.
    ],
    /*
    Deferred Updates - NEW in 1.8.0
    Prevent specific plugins/themes from auto-updating
    */
    'deferred_plugins' => [
        // 'plugin-folder/plugin.php', // Add plugin slugs here (one per line)
    ],
    'deferred_themes' => [
        // 'twentytwentythree', // Add theme slugs here (one per line)
    ],
    /*
    Blocked Updates - NEW in 1.9.0
    Completely prevent specific plugins/themes from ALL updates (automatic and manual)
    Use with caution - blocked items will not receive any updates
    */
    'blocked_plugins' => [
        // 'plugin-folder/plugin.php', // Add plugin slugs here (one per line)
    ],
    'blocked_themes' => [
        // 'twentytwentythree', // Add theme slugs here (one per line)
    ],
    /*
    Restrictions - Individual options for fine-grained control
    */
    'restrictions' => [
        'restapi_loggedin_only' => false, // Force REST API to logged in users only
        'restapi_disable_app_passwords' => true, // Disable WP application passwords for REST API
        'disable_xmlrpc' => true, // Disable old XML-RPC API
        'htaccess_security_rules' => true, // Add .htaccess security rules to uploads directory
        'disable_admin_create_edit' => true, // Disable creation of new admin accounts by non super admins
        'disable_weak_passwords' => true, // Disable ability to use weak passwords
        'force_strong_passwords' => false, // Force strong passwords usage, make password field read-only
        'disable_admin_bar_roles' => ['subscriber', 'customer'], // Disable admin bar for specific roles
        'restrict_permalinks' => true, // Restrict Permalinks config access
        'restrict_acf' => true, // Restrict ACF editing access (Custom Fields menu)
        'disable_theme_editor' => true, // Disable WP Theme code editor
        'disable_plugin_editor' => true, // Disable WP Plugin code editor
        'disable_theme_install' => true, // Disable Themes installation
        'disable_plugin_install' => true, // Disable Plugins installation
        'disable_customizer_css' => true, // Disable Customizer Additional CSS
    ],
    /*
    Advanced Restrictions - Control admin interface elements
    */
    'advanced_restrictions' => [
        'restricted_scripts' => [ // Restricted scripts by file name
            'export.php',
            //'plugins.php',
            'update.php',
            'update-core.php',
        ],
        'restricted_pages' => [ // Restricted pages by page URL variable (admin.php?page=)
            'wprocket', // WP-Rocket
            'updraftplus', // UpdraftPlus
            'better-search-replace', // Better Search Replace
            'backwpup', // BackWPup
            'backwpupjobs', // BackWPup
            'backwpupeditjob', // BackWPup
            'backwpuplogs', // BackWPup
            'backwpupbackups', // BackWPup
            'backwpupsettings', // BackWPup
            'limit-login-attempts', // Limit Login Attempts Reloaded
            'wp_stream_settings', // Stream
            'transients-manager', // Transients Manager
            'pw-transients-manager', // Transients Manager
            'envato-market', // Envato Market
            'elementor-license', // Elementor Pro
        ],
        'removed_menus' => [ // Menus to be removed (use menu slug)
            'backwpup', // BackWPup
            'check-email-status', // Check Email
            'limit-login-attempts', // Limit Logins Attempts Reloaded
            'envato-market', // Envato Market
        ],
        'removed_submenus' => [ // Submenus to be removed (parent-menu-slug|submenu-slug)
            'options-general.php|updraftplus', // UpdraftPlus
            'options-general.php|limit-login-attempts', // Limit Logins Attempts Reloaded
            'options-general.php|mainwp_child_tab', // MainWP Child
            'options-general.php|wprocket', // WP-Rocket
            'tools.php|export.php', // WP Export
            'tools.php|transients-manager', // Transients Manager
            'tools.php|pw-transients-manager', // Transients Manager
            'tools.php|better-search-replace', // Better Search Replace
        ],
        'removed_adminbar_menus' => [ // Admin bar menus to be removed (use adminbar-item-node-id)
            'wp-logo', // WP Logo
            'tm-suspend', // Transients Manager
            'updraft_admin_node', // UpdraftPlus
        ],
    ],
    /*
    Username Lists - NEW in 1.7.0
    Control access based on usernames.
    */
    'username_lists' => [
        'whitelist' => '', // Allowed usernames (one per line). Empty to disable whitelist.
        'block_default_users' => true, // Block default/admin-like usernames during registration
        'blacklist' => '', // Blocked usernames (one per line). Empty to use default blacklist.
    ],
    /*
    Registration Protection - NEW in 1.7.0
    Control user registration settings.
    */
    'registration' => [
        'registration_protect' => true, // Enable registration protection and bot blocking
    ],
    /*
    Controls several WordPress notification emails, mainly targeted to site/network admin email address.
    True to keep an email enabled. False to disable an email.
    */
    'emails' => [
        'fatal_error' => true,  // Site admin OR recovery_email address
        'automatic_updates' => false, // Site admin
        'comment_awaiting_moderation' => false, // Site admin
        'comment_has_been_published' => false, // Post author
        'user_reset_their_password' => false, // Site admin
        'user_confirm_personal_data_export_request' => false, // Site admin
        'new_user_created' => true,  // Site admin
        'network_new_site_created' => false, // Network admin
        'network_new_user_site_registered' => false, // Network admin
        'network_new_site_activated' => false, // Network admin
    ],
    /*
    NOT WORKING. WORK IN PROGRESS.

    Recommeded plugins.
    Format: plugin-slug-name/plugin-main-file.php
    */
    'recommended_plugins' => [
        'imsanity/imsanity.php', // Imsanity
        'safe-svg/safe-svg.php', // Save SVG
        'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php', // Limit Login Attempts Reloaded
    ],
    /*
    NOT WORKING. WORK IN PROGRESS.

    Discouraged plugins.
    Format: check the included examples
    */
    'discouraged_plugins' => [
        [
            // SEO Framework instead of Yoast SEO
            'discouraged_plugin' => 'wordpress-seo/wp-seo-main.php',
            'discouraged_name' => 'Yoast SEO',
            'alternative_plugin' => 'autodescription/autodescription.php',
            'alternative_name' => 'SEO Framework',
            'reason' => 'SEO Framework is lightweight, have less bloat, same features and no promotionals nags like Yoast SEO.',
        ],
        [
            // WP Core instead of Clean Filenames
            'discouraged_plugin' => 'sanitize-spanish-filenames/sanitize-spanish-filenames.php',
            'discouraged_name' => 'Clean Filenames',
            'alternative_plugin' => '',
            'alternative_name' => '',
            'reason' => 'Feature included in WP core since version 5.6',
        ],
    ],
];
