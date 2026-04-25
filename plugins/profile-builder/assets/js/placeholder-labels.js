/*
 * Function that adds placeholders to Login Forms and Widget fields
 *
 * @since v.2.1
 *
 */
jQuery( document ).ready( function() {

    // add placeholder to Login Form Fields
    loginFieldPlaceholder();

    // add placeholder to the 2FA Field
    loginAuthPlaceholder();

});

/**
 * Add Placeholder for Login Form Fields
 *
 */
function loginFieldPlaceholder () {
    jQuery(".login-username input, .login-password input").each( function ( index, elem ) {
        var element_id = jQuery( elem ).attr( 'id' );
        if( element_id && ( label = jQuery( elem ).parents( '#wppb-login-wrap' ).find( 'label[for=' + element_id + ']' ) ).length === 1 ) {
            jQuery( elem ).attr( 'placeholder', jQuery( label ).text() );
        }
    });
}

/**
 * Add Placeholder for 2FA Field on Login Form
 *
 */
function loginAuthPlaceholder () {
    let element = jQuery( ".login-auth input" ),
        element_id = element.attr( 'id' ),
        label =  jQuery( 'label[for=' + element_id + ']' );

    if( element_id && label.length === 1 ) {
        jQuery( '#' + element_id ).attr( 'placeholder', label.text() );
    }
}