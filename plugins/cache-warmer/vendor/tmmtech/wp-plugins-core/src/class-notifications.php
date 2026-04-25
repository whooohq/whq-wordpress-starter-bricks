<?php
/**
 * A class for notifications.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

use Exception;

/**
 * Notifications class.
 */
final class Notifications {

    /**
     * Notifications URL.
     *
     * @var string
     */
    public $notifications_url;

    /**
     * Interval hook name to fetch notification for the plugin.
     *
     * @var string
     */
    public $interval_hook_name;

    /**
     * Interval to fetch notification for the plugin.
     *
     * @var int
     */
    const INTERVAL = HOUR_IN_SECONDS;

    /**
     * Main plugin.
     *
     * @var WP_Plugins_Core
     */
    public $core;

    /**
     * Constructor.
     *
     * @param WP_Plugins_Core $core Plugins core.
     */
    public function __construct( WP_Plugins_Core $core ) {
        $this->core = $core;

        $this->notifications_url = 'https://wpplugins-midlayer.tmm-technology.com/api/notifications/' . $this->core->plugin_slug . '/' . $this->core->plugin_version;

        $this->interval_hook_name = $this->core->plugin_slug . '_fetch_notifications';

        // Interval action.
        add_action( $this->interval_hook_name, [ $this, 'get_notifications' ] );

        // Schedule the action.
        add_action( 'init', [ $this, 'schedule_interval' ] );

        // Unschedule interval action on plugin deactivation.
        register_deactivation_hook( $this->core->plugin_file, [ $this, 'unschedule' ] );

        // Show notifications.
        add_action( 'admin_notices', [ $this, 'show_notifications' ] );
    }

    /**
     * Schedules the interval.
     */
    public function schedule_interval() {
        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time(),
                self::INTERVAL,
                $this->interval_hook_name,
                [],
                '',
                true
            );
        };

        if ( did_action( 'action_scheduler_init' ) ) {
            $schedule_interval_action();
        } else {
            add_action( 'action_scheduler_init', $schedule_interval_action );
        }
    }

    /**
     * Plugin deactivation handler.
     */
    public function unschedule() {
        if ( function_exists( 'as_unschedule_all_actions' ) ) {
            as_unschedule_all_actions( $this->interval_hook_name );
        }
    }

    /**
     * Returns notifications.
     *
     * @throws Exception Exception.
     */
    public function get_notifications() {
        $response = wp_remote_get( $this->notifications_url );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $error_object  = [
                'Description'   => "Can't connect to the Notifications API server.",
                'Endpoint'      => $this->notifications_url,
                'Error message' => $error_message,
            ];
            throw new Exception( wp_json_encode( [ 'error' => $error_object ] ) );
        } elseif ( 200 !== $response['response']['code'] ) {
            $response_code = $response['response']['code'];
            $response_body = $response['body'];
            $error_object  = [
                'Description'   => 'Notifications API Response Code is not 200.',
                'Endpoint'      => $this->notifications_url,
                'Response Code' => $response_code,
                'Response Body' => $response_body,
            ];
            throw new Exception( wp_json_encode( [ 'error' => $error_object ] ) );
        } elseif ( array_key_exists( 'body', $response ) ) {
            $notifications = Sanitize::sanitize_array( json_decode( $response['body'], true ) );
            $this->core->options->set( 'notifications', $notifications );
        } else {
            throw new Exception( 'No response body.' );
        }
    }

    /**
     * Shows notifications.
     *
     * @throws Exception Exception.
     */
    public function show_notifications() {
        $notifications        = $this->core->options->get( 'notifications' );
        $viewed_notifications = $this->core->options->get( 'viewed-notifications' );

        $notifications_to_show = [];

        foreach ( $notifications as $notification_id => $notification ) {
            if (
                ! in_array( $notification_id, $viewed_notifications, true ) &&
                count( array_intersect( wp_get_current_user()->roles, $notification['roles'] ) )
            ) {
                $notifications_to_show[ $notification_id ] = $notification;
            }
        }

        /**
         * Prints the notification.
         */
        $print_notification = function ( $notification_id, $notification ) {
            ?>
            <div class="<?php echo 'drip' === $notification['type'] ? 'drip ' : '' ?>wp-plugins-core-notification
                notice notice-<?php echo esc_attr('notice' === $notification['level'] ? 'info' : $notification['level']); ?>
                <?php echo 'dismissable' === $notification['type'] ? 'is-dismissible' : ''; ?>"
                data-notification-id="<?php echo esc_attr( $notification_id ); ?>">
                <p>
                    <b><?php esc_html_e( $this->core->plugin_name ); ?>: </b><?php echo $notification['message']; ?>
                </p>
            </div>
            <?php
        };

        // Prints non-drip notifications.
        foreach ( $notifications_to_show as $notification_id => $notification ) {
            $print_notification( $notification_id, $notification );
        }
    }
}
