<?php
/**
 * Upgrade related functions.
 *
 * @since 1.0.0
 * @package GeoDirectory Location Manager
 * @global object $wpdb WordPress Database object.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

global $wpdb;

if ( get_option( 'geodir_location_db_version' ) != GEODIRLOCATION_VERSION ) {
    /**
     * Include custom database table related functions.
     *
     * @since 1.0.0
     * @package GeoDir_Location_Manager
     */
    add_action( 'plugins_loaded', 'geodir_location_upgrade_all', 10 );

    // Upgrade old options to new options before loading the rest GD options.
    if ( GEODIRLOCATION_VERSION <= '2.0.0' ) {
        add_action( 'init', 'geodir_location_upgrade_200' );
    }
}

/**
 * Handles upgrade for all geodirectory location manager versions.
 *
 * @since 1.0.0
 * @package GeoDir_Location_Manager
 */
function geodir_location_upgrade_all() {
}

/**
 * Handles upgrade for all geodirectory versions.
 *
 * @since 2.0.0
 * @package GeoDir_Location_Manager
 */
function geodir_location_upgrade_200() {
}

/**
 * Update for 2.0.1.0
 *
 * @since 2.0.1.0
 *
 * @return void
 */
function geodir_location_upgrade_2010() {
	// Check and add cpt_desc column in seo table.
	GeoDir_Location_SEO::check_column_cpt_desc();
}

/**
 * Update DB Version to 2.0.1.0.
 */
function geodir_location_update_2010_db_version() {
	GeoDir_Location_Admin_Install::update_db_version( '2.0.1.0' );
}