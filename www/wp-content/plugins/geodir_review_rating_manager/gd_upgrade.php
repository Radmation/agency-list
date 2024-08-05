<?php
$geodir_reviewratings_db_version = get_option( 'geodir_reviewratings_db_version' );

if ( $geodir_reviewratings_db_version != GEODIR_REVIEWRATING_VERSION ) {
	add_action( 'plugins_loaded', 'geodir_reviewratings_upgrade_all' );

	if ( version_compare( $geodir_reviewratings_db_version, '2.0.0.13', '<=' ) ) {
		add_action( 'init', 'geodir_reviewrating_upgrade_20013', 11 );
	}
}

function geodir_reviewratings_upgrade_all() {
	require_once( 'includes/activator.php' );

	if ( ! GeoDir_Review_Rating_Manager_Activator::is_v2_upgrade() ) {
		GeoDir_Review_Rating_Manager_Activator::gd_review_rating_db_install();
		update_option( 'geodir_reviewratings_db_version',  GEODIR_REVIEWRATING_VERSION );
	}
}

function geodir_reviewrating_upgrade_20013() {
	geodir_add_column_if_not_exist( GEODIR_REVIEWRATING_CATEGORY_TABLE, 'display_order', "int(11) unsigned NOT NULL DEFAULT '0'" );
}