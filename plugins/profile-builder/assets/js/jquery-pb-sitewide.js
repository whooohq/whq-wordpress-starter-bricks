/**
 * Add a negative letter spacing to Profile Builder email customizer menus.
 */

jQuery( document ).ready(function(){
    jQuery('li a[href$="admin-email-customizer"]').css("letter-spacing", "-0.7px");
    jQuery('li a[href$="user-email-customizer"]').css("letter-spacing", "-0.7px");
});

/*
 * Set the width of the shortcode input based on an element that
 * has the width of its contents
 */
function setShortcodeInputWidth( $inputField ) {
    var tempSpan = document.createElement('span');
    tempSpan.className = "wppb-shortcode-temp";
    tempSpan.innerHTML = $inputField.val();
    document.body.appendChild(tempSpan);

    var tempWidth = jQuery(tempSpan).outerWidth();

    document.body.removeChild(tempSpan);

    $inputField.outerWidth( tempWidth + 18 );
}

jQuery( document ).ready( function() {

    jQuery('.wppb-shortcode.input').each( function() {
        setShortcodeInputWidth( jQuery(this) );
    });

    jQuery('.wppb-shortcode.textarea').each( function() {
        jQuery(this).outerHeight( jQuery(this)[0].scrollHeight + parseInt( jQuery(this).css('border-top-width') ) * 2 );
    });

    jQuery('.wppb-shortcode').on('click', function() {
        this.select();
    });
});


/* make sure that we don;t leave the page without having a title in the Post Title field, otherwise we loose data */
jQuery( function(){
    if( jQuery( 'body').hasClass('post-new-php') ){

        if( jQuery( 'body').hasClass('post-type-wppb-rf-cpt') || jQuery( 'body').hasClass('post-type-wppb-epf-cpt') || jQuery( 'body').hasClass('post-type-wppb-ul-cpt') ){

            if( jQuery('#title').val() == '' ){
                jQuery(window).on('beforeunload',function() {
                    return "This page is asking you to confirm that you want to leave - data you have entered may not be saved";
                });
            }

            /* remove beforeunload event when entering a title or pressing the puclish button */
            jQuery( '#title').on( 'keypress', function() {
                jQuery(window).off('beforeunload');
            });
            jQuery( '#publish').on('click', function() {
                jQuery(window).off('beforeunload');
            });
        }
    }
});


/* show hide fields based on selected options */
jQuery( function(){
    jQuery( '#wppb-rf-settings-args').on('change', '#redirect', function(){
        if( jQuery(this).val() == 'Yes' ){
            jQuery( '.row-url, .row-display-messages', jQuery(this).parent().parent().parent()).css('display', 'flex');
        }
        else{
            jQuery( '.row-url, .row-display-messages', jQuery(this).parent().parent().parent()).hide();
        }
    });

    jQuery( '#wppb-epf-settings-args').on('change', '#redirect', function(){
        if( jQuery(this).val() == 'Yes' ){
            jQuery( '.row-url, .row-display-messages', jQuery(this).parent().parent().parent()).css('display', 'flex');
        }
        else{
            jQuery( '.row-url, .row-display-messages', jQuery(this).parent().parent().parent()).hide();
        }
    });


    jQuery( '#wppb-ul-settings-args').on('click', '#visible-only-to-logged-in-users_yes', function(){

        if (jQuery(this).is(':checked')) {
            jQuery( '.row-visible-to-following-roles').css('display', 'flex');
        }
        else jQuery( '.row-visible-to-following-roles').css('display', 'none');
    });

    jQuery( '#wppb-ul-faceted-args').on('change', '#facet-type', function(){
        if( jQuery(this).val() == 'checkboxes' ){
            jQuery( '.row-facet-behaviour, .row-facet-limit', jQuery(this).parent().parent().parent()).show();
        }else if( jQuery(this).val() == 'select_multiple' ){
            jQuery( '.row-facet-behaviour, .row-facet-limit', jQuery(this).parent().parent().parent()).hide();
            jQuery( '.row-facet-behaviour #facet-behaviour', jQuery(this).parent().parent().parent()).val('expand');
        }
        else{
            jQuery( '.row-facet-behaviour, .row-facet-limit', jQuery(this).parent().parent().parent()).hide();
            jQuery( '.row-facet-behaviour #facet-behaviour', jQuery(this).parent().parent().parent()).val('narrow');
        }
        if( jQuery(this).val() == 'search' ){
            jQuery( '#wppb-ul-faceted-args .row-facet-meta #facet-meta option[value="billing_country"] ').hide();
            jQuery( '#wppb-ul-faceted-args .row-facet-meta #facet-meta option[value="shipping_country"] ').hide();
            jQuery( '#wppb-ul-faceted-args .row-facet-meta #facet-meta option[value="billing_state"] ').hide();
            jQuery( '#wppb-ul-faceted-args .row-facet-meta #facet-meta option[value="shipping_state"] ').hide();
        }
        else {
            jQuery( '#wppb-ul-faceted-args .row-facet-meta #facet-meta option[value="billing_country"] ').show();
            jQuery( '#wppb-ul-faceted-args .row-facet-meta #facet-meta option[value="shipping_country"] ').show();
            jQuery( '#wppb-ul-faceted-args .row-facet-meta #facet-meta option[value="billing_state"] ').show();
            jQuery( '#wppb-ul-faceted-args .row-facet-meta #facet-meta option[value="shipping_state"] ').show()
        }

    });

});

/*
 * Dialog boxes throughout Profile Builder
 */
jQuery( function() {
    if ( jQuery.fn.dialog ) {
        jQuery('.wppb-modal-box').dialog({
            autoOpen: false,
            modal: true,
            draggable: false,
            minWidth: 450,
            minHeight: 450
        });

        jQuery('.wppb-open-modal-box').on('click', function (e) {
            e.preventDefault();
            jQuery('#' + jQuery(this).attr('href')).dialog('open');
        });
    }
});

/*
 * Private Website Settings page
 */

jQuery( function() {
    if( jQuery( '.wppb-private-website' ).length != 0 ) {

        wppbSelect2.call( jQuery('#private-website-redirect-to-login') );
        wppbSelect2.call( jQuery('#private-website-allowed-pages') );

        wppbDisablePrivatePageOptions(jQuery('#private-website-enable').val());

        jQuery('#private-website-enable').on('change', function () {
            wppbDisablePrivatePageOptions(jQuery(this).val());
        });


        function wppbDisablePrivatePageOptions(value) {
            if (value == 'no') {
                jQuery('#private-website-redirect-to-login').closest('tr').addClass("wppb-disabled");
                jQuery('#private-website-allowed-pages').closest('tr').addClass("wppb-disabled");
                jQuery('#private-website-menu-hide').addClass("wppb-disabled");
                jQuery('#private-website-disable-rest-api').addClass("wppb-disabled");
                jQuery('#private-website-allowed-paths').addClass("wppb-disabled");
            }
            else if (value == 'yes') {
                jQuery('#private-website-redirect-to-login').closest('tr').removeClass("wppb-disabled");
                jQuery('#private-website-allowed-pages').closest('tr').removeClass("wppb-disabled");
                jQuery('#private-website-menu-hide').removeClass("wppb-disabled");
                jQuery('#private-website-disable-rest-api').removeClass("wppb-disabled");
                jQuery('#private-website-allowed-paths').removeClass("wppb-disabled");
            }
        }
    }
});

/*
 * Login Widget trigger html validation
 */
jQuery( function() {
    if( jQuery( ".widgets-php" ).length != 0 ){//should be in the admin widgets page
        jQuery("#wpbody").on("click", ".widget-control-save", function () {
            if (jQuery('.wppb-widget-url-field', jQuery(this).closest('form')).length != 0) {//we are in the PB widget
                jQuery('.wppb-widget-url-field', jQuery(this).closest('form')).each(function () {
                    jQuery(this)[0].reportValidity();//reportValidity is the function that triggers the default html validation
                });
            }
        });
    }
});


/*
 * Advanced Settings page (Toolbox)
 */

jQuery( function() {
    if( jQuery('body.profile-builder_page_profile-builder-toolbox-settings').length != 0 ) {

        wppbSelect2.call( jQuery('#toolbox-bypass-ec') );

        wppbSelect2.call( jQuery('#toolbox-restricted-emails'), {
            tags: true,
        });

        jQuery('.wppb-toolbox-switch').on('click', function () {
            if (jQuery(this).prop('checked'))
                jQuery('.wppb-toolbox-accordion').css('display','flex');
            else
                jQuery('.wppb-toolbox-accordion').hide();
        });

        jQuery('#toolbox-send-credentials-hide').on('click', function () {
            if (jQuery(this).prop('checked'))
                jQuery('#wppb-toolbox-send-credentials-text').hide();
            else
                jQuery('#wppb-toolbox-send-credentials-text').css('display','flex');
        });

        jQuery('#toolbox-redirect-if-empty-required').on('click', function () {
            if (jQuery(this).prop('checked'))
                jQuery('#wppb-toolbox-redirect-if-empty-required-url').css('display','flex');
            else
                jQuery('#wppb-toolbox-redirect-if-empty-required-url').hide();
        });

        jQuery('#wppb-color-switcher').on('click', function () {
            if (jQuery(this).prop('checked'))
                jQuery('.wppb-color-switcher-section').css('display','flex');
            else
                jQuery('.wppb-color-switcher-section').hide();
        });


        if (jQuery('.wppb-toolbox-switch').prop('checked'))
            jQuery('.wppb-toolbox-accordion').css('display','flex');

        if (jQuery('#toolbox-send-credentials-hide').prop('checked'))
            jQuery('#wppb-toolbox-send-credentials-text').hide();

        if (jQuery('#toolbox-redirect-if-empty-required').prop('checked'))
            jQuery('#wppb-toolbox-redirect-if-empty-required-url').css('display','flex');

        if (jQuery('#wppb-color-switcher').prop('checked'))
            jQuery('.wppb-color-switcher-section').css('display','flex');
        else
            jQuery('.wppb-color-switcher-section').hide();

        // Color Switcher - Notifications Background
        var activeDesign = jQuery('.color-switcher').data('active-design');

        if ( activeDesign === 'form-style-1' ) {
            jQuery('.wppb-form-style-1-fields').show();
            jQuery('.wppb-other-style-fields').hide();
        } else {
            jQuery('.wppb-form-style-1-fields').hide();
            jQuery('.wppb-other-style-fields').show();
        }
    }
});

// Fix for Select2 search not focusing
jQuery(document).on('select2:open', function() {
    let allSelect2Found = document.querySelectorAll('.select2-container--open .select2-search__field');
    allSelect2Found[allSelect2Found.length - 1].focus();
});

/**
 * Add Link to PB Docs next to page/setting titles
 * */
jQuery(document).ready( function () {
    // email customizer
    jQuery('.wp-admin.profile-builder_page_user-email-customizer .wrap h2.cozmoslabs-page-title').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/user-email-customizer/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');
    jQuery('.wp-admin.profile-builder_page_admin-email-customizer .wrap h2.cozmoslabs-page-title').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/admin-email-customizer/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');

    // roles editor
    jQuery('.wp-admin.post-type-wppb-roles-editor .wrap h1.wp-heading-inline').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/general-settings/roles-editor/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');

    // manage form fields
    jQuery('.wp-admin.profile-builder_page_manage-fields .wrap > h2').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/manage-user-fields/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');
    jQuery('html').on('wpbFormMetaLoaded', function() {
        // conditional logic
        jQuery('.wp-admin.profile-builder_page_manage-fields .update_container_wppb_manage_fields ul.mb-list-entry-fields li.row-conditional-logic-enabled .mb-right-column label').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/manage-user-fields/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Conditional_Logic" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 10px"></a>');
        // visibility
        jQuery('.wp-admin.profile-builder_page_manage-fields .update_container_wppb_manage_fields ul.mb-list-entry-fields li.row-visibility .mb-right-column').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/field-visibility/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Visibility" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 10px"></a>');
        // user role visibility
        jQuery('.wp-admin.profile-builder_page_manage-fields .update_container_wppb_manage_fields ul.mb-list-entry-fields li.row-user-role-visibility .mb-right-column .wck-checkboxes').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/field-visibility/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#User_Role_Visibility" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
        // location visibility
        jQuery('.wp-admin.profile-builder_page_manage-fields .update_container_wppb_manage_fields ul.mb-list-entry-fields li.row-location-visibility .mb-right-column .wck-checkboxes').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/field-visibility/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Location_Visibility" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
        // admin approval
        jQuery('.wp-admin.profile-builder_page_manage-fields .update_container_wppb_manage_fields ul.mb-list-entry-fields li.row-edit-profile-approved-by-admin .mb-right-column .wck-checkboxes label').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/edit-profile-approved-by-admin/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 10px"></a>');
    });

    // register forms
    jQuery('.wp-admin.post-type-wppb-rf-cpt .wrap h1.wp-heading-inline').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/multiple-registration-forms/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');

    // edit profile forms
    jQuery('.wp-admin.post-type-wppb-epf-cpt .wrap h1.wp-heading-inline').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/multiple-edit-profile-forms/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');

    // user-listing
    jQuery('.wp-admin.post-type-wppb-ul-cpt .wrap h1.wp-heading-inline').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/user-listing/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');
    // ul settings
    jQuery('.wp-admin.post-type-wppb-ul-cpt #wppb_ul_page_settings').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/user-listing/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#User_Listing_Settings" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
    // ul faceted menus
    jQuery('.wp-admin.post-type-wppb-ul-cpt #wppb_ul_faceted_settings').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/user-listing/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Faceted_Menus" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
    // ul search settings
    jQuery('.wp-admin.post-type-wppb-ul-cpt #wppb_ul_search_settings').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/user-listing/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Search_Settings" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
    // ul themes
    jQuery('.wp-admin.post-type-wppb-ul-cpt #wppb-ul-themes-settings .inside').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/user-listing/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Themes" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
    // ul all users template
    jQuery('.wp-admin.post-type-wppb-ul-cpt #wppb-ul-templates .inside').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/user-listing/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#The_All-Userlisting_Template" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
    // ul single user template
    jQuery('.wp-admin.post-type-wppb-ul-cpt #wppb-single-ul-templates .inside').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/user-listing/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#The_Single-Userlisting_Template" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');

    // custom redirects
    jQuery('.wp-admin.profile-builder_page_custom-redirects .wrap h2.cozmoslabs-page-title').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/custom-redirects/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');
    // cr individual user
    jQuery('.wp-admin.profile-builder_page_custom-redirects #wppb_custom_redirects_user .inside').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/custom-redirects/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Individual_User_Redirects" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
    // cr user role based
    jQuery('.wp-admin.profile-builder_page_custom-redirects #wppb_custom_redirects_role .inside').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/custom-redirects/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#User_Role_based_Redirects" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
    // cr global
    jQuery('.wp-admin.profile-builder_page_custom-redirects #wppb_custom_redirects_global .inside').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/custom-redirects/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Global_Redirects" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');
    // cr default wp pages
    jQuery('.wp-admin.profile-builder_page_custom-redirects #wppb_custom_redirects_default_wp_pages .inside').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/modules/custom-redirects/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs#Redirect_Default_WordPress_Forms_and_Pages" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');

    // social connect
    jQuery('.wp-admin.profile-builder_page_wppb-social-connect .wrap h2.cozmoslabs-page-title').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/social-connect/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');

    // multi-step forms
    jQuery('.wp-admin.profile-builder_page_manage-fields #wppb-msf-side .inside').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/multi-step-forms/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');

    // form fields in columns
    jQuery('.wp-admin.profile-builder_page_manage-fields #wppb-ffc-side .inside').prepend('<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/form-fields-in-columns/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>');

    // labels edit
    jQuery('.wp-admin.profile-builder_page_pb-labels-edit .wrap h2.cozmoslabs-page-title').append('<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/labels-edit/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px"></a>');
});


/**
 * Documentation links popup
 */
jQuery( function() {
    if ( ! jQuery.fn.dialog ) {
        return;
    }

    const $docsLinkPopup = jQuery('#wppb-docs-link-popup');

    if ( ! $docsLinkPopup.length ) {
        return;
    }

    $docsLinkPopup.dialog({
          autoOpen: false,
          modal: true,
          draggable: false,
          resizable: false,
          width: 480,
          dialogClass: 'wppb-docs-link-popup-dialog'
      });

    jQuery(document).on('click', 'a.wppb-docs-link', function (e) {
        const docsUrl = jQuery(this).attr('href');

        if ( ! docsUrl ) {
            return;
        }

        e.preventDefault();

        $docsLinkPopup.find('.wppb-docs-link-popup-open-docs').attr('href', docsUrl);

        $docsLinkPopup.dialog('open');
    });

    $docsLinkPopup.on('click', '.wppb-docs-link-popup-open-docs, .wppb-docs-link-popup-open-wporg', function () {
        $docsLinkPopup.dialog('close');
    });
});


/**
 * Initialize Select2
 *
 * */
jQuery(document).ready(function() {
    if ( typeof jQuery.fn.select2 === 'function') {
        if (jQuery('.wppb-select2').is('#toolbox-restricted-words-data')) {
            jQuery('.wppb-select2').select2({
                tags: true,
            });
        }
        else jQuery('.wppb-select2').select2();
    }
});


/**
 * Form Designs Feature --> Admin UI
 *
 *  - Activate new Design
 *  - Preview Modal
 *  - Modal Image Slider controls
 *
 * */

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


/**
 * Hide/Show Roles Selector
 *
 * */
jQuery(document).ready(function() {
    let input = jQuery('input#wppb-auth-enable'),
        rolesSelector = jQuery('#wppb-auth-roles-selector');

    input.click(function (e) {
        rolesSelector.toggle();
    });

    // Upgrade notice in PB Free Version
    jQuery('input#wppb-2fa-enable').click(function (e) {
        jQuery('#wppb-2fa-upgrade-notice').toggle();
    });

});


/**
 * Handle Admin Dashboard Publish Box/Button position
 *
 */
jQuery(window).on('load', function () {

    // limit Publish Box/Button positioning to Profile Builder Custom Pages and CPTs
    if (jQuery('body').is('[class*="profile-builder_page"], [class*="admin_page_profile-builder"], [class*="post-type-wppb"]')) {

        handlePublishBoxPosition();

        // Reset the scroll event on manual window resize to keep the Publish button scrollable without refreshing
        jQuery(window).on('resize', function() {

            // clear existing scroll events
            jQuery(window).off('scroll');

            handlePublishBoxPosition();
        });

    }

});


/**
 * Reposition the Publish Box/Button in Admin Dashboard
 * - PMS CPTs
 * - Custom Pages
 *
 */
function handlePublishBoxPosition() {
    let largeScreen  = window.matchMedia("(min-width: 1402px)"),
        settingsContainer =  jQuery('#wppb-register-version'),
        buttonContainer = jQuery('.cozmoslabs-submit');

    if ( settingsContainer.length === 0 ){
        settingsContainer = jQuery( '.cozmoslabs-settings' );
    }

    // determine if we are on a PMS Custom Page or CPT
    if ( settingsContainer.length === 0 ) {
        settingsContainer = jQuery('#poststuff');
        buttonContainer = jQuery('#submitdiv');
    }

    if ( settingsContainer.length > 0 && buttonContainer.length > 0 ) {

        if ( largeScreen.matches )
            wppbRepositionPublishMetaBox( settingsContainer, buttonContainer );
        else wppbRepositionPublishButton( buttonContainer );

    }
}

/**
 *  Reposition Publish Meta-Box
 * - PMS CPTs
 * - Custom Pages
 *
 *  - works on large screens
 *
 * */
function wppbRepositionPublishMetaBox( settingsContainer, buttonContainer ) {

    buttonContainer.removeClass('cozmoslabs-publish-button-fixed');

    if ( buttonContainer.length > 0 ) {

        // set initial position
        wppbSetPublishMetaBoxPosition();

        // reposition on scroll
        jQuery(window).scroll(function () {
            wppbSetPublishMetaBoxPosition();
        });

    }

    /**
     * Position the Publish Meta-Box
     */
    function wppbSetPublishMetaBoxPosition() {

        if ( wppbCalculateDistanceToTop( settingsContainer ) < 50 ) {
            buttonContainer.addClass('cozmoslabs-publish-metabox-fixed');

            buttonContainer.css({
                'left'   : settingsContainer.offset().left + settingsContainer.outerWidth() + 'px'
            });
        } else {

            buttonContainer.removeClass('cozmoslabs-publish-metabox-fixed');

            buttonContainer.css({
                'left'   : 'unset'
            });
        }

    }

}

/**
 *  Reposition Publish Button
 *  - PMS CPTs
 *  - Custom Pages
 *
 *  - works on small/medium screens
 *
 * */
function wppbRepositionPublishButton( buttonContainer ) {

    buttonContainer.removeClass('cozmoslabs-publish-metabox-fixed');

    if ( buttonContainer.length > 0 ) {

        // set initial position
        wppbSetPublishButtonPosition();

        // reposition on scroll
        jQuery(window).on('scroll', function() {
            wppbSetPublishButtonPosition();
        });

    }

    /**
     * Position the Publish Button
     */
    function wppbSetPublishButtonPosition() {

        let button = buttonContainer.find('input[type="submit"]');

        if ( wppbElementInViewport( buttonContainer ) ) {
            buttonContainer.removeClass('cozmoslabs-publish-button-fixed');

            button.css({
               'max-width': 'unset',
               'left': 'unset',
           });
        }
        else {
            buttonContainer.addClass('cozmoslabs-publish-button-fixed');

            button.css({
               'max-width': buttonContainer.outerWidth() + 'px',
               'left': buttonContainer.offset().left + 'px',
           });
        }
    }
}


/**
 *  Calculate the distance to Top for a specific element
 *
 * */
function wppbCalculateDistanceToTop(element) {
    let scrollTop = jQuery(window).scrollTop(),
        elementOffset = element.offset().top;

    return elementOffset - scrollTop;
}


/**
 *  Check if a specific element is visible on screen
 *
 * */
function wppbElementInViewport(element) {
    let elementTop = element.offset().top,
        elementBottom = elementTop + element.outerHeight(),
        viewportTop = jQuery(window).scrollTop(),
        viewportBottom = viewportTop + jQuery(window).height();

    return elementBottom > viewportTop && elementTop < viewportBottom;
}



/**
 *  Display initially hidden admin notices, after the scripts have loaded
 *
 * */
jQuery( document ).ready(function(){

    let noticeTypes = [
        ".error",
        ".notice"
    ];

    noticeTypes.forEach(function(notice){
        let selector = "body[class*='builder_page_'] " + notice + ", " + "body[class*='post-type-wppb-'] " + notice;

        jQuery(selector).each(function () {
            jQuery(this).css('display', 'block');
        });
    });

});


/**
 *  Function that copies the shortcode from an input
 *
 * */
jQuery(document).ready(function() {
    jQuery('.wppb-shortcode_copy').click(function (e) {

        e.preventDefault();

        navigator.clipboard.writeText(jQuery(this).val());

        // Show copy message
        var copyMessage = jQuery(this).next('.wppb-copy-message');
        copyMessage.fadeIn(400).delay(2000).fadeOut(400);

    })
});


/**
 *  Function that copies the shortcode from a text
 *
 * */
jQuery(document).ready(function() {
    jQuery('.wppb-shortcode_copy-text').click(function (e) {

        e.preventDefault();

        navigator.clipboard.writeText(jQuery(this).text());

        // Show copy message
        var copyMessage = jQuery(this).next('.wppb-copy-message');
        copyMessage.fadeIn(400).delay(2000).fadeOut(400);

    })
});


/**
 * Handle extra post meta cleanup
 *
 * - wppb_sc_rf_epf_active
 * - wppb-content-restrict-message-purchasing_restricted
 * - wppb-ul-active-theme
 * - wppb-ul-default-single-user-template
 * - wppb-ul-default-all-user-template
 *
 */
jQuery(document).ready(function() {
    jQuery('.wppb-cleanup-postmeta').click(function (e) {
        e.preventDefault();

        let $button      = jQuery(this);
        let originalText = $button.text();

        $button.prop('disabled', true).text('Cleaning...');

        // Function to handle cleanup process
        function processCleanup(step = 1) {
            jQuery.post( ajaxurl, {
                action: 'wppb_cleanup_postmeta',
                nonce : $button.data('nonce'),
                step  : step
            }, function( response ) {
                if( response.success ) {
                    if( response.data.step === 'done' ) {

                        $button.text('Cleanup Complete!');

                        // If cleanup is complete, hide the button after showing completion message
                        if( response.data.hide_button ) {
                            setTimeout(function() {
                                let wrapper = jQuery($button).closest('.cozmoslabs-form-field-wrapper')

                                wrapper.fadeOut(400, function() {
                                    jQuery(this).remove();
                                });
                            }, 2000);
                        } else {
                            setTimeout(function() {
                                $button.text(originalText).prop('disabled', false);
                            }, 2000);
                        }

                    } else {
                        processCleanup(response.data.step);
                    }
                } else {
                    $button.text('Error occurred').prop('disabled', false);
                    setTimeout(function() {
                        $button.text(originalText);
                    }, 2000);
                }
            }).fail(function() {
                $button.text('Error occurred').prop('disabled', false);
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
            });
        }

        // Start the cleanup process
        processCleanup();

    })
});


/**
 * Prevent Email Customizer meta-boxes from being moved into the side section
 *
 */
jQuery(function () {

    if ( pagenow !== 'profile-builder_page_user-email-customizer' && pagenow !== 'profile-builder_page_admin-email-customizer' )
        return;

    function handleDownArrows() {

        // reset all down arrows
        jQuery('.cozmoslabs-email-customizer-section .meta-box-sortables .postbox button.handle-order-lower').attr('aria-disabled', 'false');

        // disable the last down arrow in the main area
        jQuery('.cozmoslabs-email-customizer-section #advanced-sortables .postbox:last-of-type button.handle-order-lower').attr('aria-disabled', 'true');

    }

    handleDownArrows();

    // handle down arrows after meta-boxes order change
    jQuery('.handle-order-lower, .handle-order-higher').on('click', handleDownArrows);

});


/**
 * Handle the Profile Builder deactivation popup on the Plugins page
 *
 * - intercepts the plugin deactivation link
 * - validates and stores the selected reason through AJAX before redirecting
 *
 */
jQuery(function () {

    if ( typeof jQuery.fn.dialog !== 'function' )
        return;

    const $popup = jQuery('#wppb-deactivation-popup');

    if ( $popup.length === 0 )
        return;

    const $form = $popup.find('.wppb-deactivation-popup-form');
    const $error = $popup.find('.wppb-deactivation-popup-error');
    const $actionButtons = $popup.find('.wppb-deactivation-popup-confirm, .wppb-deactivation-popup-skip');
    const pluginBasename = $popup.data('plugin');
    let deactivateLink = '';
    let isRedirecting = false;

    $actionButtons.each(function () {
        const $btn = jQuery(this);
        $btn.data('wppbOriginalText', $btn.text().trim());
    });

    function resetActionButtons() {
        $actionButtons.each(function () {
            const $btn = jQuery(this);
            const original = $btn.data('wppbOriginalText');
            if (original !== undefined) {
                $btn.text(original);
            }
            $btn.prop('disabled', false);
        });
    }

    $popup.dialog({
        autoOpen: false,
        modal: true,
        draggable: false,
        resizable: false,
        width: 480
    });

    function setError(message) {
        if (!message) {
            $error.hide().text('');
            jQuery('.wppb-deactivation-popup-extra').removeClass('error');
            return;
        }

        $error.text(message).show();
        jQuery('.wppb-deactivation-popup-extra').addClass('error');
    }

    function toggleExtraFields() {
        const selectedReason = $form.find('input[name="wppb_deactivation_reason"]:checked').val() || '';

        $form.find('.wppb-deactivation-popup-extra').each(function () {
            const $input = jQuery(this);
            const shouldShow = $input.data('reason') === selectedReason;

            $input.toggle(shouldShow);

            if (!shouldShow) {
                $input.val('');
            }
        });
    }

    function getPayload(reasonOverride) {
        const payload = {
            action: 'wppb_store_deactivation_reason',
            nonce: (window.wppbDeactivationData && window.wppbDeactivationData.deactivationReasonNonce) ? window.wppbDeactivationData.deactivationReasonNonce : '',
            reason: reasonOverride || ($form.find('input[name="wppb_deactivation_reason"]:checked').val() || '')
        };

        $form.find('.wppb-deactivation-popup-extra').each(function () {
            const $input = jQuery(this);

            if ($input.val()) {
                payload[$input.attr('name')] = $input.val();
            }
        });

        return payload;
    }

    function validatePayload(payload) {
        if (payload.reason === 'skip') {
            return true;
        }

        if (!payload.reason) {
            setError(window.wppbDeactivationData ? window.wppbDeactivationData.deactivationReasonRequired : '');
            return false;
        }

        if (
            (payload.reason === 'switched_to_another_plugin' && !payload.switched_to_another_plugin_reason) ||
            (payload.reason === 'missing_features' && !payload.missing_features_reason) ||
            (payload.reason === 'other' && !payload.other_reason)
        ) {
            setError(window.wppbDeactivationData ? window.wppbDeactivationData.deactivationReasonInput : '');
            return false;
        }

        setError('');
        return true;
    }

    function submitReason(payload, $triggerButton) {
        if (!validatePayload(payload) || !deactivateLink || isRedirecting) {
            return;
        }

        isRedirecting = true;

        if ($triggerButton && $triggerButton.length) {
            $triggerButton.text('Deactivating...');
        }
        $actionButtons.prop('disabled', true);

        jQuery.post(ajaxurl, payload)
            .done(function () {
                window.location.href = deactivateLink;
            })
            .fail(function () {
                isRedirecting = false;
                resetActionButtons();
                setError(window.wppbDeactivationData ? window.wppbDeactivationData.deactivationReasonSaveError : '');
            });
    }

    jQuery(document).on('click', 'tr[data-plugin="' + pluginBasename + '"] .deactivate a', function (e) {
        e.preventDefault();
        e.stopPropagation();

        deactivateLink = jQuery(this).attr('href');
        isRedirecting = false;
        resetActionButtons();
        $form[0].reset();
        toggleExtraFields();
        setError('');
        $popup.dialog('open');
    });

    $form.on('change', 'input[name="wppb_deactivation_reason"]', function () {
        toggleExtraFields();
        setError('');
    });

    $popup.on('click', '.wppb-deactivation-popup-confirm', function (e) {
        e.preventDefault();
        submitReason(getPayload(), jQuery(this));
    });

    $popup.on('click', '.wppb-deactivation-popup-skip', function (e) {
        e.preventDefault();
        submitReason(getPayload('skip'), jQuery(this));
    });
});
