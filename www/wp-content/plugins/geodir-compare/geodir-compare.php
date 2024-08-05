<?php
/**
 * GeoDirectory Compare Listings
 *
 * @package           GeoDir_Compare
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Compare Listings
 * Plugin URI:        https://wpgeodirectory.com/downloads/compare-listings/
 * Description:       Compare listings side by side and compare vital information.
 * Version:           2.2
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geodir-compare
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         724713
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_COMPARE_VERSION' ) ) {
	define( 'GEODIR_COMPARE_VERSION', '2.2' );
}

if ( ! defined( 'GEODIR_COMPARE_MIN_CORE' ) ) {
	define( 'GEODIR_COMPARE_MIN_CORE', '2.1.1.5' );
}

if ( ! defined( 'GEODIR_COMPARE_PLUGIN_FILE' ) ) {
	define( 'GEODIR_COMPARE_PLUGIN_FILE', __FILE__ );
}

/**
 * Begins execution of the plugin.
 * 
 * Loads the plugin after GD has been loaded
 * 
 * @since    1.0.0
 */
function geodir_load_geodir_compare() {

	// min core version check
	if( !function_exists("geodir_min_version_check") || !geodir_min_version_check("Compare Listings",GEODIR_COMPARE_MIN_CORE)){
		return '';
	}

	require_once ( plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ) . 'includes/class-geodir-compare.php' );
	GeoDir_Compare::instance();
}
add_action( 'geodirectory_loaded', 'geodir_load_geodir_compare' );

/**
 * Tells the user to install GeoDirectory, if they haven't
 *
 * @since    1.0.0
 */
function geodir_compare_check_if_geodir_is_installed() {

	//If this is not an admin page or GD is activated, abort early
	if ( !is_admin() || did_action( 'geodirectory_loaded' ) ) {
		return;
	}

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	$class   = 'notice notice-warning is-dismissible';
	$action  = 'install-plugin';
	$slug	 = 'geodirectory';
	$basename= 'geodirectory/geodirectory.php';

	//Ask the user to activate GD in case they have installed it. Otherwise ask them to install it
	if( is_plugin_inactive($basename) ){

		$activation_url = esc_url( 
			wp_nonce_url( 
				admin_url("plugins.php?action=activate&plugin=$basename"), 
				"activate-plugin_$basename" ) 
			);

		printf( 
			esc_html__( '%s requires the %sGeodirectory%s plugin to be installed and active. %sClick here to activate it.%s', 'geodir-compare' ),
			"<div class='$class'><p><strong>GeoDirectory Compare Listings",
			'<a href="https://wpgeodirectory.com" target="_blank" title=" GeoDirectory">', 
			'</a>', 
			"<a href='$activation_url'  title='GeoDirectory'>", 
			'</a></strong></p></div>' );

	}else{

		$install_url = esc_url( wp_nonce_url(
			add_query_arg(
				array(
					'action' => $action,
					'plugin' => $slug
				),
				admin_url( 'update.php' )
			),
			$action.'_'.$slug
		) );
		
		printf( 
			esc_html__( '%s requires the %sGeodirectory%s plugin to be installed and active. %sClick here to install it.%s', 'geodir-compare' ),
			"<div class='$class'><p><strong>GeoDirectory Compare Listings", 
			'<a href="https://wpgeodirectory.com" target="_blank" title=" GeoDirectory">', 
			'</a>', 
			"<a href='$install_url'  title='GeoDirectory'>",
			'</a></strong></p></div>' );

	}

}
add_action( 'admin_notices', 'geodir_compare_check_if_geodir_is_installed' );