<?php
/**
 * Contains functions related to Location Manager widgets.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */

/**
 * Register location widgets.
 *
 * @since 2.0.0.0
 *
 * @param array $widgets The list of available widgets.
 * @return array Available GD widgets.
 */
function goedir_location_register_widgets( $widgets ) {
	if ( get_option( 'geodirectory_version' )) {
		$widgets[] = 'GeoDir_Location_Widget_Description';
		$widgets[] = 'GeoDir_Location_Widget_Location_Meta';
		$widgets[] = 'GeoDir_Location_Widget_Location_Switcher';
		$widgets[] = 'GeoDir_Location_Widget_Locations';
		$widgets[] = 'GeoDir_Location_Widget_Near_Me';
	}

	return $widgets;
}
add_filter( 'geodir_get_widgets', 'goedir_location_register_widgets', 10, 1 );

//paste this function in location_functions.php
/**
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $country
 * @param string $gd_region
 * @return bool|mixed
 */
function geodir_get_country_region_location($country = '', $gd_region = '') {
    global $wpdb;

    $location = $wpdb->get_results(
        $wpdb->prepare(
            "select * from ".GEODIR_LOCATIONS_TABLE." where country_slug = %s AND region_slug = %s ",
            array($country, $gd_region)
        )
    );

    if (!empty($location)) {
        return $location;
    } else {
        return false;
    }
}

/**
 * Get the popular location widget content.
 *
 * @since 1.5.0
 *
 * @param array $args The widget parameters. 
 * @param bool $echo If true it prints output else return content. Default true.
 * @return string The popular locations content.
 */
function geodir_popular_location_widget_output( $args = array(), $echo = true ) {
	$widget_atts = isset( $args['widget_atts'] ) ? $args['widget_atts'] : $args;
    
    // get all the cities in current region
    $location_args = array(
		'what' => 'city',
		'city_val' => '', 
		'region_val' => '',
		'country_val' =>'',
		'slugs' => '',
		'country_non_restricted' => '',
		'region_non_restricted' => '',
		'city_non_restricted' => '',
		'neighbourhood_non_restricted' => '',
		'filter_by_non_restricted' => true, 
		'compare_operator' => '=',
		'country_column_name' => 'country_slug',
		'region_column_name' => 'region_slug',
		'city_column_name' => 'city_slug',
		'location_link_part' => true,
		'order_by' => ' asc ',
		'no_of_records' => '',
		'show_current' => '',
		'format' => array( 
			'type' => 'list',
            'grid_per_row'  => 3,
			'container_wrapper' => 'ul',
			'container_wrapper_attr' => '',
			'item_wrapper' => 'li',
			'item_wrapper_attr' => ''
		)
	);

    $args = wp_parse_args( $args, $location_args );

	if ( trim( $args['slugs'] ) != '' ) {
		$args['filter_by_non_restricted'] = false;
		$args['per_page'] = '';
		$args['pagi_t'] = false;
		$args['pagi_b'] = false;
		$args['pagi_info'] = '';
		$args['no_loc'] = false;
		$args['no_location_filter'] = false;
	}

    $per_page = ! empty( $args['per_page'] ) ? absint( $args['per_page'] ) : '';
    $top_pagination = ! empty( $args['pagi_t'] ) ? true : false;
    $bottom_pagination = ! empty( $args['pagi_b'] ) ? true : false;
    $pagination_info = ! empty( $args['pagi_info'] ) ? $args['pagi_info'] : '';
    $no_location_filter = ! empty( $args['no_loc'] ) ? true : false;
	$show_current = ! $no_location_filter && ! empty( $args['show_current'] ) ? true : false;

    $args['no_of_records'] = $per_page;
    $args['counts_only'] = true;
    
    if ( ! $no_location_filter ) {
        if ( ! empty( $args['city'] ) ) {
			$args['city_val'] = $args['city'];
			$args['region_val'] = isset( $args['region'] ) ? $args['region'] : '';
			$args['country_val'] = isset( $args['country'] ) ? $args['country'] : '';
		} elseif ( ! empty( $args['region'] ) ) {
			$args['region_val'] = $args['region'];
			$args['country_val'] = isset( $args['country'] ) ? $args['country'] : '';
		} elseif ( ! empty( $args['country'] ) ) {
			$args['country_val'] = $args['country'] ;
		}

		if ( empty( $args['region_val'] ) && empty( $args['country_val'] ) ) {
			$location_terms = geodir_get_current_location_terms();
			
			if ( ! empty( $location_terms ) ) {
				if ( isset( $location_terms['city'] ) && $location_terms['city'] != '' ) {
					$args['city_val'] = $location_terms['city'];
					$args['region_val'] = isset( $location_terms['region'] ) ? $location_terms['region'] : '';
					$args['country_val'] = isset( $location_terms['country'] ) ? $location_terms['country'] : '';
				} elseif ( isset( $location_terms['region'] ) && $location_terms['region'] != '' ) {
					$args['region_val'] = $location_terms['region'];
					$args['country_val'] = isset( $location_terms['country'] ) ? $location_terms['country'] : '';
				} elseif( isset( $location_terms['country'] ) && $location_terms['country'] != '' ) {
					$args['country_val'] = $location_terms['country'] ;
				}
			}
		}

		if ( $args['what'] == 'city' ) {
			if ( isset( $args['city_val'] ) && ! $show_current ) {
				$args['city_val'] = ''; // Show all cities on city page.
			}
		} elseif ( $args['what'] == 'region' ) {
			if ( isset( $args['region_val'] ) ) {
				$args['city_val'] = '';
				if ( ! $show_current ) {
					$args['region_val'] = ''; // Show all regions on region page.
				}
			}
		} elseif ( $args['what'] == 'country' ) {
			if ( isset( $args['country_val'] ) ) {
				$args['city_val'] = '';
				$args['region_val'] = '';
				if ( ! $show_current ) {
					$args['country_val'] = ''; // Show all countries on country page.
				}
			}
		}

		// Assign location terms to widget atts.
		$widget_atts['country_val'] = $args['country_val'];
		$widget_atts['region_val'] = $args['region_val'];
		$widget_atts['city_val'] = $args['city_val'];
    }

    $total = geodir_get_location_array( $args, false );
    
    $geodir_ajax = ! empty( $args['geodir_ajax'] ) ? true : false;
        
    if ( $total > 0 ) {
        $identifier = ' gd-wgt-pagi-' . mt_rand();
        $pageno = $geodir_ajax && ! empty( $args['pageno'] ) ? $args['pageno'] : 1;
    
        $pagi_args = array(
			'pagination_info' => __( 'Showing locations %1$s-%2$s of %3$s', 'geodirlocation' ),
			'more_info' => $pagination_info,
			'class' => 'gd-pagi-pop-loc',
		);
                
        $content = '';
        $args['counts_only'] = false;
        $args['spage'] = $pageno > 0 ? $pageno - 1 : 0;
        
        if (!$geodir_ajax) {
            $content .= '<div class="gd-rows-popular-locations' . $identifier . '">';
        }
        
        if ($per_page > 0 && $top_pagination) {
            $content .= geodir_popular_location_pagination( $total, $per_page, $pageno, $pagi_args );
        }
        
        $content .= geodir_get_location_array( $args, false );
        
        if ($per_page > 0 && $bottom_pagination) {
            $content .= geodir_popular_location_pagination( $total, $per_page, $pageno, $pagi_args );
        }
        
        if ( ! $geodir_ajax ) {
            $content .= '</div><p style="display:none;" class="gd-ajax-wgt-loading"><i class="fas fa-cog fa-spin"></i></p>';
ob_start();
?>
<script type="text/javascript">
jQuery(document).on('click', '.<?php echo trim($identifier);?> .gd-wgt-page', function(e) {
    var obj = this;
    var pid = parseInt(jQuery(this).data('page'));
    var container = jQuery(obj).closest('.gd-rows-popular-locations');
    var loading = jQuery('.gd-ajax-wgt-loading', jQuery(container).closest('.geodir-widget'));
    
    if (!pid > 0 || !(container && typeof container != 'undefined')) {
        return false;
    }
    
    var scatts = "<?php echo addslashes(json_encode($widget_atts));?>";
    
    var data = {
        'action': 'gd_popular_location_list',
        '_nonce': '<?php echo wp_create_nonce("geodir-popular-location-nonce");?>',
        'geodir_ajax': true,
        'pageno': pid,
        'scatts': scatts,
    };
    
    jQuery(document).ajaxStop(function() {
        jQuery('ul', container).css({'opacity': '1'});
        loading.hide();
    });

    jQuery('ul', container).css({'opacity': '0.4'});
    loading.show();

    jQuery.post(geodir_params.ajax_url, data, function(response) {
        if (response && response != '0') {
            loading.hide();
            jQuery(container).html(response);
            geodir_init_lazy_load();
        }
    });
});
</script>
<?php
$content .= ob_get_clean();
        }
    } else {
        $content = apply_filters( 'geodir_popular_location_widget_no_location', '', $args );
    }
    
    if ( ! $echo ) {
        return $content;
    }
    
    echo $content;
}

/**
 * Get the popular location list by using ajax request.
 *
 * @since 1.5.0
 *
 * @return string Locations HTML content.
 */
function geodir_ajax_popular_location_list() {
    check_ajax_referer('geodir-popular-location-nonce', '_nonce');
    
    //set variables
    $scatts = isset($_POST['scatts']) ? $_POST['scatts'] : NULL;
    $pageno = isset($_POST['pageno']) ? absint($_POST['pageno']) : 1;
    
    $widget_atts = !empty($scatts) ? (array)json_decode(stripslashes_deep($scatts), true) : NULL;

    if (!empty($widget_atts) && is_array($widget_atts)) {

	    // escape
	    $widget_atts = array_map('esc_attr', $widget_atts);

        $widget_atts['pageno'] = $pageno;
        $widget_atts['geodir_ajax'] = true;
        $widget_atts['widget_atts'] = $widget_atts;
        
        geodir_popular_location_widget_output($widget_atts, true);
    } else {
        echo 0;
    }
    
    wp_die();
}
add_action('wp_ajax_gd_popular_location_list', 'geodir_ajax_popular_location_list');
add_action('wp_ajax_nopriv_gd_popular_location_list', 'geodir_ajax_popular_location_list');

/**
 * Get the popular location pagination.
 *
 * @since 1.5.0
 *
 * @param int $total Total number of results.
 * @param int $per_page Total number of results per each page.
 * @param int $pageno Current page number.
 * @param array $params Extra pagination parameters. 
 * @return string Pagination HTML content.
 */
function geodir_popular_location_pagination($total, $per_page, $pageno, $params = array()) {
    $defaults = array(
                    'more_info' => '',
                    'pagination_info' => __('Showing locations %1$s-%2$s of %3$s', 'geodirlocation'),
                    'before' => '',
                    'after' => '',
                    'prelabel' => '',
                    'nxtlabel' => '',
                    'pages_to_show' => 5,
                    'always_show' => false,
                    'class' => 'gd-widget-pagination',
                    'pagi_function' => 'gd_popular_location_gopage',
                );
                
    $params = wp_parse_args($params, $defaults);
    $params = apply_filters('geodir_popular_location_pagination_params', $params, $total, $per_page, $pageno);

	$design_style = geodir_design_style();

    $more_info = $params['more_info'];
    $pagination_info = $params['pagination_info'];
    $before = $params['before'];
    $after = $params['after'];
    $prelabel = $design_style ? '<i class="fas fa-chevron-left"></i>' : $params['prelabel'];
    $nxtlabel = $design_style ? '<i class="fas fa-chevron-right"></i>' : $params['nxtlabel'];
    $pages_to_show = $params['pages_to_show'];
    $always_show = $params['always_show'];
    $class = !empty($params['class']) ? sanitize_html_class($params['class']) : '';
	$links = array();
	
    if (empty($prelabel)) {
        $prelabel = '<strong>&laquo;</strong>';
    }

    if (empty($nxtlabel)) {
        $nxtlabel = '<strong>&raquo;</strong>';
    }

    $half_pages_to_show = round($pages_to_show / 2);

    $max_page = ceil($total / $per_page);

    if (empty($pageno)) {
        $pageno = 1;
    }



	$bs_link_class = $design_style ? 'page-link' : '';
	

    ob_start();
    if ($max_page > 1 || $always_show) {
        $start_no = ( $pageno - 1 ) * $per_page + 1;
        $end_no = min($pageno * $per_page, $total);
        
        if ($more_info != '' && !empty($pagination_info)) {
	        $paging_class = $design_style ? 'text-muted' : '';
            $pagination_info = '<div class="gd-pagination-details gd-pagination-details-' . $more_info . ' '.$paging_class.'">' . wp_sprintf($pagination_info, $start_no, $end_no, $total) . '</div>';
            
            if ($more_info == 'before') {
                $before = $before . $pagination_info;
            } else if ($more_info == 'after') {
                $after = $pagination_info . $after;
            }
        }
            
        echo "<div class='gd-pagi-container'> $before <div class='Navi geodir-ajax-pagination " . $class . "'>";

	    $space  = $design_style ? '' : '&nbsp;';

        if ($pageno > 1 && !$design_style) {
	        $links[] = '<a class="gd-page-sc-fst gd-wgt-page '.$bs_link_class.'" data-page="1" href="javascript:void(0);">&laquo;</a>'.$space;
        }
        
        if (($pageno - 1) > 0) {
	        $links[] = '<a class="gd-page-sc-prev gd-wgt-page '.$bs_link_class.'" data-page="' . (int)($pageno - 1) . '" href="javascript:void(0);">' . $prelabel . '</a>'.$space;
        }
        
        for ($i = $pageno - $half_pages_to_show; $i <= $pageno + $half_pages_to_show; $i++) {
            if ($i >= 1 && $i <= $max_page) {
                if ($i == $pageno) {
	                $links[] = "<strong class='on $bs_link_class current' class='gd-page-sc-act'>$i</strong>";
                } else {
	                $links[] = ' <a class="gd-page-sc-no gd-wgt-page '.$bs_link_class.'" data-page="' . (int)$i . '" href="javascript:void(0);">' . $i . '</a> ';
                }
            }
        }



        if (($pageno + 1) <= $max_page) {

	        $links[] = $space . '<a class="gd-page-sc-nxt gd-wgt-page '.$bs_link_class.'" data-page="' . (int)($pageno + 1) . '" href="javascript:void(0);">' . $nxtlabel . '</a>';
        }
        
        if ($pageno < $max_page && !$design_style) {
	        $links[] = $space . '<a class="gd-page-sc-lst gd-wgt-page '.$bs_link_class.'" data-page="' . (int)$max_page . '" href="javascript:void(0);">&raquo;</a>';
        }

	    echo implode("", $links );

        echo "</div> $after </div>";
    }
    $output = ob_get_clean();

	if ( $design_style && $max_page > 1 ) {
		$paging_args = array(
			'class'              => 'pagination-sm',
			'mid_size'           => 2,
			'prev_text'          => '<i class="fas fa-chevron-left"></i>',
			'next_text'          => '<i class="fas fa-chevron-right"></i>',
			'screen_reader_text' => __( 'Posts navigation' ),
			'before_paging' => $more_info == 'before' ? $pagination_info : '',
			'after_paging'  => $more_info == 'after' ? $pagination_info : '',
			'type'               => 'array',
			'total'              => $total,
			'links'              => $links
		);
		$output = aui()->pagination($paging_args);
	}
	

    return trim($output);
}

// add location argument to the map widget
add_filter( 'wp_super_duper_arguments', 'geodir_map_extra_arguments',10,3 );
function geodir_map_extra_arguments($arguments,$options, $instance){




    if(isset($options['base_id']) && $options['base_id']=='gd_map'){

	    $new_arr = array();

	    foreach ( $arguments as $key => $val ) {
		    $new_arr[$key] = $val;
            if('post_id' == $key){
	            $new_arr['country'] = array(
		            'type'            => 'text',
		            'title'           => __( 'Country slug', 'geodirlocation' ),
		            'desc'            => __( 'Filter the listings by country slug.', 'geodirlocation' ),
		            'placeholder'     => '',
		            'desc_tip'        => true,
		            'value'           => '',
		            'default'         => '',
		            'advanced'        => true,
		            'element_require' => '[%map_type%]=="directory"',
		            'group'           => __( 'Map Content', 'geodirectory' ),
	            );

	            $new_arr['region']   = array(
		            'type'            => 'text',
		            'title'           => __( 'Region slug', 'geodirlocation' ),
		            'desc'            => __( 'Filter the listings by region slug.', 'geodirlocation' ),
		            'placeholder'     => '',
		            'desc_tip'        => true,
		            'value'           => '',
		            'default'         => '',
		            'advanced'        => true,
		            'element_require' => '[%map_type%]=="directory"',
		            'group'           => __( 'Map Content', 'geodirectory' ),
	            );

	            $new_arr['city']   = array(
		            'type'            => 'text',
		            'title'           => __( 'City slug', 'geodirlocation' ),
		            'desc'            => __( 'Filter the listings by city slug.', 'geodirlocation' ),
		            'placeholder'     => '',
		            'desc_tip'        => true,
		            'value'           => '',
		            'default'         => '',
		            'advanced'        => true,
		            'element_require' => '[%map_type%]=="directory"',
		            'group'           => __( 'Map Content', 'geodirectory' ),
	            );

	            $new_arr['neighbourhood']   = array(
		            'type'            => 'text',
		            'title'           => __( 'Neighbourhood slug', 'geodirlocation' ),
		            'desc'            => __( 'Filter the listings by neighbourhood slug.', 'geodirlocation' ),
		            'placeholder'     => '',
		            'desc_tip'        => true,
		            'value'           => '',
		            'default'         => '',
		            'advanced'        => true,
		            'element_require' => '[%map_type%]=="directory"',
		            'group'           => __( 'Map Content', 'geodirectory' ),
	            );
            }

	    }


	    $arguments = $new_arr;
    }elseif(isset($options['base_id']) && $options['base_id']=='gd_listings'){
        $arguments['country'] = array(
            'type'            => 'text',
            'title'           => __( 'Country slug', 'geodirlocation' ),
            'desc'            => __( 'Filter the listings by country slug.', 'geodirlocation' ),
            'placeholder'     => '',
            'desc_tip'        => true,
            'value'           => '',
            'default'         => '',
            'advanced'        => true,
            'element_require' => '[%add_location_filter%]=="1"',
            'group'           => __( 'Filters', 'geodirectory' )
        );

        $arguments['region']   = array(
            'type'            => 'text',
            'title'           => __( 'Region slug', 'geodirlocation' ),
            'desc'            => __( 'Filter the listings by region slug.', 'geodirlocation' ),
            'placeholder'     => '',
            'desc_tip'        => true,
            'value'           => '',
            'default'         => '',
            'advanced'        => true,
            'element_require' => '[%add_location_filter%]=="1"',
            'group'           => __( 'Filters', 'geodirectory' )
        );

        $arguments['city']   = array(
            'type'            => 'text',
            'title'           => __( 'City slug', 'geodirlocation' ),
            'desc'            => __( 'Filter the listings by city slug.', 'geodirlocation' ),
            'placeholder'     => '',
            'desc_tip'        => true,
            'value'           => '',
            'default'         => '',
            'advanced'        => true,
            'element_require' => '[%add_location_filter%]=="1"',
            'group'           => __( 'Filters', 'geodirectory' )
        );

        $arguments['neighbourhood']   = array(
            'type'            => 'text',
            'title'           => __( 'Neighbourhood slug', 'geodirlocation' ),
            'desc'            => __( 'Filter the listings by neighbourhood slug.', 'geodirlocation' ),
            'placeholder'     => '',
            'desc_tip'        => true,
            'value'           => '',
            'default'         => '',
            'advanced'        => true,
            'element_require' => '[%add_location_filter%]=="1"',
            'group'           => __( 'Filters', 'geodirectory' )
        );
    }

    return $arguments;
}

/**
 * Filter map parameters.
 *
 * @since 2.1.1.0
 *
 * @global object $geodirectory GeoDirectory object.
 *
 * @param array $params Map parameters.
 * @param array $map_args Map options.
 * @return array Map parameters.
 */
function geodir_location_map_params( $params, $map_args = array() ) {
	global $geodirectory;

	$location = ! empty( $geodirectory ) && ! empty( $geodirectory->location ) ? $geodirectory->location : array();

	// Add near me values.
	if ( ! geodir_get_option( 'lm_hide_map_near_me' ) && ! empty( $location ) && ! empty( $location->type ) && $location->type == 'me' && ! empty( $location->latitude ) && ! empty( $location->longitude ) ) {
		$latitude = filter_var( $location->latitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$longitude = filter_var( $location->longitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );

		if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
			$near_me_icon = GEODIR_LOCATION_PLUGIN_URL . '/assets/images/nearme.png';

			/**
			 * Filter near me map marker icon.
			 *
			 * @since 2.1.1.0
			 *
			 * @param string $near_me_icon Map marker icon.
			 * @param array $params Map parameters.
			 * @param array $map_args Map options.
			 */
			$near_me_icon = apply_filters( 'geodir_location_near_me_marker_icon', $near_me_icon, $params, $map_args );

			$params['nearLat'] = $latitude;
			$params['nearLng'] = $longitude;
			$params['nearIcon'] = $near_me_icon;
			$params['nearTitle'] = strip_tags( __( 'My Location', 'geodirlocation' ) );
		}
	}

	return $params;
}