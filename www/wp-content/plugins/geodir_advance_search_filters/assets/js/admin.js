jQuery(function($) {
    if ($("#geodir_enable_autocompleter").length) {
        $("#geodir_enable_autocompleter").on("click", function() {
            geodir_adv_search_autocomplete_init("#geodir_enable_autocompleter");
        });
        geodir_adv_search_autocomplete_init("#geodir_enable_autocompleter");
    }
    jQuery("#gt-form-builder-tab ul li a").on("click", function() {
        if (!jQuery(this).attr('id')) {
            return;
        }
        var htmlvar_name = jQuery(this).attr('id').replace('gd-', '');
        var post_type = jQuery(this).closest('#gt-form-builder-tab').find('#gd_new_post_type').val();
        var id = 'new' + jQuery(".field_row_main ul.advance li:last").index();
        var manage_field_type = jQuery(this).closest('#geodir-available-fields').find(".manage_field_type").val();
        if (manage_field_type == 'advance_search') {
            jQuery.get(geodir_admin_ajax.url + '?action=geodir_ajax_advance_search_action&create_field=true', {
                    htmlvar_name: htmlvar_name,
                    listing_type: post_type,
                    field_id: id,
                    field_ins_upd: 'new'
                },
                function(data) {
                    jQuery('.field_row_main ul.advance').append(data);
                    jQuery('#licontainer_' + htmlvar_name).find('#sort_order').val(parseInt(jQuery('#licontainer_' + htmlvar_name).index()) + 1);
                    show_hide('field_frm' + htmlvar_name);
                    jQuery('html, body').animate({
                        scrollTop: jQuery("#licontainer_" + htmlvar_name).offset().top
                    }, 1000);
                });
            if (htmlvar_name != 'fieldset') {
                jQuery(this).closest('li').hide();
            }
        }
    });
    jQuery(".field_row_main ul.advance").sortable({
        opacity: 0.8,
        placeholder: "ui-state-highlight",
        cancel: "input,label,select",
        cursor: 'move',
        update: function() {
            var order = jQuery(this).sortable("serialize") + '&update=update';
            jQuery.get(geodir_admin_ajax.url + '?action=geodir_ajax_advance_search_action&create_field=true', order, function(theResponse) {});
        }
    });
    jQuery(document).on("click", 'input[name="geodir_distance_sorting"]', function() {
        var $li = jQuery(this).closest('li.dd-item');
        if (jQuery(this).is(":checked") == true) {
            jQuery('.gd-search-asc-row', $li).show();
            jQuery('.gd-search-desc-row', $li).show();
        } else {
            jQuery('.gd-search-asc-row', $li).hide();
            jQuery('.gd-search-desc-row', $li).hide();
        }
    });
    geodir_adv_search_init_fields_layout();
});

function geodir_adv_search_autocomplete_init(el) {
    $el = jQuery(el);
    if ($el.is(':checked') == true) {
        jQuery("#geodir_autocompleter_autosubmit").closest('tr').show();
    } else {
        jQuery("#geodir_autocompleter_autosubmit").closest('tr').hide();
        jQuery("#geodir_autocompleter_autosubmit").attr('checked', false);
    }
}

function geodir_adv_search_toggle_field_field(id) {
    jQuery('#' + id).toggle();
}

function geodir_adv_search_type_changed(el, id) {
    var $li = jQuery(el).closest('form');
    var value = jQuery(el).val();

    jQuery('[name="input_type"]', $li).val('RANGE');
    jQuery('.gd-range-expand-row', $li).css("display", 'none');
    if (value == 'TEXT') {
        jQuery('.gd-range-min-row', $li).css("display", 'none');
        jQuery('.gd-range-max-row', $li).css("display", 'none');
        jQuery('.gd-range-step-row', $li).css("display", 'none');
        jQuery('.gd-range-start-row', $li).css("display", 'none');
        jQuery('.gd-range-from-title-row', $li).css("display", 'none');
        jQuery('.gd-range-to-title-row', $li).css("display", 'none');
        jQuery('.gd-search-condition-select-row', $li).css("display", 'block');
        jQuery('[name="search_condition"]', $li).val('SINGLE');
        jQuery('#search_condition_select', $li).prop('selectedIndex', 0);
    } else {
        if (value == 'LINK' || value == 'CHECK') {
            jQuery('.gd-range-expand-row', $li).css("display", 'block');
        }
        jQuery('[name="search_condition"]', $li).val(value);
        jQuery('.gd-search-condition-select-row', $li).css("display", 'none');
        jQuery('.gd-range-min-row', $li).css("display", 'block');
        jQuery('.gd-range-max-row', $li).css("display", 'block');
        jQuery('.gd-range-step-row', $li).css("display", 'block');
        jQuery('.gd-range-start-row', $li).css("display", 'block');
        jQuery('.gd-range-from-title-row', $li).css("display", 'block');
        jQuery('.gd-range-to-title-row', $li).css("display", 'block');
    }
}

function geodir_adv_search_range_changed(el, id) {
    jQuery(el).closest('form').find('[name="search_condition"]').val(jQuery(el).val());
}

function geodir_adv_search_input_type_changed(el, id) {
    var $li = jQuery(el).closest('form');
    var value = jQuery(el).val();
    if (value == 'LINK' || value == 'CHECK') {
        jQuery('.gd-range-expand-row', $li).css("display", 'block');
    } else {
        jQuery('.gd-range-expand-row', $li).css("display", 'none');
    }
    // if its a check then we make it a multiple
    if (value == 'CHECK') {
        jQuery('.gd-search-operator-row', $li).css("display", 'block');
    } else {
        jQuery('.gd-search-operator-row', $li).css("display", 'none');
    }
}

function geodir_adv_search_difference(el) {
    if (jQuery(el).val() == 1) {
        jQuery(el).closest('li.dd-item').find('.gd-range-mode-row').show();
    } else {
        jQuery(el).closest('li.dd-item').find('.gd-range-mode-row').hide();
    }
}

/* Add new search field */
function geodir_adv_search_add_field(el) {
    var $this, gd_nonce, post_type, htmlvar_name;

    $this = jQuery(el);

    // check if there is an unsaved field
    if (jQuery('#setName_').length) {
        alert(geodir_params.txt_save_other_setting);
        jQuery('html, body').animate({
            scrollTop: jQuery("#setName_").offset().top
        }, 1000);
        return;
    }

    gd_nonce = jQuery("#gd_new_field_nonce").val();
    post_type = jQuery("#gd_new_post_type").val();
    htmlvar_name = $this.data('htmlvar_name');

    var data = {
        'action': 'geodir_cpt_search_field_form',
        'security': gd_nonce,
        'post_type': post_type,
        'htmlvar_name': htmlvar_name
    };

    jQuery.ajax({
        'url': ajaxurl,
        'type': 'POST',
        'data': data,
        'dataType': 'json',
        'success': function(res) {
            if (res.success) {
                jQuery('.gd-tabs-sortable .gd-sort-placeholder').remove();
                jQuery('.gd-tabs-sortable').append(res.data);
                geodir_adv_search_init_fields_layout();
                jQuery('.gd-tabs-sortable > li:last-child .dd-form').trigger('click');
                jQuery('html, body').animate({
                    scrollTop: jQuery("#setName_").offset().top
                }, 1000);

                // init new tooltips
                gd_init_tooltips();

                if (htmlvar_name != 'fieldset') {
                    $this.closest('li').hide();
                }
            } else {
                alert("something went wrong");
            }
        }
    });
}

/* Save search field order */
function geodir_adv_search_field_save_order(action, $hide_success) {
    var $fields, order, gd_nonce, $data;

    gd_nonce = jQuery("#gd_new_field_nonce").val();
    $fields = jQuery('.gd-tabs-sortable').nestedSortable('toArray', {
        startDepthCount: 0
    });

    order = {};
    jQuery.each($fields, function(index, field) {
        if (field.id) {
            order[index] = {
                id: field.id,
                tab_level: field.depth,
                tab_parent: field.parent_id
            };
        }
    });

    data = {
        'action': action,
        'security': gd_nonce,
        'fields': order
    };

    jQuery.ajax({
        'url': geodir_params.gd_ajax_url,
        'type': 'POST',
        'data': data,
        'dataType': 'json',
        'success': function(result) {
            if (result.success) {
                if (!$hide_success) {
                    aui_toast('gd_tabs_save_order_success', 'success', geodir_params.txt_order_saved);
                }
            } else {
                console.log(result.data);
                aui_toast('gd_tabs_save_order_error', 'error', geodir_params.rating_error_msg, '', result.data);
            }
        }
    });
}

/* Init search field layout */
function geodir_adv_search_init_fields_layout() {
    jQuery('.gd-tabs-sortable').nestedSortable({
        maxLevels: 2,
        handle: 'div.dd-handle',
        items: 'li',
        //toleranceElement: 'form', // @todo remove this if problems
        disableNestingClass: 'mjs-nestedSortable-no-nesting',
        helper: 'clone',
        placeholder: 'placeholder',
        forcePlaceholderSize: true,
        listType: 'ul',
        update: function(e, ui) {
            geodir_adv_search_field_save_order('geodir_cpt_search_save_order');
        }
    });

    // int the new select2 boxes
    jQuery("select.geodir-select").trigger('geodir-select-init');
    jQuery("select.geodir-select-nostd").trigger('geodir-select-init');
}

/* Save search field */
function geodir_adv_search_save_field(el) {
    var $this, $form, $li, nonce, data;

    $this = jQuery(el);
    $form = $this.closest("#geodir-field-settings");
    $li = $form.closest("li");
    nonce = jQuery("#gd_new_field_nonce").val();
    data = $form.find("select, textarea, input").serialize() + "&security=" + nonce + "&action=geodir_cpt_search_save_field";

    jQuery.ajax({
        'url': ajaxurl,
        'type': 'POST',
        'data': data,
        'dataType': 'json',
        'success': function(result) {
            if (result.success) {

                jQuery('#geodir-field-settings #save').html(jQuery('#geodir-field-settings #save').data('save-text')).removeClass('disabled');

                id = $form.find('#field_id').val();
                // if(!$id){$id = 'setName_';}

                jQuery('#setName_' + id).replaceWith(jQuery.trim(result.data));

                var new_id = jQuery(result.data).attr('id').replace("setName_", "");

                // if its an auto-save then we must do a little extra
                if (gd_doing_field_auto_save) {
                    gd_doing_field_auto_save = false;

                    if (id == '') {
                        jQuery('#geodir-field-settings #field_id').val(new_id);
                        var new_nonce = jQuery(result.data).data('field-nonce');
                        jQuery('#geodir-field-settings [name="_wpnonce"]').val(new_nonce);
                    }
                    jQuery('#setName_' + new_id + ' .dd-form').addClass('border-width-2 border-primary');
                } else {
                    aui_toast('gd_adv_search_save_success', 'success', geodir_params.txt_saved);

                    jQuery('#geodir-selected-fields .dd-form').removeClass('border-width-2 border-primary');
                    jQuery('#gd-fields-tab').tab('show');
                }

                geodir_adv_search_init_fields_layout();
                geodir_adv_search_field_save_order('geodir_cpt_search_save_order', true);

                aui_init();

            } else {
                alert(result.data);
            }
        }
    });
}

/* Delete search field */
function geodir_adv_search_delete_field(el) {
    var $this, $form, $li, nonce, post_type, id;

    aui_confirm(geodir_params.txt_are_you_sure, geodir_params.txt_delete, geodir_params.txt_cancel, true).then(function(confirmed) {
        if (confirmed) {
            $this = jQuery(el);
            $form = $this.closest(".dd-form");
            $li = $form.closest("li");
            nonce = jQuery("#gd_new_field_nonce").val();
            post_type = jQuery("#gd_new_post_type").val();
            id = jQuery($li).data('id');

            htmlvar_name = jQuery("input[name=htmlvar_name]", $form).val();

            $li.css("opacity", '.5');
            if (!id || id == 'new') {
                $li.remove();
                jQuery('#geodir-available-fields').find('#geodir-field-item-' + htmlvar_name).show();
                jQuery('#gd-fields-tab').tab('show');
                return;
            }

            var data = {
                'action': 'geodir_cpt_search_delete_field',
                'security': nonce,
                'post_type': post_type,
                'id': id
            };

            jQuery.ajax({
                'url': ajaxurl,
                'type': 'POST',
                'data': data,
                'dataType': 'json',
                'success': function(result) {
                    if (result.success) {
                        $li.remove();
                        geodir_adv_search_init_fields_layout();
                        jQuery('#geodir-available-fields').find('#geodir-field-item-' + htmlvar_name).show();
                        aui_toast('gd_delete_adv_search_field_success', 'success', geodir_params.txt_deleted);
                        jQuery('#gd-fields-tab').tab('show');
                    } else {
                        alert(result.data);
                        $li.css("opacity", '1');
                    }
                }
            });
        }
    });
}