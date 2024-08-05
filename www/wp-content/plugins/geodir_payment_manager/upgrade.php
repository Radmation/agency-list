<?php
/**
 * GeoDirectory Pricing Manager Upgrade.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 */

if ( get_option( 'geodir_pricing_db_version' ) != GEODIR_PRICING_VERSION ) {
	/**
     * Include custom database table related functions.
     *
     * @since 2.5.0
     * @package GeoDir_Pricing_Manager
     */
    add_action( 'plugins_loaded', 'geodir_pricing_upgrade_all', 10 );

    // Upgrade old options to new options before loading the rest GD options.
    if ( GEODIR_PRICING_VERSION <= '2.5.0.0' ) {
        add_action( 'init', 'geodir_pricing_upgrade_300' );
    }
}

/**
 * Upgrade for all versions.
 *
 * @since 2.0.0
 */
function geodir_pricing_upgrade_all() {
	
}

/**
 * Upgrade for 2.5.0 version.
 *
 * @since 2.5.0
 */
function geodir_pricing_upgrade_300() {
	
}
