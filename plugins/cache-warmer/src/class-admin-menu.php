<?php
/**
 * Admin Menus
 *
 * Adds admin menus.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

/**
 * Class Admin_Menu.
 */
final class Admin_Menu {

    /**
     * Adds the menu and inits assets loading for it.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'init_menu' ] );
    }

    /**
     * Adds the menu and inits assets loading for it.
     */
    public function init_menu() {
        $integrations_admin_notices = function () {
            add_action( 'admin_notices', [ 'Cache_Warmer\Integrations\Cache_Plugins_Integration', 'admin_notices' ] );
        };

        $menu_slug = add_menu_page(
            __( 'Cache Warmer', 'cache-warmer' ),
            __( 'Cache Warmer', 'cache-warmer' ),
            'manage_options',
            'cache-warmer',
            function () {
                require_once CACHE_WARMER_DIR . 'src/templates/admin/screens/main.php';
            },
            'dashicons-performance'
        );

        add_action(
            'load-' . $menu_slug,
            [ 'Cache_Warmer\Assets\Menu\Screens\Main', 'init' ]
        );

        add_action( 'load-' . $menu_slug, $integrations_admin_notices );

        $settings_menu_slug = add_submenu_page(
            'cache-warmer',
            __( 'Settings', 'cache-warmer' ),
            __( 'Settings', 'cache-warmer' ),
            'manage_options',
            'cache-warmer-settings',
            function () {
                require_once CACHE_WARMER_DIR . 'src/templates/admin/screens/settings.php';
            }
        );

        add_action(
            'load-' . $settings_menu_slug,
            [ 'Cache_Warmer\Settings_Export', 'add_listeners' ]
        );

        add_action(
            'load-' . $settings_menu_slug,
            [ 'Cache_Warmer\Assets\Menu\Screens\Settings', 'init' ]
        );

        add_action( 'load-' . $settings_menu_slug, $integrations_admin_notices );

        $logs_menu_slug = add_submenu_page(
            'cache-warmer',
            __( 'Logs', 'cache-warmer' ),
            __( 'Logs', 'cache-warmer' ),
            'manage_options',
            'cache-warmer-logs',
            function () {
                require_once CACHE_WARMER_DIR . 'src/templates/admin/screens/logs.php';
            }
        );

        add_action(
            'load-' . $logs_menu_slug,
            [ 'Cache_Warmer\Assets\Menu\Screens\Logs', 'init' ]
        );

        add_action( 'load-' . $logs_menu_slug, $integrations_admin_notices );
    }

    /**
     * Returns an array of texts for the admin scripts.
     */
    public static function get_texts() {
        return [
            'error'                  => __( 'Error', 'cache-warmer' ),
            'somethingWentWrong'     => __( 'Something went wrong.', 'cache-warmer' ),
            'startANewWarmUp'        => __( 'Start a new Warm-Up', 'cache-warmer' ),
            'areYouSure'             => __( 'Are you sure?', 'cache-warmer' ),
            'yes'                    => __( 'Yes', 'cache-warmer' ),
            'urlIsInvalid'           => __( 'Entry points: URL {} is invalid.', 'cache-warmer' ),
            'resetAllSettingsNotice' => __( 'Are you sure you want to reset all plugin settings?', 'cache-warmer' ),
            'yesResetAllSettings'    => __( 'Yes, reset all plugin settings!', 'cache-warmer' ),
            /* translators: %s is a plugin name. */
            'settingsAreImporting'   => sprintf( __( '%s settings are being imported.', 'cache-warmer' ), Cache_Warmer::$name ),
            'pleaseWait'             => __( 'Please wait.', 'cache-warmer' ),
            'cookiesInsertionNotice' => __( 'If some other admin user visits this page, he can use your inserted cookies to log-in into your account on this site.<br><br>The page will be reloaded if you click "yes".', 'cache-warmer' ),
            'yesIUnderstand'         => __( 'Yes, I understand', 'cache-warmer' ),
            'no'                     => __( 'No', 'cache-warmer' ),
        ];
    }
}
