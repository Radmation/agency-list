<?php
/**
 * Pricing Manager Package Functions.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_pricing_default_package_id( $post_type, $create = true ) {
	return GeoDir_Pricing_Package::get_default_package_id( $post_type, $create );
}

function geodir_pricing_default_package( $post_type, $create = true ) {
	return GeoDir_Pricing_Package::get_default_package_id( $post_type, $create );
}

function geodir_pricing_get_packages( $args = array() ) {
	return GeoDir_Pricing_Package::get_packages( $args );
}

function geodir_pricing_field_packages( $field ) {
	return GeoDir_Pricing_Package::get_field_packages( $field );
}

function geodir_pricing_get_package( $package = null, $output = OBJECT, $filter = 'raw' ) {
	return GeoDir_Pricing_Package::get_package( $package, $output, $filter );
}

function geodir_pricing_get_meta( $package_id, $meta_key = '', $single = false ) {
	$meta = GeoDir_Pricing_Package::get_meta( $package_id, $meta_key, $single );

	$meta = apply_filters( 'geodir_pricing_get_meta', $meta, (int) $package_id, $meta_key, $single );

	if ( $meta_key && is_scalar( $meta_key ) ) {
		$meta = apply_filters( 'geodir_pricing_get_meta_' . $meta_key, $meta, (int) $package_id, $single );
	}

	return $meta;
}

function geodir_pricing_update_meta( $package_id, $meta_key, $meta_value, $prev_value = '' ) {
	return GeoDir_Pricing_Package::update_meta( $package_id, $meta_key, $meta_value, $prev_value );
}

function geodir_pricing_package_name( $package ) {
	return GeoDir_Pricing_Package::get_name( $package );
}

function geodir_pricing_package_title( $package ) {
	return GeoDir_Pricing_Package::get_title( $package );
}

function geodir_pricing_package_post_type( $package ) {
	return GeoDir_Pricing_Package::get_post_type( $package );
}

function geodir_pricing_package_post_status( $package = 0 ) {
	return GeoDir_Pricing_Package::get_post_status( $package );
}

function geodir_pricing_paid_post_status( $package = 0, $post_id = 0, $task = '' ) {
	$paid_post_status = geodir_pricing_package_post_status( $package );

	if ( $task == 'renew' && $post_id > 0 && ( $current_post_status = get_post_status( $post_id ) ) ) {
		$post_type = get_post_type( $post_id );

		// On renew set
		if ( in_array( $current_post_status, geodir_get_publish_statuses( array( 'post_type' => $post_type ) ) ) ) {
			$paid_post_status = $current_post_status;
		} elseif ( ! in_array( $current_post_status, geodir_get_publish_statuses( array( 'post_type' => $post_type ) ) ) ) {
			$paid_post_status = 'publish';
		}
	}

	return $paid_post_status;
}

function geodir_pricing_package_alive_days( $package, $trial = false ) {
	return GeoDir_Pricing_Package::get_alive_days( $package, $trial );
}

function geodir_pricing_package_desc_limit( $package ) {
	return GeoDir_Pricing_Package::get_desc_limit( $package );
}

function geodir_pricing_package_max_posts( $package ) {
	return GeoDir_Pricing_Package::get_max_posts( $package );
}

function geodir_pricing_has_upgrades( $package_id ) {
	return (int) geodir_pricing_get_meta( (int) $package_id, 'has_upgrades', true );
}

/**
 * Get the upgrade packages ids for the current package.
 *
 * @since 2.6.4
 *
 * @param int $package_id
 * @return array
 */
function geodir_pricing_upgrade_packages( $package_id ) {
	$packages = array();

	if ( ! geodir_pricing_has_upgrades( $package_id ) ) {
		return $packages;
	}

	$upgrade_packages = geodir_pricing_get_meta( (int) $package_id, 'upgrade_packages', true );

	if ( ! empty( $upgrade_packages ) && is_array( $upgrade_packages ) ) {
		$packages = $upgrade_packages;
	}

	return $packages;
}

function geodir_pricing_disable_html_editor( $package_id ) {
	$disable_html_editor = (bool) geodir_pricing_get_meta( (int) $package_id, 'disable_editor', true );

	return apply_filters( 'geodir_pricing_disable_html_editor', $disable_html_editor, (int) $package_id );
}

function geodir_pricing_is_private( $package_id ) {
	$is_private = (bool) geodir_pricing_get_meta( (int) $package_id, 'is_private', true );

	return apply_filters( 'geodir_pricing_package_is_private', $is_private, (int) $package_id );
}

function geodir_pricing_category_limit( $package_id ) {
	return (int) geodir_pricing_get_meta( (int) $package_id, 'category_limit', true );
}

function geodir_pricing_exclude_category( $package_id ) {
	return (array) geodir_pricing_get_meta( (int) $package_id, 'exclude_category', true );
}

function geodir_pricing_tag_limit( $package_id ) {
	return (int) geodir_pricing_get_meta( (int) $package_id, 'tag_limit', true );
}

function geodir_pricing_is_featured( $package_id ) {
	$post_type = geodir_pricing_package_post_type( $package_id );

	if ( ! GeoDir_Post_types::supports( $post_type, 'featured' ) ) {
		return false;
	}

	return (bool) GeoDir_Pricing_Package::check_field_visibility( true, 'featured', $package_id, $post_type );
}

function geodir_pricing_has_files( $package_id, $file_type = 'post_images' ) {
	return (bool) GeoDir_Pricing_Package::check_field_visibility( true, $file_type, $package_id );
}

function geodir_pricing_is_recurring( $package ) {
	return GeoDir_Pricing_Package::is_recurring( $package );
}

function geodir_pricing_add_listing_url( $package ) {
	return GeoDir_Pricing_Package::add_listing_url( $package );
}

/**
 * Get the upgrade packages setting options.
 *
 * @since 2.6.4
 *
 * @param string $post_type
 * @param int    $package_id
 * @return array
 */
function geodir_pricing_upgrade_package_options( $post_type, $package_id = 0 ) {
	$packages = geodir_pricing_get_packages( array( 'post_type' => $post_type ) );

	$options = array();
	if ( ! empty( $packages ) ) {
		foreach ( $packages as $key => $data ) {
			$skip = (int) $package_id == (int) $data->id ? true : false;

			/**
			 * Check to skip upgrade package options.
			 *
			 * @since 2.6.4
			 *
			 * @param bool   $skip
			 * @param string $post_type
			 * @param int    $package_id
			 */
			if ( apply_filters( 'geodir_pricing_package_skip_upgrade_package', $skip, $post_type, $package_id ) ) {
				continue;
			}

			$options[ (int) $data->id ] = __( stripslashes( $data->name ), 'geodirectory' );
		}
	}

	/**
	 * Filter the upgrade packages setting options.
	 *
	 * @since 2.6.4
	 *
	 * @param array  $options
	 * @param string $post_type
	 * @param int    $package_id
	 */
	return apply_filters( 'geodir_pricing_upgrade_package_options', $options, $post_type, $package_id );
}

/**
 * Check the package supports reviews or not.
 *
 * @since 2.6.6
 *
 * @param int $package_id The package id.
 * @return bool True when package supports reviews else False.
 */
function geodir_pricing_supports_reviews( $package_id ) {
	$disable_reviews = (bool) geodir_pricing_get_meta( (int) $package_id, 'disable_reviews', true );

	return apply_filters( 'geodir_pricing_supports_reviews', ! $disable_reviews, (int) $package_id );
}

/**
 * Check the current user can reply review.
 *
 * @since 2.7.2
 *
 * @param int    $post_id The post ID.
 * @param int    $package_id The package ID.
 * @param string $post_type The post type.
 * @return bool True if user can reply review else false.
 */
function geodir_pricing_can_reply_review( $post_id, $package_id = 0, $post_type = '' ) {
	$can_reply = GeoDir_Pricing_Post::can_reply_review( $post_id, $package_id, $post_type );

	return apply_filters( 'geodir_pricing_can_reply_review', $can_reply, (int) $post_id, (int) $package_id, $post_type );
}