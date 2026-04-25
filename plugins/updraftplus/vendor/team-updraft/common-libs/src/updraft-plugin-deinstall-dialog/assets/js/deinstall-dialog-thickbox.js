jQuery(function($) {
	window.updraft_deinstall_thickbox_v1 = window.updraft_deinstall_thickbox_v1 || function(e, plugin) {
		e.preventDefault();

		var selector = 'updraft-deinstall-thickbox-v1';
		var data = window['updraft_deinstall_data_' + plugin];
		
		// Create the dialog HTML for ThickBox
		var html = '<div id="' + selector + '" style="display:none;">' +
			'<div id="updraft-deinstall-thickbox-v1-content"></div>' +
			'<div id="plugin-information-footer">' +
				'<button class="updraft-deinstall-confirm-btn button button-primary">' + data.deactivate_label + '</button>' +
				'<button class="updraft-deinstall-cancel-btn right button">' + data.cancel_label + '</button>' +
			'</div>' +
		'</div>';

		// Append the dialog HTML to the body (ensure it's appended only once)
		if ($('#' + selector).length === 0) {
			$('body').append(html);
		}

		// Apply the dialog content to the thickbox
		$('#updraft-deinstall-thickbox-v1-content').html(data.dialog_html);

		// Open ThickBox with the created HTML content
		tb_show(data.dialog_title, '#TB_inline?inlineId=' + selector);

		var thickbox_window = $('#TB_window');
		var thickbox_ajax_content = $('#TB_ajaxContent');

		// Apply additional inline styles
		thickbox_window.css({
			'width': '400px',
			'height': 'auto',
			'max-height': '250px',
			'margin-left': '0'
		});

		thickbox_ajax_content.css({
			'width': '380px',
		});

		var thickbox_window_width = thickbox_window.outerWidth();
		var thickbox_window_height = thickbox_window.outerHeight();

		var window_width = $(window).width();
		var window_height = $(window).height();

		var top = (window_height / 2) - (thickbox_window_height / 2);
		var left = (window_width / 2) - (thickbox_window_width / 2);

		thickbox_window.css({
			'top': top + 'px',
			'left': left + 'px'
		});

		// Unbind any existing click event handlers to prevent multiple bindings
		$(document).off('click', '.updraft-deinstall-confirm-btn');
		$(document).off('click', '.updraft-deinstall-cancel-btn');
		
		// Handle the "Deactivate" button click
		$(document).one('click', '.updraft-deinstall-confirm-btn', function() {
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

			// Close ThickBox
			tb_remove();
		});

		// Handle the "Cancel" button click
		$(document).one('click', '.updraft-deinstall-cancel-btn', function() {
			// Close ThickBox
			tb_remove();

			// Prevent duplicate instance
			$('#' + selector).remove();
		});
	};
});