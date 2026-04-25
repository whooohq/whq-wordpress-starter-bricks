<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WPPB_Setup_Wizard {
    private $step              = '';
    private $steps             = array();
    public  $general_settings  = array();
    public  $user_pages  = array();

    public function __construct() {
        if( apply_filters( 'wppb_run_setup_wizard', true ) && current_user_can( 'manage_options' ) ){
            add_action( 'admin_menu', array( $this, 'add_page' ) );
            add_action( 'admin_head', array( $this, 'hide_page_from_dashboard' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
            add_filter( 'wppb_output_dashboard_setup_wizard', array( $this, 'setup_wizard' ) );
            add_action( 'admin_init', array( $this, 'redirect_to_setup' ) );
            add_action( 'admin_init', array( $this, 'save_data' ) );
            //add_action( 'admin_init', array( $this, 'set_existing_user_pages' ) );
            add_action( 'wp_ajax_dismiss_setup_wizard_newsletter_subscribe', array( $this, 'dismiss_setup_wizard_newsletter_subscribe' ) );
        }
    }

    public function add_page() {
        add_dashboard_page( '', '', 'manage_options', 'wppb-setup', '' );
    }

    public function hide_page_from_dashboard() {
        remove_submenu_page( 'index.php', 'wppb-setup' );
    }

    public function enqueue_scripts_and_styles() {
        if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'wppb-setup' ) {
            wp_enqueue_style( 'wppb-setup-wizard', WPPB_PLUGIN_URL . 'assets/css/style-setup-wizard.css', array(), PROFILE_BUILDER_VERSION );
            wp_enqueue_script( 'wppb-wizard-js', WPPB_PLUGIN_URL . 'assets/js/setup-wizard.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog' ), PROFILE_BUILDER_VERSION );
        }
    }

    public function get_default_steps(){
        return array(
            'user-pages' => __( 'User Pages', 'profile-builder' ),
            'general'    => __( 'Design & UI', 'profile-builder' ),
            'addons'     => __( 'Add-Ons', 'profile-builder' ),
            'next'       => __( 'Ready!', 'profile-builder' ),
        );
    }

    public function redirect_to_setup(){
        $run_setup = get_transient( 'wppb_run_setup_wizard' );

        if( $run_setup == true ){
            delete_transient( 'wppb_run_setup_wizard' );
            wp_safe_redirect( admin_url( 'admin.php?page=profile-builder-dashboard&subpage=wppb-setup' ) );
            die();
        }
    }

    public function setup_wizard() {
        if( empty( $_GET['page'] ) || $_GET['page'] != 'profile-builder-dashboard' )
            return;

        if( empty( $_GET['subpage'] ) || $_GET['subpage'] != 'wppb-setup' )
            return;

        $this->general_settings  = get_option( 'wppb_general_settings', array() );
        $this->user_pages  = get_option( 'wppb_user_pages', array() );

        $default_steps = $this->get_default_steps();

        reset( $default_steps );

        $this->steps = apply_filters( 'wppb_setup_wizard_steps', $default_steps );

        $step        = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : key( $default_steps );
        $valid_steps = array_keys( $this->steps );

        if ( !in_array( $step, $valid_steps ) ) {
            $step = 'user-pages'; // default
        }
        
        $this->step  = $step;

        include_once 'setup-wizard/view-page-setup-wizard.php';

        exit;
    }

    public function save_data() {
        if( empty( $_POST['wppb_setup_wizard_nonce'] ) )
            return;

        check_admin_referer( 'wppb-setup-wizard-nonce', 'wppb_setup_wizard_nonce' );

        if( !current_user_can( 'manage_options' ) )
            return;

        $default_steps = $this->get_default_steps();

        reset( $default_steps );

        $this->steps = apply_filters( 'wppb_setup_wizard_steps', $default_steps );
        $this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : key( $default_steps );

        // save data
        if( $this->step === 'user-pages' ) {

            if( !empty( $_POST['wppb_user_pages'] ) ) {

                $pages = array(
                    'register' => array(
                        'title'         => 'Register',
                        'option'        => 'register_page',
                        'content'       => '[wppb-register]',
                        'block_content' => '<!-- wp:wppb/register /-->',
                    ),
                    'login' => array(
                        'title'         => 'Login',
                        'option'        => 'login_page',
                        'content'       => '[wppb-login]',
                        'block_content' => '<!-- wp:wppb/login /-->',
                    ),
                    'edit_profile' => array(
                        'title'         => 'Edit Profile',
                        'option'        => 'edit_profile_page',
                        'content'       => '[wppb-edit-profile]',
                        'block_content' => '<!-- wp:wppb/edit-profile /-->',
                    ),
                    'reset_password' => array(
                        'title'         => 'Password Reset',
                        'option'        => 'lost_password_page',
                        'content'       => '[wppb-recover-password]',
                        'block_content' => '<!-- wp:wppb/recover-password /-->',
                    ),
                );


                foreach( $_POST['wppb_user_pages'] as $page_slug => $value ) { /* phpcs:ignore  WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */
                    if( $value == 1 ){
                        $this->create_page( $pages[$page_slug]['option'], $pages[$page_slug]['title'], $pages[$page_slug]['content'], $pages[$page_slug]['block_content'] );
                    }
                }

                update_option( 'wppb_user_pages', $this->user_pages );

            }

        } elseif( $this->step === 'general' ) {

            $general_settings = get_option( 'wppb_general_settings', array() );

            // Form Design
            if ( isset( $_POST['wppb_general_settings'] ) && !empty( $_POST['wppb_general_settings']['formsDesign'] ) )
                $general_settings['formsDesign'] = sanitize_text_field( $_POST['wppb_general_settings']['formsDesign'] );
            else $general_settings['formsDesign'] = 'form-style-default';

            // Automatically Log-in
            if( isset( $_POST['automaticallyLogIn'] ) )
                $general_settings['automaticallyLogIn'] = sanitize_text_field( $_POST['automaticallyLogIn'] );
            else
                unset( $general_settings['automaticallyLogIn'] );

            // Hide Admin Bar For Subscriber Role
            if( isset( $_POST['hide_admin_bar_for_subscriber'] ) ) {

                if ( empty( $general_settings['hide_admin_bar_for'] ) && !is_array( $general_settings['hide_admin_bar_for'] ) ) {
                    $general_settings['hide_admin_bar_for'] = array();
                }

                if ( empty( $general_settings['hide_admin_bar_for'] ) || !in_array( 'Subscriber', $general_settings['hide_admin_bar_for'] ) )
                    $general_settings['hide_admin_bar_for'][] = 'Subscriber';

            } elseif ( !empty( $general_settings['hide_admin_bar_for'] ) && in_array( 'Subscriber', $general_settings['hide_admin_bar_for'] ) ) {

                $subscriber_key = array_search('Subscriber', $general_settings['hide_admin_bar_for']);
                unset( $general_settings['hide_admin_bar_for'][$subscriber_key] );

            }

            // Email Confirmation After Registration
            if( isset( $_POST['emailConfirmation'] ) )
                $general_settings['emailConfirmation'] = sanitize_text_field( $_POST['emailConfirmation'] );
            else
                unset( $general_settings['emailConfirmation'] );

            // Admin Approval
            if( isset( $_POST['adminApproval'] ) )
                $general_settings['adminApproval'] = sanitize_text_field( $_POST['adminApproval'] );
            else
                unset( $general_settings['adminApproval'] );

            if( !empty( $general_settings ) )
                update_option( 'wppb_general_settings', $general_settings );

        } elseif( $this->step === 'addons' ) {
            $pro_addons = get_option( 'wppb_module_settings', 'not_found' );

            // User Listing Addon
            if( isset( $_POST['wppb_userListing'] ) )
                $pro_addons['wppb_userListing'] = 'show';
            else $pro_addons['wppb_userListing'] = 'hide';

            // Custom Redirects Addon
            if( isset( $_POST['wppb_customRedirect'] ) )
                $pro_addons['wppb_customRedirect'] = 'show';
            else $pro_addons['wppb_customRedirect'] = 'hide';

            update_option( 'wppb_module_settings', $pro_addons );


            $basic_addons = get_option( 'wppb_advanced_add_ons_settings', array() );

            // Multi Step Form Addon
            if( isset( $_POST['multi-step-forms'] ) )
                $basic_addons['multi-step-forms'] = true;
            else $basic_addons['multi-step-forms'] = false;

            // Social Connect Addon
            if( isset( $_POST['social-connect'] ) )
                $basic_addons['social-connect'] = true;
            else $basic_addons['social-connect'] = false;

            update_option( 'wppb_advanced_add_ons_settings', $basic_addons );

        }

        // step completion for setup
        $steps_completion = $this->get_completed_progress_steps();

        if( !empty( $this->step ) ){
            if( empty( $steps_completion ) ){

                $steps_completion = array(
                    $this->step => 1,
                );

            } else {

                $steps_completion[$this->step] = 1;

            }
        }

        update_option( 'wppb_setup_wizard_steps', $steps_completion, false );

        // redirect to the next step at the end
        wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
        exit;
    }

    public static function get_completed_progress_steps() {
        $steps = get_option( 'wppb_setup_wizard_steps', array() );

        return is_array( $steps ) ? $steps : array();
    }

    private function get_next_step_link( $step = '' ) {
        if( !$step )
            $step = $this->step;

        $keys = array_keys( $this->steps );

        if( end( $keys ) === $step )
            return admin_url();

        $step_index = array_search( $step, $keys, true );

        if( $step_index === false )
            return '';

        return add_query_arg( 'step', $keys[$step_index + 1] );
    }

    /**
     * Check if Gutenberg block editor is available and active
     * 
     * @return bool
     */
    private function is_gutenberg_available() {
        // use_block_editor_for_post_type() checks if block editor is available (WP 5.0+),
        // respects Classic Editor plugin settings, and any filters
        return function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( 'page' );
    }

    private function create_page( $option, $title, $content = '', $block_content = '' ) {
        if( empty( $this->user_pages ) )
            $this->user_pages = get_option( 'wppb_user_pages', array() );

        //try to find an existing page with the shortcode or block
        if( empty( $this->user_pages[$option] ) || $this->user_pages[$option] == '-1' ) {

            if( !empty( $content ) ){
                global $wpdb;

                // Clean shortcode for searching (remove wp:shortcode wrapper if present)
                $shortcode = str_replace( array( '<!-- wp:shortcode -->', '<!-- /wp:shortcode -->' ), '', $content );
                
                // Search for existing page with shortcode
                $existing_page = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", '%' . $shortcode . '%' ) );

                // If no shortcode page found, and we have block content, search for block
                if( empty( $existing_page ) && !empty( $block_content ) ) {
                    $existing_page = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", '%' . $wpdb->esc_like( $block_content ) . '%' ) );
                }

                if( !empty( $existing_page ) ) {
                    $this->user_pages[$option] = $existing_page;

                    return $existing_page;
                }
            }

            // Determine which content to use: blocks if available, otherwise shortcodes
            $page_content = $content;
            if( $this->is_gutenberg_available() && !empty( $block_content ) ) {
                $page_content = $block_content;
            }

            $page = array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_content' => $page_content
            );

            $page_id = wp_insert_post( $page );
            $this->user_pages[$option] = $page_id;
        }
    }

    public function set_existing_user_pages() {

        if( !current_user_can( 'manage_options' ) )
            return;

        $user_pages = get_option( 'wppb_user_pages', array() );

        $pages = array(
            'register' => array(
                'title'         => 'Register',
                'option'        => 'register_page',
                'content'       => '[wppb-register]',
                'block_content' => '<!-- wp:wppb/register /-->',
            ),
            'login' => array(
                'title'         => 'Login',
                'option'        => 'login_page',
                'content'       => '[wppb-login]',
                'block_content' => '<!-- wp:wppb/login /-->',
            ),
            'edit_profile' => array(
                'title'         => 'Edit Profile',
                'option'        => 'edit_profile_page',
                'content'       => '[wppb-edit-profile]',
                'block_content' => '<!-- wp:wppb/edit-profile /-->',
            ),
            'reset_password' => array(
                'title'         => 'Password Reset',
                'option'        => 'lost_password_page',
                'content'       => '[wppb-recover-password]',
                'block_content' => '<!-- wp:wppb/recover-password /-->',
            ),
        );

        global $wpdb;
        foreach ( $pages as $page ) {
            if( empty( $user_pages[$page['option']] ) && !empty( $page['content'] ) ){
                // Search for shortcode-based pages
                $shortcode = str_replace( array( '<!-- wp:shortcode -->', '<!-- /wp:shortcode -->' ), '', $page['content'] );
                $existing_page = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", '%' . $shortcode . '%' ) );

                // If no shortcode page found, search for block-based pages
                if( empty( $existing_page ) && !empty( $page['block_content'] ) ) {
                    $existing_page = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", '%' . $wpdb->esc_like( $page['block_content'] ) . '%' ) );
                }

                if( !empty( $existing_page ) ) {
                    $user_pages[$page['option']] = $existing_page;
                }
            }
        }

        if ( !empty( $user_pages ) )
            update_option( 'wppb_user_pages', $user_pages );

        $this->user_pages = get_option( 'wppb_user_pages', array() );

    }

    public static function get_progress_steps() {

        $wppb_generalSettings = get_option( 'wppb_general_settings', 'not_found' );
        $roles_editor_enabled = ( $wppb_generalSettings !== 'not_found' && isset( $wppb_generalSettings['rolesEditor'] ) && !empty( $wppb_generalSettings['rolesEditor'] ) && $wppb_generalSettings['rolesEditor'] === 'yes' );

        if ( $roles_editor_enabled ) {
            $roles_editor_url = admin_url( 'edit.php?post_type=wppb-roles-editor' );
        } else {
            // URL with nonce for activating Roles Editor
            $roles_editor_url = wp_nonce_url( admin_url( 'admin.php?action=wppb_enable_roles_editor' ), 'wppb_enable_roles_editor_nonce', 'wppb_nonce' );
        }

        $progress_steps = array(
            'user-pages'         => array(
                'label' => __( 'Create user pages for registration, login, edit profile and password reset.', 'profile-builder' ),
                'url'   => admin_url( 'admin.php?page=profile-builder-dashboard&subpage=wppb-setup' ),
            ),
            'general'            => array(
                'label' => __( 'Choose a design and optimize the login and registration flow for your users.', 'profile-builder' ),
                'url'   => admin_url( 'admin.php?page=profile-builder-dashboard&subpage=wppb-setup&step=general' ),
            ),
            'addons'           => array(
                'label' => __( 'Learn about and enable addons for extra functionality.', 'profile-builder' ),
                'url'   => admin_url( 'admin.php?page=profile-builder-dashboard&subpage=wppb-setup&step=addons' ),
            ),
            'extra_form_field' => array(
                'label' => __( 'Add extra fields to the registration and edit profile forms.', 'profile-builder' ),
                'url'   => admin_url( 'admin.php?page=manage-fields#manage-fields' ),
            ),
            'restrict_content'   => array(
                'label'  => __( 'Restrict your content based on the user role.', 'profile-builder' ),
                'url'    => admin_url( 'admin.php?page=profile-builder-content_restriction' ),
            ),
            'extra_user_roles'   => array(
                'label'  => __( 'Create new user roles with the Role Editor.', 'profile-builder' ),
                'url'    => $roles_editor_url,
            ),
        );

        return $progress_steps;
    }

    public static function output_progress_steps() {
        $steps            = self::get_progress_steps();
        $steps_completion = self::get_completed_progress_steps();

        // User Pages and General Settings Completion
        if( !isset( $steps_completion['user-pages'] ) && self::website_has_plugin_pages() ){
            $steps_completion['user-pages'] = 1;
            $steps_completion['general']    = 1;
        }

        // Addons Completion
        if( !isset( $steps_completion['addons'] ) && self::website_has_active_addons() )
            $steps_completion['addons'] = 1;

        // Extra Form Field Completion
        if( !isset( $steps_completion['extra_form_field'] ) && self::website_edited_form_fields() )
            $steps_completion['extra_form_field'] = 1;

        // Restrict Content Completion
        if( !isset( $steps_completion['restrict_content'] ) && self::website_has_restricted_content() )
            $steps_completion['restrict_content'] = 1;

        // User Roles Completion
        if( !isset( $steps_completion['extra_user_roles'] ) && self::website_has_extra_user_roles() )
            $steps_completion['extra_user_roles'] = 1;

        update_option( 'wppb_setup_wizard_steps', $steps_completion, false );

        $current_step = is_array( $steps_completion ) ? count( $steps_completion ) : 0;
        $total_steps  = count( $steps );

        ob_start(); ?>

        <div class="wppb-setup-progress">
            <h3><?php esc_html_e( 'Progress Review', 'profile-builder' ); ?></h3>
            <p><?php printf( esc_html__( 'Follow these steps to start registering users on your website. %1s out of %2s complete.', 'profile-builder' ), esc_html( $current_step ), esc_html( $total_steps ) ); ?></p>

            <div class="wppb-setup-progress__bar">
                <?php foreach( $steps as $slug => $step ) : ?>
                    <div class="item <?php echo isset( $steps_completion[$slug] ) && $steps_completion[$slug] == 1 ? 'completed' : ''; ?>"></div>
                <?php endforeach; ?>
            </div>

            <div class="wppb-setup-progress__steps">
                <?php foreach( $steps as $slug => $step ) : ?>
                    <a class="wppb-setup-progress__step <?php echo isset( $steps_completion[$slug] ) && $steps_completion[$slug] == 1 ? 'completed' : ''; ?>" href="<?php echo esc_url( $step['url'] ) ?>" target="<?php echo isset( $step['target'] ) ? esc_html( $step['target'] ) : '' ?>">
                        <?php echo esc_html( $step['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
        $output = ob_get_clean();

        echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public static function website_has_plugin_pages() {
        global $wpdb;

        // Check for both shortcode and block-based pages
        $search_patterns = array(
            '[wppb-register]',
            '[wppb-login]',
            '[wppb-edit-profile]',
            '[wppb-recover-password]',
            '<!-- wp:wppb/register',
            '<!-- wp:wppb/login',
            '<!-- wp:wppb/edit-profile',
            '<!-- wp:wppb/recover-password',
        );

        foreach ( $search_patterns as $pattern ) {
            $existing_page = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", '%' . $wpdb->esc_like( $pattern ) . '%' ) );

            if( !empty( $existing_page ) )
                return true;
        }

        return false;
    }

    public static function website_has_active_addons() {
        $free_addons = get_option( 'wppb_free_add_ons_settings', array() );
        $pro_addons = get_option( 'wppb_module_settings', 'not_found' );
        $basic_addons = get_option( 'wppb_advanced_add_ons_settings', array() );

        $all_addons = array_merge( $free_addons, $pro_addons, $basic_addons );

        foreach ( $all_addons as $addon => $value ) {
            if( $value === true || $value === 'show' )
                return true;
        }

        return false;
    }

    public static function website_edited_form_fields() {
        $default_fields = 	array(
            'Default - Name (Heading)',
            'Default - Contact Info (Heading)',
            'Default - About Yourself (Heading)',
            'Default - Username',
            'Default - First Name',
            'Default - Last Name',
            'Default - Nickname',
            'Default - E-mail',
            'Default - Website',
            'Default - Password',
            'Default - Repeat Password',
            'Default - Biographical Info',
            'Default - Display name publicly as',
        );

        $wppb_manage_fields = get_option ( 'wppb_manage_fields', 'not_set' );


        if ( empty( $wppb_manage_fields ) || count( $default_fields ) !== count( $wppb_manage_fields ) )
            return true;

        foreach ( $wppb_manage_fields as $field ) {
            if ( !in_array( $field['field'], $default_fields ) )
                return true;
        }

        return false;
    }

    public static function website_has_restricted_content() {
        $args = [
            'posts_per_page' => '1',
            'post_type'      => array( 'post', 'page' ),
            'meta_query'     => [
                [
                    'key'     => 'wppb-content-restrict-user-role',
                    'compare' => 'EXISTS'
                ]
            ],
        ];

        $result = new WP_Query( $args );

        if( $result->have_posts() )
            return true;

        // Logged in meta
        $args = [
            'posts_per_page' => '1',
            'post_type'      => array( 'post', 'page' ),
            'meta_query'     => [
                [
                    'key'     => 'wppb-content-restrict-user-status',
                    'compare' => 'EXISTS'
                ]
            ],
        ];

        $result = new WP_Query( $args );

        if( $result->have_posts() )
            return true;

        return false;
    }

    public static function website_has_extra_user_roles() {
        global $wp_roles;

        $user_roles = $wp_roles->roles;
        $default_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

        foreach ( $user_roles as $slug => $details ) {
            if ( !in_array( $slug, $default_roles ) )
                return true;
        }

        return false;
    }

    public function dismiss_setup_wizard_newsletter_subscribe() {

        check_ajax_referer( 'dismiss_setup_wizard_newsletter_subscribe', 'wppb_nonce' );

        $user_id = get_current_user_id();

        if( !empty( $user_id ) )
            update_user_meta( $user_id, 'wppb_setup_wizard_newsletter', 1 );

        wp_die();

    }

}

new WPPB_Setup_Wizard();
