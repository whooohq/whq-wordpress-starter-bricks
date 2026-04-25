<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Encodes the given data into a query string format
 * @param $data - array of string elements to be encoded
 * @return string - encoded request
 */
function _wppb_encodeQS($data)
{
    $req = "";
    foreach ($data as $key => $value) {
        $req .= $key . '=' . urlencode(stripslashes($value)) . '&';
    }
    // Cut the last '&'
    $req=substr($req, 0, strlen($req)-1);
    return $req;
}



/**
 * Submits an HTTP GET to a reCAPTCHA server
 * @param string $path
 * @param array $data
 */
function _wppb_submitHTTPGet($path, $data)
{
    $req = _wppb_encodeQS($data);
    $response = wp_remote_get($path . $req);

    if ( ! is_wp_error( $response ))
        return $response["body"];
}

/**
 * Gets the challenge HTML (javascript and non-javascript version).
 * This is called from the browser, and the resulting reCAPTCHA HTML widget
 * is embedded within the HTML form it was called from.
 * @param string $pubkey A public key for reCAPTCHA
 * @param string $error The error given by reCAPTCHA (optional, default is null)
 * @param boolean $use_ssl Should the request be made over ssl? (optional, default is false)

 * @return string - The HTML to be embedded in the user's form.
 */
function wppb_recaptcha_get_html ( $pubkey, $form_name='' ){
    global $wppb_recaptcha_forms; // is the counter for the number of forms that have recaptcha so we always have unique ids on the element
    if( is_null( $wppb_recaptcha_forms ) )
        $wppb_recaptcha_forms = 0;
    $wppb_recaptcha_forms++;

    $field = wppb_get_recaptcha_field();

    if ( empty($pubkey) )
        echo '<span class="error">'. esc_html__("To use reCAPTCHA you must get an API key from", "profile-builder"). " <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a></span><br/><br/>";

    // extra class needed for Invisible reCAPTCHA html
    $invisible_class = '';
    $v3_field_html = '';
    if ( isset($field['recaptcha-type']) && ($field['recaptcha-type'] == 'invisible') ) {
        $invisible_class = 'wppb-invisible-recaptcha';
    } elseif ( isset($field['recaptcha-type']) && ($field['recaptcha-type'] == 'v3') ) {
        $invisible_class = 'wppb-v3-recaptcha';
        $v3_field_html = '<input type="hidden" name="g-recaptcha-response" class="g-recaptcha-response wppb-v3-recaptcha">';
    }

    $output = '<div id="wppb-recaptcha-element-'.$form_name.$wppb_recaptcha_forms.'" class="wppb-recaptcha-element '.$invisible_class.'">'.$v3_field_html.'</div>';

    if ( isset($field['recaptcha-type']) && ($field['recaptcha-type'] == 'v3') ) {
        $output .= '<input type="hidden" name="wppb-recaptcha-v3" value="1">';

        if( $form_name == 'pb_login' ) {
            add_filter( 'wppb_login_submit_button_extra_attributes', 'wppb_recaptcha_login_submit_button_extra_attributes' );
        }

    }

    // reCAPTCHA html for all forms and we make sure we have a unique id for v2
    return $output;
}

/**
 * Add disabled attribute to login form submit button when reCaptcha v3 is used
 * This is used to prevent form submission before the reCaptcha script is loaded and a token is received
 * 
 * @param string $attributes
 * @return string
 */
function wppb_recaptcha_login_submit_button_extra_attributes( $attributes ) {
    return $attributes . ' disabled="disabled"';
}

/**
 *  Add reCAPTCHA scripts to both front-end PB forms (with support for multiple forms) as well as Default WP forms
 */
function wppb_recaptcha_script_footer(){
    $field = wppb_get_recaptcha_field();
    /* if we do not have a recaptcha field do nothing */
    if( empty( $field ) )
        return;

    //do not add script if there is no shortcode
    global $wppb_shortcode_on_front;
    if( current_filter() == 'wp_footer' && ( !isset( $wppb_shortcode_on_front ) || $wppb_shortcode_on_front === false ) )
        return;

    //do not add script if the html for the field has not been added
    global $wppb_recaptcha_present;
    if( !isset( $wppb_recaptcha_present ) || $wppb_recaptcha_present === false )
        return;

    //we don't have jquery on the backend
    if( current_filter() != 'wp_footer' ) {
        wp_print_scripts('jquery');
    }else if(!wp_script_is('jquery')){
        wp_print_scripts('jquery');
    }

    //get site key
    $pubkey = '';
    if( isset( $field['public-key'] ) ) {
        $pubkey = sanitize_text_field( $field['public-key'] );
    }

    // Check if we have a reCAPTCHA type
    if ( !isset($field['recaptcha-type']) )
        $field['recaptcha-type'] = 'v2' ;

    /*for invisible recaptcha we have extra parameters and the selector is different. v2 is initialized on the id of the div
    that must be unique and invisible is on the submit button of the forms that have the div */
    if ( $field['recaptcha-type'] === 'invisible' ) {
        $callback_conditions  = 'jQuery("input[type=\'submit\']", jQuery( ".wppb-recaptcha-element" ).closest("form") )';
        $invisible_parameters = '"callback" : wppbInvisibleRecaptchaOnSubmit,"size": "invisible"';
    } elseif ( $field['recaptcha-type'] === 'v3' ) {
        $callback_conditions  = 'jQuery( jQuery( ".wppb-recaptcha-element" ).closest("form") )';
        $invisible_parameters = '';
    } else {
        $callback_conditions  = 'jQuery(".wppb-recaptcha-element")';
        $invisible_parameters = '';
    }

    if( $field['recaptcha-type'] === 'v3' ) {

        //the section below is properly escaped or the variables contain static strings
        // phpcs:disable
        echo '
        <script>
            window.wppbRecaptchaCallbackExecuted = false;
            window.wppbRecaptchaV3 = true;
            var wppbRecaptchaCallback = function() {
                if( !window.wppbRecaptchaCallbackExecuted ){
                    '.$callback_conditions.'.each(function() {
                        let wppbElement = jQuery(this),
                            form = wppbElement.is("form") ? wppbElement : wppbElement.find("form"),
                            currentForm = form[0];
                
                        // Ensure we have a PB Form
                        if (form.length === 0) {
                            return;
                        }

                        // Listen for PB-Form submission
                        jQuery(currentForm).on("submit.wppbRecaptchaV3", wppbInitializeRecaptchaV3);
                    });
                    window.wppbRecaptchaCallbackExecuted = true;//we use this to make sure we only run the callback once

                    // Enable login form submit button
                    if( jQuery("#wppb-loginform input[type=submit]").length > 0 ) {
                        jQuery("#wppb-loginform input[type=submit]").attr("disabled", false);
                    }
                }
            };

            function wppbInitializeRecaptchaV3( event = null, current_form = null ){

                if( event ){
                    event.preventDefault();
                    event.stopPropagation();
                }

                let currentForm = this

                if( current_form != null && current_form && current_form[0] ){
                    currentForm = current_form[0]
                }

                return new Promise((resolve) => {

                    grecaptcha.ready(function() {
                        grecaptcha.execute("' . $pubkey . '", {action: "submit"}).then(function(token) {
                        
                            let recaptchaResponse = jQuery(currentForm).find(".wppb-v3-recaptcha.g-recaptcha-response");
                            jQuery(recaptchaResponse).val(token); // Set the recaptcha response
                            
                            if( token === false ){
                                return wppbRecaptchaInitializationError();
                            }
                            
                            var submitForm = true
                    
                            /* dont submit form if PMS gateway is Stripe */
                            if( jQuery(".pms_pay_gate[type=radio]").length > 0 ){
                                jQuery(".pms_pay_gate").each( function(){
                                    if( jQuery(this).is(":checked") && !jQuery(this).is(":disabled") && ( jQuery(this).val() == "stripe_connect" || jQuery(this).val() == "stripe_intents" || jQuery(this).val() == "stripe" || jQuery(this).val() == "paypal_connect" ) )
                                        submitForm = false
                                })
                            } else if( jQuery(".pms_pay_gate[type=hidden]").length > 0 ) {
                    
                                if( !jQuery(".pms_pay_gate[type=hidden]").is(":disabled") && ( jQuery(".pms_pay_gate[type=hidden]").val() == "stripe_connect" || jQuery(".pms_pay_gate[type=hidden]").val() == "stripe_intents" || jQuery(".pms_pay_gate[type=hidden]").val() == "stripe" || jQuery(".pms_pay_gate[type=hidden]").val() == "paypal_connect" ) )
                                    submitForm = false
                            } else if( currentForm.classList.contains("wppb-ajax-form") ) {
                                submitForm = false;                                    
                            } else if( currentForm.classList.contains("wppb-2fa-form") ) {
                                submitForm = false;
                            }

                            if( currentForm.classList.contains("wppb-2fa-authentication-requested" ) ){
                                submitForm = true;
                            }

                            if( submitForm ){
                                jQuery(currentForm).off("submit.wppbRecaptchaV3");
                                currentForm.submit();
                            } else {
                                jQuery(document).trigger( "wppb_v3_recaptcha_success", jQuery( "input[type=\'submit\']", jQuery( currentForm ) ) )
                            }

                            resolve( token );

                        });
                    });

                });
            }
    
            /* the callback function for when the captcha does not load propperly, maybe network problem or wrong keys  */
            function wppbRecaptchaInitializationError(){
                window.wppbRecaptchaInitError = true;
        ';

    } else {
        //the section below is properly escaped or the variables contain static strings
        // phpcs:disable
        echo '
        <script>
            window.wppbRecaptchaCallbackExecuted = false;
            window.wppbRecaptcha = true;
            var wppbRecaptchaCallback = function() {
                if( !window.wppbRecaptchaCallbackExecuted ){//see if we executed this before
                    ' . $callback_conditions . '.each(function(){
                        var $recaptchaElement = jQuery(this);
                        var existingRecaptchaId = $recaptchaElement.data("wppb-recaptcha-id");

                        if ( typeof existingRecaptchaId !== "undefined" ) {
                            grecaptcha.reset( existingRecaptchaId );
                            return;
                        }

                        var recID = grecaptcha.render( 
                            $recaptchaElement.attr("id"), 
                            {
                                "sitekey" : "' . $pubkey . '",
                                "error-callback": wppbRecaptchaInitializationError,
                                ' . $invisible_parameters . '
                            }
                        )

                        $recaptchaElement.data("wppb-recaptcha-id", recID);
                    });
                    window.wppbRecaptchaCallbackExecuted = true;//we use this to make sure we only run the callback once
                }
            };
    
            /* the callback function for when the captcha does not load propperly, maybe network problem or wrong keys  */
            function wppbRecaptchaInitializationError(){
                window.wppbRecaptchaInitError = true;
            ';
    }

    if ( $field['recaptcha-type'] === 'invisible' ) {
        echo '
            /* make sure that if the invisible recaptcha did not load properly ( network error or wrong keys ) we can still submit the form */
            jQuery("input[type=\'submit\']", jQuery( ".wppb-recaptcha-element" ).closest("form") ).on("click", function(e){
                        jQuery(this).closest("form").submit();
                });
            ';
    }

    echo '
            //add a captcha field so we do not just let the form submit if we do not have a captcha response
            jQuery( ".wppb-recaptcha-element" ).after(\'' . wp_nonce_field( 'wppb_recaptcha_init_error', 'wppb_recaptcha_load_error', false, false ) . '\');
        }

        /* compatibility with other plugins that may include recaptcha with an onload callback. if their script loads first then our callback will not execute so call it explicitly  */
        jQuery( window ).on( "load", function() {
            wppbRecaptchaCallback();
        });
    </script>';
    // phpcs:enable
    if ( $field['recaptcha-type'] === 'invisible' ) {
        echo '<script>
            /* success callback for invisible recaptcha. it submits the form that contains the right token response */
            function wppbInvisibleRecaptchaOnSubmit(token){

                var elem = jQuery(".g-recaptcha-response").filter(function(){
                    return jQuery(this).val() === token;
                });

                var form = elem.closest("form");

                var submitForm = true

                /* dont submit form if PMS gateway is Stripe */
                if( jQuery(".pms_pay_gate[type=radio]").length > 0 ){
                    jQuery(".pms_pay_gate").each( function(){
                        if( jQuery(this).is(":checked") && !jQuery(this).is(":disabled") && ( jQuery(this).val() == "stripe_connect" || jQuery(this).val() == "stripe_intents" || jQuery(this).val() == "stripe" || jQuery(this).val() == "paypal_connect" ) )
                            submitForm = false
                    })
                } else if( jQuery(".pms_pay_gate[type=hidden]").length > 0 ) {

                    if( !jQuery(".pms_pay_gate[type=hidden]").is(":disabled") && ( jQuery(".pms_pay_gate[type=hidden]").val() == "stripe_connect" || jQuery(".pms_pay_gate[type=hidden]").val() == "stripe_intents" || jQuery(".pms_pay_gate[type=hidden]").val() == "stripe" || jQuery(".pms_pay_gate[type=hidden]").val() == "paypal_connect" ) )
                        submitForm = false
                                 
                } else if( form.hasClass("wppb-ajax-form") ) {
                    submitForm = false;    
                } else if( form.hasClass("wppb-2fa-form") ) {
                    submitForm = false;
                }

                if( form.hasClass("wppb-2fa-authentication-requested" ) ){
                    submitForm = true;
                }

                if( submitForm ){
                    form.submit();
                } else {
                    jQuery(document).trigger( "wppb_invisible_recaptcha_success", jQuery( ".form-submit input[type=\'submit\']", elem.closest("form") ) )
                    return true;
                }
            }
        </script>';
    }

	$lang = '&hl=en';
    $locale = get_locale();
    if(!empty($locale)) {
        $locale_parts = explode('_',$locale);
	    $lang = '&hl='.urlencode($locale_parts[0]);
    }

    $source = apply_filters( 'wppb_recaptcha_custom_field_source', 'www.google.com' );

    if( $field['recaptcha-type'] === 'v3' ) {
        echo '<script src="https://'. esc_attr( $source ) .'/recaptcha/api.js?render='.esc_attr( $pubkey ).'" async defer></script>';
    } else  {
        echo '<script src="https://'. esc_attr( $source ) .'/recaptcha/api.js?onload=wppbRecaptchaCallback&render=explicit'.esc_attr( $lang ).'" async defer></script>';
    }

}
add_action('wp_footer', 'wppb_recaptcha_script_footer', 9999);
add_action('login_footer', 'wppb_recaptcha_script_footer');
add_action('register_form', 'wppb_recaptcha_script_footer');
add_action('lost_password', 'wppb_recaptcha_script_footer');


/**
 * Print style
 *
 */
function wppb_recaptcha_print_style() {
    echo '<style type="text/css"> 
         /* Hide reCAPTCHA V3 badge */
        .grecaptcha-badge {
        
            visibility: hidden !important;
        
        }
    </style>';
}

add_action( 'wp_footer', 'wppb_recaptcha_print_style' );
add_action( 'login_footer', 'wppb_recaptcha_print_style' );


/**
 * A wppb_ReCaptchaResponse is returned from wppb_recaptcha_check_answer()
 */
class wppb_ReCaptchaResponse {
    var $is_valid;
}


/**
 * Calls an HTTP POST function to verify if the user's answer was correct
 * @param string $privkey
 * @param string $remoteip
 * @param string $response
 * @return wppb_ReCaptchaResponse
 */
function wppb_recaptcha_check_answer ( $privkey, $remoteip, $response, $score_threshold = 0.5 ) {

    if ( $remoteip == null || $remoteip == '' )
        echo '<span class="error">'. esc_html__("For security reasons, you must pass the remote ip to reCAPTCHA!", "profile-builder") .'</span><br/><br/>';

    // Discard empty solution submissions
    if ($response == null || strlen($response) == 0) {
        $recaptchaResponse = new wppb_ReCaptchaResponse();

        if( isset( $_POST['wppb_recaptcha_load_error'] ) && wp_verify_nonce( sanitize_text_field( $_POST['wppb_recaptcha_load_error'] ), 'wppb_recaptcha_init_error' ) )
            $recaptchaResponse->is_valid = true;
        else
            $recaptchaResponse->is_valid = false;

        return $recaptchaResponse;
    }

    $source = apply_filters( 'wppb_recaptcha_custom_field_source', 'www.google.com' );

    $getResponse = _wppb_submitHTTPGet(
        "https://".$source."/recaptcha/api/siteverify?",
        array (
            'secret' => $privkey,
            'remoteip' => $remoteip,
            'response' => $response
        )
    );

    $answers = json_decode($getResponse, true);
    $recaptchaResponse = new wppb_ReCaptchaResponse();

    if (trim($answers ['success']) == true) {
        if ( array_key_exists( 'score', $answers ) ) {
            $recaptchaResponse->is_valid = ($answers['score'] >= $score_threshold);
        } else {
            $recaptchaResponse->is_valid = true;
        }
    } else {
        $recaptchaResponse->is_valid = false;
    }

    return $recaptchaResponse;

}

/* the function to display error message on the registration page */
function wppb_validate_captcha_response( $publickey, $privatekey, $score_threshold = 0.5 ){
    if (isset($_POST['g-recaptcha-response'])){
        $recaptcha_response_field = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
    } else {
        $recaptcha_response_field = '';
    }

    $already_validated = false;
    $saved = get_option( 'wppb_recaptcha_validations', array() );

    if( isset( $saved[ $recaptcha_response_field ] ) && $saved[ $recaptcha_response_field ] == true ){
        $already_validated = true;

        if( !wp_doing_ajax() ){
            unset( $saved[ $recaptcha_response_field ] );

            update_option( 'wppb_recaptcha_validations', $saved, false );
        }
    }

    if( !$already_validated ){

        if( isset( $_SERVER["REMOTE_ADDR"] ) ){
            $resp = wppb_recaptcha_check_answer($privatekey, sanitize_text_field( $_SERVER["REMOTE_ADDR"] ), $recaptcha_response_field, $score_threshold );

            if( isset( $resp ) ){
                $already_validated = ( ( !$resp->is_valid ) ? false : true );
            }
        }

    }

    // Save valid results when they are being triggered from an ajax request
    if( wp_doing_ajax() && isset( $_POST['action'] ) && $_POST['action'] == 'pms_validate_checkout' ){

        $saved = get_option( 'wppb_recaptcha_validations', array() );

        if( $already_validated === true )
            $saved[ $recaptcha_response_field ] = true;

        update_option( 'wppb_recaptcha_validations', $saved, false );

    }

    return $already_validated;

}

/* the function to add reCAPTCHA to the registration form of PB */
function wppb_recaptcha_handler ( $output, $form_location, $field, $user_id, $field_check_errors, $request_data ){
    if ( $field['field'] == 'reCAPTCHA' ){
        $item_title = apply_filters( 'wppb_'.$form_location.'_recaptcha_custom_field_'.$field['id'].'_item_title', wppb_icl_t( 'plugin profile-builder-pro', 'custom_field_'.$field['id'].'_title_translation', $field['field-title'], true ) );
        $item_description = wppb_icl_t( 'plugin profile-builder-pro', 'custom_field_'.$field['id'].'_description_translation', $field['description'], true );

        wppb_recaptcha_set_default_values();

        if ( ($form_location == 'register') && ( isset($field['captcha-pb-forms']) ) && ( strpos($field['captcha-pb-forms'],'pb_register') !== false || ( $field['recaptcha-type'] == 'v3' && wppb_maybe_enable_recaptcha_v3_on_form( $field ) ) ) ) {
            $error_mark = ( ( $field['required'] == 'Yes' ) ? '<span class="wppb-required" title="'.wppb_required_field_error($field["field-title"]).'">*</span>' : '' );

            global $wppb_recaptcha_present;
            $wppb_recaptcha_present = true;

            if ( array_key_exists( $field['id'], $field_check_errors ) )
                $error_mark = '<img src="'.WPPB_PLUGIN_URL.'assets/images/pencil_delete.png" title="'.wppb_required_field_error($field["field-title"]).'"/>';

            $publickey = trim( $field['public-key'] );
            $privatekey = trim( $field['private-key'] );

            if ( empty( $publickey ) || empty( $privatekey ) )
                return '<span class="custom_field_recaptcha_error_message" id="'.$field['meta-name'].'_error_message">'.apply_filters( 'wppb_'.$form_location.'_recaptcha_custom_field_'.$field['id'].'_error_message', __("To use reCAPTCHA you must get an API public key from:", "profile-builder"). '<a href="https://www.google.com/recaptcha/admin/create">https://www.google.com/recaptcha/admin/create</a>' ).'</span>';

            if ( empty($field['recaptcha-type']) || ($field['recaptcha-type'] == 'v2') ) {
                $output = '<label for="recaptcha_response_field">' . $item_title . $error_mark . '</label>' . wppb_recaptcha_get_html($publickey, 'pb_register');
                if (!empty($item_description))
                    $output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';
            }
            else {
                // html for Invisible reCAPTCHA
                $output = wppb_recaptcha_get_html($publickey, 'pb_register');
            }


            return $output;

        }
    }
}
add_filter( 'wppb_output_form_field_recaptcha', 'wppb_recaptcha_handler', 10, 6 );


/* handle reCAPTCHA field validation on PB Register form */
function wppb_check_recaptcha_value( $message, $field, $request_data, $form_location ){
    if( $field['field'] == 'reCAPTCHA' ){
        if ( ( $form_location == 'register' ) && ( isset($field['captcha-pb-forms']) ) && ( strpos($field['captcha-pb-forms'],'pb_register') !== false || ( $field['recaptcha-type'] == 'v3' && wppb_maybe_enable_recaptcha_v3_on_form( $field ) ) ) ) {
            /* theme my login plugin executes the register_errors hook on the frontend on all pages so on our register forms we might have already a recaptcha response
            so do not verify it again or it will fail  */
            global $wppb_recaptcha_response;
            if (!isset($wppb_recaptcha_response)){
                $wppb_recaptcha_response = wppb_validate_captcha_response( trim( $field['public-key'] ), trim( $field['private-key'] ), isset( $field['score-threshold'] ) ? trim( $field['score-threshold'] ) : 0.5 );
            }
            if ( (  $wppb_recaptcha_response == false ) && ( $field['required'] == 'Yes' ) ){
                return wppb_required_field_error($field["field-title"]);
            }
        }
    }
    return $message;
}
add_filter( 'wppb_check_form_field_recaptcha', 'wppb_check_recaptcha_value', 10, 4 );

// Get the reCAPTCHA field information
function wppb_get_recaptcha_field(){
    $wppb_manage_fields = get_option( 'wppb_manage_fields', 'not_found' );
    $field = array();
    if ( $wppb_manage_fields != 'not_found' ) {
        foreach ($wppb_manage_fields as $value) {
            if ($value['field'] == 'reCAPTCHA'){
                $field = $value;
                break;
            }
        }
    }
    return $field;
}

/* Display reCAPTCHA on PB Recover Password form */
function wppb_display_recaptcha_recover_password( $output ){
    $field = wppb_get_recaptcha_field();

    if ( !empty($field) ) {
        $publickey = trim($field['public-key']);
        $item_title = apply_filters('wppb_recover_password_recaptcha_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
        $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

        // check where reCAPTCHA should display and add reCAPTCHA html
        if ( isset($field['captcha-pb-forms']) && ( strpos( $field['captcha-pb-forms'],'pb_recover_password' ) !== false || ( $field['recaptcha-type'] == 'v3' && wppb_maybe_enable_recaptcha_v3_on_form( $field ) ) ) ) {

            global $wppb_recaptcha_present;
            $wppb_recaptcha_present = true;

            if ( empty($field['recaptcha-type']) || ($field['recaptcha-type'] == 'v2') ) {
                $recaptcha_output = '<label for="recaptcha_response_field">' . $item_title . '</label>' . wppb_recaptcha_get_html($publickey, 'pb_recover_password');
                if (!empty($item_description))
                    $recaptcha_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

                $output = str_replace('</ul>', '<li class="wppb-form-field wppb-recaptcha wppb-recaptcha-'. $field['recaptcha-type'] .'">' . $recaptcha_output . '</li>' . '</ul>', $output);
            }
            else {
                // output Invisible reCAPTCHA html
                $output = str_replace('</ul>', '<li class="wppb-form-field wppb-recaptcha wppb-recaptcha-'. $field['recaptcha-type'] .'">' . wppb_recaptcha_get_html($publickey, 'pb_recover_password') . '</li>' . '</ul>', $output);
            }
        }
    }
    return $output;
}
add_filter('wppb_recover_password_generate_password_input','wppb_display_recaptcha_recover_password');

/*  Function that changes the messageNo from the Recover Password form  */
function wppb_recaptcha_change_recover_password_message_no($messageNo) {

        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'recover_password') {
            $field = wppb_get_recaptcha_field();
            if (!empty($field)) {

                global $wppb_recaptcha_response;
                if (!isset($wppb_recaptcha_response)) $wppb_recaptcha_response = wppb_validate_captcha_response( trim( $field['public-key'] ), trim( $field['private-key'] ), isset( $field['score-threshold'] ) ? trim( $field['score-threshold'] ) : 0.5 );

                if ( isset($field['captcha-pb-forms']) && (strpos($field['captcha-pb-forms'], 'pb_recover_password') !== false) ) {

                    if ( ($wppb_recaptcha_response == false ) && ( $field['required'] == 'Yes' ) )
                        $messageNo = '';
                }
            }
        }

        return $messageNo;
}
add_filter('wppb_recover_password_message_no', 'wppb_recaptcha_change_recover_password_message_no');

/*  Function that adds the reCAPTCHA error message on the Recover Password form  */
function wppb_recaptcha_recover_password_displayed_message1( $message ) {
    $field = wppb_get_recaptcha_field();

    if ( !empty($field) ){
        global $wppb_recaptcha_response;
        if (!isset($wppb_recaptcha_response)) $wppb_recaptcha_response = wppb_validate_captcha_response( trim( $field['public-key'] ), trim( $field['private-key'] ), isset( $field['score-threshold'] ) ? trim( $field['score-threshold'] ) : 0.5 );

        if ( isset($field['captcha-pb-forms']) && ( strpos( $field['captcha-pb-forms'],'pb_recover_password' ) !== false ) && ( $wppb_recaptcha_response == false )) {

            // This message is also altered by the plugin-compatibilities.php file, in regards to Captcha plugin ( function wppb_captcha_recover_password_displayed_message1 )
            if (($message == '<p class="wppb-warning">wppb_recaptcha_error</p>') || ($message == '<p class="wppb-warning">wppb_captcha_error</p>'))
                $message = '<p class="wppb-warning">' . wppb_recaptcha_field_error($field["field-title"]) . '</p>';
            else
                $message = $message . '<p class="wppb-warning">' . wppb_recaptcha_field_error($field["field-title"]) . '</p>';

            }
        }

    return $message;
}
add_filter('wppb_recover_password_displayed_message1', 'wppb_recaptcha_recover_password_displayed_message1');

/*  Function that changes the default success message to wppb_recaptcha_error if the reCAPTCHA doesn't validate
    so that we can change the message displayed with the wppb_recover_password_displayed_message1 filter  */
function wppb_recaptcha_recover_password_sent_message_1($message) {

        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'recover_password') {
            $field = wppb_get_recaptcha_field();

            if (!empty($field)) {
                global $wppb_recaptcha_response;
                if (!isset($wppb_recaptcha_response)) $wppb_recaptcha_response = wppb_validate_captcha_response( trim( $field['public-key'] ), trim( $field['private-key'] ), isset( $field['score-threshold'] ) ? trim( $field['score-threshold'] ) : 0.5 );

                if ( isset($field['captcha-pb-forms']) && ( strpos($field['captcha-pb-forms'], 'pb_recover_password') !== false ) && ( $wppb_recaptcha_response == false ) ){
                    $message = 'wppb_recaptcha_error';
                }
            }

        }

        return $message;
}
add_filter('wppb_recover_password_sent_message1', 'wppb_recaptcha_recover_password_sent_message_1');

/* Display reCAPTCHA html on PB Login form */
function wppb_display_recaptcha_login_form($form_part, $args) {

    if( !isset( $args['form_id'] ) || $args['form_id'] != 'wppb-loginform' )
        return $form_part;

    $field = wppb_get_recaptcha_field();

    if ( !empty($field) ) {
        $item_title = apply_filters('wppb_login_recaptcha_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
        $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

        if ( isset($field['captcha-pb-forms']) && ( strpos( $field['captcha-pb-forms'],'pb_login' ) !== false || ( $field['recaptcha-type'] == 'v3' && wppb_maybe_enable_recaptcha_v3_on_form( $field ) ) ) ) { // check where reCAPTCHA should display and add reCAPTCHA html

            global $wppb_recaptcha_present;
            $wppb_recaptcha_present = true;

            if ( empty($field['recaptcha-type']) || ($field['recaptcha-type'] == 'v2') ) {
                $recaptcha_output = '<label for="recaptcha_response_field">' . $item_title . '</label>' . wppb_recaptcha_get_html(trim($field['public-key']), 'pb_login');
                if (!empty($item_description))
                    $recaptcha_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

                $form_part .= '<div class="wppb-form-field wppb-recaptcha wppb-recaptcha-'. $field['recaptcha-type'] .'">' . $recaptcha_output . '</div>';
            }
            else {
                //output Invisible reCAPTCHA html
//                $form_part .= wppb_recaptcha_get_html(trim($field['public-key']), 'pb_login');
                $form_part .= '<div class="wppb-form-field wppb-recaptcha wppb-recaptcha-'. $field['recaptcha-type'] .'">' . wppb_recaptcha_get_html(trim($field['public-key']), 'pb_login') . '</div>';
            }
        }
    }

    return $form_part;
}
add_filter('login_form_middle', 'wppb_display_recaptcha_login_form', 10, 2);

/* Display reCAPTCHA html on default WP Login form */
function wppb_display_recaptcha_wp_login_form(){
    $field = wppb_get_recaptcha_field();

    if ( !empty($field) ) {
        $item_title = apply_filters('wppb_login_recaptcha_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
        $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

        if ( isset($field['captcha-wp-forms']) && (strpos( $field['captcha-wp-forms'],'default_wp_login' ) !== false) ) { // check where reCAPTCHA should display and add reCAPTCHA html

            global $wppb_recaptcha_present;
            $wppb_recaptcha_present = true;

            if ( empty($field['recaptcha-type']) || ($field['recaptcha-type'] == 'v2') ) {
                $recaptcha_output = '<label for="recaptcha_response_field" style="padding-left:15px; padding-bottom:7px;">' . $item_title . '</label>' . wppb_recaptcha_get_html(trim($field['public-key']));
                if (!empty($item_description))
                    $recaptcha_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

                echo '<div class="wppb-form-field wppb-recaptcha" style="margin-left:-14px; margin-bottom: 15px;">' . $recaptcha_output . '</div>'; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ /* properly escaped when constructing the var */
            }
            else {
                // output Invisible reCAPTCHA html
                echo wppb_recaptcha_get_html( trim($field['public-key'])); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ /* properly escaped when constructing the var */
            }
        }
    }
}
add_action( 'login_form', 'wppb_display_recaptcha_wp_login_form' );

//Show reCAPTCHA error on Login form (both default and PB one)
function wppb_recaptcha_login_wp_error_message($user){
    //make sure you're on a Login form (WP or PB)
    if ( isset( $_POST['log'] ) && !is_wp_error($user) && !isset( $_POST['pms_login'] ) ) {

        $field = wppb_get_recaptcha_field();
        if ( !empty($field) ){
            global $wppb_recaptcha_response;

            if (!isset($wppb_recaptcha_response)) $wppb_recaptcha_response = wppb_validate_captcha_response( trim( $field['public-key'] ), trim( $field['private-key'] ), isset( $field['score-threshold'] ) ? trim( $field['score-threshold'] ) : 0.5 );

            $recaptcha_error_message = __('reCaptcha could not be verified. Please try again.','profile-builder');

            if( isset( $field['recaptcha-type'] ) && $field['recaptcha-type'] === 'v2' ) {
                $recaptcha_error_message = __('Please enter a (valid) reCAPTCHA value','profile-builder');
            }

            //reCAPTCHA error for displaying on the PB login form
            if ( isset($_POST['wppb_login']) && ($_POST['wppb_login'] == true) ) {

                // it's a PB login form, check if we have a reCAPTCHA on it and display error if not valid
                if ((isset($field['captcha-pb-forms'])) && (strpos($field['captcha-pb-forms'], 'pb_login') !== false || ( $field['recaptcha-type'] == 'v3' && wppb_maybe_enable_recaptcha_v3_on_form( $field ) ) ) && ($wppb_recaptcha_response == false)) {
                    $user = new WP_Error('wppb_recaptcha_error', $recaptcha_error_message);
                    remove_filter( 'authenticate', 'wp_authenticate_username_password',  20, 3 );
                    remove_filter( 'authenticate', 'wp_authenticate_email_password',     20, 3 );
                }

            }
            else {
                //reCAPTCHA error for displaying on the default WP login form
                if (isset($field['captcha-wp-forms']) && (strpos($field['captcha-wp-forms'], 'default_wp_login') !== false) && ($wppb_recaptcha_response == false)) {
                    $user = new WP_Error('wppb_recaptcha_error', $recaptcha_error_message);
                    remove_filter( 'authenticate', 'wp_authenticate_username_password',  20, 3 );
                    remove_filter( 'authenticate', 'wp_authenticate_email_password',     20, 3 );
                }

            }
        }
    }
    return $user;
}
add_filter('authenticate','wppb_recaptcha_login_wp_error_message', 9);

/**
 * Add a reCAPTCHA type–specific CSS class to the Register form field
 *
 * @param $classes - existing field classes
 * @param $field   - field data
 * @return mixed|string
 */
function wppb_register_form_recaptcha_type_class( $classes, $field ){

    if ( isset( $field['field'] ) && $field['field'] == 'reCAPTCHA' && ! empty( $field['recaptcha-type'] ) )
        $classes .= ' wppb-recaptcha-' . $field['recaptcha-type'];

    return $classes;
}
add_filter( 'wppb_field_css_class', 'wppb_register_form_recaptcha_type_class', 20, 2);

// Display reCAPTCHA html on default WP Recover Password form
function wppb_display_recaptcha_default_wp_recover_password() {
    $field = wppb_get_recaptcha_field();

    if (!empty($field)) {
        $publickey = trim($field['public-key']);
        $item_title = apply_filters('wppb_recover_password_recaptcha_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
        $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

        if ( isset($field['captcha-wp-forms']) && (strpos( $field['captcha-wp-forms'], 'default_wp_recover_password') !== false) ) { // check where reCAPTCHA should display and add reCAPTCHA html

            global $wppb_recaptcha_present;
            $wppb_recaptcha_present = true;

            if ( empty($field['recaptcha-type']) || ($field['recaptcha-type'] == 'v2') ){
                $recaptcha_output = '<label for="recaptcha_response_field" style="padding-left:15px; padding-bottom:7px;">' . $item_title . '</label>' . wppb_recaptcha_get_html($publickey);
                if (!empty($item_description))
                    $recaptcha_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

                echo '<div class="wppb-form-field wppb-recaptcha" style="margin-left:-14px; margin-bottom: 15px;">' . $recaptcha_output . '</div>'; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ /* properly escaped when constructing the var */
            }
            else {
                // output Invisible reCAPTCHA html
                echo wppb_recaptcha_get_html($publickey); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ /* properly escaped when constructing the var */
            }
        }
    }
}
add_action('lostpassword_form','wppb_display_recaptcha_default_wp_recover_password');

// Verify and show reCAPTCHA errors for default WP Recover Password
function wppb_verify_recaptcha_default_wp_recover_password(){

    // If field 'username or email' is empty - return
    if( isset( $_REQUEST['user_login'] ) && "" === $_REQUEST['user_login'] )
        return;

    $field = wppb_get_recaptcha_field();
    if ( !empty($field) ){
        global $wppb_recaptcha_response;
        if (!isset($wppb_recaptcha_response)) $wppb_recaptcha_response = wppb_validate_captcha_response( trim( $field['public-key'] ), trim( $field['private-key'] ), isset( $field['score-threshold'] ) ? trim( $field['score-threshold'] ) : 0.5 );

        $recaptcha_error_message = esc_html__('reCaptcha could not be verified. Please try again.','profile-builder');

        if( isset( $field['recaptcha-type'] ) && $field['recaptcha-type'] === 'v2' ) {
            $recaptcha_error_message = esc_html__('Please enter a (valid) reCAPTCHA value','profile-builder');
        }

    // If reCAPTCHA not entered or incorrect reCAPTCHA answer
        if ( isset( $_REQUEST['g-recaptcha-response'] ) && ( ( "" ===  $_REQUEST['g-recaptcha-response'] )  || ( $wppb_recaptcha_response == false ) ) ) {
            wp_die( esc_html( $recaptcha_error_message ) . '<br />' . esc_html__( "Click the BACK button on your browser, and try again.", 'profile-builder' ) ) ;
        }
    }
}
add_action('lostpassword_post','wppb_verify_recaptcha_default_wp_recover_password');

/* Display reCAPTCHA html on default WP Register form */
function wppb_display_recaptcha_default_wp_register(){
    $field = wppb_get_recaptcha_field();

    if (!empty($field)) {

            $publickey = trim($field['public-key']);
            $item_title = apply_filters('wppb_register_recaptcha_custom_field_' . $field['id'] . '_item_title', wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_title_translation', $field['field-title'], true));
            $item_description = wppb_icl_t('plugin profile-builder-pro', 'custom_field_' . $field['id'] . '_description_translation', $field['description'], true);

            wppb_recaptcha_set_default_values();
            if (isset($field['captcha-wp-forms']) && (strpos($field['captcha-wp-forms'], 'default_wp_register') !== false)) { // check where reCAPTCHA should display and add reCAPTCHA html

                global $wppb_recaptcha_present;
                $wppb_recaptcha_present = true;

                if ( empty($field['recaptcha-type']) || ($field['recaptcha-type'] == 'v2') ) {
                    $recaptcha_output = '<label for="recaptcha_response_field" style="padding-left:15px; padding-bottom:7px;">' . $item_title . '</label>' . wppb_recaptcha_get_html($publickey);
                    if (!empty($item_description))
                        $recaptcha_output .= '<span class="wppb-description-delimiter">' . $item_description . '</span>';

                    echo '<div class="wppb-form-field wppb-recaptcha" style="margin-left:-14px; margin-bottom: 15px;">' . $recaptcha_output . '</div>'; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ /* properly escaped when constructing the var */
                }
                else {
                    // output reCAPTCHA html
                    echo wppb_recaptcha_get_html($publickey); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ /* properly escaped when constructing the var */
                }
            }
    }
}
add_action( 'register_form', 'wppb_display_recaptcha_default_wp_register' );

// Verify and show reCAPTCHA errors for default WP Register form
function wppb_verify_recaptcha_default_wp_register( $errors ){

    $field = wppb_get_recaptcha_field();
    if ( !empty($field) ){
        global $wppb_recaptcha_response;
        if (!isset($wppb_recaptcha_response)) $wppb_recaptcha_response = wppb_validate_captcha_response( trim( $field['public-key'] ), trim( $field['private-key'] ), isset( $field['score-threshold'] ) ? trim( $field['score-threshold'] ) : 0.5 );

        $recaptcha_error_message = esc_html__('reCaptcha could not be verified. Please try again.','profile-builder');

        if( isset( $field['recaptcha-type'] ) && $field['recaptcha-type'] === 'v2' ) {
            $recaptcha_error_message = esc_html__('Please enter a (valid) reCAPTCHA value','profile-builder');
        }

        // If reCAPTCHA not entered or incorrect reCAPTCHA answer
        if ( isset( $_REQUEST['g-recaptcha-response'] ) && ( ( "" ===  $_REQUEST['g-recaptcha-response'] )  || ( $wppb_recaptcha_response == false ) ) ) {
            $errors->add( 'wppb_recaptcha_error', $recaptcha_error_message );
        }
    }

return $errors;
}
add_filter('registration_errors','wppb_verify_recaptcha_default_wp_register');

// set default values in case there's already an existing reCAPTCHA field in Manage fields (when upgrading)
function wppb_recaptcha_set_default_values() {
    $manage_fields = get_option('wppb_manage_fields', 'not_set');
    if ($manage_fields != 'not_set') {
        foreach ($manage_fields as $key => $value) {
            if ($value['field'] == 'reCAPTCHA') {
                if ( !isset($value['captcha-pb-forms']) ) $manage_fields[$key]['captcha-pb-forms'] = 'pb_register';
                if ( !isset($value['captcha-wp-forms']) ) $manage_fields[$key]['captcha-wp-forms'] = 'default_wp_register';
                if ( !isset($value['recaptcha-type']) )   $manage_fields[$key]['recaptcha-type'] = 'v2';
            }
        }
        update_option('wppb_manage_fields', $manage_fields);
    }
}

if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'paid-member-subscriptions/index.php' ) && defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '2.12.9', '<' ) ) {

    $notifications = WPPB_Plugin_Notifications::get_instance();

    // this must be unique
    $notification_id = 'wppb_pms_recaptcha_compatibility';

    $notification_message = '<p>' . __( 'reCAPTCHA v3 is not compatible with Paid Member Subscriptions versions that are older than <strong>2.12.7</strong>. <br>Please update Paid Member Subscriptions to a newer version to avoid any issues.', 'profile-builder' ) . '</p>';
    $notification_message .= '<a href="' . wp_nonce_url( add_query_arg( array( 'wppb_dismiss_admin_notification' => $notification_id ) ), 'wppb_plugin_notice_dismiss' ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'profile-builder' ) . '</span></a>';

    // add the notification  (we need to add the "notice is-dismissible" classes for the dismiss button to be correctly positioned)
    $notifications->add_notification( $notification_id, $notification_message, 'wppb-notice notice notice-warning is-dismissible', false );

}

// Make sure the reCAPTCHA field score threshold is set correctly
function wppb_check_recaptcha_fields_settings( $values ) {
    if( isset( $values['field'] ) && $values['field'] == 'reCAPTCHA' ) {
        if ( empty( $values['score-threshold'] ) || $values['score-threshold'] < 0 || $values['score-threshold'] > 1 ) {
            $values['score-threshold'] = 0.5;
        }
    }

    return $values;
}
add_action( 'wck_update_meta_filter_values_wppb_manage_fields', 'wppb_check_recaptcha_fields_settings' );

function wppb_maybe_enable_recaptcha_v3_on_form( $recaptcha_field ){

    // Static cache to avoid repeated calculations
    static $cache = array();

    // Early validation checks
    if( empty( $recaptcha_field ) || empty( $recaptcha_field['captcha-pb-forms'] ) )
        return false;

    $post_id = get_the_ID();
    $post    = get_post( $post_id );

    // Check if post is set, if not return false
    if( empty( $post ) || empty( $post->post_content ) )
        return false;

    // Create cache key based on post ID and captcha forms configuration
    $cache_key = md5( $post_id . serialize( $recaptcha_field['captcha-pb-forms'] ) );

    // Return cached result if available
    if( isset( $cache[ $cache_key ] ) )
        return $cache[ $cache_key ];

    $wppb_recaptcha_v3 = false;

    // Define form configurations for loop processing
    $form_configs = array(
        'pb_register' => array(
            'shortcode_pattern' => '[wppb-register',
            'block_name'        => 'wppb/register',
            'other_forms'       => array(
                array( 'shortcode' => '[wppb-login', 'block' => 'wppb/login', 'form_type' => 'pb_login' ),
                array( 'shortcode' => '[wppb-recover-password', 'block' => 'wppb/recover-password', 'form_type' => 'pb_recover_password' )
            )
        ),
        'pb_login' => array(
            'shortcode_pattern' => '[wppb-login',
            'block_name'        => 'wppb/login',
            'other_forms'       => array(
                array( 'shortcode' => '[wppb-register', 'block' => 'wppb/register', 'form_type' => 'pb_register' ),
                array( 'shortcode' => '[wppb-recover-password', 'block' => 'wppb/recover-password', 'form_type' => 'pb_recover_password' )
            )
        ),
        'pb_recover_password' => array(
            'shortcode_pattern' => '[wppb-recover-password',
            'block_name'        => 'wppb/recover-password',
            'other_forms'       => array(
                array( 'shortcode' => '[wppb-register', 'block' => 'wppb/register', 'form_type' => 'pb_register' ),
                array( 'shortcode' => '[wppb-login', 'block' => 'wppb/login', 'form_type' => 'pb_login' )
            )
        )
    );

    // Process each form type using loop
    foreach( $form_configs as $form_type => $config ) {
        // Skip if this form type is already enabled in captcha-pb-forms
        if( strpos( $recaptcha_field['captcha-pb-forms'], $form_type ) !== false )
            continue;

        // Check if current form type exists on the page
        $current_form_exists = ( strpos( $post->post_content, $config['shortcode_pattern'] ) !== false || has_block( $config['block_name'] ) );
        
        if( $current_form_exists ) {
            // Check if any other enabled form types also exist on the page
            foreach( $config['other_forms'] as $other_form ) {
                $other_form_exists = ( strpos( $post->post_content, $other_form['shortcode'] ) !== false || has_block( $other_form['block'] ) );
                $other_form_enabled = ( strpos( $recaptcha_field['captcha-pb-forms'], $other_form['form_type'] ) !== false );
                
                if( $other_form_exists && $other_form_enabled ) {
                    $wppb_recaptcha_v3 = true;
                    break 2; // Break out of both loops since we found a match
                }
            }
        }
    }

    // Cache the result
    $cache[ $cache_key ] = $wppb_recaptcha_v3;

    return $wppb_recaptcha_v3;

}
