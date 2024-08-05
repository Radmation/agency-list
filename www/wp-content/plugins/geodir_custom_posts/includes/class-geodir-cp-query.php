<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Custom Post Types Query class.
 *
 * AJAX Event Handler.
 *
 * @class    GeoDir_CP_Query
 * @package  GeoDir_Custom_Posts/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_CP_Query {

	function __construct() {

		add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ), -1, 1 );
		add_filter( 'geodir_widget_listings_query_args', array( __CLASS__, 'listings_widget_query_args' ), 100, 2 );
		add_filter( 'geodir_location_allowed_location_where', array( __CLASS__, 'check_location_where' ), 9999, 5 );
		add_filter( 'geodir_filter_widget_listings_where', array( __CLASS__, 'widget_posts_where' ), 10, 2 );
		//add_filter( 'geodir_posts_fields', array( __CLASS__, 'posts_fields' ), 1, 2 );
		//add_filter( 'geodir_posts_join', array( __CLASS__, 'posts_join' ), 1, 2 );
		add_filter( 'geodir_posts_where', array( __CLASS__, 'posts_where' ), 1, 2 );
		//add_filter( 'geodir_posts_order_by_sort', array( __CLASS__, 'posts_orderby' ), 1, 4 );
		//add_filter( 'geodir_posts_groupby', array( __CLASS__, 'posts_groupby' ), 1, 2 );
	}

	public static function pre_get_posts( $wp_query = array() ) {
		global $geodir_post_type, $dist, $mylat, $mylon, $snear;

		if ( $geodir_post_type && $wp_query->is_main_query() && geodir_is_page( 'search' ) && ! GeoDir_Post_types::supports( $geodir_post_type, 'location' ) ) {
			$dist = $mylat = $mylon = $snear = '';
			
			if ( isset( $_REQUEST['snear'] ) ) {
				unset( $_REQUEST['snear'] );
			}
			
			if ( isset( $_REQUEST['sgeo_lat'] ) ) {
				unset( $_REQUEST['sgeo_lat'] );
			}
				
			if ( isset( $_REQUEST['sgeo_lon'] ) ) {
				unset( $_REQUEST['sgeo_lon'] );
			}
		}
	}

	public static function posts_fields( $fields, $wp_query = array() ) {
		global $geodir_post_type;

		return $fields;
	}

	public static function posts_join( $join, $wp_query = array() ) {
		global $wpdb, $geodir_post_type;

		return $join;
	}

	public static function posts_where( $where, $wp_query = array() ) {
		global $wpdb, $geodir_post_type, $table;

		if ( ! GeoDir_Query::is_gd_main_query( $wp_query ) ) {
			return $where;
		}

		$linked_where = '';
		if ( ! empty( $_REQUEST['linked_to_post'] ) && ( $post_id = absint( $_REQUEST['linked_to_post'] ) ) ) {
			$linked_where = GeoDir_CP_Link_Posts::linked_post_condition( 'to', $post_id, '', 0, $geodir_post_type );
		} elseif ( ! empty( $_REQUEST['linked_from_post'] ) && ( $post_id = absint( $_REQUEST['linked_from_post'] ) ) ) {
			$linked_where = GeoDir_CP_Link_Posts::linked_post_condition( 'from', 0, $geodir_post_type, $post_id, '' );
		}

		if ( $linked_where != '' ) {
			$where .= " AND {$linked_where}";
		}

		return $where;
	}

	public static function posts_groupby( $groupby, $wp_query = array() ) {
		global $wpdb, $geodir_post_type;

		return $groupby;
	}

	public static function posts_orderby( $orderby, $sortby, $table, $wp_query = array() ) {
		global $geodir_post_type;

		return $orderby;
	}

	public static function listings_widget_query_args( $query_args, $instance ) {
		global $gd_post;

		if ( ! empty( $query_args['post_type'] ) && ! GeoDir_Post_types::supports( $query_args['post_type'], 'location' ) ) {
			$query_args['gd_location'] = false;
			$query_args['distance_to_post'] = false;
		}

		// Linked posts query args.
		if ( ! empty( $instance['linked_posts'] ) && in_array( $instance['linked_posts'], array( 'from', 'to' ) ) ) {
			$link_type = 'linked_' . $instance['linked_posts'] . '_post';
			$post_id =  ! empty( $instance['linked_post_id'] ) ? absint( $instance['linked_post_id'] ) : 0;

			if ( empty( $post_id ) && ! empty( $gd_post ) ) {
				$post_id = $gd_post->ID; // Current post ID.
			}

			$query_args[ $link_type ] = $post_id;
		}

		return $query_args;
	}

	/**
	 * Filter whether search by location allowed for CPT.
	 *
	 * @since 1.1.6
	 *
	 * @param bool $allowed True if search by location allowed. Otherwise false.
	 * @param object $wp_query_vars WP_Query query vars object.
	 * @param string $table Listing database table name.
	 * @param object $wp_query WP_Query query object.
	 * @param string $listing_table Listing database table name.
	 * @return bool True if search by location allowed. Otherwise false.
	 */
	public static function check_location_where( $allowed, $wp_query_vars, $table, $wp_query, $listing_table = '' ) {
		$post_type = !empty( $wp_query_vars ) && ! empty( $wp_query_vars['post_type'] ) ? $wp_query_vars['post_type'] : '';

		if ( $table != '' || $listing_table != '' ) {
			$post_types = geodir_get_posttypes();

			$table = $listing_table != '' ? $listing_table : $table;

			foreach ( $post_types as $cpt ) {
				if ( $table == geodir_db_cpt_table( $cpt ) ) {
					$post_type = $cpt;
				}
			}
		}

		if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			$allowed = false;
		}

		return $allowed;
	}

	public static function widget_posts_where( $where, $post_type ) {
		global  $wpdb, $gd_query_args_widgets;

		$linked_where = '';
		if ( ! empty( $gd_query_args_widgets ) && ! empty( $gd_query_args_widgets['linked_to_post'] ) && ( $post_id = absint( $gd_query_args_widgets['linked_to_post'] ) ) ) {
			$linked_where = GeoDir_CP_Link_Posts::linked_post_condition( 'to', $post_id, '', 0, $post_type );
		} elseif ( ! empty( $gd_query_args_widgets ) && ! empty( $gd_query_args_widgets['linked_from_post'] ) && ( $post_id = absint( $gd_query_args_widgets['linked_from_post'] ) ) ) {
			$linked_where = GeoDir_CP_Link_Posts::linked_post_condition( 'from', 0, $post_type, $post_id, '' );
		}

		if ( $linked_where != '' ) {
			$where .= " AND {$linked_where}";
		}

		return $where;
	}
}