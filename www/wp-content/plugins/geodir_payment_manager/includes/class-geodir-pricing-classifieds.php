<?php
/**
 * GeoDir_Pricing_Classifieds class
 *
 * @package GeoDir_Pricing_Manager
 * @since 2.6.1.3
 * @author AyeCode Ltd
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * GeoDir_Classifieds class.
 */
class GeoDir_Pricing_Classifieds {

	/**
	 * Setup.
	 */
	public static function init() {
		add_filter( 'geodir_get_post_stati', array( __CLASS__, 'filter_post_stati' ), 5, 3 );
	}

	public static function filter_post_stati( $statuses, $context, $args ) {
		if ( $context == 'renew' ) {
			$statuses[] = 'draft';
			$statuses[] = 'gd-expired';
		}

		return $statuses;
	}
}
