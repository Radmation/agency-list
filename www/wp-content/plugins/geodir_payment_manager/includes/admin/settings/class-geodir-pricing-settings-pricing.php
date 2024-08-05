<?php
/**
 * Pricing Manager Admin Settings.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Settings_Pricing class.
 */
if ( ! class_exists( 'GeoDir_Pricing_Settings_Pricing', false ) ) :

	class GeoDir_Pricing_Settings_Pricing extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'pricing';
			$this->label = __( 'Pricing', 'geodir_pricing' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
//			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );

			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );

			add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				''					=> __( 'Settings', 'geodir_pricing' ),
			);

			if( isset( $_REQUEST['section'] ) && $_REQUEST['section'] == 'add-package' ) {
				$sections['add-package'] = ! empty( $_REQUEST['id'] ) ? __( 'Edit Package', 'geodir_pricing' ) : __( 'Add Package', 'geodir_pricing' );
			}

			return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			$settings = apply_filters( 'geodir_pricing_options', 
				array(
					array( 
						'name' => __( 'Listing Expiration Settings', 'geodir_pricing' ),
						'type' => 'title', 
						'desc' => '', 
						'id' => 'geodir_pricing_expiration_settings' 
					),
					array(
						'type' => 'checkbox',
						'id'   => 'pm_listing_expiry',
						'name' => __( 'Enable listing expiry?', 'geodir_pricing' ),
						'desc' => __( 'If disabled then no listings will expire in future.', 'geodir_pricing' ),
						'default' => '1',
						'advanced' => false
					),
					array( // @todo add also in package setting
						'type' => 'select',
						'id' => 'pm_listing_ex_status',
						'name' => __( 'Expired Listing Status', 'geodir_pricing' ),
						'desc' => __( 'Select the listing default status after the listing expires.', 'geodir_pricing' ),
						'class' => 'geodir-select',
						'options' => geodir_get_post_statuses( 'expire' ),
						'default' => 'gd-expired',
						'desc_tip' => true,
						'advanced' => false,
					),
					array(
						'type' => 'select',
						'id' => 'pm_paid_listing_status',
						'name' => __( 'Paid Listing Status', 'geodir_pricing' ),
						'desc' => __( 'Select the listing status to apply to the listing on payment received for the invoice.', 'geodir_pricing' ),
						'class' => 'geodir-select',
						'options' => geodir_get_post_statuses( 'paid' ),
						'default' => 'publish',
						'desc_tip' => true,
						'advanced' => false,
					),
					array( // @todo move to package setting
						'type' => 'checkbox',
						'id'   => 'pm_free_package_renew',
						'name' => __( 'Renewal for free package?', 'geodir_pricing' ),
						'desc' => __( 'Tick to allow renewal of listing with free package.', 'geodir_pricing' ),
						'default' => '0',
						'advanced' => true
					),
					array(
						'type' => 'sectionend', 
						'id' => 'geodir_pricing_expiration_settings'
					),
					array( 
						'name' => __( 'Cart Settings', 'geodir_pricing' ),
						'type' => 'title', 
						'desc' => '', 
						'id' => 'geodir_pricing_cart_settings' 
					),
					array(
						'type' => 'select',
						'id' => 'pm_cart',
						'name' => __( 'Cart', 'geodir_pricing' ),
						'desc' => sprintf( __( 'Select the cart for payments. You can use %sGetPaid%s (recommended) or %sWooCommerce%s.', 'geodir_pricing' ),
							'<a href="https://wordpress.org/plugins/invoicing/" target="_blank">',
							'</a>',
							'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">',
							'</a>'
						),
						'class' => 'geodir-select',
						'options' => geodir_pricing_cart_options(),
						'default' => '',
						'desc_tip' => true,
						'advanced' => false,
					),
					array(
						'type' => 'select',
						'id' => 'pm_checkout_redirect',
						'name' => __( 'Redirect to Checkout', 'geodir_pricing' ),
						'desc' => __( 'Select redirect to checkout after paid listing submitted.', 'geodir_pricing' ),
						'class' => 'geodir-select',
						'options' => array(
							'0' => __( 'Auto', 'geodir_pricing' ),
							'1' => __( 'After 1 sec delay', 'geodir_pricing' ),
							'2' => __( 'After 2 sec delay', 'geodir_pricing' ),
							'3' => __( 'After 3 sec delay', 'geodir_pricing' ),
							'4' => __( 'After 4 sec delay', 'geodir_pricing' ),
							'5' => __( 'After 5 sec delay', 'geodir_pricing' ),
							'no' => __( 'No Redirect', 'geodir_pricing' )
						),
						'default' => '0',
						'desc_tip' => true,
						'advanced' => false,
					),
					array(
						'type' => 'sectionend', 
						'id' => 'geodir_pricing_cart_settings'
					)
				)
			);
			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}
		
		/**
		 * Form method.
		 *
		 * @param  string $method
		 *
		 * @return string
		 */
		public function form_method( $method ) {
			global $current_section;

			return 'post';
		}
	}

endif;

return new GeoDir_Pricing_Settings_Pricing();
