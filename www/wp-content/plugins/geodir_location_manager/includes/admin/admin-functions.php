<?php // Silence is golden
function geodir_location_chunksizes_array() {
	$chunksizes = array( 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000, 100000 );

	/**
	* Filter max entries per export csv file.
	*
	* @since 2.0.0
	* @package GeoDirectory
	*
	* @param string $chunksize Entries options.
	*/
	return apply_filters( 'geodir_location_export_csv_chunksize_options', $chunksizes );
}

function geodir_location_chunksizes_options( $default = 5000, $array = false ) {
	$chunksizes = geodir_location_chunksizes_array();

	if ( $array ) {
		$options = array();
		foreach ( $chunksizes as $value ) {
			$options[ $value ] = $value;
		}
	} else {
		$options = '';
		foreach ( $chunksizes as $value ) {
			$options .= '<option value="' . $value . '" ' . selected( $value, $default, false ) . '>' . $value . '</option>';
		}
	}
	return $options;
}


/**
 * Adds extra address custom field settings.
 *
 * @since 1.0.0
 * @package GeoDirectory_Location_Manager
 *
 * @param $address
 * @param $field_info
 */
function geodir_location_address_extra_admin_fields( $address, $field_info ) {
	$address = wp_parse_args( $address, array(
			'show_city' => 1,
			'city_lable' => __( 'City', 'geodirlocation' ),
			'show_region' => 1, 
			'region_lable' => __( 'Region', 'geodirlocation' ),
			'show_country' => 1,
			'country_lable' => __( 'Country', 'geodirlocation' ),
			'show_neighbourhood' => 1,
			'neighbourhood_lable' => __( 'Neighbourhood', 'geodirlocation' ),
		) 
	);
	$class = geodir_get_option( 'admin_disable_advanced', false ) ? '' : 'gd-advanced-setting';
	echo aui()->input(
		array(
			'id' => 'show_city',
			'name' => 'extra[show_city]',
			'type' => 'checkbox',
			'label_type' => 'horizontal',
			'label_col' => '4',
			'label' => __( 'Show city in address?', 'geodirlocation' ),
			'checked' => $address['show_city'],
			'value' => '1',
			'switch' => 'md',
			'with_hidden' => true,
			'label_force_left' => true,
			'wrap_class' => geodir_advanced_toggle_class(),
			'help_text' => geodir_help_tip( __( 'This will show/hide the city from the address when being displayed.', 'geodirlocation' ) )
		)
	);

	echo aui()->input(
		array(
			'id' => 'city_lable',
			'name' => 'extra[city_lable]',
			'type' => 'text',
			'label_type' => 'top',
			'label' => __( 'City label','geodirlocation' ) . geodir_help_tip( __( 'Enter city field label in address section. (leave as standard if you plan to translate)', 'geodirlocation' ) ),
			'wrap_class' => geodir_advanced_toggle_class(),
			'value' => $address['city_lable'],
			'element_require' => '[%show_city%:checked]'
		)
	);

	echo aui()->input(
		array(
			'id' => 'show_region',
			'name' => 'extra[show_region]',
			'type' => 'checkbox',
			'label_type' => 'horizontal',
			'label_col' => '4',
			'label' => __( 'Show region in address?', 'geodirlocation' ),
			'checked' => $address['show_region'],
			'value' => '1',
			'switch' => 'md',
			'with_hidden' => true,
			'label_force_left' => true,
			'wrap_class' => geodir_advanced_toggle_class(),
			'help_text' => geodir_help_tip( __( 'This will show/hide the region from the address when being displayed.', 'geodirlocation' ) )
		)
	);

	echo aui()->input(
		array(
			'id' => 'region_lable',
			'name' => 'extra[region_lable]',
			'type' => 'text',
			'label_type' => 'top',
			'label' => __( 'Region label','geodirlocation' ) . geodir_help_tip( __( 'Enter region field label in address section. (leave as standard if you plan to translate)', 'geodirlocation' ) ),
			'wrap_class' => geodir_advanced_toggle_class(),
			'value' => $address['region_lable'],
			'element_require' => '[%show_region%:checked]'
		)
	);

	echo aui()->input(
		array(
			'id' => 'show_country',
			'name' => 'extra[show_country]',
			'type' => 'checkbox',
			'label_type' => 'horizontal',
			'label_col' => '4',
			'label' => __( 'Show country in address?', 'geodirlocation' ),
			'checked' => $address['show_country'],
			'value' => '1',
			'switch' => 'md',
			'with_hidden' => true,
			'label_force_left' => true,
			'wrap_class' => geodir_advanced_toggle_class(),
			'help_text' => geodir_help_tip( __( 'This will show/hide the country from the address when being displayed.', 'geodirlocation' ) )
		)
	);

	echo aui()->input(
		array(
			'id' => 'country_lable',
			'name' => 'extra[country_lable]',
			'type' => 'text',
			'label_type' => 'top',
			'label' => __( 'Country label','geodirlocation' ) . geodir_help_tip( __( 'Enter country field label in address section. (leave as standard if you plan to translate)', 'geodirlocation' ) ),
			'wrap_class' => geodir_advanced_toggle_class(),
			'value' => $address['country_lable'],
			'element_require' => '[%show_country%:checked]'
		)
	);

	echo aui()->input(
		array(
			'id' => 'show_neighbourhood',
			'name' => 'extra[show_neighbourhood]',
			'type' => 'checkbox',
			'label_type' => 'horizontal',
			'label_col' => '4',
			'label' => __( 'Show neighbourhood in address?', 'geodirlocation' ),
			'checked' => $address['show_neighbourhood'],
			'value' => '1',
			'switch' => 'md',
			'with_hidden' => true,
			'label_force_left' => true,
			'wrap_class' => geodir_advanced_toggle_class(),
			'help_text' => geodir_help_tip( __( 'This will show/hide the neighbourhood from the address when being displayed.', 'geodirlocation' ) )
		)
	);

	echo aui()->input(
		array(
			'id' => 'neighbourhood_lable',
			'name' => 'extra[neighbourhood_lable]',
			'type' => 'text',
			'label_type' => 'top',
			'label' => __( 'Neighbourhood label','geodirlocation' ) . geodir_help_tip( __( 'Enter neighbourhood field label in address section. (leave as standard if you plan to translate)', 'geodirlocation' ) ),
			'wrap_class' => geodir_advanced_toggle_class(),
			'value' => $address['neighbourhood_lable'],
			'element_require' => '[%show_neighbourhood%:checked]'
		)
	);
}

/**
 * Add the plugin to uninstall settings.
 *
 * @since 2.0.0
 *
 * @return array $settings the settings array.
 * @return array The modified settings.
 */
function geodir_location_uninstall_settings( $settings ) {
    array_pop( $settings );

	$settings[] = array(
		'name'     => __( 'Location Manager', 'geodirlocation' ),
		'desc'     => __( 'Check this box if you would like to completely remove all of its data when Location Manager is deleted.', 'geodirlocation' ),
		'id'       => 'uninstall_geodir_location_manager',
		'type'     => 'checkbox',
	);
	$settings[] = array( 
		'type' => 'sectionend',
		'id' => 'uninstall_options'
	);

    return $settings;
}

function geodir_location_setup_wizard_default_location( $settings ) {
	global $wpdb, $geodirectory;
	
	$city = geodir_get_option('default_location_city');
	$region = geodir_get_option('default_location_region');
	$country = geodir_get_option('default_location_country');

	if ( empty( $city ) || empty( $region ) || empty( $country ) ) {
		return;
	}

	$latitude = geodir_get_option( 'default_location_latitude' );
	$longitude = geodir_get_option( 'default_location_longitude' );

	$location = geodir_get_location_by_names( $city, $region, $country );

	if ( ! empty( $location ) ) {
		$location_id = $location->location_id;

		if ( $location->latitude != $latitude || $location->longitude != $longitude ) {
			$save_data = array(
				'latitude' => $latitude,
				'longitude' => $longitude,
			);

			$wpdb->update( GEODIR_LOCATIONS_TABLE, $save_data, array( 'location_id' => $location_id ) );
		}
	} else {
		$country_slug = geodir_location_country_slug( $country );
		$region_slug = geodir_location_region_slug( $region );
		$city_slug = geodir_location_city_slug( $city, 0, $region_slug );

		$save_data = array(
			'city' 			=> $city,
			'region' 		=> $region,
			'country' 		=> $country,
			'city_slug' 	=> $city_slug,
			'region_slug' 	=> $region_slug,
			'country_slug' 	=> $country_slug,
			'latitude' 		=> $latitude,
			'longitude' 	=> $longitude,
			'is_default' 	=> 1,
		);

		$wpdb->insert( GEODIR_LOCATIONS_TABLE, $save_data );

		$location_id = $wpdb->insert_id;
	}

	if ( $location_id > 0 ) {
		geodir_location_set_default( $location_id );
	}

	wp_cache_delete( 'geodir_get_default_location' );

	$geodirectory->location->set_current();
}

/**
 * Location manager diagnostic tools.
 *
 * @since 2.0.0
 */
function geodir_location_diagnostic_tools( $tools = array() ) {
	$tools['fix_duplicate_location_slugs'] = array(
		'name' => __( 'Fix location duplicate slugs', 'geodirlocation' ),
		'button' => __( 'Run', 'geodirectory' ),
		'desc' => __( 'This will fix location duplicate slugs.', 'geodirlocation' ),
		'callback' => 'geodir_location_fix_duplicate_slugs'
	);

	$tools['merge_post_locations'] = array(
		'name' => __( 'Merge Post Locations', 'geodirlocation' ),
		'button' => __( 'Run', 'geodirectory' ),
		'desc' => __( 'Merge missing locations from listings to locations database table.', 'geodirlocation' ),
		'callback' => 'geodir_location_tool_merge_post_locations'
	);

	return $tools;
}

function geodir_location_fix_duplicate_slugs() {
	global $geodirectory, $wpdb;

	// Fix region duplicate slugs
	$results = $wpdb->get_results( "SELECT COUNT( DISTINCT country_slug ) AS `total`, `region_slug` FROM `" . GEODIR_LOCATIONS_TABLE . "` GROUP BY `region_slug` HAVING `total` > 1 ORDER BY `total` DESC" );

	if ( ! empty( $results ) ) {
		foreach ( $results as $row ) {
			$locations = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . GEODIR_LOCATIONS_TABLE . "` WHERE `region_slug` = %s GROUP BY `country_slug` ORDER BY `is_default` DESC, `location_id` ASC", $row->region_slug ) );
			foreach ( $locations as $i => $location ) {
				if ( $i == 0 ) {
					continue;
				}

				$iso2 = $geodirectory->location->get_country_iso2( $location->country );

				$new_slug = $location->region_slug;
				if ( ! empty( $iso2 ) ) {
					$new_slug .= '-' . strtolower( $iso2 );
				} else {
					$new_slug .= '-' . $location->country_slug;
				}

				if ( $location->region_slug == $new_slug ) {
					continue;
				}

				// Fix in location table
				$wpdb->update( GEODIR_LOCATIONS_TABLE, array( 'region_slug' => $new_slug ), array( 'country_slug' => $location->country_slug, 'region_slug' => $location->region_slug ), array( '%s' ), array( '%s', '%s' ) );

				// Fix in location seo table
				$wpdb->update( GEODIR_LOCATION_SEO_TABLE, array( 'region_slug' => $new_slug ), array( 'region_slug' => $location->region_slug, 'country_slug' => $location->country_slug ), array( '%s' ), array( '%s', '%s' ) );

				// Fix in location term meta table
				$wpdb->update( GEODIR_LOCATION_TERM_META, array( 'location_name' => $new_slug ), array( 'location_name' => $location->region_slug, 'location_type' => 'region', 'region_slug' => $location->region_slug, 'country_slug' => $location->country_slug ), array( '%s' ), array( '%s', '%s', '%s', '%s' ) );
				$wpdb->update( GEODIR_LOCATION_TERM_META, array( 'region_slug' => $new_slug ), array( 'region_slug' => $location->region_slug, 'country_slug' => $location->country_slug ), array( '%s' ), array( '%s', '%s' ) );

				do_action( 'geodir_location_fix_region_duplicate_slug', $location->region_slug, $new_slug, $location );
			}
		}
	}

	// Fix cities duplicate slugs
	$results = $wpdb->get_results( "SELECT COUNT( * ) AS `total`, `city_slug` FROM `" . GEODIR_LOCATIONS_TABLE . "` GROUP BY `city_slug` HAVING `total` > 1 ORDER BY `total` DESC" );

	if ( ! empty( $results ) ) {
		foreach ( $results as $row ) {
			$locations = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . GEODIR_LOCATIONS_TABLE . "` WHERE `city_slug` = %s ORDER BY `is_default` DESC, `location_id` ASC", $row->city_slug ) );
			foreach ( $locations as $i => $location ) {
				if ( $i == 0 ) {
					continue;
				}

				$new_slug = $geodirectory->location->create_city_slug( $location->city_slug, 0, $location->region_slug );
				if ( $location->city_slug == $new_slug ) {
					continue;
				}

				// Fix in location table
				$wpdb->update( GEODIR_LOCATIONS_TABLE, array( 'city_slug' => $new_slug ), array( 'location_id' => $location->location_id ), array( '%s' ), array( '%d' ) );

				// Fix in location seo table
				$wpdb->update( GEODIR_LOCATION_SEO_TABLE, array( 'city_slug' => $new_slug ), array( 'city_slug' => $location->city_slug, 'region_slug' => $location->region_slug, 'country_slug' => $location->country_slug ), array( '%s' ), array( '%s', '%s', '%s' ) );

				// Fix in location term meta table
				$wpdb->update( GEODIR_LOCATION_TERM_META, array( 'location_name' => $new_slug ), array( 'location_name' => $location->city_slug, 'location_type' => 'city', 'region_slug' => $location->region_slug, 'country_slug' => $location->country_slug ), array( '%s' ), array( '%s', '%s', '%s', '%s' ) );

				do_action( 'geodir_location_fix_city_duplicate_slug', $location->city_slug, $new_slug, $location );
			}
		}
	}

	// Clear location duplicate slug notice
	GeoDir_Admin_Notices::remove_notice( 'geodir_location_duplicate_slug_error' );
}

/**
 * Merge missing locations from posts table to location table.
 *
 * @since 2.1.0.6
 */
function geodir_location_tool_merge_post_locations() {
	$merged = (int) geodir_location_merge_post_locations();

	if ( $merged > 0 ) {
		$message = wp_sprintf( _n( '%d location merged.', '%d locations merged.', $merged, 'geodirlocation' ), $merged );
	} else {
		$message = __( 'No location merged.', 'geodirlocation' );
	}

	return $message;
}

/**
 * Setup TimeZone update API field for default location.
 *
 * @since 2.3.7
 *
 * @param string $prefix Location field prefix.
 */
function geodir_location_setup_timezone_api( $prefix ) {
	$tz_prefix = 'default_' . $prefix;
	?>
	if (jQuery('[name="<?php echo $prefix; ?>region"]').length && jQuery('[name="<?php echo $tz_prefix; ?>timezone_string"]').length) {
		if (getState && getState != jQuery('[name="<?php echo $prefix; ?>region"]').data('prev-value')) {
			geodir_fill_timezone('<?php echo $prefix; ?>', '<?php echo $tz_prefix; ?>');
		}
		jQuery('[name="<?php echo $prefix; ?>region"]').attr('data-prev-value', getState);
	}
	<?php
}