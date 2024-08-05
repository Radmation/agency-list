<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_AJAX.
 *
 * AJAX Event Handler.
 *
 * @class    GeoDir_Location_AJAX
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_AJAX {

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
			'json_search_regions'			=> false,
			'json_search_cities'			=> false,
			'ajax_save_location'			=> false,
			'ajax_save_seo'					=> false,
			'ajax_delete_location'			=> false,
			'ajax_set_default'				=> false,
			'fill_location'					=> true,
			'fill_location_on_add_listing' 	=> true,
			'set_region_on_map'				=> true,
			'change_term_location_desc'		=> false,
			'save_term_location_desc'		=> false,
			'ajax_save_neighbourhood'		=> false,
			'ajax_delete_neighbourhood'		=> false,
			'ajax_merge_location'			=> false,
			'location_filter_options'		=> false,
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
		
	/**
	 * Search for regions and return json.
	 */
	public static function json_search_regions( $term = '' ) {
		//check_ajax_referer( 'search-regions', 'security' );

		$term = geodir_clean( empty( $term ) ? stripslashes( $_GET['term'] ) : $term );
		$limit = ! empty( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 20;

		if ( empty( $term ) ) {
			wp_die();
		}

		$items = GeoDir_Location_AJAX::search_regions( $term, $limit );

		$results = array();
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$results[ $item->region ] = rawurldecode( $item->region );
			}
		}

		wp_send_json( apply_filters( 'geodir_location_json_search_found_regions', $results ) );
	}
	
	/**
	 * Search for regions.
	 */
	public static function search_regions( $term = '', $limit = 20 ) {
		global $wpdb;

		$like_term	= '%' . $wpdb->esc_like( $term ) . '%';
		$limit 		= $limit > 0 ? "LIMIT " . absint( $limit ) : '';

		$sql = $wpdb->prepare( "SELECT DISTINCT region_slug, region, country, country_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE region LIKE %s ORDER BY region ASC " . $limit, array( $like_term ) );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Search for cities and return json.
	 */
	public static function json_search_cities( $term = '' ) {
		//check_ajax_referer( 'search-cities', 'security' );

		$term = geodir_clean( empty( $term ) ? stripslashes( $_GET['term'] ) : $term );
		$limit = ! empty( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 20;

		if ( empty( $term ) ) {
			wp_die();
		}

		$items = GeoDir_Location_AJAX::search_cities( $term, $limit );

		$results = array();
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$results[ $item->city ] = rawurldecode( $item->city );
			}
		}

		wp_send_json( apply_filters( 'geodir_location_json_search_found_cities', $results ) );
	}
	
	/**
	 * Search for cities.
	 */
	public static function search_cities( $term = '', $limit = 20 ) {
		global $wpdb;

		$like_term	= '%' . $wpdb->esc_like( $term ) . '%';
		$limit 		= $limit > 0 ? "LIMIT " . $limit : '';

		$sql = $wpdb->prepare( "SELECT DISTINCT city_slug, city, region_slug, region, country, country_slug, location_id, longitude, latitude, is_default FROM " . GEODIR_LOCATIONS_TABLE . " WHERE city LIKE %s ORDER BY city ASC " . $limit, array( $like_term ) );

		return $wpdb->get_results( $sql );
	}
	
	public static function ajax_save_location() {
		global $wpdb, $plugin_prefix;

		check_ajax_referer( 'geodir-save-location', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$location_id 		= ! empty( $_POST['location_id'] ) ? absint( $_POST['location_id'] ) : 0;
			$city 				= ! empty( $_POST['location_city'] ) ? sanitize_text_field( wp_unslash( $_POST['location_city'] ) ) : '';
			$region 			= ! empty( $_POST['location_region'] ) ? sanitize_text_field( wp_unslash( $_POST['location_region'] ) ) : '';
			$country 			= ! empty( $_POST['location_country'] ) ? sanitize_text_field( wp_unslash( $_POST['location_country'] ) ) : '';
			$latitude 			= ! empty( $_POST['location_latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['location_latitude'] ) ) : '';
			$longitude 			= ! empty( $_POST['location_longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['location_longitude'] ) ) : '';
			$meta_title 		= ! empty( $_POST['location_meta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['location_meta_title'] ) ) : '';
			$meta_description 	= ! empty( $_POST['location_meta_description'] ) ? sanitize_text_field( wp_unslash( $_POST['location_meta_description'] ) ) : '';
			$description 		= ! empty( $_POST['location_description'] ) ? wp_unslash( $_POST['location_description'] ) : '';
			$cpt_description 	= ! empty( $_POST['location_cpt_description'] ) ? wp_unslash( $_POST['location_cpt_description'] ) : '';
			$image       		= ! empty( $_POST['location_image'] ) ? absint( $_POST['location_image'] ) : 0;
			$image_tagline 		= ! empty( $_POST['location_image_tagline'] ) ? sanitize_text_field( wp_unslash( $_POST['location_image_tagline'] ) ) : '';
			if ( isset( $_POST['default_location_timezone_string'] ) ) {
				geodir_update_option( 'default_location_timezone_string', sanitize_text_field( wp_unslash( $_POST['default_location_timezone_string'] ) ) );
			}

			$errs = array();
			if ( empty( $city ) ) {
				$errs[] = __( 'City is empty!', 'geodirlocation' );
			}
			if ( empty( $region ) ) {
				$errs[] = __( 'Region is empty!', 'geodirlocation' );
			}
			if ( empty( $country ) ) {
				$errs[] = __( 'Country is empty!', 'geodirlocation' );
			}
			if ( empty( $latitude ) ) {
				$errs[] = __( 'Latitude is empty!', 'geodirlocation' );
			}
			if ( empty( $longitude ) ) {
				$errs[] = __( 'Longitude is empty!', 'geodirlocation' );
			}
			if ( ! empty( $errs ) ) {
				throw new Exception( implode( '<br>', $errs ) );
			}
			
			if ( $location_id > 0 ) {
				$exists = $wpdb->get_var( 
					$wpdb->prepare( 
						"SELECT location_id FROM " . GEODIR_LOCATIONS_TABLE . " WHERE city LIKE %s AND region LIKE %s AND country LIKE %s AND location_id != %d",
						array( 
							$city, 
							$region, 
							$country,
							$location_id
						)
					)
				);
			} else {
				$exists = geodir_get_location_by_names( $city, $region, $country );
			}
			if ( ! empty( $exists ) ) {
				throw new Exception( wp_sprintf( __( 'Location %s, %s, %s already exists!', 'geodirlocation' ), $city, $region, __( $country, 'geodirectory' ) ) );
			}

			$location = geodir_get_location_by_id( '' , $location_id );
			
			if ( ! empty( $location ) ) {
				$country_slug 	= $location->country == $country ? $location->country_slug : geodir_location_country_slug( $country );
				$region_slug 	= $location->region == $region ? $location->region_slug : geodir_location_region_slug( $region, $country_slug, $country );
				$city_slug 		= $location->city == $city ? $location->city_slug : geodir_location_city_slug( $city, $location_id, $region_slug );
				$is_default		= $location->is_default;
			} else {
				$country_slug 	= geodir_location_country_slug( $country );
				$region_slug 	= geodir_location_region_slug( $region, $country_slug, $country );
				$city_slug 		= geodir_location_city_slug( $city, 0, $region_slug );
				$is_default		= 0;
			}

			$save_data = array(
				'city' 			=> $city,
				'region' 		=> $region,
				'country' 		=> $country,
				'city_slug' 	=> $city_slug,
				'region_slug' 	=> $region_slug,
				'country_slug' 	=> $country_slug,
				'latitude' 		=> $latitude,
				'longitude' 	=> $longitude,
				'is_default' 	=> $is_default,
			);

			if ( ! empty( $location ) ) {
				$message = __( 'Location updated successfully.', 'geodirlocation' );
				$saved = $wpdb->update( GEODIR_LOCATIONS_TABLE, $save_data, array( 'location_id' => $location_id ) );
			} else {
				$message = __( 'Location added successfully.', 'geodirlocation' );
				$saved = $wpdb->insert( GEODIR_LOCATIONS_TABLE, $save_data );
				$location_id = $wpdb->insert_id;
			}

			if ( $saved !== false ) {
				// Clear locations cache.
				wp_cache_delete( "geodir_location_city_get_info_by_slug_" . $city_slug . ":" . $country_slug . ":" . $region_slug );
				wp_cache_delete( "geodir_location_city_get_info_by_name_" . $city . ":" . $country . ":" . $region );
				wp_cache_delete( "geodir_location_city_get_slug_by_name_" . sanitize_title_with_dashes( $city . $region . $country ) );
				wp_cache_delete( "geodir_location_region_get_info_by_name_" . $region . ":" . $country );
				wp_cache_delete( "geodir_location_region_get_slug_by_name_" . sanitize_title_with_dashes( $region . $country ) );
				wp_cache_delete( "geodir_location_get_location_by_names_" . sanitize_title_with_dashes( $city . $region . $country ) );
				wp_cache_delete( "gd_post_save_location_" . sanitize_key( $country . "_" . $region . "_" . $city ), 'gd_post_save_location' );

				if ( $location_id > 0 ) {
					if ( ! empty( $location->is_default ) ) {
						geodir_location_set_default( $location_id );
					}

					if ( ! empty( $location ) ) {
						// Update detail table
						$post_types = geodir_get_posttypes();
						if ( ! empty( $post_types ) ) {
							foreach ( $post_types as $post_type ) {
								$table = geodir_db_cpt_table( $post_type );

								if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
									$sql = $wpdb->prepare( "UPDATE " . $table . " SET city = %s, region = %s, country = %s
										WHERE city LIKE %s AND region LIKE %s AND country LIKE %s",
										array( 
											$city,
											$region,
											$country,
											$location->city,
											$location->region,
											$location->country
										)
									);
									$wpdb->query( $sql );
								}  else {
									if ( geodir_column_exist( $table, 'city' ) ) {
										$wpdb->query( "UPDATE " . $table . " SET city = '', region = '', country = '', neighbourhood = ''" );
									}
								}
							}
						}
					}
				}

				// Save seo data for city.
				$seo_data = array();
				$seo_data['country_slug'] 	= $country_slug;
				$seo_data['region_slug'] 	= $region_slug;
				$seo_data['city_slug'] 		= $city_slug;
				$seo_data['meta_title'] 	= $meta_title;
				$seo_data['meta_desc'] 		= $meta_description;
				$seo_data['location_desc'] 	= $description;
				$seo_data['image'] 	        = $image;
				$seo_data['image_tagline'] 	= $image_tagline;

				$cpt_desc = array();
				if ( ! empty( $cpt_description ) ) {
					foreach ( $cpt_description as $post_type => $_cpt_desc ) {
						if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
							$cpt_desc[ $post_type ] = $_cpt_desc;
						}
					}
				}
				$seo_data['cpt_desc'] 		= ! empty( $cpt_desc ) ? json_encode( $cpt_desc ) : '';

				GeoDir_Location_SEO::save_seo_data( 'city', $seo_data );
			}

			$location = geodir_get_location_by_id( '' , $location_id );

			$data = array( 'message' => $message, 'location' => $location );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function ajax_save_neighbourhood() {
		global $wpdb, $plugin_prefix;

		check_ajax_referer( 'geodir-save-neighbourhood', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$location_id 		= ! empty( $_POST['neighbourhood_location_id'] ) ? absint( $_POST['neighbourhood_location_id'] ) : 0;
			$neighbourhood_id 	= ! empty( $_POST['neighbourhood_id'] ) ? absint( $_POST['neighbourhood_id'] ) : 0;
			$name 				= ! empty( $_POST['neighbourhood_name'] ) ? sanitize_text_field( wp_unslash( $_POST['neighbourhood_name'] ) ) : '';
			$latitude 			= ! empty( $_POST['neighbourhood_latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['neighbourhood_latitude'] ) ) : '';
			$longitude 			= ! empty( $_POST['neighbourhood_longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['neighbourhood_longitude'] ) ) : '';
			$meta_title 		= ! empty( $_POST['neighbourhood_meta_title'] ) ? stripslashes( sanitize_text_field( $_POST['neighbourhood_meta_title'] ) ) : '';
			$meta_description 	= ! empty( $_POST['neighbourhood_meta_description'] ) ? stripslashes( sanitize_text_field( $_POST['neighbourhood_meta_description'] ) ) : '';
			$description 		= ! empty( $_POST['neighbourhood_description'] ) ? stripslashes( $_POST['neighbourhood_description'] ) : '';
			$cpt_description 	= ! empty( $_POST['neighbourhood_cpt_description'] ) ? stripslashes_deep( $_POST['neighbourhood_cpt_description'] ) : '';
			$image       		= ! empty( $_POST['neighbourhood_image'] ) ? absint( $_POST['neighbourhood_image'] ) : ( ! empty( $_POST['location_image'] ) ? absint( $_POST['location_image'] ) : 0 );

			$errs = array();
			if ( empty( $latitude ) || empty( $longitude ) ) {
				$errs[] = __( 'Latitude / longitude is empty!', 'geodirlocation' );
			}
			if ( empty( $name ) ) {
				$errs[] = __( 'Neighbourhood name is empty!', 'geodirlocation' );
			}
			if ( ! empty( $errs ) ) {
				throw new Exception( implode( '<br>', $errs ) );
			}

			if ( empty( $location_id ) ) {
				throw new Exception( __( 'Location not found!', 'geodirlocation' ) );
			}

			if ( ! empty( $neighbourhood_id ) ) {
				$neighbourhood 	= GeoDir_Location_Neighbourhood::get_info_by_id( $neighbourhood_id );

				if ( empty( $neighbourhood ) ) {
					throw new Exception( __( 'Requested neighbourhood does not found!', 'geodirlocation' ) );
				}
			}
			
			$duplicate = GeoDir_Location_Neighbourhood::check_duplicate( $name, $location_id, $neighbourhood_id );
			if ( $duplicate ) {
				throw new Exception( wp_sprintf( __( 'Neighbourhood with name "%s" already exists!', 'geodirlocation' ), $name ) );
			}

			$data = array();
			$data['location_id'] 	= $location_id;
			$data['name'] 			= $name;
			$data['latitude'] 		= $latitude;
			$data['longitude'] 		= $longitude;
			$data['meta_title'] 	= $meta_title;
			$data['meta_desc'] 		= $meta_description;
			$data['description']	= $description;
			$data['image']			= $image;
			$cpt_desc = array();
			if ( ! empty( $cpt_description ) ) {
				foreach ( $cpt_description as $post_type => $_cpt_desc ) {
					if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
						$cpt_desc[ $post_type ] = $_cpt_desc;
					}
				}
			}
			$data['cpt_desc'] 		= ! empty( $cpt_desc ) ? json_encode( $cpt_desc ) : '';

			$return = GeoDir_Location_Neighbourhood::save_data( $data, $neighbourhood_id );
			if ( ! $return ) {
				throw new Exception( __( 'Nothing to update!', 'geodirlocation' ) );
			}

			$data = array( 'id' => $return, 'message' => __( 'Data saved successfully.', 'geodirlocation' ) );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function ajax_save_seo() {
		global $wpdb, $plugin_prefix;

		check_ajax_referer( 'geodir-save-seo', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$location_type 		= ! empty( $_POST['location_type'] ) ? sanitize_text_field( $_POST['location_type'] ) : '';
			$country_slug 		= ! empty( $_POST['country_slug'] ) ? sanitize_text_field( $_POST['country_slug'] ) : '';
			$region_slug 		= ! empty( $_POST['region_slug'] ) ? sanitize_text_field( $_POST['region_slug'] ) : '';

			$meta_title 		= ! empty( $_POST['location_meta_title'] ) ? stripslashes( sanitize_text_field( $_POST['location_meta_title'] ) ) : '';
			$meta_description 	= ! empty( $_POST['location_meta_description'] ) ? stripslashes( sanitize_text_field( $_POST['location_meta_description'] ) ) : '';
			$description 		= ! empty( $_POST['location_description'] ) ? stripslashes( $_POST['location_description'] ) : '';
			$cpt_description 	= ! empty( $_POST['location_cpt_description'] ) ? stripslashes_deep( $_POST['location_cpt_description'] ) : '';
			$image       		= ! empty( $_POST['location_image'] ) ? absint( $_POST['location_image'] ) : 0;
			$image_tagline 		= ! empty( $_POST['location_image_tagline'] ) ? stripslashes( sanitize_text_field( $_POST['location_image_tagline'] ) ) : '';
			
			$errs = array();
			if ( $location_type != 'country' && $location_type != 'region' ) {
				$errs[] = __( 'Invalid location type!', 'geodirlocation' );
			}
			if ( $location_type == 'country' && empty( $country_slug ) ) {
				$errs[] = __( 'Country slug is empty!', 'geodirlocation' );
			}
			if ( $location_type == 'region' && empty( $region_slug ) ) {
				$errs[] = __( 'Region slug is empty!', 'geodirlocation' );
			}
			if ( ! empty( $errs ) ) {
				throw new Exception( implode( '<br>', $errs ) );
			}
			
			// Save seo data for country/region.
			$seo_data = array();
			$seo_data['country_slug'] = $country_slug;
			$seo_data['region_slug'] = $region_slug;
			$seo_data['meta_title'] = $meta_title;
			$seo_data['meta_desc'] = $meta_description;
			$seo_data['location_desc'] = $description;
			$seo_data['image'] = $image;
			$seo_data['image_tagline'] = $image_tagline;

			$cpt_desc = array();
			if ( ! empty( $cpt_description ) ) {
				foreach ( $cpt_description as $post_type => $_cpt_desc ) {
					if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
						$cpt_desc[ $post_type ] = $_cpt_desc;
					}
				}
			}
			$seo_data['cpt_desc'] = ! empty( $cpt_desc ) ? json_encode( $cpt_desc ) : '';

			GeoDir_Location_SEO::save_seo_data( $location_type, $seo_data );

			$message = __( 'Data saved successfully.', 'geodirlocation' );

			$data = array( 'message' => $message );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function ajax_delete_location() {
		global $wpdb, $plugin_prefix;

		$location_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		check_ajax_referer( 'geodir-delete-location-' . $location_id, 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			if ( ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to delete this location.', 'geodirlocation' ) );
			}

			$location = $location_id ? GeoDir_Location_City::get_info_by_id( (int) $location_id ) : NULL;
			if ( empty( $location ) ) {
				throw new Exception( __( 'Requested location does not exists.', 'geodirlocation' ) );
			}

			if ( ! empty( $location->is_default ) ) {
				throw new Exception( __( 'Default location can not be deleted!', 'geodirlocation' ) );
			}

			if ( GeoDir_Location_City::delete_location( $location ) ) {
				$message = __( 'Location deleted successfully.', 'geodirlocation' );
			} else {
				throw new Exception( __( 'Fail to delete location!', 'geodirlocation' ) );
			}

			$data = array( 'message' => $message );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function ajax_set_default() {
		global $wpdb;

		$location_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		check_ajax_referer( 'geodir-set-default-' . $location_id, 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$location = $location_id ? geodir_get_location_by_id( '' , $location_id ) : NULL;
			if ( empty( $location ) ) {
				throw new Exception( __( 'Requested location does not exists!', 'geodirlocation' ) );
			}

			geodir_location_set_default( $location_id );

			$message = __( 'Default location set successfully.', 'geodirlocation' );

			$data = array( 'message' => $message );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function fill_location() {
		global $wpdb;

		$type = ! empty( $_POST['type'] ) ? wp_unslash( sanitize_text_field( $_POST['type'] ) ) : '';
		$country = ! empty( $_POST['country'] ) ? wp_unslash( sanitize_text_field( $_POST['country'] ) ) : '';
		$region = ! empty( $_POST['region'] ) ? wp_unslash( sanitize_text_field( $_POST['region'] ) ) : '';
		$city = ! empty( $_POST['city'] ) ? wp_unslash( sanitize_text_field( $_POST['city'] ) ) : '';
		$neighbourhood = ! empty( $_POST['neighbourhood'] ) ? wp_unslash( sanitize_text_field( $_POST['neighbourhood'] ) ) : '';
		$term = ! empty( $_POST['term'] ) ? wp_unslash( sanitize_text_field( $_POST['term'] ) ) : '';

		if ( $type == 'country' || $type == 'region' || $type == 'city' ) {
			if ( $type == 'country' ) {
				$country = '';
				$options = '<option value="">' . __( 'Select country', 'geodirlocation' ) . '</option>';
			} else if ( $type == 'region' ) {
				if ( ! empty( $term ) ) {
					$region = $term;
				}
				$options = '<option value="">' . __( 'Select region', 'geodirlocation' ) . '</option>';
			} else {
				if ( ! empty( $term ) ) {
					$city = $term;
				}
				$options = '<option value="">' . __( 'Select city', 'geodirlocation' ) . '</option>';
			}

			$args = array(
				'what' => $type,
				'country_val' => $country,
				'region_val' => $region,
				'city_val' => $city,
				'echo' => false,
				'compare_operator' => '=',
				'format' => array('type' => 'array')
			);

			$results = geodir_get_location_array( $args );

			if ( ! empty( $results ) ) {
				$value = sanitize_title( $args[  $type . '_val' ] );
				foreach ( $results as $row ) {
					$option_value = stripslashes( $row->{$type} );
					$selected = $value && $value == sanitize_title( stripslashes( $row->{$type} ) ) ? ' selected="selected"' : '';
					$options .= '<option value="' . esc_attr( $option_value ) . '"' . $selected . '>' . $option_value . '</option>';
				}
			} else {
				if ( ! empty( $term ) ) {
					$options .= '<option value="' . esc_attr( $term ) . '" >' . $term . '</option>';
				}
			}
		} else if ( $type == 'neighbourhood' ) {
			$options = '<option value="">' . __( 'Select neighbourhood', 'geodirlocation' ) . '</option>';
		}

		$data = array( 'options' => $options );
		wp_send_json_success( $data );
	}

	public static function fill_location_on_add_listing() {
		global $wpdb;

		$type = ! empty( $_POST['type'] ) ? wp_unslash( sanitize_text_field( $_POST['type'] ) ) : '';
		$country = ! empty( $_POST['country'] ) ? wp_unslash( sanitize_text_field( $_POST['country'] ) ) : '';
		$region = ! empty( $_POST['region'] ) ? wp_unslash( sanitize_text_field( $_POST['region'] ) ) : '';
		$city = ! empty( $_POST['city'] ) ? wp_unslash( sanitize_text_field( $_POST['city'] ) ) : '';
		$neighbourhood = ! empty( $_POST['neighbourhood'] ) ? wp_unslash( sanitize_text_field( $_POST['neighbourhood'] ) ) : '';
		$term = ! empty( $_POST['term'] ) ? wp_unslash( sanitize_text_field( $_POST['term'] ) ) : '';

		if ( $type == 'country' || $type == 'region' || $type == 'city' ) {
			if ( $type == 'country' ) {
				$country = '';
				$options = '<option value="">' . __( 'Select country', 'geodirlocation' ) . '</option>';
			} else if ( $type == 'region' ) {
				if ( ! empty( $term ) ) {
					$region = $term;
				}
				$options = '<option value="">' . __( 'Select region', 'geodirlocation' ) . '</option>';
			} else {
				if ( ! empty( $term ) ) {
					$city = $term;
				}
				$options = '<option value="">' . __( 'Select city', 'geodirlocation' ) . '</option>';
			}

			$args = array(
				'what' => $type,
				'country_val' => $country,
				'region_val' => $region,
				'city_val' => $city,
				'echo' => false,
				'compare_operator' => '=',
				'format' => array('type' => 'array')
			);

			$results = geodir_get_location_array( $args );

			if ( ! empty( $results ) ) {
				$value = sanitize_title( $args[  $type . '_val' ] );
				foreach ( $results as $row ) {
					$option_value = stripslashes( $row->{$type} );
					$selected = $value && $value == sanitize_title( stripslashes( $row->{$type} ) ) ? ' selected="selected"' : '';
					$options .= '<option value="' . esc_attr( $option_value ) . '"' . $selected . '>' . $option_value . '</option>';
				}
			} else {
				if ( ! empty( $term ) ) {
					$options .= '<option value="' . esc_attr( $term ) . '" >' . $term . '</option>';
				}
			}
		} else if ( $type == 'neighbourhood' ) {
			$options = '<option value="">' . __( 'Select neighbourhood', 'geodirlocation' ) . '</option>';

			$results = GeoDir_Location_Neighbourhood::get_neighbourhoods_by_location_names( $city, $region, $country );

			$found = false;
			if ( ! empty( $results ) ) {
				$value = sanitize_title( $neighbourhood );
				foreach ( $results as $row ) {
					$option_value = $row->hood_slug;
					$selected = $value && ( $value == $row->hood_slug || $value == sanitize_title( stripslashes( $row->hood_name ) ) ) ? ' selected="selected"' : '';
					if ( $selected ) {
						$found = true;
					}
					$options .= '<option value="' . esc_attr( $option_value ) . '"' . $selected . '>' . stripslashes( $row->hood_name ) . '</option>';
				}
			}
			if ( ! $found && $neighbourhood ) {
				$options .= '<option value="' . esc_attr( $neighbourhood ) . '" selected="selected">' . $neighbourhood . '</option>';
			}
		}

		$data = array( 'options' => $options );
		wp_send_json_success( $data );
	}

	public static function set_region_on_map() {
		global $wpdb,$geodirectory;

		$country = ! empty( $_POST['country'] ) ? wp_unslash( sanitize_text_field( $_POST['country'] ) ) : '';
		$city = ! empty( $_POST['city'] ) ? wp_unslash( sanitize_text_field( $_POST['city'] ) ) : '';

		if ( ! empty( $country ) &&  ! empty( $city ) ) {
			$region = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT region FROM " . GEODIR_LOCATIONS_TABLE . " WHERE ( country=%s OR country=%s ) AND city=%s",
					array( $country, $geodirectory->location->validate_country_name( $country ), $city )
				)
			);
			
			if ( empty( $region ) ) {
				$region = ! empty( $_POST['region'] ) ? wp_unslash( sanitize_text_field( $_POST['region'] ) ) : '';
			}
			$data = array( 'html' => $region );
			wp_send_json_success( $data );
		}
		wp_die( -1 );
	}

	public static function change_term_location_desc() {
		if ( empty( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'geodir-save-term-desc' ) ) {
			wp_die( -1 );
		}
		$type = !empty($_POST['_type']) ? $_POST['_type'] : 'city';
		$gd_location = isset($_POST['locid']) ? (int)$_POST['locid'] : '';
		$term_id = isset($_POST['catid']) ? (int)$_POST['catid'] : '';
		$country = isset($_POST['country']) ? wp_unslash( sanitize_text_field( $_POST['country'] ) ) : '';
		$region = isset($_POST['region']) ? wp_unslash( sanitize_text_field( $_POST['region'] ) ) : '';

		if (is_admin() || defined('GD_TESTING_MODE')) {
			if (current_user_can('manage_options') && $term_id > 0) {
				$success = false;
				$content = '';
				
				switch ($type) {
					case 'country':
						if (!empty($country)) {
							$success = true;
							$content = geodir_location_get_term_top_desc($term_id, $country, $type);
						}
						break;
					case 'region':
						if (!empty($country) && !empty($region)) {
							$success = true;
							$content = geodir_location_get_term_top_desc($term_id, $region, $type, $country);
						}
						break;
					case 'city':
						if (!empty($gd_location)) {
							$success = true;
							$content = geodir_location_get_term_top_desc($term_id, $gd_location, $type);
						}
						break;
				}
				
				if ($success) {
					echo $content;
					wp_die();
				}
			}
		}

		echo 'FAIL';
		wp_die();
	}

	public static function save_term_location_desc() {
		if ( empty( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'geodir-save-term-desc' ) ) {
			wp_die( -1 );
		}
		$type = !empty($_POST['_type']) ? $_POST['_type'] : 'city';
		$locid = isset($_POST['locid']) ? (int)$_POST['locid'] : '';
		$term_id = isset($_POST['catid']) ? (int)$_POST['catid'] : '';
		$content = isset($_POST['content']) ? $_POST['content'] : '';
		$loc_default = isset($_POST['loc_default']) ? $_POST['loc_default'] : '';
		$country = isset($_POST['country']) ? wp_unslash( sanitize_text_field( $_POST['country'] ) ) : '';
		$region = isset($_POST['region']) ? wp_unslash( sanitize_text_field( $_POST['region'] ) ) : '';
		
		if ( is_admin() || defined( 'GD_TESTING_MODE' ) ) {    
			if ( current_user_can( 'manage_options' ) && $term_id > 0 ) {
				$success = false;
				
				switch ($type) {
					case 'country':
						if (!empty($country)) {
							$success = geodir_location_save_term_top_desc($term_id, $content, $country, $type);
						}
						break;
					case 'region':
						if (!empty($country) && !empty($region)) {
							$success = geodir_location_save_term_top_desc($term_id, $content, $region, $type, $country);
						}
						break;
					case 'city':
						if (!empty($locid)) {
							$success = geodir_location_save_term_top_desc($term_id, $content, $locid, $type);
						}
						break;
				}
				
				if ( $success ) {
					echo 'OK';
					wp_die();
				}
			}
		}
		echo 'FAIL';
		wp_die();
	}

	public static function ajax_delete_neighbourhood() {
		global $wpdb, $plugin_prefix;

		$neighbourhood_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		check_ajax_referer( 'geodir-delete-neighbourhood-' . $neighbourhood_id, 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$neighbourhood 	= GeoDir_Location_Neighbourhood::get_info_by_id( $neighbourhood_id );
			if ( empty( $neighbourhood ) ) {
				throw new Exception( __( 'Requested neighbourhood does not exists!', 'geodirlocation' ) );
			}

			if ( GeoDir_Location_Neighbourhood::delete_by_id( $neighbourhood->id ) ) {
				$message = __( 'Neighbourhood deleted successfully.', 'geodirlocation' );
			} else {
				throw new Exception( __( 'Fail to delete neighbourhood!', 'geodirlocation' ) );
			}

			$data = array( 'message' => $message );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function ajax_merge_location() {
		global $wpdb, $geodirectory, $plugin_prefix;

		check_ajax_referer( 'geodir-merge-location', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$city 				= ! empty( $_POST['city'] ) ? stripslashes( sanitize_text_field( $_POST['city'] ) ) : '';
			$region 			= ! empty( $_POST['region'] ) ? stripslashes( sanitize_text_field( $_POST['region'] ) ) : '';
			$country 			= ! empty( $_POST['country'] ) ? stripslashes( sanitize_text_field( $_POST['country'] ) ) : '';
			$latitude 			= ! empty( $_POST['latitude'] ) ? stripslashes( sanitize_text_field( $_POST['latitude'] ) ) : '';
			$longitude 			= ! empty( $_POST['longitude'] ) ? stripslashes( sanitize_text_field( $_POST['longitude'] ) ) : '';
			$primary 			= ! empty( $_POST['geodir_primary_city'] ) ? (int)$_POST['geodir_primary_city'] : 0;
			$merge_ids 			= ! empty( $_POST['merge_ids'] ) ? explode( ',', $_POST['merge_ids'] ) : array();
			
			if ( empty( $merge_ids ) || empty( $primary ) ) {
				throw new Exception( __( 'No locations selected to merge!', 'geodirlocation' ) );
			}
			$errs = array();
			if ( empty( $city ) ) {
				$errs[] = __( 'City is empty!', 'geodirlocation' );
			}
			if ( empty( $region ) ) {
				$errs[] = __( 'Region is empty!', 'geodirlocation' );
			}
			if ( empty( $country ) ) {
				$errs[] = __( 'Country is empty!', 'geodirlocation' );
			}
			if ( empty( $latitude ) ) {
				$errs[] = __( 'Latitude is empty!', 'geodirlocation' );
			}
			if ( empty( $longitude ) ) {
				$errs[] = __( 'Longitude is empty!', 'geodirlocation' );
			}
			if ( ! empty( $errs ) ) {
				throw new Exception( implode( '<br>', $errs ) );
			}

			$items = GeoDir_Location_City::get_items_by_ids( $merge_ids );
			if ( empty( $items ) ) {
				throw new Exception( __( 'Requested locations not found!', 'geodirlocation' ) );
			}

			$has_default = false;
			foreach ( $items as $item ) {
				if ( $item->location_id == $primary ) {
					continue;
				}

				if ( ! $has_default && ! empty( $item->is_default ) ) {
					$has_default = true;
				}

				$wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_LOCATIONS_TABLE . " WHERE location_id = %d", array( $item->location_id ) ) );
			}

			$ids 			= implode( "','", $merge_ids );
			$country_slug 	= $geodirectory->location->get_country_slug( $country );
			$region_slug 	= $geodirectory->location->create_location_slug( $region, '', $country );
			$city_slug 		= $geodirectory->location->create_location_slug( $city );

			$post_types = geodir_get_posttypes();
			foreach ( $post_types as $post_type ) {
				$table = geodir_db_cpt_table( $post_type );

				if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					if ( ! empty( $items ) ) {
						foreach ( $items as $item ) {
							if ( $city === $item->city && $region === $item->region && $country === $item->country ) {
								continue; // skip same location
							}
							$wpdb->query( $wpdb->prepare( "UPDATE " . $table . " SET city = %s, region = %s, country = %s WHERE city LIKE %s AND region LIKE %s AND country LIKE %s", array( $city, $region, $country, $item->city, $item->region, $item->country ) ) );
						}
					}
				} else {
					if ( geodir_column_exist( $table, 'city' ) ) {
						$wpdb->query( "UPDATE " . $table . " SET city = '', region = '', country = '', neighbourhood = ''" );
					}
				}
			}

			$city_data = array(
				'location_id' 	=> $primary,
				'city'	 		=> $city,
				'region' 		=> $region,
				'country' 		=> $country,
				'city_slug' 	=> $city_slug,
				'region_slug' 	=> $region_slug,
				'country_slug' 	=> $country_slug,
			);
			if ( $has_default ) {
				$city_data['is_default'] = 1;
			}
			$saved = geodir_location_update_city( $city_data );
			if ( $saved ) {
				if ( $has_default ) {
					geodir_location_set_default( $primary );
				}

				// Update neighbourhoods
				$wpdb->query( "UPDATE " . GEODIR_NEIGHBOURHOODS_TABLE . " SET hood_location_id=" . $primary . " WHERE hood_location_id IN('{$ids}')" );
			}
			
			// Flush rewrite rules
			flush_rewrite_rules( false );

			$data = array( 'message' => wp_sprintf( __( 'Location merged successfully. Go back to %sCities%s', 'geodirlocation' ), '<a href="' . admin_url( 'admin.php?page=gd-settings&tab=locations&section=cities' ) . '">', '<a/>' ) );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function location_filter_options() {
		global $wpdb;

		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
		$country = isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : '';
		$region = isset( $_POST['region'] ) ? sanitize_text_field( $_POST['region'] ) : '';

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$options = '';
			switch( $type ) {
				case 'country':
				case 'region':
				case 'city':
					$where = array();
					if ( ! empty( $country ) ) {
						$where[] = $wpdb->prepare( "country LIKE %s", wp_unslash( $country ) );
					}
					if ( ! empty( $region ) ) {
						$where[] = $wpdb->prepare( "region LIKE %s", wp_unslash( $region ) );
					}
					$where = ! empty( $where ) ? "WHERE " . implode( ' AND ', $where ) : '';

					// Get the results
					$results = $wpdb->get_results( "SELECT DISTINCT `{$type}` FROM " . GEODIR_LOCATIONS_TABLE . " {$where} ORDER BY {$type} ASC" );
					if ( ! empty( $results ) ) {
						foreach ( $results as $row ) {
							$options .= '<option value="' . esc_attr( $row->{$type} )  . '">' . $row->{$type} . '</option>';
						}
					}
					break;
				case 'neighbourhood':
					break;
			}

			$data = array( 'options' => $options );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}
}