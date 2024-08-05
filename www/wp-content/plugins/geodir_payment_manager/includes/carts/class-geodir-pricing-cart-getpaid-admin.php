<?php
/**
 * Pricing Manager & Invoicing integration class (Admin section).
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
 * GeoDir_Pricing_Cart_GetPaid_Admin class.
 */
class GeoDir_Pricing_Cart_GetPaid_Admin {

	/**
	 * Invoicing cart.
	 *
	 * @var GeoDir_Pricing_Cart_GetPaid
	 */
	public $cart;

	/**
	 * Class constructor.
	 *
	 * @param GeoDir_Pricing_Cart_GetPaid $cart
	 */
	public function __construct( $cart ) {

		$this->cart = $cart;

		// Bulk sync packages on updates.
		add_action( 'admin_init', array( $this, 'maybe_bulk_sync_packages' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );

		// MISC.
		add_filter( 'wpinv_item_non_editable_message', array( $this, 'notice_edit_package' ), 10, 2 );
		add_action( 'geodir_pricing_create_post_invoice', array( $cart, 'create_invoice' ) );
		add_action( 'geodir_pricing_link_post_invoice', array( $cart, 'link_post_invoice' ), 10, 2 );
		add_filter( 'geodir_pricing_admin_list_packages_columns', array( $this, 'admin_list_packages_columns' ), 10 );
		add_action( 'geodir_pricing_admin_list_packages_column', array( $this, 'admin_list_packages_column' ), 10, 2 );
		add_action( 'geodir_pricing_admin_list_packages_column_actions', array( $this, 'admin_list_packages_column_actions' ), 10, 2 );
		add_action( 'getpaid_admin_table_invoice_number_column', array( $this, 'admin_table_invoice_number_column' ) );

		// Use custom payment forms for each package.
		add_filter( 'geodir_pricing_package_settings', array( $this, 'payment_form_settings' ), 10, 2 );
		add_filter( 'geodir_pricing_process_data_for_save', array( $this, 'save_payment_form_settings' ), 10, 2 );

	}

	/**
	 * Bulk syncs packages on updates.
	 *
	 */
	public function maybe_bulk_sync_packages() {

		// Maybe abort early.
		if ( wp_doing_ajax() || isset( $_REQUEST['do_update_geodirectory'] ) || ! get_option( 'geodir_pricing_version' ) ) {
			return;
		}

		if ( geodir_get_option( 'pm_wpi_merge_packages' ) ) {
			return;
		}

		$packages = geodir_pricing_get_packages( array( 'fields' => array( 'id' ) ) );

		foreach ( $packages as $package ) {
			$this->cart->sync_item_to_package( $package->id );
		}

		geodir_update_option( 'pm_wpi_merge_packages', 1 );

	}

	/**
	 * Adds meta boxes on invoice pages.
	 *
	 *
	 * @param string  $post_type Post type.
	 * @param WP_Post $post      Post object.
	 */
	public function add_meta_boxes( $post_type, $post ) {

		// Mabe abort early.
		if ( ! geodir_is_gd_post_type( $post_type ) ) {
			return;
		}
		$post_status = ! empty( $post ) ? $post->post_status : '';

		$add_meta_box = apply_filters( 'geodir_pricing_add_meta_boxes', current_user_can( 'manage_options' ) && $post_status != 'auto-draft', $post_type, $post );

		if ( $add_meta_box && $this->cart->allow_invoice_for_listing( $post->ID ) ) {
			add_meta_box( 'gd-wpi-create-invoice', __( 'Listing Invoice' ), array( $this, 'display_create_invoice_meta_box' ), $post_type, 'side', 'high' );
		}

		if ( $add_meta_box ) {
			add_meta_box( 'gd-wpi-post-invoices', __( 'Listing Invoices', 'geodir_pricing' ), array( $this, 'display_listing_invoices_meta_box' ), $post_type );
		}

	}

	/**
	 * Displays a metabox to generate a new invoice.
	 *
	 *
	 * @param  WP_Post $post      Post object.
	 */
	public function display_create_invoice_meta_box( $post ) {
		$package_id   = geodir_get_post_meta( $post->ID, 'package_id', true );
		$package_info = geodir_pricing_get_package( (int) $package_id );

		if ( empty( $package_info ) ) {
			return;
		}

		$package_title = strip_tags( $package_info->title );

		?>

		<p>
			<?php
				echo
					wp_sprintf(
						__( 'Create a new invoice for this listing with the package <b>%s</b>. Invoice will be created with Pending Payment status.', 'geodir_pricing' ),
						$package_title
					);
			?>
		</p>

		<div id="gd-btn-action">
			<input id="geodir_create_post_invoice" data-nonce-create-invoice="<?php echo esc_attr( wp_create_nonce( 'create-invoice-' . $post->ID ) ); ?>" data-id="<?php echo absint( $post->ID ); ?>" class="button button-primary button-large" value="<?php esc_attr_e( 'Create Invoice', 'geodir_pricing' ); ?>" type="button">
			<span class="spinner" style="float: none;"></span>
		</div>

		<?php
	}

	/**
	 * Displays the listing invoices metabox.
	 *
	 * @param  WP_Post $post Post object.
	 */
	public function display_listing_invoices_meta_box( $post ) {
		global $aui_bs5;

		$design_style = geodir_design_style();

		$post_invoices = GeoDir_Pricing_Post_Package::get_items(
			array(
				'post_id' => $post->ID,
				'order_by' => 'id DESC'
			)
		);

		$columns = apply_filters(
			'geodir_getpaid_listing_invoices_columns',
			array(
				'invoice'      => __( 'Invoice', 'invoicing' ),
				'date'         => __( 'Created', 'invoicing' ),
				'paid'         => __( 'Paid', 'invoicing' ),
				'status'       => __( 'Status', 'invoicing' ),
				'total'        => __( 'Total', 'invoicing' ),
			),
			$post
		);

		?>

		<style>
			#poststuff #gd-wpi-post-invoices .inside {
   				margin: 0;
    			padding: 0;
			}

			.getpaid-item-status-pending,
			.getpaid-invoice-status-wpi-pending {
				background-color: #f9fbe7;
				color: #33691e;
				border-bottom: 1px solid #dcedc8;
			}

			.getpaid-item-status-canceled,
			.getpaid-invoice-status-wpi-cancelled {
				background-color: #ede7f6;
				color: #311b92;
				border-bottom: 1px solid #d1c4e9;
			}

			.getpaid-item-status-failing,
			.getpaid-invoice-status-wpi-failed {
				background-color: #fff3e0;
				border-bottom: 1px solid #ffe0b2;
				color: #bf360c;
			}

			.getpaid-item-status-info,
			.getpaid-invoice-status-wpi-renewal {
				background-color:#e0f7fa;
				border-bottom: 1px solid#bbdefb;
				color: #006064;
			}

			.getpaid-item-status-success,
			.getpaid-invoice-status-publish {
				background-color:#f1f8e9;
				border-bottom: 1px solid#dcedc8;
				color: #33691e;
			}

			.getpaid-item-status-expired,
			.getpaid-invoice-status-wpi-onhold {
				background-color: #fbe9e7;
				border-bottom: 1px solid#ffccbc;
				color: #bf360c;
			}

			.getpaid-item-status-trial,
			.getpaid-invoice-status-wpi-processing {
				background-color: #eceff1;
				border-bottom: 1px solid#9e9e9e;
				color: #263238;
			}

		</style>
		<?php
		if ( $design_style ) {
			$table_class = 'w-100 bg-white';
		} else {
			$table_class = 'wp-list-table widefat fixed striped';
		}
		?>
		<div class="bsui" style="overflow: auto;">
			<table class="gd-listing-invoices <?php echo $table_class; ?>">
				<thead>
					<tr>
						<?php
							foreach ( $columns as $key => $label ) {
								$key   = esc_attr( $key );
								$label = sanitize_text_field( $label );

								echo "<th class='geodir-listing-invoice-field-$key bg-light p-2 color-dark " . ( $aui_bs5 ? 'text-start' : 'text-left' ) . "'>$label</th>";
							}
						?>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $post_invoices ) ) : ?>
						<tr>
							<td colspan="<?php echo count($columns); ?>" class="p-2 text-muted <?php echo ( $aui_bs5 ? 'text-start' : 'text-left' ); ?>">
								<?php _e( 'This listing has no invoices.', 'geodir_pricing' ); ?>
							</td>
						</tr>
					<?php endif; ?>

					<?php

						foreach( $post_invoices as $post_invoice ) :

							// Ensure that we have an invoice.
							$payment = new WPInv_Invoice( $post_invoice->invoice_id );

							// Abort if the invoice is invalid.
							if ( ! $payment->exists() ) {
								continue;
							}

							echo '<tr>';

								foreach ( array_keys( $columns ) as $key ) {

									echo '<td class="p-2 text-left ' . ( $aui_bs5 ? 'text-start' : 'text-left' ) . '">';

										switch( $key ) {

											case 'total':
												echo '<strong>' . wpinv_price( $payment->get_total(), $payment->get_currency() ) . '</strong>';
												break;

											case 'paid':
												echo getpaid_format_date_value( $payment->get_date_completed(), null, true );
												break;

											case 'date':
												echo getpaid_format_date_value( $payment->get_date_created(), null, true );
												break;

											case 'status':
												echo $payment->get_status_label_html();
												break;

											case 'invoice':
												$link    = esc_url( get_edit_post_link( $payment->get_id() ) );
												$invoice = sanitize_text_field( $payment->get_number() );

												echo "<a href='$link'>$invoice</a>";
												break;
										}

									echo '</td>';

								}

							echo '</tr>';

						endforeach;

					if ( $design_style ) {
					?>
					<tr><td colspan="5" class="px-3 pt-4">
					<?php
					$button = aui()->button(
						array(
							'type' => 'a',
							'class' => 'btn btn-primary geodir-link-invoice',
							'content' => __( 'Link Invoice', 'geodir_pricing' ),
							'icon' => 'fas fa-dollar-sign geodir-btn-icon',
							'href' => 'javascript:void(0)'
						)
					);

					echo aui()->input( array(
							'type' => 'text',
							'id' => 'geodir_link_invoice',
							'name' => 'geodir_link_invoice',
							'label' => esc_html__( 'Invoice ID or Number', 'geodir_pricing' ),
							'help_text' => __( 'This is an advanced setting to manually link a listing to GetPaid invoice. Enter Invoice ID or Number. Ex: 3920.', 'geodir_pricing' ),
							'label_show' => false,
							'placeholder' => esc_html__( 'Invoice ID or Number', 'geodir_pricing' ),
							'class' => 'text-medium',
							'input_group_right' => $button,
							'extra_attributes' => array(
								'data-post-id' => absint( $post->ID ),
								'data-nonce' => wp_create_nonce( 'link-invoice-' . absint( $post->ID ) )
							)
						)
					);
					?>
					</td></tr>
					<?php } else { ?>
					<tr>
						<td colspan="5" class="px-3 pt-4">
							<p></p><input type="text" name="geodir_link_invoice" id="geodir_link_invoice" placeholder="<?php esc_attr_e( 'Invoice ID or Number', 'geodir_pricing' ); ?>" class="medium-text" data-post-id="<?php echo absint( $post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'link-invoice-' . absint( $post->ID ) ) ); ?>"> <a href="javascript:void(0)" class="button button-primary geodir-link-invoice"><i class="fas fa-dollar-sign geodir-btn-icon"></i> <?php _e( 'Link Invoice', 'geodir_pricing' ); ?></a><br>
							<p class="howto"><?php _e( 'This is an advanced setting to manually link a listing to GetPaid invoice. Enter Invoice ID or Number. Ex: 3920.', 'geodir_pricing' ); ?></p>
						</td>
					</tr>
					<?php } ?>
				</tbody>

			</table>
		</div>
		<?php
	}

	/**
	 * Filters the notice shown when editing a package.
	 *
	 * @param  string $message Error message.
	 * @param  int $item_id Item id
	 */
	public function notice_edit_package( $message, $item_id ) {

		if ( get_post_meta( $item_id, '_wpinv_type', true ) == 'package' && ( $package_id = (int) get_post_meta( $item_id, '_wpinv_custom_id', true ) ) ) {
			$post_type = geodir_pricing_package_post_type( $package_id );
			return wp_sprintf( __( 'This package can be edited from %sGeoDirectory > Settings > Pricing > Packages #%d%s', 'geodir_pricing' ), '<a href="' . esc_url( admin_url('edit.php?post_type=' . $post_type . '&page=' . $post_type . '-settings&tab=cpt-package&section=add-package&id=' . $package_id ) ) . '">', $package_id, '</a>' );
		}

		return $message;
	}

	/**
	 * Filters the packages column.
	 *
	 * @param  array $columns
	 */
	public function admin_list_packages_columns( $columns = array() ) {

		$replacement = array(
			'product_id' => __( 'GetPaid Item', 'geodir_pricing' )
		);

		$position = array_search( 'lifetime', array_keys( $columns ) );
		$position = false === $position ? count( $columns ) : $position + 1;
		$columns  = array_merge( array_slice( $columns, 0, $position ), $replacement, array_slice( $columns, $position ) );

		return $columns;
	}

	/**
	 * Displays the product id column.
	 *
	 * @param array $item
	 * @param string $column_name
	 */
	public function admin_list_packages_column( $item, $column_name ) {

		if ( 'product_id' == $column_name ) {

			$item = wpinv_get_item_by( 'custom_id', $item['id'], 'package' );
			$item = new WPInv_Item( $item );

			if ( $item->exists() ) {
				$edit_link = esc_url( $item->get_edit_url() );
				printf( '<a href="%s">%d</a>', $edit_link, $item->get_id() );
			} else {
				echo '<a href="javascript:void(0)" class="geodir-sync-package geodir-act-sync" title="' . esc_attr( 'Synchronize package to GetPaid item' ) . '" data-sync-nonce="' . esc_attr( wp_create_nonce( 'geodir-sync-package-' . $item->get_id() ) ) . '" data-reload="1"><i class="fas fa-sync"></i></a>';
			}

		}

	}

	/**
	 * Displays the product sync action.
	 *
	 * @param array $action
	 * @param array $item
	 */
	public static function admin_list_packages_column_actions( $actions, $item ) {
		$actions['sync'] = '<a href="javascript:void(0)" class="geodir-sync-package geodir-act-sync" title="' . esc_attr( 'Synchronize package to GetPaid item' ) . '" data-sync-nonce="' . esc_attr( wp_create_nonce( 'geodir-sync-package-' . $item['id'] ) ) . '"><i class="fas fa-sync"></i></a>';
		return $actions;
	}

	/**
	 * Adds payment form fields to the edit package form.
	 *
	 * @param $settings
	 * @param $package_data
	 *
	 * @return array
	 */
	public function payment_form_settings( $settings, $package_data ) {
		$payment_forms = wp_list_pluck(
			get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'wpi_payment_form',
				)
			),
			'post_title',
			'ID'
		);

		$getpaid_settings = array();

		$getpaid_settings[] = array(
			'type'  => 'title',
			'id'    => 'payment_form_settings',
			'title' => __( 'GetPaid', 'geodir_pricing' ),
			'desc'  => '',
		);

		$getpaid_settings[] = array(
			'type'        => 'select',
			'id'          => 'package_payment_form',
			'title'       => __( 'Payment Form', 'geodir_pricing' ),
			'desc'        => __( 'Optional. Select a payment form to use when paying for this package.', 'geodir_pricing' ),
			'options'     => $payment_forms,
			'placeholder' => __( 'Select payment form', 'geodir_pricing' ),
			'class'       => 'geodir-select',
			'desc_tip'    => true,
			'advanced'    => false,
			'value'       => empty( $package_data['package_payment_form'] ) ? wpinv_get_default_payment_form() : intval( $package_data['package_payment_form'] ),
		);

		$getpaid_settings[] = array(
			'type'     => 'checkbox',
			'id'       => 'package_name_your_price',
			'title'    => __( 'Custom Prices', 'geodir_pricing' ),
			'desc'     => __( 'Tick to let customers name their own prices.', 'geodir_pricing' ),
			'std'      => '0',
			'advanced' => false,
			'value'    => ( ! empty( $package_data['package_name_your_price'] ) ? '1' : '0' )
		);

		if ( ! empty( $package_data['post_type'] ) && GeoDir_Post_types::supports( $package_data['post_type'], 'events' ) ) {
			$getpaid_settings[] = array(
				'type' => 'checkbox',
				'id' => 'package_disable_tickets',
				'title'=> __( 'Disable Events Tickets', 'geodir_pricing' ),
				'desc' => __( 'Tick to disable buy/sell event tickets feature for the events added with this package.', 'geodir_pricing' ),
				'std' => '0',
				'advanced' => true,
				'value' => ( ! empty( $package_data['disable_tickets'] ) ? '1' : '0' )
			);
		}

		$getpaid_settings[] = array(
			'type' => 'sectionend',
			'id'   => 'package_payment_form_settings'
		);

		return array_merge( $settings, $getpaid_settings );
	}

	/**
	 * Save the payment forms settings.
	 *
	 * @param $package_data
	 * @param $data
	 *
	 * @return mixed
	 */
	public function save_payment_form_settings( $package_data, $data ) {

		if ( ! empty( $data['payment_form'] ) ) {
			$package_data['meta']['package_payment_form'] = intval( $data['payment_form'] );
		} else {
			$package_data['meta']['package_payment_form'] = '';
		}

		if ( ! empty( $data['name_your_price'] ) ) {
			$package_data['meta']['package_name_your_price'] = 1;
		} else {
			$package_data['meta']['package_name_your_price'] = 0;
		}

		// Events Tickets feature
		if ( ! empty( $package_data['post_type'] ) && GeoDir_Post_types::supports( $package_data['post_type'], 'events' ) ) {
			if ( isset( $data['disable_tickets'] ) ) {
				$package_data['meta']['disable_tickets'] = ( ! empty( $data['disable_tickets'] ) ? 1 : 0 );
			} else if ( isset( $package['disable_tickets'] ) ) {
				$package_data['meta']['disable_tickets'] = ( ! empty( $package['disable_tickets'] ) ? 1 : 0 );
			} else {
				$package_data['meta']['disable_tickets'] = 0;
			}
		}

		return $package_data;
	}

	/**
	 * Display listing link on invoices table.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function admin_table_invoice_number_column( $invoice ) {

		$items = GeoDir_Pricing_Post_Package::get_items( array( 'invoice_id' => $invoice->get_id() ) );
		$items = is_array( $items ) ? $items : array();
		if ( empty( $items ) ) {
			return;
		}

		$listings  = array();
		$post_type = '';

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

			// Link to the actual listing.
			$url   = esc_url( get_edit_post_link( $listing_id ) );
			$title = "<a href='$url'>$title</a>";

			$listings[] = $title;
	
		}

		// Abort if there are no listings.
		if ( empty( $listings ) ) {
			return;
		}

		// If more than one listing...
		if ( empty( $post_type ) || 1 < count( $listings ) ) {
			$post_type = __( 'Listings', 'geodir_pricing' );
		}

		printf(
			'<div class="geodir-invoice-listing-link"><strong>%s:</strong> %s</div>',
			esc_html( $post_type ),
			wp_kses_post( implode( ', ', $listings ) )
		);

	}

}
