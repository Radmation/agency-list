<?php
/**
 * Load admin assets
 *
 * @author      AyeCode Ltd
 * @category    Admin
 * @package     GeoDir_Custom_Posts/Admin
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GeoDir_CP_Admin_Assets', false ) ) {

/**
 * GeoDir_CP_Admin_Assets Class.
 */
class GeoDir_CP_Admin_Assets {

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
		global $post, $pagenow;

		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$gd_screen_id 	= sanitize_title( __( 'GeoDirectory', 'geodirectory' ) );
		$page 			= ! empty( $_GET['page'] ) ? $_GET['page'] : '';

		// Register styles
		wp_register_style( 'geodir-cp-admin', GEODIR_CP_PLUGIN_URL . '/assets/css/admin.css', array(), GEODIR_CP_VERSION );

		// Admin styles for GD pages only
		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			wp_enqueue_style( 'geodir-cp-admin' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		global $post, $pagenow;

		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$gd_screen_id 	= sanitize_title( __( 'GeoDirectory', 'geodirectory' ) );
		$page 			= ! empty( $_GET['page'] ) ? $_GET['page'] : '';

		$suffix       	= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
		// Register scripts
		wp_register_script( 'geodir-cp', GEODIR_CP_PLUGIN_URL . '/assets/js/script' . $suffix . '.js', array( 'jquery', 'geodir-admin-script' ), GEODIR_CP_VERSION );
		wp_register_script( 'geodir-cp-admin', GEODIR_CP_PLUGIN_URL . '/assets/js/admin' . $suffix . '.js', array( 'jquery', 'geodir-admin-script' ), GEODIR_CP_VERSION );
		wp_register_script( 'geodir-cp-widget', GEODIR_CP_PLUGIN_URL . '/assets/js/widget' . $suffix . '.js', array( 'jquery' ), GEODIR_CP_VERSION );

		// Admin scripts for GD pages only
		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			wp_enqueue_script( 'geodir-cp' );
			wp_localize_script( 'geodir-cp', 'geodir_cp_params', geodir_cp_params() );

			wp_enqueue_script( 'geodir-cp-admin' );
			wp_localize_script( 'geodir-cp-admin', 'geodir_cp_admin_params', geodir_cp_admin_params() );
		}

		// Script for backend widgets page only
		if ( $screen_id == 'widgets' ) {
			wp_enqueue_script( 'geodir-cp-widget' );
		}
	}
}
}

return new GeoDir_CP_Admin_Assets();