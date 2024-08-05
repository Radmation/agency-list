<?php
/**
 * Admin View: Add/edit location
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $aui_bs5, $mapzoom;
$prefix 	= 'location_';
$map_title 	= __( "Set Address On Map", 'geodirlocation' );
$location_id 	= isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0;
$location 		= self::get_location_data( $location_id );
if ( ! empty( $location['location_id'] ) ) {
    $mapzoom = 10;
	$country = $location['country'];
	$region = $location['region'];
	$city = $location['city'];
	$lat = $location['latitude'];
	$lng = $location['longitude'];
}


echo '<div class="' . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . '">';
include( GEODIRECTORY_PLUGIN_DIR . 'templates/map.php' );
echo '</div>';