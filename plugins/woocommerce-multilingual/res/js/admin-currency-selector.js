( function() {

	const csLocator = '#dropdown_dashboard_currency';

	jQuery( function( $ ) {

		function tryInsertCurrencyDropdown() {
			const dashboardStatus = $( '#woocommerce_dashboard_status' );
			const dropdown = $( csLocator );

			if ( dashboardStatus.length && dropdown.length ) {
				const dashboard_dropdown = dropdown.clone();
				dropdown.remove();
				dashboard_dropdown.insertBefore( '.sales-this-month a' ).show();

				return true;
			}

			return false;
		}

		if ( ! tryInsertCurrencyDropdown() ) {
			const observer = new MutationObserver( function ( mutations, obs ) {
				if ( tryInsertCurrencyDropdown() ) {
					obs.disconnect();
				}
			});

			observer.observe( document.body, {
				childList: true,
				subtree: true
			} );
		}
	});

	jQuery( document ).on( 'change', csLocator, function() {
		jQuery.ajax( {
			url: ajaxurl,
			type: 'post',
			data: {
				action: 'wcml_dashboard_set_currency',
				currency: jQuery( csLocator ).val(),
				wcml_nonce: wcml_admin_currency_selector.nonce
			},
			error: function( xhr ){
				alert( xhr.responseJSON.data );
			},
			success: function ( response ) {
				if ( response.success ) {
					window.location = window.location.href;
				}
			}
		} )
	} );
} )();