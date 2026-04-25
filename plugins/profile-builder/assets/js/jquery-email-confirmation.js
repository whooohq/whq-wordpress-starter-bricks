
jQuery(document).ready(function() {

	// Email Confirmation
	if (jQuery ('#wppb_settings_email_confirmation').prop('checked') ) {
		jQuery('#wppb-settings-activation-page').show();
		jQuery('#unconfirmed-user-emails').show();
	}
	else {
		jQuery ( '#wppb-settings-activation-page' ).hide();
		jQuery ( '#unconfirmed-user-emails' ).hide();
	}

	jQuery ('#wppb_settings_email_confirmation').on('change', function() {
		if (jQuery ('#wppb_settings_email_confirmation').prop('checked') ) {
			jQuery('#wppb-settings-activation-page').show();
			jQuery('#unconfirmed-user-emails').show();
		}
		else {
			jQuery ( '#wppb-settings-activation-page' ).hide();
			jQuery ( '#unconfirmed-user-emails' ).hide();
		}
	});


	// Admin Approval
	if (jQuery( '#adminApprovalSelect' ).prop('checked')) {
		jQuery('.wppb-aa-user-list').show();
	}
	else {
		jQuery ( '.wppb-aa-user-list' ).hide();
	}

	jQuery ('#adminApprovalSelect').on('change', function() {
		if (jQuery( '#adminApprovalSelect' ).prop('checked')) {
			jQuery('.wppb-aa-user-list').show();
		}
		else {
			jQuery ( '.wppb-aa-user-list' ).hide();
		}
	});


	// Roles Editor
	if (jQuery( '#rolesEditorSelect' ).prop('checked')) {
		jQuery('.wppb-roles-editor-link').show();
	}
	else {
		jQuery ( '.wppb-roles-editor-link' ).hide();
	}

	jQuery ('#rolesEditorSelect').on('change', function() {
		if (jQuery( '#rolesEditorSelect' ).prop('checked')) {
			jQuery('.wppb-roles-editor-link').show();
		}
		else {
			jQuery ( '.wppb-roles-editor-link' ).hide();
		}
	});

});