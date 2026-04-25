<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Function that creates the "Paid Accounts" submenu page
 *
 * @since v.2.0
 *
 * @return void
 */
function wppb_register_pms_cross_promo() {
	add_submenu_page( 'Profile Builder Paid Accounts', __( 'Paid Accounts', 'profile-builder' ), __( 'Paid Accounts', 'profile-builder' ), 'manage_options', 'profile-builder-pms-promo', 'wppb_pms_cross_promo' );
}
add_action( 'admin_menu', 'wppb_register_pms_cross_promo', 2 );


function wppb_deactivate_pms_plugin() {
    check_ajax_referer( 'wppb-activate-addon', 'nonce' );

    if ( current_user_can( 'activate_plugins' )  && !empty( $_POST['wppb_add_on_to_deactivate'] ) && !empty( $_POST['wppb_add_on_index'] ) ) {
        $plugin_path = str_replace(array("http://", "https://"), "", sanitize_text_field( $_POST['wppb_add_on_to_deactivate']) );
        deactivate_plugins( $plugin_path );
        echo esc_html( sanitize_text_field( $_POST['wppb_add_on_index'] ) );
    } else {
        echo 'error';
    }

    wp_die();
}
add_action('wp_ajax_wppb_add_on_deactivate', 'wppb_deactivate_pms_plugin');


function wppb_activate_pms_plugin() {
    check_ajax_referer( 'wppb-activate-addon', 'nonce' );

    if ( current_user_can( 'activate_plugins' ) && !empty( $_POST['wppb_add_on_to_activate'] ) && !empty( $_POST['wppb_add_on_index'] )  ) {
        $plugin_path = str_replace(array("http://", "https://"), "", sanitize_text_field( $_POST['wppb_add_on_to_activate']) );
        activate_plugin( $plugin_path );
        echo esc_html( sanitize_text_field( $_POST['wppb_add_on_index'] ) );
    } else {
        echo 'error';
    }

    wp_die();
}
add_action('wp_ajax_wppb_add_on_activate', 'wppb_activate_pms_plugin');


/**
 * Function that adds content to the "Paid Accounts" submenu page
 *
 * @since v.2.0
 *
 * @return string
 */
function wppb_pms_cross_promo() {
	?>
	<div class="wrap wppb-wrap wppb-info-wrap cozmoslabs-wrap">
        <div class="cozmoslabs-form-subsection-wrapper" id="pb-pms-cross-promo-header">

            <div class="cozmoslabs-page-header">
                <div>
                    <h1 class="cozmoslabs-page-title"><?php esc_html_e( 'Users can pay for an account with', 'profile-builder' ); ?></h1>
                    <h3><?php esc_html_e( 'Profile Builder and Paid Member Subscriptions', 'profile-builder' ); ?></h3>
                    <p class="cozmoslabs-description"><?php esc_html_e( 'One of the most requested features in Profile Builder was for users to be able to pay for an account.', 'profile-builder' ); ?></p>
                    <p class="cozmoslabs-description">
                        <?php esc_html_e( 'Now that is possible using the free WordPress plugin - ', 'profile-builder' ); ?>
                        <a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=pb-pms-promo&utm_campaign=PBFree"><?php esc_html_e( 'Paid Member Subscriptions', 'profile-builder' ); ?></a>
                    </p>
                </div>
                <div class="wppb-badge wppb-pb-pms"></div>
            </div>
        </div>

        <div class="cozmoslabs-form-subsection-wrapper">
            <h2 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Paid Member Subscriptions - a free WordPress plugin', 'profile-builder' ); ?></h2>
            <p class="cozmoslabs-description"><?php esc_html_e( 'With the new Subscriptions Field in Profile Builder, your registration forms will allow your users to sign up for paid accounts.', 'profile-builder' ); ?></p>

            <div id="pb-pms-cross-promo-pms-plugin">

                <div id="pb-pms-cross-promo-pms-fetures"  class="cozmoslabs-description">
                    <p><?php esc_html_e( 'Other features of Paid Member Subscriptions are:', 'profile-builder' ); ?></p>
                    <ul>
                        <li><?php esc_html_e( 'Paid & Free Subscriptions', 'profile-builder' ); ?></li>
                        <li><?php esc_html_e( 'Restrict Content', 'profile-builder' ); ?></li>
                        <li><?php esc_html_e( 'Member Management', 'profile-builder' ); ?></li>
                        <li><?php esc_html_e( 'Email Templates', 'profile-builder' ); ?> </li>
                        <li><?php esc_html_e( 'Account Management', 'profile-builder' ); ?> </li>
                        <li><?php esc_html_e( 'Subscription Management', 'profile-builder' ); ?> </li>
                        <li><?php esc_html_e( 'Payment Management', 'profile-builder' ); ?> </li>
                    </ul>
                </div>

                <div>
                    <?php
                    $wppb_get_all_plugins = get_plugins();
                    $wppb_get_active_plugins = get_option('active_plugins');

                    $ajax_nonce = wp_create_nonce("wppb-activate-addon");

                    $pms_add_on_exists = 0;
                    $pms_add_on_is_active = 0;
                    $pms_add_on_is_network_active = 0;
                    // Check to see if add-on is in the plugins folder
                    foreach ($wppb_get_all_plugins as $wppb_plugin_key => $wppb_plugin) {
                        if( strtolower($wppb_plugin['Name']) == strtolower( 'Paid Member Subscriptions' ) && strpos(strtolower($wppb_plugin['AuthorName']), strtolower('Cozmoslabs')) !== false) {
                            $pms_add_on_exists = 1;
                            if (in_array($wppb_plugin_key, $wppb_get_active_plugins)) {
                                $pms_add_on_is_active = 1;
                            }
                            // Consider the add-on active if it's network active
                            if (is_plugin_active_for_network($wppb_plugin_key)) {
                                $pms_add_on_is_network_active = 1;
                                $pms_add_on_is_active = 1;
                            }
                            $plugin_file = $wppb_plugin_key;
                        }
                    }
                    ?>

                    <span id="wppb-add-on-activate-button-text" class="wppb-add-on-user-messages"><?php echo esc_html__( 'Activate', 'profile-builder' ); ?></span>

                    <span id="wppb-add-on-downloading-message-text" class="wppb-add-on-user-messages"><?php echo esc_html__( 'Downloading and installing...', 'profile-builder' ); ?></span>
                    <span id="wppb-add-on-download-finished-message-text" class="wppb-add-on-user-messages"><?php echo esc_html__( 'Installation complete', 'profile-builder' ); ?></span>

                    <span id="wppb-add-on-activated-button-text" class="wppb-add-on-user-messages"><?php echo esc_html__( 'Plugin is Active', 'profile-builder' ); ?></span>
                    <span id="wppb-add-on-activated-message-text" class="wppb-add-on-user-messages"><?php echo esc_html__( 'Plugin has been activated', 'profile-builder' ) ?></span>
                    <span id="wppb-add-on-activated-error-button-text" class="wppb-add-on-user-messages"><?php echo esc_html__( 'Retry Install', 'profile-builder' ) ?></span>

                    <span id="wppb-add-on-is-active-message-text" class="wppb-add-on-user-messages"><?php echo sprintf( wp_kses_post( __('Plugin is %1$s active %2$s', 'profile-builder') ), '<strong>', '</strong>' ); ?></span>
                    <span id="wppb-add-on-is-active-message-text" class="wppb-add-on-user-messages"><?php echo sprintf( wp_kses_post( __('Plugin is %1$s inactive %2$s', 'profile-builder') ), '<strong>', '</strong>' ); ?></span>

                    <span id="wppb-add-on-deactivate-button-text" class="wppb-add-on-user-messages"><?php echo esc_html__( 'Deactivate', 'profile-builder' ) ?></span>
                    <span id="wppb-add-on-deactivated-message-text" class="wppb-add-on-user-messages"><?php echo esc_html__( 'Plugin has been deactivated.', 'profile-builder' ) ?></span>


                    <div class="plugin-card wppb-recommended-plugin wppb-add-on">
                        <div class="plugin-card-top">
                            <div class="wppb-recommended-plugin-banner">
                                <a target="_blank" href="http://wordpress.org/plugins/paid-member-subscriptions/">
                                    <img src="<?php echo esc_url( plugins_url( '../assets/images/pms-banner.png', __FILE__ ) ); ?>" width="100%">
                                </a>
                            </div>
                            <h3 class="wppb-add-on-title">
                                <a target="_blank" href="http://wordpress.org/plugins/paid-member-subscriptions/">Paid Member Subscriptions</a>
                            </h3>
                            <h3 class="wppb-add-on-price"><?php  esc_html_e( 'Free', 'profile-builder' ) ?></h3>
                            <p class="wppb-add-on-description">
                                <?php esc_html_e( 'Accept user payments, create subscription plans and restrict content on your website.', 'profile-builder' ) ?>
                                <a href="<?php esc_url( admin_url() );?>plugin-install.php?tab=plugin-information&plugin=paid-member-subscriptions&TB_iframe=true&width=772&height=875" class="thickbox" aria-label="More information about Paid Member Subscriptions - membership & content restriction" data-title="Paid Member Subscriptions - membership & content restriction"><?php esc_html_e( 'More Details', 'profile-builder' ); ?></a>
                            </p>
                        </div>
                        <div class="plugin-card-bottom wppb-add-on-compatible">
                            <?php
                            if ($pms_add_on_exists) {

                                // Display activate/deactivate buttons
                                if (!$pms_add_on_is_active) {
                                    echo '<a class="wppb-add-on-activate right button button-primary" href="' . esc_url( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Activate', 'profile-builder') . '</a>';

                                    // If add-on is network activated don't allow deactivation
                                } elseif (!$pms_add_on_is_network_active) {
                                    echo '<a class="wppb-add-on-deactivate right button button-secondary" href="' . esc_url( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Deactivate', 'profile-builder') . '</a>';
                                }

                                // Display message to the user
                                if( !$pms_add_on_is_active ){
                                    echo '<span class="dashicons dashicons-yes"></span><span class="wppb-add-on-message">' . sprintf( wp_kses_post( __( 'Plugin is %1$s inactive %2$s', 'profile-builder' ) ), '<strong>', '</strong>' ) . '</span>';
                                } else {
                                    echo '<span class="dashicons dashicons-yes"></span><span class="wppb-add-on-message">' . sprintf( wp_kses_post( __( 'Plugin is %1$s active %2$s', 'profile-builder' ) ), '<strong>', '</strong>' ) . '</span>';
                                }

                            } else {

                                // If we're on a multisite don't add the wpp-add-on-download class to the button so we don't fire the js that
                                // handles the in-page download
                                if (is_multisite()) {
                                    $wppb_paid_link_class = 'button-primary';
                                    $wppb_paid_link_text = esc_html__('Download Now', 'profile-builder' );
                                } else {
                                    $wppb_paid_link_class = 'button-primary wppb-add-on-download';
                                    $wppb_paid_link_text = esc_html__('Install Now', 'profile-builder');
                                }

                                echo '<a target="_blank" class="right button ' . esc_attr( $wppb_paid_link_class ) . '" href="https://downloads.wordpress.org/plugin/paid-member-subscriptions.zip" data-add-on-slug="paid-member-subscriptions" data-add-on-name="Paid Member Subscriptions" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html( $wppb_paid_link_text ) . '</a>';
                                echo '<span class="dashicons dashicons-yes"></span><span class="wppb-add-on-message">' . esc_html__('Compatible with your version of Profile Builder.', 'profile-builder') . '</span>';

                            }
                            ?>
                            <div class="spinner"></div>
                            <span class="wppb-add-on-user-messages wppb-error-manual-install"><?php printf(esc_html__('Could not install plugin. Retry or <a href="%s" target="_blank">install manually</a>.', 'profile-builder'), esc_url( 'http://www.wordpress.org/plugins/paid-member-subscriptions' )) ?></a>.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cozmoslabs-form-subsection-wrapper">
            <h2 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Step by Step Quick Setup', 'profile-builder' ); ?></h2>
            <p class="cozmoslabs-description"><?php esc_html_e( 'Setting up Paid Member Subscriptions opens the door to paid user accounts.', 'profile-builder' ); ?></p>

            <div class="cozmoslabs-form-subsection-wrapper">
                <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Create Subscription Plans', 'profile-builder' ); ?></h3>
                <p class="cozmoslabs-description"><?php esc_html_e( 'With Paid Member Subscriptions it’s fairly easy to create tiered subscription plans for your users.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description"><?php esc_html_e( 'Adding a new subscription gives you access to the following options to set up: subscription name, description, duration, the price, status and user role.', 'profile-builder' ); ?></p>
                <div class="cozmoslabs-subsection-image">
                    <a href="<?php echo esc_url( WPPB_PLUGIN_URL ); ?>assets/images/pms_all_subscriptions.jpg" target="_blank">
                        <img src="<?php echo esc_url( WPPB_PLUGIN_URL ); ?>assets/images/pms_all_subscriptions.jpg" alt="paid subscription plans"/>
                    </a>
                </div>
            </div>

            <div class="cozmoslabs-form-subsection-wrapper">
                <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Add Subscriptions field to Profile Builder -> Manage Fields', 'profile-builder' ); ?></h3>
                <p class="cozmoslabs-description"><?php esc_html_e( 'The new Subscription Plans field will add a list of radio buttons with membership details to Profile Builder registration forms.', 'profile-builder' ); ?></p>
                <div class="cozmoslabs-subsection-image">
                    <a href="<?php echo esc_url( WPPB_PLUGIN_URL ); ?>assets/images/pms_pb_add_subscription.jpg" target="_blank">
                        <img src="<?php echo esc_url( WPPB_PLUGIN_URL ); ?>assets/images/pms_pb_add_subscription.jpg" alt="manage fields subscription plans"/>
                    </a>
                </div>
            </div>

            <div class="cozmoslabs-form-subsection-wrapper">
                <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Start getting user payments', 'profile-builder' ); ?></h3>
                <p class="cozmoslabs-description"><?php esc_html_e( 'To finalize registration for a paid account, users will need to complete the payment.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description"><?php esc_html_e( 'Members created with Profile Builder registration form will have the user role of the selected subscription.', 'profile-builder' ); ?></p>
                <div class="cozmoslabs-subsection-image">
                    <a href="<?php echo esc_url( WPPB_PLUGIN_URL ); ?>assets/images/pms_pb_register_page.jpg" target="_blank">
                        <img src="<?php echo esc_url( WPPB_PLUGIN_URL ); ?>assets/images/pms_pb_register_page.jpg" alt="register payed accounts"/>
                    </a>
                </div>
            </div>

        </div>


        <div id="pms-bottom-install" class="wppb-add-on">
            <div class="plugin-card-bottom wppb-add-on-compatible">
                <?php
                if ($pms_add_on_exists) {

                    // Display activate/deactivate buttons
                    if (!$pms_add_on_is_active) {
                        echo '<a class="wppb-add-on-activate right button button-primary" href="' . esc_url( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Activate', 'profile-builder') . '</a>';

                        // If add-on is network activated don't allow deactivation
                    } elseif (!$pms_add_on_is_network_active) {
                        echo '<a class="wppb-add-on-deactivate right button button-secondary" href="' . esc_url( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Deactivate', 'profile-builder') . '</a>';
                    }

                    // Display message to the user
                    if( !$pms_add_on_is_active ){
                        echo '<span class="dashicons dashicons-no-alt"></span><span class="wppb-add-on-message">' . sprintf( wp_kses_post( __( 'Plugin is %1$s inactive %2$s', 'profile-builder' ) ), '<strong>', '</strong>' ) . '</span>';
                    } else {
                        echo '<span class="dashicons dashicons-yes"></span><span class="wppb-add-on-message">' . sprintf( wp_kses_post( __( 'Plugin is %1$s active %2$s', 'profile-builder' ) ), '<strong>', '</strong>' ) . '</span>';
                    }

                } else {

                    // If we're on a multisite don't add the wpp-add-on-download class to the button so we don't fire the js that
                    // handles the in-page download
                    if (is_multisite()) {
                        $wppb_paid_link_class = 'button-secondary';
                        $wppb_paid_link_text = __('Download Now', 'profile-builder' );
                    } else {
                        $wppb_paid_link_class = 'button-secondary wppb-add-on-download';
                        $wppb_paid_link_text = __('Install Now', 'profile-builder');
                    }

                    echo '<a target="_blank" class="right button ' . esc_attr( $wppb_paid_link_class ) . '" href="https://downloads.wordpress.org/plugin/paid-member-subscriptions.zip" data-add-on-slug="paid-member-subscriptions" data-add-on-name="Paid Member Subscriptions" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html( $wppb_paid_link_text ) . '</a>';
                    echo '<span class="dashicons dashicons-yes"></span><span class="wppb-add-on-message">' . esc_html__('Compatible with your version of Profile Builder.', 'profile-builder') . '</span>';
                }
                ?>
                <div class="spinner"></div>
                <?php /* <span class="wppb-add-on-user-messages wppb-error-manual-install"><?php printf(__('Could not install plugin. Retry or <a href="%s" target="_blank">install manually</a>.', 'profile-builder'), esc_url( 'http://www.wordpress.org/plugins/paid-member-subscriptions' )) ?></a>.</span> */ ?>
            </div>
        </div>


	</div>
<?php
	}
/*
 * Instantiate a new notification for the PMS cross Promotion
 *
 * @Since 2.2.5
 */
add_action('init', function()
{
    if ( ( !isset($_GET['page']) || $_GET['page'] != 'profile-builder-pms-promo' ) && function_exists('is_plugin_active') && !is_plugin_active( 'paid-member-subscriptions/index.php' ) ){
        new WPPB_Add_General_Notices('wppb_pms_cross_promo',
            sprintf(__('Allow your users to have <strong>paid accounts with Profile Builder</strong>. %1$sFind out how >%2$s %3$sDismiss%4$s', 'profile-builder'), "<a href='" . esc_url( admin_url('options.php?page=profile-builder-pms-promo') ) . "'>", "</a>", "<a class='wppb-dismiss-notification' href='" . esc_url( wp_nonce_url( add_query_arg( 'wppb_pms_cross_promo_dismiss_notification', '0' ), 'wppb_general_notice_dismiss' ) ) . "'>", "</a>"),
            'pms-cross-promo');
    }
});