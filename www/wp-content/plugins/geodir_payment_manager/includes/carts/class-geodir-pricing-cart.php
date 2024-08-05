<?php
/**
 * Cart integration class.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Cart class.
 */
class GeoDir_Pricing_Cart {

	public function __construct() {
	}

	public function currency_code() {

		return apply_filters( 'geodir_pricing_currency_code', 'USD' );
	}

	public function currency_sign( $currency = '' ) {

		return apply_filters( 'geodir_pricing_currency_sign', '$', $currency );
	}

	public function currency_position() {

		return apply_filters( 'geodir_pricing_currency_position', 'left' );
	}

	public function thousand_separator() {

		return geodir_get_price_thousand_separator();
	}

	public function decimal_separator() {

		return geodir_get_price_decimal_separator();
	}

	public function decimals() {

		return geodir_get_price_decimals() ;
	}

	/**
	 * Format decimal numbers ready for DB storage.
	 *
	 * Sanitize, remove decimals, and optionally round + trim off zeros.
	 *
	 * This function does not remove thousands - this should be done before passing a value to the function.
	 *
	 * @since 2.5.0.0
	 *
	 * @param  float|string $number Expects either a float or a string with a decimal separator only (no thousands)
	 * @param  mixed $dp number of decimal points to use, blank to use geodir_get_price_decimals, or false to avoid all rounding.
	 * @param  bool $trim_zeros from end of string
	 * @return string
	 */
	public function format_decimal( $number, $dp = false, $trim_zeros = false ) {

		return geodir_format_decimal( $number, $dp, $trim_zeros );
	}

	/**
	 * Get the price format depending on the currency position.
	 *
	 * @return string
	 */
	public function price_format() {
		$position = geodir_pricing_currency_position();

		$format = '%1$s%2$s';

		switch ( $position ) {
			case 'left' :
				$format = '%1$s%2$s';
			break;
			case 'right' :
				$format = '%2$s%1$s';
			break;
			case 'left_space' :
				$format = '%1$s&nbsp;%2$s';
			break;
			case 'right_space' :
				$format = '%2$s&nbsp;%1$s';
			break;
		}

		return apply_filters( 'geodir_pricing_price_format', $format, $position );
	}

	public function price( $price, $args = array() ) {
		$args = apply_filters( 'geodir_pricing_price_args', wp_parse_args( $args, array(
			'currency' => '',
			'decimal_separator' => geodir_pricing_decimal_separator(),
			'thousand_separator' => geodir_pricing_thousand_separator(),
			'decimals' => geodir_pricing_decimals(),
			'price_format' => geodir_pricing_price_format(),
		) ) );

		if ( $price === '' || $price === null ) {
			$price = 0;
		}

		$unformatted_price = $price;
		$negative          = $price < 0;
		$price             = apply_filters( 'geodir_pricing_raw_price', floatval( $negative ? $price * -1 : $price ) );
		$price             = apply_filters( 'geodir_pricing_formatted_price', number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] ), $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

		if ( apply_filters( 'geodir_pricing_price_trim_zeros', false ) && $decimals > 0 ) {
			$price = geodir_pricing_trim_zeros( $price );
		}

		$formatted_price = ( $negative ? '-' : '' ) . wp_sprintf( $args['price_format'], '<span class="geodir-pricing-currency-sign">' . geodir_pricing_currency_sign( $args['currency'] ) . '</span>', $price );
		$return = '<span class="geodir-pricing-price-amount amount">' . $formatted_price . '</span>';

		return apply_filters( 'geodir_pricing_price', $return, $price, $args, $unformatted_price );
	}

	public static function get_product_id( $package ) {
		$package_id = 0;

		if ( is_int( $package ) ) {
			$package_id = $package;
		} else if ( is_object( $package ) && ! empty( $package->id ) ) {
			$package_id = $package->id;
		}

		if ( empty( $package_id ) ) {
			return NULL;
		}

		$product_id = $package_id;

		return (int) apply_filters( 'geodir_pricing_core_get_product_id', $product_id, $package_id );
	}

	public static function get_package_id( $product ) {
		global $wpdb;

		if ( is_object( $product ) ) {
			$product_id = $product->id;
		} else if ( is_int( $product ) ) {
			$product_id = $product;
		} else {
			$product_id = 0;
		}

		$package_id = geodir_cache_get( 'geodir_pricing_core_product_package_id-' . $product_id, 'geodir_pricing_core' );

		if ( $package_id !== false ) {
			return $package_id;
		}

		$package_id = $product_id;

		$package_id = (int) apply_filters( 'geodir_pricing_core_get_package_id', $package_id, $product_id );

		geodir_cache_set( 'geodir_pricing_core_product_package_id-' . $product_id, $package_id, 'geodir_pricing_core' );

		return $package_id;
	}

	public static function sync_package_to_cart_item( $package_id ) {
	}

	public static function new_listing($package_id,$post_data){

	}

	/**
	 * Check if the post has an outstanding invoice for that package.
	 *
	 * @param $post_id
	 *
	 * @return false|stdClass
	 */
	public static function has_invoice( $post_id, $task = '', $cart = '' ) {
		global $wpdb;

		$invoice = false;

		if ( empty( $post_id ) ) {
			return $invoice;
		}

		$prepare = array();
		$prepare[] = $post_id;

		$task_sql = '';
		if ( $task ) {
			$task_sql .= " AND task = %s";
			$prepare[] = $task;
		}

		if ( $cart ) {
			$task_sql .= " AND cart = %s";
			$prepare[] = $cart;
		}

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_PRICING_POST_PACKAGES_TABLE . " WHERE post_id = %d AND status NOT IN( 'publish', 'wpi-renewal', 'wpi-processing', 'completed' ) $task_sql ORDER BY date DESC", $prepare ) );

		if ( ! empty( $result->id ) ) {
			$invoice = $result;
		}

		return $invoice;
	}

	/**
	 * The message to show to the user when they submit the listing.
	 *
	 * @param $task
	 * @param array $post_data
	 * @param string $checkout_url
	 *
	 * @return string
	 */
	public static function ajax_save_post_message( $task, $post_data = array(), $checkout_url = '' ) {
		global $aui_bs5;

		$message = '';

		switch ( $task ) {
			case 'renew':
				$post = new stdClass();
				$post->ID = $post_data['ID'];
				$preview_link = GeoDir_Post_Data::get_preview_link( $post );

				if ( $checkout_url ) {
					$message = wp_sprintf( __( 'Post has been submitted, you need to complete checkout to renew the listing, you can preview it %shere%s.  %sCheckout%s', 'geodir_pricing' ), '<a href="' . $preview_link . '" target="_blank">', "</a>", '<a href="' . $checkout_url . '" class="gd-noti-button"><i class="fas fa-shopping-cart"></i> ', "</a>" );
				}
				break;
			case 'upgrade':

				$post = new stdClass();
				$post->ID = $post_data['ID'];
				$preview_link = GeoDir_Post_Data::get_preview_link( $post );

				if ( $checkout_url) {
					$message = wp_sprintf( __( 'Post has been submitted, you need to complete checkout to upgrade the listing, you can preview it %shere%s.  %sCheckout%s', 'geodir_pricing' ), '<a href="' . $preview_link . '" target="_blank">', "</a>", '<a href="' . $checkout_url . '" class="gd-noti-button"><i class="fas fa-shopping-cart"></i> ', "</a>" );
				}

				break;
			case 'new':
				$post = new stdClass();
				$post->ID = $post_data['ID'];
				$preview_link = GeoDir_Post_Data::get_preview_link( $post );

				if ( $checkout_url ) {
					$message = wp_sprintf( __( 'Post has been submitted, you need to complete checkout to make it live, you can preview it %shere%s. %sCheckout%s', 'geodir_pricing' ), '<a href="' . $preview_link . '" target="_blank">', "</a>", '<a href="' . $checkout_url . '" class="gd-noti-button"><i class="fas fa-shopping-cart"></i> ', "</a>" );
				}

				break;
			default:
				$message = '';
				break;
		}

		if ( $message ) {
			if ( $checkout_url ) {
				$set_redirect = geodir_get_option( 'pm_checkout_redirect', '0' );

				// Set auto checkout redirect.
				$set_redirect = apply_filters( 'geodir_pricing_set_checkout_redirect', $set_redirect, $checkout_url, $task, $post_data );

				$checkout_redirect = '';
				if ( $set_redirect !== false && $set_redirect != 'no' ) {
					$checkout_redirect = '<span class="gd-checkout-redirect ' . ( $aui_bs5 ? ' ms-2' : ' ml-2' ) . '"> <i class="fas fa-sync fa-spin fa-fw" aria-hidden="true"></i> ' . __( 'Redirecting for checkout...', 'geodir_pricing' ) . '</span>';

					if ( (int) $set_redirect > 0 ) {
						$checkout_redirect .= '<script type="text/javascript">(function(){setTimeout(function(){window.location.href = "' . $checkout_url . '";},' . ( (float) $set_redirect * 1000 ) . ');})();</script>';
					} else {
						$checkout_redirect .= '<script type="text/javascript">(function(){window.location.href = "' . $checkout_url . '";})();</script>';
					}

					$checkout_redirect = apply_filters( 'geodir_pricing_checkout_redirect_text', $checkout_redirect, $checkout_url, $task, $post_data );

					$message .= $checkout_redirect;
				}
			}

			$message = apply_filters( 'geodir_ajax_save_paid_post_message', $message, $task, $post_data, $checkout_url );

			return GeoDir_Post_Data::output_user_notes( array( 'gd-info' => $message ) );
		} else {
			return '';
		}
	}

	/**
	 * Should be taken over by cart class but if not then we return an error.
	 * 
	 * @param $post_data
	 *
	 * @return WP_Error
	 */
	public function ajax_post_saved( $post_data ){
		return new WP_Error( 'no_cart_set', __( "Error: No Cart has been setup to accept payments.", "geodir_pricing" ) );
	}

	/**
	 * @since 2.5.1.0
	 */
	public static function skip_invoice( $post_data = array() ) {
		global $geodir_pricing_manager;

		$package_id = ! empty( $post_data['package_id'] ) ? absint( $post_data['package_id'] ) : 0;

		$skip_invoice = ! empty( $geodir_pricing_manager->cart_class ) && $geodir_pricing_manager->cart_class == 'GeoDir_Pricing_Cart' ? true : false;

		if ( ! $skip_invoice && $package_id && ( $package = geodir_pricing_get_package( $package_id ) ) ) {
			$skip_invoice = GeoDir_Pricing_Package::is_free( $package ) && ! GeoDir_Pricing_Package::is_recurring( $package );
		}

		return apply_filters( 'geodir_pricing_skip_invoice', $skip_invoice, $post_data );
	}

	/**
	 * @since 2.5.1.0
	 */
	public static function post_without_invoice( $post_data = array() ) {
		global $geodir_expire_data;

		$package_id = ! empty( $post_data['package_id'] ) ? absint( $post_data['package_id'] ) : 0;
		if ( empty( $package_id ) ) {
			return NULL;
		}

		// First save the submitted data
		$result = GeoDir_Post_Data::auto_save_post( $post_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$parent_post_id = ! empty( $post_data['post_parent'] ) ? $post_data['post_parent'] : $post_data['ID']; // Post ID.
		$current_post_status = get_post_status( $parent_post_id ); // Current post status.
		$post_package = false;

		if ( geodir_pricing_is_new( $post_data ) ) {
			// New
			$task = 'new';
			$save_post_status = geodir_pricing_paid_post_status( $package_id, $parent_post_id, $task ); // Save default post status.

			// Check if its a logged out user and if we have details to register the user
			$post_data = GeoDir_Post_Data::check_logged_out_author( $post_data );

			if ( empty( $post_data['post_parent'] ) && $current_post_status == 'auto-draft' ) {
				// If its new and an auto-draft then we save it as pending
				wp_update_post( array( 'ID'=> $post_data['ID'], 'post_status' => $save_post_status ) );
			} elseif ( ! empty( $post_data['post_parent'] ) ) {
				// If its new and a revision then just save it
				wp_restore_post_revision( $post_data['ID'] );
				$post_data['ID'] = $parent_post_id;
			}

			$post_package = self::has_invoice( $post_data['ID'], $task );
		} else if ( geodir_pricing_is_upgrade( $post_data ) ) {
			// Upgrade
			$task = 'upgrade';
			$save_post_status = geodir_pricing_paid_post_status( $package_id, $parent_post_id, $task ); // Save default post status.

			$post_package = self::has_invoice( $post_data['ID'], $task );
		} else if ( geodir_pricing_is_renew( $post_data ) ) {
			// Renewal
			$task = 'renew';
			$save_post_status = geodir_pricing_paid_post_status( $package_id, $parent_post_id, $task ); // Save default post status.

			$post_package = self::has_invoice( $post_data['ID'], $task );
		} else {
			// Update
			$task = 'update';
			$save_post_status = geodir_pricing_paid_post_status( $package_id, $parent_post_id, $task ); // Save default post status.

			wp_restore_post_revision( $post_data['ID'] );

			return NULL;
		}

		$post_id = $post_data['ID'];
		$revision_post_id = wp_is_post_revision( $post_id ) ? $post_id : 0;
		$package_id = absint( $post_data['package_id'] );

		if ( $task != 'new' && $save_post_status != 'publish' && $save_post_status != $current_post_status ) {
			//wp_update_post( array( 'ID'=> $post_id, 'post_status' => $save_post_status ) );

			//wp_restore_post_revision( $post_id );
		}

		$data = array(
			'post_id' => $parent_post_id,
			'package_id' => $package_id,
			'product_id' => 0,
			'invoice_id' => 0,
			'task' => $task,
			'cart' => 'nocart',
			'status' => 'publish',
			'meta' => maybe_serialize( array( 'task' => $task ) ),
			'date' => date_i18n( 'Y-m-d H:i:s' )
		);

		// If we have a package id then we just update it.
		if ( ! empty( $post_package->id ) ) {
			$data['id'] = $post_package->id;
		}

		$data = apply_filters( 'geodir_pricing_nocart_post_package_data', $data, $post_id, $package_id, $post_data );

		$post_package_id = GeoDir_Pricing_Post_Package::save( $data );
		$post_package = $post_package_id ? GeoDir_Pricing_Post_Package::get_item( (int) $post_package_id ) : array();
		$post_data['post_package'] = $post_package;

		if ( ! empty( $post_package ) ) {
			do_action( 'geodir_pricing_post_package_payment_completed', $post_package, $revision_post_id  );
		}

		if ( $task == 'renew' && ! empty( $geodir_expire_data['ID'] ) && ! empty( $geodir_expire_data['expire_date'] ) && $parent_post_id == $geodir_expire_data['ID'] ) {
			geodir_save_post_meta( $parent_post_id, 'expire_date', $geodir_expire_data['expire_date'] );
			$post_data['expire_date'] = $geodir_expire_data['expire_date'];
		}

		// If its free then send the standard message
		$post_data['ID'] = $parent_post_id;
		$post_data['post_status'] = $save_post_status;

		return GeoDir_Post_Data::ajax_save_post_message( $post_data );
	}

	/**
	 * @since 2.5.1.0
	 */
	public static function claim_without_invoice( $post_id, $package_id, $user_id, $payment_id = 0 ) {
		global $geodir_expire_data;

		if ( empty( $package_id ) ) {
			return false;
		}

		$post_data = (array) geodir_get_post_info( $post_id );

		// Claim
		$task = 'claim';

		$data = array(
			'post_id' => $post_id,
			'package_id' => $package_id,
			'product_id' => 0,
			'invoice_id' => 0,
			'task' => $task,
			'cart' => 'nocart',
			'status' => 'publish',
			'meta' => serialize( array( 'task' => $task ) ),
			'date' => date_i18n( 'Y-m-d H:i:s' )
		);

		// If we have a package id then we just update it.
		if ( ! empty( $payment_id ) ) {
			$data['id'] = $payment_id;
		}

		$data = apply_filters( 'geodir_pricing_nocart_post_package_data', $data, $post_id, $package_id, $post_data );

		$post_package_id = GeoDir_Pricing_Post_Package::save( $data );

		return $post_package_id;
	}

	public static function claim_pending_message( $message, $payment, $claim, $post_id ) {
		return $message;
	}

	public function remove_cancel_at_period_end( $invoice_id ) {
		if ( empty( $invoice_id ) ) {
			return false;
		}

		// Retrieve the invoice items.
		$items = GeoDir_Pricing_Post_Package::get_items( array( 'invoice_id' => (int) $invoice_id ) );

		if ( empty( $items ) ) {
			return null;
		}

		$count = 0;

		foreach ( $items as $item ) {
			if ( empty( $item->post_id ) ) {
				continue;
			}

			$deleted = delete_post_meta( (int) $item->post_id, '_gdpm_cancel_at_period_end' );

			if ( $deleted ) {
				$count++;
			}
		}

		return $count;
	}
}
