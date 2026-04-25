jQuery( function(){

    /* Display custom redirect URL section if type of restriction is "Redirect" */
    jQuery( 'input[type=radio][name=wppb-content-restrict-type]' ).on( "click", function() {
        if( jQuery( this ).is(':checked') && jQuery( this ).val() == 'redirect' ){
            jQuery( '#wppb-meta-box-fields-wrapper-restriction-redirect-url' ).addClass( 'wppb-content-restriction-enabled' );

            // hide the custom restriction messages section (not needed for Redirect)
            jQuery( '#wppb-meta-box-fields-wrapper-restriction-custom-messages' ).hide();
        }
        else {
            jQuery( '#wppb-meta-box-fields-wrapper-restriction-redirect-url' ).removeClass( 'wppb-content-restriction-enabled' );

            // this needs to be unchecked so that the Custom URL is properly disabled
            jQuery( '#wppb-content-restrict-custom-redirect-url-enabled' ).prop( 'checked', false );
            jQuery( '.wppb-meta-box-field-wrapper-custom-redirect-url' ).removeClass( 'wppb-content-restriction-enabled' );

            // show the custom restriction messages section
            jQuery( '#wppb-meta-box-fields-wrapper-restriction-custom-messages' ).show();
        }
    } );

    /* Display custom redirect URL field */
    jQuery( '#wppb-content-restrict-custom-redirect-url-enabled' ).on( "click", function() {
        if( jQuery( this ).is( ':checked' ) )
            jQuery( '.wppb-meta-box-field-wrapper-custom-redirect-url' ).addClass( 'wppb-content-restriction-enabled' );
        else
            jQuery( '.wppb-meta-box-field-wrapper-custom-redirect-url' ).removeClass( 'wppb-content-restriction-enabled' );
    } );

    /* Display custom messages editors */
    jQuery( '#wppb-content-restrict-messages-enabled' ).on( "click", function() {
        if( jQuery( this ).is( ':checked' ) )
            jQuery( '.wppb-meta-box-field-wrapper-custom-messages' ).addClass( 'wppb-content-restriction-enabled' );
        else
            jQuery( '.wppb-meta-box-field-wrapper-custom-messages' ).removeClass( 'wppb-content-restriction-enabled' );
    } );

} );