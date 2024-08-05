<?php
/**
 * Pricing Manager Admin Functions.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_pricing_admin_params() {
	$params = array(
		'confirm_set_default' => __( 'Are you sure want to make this package as a default?', 'geodir_pricing' ),
		'confirm_delete_package' => __( 'Are you sure want to delete package?', 'geodir_pricing' ),
		'text_delete' => __( 'Delete', 'geodir_pricing' ),
		'text_deleting' => __( 'Deleting...', 'geodir_pricing' ),
		'text_deleted' => __( 'Deleted', 'geodir_pricing' ),
		'confirm_create_invoice' => __( 'Are you sure you want to create invoice for this listing?', 'geodir_pricing' ),
		'confirm_link_invoice' => ( geodir_get_option( 'pm_cart' ) == 'woocommerce' ? __( 'Are you sure you want to link a listing to WooCommerce order?', 'geodir_pricing' ) : __( 'Are you sure you want to link a listing to GetPaid invoice?', 'geodir_pricing' ) )
    );

    return apply_filters( 'geodir_pricing_admin_params', $params );
}

/**
 * Add the plugin to uninstall settings.
 *
 * @since 2.5.0
 *
 * @return array $settings the settings array.
 * @return array The modified settings.
 */
function geodir_pricing_uninstall_settings( $settings ) {
    array_pop( $settings );

	$settings[] = array(
		'name'     => __( 'Pricing Manager', 'geodir_pricing' ),
		'desc'     => __( 'Check this box if you would like to completely remove all of its data when Pricing Manager is deleted.', 'geodir_pricing' ),
		'id'       => 'uninstall_geodir_pricing_manager',
		'type'     => 'checkbox',
	);
	$settings[] = array( 
		'type' => 'sectionend',
		'id' => 'uninstall_options'
	);

    return $settings;
}

function geodir_pricing_diagnose_multisite_conversion( $table_arr ) {
	$table_arr['geodir_price'] = __( 'Packages', 'geodir_pricing' );
	$table_arr['geodir_post_packages'] = __( 'Listing Packages', 'geodir_pricing' );

	return $table_arr;
}

function geodir_pricing_post_type_saved( $post_type, $args, $new = false ) {
	if ( $new ) {		
		$package_id = geodir_pricing_default_package_id( $post_type, true );
	}
}

/**
 * Adds payment settings options that requires to add for translation.
 *
 * @package GeoDir_Pricing_Manager
 * @since 1.3.6
 *
 * @param array $gd_options GeoDirectory setting option names.
 * @param array Modified option names.
 */
function geodir_pricing_settings_to_translation( $gd_options = array() ) {
	$pm_options = array(
		'email_admin_renew_success_subject',
		'email_admin_renew_success_body',
		'email_admin_upgrade_success_subject',
		'email_admin_upgrade_success_body',
		'email_user_pre_expiry_reminder_subject',
		'email_user_pre_expiry_reminder_body',
		'email_user_renew_success_subject',
		'email_user_renew_success_body',
		'email_user_upgrade_success_subject',
		'email_user_upgrade_success_body',
		'email_user_post_downgrade_subject',
		'email_user_post_downgrade_body',
		'email_user_post_expire_subject',
		'email_user_post_expire_body',
	);

	$gd_options = array_merge( $gd_options, $pm_options );

	return $gd_options;
}

/**
 * Get price description text for translation.
 *
 * @since 1.3.6
 *
 * @global object $wpdb WordPress database abstraction object.
 *
 * @param  array $translations Array of text strings.
 * @return array
 */
function geodir_pricing_load_db_text_translation( $translations = array() ) {
	global $wpdb;

	if ( ! is_array( $translations ) ) {
		$translations = array();
	}

	$results = $wpdb->get_results( "SELECT name, title, description FROM `" . GEODIR_PRICING_PACKAGES_TABLE . "`" );

	if ( empty( $results ) ) {
		return $translations;
	}

	foreach ( $results as $row ) {
		if ( $row->name != '' ) {
			$translations[] = stripslashes_deep( $row->name );
		}

		if ( $row->title != '' ) {
			$translations[] = stripslashes_deep( $row->title );
		}

		if ( $row->description != '' ) {
			$translations[] = stripslashes_deep( $row->description );
		}
	}

	return $translations;
}

/**
 * Pricing manager diagnostic tools.
 *
 * @since 2.0.0
 */
function geodir_pricing_diagnostic_tools( $tools = array() ) {
	if ( geodir_pricing_is_post_expire_active() ) {
		if ( geodir_pricing_is_pre_expiry_reminder_active() ) {
			$tools['send_pre_expiry_emails'] = array(
				'name' => __( 'Send pre expiry reminders to user', 'geodir_pricing' ),
				'button' => __( 'Run', 'geodirectory' ),
				'desc' => __( 'Manually send pre expiry reminder emails to the users for the listings which are going to expire and the date falls in pre expiry reminder days period.', 'geodir_pricing' ),
				'callback' => 'geodir_pricing_send_pre_expiry_reminders'
			);
		}

		$tools['post_expire_check'] = array(
			'name' => __( 'Listing expire check', 'geodir_pricing' ),
			'button' => __( 'Run', 'geodirectory' ),
			'desc' => __( 'Manually run the listing expiration check function and marks the listing as expired which have expire date older than today.', 'geodir_pricing' ),
			'callback' => 'geodir_pricing_expire_check'
		);
	}

	return $tools;
}

function geodir_pricing_cfa_field_packages( $cf, $field ) {
	if ( ! empty( $field->is_default ) ) {
		$skip = true;
	} else {
		$skip = false;
	}

	if ( isset( $field->htmlvar_name ) ) {
		$skip = apply_filters( 'geodir_pricing_package_skip_exclude_field_' . $field->htmlvar_name, $skip, $field, array() );
	}

	if ( apply_filters( 'geodir_pricing_package_skip_exclude_field', $skip, $field, array() ) ) {
		return;
	}

	if ( has_filter( "geodir_pricing_cfa_field_packages_{$field->field_type}" ) ) {
		echo apply_filters( "geodir_pricing_cfa_field_packages_{$field->field_type}", '', $field->id, $cf, $field );
	} else {
		$packages = geodir_pricing_get_packages( array( 'post_type' => $field->post_type, 'fields' => array( 'id', 'name' ) ) );
		$value = geodir_pricing_field_packages( $field );

		$options = array();
		$all_values = array();

		foreach( $packages as $key => $package ){
			$pkg_id = absint($package->id);
			$options[$pkg_id] = wp_sprintf( __( '%s (ID: %d)', 'geodir_pricing' ), __( stripslashes( $package->name ), 'geodirectory' ), $pkg_id );
			$all_values[$pkg_id] = $pkg_id;
		}

		echo aui()->select(
			array(
				'id'                => 'show_on_pkg',
				'name'              =>  'show_on_pkg[]',
				'label_type'        => 'top',
				'multiple'   => true,
				'select2'    => true,
				'class'             => ' mw-100',
				'options'       => $options,
				'label'              => __( 'Show only on these price packages', 'geodir_pricing') . geodir_help_tip( __( 'Select for which packages you want to show this field.', 'geodir_pricing' )),
				'value'         => $field->id == 'new-1' ? $all_values : $value,
				'placeholder' => __('Select packages or field may not be shown', 'geodir_pricing' ),
			)
		);
	}
}

function geodir_pricing_cpt_cf_sanitize_custom_field( $field, $input ) {
	if ( in_array( 'show_on_pkg', array_keys( $input ) ) ) {
		// Display dummy data fields for all packages.
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'geodir_insert_dummy_data' && empty( $input['show_on_pkg'] ) ) {
			$items = geodir_pricing_get_packages( array( 'post_type' => $field->post_type, 'fields' => array( 'id', 'name' ) ) );

			$packages = array();
			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					$packages[] = $item->id;
				}
			}
		} else {
			$packages = GeoDir_Pricing_Package::sanitize_package_ids( $input['show_on_pkg'] );
		}
		$field->packages = ! empty( $packages ) ? implode( ',', $packages ) : '';
	}
	return $field;
}

function geodir_pricing_onsave_custom_field( $field_id ) {
	global $wpdb;

	$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE id = %d", array( $field_id ) ) );
	if ( empty( $field ) ) {
		return;
	}

	if ( ! empty( $field->is_default ) ) {
		$skip = true;
	} else {
		$skip = false;
	}

	$skip = apply_filters( 'geodir_pricing_package_skip_exclude_field_' . $field->htmlvar_name, $skip, $field, array() );

	if ( apply_filters( 'geodir_pricing_package_skip_exclude_field', $skip, $field, array() ) ) {
		return;
	}

	$results = geodir_pricing_get_packages( array( 'post_type' => $field->post_type, 'fields' => array( 'id' ), 'status' => 'all' ) );
	if ( ! empty( $results ) ) {
		$packages = geodir_pricing_field_packages( $field );

		foreach ( $results as $key => $row ) {
			$package_id = $row->id;

			$meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_PRICING_PACKAGE_META_TABLE . " WHERE meta_key = %s AND package_id = %d LIMIT 1", array( 'exclude_field', $package_id ) ) );

			$prev_value = ! empty( $meta ) ? GeoDir_Pricing_Package::sanitize_package_fields( $meta->meta_value, true ) : array();

			$new_value = $prev_value;
			if ( ! empty( $packages ) && in_array( $package_id, $packages ) ) { // Include
				if ( ! empty( $new_value ) ) {
					foreach ( $new_value as $key => $value ) {
						if ( $value == $field->htmlvar_name ) {
							unset( $new_value[ $key ] );
						}
					}
				}
			} else { // Exclude
				$new_value[] = $field->htmlvar_name;
			}

			$new_value = GeoDir_Pricing_Package::sanitize_package_fields( $new_value, true );

			$value = ! empty( $new_value ) ? implode( ',', $new_value ) : '';
			if ( implode( ',', $prev_value ) != $value ) {
				if ( ! empty( $meta ) ) {
					$wpdb->update( GEODIR_PRICING_PACKAGE_META_TABLE, array( 'meta_value' => $value ), array( 'meta_id' => $meta->meta_id ) );
				} else {
					$wpdb->insert( GEODIR_PRICING_PACKAGE_META_TABLE, array( 'meta_key' => 'exclude_field', 'meta_value' => $value, 'package_id' => $package_id ) );
				}

				do_action( 'geodir_pricing_package_fields_updated', $package_id, $prev_value, $new_value );
			}
		}
	}
}

function geodir_pricing_filter_default_fields( $fields, $post_type, $package_id ) {
	$pricing_default_fields = GeoDir_Pricing_Admin_Install::get_post_type_default_fields( $post_type );

	if ( ! empty( $pricing_default_fields ) ) {
		$fields = array_merge( $pricing_default_fields, $fields );
	}

	return $fields;
}

function geodir_pricing_cpt_db_columns( $columns, $cpt, $post_type ) {
	global $wpdb;

	$columns['package_id'] = "package_id int(11) DEFAULT '0'";
	$columns['expire_date'] = "expire_date DATE DEFAULT NULL";
	
	return $columns;
}

function geodir_pricing_event_check_default_package() {
	geodir_pricing_default_package_id( 'gd_event' );
}