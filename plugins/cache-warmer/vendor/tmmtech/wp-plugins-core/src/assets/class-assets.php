<?php
/**
 * Assets
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core\Assets;

/**
 * Assets class.
 */
final class Assets {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'register_libs' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'register_libs' ] );

        add_action( 'admin_enqueue_scripts', [ 'WP_Plugins_Core\Assets\Admin', 'Init' ] );
    }


    /**
     * Registers the libs.
     */
    public function register_libs() {

        // 1. Swiper.

        // 1.1. Style.

        wp_register_style(
            'tmm-wp-plugins-core-lib-swiper',
            TMM_WP_PLUGINS_CORE_URL . 'libs/swiper/swiper-bundle.min.css',
            [],
            '8.2.4'
        );

        // 1.2. Script.

        wp_register_script(
            'tmm-wp-plugins-core-lib-swiper',
            TMM_WP_PLUGINS_CORE_URL . 'libs/swiper/swiper-bundle.min.js',
            [],
            '8.2.4',
            true
        );

        // 2. SweetAlert2.

        // 2.1. Style.

        wp_register_style(
            'tmm-wp-plugins-core-lib-sweetalert2',
            TMM_WP_PLUGINS_CORE_URL . 'libs/sweetalert2/sweetalert2.min.css',
            [],
            '11.1.4'
        );

        // 2.2. Script.

        wp_register_script(
            'tmm-wp-plugins-core-lib-sweetalert2',
            TMM_WP_PLUGINS_CORE_URL . 'libs/sweetalert2/sweetalert2.all.min.js',
            [],
            '11.1.4',
            true
        );
    }
}
