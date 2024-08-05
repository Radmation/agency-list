<?php
/**
 * Contains functions related to Location Manager plugin update.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */

/**
 *
 *
 * @global object $wp WordPress object.
 *
 * @param $breadcrumb
 * @param $separator
 * @param bool $echo
 * @return string
 */
function geodir_location_breadcrumb( $breadcrumb, $separator, $echo= false ) {
	global $wp; 
	
	if ( geodir_is_page( 'location' ) ) {
		//$separator = str_replace( ' ', '&nbsp;', $separator );// seems to not be needed
		$location_link = geodir_get_location_link('base');
		$location_prefix = geodir_get_option('geodir_location_prefix');
		
		$breadcrumb = '';	
		$breadcrumb .= '<div class="geodir-breadcrumb clearfix"><ul id="breadcrumbs">';
		$breadcrumb .= '<li>' . apply_filters( 'geodir_breadcrumb_first_link', '<a href="' . home_url() . '">' . __( 'Home', 'geodirlocation' ) . '</a>' ) . '</li>';
     	$breadcrumb .= '<li>'.$separator;
		$breadcrumb .= '<a href="' . $location_link . '">' . __('Location','geodirlocation') . '</a>';
		$breadcrumb .= '</li>';
		
		$locations = geodir_get_current_location_terms();
		$breadcrumb .= '<li>';
		
		$hide_country_part = geodir_get_option( 'lm_hide_country_part' );
		$hide_region_part = geodir_get_option( 'lm_hide_region_part' );
		
		$hide_url_part = array();
		if ($hide_region_part && $hide_country_part) {
			$hide_url_part = array('gd_country', 'gd_region');
		} else if ($hide_region_part && !$hide_country_part) {
			$hide_url_part = array('gd_region');
		} else if (!$hide_region_part && $hide_country_part) {
			$hide_url_part = array('gd_country');
		}
			
		foreach ( $locations as $key => $location ) {
			if (in_array($key, $hide_url_part)) { // Hide location part from breadcrumb.
				continue;
			}
			
			if ( get_option('permalink_structure') != '' ) {
				$location_link .= $location;
			}
			else {
				$location_link .= '&'.$key.'='.$location;
			}
			
			$location_link = geodir_location_permalink_url( $location_link );
			
			$location = urldecode( $location );
			
			$location_actual_text = '';
			if ($key=='gd_country' && $location_actual = geodir_location_get_name('country', $location)) {
				$location_actual_text = geodir_location_get_name('country', $location, true);
			} else if ($key=='gd_region' && $location_actual = geodir_location_get_name('region', $location)) {
				$location_actual_text = geodir_location_get_name('region', $location, true);
			} else if ($key=='gd_city' && $location_actual = geodir_location_get_name('city', $location)) {
				$location_actual_text = geodir_location_get_name('city', $location, true);
			} else if ($key=='gd_neighbourhood' && $location_actual = geodir_location_get_name('neighbourhood', $location)) {
				$location_actual_text = geodir_location_get_name('neighbourhood', $location, true);
			}
			
			if ( $location != end($locations ) ) {
				$location = preg_replace('/-(\d+)$/', '',  $location);
				$location = preg_replace('/[_-]/', ' ', $location);
				$location = ucwords( $location );
				$location = __( $location, 'geodirectory' );
				$location_text = $location_actual_text!='' ? $location_actual_text : $location;
				$breadcrumb .= $separator.'<a href="'.$location_link.'">' . $location_text .'</a>';
			} else {
				$location = preg_replace('/-(\d+)$/', '',  $location);
				$location = preg_replace('/[_-]/', ' ', $location);
				$location = ucwords( $location );
				$location = __( $location, 'geodirectory' );
				$location_text = $location_actual_text!='' ? $location_actual_text : $location;
				$breadcrumb .= $separator. $location_text ;
			}
		}
		
		$breadcrumb .= '</li>';
		$breadcrumb .=  '</ul></div>';
	}
	
	if ( $echo ) {
		echo $breadcrumb;
	} else {
		return $breadcrumb;
	}
}


// New functions added from - 23rd may
$geodir_location_names = array();
/**
 *
 * @since 1.0.0
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 *
 * @global object $wpdb WordPress Database object.
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param null $args
 * @return string
 */
function geodir_get_current_location($args = null, $exit = '') {
    global $wpdb, $geodir_location_names, $gd_session;
	
	$neighbourhood_active = geodir_get_option( 'lm_enable_neighbourhoods' );
	
	$defaults = array(
		'what' => '',
		'location_text' => '',
		'blank_location_text' => '', 
		'with_link' => false, 
		'link_traget' => '',
		'container' => '' , 
		'container_class' => '' ,
		'switcher_link' => false,
		'echo' => true
	);
	
	// location picker config arguments
	$c_l_config = wp_parse_args( $args, $defaults );
	
	$order_by = '';
	$location = '';
	$what_lower = strtolower($c_l_config['what']);
	
	if (!$neighbourhood_active && $what_lower == 'neighbourhood') {
		$what_lower = 'city';
	}
	
	if ( empty($location) && $c_l_config['what'] == '') {
		if ( empty($location) && $gd_session->get('gd_multi_location') ) {
			if ($neighbourhood_active && $gd_session->get('gd_neighbourhood')) {
                if (isset($geodir_location_names['neighbourhood']) && $geodir_location_names['neighbourhood']) {
                    $location = $geodir_location_names['neighbourhood'];
                } else {
                    $gd_neighbourhood = $gd_session->get('gd_neighbourhood');
					
					$neighbourhood = GeoDir_Location_Neighbourhood::get_info_by_slug($gd_neighbourhood);
					
					if (!empty($neighbourhood)) {
						$location = stripslashes($neighbourhood->neighbourhood);
					}
					
					$gd_city = $gd_session->get('gd_city');
					$gd_region = $gd_session->get('gd_region');
					$gd_country = $gd_session->get('gd_country');
					$loc_arr = GeoDir_Location_City::get_info_by_slug($gd_city, $gd_country, $gd_region);
					
                    if (!empty($loc_arr)) {
                        $geodir_location_names['city'] = $loc_arr->city;
                        $geodir_location_names['region'] = $loc_arr->region;
                        $geodir_location_names['country'] = $loc_arr->country;
                    }
                }
				
				if ($c_l_config['what'] == '')
					$what_lower = 'neighbourhood';
			} elseif ($gd_session->get('gd_city')) {
                if (isset($geodir_location_names['city']) && $geodir_location_names['city']) {
                    $location = $geodir_location_names['city'];
                } else {
                    $gd_city = $gd_session->get('gd_city');
					$gd_region = $gd_session->get('gd_region');
					$gd_country = $gd_session->get('gd_country');
					$loc_arr = GeoDir_Location_City::get_info_by_slug($gd_city, $gd_country, $gd_region);
					
                    if (!empty($loc_arr)) {
                        $geodir_location_names['city'] = $loc_arr->city;
                        $geodir_location_names['region'] = $loc_arr->region;
                        $geodir_location_names['country'] = $loc_arr->country;
                        $location = $loc_arr->city;
                    }
                }
				
				if ($c_l_config['what'] == '')
					$what_lower = 'city';
			} elseif ($gd_session->get('gd_region')) {
                if (isset($geodir_location_names['region']) && $geodir_location_names['region']) {
                    $location = $geodir_location_names['region'];
                } else {
                    $gd_region = $gd_session->get('gd_region');
                    $loc_arr = $wpdb->get_row($wpdb->prepare("SELECT region, country FROM " . GEODIR_LOCATIONS_TABLE . " WHERE region_slug=%s", array($gd_region)));
                    
					if (!empty($loc_arr)) {
						$geodir_location_names['region'] = $loc_arr->region;
						$geodir_location_names['country'] = $loc_arr->country;
						$location = $loc_arr->region;
					}
                }
				
				if ($c_l_config['what'] == '')
					$what_lower = 'region';
			} elseif ($gd_session->get('gd_country')) {
                if (isset($geodir_location_names['country']) && $geodir_location_names['country']) {
                    $location = $geodir_location_names['country'];
                } else {
                    $gd_country = $gd_session->get('gd_country');
                    $location = $wpdb->get_var($wpdb->prepare("SELECT country FROM " . GEODIR_LOCATIONS_TABLE . " WHERE country_slug=%s", array($gd_country)));
                    $geodir_location_names['country'] = $location;
                }
				
				if ($c_l_config['what'] == '')
					$what_lower = 'country';
			}
		}	
	}
	
//	if (empty($location) && $gd_session->get('gd_multi_location')) {
//		if ($gd_what_lower = $gd_session->get('gd_' . $what_lower)) {
//			$gd_location = $gd_what_lower;
//
//            if (isset($geodir_location_names[$what_lower]) && $geodir_location_names[$what_lower]) {
//                $location = $geodir_location_names[$what_lower];
//            } else {
//                if ($what_lower == 'neighbourhood') {
//					$neighbourhood = GeoDir_Location_Neighbourhood::get_info_by_slug($gd_location);
//
//					if (!empty($neighbourhood)) {
//						$geodir_location_names['neighbourhood'] = stripslashes($neighbourhood->neighbourhood);
//					}
//				} else {
//					$loc_arr = $wpdb->get_row($wpdb->prepare("SELECT city, region, country FROM " . GEODIR_LOCATIONS_TABLE . " WHERE " . $what_lower . "_slug=%s", array($gd_location)));
//
//					if ($what_lower == 'city' && isset($loc_arr->city)) {
//						$geodir_location_names['city'] = $loc_arr->city;
//					}
//					if ($what_lower == 'region' && isset($loc_arr->region)) {
//						$geodir_location_names['region'] = $loc_arr->region;
//					}
//					if ($what_lower == 'country' && isset($loc_arr->country)) {
//						$geodir_location_names['country'] = $loc_arr->country;
//					}
//				}
//
//				$location = $geodir_location_names[$what_lower];
//            }
//		}
//	}

	if ($location!='' && $c_l_config['location_text'] != '')
		$location = $c_l_config['location_text'] ; 
	else if($location == '')
		$location = $c_l_config['blank_location_text'] ;
	
	$location_link = '' ;
	$link_a_tag_start = '' ; 
	$link_a_tag_end = '';

	if ($c_l_config['with_link']) {
        $base_location = geodir_get_location_link('base');
		$geodir_show_location_url = geodir_get_option('geodir_show_location_url');
		
		$location_link = $base_location;
		$locations = array();
		
		$gd_country = $gd_session->get('gd_country');
		$gd_region = $gd_session->get('gd_region');
		$gd_city = $gd_session->get('gd_city');
		$gd_neighbourhood = $gd_session->get('gd_neighbourhood');
		
		if ($what_lower == 'city' || $what_lower == 'neighbourhood') {
			if ($gd_country != '' && $gd_country) {
				$locations['gd_country'] = $gd_country;
			}
			
			if ($gd_region != '' && $gd_region) {
				$locations['gd_region'] = $gd_region;
			}
			
			if ($gd_city != '' && $gd_city) {
				$locations['gd_city'] = $gd_city;
			}
			
			if ($what_lower == 'neighbourhood' && $gd_neighbourhood != '' && $gd_neighbourhood) {
				$locations['gd_neighbourhood'] = $gd_neighbourhood;
			}
		} else if ($what_lower == 'region') {
			if ($geodir_show_location_url == 'all') {
				if ($gd_country != '' && $gd_country) {
					$locations['gd_country'] = $gd_country;
				}
				
				if ($gd_region != '' && $gd_region) {
					$locations['gd_region'] = $gd_region;
				}
			} else if ($geodir_show_location_url == 'country_city') {
			} else if ($geodir_show_location_url == 'region_city') {
				if ($gd_region != '' && $gd_region) {
					$locations['gd_region'] = $gd_region;
				}
			}
		} else if ($what_lower == 'country') {
			if ($geodir_show_location_url == 'all') {
				if ($gd_country != '' && $gd_country) {
					$locations['gd_country'] = $gd_country;
				}
			} else if ($geodir_show_location_url == 'country_city') {
				if ($gd_country != '' && $gd_country) {
					$locations['gd_country'] = $gd_country;
				}
			} else if ($geodir_show_location_url == 'region_city') {
			}
		}
		
		foreach ($locations as $key => $location) {
			if ( get_option('permalink_structure') != '' ) {
				$location_link .= $location;
			} else {	
				$location_link .= '&' . $key . '=' . $location;
			}
		}
		
		$location_link = geodir_location_permalink_url( $location_link );	
		
		if ($c_l_config['link_traget'] != '') {
			$link_traget = " target=\"".$link_traget."\" " ;
		}
		
		$link_a_tag_start = "<a href=\"".$location_link  ."\"  >" ; 
		$link_a_tag_end = "</a>" ;
	}	
	
	if ($location != '')	
		$location_with_link  =  $link_a_tag_start . $location . $link_a_tag_end;
	else
		$location_with_link  = '';
		
	if ($c_l_config['container'] != '')
		$location_with_link = "<" . $c_l_config['container'] . " class='" .   $c_l_config['container_class'] . "' >". $location_with_link ;
		 
	if ($c_l_config['switcher_link']) {
        $base_location= geodir_get_location_link('base');
		$location_with_link .= "<a href=\"$base_location\"><span class=\"geodir_switcher\" title=\"". __('Click to change location' ,   'geodirlocation') ."\">&nbsp;</span></a>";
	}
	
	if ($c_l_config['container'] != '')
		$location_with_link .= "</" . $c_l_config['container'] . ">" ;
		
	if ($c_l_config['echo'])
		echo $location_with_link ;
	else
		return  $location_with_link ;
}

/**
 * @param null $args
 */
function geodir_location_get_switcher($args = null) {
	$defaults = array(
		'country_default_list' => '',
		'country_text_filter' => true,
		'country_column' => true, 
		'region_default_list' => '', 
		'region_text_filter' => true,
		'region_column' => true,
		'city_default_list' => '', 
		'city_text_filter' => true,
		'city_column' => true,
		'no_of_records' => '', 
	);
	
	$neighbourhood_active = geodir_get_option( 'lm_enable_neighbourhoods' );
	if ($neighbourhood_active) {
		$defaults['neighbourhood_default_list'] = '';
		$defaults['neighbourhood_text_filter'] = true;
		$defaults['neighbourhood_column'] = true;
	}
	
	// location picker config arguments
	$l_p_config = wp_parse_args($args, $defaults);
	
	if(geodir_get_option( 'lm_default_country' ) == 'default')
		$l_p_config['country_column'] = false ; 
	
	if(geodir_get_option( 'lm_default_region' ) == 'default')
		$l_p_config['region_column'] = false ;
	
	$no_of_records = absint($l_p_config['no_of_records']);
	if (!$no_of_records > 0) {
		$default_limit = (int)geodir_get_option( 'lm_location_no_of_records' );
		$no_of_records = absint($default_limit) > 0 ? $default_limit : 50;
	}
	
	$base_location_link = geodir_get_location_link('base');
	
	$cols = 0;
	if ($l_p_config['country_column']) {
		$cols++;
	}
	if ($l_p_config['region_column']) {
		$cols++;
	}
	if ($l_p_config['city_column']) {
		$cols++;
	}
	if ($neighbourhood_active && $l_p_config['neighbourhood_column']) {
		$cols++;
	}
	$col = 1;
?>
	<div class="geodir_locListing_main">
    	<div class="geodir-common geodir_loc_clearfix gdlm-loclist-cols-<?php echo $cols;?>">
			<div class="geodir-locListing_column<?php if($l_p_config['country_column']) { echo ' gdlm-loclist-col-' . $col; $col++; };?>" style="<?php echo ($l_p_config['country_column'] ? '' : 'display:none;'); ?>" data-limit="<?php echo $no_of_records; ?>" data-type="country">
				<h2><?php _e('Country' , 'geodirlocation');?></h2>
				<input name="loc_pick_country_filter" type="text" style="<?php echo ($l_p_config['country_text_filter'] ? '' : 'display:none;'); ?>" />
				 <ul class="geodir_country_column">
				<?php
					$country_args = array(
						'what' => 'country',
						'city_val' => '', 
						'region_val' => '',
						'country_val' => '',
						'compare_operator' =>'in',
						'country_column_name' => 'country',
						'region_column_name' => 'region',
						'city_column_name' => 'city',
						'location_link_part' => true,
						'order_by' => ' asc ',
						'no_of_records' => $no_of_records,
						'format' => array('type' => 'array')
					);
					
					$total = geodir_get_location_array(array_merge($country_args, array('counts_only' => true)), false);
					$max_num_pages = ceil($total / $no_of_records);
					
					$country_loc_array = $total > 0 ? geodir_get_location_array($country_args) : NULL;
					
					if (!empty($country_loc_array)) {
						$load_more = $max_num_pages > 1 ? true : false;
						
						foreach($country_loc_array as $country_item) {
					?>
				  <li class="geodir_loc_clearfix">
					<a href="<?php echo geodir_location_permalink_url( $base_location_link . $country_item->location_link );?>"><?php echo __( $country_item->country, 'geodirectory' ) ;?></a>
					<span class="geodir_loc_arrow"><a href="javascript:void(0);">&nbsp;</a></span>
				  </li>
					<?php
						}
						if ($load_more) {
						?>
							<li class="geodir_loc_clearfix gd-loc-loadmore">
								<button class="geodir_button" data-pages="<?php echo $max_num_pages; ?>" data-title="<?php esc_attr_e( 'Loading...', 'geodirlocation' ); ?>"><i class="fas fa-sync fa-fw" aria-hidden="true"></i> <font><?php _e( 'Load more', 'geodirlocation' ) ;?></font></button>
							</li>
						<?php
 						}
					} else {
					?>
					<li class="geodir_loc_clearfix gdlm-no-results"><?php _e( 'No Results', 'geodirlocation' ); ?></li>
					<?php } ?>
				 </ul>
			</div>
		  <div class="geodir-locListing_column<?php if($l_p_config['country_column']) { echo ' gdlm-loclist-col-' . $col; $col++; };?>" style="<?php echo ($l_p_config['region_column'] ? '' : 'display:none;'); ?>" data-limit="<?php echo $no_of_records; ?>" data-type="region">
			 <h2><?php _e('Region' , 'geodirlocation');?></h2>
			 <input name="loc_pick_region_filter" type="text" style="<?php echo ($l_p_config['region_text_filter'] ? '' : 'display:none;'); ?>" />
			 <ul class="geodir_region_column">
				  <?php 
					$region_args = array(
						'what' => 'region',
						'city_val' => '', 
						'region_val' => '',
						'country_val' => '',
						'compare_operator' =>'in',
						'country_column_name' => 'country',
						'region_column_name' => 'region',
						'city_column_name' => 'city',
						'location_link_part' => true,
						'order_by' => ' asc ',
						'no_of_records' => $no_of_records,
						'format' => array('type' => 'array')
					);
					
					$total = geodir_get_location_array(array_merge($region_args, array('counts_only' => true)), false);
					$max_num_pages = ceil($total / $no_of_records);
					
					$region_loc_array = $total > 0 ? geodir_get_location_array($region_args) : NULL;
					
					if (!empty($region_loc_array)) {
						$load_more = $max_num_pages > 1 ? true : false;
						
						foreach($region_loc_array as $region_item) {
					?>
				  <li class="geodir_loc_clearfix">
					<a href="<?php echo geodir_location_permalink_url( $base_location_link . $region_item->location_link );?>"><?php echo __( $region_item->region, 'geodirectory' ) ;?></a>
					<span class="geodir_loc_arrow"><a href="javascript:void(0);">&nbsp;</a></span>
				  </li>
					<?php
						}
						if ($load_more) {
						?>
							<li class="geodir_loc_clearfix gd-loc-loadmore">
								<button class="geodir_button" data-pages="<?php echo $max_num_pages; ?>" data-title="<?php esc_attr_e( 'Loading...', 'geodirlocation' ); ?>"><i class="fas fa-sync fa-fw" aria-hidden="true"></i> <font><?php _e( 'Load more', 'geodirlocation' ) ;?></font></button>
							</li>
						<?php
 						}
					} else {
					?>
					<li class="geodir_loc_clearfix gdlm-no-results"><?php _e( 'No Results', 'geodirlocation' ); ?></li>
					<?php } ?>
			</ul>
		  </div>
		  <div class="geodir-locListing_column<?php if($l_p_config['country_column']) { echo ' gdlm-loclist-col-' . $col; $col++; };?> <?php if (!$neighbourhood_active) { ?>geodir-locListing_column_last<?php } ?>" style="<?php echo ($l_p_config['city_column']  ? '' : 'display:none;'); ?>" data-limit="<?php echo $no_of_records; ?>" data-type="city">
			 <h2><?php _e('City' , 'geodirlocation');?></h2>
			 <input name="loc_pick_city_filter" type="text" style="<?php echo ($l_p_config['city_text_filter'] ? '' : 'display:none;'); ?>" />
			 <ul class="geodir_city_column">
				 <?php 
					$city_args = array(
						'what' => 'city',
						'city_val' => '', 
						'region_val' => '',
						'country_val' => '',
						'compare_operator' =>'in',
						'country_column_name' => 'country',
						'region_column_name' => 'region',
						'city_column_name' => 'city',
						'location_link_part' => true,
						'order_by' => ' asc ',
						'no_of_records' => $no_of_records,
						'format' => array('type' => 'array')
					);
					
					$total = geodir_get_location_array(array_merge($city_args, array('counts_only' => true)), false);
					$max_num_pages = ceil($total / $no_of_records);
					
					$city_loc_array = $total > 0 ? geodir_get_location_array($city_args) : NULL;
					if (!empty($city_loc_array)) {
						$load_more = $max_num_pages > 1 ? true : false;
						
						foreach($city_loc_array as $city_item) {
					?>
					<li class="geodir_loc_clearfix">
						<a href="<?php echo geodir_location_permalink_url( $base_location_link . $city_item->location_link );?>"><?php echo __( $city_item->city, 'geodirectory' ) ;?></a>
						<?php if ($neighbourhood_active) { ?><span class="geodir_loc_arrow"><a href="javascript:void(0);">&nbsp;</a></span><?php } ?>
					</li>
					<?php
						}
						if ($load_more) {
						?>
							<li class="geodir_loc_clearfix gd-loc-loadmore">
								<button class="geodir_button" data-pages="<?php echo $max_num_pages; ?>" data-title="<?php esc_attr_e( 'Loading...', 'geodirlocation' ); ?>"><i class="fas fa-sync fa-fw" aria-hidden="true"></i> <font><?php _e( 'Load more', 'geodirlocation' ) ;?></font></button>
							</li>
						<?php
 						}
					} else {
					?>
					<li class="geodir_loc_clearfix gdlm-no-results"><?php _e( 'No Results', 'geodirlocation' ); ?></li>
					<?php } ?>
			</ul>
		  </div>
			<?php if ($neighbourhood_active) { ?>
			<div class="geodir-locListing_column<?php if($l_p_config['country_column']) { echo ' gdlm-loclist-col-' . $col; $col++; };?> geodir-locListing_column_last" style="<?php echo (!$l_p_config['neighbourhood_column'] ? 'display:none;' : '')?>" data-limit="<?php echo $no_of_records; ?>" data-type="neighbourhood">
			<h2><?php _e('Neighbourhood' , 'geodirlocation');?></h2>
			<input name="loc_pick_neighbourhood_filter" type="text" style="<?php echo (!$l_p_config['neighbourhood_text_filter'] ? 'display:none;' : '')?>" data-limit="<?php echo $no_of_records; ?>" />
			<ul class="geodir_neighbourhood_column">
				<?php 
				$neighbourhood_args = array(
					'what' => 'neighbourhood',
					'city_val' => '',
					'region_val' => '',
					'country_val' => '',
					'compare_operator' =>'in',
					'country_column_name' => 'country',
					'region_column_name' => 'region',
					'city_column_name' => 'city',
					'location_link_part' => true,
					'order_by' => ' asc ',
					'no_of_records' => $no_of_records,
					'neighbourhood_val' => '',
					'neighbourhood_column_name' => 'hood_name',
					'format' => array('type' => 'array')
				);
				
				$total = geodir_get_location_array(array_merge($neighbourhood_args, array('counts_only' => true)), false);
				$max_num_pages = ceil($total / $no_of_records);
				
				$neighbourhoods = $total > 0 ? geodir_get_location_array($neighbourhood_args) : NULL;
				
				if (!empty($neighbourhoods)) {
					$load_more = $max_num_pages > 1 ? true : false;
					
					foreach($neighbourhoods as $neighbourhood) {
				?>
				<li class="geodir_loc_clearfix">
					<a href="<?php echo geodir_location_permalink_url($base_location_link . $neighbourhood->location_link );?>"><?php echo stripslashes(__($neighbourhood->neighbourhood, 'geodirectory'));?></a>
				</li>
				<?php
					}
					if ($load_more) {
					?>
						<li class="geodir_loc_clearfix gd-loc-loadmore">
							<button class="geodir_button" data-pages="<?php echo $max_num_pages; ?>" data-title="<?php esc_attr_e( 'Loading...', 'geodirlocation' ); ?>"><i class="fas fa-sync fa-fw" aria-hidden="true"></i> <font><?php _e( 'Load more', 'geodirlocation' ) ;?></font></button>
						</li>
					<?php
 					}
				} else {
					?>
					<li class="geodir_loc_clearfix gdlm-no-results"><?php _e( 'No Results', 'geodirlocation' ); ?></li>
				<?php } ?>
				 </ul>
		  </div>
		  <?php } ?>
        </div>
     </div>
     <span><?php _e('Click on a link to filter results or on arrow to drilldown.', 'geodirlocation')?></span>
<?php
}

/**
 * @param null $args
 */
function geodir_get_location_list($args=null)
{
	$base_location = geodir_get_location_link('base') ;
	$country_list = geodir_get_location_array(array('what'=> 'country', 'format'=>array('type'=> 'array')));
	if(!empty($country_list))
	{
?>
		<ul class="geodir_all_location">
<?php 
		foreach($country_list as $country)
		{
	?>
   		 	<li>
         		<h2><a href="<?php echo geodir_location_permalink_url( $base_location . $country->location_link );?>"><?php echo $country->country; ?></a></h2>
              	<?php $region_list = geodir_get_location_array(array('what'=> 'region', 'country_val' => $country->country, 'format'=>array('type'=> 'array')));
					if(!empty($region_list))
					{
				?>		<ul class="geodir_states">	
                		<?php 
							foreach($region_list as $region)
							{
							?>
                            	<li class="geodir_region">
                                	 <h3><a href="<?php echo geodir_location_permalink_url( $base_location . $region->location_link )?>"><?php echo $region->region; ?></a></h3>
                           		<?php	$city_list = geodir_get_location_array(array('what'=> 'city', 'country_val' => $country->country,'region_val'=> $region->region, 'format'=>array('type'=> 'array')));
										if(!empty($city_list))
										{
										?>	
                                        	 <ul class="geodir_cities clearfix">          
                               		      	<?php 
											foreach($city_list as $city)
											{
											?>
                                            	 <li><a href="<?php echo geodir_location_permalink_url( $base_location . $city->location_link )?>"><?php echo $city->city; ?></a></li>
                                            <?php
                                            } // end of city list foreach
											?>
                                         	</ul> 	 	
                                     	<?php
										}// end of city list if
                                        ?>
								</li><?php // end of state list item?>
                        <?php 
							} // end of state foreach ?>
							</ul><?php
					}// end of region list if
						?>
                 
            </li><?php // end of country list item?>
    <?php
		} // end of country foreach
?>		</ul>
<?php	
	}// end of country list if
	 
}

/**
 *
 * @since 1.0.0
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param null $args
 * @return string
 */
function geodir_location_tab_switcher($args = null) {
	global $geodirectory;
	$switcher = !empty($args) && isset( $args['addSearchTermOnNorecord'] ) ? true : false;
	
	$enable_country = geodir_get_option( 'lm_default_country' );
	$enable_region = geodir_get_option( 'lm_default_region' );
	$enable_city = geodir_get_option( 'lm_default_city' );
	$neighbourhood_active = geodir_get_option( 'lm_enable_neighbourhoods' );
	
	if ($enable_country != 'default' || $enable_region != 'default' || $enable_city != 'default' || ($enable_city == 'default' && $neighbourhood_active)) {
		$defaults = array('echo' => true, 'addSearchTermOnNorecord' => 0, 'autoredirect' => false);
		$args = wp_parse_args($args, $defaults);
		
		global $wpdb;
		
		// Options
		$echo = $args['echo'];
		$addSearchTermOnNorecord = $args['addSearchTermOnNorecord'];
		$autoredirect = $args['autoredirect'];
		
		$output = '';
		$selected = '';
		$location_list = '';
		$country_div = '';
		$region_div = '';
		$city_div = '';
		$neighbourhood_div = '';
		$onchange ='';
		
		$what_is_current_location = geodir_what_is_current_location(true);
		$what_is_current_location_div = $what_is_current_location . '_div';
		
		if ($what_is_current_location != '') {
			$$what_is_current_location_div = 'gd-tab-active';
		} else {
			$what_is_current_location = apply_filters('geodir_location_switcher_default_tab', 'city');
			$what_is_current_location_div = $what_is_current_location . '_div';
			$$what_is_current_location_div = 'gd-tab-active';
		}
		
		$location_value = '';
		
		if ($autoredirect === '0') {
		} else {
			$location_value = geodir_get_location_link('base');
			$onchange = ' onchange="window.location.href=this.value" ';
			$autoredirect = '1';
		}
		
		$base_location = geodir_get_location_link('base') ;
		$current_location_array = array();
		$selected = '';
		$item_set_selected = false;
		$country_val = geodir_get_current_location(array('what' => 'country', 'echo' => false));
		$region_val = geodir_get_current_location(array('what' => 'region', 'echo' => false));
		$city_val = geodir_get_current_location(array('what' => 'city', 'echo' => false));
		
		$has_neighbourhoods = false;
		$neighbourhood_val = '';
		$neighbourhood_class = '';
		
		if ($neighbourhood_active) {
			$neighbourhood_val = $neighbourhood_active ? geodir_get_current_location(array('what' => 'neighbourhood', 'echo' => false)) : '';
			$neighbourhood_class .= ' gd-hood-switcher';
			
			if ($what_is_current_location == 'neighbourhood') {
				$has_neighbourhoods = true;
			} else {
				$args = array(
						'what' => 'neighbourhood', 
						'country_val' => (strtolower($what_is_current_location) == 'region' || strtolower($what_is_current_location) == 'city') ? $country_val : '',
						'region_val' => (strtolower($what_is_current_location) == 'city') ? $region_val : '',
						'city_val' => (strtolower($what_is_current_location) == 'city') ? $city_val : '',
						'echo' => false,
						'no_of_records' => 1,
						'format' => array('type' => 'array')
					);
				$neighbourhoods = geodir_get_location_array($args, $switcher);
				$has_neighbourhoods = !empty($neighbourhoods) ? true : false;
			}
			
			if ($has_neighbourhoods) {
				$neighbourhood_class .= ' gd-has-neighbourhoods';
			}
		}
		
		$output .= '<div class="geodir_location_tab_container' . $neighbourhood_class . '">';
		$output .= '<dl class="geodir_location_tabs_head">';
	
		if ($enable_country != 'default'):
			$output .= '<dt></dt><dd data-location="country" class="geodir_location_tabs ' . $country_div . '"><a href="javascript:void(0)">' . __('Country','geodirlocation') . '</a></dd>';
		endif;
		
		if ($enable_region != 'default'):
			$output .= '<dt></dt><dd data-location="region" class="geodir_location_tabs ' . $region_div .'"><a href="javascript:void(0)">' . __('Region','geodirlocation') . '</a></dd>';
		endif;
		
		if ($enable_city != 'default'):
			$output .= '<dt></dt><dd data-location="city" class="geodir_location_tabs ' . $city_div . '"><a href="javascript:void(0)">' . __('City','geodirlocation') . '</a></dd>';
		endif;
        
		if ($has_neighbourhoods) {
			$output .= '<dt></dt><dd data-location="neighbourhood" class="geodir_location_tabs ' . $neighbourhood_div . '"><a href="javascript:void(0)">' . __('Neighbourhood','geodirlocation') . '</a></dd>';
		}
		
		$output .= '</dl>';
		$output .= '<input type="hidden" class="selected_location" value="city" /><div style="clear:both;"></div>';
		$output .= '<div class="geodir_location_sugestion">';
		$output .= '<select class="geodir_location_switcher_chosen" name="gd_location" data-placeholder="'.__('Please wait..&hellip;', 'geodirlocation').'" data-addSearchTermOnNorecord="'.$addSearchTermOnNorecord.'" data-autoredirect="'.$autoredirect.'" '.$onchange.' data-showeverywhere="1" >';
		
		$location_switcher_list_mode = geodir_get_option( 'lm_location_switcher_list_mode' );
		if (empty($location_switcher_list_mode))
			$location_switcher_list_mode = 'drill';
		
		if ($location_switcher_list_mode == 'drill') {
			$args = array(
						'what' => $what_is_current_location, 
						'country_val' => (strtolower($what_is_current_location) == 'region' || strtolower($what_is_current_location) == 'city') ? $country_val : '',
						'region_val' => (strtolower($what_is_current_location) == 'city') ? $region_val : '',
						'echo' => false,
						'no_of_records' => '5',
						'format' => array('type' => 'array')
					);
			if ($what_is_current_location == 'neighbourhood' && $city_val != '') {
				$args['city_val'] = $city_val;
			}
		} else {
			$args = array(
						'what' => $what_is_current_location, 
						'echo' => false,
						'no_of_records' => '5',
						'format' => array('type' => 'array')
					);
		}
				
		$location_array = geodir_get_location_array($args, $switcher);
		// get country val in case of country search to get selected option
		
		if ( geodir_get_option( 'lm_everywhere_in_' . $what_is_current_location . '_dropdown' ) ) {
			$output .= '<option value="' . $base_location . '">' . __('Everywhere', 'geodirlocation') . '</option>';
		}
		
		$selected = '';
		if ( !empty( $location_array ) ) {
			foreach ( $location_array as $locations ) {
				$selected = '' ; 
				$with_parent = isset( $locations->label ) ? true : false;
				
				switch ( $what_is_current_location ) {
					case 'country':
						if ( strtolower( $country_val ) == strtolower( $locations->country ) ) {
							$selected = 'selected="selected"';
						}
						$locations->country = __( $locations->country, 'geodirectory' );
					break;
					case 'region':
						$country_iso2 = $geodirectory->location->get_country_iso2( $country_val );
						$country_iso2 = $country_iso2 != '' ? $country_iso2 : $country_val;
						$with_parent = $with_parent && strtolower( $region_val . ', ' . $country_iso2 ) == strtolower( $locations->label ) ? true : false;
						
						if ( strtolower( $region_val ) == strtolower( $locations->region ) || $with_parent ) {
							$selected = 'selected="selected"';
						}
					break;
					case 'city':
						$with_parent = $with_parent && strtolower( $city_val . ', ' . $region_val ) == strtolower( $locations->label ) ? true : false;
						
						if ( strtolower( $city_val ) == strtolower( $locations->city ) || $with_parent ) {
							$selected = 'selected="selected"';
						}
					break;
					case 'neighbourhood':
						$with_parent = $with_parent && strtolower( $neighbourhood_val . ', ' . $city_val ) == strtolower( $locations->label ) ? true : false;
						
						if ( strtolower( $neighbourhood_val ) == strtolower( $locations->neighbourhood ) || $with_parent ) {
							$selected = 'selected="selected"';
						}
					break;
				}

				$output .= '<option value="' . geodir_location_permalink_url( $base_location . $locations->location_link ) . '" ' . $selected . '>' . stripslashes( $locations->{$what_is_current_location} ) . '</option>';
				
				if ( !$item_set_selected && $selected != '' ) {
					$item_set_selected = true;
				}
			}
		}
		
		$args_current_location = array(
									'what' => $what_is_current_location,
									'compare_operator' => '=',
									'no_of_records' => '1',
									'echo' => false,
									'format'=> array('type' => 'array')
								 );
									
		if ($what_is_current_location == 'country' && $country_val != '') {
			$args_current_location['country_val'] = $country_val;
		} else if ($what_is_current_location == 'region' && $region_val != '') {
			$args_current_location['country_val'] = $country_val;
			$args_current_location['region_val'] = $region_val;
		} else if ($what_is_current_location == 'city' && $city_val != '') {
			$args_current_location['country_val'] = $country_val;
			$args_current_location['region_val'] = $region_val;
			$args_current_location['city_val'] = $city_val;
		} else if ($what_is_current_location == 'neighbourhood' && $neighbourhood_val != '') {
			$args_current_location['country_val'] = $country_val;
			$args_current_location['region_val'] = $region_val;
			$args_current_location['city_val'] = $city_val;
			$args_current_location['neighbourhood_val'] = $neighbourhood_val;
		} else {
			$args_current_location = array();
		}
		
		if (!empty($args_current_location)) {
			$current_location_array = geodir_get_location_array($args_current_location, $switcher);
		}
		
		if ( !empty( $current_location_array ) && !$item_set_selected ) {
			foreach ( $current_location_array as $current_location ) {
				$selected = '' ; 
				$with_parent = isset( $current_location->label ) ? true : false;
				
				switch ( $what_is_current_location ) {
					case 'country':
						if ( strtolower( $country_val ) == strtolower( $current_location->country ) ) {
							$selected = 'selected="selected"';
						}
						$current_location->country = __( $current_location->country, 'geodirectory' );
					break;
					case 'region':
						$country_iso2 = $geodirectory->location->get_country_iso2( $country_val );
						$country_iso2 = $country_iso2 != '' ? $country_iso2 : $country_val;
						$with_parent = $with_parent && strtolower( $region_val . ', ' . $country_iso2 ) == strtolower( $current_location->label ) ? true : false;
						
						if ( strtolower( $region_val ) == strtolower( $current_location->region ) || $with_parent ) {
							$selected = 'selected="selected"';
						}
					break;
					case 'city':
						$with_parent = $with_parent && strtolower( $city_val . ', ' . $region_val ) == strtolower( $current_location->label ) ? true : false;
						
						if ( strtolower( $city_val ) == strtolower( $current_location->city ) || $with_parent ) {
							$selected = 'selected="selected"';
						}
					break;
					case 'neighbourhood':
						$with_parent = $with_parent && strtolower( $neighbourhood_val . ', ' . $city_val ) == strtolower( $current_location->label ) ? true : false;
						
						if ( strtolower( $neighbourhood_val ) == strtolower( $current_location->neighbourhood ) || $with_parent ) {
							$selected = 'selected="selected"';
						}
					break;
				}
				
				$output .= '<option value="' . geodir_location_permalink_url( $base_location . $current_location->location_link ) . '" ' . $selected . '>' . ucwords( $current_location->{$what_is_current_location} ) . '</option>';
			}
		}
		
		$output .= '</select>';
		$output .= "</div>";
		$output .= '</div>';
		
		if ($echo)
			echo $output;
		else
			return $output;
	}
}

function geodir_location_params() {
    global $path_location_url;

	$params = array(
			'geodir_location_admin_url' => admin_url('admin.php'),
			'geodir_location_plugin_url' => GEODIR_LOCATION_PLUGIN_URL,
			'ajax_url' => admin_url('admin-ajax.php'),
			'select_merge_city_msg' => __('Please select merge city.','geodirlocation'),
			'confirm_set_default' => __('Are sure you want to make this city default?','geodirlocation'),
			'LISTING_URL_PREFIX' => __('Please enter listing url prefix', 'geodirlocation'),
			'LISTING_URL_PREFIX_INVALID_CHAR' => __('Invalid character in listing url prefix', 'geodirlocation'),
			'LOCATION_URL_PREFIX' => __('Please enter location url prefix', 'geodirlocation'),
			'LOCATOIN_PREFIX_INVALID_CHAR' => __('Invalid character in location url prefix', 'geodirlocation'),
			'LOCATION_CAT_URL_SEP' => __('Please enter location and category url separator', 'geodirlocation'),
			'LOCATION_CAT_URL_SEP_INVALID_CHAR' => __('Invalid character in location and category url separator', 'geodirlocation'),
			'LISTING_DETAIL_URL_SEP' => __('Please enter listing detail url separator', 'geodirlocation'),
			'LISTING_DETAIL_URL_SEP_INVALID_CHAR' => __('Invalid character in listing detail url separator', 'geodirlocation'),
			'LOCATION_PLEASE_WAIT' => __('Please wait...', 'geodirlocation'),
			'LOCATION_CHOSEN_NO_RESULT_TEXT' => __('Sorry, nothing found!', 'geodirlocation'),
			'LOCATION_CHOSEN_KEEP_TYPE_TEXT' => __('Please wait...', 'geodirlocation'),
			'LOCATION_CHOSEN_LOOKING_FOR_TEXT' => __('We are searching for', 'geodirlocation'),
			'select_location_translate_msg' => __( 'Please select country to update translation.', 'geodirlocation' ),
			'select_location_translate_confirm_msg' => __( 'Are you sure?', 'geodirlocation' ),
			'gd_text_search_city' => __('Search City', 'geodirlocation'),
			'gd_text_search_region' => __('Search Region', 'geodirlocation'),
			'gd_text_search_country' => __('Search Country', 'geodirlocation'),
			'gd_text_search_location' => __('Search location', 'geodirlocation'),
			'gd_base_location' => geodir_get_location_link('base'),
			'UNKNOWN_ERROR' => __('Unable to find your location.', 'geodirlocation'),
			'PERMISSION_DENINED' => __('Permission denied in finding your location.', 'geodirlocation'),
			'POSITION_UNAVAILABLE' => __('Your location is currently unknown.', 'geodirlocation'),
			'BREAK' => __('Attempt to find location took too long.', 'geodirlocation'),
			'DEFAUTL_ERROR' => __('Browser unable to find your location.', 'geodirlocation'),
			'msg_Near' => __("Near:", 'geodirectory'),
			'msg_Me' => __("Me", 'geodirectory'),
			'msg_User_defined' => __("User defined", 'geodirlocation'),
			'confirm_delete_location' => __('Deleting location will also DELETE any LISTINGS in this location. Are you sure want to DELETE this location?', 'geodirlocation'),
			'confirm_delete_neighbourhood' => __('Are you sure you want to delete this neighbourhood?', 'geodirlocation'),
			'delete_bulk_location_select_msg' => __('Please select at least one location.', 'geodirlocation'),
			'neighbourhood_is_active' => GeoDir_Location_Neighbourhood::is_active() ? true : false,
			'text_In' => __( 'In:', 'geodirectory' ),
			'autocompleter_min_chars' => absint( geodir_get_option( 'lm_autocompleter_min_chars' ) ),
			'disable_nearest_cities' => geodir_get_option( 'lm_disable_nearest_cities' ) ? true : false
    );

    return apply_filters( 'geodir_location_params', $params );
}

function geodir_location_post_form_country_options( $country = '', $prefix = '' ) {
	$country_field = geodir_get_option( 'lm_default_country' );
	$options = '';
	if ( $country_field == 'multi' ) {
		$options = geodir_get_country_dl( $country, $prefix );
	} else if ( $country_field == 'selected' ) {
		$options = geodir_get_limited_country_dl( $country, $prefix );
	}
	return $options;
}

function geodir_location_post_form_region_options( $region = '', $prefix = '', $country = '' ) {
	$args = array(
		'what' 			=> 'region',
		'country_val' 	=> $country,
		'echo' 			=> false,
		'format' 		=> array(
			'type' => 'array'
		)
	);
	if ( geodir_get_option( 'lm_location_dropdown_all' ) ) {
		$args['no_of_records'] = '10000'; // Set limit to 10 thousands as this is most browsers limit
	}

	$results = geodir_get_location_array( $args );

	$region_found = false;
	$region_options = '';
	if ( ! empty( $results ) ) {
		$value = $region ? sanitize_title( $region ) : '';
		foreach ( $results as $row ) {
			$selected = '';
			if ( $value == sanitize_title( $row->region ) ) {
				$selected = ' selected="selected"';
				$region_found = true;
			}
			$region_options .= '<option value="' . esc_attr( $row->region ) . '"' . $selected . '>' . $row->region . '</option>';
		}
	}

	$options = '<option value="">' . __( 'Select region', 'geodirlocation' ) . '</option>';
	if ( ! $region_found && $region ) {
		$options .= '<option value="' . esc_attr( $region ) . '" selected="selected">' . $region . '</option>';
	}
	$options .= $region_options;

	return $options;
}

function geodir_location_post_form_city_options( $city = '', $prefix = '', $country = '', $region = '' ) {
	$args = array(
		'what' 			=> 'city',
		'country_val' 	=> $country,
		'region_val' 	=> $region,
		'echo' 			=> false,
		'format' 		=> array(
			'type' => 'array'
		)
	);
	if ( geodir_get_option( 'lm_location_dropdown_all' ) ) {
		$args['no_of_records'] = '10000'; // Set limit to 10 thousands as this is most browsers limit
	}

	$results = geodir_get_location_array( $args );

	$city_found = false;
	$city_options = '';
	if ( ! empty( $results ) ) {
		$value = $city ? sanitize_title( $city ) : '';
		foreach ( $results as $row ) {
			$selected = '';
			if ( $value == sanitize_title( $row->city ) ) {
				$selected = ' selected="selected"';
				$city_found = true;
			}
			$city_options .= '<option value="' . esc_attr( $row->city ) . '"' . $selected . '>' . $row->city . '</option>';
		}
	}

	$options = '<option value="">' . __( 'Select city', 'geodirlocation' ) . '</option>';
	if ( ! $city_found && $city ) {
		$options .= '<option value="' . esc_attr( $city ) . '" selected="selected">' . $city . '</option>';
	}
	$options .= $city_options;

	return $options;
}

function geodir_location_post_form_neighbourhood_options( $neighbourhood = '', $prefix = '', $city = '' ) {
	$options = geodir_get_neighbourhoods_dl( wp_unslash( $city ), $neighbourhood, false );

	return $options;
}

/**
 * This is used to put country , region , city and neighbour dropdown on add/edit listing page.
 *
 * @since 1.0.0
 * @since 1.4.1 Updated to get city and state selected for go back & edit listing from preview page.
 * @since 1.5.3 Fix country code for translated country.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param array $cf The array of setting for the custom field.
 */
function geodir_location_address_extra_listing_fields( $cf ) {
    global $gd_session, $gd_post,$geodirectory;

	$location 			= $geodirectory->location->get_default_location();
    $enable_country 	= geodir_get_option( 'lm_default_country' );
    $enable_region 		= geodir_get_option( 'lm_default_region' );
    $enable_city 		= geodir_get_option( 'lm_default_city' );

    $name 				= $cf['name'];
    $is_required 		= $cf['is_required'];
    $is_default 		= $cf['is_default'];
    $required_msg 		= $cf['required_msg'];
    $extra_fields 		= stripslashes_deep( maybe_unserialize( $cf['extra_fields'] ) );

    $prefix 			= $name . '_';
	$country_label		= ! empty( $extra_fields['country_lable'] ) ? __( $extra_fields['country_lable'], 'geodirectory' ) : __( 'Country', 'geodirlocation' );
	$region_label		= ! empty( $extra_fields['region_lable'] ) ? __( $extra_fields['region_lable'], 'geodirectory' ) : __( 'Region', 'geodirlocation' );
	$city_label 		= ! empty( $extra_fields['city_lable'] ) ? __( $extra_fields['city_lable'], 'geodirectory' ) : __( 'City', 'geodirlocation' );
	$neighbourhood_label= ! empty( $extra_fields['neighbourhood_lable'] ) ? __( $extra_fields['neighbourhood_lable'], 'geodirectory' ) : __( 'Neighbourhood', 'geodirlocation' );
	
	if ( ! empty( $gd_post->city ) ) {
		$city = $gd_post->city;
	} else if ( ! empty( $location->city ) && $is_required ) {
		$city = $location->city;
	} else {
		$city = '';
	}
	if ( ! empty( $gd_post->region ) ) {
		$region = $gd_post->region;
	}  else if ( ! empty( $location->region ) && $is_required ) {
		$region = $location->region;
	} else {
		$region = '';
	}
	if ( ! empty( $gd_post->country ) ) {
		$country = $gd_post->country;
	}  else if ( ! empty( $location->country ) && $is_required ) {
		$country = $location->country;
	} else {
		$country = '';
	}
	if ( ! empty( $gd_post->neighbourhood ) ) {
		$neighbourhood = $gd_post->neighbourhood;
	} else if ( ! empty( $location->neighbourhood ) && $is_required ) {
		$neighbourhood = $location->neighbourhood;
	} else {
		$neighbourhood = '';
	}

    $country = $geodirectory->location->validate_country_name( $country );
	$region_placeholder = $enable_region == 'multi' ? __( 'Select region or type a new one', 'geodirlocation' ) : __( 'Select region', 'geodirlocation' );
	$city_placeholder = $enable_city == 'multi' ? __( 'Select city or type a new one', 'geodirlocation' ) : __( 'Select city', 'geodirlocation' );

	$design_style = geodir_design_style();

	if ( $design_style ) {
		global $geodir_label_type;

		// Country
		if ( $enable_country == 'default' ) {
			echo aui()->input(
				array(
					'id'                => $prefix . "country",
					'name'              => "country",
					'type'              => 'hidden',
					'value'            => esc_attr( $country ),
					'extra_attributes' => array(
						'data-address-type' => 'country',
						'data-country_code'        => $geodirectory->location->get_country_iso2( $country )
					),
					'wrap_attributes' => geodir_conditional_field_attrs( $cf, 'country', 'hidden' )
				)
			);
		}else{
			$required = ! empty( $is_required ) ? ' <span class="text-danger">*</span>' : '';
			echo aui()->select( array(
				'id'               => $prefix . "country",
				'name'               => "country",
				'placeholder'      => esc_attr__( 'Choose a country&hellip;', 'geodirlocation' ),
				'value'            => esc_attr( $country ),
				'required'   => $is_required,
				'label_type'       => !empty($geodir_label_type) ? $geodir_label_type : 'horizontal',
				'label'      => $country_label.$required,
				'help_text'        => __( 'Click on above field and type to filter list.', 'geodirlocation' ),
				'extra_attributes' => array(
					'data-address-type' => 'country',
					'field_type'        => $cf['type']
				),
				'options'          => geodir_location_post_form_country_options( $country, $prefix ),
				'select2'       => true,
				'wrap_attributes' => geodir_conditional_field_attrs( $cf, 'country', 'select' )
			) );
		}

		// Region
		if ( $enable_region == 'default' ) {
			echo aui()->input(
				array(
					'id'                => $prefix . "region",
					'name'              => "region",
					'type'              => 'hidden',
					'value'            => esc_attr( $region ),
					'extra_attributes' => array(
						'data-address-type' => 'region',
					),
					'wrap_attributes' => geodir_conditional_field_attrs( $cf, 'region', 'hidden' )
				)
			);
		}else{
			$required = ! empty( $is_required ) ? ' <span class="text-danger">*</span>' : '';
			echo aui()->select( array(
				'id'               => $prefix . "region",
				'name'               => "region",
				'placeholder'      => $region_placeholder,
				'value'            => esc_attr( $region ),
				'required'   => $is_required,
				'label_type'       => !empty($geodir_label_type) ? $geodir_label_type : 'horizontal',
				'label'      => $region_label.$required,
				'help_text'        => __( 'Click on above field and type to filter list or add a new region.', 'geodirlocation' ),
				'extra_attributes' => array(
					'data-address-type' => 'region',
					'field_type'        => $cf['type'],
					'data-tags'         => $enable_region == 'multi' ? "true" : "false"
				),
				'options'          => geodir_location_post_form_region_options( $region, $prefix, $country ),
				'select2'       => true,
				'wrap_attributes' => geodir_conditional_field_attrs( $cf, 'region', 'select' )
			) );
		}

		// City
		if ( $enable_city == 'default' ) {
			echo aui()->input(
				array(
					'id'                => $prefix . "city",
					'name'              => "city",
					'type'              => 'hidden',
					'value'            => esc_attr( $city ),
					'extra_attributes' => array(
						'data-address-type' => 'city',
					),
					'wrap_attributes' => geodir_conditional_field_attrs( $cf, 'city', 'hidden' )
				)
			);
		}else{
			$required = ! empty( $is_required ) ? ' <span class="text-danger">*</span>' : '';
			echo aui()->select( array(
				'id'               => $prefix . "city",
				'name'               => "city",
				'placeholder'      => $city_placeholder,
				'value'            => esc_attr( $city ),
				'required'   => $is_required,
				'label_type'       => !empty($geodir_label_type) ? $geodir_label_type : 'horizontal',
				'label'      => $city_label.$required,
				'help_text'        => __( 'Click on above field and type to filter list or add a new city.', 'geodirlocation' ),
				'extra_attributes' => array(
					'data-address-type' => 'city',
					'field_type'        => $cf['type'],
					'data-tags'         => $enable_city == 'multi' ? "true" : "false"
				),
				'options'          => geodir_location_post_form_city_options( $city, $prefix, $country, $region ),
				'select2'       => true,
				'wrap_attributes' => geodir_conditional_field_attrs( $cf, 'city', 'select' )
			) );
		}

		// Neighbourhood
		if ( GeoDir_Location_Neighbourhood::is_active() ) {
			echo aui()->select( array(
				'id'               => $prefix . "neighbourhood",
				'name'             => "neighbourhood",
				'placeholder'      => esc_attr__( 'Choose a neighbourhood&hellip;', 'geodirlocation' ),
				'value'            => esc_attr( $neighbourhood ),
				'required'         => false,
				'label_type'       => ! empty( $geodir_label_type ) ? $geodir_label_type : 'horizontal',
				'label'            => $neighbourhood_label,
				'help_text'        => __( 'Click on above field and type to filter list.', 'geodirlocation' ),
				'extra_attributes' => array(
					'data-address-type' => 'neighbourhood',
					'field_type'        => $cf['type'],
					'data-tags'         => geodir_get_option( 'lm_manual_neighbourhood' ) ? 'true' : 'false'
				),
				'options'          => geodir_location_post_form_neighbourhood_options( $neighbourhood, $prefix, $city ),
				'select2'          => true,
				'wrap_attributes'  => geodir_conditional_field_attrs( $cf, 'neighbourhood', 'select' )
			) );
		}
	} else {
		?>
		<div id="geodir_<?php echo $prefix . 'country'; ?>_row"
		     class="geodir_form_row clearfix gd-fieldset-details geodir-address-row-<?php echo $enable_country; ?><?php if ( $is_required ) {
			     echo ' required_field';
		     } ?>">
			<?php if ( $enable_country == 'default' ) { ?>
				<input type="hidden" id="<?php echo $prefix ?>country" name="country"
				       value="<?php echo esc_attr( $country ); ?>"
				       data-country_code="<?php echo $geodirectory->location->get_country_iso2( $country ); ?>"
				       field_type="<?php echo $cf['type']; ?>" data-address-type="country"/>
			<?php } else { ?>
				<label for="<?php echo $prefix ?>country"><?php echo $country_label; ?><?php if ( $is_required ) {
						echo ' <span>*</span>';
					} ?></label>
				<select id="<?php echo $prefix ?>country" name="country"
				        data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'geodirlocation' ); ?>"
				        class="geodir_textfield textfield_x geodir-select" field_type="<?php echo $cf['type']; ?>"
				        data-address-type="country">
					<?php echo geodir_location_post_form_country_options( $country, $prefix ); ?>
				</select>
				<span
					class="geodir_message_note"><?php _e( 'Click on above field and type to filter list.', 'geodirlocation' ); ?></span>
				<?php if ( $is_required ) { ?>
					<span class="geodir_message_error"><?php _e( $required_msg, 'geodirectory' ); ?></span>
				<?php } ?>
			<?php } ?>
		</div>
		<div id="geodir_<?php echo $prefix . 'region'; ?>_row"
		     class="geodir_form_row clearfix gd-fieldset-details geodir-address-row-<?php echo $enable_region; ?><?php if ( $is_required ) {
			     echo ' required_field';
		     } ?>">
			<?php if ( $enable_region == 'default' ) { ?>
				<input type="hidden" id="<?php echo $prefix ?>region" name="region" value="<?php echo $region; ?>"
				       field_type="<?php echo $cf['type']; ?>" data-address-type="region"/>
			<?php } else { ?>
				<label for="<?php echo $prefix ?>region"><?php echo $region_label; ?><?php if ( $is_required ) {
						echo ' <span>*</span>';
					} ?></label>
				<select id="<?php echo $prefix ?>region" name="region"
				        data-placeholder="<?php echo $region_placeholder; ?>"
				        class="geodir_textfield textfield_x geodir-select" field_type="<?php echo $cf['type']; ?>"
				        data-address-type="region" <?php echo( $enable_region == 'multi' ? ' data-tags="true"' : '' ); ?>>
					<?php echo geodir_location_post_form_region_options( $region, $prefix, $country ); ?>
				</select>
				<span
					class="geodir_message_note"><?php _e( 'Click on above field and type to filter list or add a new region.', 'geodirlocation' ); ?></span>
				<?php if ( $is_required ) { ?>
					<span class="geodir_message_error"><?php _e( $required_msg, 'geodirectory' ); ?></span>
				<?php } ?>
			<?php } ?>
		</div>
		<div id="geodir_<?php echo $prefix . 'city'; ?>_row"
		     class="geodir_form_row clearfix gd-fieldset-details geodir-address-row-<?php echo $enable_city; ?><?php if ( $is_required ) {
			     echo ' required_field';
		     } ?>">
			<?php if ( $enable_city == 'default' ) { ?>
				<input type="hidden" id="<?php echo $prefix ?>city" name="city" value="<?php echo $city; ?>"
				       field_type="<?php echo $cf['type']; ?>" data-address-type="city"/>
			<?php } else { ?>
				<label for="<?php echo $prefix ?>city"><?php echo $city_label; ?><?php if ( $is_required ) {
						echo ' <span>*</span>';
					} ?></label>
				<select id="<?php echo $prefix ?>city" name="city" data-placeholder="<?php echo $city_placeholder; ?>"
				        class="geodir_textfield textfield_x geodir-select" field_type="<?php echo $cf['type']; ?>"
				        data-address-type="city" <?php echo( $enable_city == 'multi' ? ' data-tags="true"' : '' ); ?>>
					<?php echo geodir_location_post_form_city_options( $city, $prefix, $country, $region ); ?>
				</select>
				<span
					class="geodir_message_note"><?php _e( 'Click on above field and type to filter list or add a new city.', 'geodirlocation' ); ?></span>
				<?php if ( $is_required ) { ?>
					<span class="geodir_message_error"><?php _e( $required_msg, 'geodirectory' ); ?></span>
				<?php } ?>
			<?php } ?>
		</div>
		<?php if ( GeoDir_Location_Neighbourhood::is_active() ) {
			$neighbourhood_options = geodir_location_post_form_neighbourhood_options( $neighbourhood, $prefix, $city ); ?>
			<div id="geodir_<?php echo $prefix . 'neighbourhood'; ?>_row"
			     class="geodir_form_row clearfix gd-fieldset-details">
				<label for="<?php echo $prefix ?>neighbourhood"><?php echo $neighbourhood_label; ?></label>
				<select id="<?php echo $prefix ?>neighbourhood" name="neighbourhood"
				        data-placeholder="<?php esc_attr_e( 'Choose a neighbourhood&hellip;', 'geodirlocation' ); ?>"
				        class="geodir_textfield textfield_x geodir-select" field_type="<?php echo $cf['type']; ?>"
				        data-address-type="neighbourhood" data-allow_clear="true">
					<?php echo $neighbourhood_options; ?>
				</select>
				<span
					class="geodir_message_note"><?php _e( 'Click on above field and type to filter list.', 'geodirlocation' ); ?></span>
			</div>
		<?php }

	}
}
add_action( 'geodir_address_extra_listing_fields', 'geodir_location_address_extra_listing_fields', 1, 1 );

function geodir_location_add_listing_geocode_js_vars() {
	global $wpdb,$geodirectory;

    if ( $default = $geodirectory->location->get_default_location() ) {
        if ( geodir_get_option( 'lm_default_country' ) == 'default' && $default->country ) {
?>
getCountry = '<?php echo addslashes_gpc( $default->country ); ?>';
baseCountry = '';
<?php
        }
    }
}

function geodir_location_check_add_listing_geocode() {
	?>
    if (window.gdMaps == 'osm' && window.osm_skip_set_on_map) {
        window.osm_skip_set_on_map = false;
		if (response.display_address) {
			return;
		}
    }
	<?php
}

/**
  * Fix conflict with Events Manager list page.
  *
  * @since 2.1.0.13
  *
  * @param bool $check_404 Check to set 404 page error.
  * @return bool True to set 404 error.
  */
function geodir_location_em_check_404( $check_404 ) {
	if ( $check_404 && is_post_type_archive( 'event' ) ) {
		$check_404 = false;
	}

	return $check_404;
}

/**
 * Add script in the head.
 *
 * @since 2.1.1.0
 *
 * @global object $geodirectory GeoDirectory object.
 *
 * @return mixed.
 */
function geodir_location_head_script() {
	global $geodirectory;

	$location = ! empty( $geodirectory ) && ! empty( $geodirectory->location ) ? $geodirectory->location : array();

	// Add near me values.
	if ( ! geodir_get_option( 'lm_hide_map_near_me' ) && ! empty( $location ) && ! empty( $location->type ) && $location->type == 'me' && ! empty( $location->latitude ) && ! empty( $location->longitude ) ) {
		$latitude = filter_var( $location->latitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$longitude = filter_var( $location->longitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );

		if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
			echo '<style>@-moz-keyframes gdnearpulse{from{-moz-transform:scale(0.25);opacity:1.0}95%{-moz-transform:scale(1.3);opacity:0}to{-moz-transform:scale(0.3);opacity:0}}@-webkit-keyframes gdnearpulse{from{-webkit-transform:scale(0.25);opacity:1.0}95%{-webkit-transform:scale(1.3);opacity:0}to{-webkit-transform:scale(0.3);opacity:0}}.gm-style [title="My Location"]{-moz-animation:gdnearpulse 1.5s ease-in-out infinite;-webkit-animation:gdnearpulse 1.5s ease-in-out infinite;border:1pt solid #fff;-moz-border-radius:51px;-webkit-border-radius:51px;border-radius:51px;-moz-box-shadow:inset 0 0 5px #06f,inset 0 0 5px #06f,inset 0 0 5px #06f,0 0 5px #06f,0 0 5px #06f,0 0 5px #06f;-webkit-box-shadow:inset 0 0 5px #06f,inset 0 0 5px #06f,inset 0 0 5px #06f,0 0 5px #06f,0 0 5px #06f,0 0 5px #06f;box-shadow:inset 0 0 5px #06f,inset 0 0 5px #06f,inset 0 0 5px #06f,0 0 5px #06f,0 0 5px #06f,0 0 5px #06f;height:51px!important;margin:-17px 0 0 -17px;width:51px!important}.gm-style [title="My Location"] img{display:none}.geodir-map-iphone .gm-style [title="' . esc_attr( strip_tags( __( 'My Location', 'geodirlocation' ) ) ) . '"]{margin:-9px 0 0 -9px}.geodir-near-marker .geodir-near-marker-wrap{height:100%;position:relative;width:100%}.geodir-near-marker-wrap .geodir-near-marker-animate{-moz-animation:gdnearpulse 1.5s ease-in-out infinite;-webkit-animation:gdnearpulse 1.5s ease-in-out infinite;border:1pt solid #fff;-moz-border-radius:51px;-webkit-border-radius:51px;border-radius:51px;-moz-box-shadow:inset 0 0 5px #06f,inset 0 0 5px #06f,inset 0 0 5px #06f,0 0 5px #06f,0 0 5px #06f,0 0 5px #06f;-webkit-box-shadow:inset 0 0 5px #06f,inset 0 0 5px #06f,inset 0 0 5px #06f,0 0 5px #06f,0 0 5px #06f,0 0 5px #06f;box-shadow:inset 0 0 5px #06f,inset 0 0 5px #06f,inset 0 0 5px #06f,0 0 5px #06f,0 0 5px #06f,0 0 5px #06f;height:51px!important;margin:-17px 0 0 -17px;width:51px!important;position:absolute}.geodir-near-marker-wrap .geodir-near-marker-img{-moz-user-select:none;border:0 none;height:17px;left:0;margin:0;max-width:none;padding:0;position:absolute;top:0;width:17px}</style>';
		}
	}
}