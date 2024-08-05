<?php
/**
 * GeoDirectory Custom Post Types
 *
 * @package           GeoDir_Custom_Posts
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Custom Post Types
 * Plugin URI:        https://wpgeodirectory.com/downloads/custom-post-types/
 * Description:       Allows to create multiple custom post types as you need, allowing you to divide categories and manage features and parameters per CPT.
 * Version:           2.3.5
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geodir_custom_posts
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65108
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_CP_VERSION' ) ) {
	define( 'GEODIR_CP_VERSION', '2.3.5' );
}

if ( ! defined( 'GEODIR_CP_MIN_CORE' ) ) {
	define( 'GEODIR_CP_MIN_CORE', '2.3' );
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
function geodir_load_custom_posts() {
    global $geodir_custom_posts;

	if ( ! defined( 'GEODIR_CP_PLUGIN_FILE' ) ) {
		define( 'GEODIR_CP_PLUGIN_FILE', __FILE__ );
	}

	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Custom Post Types', GEODIR_CP_MIN_CORE ) ) {
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once ( plugin_dir_path( GEODIR_CP_PLUGIN_FILE ) . 'includes/class-geodir-cp.php' );

    return $geodir_custom_posts = GeoDir_CP::instance();
}
add_action( 'geodirectory_loaded', 'geodir_load_custom_posts' );
