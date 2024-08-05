<?php
/**
 * GeoDirectory Location Manager
 *
 * @package           GeoDir_Location_Manager
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Location Manager
 * Plugin URI:        https://wpgeodirectory.com/downloads/location-manager/
 * Description:       Location Manager allows you to expand your directory and go global by creating unlimited locations for your listings.
 * Version:           2.3.12
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geodirlocation
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65853
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIRLOCATION_VERSION' ) ) {
	define( 'GEODIRLOCATION_VERSION', '2.3.12' );
}

if ( ! defined( 'GEODIRLOCATION_MIN_CORE' ) ) {
	define( 'GEODIRLOCATION_MIN_CORE', '2.3' );
}

/*
 * Set the activation hook for a plugin.
 */
register_activation_hook( __FILE__, function() {
	add_option( 'geodir_activate_location_manager', 1 );

	do_action( 'geodir_location_manager_on_activation' );
} );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function GeoDir_Location() {
    global $geodir_location_manager;

	if ( !defined( 'GEODIR_LOCATION_PLUGIN_FILE' ) ) {
		define( 'GEODIR_LOCATION_PLUGIN_FILE', __FILE__ );
	}

	// min core version check
	if( !function_exists("geodir_min_version_check") || !geodir_min_version_check("Location Manager",GEODIRLOCATION_MIN_CORE)){
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once ( plugin_dir_path( GEODIR_LOCATION_PLUGIN_FILE ) . 'includes/class-geodir-location-manager.php' );

    return $geodir_location_manager = GeoDir_Location_Manager::instance();
}
add_action( 'geodirectory_loaded', 'GeoDir_Location' );
