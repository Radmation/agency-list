<?php
/**
 * Claim Listings Core Functions.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_claim_get_statuses() {
	$statuses = array(
		'0' => _x( 'Pending', 'Claim status', 'geodir-claim' ),
		'1' => _x( 'Approved', 'Claim status', 'geodir-claim' ),
		'2' => _x( 'Rejected', 'Claim status', 'geodir-claim' )
	);

	return apply_filters( 'geodir_claim_get_statuses', $statuses );
}

function geodir_claim_status_name( $status ) {
	$statuses = geodir_claim_get_statuses();
	if ( ! empty( $statuses ) && isset( $statuses[ absint( $status ) ] ) ) {
		$status_name = $statuses[ absint( $status ) ];
	} else {
		$status_name = $status;
	}

	return apply_filters( 'geodir_claim_status_name', $status_name, $status );
}

function geodir_claim_event_nudge_emails( $args = array() ) {
	geodir_claim_send_nudge_emails( $args );
}

function geodir_claim_send_nudge_emails( $args = array() ) {
	global $wpdb;

	//if ( ! geodir_get_option( 'email_user_claim_nudge' ) ) {
		return;
	//}

	$interval = geodir_get_option( 'email_user_claim_nudge_interval' );
	if ( ! in_array( $interval, array( array_keys( GeoDir_Claim_Email::claim_nudge_intervals() ) ) ) ) {
		$interval = 'w';
	}

	switch ( $interval ) {
		case 'd':
			$days = 1;
			break;
		case 'f':
			$days = 14;
			break;
		case 'm':
			$days = 30;
			break;
		case 'w':
		default:
			$days = 7;
			break;
	}

	$days = apply_filters( 'geodir_claim_send_nudge_emails_interval_days', $days );

	$date_diff = strtotime( "-" . absint( $days ) . " days" );

	$post_types = geodir_get_posttypes( 'names' );

	foreach ( $post_types as $post_type ) {
		if ( apply_filters( 'geodir_claim_post_type_skip_nudge_email', false, $post_type ) ) {
			continue;
		}

		$cols = $wpdb->get_col( $wpdb->prepare( "SELECT `packages` FROM `" . GEODIR_CUSTOM_FIELDS_TABLE . "` WHERE `post_type` = %s AND ( `htmlvar_name` = %s OR `htmlvar_name` = %s ) AND `is_active` = 1 ORDER BY htmlvar_name ASC", array( $post_type, 'claimed', 'email') ) );
		if ( ! ( ! empty( $cols ) && count( $cols ) > 1 ) ) {
			continue;
		}

		$packages = ! empty( $cols[0] ) ? trim( $cols[0], ' ,' ) : "";

		if ( empty( $packages ) ) {
			continue;
		}

		$table = geodir_db_cpt_table( $post_type );

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT `p`.*, `pd`.* FROM `{$wpdb->posts}` AS `p` LEFT JOIN `{$table}` AS `pd` ON `pd`.`post_id` = `p`.`ID` LEFT JOIN `{$wpdb->postmeta}` AS `pm` ON ( `pm`.`post_id` = `p`.`ID` AND `pm`.`meta_key` = '_geodir_claim_sent_on' ) WHERE `p`.`post_type` = %s AND `pd`.`post_status` = %s AND `pd`.`claimed` != 1 AND `email` LIKE '%@%' AND `pd`.`package_id` IN ( {$packages} ) AND ( `pm`.`meta_value` IS NULL OR `pm`.`meta_value` < {$date_diff} ) ORDER BY `p`.`post_date` ASC", array( $post_type, 'publish' ) ) );
		
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				GeoDir_Claim_Email::send_user_claim_nudge_email( $row );
			}
		}
	}
}

