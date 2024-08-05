<?php
/**
 * Deprecated functions that will soon be removed.
 */


/**
 * @deprecated
 */
function geodir_unset_location(){
    _deprecated_function( 'geodir_unset_location', '2.0.0' );
}

/**
 * @deprecated
 */
function geodir_set_location_var_in_session(){
    _deprecated_function( 'geodir_set_location_var_in_session', '2.0.0' );
}

/**
 * @deprecated
 */
function geodir_set_session_from_url(){
    _deprecated_function( 'geodir_set_session_from_url', '2.0.0' );
}

/**
 * @deprecated
 */
function geodir_set_location_var_in_session_autocompleter(){
    _deprecated_function( 'geodir_set_location_var_in_session_autocompleter', '2.0.0' );
}

/**
 * @deprecated
 */
function geodir_set_user_location_near_me(){
    _deprecated_function( 'geodir_set_user_location_near_me', '2.0.0' );
}

/**
 * @deprecated
 */
function gd_location_manager_set_user_location(){
    _deprecated_function( 'gd_location_manager_set_user_location', '2.0.0' );
}

/**
 * @deprecated
 */
function geodir_location_current_name(){
    _deprecated_function( 'geodir_location_current_name', '2.0.0' );
}

/**
 * Get review count or term count.
 *
 * @deprecated 2.0.0
 *
 * @param int|string $term_id The term ID.
 * @param string $taxonomy Taxonomy slug.
 * @param string $post_type The post type.
 * @param string $location_type Location type. Possible values 'gd_city','gd_region','gd_country'.
 * @param array $loc {
 *    Attributes of the location array.
 *
 *    @type string $gd_country The country slug.
 *    @type string $gd_region The region slug.
 *    @type string $gd_city The city slug.
 *
 * }
 * @param string $count_type Count type. Possible values are 'review_count', 'term_count'.
 * @return int|null|string
 */
function geodir_filter_listings_where_set_loc( $term_id, $taxonomy, $post_type, $location_type, $loc, $count_type ) {
	_deprecated_function( 'geodir_filter_listings_where_set_loc', '2.0.0' );

	return 0;
}

///**
// * @deprecated
// */
//function (){
//    _deprecated_function( '', '2.0.0' );
//}
