<?php
/**
 * Uninstall GeoDirectory Ajax Duplicate Alert.
 *
 * Uninstalling GeoDirectory Ajax Duplicate Alert deletes the plugin options.
 *
 * @package GeoDirectory_Ajax_Duplicate_Alert.
 *
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Delete Duplicate alert option when plugin uninstall.
 *
 * @since 1.2.1
 */
$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['geodir_uninstall_ajax_duplicate_alert'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_AJAX_DUPLICATE_ALERT' ) && true === GEODIR_UNINSTALL_AJAX_DUPLICATE_ALERT ) ) {
	if ( isset( $geodir_settings[ 'duplicate_alert' ] ) ) {
		unset( $geodir_settings[ 'duplicate_alert' ] );

		// Update options.
		update_option( 'geodir_settings', $geodir_settings );
	}
}