<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDirectory GeoDir_Location_AJAX.
 *
 * AJAX Event Handler.
 *
 * @class    GeoDir_Review_Rating_AJAX
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Review_Rating_AJAX{

    /**
     * Hook in ajax handlers.
     */
    public function __construct() {
        $this->add_ajax_events();
        $this->geodir_review_rating_ajax_actions();
    }

    /**
     * Hook in methods - uses WordPress ajax handlers (admin-ajax).
     */
    public function add_ajax_events(){
        $ajax_events = array(
            'ajax_save_style' => false,
            'ajax_save_rating' => false,
            'ajax_tax_cat' => false,
            'ajax_set_default_style' => false,
            'reviewrating_ajax' => true,
        );

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}

			// GeoDir AJAX can be used for frontend ajax requests.
			add_action( 'geodir_ajax_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
    }


    public static function ajax_save_style() {
        global $wpdb;

        check_ajax_referer( 'geodir-save-style', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1 );
        }

        try{
            $style_id = ! empty( $_REQUEST['style_id'] ) ? absint( $_REQUEST['style_id'] ) : 0;

            $multi_rating_category = sanitize_text_field( stripslashes( $_REQUEST['multi_rating_category'] ) );
            $style_count = ! empty( $_REQUEST['style_count'] ) ? (int) $_REQUEST['style_count'] : '';
            $star_color = sanitize_text_field( stripslashes( $_REQUEST['star_color'] ) );
            $star_color_off = sanitize_text_field( stripslashes( $_REQUEST['star_color_off'] ) );
            $type = sanitize_text_field( stripslashes( $_REQUEST['s_rating_type'] ) );
            $icon = sanitize_text_field( $_REQUEST['s_rating_icon'] );

            $star_rating_text = $_REQUEST['star_rating_text'];

            if ( count( $star_rating_text ) > 0 ) {
                $star_rating_text = array_map( 'wp_unslash', $star_rating_text );
                $star_rating_text = array_map( 'sanitize_text_field', $star_rating_text );
                $star_rating_text_value = geodir_reviewrating_serialize_star_lables( $star_rating_text );
            } else {
                $star_rating_text_value = '';
            }

            $set_query = $wpdb->prepare("SET name = %s, s_rating_type = %s, s_rating_icon = %s, star_lables = %s, star_number = %s , star_color = %s, star_color_off = %s ", array($multi_rating_category, $type, $icon, $star_rating_text_value, $style_count, $star_color, $star_color_off));

            if (isset($_REQUEST['s_file_off']) && !empty($_REQUEST['s_file_off'])) {

                $s_file_off = (int)$_REQUEST['s_file_off'];

                $media = wp_get_attachment_metadata( $s_file_off);
                $media_w = $media['width'];
                $media_h = $media['height'];

                if (!empty($media_w)) {
                    $set_query .= $wpdb->prepare(", s_img_width = %s ", array($media_w));
                }
                if (!empty($media_h)) {
                    $set_query .= $wpdb->prepare(", s_img_height = %s ", array($media_h));
                }
                $set_query .= $wpdb->prepare(", s_img_off = %s ", array($s_file_off));
            }

            $url = admin_url('admin.php?page=gd-settings&tab=review_rating&section=styles');
            $message = '';

            if ( $style_id > 0 ) {
                $style = geodir_get_style_by_id( $style_id );
                if($style){
                    $saved = $wpdb->query($wpdb->prepare("UPDATE " . GEODIR_REVIEWRATING_STYLE_TABLE . " {$set_query} WHERE id = %d ", array( absint( $_REQUEST['style_id'] ) ) ) );
                } else {
                    $message = __( 'Style already exists!', 'geodir_reviewratings' );
                }
            } else {
                $saved = $wpdb->query("INSERT INTO " . GEODIR_REVIEWRATING_STYLE_TABLE . " {$set_query} ");
                $style_id = $wpdb->insert_id;
            }

            $style = geodir_get_style_by_id( $style_id );

            $data = array( 'style' => $style, 'url' => $url );
            wp_send_json_success( $data );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    public static function ajax_save_rating() {
        global $wpdb;

        check_ajax_referer( 'geodir-save-rating', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1 );
        }

        try{
            $rating_id = ! empty( $_REQUEST['rating_id'] ) ? absint( $_REQUEST['rating_id'] ) : 0;

            $title = sanitize_text_field( wp_unslash( $_REQUEST['rating_title'] ) );

            $post_type = $cat_arr = array();

            if(isset($_REQUEST['number_of_post']) && $_REQUEST['number_of_post']!='')
            {
                for($j=1;$j<=$_REQUEST['number_of_post'];$j++){

                    if(isset($_REQUEST['post_type'.$j]) && $_REQUEST['post_type'.$j]!=''){

                        $post_type[] = sanitize_text_field( $_REQUEST['post_type'.$j] );

                        if(isset($_REQUEST['categories_type_'.$j]) && $_REQUEST['categories_type_'.$j]!=''){

                            foreach($_REQUEST['categories_type_'.$j] as $value){
                                $cat_arr[] = absint( $value );
                            }
                        }
                    }
                }
            }

            $categories = '';

            if(count($cat_arr)>0)
                $categories = implode(',',$cat_arr);

            if(count($post_type)>0)
                $post_type = implode(',',$post_type);

            $geodir_rating_style_dl = absint( $_REQUEST['geodir_rating_style_dl'] );
            $show_text_star_count = absint( $_REQUEST['show_star'] );
            $display_order = isset( $_REQUEST['display_order'] ) ? absint( $_REQUEST['display_order'] ) : 0;
            $url = admin_url('admin.php?page=gd-settings&tab=review_rating&section=ratings');

            if ( $rating_id > 0 ) {
                $sqlqry = $wpdb->prepare(
                    "UPDATE ".GEODIR_REVIEWRATING_CATEGORY_TABLE." SET 
								title		= %s,
								post_type 	= %s,
								category	= %s,
								category_id = %s,
								check_text_rating_cond = %s,
								display_order = %d
								WHERE id = %d",
                    array($title,$post_type,$categories,$geodir_rating_style_dl,$show_text_star_count, $display_order,$rating_id)
                );

                $wpdb->query($sqlqry);

            } else {
                $sqlqry = $wpdb->prepare(
                    "INSERT INTO ".GEODIR_REVIEWRATING_CATEGORY_TABLE." SET
								title		= %s,
								post_type 	= %s,
								category	= %s,
								category_id = %s,
								check_text_rating_cond = %s,
								display_order = %d",
                    array($title,$post_type,$categories,$geodir_rating_style_dl,$show_text_star_count, $display_order)
                );

                $wpdb->query($sqlqry);
                $rating_id = $wpdb->insert_id;
            }

            $rating = geodir_get_rating_by_id( $rating_id );

            $data = array( 'rating' => $rating, 'url' => $url );
            wp_send_json_success( $data );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    public static function ajax_tax_cat(){
        if(isset($_REQUEST['post_type'])){
            global $cat_display;
            $cat_display = 'select';
            if (class_exists('SitePress')) {
                global $sitepress;
                $sitepress->switch_lang('all', true);
            }

            echo geodir_custom_taxonomy_walker( sanitize_text_field( $_REQUEST['post_type'] ) . 'category');

            if (class_exists('SitePress')) {
                global $sitepress;
                $active_lang = ICL_LANGUAGE_CODE;
                $sitepress->switch_lang($active_lang, true);
            }
        }
    }

    public static function ajax_set_default_style(){
        $style_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        check_ajax_referer( 'geodir-set-default-' . $style_id, 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1 );
        }

        try {
            $style = $style_id ? geodir_get_style_by_id( $style_id ) : NULL;
            if ( empty( $style ) ) {
                throw new Exception( __( 'Requested style does not exists!', 'geodir_reviewratings' ) );
            }

            geodir_set_default_style( $style_id );

            $message = __( 'Default style set successfully.', 'geodir_reviewratings' );

            $data = array( 'message' => $message );
            wp_send_json_success( $data );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

	public function geodir_review_rating_ajax_actions() {
		global $wpdb;

		if ( isset( $_REQUEST['ajax_action'] ) && $_REQUEST['ajax_action'] == 'delete_rating_category' ) {
			if ( ! wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'geodir_delete_rating_' . absint( $_REQUEST['rating_cat_id'] ) ) ){
				die();
			}

			$wpdb->query($wpdb->prepare("DELETE FROM ".GEODIR_REVIEWRATING_CATEGORY_TABLE." WHERE id = %d", array( absint( $_REQUEST['rating_cat_id'] ) ) ) );

			$msg = __( 'Rating item deleted successfully.', 'geodir_reviewratings' );

			if ( wp_doing_ajax() ) {
				$response = array(
					'message' => $msg
				);

				wp_send_json_success( $response );

				wp_die();
			} else {
				$url = admin_url( 'admin.php?page=gd-settings&tab=review_rating&section=ratings&gdrr_success=' . urlencode( $msg ) );

				wp_redirect( $url );exit;
			}
		}

		if ( ! empty( $_REQUEST['ajax_action'] ) && $_REQUEST['ajax_action'] == 'delete_style_category' ) {
			if ( ! wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'geodir_delete_style_' . absint( $_REQUEST['style_id'] ) ) ) {
				die();
			}

			$wpdb->query( $wpdb->prepare( "DELETE FROM  " . GEODIR_REVIEWRATING_STYLE_TABLE . " WHERE id = %d", array( absint( $_REQUEST['style_id'] ) ) ) );

			$msg = __( 'Rating style deleted successfully.', 'geodir_reviewratings' );

			if ( wp_doing_ajax() ) {
				$response = array(
					'message' => $msg
				);

				wp_send_json_success( $response );

				wp_die();
			} else {
				$url = admin_url( 'admin.php?page=gd-settings&tab=review_rating&section=styles&gdrr_success=' . urlencode( $msg ) );

				wp_redirect( $url );exit;
			}
		}
	}

    /**
     * Review Rating ajax submit function.
     */
    public static function reviewrating_ajax() {
        if ( isset( $_REQUEST['ajax_action'] ) && $_REQUEST['ajax_action'] == 'review_update_frontend' ) {
            $task = isset( $_REQUEST['task'] ) ? sanitize_text_field( $_REQUEST['task'] ) : '';
            $comment_id = isset( $_REQUEST['comment_id'] ) ? (int) $_REQUEST['comment_id'] : '';
            $wpnonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';

            if ( ! wp_verify_nonce( $wpnonce, 'gd-like-' . $comment_id ) ) {
                echo '0';
                exit;
            }
            GeoDir_Review_Rating_Like_Unlike::geodir_reviewrating_save_like_unlike( $comment_id, $task );
            exit;
        }

        if ( isset( $_REQUEST['ajax_action'] ) && ( $_REQUEST['ajax_action'] == 'comment_actions' || $_REQUEST['ajax_action'] == 'show_tab_head' ) ) {
            geodir_reviewrating_comment_action( $_REQUEST );
        }

        if ( isset( $_REQUEST['ajax_action'] ) && $_REQUEST['ajax_action'] == 'remove_images_by_url' ) {
            geodir_reviewrating_delete_comment_images_by_url();
        }
    }
}

new GeoDir_Review_Rating_AJAX();