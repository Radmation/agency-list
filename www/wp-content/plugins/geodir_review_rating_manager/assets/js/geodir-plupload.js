jQuery.fn.exists = function() {
	return jQuery(this).length > 0;
}

jQuery(document).ready(function($) {
	geodir_review_upload_init();
});

var geodir_totalImg;

function geodir_review_upload_init() {
	if (jQuery(".gd-plupload-upload-uic").exists()) {
		var pconfig = false;
		var msgErr = '';
		
		jQuery(".gd-plupload-upload-uic").each(function() {
			var $this = jQuery(this);
			var id1 = $this.attr("id");
			var imgId = id1.replace("plupload-upload-ui", "");
			
			gd_plu_show_thumbs(imgId);
			
			pconfig = JSON.parse(geodir_reviewrating_plupload_localize.geodir_reviewrating_plupload_config);
			pconfig["browse_button"] = imgId + pconfig["browse_button"];
			pconfig["container"] = imgId + pconfig["container"];
			pconfig["drop_element"] = imgId + pconfig["drop_element"];
			pconfig["file_data_name"] = imgId + pconfig["file_data_name"];
			pconfig["multipart_params"]["imgid"] = imgId;

			if ($this.hasClass("gd-plupload-upload-uic-multiple")) {
				pconfig["multi_selection"] = true;
			}
			
			if ($this.find(".plupload-resize").exists()) {
				var w = parseInt($this.find(".plupload-width").attr("id").replace("plupload-width", ""));
				var h = parseInt($this.find(".plupload-height").attr("id").replace("plupload-height", ""));
				pconfig["resize"] = {
					width: w,
					height: h,
					quality: 90
				};
			}
			
			var uploader = new plupload.Uploader(pconfig);
			uploader.bind('Init', function(up) {});
			uploader.init();
			uploader.bind('Error', function(up, files) {
				jQuery('#upload-error').addClass('upload-error');
				
				if (files.code == -600) {
					msgErr = geodir_reviewrating_plupload_localize.geodir_err_file_size;
				} else if (files.code == -601) {
					msgErr = geodir_reviewrating_plupload_localize.geodir_err_file_type;
				} else {
					msgErr = files.message;
				}
				
				jQuery('#upload-error').html(msgErr);
			});
			
			// a file was added in the queue
			geodir_totalImg = 0;
			geodir_limitImg = geodir_reviewrating_plupload_localize.geodir_image_limit;
			uploader.bind('FilesAdded', function(up, files) {
				jQuery('#upload-error').html('');
				jQuery('#upload-error').removeClass('upload-error');
				if (geodir_limitImg) {
					if (geodir_totalImg == geodir_limitImg && parseInt(geodir_limitImg) > 0) {
						while (up.files.length > 0) {
							up.removeFile(up.files[0]);
						} // remove images
						
						jQuery('#upload-error').addClass('upload-error');
						jQuery('#upload-error').html(geodir_reviewrating_plupload_localize.geodir_err_file_limit);
						return false;
					}
					if (up.files.length > geodir_limitImg && parseInt(geodir_limitImg) > 0) {
						
						while (up.files.length > 0) {
							up.removeFile(up.files[0]);
						} // remove images
						
						msgErr = geodir_reviewrating_plupload_localize.geodir_err_file_pkg_limit;
						msgErr = msgErr.replace("%s", geodir_limitImg);
						
						jQuery('#upload-error').addClass('upload-error');
						jQuery('#upload-error').html(msgErr);
						return false;
					}
					if (parseInt(up.files.length) + parseInt(geodir_totalImg) > parseInt(geodir_limitImg)) {
						while (up.files.length > 0) {
							up.removeFile(up.files[0]);
						} // remove images
						
						msgErr = geodir_reviewrating_plupload_localize.geodir_err_file_remain_limit;
						msgErr = msgErr.replace("%s", (parseInt(geodir_limitImg) - parseInt(geodir_totalImg)));
						
						jQuery('#upload-error').addClass('upload-error');
						jQuery('#upload-error').html(msgErr);
						return false;
					}
				}
				jQuery.each(files, function(i, file) {
					$this.find('.filelist').append('<div class="file" id="' + file.id + '"><b>' + file.name + '</b> (<span>' + plupload.formatSize(0) + '</span>/' + plupload.formatSize(file.size) + ') ' + '<div class="fileprogress"></div></div>');
				});
				
				up.refresh();
				up.start();
			});
			
			uploader.bind('UploadProgress', function(up, file) {
				jQuery('#' + file.id + " .fileprogress").width(file.percent + "%");
				jQuery('#' + file.id + " span").html(plupload.formatSize(parseInt(file.size * file.percent / 100)));
			});
			uploader.bind('UploadComplete', function(up, files) {
				while (up.files.length > 0) {
					up.removeFile(up.files[0]);
				} // remove images
			});
			// a file was uploaded
			
			var timer;
			var i = 0;
			var indexes = new Array();
			
			uploader.bind('FileUploaded', function(up, file, response) {
				indexes[i] = up;
				clearInterval(timer);
				timer = setTimeout(function() {}, 1000);
				i++;
				
				jQuery('#' + file.id).fadeOut();
				response = response["response"]
				
				if (response != null && response != 'null' && response != '') {
					geodir_totalImg++;
					// add url to the hidden field
					if ($this.hasClass("gd-plupload-upload-uic-multiple")) {
						// multiple
						var v1 = jQuery.trim(jQuery("#" + imgId).val());
						
						if (v1) {
							v1 = v1 + "," + response;
						} else {
							v1 = response;
						}
						jQuery("#" + imgId).val(v1);
					} else {
						// single
						jQuery("#" + imgId).val(response + "");
					}
				}
				// show thumbs
				gd_plu_show_thumbs(imgId);
			});
		});
	}
}

function geodir_review_remove_file_index(indexes) {
	for (var i = 0; i < indexes.length; i++) {
		if (indexes[i].files.length > 0) {
			indexes[i].removeFile(indexes[i].files[0]);
		}
	}
}

function gd_plu_show_thumbs(imgId) {
	var $ = jQuery;
	var thumbsC = $("#" + imgId + "plupload-thumbs");
	thumbsC.html("");
	var txtRemove = geodir_reviewrating_plupload_localize.geodir_text_remove;
	// get urls
	var imagesS = $("#" + imgId).val();
	var images = imagesS.split(",");
	
	for (var i = 0; i < images.length; i++) {
		if (images[i] && images[i] != null && images[i] != 'null') {

            var img_arr = images[i].split("|");
            var image_url = img_arr[0];
            var image_id = img_arr[1];
            var image_title = img_arr[2];
            var image_caption = img_arr[3];

            // fix undefined id
            if(typeof image_id === "undefined"){
                image_id = '';
            }
            // fix undefined title
            if(typeof image_title === "undefined"){
                image_title = '';
            }
            // fix undefined title
            if(typeof image_caption === "undefined"){
                image_caption = '';
            }

            var file_ext = image_url.substring(images[i].lastIndexOf('.') + 1);

            file_ext = file_ext.split('?').shift();// in case the image url has params
            var fileNameIndex = image_url.lastIndexOf("/") + 1;
            var dotIndex = image_url.lastIndexOf('.');
            if(dotIndex < fileNameIndex){continue;}
            var file_name = image_url.substr(fileNameIndex, dotIndex < fileNameIndex ? loc.length : dotIndex);

            var file_display = '';
            var file_display_class = '';
            if (file_ext == 'jpg' || file_ext == 'jpe' || file_ext == 'jpeg' || file_ext == 'png' || file_ext == 'gif' || file_ext == 'bmp' || file_ext == 'ico') {
                file_display ='<img class="gd-file-info" data-id="'+image_id+'" data-title="'+image_title+'" data-caption="'+image_caption+'" data-src="' + image_url + '" src="' + image_url + '" alt=""  />';
            }else{
                var file_type_class = 'fa-file';
                if (file_ext == 'pdf') {file_type_class = 'fa-file-pdf';}
                else if(file_ext == 'zip' || file_ext == 'tar'){file_type_class = 'fa-file-archive';}
                else if(file_ext == 'doc' || file_ext == 'odt'){file_type_class = 'fa-file-word';}
                else if(file_ext == 'txt' || file_ext == 'text'){file_type_class = 'fa-file-text';}
                else if(file_ext == 'csv' || file_ext == 'ods' || file_ext == 'ots'){file_type_class = 'fa-file-excel';}
                else if(file_ext == 'avi' || file_ext == 'mp4' || file_ext == 'mov'){file_type_class = 'fa-file-video';}
                file_display_class = 'file-thumb';
                file_display ='<i title="'+file_name+'" class="fa '+file_type_class+' gd-file-info" data-id="'+image_id+'" data-title="'+image_title+'" data-caption="'+image_caption+'" data-src="' + image_url + '" aria-hidden="true"></i>';
            }

            var thumb = $('<div class="thumb '+file_display_class+'" id="thumb' + imgId + i + '">' +
                file_display +
                '<div class="gd-thumb-actions">'+
                '<span class="thumbeditlink" onclick="gd_edit_image_meta('+imgId+','+i+');"><i class="far fa-edit" aria-hidden="true"></i></span>' +
                '<span class="thumbremovelink" id="thumbremovelink' + imgId + i + '"><i class="fas fa-trash-alt" aria-hidden="true"></i></span>' +
                '</div>'+
                '</div>');

			thumbsC.append(thumb);
			
			thumb.find(".thumbremovelink").on("click", function() {

                if (jQuery('#' + imgId + 'plupload-upload-ui').hasClass("plupload-upload-uic-multiple")){
                    geodir_totalImg--; // remove image from total
                    jQuery("#" + imgId + "totImg").val(totalImg);
                }
                jQuery('#' + imgId + 'upload-error').html('');
                jQuery('#' + imgId + 'upload-error').removeClass('upload-error');
                var ki = $(this).attr("id").replace("thumbremovelink" + imgId, "");
                ki = parseInt(ki);
                var kimages = [];
                imagesS = $("#" + imgId).val();
                images = imagesS.split(",");
                for (var j = 0; j < images.length; j++) {
                    if (j != ki) {
                        kimages[kimages.length] = images[j];
                    }
                }
                $("#" + imgId).val(kimages.join());

				gd_plu_show_thumbs(imgId);
				return false;
			});
		}
	}
	if (images.length > 1) {
		thumbsC.sortable({
			update: function(event, ui) {
				var kimages = [];
				
				thumbsC.find("img").each(function() {
					kimages[kimages.length] = $(this).attr("src");
					$("#" + imgId).val(kimages.join());
					gd_plu_show_thumbs(imgId);
				});
			}
		});
		thumbsC.disableSelection();
	}

    var kimages = [];
    thumbsC.find(".gd-file-info").each(function () {
        kimages[kimages.length] = $(this).data("src")+"|"+$(this).data("id")+"|"+$(this).data("title")+"|"+$(this).data("caption");
        $("#" + imgId).val(kimages.join());
    });
}