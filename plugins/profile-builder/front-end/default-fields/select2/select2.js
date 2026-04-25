
/**
 * Function that initializes select2 on fields
 *
 * @since v.3.3.4
 */
function wppb_select2_initialize() {
    jQuery ( '.custom_field_select2' ).each( function(){
        var selectElement = jQuery( this );
        var arguments = wp.hooks.applyFilters( 'wppb_select2_initialize_arguments', arguments, selectElement );

        if ( !( 'placeholder' in arguments ) || arguments.placeholder === '' ) {
            arguments.placeholder = jQuery('label[for="' + selectElement.attr('id') + '"]').text();
        }

        selectElement.select2( arguments ).on('select2:open', function(){
            // compatibility with Divi Overlay
            if( jQuery(selectElement).parents( '.overlay-container' ).length ){
                jQuery(selectElement).data('select2').dropdown.$dropdownContainer.css( 'z-index', '99999999' );
            }
        });
    });
}

jQuery( document ).ready(function() {
    wppb_select2_initialize();
});