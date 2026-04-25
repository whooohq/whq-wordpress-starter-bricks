<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Function that creates the "Register your version" submenu page for Multisite Environment
 *
 * @since v.2.0
 *
 * @return void
 */
if( is_multisite() ) {
    function wppb_multisite_register_your_version_page() {
        if ( PROFILE_BUILDER != 'Profile Builder Free' )
            add_menu_page(__('Profile Builder Register', 'profile-builder'), __('Profile Builder Register', 'profile-builder'), 'manage_options', 'profile-builder-register', 'wppb_register_your_version_content', WPPB_PLUGIN_URL . 'assets/images/pb-menu-icon.svg');
    }
    add_action('network_admin_menu', 'wppb_multisite_register_your_version_page', 29);
}


/**
 * Function that adds content to the "Register your Version" submenu page
 *
 * @since v.2.0
 *
 * @return string
 */
function wppb_register_your_version_content() {
    ?>
    <div class="wrap wppb-wrap cozmoslabs-wrap">
        <div id="wppb-register-version-page">

            <h1></h1>
            <!-- WordPress Notices are added after the h1 tag -->

            <div class="cozmoslabs-page-header">
                <div class="cozmoslabs-section-title">
                    <h2 class="cozmoslabs-page-title"><?php esc_html_e( "Register your version of Profile Builder", 'profile-builder' ); ?></h2>
                    <a href="https://www.cozmoslabs.com/docs/profile-builder/basic-information-installation?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Register_your_version" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>
                </div>
            </div>

            <div class="cozmoslabs-form-subsection-wrapper" id="wppb-register-version">
                <?php wppb_add_register_version_form(); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Retrieve saved license key
 */
function wppb_get_serial_number(){

    if( is_multisite() ){
        $license = get_site_option( 'wppb_license_key', false );

        if( empty( $license ) )
            $license = get_option( 'wppb_license_key', false );
    }
    else
        $license = get_option( 'wppb_license_key', false );

    // try to grab the license from the old options if it's not available
    if( empty( $license ) ){
        $versions = array( 'Profile Builder Pro', 'Profile Builder Agency', 'Profile Builder Unlimited', 'Profile Builder Dev' );

	    if( in_array( PROFILE_BUILDER, $versions ) )
            $version = 'pro';
        elseif ( PROFILE_BUILDER == 'Profile Builder Hobbyist' || PROFILE_BUILDER == 'Profile Builder Basic' )
            $version = 'hobbyist';
        else
            $version = '';

        $old_license = get_option( 'wppb_profile_builder_'.$version.'_serial' );

        if( !empty( $old_license ) ){
            update_option( 'wppb_license_key', $old_license );
            $license = $old_license;
        }

    }

    return $license;

}

/**
 * Retrieve license key status
 */
function wppb_get_serial_number_status(){

    if( is_multisite() )
        return get_site_option( 'wppb_license_status' );
    else
        return get_option( 'wppb_license_status' );

}

/**
 * Class that adds a notice when either the serial number wasn't found, or it has expired
 *
 * @since v.2.0
 *
 * @return void
 */
class WPPB_add_notices{
	public $pluginPrefix = '';
	public $pluginName = '';
	public $notificaitonMessage = '';
	public $pluginSerialStatus = '';

	function __construct( $pluginPrefix, $pluginName, $notificaitonMessage, $pluginSerialStatus ){
		$this->pluginPrefix = $pluginPrefix;
		$this->pluginName = $pluginName;
		$this->notificaitonMessage = $notificaitonMessage;
		$this->pluginSerialStatus = $pluginSerialStatus;

		add_action( 'admin_notices', array( $this, 'add_admin_notice' ) );
		add_action( 'admin_init', array( $this, 'dismiss_notification' ) );
	}


	// Display a notice that can be dismissed in case the serial number is inactive
	function add_admin_notice() {
		global $current_user ;
		global $pagenow;

		$user_id = $current_user->ID;

		do_action( $this->pluginPrefix.'_before_notification_displayed', $current_user, $pagenow );

		if ( current_user_can( 'manage_options' ) ){

				$plugin_serial_status = get_option( $this->pluginSerialStatus );
				if ( $plugin_serial_status != 'found' ){

				    //we want to show the expiration notice on our plugin pages even if the user dismissed it on the rest of the site
                    $force_show = false;
                    if ( $plugin_serial_status == 'expired' ) {
                        $notification_instance = WPPB_Plugin_Notifications::get_instance();
                        if ($notification_instance->is_plugin_page()){
                            $force_show = true;
                        }
                    }

					// Check that the user hasn't already clicked to ignore the message
					if ( ! get_user_meta($user_id, $this->pluginPrefix.'_dismiss_notification' ) || $force_show ) {
						echo wp_kses_post( $finalMessage = apply_filters($this->pluginPrefix.'_notification_message','<div class="error wppb-serial-notification" >'.$this->notificaitonMessage.'</div>', $this->notificaitonMessage) );
					}
				}

				do_action( $this->pluginPrefix.'_notification_displayed', $current_user, $pagenow, $plugin_serial_status );

		}

		do_action( $this->pluginPrefix.'_after_notification_displayed', $current_user, $pagenow );

	}

	function dismiss_notification() {

        if( empty( $_GET['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'wppb_license_notice_dismiss' ) )
            return;

		global $current_user;

		$user_id = $current_user->ID;

		do_action( $this->pluginPrefix.'_before_notification_dismissed', $current_user );

		// If user clicks to ignore the notice, add that to their user meta
		if ( isset( $_GET[$this->pluginPrefix.'_dismiss_notification']) && '0' == $_GET[$this->pluginPrefix.'_dismiss_notification'] )
			add_user_meta( $user_id, $this->pluginPrefix.'_dismiss_notification', 'true', true );

		do_action( $this->pluginPrefix.'_after_notification_dismissed', $current_user );
	}
}

add_action( 'admin_init', 'wppb_admin_general_notices', 9 );
function wppb_admin_general_notices(){

    if( defined( 'WPPB_PAID_PLUGIN_DIR' ) ){

        // Switch to the main site
        if( is_multisite() && function_exists( 'switch_to_blog' )
            && function_exists( 'get_main_site_id' ))
            switch_to_blog( get_main_site_id() );
    
        $wppb_serial_number = wppb_get_serial_number();
        $wppb_serial_status = wppb_get_serial_number_status();
        $license_details    = get_option( 'wppb_license_details', false );
    
        if( is_multisite() && function_exists( 'restore_current_blog' ) )
            restore_current_blog();
    
        if( empty( $wppb_serial_number ) || $wppb_serial_status == 'missing' ) {
    
            if( !is_multisite() )
                $register_url = 'admin.php?page=profile-builder-general-settings';
            else
                $register_url = network_admin_url( 'admin.php?page=profile-builder-general-settings' );
    
            new WPPB_add_notices( 'wppb', 'profile_builder_pro', sprintf( '<p>' . __( 'Your <strong>Profile Builder</strong> license is missing or invalid. <br/>Please %1$sRegister Your Copy%2$s to get access to premium features & addons, automatic updates and support. Need a license key? %3$sPurchase one now%4$s', 'profile-builder') . '</p>', "<a href='". esc_url( $register_url ) ."'>", "</a>", "<a href='https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=wp-dashboard&utm_medium=client-site&utm_campaign=pb-pro-no-active-license#pricing' target='_blank' class='button-primary'>", "</a>" ), 'wppb_license_status' );
    
        }
        elseif ( $wppb_serial_status == 'expired' ){
            /* on our plugin pages do not add the dismiss button for the expired notification*/
            $notification_instance = WPPB_Plugin_Notifications::get_instance();
            if( $notification_instance->is_plugin_page() )
                $message = '<p>' . __( 'Your <strong>Profile Builder license has expired</strong>. <br/>Please %1$sRenew Your Licence%2$s to continue receiving access to new features, premium addons, product downloads & automatic updates — including important security patches and WordPress compatibility. %3$sRenew now %4$s', 'profile-builder') . '</p>';
            else
                $message = '<p>' . __( 'Your <strong>Profile Builder license has expired</strong>. <br/>Please %1$sRenew Your Licence%2$s to continue receiving access to new features, premium addons, product downloads & automatic updates — including important security patches and WordPress compatibility. %3$sRenew now %4$s %5$sDismiss%6$s', 'profile-builder') . '</p>';
    
            new WPPB_add_notices( 'wppb_expired', 'profile_builder_pro', sprintf( $message, "<a href='https://www.cozmoslabs.com/account/?utm_source=wp-dashboard&utm_medium=client-site&utm_campaign=pb-expired-license' target='_blank'>", "</a>", "<a href='https://www.cozmoslabs.com/account/?utm_source=wp-dashboard&utm_medium=client-site&utm_campaign=pb-expired-license' target='_blank' class='button-primary'>", "</a>", "<a href='". esc_url( wp_nonce_url( add_query_arg( 'wppb_expired_dismiss_notification', '0' ), 'wppb_license_notice_dismiss' ) ) ."' class='wppb-dismiss-notification'>", "</a>" ), 'wppb_license_status' );
        }
        
        
        // Maybe add about to expire notice
        if( $wppb_serial_status != 'expired' && !empty( $license_details ) && !empty( $license_details->expires ) && $license_details->expires !== 'lifetime' ){
    
            if( ( !isset( $license_details->subscription_status ) || $license_details->subscription_status != 'active' ) && strtotime( $license_details->expires ) < strtotime( '+14 days' ) ){
                new WPPB_add_notices( 'wppb_about_to_expire', 'profile_builder_pro', sprintf( '<p>' . __( 'Your <strong>Profile Builder license is about to expire on %5$s</strong>. <br/>Please %1$sRenew Your Licence%2$s to maintain access to new features, premium addons, product downloads & automatic updates — including important security patches and WordPress compatibility. %3$sRenew now %4$s %6$sDismiss%7$s', 'profile-builder') . '</p>', "<a href='https://www.cozmoslabs.com/account/?utm_source=wp-dashboard&utm_medium=client-site&utm_campaign=pb-expire-soon' target='_blank'>", "</a>", "<a href='https://www.cozmoslabs.com/account/?utm_source=wp-dashboard&utm_medium=client-site&utm_campaign=pb-expire-soon' target='_blank' class='button-primary'>", "</a>", date_i18n( get_option( 'date_format' ), strtotime( $license_details->expires ) ), "<a href='". esc_url( wp_nonce_url( add_query_arg( 'wppb_about_to_expire_dismiss_notification', '0' ), 'wppb_license_notice_dismiss' ) )."' class='wppb-dismiss-notification'>", "</a>" ), 'wppb_license_status' );
            }
    
        }
    
        if( isset( $license_details->license ) && $license_details->license == 'invalid' ){
    
            if( isset( $license_details->error ) && $license_details->error == 'no_activations_left' ){
    
                $activations_limit_message = '<p>' . sprintf( __( 'Your <strong>%s</strong> license has reached its activation limit.<br> Upgrade now for unlimited activations and extra features like multiple registration and edit profile forms, userlisting, custom redirects and more. <a class="button-primary" href="%s">Upgrade now</a>', 'profile-builder' ), PROFILE_BUILDER, esc_url( 'https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PBPro&utm_content=license-activation-limit' ) ) . '</p>';
    
                $notification_instance = WPPB_Plugin_Notifications::get_instance();
                if( !$notification_instance->is_plugin_page() ) {//add the dismiss button only on other pages in admin
                    $activations_limit_message .= sprintf(__(' %1$sDismiss%2$s', 'profile-builder'), "<a class='dismiss-right' href='" . esc_url( wp_nonce_url( add_query_arg('wppb_basic_activations_limit_dismiss_notification', '0' ), 'wppb_license_notice_dismiss' ) ) . "'>", "</a>");
                    $force_show = false;
                } else {
                    $force_show = true;//sets the forceShow parameter of WPPB_add_notices to true so we don't take into consideration the dismiss user meta
                }
    
                new WPPB_add_notices( 'wppb_basic_activations_limit',
                    'profile_builder_basic',
                    $activations_limit_message,
                    'error',
                    '',
                    '',
                    $force_show );
            }
    
        }
    
    }


    /**
     * Black Friday
     * 
     * Showing this to:
     *   free users
     *   users that have expired or disabled licenses
     */
    if( wppb_bf_show_promotion() ){

        $license_status = wppb_get_serial_number_status();
        $notifications  = WPPB_Plugin_Notifications::get_instance();

        // Plugin pages
        if( $notifications->is_plugin_page() ){

            $notification_id = 'wppb_bf_2025';

            $message = '<div class="wppb-bf-notice-container"><img style="max-width: 60px;width: 60px;" src="' . WPPB_PLUGIN_URL . 'assets/images/pb-logo.svg" />';

            if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && $license_status == 'expired' ){
                $message .= '<div><p style="font-size: 110%;margin-top:0px;margin-bottom:4px;padding:0px;">' . '<strong>Renew your Profile Builder license this Black Friday! </strong>' . '</p>';
                $message .= '<p style="font-size: 110%;margin-top:0px;margin-bottom: 0px;padding:0px;">Don\'t miss out on our <strong>best prices & only sale of the year</strong>. <br><a class="button-primary" style="margin-top:6px;margin-left: 0px !important;" href="https://www.cozmoslabs.com/account/?utm_source=pb-settings&utm_medium=clientsite&utm_campaign=BF-2025" target="_blank">Get Deal</a></p></div>';
            } else {
                $message .= '<div><p style="font-size: 110%;margin-top:0px;margin-bottom:4px;padding:0px;">' . '<strong>Get the best price for Profile Builder PRO this Black Friday!</strong>' . '</p>';
                $message .= '<p style="font-size: 110%;margin-top:0px;margin-bottom: 0px;padding:0px;">This is a <strong>limited-time offer</strong>, so don\'t miss out on our <strong>only sale of the year</strong>. <br><a class="button-primary" style="margin-top:6px;margin-left: 0px !important;" href="https://www.cozmoslabs.com/black-friday/?utm_source=pb-settings&utm_medium=clientsite&utm_campaign=BF-2025" target="_blank">Get Deal</a></p></div>';
            }

            $message .= '</div><a href="' . wp_nonce_url( add_query_arg( array( 'wppb_dismiss_admin_notification' => $notification_id ) ), 'wppb_plugin_notice_dismiss' ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'profile-builder' ) . '</span></a>';

            $notifications->add_notification( $notification_id, $message, 'wppb-notice notice notice-info' );

        } else {

            if( wppb_bf_show_shared_promotion() ){

                $notification_id = 'wppb_bf_2025_cross_promotion';
        
                $message = '<img style="float: left; margin: 10px 8px 10px 0px; max-width: 20px;" src="' . WPPB_PLUGIN_URL . 'assets/images/pb-logo.svg" />';
                $message .= '<img style="float: left; margin: 10px 8px 10px 0px; max-width: 20px;" src="' . WPPB_PLUGIN_URL . 'assets/images/pms-logo.svg" />';
        
                if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && $license_status == 'expired' )
                    $message .= '<p style="padding-right:30px;font-size: 110%;"><strong>Upgrade to Profile Builder & Paid Member Subscriptions PRO this Black Friday!</strong> Don\'t miss our only sale of the year. <a href="https://www.cozmoslabs.com/black-friday/?utm_source=wpdashboard&utm_medium=clientsite&utm_campaign=BF-2025" target="_blank">Learn more</a></p>';
                else
                    $message .= '<p style="padding-right:30px;font-size: 110%;"><strong>Upgrade to Profile Builder & Paid Member Subscriptions PRO this Black Friday!</strong> Don\'t miss our only sale of the year. <a href="https://www.cozmoslabs.com/black-friday/?utm_source=wpdashboard&utm_medium=clientsite&utm_campaign=BF-2025" target="_blank">Learn more</a></p>';
                
                $message .= '<a href="' . wp_nonce_url( add_query_arg( array( 'wppb_dismiss_admin_notification' => $notification_id ) ), 'wppb_plugin_notice_dismiss' ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'profile-builder' ) . '</span></a>';
        
                $notifications->add_notification( $notification_id, $message, 'wppb-notice notice notice-info', false, array(), true );
        
            } else {

                $notification_id = 'wppb_bf_2025';

                $message = '<img style="float: left; margin: 10px 8px 10px 0px; max-width: 20px;" src="' . WPPB_PLUGIN_URL . 'assets/images/pb-logo.svg" />';
                
                if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && $license_status == 'expired' )
                    $message .= '<p style="padding-right:30px;font-size: 110%;"><strong>Upgrade to Profile Builder PRO this Black Friday!</strong> Don\'t miss our only sale of the year. <a href="https://www.cozmoslabs.com/black-friday/?utm_source=wpdashboard&utm_medium=clientsite&utm_campaign=BF-2025" target="_blank">Learn more</a></p>';
                else
                    $message .= '<p style="padding-right:30px;font-size: 110%;"><strong>Upgrade to Profile Builder PRO this Black Friday!</strong> Don\'t miss our only sale of the year. <a href="https://www.cozmoslabs.com/black-friday/?utm_source=wpdashboard&utm_medium=clientsite&utm_campaign=BF-2025" target="_blank">Learn more</a></p>';
                
                $message .= '<a href="' . wp_nonce_url( add_query_arg( array( 'wppb_dismiss_admin_notification' => $notification_id ) ), 'wppb_plugin_notice_dismiss' ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'profile-builder' ) . '</span></a>';
        
                $notifications->add_notification( $notification_id, $message, 'wppb-notice notice notice-info', false, array(), true );

            }

        }

    }

}

function wppb_bf_show_promotion(){

    if( !wppb_bf_promotion_is_active() )
        return false;

    if( !defined( 'WPPB_PAID_PLUGIN_DIR' ) )
        return true;

    $license_details = get_option( 'wppb_license_details', false );

    if( !empty( $license_details ) ){

        if( isset( $license_details->error ) && in_array( $license_details->error, [ 'expired', 'disabled', 'revoked', 'missing', 'no_activations_left' ] ) )
            return true;

    }

    return false;

}

function wppb_bf_show_shared_promotion(){

    if( !wppb_bf_show_promotion() )
        return false;

    // verify if the PMS promotion should be shown
    if( !defined( 'PAID_MEMBER_SUBSCRIPTIONS' ) )
        return false;

    // make sure the promotion is not shown on the PMS pages, only on other Dashboard pages
    if( class_exists( 'PMS_Plugin_Notifications' ) ){
        $pms_notifications_instance = PMS_Plugin_Notifications::get_instance();

        if( $pms_notifications_instance->is_plugin_page() )
            return false;
    }

    $license_details = get_option( 'pms_license_details', false );

    if( !empty( $license_details ) ){

        if( isset( $license_details->error ) && in_array( $license_details->error, [ 'expired', 'disabled', 'revoked', 'missing', 'no_activations_left' ] ) )
            return true;

    }

    if( !defined( 'PMS_PAID_PLUGIN_DIR' ) )
        return true;

    return false;

}

function wppb_bf_promotion_is_active(){
    
    $black_friday = array(
        'start_date' => '11/24/2025 00:00',
        'end_date'   => '12/02/2025 23:59',
    );

    $current_date = time();

    if( $current_date > strtotime( $black_friday['start_date'] ) && $current_date < strtotime( $black_friday['end_date'] ) )
        return true;

    return false;

}
