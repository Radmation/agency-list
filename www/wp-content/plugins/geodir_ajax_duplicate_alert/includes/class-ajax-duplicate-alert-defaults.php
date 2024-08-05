<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Defaults.
 *
 * A place to store default values used in many places.
 *
 * @class    GeoDir_Defaults
 * @package  GeoDirectory/Classes
 * @category Class
 * @author   AyeCode
 */
class GD_Duplicate_Alert_Defaults extends GeoDir_Defaults{


	/**
	 * The default add_listing meta description.
	 *
	 * @return string
	 */
	public static function duplicate_alert_validation_message(){
		return __( 'A listing with this field already exists! Please make sure you are not adding a duplicate entry.' ,'geodir-duplicate-alert' );
	}

}


