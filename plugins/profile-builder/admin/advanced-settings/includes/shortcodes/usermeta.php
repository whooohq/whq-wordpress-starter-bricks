<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('user_meta', 'wppb_toolbox_usermeta_handler');
function wppb_toolbox_usermeta_handler( $atts, $content=null){

	$user_id = '';

	if( isset( $atts['user_id'] ) ){

		if( ( !is_multisite() && current_user_can( 'edit_users' ) ) || ( is_multisite() && ( current_user_can( 'remove_users' ) || current_user_can( 'manage_options' ) ) ) )
			$user_id = absint( $atts['user_id'] );
		
	}

	if( empty( $user_id ) ){
		$user    = wp_get_current_user();
		$user_id = $user->ID;
	}

	if ( !isset( $atts['size'] ) ){
		$atts['size'] = '50';
	}
	if ( !isset( $atts['pre'] ) ) {
		$atts['pre'] = '';
	}
	if ( !isset( $atts['post'] ) ) {
		$atts['post'] = '';
	}
	if ( !isset( $atts['wpautop'] ) ) {
		$atts['wpautop'] = '';
	}

	if( isset( $atts['key'] ) && in_array( $atts['key'], array( 'user_pass', 'user_activation_key' ) ) )
		return;

	$user = new WP_User( $user_id );

	if ( !$user->exists() ) return;

	if ( !array_key_exists( 'key', $atts ) ) return;

	if( $atts['key'] == 'avatar' ){
		return wp_kses_post( $atts['pre'] ) . get_avatar( $user->ID, $atts['size']) . wp_kses_post( $atts['post'] ) ;
	}

    if( $atts['key'] === 'id' ){
        $atts['key'] = 'ID';
    }

    $value = '';

	if ( $user->has_prop( $atts['key'] ) ){

		if ($atts['wpautop'] == 'on'){
			$value = wpautop( $user->get( $atts['key'] ) );
		} else {
			$value = $user->get( $atts['key'] );
		}

	}

	// Verify if key is a WYSIWYG field
	$escape_value = true;
	$manage_fields = get_option( 'wppb_manage_fields' );

	if ( !empty( $manage_fields ) ){
		foreach ( $manage_fields as $field ){
			if ( $field['meta-name'] == $atts['key'] && $field['field'] == 'WYSIWYG' ){
				$escape_value = false;
				break;
			}
		}
	}

	if( $escape_value === true && !empty( $value ) ){
		$value = esc_html( $value );
	}

	if ( !empty( $value ) ){
		return wp_kses_post( $atts['pre'] ) . $value . wp_kses_post( $atts['post'] ) ;
	}

	if( $atts['key'] === 'role' ){
		$roles = !empty( $user->roles ) ? $user->roles : array();

		if( !empty( $roles ) ){
			$value_roles = implode( ', ', $roles );
			return wp_kses_post( $atts['pre'] ) . esc_html( $value_roles ) . wp_kses_post( $atts['post'] );
		}
	}

	return;
}
