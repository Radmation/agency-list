jQuery(function($){
	$('.gd-handlediv').on('click', function(e) {
        if ($(this).hasClass('gd-expired')) {
			$(this).removeClass('gd-expired');
			$('.' + $(this).data('handle')).slideDown(200);
		} else {
			$(this).addClass('gd-expired');
			$('.' + $(this).data('handle')).slideUp(200);
		}
    });
	if ($('form#mainform .gd-pkg-lifetime-wrap').length) {
		GeoDir_Pricing_Package.init($('form#mainform'));
	}
	if ($(".packages .geodir-package-set-default").length) {
		$(".packages .geodir-package-set-default").closest('form#mainform').addClass('geodir-package-form');
		$(".packages .geodir-package-set-default").on('click', function(e) {
			var package_id = $(this).closest('tr').find('.gd-has-id').data('package-id');
			if (package_id) {
				GeoDir_Pricing_Package.setDefault(package_id, $(this));
			}
		});
	}

	if ($(".packages .geodir-delete-package").length) {
		$(".packages .geodir-delete-package").on('click', function(e) {
			var package_id = $(this).closest('tr').find('.gd-has-id').data('package-id');
			if (package_id) {
				GeoDir_Pricing_Package.deletePackage(package_id, $(this));
			}
		});
	}

	if ($(".packages .geodir-sync-package").length) {
		$(".packages .geodir-sync-package").on('click', function(e) {
			var package_id = $(this).closest('tr').find('.gd-has-id').data('package-id');
			if (package_id) {
				GeoDir_Pricing_Package.syncPackage(package_id, $(this));
			}
		});
	}

	if ($("#geodir_create_post_invoice").length) {
		$("#geodir_create_post_invoice").on('click', function(e) {
			var post_id = parseInt($(this).data('id'));
			if (post_id) {
				geodir_pricing_create_invoice(post_id, $(this));
			}
		});
	}

	if (typeof WPInv_Admin != 'undefined' && WPInv_Admin.hasInvoicing) {
        $('.wpi-type-package .submitdelete').on('click', function(e) {
            if ( $(this).closest('.wpi-type-package').hasClass('wpi-inuse-pkg')) {
                alert(WPInv_Admin.errDeletePackage);
                return false;
            } else if ( $(this).closest('.wpi-type-package').hasClass('wpi-delete-pkg')) {
                return true;
            } else {
                alert(WPInv_Admin.deletePackage);
                return false;
            }
        });
        if ($('.post-type-wpi_item #_wpi_current_type').val() == 'package') {
            $('.post-type-wpi_item #submitpost #delete-action').remove();
        }
	}

	if ($('.geodir-link-invoice').length && $('[name="geodir_link_invoice"]').length) {
		$('.geodir-link-invoice').on('click', function(e) {
			geodir_pricing_link_invoice(this, e);
		});
	}
});
var GeoDir_Pricing_Package = {
	init: function($form) {
		this.$form = $form;
		var $self = this;

		jQuery('[name="package_recurring"]', $form).on('click', function(e) {
            $self.onChangeRecurring(jQuery(this));
        });
		$self.onChangeRecurring(jQuery('[name="package_recurring"]'), $form);

		jQuery('[name="package_trial"]', $form).on('click', function(e) {
            $self.onChangeTrial(jQuery(this));
        });

		jQuery('[name="package_use_desc_limit"]', $form).on('click', function(e) {
            $self.onChangeDescLimit(jQuery(this));
        });
		$self.onChangeDescLimit(jQuery('[name="package_use_desc_limit"]'), $form);
	},
	onChangeRecurring: function($el) {
        var $self = this;

		if ($el.is(':checked')) {
            $self.showFields(['package_recurring_limit', 'package_trial']);
        } else {
            $self.hideFields(['package_recurring_limit', 'package_trial']);
        }
		$self.onChangeTrial(jQuery('[name="package_trial"]', $self.$form));
    },
	onChangeTrial: function($el) {
        var $self = this;

        if ($el.is(':checked') && jQuery('[name="package_recurring"]', $self.$form).is(':checked')) {
            $self.showFields(['package_trial_amount', 'package_trial_interval', 'package_trial_unit']);
        } else {
            $self.hideFields(['package_trial_amount', 'package_trial_interval', 'package_trial_unit']);
        }
    },
	onChangeDescLimit: function($el) {
        var $self = this;

        if ($el.is(':checked')) {
            $self.showFields(['package_desc_limit']);
        } else {
            $self.hideFields(['package_desc_limit']);
        }
    },
	showFields: function(fields) {
        var $self = this;
        jQuery.each(fields, function(i, field) {
            name = field;
            jQuery('[name="' + name + '"]', $self.$form).closest('tr').removeClass('geodir-pkg-none');
        });
    },
    hideFields: function(fields) {
        var $self = this;
        jQuery.each(fields, function(i, field) {
            name = field;
            jQuery('[name="' + name + '"]', $self.$form).closest('tr').addClass('geodir-pkg-none');
        });
    },
	setDefault: function(id, $input) {
		var $el = $input.closest('.geodir-package-row');
		if (!id) {
			return false;
		}
		if (parseInt($el.data('default')) == 1) {
			return false;
		}
		if (!confirm(geodir_pricing_admin_params.confirm_set_default)) {
			return false;
		}
		post_type = $el.data('post-type');
		var data = {
			action: 'geodir_pricing_set_default',
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
					jQuery('.geodir-package-row').each(function() {
						if (jQuery(this).data('post-type') == post_type) {
							jQuery('[name="' + $input.attr('name') + '"]', jQuery(this)).prop('checked', false);
							jQuery(this).attr('data-default', '0');
						}
					});
					$input.prop('checked', true);
					$input.closest('.geodir-package-row').attr('data-default', '1');
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
	},
	deletePackage: function(id, $el) {
		var $row = $el.closest('.geodir-package-row');
		if (!id) {
			return false;
		}
		if (parseInt($row.data('default')) == 1) {
			return false;
		}
		if (!confirm(geodir_pricing_admin_params.confirm_delete_package)) {
			return false;
		}

		$el.text(geodir_pricing_admin_params.text_deleting);

		var data = {
			action: 'geodir_pricing_delete_package',
			id: id,
			security: jQuery('.gd-has-id', $row).data('delete-nonce')
		};
		jQuery.ajax({
			url: geodir_params.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: data,
			beforeSend: function() {
				$row.css({
					opacity: 0.6
				});
			},
			success: function(res, textStatus, xhr) {
				if (res.success) {
					$el.text(geodir_pricing_admin_params.text_deleted);
				}
				if (res.data.message) {
					alert(res.data.message);
				}
				if (res.success) {
					$row.fadeOut();
				} else {
					$row.css({
						opacity: 1
					});
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
				if (res.success) {
					$el.text(geodir_pricing_admin_params.text_delete);
				}
				$row.css({
					opacity: 1
				});
			}
		});
	},
	syncPackage: function(id, $el) {
		var $row = $el.closest('.geodir-package-row');
		if (!id) {
			return false;
		}

		$row.find('.fa-sync').addClass('fa-spin');

		var data = {
			action: 'geodir_pricing_sync_package',
			id: id,
			security: $el.data('sync-nonce')
		};
		jQuery.ajax({
			url: geodir_params.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: data,
			beforeSend: function() {
			},
			success: function(res, textStatus, xhr) {
				if (res.data.message) {
					alert(res.data.message);
				}
				$row.find('.fa-sync').removeClass('fa-spin');
				// Reload page
				if ( true === res.data.reload || $row.find('[data-reload="1"]').length ) {
					window.location.reload();
					return;
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
				$row.find('.fa-sync').removeClass('fa-spin');
			}
		});
	}
}

function geodir_pricing_create_invoice( post_id, $el ) {
	if ( ! confirm( geodir_pricing_admin_params.confirm_create_invoice ) ) {
		return false;
	}

	var data = {
		action: 'geodir_pricing_create_invoice',
		post_id: post_id,
		security: $el.data('nonce-create-invoice')
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
			if ( res && typeof res == 'object') {
				if ( res.success === true ) {
					if ( res.link ) {
						$el.after( res.link );
					}
					$el.remove();
				} else if ( res.msg ) {
					alert( res.msg );
				}
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

function geodir_pricing_link_invoice( el, e ) {
	var $input = jQuery('[name="geodir_link_invoice"]'), invoice_id = $input.val(), post_id = parseInt($input.data('post-id'));

	if (!invoice_id) {
		$input.trigger('focus');
		return false;
	}

	if ( ! confirm( geodir_pricing_admin_params.confirm_link_invoice ) ) {
		return false;
	}

	var data = {
		action: 'geodir_pricing_link_invoice',
		post_id: post_id,
		invoice_id: invoice_id,
		security: $input.data('nonce')
	};

	jQuery.ajax({
		url: geodir_params.ajax_url,
		type: 'POST',
		data: data,
		dataType: 'json',
		beforeSend: function(xhr, obj) {
			jQuery(el).prop("disabled", true);
			jQuery(el).find('.geodir-btn-icon').hide();
			jQuery(el).find('.fa-spin').remove();
			jQuery(el).prepend('<i class="fas fa-spinner fa-spin"></i>');
		}
	})
	.done(function(data, textStatus, jqXHR) {
		if (typeof data == 'object') {
			if (data.data.message) {
				alert(data.data.message);
			}

			if (true === data.data.reload) {
				$input.val('');
				window.location.reload();
				return;
			}
		}
	})
	.always(function(data, textStatus, jqXHR) {
		jQuery(el).prop("disabled", false);
		jQuery(el).find('.geodir-btn-icon').show();
		jQuery(el).find('.fa-spin').remove();
	});
}