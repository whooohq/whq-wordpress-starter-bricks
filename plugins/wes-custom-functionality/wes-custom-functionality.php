<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://actitud.xyz
 * @since             1.0.0
 * @package           Wes_Custom_Functionality
 *
 * @wordpress-plugin
 * Plugin Name:       WES Custom Functionality
 * Plugin URI:        https://actitud.xyz
 * Description:       Custom Functionality Plugin for WordPress
 * Version:           1.0.0
 * Author:            Esteban Cuevas
 * Author URI:        https://actitud.xyz
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wes-custom-functionality
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WES_CUSTOM_FUNCTIONALITY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wes-custom-functionality-activator.php
 */
function activate_wes_custom_functionality() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wes-custom-functionality-activator.php';
	Wes_Custom_Functionality_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wes-custom-functionality-deactivator.php
 */
function deactivate_wes_custom_functionality() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wes-custom-functionality-deactivator.php';
	Wes_Custom_Functionality_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wes_custom_functionality' );
register_deactivation_hook( __FILE__, 'deactivate_wes_custom_functionality' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wes-custom-functionality.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wes_custom_functionality() {

	$plugin = new Wes_Custom_Functionality();
	$plugin->run();

}
run_wes_custom_functionality();
