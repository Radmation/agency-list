<?php
/**
 * Deprecated functions
 *
 * Functions that no longer in use after v2.0.0.
 *
 * @author   AyeCode Ltd
 * @package  GeoDir_Custom_Posts\Functions
 * @version  2.0.0
 */

/**
 * Load geodirectory custom post types plugin textdomain.
 * @deprecated
 */
function geodir_load_translation_custom_posts(){
    _deprecated_function( 'geodir_load_translation_custom_posts', '2.0.0' );
}

/**
 * Submit form handler.
 * @deprecated
 */
function geodir_cp_from_submit_handler(){
    _deprecated_function( 'geodir_cp_from_submit_handler', '2.0.0' );
}

/**
 * Set post types order.
 * @deprecated
 */
function geodir_set_user_defined_order(){
    _deprecated_function( 'geodir_set_user_defined_order', '2.0.0', 'geodir_reorder_post_types()' );
}

/**
 * Create default fields.
 * @deprecated
 */
function geodir_cp_create_default_fields(){
    _deprecated_function( 'geodir_cp_create_default_fields', '2.0.0', 'geodir_reorder_post_types()' );
}

/**
 * Retrieve the post type archive permalink.
 * @deprecated
 */
function geodir_cpt_post_type_archive_link(){
    _deprecated_function( 'geodir_cpt_post_type_archive_link', '2.0.0' );
}

/**
 * Add a class to the `li` element of the listings list template.
 * @deprecated
 */
function geodir_cpt_post_view_class(){
    _deprecated_function( 'geodir_cpt_post_view_class', '2.0.0', 'geodir_cp_listing_cusotm_attrs()' );
}

/**
 * Adds the default location filter to the where clause.
 * @deprecated
 */
function geodir_cpt_allowed_location_where(){
    _deprecated_function( 'geodir_cpt_allowed_location_where', '2.0.0', 'GeoDir_CP_Query::check_location_where()' );
}

/**
 * Filter the location terms.
 * @deprecated
 */
function geodir_cpt_current_location_terms(){
    _deprecated_function( 'geodir_cpt_current_location_terms', '2.0.0', 'geodir_cp_current_location_terms()' );
}

/**
 * Filter the map should be displayed on detail page or not.
 * @deprecated
 */
function geodir_cpt_detail_page_map_is_display(){
    _deprecated_function( 'geodir_cpt_detail_page_map_is_display', '2.0.0', 'GeoDir_CP_Query::check_location_where()' );
}

/**
 * Outputs the listings template title.
 * @deprecated
 */
function geodir_cpt_listing_page_title(){
    _deprecated_function( 'geodir_cpt_listing_page_title', '2.0.0' );
}

/**
 * Remove filter on location change on search page.
 * @deprecated
 */
function geodir_cpt_remove_loc_on_search(){
    _deprecated_function( 'geodir_cpt_remove_loc_on_search', '2.0.0', 'GeoDir_CP_Query::pre_get_posts()' );
}

/**
 * Remove terms from location search request.
 * @deprecated
 */
function geodir_cpt_remove_location_search(){
    _deprecated_function( 'geodir_cpt_remove_location_search', '2.0.0', 'GeoDir_CP_Query::pre_get_posts()' );
}

/**
 * Filter the listing map should to be displayed or not.
 * @deprecated
 */
function geodir_cpt_remove_map_listing(){
    _deprecated_function( 'geodir_cpt_remove_map_listing', '2.0.0', 'GeoDir_CP_Post_Types::check_display_map()' );
}

/**
 * Replace the CPT meta description if set in the CPT settings.
 * @deprecated
 */
function geodir_pt_meta_desc(){
    _deprecated_function( 'geodir_pt_meta_desc', '2.0.0' );
}

/**
 * Replace the CPT description if set in the CPT settings.
 * @deprecated
 */
function geodir_cpt_pt_desc(){
    _deprecated_function( 'geodir_cpt_pt_desc', '2.0.0' );
}

/**
 * .
 * @deprecated
 */
function geodir_(){
    _deprecated_function( 'geodir_', '2.0.0', '' );
}

/**
 * Check physical location disabled.
 * @deprecated
 */
function geodir_cpt_no_location(){
    _deprecated_function( 'geodir_cpt_no_location', '2.0.0', 'GeoDir_Post_types::supports() & GeoDir_Taxonomies::supports()' );
}
