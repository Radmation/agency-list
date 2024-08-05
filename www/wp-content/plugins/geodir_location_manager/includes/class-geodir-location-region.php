<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_Region.
 *
 * @class    GeoDir_Location_Region
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_Region {

	public static function total() {
		global $wpdb;

		$total = $wpdb->get_var( "SELECT COUNT( DISTINCT CONCAT( country_slug, region_slug ) ) FROM " . GEODIR_LOCATIONS_TABLE );

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
	 * @param string $region Region name.
	 * @param string $country Country name.
	 * @return int Listing count.
	 */
	public static function count_posts_by_name( $region, $country = '' ) {
		global $wpdb, $plugin_prefix;

		$count = 0;
		if ( ! empty( $region ) ) {
			$post_types = geodir_get_posttypes();
			$statuses = array_keys( geodir_get_post_statuses() );

			foreach( $post_types as $post_type ) {
				if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					continue;
				}
				$table = geodir_db_cpt_table( $post_type );

				$where = array( "region LIKE %s" );
				$params = array( $region );

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

	public static function get_info_by_slug( $region_slug, $country_slug = '' ) {
		$info = geodir_get_location_by_slug( 'region', 
			array( 
				'region_slug' => $region_slug, 
				'country_slug' => $country_slug,
				'fields' => 'region, region_slug, country, country_slug, is_default'
			) 
		);

		return $info;
	}

	public static function get_info_by_name( $name, $country = '' ) {
		global $wpdb;

		if ( empty( $name ) ) {
			return NULL;
		}

		// Load from cache.
		$cache = wp_cache_get( "geodir_location_region_get_info_by_name_" . $name . ":" . $country );
		if ( $cache !== false ) {
			return $cache;
		}

		$where = array( "region LIKE %s" );
		$args = array( $name );

		if ( ! empty( $country ) ) {
			$where[] = "country LIKE %s";
			$args[] = $country;
		}

		$where = implode( " AND ", $where );

		$info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE {$where} ORDER BY location_id ASC LIMIT 1", $args ) );

		// Set cache.
		wp_cache_set( "geodir_location_region_get_info_by_name_" . $name . ":" . $country, $info );

		return $info;
	}

	public static function get_name_by_slug( $region_slug, $country_slug = '' ) {
		$location = geodir_get_location_by_slug( 'region', array( 'region_slug' => $region_slug, 'country_slug' => $country_slug ) );

		$region = '';
		if ( ! empty( $location->region ) ) {
			$region = $location->region;
		}
		return $region;
	}

	public static function get_slug_by_name( $region = '', $country = '' ) {
		global $wpdb;

		if ( empty( $region ) ) {
			return NULL;
		}

		// check cache
		$cache = wp_cache_get("geodir_location_region_get_slug_by_name_".sanitize_title_with_dashes($region.$country));
		if($cache !== false){
			return $cache;
		}

		$where = array( "region = %s" );
		$args = array( $region );

		if ( ! empty( $country ) ) {
			$where[] = "country = %s";
			$args[] = $country;
		}
		

		$where = implode( " AND ", $where );

		$city = $wpdb->get_var( $wpdb->prepare( "SELECT region_slug FROM " . GEODIR_LOCATIONS_TABLE . " WHERE {$where} ORDER BY location_id ASC LIMIT 1", $args ) );

		// set cache
		wp_cache_set("geodir_location_region_get_slug_by_name_".sanitize_title_with_dashes($region.$country), $city );

		return $city;
	}

	public static function has_duplicate_slugs() {
		global $wpdb;

		$total = (int) $wpdb->get_var( "SELECT COUNT( DISTINCT country_slug ) AS total FROM `" . GEODIR_LOCATIONS_TABLE . "` GROUP BY `region_slug` ORDER BY total DESC LIMIT 1" );

		return ( $total > 1 ? true : false );
	}
}

return new GeoDir_Location_Region();