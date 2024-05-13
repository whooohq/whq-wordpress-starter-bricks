jQuery(function($){
    var i;
    var ids = [
        '_virtual',
        '_downloadable',
        'product-type',
        '_backorders',
        '_manage_stock',
        '_stock',
        '_low_stock_amount',
        '_stock_status',
        '_sold_individually',
        'comment_status',
        '_tax_status',
        '_tax_class',
        'parent_id',
        'crosssell_ids',
        'upsell_ids',
        'grouped_products',
        'product_shipping_class'
    ];

    if( unlock_fields.file_paths == 1 ){
        ids.push('_download_type');
    }
    ids = ids.concat( non_standard_fields.ids );

    $('.wcml_prod_hidden_notice').prependTo('#woocommerce-product-data');

    for (i = 0; i < ids.length; i++) {
        $el = $('#'+ids[i]);
        if (!$el.length) {
            $el = $('*[name='+ids[i]+']');
        }
        $el.prop('disabled', true);
        $el.after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    }

    var buttons = [
        'add_variation',
        'link_all_variations',
        'attribute_taxonomy',
        'save_attributes',
        'add_new_attribute',
        'product_attributes .remove_row',
        'add_attribute',
        'select_all_attributes',
        'select_no_attributes',
        'edit-visibility'
    ];
    buttons = buttons.concat( non_standard_fields.classes );

    if( unlock_fields.file_paths == 1 ){
        buttons.push('downloadable_files .upload_file_button');
        buttons.push('downloadable_files .insert');
        buttons.push('downloadable_files .delete');

        $('.downloadable_files .upload_file_button,.downloadable_files .insert,.downloadable_files .delete')
            .on('click', function(){ return false; });
    }

    for (i = 0; i < buttons.length; i++) {
        $('.'+buttons[i]).prop('disabled',true);
        $('.'+buttons[i]).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    }

    $('#visibility .edit-visibility span').on('click', function() { return false; });

    $('.remove_variation').each(function(){
        $(this).prop('disabled', true);
        $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show().css('float','right'));
    });

    var inpt_names = [
        '_width',
        '_height',
        '_sku',
        '_length',
        '_weight',
        'product_length',
        '_regular_price',
        '_sale_price',
        '_sale_price_dates_from',
        '_sale_price_dates_to'
    ];

    if( unlock_fields.file_paths == 1 ){
        inpt_names.push('_download_limit');
        inpt_names.push('_download_expiry');
        inpt_names.push('_wc_file_names[]');
        inpt_names.push('_wc_file_urls[]');
    }
    inpt_names = inpt_names.concat( non_standard_fields.input_names );

    if( unlock_fields.menu_order == 1 ){
        inpt_names.push('menu_order');
    }

    for (i = 0; i < inpt_names.length; i++) {
        $('input[name="'+inpt_names[i]+'"]').prop('readonly', true);

        $('.dimensions_field span.wrap').css('float','left');
        if( inpt_names[i] == '_width' || inpt_names[i] == '_height' || inpt_names[i] == '_length' ){
            $('input[name="'+inpt_names[i]+'"]').css('margin-right',0);
            $('input[name="'+inpt_names[i]+'"]').css('float','none');
            $('input[name="'+inpt_names[i]+'"]').css('width','29%');

        }

        if( inpt_names[i] == '_sale_price_dates_to' ){
            $('input[name="'+inpt_names[i]+'"]').after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').css('float','left').show());
        }else{
            $('input[name="'+inpt_names[i]+'"]').after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
        }


    }

    $('#product_attributes td textarea,#product_attributes input[type="text"]').each(function(){
        $(this).prop('readonly', true);
        $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    });


    $(
        '#product_attributes input[type="checkbox"],' +
        '#_featured,' +
        '#product_attributes select.attribute_values'
    ).each(function () {
        $(this).prop('disabled', true);
        $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    });

    $('form#post input[type="submit"]').click(function(){
        for (i = 0; i < ids.length; i++) {
            $('#'+ids[i]).prop('disabled', false);
        }
        $('.woocommerce_variation select,#variable_product_options .toolbar select,.woocommerce_variation input[type="checkbox"],#product_attributes input[type="checkbox"]').each(function(){
            $(this).prop('disabled', false);
        });
    });

});

var wcml_lock_variation_fields = function( file_path_sync ){

    var check_attr = jQuery('.woocommerce_variation>h3 select').prop('disabled');

    if (typeof check_attr !== typeof undefined && check_attr !== false) {
        return;
    }

    jQuery('.woocommerce_variation>h3 select, #variable_product_options .toolbar select, .show_if_variation_manage_stock select').each(function(){

        jQuery(this).prop('disabled', true);
        jQuery(this).parent().append('<input type="hidden" name="'+jQuery(this).attr('name')+'" value="'+jQuery(this).val()+'" />');
        jQuery(this).after(jQuery('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    });

    var i = 0;
    var inpt_names = [
        '_width',
        '_height',
        '_sku',
        '_length',
        '_weight',
        'product_length',
        '_regular_price',
        '_sale_price',
        '_sale_price_dates_from',
        '_sale_price_dates_to',
        '_stock',
        '_low_stock_amount',
        '_download_limit',
        '_download_expiry'
    ];

    for (i = 0; i < inpt_names.length; i++) {

        //variation fields
        jQuery('input[name^="variable'+inpt_names[i]+'"]').each(function(){
            jQuery(this).prop('readonly', true);
            jQuery(this).after(jQuery('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
        });
    }

    //variation fields
    var var_checkboxes = ['_enabled','_is_downloadable','_is_virtual','_manage_stock'];
    for (i = 0; i < var_checkboxes.length; i++) {
        jQuery('input[name^="variable'+var_checkboxes[i]+'"]').each(function(){
            jQuery(this).prop('disabled', true);
            if( jQuery(this).prop('checked') ){
                jQuery(this).parent().append('<input type="hidden" name="'+jQuery(this).attr('name')+'" value="'+jQuery(this).val()+'" />');
            }
            jQuery(this).after(jQuery('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
        });
    }

    var var_selectboxes = ['_stock_status','_shipping_class','_tax_class'];
    for (i = 0; i < var_selectboxes.length; i++) {
        jQuery('select[name^="variable'+var_selectboxes[i]+'"]').each(function(){
            jQuery(this).prop('disabled', true);
            jQuery(this).parent().append('<input type="hidden" name="'+jQuery(this).attr('name')+'" value="'+jQuery(this).val()+'" />');
            jQuery(this).after(jQuery('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
        });
    }

    if( file_path_sync ){

        for (var key in file_path_sync) {

            if( file_path_sync[ key ] == 1){

                jQuery('input[name^="_wc_variation_file_names['+key+']"]').each(function(){
                    jQuery(this).prop('readonly', true);
                });

                jQuery('input[name^="_wc_variation_file_urls['+key+']"]').each(function(){
                    jQuery(this).prop('readonly', true);
                    jQuery(this).closest('tr').find('.upload_file_button').prop('disabled', true);
                    jQuery(this).closest('tr').find('.delete').prop('disabled', true);
                    jQuery(this).closest('tr').find('.delete').after(jQuery('.wcml_lock_img').clone().removeClass('wcml_lock_img').show().css('float','right'));
                });

                jQuery('input[name^="_wc_variation_file_urls['+key+']"]').closest('table').find('.insert').prop('disabled', true);
                jQuery('input[name^="_wc_variation_file_urls['+key+']"]').closest('table').find('.insert').after( jQuery('.wcml_lock_img').clone().removeClass('wcml_lock_img').show().css('float','left') );

            }

        }

    }

}