<?php
/**
 * Claim Listings Claims Admin Dashboard class.
 *
 * Used to add stats to the GD Dashboard.
 *
 * @since 2.0.0.7
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Claim_Admin_Claims_Dashboard class.
 */
class GeoDir_Claim_Admin_Claims_Dashboard {

	/**
	 * Initialize the claims admin dashboard actions.
	 */
	public function __construct() {
		add_filter('geodir_dashboard_get_pending_stats', array($this,'required_actions'));
//		add_filter( 'geodir_dashboard_get_stats', array($this,'statistics'), 3, 3 ); 	// @todo we need to implement this properly
	}

	/**
	 * Add pending actions required to GD Dashboard.
	 *
	 * @since 2.0.0.7
	 *
	 * @param $stats
	 *
	 * @return mixed
	 */
	public function required_actions( $stats ){

		$counts = geodir_claim_count_claims();
		$pending_count = ( ! empty( $counts->pending ) ? (int) $counts->pending : 0 );
		$link = admin_url( 'admin.php?page=gd-settings&tab=claims&claim_status=pending' );

		$stats['claim_listings'] = array(
			'icon' => 'fas fa-user-check',
			'label' => __( 'Pending Claims', 'geodir-claim' ),
			'total' => $pending_count,
			'url' => $link,
		);

		if ( isset( $counts->post_types ) ) {
			$items = array();
			foreach ( $counts->post_types as $post_type => $stat ) {
				$items[] = array(
					'icon' => 'fas fa-map-marker-alt',
					'label' => geodir_post_type_name( $post_type ),
					'total' => ( ! empty( $stat->pending ) ? (int) $stat->pending : 0 ),
					'url' => $link . '&_claim_post_type=' . $post_type,
				);
			}

			$stats['claim_listings']['items'] = $items;
			$stats['claim_listings']['total'] = $pending_count;
		}

		return $stats;
	}

	/**
	 * Dashboard claimed listings stats.
	 *
	 * @since 2.0.0.7
	 *
	 * @param array $stats stats.
	 * @param string $type Type.
	 * @param string $period Period.
	 * @return array $stats.
	 */
	function statistics( $stats, $type, $period ) {

		print_r($stats);echo '###';exit;
		$stat_key = 'claimed_listings';
		$stat_label = __( 'Claimed Listings', 'geodir-claim' );

		$counts = geodir_claim_count_claims();
		$pending_count = isset($counts->pending) ? $counts->pending : 0;
		$count = isset($counts->all) ? $counts->all : 0;

		$stats['stats'][ $stat_key ] = array(
			'icon' => 'fas fa-user-check',
			'label' => $stat_label,
			'value' => $count
		);

		$stats['chart_params']['ykeys'][] = $stat_key;
		$stats['chart_params']['labels'][] = $stat_label;

		if ( ! empty( $stats['chart_params']['data'] ) ) {
			foreach ( $stats['chart_params']['data'] as $key => $data ) {
				$stats['chart_params']['data'][$key][ $stat_key ] = 0;
			}
		}

		return $stats;
	}

}