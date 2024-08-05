<?php
/**
 * GeoDirectory Location Manager API
 *
 * Handles GD-API endpoint requests.
 *
 * @author   GeoDirectory
 * @category API
 * @package  GeoDir_Location_Manager/API
 * @since    2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeoDir_Location_API {

	/**
	 * Setup class.
	 * @since 2.0
	 */
	public function __construct() {
	}

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'setup' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ), 101 );
		add_filter( 'geodir_rest_posts_clauses_request', array( __CLASS__, 'rest_posts_clauses_request' ), 999, 3 );
		add_filter( 'geodir_rest_posts_clauses_fields', array( __CLASS__, 'rest_posts_fields' ), 20, 3 );
		add_filter( 'geodir_rest_posts_clauses_where', array( __CLASS__, 'rest_posts_where' ), 10, 3 );
		add_filter( 'geodir_rest_posts_order_sort_by_key', array( __CLASS__, 'rest_posts_order_sort_by_key' ), 20, 4 );
		add_filter( 'geodir_location_rest_prepare_location', array( __CLASS__, 'rest_prepare_location' ), 10, 4 );
		add_filter( 'geodir_location_get_locations_sql', array( __CLASS__, 'filter_locations_sql' ), 10, 3 );
		add_filter( 'geodir_rest_get_post_data', array( __CLASS__, 'response_get_post_data' ), 1, 4 );
		add_filter( 'geodir_rest_post_sort_options', array( __CLASS__, 'rest_post_sort_options' ), 20, 2 );
	}
	
	public static function rest_api_includes() {
		include_once( dirname( __FILE__ ) . '/api/class-geodir-location-rest-location-types-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-geodir-location-rest-locations-controller.php' );

		if ( GeoDir_Location_Neighbourhood::is_active() ) {
			include_once( dirname( __FILE__ ) . '/api/class-geodir-location-rest-neighbourhoods-controller.php' );
		}
	}

	public static function setup() {
		$gd_post_types = geodir_get_posttypes();

		foreach ( $gd_post_types as $post_type ) {
			if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				continue;
			}

			// listings
			add_filter( 'rest_' . $post_type . '_collection_params', array( __CLASS__, 'post_collection_params' ), 10, 2 );
			add_filter( 'rest_' . $post_type . '_query', array( __CLASS__, 'post_query' ), 20, 2 );
			
			// categories
			add_filter( 'rest_' . $post_type . 'category_collection_params', array( __CLASS__, 'taxonomy_collection_params' ), 10, 2 );
			if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				add_filter( 'rest_' . $post_type . 'category_query', array( __CLASS__, 'rest_taxonomy_query' ), 0, 2 );
				add_filter( 'geodir_rest_' . $post_type . 'category_query_result', array( __CLASS__, 'rest_taxonomy_query_result' ), 10, 3 );
			}
			
			// tags
			add_filter( 'rest_' . $post_type . '_tags_collection_params', array( __CLASS__, 'taxonomy_collection_params' ), 10, 2 );
			
		}
	}

    public static function location_collection_params( $object = array() ) {
        $params = array();

        $params['country'] = array(
            'description'        => __( 'Limit results to specific country.', 'geodirlocation' ),
            'type'               => 'string',
            'sanitize_callback'  => 'sanitize_text_field',
            'validate_callback'  => 'rest_validate_request_arg',
        );

        $params['region'] = array(
            'description'        => __( 'Limit results to specific region.', 'geodirlocation' ),
            'type'               => 'string',
            'sanitize_callback'  => 'sanitize_text_field',
            'validate_callback'  => 'rest_validate_request_arg',
        );

        $params['city'] = array(
            'description'        => __( 'Limit results to specific city.', 'geodirlocation' ),
            'type'               => 'string',
            'sanitize_callback'  => 'sanitize_text_field',
            'validate_callback'  => 'rest_validate_request_arg',
        );

        if ( GeoDir_Location_Neighbourhood::is_active( 'neighbourhood' ) ) {
            $params['neighbourhood'] = array(
                'description'        => __( 'Limit results to specific neighbourhood.', 'geodirlocation' ),
                'type'               => 'string',
                'sanitize_callback'  => 'sanitize_text_field',
                'validate_callback'  => 'rest_validate_request_arg',
            );
        }

        $params['near'] = array(
            'description'  => __( 'Filter posts by near address. Ex: 230 Vine Street, Philadelphia', 'geodirlocation' ),
            'type' => 'string',
            'default' => NULL,
        );

        $params['ip'] = array(
            'description'  => __( 'IP to find nearest posts.', 'geodirlocation' ),
            'type' => 'string',
            'default' => NULL,
        );

        $params['latitude'] = array(
            'description'  => __( 'Filter by latitude. Ex: 39.9558230481.', 'geodirlocation' ),
            'type' => 'string',
            'default' => NULL,
        );

        $params['longitude'] = array(
            'description'  => __( 'Filter by longitude. Ex: -75.1440811157.', 'geodirlocation' ),
            'type' => 'string',
            'default' => NULL,
        );

        $params['distance'] = array(
            'description'  => wp_sprintf( __( 'Filter posts within distance xx %s.', 'geodirlocation' ), self::get_distance_unit() ),
            'type' => 'string',
            'default' => NULL,
        );

        return $params;
    }

	public static function post_collection_params( $params, $post_type_obj ) {
		$locations_params = self::location_collection_params( $post_type_obj );

		if ( ! empty( $locations_params ) ) {
			foreach ( $locations_params as $key => $schema ) {
				$params[ $key ] = $schema;
			}
		}

		return $params;
	}

	public static function post_query( $args, $request ) {
		$mappings = array(
			'near' => 'near',
			'ip' => 'ip',
			'latitude' => 'latitude',
			'longitude' => 'longitude',
			'distance' => 'distance',
		);

		foreach ( $mappings as $key => $param ) {
			if ( isset( $request[ $key ] ) ) {
				$field = isset( $mappings ) ? $mappings[ $key ] : $key;
				$args[ $field ] = $request[ $key ];
			}
		}

		// Set latitude/longitude
		if ( empty( $args['latitude'] ) || empty( $args['longitude'] ) ) {
			if ( empty( $args['ip'] ) && ! empty( $args['near'] ) && $args['near'] == 'me' ) {
				$args['ip'] = geodir_get_ip();
			}

			if ( ! empty( $args['ip'] ) && rest_is_ip_address( $args['ip'] ) && ( $gps = geodir_geo_by_ip( $args['ip'] ) ) ) {
				$args['latitude'] = $gps['latitude'];
				$args['longitude'] = $gps['longitude'];
			}

			if ( ( empty( $args['latitude'] ) || empty( $args['longitude'] ) ) && ! empty( $args['near'] ) && $args['near'] != 'me' ) {
				$address = array( $args['near'] );
				$near_1 = trim( geodir_get_option('search_near_addition') );
				if ( ! empty( $near_1 ) ) {
					$address[] = $near_1;
				}
				$near_2 = apply_filters( 'geodir_search_near_addition', '' );
				if ( ! empty( $near_2 ) && trim( $near_2, ' ,' ) != '' ) {
					$address[] = trim( $near_2, ' ,' );
				}
				$address = implode(  ", ", $address );

				$gps = geodir_get_gps_from_address( $address );

				if ( ! empty( $gps ) && ! empty( $gps['latitude'] ) && ! empty( $gps['longitude'] ) ) {
					$args['latitude'] = $gps['latitude'];
					$args['longitude'] = $gps['longitude'];
				}
			}
		}

		return $args;
	}

	public static function taxonomy_collection_params( $params, $taxonomy ) {
		$locations_params = self::location_collection_params( $taxonomy );

		if ( ! empty( $locations_params ) ) {
			foreach ( $locations_params as $key => $schema ) {
				$params[ $key ] = $schema;
			}
		}

		return $params;
	}

	public static function rest_taxonomy_query( $prepared_args, $request ) {
		global $wp, $geodirectory;

		if ( ! empty( $wp->query_vars['country'] ) || ! empty( $wp->query_vars['region'] ) || ! empty( $wp->query_vars['city'] ) || ! empty( $wp->query_vars['neighbourhood'] ) ) {
			$geodirectory->location->set_current();
		}

		return $prepared_args;
	}

	/**
	 * Register REST API routes.
	 * @since 2.0.0
	 */
	public static function register_rest_routes() {
        self::rest_api_includes();

		$controller = new Geodir_Location_REST_Location_Types_Controller();
        $controller->register_routes();

		$location_types = self::get_location_types();

		if ( !empty( $location_types ) ) {
			foreach ( $location_types as $key => $location_type ) {
				$location_type = (object)$location_type;
				$class = ! empty( $location_type->rest_controller_class ) ? $location_type->rest_controller_class : 'Geodir_Location_REST_Locations_Controller';

				if ( ! class_exists( $class ) ) {
					continue;
				}
				$controller = new $class( $key );
				$controller->register_routes();
			}
		}


	}

	public static function get_location_types() {
		$types = array();

		$types['country'] = array(
			'type'					=> 'country',
			'name'          		=> 'countries',
			'title'         		=> __( 'Countries', 'geodirlocation' ),
			'description'   		=> __( 'All countries.', 'geodirlocation' ),
			'fields'        		=> array( 'title', 'slug', 'is_default' ),
			'show_in_rest'			=> true,
			'rest_base'     		=> 'locations/countries',
			'rest_controller_class'	=> 'Geodir_Location_REST_Locations_Controller',
		);

		$types['region'] = array(
			'type'					=> 'region',
			'name'          		=> 'regions',
			'title'         		=> __( 'Regions', 'geodirlocation' ),
			'description'   		=> __( 'All regions.', 'geodirlocation' ),
			'fields'        		=> array( 'title', 'slug', 'country', 'country_slug', 'is_default' ),
			'show_in_rest'			=> true,
			'rest_base'     		=> 'locations/regions',
			'rest_controller_class'	=> 'Geodir_Location_REST_Locations_Controller'
		);

		$types['city'] = array(
			'type'					=> 'city',
			'name'          		=> 'cities',
			'title'         		=> __( 'Cities', 'geodirlocation' ),
			'description'   		=> __( 'All cities.', 'geodirlocation' ),
			'fields'        		=> array( 'id', 'title', 'slug', 'region', 'region_slug', 'country', 'country_slug', 'latitude', 'longitude', 'is_default' ),
			'show_in_rest'			=> true,
			'rest_base'     		=> 'locations/cities',
			'rest_controller_class'	=> 'Geodir_Location_REST_Locations_Controller'
		);

		if ( GeoDir_Location_Neighbourhood::is_active() ) {    
			$types['neighbourhood'] = array(
				'type'                  => 'neighbourhood',
				'name'                  => 'neighbourhoods',
				'title'                 => __( 'Neighbourhoods', 'geodirlocation' ),
				'description'           => __( 'All neighbourhoods.', 'geodirlocation' ),
				'rest_base'             => 'locations/neighbourhoods',
				'fields'                => array( 'id', 'title', 'slug', 'latitude', 'longitude', 'meta_title', 'meta_description', 'description', 'location_id', 'city', 'region', 'country', 'city_slug', 'region_slug', 'country_slug' ),
				'show_in_rest'          => true,
				'rest_controller_class' => 'Geodir_Location_REST_Neighbourhoods_Controller'
			);
		}

		return apply_filters( 'geodir_location_rest_get_location_types', $types );
	}

	public static function get_location_options() {
		$options = array();

		$types = self::get_location_types();

		if ( empty( $types ) ) {
			return $options;
		}

		foreach ( $types as $type => $data ) {
			$options[ $type ] = $data['title'];
		}

		return $options;
	}

	public static function get_cpt_options( $plural_name = true, $translated = true ) {
		$options = array();

		$types = geodir_post_type_options( $plural_name, $translated );

		if ( empty( $types ) ) {
			return $options;
		}

		foreach ( $types as $type => $label ) {
			if ( GeoDir_Post_types::supports( $type, 'location' ) ) {
				$options[ $type ] = $label;
			}
		}

		return $options;
	}

	public static function get_tax_options() {
		$options = array();

		$types = self::get_cpt_options( false );

		if ( empty( $types ) ) {
			return $options;
		}

		foreach ( $types as $type => $label ) {
			$options[ $type . 'category' ] = wp_sprintf( __( '%s Categories', 'geodirectory' ), $label );
			$options[ $type . '_tags' ] = wp_sprintf( __( '%s Tags', 'geodirectory' ), $label );
		}

		return $options;
	}

	public static function get_location_type( $type ) {
		$location_types = self::get_location_types();
		
		if ( isset( $location_types[ $type ] ) && ! empty( $location_types[ $type ][ 'show_in_rest' ] ) ) {
			return (object)$location_types[ $type ];
		}
		
		return NULL;
	}

	public static function get_locations( $params = array() ) {
		global $wpdb, $geodirectory;

		if ( ! empty( $params['what'] ) && $params['what'] == 'neighbourhood' ) {
			return self::get_neighbourhoods( $params );
		}

		$defaults = array(
			'fields'           => '*',
			'what'             => 'city',
			'number'           => '',
			'offset'           => '',
			'search'           => '',
			'where'            => '',
			'orderby'          => '',
			'order'            => '',
			'ordertype'        => '',
			'ip'               => '',
			'count'            => false,
			'filter_locations' => false,
			'filter_country'   => '',
			'filter_region'    => '',
			'filter_city'      => ''
		);

		if ( ! in_array( $params['what'], array( 'country', 'region', 'city', 'neighbourhood' ) ) ) {
			$params['what'] = 'city';
		}

		$args = wp_parse_args( $params, $defaults );

		if ( $args['orderby'] == 'ip' ) {
			if ( $geo = geodir_geo_by_ip( $args['ip'] ) ) {
				$args['orderby'] = 'lat_lon';
				$args['latitude'] = $geo['latitude'];
				$args['longitude'] = $geo['longitude'];
			} else {
				$args['orderby'] = 'city';
			}
		}

		if ( $args['orderby'] == 'lat_lon' ) {
			if ( ! empty( $args['latitude'] ) && ! empty( $args['longitude'] ) ) {
				$radius = self::get_distance_radius();
				$args['fields'] = $args['fields'] . ", ( {$radius} * 2 * ASIN( SQRT( POWER( SIN( ( ABS( {$args['latitude']} ) - ABS( latitude ) ) * PI() / 180 / 2 ), 2 ) + COS( ABS( {$args['latitude']} ) * PI() / 180 ) * COS( ABS( latitude ) * PI() / 180 ) * POWER( SIN( ( {$args['longitude']} - longitude ) * PI() / 180 / 2 ), 2 ) ) ) ) AS distance";
				$args['orderby'] = 'distance';
			} else {
				$args['orderby'] = $params['what'];
			}
		}

		$number = absint( $args['number'] );
		$offset = absint( $args['offset'] );

		$limits = '';
		if ( ! empty( $number ) ) {
			if ( $offset ) {
				$limits = 'LIMIT ' . $offset . ', ' . $number;
			} else {
				$limits = 'LIMIT ' . $number;
			}
		}
		
		$where = array();
		if ( !empty( $args['where'] ) ) {
			$where[] = $args['where'];
		}

		if ( $args['filter_locations'] ) {
			$location_default = $geodirectory->location->get_default_location();

			// Filter countries
			if ( $args['filter_country'] == '' ) {
				if ( geodir_get_option( 'lm_default_country' ) == 'default' ) {
					$args['filter_country'] = isset( $location_default->country ) ? $location_default->country : '';
				} else if ( geodir_get_option( 'lm_default_country' ) == 'selected' ) {
					$filter_country = geodir_get_option( 'lm_selected_countries' );

					if ( ! empty( $filter_country ) && is_array( $filter_country ) ) {
						$filter_country = implode( ',' , $filter_country );
					}

					$args['filter_country'] = $filter_country;
				}
			}

			// Filter regions
			if ( $args['filter_region'] == '' ) {
				if ( geodir_get_option( 'lm_default_region' ) == 'default' ) {
					$args['filter_region'] = isset( $location_default->region ) ? $location_default->region : '';
				} else if ( geodir_get_option( 'lm_default_region' ) == 'selected' ) {
					$filter_region = geodir_get_option( 'lm_selected_regions' );

					if ( ! empty( $filter_region ) && is_array( $filter_region ) ) {
						$filter_region = implode( ',' , $filter_region );
					}

					$args['filter_region'] = $filter_region;
				}
			}

			// Filter cities
			if ( $args['filter_city'] == '' ) {
				if ( geodir_get_option( 'lm_default_city' ) == 'default' ) {
					$args['filter_city'] = isset( $location_default->city ) ? $location_default->city : '';
				} else if ( geodir_get_option( 'lm_default_city' ) == 'selected' ) {
					$filter_city = geodir_get_option( 'lm_selected_cities' );

					if ( ! empty( $filter_city ) && is_array( $filter_city ) ) {
						$filter_city = implode( ',' , $filter_city );
					}

					$args['filter_city'] = $filter_city;
				}
			}
		}

		if ( $args['filter_country'] != '' && ( $filter_country = geodir_parse_location_list( $args['filter_country'] ) ) ) {
			$where[] = "LOWER(country) IN( " . $filter_country . " )";
		}

		if ( $args['what'] != 'country' && $args['filter_region'] != '' && ( $filter_region = geodir_parse_location_list( $args['filter_region'] ) ) ) {
			$where[] = "LOWER(region) IN( " . $filter_region . " )";
		}

		if ( $args['what'] != 'country' && $args['what'] != 'region' && $args['filter_city'] != '' && ( $filter_city = geodir_parse_location_list( $args['filter_city'] ) ) ) {
			$where[] = "LOWER(city) IN( " . $filter_city . " )";
		}

		if ( !empty( $args['search'] ) ) {
			$where[] = "{$params['what']} LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( wp_unslash( $args['search'] ) ) ) ) . "%'";
		}
		if ( ! empty( $args['country'] ) ) {
			$where[] = $wpdb->prepare( "country_slug LIKE %s", wp_unslash( $args['country'] ) );
		}
		if ( ! empty( $args['region'] ) ) {
			$where[] = $wpdb->prepare( "region_slug LIKE %s", wp_unslash( $args['region'] ) );
		}
		if ( ! empty( $args['city'] ) ) {
			$where[] = $wpdb->prepare( "city_slug LIKE %s", wp_unslash( $args['city'] ) );
		}
		$where = ! empty( $where ) ? "WHERE " . implode( " AND ", $where ) : '';
		
		$groupby = '';
		switch ( $args['what'] ) {
			case 'city':
				$groupby = '';
				$fields_count = 'COUNT( location_id )';
			break;
			case 'region':
				$groupby = 'region_slug';
				$fields_count = 'COUNT( DISTINCT region_slug )';
			break;
			default:
			case 'country':
				$groupby = 'country_slug';
				$fields_count = 'COUNT( DISTINCT country_slug )';
			break;
		}
		
		$groupby = trim( $groupby ) != '' ? 'GROUP BY ' . $groupby : '';

		$orderby = '';
		if ( ! empty( $args['ordertype'] ) ) {
			$orderby = $args['ordertype'];
		} else if ( ! empty( $args['orderby'] ) ) {
			$orderby = $args['orderby'] . " " . $args['order'];
			if ( $args['orderby'] != 'city' && $args['orderby'] != 'region' && $args['orderby'] != 'country' ) {
				$orderby .= ", {$args['what']} ASC";
			}
		}
		
		$orderby = trim( $orderby ) != '' ? 'ORDER BY ' . $orderby : '';
		
		if ( $args['count'] ) {
			$fields = $fields_count;
			$groupby = '';
			$limits = '';
			$orderby = '';
		} else {
			$fields = $args['fields'] . ', ' . $args['what'] . ' AS title, ' . $args['what'] . '_slug AS slug';
		}
		
		$sql = "SELECT {$fields} FROM " . GEODIR_LOCATIONS_TABLE . " {$where} {$groupby} {$orderby} {$limits}";

		/**
		 * @since 2.0.0.23
		 */
		$sql = apply_filters( 'geodir_location_get_locations_sql', $sql, $args, $params );

		return $args['count'] ? (int)$wpdb->get_var( $sql ) : $wpdb->get_results( $sql );
	}

	public static function get_location( $params = array() ) {
		if ( ! empty( $params['what'] ) && $params['what'] == 'neighbourhood' ) {
			return self::get_neighbourhood( $params );
		}

		$defaults = array(
			'what'          => 'city',
			'country'       => '',
			'region'        => '',
			'city'          => '',
			'neighbourhood' => '',
			'id'            => '',
		);
		
		$args = wp_parse_args( $params, $defaults );
		
		if ( empty( $args[ $args['what'] ] ) ) {
			return NULL;
		}
		
		$where = array();

		if ( $args['what'] == 'city' && ! empty( $args['id'] ) ) {
			$where[] = "location_id = " . (int) $args['id'];
		} else {
			global $wpdb;
			if ( ! empty( $args['country'] ) ) {
				$where[] = $wpdb->prepare( "( country_slug LIKE %s OR country_slug = %s )", wp_unslash( $args['country'] ), wp_unslash( $args['country'] ) );
			}
			
			if ( ! empty( $args['region'] ) ) {
				$where[] = $wpdb->prepare( "( region_slug LIKE %s OR region_slug = %s )", wp_unslash( $args['region'] ), wp_unslash( $args['region'] ) );
			}
			
			if ( ! empty( $args['city'] ) ) {
				$where[] = $wpdb->prepare( "( city_slug LIKE %s OR city_slug = %s )", wp_unslash( $args['city'] ), wp_unslash( $args['city'] ) );
			}
		}
		
		if ( empty( $where ) ) {
			return NULL;
		}
		$where = implode( " AND ", $where );
		
		$rows = self::get_locations( array( 'what' => $args['what'], 'where' => $where, 'orderby' => 'is_default DESC' ) );
		
		if ( ! empty( $rows ) ) {
			return $rows[0];
		}
		
		return NULL;
	}

	public static function get_neighbourhoods( $params = array() ) {
		global $wpdb, $geodirectory;

		$defaults = array(
			'number'           => '',
			'offset'           => '',
			'search'           => '',
			'where'            => '',
			'order'            => '',
			'ordertype'        => '',
			'ip'               => '',
			'count'            => false,
			'orderby'          => 'h.hood_id ASC',
			'filter_locations' => false,
			'filter_country'   => '',
			'filter_region'    => '',
			'filter_city'      => ''
		);

		$args = wp_parse_args( $params, $defaults );

		if ( $args['orderby'] == 'ip' ) {
			if ( $geo = geodir_geo_by_ip( $args['ip'] ) ) {
				$args['orderby'] = 'lat_lon';
				$args['latitude'] = $geo['latitude'];
				$args['longitude'] = $geo['longitude'];
			} else {
				$args['orderby'] = 'h.hood_name ASC';
			}
		}

		$_fields = "";

		if ( $args['orderby'] == 'lat_lon' ) {
			if ( ! empty( $args['latitude'] ) && ! empty( $args['longitude'] ) ) {
				$radius = self::get_distance_radius();
				$_fields .= ", ( {$radius} * 2 * ASIN( SQRT( POWER( SIN( ( ABS( {$args['latitude']} ) - ABS( h.hood_latitude ) ) * PI() / 180 / 2 ), 2 ) + COS( ABS( {$args['latitude']} ) * PI() / 180 ) * COS( ABS( h.hood_latitude ) * PI() / 180 ) * POWER( SIN( ( {$args['longitude']} - h.hood_longitude ) * PI() / 180 / 2 ), 2 ) ) ) ) AS distance";
				$args['orderby'] = 'distance';
			} else {
				$args['orderby'] = 'h.hood_name ASC';
			}
		}

		$number = absint( $args['number'] );
		$offset = absint( $args['offset'] );

		$limits = '';
		if ( ! empty( $number ) ) {
			if ( $offset ) {
				$limits = 'LIMIT ' . $offset . ',' . $number;
			} else {
				$limits = 'LIMIT ' . $number;
			}
		}
		
		$where = array();
		if ( !empty( $args['where'] ) ) {
			$where[] = $args['where'];
		}

		if ( $args['filter_locations'] ) {
			$location_default = $geodirectory->location->get_default_location();

			// Filter countries
			if ( $args['filter_country'] == '' ) {
				if ( geodir_get_option( 'lm_default_country' ) == 'default' ) {
					$args['filter_country'] = isset( $location_default->country ) ? $location_default->country : '';
				} else if ( geodir_get_option( 'lm_default_country' ) == 'selected' ) {
					$filter_country = geodir_get_option( 'lm_selected_countries' );

					if ( ! empty( $filter_country ) && is_array( $filter_country ) ) {
						$filter_country = implode( ',' , $filter_country );
					}

					$args['filter_country'] = $filter_country;
				}
			}

			// Filter regions
			if ( $args['filter_region'] == '' ) {
				if ( geodir_get_option( 'lm_default_region' ) == 'default' ) {
					$args['filter_region'] = isset( $location_default->region ) ? $location_default->region : '';
				} else if ( geodir_get_option( 'lm_default_region' ) == 'selected' ) {
					$filter_region = geodir_get_option( 'lm_selected_regions' );

					if ( ! empty( $filter_region ) && is_array( $filter_region ) ) {
						$filter_region = implode( ',' , $filter_region );
					}

					$args['filter_region'] = $filter_region;
				}
			}

			// Filter cities
			if ( $args['filter_city'] == '' ) {
				if ( geodir_get_option( 'lm_default_city' ) == 'default' ) {
					$args['filter_city'] = isset( $location_default->city ) ? $location_default->city : '';
				} else if ( geodir_get_option( 'lm_default_city' ) == 'selected' ) {
					$filter_city = geodir_get_option( 'lm_selected_cities' );

					if ( ! empty( $filter_city ) && is_array( $filter_city ) ) {
						$filter_city = implode( ',' , $filter_city );
					}

					$args['filter_city'] = $filter_city;
				}
			}
		}

		if ( $args['filter_country'] != '' && ( $filter_country = geodir_parse_location_list( $args['filter_country'] ) ) ) {
			$where[] = "LOWER(l.country) IN( " . $filter_country . " )";
		}

		if ( $args['filter_region'] != '' && ( $filter_region = geodir_parse_location_list( $args['filter_region'] ) ) ) {
			$where[] = "LOWER(l.region) IN( " . $filter_region . " )";
		}

		if ( $args['filter_city'] != '' && ( $filter_city = geodir_parse_location_list( $args['filter_city'] ) ) ) {
			$where[] = "LOWER(l.city) IN( " . $filter_city . " )";
		}

		if ( !empty( $args['search'] ) ) {
			$where[] = "h.hood_name LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( wp_unslash( $args['search'] ) ) ) ) . "%'";
		}
		if ( ! empty( $args['location_id'] ) ) {
			$where[] = "h.hood_location_id = " . absint( $args['location_id'] );
		}
		if ( ! empty( $args['country'] ) ) {
			$where[] = $wpdb->prepare( "l.country_slug LIKE %s", wp_unslash( $args['country'] ) );
		}
		if ( ! empty( $args['region'] ) ) {
			$where[] = $wpdb->prepare( "l.region_slug LIKE %s", wp_unslash( $args['region'] ) );
		}
		if ( ! empty( $args['city'] ) ) {
			$where[] = $wpdb->prepare( "l.city_slug LIKE %s", wp_unslash( $args['city'] ) );
		}
		$where = ! empty( $where ) ? " AND " . implode( " AND ", $where ) : '';

		$orderby = $args['order'] . " " . $args['ordertype'];
		
		if ( !empty( $args['orderby'] ) ) {
			$orderby = $args['orderby'];
		}
		
		$orderby = trim( $orderby ) != '' ? ' ORDER BY ' . sanitize_text_field( $orderby ) : '';
		
		if ( $args['count'] ) {
			$fields = 'COUNT(*)';
			$groupby = '';
			$limits = '';
			$orderby = '';
		} else {
			$fields = 'h.hood_id AS id, h.hood_name AS title, h.hood_slug AS slug, h.hood_latitude AS latitude, h.hood_longitude AS longitude, h.hood_meta_title AS meta_title, h.hood_meta AS meta_description, h.hood_description AS description, l.location_id, l.city, l.city_slug, l.region, l.region_slug, l.country, l.country_slug' . $_fields;
		}
		
		$sql = "SELECT {$fields} FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " AS h LEFT JOIN " . GEODIR_LOCATIONS_TABLE . " AS l ON l.location_id = h.hood_location_id WHERE l.location_id IS NOT NULL {$where} {$orderby} {$limits}";

		return $args['count'] ? (int)$wpdb->get_var( $sql ) : $wpdb->get_results( $sql );
	}

	public static function get_neighbourhood( $params = array() ) {
		$defaults = array(
			'what'			=> 'neighbourhood',
			'country'       => '',
			'region'        => '',
			'city'          => '',
			'neighbourhood' => '',
			'id' 			=> '',
		);
		
		$args = wp_parse_args( $params, $defaults );
		
		$where = '';
		
		if ( empty( $args[ $args['what'] ] ) ) {
			return NULL;
		}
		
		$where = array();
		
		if ( $args['what'] == 'neighbourhood' && ! empty( $args['id'] ) ) {
			$where[] = "hood_id = " . (int) $args['id'];
		} else {
			if ( ! empty( $args['country'] ) ) {
				$where[] = "( l.country_slug LIKE '" . wp_slash( $args['country'] ) . "' OR l.country_slug = '" . wp_slash( $args['country'] ) . "' )";
			}
			
			if ( ! empty( $args['region'] ) ) {
				$where[] = "( l.region_slug LIKE '" . wp_slash( $args['region'] ) . "' OR l.region_slug = '" . wp_slash( $args['region'] ) . "' )";
			}
			
			if ( ! empty( $args['city'] ) ) {
				$where[] = "( l.city_slug LIKE '" . wp_slash( $args['city'] ) . "' OR l.city_slug = '" . wp_slash( $args['city'] ) . "' )";
			}

			if ( ! empty( $args['neighbourhood'] ) ) {
				$where[] = "( h.hood_slug LIKE '" . wp_slash( $args['neighbourhood'] ) . "' OR h.hood_slug = '" . wp_slash( $args['neighbourhood'] ) . "' )";
			}
		}
		
		if ( empty( $where ) ) {
			return NULL;
		}
		$where = implode( " AND ", $where );
		
		$rows = self::get_neighbourhoods( array( 'neighbourhood' => $args[ $args['what'] ], 'where' => $where ) );
		
		if ( ! empty( $rows ) ) {
			return $rows[0];
		}
		
		return NULL;
	}

	public static function rest_posts_clauses_request( $clauses, $wp_query, $post_type ) {
		global $wpdb;

		if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			return $clauses;
		}

		/*
		 * The HAVING clause is often used with the GROUP BY clause to filter groups based on a specified condition.
		 * If the GROUP BY clause is omitted, the HAVING clause behaves like the WHERE clause.
		 */
		if ( strpos( strtoupper( $clauses['where'] ), ' HAVING ') === false && strpos( strtoupper( $clauses['groupby'] ), ' HAVING ') === false && strpos( strtoupper( $clauses['fields'] ), ') AS DISTANCE' ) !== false ) {
			if ( GeoDir_Post_types::supports( $post_type, 'service_distance' ) ) {
				$table = geodir_db_cpt_table( $post_type );
				$having = " HAVING distance <= `{$table}`.`service_distance` ";
			} else {
				$distance = ! empty( $wp_query->query_vars['distance'] ) ? $wp_query->query_vars['distance'] : geodir_get_option( 'search_radius', 7 );
				$having = $wpdb->prepare( " HAVING distance <= %f", (float) $distance );
			}

			if ( trim( $clauses['groupby'] ) != '' ) {
				$clauses['groupby'] .= $having;
			} else {
				$clauses['where'] .= $having;
			}
		}

		return $clauses;
	}

	public static function rest_posts_fields( $fields, $wp_query, $post_type ) {
		$table = geodir_db_cpt_table( $post_type );

		if ( ! empty( $wp_query->query_vars['latitude'] ) && ! empty( $wp_query->query_vars['longitude'] ) ) {
			$latitude = $wp_query->query_vars['latitude'];
			$longitude = $wp_query->query_vars['longitude'];
			$radius = self::get_distance_radius();
			$fields .= ", ( {$radius} * 2 * ASIN( SQRT( POWER( SIN( ( ABS( {$latitude} ) - ABS( {$table}.latitude ) ) * PI() / 180 / 2 ), 2 ) + COS( ABS( {$latitude} ) * PI() / 180 ) * COS( ABS( {$table}.latitude ) * PI() / 180 ) * POWER( SIN( ( {$longitude} - {$table}.longitude ) * PI() / 180 / 2 ), 2 ) ) ) ) AS distance";
		}

		return $fields;
	}

	public static function rest_posts_where( $where, $wp_query, $post_type ) {
		if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			return $where;
		}

		$location_where = geodir_location_posts_where( $post_type, $wp_query );

		if ( ! empty( $location_where ) ) {
			$where .= " AND {$location_where} ";
		}

		return $where;
	}

	public static function rest_taxonomy_query_result( $query_result, $prepared_args, $request ) {
		if ( ! empty( $query_result ) && ! empty( $request['orderby'] ) && $request['orderby'] == 'count' ) {
			$order = ! empty( $request['order'] ) ? strtoupper( $request['order'] ) : 'DESC';
			$query_result = wp_list_sort(
				$query_result,
				array(
					'count' => $order,
					'name' => 'ASC',
				),
				$order
			);
		}
		return $query_result;
	}

	/**
	 * Add translated country name to location response.
	 *
	 * @since 2.0.0.23
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param string $this->location_type Location type.
	 * @param object $location The original location object.
	 * @param WP_REST_Request $request  Request used to generate the response.
	 * @return WP_REST_Response Filtered response object.
	 */
	public static function rest_prepare_location( $response, $location_type, $location, $request ) {
		if ( ! empty( $response->data['country'] ) ) {
			$response->data['country_title'] = __( $response->data['country'], 'geodirectory' );
		} elseif ( $location_type == 'country' && ! empty( $response->data['title'] ) ) {
			$response->data['country'] = $response->data['title'];
			$response->data['title'] = __( $response->data['country'], 'geodirectory' );
		}
		return $response;
	}

	/**
	 * Add translated country name to location response.
	 *
	 * @since 2.0.0.23
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param string $this->location_type Location type.
	 * @param object $location The original location object.
	 * @param WP_REST_Request $request  Request used to generate the response.
	 * @return WP_REST_Response Filtered response object.
	 */
	public static function filter_locations_sql( $sql, $args, $params ) {
		if ( $args['what'] == 'country' && ! empty( $args['search'] ) ) {
			$search = geodir_strtolower( $args['search'] );
			$countries = GeoDir_Location_Country::db_countries();
			$slugs = array();
			foreach ( $countries as $row ) {
				if ( geodir_utf8_strpos( geodir_strtolower( $row->country ), $search ) === false && geodir_utf8_strpos( geodir_strtolower( __( $row->country, 'geodirectory' ) ), $search ) !== false ) {
					$slugs[] = $row->country_slug;
				}
			}

			if ( ! empty( $slugs ) ) {
				$search_where = "country LIKE '%" . wp_slash( $args['search'] ) . "%'";
				$replace_where = "( country LIKE '%" . wp_slash( $args['search'] ) . "%' OR ";
				$replace_where .= count( $slugs ) == 1 ? "country_slug = '{$slugs[0]}'" : "FIND_IN_SET( country_slug, '" . implode( ",", $slugs ) . "' )";
				$replace_where .= " )";

				$sql = str_replace( $search_where, $replace_where, $sql );
			}
		}
		return $sql;
	}

	public static function response_get_post_data( $response, $gd_post, $request, $controller ) {
		if ( isset( $gd_post->distance ) ) {
			$data = array();
			$response['distance'] = self::prepare_distance_response( $gd_post );
		}

		return $response;
	}

	public static function get_distance_radius( $unit = '' ) {
		if ( empty( $unit ) ) {
			$unit = self::get_distance_unit( 'long' );
		}

		return geodir_getDistanceRadius( $unit );
	}

	public static function get_distance_unit( $type = 'long' ) {
		if ( $type != 'short' ) {
			$type = 'long';
		}

		$unit = geodir_get_option( 'search_distance_' . $type );

		if ( empty( $unit ) ) {
			$unit = 'miles';
		}

		return $unit;
	}

	public static function prepare_distance_response( $gd_post ) {
		$distance = $gd_post->distance;

		$response = array(
			'raw' => $distance,
			'rendered' => self::get_display_distance( $distance ) 
		);

		return apply_filters( 'geodir_location_prepare_distance_response', $response, $gd_post );
	}

	public static function get_display_distance( $distance ) {
		$_distance = (float) $distance;
		$unit_long = self::get_distance_unit( 'long' );
		$unit_short = self::get_distance_unit( 'short' );

		if ( $unit_short == 'feet' ) {
			$unit = __( 'feet' , 'geodirectory');
			$multiply = $unit_long == 'km' ? 3280.84 : 5280;
		} else {
			$unit = __( 'meters', 'geodirectory' );
			$multiply = $unit_long == 'km' ? 1000 : 1609.34;
		}

		$distance_ = round( (float) $_distance * $multiply );

		if ( $distance_ < 1000 ) {
			$_distance = $distance_;
		} else {
			if ( $unit_long == 'km' ) {
				$unit = __( 'km', 'geodirectory' );
			} else {
				$unit = __( 'miles', 'geodirectory' );
			}

			$_distance = round( (float) $_distance, 1 );
		}

		$distance =  $_distance . ' ' . $unit;

		return $distance;
	}

	public static function rest_post_sort_options( $options, $post_type ) {
		if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			$options['orderby_options']['distance'] = __( 'Distance', 'geodirlocation' );
		}

		return $options;
	}

	public static function rest_posts_order_sort_by_key( $sort_by, $orderby, $post_type, $wp_query ) {
		if ( ! empty( $wp_query->query_vars['order'] ) && ! empty( $wp_query->query_vars['orderby'] ) && $wp_query->query_vars['orderby'] == 'distance' ) {
			$sort_by = $wp_query->query_vars['orderby'] . '_' . strtolower( $wp_query->query_vars['order'] );
		}

		if ( ( empty( $wp_query->query_vars['latitude'] ) || empty( $wp_query->query_vars['longitude'] ) ) && ( strtolower( $sort_by ) == 'distance' || strtolower( $sort_by ) == 'distance_asc' || strtolower( $sort_by ) == 'distance_desc' ) ) {
			$sort_by = geodir_get_posts_default_sort( $post_type );
		}

		return $sort_by;
	}
}
