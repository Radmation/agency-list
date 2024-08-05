<?php
/**
 * GeoDirectory Claim Listings
 *
 * @package           Geodir_Claim_Listing
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Claim Listings
 * Plugin URI:        https://wpgeodirectory.com/downloads/claim-listings/
 * Description:       With Claim Listings addon, business owners can literally "claim" their listings, identify themselves as the business owner and get verified.
 * Version:           2.3
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geodir-claim
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65098
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_CLAIM_VERSION' ) ) {
	define( 'GEODIR_CLAIM_VERSION', '2.3' );
}

if ( ! defined( 'GEODIR_CLAIM_MIN_CORE' ) ) {
	define( 'GEODIR_CLAIM_MIN_CORE', '2.2' );
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function geodir_load_claim_listing() {
    global $geodir_claim_listing;

	if ( ! defined( 'GEODIR_CLAIM_PLUGIN_FILE' ) ) {
		define( 'GEODIR_CLAIM_PLUGIN_FILE', __FILE__ );
	}

	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Claim Listings', GEODIR_CLAIM_MIN_CORE ) ) {
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once ( plugin_dir_path( GEODIR_CLAIM_PLUGIN_FILE ) . 'includes/class-geodir-claim.php' );

    return $geodir_claim_listing = GeoDir_Claim::instance();
}
add_action( 'geodirectory_loaded', 'geodir_load_claim_listing' );