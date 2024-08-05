<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Location SEO.
 *
 * @class    GeoDir_Location_SEO
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_SEO {

	/**
	 * Save location seo meta description during location import.
	 *
	 * @since 1.4.4
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $location_type Location type (city or region or country).
	 * @param array $seo_data Location seo data.
	 * @return bool True if any record updated, otherwise false.
	 */
	public static function save_seo_data( $location_type, $seo_data ) {
		global $wpdb;

		$country_slug = isset( $seo_data['country_slug'] ) ? $seo_data['country_slug'] : '';
		$region_slug = isset( $seo_data['region_slug'] ) ? $seo_data['region_slug'] : '';
		$city_slug = isset( $seo_data['city_slug'] ) ? $seo_data['city_slug'] : '';
		
		$slug = '';
		if ( $location_type == 'city' ) {
			$slug = isset( $seo_data['city_slug'] ) ? $seo_data['city_slug'] : '';

		} else if ( $location_type == 'region' ) {
			$slug = isset( $seo_data['region_slug'] ) ? $seo_data['region_slug'] : '';
			$seo_data['city_slug'] = '';
		} else if ( $location_type == 'country' ) {
			$slug = isset( $seo_data['country_slug'] ) ? $seo_data['country_slug'] : '';
			$seo_data['region_slug'] = '';
			$seo_data['city_slug'] = '';
		} else {
			return false;
		}
		
		if ( $slug == '' ) {
			return false;
		}
		
		$seo = GeoDir_Location_SEO::get_seo_by_slug( $slug, $location_type, $country_slug, $region_slug );

		$seo_data['location_type'] = $location_type;

		if ( ! empty( $seo_data['meta_title'] ) && geodir_utf8_strlen( $seo_data['meta_title'] ) > 140 ) {
			$seo_data['meta_title'] = geodir_utf8_substr( $seo_data['meta_title'], 0, 140 );
		}
		if ( ! empty( $seo_data['meta_desc'] ) && geodir_utf8_strlen( $seo_data['meta_desc'] ) > 140 ) {
			$seo_data['meta_desc'] = geodir_utf8_substr( $seo_data['meta_desc'], 0, 140 );
		}
		if ( ! empty( $seo_data['location_desc'] ) && geodir_utf8_strlen( $seo_data['location_desc'] ) > 102400 ) {
			$seo_data['location_desc'] = geodir_utf8_substr( $seo_data['location_desc'], 0, 102400 );
		}
		if ( ! empty( $seo_data['image_tagline'] ) && geodir_utf8_strlen( $seo_data['image_tagline'] ) > 140 ) {
			$seo_data['image_tagline'] = geodir_utf8_substr( $seo_data['image_tagline'], 0, 140 );
		}

		$seo_id = 0;
		if ( !empty( $seo ) ) {
			$saved = $wpdb->update( GEODIR_LOCATION_SEO_TABLE, $seo_data, array( 'seo_id' => (int)$seo->seo_id ) );
			$seo_id = $seo->seo_id;
		} else {
			$saved = $wpdb->insert( GEODIR_LOCATION_SEO_TABLE, $seo_data );
			if ( $saved ) {
				$seo_id = (int)$wpdb->insert_id;
			}
		}

		if ( $seo_id ) {
			do_action( 'geodir_location_seo_saved', $seo_id, $seo_data );
		}

		return $seo_id;
	}
	
	/**
	 * Get location SEO information using location slug.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $slug Location slug.
	 * @param string $type Location type. Possible values city, region, country & neighbourhood.
	 * @param string $country_slug Country slug.
	 * @param string $region_slug Region slug.
	 * @return mixed|null
	 */
	public static function get_seo_by_slug( $slug, $type = 'city', $country_slug = '', $region_slug = '' ) {
		global $wpdb;

		if ( empty( $slug ) ) {
			return NULL;
		}

		// Neighbourhood
		if ( $type == 'neighbourhood' ) {
			return GeoDir_Location_Neighbourhood::get_info_by_slug( $slug );
		}

		$where = array( "location_type = %s" );
		$args = array( $type );

		switch( $type ) {
			case 'country': {
					$where[] = "country_slug = %s";
					$args[] = $slug;
				}
				break;
			case 'region': {
					if ( $country_slug != '' ) {
						$where[] = "country_slug = %s";
						$args[] = $country_slug;
					}

					$where[] = "region_slug = %s";
					$args[] = $slug;
				}
				break;
			case 'city': {
					if ( $country_slug != '' ) {
						$where[] = "country_slug = %s";
						$args[] = $country_slug;
					}

					if ( $region_slug != '' ) {
						$where[] = "region_slug = %s";
						$args[] = $region_slug;
					}

					$where[] = "city_slug = %s";
					$args[] = $slug;
				}
				break;
			default:
				return NULL;
				break;
		}

		$where = implode( " AND ", $where );

		$seo = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_LOCATION_SEO_TABLE . " WHERE {$where} ORDER BY seo_id LIMIT 1", $args ) );

		return $seo;
	}
	
	/**
	 * Get location SEO information from current or from location array.
	 *
	 * @since 1.4.5
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param array|null $args The location array of slugs or null.
	 * @return object|null The object of the seo results or null.
	 */
	public static function get_location_seo( $args = array() ) {
		global $wpdb, $geodirectory;

		$location_terms = empty( $args ) ? $geodirectory->location : $args;
		
		if ( empty( $location_terms ) ) {
			return NULL;
		}

		$type = '';
		$country_slug = '';
		$region_slug = '';
		$value = '';

		if ( ! empty ( $location_terms->country_slug ) ) {
			$type = 'country';
			$country_slug = $location_terms->country_slug;
			$value = $country_slug;
		}
		if ( ! empty ( $location_terms->region_slug ) ) {
			$type = 'region';
			$region_slug = $location_terms->region_slug;
			$value = $region_slug;
		}
		if ( ! empty ( $location_terms->city_slug ) ) {
			$type = 'city';
			$value = $location_terms->city_slug;
		}
		if ( ! empty ( $location_terms->neighbourhood_slug ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$type = 'neighbourhood';
			$value = $location_terms->neighbourhood_slug;
		}

		if ( empty( $type ) || empty( $value ) ) {
			return NULL;
		}

		return self::get_seo_by_slug( $value, $type, $country_slug, $region_slug );
	}

	/**
	 * Filter the meta title..
	 *
	 * Filter the location meta title if there is one provided, if not fall back to standard GD meta.
	 *
	 * @param string $title The meta title to filter.
	 * @param string $gd_page The current GD page.
	 * @since 1.5.2
	 * @return string The filtered title.
	 */
	public static function filter_meta_title( $title, $gd_page = '' ) {
		if ( $gd_page == 'location' ) {
			global $wp,$geodirectory;

			$location = $geodirectory->location;

			$gd_country = isset( $location->country_slug ) ? $location->country_slug : '';
			$gd_region = isset( $location->region_slug ) ? $location->region_slug : '';
			$gd_city = isset( $location->city_slug ) ? $location->city_slug : '';
			$gd_neighbourhood = isset( $location->neighbourhood_slug ) ? $location->neighbourhood_slug : '';

			$type = !empty($location->type) ? $location->type : '';
			$value = $type && in_array($type,$geodirectory->location->allowed_query_variables()) ? $location->{$location->type."_slug"} : '';


			if ( ! empty( $type ) && ! empty( $value ) ) {
				$seo = GeoDir_Location_SEO::get_seo_by_slug( $value, $type, $gd_country, $gd_region );
				if ( ! empty( $seo ) && ! empty( $seo->meta_title ) ) {
					$title = __( $seo->meta_title, 'geodirectory' );
				}
			}

			if ( ! empty( $gd_neighbourhood ) && GeoDir_Location_Neighbourhood::is_active() ) {
				$location = GeoDir_Location_City::get_info_by_slug( $gd_city, $gd_country, $gd_region );
				$location_id = !empty( $location->location_id ) ? $location->location_id : 0;
				$hood = GeoDir_Location_Neighbourhood::get_info_by_slug( $gd_neighbourhood, $location_id );
				
				if ( ! empty( $hood ) && ( ! empty( $hood->neighbourhood ) && ! empty( $hood->meta_title ) ) ) {
					$meta_title = ! empty( $hood->meta_title ) ? __( $hood->meta_title, 'geodirectory' ) : $hood->neighbourhood;
					$title = stripslashes( strip_tags( $meta_title ) );
				}
			}
		}

		return $title;
	}

	/**
	 * Add location information to the meta description.
	 *
	 * @since 1.0.0
	 * @since 1.4.1 Return original meta if blank or default settings.
	 * @since 1.4.9 Updated to show neighbourhood meta description.
	 * @package GeoDirectory_Location_Manager
	 *
	 * @global object $wp WordPress object.
	 *
	 * @param string $seo_desc Meta description text.
	 * @return null|string Altered meta desc.
	 */
	public static function filter_meta_desc( $meta_desc ) {
		global $wp, $geodirectory;

		if ( ! geodir_is_page( 'location' ) ) {
			return $meta_desc;
		}

		$location = $geodirectory->location;

		$gd_country = isset( $location->country_slug ) ? $location->country_slug : '';
		$gd_region = isset( $location->region_slug ) ? $location->region_slug : '';
		$gd_city = isset( $location->city_slug ) ? $location->city_slug : '';
		$gd_neighbourhood = isset( $location->neighbourhood_slug ) ? $location->neighbourhood_slug : '';

		$type = !empty($location->type) ? $location->type : '';
		$value = $type && in_array($type,$geodirectory->location->allowed_query_variables()) ? $location->{$location->type."_slug"} : '';


		if ( ! empty( $type ) && ! empty( $value ) ) {
			$seo = GeoDir_Location_SEO::get_seo_by_slug( $value, $type, $gd_country, $gd_region );
			if ( ! empty( $seo ) && ! empty( $seo->meta_desc ) ) {
				$meta_desc = __( $seo->meta_desc, 'geodirectory' );
			}
		}

		if ( ! empty( $gd_neighbourhood ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$location = GeoDir_Location_City::get_info_by_slug( $gd_city, $gd_country, $gd_region );
			$location_id = !empty( $location->location_id ) ? $location->location_id : 0;
			$neighbourhood = GeoDir_Location_Neighbourhood::get_info_by_slug( $gd_neighbourhood, $location_id );

			if ( ! empty( $neighbourhood ) && ! empty( $neighbourhood->meta_description ) ) {
				$meta_desc = strip_tags( stripslashes( __( $neighbourhood->meta_description, 'geodirectory' ) ) );
			}
		}

		$meta_desc = sanitize_text_field( $meta_desc );

		return $meta_desc;
	}

	public static function get_image( $type, $slug, $country_slug = '', $region_slug = '', $size = 'full', $icon = false, $attr = '' ) {
		global $wpdb;

		if ( empty( $slug ) ) {
			return NULL;
		}

		$seo = self::get_seo_by_slug( $slug, $type, $country_slug, $region_slug );
		if ( empty( $seo->image ) ) {
			return NULL;
		}

		return wp_get_attachment_image( (int) $seo->image, $size, $icon, $attr );
	}

	public static function get_image_src( $type, $slug, $country_slug = '', $region_slug = '', $size = 'full', $icon = false ) {
		global $wpdb;

		if ( empty( $slug ) ) {
			return NULL;
		}

		$seo = self::get_seo_by_slug( $slug, $type, $country_slug, $region_slug );
		if ( empty( $seo->image ) ) {
			return NULL;
		}

		return wp_get_attachment_image( (int) $seo->image, $size, $icon );
	}

	/**
	 * Removes the canonical url on location page that added by Yoast WordPress SEO.
	 *
	 * @since 1.4.0
	 * @package GeoDirectory_Location_Manager
	 *
	 * @param string $canonical The canonical URL
	 * @return bool Empty value.
	 */
	public static function wpseo_remove_canonical( $canonical ) {
		$canonical = false;

		return $canonical;
	}

	/**
	 * Filter the meta title if wpseo plugin installed.
	 *
	 * Filter the location meta title if there is one provided, if not fall back to page.
	 *
	 * @param string $title The meta title to filter.
	 * @since 1.5.2
	 * @since 1.5.3 FIX: Neighbourhood page showing wrong meta title.
	 * @return string The filtered title.
	 */
	public static function wpseo_title( $title ) {
		if ( is_page() && geodir_is_page( 'location' ) ) {
			$title = self::filter_meta_title( $title, 'location' );
			$title = wpseo_replace_vars( $title, array() );
		}

		return $title;
	}

	/**
	 * Filter the meta description if wpseo plugin installed.
	 *
	 * Filter the location meta description if there is one provided, if not fall back to page.
	 *
	 * @param string $meta_desc The meta description to filter.
	 * @since 1.5.2
	 * @since 1.5.3 FIX: Neighbourhood page showing wrong meta description.
	 * @return mixed
	 */
	public static function wpseo_metadesc( $meta_desc ) {
		global $wp;

		if ( is_page() && geodir_is_page( 'location' ) ) {
			$meta_desc = self::filter_meta_desc( $meta_desc );
			$meta_desc = wpseo_replace_vars( $meta_desc, array() );
		}

		return $meta_desc;
	}

	public static function wpseo_is_sitemap_page( $install_check = false, $check_location_sitemap = false, $check_index = false ) {
		if(!function_exists('wpseo_init')) {
			return false;
		}
		
		if ($install_check) {
			return true;
		}
		
		if (!isset($_SERVER['REQUEST_URI'])) {
			return false;
		}
		
		$request_uri = $_SERVER['REQUEST_URI'];
		$extension   = substr($request_uri, -4);

		$return = false;
		if (false !== stripos($request_uri, 'sitemap') && (in_array($extension, array('.xml', '.xsl')))) {
			$return = true;
		} else if (!empty($_GET['sitemap'])) {
			$return = true;
		}
		
		$index = false;
		if (in_array(basename($request_uri), array('sitemap_index.xml', 'sitemap_index.xsl'))) {
			$index = true;
		} else if (!empty($_GET['sitemap']) && (int)$_GET['sitemap'] == 1) {
			$index = true;
		}

		if ($index) {
			return $return;
		}

		if ($return && $check_location_sitemap && !$index) {
			if (false !== stripos($request_uri, '_location_')) {
				$return = true;
			} else {
				$return = false;
			}
		}
		
		return $return;
	}

	public static function wpseo_sitemap_init() {
		global $wpseo_sitemaps, $gd_sitemap_global;

		if ( ! self::wpseo_is_sitemap_page( false, true ) || empty( $wpseo_sitemaps ) ) {
			return;
		}

		if ( !defined('WP_DEBUG_DISPLAY') ) {
			define('WP_DEBUG_DISPLAY', false);
		}
		if ( !defined('SAVEQUERIES') ) {
			define('SAVEQUERIES', false);
		}

		$gd_sitemap_global['exclude_location'] = geodir_get_option( 'lm_sitemap_exclude_location' );
		$gd_sitemap_global['exclude_post_types'] = geodir_get_option( 'lm_sitemap_exclude_post_types' );
		$gd_sitemap_global['exclude_cats'] = geodir_get_option( 'lm_sitemap_exclude_cats' );
		$gd_sitemap_global['exclude_tags'] = geodir_get_option( 'lm_sitemap_exclude_tags' );
		$gd_sitemap_global['exclude_taxonomies'] = $gd_sitemap_global['exclude_cats'] && $gd_sitemap_global['exclude_tags'];
		$gd_sitemap_global['neighbourhoods'] = geodir_get_option( 'lm_sitemap_enable_hoods' ) && GeoDir_Location_Neighbourhood::is_active();
		
		if (!empty($gd_sitemap_global['exclude_location']) && !empty($gd_sitemap_global['exclude_taxonomies']) && !empty($gd_sitemap_global['exclude_post_types'])) {
			return;
		}
		
		// try to set higher limits for import
		$max_input_time = ini_get('max_input_time');
		$max_execution_time = ini_get('max_execution_time');
		$memory_limit= ini_get('memory_limit');

		if(!$max_input_time || $max_input_time<3000){
			try {
				ini_set('max_input_time', 3000);
			} catch(Exception $e) {
				// Error
			}
		}

		if(!$max_execution_time || $max_execution_time<3000){
			try {
				ini_set('max_execution_time', 3000);
			} catch(Exception $e) {
				// Error
			}
		}

		if($memory_limit && str_replace('M','',$memory_limit)){
			if(str_replace('M','',$memory_limit)<512){
				try {
					ini_set('memory_limit', '512M');
				} catch(Exception $e) {
					// Error
				}
			}
		}
		
		global $wpseo_sitemaps, $gd_wpseo_options, $gd_wpseo_date_helper, $gd_wpseo_max_entries, $gd_post_types,$geodirectory;
		
		$gd_post_types = geodir_get_posttypes();
		$gd_sitemap_post_types = array();

		$gd_wpseo_options = WPSEO_Options::get_all();
		$gd_wpseo_date_helper = new WPSEO_Date_Helper();
		$gd_wpseo_max_entries = self::wpseo_sitemap_entries_per_page();
		
		if ( !empty( $gd_post_types ) ) {
			foreach ( $gd_post_types as $gd_post_type ) {
				if ( empty( $gd_wpseo_options['post_types-' . $gd_post_type . '-not_in_sitemap'] ) ) {
					$gd_sitemap_post_types[] = $gd_post_type;
				}
			}
		}

		$gd_wpseo_post_types = self::wpseo_sitemap_get_post_types();
		$gd_wpseo_taxonomies = self::wpseo_sitemap_get_taxonomies();
		$gd_wpseo_location_type = self::wpseo_sitemap_get_location_type();
		$gd_current_location_type = self::wpseo_sitemap_current_location_type();
		$gd_current_post_type = self::wpseo_sitemap_current_post_type( $gd_current_location_type );
		$gd_current_taxonomy = self::wpseo_sitemap_current_taxonomy( $gd_current_location_type );
		
		$gd_sitemap_global['gd_post_types'] = $gd_post_types;
		$gd_sitemap_global['sitemap_post_types'] = $gd_sitemap_post_types;
		$gd_sitemap_global['geodir_enable_country'] = geodir_get_option( 'lm_default_country' );
		$gd_sitemap_global['geodir_enable_region'] = geodir_get_option( 'lm_default_region' );
		$gd_sitemap_global['geodir_enable_city'] = geodir_get_option( 'lm_default_city' );
		$gd_sitemap_global['default_location'] = $geodirectory->location->get_default_location();
		$gd_sitemap_global['gd_wpseo_post_types'] = $gd_wpseo_post_types;
		$gd_sitemap_global['gd_wpseo_taxonomies'] = $gd_wpseo_taxonomies;
		$gd_sitemap_global['wpseo_location_type'] = $gd_wpseo_location_type;
		$gd_sitemap_global['current_location_type'] = $gd_current_location_type;
		$gd_sitemap_global['current_post_type'] = $gd_current_post_type;
		$gd_sitemap_global['current_taxonomy'] = $gd_current_taxonomy;
		
		if ( !empty( $gd_current_post_type ) ) {
			if ( in_array( $gd_current_post_type, $gd_wpseo_post_types ) ) {
				$wpseo_sitemaps->register_sitemap( $gd_current_post_type . '_location_' . $gd_current_location_type, array( 'GeoDir_Location_SEO', 'wpseo_sitemap_post_type_location' ) );
			}
		} else if ( !empty( $gd_current_taxonomy ) ) {
			$wpseo_sitemaps->register_sitemap( $gd_current_taxonomy . '_location_' . $gd_current_location_type, array( 'GeoDir_Location_SEO', 'wpseo_sitemap_taxonomy_location' ) );
		} else if ( !empty( $gd_current_location_type ) ) {
			$wpseo_sitemaps->register_sitemap( 'gd_location_' . $gd_current_location_type, array( 'GeoDir_Location_SEO', 'wpseo_sitemap_gd_location_' . $gd_current_location_type ) );
		}
		return;
	}
	
	public static function wpseo_sitemap_entries_per_page() {
		$entries = (int) apply_filters( 'wpseo_sitemap_entries_per_page', 1000 );

		return $entries;
	}

	public static function wpseo_sitemap_get_post_types() {
		global $gd_wpseo_options, $gd_post_types;

		$post_types = array();
		
		if ( geodir_get_option( 'lm_sitemap_exclude_post_types' ) ) {
			return $post_types;
		}

		if ( !empty( $gd_post_types ) ) {
			foreach ( $gd_post_types as $gd_post_type ) {
				if ( empty( $gd_wpseo_options['post_types-' . $gd_post_type . '-not_in_sitemap'] ) ) {
					$post_types[] = $gd_post_type;
				}
			}
		}
		
		return $post_types;
	}

	public static function wpseo_sitemap_get_taxonomies() {
		global $gd_wpseo_options, $gd_post_types;
			
		$category = array();
		$tag = array();
			
		foreach ($gd_post_types as $gd_post_type) {
			if ( ! GeoDir_Post_types::supports( $gd_post_type, 'location' ) ) {
				continue;
			}
			
			$gd_cat_taxonomy = $gd_post_type . 'category';
			$gd_tag_taxonomy = $gd_post_type . '_tags';
			
			$include_cat = true;
			if (apply_filters('wpseo_sitemap_exclude_taxonomy', false, $gd_cat_taxonomy)) {
				$include_cat = false;
			}

			if (isset($gd_wpseo_options['taxonomies-' . $gd_cat_taxonomy . '-not_in_sitemap']) && $gd_wpseo_options['taxonomies-' . $gd_cat_taxonomy . '-not_in_sitemap'] === true) {
				$include_cat = false;
			}
			
			$include_tag = true;
			if (apply_filters('wpseo_sitemap_exclude_taxonomy', false, $gd_tag_taxonomy)) {
				$include_tag = false;
			}

			if (isset($gd_wpseo_options['taxonomies-' . $gd_tag_taxonomy . '-not_in_sitemap'] ) && $gd_wpseo_options['taxonomies-' . $gd_tag_taxonomy . '-not_in_sitemap'] === true) {
				$include_tag = false;
			}
			
			if ($include_cat) {
				$category[] = $gd_post_type;
			}
			
			if ($include_tag) {
				$tag[] = $gd_post_type;
			}
		}
		
		$taxonomies = array();
		if (!geodir_get_option('lm_sitemap_exclude_cats') && !empty($category)) {
			$taxonomies['category'] = $category;
		}
		if (!geodir_get_option('lm_sitemap_exclude_tags') && !empty($tag)) {
			$taxonomies['tag'] = $tag;
		}
		
		return $taxonomies;
	}

	public static function wpseo_sitemap_index( $sitemap ) {
			global $gd_sitemap_global;

			if ( ! self::wpseo_is_sitemap_page() ) {
				return $sitemap;
			}
			
			$wpseo_location_type = !empty($gd_sitemap_global['wpseo_location_type']) ? $gd_sitemap_global['wpseo_location_type'] : '';
			$exclude_location = !empty($gd_sitemap_global['exclude_location']) ? true : false;
			$exclude_post_types = !empty($gd_sitemap_global['exclude_post_types']) ? true : false;
			$exclude_taxonomies = !empty($gd_sitemap_global['exclude_taxonomies']) ? true : false;
			
			if ($exclude_location && $exclude_post_types && $exclude_taxonomies) {
				return $sitemap;
			}
			
			switch ($wpseo_location_type) {
				case 'country_city':
					if ( !$exclude_location ) {
						$sitemap .= self::wpseo_location_sitemap_index('country');
						$sitemap .= self::wpseo_location_sitemap_index('country_city');
						if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) ) {
							$sitemap .= self::wpseo_location_sitemap_index('neighbourhood');
						}
					}

					// post type + location
					if ( ! $exclude_post_types ) {
						if ( $items = self::wpseo_post_type_sitemap_index('country') ) {
							$sitemap .= $items;
						}

						if ( $items = self::wpseo_post_type_sitemap_index('country_city') ) {
							$sitemap .= $items;
						}

						if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) && ( $items = self::wpseo_post_type_sitemap_index( 'neighbourhood' ) ) ) {
							$sitemap .= $items;
						}
					}

					if ( !$exclude_taxonomies && $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index('country') ) {
						$sitemap .= $sitemap_taxonomies;
					}
					
					if ( !$exclude_taxonomies && $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index('country_city') ) {
						$sitemap .= $sitemap_taxonomies;
					}

					if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) && ! $exclude_taxonomies && ( $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index( 'neighbourhood' ) ) ) {
						$sitemap .= $sitemap_taxonomies;
					}
				break;
				case 'region_city':
					if (!$exclude_location) {
						$sitemap .= self::wpseo_location_sitemap_index('region');
						$sitemap .= self::wpseo_location_sitemap_index('region_city');
						if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) ) {
							$sitemap .= self::wpseo_location_sitemap_index('neighbourhood');
						}
					}

					// post type + location
					if ( ! $exclude_post_types ) {
						if ( $items = self::wpseo_post_type_sitemap_index('region') ) {
							$sitemap .= $items;
						}

						if ( $items = self::wpseo_post_type_sitemap_index('region_city') ) {
							$sitemap .= $items;
						}

						if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) && ( $items = self::wpseo_post_type_sitemap_index( 'neighbourhood' ) ) ) {
							$sitemap .= $items;
						}
					}
					
					if ( !$exclude_taxonomies && $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index('region') ) {
						$sitemap .= $sitemap_taxonomies;
					}
					
					if ( !$exclude_taxonomies && $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index('region_city') ) {
						$sitemap .= $sitemap_taxonomies;
					}

					if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) && ! $exclude_taxonomies && ( $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index( 'neighbourhood' ) ) ) {
						$sitemap .= $sitemap_taxonomies;
					}
				break;
				case 'city':
					if (!$exclude_location) {
						$sitemap .= self::wpseo_location_sitemap_index('city');
						if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) ) {
							$sitemap .= self::wpseo_location_sitemap_index('neighbourhood');
						}
					}

					// post type + location
					if ( ! $exclude_post_types ) {
						if ( $items = self::wpseo_post_type_sitemap_index('city') ) {
							$sitemap .= $items;
						}

						if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) && ( $items = self::wpseo_post_type_sitemap_index( 'neighbourhood' ) ) ) {
							$sitemap .= $items;
						}
					}
					
					if ( !$exclude_taxonomies && $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index('city') ) {
						$sitemap .= $sitemap_taxonomies;
					}

					if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) && ! $exclude_taxonomies && ( $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index( 'neighbourhood' ) ) ) {
						$sitemap .= $sitemap_taxonomies;
					}
				break;
				default:
					if (!$exclude_location) {
						$sitemap .= self::wpseo_location_sitemap_index('country');
						$sitemap .= self::wpseo_location_sitemap_index('country_region');
						$sitemap .= self::wpseo_location_sitemap_index('full');
						if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) ) {
							$sitemap .= self::wpseo_location_sitemap_index('neighbourhood');
						}
					}

					// post type + location
					if ( ! $exclude_post_types ) {
						if ( $items = self::wpseo_post_type_sitemap_index('country') ) {
							$sitemap .= $items;
						}

						if ( $items = self::wpseo_post_type_sitemap_index('country_region') ) {
							$sitemap .= $items;
						}

						if ( $items = self::wpseo_post_type_sitemap_index('full') ) {
							$sitemap .= $items;
						}

						if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) && ( $items = self::wpseo_post_type_sitemap_index( 'neighbourhood' ) ) ) {
							$sitemap .= $items;
						}
					}
					
					if ( !$exclude_taxonomies && $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index('country') ) {
						$sitemap .= $sitemap_taxonomies;
					}
					
					if ( !$exclude_taxonomies && $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index('country_region') ) {
						$sitemap .= $sitemap_taxonomies;
					}
					
					if ( !$exclude_taxonomies && $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index('full') ) {
						$sitemap .= $sitemap_taxonomies;
					}

					if ( ! empty( $gd_sitemap_global['neighbourhoods'] ) && ! $exclude_taxonomies && ( $sitemap_taxonomies = self::wpseo_taxonomy_sitemap_index( 'neighbourhood' ) ) ) {
						$sitemap .= $sitemap_taxonomies;
					}
				break;
			}
			return $sitemap;
	}
	
	public static function wpseo_location_sitemap_index( $location_type ) {
		global $gd_wpseo_max_entries, $gd_wpseo_index, $gd_post_types, $gd_wpseo_date_helper;
		
		$sitemap = '';
		$gd_wpseo_index = true;
		
		$rows = self::wpseo_sitemap_get_locations_post_types( $location_type, $gd_post_types );
		if ( empty( $rows ) ) {
			return $sitemap;
		}
		$count = count($rows);
		$n = ( $count > $gd_wpseo_max_entries ) ? (int)ceil( $count / $gd_wpseo_max_entries ) : 1;
		
		for ( $i = 0; $i < $n; $i++ ) {
			$page = ( $n > 1 ) ? ( $i + 1 ) : '';
			
			$index = ( $n - 1 ) * $gd_wpseo_max_entries;
			
			if ( !empty( $rows[$index]->date_gmt ) ) {
				$date = $gd_wpseo_date_helper->format( $rows[$index]->date_gmt );
			} else {
				$date = '';
			}

			$sitemap .= "\t<sitemap>\n";
			$sitemap .= "\t\t<loc>" . self::wpseo_sitemap_base_url( "gd_location_" . $location_type . "-sitemap" . $page . ".xml" ) . "</loc>\n";
			$sitemap .= "\t\t<lastmod>" . htmlspecialchars( $date ) . "</lastmod>\n";
			$sitemap .= "\t</sitemap>\n";
		}
		
		return $sitemap;
	}

	public static function wpseo_post_type_sitemap_index( $location_type ) {
		global $gd_sitemap_global, $gd_wpseo_max_entries,$gd_wpseo_date_helper, $gd_wpseo_index, $gd_post_types;
		
		$sitemap = '';
		$gd_wpseo_index = true;

		if ( empty( $gd_sitemap_global['gd_wpseo_post_types'] ) ) {
			return $sitemap;
		}
		
		foreach ( $gd_sitemap_global['gd_wpseo_post_types'] as $gd_post_type ) {
			$rows = self::wpseo_sitemap_get_locations_post_types( $location_type, array( $gd_post_type ) );
			if ( empty( $rows ) ) {
				return $sitemap;
			}
			$count = count($rows);
			
			$n = ( $count > $gd_wpseo_max_entries ) ? (int)ceil( $count / $gd_wpseo_max_entries ) : 1;
			
			for ( $i = 0; $i < $n; $i++ ) {
				$page = ( $n > 1 ) ? ( $i + 1 ) : '';
				
				$index = ( $n - 1 ) * $gd_wpseo_max_entries;
				
				if ( !empty( $rows[$index]->date_gmt ) ) {
					$date = $gd_wpseo_date_helper->format( $rows[$index]->date_gmt );
				} else {
					$date = '';
				}
				
				$sitemap .= "\t<sitemap>\n";
				$sitemap .= "\t\t<loc>" . self::wpseo_sitemap_base_url( $gd_post_type . "_location_" . $location_type . "-sitemap" . $page . ".xml" ) . "</loc>\n";
				$sitemap .= "\t\t<lastmod>" . htmlspecialchars( $date ) . "</lastmod>\n";
				$sitemap .= "\t</sitemap>\n";
			}
		}
		
		return $sitemap;
	}

	public static function wpseo_sitemap_post_type_location() {
		global $wpseo_sitemaps, $gd_sitemap_global;
		
		if ( empty( $gd_sitemap_global['current_location_type'] ) || empty( $gd_sitemap_global['current_post_type'] ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$sitemap = self::wpseo_post_type_sitemap_content( $gd_sitemap_global['current_location_type'], $gd_sitemap_global['current_post_type'] );
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	public static function wpseo_post_type_sitemap_content( $location_type, $post_type ) {
		global $wp, $geodirectory, $gd_post_type_archive_links, $gd_wpseo_date_helper, $wpseo_sitemaps, $gd_wpseo_options, $gd_wpseo_max_entries, $gd_post_types;
		
		$sitemap_key = $post_type . '_location_' . $location_type;
		$n = (int)get_query_var( 'sitemap_n' );
		
		$permalink_structure = get_option('permalink_structure');
		
		if ((int)$n > 0) {
			$page = $n - 1;
		} else {
			$page = 0;
		}
		
		$rows = self::wpseo_sitemap_get_locations_post_types( $location_type, array( $post_type ), $page );
		
		$output = '';
		if ( !empty($rows) ) {
			$old_wp = $wp;
			$old_geodirectory = $geodirectory;

			$unset_location_terms = array();
			
			switch ($location_type) {
				case 'country_region':
				case 'region':
					$unset_location_terms = array('city');
				break;
				case 'country_city':
					$unset_location_terms = array('region');
				break;
				case 'region_city':
					$unset_location_terms = array('country');
				break;
				case 'country':
					$unset_location_terms = array('region', 'city');
				break;
				case 'city':
					$unset_location_terms = array('country', 'region');
				break;
			}
			
			foreach ( $rows as $row ) {
				if ( ! empty( $gd_post_type_archive_links ) && isset( $gd_post_type_archive_links[ $post_type ] ) ) {
					unset( $gd_post_type_archive_links[ $post_type ] );
				}

				$geodirectory = $old_geodirectory;

				$country = ! empty( $row->country ) ? $row->country : '';
				$region = ! empty( $row->region ) ? $row->region : '';
				$city = ! empty( $row->city ) ? $row->city : '';
				$neighbourhood = ! empty( $row->neighbourhood ) ? $row->neighbourhood : '';

				$location = array();
				if ( $neighbourhood ) {
					$location = GeoDir_Location_Neighbourhood::get_info_by_slug( $neighbourhood );
				} elseif ( $city ) {
					$location = GeoDir_Location_City::get_info_by_name( $city, $country, $region );
				} elseif ( $region ) {
					$location = GeoDir_Location_Region::get_info_by_name( $region, $country );
				} elseif ( $country ) {
					$location = GeoDir_Location_Country::get_info_by_name( $country );
				}

				if ( empty( $location ) ) {
					continue;
				}

				$location_terms = array();
				if ( !empty( $row->country ) && !in_array( 'country', $unset_location_terms ) ) {
					$wp->query_vars['country'] = $location->country_slug;
				} else {
					$wp->query_vars['country'] = '';
				}
				
				if ( !empty( $row->region ) && !in_array( 'region', $unset_location_terms ) ) {
					$wp->query_vars['region'] = $location->region_slug;
				} else {
					$wp->query_vars['region'] = '';
				}
				
				if ( !empty( $row->city ) && !in_array( 'city', $unset_location_terms ) ) {
					$wp->query_vars['city'] = $location->city_slug;
				} else {
					$wp->query_vars['city'] = '';
				}

				if ( $location_type == 'neighbourhood' && ! empty( $location->slug ) ) {
					$wp->query_vars['neighbourhood'] = $location->slug;
					$geodirectory->location->neighbourhood_slug = '';
				}

				$geodirectory->location->country_slug = '';
				$geodirectory->location->region_slug = '';
				$geodirectory->location->city_slug = '';
				$geodirectory->location->set_current();

				$cpt_location_link = get_post_type_archive_link( $post_type );
				
				if ( !empty( $row->date_gmt ) ) {
					$date = $gd_wpseo_date_helper->format( $row->date_gmt );
				} else {
					$date = '';
				}
				
				$url = array(
					'loc' => $cpt_location_link,
					'pri' => 1,
					'chf' => self::wpseo_sitemap_filter_frequency( $post_type . '_post', 'daily', $cpt_location_link ),
					'mod' => $date,
				);
				// Use this filter to adjust the entry before it gets added to the sitemap.
				$url = apply_filters( 'wpseo_sitemap_entry', $url, $sitemap_key, $row );

				if ( is_array( $url ) && $url !== array() ) {
					$output .= self::wpseo_sitemap_url( $url );
				}
			}

			$wp = $old_wp;
			$geodirectory = $old_geodirectory;

			unset( $post_type, $cpt_location_link, $url );
		}

		if ( empty( $output ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return;
		}
		
		$sitemap = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" ';
		$sitemap .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" ';
		$sitemap .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$sitemap .= $output;

		// Filter to allow adding extra URLs, only do this on the first XML sitemap, not on all.
		if ( $n === 1 || $n === 0 ) {
			$sitemap .= apply_filters( 'wpseo_sitemap_' . $sitemap_key . '_content', '' );
		}

		$sitemap .= '</urlset>';
		
		return $sitemap;
	}

	public static function wpseo_taxonomy_sitemap_index( $location_type ) {
		global $gd_sitemap_global, $gd_wpseo_date_helper, $gd_wpseo_max_entries, $gd_wpseo_index;
		
		$gd_wpseo_index = true;
		$sitemap = '';
		
		if ( empty( $gd_sitemap_global['gd_post_types'] ) ) {
			return $sitemap;
		}
		
		foreach ( $gd_sitemap_global['gd_post_types'] as $gd_post_type ) {
			if ( !empty( $gd_sitemap_global['gd_wpseo_taxonomies']['category'] ) && in_array( $gd_post_type, $gd_sitemap_global['gd_wpseo_taxonomies']['category'] ) ) {
				$taxonomy = $gd_post_type . 'category';

				// Skip location + taxonomy sitemap.
				$skip_sitemap = apply_filters( 'geodir_location_wpseo_skip_location_taxonomy_sitemap', false, $location_type, $gd_post_type, $taxonomy );
				if ( $skip_sitemap ) {
					continue;
				}

				$rows = self::wpseo_sitemap_get_locations_taxonomies( $location_type, $gd_post_type, 'category' );
				
				if ( !empty( $rows ) ) {
					$count = count( $rows );
					
					$n = ( $count > $gd_wpseo_max_entries ) ? (int)ceil( $count / $gd_wpseo_max_entries ) : 1;
					
					for ( $i = 0; $i < $n; $i++ ) {
						$page = ( $n > 1 ) ? ( $i + 1 ) : '';
						
						$index = ( $n - 1 ) * $gd_wpseo_max_entries;
						
						if ( !empty( $rows[$index]->date_gmt ) ) {
							$date = $gd_wpseo_date_helper->format( $rows[$index]->date_gmt );
						} else {
							$date = '';
						}
						
						$sitemap .= "\t<sitemap>\n";
						$sitemap .= "\t\t<loc>" . self::wpseo_sitemap_base_url( $taxonomy . "_location_" . $location_type . "-sitemap" . $page . ".xml" ) . "</loc>\n";
						$sitemap .= "\t\t<lastmod>" . htmlspecialchars( $date ) . "</lastmod>\n";
						$sitemap .= "\t</sitemap>\n";
					}
					
					unset( $count, $n, $i );
				}
			}
			
			if ( !empty( $gd_sitemap_global['gd_wpseo_taxonomies']['tag'] ) && in_array( $gd_post_type, $gd_sitemap_global['gd_wpseo_taxonomies']['tag'] ) ) {
				$taxonomy = $gd_post_type . '_tags';
				$rows = self::wpseo_sitemap_get_locations_taxonomies( $location_type, $gd_post_type, 'tag' );
				
				if ( !empty( $rows ) ) {
					$count = count( $rows );
					
					$n = ( $count > $gd_wpseo_max_entries ) ? (int)ceil( $count / $gd_wpseo_max_entries ) : 1;
					
					for ( $i = 0; $i < $n; $i++ ) {
						$page = ( $n > 1 ) ? ( $i + 1 ) : '';
						
						$index = ( $n - 1 ) * $gd_wpseo_max_entries;
						
						if ( !empty( $rows[$index]->date_gmt ) ) {
							$date = $gd_wpseo_date_helper->format( $rows[$index]->date_gmt );
						} else {
							$date = '';
						}
						
						$sitemap .= "\t<sitemap>\n";
						$sitemap .= "\t\t<loc>" . self::wpseo_sitemap_base_url( $taxonomy . "_location_" . $location_type . "-sitemap" . $page . ".xml" ) . "</loc>\n";
						$sitemap .= "\t\t<lastmod>" . htmlspecialchars( $date ) . "</lastmod>\n";
						$sitemap .= "\t</sitemap>\n";
					}
					
					unset( $count, $n, $i );
				}
			}
		}
		
		return $sitemap;
	}

	public static function wpseo_sitemap_taxonomy_location() {
		global $wpseo_sitemaps, $gd_sitemap_global;
		
		if ( empty( $gd_sitemap_global['current_location_type'] ) || empty( $gd_sitemap_global['current_taxonomy'] ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$sitemap = self::wpseo_taxonomy_sitemap_content( $gd_sitemap_global['current_location_type'], $gd_sitemap_global['current_taxonomy'] );
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	public static function wpseo_taxonomy_sitemap_content($location_type, $taxonomy) {
		global $wp, $wpseo_sitemaps, $geodirectory, $gd_wpseo_date_helper, $gd_wpseo_options, $gd_wpseo_max_entries;
		
		if (empty($location_type) || empty($taxonomy)) {
			return NULL;
		}
		
		$taxonomy_type = '';
		$gd_post_type = '';
		if (substr($taxonomy, -8) == 'category') {
			$taxonomy_type = 'category';
			
			$explode = explode( 'category', $taxonomy, 2 );
			
			if ( !empty( $explode[0] ) ) {
				$gd_post_type = $explode[0];
			}
		} else if (substr($taxonomy, -5) == '_tags') {
			$taxonomy_type = 'tag';
			
			$explode = explode( '_tags', $taxonomy, 2 );
			
			if ( !empty( $explode[0] ) ) {
				$gd_post_type = $explode[0];
			}
		}
		
		if (empty($taxonomy_type) || empty($gd_post_type)) {
			return NULL;
		}
		
		$sitemap_key = $taxonomy . '_location_' . $location_type;
		$n = (int)get_query_var( 'sitemap_n' );
		
		if ((int)$n > 0) {
			$page = $n - 1;
		} else {
			$page = 0;
		}
		
		$rows = self::wpseo_sitemap_get_locations_taxonomies( $location_type, $gd_post_type, $taxonomy_type, $page );
		
		$output = '';
		if ( !empty($rows) ) {
			$old_wp = $wp;
			$old_geodirectory = $geodirectory;
			
			$unset_location_terms = array();
			
			switch ($location_type) {
				case 'country_region':
				case 'region':
					$unset_location_terms = array('city');
				break;
				case 'country_city':
					$unset_location_terms = array('region');
				break;
				case 'region_city':
					$unset_location_terms = array('country');
				break;
				case 'country':
					$unset_location_terms = array('region', 'city');
				break;
				case 'city':
					$unset_location_terms = array('country', 'region');
				break;
			}
			
			foreach ( $rows as $row ) {
				$term_id = (int)$row->term_id;
				if ( empty( $term_id ) ) {
					continue;
				}

				$geodirectory = $old_geodirectory;

				$country = ! empty( $row->country ) ? $row->country : '';
				$region = ! empty( $row->region ) ? $row->region : '';
				$city = ! empty( $row->city ) ? $row->city : '';
				$neighbourhood = ! empty( $row->neighbourhood ) ? $row->neighbourhood : '';

				$location = array();
				if ( $neighbourhood ) {
					$location = GeoDir_Location_Neighbourhood::get_info_by_slug( $neighbourhood );
				} elseif ( $city ) {
					$location = GeoDir_Location_City::get_info_by_name( $city, $country, $region );
				} elseif ( $region ) {
					$location = GeoDir_Location_Region::get_info_by_name( $region, $country );
				} elseif ( $country ) {
					$location = GeoDir_Location_Country::get_info_by_name( $country );
				}

				if ( empty( $location ) ) {
					continue;
				}

				$location_terms = array();
				if ( !empty( $row->country ) && !in_array( 'country', $unset_location_terms ) ) {
					$wp->query_vars['country'] = $location->country_slug;
				} else {
					$wp->query_vars['country'] = '';
				}
				
				if ( !empty( $row->region ) && !in_array( 'region', $unset_location_terms ) ) {
					$wp->query_vars['region'] = $location->region_slug;
				} else {
					$wp->query_vars['region'] = '';
				}
				
				if ( !empty( $row->city ) && !in_array( 'city', $unset_location_terms ) ) {
					$wp->query_vars['city'] = $location->city_slug;
				} else {
					$wp->query_vars['city'] = '';
				}

				if ( $location_type == 'neighbourhood' && ! empty( $location->slug ) ) {
					$wp->query_vars['neighbourhood'] = $location->slug;
					$geodirectory->location->neighbourhood_slug = '';
				}

				$geodirectory->location->country_slug = '';
				$geodirectory->location->region_slug = '';
				$geodirectory->location->city_slug = '';
				$geodirectory->location->set_current();

				$term_link = get_term_link( $term_id, $taxonomy );
				
				if ( is_wp_error( $term_link ) ) {
					continue;
				}
				
				if ( !empty( $row->date_gmt ) ) {
					$date = $gd_wpseo_date_helper->format( $row->date_gmt );
				} else {
					$date = '';
				}
				
				$url = array(
					'loc' => $term_link,
					'pri' => 1,
					'chf' => self::wpseo_sitemap_filter_frequency( $taxonomy . '_term', 'weekly', $term_link ),
					'mod' => $date,
				);
				
				// Use this filter to adjust the entry before it gets added to the sitemap.
				$url = apply_filters( 'wpseo_sitemap_entry', $url, $sitemap_key, $row );

				if ( is_array($url) && $url !== array()) {
					$output .= self::wpseo_sitemap_url( $url );
				}
			}
			
			$wp = $old_wp;
			$geodirectory = $old_geodirectory;
		}

		if ( empty( $output ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$sitemap = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" ';
		$sitemap .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" ';
		$sitemap .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$sitemap .= $output;

		// Filter to allow adding extra URLs, only do this on the first XML sitemap, not on all.
		if ( $n === 1 || $n === 0 ) {
			$sitemap .= apply_filters( 'wpseo_sitemap_' . $sitemap_key . '_content', '' );
		}

		$sitemap .= '</urlset>';
		
		return $sitemap;
	}

	public static function wpseo_location_sitemap_content( $location_type ) {
		global $wpseo_sitemaps, $gd_wpseo_options,$gd_wpseo_date_helper, $gd_wpseo_max_entries, $gd_post_types;
		
		$sitemap_key = 'gd_location_' . $location_type;
		$n = (int)get_query_var( 'sitemap_n' );
		
		$permalink_structure = get_option('permalink_structure');
		
		if ((int)$n > 0) {
			$page = $n - 1;
		} else {
			$page = 0;
		}
		
		$rows = self::wpseo_sitemap_get_locations_post_types( $location_type, $gd_post_types, $page );
		
		$output = '';
		if ( !empty($rows) ) {
			$unset_location_terms = array();
			
			switch ($location_type) {
				case 'country_region':
				case 'region':
					$unset_location_terms = array('gd_city');
				break;
				case 'country_city':
					$unset_location_terms = array('gd_region');
				break;
				case 'region_city':
					$unset_location_terms = array('gd_country');
				break;
				case 'country':
					$unset_location_terms = array('gd_region', 'gd_city');
				break;
				case 'city':
					$unset_location_terms = array('gd_country', 'gd_region');
				break;
			}
			
			foreach ( $rows as $row ) {
				$country = '';
				$region = '';
				$city = '';
				$neighbourhood = '';

				if ( ! empty( $row->country ) )
					$country = $row->country;
				if ( ! empty( $row->region ) )
					$region = $row->region;
				if ( ! empty( $row->city ) )
					$city = $row->city;
				if ( ! empty( $row->neighbourhood ) )
					$neighbourhood = $row->neighbourhood;

				$location_terms = array();
				if ( ! empty( $country ) )
					$location_terms['gd_country'] = $country;
				if ( ! empty( $region ) )
					$location_terms['gd_region'] = $region;
				if ( ! empty( $city ) )
					$location_terms['gd_city'] = $city;
				if ( ! empty( $neighbourhood ) )
					$location_terms['gd_neighbourhood'] = $city;

				if (!empty($unset_location_terms)) {
					foreach ($unset_location_terms as $location_term) {
						unset($location_terms[$location_term]);
					}
				}

				$location = array();
				if ( $neighbourhood ) {
					$location = GeoDir_Location_Neighbourhood::get_info_by_slug( $neighbourhood );
				} elseif ( $city ) {
					$location = GeoDir_Location_City::get_info_by_name( $city, $country, $region );
				} elseif ( $region ) {
					$location = GeoDir_Location_Region::get_info_by_name( $region, $country );
				} elseif ( $country ) {
					$location = GeoDir_Location_Country::get_info_by_name( $country );
				}

				if ( ! empty( $location_terms['gd_country'] ) && ! empty( $location->country_slug ) ) {
					$location_terms['gd_country'] = $location->country_slug;
				}
				if ( ! empty( $location_terms['gd_region'] ) && ! empty( $location->region_slug ) ) {
					$location_terms['gd_region'] = $location->region_slug;
				}
				if ( ! empty( $location_terms['gd_city'] ) && ! empty( $location->city_slug ) ) {
					$location_terms['gd_city'] = $location->city_slug;
				}
				if ( ! empty( $location_terms['gd_neighbourhood'] ) && $neighbourhood && ! empty( $location->slug ) ) {
					$location_terms['gd_neighbourhood'] = $location->slug;
				}

				$location_link = geodir_location_get_url($location_terms, $permalink_structure);
				
				if ( !empty( $row->date_gmt ) ) {
					$date = $gd_wpseo_date_helper->format( $row->date_gmt );
				} else {
					$date = '';
				}
				
				$url = array(
					'loc' => $location_link,
					'pri' => 1,
					'chf' => self::wpseo_sitemap_filter_frequency( 'locationpage', 'daily', $location_link ),
					'mod' => $date,
				);
				// Use this filter to adjust the entry before it gets added to the sitemap.
				$url = apply_filters( 'wpseo_sitemap_entry', $url, $sitemap_key, $row );

				if ( is_array( $url ) && $url !== array() ) {
					$output .= self::wpseo_sitemap_url( $url );
				}
			}
			unset( $location, $location_link, $url );
		}

		if ( empty( $output ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return;
		}
		
		$sitemap = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" ';
		$sitemap .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" ';
		$sitemap .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$sitemap .= $output;

		// Filter to allow adding extra URLs, only do this on the first XML sitemap, not on all.
		if ( $n === 1 || $n === 0 ) {
			$sitemap .= apply_filters( 'wpseo_sitemap_' . $sitemap_key . '_content', '' );
		}

		$sitemap .= '</urlset>';
		
		return $sitemap;
	}

	public static function wpseo_sitemap_gd_location_full() {
		global $wpseo_sitemaps;
		
		$sitemap = self::wpseo_location_sitemap_content('full');
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	public static function wpseo_sitemap_gd_location_country_region() {
		global $wpseo_sitemaps;
		
		$sitemap = self::wpseo_location_sitemap_content('country_region');
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	public static function wpseo_sitemap_gd_location_country_city() {
		global $wpseo_sitemaps;
		
		$sitemap = self::wpseo_location_sitemap_content('country_city');
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	public static function wpseo_sitemap_gd_location_region_city() {
		global $wpseo_sitemaps;
		
		$sitemap = self::wpseo_location_sitemap_content('region_city');
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	public static function wpseo_sitemap_gd_location_country() {
		global $wpseo_sitemaps;
		
		$sitemap = self::wpseo_location_sitemap_content('country');
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	public static function wpseo_sitemap_gd_location_region() {
		global $wpseo_sitemaps;
		
		$sitemap = self::wpseo_location_sitemap_content('region');
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	public static function wpseo_sitemap_gd_location_city() {
		global $wpseo_sitemaps;
		
		$sitemap = self::wpseo_location_sitemap_content('city');
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	public static function wpseo_sitemap_gd_location_neighbourhood() {
		global $wpseo_sitemaps;
		
		$sitemap = self::wpseo_location_sitemap_content( 'neighbourhood' );
		if ( empty( $sitemap ) ) {
			$wpseo_sitemaps->bad_sitemap = true;
			return NULL;
		}
		
		$wpseo_sitemaps->set_sitemap($sitemap);
	}

	/**
	 * Function to dynamically filter the change frequency
	 *
	 * @param string $filter  Expands to wpseo_sitemap_$filter_change_freq, allowing for a change of the frequency for numerous specific URLs.
	 * @param string $default The default value for the frequency.
	 * @param string $url     The URL of the current entry.
	 *
	 * @return mixed|void
	 */
	public static function wpseo_sitemap_filter_frequency( $filter, $default, $url ) {
		/**
		 * Filter: 'wpseo_sitemap_' . $filter . '_change_freq' - Allow filtering of the specific change frequency
		 *
		 * @api string $default The default change frequency
		 */
		$change_freq = apply_filters( 'wpseo_sitemap_' . $filter . '_change_freq', $default, $url );

		if ( ! in_array( $change_freq, array( 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never' ) )
		) {
			$change_freq = $default;
		}

		return $change_freq;
	}

	public static function wpseo_sitemap_get_location_type() {
		$hide_country_part = geodir_get_option( 'lm_hide_country_part' );
		$hide_region_part = geodir_get_option( 'lm_hide_region_part' );
		
		$type = 'full';
		if ($hide_region_part && $hide_country_part) {
			$type = 'city';
		} else if ($hide_region_part && !$hide_country_part) {
			$type = 'country_city';
		} else if (!$hide_region_part && $hide_country_part) {
			$type = 'region_city';
		}
		
		return $type;
	}

	public static function wpseo_sitemap_current_location_type() {
		if (!isset($_SERVER['REQUEST_URI'])) {
			return false;
		}
		
		$basename = basename($_SERVER['REQUEST_URI']);
		
		if ( stripos( $basename, '_location_' ) === false || ( stripos( $basename, '.xml' ) === false && stripos( $basename, '.xsl' ) === false ) ) {
			return false;
		}
		
		$check_types = array( 'full', 'country_city', 'country_region', 'region_city', 'country', 'region', 'city', 'neighbourhood' );
		
		$location_type = '';
		foreach ( $check_types as $check_type ) {
			if ( stripos( $basename, '_location_' . $check_type . '-' ) !== false ) {
				$location_type = $check_type;
				break;
			}
		}
		
		return $location_type;
	}

	public static function wpseo_sitemap_current_post_type( $location_type ) {
		global $gd_post_types;

		if ( ! $location_type ) {
			return false;
		}
		
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}
		
		$basename = basename( $_SERVER['REQUEST_URI'] );
		
		if ( stripos( $basename, '_location_' ) === false || ( stripos( $basename, '.xml' ) === false && stripos( $basename, '.xsl' ) === false ) ) {
			return false;
		}
		
		$post_type = '';
		
		if ( stripos( $basename, '_location_' . $location_type . '-' ) !== false ) {
			$explode = explode( '_location_' . $location_type . '-', $basename, 2 );
			
			if ( !empty( $explode[0] ) && in_array( $explode[0], $gd_post_types ) ) {
				$post_type = $explode[0];
			}
		}
		
		return $post_type;
	}

	public static function wpseo_sitemap_current_taxonomy($location_type) {
		if (!$location_type) {
			return false;
		}
		
		if (!isset($_SERVER['REQUEST_URI'])) {
			return false;
		}
		
		$basename = basename($_SERVER['REQUEST_URI']);
		
		if ( stripos( $basename, '_location_' ) === false || ( stripos( $basename, '.xml' ) === false && stripos( $basename, '.xsl' ) === false ) ) {
			return false;
		}
		
		$taxonomy = '';
		
		if ( stripos( $basename, 'category_location_' . $location_type . '-' ) !== false ) {
			$explode = explode( 'category_location_' . $location_type . '-', $basename, 2 );
			
			if ( !empty( $explode[0] ) ) {
				$taxonomy = $explode[0] . 'category';
			}
		}
		
		if ( empty( $taxonomy ) && stripos( $basename, '_tags_location_' . $location_type . '-' ) !== false ) {
			$explode = explode( '_tags_location_' . $location_type . '-', $basename, 2 );
			
			if ( !empty( $explode[0] ) ) {
				$taxonomy = $explode[0] . '_tags';
			}
		}
		
		return $taxonomy;
	}

	public static function wpseo_sitemap_get_locations_post_types( $location_type, $gd_post_types, $page = false ) {
		global $wpdb, $plugin_prefix, $gd_sitemap_global, $gd_wpseo_max_entries, $gd_wpseo_index;

		$fields = '';
		$join_condition = '';
		$where = '';
		$group_by = '';

		switch ($location_type) {
			case 'country_region':
			case 'region':
				$fields = "pd.country, pd.region";
				$group_by = "pd.country, pd.region";
			break;
			case 'country_city':
				$fields = "pd.country, pd.city";
				$group_by = "pd.country, pd.city";
			break;
			case 'region_city':
				$fields = "pd.region, pd.city";
				$group_by = "pd.region, pd.city";
			break;
			case 'country':
				$fields = "pd.country";
				$group_by = "pd.country";
			break;
			case 'city':
			case 'full':
				$fields = "pd.country, pd.region, pd.city";
				$group_by = "pd.country, pd.region, pd.city";
			break;
			case 'neighbourhood':
				$fields = "pd.country, pd.region, pd.city, pd.neighbourhood";
				$group_by = "pd.city, pd.neighbourhood";
				$where .= " AND pd.neighbourhood != ''";
			break;
		}
		
		if ( empty( $fields ) ) {
			return false;
		}
		
		$geodir_enable_country = !empty($gd_sitemap_global['geodir_enable_country']) ? $gd_sitemap_global['geodir_enable_country'] : '';
		$geodir_enable_region = !empty($gd_sitemap_global['geodir_enable_region']) ? $gd_sitemap_global['geodir_enable_region'] : '';
		$geodir_enable_city = !empty($gd_sitemap_global['geodir_enable_city']) ? $gd_sitemap_global['geodir_enable_city'] : '';
		$default_location =!empty($gd_sitemap_global['default_location']) ? $gd_sitemap_global['default_location'] : '';
		
		if ($geodir_enable_country == 'default' && !empty($default_location->country)) {
			$where .= $wpdb->prepare( " AND pd.country LIKE %s", $default_location->country );
		}
		
		if ($geodir_enable_region == 'default' && !empty($default_location->region)) {
			$where .= $wpdb->prepare( " AND pd.region LIKE %s", $default_location->region );
		}
		
		if ($geodir_enable_city == 'default' && !empty($default_location->city)) {
			$where .= $wpdb->prepare( " AND pd.city LIKE %s", $default_location->city );
		}
		
		$results = array();
		foreach ( $gd_post_types as $gd_post_type ) {
			if ( ! GeoDir_Post_types::supports( $gd_post_type, 'location' ) ) {
				continue;
			}

			// Skip location + CPT sitemap.
			$skip_sitemap = apply_filters( 'geodir_location_wpseo_skip_location_post_type_sitemap', false, $location_type, $gd_post_type );
			if ( $skip_sitemap ) {
				continue;
			}

			$detail_table = geodir_db_cpt_table( $gd_post_type );
			
			$query = "SELECT MAX(p.post_modified_gmt) AS date_gmt, " . $fields . " FROM " . $detail_table . " pd LEFT JOIN " . $wpdb->posts . " p ON p.ID = pd.post_id WHERE p.post_type = '" . $gd_post_type . "' AND p.post_status = 'publish' " . $where . " GROUP BY " . $group_by . " ORDER BY date_gmt DESC";

			$result = $wpdb->get_results($query);
			if ( !empty($result) ) {
				$results[$gd_post_type] = $result;
			}
		}
		
		if ( empty( $results ) ) {
			return false;
		}

		$_types = explode( "_", $location_type );
		if ( $location_type == 'full' || $location_type == 'neighbourhood' ) {
			$_types = array( 'country', 'region', 'city' );
		}

		if ( $location_type == 'neighbourhood' ) {
			$_types[] = 'neighbourhood';
		}

		$rows = array();
		if ( !empty($results) ) {
			foreach ( $results as $cpt => $result ) {
				foreach ( $result as $key => $row ) {
					$index = array();
					foreach ( $_types as $_type ) {
						if ( ! empty( $row->{$_type} ) ) {
							$index[] =  $row->{$_type};
						}
					}

					$index = ! empty( $index ) ? implode( '-', $index ) : '';

					if ( ! empty( $index ) && empty( $rows[ $index ] ) ) {
						$rows[ $index ] = $row;
					}
				}
			}
		}
		$rows = array_values($rows);
		
		if ( $page === 0 || $page > 0 ) {
			$rows = array_slice($rows, ((int)$page * (int)$gd_wpseo_max_entries), (int)$gd_wpseo_max_entries);
		}

		return $rows;
	}

	public static function wpseo_sitemap_get_locations_taxonomies( $location_type, $gd_post_type, $taxonomy_type, $page = false ) {
		global $wpdb, $plugin_prefix, $gd_sitemap_global, $gd_wpseo_max_entries, $gd_wpseo_index;
		
		if ( ! GeoDir_Post_types::supports( $gd_post_type, 'location' ) ) {
			return false;
		}
		
		$taxonomy = $taxonomy_type == 'tag' ? $gd_post_type . '_tags' : $gd_post_type . 'category';
		
		$fields = '';
		$join_condition = '';
		$where = '';
		$group_by = '';
		
		switch ($location_type) {
			case 'country_region':
			case 'region':
				$fields = "pd.country, pd.region";
				$group_by = "tt.term_id, pd.country, pd.region";
			break;
			case 'country_city':
				$fields = "pd.country, pd.city";
				$group_by = "tt.term_id, pd.country, pd.city";
			break;
			case 'region_city':
				$fields = "pd.region, pd.city";
				$group_by = "tt.term_id, pd.region, pd.city";
			break;
			case 'country':
				$fields = "pd.country";
				$group_by = "tt.term_id, pd.country";
			break;
			case 'city':
			case 'full':
				$fields = "pd.country, pd.region, pd.city";
				$group_by = "tt.term_id, pd.country, pd.region, pd.city";
			break;
			case 'neighbourhood':
				$fields = "pd.country, pd.region, pd.city, pd.neighbourhood";
				$group_by = "tt.term_id, pd.city, pd.neighbourhood";
				$where .= " AND pd.neighbourhood != ''";
			break;
		}
		
		if ( empty( $fields ) ) {
			return false;
		}

		$geodir_enable_country = !empty($gd_sitemap_global['geodir_enable_country']) ? $gd_sitemap_global['geodir_enable_country'] : '';
		$geodir_enable_region = !empty($gd_sitemap_global['geodir_enable_region']) ? $gd_sitemap_global['geodir_enable_region'] : '';
		$geodir_enable_city = !empty($gd_sitemap_global['geodir_enable_city']) ? $gd_sitemap_global['geodir_enable_city'] : '';
		$default_location =!empty($gd_sitemap_global['default_location']) ? $gd_sitemap_global['default_location'] : '';

		if ($geodir_enable_country == 'default' && !empty($default_location->country)) {
			$where .= $wpdb->prepare( " AND pd.country LIKE %s", $default_location->country );
		}

		if ($geodir_enable_region == 'default' && !empty($default_location->region)) {
			$where .= $wpdb->prepare( " AND pd.region LIKE %s", $default_location->region );
		}

		if ($geodir_enable_city == 'default' && !empty($default_location->city)) {
			$where .= $wpdb->prepare( " AND pd.city LIKE %s", $default_location->city );
		}

		$detail_table = geodir_db_cpt_table( $gd_post_type );

		$query = "SELECT tt.term_id, MAX(p.post_modified_gmt) AS date_gmt, " . $fields . " FROM `" . $detail_table . "` pd LEFT JOIN `" . $wpdb->posts . "` p ON p.ID = pd.post_id LEFT JOIN `" . $wpdb->term_relationships . "` AS tr ON tr.object_id = p.ID LEFT JOIN `" . $wpdb->term_taxonomy . "` AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE p.post_type = '" . $gd_post_type . "' AND p.post_status = 'publish' AND tt.taxonomy = '" . $taxonomy . "' {$where} GROUP BY " . $group_by . " ORDER BY date_gmt DESC";

		if ( !$gd_wpseo_index && ( $page === 0 || $page > 0 ) ) {
			$query .= " LIMIT " . ( absint( $page ) * (int)$gd_wpseo_max_entries ) . ", " . (int)$gd_wpseo_max_entries;
		}

		return $wpdb->get_results( $query );
	}
	
	/**
	 * Get the base URL for the SEO xml sitemaps.
	 *
	 * @since 1.5.4
	 *
	 * @global object $wpseo_sitemaps WPSEO_Sitemaps object.
	 *
	 * @param string $page page to append to the base URL.
	 *
	 * @return string base URL (incl page) for the sitemaps.
	 */
	public static function wpseo_sitemap_base_url( $page = '' ) {
		global $wpseo_sitemaps;
		
		if ( defined( 'WPSEO_VERSION' ) && version_compare( WPSEO_VERSION, '3.2', '>=' ) ) {
			return $wpseo_sitemaps->router->get_base_url( $page );
		}
		
		return wpseo_xml_sitemaps_base_url( $page );
	}

	/**
	 * Build the `<url>` tag for a given URL.
	 *
	 * @since 1.5.4
	 *
	 * @global object $wpseo_sitemaps WPSEO_Sitemaps object.
	 *
	 * @param array $url Array of parts that make up this entry.
	 *
	 * @return string Rendered sitemap URL.
	 */
	public static function wpseo_sitemap_url( $url = '' ) {
		global $wpseo_sitemaps;
		
		if ( defined( 'WPSEO_VERSION' ) && version_compare( WPSEO_VERSION, '3.2', '>=' ) ) {
			return $wpseo_sitemaps->renderer->sitemap_url( $url );
		}
		
		return $wpseo_sitemaps->sitemap_url( $url );
	}

	public static function on_location_deleted( $location ) {
		global $wpdb;

		if ( empty( $location->country_slug ) || empty( $location->region_slug ) || empty( $location->city_slug ) ) {
			return false;
		}

		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_LOCATION_SEO_TABLE . " WHERE country_slug = %s AND region_slug = %s AND city_slug = %s", array( $location->country_slug, $location->region_slug, $location->city_slug ) ) );
	}

	/**
	 * Get the canonical URL for the location page.
	 *
	 * @param bool $echo Whether or not to output the canonical element.
	 *
	 * @return string $canonical
	 */
	public static function wpseo_canonical( $canonical ) {
		return self::location_canonical( false );
	}

	/**
	 * Get the canonical URL for the location page.
	 *
	 * @param bool $echo Whether or not to output the canonical element.
	 *
	 * @return string $canonical
	 */
	public static function location_canonical( $echo = true ) {
		$canonical = geodir_get_location_link();

		if ( get_option( 'permalink_structure' ) != '' && is_string( $canonical ) && '' !== $canonical ) {
			$canonical = trailingslashit( $canonical );
		}

		if ( $echo === false ) {
			return $canonical;
		}

		if ( is_string( $canonical ) && '' !== $canonical ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical, null, 'other' ) . '" />' . "\n";
		}
	}

	/**
	 * Filter the Rank Math SEO plugin breadcrumb links to add location links.
	 *
	 * @param $crumbs
	 *
	 * @return mixed
	 */
	public static function rank_math_breadcrumb_links( $crumbs, $class_breadcrumbs = array() ) {
		global $geodirectory;

		$breadcrumb = array();

		$neighbourhood_active = (bool) GeoDir_Location_Neighbourhood::is_active();
		$show_countries 	  = geodir_get_option( 'lm_default_country' ) == 'default' && geodir_get_option( 'lm_hide_country_part' ) ? false : true;
		$show_regions 		  = geodir_get_option( 'lm_default_region' ) == 'default' && geodir_get_option( 'lm_hide_region_part' ) ? false : true;

		$gd_post_type   = geodir_get_current_posttype();
		$location_terms = geodir_get_current_location_terms( 'query_vars', $gd_post_type );

		// Maybe hide country
		if ( ! $show_countries ) {
			unset( $location_terms['country'] );
		}

		// Maybe hide region
		if ( ! $show_regions ) {
			unset( $location_terms['region'] );
		}

		$has_filter = has_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ) );

		if ( $has_filter ) {
			remove_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ) );
		}

		$location_link = get_post_type_archive_link( $gd_post_type );
		$base_location_link = geodir_get_location_link( 'base' );

		if ( $has_filter ) {
			add_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ), 10, 2 );
		}

		$location_link = trailingslashit( $location_link );
		$is_location = geodir_is_page( 'location' );

		if ( geodir_is_page( 'detail' ) || geodir_is_page( 'listing' ) || $is_location ) {
			if ( ! empty( $location_terms ) ) {
				if ( $is_location && ( $_post = get_post( (int) GeoDir_Compatibility::gd_page_id() ) ) ) {
					foreach ( $crumbs as $key => $crumb ) {
						if ( $crumb[1] == $base_location_link ) {
							$breadcrumb_title = RankMath\Helper::get_post_meta( 'breadcrumb_title', $_post->ID );
							if ( empty( $breadcrumb_title ) ) {
								$breadcrumb_title = strip_tags( $_post->post_title );
							}
							$crumbs[ $key ][0] = $breadcrumb_title;
						}
					}
				}

				$geodir_get_locations = function_exists( 'get_actual_location_name' ) ? true : false;

				foreach ( $location_terms as $key => $location_term ) {
					if ( $location_term != '' ) {

						$gd_location_link_text = preg_replace( '/-(\d+)$/', '', $location_term );
						$gd_location_link_text = preg_replace( '/[_-]/', ' ', $gd_location_link_text );
						$gd_location_link_text = geodir_utf8_ucfirst( $gd_location_link_text );

						$location_term_actual_country = $location_term_actual_region = $location_term_actual_city = $location_term_actual_neighbourhood = '';

						if ( $geodir_get_locations ) {
							if ( $key == 'country' ) {
								$location_term_actual_country = get_actual_location_name( 'country', $location_term, true );
							} else if ( $key == 'region' ) {
								$location_term_actual_region = get_actual_location_name( 'region', $location_term, true );
							} else if ( $key == 'city' ) {
								$location_term_actual_city = get_actual_location_name( 'city', $location_term, true );
							} else if ( $key == 'neighbourhood' ) {
								$location_term_actual_neighbourhood = get_actual_location_name( 'neighbourhood', $location_term, true );
							}
						}

						if ( $key == 'country' && !empty( $location_terms['country'] ) ) {
							$gd_location_link_text = $location_term_actual_country != '' ? $location_term_actual_country : $gd_location_link_text;

						} else if ( $key == 'region' && !empty( $location_terms['region'] ) ) {
							$gd_location_link_text = $location_term_actual_region != '' ? $location_term_actual_region : $gd_location_link_text;

						} else if ( $key == 'city' && !empty( $location_terms['city'] ) ) {
							$gd_location_link_text = $location_term_actual_city != '' ? $location_term_actual_city : $gd_location_link_text;

						} else if ( $key == 'neighbourhood' && !empty( $location_terms['city'] ) && $neighbourhood_active ) {
							$gd_location_link_text = $location_term_actual_neighbourhood != '' ? $location_term_actual_neighbourhood : $gd_location_link_text;
						}

						if ( get_option( 'permalink_structure' ) != '' ) {
							$location_link .= $location_term . '/';
						} else {
							$location_link .= "&$key=" . $location_term;
						}

						if ( $is_location ) {
							$breadcrumb_url = $base_location_link . ltrim( $location_link, "/" );
						} else {
							$breadcrumb_url = $location_link;;
						}

						if ( ! empty( $gd_location_link_text ) ) {
							$breadcrumb[] = array( $gd_location_link_text, $breadcrumb_url );
						}
					}
				}

				if ( is_array( $breadcrumb ) && count( $breadcrumb ) > 0 ) {
					$offset = apply_filters( 'rankmath_breadcrumb_links_offset', 2, $breadcrumb, $crumbs );
					$length = apply_filters( 'rankmath_breadcrumb_links_length', 0, $breadcrumb, $crumbs );

					array_splice( $crumbs, $offset, $length, $breadcrumb );
				}
			}
			
		}

		if ( ! empty( $geodirectory->location ) && ! empty( $geodirectory->location->type ) && in_array( $geodirectory->location->type, array( 'country', 'region', 'city', 'neighbourhood' ) ) && geodir_is_geodir_page() && ( $post_type = geodir_get_current_posttype() ) ) {
			$index = RankMath\Helper::get_settings( 'general.breadcrumbs_home' ) ? 1 : 0;

			if ( ! empty( $crumbs[ $index ] ) && ! empty( $crumbs[ $index ][1] ) && $crumbs[ $index ][1] == get_post_type_archive_link( $post_type ) ) {
				$crumbs[ $index ][1] = self::get_post_type_archive_link( $post_type, false );
			}
		}

		return $crumbs;
	}

	/**
	 * Filter the Yoast SEO plugin breadcrumb links to add location links.
	 *
	 * @param $crumbs
	 *
	 * @return mixed
	 */
	public static function wpseo_breadcrumb_links( $crumbs ) {
		global $geodirectory;

		$breadcrumb = array();

		$neighbourhood_active = (bool) GeoDir_Location_Neighbourhood::is_active();
		$show_countries 	  = geodir_get_option( 'lm_default_country' ) == 'default' && geodir_get_option( 'lm_hide_country_part' ) ? false : true;
		$show_regions 		  = geodir_get_option( 'lm_default_region' ) == 'default' && geodir_get_option( 'lm_hide_region_part' ) ? false : true;

		$gd_post_type   = geodir_get_current_posttype();
		$location_terms = geodir_get_current_location_terms( 'query_vars', $gd_post_type );

		// Maybe hide country
		if ( ! $show_countries ) {
			unset( $location_terms['country'] );
		}

		// Maybe hide region
		if ( ! $show_regions ) {
			unset( $location_terms['region'] );
		}

		$has_filter = has_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ) );

		if ( $has_filter ) {
			remove_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ) );
		}

		$location_link = get_post_type_archive_link( $gd_post_type );
		$base_location_link = geodir_get_location_link( 'base' );

		if ( $has_filter ) {
			add_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ), 10, 2 );
		}

		$location_link = trailingslashit( $location_link );
		$is_location = geodir_is_page( 'location' );

		if ( geodir_is_page( 'single' ) || geodir_is_page( 'listing' ) || $is_location ) {
			if ( ! empty( $location_terms ) ) {
				$geodir_get_locations = function_exists( 'get_actual_location_name' ) ? true : false;

				foreach ( $location_terms as $key => $location_term ) {
					if ( $location_term != '' ) {

						$gd_location_link_text = preg_replace( '/-(\d+)$/', '', $location_term );
						$gd_location_link_text = preg_replace( '/[_-]/', ' ', $gd_location_link_text );
						$gd_location_link_text = geodir_utf8_ucfirst( $gd_location_link_text );

						$location_term_actual_country = $location_term_actual_region = $location_term_actual_city = $location_term_actual_neighbourhood = '';

						if ( $geodir_get_locations ) {
							if ( $key == 'country' ) {
								$location_term_actual_country = get_actual_location_name( 'country', $location_term, true );
							} else if ( $key == 'region' ) {
								$location_term_actual_region = get_actual_location_name( 'region', $location_term, true );
							} else if ( $key == 'city' ) {
								$location_term_actual_city = get_actual_location_name( 'city', $location_term, true );
							} else if ( $key == 'neighbourhood' ) {
								$location_term_actual_neighbourhood = get_actual_location_name( 'neighbourhood', $location_term, true );
							}
						}

						if ( $key == 'country' && !empty( $location_terms['country'] ) ) {
							$gd_location_link_text = $location_term_actual_country != '' ? $location_term_actual_country : $gd_location_link_text;

						} else if ( $key == 'region' && !empty( $location_terms['region'] ) ) {
							$gd_location_link_text = $location_term_actual_region != '' ? $location_term_actual_region : $gd_location_link_text;

						} else if ( $key == 'city' && !empty( $location_terms['city'] ) ) {
							$gd_location_link_text = $location_term_actual_city != '' ? $location_term_actual_city : $gd_location_link_text;

						} else if ( $key == 'neighbourhood' && !empty( $location_terms['city'] ) && $neighbourhood_active ) {
							$gd_location_link_text = $location_term_actual_neighbourhood != '' ? $location_term_actual_neighbourhood : $gd_location_link_text;
						}

						if ( get_option( 'permalink_structure' ) != '' ) {
							$location_link .= $location_term . '/';
						} else {
							$location_link .= "&$key=" . $location_term;
						}

						if ( $is_location ) {
							$breadcrumb_url = $base_location_link . ltrim( $location_link, "/" );
						} else {
							$breadcrumb_url = $location_link;;
						}

						if ( ! empty( $gd_location_link_text ) ) {
							$breadcrumb[] = array(
								'text' => $gd_location_link_text,
								'url' => $breadcrumb_url,
							);
						}
					}
				}

				$offset = apply_filters('wpseo_breadcrumb_links_offset', 2, $breadcrumb, $crumbs);
				$length = apply_filters('wpseo_breadcrumb_links_length', 0, $breadcrumb, $crumbs);

				if ( is_array( $breadcrumb ) && count( $breadcrumb ) > 0 ) {
					array_splice( $crumbs, $offset, $length, $breadcrumb );
				}
			}

			if ( geodir_is_page( 'single' ) && ! empty( $crumbs ) ) {
				foreach ( $crumbs as $i => $crumb ) {
					if ( ! empty( $crumb['term_id'] ) && ! empty( $crumb['url'] ) ) {
						$crumbs[ $i ]['url'] = get_term_link( (int) $crumb['term_id'] );
					}
				}
			}
		}

		return $crumbs;
	}

	/**
	 * Filter the yoast post_type link to remove any location info.
	 *
	 * @param $link_info
	 * @param $index
	 * @param $crumbs
	 *
	 * @return mixed
	 */
	public static function wpseo_breadcrumb_pt_link($link_info, $index, $crumbs ){

		if ( $index == 1 && isset($crumbs[1]['ptarchive'])  && (geodir_is_page( 'detail' ) || geodir_is_page( 'listing' )) ) {
			global $geodirectory;
			$has_filter = has_filter( 'post_type_archive_link', array($geodirectory->permalinks,'post_type_archive_link') );

			if($has_filter){
				remove_filter( 'post_type_archive_link', array($geodirectory->permalinks,'post_type_archive_link') );
			}

			$location_link = get_post_type_archive_link( $crumbs[1]['ptarchive']);
			if($location_link ){
				$link_info['url'] = $location_link;
			}


			if($has_filter){
				add_filter( 'post_type_archive_link', array($geodirectory->permalinks,'post_type_archive_link'), 10, 2 );
			}
		}

		return $link_info;
	}

	/**
	 * Filter pre replace location variables.
	 *
	 * @since 2.0.0.24
	 *
	 * @param string $text Meta text.
	 * @param array $location_array The array of location variables.
	 * @param string $gd_page GeoDirectory page.
	 * @param string $sep The separator.
	 * @return string Filtered meta text.
	 */
	public static function pre_replace_location_variables( $text, $location_array = array(), $gd_page = '', $sep = '' ) {
		$text = self::pre_replace_variable( $text, $gd_page );

		return $text;
	}

	/**
	 * Filter pre replace variable.
	 *
	 * @since 2.0.0.24
	 *
	 * @param string $text Meta text.
	 * @param string $gd_page GeoDirectory page.
	 * @return string Filtered meta text.
	 */
	public static function pre_replace_variable( $text, $gd_page = '' ) {
		if ( ( strpos( $text, '%%search' ) !== false || strpos( $text, 'location' ) !== false ) && geodir_is_page( 'search' ) ) {
			$near = ! empty( $_REQUEST['snear'] ) ? sanitize_text_field( $_REQUEST['snear'] ) : '';
			if ( empty( $near ) && ! empty( $_REQUEST['near'] ) ) {
				$near = sanitize_text_field( $_REQUEST['near'] );
			}

			if ( ( ! empty( $_REQUEST['country'] ) || ! empty( $_REQUEST['region'] ) || ! empty( $_REQUEST['city'] ) || ! empty( $_REQUEST['neighbourhood'] ) ) && empty( $near ) && strpos( $text, 'location_single%%' ) === false && strpos( $text, '%%in_location' ) === false ) {
				$text = str_replace( '%%search_near_term%%', '%%location_single%%', $text );
				$text = str_replace( '%%search_near%%', '%%in_location_single%%', $text );
			} else if ( ! ( ! empty( $_REQUEST['country'] ) || ! empty( $_REQUEST['region'] ) || ! empty( $_REQUEST['city'] ) || ! empty( $_REQUEST['neighbourhood'] ) ) && ! empty( $near ) && strpos( $text, '%%search_near_term%%' ) === false && strpos( $text, '%%search_near%%' ) === false ) {
				$text = str_replace( '%%location%%', '%%search_near_term%%', $text );
				$text = str_replace( '%%location_single%%', '%%search_near_term%%', $text );
				$text = str_replace( '%%in_location%%', '%%search_near%%', $text );
				$text = str_replace( '%%in_location_single%%', '%%search_near%%', $text );
			}
		}

		return $text;
	}

	/**
	 * Get location image tag.
	 *
	 * @since 2.0.0.25
	 *
	 * @param object $location Location object.
	 * @param array $args Location args.
	 * @return string Image tag.
	 */
	public static function get_image_tag( $location, $args = array() ) {
		$image = '';

		if ( empty( $args['what'] ) ) {
			return $image;
		}

		$defaults = array(
			'default_image' => '',
			'image_size' => 'medium_large',
			'image_class' => '',
		);
		$args = wp_parse_args( (array) $args, $defaults );

		if ( $args['default_image'] === '' ) {
			$args['default_image'] = 'data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw=='; // Default image
		}

		$value = ! empty( $location->{$args['what'] . '_slug'} ) ? $location->{$args['what'] . '_slug'} : '';
		$country_slug = ! empty( $location->country_slug ) ? $location->country_slug : '';
		$region_slug = ! empty( $location->region_slug ) ? $location->region_slug : '';

		$seo = GeoDir_Location_SEO::get_seo_by_slug( $value, $args['what'], $country_slug, $region_slug );

		$image = '';
		$image_meta = array();
		if ( ! empty( $seo ) && ! empty( $seo->image ) ) {
			$attachment_attr = ! empty( $args['image_class'] ) ? array( 'class' => $args['image_class'] ) : array(); 
			$image = wp_get_attachment_image( $seo->image, $args['image_size'], false, $attachment_attr );
			$image_meta = wp_get_attachment_metadata( $seo->image );
		} elseif ( ! empty( $args['fallback_image'] ) ) {
			$params = array(
				'country' => ! empty( $location->country ) ? $location->country : ''
			);
			if ( $args['what'] == 'region' || $args['what'] == 'city' || $args['what'] == 'neighbourhood' ) {
				$params['region'] = ! empty( $location->region ) ? $location->region : '';

				if ( $args['what'] == 'city' || $args['what'] == 'neighbourhood' ) {
					$params['city'] = ! empty( $location->city ) ? $location->city : '';
				}

				if ( $args['what'] == 'neighbourhood' ) {
					$params['neighbourhood'] = ! empty( $location->neighbourhood_slug ) ? $location->neighbourhood_slug : ( ! empty( $location->neighbourhood ) ? $location->neighbourhood : '' );
				}
			}
			$attachment = self::get_post_attachment( $params );

			if ( ! empty( $attachment ) ) {
				$image = geodir_get_image_tag( $attachment, $args['image_size'] );
				if ( ! empty( $args['image_class'] ) ) {
					$image = str_replace( ' class="', ' class="' . esc_attr( $args['image_class'] ) . ' ', $image );
				}
				$image_meta = ! empty( $attachment->metadata ) ? maybe_unserialize( $attachment->metadata ) : array();
			}
		}

		if ( $image ) {
			$image = wp_image_add_srcset_and_sizes( $image, $image_meta , 0 );
			$image = geodir_image_tag_ajaxify( $image );
		}

		if ( empty( $image ) && ! empty( $args['default_image'] ) ) {
			$image = '<img src="' . esc_attr( $args['default_image'] ) . '" class="gd-location-image-default">';
		}

		return apply_filters( 'geodir_location_seo_image_tag', $image, $location, $args );
	}

	public static function get_post_attachment( $_args = array() ) {
		global $wpdb;

		$defaults = array(
			'post_type' => array(),
			'country' => '',
			'region' => '',
			'city' => '',
			'neighbourhood' => '',
		);
		$args = wp_parse_args( (array) $_args, $defaults );

		$args = apply_filters( 'geodir_location_seo_post_attachment_args', $args, $_args );

		if ( ! is_array( $args['post_type'] ) ) {
			$args['post_type'] = $args['post_type'] != '' ? explode( ',', $args['post_type'] ) : array();
		}

		$where = array();
		if ( ! empty( $args['country'] ) ) {
			$where[] = $wpdb->prepare( "pd.country = %s", $args['country'] );
		}

		if ( ! empty( $args['region'] ) ) {
			$where[] = $wpdb->prepare( "pd.region = %s", $args['region'] );
		}

		if ( ! empty( $args['city'] ) ) {
			$where[] = $wpdb->prepare( "pd.city = %s", $args['city'] );
		}

		if ( ! empty( $args['neighbourhood'] ) ) {
			$where[] = $wpdb->prepare( "pd.neighbourhood = %s", $args['neighbourhood'] );
		}

		$where = ! empty( $where ) ? "AND " . implode( " AND ", $where ) : '';

		$post_types = ! empty( $args['post_type'] ) ? $args['post_type'] : geodir_get_posttypes();

		$attachments = array();

		foreach ( $post_types as $post_type ) {
			if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				continue;
			}

			$table = geodir_db_cpt_table( $post_type );

			$sql = "SELECT a.* FROM `" . GEODIR_ATTACHMENT_TABLE . "` AS a LEFT JOIN `{$wpdb->posts}` AS p ON p.ID = a.post_id LEFT JOIN `{$table}` AS pd ON pd.post_id = a.post_id WHERE p.post_status = 'publish' AND a.type = 'post_images' AND a.is_approved != '0' {$where} ORDER BY a.featured DESC, a.menu_order ASC, a.ID DESC LIMIT 1";

			$sql = apply_filters( 'geodir_location_seo_post_type_attachment_sql', $sql, $post_type, $args );

			$row = $wpdb->get_row( $sql );

			if ( ! empty( $row ) ) {
				$attachments[ strtotime( $row->date_gmt ) ] = $row;
			}
		}

		$attachment = array();
		if ( ! empty( $attachments ) ) {
			if ( count( $attachments ) > 1 ) {
				arsort( $attachments );
			}

			$attachment = reset( $attachments );
		}

		return apply_filters( 'geodir_location_seo_post_attachment', $attachment, $args );
	}

	/**
	 * Add cpt_desc column in location seo table.
	 *
	 * @since 2.0.1.0
	 *
	 * @return void.
	 */
	public static function check_column_cpt_desc() {
		geodir_add_column_if_not_exist( GEODIR_LOCATION_SEO_TABLE, 'cpt_desc', "longtext NOT NULL" );
		geodir_add_column_if_not_exist( GEODIR_NEIGHBOURHOODS_TABLE, 'cpt_desc', "longtext NOT NULL" );
	}

	/**
	 * filter location + cpt description.
	 *
	 * @since 2.0.1.0
	 *
	 */
	public static function filter_cpt_description( $description, $key, $post_type, $post_type_obj, $instance ) {
		if ( $key == 'description' && ( $_description = self::get_cpt_description( $post_type ) ) ) {
			$description = $_description;
		}

		return $description;
	}

	/**
	 * Get location + cpt description.
	 *
	 * @since 2.0.1.0
	 *
	 */
	public static function get_cpt_description( $post_type ) {
		$description = '';

		if ( GeoDir_Post_types::supports( $post_type, 'location' ) && ( $seo = self::get_location_seo() ) ) {
			if ( ! empty( $seo ) && ! empty( $seo->cpt_desc ) ) {
				$cpt_desc = json_decode( $seo->cpt_desc, true );

				if ( ! empty( $cpt_desc ) && is_array( $cpt_desc ) && ! empty( $cpt_desc[ $post_type ] ) ) {
					$_description = trim( stripslashes( $cpt_desc[ $post_type ] ) );

					if ( $_description != '' ) {
						$description = __( $_description, 'geodirectory' );
					}
				}
			}
		}

		return apply_filters( 'geodir_location_cpt_description', $description, $post_type );
	}

	/**
	 * Retrieve term link for GeoDirectory category/tag.
	 *
	 * @since 2.0.1.14
	 *
	 * @param WP_Term|int|string $term The term object, ID, or slug whose link will be retrieved.
	 * @param bool               $location_filter True to apply location filter.
	 * @return string Term link.
	 */
	public static function get_term_link( $term, $location_filter = true ) {
		global $geodirectory;

		$has_filter = ! $location_filter && ! empty( $geodirectory->permalinks ) && has_filter( 'term_link', array( $geodirectory->permalinks, 'term_url' ) );

		if ( $has_filter ) {
			remove_filter( 'term_link', array( $geodirectory->permalinks, 'term_url' ) );
		}

		$link = get_term_link( $term );

		if ( is_wp_error( $term ) ) {
			$link = '';
		}

		if ( $has_filter ) {
			add_filter( 'term_link', array( $geodirectory->permalinks, 'term_url' ), 10, 3 );
		}

		return $link;
	}

	/**
	 * Retrieve post type archive link.
	 *
	 * @since 2.0.1.2
	 *
	 * @param string $post_type The post type.
	 * @param bool $location_filter True to apply location filter.
	 * @return string Post type archive link.
	 */
	public static function get_post_type_archive_link( $post_type, $location_filter = true ) {
		global $geodirectory;

		$has_filter = ! $location_filter && ! empty( $geodirectory->permalinks ) && has_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ) );

		if ( $has_filter ) {
			remove_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ) );
		}

		$link = get_post_type_archive_link( $post_type );

		if ( $has_filter ) {
			add_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ), 10, 2 );
		}

		return $link;
	}

	public static function wp_sitemaps_init() {
		if ( ! function_exists( 'wp_register_sitemap_provider' ) ) {
			return;
		}

		// Register location sitemaps.
		$provider = new GeoDir_Location_Sitemaps_Locations();

		wp_register_sitemap_provider( 'geodirlocations', $provider );
	}

	public static function rank_math_sitemap_providers( $providers ) {
		$types = self::rank_math_sitemap_types();

		if ( ! empty( $types ) ) {
			$providers['gd_location'] = new GeoDir_Location_Rank_Math_Sitemap();
		}

		return $providers;
	}

	/**
	 * Get location types that are public and not set to noindex.
	 *
	 * @return array All the accessible location types.
	 */
	public static function rank_math_sitemap_types() {
		$types = array();

		$sitemap_types = geodir_get_option( 'lm_rankmath_sitemap_types' );

		if ( empty( $sitemap_types ) ) {
			return $types;
		}

		foreach ( $sitemap_types as $type ) {
			$types[ 'gd_location_' . $type ] = $type;
		}

		return $types;
	}

	/**
	 * Get post types that are public and not set to noindex.
	 *
	 * @return array All the accessible post types.
	 */
	public static function rank_math_sitemap_cpts() {
		$types = array();

		$sitemap_types = self::rank_math_sitemap_types();
		if ( ! empty( $sitemap_types ) ) {
			$sitemap_types = array_values( $sitemap_types );
		}
		$sitemap_cpts = geodir_get_option( 'lm_rankmath_sitemap_cpts' );

		if ( empty( $sitemap_types ) || empty( $sitemap_cpts ) ) {
			return $types;
		}

		foreach ( $sitemap_cpts as $cpt ) {
			foreach ( $sitemap_types as $type ) {
				$types[ $cpt . '-' . $type ] = $type;
			}
		}

		return $types;
	}

	/**
	 * Get taxonomies that are public and not set to noindex.
	 *
	 * @return array All the accessible taxonomies.
	 */
	public static function rank_math_sitemap_tax() {
		$types = array();

		$sitemap_types = self::rank_math_sitemap_types();
		if ( ! empty( $sitemap_types ) ) {
			$sitemap_types = array_values( $sitemap_types );
		}
		$sitemap_tax = geodir_get_option( 'lm_rankmath_sitemap_tax' );

		if ( empty( $sitemap_types ) || empty( $sitemap_tax ) ) {
			return $types;
		}

		foreach ( $sitemap_tax as $tax ) {
			foreach ( $sitemap_types as $type ) {
				$types[ $tax . '-' . $type ] = $type;
			}
		}

		return $types;
	}

	public static function get_cpt_url( $post_type, $args = array() ) {
		global $wp, $geodirectory, $gd_post_type_archive_links;

		$defaults = array(
			'country' => '',
			'region' => '',
			'city' => '',
			'neighbourhood' => ''
		);

		$args = wp_parse_args( (array) $args, $defaults );

		if ( ! empty( $gd_post_type_archive_links ) && isset( $gd_post_type_archive_links[ $post_type ] ) ) {
			unset( $gd_post_type_archive_links[ $post_type ] );
		}

		$type = '';
		$location = array();
		if ( ! empty( $args['neighbourhood'] ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$type = 'neighbourhood';
			$location = GeoDir_Location_Neighbourhood::get_info_by_slug( $args['neighbourhood'] );
		} elseif ( ! empty( $args['city'] ) ) {
			$type = 'city';
			$location = GeoDir_Location_City::get_info_by_name( $args['city'], $args['country'], $args['region'] );
		} elseif ( ! empty( $args['region'] ) ) {
			$type = 'region';
			$location = GeoDir_Location_Region::get_info_by_name( $args['region'], $args['country'] );
		} elseif ( ! empty( $args['country'] ) ) {
			$type = 'country';
			$location = GeoDir_Location_Country::get_info_by_name( $args['country'] );
		}

		if ( empty( $location ) ) {
			$link = self::get_post_type_archive_link( $post_type, false );

			if ( is_wp_error( $link ) ) {
				$link = '';
			}

			return $link;
		}

		$old_wp = $wp;
		$old_geodirectory = $geodirectory;

		if ( ! empty( $location->country_slug ) ) {
			$wp->query_vars['country'] = $location->country_slug;
		} else {
			$wp->query_vars['country'] = '';
		}

		if ( ! empty( $location->region_slug ) && in_array( $type, array( 'region', 'city', 'neighbourhood' ) ) ) {
			$wp->query_vars['region'] = $location->region_slug;
		} else {
			$wp->query_vars['region'] = '';
		}

		if ( ! empty( $location->city_slug ) && in_array( $type, array( 'city', 'neighbourhood' ) ) ) {
			$wp->query_vars['city'] = $location->city_slug;
		} else {
			$wp->query_vars['city'] = '';
		}

		if ( ! empty( $location->neighbourhood ) && ! empty( $location->slug ) && $type == 'neighbourhood' ) {
			$wp->query_vars['neighbourhood'] = $location->slug;
		} else {
			$wp->query_vars['neighbourhood'] = '';
		}

		$geodirectory->location->country_slug = '';
		$geodirectory->location->region_slug = '';
		$geodirectory->location->city_slug = '';
		$geodirectory->location->neighbourhood_slug = '';
		$geodirectory->location->set_current();

		$link = get_post_type_archive_link( $post_type );

		if ( is_wp_error( $link ) ) {
			$link = '';
		}

		$wp = $old_wp;
		$geodirectory = $old_geodirectory;

		return $link;
	}

	public static function get_term_url( $term_id, $taxonomy, $args = array() ) {
		global $wp, $geodirectory;

		$defaults = array(
			'country' => '',
			'region' => '',
			'city' => '',
			'neighbourhood' => ''
		);

		$args = wp_parse_args( (array) $args, $defaults );

		$type = '';
		$location = array();
		if ( ! empty( $args['neighbourhood'] ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$type = 'neighbourhood';
			$location = GeoDir_Location_Neighbourhood::get_info_by_slug( $args['neighbourhood'] );
		} elseif ( ! empty( $args['city'] ) ) {
			$type = 'city';
			$location = GeoDir_Location_City::get_info_by_name( $args['city'], $args['country'], $args['region'] );
		} elseif ( ! empty( $args['region'] ) ) {
			$type = 'region';
			$location = GeoDir_Location_Region::get_info_by_name( $args['region'], $args['country'] );
		} elseif ( ! empty( $args['country'] ) ) {
			$type = 'country';
			$location = GeoDir_Location_Country::get_info_by_name( $args['country'] );
		}

		if ( empty( $location ) ) {
			return self::get_term_link( (int) $term_id, false );
		}

		$old_wp = $wp;
		$old_geodirectory = $geodirectory;

		if ( ! empty( $location->country_slug ) ) {
			$wp->query_vars['country'] = $location->country_slug;
		} else {
			$wp->query_vars['country'] = '';
		}

		if ( ! empty( $location->region_slug ) && in_array( $type, array( 'region', 'city', 'neighbourhood' ) ) ) {
			$wp->query_vars['region'] = $location->region_slug;
		} else {
			$wp->query_vars['region'] = '';
		}

		if ( ! empty( $location->city_slug ) && in_array( $type, array( 'city', 'neighbourhood' ) ) ) {
			$wp->query_vars['city'] = $location->city_slug;
		} else {
			$wp->query_vars['city'] = '';
		}

		if ( ! empty( $location->neighbourhood ) && ! empty( $location->slug ) && $type == 'neighbourhood' ) {
			$wp->query_vars['neighbourhood'] = $location->slug;
		} else {
			$wp->query_vars['neighbourhood'] = '';
		}

		$geodirectory->location->country_slug = '';
		$geodirectory->location->region_slug = '';
		$geodirectory->location->city_slug = '';
		$geodirectory->location->neighbourhood_slug = '';
		$geodirectory->location->set_current();

		$link = get_term_link( (int) $term_id, $taxonomy );

		if ( is_wp_error( $link ) ) {
			$link = '';
		}

		$wp = $old_wp;
		$geodirectory = $old_geodirectory;

		return $link;
	}

	public static function get_sitemap_cpt_locations( $location_type, $post_type, $per_page = 0, $page = null ) {
		global $wpdb, $geodir_location_options;

		$results = array();

		if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			return $results;
		}

		$fields = '';
		$join_condition = '';
		$where = '';
		$group_by = '';

		switch ( $location_type ) {
			case 'country_region':
			case 'region':
				$fields = "pd.country, pd.region";
				$group_by = "pd.country, pd.region";
			break;
			case 'country_city':
				$fields = "pd.country, pd.city";
				$group_by = "pd.country, pd.city";
			break;
			case 'region_city':
				$fields = "pd.region, pd.city";
				$group_by = "pd.region, pd.city";
			break;
			case 'country':
				$fields = "pd.country";
				$group_by = "pd.country";
			break;
			case 'city':
				$fields = "pd.country, pd.region, pd.city";
				$group_by = "pd.country, pd.region, pd.city";
			break;
			case 'neighbourhood':
				$fields = "pd.country, pd.region, pd.city, pd.neighbourhood";
				$group_by = "pd.city, pd.neighbourhood";
				$where .= " AND pd.neighbourhood != ''";
			break;
		}
		
		if ( empty( $fields ) ) {
			return $results;
		}

		// Set global location options
		self::setup_global_options();

		$option_country = ! empty( $geodir_location_options['option_country'] ) ? $geodir_location_options['option_country'] : '';
		$option_region = ! empty( $geodir_location_options['option_region'] ) ? $geodir_location_options['option_region'] : '';
		$option_city = ! empty( $geodir_location_options['option_city'] ) ? $geodir_location_options['option_city'] : '';
		$default_location =!empty($gd_sitemap_global['default_location']) ? $gd_sitemap_global['default_location'] : '';

		$statuses = geodir_get_publish_statuses( array( 'post_type' => $post_type ) );

		if ( count( $statuses ) > 1 ) {
			$where .= " AND p.post_status IN( '" . implode( "', '", $statuses ) . "' )";
		} else {
			$where .= " AND p.post_status = '{$statuses[0]}'";
		}

		if ( $option_country == 'default' && ! empty( $default_location->country ) ) {
			$where .= $wpdb->prepare( " AND pd.country LIKE %s", $default_location->country );
		}

		if ( $option_region == 'default' && ! empty( $default_location->region ) ) {
			$where .= $wpdb->prepare( " AND pd.region LIKE %s", $default_location->region );
		}

		if ( $option_city == 'default' && ! empty( $default_location->city ) ) {
			$where .= $wpdb->prepare( " AND pd.city LIKE %s", $default_location->city );
		}

		$table = geodir_db_cpt_table( $post_type );

		$sql = "SELECT MAX( p.post_modified_gmt ) AS date_gmt, {$fields} FROM `{$table}` pd LEFT JOIN {$wpdb->posts} p ON p.ID = pd.post_id WHERE p.post_type = '" . $post_type . "' {$where} GROUP BY {$group_by} ORDER BY date_gmt DESC";

		if ( $per_page > 0 ) {
			if ( $page > 1 ) {
				$sql .= " LIMIT " . ( ( (int) $page - 1 ) * (int) $per_page ) . ", " . (int) $per_page;
			} else {
				$sql .= " LIMIT " . (int) $per_page;
			}
		}

		$results = $wpdb->get_results( $sql );

		return $results;
	}

	public static function get_sitemap_tax_locations( $location_type, $post_type, $taxonomy_type, $per_page = 0, $page = null ) {
		global $wpdb, $geodir_location_options;

		$results = array();

		if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			return $results;
		}

		$fields = '';
		$join_condition = '';
		$where = '';
		$group_by = '';

		switch ($location_type) {
			case 'country_region':
			case 'region':
				$fields = "pd.country, pd.region";
				$group_by = "tt.term_id, pd.country, pd.region";
			break;
			case 'country_city':
				$fields = "pd.country, pd.city";
				$group_by = "tt.term_id, pd.country, pd.city";
			break;
			case 'region_city':
				$fields = "pd.region, pd.city";
				$group_by = "tt.term_id, pd.region, pd.city";
			break;
			case 'country':
				$fields = "pd.country";
				$group_by = "tt.term_id, pd.country";
			break;
			case 'city':
			case 'full':
				$fields = "pd.country, pd.region, pd.city";
				$group_by = "tt.term_id, pd.country, pd.region, pd.city";
			break;
			case 'neighbourhood':
				$fields = "pd.country, pd.region, pd.city, pd.neighbourhood";
				$group_by = "tt.term_id, pd.city, pd.neighbourhood";
				$where .= " AND pd.neighbourhood != ''";
			break;
		}

		if ( empty( $fields ) ) {
			return $results;
		}

		// Set global location options
		self::setup_global_options();

		$option_country = ! empty( $geodir_location_options['option_country'] ) ? $geodir_location_options['option_country'] : '';
		$option_region = ! empty( $geodir_location_options['option_region'] ) ? $geodir_location_options['option_region'] : '';
		$option_city = ! empty( $geodir_location_options['option_city'] ) ? $geodir_location_options['option_city'] : '';
		$default_location =!empty($gd_sitemap_global['default_location']) ? $gd_sitemap_global['default_location'] : '';
		$taxonomy = $taxonomy_type == 'tag' ? $post_type . '_tags' : $post_type . 'category';

		$statuses = geodir_get_publish_statuses( array( 'post_type' => $post_type ) );

		if ( count( $statuses ) > 1 ) {
			$where .= " AND p.post_status IN( '" . implode( "', '", $statuses ) . "' )";
		} else {
			$where .= " AND p.post_status = '{$statuses[0]}'";
		}

		if ( $option_country == 'default' && ! empty( $default_location->country ) ) {
			$where .= $wpdb->prepare( " AND pd.country LIKE %s", $default_location->country );
		}

		if ( $option_region == 'default' && ! empty( $default_location->region ) ) {
			$where .= $wpdb->prepare( " AND pd.region LIKE %s", $default_location->region );
		}

		if ( $option_city == 'default' && ! empty( $default_location->city ) ) {
			$where .= $wpdb->prepare( " AND pd.city LIKE %s", $default_location->city );
		}

		$table = geodir_db_cpt_table( $post_type );

		$sql = "SELECT tt.term_id, MAX( p.post_modified_gmt ) AS date_gmt, {$fields} FROM `{$table}` pd LEFT JOIN `{$wpdb->posts}` p ON p.ID = pd.post_id LEFT JOIN `{$wpdb->term_relationships}` AS tr ON tr.object_id = p.ID LEFT JOIN `{$wpdb->term_taxonomy}` AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE p.post_type = '" . $post_type . "' AND tt.taxonomy = '{$taxonomy}' {$where} GROUP BY {$group_by} ORDER BY date_gmt DESC";

		if ( $per_page > 0 ) {
			if ( $page > 1 ) {
				$sql .= " LIMIT " . ( ( (int) $page - 1 ) * (int) $per_page ) . ", " . (int) $per_page;
			} else {
				$sql .= " LIMIT " . (int) $per_page;
			}
		}

		$results = $wpdb->get_results( $sql );

		return $results;
	}

	public static function setup_global_options() {
		global $geodirectory, $geodir_location_options;

		if ( ! empty( $geodir_location_options ) ) {
			return;
		}

		$geodir_location_options = array();
		$geodir_location_options['option_country'] = geodir_get_option( 'lm_default_country' );
		$geodir_location_options['option_region'] = geodir_get_option( 'lm_default_region' );
		$geodir_location_options['option_city'] = geodir_get_option( 'lm_default_city' );
		$geodir_location_options['hide_country'] = geodir_get_option( 'lm_hide_country_part' );
		$geodir_location_options['hide_region'] = geodir_get_option( 'lm_hide_region_part' );
		$geodir_location_options['default_location'] = $geodirectory->location->get_default_location();
	}

	/**
	 * Set SEO title/meta variables.
	 *
	 * @since 2.1.1.3
	 *
	 * @param array  $vars SEO variables.
	 * @param string $gd_page Current page.
	 * @return array Array of SEO variables.
	 */
	public static function filter_seo_variables( $vars, $gd_page ) {
		if ( ! empty( $vars ) && isset( $vars['%%in_location_city%%'] ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$offset = array_search( '%%in_location_city%%', array_keys( $vars ) ) + 1;
			$push_vars = array( 
				'%%location_neighbourhood%%' => __( 'The current viewing neighbourhood eg: West Philadelphia', 'geodirlocation' ),
				'%%in_location_neighbourhood%%' => __( 'The current viewing neighbourhood prefixed with `in` eg: in West Philadelphia', 'geodirlocation' ),
			);

			$vars = array_merge( array_slice( $vars, 0, $offset ), $push_vars, array_slice( $vars, $offset ) );
		}

		return $vars;
	}

	/**
	 * Filter the SEOPress plugin breadcrumb links to add location links.
	 *
	 * @since 2.3.9
	 *
	 * @param $crumbs
	 *
	 * @return mixed
	 */
	public static function seopress_pro_breadcrumbs_crumbs( $crumbs ) {
		global $geodirectory;

		$_crumbs = array();

		$neighbourhood_active = (bool) GeoDir_Location_Neighbourhood::is_active();
		$show_countries 	  = geodir_get_option( 'lm_default_country' ) == 'default' && geodir_get_option( 'lm_hide_country_part' ) ? false : true;
		$show_regions 		  = geodir_get_option( 'lm_default_region' ) == 'default' && geodir_get_option( 'lm_hide_region_part' ) ? false : true;

		$gd_post_type   = geodir_get_current_posttype();
		$location_terms = geodir_get_current_location_terms( 'query_vars', $gd_post_type );

		// Maybe hide country
		if ( ! $show_countries ) {
			unset( $location_terms['country'] );
		}

		// Maybe hide region
		if ( ! $show_regions ) {
			unset( $location_terms['region'] );
		}

		$has_filter = has_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ) );

		if ( $has_filter ) {
			remove_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ) );
		}

		$location_link = get_post_type_archive_link( $gd_post_type );
		$base_location_link = geodir_get_location_link( 'base' );

		if ( $has_filter ) {
			add_filter( 'post_type_archive_link', array( $geodirectory->permalinks, 'post_type_archive_link' ), 10, 2 );
		}

		$location_link = trailingslashit( $location_link );
		$is_location = geodir_is_page( 'location' );

		if ( ! empty( $location_terms ) && ! empty( $gd_post_type ) && ( geodir_is_page( 'single' ) || geodir_is_page('archive') || geodir_is_page('post_type') ) ) {
			$cpt_location_link = get_post_type_archive_link( $gd_post_type );

			foreach ( $crumbs as $i => $crumb ) {
				if ( ! empty( $crumb[1] ) && $crumb[1] == $cpt_location_link ) {
					$crumbs[ $i ][1] = $location_link;
				}
			}
		}

		if ( geodir_is_page( 'single' ) || geodir_is_page( 'listing' ) || $is_location ) {
			if ( ! empty( $location_terms ) ) {
				$geodir_get_locations = function_exists( 'get_actual_location_name' ) ? true : false;

				foreach ( $location_terms as $key => $location_term ) {
					if ( $location_term != '' ) {
						$gd_location_link_text = preg_replace( '/-(\d+)$/', '', $location_term );
						$gd_location_link_text = preg_replace( '/[_-]/', ' ', $gd_location_link_text );
						$gd_location_link_text = geodir_utf8_ucfirst( $gd_location_link_text );

						$location_term_actual_country = $location_term_actual_region = $location_term_actual_city = $location_term_actual_neighbourhood = '';

						if ( $geodir_get_locations ) {
							if ( $key == 'country' ) {
								$location_term_actual_country = get_actual_location_name( 'country', $location_term, true );
							} else if ( $key == 'region' ) {
								$location_term_actual_region = get_actual_location_name( 'region', $location_term, true );
							} else if ( $key == 'city' ) {
								$location_term_actual_city = get_actual_location_name( 'city', $location_term, true );
							} else if ( $key == 'neighbourhood' ) {
								$location_term_actual_neighbourhood = get_actual_location_name( 'neighbourhood', $location_term, true );
							}
						}

						if ( $key == 'country' && !empty( $location_terms['country'] ) ) {
							$gd_location_link_text = $location_term_actual_country != '' ? $location_term_actual_country : $gd_location_link_text;

						} else if ( $key == 'region' && !empty( $location_terms['region'] ) ) {
							$gd_location_link_text = $location_term_actual_region != '' ? $location_term_actual_region : $gd_location_link_text;

						} else if ( $key == 'city' && !empty( $location_terms['city'] ) ) {
							$gd_location_link_text = $location_term_actual_city != '' ? $location_term_actual_city : $gd_location_link_text;

						} else if ( $key == 'neighbourhood' && !empty( $location_terms['city'] ) && $neighbourhood_active ) {
							$gd_location_link_text = $location_term_actual_neighbourhood != '' ? $location_term_actual_neighbourhood : $gd_location_link_text;
						}

						if ( get_option( 'permalink_structure' ) != '' ) {
							$location_link .= $location_term . '/';
						} else {
							$location_link .= "&$key=" . $location_term;
						}

						if ( $is_location ) {
							$breadcrumb_url = $base_location_link . ltrim( $location_link, "/" );
						} else {
							$breadcrumb_url = $location_link;;
						}

						if ( ! empty( $gd_location_link_text ) ) {
							$_crumbs[] = array( $gd_location_link_text, $breadcrumb_url );
						}
					}
				}

				$offset = apply_filters( 'seopress_breadcrumb_links_offset', 2, $_crumbs, $crumbs );
				$length = apply_filters( 'seopress_breadcrumb_links_length', 0, $_crumbs, $crumbs );

				if ( is_array( $_crumbs ) && count( $_crumbs ) > 0 ) {
					array_splice( $crumbs, $offset, $length, $_crumbs );
				}
			}
		}

		return $crumbs;
	}
}

return new GeoDir_Location_SEO();