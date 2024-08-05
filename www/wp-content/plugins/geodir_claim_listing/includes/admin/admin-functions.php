<?php
/**
 * Claim Listings Admin Functions.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_claim_admin_params() {
	$params = array(
		'confirm_approve_claim' => __( 'Are you sure want to approve claim request?', 'geodir-claim' ),
		'confirm_reject_claim' => __( 'Are you sure want to reject claim request?', 'geodir-claim' ),
		'confirm_undo_claim' => __( 'Are you sure want to undo?', 'geodir-claim' ),
		'confirm_delete_claim' => __( 'Are you sure want to delete claim request?', 'geodir-claim' ),
		'text_approve' => __( 'Approve', 'geodir-claim' ),
		'text_approving' => __( 'Approving...', 'geodir-claim' ),
		'text_approved' => __( 'Approved', 'geodir-claim' ),
		'text_reject' => __( 'Reject', 'geodir-claim' ),
		'text_rejecting' => __( 'Rejecting...', 'geodir-claim' ),
		'text_rejected' => __( 'Rejected', 'geodir-claim' ),
		'text_undo' => __( 'Undo', 'geodir-claim' ),
		'text_undoing' => __( 'Undo...', 'geodir-claim' ),
		'text_delete' => __( 'Delete', 'geodir-claim' ),
		'text_deleting' => __( 'Deleting...', 'geodir-claim' ),
		'text_deleted' => __( 'Deleted', 'geodir-claim' ),
    );

    return apply_filters( 'geodir_claim_admin_params', $params );
}

/**
 * Add the plugin to uninstall settings.
 *
 * @since 2.0.0
 *
 * @return array $settings the settings array.
 * @return array The modified settings.
 */
function geodir_claim_uninstall_settings( $settings ) {
    array_pop( $settings );

	$settings[] = array(
		'name'     => __( 'Claim Listings', 'geodir-claim' ),
		'desc'     => __( 'Check this box if you would like to completely remove all of its data when Claim Listings is deleted.', 'geodir-claim' ),
		'id'       => 'uninstall_geodir_claim_listing',
		'type'     => 'checkbox',
	);
	$settings[] = array( 
		'type' => 'sectionend',
		'id' => 'uninstall_options'
	);

    return $settings;
}

/**
 * Deletes the version number from the DB so install functions will run again.
 */
function geodir_claim_clear_version_number(){
	delete_option( 'geodir_claim_version' );
}

function geodir_claim_diagnose_multisite_conversion( $table_arr ) {
	$table_arr['geodir_claim'] = __( 'Claims', 'geodir-claim' );

	return $table_arr;
}

/**
 * Adds claim listings settings options that requires to add for translation.
 *
 * @since 2.0.0
 *
 * @param array $gd_options GeoDirectory setting option names.
 * @param array Modified option names.
 */
function geodir_claim_settings_to_translation( $gd_options = array() ) {
	$options = array(
		'email_admin_claim_request_subject',
		'email_admin_claim_request_body',
		'email_user_claim_request_subject',
		'email_user_claim_request_body',
		'email_user_claim_approved_subject',
		'email_user_claim_approved_body',
		'email_user_claim_rejected_subject',
		'email_user_claim_rejected_body',
		'email_user_claim_verify_subject',
		'email_user_claim_verify_body',
		'email_user_claim_nudge_subject',
		'email_user_claim_nudge_body'
	);

	$gd_options = array_merge( $gd_options, $options );

	return $gd_options;
}

function geodir_claim_filter_default_fields( $fields, $post_type, $package_id ) {
	$default_fields = GeoDir_Claim_Admin_Install::get_post_type_default_fields( $post_type );

	if ( ! empty( $default_fields ) ) {
		$fields = array_merge( $default_fields, $fields );
	}

	return $fields;
}

function geodir_claim_cpt_db_columns( $columns, $cpt, $post_type ) {
	global $wpdb;

	$columns['claimed'] = "claimed int(11) DEFAULT '0'";
	
	return $columns;
}

function geodir_claim_predefined_custom_fields( $predefined_fields, $post_type ) {
	$predefined_fields[ 'claimed' ] = array(
		'field_type'  => 'checkbox',
        'class'       => 'gd-checkbox',
		'icon'        => 'fas fa-user-check',
		'name'        => __( 'Claimed', 'geodir-claim' ),
		'description' => __( 'Add field to claim the listing.', 'geodir-claim' ),
		'single_use'  => 'claimed',
		'defaults'    => array(
			'data_type'          => 'TINYINT',
			'admin_title'        => __( 'Is Claimed?', 'geodirectory' ),
			'frontend_title'     => __( 'Business Owner/Associate?', 'geodir-claim' ),
			'frontend_desc'      => __( 'Mark listing as a claimed.', 'geodir-claim' ),
			'htmlvar_name'       => 'claimed',
			'is_active'          => true,
			'for_admin_use'      => false,
			'default_value'      => '0',
			'show_in'            => '',
			'is_required'        => false,
			'option_values'      => '',
			'validation_pattern' => '',
			'validation_msg'     => '',
			'required_msg'       => '',
			'field_icon'         => 'fas fa-user-check',
			'css_class'          => '',
			'cat_sort'           => true,
			'cat_filter'         => true,
			'single_use'         => true,
			'add_column' 		 => true
		)
	);

	return $predefined_fields;
}

/**
 * Get the counts for the claim listing statuses.
 *
 * @return stdClass
 */
function geodir_claim_count_claims(){
	global $wpdb;

	$cache = get_transient( 'geodir_claim_stats' );
	if ( $cache ) {
		return $cache;
	}

	$post_types = geodir_get_posttypes( 'names' );

	$approved = 0;
	$pending = 0;
	$rejected = 0;
	$_post_types = new stdClass();

	foreach ( $post_types as $post_type ) {
		// approved
		$_approved = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM " . GEODIR_CLAIM_TABLE . " WHERE post_type = %s AND status='1'", array( $post_type ) ) );

		// pending
		$_pending = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM " . GEODIR_CLAIM_TABLE . " WHERE post_type = %s AND status='0'", array( $post_type ) ) );

		// rejected
		$_rejected = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM " . GEODIR_CLAIM_TABLE . " WHERE post_type = %s AND status='2'", array( $post_type ) ) );

		$_post_type = new stdClass();
		$_post_type->all = ( $_approved + $_pending + $_rejected );
		$_post_type->approved = $_approved;
		$_post_type->pending = $_pending;
		$_post_type->rejected = $_rejected;

		$_post_types->{$post_type} = $_post_type;
		$approved += $_approved;
		$pending += $_pending;
		$rejected += $_rejected;
	}

	$counts = new stdClass();
	$counts->all = ( $approved + $pending + $rejected );
	$counts->approved = $approved;
	$counts->pending = $pending;
	$counts->rejected = $rejected;
	$counts->post_types = $_post_types;

	set_transient( 'geodir_claim_stats', $counts, 24 * HOUR_IN_SECONDS );

	return $counts;
}