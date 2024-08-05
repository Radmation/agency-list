<?php
/**
 * Upgrade related functions.
 *
 * @since 1.0.0
 * @package GeoDir_Custom_Posts
 * @global object $wpdb WordPress Database object.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

global $wpdb;

if ( get_option( 'geodir_custom_posts_db_version' ) != GEODIR_CP_VERSION ) {
    /**
     * Include custom database table related functions.
     *
     * @since 1.0.0
     * @package GeoDir_Custom_Posts
     */
    add_action( 'plugins_loaded', 'geodir_cp_upgrade_all', 10 );

    // Upgrade old options to new options before loading the rest GD options.
    if ( GEODIR_CP_VERSION <= '2.0.0' ) {
        add_action( 'init', 'geodir_cp_upgrade_200' );
    }
}

/**
 * Handles upgrade for all GeoDirectory Custom Post Types versions.
 *
 * @since 1.0.0
 * @package GeoDir_Custom_Posts
 */
function geodir_cp_upgrade_all() {
}

/**
 * Handles upgrade for all geodirectory versions.
 *
 * @since 2.0.0
 * @package GeoDir_Custom_Posts
 */
function geodir_cp_upgrade_200() {
}

