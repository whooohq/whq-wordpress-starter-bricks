<?php
/**
 * Assets for dashboard
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core\Assets;

/**
 * Assets class.
 */
final class Dashboard {

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
        wp_enqueue_style(
            'tmm-wp-plugins-core-admin-dashboard-style',
            TMM_WP_PLUGINS_CORE_URL . 'assets-build/admin/dashboard.css',
            [],
            TMM_WP_PLUGINS_CORE_VERSION
        );
    }

    /**
     * Loads scripts.
     */
    private function scripts() {
        wp_enqueue_script(
            'tmm-wp-plugins-core-admin-dashboard-script',
            TMM_WP_PLUGINS_CORE_URL . 'assets-build/admin/dashboard.js',
            [],
            TMM_WP_PLUGINS_CORE_VERSION,
            true
        );
    }
}
