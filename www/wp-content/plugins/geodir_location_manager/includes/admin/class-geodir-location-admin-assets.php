<?php
/**
 * Load admin assets
 *
 * @author      AyeCode Ltd
 * @category    Admin
 * @package     GeoDir_Location_Manager/Admin
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GeoDir_Location_Admin_Assets', false ) ) :

/**
 * GeoDir_Location_Admin_Assets Class.
 */
class GeoDir_Location_Admin_Assets {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {


		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 10 );


	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {

		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$gd_screen_id 	= sanitize_title( __( 'GeoDirectory', 'geodirectory' ) );
		$page 			= ! empty( $_GET['page'] ) ? $_GET['page'] : '';

		// Register admin styles
		wp_register_style( 'geodir-location-admin-css', GEODIR_LOCATION_PLUGIN_URL . '/assets/css/location-admin.css', array(), GEODIRLOCATION_VERSION );

		// Admin styles for GD pages only
		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			wp_enqueue_style( 'geodir-location-admin-css' );
		}
	}


	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		global $wp_query, $post, $pagenow;

		$design_style = geodir_design_style();

		$screen       	= get_current_screen();
		$screen_id    	= $screen ? $screen->id : '';
		$gd_screen_id 	= sanitize_title( __( 'GeoDirectory', 'geodirectory' ) );
		$page 		  	= ! empty( $_GET['page'] ) ? $_GET['page'] : '';
		$suffix       	= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
		// Register scripts
		wp_register_script( 'geodir-location-script', GEODIR_LOCATION_PLUGIN_URL . '/assets/js/location-common' . $suffix . '.js', array( 'jquery', 'geodir-admin-script' ), GEODIRLOCATION_VERSION );
		wp_register_script( 'geodir-location-admin-script', GEODIR_LOCATION_PLUGIN_URL . '/assets/js/location-admin' . $suffix . '.js', array( 'jquery', 'geodir-admin-script' ), GEODIRLOCATION_VERSION );

		// Admin scripts for GD pages only
		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			if(!$design_style) {
				wp_enqueue_script( 'geodir-location-script' );
			}
			wp_enqueue_script( 'geodir-location-admin-script' );
			wp_localize_script( 'geodir-location-admin-script', 'geodir_location_params', geodir_location_params() );
		}
	}

}
endif;

return new GeoDir_Location_Admin_Assets();