<?php
/**
 * GeoDirectory Custom Post Types Admin Functions
 *
 * @author   AyeCode Ltd
 * @category Core
 * @package  GeoDir_Custom_Posts/Admin/Functions
 * @version  2.0.0
 */

function geodir_cp_admin_params() {
	$params = array(
		'confirm_delete_post_type' => __( 'Are you wish to delete this post type?', 'geodir_custom_posts' ),
		'aui' => geodir_design_style()
	);

	return apply_filters( 'geodir_cp_admin_params', $params );
}

function geodir_cp_skip_address_field_output( $skip, $field_id, $field, $cf ) {
	if ( ! empty( $field->post_type ) && ! GeoDir_Post_types::supports( $field->post_type, 'location' ) ) {
		$skip = true;
	}
	return $skip;
}

/**
 * Add the javascript to make cat icon upload optional.
 *
 * @since 1.1.7
 *
 * @global string $pagenow The current screen.
 *
 * @return string Print the inline script.
 */
function geodir_cp_admin_footer() {
	global $pagenow;
	if ( ( $pagenow == 'edit-tags.php' || $pagenow == 'term.php' ) && !empty( $_REQUEST['taxonomy'] ) && ! GeoDir_Taxonomies::supports( $_REQUEST['taxonomy'], 'location' ) ) {
		echo '<script type="text/javascript">jQuery(\'[name="ct_cat_icon[src]"]\', \'#addtag, #edittag\').removeClass(\'ct_cat_icon[src]\');jQuery(\'[name="ct_cat_icon[id]"]\', \'#addtag, #edittag\').closest(\'.form-field\').removeClass(\'form-required\').removeClass(\'form-invalid\');</script>';
	}
}

/**
 * Add the plugin to uninstall settings.
 *
 * @since 2.0.0
 *
 * @return array $settings the settings array.
 * @return array The modified settings.
 */
function geodir_cp_uninstall_settings( $settings ) {
    array_pop( $settings );

	$settings[] = array(
		'name'     => __( 'Custom Post Types', 'geodir_custom_posts' ),
		'desc'     => __( 'Check this box if you would like to completely remove all of its data when Custom Post Types is deleted.', 'geodir_custom_posts' ),
		'id'       => 'uninstall_geodir_custom_posts',
		'type'     => 'checkbox',
	);
	$settings[] = array( 
		'type' => 'sectionend',
		'id' => 'uninstall_options'
	);

    return $settings;
}