var duplicate_validation_check = false;
jQuery(function($) {
    if ($('form#geodirectory-add-post').length) {
        geodir_duplicate_alert_setup($);

        $('body').on("geodir_social_import_data", function(e) {
            geodir_duplicate_alert_setup($);
        });

        let timeout = null;

        $('body').on('keyup', 'input.geodir_textfield', function() {
            var handle = $(this);
            duplicate_validation_check = false;
            var current_field_id = handle.attr('id')
            if (!(current_field_id && $.inArray(current_field_id, ['post_title', 'address_street', 'address_zip', 'phone', 'email', 'website' ]) > -1)) {
                return;
            }
            // Clear the timeout if it has already been set.
            clearTimeout(timeout);
            // Make a new timeout set to go off in 1000ms (1 second)
            timeout = setTimeout(function() {
                var get_current_posttype = $('#geodirectory-add-post input[name=post_type]').val(),
                    current_field_value = handle.val(),
                    get_post_parent = $('#geodirectory-add-post input[name=post_parent]').val();

                if ('' == get_post_parent) {
                    geodir_duplicate_alert_trigger(get_current_posttype, current_field_id, current_field_value);
                }
            }, 1000);

        });
    }
});

function geodir_duplicate_alert_setup($) {
    // disable submit button before validation.
    var get_posttype = $('#geodirectory-add-post input[name=post_type]').val(),
        get_title_field_val = $('input#post_title').val(),
        get_address_field_val = $('input#address_street').val(),
        get_zip_field_val = $('input#address_zip').val(),
        get_phone_field_val = $('input#phone').val(),
        get_email_field_val = $('input#email').val(),
        get_post_parent = $('#geodirectory-add-post input[name=post_parent]').val(),
        get_website_field_val = $('input#website').val();
    if ('' == get_post_parent) {
        jQuery('#geodir-add-listing-submit button').attr('disabled', 'disabled');
        geodir_duplicate_alert_trigger(get_posttype, 'post_title', get_title_field_val);
        geodir_duplicate_alert_trigger(get_posttype, 'address_street', get_address_field_val);
        geodir_duplicate_alert_trigger(get_posttype, 'address_zip', get_zip_field_val);
        geodir_duplicate_alert_trigger(get_posttype, 'phone', get_phone_field_val);
        geodir_duplicate_alert_trigger(get_posttype, 'email', get_email_field_val);
        geodir_duplicate_alert_trigger( get_posttype, 'website', get_website_field_val );
    }
}

function geodir_duplicate_alert_trigger(post_type, field_id, field_value) {
    var gd_alert_ajaxurl = geodir_params.gd_ajax_url;

    var data = {
        'action': 'geodir_duplicate_alert_action',
        'post_type': post_type,
        'field_id': field_id,
        'field_value': field_value,
    };

    jQuery.post(gd_alert_ajaxurl, data, function(response) {
        response = jQuery.parseJSON(response);
        var message = response.message;
        var field_id = response.field_id;
        var gd_duplicate_alert_message_html = "<span class='geodir_duplicate_message_error'>" + message + "</span>";

        if (field_id != null) {
            // after ajax enable submit button again and let the validation work.
            jQuery('#geodir-add-listing-submit button').removeAttr('disabled');
            var get_parent_id = jQuery('#' + field_id).parent().attr('id');
            if ('address_street' == field_id) {
                get_parent_id = 'geodir_address_street_row';
            }

            if (message != null) {
                duplicate_validation_check = true;
                jQuery('#' + get_parent_id + ' span.geodir_duplicate_message_error').remove();
                jQuery('#' + get_parent_id).append(gd_duplicate_alert_message_html);
            } else {
                jQuery('#' + get_parent_id + ' span.geodir_duplicate_message_error').remove();
                jQuery('#geodir-add-listing-submit button').removeAttr('disabled');
            }
            if( duplicate_validation_check ){
                jQuery('#geodir-add-listing-submit button').attr('disabled', 'disabled');
            }
        }
    });
}