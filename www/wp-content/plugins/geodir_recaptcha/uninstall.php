<?php
/**
 * GeoDirectory reCAPTCHA Uninstall
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Recaptcha
 * @version   2.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_recaptcha'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_RECAPTCHA' ) && true === GEODIR_UNINSTALL_RECAPTCHA ) ) {
	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'rc_client_version',
			'rc_v2_site_key',
			'rc_v2_secret_key',
			'rc_v3_site_key',
			'rc_v3_secret_key',
			'rc_invisible_site_key',
			'rc_invisible_secret_key',
			'rc_title',
			'rc_theme',
			'rc_wp_registration',
			'rc_wp_login',
			'rc_add_listing',
			'rc_claim_listing',
			'rc_comments',
			'rc_buddypress',
			'uninstall_geodir_recaptcha'
		);

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	// Clear any cached data that has been removed.
	wp_cache_flush();
}
