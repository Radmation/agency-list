<?php
/**
 * Display locations image grid view.
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/loop/location-grid.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDir_Location_Manager
 * @version    2.1.0.9
 */

defined( 'ABSPATH' ) || exit;
 
$location_as_formated_list = '';

if ( ! empty( $container_wrapper ) ) {
	$location_as_formated_list = "<div class='geodir-location-grid-container geodir-image-container '><" . $container_wrapper . " class='geodir-gallery geodir-images clearfix'>";
}

if ( ! empty( $locations ) ) {
	foreach ( $locations as $location ) {
		if ( ! empty( $item_wrapper ) ) {
			$location_as_formated_list .= "<" . $item_wrapper . " " . $item_wrapper_attr . ">";
		}

		if ( isset( $location->location_link ) ) {
			$location_as_formated_list .= "<a class='geodir-lightbox-image' href='" . geodir_location_permalink_url( $base_location_link . $location->location_link ). "' {$lightbox_attrs}>";
		}

		//$location_args['image_size'] = 'medium_large'; // Image size. 'thumbnail' or 'medium' or 'medium_large' or 'large' or 'full'.
		//$location_args['image_class'] = ''; // Image class.
		$image = GeoDir_Location_SEO::get_image_tag( $location, $location_args );

		// Translate country
		if ( ! empty( $location->country ) ) {
			$location->country = __( $location->country, 'geodirectory' );
		}
		$location_as_formated_list .= "<span class='gd-position-absolute geodir-location-name'>" . $location->{$location_args['what']} . "</span>";
		$location_as_formated_list .= $image;

		if ( isset( $location->location_link ) ) {
			$location_as_formated_list .= "</a>";
		}

		if ( ! empty( $item_wrapper ) ) {
			$location_as_formated_list .= "</" . $item_wrapper . ">";
		}
	}
}

if ( ! empty( $container_wrapper ) ) {
	$location_as_formated_list .= "</" . $container_wrapper . "></div>";
}

echo $location_as_formated_list;