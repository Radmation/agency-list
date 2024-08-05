// JavaScript Document
jQuery(document).ready(function() {
	// selection checkboxes
	jQuery("input[name='checkedall']").on("click", function() {
		if (jQuery(this).is(':checked')) {
			jQuery("input[type='checkbox']").each(function() {
				jQuery(this).prop('checked', true);
			});
		} else {
			jQuery("input[type='checkbox']").prop('checked', false);
		}
	});
	
	jQuery(document).on("click", ".three-tab li, .post-action span", function() {
		var ajax_actions = jQuery(this).attr('action');

		if (ajax_actions == 'ratingshowhide') {
			var comment_images_div = jQuery(this).closest('li').find('.edit-form-comment-images');
			var comment_rating_div = jQuery(this).closest('li').find('.edit-form-comment-rating');
			if (comment_rating_div.css('display') == 'none') {
				jQuery(this).find('a').html(geodir_reviewrating_all_js_msg.geodir_reviewrating_hide_ratings);
				comment_rating_div.slideDown('slow');
				jQuery(this).closest('li').find("[action='commentimages']").find('a').html('Show Images');
				comment_images_div.slideUp();
			} else {
				jQuery(this).find('a').html(geodir_reviewrating_all_js_msg.geodir_reviewrating_show_ratings);
				comment_rating_div.slideUp('slow');
			}
			return false;
		}
		
		if (ajax_actions == 'commentimages') {
			var comment_images_div = jQuery(this).closest('li').find('.edit-form-comment-images');
			var comment_rating_div = jQuery(this).closest('li').find('.edit-form-comment-rating');
			if (comment_images_div.css('display') == 'none') {
				jQuery(this).find('a').html(geodir_reviewrating_all_js_msg.geodir_reviewrating_hide_images);
				comment_images_div.slideDown('slow');
				jQuery(this).closest('li').find("[action='ratingshowhide']").find('a').html('Show MultiRatings');
				comment_rating_div.slideUp('slow');
			} else {
				jQuery(this).find('a').html(geodir_reviewrating_all_js_msg.geodir_reviewrating_show_images);
				comment_images_div.slideUp('slow');
			}
			return false;
		}
		
		var chkvalues = '';
		if (jQuery(this).attr('data-comment-id')) {
			chkvalues = jQuery(this).attr('data-comment-id');
		} else {
			jQuery("input[name='chk-action[]']:checked").each(function() {
				chkvalues += ',' + jQuery(this).val();
			});
		}
		
		var geodir_comment_search = jQuery('input[name="geodir_comment_search"]').val();
		var geodir_comment_posttype = jQuery('select[name="geodir_comment_posttype"]').val();
		var geodir_comment_sort = jQuery('select[name="geodir_comment_sort"]').val();
		var paged = jQuery('input[name="geodir_review_paged"]').val();
		var show_post = jQuery('input[name="geodir_review_show_post"]').val();
		var subtab = jQuery('input[name="section"]').val();
		var nonce = jQuery('input[name="geodir_review_action_nonce_field"]').val();
		
		jQuery.ajax({
			type: "POST",
			url: geodir_reviewrating_all_js_msg.geodir_reviewrating_admin_ajax_url,
			data: {
				ajax_action: 'comment_actions',
				comment_action: ajax_actions,
				comment_ids: chkvalues,
				subtab: subtab,
				geodir_comment_search: geodir_comment_search,
				geodir_comment_posttype: geodir_comment_posttype,
				geodir_comment_sort: geodir_comment_sort,
				paged: paged,
				show_post: show_post,
				_wpnonce: nonce
			}
		}).done(function(data) {
            location.reload();
		});
	});
	
	// review rating delete images //
	jQuery(".review_rating_thumb_remove").on("click", function() {
		var confirmbox = confirm(geodir_reviewrating_all_js_msg.geodir_reviewrating_delete_image_confirmation);
		
		if (confirmbox) {
			var this_var = jQuery(this);
			var removeimage_id = jQuery(this).data('cid');
			var delimgwpnonce = jQuery(this).data('nonce');
			var attach_id = jQuery(this).closest('li').find("img").data("attach_id");
			
			jQuery.ajax({
				type: "POST",
				url: geodir_reviewrating_all_js_msg.geodir_reviewrating_admin_ajax_url,
				data: {
					ajax_action: 'remove_images_by_url',
                    attach_id: attach_id,
					remove_image_id: removeimage_id,
					_wpnonce: delimgwpnonce
				}
			}).done(function(data) {
				if (jQuery.trim(data) == '0') {
					jQuery('.post-action').find('span').each(function() {
						if (jQuery(this).attr('action') == 'commentimages') jQuery(this).remove();
					});
				}
				this_var.closest('li').remove();
			});
		}
	});	
	
	//bulk actions
	jQuery('.three-tab ul li').on("click", function() {
		jQuery(this).attr('action');
	});
	
	jQuery("#gdcomment-filter_button").on("click", function() {
		var url = jQuery('input[name="review_url"]').val();
		var tab = jQuery('input[name="tab"]').val();
		var section = jQuery('input[name="section"]').val();
		var geodir_comment_search = jQuery('input[name="geodir_comment_search"]').val();
		var geodir_comment_posttype = jQuery('select[name="geodir_comment_posttype"]').val();
		var geodir_comment_sort = jQuery('select[name="geodir_comment_sort"]').val();
		
		window.location = url + '&tab=' + tab + '&section=' + section + '&geodir_comment_search=' + geodir_comment_search + '&geodir_comment_posttype=' + geodir_comment_posttype + '&geodir_comment_sort=' + geodir_comment_sort;
	});
	
	jQuery("select[name='geodir_comment_sort']").on("change", function() {
		jQuery("#gdcomment-filter_button").trigger("click");
	});
	
	jQuery(document).delegate(".comments_review_likeunlike .gdrr-btn-like", "click", function() {
		var $this = this;
		var cont = jQuery($this).closest('.comments_review_likeunlike');
		var comment_id = jQuery(cont).data('comment-id');
		var task = jQuery(cont).data('like-action');
		var wpnonce = jQuery(cont).data('wpnonce');
		if (!comment_id || !wpnonce || !(task == 'like' || task == 'unlike'))
			return false;
		
		var btnlike = jQuery($this).parent().html();
		jQuery($this).replaceWith('<i class="fas fa-sync fa-spin" aria-hidden="true"></i>');
		jQuery.post(geodir_params.gd_ajax_url, {
			action: 'geodir_reviewrating_ajax',
			ajax_action: 'review_update_frontend',
			task: task,
			comment_id: comment_id,
			_wpnonce: wpnonce
		}).done(function(data) {
			if (data && data !== '0') {
				cont.replaceWith(data);
			} else {
				jQuery('.fa-spin', cont).replaceWith(btnlike);
			}
		});
	});	

	if (geodir_params.multirating && jQuery('.gd-rating-input-wrap').closest('#commentform').length) {
		var $frm_obj = jQuery('.gd-rating-input-wrap').closest('#commentform'),commentField,commentTxt,errors;
		var optional_multirating = geodir_reviewrating_all_js_msg.geodir_reviewrating_optional_multirating;

		jQuery('input[name="submit"]', $frm_obj).on('click', function(e) {
			errors = 0;
			jQuery('#err_no_rating', $frm_obj).remove();
			jQuery('#err_no_comment', $frm_obj).remove();
			$comment = jQuery('textarea[name="comment"]', $frm_obj);
			is_review = jQuery('#comment_parent', $frm_obj).val();
			is_review = parseInt(is_review) == 0 ? true : false;
			commentField = typeof tinyMCE != 'undefined' && typeof tinyMCE.editors != 'undefined' && typeof tinyMCE.editors['comment'] == 'object' ? tinyMCE.editors['comment'] : null;
				
			if (is_review) {
				jQuery('.gd-rating-input-wrap', $frm_obj).each(function() {
					var rat_obj = this;
					// Overall ratings
					jQuery(rat_obj).find('[name=geodir_overallrating]').each(function() {
						var star_obj = this;
						var star = parseInt(jQuery(star_obj).val());
						if (!star > 0) {
							errors++;
						}
					});

					if (!errors) {
						// Multi ratings
						jQuery(rat_obj).find('[name^=geodir_rating]').each(function() {
							var star_obj = this;
							var mandatory = optional_multirating && jQuery(star_obj).attr('name') != 'geodir_overallrating' ? false : true;
							var star = parseInt(jQuery(star_obj).val());
							if (!star > 0 && mandatory) {
								errors++;
							}
						});
					}

					if (errors > 0) {
						jQuery(rat_obj).append('<div id="err_no_rating" class="err-no-rating">' + geodir_params.gd_cmt_err_no_rating + '</div>');
						return false;
					}
				});
			} else {
			}
			if (errors > 0) {
				return false;
			}
			if (commentField) {
				commentField.editorManager.triggerSave();
			}
			commentTxt = jQuery.trim($comment.val());
			if (!commentTxt) {
				error = is_review ? geodir_reviewrating_all_js_msg.err_empty_review : geodir_reviewrating_all_js_msg.err_empty_reply;
				$comment.before('<div id="err_no_comment" class="err-no-rating">' + error + '</div>');
				$comment.focus();
				return false;
			}
			return true;
		});
	}
});
