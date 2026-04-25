<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Function which returns the same field-format over and over again.
 *
 * @since v.2.0
 *
 * @param string $field
 * @param string $field_title
 *
 * @return string
 */
function wppb_field_format ( $field_title, $field ){

	return trim( $field_title ).' ( '.trim( $field ).' )';
}

/**
 * Add a notification for either the Username or the Email field letting the user know that, even though it is there, it won't do anything
 *
 * @since v.2.0
 *
 * @param string $form
 * @param integer $id
 * @param string $value
 *
 * @return string $form
 */

function wppb_manage_fields_display_field_title_slug( $form ){
    // add a notice to fields
	global $wppb_results_field;
    switch ($wppb_results_field){
        case 'Default - Username':
            $wppb_generalSettings = get_option( 'wppb_general_settings', 'not_found' );
            if ( $wppb_generalSettings != 'not_found' && $wppb_generalSettings['loginWith'] == 'email' ) {
                $form .= '<div id="wppb-login-email-nag" class="wppb-backend-notice">' . sprintf(__('Login is set to be done using the Email. This field will NOT appear in the front-end! ( you can change these settings under the "%s" tab )', 'profile-builder'), '<a href="' . admin_url('admin.php?page=profile-builder-general-settings') . '" target="_blank">' . __('General Settings', 'profile-builder') . '</a>') . '</div>';
            }
            break;
        case 'Default - Display name publicly as':
            $form .= '<div id="wppb-display-name-nag" class="wppb-backend-notice">' . __( 'Display name publicly as - only appears on the Edit Profile page!', 'profile-builder' ) . '</div>';
            break;
        case 'Default - Blog Details':
            $form .= '<div id="wppb-blog-details-nag" class="wppb-backend-notice">' . __( 'Blog Details - only appears on the Registration page!', 'profile-builder' ) . '</div>';
            break;
    }

    return $form;
}

add_filter( 'wck_after_content_element', 'wppb_manage_fields_display_field_title_slug' );

/**
 * Check if field type is 'Default - Display name publicly as' so we can add a notification for it
 *
 * @since v.2.2
 *
 * @param string $wck_element_class
 * @param string $meta
 * @param array $results
 * @param integer $element_id
 *
 */
function wppb_manage_fields_display_name_notice( $wck_element_class, $meta, $results, $element_id ) {
	global $wppb_results_field;

	$wppb_results_field = $results[$element_id]['field'];
}
add_filter( 'wck_element_class_wppb_manage_fields', 'wppb_manage_fields_display_name_notice', 10, 4 );

/**
 * Function that adds a custom class to the existing container
 *
 * @since v.2.0
 *
 * @param string $update_container_class - the new class name
 * @param string $meta - the name of the meta
 * @param array $results
 * @param integer $element_id - the ID of the element
 *
 * @return string
 */
function wppb_update_container_class( $update_container_class, $meta, $results, $element_id ) {
	$wppb_element_type = Wordpress_Creation_Kit_PB::wck_generate_slug( $results[$element_id]["field"] );

	return "class='wck_update_container update_container_$meta update_container_$wppb_element_type element_type_$wppb_element_type'";
}
add_filter( 'wck_update_container_class_wppb_manage_fields', 'wppb_update_container_class', 10, 4 );

/**
 * Function that adds a custom class to the existing element
 *
 * @since v.2.0
 *
 * @param string $element_class - the new class name
 * @param string $meta - the name of the meta
 * @param array $results
 * @param integer $element_id - the ID of the element
 *
 * @return string
 */
function wppb_element_class( $element_class, $meta, $results, $element_id ){
	$wppb_element_type = Wordpress_Creation_Kit_PB::wck_generate_slug( $results[$element_id]["field"] );

	return "class='element_type_$wppb_element_type added_fields_list'";
}
add_filter( 'wck_element_class_wppb_manage_fields', 'wppb_element_class', 10, 4 );

/**
 * Functions to check password length and strength
 *
 * @since v.2.0
 */
/* on add user and update profile from WP admin area */
add_action( 'user_profile_update_errors', 'wppb_password_check_on_profile_update', 0, 3 );
function wppb_password_check_on_profile_update( $errors, $update, $user ){
    wppb_password_check_extra_conditions( $errors, $user );
}

/* on reset password */
add_action( 'validate_password_reset', 'wppb_password_check_extra_conditions', 10, 2 );
function wppb_password_check_extra_conditions( $errors, $user ){
    $password = ( isset( $_POST[ 'pass1' ] ) && trim( $_POST[ 'pass1' ] ) ) ? $_POST[ 'pass1' ] : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

    if( $password && ( !isset( $_POST['pw_weak'] ) || $_POST['pw_weak'] != 'on' ) ){
        $wppb_generalSettings = get_option( 'wppb_general_settings' );

        if( !empty( $wppb_generalSettings['minimum_password_length'] ) ){
            if( strlen( $password ) < $wppb_generalSettings['minimum_password_length'] )
                $errors->add( 'pass', sprintf( __( '<strong>ERROR:</strong> The password must have the minimum length of %s characters', 'profile-builder' ), $wppb_generalSettings['minimum_password_length'] ) );
        }

        if( isset( $_POST['wppb_password_strength'] ) && !empty( $wppb_generalSettings['minimum_password_strength'] ) ){
            $password_strength_array = array( 'short' => 0, 'bad' => 1, 'good' => 2, 'strong' => 3 );
            $password_strength_text = array( 'short' => __( 'Very weak', 'profile-builder' ), 'bad' => __( 'Weak', 'profile-builder' ), 'good' => __( 'Medium', 'profile-builder' ), 'strong' => __( 'Strong', 'profile-builder' ) );

            foreach( $password_strength_text as $psr_key => $psr_text ){
                if( $psr_text == sanitize_text_field( $_POST['wppb_password_strength'] ) ){
                    $password_strength_result_slug = $psr_key;
                    break;
                }
            }

            if( !empty( $password_strength_result_slug ) ){
                if( $password_strength_array[$password_strength_result_slug] < $password_strength_array[$wppb_generalSettings['minimum_password_strength']] )
                    $errors->add( 'pass', sprintf( __( '<strong>ERROR:</strong> The password must have a minimum strength of %s', 'profile-builder' ), $password_strength_text[$wppb_generalSettings['minimum_password_strength']] ) );
            }
        }
    }

    return $errors;
}

/* we need to create a hidden field that contains the results of the password strength from the js strength tester */
add_action( 'admin_footer', 'wppb_add_hidden_password_strength_on_backend' );
add_action( 'login_footer', 'wppb_add_hidden_password_strength_on_backend' );
function wppb_add_hidden_password_strength_on_backend(){
    if( $GLOBALS['pagenow'] == 'profile.php' || $GLOBALS['pagenow'] == 'user-new.php' || ( $GLOBALS['pagenow'] == 'wp-login.php' && isset( $_GET['action'] ) && ( $_GET['action'] === 'rp' || $_GET['action'] === 'resetpass' ) ) ){
        $wppb_generalSettings = get_option( 'wppb_general_settings' );
        if( !empty( $wppb_generalSettings['minimum_password_strength'] ) ){
            ?>
            <script type="text/javascript">
                jQuery( document ).ready( function() {
                    var passswordStrengthResult = jQuery( '#pass-strength-result' );
                    // Check for password strength meter
                    if ( passswordStrengthResult.length ) {
                        // Attach submit event to form
                        passswordStrengthResult.parents( 'form' ).on( 'submit', function() {
                            // Store check results in hidden field
                            jQuery( this ).append( '<input type="hidden" name="wppb_password_strength" value="' + passswordStrengthResult.text() + '">' );
                        });
                    }
                });
            </script>
            <?php
        }
    }
}

/* Modify the Add Entry buttons for WCK metaboxes according to context */
add_filter( 'wck_add_entry_button', 'wppb_change_add_entry_button', 10, 2 );
function wppb_change_add_entry_button( $string, $meta ){
    if( $meta == 'wppb_manage_fields' || $meta == 'wppb_epf_fields' || $meta == 'wppb_rf_fields' ){
        return __( "Add Field", 'profile-builder' );
    }elseif( $meta == 'wppb_epf_page_settings' || $meta == 'wppb_rf_page_settings' || $meta == 'wppb_ul_page_settings' ){
        return __( "Save Settings", 'profile-builder' );
    }

    return $string;
}

/**
 * add links on plugin page
 */
add_filter( 'plugin_action_links', 'wppb_plugin_action_links', 10, 2 );
function wppb_plugin_action_links( $links, $file ) {
    if ( $file != WPPB_PLUGIN_BASENAME ) {
        return $links;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return $links;
    }

    $settings_link = sprintf( '<a href="%1$s">%2$s</a>',
        menu_page_url( 'profile-builder-general-settings', false ),
        esc_html( __( 'Settings', 'profile-builder' ) ) );

    array_unshift( $links, $settings_link );

    return $links;
}

/**
 * add links on plugin page
 */
add_filter( 'plugin_row_meta', 'wppb_plugin_row_meta', 10, 2 );
function wppb_plugin_row_meta( $links, $file ) {
    if ( WPPB_PLUGIN_BASENAME == $file ) {
        $row_meta = array(
            'docs'        => '<a href="' . esc_url( 'https://www.cozmoslabs.com/docs/profile-builder/' ) . '" target="_blank" aria-label="' . esc_attr__( 'View Profile Builder documentation', 'profile-builder' ) . '">' . esc_html__( 'Docs', 'profile-builder' ) . '</a>',
            'get_support' => '<a href="' . esc_url( apply_filters( 'wppb_docs_url', 'https://wordpress.org/support/plugin/profile-builder/' ) ) . '" title="' . esc_attr( __( 'Get Support', 'profile-builder' ) ) . '" target="_blank">' . __( 'Get Support', 'profile-builder' ) . '</a>',
        );

        return array_merge( $links, $row_meta );
    }

    return (array) $links;
}

function wppb_sync_api( $action ) {

    $base = 'https://usagetracker.cozmoslabs.com';
    $url  = $base . '/syncPlugin';
    $body = array(
        'unique_identifier' => hash( 'sha256', home_url( '', 'https' ) ),
        'product'           => 'wppb',
        'action'            => $action,
    );

    $body = apply_filters( 'wppb_sync_api_body', $body, $action );

    wp_remote_post( $url, array(
        'body'     => $body,
        'timeout'  => 3,
        'blocking' => false,
    ) );

}

function wppb_handle_plugin_activation(){

    $general_settings = get_option( 'wppb_general_settings' );

    if( empty( $general_settings ) || !is_array( $general_settings ) || empty( $general_settings['extraFieldsLayout'] ) ){
        wppb_sync_api( 'start' );
    }

}

function wppb_handle_plugin_deactivation(){
    wppb_sync_api( 'end' );
}

/**
 * Add the stored deactivation reason to the sync payload
 *
 * @param array  $body   Sync request body
 * @param string $action Sync action slug
 *
 * @return array
 */
function wppb_add_deactivation_reason_to_sync_body( $body, $action ) {

    if ( $action !== 'end' )
        return $body;

    $reason_data = get_option( 'wppb_deactivation_reason', array() );

    if ( ! is_array( $reason_data ) )
        $reason_data = array();

    if ( empty( $reason_data['reason'] ) )
        $reason_data['reason'] = 'skip';

    $body['reason'] = sanitize_key( $reason_data['reason'] );

    $reason_key = $reason_data['reason'] . '_reason';

    if ( ! empty( $reason_data[ $reason_key ] ) )
        $body['extra_metadata'] = sanitize_text_field( $reason_data[ $reason_key ] );

    delete_option( 'wppb_deactivation_reason' );

    return $body;
}
add_filter( 'wppb_sync_api_body', 'wppb_add_deactivation_reason_to_sync_body', 10, 2 );

/**
 * Store the deactivation reason selected in the popup
 */
function wppb_store_deactivation_reason() {

    if ( ! check_ajax_referer( 'wppb_deactivation_reason', 'nonce', false ) )
        wp_send_json_error( array( 'message' => __( 'We could not verify your request. Please refresh the page and try again.', 'profile-builder' ) ), 403 );

    if ( ! current_user_can( 'activate_plugins' ) )
        wp_send_json_error( array( 'message' => __( 'You do not have permission to deactivate plugins on this site.', 'profile-builder' ) ), 403 );

    $valid_reasons = array(
        'dont_need_it',
        'switched_to_another_plugin',
        'missing_features',
        'did_not_work',
        'temporary_deactivation',
        'other',
        'skip',
    );

    $reason = '';

    if ( isset( $_POST['reason'] ) )
        $reason = sanitize_key( wp_unslash( $_POST['reason'] ) );

    if ( ! in_array( $reason, $valid_reasons, true ) )
        wp_send_json_error( array( 'message' => __( 'We could not save your deactivation feedback. Please try again.', 'profile-builder' ) ), 400 );

    $reason_data = array(
        'reason' => $reason,
    );

    if ( $reason === 'switched_to_another_plugin' && ! empty( $_POST['switched_to_another_plugin_reason'] ) )
        $reason_data['switched_to_another_plugin_reason'] = sanitize_text_field( wp_unslash( $_POST['switched_to_another_plugin_reason'] ) );

    if ( $reason === 'missing_features' && ! empty( $_POST['missing_features_reason'] ) )
        $reason_data['missing_features_reason'] = sanitize_text_field( wp_unslash( $_POST['missing_features_reason'] ) );

    if ( $reason === 'other' && ! empty( $_POST['other_reason'] ) )
        $reason_data['other_reason'] = sanitize_text_field( wp_unslash( $_POST['other_reason'] ) );

    update_option( 'wppb_deactivation_reason', $reason_data, false );

    wp_send_json_success();
}
add_action( 'wp_ajax_wppb_store_deactivation_reason', 'wppb_store_deactivation_reason' );

/* hook to create pages for out forms when a user press the create pages/setup button */
add_action( 'admin_init', 'wppb_create_form_pages' );
function wppb_create_form_pages(){

    if( !isset( $_GET['page'] ) || $_GET['page'] != 'profile-builder-basic-info' || !isset( $_GET['wppb_create_pages'] ) || $_GET['wppb_create_pages'] != 'true' )
        return;

    if( !isset( $_GET['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'wppb_create_pages' ) )
        return;

    if( !current_user_can( 'manage_options' ) )
        return;

    $wppb_pages_created = get_option( 'wppb_pages_created' );

    if( empty( $wppb_pages_created ) || ( isset( $_GET['wppb_force_create_pages'] ) && $_GET['wppb_force_create_pages'] === 'true' ) ) {
        $register_page = array(
            'post_title'   => 'Register',
            'post_content' => '[wppb-register]',
            'post_status'  => 'publish',
            'post_type'    => 'page'
        );
        $register_id = wp_insert_post($register_page);

        $edit_page = array(
            'post_title'   => 'Edit Profile',
            'post_content' => '[wppb-edit-profile]',
            'post_status'  => 'publish',
            'post_type'    => 'page'
        );
        $edit_id = wp_insert_post($edit_page);

        $login_page = array(
            'post_title'   => 'Log In',
            'post_content' => '[wppb-login]',
            'post_status'  => 'publish',
            'post_type'    => 'page'
        );
        $login_id = wp_insert_post($login_page);

        update_option('wppb_pages_created', 'true' );

        wp_safe_redirect( admin_url('edit.php?s=%5Bwppb-&post_status=all&post_type=page&action=-1&m=0&paged=1&action2=-1') );
    }
}

/**
 * Function that prepares labels to pass to the WCK api...we have a quirk in wck api that labels are wrapped
 * in %label%...so if we want to have % inside the label for example 25% off...we will have a bad time
 * @param string $label
 * @return string|string[]
 */
function wppb_prepare_wck_labels( $label ){
    return trim( str_replace( '%', '&#37;', $label ) );
}

/**
 * Function that returns the reserved meta name list
 */
function wppb_get_reserved_meta_name_list( $all_fields, $posted_values ){

    $unique_meta_name_list = array( 'first_name', 'last_name', 'nickname', 'description', 'role' );

    // Default contact methods were removed in WP 3.6. A filter dictates contact methods.
    if ( apply_filters( 'wppb_remove_default_contact_methods', get_site_option( 'initial_db_version' ) < 23588 ) ){
        $unique_meta_name_list[] = 'aim';
        $unique_meta_name_list[] = 'yim';
        $unique_meta_name_list[] = 'jabber';
    }

    $add_reserved = true;

    // source: https://codex.wordpress.org/Reserved_Terms
    $reserved_meta_names = array( 'action', 'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category', 'category__and', 'category__in', 'category__not_in', 'category_name', 'comments_per_page',
        'comments_popup', 'custom', 'customize_messenger_channel', 'customized', 'cpage', 'day', 'debug', 'embed', 'error', 'exact', 'feed', 'fields', 'hour', 'link_category', 'm', 'map', 'minute', 'monthnum', 'more',
        'name', 'nav_menu', 'nonce', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id', 'paged', 'pagename', 'pb', 'perm', 'post', 'post__in', 'post__not_in', 'post_format', 'post_mime_type',
        'post_status', 'post_tag', 'post_type', 'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search', 'second', 'sentence', 'showposts', 'static', 'status', 'subpost',
        'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in', 'tag_id', 'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'terms', 'theme', 'title', 'type', 'types', 'w', 'withcomments', 'withoutcomments', 'year' );

    $args = array(
        'public'   => true,
        '_builtin' => true
    );
    $post_types = get_post_types( $args, 'names', 'or' );
    $taxonomies = get_taxonomies( $args, 'names', 'or' );


    /*reserved meta names were added in PB 3.1.2 so to avoid the situation where someone updates an already existing field
     with a reserved name and gets an error check if it is an update or new field */
    if( !empty( $all_fields ) && !empty($posted_values['id'] ) && !empty($posted_values['meta-name']) ){
        foreach( $all_fields as $field ){
            if( $field['id'] === $posted_values['id'] && $field['meta-name'] === $posted_values['meta-name'] ){//it is an update
                $add_reserved = false;
            }
        }
    }

    if( $add_reserved )
        $unique_meta_name_list = array_merge( $unique_meta_name_list, $reserved_meta_names, $post_types, $taxonomies );

    return apply_filters ( 'wppb_unique_meta_name_list', $unique_meta_name_list );
}

add_action('admin_head', 'wppb_maybe_remove_add_new_button_for_cpt' );
function wppb_maybe_remove_add_new_button_for_cpt() {

    global $pagenow;
    
    $target_slugs    = [ 'wppb-rf-cpt', 'wppb-epf-cpt', 'wppb-ul-cpt' ];
    $current_slug    = '';
    $pointer_content = '';

    $correct_page = false;

    if ( $pagenow === 'edit.php' && isset( $_GET['post_type'] ) && in_array( sanitize_text_field( $_GET['post_type'] ), $target_slugs ) ) {
        $correct_page = true;
        $current_slug = sanitize_text_field( $_GET['post_type'] );
    } else if( $pagenow === 'post.php' && isset( $_GET['post'] ) ) {

        $post_type = get_post_type( absint( $_GET['post'] ) );

        if ( in_array( $post_type, $target_slugs ) ) {
            $correct_page = true;
            $current_slug = $post_type;
        }

    }
    
    if ( $correct_page ) {
        $license_status = wppb_get_serial_number_status();

        if( $current_slug === 'wppb-rf-cpt' ) {
            if( $license_status == 'missing' ) {
                $pointer_content .= '<p>' . sprintf( __( 'Please %1$senter your license key%2$s first, to add new User Registration Forms.', 'profile-builder' ), '<a href="'. admin_url( 'admin.php?page=profile-builder-general-settings' ) .'">', '</a>' ) . '</p>';
            } else {
                $pointer_content .= '<p>' . sprintf( __( 'You need an active license to add new User Registration Forms. %1$sRenew%2$s or %3$spurchase a new one%4$s.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=pb-registration-forms&utm_medium=client-site&utm_campaign=pb-expired-license">', '</a>', '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-registration-forms&utm_medium=client-site&utm_campaign=pb-multi-registration-addon#pricing" target="_blank">', '</a>' ) . '</p>';
            }
        } else if( $current_slug === 'wppb-epf-cpt' ) {
            if( $license_status == 'missing' ) {
                $pointer_content .= '<p>' . sprintf( __( 'Please %1$senter your license key%2$s first, to add new Edit Profile Forms.', 'profile-builder' ), '<a href="'. admin_url( 'admin.php?page=profile-builder-general-settings' ) .'">', '</a>' ) . '</p>';
            } else {
                $pointer_content .= '<p>' . sprintf( __( 'You need an active license to add new Edit Profile Forms. %1$sRenew%2$s or %3$spurchase a new one%4$s.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=pb-edit-profile-forms&utm_medium=client-site&utm_campaign=pb-expired-license">', '</a>', '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-edit-profile-forms&utm_medium=client-site&utm_campaign=pb-multi-edit-profile-addon#pricing" target="_blank">', '</a>' ) . '</p>';
            }
        } else if( $current_slug === 'wppb-ul-cpt' ) {
            if( $license_status == 'missing' ) {
                $pointer_content .= '<p>' . sprintf( __( 'Please %1$senter your license key%2$s first, to add new User Listing.', 'profile-builder' ), '<a href="'. admin_url( 'admin.php?page=profile-builder-general-settings' ) .'">', '</a>' ) . '</p>';
            } else {
                $pointer_content .= '<p>' . sprintf( __( 'You need an active license to add a new User Listing. %1$sRenew%2$s or %3$spurchase a new one%4$s.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=pb-user-listing&utm_medium=client-site&utm_campaign=pb-expired-license">', '</a>', '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-user-listing&utm_medium=client-site&utm_campaign=pb-user-listing-addon#pricing" target="_blank">', '</a>' ) . '</p>';
            }
        }

        if( $license_status !== 'valid' ) {
            wp_enqueue_style('wp-pointer');
            wp_enqueue_script('wp-pointer');

            echo '
            <script>
                jQuery(document).ready(function($) {
                    let button_text = $(".page-title-action").text();
                    $(".page-title-action").remove();

                    $(".wrap .wp-heading-inline").after(`<a class="page-title-action page-title-action-disabled" style="cursor:pointer;">${button_text}</a>`);

                    $(".page-title-action-disabled").on("click", function(e) {
                        e.preventDefault();

                        let pointer_content = '. json_encode( $pointer_content ) .';

                        jQuery( this ).pointer({
                            content: pointer_content,
                            position: { edge: "right", align: "middle" }
                        }).pointer("open");
                    });
                });
            </script>';
        }

    }
    
    // This shows the Add new button. Since the above action removes it completelty, we can just attempt to show it here
    echo '<script>
        jQuery(document).ready(function($) {
            if( $(".page-title-action").length > 0 ) {
                $(".page-title-action").css("display", "inline-flex");
            }
        });
    </script>';

    // Manage fields
    if( isset( $_GET['page'] ) && $_GET['page'] === 'manage-fields' ) {
        $license_status = wppb_get_serial_number_status();
        $pointer_content_cl = '';
        $pointer_content_fv = '';
        $pointer_content_epaa = '';

        if( $license_status == 'missing' ) {
            $pointer_content_cl .= '<p>' . sprintf( __( 'Please %1$senter your license key%2$s first, to use the Conditional Logic feature.', 'profile-builder' ), '<a href="'. admin_url( 'admin.php?page=profile-builder-general-settings' ) .'">', '</a>' ) . '</p>';
            $pointer_content_fv .= '<p>' . sprintf( __( 'Please %1$senter your license key%2$s first, to configure Field Visibility options.', 'profile-builder' ), '<a href="'. admin_url( 'admin.php?page=profile-builder-general-settings' ) .'">', '</a>' ) . '</p>';
            $pointer_content_epaa .= '<p>' . sprintf( __( 'Please %1$senter your license key%2$s first, to use the Edit Profile Updates Approved by Admin addon feature.', 'profile-builder' ), '<a href="'. admin_url( 'admin.php?page=profile-builder-general-settings' ) .'">', '</a>' ) . '</p>';
        } else {
            $pointer_content_cl .= '<p>' . sprintf( __( 'You need an active license to configure the Condi  tional Logic feature. %1$sRenew%2$s or %3$spurchase a new one%4$s.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=pb-form-fields-conditional&utm_medium=client-site&utm_campaign=pb-expired-license">', '</a>', '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-form-fields-conditional&utm_medium=client-site&utm_campaign=pb-conditional-logic#pricing" target="_blank">', '</a>' ) . '</p>';
            $pointer_content_fv .= '<p>' . sprintf( __( 'You need an active license to configure Field Visibility options. %1$sRenew%2$s or %3$spurchase a new one%4$s.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=pb-form-fields-visibility&utm_medium=client-site&utm_campaign=pb-expired-license">', '</a>', '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-form-fields-visibility&utm_medium=client-site&utm_campaign=pb-field-visibility#pricing" target="_blank">', '</a>' ) . '</p>';
            $pointer_content_epaa .= '<p>' . sprintf( __( 'You need an active license to use the Edit Profile Updates Approved by Admin addon. %1$sRenew%2$s or %3$spurchase a new one%4$s.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=pb-form-fields-approval-on-edit-profile&utm_medium=client-site&utm_campaign=pb-expired-license">', '</a>', '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-form-fields-approval-on-edit-profile&utm_medium=client-site&utm_campaign=pb-approval-on-edit-profile#pricing" target="_blank">', '</a>' ) . '</p>';
        }        

        if( $license_status != 'valid' ) {
            wp_enqueue_style('wp-pointer');
            wp_enqueue_script('wp-pointer');

            echo '
            <script>
                jQuery(document).ready(function($) {

                    $(document).on( "wpbFormMetaLoaded", function(e, meta) {

                        wppb_manage_fields_license_invalid( e );

                    });

                    wppb_manage_fields_license_invalid();

                    function wppb_manage_fields_license_invalid(){

                        if( $(".row-conditional-logic-enabled").length > 0 ) {
                        
                            $(".row-conditional-logic").hide();

                            $("#edit_form_conditional-logic-enabled_yes, label[for=conditional-logic-enabled_yes]").on("click", function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();

                                let pointer_content = '. json_encode( $pointer_content_cl ) .';
                                let pointer_target  = jQuery( this ).parent( ".cozmoslabs-toggle-container" );

                                if( e && e.currentTarget && jQuery( e.currentTarget ).hasClass( "cozmoslabs-form-field-label" ) ) {
                                    pointer_target = jQuery( this );
                                }

                                pointer_target.pointer({
                                    content: pointer_content,
                                    position: { edge: "right", align: "middle" }
                                }).pointer("open");
                            });
                        }

                        if( $(".row-edit-profile-approved-by-admin").length > 0 ) {

                            $("#edit_form_edit-profile-approved-by-admin_yes, label[for=edit-profile-approved-by-admin_yes]").on("click", function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();

                                let pointer_content = '. json_encode( $pointer_content_epaa ) .';
                                let pointer_target  = jQuery( this ).parent( ".cozmoslabs-toggle-container" );

                                if( e && e.currentTarget && jQuery( e.currentTarget ).hasClass( "cozmoslabs-form-field-label" ) ) {
                                    pointer_target = jQuery( this );
                                }

                                pointer_target.pointer({
                                    content: pointer_content,
                                    position: { edge: "right", align: "middle" }
                                }).pointer("open");
                            });
                        }

                        if( $(".row-visibility").length > 0 ) {

                            $("select#visibility").on("mousedown", function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();

                                let pointer_content = '. json_encode( $pointer_content_fv ) .';

                                jQuery( this ).pointer({
                                    content: pointer_content,
                                    position: { edge: "right", align: "middle" }
                                }).pointer("open");
                            });

                        }

                        if( $(".row-user-role-visibility").length > 0 ) {

                            $(".row-user-role-visibility .wck-checkboxes input").attr("disabled", true).css("pointer-events", "none");

                            $(".row-user-role-visibility .wck-checkboxes").on("click", function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();

                                let pointer_content = '. json_encode( $pointer_content_fv ) .';

                                jQuery( this ).pointer({
                                    content: pointer_content,
                                    position: { edge: "right", align: "middle" }
                                }).pointer("open");
                            });

                        }

                        if( $(".row-location-visibility").length > 0 ) {

                            $(".row-location-visibility .wck-checkboxes input").attr("disabled", true).css("pointer-events", "none");

                            $(".row-location-visibility .wck-checkboxes").on("click", function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();

                                let pointer_content = '. json_encode( $pointer_content_fv ) .';

                                jQuery( this ).pointer({
                                    content: pointer_content,
                                    position: { edge: "right", align: "middle" }
                                }).pointer("open");
                            });

                        }

                    }

                });
            </script>';
        }
    }

}

function wppb_filter_own_post_creation( $data, $postarr ) {
    if ( in_array( $data['post_type'], [ 'wppb-rf-cpt', 'wppb-epf-cpt', 'wppb-ul-cpt' ] ) && empty( $postarr['ID'] ) ) {

        $license_status = wppb_get_serial_number_status();

        if( $license_status != 'valid' ) {
            wp_die( 'An active Profile Builder license is required to create new posts of this type.' );
        }

    }

    return $data;
}
add_filter( 'wp_insert_post_data', 'wppb_filter_own_post_creation', 10, 2 );

add_filter( 'wck_update_meta_filter_values_wppb_manage_fields', 'wppb_filter_extra_manage_fields_options', 10, 2 );
function wppb_filter_extra_manage_fields_options( $values, $element_id ) {

    $license_status = wppb_get_serial_number_status();

    if( $license_status == 'valid' )
        return $values;

    if( !empty( $values['id'] ) ) {
        $manage_fields = get_option( 'wppb_manage_fields', array() );
        $existing_field = false;

        if( !empty( $manage_fields ) ) {
            foreach( $manage_fields as $field ) {
                if( $field['id'] == $values['id'] ) {
                    $existing_field = $field;
                    break;
                }
            }
        }

        if( !empty( $existing_field ) ) {

            $target_keys = [
                'visibility',
                'location-visibility',
                'user-role-visibility',
                'conditional-logic-enabled',
                'edit-profile-approved-by-admin',
            ];

            foreach( $target_keys as $key ) {
                if( !empty( $existing_field[$key] ) ) {
                    $values[$key] = $existing_field[$key];
                }
            }

        }

    }

    return $values;

}

function wppb_international_telephone_input_admin_notification() {

    if( !current_user_can( 'manage_options' ) )
        return;

    /* initiate the plugin notifications class */
    $notifications = WPPB_Plugin_Notifications::get_instance();
    /* this must be unique */
    $notification_id = 'wppb_international_telephone_input_notification_pb';

    $docs_url = 'https://www.cozmoslabs.com/docs/profile-builder/manage-user-fields/international-telephone-input/';
    $notification_message = '<p style="font-size: 15px; margin-top:4px;">' . __( 'Let users pick their country, see flags and placeholders, and validate numbers in a familiar format.', 'profile-builder' ) . '</p>';

    $docs_link = sprintf( __( '%1$sRead the documentation%2$s', 'profile-builder' ), '<a href="' . esc_url( $docs_url ) . '" target="_blank" rel="noopener noreferrer">', '</a>' );

    $buy_url = 'https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=wpbackend&utm_medium=clientsite&utm_content=international_telephone_input_notification&utm_campaign=PBFree#pricing';

    if( defined( 'WPPB_PAID_PLUGIN_DIR' ) ) {
        $extra_message = sprintf(
            /* translators: 1: documentation link (HTML), 2: opening Form Fields link, 3: closing Form Fields link */
            __( '%1$s to set it up, or add the field under %2$sProfile Builder → Form Fields%3$s.', 'profile-builder' ),
            $docs_link,
            '<a href="' . esc_url( admin_url( 'admin.php?page=manage-fields' ) ) . '">',
            '</a>'
        );
    } else {
        $extra_message = sprintf(
            /* translators: 1: documentation link (HTML), 2: opening upgrade link, 3: closing upgrade link */
            __( '%1$s. This field is available in Profile Builder Basic and Pro. %2$sUpgrade now%3$s to use it.', 'profile-builder' ),
            $docs_link,
            '<a href="' . esc_url( $buy_url ) . '" target="_blank" rel="noopener noreferrer">',
            '</a>'
        );
    }

    $notification_message .= '<p style="font-size: 15px; margin-top:4px; padding-left: 77px;">' . $extra_message . '</p>';

    $ul_icon_url = ( file_exists( WPPB_PLUGIN_DIR . 'assets/images/pb-logo.svg' ) ) ? WPPB_PLUGIN_URL . 'assets/images/pb-logo.svg' : '';
    $ul_icon = ( !empty( $ul_icon_url ) ) ? '<img src="' . esc_url( $ul_icon_url ) . '" width="64" height="64" style="float: left; margin: 15px 12px 15px 0; max-width: 100px;" alt="Profile Builder">' : '';

    $message = $ul_icon;
    $message .= '<h3 style="margin-bottom: 0;">' . esc_html__( 'New field: International Telephone Input.', 'profile-builder' ) . '</h3>';
    $message .= $notification_message;
    $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'wppb_dismiss_admin_notification' => $notification_id ) ), 'wppb_plugin_notice_dismiss' ) ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'profile-builder' ) . '</span></a>';

    $notifications->add_notification( $notification_id, $message, 'wppb-notice notice notice-info', true, array( 'manage-fields' ) );

}
add_action( 'admin_init', 'wppb_international_telephone_input_admin_notification' );

/**
 * Get the Profile Builder Page or Post slug
 *
 */
function wppb_get_pb_page_post_slug() {
    if ( isset( $_GET['post_type'] ) )
        $post_type = sanitize_text_field( $_GET['post_type'] );
    elseif ( isset( $_GET['post'] ) )
        $post_type = get_post_type( (int)$_GET['post'] );
    elseif ( isset( $_GET['page'] ) )
        $post_type = sanitize_text_field( $_GET['page'] );
    else $post_type = '';

    $possible_slugs = array(
        'pb',
        'wppb',
        'profile-builder',
        'user-email-customizer',
        'admin-email-customizer',
        'manage-fields',
        'custom-redirects',
        'profile-user-profile-picture'
    );

    if ( !empty( $post_type ) ) {
        foreach ( $possible_slugs as $slug ) {
            if ( ($post_type === $slug || strpos( $post_type, $slug ) === 0) && strpos($post_type, 'pb_backupbuddy') === false ) {

                return $post_type;

            }
        }
    }

    return '';
}

/**
 * Insert the PB Admin area Header Banner
 *
 */
function wppb_insert_page_banner() {
    $pb_slug = wppb_get_pb_page_post_slug();

    if ( $pb_slug === 'profile-builder-dashboard' && isset( $_GET['subpage'] ) && $_GET['subpage'] === 'wppb-setup' )
        return;

    $page_name = '';
    if ( $pb_slug == 'profile-builder-add-ons' )
        $page_name = ' Add-Ons';

    if ( !empty( $pb_slug ) )
        wppb_output_page_banner($page_name);

}
add_action( 'in_admin_header', 'wppb_insert_page_banner' );

/**
 * Output the PB Admin area Header Banner content
 *
 */
function wppb_output_page_banner( $page_name ) {

    $upgrade_button = '<a class="cozmoslabs-banner-link cozmoslabs-upgrade-link" href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-settings&utm_medium=client-site&utm_campaign=pb-header-upsell#pricing" target="_blank">
                         <img src="'. esc_url(WPPB_PLUGIN_URL) . 'assets/images/upgrade-link-icon.svg" alt="">
                         Upgrade to PRO
                       </a>';

    $upgrade_button_basic = '<a class="cozmoslabs-banner-link cozmoslabs-upgrade-link" href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-settings&utm_medium=client-site&utm_campaign=pb-header-upsell#pricing" target="_blank">
                       <img src="'. esc_url(WPPB_PLUGIN_URL) . 'assets/images/upgrade-link-icon.svg" alt="">
                       Upgrade to PRO
                     </a>';

    $support_url = 'https://wordpress.org/support/plugin/profile-builder/';

    $output = '<div class="cozmoslabs-banner">
                   <div class="cozmoslabs-banner-title">
                       <img src="'. esc_url(WPPB_PLUGIN_URL) . 'assets/images/pb-logo.svg" alt="">
                       <h4>Profile Builder'. $page_name .'</h4>
                   </div>
                   <div class="cozmoslabs-banner-buttons">
                       <a class="cozmoslabs-banner-link cozmoslabs-support-link" href="'. $support_url .'" target="_blank">
                           <img src="'. esc_url(WPPB_PLUGIN_URL) . 'assets/images/support-link-icon.svg" alt="">
                           Support
                       </a>

                       <a class="cozmoslabs-banner-link cozmoslabs-documentation-link" href="https://www.cozmoslabs.com/docs/profile-builder/?utm_source=pb-settings&utm_medium=client-site&utm_campaign=pb-header-upsell" target="_blank">
                           <img src="'. esc_url(WPPB_PLUGIN_URL) . 'assets/images/docs-link-icon.svg" alt="">
                           Documentation
                       </a>';

    if ( !defined( 'WPPB_PAID_PLUGIN_DIR' ) || ( defined( 'PROFILE_BUILDER_PAID_VERSION' ) && PROFILE_BUILDER_PAID_VERSION === 'dev' ) )
        $output .= $upgrade_button;

    // Add Basic version upgrade button (not to account, to plugin purchase page)
    if( defined( 'PROFILE_BUILDER' ) && PROFILE_BUILDER == 'Profile Builder Basic' ){
        $output .= $upgrade_button_basic;
    }

    $output .= '    </div>
                </div>';

    echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}


/**
 * Remove PMS Styles from PB Pages and Posts
 *
 */
function wppb_maybe_remove_pms_styles() {
    if ( !empty( wppb_get_pb_page_post_slug() ) && wp_style_is('pms-style-back-end', 'enqueued') ) {
        wp_dequeue_style('pms-style-back-end');
    }
}
add_action('admin_enqueue_scripts', 'wppb_maybe_remove_pms_styles', 100);


/**
 * Output the deactivation confirmation popup on the Plugins page
 *
 */
function wppb_output_deactivation_popup() {

    if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) )
        return;

    $screen = get_current_screen();

    if ( empty( $screen ) )
        return;

    $is_plugins_screen = in_array( $screen->base, array( 'plugins', 'plugins-network' ), true ) || in_array( $screen->id, array( 'plugins', 'plugins-network' ), true );

    if ( !$is_plugins_screen )
        return;

    ?>
    <div id="wppb-deactivation-popup" title="<?php esc_attr_e( 'Before You Go', 'profile-builder' ); ?>" data-plugin="<?php echo esc_attr( WPPB_PLUGIN_BASENAME ); ?>">
        <div class="wppb-deactivation-popup-header">
            <img src="<?php echo esc_url( WPPB_PLUGIN_URL . 'assets/images/pb-logo.svg' ); ?>" alt="<?php esc_attr_e( 'Profile Builder', 'profile-builder' ); ?>" width="44" height="44">

            <p class="wppb-deactivation-popup-description">
                <?php esc_html_e( 'If you have a moment, please share the reason you are deactivating Profile Builder:', 'profile-builder' ); ?>
            </p>
        </div>

        <form class="wppb-deactivation-popup-form">
            <fieldset class="wppb-deactivation-popup-fieldset">
                <label class="wppb-deactivation-popup-option">
                    <input type="radio" name="wppb_deactivation_reason" value="dont_need_it">
                    <span><?php esc_html_e( 'I no longer need the plugin', 'profile-builder' ); ?></span>
                </label>

                <label class="wppb-deactivation-popup-option">
                    <input type="radio" name="wppb_deactivation_reason" value="switched_to_another_plugin">
                    <span><?php esc_html_e( 'I found a better plugin', 'profile-builder' ); ?></span>
                    <input type="text" name="switched_to_another_plugin_reason" class="wppb-deactivation-popup-extra" data-reason="switched_to_another_plugin" placeholder="<?php esc_attr_e( 'Which plugin', 'profile-builder' ); ?>">
                </label>

                <label class="wppb-deactivation-popup-option">
                    <input type="radio" name="wppb_deactivation_reason" value="missing_features">
                    <span><?php esc_html_e( 'I didn\'t find the feature I need', 'profile-builder' ); ?></span>
                    <input type="text" name="missing_features_reason" class="wppb-deactivation-popup-extra" data-reason="missing_features" placeholder="<?php esc_attr_e( 'Which feature', 'profile-builder' ); ?>">
                </label>

                <label class="wppb-deactivation-popup-option">
                    <input type="radio" name="wppb_deactivation_reason" value="did_not_work">
                    <span><?php esc_html_e( 'I couldn\'t get the plugin to work', 'profile-builder' ); ?></span>
                </label>

                <label class="wppb-deactivation-popup-option">
                    <input type="radio" name="wppb_deactivation_reason" value="temporary_deactivation">
                    <span><?php esc_html_e( 'Temporary deactivation', 'profile-builder' ); ?></span>
                </label>

                <label class="wppb-deactivation-popup-option">
                    <input type="radio" name="wppb_deactivation_reason" value="other">
                    <span><?php esc_html_e( 'Other', 'profile-builder' ); ?></span>
                    <input type="text" name="other_reason" class="wppb-deactivation-popup-extra" data-reason="other" placeholder="<?php esc_attr_e( 'Please tell us more', 'profile-builder' ); ?>">
                </label>
            </fieldset>

            <p class="wppb-deactivation-popup-error"></p>
        </form>

        <div class="wppb-deactivation-popup-actions">
            <button type="button" class="button button-primary wppb-deactivation-popup-confirm">
                <?php esc_html_e( 'Submit & deactivate', 'profile-builder' ); ?>
            </button>

            <button type="button" class="button wppb-deactivation-popup-skip">
                <?php esc_html_e( 'Skip & deactivate', 'profile-builder' ); ?>
            </button>
        </div>
    </div>
    <style>
        #wppb-deactivation-popup {
            display: none;
        }

        .wppb-deactivation-popup-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .wppb-deactivation-popup-description {
            margin: 0;
        }

        .wppb-deactivation-popup-fieldset {
            margin: 0;
            padding: 0;
            border: 0;
        }

        .wppb-deactivation-popup-option {
            display: block;
            margin-bottom: 12px;
        }

        .wppb-deactivation-popup-extra {
            display: none;
            width: 100%;
            margin-top: 8px;
        }

        .wppb-deactivation-popup-error {
            display: none;
            color: #b32d2e;
            margin: 0px 0px 12px 0px;
        }

        .wppb-deactivation-popup-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .wppb-deactivation-popup-extra.error {
            border: 1px solid #b32d2e;
        }

        .ui-dialog[aria-describedby="wppb-deactivation-popup"] .ui-dialog-titlebar {
            background: transparent;
            border: none;
        }
    </style>
    <?php
}
add_action( 'admin_footer', 'wppb_output_deactivation_popup' );


/**
 * Check if current screen is a Profile Builder admin page.
 *
 * @return bool
 */
function wppb_is_profile_builder_admin_page() {

    if ( ! is_admin() )
        return false;

    $screen = get_current_screen();

    if ( ! $screen )
        return false;

    $pb_pages = array(
        'profile-builder',
        'profile-builder-dashboard',
        'profile-builder-basic-info',
        'profile-builder-general-settings',
        'profile-builder-add-ons',
        'manage-fields',
    );

    foreach ( $pb_pages as $page ) {

        if ( strpos( $screen->id, $page ) !== false )
            return true;

    }

    if ( ! empty( $screen->post_type ) ) {
        $pb_cpt_pages = array( 'wppb-ul-cpt', 'wppb-rf-cpt', 'wppb-epf-cpt' );

        if ( in_array( $screen->post_type, $pb_cpt_pages, true ) )
            return true;
    }

    return false;
}


/**
 * Output the popup markup used for documentation links from the admin.
 *
 * @return void
 */
function wppb_output_docs_link_popup() {

    if ( ! wppb_is_profile_builder_admin_page() )
        return;

    ?>
    <div id="wppb-docs-link-popup" class="wppb-docs-link-popup" title="<?php echo esc_attr__( 'Need Help?', 'profile-builder' ); ?>" style="display:none;">
        <div class="wppb-docs-link-popup-content">
            <img src="<?php echo esc_url( WPPB_PLUGIN_URL . 'assets/images/pb-logo.svg' ); ?>" alt="<?php esc_attr_e( 'Profile Builder', 'profile-builder' ); ?>" width="44" height="44" class="wppb-docs-link-popup-logo">
            <div>
                <p class="wppb-docs-link-popup-description"><?php esc_html_e( 'If you need a hand with this setting, you can check the documentation or open a support ticket on WordPress.org.', 'profile-builder' ); ?></p>
                <p class="wppb-docs-link-popup-description"><?php esc_html_e( 'We will do our best to help you figure it out.', 'profile-builder' ); ?></p>
            </div>
        </div>
        <div class="wppb-docs-link-popup-actions cozmoslabs-wrap">
            <a href="#" target="_blank" rel="noopener noreferrer" class="button button-primary wppb-docs-link-popup-open-docs"><?php esc_html_e( 'View Documentation', 'profile-builder' ); ?></a>
            <a href="https://wordpress.org/support/plugin/profile-builder/#new-topic-0" target="_blank" rel="noopener noreferrer" class="button button-primary wppb-docs-link-popup-open-wporg"><?php esc_html_e( 'Open Support Ticket', 'profile-builder' ); ?></a>
        </div>
    </div>
    <?php
}
add_action( 'admin_footer', 'wppb_output_docs_link_popup' );
