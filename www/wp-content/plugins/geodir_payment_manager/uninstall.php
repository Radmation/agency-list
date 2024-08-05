<?php
/**
 * GeoDirectory Pricing Manager Uninstall
 *
 * Uninstalling Pricing Manager deletes user roles, pages, tables, and options.
 *
 * @author      AyeCode Ltd
 * @package     GeoDir_Pricing_Manager
 * @version     2.5.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb, $plugin_prefix;

$geodir_settings = get_option( 'geodir_settings' );

// Clear schedules
wp_clear_scheduled_hook( 'geodir_pricing_schedule_expire_check' );
wp_clear_scheduled_hook( 'geodir_pricing_schedule_pre_expiry_reminders' );
wp_clear_scheduled_hook( 'geodir_pricing_wc_save_product' );
 
if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_pricing_manager'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_PRICING_MANAGER' ) && true === GEODIR_UNINSTALL_PRICING_MANAGER ) ) {
	if ( empty( $plugin_prefix ) ) {
		$plugin_prefix = $wpdb->prefix . 'geodir_';
	}

	$packages_table = defined( 'GEODIR_PRICING_PACKAGES_TABLE' ) ? GEODIR_PRICING_PACKAGES_TABLE : $plugin_prefix . 'price';
	$package_meta_table = defined( 'GEODIR_PRICING_PACKAGE_META_TABLE' ) ? GEODIR_PRICING_PACKAGE_META_TABLE : $plugin_prefix . 'pricemeta';
	$post_packages_table = defined( 'GEODIR_PRICING_POST_PACKAGES_TABLE' ) ? GEODIR_PRICING_POST_PACKAGES_TABLE : $plugin_prefix . 'post_packages';
	$custom_fields_table = defined( 'GEODIR_CUSTOM_FIELDS_TABLE' ) ? GEODIR_CUSTOM_FIELDS_TABLE : $plugin_prefix . 'custom_fields';

	// Delete table
	$wpdb->query( "DROP TABLE IF EXISTS `{$packages_table}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$package_meta_table}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$post_packages_table}`" );

	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'pm_listing_expiry',
			'pm_listing_ex_status',
			'pm_paid_listing_status',
			'pm_free_package_renew',
			'pm_cart',
			'email_admin_renew_success',
			'email_admin_renew_success_subject',
			'email_admin_renew_success_body',
			'email_admin_upgrade_success',
			'email_admin_upgrade_success_subject',
			'email_admin_upgrade_success_body',
			'email_user_pre_expiry_reminder',
			'email_bcc_user_pre_expiry_reminder',
			'email_user_pre_expiry_reminder_days',
			'email_user_pre_expiry_reminder_subject',
			'email_user_pre_expiry_reminder_body',
			'email_user_renew_success',
			'email_bcc_user_renew_success',
			'email_user_renew_success_subject',
			'email_user_renew_success_body',
			'email_user_upgrade_success',
			'email_bcc_user_upgrade_success',
			'email_user_upgrade_success_subject',
			'email_user_upgrade_success_body',
			'email_user_post_downgrade',
			'email_bcc_user_post_downgrade',
			'email_user_post_downgrade_subject',
			'email_user_post_downgrade_body',
			'email_user_post_expire',
			'email_bcc_user_post_expire',
			'email_user_post_expire_subject',
			'email_user_post_expire_body',
			'pm_wpi_merge_packages',
			'pm_wc_merge_packages'
		);

		$post_types = ! empty( $geodir_settings['post_types'] ) ? $geodir_settings['post_types'] : array();

		foreach ( $post_types as $post_type => $data ) {
			$detail_table = $plugin_prefix . $post_type . '_detail';

			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$detail_table}'" ) ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$detail_table} SET `package_id` = %d, `expire_date` = %s", array( 0, 'never' ) ) );
				$wpdb->query( $wpdb->prepare( "UPDATE {$detail_table} SET `post_status` = %s WHERE `post_status` = %s", array( 'draft', 'gd-expired' ) ) );
			}
		}

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET `post_status` = %s WHERE `post_status` = %s", array( 'draft', 'gd-expired' ) ) );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE `meta_key` IN( '_gdpm_recurring', '_geodir_reminder_sent', '_gdpm_cancel_at_period_end' )" );

	// Custom fields
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$custom_fields_table}'" ) ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$custom_fields_table} SET `packages` = %d", array( 0 ) ) );
	}

	// Delete core options
	delete_option( 'geodir_pricing_version' );
	delete_option( 'geodir_pricing_db_version' );
	delete_option( 'geodir_payments_db_version' );
	
	// Clear any cached data that has been removed.
	wp_cache_flush();
}