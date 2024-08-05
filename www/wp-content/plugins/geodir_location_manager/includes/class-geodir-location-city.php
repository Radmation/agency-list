<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_City.
 *
 * @class    GeoDir_Location_City
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_City {

	public static function total() {
		global $wpdb;

		$total = $wpdb->get_var( "SELECT COUNT( location_id ) FROM " . GEODIR_LOCATIONS_TABLE );

		return $total;
	}

	/**
	 * Get location count for a region.
	 *
	 * @since 2.0.0
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 * @global string $plugin_prefix Geodirectory plugin table prefix.
	 *
	 * @param string $city City name.
	 * @param string $region Region name.
	 * @param string $country Country name.
	 * @return int Listing count.
	 */
	public static function count_posts_by_name( $city, $region = '', $country = '' ) {
		global $wpdb, $plugin_prefix;

		$count = 0;
		if ( ! empty( $city ) ) {
			$post_types = geodir_get_posttypes();
			$statuses = array_keys( geodir_get_post_statuses() );

			foreach( $post_types as $post_type ) {
				if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					continue;
				}
				$table = geodir_db_cpt_table( $post_type );

				$where = array( "city LIKE %s" );
				$params = array( $city );

				if ( ! empty( $region ) ) {
					$where[] = "region LIKE %s";
					$params[] = $region;
				}

				if ( ! empty( $country ) ) {
					$where[] = "country LIKE %s";
					$params[] = $country;
				}

				if ( ! empty( $statuses ) ) {
					$where[] = "post_status IN( '" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "')";
				}
				
				$where = implode( " AND ", $where );

				$count += (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT( post_id ) FROM " . $table . " WHERE " . $where, $params ) );
			}
		}
		return $count;
	}

	public static function get_info_by_id( $id ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return NULL;
		}

		$sql = $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE location_id = %d LIMIT 1", array( $id ) );
		$location = $wpdb->get_row( $sql );

		return $location;
	}

	/**
	 * Get location city information using location slug.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $slug Location slug.
	 * @param string $country_slug Country slug.
	 * @param string $region_slug Region slug.
	 * @return mixed|null
	 */
	public static function get_info_by_slug( $slug, $country_slug = '', $region_slug = '' ) {
		global $wpdb;

		if ( empty( $slug ) ) {
			return NULL;
		}

		// Load from cache.
		$cache = wp_cache_get( "geodir_location_city_get_info_by_slug_" . $slug . ":" . $country_slug . ":" . $region_slug );
		if ( $cache !== false ) {
			return $cache;
		}

		$where = array( "city_slug = %s" );
		$args = array( $slug );

		if ( ! empty( $country_slug ) ) {
			$where[] = "country_slug = %s";
			$args[] = $country_slug;
		}

		if ( ! empty( $region_slug ) ) {
			$where[] = "region_slug = %s";
			$args[] = $region_slug;
		}

		$where = implode( " AND ", $where );

		$city = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE {$where} ORDER BY location_id ASC LIMIT 1", $args ) );

		// Set cache.
		wp_cache_set( "geodir_location_city_get_info_by_slug_" . $slug . ":" . $country_slug . ":" . $region_slug, $city );

		return $city;
	}

	public static function get_info_by_name( $name, $country = '', $region = '' ) {
		global $wpdb;

		if ( empty( $name ) ) {
			return NULL;
		}

		// Load from cache.
		$cache = wp_cache_get( "geodir_location_city_get_info_by_name_" . $name . ":" . $country . ":" . $region );
		if ( $cache !== false ) {
			return $cache;
		}

		$where = array( "city LIKE %s" );
		$args = array( $name );

		if ( ! empty( $country ) ) {
			$where[] = "country LIKE %s";
			$args[] = $country;
		}

		if ( ! empty( $region ) ) {
			$where[] = "region LIKE %s";
			$args[] = $region;
		}

		$where = implode( " AND ", $where );

		$info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE {$where} ORDER BY location_id ASC LIMIT 1", $args ) );

		// Set cache.
		wp_cache_set( "geodir_location_city_get_info_by_name_" . $name . ":" . $country . ":" . $region, $info );

		return $info;
	}

	public static function get_items_by_ids( $ids ) {
		global $wpdb;

		if ( empty( $ids ) ) {
			return NULL;
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}
		$ids = array_filter( array_map( 'absint', $ids ) );
		$ids = implode( "','", $ids );

		$items = $wpdb->get_results( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE location_id IN('{$ids}') ORDER BY city ASC" );

		return $items;
	}
	
	public static function get_slug_by_name( $city, $region = '', $country = '' ) {
		global $wpdb;

		if ( empty( $city ) ) {
			return NULL;
		}

		// check cache
		$cache = wp_cache_get("geodir_location_city_get_slug_by_name_".sanitize_title_with_dashes($city.$region.$country));
		if($cache !== false){
			return $cache;
		}

		$where = array( "city = %s" );
		$args = array( $city );

		if ( ! empty( $country ) ) {
			$where[] = "country = %s";
			$args[] = $country;
		}

		if ( ! empty( $region ) ) {
			$where[] = "region = %s";
			$args[] = $region;
		}

		$where = implode( " AND ", $where );

		$city = $wpdb->get_var( $wpdb->prepare( "SELECT city_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE {$where} ORDER BY location_id ASC LIMIT 1", $args ) );

		// set cache
		wp_cache_set("geodir_location_city_get_slug_by_name_".sanitize_title_with_dashes($city.$region.$country), $city );
		
		return $city;
	}

	public static function delete_location( $location ) {
		global $wpdb;

		$location = is_int( $location ) ? GeoDir_Location_City::get_info_by_id( $location ) : $location;
		if ( empty( $location->location_id ) ) {
			return false;
		}

		// Don't allow to delete default location.
		if ( ! empty( $location->is_default ) ) {
			return false;
		}

		/**
		 * Filters whether a deletion should take place.
		 *
		 * @since 2.0.0
		 *
		 * @param bool    $delete       Whether to go forward with deletion.
		 * @param object  $location     Location object.
		 */
		$check = apply_filters( 'geodir_location_pre_delete_location', null, $location );
		if ( null !== $check ) {
			return $check;
		}

		/**
		 * Fires before a location is deleted.
		 *
		 * @since 2.0.0
		 *
		 * @param object $location Location object.
		 */
		do_action( 'geodir_location_before_delete_location', $location );

		// Delete location
		$return = $wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_LOCATIONS_TABLE . " WHERE location_id = %d", array( $location->location_id ) ) );
		if ( ! $return ) {
			return false;
		}

		/**
		 * Fires after a location is deleted.
		 *
		 * @since 2.0.0
		 *
		 * @param object $location Location object.
		 */
		do_action( 'geodir_location_after_delete_location', $location );

		return true;
	}

	public static function on_location_deleted( $location ) {
		global $wpdb;

		if ( empty( $location->country ) || empty( $location->region ) || empty( $location->city ) ) {
			return false;
		}

		$post_types = geodir_get_posttypes();
		if ( empty( $post_types ) ) {
			return false;
		}

		foreach( $post_types as $key => $post_type ) {
			if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				continue;
			}

			$table = geodir_db_cpt_table( $post_type );

			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$table} WHERE country LIKE %s AND region LIKE %s AND city LIKE %s", array( $location->country, $location->region, $location->city ) ) );
			
			if ( ! empty( $rows ) ) {
				foreach ( $rows as $row ) {
					wp_delete_post( $row->post_id ); // Delete post
				}
			}
		}

		return true;
	}

	public static function has_duplicate_slugs() {
		global $wpdb;

		$total = (int) $wpdb->get_var( "SELECT COUNT( * ) AS `total` FROM `" . GEODIR_LOCATIONS_TABLE . "` GROUP BY `city_slug` ORDER BY `total` DESC LIMIT 1" );

		return ( $total > 1 ? true : false );
	}

	/**
	 * Merge post locations for post type.
	 *
	 * @since 2.1.0.6
	 *
	 * @return int No. of locations merged.
	 */
	public static function merge_post_locations( $post_type ) {
		global $wpdb;

		$merged = 0;
		if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			return $merged;
		}

		if ( apply_filters( 'geodir_location_skip_merge_post_locations', false, $post_type ) ) {
			return $merged;
		}

		$table = geodir_db_cpt_table( $post_type );

		$results = $wpdb->get_results( "SELECT `pd`.`country`, `pd`.`region`, `pd`.`city`, `pd`.`latitude`, `pd`.`longitude` FROM `{$table}` AS `pd` LEFT JOIN `" . GEODIR_LOCATIONS_TABLE . "` AS `l` ON ( `l`.`country` = `pd`.`country` AND `l`.`region` = `pd`.`region` AND `l`.`city` = `pd`.`city` ) WHERE `l`.`location_id` IS NULL GROUP BY `pd`.`country`, `pd`.`region`, `pd`.`city`" );

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				if ( geodir_location_insert_city( (array) $row ) ) {
					$merged++;
				}
			}
		}

		return $merged;
	}
}

return new GeoDir_Location_City();