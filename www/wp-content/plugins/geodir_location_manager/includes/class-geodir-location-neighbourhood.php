<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_Neighbourhood.
 *
 * @class    GeoDir_Location_Neighbourhood
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_Neighbourhood {

	public static function total() {
		global $wpdb;

		$total = $wpdb->get_var( "SELECT COUNT( hood_id ) FROM " . GEODIR_NEIGHBOURHOODS_TABLE );

		return $total;
	}

	public static function is_active() {
		return geodir_get_option( 'lm_enable_neighbourhoods' );
	}
	/**
	 * Get location count for a neighbourhood.
	 *
	 * @since 2.0.0
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 * @global string $plugin_prefix Geodirectory plugin table prefix.
	 *
	 * @param string $neighbourhood Neighbourhood name.
	 * @return int Listing count.
	 */
	public static function count_posts_by_slug( $neighbourhood ) {
		global $wpdb, $plugin_prefix;

		$count = 0;
		if ( ! empty( $neighbourhood ) ) {
			$post_types = geodir_get_posttypes();
			$statuses = array_keys( geodir_get_post_statuses() );

			foreach( $post_types as $post_type ) {
				if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					continue;
				}
				$table = geodir_db_cpt_table( $post_type );

				$where = array( "neighbourhood LIKE %s" );
				$params = array( $neighbourhood );

				if ( ! empty( $statuses ) ) {
					$where[] = "post_status IN( '" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "')";
				}

				$where = implode( " AND ", $where );

				$count += (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT( post_id ) FROM " . $table . " WHERE " . $where, $params ) );
			}
		}
		return $count;
	}

	public static function save_data( $data, $neighbourhood_id = 0 ) {
		global $wpdb;
		
		if ( empty( $data['location_id'] ) || empty( $data['name'] ) || empty( $data['latitude'] ) || empty( $data['longitude'] ) ) {
			return false;
		}

		$neighbourhood =  !empty( $neighbourhood_id ) ? self::get_info_by_id( $neighbourhood_id ) : NULL;
		if ( ! empty( $neighbourhood_id ) && empty( $neighbourhood ) ) {
			return false;
		}

		$slug = '';
		if ( ! empty( $neighbourhood ) ) {
			if ( empty( $neighbourhood->slug ) ) {
				$slug = ! empty( $data['slug'] ) ? $data['slug'] : $data['name'];
			} else {
				if ( ! empty( $data['slug'] ) ) {
					$slug = $data['slug'];
				}
			}
		} else {
			$slug = ! empty( $data['slug'] ) ? $data['slug'] : $data['name'];
		}
		if ( $slug ) {
			$slug = self::unique_slug( $slug, $neighbourhood_id );
		}

		$save_data 						= array();
		$save_data['hood_location_id'] 	= $data['location_id'];
		$save_data['hood_name'] 		= $data['name'];
		$save_data['hood_latitude'] 	= $data['latitude'];
		$save_data['hood_longitude'] 	= $data['longitude'];
		if ( $slug ) {
			$save_data['hood_slug'] 	= $slug;
		}

		if ( isset( $data['meta_title'] ) ) {
			$save_data['hood_meta_title'] = ! empty( $data['meta_title'] ) ? geodir_utf8_substr( $data['meta_title'], 0, 140 ) : '';
		}
		if ( isset( $data['meta_desc'] ) ) {
			$save_data['hood_meta'] = ! empty( $data['meta_desc'] ) ? geodir_utf8_substr( $data['meta_desc'], 0, 140 ) : '';
		}
		if ( isset( $data['description'] ) ) {
			$save_data['hood_description'] = ! empty( $data['description'] ) ? geodir_utf8_substr( $data['description'], 0, 102400 ) : '';
		}
		if ( isset( $data['image'] ) ) {
			$save_data['image'] = $data['image'];
		}
		if ( isset( $data['cpt_desc'] ) ) {
			$save_data['cpt_desc'] = is_array( $data['cpt_desc'] ) && ! empty( $data['cpt_desc'] ) ? json_encode( $data['cpt_desc'] ) : $data['cpt_desc'];
		}

		$neighbourhood_id = 0;
		if ( !empty( $neighbourhood ) ) {
			if ( $wpdb->update( GEODIR_NEIGHBOURHOODS_TABLE, $save_data, array( 'hood_id' => (int)$neighbourhood->id ) ) ) {
				$neighbourhood_id = $neighbourhood->id;
			}
		} else {
			if ( $wpdb->insert( GEODIR_NEIGHBOURHOODS_TABLE, $save_data ) ) {
				$neighbourhood_id = $wpdb->insert_id;
			}
		}

		if ( $neighbourhood_id ) {
			do_action( 'geodir_location_neighbourhood_saved', $neighbourhood_id, $save_data );
		}

		return $neighbourhood_id;
	}

	/**
	 * Get the neighbourhood slug for name.
	 *
	 * @since 1.4.5
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $name Neighbourhood name.
	 * @param int $hood_id Neighbourhood id. Default 0.
	 * @return string Neighbourhood slug.
	 */
	public static function unique_slug( $name, $hood_id = 0 ) {
		global $wpdb;

		$slug = geodir_create_location_slug( $name );

		if ( (int)$hood_id > 0 ) {
			$check_sql = "SELECT hood_slug FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE hood_slug = %s AND hood_id != %d LIMIT 1";
			$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $hood_id ) );
		} else {
			$check_sql = "SELECT hood_slug FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE hood_slug = %s LIMIT 1";
			$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug ) );
		}

		if ( $slug_check ) {
			$suffix = 1;
			do {
				$alt_slug = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				
				if ( (int)$hood_id > 0 )
					$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug, $hood_id ) );
				else
					$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug ) );
				
				$suffix++;
			} while ( $slug_check );
			
			$slug = $alt_slug;
		}

		return $slug;
	}
	
	/**
	 * Get neighbourhood info by id.
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param int $id Neighbourhood id.
	 * @return array|mixed
	 */
	public static function get_info_by_id( $id ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return NULL;
		}

		$sql = $wpdb->prepare( "SELECT h.hood_id AS id, h.hood_name AS neighbourhood, h.hood_slug AS slug, h.hood_latitude AS latitude, h.hood_longitude AS longitude, h.hood_meta_title AS meta_title, h.hood_meta AS meta_description, h.hood_description AS description, h.image, h.cpt_desc, l.location_id, l.city, l.city_slug, l.region, l.region_slug, l.country, l.country_slug FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " AS h INNER JOIN " . GEODIR_LOCATIONS_TABLE . " AS l ON l.location_id = h.hood_location_id WHERE h.hood_id = %d LIMIT 1", array( $id ) );
		$neighbourhood = $wpdb->get_row( $sql );

		return $neighbourhood;
	}

	/**
	 * Get neighbourhood info by slug.
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $slug Neighbourhood slug.
	 * @param int    $location_id Filter neighbourhood by location id.
	 * @return array|mixed
	 */
	public static function get_info_by_slug( $slug, $location_id = 0 ) {
		global $wpdb;

		if ( empty( $slug ) ) {
			return NULL;
		}

		$where = "";
		if ( $location_id > 0 ) {
			$where .= "AND h.hood_location_id = " . (int) $location_id;
		}

		$sql = $wpdb->prepare( "SELECT h.hood_id AS id, h.hood_name AS neighbourhood, h.hood_slug AS slug, h.hood_latitude AS latitude, h.hood_longitude AS longitude, h.hood_meta_title AS meta_title, h.hood_meta AS meta_description, h.hood_description AS description, h.cpt_desc, h.image, l.location_id, l.city, l.city_slug, l.region, l.region_slug, l.country, l.country_slug FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " AS h INNER JOIN " . GEODIR_LOCATIONS_TABLE . " AS l ON l.location_id = h.hood_location_id WHERE h.hood_slug LIKE %s {$where} LIMIT 1", array( $slug ) );
		$neighbourhood = $wpdb->get_row( $sql );

		return $neighbourhood;
	}

	/**
	 * Get neighbourhood info by name.
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $name Neighbourhood name.
	 * @param int    $location_id Filter neighbourhood by location id.
	 * @return array|mixed
	 */
	public static function get_info_by_name( $name, $location_id = 0 ) {
		global $wpdb;

		if ( empty( $name ) ) {
			return NULL;
		}

		$where = "";
		if ( $location_id > 0 ) {
			$where .= "AND h.hood_location_id = " . (int) $location_id;
		}

		$sql = $wpdb->prepare( "SELECT h.hood_id AS id, h.hood_name AS neighbourhood, h.hood_slug AS slug, h.hood_latitude AS latitude, h.hood_longitude AS longitude, h.hood_meta_title AS meta_title, h.hood_meta AS meta_description, h.hood_description AS description, h.image, l.location_id, l.city, l.city_slug, l.region, l.region_slug, l.country, l.country_slug FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " AS h INNER JOIN " . GEODIR_LOCATIONS_TABLE . " AS l ON l.location_id = h.hood_location_id WHERE h.hood_name LIKE %s {$where} LIMIT 1", array( $name ) );
		$neighbourhood = $wpdb->get_row( $sql );

		return $neighbourhood;
	}

	/**
	 * Get neighbourhood slug.
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param int $id Neighbourhood id.
	 * @return string|NULL
	 */
	public static function get_slug( $id ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return NULL;
		}

		$sql = $wpdb->prepare( "SELECT hood_slug FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE hood_id = %d LIMIT 1", array( $id ) );

		return $wpdb->get_var( $sql );
	}

	/**
	 * Get neighbourhood name.
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param int $id Neighbourhood id.
	 * @return string|NULL
	 */
	public static function get_name( $id ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return NULL;
		}

		$sql = $wpdb->prepare( "SELECT hood_name FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE hood_id = %d LIMIT 1", array( $id ) );

		return $wpdb->get_var( $sql );
	}

	public static function get_nicename( $name, $translated = true ) {
		if ( empty( $name ) ) {
			return $name;
		}

		$nicename = geodir_location_get_name( 'neighbourhood', $name, $translated );

		if ( empty( $nicename ) ) {
			$nicename = preg_replace( '/-(\d+)$/', '', $name );
			$nicename = preg_replace( '/[_-]/', ' ', $nicename );

			if ( $translated ) {
				$nicename = __( $nicename, 'geodirectory' );
			}

			$nicename = geodir_ucwords( $nicename );
		}

		return $nicename;
	}

	public static function check_duplicate( $name, $location_id = 0, $neighbourhood_id = 0 ) {
		global $wpdb;

		if ( empty( $name ) ) {
			return false;
		}

		$where = '';
		if ( $location_id ) {
			$where .= $wpdb->prepare( " AND hood_location_id = %d", array( $location_id ) );
		}
		if ( $neighbourhood_id ) {
			$where .= $wpdb->prepare( " AND hood_id != %d", array( $neighbourhood_id ) );
		}
		$sql = $wpdb->prepare( "SELECT hood_id FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE hood_name = %s {$where} LIMIT 1", array( $name ) );
		$exists = $wpdb->get_var( $sql );

		return $exists;
	}

	public static function count_by_location_id( $location_id ) {
		global $wpdb;

		$count = (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT( hood_id ) FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE hood_location_id = %d LIMIT 1", array( $location_id ) ) );

		return $count;
	}

	public static function get_neighbourhoods_by_location_id( $location_id ) {
		global $wpdb;

		$neighbourhoods = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE hood_location_id = %d ORDER BY hood_id ASC", array( $location_id ) ) );

		return $neighbourhoods;
	}

	public static function get_neighbourhoods_by_location_names( $city, $region = '', $country = '', $operator = 'LIKE', $order_by = 'name' ) {
		global $wpdb;

		if ( empty( $city ) ) {
			return NULL;
		}

		$where = array( "l.city {$operator} %s" );
		$args = array( $city );

		if ( ! empty( $country ) ) {
			$where[] = "l.country {$operator} %s";
			$args[] = $country;
		}

		if ( ! empty( $region ) ) {
			$where[] = "l.region {$operator} %s";
			$args[] = $region;
		}

		$where = implode( " AND ", $where );

		if ( $order_by == 'name' ) {
			$order_by = 'h.hood_name';
		} else {
			$order_by = 'h.hood_id';
		}

		$neighbourhoods = $wpdb->get_results( $wpdb->prepare( "SELECT h.* FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " AS h INNER JOIN " . GEODIR_LOCATIONS_TABLE . " AS l ON l.location_id = h.hood_location_id WHERE {$where} ORDER BY {$order_by} ASC", $args ) );

		return $neighbourhoods;
	}

	public static function delete_by_id( $id ) {
		global $wpdb, $plugin_prefix;

		$neighbourhood 	= self::get_info_by_id( $id );
		if ( empty( $neighbourhood ) ) {
			return false;
		}
		
		do_action( 'geodir_location_pre_delete_neighbourhood', $neighbourhood );

		$post_types = geodir_get_posttypes();

		foreach ( $post_types as $post_type ) {
			$table = geodir_db_cpt_table( $post_type );

			$wpdb->query(
				$wpdb->prepare( 
					"UPDATE " . $table . " SET neighbourhood = '' WHERE location_id = %d AND neighbourhood LIKE %s", 
					array( $neighbourhood->location_id, $neighbourhood->slug )
				)
			);
		}

		$wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE hood_id = %d", array( $neighbourhood->id ) ) );

		do_action( 'geodir_location_post_delete_neighbourhood', $neighbourhood );

		return true;
	}

	/**
	 * Add the db column for neighborhoods if required.
	 * 
	 * @param $columns
	 * @param $cpt
	 *
	 * @return mixed
	 */
	public static function db_neighbourhood_column( $columns, $cpt, $post_type ) {
		if ( self::is_active() && (!isset($cpt['disable_location']) || !$cpt['disable_location'] ) ) {
			$columns['neighbourhood'] = "neighbourhood VARCHAR( 100 ) NULL";
		}
		return $columns;
	}

	/**
	 * Save neighbourhood data.
	 *
	 * @since 1.4.5
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param array $data Neighbourhood data.
	 * @return array Neighbourhood info.
	 */
	public static function import_neighbourhood($data) {
		global $wpdb;

		if (empty($data) || empty($data['hood_name'])) {
			return false;
		}
		
		$hood_id = 0;
		if (empty($data['hood_id'])) {
			$data['hood_slug'] = !empty($data['hood_slug']) ? $data['hood_slug'] : $data['hood_name'];
			$data['hood_slug'] = self::unique_slug($data['hood_slug']);
			
			if ($wpdb->insert(GEODIR_NEIGHBOURHOODS_TABLE, $data)) {
				$hood_id = (int)$wpdb->insert_id;
			}
		} else {
			$data['hood_slug'] = !empty($data['hood_slug']) ? $data['hood_slug'] : $data['hood_name'];
			$data['hood_slug'] = self::unique_slug($data['hood_slug'], $data['hood_id']);

			$wpdb->update(GEODIR_NEIGHBOURHOODS_TABLE, $data, array('hood_id' => (int)$data['hood_id']));
			$hood_id = (int)$data['hood_id'];
		}

		$result = array();
		if ($hood_id > 0) {
			$result = self::get_info_by_id($hood_id);
		}

		return $result;
	}

	/**
	 * Check the neighbour hood for current city.
	 *
	 * @since 1.4.4
	 * @package GeoDirectory_Location_Manager
	 *
	 * @param string $hood Neighbour hood id or slug.
	 * @param string $city Current city slug.
	 * @param string $region Current region slug. Default empty.
	 * @param string $country Current country slug. Default empty.
	 * @return null|string Neighbour hood location url.
	 */
	public static function is_neighbourhood($hood_slug, $gd_city, $gd_region = '', $gd_country = '') {
		if (empty($hood_slug) || empty($gd_city)) {
			return false;
		}
		
		$location = GeoDir_Location_City::get_info_by_slug($gd_city, $gd_country, $gd_region);
		if (empty($location)) {
			return false;
		}
		
		$hood = self::get_info_by_slug($hood_slug, $location->location_id);
		if (!empty($hood)) {
			return $hood;
		}
		
		return false;
	}

	/**
	 * Filter location description text..
	 *
	 * @since 1.4.9
	 *
	 * @global object $wp WordPress object.
	 *
	 * @param string $description The location description text.
	 * @param string $gd_country The current country slug.
	 * @param string $gd_region The current region slug.
	 * @param string $gd_city The current city slug.
	 */
	public static function location_description( $description, $gd_country, $gd_region, $gd_city ) {
		global $wp;

		if ( !empty( $wp->query_vars['gd_neighbourhood'] ) ) {
			$description = '';

			$location = GeoDir_Location_City::get_info_by_slug( $gd_city, $gd_country, $gd_region );
			if ( empty( $location ) ) {
				return $description;
			}

			$hood = self::get_info_by_slug( $wp->query_vars['gd_neighbourhood'], $location->location_id );
			if ( !empty( $hood ) && !empty( $hood->description ) ) {
				$description = stripslashes( __( $hood->description, 'geodirectory' ) );
			}
		}

		return $description;
	}

	public static function on_location_deleted( $location ) {
		global $wpdb;

		if ( empty( $location->location_id ) ) {
			return false;
		}

		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " WHERE hood_location_id = %d", array( $location->location_id ) ) );
	}

	public static function replace_neighbourhood_name( $name, $text ) {
		return self::get_nicename( $name );
	}

	public static function post_meta_neighbourhood_field( $fields, $post_type ) {
		if ( ! self::is_active() ) {
			return $fields;
		}

		$fields['neighbourhood'] = array(
			'type' => 'custom',
			'name' => 'neighbourhood',
			'htmlvar_name' => 'neighbourhood',
			'frontend_title' => __( 'Neighbourhood', 'geodirlocation' ),
			'field_icon' => ( ! empty( $fields['street']['field_icon'] ) ? $fields['street']['field_icon'] : 'fas fa-map-marker-alt' ),
			'field_type_key' => '',
			'css_class' => '',
			'extra_fields' => ''
		);

		return $fields;
	}

	public static function post_meta_neighbourhood_value( $value, $location, $cf, $gd_post ) {
		if ( $value && ! empty( $cf['name'] ) && $cf['name'] == 'neighbourhood' ) {
			$value = self::get_nicename( $value );
		}

		return $value;
	}
}

return new GeoDir_Location_Neighbourhood();