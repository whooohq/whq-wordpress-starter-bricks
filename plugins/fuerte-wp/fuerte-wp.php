<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Fuerte-WP
 * Plugin URI:        https://github.com/EstebanForge/Fuerte-WP
 * Description:       Stronger WP. Limit access to critical WordPress areas, even other for admins.
 * Version:           1.9.1
 * Author:            Esteban Cuevas
 * Author URI:        https://actitud.xyz
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fuerte-wp
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Tested up to:      6.9
 * Requires PHP:      8.1
 *
 * @link              https://actitud.xyz
 * @since             1.3.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

/*
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('FUERTEWP_PLUGIN_BASE', plugin_basename(__FILE__));
define('FUERTEWP_VERSION', '1.9.0');
define('FUERTEWP_PATH', realpath(plugin_dir_path(__FILE__)) . '/');
define('FUERTEWP_URL', trailingslashit(plugin_dir_url(__FILE__)));
define('FUERTEWP_LATE_PRIORITY', 9999);

/*
 * Load Fuerte-WP config file if exist
 */
if (file_exists(ABSPATH . 'wp-config-fuerte.php')) {
    require_once ABSPATH . 'wp-config-fuerte.php';
}

/*
 * Exit if FUERTEWP_DISABLE is defined (in wp-config.php or wp-config-fuerte.php)
 */
if (defined('FUERTEWP_DISABLE') && true === FUERTEWP_DISABLE) {
    return false;
}

/**
 * Includes & Autoload.
 */
function fuertewp_includes_autoload()
{
    if (file_exists(FUERTEWP_PATH . 'includes/helpers.php')) {
        require_once FUERTEWP_PATH . 'includes/helpers.php';
    }

    if (file_exists(FUERTEWP_PATH . 'vendor/autoload.php')) {
        require_once FUERTEWP_PATH . 'vendor/autoload.php';
    }
}
add_action('after_setup_theme', 'fuertewp_includes_autoload', 100);

/**
 * Load Carbon Fields early on plugins_loaded hook.
 * This must happen before any Carbon Fields containers are registered.
 *
 * @since 1.7.0
 */
/**
 * Load HyperFields early on plugins_loaded hook.
 *
 * @since 1.8.0
 */
function fuertewp_load_hyperfields()
{
    if (file_exists(FUERTEWP_PATH . 'vendor/autoload.php')) {
        require_once FUERTEWP_PATH . 'vendor/autoload.php';
    }

    // Bootstrap HyperFields
    if (file_exists(FUERTEWP_PATH . 'vendor/estebanforge/hyperfields/bootstrap.php')) {
        require_once FUERTEWP_PATH . 'vendor/estebanforge/hyperfields/bootstrap.php';

        // Initialize HyperFields with version for cache busting
        if (function_exists('hyperfields_run_initialization_logic')) {
            hyperfields_run_initialization_logic(
                FUERTEWP_PATH . 'vendor/estebanforge/hyperfields/bootstrap.php',
                defined('FUERTEWP_VERSION') ? FUERTEWP_VERSION : '1.9.0'
            );
        }
    }

    // Run migration if needed
    if (is_admin()) {
        require_once FUERTEWP_PATH . 'includes/class-fuerte-wp-migrator.php';
        Fuerte_Wp_Migrator::migrate();
    }
}
add_action('plugins_loaded', 'fuertewp_load_hyperfields', 0);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fuerte-wp-activator.php.
 */
function activate_fuerte_wp()
{
    require_once plugin_dir_path(__FILE__)
        . 'includes/class-fuerte-wp-activator.php';
    Fuerte_Wp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fuerte-wp-deactivator.php.
 */
function deactivate_fuerte_wp()
{
    require_once plugin_dir_path(__FILE__)
        . 'includes/class-fuerte-wp-deactivator.php';
    Fuerte_Wp_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_fuerte_wp');
register_deactivation_hook(__FILE__, 'deactivate_fuerte_wp');

/**
 * Check database version on plugin load and create/update tables if needed.
 * This ensures tables are created even when plugin is updated.
 *
 * @since 1.7.0
 */
function fuertewp_check_login_db_version()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-fuerte-wp-activator.php';

    $installed_version = get_option('_fuertewp_login_db_version', '0.0.0');

    if (version_compare($installed_version, Fuerte_Wp_Activator::get_db_version(), '<')) {
        Fuerte_Wp_Activator::create_login_security_tables();
        Fuerte_Wp_Activator::schedule_cron_jobs();
        Fuerte_Wp_Activator::setup_initial_super_user();
    }
}
add_action('plugins_loaded', 'fuertewp_check_login_db_version');

/**
 * Ensure at least one super user is configured during admin_init.
 * This provides a fallback if plugins_loaded didn't work (user not logged in yet).
 *
 * @since 1.7.0
 */
function fuertewp_ensure_super_user()
{
    if (!is_admin()) {
        return;
    }

    // Use Fuerte_Wp_Config instead of direct Carbon Fields calls
    require_once FUERTEWP_PATH . 'includes/class-fuerte-wp-config.php';

    // Check if super_users option exists and is not empty
    $super_users = Fuerte_Wp_Config::get_field('super_users', [], true);

    if (empty($super_users)) {
        $current_user = wp_get_current_user();

        if ($current_user && $current_user->ID > 0 && current_user_can('manage_options')) {
            // Add current user as super user
            Fuerte_Wp_Config::set_field('super_users', [$current_user->user_email], true);
            Fuerte_Wp_Config::invalidate_cache();
        }
    }
}
add_action('admin_init', 'fuertewp_ensure_super_user');

/**
 * Code that runs on plugins uninstallation.
 */
function uninstall_fuerte_wp()
{
    require_once plugin_dir_path(__FILE__)
        . 'includes/class-fuerte-wp-uninstaller.php';
    Fuerte_Wp_Uninstaller::uninstall();
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
// Load logger first to ensure it's always available for debugging
require plugin_dir_path(__FILE__) . 'includes/class-fuerte-wp-logger.php';

// Initialize logger immediately - only enable when WP_DEBUG is true
Fuerte_Wp_Logger::enable(defined('WP_DEBUG') && WP_DEBUG);
Fuerte_Wp_Logger::init_from_constant();

require plugin_dir_path(__FILE__) . 'includes/class-fuerte-wp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_fuerte_wp()
{
    $plugin = new Fuerte_Wp();
    $plugin->run();
}
run_fuerte_wp();

/*
 * Hook into plugin updates to clear configuration cache.
 * This ensures that the super users and other configuration are properly
 * refreshed when the plugin is updated.
 *
 * @since 1.7.1
 */
add_action('upgrader_process_complete', 'fuertewp_handle_plugin_update', 10, 2);

function fuertewp_handle_plugin_update($upgrader_object, $options)
{
    // Check if this is a plugin update and if it's our plugin
    if ($options['action'] === 'update' && $options['type'] === 'plugin') {
        if (isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin === plugin_basename(FUERTEWP_PLUGIN_BASE)) {
                    // This is our plugin being updated - clear the cache
                    require_once FUERTEWP_PATH . 'includes/class-fuerte-wp-activator.php';
                    Fuerte_Wp_Activator::handle_plugin_update();
                    break;
                }
            }
        }
    }
}

/**
 * htaccess security rules.
 */
$fuertewp_htaccess = "
# BEGIN Fuerte-WP
# Avoid install.php and install-helper.php from being accessed directly
<FilesMatch \"^(wp-admin/)?(install|install-helper)\.php$\">
	Order allow,deny
	Deny from all
</FilesMatch>

# Disable running PHP scripts in the uploads directory
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteRule ^(wp-content/uploads/.*)\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|html|htm|shtml|sh|cgi|suspected)$ - [F,L]
</IfModule>
# END Fuerte-WP
";
