<?php
/**
 * Assets for logs screen
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer\Assets\Menu\Screens;

use Cache_Warmer\Admin_Menu;

/**
 * Assets class.
 */
final class Logs {

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
            'cache-warmer-admin-logs-screen-style',
            CACHE_WARMER_URL . 'assets-build/admin/screens/logs.css',
            [ 'cache-warmer-admin-style' ],
            CACHE_WARMER_VERSION
        );
    }

    /**
     * Loads scripts.
     */
    private function scripts() {

        // Popper + Tippy.js.

        wp_register_script(
            'cache-warmer-lib-popper',
            CACHE_WARMER_URL . 'libs/@popperjs/core/dist/umd/popper.min.js',
            [],
            '2.11.5',
            true
        );

        wp_register_script(
            'cache-warmer-lib-tippyjs',
            CACHE_WARMER_URL . 'libs/tippy.js/dist/tippy-bundle.umd.min.js',
            [ 'cache-warmer-lib-popper' ],
            '6.3.7',
            true
        );

        wp_register_script(
            'cache-warmer-admin-script',
            CACHE_WARMER_URL . 'assets-build/admin/index.js',
            [],
            CACHE_WARMER_VERSION,
            true
        );

        wp_enqueue_script(
            'cache-warmer-admin-logs-screen-script',
            CACHE_WARMER_URL . 'assets-build/admin/screens/logs.js',
            [
                'cache-warmer-admin-script',
                'cache-warmer-lib-tippyjs',
            ],
            CACHE_WARMER_VERSION,
            true
        );

        wp_localize_script(
            'cache-warmer-admin-script',
            'wpCacheWarmer',
            [
                'nonceToken' => wp_create_nonce( 'cache-warmer-menu' ),
                'txt'        => Admin_Menu::get_texts(),
            ]
        );
    }
}
