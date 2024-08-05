<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_Permalinks.
 *
 * @class    GeoDir_Location_Locations
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_Taxonomies extends GeoDir_Taxonomies{

	public function __construct() {
		// call parent constructor
		parent::__construct();

		// add location counts to the get_terms call
		if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
			add_filter('get_terms', array($this,'get_terms_with_count'), 10, 3);
			add_filter( 'tag_cloud_sort', array( $this, 'tag_cloud_sort' ), 10, 2 );
		}
	}


	/**
	 * Get terms with term count.
	 *
	 * @since 2.0.0
	 * @package GeoDirectory_Location_Manager
	 *
	 * @param array $arr Term array.
	 * @param string $tax Taxonomy name.
	 * @param array $args GD args.
	 * @return mixed
	 */
	public function get_terms_with_count( $arr, $tax, $args ) {
		if ( isset( $args['gd_no_loop'] ) ) {
			return $arr; // so we don't do an infinite loop
		}

		// Count location terms for categories only.
		if ( empty( $tax ) ) {
			return $arr;
		} else {
			if ( is_array( $tax ) && count( $tax ) == 1 ) {
				if ( geodir_taxonomy_type( $tax[0] ) != 'category' ) {
					return $arr; // No GD category.
				}
			} elseif ( is_scalar( $tax ) && geodir_taxonomy_type( $tax ) != 'category' ) {
				return $arr; // No GD category.
			}
		}

		if (!empty($arr)) {
			$term_count = $this->get_term_count('term_count');

//			echo '@@@';print_r($term_count);

			/**
			 * Filter the terms count by location.
			 *
			 * @since 1.3.4
			 *
			 * @param array $terms_count Array of term count row.
			 * @param array $terms Array of terms.
			 */
			$term_count = apply_filters( 'geodir_loc_term_count', $term_count, $arr );


			$is_everywhere = geodir_get_current_location_terms();

//			print_r($arr);
			if(!empty($term_count)){
				foreach ($arr as $term) {
					if (isset($term->term_id) && isset($term_count[$term->term_id])) {
						$term->count = $term_count[$term->term_id];
					}elseif(isset($term->term_id) && !empty($is_everywhere)){// if we dont have a term count for it then it's probably wrong.
						$term->count = 0;
					}
				}
			}

//			print_r($arr);
//			echo '@@@';print_r($term_count);
//			print_r($is_everywhere);exit;
		}

		return $arr;
	}

	/**
	 * Get term count for a location.
	 *
	 * @since 1.0.0
	 * @since 1.4.1 Fix term data count for multiple location names with same name.
	 * @since 1.4.4 Updated for the neighbourhood system improvement.
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 * @global bool $gd_use_query_vars If true then use query vars to get current location terms.
	 *
	 * @param string $count_type Count type. Possible values are 'review_count', 'term_count'.
	 * @param null|string $location_name Location name slug. Ex: new-york.
	 * @param null|string $location_type Location type. Possible values 'gd_city','gd_region','gd_country'.
	 * @param bool|array $loc {
	 *    Attributes of the location array.
	 *
	 *    @type string $gd_country The country slug.
	 *    @type string $gd_region The region slug.
	 *    @type string $gd_city The city slug.
	 *
	 * }
	 * @param bool $force_update Do you want to force update? default: false.
	 * @return array|mixed|void
	 */
	public function get_term_count($count_type = 'term_count', $location_name=null, $location_type=null, $loc=false, $force_update=false ) {
		global $wpdb, $geodirectory;

		if (!$location_name || !$location_type || empty($loc)) {
			$loc = $geodirectory->location;
//			print_r($loc );
			$location_type = $loc->type;
			$location_name = $location_type && in_array($location_type,$geodirectory->location->allowed_query_variables()) ? $loc->{$location_type."_slug"} : '';

		}

		if ($location_name && $location_type) {
			$gd_country = isset($loc->country_slug) ? $loc->country_slug: '';
			$gd_region = isset($loc->region_slug) ? $loc->region_slug : '';

			if ($location_type == 'city' && !empty($loc->neighbourhood_slug)) {
				$location_type = 'neighbourhood';
				$location_name = $location_name . '::' . $loc->neighbourhood_slug;
			}

			$where = '';
			switch($location_type) {
				case 'country':
					$where .= " AND country_slug='" . urldecode($location_name) . "'";
					break;
				case 'region':
					$where .= " AND region_slug='" . urldecode($location_name) . "'";
					$where .= " AND country_slug='" . urldecode($gd_country) . "'";
					break;
				case 'city':
				case 'neighbourhood':
					$where .= " AND region_slug='" . urldecode($gd_region) . "'";
					$where .= " AND country_slug='" . urldecode($gd_country) . "'";
					break;
			}

			$sql = $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATION_TERM_META . " WHERE location_type=%s AND location_name=%s " . $where . " LIMIT 1", array( $location_type, urldecode($location_name) ) );
			//echo $sql;
			$row = $wpdb->get_row( $sql );


			if ( $row ) {
				if ( $force_update || !$row->{$count_type}) {
					return $this->set_term_count( $location_name, $location_type, $loc, $count_type, $row->id );
				} else {
					$data = maybe_unserialize( $row->{$count_type} );
					return $data;
				}
			} else {
				return $this->set_term_count( $location_name, $location_type, $loc, $count_type, null );
			}
		} else {
			return;
		}
	}


	/**
	 * Insert term count for a location.
	 *
	 * @since 1.0.0
	 * @since 1.4.1 Fix term data count for multiple location names with same name.
	 * @since 1.4.4 Updated for the neighbourhood system improvement.
	 * @since 1.4.7 Fixed add listing page load time.
	 * @since 1.5.0 Fixed location terms count for WPML languages.
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 * @global array $gd_update_terms The post term ids.
	 * @global string $gd_term_post_type The post type.
	 *
	 * @param $location_name
	 * @param string $location_type Location type. Possible values 'gd_city','gd_region','gd_country'.
	 * @param array $loc {
	 *    Attributes of the location array.
	 *
	 *    @type string $gd_country The country slug.
	 *    @type string $gd_region The region slug.
	 *    @type string $gd_city The city slug.
	 *
	 * }
	 * @param string $count_type Count type. Possible values are 'review_count', 'term_count'.
	 * @param null $row_id
	 * @return array
	 */
	public function set_term_count($location_name, $location_type, $loc, $count_type, $row_id=null) {
		global $wpdb, $gd_update_terms, $gd_term_post_type, $sitepress;

		if (!empty($gd_update_terms) && $gd_term_post_type && $row_id > 0) {
			$term_array = $wpdb->get_var($wpdb->prepare( "SELECT `" . $count_type . "` FROM " . GEODIR_LOCATION_TERM_META . " WHERE id=%d", array($row_id)));
			$term_array = (array)maybe_unserialize($term_array);

			if (!empty($gd_update_terms) && GeoDir_Post_types::supports( $gd_term_post_type, 'location' ) ) {
				foreach ($gd_update_terms as $term_id) {
					if ($term_id > 0) {
						$term_array[$term_id] = $this->get_term_location_counts($term_id, $gd_term_post_type . 'category', $gd_term_post_type, $location_type, $loc, $count_type);
					}
				}
			}
		} else {
			$post_types = geodir_get_posttypes();
			$term_array = apply_filters( 'geodir_location_term_meta_default_terms', array(), $row_id, $count_type );

			foreach($post_types as $post_type) {
				if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					continue;
				}

				$taxonomy = geodir_get_taxonomies($post_type);
				$taxonomy = $taxonomy[0];

				$args = array(
					'hide_empty' => false,
					'gd_no_loop' => true
				);

				do_action( 'geodir_location_get_terms_set_globals', $post_type, $taxonomy, $location_type, $loc, $count_type );

				$terms = get_terms($taxonomy, $args);

				do_action( 'geodir_location_get_terms_reset_globals', $post_type, $taxonomy, $location_type, $loc, $count_type );

				foreach ($terms as $term) {
					$count = $this->get_term_location_counts($term->term_id, $taxonomy, $post_type, $location_type, $loc, $count_type);
					$term_array[$term->term_id] = $count;
				}
			}
		}

		$data = maybe_serialize($term_array);

		$save_data = array();
		$save_data[$count_type] = $data;

		if ( $row_id ) {
			// Update term data.
			$wpdb->update(GEODIR_LOCATION_TERM_META, $save_data, array('id' => $row_id));
		} else {
			$gd_country = !empty($loc) && isset($loc->country_slug) ? $loc->country_slug : '';
			$gd_region = !empty($loc) && isset($loc->region_slug) ? $loc->region_slug : '';

			$save_data['location_type'] = $location_type;
			$save_data['location_name'] = urldecode($location_name);

			switch($location_type) {
				case 'country':
					$save_data['country_slug'] = urldecode($location_name);
					break;
				case 'region':
					$save_data['region_slug'] = urldecode($location_name);
					$save_data['country_slug'] = urldecode($gd_country);
					break;
				case 'city':
				case 'neighbourhood':
					$save_data['region_slug'] = urldecode($gd_region);
					$save_data['country_slug'] = urldecode($gd_country);
					break;
			}

			// Insert term data.
			$wpdb->insert(GEODIR_LOCATION_TERM_META, $save_data);
		}
		return $term_array;
	}

	/**
	 * Get review count or term count.
	 *
	 * @since 1.0.0
	 * @since 1.4.4 Updated for the neighbourhood system improvement.
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 * @global string $plugin_prefix Geodirectory plugin table prefix.
	 *
	 * @param int|string $term_id The term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $post_type The post type.
	 * @param string $location_type Location type. Possible values 'gd_city','gd_region','gd_country'.
	 * @param array $loc {
	 *    Attributes of the location array.
	 *
	 *    @type string $gd_country The country slug.
	 *    @type string $gd_region The region slug.
	 *    @type string $gd_city The city slug.
	 *
	 * }
	 * @param string $count_type Count type. Possible values are 'review_count', 'term_count'.
	 * @return int|null|string
	 */
	public function get_term_location_counts( $term_id, $taxonomy, $post_type, $location_type, $loc, $count_type ) {
		global $wpdb, $plugin_prefix,$geodirectory;

		$table = geodir_db_cpt_table( $post_type );

		if(!$loc){
			$loc = $geodirectory->location;
			$location_type = $loc->type;
			$location_name = $loc->{$location_type."_slug"};
		}

		$country ='';
		$region ='';
		$city = '';
		$neighbourhood = '';
		if (isset($loc->city) && $loc->city != '') {
			$city = $loc->city;
		}
		if (isset($loc->region) && $loc->region != '') {
			$region = $loc->region;
		}
		if (isset($loc->country) && $loc->country != '') {
			$country = $loc->country;
		}
		if ( $city != '' ) {
			if ( isset( $loc->neighbourhood_slug ) && $loc->neighbourhood_slug != '' ) {
				$location_type = 'neighbourhood';
				$neighbourhood = $loc->neighbourhood_slug;
			} elseif ( isset( $loc->neighbourhood ) && $loc->neighbourhood != '' ) {
				$location_type = 'neighbourhood';
				$neighbourhood = $loc->neighbourhood;
			}				
		}

		$where = '';
		if ( $country!= '') {
			$where .= $wpdb->prepare(" AND country = %s ",$country);
		}

		if ( $region != '' && $location_type!='country' ) {
			$where .= $wpdb->prepare(" AND region = %s ",$region);
		}

		if ( $city != '' && $location_type!='country' && $location_type!='region' ) {
			$where .= $wpdb->prepare(" AND city = %s ",$city);
		}

		if ($location_type == 'neighbourhood' && $neighbourhood != '' && $wpdb->get_var("SHOW COLUMNS FROM " . $table . " WHERE field = 'neighbourhood'")) {
			$where .= $wpdb->prepare(" AND neighbourhood = %s ",$neighbourhood);
		}

		if ($count_type == 'review_count') {
			$sql = "SELECT COALESCE(SUM(rating_count),0) FROM  $table WHERE post_status = 'publish' $where AND FIND_IN_SET(" . $term_id . ", post_category)";
		} else {
			$sql = "SELECT COUNT(post_id) FROM  $table WHERE post_status = 'publish' $where AND FIND_IN_SET(" . $term_id . ", post_category)";
		}
		/**
		 * Filter terms count sql query.
		 *
		 * @since 1.3.8
		 * @param string $sql Database sql query..
		 * @param int $term_id The term ID.
		 * @param int $taxonomy The taxonomy Id.
		 * @param string $post_type The post type.
		 * @param string $location_type Location type .
		 * @param string $loc Current location terms.
		 * @param string $count_type The term count type.
		 * @param string $where The where clause.
		 */
		$sql = apply_filters('geodir_location_count_reviews_by_term_sql', $sql, $term_id, $taxonomy, $post_type, $location_type, $loc, $count_type, $where );

		$count = $wpdb->get_var($sql);

		return $count;
	}

	/**
	 * Don't append location terms to tag cloud links.
	 *
	 * @since 2.0.0.18
	 *
	 * @param WP_Term[] $tags Ordered array of terms.
	 * @param array     $args An array of tag cloud arguments.
	 * @return WP_Term[] $tags Ordered array of terms.
	 */
	public function tag_cloud_sort( $tags, $args ) {
		global $geodirectory;

		if ( ! empty( $tags ) && ! empty( $tags[0]->taxonomy ) && geodir_taxonomy_type( $tags[0]->taxonomy ) == 'tag' ) {
			foreach ( $tags as $i => $tag ) {
				if ( ! empty( $tag->link ) && ( $link = GeoDir_Location_SEO::get_term_link( $tag, false ) ) ) {
					if ( ! empty( $link ) && ! is_wp_error( $link ) ) {
						$tags[ $i ]->link = $link;
					}
				}
			}
		}

		return $tags;
	}

}