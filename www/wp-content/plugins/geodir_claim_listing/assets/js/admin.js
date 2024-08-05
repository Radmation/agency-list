jQuery(function($){
	if ($(".claims .geodir-approve-claim").length) {
		$(".claims .geodir-approve-claim").on('click', function(e) {
			var id = $(this).closest('tr').data('id');
			if (id) {
				GeoDir_Claim_Request.approveClaim(id, $(this));
			}
		});
	}
	if ($(".claims .geodir-reject-claim").length) {
		$(".claims .geodir-reject-claim").on('click', function(e) {
			var id = $(this).closest('tr').data('id');
			if (id) {
				GeoDir_Claim_Request.rejectClaim(id, $(this));
			}
		});
	}
	if ($(".claims .geodir-undo-claim").length) {
		$(".claims .geodir-undo-claim").on('click', function(e) {
			var id = $(this).closest('tr').data('id');
			if (id) {
				GeoDir_Claim_Request.undoClaim(id, $(this));
			}
		});
	}
	if ($(".claims .geodir-delete-claim").length) {
		$(".claims .geodir-delete-claim").on('click', function(e) {
			var id = $(this).closest('tr').data('id');
			if (id) {
				GeoDir_Claim_Request.deleteClaim(id, $(this));
			}
		});
	}
	if ($(".claims .geodir-view-claim").length) {
		$(".claims .geodir-view-claim").on('click', function(e) {
			var id = $(this).closest('tr').data('id');
			if (id) {
				GeoDir_Claim_Request.viewClaim(id, $(this));
			}
		});
	}
	var geodir_claim_nudge_sending = false;
	$('.geodir-claim-nudge-action').on('click', function() {
		var post_id = parseInt($(this).data('id')),
			nonce = $(this).data('nonce'),
			sentText = $(this).data('sent');

		if (geodir_claim_nudge_sending) {
			return false;
		}

		if (post_id && nonce) {
			$btn = jQuery(this);
			$wrap = jQuery(this).closest('.misc-pub-claim-nudge');
			var data = {
				'action': 'geodir_claim_nudge_send_email',
				'security': nonce,
				'post_id': post_id
			};

			jQuery.ajax({
				url: ajaxurl,
				type: 'POST',
				data: data,
				dataType: 'json',
				beforeSend: function(xhr, obj) {
					geodir_claim_nudge_sending = true;
					$btn.prop("disabled", true);
					$btn.addClass("disabled");
					jQuery('.geodir-sending-wait', $wrap).show();
				}
			})
			.done(function(data, textStatus, jqXHR) {
				$btn.prop("disabled", false);
				$btn.removeClass("disabled");
				$('.geodir-sending-wait', $wrap).hide();
				if (typeof data == 'object') {
					if (data.success) {
						$btn.text(sentText);
						if (data.data.sentOn) {
							jQuery('.geodir-claim-sent-on', $wrap).show();
							jQuery('.geodir-claim-sent-on strong', $wrap).text(data.data.sentOn);
						}
					}
					if (data.data.message) {
						alert(data.data.message);
					}
				}
				geodir_claim_nudge_sending = false;
			})
			.always(function(data, textStatus, jqXHR) {
				$btn.prop("disabled", false);
				$btn.removeClass("disabled");
				$('.geodir-sending-wait', $wrap).hide();
				geodir_claim_nudge_sending = false;
			});
		}
	});
});
var GeoDir_Claim_Request = {
	init: function($form) {
		this.$form = $form;
		var $self = this;

	},
	approveClaim: function(id, $el) {
		var $row = $el.closest('.geodir-claim-row');
		if (!id) {
			return false;
		}
		if (!confirm(geodir_claim_admin_params.confirm_approve_claim)) {
			return false;
		}

		$el.find('span').text(geodir_claim_admin_params.text_approving);

		var data = {
			action: 'geodir_claim_approve_request',
			id: id,
			security: $row.data('claim-nonce')
		};
		jQuery.ajax({
			url: geodir_params.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: data,
			beforeSend: function() {
			},
			success: function(res, textStatus, xhr) {
				if (typeof res == 'object' && res.success) {
					$el.find('span').text(geodir_claim_admin_params.text_approved);
				} else {
					$el.find('span').text(geodir_claim_admin_params.text_approve);
				}

				if (typeof res == 'object') {
					if (res.data.message) {
						alert(res.data.message);
					}
					// Reload page
					if ( true === res.data.reload ) {
						window.location.reload();
						return;
					}
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
				$el.find('span').text(geodir_claim_admin_params.text_approve);
			}
		});
	},
	rejectClaim: function(id, $el) {
		var $row = $el.closest('.geodir-claim-row');
		if (!id) {
			return false;
		}
		if (!confirm(geodir_claim_admin_params.confirm_reject_claim)) {
			return false;
		}

		$el.find('span').text(geodir_claim_admin_params.text_rejecting);

		var data = {
			action: 'geodir_claim_reject_request',
			id: id,
			security: $row.data('claim-nonce')
		};
		jQuery.ajax({
			url: geodir_params.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: data,
			beforeSend: function() {
			},
			success: function(res, textStatus, xhr) {
				if (typeof res == 'object' && res.success) {
					$el.find('span').text(geodir_claim_admin_params.text_rejected);
				} else {
					$el.find('span').text(geodir_claim_admin_params.text_reject);
				}

				if (typeof res == 'object') {
					if (res.data.message) {
						alert(res.data.message);
					}
					// Reload page
					if ( true === res.data.reload ) {
						window.location.reload();
						return;
					}
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
				$el.find('span').text(geodir_claim_admin_params.text_reject);
			}
		});
	},
	undoClaim: function(id, $el) {
		var $row = $el.closest('.geodir-claim-row');
		if (!id) {
			return false;
		}
		if (!confirm(geodir_claim_admin_params.confirm_undo_claim)) {
			return false;
		}

		$el.find('span').text(geodir_claim_admin_params.text_undoing);

		var data = {
			action: 'geodir_claim_undo_request',
			id: id,
			security: $row.data('claim-nonce')
		};
		jQuery.ajax({
			url: geodir_params.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: data,
			beforeSend: function() {
			},
			success: function(res, textStatus, xhr) {
				$el.find('span').text(geodir_claim_admin_params.text_undo);

				if (typeof res == 'object') {
					if (res.data.message) {
						alert(res.data.message);
					}
					// Reload page
					if ( true === res.data.reload ) {
						window.location.reload();
						return;
					}
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
				$el.find('span').text(geodir_claim_admin_params.text_undo);
			}
		});
	},
	deleteClaim: function(id, $el) {
		var $row = $el.closest('.geodir-claim-row');
		if (!id) {
			return false;
		}
		if (!confirm(geodir_claim_admin_params.confirm_delete_claim)) {
			return false;
		}

		$el.find('span').text(geodir_claim_admin_params.text_deleting);

		var data = {
			action: 'geodir_claim_delete_request',
			id: id,
			security: $row.data('claim-nonce')
		};
		jQuery.ajax({
			url: geodir_params.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: data,
			beforeSend: function() {
			},
			success: function(res, textStatus, xhr) {
				if (typeof res == 'object' && res.success) {
					$el.find('span').text(geodir_claim_admin_params.text_deleted);
				} else {
					$el.find('span').text(geodir_claim_admin_params.text_delete);
				}


				if (typeof res == 'object') {
					if (res.data.message) {
						alert(res.data.message);
					}
					if (res.success) {
						$row.css('background','red').fadeOut('slow');
					}
					// Reload page
					if ( true === res.data.reload ) {
						window.location.reload();
						return;
					}
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
				$el.find('span').text(geodir_claim_admin_params.text_delete);
			}
		});
	},
	viewClaim: function(id, $el) {
		var $row = $el.closest('.geodir-claim-row');
		if (!id) {
			return false;
		}
		// if (!confirm(geodir_claim_admin_params.confirm_delete_claim)) {
		// 	return false;
		// }
        //
		// $el.find('span').text(geodir_claim_admin_params.text_deleting);

		var data = {
			action: 'geodir_claim_view_request',
			id: id,
			security: $row.data('claim-nonce')
		};
		jQuery.ajax({
			url: geodir_params.ajax_url,
			type: 'POST',
			dataType: 'html',
			data: data,
			beforeSend: function() {
			},
			success: function(res, textStatus, xhr) {

				$lightbox = lity("<div class='lity-show'>"+res+"</div>");

				// if (typeof res == 'object' && res.success) {
				// 	$el.find('span').text(geodir_claim_admin_params.text_deleted);
				// } else {
				// 	$el.find('span').text(geodir_claim_admin_params.text_delete);
				// }
                //
				// if (typeof res == 'object') {
				// 	if (res.data.message) {
				// 		alert(res.data.message);
				// 	}
				// 	if (res.success) {
				// 		$row.remove('slow');
				// 	}
				// 	// Reload page
				// 	if ( true === res.data.reload ) {
				// 		window.location.reload();
				// 		return;
				// 	}
				// }
			},
			error: function(xhr, textStatus, errorThrown) {
				console.log(errorThrown);
			}
		});
	}
};