jQuery(document).ready(function() {
    geodir_init_admin_rating();
});

function geodir_init_admin_rating() {
    if (jQuery('#geodir-add-style-div #geodir_save_style_nonce').length) {
        GeoDir_Rating_Style.init();
    }

    if (jQuery('#geodir-add-rating-div #geodir_save_rating_nonce').length) {
        GeoDir_Ratings.init();
    }

    jQuery('.rating_checkboxs').on("click", function() {
        var val = jQuery(this).val();
        var ids = jQuery(this).attr('id');

        if (jQuery(this).is(':checked')) {
            jQuery.post(geodir_reviewrating_all_js_msg.geodir_reviewrating_admin_ajax_url + "&action=geodir_ajax_tax_cat", {
                    post_type: val
                })
                .done(function(data) {
                    jQuery('#categories_type' + ids).show();
                    jQuery('#categories_type' + ids).html(data);
                });
        } else {
            jQuery('#categories_type' + ids).hide();
        }
    });

    if (jQuery(".geodir-style-list .geodir-style-set-default").length) {
        jQuery(".geodir-style-list .geodir-style-set-default").on("click", function(e) {
            var style_id = jQuery(this).closest('tr').find('.gd-has-id').data('style-id');
            if (style_id) {
                geodir_set_default_style(style_id, jQuery(this), jQuery(this).closest('tr'));
            }
        });
    }
}

function geodir_set_default_style(id, $input, $el) {
    if (!id) {
        return false;
    }

    var data = {
        action: 'geodir_ajax_set_default_style',
        id: id,
        security: jQuery('.gd-has-id', $el).data('set-default-nonce')
    }
    jQuery.ajax({
        url: geodir_params.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: data,
        beforeSend: function() {
            $el.css({
                opacity: 0.6
            });
        },
        success: function(res, textStatus, xhr) {
            if (res.success) {
                jQuery('[name="' + $input.attr('name') + '"]').each(function() {
                    jQuery(this).prop('checked', false);
                });
                $input.prop('checked', true);
            }
            if (res.data.message) {
                alert(res.data.message);
            }
            $el.css({
                opacity: 1
            });
        },
        error: function(xhr, textStatus, errorThrown) {
            console.log(errorThrown);
            $el.css({
                opacity: 1
            });
        }
    });
}

var GeoDir_Rating_Style = {
    init: function() {
        var $self = this;
        this.el = jQuery('#geodir-add-style-div');
        this.form = jQuery('form#mainform');
        this.form.attr('action', 'javascript:void(0);');
        jQuery("#save_style", this.el).on("click", function(e) {
            $self.saveStyle(e);
        });
    },
    block: function() {
        jQuery('#save_style', this.el).prop('disabled', true);
        jQuery(this.el).css({
            opacity: 0.6
        });
    },
    unblock: function() {
        jQuery('#save_style', this.el).prop('disabled', false);
        jQuery(this.el).css({
            opacity: 1
        });
    },
    saveStyle: function(e) {
        e.preventDefault();
        var $self = this;
        var err = false;
        $self.form.find('input,select,textarea').each(function() {
            if (jQuery(this).attr('required') == 'required' && !jQuery(this).val()) {
                jQuery(this).focus();
                err = true;
                return false;
            }
        });
        if (err) {
            return false;
        }
        $self.block();
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: $self.form.serialize() + '&action=geodir_ajax_save_style',
            beforeSend: function() {},
            success: function(res, textStatus, xhr) {
                var msg_class;
                jQuery('.gd-save-style-message', $self.el).remove();

                if (res && res.data) {
                    if (res.data.message) {
                        jQuery('#geodir-add-style-div h2').after('<div class="gd-save-style-message error"><p>' + res.data.message + '</p></div>');
                        $self.unblock();
                    } else {
                        window.location.href = res.data.url;
                        $self.unblock();
                    }

                }
                jQuery('html, body').animate({
                    scrollTop: jQuery("#mainform").offset().top
                }, 100);
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(errorThrown);
                $self.unblock();
            }
        });
    },
}

var GeoDir_Ratings = {
    init: function() {
        var $self = this;
        this.el = jQuery('#geodir-add-rating-div');
        this.form = jQuery('form#mainform');
        this.form.attr('action', 'javascript:void(0);');
        jQuery("#save_rating", this.el).on("click", function(e) {
            var validate = true;

            if (jQuery(this).closest('#geodir-add-rating-div').find('select[id="geodir_rating_style_dl"]').val() == 0) {
                aui_toast('geodir_reviewrating_rating_error_style', 'error', geodir_reviewrating_all_js_msg.geodir_reviewrating_select_multirating_style);
                validate = false;
                return false;
            }

            if (!jQuery(this).closest('#geodir-add-rating-div').find('input[name="rating_title"]').val()) {
                aui_toast('geodir_reviewrating_rating_error_title', 'error', geodir_reviewrating_all_js_msg.geodir_reviewrating_enter_rating_title);
                validate = false;
                return false;
            }

            if (jQuery(this).closest('#geodir-add-rating-div').find('.rating_checkboxs:checked').length == 0) {
                aui_toast('geodir_reviewrating_rating_error_pt', 'error', geodir_reviewrating_all_js_msg.geodir_reviewrating_select_post_type);
                validate = false;
                return false;
            }

            jQuery(this).closest('#geodir-add-rating-div').find('.rating_checkboxs:checked').each(function(i) {
                var ids = jQuery(this).attr('id');
                if (jQuery('#categories_type' + ids + ' option:selected').length == 0) {
                    cpt = jQuery('#' + ids).val()
                    aui_toast('geodir_reviewrating_rating_error_cat_' + cpt, 'error', geodir_reviewrating_all_js_msg.geodir_reviewrating_please_select + ' ' + jQuery('#' + ids).val() + ' ' + geodir_reviewrating_all_js_msg.geodir_reviewrating_categories_text);
                    validate = false;
                    return false;
                }
            });

            if (validate == true) {
                $self.saveRating(e);
            }
        });
    },
    block: function() {
        jQuery('#save_rating', this.el).prop('disabled', true);
        jQuery(this.el).css({
            opacity: 0.6
        });
    },
    unblock: function() {
        jQuery('#save_rating', this.el).prop('disabled', false);
        jQuery(this.el).css({
            opacity: 1
        });
    },
    saveRating: function(e) {
        e.preventDefault();
        var $self = this;
        var err = false;
        $self.form.find('input,select,textarea').each(function() {
            if (jQuery(this).attr('required') == 'required' && !jQuery(this).val()) {
                jQuery(this).focus();
                err = true;
                return false;
            }
        });
        if (err) {
            return false;
        }
        $self.block();
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: $self.form.serialize() + '&action=geodir_ajax_save_rating',
            beforeSend: function() {},
            success: function(res, textStatus, xhr) {
                var msg_class;
                jQuery('.gd-save-rating-message', $self.el).remove();

                if (res && res.data) {
                    if (res.data.message) {
                        jQuery('.form-table', $self.el).before('<div class="gd-save-rating-message error"><p>' + res.data.message + '</p></div>');
                    } else {
                        window.location.href = res.data.url;
                    }
                }

                $self.unblock();
                jQuery('html, body').animate({
                    scrollTop: jQuery("#mainform").offset().top
                }, 100);
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(errorThrown);
                $self.unblock();
            }
        });
    },
}

function geodir_reviewrating_delete_rating(el) {
    aui_confirm(geodir_params.txt_are_you_sure, geodir_params.txt_delete, geodir_params.txt_cancel, true).then(function(confirmed) {
        if (confirmed) {
            jQuery.ajax({
                    url: jQuery(el).attr('href'),
                    type: 'POST',
                    data: {},
                    dataType: 'json',
                    beforeSend: function(xhr, obj) {
                        jQuery(el).prop("disabled", true);
                    }
                })
                .done(function(data, textStatus, jqXHR) {
                    if (typeof data == 'object') {
                        if (data.data.message) {
                            aui_toast('geodir_new_wp_template_error', 'error', data.data.message);
                        }

                        jQuery(el).closest('tr').fadeOut();

                        if (true === data.data.reload && parseInt($btn.data('reload')) === 1) {
                            window.location.reload();
                            return;
                        }
                    }
                })
                .always(function(data, textStatus, jqXHR) {
                    jQuery(el).prop("disabled", false);
                });
        } else {
            return false;
        }
    });

    return false;
}

jQuery(document).ready(function() {
    jQuery('#geodir_rating_style_dl').on("change", function() {
        if (jQuery('#geodir_rating_style_dl').val() != 0) {
            jQuery('#multi_rating_category_tr').fadeOut();
        } else if (jQuery('#geodir_rating_style_dl').val() == 0) {
            jQuery('#multi_rating_category_tr').fadeIn();
        }
    });
})

function style_the_text_box(check_cond) {
    var total = document.getElementById('hidden-style-text').value;
    var serialized = jQuery('#hidden-style-serialized').val();

    if (serialized == '1' && total != '') {
        totalarr = geodir_reviewrating_unserialize(total);
    } else {
        totalarr = total.split(",");
    }
    len = totalarr.length;
    var numeric = isNaN(document.getElementById('style_count').value);
    var numeric_value = document.getElementById('style_count').value;

    if (numeric == false) {
        if (numeric_value > 10) {
            alert(geodir_reviewrating_all_js_msg.geodir_reviewrating_please_enter_below + ' 10');
            return false;
        } else if (numeric_value < 3) {
            alert(geodir_reviewrating_all_js_msg.geodir_reviewrating_please_enter_above + ' 2');
            return false;
        } else {
            numeric = document.getElementById('style_count').value;
            var input_box;
            var n = '';

            var num = 0;
            for (var cond = 1; cond <= numeric; cond++) {
                if (len >= cond) {
                    var rat_text = totalarr[num];
                    rat_text = rat_text !== '' ? rat_text.replace(/\\'/g, "'") : rat_text;
                    rat_text = rat_text !== '' ? rat_text.replace(/\\"/g, '&quot;') : rat_text;
                    input_box = '<tr valign="top"><th scope="row" class="titledesc"><label for="star_rating_text">' + cond + '&nbsp;' + geodir_reviewrating_all_js_msg.geodir_reviewrating_star_text + '</label></th><td class="forminp forminp-text"><input name="star_rating_text[]" value="' + rat_text + '" class="regular-text star_rating_text" type="text" required=""></td></tr>'

                } else {
                    input_box = '<tr valign="top"><th scope="row" class="titledesc"><label for="star_rating_text">' + cond + '&nbsp;' + geodir_reviewrating_all_js_msg.geodir_reviewrating_star_text + '</label></th><td class="forminp forminp-text"><input name="star_rating_text[]" value="" class="regular-text star_rating_text" type="text" required=""></td></tr>'
                }
                num++;
                n = n + input_box;
            }

        }

        jQuery('#style_texts').html(n);
    } else if (numeric == true) {
        alert(geodir_reviewrating_all_js_msg.geodir_reviewrating_numeric_validation);
        document.getElementById('overall_count').focus;
        document.getElementById('overall_count').value = '';
    }
}

function geodir_reviewrating_unserialize(data) {
    var that = this,
        utf8Overhead = function(chr) {
            var code = chr.charCodeAt(0);
            if (code < 0x0080) {
                return 0;
            }
            if (code < 0x0800) {
                return 1;
            }
            return 2;
        };
    error = function(type, msg, filename, line) {
        throw new that.window[type](msg, filename, line);
    };
    read_until = function(data, offset, stopchr) {
        var i = 2,
            buf = [],
            chr = data.slice(offset, offset + 1);
        while (chr != stopchr) {
            if ((i + offset) > data.length) {
                error('Error', 'Invalid');
            }
            buf.push(chr);
            chr = data.slice(offset + (i - 1), offset + i);
            i += 1;
        }
        return [buf.length, buf.join('')];
    };
    read_chrs = function(data, offset, length) {
        var i, chr, buf;
        buf = [];
        for (i = 0; i < length; i++) {
            chr = data.slice(offset + (i - 1), offset + i);
            buf.push(chr);
            length -= utf8Overhead(chr);
        }
        return [buf.length, buf.join('')];
    };
    _unserialize = function(data, offset) {
        var dtype, dataoffset, keyandchrs, keys, contig,
            length, array, readdata, readData, ccount,
            stringlength, i, key, kprops, kchrs, vprops,
            vchrs, value, chrs = 0,
            typeconvert = function(x) {
                return x;
            };
        if (!offset) {
            offset = 0;
        }
        dtype = (data.slice(offset, offset + 1)).toLowerCase();
        dataoffset = offset + 2;
        switch (dtype) {
            case 'i':
                typeconvert = function(x) {
                    return parseInt(x, 10);
                };
                readData = read_until(data, dataoffset, ';');
                chrs = readData[0];
                readdata = readData[1];
                dataoffset += chrs + 1;
                break;
            case 'b':
                typeconvert = function(x) {
                    return parseInt(x, 10) !== 0;
                };
                readData = read_until(data, dataoffset, ';');
                chrs = readData[0];
                readdata = readData[1];
                dataoffset += chrs + 1;
                break;
            case 'd':
                typeconvert = function(x) {
                    return parseFloat(x);
                };
                readData = read_until(data, dataoffset, ';');
                chrs = readData[0];
                readdata = readData[1];
                dataoffset += chrs + 1;
                break;
            case 'n':
                readdata = null;
                break;
            case 's':
                ccount = read_until(data, dataoffset, ':');
                chrs = ccount[0];
                stringlength = ccount[1];
                dataoffset += chrs + 2;
                readData = read_chrs(data, dataoffset + 1, parseInt(stringlength, 10));
                chrs = readData[0];
                readdata = readData[1];
                dataoffset += chrs + 2;
                if (chrs != parseInt(stringlength, 10) && chrs != readdata.length) {
                    error('SyntaxError', 'String length mismatch');
                }
                break;
            case 'a':
                readdata = {};
                keyandchrs = read_until(data, dataoffset, ':');
                chrs = keyandchrs[0];
                keys = keyandchrs[1];
                dataoffset += chrs + 2;
                length = parseInt(keys, 10);
                contig = true;
                for (i = 0; i < length; i++) {
                    kprops = _unserialize(data, dataoffset);
                    kchrs = kprops[1];
                    key = kprops[2];
                    dataoffset += kchrs;
                    vprops = _unserialize(data, dataoffset);
                    vchrs = vprops[1];
                    value = vprops[2];
                    dataoffset += vchrs;
                    if (key !== i) contig = false;
                    readdata[key] = value;
                }
                if (contig) {
                    array = new Array(length);
                    for (i = 0; i < length; i++) array[i] = readdata[i];
                    readdata = array;
                }
                dataoffset += 1;
                break;
            default:
                error('SyntaxError', 'Unknown / Unhandled data type(s): ' + dtype);
                break;
        }
        return [dtype, dataoffset - offset, typeconvert(readdata)];
    };
    return _unserialize((data + ''), 0)[2];
}