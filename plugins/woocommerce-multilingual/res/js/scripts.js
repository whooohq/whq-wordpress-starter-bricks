var WCML = {
    sanitize: function(s) {
        if (typeof s === 'string' || s instanceof String) {
            return s.replace(/<script[^>]*?>.*?<\/script>/gi, '');
        }

        return s;
    }
};

jQuery(function ($) {
    var discard = false;

    window.onbeforeunload = function (e) {
        if (discard) {
            return $('#wcml_warn_message').val();
        }
    }

    $('.wcml-section input[type="submit"]').click(function () {
        discard = false;
    });

    $('#wcml_custom_exchange_rates').submit(function () {

        var thisf = $(this);

        thisf.find(':submit').parent().prepend(icl_ajxloaderimg + '&nbsp;')
        thisf.find(':submit').prop('disabled', true);

        $.ajax({

            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: thisf.serialize(),
            success: function () {
                thisf.find(':submit').prev().remove();
                thisf.find(':submit').prop('disabled', false);
            }

        })

        return false;
    })

    $(document).on('click', '.wcml_save_base', function (e) {
        e.preventDefault();

        var elem = $(this);
        var dialog_saving_data = $(this).closest('.wcml-dialog-container');
        var link = '#wcml-edit-base-slug-' + elem.attr('data-base') + '-' + elem.attr('data-language') + '-link';
        var dialog_container = '#wcml-edit-base-slug-' + elem.attr('data-base') + '-' + elem.attr('data-language');
        $.ajax({
            type: "post",
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: "wcml_update_base_translation",
                base: elem.attr('data-base'),
                base_value: dialog_saving_data.find('#base-original').val(),
                base_translation: dialog_saving_data.find('#base-translation').val(),
                language: elem.attr('data-language'),
                wcml_nonce: $('#wcml_update_base_nonce').val()
            },
            success: function (response) {
                $(dialog_container).remove();
                $(link).find('i').remove();
                $(link).append('<i class="otgs-ico-edit" >');
                $(link).parent().prepend(WCML.sanitize(response));
            }
        })
    });

    /**
     * Function to display larger image on hover while you are in product list.
     **/

    $(function() {
        $('.original-image').mousemove(function(e) {
            $img = $("#" + $(this).data('image-id'));
            $img.show(200);
            $img.offset({
                top: e.pageY - 100,
                left: e.pageX + 15
            });
        }).mouseleave(function() {
            $img = $("#" + $(this).data('image-id'));
            $img.hide(200);
        });
    });

	/*
		 * Collapse functionality helper. Markup should resemble something like this
		 * <container#containerID>
		 * 		<button#buttonID aria-expanded="false" aria-controls="wrapperID">
		 * 			<span>button text</span>
		 * 		</button>
		 * 		<wrapper#wrapperID role="region" aria-labelledby="buttonID">content</wrapper>
		 * </container>
		 */
	function expandContainer(buttonID, containerID) {
		$(`#${buttonID}`).on('click', function() {
			const ariaExpanded = $(this).attr('aria-expanded') === 'true';
			$(this).attr('aria-expanded', !ariaExpanded);
			$(`#${containerID}`).toggleClass('expanded');
		});
	}
	expandContainer('translate_manually_toggle', 'translate_manually');

});

