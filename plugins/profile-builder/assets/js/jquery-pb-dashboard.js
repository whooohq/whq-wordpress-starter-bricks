jQuery( document ).on( 'change', '#wppb-dashboard-stats-select', function(){

    let value = jQuery( this ).val()
    let nonce = jQuery( '#wppb-dashboard-stats-select__nonce' ).val()

    jQuery.post( ajaxurl, { action: 'wppb_get_dashboard_stats', interval: value, '_wpnonce': nonce }, function( response ) {

        response = JSON.parse( response )

        if( response.data.newly_registered !== undefined && response.data.newly_registered !== null)
            jQuery('.wppb-dashboard-box.newly_registered .value').html( response.data.newly_registered )

    });

});