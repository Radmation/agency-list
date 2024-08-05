<?php
/**
 * Claim Listings Post Class.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Claim_Post class.
 */
class GeoDir_Claim_Post {

	const db_table = GEODIR_CLAIM_TABLE;

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'geodir_claim_post_item_inserted', array( __CLASS__, 'on_submit_claim' ), 10, 1 );
		add_action( 'geodir_claim_approved', array( __CLASS__, 'on_claim_approved' ), 10, 1 );
		add_action( 'geodir_claim_rejected', array( __CLASS__, 'on_claim_rejected' ), 10, 1 );
		add_action( 'geodir_claim_undone', array( __CLASS__, 'on_claim_undone' ), 10, 1 );
		add_filter( 'body_class', array( __CLASS__, 'claimed_body_class' ) );
		add_filter( 'geodir_custom_field_output_author', array( __CLASS__, 'maybe_show_author_link' ),15,5 );
		add_filter( 'geodir_claim_pre_post_get_form', array( __CLASS__, 'check_claim_post_form' ), 20, 2 );
		add_filter( 'geodir_user_posts_limit_message', array( __CLASS__, 'user_posts_limit_message' ), 11, 5 );
		add_action( 'wp_insert_post', array( __CLASS__, 'on_import_new_post_saved' ), 16, 3 );
	}

	/**
	 * Remove the author link if the listing is not claimed.
	 * 
	 * @param $html
	 * @param $location
	 * @param $cf
	 * @param string $p
	 * @param string $output
	 *
	 * @return string
	 */
	public static function maybe_show_author_link( $html, $location, $cf, $p = '', $output = '' ) {
		global $gd_post;

		// Remove the output if not claimed
		if ( ! ( ! empty( $gd_post ) && is_object( $gd_post ) && property_exists( $gd_post, 'claimed' ) && $gd_post->claimed == '1' ) ) {
			$html = '';
		}

		return $html;
	}

	/**
	 * Add a body class if the post is claimed.
	 * 
	 * @param $classes
	 *
	 * @return array
	 */
	public static function claimed_body_class($classes){

		if(geodir_is_page('single')){
			global $gd_post;
			if(isset($gd_post->claimed) && $gd_post->claimed==1){
				$classes[] = 'gd-is-claimed';
			}
		}

		return $classes;
	}

	public static function save( $data, $wp_error = false ) {
		global $wpdb;

		$update = false;
		$item = array();
		if ( ! empty( $data['id'] ) ) {
			$item = self::get_item( (int) $data['id'] );

			if ( empty( $item ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'geodir_claim_save_error', __( 'Could not find claim data.', 'geodir-claim' ) );
				} else {
					return 0;
				}
			}

			$update = true;
		}

		if ( $update ) {
			$id = $data['id'];

			if ( false === $wpdb->update( self::db_table, $data, array( 'id' => $id ) ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'geodir_claim_update_error', __( 'Could not save claim data.', 'geodir-claim' ), $wpdb->last_error );
				} else {
					return 0;
				}
			}

			// Delete claim stats cache.
			delete_transient( 'geodir_claim_stats' );

			$item_after = self::get_item( (int) $id );

			do_action( 'geodir_claim_post_item_updated', $id, $item_after, $item );
		} else {
			if ( isset( $data['id'] ) ) {
				unset( $data['id'] );
			}

			if ( empty( $data['user_ip'] ) ) {
				$data['user_ip'] = geodir_get_ip();
			}

			if ( empty( $data['claim_date'] ) ) {
				$data['claim_date'] = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
			}

			if ( empty( $data['rand_string'] ) ) {
				$data['rand_string'] = md5( microtime() . wp_rand() );
			}

			if ( false === $wpdb->insert( self::db_table, $data ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'geodir_claim_insert_error', __( 'Could not save claim data.', 'geodir-claim' ), $wpdb->last_error );
				} else {
					return 0;
				}
			}

			// Delete claim stats cache.
			delete_transient( 'geodir_claim_stats' );

			$id = $wpdb->insert_id;

			do_action( 'geodir_claim_post_item_inserted', $id );
		}

		do_action( 'geodir_claim_post_item_saved', $id, $update );

		return $id;
	}

	public static function delete( $claim_id ) {
		global $wpdb;

		if ( empty( $claim_id ) ) {
			return false;
		}

		$deleted = $wpdb->delete( self::db_table, array( 'id' => $claim_id ), array( '%d' ) );

		// Delete claim stats cache.
		delete_transient( 'geodir_claim_stats' );

		return $deleted;
	}

	public static function get_items( $args = array() ) {
		global $wpdb;

		if ( ! is_array( $args ) ) {
			$args = array();
		}

		$fields = ! empty( $args['fields'] ) ? ( is_array( $args['fields'] ) ? implode( ', ', $args['fields'] ) : $args['fields'] ) : '*';

		$where = array();
		foreach ( $args as $key => $value ) {
			if ( in_array( $key, array( 'id', 'post_id', 'author_id', 'user_id', 'status' ) ) ) {
				$where[] = $wpdb->prepare( "{$key} = %d", array( $value ) );
			} else if ( in_array( $key, array( 'post_type', 'user_fullname', 'user_number', 'user_position', 'user_ip', 'claim_date', 'rand_string' ) ) ) {
				$where[] = $wpdb->prepare( "{$key} LIKE %s", array( $value ) );
			} else if ( in_array( $key, array( 'user_comments', 'admin_comments', 'meta' ) ) ) {
				$where[] = "{$key} LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( $value ) ) ) . "%'";
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

	public static function get_item( $id ) {
		global $wpdb;

		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::db_table . " WHERE id = %d LIMIT 1", array( $id ) ) );
		
		return $item;
	}

	public static function get_item_by_key( $key ) {
		global $wpdb;

		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::db_table . " WHERE rand_string = %s LIMIT 1", array( $key ) ) );
		
		return $item;
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

	public static function on_submit_claim( $id ) {
		$claim = self::get_item( $id );

		if ( empty( $claim ) ) {
			return;
		}

		$post = get_post( (int) $claim->post_id );
		if ( empty( $post ) ) {
			return;
		}

		// Send email to admin on claim request.
		GeoDir_Claim_Email::send_admin_claim_request_email( $claim, $post );
		$package = ! empty( $claim->payment_id ) ? geodir_pricing_get_package( $claim->payment_id ): array();
		if ( ! empty( $claim->payment_id ) && ! empty( $package ) && ! GeoDir_Pricing_Package::is_free( $package ) ) {
			return;
		}

		if ( geodir_get_option( 'claim_auto_approve' ) ) {
			// Send email to user for auto verify claim request.
			GeoDir_Claim_Email::send_user_claim_verify_email( $claim, $post );
		} else {
			// Send email to user on claim request.
			GeoDir_Claim_Email::send_user_claim_request_email( $claim, $post );
		}
	}

	public static function on_claim_approved( $claim ) {
		if ( empty( $claim ) ) {
			return;
		}

		$post = get_post( (int) $claim->post_id );
		if ( empty( $post ) ) {
			return;
		}

		if ( $claim->user_id && $post->post_author != $claim->user_id ) {
			$post_update = array();
			$post_update['ID'] = $post->ID;
			$post_update['post_author'] = $claim->user_id;
						
			wp_update_post( $post_update );
		}

		// Approve claim
		geodir_save_post_meta( $claim->post_id, 'claimed', '1' );

		// Send email to user on claim approve.
		GeoDir_Claim_Email::send_user_claim_approved_email( $claim, $post );
	}

	public static function on_claim_rejected( $claim ) {
		if ( empty( $claim ) ) {
			return;
		}

		$post = get_post( (int) $claim->post_id );
		if ( empty( $post ) ) {
			return;
		}

		// Send email to user on claim reject.
		GeoDir_Claim_Email::send_user_claim_rejected_email( $claim, $post );
	}

	public static function on_claim_undone( $claim ) {
		if ( empty( $claim ) ) {
			return;
		}

		$post = get_post( (int) $claim->post_id );
		if ( empty( $post ) ) {
			return;
		}

		// Don't undone if current claim is not active.
		if ( ! ( geodir_get_post_meta( $claim->post_id, 'claimed', true ) && $claim->user_id && $post->post_author == $claim->user_id ) ) {
			return;
		}

		if ( $claim->author_id && $post->post_author != $claim->author_id ) {
			$post_update = array();
			$post_update['ID'] = $post->ID;
			$post_update['post_author'] = $claim->author_id;
						
			wp_update_post( $post_update );
		}

		// Unclaim
		geodir_save_post_meta( $claim->post_id, 'claimed', '0' );
	}

	public static function post_claim_allowed( $post_ID ) {
		if ( empty( $post_ID ) ) {
			return false;
		}

		$allow = self::post_type_claim_allowed( get_post_type( $post_ID ) );

		return apply_filters( 'geodir_claim_post_claim_allowed', $allow, $post_ID );
	}

	public static function post_type_claim_allowed( $post_type ) {
		if ( ! geodir_is_gd_post_type( $post_type ) ) {
			return false;
		}

		$field = geodir_get_field_infoby( 'htmlvar_name', 'claimed', $post_type );

		$allow = ! empty( $field ) && ! empty( $field['is_active'] );

		return apply_filters( 'geodir_claim_post_type_claim_allowed', $allow, $post_type );
	}

	public static function is_claimed( $post_ID ) {
		if ( empty( $post_ID ) ) {
			return false;
		}

		$claimed = geodir_get_post_meta( $post_ID, 'claimed', true );

		return apply_filters( 'geodir_claim_post_is_claimed', $claimed, $post_ID );
	}

	public static function is_claim_pending( $post_ID ) {
		global $wpdb;

		$result = false;
		$user_id = get_current_user_id();

		if ( empty( $post_ID ) || empty( $user_id ) ) {
			$result = false;
		} else {
			$claim_pending = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::db_table . " WHERE post_id = %d AND user_id = %d AND status=0 ORDER BY id DESC LIMIT 1", array( $post_ID, $user_id ) ) );

			if ( empty( $claim_pending ) ) {
				$result = false;
			} else {
				$result = true;
			}
		}

		return apply_filters( 'geodir_claim_post_is_claim_pending', $result, $post_ID, $user_id );
	}

	public static function get_pending_claim( $post_ID ) {
		global $wpdb;

		$claim = NULL;

		if ( ! empty( $post_ID ) && ( $user_id = get_current_user_id() ) ) {
			$claim = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::db_table . " WHERE post_id = %d AND user_id = %d AND status=0 ORDER BY id DESC LIMIT 1", array( $post_ID, $user_id ) ) );
		}

		return apply_filters( 'geodir_claim_post_pending_claim', $claim, $post_ID );
	}

	public static function approve_claim( $claim, $wp_error = false ) {
		if ( ! is_object( $claim ) ) {
			$claim = self::get_item( $claim );
		}

		if ( empty( $claim ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_approve_error', __( 'Could not found claim data.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		if ( absint( $claim->status ) === 1 ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_approve_error', __( 'Could not process claim request.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		$gd_post = geodir_get_post_info( $claim->post_id );
		if ( ! ( ! empty( $gd_post ) && ( GeoDir_Claim_Post::post_claim_allowed( $gd_post->ID ) ) ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_approve_error', __( 'Claim request can not be processed for this post.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		if ( ! apply_filters( 'geodir_claim_check_approve', true, $claim ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_approve_error', __( 'Claim request not approved.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		do_action( 'geodir_claim_pre_approve', $claim );

		$data = array(
			'id' => $claim->id,
			'rand_string' => '',
			'claim_date' => date_i18n( 'Y-m-d', current_time( 'timestamp' ) ),
			'status' => 1,
		);

		$approve = self::save( $data, true );

		if ( $approve && ! is_wp_error( $approve ) ) {
			do_action( 'geodir_claim_approved', $claim );
			return true;
		}

		if ( $wp_error ) {
			return is_wp_error( $approve ) ? $approve : new WP_Error( 'geodir_claim_approve_error', __( 'Can not save claim data.', 'geodir-claim' ) );
		} else {
			return false;
		}
	}

	public static function reject_claim( $claim, $wp_error = false ) {
		if ( ! is_object( $claim ) ) {
			$claim = self::get_item( $claim );
		}

		if ( empty( $claim ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_reject_error', __( 'Could not found claim data.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		if ( absint( $claim->status ) !== 0 ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_reject_error', __( 'Could not process claim request.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		$gd_post = geodir_get_post_info( $claim->post_id );
		if ( ! ( ! empty( $gd_post ) && ( GeoDir_Claim_Post::post_claim_allowed( $gd_post->ID ) ) ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_reject_error', __( 'Claim request can not be processed for this post.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		if ( ! apply_filters( 'geodir_claim_check_reject', true, $claim ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_reject_error', __( 'Claim request not rejected.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		do_action( 'geodir_claim_pre_reject', $claim );

		$data = array(
			'id' => $claim->id,
			'rand_string' => '',
			'claim_date' => date_i18n( 'Y-m-d', current_time( 'timestamp' ) ),
			'status' => 2,
		);

		$reject = self::save( $data, true );

		if ( $reject && ! is_wp_error( $reject ) ) {
			do_action( 'geodir_claim_rejected', $claim );
			return true;
		}

		if ( $wp_error ) {
			return is_wp_error( $reject ) ? $reject : new WP_Error( 'geodir_claim_reject_error', __( 'Can not save claim data.', 'geodir-claim' ) );
		} else {
			return false;
		}
	}

	public static function undo_claim( $claim, $wp_error = false ) {
		if ( ! is_object( $claim ) ) {
			$claim = self::get_item( $claim );
		}

		if ( empty( $claim ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_undo_error', __( 'Could not found claim data.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		if ( absint( $claim->status ) !== 1 ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_undo_error', __( 'Could not process claim request.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		$gd_post = geodir_get_post_info( $claim->post_id );
		if ( ! ( ! empty( $gd_post ) && ( GeoDir_Claim_Post::post_claim_allowed( $gd_post->ID ) ) ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_undo_error', __( 'Claim request can not be processed for this post.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		if ( ! apply_filters( 'geodir_claim_check_undo', true, $claim ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_undo_error', __( 'Could not undone claim request.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		do_action( 'geodir_claim_pre_undone', $claim );

		$data = array(
			'id' => $claim->id,
			'rand_string' => '',
			'claim_date' => date_i18n( 'Y-m-d', current_time( 'timestamp' ) ),
			'status' => 0,
		);

		$approve = self::save( $data, true );

		if ( $approve && ! is_wp_error( $approve ) ) {
			do_action( 'geodir_claim_undone', $claim );
			return true;
		}

		if ( $wp_error ) {
			return is_wp_error( $approve ) ? $approve : new WP_Error( 'geodir_claim_undo_error', __( 'Can not save claim data.', 'geodir-claim' ) );
		} else {
			return false;
		}
	}

	public static function delete_claim( $claim, $wp_error = false ) {
		if ( ! is_object( $claim ) ) {
			$claim = self::get_item( $claim );
		}

		if ( empty( $claim ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_delete_error', __( 'Could not found claim data.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		if ( ! apply_filters( 'geodir_claim_check_delete', true, $claim ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'geodir_claim_delete_error', __( 'Could not delete claim request.', 'geodir-claim' ) );
			} else {
				return false;
			}
		}

		do_action( 'geodir_claim_pre_delete', $claim );

		$delete = self::delete( $claim->id );

		if ( $delete ) {
			do_action( 'geodir_claim_deleted', $claim );
			return true;
		}

		if ( $wp_error ) {
			return is_wp_error( $approve ) ? $approve : new WP_Error( 'geodir_claim_delete_error', __( 'Can not delete claim request.', 'geodir-claim' ) );
		} else {
			return false;
		}
	}

	public static function view_item($id){
		$outputs = array();
		$claim = self::get_item( $id );
		if(!empty($claim )){
			$gd_post = geodir_get_post_info($claim->post_id);
//			print_r($gd_post);
			if(!empty($gd_post)){
				$post_link  = "<a href='" . get_permalink( absint( $claim->post_id ) ) . "'>" . esc_attr( $gd_post->post_title ) . "</a>";
				$outputs['listing_title'] = array( 'label' => __( 'Listing title', 'geodir-claim' ), 'value' => $post_link );

				// User
				$user_link  = "<a href='" . add_query_arg( array( 'user_id' => absint( $claim->user_id ) ), admin_url( 'user-edit.php' ) ) . "'>" . esc_attr( geodir_get_client_name( absint( $claim->user_id ) ) ) . "</a>";
				if ( ! empty( $claim->user_id ) ) {
					$user_link .= " <small>( " . $claim->user_id . " )</small>";
				}
				$outputs['user'] = array( 'label' => __( 'User', 'geodir-claim' ), 'value' => $user_link );

				// full name
				if(!empty($claim->user_fullname)){
					$outputs['user_fullname'] = array('label'=> __( 'Full name', 'geodir-claim' ),'value'=>esc_attr($claim->user_fullname));
				}

				// user number
				if(!empty($claim->user_number)){
					$outputs['user_number'] = array('label'=> __( 'Number', 'geodir-claim' ),'value'=>esc_attr($claim->user_number));
				}

				// user position
				if(!empty($claim->user_position)){
					$outputs['user_position'] = array('label'=> __( 'Position', 'geodir-claim' ),'value'=>esc_attr($claim->user_position));
				}

				// user ip
				if(!empty($claim->user_ip)){
					$ip_link = "<a href='https://iplocation.com/?ip=".esc_attr($claim->user_ip)."' target='_blank'>".esc_attr($claim->user_ip)."</a>";
					$outputs['user_ip'] = array('label'=> __( 'IP', 'geodir-claim' ),'value'=>$ip_link);
				}

				// user comments
				if(!empty($claim->user_comments)){
					$outputs['user_comments'] = array('label'=> __( 'Message', 'geodir-claim' ),'value'=>esc_attr($claim->user_comments));
				}

				// Post author
				$author_link  = "<a href='" . add_query_arg( array( 'user_id' => absint( $claim->author_id ) ), admin_url( 'user-edit.php' ) ) . "'>" . esc_attr( geodir_get_client_name( absint( $claim->author_id ) ) ) . "</a>";
				if ( ! empty( $claim->user_id ) ) {
					$author_link .= " <small>( " . $claim->author_id . " )</small>";
				}
				$outputs['author'] = array( 'label' => __( 'Post Author', 'geodir-claim' ), 'value' => $author_link );

				if ( function_exists( 'geodir_load_pricing_manager' ) ) {
					// claim_package.
					if ( ! empty( $claim->package_id ) ) {
						$package_id                  = $claim->package_id;
						$c_post_type                 = get_post_type( $claim->post_id );
						$package_link                = admin_url( 'edit.php?post_type=' . $c_post_type . '&page=' . $c_post_type . '-settings&tab=cpt-package&section=add-package&id=' . $package_id );
						$name                        = geodir_pricing_package_name( (int) $package_id );
						$name_link                   = sprintf(
							'<a title="%s" href="%s">%s</a> ( %s )',
							esc_attr( $name ),
							esc_url( $package_link ),
							esc_html( $name ),
							esc_html( $package_id )
						);
						$outputs['claim_package_id'] = array(
							'label' => __( 'Package', 'geodir-claim' ),
							'value' => $name_link,
						);
					}

					if ( ! empty( $claim->payment_id ) ) {
						$post_package = GeoDir_Pricing_Post_Package::get_item( (int) $claim->payment_id );

						if ( ! empty( $post_package ) ) {
							$cart = ! empty( $post_package->cart ) ? $post_package->cart : '';

							if ( $cart == 'woocommerce' && ! empty( $post_package->invoice_id ) ) {
								$invoice = 'Order #' . $post_package->invoice_id;

								if ( class_exists( 'WooCommerce' ) ) {
									$invoice = '<a href="' . esc_url( get_edit_post_link( $post_package->invoice_id ) ) . '">' . $invoice . '</a>';
								}

								$outputs['claim_payment'] = array(
									'label' => __( 'Payment', 'geodir-claim' ),
									'value' => wp_sprintf( __( '%s (WooCommerce)', 'geodir-claim' ), $invoice ),
								);
							} else if ( $cart == 'invoicing' && ! empty( $post_package->invoice_id ) ) {
								$invoice = 'Invoice #' . $post_package->invoice_id;

								if ( defined( 'WPINV_VERSION' ) ) {
									$invoice = '<a href="' . esc_url( get_edit_post_link( $post_package->invoice_id ) ) . '">' . $invoice . '</a>';
								}

								$outputs['claim_payment'] = array(
									'label' => __( 'Payment', 'geodir-claim' ),
									'value' => wp_sprintf( __( '%s (GetPaid)', 'geodir-claim' ), $invoice ),
								);
							} else {
								$outputs['claim_payment'] = array(
									'label' => __( 'Payment', 'geodir-claim' ),
									'value' => __( 'No Cart', 'geodir-claim' ),
								);
							}
						}
					}
				}

				// claim_date
				if ( ! empty( $claim->claim_date ) ) {
					$outputs['claim_date'] = array('label'=> __( 'Date', 'geodir-claim' ),'value'=>esc_attr($claim->claim_date));
				}

				// Ninja Forms extra
				$meta = maybe_unserialize( $claim->meta );
				if ( ! empty( $meta ) ) {
					// claim_date
					if(!empty($meta['ninja_sub_id'])){
						$nf_link = "<a href='".admin_url('post.php?post='.absint($meta['ninja_sub_id']).'&action=edit')."'>".__( 'View submission', 'geodir-claim' )."</a>";
						$outputs['ninja_submission'] = array('label'=> __( 'Ninja Forms', 'geodir-claim' ),'value'=>$nf_link);
					}

					// extra fields
					if(!empty($meta['extra_fields'])){
						foreach($meta['extra_fields'] as $key => $extra_field){
							if(!empty($extra_field['value'])){
								$outputs[$key] = array('label'=> esc_attr($extra_field['label']),'value'=>esc_attr($extra_field['value']));
							}
						}
					}
				}

			}

		}

		echo '<div class="geodir-claim-view-wrapper">';
		echo '<table class="wp-list-table widefat striped"><tbody>';

			foreach($outputs as $output){
				echo '<tr>';
				echo '<th scope="row">'.esc_attr($output['label']).'</th>';
				echo '<td>'.$output['value'].'</td>'; // escape each field
				echo '</tr>';
			}
		echo '</tbody></table>';
		echo '</div>';
	}

	public static function check_claim_post_form( $output, $post_ID ) {
		if ( class_exists( 'GeoDir_Post_Limit' ) && ! geodir_listing_belong_to_current_user( $post_ID ) ) {
			$post_type = get_post_type( $post_ID );

			$args = array( 'post_type' => $post_type, 'group' => 'claim' );

			$can_add_post = GeoDir_Post_Limit::user_can_add_post( $args, true );

			if ( is_wp_error( $can_add_post ) ) {
				if ( geodir_design_style() ) {
					$message = aui()->alert( array(
						'type'=> 'info',
						'content'=> $can_add_post->get_error_message(),
						'class' => 'mb-0'
					) );
				} else {
					$message = geodir_notification( array( 'claim_listing_error' => $can_add_post->get_error_message() ) );
				}

				$output = apply_filters( 'geodir_posts_limit_claim_listing_message', $message, $args );
			}
		}

		return $output;
	}

	public static function posts_limit_message( $post_type, $post_author = 0 ) {
		$posts_limit = (int) GeoDir_Post_Limit::cpt_posts_limit( $post_type, $post_author );
		$post_type_name = geodir_strtolower( geodir_post_type_name( $post_type ) );

		if ( $posts_limit < 0 ) {
			$message = wp_sprintf( __( 'You are not allowed to add/claim the listing under %s.', 'geodir-claim' ), $post_type_name );
		} else {
			$message = wp_sprintf( __( 'You have reached the limit of %s you can add/claim at this time.', 'geodir-claim' ), $post_type_name );
		}

		return apply_filters( 'geodir_user_posts_limit_message', $message, $post_type, $posts_limit );
	}

	public static function get_pending_message( $post_id ) {
		$claim = self::get_pending_claim( $post_id );

		$message = __( 'It looks like you already have a claim pending review for this listing, you will be notified by email once we have reviewed your claim.', 'geodir-claim' );

		return apply_filters( 'geodir_claim_pending_message', $message, $claim, $post_id );
	}

	public static function user_posts_limit_message( $message, $posts_limit, $posts_count, $posts_limits, $params ) {
		if ( ! empty( $params['group'] ) && $params['group'] == 'claim' ) {
			$post_type_name = geodir_strtolower( geodir_post_type_name( $params['post_type'] ) );

			if ( $posts_limit < 0 ) {
				$message = wp_sprintf( __( 'You are not allowed to add/claim the listing under %s.', 'geodir-claim' ), $post_type_name );
			} else {
				$message = wp_sprintf( __( 'You have reached the limit of %s you can add/claim at this time.', 'geodir-claim' ), $post_type_name );
			}
		}

		return $message;
	}

	public static function can_claim_post( $post_ID ) {
		if ( ! geodir_listing_belong_to_current_user( $post_ID ) ) {
			$post_type = get_post_type( $post_ID );

			$args = array( 
				'post_type' => $post_type,
				'group' => 'claim'
			);

			$can_claim = GeoDir_Post_Limit::user_can_add_post( $args, true );

			if ( is_wp_error( $can_claim ) ) {
				return $can_claim;
			}
		}

		return true;
	}

	/**
	 * Handles new GD post inserted during import.
	 *
	 * @since 2.2.2
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public static function on_import_new_post_saved( $post_ID, $post, $update ) {
		global $geodir_claim_nudge_sent;

		if ( $update || empty( $post ) || ! defined( 'GEODIR_DOING_IMPORT' ) ) {
			return;
		}

		if ( ! is_array( $geodir_claim_nudge_sent ) ) {
			$geodir_claim_nudge_sent = array();
		}

		if ( empty( $geodir_claim_nudge_sent[ $post_ID ] ) && geodir_get_option( 'email_user_claim_nudge' ) && ! empty( $post->post_status ) && in_array( $post->post_status, geodir_get_publish_statuses( array( 'post_type' => $post->post_type ) ) ) ) {
			$gd_post = geodir_get_post_info( $post_ID );

			if ( ! empty( $gd_post ) ) {
				$geodir_claim_nudge_sent[ $post_ID ] = $post_ID;

				GeoDir_Claim_Email::process_user_claim_nudge_email( $gd_post );
			}
		}
	}
}