<?php
/**
 * Pricing Manager Post Package class.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Post_Package class.
 */
class GeoDir_Pricing_Post_Package {

	const db_table = GEODIR_PRICING_POST_PACKAGES_TABLE;

	public static function init() {
		if ( is_admin() ) {
		}

		add_action( 'geodir_pricing_post_package_payment_completed', array( __CLASS__, 'package_payment_completed' ), 20, 2 );
		add_action( 'geodir_pricing_post_package_payment_cancelled', array( __CLASS__, 'package_payment_cancelled' ), 20, 1 );
		add_action( 'geodir_pricing_post_package_payment_refunded', array( __CLASS__, 'package_payment_refunded' ), 20, 1 );

		// Handle transactions on post trash
		add_action( 'wp_trash_post', array( __CLASS__, 'on_trash_post' ), 50, 2 );

		// Delete transactions on post delete
		add_action( 'delete_post', array( __CLASS__, 'on_delete_post' ), 50, 2 );
	}

	public static function save( $data, $wp_error = false ) {
		global $wpdb;

		$update = false;
		$item = array();

		if ( ! empty( $data['id'] ) ) {
			$item = self::get_item( (int) $data['id'] );

			if ( empty( $item ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'pricing_post_package_error', __( 'Could not find post package', 'geodir_pricing' ) );
				} else {
					return 0;
				}
			}

			$update = true;
		}

		if ( $update ) {
			$post_package_id = $data['id'];

			if ( false === $wpdb->update( self::db_table, $data, array( 'id' => $post_package_id ) ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'db_update_error', __( 'Could not save post package', 'geodir_pricing' ), $wpdb->last_error );
				} else {
					return 0;
				}
			}

			$item_after = self::get_item( (int) $post_package_id );

			do_action( 'geodir_pricing_post_package_updated', $post_package_id, $item_after, $item );
		} else {
			if ( isset( $data['id'] ) ) {
				unset( $data['id'] );
			}

			if ( false === $wpdb->insert( self::db_table, $data ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'db_insert_error', __( 'Could not save post package', 'geodir_pricing' ), $wpdb->last_error );
				} else {
					return 0;
				}
			}

			$post_package_id = $wpdb->insert_id;

			do_action( 'geodir_pricing_post_package_inserted', $post_package_id );
		}

		do_action( 'geodir_pricing_post_package_saved', $post_package_id, $update );

		return $post_package_id;
	}
	
	public static function save_post_package( $data, $wp_error = false ) {
		global $wpdb;

		if ( false === $wpdb->insert( self::db_table, $data ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'db_insert_error', __( 'Could not save post package' ), $wpdb->last_error );
			} else {
				return 0;
			}
		}
		return $wpdb->insert_id;
	}

	public static function get_item( $id ) {
		global $wpdb;

		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::db_table . " WHERE id = %d LIMIT 1", array( $id ) ) );
		
		return $item;
	}

	public static function get_items( $args = array() ) {
		global $wpdb;

		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$args['cart'] = geodir_get_option( 'pm_cart' );

		$fields = ! empty( $args['fields'] ) ? ( is_array( $args['fields'] ) ? implode( ', ', $args['fields'] ) : $args['fields'] ) : '*';

		$where = array();
		foreach ( $args as $key => $value ) {
			if ( in_array( $key, array( 'id', 'post_id', 'package_id', 'invoice_id', 'product_id' ) ) ) {
				$where[] = $wpdb->prepare( "{$key} = %d", array( $value ) );
			} else if ( in_array( $key, array( 'cart', 'meta', 'date', 'status' ) ) ) {
				$where[] = $wpdb->prepare( "{$key} = %s", array( $value ) );
			}
		}
		$where = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		if ( ! empty( $args['order_by'] ) ) {
			$order_by = $args['order_by'];
		} else {
			if ( ! empty( $args['order'] ) ) {
				$order_by = $args['order'] . ' ' . ( ! empty( $args['order_type'] ) ? $args['order_type'] : 'ASC' );
			} else {
				$order_by = 'id ASC';
			}
		}

		$order_by = ! empty( $order_by ) ? "ORDER BY {$order_by}" : '';

		$items = $wpdb->get_results( "SELECT {$fields} FROM " . self::db_table . " {$where} {$order_by}" );

		return $items;
	}

	public static function get_metadata( $id ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return false;
		}

		$item = self::get_item( (int) $id );
		if ( empty( $item ) ) {
			return false;
		}

		$metadata = $item->meta != '' ? maybe_unserialize( $item->meta ) : array();
		if ( ! is_array( $metadata ) ) {
			$metadata = array();
		}

		return $metadata;
	}

	public static function update_metadata( $id, $meta, $empty = false ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return false;
		}

		$metadata = self::get_metadata( (int) $id );

		if ( ! is_array( $metadata ) || $empty ) {
			$metadata = array();
		}

		$meta = array_merge( $metadata, $meta );

		$meta = $meta ? maybe_serialize( $meta ) : '';

		return $wpdb->update( self::db_table, array( 'meta' => $meta ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
	}

	public static function get_package_id( $id ) {
		$item = self::get_item( $id );

		$package_id = ! empty( $item ) ? $item->package_id : 0;
		
		return $package_id;
	}

	public static function get_task( $id, $meta = '' ) {
		$metadata = $meta != '' ? maybe_unserialize( $meta ) : array();

		if ( ! ( ! empty( $metadata ) && is_array( $metadata ) ) ) {
			$metadata = self::get_metadata( (int) $id );
		}

		$task = '';
		if ( ! empty( $metadata ) && isset( $metadata['task'] ) ) {
			$task = $metadata['task'];
		}

		return $task;
	}

	public static function has_trial_period( $id, $meta = '' ) {
		$metadata = $meta != '' ? maybe_unserialize( $meta ) : array();

		if ( ! ( ! empty( $metadata ) && is_array( $metadata ) ) ) {
			$metadata = self::get_metadata( (int) $id );
		}

		$has_trial_period = false;
		if ( ! empty( $metadata['task'] ) && $metadata['task'] != 'renew' && ! empty( $metadata['free_trial'] ) ) {
			$has_trial_period = true;
		}

		return $has_trial_period;
	}

	public static function package_payment_completed( $item, $revision_id ) {
		global $wpdb, $geodir_expire_data;

		if ( is_int( $item ) ) {
			$item = self::get_item( $item );
		}

		if ( ! ( is_object( $item ) && ! empty( $item->id ) && ! empty( $item->post_id ) ) ) {
			return false;
		}

		$post_id = (int) $item->post_id;
		$package_id = (int) $item->package_id;
		$post_package_id = (int) $item->id;

		$package = geodir_pricing_get_package( $package_id );
		if ( empty( $package ) ) {
			return false;
		}

		$task = self::get_task( $post_package_id, $item->meta );
		$days = geodir_pricing_package_alive_days( $package );

		if ( self::has_trial_period( $post_package_id, $item->meta ) && ( $_days = geodir_pricing_package_alive_days( $package, true ) ) ) {
			$days = $_days;
		}

		$current_post_status = get_post_status( $post_id );
		$new_post_status = geodir_pricing_paid_post_status( $package_id, $post_id, $task );

		if ( $current_post_status == 'publish' && ( empty( $task ) || in_array( $task, array( 'new', 'renew' ) ) ) ) {
			$prev_expire_date = geodir_get_post_meta( $post_id, 'expire_date', true );
			$expire_date = geodir_pricing_new_expire_date( (int) $days, $prev_expire_date );
		} else {
			$expire_date = geodir_pricing_new_expire_date( (int) $days );
		}

		$data = array(
			'ID' => $post_id,
			'package_id' => $package_id,
			'expire_date' => $expire_date,
		);

		if ( ! ( $new_post_status == $current_post_status && $current_post_status == 'publish' ) ) {
			$data['post_status'] = $new_post_status;
		}

		// Set featured
		if ( geodir_pricing_is_featured( $package->id ) && ( $cf_featured = geodir_get_field_infoby( 'htmlvar_name', 'featured', $package->post_type ) ) ) {
			if ( ! empty( $cf_featured['default_value'] ) ) {
				$data['featured'] = 1;
			}
		}

		$data = apply_filters( 'geodir_pricing_complete_package_update_post_data', $data, $post_id, $package_id, $post_package_id, $revision_id );

		$geodir_expire_data = $data;

		if ( ! $revision_id ) { // New post
			$post_id = wp_update_post( wp_slash( $data ), true );
		} else { // Its a revision
			// Update the main post post_status
			if ( isset( $data['post_status'] ) ) { // Its a revision
				wp_update_post( wp_slash( $data ), true );
			}
			
			// Update the revision with the new package info first
			$data['ID'] = $revision_id;
			unset( $data['post_status'] );

			$post_id = wp_update_post( wp_slash( $data ), true );

			// Then restore the revision
			$post_id = wp_restore_post_revision( $revision_id );
		}

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		if ( empty( $post_id ) && ! empty( $data['ID'] ) ) {
			$post_id = $data['ID'];
		}

		do_action( 'geodir_pricing_complete_package_post_updated', $post_id, $package_id, $post_package_id, $revision_id );

		return true;
	}

	public static function package_payment_cancelled( $item ) {
		global $wpdb;

		if ( is_int( $item ) ) {
			$item = self::get_item( $item );
		}

		if ( ! ( is_object( $item ) && ! empty( $item->id ) && ! empty( $item->post_id ) ) ) {
			return false;
		}

		$post_id = (int) $item->post_id;
		$package_id = (int) $item->package_id;
		$post_package_id = (int) $item->id;

		$package = geodir_pricing_get_package( $package_id );
		if ( empty( $package ) ) {
			return false;
		}

		$current_post_status = get_post_status( $post_id );
		$current_package_id = (int) geodir_get_post_meta( $post_id, 'package_id', true );
		if ( $current_package_id != $package_id || $current_post_status == 'draft' ) {
			return false;
		}

		$data = array(
			'ID' => $post_id,
			'post_status' => 'draft'
		);

		$data = apply_filters( 'geodir_pricing_cancel_package_update_post_data', $data, $post_id, $package_id, $post_package_id );

		$post_id = wp_update_post( wp_slash( $data ), true );
        
        if ( is_wp_error( $post_id ) ) {
            return false;
        }

		do_action( 'geodir_pricing_cancel_package_post_updated', $post_id, $package_id, $post_package_id );

		return true;
	}

	public static function package_payment_refunded( $item ) {
		global $wpdb;

		if ( is_int( $item ) ) {
			$item = self::get_item( $item );
		}

		if ( ! ( is_object( $item ) && ! empty( $item->id ) && ! empty( $item->post_id ) ) ) {
			return false;
		}

		$post_id = (int) $item->post_id;
		$package_id = (int) $item->package_id;
		$post_package_id = (int) $item->id;

		$package = geodir_pricing_get_package( $package_id );
		if ( empty( $package ) ) {
			return false;
		}

		$current_post_status = get_post_status( $post_id );
		$current_package_id = (int) geodir_get_post_meta( $post_id, 'package_id', true );
		if ( $current_package_id != $package_id || $current_post_status == 'draft' ) {
			return false;
		}

		$data = array(
			'ID' => $post_id,
			'post_status' => 'draft'
		);

		$data = apply_filters( 'geodir_pricing_refunded_package_update_post_data', $data, $post_id, $package_id, $post_package_id );

		$post_id = wp_update_post( wp_slash( $data ), true );
        
        if ( is_wp_error( $post_id ) ) {
            return false;
        }

		do_action( 'geodir_pricing_refunded_package_post_updated', $post_id, $package_id, $post_package_id );

		return true;
	}

	public static function get_checkout_url($payment_id = ''){
		return '';// cart specific
	}

	/**
	 * Handle post package entry on trash post.
	 *
	 * @since 2.6.0.2
	 *
	 * @param int    $post_id Post ID.
	 * @param string $previous_status The status of the post about to be trashed.
	 */
	public static function on_trash_post( $post_id, $previous_status = '' ) {
		global $wpdb;

		$post_type = get_post_type( $post_id );

		if ( ! ( in_array( $post_type, array( 'wpi_invoice', 'shop_order' ) ) ) ) {
			return;
		}

		$wpdb->query( "DELETE FROM `" . self::db_table . "` WHERE `invoice_id` = " . (int) $post_id );
	}

	/**
	 * Delete post package entry on delete post.
	 *
	 * @since 2.6.0.2
	 *
	 * @param int     $postid Post ID.
	 * @param WP_Post $post   Post object.
	 */
	public static function on_delete_post( $postid, $post = array() ) {
		global $wpdb;

		$post_type = ! empty( $post ) ? $post->post_type : get_post_type( $postid );

		if ( ! ( geodir_is_gd_post_type( $post_type ) || in_array( $post_type, array( 'wpi_invoice', 'wpi_item', 'shop_order', 'product' ) ) ) ) {
			return;
		}

		$wpdb->query(  "DELETE FROM `" . self::db_table . "` WHERE `post_id` = " . (int) $postid . " OR `invoice_id` = " . (int) $postid . " OR `product_id` = " . (int) $postid );
	}
}