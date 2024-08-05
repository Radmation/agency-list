<?php
/**
 * Pricing Manager Post Type Class.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Post class.
 */
class GeoDir_Pricing_Post {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'geodir_register_post_statuses', array( __CLASS__, 'register_post_status' ), 10, 1 );
		add_action( 'geodir_listing_custom_statuses', array( __CLASS__, 'set_custom_status' ), 10, 1 );

		if ( is_admin() ) {
			add_filter( 'display_post_states',array( __CLASS__, 'display_post_states' ), 10, 2 );
		} else {
			add_filter( 'pre_get_posts', array( __CLASS__, 'show_public_preview' ) );
			add_filter( 'posts_results', array( __CLASS__, 'set_expired_status' ), 999, 2 );
			add_filter( 'the_posts', array( __CLASS__, 'reset_expired_status' ), 999, 2 );
			add_filter( 'geodir_notifications', array( __CLASS__, 'maybe_show_expired_notification' ), 999, 2 );
			add_filter( 'geodir_widget_after_detail_user_actions', 'geodir_pricing_detail_author_actions', 10 );
			add_filter( 'geodir_add_listing_form_start', array( __CLASS__, 'add_listing_hidden_fields' ), 20, 3 );
		}

		// Handle save post data
		//add_filter( 'geodir_ajax_post_auto_saved', array( __CLASS__, 'ajax_post_auto_saved' ), 10, 2 );
		//add_filter( 'geodir_auto_post_save_message', array( __CLASS__, 'auto_post_save_message' ), 10, 3 );
		add_filter( 'geodir_validate_auto_save_post_data',array( __CLASS__, 'validate_auto_save_post_data' ), 10, 4 );
		add_filter( 'geodir_auto_save_post_data', array( __CLASS__, 'auto_save_post_data' ), 10, 1 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'wp_insert_post_data' ), 9, 2 );
		add_filter( 'geodir_save_post_temp_data', array( __CLASS__, 'temp_gd_post' ), 10, 3 );
		add_filter( 'geodir_validate_ajax_save_post_data',array( __CLASS__, 'validate_ajax_save_post_data' ), 10, 3 );
		add_filter( 'geodir_save_post_data', array( __CLASS__, 'save_post_data' ), 100, 4 );
		add_filter( 'geodir_post_output_user_notes', array( __CLASS__, 'post_output_user_notes' ), 10, 1 );

		// Field input
		add_action( 'geodir_pricing_complete_package_post_updated', array( __CLASS__, 'send_notifications' ), 10, 3 );
		add_filter( 'geodir_custom_field_input_text_package_id', array( __CLASS__, 'package_id_input' ), 10, 2 );

		add_filter( 'geodir_custom_field_output_pricing', array( __CLASS__, 'pricing_field_output' ), 10, 3 );
		add_filter( 'geodir_custom_field_output_pricing_var_package_id', array( __CLASS__, 'package_id_output' ), 10, 4 );
		add_filter( 'geodir_custom_field_output_pricing_var_expire_date', array( __CLASS__, 'expire_date_output' ), 10, 4 );
		add_filter( 'geodir_pricing_onchange_package_redirect_to', array( __CLASS__, 'onchange_package_redirect_to' ), 10, 5 );
		add_filter( 'geodir_post_custom_fields_skip_field', array( __CLASS__, 'skip_post_custom_field' ), 10, 5 );
		add_filter( 'geodir_get_cf_value', array( __CLASS__, 'cf_value' ), 10, 2 );
		add_filter( 'geodir_custom_field_file_limit', array( __CLASS__, 'set_file_limit' ), 10, 3 );
		add_filter( 'geodir_rest_markers_query_where', array( __CLASS__, 'rest_markers_query_where' ), 10, 2 );
		add_filter( 'geodir_custom_field_allow_html_editor', array( __CLASS__, 'cfi_allow_html_editor' ), 20, 2 );
		add_filter( 'geodir_custom_field_output_text_var_package_id', array( __CLASS__, 'output_package_id' ), 10, 4 );
		add_filter( 'geodir_post_badge_match_value', array( __CLASS__, 'post_badge_match_value' ), 20, 5 );
		add_filter( 'geodir_user_can_reply_review', array( __CLASS__, 'user_can_reply_review' ), 10, 2 );

		add_filter( 'geodir_cfa_can_delete_field', array( __CLASS__, 'prevent_field_delete' ), 10, 2 );
		
		// post save override
		add_filter( 'geodir_ajax_save_post_override', array( __CLASS__, 'ajax_save_post_override' ), 10, 2 );
		add_filter( 'geodir_ajax_save_post_message', array( __CLASS__, 'ajax_save_post_message' ), 10, 2 );

		// Claim Listings
		// claim submit override
		add_filter( 'geodir_claim_submit_success_message', array( __CLASS__, 'claim_submit_success_message' ), 11, 3 );
		add_action( 'geodir_claim_pending_message', array( __CLASS__, 'claim_pending_message' ), 10, 3 );

		// author actions filters
		add_filter( 'geodir_author_actions', array( __CLASS__, 'author_actions' ), 11, 2 );
		//add_filter('geodir_post_status_author_page',array( __CLASS__, 'post_status_author_page' ), 10, 3);
		add_filter('geodir_filter_status_array_on_author_page', array( __CLASS__, 'status_array_on_author_page' ), 10, 1 );
		add_filter('geodir_filter_status_text_on_author_page', array( __CLASS__, 'status_text_on_author_page' ), 10, 1 );
		
		// GD BuddyPress listings set custom status.
		add_filter( 'geodir_bp_listings_where', array( __CLASS__, 'bp_listings_where' ), 11, 2 );

		// Frontend Analytics
		add_filter( 'frontend_analytics_check_post_google_analytics', array( __CLASS__, 'frontend_analytics_check_post_google_analytics' ), 20, 2 );
		add_filter( 'comments_open', array(__CLASS__,'comments_open'), 21, 2 );
	}

	/**
	 * Show expired notification on single listing page.
	 * 
	 * @param $notifications
	 *
	 * @return mixed
	 */
	public static function maybe_show_expired_notification($notifications){
		global $post,$gd_expired,$preview;

		if(geodir_is_page('single') && isset($post->ID) && isset($gd_expired) && $post->ID==$gd_expired && !$preview){
			if ( ! empty( $post ) && ! empty( $post->post_type ) ) {
				$cpt_name = geodir_strtolower( geodir_post_type_singular_name( $post->post_type ) );
			} else {
				$cpt_name = __( 'business', 'geodirectory' );
			}

			$current_user = get_current_user_id();

			if( $current_user && $current_user==$post->post_author){
				$renew_url = geodir_pricing_post_renew_url( $post->ID );
				$notifications['post_is_closed'] = array(
					'type' => 'error',
					'note' => wp_sprintf( __( 'Your %s has expired and may be removed soon, please %srenew now.%s', 'geodir_pricing' ), $cpt_name,"<a href='$renew_url'>","</a>" )
				);
			}else{
				$notifications['post_is_closed'] = array(
					'type' => 'error',
					'note' => wp_sprintf( __( 'This %s appears to have expired and may be removed soon.', 'geodir_pricing' ), $cpt_name )
				);
			}

		}

		return $notifications;
	}

	public static function status_array_on_author_page( $status_parts ) {
		global $wpdb, $geodir_pricing_manager, $gd_post;

		if ( ! empty( $status_parts ) && ! empty( $gd_post ) && ! empty( $gd_post->post_author ) && $gd_post->post_author == get_current_user_id() ) {
			$post_id = $gd_post->ID;
			$real_status = $wpdb->get_var( "SELECT post_status from {$wpdb->posts} WHERE ID = {$post_id}" );

			if ( $real_status == 'pending' ) {
				$has_invoice = $geodir_pricing_manager->cart->has_invoice( $post_id, 'new' );

				if ( ! empty( $has_invoice ) ) {
					$editlink = geodir_edit_post_link( $post_id );
					$status_parts['title'] = __( 'Pending Payment', 'geodir_pricing' );
					$status_parts['url'] = $editlink;
					$status_parts['icon'] = 'fas fa-shopping-cart';
				}
			} elseif ( $real_status == 'gd-expired' ) {
				$revision_id = 0;
				$post_revisions = wp_get_post_revisions( $post_id, array( 'check_enabled' => false,'author' => get_current_user_id() ) );

				if ( ! empty( $post_revisions ) ) {
					$revision = reset( $post_revisions );
					$revision_id = absint( $revision->ID );
				}

				$has_invoice = $geodir_pricing_manager->cart->has_invoice( $revision_id );

				$editlink = geodir_edit_post_link( $post_id );
				if ( ! empty( $has_invoice ) ) {
					$status_parts['title'] = __( 'Pending Payment', 'geodir_pricing' );
					$status_parts['url'] = $editlink;
					$status_parts['icon'] = 'fas fa-shopping-cart';
				} else {
					$status_parts['title'] = __( 'Expired', 'geodir_pricing' );
					$status_parts['url'] = geodir_pricing_post_renew_link( $post_id ) ? geodir_pricing_post_renew_url( $post_id ) : $editlink;
				}
			}
		}

		return $status_parts;
	}

	/**
	 * UsersWP post status on author page for legacy mode.
	 *
	 * @since 2.6.0.5
	 *
	 * @param string $html Post status text.
	 * @return string Post status text.
	 */
	public static function status_text_on_author_page( $html ) {
		$_status = GeoDir_Widget_Author_Actions::post_status_author_page();

		if ( ! empty( $_status ) && ! empty( $_status['title'] ) ) {
			$icon = ! empty( $_status['icon'] ) ? $_status['icon'] : 'fas fa-play';

			$html = '<span class="geodir-post-status"><i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i> <span class="geodir-status-label">' . __( 'Status:', 'geodirectory' ) . ' </span>';
			if ( ! empty( $_status['url'] ) ) {
				$html .= '<a href="' . esc_url( $_status['url'] ) . '">';
			}
			$html .= $_status['title'];
			if ( ! empty( $_status['url'] ) ) {
				$html .= '</a>';
			}
			$html .= '</span>';
		}

		return $html;
	}

	public static function post_status_author_page( $status, $real_status, $post_id ) {
		global $geodir_pricing_manager;

		// If pending check if payment is required
		if ( $real_status == 'pending' ) {
			$has_invoice = $geodir_pricing_manager->cart->has_invoice( $post_id, 'new' );

			if ( ! empty( $has_invoice ) ) {
				$editlink = geodir_edit_post_link( $post_id );
				$status = __( 'Pending Payment', 'geodir_pricing' );
			}
		} elseif ( $real_status == 'gd-expired' ) {
			$revision_id = 0;
			$post_revisions = wp_get_post_revisions( $post_id, array( 'check_enabled' => false,'author' => get_current_user_id() ) );

			if ( ! empty( $post_revisions ) ) {
				$revision = reset( $post_revisions );
				$revision_id = absint( $revision->ID );
			}

			$has_invoice = $geodir_pricing_manager->cart->has_invoice( $revision_id );

			$editlink = geodir_edit_post_link( $post_id );
			if ( ! empty( $has_invoice ) ) {
				$editlink = geodir_edit_post_link( $post_id );
				$status = __( 'Pending Payment', 'geodir_pricing' );
			} else {
				$status = __( 'Expired', 'geodir_pricing' );
			}
		}
		
		return $status;
	}

//	public static function post_payment_status($post_id,$real_status){
//		global $geodir_pricing_manager;
//		$payment_status = 'pending-payment';
//
//		// check if there is a revision by the user
//		$revision_id = 0;
//		$revision_has_invoice = new stdClass();
//		$post_revisions = wp_get_post_revisions( $post_id, array( 'check_enabled' => false,'author' => get_current_user_id() ) );
//		if(!empty($post_revisions)){
//			$revision = reset( $post_revisions );
//			$revision_id = absint( $revision->ID );
//		}
//
//		$has_invoice = $geodir_pricing_manager->cart->has_invoice($post_id);
//		if($revision_id){
//			$revision_has_invoice = $geodir_pricing_manager->cart->has_invoice($revision_id);
//		}
//
//		print_r($has_invoice);
//		print_r($revision_has_invoice);
//
//		// new
//		if($real_status=='pending' && !empty($has_invoice) && $has_invoice->task=='new'){
//			// pending-payment: woo: blank, WPI
//			if($has_invoice->status=='' || $has_invoice->status=='wpi-pending'){// needs payment
//				$payment_status = 'new-pending-payment';
//			}else{// processing payment
//				$payment_status = 'new-processing-payment';
//			}
//		}elseif($real_status=='pending' && !empty($revision_has_invoice) && $revision_has_invoice->task=='new'){
//
//		}
//
//
//
//
//		return $payment_status;
//	}
	

	/**
	 * @param $result array The result that should be sent back, usually and empty array.
	 * @param $post_data array Unescaped $_REQUEST data.
	 *
	 * @return mixed
	 */
	public static function ajax_save_post_override( $result, $post_data ) {
		global $geodir_pricing_manager;

		if ( $geodir_pricing_manager->cart->skip_invoice( $post_data ) ) {
			// Save post without invoice when no cart is active of package is free package.
			$cart_result = $geodir_pricing_manager->cart->post_without_invoice( $post_data );
		} else {
			// Send the info to the cart that is set WPI/WOO and if it has a return then set the result to that.
			$cart_result = $geodir_pricing_manager->cart->ajax_post_saved( $post_data );
		}

		if ( ! empty( $cart_result ) ) {
			$result = $cart_result;
		}

		return $result;
	}

	public static function create_claim_invoice( $post_id, $package_id, $user_id, $payment_id = 0 ) {
		global $geodir_pricing_manager;

		$data = array(
			'package_id' => $post_id,
			'package_id' => $package_id,
			'user_id' => $user_id
		);

		if ( $geodir_pricing_manager->cart->skip_invoice( $data ) ) {
			// Save claim post without invoice when no cart is active of package is free package.
			$result = $geodir_pricing_manager->cart->claim_without_invoice( $post_id, $package_id, $user_id, $payment_id );
		} else {
			// Send the info to the cart that is set WPI/WC and if it has a return then set the result to that.
			$result = $geodir_pricing_manager->cart->create_claim_invoice( $post_id, $package_id, $user_id, $payment_id );
		}

		return $result;
	}

	public static function claim_submit_success_message( $message, $claim, $post_id ) {
		global $geodir_pricing_manager;

		if ( ! empty( $claim->payment_id ) && ( $payment = GeoDir_Pricing_Post_Package::get_item( $claim->payment_id ) ) ) {
			if ( ! empty( $payment->cart ) && $payment->cart == 'nocart' ) {
				$post_package_id = $claim->payment_id;

				do_action( 'geodir_pricing_post_package_payment_completed', $payment, $payment->post_id  );

				$claim = GeoDir_Claim_Post::get_item( $claim->id );
		
				if ( absint( $claim->status ) == 1 ) {
					$message = self::alert( wp_sprintf( __( 'Your request to claim the listing has been approved. View the listing %shere%s.', 'geodir_pricing' ), '<a href="' . get_permalink( $post_id ) . '">', '</a>' ), 'success' );
				} elseif ( absint( $claim->status ) == 0 ) {
					if ( geodir_get_option( 'claim_auto_approve' ) ) {
						$message = self::alert( __( 'A verification link has been sent to your email address, please click the link in the email to verify your listing claim.', 'geodir_pricing' ), 'success' );
					} else {
						$message = self::alert( __( 'Your request to claim this listing has been sent successfully. You will be notified by email once a decision has been made.', 'geodir_pricing' ), 'success' );
					}
				}
			} elseif ( empty( $claim->status ) ) {
				$message = $geodir_pricing_manager->cart->claim_submit_success_message( $message, $claim, $post_id );

				if ( $message ) {
					$message = self::alert( $message, 'success' );
				}
			}
		}

		return $message;
	}

	public static function claim_pending_message( $message, $claim, $post_id ) {
		global $geodir_pricing_manager;

		if ( ! empty( $claim->payment_id ) && ( $payment = GeoDir_Pricing_Post_Package::get_item( $claim->payment_id ) ) ) {
			if ( ! empty( $payment->cart ) && $payment->cart == 'nocart' ) {
				// No cart
			} else {
				$_message = $geodir_pricing_manager->cart->claim_pending_message( $message, $payment, $claim, $post_id );

				if ( $_message ) {
					$message = $_message;
				}
			}
		}

		return $message;
	}

	/**
	 * Prevent the pricing fields from being deleted.
	 *
	 * @param $allow
	 * @param $field
	 *
	 * @return bool
	 */
	public static function prevent_field_delete( $allow, $field ) {
		if ( isset( $field->htmlvar_name ) && ( $field->htmlvar_name == 'expire_date' || $field->htmlvar_name == 'package_id' ) ) {
			$allow = false;
		}

		return $allow;
	}

	/**
	 * Register our custom post statuses, used for listing status.
     *
     * @since 2.0.0
	 */
	public static function register_post_status( $statuses = array() ) {

		$statuses['gd-expired'] = array(
			'label'                     => _x( 'Expired', 'Listing status', 'geodir_pricing' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'private'     				=> true, // Show in author page
			'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'geodir_pricing' ),
		);

		return $statuses;
	}

	public static function set_custom_status( $statuses = array() ) {
		$statuses['gd-expired'] = _x( 'Expired', 'Listing status', 'geodir_pricing' );

		return $statuses;
	}

	public static function package_id_input( $html, $field ) {
		global $aui_bs5, $gd_post, $geodir_label_type;

		$is_admin			= is_admin() && current_user_can( 'manage_options' ) ? true : false;
		$htmlvar_name 		= $field['htmlvar_name'];
		$value 				= geodir_get_cf_value( $field );
		$field_title 		= ! empty( $field['frontend_title'] ) ? __( $field['frontend_title'], 'geodirectory' ) : '';
		$field_desc 		= ! empty( $field['desc'] ) ? __( $field['desc'], 'geodirectory' ) : '';
		$task				= ! empty( $_REQUEST['task'] ) ? sanitize_text_field( $_REQUEST['task'] ) : '';
		$req_package_id		= ! empty( $_REQUEST['package_id'] ) ? absint( $_REQUEST['package_id'] ) : 0;
		$current_package_id	= ! empty( $_REQUEST['pid'] ) ? (int) geodir_get_post_meta( absint( $_REQUEST['pid'] ), 'package_id', true ) : 0;

		// Fetch packages and include the current post package just in case it is inactive.
		$package_args = array( 
			'post_type'       => $field['post_type'],
			'must_include'    => $value,
			'current_package' => ( ! empty( $gd_post ) && ! empty( $gd_post->package_id ) ? absint( $gd_post->package_id ) : 0 ),
			'group'           => 'add',
			'is_admin'        => $is_admin
		);

		if ( ! $is_admin ) {
			// Upgrade
			if ( $current_package_id && ! empty( $_REQUEST['pid'] ) && geodir_is_gd_post_type( get_post_type( absint( $_REQUEST['pid'] ) ) ) ) {
				$package__in = geodir_pricing_upgrade_packages( $current_package_id );

				if ( $task != 'upgrade' && ! empty( $package__in ) ) {
					$package__in[] = $current_package_id;
				}

				if ( ! empty( $package__in ) ) {
					$package_args['package__in'] = $package__in;
				}
			}
		}

		/**
		 * Filter the packages args to retrieve packages.
		 *
		 * @since 2.6.4
		 *
		 * @param array $package_args Package arguements.
		 */
		$package_args = apply_filters( 'geodir_pricing_package_id_input_package_args', $package_args );

		$packages = geodir_pricing_get_packages( $package_args, $gd_post );

		$options = array();
		foreach ( $packages as $key => $package ) {
			$title = '';

			if ( ! empty( $package->fa_icon ) ) {
				$title .= '<i class="' . esc_attr( $package->fa_icon ) . '" aria-hidden="true"></i> ';
			}

			$title .= __( $package->title, 'geodirectory' );

			if ( $is_admin ) {
				$title .= ' ( <a href="' . admin_url( 'edit.php?post_type=' . $field['post_type'] . '&page=' . $field['post_type'] . '-settings&tab=cpt-package&section=add-package&id=' . $package->id ) . '" target="_blank">' . $package->id . '</a> )';
			}

			if ( geodir_pricing_is_private( $package->id ) ) {
				$show = current_user_can( 'manage_options' );

				if ( ! $show && $current_package_id && $current_package_id == $package->id ) {
					$show = true;
				}

				if ( ! $show ) {
					continue;
				}

				$title .= '<span class="far fa-eye-slash text-warning c-pointer ' . ( $aui_bs5 ? 'ms-2' : 'ml-2' ) . '" title="' . __( 'private package', 'geodir_pricing' ) . '"></i>';
			}

			$options[ $package->id ] = $title;
		}

		ob_start();
		if ( ! is_admin() && $task == 'upgrade' && ! empty( $_REQUEST['pid'] ) && $value > 0 ) {
			if ( geodir_design_style() ) {
				// Required
				$required = ! empty( $field['is_required'] ) ? ' <span class="text-danger">*</span>' : '';

				// Admin only
				$admin_only = geodir_cfi_admin_only( $field );

				$package_ids = array_keys( $options);
				$f_package_id = ! empty( $package_ids ) ? $package_ids[0] : '';

				$package_redirect = apply_filters( 'geodir_pricing_onchange_package_redirect_to', '', $f_package_id, $value, $gd_post->ID, $field['post_type'] );
				if ( ! empty( $package_redirect ) ) {
					$package_redirect = add_query_arg( array( 'package_id' => '__PACKAGE_ID__' ), remove_query_arg( 'package_id', $package_redirect ) );
					$package_redirect = str_replace( '__PACKAGE_ID__', "' + this.value + '", $package_redirect );
				}

				if ( isset( $options[ $current_package_id ] ) ) {
					unset( $options[ $current_package_id ] );
				}

				$html = aui()->radio(
					array(
						'id'               => $htmlvar_name,
						'name'             => $htmlvar_name,
						'type'             => 'radio',
						'title'            => esc_attr( $field_title ),
						'label'            => esc_attr( $field_title ) . $admin_only . $required,
						'label_type'       => ! empty( $geodir_label_type ) ? $geodir_label_type : 'horizontal',
						'help_text'        => $field_desc,
						'class'            => '',
						'value'            => $value,
						'options'          => $options,
						'inline'           => false,
						'extra_attributes' => array(
							'onclick' => 'geodir_pricing_select_post_package(this, this.value, \'' . $package_redirect . '\');'
						),
						'wrap_attributes' => geodir_conditional_field_attrs( $field )
					)
				);

				echo $html;
			} else {
				?>
				<div id="<?php echo esc_attr( $htmlvar_name ); ?>_row" class="required_field geodir_form_row clearfix gd-fieldset-details geodir-pricing-field">
					<label><?php echo $field_title . ' <span>*</span>'; ?></label>
					<div role="radiogroup gd_price_package_group" style="float:left;width:70%">
						<?php foreach ( $options as $package_id => $package_title ) { 
							if ( $package_id == geodir_get_post_meta( (int)$_REQUEST['pid'], 'package_id', true ) ) {
								continue;
							}
							$package_redirect = apply_filters( 'geodir_pricing_onchange_package_redirect_to', '', $package_id, $value, $gd_post->ID, $field['post_type'] ); ?>
						<div id='<?php echo 'gd_price_package_'. $package_id; ?>' class="geodir_package"><span class="gd-radios" role="radio"><input name="<?php echo esc_attr( $htmlvar_name ); ?>" id="<?php echo esc_attr( $htmlvar_name . '_' . $package_id ); ?>" <?php checked( (int) $value, $package_id ); ?> value="<?php echo absint( $package_id ); ?>" class="gd-checkbox" field_type="radio" type="radio" onclick="geodir_pricing_select_post_package(this, <?php echo absint( $package_id ); ?>, '<?php echo $package_redirect; ?>');" /> <?php echo $package_title; ?></span></div>
						<?php } ?>
					 </div>
					 <span class="geodir_message_note"><?php echo $field_desc; ?></span>
					 <span class="geodir_message_error"><?php echo !empty($field['required_msg']) ? __($field['required_msg'], 'geodir_pricing') : __('You must select a package.', 'geodir_pricing'); ?></span>
				</div>
				<?php
			}
		} else {
			if ( geodir_design_style() ) {
				// Required
				$required = ! empty( $field['is_required'] ) ? ' <span class="text-danger">*</span>' : '';

				// Admin only
				$admin_only = geodir_cfi_admin_only( $field );

				$package_ids = array_keys( $options);
				$f_package_id = ! empty( $package_ids ) ? $package_ids[0] : '';

				$package_redirect = apply_filters( 'geodir_pricing_onchange_package_redirect_to', '', $f_package_id, $value, $gd_post->ID, $field['post_type'] );
				if ( ! empty( $package_redirect ) ) {
					$package_redirect = add_query_arg( array( 'package_id' => '__PACKAGE_ID__' ), remove_query_arg( 'package_id', $package_redirect ) );
					$package_redirect = str_replace( '__PACKAGE_ID__', "' + this.value + '", $package_redirect );
				}

				$html = aui()->radio(
					array(
						'id'               => $htmlvar_name,
						'name'             => $htmlvar_name,
						'type'             => 'radio',
						'title'            => esc_attr( $field_title ),
						'label'            => esc_attr( $field_title ) . $admin_only . $required,
						'label_type'       => ! empty( $geodir_label_type ) ? $geodir_label_type : 'horizontal',
						'help_text'        => $field_desc,
						'class'            => '',
						'value'            => $value,
						'options'          => $options,
						'inline'           => false,
						'extra_attributes' => array(
							'onclick' => 'geodir_pricing_select_post_package(this, this.value, \'' . $package_redirect . '\');'
						),
						'wrap_attributes'  => geodir_conditional_field_attrs( $field )
					)
				);

				echo $html;
			} else {
				?>
				<div id="<?php echo esc_attr( $htmlvar_name ); ?>_row" class="required_field geodir_form_row clearfix gd-fieldset-details geodir-pricing-field">
					<label><?php echo $field_title . ' <span>*</span>'; ?></label>
					<div role="radiogroup gd_price_package_group" style="float:left;width:70%">
						<?php 
						foreach ( $options as $package_id => $package_title ) {
							$package_redirect = apply_filters( 'geodir_pricing_onchange_package_redirect_to', '', $package_id, $value, $gd_post->ID, $field['post_type'] ); 
						?>
						<div id='<?php echo 'gd_price_package_'. $package_id; ?>'  class="geodir_package"><span class="gd-radios" role="radio"><input name="<?php echo esc_attr( $htmlvar_name ); ?>" id="<?php echo esc_attr( $htmlvar_name . '_' . $package_id ); ?>" <?php checked( (int) $value, $package_id ); ?> value="<?php echo absint( $package_id); ?>" class="gd-checkbox" field_type="radio" type="radio" onclick="geodir_pricing_select_post_package(this, <?php echo absint( $package_id ); ?>, '<?php echo $package_redirect; ?>');" /> <?php echo $package_title; ?></span></div>
						<?php } ?>
					 </div>
					 <span class="geodir_message_note"><?php echo $field_desc; ?></span>
					 <span class="geodir_message_error"><?php echo !empty($field['required_msg']) ? __($field['required_msg'], 'geodir_pricing') : __('You must select a package.', 'geodir_pricing'); ?></span>
				</div>
				<?php
			}
		}

		$html = ob_get_clean();

		return $html;
	}

	public static function onchange_package_redirect_to( $redirect_to = '', $package_id = 0, $current_package_id = 0, $post_id = 0, $post_type = '' ) {
		if ( empty( $package_id ) ) {
			return $redirect_to;
		}

		if ( empty( $post_type ) && $post_id ) {
			$post_type = get_post_type( $post_id );
		}

		if ( is_admin() ) {
			$redirect_to = add_query_arg( array( 'package_id' => $package_id ), get_edit_post_link( $post_id ) );
		} else {
			$redirect_to = geodir_is_page('add-listing') ? geodir_curPageURL() : geodir_add_listing_page_url( $post_type );
			$redirect_to = add_query_arg( array( 'package_id' => $package_id ), $redirect_to );
		}

		return $redirect_to;
	}

	public static function skip_post_custom_field( $skip = false, $field = array(), $package_id = '', $default = 'all', $fields_location = 'none' ) {
		if ( $package_id != '' ) {
			$packages = isset( $field->packages ) ? explode( ',', $field->packages ) : array();

			if ( ! empty( $packages ) && in_array( (int)$package_id, $packages ) ) {
				$skip = false;
			} else {
				$skip = true;
			}

			if ( ! empty( $field->is_default ) && $field->htmlvar_name != 'post_images' ) {
				$skip = false;
			}
		}

		return $skip;
	}

	public static function cf_value( $value, $field ) {
		global $gd_post;

		if ( $field['name'] == 'package_id' ) {
			if ( isset( $_REQUEST['package_id'] ) ) {
				$value = absint( $_REQUEST['package_id'] );
			} else if ( empty( $value ) ) {
				$post_type = ! empty( $gd_post->post_type ) ? $gd_post->post_type : $field['post_type'];

				$value = geodir_pricing_default_package_id( $post_type );
			}
		}

		return $value;
	}

	public static function set_file_limit( $limit, $cf, $gd_post ) {
		if ( $cf['htmlvar_name'] == 'post_images' ) {
			$package_id = self::cf_value( geodir_get_post_meta( $gd_post->ID, 'package_id', true ), array( 'name' => 'package_id' ) );
			$limit = (int) geodir_pricing_get_meta( $package_id, 'image_limit', true );
		}
		return $limit;
	}

	public static function send_notifications( $post_id, $package_id, $post_package_id ) {
		$post_package = GeoDir_Pricing_Post_Package::get_item( (int) $post_package_id );

		if ( empty( $post_package ) ) {
			return;
		}

		$gd_post = geodir_get_post_info( (int) $post_id );
		if ( empty( $gd_post ) ) {
			return;
		}

		$task = GeoDir_Pricing_Post_Package::get_task( $post_package_id );

		$email_args = array( 
			'post_package_id' => $post_package_id
		);

		if ( $task == 'renew' ) {
			// Post renew success email to user.
			GeoDir_Pricing_Email::send_user_renew_success_email( $gd_post, $email_args );
			// Post renew success email to admin.
			GeoDir_Pricing_Email::send_admin_renew_success_email( $gd_post, $email_args );
		} else if ( $task == 'upgrade' ) {
			// Post upgrade success email to user.
			GeoDir_Pricing_Email::send_user_upgrade_success_email( $gd_post, $email_args );
			// Post upgrade success email to admin.
			GeoDir_Pricing_Email::send_admin_upgrade_success_email( $gd_post, $email_args );
		} else {
			do_action( 'geodir_pricing_post_package_notification_' . $task, $post_id, $package_id, $post_package_id );
		}
	}

	public static function pricing_field_output( $html, $location, $cf, $p = '', $output='' ) {
		if(is_numeric($p)){$gd_post = geodir_get_post_info($p);}
		else{ global $gd_post;}

		if(!is_array($cf) && $cf!=''){
			$cf = geodir_get_field_infoby('htmlvar_name', $cf, $gd_post->post_type);
			if(!$cf){return NULL;}
		}

		$html_var = $cf['htmlvar_name'];

		if(has_filter("geodir_custom_field_output_pricing_loc_{$location}")){
			$html = apply_filters("geodir_custom_field_output_pricing_loc_{$location}",$html,$cf,$output);
		}

		// Check if there is a custom field specific filter.
		if(has_filter("geodir_custom_field_output_pricing_var_{$html_var}")){
			/**
			 * Filter the business hours html by individual custom field.
			 *
			 * @param string $html The html to filter.
			 * @param string $location The location to output the html.
			 * @param array $cf The custom field array.
			 * @param string $output The output string that tells us what to output.
			 * @since 2.0.0
			 */
			$html = apply_filters("geodir_custom_field_output_pricing_var_{$html_var}",$html,$location,$cf,$output);
		}

		// Check if there is a custom field key specific filter.
		if(has_filter("geodir_custom_field_output_pricing_key_{$cf['field_type_key']}")){
			/**
			 * Filter the business hours html by field type key.
			 *
			 * @param string $html The html to filter.
			 * @param string $location The location to output the html.
			 * @param array $cf The custom field array.
			 * @param string $output The output string that tells us what to output.
			 * @since 2.0.0
			 */
			$html = apply_filters("geodir_custom_field_output_pricing_key_{$cf['field_type_key']}",$html,$location,$cf,$output);
		}

		return $html;
	}

	public static function package_id_output( $html, $location, $cf, $output ) {
	}

	public static function expire_date_output( $html, $location, $cf, $output ) {
		global $gd_post;

		if(!is_array($cf) && $cf!=''){
			$cf = geodir_get_field_infoby('htmlvar_name', $cf, $gd_post->post_type);
			if(!$cf){return NULL;}
		}
	
		// If not html then we run the standard output.
		if(empty($html)){

			if (isset($gd_post->{$cf['htmlvar_name']}) && $gd_post->{$cf['htmlvar_name']} != '' ):

				$class = "geodir-i-datepicker";

				$field_icon = geodir_field_icon_proccess($cf);
				$output = geodir_field_output_process($output);
				if (strpos($field_icon, 'http') !== false) {
					$field_icon_af = '';
				} elseif ($field_icon == '') {
					$field_icon_af = ($cf['htmlvar_name'] == 'geodir_timing') ? '<i class="fas fa-clock" aria-hidden="true"></i>' : "";
				} else {
					$field_icon_af = $field_icon;
					$field_icon = '';
				}

				$current_date = date_i18n( 'Y-m-d' );
				$expire_date = $gd_post->{$cf['htmlvar_name']};
				$state = __( 'days left', 'geodir_pricing' );
				$date_diff_text = '';
				$expire_class = 'expire_left';

				if ( geodir_pricing_date_never_expire( $expire_date ) ) {
					$expire_date = __( 'Never', 'geodir_pricing' );
				} else {
					if ( $expire_date == $current_date ) {
						$expire_date = __( 'Today', 'geodir_pricing' );
					} else {
						if ( strtotime( $expire_date ) < strtotime( $current_date ) ) {
							$state = __( 'days overdue', 'geodir_pricing' );
							$expire_class = 'expire_over';
						}
						$date_diff = round( abs( strtotime( $expire_date ) - strtotime( $current_date ) ) / 86400 );
						$date_diff_text = '<br /><span class="' . esc_attr( $expire_class ) . '">(' . $date_diff . ' ' . $state . ')</span>';
					}
				}

				if ( empty( $expire_date ) ) {
					$value = __( 'Never', 'geodir_pricing' );
				} else {
					$value = $expire_date . $date_diff_text;
				}

				$html = '<div class="geodir_post_meta ' . $cf['css_class'] . ' geodir-field-' . $cf['htmlvar_name'] . '">';

				if($output=='' || isset($output['icon'])) $html .= '<span class="geodir_post_meta_icon '.$class.'" style="' . $field_icon . '">' . $field_icon_af;
				if($output=='' || isset($output['label']))$html .= (trim($cf['frontend_title'])) ? '<span class="geodir_post_meta_title" >'.__($cf['frontend_title'], 'geodirectory') . ': '.'</span>' : '';
				if($output=='' || isset($output['icon']))$html .= '</span>';
				if($output=='' || isset($output['value']))$html .= stripslashes( $value );

				$html .= '</div>';

			endif;

		}
		return $html;
	}

	public static function show_public_preview( $query ) {
		if ( $query->is_main_query() && $query->is_preview() && $query->is_singular() ) {
			if ( ! headers_sent() ) {
				nocache_headers();
			}

			add_filter( 'posts_results', array( __CLASS__, 'set_post_to_publish' ), 10, 2 );
		}

		return $query;
	}

	public static function set_post_to_publish( $posts ) {
		// Remove the filter again, otherwise it will be applied to other queries too.
		remove_filter( 'posts_results', array( __CLASS__, 'set_post_to_publish' ), 10 );

		if ( empty( $posts ) ) {
			return;
		}

		// check id post has no author and if the current user owns it
		if ( ( ! get_current_user_id() && GeoDir_Post_Data::owner_check( $posts[0]->ID, 0 ) ) ||  ( ! isset($_REQUEST['preview_nonce']) && get_current_user_id() && GeoDir_Post_Data::owner_check( $posts[0]->ID, get_current_user_id() ) ) ) {
			$posts[0]->post_status = 'publish';

			// Disable comments and pings for this post.
			add_filter( 'comments_open', '__return_false' );
			add_filter( 'pings_open', '__return_false' );
		}


		return $posts;
	}

	public static function set_expired_status( $posts, $wp_query ) {
		global $wp_post_statuses, $gd_reset_expired;
		
		if ( isset( $wp_post_statuses['gd-expired'] ) && ! empty( $wp_query->is_single ) && ! empty( $posts ) && ! empty( $posts[0]->post_type ) && geodir_is_gd_post_type( $posts[0]->post_type ) && !empty( $posts[0]->post_status ) && geodir_pricing_post_is_expired( $posts[0] ) ) {
			$wp_post_statuses['gd-expired']->public = true;
			$gd_reset_expired = true;
		}
		
		return $posts;
	}

	public static function reset_expired_status( $posts, $wp_query ) {
		global $wp_post_statuses, $gd_reset_expired;
		
		if ( $gd_reset_expired && isset( $wp_post_statuses['gd-expired'] ) ) {
			$wp_post_statuses['gd-expired']->public = false;
			$gd_reset_expired = false;
		}
		
		return $posts;
	}

	/**
	 * Filters the default post display states used in the posts list table.
	 *
	 * @since 2.5.0
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 * @return array Post display states.
	 */
	public static function display_post_states( $post_states, $post ) {
		if ( 'gd-expired' === $post->post_status ) {
			$post_states[ $post->post_status ] = geodir_get_post_status_name( $post->post_status );
		}
		return $post_states;
	}

	public static function rest_markers_query_where( $where, $request ) {
		if ( ! empty( $request['post'] ) && is_array( $request['post'] ) && count( $request['post'] ) == 1 && strpos( $where, "'gd-expired'" ) === false ) {
			$where = str_replace( "'gd-closed'", "'gd-closed', 'gd-expired'", $where );
		}
		return $where;
	}

	public static function cfi_allow_html_editor( $allow, $field ) {
		global $gd_post;

		if ( $allow && ! empty( $field['name'] ) && $field['name'] == 'post_content' && ( $package_id = geodir_get_post_package_id( $gd_post ) ) ) {
			$allow = ! geodir_pricing_disable_html_editor( $package_id );
		}

		return $allow;
	}

	public static function wp_insert_post_data( $data, $postarr ) {
		if ( ! ( isset( $data['post_type'] ) && geodir_is_gd_post_type( $data['post_type'] ) ) ) {
			return $data;
		}

		if ( ! empty( $data['post_content'] ) ) {
			$package = geodir_get_post_package( $postarr, $data['post_type'] );

			// Remove html tags
			if ( geodir_pricing_disable_html_editor( $package->id ) ) {
				$data['post_content'] = geodir_sanitize_textarea_field( $data['post_content'] );
			}

			$desc_limit = geodir_pricing_package_desc_limit( $package );

			if ( $desc_limit !== NULL ) {
				$data['post_content'] = geodir_excerpt( $data['post_content'], absint( $desc_limit ) );
			}
		}

		return $data;
	}

	public static function auto_save_post_data( $post_data ) {
		if ( ! ( isset( $post_data['post_type'] ) && geodir_is_gd_post_type( $post_data['post_type'] ) ) ) {
			return $post_data;
		}

		$post_type = $post_data['post_type'];
		$cat_taxonomy = $post_type . 'category';
		$tag_taxonomy = $post_type . '_tags';
		$package = geodir_get_post_package( $post_data, $post_type );

		$post_categories = array();
		if ( isset( $post_data['tax_input'][ $cat_taxonomy ] ) && ! empty( $post_data['tax_input'][ $cat_taxonomy ] ) ) {
			$post_categories = $post_data['tax_input'][ $cat_taxonomy ];
			$default_category = isset( $post_data['default_category'] ) ? absint( $post_data['default_category'] ) : 0;
			$filter_categories = self::filter_package_categories( $package->id, $post_categories, $default_category );

			$post_data['tax_input'][ $cat_taxonomy ] = $filter_categories['categories'];
			$post_data['default_category'] = $filter_categories['default_category'];
		}

		if ( empty( $post_categories ) && isset( $post_data['post_category'] ) ) {
			$post_categories = $post_data['post_category'];
			$default_category = isset( $post_data['default_category'] ) ? absint( $post_data['default_category'] ) : 0;
			$filter_categories = self::filter_package_categories( $package->id, $post_categories, $default_category );

			$post_data['post_category'] = $filter_categories['categories'];
			$post_data['default_category'] = $filter_categories['default_category'];
		}

		// Tags
		if ( isset( $post_data['tax_input'][ $tag_taxonomy ] ) && ! empty( $post_data['tax_input'][ $tag_taxonomy ] ) ) {
			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'inline-save' ) {
				$post_tags = isset( $_REQUEST['tax_input'][ $tag_taxonomy ] ) ? $_REQUEST['tax_input'][ $tag_taxonomy ] : '';
				$post_tags = self::filter_package_tags( $package->id, $post_tags );
				if ( $post_tags ) {
					$_REQUEST['tax_input'][ $tag_taxonomy ] = is_array( $post_tags ) ? implode( ",", $post_tags ) : $post_tags;
				}
			} else {
				$post_data['tax_input'][ $tag_taxonomy ] = self::filter_package_tags( $package->id, $post_data['tax_input'][ $tag_taxonomy ] );
			}
		} else if ( isset( $post_data['post_tags'] ) && is_array( $post_data['post_tags'] ) ){
			$post_tags = self::filter_package_tags( $package->id, $post_data['post_tags'] );

			$post_data['post_tags'] = $post_tags;
		}

		return $post_data;
	}

	public static function temp_gd_post( $gd_post, $post, $update ) {
		if ( ! ( isset( $gd_post['post_type'] ) && geodir_is_gd_post_type( $gd_post['post_type'] ) ) ) {
			return $gd_post;
		}

		// If dummy data then don't restrict the cats
		if ( isset( $gd_post['post_dummy'] ) && $gd_post['post_dummy'] ) {
			return $gd_post;
		}

		$post_type = $gd_post['post_type'];
		$cat_taxonomy = $post_type . 'category';
		$tag_taxonomy = $post_type . '_tags';
		$package = geodir_get_post_package( $gd_post, $post_type );

		if ( empty( $package ) ) {
			return $gd_post;
		}

		// Categories
		$post_categories = array();
		if ( isset( $gd_post['tax_input'][ $cat_taxonomy ] ) && ! empty( $gd_post['tax_input'][ $cat_taxonomy ] ) ) {
			$post_categories = $gd_post['tax_input'][ $cat_taxonomy ];
			$default_category = isset( $gd_post['default_category'] ) ? absint( $gd_post['default_category'] ) : 0;
			$filter_categories = self::filter_package_categories( $package->id, $post_categories, $default_category );

			$gd_post['tax_input'][ $cat_taxonomy ] = $filter_categories['categories'];
			$gd_post['default_category'] = $filter_categories['default_category'];
		}

		if ( empty( $post_categories ) && isset( $gd_post['post_category'] ) ) {
			$post_categories = $gd_post['post_category'];
			$default_category = isset( $gd_post['default_category'] ) ? absint( $gd_post['default_category'] ) : 0;
			$filter_categories = self::filter_package_categories( $package->id, $post_categories, $default_category );

			$gd_post['post_category'] = $filter_categories['categories'];
			$gd_post['default_category'] = $filter_categories['default_category'];
		}

		// Tags
		if ( isset( $gd_post['tax_input'][ $tag_taxonomy ] ) && ! empty( $gd_post['tax_input'][ $tag_taxonomy ] ) ) {
			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'inline-save' ) {
				$post_tags = isset( $_REQUEST['tax_input'][ $tag_taxonomy ] ) ? $_REQUEST['tax_input'][ $tag_taxonomy ] : '';
				$post_tags = self::filter_package_tags( $package->id, $post_tags );
				if ( $post_tags ) {
					$_REQUEST['tax_input'][ $tag_taxonomy ] = is_array( $post_tags ) ? implode( ",", $post_tags ) : $post_tags;
				}
			} else {
				$gd_post['tax_input'][ $tag_taxonomy ] = self::filter_package_tags( $package->id, $gd_post['tax_input'][ $tag_taxonomy ] );
			}
		} else if ( isset( $gd_post['post_tags'] ) && is_array( $gd_post['post_tags'] ) ){
			$post_tags = self::filter_package_tags( $package->id, $gd_post['post_tags'] );

			$gd_post['post_tags'] = $post_tags;
		}

		return $gd_post;
	}

	public static function filter_package_categories( $package_id, $categories, $default_category = 0 ) {
		$value = array( 'categories' => array(), 'default_category' => $default_category );

		if ( empty( $package_id ) || empty( $categories ) ) {
			return $value;
		}

		$value = array( 'categories' => $categories, 'default_category' => $default_category );

		$exclude_category = geodir_pricing_exclude_category( $package_id );
		$category_limit = geodir_pricing_category_limit( $package_id );

		if ( empty( $exclude_category ) && empty( $category_limit ) ) {
			return $value;
		}

		$filtered = array();
		if ( ! empty( $exclude_category ) ) {
			foreach ( $categories as $id ) {
				if ( (int) $id > 0 && ! in_array( (int) $id, $exclude_category ) ) {
					$filtered[] = (int) $id;
				}
			}

			$categories = $filtered;
		}

		if ( ! empty( $categories ) ) {
			if ( (int) $default_category > 0 && ! in_array( (int) $default_category, $categories ) ) {
				$default_category = $categories[0];
			}
		} else {
			$default_category = 0;
		}

		if ( $category_limit > 0 && ! empty( $categories ) && count( $categories ) > $category_limit ) {
			$filtered = array();
			$count = 0;
			foreach ( $categories as $id ) {
				if ( (int) $id > 0 ) {
					if ( $count < $category_limit ) {
						$filtered[] = (int) $id;
						$count++;
					}
				}
			}
			$categories = $filtered;
			if ( ! empty( $categories ) && (int) $default_category > 0 && ! in_array( (int) $default_category, $categories ) ) {
				array_pop( $categories );
				$categories[] = $default_category;
			}
		}

		if ( ! empty( $categories ) ) {
			if ( (int) $default_category > 0 && ! in_array( (int) $default_category, $categories ) ) {
				$default_category = $categories[0];
			}
		} else {
			$default_category = 0;
		}

		return array( 'categories' => $categories, 'default_category' => $default_category );
	}

	public static function filter_package_tags( $package_id, $tags ) {
		$value = array();

		if ( empty( $package_id ) || empty( $tags ) ) {
			return $value;
		}

		$tags = is_array( $tags ) ? $tags : explode( ",", $tags );
		$value = $tags;
		$tag_limit = geodir_pricing_tag_limit( $package_id );

		if ( empty( $tag_limit ) ) {
			return $value;
		}

		if ( count( $tags ) > $tag_limit ) {
			$value = array();
			$count = 0;
			foreach ( $tags as $tag ) {
				if ( $tag != '' && $count < $tag_limit ) {
					$value[] = $tag;
					$count++;
				}
			}
		}

		return $value;
	}

	public static function save_post_data( $postarr, $gd_post, $post, $update ) {
		if ( ! ( isset( $gd_post['post_type'] ) && geodir_is_gd_post_type( $gd_post['post_type'] ) ) ) {
			return $postarr;
		}

		$post_type = $gd_post['post_type'];
		$tag_taxonomy = $post_type . '_tags';
		$package = geodir_get_post_package( $gd_post, $post_type );

		// Tags
		if ( ! empty( $postarr['post_tags'] ) && geodir_pricing_tag_limit( $package->id ) ) {
			$post_tags = ! is_array( $postarr['post_tags'] ) ? explode( ',', $postarr['post_tags'] ) : $postarr['post_tags'];

			$save_tags = self::filter_package_tags( $package->id, $post_tags );
			if ( count( $post_tags ) != count( $save_tags ) && ( $tag_terms = wp_get_object_terms( $gd_post['ID'], $tag_taxonomy, array( 'fields' => 'names' ) ) ) ) {
				if ( ! is_wp_error( $tag_terms ) && is_array( $tag_terms ) && count( $tag_terms ) != count( $save_tags ) ) {
					wp_set_post_terms( $gd_post['ID'], $save_tags, $tag_taxonomy ); // Save post terms
				}
			}
			$postarr['post_tags'] = implode( ",", $save_tags );
		}

		// Set featured
		if ( GeoDir_Post_types::supports( $post_type, 'featured' ) ) {
			if ( geodir_pricing_is_featured( $package->id ) ) {
				if ( isset( $postarr['featured'] ) ) {
					// Set from data
				} elseif ( isset( $gd_post['featured'] ) ) {
					$postarr['featured'] = $gd_post['featured'];
				}
			} else {
				$postarr['featured'] = 0;
			}
		} else {
			if ( isset( $postarr['featured'] ) ) {
				unset( $postarr['featured'] );
			}
		}

		return $postarr;
	}

	public static function validate_auto_save_post_data( $valid, $post_data, $update = false, $doing_autosave = true ) {
		if ( $doing_autosave && ! $update ) {
			$package = ! empty( $post_data['package_id'] ) ? geodir_pricing_get_package( absint( $post_data['package_id'] ) ) : NULL;

			if ( empty( $package->status ) ) {
				$valid = new WP_Error(
					"geodir_pricing_invalid_package_id", __( 'There is a problem with the listing package configuration, please contact the administrator.', 'geodir_pricing' ), array(
						'status' => 404,
					)
				);
			}
		}

		// Handle package switch & auto save
		if ( $doing_autosave && ! empty( $post_data['action'] ) && $post_data['action'] == 'geodir_auto_save_post' && ! empty( $post_data['target'] ) && $post_data['target'] == 'auto' && ! empty( $post_data['geodir_switch_pkg'] ) ) {
			update_metadata( 'post', $post_data['geodir_switch_pkg'], '_gd_switch_pkg', $post_data['geodir_switch_pkg'] );
		}

		return $valid;
	}

	public static function validate_ajax_save_post_data( $valid, $post_data, $update = false ) {
		if ( ! $update ) {
			$package = ! empty( $post_data['package_id'] ) ? geodir_pricing_get_package( absint( $post_data['package_id'] ) ) : NULL;

			if ( empty( $package->status ) ) {
				$valid = new WP_Error(
					"geodir_pricing_invalid_package_id", __( 'There is a problem with the listing package configuration, please contact the administrator.', 'geodir_pricing' ), array(
						'status' => 404,
					)
				);
			}
		}

		return $valid;
	}

	public static function bp_listings_where( $where, $post_type ) {
		global $wpdb;

		$where = str_replace( " OR " . $wpdb->posts . ".post_status = 'draft'", " OR " . $wpdb->posts . ".post_status = 'draft' OR " . $wpdb->posts . ".post_status = 'gd-expired'", $where );

		return $where;
	}
	
	// Don't show user notes on package switch
	public static function post_output_user_notes( $user_notes ) {
		global $gd_post;

		if ( ! empty( $user_notes ) && ! empty( $gd_post ) && (int) $gd_post->ID == (int) get_post_meta( $gd_post->ID, '_gd_switch_pkg', true ) ) {
			if ( isset( $user_notes['has-auto-draft'] ) ) {
				unset( $user_notes['has-auto-draft'] );
			}
			if ( isset( $user_notes['has-revision'] ) ) {
				unset( $user_notes['has-revision'] );
			}
			delete_metadata( 'post', $gd_post->ID, '_gd_switch_pkg' );
		}

		return $user_notes;
	}

	public static function ajax_save_post_message( $message, $post_data ) {
		if ( ! ( ! empty( $post_data['post_parent'] ) && ! empty( $post_data['post_package']->task ) ) ) {
			return $message;
		}

		$task = $post_data['post_package']->task;

		if ( empty( $post_data['post_parent'] ) && $post_data['post_status'] != 'publish' ) {
			$post = new stdClass();
			$post->ID = $post_data['ID'];
			$link = GeoDir_Post_Data::get_preview_link( $post );
		} else {
			$link = get_permalink( $post_data['ID'] );
		}

		$post_type = get_post_type( $post_data['ID'] );

		if ( $post_data['post_status'] == 'publish' ) {
			// Published
			if ( $task == 'renew' ) { // Renew
				$message = wp_sprintf( __( 'Post renew received, your changes are now live and can be viewed %shere%s.', 'geodir_pricing' ), "<a href='" . $link . "'>", "</a>" );
			} elseif ( $task == 'upgrade' ) { // Upgrade
				$message = wp_sprintf( __( 'Post upgrade received, your changes are now live and can be viewed %shere%s.', 'geodir_pricing' ), "<a href='" . $link . "'>", "</a>" );
			}
		} else {
			// Pending
			if ( $task == 'renew' ) { // Renew
				$message = __( 'Post renew received, your changes may need to be reviewed before going live.', 'geodir_pricing' );
			} elseif ( $task == 'upgrade' ) { // Upgrade
				$message = __( 'Post upgrade received, your changes may need to be reviewed before going live.', 'geodir_pricing' );
			}
		}

		return $message;
	}

	/**
	 * Check frontend analytics visibility for GD post.
	 *
	 * @since 2.5.1.0
	 *
	 * @param bool $show True or False to show/hide analytics.
	 * @param object $post The post object.
	 * return bool True to show analytics else False.
	 */
	public static function frontend_analytics_check_post_google_analytics( $show, $post ) {
		if ( ! empty( $post ) && ! empty( $post->post_type ) && geodir_is_gd_post_type( $post->post_type ) ) {
			$package = geodir_get_post_package( $post );

			$show = ! empty( $package->google_analytics ) ? true : false;
		}

		return $show;
	}

	public static function author_actions( $author_actions, $post_id ) {
		if ( ! empty( $post_id ) && is_user_logged_in() ) {
			// Renew link
			if ( $renew_link = geodir_pricing_post_renew_link( $post_id, true ) ) {
				$author_actions['renew'] = array(
					'title' => esc_attr__( 'Renew', 'geodir_pricing' ),
					'icon' => 'fas fa-sync',
					'url' => $renew_link
				);
			}

			// Upgrade link
			if ( $upgrade_link = geodir_pricing_post_upgrade_link( $post_id, true ) ) {
				$author_actions['upgrade'] = array(
					'title' => esc_attr__( 'Upgrade', 'geodir_pricing' ),
					'icon' => 'fas fa-sync',
					'url' => $upgrade_link
				);
			}
		}

		return $author_actions;
	}

	public static function alert( $message, $type = 'info' ) {
		if ( ! geodir_design_style() ) {
			return $message;
		}

		return aui()->alert(
			array(
				'type'=> $type,
				'content'=> $message,
				'class' => 'mb-0'
			)
		);
	}

	/**
	 * Output for package id.
	 *
	 * @since 2.6.0.1
	 *
	 * @param string $html HTML output.
	 * @param string $location Field location.
	 * @param array $cf Custom field.
	 * @param array $output Output type.
	 * @return string Output for package id.
	 */
	public static function output_package_id( $html, $location, $cf, $output ) {
		global $gd_post;

		if ( ! geodir_is_block_demo() && ! empty( $cf['htmlvar_name'] ) && ! empty( $gd_post ) && ! empty( $gd_post->{$cf['htmlvar_name']} ) ) {
			$design_style = geodir_design_style();
			$class = "geodir-i-text";

			$field_icon = geodir_field_icon_proccess( $cf );
			$output = geodir_field_output_process( $output );
			if ( strpos( $field_icon, 'http' ) !== false ) {
				$field_icon_af = '';
			} elseif ( $field_icon == '' ) {
				$field_icon_af = '';
			} else {
				$field_icon_af = $field_icon;
				$field_icon = '';
			}

			$value = absint( $gd_post->{$cf['htmlvar_name']} );

			// Database value.
			if ( ! empty( $output ) && isset( $output['raw'] ) ) {
				return $value;
			}

			$value = geodir_pricing_package_name( (int) $value );

			// Return stripped value.
			if ( ! empty( $output ) && isset( $output['strip'] ) ) {
				return $value;
			}

			$html = '<div class="geodir_post_meta ' . $cf['css_class'] . ' geodir-field-' . $cf['htmlvar_name'] . '">';

			$maybe_secondary_class = isset( $output['icon'] ) ? 'gv-secondary' : '';

			if ( $output == '' || isset( $output['icon'] ) ) {
				$html .= '<span class="geodir_post_meta_icon '.$class.'" style="' . $field_icon . '">' . $field_icon_af;
			}
			if ( $output == '' || isset( $output['label'] ) ) {
				$html .= trim( $cf['frontend_title'] ) != '' ? '<span class="geodir_post_meta_title ' . $maybe_secondary_class . '" >' . __( $cf['frontend_title'], 'geodirectory' ) . ': ' . '</span>' : '';
			}
			if ( $output == '' || isset( $output['icon'] ) ) {
				$html .= '</span>';
			}
			if ( $output == '' || isset( $output['value'] ) ) {
				$html .= $value;
			}

			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Filter post badge match value.
	 *
	 * @since 2.6.0.1
	 *
	 * @param string $match_value Match value.
	 * @param string $match_field Match field.
	 * @param array $args The badge parameters.
	 * @param array $find_post Post object.
	 * @param array $field The custom field array.
	 * @return string Filtered value.
	 */
	public static function post_badge_match_value( $match_value, $match_field, $args, $find_post, $field ) {
		if ( $match_field == 'package_id' && (int) $match_value > 0 && ! empty( $args['badge'] ) && strpos( $args['badge'], '%%input%%' ) !== false ) {
			if ( $name = geodir_pricing_package_name( (int) $match_value ) ) {
				$match_value = str_replace( '%%input%%', $name, $args['badge'] );
			}
		}

		return $match_value;
	}

	/**
	 * Add listing from hidden fields.
	 *
	 * @since 2.6.1.4
	 *
	 * @param string $post_type Post type.
	 * @param object $post The post object.
	 * @param object $package The package.
	 */
	public static function add_listing_hidden_fields( $post_type, $post, $package ) {
		if ( ! empty( $_REQUEST['task'] ) ) {
			echo '<input type="hidden" name="task" value="' . esc_attr( sanitize_text_field( $_REQUEST['task'] ) ) . '">';
		}
	}

	/**
	 * Check whether the current post is open for reviews.
	 *
	 * @since 2.6.6
	 *
	 * @param bool $open Whether the current post is open for reviews.
	 * @param int $post_id The post ID.
	 *
	 * @return bool True if allowed otherwise False.
	 */
	public static function comments_open( $open, $post_id ) {
		global $gd_post;

		if ( empty( $post_id ) ) {
			return $open;
		}

		if ( ! empty( $open ) && ! empty( $gd_post ) && (int) $gd_post->ID == (int) $post_id && ! empty( $gd_post->package_id ) ) {
			if ( ! geodir_pricing_supports_reviews( (int) $gd_post->package_id ) ) {
				$open = false;
			}
		}

		return $open;
	}

	/**
	 * Check current user can reply to post review.
	 *
	 * @since 2.7.2
	 *
	 * @param bool   $can_reply_review If allowed then true else false.
	 * @param object $comment Comment object.
	 */
	public static function user_can_reply_review( $can_reply_review, $comment ) {
		$post_id = ! empty( $comment->comment_post_ID ) ? (int) $comment->comment_post_ID : 0;

		if ( $post_id ) {
			$can_reply_review = self::can_reply_review( $post_id );
		}

		return $can_reply_review;
	}

	/**
	 * Check the current user can reply review.
	 *
	 * @since 2.7.2
	 *
	 * @param int    $post_id The post ID.
	 * @param int    $package_id The package ID.
	 * @param string $post_type The post type.
	 * @return bool True if user can reply review else false.
	 */
	public static function can_reply_review( $post_id, $package_id = 0, $post_type = '' ) {
		if ( empty( $package_id ) ) {
			if ( empty( $post_type ) ) {
				$post_type = get_post_type( $post_id );
			}

			$package_id = geodir_get_post_package_id( $post_id, $post_type );
		}

		$_can_reply = geodir_pricing_get_meta( (int) $package_id, 'can_reply_review', true );

		if ( empty( $_can_reply ) ) {
			$can_reply = true;
		} else if ( $_can_reply == 'nobody' ) {
			$can_reply = false;
		} else {
			if ( $_can_reply == 'admin' &&(int) get_current_user_id() && current_user_can( 'manage_options' ) ) {
				$can_reply = true;
			} else if ( $_can_reply == 'owner' &&(int) get_current_user_id() ) {
				$can_reply = (bool) geodir_listing_belong_to_current_user( (int) $post_id );
			} else {
				$can_reply = false;
			}
		}

		return $can_reply;
	}
}