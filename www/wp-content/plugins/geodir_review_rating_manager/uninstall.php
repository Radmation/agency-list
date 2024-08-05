<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    GeoDir_Review_Rating_Manager
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb, $plugin_prefix;

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_review_rating_manager'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_GEODIR_REVIEW_RATING_MANAGER' ) && true === GEODIR_UNINSTALL_GEODIR_REVIEW_RATING_MANAGER ) ) {
	if ( empty( $plugin_prefix ) ) {
		$plugin_prefix = $wpdb->prefix . 'geodir_';
	}

	$post_review_table = defined( 'GEODIR_REVIEWRATING_POSTREVIEW_TABLE' ) ? GEODIR_REVIEWRATING_POSTREVIEW_TABLE : $plugin_prefix . 'post_review';
	$comments_reviews_table = defined( 'GEODIR_COMMENTS_REVIEWS_TABLE' ) ? GEODIR_COMMENTS_REVIEWS_TABLE : $plugin_prefix . 'comments_reviews';
	$rating_category_table = defined( 'GEODIR_REVIEWRATING_CATEGORY_TABLE' ) ? GEODIR_REVIEWRATING_CATEGORY_TABLE : $plugin_prefix . 'rating_category';
	$rating_style_table = defined( 'GEODIR_REVIEWRATING_STYLE_TABLE' ) ? GEODIR_REVIEWRATING_STYLE_TABLE : $plugin_prefix . 'rating_style';

    // Delete table
	$wpdb->query( "DROP TABLE IF EXISTS `{$comments_reviews_table}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$rating_category_table}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$rating_style_table}`" );

	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'rr_enable_rating',
			'rr_enable_images',
			'rr_enable_rate_comment',
			'rr_enable_sorting',
			'rr_image_limit',
			'uninstall_geodir_review_rating_manager',
		);

		$post_types = ! empty( $geodir_settings['post_types'] ) ? $geodir_settings['post_types'] : array();

		foreach ( $post_types as $post_type => $data ) {
			$detail_table = $plugin_prefix . $post_type . '_detail';

			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$detail_table}'" ) && $wpdb->get_var( "SHOW COLUMNS FROM '{$detail_table}' WHERE field = 'ratings'" ) ) {
				$wpdb->query( "ALTER TABLE {$detail_table} DROP ratings" );
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

	// Delete core options
	delete_option( 'geodir_reviewrating_version' );
	delete_option( 'geodir_reviewrating_db_version' );
	delete_option( 'geodir_reviewratings_db_version' );

	// Clear any cached data that has been removed.
	wp_cache_flush();
}