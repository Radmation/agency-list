jQuery(document).ready(function($) {
    // because of how GeoDirectory is built (...), make these adjustments via JS
    const $address_fields = $('#geodir_address_country_row, #geodir_address_region_row, #geodir_address_city_row'),
        $registration_fields = $('#post_title_row, #post_title_row'),
        $all_custom_fields = $address_fields.add($registration_fields);

    $address_fields.addClass('dws_gd_form_row').addClass('dws_gd_form_row_address');
    $registration_fields.addClass('dws_gd_form_row').addClass('dws_gd_form_row_text'); // yes, both user registration fields have the same ID as the post title...

    $registration_fields.each(function() {
        const $note = $(this).find('.geodir_message_note'),
            $this = $(this), $input = $(this).find('input');

        if ($note.length === 1) {
            let text = $note.text();
            $input.attr('placeholder', text);
        }
    });

    $all_custom_fields.each(function() {
        const $note = $(this).find('.geodir_message_note'),
            $this = $(this);

        if ($note.length === 1) {
            let text = $note.text();
            $note.remove();

            $this.find('label').append('<i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="' + text + '"></i>');
        }
    });
    $all_custom_fields.each(function() {
        const $error = $(this).find('.geodir_message_error'),
            $this = $(this), $input = $this.find('input, select');

        if ($error.length === 1) {
            let text = $error.text(), name = $input.parent().attr('id').replace('_row', '');
            $error.remove();

            $input.wrap('<div id="' + name + '_input_wrapper" class="dws_geodir_input_wrapper"></div>');
            $input.parent().append('<span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="' + text + '" data-toggle="tooltip" style="display: none;"></span>');
        }
    });

    // other custom JS behavior
    $('.dws_geodir_message_error').on('DOMSubtreeModified', function() {
        $(this).attr('data-original-title', $(this).html());
    });

    $('#post_title_input_wrapper.dws_geodir_post_title').on('DOMSubtreeModified', function() { // the class selector fixes an issue with GD's DOM where there are multiple elements with the same ID...
        const $duplicate_error_message = $(this).find('.geodir_duplicate_message_error');
        if ($duplicate_error_message.length < 1) { return; }

        $duplicate_error_message.addClass('fas').addClass('fa-exclamation-circle');
        $duplicate_error_message.attr('data-original-title', $duplicate_error_message.html());
        $duplicate_error_message.tooltip();
    });

    $('select.geodir-select.dws_enhanced').each(function() {
        const $options_wrapper = $('.dws_gd_form_row_select_options[data-field-name="' + $(this).attr('name') + '"]');
        $(this).find('option').each(function() {
            if ($(this).val() === '') { return; }
            $options_wrapper.append('<li data-value="' + $(this).val() + '">' + $(this).text() + '</li>');
        });
    });

    $('.dws_gd_select_placeholder').click(function() {
        const $options_wrapper = $('.dws_gd_form_row_select_options[data-field-name="' + $(this).siblings('select').attr('name') + '"]');

        $(this).toggleClass('active');
        $options_wrapper.toggleClass('active');
    });

    $('.dws_gd_form_row_select_options li').click(function() {
        const $parent_select = $('select.geodir-select.dws_enhanced[name="' + $(this).parent().data('field-name') + '"]'),
            $dummy_select = $parent_select.siblings('.dws_gd_select_placeholder');

        $parent_select.val($(this).data('value')).change();

        $dummy_select.addClass('selected');
        $dummy_select.find('span').html($(this).text());

        $dummy_select.click();
    });
});