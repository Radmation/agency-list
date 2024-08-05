<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Custom Post Types Import Export class
 *
 * @class       GeoDir_CP_Import_Export
 * @version     2.0.0
 * @package     GeoDir_Custom_Posts/Classes
 * @category    Class
 * @author      AyeCode Ltd
 */
class GeoDir_CP_Import_Export {

	public static function init() {
		if ( is_admin() ) {
			// add the column names
			add_filter( 'geodir_export_posts_csv_columns', array( __CLASS__, 'export_posts_csv_columns' ), 10, 2 );

			// add the post row info
			add_filter( 'geodir_export_posts_csv_row', array( __CLASS__, 'export_posts_csv_row' ), 10, 3 );
		}
	}

	/**
	 * Add the linked post ids to the export row.
	 *
	 * @param $post_info
	 * @param $post_id
	 * @param $post_type
	 *
	 * @return mixed
	 */
	public static function export_posts_csv_row( $post_info, $post_id, $post_type ) {
		$post_types = GeoDir_CP_Link_Posts::linked_to_post_types( $post_type );

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $_post_type ) {
				$items = self::get_linked_ids( $post_id, $_post_type );

				if ( empty( $post_info[ $_post_type ] ) ) {
					if ( ! empty( $items ) ) {
						$items_string = implode( ',', array_map( function( $entry ) {
							return $entry['linked_id'];
						}, $items ) );
						$post_info[ $_post_type ] = $items_string;
					} else {
						$post_info[ $_post_type ] = '';
					}
				}
			}
		}

		return $post_info;
	}

	/**
	 * Get the linked ids of the post and post_type.
	 *
	 * @param $post_id
	 * @param $post_type
	 *
	 * @return mixed
	 */
	public static function get_linked_ids( $post_id, $post_type ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT linked_id FROM " . GEODIR_CP_LINK_POSTS . " WHERE post_id = %d AND linked_post_type = %s", $post_id, $post_type ),ARRAY_A );

		return $results;
	}


	/**
	 * Add the linked post types as columns.
	 *
	 * @param $columns
	 * @param $post_type
	 *
	 * @return array
	 */
	public static function export_posts_csv_columns( $columns, $post_type ) {
		$post_types = GeoDir_CP_Link_Posts::linked_to_post_types( $post_type );

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $_post_type ) {
				$columns[] = $_post_type;
			}
		}

		return $columns;
	}
}