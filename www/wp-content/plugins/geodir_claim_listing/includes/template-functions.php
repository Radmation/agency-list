<?php
/**
 * Claim Listings Template Functions.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register claim widgets.
 *
 * @since 2.0.0.0
 *
 * @param array $widgets The list of available widgets.
 * @return array Available GD widgets.
 */
function geodir_claim_register_widgets( $widgets ) {
	if ( get_option( 'geodir_claim_version' ) ) {
		$widgets[] = 'GeoDir_Claim_Widget_Post_Claim';
	}

	return $widgets;
}

function geodir_claim_params() {
	$params = array(
		'text_send' => __( 'Send', 'geodir-claim' ),
		'text_sending' => __( 'Sending...', 'geodir-claim' ),
		'aui' => geodir_design_style()
	);

    return apply_filters( 'geodir_claim_params', $params );
}

/**
 * get_templates_dir function.
 *
 * The function is return templates dir path.
 *
 * @since 2.0.0
 *
 * @return string Templates dir path.
 */
function geodir_claim_get_templates_dir() {
    return GEODIR_CLAIM_PLUGIN_DIR . 'templates';
}

/**
 * get_templates_url function.
 *
 * The function is return templates dir url.
 *
 * @since 2.0.0
 *
 * @return string Templates dir url.
 */
function geodir_claim_get_templates_url() {
    return GEODIR_CLAIM_PLUGIN_URL . '/templates';
}

/**
 * get_theme_template_dir_name function.
 *
 * The function is return theme template dir name.
 *
 * @since 2.0.0
 *
 * @return string Theme template dir name.
 */
function geodir_claim_theme_templates_dir() {
    return untrailingslashit( apply_filters( 'geodir_claim_templates_dir', 'geodir_claim_listing' ) );
}

function geodir_claim_locate_template( $template, $template_name, $template_path = '' ) {
	if ( file_exists( $template ) ) {
		return $template;
	}

	$template_path = geodir_claim_theme_templates_dir();
	$default_path = geodir_claim_get_templates_dir();
	$default_template = untrailingslashit( $default_path ) . '/' . $template_name;

	if ( ! file_exists( $default_template ) ) {
		return $template;
	}

    // Look within passed path within the theme - this is priority.
    $template = locate_template(
        array(
            untrailingslashit( $template_path ) . '/' . $template_name,
            $template_name,
        )
    );

    // Get default template
    if ( ! $template ) {
        $template = $default_template;
    }

	return $template;
}

function geodir_claim_check_verification() {
	global $geodirectory;
	if ( ! empty( $_GET['_claim_verify'] ) && ! wp_doing_ajax() ) {
		$current_url = geodir_curPageURL();

		$approved = false;

		$key = sanitize_text_field( $_GET['_claim_verify'] );

		if ( ! empty( $key ) ) {
            do_action( 'geodir_claim_check_verification', $key );

			$claim = GeoDir_Claim_Post::get_item_by_key( $key );
			if ( ! empty( $claim ) && empty( $claim->status ) && ( $gd_post = geodir_get_post_info( $claim->post_id ) ) ) {
				if ( GeoDir_Claim_Post::post_claim_allowed( $gd_post->ID ) && apply_filters( 'geodir_claim_allow_verification', true, $claim, $gd_post ) ) {
					$approved = GeoDir_Claim_Post::approve_claim( $claim );
				}
			}
        }

		if(is_wp_error($approved)){
			$geodirectory->notifications->add('gd_claim_verification',array('type'=>'error','note'=>$approved->get_error_message()));
		}elseif(!$approved){
			global $post;
			$user_id = get_current_user_id();
			if($user_id && !empty($post->post_author) && $user_id == $post->post_author){
				$geodirectory->notifications->add('gd_claim_verification',array('type'=>'warning','note'=>__( 'It looks like you already verified your listing claim.', 'geodir-claim' )));
			}else{
				$geodirectory->notifications->add('gd_claim_verification',array('type'=>'error','note'=>__( 'Something went wrong with your claim listing request.', 'geodir-claim' )));
			}
		}else{
			$redirect_to = remove_query_arg( array( '_claim_verify' ), $current_url );

			$geodirectory->notifications->add(
				'gd_claim_verification',
				array('type'=>'success',
					'note' => wp_sprintf( __( 'Claim listing verified, you are now the owner of this listing! %sClick here%s to see any owner changes.', 'geodir-claim' ), "<a href='$redirect_to'>","</a>" )
				)
			);

//			wp_redirect( $redirect_to );
//			geodir_die();
		}
	}
}

/**
 * Function for display widget callback.
 *
 *
 * @since 2.0.0
 *
 * @param array $instance {
 *      An array display widget arguments.
 *
 * @type string $gd_wgt_showhide Widget display type.
 * @type string $gd_wgt_restrict Widget restrict pages.
 * }
 *
 * @param object $widget Display widget options.
 * @param array $args Widget arguments.
 *
 * @return bool|array $instance
 */
function geodir_claim_widget_display_callback( $instance, $widget, $args ) {
	// Validate claim button display for ninja forms.
	if ( $instance && ! empty( $instance['form_id'] ) && ! empty( $widget->id_base ) && $widget->id_base == 'gd_ninja_forms' ) {
		if ( class_exists( 'Ninja_Forms' ) && ( $form = Ninja_Forms()->form( $instance['form_id'] )->get() ) ) {
			$form_key = $form->get_setting( 'key' );

			if ( $form_key == 'geodirectory_claim' ) {
				global $post;

				if ( ! geodir_claim_show_claim_link( $post->ID ) ) {
					$instance = false; // Don't display link to claim post.
				}
			}
		} else {
			$instance = false; // Don't display if ninja form not found.
		}
	}

	return $instance;
}

function geodir_claim_show_claim_link( $post_ID ) {
	if ( empty( $post_ID ) ) {
		return false;
	}

	$show = GeoDir_Claim_Post::post_claim_allowed( $post_ID ) && ! GeoDir_Claim_Post::is_claimed( $post_ID );

	return apply_filters( 'geodir_claim_show_claim_link', $show, $post_ID );
}

function geodir_claim_post_get_form( $post_ID ) {
	if ( empty( $post_ID ) ) {
		return false;
	}

	$content = apply_filters( 'geodir_claim_pre_post_get_form', NULL, $post_ID );
	if ( $content ) {
		echo $content;
		return;
	}

	$design_style = geodir_design_style();

	$template = $design_style ? $design_style . '/post-claim-form.php' : 'post-claim-form.php' ;

	$content = geodir_get_template_html( $template, array(
		'post_id' => $post_ID,
	) );

	echo $content;
}

function geodir_claim_post_form_hidden_fields( $post_id ) {
	?>
	<input type="hidden" name="action" value="geodir_claim_submit_form" />
	<input type="hidden" name="post_id" value="<?php echo absint( $post_id ); ?>" />
	<input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'geodir_claim_nonce_' . absint( $post_id ) ) ); ?>" />
	<?php
}