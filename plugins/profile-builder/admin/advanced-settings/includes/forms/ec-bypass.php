<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'wppb_email_confirmation_on_register', 'wppb_toolbox_bypass_email_confirmation', 2, 20 );
function wppb_toolbox_bypass_email_confirmation( $email_confirmation, $global_request ) {
    $forms = wppb_toolbox_get_settings( 'forms', 'ec-bypass' );

    if ( ! empty( $global_request['form_name'] ) && is_array( $forms ) && in_array( $global_request['form_name'], $forms, true ) ) {
        return 'no';
    }

    return $email_confirmation;
}

add_filter( 'wppb_register_send_credentials_via_email', 'wppb_toolbox_force_enable_send_credentials_via_email', 2, 20 );
function wppb_toolbox_force_enable_send_credentials_via_email( $send_credentials, $user_id, $form_args ) {

    $forms = wppb_toolbox_get_settings( 'forms', 'ec-bypass' );

    if ( ! empty( $form_args['form_name'] ) && is_array( $forms ) && in_array( $form_args['form_name'], $forms, true ) ) {
        return 'no';
    }

    return $send_credentials;

}
