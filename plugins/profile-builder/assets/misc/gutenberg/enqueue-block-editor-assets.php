<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
    'enqueue_block_assets',
    function () {

        if ( !is_admin() ) {
            return;
        }

        // Enqueue necessary assets for the Editor interface
        wp_enqueue_style('wppb_block_stylesheet', WPPB_PLUGIN_URL . 'assets/misc/gutenberg/blocks/assets/css/gutenberg-blocks.css', PROFILE_BUILDER_VERSION);
        wp_enqueue_style('wppb_stylesheet', WPPB_PLUGIN_URL . 'assets/css/style-front-end.css', array('wp-edit-blocks'), PROFILE_BUILDER_VERSION);

        // load the corresponding Form Design stylesheets
        $active_design = 'form-style-default';
        if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR.'/features/form-designs/form-designs.php' ) )
            $active_design = wppb_get_active_form_design();

        if ( $active_design === 'form-style-default' ) {

            // load stylesheet for the Default Form Style if the active WP Theme is a Block Theme (Block Themes were introduced in WordPress since the 5.9 release)
            if ( version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() )
                wp_enqueue_style('wppb_block_themes_front_end_stylesheet', WPPB_PLUGIN_URL . 'assets/css/style-block-themes-front-end.css', array('wp-edit-blocks'), PROFILE_BUILDER_VERSION);

        }
        else { // if $active_design is other than 'form-style-default' the constants WPPB_PAID_PLUGIN_DIR and WPPB_PAID_PLUGIN_URL are defined (verified at line:14)

            if ( file_exists( WPPB_PAID_PLUGIN_DIR . '/features/form-designs/css/' . $active_design . '/form-design-general-style.css' ) )
                wp_enqueue_style( 'wppb_form_designs_general_style', WPPB_PAID_PLUGIN_URL . 'features/form-designs/css/' . $active_design . '/form-design-general-style.css', array('wp-edit-blocks'),PROFILE_BUILDER_VERSION );

            if ( file_exists( WPPB_PAID_PLUGIN_DIR . '/features/form-designs/css/' . $active_design  .'/extra-form-notifications-style.css' ) )
                wp_enqueue_style( 'wppb_register_success_notification_style', WPPB_PAID_PLUGIN_URL . 'features/form-designs/css/' . $active_design  .'/extra-form-notifications-style.css', array('wp-edit-blocks'),PROFILE_BUILDER_VERSION );
        }

        //Select
        // Don't enqueue when JetEngine is active
        if( !class_exists( 'Jet_Engine' ) ){
            //Select2
            wp_enqueue_script('wppb_select2_js', WPPB_PLUGIN_URL . 'assets/js/select2/select2.min.js', array('jquery'), PROFILE_BUILDER_VERSION);
            wp_enqueue_style('wppb_select2_css', WPPB_PLUGIN_URL . 'assets/css/select2/select2.min.css', array(), PROFILE_BUILDER_VERSION);
        }


        //Upload
        wp_enqueue_style('wppb-upload-css', WPPB_PLUGIN_URL . 'front-end/default-fields/upload/upload.css', false, PROFILE_BUILDER_VERSION);

        if ( defined( 'WPPB_PAID_PLUGIN_URL' ) ) {
            //Select CPT
            wp_enqueue_style('wppb-select-cpt-style', WPPB_PAID_PLUGIN_URL . 'front-end/extra-fields/select-cpt/style-front-end.css', array(), PROFILE_BUILDER_VERSION);

            //Select Taxonomy
            wp_enqueue_style('wppb-select-taxonomy-style', WPPB_PAID_PLUGIN_URL . 'front-end/extra-fields/select-taxonomy/select-taxonomy-style.css', array(), PROFILE_BUILDER_VERSION);

            //Multi-Step Forms compatibility
            wp_enqueue_style('wppb-msf-style-frontend', WPPB_PAID_PLUGIN_URL . 'add-ons-advanced/multi-step-forms/assets/css/frontend-multi-step-forms.css', array(), PROFILE_BUILDER_VERSION);

            //Social Connect
            wp_enqueue_style('wppb-sc-frontend-style', WPPB_PAID_PLUGIN_URL . 'add-ons-advanced/social-connect/assets/css/wppb_sc_main_frontend.css', false, PROFILE_BUILDER_VERSION);

            if ( in_array( PROFILE_BUILDER, ['Profile Builder Pro', 'Profile Builder Agency', 'Profile Builder Unlimited'] ) ) {
                //Userlisting
                wp_enqueue_script('wppb-userlisting-js', WPPB_PAID_PLUGIN_URL . 'add-ons/user-listing/userlisting.js', array('jquery', 'jquery-touch-punch'), PROFILE_BUILDER_VERSION, true);
                wp_localize_script('wppb-userlisting-js', 'wppb_userlisting_obj', array('pageSlug' => wppb_get_users_pagination_slug()));
                wp_enqueue_style('wppb-ul-slider-css', WPPB_PAID_PLUGIN_URL . 'add-ons/user-listing/jquery-ui-slider.min.css', array(), PROFILE_BUILDER_VERSION);
                wp_enqueue_script('jquery-ui-slider');
            }
        }
    }
);

add_action(
    'enqueue_block_editor_assets',
    function () {
        global $content_restriction_activated;
        global $wp_version;

        global $pagenow;

        $arrDeps = ($pagenow === 'widgets.php') ?
            array( 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-widgets', 'lodash', ) :
            array( 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post'   , 'lodash', );


        //Register the Block Content Restriction assets
        if ( $content_restriction_activated == 'yes' && version_compare( $wp_version, "5.0.0", ">=" ) ) {
            wp_register_script(
                'wppb-block-editor-assets-content-restriction',
                WPPB_PLUGIN_URL . 'assets/misc/gutenberg/block-content-restriction/build/index.js',
                $arrDeps,
                PROFILE_BUILDER_VERSION
            );
            wp_enqueue_script('wppb-block-editor-assets-content-restriction');

            if (!function_exists('get_editable_roles')) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
            }

            $user_roles_initial = get_editable_roles();

            foreach ($user_roles_initial as $key => $role) {
                $user_roles[] = [
                    "slug" => $key,
                    "name" => $role['name'],
                ];
            }

            $vars_array = array(
                'userRoles' => json_encode($user_roles),
                'content_restriction_activated' => json_encode($content_restriction_activated == 'yes'),
            );

            wp_localize_script('wppb-block-editor-assets-content-restriction', 'wppbBlockEditorData', $vars_array);
        }

        // Disable pointer events for our forms inside the Editing interface
        $style = '.wppb-block-container{ pointer-events: none; }';
        wp_add_inline_style( 'wp-block-library', $style );
    }
);

add_action(
    'init',
    function () {
        global $content_restriction_activated;
        global $wp_version;

        //Register the Content Restriction Start and Content Restriction End blocks
        if ( $content_restriction_activated == 'yes' && version_compare( $wp_version, "5.0.0", ">=" ) ) {
            if( file_exists( WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/build/content-restriction-start' ) )
                register_block_type( WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/build/content-restriction-start' );
            if( file_exists( WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/build/content-restriction-end' ) )
                register_block_type( WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/build/content-restriction-end' );
        }
        //Register the shortcode blocks
        if ( version_compare( $wp_version, "5.0.0", ">=" ) ) {
            if( file_exists( WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/edit-profile.php' ) )
                include_once WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/edit-profile.php' ;
            if( file_exists( WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/login.php' ) )
                include_once WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/login.php' ;
            if( file_exists( WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/recover-password.php' ) )
                include_once WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/recover-password.php' ;
            if( file_exists( WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/register.php' ) )
                include_once WPPB_PLUGIN_DIR . 'assets/misc/gutenberg/blocks/register.php' ;
            if( defined( 'WPPB_PAID_PLUGIN_URL' ) && wppb_check_if_add_on_is_active( 'wppb_userListing' ) && file_exists( WPPB_PAID_PLUGIN_DIR . '/add-ons/user-listing/assets/misc/gutenberg/blocks/user-listing.php' ) )
                include_once( WPPB_PAID_PLUGIN_DIR . '/add-ons/user-listing/assets/misc/gutenberg/blocks/user-listing.php' );
        }
    }
);

function register_layout_category( $categories ) {

    $categories[] = array(
        'slug'  => 'wppb-block',
        'title' => 'Profile Builder'
    );

    return $categories;
}

if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
    add_filter( 'block_categories_all', 'register_layout_category' );
} else {
    add_filter( 'block_categories', 'register_layout_category' );
}
