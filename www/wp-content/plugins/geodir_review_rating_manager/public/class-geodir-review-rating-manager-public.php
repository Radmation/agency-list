<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com/
 * @since      1.0.0
 *
 * @package    Geodir_review_rating_manager
 * @subpackage Geodir_review_rating_manager/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Geodir_review_rating_manager
 * @subpackage Geodir_review_rating_manager/public
 * @author     GeoDirectory <info@wpgeodirectory.com>
 */
class GeoDir_Review_Rating_Manager_Public {

    public function __construct() {
        add_filter('geodir_overall_rating_label', array(__CLASS__,'overall_label_text'));
        add_action( 'geodir_comment_links_after_edit', array(__CLASS__,'like_button') );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_aui' ), 10 );
    }

    public static function overall_label_text(){
        return  __('Overall','geodir_reviewratings');
    }

    public function enqueue_styles() {
        
        $design_style = geodir_design_style();

        wp_enqueue_script( 'jquery' );

        if ( geodir_is_page('detail') ) {
            $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            if(!$design_style){
                wp_register_script( 'geodir-reviewrating-review-script', GEODIR_REVIEWRATING_PLUGINDIR_URL.'/assets/js/comments-script' . $suffix . '.js' );
                wp_enqueue_script( 'geodir-reviewrating-review-script' );

                wp_register_style( 'geodir-reviewratingrating-style', GEODIR_REVIEWRATING_PLUGINDIR_URL .'/assets/css/style.css' );
                wp_enqueue_style( 'geodir-reviewratingrating-style' );
            }


            if (!wp_script_is( 'geodir-plupload', 'enqueued' )) {
                wp_enqueue_script('geodir-plupload');
            }

            if(!$design_style) {
                if ( ! wp_script_is( 'geodir-lity', 'enqueued' ) ) {
                    wp_enqueue_script( 'geodir-lity' );
                }
            }

            // SCRIPT FOR UPLOAD
            wp_enqueue_script('plupload-all');
            wp_enqueue_script('jquery-ui-sortable');
            if(!$design_style) {
                wp_register_script( 'geodir-reviewrating-plupload-script', GEODIR_REVIEWRATING_PLUGINDIR_URL . '/assets/js/geodir-plupload' . $suffix . '.js' );
                wp_enqueue_script( 'geodir-reviewrating-plupload-script' );
            }

        }

        $max_upload_size = geodir_max_upload_size();

        $allowed_img_types = 'jpg,jpeg,jpe,gif,png';
        /**
         * Filter the allowed image type extensions for review images upload.
         *
         * @since 1.2.4
         * @param int $allowed_img_types The image type extensions.
         */
        $allowed_img_types = apply_filters('geodir_reviewrating_allowed_review_image_exts', $allowed_img_types);
        $allowed_img_types = $allowed_img_types != '' ? $allowed_img_types : '*';

        $image_limit = 10;
        /**
         * Filter the limit of review images upload.
         *
         * @since 1.2.4
         * @param string $image_limit The image upload limit.
         */
        $image_limit = apply_filters('geodir_reviewrating_allowed_review_image_limit', $image_limit);

        // place js config array for plupload
        $geodir_plupload_init = array(
            'runtimes' => 'html5,silverlight,html4',
            'browse_button' => 'plupload-browse-button', // will be adjusted per uploader
            'container' => 'plupload-upload-ui', // will be adjusted per uploader
            'drop_element' => 'dropbox', // will be adjusted per uploader
            'file_data_name' => 'async-upload', // will be adjusted per uploader
            'multiple_queues' => true,
            'max_file_size' => $max_upload_size,
            'url' => admin_url('admin-ajax.php'),
            'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
            'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
            'filters' => array(array('title' => __('Allowed Files', 'geodir_reviewratings'), 'extensions' => $allowed_img_types)),
            'multipart' => true,
            'urlstream_upload' => true,
            'multi_selection' => false, // will be added per uploader
            // additional post data to send to our ajax hook
            'multipart_params' => array(
                '_ajax_nonce' => wp_create_nonce( "geodir_attachment_upload" ),
                'action' => 'geodir_post_attachment_upload', // the ajax action name
                'imgid' => 0, // will be added per uploader
                'post_id' => '' // will be added per uploader
            )
        );

        $geodir_reviewrating_plupload_config = json_encode($geodir_plupload_init);

        $allowed_img_types = str_replace(',', ', .', $allowed_img_types);
        $geodir_plupload_init = array(
            'geodir_reviewrating_plupload_config' => $geodir_reviewrating_plupload_config,
            'geodir_totalImg' => 0,
            'geodir_image_limit' => (int)$image_limit,
            'geodir_upload_img_size' => $max_upload_size,
            'geodir_err_file_limit' => wp_sprintf(__('You have reached your upload limit of %s images.', 'geodir_reviewratings'), $image_limit),
            'geodir_err_file_pkg_limit' => wp_sprintf(__('You may only upload total %s images.', 'geodir_reviewratings'), $image_limit),
            'geodir_err_file_remain_limit' => __('You may only upload another %s images.', 'geodir_reviewratings'),
            'geodir_err_file_size' => wp_sprintf(__('File size error : You tried to upload an image over %s', 'geodir_reviewratings'), $max_upload_size),
            'geodir_err_file_type' => wp_sprintf(__('File type error. Allowed image types: %s', 'geodir_reviewratings'), $allowed_img_types),
            'geodir_text_remove' => __('Remove', 'geodir_reviewratings'),
        );

        if ( geodir_is_page('detail') ) {
            $script = $design_style ? 'plupload-all' : 'geodir-reviewrating-plupload-script';
            wp_localize_script( $script, 'geodir_reviewrating_plupload_localize', $geodir_plupload_init );
            
        }
    }

    public static function enqueue_aui(){
        $design_style = geodir_design_style();
        if ($design_style && geodir_is_page('detail') ) {
            wp_add_inline_script( 'geodir', self::single_page_script() );
        }
    }
    
    public static function single_page_script(){
		global $aui_bs5;

        ob_start();
if(0){ ?><script><?php }?>
document.addEventListener("DOMContentLoaded", function(event) {
    var els = document.getElementsByClassName('comments_review_likeunlike');

    for (var i = 0; i < els.length; i++) {
        var el = els[i].parentElement;
        el.addEventListener("click", function(e) {
            if (jQuery(e.target).closest('.comments_review_likeunlike').length) {
                var $this = jQuery(e.target).closest('.comments_review_likeunlike');
            } else if (jQuery(e.target).hasClass('.comments_review_likeunlike')) {
                var $this = jQuery(this);
            } else {
                return;
            }
            var cont = jQuery($this).closest('.comments_review_likeunlike');
            var comment_id = jQuery($this).data('comment-id');
            var task = jQuery($this).data('like-action');
            var template = jQuery($this).data('template');
            var wpnonce = jQuery(cont).data('wpnonce');
            if (!comment_id || !wpnonce || !(task == 'like' || task == 'unlike'))
                return false;

            var btnlike = jQuery($this).find('.gdrr-btn-like').parent().html();
            jQuery($this).find('.gdrr-btn-like').replaceWith('<i class="fas fa-sync fa-spin <?php echo ( $aui_bs5 ? 'me-1' : 'mr-1' ); ?>" aria-hidden="true"></i>');
            jQuery.post(geodir_params.gd_ajax_url, {
                action: 'geodir_reviewrating_ajax',
                ajax_action: 'review_update_frontend',
                task: task,
                template: template,
                comment_id: comment_id,
                _wpnonce: wpnonce
            }).done(function(data) {
                if (data && data !== '0') {
                    cont.replaceWith(data);
                } else {
                    jQuery('.fa-spin', cont).replaceWith(btnlike);
                }
            });
        }, false);
    }

    if (geodir_params.multirating && jQuery('.gd-rating-input-wrap').closest('#commentform').length) {
        var $frm_obj = jQuery('.gd-rating-input-wrap').closest('#commentform'), commentField, commentTxt, errors;
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
                        jQuery(rat_obj).after('<div id="err_no_rating" class="err-no-rating alert alert-danger">' + geodir_params.gd_cmt_err_no_rating + '</div>');
                        jQuery("#err_no_rating").get(0).scrollIntoView();
                        return false;
                    }
                });
            } else {}
            if (errors > 0) {
                return false;
            }
            if (commentField) {
                commentField.editorManager.triggerSave();
            }
            if (!(is_review && geodir_reviewrating_all_js_msg.allow_empty_review)) {
                commentTxt = jQuery.trim($comment.val());
                if (!commentTxt) {
                    error = is_review ? geodir_reviewrating_all_js_msg.err_empty_review : geodir_reviewrating_all_js_msg.err_empty_reply;
                    $comment.before('<div id="err_no_comment" class="err-no-rating alert alert-danger">' + error + '</div>');
                    $comment.focus();
                    return false;
                }
            }
            return true;
        });
    }
});
<?php if(0){ ?></script><?php }

        return ob_get_clean();
    }

    /**
     * Adds custom columns in detail table for review rating manager.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @param string $post_type The post type.
     * @param string $detail_table The detail table name.
     */
    public function geodir_reviewrating_after_custom_detail_table_create($post_type, $detail_table){

        $post_types = geodir_get_posttypes();

        if(in_array($post_type, $post_types)){
            geodir_add_column_if_not_exist($detail_table, 'ratings',  'TEXT NULL DEFAULT NULL');
            geodir_add_column_if_not_exist($detail_table, 'overall_rating',  'float(11) DEFAULT NULL');
        }

    }

    /**
     * Rating manager delete comment by comment ID.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @param int $comment_id The comment ID.
     */
    public function geodir_reviewrating_delete_comments( $comment_id ){

        global $wpdb;

        geodir_reviewrating_delete_comment_images($comment_id);

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." WHERE comment_id = %d",
                array($comment_id)
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM ".GEODIR_COMMENTS_REVIEWS_TABLE." WHERE comment_id = %d",
                array($comment_id)
            )
        );
    }

    /**
     * Rating manager set comment status.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @param int $comment_id The comment ID.
     * @param int|string $status The comment status.
     */
    function geodir_reviewrating_set_comment_status( $comment_id, $status ) {
        global $wpdb;

        $comment_info = get_comment( $comment_id );

        $post_id = isset( $comment_info->comment_post_ID ) ? $comment_info->comment_post_ID : '';

        if ( ! empty( $comment_info ) ) {
            $status = $comment_info->comment_approved;
		}

        if ( $status == 'approve' || $status == 1 ) {
			$status = 1;
		} else {
			$status = 0;
		}

        $comment_info_ID = isset( $comment_info->comment_ID ) ? $comment_info->comment_ID : '';
        $old_rating = geodir_get_commentoverall( $comment_info_ID );

        $post_type = get_post_type($post_id);

        if ( $comment_id ) {
            $overall_rating = $old_rating;

            if ( isset( $old_rating ) ) {
                $sqlqry = $wpdb->prepare( "UPDATE " . GEODIR_REVIEWRATING_POSTREVIEW_TABLE . " SET rating = %f WHERE comment_id = %d", array( $overall_rating, $comment_id ) );
                $wpdb->query( $sqlqry );

                $post_newrating = geodir_get_review_total( $post_id );

                // Update rating
                geodir_update_postrating( $post_id, $post_type );
            }

            // Update post average ratings
            geodir_reviewrating_update_post_ratings( $post_id );

			// Update comment attachments status
			self::set_comment_attachments_status( $comment_id, $status );
        }
    }

    function geodir_reviewrating_list_comments_args( $args ) {

        if ( !empty( $args['callback'] ) && $args['callback'] == 'geodir_comment' ) {
            $page_comments = get_option('page_comments');
            $comment_order = get_option('comment_order');
            $default_comments_page = get_option('default_comments_page');
            $default_shorting = 'latest';

            $reverse_order = false;
            if ( $page_comments ) {
                if ( $default_comments_page == 'newest' ) {
                    $default_shorting = 'latest';
                    $reverse_order = true;
                } else if ( $default_comments_page == 'oldest' ) {
                    $default_shorting = 'oldest';
                }
            }

            $comment_sorting = apply_filters( 'geodir_reviewrating_comments_shorting_default', $default_shorting );
            $comment_sorting = isset( $_REQUEST['comment_sorting'] ) && $_REQUEST['comment_sorting'] != '' ? $_REQUEST['comment_sorting'] : $comment_sorting;

            if ( $comment_sorting == 'latest' || $comment_sorting == 'oldest' || empty( $comment_sorting ) ) {
                if ( $comment_sorting == 'latest' && ( $default_comments_page == 'oldest' || ( !$page_comments && $default_comments_page == 'newest' ) ) ) {
                    $args['reverse_top_level'] = $comment_order == 'asc' ? true : false;

                    if ( !$page_comments && $comment_order == 'asc' && ( $default_comments_page == 'newest' || $default_comments_page == 'oldest' ) ) {
                        $args['reverse_top_level'] = false;
                    }
                } else if ( $comment_sorting == 'oldest' && !$page_comments && ( $default_comments_page == 'newest' || $default_comments_page == 'oldest' ) ) {
                    $args['reverse_top_level'] = false;
                } else {
                    $args['reverse_top_level'] = null;
                }
            } else {
                $args['reverse_top_level'] = $reverse_order;
            }
        }
        return $args;
    }

    function geodir_reviewrating_reviews_query_args( $comment_args ) {
        global $gd_comment_args;

        $gd_comment_args = $comment_args;

        return $comment_args;
    }

    function geodir_reviewrating_reviews_clauses( $clauses, $wp_comment_query = array() ) {
        global $post, $wpdb, $gd_comment_args, $gd_filter_reviews;

        if ( !empty( $wp_comment_query->query_vars['parent__in'][0] ) ) {
            $clauses['fields'] = $wpdb->comments . ".*, " . $wpdb->comments . ".comment_content AS comment_content";
            return $clauses;
        }

        if (empty($post) || (!is_single() && !is_page()) || (isset($post->comment_count) && $post->comment_count <= 0) || !empty($wp_comment_query->query_vars['count'])) {
            return $clauses;
        }

        $gd_filter_reviews = true;
        $all_postypes = geodir_get_posttypes();

        if (!(!empty($post->post_type) && in_array($post->post_type, $all_postypes))) {
            return $clauses;
        }

        $comments_shorting = 'latest';

        $page_comments = get_option('page_comments');
        $comment_order = get_option('comment_order');
        $default_comments_page = get_option('default_comments_page');
        $reverse_order = false;

        if ( $page_comments ) {
            if ( $default_comments_page == 'newest' ) {
                $comments_shorting = 'latest';
                $reverse_order = true;
            } else if ( $default_comments_page == 'oldest' ) {
                $comments_shorting = 'oldest';
                //$reverse_order = true;
            }
        }

        /**
         * Filter the default comments sorting.
         *
         * @since 1.1.7
         * @package GeoDirectory_Review_Rating_Manager
         *
         * @param string $comment_sorting Sorting name to sort comments.
         */
        $comment_sorting = apply_filters( 'geodir_reviewrating_comments_shorting_default', $comments_shorting );
        $comment_sorting = isset( $_REQUEST['comment_sorting'] ) && $_REQUEST['comment_sorting'] != '' ? $_REQUEST['comment_sorting'] : $comment_sorting;

        switch( $comment_sorting ) {
            case 'low_rating':
                $sorting_orderby = GEODIR_REVIEWRATING_POSTREVIEW_TABLE . '.rating';
                $sorting_order = 'ASC';
                break;
            case 'high_rating':
                $sorting_orderby = GEODIR_REVIEWRATING_POSTREVIEW_TABLE . '.rating';
                $sorting_order = 'DESC';
                break;
            case 'low_review':
                $sorting_orderby = 'wasthis_review';
                $sorting_order = 'ASC';
                break;
            case 'high_review':
                $sorting_orderby = 'wasthis_review';
                $sorting_order = 'DESC';
                break;
            case 'oldest':
                $sorting_orderby = 'comment_date_gmt';
                $sorting_order = 'ASC';
                break;
            case 'least_images':
                $sorting_orderby = 'total_images';
                $sorting_order = 'ASC';
                break;
            case 'highest_images':
                $sorting_orderby = 'total_images';
                $sorting_order = 'DESC';
                break;
            default:
                $sorting_orderby = 'comment_date_gmt';
                $sorting_order = 'DESC';
        }

        if ( $reverse_order ) {
            if ( $sorting_order == 'DESC' ) {
                $sorting_order = 'ASC';
            } else {
                $sorting_order = 'DESC';
            }
        }

        if ( !isset( $clauses['groupby'] ) ) {
            $clauses['groupby'] = "";
        }

        $clauses['fields'] = $wpdb->comments . ".*, " . $wpdb->comments . ".comment_content AS comment_content";
        $clauses['join'] .= " LEFT JOIN " . GEODIR_REVIEWRATING_POSTREVIEW_TABLE." ON " . GEODIR_REVIEWRATING_POSTREVIEW_TABLE . ".comment_id = " . $wpdb->comments . ".comment_ID";
        $clauses['groupby'] = "{$wpdb->comments}.comment_ID";
        $clauses['orderby'] = $sorting_orderby . ' ' . $sorting_order;
        $clauses['order'] = $sorting_order;

        if ( $sorting_orderby != 'comment_date_gmt' ) {
            $clauses['orderby'] .= ", comment_date_gmt ";

            if ( $reverse_order ) {
                $clauses['orderby'] .= 'ASC';
            } else {
                $clauses['orderby'] .= 'DESC';
            }
        }

        // fix comment awaiting moderation
        if ( isset( $clauses['where'] ) && $clauses['where'] ) {
            $clauses['where'] = str_replace( ' user_id ', ' ' . $wpdb->comments . '.user_id', $clauses['where'] );
        }

        return $clauses;
    }

    /**
     * Add ratings column to the detail table when a new post type get created.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @param string $post_type The post type.
     */
    public function geodir_reviewrating_create_new_post_type($post_type = ''){

        global $plugin_prefix;

        if($post_type != ''){

            $all_postypes = geodir_get_posttypes();

            if(!in_array($post_type, $all_postypes))
                return false;

            $detail_table = $plugin_prefix . $post_type . '_detail';
            geodir_add_column_if_not_exist($detail_table, 'ratings',  'TEXT NULL DEFAULT NULL');

        }
    }

    /**
     * review rating manager delete post type.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @param string $post_type The post type.
     */
    public function geodir_reviewrating_delete_post_type($post_type = ''){

        global $wpdb;

        if($post_type != ''){

            $all_postypes = geodir_get_posttypes();

            $rating_data = $wpdb->get_results($wpdb->prepare("SELECT id, post_type FROM ".GEODIR_REVIEWRATING_CATEGORY_TABLE." WHERE FIND_IN_SET(%s, post_type)", array($post_type)));

            if(!empty($rating_data)){

                foreach($rating_data as $key => $rating){

                    $ratings = explode(",",$rating->post_type);

                    if(($del_key = array_search($post_type, $ratings)) !== false)
                        unset($ratings[$del_key]);

                    if(!empty($ratings)){

                        $ratings = implode(',',$ratings);

                        $wpdb->query($wpdb->prepare("UPDATE ".GEODIR_REVIEWRATING_CATEGORY_TABLE." SET post_type=%s WHERE id=%d",array($ratings,$rating->id)));

                    }else{

                        $wpdb->query($wpdb->prepare("DELETE FROM ".GEODIR_REVIEWRATING_CATEGORY_TABLE." WHERE id=%d", array($rating->id)));

                    }

                }

            }

        }

    }

    /**
     * Adds rating manager fields to the comment text.
     *
     * @since 1.0.0
     * @since 1.3.5 Fix rating validation for font-awesome star rating.
     * @since 1.3.6 Changes for disable review stars for certain post type.
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @global string $geodir_post_type The post type.
     *
     * @param string $content Comment text.
     * @param string|object $comment The comment object.
     * @return string Modified comment text.
     */
    public function geodir_reviewrating_wrap_comment_text($content,$comment=''){
        global $geodir_post_type, $aui_bs5;
        $all_postypes = geodir_get_posttypes();

        if(!in_array($geodir_post_type, $all_postypes))
            return $content;

        if (geodir_cpt_has_rating_disabled($geodir_post_type)) {
            return $content;
        }

        $like_unlike = '';
        $ratings_html = '';
        $rating_html = '';
        if(!empty($comment) && !is_admin()){

            $design_style = geodir_design_style();
            if(geodir_get_option('rr_enable_rating') && !$comment->comment_parent):
                $comment_ratings = geodir_reviewrating_get_comment_rating_by_id($comment->comment_ID);
                $ratings = @unserialize($comment_ratings->ratings);
                $ratings_html = $aui_bs5 ? '' :GeoDir_Review_Rating_Template::geodir_reviewrating_draw_ratings($ratings);
            endif;
            if(!is_admin()){
                $comment_images = GeoDir_Review_Rating_Template::geodir_reviewrating_get_comment_images($comment->comment_ID,$comment->comment_post_ID);
            }

            $images_show_hide = '';
            $comment_images_display = '';


            if (geodir_get_option('rr_enable_rating')):
                $rating_html = $ratings_html;
            endif;

            if(geodir_get_option('rr_enable_rate_comment') && !is_admin()):
                $like_unlike = GeoDir_Review_Rating_Like_Unlike::geodir_reviewrating_comments_like_unlike($comment->comment_ID, false);
            endif;

            if ( $design_style ) {
                if($rating_html){
                    $content = '<div class="border-bottom pb-2 mb-2 mt-n3" >' . $rating_html . '</div>' . $content;
                }

                if ( isset( $comment_images->html ) ) {
                    $content .= $comment_images->html;
                }

            }else{
            ob_start(); ?>
            <div class="gdreview_section">
                <div class="clearfix">
                    <div  style="float:left;"><?php echo $comment_images_display; ?></div>
                    <?php echo $like_unlike; ?>
                </div>

            </div>
            <div class="commenttext geodir-reviewrating-commenttext"><?php echo $content;?></div>
            <?php if($rating_html || ( isset($comment_images->html) && $comment_images->html) ) { ?>
                <div class="comment_more_ratings clearfix <?php
                if ( $comment_images->images ) {
                    echo " gdreview_comment_extra_images ";
                }
                if (  $rating_html) {
                    echo " gdreview_comment_extra_ratings ";
                }
                ?>">
                    <?php echo $rating_html; ?>
                    <?php
                    if ( isset( $comment_images->html ) ) {
                        echo $comment_images->html;
                    } ?>
                </div>
                <?php
            }

            $content = ob_get_clean();

            }

            return $content;
        } else {
            return $content;
        }
    }

    public static function like_button($comment){
        if(geodir_get_option('rr_enable_rate_comment') && !is_admin()){
            $like_unlike = GeoDir_Review_Rating_Like_Unlike::geodir_reviewrating_comments_like_unlike($comment->comment_ID, false);
            echo $like_unlike;
        }

    }

    /**
     * Adds sorting options to the comment list.
     *
     * @since 2.0.0
     *
     * @global object $post The current post object.
     *
     * @return bool|void
     */
    public function sort_ratings_select(){
        global $post, $geodir_post_type;

        $all_postypes = geodir_get_posttypes();

        if (!in_array($geodir_post_type, $all_postypes))
            return false;

        if (geodir_cpt_has_rating_disabled($geodir_post_type)) {
            return;
        }

        if (isset($_REQUEST['comment_sorting'])) { ?>
            <script type="text/javascript">
                jQuery(document).ready(function(){
                    jQuery('#gd-tabs dl dd').removeClass('geodir-tab-active');
                    jQuery('#gd-tabs dl dd').find('a').each(function(){
                        if (jQuery(this).attr('data-tab') == '#reviews')
                            jQuery(this).closest('dd').addClass('geodir-tab-active');

                    });
                });

            </script>
            <?php
        }

        if (!isset($post->ID)) {
            return;
        }

        $comment_sorting_form_field_val = array(
            'latest' => __( 'Latest', 'geodir_reviewratings' ),
            'oldest' => __( 'Oldest', 'geodir_reviewratings' ),
            'low_rating' => __( 'Lowest Rating', 'geodir_reviewratings' ),
            'high_rating' => __( 'Highest Rating', 'geodir_reviewratings' )
        );

        $comment_sorting_form_field_val = apply_filters( 'geodir_reviews_rating_comment_shorting', $comment_sorting_form_field_val );

        $design_style = geodir_design_style();

        $template = $design_style ? $design_style."/comment-sorting.php" : "legacy/comment-sorting.php";
        $args = array(
            'comment_sorting_form_field_val'    => $comment_sorting_form_field_val,
        );

        echo geodir_get_template_html( $template , $args, '', plugin_dir_path( GEODIR_REVIEWRATING_PLUGIN_FILE ). "templates/");
        

    }

    /**
     * Adds overall rating to the comment list.
     *
     * @since 2.0.0
     *
     * @global object $post The current post object.
     *
     */
    public function show_overall_multiratings( $bar_style = '' ) {
        global $post, $aui_bs5;

        if($aui_bs5 && doing_action('geodir_after_review_list_title')){
            return '';
        }
	    if ( $bar_style === '' ) {
		    $bar_style = $aui_bs5 ? true : '';
	    }
        $ratings = geodir_reviewrating_get_post_rating($post->ID);
        if (!empty($ratings)) {
            $ratings_html = GeoDir_Review_Rating_Template::geodir_reviewrating_draw_ratings($ratings, $bar_style);
            echo $ratings_html;
        }
    }

    /**
     * Adds review rating sorting options to the available sorting list.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @param array $arr Sorting array.
     * @return array Modified sorting array.
     */
    public function geodir_reviews_rating_update_comment_shorting_options($arr){

        if(geodir_get_option('rr_enable_images')){
            $arr['least_images'] = __( 'Least Images', 'geodir_reviewratings' );
            $arr['highest_images'] = __( 'Highest Images', 'geodir_reviewratings' );
        }

        if(geodir_get_option('rr_enable_rate_comment')){
            $arr['low_review'] = __( 'Lowest Reviews', 'geodir_reviewratings' );
            $arr['high_review'] = __( 'Highest Reviews', 'geodir_reviewratings' );
        }

        return $arr;
    }

    /**
     * Adds multi rating and image upload fields to comments.
     *
     * @since 1.0.0
     * @since 1.3.6 Changes for disable review stars for certain post type.
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @return bool
     */
    function geodir_reviewrating_comment_rating_fields() {
        global $geodir_post_type;

        if (!$geodir_post_type) {
            $geodir_post_type = geodir_get_current_posttype();
        }

        $all_postypes = geodir_get_posttypes();

        if (!in_array($geodir_post_type, $all_postypes))
            return false;

        if (geodir_cpt_has_rating_disabled($geodir_post_type)) {
            return false;
        }

        if (geodir_get_option('rr_enable_rating')) {
            GeoDir_Review_Rating_Template::geodir_reviewrating_rating_frm_html();
        }

        if(geodir_get_option('rr_enable_images')):
            GeoDir_Review_Rating_Template::geodir_reviewrating_rating_img_html();
        endif;
    }

    /**
     * Review Rating replay link.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @param $link
     * @return string
     */
    function geodir_reviewrating_comment_replylink($link){

        if(!geodir_design_style()){
            $link = '<div class="gdrr-comment-replaylink">'.$link.'</div>';
        }

        return $link;
    }

    /**
     * Review Rating cancel reply link.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @param $link
     * @return string
     */
    function geodir_reviewrating_cancle_replylink($link){

        $link = '<span class="gdrr-cancel-replaylink">'.$link.'</span>';

        return $link;
    }

	/**
	 * Review Rating update Comment Rating.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory_Review_Rating_Manager
	 *
	 * @param $comment_content
	 * @return mixed
	 */
	function geodir_reviewrating_update_comments( $comment_content ) {
		global $wpdb, $post;

		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'editedcomment' && isset( $_REQUEST['geodir_overallrating'] ) ) {
			$comment_ID = absint( $_REQUEST['comment_ID'] );
			$comment_post_ID = absint( $_REQUEST['comment_post_ID'] );

			$post_type = get_post_type( $comment_post_ID );

			if ( ! geodir_is_gd_post_type( $post_type ) ) {
				return false;
			}

			if ( geodir_cpt_has_rating_disabled( $post_type ) ) {
				return false;
			}

			$ratings = '';
			$rating = array();
			$overall_rating = sanitize_text_field( $_REQUEST['geodir_overallrating'] );

			if ( isset( $_REQUEST['geodir_rating'] ) && is_array( $_REQUEST['geodir_rating'] ) ) {
				foreach ( $_REQUEST['geodir_rating'] as $key => $value ) {
					if ( $key != 'overall' ) {
						$rating[ $key ] = sanitize_text_field( $value );
					} else {
						$overall_rating = sanitize_text_field( $value );
					}
				}

				$ratings = ! empty( $rating ) ? maybe_serialize( $rating ) : '';
			}

			$attachments = '';
			$total_images = 0;

			if ( isset( $_POST['comment_images'] ) ) {
				$file_info = $_POST['comment_images'];
				$newArr = explode( '::', $file_info );
				$total_images = count( $newArr );
				$comment_images = geodir_reviewrating_add_remove_images( $comment_ID, $newArr, $comment_post_ID );

				if ( ! empty( $comment_images ) ) {
					$attachments = implode( '::', $comment_images );
				}
			}

			if ( ! empty( $rating ) || $overall_rating || $attachments != '' ) {
				$data = array(
					'rating' => $overall_rating,
					'ratings' => $ratings,
					'attachments' => $attachments,
					'total_images' => $total_images
				);

				$format = array(
					'%f',
					'%s',
					'%s',
					'%d'
				);

				$wpdb->update( GEODIR_REVIEWRATING_POSTREVIEW_TABLE, $data, array( 'comment_id' => $comment_ID ), $format );

				if ( ! empty( $rating ) || $overall_rating ) {
					geodir_reviewrating_update_postrating( $comment_post_ID, $rating, $overall_rating );
				}

				geodir_reviewrating_update_post_ratings( $comment_post_ID );
			}
		}

		return $comment_content;
	}

	/**
	 * Review Rating insert Comment Rating.
	 *
	 * @since 1.0.0
	 * @since 1.3.6 Changes for disable review stars for certain post type.
	 * @package GeoDirectory_Review_Rating_Manager
	 *
	 * @param int $comment The comment ID.
	 * @param int|string $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
	 * @param array $commentdata Comment data.
	 * @return bool
	 */
	function geodir_reviewrating_save_rating( $comment = 0, $comment_approved = false, $commentdata = array() ) {
		global $wpdb;

		$comment_info = get_comment( $comment );
		if ( empty( $comment_info ) ) {
			return false;
		}

		if ( empty( $comment_info->comment_post_ID ) ) {
			return false;
		}
		$post_id = $comment_info->comment_post_ID;
		$post = get_post( $post_id );
		$post_type = isset( $post->post_type ) ? $post->post_type : get_post_type( $post_id );

		if ( ! geodir_is_gd_post_type( $post_type ) ) {
			return false;
		}

		if ( geodir_cpt_has_rating_disabled( $post_type ) ) {
			return false;
		}

		$status = $comment_info->comment_approved;
		$rating = array();
		$overall_rating = sanitize_text_field($_REQUEST['geodir_overallrating']);

		if (isset($_REQUEST['geodir_rating']) && is_array($_REQUEST['geodir_rating']) && geodir_get_option('rr_enable_rating')) {
			foreach ($_REQUEST['geodir_rating'] as $key => $value) {
				if ($key != 'overall') {
					$rating[$key] = sanitize_text_field($value);
				} else {
					$overall_rating = sanitize_text_field($value);
				}
			}

			if (!empty($rating))
				$ratings = serialize($rating);
		}

		if (isset($comment_info->comment_parent) && (int)$comment_info->comment_parent == 0) {
			$overall_rating = $overall_rating > 0 ? $overall_rating : '0';
		} else {
			$overall_rating = '';
		}

		$attachments = '';
		$total_images = 0;

		if ( isset( $_POST['comment_images'] ) ) {
			$file_info = $_POST['comment_images'];

			$newArr = explode( '::', $file_info );
			$total_images = count( $newArr );
			$comment_images = geodir_reviewrating_add_remove_images( $comment, $newArr, $post_id );

			if ( ! empty( $comment_images ) ) {
				$attachments = implode( '::', $comment_images );
			}
		}

		if ( ! empty( $rating ) || $overall_rating || $attachments != '' ) {
			$ratings = isset($ratings) ? $ratings : '';
			global $plugin_prefix;
			$post_details = $wpdb->get_row("SELECT * FROM " . $plugin_prefix . $post_type . "_detail WHERE post_id =" . (int)$post->ID);
			$user_id = get_current_user_id();

			$get_ids = $wpdb->get_row("SELECT count(comment_id) as ids FROM " . GEODIR_REVIEWRATING_POSTREVIEW_TABLE . " WHERE comment_id =" . (int)$comment, ARRAY_A);

			$data = array(
				'comment_id' => $comment,
				'post_id' => $post->ID,
				'user_id' => $user_id,
				'rating' => $overall_rating,
				'ratings' => $ratings,
				'attachments' => $attachments,
				'post_type' => $post->post_type,
				'city' => ( isset( $post_details->city ) ? $post_details->city : '' ),
				'region' => ( isset( $post_details->region ) ? $post_details->region : '' ),
				'country' => ( isset( $post_details->country ) ? $post_details->country : '' ),
				'latitude' => ( isset( $post_details->latitude ) ? $post_details->latitude : '' ),
				'longitude' => ( isset( $post_details->longitude ) ? $post_details->longitude : '' ),
				'read_unread' => '',
				'total_images' => $total_images,
			);

			$format =array(
				'%d',
				'%d',
				'%d',
				'%f',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
			);

			if($get_ids['ids'] > 0){
				$wpdb->update(
					GEODIR_REVIEWRATING_POSTREVIEW_TABLE,
					$data,
					array( 'comment_id' => $comment ),
					$format
				);
			} else {
				$wpdb->insert(
					GEODIR_REVIEWRATING_POSTREVIEW_TABLE,
					$data,
					$format
				);
			}

			if (!empty($rating) || $overall_rating)
				geodir_reviewrating_update_postrating($post->ID, $rating, $overall_rating);
		}

		//update post average ratings
		geodir_reviewrating_update_post_ratings($post->ID);
	}

	/**
	 * Adds overall rating to the map marker info window.
	 *
	 * @since 1.0.0
	 * @since 1.3.6 Changes for disable review stars for certain post type.
	 * @package GeoDirectory_Review_Rating_Manager
	 *
	 * @param $rating_star
	 * @param float|int $avg_rating Average post rating.
	 * @param int $post_id The post ID.
	 * @return string
	 */
	function geodir_reviewrating_advance_stars_on_infowindow($rating_star, $avg_rating, $post_id) {
		if (!empty($post_id) && geodir_cpt_has_rating_disabled((int)$post_id)) {
			return $rating_star;
		}

		$rating_star  = geodir_reviewrating_draw_overall_rating($avg_rating);

		return $rating_star;
	}

	/**
	 * Diagnose review rating manager tables.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory_Review_Rating_Manager
	 *
	 * @param array $table_arr Diagnose table array.
	 * @return array Modified diagnose table array.
	 */
	function geodir_diagnose_multisite_conversion_review_manager($table_arr){
		// Diagnose Claim listing details table
		$table_arr['geodir_rating_style'] = __('Rating style', 'geodir_reviewratings');
		$table_arr['geodir_rating_category'] = __('Rating category', 'geodir_reviewratings');
		$table_arr['geodir_unassign_comment_images'] = __('Comment image', 'geodir_reviewratings');
		$table_arr['geodir_comments_reviews'] = __('Comment reviews', 'geodir_reviewratings');
		return $table_arr;
	}

	function geodir_reviewrating_comments_pagenum_link( $result ) {
		global $gd_filter_reviews;

		if ( $gd_filter_reviews && !empty( $_REQUEST['comment_sorting'] ) ) {
			$result = str_replace( '#comments', '#reviews', $result );
			$result = add_query_arg( 'comment_sorting', $_REQUEST['comment_sorting'], $result );
		}

		return $result;
	}

	/**
	 * Update comment attachments status on comment status change.
	 *
	 * @since 2.0.0.13
	 */
	public static function set_comment_attachments_status( $comment_id, $status ) {
		global $wpdb;

		if ( empty( $comment_id ) ) {
			return false;
		}

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM `" . GEODIR_ATTACHMENT_TABLE . "` WHERE `type` = 'comment_images' AND `other_id` = %d AND `is_approved` != %d", $comment_id, $status ) );

		$updated = 0;
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$_updated = $wpdb->update( GEODIR_ATTACHMENT_TABLE, array( 'is_approved' => $status, 'featured' => 0 ), array( 'ID' => $row->ID ), array( '%d' ) );

				if ( $_updated ) {
					$updated++;
				}
			}
		}

		return $updated;
	}

}
