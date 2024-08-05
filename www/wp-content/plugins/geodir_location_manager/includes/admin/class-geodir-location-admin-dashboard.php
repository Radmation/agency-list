<?php
/**
 * GeoDirectory Location Manager Admin Dashboard
 *
 * @author      AyeCode
 * @category    Admin
 * @package     GeoDir_Location_Manager/Admin
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GeoDir_Location_Admin_Dashboard Class.
 */
class GeoDir_Location_Admin_Dashboard {

	public static $stat_key;
	public static $stat_label;

	public function __construct() {
		$type = isset( $_REQUEST['type'] ) ? sanitize_text_field( $_REQUEST['type'] ) : 'all';
		$period = isset( $_REQUEST['period'] ) ? sanitize_text_field( $_REQUEST['period'] ) : 'this_month';
		
		self::$stat_key = 'locations';
		self::$stat_label = __( 'Locations', 'geodirlocation' );

		add_filter( 'geodir_dashboard_navs', array( __CLASS__, 'stat_nav' ), 10, 2 );
		if ( ( $type == 'all' && $period == 'all' ) || $type == 'locations' ) {
			add_filter( 'geodir_dashboard_get_stats', array( __CLASS__, 'get_stats' ), 7, 3 ); // 7. Locations
		}
	}

	public static function get_location_types() {
		$types	= array(
			'country' => __( 'Countries', 'geodirlocation' ),
			'region' => __( 'Regions', 'geodirlocation' ),
			'city' => __( 'Cities', 'geodirlocation' ),
		);
		if ( GeoDir_Location_Neighbourhood::is_active() ) {
			$types['neighbourhood'] = __( 'Neighbourhoods', 'geodirlocation' );
		}

		return apply_filters( 'geodir_dashboard_get_location_types', $types );
	}

	public static function stat_nav( $navs ) {
		$navs[ self::$stat_key ] = self::$stat_label;

		return $navs;
	}

	public static function get_stats( $stats, $type, $period ) {
		$location_types = self::get_location_types();
		$location_stats = self::location_stats( $type, $period );

		$data = array();
		if ( ! empty( $location_stats['location_types'] ) ) {
			foreach ( $location_stats['location_types'] as $location_type => $total ) {
				$stats['stats'][ $location_type ] = array(
					'icon' => 'fas fa-map-marker-alt',
					'label' => $location_types[ $location_type ],
					'value' => $total
				);

				$data[] = array( 'key' => $location_types[ $location_type ], 'value' => $total );
			}
		}
		$stats['chart_type'] = 'bar';
		$stats['chart_params']['ykeys'] = array( 'value' );
		$stats['chart_params']['labels'] = array( __( 'Total', 'geodirlocation' ) );
		$stats['chart_params']['data'] = $data;

		return $stats;
	}

	public static function location_stats( $type = 'all', $period = 'all', $statuses = array() ) {
		$location_types = array();
		$location_types['country'] = GeoDir_Location_Country::total();
		$location_types['region'] = GeoDir_Location_Region::total();
		$location_types['city'] = GeoDir_Location_City::total();
		if ( GeoDir_Location_Neighbourhood::is_active() ) {
			$location_types['neighbourhood'] = GeoDir_Location_Neighbourhood::total();
		}

		$total = 0;
		foreach ( $location_types as $key => $count ) {
			$total += (int)$count;
		}
		$stats = array( 'location_types' => $location_types , 'total' => $total );
		
		return apply_filters( 'geodir_dashboard_locations_stats', $stats, $type, $period, $statuses );
	}
}

new GeoDir_Location_Admin_Dashboard();
