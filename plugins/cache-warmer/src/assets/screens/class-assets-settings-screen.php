<?php
/**
 * Assets for settings screen
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer\Assets\Menu\Screens;

use Exception;
use Cache_Warmer\Admin_Menu;
use Cache_Warmer\Cache_Warmer;

/**
 * Assets class.
 */
final class Settings {

    /**
     * Inits.
     */
    public static function init() {
        $class = __CLASS__;
        add_action(
            'admin_enqueue_scripts',
            function () use ( $class ) {
                new $class();
            }
        );
    }

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        $this->styles();
        $this->scripts();
    }

    /**
     * Loads styles.
     */
    private function styles() {
        wp_register_style(
            'cache-warmer-admin-style',
            CACHE_WARMER_URL . 'assets-build/admin/index.css',
            [],
            CACHE_WARMER_VERSION
        );

        wp_enqueue_style(
            'cache-warmer-admin-settings-screen-style',
            CACHE_WARMER_URL . 'assets-build/admin/screens/settings.css',
            [ 'cache-warmer-admin-style' ],
            CACHE_WARMER_VERSION
        );
    }

    /**
     * Loads scripts.
     *
     * @throws Exception Exception.
     */
    private function scripts() {
        wp_register_script(
            'cache-warmer-admin-script',
            CACHE_WARMER_URL . 'assets-build/admin/index.js',
            [],
            CACHE_WARMER_VERSION,
            true
        );

        wp_enqueue_script(
            'cache-warmer-admin-settings-screen-script',
            CACHE_WARMER_URL . 'assets-build/admin/screens/settings.js',
            [ 'cache-warmer-admin-script' ],
            CACHE_WARMER_VERSION,
            true
        );

        wp_localize_script(
            'cache-warmer-admin-script',
            'wpCacheWarmer',
            [
                'nonceToken'     => wp_create_nonce( 'cache-warmer-menu' ),
                'txt'            => Admin_Menu::get_texts(),
                'urlParams'      => Cache_Warmer::$options->get( 'cache-warmer-setting-url-params' ),
                'requestHeaders' => Cache_Warmer::$options->get( 'cache-warmer-setting-request-headers' ),
                'cookies'        => Cache_Warmer::$options->get( 'cache-warmer-setting-cookies' ),
                'entryPoints'    => Cache_Warmer::$options->get( 'cache-warmer-setting-entry-points' ),
                'userAgents'     => Cache_Warmer::$options->get( 'cache-warmer-setting-user-agents' ),
            ]
        );
    }
}
