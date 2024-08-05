<?php
/**
 * Pricing Manager & Invoicing integration class.
 *
 * Takes advantage of GetPaid whenever it is available.
 *
 * @since 2.6.0.1
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Cart_GetPaid class.
 */
class GeoDir_Pricing_Cart_GetPaid extends GeoDir_Pricing_Cart {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Display listing details on invoices.
		add_action( 'getpaid_before_invoice_line_item_actions', array( $this, 'display_invoice_item_listing' ), 10, 2 );
		add_action( 'getpaid_email_line_item_name', array( $this, 'display_invoice_item_listing' ), 10, 2 );
		add_action( 'getpaid_payment_form_cart_item_name', array( $this, 'display_invoice_item_listing' ), 10, 2 );

		// Display listing details on invoice and subscriptions meta.
		add_filter( 'getpaid_invoice_meta_data', array( $this, 'filter_invoice_meta_data' ), 10, 2 );
		add_filter( 'getpaid_invoice_email_merge_tags', array( $this, 'filter_invoice_merge_tags' ), 10, 2 );
		add_filter( 'getpaid_single_subscription_details_fields', array( $this, 'filter_subscription_details' ), 10, 3 );
		add_action( 'getpaid_render_single_subscription_column_listing', array( $this, 'display_subscription_listing' ) );

		// Sync items and packages.
		add_action( 'geodir_pricing_save_package', array( $this, 'on_save_package' ) );
		add_action( 'geodir_pricing_deleted_package', array( $this, 'on_delete_package' ) ) ;
		add_action( 'geodir_pricing_post_package_payment_completed', array( $this, 'post_package_payment_completed' ), 9, 2 );

		// Register the packages item type.
		add_filter( 'wpinv_get_item_types', array( $this, 'register_package_item_type' ) );

		// Handle invoice events
		add_action( 'getpaid_invoice_status_publish', array( $this, 'maybe_publish_invoice_listings' ), 10, 2 );
		add_action( 'getpaid_invoice_status_changed', array( $this, 'maybe_unpublish_invoice_listings' ), 10, 3 );

		// Handle subscription events
		add_action( 'getpaid_subscription_trialling', array( $this, 'maybe_publish_subscription_listings' ), 10, 2 );
		add_action( 'getpaid_subscription_active',    array( $this, 'maybe_publish_subscription_listings' ), 10, 2 );
		add_action( 'getpaid_subscription_renewed',   array( $this, 'handle_subscription_renewed' ) );
		add_action( 'getpaid_subscription_cancelled', array( $this, 'handle_subscription_cancelled' ), 10, 2 );
		add_action( 'getpaid_subscription_expired',   array( $this, 'handle_subscription_expired' ), 10, 2 );
		add_action( 'getpaid_after_create_subscription_renewal_invoice', array( $this, 'after_renewal_invoice_created' ), 10, 2 );

		// Events Tickets
		add_filter( 'geodir_tickets_pre_widget_output',   array( $this, 'tickets_pre_widget_output' ), 10, 5 );

		// Admin functionalities.
		if ( is_admin() ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-geodir-pricing-cart-getpaid-admin.php';
			new GeoDir_Pricing_Cart_GetPaid_Admin( $this );
		}

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

	/**
	 * Retrieves a listing id from an invoice item.
	 *
	 * @param GetPaid_Form_Item $item
	 * @return int|false Listing id or false on failure.
	 */
	public function get_invoice_item_listing( $item ) {

		// Ensure this is a package.
		if ( $item->get_type() != 'package'  ) {
			return false;
		}

		$meta = $item->get_item_meta();

		if ( ! empty( $meta ) && isset( $meta['post_id'] ) ) {
			return absint( $meta['post_id'] );
		}

		return false;
	}

	/**
	 * Retrieves the invoice item description.
	 *
	 * @param string $post_type
	 * @param string $listing_title
	 */
	public function get_invoice_item_listing_info( $post_type, $listing_title ) {
		return wp_sprintf(
			/* translators: $1: is the post type, $2: is the listing title */
			_x( '%1$s: %2$s', 'Invoice item description. (e.g.: Listing: My First Listing)', 'geodir_pricing' ),
			sanitize_text_field( $post_type ),
			wp_kses_post( $listing_title )
		);
	}

	/**
	 * Display listing info below each invoice item.
	 *
	 * @param GetPaid_Form_Item $item
	 * @param WPInv_Invoice|GetPaid_Payment_Form $invoice
	 */
	public function display_invoice_item_listing( $item, $invoice ) {
		global $aui_bs5;

		// Prepare invoice.
		if ( is_a( $invoice, 'GetPaid_Payment_Form' ) ) {

			if ( empty( $invoice->invoice ) || ! $invoice->invoice->exists() ) {
				return;
			}

			$invoice = $invoice->invoice;
		}

		// Do we have a listing?
		$items = $this->get_invoice_listing_packages( $invoice->get_id() );
		if ( empty( $items ) ) {
			return;
		}

		// Update each listing's package.
		foreach ( $items as $listing_package ) {

			if ( (int) $listing_package->product_id !== $item->get_id() ) {
				continue;
			}

			$listing_id = $listing_package->post_id;

			// For revisions, use the original post id.
			if ( $post_id = wp_is_post_revision( $listing_package->post_id ) ) {
				$listing_id = $post_id;
			}

			// Retrieve the listing's title.
			$title = sanitize_text_field( get_the_title( $listing_id ) );

			if ( empty( $title ) ) {
				continue;
			}

			// Maybe link to the actual listing.
			if ( in_array( get_post_status( $listing_id ), geodir_get_publish_statuses( array( 'post_type' => get_post_type( $listing_id ) ) ) ) ) {
				$url   = esc_url( get_permalink( $listing_id ) );
				$title = "<a href='$url'>$title</a>";
			}

			// Get the custom description.
			$description = $this->get_invoice_item_listing_info( $item->get_custom_singular_name(), $title );
			$description = wp_kses_post( $description );

			// Emails use a slightly different markup.
			if ( doing_action( 'getpaid_email_line_item_name' ) ) {
				echo "<p class='small'>$description</p>";
				return;
			}

			echo "<small class='form-text text-muted m-0 d-block " . ( $aui_bs5 ? 'pe-2' : 'pr-2' ) . "'>$description</small>";
			return;
		}

	}

	/**
	 * Display listing info on the invoice meta.
	 *
	 * @param array $meta
	 * @param WPInv_Invoice $invoice
	 */
	public function filter_invoice_meta_data( $meta, $invoice ) {

		return array_merge(
			$this->get_invoice_listing_meta( $invoice->get_id() ),
			$meta
		);

	}

	/**
	 * Fetches invoice listing meta.
	 *
	 * @param int $invoice_id
	 * @return array
	 */
	protected function get_invoice_listing_meta( $invoice_id ) {

		$listings  = array();
		$post_type = '';

		$items = $this->get_invoice_listing_packages( $invoice_id );
		if ( empty( $items ) ) {
			return array();
		}

		// Update each listing's package.
		foreach ( $items as $listing_package ) {
			$listing_id = $listing_package->post_id;

			// For revisions, use the original post id.
			if ( $post_id = wp_is_post_revision( $listing_package->post_id ) ) {
				$listing_id = $post_id;
			}

			$post_type  = geodir_get_post_type_singular_label( get_post_type( $listing_id ) );

			// Retrieve the listing's title.
			$title = sanitize_text_field( get_the_title( $listing_id ) );

			// Maybe link to the actual listing.
			if ( in_array( get_post_status( $listing_id ), geodir_get_publish_statuses( array( 'post_type' => get_post_type( $listing_id ) ) ) ) ) {
				$url   = esc_url( get_permalink( $listing_id ) );
				$title = "<a href='$url'>$title</a>";
			}

			$listings[] = $title;
		}

		// Abort if there are no listings.
		if ( empty( $listings ) ) {
			return array();
		}

		// If more than one listing...
		if ( empty( $post_type ) || 1 < count( $listings ) ) {
			$post_type = __( 'Listings', 'geodir_pricing' );
		}

		return array(
			'listing'   => array(
				'label' => sanitize_text_field( $post_type ),
				'value' => implode( ', ', $listings ),
			)
		);

	}

	/**
	 * Filters subscription details.
	 *
	 * @param array $details
	 * @param WPInv_Subscription $subscription
	 * @param WPInv_Invoice $invoice
	 * @return array
	 */
	public function filter_subscription_details( $details, $subscription, $items_count = 0 ) {

		$listing_info = $this->get_invoice_listing_meta( $subscription->get_parent_invoice_id() );
		if ( count( $listing_info ) ) {

			$details['listing'] = $listing_info['listing']['label'];

			if ( 1 === $items_count && isset( $details['item'] ) ) {
				$details['item'] = __( 'Package', 'geodir_pricing' );
			}
		}

		return $details;
	}

	/**
	 * Displays a subscription's listing.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function display_subscription_listing( $subscription ) {

		$listing_info = $this->get_invoice_listing_meta( $subscription->get_parent_invoice_id() );
		if ( count( $listing_info ) ) {
			echo wp_kses_post( $listing_info['listing']['value'] );
		}

	}

	/**
	 * Filters invoice merge tags.
	 *
	 * @param array $merge_tags
	 * @param WPInv_Invoice $invoice
	 */
	public function filter_invoice_merge_tags( $merge_tags, $invoice ) {

		$items = $this->get_invoice_listing_packages( $invoice->get_id() );
		if ( empty( $items ) ) {
			return $merge_tags;
		}

		// Update each listing's package.
		foreach ( $items as $listing_package ) {
			$listing_id = $listing_package->post_id;

			// For revisions, use the original post id.
			if ( $post_id = wp_is_post_revision( $listing_package->post_id ) ) {
				$listing_id = $post_id;
			}

			$merge_tags['{listing_id}']       = (int) $listing_id;
			$merge_tags['{listing_type}']     = geodir_get_post_type_singular_label( get_post_type( $listing_id ) );
			$merge_tags['{listing_name}']     = sanitize_text_field( get_the_title( $listing_id ) );
			$merge_tags['{listing_url}']      = esc_url( get_permalink( $listing_id ) );
			$merge_tags['{listing_edit_url}'] = esc_url( geodir_edit_post_link( $listing_id ) );

			break;
		}

		return $merge_tags;

	}

	/**
	 * Called every time a package is saved.
	 *
	 * @param int $package_id The package being saved.
	 */
	public function on_save_package( $package_id ) {

		if ( current_user_can( 'manage_options' ) ) {
			$this->sync_item_to_package( $package_id );
		}

	}

	/**
	 * Keeps an item in sync with a package.
	 *
	 * @param int $package_id The package to merge.
	 * @return false|WPInv_Item
	 */
	public function sync_item_to_package( $package_id ) {

		// Retrieve the page.
		$package = geodir_pricing_get_package( (int) $package_id );

		// Abort if it does not exist.
		if ( empty( $package ) || empty( $package->post_type ) || ! geodir_is_gd_post_type( $package->post_type ) ) {
			return false;
		}

		// Prepare the associated invoicing item.
		$item = wpinv_get_item_by( 'custom_id', $package_id, 'package' );
		$item = new WPInv_Item( $item );

		// Set the item props.
		$item->set_type( 'package' );
		$item->set_name( $package->name );
		$item->set_description( $package->description );
		$item->set_custom_id( $package_id );
		$item->set_price( $package->amount );
		$item->set_status( $package->status == 1 ? 'publish' : 'pending' );
		$item->set_custom_name( geodir_get_post_type_plural_label( $package->post_type ) );
		$item->set_custom_singular_name( geodir_get_post_type_singular_label( $package->post_type ) );
		$item->set_is_editable( false );

		// Custom prices.
		$name_your_price = geodir_pricing_get_meta( $package_id, 'package_name_your_price', true );
		$item->set_is_dynamic_pricing( ! empty( $name_your_price ) );

		// Handle recurring props.
		$trial_interval = absint( $package->trial_interval );
		$item->set_is_recurring( ! empty( $package->recurring ) );
		$item->set_recurring_period( $package->time_unit );
		$item->set_recurring_interval( $package->time_interval );
		$item->set_recurring_limit( $package->recurring_limit );
		$item->set_is_free_trial( $trial_interval > 0 );
		$item->set_trial_period( $package->trial_unit );
		$item->set_trial_interval( $trial_interval );

		// Save the item.
		$item->save();

		// Abort if it was not successful.
		if ( ! $item->exists() ) {
			return false;
		}

		// Fires after saving the item.
		do_action( 'geodir_pricing_manager_after_sync_package_to_getpaid_item', $item, $package );

		// Cache the item id to the package.
		geodir_pricing_update_meta( $package_id, 'invoicing_product_id', $item->get_id() );

		return $item;
	}

	/**
	 * Called every time a package is deleted.
	 *
	 * @param int $package_id The package being deleted.
	 */
	public function on_delete_package( $package_id ) {

		$item = wpinv_get_item_by( 'custom_id', $package_id, 'package' );
		$item = new WPInv_Item( $item );
		$item->delete();

	}

	/**
	 * Registers the package item type.
	 *
	 * @param array $item_types
	 */
	public function register_package_item_type( $item_types ) {

		return array_merge(
			$item_types,
			array(
				'package' => __( 'Package', 'geodir_pricing' ),
			)
		);

	}

	/**
	 * Retrieves the listings used associated with an invoice.
	 *
	 * @param int $invoice_id
	 * @return array An array of rows in wp_geodir_post_packages table
	 */
	public function get_invoice_listing_packages( $invoice_id ) {
		$items = GeoDir_Pricing_Post_Package::get_items( compact( 'invoice_id' ) );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Retrieves the listings used associated with a subscription.
	 *
	 * @param int $invoice_id
	 * @param int $product_id
	 * @return array An array of one row from wp_geodir_post_packages table
	 */
	public function get_subscription_listing_packages( $invoice_id, $product_id ) {

		$items = GeoDir_Pricing_Post_Package::get_items(
			array(
				'invoice_id' => $invoice_id,
				'product_id' => $product_id,
			)
		);

		$items = is_array( $items ) ? $items : array();

		return $items;
	}

	/**
	 * Publishes listings after an invoice is paid for.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function maybe_publish_invoice_listings( $invoice, $status_transition = array() ) {
		global $geodir_publish_invoice_listings;

		// Abort if the invoice does not exist.
		if ( ! $invoice->get_id() ) {
			return;
		}

		// Already executed!
		if ( ! empty( $status_transition['form'] ) && $status_transition['form'] == 'publish' ) {
			return;
		}

		if ( empty( $geodir_publish_invoice_listings ) ) {
			$geodir_publish_invoice_listings = array();
		}

		if ( ! empty( $geodir_publish_invoice_listings[ $invoice->get_id() ] ) ) {
			return;
		}

		$geodir_publish_invoice_listings[ $invoice->get_id() ] = true;

		$items = $this->get_invoice_listing_packages( $invoice->get_id() );
		if ( empty( $items ) ) {
			return;
		}

		// Update each listing's package.
		foreach ( $items as $listing_package ) {
			$revision_id = '';
			$data        = array(
				'id'     => $listing_package->id,
				'status' => 'publish',
				'date'   => current_time( 'mysql' ),
			);

			// For revisions, use the original post id.
			if ( $post_id = wp_is_post_revision( $listing_package->post_id ) ) {
				$revision_id                = $listing_package->post_id;
				$data['post_id']            = $post_id;
				$listing_package->post_id   = $post_id;
			}

			// Update the row.
			GeoDir_Pricing_Post_Package::save( $data );

			do_action( 'geodir_pricing_post_package_payment_completed', $listing_package, $revision_id );
		}

	}

	/**
	 * Activates listings after a subscription is activated.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function maybe_publish_subscription_listings( $subscription, $status_transition = array() ) {
		$parent_invoice_id = (int) $subscription->get_parent_invoice_id();

		if ( ! empty( $parent_invoice_id ) ) {
			$this->remove_cancel_at_period_end( $parent_invoice_id );
		}
	}

	/**
	 * Unpublishes invoice listings whenever payment fails.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param string $from
	 * @param string $to
	 */
	public function maybe_unpublish_invoice_listings( $invoice, $from, $to ) {

		// Abort if the invoice does not exist.
		if ( ! $invoice->get_id() ) {
			return;
		}

		// Abort if this is a renewal invoice or the previous status was not publish.
		if ( $invoice->is_renewal() || 'publish' != $from || 'publish' == $to ) {
			return;
		}

		$action = $invoice->is_refunded() ? 'refunded' : 'cancelled';

		$items = $this->get_invoice_listing_packages( $invoice->get_id() );
		if ( empty( $items ) ) {
			return;
		}

		// Update each listing's package.
		foreach ( $items as $listing_package ) {
			$data = array(
				'id'     => $listing_package->id,
				'status' => 'wpi-' . $action,
			);

			GeoDir_Pricing_Post_Package::save( $data );

			do_action( "geodir_pricing_post_package_payment_$action", $listing_package );
		}

	}

	/**
	 * This method is called whenever a subscription is renewed.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function handle_subscription_renewed( $subscription ) {
		// Handle subscription renew.
	}

	/**
	 * This method is called whenever a subscription is cancelled.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function handle_subscription_cancelled( $subscription, $transition = array() ) {
		if ( ! empty( $transition['from'] ) && $transition['from'] == 'trialling' ) {
			$this->handle_subscription_expired( $subscription, $transition );
			return;
		}

		$parent_invoice_id = (int) $subscription->get_parent_invoice_id();
		$product_id = (int) $subscription->get_product_id();

		if ( empty( $parent_invoice_id ) || empty( $product_id ) ) {
			return;
		}

		// We only want to process the recurring listings.
		$items = $this->get_subscription_listing_packages( $parent_invoice_id, $product_id );

		if ( empty( $items ) ) {
			return;
		}

		// Downgrade each listing.
		foreach ( $items as $post_package ) {
			if ( empty( $post_package->post_id ) ) {
				continue;
			}

			$package_id = (int) geodir_get_post_meta( (int) $post_package->post_id, 'package_id', true );

			if ( ! ( ! empty( $package_id ) && ! empty( $post_package->package_id ) && $package_id == (int) $post_package->package_id ) ) {
				// Don't downgrade/expire if listing package is changed.
				continue;
			}

			update_post_meta( (int) $post_package->post_id, '_gdpm_cancel_at_period_end', true );

			geodir_save_post_meta( (int) $post_package->post_id, 'expire_date', $subscription->get_expiration() );
		}
	}

	/**
	 * This method is called whenever a subscription expires.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function handle_subscription_expired( $subscription, $transition = array() ) {
		$parent_invoice_id = (int) $subscription->get_parent_invoice_id();
		$product_id = (int) $subscription->get_product_id();

		if ( empty( $parent_invoice_id ) || empty( $product_id ) ) {
			return;
		}

		// We only want to process the recurring listings.
		$items = $this->get_subscription_listing_packages( $parent_invoice_id, $product_id );

		if ( empty( $items ) ) {
			return;
		}

		// Downgrade each listing.
		foreach ( $items as $post_package ) {
			if ( empty( $post_package->post_id ) ) {
				continue;
			}

			$package_id = (int) geodir_get_post_meta( (int) $post_package->post_id, 'package_id', true );

			if ( ! ( ! empty( $package_id ) && ! empty( $post_package->package_id ) && $package_id == (int) $post_package->package_id ) ) {
				// Don't downgrade/expire if listing package is changed.
				continue;
			}

			delete_post_meta( $post_package->post_id, '_gdpm_cancel_at_period_end' );

			// Retrieve the associated listing and abort if it does not exist.
			$gd_post = geodir_get_post_info( $post_package->post_id );

			if ( empty( $gd_post ) ) {
				continue;
			}

			// Retrieve the associated package.
			$package = geodir_pricing_get_package( $post_package->package_id );

			// Either downgrade or expire the listing.
			if ( ! empty( $package ) && ! empty( $package->downgrade_pkg ) && ( $downgrade_to = geodir_pricing_get_package( (int) $package->downgrade_pkg ) ) ) {
				geodir_pricing_post_downgrade( $gd_post, $downgrade_to, $package );
			} else {
				geodir_pricing_post_expire( $gd_post );
			}
		}
	}

	/**
	 * Checks if we're allowed to generate an invoice for a given listing.
	 *
	 * @param  int $listing_id
	 * @return bool
	 */
	public function allow_invoice_for_listing( $listing_id ) {

		$allow_invoice = in_array( get_post_status( $listing_id ), array( 'draft', 'gd-expired' ) );

		if ( $allow_invoice ) {
			$gd_post = geodir_get_post_info( $listing_id );

			// Ensure it has a package.
			if ( empty( $gd_post->package_id ) ) {
				$allow_invoice = false;
			}

		}

		return apply_filters( 'geodir_wpi_allow_invoice_for_listing', $allow_invoice, $listing_id );
	}

	/**
	 * Creates a new invoice for a package (via admin action).
	 *
	 * @param int $post_id
	 */
	public function create_invoice( $post_id ) {

		// Abort if no listing.
		if ( empty( $post_id ) || ! $this->allow_invoice_for_listing( $post_id ) ) {

			wp_send_json(
				array(
					'success' => false,
					'msg'     => __( 'Missing or invalid listing.' , 'geodir_pricing' ),
				)
			);

		}

		// Retrieve package and listing info...
		$gd_post      = geodir_get_post_info( $post_id );

		// ... and abort if it has no package.
		if ( empty( $gd_post->package_id ) ) {

			wp_send_json(
				array(
					'success' => false,
					'msg'     => __( 'No package assigned to this listing.' , 'geodir_pricing' ),
				)
			);

		}

		// Retrieve the package item.
		$item = $this->get_package_item( (int) $gd_post->package_id );

		// ... and abort if that fails.
		if ( empty( $item ) ) {

			wp_send_json(
				array(
					'success' => false,
					'msg'     => __( 'No GetPaid item found with the package selected.' , 'geodir_pricing' ),
				)
			);
		}

		// Create the invoice.
		$invoice = $this->save_listing_invoice(
			$post_id,
			$gd_post->package_id,
			$gd_post->post_author,
			$item,
			wp_sprintf( __( 'Payment For: %s', 'geodir_pricing' ), $gd_post->post_title )
		);

		if ( is_wp_error( $invoice ) ) {

			wp_send_json(
				array(
					'success' => false,
					'msg'     => $invoice->get_error_message(),
				)
			);

		}

		// Add relationship note.
		$this->add_invoice_listing_note(
			$invoice,
			/* translators: %1$s is listing title, %2$s is listing post type. */
			esc_html__( 'Invoice for: %1$s (%2$s)', 'geodir_pricing' ),
			$post_id
		);

		// Save the post package.
		$save_data = array(
			'post_id'    => $post_id,
			'package_id' => $gd_post->package_id,
			'product_id' => $item->get_id(),
			'invoice_id' => $invoice->get_id(),
			'task'       => 'renew',
			'cart'       => 'invoicing',
			'status'     => $invoice->get_status(),
			'meta'       => maybe_serialize( array( 'task' => 'renew', 'user_id' => (int) get_current_user_id() ) ),
			'date'       => current_time( 'mysql' ),
		);
		GeoDir_Pricing_Post_Package::save( $save_data );

		wp_send_json(
			array(
				'success' => true,
				'link'    => '<a target="_blank" href="' . get_edit_post_link( $invoice->get_id() ) . '">' . wp_sprintf( __( 'View Invoice #%s' , 'geodir_pricing' ), $invoice->get_number() ) . '</a>',
			)
		);

	}

	/**
	 * Creates a new invoice for a package (via frontend).
	 *
	 * @param array $post_data
	 * @return WP_Error
	 */
	public function ajax_post_saved( $post_data ) {

		// First save the submitted data.
		$result = GeoDir_Post_Data::auto_save_post( $post_data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$has_invoice    = false;
		$parent_post_id = ! empty( $post_data['post_parent'] ) ? $post_data['post_parent'] : $post_data['ID'];

		// New listing.
		if ( geodir_pricing_is_new( $post_data ) ) {
			$task = 'new';

			// Check if its a logged out user and if we have details to register the user.
			$post_data = GeoDir_Post_Data::check_logged_out_author( $post_data );

			// If its new and an auto-draft then we save it as pending.
			$post_status = get_post_status( $parent_post_id );
			if ( empty( $post_data['post_parent'] ) && $post_status=='auto-draft' ) {
				wp_update_post( array('ID'=> $post_data['ID'],'post_status'=>'pending'));
			}

			// If its new and a revision then just save it.
			else if ( ! empty( $post_data['post_parent'] ) ) {
				wp_restore_post_revision( $post_data['ID'] );
				$post_data['ID'] = $parent_post_id;
			}

			$has_invoice = self::has_invoice( $post_data['ID'], 'new' );

		// Upgrade listing package.
		} else if ( geodir_pricing_is_upgrade( $post_data ) ) {
			$task        = 'upgrade';
			$has_invoice = self::has_invoice( $post_data['ID'] );

		// Renewal listing.
		} else if ( geodir_pricing_is_renew( $post_data ) ) {
			$task = 'renew';
			$has_invoice = self::has_invoice( $post_data['ID'] );

		// Updating an existing listing.
		} else {
			wp_restore_post_revision( $post_data['ID'] );
			return;
		}

		// Abort if we have no package information.
		if ( empty( $post_data['package_id'] ) ) {
			return;
		}

		// Retrieve the package item.
		$item = $this->get_package_item( (int) $post_data['package_id'] );

		if ( empty( $item ) ) {
			return false;
		}

		$post_id = $post_data['ID'];
		$user_id = !empty($post_data['post_author']) ? (int)$post_data['post_author'] : get_current_user_id();

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

		if ( ! empty( $has_invoice->invoice_id ) && $has_invoice->cart == 'invoicing' ) {
			$invoice = new WPInv_Invoice( $has_invoice->invoice_id );
		} else {

			// Create the invoice.
			$invoice = $this->save_listing_invoice(
				$post_id,
				(int) $post_data['package_id'],
				$user_id,
				$item,
				$invoice_title
			);

			if ( is_wp_error( $invoice ) ) {
				geodir_error_log( $invoice->get_error_message(), 'Pricing -> WPI' );
				return;
			}

		}

		if ( ! $invoice->exists() ) {
			return;
		}

		// Add relationship note.
		$this->add_invoice_listing_note(
			$invoice,
			/* translators: %1$s is listing title, %2$s is listing post type. */
			esc_html__( 'Invoice for %1$s (%2$s)', 'geodir_pricing' ),
			$post_id
		);

		try {
			$meta = array( 'task' => $task, 'user_id' => (int) get_current_user_id(), 'free_trial' => (int) ( $item->has_free_trial() && $invoice->is_recurring() && $invoice->is_parent() ) );

			// Save the listing invoice relationship.
			$data = array(
				'post_id'    => $post_id,
				'package_id' => $post_data['package_id'],
				'product_id' => $item->get_id(),
				'invoice_id' => $invoice->get_id(),
				'task'       => $task,
				'cart'       => 'invoicing',
				'status'     => $invoice->get_status(),
				'meta'       => maybe_serialize( $meta ),
				'date'       => current_time( 'mysql' )
			);

			// If we have a package id then we just update it.
			if ( ! empty( $has_invoice->id ) ) {
				$data['id'] = $has_invoice->id;
			}

			$data = apply_filters( 'geodir_pricing_getpaid_post_package_data', $data, $post_id, $post_data['package_id'], $post_data );

			GeoDir_Pricing_Post_Package::save( $data );

			if ( $invoice->is_free() ) {
				$invoice->mark_paid();
				$post_data['ID']          = $parent_post_id;
				$post_data['post_status'] = geodir_pricing_paid_post_status( $post_data['package_id'], $parent_post_id, $task );
				return GeoDir_Post_Data::ajax_save_post_message( $post_data );

			}

			// Force a specific payment form.
			$payment_form = geodir_pricing_get_meta( $post_data['package_id'], 'package_payment_form', true );
			$invoice->update_meta_data( 'force_payment_form', $payment_form );

			if ( $invoice->needs_payment() ) {
				return self::ajax_save_post_message( $task, $post_data, $invoice->get_checkout_payment_url() );
			}

		} catch ( Exception $e ) {
			geodir_error_log( $e->getMessage(), 'Pricing -> WPI' );
		}

		return;
	}

	/**
	 * Filters the success message to show a payment link.
	 *
	 * @param string $message
	 * @param GeoDir_Claim_Post $claim
	 * @param int $post_id
	 *
	 * @return string
	 */
	public static function claim_submit_success_message( $message, $claim, $post_id ) {

		// Abort if no payment info exists.
		if ( empty( $claim->payment_id ) ) {
			return $message;
		}

		$payment = GeoDir_Pricing_Post_Package::get_item( $claim->payment_id );

		if ( empty( $payment ) ) {
			return $message;
		}

		$invoice = new WPInv_Invoice( $payment->invoice_id );
		if ( ! $invoice->exists() ) {
			return $message;
		}

		if ( $invoice->is_free() ) {
			$invoice->mark_paid();

			if ( geodir_get_option( 'claim_auto_approve_on_payment', 1 ) ) {
				if ( geodir_get_option( 'claim_auto_approve' ) && $invoice->is_recurring() ) {
					return __( 'A verification link has been sent to your email address, please click the link in the email to verify your listing claim.', 'geodir_pricing' );
				}

				return wp_sprintf(
					__( 'Your request to claim the listing has been approved. View the listing %shere%s.', 'geodir_pricing' ),
					'<a href="' . get_permalink( $post_id ) . '">',
					'</a>'
				);
			}

			return $message;
		}

		return wp_sprintf(
			__( 'Your claim requires a payment to complete.  %sCheckout%s', 'geodir_pricing' ),
			'<a href="' .  $invoice->get_checkout_payment_url() . '" class="gd-noti-button" target="_top"><i class="fas fa-shopping-cart"></i> ',
			'</a>'
		);
	}

	/**
	 * Create a invoice for claiming a listing.
	 *
	 * @param int $post_id
	 * @param int $package_id
	 * @param int $user_id
	 * @param int $payment_id
	 *
	 * @return mixed
	 */
	public function create_claim_invoice( $post_id, $package_id, $user_id, $payment_id = 0 ) {

		// Retrieve the package item.
		$item = $this->get_package_item( $package_id );

		if ( empty( $item ) ) {
			return false;
		}

		// Create the invoice.
		$invoice = $this->save_listing_invoice(
			$post_id,
			$package_id,
			$user_id,
			$item,
			wp_sprintf(  __( 'Claim: %s', 'geodir_pricing' ), get_the_title( $post_id ) )
		);

		if ( is_wp_error( $invoice ) ) {
			geodir_error_log( $invoice->get_error_message(), 'Pricing -> WPI' );
		}

		// Add relationship note.
		$this->add_invoice_listing_note(
			$invoice,
			/* translators: %1$s is listing title, %2$s is listing post type. */
			esc_html__( 'Invoice for the claim on: %1$s (%2$s)', 'geodir_pricing' ),
			$post_id
		);

		try {
			$meta = array( 'task' => 'claim', 'user_id' => (int) get_current_user_id(), 'free_trial' => (int) ( $item->has_free_trial() && $invoice->is_recurring() && $invoice->is_parent() ) );

			// Save the listing invoice relationship.
			$data = array(
				'post_id'    => $post_id,
				'package_id' => $package_id,
				'product_id' => $item->get_id(),
				'invoice_id' => $invoice->get_id(),
				'task'       => 'claim',
				'cart'       => 'invoicing',
				'status'     => 'wpi-pending',
				'meta'       => maybe_serialize( $meta ),
				'date'       => current_time( 'mysql' )
			);

			$post_data = (array) geodir_get_post_info( $post_id );

			$data = apply_filters( 'geodir_pricing_getpaid_post_package_data', $data, $post_id, $package_id, $post_data );

			$payment_id = GeoDir_Pricing_Post_Package::save( $data );

			return $payment_id;

		} catch ( Exception $e ) {
			geodir_error_log( $e->getMessage(), 'Pricing -> WPI' );
		}

		return false;
	}

	/**
	 * Retrieves a package WPI item and tries to create it if one does not exist.
	 *
	 * @param int $listing_id
	 * @param int $customer_id
	 * @param WPInv_Item $item
	 * @param string $item_description
	 * @param int $package_id
	 *
	 * @return WP_Error|WPInv_Invoice
	 */
	public function save_listing_invoice( $listing_id, $package_id, $customer_id, $item, $item_description ) {
		// Create the invoice.
		$invoice = new WPInv_Invoice();
		$invoice->set_status( 'wpi-pending' );
		$invoice->set_customer_id( $customer_id );
		$invoice->created_via( 'geodirectory' );

		$data = array(
			'item_id'           => $item->get_id(),
			'quantity'          => 1,
			'meta'              => array(
				'post_id'       => $listing_id,
				'invoice_title' => $item_description
			),
		);

		$data = apply_filters( 'geodir_pricing_getpaid_invoice_item_data', $data, $listing_id, $package_id, $item, $customer_id );

		$possible_error = $invoice->add_item( $data );

		if ( is_wp_error( $possible_error ) ) {
			wp_send_json(
				array(
					'success' => false,
					'msg'     => $possible_error->get_error_message(),
				)
			);
		}

		// Try filling the default address.
		$address = wpinv_get_user_address( $customer_id );
		$skip    = array();

		foreach ( $address as $key => $value ) {
			$method = "set_$key";

			if ( method_exists( $invoice, $method ) && $value != '' ) {
				$skip[] = $key;
				$invoice->$method( wpinv_clean( $value ) );
			}
		}

		// Use current country as the invoice's country.
		$country = getpaid_get_ip_country();
		$country = empty( $country ) ? wpinv_get_default_country() : $country;
		$invoice->set_country( $country );

		// Copy some address details from the listing.
		$gd_post = geodir_get_post_info( $listing_id );

		if ( ! empty( $gd_post->city ) && ! in_array( 'city', $skip ) ) {
			$invoice->set_city( wpinv_clean( $gd_post->city ) );
		}

		if ( ! empty( $gd_post->zip ) && ! in_array( 'zip', $skip ) ) {
			$invoice->set_zip( wpinv_clean( $gd_post->zip ) );
		}

		if ( ! empty( $gd_post->street ) && ! in_array( 'address', $skip ) ) {
			$invoice->set_address( wpinv_clean( $gd_post->street ) );
		}

		$invoice->recalculate_total();
		$invoice->save();

		// Abort if we were unable to save the invoice.
		if ( ! $invoice->exists() ) {
			wp_send_json(
				array(
					'success' => false,
					'msg'     => __( 'There was a problem creating your invoice. Please refresh the page and try again.' , 'geodir_pricing' ),
				)
			);
		}

		return $invoice;
	}

	/**
	 * Retrieves a package WPI item and tries to create it if one does not exist.
	 *
	 * @param int $package_id
	 *
	 * @return false|WPInv_Item
	 */
	public function get_package_item( $package_id ) {
		$item = wpinv_get_item_by( 'custom_id', (int) $package_id, 'package' );

		if ( ! empty( $item ) ) {
			return $item;
		}

		return $this->sync_item_to_package( $package_id );
	}

	/**
	 * Adds a note to an invoice with details about the listing.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param string $note
	 * @param int $listing_id
	 */
	public function add_invoice_listing_note( $invoice, $note, $listing_id ) {
		if ( wp_is_post_revision( (int) $listing_id ) && ( $_listing_id = wp_get_post_parent_id( (int) $listing_id ) ) ) {
			$listing_id = $_listing_id;
		}

		$url       = esc_url( get_edit_post_link( $listing_id ) );
		$title     = esc_html( get_the_title( $listing_id ) );
		$post_type = sanitize_text_field( geodir_post_type_singular_name( get_post_type( $listing_id ), true ) );
		$note      = sprintf( $note, "<a href='$url'>$title</a>", $post_type );

		$invoice->add_note( $note, false, false, true );
	}

	/**
	 * Formats a price.
	 *
	 * @param float $price
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function price( $_price, $args = array() ) {
		$currency = '';
		if ( ! empty( $args['currency'] ) ) {
			$currency = $args['currency'];
		}

		$price = wpinv_price( wpinv_sanitize_amount( $_price ), $currency );

		return apply_filters( 'geodir_pricing_wpi_price', $price, $_price, $args );
	}

	/*
	 * Handle active subscription on upgrade of listing to another package.
	 *
	 * @param object $post_package Post package object.
	 * @param int    $revision_id Post revision id.
	 */
	public function post_package_payment_completed( $post_package, $revision_id = 0 ) {
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

	/*
	 * Handle invoice created for subscription payment.
	 *
	 * @since 2.6.0.4
	 *
	 * @param WPInv_Invoice      $invoice The invoice object.
	 * @param WPInv_Subscription $subscription The subscription object.
	 */
	public function after_renewal_invoice_created( $invoice, $subscription ) {
		// Abort if the invoice does not exist.
		if ( empty( $invoice ) || empty( $subscription ) ) {
			return;
		}

		// We only want to renew the recurring listings.
		$items = $this->get_subscription_listing_packages( $subscription->get_parent_invoice_id(), $subscription->get_product_id() );

		if ( empty( $items ) ) {
			return;
		}

		foreach ( $items as $_post_package ) {
			if ( empty( $_post_package->post_id ) ) {
				continue;
			}

			$data = array(
				'post_id'    => $_post_package->post_id,
				'package_id' => $_post_package->package_id,
				'cart'       => 'invoicing',
				'invoice_id' => $invoice->get_id(),
				'product_id' => $_post_package->product_id,
				'task'       => 'renew',
				'meta'       => maybe_serialize( array( 'task' => 'renew', 'user_id' => (int) get_current_user_id() ) ),
				'date'       => current_time( 'mysql' ),
				'status'     => $invoice->get_status()
			);

			$post_package_id = (int) GeoDir_Pricing_Post_Package::save( $data );

			$post_package = GeoDir_Pricing_Post_Package::get_item( $post_package_id );

			if ( ! empty( $post_package ) ) {
				do_action( 'geodir_pricing_post_package_payment_completed', $post_package, $post_package->post_id );
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

	/**
	 * Link invoice to post.
	 *
	 * @since 2.6.1.2
	 *
	 * @param int $post_id The Post ID.
	 * @param int $invoice_id The Invoice ID.
	 * @param WP_Error|string
	 */
	public function link_post_invoice( $post_id, $invoice_id ) {
		global $wpdb;

		// Abort if invalid post.
		if ( ! ( ! empty( $post_id ) && geodir_is_gd_post_type( get_post_type( $post_id ) ) ) ) {
			throw new Exception( __( 'Invalid post.', 'geodir_pricing' ) );
		}

		$gd_post = geodir_get_post_info( $post_id );

		
		if ( empty( $gd_post->package_id ) ) {
			throw new Exception( __( 'Please assign a package to the post first.', 'geodir_pricing' ) );
		}

		$invoice = new WPInv_Invoice( $invoice_id );

		if ( ! $invoice->exists() ) {
			throw new Exception( __( 'Invoice does not exist. Please enter a valid invoice id or number.', 'geodir_pricing' ) );
		}

		// Retrieve the package item.
		$item = $this->get_package_item( (int) $gd_post->package_id );

		// Retrieve the invoice item.
		$invoice_item = ! empty( $item ) ? $invoice->get_item( (int) $item->get_id() ) : NULL;

		if ( empty( $invoice_item ) ) {
			throw new Exception( __( "Invoice don't have a cart item for the listing's current package.", 'geodir_pricing' ) );
		}

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . GEODIR_PRICING_POST_PACKAGES_TABLE . "` WHERE invoice_id = %d AND product_id = %d LIMIT 1", array( $invoice->get_id(), (int) $item->get_id() ) ) );
		if ( ! empty( $exists ) ) {
			throw new Exception( __( "Invoice is already linked to another post. Please try other invoice id.", 'geodir_pricing' ) );
		}

		/**
		 * Filter to check link invoice or not.
		 * 
		 * @since 2.6.1.2
		 *
		 * @param string $check_error Error. Default empty.
		 * @param int    $post_id Post ID.
		 * @param int    $invoice_id Invoice ID.
		 * @param object $invoice The invoice.
		 * @param object $item The invoice item.
		 */
		$check_error = apply_filters( 'geodir_pricing_gp_check_link_invoice', '', $post_id, $invoice->get_id(), $invoice, $invoice_item );
		if ( ! empty( $check_error ) ) {
			throw new Exception( $check_error );
		}

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . GEODIR_PRICING_POST_PACKAGES_TABLE . "` WHERE post_id = %d LIMIT 1", array( $post_id ) ) );
		$task = $exists ? 'upgrade' : 'new';

		$data = array(
			'post_id' => $post_id,
			'package_id' => (int) $gd_post->package_id,
			'cart' => 'invoicing',
			'invoice_id' => $invoice->get_id(),
			'product_id' => (int) $item->get_id(),
			'task' => $task,
			'meta' => maybe_serialize( array( 'task' => $task ) ),
			'date' => $invoice->get_invoice_date( false ),
			'status' => $invoice->get_status(),
		);

		/**
		 * Filter post package data to save.
		 * 
		 * @since 2.6.1.2
		 *
		 * @param array  $data The post package data.
		 * @param int    $post_id Post ID.
		 * @param int    $invoice_id Invoice ID.
		 * @param object $invoice The invoice.
		 * @param object $item The invoice item.
		 */
		$data = apply_filters( 'geodir_pricing_gp_link_invoice_save_data', $data, $post_id, $invoice->get_id(), $invoice, $invoice_item );

		if ( false === $wpdb->insert( GEODIR_PRICING_POST_PACKAGES_TABLE, $data ) ) {
			throw new Exception( wp_sprintf( __( 'Could not link invoice to the post. Error: %s', 'geodir_pricing' ), $wpdb->last_error ) );
		}
		$post_package_id = (int) $wpdb->insert_id;

		/**
		 * Fires after invoice linked to the product.
		 * 
		 * @since 2.6.1.2
		 *
		 * @param int    $post_package_id The post package id.
		 * @param int    $post_id Post ID.
		 * @param int    $invoice_id Invoice ID.
		 * @param object $invoice The invoice.
		 * @param object $item The invoice item.
		 */
		do_action( 'geodir_pricing_gp_post_invoice_linked', $post_package_id, $post_id, $invoice->get_id(), $invoice, $invoice_item );

		wp_send_json_success( array( 'reload' => true ) );
	}

	/**
	 * Filters the Events Tickets widget output before the original output.
	 *
	 * @since 2.6.3
	 *
	 * @param string $output Widget output.
	 * @param array  $args Widget parameters.
	 * @param object $post The event post object.
	 * @param array  $widget_args Widget arguments.
	 * @param string $content Widget content.
	 * @return string Tickets widget output.
	 */
	public static function tickets_pre_widget_output( $output, $args, $post, $widget_args = array(), $content = '' ) {
		if ( ! empty( $post ) && ! self::allow_tickets( $post ) ) {
			// Don't show buy/sell tickets.
			return '';
		}

		return $output;
	}

	/**
	 * Check buy/sell event tickets feature allowed for the event.
	 *
	 * @since 2.6.3
	 *
	 * @param object $post The event post object.
	 * @return bool If valid then True, else False.
	 */
	public static function allow_tickets( $post ) {
		$package = geodir_get_post_package( $post );

		$is_valid = empty( $package->disable_tickets ) ? true : false;

		return apply_filters( 'geodir_pricing_tickets_is_valid', $is_valid, $post );
	}
}
