<?php
/**
 * Pricing Manager Package Settings.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'GeoDir_Pricing_Settings_Cpt_Package', false ) ) :

	/**
	 * GeoDir_Pricing_Settings_Cpt_Package class.
	 */
	class GeoDir_Pricing_Settings_Cpt_Package extends GeoDir_Settings_Page {

		/**
		 * Post type.
		 *
		 * @var string
		 */
		private static $post_type = '';

		/**
		 * Sub tab.
		 *
		 * @var string
		 */
		private static $sub_tab = '';


		/**
		 * Constructor.
		 */
		public function __construct() {

			self::$post_type = ! empty( $_REQUEST['post_type'] ) && geodir_is_gd_post_type( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : 'gd_place';
			self::$sub_tab   = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : '';

			$this->id    = 'cpt-package';
			$this->label = __( 'Packages', 'geodir_pricing' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
//			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );

			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );

			// Add package
			if ( isset( $_REQUEST['section'] ) && $_REQUEST['section'] == 'add-package' ) {
				add_action( 'geodir_admin_field_package_lifetime', array( $this, 'field_package_lifetime' ), 10, 1 );
				add_action( 'geodir_admin_field_package_lifetime_trial', array( $this, 'field_package_lifetime_trial' ), 10, 1 );
			} else {
				// List cpt packages
				add_action( 'geodir_admin_field_cpt_packages_page', array( $this, 'cpt_packages_page' ) );
			}

			add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array();

			if ( isset( $_REQUEST['section'] ) && $_REQUEST['section'] == 'add-package' ) {
				$sections['add-package'] = __( 'Add New Package', 'geodir_pricing' );
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

			if ( $current_section == 'add-package' ) {
				$data = $this->sanitize_package();

				if ( is_wp_error( $data ) ) {
					GeoDir_Admin_Settings::add_error( $data->get_error_message() );
					return;
				}

				$id = GeoDir_Pricing_Package::insert_package( $data, true );
				if ( is_wp_error( $id ) ) {
					GeoDir_Admin_Settings::add_error( $data->get_error_message() );
					return;
				}

				GeoDir_Admin_Settings::add_message( __( 'Package saved successfully.', 'geodir_pricing' ) );

				wp_redirect( admin_url( 'edit.php?post_type=' . self::$post_type . '&page=' . self::$post_type . '-settings&tab=cpt-package&section=add-package&id=' . $id ) );
				geodir_die();
			}
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			$package_data = array( 'post_type' => self::$post_type );

			if ( 'add-package' == $current_section ) {
				if ( isset( $_REQUEST['id'] ) ) {
					$data = GeoDir_Pricing_Package::get_package( absint( $_REQUEST['id'] ), ARRAY_A, 'edit' );

					if ( ! empty( $data ) && $data['post_type'] == self::$post_type ) {
						$meta = GeoDir_Pricing_Package::get_metas( absint( $_REQUEST['id'] ), true, 'edit' );
						$package_data = ! empty( $data ) && ! empty( $meta ) ? array_merge( $data, $meta ) : $data;
					}
				}

				$package_data = wp_parse_args( (array)$package_data, array(
					'id' => '',
					// Details
					'post_type' => self::$post_type,
					'name' => '',
					'title' => '',
					'description' => '',
					'fa_icon' => 'fas fa-certificate',
					'amount' => '',
					'time_interval' => '',
					'time_unit' => 'M',
					'recurring' => '',
					'recurring_limit' => '',
					'trial' => '',
					'trial_amount' => '',
					'trial_interval' => '',
					'trial_unit' => 'M',

					// Features / Meta
					'is_private' => '',
					'exclude_field' => array(),
					'exclude_category' => array(),
					'image_limit' => '',
					'category_limit' => '',
					'desc_limit' => '',
					'tag_limit' => '',
					'max_posts' => '',
					'has_upgrades' => '',
					'upgrade_packages' => array(),
					'disable_editor' => '',
					'can_reply_review' => '',
					
					// Status
					'is_default' => '',
					'display_order' => GeoDir_Pricing_Package::default_sort_order( self::$post_type ),
					'downgrade_pkg' => '',
					'post_status' => 'default',
					'status' => '',
				) );

				$package_data  = apply_filters( "geodir_pricing_default_package_data", $package_data );

				$panel_actions = ' <a href="' . esc_url( admin_url( 'edit.php?post_type=' . self::$post_type . '&page=' . self::$post_type . '-settings&tab=cpt-package' ) ) . '" class="add-new-h2"><i class="fas fa-arrow-alt-circle-left" aria-hidden="true"></i> ' . __( 'Back to Packages', 'geodir_pricing' ) . '</a>';
				if ( ! empty( $package_data['id'] ) ) {
					$panel_actions .= ' <a href="' . esc_url( admin_url( 'edit.php?post_type=' . self::$post_type . '&page=' . self::$post_type . '-settings&tab=cpt-package&section=add-package' ) ) . '" class="add-new-h2"><i class="fas fa-plus-circle" aria-hidden="true"></i> ' . __( 'Add New', 'geodir_pricing' ) . '</a>';
				}

				$settings  = apply_filters( "geodir_pricing_package_settings", array(
					array(
						'type' 	=> 'page-title',
						'id'   	=> 'package_settings',
						'title' => ( ! empty( $package_data['id'] ) ? wp_sprintf( __( 'Edit Package #%d', 'geodir_pricing' ), $package_data['id'] ) : __( 'Add New Package', 'geodir_pricing' ) ),
						'desc' 	=> '',
						'title_html' => $panel_actions
					),
//					array(
//						'type' => 'sectionend',
//						'id' => 'package_settings'
//					),
					array(
						'type' 	=> 'title',
						'id'   	=> 'package_details_settings',
						'title' => __( 'Details', 'geodir_pricing' ),
						'desc' 	=> '',
					),
					array(
						'type'     => 'hidden',
						'id'       => 'package_id',
						'title'    => __( 'ID', 'geodir_pricing' ),
						'value'	   => $package_data['id']
					),
					array(
						'type' 		=> 'hidden',
						'id'       	=> 'package_post_type',
						'title'     => __( 'Post Type', 'geodir_pricing' ),
						'desc' 		=> '',
						//'options'   => array( self::$post_type => geodir_get_post_type_singular_label( self::$post_type, false, true ) ),
						'class'		=> 'geodir-select',
						'desc_tip' 	=> false,
						'advanced' 	=> false,
						'value'	   	=> self::$post_type
					),
					array(
						'type'     => 'text',
						'id'       => 'package_name',
						'title'    => __( 'Name', 'geodir_pricing' ),
						'desc'     => __( 'Package short name.', 'geodir_pricing' ),
						'std'      => '',
						'desc_tip' => true,
						'advanced' => false,
						'custom_attributes' => array(
							'required' => 'required'
						),
						'value'	   => $package_data['name']
					),
					array(
						'type'     => 'textarea',
						'id'       => 'package_title',
						'title'    => __( 'Title', 'geodir_pricing' ),
						'desc'     => __( 'Package title to be display on frontend. Leave blank to auto generate.', 'geodir_pricing' ),
						'std'      => '',
						'desc_tip' => true,
						'advanced' => false,
						'value'	   => $package_data['title']
					),
					array(
						'type'     => 'textarea',
						'id'       => 'package_description',
						'title'    => __( 'Description', 'geodir_pricing' ),
						'desc'     => __( 'Package description.', 'geodir_pricing' ),
						'std'      => '',
						'desc_tip' => true,
						'advanced' => true,
						'value'	   => $package_data['description']
					),
					array(
						'type' => 'font-awesome',
						'id'   => 'package_fa_icon',
						'name' => __( 'Package Icon', 'geodir_pricing' ),
						'desc' => __( 'Select the font awesome icon to use on frontend.', 'geodir_pricing' ),
						//'class' => 'geodir-select',
						'default' => '',
						'desc_tip' => true,
						'advanced' => true,
						'custom_attributes' => array(
							'data-fa-icons' => true,
						),
						'value'	   => $package_data['fa_icon']
					),

					array( 
						'type' => 'sectionend', 
						'id' => 'package_details_settings'
					),
					array(
						'type' 	=> 'title',
						'id'   	=> 'package_price_lifetime_settings',
						'title' => __( 'Price & Lifetime', 'geodir_pricing' ),
						'desc' 	=> '',
					),
					array(
						'type'     => 'text',
						'id'       => 'package_amount',
						'title'    => wp_sprintf( __( 'Price (%s)', 'geodir_pricing' ), geodir_pricing_currency_sign() ),
						'desc'     => __( 'Package price', 'geodir_pricing' ),
						'std'      => '',
						'desc_tip' => true,
						'advanced' => false,
						'value'	   => $package_data['amount']
					),
					array(
						'type'     => 'package_lifetime',
						'id'       => 'package_lifetime',
						'title'    => __( 'Package Lifetime', 'geodir_pricing' ),
						'desc'     => __( 'Select package lifetime. Leave blank or 0 to allow unlimited lifetime.', 'geodir_pricing' ),
						'sub_fields' => array( 'package_time_interval', 'package_time_unit' ),
						'package_data' => $package_data
					),
					array(
						'type' => 'checkbox',
						'id'   => 'package_recurring',
						'title'=> __( 'Is Recurring?', 'geodir_pricing' ),
						'desc' => __( 'Tick to make package recurring.', 'geodir_pricing' ),
						'std'  => '0',
						'advanced' => false,
						'value'	=> ( ! empty( $package_data['recurring'] ) ? '1' : '0' )
					),
					array(
						'type'     => 'number',
						'id'       => 'package_recurring_limit',
						'title'    => __( 'Recurring limit', 'geodir_pricing' ),
						'desc'     => __( 'Select 0 for recurring forever until cancelled.', 'geodir_pricing' ),
						'std'      => '',
						'desc_tip' => true,
						'advanced' => false,
						'custom_attributes' => array(
							'min' => '0',
							'step' => '1',
						),
						'value'	   => $package_data['recurring_limit'],
						'element_require' => '[%package_recurring%:checked]'
					),
					array(
						'type' => 'checkbox',
						'id'   => 'package_trial',
						'title'=> __( 'Offer Trial?', 'geodir_pricing' ),
						'desc' => __( 'Tick to allow trial offer.', 'geodir_pricing' ),
						'std'  => '0',
						'advanced' => false,
						'value'	=> ( ! empty( $package_data['trial'] ) ? '1' : '0' ),
						'element_require' => '[%package_recurring%:checked]'
					),
					/*array(
						'type'     => 'text',
						'id'       => 'package_trial_amount',
						'title'    => wp_sprintf( __( 'Trial Price (%s)', 'geodir_pricing' ), geodir_pricing_currency_sign() ),
						'desc'     => __( 'Package trial price. Leave blank or 0 to allow free trial.', 'geodir_pricing' ),
						'std'      => '',
						'desc_tip' => true,
						'advanced' => false,
						'value'	   => $package_data['trial_amount']
					),*/
					array(
						'type'     => 'package_lifetime_trial',
						'id'       => 'package_lifetime_trial',
						'title'    => __( 'Offer Trial For', 'geodir_pricing' ),
						'desc'     => __( 'Select package trial lifetime.', 'geodir_pricing' ),
						'sub_fields' => array( 'package_trial_interval', 'package_trial_unit' ),
						'package_data' => $package_data,
						'element_require' => '[%package_trial%:checked]'
					),
					array( 
						'type' => 'sectionend', 
						'id' => 'package_price_lifetime_settings' 
					),
					array(
						'id'   	=> 'package_features_settings',
						'type' 	=> 'title',
						'title' => __( 'Features', 'geodir_pricing' ),
						'desc' 	=> '',
					),
					array(
						'type' 		=> 'multiselect',
						'id'       	=> 'package_exclude_field',
						'title'     => __( 'Exclude Fields', 'geodir_pricing' ),
						'desc' 		=> __( 'Select post fields to exclude for this package.', 'geodir_pricing' ),
						'options'   => geodir_pricing_exclude_field_options( self::$post_type, $package_data ),
						'placeholder' => __( 'Select Fields', 'geodir_pricing' ),
						'class'		=> 'geodir-select',
						'desc_tip' 	=> true,
						'advanced' 	=> false,
						'value'	   	=> $package_data['exclude_field']
					),
					array(
						'type' 		=> 'multiselect',
						'id'       	=> 'package_exclude_category',
						'title'     => __( 'Exclude Categories', 'geodir_pricing' ),
						'desc' 		=> __( 'Select categories exclude for this package. If removing a parent category, you should remove its child categories. It is not recommended to exclude categories from live packages as users will not be able to remove that category from the frontend.', 'geodir_pricing' ),
						'options'   => self::exclude_category_options( self::$post_type, $package_data ),
						'placeholder' => __( 'Select Categories', 'geodir_pricing' ),
						'class'		=> 'geodir-select',
						'desc_tip' 	=> true,
						'advanced' 	=> true,
						'value'	   	=> $package_data['exclude_category']
					),
					array(
						'type'     => 'number',
						'id'       => 'package_image_limit',
						'title'    => __( 'Image Limit', 'geodir_pricing' ),
						'desc'     => __( 'Leave blank to allow unlimited images.', 'geodir_pricing' ),
						'std'      => '',
						'placeholder' => ( $package_data['image_limit'] > 0 ? $package_data['image_limit'] : __( 'Unlimited', 'geodir_pricing' ) ),
						'value' => ( $package_data['image_limit'] > 0 ? $package_data['image_limit'] : '' ),
						'desc_tip' => true,
						'advanced' => true,
						'custom_attributes' => array(
							'min' => '0',
							'step' => '1',
						)
					),
					array(
						'type'     => 'number',
						'id'       => 'package_category_limit',
						'title'    => __( 'Category Limit', 'geodir_pricing' ),
						'desc'     => __( 'Leave blank or 0 to allow unlimited categories.', 'geodir_pricing' ),
						'std'      => '',
						'placeholder' => ( $package_data['category_limit'] > 0 ? $package_data['category_limit'] : __( 'Unlimited', 'geodir_pricing' ) ),
						'value' => ( $package_data['category_limit'] > 0 ? $package_data['category_limit'] : '' ),
						'desc_tip' => true,
						'advanced' => true,
						'custom_attributes' => array(
							'min' => '0',
							'step' => '1',
						)
					),
					array(
						'type'     => 'number',
						'id'       => 'package_tag_limit',
						'title'    => __( 'Tag Limit', 'geodir_pricing' ),
						'desc'     => __( 'Leave blank or 0 to allow unlimited tags.', 'geodir_pricing' ),
						'std'      => '',
						'placeholder' => ( $package_data['tag_limit'] > 0 ? $package_data['tag_limit'] : __( 'Unlimited', 'geodir_pricing' ) ),
						'value' => ( $package_data['tag_limit'] > 0 ? $package_data['tag_limit'] : '' ),
						'desc_tip' => true,
						'advanced' => true,
						'custom_attributes' => array(
							'min' => '0',
							'step' => '1',
						)
					),
					array(
						'type' => 'checkbox',
						'id'   => 'package_use_desc_limit',
						'title'=> __( 'Limit Description Characters Length?', 'geodir_pricing' ),
						'desc' => __( 'Tick to set limit for the description characters length.', 'geodir_pricing' ),
						'std'  => '0',
						'advanced' => true,
						'value'	=> ( ! empty( $package_data['use_desc_limit'] ) ? '1' : '0' )
					),
					array(
						'type'     => 'number',
						'id'       => 'package_desc_limit',
						'title'    => __( 'Max. Description Characters', 'geodir_pricing' ),
						'desc'     => __( 'Maximum number characters allowed in description.', 'geodir_pricing' ),
						'std'      => '',
						'desc_tip' => true,
						'advanced' => true,
						'custom_attributes' => array(
							'min' => '0',
							'step' => '1',
						),
						'value'	   => $package_data['desc_limit'],
						'element_require' => '[%package_use_desc_limit%:checked]'
					),
					array(
						'type' => 'number',
						'id' => 'package_max_posts',
						'name' => __( 'Limit Posts Per User', 'geodir_pricing' ),
						'desc' => __( 'Limit max no. posts allowed per user under this package. Leave blank or enter 0 to allow unlimited posts.', 'geodir_pricing' ),
						'std' => '',
						'placeholder' => __( 'Unlimited', 'geodir_pricing' ),
						'value' => ( (int) $package_data['max_posts'] === 0 ? '' : ( (int) $package_data['max_posts'] < 0 ? -1 : (int) $package_data['max_posts'] ) ),
						'custom_attributes' => array(
							'min' => '-1',
							'step' => '1'
						),
						'desc_tip' => true,
						'advanced' => true
					),
					array(
						'type' => 'checkbox',
						'id'   => 'package_disable_editor',
						'title'=> __( 'Disable HTML Editor?', 'geodir_pricing' ),
						'desc' => __( 'Tick to disable html editor in description field.', 'geodir_pricing' ),
						'std'  => '0',
						'advanced' => true,
						'value' => ( ! empty( $package_data['disable_editor'] ) ? '1' : '0' )
					),
					array(
						'type' => 'checkbox',
						'id'   => 'package_disable_reviews',
						'title'=> __( 'Disable Reviews?', 'geodir_pricing' ),
						'desc' => __( 'Disable reviews for the listings assigned to this package.', 'geodir_pricing' ),
						'std'  => '0',
						'advanced' => true,
						'value' => ( ! empty( $package_data['disable_reviews'] ) ? '1' : '0' )
					),
					array(
						'type'     => 'select',
						'id'       => 'package_can_reply_review',
						'title'    => __( 'Review Replies', 'geodir_pricing' ),
						'desc'     => __( 'Who can reply to reviews.', 'geodir_pricing' ),
						'options'  => array(
							'' => __( 'Everyone', 'geodir_pricing' ),
							'admin' => __( 'Admin Only', 'geodir_pricing' ),
							'owner' => __( 'Listing Owner & Admin', 'geodir_pricing' ),
							'nobody' => __( 'Nobody', 'geodir_pricing' ),
						),
						'class'    => '',
						'desc_tip' => true,
						'advanced' => false,
						'value'    => $package_data['can_reply_review']
					),
					array(
						'type' => 'checkbox',
						'id'   => 'package_has_upgrades',
						'title'=> __( 'Has Upgrades?', 'geodir_pricing' ),
						'desc' => __( 'Tick if this package is allowed to be upgraded to another package.', 'geodir_pricing' ),
						'std'  => '0',
						'advanced' => true,
						'value' => ( ! empty( $package_data['has_upgrades'] ) ? '1' : '0' )
					),
					array(
						'type'            => 'multiselect',
						'id'              => 'package_upgrade_packages',
						'title'           => __( 'Upgrade To Packages', 'geodir_pricing' ),
						'desc'            => __( 'Select the packages to show in the upgrade packages list during upgrade listing assigned this package.', 'geodir_pricing' ),
						'options'         => geodir_pricing_upgrade_package_options( $package_data['post_type'], (int) $package_data['id'] ),
						'placeholder'     => __( 'Select Package', 'geodir_pricing' ),
						'class'           => 'geodir-select',
						'desc_tip'        => true,
						'advanced'        => false,
						'value'           => $package_data['upgrade_packages'],
						'element_require' => '[%package_has_upgrades%:checked]'
					),
					array( 
						'type' => 'sectionend', 
						'id' => 'package_features_settings' 
					),
					array(
						'id'   	=> 'package_status_settings',
						'type' 	=> 'title',
						'title' => __( 'Status', 'geodir_pricing' ),
						'desc' 	=> '',
					),
					array(
						'type' => 'checkbox',
						'id'   => 'package_status',
						'title' => __( 'Is Active?', 'geodir_pricing' ),
						'desc' => __( 'Tick to activate this package.', 'geodir_pricing' ),
						'std'  => '0',
						'advanced' => false,
						'value'	=> ( ! empty( $package_data['status'] ) ? '1' : '0' )
					),
					array(
						'type' => 'checkbox',
						'id'   => 'package_is_default',
						'title' => __( 'Is Default?', 'geodir_pricing' ),
						'desc' => __( 'Tick to use as a default package.', 'geodir_pricing' ),
						'std'  => '0',
						'advanced' => false,
						'value'	=> ( ! empty( $package_data['is_default'] ) ? '1' : '0' )
					),
					array(
						'type' => 'checkbox',
						'id'   => 'package_is_private',
						'title' => __( 'Is Private?', 'geodir_pricing' ),
						'desc' => __( 'If marked private then the package is not visible to frontend users to add a new listing.', 'geodir_pricing' ),
						'std'  => '0',
						'advanced' => false,
						'value'	=> ( ! empty( $package_data['is_private'] ) ? '1' : '0' ),
						'element_require' => '![%package_is_default%:checked]'
					),
					array(
						'type'     => 'number',
						'id'       => 'package_display_order',
						'title'    => __( 'Display Order', 'geodir_pricing' ),
						'desc'     => __( 'Package sort order.', 'geodir_pricing' ),
						'std'      => '',
						'desc_tip' => true,
						'advanced' => false,
						'custom_attributes' => array(
							'min' => '0',
							'step' => '1',
						),
						'value'	   => $package_data['display_order']
					),
					array(
						'type' 		=> 'select',
						'id'       	=> 'package_downgrade_pkg',
						'title'     => __( 'Downgrade to', 'geodir_pricing' ),
						'desc' 		=> __( 'Select package to apply on expire of current package.', 'geodir_pricing' ),
						'options'   => self::get_downgradable_packages( $package_data ),
						'desc_tip' 	=> true,
						'advanced' 	=> false,
						'value'	   	=> $package_data['downgrade_pkg']
					),
					array(
						'type' 		=> 'select',
						'id'       	=> 'package_post_status',
						'title'     => __( 'Paid Listing Status', 'geodir_pricing' ),
						'desc' 		=> __( 'Select status to apply to the post on payment received for the invoice. Select "Default" to apply post status set under "Pricing > Listing Expiration Settings > Paid Listing Status".', 'geodir_pricing' ),
						'options'   => array_merge( array( 'default' => wp_sprintf( __( 'Default ( %s )', 'geodir_pricing' ), geodir_get_post_status_name( geodir_get_option( 'pm_paid_listing_status' ) ) ) ), geodir_get_post_statuses( self::$post_type ) ),
						'desc_tip' 	=> true,
						'advanced' 	=> false,
						'value'	   	=> $package_data['post_status']
					),
					array( 
						'type' => 'sectionend', 
						'id' => 'package_status_settings' 
					),
				), $package_data );
			} else {
				$settings = apply_filters( 'geodir_pricing_settings_cpt_packages', 
					array(
						array( 
							'name' => __( 'Packages', 'geodir_pricing' ) ,
							'type' => 'page-title',
							'desc' => '', 
							'id' => 'geodir_pricing_section_cpt_packages',
							'title_html' => ' <a href="' . esc_url( admin_url( 'edit.php?post_type=' . self::$post_type . '&page=' . self::$post_type . '-settings&tab=cpt-package&section=add-package' ) ) . '" class="add-new-h2"><i class="fas fa-plus-circle" aria-hidden="true"></i> ' . __( 'Add New', 'geodir_pricing' ) . '</a>'
						),
						array( 
							'name' => 'cpt_packages_page', 
							'type' => 'cpt_packages_page', 
							'desc' => '', 
							'id' => 'geodir_cp_post_types_page_settings' 
						),
//						array(
//							'type' => 'sectionend',
//							'id' => 'geodir_pricing_section_cpt_packages'
//						)
					)
				);
			}

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section, $package_data );
		}
	
		public function sanitize_package( $request = null ) {
			if ( is_null( $request ) ) {
				$request = $_POST;
			}

			if ( empty( $request ) ) {
				return false;
			}

			$settings = $this->get_settings( 'add-package' );

			$data = array();
			foreach ( $settings as $key => $option ) {
				if ( empty( $option['id'] ) || empty( $option['type'] ) ) {
					continue;
				}

				$name = strpos( $option['id'], 'package_' ) === 0 ? substr( $option['id'], 8 ) : $option['id'];
				if ( in_array( $option['id'], array_keys( $request ) ) ) {
					$data[ $name ] = $request[ $option['id'] ];
				} else {
					$data[ $name ] = '';
				}

				if ( ! empty( $option['sub_fields'] ) ) {
					foreach ( $option['sub_fields'] as $sub_key => $sub_field ) {
						$name = strpos( $sub_field, 'package_' ) === 0 ? substr( $sub_field, 8 ) : $sub_field;
						if ( in_array( $sub_field, array_keys( $request ) ) ) {
							$data[ $name ] = $request[ $sub_field ];
						} else {
							$data[ $name ] = '';
						}
					}
				}
			}

			$data = apply_filters( 'geodir_pricing_sanitize_package', $data, $request );

			if ( empty( $data['name'] ) ) {
				return new WP_Error( 'invalid_package_name', __( 'Invalid package name.', 'geodir_pricing' ) );
			}

			if ( ! ( ! empty( $data['post_type'] ) && geodir_is_gd_post_type( $data['post_type'] ) ) ) {
				return new WP_Error( 'invalid_post_type', __( 'Invalid post type.', 'geodir_pricing' ) );
			}

			return GeoDir_Pricing_Package::prepare_data_for_save( $data );
		}

		public function field_package_lifetime( $field ) {
			global $aui_bs5;

			?>
			<div class="gd-pkg-lifetime-wrap <?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row">
				<label for="time_interval" class="col-sm-3 col-form-label <?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo $field['title']; ?><?php echo geodir_help_tip( $field['desc'] ); ?></label>
				<div class="col-sm-9 d-flex">
					<input name="package_time_interval" id="package_time_interval" value="<?php echo esc_attr( $field['package_data']['time_interval'] ); ?>" class="form-control" min="0" max="1000" step="1" type="number" lang="EN">
					<select name="package_time_unit" id="package_time_unit" class="form-control <?php echo ( $aui_bs5 ? ' ms-1' : ' ml-1' ); ?>">
						<?php echo geodir_pricing_lifetime_unit_options( $field['package_data'], $field['package_data']['time_unit'] ); ?>
					</select>
				</div>
			</div>
			<?php
		}

		public function field_package_lifetime_trial( $field ) {
			global $aui_bs5;

			?>
			<div class="gd-pkg-lifetime-wrap <?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row aui-conditional-field" data-element-require="jQuery(form).find('[data-argument=&quot;package_trial&quot;]').find('input:checked').val()" data-argument="package_lifetime_trial">
				<label for="trial_interval" class="col-sm-3 col-form-label <?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo $field['title']; ?><?php echo geodir_help_tip( $field['desc'] ); ?></label>
				<div class="col-sm-9 d-flex">
					<input name="package_trial_interval" id="package_trial_interval" value="<?php echo esc_attr( $field['package_data']['trial_interval'] ); ?>" class="form-control g" min="0" max="1000" step="1" type="number" lang="EN">
					<select name="package_trial_unit" id="package_trial_unit" class="form-control <?php echo ( $aui_bs5 ? ' ms-1' : ' ml-1' ); ?>">
						<?php echo geodir_pricing_lifetime_unit_options( $field['package_data'], $field['package_data']['trial_unit'] ); ?>
					</select>
				</div>
			</div>
			<?php
		}

		public static function get_downgradable_packages( $package = array() ) {
			$packages = array(
				'0' => __( 'Expire', 'geodir_pricing' ),
			);

			$results = geodir_pricing_get_packages( array( 'post_type' => $package['post_type'] ) );
			if ( ! empty( $results ) ) {
				foreach ( $results as $key => $data ) {
					$skip = ( ! empty( $package['id'] ) && $package['id'] == $data->id ) || ! empty( $data->recurring ) ? true : false;

					if ( apply_filters( 'geodir_pricing_package_skip_downgradable_package', $skip, $data, $package ) ) {
						continue;
					}

					$packages[ $data->id ] = __( $data->name, 'geodirectory' );
				}
			}

			return apply_filters( 'geodir_pricing_package_downgradable_packages', $packages, $package );
		}

		public static function exclude_category_options( $post_type = 'gd_place', $package = array() ) {
			$categories = geodir_category_tree_options( $post_type, false );

			return apply_filters( 'geodir_pricing_package_exclude_category_options', $categories, $package );
		}

		public function cpt_packages_page( $option ) {
			// Hide the save button
			$GLOBALS['hide_save_button'] = true;

			$table_list = new GeoDir_Pricing_Admin_Packages_Table_List();

			$table_list->prepare_items();

			echo '</table><div class="geodir-packages-list">';
			echo '<input type="hidden" name="post_type" value="' . esc_attr( self::$post_type ) . '" />';
			echo '<input type="hidden" name="page" value="' . esc_attr( self::$post_type ) . '-settings" />';
			echo '<input type="hidden" name="tab" value="' . esc_attr( self::$sub_tab ) . '" />';
			echo '<input type="hidden" name="section" value="" />';

			$table_list->views();
			$table_list->search_box( __( 'Search package', 'geodir_pricing' ), 'package' );
			$table_list->display();

			echo '</div>';
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

			if ( empty( $current_section ) ) {
				return 'get';
			}

			return 'post';
		}
	}


endif;

return new GeoDir_Pricing_Settings_Cpt_Package();
