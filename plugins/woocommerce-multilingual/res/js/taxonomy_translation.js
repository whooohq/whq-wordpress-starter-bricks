jQuery(function ($) {

    $(document).on('submit', '#wcml_tt_sync_variations', function () {

        var this_form = $('#wcml_tt_sync_variations');
        var data = this_form.serialize();
        this_form.find('.wcml_tt_spinner').fadeIn();
        this_form.find('input[type=submit]').prop('disabled', true);

        $.ajax({
            type: "post",
            url: ajaxurl,
            dataType: 'json',
            data: data,
            success: function (response) {
                this_form.find('.wcml_tt_sycn_preview').html(response.progress);
                if (response.go) {
                    this_form.find('input[name=last_post_id]').val(response.last_post_id);
                    this_form.find('input[name=languages_processed]').val(response.languages_processed);
                    this_form.trigger('submit');
                } else {
                    this_form.find('input[name=last_post_id]').val(0);
                    this_form.find('.wcml_tt_spinner').fadeOut();
                    this_form.find('input').prop('disabled', false);
                    jQuery('#wcml_tt_sync_assignment').fadeOut();
                    jQuery('#wcml_tt_sync_desc').fadeOut();
                }

            }
        });

        return false;

    });


    $(document).on('submit', '#wcml_tt_sync_assignment', function () {

        var this_form = $('#wcml_tt_sync_assignment');
        var parameters = this_form.serialize();

        this_form.find('.wcml_tt_spinner').fadeIn();
        this_form.find('input').prop('disabled', true);

        $('.wcml_tt_sync_row').remove();

        $.ajax({
            type: "POST",
            dataType: 'json',
            url: ajaxurl,
            data: 'action=wcml_tt_sync_taxonomies_in_content_preview&wcml_nonce=' + $('#wcml_sync_taxonomies_in_content_preview_nonce').val() + '&' + parameters,
            success: function (ret) {

                this_form.find('.wcml_tt_spinner').fadeOut();
                this_form.find('input').prop('disabled', false);

                if (ret.errors) {
                    this_form.find('.errors').html(WCML.sanitize(ret.errors));
                } else {
                    jQuery('#wcml_tt_sync_preview').html(WCML.sanitize(ret.html));
                    jQuery('#wcml_tt_sync_assignment').fadeOut();
                    jQuery('#wcml_tt_sync_desc').fadeOut();
                }

            }

        });

        return false;

    });

    $(document).on('click', 'form.wcml_tt_do_sync a.submit', function () {

        var this_form = $('form.wcml_tt_do_sync');
        var parameters = this_form.serialize();

        this_form.find('.wcml_tt_spinner').fadeIn();
        this_form.find('input').prop('disabled', true);

        jQuery.ajax({
            type: "POST",
            dataType: 'json',
            url: ajaxurl,
            data: 'action=wcml_tt_sync_taxonomies_in_content&wcml_nonce=' + $('#wcml_sync_taxonomies_in_content_nonce').val() + '&' + parameters,
            success: function (ret) {

                this_form.find('.wcml_tt_spinner').fadeOut();
                this_form.find('input').prop('disabled', false);

                if (ret.errors) {
                    this_form.find('.errors').html(ret.errors);
                } else {
                    this_form.closest('.wcml_tt_sync_row').html(ret.html);
                }

            }

        });

        return false;


    });

    $(document).on('click', '#term-table-sync-header', function () {
        $('#wcml_tt_sync_assignment').hide();
        $('#wcml_tt_sync_desc').hide();
    });

    $(document).on('click', '#term-table-header', function () {
        if( $('#wcml_tt_sync_assignment').data('sync') ) {
            $('#wcml_tt_sync_assignment').show();
            $('#wcml_tt_sync_desc').show();
        }
    });


});

