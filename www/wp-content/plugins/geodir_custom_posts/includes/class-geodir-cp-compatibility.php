<?php
/**
 * Compatibility functions for third party plugins.
 *
 * @since 2.0.0
 * @package GeoDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class GeoDir_CP_Compatibility {

	/**
	 * Initiate the compatibility class.
     *
     * @since 2.0.0
	 */
	public static function init() {

		/*######################################################
		Beaver Builder :: Fix widgets.
		######################################################*/
		add_action('wp_enqueue_scripts',array(__CLASS__,'beaver_builder'),100);
		

	}

	public static function beaver_builder(){
		if(isset($_REQUEST['fl_builder'])){
			$suffix       	= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_script( 'geodir-cp-widget', GEODIR_CP_PLUGIN_URL . '/assets/js/widget' . $suffix . '.js', array( 'jquery' ), GEODIR_CP_VERSION );
			wp_enqueue_script( 'geodir-cp-widget' );
		}
	}


}