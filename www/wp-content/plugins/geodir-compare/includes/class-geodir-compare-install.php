<?php
/**
 * Installation related functions and actions.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrades the database
 */
class Geodir_Compare_Install {

	/**
	 * Class constructor.
	 * 
	 * @var $current Integer. The current database version
	 */
	public function __construct( $current ) {
		global $wpdb;

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! defined( 'GEODIR_COMPARE_INSTALLING' ) ) {
			define( 'GEODIR_COMPARE_INSTALLING', true );
		}
		
		//Is this a fresh install?
		if( !$current ){
			$this->do_full_install();
		}

	}

	/**
	 * Does a full install of the plugin.
	 */
	private function do_full_install() {

		// Create plugin pages
		$this->create_pages();

	}
	/**
	 * Create pages that the plugin relies on
	 */
	public function create_pages() {

		/**
		 * Filters geodir_compare pages before they are created
		 */
		$pages = apply_filters( 'geodir_compare_create_pages', array(
			'compare' => array(
				'name'    => _x( 'compare', 'Page slug', 'geodir-compare' ),
				'title'   => _x( 'Compare Listings', 'Page title', 'geodir-compare' ),
				'content' => '[gd_compare_list]',
			),
		) );


		//Create the pages
		foreach ( $pages as $key=>$page ) {
			geodir_create_page( $page['name'], 'geodir_compare_listings_page', $page['title'], $page['content'] );
		}

	}

}
