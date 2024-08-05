<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Custom Post Types class.
 *
 * AJAX Event Handler.
 *
 * @class    GeoDir_CP_Post_Types
 * @package  GeoDir_Custom_Posts/Classes
 * @category Class
 * @author   AyeCode Ltd
 */
class GeoDir_CP_Post_Types {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_filter( 'geodir_save_post_type', array( __CLASS__, 'sanitize_post_type' ), 10, 3 );
		add_action( 'geodir_post_type_saved', array( __CLASS__, 'post_type_saved' ), 10, 3 );
		add_action( 'geodir_post_type_saved', array( __CLASS__, 'flush_rewrite_rules' ), 100, 3 );
		add_action( 'geodir_cp_after_register_taxonomy', array( __CLASS__, 'register_taxonomy_for_object_type' ), 0, 3 );
		add_filter( 'geodir_get_settings_cpt', array( __CLASS__, 'filter_cpt_settings' ), 10, 3 );
		add_filter( 'geodir_post_type_supports', array( __CLASS__, 'post_type_supports' ), 10, 3 );
		add_action( 'geodir_cp_disable_location_changed', array( __CLASS__, 'disable_location_changed' ), 10, 3 );
		
		// Handle save post data
		add_filter( 'geodir_save_post_data', array( __CLASS__, 'save_post_data' ), 100, 4 );

		add_filter( 'geodir_post_permalink_structure_params', array( __CLASS__, 'post_permalink_structure_params' ), 10, 3 );
		add_filter( 'geodir_post_permalink_structure', array( __CLASS__, 'post_permalink_structure' ), 10, 2 );
		add_filter( 'geodir_check_display_map', array( __CLASS__, 'check_display_map' ), 10, 2 );
	}

	public static function filter_cpt_settings( $settings, $current_section = '', $post_type_values = array() ) {
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $key => $setting ) {
				if ( ! empty( $setting['id'] ) && $setting['id'] == 'cpt_settings' && $setting['type'] == 'title' ) {
					//$settings[ $key ]['desc'] =  $setting['desc'] . ' <a href="' . admin_url( 'admin.php?page=gd-settings&tab=cpts&section=cpt' ) . '"><i class="fas fa-chevron-left" aria-hidden="true"></i> ' . __( 'All Post Types', 'geodir_custom_posts' ) . '</a>';
					if(isset($_REQUEST['section']) && $_REQUEST['section']=='add-cpt'){
						$settings[ $key ]['title'] = __('Add Post Type','geodir_custom_posts');
					}
				}
			}

			// Physical location setting
			if ( defined( 'GEODIRLOCATION_VERSION' ) ) {
				$new_settings = array();
				foreach ( $settings as $key => $setting ) {
					if ( ! empty( $setting['id'] ) && $setting['id'] == 'cpt_settings' && $setting['type'] == 'sectionend' ) {
						$new_settings[] =  array(
							'name' => __( 'Disable physical location?', 'geodir_custom_posts' ),
							'desc' => __( 'Tick if post type does not require geographic position/physical location. All fields will be disabled that related to geographic position/physical location. <span style="color:red;">(WARNING: this will remove all location data from the CPT, it can not be recovered after saved.)</span>', 'geodir_custom_posts' ),
							'id'   => 'disable_location',
							'type' => 'checkbox',
							'std'  => '0',
							'advanced' => true,
							'value'	   => ( ! empty( $post_type_values['disable_location'] ) ? '1' : '0' )
						);
						$new_settings[] =  array(
							'name' => '',
							'desc' => '',
							'id'   => 'prev_disable_location',
							'type' => 'hidden',
							'value'	   => ( ! empty( $post_type_values['disable_location'] ) ? 'y' : 'n' )
						);
					}

					$new_settings[] = $setting;
				}
				$settings = $new_settings;
			}
		}

		// Link post settings
		if ( ! empty( $post_type_values['post_type'] ) && $post_type_values['post_type'] != '_gd_new_cpt' ) {
			$field_options = GeoDir_CP_Link_Posts::get_field_options( $post_type_values['post_type'] );
			$description = __( 'Select which fields are allowed to import for post linking. Leave blank to disable fields import for this post type.', 'geodir_custom_posts' );
			$value = ( isset( $post_type_values['fill_fields'] ) ? $post_type_values['fill_fields'] : GeoDir_CP_Link_Posts::default_fields( $post_type_values['post_type'] ) );
			$desc_tip = true;
		} else {
			$field_options = array();
			$description = __( 'Save post type first to select fields!', 'geodir_custom_posts' );
			$desc_tip = false;
			$value = array();
		}

		$new_settings = array();
		foreach ( $settings as $key => $setting ) {
			$new_settings[] = $setting;

			if ( ! empty( $setting['id'] ) && $setting['id'] == 'cpt_settings_page' && $setting['type'] == 'sectionend' ) {
				$new_settings[] = array(
					'title' => __( 'Link Posts Settings', 'geodir_custom_posts' ),
					'type' => 'title',
					'id' => 'cpt_settings_link_posts',
					'desc_tip' => true,
				);
				$new_settings[] = array(
					'type' => 'multiselect',
					'id' => 'fill_fields',
					'name' => __( 'Fill data for fields', 'geodir_custom_posts' ),
					'desc' => $description,
					'placeholder' => __( 'Select fields&hellip;', 'geodir_custom_posts' ),
					'options' => $field_options,
					'class' => 'geodir-select',
					'advanced' => false,
					'desc_tip' => $desc_tip,
					'value' => $value
				);
				$new_settings[] = array( 
					'type' => 'sectionend', 
					'id' => 'cpt_settings_link_posts' 
				);
			}
		}
		$settings = $new_settings;

		return $settings;
	}

	public static function sanitize_post_type( $data, $post_type, $request ) {
		// Physical location setting
		if ( defined( 'GEODIRLOCATION_VERSION' ) ) {
			$data[ $post_type ]['disable_location'] = ! empty( $request['disable_location'] ) ? true : false;
		}

		// Link posts fields
		$data[ $post_type ]['fill_fields'] = ! empty( $request['fill_fields'] ) && is_array( $request['fill_fields'] ) ? $request['fill_fields'] : array();

		return $data;
	}

	public static function post_type_saved( $post_type, $args, $new = false ) {
		self::save_taxonomies( $post_type, $args, $new );

		self::register_post_type( $post_type, $args  );
		self::register_taxonomies( $post_type );

		if ( $new ) {
			self::insert_post_type_default_fields( $post_type );
			self::create_uncategorized_categories( $post_type );
			GeoDir_Admin_Install::insert_default_tabs( $post_type );
		}

		$current = ! empty( $args['disable_location'] ) ? true : false;
		$previous = ! empty( $_POST['prev_disable_location'] ) && $_POST['prev_disable_location'] == 'y' ? true : false;
		if ( $new ) {
			$previous = false;
		}
		if ( $current != $previous ) {
			do_action( 'geodir_cp_disable_location_changed', $post_type, $current, $previous );
		}

		// Delete cache
		GeoDir_CP_Link_Posts::delete_cache();
	}

	public static function disable_location_changed( $post_type, $current, $previous ) {
		global $wpdb;

		if ( $current && ! $previous ) { // disable address fields
			$wpdb->query( $wpdb->prepare( "UPDATE " . GEODIR_CUSTOM_FIELDS_TABLE. " SET is_active = %d WHERE post_type = %s AND field_type = 'address'", array( 0, $post_type ) ) );
			//$wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_TABS_LAYOUT_TABLE. " WHERE post_type = %s AND tab_key = 'post_map'", array( $post_type ) ) ); @todo check if necessary
		} else if ( ! $current && $previous ) { // enable address fields
			$wpdb->query( $wpdb->prepare( "UPDATE " . GEODIR_CUSTOM_FIELDS_TABLE. " SET is_active = %d WHERE post_type = %s AND field_type = 'address'", array( 1, $post_type ) ) );

			// Run the dbDelta functions incase the hoods column needs to be added.
			GeoDir_Admin_Install::create_tables();
		}
	}

	public static function flush_rewrite_rules( $post_type, $args, $new = false ) {
		// Flush rules after install
		do_action( 'geodir_flush_rewrite_rules' );
	}

	public static function save_taxonomies( $post_type, $args, $new = false ) {
		$taxonomies = geodir_get_option( 'taxonomies', array() );

		$singular_name = isset( $args['labels']['singular_name']) ? $args['labels']['singular_name'] : 'Listing';
		$listing_slug = ! empty( $args['rewrite']['slug'] ) ? $args['rewrite']['slug'] : 'listings';

		// Categories
		$taxonomy = ! empty( $taxonomies ) && ! empty( $taxonomies[ $post_type . 'category' ] ) ? $taxonomies[ $post_type . 'category' ] : array();
		$category_permalink = geodir_get_option( 'permalink_category_base', 'category' );

		$taxonomy['object_type'] = $post_type;
		$taxonomy['listing_slug'] = $listing_slug;
		$taxonomy['args'] = array(
			'public' => true,
			'hierarchical' => true,
			'rewrite' => array(
				'slug' => $category_permalink ? $listing_slug . '/' . $category_permalink : $listing_slug,
				'with_front' => false,
				'hierarchical' => true
			),
			'query_var' => true,
			'labels' => array(
				'name' => wp_sprintf( __( '%s Categories', 'geodirectory' ), $singular_name ),
				'singular_name' => wp_sprintf( __( '%s Category', 'geodirectory' ), $singular_name ),
				'search_items' => wp_sprintf( __( 'Search %s Categories', 'geodirectory' ), $singular_name ),
				'popular_items' => wp_sprintf( __( 'Popular %s Categories', 'geodirectory' ), $singular_name ),
				'all_items' => wp_sprintf( __( 'All %s Categories', 'geodirectory' ), $singular_name ),
				'edit_item' => wp_sprintf( __( 'Edit %s Category', 'geodirectory' ), $singular_name ),
				'update_item' => wp_sprintf( __( 'Update %s Category', 'geodirectory' ), $singular_name ),
				'add_new_item' => wp_sprintf( __( 'Add New %s Category', 'geodirectory' ), $singular_name ),
				'new_item_name' => wp_sprintf( __( 'New %s Category', 'geodirectory' ), $singular_name ),
				'add_or_remove_items' => wp_sprintf( __( 'Add or remove %s categories', 'geodirectory' ), $singular_name ),
			)
		);

		// add capability to assign terms to any user, if not added then subscribers listings wont have terms
		$taxonomy['args']['capabilities']['assign_terms'] = 'read';

		$taxonomies[ $post_type . 'category' ] = $taxonomy;

		// Tags
		$taxonomy = ! empty( $taxonomies ) && ! empty( $taxonomies[ $post_type . '_tags' ] ) ? $taxonomies[ $post_type . '_tags' ] : array();
		$tags_permalink = geodir_get_option( 'permalink_tag_base', 'tags' );

		$taxonomy['object_type'] = $post_type;
		$taxonomy['listing_slug'] = $listing_slug;
		$taxonomy['args'] = array(
			'public' => true,
			'hierarchical' => false,
			'rewrite' => array(
				'slug' => $tags_permalink ? $listing_slug . '/' . $tags_permalink : $listing_slug,
				'with_front' => false,
				'hierarchical' => true
			),
			'query_var' => true,
			'labels' => array(
				'name' => wp_sprintf( __( '%s Tags', 'geodirectory' ), $singular_name ),
				'singular_name' => wp_sprintf( __( '%s Tag', 'geodirectory' ), $singular_name ),
				'search_items' => wp_sprintf( __( 'Search %s Tags', 'geodirectory' ), $singular_name ),
				'popular_items' => wp_sprintf( __( 'Popular %s Tags', 'geodirectory' ), $singular_name ),
				'all_items' => wp_sprintf( __( 'All %s Tags', 'geodirectory' ), $singular_name ),
				'edit_item' => wp_sprintf( __( 'Edit %s Tag', 'geodirectory' ), $singular_name ),
				'update_item' => wp_sprintf( __( 'Update %s Tag', 'geodirectory' ), $singular_name ),
				'add_new_item' => wp_sprintf( __( 'Add New %s Tag', 'geodirectory' ), $singular_name ),
				'new_item_name' => wp_sprintf( __( 'New %s Tag Name', 'geodirectory' ), $singular_name ),
				'add_or_remove_items' => wp_sprintf( __( 'Add or remove %s tags', 'geodirectory' ), $singular_name ),
				'choose_from_most_used' => wp_sprintf( __( 'Choose from the most used %s tags', 'geodirectory' ), $singular_name ),
				'separate_items_with_commas' => wp_sprintf( __( 'Separate %s tags with commas', 'geodirectory' ), $singular_name ),
			)
		);

		// add capability to assign terms to any user, if not added then subscribers listings wont have terms
		$taxonomy['args']['capabilities']['assign_terms'] = 'read';

		$taxonomies[ $post_type . '_tags' ] = $taxonomy;

		geodir_update_option( 'taxonomies', $taxonomies );
	}

	public static function register_post_type( $post_type, $args  ) {
		if ( ! is_blog_installed() || post_type_exists( $post_type ) ) {
			return;
		}

		do_action( 'geodir_cp_register_post_type', $post_type, $args );

		$args = stripslashes_deep( $args );

		if ( ! empty( $args['labels'] ) ) {
			foreach ( $args['labels'] as $key => $val ) {
				$args['labels'][ $key ] = __( $val, 'geodirectory' );
			}
		}

		if ( ! empty( $args['menu_icon'] ) ) {
			$args['menu_icon'] = GeoDir_Post_types::sanitize_menu_icon( $args['menu_icon'] );
		} else {
			$args['menu_icon'] = 'dashicons-admin-post';
		}

		/**
		 * Filter post type args.
		 *
		 * @since 2.0.0
		 * @param array $args Post type args.
		 * @param string $post_type The post type.
		 */
		$args = apply_filters( 'geodir_cp_register_post_type_args', $args, $post_type );

		register_post_type( $post_type, $args );

		do_action( 'geodir_cp_after_register_post_type', $post_type, $args );
	}

	public static function register_taxonomies( $post_type  ) {
		$taxonomies = geodir_get_option( 'taxonomies', array() );

		// Categories
		$taxonomy = ! empty( $taxonomies ) && ! empty( $taxonomies[ $post_type . 'category' ] ) ? $taxonomies[ $post_type . 'category' ] : array();
		self::register_taxonomy( $post_type . 'category', $post_type, $taxonomy );

		// Tags
		$taxonomy = ! empty( $taxonomies ) && ! empty( $taxonomies[ $post_type . '_tags' ] ) ? $taxonomies[ $post_type . '_tags' ] : array();
		self::register_taxonomy( $post_type . '_tags', $post_type, $taxonomy );
	}

	public static function register_taxonomy( $taxonomy, $post_type, $args  ) {
		if ( ! is_blog_installed() || taxonomy_exists( $taxonomy ) ) {
			return;
		}

		do_action( 'geodir_cp_register_taxonomy', $taxonomy, $args );

		$args = stripslashes_deep( $args );

		if ( ! empty( $args['labels'] ) ) {
			foreach ( $args['labels'] as $key => $val ) {
				$args['labels'][ $key ] = __( $val, 'geodirectory' );
			}
		}

		/**
		 * Filter taxonomy args.
		 *
		 * @since 2.0.0
		 * @param array $args Taxonomy args.
		 * @param string $taxonomy The taxonomy.
		 * @param string $post_type The post type.
		 */
		$args = apply_filters( 'geodir_cp_register_taxonomy_args', $args, $taxonomy, $post_type );

		register_taxonomy( $taxonomy, $post_type, $args );

		do_action( 'geodir_cp_after_register_taxonomy', $taxonomy, $post_type, $args );
	}

	public static function register_taxonomy_for_object_type( $taxonomy, $post_type, $args  ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			return;
		}

		register_taxonomy_for_object_type( $taxonomy, $post_type );
	}




	/*
	 * Insert the default field for the CPTs
	 */
	public static function insert_post_type_default_fields( $post_type ){
		$fields = GeoDir_Admin_Dummy_Data::default_custom_fields( $post_type );

		/**
		 * Filter the array of default custom fields DB table data.
		 *
		 * @since 2.0.0
		 * @param string $fields The default custom fields as an array.
		 */
		$fields = apply_filters( 'geodir_before_default_custom_fields_saved', $fields );

		foreach ( $fields as $field_index => $field ) {
			geodir_custom_field_save( $field );
		}
	}

	/**
	 * Create a category for each CPT.
	 *
	 * So users can start adding posts right away.
	 */
	public static function create_uncategorized_categories( $post_type ) {
		if ( ! get_option( $post_type . 'category_installed', false ) ) {
			$dummy_categories = array(
				'uncategorized' => array(
					'name'        => 'Uncategorized',
					'icon'        => GEODIRECTORY_PLUGIN_URL . '/assets/images/pin.png',
					'schema_type' => ''
				)
			);

			GeoDir_Admin_Dummy_Data::create_taxonomies( $post_type, $dummy_categories );

			update_option( $post_type . 'category_installed', true );
		}
	}

	public static function reorder_post_types( $a, $b ) {
		$orderby = ( !empty( $_REQUEST['orderby'] ) ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'name';
		$order = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( $_REQUEST['order'] ) : 'asc';

		if ( ! in_array( $order, array( 'asc', 'desc' ) ) ) {
			$order = 'asc';
		}
		if ( 'name' == $orderby || ! isset( $a[ $orderby ] ) ) {
			$orderby = 'order';
		}

		if ( $a[ $orderby ] == $b[ $orderby ] ) {
			$orderby = 'order';
		}

		if ( $orderby == 'order' ) {
			$result = (int) $a[ $orderby ] - (int) $b[ $orderby ];
		} else {
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		}

		return ( $order === 'asc' ) ? $result : - $result;
	}

	/**
	 * Check a post type's support for a given feature.
	 *
	 * @param bool $value       True if supports else False.
	 * @param string $post_type The post type being checked.
	 * @param string $feature   The feature being checked.
	 * @return bool Whether the post type supports the given feature.
	 */
	public static function post_type_supports( $value, $post_type, $feature ) {
		// Check a post type supports location
		if ( $feature == 'location' && defined( 'GEODIRLOCATION_VERSION' ) ) {
			$post_type_object = geodir_post_type_object( $post_type );
			if ( ! empty( $post_type_object ) && ! empty( $post_type_object->disable_location ) ) {
				$value = false;
			} else {
				$value = true;
			}
		}

		return $value;
	}

	public static function save_post_data( $postarr, $gd_post, $post, $update ) {
		if ( ! empty( $gd_post['post_type'] ) && ! GeoDir_Post_types::supports( $gd_post['post_type'], 'location' ) ) {
			$address_fields = array( 'street', 'city', 'region', 'country', 'neighbourhood', 'zip', 'latitude', 'longitude' );

			foreach ( $address_fields as $field ) {
				if ( isset( $postarr[ $field ] ) ) {
					unset( $postarr[ $field ] );
				}
			}
		}

		return $postarr;
	}

	public static function post_permalink_structure_params( $params, $post_type, $post_type_object ) {
		if ( ! empty( $params ) && ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			$params = array_diff( $params, array( '%country%', '%region%', '%city%', '%neighbourhood%' ) );
		}
		return $params;
	}

	public static function post_permalink_structure( $permalink_structure, $post_type ) {
		if ( ! empty( $permalink_structure ) && ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			$params = explode( '/', $permalink_structure );

			if ( ! empty( $params ) ) {
				$params = array_diff( $params, array( '%country%', '%region%', '%city%', '%neighbourhood%' ) );
				$permalink_structure = implode( '/', $params );

				if ( $permalink_structure == '/' ) {
					$permalink_structure = '';
				}
			}
		}
		return $permalink_structure;
	}

	public static function check_display_map( $display, $params ) {
		if ( ! empty( $params['post_type'] ) && ! GeoDir_Post_types::supports( $params['post_type'], 'location' ) ) {
			$display = false;
		}
		return $display;
	}

	public static function delete_cpt( $post_type ) {
		global $wpdb;

		if ( ! $post_type ) {
			return false;
		}

		$args = array( 'post_type' => $post_type, 'posts_per_page' => -1, 'post_status' => 'any', 'post_parent' => null );

		/* ------- START DELETE ALL TERMS ------- */

		$terms = $wpdb->get_results("SELECT term_id, taxonomy FROM ".$wpdb->prefix."term_taxonomy WHERE taxonomy IN ('".$post_type."category', '".$post_type."_tags')");

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, $term->taxonomy );
			}
		}

		$wpdb->query("DELETE FROM ".$wpdb->prefix."options WHERE option_name LIKE '%tax_meta_".$post_type."_%'");

		/* ------- END DELETE ALL TERMS ------- */

		$geodir_all_posts = get_posts( $args );

		if ( ! empty( $geodir_all_posts ) ) {
			foreach ( $geodir_all_posts as $posts ) {
				wp_delete_post( $posts->ID );
			}
		}

		do_action('geodir_after_post_type_deleted'  , $post_type);

		// Custom fields
		$wpdb->query( $wpdb->prepare( "DELETE FROM `" . GEODIR_CUSTOM_FIELDS_TABLE . "` WHERE `post_type` = %s OR ( `field_type_key` = %s AND `field_type` = 'link_posts' )", array( $post_type, $post_type ) ) );

		$wpdb->query( $wpdb->prepare( "DELETE FROM `" . GEODIR_CUSTOM_SORT_FIELDS_TABLE . "` WHERE post_type = %s", array( $post_type ) ) );

		// Single tabs
		$wpdb->query($wpdb->prepare( "DELETE FROM `" . GEODIR_TABS_LAYOUT_TABLE . "` WHERE post_type = %s", array( $post_type ) ) );

		// Linked posts
		$wpdb->query($wpdb->prepare( "DELETE FROM `" . GEODIR_CP_LINK_POSTS . "` WHERE post_type = %s OR linked_post_type = %s", array( $post_type, $post_type ) ) );

		if ( defined( 'GEODIR_ADV_SEARCH_VERSION' ) ) {
			// Advance search fields
			$wpdb->query($wpdb->prepare( "DELETE FROM `" . GEODIR_ADVANCE_SEARCH_TABLE . "` WHERE post_type = %s", array( $post_type ) ) );
		}

		if ( defined( 'GEODIR_PRICING_VERSION' ) ) {
			// Packages
			$wpdb->query($wpdb->prepare( "DELETE FROM `" . GEODIR_PRICING_PACKAGES_TABLE . "` WHERE post_type = %s", array( $post_type ) ) );

			// Delete orphan price metas
			$wpdb->query( "DELETE pricemeta FROM `" . GEODIR_PRICING_PACKAGE_META_TABLE . "` pricemeta LEFT JOIN `" . GEODIR_PRICING_PACKAGES_TABLE . "` price ON price.id = pricemeta.package_id WHERE price.id IS NULL" );

			// Delete orphan price post packages
			$wpdb->query( "DELETE post_packages FROM `" . GEODIR_PRICING_POST_PACKAGES_TABLE . "` post_packages LEFT JOIN `" . GEODIR_PRICING_PACKAGES_TABLE . "` price ON price.id = post_packages.package_id WHERE price.id IS NULL" );
		}

		// Delete transients
		delete_transient( 'geodir_post_custom_fields' );

		// Delete options
		delete_option( $post_type . 'category_children' );
		delete_option( $post_type . 'category_installed' );

		$detail_table =  geodir_db_cpt_table( $post_type );

		$wpdb->query( "DROP TABLE IF EXISTS " . $detail_table );

		// Remove CPT Settings AFTER posts (in case posts delete timeout)
		$geodir_taxonomies = geodir_get_option('taxonomies');

		if ( array_key_exists( $post_type . 'category', $geodir_taxonomies ) ) {
			unset( $geodir_taxonomies[ $post_type . 'category' ] );
			geodir_update_option( 'taxonomies', $geodir_taxonomies );
		}

		if ( array_key_exists( $post_type . '_tags', $geodir_taxonomies ) ) {
			unset( $geodir_taxonomies[ $post_type . '_tags' ] );
			geodir_update_option( 'taxonomies', $geodir_taxonomies );
		}

		$geodir_post_types = geodir_get_option( 'post_types' );

		if ( array_key_exists( $post_type, $geodir_post_types ) ) {
			unset( $geodir_post_types[ $post_type ] );
			geodir_update_option( 'post_types', $geodir_post_types );
		}

		// Delete cache
		GeoDir_CP_Link_Posts::delete_cache();

		do_action( 'geodir_flush_rewrite_rules' ) ;

		return true;
	}
}