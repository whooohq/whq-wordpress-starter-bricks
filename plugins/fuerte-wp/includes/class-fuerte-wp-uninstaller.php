<?php

/**
 * Fired during plugin uninstallation.
 *
 * @link       https://actitud.xyz
 * @since      1.4.0
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Fired during plugin uninstallation.
 *
 * This class defines all code necessary to run during the plugin's uninstallation.
 *
 * @since      1.4.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */
class Fuerte_Wp_Uninstaller
{
    /**
     * Short Description. (use period).
     *
     * Long Description.
     *
     * @since    1.3.0
     */
    public static function uninstall()
    {
        // Remove options
        delete_option('fuertewp_options');

        // Remove $fuertewp_htaccess lines from .htaccess file
        // Only in Apache
        if (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
            global $fuertewp_htaccess;

            $htaccessFile = ABSPATH . '.htaccess';

            // Check if .htaccess exists and is writable
            if (file_exists($htaccessFile) && is_writable($htaccessFile)) {
                // Read the current content
                $currentContent = file_get_contents($htaccessFile);

                // Check if the lines are present
                if (strpos($currentContent, $fuertewp_htaccess) !== false) {
                    // Remove the lines
                    $newContent = str_replace($fuertewp_htaccess, '', $currentContent);

                    // Write the modified content back to the file
                    file_put_contents($htaccessFile, $newContent);
                }
            }
        }
    }
}
