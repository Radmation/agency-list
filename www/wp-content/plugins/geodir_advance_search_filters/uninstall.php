<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    GeoDir_Advance_Search_Filters
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb, $plugin_prefix;

wp_clear_scheduled_hook( 'geodir_search_schedule_adjust_business_hours_dst' );

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_advance_search_filters'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_GEODIR_ADVANCE_SEARCH_FILTERS' ) && true === GEODIR_UNINSTALL_GEODIR_ADVANCE_SEARCH_FILTERS ) ) {
	if ( empty( $plugin_prefix ) ) {
		$plugin_prefix = $wpdb->prefix . 'geodir_';
	}

	$advance_search_table = defined( 'GEODIR_ADVANCE_SEARCH_TABLE' ) ? GEODIR_ADVANCE_SEARCH_TABLE : $plugin_prefix . 'custom_advance_search_fields';
	$business_hours_table = defined( 'GEODIR_BUSINESS_HOURS_TABLE' ) ? GEODIR_BUSINESS_HOURS_TABLE : $plugin_prefix . 'business_hours';

	// Delete table
	$wpdb->query( "DROP TABLE IF EXISTS {$advance_search_table}" );
	$wpdb->query( "DROP TABLE IF EXISTS {$business_hours_table}" );

	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		// Remove plugin options
		$remove_options = array(
			'advs_enable_autocompleter',
			'advs_search_suggestions_with',
			'advs_autocompleter_autosubmit',
			'advs_autocompleter_min_chars',
			'advs_autocompleter_max_results',
			'advs_autocompleter_filter_location',
			'advs_enable_autocompleter_near',
			'advs_autocompleter_autosubmit_near',
			'advs_first_load_redirect',
			'advs_autolocate_ask',
			'advs_near_me_dist',
			'advs_search_display_searched_params',
			'advs_search_in_child_cats',
			'advs_ajax_search',
			'advs_search_type',
			'advs_update_results_label',
			'advs_map_search',
			'advs_map_search_type',
			'advs_map_search_default',
			'advs_pagination',
			'uninstall_geodir_advance_search_filters',
		);

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	// Delete core options
	delete_option( 'geodir_advance_search_version' );
	delete_option( 'geodir_advance_search_db_version' );
	delete_option( 'geodiradvancesearch_db_version' );
	
	// Clear any cached data that has been removed.
	wp_cache_flush();
}