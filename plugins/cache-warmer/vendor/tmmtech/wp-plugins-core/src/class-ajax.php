<?php
/**
 * AJAX handlers.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

use Exception;

/**
 * Manages AJAX.
 */
final class AJAX {

    /**
     * Main plugin.
     *
     * @var WP_Plugins_Core
     */
    public $core;

    /**
     * Adds the menu and inits assets loading for it.
     *
     * @param WP_Plugins_Core $core Plugin core.
     */
    public function __construct( $core ) {
        $this->core = $core;

        $this->add_ajax_events();
    }

    /**
     * Loads AJAX handlers.
     */
    private function add_ajax_events() {
        $admin_ajax_events = [
            'save_viewed_notifications',
        ];

        foreach ( $admin_ajax_events as $ajax_event ) {
            add_action( "wp_ajax_wp_plugins_core_$ajax_event", [ $this, $ajax_event ] );
        }
    }

    /**
     * Saves viewed notifications.
     *
     * @throws Exception Exception.
     */
    public function save_viewed_notifications() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'tmm-wp-plugins-core', 'nonceToken' );

        if ( array_key_exists( 'ids', $_REQUEST ) ) {
            $notification_ids = Sanitize::sanitize_array( (array) json_decode( wp_unslash( $_REQUEST['ids'] ), true ) ); // @codingStandardsIgnoreLine

            $viewed_notifications = $this->core->options->get( 'viewed-notifications' );
            $viewed_notifications = array_unique( array_merge( $viewed_notifications, $notification_ids ) );

            $this->core->options->set( 'viewed-notifications', $viewed_notifications );

            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
}
