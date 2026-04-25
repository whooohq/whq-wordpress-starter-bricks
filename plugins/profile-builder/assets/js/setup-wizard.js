jQuery( document ).ready(function(){

    jQuery('.wppb-forms-design-preview').click(function (e) {
        let themeID = e.target.id.replace('-info', '');
        displayDesignPreviewModal(themeID);
    });

    jQuery('.wppb-slideshow-button').click(function (e) {
        let themeID = jQuery(e.target).data('theme-id'),
            direction = jQuery(e.target).data('slideshow-direction'),
            currentSlide = jQuery('#modal-' + themeID + ' .wppb-forms-design-preview-image.active'),
            changeSlideshowImage = window[direction+'SlideshowImage'];

        changeSlideshowImage(currentSlide,themeID);
    });


    jQuery('.wppb-setup-newsletter__form a').on('click', function (e) {

        e.preventDefault()

        jQuery( '.wppb-setup-newsletter__form input[name="email"]' ).removeClass( 'error' )

        var email = jQuery( '.wppb-setup-newsletter__form input[name="email"]').val()

        if ( !validateEmail( email ) ){
            jQuery( '.wppb-setup-newsletter__form input[name="email"]' ).addClass( 'error' )
            jQuery( '.wppb-setup-newsletter__form input[name="email"]' ).focus()

            return
        }

        if( email != '' ){

            jQuery( '.wppb-setup-newsletter__form a' ).html( 'Working...' )

            var data = new FormData()
            data.append( 'email', email )

            jQuery.ajax({
                url: 'https://www.cozmoslabs.com/wp-json/cozmos-api/subscribeEmailToNewsletter',
                type: 'POST',
                processData: false,
                contentType: false,
                data: data,
                success: function (response) {

                    if( response.message ){

                        jQuery( '.wppb-setup-newsletter__form input[name="email"]' ).removeClass( 'error' )
                        jQuery( '.wppb-setup-newsletter__form' ).hide()
                        jQuery( '.wppb-setup-newsletter__success' ).show()

                        var data = new FormData()
                            data.append( 'action', 'dismiss_setup_wizard_newsletter_subscribe' )
                            data.append( 'wppb_nonce', jQuery( '.wppb-setup-newsletter #wppb_nonce' ).val() )

                        jQuery.ajax({
                            url        : ajaxurl,
                            type       : 'POST',
                            processData: false,
                            contentType: false,
                            data       : data,
                            success    : function (response) {

                            },
                            error: function (response) {

                            }
                        })

                    }

                },
                error: function (response) {

                    jQuery('.wppb-setup-newsletter__form a').html('Sign me up!')

                }
            })

        }

    })

});


function displayDesignPreviewModal( themeID ) {
    jQuery('#modal-' + themeID).dialog({
        resizable: false,
        height: 'auto',
        width: 1200,
        modal: true,
        closeOnEscape: true,
        open: function () {
            jQuery('.ui-widget-overlay').bind('click',function () {
                jQuery('#modal-' + themeID).dialog('close');
            })
        },
        close: function () {
            let allImages = jQuery('.wppb-forms-design-preview-image');

            allImages.each( function() {
                if ( jQuery(this).is(':first-child') && !jQuery(this).hasClass('active') ) {
                    jQuery(this).addClass('active');
                }
                else if ( !jQuery(this).is(':first-child') ) {
                    jQuery(this).removeClass('active');
                }
            });

            jQuery('.wppb-forms-design-sildeshow-previous').addClass('disabled');
            jQuery('.wppb-forms-design-sildeshow-next').removeClass('disabled');
        }
    });
    return false;
}

function nextSlideshowImage( currentSlide, themeID ){
    if ( currentSlide.next().length > 0 ) {
        currentSlide.removeClass('active');
        currentSlide.next().addClass('active');

        jQuery('#modal-' + themeID + ' .wppb-forms-design-sildeshow-previous').removeClass('disabled');

        if ( currentSlide.next().next().length <= 0 )
            jQuery('#modal-' + themeID + ' .wppb-forms-design-sildeshow-next').addClass('disabled');

    }
}

function previousSlideshowImage( currentSlide, themeID ){
    if ( currentSlide.prev().length > 0 ) {
        currentSlide.removeClass('active');
        currentSlide.prev().addClass('active');

        jQuery('#modal-' + themeID + ' .wppb-forms-design-sildeshow-next').removeClass('disabled');

        if ( currentSlide.prev().prev().length <= 0 )
            jQuery('#modal-' + themeID + ' .wppb-forms-design-sildeshow-previous').addClass('disabled');

    }
}


function validateEmail(email) {

    const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());

}