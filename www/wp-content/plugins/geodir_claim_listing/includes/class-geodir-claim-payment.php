<?php
/**
 * Claim Listings Payment Class.
 * 
 * This class is used to allow claims to be paid if the Pricing Manager addon is installed.
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
class GeoDir_Claim_Payment {

	const db_table = GEODIR_CLAIM_TABLE;

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		// add settings to the package
		add_filter('geodir_pricing_package_settings', array( __CLASS__, 'pricing_package_settings' ), 10, 2);

		// add settings to the data array
		add_filter('geodir_pricing_default_package_data',array( __CLASS__,'pricing_package_data'),10,1);

		// save the settings
		add_filter('geodir_pricing_process_data_for_save',array( __CLASS__,'save_pricing_package_data'),10,3);

		// add package select to the claim listing form
		add_action('geodir_claim_post_form_after_fields',array( __CLASS__,'claim_form_package_select'),10,1);

		// claim payments
		add_action( 'geodir_pricing_post_package_payment_completed', array( __CLASS__, 'payment_completed' ), 20, 2 );
		add_filter( 'geodir_pricing_complete_package_update_post_data', array( __CLASS__, 'update_post_data_on_complete_payment' ), 10, 5 );
	}

	public static function get_claim_from_payment_id( $payment_id ) {
		global $wpdb;

		$claim = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . SELF::db_table . " WHERE payment_id = %d", $payment_id ) );

		return $claim;
	}

	/**
	 * Approve the claim when payment received if set to do so.
	 *
	 * @param $item
	 * @param $revision_id
	 */
	public static function payment_completed( $item, $revision_id ) {
		$package = ! empty( $item->package_id ) ? geodir_pricing_get_package( $item->package_id ): array();
		// check if we should approve the claim
		if ( ! empty( $item->task ) && $item->task == 'claim' && ! empty( $item->id ) && geodir_get_option( 'claim_auto_approve_on_payment', 1 ) && ! empty( $package ) && ! GeoDir_Pricing_Package::is_free( $package ) ) {
			$claim = self::get_claim_from_payment_id( $item->id );

			if ( ! empty( $claim ) ) {
				// approve the claim
				GeoDir_Claim_Post::approve_claim( $claim, true );
			}
		}
	}

	public static function claim_form_package_select($post_id){
		$gd_post = geodir_get_post_info( absint( $post_id ) );

		$package_id = ! empty( $gd_post->package_id ) ? absint( $gd_post->package_id ) : 0;

		if ( ! empty( $gd_post ) && empty( $gd_post->claimed ) && $package_id ) {
			$packages = self::get_upgrade_price_packages( $gd_post->post_type, $package_id );

			if ( ! empty( $packages ) ) {
				if ( geodir_design_style() ) {
					echo aui()->select( 
						array(
							'id' => 'gd_claim_user_package',
							'name' => 'gd_claim_user_package',
							'title' => __( 'Select Package', 'geodir-claim' ),
							'required' => true,
							'label_type' => 'vertical',
							'label' => __( 'Select Package', 'geodir-claim' ) . '<span class="text-danger">*</span>',
							'options' => $packages,
							'select2' => true
						)
					);
				} else {
			?>
			<div class="required_field geodir_form_row clearfix geodir-claim-field-comments">
				<label for="gd_claim_user_package"><?php _e('Select Package','geodir-claim');?> <span>*</span></label>
				<select name="gd_claim_user_package" id="gd_claim_user_package" style="" class="regular-text" tabindex="-1" aria-hidden="true">
					<?php
					foreach($packages as $id => $title){
						echo "<option value='$id'>$title</option>";
					}
					?>
				</select>
			</div>
			<?php
				}
			}
		}
	}

	/**
	 * Save the submitted data.
	 *
	 * @param $package_data
	 * @param $data
	 * @param $package
	 *
	 * @return mixed
	 */
	public static function save_pricing_package_data($package_data, $data, $package){
		if ( ! empty( $data['claim_packages'] ) ) {
			$package_data['meta']['claim_packages'] = ( is_array( $data['claim_packages'] ) ? implode( ",", $data['claim_packages'] ) : $data['claim_packages'] );
		} else {
			$package_data['meta']['claim_packages'] = '';
		}

		return $package_data;
	}

	/**
	 * Add our fields to the package edit form data.
	 *
	 * @param $package_data
	 *
	 * @return mixed
	 */
	public static function pricing_package_data($package_data){
		if ( isset( $package_data['claim_packages'] ) ) {
			$package_data['claim_packages'] = ( ! is_array( $package_data['claim_packages'] ) ? explode( ",", $package_data['claim_packages'] ) : $package_data['claim_packages'] );
		} else {
			$package_data['claim_packages'] = array();
		}

		return $package_data;
	}

	/**
	 * Add field inputs to the edit package form.
	 *
	 * @param $settings
	 * @param $package_data
	 *
	 * @return array
	 */
	public static function pricing_package_settings($settings,$package_data){

		$post_type = ! empty( $_REQUEST['post_type'] ) && geodir_is_gd_post_type( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : 'gd_place';

//print_r($package_data);exit;
		// add the pay to claim settings.
		$claim_settings = array(
			array(
				'type' 	=> 'title',
				'id'   	=> 'claim_package_settings',
				'title' => __( 'Claim Listing', 'geodir-claim' ),
				'desc' 	=> '',
			),
			array(
				'type' 		=> 'multiselect',
				'id'       	=> 'package_claim_packages',
				'title'     => __( 'Claim Packages', 'geodir-claim' ),
				'desc' 		=> __( 'Select packages that a user must select to claim the listing, if this is a paid package then they will be able to pay to approve the claim.', 'geodir-claim' ),
				'options'   => self::get_price_pacakges( $post_type ),
				'placeholder' => __( 'Select Package', 'geodir-claim' ),
				'class'		=> 'geodir-select',
				'desc_tip' 	=> true,
				'advanced' 	=> false,
				'value'	   	=> $package_data['claim_packages']
			),
			array(
				'type' => 'sectionend',
				'id' => 'claim_package_settings'
			)
		);
		$settings = array_merge($settings,$claim_settings);


		return $settings;
	}

	public static function get_price_pacakges( $post_type ) {
		$packages = array();
		$_packages = geodir_pricing_get_packages( array( 'post_type' => $post_type ) );

		if ( ! empty( $_packages ) ) {
			foreach ( $_packages as $package ) {
				$packages[ $package->id ] = __( stripslashes( $package->name ), 'geodirectory' );
			}
		}

		return $packages;
	}

	public static function get_upgrade_price_packages( $post_type, $package_id ) {
		$packages = array();
		$claim_packages = geodir_pricing_get_meta( $package_id, 'claim_packages', true );
		$_claim_packages = ! empty( $claim_packages ) ? array_map( 'absint', explode( ',', $claim_packages ) ) : array();

		if ( empty( $_claim_packages ) ) {
			return $packages;
		}

		$args = array( 'post_type' => $post_type, 'group' => 'claim' );

		if ( ! empty( $_REQUEST['p'] ) && get_post_type( absint( $_REQUEST['p'] ) ) == $post_type && geodir_listing_belong_to_current_user( absint( $_REQUEST['p'] ) ) ) {
			$args['current_package'] = geodir_get_post_meta( absint( $_REQUEST['p'] ), 'package_id', true );
		}

		$_packages = geodir_pricing_get_packages( $args );

		if ( ! empty( $_packages ) ) {
			foreach ( $_packages as $package ) {
				if ( in_array( $package->id, $_claim_packages ) ) {
					$title = __( stripslashes( $package->title ), 'geodirectory' );

					// Check private package.
					if ( function_exists( 'geodir_pricing_is_private' ) && geodir_pricing_is_private( $package->id ) ) {
						$show = current_user_can( 'manage_options' );

						if ( ! $show && $package_id && $package_id == $package->id ) {
							$show = true;
						}

						if ( ! $show ) {
							continue;
						}

						$title .= ' ' . __( '(private package)', 'geodir-claim' );
					}

					$packages[ $package->id ] = $title;
				}
			}
		}

		return $packages;
	}

	public static function update_post_data_on_complete_payment( $data, $post_id, $package_id, $post_package_id, $revision_id ) {
		// Post package listing status should not affect claimed post.
		if ( isset( $data['post_status'] ) && $data['post_status'] != get_post_status( $post_id ) && ( $post_package = GeoDir_Pricing_Post_Package::get_item( (int) $post_package_id ) ) ) {
			if ( $post_package->task == 'claim' ) {
				unset( $data['post_status'] );
			}
		}

		return $data;
	}
	
}