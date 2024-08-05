jQuery(function($) {
  if ($("input[name=lm_show_switcher_in_nav]").length) {
    $("input[name=lm_show_switcher_in_nav]").on("click", function() {
      geodir_location_switcher_nav_changed($(this));
    });
    geodir_location_switcher_nav_changed($("input[name=lm_show_switcher_in_nav]"));
  }

  if ($("input[name=lm_default_country]").length) {
    $("input[name=lm_default_country]").on("click", function() {
      if ($(this).is(':checked')) {
        geodir_location_show_selected_countries($(this));
      }
    });
    geodir_location_show_selected_countries($("input[name=lm_default_country]:checked"));
  }

  if ($("input[name=lm_default_region]").length) {
    $("input[name=lm_default_region]").on("click", function() {
      if ($(this).is(':checked')) {
        geodir_location_show_selected_regions($(this));
      }
    });
    geodir_location_show_selected_regions($("input[name=lm_default_region]:checked"));
  }

  if ($("input[name=lm_default_city]").length) {
    $("input[name=lm_default_city]").on("click", function() {
      if ($(this).is(':checked')) {
        geodir_location_show_selected_cities($(this));
      }
    });
    var countryChecked = $("input[name=lm_default_city]:checked");
    geodir_location_show_selected_cities(countryChecked);
  }

  if ($('#geodir_save_location_nonce').length) {
    GeoDir_Location.init();
  }

  if ($('#geodir_save_neighbourhood_nonce').length) {
    GeoDir_Location_Neighbourhood.init();
  }

  if ($('#geodir-save-seo-div #geodir_save_seo_nonce').length) {
    GeoDir_Location_SEO.init();
  }

  if ($(".neighbourhoods .geodir-delete-neighbourhood").length) {
    $(".neighbourhoods .geodir-delete-neighbourhood").on("click", function(e) {
      var id = $(this).closest('tr').find('.gd-has-id').data('hood-id');
      if (id) {
        GeoDir_Location_Neighbourhood.deleteNeighbourhood(id, $(this).closest('tr'));
      }
    });
  }

  if ($(".cities .geodir-location-set-default").length) {
    $(".cities .geodir-location-set-default").on("click", function(e) {
      var location_id = $(this).closest('tr').find('.gd-has-id').data('location-id');
      if (location_id) {
        geodir_location_set_default(location_id, $(this), $(this).closest('tr'));
      }
    });
  }

  if ($('.geodir-merge-location-div [name="geodir_primary_city"]').length) {
    GeoDir_Location.initMergeLocation();
  }

  function geodirSelect2FormatString() {
    return {
      'language': {
        errorLoading: function() {
          // Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
          return geodir_params.i18n_searching;
        },
        inputTooLong: function(args) {
          var overChars = args.input.length - args.maximum;
          if (1 === overChars) {
            return geodir_params.i18n_input_too_long_1;
          }
          return geodir_params.i18n_input_too_long_n.replace('%item%', overChars);
        },
        inputTooShort: function(args) {
          var remainingChars = args.minimum - args.input.length;
          if (1 === remainingChars) {
            return geodir_params.i18n_input_too_short_1;
          }
          return geodir_params.i18n_input_too_short_n.replace('%item%', remainingChars);
        },
        loadingMore: function() {
          return geodir_params.i18n_load_more;
        },
        maximumSelected: function(args) {
          if (args.maximum === 1) {
            return geodir_params.i18n_selection_too_long_1;
          }
          return geodir_params.i18n_selection_too_long_n.replace('%item%', args.maximum);
        },
        noResults: function() {
          return geodir_params.i18n_no_matches;
        },
        searching: function() {
          return geodir_params.i18n_searching;
        }
      }
    };
  }

  try {
    $(document.body).on('geodir-select-init', function() {
      // Ajax region search box
      $(':input.geodir-region-search').filter(':not(.enhanced)').each(function() {
        var select2_args = {
          allowClear: $(this).data('allow_clear') ? true : false,
          placeholder: $(this).data('placeholder'),
          minimumInputLength: $(this).data('min_input_length') ? $(this).data('min_input_length') : '3',
          escapeMarkup: function(m) {
            return m;
          },
          ajax: {
            url: geodir_params.gd_ajax_url,
            dataType: 'json',
            delay: 250,
            data: function(params) {
              return {
                term: params.term,
                action: 'geodir_json_search_regions',
                security: geodir_params.search_regions_nonce,
                exclude: $(this).data('exclude'),
                include: $(this).data('include'),
                limit: $(this).data('limit')
              };
            },
            processResults: function(data) {
              var terms = [];
              if (data) {
                $.each(data, function(id, text) {
                  terms.push({
                    id: id,
                    text: text
                  });
                });
              }
              return {
                results: terms
              };
            },
            cache: true
          }
        };
        select2_args = $.extend(select2_args, geodirSelect2FormatString());
        var $select2 = $(this).select2(select2_args);
        $select2.addClass('enhanced');
        $select2.data('select2').$container.addClass('gd-select2-container');
        $select2.data('select2').$dropdown.addClass('gd-select2-container');

        if ($(this).data('sortable')) {
          var $select = $(this);
          var $list = $(this).next('.select2-container').find('ul.select2-selection__rendered');

          $list.sortable({
            placeholder: 'ui-state-highlight select2-selection__choice',
            forcePlaceholderSize: true,
            items: 'li:not(.select2-search__field)',
            tolerance: 'pointer',
            stop: function() {
              $($list.find('.select2-selection__choice').get().reverse()).each(function() {
                var id = $(this).data('data').id;
                var option = $select.find('option[value="' + id + '"]')[0];
                $select.prepend(option);
              });
            }
          });
          // Keep multiselects ordered alphabetically if they are not sortable.
        } else if ($(this).prop('multiple')) {
          $(this).on('change', function() {
            var $children = $(this).children();
            $children.sort(function(a, b) {
              var atext = a.text.toLowerCase();
              var btext = b.text.toLowerCase();

              if (atext > btext) {
                return 1;
              }
              if (atext < btext) {
                return -1;
              }
              return 0;
            });
            $(this).html($children);
          });
        }
      });
      // Ajax city search box
      $(':input.geodir-city-search').filter(':not(.enhanced)').each(function() {
        var select2_args = {
          allowClear: $(this).data('allow_clear') ? true : false,
          placeholder: $(this).data('placeholder'),
          minimumInputLength: $(this).data('min_input_length') ? $(this).data('min_input_length') : '3',
          escapeMarkup: function(m) {
            return m;
          },
          ajax: {
            url: geodir_params.gd_ajax_url,
            dataType: 'json',
            delay: 250,
            data: function(params) {
              return {
                term: params.term,
                action: 'geodir_json_search_cities',
                security: geodir_params.search_cities_nonce,
                exclude: $(this).data('exclude'),
                include: $(this).data('include'),
                limit: $(this).data('limit')
              };
            },
            processResults: function(data) {
              var terms = [];
              if (data) {
                $.each(data, function(id, text) {
                  terms.push({
                    id: id,
                    text: text
                  });
                });
              }
              return {
                results: terms
              };
            },
            cache: true
          }
        };
        select2_args = $.extend(select2_args, geodirSelect2FormatString());
        var $select2 = $(this).select2(select2_args);
        $select2.addClass('enhanced');
        $select2.data('select2').$container.addClass('gd-select2-container');
        $select2.data('select2').$dropdown.addClass('gd-select2-container');

        if ($(this).data('sortable')) {
          var $select = $(this);
          var $list = $(this).next('.select2-container').find('ul.select2-selection__rendered');

          $list.sortable({
            placeholder: 'ui-state-highlight select2-selection__choice',
            forcePlaceholderSize: true,
            items: 'li:not(.select2-search__field)',
            tolerance: 'pointer',
            stop: function() {
              $($list.find('.select2-selection__choice').get().reverse()).each(function() {
                var id = $(this).data('data').id;
                var option = $select.find('option[value="' + id + '"]')[0];
                $select.prepend(option);
              });
            }
          });
          // Keep multiselects ordered alphabetically if they are not sortable.
        } else if ($(this).prop('multiple')) {
          $(this).on('change', function() {
            var $children = $(this).children();
            $children.sort(function(a, b) {
              var atext = a.text.toLowerCase();
              var btext = b.text.toLowerCase();

              if (atext > btext) {
                return 1;
              }
              if (atext < btext) {
                return -1;
              }
              return 0;
            });
            $(this).html($children);
          });
        }
      });
    }).trigger('geodir-select-init');
    $('html').on('click', function(event) {
      if (this === event.target) {
        $(':input.geodir-region-search,:input.geodir-city-search').filter('.select2-hidden-accessible').select2('close');
      }
    });
  } catch (err) {
    window.console.log(err);
  }
  if ($('.geodirectory .geodir-locations-list').length) {
	  $('#filter-by-country, #filter-by-region, #filter-by-city').on('change', function(e){
		  geodir_location_get_filter_options(this);
	  });
  }
});

function geodir_location_switcher_nav_changed(el) {
  if (el.is(':checked')) {
    jQuery("input[name=lm_location_switcher_list_mode]").closest('tr').show();
    if (jQuery("input[name=lm_location_switcher_list_mode]:radio:checked").length == 0)
      jQuery("input[name=lm_location_switcher_list_mode]:first").attr('checked', true);
  } else {
    jQuery("input[name=lm_location_switcher_list_mode]").each(function() {
      jQuery(this).attr('checked', false);
    });
    jQuery("input[name=lm_location_switcher_list_mode]").closest('tr').hide();
  }
}

function geodir_location_show_selected_countries(ele) {
  if (jQuery(ele).val() != 'selected') {
    jQuery('select#lm_selected_countries').closest('tr').hide();
  } else {
    jQuery('select#lm_selected_countries').closest('tr').show();
  }
  var drop = jQuery('input#lm_everywhere_in_country_dropdown');
  var part = jQuery('input#lm_hide_country_part');
  if (jQuery(ele).val() == 'default') {
    jQuery(drop).closest('tr').hide();
    jQuery(drop).prop('checked', false);
    jQuery(part).closest('tr').show();
  } else {
    jQuery(drop).closest('tr').show();
    jQuery(part).closest('tr').hide();
    jQuery(part).prop('checked', false);
  }
}

function geodir_location_show_selected_regions(ele) {
  if (jQuery(ele).val() != 'selected') {
    jQuery('select#lm_selected_regions').closest('tr').hide();
  } else {
    jQuery('select#lm_selected_regions').closest('tr').show();
  }
  var drop = jQuery('input#lm_everywhere_in_region_dropdown');
  var part = jQuery('input#lm_hide_region_part');
  if (jQuery(ele).val() == 'default') {
    jQuery(drop).closest('tr').hide();
    jQuery(drop).prop('checked', false);
    jQuery(part).closest('tr').show();
  } else {
    jQuery(drop).closest('tr').show();
    jQuery(part).closest('tr').hide();
    jQuery(part).prop('checked', false);
  }
}

function geodir_location_show_selected_cities(ele) {
  if (jQuery(ele).val() != 'selected') {
    jQuery('select#lm_selected_cities').closest('tr').hide();
  } else {
    jQuery('select#lm_selected_cities').closest('tr').show();
  }
  var drop = jQuery('input#lm_everywhere_in_city_dropdown');
  if (jQuery(ele).val() == 'default') {
    jQuery(drop).closest('tr').hide();
    jQuery(drop).prop('checked', false);
  } else {
    jQuery(drop).closest('tr').show();
  }
}

function geodir_location_delete_location(id, $el) {
  
  if (!confirm(geodir_location_params.confirm_delete_location)) {
    return false;
  }

  if (!id) {
    return;
  }
  
  var data = {
    action: 'geodir_ajax_delete_location',
    id: id,
    security: jQuery('.gd-has-id', $el).data('delete-nonce')
  };
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
      if (res.data.message) {
        alert(res.data.message);
      }
      if (res.success) {
        $el.fadeOut();
      } else {
        $el.css({
          opacity: 1
        });
      }
    },
    error: function(xhr, textStatus, errorThrown) {
      console.log(errorThrown);
      $el.css({
        opacity: 1
      });
    }
  });
}

function geodir_location_set_default(id, $input, $el) {
  if (!confirm(geodir_location_params.confirm_set_default)) {
    return false;
  }
  if (!id) {
    return false;
  }

  var data = {
    action: 'geodir_ajax_set_default',
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

var GeoDir_Location = {
  init: function() {
    var $self = this;
    this.el = jQuery('.gd-settings-wrap');
    this.form = jQuery('form#mainform');
    // this.form.attr('action', 'javascript:void(0);');
    jQuery(".geodir-save-button", this.el).on("click", function(e) {
        $self.saveLocation(e);
    });
  },
  block: function() {
    jQuery('#save_location', this.el).prop('disabled', true);
    jQuery(this.el).css({
      opacity: 0.6
    });
  },
  unblock: function() {
    jQuery('#save_location', this.el).prop('disabled', false);
    jQuery(this.el).css({
      opacity: 1
    });
  },
  saveLocation: function(e) {
    e.preventDefault();
    var $self = this;
    var err = false;
    if (window.tinyMCE) {
        window.tinyMCE.triggerSave();
    }
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
      data: $self.form.serialize() + '&action=geodir_ajax_save_location',
      beforeSend: function() {},
      success: function(res, textStatus, xhr) {
        var msg_class;
        jQuery('.gd-save-location-message', $self.el).remove();

        if (res.success) {
          aui_toast('gd_lm_save_hood','success',geodir_params.txt_saved);
        } else {
          aui_toast('gd_tabs_save_order_e','error',res.data.message);
        }
        $self.unblock();
      },
      error: function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
        $self.unblock();
      }
    });
  },
  initMergeLocation: function() {
    var $self = this;
    var $div = jQuery('.geodir-merge-data');
    var $list = jQuery('.geodir-merge-location-div .wp-list-table');
    var $form = jQuery('form#mainform');
    $form.attr('action', 'javascript:void(0);');

    jQuery('.geodir-merge-value', $div).on('click', function(e) {
      $self.mergeValue(jQuery(this));
    });
    jQuery('[name="geodir_primary_city"]', $list).on('click', function(e) {
      $self.getPrimary(jQuery(this));
    });
    jQuery('[name="geodir_primary_city"]:first', $list).trigger('click');
    jQuery("#set_primary_button", $div).on("click", function(e) {
      $self.mergeLocation(jQuery(this));
    });
  },
  getPrimary: function($el) {
    if (!$el.data('id')) {
      return false;
    }
    $div = jQuery('.geodir-merge-data');
    if ($el.data('id') == $div.data('id')) {
      jQuery('.geodir-merge-data').addClass('geodir-is-primary');
    } else {
      jQuery('.geodir-merge-data').removeClass('geodir-is-primary');
    }
    $el.closest('tr').find('.column-field').each(function() {
      var field = jQuery(this).data('field');
      var value = jQuery(this).data('value');
      jQuery('#' + field, $div).val(value);
    });
  },
  mergeValue: function($el) {
    var $div, field;
    $div = $el.closest('.geodir-merge-data');
    field = $el.data('field');
    jQuery('#' + field, $div).val($el.data('value'));
  },
  mergeLocation: function($el) {
    var $form = $el.closest('form#mainform');
    var $div = $el.closest('.geodir-merge-location-div');
    $div.css({
      opacity: 0.6
    });
	$el.prop('disabled', true);
    jQuery.ajax({
      url: geodir_params.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: $form.serialize() + '&action=geodir_ajax_merge_location',
      beforeSend: function() {},
      success: function(res, textStatus, xhr) {
        var msg_class;
        jQuery('.gd-merge-location-message', $div).remove();

        if (res.success) {
          msg_class = 'updated';
        } else {
          msg_class = 'error';
        }
        if (res && res.data && res.data.message) {
			message = '<div class="gd-merge-location-message ' + msg_class + '"><p>' + res.data.message + '</p></div>';
			if (msg_class == 'error') {
				jQuery('.cities.wp-list-table', $div).before(message);
				$el.prop('disabled', false);
			} else {
				jQuery('.geodir-merge-location-div').html(message);
			}
        }
        $div.css({
          opacity: 1
        });
        jQuery('html, body').animate({
          scrollTop: $form.offset().top
        }, 100);
      },
      error: function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
        $div.css({
          opacity: 1
        });
		$el.prop('disabled', false);
      }
    });
  }
}

var GeoDir_Location_Neighbourhood = {
  init: function() {
    var $self = this;
    this.el = jQuery('.gd-settings-wrap');
    this.form = jQuery('form#mainform');
    this.form.attr('action', 'javascript:void(0);');
    jQuery(".geodir-save-button", this.el).on("click", function(e) {
      $self.saveNeighbourhood(e);
    });
  },
  block: function() {
    jQuery('#save_neighbourhood_button', this.el).prop('disabled', true);
    jQuery(this.el).css({
      opacity: 0.6
    });
  },
  unblock: function() {
    jQuery('#save_neighbourhood_button', this.el).prop('disabled', false);
    jQuery(this.el).css({
      opacity: 1
    });
  },
  saveNeighbourhood: function(e) {
    e.preventDefault();
    var $self = this;
    var err = false;
    if (window.tinyMCE) {
        window.tinyMCE.triggerSave();
    }
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
      data: $self.form.serialize() + '&action=geodir_ajax_save_neighbourhood',
      beforeSend: function() {},
      success: function(res, textStatus, xhr) {
        var msg_class;
        jQuery('.gd-save-neighbourhood-message', $self.el).remove();

        if (res.success) {
          msg_class = 'updated';
          aui_toast('gd_lm_save_hood','success',geodir_params.txt_saved);

          if (!(parseInt(jQuery('[name="neighbourhood_id"]').val()) > 0) && res.data && res.data.id) {
            jQuery('[name="neighbourhood_id"]').val(res.data.id);
          }
        } else {
          aui_toast('gd_tabs_save_order_e','error',res.data.message);
        }
        $self.unblock();
      },
      error: function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
        $self.unblock();
      }
    });
  },
  deleteNeighbourhood: function(id, $el) {
    if (!confirm(geodir_location_params.confirm_delete_neighbourhood)) {
      return false;
    }

    if (!id) {
      return;
    }

    var data = {
      action: 'geodir_ajax_delete_neighbourhood',
      id: id,
      security: jQuery('.gd-has-id', $el).data('delete-nonce')
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
        if (res.data.message) {
          alert(res.data.message);
        }
        if (res.success) {
          $el.fadeOut();
        } else {
          $el.css({
            opacity: 1
          });
        }
      },
      error: function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
        $el.css({
          opacity: 1
        });
      }
    });
  }
}

var GeoDir_Location_SEO = {
  init: function() {
    var $self = this;
    this.el = jQuery('#geodir-save-seo-div');
    this.form = jQuery('form#mainform');
    this.form.attr('action', 'javascript:void(0);');
    jQuery("#save_seo_button", this.el).on("click", function(e) {
      $self.saveSEO(e);
    });
  },
  block: function() {
    jQuery('#save_seo_button', this.el).prop('disabled', true);
    jQuery(this.el).css({
      opacity: 0.6
    });
  },
  unblock: function() {
    jQuery('#save_seo_button', this.el).prop('disabled', false);
    jQuery(this.el).css({
      opacity: 1
    });
  },
  saveSEO: function(e) {
    e.preventDefault();
    var $self = this;
    var err = false;
    if (window.tinyMCE) {
        window.tinyMCE.triggerSave();
    }
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
      data: $self.form.serialize() + '&action=geodir_ajax_save_seo',
      beforeSend: function() {},
      success: function(res, textStatus, xhr) {
        var msg_class;
        jQuery('.gd-save-seo-message', $self.el).remove();

        if (res.success) {
          msg_class = 'updated';
        } else {
          msg_class = 'error';
        }
        if (res && res.data && res.data.message) {
          jQuery('.form-table', $self.el).before('<div class="gd-save-seo-message ' + msg_class + '"><p>' + res.data.message + '</p></div>');
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
  }
}

function geodir_add_location_validation(fields) {
  var error = false;
  if (fields.val() == '') {
    jQuery(fields).closest('.gtd-formfeild').find('.gd-location_message_error').show();
    error = true;
  } else {
    jQuery(fields).closest('.gtd-formfeild').find('.gd-location_message_error').hide();
  }
  if (error) {
    return false;
  } else {
    return true;
  }
}
jQuery(document).ready(function() {
  jQuery('#geodir_location_save').on("click", function() {
    var is_validate = true;
    jQuery(this).closest('form').find('.required:visible').each(function() {
      var fields = jQuery(this).find('input, select');
      if (!geodir_add_location_validation(fields))
        is_validate = false;
    });
    if (!is_validate) {
      return false;
    }
  });
  jQuery('.geodir_add_location_form').find(".required:visible").find('input').on("blur", function() {
    geodir_add_location_validation(jQuery(this));
  });
  jQuery('.geodir_add_location_form').find(".required:visible").find('select').on("change",function() {
    geodir_add_location_validation(jQuery(this));
  });
  jQuery('.button-primary').on("click", function() {
    var error = false;
    var characterReg = /^\s*[a-zA-Z0-9,\s]+\s*$/;
    var listing_prefix = jQuery('#geodir_listing_prefix').val();
    var location_prefix = jQuery('#geodir_location_prefix').val();
    var listingurl_separator = jQuery('#geodir_listingurl_separator').val();
    var detailurl_separator = jQuery('#geodir_detailurl_separator').val();
    if (listing_prefix == '') {
      alert(geodir_location_params.LISTING_URL_PREFIX);
      jQuery('#geodir_listing_prefix').focus();
      error = true;
    }
    if (!characterReg.test(listing_prefix) && listing_prefix != '') {
      jQuery('#geodir_listing_prefix').focus();
      alert(geodir_location_params.LISTING_URL_PREFIX_INVALID_CHAR);
      error = true;
    }
    if (location_prefix == '') {
      alert(geodir_location_params.LOCATION_URL_PREFIX);
      jQuery('#geodir_location_prefix').focus();
      error = true;
    }
    if (!characterReg.test(location_prefix) && location_prefix != '') {
      alert(geodir_location_params.LISTING_URL_PREFIX_INVALID_CHAR);
      jQuery('#geodir_location_prefix').focus();
      error = true;
    }
    if (listingurl_separator == '') {
      alert(geodir_location_params.LOCATION_CAT_URL_SEP);
      jQuery('#geodir_listingurl_separator').focus();
      error = true;
    }
    if (!characterReg.test(listingurl_separator) && listingurl_separator != '') {
      alert(geodir_location_params.LOCATION_CAT_URL_SEP_INVALID_CHAR);
      jQuery('#geodir_listingurl_separator').focus();
      error = true;
    }
    if (detailurl_separator == '') {
      alert(geodir_location_params.LISTING_DETAIL_URL_SEP);
      jQuery('#geodir_detailurl_separator').focus();
      error = true;
    }
    if (!characterReg.test(detailurl_separator) && detailurl_separator != '') {
      alert(geodir_location_params.LISTING_DETAIL_URL_SEP_INVALID_CHAR);
      jQuery('#geodir_detailurl_separator').focus();
      error = true;
    }
    if (error == true) {
      return false;
    } else {
      return true;
    }
  });

  jQuery('#gd-filter-by-country, #gd-filter-by-region').on('change', function(e) {
        var $    = jQuery,
        val      = $(this).val(),
        filtertype= $(this).prop('name'),
        form     = $(this).closest('form'),
        country  = $('select[name="_gd_country"]', form),
        region   = $('select[name="_gd_region"]', form),
        city     = $('select[name="_gd_city"]', form),
        countryd = jQuery('option:first-child', country),
        regiond  = jQuery('option:first-child', region);
		cityd    = jQuery('option:first-child', city);
        
        switch ( filtertype ) {
            case '_gd_country':

                city.html(cityd).attr('disabled', 'disabled');
                if ( ! val ) {
                    region.html(regiond).attr('disabled', 'disabled');
                    return false;
                }
                data = {
                    cn: val
                }
                $(region).css({
                    opacity: 0.6
                  });

                break;
            case '_gd_region':

                if ( ! val ) {
                    city.html(cityd).attr('disabled', 'disabled');
                    return false;
                }
                var cnv = country.val();
                if (!cnv) {
                    region.html(regiond).attr('disabled', 'disabled');
                    return false;
                }
                data = {
                    cn: cnv,
                    rg: val,
                }
                $(city).css({
                    opacity: 0.6
                  });

                break;
            default:
                return false;
                break;
        }
    
        data.action = 'geodir_location_ajax';
        data.gd_loc_ajax_action = 'get_location_options';

        $.ajax({
            type: "POST",
            data: data,
            dataType: "json",
            url: ajaxurl,
            success: function(res) {
                if (typeof res == 'object' && typeof res.options != 'undefined') {
                    
                    switch ( filtertype ) {
                        case '_gd_country':
                            region.html(regiond).append(res.options).removeAttr('disabled');
                            $(region).css({
                                opacity: 1
                              });
                            break;
                        case '_gd_region':
                            city.html(cityd).append(res.options).removeAttr('disabled');
                            $(city).css({
                                opacity: 1
                              });
                            break;
                    }

                }
            }
        });
    });

});
function geodir_location_get_filter_options(el) {
    var o, data, v = jQuery(el).val(),
		type = jQuery(el).prop('name'),
        f = jQuery(el).closest('form'),
        scn = jQuery('select[name="country"]', f),
        srg = jQuery('select[name="region"]', f),
        sct = jQuery('select[name="city"]', f),
		shood = jQuery('select[name="neighbourhood"]', f),
        org = jQuery('option:first-child', srg),
        oct = jQuery('option:first-child', sct);
		ohood = jQuery('option:first-child', sct);
    switch (type) {
        case 'country':
            if (!srg.length) {
				return false;
			}
			sct.html(oct).attr('disabled', 'disabled');
            if (!v) {
                srg.html(org).attr('disabled', 'disabled');
                return false;
            }
            data = {
                type: 'region',
				country: v
            }
            break;
        case 'region':
            if (!sct.length) {
				return false;
			}
			if (!v) {
                sct.html(oct).attr('disabled', 'disabled');
                return false;
            }
            var country = scn.val();
            if (!country) {
                srg.html(org).attr('disabled', 'disabled');
                return false;
            }
            data = {
                type: 'city',
				country: country,
                region: v,
            }
            break;
		case 'city':
            if (!shood.length) {
				return false;
			}
			if (!v) {
                shood.html(ohood).attr('disabled', 'disabled');
                return false;
            }
            var country = scn.val();
			var region = srg.val();
            if (!country) {
                srg.html(oct).attr('disabled', 'disabled');
                return false;
            }
			if (!region) {
                sct.html(ohood).attr('disabled', 'disabled');
                return false;
            }
            data = {
                type: 'neighbourhood',
				country: country,
                region: region,
				city: v,
            }
            break;
        default:
            return false;
            break;
    }

    data.action = 'geodir_location_filter_options';

    jQuery.ajax({
      url: geodir_params.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: data,
      beforeSend: function() {},
      success: function(res, textStatus, xhr) {
        if (res && res.success) {
			switch (type) {
				case 'country':
					srg.html(org).append(res.data.options).removeAttr('disabled');
					break;
				case 'region':
					sct.html(oct).append(res.data.options).removeAttr('disabled');
					break;
				case 'city':
					shood.html(ohood).append(res.data.options).removeAttr('disabled');
					break;
			}
        }
      },
      error: function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
      }
    });
}