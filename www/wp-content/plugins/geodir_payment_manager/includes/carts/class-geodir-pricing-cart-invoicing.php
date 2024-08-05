<?php
/**
 * Pricing Manager & Invoicing integration class.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Cart_Invoicing class.
 */
class GeoDir_Pricing_Cart_Invoicing extends GeoDir_Pricing_Cart {

	/**
	 * Product meta key.
	 *
	 * @var string
	 */
	const GEODIR_PRICING_WPI_PRODUCT_ID = 'invoicing_product_id';

	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'geodir_integration' ) );
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 2 );
			add_filter( 'post_class', array( __CLASS__, 'package_item_classes' ), 11, 3 );

			add_action( 'wpinv_item_info_metabox_after', array( __CLASS__, 'gdp_package_type_info' ), 10, 1 ) ;
			add_action( 'wpinv_prices_metabox_price', array( __CLASS__, 'metabox_price_note' ), 10, 1 ) ;
			add_action( 'wpinv_tools_row', array( __CLASS__, 'add_tools' ), 10 );
			add_action( 'wpinv_tool_merge_packages', array( __CLASS__, 'tool_merge_packages' ) );
			add_filter( 'wpinv_admin_js_localize', array( __CLASS__, 'admin_js_localize' ), 10, 1 );
			add_filter( 'wpinv_item_non_editable_message', array( __CLASS__, 'notice_edit_package' ), 10, 2 );

			add_action( 'geodir_pricing_create_post_invoice', array( __CLASS__, 'create_invoice' ), 10, 1 );
			add_action( 'geodir_pricing_admin_list_packages_columns', array( __CLASS__, 'admin_list_packages_columns' ), 10, 1 );
			add_action( 'geodir_pricing_admin_list_packages_column', array( __CLASS__, 'admin_list_packages_column' ), 10, 2 );
			add_action( 'geodir_pricing_admin_list_packages_column_actions', array( __CLASS__, 'admin_list_packages_column_actions' ), 10, 2 );
		}

		add_action( 'wpinv_item_is_taxable', array( __CLASS__, 'gdp_to_gdi_set_zero_tax' ), 10, 4 ) ;
		add_action( 'wpinv_subscription_post_renew', array( __CLASS__, 'handle_subscription_renew' ), 10, 3 );
		add_action( 'wpinv_subscription_cancelled', array( __CLASS__, 'to_gdp_handle_subscription_cancel' ), 10, 2 );
		add_action( 'wpinv_subscription_completed', array( __CLASS__, 'to_gdp_handle_subscription_complete' ), 10, 2 );
		add_action( 'wpinv_checkout_cart_line_item_summary', array( __CLASS__, 'cart_line_item_summary' ), 10, 4 );
		add_action( 'wpinv_paypal_args', array( __CLASS__, 'paypal_args' ), 11, 3 );
		add_action( 'wpinv_status_publish', array( __CLASS__, 'order_completed' ), 100, 2 );
		add_action( 'wpinv_status_publish_to_wpi-cancelled', array( __CLASS__, 'order_cancelled' ), 100, 2 );
		add_action( 'wpinv_status_wpi-refunded', array( __CLASS__, 'order_refunded' ), 100, 2 );
		add_filter( 'wpinv_get_item_types', array( __CLASS__, 'get_package_type' ), 10, 1 );
		add_filter( 'wpinv_can_delete_item', array( __CLASS__, 'can_delete_package_item' ), 10, 2 );
		add_filter( 'wpinv_email_invoice_line_item_summary', array( __CLASS__, 'email_line_item_summary' ), 10, 4 );
		add_filter( 'wpinv_admin_invoice_line_item_summary', array( __CLASS__, 'admin_line_item_summary' ), 10, 4 );
		add_filter( 'wpinv_print_invoice_line_item_summary', array( __CLASS__, 'print_line_item_summary' ), 10, 4 );
		add_filter( 'wpinv_item_allowed_save_meta_value', array( __CLASS__, 'skip_save_package_price' ), 10, 3 );
		add_action( 'wpinv_display_details_before_due_date', array( __CLASS__, 'link_to_listing' ), 1, 1 );
		add_filter( 'wpinv_item_types_for_quick_add_item', array( __CLASS__, 'remove_package_for_quick_add_item' ), 10, 2 );
		add_filter( 'wpinv_item_dropdown_query_args', array( __CLASS__, 'item_dropdown_hide_packages' ), 10, 3 );
		add_filter( 'wpinv_get_option_address_autofill_api', array( __CLASS__, 'set_google_map_api_key' ), 10, 3 );

		add_action( 'geodir_after_save_package', array( __CLASS__, 'update_package_item' ), 10, 1 );
		add_action( 'geodir_pricing_deleted_package', array( __CLASS__, 'gdp_to_wpi_delete_package' ), 10, 1 ) ;
		add_action( 'geodir_pricing_save_package', array( __CLASS__, 'on_save_package' ), 100, 3 );
		add_filter( 'geodir_googlemap_script_extra', array( __CLASS__, 'google_map_places_params' ), 101, 1 );
		add_action( 'geodir_pricing_post_package_payment_completed', array( __CLASS__, 'post_package_payment_completed' ), 9, 2 );

		add_action( 'wpinv_verify_payment_ipn', array( __CLASS__, 'wpinv_verify_payment_ipn' ) );
	}

		/**
	 * Retrieves the default currency code.
	 * 
	 * @param string $current
	 */
	public function currency_code() {
		$currency_code =  wpinv_get_currency();

		return apply_filters( 'geodir_pricing_wpi_currency_code', $currency_code );
	}

	/**
	 * Given a currency, it returns a currency symbol.
	 * 
	 * @param string|null $currency The currency code. Defaults to the default currency.
	 */
	public function currency_sign( $currency = '' ) {
		$currency_sign = wpinv_currency_symbol( $currency );

		return apply_filters( 'geodir_pricing_wpi_currency_sign', $currency_sign, $currency );
	}

	/**
	 * Returns currency position.
	 * 
	 * @param $string|null $current
	 */
	public function currency_position() {
		$currency_position = wpinv_currency_position();

		return apply_filters( 'geodir_pricing_wpi_currency_position', $currency_position );
	}

	/**
	 * Returns the thousands separator for a currency.
	 * 
	 * @param $string|null $current
	 */
	public function thousand_separator() {
		$thousand_separator = wpinv_thousands_separator();

		return apply_filters( 'geodir_pricing_wpi_thousand_separator', $thousand_separator );
	}

	/**
	 * Returns the decimal separator for a currency.
	 * 
	 * @param $string|null $current
	 */
	public function decimal_separator() {
		$decimal_separator = wpinv_decimal_separator();

		return apply_filters( 'geodir_pricing_wpi_decimal_separator', $decimal_separator );
	}

	/**
	 * Returns the number of decimals to use.
	 * 
	 * @param $string|null $current
	 */
	public function decimals() {
		$decimals = wpinv_decimals();

		return apply_filters( 'geodir_pricing_wpi_decimals', $decimals );
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

		$item_ID = (int) geodir_pricing_get_meta( (int) $package_id, self::GEODIR_PRICING_WPI_PRODUCT_ID, true );
		if ( empty( $item_ID ) ) {
			$item = wpinv_get_item_by( 'custom_id', (int) $package_id, 'package' );

			if ( empty( $item ) ) {
				$item_ID = $item->ID;
			}
		}

		return (int) apply_filters( 'geodir_pricing_wpi_get_product_id', $item_ID, $package_id );
	}

	public static function get_package_id( $item ) {
		global $wpdb;

		if ( is_object( $item ) ) {
			$item_ID = $item->ID;
		} else if ( is_int( $item ) ) {
			$item_ID = $item;
		} else {
			$item_ID = 0;
		}

		$package_id = geodir_cache_get( 'geodir_pricing_wpi_product_package_id-' . $item_ID, 'geodir_pricing_wpi' );

		if ( $package_id !== false ) {
			return $package_id;
		}

		$package_id = $wpdb->get_var( $wpdb->prepare( "SELECT p.id FROM " . GEODIR_PRICING_PACKAGES_TABLE . " AS p LEFT JOIN " . GEODIR_PRICING_PACKAGE_META_TABLE . " AS pm ON pm.package_id = p.id WHERE pm.meta_key = %s AND pm.meta_value = %s ORDER BY `pm`.`meta_id` ASC", array( self::GEODIR_PRICING_WPI_PRODUCT_ID , $item_ID ) ) );

		if ( empty( $package_id ) ) {
			$package_id = get_post_meta( $item_ID, '_wpinv_custom_id', true );
		}

		$package_id = (int) apply_filters( 'geodir_pricing_wpi_get_package_id', $package_id, $item_ID );

		geodir_cache_set( 'geodir_pricing_wpi_product_package_id-' . $item_ID, $package_id, 'geodir_pricing_wpi' );

		return $package_id;
	}

	public static function admin_js_localize( $localize ) {
		$localize['hasInvoicing']       = (bool)defined( 'WPINV_VERSION' );
		$localize['hasGD']              = true;
		$localize['hasPM']              = true;
		$localize['deletePackage']      = __( 'GD package items should be deleted from GD pricing manager only, otherwise it will break invoices that created with this package!', 'geodir_pricing' );
		$localize['deletePackages']     = __( 'GD package items should be deleted from GD pricing manager only', 'geodir_pricing' );
		$localize['errDeletePackage']   = __( 'This item is in use! Before delete this package, you need to delete all the invoice(s) using this package.', 'geodir_pricing' );

		return $localize;
	}

	public static function notice_edit_package( $message, $item_ID ) {
		if ( get_post_meta( $item_ID, '_wpinv_type', true ) == 'package' && ( $package_id = (int) self::get_package_id( $item_ID ) ) ) {
			$post_type = geodir_pricing_package_post_type( $package_id );
			return wp_sprintf( __( 'GD price package can be edited from %sGeoDirectory > Settings > Pricing > Packages #%d%s', 'geodir_pricing' ), '<a href="' . admin_url('edit.php?post_type=' . $post_type . '&page=' . $post_type . '-settings&tab=cpt-package&section=add-package&id=' . $package_id ) . '">', $package_id, '</a>' );
		}

		return $message;
	}

	public static function geodir_integration() {
		if ( ! wp_doing_ajax() && ! isset( $_REQUEST['do_update_geodirectory'] ) && get_option( 'geodir_pricing_version' ) ) {
			// Merge price packages
			self::merge_packages_to_items();
		}
	}

	public static function get_package_type( $item_types ) {
		$item_types['package'] = __( 'Package', 'geodir_pricing' );
			
		return $item_types;
	}

	public static function update_package_item($package_id) {
		return self::merge_package_to_item($package_id, true);
	}

	public static function merge_packages_to_items( $force = false ) {    
		if ( ( $merged = geodir_get_option( 'pm_wpi_merge_packages' ) ) && ! $force ) {
			return true;
		}

		$packages = geodir_pricing_get_packages();
		
		foreach ( $packages as $key => $package ) {
			self::merge_package_to_item( $package->id, $force, $package );
		}

		if ( !$merged ) {
			geodir_update_option( 'pm_wpi_merge_packages', 1 );
		}

		return true;
	}

	public static function get_package_item( $package_id, $create = false ) {
		$item = wpinv_get_item_by( 'custom_id', $package_id, 'package' );

		if ( !$create ) {
			return $item;
		}

		return self::merge_package_to_item( $package_id, true );
	}

	public static function merge_package_to_item( $package_id, $force = false, $package = NULL) {
		if ( empty( $package_id ) ) {
			return false;
		}
		
		if ( empty( $package ) ) {
			$package = geodir_pricing_get_package( $package_id );
		}

		if ( !( !empty( $package->post_type ) && geodir_is_gd_post_type( $package->post_type ) ) ) {
			return false;
		}

		$data = array(
			'type'                 => 'package',
			'title'                => $package->name,
			'custom_id'            => $package_id,
			'price'                => wpinv_round_amount( $package->amount ),
			'status'               => $package->status == 1 ? 'publish' : 'pending',
			'custom_name'          => geodir_get_post_type_plural_label( $package->post_type ),
			'custom_singular_name' => geodir_get_post_type_singular_label( $package->post_type ),
			'vat_rule'             => 'digital',
			'vat_class'            => '_standard',
			'editable'             => false,
			'excerpt'              => $package->description,
		);
		
		if ( !empty( $package->recurring ) ) {
			$trial_interval = absint( $package->trial_interval );
			
			$data['is_recurring']       = 1;
			$data['recurring_period']   = $package->time_unit;
			$data['recurring_interval'] = absint( $package->time_interval );
			$data['recurring_limit']    = absint( $package->recurring_limit );
			$data['free_trial']         = $trial_interval > 0 ? 1 : 0;
			$data['trial_period']       = $package->trial_unit;
			$data['trial_interval']     = $trial_interval;
		} else {
			$data['is_recurring']       = 0;
			$data['recurring_period']   = '';
			$data['recurring_interval'] = '';
			$data['recurring_limit']    = '';
			$data['free_trial']         = 0;
			$data['trial_period']       = '';
			$data['trial_interval']     = '';
		}

		$data = apply_filters( 'geodir_pricing_wpi_sync_product_data', $data, $package );

		$exists = self::get_package_item( $package_id );

		$merged = wpinv_create_item( $data, false, $force );

		if ( $merged ) {
			$item = self::get_package_item( $package_id );
	
			if ( ! empty( $item ) ) {
				// Update meta.
				geodir_pricing_update_meta( $package_id, self::GEODIR_PRICING_WPI_PRODUCT_ID, $item->ID );
			}

			do_action( 'geodir_pricing_wpi_sync_product_done', $item, $package, empty( $exists ) );
		}

		return $merged;
	}

	public static function add_tools() {
		?>
		<tr>
			<td><?php _e( 'Merge Price Packages', 'geodir_pricing' ); ?></td>
			<td><p><?php _e( 'Merge GeoDirectory Pricing Manager price packages to the Invoicing items.', 'geodir_pricing' ); ?></p></td>
			<td><input type="button" data-tool="merge_packages" class="button-primary wpinv-tool" value="<?php esc_attr_e( 'Run', 'geodir_pricing' ); ?>"></td>
		</tr>
		<?php
	}

	public static function tool_merge_packages() {
		$packages = geodir_pricing_get_packages();
		
		$count = 0;
		
		if ( !empty( $packages ) ) {
			$success = true;
			
			foreach ( $packages as $key => $package ) {
				$item = wpinv_get_item_by('custom_id', $package->id, 'package');
				if ( !empty( $item ) ) {
					continue;
				}
				
				$merged = self::merge_package_to_item( $package->id, false, $package );
				
				if ( !empty( $merged ) ) {
					wpinv_error_log( 'Package merge S : ' . $package->id );
					$count++;
				} else {
					wpinv_error_log( 'Package merge F : ' . $package->id );
				}
			}
			
			if ( $count > 0 ) {
				$message = sprintf( _n( 'Total <b>%d</b> price package is merged successfully.', 'Total <b>%d</b> price packages are merged successfully.', $count, 'geodir_pricing' ), $count );
			} else {
				$message = __( 'No price packages merged.', 'geodir_pricing' );
			}
		} else {
			$success = false;
			$message = __( 'No price packages found to merge!', 'geodir_pricing' );
		}
		
		$response = array();
		$response['success'] = $success;
		$response['data']['message'] = $message;
		wp_send_json( $response );
	}

	public static function gdp_to_wpi_delete_package( $gd_package_id ) {
		$item = wpinv_get_item_by( 'custom_id', $gd_package_id, 'package' );
		
		if ( !empty( $item ) ) {
			wpinv_remove_item( $item, true );
		}
	}

	public static function can_delete_package_item( $return, $item_ID ) {
		global $wpdb;

		if ( $return && get_post_meta( $item_ID, '_wpinv_type', true ) == 'package' ) {
			$package_id = $wpdb->get_var( $wpdb->prepare( "SELECT p.id FROM " . GEODIR_PRICING_PACKAGES_TABLE . " AS p LEFT JOIN " . GEODIR_PRICING_PACKAGE_META_TABLE . " AS pm ON pm.package_id = p.id WHERE pm.meta_key = %s AND pm.meta_value = %s ORDER BY `pm`.`meta_id` ASC", array( self::GEODIR_PRICING_WPI_PRODUCT_ID , $item_ID ) ) );

			if ( empty( $package_id ) ) {
				$return = true;
			} else {
				$return = false;
			}
		}

		return $return;
	}

	public static function package_item_classes( $classes, $class, $item_ID ) {
		global $typenow;

		if ( $typenow == 'wpi_item' && in_array( 'wpi-type-package', $classes ) ) {
			if ( wpinv_item_in_use( $item_ID ) ) {
				$classes[] = 'wpi-inuse-pkg';
			} else if ( !( get_post_meta( $item_ID, '_wpinv_type', true ) == 'package' && geodir_pricing_get_package( (int) self::get_package_id( $item_ID ) ) ) ) {
				$classes[] = 'wpi-delete-pkg';
			}
		}

		return $classes;
	}

	public static function gdp_package_type_info( $post ) {
		?><p class="wpi-m0"><?php _e( 'Package: GeoDirectory price packages items.', 'geodir_pricing' );?></p><?php
	}

	public static function gdp_to_gdi_set_zero_tax( $is_taxable, $item_id, $country , $state ) {
		global $wpi_zero_tax;

		if ( $wpi_zero_tax ) {
			$is_taxable = false;
		}

		return $is_taxable;
	}

	public static function handle_subscription_renew( $subscription_id, $expiration, $subscription ) {
		$invoice = ! empty( $subscription->parent_payment_id ) ? wpinv_get_invoice( $subscription->parent_payment_id ) : NULL;
		
		if ( ! empty( $invoice ) && ! empty( $invoice->ID ) && $invoice->is_recurring() && ( $item_ID = (int) $invoice->get_recurring() ) ) {
			if ( get_post_meta( $item_ID, '_wpinv_type', true ) == 'package' && ( $package_id = (int) self::get_package_id( $item_ID ) ) ) {
				$items = GeoDir_Pricing_Post_Package::get_items( array( 'invoice_id' => $invoice->ID, 'package_id' => $package_id ) );

				if ( empty( $items ) )  {
					return false;
				}

				foreach ( $items as $post_package_item ) {
					if ( empty( $post_package_item->post_id ) ) {
						continue;
					}
					$post_id = absint( $post_package_item->post_id );

					$prev_expire_date = geodir_get_post_meta( $post_id, 'expire_date', true );
					if ( ! geodir_pricing_date_never_expire( $prev_expire_date ) && strtotime( $prev_expire_date ) <= strtotime( date_i18n( 'Y-m-d' ) ) ) {
						$prev_expire_date = '';
					}

					// New expire date
					$expire_date = geodir_pricing_new_expire_date( geodir_pricing_package_alive_days( $package_id ), $prev_expire_date );

					// Set new expire date
					geodir_save_post_meta( $post_id, 'expire_date', $expire_date );
				}
			}
		}
	}

	public static function to_gdp_handle_subscription_complete( $subscription_id, $subscription ) {
		if ( empty( $subscription->parent_payment_id ) ) {
			return;
		}
		
		$invoice_id = $subscription->parent_payment_id;
		$invoice = wpinv_get_invoice( $invoice_id );
		
		if ( !empty( $invoice ) && !empty( $invoice->ID ) && $invoice->is_recurring() ) {
			self::to_gdp_subscription_completed( $subscription, $invoice );
		}
	}

	public static function to_gdp_subscription_completed( $subscription, $invoice ) {
		if ( empty( $invoice ) ) {
			return false;
		}
		
		if ( is_int( $invoice ) ) {
			$invoice = new WPInv_Invoice( $invoice );
		}

		$item_ID = ! empty( $invoice->ID ) ? (int) $invoice->get_recurring() : 0;
		if ( empty( $item_ID ) ) {
			return false;
		}

		if ( get_post_meta( $item_ID, '_wpinv_type', true ) == 'package' && ( $package_id = (int) self::get_package_id( $item_ID ) ) ) {
			$items = GeoDir_Pricing_Post_Package::get_items( array( 'invoice_id' => $invoice_id, 'package_id' => $package_id ) );

			if ( empty( $items ) )  {
				return false;
			}

			foreach ( $items as $post_package_item ) {
				if ( empty( $post_package_item->post_id ) ) {
					continue;
				}

				if ( geodir_pricing_is_post_expire_active() ) {
					update_post_meta( $post_package_item->post_id, '_gdpm_cancel_at_period_end', true ); // Set cancel at period end
				} else {
					self::set_post_status( $post_id, 'draft' );
				}
			}
		}
	}

	public static function to_gdp_handle_subscription_cancel( $subscription_id, $subscription ) {
		if ( empty( $subscription->parent_payment_id ) ) {
			return;
		}
		
		$invoice_id = $subscription->parent_payment_id;
		$invoice = wpinv_get_invoice( $invoice_id );
		
		if ( !empty( $invoice ) && !empty( $invoice->ID ) && $invoice->is_recurring() ) {
			self::to_gdp_subscription_ended( $subscription, $invoice );
		}
	}

	public static function to_gdp_subscription_ended( $subscription, $invoice ) {
		if ( empty( $invoice ) ) {
			return false;
		}

		if ( is_int( $invoice ) ) {
			$invoice = new WPInv_Invoice( $invoice );
		}

		$item_ID = ! empty( $invoice->ID ) ? (int) $invoice->get_recurring() : 0;
		if ( empty( $item_ID ) ) {
			return false;
		}

		if ( get_post_meta( $item_ID, '_wpinv_type', true ) == 'package' && ( $package_id = (int) self::get_package_id( $item_ID ) ) ) {
			$items = GeoDir_Pricing_Post_Package::get_items( array( 'invoice_id' => $invoice->ID, 'package_id' => $package_id ) );

			if ( empty( $items ) )  {
				return false;
			}

			foreach ( $items as $post_package_item ) {
				if ( empty( $post_package_item->post_id ) ) {
					continue;
				}

				if ( geodir_pricing_is_post_expire_active() ) {
					update_post_meta( $post_package_item->post_id, '_gdpm_cancel_at_period_end', true ); // Set cancel at period end
				} else {
					self::set_post_status( $post_id, 'draft' );
				}
			}
		}
	}

	public static function cart_line_item_summary( $summary, $cart_item, $wpi_item, $invoice ) {
		if ( !empty( $wpi_item ) && !empty( $cart_item['meta']['post_id'] ) && $wpi_item->get_type() == 'package' ) {
			$post_link = !empty( $cart_item['meta']['invoice_title'] ) ? $cart_item['meta']['invoice_title'] : get_the_title( $cart_item['meta']['post_id'] );
			$summary = wp_sprintf( __( '%s: %s', 'geodir_pricing' ), $wpi_item->get_custom_singular_name(), $post_link );
			$summary = '<small class="meta">' . wpautop( wp_kses_post( $summary ) ) . '</small>';
		}
		
		return $summary;
	}

	public static function email_line_item_summary( $summary, $cart_item, $wpi_item, $invoice ) {
		if ( !empty( $wpi_item ) && !empty( $cart_item['meta']['post_id'] ) && $wpi_item->get_type() == 'package' ) {
			$post_link = '<a href="' . get_permalink( $cart_item['meta']['post_id'] ) .'" target="_blank">' . ( !empty($cart_item['meta']['invoice_title'] ) ? $cart_item['meta']['invoice_title'] : get_the_title( $cart_item['meta']['post_id']) ) . '</a>';
			$summary = wp_sprintf( __( '%s: %s', 'geodir_pricing' ), $wpi_item->get_custom_singular_name(), $post_link );
		}

		return $summary;
	}

	public static function admin_line_item_summary( $summary, $cart_item, $wpi_item, $invoice ) {
		if ( !empty( $wpi_item ) && !empty( $cart_item['meta']['post_id'] ) && $wpi_item->get_type() == 'package' ) {
			$post_link = '<a href="' . get_edit_post_link( $cart_item['meta']['post_id'] ) .'" target="_blank">' . (!empty($cart_item['meta']['invoice_title']) ? $cart_item['meta']['invoice_title'] : get_the_title( $cart_item['meta']['post_id']) ) . '</a>';
			$summary = wp_sprintf( __( '%s: %s', 'geodir_pricing' ), $wpi_item->get_custom_singular_name(), $post_link );
		}

		return $summary;
	}

	public static function print_line_item_summary( $summary, $cart_item, $wpi_item, $invoice ) {
		if ( !empty( $wpi_item ) && !empty( $cart_item['meta']['post_id'] ) && $wpi_item->get_type() == 'package' ) {
			$title = !empty( $cart_item['meta']['invoice_title'] ) ? $cart_item['meta']['invoice_title'] : get_the_title( $cart_item['meta']['post_id'] );
			$summary = wp_sprintf( __( '%s: %s', 'geodir_pricing' ), $wpi_item->get_custom_singular_name(), $title );
		}
		
		return $summary;
	}

	public static function metabox_price_note( $item ) {
		if ( !empty( $item ) && $item->get_type() == 'package' ) { ?>
			<span class="description"><?php _e( 'GD package item price can be edited only from GD pricing manager.', 'geodir_pricing' ); ?></span>
		<?php }
	}

	public static function skip_save_package_price( $return, $field, $post_id ) {
		if ( !empty( $post_id ) && $field == '_wpinv_price' && get_post_meta( $post_id, '_wpinv_type', true ) == 'package' ) {
			$return = false;
		}

		return $return;
	}

	public static function remove_package_for_quick_add_item( $item_types, $post = array() ) {
		if ( isset( $item_types['package'] ) ) {
			unset( $item_types['package'] );
		}
			
		return $item_types;
	}

	public static function item_dropdown_hide_packages( $item_args, $args, $defaults ) {
		if ( !empty( $args['name'] ) && $args['name'] == 'wpinv_invoice_item' ) {
			$item_args['meta_query'] = array(
				array(
					'key'       => '_wpinv_type',
					'compare'   => '!=',
					'value'     => 'package'
				),
			);
		}

		return $item_args;
	}

	public static function google_map_places_params( $extra ) {
		if ( wpinv_is_checkout() && strpos( $extra, 'libraries=places' ) === false ) {
			$extra .= "&amp;libraries=places";
		}

		return $extra;
	}

	public static function set_google_map_api_key( $value, $key, $default ) {
		if ( empty( $value ) && ( $api_key = GeoDir_Maps::google_api_key() ) ) {
			$value = $api_key;
			wpinv_update_option( 'address_autofill_api', $api_key );
		}

		return $value;
	}

	/**
	 * Register meta box to create invoice for listing.
	 *
	 * @since 2.0.32
	 *
	 * @param string  $post_type Post type.
	 * @param WP_Post $post      Post object.
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		if ( geodir_is_gd_post_type( $post_type ) ) {
			$add_meta_box = apply_filters( 'geodir_pricing_add_meta_boxes', current_user_can( 'manage_options' ), $post_type, $post );

			if ( $add_meta_box && self::allow_invoice_for_listing( $post->ID ) ) {
				add_meta_box( 'gd-wpi-create-invoice', __( 'Listing Invoice' ), array( __CLASS__, 'display_meta_box_create_invoice' ), $post_type, 'side', 'high' );
			}

			if ( $add_meta_box ) {
				add_meta_box( 'gd-wpi-post-invoices', __( 'Listing Invoices', 'geodir_pricing' ), array( __CLASS__, 'display_meta_box_post_invoices' ), $post_type );
			}
		}
	}

	/**
	 * 
	 *
	 * @since 2.0.32
	 *
	 * @param  WP_Post $post      Post object.
	 * @return bool
	 */
	public static function allow_invoice_for_listing( $post_ID ) {
		$return = in_array( get_post_status( $post_ID ), array( 'draft', 'gd-expired' ) ) ? true : false;

		if ( $return ) {
			$gd_post = geodir_get_post_info( $post_ID );

			if ( empty( $gd_post->package_id ) ) {
				$return = false;
			}

			// @todo move to Franchise
			if ( $return && function_exists( 'geodir_franchise_enabled' ) && geodir_franchise_enabled( $gd_post->post_type ) ) {
				$parent_ID = geodir_franchise_main_franchise_id( $post_ID );

				if ( $parent_ID && $parent_ID != $post_ID ) {
					$return = false; // Do not allow to create invoice for franchise listings.
				}
			}
		}

		return apply_filters( 'geodir_wpi_allow_invoice_for_listing', $return, $post_ID );
	}

	/**
	 * 
	 *
	 * @since 2.0.32
	 *
	 * @param  WP_Post $post      Post object.
	 * @return bool
	 */
	public static function display_meta_box_create_invoice( $post ) {
		$package_id = geodir_get_post_meta( $post->ID, 'package_id', true );
		$package_info = geodir_pricing_get_package( (int) $package_id );

		if ( empty( $package_info ) ) {
			return;
		}
		$package_title = strip_tags( __( stripslashes_deep( $package_info->title ), 'geodirectory' ) );
		?>
		<p><?php echo wp_sprintf( __( 'Create a new invoice for this listing with a package <b>%s</b>. Invoice will be created with Pending Payment status.', 'geodir_pricing' ), $package_title ); ?></p>
		<div id="gd-btn-action">
			<span class="spinner"></span>
			<input id="geodir_create_post_invoice" data-nonce-create-invoice="<?php echo esc_attr( wp_create_nonce( 'create-invoice-' . $post->ID ) ); ?>" data-id="<?php echo absint( $post->ID ); ?>" class="button button-primary button-large" value="<?php esc_attr_e( 'Create Invoice for this Listing', 'geodir_pricing' ); ?>" type="button">
		</div>
		<?php
	}

	/**
	 * 
	 *
	 * @since 2.0.32
	 *
	 */
	public static function create_invoice( $post_id ) {
		global $wpdb;

		$json            = array();
		$json['success'] = false;
		
		if ( $post_id > 0 ) {
			$json['success'] = false;
			if ( self::allow_invoice_for_listing( $post_id ) ) {
				$gd_post = geodir_get_post_info( $post_id );
				$package_info = $gd_post->package_id ? geodir_pricing_get_package( $gd_post->package_id ) : NULL;
				
				if ( !empty( $package_info->id ) ) {
					$package_id = $package_info->id;
					
					$item = wpinv_get_item_by( 'custom_id', $package_id, 'package' );

					if ( !empty( $item->ID ) ) {
						$invoice_title = wp_sprintf(  __( 'Payment For: %s', 'geodir_pricing' ), get_the_title( $post_id ) );

						$data = array(
							'status'        => 'wpi-pending',
							'user_id'       => $gd_post->post_author,
							'cart_details'  => array(
								array(
									'id'    => $item->ID,
									'meta'  => array( 
										'post_id'       => $post_id,
										'invoice_title' => $invoice_title
									),
								),
							)
						);

						$invoice = wpinv_insert_invoice( $data, true );

						if ( ! is_wp_error( $invoice ) && !empty( $invoice->ID ) ) {
							$save_data = array(
								'post_id' => $post_id,
								'package_id' => $package_id,
								'product_id' => $item->ID,
								'invoice_id' => $invoice->ID,
								'task' => 'renew',
								'cart' => 'invoicing',
								'status' => $invoice->get_status(),
								'meta' => maybe_serialize( array( 'task' => 'renew' ) ),
								'date' => date_i18n( 'Y-m-d H:i:s' )
							);
							GeoDir_Pricing_Post_Package::save( $save_data );

							// Add a note to the invoice to link it to the listing.
							$note = sprintf(
								/* translators: %1$s is listing title, %2$s is listing post type. */
								esc_html__( 'Invoice For: %1$s (%2$s)', 'geodir_pricing' ),
								sprintf(
									'<a href="%s">%s</a>',
									esc_url( get_edit_post_link( $post_id ) ),
									esc_html( get_the_title( $post_id ) )
								),
								esc_html( geodir_post_type_singular_name( get_post_type( $post_id ), true ) )
							);
							$invoice->add_note( $note );
					
							$json['success'] = true;
							$json['link']    = '<a target="_blank" href="' . get_edit_post_link( $invoice->ID ) . '">' . wp_sprintf( __( 'View Invoice #%s' , 'geodir_pricing' ), $invoice->get_number() ) . '</a>';
						} else {
							if ( is_wp_error( $invoice ) ) {
								$json['msg'] = wp_sprintf( __( 'Fail to create invoice. No invoicing item found with the package selected.' , 'geodir_pricing' ), implode( ', ', $invoice->get_error_messages() ) );
							} else {
								$json['msg'] = __( 'Fail to create invoice. Please refresh page and try again.' , 'geodir_pricing' );
							}
						}
					} else {
						$json['msg'] = __( 'Fail to create invoice. No invoicing item found with the package selected.' , 'geodir_pricing' );
					}
				} else {
					$json['msg'] = __( 'Fail to create invoice. No package assigned to this listing.' , 'geodir_pricing' );
				}
			} else {
				$json['msg'] = __( 'This listing is not allowed to create invoice.' , 'geodir_pricing' );
			}
		}

		wp_send_json( $json );
	}

	public static function display_meta_box_post_invoices( $post ) {
		$post_invoices = self::get_post_invoices( $post->ID );

		if ( empty( $post_invoices ) ) {
			_e( '<p>No invoice(s) found for this listing.</p>' , 'geodir_pricing' );
			return;
		}
		?>
		<table class="wp-list-table widefat fixed striped posts gd-listing-invoices">
			<thead>
				<th class="column-wpi_number"><?php _e('Number', 'geodir_pricing'); ?></th>
				<th class="column-wpi_customer"><?php _e('Customer', 'geodir_pricing'); ?></th>
				<th class="column-wpi_amount"><?php _e('Amount', 'geodir_pricing'); ?></th>
				<th class="column-wpi_invoice_date"><?php _e('Created Date', 'geodir_pricing'); ?></th>
				<th class="column-wpi_payment_date"><?php _e('Payment Date', 'geodir_pricing'); ?></th>
				<th class="column-wpi_status"><?php _e('Status', 'geodir_pricing'); ?></th>
				<th class="column-wpi_actions"><?php _e('Actions', 'geodir_pricing'); ?></th>
			</thead>
			<tbody>
		<?php
		foreach ( $post_invoices as $key => $item ) {
			$invoice_id = $item->invoice_id;
			if ( ! $invoice_id ) {
				continue;
			}
			$invoice = wpinv_get_invoice( $invoice_id );
			if ( empty( $invoice ) ) {
				continue;
			}
			$edit_link = get_edit_post_link( $invoice_id );
			$value = '<a title="' . esc_attr__( 'View Invoice Details', 'invoicing' ) . '" href="' . esc_url( $edit_link ) . '">' . $invoice->get_number() . '</a>';
			$customer_name = $invoice->get_user_full_name();
			$customer_name = $customer_name != '' ? $customer_name : __( 'Customer', 'invoicing' );
			$customer = '<a href="' . esc_url( get_edit_user_link( $invoice->get_user_id() ) ) . '">' . $customer_name . '</a>';
			if ( $email = $invoice->get_email() ) {
				$customer .= '<br><a class="email" href="mailto:' . $email . '">' . $email . '</a>';
			}
			$date_format = geodir_date_format();
			$time_format = geodir_time_format();

			// invoice date
			$m_time = get_post_field( 'post_date', $invoice->ID );
			$h_time = mysql2date( $date_format, $m_time );
			$invoice_date   = '<abbr title="' . $m_time . '">' . $h_time . '</abbr>';

			// payment_date
			if ( $date_completed = $invoice->get_meta( '_wpinv_completed_date', true ) ) {
				$m_time = $date_completed;
				$h_time = mysql2date( $date_format, $m_time );
				
				$payment_date   = '<abbr title="' . $m_time . '">' . $h_time . '</abbr>';
			} else {
				$payment_date = '-';
			}

			// status
			$status   = $invoice->get_status( true ) . ( $invoice->is_recurring() && $invoice->is_parent() ? ' <span class="wpi-suffix">' . __( '(r)', 'geodir_pricing' ) . '</span>' : '' );
			if ( ( $invoice->is_paid() || $invoice->is_refunded() ) && ( $gateway_title = wpinv_get_gateway_admin_label( $invoice->get_gateway() ) ) ) {
				$status .= '<br><small class="meta gateway">' . wp_sprintf( __( 'Via %s', 'geodir_pricing' ), $gateway_title ) . '</small>';
			}

			// actions
			$actions = '<a title="' . esc_attr__( 'Print invoice', 'invoicing' ) . '" href="' . esc_url( get_permalink( $invoice->ID ) ) . '" class="button ui-tip column-act-btn" title="" target="_blank"><span class="dashicons dashicons-print"><i style="" class="fa fa-print"></i></span></a>';

			if ( $email = $invoice->get_email() ) {
				$actions .= ' <a title="' . esc_attr__( 'Send invoice to customer', 'invoicing' ) . '" href="' . esc_url( add_query_arg( array( 'wpi_action' => 'send_invoice', 'invoice_id' => $invoice->ID ) ) ) . '" class="button ui-tip column-act-btn"><span class="dashicons dashicons-email-alt"></span></a>';
			}

			$actions = apply_filters( 'geodir_wpi_listing_invoices_actions', $actions, $invoice );
		?>
		<tr>
			<td class="column-wpi_number"><?php echo $value; ?></td>
			<td class="column-wpi_customer"><?php echo $customer; ?></td>
			<td class="column-wpi_amount"><?php echo $invoice->get_total( true ); ?></td>
			<td class="column-wpi_invoice_date"><?php echo $invoice_date; ?></td>
			<td class="column-wpi_payment_date"><?php echo $payment_date; ?></td>
			<td class="column-wpi_status"><?php echo $status; ?></td>
			<td class="column-wpi_actions"><?php echo $actions; ?></td>
		</tr>
		<?php
		}
		?>
		</tbody>
		</table><?php
	}

	public static function get_post_invoices( $post_id = 0 ) {
		global $wpdb;

		if ( ! $post_id ) {
			return false;
		}

		$invoices = GeoDir_Pricing_Post_Package::get_items( array( 'post_id' => $post_id, 'order_by' => 'id DESC' ) );

		return $invoices;
	}

	public static function paypal_args( $paypal_args, $purchase_data, $invoice ) {
		if ( empty( $invoice ) ) {
			return $paypal_args;
		}

		$items = GeoDir_Pricing_Post_Package::get_items( array( 'invoice_id' => $invoice->ID ) );
		if ( empty( $items ) ) {
			return $paypal_args;
		}

		$invoice_title = get_the_title( $items[0]->post_id ) . ' - ' . geodir_pricing_package_name( (int) $items[0]->package_id );

		if ( $invoice->is_recurring() ) {
			$item_name                  = sprintf( '[%s] %s', $invoice->get_number(), $invoice_title );
			$paypal_args['item_name']   = stripslashes_deep( html_entity_decode( $item_name, ENT_COMPAT, 'UTF-8' ) );
		} else {
			$i = 0;
			foreach ( $invoice->cart_details as $key => $cart_item ) {
				$i++;
				if ( isset( $cart_item['id'] ) && isset( $paypal_args['item_number_' . $i] ) && $cart_item['id'] == $paypal_args['item_number_' . $i] ) {
					$item_name  = ! empty( $cart_item['meta']['invoice_title'] ) ? $cart_item['meta']['invoice_title'] : $invoice_title;
					$item_name  = sprintf( '[%s] %s', $invoice->get_number(), $item_name );
					$paypal_args['item_name_' . $i] = stripslashes_deep( html_entity_decode( $item_name, ENT_COMPAT, 'UTF-8' ) );
				}
			}
		}

		return $paypal_args;
	}

	public function price( $_price, $args = array() ) {
		$currency = '';
		if ( ! empty( $args['currency'] ) ) {
			$currency = $args['currency'];
		}

		$price = wpinv_price( wpinv_format_amount( $_price ), $currency );

		return apply_filters( 'geodir_pricing_wpi_price', $price, $_price, $args );
	}

	public static function on_save_package( $package_id, $package, $update = false ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return self::merge_package_to_item( $package_id, true );
	}

	public function ajax_post_saved( $post_data ) {

		// first save the submitted data
		$result = GeoDir_Post_Data::auto_save_post( $post_data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$has_invoice = false;
		// get current status
		$parent_post_id = !empty($post_data['post_parent']) ? $post_data['post_parent'] : $post_data['ID'];

		// new post
		if ( geodir_pricing_is_new( $post_data ) ) {
			$task = 'new';

			//Check if its a logged out user and if we have details to register the user
			$post_data = GeoDir_Post_Data::check_logged_out_author($post_data);

			// if its new and an auto-draft then we save it as pending
			$post_status = get_post_status( $parent_post_id );
			if(empty($post_data['post_parent']) && $post_status=='auto-draft'){
				wp_update_post( array('ID'=> $post_data['ID'],'post_status'=>'pending'));
			}
			// if its new and a revision then just save it
			elseif(!empty($post_data['post_parent'])){
				wp_restore_post_revision( $post_data['ID'] );
				$post_data['ID'] = $parent_post_id;
			}

			$has_invoice = self::has_invoice($post_data['ID'],'new');

		} else if ( geodir_pricing_is_upgrade( $post_data ) ) { // upgrade
			$task = 'upgrade';
			$has_invoice = self::has_invoice($post_data['ID']);
		} else if ( geodir_pricing_is_renew( $post_data ) ) { // renewal
			$task = 'renew';
			$has_invoice = self::has_invoice($post_data['ID']);
		} else { // update
			wp_restore_post_revision( $post_data['ID'] );
			$task = 'update';
		}

		if ( $task != 'update' && ! empty( $post_data['package_id'] ) && ( $item = wpinv_get_item_by( 'custom_id', (int) $post_data['package_id'], 'package' ) ) ) {

			$post_id = $post_data['ID'];


			switch ( $task ) {
				case 'renew':
					$invoice_title = wp_sprintf(  __( 'Renew: %s', 'geodir_pricing' ), get_the_title( $post_id ) );
					break;
				case 'upgrade':
					$invoice_title = wp_sprintf(  __( 'Upgrade: %s', 'geodir_pricing' ), get_the_title( $post_id ) );
					break;
				case 'new':
					$invoice_title = wp_sprintf(  __( 'Add: %s', 'geodir_pricing' ), get_the_title( $post_id ) );
					break;
				default:
					$invoice_title = get_the_title( $post_id );
					break;
			}

			try {
				wpinv_empty_cart();

				if ( !empty( $item->ID ) ) {

					if(!empty($has_invoice->invoice_id) && $has_invoice->cart=='invoicing'){
						$invoice = wpinv_get_invoice( $has_invoice->invoice_id );
					}else{
						$user_id = !empty($post_data['post_author']) ? (int)$post_data['post_author'] : get_current_user_id();
						$data = array(
							'status'        => 'wpi-pending',
							'user_id'       => $user_id,
							'cart_details'  => array(
								array(
									'id'    => $item->ID,
									'meta'  => array(
										'post_id'       => $parent_post_id,
										'invoice_title' => $invoice_title
									),
								),
							)
						);

						$data = apply_filters( 'geodir_pricing_wpi_insert_invoice_data', $data, $task, $post_id, $post_data['package_id'], $post_data );

						$invoice = wpinv_insert_invoice( $data, true );
					}


					if ( ! is_wp_error( $invoice ) && ! empty( $invoice->ID ) ) {
						$note = sprintf(
							/* translators: %1$s is listing title, %2$s is listing post type. */
							esc_html__( 'Invoice for the listing: %1$s (%2$s)', 'geodir_pricing' ),
							sprintf(
								'<a href="%s">%s</a>',
								esc_url( get_edit_post_link( $post_data['ID'] ) ),
								esc_html( get_the_title( $post_data['ID'] ) )
							),
							esc_html( geodir_post_type_singular_name( get_post_type( $post_data['ID'] ), true ) )
						);
						$invoice->add_note( $note );

						$data = array(
							'post_id' => $post_data['ID'],
							'package_id' => $post_data['package_id'],
							'product_id' => $item->ID,
							'invoice_id' => $invoice->ID,
							'task' => $task,
							'cart' => 'invoicing',
							'status' => 'wpi-pending',
							'meta' => maybe_serialize( array( 'task' => $task ) ),
							'date' => date_i18n( 'Y-m-d H:i:s' )
						);



						// if we have a package id then we just update it.
						if(!empty($has_invoice->id)){
							$data['id'] = $has_invoice->id;
						}

						$data = apply_filters( 'geodir_pricing_wpi_post_package_data', $data, $post_id, $post_data['package_id'], $post_data );

						GeoDir_Pricing_Post_Package::save( $data );

						if ( $invoice->is_free() ) {
							$invoice->set( 'transaction_id', time() );
							$invoice->set( 'gateway', 'manual' );
							$invoice->set( 'status', 'publish' );
							$invoice->save();


							// if its free then send the standard message
							$post_data['ID'] = $parent_post_id;
							$post_data['post_status'] = geodir_pricing_paid_post_status( $post_data['package_id'], $parent_post_id, $task );
							return GeoDir_Post_Data::ajax_save_post_message($post_data);

						} else if ( $invoice->needs_payment() ) {
							$data                   = array();
							$data['invoice_id']     = $invoice->ID;
							$data['cart_discounts'] = $invoice->get_discounts( true );
							
							if ( wpinv_get_option( 'vat_ip_country_default' ) ) {
								global $wpinv_euvat;
								$_POST['country']   = $wpinv_euvat->get_country_by_ip();
								$_POST['state']     = $_POST['country'] == $invoice->country ? $invoice->state : '';
								
								wpinv_recalculate_tax( true );
							}

							return self::ajax_save_post_message($task,$post_data,$invoice->get_checkout_payment_url());
							
						}
					}
				}
			} catch ( Exception $e ) {
				geodir_error_log( $e->getMessage(), 'Pricing -> WPI' );
			}
		}

		return '';
	}
	

	public static function order_completed( $invoice_id, $old_status ) {


		$invoice = wpinv_get_invoice( $invoice_id );

		if ( empty( $invoice ) ) {
			return false;
		}

		$items = GeoDir_Pricing_Post_Package::get_items( array( 'invoice_id' => $invoice_id ) );

		if ( ! empty( $items ) ) {
			$date = $invoice->get_invoice_date( false );

			foreach ( $items as $key => $item ) {
				$revision_id = '';
				$data = array(
					'id' => $item->id,
					'status' => $invoice->get_status(),
					'date' => $date
				);

				// If its is revision post then we need to swap the post id.
				if ( $post_id = wp_is_post_revision( $item->post_id ) ) {
					$revision_id = $item->post_id;
					$data['post_id'] = $post_id;
					$item->post_id = $post_id;
				}

				GeoDir_Pricing_Post_Package::save( $data );

				do_action( 'geodir_pricing_post_package_payment_completed', $item,$revision_id );
			}
		}
	}

	public static function order_cancelled( $invoice_id, $old_status ) {
		$invoice = wpinv_get_invoice( $invoice_id );

		if ( empty( $invoice ) ) {
			return false;
		}

		$items = GeoDir_Pricing_Post_Package::get_items( array( 'invoice_id' => $invoice_id ) );

		if ( ! empty( $items ) ) {
			$date = $invoice->get_invoice_date( false );

			foreach ( $items as $key => $item ) {
				$data = array(
					'id' => $item->id,
					'status' => $invoice->get_status(),
					'date' => $date
				);
				GeoDir_Pricing_Post_Package::save( $data );

				do_action( 'geodir_pricing_post_package_payment_cancelled', $item );
			}
		}
	}

	public static function order_refunded( $invoice_id, $old_status ) {
		$invoice = wpinv_get_invoice( $invoice_id );

		if ( empty( $invoice ) ) {
			return false;
		}

		$items = GeoDir_Pricing_Post_Package::get_items( array( 'invoice_id' => $invoice_id ) );

		if ( ! empty( $items ) ) {
			$date = $invoice->get_invoice_date( false );

			foreach ( $items as $key => $item ) {
				$data = array(
					'id' => $item->id,
					'status' => $invoice->get_status(),
					'date' => $date
				);
				GeoDir_Pricing_Post_Package::save( $data );

				do_action( 'geodir_pricing_post_package_payment_refunded', $item );
			}
		}
	}

	public static function admin_list_packages_columns( $columns = array() ) {
		$replacement = array(
			'product_id' => __( 'Invoicing Item', 'geodir_pricing' )
		);

		$position = array_search( 'lifetime', array_keys( $columns ) );
		$position = false === $position ? count( $columns ) : $position + 1;
		$columns = array_merge( array_slice( $columns, 0, $position ), $replacement, array_slice( $columns, $position ) );

		return $columns;
	}

	public static function admin_list_packages_column_actions( $actions, $item ) {
		$actions['sync'] = '<a href="javascript:void(0)" class="geodir-sync-package geodir-act-sync" title="' . esc_attr( 'Synchronize package to Invoicing item' ) . '" data-sync-nonce="' . esc_attr( wp_create_nonce( 'geodir-sync-package-' . $item['id'] ) ) . '"><i class="fas fa-sync"></i></a>';

		return $actions;
	}

	public static function admin_list_packages_column( $item = array(), $column_name = '' ) {
		switch ( $column_name ) {
			case 'product_id':
				$invoice_item = self::get_package_item( $item['id'] );
				if ( ! empty( $invoice_item ) ) {
					$edit_link = get_edit_post_link( $invoice_item->ID );
				
					echo sprintf( '<a href="%s">%d</a>', $edit_link, $invoice_item->ID );
				} else {
					echo '<a href="javascript:void(0)" class="geodir-sync-package geodir-act-sync" title="' . esc_attr( 'Synchronize package to Invoicing item' ) . '" data-sync-nonce="' . esc_attr( wp_create_nonce( 'geodir-sync-package-' . $item['id'] ) ) . '" data-reload="1"><i class="fas fa-sync"></i></a>';
				}
				break;
		}
	}

	public static function sync_package_to_cart_item( $package_id ) {
		return self::update_package_item( $package_id );
	}

	public static function set_post_status( $post_id, $status ) {
		if ( empty( $post_id ) || empty( $status ) ) {
			return false;
		}

		$data = array();
		$data['ID'] = $post_id;
		if ( $status != get_post_status( $status ) ) {
			$data['post_status'] = $status;
		}

		return wp_update_post( $data );
	}

	/**
	 * Create a invoice for claiming a listing.
	 * 
	 * @param $post_id
	 * @param $package_id
	 * @param $user_id
	 * @param $payment_id
	 *
	 * @return mixed
	 */
	public static function create_claim_invoice( $post_id, $package_id, $user_id, $payment_id = 0 ) {
		$task = 'claim';
		$invoice_title = wp_sprintf(  __( 'Claim: %s', 'geodir_pricing' ), get_the_title( $post_id ) );
		$item = wpinv_get_item_by( 'custom_id', (int) $package_id, 'package' );

		if ( empty( $item->ID ) ) {
			return false;
		}

		$post_data = (array) geodir_get_post_info( $post_id );

		try {
			wpinv_empty_cart();

			$data = array(
				'status'        => 'wpi-pending',
				'user_id'       => $user_id,
				'cart_details'  => array(
					array(
						'id'    => $item->ID,
						'meta'  => array(
							'post_id'       => $post_id,
							'invoice_title' => $invoice_title
						),
					),
				)
			);

			$data = apply_filters( 'geodir_pricing_wpi_insert_invoice_data', $data, $task, $post_id, $package_id, $post_data );

			$invoice = wpinv_insert_invoice( $data, true );

			if ( ! is_wp_error( $invoice ) && ! empty( $invoice->ID ) ) {
				$note = sprintf(
					/* translators: %1$s is listing title, %2$s is listing post type. */
					esc_html__( 'Invoice for the claim on: %1$s (%2$s)', 'geodir_pricing' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( get_edit_post_link( $post_id ) ),
						esc_html( get_the_title( $post_id ) )
					),
					esc_html( geodir_post_type_singular_name( get_post_type( $post_id ), true ) )
				);
				$invoice->add_note( $note );

				$data = array(
					'post_id' => $post_id,
					'package_id' => $package_id,
					'product_id' => $item->ID,
					'invoice_id' => $invoice->ID,
					'task' => $task,
					'cart' => 'invoicing',
					'status' => 'wpi-pending',
					'meta' => maybe_serialize( array( 'task' => $task ) ),
					'date' => date_i18n( 'Y-m-d H:i:s' )
				);

				$data = apply_filters( 'geodir_pricing_wpi_post_package_data', $data, $post_id, $package_id, $post_data );

				$payment_id = GeoDir_Pricing_Post_Package::save( $data );

				// Send email notification.
				wpinv_user_invoice_notification( $invoice->ID );

				return $payment_id;
			}
		} catch ( Exception $e ) {
			geodir_error_log( $e->getMessage(), 'Pricing -> WPI' );
		}

		return false;
	}

	/**
	 * Get the checkout url (without adding to cart)
	 *
	 * @param string $payment_id
	 *
	 * @return mixed
	 */
	public static function get_checkout_url($payment_id = ''){
		$url = '';
		$payment = GeoDir_Pricing_Post_Package::get_item($payment_id);
		if(!empty($payment->invoice_id)){
			$invoice = wpinv_get_invoice( $payment->invoice_id );
			$url = $invoice->get_checkout_payment_url();
		}else{
			$url = wpinv_get_checkout_uri();
		}


		return $url;// cart specific
	}

	public static function claim_submit_success_message( $message, $claim, $post_id ) {
		if ( ! empty( $claim->payment_id ) && ( $payment = GeoDir_Pricing_Post_Package::get_item( $claim->payment_id ) ) ) {
			if ( ! empty( $payment->invoice_id ) ) {
				$invoice = wpinv_get_invoice( $payment->invoice_id );

				if ( $invoice->is_free() ) {
					$invoice->set( 'transaction_id', time() );
					$invoice->set( 'gateway', 'manual' );
					$invoice->set( 'status', 'publish' );
					$invoice->save();

					if ( geodir_get_option( 'claim_auto_approve_on_payment', 1 ) ) {
						$message = wp_sprintf( __( 'Your request to claim the listing has been approved. View the listing %shere%s.', 'geodir_pricing' ), '<a href="' . get_permalink( $post_id ) . '">', '</a>' );
					}
				} elseif ( $invoice->needs_payment() ) {
					$nonce   = wp_create_nonce('getpaid_ajax_form');
					$id      = $invoice->ID;
					$message = wp_sprintf( __( 'Your claim requires a payment to complete.  %sCheckout%s', 'geodir_pricing' ), '<a href="' .  $invoice->get_checkout_payment_url() . '" class="gd-noti-button getpaid-payment-button" data-invoice="' . $id .'" data-nonce="' . $nonce .'" target="_top"><i class="fas fa-shopping-cart"></i> ', '</a>' );
				}
			}
		}

		return $message;
	}

	/**
	 * Set hook to handle restore post revisions on IPN requests.
	 *
	 * @since 2.5.0.13
	 */
	public static function wpinv_verify_payment_ipn() {
		if ( ! get_current_user_id() ) {
			add_action( 'wp_restore_post_revision', array( __CLASS__, 'restore_post_revision' ), 9, 2 );
		}
	}

	/**
	 * Set post author to handle restore post revisions on IPN requests.
	 *
	 * @since 2.5.0.13
	 *
	 * @param int $post_id Post ID.
	 * @param int $revision_id Post revision ID.
	 */
	public static function restore_post_revision( $post_id, $revision_id ) {
		global $geodir_post_author;

		if ( geodir_is_gd_post_type( get_post_type( $post_id ) ) ) {
			$geodir_post_author = geodir_get_listing_author( $revision_id );
		}
	}

	/**
	 * Link this invoice to the associated listing.
	 */
	public static function link_to_listing( $invoice_id ) {
		// Try fetching an associated listing for the invoice...
		$associated_listing = GeoDir_Pricing_Post_Package::get_items(
			array( 
				'invoice_id' => $invoice_id, 
				'order_by'   => 'id DESC'
			)
		);

		// ... if non exists, abort.
		if ( empty( $associated_listing ) ) {
			return;
		}

		// ... else, take the first one (wpdb->get_results returns an array of results)...
		$associated_listing = $associated_listing[0];

		// Does the post exist?
		$listing = get_post( $associated_listing->post_id );
		if ( empty( $listing ) || empty( $listing->ID ) ) {
			return;
		}

		// Prepare variables
		$title  = esc_html( get_the_title( $listing ) );
		$link   = esc_url( get_the_permalink( $listing ) );
		$label  = __( 'Listing', 'geodir_pricing' );
		$cpt    = esc_html( geodir_post_type_singular_name( get_post_type( $listing ), true ) );

		// If the listing is published, link to it.
		if ( in_array( get_post_status( $listing ), geodir_get_publish_statuses( array( 'post_type' => get_post_type( $listing ) ) ) ) ) {
			$title = "<a href='$link'>$title</a>";
		}

		// Finally, we append the cpt to the title.
		if ( ! empty( $cpt ) ) {
			$label  = wp_sprintf( __( '%s Listing', 'geodir_pricing' ), $cpt );
		}

		echo "<tr class='wpi-invoice-package-listing'><th>$label</th><td>$title</td></tr>";

	}

	/*
	 * Handle active subscription on upgrade of listing to another package.
	 *
	 * @param object $post_package Post package object.
	 * @param int    $revision_id Post revision id.
	 */
	public static function post_package_payment_completed( $post_package, $revision_id = 0 ) {
		global $wpdb;

		if ( ! empty( $post_package ) ) {
			$results = $wpdb->get_col( "SELECT `invoice_id` FROM `" . GEODIR_PRICING_POST_PACKAGES_TABLE . "` WHERE post_id = " . (int) $post_package->post_id . " AND `cart` = 'invoicing' AND `invoice_id` > 0 AND `invoice_id` != " . (int) $post_package->invoice_id. " AND `package_id` != " . (int) $post_package->package_id . " ORDER BY `id` DESC" );

			if ( ! empty( $results ) ) {
				foreach ( $results as $invoice_id ) {
					$invoice = wpinv_get_invoice( $invoice_id );

					if ( ! empty( $invoice ) && ! empty( $invoice->ID ) && $invoice->is_recurring() ) {
						// Cancel other active subscription.
						$wpdb->query( "UPDATE `" . $wpdb->prefix . "wpinv_subscriptions` SET status = 'cancelled' WHERE `parent_payment_id` = " . (int) $invoice->ID . " AND status NOT IN ( 'completed', 'expired', 'cancelled', 'failing' )" );
					}
				}
			}
		}
	}

	public static function claim_pending_message( $message, $payment, $claim, $post_id ) {
		if ( empty( $claim->payment_id ) || empty( $payment ) ) {
			return $message;
		}

		$invoice = new WPInv_Invoice( $payment->invoice_id );
		if ( ! $invoice->exists() ) {
			return $message;
		}

		if ( $invoice->has_status( 'pending' ) || $invoice->has_status( 'wpi-pending' ) ) {
			$message = wp_sprintf( __( 'It looks like you already have a claim request for this listing before. Your claim requires a payment to complete.  %sCheckout%s', 'geodir_pricing' ), '<a href="' . $invoice->get_checkout_payment_url() . '" class="gd-noti-button" target="_top"><i class="fas fa-shopping-cart"></i> ', '</a>' );
		}

		return $message;
	}
}