<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_Locations.
 *
 * @class    GeoDir_Location_Locations
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_Locations extends GeoDir_Location {
	public $neighbourhood;
	public $neighbourhood_slug;

	public function set_current( $type = '' ) {
		global $wp, $wp_query, $geodirectory, $post, $gd_post;

		$skip = false;
		/**
		 * Filter to skip set current location.
		 *
		 * @since 2.0.0.24
		 *
		 * @param bool $skip True to skip set current location.
		 */
		$skip = apply_filters( 'geodir_location_skip_set_current', $skip );
		if ( $skip === true ) {
			return;
		}

		$location_info = new stdClass();
		$location = new stdClass();

		// type
		$location->type = $type && is_scalar( $type ) && in_array( $type, array( 'neighbourhood', 'city', 'region', 'country', 'me', 'gps' ) ) ? $type : $this->current_location_type();
		$check_404 = false;

		if ( geodir_is_page( 'single' ) ) {
			if ( empty( $gd_post->ID ) ) {
				$gd_post = geodir_get_post_info( $post->ID );
			}

			if ( isset( $gd_post->post_type ) && GeoDir_Post_types::supports( $gd_post->post_type, 'location' ) ) {
				if ( $location->type == 'city' ) {
					$slug = GeoDir_Location_City::get_slug_by_name( $gd_post->city, $gd_post->region, $gd_post->country );
					$location_info = $this->setup_city( $slug );
				} elseif ( $location->type == 'region' ) {
					$country_slug = $geodirectory->location->get_country_slug( $gd_post->country );
					$slug = GeoDir_Location_Region::get_slug_by_name( $gd_post->region, $gd_post->country );
					$location_info = $this->setup_region( $slug, $country_slug );
				} elseif ( $location->type == 'country' ) {
					$slug = $geodirectory->location->get_country_slug( $gd_post->country );
					$location_info = $this->setup_country( $slug );
				}
			}
		} else {
			$check_404 = true;
			/**
			 * Set the 404 page.
			 *
			 * @since 2.0.0.24
			 *
			 * @param bool $check_404 True to set 404 page.
			 */
			$check_404 = apply_filters( 'geodir_location_set_current_check_404', $check_404 );

			$latlon = $this->get_latlon();

			$country_slug = isset( $wp->query_vars['country'] ) ? $wp->query_vars['country'] : '';
			$region_slug = isset( $wp->query_vars['region'] ) ? $wp->query_vars['region'] : '';
			$city_slug = isset( $wp->query_vars['city'] ) ? $wp->query_vars['city'] : '';

			if ( $location->type == 'neighbourhood' && GeoDir_Location_Neighbourhood::is_active() ) {
				$location_info = $this->setup_neighbourhood( $wp->query_vars['neighbourhood'] );
			} elseif ( $location->type == 'city' ) {
				$location_info = $this->setup_city( $city_slug, $country_slug, $region_slug );
			} elseif ( $location->type == 'region' ) {
				$location_info = $this->setup_region( $region_slug, $country_slug );
			} elseif ( $location->type == 'country' ) {
				$location_info = $this->setup_country( $country_slug );
			} elseif ( $location->type == 'me' && ! empty( $latlon ) ) {
				$location_info->latitude = isset( $latlon['lat'] ) ? $latlon['lat'] : '';
				$location_info->longitude = isset( $latlon['lon'] ) ? $latlon['lon'] : '';
			} elseif ( $location->type == 'gps' && ! empty( $latlon ) ) {
				$location_info->latitude = isset( $latlon['lat'] ) ? $latlon['lat'] : '';
				$location_info->longitude = isset( $latlon['lon'] ) ? $latlon['lon'] : '';
			}
		}

		// names
		if ( GeoDir_Location_Neighbourhood::is_active() ) {
			$location->neighbourhood = isset( $location_info->neighbourhood ) ? $location_info->neighbourhood : '';
		}
		$location->city = isset( $location_info->city ) ? $location_info->city : '';
		$location->region = isset( $location_info->region ) ? $location_info->region : '';
		$location->country = isset( $location_info->country ) ? $location_info->country : '';

		// gps
		$location->latitude = isset( $location_info->latitude ) ? $location_info->latitude : '';
		$location->longitude = isset( $location_info->longitude ) ? $location_info->longitude : '';

		// slugs
		if ( GeoDir_Location_Neighbourhood::is_active() ) {
			$location->neighbourhood_slug = isset( $location_info->slug ) ? $location_info->slug : '';
		}
		$location->city_slug = isset( $location_info->city_slug ) ? $location_info->city_slug : '';
		$location->region_slug = isset( $location_info->region_slug ) ? $location_info->region_slug : '';
		$location->country_slug = isset( $location_info->country_slug ) ? $location_info->country_slug : '';

		// id
		$location->id = isset( $location_info->location_id ) ? $location_info->location_id : '';

		// is_default
		$location->is_default = isset( $location_info->is_default ) ? $location_info->is_default : 0;

		// If location query vars are set but not found then we 404
		if ( $check_404 && empty( (array) $location_info ) && ( isset( $wp->query_vars['neighbourhood'] ) || isset( $wp->query_vars['city'] ) || isset( $wp->query_vars['region'] ) || isset( $wp->query_vars['country'] ) ) ) {
			$wp_query->set_404();
			status_header( 404 );
		}

		/**
		 * Filter the default location.
		 *
		 * @since 1.0.0
		 * @package GeoDirectory
		 *
		 * @param string $location_result The default location object.
		 */
		$location_result = apply_filters( 'geodir_set_current_location', $location );

		$this->country_slug = $location_result->country_slug;
		$this->country = $location_result->country;
		$this->region_slug = $location_result->region_slug;
		$this->region = $location_result->region;
		$this->city_slug = $location_result->city_slug ;
		$this->city = $location_result->city;
		if ( GeoDir_Location_Neighbourhood::is_active() ) {
			$this->neighbourhood_slug = $location_result->neighbourhood_slug;
			$this->neighbourhood      = $location_result->neighbourhood;
		}
		$this->latitude = $location_result->latitude;
		$this->longitude = $location_result->longitude;
		$this->id = $location_result->id;
		$this->type = $location_result->type;
		$this->is_default = $location_result->is_default;
	}

	public function current_location_type() {
		global $wp;

		$single_location_type = geodir_is_page( 'single' ) ? geodir_get_option( 'lm_url_filter_archives_on_single', 'city' ) : '';
		$type = '';

		if ( $single_location_type ) {

			$type = $single_location_type;

			// if listing is missing some location data we change the location type so things like archive links update to the provided location.
			if( $type && ! empty( $wp->query_vars['city'] ) && $wp->query_vars['city'] === geodir_get_option( 'permalink_missing_city_base','explore' )){
				if( ! empty( $wp->query_vars['region'] ) && $wp->query_vars['region'] === geodir_get_option( 'permalink_missing_region_base','discover' )){
					if( ! empty( $wp->query_vars['country'] ) && $wp->query_vars['country'] === geodir_get_option( 'permalink_missing_country_base','global' )){
						$type = '';
					}else{
						$type = 'country';
					}
				}else{
					$type = 'region';
				}
			}

		} else {
			if ( GeoDir_Location_Neighbourhood::is_active() && ! empty( $wp->query_vars['neighbourhood'] ) ) {
				$type = 'neighbourhood';
			} elseif ( ! empty( $wp->query_vars['city'] ) ) {
				$type = 'city';
			} elseif ( ! empty( $wp->query_vars['region'] ) ) {
				$type = 'region';
			} elseif ( ! empty( $wp->query_vars['country'] ) ) {
				$type = 'country';
			} elseif ( ! empty( $wp->query_vars['near'] ) ) {
				if ( $wp->query_vars['near'] == 'me' ) {
					$type = 'me';
				} elseif ( $wp->query_vars['near'] == 'gps' ) {
					$type = 'gps';
				}
			}
		}

		return $type;
	}

	public function setup_neighbourhood( $slug ) {
		$hood_info = GeoDir_Location_Neighbourhood::get_info_by_slug( $slug );

		if ( ! empty( $hood_info ) ) {
			$location = $hood_info;
		} else {
			$location = new stdClass();
		}

		return $location;
	}

	public function setup_city( $slug, $country_slug = '', $region_slug = '' ) {
		$city_info = GeoDir_Location_City::get_info_by_slug( $slug, $country_slug, $region_slug );

		if ( ! empty( $city_info ) ) {
			$location = $city_info;
		} else {
			$location = new stdClass();
		}

		return $location;
	}

	public function setup_region( $slug, $country_slug = '' ) {
		$region_info = GeoDir_Location_Region::get_info_by_slug( $slug, $country_slug );

		if ( ! empty( $region_info ) ) {
			$location = $region_info;
		} else {
			$location = new stdClass();
		}

		return $location;
	}

	public function setup_country( $slug ) {
		$country_info = GeoDir_Location_Country::get_info_by_slug( $slug );

		if ( ! empty( $country_info ) ) {
			$location = $country_info;
		} else {
			$location = new stdClass();
		}

		return $location;
	}


	public function get_country_name_from_slug($slug){
		$country_info = GeoDir_Location_Country::get_info_by_slug($slug);
		return !empty($country_info->country) ? $country_info->country : '';
	}

	public function get_region_name_from_slug($slug){
		$region_info = GeoDir_Location_Region::get_info_by_slug($slug);
		return !empty($region_info->region) ? $region_info->region : '';
	}

	public function get_city_name_from_slug($slug){
		$city_info = GeoDir_Location_City::get_info_by_slug( $slug);
		return !empty($city_info->city) ? $city_info->city : '';
	}

	public function get_neighbourhood_name_from_slug($slug){
		$hood_info = GeoDir_Location_Neighbourhood::get_info_by_slug( $slug);
		return !empty($hood_info->city) ? $hood_info->neighbourhood : '';
	}

	public function get_post_location($gd_post){
		$location = new stdClass();
		$location->country = isset($gd_post->country) ? $gd_post->country : '';
		$location->region = isset($gd_post->region) ? $gd_post->region : '';
		$location->city = isset($gd_post->city) ? $gd_post->city : '';
		$location->neighbourhood = isset($gd_post->neighbourhood) ? $gd_post->neighbourhood : '';

		$location_info = $this->get_location_by_names( $location->city, $location->region, $location->country );

		$location->country_slug = ! empty($location_info) ? $location_info->country_slug : $this->create_location_slug($location->country);
		$location->region_slug = ! empty($location_info) ? $location_info->region_slug : $this->create_location_slug($location->region);
		$location->city_slug = ! empty($location_info) ? $location_info->city_slug : $this->create_location_slug($location->city);
		$location->neighbourhood_slug = isset($gd_post->neighbourhood) ? $this->create_location_slug($gd_post->neighbourhood) : '';

		return $location;
	}


	/**
	 * Function for addons to add new location.
	 *
	 * @since 1.0.0
	 * @since 1.5.3 The translated country creates new slug - FIXED.
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param array $location_info Location information.
	 * @return mixed
	 */
	public function add_new_location( $location ) {
		global $wpdb;

		if ( ! empty( $location ) ) {
			$location = array_map( 'stripslashes_deep', $location );

			//$country = geodir_get_normal_country( $location['country'] );
			$country = $this->validate_country_name( $location['country'] );
			if(!$country){return false;}
			$region = $location['region'];
			$city = $location['city'];

			$found = $this->get_location_by_names( $city, $region, $country );
			if ( ! empty( $found ) ) {
				$location = $found;
			} else {
				$location['country'] = $country;
				if ( $country_slug = $this->get_country_slug( $country) ) {
					$location['country_slug'] = $country_slug;
				}
				if ( empty( $location['region_slug'] ) ) {
					$location['region_slug'] = $this->create_region_slug( $region, ( isset( $location['country_slug'] ) ? $location['country_slug'] : '' ), $location['country'] );
				}
				if ( empty( $location['city_slug'] ) ) {
					$location['city_slug'] = $this->create_city_slug( $city, 0, ( isset( $location['region_slug'] ) ? $location['region_slug'] : '' ) );
				}
				$data = array(
					'country' => $location['country'],
					'country_slug' => $location['country_slug'],
					'region' => $location['region'],
					'region_slug' => $location['region_slug'],
					'city' => $location['city'],
					'city_slug' => $location['city_slug'],
					'latitude' => $location['latitude'],
					'longitude' => $location['longitude'],
					'is_default' => ! empty( $location['is_default'] ) ? 1 : 0,
				);
				$data_types = array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
				);

				$wpdb->insert( GEODIR_LOCATIONS_TABLE, $data, $data_types );

				if ( ! empty( $wpdb->insert_id ) ) {
					$location = geodir_get_location_by_id( '' , $wpdb->insert_id );
				} else {
					$location = (object)$location;
				}
			}
		}

		return $location;
	}


	/**
	 * Make sure the country name is valid.
	 * 
	 * @param $country_name
	 *
	 * @return bool
	 */
	public function validate_country_name( $country_name ) {
		$valid = false;

		if ( $country_name ) {
			$countries = function_exists( 'geodir_wp_countries' ) ? geodir_wp_countries() : wp_country_database()->get_countries();

			foreach ( $countries as $country ) {
				if ( $country_name == $country->name ) {
					$valid = true;
					break;
				}
			}
		}

		// if not valid check for translation
		if ( ! $valid ) {
			global $wpdb;

			if ( $wpdb->get_var( $wpdb->prepare( "SELECT country FROM " . GEODIR_LOCATIONS_TABLE . " WHERE country LIKE %s ORDER BY is_default DESC LIMIT 1", array( $country_name ) ) ) ) {
				$valid = true;
			}
		}

		return $valid ? $country_name : false;
	}

	public function get_location_by_names( $city = '', $region = '', $country = '' ) {
		global $wpdb;

		// check cache
		$cache = wp_cache_get("geodir_location_get_location_by_names_".sanitize_title_with_dashes($city.$region.$country));
		if($cache !== false){
			return $cache;
		}

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE city LIKE %s AND region LIKE %s AND country LIKE %s LIMIT 1", array( $city, $region, $country ) ) );

		// set cache
		wp_cache_set("geodir_location_get_location_by_names_".sanitize_title_with_dashes($city.$region.$country), $result );

		return $result;
	}

	/**
	 * Get the country slug for country name.
	 *
	 * @since 1.4.4
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $name Country name.
	 * @return string Country slug.
	 */
	public function get_country_slug( $name ) {
		global $wpdb, $wp_country_database;

		if ( $slug = $wpdb->get_var( $wpdb->prepare( "SELECT country_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE country LIKE %s ORDER BY is_default DESC LIMIT 1", array( $name ) ) ) ) {
			return $slug;
		}

		if ( in_array( strtolower( $name ), array( "england", "northern ireland", "scotland", "wales" ) ) ) {
			$slug = sanitize_title( $name );
		} else {
			$slug = $wp_country_database->get_country_slug( $name );
		}

		return $slug;
	}

	/**
	 * Get the region slug for region name.
	 *
	 * @since 1.4.4
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $name Region name.
	 * @param string $country_slug Country slug. Default empty.
	 * @param string $country Country name. Default empty.
	 * @return string Region slug.
	 */
	public function create_region_slug( $name, $country_slug = '', $country = '' ) {
		global $wpdb;

		if ( $country_slug != '' ) {
			$query = $wpdb->prepare( "SELECT region_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE region LIKE %s AND country_slug = %s ORDER BY is_default DESC LIMIT 1", array( $name, $country_slug ) );
			if ( $slug = $wpdb->get_var( $query ) ) {
				return $slug;
			}
		}

		if ( $country != '' ) {
			$query = $wpdb->prepare("SELECT region_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE region LIKE %s AND country LIKE %s ORDER BY is_default DESC LIMIT 1", array( $name, $country ) );
			if ( $slug = $wpdb->get_var( $query ) ) {
				return $slug;
			}
		}

		$slug = $this->create_location_slug( $name );

		if ( $country_slug != '' ) {
			// Check region/city slug
			$check_sql = "SELECT region_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE ( country_slug != %s AND region_slug LIKE %s ) OR city_slug LIKE %s LIMIT 1";
			$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $country_slug, $slug, $slug ) );

			if ( $slug_check ) {
				$suffix = 1;

				do {
					$alt_slug = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";

					$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $country_slug, $alt_slug, $alt_slug ) );

					$suffix++;
				} while ( $slug_check );

				$slug = $alt_slug;

				return $slug;
			}
		}

		if ( $country != '' ) {
			// Check region/city slug
			$check_sql = "SELECT region_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE ( country != %s AND region_slug LIKE %s ) OR city_slug LIKE %s LIMIT 1";
			$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $country, $slug, $slug ) );

			if ( $slug_check ) {
				$suffix = 1;

				do {
					$alt_slug = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";

					$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $country, $alt_slug, $alt_slug ) );

					$suffix++;
				} while ( $slug_check );

				$slug = $alt_slug;

				return $slug;
			}
		}

		return $slug;
	}

	/**
	 * Get a unique city slug, will add `-1` to it if already exists
	 *
	 * @since 1.4.4
	 * @since 1.5.0 Fix looping when importing duplicate city names.
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $name City name.
	 * @param int $location_id Location id. Default 0.
	 * @param string $region_slug Region slug to check.
	 * @return string City slug.
	 */
	public function create_city_slug( $name, $location_id = 0, $region_slug = '' ) {
		global $wpdb;

		$slug = $this->create_location_slug( $name );

		// Check city slug
		if ( (int)$location_id > 0 ) {
			$check_sql = "SELECT city_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE region_slug LIKE %s OR ( city_slug LIKE %s AND location_id != %d ) LIMIT 1";
			if ( $region_slug && $region_slug == $slug ) {
				$slug_check = $region_slug;
			} else {
				$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $slug, $location_id ) );
			}
		} else {
			$check_sql = "SELECT city_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE region_slug LIKE %s OR city_slug LIKE %s LIMIT 1";

			if ( $region_slug && $region_slug == $slug ) {
				$slug_check = $region_slug;
			} else {
				$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $slug ) );
			}
		}

		if ( $slug_check ) {
			$suffix = 1;

			do {
				$alt_slug = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";

				if ( (int)$location_id > 0 ) {
					if ( $region_slug && $region_slug == $alt_slug ) {
						$slug_check = $region_slug;
					} else {
						$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug, $alt_slug, $location_id ) );
					}
				} else {
					if ( $region_slug && $region_slug == $alt_slug ) {
						$slug_check = $region_slug;
					} else {
						$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug, $alt_slug ) );
					}
				}

				$suffix++;
			} while ( $slug_check );

			$slug = $alt_slug;
		}

		return $slug;
	}

	/**
	 * An array of the used location vars.
	 *
	 * These are used in the where queries.
	 *
	 * @return array
	 */
	public function allowed_query_variables(){
		return array('country','region','city','neighbourhood');
	}

	public function get_post_ids( $post_type, $city = '', $region = '', $country = '', $neighbourhood = '' ) {
		global $wpdb, $plugin_prefix;

		$posts = NULL;

		if ( empty( $post_type ) || ( empty( $city ) && empty( $region ) && empty( $country ) && empty( $neighbourhood ) ) ) {
			return $posts;
		}

		$table = geodir_db_cpt_table( $post_type );

		if ( ! empty( $city ) ) {
			$where[] = "city LIKE %s";
			$params[] = $city;
		}

		if ( ! empty( $region ) ) {
			$where[] = "region LIKE %s";
			$params[] = $region;
		}

		if ( ! empty( $country ) ) {
			$where[] = "country LIKE %s";
			$params[] = $country;
		}

		if ( ! empty( $neighbourhood ) ) {
			$where[] = "neighbourhood LIKE %s";
			$params[] = $neighbourhood;
		}

		$where = implode( " AND ", $where );

		return $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM " . $table . " WHERE " . $where, $params ) );
	}

	public function has_duplicate_slugs() {
		// Check regions duplicate slugs
		if ( GeoDir_Location_Region::has_duplicate_slugs() ) {
			return true;
		}

		// Check cities duplicate slugs
		if ( GeoDir_Location_City::has_duplicate_slugs() ) {
			return true;
		}
		return false;
	}
}