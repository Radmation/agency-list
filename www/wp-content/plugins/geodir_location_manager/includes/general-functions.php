<?php
/**
 * Contains functions related to Location Manager plugin.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 */

/**
 * Get location by location ID.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param array $location_result Location table query results.
 * @param string $id Location ID.
 * @return array|mixed
 */
function geodir_get_location_by_id($location_result = array() , $id='')
{
	global $wpdb;
	if($id)
	{
		$get_result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM ".GEODIR_LOCATIONS_TABLE." WHERE location_id = %d",
				array($id)
			)
		);
		if(!empty($get_result))
			$location_result = $get_result;

		}
		return $location_result;
}

/**
 * Get location array using arguments.
 *
 * @since 1.0.0
 * @since 1.4.1 Modified to apply country/city & region/city url rules.
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param null|array $args {
 *    Attributes of args.
 *
 *    @type string $what What do you want to query. Possible values: city, region, country. Default: 'city'.
 *    @type string $city_val City value.
 *    @type string $region_val Region value.
 *    @type string $country_val Country value.
 *    @type string $country_non_restricted Non restricted countries.
 *    @type string $region_non_restricted Non restricted regions.
 *    @type string $city_non_restricted Non restricted cities.
 *    @type bool $filter_by_non_restricted Filter by non restricted?.
 *    @type string $compare_operator Comparison operator.
 *    @type string $country_column_name Country column name.
 *    @type string $region_column_name Region column name.
 *    @type string $city_column_name City column name.
 *    @type bool $location_link_part Location link part.
 *    @type string $order_by Order by value.
 *    @type string $no_of_records No of records to return.
 *    @type string $spage Current page number.
 *    @type string $search_term Search value in particular field with wildcard.
 *    @type array $format {
 *        Attributes of format.
 *
 *        @type string $type Type. Default: 'list'.
 *        @type string $container_wrapper Container wrapper. Default: 'ul'.
 *        @type string $container_wrapper_attr Container wrapper attr.
 *        @type string $item_wrapper Item wrapper. Default: 'li'.
 *        @type string $item_wrapper_attr Item wrapper attr.
 *
 *    }
 *
 * }
 * @param bool $switcher Todo: describe this part.
 * @return array|mixed|string
 */
function geodir_get_location_array( $args = null, $switcher = false ) {
	global $wpdb,$geodirectory;
	//escape values
	if(isset($args['city_val'])){$args['city_val'] = esc_sql(wp_unslash($args['city_val']));}
	if(isset($args['region_val'])){$args['region_val'] = esc_sql(wp_unslash($args['region_val']));}
	if(isset($args['country_val'])){$args['country_val'] = esc_sql(wp_unslash($args['country_val']));}

	$permalink_structure = get_option('permalink_structure');
	$hide_country_part = geodir_get_option( 'lm_hide_country_part' );
	$hide_region_part = geodir_get_option( 'lm_hide_region_part' );
	$neighbourhood_active = geodir_get_option( 'lm_enable_neighbourhoods' );
	$defaults = array(
		'what' => 'city',
		'slugs' => '',
		'city_val' => '',
		'region_val' => '',
		'country_val' => '',
		'country_non_restricted' => '',
		'region_non_restricted' => '',
		'city_non_restricted' => '',
		'neighbourhood_non_restricted' => '',
		'filter_by_non_restricted' => true,
		'compare_operator' => 'like',
		'country_column_name' => 'country',
		'region_column_name' => 'region',
		'city_column_name' => 'city',
		'location_link_part' => true,
		'order_by' => 'asc',
		'no_of_records' => '',
		'spage' => '',
		'counts_only' => false,
		'search_term' => '',
		'fallback_image' => false,
		'format' => array(
			'type' => 'list',
			'container_wrapper' => 'ul',
			'container_wrapper_attr' => '',
			'item_wrapper' => 'li',
			'item_wrapper_attr' => ''
		)
	);
	if ($neighbourhood_active) {
		$defaults['neighbourhood_val'] = '';
		$defaults['neighbourhood_column_name'] = 'hood_name';
	}

	$location_args = wp_parse_args( $args, $defaults );

	//escaping
	$location_args['order_by'] = $location_args['order_by'] ? sanitize_sql_orderby($location_args['order_by']) : '';
	$location_args['what'] = in_array($location_args['what'],array('country','region','city','neighbourhood')) ? esc_attr($location_args['what']) : '';

	if (!$neighbourhood_active) {
		if (isset($defaults['neighbourhood_val'])) {
			unset($defaults['neighbourhood_val']);
		}
		
		if ($location_args['what'] == 'neighbourhood') {
			$location_args['what'] = 'city';
		}
	}

	$search_query = '';
	$location_link_column = '';
	$location_default = $geodirectory->location->get_default_location();

	if( $location_args['filter_by_non_restricted'] ) {
		// Non restricted countries
		if( $location_args['country_non_restricted'] == '' ) {
			if( geodir_get_option( 'lm_default_country' ) == 'default' ) {
				$country_non_retsricted = isset( $location_default->country ) ? $location_default->country : '';
				$location_args['country_non_restricted']  = $country_non_retsricted;
			} else if( geodir_get_option( 'lm_default_country' ) == 'selected' ) {
				$country_non_retsricted = geodir_get_option( 'lm_selected_countries' );

				if( !empty( $country_non_retsricted ) && is_array( $country_non_retsricted ) ) {
					$country_non_retsricted = implode(',' , $country_non_retsricted );
				}

				$location_args['country_non_restricted'] = $country_non_retsricted;
			}

			//$location_args['country_non_restricted'] = geodir_parse_location_list( $location_args['country_non_restricted'] );
		}

		//Non restricted Regions
		if( $location_args['region_non_restricted'] == '' ) {
			if( geodir_get_option( 'lm_default_region' ) == 'default' ) {
				$regoin_non_restricted= isset( $location_default->region ) ? $location_default->region : '';
				$location_args['region_non_restricted']  = $regoin_non_restricted;
			} else if( geodir_get_option( 'lm_default_region' ) == 'selected' ) {
				$regoin_non_restricted = geodir_get_option( 'lm_selected_regions' );
				if( !empty( $regoin_non_restricted ) && is_array( $regoin_non_restricted ) ) {
					$regoin_non_restricted = implode( ',', $regoin_non_restricted );
				}

				$location_args['region_non_restricted']  = $regoin_non_restricted;
			}

			//$location_args['region_non_restricted'] = geodir_parse_location_list( $location_args['region_non_restricted'] );
		}

		//Non restricted cities
		if( $location_args['city_non_restricted'] == '' ) {
			if( geodir_get_option( 'lm_default_city' ) == 'default' ) {
				$city_non_retsricted = isset( $location_default->city ) ? $location_default->city : '';
				$location_args['city_non_restricted']  = $city_non_retsricted;
			} else if( geodir_get_option( 'lm_default_city' ) == 'selected' ) {
				$city_non_restricted = geodir_get_option( 'lm_selected_cities' );

				if( !empty( $city_non_restricted ) && is_array( $city_non_restricted ) ) {
					$city_non_restricted = implode( ',', $city_non_restricted );
				}

				$location_args['city_non_restricted']  = $city_non_restricted;
			}
			//$location_args['city_non_restricted'] = geodir_parse_location_list( $location_args['city_non_restricted'] );
		}
	}

	if ( $location_args['what'] == '') {
		$location_args['what'] = 'city';
	}

	if ( $location_args['location_link_part'] ) {
		switch( $location_args['what'] ) {
			case 'country':
				if ( $permalink_structure != '' ) {
					$location_link_column = ", CONCAT_WS( '/', country_slug ) AS location_link ";
				} else {
					$location_link_column = ", CONCAT_WS( '&gd_country=', '', country_slug ) AS location_link ";
				}
				break;
			case 'region':
				if ( $permalink_structure != '' ) {
					if ( ! $hide_country_part ) {
						$location_link_column = ", CONCAT_WS( '/', country_slug, region_slug ) AS location_link ";
					} else {
						$location_link_column = ", CONCAT_WS( '/', region_slug ) AS location_link ";
					}
				} else {
					if ( ! $hide_country_part ) {
						$location_link_column = ", CONCAT_WS( '&', CONCAT( '&gd_country=', country_slug ), CONCAT( 'gd_region=', region_slug ) ) AS location_link ";
					} else {
						$location_link_column = ", CONCAT_WS( '&gd_region=', '', region_slug ) AS location_link ";
					}
				}
				break;
			case 'city':
			case 'neighbourhood':
				$concat_ws = array();

				if ( $permalink_structure != '' ) {
					if ( ! $hide_country_part ) {
						$concat_ws[] = 'country_slug';
					}

					if ( ! $hide_region_part ) {
						$concat_ws[] = 'region_slug';
					}

					$concat_ws[] = 'city_slug';

					if ( $location_args['what'] == 'neighbourhood' ) {
						$concat_ws[] = 'hood_slug';
					}

					$concat_ws = implode( ', ', $concat_ws );

					$location_link_column = ", CONCAT_WS( '/', " . $concat_ws . " ) AS location_link ";
				} else {
					$amp = '&';
					if ( ! $hide_country_part ) {
						$concat_ws[] = "CONCAT( '" . $amp . "gd_country=', country_slug )";
						$amp = '';
					}

					if ( ! $hide_region_part ) {
						$concat_ws[] = "CONCAT( '" . $amp . "gd_region=', region_slug )";
						$amp = '';
					}

					$concat_ws[] = "CONCAT( '" . $amp . "gd_city=', city_slug )";

					if ( $location_args['what'] == 'neighbourhood' ) {
						$amp = '';
						$concat_ws[] = "CONCAT( '" . $amp . "gd_neighbourhood=', hood_slug )";
					}

					$concat_ws = implode( ', ', $concat_ws );

					$location_link_column = ", CONCAT_WS( '&', " . $concat_ws . " ) AS location_link ";
				}
				break;
		}
	}

	switch( $location_args['compare_operator'] ) {
		case 'like' :
			if ( isset( $location_args['country_val'] ) && $location_args['country_val'] != '' ) {
				$countries_search_sql = geodir_countries_search_sql( $location_args['country_val'] );
				$countries_search_sql = $countries_search_sql != '' ? " OR FIND_IN_SET( country, '" . $countries_search_sql . "' )" : '';
				$translated_country_val = sanitize_title( trim( wp_unslash( $location_args['country_val'] ) ) );
				$search_query .= " AND ( LOWER( " . $location_args['country_column_name'] . " ) LIKE \"%" . geodir_strtolower( $location_args['country_val'] ) . "%\" OR  LOWER( country_slug ) LIKE \"" . $translated_country_val . "%\" OR country_slug LIKE '" . urldecode( $translated_country_val ) . "' " . $countries_search_sql . " ) ";
			}

			if ( isset( $location_args['region_val'] ) && $location_args['region_val'] != '' ) {
				$search_query .= " AND LOWER( ".$location_args['region_column_name'] . " ) LIKE \"%" . geodir_strtolower( $location_args['region_val'] ) . "%\" ";
			}

			if ( isset( $location_args['city_val'] ) && $location_args['city_val'] != '' ) {
				$search_query .= " AND LOWER( " . $location_args['city_column_name'] . " ) LIKE \"%" . geodir_strtolower( $location_args['city_val'] ) . "%\" ";
			}
			
			if ( isset( $location_args['neighbourhood_val'] ) && $location_args['neighbourhood_val'] != '' ) {
				$search_query .= " AND LOWER( " . $location_args['neighbourhood_column_name'] . " ) LIKE \"%" . geodir_strtolower( $location_args['neighbourhood_val'] ) . "%\" ";
			}
			break;
		case 'in' :
			if ( isset( $location_args['country_val'] ) && $location_args['country_val'] != '' ) {
				$location_args['country_val'] = geodir_parse_location_list( $location_args['country_val'] ) ;
				$search_query .= " AND LOWER( " . $location_args['country_column_name'] . " ) IN( $location_args[country_val] ) ";
			}

			if ( isset( $location_args['region_val'] ) && $location_args['region_val'] != '' ) {
				$location_args['region_val'] = geodir_parse_location_list( $location_args['region_val'] ) ;
				$search_query .= " AND LOWER( " . $location_args['region_column_name'] . " ) IN( $location_args[region_val] ) ";
			}

			if ( isset( $location_args['city_val'] ) && $location_args['city_val'] != '' ) {
				$location_args['city_val'] = geodir_parse_location_list( $location_args['city_val'] ) ;
				$search_query .= " AND LOWER( " . $location_args['city_column_name'] . " ) IN( $location_args[city_val] ) ";
			}
			
			if ( isset( $location_args['neighbourhood_val'] ) && $location_args['neighbourhood_val'] != '' ) {
				$location_args['neighbourhood_val'] = geodir_parse_location_list( $location_args['neighbourhood_val'] ) ;
				$search_query .= " AND LOWER( " . $location_args['neighbourhood_column_name'] . " ) IN( $location_args[neighbourhood_val] ) ";
			}

			break;
		default :
			if ( isset( $location_args['country_val'] ) && $location_args['country_val'] != '' ) {
				$countries_search_sql = geodir_countries_search_sql( $location_args['country_val'] );
				$countries_search_sql = $countries_search_sql != '' ? " OR FIND_IN_SET( country, '" . $countries_search_sql . "' )" : '';
				$translated_country_val = sanitize_title( trim( wp_unslash( $location_args['country_val'] ) ) );
				$search_query .= " AND ( LOWER( " . $location_args['country_column_name'] . " ) = '" . geodir_strtolower( $location_args['country_val'] ) . "' OR LOWER( country_slug ) LIKE \"" . $translated_country_val . "%\" OR country_slug LIKE '" . urldecode( $translated_country_val ) . "' " . $countries_search_sql . " ) ";
			}

			if ( isset( $location_args['region_val'] ) && $location_args['region_val'] != '' ) {
				if ( $location_args['search_term'] == 'region' ) {
					$search_query .= " AND LOWER( " . $location_args['region_column_name'] . " ) LIKE \"%" . geodir_strtolower( $location_args['region_val'] ) . "%\" ";
				} else {
					$search_query .= " AND LOWER( " . $location_args['region_column_name'] . " ) = \"" . geodir_strtolower( $location_args['region_val'] ) . "\" ";
				}
			}

			if ( isset( $location_args['city_val'] ) && $location_args['city_val'] != '' ) {
				if ( $location_args['search_term'] == 'city' ) {
					$search_query .= " AND LOWER( " . $location_args['city_column_name'] . " ) LIKE \"%" . geodir_strtolower( $location_args['city_val'] ) . "%\" ";
				} else {
					$search_query .= " AND LOWER( " . $location_args['city_column_name'] . " ) = \"" . geodir_strtolower( $location_args['city_val'] ) . "\" ";
				}
			}

			if ( isset( $location_args['neighbourhood_val'] ) && $location_args['neighbourhood_val'] != '' ) {
				$search_query .= " AND LOWER( " . $location_args['neighbourhood_column_name'] . " ) = \"" . geodir_strtolower( $location_args['neighbourhood_val'] ) . "\" ";
			}
			break ;
	} // end of switch

	if ( $location_args['country_non_restricted'] != '' ) {
		$search_query .= " AND LOWER( country ) IN( ".geodir_parse_location_list( $location_args['country_non_restricted'] )." ) ";
	}

	if ( $location_args['region_non_restricted'] != '' ) {
		if ( $location_args['what'] == 'region' ) {
			$search_query .= " AND LOWER( region ) IN( ".geodir_parse_location_list( $location_args['region_non_restricted'] )." ) ";
		}
	}

	if ( $location_args['city_non_restricted'] != '' ) {
		if ( $location_args['what'] == 'city' ) {
			$search_query .= " AND LOWER( city ) IN( ".geodir_parse_location_list( $location_args['city_non_restricted'] )." ) ";
		}
	}

	if ( $location_args['what'] == 'neighbourhood' && $location_args['neighbourhood_non_restricted'] != '' ) {
		$search_query .= " AND LOWER( hood_name ) IN( ".geodir_parse_location_list($location_args['neighbourhood_non_restricted'])." ) ";
	}

	$slugs = trim( $location_args['slugs'] ) != '' ? geodir_parse_location_list( $location_args['slugs'] ) : '';
	if ( ! empty( $slugs ) ) {
		if ( $location_args['what'] == 'neighbourhood' ) {
			$search_query .= " AND hood_slug IN( $slugs ) ";
		} else {
			$search_query .= " AND " . $location_args['what'] . "_slug IN( $slugs ) ";
		}
	}

	// page
	if ( $location_args['no_of_records'] ) {
		$spage = (int)$location_args['no_of_records'] * (int)$location_args['spage'];
	} else {
		$spage = 0;
	}

	// limit
	$limit = $location_args['no_of_records'] != '' ? ' LIMIT ' . $spage . ', ' . (int)$location_args['no_of_records'] . ' ' : '';

	// display all locations with same name also
	$search_field = $location_args['what'];
	
	if ( $switcher ) {
		$select = $search_field . $location_link_column;
		$group_by = $search_field;
		$order_by = $search_field;
		
		if ( $search_field == 'city' ) {
			$select .= ', country, region, city, country_slug, region_slug, city_slug';
			$group_by = 'country, region, city';
			$order_by = 'city, region, country';
		} else if ( $search_field == 'neighbourhood' ) {
			$select = "hood_name AS neighbourhood " . $location_link_column;
			$select .= ', country, region, city, hood_name AS neighbourhood, country_slug, region_slug, city_slug, hood_slug AS neighbourhood_slug';
			$group_by = 'country, region, city, hood_name';
			$order_by = 'hood_name, city, region, country';
		} else if( $search_field == 'region' ) {
			$select .= ', country, region, country_slug, region_slug';
			$group_by = 'country, region';
			$order_by = 'region, country';
		} else if( $search_field == 'country' ) {
			$select .= ', country, country_slug';
			$group_by = 'country';
			$order_by = 'country';
		}
		
		if ( $search_field == 'neighbourhood' ) {
			$main_location_query = "SELECT " . $select . " FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " AS h LEFT JOIN " . GEODIR_LOCATIONS_TABLE . " AS l ON l.location_id = h.hood_location_id WHERE 1=1 " . $search_query . " GROUP BY " . $group_by . " ORDER BY " . $order_by . " " . $location_args['order_by'] . " " . $limit;
		} else {
			$main_location_query = "SELECT " . $select . " FROM " . GEODIR_LOCATIONS_TABLE . " WHERE 1=1 " . $search_query . " GROUP BY " . $group_by . " ORDER BY " . $order_by . " " . $location_args['order_by'] . " " . $limit;
		}
	} else {
		$counts_only = ! empty( $location_args['counts_only'] ) ? true : false;

		$order_by = '';
		if ( $counts_only ) {
			$limit = '';
		}

		if ( $search_field == 'neighbourhood' ) {
			$fields = $counts_only ? "COUNT(*)" : "h.*, l.*, hood_name AS neighbourhood, hood_slug AS neighbourhood_slug " . $location_link_column;
			if ( empty( $counts_only ) ) {
				$order_by = "ORDER BY hood_name " . $location_args['order_by'];
			}

			$main_location_query = "SELECT " . $fields . " FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " AS h LEFT JOIN " . GEODIR_LOCATIONS_TABLE . " AS l ON l.location_id = h.hood_location_id WHERE 1=1 " .  $search_query . " GROUP BY hood_name {$order_by} " . $limit;
		} else {
			$fields = $counts_only ? "COUNT(*)" : "*" . " " . $location_link_column;
			if ( empty( $counts_only ) ) {
				$order_by = "ORDER BY $location_args[what] $location_args[order_by]";
			}

			$main_location_query = "SELECT " . $fields . " FROM "  . GEODIR_LOCATIONS_TABLE . " WHERE 1=1 " . $search_query . " GROUP BY $location_args[what] {$order_by} $limit";
		}

		if ( $counts_only ) {
			$count_locations = $wpdb->get_results( $main_location_query );
			return ! empty( $count_locations ) && is_array( $count_locations ) ? count( $count_locations ) : 0;
		}
	}

	$locations = $wpdb->get_results( $main_location_query );

	if( $switcher && !empty( $locations ) ) {
		$new_locations = array();

		foreach( $locations as $location ) {
			$new_location = $location;
			$label = $location->{$search_field};
			if( ( $search_field == 'city' || $search_field == 'neighbourhood' || $search_field == 'region' ) && (int)geodir_location_check_duplicate( $search_field, $label ) > 1 ) {

				if( $search_field == 'neighbourhood' ) {
					$label .= ', ' . $location->city;
				} else if( $search_field == 'city' ) {
					$label .= ', ' . $location->region;
				} else if( $search_field == 'region' ) {
					$country_iso2 = $geodirectory->location->get_country_iso2( $location->country );
					$country_iso2 = $country_iso2 != '' ? $country_iso2 : $location->country;
					$label .= $country_iso2 != '' ? ', ' . $country_iso2 : '';
				}
			}
			$new_location->title = stripslashes($location->{$search_field});
			$new_location->{$search_field} = stripslashes($label);
			$new_location->label = stripslashes($label);
			$new_locations[] = $new_location;
		}
		$locations = $new_locations;
	}

	if ( ! empty( $location_args['format'] ) ) {
		if ( $location_args['format']['type'] == 'array' )
			return $locations ;
		elseif ( $location_args['format']['type'] == 'json' )
			return json_encode( $locations ) ;
		else {
			$base_location_link = geodir_get_location_link('base');
			$container_wrapper = '' ;
			$container_wrapper_attr = '' ;
			$item_wrapper = '' ;
			$item_wrapper_attr = '' ;

			if (isset($location_args['format']['container_wrapper']) && !empty($location_args['format']['container_wrapper']))
				$container_wrapper = $location_args['format']['container_wrapper'] ;

			if (isset($location_args['format']['container_wrapper_attr']) && !empty($location_args['format']['container_wrapper_attr']))
				$container_wrapper_attr = $location_args['format']['container_wrapper_attr'] ;

			if (isset($location_args['format']['item_wrapper']) && !empty($location_args['format']['item_wrapper']))
				$item_wrapper = $location_args['format']['item_wrapper'] ;

			if (isset($location_args['format']['item_wrapper_attr']) && !empty($location_args['format']['item_wrapper_attr']))
				$item_wrapper_attr = $location_args['format']['item_wrapper_attr'] ;


			$design_style = geodir_design_style();
//			print_r( $location_args );echo '###';
			####
			if(isset($location_args['output_type']) && $location_args['output_type']=='grid'){

				$grid_font_class = sd_build_aui_class(
					array(
						'font_case'     => $location_args['grid_font_case'],
						'font_weight'   => $location_args['grid_font_weight'],
						'text_align'    => $location_args['grid_text_align'],
						'text_align_md' => $location_args['grid_text_align_md'],
						'text_align_lg' => $location_args['grid_text_align_lg'],
						'font_size'     => $location_args['grid_font_size'],
					)
				);

				$template = $design_style ? $design_style."/location-grid.php" : "loop/location-grid.php";
				return geodir_get_template_html(
					$template,
					array(
						'container_wrapper' => $container_wrapper,
						'base_location_link'  => $base_location_link,
						'container_wrapper_attr'  => $container_wrapper_attr,
						'item_wrapper'  => $item_wrapper,
						'item_wrapper_attr'  => $item_wrapper_attr,
						'locations'  => $locations,
						'location_args'  => $location_args,
						'grid_per_row'  => $location_args['grid_per_row'] ? absint( $location_args['grid_per_row'] ) : 3,
						'grid_item_aspect'  => $location_args['grid_item_aspect'] ? 'embed-responsive embed-responsive-' . esc_attr( $location_args['grid_item_aspect'] ) : 'embed-responsive embed-responsive-4by3',
						'lightbox_attrs' => apply_filters( 'geodir_link_to_lightbox_attrs', '' ),
						'grid_font_class'   => $grid_font_class,
					),
					'',
					plugin_dir_path( GEODIR_LOCATION_PLUGIN_FILE ). "/templates/"
				);

			}else{

				$template = $design_style ? $design_style."/location-list.php" : "loop/location-list.php";
				return geodir_get_template_html(
					$template,
					array(
						'container_wrapper' => $container_wrapper,
						'base_location_link'  => $base_location_link,
						'container_wrapper_attr'  => $container_wrapper_attr,
						'item_wrapper'  => $item_wrapper,
						'item_wrapper_attr'  => $item_wrapper_attr,
						'locations'  => $locations,
						'location_args'  => $location_args,
						'btn_args'  => $location_args['widget_atts']
					),
					'',
					plugin_dir_path( GEODIR_LOCATION_PLUGIN_FILE ). "/templates/"
				);
			}

		}
	}
	return $locations ;
}

/**
 * Check location duplicates.
 *
 * @since 1.0.0
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $field The field to check for duplicates.
 * @param string $location The location value to check for duplicates.
 * @return int Total rows found.
 */
function geodir_location_check_duplicate( $field, $location ) {
	global $wpdb;

	$sql = '';
	$result = 0;
	if( $field == 'city' ) {
		$sql = $wpdb->prepare( "SELECT COUNT(*) AS total FROM " . GEODIR_LOCATIONS_TABLE . " WHERE " . $field . "=%s GROUP BY " . $field, $location );
		$row = $wpdb->get_results( $sql );
		if( !empty( $row ) && isset( $row[0]->total ) ) {
			$result = (int)$row[0]->total;
		}
	} else if( $field == 'region' ) {
		$sql = $wpdb->prepare( "SELECT COUNT(*) AS total FROM " . GEODIR_LOCATIONS_TABLE . " WHERE " . $field . "=%s GROUP BY country, " . $field, $location );
		$row = $wpdb->get_results( $sql );
		if( !empty( $row ) && count( $row ) > 0 ) {
			$result = (int)count( $row );
		}
	} else if( $field == 'neighbourhood' ) {
		$field = 'hood_name';
		
		$sql = $wpdb->prepare( "SELECT COUNT(*) AS total FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE " . $field . "=%s GROUP BY " . $field, $location );
		$row = $wpdb->get_results( $sql );
		if( !empty( $row ) && isset( $row[0]->total ) ) {
			$result = (int)$row[0]->total;
		}
	}
	return $result;
}

/**
 * Returns countries array.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $from Get countries from table or option?
 * @return array Countries array.
 */
function geodir_get_countries_array( $from = 'table' ) {
    global $wpdb;

    if ( $from == 'table' ) {
        $countries = geodir_get_countries();

    } else {
        $countries = geodir_get_option( 'lm_selected_countries' );
    }
    
    $countries_array = array();
	
    foreach ( $countries as $key => $country ) {
        $countries_array[$country] = __( $country, 'geodirectory' ) ;
    }
    asort($countries_array);

    return $countries_array ;
}

/**
 * Get countries in a dropdown.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param $selected_option
 */
function geodir_get_limited_country_dl( $selected_option ) {
	$selected_countries = geodir_get_countries_array( 'saved_option' );
	$rows = function_exists( 'geodir_wp_countries' ) ? geodir_wp_countries() : wp_country_database()->get_countries();

	$ISO2 = array();
	$countries = array();
	$latlng = array();

	foreach ( $rows as $row ) {
		if ( isset( $selected_countries[ $row->name ] ) ) {
			$ISO2[$row->name] = $row->alpha2Code;
			$countries[ $row->name ] = $selected_countries[ $row->name ];
			if ( ! empty( $row->latlng ) ) {
				$_latlng = explode( ',', $row->latlng );

				if ( ! empty( $_latlng[0] ) && ! empty( $_latlng[1] ) ) {
					$latlng[ $row->name ] = array(
						'latitude' => $_latlng[0],
						'longitude' => $_latlng[1]
					);
				}
			}
		}
	}

	asort( $countries );

	$out_put = '<option ' . selected( '', $selected_option, false ) . ' value="">' . __( 'Select Country', 'geodirlocation' ) . '</option>';

	foreach ( $countries as $country => $name ) {
		$ccode = $ISO2[ $country ];

		$attribs = '';
		if ( ! empty( $latlng[ $country ] ) ) {
			$attribs .= ' data-country_lat="' . esc_attr( $latlng[ $country ]['latitude'] ) . '" data-country_lon="' . esc_attr( $latlng[ $country ]['longitude'] ) . '"';
		}

		$out_put .= '<option ' . selected( $selected_option, $country, false ) . ' value="' . esc_attr( $country ) . '" data-country_code="' . $ccode . '"' . $attribs . '>' . $name . '</option>';
	}

	return $out_put;
}

/**
 * Get location data as an array or object.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param string $which Location type. Possible values are 'country', 'region', 'city'. Default: 'country'.
 * @param string $format Output format. Possible values are 'array', 'object'. Default: 'array'.
 * @return object|string|array Location array or object.
 */
function geodir_get_limited_location_array($which = 'country' , $format = 'array') {
    $location_array = '' ;
    $locations = '' ;
    
    switch($which) {
        case 'country':
            $locations = geodir_get_option( 'lm_selected_countries' );
            break;
        case 'region':
            $locations = geodir_get_option( 'lm_selected_regions' );
            break;
        case 'city':
            $locations = geodir_get_option( 'lm_selected_cities' );
            break;
    }

    if (!empty($locations) && is_array($locations)) {
        foreach($locations as $location)
            $location_array[$location] = $location ;
    }

    if ($format=='object')
        $location_array = (object)$location_array ;

    return $location_array ;
}

/**
 * Get neighbourhoods in dropdown.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $city
 * @param string $selected_id
 * @param bool $echo
 * @return string
 */
function geodir_get_neighbourhoods_dl($city='', $selected_id='', $echo = true) {
	global $wpdb;

	$neighbourhoods = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM ".GEODIR_NEIGHBOURHOODS_TABLE." hood, ".GEODIR_LOCATIONS_TABLE." location WHERE hood.hood_location_id = location.location_id AND location.city=%s ORDER BY hood_name ",
			array(wp_unslash($city))
		)
	);

	$selectoptions = '';
	$found = false;
	if (!empty($neighbourhoods)) {
		foreach($neighbourhoods as $neighbourhood) {
			$selected = '';
			if ($selected_id) {
				if ($neighbourhood->hood_slug == $selected_id) {
					$selected = ' selected="selected" ';
					$found = true;
				} else if (geodir_strtolower(wp_unslash($neighbourhood->hood_name)) == geodir_strtolower($selected_id)) {
					$selected = ' selected="selected" ';
					$found = true;
				}
			}
			
			$selectoptions.= '<option value="' . $neighbourhood->hood_slug . '" ' . $selected . '>' . wp_unslash($neighbourhood->hood_name) . '</option>';
		}
	}
    
	if (!$found && ( !empty( $_REQUEST['neighbourhood_val'] ) || isset( $_REQUEST['backandedit'] ) ) && $selected_id) {
		$selectoptions .= '<option value="' . esc_attr( $selected_id ) . '" selected="selected">' . wp_unslash( $selected_id ) . '</option>';
	}
	
	if ($selectoptions) {
		$selectoptions = '<option value="">' . __( 'Select Neighbourhood','geodirlocation' ) . '</option>' . $selectoptions;
	}
	
	if($echo)
		echo $selectoptions;
	else
		return $selectoptions;
}

/**
 * Set default location.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param int $locationid Location ID.
 */
function geodir_location_set_default( $location_id ) {
	global $wpdb;

	if ( ! absint( $location_id ) > 0 ) {
		return false;
	}

	$wpdb->query( "UPDATE " . GEODIR_LOCATIONS_TABLE . " SET is_default = '0'" );
	$wpdb->query( $wpdb->prepare( "UPDATE " . GEODIR_LOCATIONS_TABLE . " SET is_default = '1' WHERE location_id = %d", array( $location_id ) ) );

	$location = $wpdb->get_row( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE is_default = '1'" );
	if ( empty( $location ) ) {
		$location = $wpdb->get_row( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " ORDER BY location_id ASC" );
	}

	if ( empty( $location ) ) {
		return false;
	}

	geodir_update_option( 'default_location_city', $location->city );
    geodir_update_option( 'default_location_region', $location->region );
    geodir_update_option( 'default_location_country', $location->country );
	geodir_update_option( 'default_location_latitude', $location->latitude );
    geodir_update_option( 'default_location_longitude', $location->longitude );

	return true;
}

/**
 * Get actual location name.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param $type
 * @param $term
 * @param bool $translated
 * @return null|string|void
 */
function get_actual_location_name($type, $term, $translated=false) {
	if ($type=='' || $term=='') {
		return NULL;
	}
	$row = geodir_get_locations($type, $term, true);
	$value = !empty($row) && !empty($row[0]) && isset($row[0]->{$type}) ? $row[0]->{$type} : '';
	if( $translated ) {
		$value = __( $value, 'geodirectory' );
	}
	return stripslashes($value);
}

/**
 * Get locations by keyword.
 *
 * @since 1.0.0
 * @since 1.4.9 Updated to get neighbourhood locations.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $type Search type. Possible values are 'country', 'region', 'city'.
 * @param string $search Keyword.
 * @param bool $single Return only single row? Default: false.
 * @return bool|mixed
 */
function geodir_get_locations($type = '', $search = '', $single = false)
{   global $wpdb,$geodirectory;

	$single_key = $single ? 'single' : '';
	// check cache
	$cache = wp_cache_get("geodir_location_get_locations_".$type.$search.$single_key);
	if($cache !== false){
		return $cache;
	}


	$where = $group_by = '';

	$limit = $single ? " LIMIT 1 " : "";

	$where_array = array();

	switch($type):
		case 'country':
			if($search !='' ){
				$where = $wpdb->prepare(" AND ( country = %s OR country_slug = %s )", array($search,$search));
			}else{ $group_by = " GROUP BY country ";}
		break;
		case 'region':
			if($search !='' ){
				$where = $wpdb->prepare(" AND ( region = %s OR region_slug = %s ) ", array($search,$search));
			}else{ $group_by = " GROUP BY region ";}
		break;
		case 'city':
			if($search !='' ){
				$where = $wpdb->prepare(" AND ( city = %s OR city_slug = %s ) ", array($search,$search));
			}else{ $group_by = " GROUP BY city ";}
		break;
        case 'neighbourhood':
			if ($search != '') {
				$where = $wpdb->prepare(" AND hood_slug = %s ", array($search));
			} else {
                $group_by = " GROUP BY hood_slug ";
            }
            return $wpdb->get_results("SELECT *, hood_name AS neighbourhood, hood_slug AS neighbourhood_slug FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE 1=1 " . $where . $group_by . " ORDER BY hood_name ASC $limit");
		break;
	endswitch;

	$locations = $wpdb->get_results(
			"SELECT * FROM ".GEODIR_LOCATIONS_TABLE." WHERE 1=1 ".$where.$group_by." ORDER BY city $limit"
	);



	$result = (!empty($locations)) ?  $locations : false;

	// set cache
	wp_cache_set("geodir_location_get_locations_".$type.$search.$single_key, $result );

	return $result;

}
/**/

/**
 * Get default location latitude.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param float $latitude Default latitude.
 * @param string $is_default Is default?
 * @return string Default location latitude.
 */
function geodir_location_default_latitude($latitude, $is_default) {
	global $gd_session;
	
//	if ($is_default == '1' && $gd_session->get('gd_multi_location') && !isset($_REQUEST['pid']) && !isset($_REQUEST['backandedit']) && !$gd_session->get('listing')) {
//		if ($gd_ses_city = $gd_session->get('gd_city'))
//			$location = geodir_get_locations('city', $gd_ses_city);
//		else if ($gd_ses_region = $gd_session->get('gd_region'))
//			$location = geodir_get_locations('region', $gd_ses_region);
//		else if ($gd_ses_country = $gd_session->get('gd_country'))
//			$location = geodir_get_locations('country', $gd_ses_country);
//
//		if (isset($location) && $location)
//			$location = end($location);
//
//		$latitude = isset($location->latitude) ? $location->latitude : '';
//	}

	return $latitude;
}

/**
 * Get default location longitude.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param float $lon Default longitude.
 * @param string $is_default Is default?
 * @return string Default location longitude.
 */
function geodir_location_default_longitude($longitude, $is_default) {
	global $gd_session;
	
//	if ($is_default == '1' && $gd_session->get('gd_multi_location') && !isset($_REQUEST['pid']) && !isset($_REQUEST['backandedit']) && !$gd_session->get('listing')) {
//		if ($gd_ses_city = $gd_session->get('gd_city'))
//			$location = geodir_get_locations('city', $gd_ses_city);
//		else if ($gd_ses_region = $gd_session->get('gd_region'))
//			$location = geodir_get_locations('region', $gd_ses_region);
//		else if ($gd_ses_country = $gd_session->get('gd_country'))
//			$location = geodir_get_locations('country', $gd_ses_country);
//
//		if (isset($location) && $location)
//			$location = end($location);
//
//		$longitude = isset($location->longitude) ? $location->longitude : '';
//	}

	return $longitude;
}

//// Location DB requests

/**
 * Parse location list for DB request.
 *
 * @since 1.0.0
 * @since 2.0.0.21 Fix single quote in city name.
 * @package GeoDirectory_Location_Manager
 *
 * @param $list
 * @return string
 */
function geodir_parse_location_list( $list ) {
	$values = '';

	if ( ! empty( $list ) ) {
		$list_arr = explode( ',' , $list );

		if ( ! empty( $list_arr ) ) {
			$values_arr = array();

			foreach ( $list_arr as $value ) {
				$value = trim( wp_unslash( $value ) );

				if ( $value != '' ) {
					$values_arr[] = esc_sql( geodir_strtolower( $value ) );
				}
			}

			if ( ! empty( $values_arr ) ) {
				$values = "'" . implode( "','", $values_arr ) . "'";
			}
		}
	}

	return $values;
}

/**
 * Get current location city or region or country info.
 *
 * @since 1.0.0
 * @since 1.4.4 Updated for the neighbourhood system improvement.
 * @package GeoDirectory_Location_Manager
 *
 * @return string city or region or country info.
 */
function geodir_what_is_current_location($neighbourhood = '') {
	if ($neighbourhood && geodir_get_option( 'lm_enable_neighbourhoods' )) {
		$neighbourhood = geodir_get_current_location(array('what' => 'neighbourhood' , 'echo' => false));
		if(!empty($neighbourhood))
			return 'neighbourhood';
	}
	
	$city = geodir_get_current_location(array('what' => 'city' , 'echo' => false));
	if(!empty($city))
		return 'city';
	
	$region = geodir_get_current_location(array('what' => 'region' , 'echo' => false));
	if(!empty($region))
		return 'region' ;
	
	$country = geodir_get_current_location(array('what' => 'country' , 'echo' => false)) ;
	if(!empty($country))
		return 'country' ;

	return '';

}

/**
 * Get actual location name.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param $type
 * @param $term
 * @param bool $translated
 * @return null|string|void
 */
function geodir_location_get_name($type, $term, $translated=false) {
	if ($type=='' || $term=='') {
		return NULL;
	}
	$row = geodir_get_locations($type, $term, true);
	$value = !empty($row) && !empty($row[0]) && isset($row[0]->{$type}) ? $row[0]->{$type} : '';
	if( $translated ) {
		$value = __( $value, 'geodirectory' );
	}
	return stripslashes($value);
}

/**
 * Returns countries search SQL.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param string $search Search string.
 * @param bool $array Return as array?. Default false.
 * @return array|string Search SQL
 */
function geodir_countries_search_sql( $search = '', $array = false ) {
	$return = $array ? array() : '';
	$search = geodir_strtolower( trim( $search ) );
	if ( $search == '' ) {
		return $return;
	}
	
	$countries = geodir_get_countries_array();
	if ( empty( $countries ) ) {
		return $return;
	}
	
	$return = array();
	foreach( $countries as $row => $value ) {
		$strfind = geodir_strtolower( $value );
		
		if ( $row != $value && geodir_utf8_strpos( $strfind, $search ) === 0 ) {
			$return[] = $row;
		}
	}
	
	if ( $array ) {
		return $return;
	}
	$return = !empty( $return ) ? implode( ",", $return ) : '';
	return $return;
}

/**
 * Clean up location permalink url.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param string $url Permalink url.
 * @return null|string Url.
 */
function geodir_location_permalink_url( $url ) {
	if ( $url == '' ) {
		return NULL;
	}

	if ( get_option( 'permalink_structure' ) != '' ) {
		$url = trim( $url );
		$url = rtrim( $url, '/' ) . '/';
	}

	$url = apply_filters( 'geodir_location_filter_permalink_url', $url );

	return $url;
}

/**
 * Remove location and its data using location ID.
 *
 * @since 1.0.0
 *
 * @param int $id Location ID.
 * @return bool Returns true when successful deletion.
 */
function geodir_location_delete_by_id( $id ) {
	if ( ! current_user_can( 'manage_options' ) || empty( $id ) ) {
		return false;
	}

	return GeoDir_Location_City::delete_location( (int) $id );
}

/**
 * Get location countries.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param bool $list Return as list? Default: false.
 * @return array|mixed
 */
function geodir_post_location_countries( $list = false, $translated = true ) {
	global $wpdb;
	$sql = "SELECT country, country_slug, count(location_id) AS total FROM " . GEODIR_LOCATIONS_TABLE . " WHERE country_slug != '' && country != '' GROUP BY country_slug ORDER BY country ASC";
	$rows = $wpdb->get_results( $sql );
	
	$items = array();
	if ( $list && !empty( $rows ) ) {
		foreach( $rows as $row ) {
			$items[$row->country_slug] = geodir_location_get_name( 'country', $row->country_slug, $translated );
		}
		
		asort( $items );
		
		$rows = $items;
	}
	
	return $rows;	
}

add_filter('geodir_get_full_location','geodir_location_get_full_location',10,1);
function geodir_location_get_full_location($location){


    return $location;
}

/**
 * Set the homepage link if in a location.
 *
 * @param $url
 * @param $path
 *
 * @return string
 */
function geodir_location_geo_home_link($url, $path) {
    if (is_admin()) {
        return $url;
    }

    // If direct home path then we edit it.
    global $post,$geodirectory,$geodir_location_geo_home_link;
    if ((!$path || $path == '/') && geodir_get_option( 'lm_home_go_to' ,'location' ) == 'location' && isset($post->ID)) {

	    $what_is_current_location = $geodirectory->location;
        if ($what_is_current_location) {

	        // if already calculated then don't repeat
            if ($geodir_location_geo_home_link) {
                return $geodir_location_geo_home_link;
            }

	        $current = geodir_get_location_link('current');
	        $base = geodir_get_location_link('base');
            if ( $current != $base) {
                $geodir_location_geo_home_link = trailingslashit($current);
                return $geodir_location_geo_home_link;
            }
        }
    }

    return $url;
}
add_filter( 'home_url', 'geodir_location_geo_home_link',100000,2 );


function gd_seo_remove_image() {
	global $wpdb;

	if (isset($_GET['gd_loc_nonce']) && wp_verify_nonce($_GET['gd_loc_nonce'], 'gd_seo_image_remove')) {

		$seo_id = $_GET['seo_id'];
		$seo_data = array();
		$seo_data['image'] = '';

		$wpdb->update(GEODIR_LOCATION_SEO_TABLE, $seo_data, array('seo_id' => $seo_id));
		$msg = urlencode(__('Location SEO image removed successfully.','geodirlocation'));

		$wp_redirect = wp_get_referer();
		$wp_redirect = remove_query_arg(array('location_success'), $wp_redirect);
		$wp_redirect = add_query_arg(array('location_success' => $msg), $wp_redirect);

		wp_redirect($wp_redirect);
		exit;
	}
}
add_action('init', 'gd_seo_remove_image');

function geodir_handle_attachment($file_handler, $post_id) {
// check to make sure its a successful upload
	if ($_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK) __return_false();

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	$attach_id = media_handle_upload( $file_handler, $post_id );
	if ( is_numeric( $attach_id ) ) {
		return $attach_id;
	}
}

/**
 * Get the neighbour hood location url.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @param int|string $hood Neighbour hood id or slug.
 * @param bool $is_slug Is hood passed is slug? Default false.
 * @return null|string Neighbour hood location url.
 */
function geodir_location_get_neighbourhood_url($hood, $is_slug = false) {
	global $geodirectory;
	if ($hood == '') {
		return NULL;
	}
	
	$hood_slug = $hood;
	if (!$is_slug) {
		$hood_info = GeoDir_Location_Neighbourhood::get_info_by_id($hood);
		
		if (empty($hood_info)) {
			return NULL;
		}
		
		$hood_slug = $hood_info->slug;
	}
	
	$permalink_structure = get_option('permalink_structure');
	
	if (geodir_get_option( 'lm_default_city' ) == 'default' && $default_location = $geodirectory->location->get_default_location()) {
		$location_terms = array('gd_country' => $default_location->country_slug, 'gd_region' => $default_location->region_slug, 'gd_city' => $default_location->city_slug);
		$location_terms = geodir_remove_location_terms($location_terms);
	} else {
		$location_terms = geodir_get_current_location_terms();
	}
	
	if (!empty($location_terms) && isset($location_terms['gd_neighbourhood'])) {
		unset($location_terms['gd_neighbourhood']);
	}
	
	$location_link = geodir_location_get_url($location_terms, $permalink_structure);
	
	if ($permalink_structure != '') {
		$url = trailingslashit($location_link) . $hood_slug . '/';
	} else {
		$url = add_query_arg(array('gd_neighbourhood' => $hood_slug), $location_link);
	}
	
	/**
     * Filter the neighbour hood location url.
     *
     * @since 1.4.4
     * @package GeoDirectory_Location_Manager
     *
     * @param string $url Neighbour hood location url.
	 * @param string $hood int|string $hood Neighbour hood id or slug.
	 * @param bool $is_slug Is hood passed is slug?.
     */
	$url = apply_filters('geodir_location_get_neighbourhood_url', $url, $hood, $is_slug);

	return $url;
}

/**
 * Set the neighbourhood location term.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param array $location_array {
 *    Attributes of the location_array.
 *
 *    @type string $gd_country The country slug.
 *    @type string $gd_region The region slug.
 *    @type string $gd_city The city slug.
 *
 * }
 * @param string $location_array_from Source type of location terms. Default session.
 * @param string $gd_post_type WP post type.
 */
function geodir_location_set_neighbourhood_term($location_array, $location_array_from, $gd_post_type) {
    global $gd_session;
	
	if (!geodir_get_option( 'lm_enable_neighbourhoods' ) || empty($location_array['gd_city']) || (isset($location_array['gd_city']) && $location_array['gd_city'] == 'me')) {
		return $location_array;
	}
	
    if ($location_array_from == 'session') {
        if ($gd_ses_neighbourhood = $gd_session->get('gd_neighbourhood')) {
			$location_array['gd_neighbourhood'] = urldecode($gd_ses_neighbourhood);
		}
    } else {
		global $wp;
		if (isset($wp->query_vars['gd_neighbourhood']) && $wp->query_vars['gd_neighbourhood'] != '') {
			$location_array['gd_neighbourhood'] = urldecode($wp->query_vars['gd_neighbourhood']);
		}
	   			
		// Fix category link in ajax popular category widget on change post type
		if (empty($location_array['gd_neighbourhood']) && defined('DOING_AJAX') && DOING_AJAX && $gd_ses_neighbourhood = $gd_session->get('gd_neighbourhood')) {
			$location_array['gd_neighbourhood'] = urldecode($gd_ses_neighbourhood);
		}
    }

    return $location_array;
}

add_filter('geodir_current_location_terms', 'geodir_location_set_neighbourhood_term', 10, 3);

/**
 * Helper function that determines whether or not this is an admin CPT listings page.
 *
 * @param $check_location_support Bool. Whether or not to check if the CPT supports locations
 * @since 2.0.0.16
 * @package GeoDirectory_Location_Manager
 *
 * @global string $pagenow The current screen.
 */
function geodir_location_is_admin_cpt_listings_page( $check_location_support = true ) {
	global $pagenow;

	//Retrieve the requested post type
	$post_type   = isset($_REQUEST['post_type']) && $_REQUEST['post_type'] ? sanitize_text_field($_REQUEST['post_type']) : '';
	
	//If this is not our CPT, return false
	if ( empty($post_type) || !in_array( $post_type, geodir_get_posttypes() ) ) {
		return false;
	}

	//Great, now make sure this is a listing page
	if ( $pagenow != 'edit.php' ){
		return false;
	}

	//Maybe check if the CPT support locations
	if ( $check_location_support && !GeoDir_Post_types::supports( $post_type, 'location' ) ){
		return false;
	}

	return true;
}

/**
 * Set up the location filter for backend cpt listing pages.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @global string $pagenow The current screen.
 */
function geodir_location_admin_location_filter_init() {
	global $pagenow;

	//Abort early if this is not our admin cpt listing page 
	if (! geodir_location_is_admin_cpt_listings_page() ) {
		return;
	}

	//This hook displays our cpt filters
	add_action('restrict_manage_posts', 'geodir_location_admin_location_filter_box', 10);
			
	//In case the user has selected a filter, let us apply it
	if (!empty($_GET['_gd_country'])) {
		add_filter('posts_join', 'geodir_location_admin_filter_posts_join', 10, 1);
		add_filter('posts_where', 'geodir_location_admin_filter_posts_where', 10, 1);
	}
}
add_action('admin_init', 'geodir_location_admin_location_filter_init', 10);

/**
 * Adds the location filter in backend cpt listing pages.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 */
function geodir_location_admin_location_filter_box() {
	$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : '';
	$gd_country = isset( $_GET['_gd_country'] ) ? sanitize_text_field( wp_unslash( $_GET['_gd_country'] ) ) : '';
	$gd_region = isset( $_GET['_gd_region'] ) ? sanitize_text_field( wp_unslash( $_GET['_gd_region'] ) ) : '';
	$gd_city = isset( $_GET['_gd_city'] ) ? sanitize_text_field( wp_unslash( $_GET['_gd_city'] ) ) : '';

	//Get a list of all countries containing listings...
	$gd_countries = geodir_post_location_countries( true, false );

	// ... Or abort if none exists
	if ( empty( $gd_countries ) ) {
		return;
	}

	$region_disabled = 'disabled="disabled"';
	$city_disabled = 'disabled="disabled"';

	echo '<select name="_gd_country" id="gd-filter-by-country" class="_gd_country">';
	echo '<option value="" style="color:#888888">' . __( 'Country', 'geodirlocation' ) . '</option>';
	foreach ( $gd_countries as $slug => $title ) {
		$title = wp_unslash( $title );
		echo '<option value="' . esc_attr( $title ) . '" ' . selected( $gd_country, $title ) . '>' . esc_html( __( $title, 'geodirectory' ) ) . '</option>';
	}
	echo '</select>';

	$gd_regions = array();
	//In case a country has been selected, get a list of matching regions	
	if ( $gd_country != '' ) {
		$args = array();
		$args['filter_by_non_restricted'] = false;
		$args['location_link_part'] = false;
		$args['compare_operator'] = '=';
		$args['country_column_name'] = 'country';
		$args['region_column_name'] = 'region';
		$args['country_val'] = wp_slash( $gd_country );
		
		$args['fields'] = 'region AS title, region_slug AS slug';
		$args['order'] = 'region';
		$args['group_by'] = 'region_slug';
		$gd_regions = geodir_location_get_locations_array( $args );
		
		if ( ! empty( $gd_regions ) ) {
			$region_disabled = '';
		}
	}

	echo '<select name="_gd_region" class="_gd_region" ' . $region_disabled . ' id="gd-filter-by-region">';
	echo '<option value="" style="color:#888888">' . __( 'Region', 'geodirlocation' ) . '</option>';
	if ( ! empty( $gd_regions ) ) {
		foreach ( $gd_regions as $region ) {
			if ( $region->slug == '' || $region->title == '' ) {
				continue;
			}
			$title = wp_unslash( $region->title );
			echo '<option data-slug="' . esc_attr( $region->slug ) . '" value="' . esc_attr( $title ) . '" ' . selected( $gd_region, $title ) . '>' . __( $title, 'geodirectory' ) . '</option>';
		}
	}
	echo '</select>';

	$gd_cities = array();
	if ( $gd_country != '' && $gd_region != '' ) {
		$args['region_val'] = $gd_region;
		$args['fields'] = 'city AS title, city_slug AS slug';
		$args['order'] = 'city';
		$args['group_by'] = 'city_slug';
		$gd_cities = geodir_location_get_locations_array( $args );

		if ( ! empty( $gd_cities ) ) {
			$city_disabled = '';
		}
	}
	echo '<select name="_gd_city" class="_gd_city" ' . $city_disabled . '>';
	echo '<option value="" style="color:#888888">' . __( 'City', 'geodirlocation' ) . '</option>';
	if ( ! empty( $gd_cities ) ) {
		foreach ( $gd_cities as $city ) {
			if ( $city->slug == '' || $city->title == '' ) {
				continue;
			}
			$title = wp_unslash( $city->title );
			echo'<option data-slug="' . esc_attr( $city->slug ) . '" value="' . esc_attr( $title ) . '" ' . selected( $gd_city, $title ) . '>' . __( $title, 'geodirectory' ) . '</option>';
		}
	}
	echo '</select>';

	if ( ! $post_type ) {
		return;
	}
	// Filter within a chosen location.
	// Category
	$cat_taxonomy = $post_type . 'category';
	if ( ! empty( $_REQUEST[ $cat_taxonomy ] ) ) {
		echo '<input type="hidden" name="' . $cat_taxonomy . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST[ $cat_taxonomy ] ) ) ) . '"/>';
	}
	// Tag
	$tag_taxonomy = $post_type . '_tags';
	if ( ! empty( $_REQUEST[ $tag_taxonomy ] ) ) {
		echo '<input type="hidden" name="' . $tag_taxonomy . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST[ $tag_taxonomy ] ) ) ) . '"/>';
	}
	// Author
	if ( ! empty( $_REQUEST['author'] ) ) {
		echo '<input type="hidden" name="author" value="' . absint( $_REQUEST['author'] ) . '"/>';
	}
}

/**
 * Back end cpt listing location join filter.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @global string $pagenow The current screen.
 *
 * @param string $join The join query clause.
 * @return string Modified join query clause.
 */
function geodir_location_admin_filter_posts_join($join) {
	global $wpdb, $plugin_prefix, $pagenow;

	//Abort early if this is not our admin cpt listing page ...
	if (! geodir_location_is_admin_cpt_listings_page() ) {
		return $join;
	}

	//... or no filters have been applied
	if( empty($_GET['_gd_country']) ){
		return $join;
	}
	
	$post_type   = isset($_REQUEST['post_type']) && $_REQUEST['post_type'] ? sanitize_text_field($_REQUEST['post_type']) : '';
	$table 		 = geodir_db_cpt_table( $post_type );
	return $join . " INNER JOIN " . $table . " ON (" . $table . ".post_id = " . $wpdb->posts . ".ID) ";
}

/**
 * Back end cpt listing location where filter.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @global string $pagenow The current screen.
 *
 * @param string $where The where query clause.
 * @return string Modified where query clause.
 */
function geodir_location_admin_filter_posts_where($where) {
	global $wpdb, $plugin_prefix, $pagenow;

	//Abort early if this is not our admin cpt listing page ...
	if (! geodir_location_is_admin_cpt_listings_page() ) {
		return $where;
	}

	//If we are here, that means one of our CPTs is being requested, so $_REQUEST['post_type'] is not empty
	$table = geodir_db_cpt_table( sanitize_text_field($_REQUEST['post_type']) );

	if ( ! empty( $_GET['_gd_city'] ) ) {
		$city   = sanitize_text_field( $_GET['_gd_city'] );
		$where .= $wpdb->prepare( " AND {$table}.city LIKE %s", $city );
	}

	if ( ! empty( $_GET['_gd_region'] ) ) {
		$region    = sanitize_text_field( $_GET['_gd_region'] );
		$where    .= $wpdb->prepare( " AND {$table}.region LIKE %s", $region );
	}

	if ( ! empty( $_GET['_gd_country'] ) ) {
		$country   = sanitize_text_field( $_GET['_gd_country'] );
		$where    .= $wpdb->prepare( " AND {$table}.country LIKE %s", $country );
	}

	if ( ! empty( $_GET['_gd_neighbourhood'] ) ) {
		$neighbourhood   = sanitize_text_field( $_GET['_gd_neighbourhood'] );
		$where    .= $wpdb->prepare( " AND {$table}.neighbourhood LIKE %s", $neighbourhood );
	}
	
	return $where;
}

/**
 * Retrieve locations data.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @param int $per_page Per page limit. Default 0.
 * @param int $page_no Page number. Default 0.
 * @return array Array of locations data.
 */
function geodir_location_imex_locations_data($per_page = 0, $page_no = 0) {
	$items = geodir_location_imex_get_locations($per_page, $page_no);

	$rows = array();
	//print_r($items);exit;
	if (!empty($items)) {
		$row = array();
		$row[] = 'location_id';
		$row[] = 'latitude';
		$row[] = 'longitude';
		$row[] = 'city';
		$row[] = 'city_slug';
		$row[] = 'region';
		//$row[] = 'region_slug';
		$row[] = 'country';
		//$row[] = 'country_slug';
		$row[] = 'city_meta_title';
		$row[] = 'city_meta_desc';
		$row[] = 'city_desc';
		$row[] = 'region_meta_title';
		$row[] = 'region_meta_desc';
		$row[] = 'region_desc';
		$row[] = 'country_meta_title';
		$row[] = 'country_meta_desc';
		$row[] = 'country_desc';
		
		$rows[] = $row;
		
		$aregion_meta_title = $aregion_meta_desc = $aregion_desc = $acountry_meta_title = $acountry_meta_desc = $acountry_desc = array();
		
		foreach ($items as $item) {			
			$region_meta_title = $region_meta_desc = $region_desc = $country_meta_title = $country_meta_desc = $country_desc = '';
			
			if (($meta_title = trim($item->region_meta_title)) != '' && !isset($aregion_meta_title[$item->country_slug][$item->region_slug])) {
				$region_meta_title = $meta_title;
				$aregion_meta_title[$item->country_slug][$item->region_slug] = true;
			}

			if (($meta_desc = trim($item->region_meta_desc)) != '' && !isset($aregion_meta_desc[$item->country_slug][$item->region_slug])) {
				$region_meta_desc = $meta_desc;
				$aregion_meta_desc[$item->country_slug][$item->region_slug] = true;
			}
			
			if (($desc = trim($item->region_desc)) != '' && !isset($aregion_desc[$item->country_slug][$item->region_slug])) {
				$region_desc = $desc;
				$aregion_desc[$item->country_slug][$item->region_slug] = true;
			}
			
			if (($meta_title = trim($item->country_meta_title)) != '' && !isset($acountry_meta_title[$item->country_slug])) {
				$country_meta_title = $meta_title;
				$acountry_meta_title[$item->country_slug] = true;
			}

			if (($meta_desc = trim($item->country_meta_desc)) != '' && !isset($acountry_meta_desc[$item->country_slug])) {
				$country_meta_desc = $meta_desc;
				$acountry_meta_desc[$item->country_slug] = true;
			}

			if (($desc = trim($item->country_desc)) != '' && !isset($acountry_desc[$item->country_slug])) {
				$country_desc = $desc;
				$acountry_desc[$item->country_slug] = true;
			}
			
			$row = array();
			$row[] = $item->location_id;
			$row[] = $item->latitude;
			$row[] = $item->longitude;
			$row[] = stripslashes($item->city);
			$row[] = $item->city_slug;
			$row[] = stripslashes($item->region);
			//$row[] = $item->region_slug;
			$row[] = stripslashes($item->country);
			//$row[] = $item->country_slug;
			$row[] = stripslashes($item->city_meta_title);
			$row[] = stripslashes($item->city_meta_desc);
			$row[] = stripslashes($item->city_desc);
			$row[] = stripslashes($region_meta_title);
			$row[] = stripslashes($region_meta_desc);
			$row[] = stripslashes($region_desc);
			$row[] = stripslashes($country_meta_title);
			$row[] = stripslashes($country_meta_desc);
			$row[] = stripslashes($country_desc);
			
			$rows[] = $row;
		}
	}
	return $rows;
}

/**
 * Retrieve neighbourhoods data.
 *
 * @since 1.4.5
 * @package GeoDirectory_Location_Manager
 *
 * @param int $per_page Per page limit. Default 0.
 * @param int $page_no Page number. Default 0.
 * @return array Array of neighbourhoods data.
 */
function geodir_location_imex_neighbourhoods_data($per_page = 0, $page_no = 0) {
    $items = geodir_location_imex_get_neighbourhoods($per_page, $page_no);

    $rows = array();

    if (!empty($items)) {
        $row = array();
        $row[] = 'neighbourhood_id';
        $row[] = 'neighbourhood_name';
        $row[] = 'neighbourhood_slug';
        $row[] = 'latitude';
        $row[] = 'longitude';
        $row[] = 'location_id';
        $row[] = 'city';
        $row[] = 'region';
        $row[] = 'country';
        $row[] = 'meta_title';
        $row[] = 'meta_description';
        $row[] = 'description';

        $post_types = geodir_get_posttypes();

        foreach ( $post_types as $post_type ) {
            if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
                $row[] = 'cpt_desc_' . $post_type;
            }
        }

        $rows[] = $row;

        foreach ( $items as $item ) {
            $row = array();
            $row[] = $item->hood_id;
            $row[] = stripslashes($item->hood_name);
            $row[] = $item->hood_slug;
            $row[] = $item->hood_latitude;
            $row[] = $item->hood_longitude;
            $row[] = (int) $item->hood_location_id > 0 ? $item->hood_location_id : '';
            $row[] = stripslashes( $item->city );
            $row[] = stripslashes( $item->region );
            $row[] = stripslashes( $item->country );
            $row[] = stripslashes( $item->hood_meta_title );
            $row[] = stripslashes( $item->hood_meta );
            $row[] = stripslashes( $item->hood_description );

            $cpt_desc = ! empty( $item->cpt_desc ) ? json_decode( $item->cpt_desc, true ) : array();

            foreach ( $post_types as $post_type ) {
                if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
                    $row[] = is_array( $cpt_desc ) && isset( $cpt_desc[ $post_type ] ) ? stripslashes( $cpt_desc[ $post_type ] ) : '';
                }
            }

            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Counts the total locations.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @return int Total number of locations.
 */
function geodir_location_imex_count_locations( $type = 'city' ) {
	global $wpdb;
	
	if ( $type == 'country' ) {
		$field = "country_slug";
		$where = "country_slug != ''";
	} else if ( $type == 'region' ) {
		$field = "CONCAT(country_slug, '|', region_slug)";
		$where = "country_slug != '' AND region_slug != ''";
	} else {
		$field = "CONCAT(country_slug, '|', region_slug, '|', city_slug)";
		$where = "country_slug != '' AND region_slug != '' AND city_slug != ''";
	}
	
	$query = "SELECT COUNT( DISTINCT " . $field . " ) FROM `" . GEODIR_LOCATIONS_TABLE . "` WHERE " . $where;
	$value = (int)$wpdb->get_var($query);
	
	return $value;
}

/**
 * Counts the total neighbourhoods.
 *
 * @since 1.4.5
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @return int Total number of neighbourhoods.
 */
function geodir_location_imex_count_neighbourhoods() {
    global $wpdb;
    
    $query = "SELECT COUNT(h.hood_id) FROM `" . GEODIR_NEIGHBOURHOODS_TABLE . "` AS h INNER JOIN `" . GEODIR_LOCATIONS_TABLE . "` AS l ON l.location_id = h.hood_location_id";
    $value = (int)$wpdb->get_var($query);

    return $value;
}

/**
 * Get the locations data to export as csv file.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param int $per_page Number of records per page.
 * @param int $page_no Current page number. Default 0.
 * @return array Location data.
 */
function geodir_location_imex_get_locations($per_page, $page_no = 0) {
	global $wpdb;
	
	$page_no = max($page_no, 1);
	
	$fields = "l.location_id, l.country, l.region, l.city,
	 l.country_slug, l.region_slug, l.city_slug, 
	 l.latitude, l.longitude, 
	 lscn.meta_desc AS country_meta_desc, 
	 lscn.meta_title AS country_meta_title, 
	 lscn.location_desc AS country_desc, 
	 lsre.meta_title AS region_meta_title, 
	 lsre.meta_desc AS region_meta_desc, 
	 lsre.location_desc AS region_desc, 
	 lsct.meta_title AS city_meta_title, 
	 lsct.meta_desc AS city_meta_desc, 
	 lsct.location_desc AS city_desc";
	
	$join = " LEFT JOIN `" . GEODIR_LOCATION_SEO_TABLE . "` AS lscn ON ( lscn.location_type = 'country' AND lscn.country_slug = l.country_slug )";
	$join .= " LEFT JOIN `" . GEODIR_LOCATION_SEO_TABLE . "` AS lsre ON ( lsre.location_type = 'region' AND lsre.country_slug = l.country_slug AND lsre.region_slug = l.region_slug )";
	$join .= " LEFT JOIN `" . GEODIR_LOCATION_SEO_TABLE . "` AS lsct ON ( lsct.location_type = 'city' AND lsct.country_slug = l.country_slug AND lsct.region_slug = l.region_slug AND lsct.city_slug = l.city_slug )";
	
	$where = "l.country_slug != '' AND l.region_slug != '' AND l.city_slug != ''";
	$groupby = "CONCAT(l.country_slug, '|', l.region_slug, '|', l.city_slug)";
	$orderby = "l.country ASC, l.region ASC, l.city ASC";
	
	$where = apply_filters('geodir_location_imex_get_locations_where', $where, $per_page, $page_no);
	if ($where != '') {
		$where = " WHERE " . $where;
	}
	if ($groupby != '') {
		$groupby = " GROUP BY " . $groupby;
	}
	if ($orderby != '') {
		$orderby = " ORDER BY " . $orderby;
	}
	
	$limit = (int)$per_page > 0 ? " LIMIT " . (($page_no - 1) * $per_page) . ", " . $per_page : '';
	
	$query = "SELECT " . $fields . " FROM `" . GEODIR_LOCATIONS_TABLE . "` AS l " . $join . $where . $groupby . $orderby . $limit;
	$results = $wpdb->get_results($query);
	
	return $results;
}

/**
 * Get the neighbourhoods data to export as csv file.
 *
 * @since 1.4.5
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param int $per_page Number of records per page.
 * @param int $page_no Current page number. Default 0.
 * @return array Neighbourhoods data.
 */
function geodir_location_imex_get_neighbourhoods($per_page, $page_no = 0) {
    global $wpdb;

    $page_no = max($page_no, 1);

    $fields = "h.hood_id, h.hood_name, h.hood_slug, h.hood_latitude, h.hood_longitude, h.hood_meta_title, h.hood_meta, h.hood_description, h.hood_location_id, h.cpt_desc, l.location_id, l.location_id, l.country, l.region, l.city, l.country_slug, l.region_slug, l.city_slug, l.latitude, l.longitude";

    $join = " INNER JOIN `" . GEODIR_LOCATIONS_TABLE . "` AS l ON l.location_id = h.hood_location_id";

    $where = "";
    $groupby = "";
    $orderby = "h.hood_id ASC";

    if ($where != '') {
        $where = " WHERE " . $where;
    }
    if ($groupby != '') {
        $groupby = " GROUP BY " . $groupby;
    }
    if ($orderby != '') {
        $orderby = " ORDER BY " . $orderby;
    }

    $limit = (int)$per_page > 0 ? " LIMIT " . (($page_no - 1) * $per_page) . ", " . $per_page : '';

    $query = "SELECT " . $fields . " FROM `" . GEODIR_NEIGHBOURHOODS_TABLE . "` AS h " . $join . $where . $groupby . $orderby . $limit;
    $results = $wpdb->get_results($query);

    return $results;
}

/**
 * Get the location slug for location type and name.
 *
 * @since 1.4.4
 * @since 1.5.4 Fix country translation.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param string $type Location type(city or region or country).
 * @param array $args Location input data. Default empty.
 * @return string Location slug.
 */
function geodir_get_location_by_slug($type, $args = array()) {
	global $wpdb,$geodirectory;

	if (!in_array($type, array('city', 'region', 'country'))) {
		return NULL;
	}
	
	if ($type == 'city' && empty($args['city_slug']) && empty($args['city'])) {
		return NULL;
	}
	if ($type == 'region' && empty($args['region_slug']) && empty($args['region'])) {
		return NULL;
	}
	if ($type == 'country' && empty($args['country_slug']) && empty($args['country'])) {
		return NULL;
	}

	// check if its the current location
	global $geodirectory;
	$location = $geodirectory->location;
	//echo '###';print_r($args);
	if(!empty($args['city_slug']) && $location->city_slug==$args['city_slug']){ return $location;  }
	if(!empty($args['region_slug']) && $location->region_slug==$args['region_slug']){ return $location;  }
	if(!empty($args['country_slug']) && $location->country_slug==$args['country_slug']){ return $location;  }

	$params = array();
	$where = '';
	
	$fields = !empty($args['fields']) ? $args['fields'] : '*';
	$operator = !empty($args['sensitive']) ? '=' : 'LIKE';
	
	if (!empty($args['country_slug'])) {
		$params[] = $args['country_slug'];
		$where .= ' AND country_slug = %s';
	}
	
	if (!empty($args['region_slug']) && $type != 'country') {
		$params[] = $args['region_slug'];
		$where .= ' AND region_slug = %s';
	}
	
	if (!empty($args['city_slug']) && $type != 'country' && $type != 'region') {
		$params[] = $args['city_slug'];
		$where .= ' AND city_slug = %s';
	}
	
	if (!empty($args['country'])) {
		$params[] = $geodirectory->location->validate_country_name($args['country']);
		$where .= ' AND country ' . $operator . ' %s';
	}
	
	if (!empty($args['region']) && $type != 'country') {
		$params[] = $args['region'];
		$where .= ' AND region ' . $operator . ' %s';
	}
	
	if (!empty($args['city']) && $type != 'country' && $type != 'region') {
		$params[] = $args['city'];
		$where .= ' AND city ' . $operator . ' %s';
	}
	
	$query = $wpdb->prepare("SELECT " . $fields . " FROM `" . GEODIR_LOCATIONS_TABLE . "` WHERE 1 " . $where . " ORDER BY is_default DESC, location_id ASC", $params);
	$row = $wpdb->get_row($query);

	return $row;
}

/**
 * Save location data during location import.
 *
 * @since 1.4.4
 * @since 1.5.4 Fix country translation.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param array $args Location data.
 * @param bool $has_seo True if location data contains seo data, otherwise false. Default false.
 * @return bool|int Location if record saved, otherwise false.
 */
function geodir_location_insert_city( $args, $has_seo = false ) {
	global $wpdb,$geodirectory;
	
	if ( empty( $args ) )
		return false;
	
	if ( empty( $args['city'] ) || empty( $args['region'] ) || empty( $args['country'] ) || empty( $args['latitude'] ) || empty( $args['longitude'] ) ) {
		return NULL;
	}
	
	$data = array();
	$data['city'] = $args['city'];
	$data['region'] = $args['region'];
	$data['country'] = $geodirectory->location->validate_country_name($args['country']);
	$data['latitude'] = $args['latitude'];
	$data['longitude'] = $args['longitude'];
	
	$city_slug = !empty($args['city_slug']) ? $args['city_slug'] : $args['city'];
	
	$data['country_slug'] = empty($args['country_slug']) ? geodir_location_country_slug($args['country']) : $args['country_slug'];
	$data['region_slug'] = empty($args['region_slug']) ? geodir_location_region_slug($args['region'], $data['country_slug'], $data['country']) : $args['region_slug'];
	$data['city_slug'] = geodir_location_city_slug( $city_slug, 0, $data['region_slug'] );

	if ( !empty( $args['is_default'] ) )
		$data['is_default'] = $args['is_default'];

	if ( $wpdb->insert( GEODIR_LOCATIONS_TABLE, $data ) ) {
		if ( $has_seo ) {
			$seo_data = array();
			$seo_data['country_slug'] = $data['country_slug'];
			
			if ( !empty( $args['country_meta_title'] ) || !empty( $args['country_meta_desc'] ) || !empty( $args['country_desc'] ) ) {
				$seo_data['location_type'] = 'country';
				
				if ( !empty( $args['country_meta_title'] ) )
					$seo_data['meta_title'] = $args['country_meta_title'];

				if ( !empty( $args['country_meta_desc'] ) )
					$seo_data['meta_desc'] = $args['country_meta_desc'];

				if ( !empty( $args['country_desc'] ) )
					$seo_data['location_desc'] = $args['country_desc'];
				
				GeoDir_Location_SEO::save_seo_data( $seo_data['location_type'], $seo_data );
			}
			
			$seo_data['region_slug'] = $data['region_slug'];
			
			if ( !empty( $args['region_meta_title'] ) || !empty( $args['region_meta_desc'] ) || !empty( $args['region_desc'] ) ) {
				$seo_data['location_type'] = 'region';
				
				if ( !empty( $args['region_meta_title'] ) )
					$seo_data['meta_title'] = $args['region_meta_title'];

				if ( !empty( $args['region_meta_desc'] ) )
					$seo_data['meta_desc'] = $args['region_meta_desc'];

				if ( !empty( $args['region_desc'] ) )
					$seo_data['location_desc'] = $args['region_desc'];
				
				GeoDir_Location_SEO::save_seo_data( $seo_data['location_type'], $seo_data );
			}
			
			$seo_data['city_slug'] = $data['city_slug'];
			
			if ( !empty( $args['city_meta_title'] ) || !empty( $args['city_meta_desc'] ) || !empty( $args['city_desc'] ) ) {
				$seo_data['location_type'] = 'city';

				if ( !empty( $args['city_meta_title'] ) )
					$seo_data['meta_title'] = $args['city_meta_title'];

				if ( !empty( $args['city_meta_desc'] ) )
					$seo_data['meta_desc'] = $args['city_meta_desc'];

				if ( !empty( $args['city_desc'] ) )
					$seo_data['location_desc'] = $args['city_desc'];
				
				GeoDir_Location_SEO::save_seo_data( $seo_data['location_type'], $seo_data );
			}
		}
		
		return (int)$wpdb->insert_id;
	}
	
	return false;
}

/**
 * Update location data during location import.
 *
 * @since 1.4.4
 * @since 1.5.4 Fix country translation.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param array $args Location data.
 * @param bool $has_seo True if location data contains seo data, otherwise false. Default false.
 * @param object $old_location Old location data before updated. Default empty.
 * @return bool True if record update, otherwise false.
 */
function geodir_location_update_city( $args, $has_seo = false, $old_location = array() ) {
	global $wpdb,$geodirectory;

	if ( empty( $args ) )
		return false;
	
	if ( empty( $args['location_id'] ) ) {
		return false;
	}
	
	$location_id = (int)$args['location_id'];
	
	if ( empty( $old_location ) ) {
		$old_location = geodir_get_location_by_id( '', $location_id );
	}
	
	if ( empty( $old_location ) ) {
		return false;
	}
	
	$data = array();
	if ( !empty( $args['city'] ) && $args['city'] != $old_location->city )
		$data['city'] = $args['city'];
	
	if ( !empty( $args['region'] ) && $args['region'] != $old_location->region )
		$data['region'] = $args['region'];
	
	if ( !empty( $args['country'] ) && $args['country'] != $old_location->country )
		$data['country'] = $geodirectory->location->validate_country_name($args['country']);
	
	if ( !empty( $args['latitude'] ) && $args['latitude'] != $old_location->latitude )
		$data['latitude'] = $args['latitude'];
	
	if ( !empty( $args['longitude'] ) && $args['longitude'] != $old_location->longitude )
		$data['longitude'] = $args['longitude'];
		
	if (!empty($data['country']) && empty($args['country_slug'])) {
		$args['country_slug'] = geodir_location_country_slug($data['country']);
	}
	
	if ( !empty($args['country_slug']) && $args['country_slug'] != $old_location->country_slug )
		$data['country_slug'] = $args['country_slug'];
	
	$country = !empty($data['country']) ? $data['country'] : $old_location->country;
	$country_slug = !empty($data['country_slug']) ? $data['country_slug'] : $old_location->country_slug;
	
	if (!empty($data['region']) && empty($args['region_slug'])) {
		$args['region_slug'] = geodir_location_region_slug($data['region'], $country_slug, $country);
	}
	
	if ( !empty($args['region_slug']) && $args['region_slug'] != $old_location->region_slug )
		$data['region_slug'] = $args['region_slug'];
	
	$region_slug = !empty($data['region_slug']) ? $data['region_slug'] : $old_location->region_slug;
	
	if ( !empty($args['city_slug']) && $args['city_slug'] != $old_location->city_slug )
		$data['city_slug'] = geodir_location_city_slug( $args['city_slug'], $location_id, $region_slug );
	
	$city_slug = !empty($data['city_slug']) ? $data['city_slug'] : $old_location->city_slug;

	if ( !empty( $args['is_default'] ) && $args['is_default'] != $old_location->is_default )
		$data['is_default'] = $args['is_default'];

	if ( ! empty( $data ) || ( ! empty( $args['city_meta_title'] ) || ! empty( $args['city_meta_desc'] ) || ! empty( $args['city_desc'] ) || ! empty( $args['region_meta_title'] ) || ! empty( $args['region_meta_desc'] ) || ! empty( $args['region_desc'] ) || ! empty( $args['country_meta_title'] ) || ! empty( $args['country_meta_desc'] ) || ! empty( $args['country_desc'] ) ) ) {

		$updated = !empty( $data ) ? (int)$wpdb->update( GEODIR_LOCATIONS_TABLE, $data, array( 'location_id' => $location_id ) ) : false;
		if ($updated) {
			$new_location = geodir_get_location_by_id( '', $location_id );
			geodir_location_on_update_location($new_location, $old_location);
		}
		
		if ( $has_seo ) {
			$seo_data = array();
			$seo_data['country_slug'] = $country_slug;

			if ( !empty( $args['country_meta_title'] ) || !empty( $args['country_meta_desc'] ) || !empty( $args['country_desc'] ) ) {
				$seo_data['location_type'] = 'country';
				
				if ( !empty( $args['country_meta_title'] ) )
					$seo_data['meta_title'] = $args['country_meta_title'];

				if ( !empty( $args['country_meta_desc'] ) )
					$seo_data['meta_desc'] = $args['country_meta_desc'];

				if ( !empty( $args['country_desc'] ) )
					$seo_data['location_desc'] = $args['country_desc'];

				GeoDir_Location_SEO::save_seo_data( $seo_data['location_type'], $seo_data );
			}
			
			$seo_data['region_slug'] = $region_slug;
			
			if ( !empty( $args['region_meta_title'] ) || !empty( $args['region_meta_desc'] )  || !empty( $args['region_desc'] ) ) {
				$seo_data['location_type'] = 'region';
				
				if ( !empty( $args['region_meta_title'] ) )
					$seo_data['meta_title'] = $args['region_meta_title'];

				if ( !empty( $args['region_meta_desc'] ) )
					$seo_data['meta_desc'] = $args['region_meta_desc'];

				if ( !empty( $args['region_desc'] ) )
					$seo_data['location_desc'] = $args['region_desc'];

				GeoDir_Location_SEO::save_seo_data( $seo_data['location_type'], $seo_data );
			}
			
			$seo_data['city_slug'] = $city_slug;

			if ( !empty( $args['city_meta_title'] ) || !empty( $args['city_meta_desc'] )  || !empty( $args['city_desc'] ) ) {
				$seo_data['location_type'] = 'city';
				
				if ( !empty( $args['city_meta_title'] ) )
					$seo_data['meta_title'] = $args['city_meta_title'];

				if ( !empty( $args['city_meta_desc'] ) )
					$seo_data['meta_desc'] = $args['city_meta_desc'];

				if ( !empty( $args['city_desc'] ) )
					$seo_data['location_desc'] = $args['city_desc'];

				GeoDir_Location_SEO::save_seo_data( $seo_data['location_type'], $seo_data );
			}
		}
	}
	
	return true;
}

/**
 * Get the city slug for city name.
 *
 * @since 1.4.4
 * @since 1.5.0 Fix looping when importing duplicate city names.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $geodirectory GeoDirectory object.
 *
 * @param string $name City name.
 * @param int $location_id Location id. Default 0.
 * @param string $region_slug Region slug to check.
 * @return string City slug.
 * @todo moved to class
 */
function geodir_location_city_slug( $name, $location_id = 0, $region_slug = '' ) {
	global $geodirectory;

	return $geodirectory->location->create_city_slug( $name, $location_id, $region_slug );
}

/**
 * Get the country slug for country name.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @global object $geodirectory GeoDirectory object.
 *
 * @param string $name Country name.
 * @return string Country slug.
 * @todo moved to class
 */
function geodir_location_country_slug( $name ) {
	global $geodirectory;

	return $geodirectory->location->get_country_slug( $name );
}

/**
 * Get the region slug for region name.
 *
 * @since 1.4.4
 * @package GeoDirectory_Location_Manager
 *
 * @global object $geodirectory GeoDirectory object.
 *
 * @param string $name Region name.
 * @param string $country_slug Country slug. Default empty.
 * @param string $country Country name. Default empty.
 * @return string Region slug.
 * @todo moved to class
 */
function geodir_location_region_slug( $name, $country_slug = '', $country = '' ) {
	global $geodirectory;

	return $geodirectory->location->create_region_slug( $name, $country_slug, $country );
}

/**
 * Updates listings location data on location update.
 *
 * @since 1.4.4
 * @since 1.5.4 Fix country translation.
 * @package GeoDirectory_Location_Manager
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix GeoDirectory plugin table prefix.
 * @global array $gd_post_types GeoDirectory custom post types.
 *
 * @param object $new_location New location info after updated.
 * @param object $old_location Old location info before updated.
 * @return bool True if any record updated, otherwise false.
 */
function geodir_location_on_update_location($new_location, $old_location) {
	global $wpdb, $plugin_prefix, $gd_post_types;
	if (empty($new_location) || empty($old_location)) {
		return false;
	}

	if (empty($gd_post_types)) {
		$gd_post_types = geodir_get_posttypes();
	}
	
	foreach ($gd_post_types as $i => $post_type) {
		$table = geodir_db_cpt_table( $post_type );

		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET city = %s, region = %s, country = %s WHERE city LIKE %s AND region LIKE %s AND country LIKE %s", array( $new_location->city, $new_location->region, $new_location->country, $old_location->city, $old_location->region, $old_location->country ) ) );
	}
	
	return true;
}

/**
 * Returns current location terms.
 *
 * @since 1.4.9
 * @package GeoDirectory_Location_Manager
 * @global object $wp WordPress object.
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param string $location_array_from Place to look for location array. Default: 'session'.
 * @return array The location term array.
 */
function geodir_location_get_full_current_location_terms($location_array_from = 'session')
{
	global $wp, $gd_session,$geodirectory;

	$loc = geodir_get_current_location_terms($location_array_from);
	
	// if it's a city
	if(isset($loc['gd_city']) && $loc['gd_city']){

		// check for proper region value
		if(!isset($loc['gd_region']) || $loc['gd_region']==''){

			// if its set to show the default region then grab that
			if(geodir_get_option( 'lm_default_region' )=='default'){
				$default_location = $geodirectory->location->get_default_location();
				$loc['gd_region'] = urldecode($default_location->region_slug);
			}
		}
		
	}

	// if it's a region
	if(isset($loc['gd_region']) && $loc['gd_region']){

		// check for proper country value
		if(!isset($loc['gd_country']) || $loc['gd_country']==''){

			// if its set to show the default region then grab that
			if(geodir_get_option( 'lm_default_country' )=='default'){
				$default_location = $geodirectory->location->get_default_location();
				$loc['gd_country'] = urldecode($default_location->country_slug);
			}
		}

	}
	
	

	/**
	 * Filter the location terms.
	 *
	 * @since 1.4.9
	 * @package GeoDirectory
	 *
	 * @param array $location_array {
	 *    Attributes of the location_array.
	 *
	 *    @type string $gd_country The country slug.
	 *    @type string $gd_region The region slug.
	 *    @type string $gd_city The city slug.
	 *
	 * }
	 * @param string $location_array_from Source type of location terms. Default session.
	 */
	$location_array = apply_filters( 'geodir_full_current_location_terms', $loc, $location_array_from );

	return $location_array;

}

function geodir_location_get_term_top_desc($term_id, $location, $location_type = 'city', $country = '') {
    $description = '';
    
    if (empty($term_id) || empty($location) || empty($location_type)) {
        return $description;
    }

    switch ($location_type) {
        case 'country':
            if (!empty($location)) {
				$description = get_term_meta( $term_id, 'gd_desc_co_' . $location, true );
            }
            break;
        case 'region':
            if (!empty($country) && !empty($location)) {
                $description = get_term_meta( $term_id, 'gd_desc_re_' . $country . '_' . $location, true );
            }
            break;
        case 'city':
            if (!empty($location)) {
                $description = get_term_meta( $term_id, 'gd_desc_id_' . $location, true );
            }
            break;
    }
	$description = ! empty( $description ) ? stripslashes( $description ) : '';
    
    return apply_filters('geodir_location_category_top_description', $description, $term_id, $location, $location_type);
}

function geodir_location_save_term_top_desc($term_id, $content, $location, $location_type = 'city', $country = '') {
    if (empty($term_id) || empty($location) || empty($location_type)) {
        return false;
    }
	$meta_value = ! empty( $content ) ? stripslashes( $content ) : '';

    switch ($location_type) {
        case 'country':
            if (!empty($location)) {
                return update_term_meta( $term_id, 'gd_desc_co_' . $location, $meta_value );
            }
            break;
        case 'region':
            if (!empty($country) && !empty($location)) {
                return update_term_meta( $term_id, 'gd_desc_re_' . $country . '_' . $location, $meta_value );
            }
            break;
        case 'city':
            if (!empty($location)) {
                return update_term_meta( $term_id, 'gd_desc_id_' . $location, $meta_value );
            }
            break;
    }
    
    return false;
}

/**
 * Retrieve locations + category top descriptions data.
 *
 * @since 1.5.4
 * @package GeoDirectory_Location_Manager
 *
 * @param int $per_page Per page limit. Default 0.
 * @param int $page_no Page number. Default 0.
 * @param string $post_type Post type. Default Empty.
 * @param string $location_type Location type. Default Empty.
 * @return array Array of locations data.
 */
function geodir_location_imex_cat_locations_data($per_page = 0, $page_no = 0, $post_type = '', $location_type = '') {
    $items = geodir_location_imex_get_cat_locations($per_page, $page_no, $post_type, $location_type);
    
    $rows = array();
    
    if (!empty($items)) {
        if (empty($location_type)) {
            $row = array();
            $row[] = 'term_id';
            $row[] = 'term_name';
            $row[] = 'enable_default_for_all_locations';
            $row[] = 'top_description';
            
            $rows[] = $row;
            
            foreach ($items as $item) {
                $default = get_term_meta( $item->term_id, 'gd_desc_custom', true ) ? 0 : 1;
                $top_description = get_term_meta( $item->term_id, 'ct_cat_top_desc', true );
                if (!empty($top_description)) {
                    $top_description = stripslashes($top_description);
                }
                
                $row = array();
                $row[] = $item->term_id;
                $row[] = $item->name;
                $row[] = $default;
                $row[] = $top_description;
                
                $rows[] = $row;
            }
        } else {
            $row = array();
            $row[] = 'term_id';
            $row[] = 'term_name';
            $row[] = 'country';
            $row[] = 'country_slug';
            
            if ( $location_type == 'region' || $location_type == 'city' ) {
                $row[] = 'region';
                $row[] = 'region_slug';
                
                if ( $location_type == 'city' ) {
                    $row[] = 'city';
                    $row[] = 'city_slug';
                }
            }
            
            $row[] = 'top_description';
            
            $rows[] = $row;
            
            foreach ( $items as $item ) {
                if ( empty( $item->country_slug ) ) {
                    continue;
                }
                
                if ( ( $location_type == 'region' || $location_type == 'city' ) && empty( $item->region_slug ) ) {
                    continue;
                    
                    if ( $location_type == 'city' && empty( $item->city_slug ) ) {
                        continue;
                    }
                }
                
                $row = array();
                $row[] = $item->term_id;
                $row[] = $item->name;
                $row[] = $item->country;
                $row[] = $item->country_slug;
                
                if ( ( $location_type == 'region' || $location_type == 'city' ) ) {
                    $row[] = $item->region;
                    $row[] = $item->region_slug;
                    
                    if ( $location_type == 'city' ) {
                        $row[] = $item->city;
                        $row[] = $item->city_slug;
                    }
                }
                
                $top_description = '';
                if ( $location_type == 'country'  ) {
                    $top_description = geodir_location_get_term_top_desc( $item->term_id, $item->country_slug, 'country');
                } else if ( $location_type == 'region' ) {
                    $top_description = geodir_location_get_term_top_desc( $item->term_id, $item->region_slug, 'region', $item->country_slug );
                } else if ( $location_type == 'city' ) {
                    $top_description = geodir_location_get_term_top_desc( $item->term_id, $item->location_id, 'city' );
                }
				if ( !empty( $top_description ) ) {
					$top_description = stripslashes( $top_description );
				}
                
                $row[] = $top_description;
                
                $rows[] = $row;
            }
        }
    }

    return $rows;
}

/**
 * Get the locations + category descriptions data to export as csv file.
 *
 * @since 1.5.4
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param int $per_page Number of records per page.
 * @param int $page_no Current page number. Default 0.
 * @return array Location data.
 */
function geodir_location_imex_get_cat_locations( $per_page, $page_no = 0, $post_type = '', $location_type = '' ) {
    global $wpdb;

    $page_no = max( $page_no, 1 );
    
    $taxonomies = array();
    if ( !empty( $post_type ) ) {
        $taxonomies[] = $post_type . 'category';
    } else {
        $post_types = geodir_get_posttypes();
        
        foreach ( $post_types as $cpt ) {
            if ( GeoDir_Post_types::supports( $cpt, 'location' ) ) {
                $taxonomies[] = $cpt . 'category';
            }
        }
    }
    
    if ( empty( $taxonomies ) ) {
        return NULL;
    }
    
    $fields = "t.term_id, t.name, tt.taxonomy";
    $join = " INNER JOIN `" . $wpdb->term_taxonomy . "` AS tt ON t.term_id = tt.term_id";
    $where = "tt.taxonomy IN ('" . implode( "','", $taxonomies ) . "')";
    $orderby = "tt.taxonomy ASC, t.name ASC";
    $groupby = '';
    
    if ( !empty( $location_type ) ) {
        $join .= " JOIN `" . GEODIR_LOCATIONS_TABLE . "` AS l";
        
        $fields .= ", l.country, l.country_slug";
        $where .= " AND l.country_slug != ''";
        $groupby .= "CONCAT(t.term_id, '|', l.country_slug";
        $orderby .= ", l.country ASC";
    
        if ( $location_type == 'region' || $location_type == 'city' ) {
            $fields .= ", l.region, l.region_slug";
            $where .= " AND l.region_slug != ''";
            $groupby .= ", '|', l.region_slug";
            $orderby .= ", l.region ASC";
            
            if ( $location_type == 'city' ) {
                $fields .= ", l.city, l.city_slug, l.location_id";
                $where .= " AND l.city_slug != ''";
                $groupby .= ", '|', l.city_slug";
                $orderby .= ", l.city ASC";
            }
        }
        
        $groupby .= ")";
    }

    if ($where != '') {
        $where = " WHERE " . $where;
    }
    if ($groupby != '') {
        $groupby = " GROUP BY " . $groupby;
    }
    if ($orderby != '') {
        $orderby = " ORDER BY " . $orderby;
    }
    
    $limit = (int)$per_page > 0 ? " LIMIT " . (($page_no - 1) * $per_page) . ", " . $per_page : '';
    
    $query = "SELECT " . $fields . " FROM `" . $wpdb->terms . "` AS t " . $join . $where . $groupby . $orderby . $limit;
    $results = $wpdb->get_results($query);

    return $results;
}

function geodir_location_restricted_countries() {
	$restricted = geodir_get_option( 'lm_selected_countries' );

	if ( empty( $restricted ) || ! is_array( $restricted ) ) {
		$restricted = array();
	}

	return apply_filters( 'geodir_location_get_restricted_countries', $restricted );
}

function geodir_location_restricted_regions() {
	$restricted = geodir_get_option( 'lm_selected_regions' );

	if ( empty( $restricted ) || ! is_array( $restricted ) ) {
		$restricted = array();
	}

	return apply_filters( 'geodir_location_get_restricted_regions', $restricted );
}

function geodir_location_restricted_cities() {
	$restricted = geodir_get_option( 'lm_selected_cities' );

	if ( empty( $restricted ) || ! is_array( $restricted ) ) {
		$restricted = array();
	}

	return apply_filters( 'geodir_location_get_restricted_cities', $restricted );
}

// @todo moved to class
function geodir_get_location_by_names( $city = '', $region = '', $country = '' ) {
	global $wpdb;

	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE city LIKE %s AND region LIKE %s AND country LIKE %s", array( $city, $region, $country ) ) );
}

function geodir_location_get_locations_array( $args = null ) {
	global $wpdb,$geodirectory;

	$hide_country_part = geodir_get_option( 'lm_hide_country_part' );
	$hide_region_part = geodir_get_option( 'lm_hide_region_part' );

	$defaults = array(
		'fields' => '*',
		'what' => 'full',
		'city_val' => '',
		'region_val' => '',
		'country_val' => '' ,
		'country_non_restricted' => '',
		'region_non_restricted' => '',
		'city_non_restricted' => '',
		'filter_by_non_restricted' => true,
		'compare_operator' => 'like',
		'country_column_name' => 'country',
		'region_column_name' => 'region',
		'city_column_name' => 'city',
		'location_link_part' => true,
		'order' => 'location_id',
		'order_by' => 'asc',
		'group_by' => '',
		'no_of_records' => '',
		'spage' => '',
		'count_only' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	//escaping
	$args['order_by'] = $args['order_by'] ? sanitize_sql_orderby($args['order_by']) : '';
	$args['what'] = in_array($args['what'],array('country','region','city','neighbourhood')) ? esc_attr($args['what']) : '';

	if ( empty( $args['what'] ) ) {
		$args['what'] = 'full';
	}

	$search_query = '';
	$location_link_column = '';
	$location_default = $geodirectory->location->get_default_location();
	
	$permalink_structure = get_option('permalink_structure');
	$geodir_enable_country = geodir_get_option( 'lm_default_country' );
	$geodir_enable_region = geodir_get_option( 'lm_default_region' );
	$geodir_enable_city = geodir_get_option( 'lm_default_city' );
	$geodir_selected_countries = geodir_get_option( 'lm_selected_countries' );
	$geodir_selected_regions = geodir_get_option( 'lm_selected_regions' );
	$geodir_selected_cities = geodir_get_option( 'lm_selected_cities' );

	if ( $args['filter_by_non_restricted'] ) {
		// Non restricted countries
		if ( $args['country_non_restricted'] == '' ) {
			if( $geodir_enable_country == 'default' ) {
				$country_non_retsricted = isset( $location_default->country ) ? $location_default->country : '';
				$args['country_non_restricted']  = $country_non_retsricted;
			} else if( $geodir_enable_country == 'selected' ) {
				$country_non_retsricted = $geodir_selected_countries;

				if( !empty( $country_non_retsricted ) && is_array( $country_non_retsricted ) ) {
					$country_non_retsricted = implode(',' , $country_non_retsricted );
				}

				$args['country_non_restricted'] = $country_non_retsricted;
			}

			//$args['country_non_restricted'] = geodir_parse_location_list( $args['country_non_restricted'] );
		}

		// Non restricted Regions
		if ( $args['region_non_restricted'] == '' ) {
			if( $geodir_enable_region == 'default' ) {
				$regoin_non_restricted= isset( $location_default->region ) ? $location_default->region : '';
				$args['region_non_restricted']  = $regoin_non_restricted;
			} else if( $geodir_enable_region == 'selected' ) {
				$regoin_non_restricted = $geodir_selected_regions;
				
				if( !empty( $regoin_non_restricted ) && is_array( $regoin_non_restricted ) ) {
					$regoin_non_restricted = implode( ',', $regoin_non_restricted );
				}

				$args['region_non_restricted']  = $regoin_non_restricted;
			}

			//$args['region_non_restricted'] = geodir_parse_location_list( $args['region_non_restricted'] );
		}

		// Non restricted cities
		if ( $args['city_non_restricted'] == '' ) {
			if( $geodir_enable_city == 'default' ) {
				$city_non_retsricted = isset( $location_default->city ) ? $location_default->city : '';
				$args['city_non_restricted']  = $city_non_retsricted;
			} else if( $geodir_enable_city == 'selected' ) {
				$city_non_restricted = $geodir_selected_cities;

				if( !empty( $city_non_restricted ) && is_array( $city_non_restricted ) ) {
					$city_non_restricted = implode( ',', $city_non_restricted );
				}

				$args['city_non_restricted']  = $city_non_restricted;
			}
			//$args['city_non_restricted'] = geodir_parse_location_list( $args['city_non_restricted'] );
		}
	}

	if ( $args['location_link_part'] ) {
		switch( $args['what'] ) {
			case 'country':
				if ($permalink_structure != '') {
					$location_link_column = ", CONCAT_WS('/', country_slug) AS location_link ";
				} else {
					$location_link_column = ", CONCAT_WS('&gd_country=', '', country_slug) AS location_link ";
				}
			break;
			case 'country_region':
				if ($permalink_structure != '') {
					$location_link_column = ", CONCAT_WS('/', country_slug, region_slug) AS location_link ";
				} else {
					$location_link_column = ", CONCAT_WS('&', CONCAT('&gd_country=', country_slug), CONCAT('gd_region=', region_slug) ) AS location_link ";
				}
			break;
			case 'country_city':
				if ($permalink_structure != '') {
					$location_link_column = ", CONCAT_WS('/', country_slug, city_slug) AS location_link ";
				} else {
					$location_link_column = ", CONCAT_WS('&', CONCAT('&gd_country=', city_slug), CONCAT('gd_city=', city_slug) ) AS location_link ";
				}
			break;
			case 'region_city':
				if ($permalink_structure != '') {
					$location_link_column = ", CONCAT_WS('/', region_slug, city_slug) AS location_link ";
				} else {
					$location_link_column = ", CONCAT_WS('&', CONCAT('&gd_region=', city_slug), CONCAT('gd_city=', city_slug) ) AS location_link ";
				}
			break;
			case 'city':
				if ($permalink_structure != '') {
					$location_link_column = ", CONCAT_WS('/', city_slug) AS location_link ";
				} else {
					$location_link_column = ", CONCAT_WS('&gd_city=', '', city_slug) AS location_link ";
				}
			break;
			case 'full':				
				$concat_ws = array();
				
				if ($permalink_structure != '') {
					$concat_ws[] = 'country_slug';
					$concat_ws[] = 'region_slug';					
					$concat_ws[] = 'city_slug';
					
					$concat_ws = implode(', ', $concat_ws);
					
					$location_link_column = ", CONCAT_WS('/', " . $concat_ws . ") AS location_link ";
				} else {
					$concat_ws[] = "CONCAT('&gd_country=', country_slug)";
					$concat_ws[] = "CONCAT('gd_region=', region_slug)";
					$concat_ws[] = "CONCAT('gd_city=', city_slug)";
					
					$concat_ws = implode(', ', $concat_ws);
					
					$location_link_column = ", CONCAT_WS('&', " . $concat_ws . ") AS location_link ";
				}				
			break;
		}
	}

	switch( $args['compare_operator'] ) {
		case 'like' :
			if( isset( $args['country_val'] ) && $args['country_val'] != '' ) {
				$countries_search_sql = geodir_countries_search_sql( $args['country_val'] );
				$countries_search_sql = $countries_search_sql != '' ? " OR FIND_IN_SET(country, '" . $countries_search_sql . "')" : '';
				$translated_country_val = sanitize_title( trim( wp_unslash( $args['country_val'] ) ) );
				$search_query .= " AND ( lower(".$args['country_column_name'].") like  \"%". geodir_strtolower( $args['country_val'] )."%\" OR  lower(country_slug) LIKE \"". $translated_country_val ."%\" OR country_slug LIKE '" . urldecode( $translated_country_val ) . "' " . $countries_search_sql . " ) ";
			}

			if (isset($args['region_val']) &&  $args['region_val'] !='') {
				$search_query .= " AND lower(".$args['region_column_name'].") like  \"%". geodir_strtolower($args['region_val'])."%\" ";
			}

			if (isset($args['city_val']) && $args['city_val'] !='') {
				$search_query .= " AND lower(".$args['city_column_name'].") like  \"%". geodir_strtolower($args['city_val'])."%\" ";
			}
			break;

		case 'in' :
			if (isset($args['country_val'])  && $args['country_val'] !='') {
				$args['country_val'] = geodir_parse_location_list($args['country_val']) ;
				$search_query .= " AND lower(".$args['country_column_name'].") in($args[country_val]) ";
			}

			if (isset($args['region_val']) && $args['region_val'] !='' ) {
				$args['region_val'] = geodir_parse_location_list($args['region_val']) ;
				$search_query .= " AND lower(".$args['region_column_name'].") in($args[region_val]) ";
			}

			if (isset($args['city_val'])  && $args['city_val'] !='' ) {
				$args['city_val'] = geodir_parse_location_list($args['city_val']) ;
				$search_query .= " AND lower(".$args['city_column_name'].") in($args[city_val]) ";
			}

			break;
		default :
			if(isset($args['country_val']) && $args['country_val'] !='' ) {
				$countries_search_sql = geodir_countries_search_sql( $args['country_val'] );
				$countries_search_sql = $countries_search_sql != '' ? " OR FIND_IN_SET(country, '" . $countries_search_sql . "')" : '';
				$translated_country_val = sanitize_title( trim( wp_unslash( $args['country_val'] ) ) );
				$search_query .= " AND ( lower(".$args['country_column_name'].") =  '". geodir_strtolower($args['country_val'])."' OR  lower(country_slug) LIKE \"". $translated_country_val ."%\" OR country_slug LIKE '" . urldecode( $translated_country_val ) . "' " . $countries_search_sql . " ) ";
			}

			if (isset($args['region_val']) && $args['region_val'] !='') {
				$search_query .= " AND lower(".$args['region_column_name'].") =  \"". geodir_strtolower($args['region_val'])."\" ";
			}

			if (isset($args['city_val']) && $args['city_val'] !='' ) {
				$search_query .= " AND lower(".$args['city_column_name'].") =  \"". geodir_strtolower($args['city_val'])."\" ";
			}
			break ;

	}

	if ($args['country_non_restricted'] != '') {
		$search_query .= " AND LOWER(country) IN (".geodir_parse_location_list($args['country_non_restricted']).") ";
	}

	if ($args['region_non_restricted'] != '') {
		$search_query .= " AND LOWER(region) IN (".geodir_parse_location_list($args['region_non_restricted']).") ";
	}

	if ($args['city_non_restricted'] != '') {
		$search_query .= " AND LOWER(city) IN (".geodir_parse_location_list($args['city_non_restricted']).") ";
	}

	// page
	if ($args['no_of_records']){
		$spage = absint($args['no_of_records']) * absint($args['spage']);
	} else {
		$spage = "0";
	}

	// limit
	$limit = $args['no_of_records'] != '' ? ' LIMIT ' . absint($spage). ', ' . (int)$args['no_of_records'] . ' ' : '';
	
	$group_by = !empty($args['group_by']) ? 'GROUP BY ' . esc_sql( $args['group_by'] ) : '';
	$order_by = 'ORDER BY ';
	$order_by .= !empty($args['order']) ? esc_attr($args['order']) . ' ' : 'location_id ';
	$order_by .= !empty($args['order_by']) ? sanitize_sql_orderby($args['order_by']) : 'asc';
	
	if (!empty($args['count_only'])) {
		// query
		$query = "SELECT location_id FROM " . GEODIR_LOCATIONS_TABLE . " WHERE 1=1 " .  $search_query . " " . $group_by;
		$rows = $wpdb->get_results($query);
		
		$wpdb->flush();
		return !empty($rows) ? count($rows) : NULL;
	}

	// query
	$query = "SELECT " . $args['fields'] . $location_link_column . " FROM " . GEODIR_LOCATIONS_TABLE . " WHERE 1=1 " .  $search_query . " " . $group_by . " " . $order_by . " " . $limit;
	$rows = $wpdb->get_results($query);
	
	$wpdb->flush();

	return $rows;
}

function geodir_location_get_url( $location_terms, $permalink_structure = true, $with_base = true, $remove_location_terms = true ) {
	$location_link = '';

	if ( empty( $location_terms ) ) {
		return $location_link;
	}

	if ( $remove_location_terms ) {
		$location_terms = geodir_remove_location_terms( $location_terms );
	}

	if ( $permalink_structure ) {
		$location_link = implode( "/", array_values( $location_terms ) ) . '/';
	} else {
		foreach ( $location_terms as $term => $value ) {
			$location_link .= '&' . $term . '=' . $value;
		}
	}

	if ( $with_base ) {
		$location_base_link = geodir_get_location_link( 'base' );
		
		$location_link = $permalink_structure ? trailingslashit( $location_base_link ) . $location_link : $location_base_link . $location_link;
	}

	return $location_link;
}

/**
 * Add the location switcher menu item as an option in the WP menu screen.
 * 
 * @param $items
 * @param $loop_index
 *
 * @return mixed
 */
function geodir_location_add_menu_item($items,$loop_index){

	$add_item = new stdClass();
	$loop_index++;

	$add_item->ID = $loop_index;
	$add_item->object_id = $loop_index;
	$add_item->db_id = 0;
	$add_item->object =  'page';
	$add_item->menu_item_parent = 0;
	$add_item->type = 'custom';
	$add_item->title = __('Change Location','geodirectory');
	$add_item->url = "#location-switcher";
	$add_item->target = '';
	$add_item->attr_title = '';
	$add_item->classes = array('gd-menu-item');
	$add_item->xfn = '';
	$items['location_switcher'][] = $add_item;

	return $items;
}
add_filter('geodirectory_menu_items','geodir_location_add_menu_item',10,2);

function geodir_location_switcher_menu_item_name( $sorted_menu_items ) {
	global $aui_bs5, $geodirectory;

	if ( ! empty( $sorted_menu_items ) ) {
		foreach( $sorted_menu_items as $key => $menu_item ) {
			if ( isset( $menu_item->url ) && $menu_item->url == '#location-switcher' ) {
				$design_style = geodir_design_style();
				$data_bs = $aui_bs5 ? "data-bs-" : "data-";
				$location_name = $menu_item->title;
				$location_set = true;
				if(!empty($geodirectory->location->neighbourhood)){$location_name = $geodirectory->location->neighbourhood;}
				elseif(!empty($geodirectory->location->city)){$location_name = $geodirectory->location->city;}
				elseif(!empty($geodirectory->location->region)){$location_name = $geodirectory->location->region;}
				elseif(!empty($geodirectory->location->country)){$location_name = __( $geodirectory->location->country, 'geodirectory' );}
				else{$location_set = false;}
				if($location_set ){
					if($design_style){
						$sorted_menu_items[$key]->title = '<span class="gdlmls-menu-icon bsui"><span class="hover-swap"><i class="fas fa-map-marker-alt hover-content-original"></i><i class="fas fa-times hover-content c-pointer" title="'.__('Clear Location','geodirlocation').'" ' . $data_bs . 'toggle="tooltip"></i></span></span> '.$location_name;
					}else{
						$sorted_menu_items[$key]->title = '<span class="gdlmls-menu-icon"><i class="fas fa-map-marker-alt"></i><i class="fas fa-times gd-hide" title="'.__('Clear Location','geodirlocation').'"></i></span> '.$location_name;
					}
				}else{
					$sorted_menu_items[$key]->title = '<i class="fas fa-map-marker-alt"></i> '.$location_name;
				}
			}
		}
	}

	return $sorted_menu_items;
}
add_filter( 'wp_nav_menu_objects','geodir_location_switcher_menu_item_name',10);

/**
 * @since 2.0.0.21
 */
function geodir_location_main_query_posts_where( $where, $query, $post_type ) {
	$location_where = geodir_location_posts_where( $post_type, $query );

	if ( ! empty( $location_where ) ) {
		$where .= " AND {$location_where} ";
	}

	return $where;
}

/**
 * @since 2.0.0.21
 */
function geodir_location_posts_where( $post_type, $_wp_query ) {
	global $wpdb;

	$where = '';
	if ( ! ( $post_type && GeoDir_Post_types::supports( $post_type, 'location' ) ) ) {
		return $where;
	}

	if ( empty( $_wp_query ) ) {
		global $wp_query;
		$_wp_query = $wp_query;
	}

	$table = geodir_db_cpt_table( $post_type );
	$_where = array();

	if ( ! empty( $_wp_query->query_vars['city'] ) ) {
		$city = $_wp_query->query_vars['city'];
		$region = ! empty( $_wp_query->query_vars['region'] ) ? $_wp_query->query_vars['region'] : '';
		$country = ! empty( $_wp_query->query_vars['country'] ) ? $_wp_query->query_vars['country'] : '';

		$location = GeoDir_Location_City::get_info_by_slug( $city, $country, $region );

		if ( ! empty( $location ) ) {
			$_where['country'] = $location->country;
			$_where['region'] = $location->region;
			$_where['city'] = $location->city;
		} else {
			if ( $country ) {
				$_where['country'] = $country;
			}
			if ( $region ) {
				$_where['region'] = $region;
			}
			$_where['city'] = $city;
		}
	} elseif ( ! empty( $_wp_query->query_vars['region'] ) ) {
		$region = $_wp_query->query_vars['region'];
		$country = ! empty( $_wp_query->query_vars['country'] ) ? $_wp_query->query_vars['country'] : '';

		$location = GeoDir_Location_Region::get_info_by_slug( $region, $country );

		if ( ! empty( $location ) ) {
			$_where['country'] = $location->country;
			$_where['region'] = $location->region;
		} else {
			if ( $country ) {
				$_where['country'] = $country;
			}
			$_where['region'] = $region;
		}
	} elseif ( ! empty( $_wp_query->query_vars['country'] ) ) {
		$country = $_wp_query->query_vars['country'];

		$location = GeoDir_Location_Country::get_info_by_slug( $country );

		if ( ! empty( $location ) ) {
			$_where['country'] = $location->country;
		} else {
			$_where['country'] = $country;
		}
	}

	if ( ! empty( $_wp_query->query_vars['neighbourhood'] ) ) {
		$_where['neighbourhood'] = $_wp_query->query_vars['neighbourhood'];
	}

	$_where = apply_filters( 'geodir_location_posts_location_query_vars', $_where, $post_type, $_wp_query );

	if ( ! empty( $_where ) ) {
		$a_where = array();

		foreach ( $_where as $key => $value ) {
			$a_where[] = $wpdb->prepare( "{$table}.{$key} = %s", $value );
		}

		$where .= implode( " AND ", $a_where );
	}

	return $where;
}

/**
 * Retrieve location + cpt description data.
 *
 * @since 2.0.1.0
 *
 * @param int $per_page Per page limit. Default 0.
 * @param int $page_no Page number. Default 0.
 * @param string $location_type Location type. Default Empty.
 * @return array Array of locations data.
 */
function geodir_location_imex_cpt_locations_data( $per_page = 0, $page_no = 0, $location_type = '' ) {
    $items = geodir_location_imex_get_cpt_locations( $per_page, $page_no, $location_type );
    
    $rows = array();
    
    if (!empty($items)) {
		$row = array();
		if ( $location_type == 'city' ) {
			$row[] = 'city_slug';
			$row[] = 'city';
			$row[] = 'region_slug';
			$row[] = 'country_slug';
		} elseif ( $location_type == 'region' ) {
			$row[] = 'region_slug';
			$row[] = 'region';
			$row[] = 'country_slug';
		} else {
			$row[] = 'country_slug';
			$row[] = 'country';
		}
		
		$post_types = geodir_get_posttypes();

		foreach ( $post_types as $post_type ) {
			if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				$row[] = 'cpt_desc_' . $post_type;
			}
		}

		$rows[] = $row;

		foreach ( $items as $item ) {
			if ( empty( $item->country_slug ) ) {
				continue;
			}

			$row = array();
			if ( $location_type == 'city' ) {
				$row[] = $item->city_slug;
				$row[] = $item->city;
				$row[] = $item->region_slug;
				$row[] = $item->country_slug;
			} elseif ( $location_type == 'region' ) {
				$row[] = $item->region_slug;
				$row[] = $item->region;
				$row[] = $item->country_slug;
			} else {
				$row[] = $item->country_slug;
				$row[] = $item->country;
			}

			$cpt_desc = ! empty( $item->cpt_desc ) ? json_decode( $item->cpt_desc, true ) : array();
			foreach ( $post_types as $post_type ) {
				if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					$row[] = is_array( $cpt_desc ) && isset( $cpt_desc[ $post_type ] ) ? stripslashes( $cpt_desc[ $post_type ] ) : '';
				}
			}

			$rows[] = $row;
		}
    }

    return $rows;
}

/**
 * Get the cpt locations data to export as csv file.
 *
 * @since 2.0.1.0
 *
 * @global object $wpdb WordPress Database object.
 *
 * @param int $per_page Number of records per page.
 * @param int $page_no Current page number. Default 0.
 * @param string $location_type Location type.
 * @return array Location data.
 */
function geodir_location_imex_get_cpt_locations( $per_page, $page_no = 0, $location_type = 'country' ) {
	global $wpdb;
	
	$page_no = max( $page_no, 1 );

	$fields = 'l.country_slug, l.country';
	$join = " LEFT JOIN `" . GEODIR_LOCATION_SEO_TABLE . "` AS ls ON ( ls.location_type = '" . $location_type . "' AND ls.country_slug = l.country_slug";
	$groupby = "l.country_slug";
	$orderby = "l.country ASC, l.region ASC, l.city ASC";
		
	if ( $location_type == 'region' || $location_type == 'city' ) {
		$fields .= ', l.region_slug, l.region';
		$join .= " AND ls.region_slug = l.region_slug";
		$groupby = "CONCAT( l.country_slug, '|', l.region_slug )";
		
		if ( $location_type == 'city' ) {
			$fields .= ', l.city_slug, l.city';
			$join .= " AND ls.city_slug = l.city_slug";
			$groupby = "CONCAT( l.country_slug, '|', l.region_slug, '|', l.city_slug )";
		}
	}
	$join .= " )";

	$fields .= ', ls.cpt_desc';
	
	$where = '';
	$where = apply_filters( 'geodir_location_imex_get_cpt_locations_where', $where, $per_page, $page_no, $location_type );
	if ($where != '') {
		$where = " WHERE " . $where;
	}
	if ($groupby != '') {
		$groupby = " GROUP BY " . $groupby;
	}
	if ($orderby != '') {
		$orderby = " ORDER BY " . $orderby;
	}
	
	$limit = (int)$per_page > 0 ? " LIMIT " . (($page_no - 1) * $per_page) . ", " . $per_page : '';
	
	$query = "SELECT " . $fields . " FROM `" . GEODIR_LOCATIONS_TABLE . "` AS l " . $join . $where . $groupby . $orderby . $limit;
	$results = $wpdb->get_results($query);
	
	return $results;
}

/**
 * Merge post locations.
 *
 * @since 2.1.0.6
 *
 * @return int No. of locations merged.
 */
function geodir_location_merge_post_locations() {
	$post_types = geodir_get_posttypes();

	$merged = 0;

	if ( ! empty( $post_types ) ) {
		foreach ( $post_types as $post_type ) {
			$merged += (int) GeoDir_Location_City::merge_post_locations( $post_type );
		}
	}

	return $merged;
}

/**
 * Filter widget listings query args.
 *
 * @since 2.1.0.10
 *
 * @param array $query_args Query args.
 * @param array $instance Listings parameters.
 * @return array Query args.
 */
function geodir_location_widget_listings_query_args( $query_args, $instance ) {
	if ( GeoDir_Location_Neighbourhood::is_active() && ! empty( $instance['add_location_filter'] ) &&  ! empty( $instance['neighbourhood'] ) && GeoDir_Post_types::supports( $query_args['post_type'], 'location' ) ) {
		$query_args['neighbourhood'] = $instance['neighbourhood'];
	}
	return $query_args;
}