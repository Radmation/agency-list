<?php
/**
 * Handle import and exports.
 *
 * @author   AyeCode
 * @category Admin
 * @package  GeoDirectory_Location_manager/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * GeoDir_Admin_Import_Export Class.
 */
class GeoDir_Location_Admin_Import_Export {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'geodir_get_sections_import-export', array( $this, 'import_export_sections' ), 10, 1 );
		add_filter( 'geodir_get_settings_import-export', array( $this, 'import_export_settings' ), 10, 2 );
		add_action( 'geodir_location_import_js_stats', array( $this, 'set_custom_js_errors' ) );

		// Locations
		add_filter( 'geodir_admin_field_import_export_locations', array( $this, 'import_export_locations' ), 10, 1 );

		add_filter('geodir_ajax_prepare_export_locations', array( $this, 'prepare_export_locations' ) );
		add_filter('geodir_ajax_export_locations', array( $this, 'export_locations' ) );
		add_filter('geodir_location_imex_get_locations_where', array( $this, 'set_filter' ), 10, 3 );
		
		add_filter('geodir_ajax_import_location', array( $this, 'import_locations' ) );
		
		// Locations + CPT Description
		add_filter( 'geodir_admin_field_import_export_cpt_locations', array( $this, 'import_export_cpt_locations' ), 10, 1 );
		add_filter( 'geodir_ajax_prepare_export_cpt_locations', array( $this, 'prepare_export_cpt_locations' ) );
		add_filter( 'geodir_ajax_export_cpt_locations', array( $this, 'export_cpt_locations' ) );
		add_filter( 'geodir_ajax_import_cpt_location', array( $this, 'import_cpt_locations' ) );

		// Category + Locations Description
		add_filter( 'geodir_admin_field_import_export_cat_locations', array( $this, 'import_export_cat_locations' ), 10, 1 );
		add_filter('geodir_ajax_prepare_export_cat_locations', array( $this, 'prepare_export_cat_locations' ) );
		add_filter('geodir_ajax_export_cat_locations', array( $this, 'export_cat_locations' ) );
		add_filter('geodir_ajax_import_cat_location', array( $this, 'import_cat_locations' ) );
		
		// Neighbourhoods
		add_filter( 'geodir_admin_field_import_export_neighbourhoods', array( $this, 'import_export_neighbourhoods' ), 10, 1 );

		add_filter('geodir_ajax_prepare_export_neighbourhoods', array( $this, 'prepare_export_neighbourhoods' ) );
		add_filter('geodir_ajax_export_neighbourhoods', array( $this, 'export_neighbourhoods' ) );

		add_filter('geodir_ajax_import_neighbourhood', array( $this, 'import_neighbourhoods' ) );
	}
	
	public function import_export_sections( $sections ) {
		$sections['locations'] = __( 'Locations', 'geodirlocation' );
		$sections['cpt_locations'] = __( 'Locations + CPT Description', 'geodirlocation' );
		$sections['cat_locations'] = __( 'Category + Locations Description', 'geodirlocation' );
		if ( GeoDir_Location_Neighbourhood::is_active() ) {
			$sections['neighbourhoods'] = __( 'Neighbourhoods', 'geodirlocation' );
		}
		return $sections;
	}

	public function import_export_settings( $settings, $current_section ) {
		if ( $current_section == 'locations' ) {
			$settings = apply_filters( 'geodir_import_export_locations_settings', array(
				/*array(
					'title' 	=> '',
					'type' 		=> 'title',
					'id' 		=> 'import_export_options',
				),*/

				array(
					'id'       => 'import_export_locations',
					'type'     => 'import_export_locations',
				),

				/*array(
					'type' 	=> 'sectionend',
					'id' 	=> 'import_export_options',
				),*/

			));
		} else if ( $current_section == 'cpt_locations' ) {
			$settings = apply_filters( 'geodir_import_export_cpt_locations_settings', array(
				/*array(
					'title' 	=> '',
					'type' 		=> 'title',
					'id' 		=> 'import_export_options',
				),*/

				array(
					'id'       => 'import_export_cpt_locations',
					'type'     => 'import_export_cpt_locations',
				),

				/*array(
					'type' 	=> 'sectionend',
					'id' 	=> 'import_export_options',
				),*/

			));
		} else if ( $current_section == 'cat_locations' ) {
			$settings = apply_filters( 'geodir_import_export_cat_locations_settings', array(
				/*array(
					'title' 	=> '',
					'type' 		=> 'title',
					'id' 		=> 'import_export_options',
				),*/

				array(
					'id'       => 'import_export_cat_locations',
					'type'     => 'import_export_cat_locations',
				),

				/*array(
					'type' 	=> 'sectionend',
					'id' 	=> 'import_export_options',
				),*/

			));
		} else if ( $current_section == 'neighbourhoods' ) {
			$settings = apply_filters( 'geodir_import_export_neighbourhoods_settings', array(
				/*array(
					'title' 	=> '',
					'type' 		=> 'title',
					'id' 		=> 'import_export_options',
				),*/

				array(
					'id'       => 'import_export_neighbourhoods',
					'type'     => 'import_export_neighbourhoods',
				),

				/*array(
					'type' 	=> 'sectionend',
					'id' 	=> 'import_export_options',
				),*/

			));
		}
		return $settings;
	}

	public static function import_export_locations( $setting ) {
		?>
		<tr valign="top" class="<?php echo ( ! empty( $value['advanced'] ) ? 'gd-advanced-setting' : '' ); ?>">
			<td class="forminp" colspan="2">
				<?php /**
				 * Contains template for import/export locations.
				 *
				 * @since 2.0.0
				 */
				include_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/views/html-import-export-locations.php' );
				?>
			</td>
		</tr>
		<?php
	}
	
	public static function import_export_cat_locations( $setting ) {
		?>
		<tr valign="top">
			<td class="forminp" colspan="2">
				<?php /**
				 * Contains template for import/export category + locations description.
				 *
				 * @since 2.0.0
				 */
				include_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/views/html-import-export-cat-locations.php' );
				?>
			</td>
		</tr>
		<?php
	}
	
	public static function import_export_neighbourhoods( $setting ) {
		?>
		<tr valign="top">
			<td class="forminp" colspan="2">
				<?php /**
				 * Contains template for import/export geodirectory settings.
				 *
				 * @since 2.0.0
				 */
				include_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/views/html-import-export-neighbourhoods.php' );
				?>
			</td>
		</tr>
		<?php
	}
	
	public static function set_custom_js_errors() {
		if ( ! empty( $_GET['section'] ) ) {
			$errors = '';
			switch ( $_GET['section'] ) {
				case 'locations':
					$errors .= " msgInvalid = '" . addslashes( __( '%d item(s) could not be added due to blank/invalid value for "city, region, country, latitude, longitude".', 'geodirlocation' ) ) . "';";
				break;
				case 'cat_locations':
					$errors .= " msgInvalid = '" . addslashes( __( '%d item(s) could not be added due to blank/invalid value for "term_id, country_slug / region_slug / city_slug".', 'geodirlocation' ) ) . "';";
				break;
				case 'neighbourhoods':
					$errors .= " msgInvalid = '" . addslashes( __( '%d item(s) could not be added due to blank/invalid value for "neighbourhood_name, latitude, longitude".', 'geodirlocation' ) ) . "';";
				break;
			}
			echo $errors;
		}
	}

	public static function prepare_export_locations() {
		global $wpdb;

		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();

		$where = array();
		if ( ! empty( $data['country'] ) ) {
			$where[] = $wpdb->prepare( 'country = %s', wp_unslash( $data['country'] ) );
		}

		$sql = "SELECT COUNT(location_id) FROM " . GEODIR_LOCATIONS_TABLE;
		if ( ! empty( $where ) ) {
			$sql .= " WHERE " . implode( " AND ", $where );
		}
		$count = $wpdb->get_var( $sql );

		$json = array( 'total' => (int)$count );

		return $json;
	}

	public static function set_filter( $where, $per_page, $page_no ) {
		global $wpdb;

		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();

		if ( ! empty( $data['country'] ) ) {
			$where .= $wpdb->prepare( " AND l.country = %s", wp_unslash( $data['country'] ) );
		}

		return $where;
	}

	public static function import_locations() {
		$limit     = isset( $_POST['limit'] ) && $_POST['limit'] ? (int) $_POST['limit'] : 1;
		$processed = isset( $_POST['processed'] ) ? (int) $_POST['processed'] : 0;

		$processed ++;
		$rows = GeoDir_Admin_Import_Export::get_csv_rows( $processed, $limit );

		if ( ! empty( $rows ) ) {
			$created = 0;
			$updated = 0;
			$skipped = 0;
			$invalid = 0;
			$images  = 0;

			$update_or_skip = isset( $_POST['_ch'] ) && $_POST['_ch'] == 'update' ? 'update' : 'skip';
			$log_error = __( 'GD IMPORT LOCATIONS [ROW %d]:', 'geodirlocation' );

			$i = 0;
			foreach ( $rows as $row ) {
				$i++;
				$line_no = $processed + $i;
				$line_error = wp_sprintf( $log_error, $line_no );
				$row = self::validate_location( $row );
				
				if ( empty( $row ) ) {
					geodir_error_log( $line_error . ' ' . __( 'data is empty.', 'geodirlocation' ) );
					$invalid++;
					continue;
				}

				$exists = empty( $row['location_id'] ) && ! empty( $row['city_slug'] ) ? geodir_get_location_by_slug( 'city', array( 'city_slug' => $row['city_slug'] ) ) : NULL;

				if ( $update_or_skip == 'skip' && ( ! empty( $row['location_id'] ) || ! empty( $exists ) ) ) {
					$skipped++;
					continue;
				}
				
				$valid = true;
				if ( empty( $row['city'] ) || empty( $row['region'] ) || empty( $row['country'] ) || empty( $row['latitude'] ) || empty( $row['longitude'] ) ) {
					$valid = false;
					geodir_error_log( $line_error . ' ' . __( 'blank value for city / region / country / latitude / longitude.', 'geodirlocation' ) );
				}
				
				if ( ! $valid ) {
					$invalid++;
					continue;
				}

				do_action( 'geodir_location_pre_import_location_data', $row );

				if ( ! empty( $row['location_id'] ) || ! empty( $row['city_slug'] ) ) {
					if ( (int)$row['location_id'] > 0 && ( $location = geodir_get_location_by_id( '', (int)$row['location_id'] ) ) ) {
						if ( $location_id = geodir_location_update_city( $row, true, $location ) ) {
							$updated++;
						} else {
							$invalid++;
							geodir_error_log( $line_error . ' ' . __( 'fail to update location.', 'geodirlocation' ) );
						}
					} else if ( !empty( $row['city_slug'] ) && ( $location = geodir_get_location_by_slug( 'city', array( 'city_slug' => $row['city_slug'] ) ) ) ) {
						$row['location_id'] = (int)$location->location_id;
                                    
						if ( $location = geodir_get_location_by_slug( 'city', array( 'city_slug' => $row['city_slug'], 'country' => $row['country'], 'region' => $row['region'] ) ) ) {
							$row['location_id'] = (int)$location->location_id;
						} else if ( $location = geodir_get_location_by_slug( 'city', array( 'city_slug' => $row['city_slug'], 'region' => $row['region'] ) ) ) {
							$row['location_id'] = (int)$location->location_id;
						} else if ( $location = geodir_get_location_by_slug( 'city', array( 'city_slug' => $row['city_slug'], 'country' => $row['country'] ) ) ) {
							$row['location_id'] = (int)$location->location_id;
						}
						
						if ( $location_id = geodir_location_update_city( $row, true, $location ) ) {
							$updated++;
						} else {
							$invalid++;
							geodir_error_log( $line_error . ' ' . __( 'fail to update location.', 'geodirlocation' ) );
						}
					} else {
						if ( $location_id = geodir_location_insert_city( $row, true ) ) { // inserted
							$created++;
						} else { // error
							$invalid++;
							geodir_error_log( $line_error . ' ' . __( 'invalid data.', 'geodirlocation' ) );
						}
					}
				// insert
				} else {
					if ( $location_id = geodir_location_insert_city( $row, true ) ) { // inserted
						$created++;
					} else { // error
						$invalid++;
						geodir_error_log( $line_error . ' ' . __( 'fail to import location.', 'geodirlocation' ) );
					}
				}
			}

		} else {
			return new WP_Error( 'gd-csv-empty', __( "No data found in csv file.", "geodirectory" ) );
		}

		return array(
			"processed" => $processed,
			"created"   => $created,
			"updated"   => $updated,
			"skipped"   => $skipped,
			"invalid"   => $invalid,
			"images"    => $images,
			"ID"        => 0,
		);
	}

	public static function export_locations() {
		global $wp_filesystem;

		$nonce          = isset( $_REQUEST['_nonce'] ) ? $_REQUEST['_nonce'] : null;
		$count 			= isset( $_REQUEST['_c'] ) ? absint( $_REQUEST['_c'] ) : 0;
		$chunk_per_page = !empty( $_REQUEST['_n'] ) ? absint( $_REQUEST['_n'] ) : 5000;
		$chunk_page_no  = isset( $_REQUEST['_p'] ) ? absint( $_REQUEST['_p'] ) : 1;
		$csv_file_dir   = GeoDir_Admin_Import_Export::import_export_cache_path( false );
		
		$file_name = 'gd_locations_' . date( 'dmyHi' );

		$file_url_base  = GeoDir_Admin_Import_Export::import_export_cache_path() . '/';
		$file_url       = $file_url_base . $file_name . '.csv';
		$file_path      = $csv_file_dir . '/' . $file_name . '.csv';
		$file_path_temp = $csv_file_dir . '/locations_' . $nonce . '.csv';

		$chunk_file_paths = array();

		if ( isset( $_REQUEST['_st'] ) ) {
			$line_count = (int) GeoDir_Admin_Import_Export::file_line_count( $file_path_temp );
			$percentage = count( $count ) > 0 && $line_count > 0 ? ceil( $line_count / $count ) * 100 : 0;
			$percentage = min( $percentage, 100 );

			$json['percentage'] = $percentage;

			return $json;
		} else {
			if ( ! $count > 0 ) {
				$json['error'] = __( 'No records to export.', 'geodirlocation' );
			} else {
				$total = $count;
				if ( $chunk_per_page > $count ) {
					$chunk_per_page = $count;
				}
				$chunk_total_pages = ceil( $total / $chunk_per_page );

				$j      = $chunk_page_no;
				$rows 	= geodir_location_imex_locations_data( $chunk_per_page, $j );

				$per_page = 500;
				if ( $per_page > $chunk_per_page ) {
					$per_page = $chunk_per_page;
				}
				$total_pages = ceil( $chunk_per_page / $per_page );

				for ( $i = 0; $i <= $total_pages; $i ++ ) {
					$save_rows = array_slice( $rows, ( $i * $per_page ), $per_page );

					$clear = $i == 0 ? true : false;
					GeoDir_Admin_Import_Export::save_csv_data( $file_path_temp, $save_rows, $clear );
				}

				if ( $wp_filesystem->exists( $file_path_temp ) ) {
					$chunk_page_no   = $chunk_total_pages > 1 ? '-' . $j : '';
					$chunk_file_name = $file_name . $chunk_page_no . '_' . substr( geodir_rand_hash(), 0, 8 ) . '.csv';
					$file_path       = $csv_file_dir . '/' . $chunk_file_name;
					$wp_filesystem->move( $file_path_temp, $file_path, true );

					$file_url           = $file_url_base . $chunk_file_name;
					$chunk_file_paths[] = array(
						'i' => $j . '.',
						'u' => $file_url,
						's' => size_format( filesize( $file_path ), 2 )
					);
				}

				if ( ! empty( $chunk_file_paths ) ) {
					$json['total'] = $count;
					$json['files'] = $chunk_file_paths;
				} else {
					$json['error'] = __( 'ERROR: Could not create csv file. This is usually due to inconsistent file permissions.', 'geodirlocation' );
				}
			}
		}

		return $json;
	}
	
	public static function validate_location( $data ) {
		$data = array_map( 'trim', $data );

		$location_data 								= array();
		$location_data['location_id'] 				= isset( $data['location_id'] ) ? absint( $data['location_id'] ) : '';
		$location_data['latitude'] 					= isset( $data['latitude'] ) ? sanitize_text_field( $data['latitude'] ) : '';
		$location_data['longitude'] 				= isset( $data['longitude'] ) ? sanitize_text_field( $data['longitude'] ) : '';
		$location_data['city'] 						= isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : '';
		$location_data['city_slug'] 				= isset( $data['city_slug'] ) ? sanitize_text_field( $data['city_slug'] ) : '';
		$location_data['region'] 					= isset( $data['region'] ) ? sanitize_text_field( $data['region'] ) : '';
		$location_data['country'] 					= isset( $data['country'] ) ? sanitize_text_field( $data['country'] ) : '';
		$location_data['city_meta_title'] 			= isset( $data['city_meta_title'] ) ? sanitize_text_field( $data['city_meta_title'] ) : '';
		$location_data['city_meta_desc'] 			= isset( $data['city_meta_desc'] ) ? sanitize_text_field( $data['city_meta_desc'] ) : '';
		$location_data['city_desc']					= isset( $data['city_desc'] ) ? $data['city_desc'] : '';
		$location_data['region_meta_title'] 		= isset( $data['region_meta_title'] ) ? sanitize_text_field( $data['region_meta_title'] ) : '';
		$location_data['region_meta_desc'] 			= isset( $data['region_meta_desc'] ) ? sanitize_text_field( $data['region_meta_desc'] ) : '';
		$location_data['region_desc'] 				= isset( $data['region_desc'] ) ? $data['region_desc'] : '';
		$location_data['country_meta_title'] 		= isset( $data['country_meta_title'] ) ? sanitize_text_field( $data['country_meta_title'] ) : '';
		$location_data['country_meta_desc'] 		= isset( $data['country_meta_desc'] ) ? sanitize_text_field( $data['country_meta_desc'] ) : '';
		$location_data['country_desc'] 				= isset( $data['country_desc'] ) ? $data['country_desc'] : '';

		return apply_filters( 'geodir_location_import_validate_location', $location_data, $data );
	}

	public static function validate_cat_location( $row ) {
		$row = array_map( 'trim', $row );

		$data 								= array();
		$data['term_id'] 					= isset( $row['term_id'] ) ? absint( $row['term_id'] ) : '';
		$data['term_name'] 					= isset( $row['term_name'] ) ? sanitize_text_field( $row['term_name'] ) : '';
		if ( isset( $row['enable_default_for_all_locations'] ) ) {
			$data['enable_default_for_all_locations'] = absint( $row['enable_default_for_all_locations'] );
		} else {
			$data['country'] 				= isset( $row['country'] ) ? sanitize_text_field( $row['country'] ) : '';
			$data['country_slug'] 			= isset( $row['country_slug'] ) ? sanitize_text_field( $row['country_slug'] ) : '';
			$data['region'] 				= isset( $row['region'] ) ? sanitize_text_field( $row['region'] ) : '';
			$data['region_slug'] 			= isset( $row['region_slug'] ) ? sanitize_text_field( $row['region_slug'] ) : '';
			$data['city'] 					= isset( $row['city'] ) ? sanitize_text_field( $row['city'] ) : '';
			$data['city_slug'] 				= isset( $row['city_slug'] ) ? sanitize_text_field( $row['city_slug'] ) : '';
		}
		$data['top_description'] 			= isset( $row['top_description'] ) ? $row['top_description'] : '';

		return apply_filters( 'geodir_location_import_validate_cat_location', $data, $row );
	}

	public static function validate_neighbourhood( $row ) {
		$row = array_map( 'trim', $row );

		$data 						= array();
		$data['hood_id'] 			= isset( $row['neighbourhood_id'] ) ? absint( $row['neighbourhood_id'] ) : '';
		$data['hood_name'] 			= isset( $row['neighbourhood_name'] ) ? sanitize_text_field( $row['neighbourhood_name'] ) : '';
		$data['hood_location_id'] 	= isset( $row['location_id'] ) ? absint( $row['location_id'] ) : '';
		$data['hood_latitude'] 		= isset( $row['latitude'] ) ? sanitize_text_field( $row['latitude'] ) : '';
		$data['hood_longitude'] 	= isset( $row['longitude'] ) ? sanitize_text_field( $row['longitude'] ) : '';
		$data['hood_slug'] 			= isset( $row['neighbourhood_slug'] ) ? sanitize_text_field( $row['neighbourhood_slug'] ) : '';
		$data['hood_meta_title'] 	= isset( $row['meta_title'] ) ? sanitize_text_field( $row['meta_title'] ) : '';
		$data['hood_meta'] 			= isset( $row['meta_description'] ) ? sanitize_text_field( $row['meta_description'] ) : '';
		$data['hood_description'] 	= isset( $row['description'] ) ? $row['description'] : '';
		$data['city'] 				= isset( $row['city'] ) ? sanitize_text_field( $row['city'] ) : '';
		$data['region'] 			= isset( $row['region'] ) ? sanitize_text_field( $row['region'] ) : '';
		$data['country'] 			= isset( $row['country'] ) ? sanitize_text_field( $row['country'] ) : '';

		$post_types = geodir_get_posttypes();

		$cpt_desc = array();
		foreach ( $post_types as $post_type ) {
			$cpt_column = 'cpt_desc_' . $post_type;

			if ( in_array( $cpt_column, array_keys( $row ) ) && GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				$cpt_desc[ $post_type ] = $row[ $cpt_column ];
			}
		}
		$data['cpt_desc'] 			= ! empty( $cpt_desc ) ? json_encode( $cpt_desc ) : '';

		return apply_filters( 'geodir_location_import_validate_neighbourhood', $data, $row );
	}

	public static function prepare_export_cat_locations() {
		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();

		$count = 0;
		if ( ! empty( $data ) ) {
			$post_types = geodir_get_posttypes();
			$total_terms = 0;
			foreach ( $post_types as $post_type ) {
				if ( ! empty( $data['post_type'] ) && $data['post_type'] != $post_type ) {
					continue;
				}
				if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					continue;
				}

				$total_terms += (int)geodir_get_terms_count( $post_type );
			}

			if ( $total_terms > 0 ) {
				$location_type = ! empty( $data['loc_type'] ) ? $data['loc_type'] : '';
				switch ( $location_type ) {
					case 'country':
						$total_locations = (int)geodir_location_imex_count_locations( 'country' );
						break;
					case 'region':
						$total_locations = (int)geodir_location_imex_count_locations( 'region' );
						break;
					case 'city':
						$total_locations = (int)geodir_location_imex_count_locations();
						break;
					default:
						$total_locations = 1;
						break;
				}

				$count = $total_locations * $total_terms;
			}
		}

		$json = array( 'total' => (int)$count );

		return $json;
	}

	public static function export_cat_locations() {
		global $wp_filesystem;

		$nonce          = isset( $_REQUEST['_nonce'] ) ? $_REQUEST['_nonce'] : null;
		$count 			= isset( $_REQUEST['_c'] ) ? absint( $_REQUEST['_c'] ) : 0;
		$chunk_per_page = !empty( $_REQUEST['_n'] ) ? absint( $_REQUEST['_n'] ) : 5000;
		$chunk_page_no  = isset( $_REQUEST['_p'] ) ? absint( $_REQUEST['_p'] ) : 1;
		$csv_file_dir   = GeoDir_Admin_Import_Export::import_export_cache_path( false );

		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();
		$post_type = ! empty( $data['post_type'] ) ? $data['post_type'] : '';
		$location_type = ! empty( $data['loc_type'] ) ? $data['loc_type'] : '';
		
		$file_name = 'gd_cat_locations_' . date( 'dmyHi' );

		$file_url_base  = GeoDir_Admin_Import_Export::import_export_cache_path() . '/';
		$file_url       = $file_url_base . $file_name . '.csv';
		$file_path      = $csv_file_dir . '/' . $file_name . '.csv';
		$file_path_temp = $csv_file_dir . '/cat_locations_' . $nonce . '.csv';

		$chunk_file_paths = array();

		if ( isset( $_REQUEST['_st'] ) ) {
			$line_count = (int) GeoDir_Admin_Import_Export::file_line_count( $file_path_temp );
			$percentage = count( $count ) > 0 && $line_count > 0 ? ceil( $line_count / $count ) * 100 : 0;
			$percentage = min( $percentage, 100 );

			$json['percentage'] = $percentage;

			return $json;
		} else {
			if ( ! $count > 0 ) {
				$json['error'] = __( 'No records to export.', 'geodirlocation' );
			} else {
				$total = $count;
				if ( $chunk_per_page > $count ) {
					$chunk_per_page = $count;
				}
				$chunk_total_pages = ceil( $total / $chunk_per_page );

				$j      = $chunk_page_no;
				$rows 	= geodir_location_imex_cat_locations_data( $chunk_per_page, $j, $post_type, $location_type );

				$per_page = 500;
				if ( $per_page > $chunk_per_page ) {
					$per_page = $chunk_per_page;
				}
				$total_pages = ceil( $chunk_per_page / $per_page );

				for ( $i = 0; $i <= $total_pages; $i ++ ) {
					$save_rows = array_slice( $rows, ( $i * $per_page ), $per_page );

					$clear = $i == 0 ? true : false;
					GeoDir_Admin_Import_Export::save_csv_data( $file_path_temp, $save_rows, $clear );
				}

				if ( $wp_filesystem->exists( $file_path_temp ) ) {
					$chunk_page_no   = $chunk_total_pages > 1 ? '-' . $j : '';
					$chunk_file_name = $file_name . $chunk_page_no . '_' . substr( geodir_rand_hash(), 0, 8 ) . '.csv';
					$file_path       = $csv_file_dir . '/' . $chunk_file_name;
					$wp_filesystem->move( $file_path_temp, $file_path, true );

					$file_url           = $file_url_base . $chunk_file_name;
					$chunk_file_paths[] = array(
						'i' => $j . '.',
						'u' => $file_url,
						's' => size_format( filesize( $file_path ), 2 )
					);
				}

				if ( ! empty( $chunk_file_paths ) ) {
					$json['total'] = $count;
					$json['files'] = $chunk_file_paths;
				} else {
					$json['error'] = __( 'ERROR: Could not create csv file. This is usually due to inconsistent file permissions.', 'geodirlocation' );
				}
			}
		}

		return $json;
	}

	public static function import_cat_locations() {
		global $gd_location_ids;
		$limit     = isset( $_POST['limit'] ) && $_POST['limit'] ? (int) $_POST['limit'] : 1;
		$processed = isset( $_POST['processed'] ) ? (int) $_POST['processed'] : 0;

		$processed ++;
		$rows = GeoDir_Admin_Import_Export::get_csv_rows( $processed, $limit );

		if ( ! empty( $rows ) ) {
			$created = 0;
			$updated = 0;
			$skipped = 0;
			$invalid = 0;
			$images  = 0;

			$update_or_skip = isset( $_POST['_ch'] ) && $_POST['_ch'] == 'update' ? 'update' : 'skip';
			$log_error = __( 'GD IMPORT CAT + LOCATIONS DESCRIPTION [ROW %d]:', 'geodirlocation' );

			$i = 0;
			foreach ( $rows as $row ) {
				$i++;
				$line_no = $processed + $i;
				$line_error = wp_sprintf( $log_error, $line_no );
				$row = self::validate_cat_location( $row );
				
				if ( empty( $row ) ) {
					geodir_error_log( $line_error . ' ' . __( 'data is empty!', 'geodirlocation' ) );
					$invalid++;
					continue;
				}

				if ( empty( $row['term_id'] ) ) {
					geodir_error_log( $line_error . ' ' . __( 'term_id is empty!', 'geodirlocation' ) );
					$invalid++;
					continue;
				}

				do_action( 'geodir_location_pre_import_cat_location_data', $row );

				$use_default = isset( $row['enable_default_for_all_locations'] ) && (int)$row['enable_default_for_all_locations'] == 1 ? 0 : 1;
				if ( isset( $row['enable_default_for_all_locations'] ) ) {
					if ( isset( $row['enable_default_for_all_locations'] ) ) {
						update_term_meta( $row['term_id'], 'gd_desc_custom', $use_default );
					}
					update_term_meta( $row['term_id'], 'ct_cat_top_desc', $row['top_description'] );
					$updated++;
				} else {
					if ( empty( $row['country_slug'] ) ) {
						geodir_error_log( $line_error . ' ' . __( 'country_slug is empty!', 'geodirlocation' ) );
						$invalid++;
						continue;
					}

					if ( ! empty( $row['city_slug'] ) ) {
						if ( empty( $row['region_slug'] ) ) {
							geodir_error_log( $line_error . ' ' . __( 'region_slug is empty!', 'geodirlocation' ) );
							$invalid++;
						} else {
							$location_id = 0;
							if ( ! empty( $gd_location_ids[ $row['country_slug'] ][ $row['region_slug'] ][ $row['city_slug'] ] ) ) {
								$location_id = $gd_location_ids[ $row['country_slug'] ][ $row['region_slug'] ][ $row['city_slug'] ];
							} else {
								$location = GeoDir_Location_City::get_info_by_slug( $row['city_slug'], $row['country_slug'], $row['region_slug'] );
								
								if ( !empty( $location->location_id ) ) {
									$location_id = $location->location_id;
									$gd_location_ids[ $row['country_slug'] ][ $row['region_slug'] ][ $row['city_slug'] ] = $location_id;
								}
							}

							if (!empty($location_id)) {
								if ( isset( $row['enable_default_for_all_locations'] ) ) {
									update_term_meta( $row['term_id'], 'gd_desc_custom', $use_default );
								}
								geodir_location_save_term_top_desc( $row['term_id'], $row['top_description'], $location_id, 'city' );
								$updated++;
							} else {
								$invalid++;
								geodir_error_log( $line_error . ' ' . __( '%s city not found!', 'geodirlocation' ) );
							}
						}
					} else {
						if ( ! empty( $row['region_slug'] ) ) {
							if ( isset( $row['enable_default_for_all_locations'] ) ) {
								update_term_meta( $row['term_id'], 'gd_desc_custom', $use_default );
							}
							geodir_location_save_term_top_desc( $row['term_id'], $row['top_description'], $row['region_slug'], 'region', $row['country_slug'] );
							$updated++;
						} else {
							if ( isset( $row['enable_default_for_all_locations'] ) ) {
								update_term_meta( $row['term_id'], 'gd_desc_custom', $use_default );
							}
							geodir_location_save_term_top_desc( $row['term_id'], $row['top_description'], $row['country_slug'], 'country' );
							$updated++;
						}
					}
				}
			}

		} else {
			return new WP_Error( 'gd-csv-empty', __( "No data found in csv file.", "geodirectory" ) );
		}

		return array(
			"processed" => $processed,
			"created"   => $created,
			"updated"   => $updated,
			"skipped"   => $skipped,
			"invalid"   => $invalid,
			"images"    => $images,
			"ID"        => 0,
		);
	}

	public static function prepare_export_neighbourhoods() {
		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();

		$count = geodir_location_imex_count_neighbourhoods();

		$json = array( 'total' => (int)$count );

		return $json;
	}

	public static function export_neighbourhoods() {
		global $wp_filesystem;

		$nonce          = isset( $_REQUEST['_nonce'] ) ? $_REQUEST['_nonce'] : null;
		$count 			= isset( $_REQUEST['_c'] ) ? absint( $_REQUEST['_c'] ) : 0;
		$chunk_per_page = !empty( $_REQUEST['_n'] ) ? absint( $_REQUEST['_n'] ) : 5000;
		$chunk_page_no  = isset( $_REQUEST['_p'] ) ? absint( $_REQUEST['_p'] ) : 1;
		$csv_file_dir   = GeoDir_Admin_Import_Export::import_export_cache_path( false );
		
		$file_name = 'gd_neighbourhoods_' . date( 'dmyHi' );

		$file_url_base  = GeoDir_Admin_Import_Export::import_export_cache_path() . '/';
		$file_url       = $file_url_base . $file_name . '.csv';
		$file_path      = $csv_file_dir . '/' . $file_name . '.csv';
		$file_path_temp = $csv_file_dir . '/neighbourhoods_' . $nonce . '.csv';

		$chunk_file_paths = array();

		if ( isset( $_REQUEST['_st'] ) ) {
			$line_count = (int) GeoDir_Admin_Import_Export::file_line_count( $file_path_temp );
			$percentage = count( $count ) > 0 && $line_count > 0 ? ceil( $line_count / $count ) * 100 : 0;
			$percentage = min( $percentage, 100 );

			$json['percentage'] = $percentage;

			return $json;
		} else {
			if ( ! $count > 0 ) {
				$json['error'] = __( 'No records to export.', 'geodirlocation' );
			} else {
				$total = $count;
				if ( $chunk_per_page > $count ) {
					$chunk_per_page = $count;
				}
				$chunk_total_pages = ceil( $total / $chunk_per_page );

				$j      = $chunk_page_no;
				$rows 	= geodir_location_imex_neighbourhoods_data( $chunk_per_page, $j );

				$per_page = 500;
				if ( $per_page > $chunk_per_page ) {
					$per_page = $chunk_per_page;
				}
				$total_pages = ceil( $chunk_per_page / $per_page );

				for ( $i = 0; $i <= $total_pages; $i ++ ) {
					$save_rows = array_slice( $rows, ( $i * $per_page ), $per_page );

					$clear = $i == 0 ? true : false;
					GeoDir_Admin_Import_Export::save_csv_data( $file_path_temp, $save_rows, $clear );
				}

				if ( $wp_filesystem->exists( $file_path_temp ) ) {
					$chunk_page_no   = $chunk_total_pages > 1 ? '-' . $j : '';
					$chunk_file_name = $file_name . $chunk_page_no . '_' . substr( geodir_rand_hash(), 0, 8 ) . '.csv';
					$file_path       = $csv_file_dir . '/' . $chunk_file_name;
					$wp_filesystem->move( $file_path_temp, $file_path, true );

					$file_url           = $file_url_base . $chunk_file_name;
					$chunk_file_paths[] = array(
						'i' => $j . '.',
						'u' => $file_url,
						's' => size_format( filesize( $file_path ), 2 )
					);
				}

				if ( ! empty( $chunk_file_paths ) ) {
					$json['total'] = $count;
					$json['files'] = $chunk_file_paths;
				} else {
					$json['error'] = __( 'ERROR: Could not create csv file. This is usually due to inconsistent file permissions.', 'geodirlocation' );
				}
			}
		}

		return $json;
	}

	public static function import_neighbourhoods() {
		$limit     = isset( $_POST['limit'] ) && $_POST['limit'] ? (int) $_POST['limit'] : 1;
		$processed = isset( $_POST['processed'] ) ? (int) $_POST['processed'] : 0;

		$processed ++;
		$rows = GeoDir_Admin_Import_Export::get_csv_rows( $processed, $limit );

		if ( ! empty( $rows ) ) {
			$created = 0;
			$updated = 0;
			$skipped = 0;
			$invalid = 0;
			$images  = 0;

			$update_or_skip = isset( $_POST['_ch'] ) && $_POST['_ch'] == 'update' ? 'update' : 'skip';
			$log_error = __( 'GD IMPORT NEIGHBOURHOODS [ROW %d]:', 'geodirlocation' );

			$i = 0;
			foreach ( $rows as $row ) {
				$i++;
				$line_no = $processed + $i;
				$line_error = wp_sprintf( $log_error, $line_no );
				$row = self::validate_neighbourhood( $row );
				
				if ( empty( $row ) ) {
					geodir_error_log( $line_error . ' ' . __( 'data is empty.', 'geodirlocation' ) );
					$invalid++;
					continue;
				}

				$exists = empty( $row['hood_id'] ) && ! empty( $row['hood_slug'] ) ? GeoDir_Location_Neighbourhood::get_info_by_slug( $row['hood_slug'] ) : NULL;

				if ( $update_or_skip == 'skip' && ( ! empty( $row['hood_id'] ) || ! empty( $exists ) ) ) {
					$skipped++;
					continue;
				}
				
				$valid = true;
				if ( empty( $row['hood_name'] ) || empty( $row['hood_latitude'] ) || empty( $row['hood_longitude'] ) ) {
					$valid = false;
					geodir_error_log( $line_error . ' ' . __( 'blank/invalid value for neighbourhood_name / latitude / longitude.', 'geodirlocation' ) );
				}
				
				$location = array();
				if ( ! empty( $row['hood_location_id'] ) && (int)$row['hood_location_id'] > 0 ) {
					$location = geodir_get_location_by_id( '', (int)$row['hood_location_id'] );
				} else if ( ! empty( $row['city'] ) && ! empty( $row['region'] ) && ! empty( $row['country'] ) ) {
					$location = geodir_get_location_by_slug( 'city', array( 'fields' => 'location_id', 'city' => $row['city'], 'country' => $row['country'], 'region' => $row['region'] ) );
				}

				if ( empty( $location ) ) {
					$valid = false;
					geodir_error_log( $line_error . ' ' . __( 'location not found with hood_location_id or matching city, region, country!', 'geodirlocation' ) );
				}
				$row['hood_location_id'] = $location->location_id;

				if ( ! $valid ) {
					$invalid++;
					continue;
				}

				do_action( 'geodir_location_pre_import_neighbourhood_data', $row );

				if ( isset( $row['country'] ) ) {
					unset( $row['country'] );
				}
				if ( isset( $row['region'] ) ) {
					unset( $row['region'] );
				}
				if ( isset( $row['city'] ) ) {
					unset( $row['city'] );
				}

				if ( ! empty( $row['hood_id'] ) || ! empty( $row['hood_slug'] ) ) {
					if ( (int)$row['hood_id'] > 0 && ( $neighbourhood = GeoDir_Location_Neighbourhood::get_info_by_id( (int)$row['hood_id'] ) ) ) {
						if ( $neighbourhood = GeoDir_Location_Neighbourhood::import_neighbourhood( $row ) ) { // updated
							$updated++;
						} else {
							$invalid++;
							geodir_error_log( $line_error . ' ' . __( 'fail to update neighbourhood.', 'geodirlocation' ) );
						}
					} else if ( !empty( $row['hood_slug'] ) && ( $neighbourhood = GeoDir_Location_Neighbourhood::get_info_by_slug( $row['hood_slug'] ) ) ) {
						$row['hood_id'] = (int)$neighbourhood->id;
                                    
						if ( $neighbourhood = GeoDir_Location_Neighbourhood::import_neighbourhood( $row ) ) { // updated
							$updated++;
						} else {
							$invalid++;
							geodir_error_log( $line_error . ' ' . __( 'fail to update neighbourhood.', 'geodirlocation' ) );
						}
					} else {
						if ( $neighbourhood = GeoDir_Location_Neighbourhood::import_neighbourhood( $row ) ) { // inserted
							$created++;
						} else {
							$invalid++;
							geodir_error_log( $line_error . ' ' . __( 'invalid data.', 'geodirlocation' ) );
						}
					}
				} else {
					if ( $neighbourhood = GeoDir_Location_Neighbourhood::import_neighbourhood( $row ) ) { // inserted
						$created++;
					} else {
						$invalid++;
						geodir_error_log( $line_error . ' ' . __( 'invalid data.', 'geodirlocation' ) );
					}
				}
			}

		} else {
			return new WP_Error( 'gd-csv-empty', __( "No data found in csv file.", "geodirectory" ) );
		}

		return array(
			"processed" => $processed,
			"created"   => $created,
			"updated"   => $updated,
			"skipped"   => $skipped,
			"invalid"   => $invalid,
			"images"    => $images,
			"ID"        => 0,
		);
	}
	
	/**
	 * JS code for import/export view.
	 * 
	 * @param $nonce
	 */
	public static function get_import_export_js($nonce){
		$uploads = wp_upload_dir();
		?>
		<script type="text/javascript">
		var timoutL;
		function geodir_location_prepare_import(el, type) {
			var $wrap, prepared, file;
			$wrap = jQuery(el).closest('.card-body');
			prepared = jQuery('#gd_prepared', $wrap).val();
			file = jQuery('#gd_im_' + type, $wrap).val();
			jQuery('gd-import-msg', $wrap).hide();
			jQuery('#gd-import-errors').hide();
			jQuery('#gd-import-errors #gd-csv-errors').html('');

			if(prepared == file) {
				geodir_location_resume_import(el, type);
				jQuery('#gd_import_data', $wrap).attr('disabled', 'disabled');
			} else {
				jQuery.ajax({
					url: geodir_params.ajax_url,
					type: "POST",
					data: 'action=geodir_import_export&task=prepare_import&_pt=' + type + '&_file=' + file + '&_nonce=<?php echo $nonce;?>',
					dataType: 'json',
					cache: false,
					success: function(data) {
						if(typeof data == 'object') {
							if(data.success == false) {
								jQuery('#gd-import-msg', $wrap).find('#message').removeClass('updated').addClass('error').html('<p>' + data.data + '</p>');
								jQuery('#gd-import-msg', $wrap).show();
							} else if(!data.error && typeof data.rows != 'undefined') {
								jQuery('#gd_total', $wrap).val(data.rows);
								jQuery('#gd_prepared', $wrap).val(file);
								jQuery('#gd_processed', $wrap).val('0');
								jQuery('#gd_created', $wrap).val('0');
								jQuery('#gd_updated', $wrap).val('0');
								jQuery('#gd_skipped', $wrap).val('0');
								jQuery('#gd_invalid', $wrap).val('0');
								jQuery('#gd_images', $wrap).val('0');
								geodir_location_start_import(el, type);
							}
						}
					},
					error: function(errorThrown) {
						console.log(errorThrown);
					}
				});
			}
		}

		function geodir_location_start_import(el, type) {
			var $wrap, limit, total, total_processed, file, choice;
			$wrap = jQuery(el).closest('.card-body');

			limit = 1;
			total = parseInt(jQuery('#gd_total', $wrap).val());
			total_processed = parseInt(jQuery('#gd_processed', $wrap).val());
			file = jQuery('#gd_im_' + type, $wrap).val();
			choice = jQuery('#gd_im_choice' + type, $wrap).val();

			if (!file) {
				jQuery('#gd_import_data', $wrap).removeAttr('disabled').show();
				jQuery('#gd_stop_import', $wrap).hide();
				jQuery('#gd_process_data', $wrap).hide();
				jQuery('#gd-import-progress', $wrap).hide();
				jQuery('.gd-fileprogress', $wrap).width(0);
				jQuery('#gd-import-done', $wrap).text('0');
				jQuery('#gd-import-total', $wrap).text('0');
				jQuery('#gd-import-perc', $wrap).text('0%');

				jQuery($wrap).find('.filelist .file').remove();

				jQuery('#gd-import-msg', $wrap).find('#message').removeClass('updated').addClass('error').html("<p><?php esc_attr_e( 'Please select csv file.', 'geodirlocation' ); ?></p>");
				jQuery('#gd-import-msg', $wrap).show();

				return false;
			}

			jQuery('#gd-import-total', $wrap).text(total);
			jQuery('#gd_stop_import', $wrap).show();
			jQuery('#gd_process_data', $wrap).css({
				'display': 'inline-block'
			});
			jQuery('#gd-import-progress', $wrap).show();
			if ((parseInt(total) / 100) > 0) {
				limit = parseInt(parseInt(total) / 100);
			}
			if (limit == 1) {
				if (parseInt(total) > 50) {
					limit = 5;
				} else if (parseInt(total) > 10 && parseInt(total) < 51) {
					limit = 2;
				}
			}
			if (limit > 10) {
				limit = 10;
			}
			if (limit < 1) {
				limit = 1;
			}

			if ( parseInt(limit) > parseInt(total) )
				limit = parseInt(total);
			if (total_processed >= total) {
				jQuery('#gd_import_data', $wrap).removeAttr('disabled').show();
				jQuery('#gd_stop_import', $wrap).hide();
				jQuery('#gd_process_data', $wrap).hide();

				geodir_location_show_results(el, type);

				jQuery('#gd_im_' + type, $wrap).val('');
				jQuery('#gd_prepared', $wrap).val('');

				return false;
			}
			jQuery('#gd-import-msg', $wrap).hide();

			var gd_processed = parseInt(jQuery('#gd_processed', $wrap).val());
			var gd_created = parseInt(jQuery('#gd_created', $wrap).val());
			var gd_updated = parseInt(jQuery('#gd_updated', $wrap).val());
			var gd_skipped = parseInt(jQuery('#gd_skipped', $wrap).val());
			var gd_invalid = parseInt(jQuery('#gd_invalid', $wrap).val());
			var gd_images = parseInt(jQuery('#gd_images', $wrap).val());

			data = '&_import=' + type + '&_file=' + file + '&_ch=' + choice + '&limit=' + limit + '&processed=' + gd_processed;

			jQuery.ajax({
				url: geodir_params.ajax_url,
				type: "POST",
				data: 'action=geodir_import_export&task=import&_nonce=<?php echo $nonce;?>' + data,
				dataType : 'json',
				cache: false,
				success: function (data) {
					// log any errors
					if(data.errors){
						geodir_location_log_errors(data.errors);
					}

					if (typeof data == 'object') {
						if(data.success == false) {
							jQuery('#gd_import_data', $wrap).removeAttr('disabled').show();
							jQuery('#gd_stop_import', $wrap).hide();
							jQuery('#gd_process_data', $wrap).hide();
							jQuery('#gd-import-msg', $wrap).find('#message').removeClass('updated').addClass('error').html('<p>' + data.data + '</p>');
							jQuery('#gd-import-msg', $wrap).show();
						} else {
							gd_created = gd_created + parseInt(data.created);
							gd_updated = gd_updated + parseInt(data.updated);
							gd_skipped = gd_skipped + parseInt(data.skipped);
							gd_invalid = gd_invalid + parseInt(data.invalid);
							gd_images = gd_images + parseInt(data.images);

							jQuery('#gd_processed', $wrap).val(gd_processed);
							jQuery('#gd_created', $wrap).val(gd_created);
							jQuery('#gd_updated', $wrap).val(gd_updated);
							jQuery('#gd_skipped', $wrap).val(gd_skipped);
							jQuery('#gd_invalid', $wrap).val(gd_invalid);
							jQuery('#gd_images', $wrap).val(gd_images);

							if (parseInt(gd_processed) == parseInt(total)) {
								jQuery('#gd-import-done', $wrap).text(total);
								jQuery('#gd-import-perc', $wrap).text('100%');
								jQuery('.gd-fileprogress', $wrap).css({
									'width': '100%'
								});
								jQuery('#gd_im_' + type, $wrap).val('');
								jQuery('#gd_prepared', $wrap).val('');

								geodir_location_show_results(el, type);
								gd_imex_FinishImport(el, type);

								jQuery('#gd_stop_import', $wrap).hide();
							}
							if (parseInt(gd_processed) < parseInt(total)) {
								var terminate_action = jQuery('#gd_terminateaction', $wrap).val();
								if (terminate_action == 'continue') {
									var nTmpCnt = parseInt(total_processed) + parseInt(limit);
									nTmpCnt = nTmpCnt > total ? total : nTmpCnt;

									jQuery('#gd_processed', $wrap).val(nTmpCnt);

									jQuery('#gd-import-done', $wrap).text(nTmpCnt);
									if (parseInt(total) > 0) {
										var percentage = ((parseInt(nTmpCnt) / parseInt(total)) * 100);
										percentage = percentage > 100 ? 100 : percentage;
										jQuery('#gd-import-perc', $wrap).text(parseInt(percentage) + '%');
										jQuery('.gd-fileprogress', $wrap).css({
											'width': percentage + '%'
										});
									}

									clearTimeout(timoutL);
									timoutL = setTimeout(function () {
										geodir_location_start_import(el, type);
									}, 0);
								} else {
									jQuery('#gd_import_data', $wrap).hide();
									jQuery('#gd_stop_import', $wrap).hide();
									jQuery('#gd_process_data', $wrap).hide();
									jQuery('#gd_continue_data', $wrap).show();
									return false;
								}
							} else {
								jQuery('#gd_import_data', $wrap).removeAttr('disabled').show();
								jQuery('#gd_stop_import', $wrap).hide();
								jQuery('#gd_process_data', $wrap).hide();
								return false;
							}
						}
					} else {
						jQuery('#gd_import_data', $wrap).removeAttr('disabled').show();
						jQuery('#gd_stop_import', $wrap).hide();
						jQuery('#gd_process_data', $wrap).hide();
					}
				},
				error: function (errorThrown) {
					jQuery('#gd_import_data', $wrap).removeAttr('disabled').show();
					jQuery('#gd_stop_import', $wrap).hide();
					jQuery('#gd_process_data', $wrap).hide();
					console.log(errorThrown);
				}
			});
		}


		function geodir_location_log_errors(errors){
				jQuery.each(errors, function( index, value ) {
					jQuery( "#gd-csv-errors" ).append( "<p>"+value+"</p>" );
					jQuery( "#gd-csv-errors" ).addClass('error');
					jQuery( "#gd-import-errors" ).show();
				});
		}

		function geodir_location_terminate_import(el, type) {
			var $wrap = jQuery(el).closest('.card-body');
			jQuery('#gd_terminateaction', $wrap).val('terminate');
			jQuery('#gd_import_data', $wrap).hide();
			jQuery('#gd_stop_import', $wrap).hide();
			jQuery('#gd_process_data', $wrap).hide();
			jQuery('#gd_continue_data', $wrap).show();
		}

		function geodir_location_resume_import(el, type) {
			var $wrap = jQuery(el).closest('.card-body');
			var processed = jQuery('#gd_processed', $wrap).val();
			var total = jQuery('#gd_total', $wrap).val();
			if (parseInt(processed) > parseInt(total)) {
				jQuery('#gd_stop_import', $wrap).hide();
			} else {
				jQuery('#gd_stop_import', $wrap).show();
			}
			jQuery('#gd_import_data', $wrap).show();
			jQuery('#gd_import_data', $wrap).attr('disabled', 'disabled');
			jQuery('#gd_process_data', $wrap).css({
				'display': 'inline-block'
			});
			jQuery('#gd_continue_data', $wrap).hide();
			jQuery('#gd_terminateaction', $wrap).val('continue');

			clearTimeout(timoutL);
			timoutL = setTimeout(function () {
				geodir_location_start_import(el, type);
			}, 0);
		}

		function geodir_location_show_results(el, type) {
			var $wrap = jQuery(el).closest('.card-body');

			var total = parseInt(jQuery('#gd_total', $wrap).val());
			var processed = parseInt(jQuery('#gd_processed', $wrap).val());
			var created = parseInt(jQuery('#gd_created', $wrap).val());
			var updated = parseInt(jQuery('#gd_updated', $wrap).val());
			var skipped = parseInt(jQuery('#gd_skipped', $wrap).val());
			var invalid = parseInt(jQuery('#gd_invalid', $wrap).val());
			var images = parseInt(jQuery('#gd_images', $wrap).val());

			var msgProcessed = '<?php echo addslashes( __( 'Total %d item(s) found.', 'geodirlocation' ) );?>';
			var msgCreated = '<?php echo addslashes( __( '%d item(s) added.', 'geodirlocation' ) );?>';
			var msgUpdated = '<?php echo addslashes( __( '%d item(s) updated.', 'geodirlocation' ) );?>';
			var msgSkipped = '<?php echo addslashes( __( '%d item(s) ignored due to already exists.', 'geodirlocation' ) );?>';
			var msgInvalid = '<?php echo addslashes( __( '%d item(s) could not be saved due to invalid data.', 'geodirlocation' ) );?>';
			var msgImages = '<?php echo addslashes( wp_sprintf( __( "Please transfer all new images to <b>'%s'</b> folder.", 'geodirectory' ), str_replace( ABSPATH, '', $uploads['path'] ) ) );?>';

			<?php do_action( 'geodir_location_import_js_stats' ); ?>

			var gdMsg = '<p></p>';
			if ( processed > 0 ) {
				msgProcessed = '<p>' + msgProcessed + '</p>';
				gdMsg += msgProcessed.replace("%d", processed);
			}
			if ( created > 0 ) {
				msgCreated = '<p>' + msgCreated + '</p>';
				gdMsg += msgCreated.replace("%d", created);
			}
			if ( updated > 0 ) {
				msgUpdated = '<p>' + msgUpdated + '</p>';
				gdMsg += msgUpdated.replace("%d", updated);
			}
			if ( skipped > 0 ) {
				msgSkipped = '<p>' + msgSkipped + '</p>';
				gdMsg += msgSkipped.replace("%d", skipped);
			}
			if (invalid > 0) {
				msgInvalid = '<p>' + msgInvalid + '</p>';
				gdMsg += msgInvalid.replace("%d", invalid);
			}
			<?php do_action( 'geodir_location_import_js_message' ); ?>
			if (images > 0) {
				gdMsg += '<p>' + msgImages + '</p>';
			}
			gdMsg += '<p></p>';
			jQuery('#gd-import-msg', $wrap).find('#message').removeClass('error').addClass('updated').html(gdMsg);
			jQuery('#gd-import-msg', $wrap).show();
			return;
		}
			
		function geodir_location_finish_import(el, type) {
			jQuery.ajax({
				url: ajaxurl,
				type: "POST",
				data: 'action=geodir_import_export&task=import_finish&_pt=' + type + '&_nonce=<?php echo $nonce; ?>',
				dataType : 'json',
				cache: false,
				success: function (data) {
					//import done
				}
			});
		}		
		</script>
		<?php
	}

		public static function import_export_cpt_locations( $setting ) {
		?>
		<tr valign="top">
			<td class="forminp" colspan="2">
				<?php /**
				 * Contains template for import/export locations + cpt description.
				 *
				 * @since 2.0.0
				 */
				include_once( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/views/html-import-export-cpt-locations.php' );
				?>
			</td>
		</tr>
		<?php
	}

	public static function validate_cpt_location( $row ) {
		$row = array_map( 'trim', $row );

		$data 					= array();
		$data['country'] 		= isset( $row['country'] ) ? sanitize_text_field( $row['country'] ) : '';
		$data['country_slug']	= isset( $row['country_slug'] ) ? sanitize_text_field( $row['country_slug'] ) : '';
		$data['region'] 		= isset( $row['region'] ) ? sanitize_text_field( $row['region'] ) : '';
		$data['region_slug'] 	= isset( $row['region_slug'] ) ? sanitize_text_field( $row['region_slug'] ) : '';
		$data['city'] 			= isset( $row['city'] ) ? sanitize_text_field( $row['city'] ) : '';
		$data['city_slug'] 		= isset( $row['city_slug'] ) ? sanitize_text_field( $row['city_slug'] ) : '';

		$post_types = geodir_get_posttypes();

		$cpt_desc = array();
		foreach ( $post_types as $post_type ) {
			$cpt_column = 'cpt_desc_' . $post_type;

			if ( in_array( $cpt_column, array_keys( $row ) ) && GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				$cpt_desc[ $post_type ] = $row[ $cpt_column ];
			}
		}
		$data['cpt_desc'] 			= ! empty( $cpt_desc ) ? json_encode( $cpt_desc ) : '';

		return apply_filters( 'geodir_location_import_validate_cpt_location', $data, $row );
	}

	public static function prepare_export_cpt_locations() {
		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();

		$count = 0;
		if ( ! empty( $data ) ) {
			$location_type = ! empty( $data['loc_type'] ) ? $data['loc_type'] : '';
			switch ( $location_type ) {
				case 'country':
					$count = (int)geodir_location_imex_count_locations( 'country' );
					break;
				case 'region':
					$count = (int)geodir_location_imex_count_locations( 'region' );
					break;
				case 'city':
					$count = (int)geodir_location_imex_count_locations();
					break;
				default:
					$count = 1;
					break;
			}
		}

		$json = array( 'total' => (int)$count );

		return $json;
	}

	public static function export_cpt_locations() {
		global $wp_filesystem;

		$nonce          = isset( $_REQUEST['_nonce'] ) ? $_REQUEST['_nonce'] : null;
		$count 			= isset( $_REQUEST['_c'] ) ? absint( $_REQUEST['_c'] ) : 0;
		$chunk_per_page = !empty( $_REQUEST['_n'] ) ? absint( $_REQUEST['_n'] ) : 5000;
		$chunk_page_no  = isset( $_REQUEST['_p'] ) ? absint( $_REQUEST['_p'] ) : 1;
		$csv_file_dir   = GeoDir_Admin_Import_Export::import_export_cache_path( false );

		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();
		$location_type = ! empty( $data['loc_type'] ) ? $data['loc_type'] : '';
		
		$file_name = 'gd_cpt_locations_' . $location_type . '_' . date( 'dmyHi' );

		$file_url_base  = GeoDir_Admin_Import_Export::import_export_cache_path() . '/';
		$file_url       = $file_url_base . $file_name . '.csv';
		$file_path      = $csv_file_dir . '/' . $file_name . '.csv';
		$file_path_temp = $csv_file_dir . '/cpt_locations_' . $nonce . '.csv';

		$chunk_file_paths = array();

		if ( isset( $_REQUEST['_st'] ) ) {
			$line_count = (int) GeoDir_Admin_Import_Export::file_line_count( $file_path_temp );
			$percentage = count( $count ) > 0 && $line_count > 0 ? ceil( $line_count / $count ) * 100 : 0;
			$percentage = min( $percentage, 100 );

			$json['percentage'] = $percentage;

			return $json;
		} else {
			if ( ! $count > 0 ) {
				$json['error'] = __( 'No records to export.', 'geodirlocation' );
			} else {
				$total = $count;
				if ( $chunk_per_page > $count ) {
					$chunk_per_page = $count;
				}
				$chunk_total_pages = ceil( $total / $chunk_per_page );

				$j      = $chunk_page_no;
				$rows 	= geodir_location_imex_cpt_locations_data( $chunk_per_page, $j, $location_type );

				$per_page = 500;
				if ( $per_page > $chunk_per_page ) {
					$per_page = $chunk_per_page;
				}
				$total_pages = ceil( $chunk_per_page / $per_page );

				for ( $i = 0; $i <= $total_pages; $i ++ ) {
					$save_rows = array_slice( $rows, ( $i * $per_page ), $per_page );

					$clear = $i == 0 ? true : false;
					GeoDir_Admin_Import_Export::save_csv_data( $file_path_temp, $save_rows, $clear );
				}

				if ( $wp_filesystem->exists( $file_path_temp ) ) {
					$chunk_page_no   = $chunk_total_pages > 1 ? '-' . $j : '';
					$chunk_file_name = $file_name . $chunk_page_no . '_' . substr( geodir_rand_hash(), 0, 8 ) . '.csv';
					$file_path       = $csv_file_dir . '/' . $chunk_file_name;
					$wp_filesystem->move( $file_path_temp, $file_path, true );

					$file_url           = $file_url_base . $chunk_file_name;
					$chunk_file_paths[] = array(
						'i' => $j . '.',
						'u' => $file_url,
						's' => size_format( filesize( $file_path ), 2 )
					);
				}

				if ( ! empty( $chunk_file_paths ) ) {
					$json['total'] = $count;
					$json['files'] = $chunk_file_paths;
				} else {
					$json['error'] = __( 'ERROR: Could not create csv file. This is usually due to inconsistent file permissions.', 'geodirlocation' );
				}
			}
		}

		return $json;
	}

	public static function import_cpt_locations() {
		global $gd_location_ids;
		$limit     = isset( $_POST['limit'] ) && $_POST['limit'] ? (int) $_POST['limit'] : 1;
		$processed = isset( $_POST['processed'] ) ? (int) $_POST['processed'] : 0;

		$processed ++;
		$rows = GeoDir_Admin_Import_Export::get_csv_rows( $processed, $limit );

		if ( ! empty( $rows ) ) {
			$created = 0;
			$updated = 0;
			$skipped = 0;
			$invalid = 0;
			$images  = 0;

			$update_or_skip = isset( $_POST['_ch'] ) && $_POST['_ch'] == 'update' ? 'update' : 'skip';
			$log_error = __( 'GD IMPORT LOCATIONS + CPT DESCRIPTION [ROW %d]:', 'geodirlocation' );

			$i = 0;
			foreach ( $rows as $row ) {
				$i++;
				$line_no = $processed + $i;
				$line_error = wp_sprintf( $log_error, $line_no );
				$row = self::validate_cpt_location( $row );
				
				if ( empty( $row ) ) {
					geodir_error_log( $line_error . ' ' . __( 'data is empty!', 'geodirlocation' ) );
					$invalid++;
					continue;
				}

				if ( empty( $row['cpt_desc'] ) ) {
					geodir_error_log( $line_error . ' ' . __( 'Description is empty!', 'geodirlocation' ) );
					$invalid++;
					continue;
				}

				do_action( 'geodir_location_pre_import_cpt_location_data', $row );

				if ( ! empty( $row['city_slug'] ) ) {
					if ( ! empty( $row['region_slug'] ) ) {
						if ( ! empty( $row['country_slug'] ) ) {
							$type = 'city';
						} else {
							geodir_error_log( $line_error . ' ' . __( 'country_slug is empty!', 'geodirlocation' ) );
							$invalid++;
							continue;
						}
					} else {
						geodir_error_log( $line_error . ' ' . __( 'region_slug is empty!', 'geodirlocation' ) );
						$invalid++;
						continue;
					}
				} elseif ( ! empty( $row['region_slug'] ) ) {
					if ( ! empty( $row['country_slug'] ) ) {
						$type = 'region';
					} else {
						geodir_error_log( $line_error . ' ' . __( 'country_slug is empty!', 'geodirlocation' ) );
						$invalid++;
						continue;
					}
				} elseif ( ! empty( $row['country_slug'] ) ) {
					$type = 'country';
				} else {
					geodir_error_log( $line_error . ' ' . __( 'country_slug is empty!', 'geodirlocation' ) );
					$invalid++;
					continue;
				}

				$data = array(
					'country_slug' => $row['country_slug'],
					'region_slug' => $row['region_slug'],
					'city_slug' => $row['city_slug'],
					'cpt_desc' => $row['cpt_desc']
				);

				$saved = GeoDir_Location_SEO::save_seo_data( $type, $data );

				if ( ! empty( $saved ) ) {
					$updated++;
				}
			}

		} else {
			return new WP_Error( 'gd-csv-empty', __( "No data found in csv file.", "geodirectory" ) );
		}

		return array(
			"processed" => $processed,
			"created"   => $created,
			"updated"   => $updated,
			"skipped"   => $skipped,
			"invalid"   => $invalid,
			"images"    => $images,
			"ID"        => 0,
		);
	}
}

new GeoDir_Location_Admin_Import_Export();