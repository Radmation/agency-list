<?php
/**
 * GeoDirectory Advanced Search Filters
 *
 * @package           GeoDir_Advance_Search_Filters
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Advanced Search Filters
 * Plugin URI:        https://wpgeodirectory.com/downloads/advanced-search-add-on/
 * Description:       Allows to expand the default GeoDirectory search functionality by adding a range of filters.
 * Version:           2.3.5
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geodiradvancesearch
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65056
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! defined( 'GEODIR_ADV_SEARCH_VERSION' ) ) {
	define( 'GEODIR_ADV_SEARCH_VERSION', '2.3.5' );
}
if ( ! defined( 'GEODIR_ADV_SEARCH_MIN_CORE' ) ) {
	define( 'GEODIR_ADV_SEARCH_MIN_CORE', '2.3' );
}
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function geodir_load_advance_search_filters() {
    global $geodir_advance_search_filters;

	if ( ! defined( 'GEODIR_ADV_SEARCH_PLUGIN_FILE' ) ) {
		define( 'GEODIR_ADV_SEARCH_PLUGIN_FILE', __FILE__ );
	}

	// min core version check
	if( !function_exists("geodir_min_version_check") || !geodir_min_version_check("Advanced Search",GEODIR_ADV_SEARCH_MIN_CORE)){
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once ( plugin_dir_path( GEODIR_ADV_SEARCH_PLUGIN_FILE ) . 'includes/class-geodir-adv-search.php' );

    return $geodir_advance_search_filters = GeoDir_Adv_Search::instance();
}
add_action( 'geodirectory_loaded', 'geodir_load_advance_search_filters' );