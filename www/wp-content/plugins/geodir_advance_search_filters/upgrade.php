<?php
/**
 * GeoDirectory Advance Search Filters upgrade functions.
 *
 * @author   AyeCode
 * @package  GeoDir_Advance_Search_Filters
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( get_option( 'geodir_advance_search_db_version' ) != GEODIR_ADV_SEARCH_VERSION ) {
	add_action( 'plugins_loaded', 'geodir_adv_search_upgrade_all', 10 );

    if ( GEODIR_ADV_SEARCH_VERSION <= '2.0.0' ) {
        add_action( 'init', 'geodir_adv_search_upgrade_200' );
    }
}

/**
 * Handles upgrade for all versions of the plugin.
 *
 * @since 1.0.0
 * 
 */
function geodir_adv_search_upgrade_all() {
}

/**
 * Handles upgrade for version < v2.0.0.
 *
 * @since 2.0.0
 * 
 */
function geodir_adv_search_upgrade_200() {

}

/**
 * Update for 2.0.1.0
 *
 * @since 2.0.1.0
 *
 * @return void
 */
function geodir_search_upgrade_2010() {
	global $wpdb;

	// Merge business hours.
	// Add columns in business hours table.
	$table = $wpdb->prefix . 'geodir_business_hours';

	if ( ! geodir_column_exist( $table, 'open_utc' ) ) {
		$wpdb->query( "ALTER TABLE `{$table}` 
			ADD open_utc int(9) UNSIGNED NOT NULL, 
			ADD close_utc int(9) UNSIGNED NOT NULL, 
			ADD open_dst int(9) UNSIGNED NOT NULL, 
			ADD close_dst int(9) UNSIGNED NOT NULL, 
			ADD timezone_string varchar(100) NOT NULL, 
			ADD has_dst tinyint(1) NOT NULL DEFAULT '0', 
			ADD is_dst tinyint(1) NOT NULL DEFAULT '0',
			ADD open_off int(9) UNSIGNED NOT NULL DEFAULT '0', 
			ADD close_off int(9) UNSIGNED NOT NULL DEFAULT '0', 
			ADD offset int(9) NOT NULL DEFAULT '0'" 
		);
	}

	// Update timezone to timezone string.
	if ( ! geodir_get_option( 'default_location_timezone_string' ) ) {
		$country = geodir_get_option( 'default_location_country' );
		$timezone = geodir_get_option( 'default_location_timezone' );
		$timezone_string = geodir_offset_to_timezone_string( $timezone, $country );
		geodir_update_option( 'default_location_timezone_string', $timezone_string );
	}

	geodir_search_merge_business_hours();

	// Set schedule
	geodir_search_schedule_events();
}

/**
 * Update DB Version to 2.0.1.0.
 */
function geodir_search_update_2010_db_version() {
	GeoDir_Adv_Search_Admin_Install::update_db_version( '2.0.1.0' );
}

/**
 * Update for 2.1.1.1
 *
 * @since 2.1.1.1
 *
 * @return void
 */
function geodir_search_upgrade_2111() {
	global $wpdb;

	// Add columns in business hours table.
	$table = $wpdb->prefix . 'geodir_business_hours';

	$force = false;
	if ( ! geodir_column_exist( $table, 'open_off' ) ) {
		$force = true;

		$wpdb->query( "ALTER TABLE `{$table}` 
			ADD open_off int(9) UNSIGNED NOT NULL DEFAULT '0', 
			ADD close_off int(9) UNSIGNED NOT NULL DEFAULT '0', 
			ADD offset int(9) NOT NULL DEFAULT '0'" 
		);
	}

	geodir_search_merge_business_hours( $force );
}

/**
 * Update DB Version to 2.1.1.1.
 */
function geodir_search_update_2111_db_version() {
	GeoDir_Adv_Search_Admin_Install::update_db_version( '2.1.1.1' );
}