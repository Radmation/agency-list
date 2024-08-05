<?php
/**
 * Contains functions for Review Rating Manager add on.
 *
 * @since 2.0.0
 * @package geodir_review_rating_manager
 */

/**
 * Get style by ID.
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $style_id Style ID.
 * @return array|mixed
 */
function geodir_get_style_by_id($style_id = '')
{
    global $wpdb;
    $styles = false;

    if($style_id){
        $get_styles = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM ".GEODIR_REVIEWRATING_STYLE_TABLE." WHERE id = %d",
                array($style_id)
            )
        );
        if(!empty($get_styles))
            $styles = $get_styles;
    }

    return $styles;
}

/**
 * Remove style and its data using ID.
 *
 * @since 2.0.0
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param int $style_id Style ID.
 * @return bool Returns true when successful deletion.
 */
function geodir_delete_style_by_id( $style_id ) {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) || !$style_id > 0 ) {
		return false;
	}

	do_action( 'geodir_before_delete_style', $style_id );

	$wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_REVIEWRATING_STYLE_TABLE . " WHERE id = %d", array( $style_id ) ) );

	do_action( 'geodir_after_delete_style', $style_id );

	return true;
}

/**
 * Get rating by ID.
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $rating_id Rating ID.
 * @return array|mixed
 */
function geodir_get_rating_by_id($rating_id = '')
{
    global $wpdb;
    $get_ratings = false;

    if($rating_id){
        $where = $wpdb->prepare(" AND rt.id = %d ", array($rating_id));

        $get_ratings = $wpdb->get_results("SELECT rt.id as id,
									rt.title as title,
									rt.post_type as post_type,
									rt.category as category,
									rt.category_id as category_id,
									rt.check_text_rating_cond as check_text_rating_cond,
									rt.display_order,
									rs.s_rating_type  as s_rating_type,
									rs.s_rating_icon  as s_rating_icon,	 
									rs.s_img_off  as s_img_off,
									rs.s_img_width as s_img_width,
									rs.s_img_height as s_img_height,
									rs.star_color as star_color,
									rs.star_color_off as star_color_off,
									rs.star_lables as star_lables,
									rs.star_number as star_number
									FROM ".GEODIR_REVIEWRATING_CATEGORY_TABLE." rt,".GEODIR_REVIEWRATING_STYLE_TABLE." rs
									WHERE rt.category_id = rs.id $where ORDER BY rt.display_order ASC, rt.id");
    }

    if(!empty($get_ratings) && $rating_id != '')
        return $get_ratings[0];
    elseif(!empty($get_ratings))
        return $get_ratings;
    else
        return false;
}

/**
 * Remove rating and its data using ID.
 *
 * @since 2.0.0
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param int $rating_id Rating ID.
 * @return bool Returns true when successful deletion.
 */
function geodir_delete_rating_by_id( $rating_id ) {
	global $wpdb;

	if ( !current_user_can( 'manage_options' ) || !$rating_id > 0 ) {
		return false;
	}

	do_action( 'geodir_before_delete_rating', $rating_id );

	$wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_REVIEWRATING_CATEGORY_TABLE . " WHERE id = %d", array( $rating_id ) ) );

	do_action( 'geodir_after_delete_rating', $rating_id );

	return true;
}


/**
 * Converts rating manager star labels to string.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param mixed $star_lables Rating star labels.
 * @param bool $translate Do you want to translate this label? Default: false.
 * @param string $separator Label separator.
 * @return array|mixed|null|string|void
 */
function geodir_reviewrating_star_lables_to_str( $star_lables, $translate = false , $separator = ',' ) {
	if ( empty( $star_lables ) ) {
		return NULL;
	}

	if ( is_serialized( $star_lables ) ) {
		$star_lables = maybe_unserialize( $star_lables );
	}

	if ( is_array( $star_lables ) ) {
		if ( $translate && !empty( $star_lables ) ) {
			$translated = array();

			foreach ( $star_lables as $lable ) {
				if ( $lable ) {
					$translated[] = __( stripslashes( $lable ), 'geodirectory' );
				}
			}

			$star_lables = $translated;
		}

		$star_lables = implode( $separator, $star_lables );
	} else {
		$star_lables = __( stripslashes_deep( $star_lables ), 'geodirectory' );
	}

	$star_lables = $star_lables != '' ? stripslashes_deep( $star_lables ) : $star_lables;

	return $star_lables;
}

/**
 * Admin ajax url for review rating manager.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @return string|void
 */
function geodir_reviewrating_ajax_url(){
    return admin_url('admin-ajax.php?action=geodir_reviewrating_ajax');
}

/**
 * Rating manager create ratings tab - 'Select multirating style' dropdown html.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $style_id Rating style ID.
 *
 * @return array $values Options
 */
function geodir_review_rating_style_dl($style_id = '') {
    global $wpdb;

    $select_styles = $wpdb->get_results("SELECT * FROM " . GEODIR_REVIEWRATING_STYLE_TABLE);

    if (empty($style_id)) {
        if ($wpdb->get_var("SHOW COLUMNS FROM " . GEODIR_REVIEWRATING_STYLE_TABLE . " WHERE field = 'is_default'")) {
            $style_id = $wpdb->get_var("SELECT id FROM " . GEODIR_REVIEWRATING_STYLE_TABLE . " WHERE is_default='1'");
        }
    }

    $values = array(
        0 => __('-- Select multirating style --', 'geodir_reviewratings')
    );

    foreach ($select_styles as $select_style) {
        $values[$select_style->id] = __(stripslashes($select_style->name), 'geodir_reviewratings');
    }

    return $values;
}

/**
 * Rating manager serialize star labels.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param mixed $star_lables Rating star labels.
 * @return mixed
 */
function geodir_reviewrating_serialize_star_lables( $star_lables ) {
    if ( empty( $star_lables ) ) {
        return $star_lables;
    }

    $star_lables = maybe_serialize( $star_lables );

    return $star_lables;
}

/**
 * Converts rating manager star labels to array.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param mixed $star_lables Rating star labels.
 * @param int $max_rating Max rating value.
 * @param bool $translate Do you want to translate this label? Default: false.
 * @return array|mixed
 */
function geodir_reviewrating_star_lables_to_arr( $star_lables, $max_rating = 0, $translate = false ) {
    if ( $star_lables == '' ) {
        $star_lables = GeoDir_Comments::rating_texts_default();
        return apply_filters( 'geodir_reviewrating_default_labels', $star_lables, $translate );
    }

    if ( is_serialized( $star_lables ) ) {
        $star_lables = maybe_unserialize( $star_lables );
    } else {
        $star_lables = explode( ',', $star_lables );
    }

    if ( $translate && !empty( $star_lables ) ) {
        $translated = array();
        $count = 0;
        foreach ( $star_lables as $lable ) {
            $count++;
            $translated[] = __( stripslashes_deep( $lable ), 'geodirectory' );

            if ( absint( $max_rating ) > 0 && $count >= absint( $max_rating ) ) {
                break;
            }
        }

        $star_lables = $translated;
    }

    array_unshift($star_lables,"");

    unset($star_lables[0]);

    return apply_filters( 'geodir_reviewrating_labels', $star_lables, $translate );
}

/**
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param int $comment_id The comment ID.
 * @return array|bool|mixed
 */
function geodir_reviewrating_get_comment_rating_by_id($comment_id = 0){

    global $wpdb;
    $ratings = array();

    $ratings = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT rating,ratings FROM ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." WHERE comment_id = %d",
            array($comment_id)
        )
    );

    if(!empty($ratings))
        return $ratings;
    else
        return false;
}

/**
 * Review Rating module delete images function
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param int $comment_id The comment ID.
 */
function geodir_reviewrating_delete_comment_images( $comment_id ) {
	global $wpdb;

	if ( empty( $comment_id ) ) {
		return;
	}

	$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . GEODIR_ATTACHMENT_TABLE . "` WHERE other_id = %d AND type = %s", array( $comment_id, 'comment_images' ) ) );

	if ( ! empty( $results ) ) {
		foreach ( $results as $row ) {
			GeoDir_Media::delete_attachment( $row->ID, $row->post_id, $row );
		}
	}
}

/**
 * Get overall rating of a comment.
 *
 * Returns overall rating of a comment. If no results, returns false.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @param int $comment_id The comment ID.
 * @global object $wpdb WordPress Database object.
 * @return bool|null|string
 */
function geodir_get_commentoverall($comment_id = 0)
{
    global $wpdb;

    $reatings = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT rating FROM " . GEODIR_REVIEWRATING_POSTREVIEW_TABLE . " WHERE comment_id = %d",
            array($comment_id)
        )
    );

    if ($reatings)
        return $reatings;
    else
        return false;
}

/**
 * Rating manager update ratings for a Post.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param int $post_id The post ID.
 */
function geodir_reviewrating_update_post_ratings( $post_id ) {
    global $wpdb;

    $post_type = get_post_type( $post_id );
    $detail_table = geodir_db_cpt_table( $post_type );

    $post_ratings = $wpdb->get_results( $wpdb->prepare( "SELECT r.ratings FROM " . GEODIR_REVIEW_TABLE . " AS r LEFT JOIN {$wpdb->comments} AS c ON c.comment_ID = r.comment_id WHERE r.post_id = %d AND c.comment_approved = '1' AND r.rating > 0", array( $post_id ) ) );

    $post_comments_rating = array();

    if ( ! empty( $post_ratings ) ) {
        $optional_multirating = apply_filters( 'geodir_reviewrating_comment_rating_fields', 0, $post_id ); // Allow review without rating star for multirating

        foreach ( $post_ratings as $rating ) {
            $ratings = maybe_unserialize( $rating->ratings );

            if ( ! empty( $ratings ) && is_array( $ratings ) ) {
                foreach ( $ratings as $rating_id => $rating_value ) {
                    if ( ! empty( $post_comments_rating ) && array_key_exists( $rating_id, $post_comments_rating ) ) {
                        $rating_count = (int) $post_comments_rating[ $rating_id ]['c'];
                        if ( ! $optional_multirating || (float) $rating_value > 0 ) {
                            $rating_count ++;
                        }

                        $_rating_value = (float) $post_comments_rating[$rating_id]['r']  + (float) $rating_value;
                    } else {
                        $rating_count = 0;
                        if ( ! $optional_multirating || (float) $rating_value > 0 ) {
                            $rating_count ++;
                        }

                        $_rating_value = (float) $rating_value;
                    }

                    $post_comments_rating[ $rating_id ]['c'] = $rating_count;
                    $post_comments_rating[ $rating_id ]['r'] = $_rating_value;
                }
            }
        }
    }

    if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $detail_table . "'" ) == $detail_table ) {
        $wpdb->query( $wpdb->prepare( "UPDATE " . $detail_table . " SET ratings  = %s where post_id = %d", array( maybe_serialize( $post_comments_rating ), $post_id ) ) );
    } else {
        update_post_meta( $post_id, 'ratings ', maybe_serialize( $post_comments_rating ) );
    }
}

/**
 * Review manager get rating by post ID.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param int $post_id The post ID.
 * @return array|bool
 */
function geodir_reviewrating_get_post_rating($post_id) {
    global $wpdb, $plugin_prefix;

    $post_type = get_post_type( $post_id );
    $detail_table = $plugin_prefix . $post_type . '_detail';

    $ratings = array();
    $overall = 	geodir_get_post_rating($post_id);

    if ($wpdb->get_var("SHOW TABLES LIKE '".$detail_table."'") == $detail_table) {
        $sql = $wpdb->prepare(
            "SELECT ratings, rating_count, overall_rating FROM ".$detail_table." WHERE post_id = %d",
            array($post_id)
        );
        $post_ratings = $wpdb->get_row($sql);

        if (!empty($post_ratings) && $post_ratings->rating_count>0 ) {
            $old_rating = @unserialize($post_ratings->ratings);
        }

    } else {
        $old_rating_val = get_post_meta( $post_id, 'ratings');
        if(isset($post_id) && is_array($old_rating_val)){$old_rating = end($old_rating_val);}else{$old_rating =array();}
        $overall_val = get_post_meta( $post_id, 'overall_rating');
        if(isset($post_id) && is_array($overall_val)){$overall= end($overall_val);}else{$overall =array();}
    }

    if (!empty($old_rating)) {
        foreach ($old_rating as $key=>$value) {
            $ratings[$key] = $value;
        }
    }

    if (isset($overall) && $overall != '') {
        $ratings['overall'] = $overall;
    }

    if(!empty($ratings))
        return $ratings;
    else
        return false;
}

/**
 * Get the element in the WPML current language.
 *
 * @since 1.6.22
 *
 * @param int         $element_id                 Use term_id for taxonomies, post_id for posts
 * @param string      $element_type               Use post, page, {custom post type name}, nav_menu, nav_menu_item, category, tag, etc.
 *                                                You can also pass 'any', to let WPML guess the type, but this will only work for posts.
 * @param bool        $return_original_if_missing Optional, default is FALSE. If set to true it will always return a value (the original value, if translation is missing).
 * @param string|NULL $language_code              Optional, default is NULL. If missing, it will use the current language.
 *                                                If set to a language code, it will return a translation for that language code or
 *                                                the original if the translation is missing and $return_original_if_missing is set to TRUE.
 *
 * @return int|NULL
 */
function geodir_wpml_object_id( $element_id, $element_type = 'post', $return_original_if_missing = false, $ulanguage_code = null ) {
	if ( class_exists('SitePress') ) {
		if ( function_exists( 'wpml_object_id_filter' ) ) {
			return apply_filters( 'wpml_object_id', $element_id, $element_type, $return_original_if_missing, $ulanguage_code );
		} else {
			return icl_object_id( $element_id, $element_type, $return_original_if_missing, $ulanguage_code );
		}
	}

	return $element_id;
}

/**
 * Get review manager, rating categories.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param string $cat_id
 * @return bool|mixed
 */
function geodir_reviewrating_rating_categories( $cat_id = '' ) {
	global $wpdb;

	$where = '';
	if ( $cat_id != '' ) {
		$where = $wpdb->prepare( " AND rt.id = %d ", array( $cat_id ) );
	}

	$results = $wpdb->get_results( "SELECT rt.id as id,
									rt.title as title,
									rt.post_type as post_type,
									rt.category as category,
									rt.category_id as category_id,
									rt.check_text_rating_cond as check_text_rating_cond,
									rt.display_order,
									rs.s_rating_type  as s_rating_type,
									rs.s_rating_icon  as s_rating_icon,	 
									rs.s_img_off  as s_img_off,
									rs.s_img_width as s_img_width,
									rs.s_img_height as s_img_height,
									rs.star_color as star_color,
									rs.star_color_off as star_color_off,
									rs.star_lables as star_lables,
									rs.star_number as star_number
									FROM ".GEODIR_REVIEWRATING_CATEGORY_TABLE." rt,".GEODIR_REVIEWRATING_STYLE_TABLE." rs
									WHERE rt.category_id = rs.id $where ORDER BY rt.display_order ASC, rt.id" );

	if ( ! empty( $results ) && $cat_id != '' )
		return $results[0];
	elseif ( ! empty( $results ) )
		return $results;
	else
		return false;
}

/**
 * Set default style.
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param int $style_id Style ID.
 *
 * @return bool
 */
function geodir_set_default_style($style_id){
    global $wpdb;

	if ( ! absint( $style_id ) > 0 ) {
		return false;
	}

	$wpdb->query( "UPDATE " . GEODIR_REVIEWRATING_STYLE_TABLE . " SET is_default = '0'" );
	$wpdb->query( $wpdb->prepare( "UPDATE " . GEODIR_REVIEWRATING_STYLE_TABLE . " SET is_default = '1' WHERE id = %d", array( $style_id ) ) );
	
	return true;
}

/**
 * Review manager Get comments.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param array|string $default Comment filter array.
 * @return mixed
 */
function geodir_reviewrating_get_comments($default=''){
	global $wpdb,$paged, $show_post;

	$condition = '';
	$orderby = '';
	$value_order = '';
	$value_field = '';
	$limit = '';

	$search_array = array();
	foreach($default as $key=>$value)
	{
		if($key == 'comment_approved')
		{
			switch($value):
				case 'approved':
					$condition .= " AND wpc.comment_approved = '1' ";
				break;
				case 'pending':
					$condition .= " AND wpc.comment_approved = '0' ";
				break;
				case 'trash':
				case 'spam':
					$condition .= " AND wpc.comment_approved = '{$value}' ";
				break;
				default:
					$condition .= " AND wpc.comment_approved != 'spam' AND wpc.comment_approved != 'trash' ";
			endswitch;
		}
		elseif($key == 'orderby' || $key == 'order' )
		{
			if($key == 'orderby')
			{
				$value_field = $value;
			}
			else
			{
				$value_order = $value;
			}

			$orderby = " ORDER BY wpc.{$value_field} {$value_order} ";

			if($value_field == 'rating')
			{
				$orderby = " ORDER BY gdc.{$value_field} {$value_order} ";
			}

		}
		elseif($value != '' && $key == 'post_type')
		{
			$condition .= " AND gdc.{$key} = '{$value}' ";
		}
		elseif($value != '' && $key == 'search' )
		{
			$condition .= " AND (gdc.post_title LIKE %s || wpc.comment_author LIKE %s || wpc.comment_content LIKE %s ) ";

			$search_array = array('%'.$value.'%','%'.$value.'%','%'.$value.'%');

		}
		elseif($value != '' && $key == 'paged')
		{
			$paged = $value;
			$start = $value;
		}
		elseif($value != '' && $key == 'show_post')
		{
			$show_post = $value;
		}
	}

	if($condition == '')
	{
		$condition .= " AND wpc.comment_approved != 'spam' AND wpc.comment_approved != 'trash' ";
	}

	if($start != '' && $show_post != '')
	{
		if($start > 0)
		{

			$start = ($start-1)*$show_post;

			$limit = "LIMIT $start, $show_post";
		}
		else
		{
			$limit = "LIMIT $start, $show_post";
		}
	}

	if(!empty($search_array)) {
		$array['comment_count'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(wpc.comment_ID) FROM $wpdb->comments wpc, ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." gdc WHERE wpc.comment_ID = gdc.comment_id ".$condition.$orderby, $search_array));

		$array['comments'] = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments wpc, ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." gdc WHERE wpc.comment_ID = gdc.comment_id ".$condition.$orderby.$limit, $search_array));
	}else{

		$array['comment_count'] = $wpdb->get_var("SELECT COUNT(wpc.comment_ID) FROM $wpdb->comments wpc, ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." gdc WHERE wpc.comment_ID = gdc.comment_id ".$condition.$orderby);

		$array['comments'] = $wpdb->get_results("SELECT * FROM $wpdb->comments wpc, ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." gdc WHERE wpc.comment_ID = gdc.comment_id ".$condition.$orderby.$limit);

	}

	return $array;

}

/**
 * Review Rating comment ajax actions.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param $request
 * @return bool
 */
function geodir_reviewrating_comment_action( $request ) {
		global $wpdb;

		if ( isset( $request['comment_ids'] ) && $request['comment_ids'] != '' ) {
			$comment_ids = explode(',', $request['comment_ids']);
		} else {
			$comment_ids = array();
		}

		if ( ! empty( $comment_ids ) && $request['comment_ids'] != '' ) {
			if ( ! wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'geodir_review_action_nonce' ) ) {
				return false;
			}

			foreach( $comment_ids as $comment_id ) {
				$comment_id = absint( $comment_id );

				if ( $comment_id != '' ) {
					switch ( $request['comment_action'] ) {
						case 'deletecomment' :
							wp_delete_comment( $comment_id );
							break;
						case 'trashcomment' :
							wp_trash_comment( $comment_id );
							break;
						case 'untrashcomment' :
							wp_untrash_comment( $comment_id );
							break;
						case 'spamcomment' :
							wp_spam_comment( $comment_id );
							break;
						case 'unspamcomment' :
							wp_unspam_comment( $comment_id );
							break;
						case 'approvecomment' :
							wp_set_comment_status( $comment_id, 'approve' );
							break;
						case 'unapprovecomment' :
							wp_set_comment_status( $comment_id, 'hold' );
							break;
					}
				}
			}

			if ( isset( $request['geodir_comment_search'] ) )
				$geodir_commentsearch = sanitize_text_field( $request['geodir_comment_search'] );

			if ( isset( $request['geodir_comment_posttype'] ) )
				$post_type = sanitize_text_field( $request['geodir_comment_posttype'] );

			$status = $request['subtab'];

			$orderby = 'comment_date_gmt';
			$order = 'DESC';

			if ( isset( $request['geodir_comment_sort'] ) ) {
				if ( $request['geodir_comment_sort'] == 'oldest' ) {
					$orderby = 'comment_date_gmt';
					$order = 'ASC';
				}
			}

			if ( isset( $request['paged'] ) && $request['paged'] != '' ) {
				$paged = sanitize_text_field( $request['paged'] );
			} else {
				$paged = 1;
			}

			$show_post = sanitize_text_field( $request['show_post'] );

			$defaults = array(
				'paged' => $paged,
				'show_post' => $show_post,
				'orderby' => $orderby,
				'order' => $order,
				'post_type' => $post_type,
				'comment_approved' => $status,
				'user_id' => '',
				'search' => $geodir_commentsearch,
			);

			$comments = geodir_reviewrating_get_comments( $defaults );

			GeoDir_Review_Rating_Template::geodir_reviewrating_show_comments( $comments['comments'] );
		}

		exit;
}

/**
 * Review manager Get comments count.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param string $status The comment status.
 * @return null|string
 */
function geodir_reviewrating_get_comments_count($status = ''){

	global $wpdb;

	switch($status):

		case 'approved':
			$status = " AND wpc.comment_approved = '1' ";
		break;

		case 'pending':
			$status = " AND wpc.comment_approved = '0' ";
		break;

		case 'trash':
		case 'spam':
			$status = $wpdb->prepare(" AND wpc.comment_approved = %s ", array($status));
		break;

		default:
			$status = " AND wpc.comment_approved != 'spam' AND wpc.comment_approved != 'trash' ";

	endswitch;

	$geodir_review_count = $wpdb->get_var("SELECT COUNT(wpc.comment_ID) 
						FROM $wpdb->comments wpc, ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." gdc
						WHERE wpc.comment_ID = gdc.comment_id ".$status);

	return $geodir_review_count;
}

/**
 * Rating manager delete comment images by url.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @return bool
 */
function geodir_reviewrating_delete_comment_images_by_url(){

	if(current_user_can( 'manage_options' )){

		if( isset($_REQUEST['ajax_action'])  && $_REQUEST['ajax_action']=='remove_images_by_url' && isset($_REQUEST['_wpnonce'])) {

				if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'del_img_'.$_REQUEST['remove_image_id'] ) )
					return false;

				global $wpdb;

				$remove_image_id = $_REQUEST['remove_image_id'];

				$img_attach_id = $_REQUEST['attach_id'];

				$comment_imges = $wpdb->get_var($wpdb->prepare("SELECT attachments FROM ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." WHERE comment_id = %d",array($remove_image_id)));

				$del_images = explode(',',$comment_imges);
				$total_images = count($del_images);

				if(($key = array_search($img_attach_id, $del_images)) !== false) {
					unset($del_images[$key]);
					$total_images = $total_images-1;

					wp_delete_attachment($img_attach_id);
				}

				$del_images = implode(',', $del_images);

				$wpdb->query($wpdb->prepare("UPDATE ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE ."
							SET attachments = %s,
							total_images = %d
							WHERE comment_id = %d",
							array($del_images, $total_images, $remove_image_id)
						 ));

				echo $total_images;exit;
			}

	}else{

		wp_redirect(geodir_login_url());
		exit();

	}

}

/**
 * Get review total of a Post.
 *
 * Returns review total of a post. If no results returns false.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @param int $post_id The post ID.
 * @global object $wpdb WordPress Database object.
 * @return bool|null|string
 */
function geodir_get_review_total($post_id = 0)
{
    global $wpdb;

    $results = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(rating) FROM " . GEODIR_REVIEWRATING_POSTREVIEW_TABLE . " WHERE post_id = %d AND rating > 0",
            array($post_id)
        )
    );

    if (!empty($results))
        return $results;
    else
        return false;
}

/**
 * Update post overall rating and rating count.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @param int $post_id The post ID.
 * @param string $post_type The post type.
 * @param bool $delete Depreciated since ver 1.3.6.
 */
function geodir_update_postrating($post_id = 0, $post_type = '', $delete = false) {
    GeoDir_Comments::update_post_rating( $post_id, $post_type, $delete );
}

/**
 * Checks if a given taxonomy is currently translated.
 *
 * @since 1.6.22
 *
 * @param string $taxonomy name/slug of a taxonomy.
 * @return bool true if the taxonomy is currently set to being translatable in WPML.
 */
function geodir_wpml_is_taxonomy_translated( $taxonomy ) {
	if ( empty( $taxonomy ) || !class_exists('SitePress') || !function_exists( 'is_taxonomy_translated' ) ) {
		return false;
	}

	if ( is_taxonomy_translated( $taxonomy ) ) {
		return true;
	}

	return false;
}

/**
 * WP media large width.
 *
 * @since   1.0.0
 * @package GeoDirectory
 *
 * @param int $default         Default width.
 * @param string|array $params Image parameters.
 *
 * @return int Large size width.
 */
function geodir_media_image_large_width( $default = 800, $params = '' ) {
	$large_size_w = get_option( 'large_size_w' );
	$large_size_w = $large_size_w > 0 ? $large_size_w : $default;
	$large_size_w = absint( $large_size_w );

	if ( ! get_option( 'geodir_use_wp_media_large_size' ) ) {
		$large_size_w = 800;
	}

	/**
	 * Filter large image width.
	 *
	 * @since 1.0.0
	 *
	 * @param int $large_size_w    Large image width.
	 * @param int $default         Default width.
	 * @param string|array $params Image parameters.
	 */
	$large_size_w = apply_filters( 'geodir_filter_media_image_large_width', $large_size_w, $default, $params );

	return $large_size_w;
}

/**
 * WP media large height.
 *
 * @since   1.0.0
 * @package GeoDirectory
 *
 * @param int $default   Default height.
 * @param string $params Image parameters.
 *
 * @return int Large size height.
 */
function geodir_media_image_large_height( $default = 800, $params = '' ) {
	$large_size_h = get_option( 'large_size_h' );
	$large_size_h = $large_size_h > 0 ? $large_size_h : $default;
	$large_size_h = absint( $large_size_h );

	if ( ! get_option( 'geodir_use_wp_media_large_size' ) ) {
		$large_size_h = 800;
	}

	/**
	 * Filter large image height.
	 *
	 * @since 1.0.0
	 *
	 * @param int $large_size_h    Large image height.
	 * @param int $default         Default height.
	 * @param string|array $params Image parameters.
	 */
	$large_size_h = apply_filters( 'geodir_filter_media_image_large_height', $large_size_h, $default, $params );

	return $large_size_h;
}

/**
 * Add or remove images for a comment.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param object $comment The comment id.
 * @param array $newArr Images array.
 * @return array Comment attachment ids.
 */
function geodir_reviewrating_add_remove_images( $comment_id, $files, $post_id ) {
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/image.php' );
	}

	$current_files  = GeoDir_Media::get_field_edit_string( $post_id, 'comment_images', '', $comment_id );

	if ( $current_files == $files ) {
		return false;
	}

	$comment_images = array();
	$field = 'comment_images';
	$files = array_filter( $files );
	if ( empty( $files ) && ! empty( $current_files ) ) {
		$current_files_arr = explode( "::", $current_files );

		foreach ( $current_files_arr as $current_file ) {
			$current_file_arr = explode( "|", $current_file );
			if ( isset( $current_file_arr[1] ) && $current_file_arr[1] ) {
				GeoDir_Media::delete_attachment( $current_file_arr[1], $post_id );
			}
		}
	}

	$comment = get_comment( $comment_id );
	$file_ids = array();

	foreach ( $files as $order => $file_string ) {
		$file_info = array();
		// Check if the string contains more info
		if ( strpos( $file_string, '|' ) !== false ) {
			$file_info = explode( "|", $file_string );
		} else {
			$file_info[0] = $file_string;
		}

		/*
		 * $file_info[0] = file_url;
		 * $file_info[1] = file_id;
		 * $file_info[2] = file_title;
		 * $file_info[3] = file_caption;
		 */
		$file_url     = isset( $file_info[0] ) ? sanitize_text_field( $file_info[0] ) : '';
		$file_id      = ! empty( $file_info[1] ) ? absint( $file_info[1] ) : '';
		$file_title   = ! empty( $file_info[2] ) ? sanitize_text_field( $file_info[2] ) : '';
		$file_caption = ! empty( $file_info[3] ) ? sanitize_text_field( $file_info[3] ) : '';
		$approved     = $comment->comment_approved == '1' || $comment->comment_approved == 'approve' ? 1 : 0;

		// Check if we already have the file.
		if ( $file_url && $file_id ) { // we already have the image so just update the title, caption and order id
			// Update the image
			$file        = GeoDir_Media::update_attachment( $file_id, $comment->comment_post_ID, $field, $file_url, $file_title, $file_caption, ( $order + 1 ), $approved, $comment_id );
			$file_ids[] = $file_id;
		} else { // its a new image we have to insert.
			// Insert the image
			$file = GeoDir_Media::insert_attachment( $comment->comment_post_ID, $field , $file_url, $file_title, $file_caption, ( $order + 1 ), $approved, false, $comment_id );
		}

		// Check for error
		if ( is_wp_error( $file ) ) {
			// Fail silently so the rest of the post data can be inserted
		} else {
			$comment_images[] = $file['ID'];
		}
	}

	// Check if there are any missing file ids we need to delete
	if ( ! empty( $current_files ) && ! empty( $files ) && ! empty( $file_ids ) ) {
		$current_files_arr = explode( "::", $current_files );

		foreach ( $current_files_arr as $current_file ) {
			$current_file_arr = explode( "|", $current_file );
			if ( isset( $current_file_arr[1] ) && $current_file_arr[1] && ! in_array( $current_file_arr[1], $file_ids ) ) {
				GeoDir_Media::delete_attachment( $current_file_arr[1], $post_id );
			}
		}
	}

	return $comment_images;
}

/**
 * Review manager update rating for a post.
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param int $post_id The post ID.
 * @param array $ratings The rating information.
 * @param float $overall overall rating.
 */
function geodir_reviewrating_update_postrating( $post_id, $ratings, $overall ) {
	geodir_reviewrating_update_postrating_all( $post_id );
	return; // DISABLED FOR NOW, WE WILL JUST CALL AN OVERAL UPDATE FUNCTION ON COMMENT SAVE. geodir_reviewrating_update_postrating_all

	global $wpdb, $plugin_prefix;

	$post = get_post( $post_id );

	$post_ratings = array();
	$post_ratings = geodir_reviewrating_get_post_rating( $post_id );
	$old_ratings = $post_ratings;
	$new_ratings = array();

	if ( ! empty( $ratings ) ) {
		$r_count = count( $ratings );

		foreach ( $ratings as $rating_id => $rating_value ) {
			$rating_info = geodir_reviewrating_rating_categories( $rating_id );

			if ( ! empty( $post_ratings ) && array_key_exists( $rating_id, $old_ratings ) ) {
				$new_rating_value = (float) $old_ratings[ $rating_id ]['r'] + (float) $rating_value;
				$new_ratings[ $rating_id ]['c'] = $new_rating_value;
				$new_ratings[ $rating_id ]['r'] = (float) $old_ratings[ $rating_id ]['c'] + 1;
			} else{
				$new_ratings[ $rating_id ]['c'] = (float) $r_count;
				$new_ratings[ $rating_id ]['r'] = (float) $rating_value;
			}
		}
	}

	// Update rating
	geodir_update_postrating( $post_id, $post->post_type );
}

/**
 *
 * @since 1.0.0
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param int $post_id The post ID.
 */
function geodir_reviewrating_update_postrating_all($post_id = 0){


	global $wpdb,$plugin_prefix;

	$post = get_post( $post_id );

	$post_ratings = array();

    $post_ratings = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ratings FROM ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." WHERE post_id = %d AND ratings != ''",
            array($post_id)
        )
    );

    $post_comments_rating = array();
    $new_ratings = array();

    if(!empty($post_ratings)){
    $r_count=count($post_ratings);
        foreach($post_ratings as $rating){

            $ratings = unserialize($rating->ratings);

            foreach($ratings as $rating_id=>$rating_value){

                if( !empty($post_comments_rating) && array_key_exists($rating_id,$post_comments_rating) ){

                    $new_rating_value = (float)$post_comments_rating[$rating_id]['r'] + (float)$rating_value;
                    $post_comments_rating[$rating_id]['c'] = $r_count;
                    $post_comments_rating[$rating_id]['r'] = $new_rating_value;

                }else{
                    $post_comments_rating[$rating_id]['c'] = (float)$r_count;
                    $post_comments_rating[$rating_id]['r'] = (float)$rating_value;
                }

            }

        }

    }
    if($post_comments_rating){$new_ratings = $post_comments_rating;}

	//update rating
	geodir_update_postrating($post_id,$post->post_type);
}

/**
 * over all rating.
 *
 * @since 1.0.0
 * @since 1.3.5 Don't draw rating when rating disabled.
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param float|int $rating Average post rating.
 * @return string
 */
function geodir_reviewrating_draw_overall_rating($rating) {
    $overall_rating =  GeoDir_Comments::rating_html($rating, 'output');
    return apply_filters('geodir_reviewrating_draw_overall_rating_html', $overall_rating, $rating);
}