<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$redirect_url = wppb_toolbox_get_settings( 'fields', 'redirect-if-empty-required-url' );

if ( !empty( $redirect_url ) )
    add_action( 'template_redirect', 'wppb_toolbox_redirect_if_empty_required' );

function wppb_toolbox_redirect_if_empty_required() {
    if ( current_user_can( 'manage_options' ) )
         return;

	$user_id      = get_current_user_id();
	$current_url  = wppb_curpageurl();
	$redirect_url = get_permalink( wppb_toolbox_get_settings( 'fields', 'redirect-if-empty-required-url' ) );

	if ( !empty( $user_id ) && ( $current_url != $redirect_url ) && apply_filters( 'wppb_toolbox_redirect_if_empty_required', true, $user_id, $current_url, $redirect_url ) ) {

		$fields            = get_option( 'wppb_manage_fields', array() );
		$without_meta_name = array( 'user_url' => 'Default - Website', 'display_name' => 'Default - Display name publicly as' );

		foreach ( $fields as $field ){
            // Skip fields that are hidden by conditional logic
            if ( !empty( $field["conditional-logic-enabled"] ) && !empty( $field["conditional-logic"] ) && $field["conditional-logic-enabled"] === 'yes' ) {
                $field_conditional_logic = json_decode($field["conditional-logic"], true);

                // Set initial conditions based on action_type. If action type is show then it means that at the beginning it is hidden
                if( $field_conditional_logic['action_type'] == 'show' )
                    $hide_field = true;
                else if( $field_conditional_logic['action_type'] == 'hide' )
                    $hide_field = false;

                // If all the rules must be met then we start with a true value and pass through all of them and if one is not true then we invalidate them
                if ($field_conditional_logic['logic_type'] == 'all') {
                    $all_conditions = true;
                }

                if (!empty($field_conditional_logic['rules'])) {
                    foreach ($field_conditional_logic['rules'] as $rule) {
                        // Get the value of the field that this condition depends on
                        $dependency_field_meta_name = '';
                        foreach ($fields as $dependency_field) {
                            if ($dependency_field['id'] == $rule['field']) {
                                if (!empty($dependency_field['meta-name'])) {
                                    $dependency_field_meta_name = $dependency_field['meta-name'];
                                } else {
                                    // Handle default fields
                                    if (class_exists('Wordpress_Creation_Kit_PB')) {
                                        switch (Wordpress_Creation_Kit_PB::wck_generate_slug($dependency_field['field'])) {
                                        case 'default-username':
                                            $dependency_field_meta_name = 'username';
                                            break;
                                        case 'default-display-name-publicly-as':
                                            $dependency_field_meta_name = "display_name";
                                            break;
                                        case 'default-e-mail':
                                            $dependency_field_meta_name = 'email';
                                            break;
                                        case 'default-website':
                                            $dependency_field_meta_name = 'website';
                                            break;
                                        case 'select-user-role':
                                            $dependency_field_meta_name = 'custom_field_user_role';
                                            break;
                                        case 'subscription-plans':
                                            $dependency_field_meta_name = 'subscription_plans';
                                            break;
                                        }
                                    }
                                }
                                break;
                            }
                        }

                        if (!empty($dependency_field_meta_name)) {
                            // Get the value of the dependency field
                            $dependency_value = get_user_meta($user_id, $dependency_field_meta_name, true);

                            if ($dependency_value !== false) {
                                if (!is_array($dependency_value)) {
                                    // Check if the value is a comma-separated string
                                    if (is_string($dependency_value) && strpos($dependency_value, ',') !== false) {
                                        $dependency_value = array_map('trim', explode(',', $dependency_value));
                                    } else {
                                        $dependency_value = array($dependency_value);
                                    }
                                }

                                if ($rule['operator'] == 'is' && in_array($rule['value'], $dependency_value)) {
                                    if ($field_conditional_logic['logic_type'] == 'any') {
                                        $hide_field = !$hide_field;
                                        break;
                                    }
                                } else if ($rule['operator'] == 'is' && !in_array($rule['value'], $dependency_value)) {
                                    if ($field_conditional_logic['logic_type'] == 'all') {
                                        $all_conditions = false;
                                        break;
                                    }
                                }

                                if ($rule['operator'] == 'is not' && !in_array($rule['value'], $dependency_value)) {
                                    if ($field_conditional_logic['logic_type'] == 'any') {
                                        $hide_field = !$hide_field;
                                        break;
                                    }
                                } else if ($rule['operator'] == 'is not' && in_array($rule['value'], $dependency_value)) {
                                    if ($field_conditional_logic['logic_type'] == 'all') {
                                        $all_conditions = false;
                                        break;
                                    }
                                }
                            } else {
                                if ($field_conditional_logic['logic_type'] == 'all') {
                                    $all_conditions = false;
                                }
                            }
                        }
                    }
                }

                // If all the rules must be valid and the boolean is still true then the initial state of the field changed
                if ($field_conditional_logic['logic_type'] == 'all' && $all_conditions) {
                    $hide_field = !$hide_field;
                }

                // If the field is hidden by conditional logic, skip it
                if ($hide_field) {
                    continue;
                }
            }

			if ( $field['required'] == 'Yes' && !empty( $field['meta-name'] ) && $field['field'] != 'Checkbox (Terms and Conditions)' ){

                if( $field['meta-name'] == 'map' && function_exists( 'wppb_get_user_map_markers' ) )
                    $value = wppb_get_user_map_markers( $user_id, $field['meta-name'] );
                else 
                    $value = get_user_meta( $user_id, $field['meta-name'], true );

                if ( empty( $value ) ){
                    wp_redirect( $redirect_url );
                    exit();
                }
            }

            if( $field['required'] == 'Yes' && ( $field['field'] == 'Default - Website' || $field['field'] == 'Default - Display name publicly as' ) ){
                $user = get_userdata( $user_id );

                $key = array_search( $field['field'], $without_meta_name );
                $value = $user->$key;

                if ( empty( $value ) ){
                    wp_redirect( $redirect_url );
                    exit();
                }
            }
		}
	}
}
