<?php
/**
 * Common admin assets which are loaded on each admin screen.
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core\Assets;

/**
 * Assets class.
 */
final class Admin {

    /**
     * Inits.
     */
    public static function init() {
        $class = __CLASS__;
        new $class();
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

        // Main styles.

        wp_enqueue_style(
            'tmm-wp-plugins-core-admin-style',
            TMM_WP_PLUGINS_CORE_URL . 'assets-build/admin/common.css',
            [ 'tmm-wp-plugins-core-lib-swiper', 'tmm-wp-plugins-core-lib-sweetalert2' ],
            TMM_WP_PLUGINS_CORE_VERSION
        );
    }

    /**
     * Loads scripts.
     */
    private function scripts() {

        // Main script.

        wp_enqueue_script(
            'tmm-wp-plugins-core-admin-script',
            TMM_WP_PLUGINS_CORE_URL . 'assets-build/admin/common.js',
            [ 'tmm-wp-plugins-core-lib-swiper', 'jquery', 'tmm-wp-plugins-core-lib-sweetalert2' ],
            TMM_WP_PLUGINS_CORE_VERSION,
            true
        );

        wp_localize_script(
            'tmm-wp-plugins-core-admin-script',
            'wpPluginsCoreAdminCommon',
            [
                'nonceToken' => wp_create_nonce( 'tmm-wp-plugins-core' ),
                'txt'        => self::get_texts(),
            ]
        );
    }

    /**
     * Returns an array of common texts for the admin scripts.
     */
    private static function get_texts() {
        return [
            'error'              => __( 'Error', 'tmm-wp-plugins-core' ),
            'somethingWentWrong' => __( 'Something went wrong.', 'tmm-wp-plugins-core' ),
        ];
    }
}
