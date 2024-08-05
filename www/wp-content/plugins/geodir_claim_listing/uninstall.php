<?php
/**
 * GeoDirectory Claim Listings Uninstall
 *
 * Uninstalling Claim Listings deletes user roles, pages, tables, and options.
 *
 * @author      AyeCode Ltd
 * @package     Geodir_Claim_Listing
 * @version     1.0.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb, $plugin_prefix;

$geodir_settings = get_option( 'geodir_settings' );

wp_clear_scheduled_hook( 'geodir_claim_schedule_event_nudge_emails' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_claim_listing'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_CLAIM_LISTING' ) && true === GEODIR_UNINSTALL_CLAIM_LISTING ) ) {
	if ( empty( $plugin_prefix ) ) {
		$plugin_prefix = $wpdb->prefix . 'geodir_';
	}

	$claim_table = defined( 'GEODIR_CLAIM_TABLE' ) ? GEODIR_CLAIM_TABLE : $plugin_prefix . 'claim';
	$custom_fields_table = defined( 'GEODIR_CUSTOM_FIELDS_TABLE' ) ? GEODIR_CUSTOM_FIELDS_TABLE : $plugin_prefix . 'custom_fields';
	$custom_advance_search_fields_table = defined( 'GEODIR_ADVANCE_SEARCH_TABLE' ) ? GEODIR_ADVANCE_SEARCH_TABLE : $plugin_prefix . 'custom_advance_search_fields';
	$custom_sort_fields_table = defined( 'GEODIR_CUSTOM_SORT_FIELDS_TABLE' ) ? GEODIR_CUSTOM_SORT_FIELDS_TABLE : $plugin_prefix . 'custom_sort_fields';
	$tabs_layout_table = defined( 'GEODIR_TABS_LAYOUT_TABLE' ) ? GEODIR_TABS_LAYOUT_TABLE : $plugin_prefix . 'tabs_layout';
	$pricemeta_table = defined( 'GEODIR_PRICING_PACKAGE_META_TABLE' ) ? GEODIR_PRICING_PACKAGE_META_TABLE : $plugin_prefix . 'pricemeta';

	// Delete table
	$wpdb->query( "DROP TABLE IF EXISTS `{$claim_table}`" );

	if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$custom_fields_table}'" ) ) {
		$custom_fields_table = '';
	}
	if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$custom_advance_search_fields_table}'" ) ) {
		$custom_advance_search_fields_table = '';
	}
	if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$custom_sort_fields_table}'" ) ) {
		$custom_sort_fields_table = '';
	}
	if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$tabs_layout_table}'" ) ) {
		$tabs_layout_table = '';
	}
	if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$pricemeta_table}'" ) ) {
		$pricemeta_table = '';
	}

	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'claim_auto_approve',
			'claim_auto_approve_on_payment',
			'email_bcc_user_claim_request',
			'email_bcc_user_claim_approved',
			'email_bcc_user_claim_rejected',
			'email_bcc_user_claim_verify',
			'email_admin_claim_request',
			'email_admin_claim_request_subject',
			'email_admin_claim_request_body',
			'email_user_claim_request',
			'email_user_claim_request_subject',
			'email_user_claim_request_body',
			'email_user_claim_approved',
			'email_user_claim_approved_subject',
			'email_user_claim_approved_body',
			'email_user_claim_rejected',
			'email_user_claim_rejected_subject',
			'email_user_claim_rejected_body',
			'email_user_claim_verify',
			'email_user_claim_verify_subject',
			'email_user_claim_verify_body',
			'email_user_claim_nudge',
			'email_user_claim_nudge_on_publish',
			'email_user_claim_nudge_interval',
			'email_user_claim_nudge_subject',
			'email_user_claim_nudge_body',
			'uninstall_geodir_claim_listing'
		);

		$post_types = ! empty( $geodir_settings['post_types'] ) ? $geodir_settings['post_types'] : array();

		foreach ( $post_types as $post_type => $data ) {
			$detail_table = $plugin_prefix . $post_type . '_detail';

			$results = $wpdb->get_results("DESC {$detail_table}");
			if ( empty( $results ) ) {
				continue;
			}

			$columns = array();
			foreach ( $results as $key => $row ) {
				$columns[] = $row->Field;
			}

			// Delete detail table columns
			$delete_columns = array( 'claimed' );
			foreach ( $delete_columns as $delete_column ) {
				if ( in_array( $delete_column, $columns ) ) {
					$wpdb->query( "ALTER TABLE {$detail_table} DROP {$delete_column}" );
				}
			}
		}

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update GD plugin options.
		update_option( 'geodir_settings', $save_settings );
	}

	// Delete post meta
	$wpdb->query( "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` = '_geodir_claim_sent_on'" );

	// Delete custom fields
	if ( $custom_fields_table ) {
		$wpdb->query( "DELETE FROM {$custom_fields_table} WHERE htmlvar_name = 'claimed'" );
	}

	// Delete custom advance search fields
	if ( $custom_advance_search_fields_table ) {
		$wpdb->query( "DELETE FROM {$custom_advance_search_fields_table} WHERE htmlvar_name = 'claimed'" );
	}

	// Delete custom sort fields
	if ( $custom_sort_fields_table ) {
		$wpdb->query( "DELETE FROM {$custom_sort_fields_table} WHERE htmlvar_name = 'claimed'" );
	}

	// Delete tabs layout fields
	if ( $tabs_layout_table ) {
		$wpdb->query( "DELETE FROM {$tabs_layout_table} WHERE tab_key = 'claimed'" );
	}

	// Delete price package meta fields
	if ( $pricemeta_table ) {
		$wpdb->query( "DELETE FROM {$pricemeta_table} WHERE meta_key = 'claim_packages'" );
	}

	// Delete options
	delete_option( 'widget_gd_claim_post' );

	// Delete core options
	delete_option( 'geodir_claim_version' );
	delete_option( 'geodir_claim_db_version' );
	delete_option( 'geodirclaim_db_version' );
	
	// Clear any cached data that has been removed.
	wp_cache_flush();
}