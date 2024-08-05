<?php
/**
 * Contains hook related to Location Manager plugin.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */

add_filter('geodir_diagnose_multisite_conversion', 'geodir_diagnose_multisite_conversion_location_manager', 10, 1);
/**
 * Diagnose Location Manager tables.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param array $table_arr Diagnose table array.
 * @return array Modified diagnose table array.
 */
function geodir_diagnose_multisite_conversion_location_manager($table_arr) {
    $table_arr['geodir_neighbourhood'] = __('Neighbourhood', 'geodirlocation');
    $table_arr['geodir_post_locations'] = __('Locations', 'geodirlocation');
    $table_arr['geodir_location_seo'] = __('Location SEO', 'geodirlocation');
    return $table_arr;
}

/**
 * Function for display GeoDirectory location error and success messages.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */
function geodir_display_location_messages() {
    if (isset($_REQUEST['location_success']) && $_REQUEST['location_success'] != '') {
        echo '<div id="message" class="updated fade"><p><strong>' . sanitize_text_field($_REQUEST['location_success']) . '</strong></p></div>';
    }

    if (isset($_REQUEST['location_error']) && $_REQUEST['location_error'] != '') {
        echo '<div id="payment_message_error" class="updated fade"><p><strong>' . sanitize_text_field($_REQUEST['location_error']) . '</strong></p></div>';
    }
}
add_action('geodir_before_admin_panel', 'geodir_display_location_messages');

add_filter('geodir_search_near_addition', 'geodir_search_near_additions', 3);
/**
 * Adds any extra info to the near search box query when trying to geolocate it via google api.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $additions Extra info string.
 * @return string
 */
function geodir_search_near_additions( $additions ) {
    global $geodirectory;

    $loc = '';
    if ( $default = $geodirectory->location->get_default_location() ) {
        if ( geodir_get_option( 'lm_default_region' ) == 'default' && $default->region ) {
            $loc .= '+", ' . $default->region . '"';
        }
		// Added country code filter
        /*if ( geodir_get_option( 'lm_default_country' ) == 'default' && $default->country ) {
            $loc .= '+", ' . $default->country . '"';
        }*/
    }
    return $loc;
}

function geodir_location_google_geocode_address_request( $near_address ) {
	global $geodirectory;

	$country_option = geodir_get_option( 'lm_default_country' );

    if ( $country_option == 'default' && ( $default = $geodirectory->location->get_default_location() ) ) {
        if ( ! empty( $default->country ) && ( $iso2 = $geodirectory->location->get_country_iso2( $default->country ) ) ) {
if (0) { ?><script><?php } ?>
if (typeof geocodeQueryParams['componentRestrictions'] != 'undefined') { if (typeof geocodeQueryParams['componentRestrictions']['country'] == 'undefined') { geocodeQueryParams['componentRestrictions']['country'] = '<?php echo esc_js( $iso2 ); ?>'; } } else { geocodeQueryParams['componentRestrictions'] = {'country': '<?php echo esc_js( $iso2 ); ?>'}; }
<?php if (0) { ?></script><?php } 
        }
    } else if ( $country_option == 'selected' && ( $selected_countries = (array) geodir_get_option( 'lm_selected_countries' ) ) ) {
		$bounds = geodir_location_get_search_bounds();

		if ( ! empty( $bounds ) ) {
			echo " geocodeQueryParams['bounds'] = new google.maps.LatLngBounds(new google.maps.LatLng(" . esc_js( $bounds['sw'][0] ) . "," . esc_js( $bounds['sw'][1] ) . "),new google.maps.LatLng(" . esc_js( $bounds['ne'][0] ) . "," . esc_js( $bounds['ne'][1] ) . "));";
		}
	}
}
add_action( 'geodir_google_geocode_address_request', 'geodir_location_google_geocode_address_request', 10, 1 );

function geodir_location_google_geocode_results( $near_address ) {
	global $geodirectory;

	$country_option = geodir_get_option( 'lm_default_country' );

	$country_codes = array();

    if ( $country_option == 'selected' && ( $selected_countries = (array) geodir_get_option( 'lm_selected_countries' ) ) ) {
		foreach( $selected_countries as $country_name ) {
			if ( $country_code = $geodirectory->location->get_country_iso2( $country_name ) ) {
				$country_codes[] = esc_js( $country_code );
			}
		}
	}

	if ( ! empty( $country_codes ) ) {
		$country_codes = "['" . strtolower( implode( "','", $country_codes ) ) . "']";
if (0) { ?><script><?php } ?>
 if (status == google.maps.GeocoderStatus.OK && results.length) {
    var matchCountries = <?php echo $country_codes; ?>;
	var oItem = false, iMoveItems = 0, aMoveItems = [];
	results.forEach(function(item) {
		var bFound = false;
		if (item.address_components && !oItem) {
			for (var _i=0; _i < item.address_components.length; _i++) {
				var addrComp = item.address_components[_i];
				var _contCode = addrComp.types && addrComp.types[0] == 'country' && addrComp.short_name ? (addrComp.short_name).toLowerCase() : '';
				if (_contCode && jQuery.inArray(_contCode, matchCountries) !== -1) { oItem = item;bFound = true; }
			}
		}
		if (!bFound) { aMoveItems.push(item);iMoveItems++; }
	});
	if (oItem) { if (aMoveItems.length) { results = jQuery.merge([oItem], aMoveItems); } else { results[0] = oItem; } } else { status = 'ZERO_RESULTS'; /* Show zero results message. */ }
 }
<?php if (0) { ?></script><?php }
	}
}
add_action( 'geodir_google_geocode_results_script', 'geodir_location_google_geocode_results', 10, 1 );

function geodir_location_osm_geocode_address_request( $near_address ) {
	global $geodirectory;

	$country_option = geodir_get_option( 'lm_default_country' );

	$country_codes = array();

    if ( $country_option == 'default' && ( $default = $geodirectory->location->get_default_location() ) ) {
        if ( ! empty( $default->country ) && ( $iso2 = $geodirectory->location->get_country_iso2( $default->country ) ) ) {
			$country_codes[] = $iso2;
		}
	} else if ( $country_option == 'selected' && ( $selected_countries = (array) geodir_get_option( 'lm_selected_countries' ) ) ) {
		foreach( $selected_countries as $country_name ) {
			if ( $country_code = $geodirectory->location->get_country_iso2( $country_name ) ) {
				$country_codes[] = $country_code;
			}
		}
	}

	if ( ! empty( $country_codes ) ) {
		$country_codes = strtolower( implode( ',', $country_codes ) );

		echo " osmCountryCodes = '" . esc_js( $country_codes ) . "';";
	}
}
add_action( 'geodir_osm_geocode_address_request', 'geodir_location_osm_geocode_address_request', 10, 1 );

add_filter('geodir_design_settings', 'geodir_detail_page_related_post_add_location_filter_checkbox', 1);
/**
 * This add a new filed in Geodirectory > Design > Detail > Related Post Settings.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param array $arr GD design settings array.
 * @return array Filtered GD design settings array.
 */
function geodir_detail_page_related_post_add_location_filter_checkbox($arr)
{
    $location_design_array = array();
    foreach ($arr as $key => $val) {
        $location_design_array[] = $val;
        if ($val['id'] == 'geodir_related_post_excerpt') {
            $location_design_array[] = array(
                'name' => __('Enable Location Filter:', 'geodirlocation'),
                'desc' => __('Enable location filter on related post.', 'geodirlocation'),
                'id' => 'geodir_related_post_location_filter',
                'type' => 'checkbox',
                'std' => '1' // Default value to show home top section
            );
        }
    }
    return $location_design_array;
}


/**************************
 * /* LOCATION ADDONS QUERY FILTERS
 **************************    */

/**
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param array $public_query_vars The array of white listed query variables.
 * @return array Filtered query variables.
 */
function geodir_location_set_public_query_vars( $public_query_vars ) {
    $public_query_vars[] = 'gd_neighbourhood';

    return $public_query_vars;
}
add_filter( 'query_vars', 'geodir_location_set_public_query_vars' );


add_action('pre_get_posts', 'geodir_listing_loop_location_filter', 2);
/**
 * Adds location filter to the query.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wp_query WordPress Query object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 *
 * @param object $query The WP_Query instance.
 */
function geodir_listing_loop_location_filter($query)
{
    global $wp_query, $geodir_post_type, $table, $plugin_prefix, $table, $term;

    // fix wp_reset_query for popular post view widget
    if (!geodir_is_geodir_page()) {
        return;
    }

    $apply_location_filter = true;
    if (isset($query->query_vars['gd_location'])) {
        $apply_location_filter = $query->query_vars['gd_location'] ? true : false;
    }

    if (isset($query->query_vars['is_geodir_loop']) && $query->query_vars['is_geodir_loop'] && !is_admin() && !geodir_is_page('add-listing') && !isset($_REQUEST['geodir_dashbord']) && $apply_location_filter) {
        //geodir_post_location_where(); // this function is in geodir_location_functions.php
    }
}

/**
 * Filters the where clause.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */
function geodir_post_location_where()
{
    global $snear;
    if ((is_search() && $_REQUEST['geodir_search'])) {
        add_filter('posts_where', 'searching_filter_location_where', 2);

        if ($snear != '')
            add_filter('posts_where', 'searching_filter_location_where', 2);
    }

//    if (!geodir_is_page('detail'))
//        add_filter('posts_where', 'geodir_default_location_where', 2);/**/

}

/**
 * Adds the location filter to the where clause.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param string $where The WHERE clause of the query.
 * @return string Filtered WHERE clause.
 */
function searching_filter_location_where($where)
{
    global $table;
    $city_where = '';
    // Filter-Location-Manager // City search ..
    if (isset($_REQUEST['scity'])) {
        if (is_array($_REQUEST['scity']) && !empty($_REQUEST['scity'])) {
            $awhere = array();
            foreach ($_REQUEST['scity'] as $city) {
                $awhere[] = " locations LIKE '[" . $_REQUEST['scity'] . "],%' ";
            }
            $where .= " ( " . implode(" OR ", $awhere) . " ) ";
        } elseif ($_REQUEST['scity'] != '') {
            $where .= " locations LIKE '[" . $_REQUEST['scity'] . "],%' ";
        }
    }
    return $where;
}

/**
 * Adds the default location filter to the where clause.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wp_query WordPress Query object.
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @global object $wp WordPress object.
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param string $where The WHERE clause of the query.
 * @param string $p_table Post table.
 * @return mixed|string Filtered where clause.
 */
function geodir_default_location_where( $where, $p_table = '' ) {
	global $wp_query, $wpdb, $table, $wp, $geodirectory, $gd_query_args_widgets;

	$allowed_location = apply_filters( 'geodir_location_allowed_location_where', true, $wp->query_vars, $table, $wp_query, $p_table );

	if ( ! $allowed_location ) {
		return $where;
	}

	// My location set start
	if ( $latlon = $geodirectory->location->get_latlon() ) {
		$lat = $latlon['lat'];
		$lon = $latlon['lon'];
		$between = geodir_get_between_latlon( $lat, $lon );

		$where .= $wpdb->prepare( " AND $p_table.latitude between %f and %f AND $p_table.longitude between %f and %f ", $between['lat1'], $between['lat2'], $between['lon1'], $between['lon2'] );

		return $where;
	}

	$where = str_replace("0 = 1", "1=1", $where);

	$locations = GeoDir()->location;

	$country = isset( $gd_query_args_widgets['country'] ) ? $gd_query_args_widgets['country'] : '';
	$region = isset( $gd_query_args_widgets['region'] ) ? $gd_query_args_widgets['region'] : '';
	$city = isset( $gd_query_args_widgets['city'] ) ? $gd_query_args_widgets['city'] : '';
	$neighbourhood = isset( $gd_query_args_widgets['neighbourhood'] ) ? $gd_query_args_widgets['neighbourhood'] : '';

	if (!empty($locations->country)) {
		$where .= $wpdb->prepare(" AND $p_table.country = %s", $locations->country);
	}elseif(!empty($country)){
		$country =  $geodirectory->location->get_country_name_from_slug($country );
		if($country)
		$where .= $wpdb->prepare(" AND $p_table.country = %s", $country);
	}

	if (!empty($locations->region) ) {
		$where .= $wpdb->prepare(" AND $p_table.region = %s", $locations->region);
	}elseif(!empty($region)){
		$region =  $geodirectory->location->get_region_name_from_slug($region );
		if($region)
		$where .= $wpdb->prepare(" AND $p_table.region = %s", $region);
	}

	if (!empty($locations->city)) {
		$where .= $wpdb->prepare(" AND $p_table.city = %s", $locations->city);
	}elseif(!empty($city)){
		$city =  $geodirectory->location->get_city_name_from_slug($city );
		if($city)
		$where .= $wpdb->prepare(" AND $p_table.city = %s", $city);
	}

	if (geodir_get_option( 'lm_enable_neighbourhoods' )) {
		if (!empty($locations->neighbourhood_slug)) {
			$neighbourhood = $locations->neighbourhood_slug;
		}

		if (!$neighbourhood || $neighbourhood == '') {
			// check if we have neighbourhood in query vars
			if (isset($wp->query_vars['gd_neighbourhood']) && $wp->query_vars['gd_neighbourhood'] != '') {
				$neighbourhood = $wp->query_vars['gd_neighbourhood'];
			}
		}

		// added for map calls
		if (empty($neighbourhood) && !empty($_REQUEST['gd_neighbourhood'])) {
			$neighbourhood = $_REQUEST['gd_neighbourhood'];

			if (isset($_REQUEST['gd_posttype']) && $_REQUEST['gd_posttype'] != '') {
				$p_table = "pd";
			}
		}

		$post_table = $table != '' ? $table . '.' : ''; /* fixed db error when $table is not set */

		if (!empty($p_table)) {
			$post_table = $p_table . '.';
		}

		if (is_array($neighbourhood) && !empty($neighbourhood)) {
			$neighbourhood_length = count($neighbourhood);
			$format = array_fill(0, $neighbourhood_length, '%s');
			$format = implode(',', $format);

			$where .= $wpdb->prepare(" AND " . $post_table . "neighbourhood IN ($format) ", $neighbourhood);
		} else if (!is_array($neighbourhood) && !empty($neighbourhood)) {
			$where .= $wpdb->prepare(" AND " . $post_table . "neighbourhood LIKE %s ", $neighbourhood);
		}
	}

	return $where;
}

/**************************
 * /* LOCATION AJAX Handler
 ***************************/
add_action('wp_ajax_geodir_location_ajax', 'geodir_location_ajax_handler'); // it in geodir_location_functions.php
add_action('wp_ajax_nopriv_geodir_location_ajax', 'geodir_location_ajax_handler');
// AJAX Handler //
/**
 * Handles ajax request.
 *
 * @since 1.0.0
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 *              Location filter added in back-end post type listing pages.
 * @package GeoDirectory_Location_Manager
 */
function geodir_location_ajax_handler()
{
    global $geodirectory;
    if (isset($_REQUEST['gd_loc_ajax_action']) && $_REQUEST['gd_loc_ajax_action'] != '') {
        switch ($_REQUEST['gd_loc_ajax_action']) {
            case 'get_location' :
                $neighbourhood_active = geodir_get_option( 'lm_enable_neighbourhoods' );
                $which_location = isset($_REQUEST['gd_which_location']) ? trim($_REQUEST['gd_which_location']) : '';
                $formated_for = isset($_REQUEST['gd_formated_for']) ? trim($_REQUEST['gd_formated_for']) : '';

                if ($which_location != '') {
                    $city_val = isset($_REQUEST['gd_city_val']) && $_REQUEST['gd_city_val'] != '' ? $_REQUEST['gd_city_val'] : '';
                    $region_val = isset($_REQUEST['gd_region_val']) && $_REQUEST['gd_region_val'] != '' ? $_REQUEST['gd_region_val'] : '';
                    $country_val = isset($_REQUEST['gd_country_val']) && $_REQUEST['gd_country_val'] != '' ? $_REQUEST['gd_country_val'] : '';
                    $country_val = isset($_REQUEST['gd_country_val']) && $_REQUEST['gd_country_val'] != '' ? $_REQUEST['gd_country_val'] : '';
                    $task = !empty($_REQUEST['_task']) ? $_REQUEST['_task'] : '';
                    $max_num_pages = !empty($_REQUEST['_pages']) ? absint($_REQUEST['_pages']) : 1;

                    $spage = !empty($_REQUEST['spage']) && $_REQUEST['spage'] > 0 ? absint($_REQUEST['spage']) : 0;
                    $no_of_records = !empty($_REQUEST['lscroll']) ? absint($_REQUEST['lscroll']) : 0;
                    if (!$no_of_records > 0) {
                        $default_limit = (int)geodir_get_option( 'lm_location_no_of_records' );
                        $no_of_records = absint($default_limit) > 0 ? $default_limit : 50;
                    }

                    $location_args = array(
                        'what' => $which_location,
                        'city_val' => $city_val,
                        'region_val' => $region_val,
                        'country_val' => $country_val,
                        'compare_operator' => 'like',
                        'country_column_name' => 'country',
                        'region_column_name' => 'region',
                        'city_column_name' => 'city',
                        'location_link_part' => true,
                        'order_by' => ' asc ',
                        'no_of_records' => $no_of_records,
                        'format' => array('type' => 'array'),
                        'spage' => $spage
                    );

                    if ($neighbourhood_active) {
                        $neighbourhood_val = isset($_REQUEST['gd_neighbourhood_val']) && $_REQUEST['gd_neighbourhood_val'] != '' ? $_REQUEST['gd_neighbourhood_val'] : '';
                        $location_args['neighbourhood_val'] = $neighbourhood_val;
                        $location_args['neighbourhood_column_name'] = 'hood_name';
                    }
                    
                    if ($task != 'loadmore') {
                        $total = geodir_get_location_array(array_merge($location_args, array('counts_only' => true)), false);
                        $max_num_pages = ceil($total / $no_of_records);
                    }

                    $location_array = geodir_get_location_array($location_args);

                    if ($formated_for == 'location_switcher') {
                        $base_location_link = geodir_get_location_link('base');

                        if (!empty($location_array)) {
                            if ($task == 'loadmore') {
                                $load_more = $max_num_pages > ($spage + 1) ? true : false;
                            } else {
                                $load_more = $max_num_pages > 1 ? true : false;
                            }
                        
                            $has_arrow = true;
                            if (($neighbourhood_active && $which_location == 'neighbourhood') || (!$neighbourhood_active && $which_location == 'city')) {
                                $has_arrow = false;
                            }

                            $arrow_html = $has_arrow ? '<span class="geodir_loc_arrow"><a href="javascript:void(0);">&nbsp;</a></span>' : '';

                            foreach ($location_array as $location_item) {
                                if (empty($location_item->{$which_location})) {
                                    continue;
                                }
                                $location_name = $which_location == 'country' ? __($location_item->{$which_location}, 'geodirectory') : $location_item->{$which_location};

                                echo '<li class="geodir_loc_clearfix"><a href="' . geodir_location_permalink_url($base_location_link . $location_item->location_link) . '">' . stripslashes($location_name) . '</a>' . $arrow_html . '</li>';
                            }
                            if ($load_more) {
                                echo '<li class="geodir_loc_clearfix gd-loc-loadmore"><button class="geodir_button" data-pages="' . $max_num_pages . '" data-next="' . ( $spage + 1 ) . '" data-title="' . esc_attr__( 'Loading...', 'geodirlocation' ) . '"><i class="fas fa-sync fa-fw" aria-hidden="true"></i> <font>' . __( 'Load more', 'geodirlocation' ) . '<font></button></li>';
                            }
                        } else {
                            if ($task == 'loadmore') {
                                exit;
                            }
                            echo '<li class="geodir_loc_clearfix gdlm-no-results">' . __("No Results", 'geodirlocation') . '</li>';
                        }
                    } else {
                        print_r($location_array);
                    }
                    exit();
                }
                break;
            case 'fill_location': {
                $neighbourhood_active = geodir_get_option( 'lm_enable_neighbourhoods' );

                $gd_which_location = isset($_REQUEST['gd_which_location']) ? strtolower(trim($_REQUEST['gd_which_location'])) : '';
                $term = isset($_REQUEST['term']) ? trim($_REQUEST['term']) : '';

                $base_location = geodir_get_location_link('base');
                $current_location_array;
                $selected = '';
                $country_val = '';
                $region_val = '';
                $city_val = '';
                $country_val = geodir_get_current_location(array('what' => 'country', 'echo' => false));
                $region_val = geodir_get_current_location(array('what' => 'region', 'echo' => false));
                $city_val = geodir_get_current_location(array('what' => 'city', 'echo' => false));
                $neighbourhood_val = $neighbourhood_active ? geodir_get_current_location(array('what' => 'neighbourhood', 'echo' => false)) : '';

                $item_set_selected = false;

                if (isset($_REQUEST['spage']) && $_REQUEST['spage'] != '') {
                    $spage = $_REQUEST['spage'];
                } else {
                    $spage = '';
                }

                if (isset($_REQUEST['lscroll']) && $_REQUEST['lscroll'] != '') {
                    $no_of_records = '5';
                } else {
                    $no_of_records = '5';
                } // this was loading all locations when set to 0 on tab switch so we change to 5 to limit it.

                $location_switcher_list_mode = geodir_get_option( 'lm_location_switcher_list_mode' );
                if (empty($location_switcher_list_mode)) {
                    $location_switcher_list_mode = 'drill';
                }

                if ($location_switcher_list_mode == 'drill') {
                    $args = array(
                        'what' => $gd_which_location,
                        'country_val' => in_array($gd_which_location, array('region', 'city', 'neighbourhood')) ? $country_val : '',
                        'region_val' => in_array($gd_which_location, array('city', 'neighbourhood')) ? $region_val : '',
                        'echo' => false,
                        'no_of_records' => $no_of_records,
                        'format' => array('type' => 'array'),
                        'spage' => $spage
                    );

                    if ($gd_which_location == 'neighbourhood' && $city_val != '') {
                        $args['city_val'] = $city_val;
                    }
                } else {
                    $args = array(
                        'what' => $gd_which_location,
                        'echo' => false,
                        'no_of_records' => $no_of_records,
                        'format' => array('type' => 'array'),
                        'spage' => $spage
                    );
                }

                if ($term != '') {
                    if ($gd_which_location == 'neighbourhood') {
                        $args['neighbourhood_val'] = $term;
                    }

                    if ($gd_which_location == 'city') {
                        $args['city_val'] = $term;
                    }

                    if ($gd_which_location == 'region') {
                        $args['region_val'] = $term;
                    }

                    if ($gd_which_location == 'country') {
                        $args['country_val'] = $term;
                    }
                } else {
                    if ($gd_which_location == 'country' && $country_val != '') {
                        $args_current_location = array(
                            'what' => $gd_which_location,
                            'country_val' => $country_val,
                            'compare_operator' => '=',
                            'no_of_records' => '1',
                            'echo' => false,
                            'format' => array('type' => 'array')
                        );
                        $current_location_array = geodir_get_location_array($args_current_location, true);
                    }

                    if ($gd_which_location == 'region' && $region_val != '') {
                        $args_current_location = array(
                            'what' => $gd_which_location,
                            'country_val' => $country_val,
                            'region_val' => $region_val,
                            'compare_operator' => '=',
                            'no_of_records' => '1',
                            'echo' => false,
                            'format' => array('type' => 'array')
                        );
                        $current_location_array = geodir_get_location_array($args_current_location, true);
                    }

                    if ($gd_which_location == 'city' && $city_val != '') {
                        $args_current_location = array(
                            'what' => $gd_which_location,
                            'country_val' => $country_val,
                            'region_val' => $region_val,
                            'city_val' => $city_val,
                            'compare_operator' => '=',
                            'no_of_records' => '1',
                            'echo' => false,
                            'format' => array('type' => 'array')
                        );
                        $current_location_array = geodir_get_location_array($args_current_location, true);
                    }

                    if ($gd_which_location == 'neighbourhood' && $neighbourhood_val != '') {
                        $args_current_location = array(
                            'what' => $gd_which_location,
                            'country_val' => $country_val,
                            'region_val' => $region_val,
                            'city_val' => $city_val,
                            'neighbourhood_val' => $neighbourhood_val,
                            'compare_operator' => '=',
                            'no_of_records' => '1',
                            'echo' => false,
                            'format' => array('type' => 'array')
                        );

                        $current_location_array = geodir_get_location_array($args_current_location, true);
                    }
                    // if not searching then set to get exact matches
                    $args['compare_operator'] = 'in';
                }

                $location_array = geodir_get_location_array($args, true);
                // get country val in case of country search to get selected option

                if (!isset($_REQUEST['lscroll']))
                    echo '<option value="" disabled="disabled" style="display:none;" selected="selected">' . __('Select', 'geodirlocation') . '</option>';

                if ( geodir_get_option( 'lm_everywhere_in_' . $gd_which_location . '_dropdown' ) && !isset($_REQUEST['lscroll'])) {
                    echo '<option value="' . $base_location . '">' . __('Everywhere', 'geodirlocation') . '</option>';
                }

                $selected = '';
                $loc_echo = '';

                if (!empty($location_array)) {
                    foreach ($location_array as $locations) {
                        $selected = '';
                        $with_parent = isset($locations->label) ? true : false;

                        switch ($gd_which_location) {
                            case 'country':
                                if (strtolower($country_val) == strtolower(stripslashes($locations->country))) {
                                    $selected = ' selected="selected"';
                                }
                                $locations->country = __(stripslashes($locations->country), 'geodirectory');
                                break;
                            case 'region':
                                $country_iso2 = $geodirectory->location->get_country_iso2($country_val);
                                $country_iso2 = $country_iso2 != '' ? $country_iso2 : $country_val;
                                $with_parent = $with_parent && strtolower($region_val . ', ' . $country_iso2) == strtolower(stripslashes($locations->label)) ? true : false;
                                if (strtolower($region_val) == strtolower(stripslashes($locations->region)) || $with_parent) {
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'city':
                                $with_parent = $with_parent && strtolower($city_val . ', ' . $region_val) == strtolower(stripslashes($locations->label)) ? true : false;
                                if (strtolower($city_val) == strtolower(stripslashes($locations->city)) || $with_parent) {
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'neighbourhood':
                                $with_parent = $with_parent && strtolower($neighbourhood_val . ', ' . $city_val) == strtolower(stripslashes($locations->label)) ? true : false;
                                if (strtolower($neighbourhood_val) == strtolower(stripslashes($locations->neighbourhood)) || $with_parent) {
                                    $selected = ' selected="selected"';
                                }
                                break;
                        }

                        echo '<option value="' . geodir_location_permalink_url($base_location . $locations->location_link) . '"' . $selected . '>' . ucwords(stripslashes($locations->{$gd_which_location})) . '</option>';

                        if (!$item_set_selected && $selected != '') {
                            $item_set_selected = true;
                        }
                    }
                }

                if (!empty($current_location_array) && !$item_set_selected && !isset($_REQUEST['lscroll'])) {
                    foreach ($current_location_array as $current_location) {
                        $selected = '';
                        $with_parent = isset($current_location->label) ? true : false;

                        switch ($gd_which_location) {
                            case 'country':
                                if (strtolower($country_val) == strtolower(stripslashes($current_location->country))) {
                                    $selected = ' selected="selected"';
                                }
                                $current_location->country = __(stripslashes($current_location->country), 'geodirectory');
                                break;
                            case 'region':
                                $country_iso2 = $geodirectory->location->get_country_iso2($country_val);
                                $country_iso2 = $country_iso2 != '' ? $country_iso2 : $country_val;
                                $with_parent = $with_parent && strtolower($region_val . ', ' . $country_iso2) == strtolower(stripslashes($current_location->label)) ? true : false;
                                if (strtolower($region_val) == strtolower(stripslashes($current_location->region)) || $with_parent) {
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'city':
                                $with_parent = $with_parent && strtolower($city_val . ', ' . $region_val) == strtolower(stripslashes($current_location->label)) ? true : false;
                                if (strtolower($city_val) == strtolower(stripslashes($current_location->city)) || $with_parent) {
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'neighbourhood':
                                $with_parent = $with_parent && strtolower($neighbourhood_val . ', ' . $city_val) == strtolower(stripslashes($locations->label)) ? true : false;
                                if (strtolower($neighbourhood_val) == strtolower(stripslashes($locations->neighbourhood)) || $with_parent) {
                                    $selected = ' selected="selected"';
                                }
                                break;
                        }

                        echo '<option value="' . geodir_location_permalink_url($base_location . $current_location->location_link) . '"' . $selected . '>' . ucwords(stripslashes($current_location->{$gd_which_location})) . '</option>';
                    }
                }
                exit;
            }
                break;
            case 'fill_location_on_add_listing' :
                $selected = '';
                $country_val = (isset($_REQUEST['country_val'])) ? $_REQUEST['country_val'] : '';
                $region_val = (isset($_REQUEST['region_val'])) ? $_REQUEST['region_val'] : '';
                $city_val = (isset($_REQUEST['city_val'])) ? $_REQUEST['city_val'] : '';
                $compare_operator = (isset($_REQUEST['compare_operator'])) ? $_REQUEST['compare_operator'] : '=';
				$search_term = '';

                if (isset($_REQUEST['term']) && $_REQUEST['term'] != '') {
                    if ($_REQUEST['gd_which_location'] == 'region') {
                        $region_val = $_REQUEST['term'];
                        $city_val = '';
						$search_term = 'region';
                    } else if ($_REQUEST['gd_which_location'] == 'city') {
                        $city_val = $_REQUEST['term'];
						$search_term = 'city';
                    }
                }

                if ($_REQUEST['gd_which_location'] != 'neighbourhood') {
                    $args = array(
                        'what' => $_REQUEST['gd_which_location'],
                        'country_val' => (strtolower($_REQUEST['gd_which_location']) == 'region' || strtolower($_REQUEST['gd_which_location']) == 'city') ? wp_unslash($country_val) : '',
                        'region_val' => wp_unslash($region_val),
                        'city_val' => wp_unslash($city_val),
                        'echo' => false,
                        'compare_operator' => $compare_operator,
						'search_term' => $search_term,
                        'format' => array('type' => 'array')
                    );
                    $location_array = geodir_get_location_array($args);
                } else {
                    $neighbourhood_val = !empty($_REQUEST['neighbourhood_val']) ? sanitize_text_field($_REQUEST['neighbourhood_val']) : '';
                    geodir_get_neighbourhoods_dl(wp_unslash($city_val), wp_unslash($neighbourhood_val));
                    exit();
                }

                // get country val in case of country search to get selected option

                if ($_REQUEST['gd_which_location'] == 'region')
                    echo '<option value="">' . __('Select Region', 'geodirlocation') . '</option>';
                else
                    echo '<option value="">' . __('Select City', 'geodirlocation') . '</option>';

                if (!empty($location_array)) {
                    foreach ($location_array as $locations) {
                        $selected = '';
                        switch ($_REQUEST['gd_which_location']) {
                            case 'country':
                                if (strtolower($country_val) == strtolower(stripslashes($locations->country)))
                                    $selected = "selected='selected' ";
                                break;
                            case 'region':
                                if (strtolower($region_val) == strtolower(stripslashes($locations->region)))
                                    $selected = "selected='selected' ";
                                break;
                            case 'city':
                                if (strtolower($city_val) == strtolower(stripslashes($locations->city)))
                                    $selected = "selected='selected' ";
                                break;

                        }
                        echo '<option ' . $selected . 'value="' . ucwords(stripslashes($locations->{$_REQUEST['gd_which_location']})) . '">' . ucwords(stripslashes($locations->{$_REQUEST['gd_which_location']})) . '</option>';
                    }

                } else {
                    if (isset($_REQUEST['term']) && $_REQUEST['term'] != '')
                        echo '<option value="' . wp_unslash(sanitize_text_field($_REQUEST['term'])) . '" >' . wp_unslash(sanitize_text_field($_REQUEST['term'])) . '</option>';
                }
                exit();
                break;
            case 'get_location_options':
                $return = array();
                if (!isset($_REQUEST['cn'])) {
                    echo json_encode($return);
                    exit;
                }
                $args = array();
                $args['filter_by_non_restricted'] = false;
                $args['location_link_part'] = false;
                $args['compare_operator'] = '=';
                $args['country_column_name'] = 'country';
                $args['region_column_name'] = 'region';
                $args['country_val'] = $_REQUEST['cn'];

                $args['fields'] = 'region AS title, region_slug AS slug';
                $args['order'] = 'region';
                $args['group_by'] = 'region_slug';

                if (isset($_REQUEST['rg'])) {
                    $args['region_val'] = $_REQUEST['rg'];

                    $args['fields'] = 'city AS title, city_slug AS slug';
                    $args['order'] = 'city';
                    $args['group_by'] = 'city_slug';
                }

                $gd_locations = geodir_location_get_locations_array($args);

                $options = '';
                if (!empty($gd_locations)) {
                    foreach ($gd_locations as $location) {
                        $options .= '<option data-slug="' . esc_attr($location->slug) . '" value="' . esc_attr(stripslashes($location->title)) . '">' . __(stripslashes($location->title), 'geodirectory') . '</option>';
                    }
                }
                $return['options'] = $options;
                echo json_encode($return);
                exit;
                break;
        }
    }
}

// AJAX Handler ends//

/**************************
 * /* LOCATION SWITCHER IN NAV
 ***************************/



/**************************
 * /* Filters and Actions for other addons
 ***************************/
add_filter('geodir_breadcrumb', 'geodir_location_breadcrumb', 1, 2);


add_filter('geodir_add_listing_map_restrict', 'geodir_remove_listing_map_restrict', 1, 1);
/**
 * Allow marker to be dragged beyond the range of default city when Multilocation is enabled.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param bool $restrict Whether to restrict the map?
 * @return bool
 */
function geodir_remove_listing_map_restrict($restrict)
{
    return false;
}

add_filter('geodir_home_map_enable_location_filters', 'geodir_home_map_enable_location_filters', 1);
/**
 * Enable location filter on home page.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param bool $enable True if location filters should be used, false if not.
 * @return bool
 */
function geodir_home_map_enable_location_filters($enable)
{
    return $enable = true;
}

//add_filter('geodir_home_map_listing_where', 'geodir_default_location_where', 1);
//add_filter('geodir_cat_post_count_where', 'geodir_default_location_where', 1, 2);

add_action('geodir_create_new_post_type', 'geodir_after_custom_detail_table_create', 1, 2);
/**
 * Add neighbourhood column in custom post detail table.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 *
 * @param string $post_type The post type.
 * @param string $detail_table The detail table name.
 */
function geodir_after_custom_detail_table_create($post_type, $detail_table = '')
{
    global $wpdb, $plugin_prefix;
    $post_types = geodir_get_posttypes();
    if ($detail_table == '')
        $detail_table = geodir_db_cpt_table( $post_type );

    if (in_array($post_type, $post_types)) {
        $meta_field_add = "VARCHAR( 100 ) NULL";
        geodir_add_column_if_not_exist($detail_table, "neighbourhood", $meta_field_add);
    }

    if (GEODIRLOCATION_VERSION <= '1.5.0') {
        geodir_location_fix_neighbourhood_field_limit_150();
    }
}

/**************************
 * /* DATABASE OPERATION RELATED FILTERS AND ACTIONS
 ***************************/
add_filter('geodir_get_location_by_id', 'geodir_get_location_by_id', 1, 2); // this function is in geodir_location_functions.php
add_filter('geodir_default_latitude', 'geodir_location_default_latitude', 1, 2);
add_filter('geodir_default_longitude', 'geodir_location_default_longitude', 1, 2);

add_action('geodir_get_new_location_link', 'geodir_get_new_location_link', 1, 3);


add_filter('geodir_auto_change_map_fields', 'geodir_location_auto_change_map_fields', 1, 1);

add_action('geodir_update_marker_address', 'geodir_location_update_marker_address', 1, 1);

add_action('geodir_add_listing_js_start', 'geodir_location_autofill_address', 1, 1);

add_filter('geodir_codeaddress', 'geodir_location_codeaddress', 1, 1);


/**
 * Change the address code when add neighbourhood request.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param string $codeAddress Row of address to use in google map.
 * @return string Filtered codeAddress.
 */
function geodir_location_codeaddress($codeAddress)
{

    if (isset($_REQUEST['add_hood']) && $_REQUEST['add_hood'] != '') {

        ob_start(); ?>
        address = jQuery("#hood_name").val();
        <?php $codeAddress = ob_get_clean();

    }
    return $codeAddress;
}

/**
 * Set auto change map fields to false when add neighbourhood request.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param bool $change Whether to auto fill country, state, city values in fields.
 * @return bool
 */
function geodir_location_auto_change_map_fields($change)
{

    if (isset($_REQUEST['add_hood']) && $_REQUEST['add_hood'] != '') {
        $change = false;
    }
    return $change;
}

add_filter('geodir_googlemap_script_extra', 'geodir_location_map_extra', 1, 1);
/**
 * Add map extras.
 *
 * @since 1.0.0
 * @since 1.5.1 Function geodir_is_page() used to identify add listing page.
 * @package GeoDirectory_Location_Manager
 *
 * @param string $prefix The string to filter, default is empty string.
 * @return string
 */
function geodir_location_map_extra($prefix = '') {
    global $pagenow;

    if (geodir_is_page('add-listing') || (is_admin() && ($pagenow == 'post.php' || isset($_REQUEST['post_type'])))) {
        return "&libraries=places";
    }
}

/**
 * Adds js to autofill the address.
 *
 * @since 1.0.0
 * @since 1.5.1 Function geodir_is_page() used to identify add listing page.
 * @package GeoDirectory_Location_Manager
 *
 * @global string $pagenow The current screen.
 *
 * @param string $prefix The prefix for all elements.
 */
function geodir_location_autofill_address( $prefix = '' ) {
	global $pagenow, $geodirectory;

	if ( geodir_is_page( 'add-listing' ) || ( is_admin() && ( $pagenow == 'post.php' || isset( $_REQUEST['post_type'] ) ) ) ) {
		$country_option = geodir_get_option( 'lm_default_country' );
		$country_codes = array();
		if ( $country_option == 'default' ) {
			$default_location = $geodirectory->location->get_default_location();

			if ( ! empty( $default_location ) ) {
				$country_codes[] = $geodirectory->location->get_country_iso2( $default_location->country );
			}
		} elseif ( $country_option == 'selected' && ( $selected_countries = (array) geodir_get_option( 'lm_selected_countries' ) ) ) {
			if ( ! empty( $selected_countries ) && count( $selected_countries ) < 6 ) { // we can only filter by 5 countries
				foreach( $selected_countries as $country_name ) {
					if ( $country_code = $geodirectory->location->get_country_iso2( $country_name ) ) {
						$country_codes[] = $country_code;
					}
				}
			}
		}
		if ( geodir_get_option( 'lm_location_address_fill' ) ) {
		} else {
			$_country_codes = ! empty( $country_codes ) ? ",countrycodes: '" . strtolower( implode( ',', $country_codes ) ) . "'" : '';
			?>
	var gdlmKeyupTimeout = null, gdlmData;
	jQuery(function($) {<?php if ( geodir_lazy_load_map() ) { ?>
	jQuery('input[name="street"]').geodirLoadMap({
		loadJS: true,
		callback: function() {<?php } ?>
		// Setup OpenStreetMap autocomplete address search
		if (window.gdMaps == 'osm' && $('input[name="street"]').length) {
			<?php if ( geodir_design_style() ) { ?>
			geodir_aui_osm_autocomplete_init();
			<?php } else { ?>
			geodir_osm_autocomplete_search(this);
			<?php } ?>
		}

		initialize_autofill_address();<?php if ( geodir_lazy_load_map() ) { ?>
		}
	});<?php } ?>
	});
	<?php if ( geodir_design_style() ) { ?>
	function geodir_aui_osm_autocomplete_init() {
		jQuery('input[name="street"]').after("<div class='dropdown-menu dropdown-caret-0 w-100 show scrollbars-ios overflow-auto p-0 m-0 gd-suggestions-dropdown gdlm-street-suggestions gd-ios-scrollbars'><ul class='gdlmls-street list-unstyled p-0 m-0'></ul></div>");

		jQuery('input[name="street"]').on('keyup', function(e) {
			if (gdlmKeyupTimeout != null) {
				clearTimeout(gdlmKeyupTimeout);
			}
			gdlmKeyupTimeout = setTimeout(geodir_aui_osm_autocomplete_search(this), 500);
		});

		jQuery('input[name="street"]').on('focus', function(e){
			if(jQuery(this).parent().find(".gdlmls-street .list-group-item-action").length){
				jQuery(".gdlm-street-suggestions").show();
			}
		})

		jQuery('body').on('click', function(e){
			if (jQuery(e.target).closest(".input-group").find("input[name='street']").length) {
			} else {
				jQuery(".gdlm-street-suggestions").hide();
			}
		})
	}

	function geodir_aui_osm_autocomplete_search(el) {
		var $form, term, 
		$form = jQuery(el).closest('form');

		gdlmKeyupTimeout = null; // Reset timeout
		term = jQuery(el).val();
		term = term ? term.trim() : '';
		if (term) {
			jQuery.ajax({
				url: 'https://nominatim.openstreetmap.org/search',
				dataType: "json",
				data: {
					q: term,
					format: 'json',
					addressdetails: 1,
					limit: 5,
					'accept-language': geodir_params.mapLanguage<?php echo $_country_codes; ?>
					<?php do_action( 'geodir_location_osm_autocomplete_address_search_params' ); ?>
				},
				success: function(data, textStatus, jqXHR) {
					var items = '';
					if (data) {
						gdlmData = data;
						jQuery.each(data, function(i, value) {
							items += geodir_aui_osm_autocomplete_li(value, term, i);
						});
					}

					jQuery(el).parent().find("ul.gdlmls-street").empty().append(items);

					if (items) {
						jQuery(".gdlm-street-suggestions").show();
					} else {
						jQuery(".gdlm-street-suggestions").hide();
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.log(errorThrown);
				},
				complete: function(jqXHR, textStatus) {
				}
			});
		} else {
			jQuery(el).parent().find("ul.gdlmls-street").empty();
		}
	}

	function geodir_aui_osm_autocomplete_li(address, term, i) {
		var output, label;
		label = address.display_name;
		if (label && term) {
			label = gd_highlight(label, term);
			label = label.replace(' class="gdOH"', ' class="gdOH text-dark"');
		}
		output = '<li class="list-group-item-action c-pointer px-1 py-1 m-0 d-flex small text-muted" ontouchstart="this.click();return false;" onclick="geodir_aui_osm_autocomplete_select(this, '+ i +');">';
		output += '<i class="fas fa-map-marker-alt mr-1 me-1" aria-hidden="true"></i><span>' + label + '</span>';
		output += '</li>';
		return output;
	}

	function geodir_aui_osm_autocomplete_select(el, i) {
		address = gd_osm_parse_item(gdlmData[i]);
		jQuery(el).closest('form').find('input[name="street"]').val(address.display_address);
		jQuery(".gdlm-street-suggestions").hide();
		jQuery(el).closest(".gdlm-street-suggestions").find("ul.gdlmls-street").empty();
		geocodeResponseOSM(address, true);
	}

	<?php } else { ?>
	function geodir_osm_autocomplete_search() {
		try {
			if (window.gdMaps == 'osm' && jQuery('input[name="street"]').length) {
				$form = jQuery('input[name="street"]').closest('form');
				jQuery('input[name="street"]', $form).autocomplete({
					source: function(request, response) {
						jQuery.ajax({
							url: (location.protocol === 'https:' ? 'https:' : 'https:') + '//nominatim.openstreetmap.org/search',
							dataType: "json",
							data: {
								q: request.term,
								format: 'json',
								addressdetails: 1,
								limit: 5,
								'accept-language': geodir_params.mapLanguage<?php echo $_country_codes; ?>
							},
							success: function(data, textStatus, jqXHR) {
								jQuery('input[name="street"]', $form).removeClass('ui-autocomplete-loading');
								response(data);
							},
							error: function(jqXHR, textStatus, errorThrown) {
								console.log(errorThrown);
							},
							complete: function(jqXHR, textStatus) {
								jQuery('input[name="street"]', $form).removeClass('ui-autocomplete-loading');
							}
						});
					},
					autoFocus: true,
					minLength: 1,
					appendTo: jQuery('input[name="street"]', $form).closest('.geodir_form_row'),
					open: function(event, ui) {
						jQuery('input[name="street"]', $form).removeClass('ui-autocomplete-loading');
					},
					select: function(event, ui) {
						item = gd_osm_parse_item(ui.item);
						event.preventDefault();
						jQuery('input[name="street"]', $form).val(item.display_address);
						geocodeResponseOSM(item, true);
					},
					close: function(event, ui) {
						jQuery('input[name="street"]', $form).removeClass('ui-autocomplete-loading');
					}
				}).autocomplete("instance")._renderItem = function(ul, item) {
					if (!ul.hasClass('gd-osm-results')) {
						ul.addClass('gd-osm-results');
					}

					var label = item.display_name;
					if (label && this.term) {
						label = gd_highlight(label, this.term);
					}

					return jQuery("<li>").width(jQuery('input[name="street"]', $form).outerWidth()).append('<i class="fas fa-map-marker-alt" aria-hidden="true"></i><span>' + label + '</span>').appendTo(ul);
				};
			}
		} catch (err) {
			console.log(err.message);
		}
	}
<?php } } ?>

	var placeSearch, autocomplete;
	var componentForm = {
		street_number: 'short_name',
		route: 'long_name',
		locality: 'long_name',
		administrative_area_level_1: 'short_name',
		country: 'long_name',
		postal_code: 'short_name'
	};

	function initialize_autofill_address() {
		var options = {
		<?php
		/**
		 * Filter the types of addresses to auto complete.
		 *
		 * ['geocodes'] work best but other users may want to filter this.
		 */
		echo apply_filters("goedir_lm_autofill_address_types","types: ['geocode'],");
		if ( ! empty( $country_codes ) && count( $country_codes ) < 6 ) {
			$_country_codes = count( $country_codes ) > 1 ? "['" . implode( "','", $country_codes ) . "']" : "'" . $country_codes[0] . "'";
			echo 'componentRestrictions: {country: ' . $_country_codes . '}';
		}
		?>
		};

		if (window.gdMaps == 'google' && typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
			// Create the autocomplete object, restricting the search
			// to geographical location types.
			autocomplete = new google.maps.places.Autocomplete(
				/** @type {HTMLInputElement} */
				(document.getElementById('<?php echo $prefix . 'street'; ?>')), options);
			// When the user selects an address from the dropdown,
			// populate the address fields in the form.
			google.maps.event.addListener(autocomplete, 'place_changed', function() {
				geodir_fillInAddress();
			});
		}
	}

	// [START region_fillform]
	function geodir_fillInAddress() {
		// Get the place details from the autocomplete object.
		var place = autocomplete.getPlace();
		// blank fields
		jQuery("#<?php echo $prefix . 'country'; ?>").val('').trigger('change.select2');
		if (!jQuery('#<?php echo $prefix . 'region'; ?> option[value=""]').length) {
			jQuery("#<?php echo $prefix . 'region'; ?>").append('<option value=""><?php _e( 'Select Region', 'geodirlocation' ); ?></option>');
		}
		jQuery("#<?php echo $prefix . 'region'; ?>").val('').trigger('change.select2');
		if (!jQuery('#<?php echo $prefix . 'city'; ?> option[value=""]').length) {
			jQuery("#<?php echo $prefix . 'city'; ?>").append('<option value=""><?php _e( 'Select City', 'geodirlocation' ); ?></option>');
		}
		jQuery("#<?php echo $prefix . 'city'; ?>").val('').trigger('change.select2');
		jQuery('#<?php echo $prefix . 'zip'; ?>').val('');

		var newArr = new Array();
		newArr[0] = place;
		user_address = false; // set the user address as NOT changed so the selected address is inserted.
		geocodeResponse(newArr);

		user_address = true; // set the user address as changed so its not overwritten by map move.
		geodir_codeAddress(true);
	}
	// [END region_fillform]
	<?php
	}
}

/**
 * Updates marker address.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global object $geodirectory GeoDirectory object.
 *
 * @param string $prefix Identifier used as a prefix for field name
 */
function geodir_location_update_marker_address( $prefix = '' ) {
	global $pagenow, $wpdb, $geodirectory;

	if ( ( ( is_page() && geodir_is_page( 'add-listing' ) ) ) || ( is_admin() && ( $pagenow == 'post.php' || isset( $_REQUEST['post_type'] ) ) ) ) {
		$country_option = geodir_get_option( 'lm_default_country' );
		$region_option = geodir_get_option( 'lm_default_region' );
		$city_option = geodir_get_option( 'lm_default_city' );
		$neighbourhood_active = geodir_get_option( 'lm_enable_neighbourhoods' );

		$default_country = '';
		$default_region = '';
		$default_city = '';

		$location = $geodirectory->location->get_default_location();

		if (empty($default_city)) $default_city = isset($location->city) ? $location->city : '';
		if (empty($default_region)) $default_region = isset($location->region) ? $location->region : '';
		if (empty($default_country)) $default_country = isset($location->country) ? $location->country : '';

		$default_lat = apply_filters('geodir_default_latitude', $location->latitude, true);
		$default_lng = apply_filters('geodir_default_longitude', $location->longitude, true);

		$selected_countries = array();
		if (geodir_get_option( 'lm_selected_countries' ))
			$selected_countries = geodir_get_option( 'lm_selected_countries' );

		$selected_regions = array();
		if (geodir_get_option( 'lm_selected_regions' ))
			$selected_regions = geodir_get_option( 'lm_selected_regions' );

		$selected_cities = array();
		if (geodir_get_option( 'lm_selected_cities' ))
			$selected_cities = geodir_get_option( 'lm_selected_cities' );
	?>

        // Initialize the global timestamp if it hasn't been already
        if (typeof window.gdLastCallTime === 'undefined') {
        window.gdLastCallTime = 0; // Ensures this block runs only once
        }

        const now = Date.now();
        let gdShouldExecuteRateLimitedCode = false;

        // Check if more than 1 second has passed since the last execution
        if (now - window.gdLastCallTime >= 1000) {
        window.gdLastCallTime = now; // Update the last call time
        gdShouldExecuteRateLimitedCode = true; // Allow rate-limited code to run
        }

        // if we don't have a state yet then try running moving the marker to find one
        if (gdShouldExecuteRateLimitedCode && !getState && jQuery("#<?php echo $prefix.'region';?>").length ) {
        setTimeout(function() {
        if(window.gdMaps == 'google'){
            geocodePosition(baseMarker.getPosition());
        }else if(window.gdMaps == 'osm'){
            updateMarkerPositionOSM(baseMarker.getLatLng());
        }

        }, 500);

        }


	var error = false;
	var loc_error_checking_start_count = 0;
	var loc_error_checking_end_count = 0;
	<?php
		if ($country_option == 'default' || $country_option == 'selected') {
			echo "loc_error_checking_start_count++;";
		}
		if ($region_option == 'default' || $region_option == 'selected') {
			echo "loc_error_checking_start_count++;";
		}
		if ($city_option == 'default' || $city_option == 'selected') {
			echo "loc_error_checking_start_count++;";
		}

		if ($country_option == 'default') {
			$countries_ISO2 = $geodirectory->location->get_country_iso2($default_country);
	?>
	if ('<?php echo $countries_ISO2; ?>' != getCountryISO && error == false) {
		error = true;
		alert('<?php echo addslashes_gpc( wp_sprintf(__('Please choose any address of the (%s) country only.', 'geodirlocation'), __( $default_country, 'geodirectory' ) ) ); ?>');
		loc_error_checking_end_count=loc_error_checking_start_count;
	} else {
		loc_error_checking_end_count++;
	}
	<?php
		} elseif ($country_option == 'selected') {
			if (is_array($selected_countries) && !empty($selected_countries)) {
				$selected_countries_string = implode(',', $selected_countries);

				if (count($selected_countries) > 1) {
					$selected_countries_string = wp_sprintf(__('Please choose any address of the (%s) countries only.', 'geodirlocation'), implode(',', $selected_countries));
				} else {
					$selected_countries_string = wp_sprintf(__('Please choose any address of the (%s) country only.', 'geodirlocation'), implode(',', $selected_countries));
				}

			} else {
				$selected_countries_string = __('No countries available.', 'geodirlocation');
			}

			if ( function_exists( 'geodir_wp_countries' ) ) {
				$countries_ISO2 = geodir_wp_countries(
					array(
						'fields' => array( 'alpha2Code' ),
						'in'     => array( 'name' => $selected_countries )
					)
				);
			} else {
				$countries_ISO2 = wp_country_database()->get_countries(
					array(
						'fields' => array( 'alpha2Code' ),
						'in'     => array( 'name' => $selected_countries )
					)
				);
			}
			$cISO_arr = array();
			foreach ($countries_ISO2 as $cIOS2) {
				$cISO_arr[] = $cIOS2->alpha2Code;
			}
			if ( geodir_split_uk() && ! empty( $selected_countries ) ) {
				foreach ( $selected_countries as $_country ) {
					if ( $_country && in_array( strtolower( $_country ), array( "england", "northern ireland", "scotland", "wales" ) ) ) {
						$cISO_arr[] = 'GB';
						break;
					}
				}
			}
			?>
	var country_array = <?php echo json_encode($cISO_arr); ?>;

	if (error == false && getCountryISO && jQuery.inArray( getCountryISO, country_array ) == -1  ) {
		error = true;
		alert('<?php echo addslashes_gpc( $selected_countries_string ); ?>');
		loc_error_checking_end_count++;
	} else {
		loc_error_checking_end_count++;
	}
		<?php } ?>
	if (getCountry && getCity && error == false) {
		jQuery.post(geodir_params.gd_ajax_url, {
				action: 'geodir_set_region_on_map',
				country: getCountry,
				region: getState,
				city: getCity
			}, "json").done(function(data) {
					data = data && data.data && data.data.html ? data.data.html : '';
					if (jQuery.trim(data) != '') {
						getState = data;
					}
					<?php if ($region_option == 'default') { ?>
					if ('<?php echo geodir_strtolower(esc_attr($default_region)); ?>' != getState.toLowerCase() && error == false) {
						error = true;
						alert('<?php echo addslashes_gpc( wp_sprintf( __( 'Please choose any address of the (%s) region only.', 'geodirlocation' ), $default_region ) ); ?>');
						loc_error_checking_end_count++;
					} else {
						loc_error_checking_end_count++;
					}
					<?php } elseif ($region_option == 'selected') {
					$selected_regions_string = '';
					if (is_array($selected_regions) && !empty($selected_regions)) {
						$selected_regions_string = implode(',', $selected_regions);

						if (count($selected_regions) > 1) {
							$selected_regions_string = wp_sprintf(__('Please choose any address of the (%s) regions only.', 'geodirlocation'), implode(',', $selected_regions));
						} else {
							$selected_regions_string = wp_sprintf(__('Please choose any address of the (%s) region only.', 'geodirlocation'), implode(',', $selected_regions));
						}
					}
					?>
					var region_array = <?php echo json_encode(stripslashes_deep($selected_regions)); ?>;
					region_array = jQuery.map(region_array, function(n){return n.toLowerCase();});

					if (jQuery.inArray(getState.toLowerCase(), region_array) == -1 && error == false && region_array.length > 0) {
						error = true;
						alert('<?php echo addslashes_gpc( $selected_regions_string ); ?>');
						loc_error_checking_end_count++;
					} else {
						loc_error_checking_end_count++;
					}
				<?php } ?>
				<?php if ( $city_option == 'default' ) { ?>
					if ('<?php echo geodir_strtolower(esc_attr($default_city)); ?>' != getCity.toLowerCase() && error == false) {
						error = true;
						alert('<?php echo addslashes_gpc( wp_sprintf( __( 'Please choose any address of the (%s) city only.', 'geodirlocation' ), $default_city ) ); ?>');
						loc_error_checking_end_count++;
					} else {
						loc_error_checking_end_count++;
					}
				<?php } elseif ( $city_option == 'selected' ) {
				$selected_cities_string = '';
				if (is_array($selected_cities) && !empty($selected_cities)) {
					if (count($selected_cities) > 1) {
						$selected_cities_string = wp_sprintf(__('Please choose any address of the (%s) cities only.', 'geodirlocation'), implode(',', $selected_cities));
					} else {
						$selected_cities_string = wp_sprintf(__('Please choose any address of the (%s) city only.', 'geodirlocation'), implode(',', $selected_cities));
					}
				}
				?>
					var city_array = <?php echo json_encode(stripslashes_deep($selected_cities)); ?>;
					city_array = jQuery.map(city_array, function(n) {
						return n.toLowerCase();
					});

					if (jQuery.inArray(getCity.toLowerCase(), city_array) == -1 && error == false && city_array.length > 0) {
						error = true;
						alert('<?php echo addslashes_gpc($selected_cities_string); ?>');
						loc_error_checking_end_count++;
					} else {
						loc_error_checking_end_count++;
					}
			<?php } ?>
		});
	}

	function gd_location_error_done() {
		if (loc_error_checking_start_count != loc_error_checking_end_count) {
			setTimeout(function() {
				gd_location_error_done();
			}, 100);
		} else {
			if (error == false) {
				var mapLang = '<?php echo GeoDir_Maps::map_language();?>';
				var countryChanged = jQuery.trim(old_country) != jQuery.trim(getCountry) ? true : false;
				old_country = jQuery.trim(old_country);
				if (mapLang != 'en' && old_country) {
					<?php if ($country_option == 'default') { ?>
					var oldISO2 = jQuery('input#<?php echo $prefix . 'country'; ?>').attr('data-country_code');
					<?php } else { ?>
					var oldISO2 = jQuery('#<?php echo $prefix . 'country'; ?> option[value="' + old_country + '"]').attr('data-country_code');
					<?php } ?>
					if (oldISO2 && oldISO2 == getCountryISO) {
						countryChanged = false;
					}
				}

				if (countryChanged) {
					jQuery('select#<?php echo $prefix . 'region'; ?>').html('');
					if (getState) {
						jQuery("#<?php echo $prefix . 'region'; ?>").append('<option value="' + getState + '">' + getState + '</option>');
					}
					jQuery("#<?php echo $prefix . 'region'; ?>").val(getState).trigger("change.select2");
					jQuery('select#<?php echo $prefix . 'city'; ?>').html('');
					if (getCity) {
						jQuery("#<?php echo $prefix . 'city'; ?>").append('<option value="' + getCity + '">' + getCity + '</option>');
					}
					jQuery("#<?php echo $prefix . 'city'; ?>").val(getCity).trigger("change.select2");
				}
				if (jQuery.trim(old_region) != jQuery.trim(getState)) {
					jQuery('select#<?php echo $prefix . 'city'; ?>').html('');
					if (getCity) {
						jQuery("#<?php echo $prefix . 'city'; ?>").append('<option value="' + getCity + '">' + getCity + '</option>');
					}
					jQuery("#<?php echo $prefix . 'city'; ?>").val(getCity).trigger("change.select2");
				}

				if (getCountry) {
					jQuery("#<?php echo $prefix . 'country'; ?>").val(getCountry).trigger("change.select2");
				}

				if (getZip) {
					if (getCountryISO == 'SK' || getCountryISO == 'TR' || getCountryISO == 'DK' || getCountryISO == 'ES' || getCountryISO == 'CZ' || getCountryISO == 'LV' || getCountryISO == 'HU' || getCountryISO == 'GR') {
						geodir_region_fix(getCountryISO, getZip, '<?php echo $prefix; ?>');
					}
				}
				
				if (getState) {
					if (jQuery("#<?php echo $prefix . 'region'; ?> option:contains(" + getState + ")").length == 0) {
						jQuery("#<?php echo $prefix . 'region'; ?>").append('<option value="' + getState + '">' + getState + '</option>');
					}
					jQuery("#<?php echo $prefix . 'region'; ?>").val(getState).trigger("change.select2");
				}

				if (getCity) {
					if (jQuery("#<?php echo $prefix . 'city'; ?> option:contains(" + getCity + ")").length == 0) {
						jQuery("#<?php echo $prefix . 'city'; ?>").append('<option value="' + getCity + '">' + getCity + '</option>');
					}
					jQuery("#<?php echo $prefix . 'city'; ?>").val(getCity).trigger("change.select2");
					jQuery("#<?php echo $prefix . 'city'; ?>").trigger('change');
				}
				<?php if ($neighbourhood_active) { ?>
				if (window.neighbourhood) {
					var $neighbourhood = jQuery('#<?php echo $prefix . 'neighbourhood'; ?>');
					if ($neighbourhood.find("option:contains(" + window.neighbourhood + ")").length == 0) {
						$neighbourhood.append('<option value="' + window.neighbourhood + '">' + window.neighbourhood + '</option>');
					}
					$neighbourhood.val(window.neighbourhood).trigger("change.select2");
				}
				<?php } ?>
			} else {
				geodir_set_map_default_location('<?php echo $prefix . 'map'; ?>', '<?php echo $default_lat; ?>', '<?php echo $default_lng; ?>');
				return false;
			}

			if (error) {
				geodir_set_map_default_location('<?php echo $prefix . 'map'; ?>', '<?php echo $default_lat; ?>', '<?php echo $default_lng; ?>');
				return false;
			}
		}
	}

	gd_location_error_done();
	<?php }
}

/**
 *
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */
function geodir_add_fix_region_code() {
?> 
function geodir_region_fix(ISO2, ZIP, prefix) {
    var _wpnonce = jQuery('#gd_location').closest('form').find('#_wpnonce').val();
    jQuery.post("<?php echo GEODIR_LOCATION_PLUGIN_URL; ?>/assets/zip_arrays/" + ISO2 + ".php", {
        ISO2: ISO2,
		ZIP: ZIP
    }).done(function(data) {
        if (data) {
            getState = data;
            if (getState) {
                if (jQuery("#" + prefix + "<?php echo 'region'; ?> option:contains(" + getState + ")").length == 0) {
                    jQuery("#" + prefix + "<?php echo 'region'; ?>").append('<option value="' + getState + '">' + getState + '</option>');
                }
                jQuery('#' + prefix + '<?php echo 'region'; ?>').val(getState).trigger('change.select2');
            }
        }
    });
} 
<?php
}
add_filter( 'geodir_add_listing_js_start', 'geodir_add_fix_region_code' );

/*========================*/
/* ENABLE SHARE LOCATION */
/*========================*/
add_filter('geodir_ask_for_share_location', 'geodir_ask_for_share_location');
/**
 * Ask user confirmation to share location.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param bool $mode Ask the user? Default: false.
 * @return bool Filtered value.
 */
function geodir_ask_for_share_location($mode)
{
    global $gd_session;
    if ($gd_session->get('gd_location_shared') == 1 || ($gd_session->get('gd_multi_location') && !$gd_session->get('gd_location_default_loaded'))) {
        $gd_session->set('gd_location_shared', 1);
        return $mode;
    } else if (!geodir_is_geodir_page()) {
        return $mode;
    } else if (geodir_is_page('home')) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', TRUE);// do not cache if we are asking for location
        }
        return true;
    }

    return $mode;
}

add_filter('geodir_share_location', 'geodir_location_manager_share_location');

/**
 * Redirect url after sharing location.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wp_query WordPress Query object.
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param string $redirect_url Old redirect url
 * @return bool|null|string Filtered redirect url.
 */
function geodir_location_manager_share_location($redirect_url) {
    global $wpdb, $wp_query, $plugin_prefix, $gd_session;

    if (isset($_REQUEST['geodir_ajax']) && $_REQUEST['geodir_ajax'] == 'share_location') {
        $gd_session->set('gd_onload_redirect_done', 1);

        if (isset($_REQUEST['error']) && $_REQUEST['error']) {
            $gd_session->set('gd_location_shared', 1);
            return;
        }

        // ask user to share his location only one time.
        $gd_session->set('gd_location_shared', 1);

        $DistanceRadius = geodir_getDistanceRadius( geodir_get_option( 'search_distance_long' ) );

        if ( geodir_get_option( 'search_radius' ) != '' ) {
            $dist = geodir_get_option( 'search_radius' );
        } else {
            $dist = '25000';
        }
        if ( geodir_get_option( 'geodir_near_me_dist' ) != '' ) {
            $dist2 = geodir_get_option( 'geodir_near_me_dist' );
        } else {
            $dist2 = '200';
        }

        if (isset($_REQUEST['lat']) && isset($_REQUEST['long'])) {
            $mylat = (float)stripslashes(ucfirst($_REQUEST['lat']));
            $mylon = (float)stripslashes(ucfirst($_REQUEST['long']));
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
            $addr_details = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip=' . $ip));
            $mylat = stripslashes(ucfirst($addr_details['geoplugin_latitude']));
            $mylon = stripslashes(ucfirst($addr_details['geoplugin_longitude']));
        }

        $gd_session->set('user_lat', $mylat);
        $gd_session->set('user_lon', $mylon);
        $lon1 = $mylon - $dist2 / abs(cos(deg2rad($mylat)) * 69);
        $lon2 = $mylon + $dist2 / abs(cos(deg2rad($mylat)) * 69);
        $lat1 = $mylat - ($dist2 / 69);
        $lat2 = $mylat + ($dist2 / 69);

        $rlon1 = is_numeric(min($lon1, $lon2)) ? min($lon1, $lon2) : '';
        $rlon2 = is_numeric(max($lon1, $lon2)) ? max($lon1, $lon2) : '';
        $rlat1 = is_numeric(min($lat1, $lat2)) ? min($lat1, $lat2) : '';
        $rlat2 = is_numeric(max($lat1, $lat2)) ? max($lat1, $lat2) : '';

        $near_location_info = $wpdb->get_results($wpdb->prepare("SELECT *,CONVERT((%s * 2 * ASIN(SQRT( POWER(SIN((%s - (" . $plugin_prefix . "gd_place_detail.latitude)) * pi()/180 / 2), 2) +COS(%s * pi()/180) * COS( (" . $plugin_prefix . "gd_place_detail.latitude) * pi()/180) *POWER(SIN((%s - " . $plugin_prefix . "gd_place_detail.longitude) * pi()/180 / 2), 2) ))),UNSIGNED INTEGER) as distance FROM " . $plugin_prefix . "gd_place_detail WHERE (" . $plugin_prefix . "gd_place_detail.latitude IS NOT NULL AND " . $plugin_prefix . "gd_place_detail.latitude!='') AND " . $plugin_prefix . "gd_place_detail.latitude between $rlat1 and $rlat2  AND " . $plugin_prefix . "gd_place_detail.longitude between $rlon1 and $rlon2 ORDER BY distance ASC LIMIT 1", $DistanceRadius, $mylat, $mylat, $mylon));

        if (!empty($near_location_info)) {
            $redirect_url = geodir_get_location_link('base') . 'me';
            return ($redirect_url);
            die();
        }


        $location_info = $wpdb->get_results($wpdb->prepare("SELECT *,CONVERT((%s * 2 * ASIN(SQRT( POWER(SIN((%s - (" . GEODIR_LOCATIONS_TABLE . ".latitude)) * pi()/180 / 2), 2) +COS(%s * pi()/180) * COS( (" . GEODIR_LOCATIONS_TABLE . ".latitude) * pi()/180) *POWER(SIN((%s - " . GEODIR_LOCATIONS_TABLE . ".longitude) * pi()/180 / 2), 2) ))),UNSIGNED INTEGER) as distance FROM " . GEODIR_LOCATIONS_TABLE . " ORDER BY distance ASC LIMIT 1", $DistanceRadius, $mylat, $mylat, $mylon));

        if (!empty($location_info)) {
            $location_info = end($location_info);
            $location_array = array();
            $location_array['gd_country'] = $location_info->country_slug;
            $location_array['gd_region'] = $location_info->region_slug;
            $location_array['gd_city'] = $location_info->city_slug;
            $base = rtrim(geodir_get_location_link('base'), '/');
            $redirect_url = $base . '/' . $location_info->country_slug . '/' . $location_info->region_slug . '/' . $location_info->city_slug;

                $args_current_location = array(
                    'what' => 'city',
                    'country_val' => $location_info->country,
                    'region_val' => $location_info->region,
                    'city_val' => $location_info->city,
                    'compare_operator' => '=',
                    'no_of_records' => '1',
                    'echo' => false,
                    'format' => array('type' => 'array')
                );
                $current_location_array = geodir_get_location_array($args_current_location);
            if(isset($current_location_array[0])){
                $redirect_url =  $base.'/'.$current_location_array[0]->location_link;
            }
            $redirect_url = geodir_location_permalink_url($redirect_url);
        } else {
            $redirect_url = geodir_get_location_link('base');
        }

        return ($redirect_url);
    }
}


add_filter('geodir_term_slug_is_exists', 'geodir_location_term_slug_is_exists', 1, 3);
/**
 * Check term slug exists or not.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 *
 * @param bool $slug_exists Default: false.
 * @param string $slug The term slug.
 * @param int|string $term_id The term ID.
 * @return bool Filtered $slug_exists value.
 */
function geodir_location_term_slug_is_exists( $slug_exists, $slug, $term_id ) {
    global $wpdb, $table_prefix;

    if ( $slug_exists ) {
        return $slug_exists;
    }

    $slug = urldecode( $slug );

    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT location_id FROM " . GEODIR_LOCATIONS_TABLE . " WHERE country_slug = %s || region_slug = %s || city_slug = %s LIMIT 1", array( $slug, $slug, $slug ) ) );

    if ( $exists ) {
        return true;
    }

    // No longer required as we have category & tags slug now.
    //if ( $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM " . $table_prefix . "terms WHERE slug=%s AND term_id != %d", array( $slug, $term_id ) ) ) ) {
        //return true;
    //}

    return $slug_exists;
}


add_action('init', 'geodir_update_locations_default_options');
/**
 * Update the default settings of location manager.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */
function geodir_update_locations_default_options() {
    if ( ! geodir_get_option( 'geodir_update_locations_default_options' ) ) {
        if (!geodir_get_option( 'lm_default_country' ))
            geodir_update_option('lm_default_country', 'multi');

        if (!geodir_get_option( 'lm_default_region' ))
            geodir_update_option('lm_default_region', 'multi');

        if (!geodir_get_option( 'lm_default_city' ))
            geodir_update_option('lm_default_city', 'multi');

        if (!geodir_get_option( 'lm_everywhere_in_country_dropdown' ))
            geodir_update_option('lm_everywhere_in_country_dropdown', '1');

        if (!geodir_get_option( 'lm_everywhere_in_region_dropdown' ))
            geodir_update_option('lm_everywhere_in_region_dropdown', '1');

        if (!geodir_get_option( 'lm_everywhere_in_city_dropdown' ))
            geodir_update_option('lm_everywhere_in_city_dropdown', '1');

        geodir_update_option( 'geodir_update_locations_default_options', '1' );
    }

    if ( geodir_get_option( 'lm_enable_neighbourhoods' ) ) {
        add_action( 'geodir_add_listing_geocode_js_vars', 'geodir_location_grab_neighbourhood' );
    }
}

add_action('wp', 'geodir_location_temple_redirect');
/**
 * Manage canonical link on location pages.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wp WordPress object.
 */
function geodir_location_temple_redirect() {
	if ( is_page() && geodir_is_page( 'location' ) ) {
		add_action('template_redirect', 'geodir_set_location_canonical_urls', 1);

		// Location page + Rank Math SEO canonical.
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			add_filter( 'rank_math/frontend/canonical', array( 'GeoDir_Location_SEO', 'wpseo_canonical' ), 20, 1 );
		}
	}
}

/**
 * Modify canonical links.
 *
 * @since 1.0.0
 * @since 1.4.0 Filter added to fix conflict canonical url with WordPress SEO by Yoast.
 * @package GeoDirectory_Location_Manager
 */
function geodir_set_location_canonical_urls() {
    if ( defined( 'WPSEO_VERSION' ) ) {
		add_filter( 'wpseo_canonical', array( 'GeoDir_Location_SEO', 'wpseo_canonical' ), 20, 1 );
		add_filter( 'wpseo_opengraph_url', array( 'GeoDir_Location_SEO', 'wpseo_canonical' ), 20, 1 );
	} else if ( defined( 'RANK_MATH_VERSION' ) ) {
		// Rank Math SEO
	} else {
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'wp_head', 'geodir_location_rel_canonical', 9 );
	}
}

/**
 * Adds rel='canonical' tag to links.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */
function geodir_location_rel_canonical() {
	GeoDir_Location_SEO::location_canonical();
}


add_action('init', 'geodir_remove_parse_request_core');
/**
 * Removes {@see geodir_set_location_var_in_session_in_core} function from parse_request.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */
function geodir_remove_parse_request_core()
{
    remove_filter('parse_request', 'geodir_set_location_var_in_session_in_core');
}

/**
 * Adds additional description form fields to the listing category.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param WP_Term $tag      Taxonomy term object.
 * @param string  $taxonomy Taxonomy slug.
 */
function geodir_location_cat_loc_desc( $term, $taxonomy ) {
    global $wpdb, $wp_version;

    $sql = "SELECT loc.location_id, loc.country, loc.region, loc.city, loc.country_slug, loc.region_slug FROM " . GEODIR_LOCATIONS_TABLE . " AS loc ORDER BY loc.city ASC";
    $locations = $wpdb->get_results($sql);
    if ( empty( $locations ) ) {
        return;
    }

    $term_id = $term->term_id;

    $gd_desc_custom = get_term_meta( $term_id, 'gd_desc_custom', true );
    if ( isset( $_REQUEST['topdesc_type'] ) ) {
        $gd_desc_custom = true;
    }

    $show_countries = geodir_get_option( 'lm_default_country' ) == 'default' && geodir_get_option( 'lm_hide_country_part' ) ? false : true;
    $show_regions = geodir_get_option( 'lm_default_region' ) == 'default' && geodir_get_option( 'lm_hide_region_part' ) ? false : true;

    $all_countries = array();
    $all_regions = array();
    $location_options = '';
    $count = 0;
    if (isset($_REQUEST['gd_location'])) {
        $gd_location = (int)$_REQUEST['gd_location'];
    }
    foreach ($locations as $location) {
        $count++;
        $location_id = (int)$location->location_id;
        if ($count == 1 && !isset($gd_location)) {
            $gd_location = $location_id;
        }
        $country = $location->country;
        $region = $location->region;
        $city = $location->city;
        $location_name = $city . ', ' . $region . ', ' . __($country, 'geodirectory');
        $selected = $gd_location == $location_id ? 'selected="selected"' : '';
        if ($gd_location == $location_id) {
            $gd_location_name = $location_name;
        }
        $location_options .= '<option value="' . $location_id . '" ' . $selected . '>' . $location_name . '</option>';

        if (empty($all_countries[$location->country_slug])) {
            $all_countries[$location->country_slug] = __($country, 'geodirectory');
        }

        if ($show_regions && empty($all_regions[$location->country_slug][$location->region_slug])) {
            $all_regions[$location->country_slug][$location->region_slug] = $region;
        }
    }

    if ( isset( $_REQUEST['gd_cat_loc'] ) ) {
        $gd_cat_loc_desc = $_REQUEST['gd_cat_loc'];
		$city_description = esc_attr($gd_cat_loc_desc);
    } else {
		$city_description = geodir_location_get_term_top_desc( $term_id, $gd_location, 'city' );
	}

    $country_description = '';
    $region_description = '';
    $country_options = '';
    $region_options = '';

    $gd_cat_country = isset($_REQUEST['gd_cat_country']) ? sanitize_text_field($_REQUEST['gd_cat_country']) : '';
    $gd_cat_region = isset($_REQUEST['gd_cat_region']) ? sanitize_text_field($_REQUEST['gd_cat_region']) : '';
    $gd_cat_region_country = isset($_REQUEST['gd_cat_region_country']) ? sanitize_text_field($_REQUEST['gd_cat_region_country']) : '';

    if (($show_countries || $show_regions) && !empty($all_countries)) {
        asort($all_countries);

        $i = 0;

        foreach ($all_countries as $country_slug => $country_name) {
            if ($show_countries) {
                if ($i == 0 && empty($gd_cat_country)) {
                    $gd_cat_country = $country_slug;
                }

                $selected = $gd_cat_country == $country_slug ? 'selected="selected"' : '';
                $country_options .= '<option value="' . $country_slug . '" ' . $selected . '>' . $country_name . '</option>';
            }

            if ($i == 0 && empty($gd_cat_region_country) && empty($gd_cat_region)) {
                $gd_cat_region_country = $country_slug;
            }

            if ($show_regions && !empty($all_regions[$country_slug])) {
                $country_regions = $all_regions[$country_slug];
                asort($country_regions);

                $region_options .= '<optgroup data-country="' . esc_attr($country_slug) . '" label="' . esc_attr($country_name) . '">';

                $j = 0;

                foreach ($country_regions as $region_slug => $region_name) {
                    if ($i == 0 && $j == 0 && empty($gd_cat_region)) {
                        $gd_cat_region = $region_slug;
                    }

                    $selected = $country_slug == $gd_cat_region_country && $gd_cat_region == $region_slug ? 'selected="selected"' : '';
                    $region_options .= '<option value="' . $region_slug . '" ' . $selected . '>' . $region_name . '</option>';

                    $j++;
                }

                $region_options .= '</optgroup>';
            }

            $i++;
        }

        if ($show_countries) {
            if (isset($_REQUEST['gd_cat_loc_country'])) {
                $country_description = esc_attr($_REQUEST['gd_cat_loc_country']);
            } else {
				$country_description = geodir_location_get_term_top_desc($term_id, $gd_cat_country, 'country');
			}
        }

        if ($show_regions) {
            if (isset($_REQUEST['gd_cat_loc_region'])) {
                $region_description = esc_attr($_REQUEST['gd_cat_loc_region']);
            } else {
				$region_description = geodir_location_get_term_top_desc($term_id, $gd_cat_region, 'region', $gd_cat_region_country);
			}
        }
    }

    $gd_cat_country_name = !empty($all_countries[$gd_cat_country]) ? $all_countries[$gd_cat_country] : '';
    $gd_cat_region_name = !empty($all_regions[$gd_cat_region_country][$gd_cat_region]) ? $all_regions[$gd_cat_region_country][$gd_cat_region] . ', ' : '';
    $gd_cat_region_name .= !empty($all_countries[$gd_cat_region_country]) ? $all_countries[$gd_cat_region_country] : '';
    ?>
    <tr class="form-field topdesc_type">
        <th scope="row"><label for="topdesc_type"><?php echo __('Category Top Description', 'geodirlocation'); ?></label>
        </th>
        <td><input type="checkbox" id="topdesc_type" name="topdesc_type" class="rw-checkbox" value="1" <?php checked( ! $gd_desc_custom, true ); ?> /> <?php echo _e('Use main description for all locations', 'geodirlocation'); ?><br/><span class="description wrap geodirectory"><?php echo __('The following location tags are available here:', 'geodirlocation'); echo GeoDir_SEO::helper_tags('location_tags') ?></span>
		<input type="hidden" id="geodir_save_term_desc" value="<?php echo wp_create_nonce( "geodir-save-term-desc" ); ?>">
        </td>
    </tr>
    <?php
    if ($show_countries && !empty($country_options)) {
        $count = 1;
        $field_id = 'gd_cat_loc_country';
        $field_name = 'gd_cat_loc_country';
    ?>
    <tr class="form-field location-top-desc" <?php echo ($gd_desc_custom ? '' : 'style="display:none"'); ?>>
        <th scope="row"><label for=""><?php echo __('Category + Country Top Description', 'geodirlocation'); ?></label><br/><span class="description"><?php echo __('(Leave blank to display default description of category for location)', 'geodirlocation'); ?></span>
        </th>
        <td class="all-locations">
            <select name="gd_location_country" id="gd_location_country" class="gd-location-list" onchange="javascript:changeCatLocation('<?php echo $term_id; ?>', 'country', this);"><?php echo $country_options; ?></select><span class="gd-loc-progress gd-catloc-status"><i class="fas fa-sync fa-spin"></i> <?php _e('Saving...', 'geodirlocation'); ?></span><span class="gd-loc-done gd-catloc-status"><i class="fas fa-check-circle"></i> <?php _e('Saved', 'geodirlocation'); ?></span><span class="gd-loc-fail gd-catloc-status"><i class="fas fa-exclamation-circle"></i> <?php _e('Not saved!', 'geodirlocation'); ?></span>
            <table class="form-table">
                <tbody>
                    <tr>
                        <td class="cat-loc-editor cat-loc-row-<?php echo $count; ?>">
                            <label for="<?php echo $field_id; ?>">&raquo; <?php echo wp_sprintf(__('Category description for location %s', 'geodirlocation'), '<b id="lbl-location-name">' . $gd_cat_country_name . '</b>'); ?></label>
                            <?php if (version_compare($wp_version, '3.2.1') < 1) { ?>
                                <textarea class="at-wysiwyg theEditor large-text cat-loc-desc" name="<?php echo  $field_name; ?>" id="<?php echo $field_id; ?>" cols="40" rows="10"><?php echo $country_description; ?></textarea>
                            <?php } else {
                                $settings = array('textarea_name' => $field_name, 'wpautop' => false, 'media_buttons' => false, 'editor_class' => 'at-wysiwyg cat-loc-desc', 'textarea_rows' => 10);
                                // Use new wp_editor() since WP 3.3
                                wp_editor(stripslashes(html_entity_decode($country_description)), $field_id, $settings);
                            } ?>
                            <div id="<?php echo $field_id; ?>-values" style="display:none!important">
                                <input type="hidden" name="gd_loc_country" value="<?php echo $gd_cat_country; ?>"/>
                            </div>
                           <script type="text/javascript">jQuery('textarea#<?php echo $field_id;?>').attr('onchange', "javascript:saveCatLocation(this, 'country');");</script>
                       </td>
                    </tr>
                </tbody>
            </table>
            <span class="description"><?php _e('Description auto saved on change value.', 'geodirlocation'); ?></span>
        </td>
    </tr>
    <?php } ?>
    <?php if ($show_regions && !empty($region_options)) { ?>
    <tr class="form-field location-top-desc" <?php echo ($gd_desc_custom ? '' : 'style="display:none"'); ?>>
        <th scope="row"><label for=""><?php echo __('Category + Region Top Description', 'geodirlocation'); ?></label><br/><span class="description"><?php echo __('(Leave blank to display default description of category for location)', 'geodirlocation'); ?></span>
        </th>
        <td class="all-locations"><select name="gd_location_region" id="gd_location_region" class="gd-location-list" onchange="javascript:changeCatLocation('<?php echo $term_id; ?>', 'region', this);"><?php echo $region_options; ?></select><span class="gd-loc-progress gd-catloc-status"><i class="fas fa-sync fa-spin"></i> <?php _e('Saving...', 'geodirlocation'); ?></span><span class="gd-loc-done gd-catloc-status"><i class="fas fa-check-circle"></i> <?php _e('Saved', 'geodirlocation'); ?></span><span class="gd-loc-fail gd-catloc-status"><i class="fas fa-exclamation-circle"></i> <?php _e('Not saved!', 'geodirlocation'); ?></span>
            <table class="form-table">
                <tbody>
                <?php
                $count = 1;
                $field_id = 'gd_cat_loc_region';
                $field_name = 'gd_cat_loc_region';

                echo '<tr><td class="cat-loc-editor cat-loc-row-' . $count . '">';
                echo '<label for="' . $field_id . '">&raquo; ' . sprintf(__('Category description for location %s', 'geodirlocation'), '<b id="lbl-location-name">' . $gd_cat_region_name . '</b>') . '</label>';
                if (version_compare($wp_version, '3.2.1') < 1) {
                    echo '<textarea class="at-wysiwyg theEditor large-text cat-loc-desc" name="' . $field_name . '" id="' . $field_id . '" cols="40" rows="10">' . $region_description . '</textarea>';
                } else {
                    $settings = array('textarea_name' => $field_name, 'wpautop' => false, 'media_buttons' => false, 'editor_class' => 'at-wysiwyg cat-loc-desc', 'textarea_rows' => 10);
                    // Use new wp_editor() since WP 3.3
                    wp_editor(stripslashes(html_entity_decode($region_description)), $field_id, $settings);
                }
                ?>
                <div id="<?php echo $field_id; ?>-values" style="display:none!important">
                    <input type="hidden" name="gd_loc_region" value="<?php echo $gd_cat_region; ?>"/>
                    <input type="hidden" name="gd_loc_region_country" value="<?php echo $gd_cat_region_country; ?>"/>
                </div>
                <script type="text/javascript">jQuery('textarea#<?php echo $field_id;?>').attr('onchange', "javascript:saveCatLocation(this, 'region');");</script>
                <?php
                echo '</td></tr>';
                ?></tbody>
            </table>
            <span class="description"><?php _e('Description auto saved on change value.', 'geodirlocation'); ?></span>
        </td>
    </tr>
    <?php } ?>
    <tr class="form-field location-top-desc" <?php echo ($gd_desc_custom ? '' : 'style="display:none"'); ?>>
        <th scope="row"><label for=""><?php echo __('Category + City Top Description', 'geodirlocation'); ?></label><br/><span class="description"><?php echo __('(Leave blank to display default description of category for location)', 'geodirlocation'); ?></span>
        </th>
        <td class="all-locations"><select name="gd_location" id="gd_location" class="gd-location-list" onchange="javascript:changeCatLocation('<?php echo $term_id; ?>', 'city', this);"><?php echo $location_options; ?></select><span class="gd-loc-progress gd-catloc-status"><i class="fas fa-sync fa-spin"></i> <?php _e('Saving...', 'geodirlocation'); ?></span><span class="gd-loc-done gd-catloc-status"><i class="fas fa-check-circle"></i> <?php _e('Saved', 'geodirlocation'); ?></span><span class="gd-loc-fail gd-catloc-status"><i class="fas fa-exclamation-circle"></i> <?php _e('Not saved!', 'geodirlocation'); ?></span>
            <table class="form-table">
                <tbody>
                <?php
                $count = 1;
                $field_id = 'gd_cat_loc';
                $field_name = 'gd_cat_loc';

                echo '<tr><td class="cat-loc-editor cat-loc-row-' . $count . '">';
                echo '<label for="' . $field_id . '">&raquo; ' . sprintf(__('Category description for location %s', 'geodirlocation'), '<b id="lbl-location-name">' . $gd_location_name . '</b>') . '</label>';
                if (version_compare($wp_version, '3.2.1') < 1) {
                    echo '<textarea class="at-wysiwyg theEditor large-text cat-loc-desc" name="' . $field_name . '" id="' . $field_id . '" cols="40" rows="10">' . $city_description . '</textarea>';
                } else {
                    $settings = array('textarea_name' => $field_name, 'wpautop' => false, 'media_buttons' => false, 'editor_class' => 'at-wysiwyg cat-loc-desc', 'textarea_rows' => 10);
                    // Use new wp_editor() since WP 3.3
                    wp_editor(stripslashes(html_entity_decode($city_description)), $field_id, $settings);
                }
                ?>
                <div id="<?php echo $field_id; ?>-values" style="display:none!important"><input type="hidden" id="gd_locid" name="gd_locid" value="<?php echo $gd_location; ?>"/><input type="hidden" id="gd_catid" name="gd_catid" value="<?php echo $term_id; ?>"/></div>
                <script type="text/javascript">jQuery('textarea#<?php echo $field_id;?>').attr('onchange', "javascript:saveCatLocation(this, 'city');");</script>
                <?php
                echo '</td></tr>';
                ?></tbody>
            </table>
            <span class="description"><?php _e('Description auto saved on change value.', 'geodirlocation'); ?></span>
        </td>
    </tr>
    <?php
}

add_action('admin_head', 'geodir_location_cat_loc_add_css');
/**
 * Adds category location styles to head.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global string $pagenow The current screen.
 */
function geodir_location_cat_loc_add_css()
{
    global $pagenow;

    $taxonomy = isset($_REQUEST['taxonomy']) ? $_REQUEST['taxonomy'] : '';
    $action = isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' || $pagenow == 'term.php' ? true : false;
    if (is_admin() && $taxonomy && $action && strpos($taxonomy, 'category') !== false) {
        ?>
        <style>td.cat-loc-editor {
                padding-top: 10px;
                padding-bottom: 12px;
                border: 1px solid #dedede
            }

            .all-locations > table {
                margin-top: 0
            }

            .cat-loc-editor > label {
                padding-bottom: 10px;
                display: block
            }

            textarea.cat-loc-desc {
                width: 100% !important
            }

            .default-top-desc iframe, .default-top-desc textarea {
                min-height: 400px !important
            }

            .cat-loc-editor iframe {
                min-height: 234px !important
            }

            .cat-loc-editor textarea {
                min-height: 256px !important
            }

            .location-top-desc .description {
                font-weight: normal
            }

            #ct_cat_top_desc,#ct_cat_bottom_desc {
                width: 100% !important
            }

            select.gd-location-list {
                margin-bottom: 5px;
                margin-left: 0;
            }</style>
        <?php
    }
}

add_filter('tiny_mce_before_init', 'add_idle_function_to_tinymce');
/**
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param $initArray
 * @return mixed
 */
function add_idle_function_to_tinymce($initArray) {
    if (isset($initArray['selector'])) {
        if ($initArray['selector'] == '#gd_cat_loc_country') {
            $initArray['setup'] = 'function(ed) { ed.onChange.add(function(ob, e) { var content = ob.getContent(); if (ob.id=="gd_cat_loc_country") { saveCatLocation(ob, "country", content); } }); }';
        } else if ($initArray['selector'] == '#gd_cat_loc_region') {
            $initArray['setup'] = 'function(ed) { ed.onChange.add(function(ob, e) { var content = ob.getContent(); if (ob.id=="gd_cat_loc_region") { saveCatLocation(ob, "region", content); } }); }';
        } else if ($initArray['selector'] == '#gd_cat_loc') {
            $initArray['setup'] = 'function(ed) { ed.onChange.add(function(ob, e) { var content = ob.getContent(); if (ob.id=="gd_cat_loc") { saveCatLocation(ob, "city", content); } }); }';
        }
    }
    return $initArray;
}

add_action('admin_footer', 'geodir_location_cat_loc_add_script', 99);
/**
 * Adds category location javascript to footer.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global string $pagenow The current screen.
 */
function geodir_location_cat_loc_add_script() {
    global $pagenow;

    $taxonomy = isset($_REQUEST['taxonomy']) ? $_REQUEST['taxonomy'] : '';
    $action = isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' || $pagenow == 'term.php' ? true : false;
    if (is_admin() && $taxonomy && $action && strpos($taxonomy, 'category') !== false) {
        $show_countries = geodir_get_option( 'lm_default_country' ) == 'default' && geodir_get_option( 'lm_hide_country_part' ) ? false : true;
        $show_regions = geodir_get_option( 'lm_default_region' ) == 'default' && geodir_get_option( 'lm_hide_region_part' ) ? false : true;
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                jQuery('#wp-ct_cat_top_desc-wrap').closest('tr').addClass('default-top-desc');
                jQuery('.default-top-desc > th > label').hide();
                jQuery("#topdesc_type").on("change",function (e) {
                    e.preventDefault();
                    var $input = jQuery(this);
                    if ($input.is(":checked")) {
                        jQuery('.default-top-desc').show();
                        jQuery('.location-top-desc').hide();
                    } else {
                        jQuery('.default-top-desc').hide();
                        jQuery('.location-top-desc').show();
                    }
                });
                jQuery("#topdesc_type").trigger('change');
            });

            function saveCatLocation(obj, type, content) {
                var locid, country, region, $tr;
                var catid = jQuery('#gd_catid').val();
                if (!catid) {
                    return;
                }

                if (type == 'country') {
                    var country = jQuery('[name="gd_loc_country"]').val();
                    if (!country) {
                        return;
                    }
                    $tr = jQuery('[name="gd_loc_country"]').closest('.location-top-desc');
                } else if (type == 'region') {
                    country = jQuery('[name="gd_loc_region_country"]').val();
                    region = jQuery('[name="gd_loc_region"]').val();
                    if (!country || !region) {
                        return;
                    }
                    $tr = jQuery('[name="gd_loc_region"]').closest('.location-top-desc');
                } else {
                    locid = jQuery('#gd_locid').val();
                    if (!locid) {
                        return;
                    }
                    $tr = jQuery('#gd_locid').closest('.location-top-desc');
                }

                if (typeof content == 'undefined') {
                    content = jQuery(obj).val();
                }
                jQuery('.gd-catloc-status', $tr).hide();
                jQuery('.gd-loc-progress', $tr).show();

                var _wpnonce = jQuery('#gd_location').closest('form').find('#geodir_save_term_desc').val();
                var loc_default = jQuery('#topdesc_type').is(':checked') == true ? 1 : 0;

                var postData = {
                    action: 'geodir_save_term_location_desc',
                    wpnonce: _wpnonce,
                    catid: catid,
                    loc_default: loc_default,
                    content: content,
                };

                if (type == 'country') {
                    postData._type = 'country';
                    postData.country = country;
                } else if (type == 'region') {
                    postData._type = 'region';
                    postData.country = country;
                    postData.region = region;
                } else {
                    postData._type = 'city';
                    postData.locid = locid;
                }

                jQuery.post(geodir_params.ajax_url, postData).done(function (data) {
                    jQuery('.gd-catloc-status', $tr).hide();
                    if (data == 'FAIL') {
                        jQuery('.gd-loc-fail', $tr).show();
                    } else {
                        jQuery('.gd-loc-done', $tr).show();
                    }
                });
            }

            function changeCatLocation(catid, type, obj) {
                var locid, loc_name, country, region, field;

                jQuery('.gd-catloc-status').hide();

                if (!catid) {
                    return;
                }

                if (type == 'country') {
                    var country = jQuery(obj).val();
                    if (!country) {
                        return;
                    }

                    field = 'gd_cat_loc_country';
                    loc_name = jQuery("#gd_location_country option:selected").text();
                    jQuery('[name="gd_loc_country"]').val(country);
                } else if (type == 'region') {
                    country = jQuery(obj).find('option:selected').closest('optgroup').data('country');
                    region = jQuery(obj).val();
                    if (!country || !region) {
                        return;
                    }

                    loc_name = jQuery("#gd_location_region option:selected").text() + ', ' + jQuery(obj).find('option:selected').closest('optgroup').attr('label');
                    field = 'gd_cat_loc_region';
                    jQuery('[name="gd_loc_region"]').val(region);
                    jQuery('[name="gd_loc_region_country"]').val(country);
                } else {
                    locid = jQuery(obj).val();
                    if (!locid) {
                        return;
                    }

                    field = 'gd_cat_loc';
                    loc_name = jQuery("#gd_location option:selected").text();
                    jQuery("#gd_locid").val(locid);
                }

                jQuery(obj).closest('.location-top-desc').find('#lbl-location-name').text(loc_name);

                var _wpnonce = jQuery('#gd_location').closest('form').find('#geodir_save_term_desc').val();
                var is_tinymce = typeof tinymce != 'undefined' && typeof tinymce.editors != 'undefined' && typeof tinymce.editors[field] != 'undefined' ? true : false;
                if (is_tinymce) {
                    tinymce.editors[field].setProgressState(true);
                }

                var postData = {
                    action: 'geodir_change_term_location_desc',
                    wpnonce: _wpnonce,
                    catid: catid,
                };

                if (type == 'country') {
                    postData._type = 'country';
                    postData.country = country;
                } else if (type == 'region') {
                    postData._type = 'region';
                    postData.country = country;
                    postData.region = region;
                } else {
                    postData._type = 'city';
                    postData.locid = locid;
                }

                jQuery.post(geodir_params.ajax_url, postData).done(function (data) {
                    if (data != 'FAIL') {
                        jQuery('#' + field).val(data);

                        if (is_tinymce) {
                            tinymce.editors[field].setContent(data);
                        }
                    }

                    if (is_tinymce) {
                        tinymce.editors[field].setProgressState(false);
                    }
                });
            }
        </script>
        <?php
    }
}

if (is_admin()) {
    add_action('edited_term', 'geodir_location_save_cat_loc_desc', 10, 2);
}
/**
 * Save category and location description.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param int|string $term_id The term ID.
 * @param int $tt_id The term taxonomy ID.
 */
function geodir_location_save_cat_loc_desc( $term_id, $tt_id ) {
    if ( isset( $_POST['ct_cat_top_desc'] ) ) {
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			$use_default = empty( $_POST['topdesc_type'] ) ? true : false;
			update_term_meta( $term_id, 'gd_desc_custom', $use_default );
		}
	}
}

// filter the cat description
add_filter('geodir_get_cat_top_description','geodir_location_action_listings_description', 100, 2);

/**
 * Adds listing description to the page.
 *
 * @since 1.0.0
 * @since 1.4.9 Modified to filter neighbourhood in category top description.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global object $wp_query WordPress Query object.
 */
function geodir_location_action_listings_description( $top_description, $term_id='') {
    global $wpdb, $wp_query, $wp_embed, $geodirectory;
    $post_type = geodir_get_current_posttype();

    if ($term_id) {
        $term_desc = term_description($term_id, $post_type . '_tags');
        $saved_data = stripslashes(get_term_meta( $term_id, 'ct_cat_top_desc', true ));

        if ($term_desc && !$saved_data) {
            $saved_data = $term_desc;
        }

        $default_location = $geodirectory->location->get_default_location();
        /**
         * Filter the Everywhere text in location description.
         *
         * @since 1.5.6
         * 
         * @param string $replace_location Everywhere text.
         */
        $replace_location = apply_filters('geodir_location_description_everywhere_text', __('Everywhere', 'geodirlocation'));

        $gd_country = get_query_var('country');
        $gd_region = get_query_var('region');
        $gd_city = get_query_var('city');
        
        $location_type = '';
        if (!empty($gd_country)) {
            $location_type = 'country';
        }
        if (!empty($gd_region)) {
            $location_type = 'region';
        }
        if (!empty($gd_city)) {
            $location_type = 'city';
        }

        if ($location_type == 'country' || $location_type == 'region' || $location_type == 'city') {
            if (geodir_get_option( 'lm_default_country' ) == 'default' && !empty($default_location->country_slug)) {
                $gd_country = $default_location->country_slug;
            }

            if ($location_type != 'country') {
                if (geodir_get_option( 'lm_default_region' ) == 'default' && !empty($default_location->region_slug)) {
                    $gd_region = $default_location->region_slug;
                }

                if ($location_type != 'region') {
                    if (geodir_get_option( 'lm_default_city' ) == 'default' && !empty($default_location->city_slug)) {
                        $gd_city = $default_location->city_slug;
                    }
                }
            }
        }

        $current_location = '';
        if ($gd_country != '') {
            $location_type = 'country';
            $current_location = geodir_location_get_name('country', $gd_country, true);
        }
        if ($gd_region != '') {
            $location_type = 'region';
            $current_location = geodir_location_get_name('region', $gd_region);
        }
        if ($gd_city != '') {
            $location_type = 'city';
            $current_location = geodir_location_get_name('city', $gd_city);
        }

        $show_custom = get_term_meta( $term_id, 'gd_desc_custom', true );
        if ($show_custom) {
            $saved_data = $term_desc;
        }

        $location_description = '';
        if ($location_type == 'city') {
            $location_info = GeoDir_Location_City::get_info_by_slug($gd_city, $gd_country, $gd_region);

            $replace_location = !empty($location_info) ? $location_info->city : $replace_location;
            $location_id = !empty($location_info) ? $location_info->location_id : '';

            if ($show_custom && $location_id) {
                $location_description = geodir_location_get_term_top_desc($term_id, $location_id, 'city');
            }
        } else if ($location_type == 'region') {
            $replace_location = geodir_get_current_location(array('what' => 'region', 'echo' => false));

            if ($show_custom && $gd_region) {
                $location_description = geodir_location_get_term_top_desc($term_id, $gd_region, 'region', $gd_country);
            }
        } else if ($location_type == 'country') {
            $replace_location = geodir_get_current_location(array('what' => 'country', 'echo' => false));
            $replace_location = __($replace_location, 'geodirectory');

            if ($show_custom && $gd_country) {
                $location_description = geodir_location_get_term_top_desc($term_id, $gd_country, 'country');
            }
        }
        if (geodir_get_option( 'lm_enable_neighbourhoods' ) && ($gd_neighbourhood = get_query_var('gd_neighbourhood')) != '') {
            $current_location = geodir_location_get_name('neighbourhood', $gd_neighbourhood, true);
        }
        $replace_location = $current_location != '' ? $current_location : $replace_location;

        if (trim($location_description) != '') {
            $saved_data = stripslashes($location_description);
        }
        if (!empty($saved_data)) {
            $saved_data = geodir_replace_location_variables($saved_data);
        }
        $saved_data = str_replace('%location%', $replace_location, $saved_data);

		$cat_description = $wp_embed->autoembed( $saved_data );
		$cat_description = do_shortcode( wpautop( $cat_description ) );

		$cat_description = apply_filters( 'geodir_location_term_top_description', $cat_description, $term_id, $saved_data );

        if ($cat_description) {
            $top_description = $cat_description;
        }
    }

    return $top_description;
}

/**
 * Disable geodir_codeAddress from location manager. Adds return to js.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */
function geodir_add_listing_codeaddress_before_geocode_lm() {
    if ( geodir_get_option( 'lm_set_address_disable' ) ) {
	?>
    /* Disable geodir_codeAddress from location manager */
    if (window.gdMaps == 'google') {
	    return;
	} else if (window.gdMaps == 'osm') {
	    window.osm_skip_set_on_map = true;
	}
    <?php }
}
add_action( 'geodir_add_listing_codeaddress_before_geocode', 'geodir_add_listing_codeaddress_before_geocode_lm', 11 );

add_filter('geodir_auto_change_address_fields_pin_move', 'geodir_location_set_pin_disable', 10, 1);
/**
 * Filters the auto change address fields values when moving the map pin.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param bool $val Whether to change the country, state, city values in fields.
 * @return string|bool
 */
function geodir_location_set_pin_disable( $val ) {
    if ( geodir_get_option( 'lm_set_pin_disable' ) ) {
        return '0';
    }
    return $val;
}

/**
 * Filters the map query for server side clustering.
 *
 * Alters the query to limit the search area to the bounds of the map view.
 *
 * @since 1.1.1
 * @param string $search The where query string for marker search.
 * @package GeoDirectory_Marker_Cluster
 */
function geodir_location_manager_location_me($search)
{
    $my_lat = filter_var($_REQUEST['my_lat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $my_lon = filter_var($_REQUEST['my_lon'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    $distance_in_miles = geodir_get_option( 'search_radius' ) ? geodir_get_option( 'search_radius' ) : 40;
    $data = geodir_lm_bounding_box($my_lat, $my_lon, sqrt($distance_in_miles));

    $lat_sw = filter_var($data[0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $lat_ne = filter_var($data[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $lon_sw = filter_var($data[2], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $lon_ne = filter_var($data[3], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    $lon_not = '';
    //if the corners span more than half the world

    if ($lon_ne > 0 && $lon_sw > 0 && $lon_ne < $lon_sw) {
        $lon_not = 'not';
    } elseif ($lon_ne < 0 && $lon_sw < 0 && $lon_ne < $lon_sw) {
        $lon_not = 'not';
    } elseif ($lon_ne < 0 && $lon_sw > 0 && ($lon_ne + 360 - $lon_sw) > 180) {
        $lon_not = 'not';
    } elseif ($lon_ne < 0 && $lon_sw > 0 && abs($lon_ne) + abs($lon_sw) > 180) {
        $lon_not = 'not';
    }

    if ($lon_ne == 180 && $lon_sw == -180) {
        return $search;
    }

    $search .= " AND pd.latitude between least($lat_sw,$lat_ne) and greatest($lat_sw,$lat_ne)  AND pd.longitude $lon_not between least($lon_sw,$lon_ne) and greatest($lon_sw,$lon_ne)";

    return $search;
}


function geodir_lm_bounding_box($lat_degrees, $lon_degrees, $distance_in_miles)
{

    $radius = 3963.1; // of earth in miles

    // bearings - FIX
    $due_north = deg2rad(0);
    $due_south = deg2rad(180);
    $due_east = deg2rad(90);
    $due_west = deg2rad(270);

    // convert latitude and longitude into radians
    $lat_r = deg2rad($lat_degrees);
    $lon_r = deg2rad($lon_degrees);

    // find the northmost, southmost, eastmost and westmost corners $distance_in_miles away
    // original formula from
    // http://www.movable-type.co.uk/scripts/latlong.html

    $northmost = asin(sin($lat_r) * cos($distance_in_miles / $radius) + cos($lat_r) * sin($distance_in_miles / $radius) * cos($due_north));
    $southmost = asin(sin($lat_r) * cos($distance_in_miles / $radius) + cos($lat_r) * sin($distance_in_miles / $radius) * cos($due_south));

    $eastmost = $lon_r + atan2(sin($due_east) * sin($distance_in_miles / $radius) * cos($lat_r), cos($distance_in_miles / $radius) - sin($lat_r) * sin($lat_r));
    $westmost = $lon_r + atan2(sin($due_west) * sin($distance_in_miles / $radius) * cos($lat_r), cos($distance_in_miles / $radius) - sin($lat_r) * sin($lat_r));


    $northmost = rad2deg($northmost);
    $southmost = rad2deg($southmost);
    $eastmost = rad2deg($eastmost);
    $westmost = rad2deg($westmost);

    // sort the lat and long so that we can use them for a between query
    if ($northmost > $southmost) {
        $lat1 = $southmost;
        $lat2 = $northmost;

    } else {
        $lat1 = $northmost;
        $lat2 = $southmost;
    }


    if ($eastmost > $westmost) {
        $lon1 = $westmost;
        $lon2 = $eastmost;

    } else {
        $lon1 = $eastmost;
        $lon2 = $westmost;
    }

    return array($lat1, $lat2, $lon1, $lon2);
}


add_filter('get_pagenum_link', 'geodir_lm_strip_location_from_blog_link', 10, 1);

/**
 * Removes the location from blog page links if added.
 *
 * @since 1.5.6
 * @package GeoDirectory_Location_Manager
 *
 * @param string $link The link maybe with location info in it.
 * @return string The link with no location info in it.
 */
function geodir_lm_strip_location_from_blog_link( $link ) {
    if ( strpos( $link, '/category/' ) !== false && function_exists( 'geodir_location_geo_home_link' ) ) {
        $loc_home = trailingslashit( home_url() );

        remove_filter( 'home_url', 'geodir_location_geo_home_link', 100000, 2 );

        $real_home = trailingslashit( home_url() );

        add_filter( 'home_url', 'geodir_location_geo_home_link', 100000, 2 );

        $link = str_replace( $loc_home, $real_home, $link );
    }
    return $link;
}

add_action('geodir_diagnostic_tool', 'geodir_refresh_location_cat_counts_tool', 1);
/**
 * Adds location category count tool to GD Tools page.
 *
 * @since 1.4.8
 * @package GeoDirectory_Location_Manager
 */
function geodir_refresh_location_cat_counts_tool()
{
    ?>
    <tr>
        <td><?php _e('Location category counts', 'geodirlocation'); ?></td>
        <td>
            <small><?php _e('Refresh the category counts for each location (can take time on large sites)', 'geodirlocation'); ?></small>
        </td>
        <td><input type="button" value="<?php _e('Run', 'geodirlocation'); ?>"
                   class="button-primary geodir_diagnosis_button" data-diagnose="run_refresh_cat_count"/>
        </td>
    </tr>
    <?php
}

/**
 * Returns the html/js used to ajax refresh the location category count tool.
 *
 * @since 1.4.8
 * @package GeoDirectory_Location_Manager
 */
function geodir_diagnose_run_refresh_cat_count()
{
    global $wpdb, $plugin_prefix;

    $output_str = '';
    $city_count = geodir_cat_count_location('city');
    if (!$city_count) {
        _e('No cities found.', 'geodirlocation');
        exit;
    }

    $region_count = geodir_cat_count_location('region');
    $country_count = geodir_cat_count_location('country');

    $first_city = $wpdb->get_var("SELECT city_slug FROM " . GEODIR_LOCATIONS_TABLE . " ORDER BY  `city_slug` ASC LIMIT 1");


    // city
    $output_str .= "<li class='gd-cat-count-progress-city'><h2 class='gd-cat-count-loc-title'>" . __('Cities', 'geodirlocation') . ": <span class='gd-cat-count-progress-city-name'></span></h2>";

    $output_str .= '<div id=\'gd_progressbar_box_city\'>
					  <div id="gd_progressbar" class="gd_progressbar">
						<div class="gd-progress-label"></div>
					  </div>
					</div>';

    $output_str .= '</li>';

    //region
    $output_str .= "<li class='gd-cat-count-progress-region'><h2 class='gd-cat-count-loc-title'>" . __('Regions', 'geodirlocation') . ": <span class='gd-cat-count-progress-region-name'></span></h2>";

    $output_str .= '<div id=\'gd_progressbar_box_region\'>
					  <div id="gd_progressbar" class="gd_progressbar">
						<div class="gd-progress-label"></div>
					  </div>
					</div>';

    $output_str .= '</li>';

    // country
    $output_str .= "<li class='gd-cat-count-progress-country'><h2 class='gd-cat-count-loc-title'>" . __('Countries', 'geodirlocation') . ": <span class='gd-cat-count-progress-country-name'></span></h2>";

    $output_str .= '<div id=\'gd_progressbar_box_country\'>
					  <div id="gd_progressbar" class="gd_progressbar">
						<div class="gd-progress-label"></div>
					  </div>
					</div>';

    $output_str .= '</li>';


    $info_div_class = "geodir_running_info";
    $fix_button_txt = '';

    echo "<ul class='gd-loc-cat-count-container $info_div_class'>";
    echo $output_str;
    echo $fix_button_txt;
    echo "</ul>";
    ?>
    <script>
        jQuery('.gd_progressbar').each(function () {
            jQuery(this).progressbar({value: 0});
        });

        $gdIntCityCount = '<?php echo $city_count;?>';
        $gdIntRegionCount = '<?php echo $region_count;?>';
        $gdIntCountryCount = '<?php echo $country_count;?>';
        $gdCityCount = 0;
        $gdRegionCount = 0;
        $gdCountryCount = 0;

        setTimeout(function () {
            gd_loc_count_loc_terms('city', '<?php echo $first_city;?>');
            gd_progressbar('.gd-cat-count-progress-city', 0, '0% (0 / ' + $gdIntCityCount + ') <i class="fas fa-sync fa-spin"></i><?php echo esc_attr(__('Calculating...', 'geodirlocation'));?>');
            gd_progressbar('.gd-cat-count-progress-region', 0, '0% (0 / ' + $gdIntRegionCount + ') <i class="fas fa-hourglass-start"></i><?php echo esc_attr(__('Waiting...', 'geodirlocation'));?>');
            gd_progressbar('.gd-cat-count-progress-country', 0, '0% (0 / ' + $gdIntCountryCount + ') <i class="fas fa-hourglass-start"></i><?php echo esc_attr(__('Waiting...', 'geodirlocation'));?>');

        }, 1000);

        function gd_loc_count_loc_terms($type, $loc) {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'html',
                data: {
                    action: 'geodir_location_cat_count_ajax',
                    gd_loc_type: $type,
                    gd_loc: $loc
                },
                beforeSend: function () {
                    jQuery('.gd-cat-count-progress-' + $type + '-name').html($loc);
                },
                success: function (data, textStatus, xhr) {
                    data = JSON.parse(data);

                    if ($type == 'city') {
                        $gdCityCount++;
                        var percentage = Math.round(($gdCityCount / $gdIntCityCount ) * 100);
                        percentage = percentage > 100 ? 100 : percentage;

                        if (data.loc_type && data.loc_name && data.loc_type == $type && data.loc_name != $loc) {
                            gd_loc_count_loc_terms(data.loc_type, data.loc_name);
                            gd_progressbar('.gd-cat-count-progress-city', percentage, '' + percentage + '% (' + ( $gdCityCount ) + ' / ' + $gdIntCityCount + ') <i class="fas fa-sync fa-spin"></i><?php echo esc_attr(__('Calculating...', 'geodirlocation'));?>');
                        } else if (data.loc_type && data.loc_name && data.loc_type != $type && data.loc_name != $loc) {
                            gd_loc_count_loc_terms(data.loc_type, data.loc_name);
                            jQuery('.gd-cat-count-progress-city-name').html('');
                            gd_progressbar('.gd-cat-count-progress-city', percentage, '' + percentage + '% (' + ( $gdCityCount ) + ' / ' + $gdIntCityCount + ') <i class="fas fa-check"></i><?php echo esc_attr(__('Complete!', 'geodirlocation'));?>');
                        }

                    } else if ($type == 'region') {

                        $gdRegionCount++;
                        var percentage = Math.round(($gdRegionCount / $gdIntRegionCount ) * 100);
                        percentage = percentage > 100 ? 100 : percentage;

                        if (data.loc_type && data.loc_name && data.loc_type == $type && data.loc_name != $loc) {
                            gd_loc_count_loc_terms(data.loc_type, data.loc_name);
                            gd_progressbar('.gd-cat-count-progress-region', percentage, '' + percentage + '% (' + ( $gdRegionCount ) + ' / ' + $gdIntRegionCount + ') <i class="fas fa-sync fa-spin"></i><?php echo esc_attr(__('Calculating...', 'geodirlocation'));?>');
                        } else if (data.loc_type && data.loc_name && data.loc_type != $type && data.loc_name != $loc) {
                            gd_loc_count_loc_terms(data.loc_type, data.loc_name);
                            jQuery('.gd-cat-count-progress-region-name').html('');
                            gd_progressbar('.gd-cat-count-progress-region', percentage, '' + percentage + '% (' + ( $gdRegionCount ) + ' / ' + $gdIntRegionCount + ') <i class="fas fa-check"></i><?php echo esc_attr(__('Complete!', 'geodirlocation'));?>');
                        }

                    } else if ($type == 'country') {

                        $gdCountryCount++;
                        var percentage = Math.round(($gdCountryCount / $gdIntCountryCount ) * 100);
                        percentage = percentage > 100 ? 100 : percentage;

                        if (data.loc_type && data.loc_name && data.loc_type == $type && data.loc_name != $loc) {
                            gd_loc_count_loc_terms(data.loc_type, data.loc_name);
                            gd_progressbar('.gd-cat-count-progress-country', percentage, '' + percentage + '% (' + ( $gdCountryCount ) + ' / ' + $gdIntCountryCount + ') <i class="fas fa-sync fa-spin"></i><?php echo esc_attr(__('Calculating...', 'geodirlocation'));?>');
                        } else if (data.loc_type && data.loc_name && data.loc_type != $type && data.loc_name != $loc) {
                            gd_loc_count_loc_terms(data.loc_type, data.loc_name);
                            gd_progressbar('.gd-cat-count-progress-country', percentage, '' + percentage + '% (' + ( $gdCountryCount ) + ' / ' + $gdIntCountryCount + ') <i class="fas fa-check"></i><?php echo esc_attr(__('Complete!', 'geodirlocation'));?>');
                        } else if (data.loc_type && data.loc_type == 'end') {
                            gd_progressbar('.gd-cat-count-progress-country', percentage, '' + percentage + '% (' + ( $gdCountryCount ) + ' / ' + $gdIntCountryCount + ') <i class="fas fa-check"></i><?php echo esc_attr(__('Complete!', 'geodirlocation'));?>');

                            jQuery('.gd-cat-count-progress-country-name').html('');
                            jQuery(".gd-loc-cat-count-container").addClass("geodir_noproblem_info").removeClass("geodir_running_info");
                            alert('<?php _e('Complete!', 'geodirlocation');?>');
                        }

                    }


                },
                error: function (xhr, textStatus, errorThrown) {
                    alert(textStatus);
                }
            });
        }
    </script>
    <?php

}

/**
 * Gets the total count for city/region/country.
 *
 * @since 1.4.8
 * @package GeoDirectory_Location_Manager
 * @param string $type The location type, city/region/country.
 */
function geodir_cat_count_location($type)
{
    global $wpdb;
    $type = esc_sql($type);
    $count = $wpdb->get_var("SELECT COUNT(DISTINCT `" . $type . "_slug`) FROM " . GEODIR_LOCATIONS_TABLE);

    return $count;
}

add_action('wp_ajax_geodir_location_cat_count_ajax', 'geodir_location_cat_count');

/**
 * Ajax function used to count the location category counts and return the next location to check.
 *
 * @since 1.4.8
 * @package GeoDirectory_Location_Manager
 */
function geodir_location_cat_count()
{
    global $wpdb;
    $gd_loc_type = '';
    if (sanitize_text_field($_POST['gd_loc_type']) == 'city') {
        $gd_loc_type = 'city';
        $gd_loc_type_next = 'region';
    } elseif (sanitize_text_field($_POST['gd_loc_type']) == 'region') {
        $gd_loc_type = 'region';
        $gd_loc_type_next = 'country';
    } elseif (sanitize_text_field($_POST['gd_loc_type']) == 'country') {
        $gd_loc_type = 'country';
        $gd_loc_type_next = 'end';
    }
    if (!$gd_loc_type) {
        exit;
    }
    $gd_loc = sanitize_text_field($_POST['gd_loc']);
    $loc = $wpdb->get_row($wpdb->prepare("SELECT location_id,country_slug, country,region_slug, region,city_slug, city FROM " . GEODIR_LOCATIONS_TABLE . " WHERE `" . $gd_loc_type . "_slug`=%s  GROUP BY `" . $gd_loc_type . "_slug` ORDER BY `" . $gd_loc_type . "_slug` ASC LIMIT 1", $gd_loc), ARRAY_A);
    $loc_id = $loc['location_id'];


    geodir_get_loc_term_count('term_count', $gd_loc, $gd_loc_type, $loc, true);

    $sql = $wpdb->prepare("SELECT " . $gd_loc_type . "_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE " . $gd_loc_type . "_slug!=%s AND `" . $gd_loc_type . "_slug` > %s GROUP BY `" . $gd_loc_type . "_slug`  ORDER BY  `" . $gd_loc_type . "_slug` ASC LIMIT 1", $loc[$gd_loc_type], $loc[$gd_loc_type]);
    $loc_name = $wpdb->get_var($sql);

    if (!$loc_name && $gd_loc_type_next != 'end') {
        $gd_loc_type = $gd_loc_type_next;
        $sql = "SELECT " . $gd_loc_type . "_slug FROM " . GEODIR_LOCATIONS_TABLE . "  GROUP BY `" . $gd_loc_type . "_slug`  ORDER BY  `" . $gd_loc_type . "_slug` ASC LIMIT 1";
        $loc_name = $wpdb->get_var($sql);
    } elseif (!$loc_name && $gd_loc_type_next == 'end') {
        $gd_loc_type = $gd_loc_type_next;
    }

    $result = array(
        'loc_type' => $gd_loc_type,
        'loc_name' => $loc_name,
        'loc' => $loc,
    );

    echo json_encode($result);
    gd_die();
}

remove_filter('geodir_location_slug_check', 'geodir_location_slug_check');

/**
 * Filter location variables used in description text.
 *
 * @since 1.5.3
 *
 * @param string $description The location description text.
 * @param string $gd_country The current country slug.
 * @param string $gd_region The current region slug.
 * @param string $gd_city The current city slug.
 * @return string Filtered description.
 */
function geodir_location_description_filter_variables( $description, $gd_country, $gd_region, $gd_city ) {
    if ( !empty( $description ) ) {
        $description = geodir_replace_location_variables( $description );
    }

    return $description;
}
add_filter('geodir_location_description', 'geodir_location_description_filter_variables', 999, 4);

/**
 * Add neighbourhood location title variables.
 *
 * @since 1.5.3
 *
 * @param array $settings The settings array.
 * @return array Filtered array.
 */
function geodir_location_filter_title_meta_vars($settings) {
    foreach($settings as $index => $setting) {
        if (!empty($setting['id']) && $setting['id'] == 'geodir_meta_vars' && !empty($setting['type']) && $setting['type']== 'sectionstart' && geodir_get_option( 'lm_enable_neighbourhoods' )) {
            $settings[$index]['desc'] = $setting['desc'] . ', %%location_neighbourhood%%, %%in_location_neighbourhood%%';
        }
    }
    return $settings;
}
add_filter('geodir_title_meta_settings', 'geodir_location_filter_title_meta_vars', 11, 1);

/**
 * Set neighbourhood as a default tab in location switcher.
 *
 * @since 1.5.3
 *
 * @param string $tab The location switcher default tab.
 * @return string Filtered default tab.
 */
function geodir_location_switcher_set_neighbourhood_tab($tab) {
    if (geodir_get_option( 'lm_enable_neighbourhoods' ) && geodir_get_option( 'lm_default_city' ) == 'default') {
        $tab = 'neighbourhood';
    }

    return $tab;
}
add_filter('geodir_location_switcher_default_tab', 'geodir_location_switcher_set_neighbourhood_tab', 10, 1);

/**
 * Filter everywhere_in_neighbourhood_dropdown option.
 *
 * @since 1.5.3
 *
 * @param bool Whether to show everywhere option in location switcher or not.
 * @param string $option Option name.
 * @return bool True if active false if disabled.
 */
function geodir_location_switcher_everywhere_in_neighbourhood($value, $option) {
    return true;
}
add_filter('pre_option_geodir_everywhere_in_neighbourhood_dropdown', 'geodir_location_switcher_everywhere_in_neighbourhood', 10, 2);

/**
 * Grab neighbourhood and add it to neighbourhood field during add listing location search.
 *
 * @since 1.5.4
 *
 */
function geodir_location_grab_neighbourhood() {
?> 
window.neighbourhood = '';
window.gdGeo = true;
if (window.gdMaps == 'google' && typeof responses != 'undefined' && responses && responses.length > 0) {    
    for (var j=0; j < responses[0].address_components.length; j++) {
        var component = responses[0].address_components[j];
        if (component.types[0] == 'neighbourhood' || component.types[0] == 'neighborhood') {
            window.neighbourhood = component.long_name;
        }
    }
} 
<?php
}

/**
 * Filter the address fields array being displayed.
 *
 * @since 1.5.5
 *
 * @param array $address_fields The array of address fields.
 * @param object $post The current post object.
 * @param array $cf The custom field array details.
 * @param string $location The location to output the html.
 * @return array Filtered address fields.
 */
function geodir_custom_field_output_show_address_neighbourhood( $address_fields, $gd_post, $cf, $location ) {
    $extra_fields = ! empty( $cf['extra_fields'] ) ? maybe_unserialize( $cf['extra_fields'] ) : NULL;
	$show_neighbourhood_in_address = ! empty( $extra_fields['show_neighbourhood'] ) ? true : false;

	/**
	 * Filter "show city in address" value.
	 *
	 * @since 1.0.0
	 */
	$show_neighbourhood_in_address = apply_filters( 'geodir_show_neighbourhood_in_address', $show_neighbourhood_in_address );

	if ( ! empty( $gd_post->neighbourhood ) && $show_neighbourhood_in_address && geodir_get_option( 'lm_enable_neighbourhoods' ) ) {
        $neighbourhood = GeoDir_Location_Neighbourhood::get_nicename( $gd_post->neighbourhood );

        if ( $neighbourhood ) {
            $address_fields['neighbourhood'] = '<span data-itemprop="addressNeighbourhood">' . $neighbourhood . '</span>'; // The property addressNeighbourhood is not recognised by Google
        }
    }

    return $address_fields;
}
add_filter( 'geodir_custom_field_output_address_fields', 'geodir_custom_field_output_show_address_neighbourhood', 10, 4 );

/**
 * Add location type in the near field value.
 *
 * @since 1.0.0
 * @since 1.4.7 Changed language domain to "geodirectory" for "In:" text
 *              because if translation don't match in both plugins then
 *              it will breaks autocomplete search.
 *
 * @global object $wpdb        WordPress Database object.
 * @global object $gd_session  GeoDirectory Session object.
 *
 * @param string $near The near field value.
 * @return string Filtered near value.
 */
function geodir_set_search_near_text( $near, $default_near_text = '' ) {
	global $wpdb, $geodirectory;

	if ( ! defined( 'GEODIR_LOCATIONS_TABLE' ) ) {
		return $near;
	}

	// If near me then set to default as its set via JS on page
	if ( $near == __( 'Near:', 'geodirectory' ) . ' ' . __( 'Me', 'geodirectory' ) || $near == __( 'Near:', 'geodirectory' ) . ' ' . __( 'My Location', 'geodirectory' ) ) {
		return $default_near_text;
	}

	$gd_country = get_query_var( 'country' );
	$gd_region = get_query_var( 'region' );
	$gd_city = get_query_var( 'city' );
	$gd_neighbourhood = GeoDir_Location_Neighbourhood::is_active() ? get_query_var( 'neighbourhood' ) :'';

	$keep_params = geodir_location_search_keep_params();

	if ( ! empty( $keep_params ) ) {
		if ( ! empty( $keep_params['country'] ) ) {
			$gd_country = $keep_params['country'];
		} else if ( ! empty( $keep_params['region'] ) ) {
			$gd_region = $keep_params['region'];
		} else if ( ! empty( $keep_params['city'] ) ) {
			$gd_city = $keep_params['city'];
		} else if ( ! empty( $keep_params['neighbourhood'] ) ) {
			$gd_neighbourhood = $keep_params['neighbourhood'];
		}
	}

	if ( $gd_country || $gd_region || $gd_city || $gd_neighbourhood ) {
		if ( $gd_neighbourhood && geodir_get_option( 'lm_enable_neighbourhoods' ) ) {
			$neighbourhood = GeoDir_Location_Neighbourhood::get_info_by_slug( $gd_neighbourhood );

			if ( ! empty( $neighbourhood ) ) {
				$near = __( 'In:', 'geodirectory' ) . ' ' . $neighbourhood->neighbourhood . ' ' . __( '(Neighbourhood)', 'geodirlocation' );
				return $near;
			}
		}

		if ( $gd_city ) {
			$type = 'city';
			$location_slug = 'city_slug';
			$value = $gd_city;
		} else if ( $gd_region ) {
			$type = 'region';
			$location_slug = 'region_slug';
			$value = $gd_region;
		} else if ( $gd_country ) {
			$type = 'country';
			$location_slug = 'country_slug';
			$value = $gd_country;
		} else {
			return $near;
		}

		$location_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE " . $location_slug . " = %s", $value ) );

		if ( ! empty( $location_data ) ) {
			if ( $type == 'city' ) {
				$near = __( 'In:', 'geodirectory' ) . ' ' . $location_data->{$type} . ' ' . __( '(City)', 'geodirlocation' );
			} else if ( $type == 'region' ) {
				$near = __( 'In:', 'geodirectory' ) . ' ' . $location_data->{$type} . ' ' . __( '(Region)', 'geodirlocation' );
			} else if ( $type == 'country' ) {
				$near = __( 'In:', 'geodirectory' ) . ' ' . __( $location_data->{$type}, 'geodirectory' ) . ' ' . __( '(Country)', 'geodirlocation' );
			}
		}
	} else if ( ! empty( $geodirectory->location->type ) || ! empty( $keep_params['near'] ) ) {
		$type = ! empty( $geodirectory->location->type ) ? $geodirectory->location->type : $keep_params['near'];

		if( $type == 'me' ) {
			$near = __( 'Near:', 'geodirectory' ) . ' ' . __( 'My Location', 'geodirectory' );
		} else if ( $type == 'gps' ) {
			$near = wp_sprintf( __( 'Near: %s', 'geodirlocation' ), __( 'GPS Location', 'geodirlocation' ) );
		}
	}

	return $near;
}
add_filter( 'geodir_search_near_text', 'geodir_set_search_near_text', 10, 2 );

function geodir_as_add_search_location() {
	global $wpdb, $geodirectory;

	if ( ! defined('GEODIR_LOCATIONS_TABLE' ) ) {
		return;
	}

	$type = '';
	$slug = '';

	$gd_country = get_query_var( 'country' );
	$gd_region = get_query_var( 'region' );
	$gd_city = get_query_var( 'city' );
	$gd_neighbourhood = get_query_var( 'neighbourhood' );
	$gd_near_me = isset( $geodirectory->location->type ) && $geodirectory->location->type == 'me' ? true : false;
	$gd_near_gps = isset( $geodirectory->location->type ) && $geodirectory->location->type == 'gps' ? true : false;

	$keep_params = geodir_location_search_keep_params();

	if ( ! empty( $keep_params ) ) {
		if ( ! empty( $keep_params['country'] ) ) {
			$gd_country = $keep_params['country'];
		} else if ( ! empty( $keep_params['region'] ) ) {
			$gd_region = $keep_params['region'];
		} else if ( ! empty( $keep_params['city'] ) ) {
			$gd_city = $keep_params['city'];
		} else if ( ! empty( $keep_params['neighbourhood'] ) ) {
			$gd_neighbourhood = $keep_params['neighbourhood'];
		} else if ( ! empty( $keep_params['near'] ) & $keep_params['near'] == 'me' ) {
			$gd_near_me = true;
		} else if ( ! empty( $keep_params['near'] ) & $keep_params['near'] == 'gps' ) {
			$gd_near_gps = true;
		}
	}

	if ( $gd_country || $gd_region || $gd_city || $gd_neighbourhood || $gd_near_me || $gd_near_gps ) {
		if ( $gd_neighbourhood ) {
			$type = 'neighbourhood';
			$slug = $gd_neighbourhood;
		} else if ( $gd_city ) {
			$type = 'city';
			$slug = $gd_city;
		} else if ( $gd_region ) {
			$type = 'region';
			$slug = $gd_region;
		} else if ( $gd_country ) {
			$type = 'country';
			$slug = $gd_country;
		} else if ( $gd_near_me ) {
			$type = 'near';
			$slug = 'me';
		} else if ( $gd_near_gps ) {
			$type = 'near';
			$slug = 'gps';
		} else {
			return;
		}
	}

	echo '<input class="geodir-location-search-type" name="' . $type . '" type="hidden" value="' . esc_attr( $slug ) . '">';
}
add_action( 'geodir_search_hidden_fields', 'geodir_as_add_search_location', 10 );

function geodir_set_search_near_class( $class ) {
	global $geodirectory;

	$keep_params = geodir_location_search_keep_params();

	if ( get_query_var( 'country' || ! empty( $keep_params['country'] ) ) ) {
		$class = $class . ' in-location in-country';
	}

	if ( get_query_var( 'neighbourhood' ) || ! empty( $keep_params['neighbourhood'] ) ) {
		$class = $class . ' in-location in-neighbourhood';
	} else if ( get_query_var( 'city' ) || ! empty( $keep_params['city'] ) ) {
		$class = $class . ' in-location in-city';
	} else if ( get_query_var( 'region' ) || ! empty( $keep_params['region'] ) ) {
		$class = $class . ' in-location in-region';
	} else if ( ( isset( $geodirectory->location->type ) && $geodirectory->location->type == 'me' ) || ( ! empty( $keep_params['near'] ) && $keep_params['near'] == 'me' ) ) {
		$class = $class . ' in-location near-me';
	} else if ( ( isset( $geodirectory->location->type ) && $geodirectory->location->type == 'gps' ) || ( ! empty( $keep_params['near'] ) && $keep_params['near'] == 'gps' ) ) {
		$class = $class . ' in-location near-gps';
	}

	return $class;
}
add_action( 'geodir_search_near_class', 'geodir_set_search_near_class', 10, 1 );

/**
 * Get the location value from location object.
 *
 * @since 2.0.0.19
 *
 * @param string $key Location field.
 * @return null|string Location value.
 */
function geodir_location_get_current( $key ) {
	global $geodirectory;

	$value = '';
	if ( empty( $geodirectory ) ) {
		return $value;
	}

	if ( empty( $geodirectory->location ) ) {
		return $value;
	}

	if ( ! empty( $key ) && is_scalar( $key ) ) {
		if ( $key == 'location' || $key == 'location_slug' ) {
			$type = geodir_location_get_current( 'type' );

			if ( in_array( $type, array( 'country', 'region', 'city', 'neighbourhood' ) ) ) {
				$find = $key == 'location_slug' ? $type . '_slug' : $type;

				$value = geodir_location_get_current( $find );
			}
		} elseif ( isset( $geodirectory->location->{$key} ) ) {
			$value = $geodirectory->location->{$key};
		}
	}

	return $value;
}

/**
 * Get the request parameters to keep on search form CPT change.
 *
 * @since 2.2.9
 *
 * @return array Location parameters.
 */
function geodir_location_search_keep_params() {
	$params = array();

	if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'geodir_search_form' && ! empty( $_REQUEST['_gd_keep_loc'] ) && is_array( $_REQUEST['_gd_keep_loc'] ) && wp_doing_ajax() ) {
		$keep_location = $_REQUEST['_gd_keep_loc'];

		if ( ! empty( $keep_location['country'] ) ) {
			$params['country'] = sanitize_text_field( $keep_location['country'] );
		} else if ( ! empty( $keep_location['region'] ) ) {
			$params['region'] = sanitize_text_field( $keep_location['region'] );
		} else if ( ! empty( $keep_location['city'] ) ) {
			$params['city'] = sanitize_text_field( $keep_location['city'] );
		} else if ( ! empty( $keep_location['neighbourhood'] ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$params['neighbourhood'] = sanitize_text_field( $keep_location['neighbourhood'] );
		} else if ( ! empty( $keep_location['near'] ) && ( $keep_location['near'] == 'me' || $keep_location['near'] == 'gps' ) && ! empty( $keep_location['sgeo_lat'] ) && ! empty( $keep_location['sgeo_lon'] ) ) {
			$params['near'] = $keep_location['near'];
			$params['latitude'] = sanitize_text_field( $keep_location['sgeo_lat'] );
			$params['longitude'] = sanitize_text_field( $keep_location['sgeo_lon'] );
		}
	}

	return $params;
}

function geodir_location_get_search_bounds() {
	$south_west = geodir_get_option( 'lm_search_bound_sw' );
	$north_east = geodir_get_option( 'lm_search_bound_ne' );

	$bounds = array();

	if ( $south_west && $north_east ) {
		$_south_west = explode( ",", $south_west );
		$_north_east = explode( ",", $north_east );

		$sw_lat = isset( $_south_west[0] ) && ! empty( $_south_west[0] ) ? trim( $_south_west[0] ) : '';
		$sw_lng = isset( $_south_west[1] ) && ! empty( $_south_west[1] ) ? trim( $_south_west[1] ) : '';
		$ne_lat = isset( $_north_east[0] ) && ! empty( $_north_east[0] ) ? trim( $_north_east[0] ) : '';
		$ne_lng = isset( $_north_east[1] ) && ! empty( $_north_east[1] ) ? trim( $_north_east[1] ) : '';

		if ( $sw_lat && $sw_lng && $ne_lat && $ne_lng ) {
			$bounds = array(
				'sw' => array( geodir_sanitize_float( $sw_lat ), geodir_sanitize_float( $sw_lng ) ),
				'ne' => array( geodir_sanitize_float( $ne_lat ), geodir_sanitize_float( $ne_lng ) )
			);
		}
	}

	return $bounds;
}