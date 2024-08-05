<?php
/**
 * Display locations list view.
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/loop/location-list.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://wpgeodirectory.com/docs-v2/faq/customizing/#templates
 * @package GeoDirectory/Templates
 * @version 2.0.1.0
 */
 
$location_as_formated_list = '';


if ( ! empty( $locations ) ) {
	foreach ( $locations as $location ) {
		// Translate country
		if ( ! empty( $location->country ) ) {
			$location->country = __( $location->country, 'geodirectory' );
		}

		$btn_args['link'] =  isset( $location->location_link ) ? geodir_location_permalink_url( $base_location_link . $location->location_link ) : '';
		$btn_args['badge'] = $location->{$location_args['what']};

		$location_as_formated_list .= geodir_get_post_badge( 0, $btn_args );
			
		
	}
}


echo $location_as_formated_list;