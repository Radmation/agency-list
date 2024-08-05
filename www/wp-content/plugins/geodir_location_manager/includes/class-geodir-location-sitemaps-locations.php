<?php
/**
 * GeoDir_Location_Sitemaps_Locations class
 *
 * Builds the sitemaps for the 'country, region, city, neighbourhood'.
 *
 * @package GeoDir_Location_Manager
 * @since 2.0.1.4
 */

/**
 * Locations XML sitemap provider.
 *
 * @since 2.0.1.4
 */
class GeoDir_Location_Sitemaps_Locations extends WP_Sitemaps_Provider {
	/**
	 * GeoDir_Location_Sitemaps_Locations constructor.
	 *
	 * @since 2.0.1.4
	 */
	public function __construct() {
		$this->name        = 'geodirlocations';
		$this->object_type = 'location';
	}

	/**
	 * Get the location types.
	 *
	 * @since 2.0.1.4
	 *
	 * @return Array of location types.
	 */
	public function get_object_subtypes() {
		$options = geodir_get_option( 'location_sitemaps_locations' );

		if ( empty( $options ) || ! is_array( $options ) ) {
			return array();
		}

		// Location types.
		$location_types = GeoDir_Location_API::get_location_types();

		foreach ( $location_types as $type => $data ) {
			if ( ! in_array( $type, $options ) ) {
				unset( $location_types[ $type ] );
			}
		}

		if ( isset( $location_types['country'] ) && geodir_get_option( 'lm_default_country' ) == 'default' && geodir_get_option( 'lm_hide_country_part' ) ) {
			unset( $location_types['country'] );
		}

		if ( isset( $location_types['region'] ) && geodir_get_option( 'lm_default_region' ) == 'default' && geodir_get_option( 'lm_hide_region_part' ) ) {
			unset( $location_types['region'] );
		}

		if ( isset( $location_types['neighbourhood'] ) && ! GeoDir_Location_Neighbourhood::is_active() ) {
			unset( $location_types['neighbourhood'] );
		}

		/**
		 * Filters the list of location types.
		 *
		 * @since 2.0.1.4
		 *
		 * @param array $location_types Array of location types.
		 */
		return apply_filters( 'geodir_location_sitemaps_locations_types', $location_types );
	}

	/**
	 * Gets a URL list for a location sitemap.
	 *
	 * @since 2.0.1.4
	 *
	 * @param int    $page_num  Page of results.
	 * @param string $location_type Optional. Location type name. Default empty.
	 * @return array Array of URLs for a sitemap.
	 */
	public function get_url_list( $page_num, $location_type = '' ) {
		// Bail early if the queried post type is not supported.
		$supported_types = $this->get_object_subtypes();

		if ( empty( $supported_types ) ) {
			return array();
		}

		if ( ! isset( $supported_types[ $location_type ] ) ) {
			return array();
		}

		/**
		 * Filters the locations URL list before it is generated.
		 *
		 * @since 2.0.1.4
		 *
		 * @param array  $url_list  The URL list. Default null.
		 * @param string $location_type Location type name.
		 * @param int    $page_num  Page of results.
		 */
		$url_list = apply_filters( 'geodir_location_sitemaps_locations_pre_url_list', null, $location_type, $page_num );

		if ( null !== $url_list ) {
			return $url_list;
		}

		// Get the entries.
		$entries = $this->get_entries( $location_type, $page_num );

		$url_list = array();

		if ( ! empty( $entries ) ) {
			foreach ( $entries as $location ) {
				$sitemap_entry = array(
					'loc' => $this->get_entry_url( $location, $location_type ),
				);

				/**
				 * Filters the sitemap entry for an individual location.
				 *
				 * @since 2.0.1.4
				 *
				 * @param array  $sitemap_entry Sitemap entry for the location.
				 * @param object $location Location object.
				 * @param string $location_type Name of the location type.
				 */
				$sitemap_entry = apply_filters( 'geodir_location_sitemaps_locations_entry', $sitemap_entry, $location, $location_type );

				$url_list[] = $sitemap_entry;
			}
		}

		return $url_list;
	}

	/**
	 * Get the location sitemap URL.
	 *
	 * @since 2.0.1.4
	 *
	 * @param object $location Location object.
	 * @param string $location_type Name of the location type.
	 * @rerurn string Location url.
	 */
	public function get_entry_url( $location, $location_type ) {
		$args = array();

		if ( in_array( $location_type, array( 'country', 'region', 'city', 'neighbourhood' ) ) && isset( $location->country_slug ) ) {
			$args['gd_country'] = $location->country_slug;
		}

		if ( in_array( $location_type, array( 'region', 'city', 'neighbourhood' ) ) && isset( $location->region_slug ) ) {
			$args['gd_region'] = $location->region_slug;
		}

		if ( in_array( $location_type, array( 'city', 'neighbourhood' ) ) && isset( $location->city_slug ) ) {
			$args['gd_city'] = $location->city_slug;
		}

		if ( in_array( $location_type, array( 'neighbourhood' ) ) && isset( $location->slug ) ) {
			$args['gd_neighbourhood'] = $location->slug;
		}

		if ( $location_type != 'country' && geodir_get_option( 'lm_default_country' ) == 'default' && geodir_get_option( 'lm_hide_country_part' ) && isset( $args['gd_country'] ) ) {
			unset( $args['gd_country'] );
		}

		if ( geodir_get_option( 'lm_default_region' ) == 'default' && geodir_get_option( 'lm_hide_region_part' ) && isset( $args['gd_region'] ) ) {
			unset( $location_types['gd_region'] );
		}

		return geodir_location_get_url( $args, get_option( 'permalink_structure' ) );
	}

	/**
	 * Get the location entries.
	 *
	 * @since 2.0.1.4
	 *
	 * @param string $location_type Location type name.
	 * @param int $page_num Page number.
	 * @param array Array of locations.
	 */
	public function get_entries( $location_type, $page_num ) {
		if ( empty( $location_type ) ) {
			return array();
		}

		$args = $this->get_locations_query_args( $location_type, $page_num );

		$locations = GeoDir_Location_API::get_locations( $args );

		/**
		 * Filters the location entries.
		 *
		 * @since 2.0.1.4
		 *
		 * @param array  $locations The array of locations.
		 * @param string $location_type Location type name.
		 */
		return apply_filters( 'geodir_location_sitemaps_locations_entries', $locations, $location_type );
	}

	/**
	 * Gets the max number of pages available for the object type.
	 *
	 * @since 2.0.1.4
	 *
	 * @param string $location_type Optional. Location type name. Default empty.
	 * @return int Total number of pages.
	 */
	public function get_max_num_pages( $location_type = '' ) {
		if ( empty( $location_type ) ) {
			return 0;
		}

		/**
		 * Filters the max number of pages before it is generated.
		 *
		 * @since 2.0.1.4
		 *
		 * @param int|null $max_num_pages The maximum number of pages. Default null.
		 * @param string   $location_type Location type name.
		 */
		$max_num_pages = apply_filters( 'geodir_location_sitemaps_locations_pre_max_num_pages', null, $location_type );

		if ( null !== $max_num_pages ) {
			return $max_num_pages;
		}

		// Get total number of entries.
		$total_entries = $this->get_total_entries( $location_type );

		$pages = (int) ceil( $total_entries / wp_sitemaps_get_max_urls( $this->object_type ) );

		return $pages;
	}

	/**
	 * Get the total number of entries for the location type.
	 *
	 * @since 2.0.1.4
	 *
	 * @param string $location_type Location type name.
	 * @return int Total entries.
	 */
	public function get_total_entries( $location_type = '' ) {
		if ( empty( $location_type ) ) {
			return 0;
		}

		$args = array(
			'what' => $location_type,
			'filter_locations' => true,
			'count' => true
		);

		$result = GeoDir_Location_API::get_locations( $args );

		$total = ! empty( $result ) ? $result : 0;

		/**
		 * Filters the total number of entries.
		 *
		 * @since 2.0.1.4
		 *
		 * @param int    $total The number of entries.
		 * @param string $location_type Location type name.
		 */
		return apply_filters( 'geodir_location_sitemaps_locations_total_entries', $total, $location_type );
	}

	/**
	 * Returns the query args for retrieving locations to list in the sitemap.
	 *
	 * @since 2.0.1.4
	 *
	 * @param string $location_type Location type name.
	 * @param int $page_num Page number.
	 * @return array Array of Location arguments.
	 */
	protected function get_locations_query_args( $location_type, $page_num ) {
		$args = array();
		$args['what']  = $location_type;
		$args['filter_locations'] = true;
		$args['number'] = wp_sitemaps_get_max_urls( $this->object_type );
		$args['offset'] = ( $page_num - 1 ) * $args['number'];
		if ( $location_type == 'neighbourhood' ) {
			$args['orderby'] = 'hood_name ASC';
		} else {
			$args['ordertype'] = $location_type . ' ASC';
		}

		/**
		 * Filters the query arguments for locations sitemap queries.
		 *
		 * @since 2.0.1.4
		 *
		 * @param array  $args Array of location arguments.
		 * @param string $location_type Location type name.
		 * @param int $page_num Page number.
		 */
		return apply_filters( 'geodir_location_sitemaps_locations_query_args', $args, $location_type, $page_num );
	}
}
