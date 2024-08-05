<?php
/**
 * Term and review count, common functions.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */

/**
 * Insert term count for a location.
 *
 * @since 1.0.0
 * @since 1.4.1 Fix term data count for multiple location names with same name.
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 * @since 1.4.7 Fixed add listing page load time.
 * @since 1.5.0 Fixed location terms count for WPML languages.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global array $gd_update_terms The post term ids.
 * @global string $gd_term_post_type The post type.
 *
 * @param $location_name
 * @param string $location_type Location type. Possible values 'gd_city','gd_region','gd_country'.
 * @param array $loc {
 *    Attributes of the location array.
 *
 *    @type string $gd_country The country slug.
 *    @type string $gd_region The region slug.
 *    @type string $gd_city The city slug.
 *
 * }
 * @param string $count_type Count type. Possible values are 'review_count', 'term_count'.
 * @param null $row_id
 * @return array
 */
function geodir_insert_term_count_by_loc($location_name, $location_type, $loc, $count_type, $row_id=null) {
    global $geodirectory;

	if ( ! empty( $geodirectory->taxonomies ) ) {
		return $geodirectory->taxonomies->set_term_count( $location_name, $location_type, $loc, $count_type, $row_id );
	}

	return array();
}

/**
 * Get term count for a location.
 *
 * @since 1.0.0
 * @since 1.4.1 Fix term data count for multiple location names with same name.
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global bool $gd_use_query_vars If true then use query vars to get current location terms.
 *
 * @param string $count_type Count type. Possible values are 'review_count', 'term_count'.
 * @param null|string $location_name Location name slug. Ex: new-york.
 * @param null|string $location_type Location type. Possible values 'gd_city','gd_region','gd_country'.
 * @param bool|array $loc {
 *    Attributes of the location array.
 *
 *    @type string $gd_country The country slug.
 *    @type string $gd_region The region slug.
 *    @type string $gd_city The city slug.
 *
 * }
 * @param bool $force_update Do you want to force update? default: false.
 * @return array|mixed|void
 */
function geodir_get_loc_term_count($count_type = 'term_count', $location_name=null, $location_type=null, $loc=false, $force_update=false ) {
	global $geodirectory;

	if ( ! empty( $geodirectory->taxonomies ) ) {
		return $geodirectory->taxonomies->get_term_count( $count_type, $location_name, $location_type, $loc, $force_update );
	}

	return NULL;
}

/*-----------------------------------------------------------------------------------*/
/*  Term count functions
/*-----------------------------------------------------------------------------------*/

/**
 * Update post term count for the given post id.
 *
 * @since 1.0.0
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 * @since 1.4.7 Fixed add listing page load time.
 * @package GeoDirectory_Location_Manager
 *
 * @global array $gd_update_terms The post term ids.
 * @global string $gd_term_post_type The post type.
 *
 * @param int $post_id The post ID.
 * @param array $post {
 *    Attributes of the location array.
 *
 *    @type string $post_type The post type.
 *    @type string $country The country name.
 *    @type string $region The region name.
 *    @type string $city The city name.
 *
 * }
 */
function geodir_term_post_count_update($post_id, $post) {
    global $wp, $geodirectory;

	if ( defined( 'GEODIR_DOING_IMPORT' ) ) {
		return; //do not run if importing listings
	}
	
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$post_type = ! empty( $post['post_type'] ) ? $post['post_type'] : get_post_type( $post_id );

	if ( $post_type && GeoDir_Post_types::supports( $post_type, 'location' ) ) {
        $gd_post = geodir_get_post_info( $post_id );

		if ( empty( $post_id ) ) {
			return;
		}

		$old_wp = $wp;
		$old_geodirectory = $geodirectory;

		$wp->query_vars['country'] = $gd_post->country;
		$wp->query_vars['region'] = $gd_post->region;
		$wp->query_vars['city'] = $gd_post->city;

		$geodirectory->location->set_current();

		$location_args = array();
		$location_args['country'] = $gd_post->country;
		$location_args['country_slug'] = isset( $geodirectory->location->country_slug ) ? $geodirectory->location->country_slug : '';
		$location_args['region'] = $gd_post->region;
		$location_args['region_slug'] = isset( $geodirectory->location->region_slug ) ? $geodirectory->location->region_slug : '';
		$location_args['city'] = $gd_post->city;
		$location_args['city_slug'] = isset( $geodirectory->location->city_slug ) ? $geodirectory->location->city_slug : '';
		if ( ! empty( $gd_post->neighbourhood ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$location_args['neighbourhood'] = $location_args['city'] . '::' . $gd_post->neighbourhood;
		}

		$wp = $old_wp;
		$geodirectory = $old_geodirectory;

		global $gd_update_terms, $gd_term_post_type;
		$gd_term_post_type = $post_type;
		$terms = wp_get_object_terms( $post_id, $gd_term_post_type . 'category', array( 'fields' => 'ids' ) );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$gd_update_terms = (array)$terms;
		}

		foreach( $location_args as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			if ( in_array( $key, array( 'country', 'region', 'city', 'neighbourhood' ) ) ) {
				geodir_get_loc_term_count( 'review_count', $value, $key, (object) $location_args, true );
			}
		}

		unset( $gd_update_terms );
        unset( $gd_term_post_type );
	}
}

add_action('after_setup_theme','geodir_maybe_disable_auto_term_count_update');
/**
 * 
 */
function geodir_maybe_disable_auto_term_count_update() {
	if ( ! geodir_get_option( 'lm_disable_term_auto_count' ) ) {
		//add_action( 'geodir_after_save_listing', 'geodir_term_post_count_update', 100, 2 );
		add_action( 'save_post', 'goedir_loc_blank_term_counts', 10, 0 );
		add_action( 'edited_terms', 'goedir_loc_blank_term_counts', 10, 0 );
		add_action( 'geodir_tool_recount_terms', 'goedir_loc_blank_term_counts', 10, 0 );
	}
}


function goedir_loc_blank_term_counts(){
	global $wpdb;
	$wpdb->query('UPDATE '.GEODIR_LOCATION_TERM_META.' SET term_count ="" WHERE 1=1 ');
}

/**
 * Returns the term count array.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @return array|mixed|void
 */
function geodir_get_loc_term_count_filter() {
    $data = geodir_get_loc_term_count('term_count');
    return $data;
}
add_filter( 'geodir_get_term_count_array', 'geodir_get_loc_term_count_filter' );

if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
	//add_filter('get_terms', 'gd_get_terms', 10, 3);
}

/**
 * Get terms with term count.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param array $arr Term array.
 * @param string $tax Taxonomy name.
 * @param array $args GD args.
 * @return mixed
 */
function gd_get_terms($arr, $tax, $args) {
	if (isset($args['gd_no_loop'])) {
		return $arr; // so we don't do an infinite loop
	}

	echo '###';print_r($arr);

	if (!empty($arr)) {
		$term_count = geodir_get_loc_term_count('term_count');

		echo '@@@';print_r($term_count);

		/**
		 * Filter the terms count by location.
		 *
		 * @since 1.3.4
		 *
		 * @param array $terms_count Array of term count row.
		 * @param array $terms Array of terms.
		 */
		$term_count = apply_filters( 'geodir_loc_term_count', $term_count, $arr );

        $is_everywhere = geodir_get_current_location_terms();

		foreach ($arr as $term) {
			if (isset($term->term_id) && isset($term_count[$term->term_id])) {
				$term->count = $term_count[$term->term_id];
			}elseif(isset($term->term_id) && !empty($is_everywhere)){// if we dont have a term count for it then it's probably wrong.
                $term->count = 0;
            }
		}
	}

	return $arr;
}

/*-----------------------------------------------------------------------------------*/
/*  Review count functions
/*-----------------------------------------------------------------------------------*/

/**
 * Update review count for each location.
 *
 * @since 1.0.0
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 * @package GeoDirectory_Location_Manager
 *
 * @param int $post_id The post ID.
 */
function geodir_term_review_count_update($post_id) {
	global $wp, $geodirectory;

	if ( empty( $post_id ) ) {
		return;
	}

    $post_type = get_post_type( $post_id );

	if ( $post_type && GeoDir_Post_types::supports( $post_type, 'location' ) ) {
        $gd_post = geodir_get_post_info( $post_id );

		if ( empty( $post_id ) ) {
			return;
		}

		$old_wp = $wp;
		$old_geodirectory = $geodirectory;

		$wp->query_vars['country'] = $gd_post->country;
		$wp->query_vars['region'] = $gd_post->region;
		$wp->query_vars['city'] = $gd_post->city;

		$geodirectory->location->set_current();

		$location_args = array();
		$location_args['country'] = $gd_post->country;
		$location_args['country_slug'] = isset( $geodirectory->location->country_slug ) ? $geodirectory->location->country_slug : '';
		$location_args['region'] = $gd_post->region;
		$location_args['region_slug'] = isset( $geodirectory->location->region_slug ) ? $geodirectory->location->region_slug : '';
		$location_args['city'] = $gd_post->city;
		$location_args['city_slug'] = isset( $geodirectory->location->city_slug ) ? $geodirectory->location->city_slug : '';
		if ( ! empty( $gd_post->neighbourhood ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$location_args['neighbourhood'] = $location_args['city'] . '::' . $gd_post->neighbourhood;
		}

		$wp = $old_wp;
		$geodirectory = $old_geodirectory;

		global $gd_update_terms, $gd_term_post_type;
		$gd_term_post_type = $post_type;
		$terms = wp_get_object_terms( $post_id, $gd_term_post_type . 'category', array( 'fields' => 'ids' ) );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$gd_update_terms = (array)$terms;
		}

		foreach( $location_args as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			if ( in_array( $key, array( 'country', 'region', 'city', 'neighbourhood' ) ) ) {
				geodir_get_loc_term_count( 'review_count', $value, $key, (object) $location_args, true );
			}
		}
    }
    return;
}

add_action( 'geodir_update_postrating', 'geodir_term_review_count_update', 100, 1);


/**
 * Returns the review count array.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @return array|mixed|void
 * @param string $blank	A empty string.
 * @param bool	$force_update If we should force an update.
 */
function geodir_get_loc_review_count_action($blank='',$force_update = false,$post_id=0) {
	if($post_id){
		$hood = geodir_get_post_meta($post_id, 'neighbourhood');
		$post_info['neighbourhood'] = ($hood) ? $hood : '';
		geodir_term_post_count_update($post_id, $post_info);
		//$force_update = false;
	}

	if($force_update ){return null;}

    $data = geodir_get_loc_term_count('review_count',null,null, false,$force_update);

    return $data;
}
add_filter( 'geodir_count_reviews_by_terms_before', 'geodir_get_loc_review_count_action',10,3 );