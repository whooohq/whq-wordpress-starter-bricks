/**
 * Deactivation Dialog Script for UpdraftPlus Plugin
 * This script customizes the deactivation dialog by adding specific classes to buttons
 */

(function($) {
	$(document).on('dialogopen', '#updraftplus-deinstall-dialog', function() {
		$('body').addClass('udp-no-scroll');
		var btn = $('.updraftplus-ui-deinstall-dialog .ui-dialog-buttonset button:first-child');
		$('#updraftplus-deinstall-dialog').on('click', '.udp-toggle-container', function() {
			btn.text($(this).find('input:checked').length ? upraftplusdialog.remove : upraftplusdialog.deactivate);
		});
		$(this).on('dialogclose', function() {
			$('body').removeClass('udp-no-scroll');
		});
	});

})(jQuery);
