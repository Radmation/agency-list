<?php
/**
 * Single Listing Claim Form
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/post-claim-form.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://wpgeodirectory.com/docs-v2/faq/customizing/#templates
 * @package    Geodir_Claim_Listing
 * @version    2.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $aui_bs5;

?>
<form method="post" class="geodir-post-claim-form form-sm">
	<?php do_action( 'geodir_claim_post_form_hidden_fields', $post_id ); ?>

	<div class="geodir-claim-form-fields">
		<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>"><p class="text-muted"><?php _e( 'Fill out the form below, we will verify your claim and email you confirmation.', 'geodir-claim' ); ?></p></div>
		<?php 
		do_action( 'geodir_claim_post_form_before_fields', $post_id );

		// Full Name
		echo aui()->input(
			array(
				'id' => 'gd_claim_user_fullname',
				'name' => 'gd_claim_user_fullname',
				'required' => true,
				'label' => __( 'Full Name', 'geodir-claim' ) . ' <span class="text-danger">*</span>',
				'label_type' => 'vertical',
				'type' => 'text'
			)
		);

		// Phone
		echo aui()->input(
			array(
				'id' => 'gd_claim_user_number',
				'name' => 'gd_claim_user_number',
				'required' => true,
				'label' => __( 'Phone', 'geodir-claim' ) . ' <span class="text-danger">*</span>',
				'label_type' => 'vertical',
				'type' => 'text'
			)
		);

		// Position
		echo aui()->input(
			array(
				'id' => 'gd_claim_user_position',
				'name' => 'gd_claim_user_position',
				'required' => true,
				'label' => __( 'Position in Business', 'geodir-claim' ) . ' <span class="text-danger">*</span>',
				'label_type' => 'vertical',
				'type' => 'text'
			)
		);

		// Message
		echo aui()->textarea(
			array(
				'id' => 'gd_claim_user_comments',
				'name' => 'gd_claim_user_comments',
				'required' => true,
				'label' => __( 'Message', 'geodir-claim' ) . ' <span class="text-danger">*</span>',
				'label_type' => 'vertical',
				'no_wrap' => false,
				'rows' => 2,
				'wysiwyg' => false,
				'value' => __( 'Hi I am the owner of this business and i would like to claim it.', 'geodir-claim' )
			)
		);

		do_action( 'geodir_claim_post_form_after_fields', $post_id );

		?>
	</div>
	<div class="geodir-claim-form-footer">
		<?php
		do_action( 'geodir_claim_post_form_before_button', $post_id );

		$button = aui()->button(
			array(
				'type' => 'submit',
				'class' => 'btn btn-primary btn-sm geodir-post-claim-button',
				'content' => __( 'Send', 'geodir-claim' ),
				'no_wrap' => true
			)
		);

		echo AUI_Component_Input::wrap(
			array(
				'content' => $button,
				'class' => ( $aui_bs5 ? 'd-grid' : 'form-group' ). ' text-center mb-0 pt-3'
			)
		);

		do_action( 'geodir_claim_post_form_after_button', $post_id );
		?>
	</div>
</form>