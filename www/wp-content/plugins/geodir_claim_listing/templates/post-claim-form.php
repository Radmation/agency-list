<?php
/**
 * Single Listing Claim Form
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/post-claim-form.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://wpgeodirectory.com/docs-v2/faq/customizing/#templates
 * @package    Geodir_Claim_Listing
 * @version    2.1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<form method="post" class="geodir-post-claim-form">
	<?php do_action( 'geodir_claim_post_form_hidden_fields', $post_id ); ?>

	<div class="geodir-claim-form-header">
		<div class="ggeodir_form_row clearfix geodir-claim-field-header"><h5 class="geodir-fieldset-row"><?php echo wp_sprintf( __( 'Claim Listing: %s', 'geodir-claim' ), get_the_title( $post_id ) ); ?></h5></div>
	</div>

	<div class="geodir-claim-form-fields">
		<div class="geodir_form_row clearfix geodir-claim-field-info"><p><?php _e( 'Fill out the form below, we will verify your claim and email you confirmation.', 'geodir-claim' ); ?></p></div>

		<?php do_action( 'geodir_claim_post_form_before_fields', $post_id ); ?>

		<div class="required_field geodir_form_row clearfix geodir-claim-field-fullname">
			<label for="gd_claim_user_fullname"><?php _e( 'Full Name', 'geodir-claim' ); ?> <span>*</span></label>
			<input name="gd_claim_user_fullname" id="gd_claim_user_fullname" type="text" class="geodir_textfield" required>
		</div>
		<div class="required_field geodir_form_row clearfix geodir-claim-field-number">
			<label for="gd_claim_user_number"><?php _e( 'Phone', 'geodir-claim' ); ?> <span>*</span></label>
			<input name="gd_claim_user_number" id="gd_claim_user_number" type="text" class="geodir_textfield" required>
		</div>
		<div class="required_field geodir_form_row clearfix geodir-claim-field-position">
			<label for="gd_claim_user_position"><?php _e( 'Position in Business', 'geodir-claim' ); ?> <span>*</span></label>
			<input name="gd_claim_user_position" id="gd_claim_user_position" type="text" class="geodir_textfield" required>
		</div>
		<div class="required_field geodir_form_row clearfix geodir-claim-field-comments">
			<label for="gd_claim_user_comments"><?php _e( 'Message', 'geodir-claim' ); ?> <span>*</span></label>
			<textarea name="gd_claim_user_comments" id="gd_claim_user_position" class="geodir_textarea" required><?php _e( 'Hi I am the owner of this business and i would like to claim it.', 'geodir-claim' ); ?></textarea>
		</div>

		<?php do_action( 'geodir_claim_post_form_after_fields', $post_id ); ?>
	</div>

	<div class="geodir-claim-form-footer">
		<?php do_action( 'geodir_claim_post_form_before_button', $post_id ); ?>

		<div class="geodir_form_row clearfix geodir-claim-field-button"><button type="submit" class="btn btn-default geodir-post-claim-button"><?php _e( 'Send', 'geodir-claim' ); ?></button></div>

		<?php do_action( 'geodir_claim_post_form_after_button', $post_id ); ?>
	</div>
</form>