<?php
/**
 * REST API: Geodir_Location_REST_Locations_Controller class
 *
 */

/**
 * Core class used to manage locations via the REST API.
 *
 *
 * @see WP_REST_Controller
 */
class Geodir_Location_REST_Locations_Controller extends WP_REST_Controller {

    /**
     * location type.
     *
     * @access protected
     * @var string
     */
    protected $location_type;
    
	/**
     * location type object.
     *
     * @access protected
     * @var string
     */
    protected $location_type_obj;

    /**
     * Object type.
     *
     * @access public
     * @var string
     */
    public $object_type = 'geodir_location';
    
    /**
     * Constructor.
     *
     * @access public
     */
    public function __construct( $location_type ) {

		$this->location_type = $location_type;
		$this->namespace = GEODIR_REST_SLUG . '/v' . GEODIR_REST_API_VERSION;
		$location_type_obj = GeoDir_Location_API::get_location_type( $location_type );
		$this->rest_base = ! empty( $location_type_obj->rest_base ) ? $location_type_obj->rest_base : $location_type_obj->name;
		$this->location_type_obj = $location_type_obj;
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @access public
     *
     * @see register_rest_route()
     */
    public function register_routes() {

        register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<slug>[\w-]+)', array(
            'args' => array(
				'slug' => array(
					'description' => __( 'Unique identifier for the location type.', 'geodirlocation' ),
					'type'        => 'string',
				),
			),
			array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
                'args'            => array(
                    'context'     => $this->get_context_param( array( 'default' => 'view' ) ),
                ),
            ),
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );
    }

    /**
     * Checks whether a given request has permission to read locations.
     *
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_items_permissions_check( $request ) {
        $location_type_obj = GeoDir_Location_API::get_location_type( $this->location_type );
		if ( ! $location_type_obj || ! $this->check_is_location_type_allowed( $this->location_type ) ) {
			return false;
		}
		return true;
    }

    /**
     * Retrieves all public taxonomies.
     *
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function get_items( $request ) {

        // Retrieve the list of registered collection query parameters.
        $registered = $this->get_collection_params();
        
        $parameter_mappings = array(
            'slug'           => 'slug',
            'order'          => 'order',
            'orderby'        => 'orderby',
            'per_page'       => 'number',
            'search'         => 'search',
            'country'        => 'country',
            'country_title'  => 'country',
            'region'         => 'region',
            'city'           => 'city',
            'ip'             => 'ip',
            'latitude'       => 'latitude',
            'longitude'      => 'longitude',
        );
        
        $prepared_args          = array();
        $prepared_args['what']  = $this->location_type;

        foreach ( $parameter_mappings as $api_param => $wp_param ) {
            if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
                $prepared_args[ $wp_param ] = $request[ $api_param ];
            }
        }

        if ( isset( $registered['offset'] ) && ! empty( $request['offset'] ) ) {
            $prepared_args['offset'] = $request['offset'];
        } else {
            $prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['number'];
        }
        
        $prepared_args = apply_filters( "geodir_location_rest_location_type_{$this->location_type}_query", $prepared_args, $request );

        $query_result = GeoDir_Location_API::get_locations( $prepared_args );

        $count_args             = $prepared_args;
        $count_args['count']    = true;

        $total_items = GeoDir_Location_API::get_locations( $count_args );

        if ( ! $total_items ) {
            $total_items = 0;
        }

        $response = array();

        foreach ( $query_result as $item ) {
            $data = $this->prepare_item_for_response( $item, $request );
            $response[] = $this->prepare_response_for_collection( $data );
        }

        $response = rest_ensure_response( $response );

        // Store pagination values for headers.
        $per_page = (int) $prepared_args['number'];
        $page     = ceil( ( ( (int) $prepared_args['offset'] ) / $per_page ) + 1 );

        $response->header( 'X-WP-Total', (int) $total_items );

        $max_pages = ceil( $total_items / $per_page );

        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        $base = add_query_arg( $request->get_query_params(), rest_url( $this->namespace . '/' . $this->rest_base ) );
        if ( $page > 1 ) {
            $prev_page = $page - 1;

            if ( $prev_page > $max_pages ) {
                $prev_page = $max_pages;
            }

            $prev_link = add_query_arg( 'page', $prev_page, $base );
            $response->link_header( 'prev', $prev_link );
        }
        if ( $max_pages > $page ) {
            $next_page = $page + 1;
            $next_link = add_query_arg( 'page', $next_page, $base );

            $response->link_header( 'next', $next_link );
        }

        return $response;
    }

    /**
     * Checks if a given request has access to a country.
     *
     * @access public
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access for the item, otherwise false or WP_Error object.
     */
    public function get_item_permissions_check( $request ) {
		$location_type_obj = $this->location_type;

		if ( empty( $location_type_obj ) ) {
			return new WP_Error( 'rest_location_type_invalid', __( 'Invalid location type.', 'geodirlocation' ), array( 'status' => 404 ) );
		}

        if ( ! $this->check_is_location_type_allowed( $this->location_type ) ) {
            return new WP_Error( 'rest_cannot_view', __( 'Sorry, you are not allowed to view location.', 'geodirlocation' ), array( 'status' => rest_authorization_required_code() ) );
        }

        return true;
    }

    /**
     * Retrieves a specific country.
     *
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item( $request ) {        
		$query_vars         = array();
		$query_vars['what'] = $this->location_type;
		
		if ( isset( $request['country'] ) ) {
			$query_vars['country'] = $request['country'];
		}
		if ( isset( $request['region'] ) ) {
			$query_vars['region'] = $request['region'];
		}
		if ( isset( $request['city'] ) ) {
			$query_vars['city'] = $request['city'];
		}

		if ( $this->location_type == 'city' && !empty( $request['slug'] ) && absint( $request['slug'] ) > 0 ) {
			$query_vars['id'] = absint( $request['slug'] );
		}
		
		$query_vars[ $this->location_type ] = $request['slug'];

		$location = GeoDir_Location_API::get_location( $query_vars );
        
        if ( empty( $location ) ) {
            return new WP_Error( 'rest_location_slug_invalid', __( 'Invalid location slug.', 'geodirlocation' ), array( 'status' => 404 ) );
        }
        
        $data = $this->prepare_item_for_response( $location, $request );
        
        return rest_ensure_response( $data );
    }

    /**
     * Prepares a location object for serialization.
     *
     * @access public
     *
     * @param stdClass        $location Location data.
     * @param WP_REST_Request $request  Full details about the request.
     * @return WP_REST_Response Response object.
     */
    public function prepare_item_for_response( $location, $request ) {
        $schema = $this->get_item_schema();

		$data = array();
        
        $parameter_mappings = array(
            'location_id'   => 'id',
            'city_latitude' => 'latitude',
            'city_longitude'=> 'longitude',
        );

        foreach ( $parameter_mappings as $db_field => $wp_field ) {
            if ( isset( $location->{$db_field} ) && !isset( $location->{$wp_field} ) ) {
                $location->{$wp_field} = $location->{$db_field};
				unset($location->{$db_field});
            }
        }
        
        foreach ( $this->location_type_obj->fields as $field ) {
            if ( isset( $location->{$field} ) ) {
                $data[ $field ] = $location->{$field};
            }
        }
		if ( isset( $location->distance ) ) {
			$data[ 'distance' ] = $location->distance;
		}
        
        $slug       = isset( $data['slug'] ) ? $data['slug'] : '';
        $context    = 'view';
        $data       = $this->add_additional_fields_to_object( $data, $request );
        $data       = $this->filter_response_by_context( $data, $context );

        // Wrap the data in a response object.
        $response = rest_ensure_response( $data );

        $links = array();
        $links['self'] = array();
        $links['collection'] = array( 'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );
        
        if ( !empty( $slug ) ) {
            $self_url = rest_url( sprintf( '%s/%s/%s', $this->namespace, $this->rest_base, $slug ) );
            $add_query_args = array();
            
            if ( ! in_array( $this->location_type, array( 'country' ) ) && !empty( $data['country_slug'] ) ) {
                $add_query_args['country'] = $data['country_slug'];
                $self_url = add_query_arg( array( 'country' => $data['country_slug'] ), $self_url );
            }
            
            if ( ! in_array( $this->location_type, array( 'country', 'region' ) ) && !empty( $data['region_slug'] ) ) {
                $add_query_args['region'] = $data['region_slug'];
                $self_url = add_query_arg( array( 'region' => $data['region_slug'] ), $self_url );
            }
            
            if ( ! in_array( $this->location_type, array( 'country', 'region', 'city' ) ) && !empty( $data['city_slug'] ) ) {
                $add_query_args['city'] = $data['city_slug'];
                $self_url = add_query_arg( array( 'city' => $data['city_slug'] ), $self_url );
            }
            
            $add_query_args[ $this->location_type ] = $slug;
            
            $links['self'] = array( 'href' => $self_url );
            
            if ( $this->location_type == 'city' ) {
                $links['info'] = array( 'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $data['id'] ) ) );
            }
            
            global $wp_post_types, $wp_taxonomies;
            
            $gd_post_types = geodir_get_posttypes();

            $post_type_links    = array();
            $taxonomies_links   = array();
            
            foreach ( $wp_post_types as $post_type ) {
                if ( in_array( $post_type->name, $gd_post_types ) && GeoDir_Post_types::supports( $post_type->name, 'location' ) ) {
                    if ( !empty( $post_type->show_in_rest ) ) {
                        $post_type_url = rest_url( sprintf( '%s/%s', $this->namespace, $post_type->rest_base ) );
                        
                        if ( !empty( $add_query_args ) ) {
                            foreach ( $add_query_args as $arg => $value ) {
                                $post_type_url = add_query_arg( array( $arg => $value ), $post_type_url );
                            }
                        }
                        
                        $post_type_links[] = array(
                            'href'       => $post_type_url,
                            'post_type'  => $post_type->name,
                            'embeddable' => true,
                        );
                    }
                                        
                    if ( !empty( $post_type->taxonomies ) ) {
                        foreach ( $post_type->taxonomies as $taxonomy ) {
                            if ( !empty( $wp_taxonomies[$taxonomy]->show_in_rest ) ) {
                                $taxonomy_url = rest_url( sprintf( '%s/%s', $this->namespace, $wp_taxonomies[$taxonomy]->rest_base ) );
                        
                                if ( !empty( $add_query_args ) ) {
                                    foreach ( $add_query_args as $arg => $value ) {
                                        $taxonomy_url = add_query_arg( array( $arg => $value ), $taxonomy_url );
                                    }
                                }
                        
                                $taxonomies_links[] = array(
                                    'href'       => $taxonomy_url,
                                    'taxonomy'   => $taxonomy,
                                    'embeddable' => true,
                                );
                            }
                        }
                    }
                }
            }
            
            if ( !empty( $post_type_links ) ) {
                $links['https://api.w.org/post_type'] = $post_type_links;
            }
            
            if ( !empty( $taxonomies_links ) ) {
                $links['https://api.w.org/term'] = $taxonomies_links;
            }
        }
        
        $response->add_links( $links );

        /**
         * Filters a location returned from the REST API.
         *
         * @param WP_REST_Response $response The response object.
         * @param string           $this->location_type Location type.
         * @param object           $location The original location object.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters( 'geodir_location_rest_prepare_location', $response, $this->location_type, $location, $request );
    }

    /**
     * Retrieves the country's schema, conforming to JSON Schema.
     *
     * @access public
     *
     * @return array Item schema data.
     */
    public function get_item_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/schema#',
            'title'                => $this->object_type,
            'type'                 => 'object',
            'properties'           => array(
                'id'               => array(
                    'description'  => __( 'A human-readable description of the country.', 'geodirlocation' ),
                    'type'         => 'integer',
                    'context'      => array( 'view' ),
                ),
                'name'             => array(
                    'description'  => __( 'The name for the country.', 'geodirlocation' ),
                    'type'         => 'string',
                    'context'      => array( 'view' ),
                ),
                'title'             => array(
                    'description'  => __( 'The translated name for the country.', 'geodirlocation' ),
                    'type'         => 'string',
                    'context'      => array( 'view' ),
                ),
                'iso2'             => array(
                    'description'  => __( 'The ISO2 code for the country.', 'geodirlocation' ),
                    'type'         => 'string',
                    'context'      => array( 'view' ),
                )
            ),
        );
        return $this->add_additional_fields_schema( $schema );
    }

    /**
     * Retrieves the query params for collections.
     *
     * @access public
     *
     * @return array Collection parameters.
     */
    public function get_collection_params() {
        $query_params = parent::get_collection_params();
        $query_params['context'][ 'default'] = 'view';

        $query_params['country'] = array(
            'description'       => __( 'Country slug.', 'geodirlocation' ),
            'type'              => 'string',
            'default'           => '',
        );

		$query_params['region'] = array(
            'description'       => __( 'Region slug.', 'geodirlocation' ),
            'type'              => 'string',
            'default'           => '',
        );

		$query_params['city'] = array(
            'description'       => __( 'City slug.', 'geodirlocation' ),
            'type'              => 'string',
            'default'           => '',
        );

		$query_params['ip'] = array(
            'description'       => __( 'IP to find nearest locations.', 'geodirlocation' ),
            'type'              => 'string',
            'default'           => '',
        );

		$query_params['latitude'] = array(
            'description'       => __( 'Latitude', 'geodirlocation' ),
            'type'              => 'string',
            'default'           => '',
        );

		$query_params['longitude'] = array(
            'description'       => __( 'Longitude', 'geodirlocation' ),
            'type'              => 'string',
            'default'           => '',
        );

		$query_params['order'] = array(
            'description'       => __( 'Order sort attribute ascending or descending.', 'geodirlocation' ),
            'type'              => 'string',
            'default'           => 'asc',
            'enum'              => array(
                'asc',
                'desc',
            ),
        );

        $query_params['orderby'] = array(
            'description'       => __( 'Sort collection by location attribute.', 'geodirlocation' ),
            'type'              => 'string',
            'default'           => 'city',
            'enum'              => array(
                'id',
                'country',
                'region',
                'city',
                'is_default',
                'ip',
                'lat_lon'
            ),
        );
        
        return $query_params;
    }
    
    public function show_in_rest() {
        return true;
    }

	protected function check_is_location_type_allowed( $location_type ) {
		$location_type_obj = GeoDir_Location_API::get_location_type( $location_type );
		if ( $location_type_obj && ! empty( $location_type_obj->show_in_rest ) ) {
			return true;
		}
		return false;
	}

}
