<?php
/**
 * Plugin administration functions.
 *
 * @since 2.0.0
 * @package GeoDir_Advance_Search_Filters
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_adv_search_admin_params() {
	$params = array(
    );

    return apply_filters( 'geodir_adv_search_admin_params', $params );
}

/**
 * Add the plugin to uninstall settings.
 *
 * @since 2.0.0
 *
 * @return array $settings the settings array.
 * @return array The modified settings.
 */
function geodir_search_uninstall_settings( $settings ) {
    array_pop( $settings );

	$settings[] = array(
		'name'     => __( 'Advanced Search Filters', 'geodiradvancesearch' ),
		'desc'     => __( 'Check this box if you would like to completely remove all of its data when Advanced Search Filters is deleted.', 'geodiradvancesearch' ),
		'id'       => 'uninstall_geodir_advance_search_filters',
		'type'     => 'checkbox',
	);
	$settings[] = array( 
		'type' => 'sectionend',
		'id' => 'uninstall_options'
	);

    return $settings;
}

/**
 * Clear version number so install/upgrade functions will run.
 *
 * @since 2.0.0
 *
 */
function geodir_search_clear_version_number() {
	delete_option( 'geodir_advance_search_version' );
}

/**
 * Ahe array of GeoDirectory database tables that needs to diagnose for multisite.
 *
 * @since 2.0.0
 * @param array $table_arr The array of database tables to check.
 * @param array Filtered array of the database tables.
 */
function geodir_search_diagnose_multisite_conversion( $table_arr ) {
	$table_arr['custom_advance_search_fields'] = __( 'Advance Search Fields', 'geodiradvancesearch' );

	return $table_arr;
}