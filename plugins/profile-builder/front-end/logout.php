<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    /*
     * Function that returns a front-end logout message from the wppb-logout shortcode
     *
     * @param $atts     The shortcode attributes
     */
    function wppb_front_end_logout( $atts ) {

        if( !is_user_logged_in() )
            return;

        $current_user = get_userdata( get_current_user_id() );

        $wppb_generalSettings = get_option( 'wppb_general_settings' );

        if( !empty( $wppb_generalSettings['loginWith'] ) && $wppb_generalSettings['loginWith'] == 'email' ) {
            $display_username_email = $current_user->user_email;
        }
        else {
            $display_username_email = $current_user->user_login;
        }

        $atts = shortcode_atts( array(
            'text'              => sprintf(__('You are currently logged in as %s. ', 'profile-builder'), $display_username_email),
            'redirect'          => '',
            'redirect_url'      => wppb_curpageurl(),
            'redirect_priority' => 'normal',
            'link_text'         => __('Log out &raquo;', 'profile-builder'),
            'url_only'          => '',
        ), $atts, 'wppb-logout' );

        if( ! empty( $atts['redirect'] ) ) {
            $atts['redirect_url'] = $atts['redirect'];
        }

        // CHECK FOR REDIRECT
        $redirect_url = wppb_get_redirect_url( $atts['redirect_priority'], 'after_logout', esc_url_raw( $atts['redirect_url'] ), $current_user );
        $redirect_url = apply_filters( 'wppb_after_logout_redirect_url', $redirect_url );

        if( isset( $atts['url_only'] ) && $atts['url_only'] == 'yes' )
            return wp_logout_url( $redirect_url );

        $logout_link = '<a href="' . esc_url( wp_logout_url( $redirect_url ) ) . '" class="wppb-logout-url" title="' . esc_attr__( 'Log out of this account', 'profile-builder' ) . '">' . esc_html( $atts['link_text'] ) . '</a>';

        $meta_tags = apply_filters( 'wppb_front_end_logout_meta_tags', array( '{{meta_user_name}}', '{{meta_first_name}}', '{{meta_last_name}}', '{{meta_display_name}}' ) );
        $meta_tags_values = apply_filters( 'wppb_front_end_logout_meta_tags_values', array( $current_user->user_login, $current_user->first_name, $current_user->last_name, $current_user->display_name ) );

        $text = apply_filters( 'wppb_front_end_logout_text', str_replace( $meta_tags, $meta_tags_values, $atts['text'] ), $current_user );

        return apply_filters( 'wppb_logout_message', '<p class="wppb-front-end-logout"><span>' . esc_html( $text ) . '</span>' . $logout_link . '</p>' );
    }