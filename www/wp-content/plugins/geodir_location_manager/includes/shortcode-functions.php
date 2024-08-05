<?php
/**
 * Contains functions related to Location Manager plugin update.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @param $atts
 * @param string $content
 * @return string
 */
function geodir_location_switcher_sc( $atts = array(), $content = '' ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}
	ob_start();
	geodir_location_get_switcher( $atts );
	$content = ob_get_clean();

	return $content;
}

/**
 * @param $atts
 * @param string $caption
 * @return string
 */
function geodir_location_list_sc( $atts = array(), $content = '' ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}
	ob_start();
	geodir_get_location_list( $atts );
	$content = ob_get_clean();
	//ob_end_clean();
	return $content;
}

/**
 * @param $atts
 * @param string $caption
 * @return string
 */
function geodir_location_tab_switcher_sc( $atts = array(), $content = '' ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}
	$atts['echo'] = false;
	
	$content = geodir_location_tab_switcher( $atts );
	
	return '<span class="geodir_shortcode_location_tab_container">' . $content . '</span>';
}

/**
 *
 * @global object $wpdb WordPress Database object.
 *
 * @since 1.0.0
 * @since 1.5.1 Fix: use of wpautop() is messing up the location description.
 *
 * @param $atts
 * @return null|string
 *
 * @global object $wp WordPress object.
 */
function geodir_location_description_sc( $atts = array(), $content = '' ) {
    global $wpdb, $wp;

	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

    $gd_country = isset( $wp->query_vars['gd_country'] ) ? $wp->query_vars['gd_country'] : '';
    $gd_region  = isset( $wp->query_vars['gd_region'] ) ? $wp->query_vars['gd_region'] : '';
    $gd_city    = isset( $wp->query_vars['gd_city'] ) ? $wp->query_vars['gd_city'] : '';
	
    $location_desc       = '';
    if ( $gd_city ) {
        $info = GeoDir_Location_City::get_info_by_slug( $gd_city, $gd_country, $gd_region );
        if ( ! empty( $info ) ) {
            $location_desc       = $info->city_desc;
        }
    } else if ( ! $gd_city && $gd_region ) {
        $info = GeoDir_Location_SEO::get_seo_by_slug( $gd_region, 'region', $gd_country );
        if ( ! empty( $info ) ) {
            $location_desc       = $info->location_desc;
        }
    } else if ( ! $gd_city && ! $gd_region && $gd_country ) {
        $info = GeoDir_Location_SEO::get_seo_by_slug( $gd_country, 'country' );
        if ( ! empty( $info ) ) {
            $location_desc       = $info->location_desc;
        }
    }
    
    $location_desc = $location_desc != '' ? stripslashes( __( $location_desc, 'geodirectory' ) ) : '';
    
    /**
     * Filter location description text..
     *
     * @since 1.4.0
     *
     * @param string $location_desc The location description text.
     * @param string $gd_country The current country slug.
     * @param string $gd_region The current region slug.
     * @param string $gd_city The current city slug.
     */
    $location_desc = apply_filters('geodir_location_description',$location_desc,$gd_country,$gd_region,$gd_city);
    if ( $location_desc == '' ) {
        return null;
    }

    $output = '<div class="geodir-category-list-in clearfix geodir-location-desc">' . $location_desc . '</div>';

    return $output;
}

 /**
 * Get the location neighbourhoods.
 *
 * @since 1.0.0
 * @since 1.4.4 Permalink added for location neighbourhood urls.
 * @since 1.5.6 Option added in location neighbourhood shortcode to use viewing CPT in links.
 *
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param array $atts Array of arguments to filter listings.
 * @return string Listings HTML content.
 */
function geodir_location_neighbourhood_sc( $atts = array(), $content = '' ) {
	global $gd_session;

	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$location_id = '';
	$location_terms = geodir_get_current_location_terms();
	if (isset($location_terms['gd_city']) && !empty($location_terms['gd_city'])) {
		$gd_city = $location_terms['gd_city'];
		$gd_region = isset($location_terms['gd_region']) ? $location_terms['gd_region'] : '';
		$gd_country = isset($location_terms['gd_country']) ? $location_terms['gd_country'] : '';
		
		$location_info = GeoDir_Location_City::get_info_by_slug($gd_city, $gd_country, $gd_region);
		$location_id = !empty($location_info) ? $location_info->location_id : 0;
	}
	
	$gd_neighbourhoods = $location_id ? GeoDir_Location_Neighbourhood::get_neighbourhoods_by_location_id($location_id) : NULL;
	
	ob_start();
	if (!empty($gd_neighbourhoods)) {
		$use_current_cpt = isset( $atts['use_current_cpt'] ) ? gdsc_to_bool_val( $atts['use_current_cpt'] ) : false;
		$post_type = !empty( $atts['post_type'] ) ? $atts['post_type'] : '';
		if ( $use_current_cpt && $current_post_type = geodir_get_current_posttype() ) {
			$post_type = $current_post_type;
		}
		
		$post_type_url = '';
		$location_page_url = '';
		if ( $post_type ) {
			if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				return;
			}
			
			if ( $post_type && geodir_get_option( 'geodir_add_location_url' ) ) {
				$location_page_url = geodir_get_location_link( 'base' );
				$set_multi_location = false;
				
				if ( $gd_session->get( 'gd_multi_location' ) ) {
					$gd_session->un_set( 'gd_multi_location' );
					$set_multi_location = true;
				}
				
				$post_type_url = get_post_type_archive_link( $post_type );
				
				if ( $set_multi_location ) {
					$gd_session->set( 'gd_multi_location', 1 );
				}
			}
		}
		?>
		<div id="geodir-category-list">
			<div class="geodir-category-list-in clearfix">
				<div class="geodir-cat-list clearfix">
				<?php
					$hood_count = 0;
					echo '<ul>';     
					foreach ($gd_neighbourhoods as $gd_neighbourhood) {
						if ($hood_count%15 == 0) {
							echo '</ul><ul>';
						}

						$neighbourhood_name = __($gd_neighbourhood->hood_name, 'geodirlocation');
						$neighbourhood_url = geodir_location_get_neighbourhood_url($gd_neighbourhood->hood_slug, true);
						if ( $post_type_url && $location_page_url ) {
							$neighbourhood_url = str_replace( untrailingslashit( $location_page_url ), untrailingslashit( $post_type_url ), $neighbourhood_url );
						}
						echo '<li><a href="' . esc_url($neighbourhood_url) . '">' . stripslashes($neighbourhood_name) . '</a></li>';
						$hood_count++;
					}
					echo '</ul>';
					?>
				</div>
			</div>
		</div>
	<?php
	}
	$output = ob_get_clean();
	
	return $output;
}

/**
 *
 * @global object $wp WordPress object.
 *
 * @param $atts
 * @return string
 */
function geodir_location_popular_locations_sc( $atts = array(), $content = '' ) {
	global $wp;

	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$location_terms = geodir_get_current_location_terms(); //locations in sessions

	// get all the cities in current region
	$args = array(
		'what'                     => 'city',
		'city_val'                 => '',
		'region_val'               => '',
		'country_val'              => '',
		'country_non_restricted'   => '',
		'region_non_restricted'    => '',
		'city_non_restricted'      => '',
		'filter_by_non_restricted' => true,
		'compare_operator'         => 'like',
		'country_column_name'      => 'country_slug',
		'region_column_name'       => 'region_slug',
		'city_column_name'         => 'city_slug',
		'location_link_part'       => true,
		'order_by'                 => ' asc ',
		'no_of_records'            => '',
		'format'                   => array(
			'type'                   => 'list',
			'container_wrapper'      => 'ul',
			'container_wrapper_attr' => '',
			'item_wrapper'           => 'li',
			'item_wrapper_attr'      => ''
		)
	);
	if ( ! empty( $location_terms ) ) {
		if ( isset( $location_terms['gd_region'] ) && $location_terms['gd_region'] != '' ) {
			$args['region_val']  = $location_terms['gd_region'];
			$args['country_val'] = $location_terms['gd_country'];
		} else if ( isset( $location_terms['gd_country'] ) && $location_terms['gd_country'] != '' ) {
			$args['country_val'] = $location_terms['gd_country'];
		}
	}
	ob_start();
	echo '<div class="geodir-sc-popular-location">';
	echo $geodir_cities_list = geodir_get_location_array( $args, false );
	echo '</div>';
	$output = ob_get_clean();
	return $output;
}
