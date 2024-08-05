<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_Country.
 *
 * @class    GeoDir_Location_Country
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_Country {

	public static function total() {
		global $wpdb;

		$total = $wpdb->get_var( "SELECT COUNT( DISTINCT country_slug ) FROM " . GEODIR_LOCATIONS_TABLE );

		return $total;
	}

	/**
	 * Get location count for a country.
	 *
	 * @since 2.0.0
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 * @global string $plugin_prefix Geodirectory plugin table prefix.
	 *
	 * @param string $country Country name.
	 * @return int Listing count.
	 */
	public static function count_posts_by_name( $country ) {
		global $wpdb, $plugin_prefix;

		$count = 0;
		if ( ! empty( $country ) ) {
			$post_types = geodir_get_posttypes();
			$statuses = array_keys( geodir_get_post_statuses() );

			foreach( $post_types as $post_type ) {
				if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					continue;
				}
				$table = geodir_db_cpt_table( $post_type );

				$where = array( "country LIKE %s" );
				$params = array( $country );

				if ( ! empty( $statuses ) ) {
					$where[] = "post_status IN( '" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "')";
				}

				$where = implode( " AND ", $where );

				$count += (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT( post_id ) FROM " . $table . " WHERE " . $where, $params ) );
			}
		}
		return $count;
	}

	public static function get_info_by_slug( $country_slug ) {
		$info = geodir_get_location_by_slug( 'country', 
			array( 
				'country_slug' => $country_slug,
				'fields' => 'country, country_slug, is_default'
			) 
		);

		return $info;
	}

	public static function get_info_by_name( $name ) {
		global $wpdb;

		if ( empty( $name ) ) {
			return NULL;
		}

		// Load from cache.
		$cache = wp_cache_get( "geodir_location_country_get_info_by_name_" . $name );
		if ( $cache !== false ) {
			return $cache;
		}

		$where = array( "country LIKE %s" );
		$args = array( $name );

		$where = implode( " AND ", $where );

		$info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " WHERE {$where} ORDER BY location_id ASC LIMIT 1", $args ) );

		// Set cache.
		wp_cache_set( "geodir_location_country_get_info_by_name_" . $name, $info );

		return $info;
	}

	public static function get_name_by_slug( $country_slug, $translated = false ) {
		$location = geodir_get_location_by_slug( 'country', array( 'country_slug' => $country_slug ) );

		$country = '';
		if ( ! empty( $location->country ) ) {
			$country = $location->country;

			if ( $translated ) {
				$country = __( $country, 'geodirectory' );
			}
		}
		return $country;
	}
	
	/**
	 * Update location with translated string.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 * @global string $plugin_prefix Geodirectory plugin table prefix.
	 *
	 * @param $country_slug
	 * @return bool
	 */
	public static function translate( $country_slug ) {
		global $wpdb, $plugin_prefix,$geodirectory;

		if ( empty( $country_slug ) ) {
			return false;
		}

		$country = self::get_name_by_slug( $country_slug );
		if ( empty( $country ) ) {
			return false;
		}

		$country = $geodirectory->location->validate_country_name( $country, '0' );

		if ( empty( $country ) ) {
			return false;
		}

		$post_types = geodir_get_posttypes();

		$country_translated = __( $country, 'geodirectory' );
		$country_translated = trim( wp_unslash( $country_translated ) );
		$country_slug_translated = geodir_create_location_slug( $country_translated );

		$country_slug = apply_filters( 'geodir_filter_update_location_translate', $country_slug, $country, $country_translated, $country_slug_translated );
		
		if ( $country_slug == $country_slug_translated && $country == $country_translated ) {
			return false;
		}

		do_action( 'geodir_action_update_location_translate', $country_slug, $country, $country_translated, $country_slug_translated );

		// Update locations
		$sql = $wpdb->prepare( "UPDATE " . GEODIR_LOCATIONS_TABLE . " SET country = %s, country_slug = %s WHERE country_slug LIKE %s", array( $country, $country_slug_translated, $country_slug ) );
		$update_locations = $wpdb->query( $sql );

		// Update location seo
		$sql = $wpdb->prepare( "UPDATE " . GEODIR_LOCATION_SEO_TABLE . " SET country_slug = %s WHERE country_slug LIKE %s", array( $country_slug_translated, $country_slug ) );
		$update_location_seo = $wpdb->query( $sql );
		
		// Update location term meta
		$sql = $wpdb->prepare( "UPDATE " . GEODIR_LOCATION_TERM_META . " SET country_slug = %s WHERE country_slug LIKE %s", array( $country_slug_translated, $country_slug ) );
		$update_term_meta = $wpdb->query( $sql );

		if ( $update_locations || $update_location_seo || $update_term_meta ) {
			do_action( 'geodir_location_country_slug_updated', $country_slug_translated, $country_slug, $country, $country_translated );
			
			return true;
		}
		return false;
	}

	/**
	 * Get countries from location table.
	 */
	public static function db_countries() {
		global $wpdb;

		$db_countries = wp_cache_get( 'geodir_location_get_db_countries' );
		if ( ! empty( $db_countries ) ) {
			return $db_countries;
		}

		$db_countries = $wpdb->get_results( "SELECT * FROM " . GEODIR_LOCATIONS_TABLE . " GROUP BY country_slug ORDER BY country ASC" );

		wp_cache_set( 'geodir_location_get_db_countries', $db_countries );

		return $db_countries;
	}
}

return new GeoDir_Location_Country();