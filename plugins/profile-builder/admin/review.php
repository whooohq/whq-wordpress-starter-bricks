<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPPB_Review_Request {

    // Number of days to wait until review request is displayed
    public $delay = 7;
    public $wppb_review_cron_hook = 'wppb_review_check';
    public $notificationId = 'wppb_review_request';
    public $query_arg = 'wppb_dismiss_admin_notification';

    public function __construct() {
        $wppb_review_request_status = get_option( 'wppb_review_request_status', 'not_found' );

        // Initialize the option that keeps track of the number of days elapsed
        if ( $wppb_review_request_status === 'not_found' || !is_numeric( $wppb_review_request_status ) ) {
            update_option( 'wppb_review_request_status', 0 );
        }

        // Handle the cron
        if ( $wppb_review_request_status <= $this->delay ) {
            if ( !wp_next_scheduled( $this->wppb_review_cron_hook ) ) {
                wp_schedule_event( time(), 'daily', $this->wppb_review_cron_hook );
            }

            if ( !has_action( $this->wppb_review_cron_hook ) ) {
                add_action( $this->wppb_review_cron_hook, array( $this, 'check_for_registration_shortcode' ) );
            }
        } else if ( wp_next_scheduled( $this->wppb_review_cron_hook ) ){
            wp_clear_scheduled_hook( $this->wppb_review_cron_hook );
        }

        // Admin notice requesting review
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action( 'admin_init', array( $this, 'dismiss_notification' ) );
        // Footer text requesting review
        add_filter('admin_footer_text', array( $this, 'admin_footer_rate_us' ) );
    }

    // Function that looks for the PB registration form shortcode and counts the number of days elapsed
    public function check_for_registration_shortcode() {
        global $wpdb;

        $query = "SELECT ID, post_title, guid FROM ".$wpdb->posts." WHERE post_content LIKE '%[wppb-register%' AND post_status = 'publish'";

        if ( !empty( $wpdb->get_results( $query ) ) ) {
            $wppb_review_request_status = get_option( 'wppb_review_request_status', 'not_found' );

            if ( $wppb_review_request_status !== 'not_found' && is_numeric( $wppb_review_request_status ) ) {
                update_option( 'wppb_review_request_status', $wppb_review_request_status + 1 );
            } else {
                update_option( 'wppb_review_request_status', 1 );
            }
        }
    }

    // Function that displays the notice
    public function admin_notices() {
        $wppb_review_request_status = get_option( 'wppb_review_request_status' );

        if ( is_numeric( $wppb_review_request_status ) && $wppb_review_request_status > $this->delay ) {
            global $current_user;
            global $pagenow;

            $user_id = $current_user->ID;

            if ( current_user_can( 'manage_options' ) && apply_filters( 'wppb_enable_review_request_notice', true ) ) {
                // Check that the user hasn't already dismissed the message
                if ( !get_user_meta( $user_id, $this->notificationId . '_dismiss_notification' ) ) {
                    do_action( $this->notificationId . '_before_notification_displayed', $current_user, $pagenow );
                    $ul_icon_url = ( file_exists( WPPB_PLUGIN_DIR . 'assets/images/pb-logo.svg' )) ? WPPB_PLUGIN_URL . 'assets/images/pb-logo.svg' : '';
                    ?>
                    <div class="wppb-review-notice wppb-notice notice is-dismissible">
                        <div style="display: flex;">
                            <div>
                                <img style="width: 80px; height: 80px; margin-right: 20px; margin-top: 20px;" src="<?php echo esc_url($ul_icon_url); ?>" >
                            </div>
                            <div>
                                <p style="margin-top: 16px; font-size: 15px;">
		                            <?php esc_html_e("Hello! Seems like you've been using Profile Builder to create front-end user forms. That's awesome!", 'profile-builder'); ?>
                                    <br/>
		                            <?php esc_html_e("If you can spare a few moments to rate it on WordPress.org, it would help us a lot (and boost my motivation).", 'profile-builder'); ?>
                                </p>
                                <p>
		                            <?php esc_html_e("~ Paul, developer of Profile Builder", 'profile-builder'); ?>
                                </p>
                                <p></p>
                            </div>
                        </div>

                        <div style="display: flex; margin-bottom: 24px; padding-left: 95px;">
                            <div>
                                <a href="https://wordpress.org/support/plugin/profile-builder/reviews/?filter=5#new-post"
                                   target="_blank" rel="noopener" class="button-primary" style="margin-right: 20px">
		                            <?php esc_html_e('Ok, I will gladly help!', 'profile-builder'); ?>
                                </a>
                            </div>

                            <div>
                                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( $this->query_arg => $this->notificationId ) ), 'wppb_review_notice_dismiss' ) ) ?>"
                                   class="button-secondary">
		                            <?php esc_html_e('No, thanks.', 'profile-builder'); ?>
                                </a>
                            </div>
                        </div>

                        <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( $this->query_arg => $this->notificationId ) ), 'wppb_review_notice_dismiss' ) ) ?>"
                           type="button" class="notice-dismiss" style="text-decoration: none;">
                            <span class="screen-reader-text">
                                <?php esc_html_e('Dismiss this notice.', 'profile-builder'); ?>
                            </span>
                        </a>
                    </div>
                    <?php
                    do_action( $this->notificationId . '_after_notification_displayed', $current_user, $pagenow );
                }
            }
        }
    }

    // Function that saves the notification dismissal to the user meta
    public function dismiss_notification() {

        if( empty( $_GET['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'wppb_review_notice_dismiss' ) )
            return;

        global $current_user;

        $user_id = $current_user->ID;

        // If user clicks to ignore the notice, add that to their user meta
        if ( isset( $_GET[$this->query_arg] ) && $this->notificationId === $_GET[$this->query_arg] ) {
            do_action( $this->notificationId.'_before_notification_dismissed', $current_user );
            add_user_meta($user_id, $this->notificationId . '_dismiss_notification', 'true', true);
            do_action( $this->notificationId.'_after_notification_dismissed', $current_user );
        }
    }

    // Function that adds admin footer text for encouraging users to leave a review of the plugin on wordpress.org
    function admin_footer_rate_us( $footer_text ) {
        global $current_screen;

        if ( $current_screen->parent_base == 'profile-builder' ){
            $rate_text = sprintf( __( 'If you enjoy using <strong> %1$s </strong> please <a href="%2$s" target="_blank">rate us on WordPress.org</a>. More happy users means more features, less bugs and better support for everyone. ', 'profile-builder' ),
                PROFILE_BUILDER,
                'https://wordpress.org/support/view/plugin-reviews/profile-builder?filter=5#postform'
            );
            return '<span id="footer-thankyou">' .$rate_text . '</span>';
        } else {
            return $footer_text;
        }
    }
}