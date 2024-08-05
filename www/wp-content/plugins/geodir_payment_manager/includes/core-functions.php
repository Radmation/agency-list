<?php
/**
 * Pricing Manager Core Functions.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_pricing_pre_expiry_day_options() {
    $day_options       = array();
    $day_options[0]    = __( 'On Expiry Date', 'geodir_pricing' );
    $day_options[1]    = __( '1 day before Expire Date', 'geodir_pricing' );

    for ( $i = 2; $i <= 10; $i++ ) {
        $day_options[$i]   = wp_sprintf( __( '%d days before Expire Date', 'geodir_pricing' ), $i );
    }

    foreach ( array( 15, 30, 60 ) as $key => $value ) {
        $day_options[ $value ]   = wp_sprintf( __( '%d days before Expire Date', 'geodir_pricing' ), $value );
    }

    return apply_filters( 'geodir_pricing_pre_expiry_day_options', $day_options );
}

function geodir_pricing_cron_expire_check() {
	geodir_pricing_expire_check();
}

function geodir_pricing_expire_check() {
    global $wpdb;

	if ( ! geodir_pricing_is_post_expire_active() ) {
		return;
	}

	$today = date_i18n( 'Y-m-d' );
	$post_types = geodir_get_posttypes( 'names' );

	foreach ( $post_types as $post_type ) {
		if ( apply_filters( 'geodir_pricing_post_type_skip_expire_check', false, $post_type ) ) {
			continue;
		}
		$table = geodir_db_cpt_table( $post_type );

		$sql = $wpdb->prepare( "SELECT post_id, package_id FROM {$table} WHERE post_status = %s  AND LOWER( expire_date ) != %s  AND expire_date != '0000-00-00' AND %s > expire_date ORDER BY expire_date ASC", array( 'publish', 'never', $today ) );
		$results = $wpdb->get_results( $sql );

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				geodir_pricing_check_post_expiration( (int) $row->post_id, (int) $row->package_id );
			}
		}
	}

	do_action( 'geodir_pricing_after_expire_check' );
}

function geodir_pricing_check_post_expiration( $gd_post, $package_id = NULL ) {
	if ( ! empty( $gd_post ) && ! is_object( $gd_post ) ) {
		$gd_post = geodir_get_post_info( $gd_post );
	}

	if ( empty( $gd_post ) ) {
		return;
	}

	// Check for revision post.
	if ( ! ( ! empty( $gd_post->post_type ) && geodir_is_gd_post_type( $gd_post->post_type ) ) ) {
		return;
	}

	if ( ! ( ! empty( $gd_post->expire_date ) && ! geodir_pricing_date_never_expire( $gd_post->expire_date ) && strtotime( date_i18n( 'Y-m-d' ) ) > strtotime( $gd_post->expire_date ) ) ) {
		return;
	}

	if ( apply_filters( 'geodir_pricing_skip_check_post_expiration', false, $gd_post ) ) {
		return;
	}

	$post_id = (int) $gd_post->ID;
	$package_id = ! empty( $package_id ) ? (int) $package_id : (int) $gd_post->package_id;
	$cancel_at_period_end = get_post_meta( $post_id, '_gdpm_cancel_at_period_end', true ); // Cancel at the end of current period

	$package = geodir_pricing_get_package( $package_id );
	if ( empty( $package ) ) {
		return;
	}

	if ( empty( $package->recurring ) ) {
		update_post_meta( $post_id, '_gdpm_recurring', false );
	}

	if ( $cancel_at_period_end ) {
		// Force to expire
	} else if ( ! empty( $package->recurring ) || get_post_meta( $post_id, '_gdpm_recurring', true ) ) {
		return; // Don't expire recurring listing
	}

	do_action( 'geodir_pricing_before_check_post_expiration', $gd_post, $package );

	if ( ! empty( $package->downgrade_pkg ) && ( $downgrade_to = geodir_pricing_get_package( (int) $package->downgrade_pkg ) ) ) {
		geodir_pricing_post_downgrade( $gd_post, $downgrade_to, $package );
	} else {
		geodir_pricing_post_expire( $gd_post );
	}

	if ( $cancel_at_period_end ) {
		delete_post_meta( $post_id, '_gdpm_cancel_at_period_end' ); // Delete cancel at period end meta
	}

	do_action( 'geodir_pricing_after_check_post_expiration', $gd_post, $package );

	return true;
}

function geodir_pricing_post_downgrade( $gd_post, $downgrade_to, $package = array() ) {
	global $geodir_post_downgraded;

	if ( empty( $gd_post ) || empty( $downgrade_to ) ) {
		return false;
	}

	if ( ! is_object( $gd_post ) ) {
		$gd_post = geodir_get_post_info( $gd_post );
	}

	if ( empty( $gd_post ) ) {
		return false;
	}

	// Check for revision post.
	if ( ! ( ! empty( $gd_post->post_type ) && geodir_is_gd_post_type( $gd_post->post_type ) ) ) {
		return false;
	}

	if ( empty( $package ) ) {
		$package = (int) $gd_post->package_id;
	}

	if ( ! empty( $package ) && ! is_object( $package ) ) {
		$package = geodir_pricing_get_package( $package );
	}

	if ( ! empty( $package ) && $package->id != $downgrade_to->id ) {
		if ( apply_filters( 'geodir_pricing_skip_post_downgrade', false, $gd_post ) ) {
			return false;
		}

		$days = geodir_pricing_package_alive_days( $downgrade_to );
		$expire_date = geodir_pricing_new_expire_date( (int) $days );

		do_action( 'geodir_pricing_pre_post_downgrade', $gd_post, $downgrade_to, $package );
	
		$post_data = array();
		$post_data['ID'] = $gd_post->ID;
		if ( $gd_post->post_status != 'publish' ) {
			$post_data['post_status'] = 'publish';
		}
		$post_data['package_id'] = $downgrade_to->id;
		$post_data['expire_date'] = $expire_date;

		if ( GeoDir_Post_types::supports( $gd_post->post_type, 'featured' ) ) {
			if ( geodir_pricing_is_featured( $downgrade_to->id ) ) {
				$post_data['featured'] = 1; // Set featured
			} else {
				$post_data['featured'] = 0;
			}
		}

		// Manage post categories
		if ( geodir_pricing_category_limit( $downgrade_to->id ) > 0 || geodir_pricing_exclude_category( $downgrade_to->id ) ) {
			$post_data['default_category'] = $gd_post->default_category;
			$post_data['post_category' ] = wp_get_object_terms( $gd_post->ID, $gd_post->post_type . 'category', array( 'fields' => 'ids' ) );
		}

		// Manage post tags
		if ( geodir_pricing_tag_limit( $downgrade_to->id ) > 0 ) {
			$post_data['post_tags' ] = wp_get_object_terms( $gd_post->ID, $gd_post->post_type . '_tags', array( 'fields' => 'names' ) );
		}

		$post_data = apply_filters( 'geodir_pricing_downgrade_post_data', $post_data, $gd_post, $downgrade_to, $package );

		wp_update_post( $post_data );

		// Manage post files
		geodir_pricing_manage_files_on_downgrade( $gd_post, $downgrade_to, $package );

		if ( empty( $geodir_post_downgraded ) ) {
			$geodir_post_downgraded = array();
		}
		$geodir_post_downgraded[] = $gd_post->ID;

		do_action( 'geodir_pricing_post_downgraded', $gd_post, $downgrade_to, $package );

		return true;
	}

	return false;
}

function geodir_pricing_manage_files_on_downgrade( $gd_post, $downgrade_to, $package ) {
	$post_type = ! empty( $gd_post->post_type ) ? $gd_post->post_type : get_post_type( $gd_post->ID );

	$file_fields = GeoDir_Media::get_file_fields( $post_type );

	if ( ! empty( $file_fields) ) {
		foreach ( $file_fields as $file_type => $exts ) {
			if ( $file_type && $file_type != 'post_images' ) {
				// Custom files
				geodir_pricing_delete_files_on_downgrade( $gd_post, $downgrade_to, $package, $file_type );
			}
		}
	}

	// post_images
	geodir_pricing_delete_files_on_downgrade( $gd_post, $downgrade_to, $package, 'post_images' );
}

function geodir_pricing_delete_files_on_downgrade( $gd_post, $downgrade_to, $package, $file_type = 'post_images' ) {
	global $wpdb;

	// Delete all images when package has excluded post_images.
	if ( ! geodir_pricing_has_files( $downgrade_to->id, $file_type ) ) {
		if ( GeoDir_Media::delete_files( $gd_post->ID, $file_type ) !== false ) {
			if ( $file_type == 'post_images' ) {
				$save_field = 'featured_image';
			} else {
				$save_field = $file_type;
			}

			geodir_save_post_meta( $gd_post->ID, $save_field, '' );
		}
		return true;
	}

	if ( $file_type != 'post_images' ) {
		return;
	}

	$image_limit = (int) geodir_pricing_get_meta( $downgrade_to->id, 'image_limit', true );
	if ( empty( $image_limit ) ) {
		return;
	}

	$attachments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE post_id = %d AND type = 'post_images' ORDER BY featured DESC, menu_order ASC", array( $gd_post->ID ) ) );
	if ( empty( $attachments ) ) {
		return;
	}

	if ( count( $attachments ) <= $image_limit ) {
		return;
	}

	foreach ( $attachments as $i => $attachment ) {
		if ( $image_limit > $i ) {
			continue;
		}

		// Delete attachment
		GeoDir_Media::delete_attachment( $attachment->ID, $gd_post->ID, $attachment );
	}
}

function geodir_pricing_post_images_on_downgrade( $gd_post, $downgrade_to, $package ) {
	global $wpdb;

	// Delete all images when package has excluded post_images.
	if ( ! geodir_pricing_has_files( $downgrade_to->id ) ) {
		if ( GeoDir_Media::delete_files( $gd_post->ID, 'post_images' ) ) {
			geodir_save_post_meta( $gd_post->ID, 'featured_image', '' );
		}
		return true;
	}

	$image_limit = (int) geodir_pricing_get_meta( $downgrade_to->id, 'image_limit', true );
	if ( empty( $image_limit ) ) {
		return;
	}

	$attachments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE post_id = %d AND type = 'post_images' ORDER BY featured DESC, menu_order ASC", array( $gd_post->ID ) ) );
	if ( empty( $attachments ) ) {
		return;
	}

	if ( count( $attachments ) <= $image_limit ) {
		return;
	}

	foreach ( $attachments as $i => $attachment ) {
		if ( $image_limit > $i ) {
			continue;
		}

		// Delete attachment
		GeoDir_Media::delete_attachment( $attachment->ID, $gd_post->ID, $attachment );
	}
}

function geodir_pricing_post_expire( $gd_post ) {
	global $geodir_post_expired;

	if ( ! empty( $gd_post ) && ! is_object( $gd_post ) ) {
		$gd_post = geodir_get_post_info( $gd_post );
	}

	if ( empty( $gd_post ) ) {
		return false;
	}

	// Check for revision post.
	if ( ! ( ! empty( $gd_post->post_type ) && geodir_is_gd_post_type( $gd_post->post_type ) ) ) {
		return false;
	}

	if ( empty( $package ) ) {
		$package = (int) $gd_post->package_id;
	}

	if ( ! empty( $package ) && ! is_object( $package ) ) {
		$package = geodir_pricing_get_package( $package );
	}

	$expire_status = geodir_get_option( 'pm_listing_ex_status' );

	if ( ! empty( $expire_status ) && $expire_status != $gd_post->post_status ) {
		if ( apply_filters( 'geodir_pricing_skip_post_expire', false, $gd_post ) ) {
			return false;
		}

		do_action( 'geodir_pricing_pre_post_expire', $gd_post );

		$post_data = array();
		$post_data['ID'] = $gd_post->ID;
		$post_data['post_status'] = $expire_status;

		$post_data = apply_filters( 'geodir_pricing_expire_post_data', $post_data, $gd_post );

		wp_update_post( $post_data );

		if ( empty( $geodir_post_expired ) ) {
			$geodir_post_expired = array();
		}
		$geodir_post_expired[] = $gd_post->ID;

		do_action( 'geodir_pricing_post_expired', $gd_post );

		return true;
	}

	return false;
}

function geodir_pricing_cron_pre_expiry_reminders() {
	geodir_pricing_send_pre_expiry_reminders();
}

function geodir_pricing_send_pre_expiry_reminders() {
	global $wpdb;

	if ( ! geodir_pricing_is_post_expire_active() || ! geodir_pricing_is_pre_expiry_reminder_active() ) {
		return;
	}

	$reminder_days = geodir_pricing_pre_expiry_reminder_days();
	if ( empty( $reminder_days ) ) {
		return;
	}

	$today = date_i18n( 'Y-m-d' );
	$post_types = geodir_get_posttypes( 'names' );

	foreach ( $post_types as $post_type ) {
		if ( apply_filters( 'geodir_pricing_post_type_skip_pre_expiry_reminder', false, $post_type ) ) {
			continue;
		}
		$table = geodir_db_cpt_table( $post_type );

		$date_where = array();
		foreach ( $reminder_days as $day ) {
			if ( $day > 0 ) {
				$date = date_i18n( 'Y-m-d', strtotime( $today . ' +' . absint( $day ) . ' days' ) );
			} else {
				$date = date_i18n( 'Y-m-d' );
			}
			$date_where[] = $wpdb->prepare( "expire_date = %s", array( $date ) );
		}
		$date_where = "AND ( " . implode( " OR ", $date_where ) . " )";

		$sql = $wpdb->prepare( "SELECT post_id, package_id FROM {$table} WHERE post_status = %s {$date_where} ORDER BY expire_date ASC", array( 'publish' ) );
		$results = $wpdb->get_results( $sql );

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$sent = get_post_meta( $row->post_id, '_geodir_reminder_sent', true );

				if ( ! ( ! empty( $sent ) && in_array( $today, $sent ) ) ) {
					$recurring = geodir_pricing_is_recurring( (int) $row->package_id );

					if ( empty( $recurring ) ) {
						update_post_meta( $row->post_id, '_gdpm_recurring', false );
					}

					if ( ! empty( $recurring ) || get_post_meta( $row->post_id, '_gdpm_recurring', true ) ) { // Don't send expiration for recurring listing
						if ( get_post_meta( $row->post_id, '_gdpm_cancel_at_period_end', true ) ) {
							// Cancel at the end of current period
						} else {
							continue;
						}
					}

					geodir_pricing_send_pre_expiry_reminder( $row->post_id );
				}
			}
		}
	}
}

function geodir_pricing_send_pre_expiry_reminder( $gd_post, $data = array() ) {
	if ( ! empty( $gd_post ) && ! is_object( $gd_post ) ) {
		$gd_post = geodir_get_post_info( $gd_post );
	}

	if ( empty( $gd_post ) ) {
		return;
	}

	// Check for revision post.
	if ( ! ( ! empty( $gd_post->post_type ) && geodir_is_gd_post_type( $gd_post->post_type ) ) ) {
		return;
	}

	if ( ! ( ! empty( $gd_post->expire_date ) && ! geodir_pricing_date_never_expire( $gd_post->expire_date ) ) ) {
		return;
	}

	return GeoDir_Pricing_Email::send_user_pre_expiry_reminder_email( $gd_post );
}

function geodir_pricing_period_in_days( $period, $unit ) {
    $period = absint( $period );

	if ( in_array( strtolower( $unit ), array( 'w', 'week', 'weeks' ) ) ) {
		$days = $period * 7;
	} else if ( in_array( strtolower( $unit ), array( 'm', 'month', 'months' ) ) ) {
		$days = $period * 30;
	} else if ( in_array( strtolower( $unit ), array( 'y', 'year', 'years' ) ) ) {
		$days = $period * 365;
	} else {
		$days = $period;
	}

    return $days;
}

function geodir_pricing_time_diff( $from, $to = '' ) {
	if ( empty( $from ) ) {
		$from = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
	}
	if ( ! is_int( $from ) ) {
		$from = strtotime( $from );
	}

	if ( empty( $to ) ) {
		$to = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
	}
	if ( ! is_int( $to ) ) {
		$to = strtotime( $to );
	}

	$diff = (int) abs( $to - $from );

	if ( $diff >= YEAR_IN_SECONDS ) {
		$years = round( $diff / YEAR_IN_SECONDS );
		
		if ( $years <= 1 ) {
			$years = 1;
		}
		
		if ( $to <= $from ) {
			$since = sprintf( _n( '%s year left', '%s years left', $years, 'geodir_pricing' ), $years );
		} else {
			$since = sprintf( _n( '%s year overdue', '%s years overdue', $years, 'geodir_pricing' ), $years );
		}
	} else {
		$days = round( $diff / DAY_IN_SECONDS );
		
		if ( $days <= 1 ) {
			$days = 1;
		}
		
		if ( $to == $from ) {
			$since = __( 'today', 'geodir_pricing' );
		} elseif ( $to < $from ) {
			$since = sprintf( _n( '%s day left', '%s days left', $days, 'geodir_pricing' ), $days );
		} else {
			$since = sprintf( _n( '%s day overdue', '%s days overdue', $days, 'geodir_pricing' ), $days );
		}
	}

	return $since;
}

function geodir_pricing_post_has_renew_period( $post_id, $expire_date = '' ) {
	if ( ! geodir_pricing_is_pre_expiry_reminder_active() ) {
		return false;
	}

	if ( empty( $expire_date ) ) {
		$expire_date = geodir_get_post_meta( $post_id, 'expire_date', true );
	}

	$return = false;
	if ( ! geodir_pricing_date_never_expire( $expire_date ) ) {
		$days_diff = geodir_pricing_pre_expiry_reminder_days_max();
		$check_expire_date = date_i18n( 'Y-m-d', strtotime( $expire_date . ' - ' . (int) $days_diff . ' days' ) );

		if ( strtotime( $check_expire_date ) <= strtotime( date_i18n( 'Y-m-d' ) ) ) {
			$return = true;
		}
	}

	if ( $return && ( $package_id = (int) geodir_get_post_meta( (int) $post_id, 'package_id', true ) ) ) {
		$package = geodir_pricing_get_package( $package_id );

		if ( ! empty( $package ) && ! empty( $package->recurring ) ) {
			$return = false; // Listing with active subscription will be auto renewed when payment received at Payment Gateway site.
		}
	}

	return apply_filters( 'geodir_pricing_post_has_renew_period', $return );
}

function geodir_pricing_is_post_expire_active() {
    $active = (bool)geodir_get_option( 'pm_listing_expiry' );

    return apply_filters( 'geodir_pricing_is_post_expire_active', $active );
}

function geodir_pricing_is_pre_expiry_reminder_active() {
    $active = (bool)geodir_get_option( 'email_user_pre_expiry_reminder' );

    return apply_filters( 'geodir_pricing_is_pre_expiry_reminder_active', $active );
}

function geodir_pricing_pre_expiry_reminder_days() {
	$days = (array)geodir_get_option( 'email_user_pre_expiry_reminder_days' );

    return apply_filters( 'geodir_pricing_pre_expiry_reminder_days', $days );
}

function geodir_pricing_pre_expiry_reminder_days_max() {
	$reminder_days = geodir_pricing_pre_expiry_reminder_days();

	if ( empty( $reminder_days ) || ! is_array( $reminder_days ) ) {
		return 0;
	}

	$days_max = max( $reminder_days );

	return apply_filters('geodir_pricing_pre_expiry_reminder_days_max', $days_max );
}

/**
 * Check whether free renewal allowed or not.
 *
 * @since 2.5.0
 *
 * @param int $package_id The package ID. Default 0.
 * @return bool True is free renewal is allowed.
 */
function geodir_pricing_package_has_free_renewal( $package_id = 0 ) {
    $return = true;

    if ( ! empty( $package_id ) && ( $package = geodir_pricing_get_package( (int) $package_id ) ) ) {
        if ( isset( $package->amount ) && ! ( (float)$package->amount > 0 ) ) {
            $return = (bool)geodir_get_option( 'pm_free_package_renew' ) ? true : false;
        }
    }

    return apply_filters( 'geodir_pricing_package_has_free_renewal', $return, $package_id );
}

/**
 * Trim trailing zeros of price.
 *
 * @param string|float|int $price Price.
 * @return string
 */
function geodir_pricing_trim_zeros( $price ) {
	return preg_replace( '/' . preg_quote( geodir_pricing_decimal_separator(), '/' ) . '0++$/', '', $price );
}

/**
 * Check & set expired listing.
 *
 * @since 2.5.0
 *
 * @global object $wp WordPress object.
 * @global object $post The current post object.
 * @global object $wp_query WP_Query object.
 * @global object $gd_expired True if expired else false.
 */
function geodir_pricing_set_post_expired() {
    global $wp, $post, $wp_query, $gd_expired;
   
    if ( is_404() && $gd_expired ) {
        $the_post = get_post( $gd_expired );
        
        if ( ! ( !empty( $the_post ) && geodir_is_gd_post_type( $the_post->post_type ) ) ) {
            return;
        }
        
        $default_category_id = geodir_get_post_meta( $the_post->ID, 'default_category', true );
        $term = $default_category_id ? get_term( $default_category_id, $the_post->post_type . 'category' ) : '';
        $default_category = !empty( $term ) && ! is_wp_error( $term ) ? $term->slug : '';
        
        $post = $the_post;

		// $wp_query->query
        $wp_query->query['error'] = '';
        if ( $default_category ) {
            $wp_query->query[$the_post->post_type . 'category'] = $default_category;
        }
        $wp_query->query[$the_post->post_type] = $the_post->post_name;
        $wp_query->query['name'] = $the_post->post_name;
        $wp_query->query['post_type'] = $the_post->post_type;
        $wp_query->query['gd_is_geodir_page'] = true;
        
		// $wp_query->query_vars
        $wp_query->query_vars['error'] = '';
        if ( $default_category ) {
            $wp_query->query_vars[$the_post->post_type . 'category'] = $default_category;
        }
        $wp_query->query_vars[$the_post->post_type] = $the_post->post_name;
        $wp_query->query_vars['name'] = $the_post->post_name;
        $wp_query->query_vars['post_type'] = $the_post->post_type;
        $wp_query->query_vars['gd_is_geodir_page'] = true;
        if ( GeoDir_Post_types::supports( $the_post->post_type, 'location' ) ) {
            $wp->query_vars['country'] = geodir_get_post_meta( $the_post->ID, 'country', true );
			$wp->query_vars['region'] = geodir_get_post_meta( $the_post->ID, 'region', true );
			$wp->query_vars['city'] = geodir_get_post_meta( $the_post->ID, 'city', true );
        }

		// $wp_query
        $wp_query->gd_is_geodir_page = true;
        $wp_query->post_type = $the_post->post_type;
        $wp_query->queried_object = $the_post;
        $wp_query->queried_object_id = $the_post->ID;
        $wp_query->name = $the_post->post_name;
        $wp_query->posts = array( $the_post );
        $wp_query->post_count = 1;
        $wp_query->current_post = -1;
        $wp_query->post = $the_post;
        $wp_query->found_posts = true;
        $wp_query->is_404 = false;
        $wp_query->is_single = true;
        $wp_query->is_singular = true;
        $wp_query->is_expired = $the_post->ID;
    }
}

/**
 * Check & filtere the current listing it has expired date.
 *
 * @since 2.0.0
 *
 * @param object $wp WordPress object.
 * @param bool $gd_expired True if expired else false.
 * @param object $wp_query WP_Query object.
 *
 * @param object $posts The current posts object.
 * @param object $query WP_Query object.
 * @return object The filtered post object.
 */
function geodir_pricing_check_post_expired( $posts, $query ) {
    global $wp, $gd_expired, $wp_query;

	if ( empty( $posts ) || empty( $query->is_single ) ) {
		return $posts;
	}

    if ( ! empty( $posts[0]->post_status ) && geodir_is_gd_post_type( $posts[0]->post_type ) && $posts[0]->post_status == 'gd-expired' ) {
		$gd_expired = $posts[0]->ID;
		$wp_query->is_single = true;
    }

    return $posts;
}

function geodir_pricing_lifetime_units( $package = array() ) {
	$units = array(
		'D' => __( 'Day(s)', 'geodir_pricing' ),
		'W' => __( 'Week(s)', 'geodir_pricing' ),
		'M' => __( 'Month(s)', 'geodir_pricing' ),
		'Y' => __( 'Year(s)', 'geodir_pricing' ),
	);

	return apply_filters( 'geodir_pricing_package_lifetime_units', $units, $package );
}

function geodir_pricing_unit_title( $unit, $plural = true, $translated = true ) {
	if ( $translated ) {
		switch ( $unit ) {
			case 'D':
				$title = $plural ? __( 'days', 'geodir_pricing' ) : __( 'day', 'geodir_pricing' );
				break;
			case 'W':
				$title = $plural ? __( 'weeks', 'geodir_pricing' ) : __( 'week', 'geodir_pricing' );
				break;
			case 'M':
				$title = $plural ? __( 'months', 'geodir_pricing' ) : __( 'month', 'geodir_pricing' );
				break;
			case 'Y':
				$title = $plural ? __( 'years', 'geodir_pricing' ) : __( 'year', 'geodir_pricing' );
				break;
			default:
				$title = $unit;
				break;
		}
	} else {
		switch ( $unit ) {
			case 'D':
				$title = $plural ? 'days' : 'day';
				break;
			case 'W':
				$title = $plural ? 'weeks' : 'week';
				break;
			case 'M':
				$title = $plural ? 'months' : 'month';
				break;
			case 'Y':
				$title = $plural ? 'years' : 'year';
			default:
				$title = $unit;
				break;
		}
	}

	return apply_filters( 'geodir_pricing_unit_title', $title, $unit, $plural, $translated );
}

function geodir_pricing_display_lifetime( $value, $unit, $translated = true ) {
	$units = geodir_pricing_lifetime_units();

	$value = absint( $value );
	$unit = ! empty( $unit ) ? strtoupper( $unit ) : '';
	if ( ! isset( $units[ $unit ] ) || empty( $value ) ) {
		$lifetime = $translated ? __( 'Unlimited', 'geodir_pricing' ) : 'Unlimited';
	} else {
		if ( $translated ) {
			switch ( $unit ) {
				case 'D':
					$lifetime = wp_sprintf( _n( '%d day', '%d days', $value, 'geodir_pricing' ), $value );
					break;
				case 'W':
					$lifetime = wp_sprintf( _n( '%d week', '%d weeks', $value, 'geodir_pricing' ), $value );
					break;
				case 'M':
					$lifetime = wp_sprintf( _n( '%d month', '%d months', $value, 'geodir_pricing' ), $value );
					break;
				case 'Y':
					$lifetime = wp_sprintf( _n( '%d year', '%d years', $value, 'geodir_pricing' ), $value );
					break;
			}
		} else {
			switch ( $unit ) {
				case 'D':
					$lifetime = $value > 1 ? wp_sprintf( '%d days', $value ) : wp_sprintf( '%s day', $value );
					break;
				case 'W':
					$lifetime = $value > 1 ? wp_sprintf( '%d weeks', $value ) : wp_sprintf( '%d week', $value );
					break;
				case 'M':
					$lifetime = $value > 1 ? wp_sprintf( '%d months', $value ) : wp_sprintf( '%d month', $value );
					break;
				case 'Y':
					$lifetime = $value > 1 ? wp_sprintf( '%d years', $value ) : wp_sprintf( '%d year', $value );
			}
		}
	}

	return apply_filters( 'geodir_pricing_display_lifetime', $lifetime, $value, $unit, $translated );
}

function geodir_pricing_display_free_trial( $value, $unit, $translated = true ) {
	$units = geodir_pricing_lifetime_units();

	$value = absint( $value );
	$unit = ! empty( $unit ) ? strtoupper( $unit ) : '';
	if ( ! isset( $units[ $unit ] ) || empty( $value ) ) {
		$free_trial = '';
	} else {
		if ( $translated ) {
			switch ( $unit ) {
				case 'D':
					$free_trial = wp_sprintf( _n( '%d day', '%d days', $value, 'geodir_pricing' ), $value );
					break;
				case 'W':
					$free_trial = wp_sprintf( _n( '%d week', '%d weeks', $value, 'geodir_pricing' ), $value );
					break;
				case 'M':
					$free_trial = wp_sprintf( _n( '%d month', '%d months', $value, 'geodir_pricing' ), $value );
					break;
				case 'Y':
					$free_trial = wp_sprintf( _n( '%d year', '%d years', $value, 'geodir_pricing' ), $value );
					break;
			}
		} else {
			switch ( $unit ) {
				case 'D':
					$free_trial = $value > 1 ? wp_sprintf( '%d days', $value ) : wp_sprintf( '%s day', $value );
					break;
				case 'W':
					$free_trial = $value > 1 ? wp_sprintf( '%d weeks', $value ) : wp_sprintf( '%d week', $value );
					break;
				case 'M':
					$free_trial = $value > 1 ? wp_sprintf( '%d months', $value ) : wp_sprintf( '%d month', $value );
					break;
				case 'Y':
					$free_trial = $value > 1 ? wp_sprintf( '%d years', $value ) : wp_sprintf( '%d year', $value );
			}
		}
	}

	return apply_filters( 'geodir_pricing_display_free_trial', $free_trial, $value, $unit, $translated );
}

function geodir_pricing_table_display_lifetime( $value, $unit, $translated = true ) {
	$units = geodir_pricing_lifetime_units();

	$value = absint( $value );
	$unit = ! empty( $unit ) ? strtoupper( $unit ) : '';
	if ( ! isset( $units[ $unit ] ) || empty( $value ) ) {
		$lifetime = $translated ? __( 'lifetime', 'geodir_pricing' ) : 'lifetime';
	} else {
		if ( $translated ) {
			switch ( $unit ) {
				case 'D':
					$lifetime = $value > 1 ? wp_sprintf( __( '%d days', 'geodir_pricing' ), $value ) : __( 'day', 'geodir_pricing' );
					break;
				case 'W':
					$lifetime = $value > 1 ? wp_sprintf( __( '%d weeks', 'geodir_pricing' ), $value ) : __( 'week', 'geodir_pricing' );
					break;
				case 'M':
					$lifetime = $value > 1 ? wp_sprintf( __( '%d months', 'geodir_pricing' ), $value ) : __( 'month', 'geodir_pricing' );
					break;
				case 'Y':
					$lifetime = $value > 1 ? wp_sprintf( __( '%d years', 'geodir_pricing' ), $value ) : __( 'year', 'geodir_pricing' );
					break;
			}
		} else {
			switch ( $unit ) {
				case 'D':
					$lifetime = $value > 1 ? wp_sprintf( '%d days', $value ) : 'day';
					break;
				case 'W':
					$lifetime = $value > 1 ? wp_sprintf( '%d weeks', $value ) : 'week';
					break;
				case 'M':
					$lifetime = $value > 1 ? wp_sprintf( '%d months', $value ) : 'month';
					break;
				case 'Y':
					$lifetime = $value > 1 ? wp_sprintf( '%d years', $value ) : 'year';
			}
		}
	}

	return apply_filters( 'geodir_pricing_table_display_lifetime', $lifetime, $value, $unit, $translated );
}

function geodir_pricing_lifetime_unit_options( $package = array(), $value = '' ) {
	$units = geodir_pricing_lifetime_units();

	$options = array();
	foreach ( $units as $key => $label ) {
		$options[] = '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $value, false ) . '>' . $label . '</option>';
	}

	return implode( '', $options );
}

function geodir_pricing_new_expire_date( $days, $date = '' ) {
	$expire_date = $date;

	if ( $days > 0 ) {
		if ( ! geodir_pricing_date_never_expire( $date ) ) {
			$expire_date = date_i18n( 'Y-m-d', strtotime( $date . ' + ' . (int) $days . ' days' ) );
		} else {
			$expire_date = date_i18n( 'Y-m-d', strtotime( date_i18n( 'Y-m-d' ) . ' + ' . (int) $days . ' days' ) );
		}
	} else {
		$expire_date = 'never';
	}

	return $expire_date;
}

function geodir_pricing_post_is_expired( $post ) {
	if ( ! empty( $post ) && ! is_object( $post ) ) {
		$post = get_post( $post );
	}

	if ( empty( $post ) ) {
		return NULL;
	}

	$status = ! empty( $post->post_status ) ? $post->post_status : get_post_status( $post );
	$expired = $status == 'gd-expired' ? true : false;

	return apply_filters( 'geodir_pricing_post_is_expired', $expired, $post );
}

function geodir_pricing_post_renew_url( $post_id ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$post_type = get_post_type( $post_id );
	if ( ! geodir_is_gd_post_type( $post_type ) ) {
		return NULL;
	}

	$url = geodir_add_listing_page_url( $post_type, $post_id );
	$url = geodir_getlink( $url, array( 'task' => 'renew' ), false );

	return apply_filters( 'geodir_pricing_post_renew_url', $url, $post_id );
}

function geodir_pricing_post_upgrade_url( $post_id ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$post_type = get_post_type( $post_id );
	if ( ! geodir_is_gd_post_type( $post_type ) ) {
		return NULL;
	}

	$url = geodir_add_listing_page_url( $post_type, $post_id );
	$url = geodir_getlink( $url, array( 'task' => 'upgrade' ), false );

	return apply_filters( 'geodir_pricing_post_upgrade_url', $url, $post_id );

}

function geodir_pricing_is_new( $post_data = array() ) {
	$post_id = ! empty( $post_data['ID'] ) ? $post_data['ID'] : 0;

	// If its is revision post.
	if ( $revision_id = wp_is_post_revision( $post_id ) ) {
		$post_id = $revision_id;
	}

	$post_status = get_post_status( $post_id );

	$is_new = false;
	if ( $post_status == 'auto-draft' ) {
		$is_new = true;
	} elseif ( GeoDir_Pricing_Cart::has_invoice( $post_id, 'new' ) ) {
		$is_new = true;
	}

	return apply_filters( 'geodir_pricing_is_new', $is_new, $post_id, $post_data );
}

function geodir_pricing_skip_invoice( $post_data = array() ) {
	return GeoDir_Pricing_Cart::skip_invoice( $post_data );
}

function geodir_pricing_is_upgrade( $post_data = array() ) {
	$post_id = ! empty( $post_data['ID'] ) ? $post_data['ID'] : 0;

	// If its is revision post.
	if ( $revision_id = wp_is_post_revision( $post_id ) ) {
		$post_id = $revision_id;
	}

	$is_updrade = false;
	if ( isset( $post_data['package_id'] ) && $post_data['package_id'] != geodir_get_post_meta( $post_id, 'package_id', true ) ) {
		$is_updrade = true;
	}

	return apply_filters( 'geodir_pricing_is_upgrade', $is_updrade, $post_id, $post_data );
}

function geodir_pricing_post_has_upgrade( $post_id ) {
	// If its is revision post.
	if ( $revision_id = wp_is_post_revision( $post_id ) ) {
		$post_id = $revision_id;
	}

	$post_status = get_post_status( $post_id );

	$has_upgrade = false;
	if ( ! in_array( $post_status, array( 'trash', 'gd-closed', 'pending' ) ) && geodir_pricing_has_upgrades( (int) geodir_get_post_meta( $post_id, 'package_id', true ) ) ) {
		$has_upgrade = true;
	}

	return apply_filters( 'geodir_pricing_post_has_upgrade', $has_upgrade, $post_id );
}

function geodir_pricing_is_renew( $post_data = array() ) {
	$post_id = ! empty( $post_data['ID'] ) ? absint( $post_data['ID'] ) : 0;
	$original_id = $post_id;

	if ( $revision_id = wp_is_post_revision( $post_id ) ) {
		$original_id = $revision_id;
	}

	$is_renew = geodir_pricing_post_has_renew( $post_id );

	$task = isset( $post_data['task'] ) ? sanitize_text_field( $post_data['task'] ) : '';

	if ( $is_renew && $task != 'renew' && ! geodir_pricing_is_upgrade( $post_data ) && in_array( get_post_status( $original_id ), geodir_get_publish_statuses( array( 'post_type' => get_post_type( $original_id ) ) ) ) ) {
		$is_renew = false;
		// Update
	}

	return apply_filters( 'geodir_pricing_is_renew', $is_renew, $post_id, $post_data );
}

function geodir_pricing_post_has_renew( $post_id ) {
	// If its is revision post.
	if ( $revision_id = wp_is_post_revision( $post_id ) ) {
		$post_id = $revision_id;
	}

	$post_status = get_post_status( $post_id );

	$is_renew = false;
	if ( in_array( $post_status, array( 'draft', 'gd-expired' ) ) ) {
		$is_renew = true;
	} elseif ( geodir_pricing_post_has_renew_period( $post_id ) && in_array( $post_status, geodir_get_post_stati( 'renew', array( 'post_type' => get_post_type( $post_id ) ) ) ) ) {
		$is_renew = true;
	}

	return apply_filters( 'geodir_pricing_post_has_renew', $is_renew, $post_id );
}

/**
 * Check date is neve expire or not.
 *
 * @since 2.5.1.0
 *
 * @param string $date Date to check.
 * @return bool True if never expire.
 */
function geodir_pricing_date_never_expire( $date ) {
	if ( $date ) {
		$date = geodir_strtolower( trim( $date ) );
	}

	if ( empty( $date ) ) {
		return true;
	}

	if ( strpos( $date, '0000' ) !== false || $date == 'unlimited' || strlen( $date ) < 8 ) {
		return true;
	}

	return false;
}

function geodir_pricing_exclude_field_options( $post_type = 'gd_place', $package = array() ) {
	$custom_fields = geodir_post_custom_fields( '', 'all', $post_type,'none' );

	$exclude_field = array();
	if ( ! empty( $custom_fields ) ) {
		foreach( $custom_fields as $key => $field ) {
			if ( ! empty( $field['is_default'] ) ) {
				$skip = true;
			} else {
				$skip = false;
			}
		
			$skip = apply_filters( 'geodir_pricing_package_skip_exclude_field_' . $field['htmlvar_name'], $skip, $field, $package );

			if ( apply_filters( 'geodir_pricing_package_skip_exclude_field', $skip, $field, $package ) ) {
				continue;
			}

			$exclude_field[ $field['htmlvar_name'] ] = __( $field['admin_title'], 'geodirectory' );
		}

		asort( $exclude_field );
	}

	return apply_filters( 'geodir_pricing_package_exclude_field_options', $exclude_field, $package );
}