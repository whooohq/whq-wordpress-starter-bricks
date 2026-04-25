<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Cozmoslabs_Plugin_Optin_WPPB {

    public static $user_name           = '';
    public static $api_url             = 'https://www.cozmoslabs.com/wp-json/cozmos-api/';
    public static $base_url            = 'https://usagetracker.cozmoslabs.com/update';
    public static $plugin_optin_status = '';
    public static $plugin_optin_email  = '';

    public static $plugin_option_key       = 'cozmos_wppb_plugin_optin';
    public static $plugin_option_email_key = 'cozmos_wppb_plugin_optin_email';

    public function __construct(){

        if( apply_filters( 'wppb_enable_plugin_optin', true ) === false )
            return;

        if ( !wp_next_scheduled( 'cozmos_wppb_plugin_optin_sync' ) )
            wp_schedule_event( time(), 'weekly', 'cozmos_wppb_plugin_optin_sync' );

        add_action( 'cozmos_wppb_plugin_optin_sync', array( 'Cozmoslabs_Plugin_Optin_WPPB', 'sync_data' ) );

        self::$plugin_optin_status = get_option( self::$plugin_option_key, false );
        self::$plugin_optin_email  = get_option( self::$plugin_option_email_key, false );

        add_action( 'admin_init', array( $this, 'redirect_to_plugin_optin_page' ) );
        add_action( 'admin_menu', array( $this, 'add_submenu_page_optin' ) );
        add_action( 'admin_init', array( $this, 'process_optin_actions' ) );
        add_action( 'activate_plugin', array( $this, 'process_paid_plugin_activation' ) );
        add_action( 'deactivated_plugin', array( $this, 'process_paid_plugin_deactivation' ) );
        add_filter( 'wppb_advanced_settings_sanitize', array( $this, 'process_plugin_optin_advanced_setting' ), 20, 2 );

    }

    public function redirect_to_plugin_optin_page(){

        if( ( isset( $_GET['page'] ) && sanitize_text_field( $_GET['page'] ) == 'wppb-optin-page' ) || ( isset( $_GET['page'] ) && isset( $_GET['subpage'] ) && sanitize_text_field( $_GET['page'] ) == 'profile-builder-dashboard' && sanitize_text_field( $_GET['subpage'] ) == 'wppb-setup' ) )
            return;

        if( self::$plugin_optin_status !== false )
            return;

        // Show this only when admin tries to access a plugin page
        $target_slugs   = array( 'profile-builder-', 'manage-fields', 'wppb-', 'admin-email-customizer', 'user-email-customizer', 'pbie', 'manage-fields', 'custom-redirects', 'pb-labels-edit' );
        $is_plugin_page = false;

        if( !empty( $target_slugs ) ){
            foreach ( $target_slugs as $slug ){

                if( ! empty( $_GET['page'] ) && false !== strpos( sanitize_text_field( $_GET['page'] ), $slug ) )
                    $is_plugin_page = true;

                if( ! empty( $_GET['post_type'] ) && false !== strpos( sanitize_text_field( $_GET['post_type'] ), $slug ) )
                    $is_plugin_page = true;

                if( ! empty( $_GET['post'] ) && false !== strpos( get_post_type( (int)$_GET['post'] ), $slug ) )
                    $is_plugin_page = true;

            }
        }

        if( $is_plugin_page == true ){
            wp_safe_redirect( admin_url( 'admin.php?page=wppb-optin-page' ) );
            exit();
        }

        return;

    }

    public function add_submenu_page_optin() {
        add_submenu_page( 'WPPBHidden', 'Profile Builder Plugin Optin', 'WPPBHidden', 'manage_options', 'wppb-optin-page', array(
            $this,
            'optin_page_content'
        ) );
	}

    public function optin_page_content(){
        require_once WPPB_PLUGIN_DIR . 'admin/plugin-optin/view-plugin-optin.php';
    }

    public function process_optin_actions(){

        if( !isset( $_GET['page'] ) || $_GET['page'] != 'wppb-optin-page' || !isset( $_GET['_wpnonce'] ) )
            return;

        if( !current_user_can( 'manage_options' ) )
            return;

        if( wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'cozmos_enable_plugin_optin' ) ){

            $args = array(
                'method' => 'POST',
                'body'   => array(
                    'email'   => get_option( 'admin_email' ),
                    'name'    => self::get_user_name(),
                    'version' => self::get_current_active_version(),
                    'product' => 'wppb',
                ),
            );

            // Check if the other plugin might be active as well
            $args = $this->add_other_plugin_version_information( $args );

            $request = wp_remote_post( self::$api_url . 'pluginOptinSubscribe/', $args );

            update_option( self::$plugin_option_key, 'yes' );
            update_option( self::$plugin_option_email_key, get_option( 'admin_email' ) );

            $settings = get_option( 'wppb_toolbox_admin_settings', array() );

            if( empty( $settings ) )
                $settings = array( 'plugin-optin' => 'yes' );
            else
                $settings['plugin-optin'] = 'yes';

            update_option( 'wppb_toolbox_admin_settings', $settings );

            wp_safe_redirect( admin_url( 'admin.php?page=profile-builder-dashboard' ) );
            exit;

        }

        if( wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'cozmos_disable_plugin_optin' ) ){

            update_option( self::$plugin_option_key, 'no' );

            $settings = get_option( 'wppb_toolbox_admin_settings', array() );

            if( empty( $settings ) )
                $settings = array( 'plugin-optin' => 'no' );
            else
                $settings['plugin-optin'] = 'no';

            update_option( 'wppb_toolbox_admin_settings', $settings );

            wp_safe_redirect( admin_url( 'admin.php?page=profile-builder-dashboard' ) );
            exit;

        }

    }

    // Update tags when a paid version is activated
    public function process_paid_plugin_activation( $plugin ){

        if( self::$plugin_optin_status !== 'yes' || self::$plugin_optin_email === false )
            return;

        $target_plugins = [ 'profile-builder-agency/index.php', 'profile-builder-pro/index.php', 'profile-builder-unlimited/index.php', 'profile-builder-hobbyist/index.php' ];

        if( !in_array( $plugin, $target_plugins ) )
            return;

        $version = explode( '/', $plugin );
        $version = str_replace( 'profile-builder-', '', $version[0] );

        if( $version == 'hobbyist' )
            $version == 'basic';

        // Update user version tag
        $args = array(
            'method' => 'POST',
            'body'   => array(
                'email'   => self::$plugin_optin_email,
                'version' => $version,
                'product' => 'wppb',
            )
        );

        // Check if the other plugin might be active as well
        $args = $this->add_other_plugin_version_information( $args );

        $request = wp_remote_post( self::$api_url . 'pluginOptinUpdateVersion/', $args );

    }

    // Update tags when a paid version is deactivated
    public function process_paid_plugin_deactivation( $plugin ){

        if( self::$plugin_optin_status !== 'yes' || self::$plugin_optin_email === false )
            return;

        $target_plugins = [ 'profile-builder-agency/index.php', 'profile-builder-pro/index.php', 'profile-builder-unlimited/index.php', 'profile-builder-hobbyist/index.php' ];

        if( !in_array( $plugin, $target_plugins ) )
            return;

        // Update user version tag
        $args = array(
            'method' => 'POST',
            'body'   => [
                'email'   => self::$plugin_optin_email,
                'version' => 'free',
                'product' => 'wppb',
            ],
        );

        $request = wp_remote_post( self::$api_url . 'pluginOptinUpdateVersion/', $args );

    }

    // Advanced settings
    public function process_plugin_optin_advanced_setting( $settings, $previous_settings ){

        if( !isset( $settings['plugin-optin'] ) || $settings['plugin-optin'] == 'no' ){

            update_option( self::$plugin_option_key, 'no' );

            if( self::$plugin_optin_email === false )
                return $settings;

            $args = array(
                'method' => 'POST',
                'body'   => [
                    'email'   => self::$plugin_optin_email,
                    'product' => 'wppb',
                ],
            );

            $request = wp_remote_post( self::$api_url . 'pluginOptinArchiveSubscriber/', $args );

        } else if ( $settings['plugin-optin'] == 'yes' ) {

            if( isset( $previous_settings['plugin-optin'] ) && $settings['plugin-optin'] == $previous_settings['plugin-optin'] ){

                // if the user has not changed the setting, we don't need to send the data again but if the option is not set, we need to send the data
                if( self::$plugin_optin_status == 'yes' )
                    return $settings;

            }

            update_option( self::$plugin_option_key, 'yes' );
            update_option( self::$plugin_option_email_key, get_option( 'admin_email' ) );

            if( self::$plugin_optin_email === false )
                return $settings;

            $args = array(
                'method' => 'POST',
                'body'   => [
                    'email'   => self::$plugin_optin_email,
                    'name'    => self::get_user_name(),
                    'product' => 'wppb',
                    'version' => self::get_current_active_version(),
                ],
            );

            // Check if the other plugin might be active as well
            $args = $this->add_other_plugin_version_information( $args );

            $request = wp_remote_post( self::$api_url . 'pluginOptinSubscribe/', $args );

        }

        return $settings;

    }

    public function add_other_plugin_version_information( $args ){

        $target_found = false;

        // paid versions
        $target_plugins = [ 'paid-member-subscriptions-agency/index.php', 'paid-member-subscriptions-pro/index.php', 'paid-member-subscriptions-unlimited/index.php', 'paid-member-subscriptions-basic/index.php' ];

        foreach( $target_plugins as $plugin ){
            if( is_plugin_active( $plugin ) || is_plugin_active_for_network( $plugin ) ){
                $target_found = $plugin;
                break;
            }
        }

        // verify free version separately
        if( $target_found === false ){

            if( is_plugin_active( 'paid-member-subscriptions/index.php' ) || is_plugin_active_for_network( 'paid-member-subscriptions/index.php' ) )
                $target_found = 'paid-member-subscriptions-free';

        }

        if( $target_found !== false ){

            $target_found = explode( '/', $target_found );
            $target_found = str_replace( 'paid-member-subscriptions-', '', $target_found[0] );

            $args['body']['other_product_data'] = array(
                'product' => 'pms',
                'version' => $target_found,
            );

        }

        return $args;

    }

    // Determine current user name
    public static function get_user_name(){

        if( !empty( self::$user_name ) )
            return self::$user_name;

        $user = wp_get_current_user();

        $name = $user->display_name;

        $first_name = get_user_meta( $user->ID, 'first_name', true );
        $last_name  = get_user_meta( $user->ID, 'last_name', true );

        if( !empty( $first_name ) && !empty( $last_name ) )
            $name = $first_name . ' ' . $last_name;

        self::$user_name = $name;

        return self::$user_name;

    }

    // Determine current active plugin version
    public static function get_current_active_version(){

        if( !function_exists( 'is_plugin_active' ) || !function_exists( 'is_plugin_active_for_network' ) )
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        if( is_plugin_active( 'profile-builder-agency/index.php' ) || is_plugin_active_for_network( 'profile-builder-agency/index.php' ) )
            return 'agency';
        elseif( is_plugin_active( 'profile-builder-pro/index.php' ) || is_plugin_active_for_network( 'profile-builder-pro/index.php' ) )
            return 'pro';
        elseif( is_plugin_active( 'profile-builder-unlimited/index.php' ) || is_plugin_active_for_network( 'profile-builder-unlimited/index.php' ) )
            return 'unlimited';
        elseif( is_plugin_active( 'profile-builder-hobbyist/index.php' ) || is_plugin_active_for_network( 'profile-builder-hobbyist/index.php' ) )
            return 'basic';

        return 'free';

    }

    public static function sync_data(){

        if( self::$plugin_optin_status != 'yes' )
            return;

        $args = array(
            'method' => 'POST',
            'body'   => array(
                'home_url'       => home_url(),
                'product'        => 'wppb',
                'email'          => self::$plugin_optin_email,
                'name'           => self::get_user_name(),
                'version'        => self::get_current_active_version(),
                'license'        => wppb_get_serial_number(),
                'active_plugins' => json_encode( get_option( 'active_plugins', array() ) ),
                'wp_version'     => get_bloginfo('version'),
                'wp_locale'      => get_locale(),
                'plugin_version' => defined( 'PROFILE_BUILDER_VERSION' ) ? PROFILE_BUILDER_VERSION : '',
                'php_version'    => defined( 'PHP_VERSION' ) ? PHP_VERSION : '',
            ),
        );

        // Only send the major version for WordPress and PHP
        // e.g. 1.x
        $target_keys = array( 'wp_version', 'php_version' );

        foreach( $target_keys as $key ){
            $version_number = explode( '.', $args['body'][$key] );

            if( isset( $version_number[0] ) && isset( $version_number[1] ) )
                $args['body'][$key] = $version_number[0] . '.' . $version_number[1];
        }

        $args = apply_filters( 'cozmoslabs_plugin_optin_wppb_metadata', $args );

        $request = wp_remote_post( self::$base_url, $args );

        // echo wp_remote_retrieve_body( $request );
        // die();

    }

}

new Cozmoslabs_Plugin_Optin_WPPB();

if( !class_exists( 'Cozmoslabs_Plugin_Optin_Metadata_Builder' ) ) {
    class Cozmoslabs_Plugin_Optin_Metadata_Builder {

        public $option_prefix = '';
        public $blacklisted_option_slugs = [];
        public $blacklisted_option_patterns = [];
        public $blacklisted_option_names = [];
        protected $metadata;

        public function __construct(){

            $this->metadata = [
                'settings' => [],
                'add-ons'  => [],
                'custom'   => [],
                'cpt'      => [],
            ];

            add_filter( 'cozmoslabs_plugin_optin_'. $this->option_prefix .'metadata', array( $this, 'build_metadata' ) );

        }

        public function build_metadata( $args ){
            // Get all options that start with the prefix
            $options = $this->get_option_keys();

            if( !empty( $options ) ){

                foreach( $options as $option ){

                    // exclude exact option names
                    if( in_array( $option['option_name'], $this->blacklisted_option_slugs ) ){
                        continue;
                    }

                    // exclude patterns
                    if( !empty( $this->blacklisted_option_patterns ) ){
                        $found_pattern = false;

                        foreach( $this->blacklisted_option_patterns as $pattern ){
                            if( strpos( $option['option_name'], $pattern ) !== false ){
                                $found_pattern = true;
                                break;
                            }
                        }

                        if( $found_pattern )
                            continue;
                    }

                    $option_value = get_option( $option['option_name'], false );

                    if( !empty( $option_value ) ){

                        if( is_array( $option_value ) ){
                            foreach( $option_value as $key => $value ){
                                if( !is_array( $value ) ){
                                    if( in_array( $key, $this->blacklisted_option_names ) )
                                    unset( $option_value[ $key ] );
                                } else {
                                    if( in_array( $key, $this->blacklisted_option_names ) )
                                        unset( $option_value[ $key ] );

                                    foreach( $value as $key_deep => $value_deep ){
                                        if( in_array( $key_deep, $this->blacklisted_option_names ) )
                                            unset( $option_value[ $key ][ $key_deep ] );
                                    }
                                }
                            }
                        }

                        // cleanup options like array( array( 'abc' ) ) to be array( 'abc' ) 
                        if( is_array( $option_value ) && count( $option_value ) == 1 && isset( $option_value[0] ) )
                            $option_value = $option_value[0];
                        
                        $this->metadata['settings'][ $option['option_name'] ] = $option_value;
                    }

                }

            }

            // Ability to add custom data
            $this->metadata = apply_filters( 'cozmoslabs_plugin_optin_'. $this->option_prefix .'metadata_builder_metadata', $this->metadata );

            $args['body']['metadata'] = $this->metadata;

            return $args;
        }

        private function get_option_keys(){

            global $wpdb;

            if( empty( $this->option_prefix ) )
                return [];
            
            $result = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM {$wpdb->prefix}options WHERE option_name LIKE %s", $this->option_prefix . '%' ), 'ARRAY_A' );

            if( !empty( $result ) )
                return $result;
        
            return [];

        }

    }
}

class Cozmoslabs_Plugin_Optin_Metadata_Builder_WPPB extends Cozmoslabs_Plugin_Optin_Metadata_Builder {

    public function __construct(){

        $this->option_prefix = 'wppb_';

        parent::__construct();

        $this->blacklisted_option_slugs = [
            'wppb-single-ul-templates',
            'wppb-ul-templates',
            'wppb_admin_emailc_common_settings_header',
            'wppb_admin_emailc_default_registration_email_content',
            'wppb_admin_emailc_default_registration_email_enabled',
            'wppb_admin_emailc_default_registration_email_subject',
            'wppb_admin_emailc_epaa_notification_content',
            'wppb_admin_emailc_epaa_notification_enabled',
            'wppb_admin_emailc_epaa_notification_subject',
            'wppb_admin_emailc_registration_with_admin_approval_email_content',
            'wppb_admin_emailc_registration_with_admin_approval_email_enabled',
            'wppb_admin_emailc_registration_with_admin_approval_email_subject',
            'wppb_admin_emailc_user_password_reset_email_content',
            'wppb_admin_emailc_user_password_reset_email_enabled',
            'wppb_admin_emailc_user_password_reset_email_subject',
            'wppb_advanced_add_ons_settings',
            'wppb_cleaned_up_user_status_taxonomy_from_db',
            'wppb_cmi_api_key_validated',
            'wppb_cr_default_wp_pages', // CR settings are added in a special way below
            'wppb_cr_global',
            'wppb_cr_role',
            'wppb_cr_user',
            'wppb_display_admin_settings',
            'wppb_edd_sl_initial_activation',
            'wppb_emailc_common_settings_from_name',
            'wppb_emailc_common_settings_from_reply_to_email',
            'wppb_free_add_ons_settings',
            'wppb_license_details',
            'wppb_license_key',
            'wppb_license_status',
            'wppb_mailchimp_api_key_validated',
            'wppb_manage_fields',
            'wppb_module_settings',
            'wppb_module_settings_description',
            'wppb_msf_break_points',
            'wppb_msf_tab_titles',
            'wppb_old_add_ons_status',
            'wppb_pages_created',
            'wppb_profile_builder_hobbyist_serial_status',
            'wppb_profile_builder_pro_serial',
            'wppb_profile_builder_pro_serial_status',
            'wppb_repackage_initial_upgrade',
            'wppb_review_request_status',
            'wppb_roles_editor_capabilities',
            'wppb_setup_wizard_steps',
            'wppb_user_emailc_admin_approval_notif_approved_email_content',
            'wppb_user_emailc_admin_approval_notif_approved_email_enabled',
            'wppb_user_emailc_admin_approval_notif_approved_email_subject',
            'wppb_user_emailc_admin_approval_notif_unapproved_email_content',
            'wppb_user_emailc_admin_approval_notif_unapproved_email_enabled',
            'wppb_user_emailc_admin_approval_notif_unapproved_email_subject',
            'wppb_user_emailc_change_email_address_content',
            'wppb_user_emailc_change_email_address_enabled',
            'wppb_user_emailc_change_email_address_request_content',
            'wppb_user_emailc_change_email_address_request_enabled',
            'wppb_user_emailc_change_email_address_request_subject',
            'wppb_user_emailc_change_email_address_subject',
            'wppb_user_emailc_default_registration_email_content',
            'wppb_user_emailc_default_registration_email_enabled',
            'wppb_user_emailc_default_registration_email_subject',
            'wppb_user_emailc_epaa_notification_content',
            'wppb_user_emailc_epaa_notification_enabled',
            'wppb_user_emailc_epaa_notification_subject',
            'wppb_user_emailc_registration_with_admin_approval_email_content',
            'wppb_user_emailc_registration_with_admin_approval_email_enabled',
            'wppb_user_emailc_registration_with_admin_approval_email_subject',
            'wppb_user_emailc_registr_w_email_confirm_email_content',
            'wppb_user_emailc_registr_w_email_confirm_email_enabled',
            'wppb_user_emailc_registr_w_email_confirm_email_subject',
            'wppb_user_emailc_reset_email_content',
            'wppb_user_emailc_reset_email_enabled',
            'wppb_user_emailc_reset_email_subject',
            'wppb_user_emailc_reset_success_email_content',
            'wppb_user_emailc_reset_success_email_enabled',
            'wppb_user_emailc_reset_success_email_subject',
            'wppb_user_pages',
            'wppb_version',
            'wppb_recaptcha_validations',
            'wppb_turnstile_validations',
        ];

        $this->blacklisted_option_names = [
            'api_key',
            'facebook-app-id',
            'google-client-id',
            'google-client-name',
            'twitter-api-key',
            'twitter-api-secret',
            'linkedin-client-id',
            'linkedin-client-secret',
            'admin-emails',
            'allowed_pages',
            'allowed_paths',
            'allowed_query_strings',
            'redirect_to',
            'lists',
            'heading-before-reg-buttons',
            'heading-before-ep-buttons',
            'facebook-button-text',
            'google-button-text',
            'twitter-button-text',
            'linkedin-button-text',
            'facebook-button-text-ep',
            'google-button-text-ep',
            'twitter-button-text-ep',
            'linkedin-button-text-ep',
            'message_logged_out',
            'message_logged_in',
            'purchasing_restricted'
        ];

        $this->blacklisted_option_patterns = [
            'wppb_repeater',
        ];

        add_action( 'cozmoslabs_plugin_optin_'. $this->option_prefix .'metadata_builder_metadata', array( $this, 'build_custom_plugin_metadata' ) );

    }

    public function build_custom_plugin_metadata(){

        // add custom redirects settings inside the settings array
        $this->generate_custom_redirects_settings();

        // content restriction data first
        $this->metadata['custom']['content_restriction'] = $this->generate_content_restriction_data();

        // add-ons data
        $this->metadata['addons'] = $this->generate_addon_settings();

        // custom fields data
        $this->metadata['custom']['custom-fields'] = $this->generate_custom_fields_data();

        // custom post types data
        $this->metadata['cpt'] = $this->generate_cpt_data();

        return $this->metadata;

    }

    public function generate_custom_redirects_settings(){

        $custom_redirects_option_slugs = [
            'wppb_cr_user',
            'wppb_cr_default_wp_pages',
            'wppb_cr_global',
            'wppb_cr_role',
        ];

        $custom_redirects_data  = [];

        foreach( $custom_redirects_option_slugs as $option_slug ){
            $option = get_option( $option_slug, false );

            $normalized_option_slug = str_replace( 'wppb_cr_', '', $option_slug );

            if( !empty( $option ) ){
                foreach( $option as $slug => $value ){
                    if( !isset( $custom_redirects_data[ $normalized_option_slug ] ) )
                        $custom_redirects_data[ $normalized_option_slug ] = array();
                    
                    $custom_redirects_data[ $normalized_option_slug ][] = $value['type'];
                }
            }
        }

        foreach( $custom_redirects_data as $key => $entry ){
            if( is_array( $entry ) )
                $custom_redirects_data[ $key ] = implode( ',', $entry );
        }
        
        $this->metadata['settings']['wppb_custom_redirects_settings'] = $custom_redirects_data;

    }

    /**
     * Using this to normalize all of the different ways add-ons status is stored
     */
    public function generate_addon_settings(){
        $add_on_option_slugs = [
            'wppb_free_add_ons_settings',
            'wppb_advanced_add_ons_settings',
            'wppb_module_settings',
        ];

        $name_normalization = [
            'userListing'               => 'user-listing',
            'customRedirect'            => 'custom-redirects',
            'multipleEditProfileForms'  => 'multiple-edit-profile-forms',
            'multipleRegistrationForms' => 'multiple-registration-forms',
            'repeaterFields'            => 'repeater-fields',
            'fileRestriction'           => 'files-restriction',
        ];   

        $add_ons = [];

        foreach( $add_on_option_slugs as $option_slug ){
            $option = get_option( $option_slug, false );

            if( !empty( $option ) ){
                foreach( $option as $slug => $value ){
                    
                    if( ( is_bool( $value ) && $value == true ) || $value == 'show' ){
                        $slug = str_replace( 'wppb_', '', $slug );

                        if( isset( $name_normalization[$slug] ) )
                            $slug = $name_normalization[$slug];

                        $add_ons[ str_replace( 'wppb_', '', $slug ) ] = true;
                    }
                }
            }
        }

        // Email Customizer was moved to a free add-on at some point, remove it from the array if we find it
        if( isset( $add_ons['emailCustomizer'] ) )
            unset( $add_ons['emailCustomizer'] );

        if( isset( $add_ons['emailCustomizerAdmin'] ) )
            unset( $add_ons['emailCustomizerAdmin'] );

        // Add content restriction integrations as active add-ons if they have restrictions
        if( !empty( $this->metadata['custom']['content_restriction'] ) ) {
            // Elementor integration
            if( !empty( $this->metadata['custom']['content_restriction']['elementor_restrictions'] ) ) {
                $add_ons['elementor-integration'] = true;
            }

            // Gutenberg integration
            if( !empty( $this->metadata['custom']['content_restriction']['blocks_restrictions'] ) ) {
                $add_ons['gutenberg-integration'] = true;
            }

            // Divi integration
            if( !empty( $this->metadata['custom']['content_restriction']['divi_restrictions'] ) ) {
                $add_ons['divi-integration'] = true;
            }

            // WooCommerce integration
            if( !empty( $this->metadata['custom']['content_restriction']['woocommerce_restrictions'] ) && 
                ( !empty( $this->metadata['custom']['content_restriction']['woocommerce_restrictions']['view'] ) || 
                  !empty( $this->metadata['custom']['content_restriction']['woocommerce_restrictions']['purchase'] ) ) 
            ) {
                $add_ons['woocommerce-integration'] = true;
            }
        }

        return $add_ons;
    }

    public function generate_custom_fields_data(){

        $manage_fields = get_option( 'wppb_manage_fields', false );

        if( empty( $manage_fields ) )
            return '';

        $whitelisted_settings = [
            'field'                          => '',
            'required'                       => '',
            'overwrite-existing'             => '',
            'conditional-logic-enabled'      => '',
            'conditional-logic'              => '',
            'edit-profile-approved-by-admin' => '',
            'visibility'                     => '',
            'user-role-visibility'           => '',
            'location-visibility'            => '',
            'simple-upload'                  => '',
            'recaptcha-type'                 => '',
            'captcha-pb-forms'               => '',
            'captcha-wp-forms'               => '',
        ];

        $custom_fields = [];

        foreach( $manage_fields as $field ){

            // Fix for recaptcha & turnstile settings being present on every field
            if( $field['field'] != 'reCAPTCHA' ){
                unset( $field['recaptcha-type'] );
                unset( $field['captcha-pb-forms'] );
                unset( $field['captcha-wp-forms'] );
            }
            if( $field['field'] != 'Turnstile' ){
                unset( $field['theme'] );
                unset( $field['turnstile-site-key'] );
                unset( $field['turnstile-secret-key'] );
                unset( $field['turnstile-pb-forms'] );
                unset( $field['turnstile-wp-forms'] );
            }

            $custom_fields[] = array_filter( array_intersect_key( $field, $whitelisted_settings ) );

        }

        return $custom_fields;

    }

    public function generate_cpt_data(){

        // Userlistings
	    $userlistings = get_posts( 
            array( 'posts_per_page' => -1, 'post_status' =>'publish', 'post_type' => 'wppb-ul-cpt', 'orderby' => 'post_date', 'order' => 'ASC', 'fields' => 'ID' ) 
        );

        $cpt_data = [];

        if( !empty( $userlistings ) ){
            foreach( $userlistings as $userlisting ){

                $data = get_post_meta( $userlisting->ID, 'wppb_ul_page_settings', true );

                if( !empty( $data[0] ) )
                    $data = $data[0];
                else
                    $data = [];

                $faceted_settings = get_post_meta( $userlisting->ID, 'wppb_ul_faceted_settings', true );

                if( !empty( $faceted_settings ) )
                    $data['facets'] = $faceted_settings;

                $active_ul_theme = get_post_meta( $userlisting->ID, 'wppb-ul-active-theme', true );

                if( !empty( $active_ul_theme ) )
                    $data['theme'] = $active_ul_theme;

                $cpt_data['userlistings'] = $data;

            }
        }

        // Registration Forms
        $registration_forms = get_posts( 
            array( 'posts_per_page' => -1, 'post_status' => 'publish' , 'post_type' => 'wppb-rf-cpt', 'fields' => 'ID' ) 
        );

        if( !empty( $registration_forms ) ){
            foreach( $registration_forms as $form ) {

                $data = get_post_meta( $form->ID, 'wppb_rf_page_settings', true );

                if( !empty( $data[0] ) )
                    $data = $data[0];
                else
                    $data = [];

                $form_fields = get_post_meta( $form->ID, 'wppb_rf_fields', true );

                if( !empty( $form_fields ) )
                    $data['fields'] = $form_fields;

                $form_social_connect_active = get_post_meta( $form->ID, 'wppb_sc_rf_epf_active', true );

                if( !empty( $form_social_connect_active ) )
                    $data['social_connect_active'] = $form_social_connect_active;

                $form_msf_settings = get_post_meta( $form->ID, 'wppb_msf_post_options', true );

                if( !empty( $form_msf_settings ) )
                    $data['msf_settings'] = $form_msf_settings;

                $cpt_data['rf'] = $data;
            }
        }

        // Edit Profile Forms
        $edit_profile_forms = get_posts( 
            array( 'posts_per_page' => -1, 'post_status' => 'publish' , 'post_type' => 'wppb-epf-cpt', 'fields' => 'ID' ) 
        );

        if( !empty( $edit_profile_forms ) ){
            foreach( $edit_profile_forms as $form ) {

                $data = get_post_meta( $form->ID, 'wppb_epf_page_settings', true );

                if( !empty( $data[0] ) )
                    $data = $data[0];
                else
                    $data = [];

                $form_fields = get_post_meta( $form->ID, 'wppb_epf_fields', true );

                if( !empty( $form_fields ) )
                    $data['fields'] = $form_fields;

                $form_social_connect_active = get_post_meta( $form->ID, 'wppb_sc_rf_epf_active', true );

                if( !empty( $form_social_connect_active ) )
                    $data['social_connect_active'] = $form_social_connect_active;

                $form_msf_settings = get_post_meta( $form->ID, 'wppb_msf_post_options', true );

                if( !empty( $form_msf_settings ) )
                    $data['msf_settings'] = $form_msf_settings;

                $cpt_data['epf'] = $data;
            }
        }

        return $cpt_data;

    }

    public function generate_content_restriction_data(){

        $restriction_data = [
            'post_restrictions'        => 0,
            'elementor_restrictions'   => 0,
            'divi_restrictions'        => 0,
            'blocks_restrictions'      => 0,
            'woocommerce_restrictions' => [
                'view'     => 0,
                'purchase' => 0
            ]
        ];

        // Count post/page/cpt restrictions
        global $wpdb;
        $restriction_data['post_restrictions'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT a.post_id) 
            FROM {$wpdb->postmeta} a
            INNER JOIN {$wpdb->posts} b ON a.post_id = b.ID 
            WHERE b.post_type != 'revision'
            AND ( ( a.meta_key = 'wppb-content-restrict-user-status' AND a.meta_value = 'loggedin' )
            OR ( a.meta_key = 'wppb-content-restrict-user-role' AND a.meta_value IS NOT NULL ) ) LIMIT 100"
        );
        

        // Count Elementor widget restrictions if Elementor is active
        if( did_action( 'elementor/loaded' ) ) {

            /**
             * Searching for:
             * 
             * "wppb_restriction_loggedin_users":"yes"
             * "wppb_restriction_loggedout_users":"yes"
             * "wppb_restriction_user_roles":["
             */
            $elementor_posts = $wpdb->get_results(
                "SELECT a.post_id, a.meta_value 
                FROM {$wpdb->postmeta} a
                INNER JOIN {$wpdb->posts} b ON a.post_id = b.ID
                WHERE b.post_type != 'revision'
                AND a.meta_key = '_elementor_data' 
                AND ( a.meta_value LIKE '%\"wppb_restriction_loggedin_users\":\"yes\"%'
                OR a.meta_value LIKE '%\"wppb_restriction_loggedout_users\":\"yes\"%'
                OR a.meta_value LIKE '%\"wppb_restriction_user_roles\":[\"%') LIMIT 100"
            );

            if( !empty( $elementor_posts ) )
                $restriction_data['elementor_restrictions'] = count($elementor_posts);

        }

        // Check if Divi is active (either theme or plugin)
        if( defined( 'ET_BUILDER_VERSION' ) ) {
            // Count Divi builder restrictions
            $divi_posts = $wpdb->get_results(
                "SELECT ID 
                FROM {$wpdb->posts}
                WHERE post_type != 'revision'
                AND post_content LIKE '%wppb_display_to=\"logged_in\"%'
                OR post_content LIKE '%wppb_display_to=\"not_logged_in\"%' LIMIT 100"
            );

            if( !empty( $divi_posts ) )
                $restriction_data['divi_restrictions'] = count($divi_posts);

        }

        // Check if Gutenberg is available
        if( version_compare( get_bloginfo( 'version' ), '5.0', '>=' ) ) {
            // Count Gutenberg restrictions
            $gutenberg_posts = $wpdb->get_results(
                "SELECT ID 
                FROM {$wpdb->posts}
                WHERE post_type != 'revision'
                AND ( post_content LIKE '%\"display_to\":\"\",\"enable_message_logged_in\"%'
                OR post_content LIKE '%\"display_to\":\"not_logged_in\",\"enable_message_logged_in\"%'
                OR post_content LIKE '%\"wppbContentRestriction\":{\"user_roles\":[\"%' ) LIMIT 100"
            );

            if( !empty( $gutenberg_posts ) )
                $restriction_data['blocks_restrictions'] = count($gutenberg_posts);

        }

        // Count WooCommerce restrictions if WooCommerce is active
        if( class_exists( 'WooCommerce' ) ) {
            // Count view restrictions
            $restriction_data['woocommerce_restrictions']['view'] = $wpdb->get_var(
                "SELECT COUNT(DISTINCT a.post_id) 
                FROM {$wpdb->postmeta} a
                INNER JOIN {$wpdb->posts} b ON a.post_id = b.ID 
                WHERE b.post_type = 'product'
                AND ( ( a.meta_key = 'wppb-content-restrict-user-status' AND a.meta_value = 'loggedin' )
                OR ( a.meta_key = 'wppb-content-restrict-user-role' AND a.meta_value IS NOT NULL ) ) LIMIT 100"
            );

            // Count purchase restrictions
            $restriction_data['woocommerce_restrictions']['purchase'] = $wpdb->get_var(
                "SELECT COUNT(DISTINCT a.post_id) 
                FROM {$wpdb->postmeta} a
                INNER JOIN {$wpdb->posts} b ON a.post_id = b.ID 
                WHERE b.post_type = 'product'
                AND ( ( a.meta_key = 'wppb-purchase-restrict-user-status' AND a.meta_value = 'loggedin' )
                OR ( a.meta_key = 'wppb-purchase-restrict-user-role' AND a.meta_value IS NOT NULL ) ) LIMIT 100"
            );
        }

        return $restriction_data;

    }

}

new Cozmoslabs_Plugin_Optin_Metadata_Builder_WPPB();