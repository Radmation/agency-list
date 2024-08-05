<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Advance Search Filters AJAX class.
 *
 * AJAX Event Handler.
 *
 * @class    GeoDir_Adv_Search_AJAX
 * @package  GeoDir_Advance_Search_Filters/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Adv_Search_AJAX {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		// geodirectory_EVENT => nopriv
		$ajax_events = array(
			'cpt_search_field_form' => false,
			'cpt_search_save_field' => false,
			'cpt_search_delete_field' => false,
			'cpt_search_save_order' => false,
			'search_autocomplete' => true,
			'set_user_location' => true,
			'set_near_me_range' => true,
			'share_location' => true,
			'do_not_share_location' => true,
			'ajax_search' => true
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

	public static function cpt_search_field_form() {
		check_ajax_referer( 'gd_new_field_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$post_type = ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';
		$htmlvar_name = ! empty( $_POST['htmlvar_name'] ) ? sanitize_text_field( $_POST['htmlvar_name'] ) : '';

		if ( empty( $post_type ) ) {
			wp_send_json_error( __( "Post type not found.", "geodiradvancesearch" ) );
		} else if ( empty( $htmlvar_name ) ) {
			wp_send_json_error( __( "htmlvar_name not found.", "geodiradvancesearch" ) );
		} else {
			$field = array(
				'post_type' => $post_type,
				'htmlvar_name' => $htmlvar_name,
			);
			$result = GeoDir_Adv_Search_Settings_Cpt_Search::get_field( $field );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			} else {
				wp_send_json_success( $result );
			}
		}

		wp_die();
	}


	public static function cpt_search_save_field(){
		check_ajax_referer( 'gd_new_field_nonce', 'security' );
	
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$post_type = ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';
		$htmlvar_name = ! empty( $_POST['htmlvar_name'] ) ? sanitize_text_field( $_POST['htmlvar_name'] ) : '';

		if ( empty( $post_type ) ) {
			wp_send_json_error( __( "Post type not found.", "geodiradvancesearch" ) );
		} else {
			$result = GeoDir_Adv_Search_Settings_Cpt_Search::save_field( $_POST );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			} else {
				if ( $result > 0 ) {
					$result = GeoDir_Adv_Search_Settings_Cpt_Search::get_field( $result );
				}

				wp_send_json_success( $result );
			}
		}

		wp_die();
	}

	public static function cpt_search_delete_field(){
		check_ajax_referer( 'gd_new_field_nonce', 'security' );
	
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$id = ! empty( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
		$post_type = ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';

		if ( ! $id ) {
			wp_send_json_error( __( "No field found.", "geodiradvancesearch" ) );
		} else {
			$result = GeoDir_Adv_Search_Settings_Cpt_Search::delete_field( $id, $post_type );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			} else {
				wp_send_json_success( $result );
			}
		}

		wp_die();
	}

	public static function cpt_search_save_order(){
		check_ajax_referer( 'gd_new_field_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$fields = ! empty( $_POST['fields'] ) ? $_POST['fields'] : '';

		if ( empty( $fields ) ) {
			wp_send_json_error( __( "No fields found.", "geodiradvancesearch" ) );
		} else {
			$result = GeoDir_Adv_Search_Settings_Cpt_Search::set_fields_order( $fields );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			} else {
				wp_send_json_success( $result );
			}
		}

		wp_die();
	}

	public static function search_autocomplete(){
		$keyword = isset( $_REQUEST['q'] ) ? sanitize_text_field( $_REQUEST['q'] ) : '';
		$post_type = isset( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : '';

		if ( $keyword !== '' && ! empty( $post_type ) ) {
			$results = geodir_search_get_autocomplete_results( $post_type, $keyword );

			if ( ! empty( $results ) ) {
				echo implode( "\n", $results );
			}
		}

		wp_die();
	}

	/* @todo move to LMv2 */
	public static function set_user_location() {
		do_action( 'geodir_search_set_user_location' );
		/*
		global $gd_session;
		
		$my_location = isset($_POST['myloc']) && $_POST['myloc'] ? 1 : 0;
		
		$gd_session->set('user_lat',$_POST['lat']);
		$gd_session->set('user_lon', $_POST['lon']);
		$gd_session->set('my_location', $my_location);
		$gd_session->set('user_pos_time', time());
		*/
		
		wp_die();
	}

	/* @todo move to LMv2 */
	public static function set_near_me_range() {
		global $gd_session;

		$near_me_range = geodir_adv_search_distance_unit() == 'km' ? (int)$_POST['range'] * 0.621371192 : (int)$_POST['range'];

		//$gd_session->set('near_me_range', $near_me_range);

		$result = array();
		$result['near_me_range'] = $near_me_range;

		wp_send_json_success( $result );

		wp_die();
	}

	/* @todo move to LMv2 */
	public static function share_location() {
		$redirect_url = apply_filters( 'geodir_share_location', get_site_url() );
		echo wp_validate_redirect( $redirect_url, 'OK' );
		wp_die();
	}

	/* @todo move to LMv2 */
	public static function do_not_share_location() {
		/*
		global $gd_session;
		$gd_session->set('gd_onload_redirect_done', 1);
		$gd_session->set('gd_location_shared', 1);
		*/
		echo 'OK';
		wp_die();
	}

	/**
	 * Handle AJAX search.
	 *
	 * @since 2.2.2
	 *
	 * @return string Search results output.
	 */
	public static function ajax_search() {
		do_action( 'geodir_search_handle_ajax_request' );

		wp_send_json_success( array() );
	}
}