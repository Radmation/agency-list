<?php
/**
 * The sitemap provider for locations.
 *
 * @since      2.1.1.2
 * @package    GeoDir_Location_Manager
 * @author     AyeCode
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post type provider class.
 */
class GeoDir_Location_Rank_Math_Sitemap implements RankMath\Sitemap\Providers\Provider {

	/**
	 * The constructor.
	 */
	public function __construct() {

	}

	/**
	 * Check if provider supports given location type.
	 *
	 * @param string $type Location type.
	 *
	 * @return boolean
	 */
	public function handles_type( $type ) {
		global $geodir_location_options;

		GeoDir_Location_SEO::setup_global_options();

		$sitemap_type = self::parse_type( $type );

		if ( ! empty( $sitemap_type ) && ! empty( $geodir_location_options ) ) {
			if ( $sitemap_type['location'] == 'country' && $geodir_location_options['option_country'] == 'default' && $geodir_location_options['hide_country'] ) {
				return false;
			} else if ( $sitemap_type['location'] == 'region' && $geodir_location_options['option_region'] == 'default' && $geodir_location_options['hide_region'] ) {
				return false;
			} else if ( $sitemap_type['location'] == 'neighbourhood' && ! GeoDir_Location_Neighbourhood::is_active() ) {
				return false;
			}
		}
		/**
		 * Filter decision if location is excluded from the XML sitemap.
		 *
		 * @param bool   $exclude Default false.
		 * @param string $type    Location type.
		 * @param array $sitemap_type Parsed sitemap type.
		 */
		return ! apply_filters( 'geodir_location_rankmath_sitemap_exclude', false, $type, $sitemap_type );
	}

	/**
	 * Get set of sitemaps index link data.
	 *
	 * @param int $max_entries Entries per sitemap.
	 *
	 * @return array
	 */
	public function get_index_links( $max_entries ) {
		global $cpt_items, $tax_items;

		$sitemap_types = GeoDir_Location_SEO::rank_math_sitemap_types();
		$types = ! empty( $sitemap_types ) ? array_filter( array_keys( $sitemap_types ), array( $this, 'handles_type' ) ) : array();
		$last_modified_times = self::get_last_modified_gmt( $types, true );
		$last_modified_gmt = self::get_last_modified_gmt( $types, false );
		$index = array();

		if ( empty( $types ) ) {
			return $index;
		}

		// Location Types
		foreach ( $types as $type ) {
			$total_count = $this->get_location_count( $sitemap_types[ $type ] );
			if ( 0 === $total_count ) {
				continue;
			}

			$max_pages = 1;
			if ( $total_count > $max_entries ) {
				$max_pages = (int) ceil( $total_count / $max_entries );
			}

			for ( $page_counter = 0; $page_counter < $max_pages; $page_counter++ ) {
				$current_page = ( $max_pages > 1 ) ? ( $page_counter + 1 ) : '';
				$date         = $last_modified_gmt;

				if ( ! empty( $last_modified_times[ $type ] ) ) {
					$date = $last_modified_times[ $type ];
				}

				$index[] = [
					'loc'     => RankMath\Sitemap\Router::get_base_url( $type . '-sitemap' . $current_page . '.xml' ),
					'lastmod' => $date,
				];
			}
		}

		// Post Types
		$sitemap_cpts = GeoDir_Location_SEO::rank_math_sitemap_cpts();
		$cpts = ! empty( $sitemap_cpts ) ? array_filter( array_keys( $sitemap_cpts ), array( $this, 'handles_type' ) ) : array();

		if ( ! empty( $cpts ) ) {
			foreach ( $cpts as $cpt ) {
				$total_count = $this->get_cpt_count( $cpt );
				if ( 0 === $total_count ) {
					continue;
				}

				$max_pages = 1;
				if ( $total_count > $max_entries ) {
					$max_pages = (int) ceil( $total_count / $max_entries );
				}

				for ( $page_counter = 0; $page_counter < $max_pages; $page_counter++ ) {
					$current_page = ( $max_pages > 1 ) ? ( $page_counter + 1 ) : '';
					$item_index = $current_page > 0 ? ( $current_page - 1 ) * $max_entries : 0;

					if ( ! empty( $cpt_items ) && isset( $cpt_items[ $item_index ] ) ) {
						$date = $cpt_items[ $item_index ]->date_gmt;
					} else if ( ! empty( $last_modified_times[ $cpt ] ) ) {
						$date = $last_modified_times[ $cpt ];
					} else {
						$date = $last_modified_gmt;
					}

					$index[] = [
						'loc'     => RankMath\Sitemap\Router::get_base_url( $cpt . '-sitemap' . $current_page . '.xml' ),
						'lastmod' => $date,
					];
				}
			}
		}

		// Taxonomies
		$sitemap_taxs = GeoDir_Location_SEO::rank_math_sitemap_tax();
		$taxs = ! empty( $sitemap_taxs ) ? array_filter( array_keys( $sitemap_taxs ), array( $this, 'handles_type' ) ) : array();

		if ( ! empty( $taxs ) ) {
			foreach ( $taxs as $tax ) {
				$total_count = $this->get_tax_count( $tax );
				if ( 0 === $total_count ) {
					continue;
				}

				$max_pages = 1;
				if ( $total_count > $max_entries ) {
					$max_pages = (int) ceil( $total_count / $max_entries );
				}

				for ( $page_counter = 0; $page_counter < $max_pages; $page_counter++ ) {
					$current_page = ( $max_pages > 1 ) ? ( $page_counter + 1 ) : '';
					$item_index = $current_page > 0 ? ( $current_page - 1 ) * $max_entries : 0;

					if ( ! empty( $tax_items ) && isset( $tax_items[ $item_index ] ) ) {
						$date = $tax_items[ $item_index ]->date_gmt;
					} else if ( ! empty( $last_modified_times[ $tax ] ) ) {
						$date = $last_modified_times[ $tax ];
					} else {
						$date = $last_modified_gmt;
					}

					$index[] = [
						'loc'     => RankMath\Sitemap\Router::get_base_url( $tax . '-sitemap' . $current_page . '.xml' ),
						'lastmod' => $date,
					];
				}
			}
		}

		return $index;
	}

	/**
	 * Get list of sitemap link data.
	 *
	 * @param string $type         Sitemap type.
	 * @param int    $max_entries  Entries per sitemap.
	 * @param int    $current_page Current page of the sitemap.
	 *
	 * @return array
	 */
	public function get_sitemap_links( $type, $max_entries, $current_page ) {
		$sitemap_type = self::parse_type( $type );

		if ( empty( $sitemap_type ) ) {
			return array();
		}

		if ( $sitemap_type['type'] == 'cpt' ) {
			return self::get_sitemap_cpt_links( $type, $sitemap_type['object'], $sitemap_type['location'], $max_entries, $current_page );
		} else if ( $sitemap_type['type'] == 'tax' ) {
			return self::get_sitemap_tax_links( $type, $sitemap_type['object'], $sitemap_type['location'], $max_entries, $current_page );
		} else {
			return self::get_sitemap_location_links( $type, $sitemap_type['location'], $max_entries, $current_page );
		}
	}

	/**
	 * Get list of sitemap locations link data.
	 *
	 * @param string $type         Sitemap type.
	 * @param int    $max_entries  Entries per sitemap.
	 * @param int    $current_page Current page of the sitemap.
	 *
	 * @return array
	 */
	public function get_sitemap_location_links( $type, $location_type, $max_entries, $current_page ) {
		$links = array();

		$steps     = $max_entries;
		$offset    = ( $current_page > 1 ) ? ( ( $current_page - 1 ) * $max_entries ) : 0;
		$total     = ( $offset + $max_entries );
		$typecount = $this->get_location_count( $location_type );

		if ( $total > $typecount ) {
			$total = $typecount;
		}

		if ( 1 === $current_page && ( $_links = $this->get_first_links( $type, $location_type ) ) ) {
			$links = array_merge( $links, $_links );
		}

		if ( 0 === $typecount ) {
			return $links;
		}

		while ( $total > $offset ) {
			$items   = $this->get_items( $type, $steps, $current_page );
			$offset += $steps;

			if ( empty( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				$url = $this->get_url( $item, $type );
				if ( ! isset( $url['loc'] ) ) {
					continue;
				}

				$url = apply_filters( 'geodir_location_rankmath_sitemap_location_entry', $url, $type, $location_type, $item );

				if ( empty( $url ) ) {
					continue;
				}

				$links[] = $url;
			}

			unset( $item, $url );
		}

		return $links;
	}

	/**
	 * Get list of sitemap link data.
	 *
	 * @param string $type         Sitemap type.
	 * @param string $cpt          Post type.
	 * @param string $location_type Location type.
	 * @param int    $max_entries  Entries per sitemap.
	 * @param int    $current_page Current page of the sitemap.
	 *
	 * @return array
	 */
	public function get_sitemap_cpt_links( $type, $cpt, $location_type, $max_entries, $current_page ) {
		global $cpt_items;

		$items = $this->get_cpt_items( $type, $max_entries, $current_page );
		$links = array();

		if ( 1 === $current_page && ( $_links = $this->get_first_links( $type, $location_type ) ) ) {
			$links = array_merge( $links, $_links );
		}

		if ( empty( $items ) ) {
			return $links;
		}

		foreach ( $items as $item ) {
			$url = $this->get_cpt_url( $item, $type );
			if ( ! isset( $url['loc'] ) ) {
				continue;
			}

			$url = apply_filters( 'geodir_location_rankmath_sitemap_cpt_entry', $url, $type, $cpt, $item );

			if ( empty( $url ) ) {
				continue;
			}

			$links[] = $url;
		}

		unset( $item, $url );

		return $links;
	}

	/**
	 * Get list of sitemap link data.
	 *
	 * @param string $type         Sitemap type.
	 * @param string $tax          Taxonomy.
	 * @param string $location_type Location type.
	 * @param int    $max_entries  Entries per sitemap.
	 * @param int    $current_page Current page of the sitemap.
	 *
	 * @return array
	 */
	public function get_sitemap_tax_links( $type, $tax, $location_type, $max_entries, $current_page ) {
		$items = $this->get_tax_items( $type, $max_entries, $current_page );
		$links = array();

		if ( empty( $items ) ) {
			return $links;
		}

		foreach ( $items as $item ) {
			$url = $this->get_tax_url( $item, $type );
			if ( ! isset( $url['loc'] ) ) {
				continue;
			}

			$url = apply_filters( 'geodir_location_rankmath_sitemap_tax_entry', $url, $type, $tax, $item );

			if ( empty( $url ) ) {
				continue;
			}

			$links[] = $url;
		}

		unset( $item, $url );

		return $links;
	}

	/**
	 * Get count of items for location type.
	 *
	 * @param string $type Location type.
	 *
	 * @return int
	 */
	protected function get_location_count( $type ) {
		$args = array(
			'what' => $type,
			'filter_locations' => true,
			'count' => true
		);

		$result = GeoDir_Location_API::get_locations( $args );

		$count = ! empty( $result ) ? (int) $result : 0;

		return $count;
	}

	/**
	 * Get count of items for post type.
	 *
	 * @param string $type Sitemap type.
	 *
	 * @return int
	 */
	protected function get_cpt_count( $type ) {
		global $cpt_items;

		$count = 0;
		$sitemap_type = self::parse_type( $type );

		if ( empty( $sitemap_type ) ) {
			return $count;
		}

		$items = GeoDir_Location_SEO::get_sitemap_cpt_locations( $sitemap_type['location'], $sitemap_type['object'] );
		$cpt_items = $items;

		$count = ! empty( $items ) ? count( $items ) : 0;

		return $count;
	}

	/**
	 * Get items for post type.
	 *
	 * @param string $type Sitemap type.
	 * @param int|null $per_page Per page.
	 * @param int|null $page Current page offset.
	 *
	 * @return int
	 */
	protected function get_cpt_items( $type, $per_page = 0, $page = null ) {
		$items = array();
		$sitemap_type = self::parse_type( $type );

		if ( empty( $sitemap_type ) ) {
			return $items;
		}

		$items = GeoDir_Location_SEO::get_sitemap_cpt_locations( $sitemap_type['location'], $sitemap_type['object'], $per_page, $page );

		return $items;
	}

	/**
	 * Get count of items for taxonomy.
	 *
	 * @param string $type Sitemap type.
	 *
	 * @return int
	 */
	protected function get_tax_count( $type ) {
		global $tax_items;

		$count = 0;
		$sitemap_type = self::parse_type( $type );

		if ( empty( $sitemap_type ) ) {
			return $count;
		}

		$taxonomy = $sitemap_type['object'];
		$taxonomy_type = '';
		$post_type = '';
		if ( substr( $taxonomy, -8 ) == 'category' ) {
			$taxonomy_type = 'category';
			$post_type = substr( $taxonomy, 0, -8 );
		} else if ( substr( $taxonomy, -5 ) == '_tags' ) {
			$taxonomy_type = 'tag';
			$post_type = substr( $taxonomy, 0, -5 );
		} else {
			return $count;
		}

		$items = GeoDir_Location_SEO::get_sitemap_tax_locations( $sitemap_type['location'], $post_type, $taxonomy_type );
		$tax_items = $items;

		$count = ! empty( $items ) ? count( $items ) : 0;

		return $count;
	}

	/**
	 * Get count of items for taxonomy.
	 *
	 * @param string $type Sitemap type.
	 * @param int|null $per_page Per page.
	 * @param int|null $page Current page offset.
	 *
	 * @return int
	 */
	protected function get_tax_items( $type, $per_page = 0, $page = null ) {
		$items = array();
		$sitemap_type = self::parse_type( $type );

		if ( empty( $sitemap_type ) ) {
			return $items;
		}

		$taxonomy = $sitemap_type['object'];
		$taxonomy_type = '';
		$post_type = '';
		if ( substr( $taxonomy, -8 ) == 'category' ) {
			$taxonomy_type = 'category';
			$post_type = substr( $taxonomy, 0, -8 );
		} else if ( substr( $taxonomy, -5 ) == '_tags' ) {
			$taxonomy_type = 'tag';
			$post_type = substr( $taxonomy, 0, -5 );
		} else {
			return $items;
		}

		$items = GeoDir_Location_SEO::get_sitemap_tax_locations( $sitemap_type['location'], $post_type, $taxonomy_type, $per_page, $page );

		return $items;
	}

	/**
	 * Produces set of links to prepend at start of first sitemap page.
	 *
	 * @param string $type Sitemap type.
	 * @param string $location_type Location type.
	 *
	 * @return array
	 */
	protected function get_first_links( $type, $location_type ) {
		$links = array();

		if ( $type == 'gd_location_city' ) {
			$base_url = geodir_get_location_link( 'base' );

			$links[] = array(
				'loc' => $base_url,
				'mod' => self::get_last_modified_gmt( $type )
			);
		}

		return $links;
	}

	/**
	 * Retrieve list of items with optimized query routine.
	 *
	 * @param array $type   Location type.
	 * @param int   $count  Count items.
	 * $param int   $page_num  Current page
	 * @param int   $offset Starting offset.
	 *
	 * @return object[]
	 */
	protected function get_items( $type, $per_page, $page_num ) {
		$args = self::get_locations_query_args( $type, $per_page, (int) $page_num );

		$items = GeoDir_Location_API::get_locations( $args );

		return $items;
	}

	/**
	 * Produce array of URL parts for given location object.
	 *
	 * @param object $item Location object.
	 * @param sting $type Location type.
	 *
	 * @return array|boolean
	 */
	protected function get_url( $item, $type ) {
		$_type = self::get_type( $type );

		$args = array();

		if ( in_array( $_type, array( 'country', 'region', 'city', 'neighbourhood' ) ) && isset( $item->country_slug ) ) {
			$args['gd_country'] = $item->country_slug;
		}

		if ( in_array( $_type, array( 'region', 'city', 'neighbourhood' ) ) && isset( $item->region_slug ) ) {
			$args['gd_region'] = $item->region_slug;
		}

		if ( in_array( $_type, array( 'city', 'neighbourhood' ) ) && isset( $item->city_slug ) ) {
			$args['gd_city'] = $item->city_slug;
		}

		if ( in_array( $_type, array( 'neighbourhood' ) ) && isset( $item->slug ) ) {
			$args['gd_neighbourhood'] = $item->slug;
		}

		$url = array();
		$url['loc'] = geodir_location_get_url( $args );
		$url['mod'] = self::get_last_modified_gmt( $type );

		return $url;
	}

	/**
	 * Produce array of URL parts for given cpt object.
	 *
	 * @param object $item cpt object.
	 * @param sting $type Location type.
	 *
	 * @return array|boolean
	 */
	protected function get_cpt_url( $item, $type ) {
		global $wp, $geodirectory;

		$_type = self::parse_type( $type );

		$country = ! empty( $item->country ) ? $item->country : '';
		$region = ! empty( $item->region ) ? $item->region : '';
		$city = ! empty( $item->city ) ? $item->city : '';
		$neighbourhood = ! empty( $item->neighbourhood ) ? $item->neighbourhood : '';

		$args = array();
		if ( in_array( $_type['location'], array( 'country', 'region', 'city', 'neighbourhood' ) ) ) {
			$args['country'] = $country;

			if ( in_array( $_type['location'], array( 'region', 'city', 'neighbourhood' ) ) ) {
				$args['region'] = $region;

				if ( in_array( $_type['location'], array( 'city', 'neighbourhood' ) ) ) {
					$args['city'] = $city;

					if ( $_type['location'] == 'neighbourhood' ) {
						$args['neighbourhood'] = $neighbourhood;
					}
				}
			}
		}

		$cpt_link = GeoDir_Location_SEO::get_cpt_url( $_type['object'], $args );

		$url = array();
		if ( $cpt_link ) {
			$url['loc'] = $cpt_link;
			$url['mod'] = $item->date_gmt;
		}

		return $url;
	}

	/**
	 * Produce array of URL parts for given taxonomy object.
	 *
	 * @param object $item tax object.
	 * @param sting $type Location type.
	 *
	 * @return array|boolean
	 */
	protected function get_tax_url( $item, $type ) {
		global $wp, $geodirectory;

		$_type = self::parse_type( $type );

		$country = ! empty( $item->country ) ? $item->country : '';
		$region = ! empty( $item->region ) ? $item->region : '';
		$city = ! empty( $item->city ) ? $item->city : '';
		$neighbourhood = ! empty( $item->neighbourhood ) ? $item->neighbourhood : '';

		$args = array();
		if ( in_array( $_type['location'], array( 'country', 'region', 'city', 'neighbourhood' ) ) ) {
			$args['country'] = $country;

			if ( in_array( $_type['location'], array( 'region', 'city', 'neighbourhood' ) ) ) {
				$args['region'] = $region;

				if ( in_array( $_type['location'], array( 'city', 'neighbourhood' ) ) ) {
					$args['city'] = $city;

					if ( $_type['location'] == 'neighbourhood' ) {
						$args['neighbourhood'] = $neighbourhood;
					}
				}
			}
		}

		$term_link = GeoDir_Location_SEO::get_term_url( $item->term_id, $_type['object'], $args );

		$url = array();
		if ( $term_link ) {
			$url['loc'] = $term_link;
			$url['mod'] = $item->date_gmt;
		}

		return $url;
	}

	/**
	 * Get the last modified date.
	 *
	 * @param  string|array $types Location type or array of types.
	 * @param  boolean      $return_all Flag to return array of values.
	 * @return string|array|false
	 */
	public static function get_last_modified_gmt( $types, $return_all = false ) {
		global $wpdb;

		if ( empty( $types ) ) {
			return false;
		}

		static $location_type_dates = null;
		if ( ! is_array( $types ) ) {
			$types = array( $types );
		}

		foreach ( $types as $type ) {
			if ( ! isset( $location_type_dates[ $type ] ) ) {
				$location_type_dates = null;
				break;
			}
		}

		if ( is_null( $location_type_dates ) ) {
			$post_types = self::get_accessible_post_types();
			$last_modified_times = RankMath\Sitemap\Sitemap::get_last_modified_gmt( $post_types, false );

			$location_type_dates = array();

			foreach ( $types as $type ) {
				$location_type_dates[ $type ] = $last_modified_times;
			}
		}

		$dates = array_intersect_key( $location_type_dates, array_flip( $types ) );
		if ( count( $dates ) > 0 ) {
			return $return_all ? $dates : max( $dates );
		}

		return false;
	}

	public static function get_accessible_post_types() {
		$post_types = array();

		$_post_types = RankMath\Helper::get_accessible_post_types();
		
		if ( empty( $_post_types ) ) {
			return $post_types;
		}

		foreach ( $_post_types as $post_type ) {
			if ( geodir_is_gd_post_type( $post_type ) && GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				$post_types[] = $post_type;
			}
		}

		return $post_types;
	}

	/**
	 * Returns the query args for retrieving locations to list in the sitemap.
	 *
	 * @since 2.1.1.2
	 *
	 * @param string $type Location type name.
	 * @param int $per_page Items per page.
	 * @param int $page_num Page number.
	 * @return array Array of Location arguments.
	 */
	public static function get_locations_query_args( $type, $per_page, $page_num ) {
		$type = self::get_type( $type );

		$args = array();
		$args['what']  = $type;
		$args['filter_locations'] = true;
		$args['number'] = $per_page;
		$args['offset'] = ( $page_num - 1 ) * $args['number'];

		$fields = array();

		if ( in_array( $type, array( 'country', 'region', 'city', 'neighbourhood' ) ) ) {
			$fields[] = 'country_slug';
		}

		if ( in_array( $type, array( 'region', 'city', 'neighbourhood' ) ) ) {
			$fields[] = 'region_slug';
		}

		if ( in_array( $type, array( 'city', 'neighbourhood' ) ) ) {
			$fields[] = 'city_slug';
		}

		if ( in_array( $type, array( 'neighbourhood' ) ) ) {
			$fields[] = 'slug';
		}

		if ( ! empty( $fields ) ) {
			$args['fields'] = implode( ", ", $fields );
		}

		if ( $type == 'neighbourhood' ) {
			$args['orderby'] = 'hood_name ASC';
		} else {
			$args['ordertype'] = $type . ' ASC';
		}

		/**
		 * Filters the query arguments for locations sitemap queries.
		 *
		 * @since 2.1.1.2
		 *
		 * @param array  $args Array of location arguments.
		 * @param string $type Location type name.
		 * @param int $page_num Page number.
		 */
		return apply_filters( 'geodir_location_sitemaps_locations_query_args', $args, $type, $page_num );
	}

	public static function get_type( $type ) {
		if ( strpos( $type, 'gd_location' ) === 0 ) {
			$type = str_replace( 'gd_location_', '', $type ); 
		}

		return $type;
	}

	public function parse_type( $sitemap_type ) {
		$sitemap_types = GeoDir_Location_SEO::rank_math_sitemap_types();

		if ( ! empty( $sitemap_types ) && isset( $sitemap_types[ $sitemap_type ] ) ) {
			return array( 'type' => 'location', 'object' => 'location', 'location' => $sitemap_types[ $sitemap_type ] );
		}

		$sitemap_types = GeoDir_Location_SEO::rank_math_sitemap_cpts();

		if ( ! empty( $sitemap_types ) && isset( $sitemap_types[ $sitemap_type ] ) ) {
			$location = $sitemap_types[ $sitemap_type ];
			return array( 'type' => 'cpt', 'object' => substr( $sitemap_type, 0, ( strlen( $sitemap_type ) - strlen( $location ) - 1 ) ), 'location' => $location );
		}

		$sitemap_types = GeoDir_Location_SEO::rank_math_sitemap_tax();

		if ( ! empty( $sitemap_types ) && isset( $sitemap_types[ $sitemap_type ] ) ) {
			$location = $sitemap_types[ $sitemap_type ];
			return array( 'type' => 'tax', 'object' => substr( $sitemap_type, 0, ( strlen( $sitemap_type ) - strlen( $location ) - 1 ) ), 'location' => $location );
		}

		return array();
	}
}
