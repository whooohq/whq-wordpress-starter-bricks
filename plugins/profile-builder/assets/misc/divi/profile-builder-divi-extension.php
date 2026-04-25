<?php
/*
Plugin Name: Profile Builder Divi Extension
Plugin URI:  https://wordpress.org/plugins/profile-builder/
Description: Profile Builder is the all in one user profile and user registration plugin for WordPress.
Version:     1.0.0
Author:      Cozmoslabs
Author URI:  https://www.cozmoslabs.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wppb-profile-builder-divi-extension
Domain Path: /languages

Profile Builder Divi Extension is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Profile Builder Divi Extension is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Profile Builder Divi Extension. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/


if ( ! function_exists( 'wppb_initialize_extension' ) ):

add_action( 'divi_extensions_init', 'wppb_divi_initialize_extension' );

/**
 * Creates the extension's main class instance.
 *
 * @since 1.0.0
 */
function wppb_divi_initialize_extension() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/ProfileBuilderDiviExtension.php';

}

add_action( 'wp_ajax_wppb_divi_extension_ajax', 'wppb_divi_extension_ajax' );

function wppb_divi_extension_ajax() {
    check_ajax_referer( 'pb_divi_render_form', 'pb_nonce' );

    if( !current_user_can( 'manage_options' ) )
        die();


    if ( is_array( $_POST ) && array_key_exists( 'form_type', $_POST ) && $_POST['form_type'] !== '' ) {
        $output = '';

        switch ($_POST['form_type']) {
            case 'rf':
                include_once(WPPB_PLUGIN_DIR . '/front-end/register.php');
                include_once(WPPB_PLUGIN_DIR . '/front-end/class-formbuilder.php');

                $form_name = 'unspecified';
                if ( array_key_exists('form_name', $_POST)) {
                    $form_name = sanitize_text_field($_POST['form_name']);
                    if ($form_name === 'default') {
                        $form_name = 'unspecified';
                    }
                }

                $atts['role1'] = pb_divi_parse_role( $_POST['role'] );

                if (!$form_name || $form_name === 'unspecified') {
                    $atts = [
                        'role'                => array_key_exists('role', $_POST)                                                                     ? sanitize_text_field($_POST['role'])               : '',
                        'form_name'           => '',
                        'redirect_url'        => array_key_exists('redirect_url', $_POST)           && $_POST['redirect_url']           !== 'default' ? pb_divi_parse_url($_POST['redirect_url'])        : '',
                        'logout_redirect_url' => array_key_exists('logout_redirect_url', $_POST)    && $_POST['logout_redirect_url']    !== 'default' ? pb_divi_parse_url($_POST['logout_redirect_url']) : '',
                        'automatic_login'     => array_key_exists('toggle_automatic_login', $_POST) && $_POST['toggle_automatic_login']               ? 'yes'                                  : '',
                    ];
                } else {
                    $atts = [
                        'role'                => '',
                        'form_name'           => $form_name,
                        'redirect_url'        => '',
                        'logout_redirect_url' => array_key_exists('logout_redirect_url', $_POST) && $_POST['logout_redirect_url'] !== 'default' ? pb_divi_parse_url($_POST['logout_redirect_url']) : '',
                        'automatic_login'     => '',
                    ];
                }

                $output =
                    '<div class="wppb-divi-editor-container">' .
                    wppb_front_end_register( $atts ) .
                    '</div>';

                break;

            case 'epf':
                include_once(WPPB_PLUGIN_DIR . '/front-end/edit-profile.php');
                include_once(WPPB_PLUGIN_DIR . '/front-end/class-formbuilder.php');

                $form_name = 'unspecified';
                if ( array_key_exists('form_name', $_POST)) {
                    $form_name = sanitize_text_field($_POST['form_name']);
                    if ($form_name === 'default') {
                        $form_name = 'unspecified';
                    }
                }

                $atts = [
                    'form_name'    => $form_name,
                    'redirect_url' => array_key_exists('redirect_url', $_POST) && $_POST['redirect_url'] !== 'default' ? pb_divi_parse_url($_POST['redirect_url']) : '',
                ];

                $output =
                    '<div class="wppb-divi-editor-container">' .
                    wppb_front_end_profile_info( $atts ) .
                    '</div>';

                break;

            case 'l':
                include_once(WPPB_PLUGIN_DIR . '/front-end/login.php');

                $atts = [
                    'register_url'        => array_key_exists('register_url', $_POST)        && $_POST['register_url']        !== 'default' ? pb_divi_parse_url($_POST['register_url'])        : '',
                    'lostpassword_url'    => array_key_exists('lostpassword_url', $_POST)    && $_POST['lostpassword_url']    !== 'default' ? pb_divi_parse_url($_POST['lostpassword_url'])    : '',
                    'redirect_url'        => array_key_exists('redirect_url', $_POST)        && $_POST['redirect_url']        !== 'default' ? pb_divi_parse_url($_POST['redirect_url'])        : '',
                    'logout_redirect_url' => array_key_exists('logout_redirect_url', $_POST) && $_POST['logout_redirect_url'] !== 'default' ? pb_divi_parse_url($_POST['logout_redirect_url']) : '',
                    'show_2fa_field'      => array_key_exists('toggle_auth_field', $_POST)   && $_POST['toggle_auth_field']   === 'on'      ? 'yes'                                  : '',
                    'block'               => 'true',
                ];

                $output =
                    '<div class="wppb-divi-editor-container">' .
                    wppb_front_end_login( $atts ) .
                    '</div>';

                break;

            case 'rp':
                include_once(WPPB_PLUGIN_DIR . '/front-end/recover.php');

                $atts = [
                    'block'               => 'true',
                ];

                $output =
                    '<div class="wppb-divi-editor-container">' .
                    wppb_front_end_password_recovery( $atts ) .
                    '</div>';

                break;

            case 'ul':
                if( defined( 'WPPB_PAID_PLUGIN_DIR' ) ) {
                    include_once( WPPB_PAID_PLUGIN_DIR.'/add-ons/user-listing/userlisting.php' );

                    $atts = [
                        'name'       => array_key_exists('userlisting_name', $_POST) ?  sanitize_text_field($_POST['userlisting_name'])                            : '',
                        'meta_value' => array_key_exists('field_name', $_POST) && array_key_exists('meta_value', $_POST) && $_POST['field_name'] !== 'default' ? ( $_POST['meta_value'] !== 'undefined' ? sanitize_text_field($_POST['meta_value']) : '' ) : '',
                        '0'          => array_key_exists('single', $_POST) && $_POST['single'] === 'on'                ? 'single'                                  : '',
                        'id'         => array_key_exists('id', $_POST)         && $_POST['id']         !== 'undefined' ? absint($_POST['id'])                      : '',
                        'meta_key'   => array_key_exists('field_name', $_POST) && $_POST['field_name'] !== 'default'   ? sanitize_text_field($_POST['field_name']) : '',
                        'include'    => array_key_exists('include', $_POST)    && $_POST['include']    !== 'undefined' ? pb_divi_parse_ids($_POST['include'])      : '',
                        'exclude'    => array_key_exists('exclude', $_POST)    && $_POST['exclude']    !== 'undefined' ? pb_divi_parse_ids($_POST['exclude'])      : '',
                    ];

                    if ( $atts['name'] === '' || $atts['name'] === 'default' ) {
                        $output =wppb_form_notification_styling(
                            '<div class="wppb-divi-editor-container">
                                <p class="wppb-alert">
                                    Please select a User Listing!
                                </p><!-- .wppb-alert-->
                             </div>');
                    } else {
                        $output =
                            '<div class="wppb-divi-editor-container">' .
                            wppb_user_listing_shortcode( $atts ) .
                            '</div>';
                    }
                }

                break;
        }

        $output .=
            '<style type="text/css">' .
            file_get_contents( WPPB_PLUGIN_DIR . '/assets/css/style-front-end.css' ) .
            '</style>';

        // load the corresponding Form Design stylesheets
        $active_design = 'form-style-default';
        if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR.'/features/form-designs/form-designs.php' ) )
            $active_design = wppb_get_active_form_design();

        if ( $active_design === 'form-style-default' ) {

            // load stylesheet for the Default Form Style if the active WP Theme is a Block Theme (Block Themes were introduced in WordPress since the 5.9 release)
            if ( version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() )
                $output .=
                    '<style type="text/css">' .
                    file_get_contents( WPPB_PLUGIN_DIR . '/assets/css/style-block-themes-front-end.css' ) .
                    '</style>';

        }
        else { // if $active_design is other than 'form-style-default' the constants WPPB_PAID_PLUGIN_DIR and WPPB_PAID_PLUGIN_URL are defined (verified at line:14)

            if ( file_exists( WPPB_PAID_PLUGIN_DIR . '/features/form-designs/css/' . $active_design . '/form-design-general-style.css' ) )
                $output .=
                    '<style type="text/css">' .
                    file_get_contents( WPPB_PLUGIN_DIR . '/features/form-designs/css/' . $active_design . '/form-design-general-style.css' ) .
                    '</style>';

            if ( file_exists( WPPB_PAID_PLUGIN_DIR . '/features/form-designs/css/' . $active_design  .'/extra-form-notifications-style.css' ) )
                $output .=
                    '<style type="text/css">' .
                    file_get_contents( WPPB_PLUGIN_DIR . '/features/form-designs/css/' . $active_design  .'/extra-form-notifications-style.css' ) .
                    '</style>';
        }

        //Select
        // Don't enqueue when JetEngine is active
        if( !class_exists( 'Jet_Engine' ) ) {
            $output .=
                '<script type="text/javascript">' .
                file_get_contents( WPPB_PLUGIN_DIR . '/assets/js/select2/select2.min.js' ) .
                '</script>';
            $output .=
                '<style type="text/css">' .
                file_get_contents( WPPB_PLUGIN_DIR . '/assets/css/select2/select2.min.css' ) .
                '</style>';
        }

        if ( defined( 'WPPB_PAID_PLUGIN_URL' ) ) {
            //Select2
            $output .=
                '<style type="text/css">' .
                file_get_contents( WPPB_PAID_PLUGIN_DIR . '/front-end/default-fields/select2/select2.css' ) .
                '</style>';
            $output .=
                '<style type="text/css">' .
                file_get_contents( WPPB_PAID_PLUGIN_DIR . '/front-end/extra-fields/select-cpt/style-front-end.css' ) .
                '</style>';

            //Upload
            $output .=
                '<style type="text/css">' .
                file_get_contents( WPPB_PAID_PLUGIN_DIR . '/front-end/extra-fields/upload/upload.css' ) .
                '</style>';

            //Multi-Step Forms compatibility
            $output .=
                '<style type="text/css">' .
                file_get_contents( WPPB_PAID_PLUGIN_DIR . '/add-ons-advanced/multi-step-forms/assets/css/frontend-multi-step-forms.css' ) .
                '</style>';

            //Social Connect
            $output .=
                '<style type="text/css">' .
                file_get_contents( WPPB_PAID_PLUGIN_DIR . '/add-ons-advanced/social-connect/assets/css/wppb_sc_main_frontend.css' ) .
                '</style>';
        }

        echo json_encode( $output );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        die();
    }

    die();
}

$wppb_generalSettings = get_option( 'wppb_general_settings', 'not_found' );
$wppb_content_restriction_settings = get_option( 'wppb_content_restriction_settings', 'not_found' );
if( $wppb_generalSettings != 'not_found' || $wppb_content_restriction_settings != 'not_found' ) {
    global $content_restriction_activated;
    $content_restriction_activated = 'no';
    if( !empty( $wppb_content_restriction_settings['contentRestriction'] ) ){
        $content_restriction_activated = $wppb_content_restriction_settings['contentRestriction'];
    }
    elseif( !empty( $wppb_generalSettings['contentRestriction'] ) ){
        $content_restriction_activated = $wppb_generalSettings['contentRestriction'];
    }
}
global $wp_version;
if ( isset( $content_restriction_activated ) && $content_restriction_activated == 'yes' && version_compare( $wp_version, "5.0.0", ">=" ) ) {
    add_filter( 'et_builder_get_parent_modules', 'wppb_divi_content_restriction_extend_modules' );
    add_filter( 'et_module_shortcode_output', 'wppb_divi_content_restriction_render_section', 10, 3 );
}

/**
 * Add the content restriction toggle and fields on all modules
 */
function wppb_divi_content_restriction_extend_modules( $modules ) {

    static $is_applied = false;
    if ( $is_applied ) {
        return $modules;
    }

    if ( empty( $modules ) ) {
        return $modules;
    }

    foreach ( $modules as $module_slug => $module ) {
        if ( ! isset( $module->settings_modal_toggles ) ||
            ! isset( $module->fields_unprocessed ) ||
            in_array( $module_slug, array( 'wppb_content_restriction_start', 'wppb_content_restriction_end' ) ) ) {
            continue;
        }

        $toggles_list = $module->settings_modal_toggles;
        // Add a 'PB Content Restriction' toggle on the 'Advanced' tab
        if ( isset( $toggles_list['custom_css'] ) && ! empty( $toggles_list['custom_css']['toggles'] ) ) {
            $toggles_list['custom_css']['toggles']['wppb_content_restriction_toggle'] = array(
                'title'    => esc_html__( 'PB Content Restriction', 'profile-builder' ),
                'priority' => 220,
            );
            $module->settings_modal_toggles = $toggles_list;
        }

        $fields_list = $module->fields_unprocessed;
        // Add content restriction options in the toggle
        if ( ! empty( $fields_list ) ) {
            $module->fields_unprocessed = wppb_divi_content_restriction_get_fields_list ( $fields_list );
        }
    }
    $is_applied = true;
    return $modules;
}

function wppb_divi_content_restriction_render_section( $output, $render_slug, $module ) {

    if ( is_array( $output ) ) {
        return $output;
    }

    if ('et_pb_column' === $render_slug) {
        return $output;
    }

    static $show_message = false;

    if ( !isset( $content_restriction_module_pair_active ) ) {
        static $content_restriction_module_pair_active = false;
    }
    if ( !isset( $content_restriction_module_pair_settings ) ) {
        static $content_restriction_module_pair_settings = array();
    }

    if ( $render_slug === 'wppb_content_restriction_start' ) {
        $content_restriction_module_pair_active = true;
        $content_restriction_module_pair_settings = $module->get_attrs_unprocessed();
        return;
    }
    if ( $render_slug === 'wppb_content_restriction_end' ) {
        if ( $content_restriction_module_pair_active ) {
            $content_restriction_module_pair_active = false;
            $aux = $content_restriction_module_pair_settings;
            $content_restriction_module_pair_settings = array();
            return wppb_divi_content_restriction_process_shortcode( wppb_divi_content_restriction_get_attrs( $aux ), '', isset( $aux['wppb_toggle_message'] ) && $aux['wppb_toggle_message'] === 'on' );
        } else {
            $content_restriction_module_pair_active = false;
            $content_restriction_module_pair_settings = array();
            return;
        }
    }

    if ( $content_restriction_module_pair_active ) {
        return wppb_divi_content_restriction_process_shortcode( wppb_divi_content_restriction_get_attrs( $content_restriction_module_pair_settings ), $output, false );
    }

    $attrs_unprocessed = $module->get_attrs_unprocessed();

    if ( isset( $attrs_unprocessed['wppb_display_to'] ) && $attrs_unprocessed['wppb_display_to'] !== 'all' ) {
        return wppb_divi_content_restriction_process_shortcode( wppb_divi_content_restriction_get_attrs( $attrs_unprocessed ), $output, isset( $attrs_unprocessed['wppb_toggle_message'] ) && $attrs_unprocessed['wppb_toggle_message'] === 'on' );
    }

    return $output;
}

function wppb_divi_content_restriction_process_shortcode ( $attrs, $output, $show_message = true ) {
    if ( $show_message ){
        $output = wppb_content_restriction_shortcode( $attrs, $output );
    } else {
        add_filter( 'wppb_content_restriction_shortcode_message', 'wppb_divi_content_restriction_filter_no_message');
        $output = wppb_content_restriction_shortcode( $attrs, $output );
        remove_filter( 'wppb_content_restriction_shortcode_message', 'wppb_divi_content_restriction_filter_no_message');
    }
    return $output;
}

function wppb_divi_content_restriction_filter_no_message () {
    return;
}

function wppb_divi_content_restriction_get_attrs ( $attrs_unprocessed ) {
    return array(
        'user_roles'    => isset( $attrs_unprocessed['wppb_user_roles'] ) ? $attrs_unprocessed['wppb_user_roles'] : array(),
        'display_to'    => $attrs_unprocessed['wppb_display_to'] === 'logged_in' ? '' : $attrs_unprocessed['wppb_display_to'],
        'message'       => isset( $attrs_unprocessed['wppb_toggle_custom_message'] ) && $attrs_unprocessed['wppb_toggle_custom_message'] === 'on'
            ? ( $attrs_unprocessed['wppb_display_to'] === 'not_logged_in' ? $attrs_unprocessed['wppb_message_logged_out'] : $attrs_unprocessed['wppb_message_logged_in'] )
            : '',
        'users_id'      => $attrs_unprocessed['wppb_display_to'] === 'logged_in' ? ( isset( $attrs_unprocessed['wppb_users_ids'] ) ? $attrs_unprocessed['wppb_users_ids'] : '' ) : '',
    );
}

function wppb_divi_content_restriction_get_fields_list ( $fields_list = array() ) {
    if (!function_exists('get_editable_roles')) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
    }
    $user_roles ['default'] = esc_html__( 'All' , 'profile-builder' );
    $editable_roles = get_editable_roles();
    foreach ($editable_roles as $key => $role) {
        $user_roles [$key] = $role['name'];
    }

    $fields_list['wppb_display_to'] = array(
        'label'              => esc_html__( 'Show content to', 'profile-builder' ),
        'description'        => esc_html__( 'The users you wish to see the content.', 'profile-builder' ),
        'type'               => 'select',
        'options'            => array(
            'all'            => esc_html__( 'All', 'profile-builder' ),
            'logged_in'      => esc_html__( 'Logged in', 'profile-builder' ),
            'not_logged_in'  => esc_html__( 'Not logged in', 'profile-builder' ),
        ),
        'default'            => 'all',
        'toggle_slug'        => 'wppb_content_restriction_toggle',
        'tab_slug'           => 'custom_css',
    );
    $fields_list['wppb_user_roles'] = array(
        'label'              => esc_html__( 'User Roles', 'profile-builder' ),
        'description'        => esc_html__( 'The desired valid user roles. Select none for all roles to be valid.', 'profile-builder' ),
        'type'               => 'select',
        'options'            => $user_roles,
        'default'            => 'default',
        'toggle_slug'        => 'wppb_content_restriction_toggle',
        'tab_slug'           => 'custom_css',
        'show_if'            => array(
            'wppb_display_to'     => 'logged_in',
        ),
    );
    $fields_list['wppb_users_ids'] = array(
        'label'              => esc_html__( 'User IDs', 'profile-builder' ),
        'description'        => esc_html__( 'A comma-separated list of user IDs.', 'profile-builder' ),
        'type'               => 'text',
        'toggle_slug'        => 'wppb_content_restriction_toggle',
        'tab_slug'           => 'custom_css',
        'show_if'            => array(
            'wppb_display_to'     => 'logged_in',
        ),
    );
    $fields_list['wppb_toggle_message'] = array(
        'label'              => esc_html__( 'Enable Message', 'profile-builder' ),
        'description'        => esc_html__( 'Show the Message defined in the Profile Builder Settings.', 'profile-builder' ),
        'type'               => 'yes_no_button',
        'options'            => array(
            'on'             => esc_html__( 'Yes', 'profile-builder'),
            'off'            => esc_html__( 'No', 'profile-builder'),
        ),
        'toggle_slug'        => 'wppb_content_restriction_toggle',
        'tab_slug'           => 'custom_css',
        'show_if_not'        => array(
            'wppb_display_to'     => 'all',
        ),
    );
    $fields_list['wppb_toggle_custom_message'] = array(
        'label'              => esc_html__( 'Custom Message', 'profile-builder' ),
        'description'        => esc_html__( 'Enable Custom Message.', 'profile-builder' ),
        'type'               => 'yes_no_button',
        'options'            => array(
            'on'             => esc_html__( 'Yes', 'profile-builder'),
            'off'            => esc_html__( 'No', 'profile-builder'),
        ),
        'toggle_slug'        => 'wppb_content_restriction_toggle',
        'tab_slug'           => 'custom_css',
        'show_if_not'        => array(
            'wppb_display_to'     => 'all',
        ),
        'show_if'            => array(
            'wppb_toggle_message' => 'on',
        ),
    );
    $fields_list['wppb_message_logged_in'] = array(
        'label'              => esc_html__( 'Custom message', 'profile-builder' ),
        'description'        => esc_html__( 'Enter the custom message you wish the restricted users to see.', 'profile-builder' ),
        'type'               => 'text',
        'toggle_slug'        => 'wppb_content_restriction_toggle',
        'tab_slug'           => 'custom_css',
        'show_if'            => array(
            'wppb_toggle_message'        => 'on',
            'wppb_toggle_custom_message' => 'on',
            'wppb_display_to'            => 'logged_in',
        ),
    );
    $fields_list['wppb_message_logged_out'] = array(
        'label'              => esc_html__( 'Custom message', 'profile-builder' ),
        'description'        => esc_html__( 'Custom message for logged-out users.', 'profile-builder' ),
        'type'               => 'text',
        'toggle_slug'        => 'wppb_content_restriction_toggle',
        'tab_slug'           => 'custom_css',
        'show_if'            => array(
            'wppb_toggle_message'        => 'on',
            'wppb_toggle_custom_message' => 'on',
            'wppb_display_to'            => 'not_logged_in',
        ),
    );
    return $fields_list;
}

function pb_divi_parse_role( $role ){

    $roles = explode( ',', $roles );

    if( empty( $roles ) )
        return '';

    $result = '';

    foreach( $roles as $role ){
        $result .= absint( $role ) . ',';
    }

    return rtrim( $result, ',' );

}

function pb_divi_parse_ids( $attrs ){

    $attrs = explode( ',', $attrs );

    if( empty( $attrs ) )
        return '';

    $result = '';

    foreach( $attrs as $attr ){
        $result .= absint( $attr ) . ',';
    }

    return rtrim( $result, ',' );

}

function pb_divi_parse_url( $redirect_url ){

    if( $redirect_url === esc_url_raw( $redirect_url ) )
        return esc_url_raw( $redirect_url );

    return '';

}

endif;
