<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Submits an HTTP POST to the Turnstile siteverify server.
 * Turnstile requires POST, unlike reCAPTCHA which historically allowed GET.
 *
 * @param string $path
 * @param array $data
 */
function _wppb_turnstile_submitHTTPPost($path, $data)
{
    $response = wp_remote_post( $path, array(
        'body' => $data
    ) );

    if ( ! is_wp_error( $response ) )
        return $response["body"];
}

/**
 * Gets the challenge HTML wrapper for Turnstile.
 *
 * @param string $pubkey A public key for Turnstile
 * @param string $form_name The name of the form
 *
 * @return string - The HTML to be embedded in the user's form.
 */
function wppb_turnstile_get_html ( $pubkey, $form_name='' ){
    global $wppb_turnstile_forms; // is the counter for the number of forms that have turnstile so we always have unique ids on the element
    if( is_null( $wppb_turnstile_forms ) )
        $wppb_turnstile_forms = 0;
    $wppb_turnstile_forms++;

    if ( empty($pubkey) )
        echo '<span class="error">'. esc_html__("To use Cloudflare Turnstile you must get a Site Key from", "profile-builder"). " <a href='https://dash.cloudflare.com/?to=/:account/turnstile'>https://dash.cloudflare.com/?to=/:account/turnstile</a></span><br/><br/>";

    $field = wppb_get_turnstile_field();
    $theme = isset( $field['theme'] ) ? esc_attr( sanitize_text_field( $field['theme'] ) ) : 'auto';

    $output = '<div id="wppb-turnstile-element-'.$form_name.$wppb_turnstile_forms.'" class="wppb-turnstile-element cf-turnstile" data-wppb-sitekey="'.esc_attr( $pubkey ).'" data-wppb-theme="'.$theme.'"></div>';
    
    // We add a hidden field so we can easily check if Turnstile should be processed on this form
    $output .= '<input type="hidden" name="wppb-turnstile-present" value="1">';

    if( $form_name == 'pb_login' ) {
         add_filter( 'wppb_login_submit_button_extra_attributes', 'wppb_turnstile_login_submit_button_extra_attributes' );
    }

    return $output;
}

/**
 * Add disabled attribute to login form submit button when Turnstile is used.
 * Prevent form submission before the script is loaded and a token is received.
 *
 * @param string $attributes
 * @return string
 */
function wppb_turnstile_login_submit_button_extra_attributes( $attributes ) {
    return $attributes . ' disabled="disabled"';
}

/**
 *  Add Turnstile scripts to both front-end PB forms as well as Default WP forms
 */
function wppb_turnstile_script_footer(){
    $field = wppb_get_turnstile_field();
    /* if we do not have a turnstile field do nothing */
    if( empty( $field ) )
        return;

    //do not add script if there is no shortcode
    global $wppb_shortcode_on_front;
    if( current_filter() == 'wp_footer' && ( !isset( $wppb_shortcode_on_front ) || $wppb_shortcode_on_front === false ) )
        return;

    //do not add script if the html for the field has not been added
    global $wppb_turnstile_present;
    if( !isset( $wppb_turnstile_present ) || $wppb_turnstile_present === false )
        return;

    //we don't have jquery on the backend
    if( current_filter() != 'wp_footer' ) {
        wp_print_scripts('jquery');
    }else if(!wp_script_is('jquery')){
        wp_print_scripts('jquery');
    }

    //get site key
    $pubkey = '';
    if( isset( $field['turnstile-site-key'] ) ) {
        $pubkey = sanitize_text_field( $field['turnstile-site-key'] );
    }
    
    $theme = isset( $field['theme'] ) ? sanitize_text_field( $field['theme'] ) : 'auto';

    // phpcs:disable
    echo '
    <script>
        window.wppbTurnstileCallbackExecuted = false;
        
        var wppbTurnstileCallback = function() {
            if( !window.wppbTurnstileCallbackExecuted ){
                let $elements = jQuery(".wppb-turnstile-element");
                
                $elements.each(function(){
                    let $turnstileElement = jQuery(this);
                    
                    if ( typeof $turnstileElement.data("wppb-turnstile-id") !== "undefined" ) {
                        turnstile.reset( $turnstileElement.data("wppb-turnstile-id") );
                        return;
                    }
                    
                    let widgetId = turnstile.render( 
                        "#" + $turnstileElement.attr("id"), 
                        {
                            "sitekey" : "' . $pubkey . '",
                            "theme": "' . $theme . '"
                        }
                    )
                    
                    $turnstileElement.data("wppb-turnstile-id", widgetId);
                });
                
                window.wppbTurnstileCallbackExecuted = true;
                
                // Enable login form submit button initially as Turnstile handles its own disabled state or we wait for callback
                if( jQuery("#wppb-loginform input[type=submit]").length > 0 ) {
                    jQuery("#wppb-loginform input[type=submit]").attr("disabled", false);
                }
            }
        };
    </script>';
    // phpcs:enable

    echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=wppbTurnstileCallback&render=explicit" async defer></script>';
    echo '<script>
        /* compatibility with other plugins that may include Turnstile with an onload callback. if their script loads first then our callback will not execute so call it explicitly  */
        jQuery( window ).on( "load", function() {
            wppbTurnstileCallback();
        });
    </script>';

}
add_action('wp_footer', 'wppb_turnstile_script_footer', 9999);
add_action('login_footer', 'wppb_turnstile_script_footer');
add_action('register_form', 'wppb_turnstile_script_footer');
add_action('lost_password', 'wppb_turnstile_script_footer');

/**
 * A wppb_TurnstileResponse is returned from wppb_turnstile_check_answer()
 */
class wppb_TurnstileResponse {
    var $is_valid;
}

/**
 * Calls an HTTP POST function to verify if the user\'s answer was correct
 * @param string $privkey
 * @param string $remoteip
 * @param string $response
 * @return wppb_TurnstileResponse
 */
function wppb_turnstile_check_answer ( $privkey, $remoteip, $response ) {

    if ( $remoteip == null || $remoteip == '' )
        echo '<span class="error">'. esc_html__("For security reasons, you must pass the remote ip to Turnstile!", "profile-builder") .'</span><br/><br/>';

    // Discard empty solution submissions
    if ($response == null || strlen($response) == 0) {
        $turnstileResponse = new wppb_TurnstileResponse();
        $turnstileResponse->is_valid = false;
        
        return $turnstileResponse;
    }

    $getResponse = _wppb_turnstile_submitHTTPPost(
        "https://challenges.cloudflare.com/turnstile/v0/siteverify",
        array (
            'secret' => $privkey,
            'remoteip' => $remoteip,
            'response' => $response
        )
    );

    $answers = json_decode($getResponse, true);
    $turnstileResponse = new wppb_TurnstileResponse();

    if (trim($answers ['success']) == true) {
        $turnstileResponse->is_valid = true;
    } else {
        $turnstileResponse->is_valid = false;
    }

    return $turnstileResponse;

}

/* the function to validate the Turnstile response with the API */
function wppb_validate_turnstile_response( $publickey, $privatekey ){
    if (isset($_POST['cf-turnstile-response'])){
        $turnstile_response_field = sanitize_textarea_field( $_POST['cf-turnstile-response'] );
    } else {
        $turnstile_response_field = '';
    }

    $already_validated = false;
    $saved = get_option( 'wppb_turnstile_validations', array() );

    if( isset( $saved[ $turnstile_response_field ] ) && $saved[ $turnstile_response_field ] == true ){
        $already_validated = true;

        if( !wp_doing_ajax() ){
            unset( $saved[ $turnstile_response_field ] );
            update_option( 'wppb_turnstile_validations', $saved, false );
        }
    }

    if( !$already_validated ){

        if( isset( $_SERVER["REMOTE_ADDR"] ) ){
            $resp = wppb_turnstile_check_answer($privatekey, sanitize_text_field( $_SERVER["REMOTE_ADDR"] ), $turnstile_response_field );

            if( isset( $resp ) ){
                $already_validated = ( ( !$resp->is_valid ) ? false : true );
            }
        }

    }

    // Save valid results when they are being triggered from an ajax request
    if( wp_doing_ajax() && isset( $_POST['action'] ) && $_POST['action'] == 'pms_validate_checkout' ){

        $saved = get_option( 'wppb_turnstile_validations', array() );

        if( $already_validated === true )
            $saved[ $turnstile_response_field ] = true;

        update_option( 'wppb_turnstile_validations', $saved, false );

    }

    return $already_validated;

}

/* the function to add Turnstile to the registration form of PB */
function wppb_turnstile_handler ( $output, $form_location, $field, $user_id, $field_check_errors, $request_data ){
    if ( $field['field'] == 'Turnstile' ){
        $item_title = apply_filters( 'wppb_'.$form_location.'_turnstile_custom_field_'.$field['id'].'_item_title', wppb_icl_t( 'plugin profile-builder-pro', 'custom_field_'.$field['id'].'_title_translation', $field['field-title'], true ) );
        $item_description = wppb_icl_t( 'plugin profile-builder-pro', 'custom_field_'.$field['id'].'_description_translation', $field['description'], true );

        wppb_turnstile_set_default_values();

        if ( ($form_location == 'register') && ( isset($field['turnstile-pb-forms']) ) && ( strpos($field['turnstile-pb-forms'],'pb_register') !== false ) ) {
            $error_mark = ( ( $field['required'] == 'Yes' ) ? '<span class="wppb-required" title="'.wppb_required_field_error($field["field-title"]).'">*</span>' : '' );

            global $wppb_turnstile_present;
            $wppb_turnstile_present = true;

            if ( array_key_exists( $field['id'], $field_check_errors ) )
                $error_mark = '<img src="'.WPPB_PLUGIN_URL.'assets/images/pencil_delete.png" title="'.wppb_required_field_error($field["field-title"]).'"/>';

            $publickey = trim( $field['turnstile-site-key'] );
            $privatekey = trim( $field['turnstile-secret-key'] );

            if ( empty( $publickey ) || empty( $privatekey ) )
                return '<span class="custom_field_turnstile_error_message" id="'.$field['meta-name'].'_error_message">'.apply_filters( 'wppb_'.$form_location.'_turnstile_custom_field_'.$field['id'].'_error_message', __("To use Cloudflare Turnstile you must get a Site Key and Secret Key from:", "profile-builder"). '<a href="https://dash.cloudflare.com/?to=/:account/turnstile">https://dash.cloudflare.com/?to=/:account/turnstile</a>' ).'</span>';

            $output = '<label for="turnstile_response_field">' . $item_title . $error_mark . '</label>' . wppb_turnstile_get_html($publickey, 'pb_register');
            if (!empty($item_description))
                $output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

            return $output;

        }
    }
}
add_filter( 'wppb_output_form_field_turnstile', 'wppb_turnstile_handler', 10, 6 );

/* handle Turnstile field validation on PB Register form */
function wppb_check_turnstile_value( $message, $field, $request_data, $form_location ){
    if( $field['field'] == 'Turnstile' ){
        if ( ( $form_location == 'register' ) && ( isset($field['turnstile-pb-forms']) ) && ( strpos($field['turnstile-pb-forms'],'pb_register') !== false ) ) {
            /* theme my login plugin executes the register_errors hook on the frontend on all pages so on our register forms we might have already a turnstile response
            so do not verify it again or it will fail  */
            global $wppb_turnstile_response;
            if (!isset($wppb_turnstile_response)){
                $wppb_turnstile_response = wppb_validate_turnstile_response( trim( $field['turnstile-site-key'] ), trim( $field['turnstile-secret-key'] ) );
            }
            if ( (  $wppb_turnstile_response == false ) && ( $field['required'] == 'Yes' ) ){
                return __('Cloudflare Turnstile could not be verified. Please try again.', 'profile-builder');
            }
        }
    }
    return $message;
}
add_filter( 'wppb_check_form_field_turnstile', 'wppb_check_turnstile_value', 10, 4 );

// Get the Turnstile field information
function wppb_get_turnstile_field(){
    $wppb_manage_fields = get_option( 'wppb_manage_fields', 'not_found' );
    $field = array();
    if ( $wppb_manage_fields != 'not_found' ) {
        foreach ($wppb_manage_fields as $value) {
            if ($value['field'] == 'Turnstile'){
                $field = $value;
                break;
            }
        }
    }
    return $field;
}

/* Display Turnstile on PB Recover Password form */
function wppb_display_turnstile_recover_password( $output ){
    $field = wppb_get_turnstile_field();

    if ( !empty($field) ) {
        $publickey = trim($field['turnstile-site-key']);
        $item_title = apply_filters('wppb_recover_password_turnstile_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
        $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

        // check where Turnstile should display and add Turnstile html
        if ( isset($field['turnstile-pb-forms']) && ( strpos( $field['turnstile-pb-forms'],'pb_recover_password' ) !== false ) ) {

            global $wppb_turnstile_present;
            $wppb_turnstile_present = true;

            $turnstile_output = '<label for="turnstile_response_field">' . $item_title . '</label>' . wppb_turnstile_get_html($publickey, 'pb_recover_password');
            if (!empty($item_description))
                $turnstile_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

            $output = str_replace('</ul>', '<li class="wppb-form-field wppb-turnstile">' . $turnstile_output . '</li>' . '</ul>', $output);
        }
    }
    return $output;
}
add_filter('wppb_recover_password_generate_password_input','wppb_display_turnstile_recover_password');

/*  Function that changes the messageNo from the Recover Password form  */
function wppb_turnstile_change_recover_password_message_no($messageNo) {

        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'recover_password') {
            $field = wppb_get_turnstile_field();
            if (!empty($field)) {

                global $wppb_turnstile_response;
                if (!isset($wppb_turnstile_response)) $wppb_turnstile_response = wppb_validate_turnstile_response( trim( $field['turnstile-site-key'] ), trim( $field['turnstile-secret-key'] ) );

                if ( isset($field['turnstile-pb-forms']) && (strpos($field['turnstile-pb-forms'], 'pb_recover_password') !== false) ) {

                    if ( $wppb_turnstile_response == false )
                        $messageNo = '';
                }
            }
        }

        return $messageNo;
}
add_filter('wppb_recover_password_message_no', 'wppb_turnstile_change_recover_password_message_no');

/*  Function that adds the Turnstile error message on the Recover Password form  */
function wppb_turnstile_recover_password_displayed_message1( $message ) {
    $field = wppb_get_turnstile_field();

    if ( !empty($field) ){
        global $wppb_turnstile_response;
        if (!isset($wppb_turnstile_response)) $wppb_turnstile_response = wppb_validate_turnstile_response( trim( $field['turnstile-site-key'] ), trim( $field['turnstile-secret-key'] ) );

        if ( isset($field['turnstile-pb-forms']) && ( strpos( $field['turnstile-pb-forms'],'pb_recover_password' ) !== false ) && ( $wppb_turnstile_response == false )) {

            $turnstile_error_message = __('Cloudflare Turnstile could not be verified. Please try again.', 'profile-builder');

            if (($message == '<p class="wppb-warning">wppb_turnstile_error</p>') || ($message == '<p class="wppb-warning">wppb_captcha_error</p>'))
                $message = '<p class="wppb-warning">' . $turnstile_error_message . '</p>';
            else
                $message = $message . '<p class="wppb-warning">' . $turnstile_error_message . '</p>';

            }
        }

    return $message;
}
add_filter('wppb_recover_password_displayed_message1', 'wppb_turnstile_recover_password_displayed_message1');

/*  Function that changes the default success message to wppb_turnstile_error if it doesn't validate */
function wppb_turnstile_recover_password_sent_message_1($message) {

        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'recover_password') {
            $field = wppb_get_turnstile_field();

            if (!empty($field)) {
                global $wppb_turnstile_response;
                if (!isset($wppb_turnstile_response)) $wppb_turnstile_response = wppb_validate_turnstile_response( trim( $field['turnstile-site-key'] ), trim( $field['turnstile-secret-key'] ) );

                if ( isset($field['turnstile-pb-forms']) && ( strpos($field['turnstile-pb-forms'], 'pb_recover_password') !== false ) && ( $wppb_turnstile_response == false ) ){
                    $message = 'wppb_turnstile_error';
                }
            }

        }

        return $message;
}
add_filter('wppb_recover_password_sent_message1', 'wppb_turnstile_recover_password_sent_message_1');

/* Display Turnstile html on PB Login form */
function wppb_display_turnstile_login_form($form_part, $args) {

    if( !isset( $args['form_id'] ) || $args['form_id'] != 'wppb-loginform' )
        return $form_part;

    $field = wppb_get_turnstile_field();

    if ( !empty($field) ) {
        $item_title = apply_filters('wppb_login_turnstile_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
        $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

        if ( isset($field['turnstile-pb-forms']) && ( strpos( $field['turnstile-pb-forms'],'pb_login' ) !== false ) ) { // check where Turnstile should display

            global $wppb_turnstile_present;
            $wppb_turnstile_present = true;

            $turnstile_output = '<label for="turnstile_response_field">' . $item_title . '</label>' . wppb_turnstile_get_html(trim($field['turnstile-site-key']), 'pb_login');
            if (!empty($item_description))
                $turnstile_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

            $form_part .= '<div class="wppb-form-field wppb-turnstile">' . $turnstile_output . '</div>';
            
        }
    }

    return $form_part;
}
add_filter('login_form_middle', 'wppb_display_turnstile_login_form', 10, 2);

/* Display Turnstile html on default WP Login form */
function wppb_display_turnstile_wp_login_form(){
    $field = wppb_get_turnstile_field();

    if ( !empty($field) ) {
        $item_title = apply_filters('wppb_login_turnstile_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
        $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

        if ( isset($field['turnstile-wp-forms']) && (strpos( $field['turnstile-wp-forms'],'default_wp_login' ) !== false) ) {

            global $wppb_turnstile_present;
            $wppb_turnstile_present = true;

            $turnstile_output = '<label for="turnstile_response_field" style="padding-left:15px; padding-bottom:7px;">' . $item_title . '</label>' . wppb_turnstile_get_html(trim($field['turnstile-site-key']));
            if (!empty($item_description))
                $turnstile_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

            echo '<div class="wppb-form-field wppb-turnstile" style="margin-left:-14px; margin-bottom: 15px;">' . $turnstile_output . '</div>'; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ /* properly escaped when constructing the var */
            
        }
    }
}
add_action( 'login_form', 'wppb_display_turnstile_wp_login_form' );

//Show Turnstile error on Login form (both default and PB one)
function wppb_turnstile_login_wp_error_message($user){
    //make sure you\'re on a Login form (WP or PB)
    if ( isset( $_POST['log'] ) && !is_wp_error($user) && !isset( $_POST['pms_login'] ) ) {

        $field = wppb_get_turnstile_field();
        if ( !empty($field) ){
            global $wppb_turnstile_response;

            if (!isset($wppb_turnstile_response)) $wppb_turnstile_response = wppb_validate_turnstile_response( trim( $field['turnstile-site-key'] ), trim( $field['turnstile-secret-key'] ) );

            $turnstile_error_message = __('Cloudflare Turnstile could not be verified. Please try again.','profile-builder');

            //Turnstile error for displaying on the PB login form
            if ( isset($_POST['wppb_login']) && ($_POST['wppb_login'] == true) ) {

                // it\'s a PB login form, check if we have Turnstile on it and display error if not valid
                if ((isset($field['turnstile-pb-forms'])) && (strpos($field['turnstile-pb-forms'], 'pb_login') !== false) && ($wppb_turnstile_response == false)) {
                    $user = new WP_Error('wppb_turnstile_error', $turnstile_error_message);
                    remove_filter( 'authenticate', 'wp_authenticate_username_password',  20, 3 );
                    remove_filter( 'authenticate', 'wp_authenticate_email_password',     20, 3 );
                }

            }
            else {
                //Turnstile error for displaying on the default WP login form
                if (isset($field['turnstile-wp-forms']) && (strpos($field['turnstile-wp-forms'], 'default_wp_login') !== false) && ($wppb_turnstile_response == false)) {
                    $user = new WP_Error('wppb_turnstile_error', $turnstile_error_message);
                    remove_filter( 'authenticate', 'wp_authenticate_username_password',  20, 3 );
                    remove_filter( 'authenticate', 'wp_authenticate_email_password',     20, 3 );
                }

            }
        }
    }
    return $user;
}
add_filter('authenticate','wppb_turnstile_login_wp_error_message', 9);

/**
 * Add a Turnstile CSS class to the Register form field
 *
 * @param $classes - existing field classes
 * @param $field   - field data
 * @return mixed|string
 */
function wppb_register_form_turnstile_type_class( $classes, $field ){

    if ( isset( $field['field'] ) && $field['field'] == 'Turnstile' )
        $classes .= ' wppb-turnstile';

    return $classes;
}
add_filter( 'wppb_field_css_class', 'wppb_register_form_turnstile_type_class', 20, 2);

// Display Turnstile html on default WP Recover Password form
function wppb_display_turnstile_default_wp_recover_password() {
    $field = wppb_get_turnstile_field();

    if (!empty($field)) {
        $publickey = trim($field['turnstile-site-key']);
        $item_title = apply_filters('wppb_recover_password_turnstile_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
        $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

        if ( isset($field['turnstile-wp-forms']) && (strpos( $field['turnstile-wp-forms'], 'default_wp_recover_password') !== false) ) { 

            global $wppb_turnstile_present;
            $wppb_turnstile_present = true;

            $turnstile_output = '<label for="turnstile_response_field" style="padding-left:15px; padding-bottom:7px;">' . $item_title . '</label>' . wppb_turnstile_get_html($publickey);
            if (!empty($item_description))
                $turnstile_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

            echo '<div class="wppb-form-field wppb-turnstile" style="margin-left:-14px; margin-bottom: 15px;">' . $turnstile_output . '</div>'; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ /* properly escaped when constructing the var */
            
        }
    }
}
add_action('lostpassword_form','wppb_display_turnstile_default_wp_recover_password');

// Verify and show Turnstile errors for default WP Recover Password
function wppb_verify_turnstile_default_wp_recover_password(){

    // If field \'username or email\' is empty - return
    if( isset( $_REQUEST['user_login'] ) && "" === $_REQUEST['user_login'] )
        return;

    $field = wppb_get_turnstile_field();
    if ( !empty($field) ){
        global $wppb_turnstile_response;
        if (!isset($wppb_turnstile_response)) $wppb_turnstile_response = wppb_validate_turnstile_response( trim( $field['turnstile-site-key'] ), trim( $field['turnstile-secret-key'] ) );

        $turnstile_error_message = esc_html__('Cloudflare Turnstile could not be verified. Please try again.','profile-builder');

        // If Turnstile not entered or incorrect Turnstile answer
        if ( isset( $_REQUEST['cf-turnstile-response'] ) && ( ( "" ===  $_REQUEST['cf-turnstile-response'] )  || ( $wppb_turnstile_response == false ) ) ) {
            wp_die( esc_html( $turnstile_error_message ) . '<br />' . esc_html__( "Click the BACK button on your browser, and try again.", 'profile-builder' ) ) ;
        }
    }
}
add_action('lostpassword_post','wppb_verify_turnstile_default_wp_recover_password');

/* Display Turnstile html on default WP Register form */
function wppb_display_turnstile_default_wp_register(){
    $field = wppb_get_turnstile_field();

    if (!empty($field)) {

            $publickey = trim($field['turnstile-site-key']);
            $item_title = apply_filters('wppb_register_turnstile_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
            $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

            wppb_turnstile_set_default_values();
            if (isset($field['turnstile-wp-forms']) && (strpos($field['turnstile-wp-forms'], 'default_wp_register') !== false)) {

                global $wppb_turnstile_present;
                $wppb_turnstile_present = true;

                $turnstile_output = '<label for="turnstile_response_field" style="padding-left:15px; padding-bottom:7px;">' . $item_title . '</label>' . wppb_turnstile_get_html($publickey);
                if (!empty($item_description))
                    $turnstile_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

                echo '<div class="wppb-form-field wppb-turnstile" style="margin-left:-14px; margin-bottom: 15px;">' . $turnstile_output . '</div>'; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ /* properly escaped when constructing the var */
                
            }
    }
}
add_action( 'register_form', 'wppb_display_turnstile_default_wp_register' );

// Verify and show Turnstile errors for default WP Register form
function wppb_verify_turnstile_default_wp_register( $errors ){

    $field = wppb_get_turnstile_field();
    if ( !empty($field) ){
        global $wppb_turnstile_response;
        if (!isset($wppb_turnstile_response)) $wppb_turnstile_response = wppb_validate_turnstile_response( trim( $field['turnstile-site-key'] ), trim( $field['turnstile-secret-key'] ) );

        $turnstile_error_message = esc_html__('Cloudflare Turnstile could not be verified. Please try again.','profile-builder');

        // If Turnstile not entered or incorrect Turnstile answer
        if ( isset( $_REQUEST['cf-turnstile-response'] ) && ( ( "" ===  $_REQUEST['cf-turnstile-response'] )  || ( $wppb_turnstile_response == false ) ) ) {
            $errors->add( 'wppb_turnstile_error', $turnstile_error_message );
        }
    }

return $errors;
}
add_filter('registration_errors','wppb_verify_turnstile_default_wp_register');

// set default values in case there's already an existing Turnstile field in Manage fields (when upgrading)
function wppb_turnstile_set_default_values() {
    $manage_fields = get_option('wppb_manage_fields', 'not_set');
    if ($manage_fields != 'not_set') {
        foreach ($manage_fields as $key => $value) {
            if ($value['field'] == 'Turnstile') {
                if ( !isset($value['turnstile-pb-forms']) ) $manage_fields[$key]['turnstile-pb-forms'] = 'pb_register';
                if ( !isset($value['turnstile-wp-forms']) ) $manage_fields[$key]['turnstile-wp-forms'] = 'default_wp_register';
                if ( !isset($value['theme']) )              $manage_fields[$key]['theme'] = 'auto';
            }
        }
        update_option('wppb_manage_fields', $manage_fields);
    }
}
