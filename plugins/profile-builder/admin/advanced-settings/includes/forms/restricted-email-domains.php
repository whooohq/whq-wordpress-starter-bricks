<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wppb_toolbox_check_email_domain( $message, $field, $request_data, $form_location ) {

    if( empty( $request_data['email'] ) )
        return $message;

    $type = wppb_toolbox_get_settings( 'forms', 'restricted-email-domains-type' );

    if ( $type == false ) return $message;

    $restricted_domains = wppb_toolbox_get_settings( 'forms', 'restricted-email-domains-data' );

    if ( $restricted_domains == false ) return $message;

    $domain = strtolower( substr( strrchr( trim( $request_data['email'] ), '@' ), 1 ) );
    $validation_message = wppb_toolbox_get_settings( 'forms', 'restricted-email-domains-message' );
    $validation_message = wppb_icl_t( 'plugin profile-builder-pro', 'restricted_email_domains_message_translation', $validation_message, true );

    if ( $type == 'allow' ) {

        if ( !in_array( $domain, $restricted_domains ) )
            return $validation_message;

    } else if ( $type == 'deny' ) {

        if ( in_array( $domain, $restricted_domains ) )
            return $validation_message;

    }

    return $message;
}
add_filter( 'wppb_check_form_field_default-e-mail', 'wppb_toolbox_check_email_domain', 20, 4 );
