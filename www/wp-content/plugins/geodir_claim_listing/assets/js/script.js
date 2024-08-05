jQuery(function($) {
	geodir_claim_params.loader = null;
	geodir_claim_params.addPopup = null;
	$(document).on('submit', 'form.geodir-post-claim-form', function(event) {
		event.preventDefault();
		var $form = $(this), $button = $form.find('.geodir-post-claim-button'), $fields = $form.find('.geodir-claim-form-fields');

		// recaptcha check
			if($form.find('.g-recaptcha-response').length && $form.find('.g-recaptcha-response').val()==''){
			 return;// do nothing if the captch val is empty
			}

		var data = $form.serialize();

		jQuery.ajax({
			url: geodir_params.ajax_url,
			type: 'POST',
			data: data,
			dataType: 'json',
			beforeSend: function() {
				$button.prop('disabled', true).html(geodir_claim_params.text_sending);
				$fields.css({
					opacity: 0.6
				});
			},
			success: function(res, textStatus, xhr) {
				$button.prop('disabled', false).html(geodir_claim_params.text_send);
				$fields.css({
					opacity: 1
				});
				if (typeof res == 'object') {
					if ( res.data.message ) {
						if (geodir_claim_params.aui) {
							$fields.html(res.data.message);
						} else {
							if (res.success) {
								type = 'success';
								message = '<i class="fas fa-check-circle" aria-hidden="true"></i>';
							} else {
								type = 'error';
								message = '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i>';
							}
							message += ' ' + res.data.message;
							$fields.html('<div class="geodir-claim-message geodir-claim-msg-' + type + '">' + message + '</div>');
						}
					}
				} else {
					$fields.html(res);
				}
				$form.find('.geodir-claim-form-footer').remove();
			},
			error: function(res, textStatus, errorThrown) {
				console.log(errorThrown);
				$button.prop('disabled', false).html(geodir_claim_params.text_send);
				$fields.css({
					opacity: 1
				});
			}
		});
		event.preventDefault();
		return false;
	});
});

function gd_claim_ajax_lightbox($action, $nonce, $post_id, $extra) {
	if (geodir_claim_params.aui) {
		gd_claim_ajax_lightbox_aui($action, $nonce, $post_id, $extra);
		return;
	}
    if ($action) {
        if (!$nonce || $nonce == '') {
            $nonce = geodir_params.basic_nonce;
        }
        $content = '<div class="geodir-claim-lity-content"><i class="fas fa-sync fa-spin fa-fw"></i></div>';
        $lightbox = '';

        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            data: {
                action: $action,
                security: $nonce,
                p: $post_id,
                extra: $extra
            },
            beforeSend: function() {
                $lightbox = lity($content);
            },
            success: function(content) {
                jQuery('.geodir-claim-lity-content').addClass('lity-show').html(content);
            }
        });
    }
}

function gd_claim_ajax_lightbox_aui($action, $nonce, $post_id, $extra) {
    if ($action) {
        if ( ! $nonce || $nonce == '') {
            $nonce = geodir_params.basic_nonce;
        }

		/* Close any instance of the popup */
		if ( geodir_claim_params.addPopup ) {
			geodir_claim_params.addPopup.remove();
		}

		/* Show loading screen */
		geodir_claim_params.loader = aui_modal();

        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            data: {
                action: $action,
                security: $nonce,
                p: $post_id,
                extra: $extra
            },
            success: function(content) {
                 geodir_claim_params.addPopup = aui_modal('',content,'','','','');
            }
        });
    }
}