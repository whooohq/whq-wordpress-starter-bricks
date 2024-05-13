jQuery(function($) {
	var widget = $('#ws_php_error_log'),

		dashboardNoFilterOption = widget.find('#elm_dashboard_message_filter_all'),
		dashboardCustomFilterOption = widget.find('#elm_dashboard_message_filter_selected'),

		emailMatchFilterOption = widget.find('#elm_email_message_filter_same'),
		emailCustomFilterOption = widget.find('#elm_email_message_filter_selected'),

		dashboardFilterOptions = widget.find('input[name^="ws_php_error_log[dashboard_severity_option-"]'),
		emailFilterOptions = widget.find('input[name^="ws_php_error_log[email_severity_option-"]');

	function updateDashboardOptions() {
		dashboardFilterOptions.prop('disabled', !dashboardCustomFilterOption.is(':checked'))
	}
	function updateEmailOptions() {
		emailFilterOptions.prop('disabled', !emailCustomFilterOption.is(':checked'));
	}

	//First enable/disable the checkboxes when the page loads.
	updateDashboardOptions();
	updateEmailOptions();

	//Then refresh them when the user changes filter settings.
	dashboardCustomFilterOption.add(dashboardNoFilterOption).on('change', function() {
		updateDashboardOptions();
	});
	emailCustomFilterOption.add(emailMatchFilterOption).on('change', function() {
		updateEmailOptions();
	});

	//Handle the "Ignore" and "Mark as fixed" links.
	widget.on('click', '.elm-ignore-message, .elm-mark-as-fixed', function() {
		var row = $(this).closest('.elm-entry'),
			message = row.data('raw-message');

		//Hide all copies of this message.
		row.closest('.elm-log-entries').find('.elm-entry').filter(function() {
			return $(this).data('raw-message') === message;
		}).hide().remove();

		var action;
		if ($(this).hasClass('elm-mark-as-fixed')) {
			action = AjawV1.getAction('elm-mark-as-fixed');
		} else {
			action = AjawV1.getAction('elm-ignore-message');
		}
		action.post({ message: message });

		return false;
	});

	//And the "Unignore" and "Mark as not fixed" links.
	widget.on('click', '.elm-unignore-message, .elm-mark-as-not-fixed', function() {
		var row = $(this).closest('tr'),
			message = row.data('raw-message');

		row.remove();

		var action;
		if ($(this).hasClass('elm-mark-as-not-fixed')) {
			action = AjawV1.getAction('elm-mark-as-not-fixed');
		} else {
			action = AjawV1.getAction('elm-unignore-message');
		}
		action.post({ message: message });

		return false;
	});

	function handleClearMessagesButton(button, tableSelector, noticeSelector, ajaxActionName) {
		var actionText = button.text();

		button.prop('disabled', true);
		button.text(button.data('progressText'));

		//Hide the entire table.
		var table = widget.find(tableSelector);
		var totalMessages = table.find('tr').length;
		table.hide();

		var action = AjawV1.getAction(ajaxActionName);
		if (action) {
			action.post(
				{total: totalMessages},
				function () {
					//Success!
					table.remove();
					button.remove();
					widget.find(noticeSelector).show();
				},
				function () {
					//Something went wrong. Restore the table and the button.
					button.text(actionText);
					button.prop('disabled', false)
					table.show();
				}
			);
		}
	}

	//Handle the "Clear Ignored Messages" button.
	widget.find('#elm-clear-ignored-messages').on('click', function () {
		handleClearMessagesButton(
			$(this),
			'.elm-ignored-messages',
			'#elm-no-ignored-messages-notice',
			'elm-clear-ignored-messages'
		);
		return false;
	});

	//Handle the "Clear Fixed Messages" button.
	widget.find('#elm-clear-fixed-messages').on('click', function () {
		handleClearMessagesButton(
			$(this),
			'.elm-fixed-messages',
			'#elm-no-fixed-messages-notice',
			'elm-clear-fixed-messages'
		);
		return false;
	});

	//Handle the "Show X more" context link.
	widget.on('click', '.elm-show-mundane-context', function() {
		var link = $(this),
			container = link.closest('.elm-context-group-content');
		container.removeClass('elm-hide-mundane-items');
		link.hide().closest('tr,li').hide();
		return false;
	});

	//Handle the "Hide" link that hides the "Upgrade to Pro" notice.
	widget.on('click', '.elm-hide-upgrade-notice', function(event) {
		$(this).closest('.elm-upgrade-to-pro-footer').hide();
		AjawV1.getAction('elm-hide-pro-notice').post();
		event.preventDefault();
		return false;
	});

	//Move the "Upgrade to Pro" section to the very bottom of the widget settings panel, below the "Submit" button.
	var settingsForm = widget.find('.dashboard-widget-control-form'),
		proSection = settingsForm.find('#elm-pro-version-settings-section');
	if (settingsForm.length > 0) {
		proSection.appendTo(settingsForm).show();
	}
});
