<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Custom Post Types AJAX class.
 *
 * AJAX Event Handler.
 *
 * @class    GeoDir_CP_AJAX
 * @package  GeoDir_Custom_Posts/Classes
 * @category Class
 * @author   AyeCode Ltd
 */
class GeoDir_CP_AJAX {

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
			'ajax_delete_post_type' => false,
			'cp_search_posts' => true,
			'widget_post_type_field_options' => false,
			'cp_fill_data' => true,
			'post_type_export_fields' => true,
			'post_type_export_tabs' => true
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

	public static function ajax_delete_post_type() {
		$post_type = ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';

		check_ajax_referer( 'geodir-delete-post-type-' . $post_type, 'security' );

		if ( ! current_user_can( 'manage_options' ) || $post_type == 'gd_place' || $post_type == 'gd_event' ) {
			wp_die( -1 );
		}

		if(GeoDir_CP_Post_Types::delete_cpt($post_type)){
			$data = array( 'message' => __( 'Post type deleted successfully.', 'geodir_custom_posts' ) );
			wp_send_json_success( $data );
		}else{
			wp_send_json_error( array( 'message' => __( 'Post type delete failed.', 'geodir_custom_posts' )) );
		}
	}

	public static function cp_search_posts( $term = '' ) {
		$source_cpt = ! empty( $_POST['source_cpt'] ) ? sanitize_text_field( $_POST['source_cpt'] ) : '';
		$dest_cpt = ! empty( $_POST['dest_cpt'] ) ? sanitize_text_field( $_POST['dest_cpt'] ) : '';

		check_ajax_referer( 'geodir-select-search-' . $source_cpt . '-' . $dest_cpt, 'security' );

		if ( $term == '' && isset( $_POST['term'] ) ) {
			 $term = stripslashes( $_POST['term'] );
		}

		$term = geodir_clean( $term );

		if ( $term == '' ) {
			wp_die();
		}

		$custom_field = geodir_get_field_infoby( 'htmlvar_name', $dest_cpt, $source_cpt );
		if ( empty( $custom_field ) ) {
			wp_die();
		}

		if ( apply_filters( 'geodir_cp_link_posts_allow_search_posts', true, $custom_field ) !== true ) {
			wp_die();
		}

		$limit = ! empty( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 20;
		$include = ! empty( $_POST['include'] ) ? absint( $_POST['include'] ) : 0;

		$items = GeoDir_CP_Link_Posts::search_posts( $dest_cpt, $term, $limit, $custom_field );

		$results = array();
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				if ( $include == $item->ID ) {
					$include = 0;
				}
				$results[ $item->ID ] = rawurldecode( $item->post_title );
			}
		}
		if ( ! empty( $include ) && ( $title = get_the_title( $include ) ) ) {
			$results[ $include ] = rawurldecode( $title );
		}

		wp_send_json( apply_filters( 'geodir_cp_search_found_posts', $results, $term, $source_cpt, $dest_cpt ) );
	}

	public static function widget_post_type_field_options() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$post_type = ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';

			$category_options = '';
			if ( $categories = geodir_category_options( $post_type ) ) {
				foreach ( $categories as $value => $name ) {
					$category_options .= '<option value="' . $value . '">' . $name . '</option>';
				}
			}

			$sort_by_options = '';
			if ( $sort_by = geodir_sort_by_options( $post_type ) ) {
				foreach ( $sort_by as $value => $name ) {
					$sort_by_options .= '<option value="' . $value . '">' . $name . '</option>';
				}
			}

			$data = array( 
				'category' => array( 
					'options' => $category_options 
				),
				'sort_by' => array( 
					'options' => $sort_by_options 
				)
			);

			$data = apply_filters( 'geodir_widget_post_type_field_options', $data, $post_type );

			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function cp_fill_data() {
		$from_post_type = ! empty( $_POST['from_post_type'] ) ? sanitize_text_field( $_POST['from_post_type'] ) : '';
		$to_post_type = ! empty( $_POST['to_post_type'] ) ? sanitize_text_field( $_POST['to_post_type'] ) : '';
		$from_post_id = ! empty( $_POST['from_post_id'] ) ? absint( $_POST['from_post_id'] ) : 0;
		$to_post_id = ! empty( $_POST['to_post_id'] ) ? absint( $_POST['to_post_id'] ) : 0;

		check_ajax_referer( 'geodir_basic_nonce', 'security' );

		if ( ! ( ! empty( $from_post_type ) && ! empty( $to_post_type ) && ! empty( $to_post_id ) ) ) {
			wp_die();
		}

		$response = GeoDir_CP_Link_Posts::fill_data( $from_post_type, $from_post_id, $to_post_type, $to_post_id );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		} else {
			wp_send_json_success( $response );
		}

		wp_die();
	}

	public static function post_type_export_fields() {
		check_ajax_referer( 'geodir_basic_nonce', 'security' );

		$post_type = ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';
		$fields = GeoDir_CP_Admin_Import_Export::get_custom_fields( $post_type );

		$content = '';
		foreach ( $fields as $key => $field ) {
			$content .= '<div class="col-sm-6">';
			$content .= aui()->input(
				array(
					'id'         => 'gd_field_'. $key,
					'name'       => 'gd_imex[fields][]',
					'type'       => 'checkbox',
					'value'      => (int) $field->id,
					'label'      => $field->htmlvar_name . ' ( ' . $field->admin_title . ' ) ',
					'label_type' => 'hidden',
					'no_wrap'    => true
				)
			);
			$content .= '</div>';
		}

		wp_send_json_success( array( 'html' => $content ) );

		wp_die();
	}

	public static function post_type_export_tabs() {
		check_ajax_referer( 'geodir_basic_nonce', 'security' );

		$post_type = ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';
		$items = GeoDir_CP_Admin_Import_Export::get_cpt_tabs( $post_type );

		$content = '';
		foreach ( $items as $key => $item ) {
			$content .= '<div class="col-sm-6 mb-1">';
			$content .= aui()->input(
				array(
					'id'         => 'gd_field_'. $key,
					'name'       => 'gd_imex[tabs][]',
					'type'       => 'checkbox',
					'value'      => (int) $item->id,
					'label'      => stripslashes( $item->tab_name ) . '<div class="d-inline text-muted"> - ' . $item->tab_key . ' (' . $item->tab_type . ')</div>',
					'label_type' => 'hidden',
					'no_wrap'    => true
				)
			);
			$content .= '</div>';
		}

		wp_send_json_success( array( 'html' => $content ) );

		wp_die();
	}
}