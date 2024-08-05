<?php
/**
 * Claim Listings Form class.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Claim_Form class.
 */
class GeoDir_Claim_Form {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_filter( 'ninja_forms_new_form_templates', array( __CLASS__, 'ninja_forms_templates' ), 9, 1 );
		//add_action( 'ninja_forms_after_submission', array( __CLASS__, 'ninja_forms_after_submission' ), 1 ); // see below
		// @todo we need to use this hook and not the above one until this is merged https://git.saturdaydrive.io/_/ninja-forms/ninja-forms/merge_requests/3732
		add_filter('ninja_forms_post_run_action_type_successmessage',array( __CLASS__, 'ninja_forms_submission' ));


		add_filter('geodir_ajax_ninja_forms_override', array( __CLASS__, 'ninja_forms_claim_pending_check' ), 10, 3);
		add_filter( 'geodir_show_ninja_form_widget', array( __CLASS__, 'show_ninja_form_widget' ), 10, 4 );
	}

	/**
	 * Handel NF submissions.
	 * 
	 * @param $data
	 */
	public static function ninja_forms_submission( $data ) {
		if ( ! ( ! empty( $data['settings']['key'] ) && $data['settings']['key'] == 'geodirectory_claim' ) ) {
			return $data;
		}
		$sub_id = isset($data['actions']['save']['sub_id']) ? $data['actions']['save']['sub_id'] : '';
		$form_id = $data['form_id'];
		$fields = $data['fields'];

		$claim_fields = array();
		$extra_fields = array();

		foreach ( $fields as $field_id => $field ) {
			if ( in_array( $field['type'] , array( 'hr', 'submit' ) ) ) {
				continue;
			}

			if ( in_array( $field['key'], array( 'listing_id', 'listing_title', 'name', 'phone', 'position', 'message' ) ) ) {
				$claim_fields[ $field['key'] ] = $field['value'];
			} elseif ( isset( $field['field_key'] ) && in_array( $field['field_key'], array( 'listing_id', 'listing_title', 'name', 'phone', 'position', 'message' ) ) ) {
				$claim_fields[ $field['field_key'] ] = $field['value'];
			} elseif ( $field['type'] == 'geodir_packages' ) {// check for package id
				$claim_fields[ 'package_id' ] = $field['value'];
			} else {
				$extra_fields[ $field['key'] ] = array(
					'label' => ( $field['label'] ? $field['label'] : $field['admin_label'] ),
					'value' => $field['value'],
				);
			}
		}

		$claim_fields = wp_parse_args( $claim_fields, array(
			'listing_id' => '',
			'name' => '',
			'phone' => '',
			'position' => '',
			'message' => '',
		) );

		$listing = ! empty( $claim_fields['listing_id'] ) ? get_post( $claim_fields['listing_id'] ) : NULL;
		if ( empty( $listing ) ) {
			$data[ 'errors' ][ 'form' ][] = __( 'Invalid post id!', 'geodir-claim' );
			return $data;
		}

		$user = wp_get_current_user();

		$claim_data = array(
			'post_id' => $listing->ID,
			'post_type' => $listing->post_type,
			'author_id' => $listing->post_author,
			'user_id' => $user->ID,
			'user_fullname' => $claim_fields['name'],
			'user_number' => $claim_fields['phone'],
			'user_position' => $claim_fields['position'],
			'user_ip' => geodir_get_ip(),
			'user_comments' => $claim_fields['message'],
			'claim_date' => date_i18n( 'Y-m-d', current_time( 'timestamp' ) ),
			'status' => 0,
			'rand_string' => md5( microtime() . wp_rand() . $user->user_email ),
			'meta' => maybe_serialize( array( 'ninja_form_id' => $form_id,'ninja_sub_id' => $sub_id, 'ninja_post' => $listing->ID, 'extra_fields' => $extra_fields ) ),
		);


		//  has package change
		if($packages = self::get_claim_packages($listing->ID)){
			$package_id = !empty($claim_fields[ 'package_id' ]) ? absint($claim_fields[ 'package_id' ]) : '';

			$packages = explode(",",$packages);
			if(!$package_id){ // no package id
				$data[ 'errors' ][ 'form' ][] = __( 'Invalid package id!', 'geodir-claim' );
				return $data;
			}elseif(!in_array($package_id,$packages)){ // invalid package id
				$data[ 'errors' ][ 'form' ][] = __( 'Invalid package id!', 'geodir-claim' );
				return $data;
			}else{// has package change
				$old_package_id = geodir_get_post_meta($listing->ID,'package_id',true);
				$claim_data['old_package_id'] = $old_package_id;
				$claim_data['package_id'] = $package_id;

				// Leave for own listing claim with same package.
				if ( ! ( $old_package_id == $package_id && geodir_listing_belong_to_current_user( $listing->ID ) ) ) {
					$args = array( 
						'post_type' => $listing->post_type,
						'package_id' => $package_id,
						'group' => 'claim'
					);

					$can_claim = GeoDir_Post_Limit::user_can_add_post( $args, true );

					if ( is_wp_error( $can_claim ) ) {
						$data[ 'errors' ][ 'form' ][] = $can_claim->get_error_message();
						return $data;
					}
				}

				// create invoice
				$payment_id = GeoDir_Pricing_Post::create_claim_invoice( $listing->ID, $package_id, $user->ID );
				if ( empty( $payment_id ) ) {
					$data[ 'errors' ][ 'form' ][] = __( 'Could not create invoice for claim request!', 'geodir-claim' );
					return $data;
				}
				$claim_data['payment_id'] = $payment_id;
			}
		} else {
			// Leave for own listing claim.
			if ( ! geodir_listing_belong_to_current_user( $listing->ID ) ) {
				$args = array( 
					'post_type' => $listing->post_type,
					'group' => 'claim'
				);

				if ( defined( 'GEODIR_PRICING_VERSION' ) ) {
					$args['package_id'] = geodir_get_post_meta( $listing->ID, 'package_id', true );
				}

				$can_claim = GeoDir_Post_Limit::user_can_add_post( $args, true );

				if ( is_wp_error( $can_claim ) ) {
					$data[ 'errors' ][ 'form' ][] = $can_claim->get_error_message();
					return $data;
				}
			}
		}

		$claim_id = GeoDir_Claim_Post::save( $claim_data, true );

		// set the claim id as a global so we can grab it later and change the success message if it needs payment.
		$claim = GeoDir_Claim_Post::get_item( $claim_id );

		if ( geodir_get_option( 'claim_auto_approve' ) && empty( $claim->payment_id ) ) {
			$message = __( 'A verification link has been sent to your email address, please click the link in the email to verify your listing claim.', 'geodir-claim' );
		} else {
			$message = __( 'Your request to claim this listing has been sent successfully. You will be notified by email once a decision has been made.', 'geodir-claim' );
		}

		$message = apply_filters( 'geodir_claim_submit_success_message', $message, $claim, $listing->ID );

		$data['actions']['success_message'] .= "<p><b>$message</b></p>";

		return $data;
	}
	
	public static function ninja_forms_claim_pending_check( $html, $post_id = '', $form_id = '' ) {
		if ( $form_id ) {
			$form = Ninja_Forms()->form( $form_id )->get();
			$settings = $form->get_settings();

			if ( isset( $settings['key'] ) && $settings['key'] == 'geodirectory_claim' ) {
				if ( GeoDir_Claim_Post::is_claim_pending( $post_id ) ) {
					$notification = array(
						'gd_claim_notice' => array(
							'type' => 'info',
							'note' => GeoDir_Claim_Post::get_pending_message( $post_id )
						)
					);

					$html .= geodir_notification( $notification );
				} else if ( $can_claim = GeoDir_Claim_Post::can_claim_post( $post_id ) ) {
					if ( is_wp_error( $can_claim ) ) {
						$notification = array(
							'gd_claim_notice' => array(
								'type' => 'info',
								'note' => $can_claim->get_error_message()
							)
						);

						$html .= geodir_notification( $notification );
					}
				}
			}
		}

		return $html;
	}

	public static function ninja_forms_templates( $templates ) {
		$new_templates['formtemplate-geodirectory-claimform'] = array(
			'id' => 'formtemplate-geodirectory-claimform',
			'title' => __( 'GeoDirectory Claim Form', 'geodir-claim' ),
			'template-desc' => __( 'Allow your users to send the business details to admin to claim the listing. You can add and remove fields as needed.', 'geodir-claim' ),
			'form' => self::ninja_forms_claim_template()
		);

		return $new_templates + $templates;
	}

	/**
	 * Ninja forms claim listing Templates.
	 *
	 * @since 2.0.0
	 *
	 * @return string settings templates.
	 */
	public static function ninja_forms_claim_template(){
		return '{
		"settings": {
			"title": "GeoDirectory Claim Form",
			"key": "geodirectory_claim",
			"created_at": "2018-11-05 19:16:00",
			"default_label_pos": "above",
			"conditions": [],
			"objectType": "Form Setting",
			"editActive": "",
			"show_title": "0",
			"clear_complete": "1",
			"hide_complete": "1",
			"wrapper_class": "",
			"element_class": "",
			"add_submit": "1",
			"logged_in": "1",
			"not_logged_in_msg": "You must login to claim, please login first.",
			"sub_limit_number": "",
			"sub_limit_msg": "",
			"calculations": [],
			"formContentData": [{
				"order": "0",
				"cells": [{
					"order": "0",
					"fields": ["name"],
					"width": "100"
				}]
			}, {
				"order": "1",
				"cells": [{
					"order": "0",
					"fields": ["phone"],
					"width": "100"
				}]
			}, {
				"order": "2",
				"cells": [{
					"order": "0",
					"fields": ["position"],
					"width": "100"
				}]
			}, {
				"order": "3",
				"cells": [{
					"order": "0",
					"fields": ["message"],
					"width": "100"
				}]
			}, {
				"order": "4",
				"cells": [{
					"order": "0",
					"fields": ["submit"],
					"width": "100"
				}]
			}],
			"container_styles_background-color": "",
			"container_styles_border": "",
			"container_styles_border-style": "",
			"container_styles_border-color": "",
			"container_styles_color": "",
			"container_styles_height": "",
			"container_styles_width": "",
			"container_styles_font-size": "",
			"container_styles_margin": "",
			"container_styles_padding": "",
			"container_styles_display": "",
			"container_styles_float": "",
			"container_styles_show_advanced_css": "0",
			"container_styles_advanced": "",
			"title_styles_background-color": "",
			"title_styles_border": "",
			"title_styles_border-style": "",
			"title_styles_border-color": "",
			"title_styles_color": "",
			"title_styles_height": "",
			"title_styles_width": "",
			"title_styles_font-size": "",
			"title_styles_margin": "",
			"title_styles_padding": "",
			"title_styles_display": "",
			"title_styles_float": "",
			"title_styles_show_advanced_css": "0",
			"title_styles_advanced": "",
			"row_styles_background-color": "",
			"row_styles_border": "",
			"row_styles_border-style": "",
			"row_styles_border-color": "",
			"row_styles_color": "",
			"row_styles_height": "",
			"row_styles_width": "",
			"row_styles_font-size": "",
			"row_styles_margin": "",
			"row_styles_padding": "",
			"row_styles_display": "",
			"row_styles_show_advanced_css": "0",
			"row_styles_advanced": "",
			"row-odd_styles_background-color": "",
			"row-odd_styles_border": "",
			"row-odd_styles_border-style": "",
			"row-odd_styles_border-color": "",
			"row-odd_styles_color": "",
			"row-odd_styles_height": "",
			"row-odd_styles_width": "",
			"row-odd_styles_font-size": "",
			"row-odd_styles_margin": "",
			"row-odd_styles_padding": "",
			"row-odd_styles_display": "",
			"row-odd_styles_show_advanced_css": "0",
			"row-odd_styles_advanced": "",
			"success-msg_styles_background-color": "",
			"success-msg_styles_border": "",
			"success-msg_styles_border-style": "",
			"success-msg_styles_border-color": "",
			"success-msg_styles_color": "",
			"success-msg_styles_height": "",
			"success-msg_styles_width": "",
			"success-msg_styles_font-size": "",
			"success-msg_styles_margin": "",
			"success-msg_styles_padding": "",
			"success-msg_styles_display": "",
			"success-msg_styles_show_advanced_css": "0",
			"success-msg_styles_advanced": "",
			"error_msg_styles_background-color": "",
			"error_msg_styles_border": "",
			"error_msg_styles_border-style": "",
			"error_msg_styles_border-color": "",
			"error_msg_styles_color": "",
			"error_msg_styles_height": "",
			"error_msg_styles_width": "",
			"error_msg_styles_font-size": "",
			"error_msg_styles_margin": "",
			"error_msg_styles_padding": "",
			"error_msg_styles_display": "",
			"error_msg_styles_show_advanced_css": "0",
			"error_msg_styles_advanced": ""
		},
		"fields": [{
			"objectType": "Field",
			"objectDomain": "fields",
			"editActive": false,
			"order": 1,
			"label": "Listing ID",
			"type": "hidden",
			"key": "listing_id",
			"default": "{wp:post_id}",
			"admin_label": "",
			"drawerDisabled": false
		},{
			"objectType":"Field",
			"objectDomain":"fields",
			"editActive":"",
			"order":"2",
			"label":"User ID",
			"type":"hidden",
			"key":"user_id",
			"default":"{wp:user_id}",
			"admin_label":"",
			"drawerDisabled":""
		},{
			"objectType": "Field",
			"objectDomain": "fields",
			"editActive": false,
			"order": 3,
			"label": "Listing Title",
			"type": "textbox",
			"key": "listing_title",
			"label_pos": "default",
			"required": false,
			"default": "{wp:post_title}",
			"placeholder": "",
			"container_class": "",
			"element_class": "",
			"input_limit": "",
			"input_limit_type": "characters",
			"input_limit_msg": "Character(s) left",
			"manual_key": false,
			"disable_input": 1,
			"admin_label": "",
			"help_text": "",
			"disable_browser_autocomplete": 1,
			"mask": "",
			"custom_mask": "",
			"custom_name_attribute": "",
			"drawerDisabled": false,
			"desc_text": ""
		},{
			"objectType": "Field",
			"objectDomain": "fields",
			"editActive": false,
			"order": 4,
			"label": "Divider",
			"type": "hr",
			"container_class": "",
			"element_class": "",
			"key": "hr",
			"drawerDisabled": false
		},{
			"label": "Full Name",
			"key": "name",
			"parent_id": "1",
			"type": "textbox",
			"created_at": "2016-08-24 16:39:20",
			"label_pos": "above",
			"required": "1",
			"order": 5,
			"placeholder": "",
			"default": "",
			"wrapper_class": "",
			"element_class": "",
			"objectType": "Field",
			"objectDomain": "fields",
			"editActive": "",
			"container_class": "",
			"input_limit": "",
			"input_limit_type": "characters",
			"input_limit_msg": "Character(s) left",
			"manual_key": "",
			"disable_input": "",
			"admin_label": "",
			"help_text": "",
			"desc_text": "",
			"disable_browser_autocomplete": "",
			"mask": "",
			"custom_mask": "",
			"wrap_styles_background-color": "",
			"wrap_styles_border": "",
			"wrap_styles_border-style": "",
			"wrap_styles_border-color": "",
			"wrap_styles_color": "",
			"wrap_styles_height": "",
			"wrap_styles_width": "",
			"wrap_styles_font-size": "",
			"wrap_styles_margin": "",
			"wrap_styles_padding": "",
			"wrap_styles_display": "",
			"wrap_styles_float": "",
			"wrap_styles_show_advanced_css": "0",
			"wrap_styles_advanced": "",
			"label_styles_background-color": "",
			"label_styles_border": "",
			"label_styles_border-style": "",
			"label_styles_border-color": "",
			"label_styles_color": "",
			"label_styles_height": "",
			"label_styles_width": "",
			"label_styles_font-size": "",
			"label_styles_margin": "",
			"label_styles_padding": "",
			"label_styles_display": "",
			"label_styles_float": "",
			"label_styles_show_advanced_css": "0",
			"label_styles_advanced": "",
			"element_styles_background-color": "",
			"element_styles_border": "",
			"element_styles_border-style": "",
			"element_styles_border-color": "",
			"element_styles_color": "",
			"element_styles_height": "",
			"element_styles_width": "",
			"element_styles_font-size": "",
			"element_styles_margin": "",
			"element_styles_padding": "",
			"element_styles_display": "",
			"element_styles_float": "",
			"element_styles_show_advanced_css": "0",
			"element_styles_advanced": "",
			"cellcid": "c3277"
		},{
			"objectType": "Field",
			"objectDomain": "fields",
			"editActive": false,
			"order": 6,
			"label": "Phone",
			"type": "phone",
			"key": "phone",
			"label_pos": "default",
			"required": true,
			"default": "",
			"placeholder": "",
			"container_class": "",
			"element_class": "",
			"input_limit": "",
			"input_limit_type": "characters",
			"input_limit_msg": "Character(s) left",
			"manual_key": false,
			"admin_label": "",
			"help_text": "",
			"mask": "",
			"custom_mask": "",
			"custom_name_attribute": "phone",
			"drawerDisabled": false
		},{
			"label": "Position in Business",
			"key": "position",
			"type": "textbox",
			"label_pos": "above",
			"required": "1",
			"order": "7",
			"placeholder": "",
			"default": "",
			"wrapper_class": "",
			"element_class": "",
			"objectType": "Field",
			"objectDomain": "fields",
			"editActive": "",
			"container_class": "",
			"input_limit": "",
			"input_limit_type": "characters",
			"input_limit_msg": "Character(s) left",
			"manual_key": "",
			"disable_input": "",
			"admin_label": "",
			"help_text": "",
			"desc_text": "",
			"disable_browser_autocomplete": "",
			"mask": "",
			"custom_mask": "",
			"wrap_styles_background-color": "",
			"wrap_styles_border": "",
			"wrap_styles_border-style": "",
			"wrap_styles_border-color": "",
			"wrap_styles_color": "",
			"wrap_styles_height": "",
			"wrap_styles_width": "",
			"wrap_styles_font-size": "",
			"wrap_styles_margin": "",
			"wrap_styles_padding": "",
			"wrap_styles_display": "",
			"wrap_styles_float": "",
			"wrap_styles_show_advanced_css": "0",
			"wrap_styles_advanced": "",
			"label_styles_background-color": "",
			"label_styles_border": "",
			"label_styles_border-style": "",
			"label_styles_border-color": "",
			"label_styles_color": "",
			"label_styles_height": "",
			"label_styles_width": "",
			"label_styles_font-size": "",
			"label_styles_margin": "",
			"label_styles_padding": "",
			"label_styles_display": "",
			"label_styles_float": "",
			"label_styles_show_advanced_css": "0",
			"label_styles_advanced": "",
			"element_styles_background-color": "",
			"element_styles_border": "",
			"element_styles_border-style": "",
			"element_styles_border-color": "",
			"element_styles_color": "",
			"element_styles_height": "",
			"element_styles_width": "",
			"element_styles_font-size": "",
			"element_styles_margin": "",
			"element_styles_padding": "",
			"element_styles_display": "",
			"element_styles_float": "",
			"element_styles_show_advanced_css": "0",
			"element_styles_advanced": "",
			"cellcid": "c3277"
		}, {
			"label": "Message",
			"key": "message",
			"parent_id": "1",
			"type": "textarea",
			"created_at": "2016-08-24 16:39:20",
			"label_pos": "above",
			"required": "1",
			"order": "8",
			"placeholder": "",
			"default": "Hi I am the owner of this business and i would like to claim it.",
			"wrapper_class": "",
			"element_class": "",
			"objectType": "Field",
			"objectDomain": "fields",
			"editActive": "",
			"container_class": "",
			"input_limit": "",
			"input_limit_type": "characters",
			"input_limit_msg": "Character(s) left",
			"manual_key": "",
			"disable_input": "",
			"admin_label": "",
			"help_text": "",
			"desc_text": "",
			"disable_browser_autocomplete": "",
			"textarea_rte": "",
			"disable_rte_mobile": "",
			"textarea_media": "",
			"wrap_styles_background-color": "",
			"wrap_styles_border": "",
			"wrap_styles_border-style": "",
			"wrap_styles_border-color": "",
			"wrap_styles_color": "",
			"wrap_styles_height": "",
			"wrap_styles_width": "",
			"wrap_styles_font-size": "",
			"wrap_styles_margin": "",
			"wrap_styles_padding": "",
			"wrap_styles_display": "",
			"wrap_styles_float": "",
			"wrap_styles_show_advanced_css": "0",
			"wrap_styles_advanced": "",
			"label_styles_background-color": "",
			"label_styles_border": "",
			"label_styles_border-style": "",
			"label_styles_border-color": "",
			"label_styles_color": "",
			"label_styles_height": "",
			"label_styles_width": "",
			"label_styles_font-size": "",
			"label_styles_margin": "",
			"label_styles_padding": "",
			"label_styles_display": "",
			"label_styles_float": "",
			"label_styles_show_advanced_css": "0",
			"label_styles_advanced": "",
			"element_styles_background-color": "",
			"element_styles_border": "",
			"element_styles_border-style": "",
			"element_styles_border-color": "",
			"element_styles_color": "",
			"element_styles_height": "",
			"element_styles_width": "",
			"element_styles_font-size": "",
			"element_styles_margin": "",
			"element_styles_padding": "",
			"element_styles_display": "",
			"element_styles_float": "",
			"element_styles_show_advanced_css": "0",
			"element_styles_advanced": "",
			"cellcid": "c3284"
		}, {
			"label": "Send",
			"key": "submit",
			"parent_id": "1",
			"type": "submit",
			"created_at": "2016-08-24 16:39:20",
			"processing_label": "Processing",
			"order": "9",
			"objectType": "Field",
			"objectDomain": "fields",
			"editActive": "",
			"container_class": "",
			"element_class": "",
			"wrap_styles_background-color": "",
			"wrap_styles_border": "",
			"wrap_styles_border-style": "",
			"wrap_styles_border-color": "",
			"wrap_styles_color": "",
			"wrap_styles_height": "",
			"wrap_styles_width": "",
			"wrap_styles_font-size": "",
			"wrap_styles_margin": "",
			"wrap_styles_padding": "",
			"wrap_styles_display": "",
			"wrap_styles_float": "",
			"wrap_styles_show_advanced_css": "0",
			"wrap_styles_advanced": "",
			"label_styles_background-color": "",
			"label_styles_border": "",
			"label_styles_border-style": "",
			"label_styles_border-color": "",
			"label_styles_color": "",
			"label_styles_height": "",
			"label_styles_width": "",
			"label_styles_font-size": "",
			"label_styles_margin": "",
			"label_styles_padding": "",
			"label_styles_display": "",
			"label_styles_float": "",
			"label_styles_show_advanced_css": "0",
			"label_styles_advanced": "",
			"element_styles_background-color": "",
			"element_styles_border": "",
			"element_styles_border-style": "",
			"element_styles_border-color": "",
			"element_styles_color": "",
			"element_styles_height": "",
			"element_styles_width": "",
			"element_styles_font-size": "",
			"element_styles_margin": "",
			"element_styles_padding": "",
			"element_styles_display": "",
			"element_styles_float": "",
			"element_styles_show_advanced_css": "0",
			"element_styles_advanced": "",
			"submit_element_hover_styles_background-color": "",
			"submit_element_hover_styles_border": "",
			"submit_element_hover_styles_border-style": "",
			"submit_element_hover_styles_border-color": "",
			"submit_element_hover_styles_color": "",
			"submit_element_hover_styles_height": "",
			"submit_element_hover_styles_width": "",
			"submit_element_hover_styles_font-size": "",
			"submit_element_hover_styles_margin": "",
			"submit_element_hover_styles_padding": "",
			"submit_element_hover_styles_display": "",
			"submit_element_hover_styles_float": "",
			"submit_element_hover_styles_show_advanced_css": "0",
			"submit_element_hover_styles_advanced": "",
			"cellcid": "c3287"
		}],
		"actions": [{
			"title": "",
			"key": "",
			"type": "save",
			"active": "1",
			"created_at": "2016-08-24 16:39:20",
			"label": "Store Submission",
			"objectType": "Action",
			"objectDomain": "actions",
			"editActive": "",
			"conditions": {
				"collapsed": "",
				"process": "1",
				"connector": "all",
				"when": [{
					"connector": "AND",
					"key": "",
					"comparator": "",
					"value": "",
					"type": "field",
					"modelType": "when"
				}],
				"then": [{
					"key": "",
					"trigger": "",
					"value": "",
					"type": "field",
					"modelType": "then"
				}],
				"else": []
			},
			"payment_gateways": "",
			"payment_total": "",
			"tag": "",
			"to": "",
			"email_subject": "",
			"email_message": "",
			"from_name": "",
			"from_address": "",
			"reply_to": "",
			"email_format": "html",
			"cc": "",
			"bcc": "",
			"attach_csv": "",
			"redirect_url": "",
			"email_message_plain": ""
		}, {
			"title": "",
			"key": "",
			"type": "email",
			"active": "1",
			"created_at": "2016-08-24 16:39:20",
			"label": "Email Confirmation",
			"to": "{user:email}",
			"subject": "This is an email action.",
			"message": "Hello, Ninja Forms!",
			"objectType": "Action",
			"objectDomain": "actions",
			"editActive": "",
			"conditions": {
				"collapsed": "",
				"process": "1",
				"connector": "all",
				"when": [],
				"then": [{
					"key": "",
					"trigger": "",
					"value": "",
					"type": "field",
					"modelType": "then"
				}],
				"else": []
			},
			"payment_gateways": "",
			"payment_total": "",
			"tag": "",
			"email_subject": "Submission Confirmation ",
			"email_message": "<p>{all_fields_table}<br><\/p>",
			"from_name": "",
			"from_address": "",
			"reply_to": "",
			"email_format": "html",
			"cc": "",
			"bcc": "",
			"attach_csv": "",
			"email_message_plain": ""
		}, {
			"title": "",
			"key": "",
			"type": "email",
			"active": "1",
			"created_at": "2016-08-24 16:47:39",
			"objectType": "Action",
			"objectDomain": "actions",
			"editActive": "",
			"label": "Email Notification",
			"conditions": {
				"collapsed": "",
				"process": "1",
				"connector": "all",
				"when": [{
					"connector": "AND",
					"key": "",
					"comparator": "",
					"value": "",
					"type": "field",
					"modelType": "when"
				}],
				"then": [{
					"key": "",
					"trigger": "",
					"value": "",
					"type": "field",
					"modelType": "then"
				}],
				"else": []
			},
			"payment_gateways": "",
			"payment_total": "",
			"tag": "",
			"to": "{system:admin_email}",
			"email_subject": "[{wp:site_title}] Claim listing request",
			"email_message": "<p>{field:message}<\/p><p>-{field:name} ( {user:email} )<\/p>",
			"from_name": "",
			"from_address": "",
			"reply_to": "{user:email}",
			"email_format": "html",
			"cc": "",
			"bcc": "",
			"attach_csv": "0",
			"email_message_plain": ""
		}, {
			"title": "",
			"key": "",
			"type": "successmessage",
			"active": "1",
			"created_at": "2016-08-24 16:39:20",
			"label": "Success Message",
			"message": "Thank you {field:name} your claim request has been sent to the admin!",
			"objectType": "Action",
			"objectDomain": "actions",
			"editActive": "",
			"conditions": {
				"collapsed": "",
				"process": "1",
				"connector": "all",
				"when": [{
					"connector": "AND",
					"key": "",
					"comparator": "",
					"value": "",
					"type": "field",
					"modelType": "when"
				}],
				"then": [{
					"key": "",
					"trigger": "",
					"value": "",
					"type": "field",
					"modelType": "then"
				}],
				"else": []
			},
			"payment_gateways": "",
			"payment_total": "",
			"tag": "",
			"to": "",
			"email_subject": "",
			"email_message": "",
			"from_name": "",
			"from_address": "",
			"reply_to": "",
			"email_format": "html",
			"cc": "",
			"bcc": "",
			"attach_csv": "",
			"redirect_url": "",
			"success_msg": "<p>Claim request submitted successfully.<\/p>",
			"email_message_plain": ""
		}]
	}';
	}

	public static function ninja_forms_after_submission( $data ) {

//		print_r($data);exit;
		if ( ! ( ! empty( $data['settings']['key'] ) && $data['settings']['key'] == 'geodirectory_claim' ) ) {
			return;
		}
		$sub_id = isset($data['actions']['save']['sub_id']) ? $data['actions']['save']['sub_id'] : '';
		$form_id = $data['form_id'];
		$fields = $data['fields'];

		$claim_fields = array();
		$extra_fields = array();

		foreach ( $fields as $field_id => $field ) {
			if ( in_array( $field['type'] , array( 'hr', 'submit' ) ) ) {
				continue;
			}

			if ( in_array( $field['key'], array( 'listing_id', 'listing_title', 'name', 'phone', 'position', 'message' ) ) ) {
				$claim_fields[ $field['key'] ] = $field['value'];
			} elseif($field['type']=='geodir_packages'){// check for package id
				$claim_fields[ 'package_id' ] = $field['value'];
			}else {
				$extra_fields[ $field['key'] ] = array(
					'label' => ( $field['label'] ? $field['label'] : $field['admin_label'] ),
					'value' => $field['value'],
				);
			}
		}

		$claim_fields = wp_parse_args( $claim_fields, array(
			'listing_id' => '',
			'name' => '',
			'phone' => '',
			'position' => '',
			'message' => '',
		) );

		$listing = ! empty( $claim_fields['listing_id'] ) ? get_post( $claim_fields['listing_id'] ) : NULL;
		if ( empty( $listing ) ) {
			return;
		}

		$user = wp_get_current_user();

		$claim_data = array(
			'post_id' => $listing->ID,
			'post_type' => $listing->post_type,
			'author_id' => $listing->post_author,
			'user_id' => $user->ID,
			'user_fullname' => $claim_fields['name'],
			'user_number' => $claim_fields['phone'],
			'user_position' => $claim_fields['position'],
			'user_ip' => geodir_get_ip(),
			'user_comments' => $claim_fields['message'],
			'claim_date' => date_i18n( 'Y-m-d', current_time( 'timestamp' ) ),
			'status' => 0,
			'rand_string' => md5( microtime() . wp_rand() . $user->user_email ),
			'meta' => maybe_serialize( array( 'ninja_form_id' => $form_id,'ninja_sub_id' => $sub_id, 'ninja_post' => $listing->ID, 'extra_fields' => $extra_fields ) ),
		);


		//  has package change
		if($packages = self::get_claim_packages($listing->ID)){
			$package_id = !empty($claim_fields[ 'package_id' ]) ? absint($claim_fields[ 'package_id' ]) : '';

			$packages = explode(",",$packages);
			if(!$package_id){ // no package id
				return new WP_Error( 'geodir-claim-package-required', __( 'Invalid package id!', 'geodir-claim' ) );
			}elseif(!in_array($package_id,$packages)){ // invalid package id
				return new WP_Error( 'geodir-claim-package-required', __( 'Invalid package id!', 'geodir-claim' ) );
			}else{// has package change
				$old_package_id = geodir_get_post_meta($listing->ID,'package_id',true);
				$claim_data['old_package_id'] = $old_package_id;
				$claim_data['package_id'] = $package_id;

				// Leave for own listing claim with same package.
				if ( ! ( $old_package_id == $package_id && geodir_listing_belong_to_current_user( $listing->ID ) ) ) {
					$args = array( 
						'post_type' => $listing->post_type,
						'package_id' => $package_id,
						'group' => 'claim'
					);

					$can_claim = GeoDir_Post_Limit::user_can_add_post( $args, true );

					if ( is_wp_error( $can_claim ) ) {
						return $can_claim;
					}
				}

				// create invoice
				$payment_id = GeoDir_Pricing_Post::create_claim_invoice( $listing->ID, $package_id, $user->ID );
				if ( empty( $payment_id ) ) {
					return new WP_Error( 'geodir-claim-empty-field', __( 'Could not create invoice for claim request!', 'geodir-claim' ) );
				}
				$claim_data['payment_id'] = $payment_id;
			}
		} else {
			// Leave for own listing claim.
			if ( ! geodir_listing_belong_to_current_user( $listing->ID ) ) {
				$args = array( 
					'post_type' => $listing->post_type,
					'group' => 'claim'
				);

				if ( defined( 'GEODIR_PRICING_VERSION' ) ) {
					$args['package_id'] = geodir_get_post_meta( $listing->ID, 'package_id', true );
				}

				$can_claim = GeoDir_Post_Limit::user_can_add_post( $args, true );

				if ( is_wp_error( $can_claim ) ) {
					return $can_claim;
				}
			}
		}

		$payment_id = GeoDir_Claim_Post::save( $claim_data, true );

		// set the claim id as a global so we can grab it later and change the success message if it needs payment.
		global $geodir_claim_submission_id;
		$geodir_claim_submission_id = $payment_id;

		return $payment_id;
	}

	public static function get_claim_packages( $post_id ) {
		$claim_packages = '';

		if ( ! function_exists( 'geodir_pricing_get_meta' ) ) {
			return $claim_packages;
		}

		if ( $package_id = (int) geodir_get_post_meta( $post_id, 'package_id' ) ) {
			$claim_packages = geodir_pricing_get_meta( $package_id, 'claim_packages', true );
		}

		return $claim_packages;
	}

	public static function handle_claim_submit( $request ) {
		$post_id = ! empty( $request['post_id'] ) ? absint( $request['post_id'] ) : 0;
		$user_fullname = isset( $request['gd_claim_user_fullname'] ) ? geodir_clean( $request['gd_claim_user_fullname'] ) : '';
		$user_number = isset( $request['gd_claim_user_number'] ) ? geodir_clean( $request['gd_claim_user_number'] ) : '';
		$user_position = isset( $request['gd_claim_user_position'] ) ? geodir_clean( $request['gd_claim_user_position'] ) : '';
		$user_comments = isset( $request['gd_claim_user_comments'] ) ? geodir_clean( $request['gd_claim_user_comments'] ) : '';
		$extra_fields = isset( $request['gd_claim_extra_fields'] ) && is_array( $request['gd_claim_extra_fields'] ) ? geodir_clean( $request['gd_claim_extra_fields'] ) : array();

		if ( empty( $user_fullname ) ) {
			return new WP_Error( 'geodir-claim-empty-field', __( 'Invalid user full name!', 'geodir-claim' ) );
		}

		if ( empty( $user_number ) ) {
			return new WP_Error( 'geodir-claim-empty-field', __( 'Invalid user phone number!', 'geodir-claim' ) );
		}

		if ( empty( $user_position ) ) {
			return new WP_Error( 'geodir-claim-empty-field', __( 'Invalid user position value!', 'geodir-claim' ) );
		}

		if ( empty( $user_comments ) ) {
			return new WP_Error( 'geodir-claim-empty-field', __( 'Invalid comments!', 'geodir-claim' ) );
		}

		$post = $post_id ? get_post( $post_id ) : NULL;
		if ( empty( $post ) ) {
			return new WP_Error( 'geodir-claim-empty-field', __( 'Invalid post id!', 'geodir-claim' ) );
		}

		$user = wp_get_current_user();

		$claim_data = array(
			'post_id' => $post->ID,
			'post_type' => $post->post_type,
			'author_id' => $post->post_author,
			'user_id' => $user->ID,
			'user_fullname' => $user_fullname,
			'user_number' => $user_number,
			'user_position' => $user_position,
			'user_ip' => geodir_get_ip(),
			'user_comments' => $user_comments,
			'claim_date' => date_i18n( 'Y-m-d', current_time( 'timestamp' ) ),
			'status' => 0,
			'rand_string' => md5( microtime() . wp_rand() . $user->user_email ),
			'meta' => maybe_serialize( array( 'extra_fields' => $extra_fields ) ),
		);

		//  has package change
		if ( $packages = self::get_claim_packages( $post_id ) ) {
			$package_id = isset( $request['gd_claim_user_package'] )  ? absint( $request['gd_claim_user_package'] ) : '';

			$packages = explode(",",$packages);
			if ( ! $package_id ) { // no package id
				return new WP_Error( 'geodir-claim-package-required', __( 'Invalid package id!', 'geodir-claim' ) );
			} elseif( ! in_array( $package_id,$packages ) ) { // invalid package id
				return new WP_Error( 'geodir-claim-package-required', __( 'Invalid package id!', 'geodir-claim' ) );
			} else {// has package change
				$old_package_id = geodir_get_post_meta( $post_id, 'package_id', true );
				$claim_data['old_package_id'] = $old_package_id;
				$claim_data['package_id'] = $package_id;

				// Leave for own listing claim with same package.
				if ( ! ( $old_package_id == $package_id && geodir_listing_belong_to_current_user( $post_id ) ) ) {
					$args = array( 
						'post_type' => $post->post_type,
						'package_id' => $package_id,
						'group' => 'claim'
					);

					$can_claim = GeoDir_Post_Limit::user_can_add_post( $args, true );

					if ( is_wp_error( $can_claim ) ) {
						return $can_claim;
					}
				}

				// create invoice
				$payment_id = GeoDir_Pricing_Post::create_claim_invoice( $post_id, $package_id, $user->ID );
				if ( empty( $payment_id ) ) {
					return new WP_Error( 'geodir-claim-empty-field', __( 'Could not create invoice for claim request!', 'geodir-claim' ) );
				}

				$claim_data['payment_id'] = $payment_id;
			}
		} else {
			// Leave for own listing claim.
			if ( ! geodir_listing_belong_to_current_user( $post_id ) ) {
				$args = array( 
					'post_type' => $post->post_type,
					'group' => 'claim'
				);

				if ( defined( 'GEODIR_PRICING_VERSION' ) ) {
					$args['package_id'] = geodir_get_post_meta( $post_id, 'package_id', true );
				}

				$can_claim = GeoDir_Post_Limit::user_can_add_post( $args, true );

				if ( is_wp_error( $can_claim ) ) {
					return $can_claim;
				}
			}
		}

		return GeoDir_Claim_Post::save( $claim_data, true );
	}

	/**
	 * Check whether to show or not the widget output.
	 *
	 * @param bool $show Whether to show or not widget output.
	 * @param object $post The post object.
	 * @param array $instance Widget arguments.
	 * @param object $widget Widget object.
	 *
	 * @return bool True to show, False to hide.
	 */
	public static function show_ninja_form_widget( $show, $post, $instance, $widget ) {
		if ( $show && ! empty( $instance ) && ! empty( $instance['form_id'] ) && ! empty( $widget->id_base ) && $widget->id_base == 'gd_ninja_forms' ) {
			if ( class_exists( 'Ninja_Forms' ) && ( $form = Ninja_Forms()->form( $instance['form_id'] )->get() ) ) {
				$form_key = $form->get_setting( 'key' );

				if ( $form_key == 'geodirectory_claim' ) {
					if ( ! ( ! empty( $post ) && geodir_claim_show_claim_link( $post->ID ) ) ) {
						$show = false; // Don't display link to claim post.
					}
				}
			} else {
				$show = false; // Don't display if ninja form not found.
			}
		}

		return $show;
	}
	
}