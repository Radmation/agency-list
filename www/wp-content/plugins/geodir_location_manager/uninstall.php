<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    GeoDir_Location_Manager
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb, $plugin_prefix;

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_location_manager'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_GEODIR_LOCATION_MANAGER' ) && true === GEODIR_UNINSTALL_GEODIR_LOCATION_MANAGER ) ) {
	if ( empty( $plugin_prefix ) ) {
		$plugin_prefix = $wpdb->prefix . 'geodir_';
	}

	$locations_table = defined( 'GEODIR_LOCATIONS_TABLE' ) ? GEODIR_LOCATIONS_TABLE : $plugin_prefix . 'post_locations';
	$location_seo_table = defined( 'GEODIR_LOCATION_SEO_TABLE' ) ? GEODIR_LOCATION_SEO_TABLE : $plugin_prefix . 'location_seo';
	$location_term_meta_table = defined( 'GEODIR_LOCATION_TERM_META' ) ? GEODIR_LOCATION_TERM_META : $plugin_prefix . 'term_meta';
	$neighbourhoods_table = defined( 'GEODIR_NEIGHBOURHOODS_TABLE' ) ? GEODIR_NEIGHBOURHOODS_TABLE : $plugin_prefix . 'post_neighbourhood';
	$attachments_table = defined( 'GEODIR_ATTACHMENT_TABLE' ) ? GEODIR_ATTACHMENT_TABLE : $plugin_prefix . 'attachments';
	$reviews_table = defined( 'GEODIR_REVIEW_TABLE' ) ? GEODIR_REVIEW_TABLE : $plugin_prefix . 'post_review';

	// Delete table
	$wpdb->query( "DROP TABLE IF EXISTS {$locations_table}" );
	$wpdb->query( "DROP TABLE IF EXISTS {$location_seo_table}" );
	$wpdb->query( "DROP TABLE IF EXISTS {$location_term_meta_table}" );
	$wpdb->query( "DROP TABLE IF EXISTS {$neighbourhoods_table}" );

	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		// Remove plugin options
		$remove_options = array(
			'lm_home_go_to',
			'lm_default_country',
			'lm_hide_country_part',
			'lm_selected_countries',
			'lm_default_region',
			'lm_hide_region_part',
			'lm_selected_regions',
			'lm_default_city',
			'lm_selected_cities',
			'lm_enable_neighbourhoods',
			'lm_manual_neighbourhood',
			'lm_location_address_fill',
			'lm_location_dropdown_all',
			'lm_set_address_disable',
			'lm_set_pin_disable',
			'lm_location_no_of_records',
			'lm_enable_search_autocompleter',
			'lm_enable_search_autocompleter',
			'lm_hide_map_near_me',
			'lm_disable_nearest_cities',
			'lm_disable_term_auto_count',
			'lm_desc_no_editor',
			'lm_sitemap_exclude_location',
			'lm_disable_nearest_cities',
			'lm_sitemap_exclude_cats',
			'lm_sitemap_exclude_tags',
			'lm_everywhere_in_country_dropdown',
			'lm_everywhere_in_region_dropdown',
			'lm_everywhere_in_city_dropdown',
			'lm_search_bound_sw',
			'lm_search_bound_ne',
			'geodir_update_locations_default_options',
			'location_sitemaps_locations',
			'uninstall_geodir_location_manager',
		);

		$post_types = ! empty( $geodir_settings['post_types'] ) ? $geodir_settings['post_types'] : array();
		$default_city = ! empty( $geodir_settings['default_location_city'] ) ? $geodir_settings['default_location_city'] : '';
		$default_region = ! empty( $geodir_settings['default_location_region'] ) ? $geodir_settings['default_location_region'] : '';
		$default_country = ! empty( $geodir_settings['default_location_country'] ) ? $geodir_settings['default_location_country'] : '';

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type => $data ) {
				$detail_table = $plugin_prefix . $post_type . '_detail';

				if ( ! empty( $default_city ) && ! empty( $default_region ) && ! empty( $default_country ) ) {
					// Delete listing data
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$detail_table} WHERE city != %s AND region != %s AND country != %s", array( $default_city, $default_region, $default_country ) ) );

					// Delete orphan posts
					$wpdb->query( $wpdb->prepare( "DELETE posts FROM {$wpdb->posts} posts LEFT JOIN {$detail_table} detail ON detail.post_id = posts.ID WHERE posts.post_type = %s AND detail.post_id IS NULL", array( $post_type ) ) );
				}

				$wpdb->query( "ALTER TABLE {$detail_table} DROP neighbourhood" );
			}
		}

		// Delete location switcher menu
		$wpdb->query( "DELETE posts FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} meta ON posts.ID = meta.post_id WHERE posts.post_type= 'nav_menu_item' AND meta.meta_key = '_menu_item_url' AND meta.meta_value = '#location-switcher'" );

		// Delete orphan attachment.
		$wpdb->query( "DELETE post1 FROM {$wpdb->posts} post1 LEFT JOIN {$wpdb->posts} post2 ON post1.post_parent = post2.ID WHERE post1.post_parent > 0 AND post1.post_type = 'attachment' AND post2.ID IS NULL" );

		// Delete orphan post meta
		$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL" );

		// Delete orphan relationships
		$wpdb->query( "DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} posts ON posts.ID = tr.object_id WHERE posts.ID IS NULL" );
		
		// Delete orphan comments
		$wpdb->query( "DELETE comments FROM {$wpdb->comments} AS comments LEFT JOIN {$wpdb->posts} AS posts ON posts.ID = comments.comment_post_ID WHERE posts.ID IS NULL" );
		$wpdb->query( "DELETE meta FROM {$wpdb->commentmeta} meta LEFT JOIN {$wpdb->comments} comments ON comments.comment_ID = meta.comment_id WHERE comments.comment_ID IS NULL" );

		// Delete orphan post attachments
		$wpdb->query( "DELETE attachments FROM {$attachments_table} attachments LEFT JOIN {$wpdb->posts} posts ON posts.ID = attachments.post_id WHERE posts.ID IS NULL" );
		
		// Delete term meta
		$wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE 'gd_desc_co_%' OR meta_key LIKE 'gd_desc_re_%' OR meta_key LIKE 'gd_desc_id_%' OR meta_key = 'gd_desc_custom'" );
		
		// Delete orphan post reviews
		$wpdb->query( "DELETE reviews FROM {$reviews_table} reviews LEFT JOIN {$wpdb->posts} posts ON posts.ID = reviews.post_id WHERE posts.ID IS NULL" );

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	// Delete core options
	delete_option( 'geodir_location_version' );
	delete_option( 'geodir_location_db_version' );
	delete_option( 'geodirlocation_db_version' );
	
	// Clear any cached data that has been removed.
	wp_cache_flush();
}