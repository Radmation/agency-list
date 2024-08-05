<?php
/**
 * Email To Listing Contact Email Address
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/emails/email-user_claim_nudge.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    Geodir_Claim_Listing
 * @version    2.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'geodir_email_header', $email_heading, $email_name, $email_vars, $plain_text, $sent_to_admin );

if ( ! empty( $message_body ) ) {
	echo wpautop( wptexturize( $message_body ) );
}

do_action( 'geodir_email_footer', $email_name, $email_vars, $plain_text, $sent_to_admin );