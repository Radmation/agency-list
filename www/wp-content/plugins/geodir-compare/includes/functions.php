<?php
/**
 * Contains core functions
 *
 *
 * @since 1.0.0
 *
 */

/**
 * Get listing featured image
 * 
 * $param $listing_id Id of the listing
 * @param $size size of the image to retrieve
 * @return string
 */
function geodir_compare_get_listing_image( $listing_id, $size = 'thumbnail') {
	$image = get_the_post_thumbnail($listing_id, $size );

	/**
	 * Filters listing images
	 *
	 * @since 1.0.0
	 *
	*/
	$image = apply_filters( 'geodir_compare_get_listing_image', $image, $listing_id, $size );

	// if no image show a list icon
	if(!$image){
		$image = '<span class="gd-compare-image"><i class="fas fa-list"></i></span>';
	}

	return $image;

}


/**
 * Maximum number of items to compare in a list
 * 
 * @return int
 */
function geodir_compare_maximum_listings() {
	/**
	 * Filters maximum number of comparison items
	 *
	 * @since 1.0.0
	 *
	*/
	return apply_filters( 'geodir_compare_maximum_listings', 5 );
}