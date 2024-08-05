<?php
/**
 * Pricing Manager Query class.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Query class.
 */
class GeoDir_Pricing_Query {

	function __construct() {
		add_filter( 'geodir_widget_listings_query_args', array( __CLASS__, 'listings_widget_query_args' ), 1, 2 );
		add_filter( 'geodir_filter_widget_listings_where', array( __CLASS__, 'listings_widget_where' ), 20, 2 );
	}

	/**
	 * Filter listings widget query args.
	 *
	 * @since 2.6.0.4
	 *
	 * @param array $query_args Listings widget arguements.
	 * @param array $instance Widget instance.
	 *
	 * @return array Modified listings widget query args.
	 */
	public static function listings_widget_query_args( $query_args, $instance ) {
		if ( ! empty( $instance['package_ids'] ) ) {
			$package_ids = is_array( $instance['package_ids'] ) ? $instance['package_ids'] : explode( ",", $instance['package_ids'] );

			$package__in = array();
			$package__not_in = array();

			foreach ( $package_ids as $package_id ) {
				$package_id = trim( $package_id );

				if ( abs( $package_id ) != $package_id ) {
					$package__not_in[] = absint( $package_id );
				} else {
					$package__in[] = absint( $package_id );
				}
			}

			if ( ! empty( $package__in ) ) {
				$query_args['package__in'] = $package__in;
			} elseif ( ! empty( $package__not_in ) ) {
				if ( ! empty( $query_args['package__not_in'] ) ) {
					$package__not_in[] = $query_args['package__not_in'];
				}

				$query_args['package__not_in'] = $package__not_in;
			}
		}

		return $query_args;
	}

	/**
	 * Listings query where clause SQL part for widgets.
	 *
	 * @since 2.6.0.4
	 *
	 * @global array $gd_query_args_widgets Listings widget arguements.
	 *
	 * @param string $where Listings query where clause.
	 *
	 * @return string Modified listings query where clause.
	 */
	public static function listings_widget_where( $where, $post_type ) {
		global $gd_query_args_widgets;

		if ( empty( $gd_query_args_widgets ) ) {
			return $where;
		}

		$table = geodir_db_cpt_table( $post_type );

		if ( ! empty( $gd_query_args_widgets['package__in'] ) ) {
			if ( count( $gd_query_args_widgets['package__in'] ) == 1 ) {
				$where .= " AND `{$table}`.`package_id` = " . absint( $gd_query_args_widgets['package__in'][0] );
			} else {
				$package__in = implode( ',', array_map( 'absint', $gd_query_args_widgets['package__in'] ) );
				$where .= " AND `{$table}`.`package_id` IN ($package__in)";
			}
		} elseif ( ! empty( $gd_query_args_widgets['package__not_in'] ) ) {
			if ( count( $gd_query_args_widgets['package__not_in'] ) == 1 ) {
				$where .= " AND `{$table}`.`package_id` != " . absint( $gd_query_args_widgets['package__not_in'][0] );
			} else {
				$package__not_in = implode( ',', array_map( 'absint', $gd_query_args_widgets['package__not_in'] ) );
				$where .= " AND `{$table}`.`package_id` NOT IN ($package__not_in)";
			}
		}

		return $where;
	}
}