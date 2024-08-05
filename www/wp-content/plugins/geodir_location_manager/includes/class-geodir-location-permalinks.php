<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_Permalinks.
 *
 * @class    GeoDir_Location_Locations
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_Permalinks extends GeoDir_Permalinks{

	public function __construct() {
		// call parent constructor
		parent::__construct();

		// only add the location to urls if set to do so
		if ( geodir_get_option( 'lm_url_filter_archives' ) != 'disable' || GeoDir_Location_SEO::wpseo_is_sitemap_page( false, true ) ) {
			// term url filter
			add_filter('term_link', array($this,'term_url'), 10, 3);

			// post type url filter
			add_filter( 'post_type_archive_link', array($this,'post_type_archive_link'), 10, 2);
		}

		// term and CPT archive rewrite rules
		add_action('init', array( $this, 'term_archive_rewrite_rules'), 10, 0);

		// extra location page rewrite rules
		add_action('init', array( $this, 'extra_location_rewrite_rules'), 11,0);

		add_action( 'parse_request', array( $this, 'is_post_or_location') );

		add_filter('geodir_location_link_location_terms',array( $this, 'remove_hidden_location_terms'));

		// Prevent 404 page not found on 3rd party form submit with country/region/city/neighbourhood fields.
		add_action( 'geodir_location_skip_set_current', array( $this, 'skip_set_current' ), 1 );
		add_action( 'wp', array( $this, 'rescue_404' ), 1, 1 );
	}


	//@todo remove after testing
	public function query_vars( $qvars ) {
//		$qvars[] = 'custom_query_var';
		unset($qvars->country);
		$qvars->set('country', '');
		$qvars->set('name', 'buddakan');

		print_r($qvars);

		return $qvars;
	}


	function remove_hidden_location_terms($location_terms){
		$show_countries = geodir_get_option( 'lm_default_country' ) == 'default' && geodir_get_option( 'lm_hide_country_part' ) ? false : true;
		$show_regions = geodir_get_option( 'lm_default_region' ) == 'default' && geodir_get_option( 'lm_hide_region_part' ) ? false : true;

		if(!$show_countries && isset($location_terms['country'])){
			unset($location_terms['country']);
		}

		if(!$show_regions && isset($location_terms['region'])){
			unset($location_terms['region']);
		}

		return $location_terms;
	}

	/**
	 * If GD permalink structure is default then check if current request is GD post or a location.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	function is_post_or_location( $query ) {
		global $geodirectory;

		$post_type = ! empty( $query ) && ! empty( $query->query_vars['post_type'] ) ? $query->query_vars['post_type'] : '';
		if ( ! empty( $post_type ) && is_array( $post_type ) ) {
			$post_type = $post_type[0];
		}

		// Get the post_type slug, we need this to be dynamic
		$post_type_slug = ! empty( $post_type ) && ! empty( $geodirectory ) && ! empty( $geodirectory->settings['post_types'][ $post_type ] ) && ! empty( $geodirectory->settings['post_types'][ $post_type ]['rewrite']['slug'] ) ? $geodirectory->settings['post_types'][ $post_type ]['rewrite']['slug'] : false;

		$last_location_query_var = '';
		if ( isset( $query->query_vars['neighbourhood'] ) && GeoDir_Location_Neighbourhood::is_active() ) {
			$last_location_query_var = 'neighbourhood';
		} else if ( isset( $query->query_vars['city'] ) ) {
			$last_location_query_var = 'city';
		} else if ( isset( $query->query_vars['region'] ) ) {
			$last_location_query_var = 'region';
		} else if ( isset( $query->query_vars['country'] ) ) {
			$last_location_query_var = 'country';
		}

		// If permalink not default
		if ( isset( $query->query_vars['gd_is_geodir_page'] )
			 && $post_type
			 && $post_type_slug
			 && ! isset( $query->query_vars[ $post_type . 'category'] )
			 && ! empty( $geodirectory->settings['permalink_structure'] )
			 && $last_location_query_var
			 && self::is_post_slug( $query->query_vars['post_type'], $query->query_vars[ $last_location_query_var ] )
		) {
			// Backup & unset vars to match var count.
			$backup_vars = array();
			foreach ( $query->query_vars as $bkp_key => $bkp_value ) {
				if ( in_array( $bkp_key, array( 'feed', 'preview' ) ) ) {
					$backup_vars[ $bkp_key ] = $bkp_value;
					unset( $query->query_vars[ $bkp_key ] );
				}
			}

			$details_query_vars = array_filter( explode( "/", $geodirectory->settings['permalink_structure'] ) );
			$post_type = $query->query_vars['post_type'];
			$matched_query = "post_type=" . $post_type;

			$original_vars = array_values( $query->query_vars );
			$original_vars_keys = array_keys( $query->query_vars );
			$original_query_vars = $query->query_vars;
			$gd_query_vars = array();

			foreach ( $details_query_vars as $key => $details_query_var ) {
				if ( isset( $original_vars[ $key ] ) ) {
					if ( $details_query_var == '%category%' ) {
						$query->matched_query .= "&" . $post_type . "category=" . $original_vars[ $key ];
						unset( $original_query_vars[ $original_vars_keys[ $key ] ] );
						$gd_query_vars[ $post_type . "category" ] = $original_vars[ $key ];
					} else if ( $details_query_var == '%postname%' ) {
						$query->matched_query .= "&" . $post_type . "=" . $original_vars[ $key ];
						unset( $original_query_vars[ $original_vars_keys[ $key ] ] );
						$gd_query_vars['name'] = $original_vars[ $key ];
					} else if ( $details_query_var == '%country%' ) {
						$query->matched_query .= "&country=" . $original_vars[ $key ];
						unset( $original_query_vars[ $original_vars_keys[ $key ] ] );
						$gd_query_vars['country'] = $original_vars[ $key ];
					} else if ( $details_query_var == '%region%' ) {
						$query->matched_query .= "&region=" . $original_vars[ $key ];
						unset( $original_query_vars[ $original_vars_keys[ $key ] ] );
						$gd_query_vars['region'] = $original_vars[ $key ];
					} else if ( $details_query_var == '%city%' ) {
						$query->matched_query .= "&city=" . $original_vars[ $key ];
						unset( $original_query_vars[ $original_vars_keys[ $key ] ] );
						$gd_query_vars['city'] = $original_vars[ $key ];
					} else if ( $details_query_var == '%neighbourhood%' ) {
						$query->matched_query .= "&neighbourhood=" . $original_vars[ $key ];
						unset( $original_query_vars[ $original_vars_keys[ $key ] ] );
						$gd_query_vars['neighbourhood'] = $original_vars[ $key ];
					} else if ( $details_query_var == '%post_id%' ) {
						$query->matched_query .= "&p=" . $original_vars[ $key ];
						unset( $original_query_vars[ $original_vars_keys[ $key ] ] );
						$gd_query_vars['p'] = $original_vars[ $key ];
					}
				}
			}

			$query->query_vars = ! empty( $original_query_vars ) ? array_merge( $original_query_vars, $gd_query_vars ) : $gd_query_vars;

			// Assign query vars back.
			if ( ! empty( $backup_vars ) ) {
				foreach ( $backup_vars as $bkp_key => $bkp_value ) {
					$query->query_vars[ $bkp_key ] = $bkp_value;
				}
			}

			$query->matched_query = $matched_query;
		}

		// If permalink default, do we need to run the checks
		if ( isset( $query->query_vars['gd_is_geodir_page'] ) && empty( $geodirectory->settings['permalink_structure'] ) && isset( $query->query_vars['post_type'] ) && $post_type_slug && ( $query->matched_rule == '^' . $post_type_slug . '/([^/]*)/?$' || $query->matched_rule == '^' . $post_type_slug . '/([0-9]+)/?$' ) && in_array( $query->query_vars['post_type'], geodir_get_posttypes() ) ) {
			$show_country = geodir_get_option( 'lm_hide_country_part') ? false : true;
			$show_region = geodir_get_option( 'lm_hide_region_part') ? false : true;
			$show_city = geodir_get_option( 'lm_hide_city_part') ? false : true;

			$type = '';
			$post_type = '';

			if ( ( $show_country || $show_region || $show_city ) && GeoDir_Post_types::supports( $query->query_vars['post_type'], 'location' ) ) {
				$post_type = $query->query_vars['post_type'];

				if ( $show_country) {
					$type = 'country';
				} else if ( $show_region) {
					$type = 'region';
				} else if ( $show_city) {
					$type = 'city';
				}
			}

			// Check the current request matches the requirements
			if ( $post_type && $type && ! empty( $query->query_vars[ $type ] ) && $query->matched_query == "post_type=" . $post_type . "&" . $type . "=" . $query->query_vars[ $type ] ) {
				$slug = $query->query_vars[ $type ];

				// If its a location request but it should be a post request then alter the query vars.
				if ( $this->is_post_slug( $post_type, $slug ) ) {
					$query->matched_query = "post_type=" . $post_type . "&" . $post_type . "=" . $slug;
					$query->query_vars[ $post_type ] = $query->query_vars['name'] = $query->query_vars[ $type ];
					unset( $query->query_vars[ $type ] );
				}
			}
		}

		/**
		 * Fix permalink errors when location slugs contains urlencoded special characters.
		 * See: https://wpgeodirectory.com/support/topic/arabic-cities-permalinks-error/
		 */
		$vars = array( 'city', 'region', 'country', 'neighbourhood' );
		foreach ( $vars as $var ) {
			if ( ! empty( $query->query_vars[ $var ] ) ) {
				$query->query_vars[ $var ] = urldecode( $query->query_vars[ $var ] );
			}
		}

		return $query;
	}

	/**
	 * Check if a slug is a GD post slug.
	 *
	 * @param $post_type
	 * @param $slug
	 *
	 * @return null|string
	 */
	public function is_post_slug( $post_type, $slug ) {
		global $wpdb;
		// Prevent redirect loop. Ex: convert μουσείο to %ce%bc%ce%bf%cf%85%cf%83%ce%b5%ce%af%ce%bf.
		$slug = sanitize_title_for_query( $slug );
		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts p WHERE p.post_type = %s AND p.post_name = %s", $post_type, $slug ) );
	}

	/**
	 * Add the extra locations page rewrite rules.
	 */
	public function extra_location_rewrite_rules() {
		// Locations page
		$location_slug = $this->location_slug();
		$near_slug = $this->location_near_slug();
		$me_slug = $this->location_me_slug();

		// near me
		$this->add_rewrite_rule( "^" . $location_slug . "/" . $near_slug . "/" . $me_slug . "/?$", 'index.php?pagename=' . $location_slug . '&near=me', 'top' );
		$this->add_rewrite_rule( "^" . $location_slug . "/" . $near_slug . "/" . $me_slug . '/((\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?))/?$', 'index.php?pagename=' . $location_slug . '&near=me&latlon=$matches[1]', 'top' );
		$this->add_rewrite_rule( "^" . $location_slug . "/" . $near_slug . "/" . $me_slug . '/((\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?))/((\d+(\.\d+)?))/?$', 'index.php?pagename=' . $location_slug . '&near=me&latlon=$matches[1]&dist=$matches[6]', 'top' );

		// near gps
		$this->add_rewrite_rule( "^" . $location_slug . "/" . $near_slug . '/gps/((\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?))/?$', 'index.php?pagename=' . $location_slug . '&near=gps&latlon=$matches[1]', 'top' );
		$this->add_rewrite_rule( "^" . $location_slug . "/" . $near_slug . '/gps/((\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?))/((\d+(\.\d+)?))/?$', 'index.php?pagename=' . $location_slug . '&near=gps&latlon=$matches[1]&dist=$matches[6]', 'top' );
	}

	/**
	 * Filter the post type archive link to add current locations.
	 *
	 * @param $link
	 * @param $post_type
	 *
	 * @return string
	 */
	public function post_type_archive_link($link, $post_type){
		global $gd_post_type_archive_links,$wp_query;

		if( geodir_is_gd_post_type( $post_type) ){

			// check if its in the cache
			if(isset($gd_post_type_archive_links[$post_type])){
				//return $gd_post_type_archive_links[$post_type];
			}

			$show_country = geodir_get_option( 'lm_hide_country_part') ? false : true;
			$show_region = geodir_get_option( 'lm_hide_region_part') ? false : true;
			$show_city = geodir_get_option( 'lm_hide_city_part') ? false : true;
			$show_hood = GeoDir_Location_Neighbourhood::is_active() ? true : false;

			if( ( $show_country || $show_region || $show_city || $show_hood ) && GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				global $geodirectory;
				// current location
				$current_location = $geodirectory->location;

				$location_terms = array();
				if($show_country){$location_terms['country'] = $current_location->country_slug;}
				if($show_region){$location_terms['region'] = $current_location->region_slug;}
				if($show_city){$location_terms['city'] = $current_location->city_slug;}
				if($show_hood){$location_terms['neighbourhood'] = $current_location->neighbourhood_slug;}

				$route = implode("/", array_filter($location_terms));
				$route = $route && $this->is_slash() ? trailingslashit($route) : $route;

				// trailingslashit if needed
				$link = $route  ? trailingslashit($link) : $link;

				$link .= $route;
			}

			// only cache is the query has been set
			if(!empty($wp_query->query_vars)){
				// cache the link
				$gd_post_type_archive_links[$post_type] = $link;
			}

		}

		return $link;
	}

	/**
	 * Add CPT and term rewrite rules for locations.
	 */
	public function term_archive_rewrite_rules(){
		global $wp_rewrite;

		$feedindex = $wp_rewrite->index;

		// Build a regex to match the feed section of URLs, something like (feed|atom|rss|rss2)/?
		$feedregex2 = '';
		foreach ( (array) $wp_rewrite->feeds as $feed_name ) {
			$feedregex2 .= $feed_name . '|';
		}
		$feedregex2 = '(' . trim( $feedregex2, '|' ) . ')/?$';

		/*
		 * $feedregex is identical but with /feed/ added on as well, so URLs like <permalink>/feed/atom
		 * and <permalink>/atom are both possible
		 */
		$feedregex = $wp_rewrite->feed_base . '/' . $feedregex2;
		$feedbase = $wp_rewrite->feed_base;

		$show_country = geodir_get_option( 'lm_hide_country_part') ? false : true;
		$show_region = geodir_get_option( 'lm_hide_region_part') ? false : true;
		$show_city = geodir_get_option( 'lm_hide_city_part') ? false : true;
		$show_neighbourhood = geodir_get_option( 'lm_enable_neighbourhoods') ? true : false;
		$post_types = geodir_get_posttypes( 'array' );

		if( ( $show_country || $show_region || $show_city ) && !empty( $post_types ) ){
			foreach($post_types as $post_type => $cpt){
				$rewrite_slug = isset($cpt['rewrite']['slug']) ? $cpt['rewrite']['slug'] : '';
				$tag_base = geodir_get_option('permalink_tag_base','tags');
				$cat_base = geodir_get_option('permalink_category_base','category') ? trailingslashit(geodir_get_option('permalink_category_base','category')) : '';

				$location_terms = array();

				if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					if($show_country){$location_terms[] = 'country';}
					if($show_region){$location_terms[] = 'region';}
					if($show_city){$location_terms[] = 'city';}
					if($show_neighbourhood){$location_terms[] = 'neighbourhood';}
				}

				// base args
				$redirect   = 'index.php?post_type='.$post_type."&";
				$after      = 'top'; // top/bottom
				$match      = 1;
				$query_vars = array();
				$cpt_slug = "^".$rewrite_slug;


				// add location terms
				foreach ( $location_terms as $tag ) {
					$query_vars[] = trim( $tag ) . '=$matches[' . $match . ']';
					$match ++;
				}


				####################
				## category rules ##
				####################
				// add the base vars
				$cat_query_vars = array($post_type.'category=$matches[1]');
				$cat_match = 2;
				// add location vars
				foreach ( $location_terms as $tag ) {
					$cat_query_vars[] = trim( $tag ) . '=$matches[' . $cat_match . ']';
					$cat_match++;
				}

				foreach($cat_query_vars  as $cat_query_var) {
					$cat_match = count( $cat_query_vars );

					// Add rule for /feed/(feed|atom|rss|rss2|rdf).
					$this->add_rewrite_rule(
						$cpt_slug . '/' . $cat_base . implode( "", array_fill( 0, count( $cat_query_vars ) , '([^/]*)/' ) ) . $feedregex,
						$redirect . implode( '&', $cat_query_vars ).'&feed=$matches['.( $cat_match + 1 ).']',
						$after
					);
					// Add rule for /(feed|atom|rss|rss2|rdf) (see comment near creation of $feedregex).
					$this->add_rewrite_rule(
						$cpt_slug . '/' . $cat_base . implode( "", array_fill( 0, count( $cat_query_vars ) , '([^/]*)/' ) ) . $feedregex2,
						$redirect . implode( '&', $cat_query_vars ).'&feed=$matches['.( $cat_match + 1 ).']',
						$after
					);
					// paged
					$this->add_rewrite_rule(
						$cpt_slug . '/' . $cat_base . implode( "", array_fill( 0, count( $cat_query_vars ) , '([^/]*)/' ) ) . 'page/?([0-9]{1,})/?$',
						$redirect . implode( '&', $cat_query_vars ).'&paged=$matches['.( $cat_match + 1 ).']',
						$after
					);
					// non paged
					$this->add_rewrite_rule(
						$cpt_slug . '/' . $cat_base . implode( "", array_fill( 0, count( $cat_query_vars )  , '([^/]*)/' ) ) . '?$',
						$redirect . implode( '&', $cat_query_vars ) ,
						$after
					);

					do_action( 'geodir_location_permalinks_cat_rewrite_rule', $post_type, $rewrite_slug, $cat_base, $redirect, $cat_match, $cat_query_vars, $after, $this );
					// remove one from the array
					array_pop( $cat_query_vars );
				}


				####################
				###  tag rules  ####
				####################
				// add the base vars
				$tag_query_vars = array($post_type.'_tags=$matches[1]');
				$tag_match = 2;
				// add location vars
				foreach ( $location_terms as $tag ) {
					$tag_query_vars[] = trim( $tag ) . '=$matches[' . $tag_match . ']';
					$tag_match++;
				}

				foreach($tag_query_vars  as $tag_query_var) {
					$tag_match = count( $tag_query_vars );

					// Add rule for /feed/(feed|atom|rss|rss2|rdf).
					$this->add_rewrite_rule(
						$cpt_slug . '/' . trailingslashit($tag_base) . implode( "", array_fill( 0, count( $tag_query_vars ) , '([^/]*)/' ) ) . $feedregex,
						$redirect . implode( '&', $tag_query_vars ).'&feed=$matches['.($tag_match + 1).']',
						$after
					);
					// Add rule for /(feed|atom|rss|rss2|rdf) (see comment near creation of $feedregex).
					$this->add_rewrite_rule(
						$cpt_slug . '/' . trailingslashit($tag_base) . implode( "", array_fill( 0, count( $tag_query_vars ) , '([^/]*)/' ) ) . $feedregex2,
						$redirect . implode( '&', $tag_query_vars ).'&feed=$matches['.($tag_match + 1).']',
						$after
					);
					// paged
					$this->add_rewrite_rule(
						$cpt_slug . '/' . trailingslashit($tag_base) . implode( "", array_fill( 0, count( $tag_query_vars ) , '([^/]*)/' ) ) . 'page/?([0-9]{1,})/?$',
						$redirect . implode( '&', $tag_query_vars ).'&paged=$matches['.($tag_match + 1).']',
						$after
					);
					// non pages
					$this->add_rewrite_rule(
						$cpt_slug . '/' . trailingslashit($tag_base) . implode( "", array_fill( 0, count( $tag_query_vars ) , '([^/]*)/' ) ) . '?$',
						$redirect . implode( '&', $tag_query_vars ),
						$after
					);

					do_action( 'geodir_location_permalinks_tag_rewrite_rule', $post_type, $rewrite_slug, $tag_base, $redirect, $tag_match, $tag_query_vars, $after, $this );

					// remove one from the array
					array_pop( $tag_query_vars );
				}


				####################
				###  CPT rules  ####
				####################
				// post type archive rules
				$cpt_query_vars = $query_vars;
				foreach($cpt_query_vars  as $cpt_query_var){
					$cpt_match = count( $cpt_query_vars ) + 1;
					// Add rule for /feed/(feed|atom|rss|rss2|rdf).
					$this->add_rewrite_rule(
						$cpt_slug . '/' . implode( "", array_fill( 0, count( $cpt_query_vars ) , '([^/]*)/' ) ) . $feedregex,
						$redirect . implode( '&', $cpt_query_vars ).'&feed=$matches['.$cpt_match.']',
						$after
					);
					// Add rule for /(feed|atom|rss|rss2|rdf) (see comment near creation of $feedregex).
					$this->add_rewrite_rule(
						$cpt_slug . '/' . implode( "", array_fill( 0, count( $cpt_query_vars ) , '([^/]*)/' ) ) . $feedregex2,
						$redirect . implode( '&', $cpt_query_vars ).'&feed=$matches['.$cpt_match.']',
						$after
					);
					// paged
					$this->add_rewrite_rule(
						$cpt_slug . '/' . implode( "", array_fill( 0, count( $cpt_query_vars ) , '([^/]*)/' ) ) . 'page/?([0-9]{1,})/?$',
						$redirect . implode( '&', $cpt_query_vars ).'&paged=$matches['.$cpt_match.']',
						$after
					);
					// non paged
					$this->add_rewrite_rule(//
						$cpt_slug . '/' . implode( "", array_fill( 0, count( $cpt_query_vars ) , '([^/]*)/' ) ) . '?$',
						$redirect . implode( '&', $cpt_query_vars ),
						$after
					);

					do_action( 'geodir_location_permalinks_post_rewrite_rule', $post_type, $rewrite_slug, $cpt_match, $redirect, $cpt_query_vars, $after, $this );
//					print_r($cpt_query_vars);
					// remove one from the array
					array_pop( $cpt_query_vars );
				}

			}
		}
	}

	/**
	 * Returns the term link with parameters.
	 *
	 * @param $termlink
	 * @param $term
	 * @param $taxonomy
	 *
	 * @return mixed
	 */
	public function term_url( $termlink, $term, $taxonomy ) {
		global $geodirectory;

		if ( ! geodir_is_gd_taxonomy( $taxonomy ) ) {
			return $termlink;
		}

		$term_id = $term->term_id;
		$show_country = geodir_get_option( 'lm_hide_country_part') ? false : true;
		$show_region = geodir_get_option( 'lm_hide_region_part') ? false : true;
		$show_city = geodir_get_option( 'lm_hide_city_part') ? false : true;
		$cache_key = $term_id;
		$cache_group = 'geodir_term_url_' . $term_id;

		if ( ( $show_country || $show_region || $show_city ) && GeoDir_Taxonomies::supports( $taxonomy, 'location' ) ) {
			$show_hood = GeoDir_Location_Neighbourhood::is_active() ? true : false;
			$current_location = $geodirectory->location;

			$location_terms = array();
			$cache_keys = array();

			if ( $show_country ) { 
				$location_terms['country'] = $current_location->country_slug;
				$cache_keys[] = $current_location->country_slug;
			} else {
				$cache_keys[] = '';
			}
			if ( $show_region ) {
				$location_terms['region'] = $current_location->region_slug;
				$cache_keys[] = $current_location->region_slug;
			} else {
				$cache_keys[] = '';
			}
			if ( $show_city ) {
				$location_terms['city'] = $current_location->city_slug;
				$cache_keys[] = $current_location->city_slug;
			} else {
				$cache_keys[] = '';
			}
			if ( $show_hood ) {
				$location_terms['neighbourhood'] = $current_location->neighbourhood_slug;
				$cache_keys[] = $current_location->neighbourhood_slug;
			} else {
				$cache_keys[] = '';
			}

			$cache_key .= '_' .  implode( ':', $cache_keys );

			$cache = wp_cache_get( $cache_key, $cache_group );
			if ( $cache ) {
				return $cache;
			}

			$location_terms = array_filter( $location_terms );

			// new style
			if ( ! empty( $location_terms ) ) {
				$termlink = trailingslashit( $termlink ) . implode( "/", $location_terms );
				if ( $this->is_slash() ) {
					$termlink = trailingslashit( $termlink );
				}
			}
		} else {
			$cache = wp_cache_get( $cache_key, $cache_group );
			if ( $cache ) {
				return $cache;
			}
		}

		$termlink = apply_filters( 'geodir_term_link', $termlink, $term, $taxonomy );

		wp_cache_set( $cache_key, $termlink, $cache_group );

		return $termlink;
	}

	/**
	 * Add GD rewrite tags.
	 *
	 * @since 2.0.0
	 */
	public function rewrite_tags(){
		parent::rewrite_tags();
		add_rewrite_tag('%neighbourhood%', '([^&]+)');
		add_rewrite_tag('%'.$this->location_near_slug().'%', '(gps|'.$this->location_me_slug().')');
	}

	public function location_near_slug(){
		return apply_filters('geodir_location_near_slug','near');
	}

	public function location_me_slug(){
		return apply_filters('geodir_location_me_slug','me');
	}

	/**
	 * Add the locations page rewrite rules.
	 */
	public function location_rewrite_rules() {
		$show_country = geodir_get_option( 'lm_hide_country_part' ) ? false : true;
		$show_region = geodir_get_option( 'lm_hide_region_part' ) ? false : true;
		$show_city = geodir_get_option( 'lm_hide_city_part' ) ? false : true;
		$show_neighbourhood = geodir_get_option( 'lm_enable_neighbourhoods' ) ? true : false;

		$location_terms = array();

		if ( $show_country ) {
			$location_terms[] = 'country';
		}

		if ( $show_region ) {
			$location_terms[] = 'region';
		}

		if ( $show_city ) {
			$location_terms[] = 'city';
		}

		if ( $show_neighbourhood ) {
			$location_terms[] = 'neighbourhood';
		}

		// Base args
		$redirect = 'index.php?pagename=' . $this->location_slug() . "&";
		$after = 'top'; // top/bottom
		$match = 1;
		$query_vars = array();
		$base = "^" . $this->location_slug();

		// Add location terms
		foreach ( $location_terms as $tag ) {
			$query_vars[] = trim( $tag ) . '=$matches[' . $match . ']';
			$match ++;
		}

		foreach ( $query_vars  as $query_var ) {
			$this->add_rewrite_rule(
				$base . '/' . implode( "", array_fill( 0, count( $query_vars ) , '([^/]*)/' ) ) . '?$',
				$redirect . implode( '&', $query_vars ),
				$after
			);

			// Remove one from the array
			array_pop( $query_vars );
		}
	}

	/**
	 * Check & skip set current location.
	 *
	 * @since 2.0.0.24
	 *
	 * @param bool $skip True to skip set current location.
	 * @return bool True or false.
	 */
	public function skip_set_current( $skip ) {
		if ( isset( $_POST['country'] ) || isset( $_POST['region'] ) || isset( $_POST['city'] ) || isset( $_POST['neighbourhood'] ) ) {
			// UsersWP
			if ( isset( $_POST['uwp_account_nonce'] ) || isset( $_POST['uwp_register_nonce'] ) ) {
				$skip = true;
			}elseif( isset( $_POST['getpaid-nonce'] ) && isset( $_POST['getpaid-action'] ) ){ // GetPaid
				$skip = true;
			}
		}

		return $skip;
	}

	/**
	 * Rescue 404 location page.
	 *
	 * @since 2.3.5
	 *
	 * @param object $wp WP Object.
	 * @return mixed.
	 */
	public function rescue_404( $wp ) {
		global $wp_rewrite, $geodirectory;

		if ( empty( $wp->query_vars['gd_is_geodir_page'] ) ) {
			return;
		}

		if ( is_404() ) {
			if ( ! ( ! empty( $wp->query_vars['name'] ) && ! empty( $wp->query_vars['post_type'] ) && geodir_is_gd_post_type( $wp->query_vars['post_type'] ) && ! empty( $wp->query_vars[ $wp->query_vars['post_type'] ] ) ) ) {
				return;
			}

			if ( $wp->query_vars['name'] == $wp->query_vars[ $wp->query_vars['post_type'] ] && ! empty( $wp_rewrite->rules ) && $this->is_location_slug( $wp->query_vars['name'] ) ) {
				$matching = array();

				foreach ( $wp_rewrite->rules as $match => $query ) {
					if ( preg_match( "#^{$match}#", $wp->request ) && $match != $wp->matched_rule ) {
						$matching = array(
							'match' => $match,
							'query' => $query
						);
						break;
					}
				}

				if ( ! empty( $matching ) ) {
					preg_match( "#^" . $matching['match'] . "#", urldecode( $wp->request ), $matches );

					if ( ! empty( $matches ) ) {
						$query = preg_replace( '!^.+\?!', '', $matching['query'] );
						$query = addslashes( WP_MatchesMapRegex::apply( $query, $matches ) );

						parse_str( $query, $perma_query_vars );

						$perma_query_vars['gd_is_geodir_page'] = true;

						$wp->query_vars = $perma_query_vars;
						$wp->matched_rule = $matching['match'];
						$wp->matched_query = $query;
						$wp->build_query_string();

						add_action( 'pre_get_posts', array( $geodirectory->query, 'pre_get_posts' ), 10, 1 );

						$wp->query_posts();
						$wp->handle_404();
						$wp->register_globals();
						$wp->send_headers();

						// Set current location after 404 rescue.
						$geodirectory->location->set_current();
					}
				}
			}
		}
	}

	/**
	 * Check location slug.
	 *
	 * @since 2.3.5
	 *
	 * @param $slug Location slug.
	 * @return null|object
	 */
	public function is_location_slug( $slug ) {
		global $wpdb;

		$found = geodir_cache_get( 'is_location_slug:' . $slug, 'locations' );

		if ( $found === false ) {
			$slug = sanitize_title_for_query( $slug ); // Ex: convert μουσείο to %ce%bc%ce%bf%cf%85%cf%83%ce%b5%ce%af%ce%bf.

			$found = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . GEODIR_LOCATIONS_TABLE . "` WHERE `city_slug` = %s OR `region_slug` = %s OR `country_slug` = %s LIMIT 1", $slug, $slug, $slug ) );

			geodir_cache_set( 'is_location_slug:' . $slug, $found, 'locations' );
		}

		return $found;
	}
}