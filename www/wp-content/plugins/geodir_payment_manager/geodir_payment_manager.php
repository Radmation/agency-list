<?php
/**
 * GeoDirectory Pricing Manager
 *
 * @package           GeoDir_Pricing_Manager
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Pricing Manager
 * Plugin URI:        https://wpgeodirectory.com/downloads/pricing-manager/
 * Description:       Pricing Manager is a powerful price manager that allows you to monetize your directory quickly and easily via a pay per listing business model.
 * Version:           2.7.9
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geodir_pricing
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65868
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_PRICING_VERSION' ) ) {
	define( 'GEODIR_PRICING_VERSION', '2.7.9' );
}

if ( ! defined( 'GEODIR_PRICING_MIN_CORE' ) ) {
	define( 'GEODIR_PRICING_MIN_CORE', '2.3' );
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.5.0
 */
function geodir_load_pricing_manager() {
    global $geodir_pricing_manager;

	if ( ! defined( 'GEODIR_PRICING_PLUGIN_FILE' ) ) {
		define( 'GEODIR_PRICING_PLUGIN_FILE', __FILE__ );
	}

	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Pricing Manager', GEODIR_PRICING_MIN_CORE ) ) {
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once ( plugin_dir_path( GEODIR_PRICING_PLUGIN_FILE ) . 'includes/class-geodir-pricing.php' );

    return $geodir_pricing_manager = GeoDir_Pricing::instance();
}
add_action( 'geodirectory_loaded', 'geodir_load_pricing_manager' );