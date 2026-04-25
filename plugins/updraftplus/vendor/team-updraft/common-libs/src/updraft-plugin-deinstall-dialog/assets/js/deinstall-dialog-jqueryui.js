jQuery(function($) {
	window.updraft_deinstall_jqueryui_v1 = window.updraft_deinstall_jqueryui_v1 || function(e, plugin) {
		e.preventDefault();

		var data = window['updraft_deinstall_data_' + plugin];

		// Show the dialog with the specified title and buttons
		$('<div />').html(data.dialog_html).dialog({
			title: data.dialog_title,
			modal: true,
			buttons: [
				{
					text: data.deactivate_label,
					click: function() {
						var $form = $('#' + plugin + '-deactivate-form');
						var form_data = $form.serializeArray();
						form_data.push(
							{
								name: '_nonce',
								value: data.nonce
							},
							{
								name: 'action',
								value: plugin + '_deinstall_confirm'
							}
						);

						$.post(ajaxurl, form_data).always(function() {
							window.location.href = e.target.href;
						});

						$(this).dialog('close');
					}
				},
				{
					text: data.cancel_label,
					click: function() {
						$(this).dialog('close');
					}
				}
			]
		});
	};
});