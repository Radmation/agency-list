<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Custom Post Types class.
 *
 * AJAX Event Handler.
 *
 * @class    GeoDir_CP_Post_Type
 * @package  GeoDir_Custom_Posts/Classes
 * @category Class
 * @author   AyeCode Ltd
 */
class GeoDir_CP_Post_Type {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'geodir_post_type_saved', array( __CLASS__, 'post_type_saved' ), 10, 3 );
	}

	public static function post_type_saved( $post_type, $args = array(), $new = false ) {
		self::register_post_type( $post_type, $args );
	}

	public static function register_post_type( $post_type, $args  ) {
		if ( ! is_blog_installed() || post_type_exists( $post_type ) ) {
			return;
		}
	
		register_post_type( $post_type, $args );
	}

	public static function register_taxonomy( $taxonomy, $args  ) {
		if ( ! is_blog_installed() || taxonomy_exists( $taxonomy ) ) {
			return;
		}
	
		register_taxonomy( $taxonomy, $args );
	}
}