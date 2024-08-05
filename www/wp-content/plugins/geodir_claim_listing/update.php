<?php
/**
 * Claim Listings Update
 *
 * Claim Listings update functions.
 *
 * @package GeoDir_Claim_Listing
 * @author AyeCode Ltd
 * @since 2.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update to 2.2.2
 *
 * @since 2.2.2
 *
 * @return void
 */
function geodir_claim_update_222() {
	// Create schedules.
	GeoDir_Claim_Admin_Install::create_cron_schedules();
}

/**
 * Update DB Version to 2.2.2.
*
 * @since 2.2.2
 *
 * @return void
 */
function geodir_claim_update_db_version_222() {
	GeoDir_Claim_Admin_Install::update_db_version( '2.1.1.1' );
}