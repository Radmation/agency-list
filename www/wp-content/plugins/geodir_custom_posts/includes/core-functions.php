<?php
/**
 * Core functions.
 *
 * @since 1.0.0
 * @package GeoDir_Custom_Posts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register CPT widgets.
 *
 * @since 2.0.0.0
 *
 * @param array $widgets The list of available widgets.
 * @return array Available GD widgets.
 */
function goedir_cp_register_widgets( $widgets ) {
	if ( get_option( 'geodir_cp_version' ) ) {
		$widgets[] = 'GeoDir_CP_Widget_CPT_Listings';
		$widgets[] = 'GeoDir_CP_Widget_Post_Linked';
	}

	return $widgets;
}

/**
 * Filter the location terms.
 *
 * @since 1.1.6
 *
 * @param array $location_array Array of location terms. Default empty.
 * @param string $location_array_from Source type of location terms. Default session.
 * @param string $gd_post_type WP post type.
 * @return array Array of location terms.
 */
function geodir_cp_current_location_terms( $location_array = array(), $location_array_from = 'session', $gd_post_type = '' ) {
	if ( ! GeoDir_Post_types::supports( $gd_post_type, 'location' ) ) {
		$location_array = array();
	}
	
	return $location_array;
}

/**
 * Filter the terms count by location.
 *
 * @since 1.1.6
 *
 * @param array $terms_count Array of term count row.
 * @param array $terms Array of terms.
 * @return array Array of term count row.
 */
function geodir_cp_loc_term_count( $terms_count, $terms ) {
	if ( !empty( $terms_count ) ) {
		foreach ( $terms as $term ) {
			if ( isset( $term->taxonomy ) && ! GeoDir_Taxonomies::supports( $term->taxonomy, 'location' ) ) {
				$terms_count[$term->term_id] = $term->count;
			}
		}
	}
	return $terms_count;
}

function geodir_cp_params() {
	$params = array(
		'aui' => geodir_design_style()
	);

	return apply_filters( 'geodir_cp_params', $params );
}

/**
 * Hide the near search field if the CPT is locationless.
 *
 * @since 1.3.1
 * @param string $attrs The inout attributes.
 * @param string $post_type The current CPT.
 *
 * @return string The filtered attrs.
 */
function geodir_cp_hide_near_search_input( $attrs, $post_type ) {
	if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
		$attrs .= ' style="display:none;"';
	}

	return $attrs;
}

/**
 * Add the css class to near search field.
 *
 * @since 2.1.0.3
 *
 * @param string $class The css class.
 * @return string The filtered class.
 */
function geodir_cp_class_near_search_input( $class ) {
	global $geodir_search_post_type;

	if ( ! GeoDir_Post_types::supports( $geodir_search_post_type, 'location' ) ) {
		$class .= geodir_design_style() ? ' d-none' : ' gd-hide';
	}

	return $class;
}

/**
 * Display the attributes for the listing div.
 *
 * @since 2.0.0
 *
 * @param WP_Post $post Optional. Post object.
 */
function geodir_cp_listing_cusotm_attrs( $post = null, $attrs = array() ) {
    if ( ! is_array( $attrs ) ) {
        $attrs = array();
    }
    
    if ( ! empty( $post->post_type ) && geodir_is_gd_post_type( $post->post_type ) ) {
        $attrs['gd-nogeo'] = 1;
    }
    
     return $attrs;
}

/**
 * @param string $post_type
 * @deprecated 
 */
function geodir_custom_post_type_ajax($post_type = ''){
	
	global $wpdb, $plugin_prefix;
	
	if($post_type == '')
		$post_type = $_REQUEST['geodir_deleteposttype'];
	
	$args = array( 'post_type' => $post_type, 'posts_per_page' => -1, 'post_status' => 'any', 'post_parent' => null );
	
	
	/* ------- START DELETE ALL TERMS ------- */
	
	$terms = $wpdb->get_results("SELECT term_id, taxonomy FROM ".$wpdb->prefix."term_taxonomy WHERE taxonomy IN ('".$post_type."category', '".$post_type."_tags')");
	
	if(!empty($terms)){
		foreach( $terms as $term ){
			wp_delete_term($term->term_id,$term->taxonomy);
		}
	}
	
	$wpdb->query("DELETE FROM ".$wpdb->prefix."options WHERE option_name LIKE '%tax_meta_".$post_type."_%'");
	
	
	/* ------- END DELETE ALL TERMS ------- */
	
	$geodir_all_posts = get_posts( $args );
	
	if(!empty($geodir_all_posts)){
	
		foreach($geodir_all_posts as $posts)
		{
			wp_delete_post($posts->ID);
		}
	}
	
	do_action('geodir_after_post_type_deleted'  , $post_type);

	$wpdb->query($wpdb->prepare("DELETE FROM ".GEODIR_CUSTOM_FIELDS_TABLE." WHERE post_type=%s",array($post_type)));
	
	$wpdb->query($wpdb->prepare("DELETE FROM ".GEODIR_CUSTOM_SORT_FIELDS_TABLE." WHERE post_type=%s",array($post_type)));
	
	$detail_table =  geodir_db_cpt_table( $post_type );
	
	$wpdb->query("DROP TABLE IF EXISTS ".$detail_table);
	
	$msg = 	__( 'Post type related data deleted successfully.', 'geodir_custom_posts' );
	
	$msg = urlencode($msg);
	
	if(isset($_REQUEST['geodir_deleteposttype']) && $_REQUEST['geodir_deleteposttype']){
	
		$redirect_to = admin_url().'admin.php?page=geodirectory&tab=geodir_manage_custom_posts&cp_success='.$msg;
		wp_redirect( $redirect_to );
	
		gd_die();
	}
	
}

function geodir_cp_super_duper_widget_init( $options, $super_duper ) {
	global $gd_listings_widget_js;

	if ( ! $gd_listings_widget_js && ! empty( $options['base_id'] ) && ( $options['base_id'] == 'gd_listings' || $options['base_id'] == 'gd_linked_posts' ) ) {
		$gd_listings_widget_js = true;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'geodir-cp-widget', GEODIR_CP_PLUGIN_URL . '/assets/js/widget' . $suffix . '.js', array( 'jquery' ), GEODIR_CP_VERSION );

		wp_enqueue_script( 'geodir-cp-widget' );
	}
}

function geodir_cp_parse_width_height( $value ) {
    if ( $value !== '' ) {
		$value = geodir_strtolower( $value );
		
		if ( strpos( $value, 'auto' ) !== false ) {
			$value = 'auto';
		} else if ( strpos( $value, 'inherit' ) !== false ) {
			$value = 'inherit';
		} else if ( strpos( $value, '%' ) !== false ) {
			$value = abs( (float)$value ) . '%';
		} else if ( strpos( $value, 'px' ) !== false || abs( (float)$value ) > 0 ) {
			$value = abs( (float)$value ) . 'px';
		} else {
			$value = '';
		}
	}
	
    return $value;
}

function geodir_cp_templates_path() {
	return GEODIR_CP_PLUGIN_DIR . '/templates/';
}