<?php
/**
 * Handle import and exports.
 *
 * @author   AyeCode
 * @category Admin
 * @package  GeoDir_Custom_Posts/Admin
 * @version  2.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GeoDir_CP_Admin_Import_Export', false ) ) {

/**
 * GeoDir_CP_Admin_Import_Export Class.
 */
class GeoDir_CP_Admin_Import_Export {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'geodir_get_sections_import-export', array( $this, 'import_export_sections' ), 9, 1 );
		add_filter( 'geodir_get_settings_import-export', array( $this, 'import_export_settings' ), 9, 2 );
		add_action( 'geodir_cp_import_js_stats', array( $this, 'set_custom_js_errors' ) );

		// Post Types
		add_filter( 'geodir_admin_field_import_export_post_types', array( $this, 'import_export_post_types' ), 10, 1 );
		add_filter('geodir_ajax_prepare_export_post_types', array( $this, 'prepare_export_post_types' ) );
		add_filter('geodir_ajax_export_post_types', array( $this, 'export_post_types' ) );
		add_filter('geodir_ajax_import_post_types', array( $this, 'import_post_types' ) );

		// Custom Fields
		add_filter( 'geodir_admin_field_import_export_custom_fields', array( $this, 'import_export_custom_fields' ), 10, 1 );
		add_filter('geodir_ajax_prepare_export_custom_fields', array( $this, 'prepare_export_custom_fields' ) );
		add_filter('geodir_ajax_export_custom_fields', array( $this, 'export_custom_fields' ) );
		add_filter('geodir_ajax_import_custom_fields', array( $this, 'import_custom_fields' ) );
		add_filter('geodir_cpt_cf_save_data', array( __CLASS__, 'cpt_cf_save_data' ), 10, 2 );

		// Tabs
		add_filter( 'geodir_admin_field_import_export_cpt_tabs', array( $this, 'import_export_cpt_tabs' ), 10, 1 );
		add_filter('geodir_ajax_prepare_export_cpt_tabs', array( $this, 'prepare_export_cpt_tabs' ) );
		add_filter('geodir_ajax_export_cpt_tabs', array( $this, 'export_cpt_tabs' ) );
		add_filter('geodir_ajax_import_cpt_tabs', array( $this, 'import_cpt_tabs' ) );
		//add_filter('geodir_cpt_cf_save_data', array( __CLASS__, 'cpt_cf_save_data' ), 10, 2 );
	}

	public function import_export_sections( $sections ) {
		$sections['post-types'] = __( 'Post Types', 'geodir_custom_posts' );
		$sections['custom-fields'] = __( 'Custom Fields', 'geodir_custom_posts' );
		$sections['cpt-tabs'] = __( 'CPT Tabs', 'geodir_custom_posts' );

		return $sections;
	}

	public function import_export_settings( $settings, $current_section ) {
		if ( $current_section == 'post-types' ) {
			$settings = apply_filters( 'geodir_import_export_post_types_settings', array(
					array(
						'id'   => 'import_export_post_types',
						'type' => 'import_export_post_types',
					)
				)
			);
		} else if ( $current_section == 'custom-fields' ) {
			$settings = apply_filters( 'geodir_import_export_custom_fields_settings', array(
					array(
						'id'   => 'import_export_custom_fields',
						'type' => 'import_export_custom_fields',
					)
				)
			);
		}  else if ( $current_section == 'cpt-tabs' ) {
			$settings = apply_filters( 'geodir_import_export_cpt_tabs_settings', array(
					array(
						'id'   => 'import_export_cpt_tabs',
						'type' => 'import_export_cpt_tabs',
					)
				)
			);
		}

		return $settings;
	}

	public static function import_export_post_types( $setting ) {
		?>
		<tr valign="top" class="<?php echo ( ! empty( $value['advanced'] ) ? 'gd-advanced-setting' : '' ); ?>">
			<td class="forminp" colspan="2">
				<?php /**
				 * Contains template for import/export post types.
				 *
				 * @since 2.2.1
				 */
				include_once( GEODIR_CP_PLUGIN_DIR . 'includes/admin/views/html-import-export-post-types.php' );
				?>
			</td>
		</tr>
		<?php
	}
	
	public static function import_export_custom_fields( $setting ) {
		?>
		<tr valign="top">
			<td class="forminp" colspan="2">
				<?php /**
				 * Contains template for import/export custom fields.
				 *
				 * @since 2.2.1
				 */
				include_once( GEODIR_CP_PLUGIN_DIR . 'includes/admin/views/html-import-export-custom-fields.php' );
				?>
			</td>
		</tr>
		<?php
	}

	public static function set_custom_js_errors() {
		if ( ! empty( $_GET['section'] ) ) {
			$errors = '';
			switch ( $_GET['section'] ) {
				case 'post-types':
					$errors .= " msgInvalid = '" . addslashes( __( '%d item(s) could not be added due to blank/invalid value for "post type, name, singular name, slug".', 'geodir_custom_posts' ) ) . "';";
				break;
				case 'custom-fields':
				case 'cpt-tabs':
					$errors .= " msgInvalid = '" . addslashes( __( '%d item(s) could not be added due to blank/invalid data.', 'geodir_custom_posts' ) ) . "';";
				break;
			}
			echo $errors;
		}
	}

	public static function prepare_export_post_types() {
		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();
		$_post_types = isset( $data['post_types'] ) ? array_filter( $data['post_types'] ) : array();

		if ( ! empty( $_post_types ) ) {
			$post_types = array();

			foreach ( $_post_types as $post_type ) {
				if ( geodir_is_gd_post_type( $post_type ) ) {
					$post_types[] = $post_type;
				}
			}
		} else {
			$post_types = geodir_get_posttypes( 'names' );
		}

		$json = array( 'total' => count( $post_types ) );

		return $json;
	}

	public static function export_post_types() {
		global $wp_filesystem;

		$nonce          = isset( $_REQUEST['_nonce'] ) ? $_REQUEST['_nonce'] : null;
		$data           = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();
		$_post_types    = isset( $data['post_types'] ) ? array_filter( $data['post_types'] ) : array();
		$csv_file_dir   = GeoDir_Admin_Import_Export::import_export_cache_path( false );
		$file_url_base  = GeoDir_Admin_Import_Export::import_export_cache_path() . '/';
		$file_name      = 'gd_post_types_' . date( 'dmyHi' );
		$file_url       = $file_url_base . $file_name . '.csv';
		$file_path      = $csv_file_dir . '/' . $file_name . '.csv';
		$file_path_temp = $csv_file_dir . '/post_types_' . $nonce . '.csv';

		if ( ! empty( $_post_types ) ) {
			$post_types = array();

			foreach ( $_post_types as $post_type ) {
				if ( geodir_is_gd_post_type( $post_type ) ) {
					$post_types[] = $post_type;
				}
			}
		} else {
			$post_types = geodir_get_posttypes( 'names' );
		}

		$count = count( $post_types );

		if ( ! $count > 0 ) {
			$json['error'] = __( 'No post type found.', 'geodir_custom_posts' );
		} else {
			$csv_rows = self::get_post_types_csv_rows( $post_types );

			if ( ! empty( $csv_rows ) ) {
				$csv_rows = array_merge( array( array_keys( $csv_rows[0] ) ), $csv_rows );
			}

			GeoDir_Admin_Import_Export::save_csv_data( $file_path_temp, $csv_rows, 0 );
			$export_files = array();

			if ( $wp_filesystem->exists( $file_path_temp ) ) {
				$csv_filename = $file_name . '_' . substr( geodir_rand_hash(), 0, 8 ) . '.csv';
				$file_path    = $csv_file_dir . '/' . $csv_filename;
				$wp_filesystem->move( $file_path_temp, $file_path, true );

				$file_url     = $file_url_base . $csv_filename;
				$export_files[] = array(
					'i' => '',
					'u' => $file_url,
					's' => size_format( filesize( $file_path ), 2 )
				);
			}

			if ( ! empty( $export_files ) ) {
				$json['total'] = $count;
				$json['files'] = $export_files;
			} else {
				$json['error'] = __( 'ERROR: Could not create csv file. This is usually due to inconsistent file permissions.', 'geodir_custom_posts' );
			}
		}

		return $json;
	}

	public static function get_post_types_csv_rows( $_post_types ) {
		$csv_rows = array();

		$post_types_array = geodir_get_posttypes( 'array' );
		$post_types = array();

		foreach ( $_post_types as $post_type ) {
			if ( empty( $post_types_array[ $post_type ] ) ) {
				continue;
			}

			$args = $post_types_array[ $post_type ];
			$post_types[ $post_type ] = $args;

			if ( ! isset( $args['limit_posts'] ) ) {
				$args['limit_posts'] = 0;
			}

			if ( ! isset( $args['classified_features'] ) ) {
				$args['classified_features'] = '';
			}

			$csv_row = array(
				'post_type' => $post_type,
				'slug' => ( ! empty( $args['rewrite']['slug'] ) ? $args['rewrite']['slug'] : $args['has_archive'] ),
				'name' => ( isset( $args['labels']['name'] ) ? $args['labels']['name'] : '' ),
				'singular_name' => ( isset( $args['labels']['singular_name'] ) ? $args['labels']['singular_name'] : '' ),
				'listing_order' => ( isset( $args['listing_order'] ) && $args['listing_order'] !== '' ? (int) $args['listing_order'] : '' ),
				'default_image' => ( ! empty( $args['default_image'] ) ? absint( $args['default_image'] ) : '' ),
				'menu_icon' => ( isset( $args['menu_icon'] ) ? $args['menu_icon'] : '' ),
				'disable_comments' => ( ! empty( $args['disable_comments'] ) ? 1 : 0 ),
				'disable_reviews' => ( ! empty( $args['disable_reviews'] ) ? 1 : 0 ),
				'single_review' => ( ! empty( $args['single_review'] ) ? 1 : 0 ),
				'disable_favorites' => ( ! empty( $args['disable_favorites'] ) ? 1 : 0 ),
				'disable_frontend_add' => ( ! empty( $args['disable_frontend_add'] ) ? 1 : 0 ),
				'supports_events' => ( ! empty( $args['supports_events'] ) || $post_type == 'gd_event' ? 1 : 0 ),
				'disable_location' => ( ! empty( $args['disable_location'] ) ? 1 : 0 ),
				'supports_franchise' => ( ! empty( $args['supports_franchise'] ) ? 1 : 0 ),
				'wpml_duplicate' => ( ! empty( $args['wpml_duplicate'] ) ? 1 : 0 ),
				'author_posts_private' => ( ! empty( $args['author_posts_private'] ) ? 1 : 0 ),
				'author_favorites_private' => ( ! empty( $args['author_favorites_private'] ) ? 1 : 0 ),
				'limit_posts' => ( (int) $args['limit_posts'] === 0 ? '' : ( (int) $args['limit_posts'] < 0 ? -1 : (int) $args['limit_posts'] ) ),
				'page_add' => ( ! empty( $args['page_add'] ) ? absint( $args['page_add'] ) : '' ),
				'page_details' => ( ! empty( $args['page_details'] ) ? absint( $args['page_details'] ) : '' ),
				'page_archive' => ( ! empty( $args['page_archive'] ) ? absint( $args['page_archive'] ) : '' ),
				'page_archive_item' => ( ! empty( $args['page_archive_item'] ) ? absint( $args['page_archive_item'] ) : '' ),
				'classified_features' => ( isset( $args['classified_features'] ) && is_array( $args['classified_features'] ) ? implode( ',', $args['classified_features'] ) : ( ! empty( $args['classified_features'] ) ? $args['classified_features'] : '' ) ),
				'link_posts_fields' => ( isset( $args['fill_fields'] ) && is_array( $args['fill_fields'] ) ? implode( ',', $args['fill_fields'] ) : ( ! empty( $args['fill_fields'] ) ? $args['fill_fields'] : '' ) ),
				'label-add_new' => ( isset( $args['labels']['add_new'] ) ? $args['labels']['add_new'] : '' ),
				'label-add_new_item' => ( isset( $args['labels']['add_new_item'] ) ? $args['labels']['add_new_item'] : '' ),
				'label-edit_item' => ( isset( $args['labels']['edit_item'] ) ? $args['labels']['edit_item'] : '' ),
				'label-new_item' => ( isset( $args['labels']['new_item'] ) ? $args['labels']['new_item'] : '' ),
				'label-view_item' => ( isset( $args['labels']['view_item'] ) ? $args['labels']['view_item'] : '' ),
				'label-search_items' => ( isset( $args['labels']['search_items'] ) ? $args['labels']['search_items'] : '' ),
				'label-not_found' => ( isset( $args['labels']['not_found'] ) ? $args['labels']['not_found'] : '' ),
				'label-not_found_in_trash' => ( isset( $args['labels']['not_found_in_trash'] ) ? $args['labels']['not_found_in_trash'] : '' ),
				'label-listing_owner' => ( isset( $args['labels']['listing_owner'] ) ? $args['labels']['listing_owner'] : '' ),
				'description' => ( isset( $args['description'] ) ? $args['description'] : '' ),
				'seo-title' => ( isset( $args['seo']['title'] ) ? $args['seo']['title'] : '' ),
				'seo-meta_title' => ( isset( $args['seo']['meta_title'] ) ? $args['seo']['meta_title'] : '' ),
				'seo-meta_description' => ( isset( $args['seo']['meta_description'] ) ? $args['seo']['meta_description'] : '' ),
			);

			if ( ! defined( 'GEODIR_EVENT_VERSION' ) ) {
				unset( $csv_row['supports_events'] );
			}

			if ( ! defined( 'GEODIRLOCATION_VERSION' ) ) {
				unset( $csv_row['disable_location'] );
			}

			if ( ! defined( 'GEODIR_FRANCHISE_VERSION' ) ) {
				unset( $csv_row['supports_franchise'] );
			}

			if ( ! defined( 'GEODIR_MULTILINGUAL_VERSION' ) ) {
				unset( $csv_row['wpml_duplicate'] );
			}

			/**
			 * Filters post type CSV row.
			 *
			 * @since 2.2.1
			 *
			 * @param array $csv_rows Post types CSV rows.
			 * @param array $post_types GD Post types.
			 */
			$csv_rows[] = apply_filters( 'geodir_cp_export_post_types_csv_row', $csv_row, $post_type, $args );
		}

		/**
		 * Filters post types CSV rows.
		 *
		 * @since 2.2.1
		 *
		 * @param array $csv_rows Post types CSV rows.
		 * @param array $post_types GD Post types.
		 */
		return apply_filters( 'geodir_cp_export_post_types_csv_rows', $csv_rows, $post_types );
	}

	public static function import_post_types() {
		$limit     = 1;
		$processed = isset( $_POST['processed'] ) ? (int) $_POST['processed'] : 0;

		$processed ++;
		$rows = GeoDir_Admin_Import_Export::get_csv_rows( $processed, $limit );

		if ( ! empty( $rows ) ) {
			// Set doing import constant.
			if ( ! defined( 'GEODIR_DOING_IMPORT_POST_TYPE' ) ) {
				define( 'GEODIR_DOING_IMPORT_POST_TYPE', true );
			}

			$created = 0;
			$updated = 0;
			$skipped = 0;
			$invalid = 0;
			$images  = 0;
			$errors  = array();

			$update_or_skip = isset( $_POST['_ch'] ) && $_POST['_ch'] == 'update' ? 'update' : 'skip';
			$log_error = __( 'GD IMPORT POST TYPES [ROW %d]:', 'geodir_custom_posts' );

			$i = 0;
			foreach ( $rows as $row ) {
				$i++;
				$line_no = $processed + $i;
				$line_error = wp_sprintf( $log_error, $line_no );
				$row = self::sanitize_post_type( $row, $update_or_skip == 'skip' );

				// Invalid
				if ( is_wp_error( $row ) ) {
					$invalid++;
					$errors[ $line_no ] = sprintf( esc_attr__('Row %d Error: %s', 'geodir_custom_posts'), $line_no, esc_attr( $row->get_error_message() ) );
					geodir_error_log( $line_error . ' ' . $row->get_error_message() );
					continue;
				}

				$exists = ! empty( $row['prev_cpt_data'] ) && is_array( $row['prev_cpt_data'] ) ? $row['prev_cpt_data'] : array();

				// Skip
				if ( $update_or_skip == 'skip' && ! empty( $exists ) ) {
					$skipped++;
					continue;
				}

				do_action( 'geodir_cp_pre_import_post_type_data', $row, $exists );

				// Save post type
				$response = self::save_post_type( $row, $exists );

				if ( is_wp_error( $response ) ) {
					$invalid++;
					$errors[ $line_no ] = sprintf( esc_attr__('Row %d Error: %s', 'geodir_custom_posts'), $line_no, esc_attr( $response->get_error_message() ) );
					geodir_error_log( $line_error . ' ' . $response->get_error_message() );
					continue;
				}

				if ( ! empty( $exists ) ) {
					$updated++;
				} else {
					$created++;
				}

				do_action( 'geodir_cp_after_import_post_type_data', $row, $exists );
			}

		} else {
			return new WP_Error( 'gd-csv-empty', __( "No data found in csv file.", "geodir_custom_posts" ) );
		}

		return array(
			"processed" => $processed,
			"created"   => $created,
			"updated"   => $updated,
			"skipped"   => $skipped,
			"invalid"   => $invalid,
			"images"    => $images,
			"errors"    => $errors,
			"ID"        => 0,
		);
	}

	public static function sanitize_post_type( $data, $skip = false ) {
		$data = array_map( 'trim', $data );

		$post_types = geodir_get_posttypes( 'array' );
		$post_type = '';
		$prev_cpt_data = array();

		// Post type
		if ( ! empty( $data['post_type'] ) ) {
			$post_type = str_replace( "-", "_", sanitize_key( $data['post_type'] ) );

			if ( strpos( $post_type, 'gd_' ) !== 0 ) {
				$post_type = 'gd_' . $post_type;
			}

			$data['post_type'] = $post_type;

			if ( post_type_exists( $post_type ) ) {
				if ( ! empty( $post_types[ $post_type ] ) ) {
					$prev_cpt_data = $post_types[ $post_type ];
					$data['prev_cpt_data'] = $prev_cpt_data;

					// Skip row on post type exists for skip action.
					if ( $skip ) {
						return $data;
					}
				} else {
					return new WP_Error( 'gd_invalid_post_type', wp_sprintf( __( 'Non GD post type already exists with name %s.', 'geodir_custom_posts' ), $post_type ) );
				}
			}
		}

		if ( empty( $data['post_type'] ) ) {
			return new WP_Error( 'gd_invalid_post_type', __( 'Invalid or missing post type.', 'geodir_custom_posts' ) );
		}

		// Post type slug
		if ( ! empty( $data['slug'] ) ) {
			$data['slug'] = sanitize_key( $data['slug'] );
		} else if ( ! empty( $prev_cpt_data ) && ! empty( $prev_cpt_data['rewrite']['slug'] ) ) {
			$data['slug'] = $prev_cpt_data['rewrite']['slug'];
		}

		if ( empty( $data['slug'] ) ) {
			return new WP_Error( 'gd_invalid_post_type_slug', __( 'Invalid or missing post type slug.', 'geodir_custom_posts' ) );
		}

		foreach ( $post_types as $_post_type => $_data ) {
			if ( $_post_type != $data['post_type'] && ! empty( $_data['rewrite']['slug'] ) && $_data['rewrite']['slug'] == $data['slug'] ) {
				return new WP_Error( 'gd_invalid_post_type', wp_sprintf( __( 'Post type already exists with slug %s.', 'geodir_custom_posts' ), $data['slug'] ) );
			}
		}

		$text_keys = array( 'post_type', 'slug', 'name', 'singular_name', 'menu_icon' );
		$int_keys = array( 'listing_order', 'limit_posts', 'default_image', 'page_add', 'page_details', 'page_archive', 'page_archive_item', 'default_image', 'default_image', 'default_image', 'default_image' );
		$bool_keys = array( 'disable_comments', 'disable_reviews', 'single_review', 'disable_favorites', 'disable_frontend_add', 'supports_events', 'disable_location', 'supports_franchise', 'author_posts_private', 'author_favorites_private', 'author_posts_private', 'author_posts_private', 'author_posts_private', 'author_posts_private' );

		$args = array();
		$args['post_type'] = $data['post_type'];
		$args['slug'] = $data['slug'];

		$text_keys = array(  'name', 'singular_name', 'menu_icon', 'past_event', 'past_event_days', 'past_event_status' );
		foreach ( $text_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$args[ $key ] = $data[ $key ] !== '' ? sanitize_text_field( $data[ $key ] ) : '';
			} else if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data[ $key ] ) ) {
				$args[ $key ] = $prev_cpt_data[ $key ];
			}
		}

		if ( empty( $data['name'] ) || empty( $data['singular_name'] ) ) {
			return new WP_Error( 'gd_invalid_post_type', __( 'Post type name / singular name is invalid.', 'geodir_custom_posts' ) );
		}

		$int_keys = array( 'listing_order', 'limit_posts', 'default_image', 'page_add', 'page_details', 'page_archive', 'page_archive_item', 'template_add', 'template_details', 'template_archive' );
		foreach ( $int_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$args[ $key ] = ! empty( $data[ $key ] ) ? (int) $data[ $key ] : '';
			} else if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data[ $key ] ) ) {
				$args[ $key ] = $prev_cpt_data[ $key ];
			}
		}

		$bool_keys = array( 'disable_comments', 'disable_reviews', 'single_review', 'disable_favorites', 'disable_frontend_add', 'supports_events', 'disable_location', 'supports_franchise', 'wpml_duplicate', 'author_posts_private', 'author_favorites_private', 'author_posts_private', 'author_posts_private', 'author_posts_private', 'author_posts_private' );
		foreach ( $bool_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$args[ $key ] = ! empty( $data[ $key ] ) ? $data[ $key ] : 0;
			} else if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data[ $key ] ) ) {
				$args[ $key ] = $prev_cpt_data[ $key ];
			}
		}

		// Labels
		$labels = array( 'add_new', 'add_new_item', 'edit_item', 'new_item', 'view_item', 'search_items', 'not_found', 'not_found_in_trash', 'listing_owner' );
		foreach ( $labels as $key ) {
			if ( isset( $data[ 'label-' . $key ] ) ) {
				$args[ $key ] = $data[ 'label-' . $key ] !== '' ? sanitize_text_field( $data[ 'label-' . $key ] ) : '';
			} else if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data['labels'][ $key ] ) ) {
				$args[ $key ] = $prev_cpt_data['labels'][ $key ];
			}
		}

		$seos = array( 'title', 'meta_title', 'meta_description' );
		foreach ( $seos as $key ) {
			if ( isset( $data[ 'seo-' . $key ] ) ) {
				$args[ $key ] = $data[ 'seo-' . $key ] !== '' ? sanitize_text_field( $data[ 'seo-' . $key ] ) : '';
			} else if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data['seo'][ $key ] ) ) {
				$args[ $key ] = $prev_cpt_data['seo'][ $key ];
			}
		}

		// Description
		if ( isset( $data['description'] ) ) {
			$args['description'] = $data['description'] != '' ? geodir_sanitize_html_field( $data['description'] ) : '';
		} else if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data['description'] ) ) {
			$args['description'] = $prev_cpt_data['description'];
		}

		// Linked Posts Fields
		if ( isset( $data['link_posts_fields'] ) ) {
			$value = $data['link_posts_fields'] !== '' ? sanitize_text_field( $data['link_posts_fields'] ) : '';
			if ( ! empty( $value ) ) {
				$value = array_map( 'trim', explode( ",", $value ) );
				$value = array_filter( array_unique( $value ) );
			} else {
				$value = array();
			}
			$args['fill_fields'] = $value;
		} else if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data['fill_fields'] ) ) {
			$args['fill_fields'] = $prev_cpt_data['fill_fields'];
		}

		// Classified Features
		if ( isset( $data['classified_features'] ) ) {
			$value = $data['classified_features'] !== '' ? sanitize_text_field( $data['classified_features'] ) : '';
			if ( ! empty( $value ) ) {
				$value = array_map( 'trim', explode( ",", $value ) );
				$value = array_filter( array_unique( $value ) );
			} else {
				$value = array();
			}
			$args['classified_features'] = $value;

			if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data['classified_features'] ) ) {
				$args['prev_classified_features'] = ! empty( $prev_cpt_data['classified_features'] ) ? implode( ',', $prev_cpt_data['classified_features'] ) : '';
			}
		} else if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data['classified_features'] ) ) {
			$args['classified_features'] = $prev_cpt_data['classified_features'];
		}

		if ( defined( 'GEODIR_EVENT_VERSION' ) ) {
			if ( $args['post_type'] == 'gd_event' ) {
				$args['supports_events'] = true;
			} else if ( isset( $args['supports_events'] ) && ! empty( $prev_cpt_data ) && isset( $prev_cpt_data['supports_events'] ) ) {
				$args['prev_supports_events'] = ! empty( $prev_cpt_data['supports_events'] ) ? 'y' : 'n';
			}
		} else {
			$unset = array( 'supports_events', 'past_event', 'past_event_days', 'past_event_status' );

			foreach ( $unset as $_unset ) {
				if ( isset( $args[ $_unset ] ) ) {
					unset( $args[ $_unset ] );
				}
			}
		}

		if ( isset( $args['disable_location'] ) ) {
			if ( ! defined( 'GEODIRLOCATION_VERSION' ) ) {
				unset( $args['disable_location'] );
			} else {
				if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data['disable_location'] ) ) {
					$args['prev_disable_location'] = ! empty( $prev_cpt_data['disable_location'] ) ? 'y' : 'n';
				}
			}
		}

		if ( isset( $args['supports_franchise'] ) ) {
			if ( ! defined( 'GEODIR_FRANCHISE_VERSION' ) ) {
				unset( $args['supports_franchise'] );
			} else {
				if ( ! empty( $prev_cpt_data ) && isset( $prev_cpt_data['supports_franchise'] ) ) {
					$args['prev_supports_franchise'] = ! empty( $prev_cpt_data['supports_franchise'] ) ? 'y' : 'n';
				}
			}
		}

		if ( isset( $args['wpml_duplicate'] ) && ! defined( 'GEODIR_MULTILINGUAL_VERSION' ) ) {
			unset( $args['wpml_duplicate'] );
		}


		if ( isset( $args['listing_order'] ) ) {
			$args['order'] = $args['listing_order'];
			unset( $args['listing_order'] );
		}

		if ( ! empty( $prev_cpt_data ) ) {
			$args[ 'prev_cpt_data' ] = $prev_cpt_data;
		}

		return apply_filters( 'geodir_cp_import_sanitize_post_type', $args, $data, $prev_cpt_data );
	}

	/**
	 * Sanatize post type.
	 *
	 * @since 2.2.1
	 *
	 * @param array $data An array sanatize post type.
	 * @return array $output.
	 */
	public static function save_post_type( $data, $prev_data = array() ) {
		global $current_section;

		$data = stripslashes_deep( $data );

		$post_type = str_replace( "-", "_", sanitize_key( $data['post_type' ] ) );
		$name = sanitize_text_field( $data['name'] );
		$singular_name = sanitize_text_field( $data['singular_name'] );
		$slug = sanitize_key( $data['slug'] );

		$args = array();
		$args['labels'] = array(
			'name' => $name,
			'singular_name' => $singular_name,
			'add_new' => ! empty( $data['add_new'] ) ? sanitize_text_field( $data['add_new'] ) : _x( 'Add New', $post_type, 'geodirectory' ),
			'add_new_item' => ! empty( $data['add_new_item'] ) ? sanitize_text_field( $data['add_new_item'] ) : __( 'Add New ' . $singular_name, 'geodirectory' ),
			'edit_item' => ! empty( $data['edit_item'] ) ? sanitize_text_field( $data['edit_item'] ) : __( 'Edit ' . $singular_name, 'geodirectory' ),
			'new_item' => ! empty( $data['new_item'] ) ? sanitize_text_field( $data['new_item'] ) : __( 'New ' . $singular_name, 'geodirectory' ),
			'view_item' => ! empty( $data['view_item'] ) ? sanitize_text_field( $data['view_item'] ) : __( 'View ' . $singular_name, 'geodirectory' ),
			'search_items' => ! empty( $data['search_items'] ) ? sanitize_text_field( $data['search_items'] ) : __( 'Search ' . $name, 'geodirectory' ),
			'not_found' => ! empty( $data['not_found'] ) ? sanitize_text_field( $data['not_found'] ) : __( 'No ' . $name . ' found.', 'geodirectory' ),
			'not_found_in_trash' => ! empty( $data['not_found_in_trash'] ) ? sanitize_text_field( $data['not_found_in_trash'] ) : __( 'No ' . $name . ' found in trash.', 'geodirectory' ),
			'listing_owner' => ! empty( $data['listing_owner'] ) ? sanitize_text_field( $data['listing_owner'] ) : ''
		);
		$args['description'] = ! empty( $data['description'] ) ? trim( $data['description'] ) : '';
		$args['can_export'] = true;
		$args['capability_type'] = 'post';
		$args['has_archive'] = $slug;
		$args['hierarchical'] = false;
		$args['map_meta_cap'] = true;
		$args['public'] = true;
		$args['query_var'] = true;
		$args['show_in_nav_menus'] = true;
		$args['rewrite'] = array(
			'slug' => $slug,
			'with_front' => false,
			'hierarchical' => true,
			'feeds' => true
		);
		$args['supports'] = array(
			'title',
			'editor',
			'author',
			'thumbnail',
			'excerpt',
			'custom-fields',
			'comments',
			'revisions'
		);
		$args['taxonomies'] = array( 
			$post_type . "category",
			$post_type . "_tags"
		);
		$args['listing_order'] = ! empty( $data['order'] ) ? absint( $data['order'] ) : 0;
		$args['disable_comments'] = ! empty( $data['disable_comments'] ) ? absint( $data['disable_comments'] ) : 0;
		$args['disable_reviews'] = ! empty( $data['disable_reviews'] ) ? absint( $data['disable_reviews'] ) : 0;
		$args['single_review'] = ! empty( $data['single_review'] ) ? absint( $data['single_review'] ) : 0;
		$args['disable_favorites'] = ! empty( $data['disable_favorites'] ) ? absint( $data['disable_favorites'] ) : 0;
		$args['disable_frontend_add'] = ! empty( $data['disable_frontend_add'] )  ? absint( $data['disable_frontend_add'] ) : 0;
		$args['author_posts_private'] = ! empty( $data['author_posts_private'] ) ? absint( $data['author_posts_private'] ) : 0;
		$args['author_favorites_private'] = ! empty( $data['author_favorites_private'] ) ? absint( $data['author_favorites_private'] ) : 0;
		$args['limit_posts'] = ! empty( $data['limit_posts'] ) && $data['limit_posts'] ? (int) $data['limit_posts'] : 0;
		$args['seo']['title'] = ! empty( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
		$args['seo']['meta_title'] = ! empty( $data['meta_title'] ) ? sanitize_text_field( $data['meta_title'] ) : '';
		$args['seo']['meta_description'] = ! empty( $data['meta_description'] ) ? sanitize_text_field( $data['meta_description'] ) : '';
		$args['menu_icon'] = ! empty( $data['menu_icon'] ) ? GeoDir_Post_types::sanitize_menu_icon( $data['menu_icon'] ) : 'dashicons-admin-post';
		$args['default_image'] = ! empty( $data['default_image'] ) ? $data['default_image'] : '';
		$args['page_add'] = ! empty( $data['page_add'] ) ? (int) $data['page_add'] : 0;
		$args['page_details'] = ! empty( $data['page_details'] ) ? (int) $data['page_details'] : 0;
		$args['page_archive'] = ! empty( $data['page_archive'] ) ? (int) $data['page_archive'] : 0;
		$args['page_archive_item'] = ! empty( $data['page_archive_item'] ) ? (int) $data['page_archive_item'] : 0;
		$args['template_add'] = ! empty( $data['template_add'] ) ? (int) $data['template_add'] : 0;
		$args['template_details'] = ! empty( $data['template_details'] ) ? (int) $data['template_details'] : 0;
		$args['template_archive'] = ! empty( $data['template_archive'] ) ? (int) $data['template_archive'] : 0;

		$save_data = array();
		$save_data[ $post_type ] = $args;

		$set_vars = array( 'prev_classified_features', 'prev_supports_events', 'prev_supports_franchise', 'prev_disable_location' );
		foreach ( $set_vars as $var ) {
			if ( isset( $data[ $var ] ) ) {
				$_POST[ $var ] = $data[ $var ]; // @codingStandardsIgnoreLine
			}
		}

		$save_data = apply_filters( 'geodir_save_post_type', $save_data, $post_type, $data );

		$current_post_types = geodir_get_option( 'post_types', array() );
		if ( empty( $current_post_types ) ) {
			$post_types = $save_data;
		} else {
			$post_types = array_merge( $current_post_types, $save_data );
		}

		foreach ( $save_data as $_post_type => $_args ) {
			$cpt_before = ! empty( $current_post_types[ $_post_type ] ) ? $current_post_types[ $_post_type ] : array();

			do_action( 'geodir_pre_save_post_type', $_post_type, $_args, $cpt_before );
		}

		// Update custom post types
		geodir_update_option( 'post_types', $post_types );
		
		// create tables if needed
		GeoDir_Admin_Install::create_tables();

		foreach ( $save_data as $_post_type => $_args ) {
			do_action( 'geodir_post_type_saved', $_post_type, $_args, empty( $cpt_before ) );
		}

		$post_types = geodir_get_option( 'post_types', array() );

		foreach ( $save_data as $_post_type => $_args ) {
			$cpt_before = ! empty( $current_post_types[ $_post_type ] ) ? $current_post_types[ $_post_type ] : array();
			$cpt_after = ! empty( $post_types[ $_post_type ] ) ? $post_types[ $_post_type ] : array();

			do_action( 'geodir_post_type_updated', $_post_type, $cpt_after, $cpt_before );
		}

		// flush rewrite rules
		flush_rewrite_rules();
		do_action( 'geodir_flush_rewrite_rules' );
		wp_schedule_single_event( time(), 'geodir_flush_rewrite_rules' );
	}

	public static function prepare_export_custom_fields() {
		global $wpdb;

		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();
		$post_type = ! empty( $data['post_type'] ) ? geodir_clean( $data['post_type'] ) : '';
		$fields = isset( $data['fields'] ) && ! empty( $post_type ) ? array_map( 'absint', array_filter( $data['fields'] ) ) : array();

		$where = array();
		if ( $post_type != '' ) {
			$where[] = $wpdb->prepare( "post_type = %s", array( $post_type ) );
		} else {
			$post_types = geodir_get_posttypes();
			$where[] = "post_type IN('" . implode( "','", $post_types ) . "')";
		}

		if ( ! empty( $fields ) ) {
			$where[] = "id IN(" . implode( ",", $fields ) . ")";
		}

		$where = implode( " AND ", $where );

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `" . GEODIR_CUSTOM_FIELDS_TABLE . "` WHERE {$where}" );

		return array( 'total' => $count );
	}

	public static function export_custom_fields() {
		global $wp_filesystem;

		$csv_file_dir   = GeoDir_Admin_Import_Export::import_export_cache_path( false );
		$file_url_base  = GeoDir_Admin_Import_Export::import_export_cache_path() . '/';
		$nonce          = isset( $_REQUEST['_nonce'] ) ? $_REQUEST['_nonce'] : null;
		$data           = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();
		$post_type      = ! empty( $data['post_type'] ) ? geodir_clean( $data['post_type'] ) : '';
		$fields         = isset( $data['fields'] ) && ! empty( $post_type ) ? array_map( 'absint', array_filter( $data['fields'] ) ) : array();
		$file_name      = ( $post_type != '' ? $post_type : 'gd' ) . '_custom_fields_' . date( 'dmyHi' );
		$file_url       = $file_url_base . $file_name . '.csv';
		$file_path      = $csv_file_dir . '/' . $file_name . '.csv';
		$file_path_temp = $csv_file_dir . '/custom_fields_' . $nonce . '.csv';

		$csv_rows = self::get_custom_fields_csv_rows( $post_type, $fields );

		if ( empty( $csv_rows ) ) {
			$json['error'] = __( 'No custom fields not found.', 'geodir_custom_posts' );
		} else {
			$csv_rows = array_merge( array( array_keys( $csv_rows[0] ) ), $csv_rows );

			GeoDir_Admin_Import_Export::save_csv_data( $file_path_temp, $csv_rows, 0 );
			$export_files = array();

			if ( $wp_filesystem->exists( $file_path_temp ) ) {
				$csv_filename = $file_name . '_' . substr( geodir_rand_hash(), 0, 8 ) . '.csv';
				$file_path    = $csv_file_dir . '/' . $csv_filename;
				$wp_filesystem->move( $file_path_temp, $file_path, true );

				$file_url     = $file_url_base . $csv_filename;
				$export_files[] = array(
					'i' => '',
					'u' => $file_url,
					's' => size_format( filesize( $file_path ), 2 )
				);
			}

			if ( ! empty( $export_files ) ) {
				$json['total'] = count( $csv_rows );
				$json['files'] = $export_files;
			} else {
				$json['error'] = __( 'ERROR: Could not create csv file. This is usually due to inconsistent file permissions.', 'geodir_custom_posts' );
			}
		}

		return $json;
	}

	public static function import_custom_fields() {
		$limit = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 1;
		if ( $limit < 1 ) {
			$limit = 1;
		}
		$processed = isset( $_POST['processed'] ) ? (int) $_POST['processed'] : 0;

		$processed ++;
		$rows = GeoDir_Admin_Import_Export::get_csv_rows( $processed, $limit );

		if ( ! empty( $rows ) ) {
			// Set doing import constant.
			if ( ! defined( 'GEODIR_DOING_IMPORT_CUSTOM_FIELD' ) ) {
				define( 'GEODIR_DOING_IMPORT_CUSTOM_FIELD', true );
			}

			$created = 0;
			$updated = 0;
			$skipped = 0;
			$invalid = 0;
			$images  = 0;
			$errors  = array();

			$update_or_skip = isset( $_POST['_ch'] ) && $_POST['_ch'] == 'update' ? 'update' : 'skip';
			$log_error = __( 'GD IMPORT CUSTOM_FIELDS [ROW %d]:', 'geodir_custom_posts' );

			$cpt_cf = new GeoDir_Settings_Cpt_Cf();

			$i = 0;
			foreach ( $rows as $row ) {
				$i++;
				$line_no = $processed + $i;
				$line_error = wp_sprintf( $log_error, $line_no );
				if ( isset( $row['id'] ) ) {
					unset( $row['id'] );
				}
				$row = self::sanitize_custom_field( $row, $update_or_skip == 'skip' );

				// Invalid
				if ( is_wp_error( $row ) ) {
					$invalid++;
					$errors[ $line_no ] = sprintf( esc_attr__('Row %d Error: %s', 'geodir_custom_posts'), $line_no, esc_attr( $row->get_error_message() ) );
					geodir_error_log( $line_error . ' ' . $row->get_error_message() );
					continue;
				}

				$exists = ! empty( $row['prev_field_data'] ) && is_array( $row['prev_field_data'] ) ? $row['prev_field_data'] : array();

				// Skip
				if ( $update_or_skip == 'skip' && ! empty( $exists ) ) {
					$skipped++;
					continue;
				}

				do_action( 'geodir_cp_pre_import_custom_field_data', $row, $exists );

				// Save post type
				$response = $cpt_cf->save_custom_field( $row );

				if ( is_wp_error( $response ) ) {
					$invalid++;
					$errors[ $line_no ] = sprintf( esc_attr__('Row %d Error: %s', 'geodir_custom_posts'), $line_no, esc_attr( $response->get_error_message() ) );
					geodir_error_log( $line_error . ' ' . $response->get_error_message() );
					continue;
				}

				if ( ! empty( $exists ) ) {
					$updated++;
				} else {
					$created++;
				}

				do_action( 'geodir_cp_after_import_custom_field_data', $row, $exists );
			}

		} else {
			return new WP_Error( 'gd-csv-empty', __( "No data found in csv file.", "geodir_custom_posts" ) );
		}

		return array(
			"processed" => $processed,
			"created"   => $created,
			"updated"   => $updated,
			"skipped"   => $skipped,
			"invalid"   => $invalid,
			"images"    => $images,
			"errors"    => $errors,
			"ID"        => 0,
		);
	}

	public static function get_custom_fields( $post_type, $fields = array() ) {
		global $wpdb;

		$where = array();
		if ( $post_type != '' ) {
			$where[] = $wpdb->prepare( "post_type = %s", array( $post_type ) );
		} else {
			$post_types = geodir_get_posttypes();
			$where[] = "post_type IN ('" . implode( "','", $post_types ) . "')";
		}

		if ( ! empty( $fields ) ) {
			$where[] = "id IN(" . implode( ",", $fields ) . ")";
		}

		$where = implode( " AND ", $where );

		$results = $wpdb->get_results( "SELECT * FROM `" . GEODIR_CUSTOM_FIELDS_TABLE . "` WHERE {$where} ORDER BY post_type ASC, sort_order ASC, admin_title ASC" );

		return $results;
	}

	public static function get_custom_fields_csv_rows( $post_type, $fields = array() ) {
		$results = self::get_custom_fields( $post_type, $fields );
		if ( empty( $results ) ) {
			return array();
		}

		$rows = array();

		foreach ( $results as $field ) {
			$field = stripslashes_deep( $field );
			$extra_fields = self::export_extra_fields( $field->extra_fields );

			$row = array(
				'id' => $field->id,
				'field_key' => $field->htmlvar_name,
				'post_type' => $field->post_type,
				'frontend_title' => $field->frontend_title,
				'admin_title' => $field->admin_title,
				'field_type' => $field->field_type,
				'data_type' => $field->data_type,
				'description' => $field->frontend_desc,
				'placeholder_text' => $field->placeholder_value,
				'default_value' => $field->default_value,
				'option_values' => $field->option_values,
				'is_active' => $field->is_active,
				'sort_order' => $field->sort_order,
				'tab_parent' => $field->tab_parent,
				'tab_level' => $field->tab_level,
				'output_location' => str_replace( array( "[", "]" ), array( "", "" ), $field->show_in ),
				'for_admin_use' => $field->for_admin_use,
				'package_ids' => $field->packages,
				'show_in_sorting' => $field->cat_sort,
				'field_icon' => $field->field_icon,
				'css_class' => $field->css_class,
				'decimal_point' => $field->decimal_point,
				'is_required' => $field->is_required,
				'required_message' => $field->required_msg,
				'validation_pattern' => $field->validation_pattern,
				'validation_message' => $field->validation_msg,
				'extra_fields' => $extra_fields['extra_fields'],
				'conditional_fields' => $extra_fields['conditional_fields'],
			);

			/**
			 * Filters custom field CSV row.
			 *
			 * @since 2.2.1
			 *
			 * @param array $row Custom field CSV row.
			 * @param object $field Field object.
			 */
			$rows[] = apply_filters( 'geodir_cp_export_custom_field_csv_row', $row, $field );
		}

		/**
		 * Filters custom fields CSV rows.
		 *
		 * @since 2.2.1
		 *
		 * @param array $rows Custom fields CSV rows.
		 * @param object $results Fields object.
		 * @param string $post_type Requested post type.
		 */
		return apply_filters( 'geodir_cp_export_custom_fields_csv_rows', $rows, $results, $post_type );
	}

	public static function export_extra_fields( $extra_fields ) {
		$extra_fields = ! empty( $extra_fields ) ? maybe_unserialize( $extra_fields ) : $extra_fields;

		if ( is_scalar( $extra_fields ) ) {
			return array( 'extra_fields' => $extra_fields, 'conditional_fields' => '' );
		}

		$conditions = '';
		if ( ! empty( $extra_fields['conditions'] ) ) {
			$_conditions = $extra_fields['conditions'];

			unset( $extra_fields['conditions'] );

			if ( is_array( $_conditions ) ) {
				$i = 1;
				$a_conditions = array();

				foreach ( $_conditions as $key => $condition ) {
					if ( ! empty( $condition['action'] ) && ! empty( $condition['field'] ) ) {
						$condition = wp_parse_args( $condition, array( 'action' => '', 'field' => '', 'condition' => '', 'value' => '' ) );

						foreach ( $condition as $k => $v ) {
							if ( is_numeric( $v ) ) {
								$a_conditions[] = $k . '_' . $i . '=' . $v;
							} else {
								$a_conditions[] = $k . '_' . $i . '="' . esc_attr( $v ) . '"';
							}
						}

						$i++;
					}
				}

				if ( ! empty( $a_conditions ) ) {
					$conditions = implode( " ", $a_conditions );
				}
			}
		}

		$field_keys = self::extra_fields_keys();
		$_extra_fields = array();

		foreach ( $extra_fields as $key => $value ) {
			if ( $key == 'gd_file_types' ) {
				$_value = maybe_unserialize( $value );

				if ( is_array( $_value ) ) {
					$value = implode( ',', array_filter( $_value ) );
				} else {
					$value = $_value;
				}
			}

			if ( is_array( $value ) ) {
				$value = maybe_serialize( $value );
			}

			if ( $key && isset( $field_keys[ $key ] ) ) {
				$key = $field_keys[ $key ];
			}

			if ( is_numeric( $value ) ) {
				$_extra_fields[] = $key . '=' . $value;
			} else {
				$_extra_fields[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$extra_fields = implode( " ", $_extra_fields );

		return array( 'extra_fields' => $extra_fields, 'conditional_fields' => $conditions );
	}

	public static function switch_fields_keys( $import = false ) {
		$keys = array(
			'htmlvar_name' => 'field_key',
			'frontend_desc' => 'description',
			'placeholder_value' => 'placeholder_text',
			'show_in' => 'output_location',
			'packages' => 'package_ids',
			'cat_sort' => 'show_in_sorting',
			'required_msg' => 'required_message',
			'validation_msg' => 'validation_message'
		);

		// Flip during import.
		if ( $import ) {
			$keys = array_flip( $keys );
		}

		return $keys;
	}

	public static function extra_fields_keys( $import = false ) {
		$keys = array(
			'cat_display_type' => 'category_input_type',
			'city_lable' => 'city_label',
			'region_lable' => 'region_label',
			'country_lable' => 'country_label',
			'neighbourhood_lable' => 'neighbourhood_label',
			'street2_lable' => 'street2_label',
			'zip_lable' => 'zip_label',
			'map_lable' => 'map_label',
			'mapview_lable' => 'mapview_label',
			'multi_display_type' => 'multiselect_input_type',
			'currency_symbol_placement' => 'currency_symbol_position',
			'gd_file_types' => 'allowed_file_types',
			'max_posts' => 'link_max_posts',
			'all_posts' => 'link_all_posts'
		);

		// Flip during import.
		if ( $import ) {
			$keys = array_flip( $keys );
		}

		return $keys;
	}

	public static function sanitize_custom_field( $data, $skip = false ) {
		$data = array_map( 'trim', $data );

		if ( isset( $data['field_id'] ) ) {
			unset( $data['field_id'] );
		}

		$args = $data;
		$switch_keys = self::switch_fields_keys();

		$data_keys = array_keys( $args );
		foreach ( $switch_keys as $imp_key => $exp_key ) {
			if ( in_array( $exp_key, $data_keys ) && ! in_array( $imp_key, $data_keys ) ) {
				$args[ $imp_key ] = $args[ $exp_key ];
				unset( $args[ $exp_key ] );
			}
		}

		// Post type
		$post_type = ! empty( $args['post_type'] ) ? sanitize_text_field( $args['post_type'] ) : null;

		if ( ! ( $post_type && geodir_is_gd_post_type( $post_type ) ) ) {
			return new WP_Error( 'gd_invalid_post_type', __( 'Invalid post type.', 'geodir_custom_posts' ) );
		}

		// htmlvar_name
		$prev_field_data = array();
		if ( ! empty( $args['htmlvar_name'] ) ) {
			$args['htmlvar_name'] = sanitize_text_field( $args['htmlvar_name'] );

			$prev_field_data = geodir_get_field_infoby( 'htmlvar_name', $args['htmlvar_name'], $post_type );

			if ( ! empty( $prev_field_data ) ) {
				$args['prev_field_data'] = $prev_field_data;

				// Skip row on field exists for skip action.
				if ( $skip ) {
					return $args;
				}

				$args['field_id'] = $prev_field_data['id'];
				$args['htmlvar_name'] = $prev_field_data['htmlvar_name'];
			}
		}

		if ( empty( $args['admin_title'] ) && empty( $args['frontend_title'] ) ) {
			return new WP_Error( 'gd_invalid_title', __( 'Missing or invalid admin title & frontend title.', 'geodir_custom_posts' ) );
		}

		if ( ! empty( $args['extra_fields'] ) ) {
			$_extra_fields = $args['extra_fields'];
			$_extra_fields = shortcode_parse_atts( $_extra_fields );

			$extra_fields = ! empty( $prev_field_data['extra_fields'] ) ? stripslashes_deep( maybe_unserialize( $prev_field_data['extra_fields'] ) ) : array();
			if ( ! is_array( $extra_fields ) ) {
				$extra_fields = (array) $extra_fields;
			}

			$field_keys = self::extra_fields_keys( true );
			foreach ( $_extra_fields as $key => $value ) {
				if ( isset( $field_keys[ $key ] ) ) {
					$key = $field_keys[ $key ];
				}
				$extra_fields[ $key ] = geodir_clean( $value );
			}
			$args['extra_fields'] = $extra_fields;
		}

		if ( isset( $args['conditional_fields'] ) ) {
			$_conditional_fields = $args['conditional_fields'];
			$_conditional_fields = $_conditional_fields ? shortcode_parse_atts( $_conditional_fields ) : array();
			$conditional_fields = array( 'TEMP' => array() );

			if ( ! empty( $_conditional_fields ) && is_array( $_conditional_fields ) ) {
				for ( $k = 1; $k <= 10; $k++ ) {
					if ( ! empty( $_conditional_fields['action_' . $k ] ) && ! empty( $_conditional_fields['field_' . $k ] ) && ! empty( $_conditional_fields['condition_' . $k ] ) ) {
						$conditional_fields[] = array(
							'action' => $_conditional_fields['action_' . $k ],
							'field' => $_conditional_fields['field_' . $k ],
							'condition' => $_conditional_fields['condition_' . $k ],
							'value' => ( isset( $_conditional_fields['value_' . $k ] ) ? $_conditional_fields['value_' . $k ] : '' ),
						);
					}
				}
			}
			$args['conditional_fields'] = $conditional_fields;
		}

		if ( ! empty( $prev_field_data ) ) {
			$field_keys = array_keys( $prev_field_data );

			$cf_keys = array( 'field_id', 'post_type', 'admin_title', 'frontend_title', 'field_type', 'field_type_key', 'htmlvar_name', 'frontend_desc', 'clabels', 'default_value', 'db_default', 'placeholder_value', 'sort_order', 'is_active', 'is_default', 'is_required', 'required_msg', 'css_class', 'field_icon', 'show_in', 'option_values', 'packages', 'cat_sort', 'cat_filter', 'data_type', 'extra_fields', 'decimal_point', 'validation_pattern', 'validation_msg', 'for_admin_use', 'add_column', 'data_type' );

			foreach ( $cf_keys as $key ) {
				if ( ! in_array( $key, $data_keys ) && in_array( $key, $field_keys ) ) {
					$args[ $key ] = $prev_field_data[ $key ];
				}
			}
		}

		if ( ! empty( $args['show_in'] ) ) {
			$_show_in = explode( ",", str_replace( array( "[", "]" ), array( "", "" ), sanitize_text_field( $args['show_in'] ) ) );

			$show_in = array();
			foreach ( $_show_in as $key ) {
				$key = trim( $key );
				if ( $key ) {
					$show_in[] = '[' . $key . ']';
				}
			}
			$args['show_in'] = $show_in;
		}

		if ( ! empty( $args['data_type'] ) ) {
			$args['data_type'] = strtoupper( $args['data_type'] );
		}

		if ( ! empty( $args['validation_pattern'] ) ) {
			$args['validation_pattern'] = addslashes_gpc( $args['validation_pattern'] );
		}

		if ( isset( $args['packages'] ) ) {
			$args['show_on_pkg'] = ! empty( $args['packages'] ) ? explode( ",", sanitize_text_field( $args['packages'] ) ) : '';
			unset( $args['packages'] );
		}

		if ( isset( $args['extra_fields'] ) ) {
			$args['extra'] = $args['extra_fields'];
			unset( $args['extra_fields'] );
		}

		if ( ! empty( $args['tab_parent'] ) || ! empty( $args['tab_level'] ) ) {
			if ( ! empty( $args['tab_parent'] ) ) {
				$exists = self::is_field_exists( (int) $args['tab_parent'], $post_type );

				if ( ! empty( $exists ) ) {
					$args['tab_level'] = 1;
				} else {
					$args['tab_parent'] = 0;
					$args['tab_level'] = 0;
				}
			} else if ( isset( $args['tab_parent'] ) && empty( $args['tab_parent'] ) && ! empty( $args['tab_level'] ) ) {
				$args['tab_level'] = 0;
			}
		}

		return apply_filters( 'geodir_cp_import_sanitize_custom_field', $args, $data, $prev_field_data );
	}

	public static function get_cpt_tabs( $post_type, $items = array() ) {
		global $wpdb;

		$where = array();
		if ( $post_type != '' ) {
			$where[] = $wpdb->prepare( "post_type = %s", array( $post_type ) );
		} else {
			$post_types = geodir_get_posttypes();
			$where[] = "post_type IN ('" . implode( "','", $post_types ) . "')";
		}

		if ( ! empty( $items ) ) {
			$where[] = "id IN(" . implode( ",", $items ) . ")";
		}

		$where = implode( " AND ", $where );

		$results = $wpdb->get_results( "SELECT * FROM `" . GEODIR_TABS_LAYOUT_TABLE . "` WHERE {$where} ORDER BY post_type ASC, sort_order ASC, tab_level ASC, tab_name ASC" );

		return $results;
	}

	public static function get_cpt_tabs_csv_rows( $post_type, $tabs = array() ) {
		$items = self::get_cpt_tabs( $post_type, $tabs );
		if ( empty( $items ) ) {
			return array();
		}

		$rows = array();

		foreach ( $items as $tab ) {
			$tab = stripslashes_deep( $tab );

			$row = array(
				'id' => $tab->id,
				'tab_key' => $tab->tab_key,
				'post_type' => $tab->post_type,
				'tab_name' => $tab->tab_name,
				'tab_type' => $tab->tab_type,
				'sort_order' => $tab->sort_order,
				'tab_parent' => $tab->tab_parent,
				'tab_level' => $tab->tab_level,
				'tab_icon' => $tab->tab_icon,
				'tab_content' => $tab->tab_content
			);

			/**
			 * Filters cpt tab CSV row.
			 *
			 * @since 2.3.3
			 *
			 * @param array $row CPT tab CSV row.
			 * @param object $tab Tab object.
			 */
			$rows[] = apply_filters( 'geodir_cp_export_cpt_tab_csv_row', $row, $tab );
		}

		/**
		 * Filters cpt tabs CSV rows.
		 *
		 * @since 2.3.3
		 *
		 * @param array $rows CPT tabs CSV rows.
		 * @param object $items Fields object.
		 * @param string $post_type Requested post type.
		 */
		return apply_filters( 'geodir_cp_export_cpt_tabs_csv_rows', $rows, $items, $post_type );
	}

	public static function import_export_cpt_tabs( $setting ) {
		?>
		<tr valign="top">
			<td class="forminp" colspan="2">
				<?php /**
				 * Contains template for import/export cpt tabs.
				 *
				 * @since 2.3.3
				 */
				include_once( GEODIR_CP_PLUGIN_DIR . 'includes/admin/views/html-import-export-cpt-tabs.php' );
				?>
			</td>
		</tr>
		<?php
	}

	public static function prepare_export_cpt_tabs() {
		global $wpdb;

		$data = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();
		$post_type = ! empty( $data['post_type'] ) ? geodir_clean( $data['post_type'] ) : '';
		$items = isset( $data['tabs'] ) && ! empty( $post_type ) ? array_map( 'absint', array_filter( $data['tabs'] ) ) : array();

		$where = array();
		if ( $post_type != '' ) {
			$where[] = $wpdb->prepare( "post_type = %s", array( $post_type ) );
		} else {
			$post_types = geodir_get_posttypes();
			$where[] = "post_type IN('" . implode( "','", $post_types ) . "')";
		}

		if ( ! empty( $items ) ) {
			$where[] = "id IN(" . implode( ",", $items ) . ")";
		}

		$where = implode( " AND ", $where );

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `" . GEODIR_TABS_LAYOUT_TABLE . "` WHERE {$where}" );

		return array( 'total' => $count );
	}

	public static function export_cpt_tabs() {
		global $wp_filesystem;

		$csv_file_dir   = GeoDir_Admin_Import_Export::import_export_cache_path( false );
		$file_url_base  = GeoDir_Admin_Import_Export::import_export_cache_path() . '/';
		$nonce          = isset( $_REQUEST['_nonce'] ) ? $_REQUEST['_nonce'] : null;
		$data           = ! empty( $_POST['gd_imex'] ) ? $_POST['gd_imex'] : array();
		$post_type      = ! empty( $data['post_type'] ) ? geodir_clean( $data['post_type'] ) : '';
		$tabs           = isset( $data['tabs'] ) && ! empty( $post_type ) ? array_map( 'absint', array_filter( $data['tabs'] ) ) : array();
		$file_name      = ( $post_type != '' ? $post_type : 'gd' ) . '_cpt_tabs_' . date( 'dmyHi' );
		$file_url       = $file_url_base . $file_name . '.csv';
		$file_path      = $csv_file_dir . '/' . $file_name . '.csv';
		$file_path_temp = $csv_file_dir . '/cpt_tabs_' . $nonce . '.csv';

		$csv_rows = self::get_cpt_tabs_csv_rows( $post_type, $tabs );

		if ( empty( $csv_rows ) ) {
			$json['error'] = __( 'No cpt tab found.', 'geodir_custom_posts' );
		} else {
			$csv_rows = array_merge( array( array_keys( $csv_rows[0] ) ), $csv_rows );

			GeoDir_Admin_Import_Export::save_csv_data( $file_path_temp, $csv_rows, 0 );
			$export_files = array();

			if ( $wp_filesystem->exists( $file_path_temp ) ) {
				$csv_filename = $file_name . '_' . substr( geodir_rand_hash(), 0, 8 ) . '.csv';
				$file_path    = $csv_file_dir . '/' . $csv_filename;
				$wp_filesystem->move( $file_path_temp, $file_path, true );

				$file_url     = $file_url_base . $csv_filename;
				$export_files[] = array(
					'i' => '',
					'u' => $file_url,
					's' => size_format( filesize( $file_path ), 2 )
				);
			}

			if ( ! empty( $export_files ) ) {
				$json['total'] = count( $csv_rows );
				$json['files'] = $export_files;
			} else {
				$json['error'] = __( 'ERROR: Could not create csv file. This is usually due to inconsistent file permissions.', 'geodir_custom_posts' );
			}
		}

		return $json;
	}

	public static function import_cpt_tabs() {
		$limit = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 1;
		if ( $limit < 1 ) {
			$limit = 1;
		}
		$processed = isset( $_POST['processed'] ) ? (int) $_POST['processed'] : 0;

		$processed ++;
		$rows = GeoDir_Admin_Import_Export::get_csv_rows( $processed, $limit );

		if ( ! empty( $rows ) ) {
			// Set doing import constant.
			if ( ! defined( 'GEODIR_DOING_IMPORT_CPT_TAB' ) ) {
				define( 'GEODIR_DOING_IMPORT_CPT_TAB', true );
			}

			$created = 0;
			$updated = 0;
			$skipped = 0;
			$invalid = 0;
			$images  = 0;
			$errors  = array();

			$update_or_skip = isset( $_POST['_ch'] ) && $_POST['_ch'] == 'update' ? 'update' : 'skip';
			$log_error = __( 'GD IMPORT CPT TABS [ROW %d]:', 'geodir_custom_posts' );

			$cpt_tabs = new GeoDir_Settings_Cpt_Tabs();

			$i = 0;
			foreach ( $rows as $row ) {
				$i++;
				$line_no = $processed + $i;
				$line_error = wp_sprintf( $log_error, $line_no );
				if ( isset( $row['id'] ) ) {
					//unset( $row['id'] );
				}
				$row = self::sanitize_cpt_tab( $row, $update_or_skip == 'skip' );

				// Invalid
				if ( is_wp_error( $row ) ) {
					$invalid++;
					$errors[ $line_no ] = sprintf( esc_attr__('Row %d Error: %s', 'geodir_custom_posts'), $line_no, esc_attr( $row->get_error_message() ) );
					geodir_error_log( $line_error . ' ' . $row->get_error_message() );
					continue;
				}

				$exists = ! empty( $row['prev_tab_data'] ) && is_array( $row['prev_tab_data'] ) ? $row['prev_tab_data'] : array();

				// Skip
				if ( $update_or_skip == 'skip' && ! empty( $exists ) ) {
					$skipped++;
					continue;
				}

				do_action( 'geodir_cp_pre_import_cpt_tab_data', $row, $exists );

				// Save tab
				$response = $cpt_tabs::save_tab_item( $row );

				if ( is_wp_error( $response ) ) {
					$invalid++;
					$errors[ $line_no ] = sprintf( esc_attr__('Row %d Error: %s', 'geodir_custom_posts'), $line_no, esc_attr( $response->get_error_message() ) );
					geodir_error_log( $line_error . ' ' . $response->get_error_message() );
					continue;
				}

				if ( ! empty( $exists ) ) {
					$updated++;
				} else {
					$created++;
				}

				do_action( 'geodir_cp_after_import_cpt_tab_data', $row, $exists );
			}

		} else {
			return new WP_Error( 'gd-csv-empty', __( "No data found in csv file.", "geodir_custom_posts" ) );
		}

		return array(
			"processed" => $processed,
			"created"   => $created,
			"updated"   => $updated,
			"skipped"   => $skipped,
			"invalid"   => $invalid,
			"images"    => $images,
			"errors"    => $errors,
			"ID"        => 0,
		);
	}

	public static function sanitize_cpt_tab( $data, $skip = false ) {
		$data = array_map( 'trim', $data );

		if ( isset( $data['id'] ) ) {
			//unset( $data['id'] );
		}

		$args = $data;
		$data_keys = array_keys( $args );

		// Post type
		$post_type = ! empty( $args['post_type'] ) ? sanitize_text_field( $args['post_type'] ) : null;

		if ( ! ( $post_type && geodir_is_gd_post_type( $post_type ) ) ) {
			return new WP_Error( 'gd_invalid_post_type', __( 'Invalid post type.', 'geodir_custom_posts' ) );
		}

		if ( empty( $args['tab_name'] ) ) {
			return new WP_Error( 'gd_invalid_title', __( 'Missing or invalid tab name.', 'geodir_custom_posts' ) );
		}

		if ( ! ( ! empty( $args['tab_type'] ) && $args['tab_type'] == 'meta' ) ) {
			$args['tab_type'] = 'standard';
		}

		if ( empty( $args['tab_key'] ) ) {
			$args['tab_key'] = sanitize_title( $args['tab_name'], 'tab-' . ( ! empty( $args['tab_icon'] ) ? $args['tab_icon'] : substr( md5(), 0, 10 ) ) );
		}

		// tab_key
		$prev_tab_data = array();
		if ( ! empty( $args['id'] ) || ! empty( $args['tab_key'] ) ) {
			if ( ! empty( $args['id'] ) ) {
				$prev_tab_data = self::get_cpt_tab_by_id( (int) $args['id'] );
			}


			if ( empty( $prev_tab_data ) ) {
				$args['tab_key'] = sanitize_text_field( $args['tab_key'] );

				$prev_tab_data = self::get_cpt_tab( $post_type, $args['tab_key'], $args['tab_type'], $args['tab_name'] );
			}

			if ( ! empty( $prev_tab_data ) ) {
				$args['prev_tab_data'] = $prev_tab_data;

				// Skip row on field exists for skip action.
				if ( $skip ) {
					return $args;
				}

				$args['id'] = $prev_tab_data['id'];
				$args['tab_key'] = $prev_tab_data['tab_key'];
			}
		}

		if ( ! empty( $prev_tab_data ) ) {
			$field_keys = array_keys( $prev_tab_data );

			$cf_keys = array( 'id', 'post_type', 'sort_order', 'tab_layout', 'tab_parent', 'tab_type', 'tab_level', 'tab_name', 'tab_icon', 'tab_key', 'tab_content' );

			foreach ( $cf_keys as $key ) {
				if ( ! in_array( $key, $data_keys ) && in_array( $key, $field_keys ) ) {
					$args[ $key ] = $prev_tab_data[ $key ];
				}
			}
		}

		if ( ! empty( $args['tab_parent'] ) || ! empty( $args['tab_level'] ) ) {
			if ( ! empty( $args['tab_parent'] ) ) {
				$exists = self::is_tab_exists( (int) $args['tab_parent'], $post_type );

				if ( ! empty( $exists ) ) {
					$args['tab_level'] = 1;
				} else {
					$args['tab_parent'] = 0;
					$args['tab_level'] = 0;
				}
			} else if ( isset( $args['tab_parent'] ) && empty( $args['tab_parent'] ) && ! empty( $args['tab_level'] ) ) {
				$args['tab_level'] = 0;
			}
		}

		if ( isset( $args['tab_parent'] ) ) {
			$args['tab_parent'] = (int) $args['tab_parent'];
		}

		$args['tab_layout'] = 'post';

		if ( isset( $args['id'] ) && empty( $args['id'] ) ) {
			unset( $args['id'] );
		}

		return apply_filters( 'geodir_cp_import_sanitize_cpt_tab', $args, $data, $prev_tab_data );
	}

	public static function get_cpt_tab_by_id( $id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . GEODIR_TABS_LAYOUT_TABLE . "` WHERE id = %s LIMIT 1", array( (int) $id ) ), ARRAY_A );
	}

	public static function get_cpt_tab( $post_type, $tab_key, $tab_type, $tab_name ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . GEODIR_TABS_LAYOUT_TABLE . "` WHERE post_type = %s AND tab_type = %s AND tab_name LIKE %s AND tab_key = %s LIMIT 1", array( $post_type, $tab_type, $tab_name, $tab_key ) ), ARRAY_A );
	}

	public static function is_tab_exists( $id, $post_type ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM `" . GEODIR_TABS_LAYOUT_TABLE . "` WHERE `id` = %d AND `post_type` = %s LIMIT 1", array( $id, $post_type ) ) );
	}

	public static function get_custom_field_keys( $post_type ) {
		global $geodir_custom_field_keys;

		if ( empty( $geodir_custom_field_keys ) ) {
			$geodir_custom_field_keys = array();
		}

		if ( ! empty( $geodir_custom_field_keys[ $post_type ] ) ) {
			return $geodir_custom_field_keys[ $post_type ];
		}
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
		function geodir_cp_prepare_import(el, type) {
			var $wrap, prepared, file;
			$wrap = jQuery(el).closest('.gd-imex-box');
			prepared = jQuery('#gd_prepared', $wrap).val();
			file = jQuery('#gd_im_' + type, $wrap).val();
			jQuery('gd-import-msg', $wrap).hide();
			jQuery('#gd-import-errors').hide();
			jQuery('#gd-import-errors #gd-csv-errors').html('');

			if(prepared == file) {
				geodir_cp_resume_import(el, type);
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
								geodir_cp_start_import(el, type);
							}
						}
					},
					error: function(errorThrown) {
						console.log(errorThrown);
					}
				});
			}
		}

		function geodir_cp_start_import(el, type) {
			var $wrap, limit, total, total_processed, file, choice;
			$wrap = jQuery(el).closest('.gd-imex-box');

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

				jQuery('#gd-import-msg', $wrap).find('#message').removeClass('updated').addClass('error').html("<p><?php esc_attr_e( 'Please select csv file.', 'geodir_custom_posts' ); ?></p>");
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

				geodir_cp_show_results(el, type);

				jQuery('#gd_im_' + type, $wrap).val('');
				jQuery('#gd_prepared', $wrap).val('');

				return false;
			}
			jQuery('#gd-import-msg', $wrap).hide();

			var gd_processed = parseInt(jQuery('#gd_processed', $wrap).val()),gd_created = parseInt(jQuery('#gd_created', $wrap).val()),gd_updated = parseInt(jQuery('#gd_updated', $wrap).val()),gd_skipped = parseInt(jQuery('#gd_skipped', $wrap).val()),gd_invalid = parseInt(jQuery('#gd_invalid', $wrap).val()),gd_images = parseInt(jQuery('#gd_images', $wrap).val());

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
						geodir_cp_log_errors(data.errors);
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

								geodir_cp_show_results(el, type);
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
										geodir_cp_start_import(el, type);
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


		function geodir_cp_log_errors(errors){
			jQuery.each(errors, function( index, value ) {
				jQuery( "#gd-csv-errors" ).append( "<p class='m-0 p-0 small'>"+value+"</p>" );
				jQuery( "#gd-csv-errors" ).addClass('show error py-2');
				jQuery( "#gd-import-errors" ).show();
			});
		}

		function geodir_cp_terminate_import(el, type) {
			var $wrap = jQuery(el).closest('.gd-imex-box');
			jQuery('#gd_terminateaction', $wrap).val('terminate');
			jQuery('#gd_import_data', $wrap).hide();
			jQuery('#gd_stop_import', $wrap).hide();
			jQuery('#gd_process_data', $wrap).hide();
			jQuery('#gd_continue_data', $wrap).show();
		}

		function geodir_cp_resume_import(el, type) {
			var $wrap = jQuery(el).closest('.gd-imex-box');
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
				geodir_cp_start_import(el, type);
			}, 0);
		}

		function geodir_cp_show_results(el, type) {
			var $wrap = jQuery(el).closest('.gd-imex-box'),total = parseInt(jQuery('#gd_total', $wrap).val()),processed = parseInt(jQuery('#gd_processed', $wrap).val()),created = parseInt(jQuery('#gd_created', $wrap).val()),updated = parseInt(jQuery('#gd_updated', $wrap).val()),skipped = parseInt(jQuery('#gd_skipped', $wrap).val()),invalid = parseInt(jQuery('#gd_invalid', $wrap).val()),images = parseInt(jQuery('#gd_images', $wrap).val());
			var msgProcessed = '<?php echo addslashes( __( 'Total %d item(s) found.', 'geodir_custom_posts' ) );?>',msgCreated = '<?php echo addslashes( __( '%d item(s) added.', 'geodir_custom_posts' ) );?>',msgUpdated = '<?php echo addslashes( __( '%d item(s) updated.', 'geodir_custom_posts' ) );?>',msgSkipped = '<?php echo addslashes( __( '%d item(s) skipped due to already exists.', 'geodir_custom_posts' ) );?>',msgInvalid = '<?php echo addslashes( __( '%d item(s) could not be saved due to invalid data.', 'geodir_custom_posts' ) );?>',msgImages = '<?php echo addslashes( wp_sprintf( __( "Please transfer all new images to <b>'%s'</b> folder.", 'geodir_custom_posts' ), str_replace( ABSPATH, '', $uploads['path'] ) ) );?>';

			<?php do_action( 'geodir_cp_import_js_stats' ); ?>

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
			<?php do_action( 'geodir_cp_import_js_message' ); ?>
			if (images > 0) {
				gdMsg += '<p>' + msgImages + '</p>';
			}
			gdMsg += '<p></p>';
			jQuery('#gd-import-msg', $wrap).find('#message').removeClass('error').addClass('updated').html(gdMsg);
			jQuery('#gd-import-msg', $wrap).show();
			return;
		}
		<?php if ( ! empty( $_GET['section'] ) && $_GET['section'] == 'custom-fields' ) { ?>
		jQuery('#gd_post_type').on('change', function() {
			if (jQuery(this).val()) {
				jQuery('.geodir-export-fields').html('<div class="col py-3"><i class="fas fa-sync fa-spin ml-3 mr-2" aria-hidden="true"></i><?php echo __( 'Loading fields...', 'geodir_custom_posts' ); ?></div>');
				var data = {
					action: 'geodir_post_type_export_fields',
					post_type: jQuery(this).val(),
					security: geodir_params.basic_nonce
				};

				jQuery.ajax({
					url: geodir_params.gd_ajax_url,
					type: 'POST',
					data: data,
					dataType: 'json',
					beforeSend: function(xhr, obj) {
					}
				})
				.done(function(data, textStatus, jqXHR) {
					var _html = '';
					if (typeof data == 'object' && data.data && data.data.html) {
						_html = data.data.html;
					}
					jQuery('.geodir-export-fields').html(_html);
				})
				.always(function(data, textStatus, jqXHR) {
				});
			} else {
				jQuery('.geodir-export-fields').html('');
			}
		});
		<?php } else if ( ! empty( $_GET['section'] ) && $_GET['section'] == 'cpt-tabs' ) { ?>
		jQuery('#gd_post_type').on('change', function() {
			if (jQuery(this).val()) {
				jQuery('.geodir-export-items').html('<div class="col py-3"><i class="fas fa-sync fa-spin ml-3 mr-2" aria-hidden="true"></i><?php echo __( 'Loading items...', 'geodir_custom_posts' ); ?></div>');
				var data = {
					action: 'geodir_post_type_export_tabs',
					post_type: jQuery(this).val(),
					security: geodir_params.basic_nonce
				};
				jQuery.ajax({
					url: geodir_params.gd_ajax_url,
					type: 'POST',
					data: data,
					dataType: 'json',
					beforeSend: function(xhr, obj) {
					}
				})
				.done(function(data, textStatus, jqXHR) {
					var _html = '';
					if (typeof data == 'object' && data.data && data.data.html) {
						_html = data.data.html;
					}
					jQuery('.geodir-export-items').html(_html);
				})
				.always(function(data, textStatus, jqXHR) {
				});
			} else {
				jQuery('.geodir-export-items').html('');
			}
		});
		<?php } ?>
		</script>
		<?php
	}

	/**
	 * Check field exists with parent field.
	 *
	 * @since 2.2.2
	 *
	 * @param int    $field_id The field id.
	 * @param string $post_type The post type.
	 * @return bool True if exists else false.
	 */
	public static function is_field_exists( $field_id, $post_type ) {
		global $wpdb;

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM `" . GEODIR_CUSTOM_FIELDS_TABLE . "` WHERE `id` = %d AND `post_type` = %s LIMIT 1", array( $field_id, $post_type ) ) );

		return $exists;
	}

	/**
	 * Filters the custom field data to save.
	 *
	 * @since 2.2.2
	 *
	 * @param array  $data Field data to save.
	 * @param object $field Field object.
	 * @return array Field data.
	 */
	public static function cpt_cf_save_data( $data, $field ) {
		if ( defined( 'GEODIR_DOING_IMPORT_CUSTOM_FIELD' ) ) {
			if ( ! empty( $data['db_data'] ) && ! empty( $data['db_format'] ) && isset( $field->tab_parent ) && isset( $field->tab_level ) && ! ( isset( $data['db_data']['tab_parent'] ) && isset( $data['db_data']['tab_level'] ) ) ) {
				$data['db_data']['tab_parent'] = (int) $field->tab_parent;
				$data['db_format'][] = '%d';

				$data['db_data']['tab_level'] = (int) $field->tab_level;
				$data['db_format'][] = '%d';
			}
		}

		return $data;
	}
}

new GeoDir_CP_Admin_Import_Export();

}