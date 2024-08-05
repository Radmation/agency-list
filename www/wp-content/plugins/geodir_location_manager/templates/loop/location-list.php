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

if ( ! empty( $container_wrapper ) ) {
	$location_as_formated_list = "<" . $container_wrapper . " " . $container_wrapper_attr . ">";
}

if ( ! empty( $locations ) ) {
	foreach ( $locations as $location ) {
		// Translate country
		if ( ! empty( $location->country ) ) {
			$location->country = __( $location->country, 'geodirectory' );
		}

		if ( ! empty( $item_wrapper ) )
			$location_as_formated_list .= "<" . $item_wrapper . " " . $item_wrapper_attr . ">";

		if ( isset( $location->location_link ) ) {
			$location_as_formated_list .= "<a href='" . geodir_location_permalink_url( $base_location_link . $location->location_link ) . "'><i class='fas fa-caret-right'></i> ";
		}

		$location_as_formated_list .= $location->{$location_args['what']};

		if ( isset( $location->location_link ) ) {
			$location_as_formated_list .= "</a>";
		}

		if ( ! empty( $item_wrapper ) ) {
			$location_as_formated_list .= "</" . $item_wrapper . ">";
		}
	}
}

if ( ! empty( $container_wrapper ) ) {
	$location_as_formated_list .= "</" . $container_wrapper . ">";
}

echo $location_as_formated_list;