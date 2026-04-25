<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* handle field output */
function wppb_password_handler( $output, $form_location, $field, $user_id, $field_check_errors, $request_data ){
	$current_password_error = wppb_password_has_current_password_error( $field, $form_location, $request_data, $user_id );

	$item_title = apply_filters( 'wppb_'.$form_location.'_password_item_title', wppb_icl_t( 'plugin profile-builder-pro', 'default_field_'.$field['id'].'_title_translation', $field['field-title'], true ) );
	$item_description = wppb_icl_t( 'plugin profile-builder-pro', 'default_field_'.$field['id'].'_description_translation', $field['description'], true );

	if ( $form_location != 'back_end' ){
		$error_mark = ( ( $field['required'] == 'Yes' ) ? ( $form_location != 'edit_profile' ? '<span class="wppb-required" title="'.wppb_required_field_error($field["field-title"]).'">*</span>' : '' ) : '' );
					
		if ( array_key_exists( $field['id'], $field_check_errors ) && ! $current_password_error )
			$error_mark = '<img src="'.WPPB_PLUGIN_URL.'assets/images/pencil_delete.png" title="'.wppb_required_field_error($field["field-title"]).'"/>';

		$extra_attr = apply_filters( 'wppb_extra_attribute', '', $field, $form_location );

        $autocomplete = 'off';

        if( $form_location == 'edit_profile' )
            $autocomplete = 'new-password';

	        $subfields_output = '';

	        if ( wppb_password_should_ask_for_current_password( $field, $form_location, $user_id ) ) {
		        $subfields_output .= '
					<div class="wppb-password-subfield wppb-current-password-subfield' . ( $current_password_error ? ' wppb-current-password-subfield-error' : '' ) . '">
						<label for="wppb_current_passw">' . __( 'Current Password', 'profile-builder' ) . '</label>
			            <span class="wppb-password-field-container">
						    <input class="text-input '. apply_filters( 'wppb_fields_extra_css_class', '', $field ) .'" name="wppb_current_passw" maxlength="'. apply_filters( 'wppb_maximum_character_length', 70, $field ) .'" type="password" id="wppb_current_passw" value="" autocomplete="current-password" '. $extra_attr .'/>
						    '. wppb_password_visibility_toggle_html() .' <!-- add the HTML for the visibility toggle -->
						</span>
					</div>';
	        }

	        $subfields_output .= '
				<div class="wppb-password-subfield wppb-new-password-subfield">
					<label for="passw1">' . $item_title.$error_mark . '</label>
		            <span class="wppb-password-field-container">
					    <input class="text-input '. apply_filters( 'wppb_fields_extra_css_class', '', $field ) .'" name="passw1" maxlength="'. apply_filters( 'wppb_maximum_character_length', 70, $field ) .'" type="password" id="passw1" value="" autocomplete="'. esc_attr( $autocomplete ) .'" '. $extra_attr .'/>
					    '. wppb_password_visibility_toggle_html() .' <!-- add the HTML for the visibility toggle -->
					</span>
				</div>';

	        $output = '<div class="wppb-password-group">' . $subfields_output . '</div>';

        if( ! empty( $item_description ) )
            $output .= '<span class="wppb-description-delimiter">'. $item_description .' '. wppb_password_length_text() .' '. wppb_password_strength_description() .'</span>';
        else
            $output .= '<span class="wppb-description-delimiter">'. wppb_password_length_text() .' '. wppb_password_strength_description() .'</span>';

        /* if we have active the password strength checker */
        $output .= wppb_password_strength_checker_html();

	}
		
	return apply_filters( 'wppb_'.$form_location.'_password', $output, $form_location, $field, $user_id, $field_check_errors, $request_data );
}
add_filter( 'wppb_output_form_field_default-password', 'wppb_password_handler', 10, 6 );

/* handle field validation */
function wppb_check_password_value( $message, $field, $request_data, $form_location, $field_check_errors = '', $user_id = 0 ){

	if ( $form_location == 'register' ){
		if ( ( isset( $request_data['passw1'] ) && ( trim( $request_data['passw1'] ) == '' ) ) && ( $field['required'] == 'Yes' ) )
			return wppb_required_field_error($field["field-title"]);
		
		elseif ( !isset( $request_data['passw1'] ) && ( $field['required'] == 'Yes' ) )
			return wppb_required_field_error($field["field-title"]);
	}

		if ( wppb_password_should_ask_for_current_password( $field, $form_location, $user_id ) && isset( $request_data['passw1'] ) && trim( $request_data['passw1'] ) !== '' && !wppb_password_was_changed_in_current_request() ) {
                $current_password = isset( $request_data['wppb_current_passw'] ) ? trim( wp_unslash( (string) $request_data['wppb_current_passw'] ) ) : '';

                if ( empty( $request_data['wppb_current_passw'] ) ) {
                    return __( 'Please enter your current password to change your password.', 'profile-builder' );
                }

                $edited_user_id = ! empty( $user_id ) ? absint( $user_id ) : get_current_user_id();
                $user           = get_userdata( $edited_user_id );

                if ( empty( $user ) || ! wp_check_password( $current_password, $user->data->user_pass, $edited_user_id ) ) {
                    return __( 'The current password you entered is incorrect.', 'profile-builder' );
                }
		}

	    if ( isset( $request_data['passw1'] ) && trim( $request_data['passw1'] ) != '' ){
	        $wppb_generalSettings = get_option( 'wppb_general_settings' );

        if( wppb_check_password_length( $request_data['passw1'] ) )
            return '<br/>'. sprintf( __( "The password must have the minimum length of %s characters", "profile-builder" ), $wppb_generalSettings['minimum_password_length'] );


        if( wppb_check_password_strength() ){
            return '<br/>' . sprintf( __( "The password must have a minimum strength of %s", "profile-builder" ), wppb_check_password_strength() );
        }
    }

    return $message;
}
add_filter( 'wppb_check_form_field_default-password', 'wppb_check_password_value', 10, 6 );

/* handle field save */
function wppb_userdata_add_password( $userdata, $global_request, $form_args ){
    if( wppb_field_exists_in_form( 'Default - Password', $form_args ) ) {
        if (isset($global_request['passw1']) && (trim($global_request['passw1']) != ''))
            $userdata['user_pass'] = trim($global_request['passw1']);
    }

	return $userdata;
}
add_filter( 'wppb_build_userdata', 'wppb_userdata_add_password', 10, 3 );

function wppb_password_should_ask_for_current_password( $field, $form_location, $user_id = 0 ) {
	if ( $form_location !== 'edit_profile' ) {
		return false;
	}

	if ( empty( $field['ask-current-password'] ) || $field['ask-current-password'] !== 'yes' ) {
		return false;
	}

	$edited_user_id = ! empty( $user_id ) ? absint( $user_id ) : get_current_user_id();

	return $edited_user_id === get_current_user_id();
}

function wppb_password_has_current_password_error( $field, $form_location, $request_data, $user_id = 0 ) {
	if ( ! wppb_password_should_ask_for_current_password( $field, $form_location, $user_id ) ) {
		return false;
	}

	if ( wppb_password_was_changed_in_current_request() ) {
		return false;
	}

	if ( empty( $request_data['passw1'] ) || trim( $request_data['passw1'] ) === '' ) {
		return false;
	}

	if ( empty( $request_data['wppb_current_passw'] ) ) {
		return true;
	}

	$current_password = isset( $request_data['wppb_current_passw'] ) ? trim( wp_unslash( (string) $request_data['wppb_current_passw'] ) ) : '';
	$edited_user_id = ! empty( $user_id ) ? absint( $user_id ) : get_current_user_id();
	$user           = get_userdata( $edited_user_id );

	return empty( $user ) || ! wp_check_password( $current_password, $user->data->user_pass, $edited_user_id );
}

function wppb_password_was_changed_in_current_request() {
	return did_action( 'wppb_edit_profile_password_changed' ) > 0;
}

function wppb_password_add_current_password_error_class( $classes, $field ) {
	if ( empty( $field['field'] ) || $field['field'] !== 'Default - Password' ) {
		return $classes;
	}

	if ( ! is_user_logged_in() || empty( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'edit_profile' ) {
		return $classes;
	}

	$user_id = get_current_user_id();

	if ( ( ! is_multisite() && current_user_can( 'edit_users' ) ) || ( is_multisite() && ( current_user_can( 'remove_users' ) || current_user_can( 'manage_options' ) ) ) ) {
		if ( ! empty( $_REQUEST['edit_user'] ) ) {
			$user_id = absint( $_REQUEST['edit_user'] );
		}
	}

	if ( wppb_password_has_current_password_error( $field, 'edit_profile', $_REQUEST, $user_id ) ) {
		$classes .= ' wppb-current-password-field-error';
	}

	return $classes;
}
add_filter( 'wppb_field_css_class', 'wppb_password_add_current_password_error_class', 10, 2 );
