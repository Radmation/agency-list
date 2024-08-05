<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Advance Search Filters Query class.
 *
 * AJAX Event Handler.
 *
 * @class    GeoDir_Adv_Search_Query
 * @package  GeoDir_Advance_Search_Filters/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Adv_Search_Query {

	function __construct() {

		add_filter( 'geodir_posts_fields', array( __CLASS__, 'posts_fields' ), 1, 2 );
		add_filter( 'geodir_posts_join', array( __CLASS__, 'posts_join' ), 1, 2 );
		add_filter( 'geodir_posts_where', array( __CLASS__, 'posts_where' ), 1, 2 );
		add_filter( 'geodir_posts_order_by_sort', array( __CLASS__, 'posts_orderby' ), 1, 4 );
		add_filter( 'geodir_posts_groupby', array( __CLASS__, 'posts_groupby' ), 1, 2 );

		// Distance sort by
		add_filter( 'geodir_posts_order_by_sort', array( __CLASS__, 'sory_by_distance' ), 10, 4 );

		// Classifieds filter
		add_filter( 'geodir_get_post_stati', array( __CLASS__, 'filter_post_stati' ), 9, 3 );

		// AJAX search
		add_action( 'geodir_search_handle_ajax_request', array( __CLASS__, 'ajax_search_init' ), 0 );
		add_action( 'geodir_search_ajax_init', array( __CLASS__, 'ajax_search_set_request' ), 0 );

		// Set searched distance unit.
		if ( ! empty( $_REQUEST['geodir_search'] ) && isset( $_REQUEST['snear'] ) && isset( $_REQUEST['_unit'] ) ) {
			add_action( 'geodir_get_option_search_distance_long', array( __CLASS__, 'search_set_distance_unit' ), 20, 3 );
			add_action( 'geodir_get_option_search_distance_short', array( __CLASS__, 'search_set_short_distance_unit' ), 20, 3 );
		}

		// Divi + AJAX Search + GD AJAX compatibility.
		if ( defined( 'GEODIR_FAST_AJAX' ) && ! empty( $_REQUEST['gd-ajax'] ) && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'geodir_ajax_search' ) {
			add_filter( 'et_builder_modules_load_hook', array( __CLASS__, 'et_builder_modules_load_hook' ), 21, 1 );
		}

		// GD Booking
		add_filter( 'geodir_search_posts_where_skip_field', array( __CLASS__, 'search_posts_where_skip_field' ), 30, 2 );
		add_filter( 'geodir_search_posts_join', array( __CLASS__, 'search_posts_join' ), 30, 3 );
		add_filter( 'geodir_search_posts_where', array( __CLASS__, 'search_posts_where' ), 30, 3 );
	}

	public static function posts_fields( $fields, $wp_query = array() ) {
		global $geodir_post_type;

		return $fields;
	}

	public static function posts_join( $join, $wp_query = array() ) {
		global $wpdb, $geodir_post_type, $table;

		if ( ! geodir_is_page('search') ) {
			return $join;
		}

		if ( ! ( ! empty( $wp_query ) && $wp_query->is_main_query() ) ) {
			return $join;
		}

		// Current post type
		$post_type = geodir_get_search_post_type();

		return apply_filters( 'geodir_search_posts_join', $join, $post_type, $wp_query );
	}

	public static function posts_where( $where, $wp_query = array() ) {
		global $wpdb, $geodir_post_type, $table;

		if ( ! geodir_is_page('search') ) {
			return $where;
		}

		if ( ! ( ! empty( $wp_query ) && $wp_query->is_main_query() ) ) {
			return $where;
		}
		
		// Current post type
		$post_type = geodir_get_search_post_type();

		if ( empty( $table ) ) {
			$table = geodir_db_cpt_table( $post_type );
		}

		// Search fields
		$fields = GeoDir_Adv_Search_Fields::get_search_fields( $post_type );

		if ( ! empty( $fields ) ) {
			$checkbox_fields = GeoDir_Adv_Search_Fields::checkbox_fields( $post_type );
			$active_features = geodir_classified_active_statuses( $post_type );

			$fields_where = array();

			foreach ( $fields as $key => $field ) {
				$field = stripslashes_deep( $field );
				if ( $field->htmlvar_name == 'address' ) {
					$field->htmlvar_name = 'street';
				}

				$skip = isset( $field->htmlvar_name ) && in_array( $field->htmlvar_name, array( '_sold' ) ) ? true : false;
	
				if ( $field->htmlvar_name == 'sale_status' && ! empty( $active_features ) ) {
					$skip = true;
				}

				$skip = apply_filters( 'geodir_search_posts_where_skip_field', $skip, $field );
				if ( $skip ) {
					continue;
				}

				$htmlvar_name = $field->htmlvar_name;
				$extra_fields = ! empty( $field->extra_fields ) ? maybe_unserialize( $field->extra_fields ) : NULL;

				$field_where = array();

				switch ( $field->input_type ) {
					case 'RANGE': {
						switch ( $field->search_condition ) {
							case 'SINGLE': {
								$value = isset( $_REQUEST['s' . $htmlvar_name ] ) ? sanitize_text_field( $_REQUEST['s' . $htmlvar_name ] ) : '';

								if ( $value !== '' ) {
									$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} = %s", array( $value ) );
								}
							}
							break;
							case 'FROM': {
								$min_value = isset( $_REQUEST['smin' . $htmlvar_name ] ) ? sanitize_text_field( $_REQUEST['smin' . $htmlvar_name ] ) : '';
								$max_value = isset( $_REQUEST['smax' . $htmlvar_name ] ) ? sanitize_text_field( $_REQUEST['smax' . $htmlvar_name ] ) : '';

								// min range
								if ( $min_value !== '' ) {
									$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} >= %s", array( $min_value ) );
								}

								// max range
								if ( $max_value !== '' ) {
									$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} <= %s", array( $max_value ) );
								}
							}
							break;
							case 'RADIO': {
								// This code in main geodirectory listing filter
							}
							break;
							default: {
								$value = isset( $_REQUEST['s' . $htmlvar_name ] ) ? sanitize_text_field( stripslashes_deep( $_REQUEST['s' . $htmlvar_name ] ) ) : '';

								if ( $value !== '' ) {
									$values = explode( '-', $value );

									$min_value = trim( $values[0] );
									$max_value = isset( $values[1] ) ? trim( $values[1] ) : '';

									$compare = substr( $max_value, 0, 4 );

									if ( $compare == 'Less' || $compare == 'less' ) {
										if ( $min_value !== '' ) {
											$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} < %s", array( $min_value ) );
										}
									} else if ( $compare == 'More' || $compare == 'more' ) {
										if ( $min_value !== '' ) {
											$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} > %s", array( $min_value ) );
										}
									} else {
										if ( $min_value !== '' ) {
											$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} >= %s", array( $min_value ) );
										}

										if ( $max_value !== '' ) {
											$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} <= %s", array( $max_value ) );
										}
									}
								}
							}
							break;
						}
					}
					break;
					case 'DATE': {
						if ( ! empty( $_REQUEST[ $htmlvar_name ] ) ) {
							$value = stripslashes_deep( $_REQUEST[ $htmlvar_name ] );

							// new one field range picker
							$design_style = geodir_design_style();
							if(!is_array($value) && $design_style && strpos($value, ' ') !== false){
								$parts = explode(" ",$value);
								if(!empty($parts[2])){
									$value = array();
									$value['from'] = $parts[0];
									$value['to'] = $parts[2];
								}
							}

							if ( $field->data_type == 'DATE' ) {
								if ( is_array( $value ) ) {
									$value_from = ! empty( $value['from'] ) ? date_i18n( 'Y-m-d', strtotime( sanitize_text_field( $value['from'] ) ) ) : '';
									$value_to = ! empty( $value['to'] ) ? date_i18n( 'Y-m-d', strtotime( sanitize_text_field( $value['to'] ) ) ) : '';

									if ( ! empty( $value_from ) ) {
										$field_where[] = $wpdb->prepare( "UNIX_TIMESTAMP( {$table}.{$htmlvar_name} ) >= UNIX_TIMESTAMP( %s )", array( $value_from ) );
									}

									if ( ! empty ( $value_to ) ) {
										$field_where[] = $wpdb->prepare( "UNIX_TIMESTAMP( {$table}.{$htmlvar_name} ) <= UNIX_TIMESTAMP( %s )", array( $value_to ) );
									}
								} else {
									$value = date_i18n( 'Y-m-d', strtotime( sanitize_text_field( $value ) ) );
									$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} = %s", array( $value ) );
								}
							} else if ( $field->data_type == 'TIME' ) {
								if ( is_array( $value ) ) {
									$value_from = isset( $value['from'] ) && $value['from'] != '' ? date_i18n( 'H:i:s', strtotime( sanitize_text_field( $value['from'] ) ) ) : '';
									$value_to = isset( $value['to'] ) && $value['to'] != '' ? date_i18n( 'H:i:s', strtotime( sanitize_text_field( $value['to'] ) ) ) : '';

									if ( ! empty( $value_from ) ) {
										$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} >= %s", array( $value_from ) );
									}

									if ( ! empty ( $value_to ) ) {
										$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} <= %s", array( $value_to ) );
									}
								} else {
									$value = date_i18n( 'H:i:s', strtotime( sanitize_text_field( $value ) ) ); // old style
									$value2 = date_i18n( 'H:i', strtotime( sanitize_text_field( $value ) ) ); // new style
									$field_where[] = $wpdb->prepare( " ( {$table}.{$htmlvar_name} = %s || {$table}.{$htmlvar_name} = %s) ", array( $value,$value2  ) );
								}
							}
						}
					}
					break;
					default: {
						if ( isset( $_REQUEST['s' . $htmlvar_name ] ) ) {
							$value = stripslashes_deep( $_REQUEST['s' . $htmlvar_name ] );

							if ( is_array( $value ) ) {
								$search_operator = !empty( $extra_fields ) && !empty( $extra_fields['search_operator'] ) && $extra_fields['search_operator'] == 'OR' ? 'OR' : 'AND';

								$loops = array();
								foreach ( $value as $v ) {
									$v = sanitize_text_field( $v );
									if ( $v !== '' ) {
										$terms_loop = '';
										if ( $htmlvar_name == 'post_category' ) {
											$terms_loop = self::query_terms_children( absint( $v ), $post_type, $htmlvar_name, $table );
										}

										if ( ! empty( $terms_loop ) ) {
											$loops[] = $terms_loop;
										} else {
											$loops[] = $wpdb->prepare( "FIND_IN_SET( %s, {$table}.{$htmlvar_name} )", array( $v ) );
										}
									}
								}

								if ( ! empty ( $loops ) ) {
									$field_where[] = ( count( $loops ) > 1 ? '( ' : '' ) . implode( " {$search_operator} ", $loops ) . ( count( $loops ) > 1 ? ' )' : '' );
								}
							} else {
								$value = sanitize_text_field( $value );

								if ( $value !== '' ) {
									// Show special offers, video as a checkbox field.
									if ( ! empty( $checkbox_fields ) && in_array( $htmlvar_name, $checkbox_fields ) && (int)$value == 1 ) {
										$field_where[] = "{$table}.{$htmlvar_name} IS NOT NULL AND {$table}.{$htmlvar_name} != '' AND {$table}.{$htmlvar_name} != '0'";
									} else {
										if ( $field->data_type == 'VARCHAR' || $field->data_type == 'TEXT' ) {
											$operator = 'LIKE';
											if ( ! empty( $value ) ) {
												$value = '%' . $value . '%';
											}
										} else {
											$operator = '=';
										}
										$field_where[] = $wpdb->prepare( "{$table}.{$htmlvar_name} {$operator} %s", array( $value ) );
									}
								}
							}
						}
					}
					break;
				}

				$field_where = ! empty( $field_where ) ? implode( " AND ", $field_where ) : '';
				$field_where = apply_filters( 'geodir_search_posts_field_where', $field_where, $htmlvar_name, $field, $post_type, $wp_query );

				if ( ! empty( $field_where ) ) {
					$fields_where[] = $field_where;
				}
			}

			$fields_where = ! empty( $fields_where ) ? implode( " AND ", $fields_where ) : '';
			$fields_where = apply_filters( 'geodir_search_posts_fields_where', $fields_where, $where, $wp_query );

			if ( ! empty( $fields_where ) ) {
				$where = rtrim( $where ) . " AND {$fields_where}";
			}
		}

		// AJAX map search
		if ( defined( 'GEODIR_AJAX_SEARCH' ) && isset( $wp_query->query_vars['lat_ne'] ) && ! empty( $wp_query->query_vars['lon_ne'] ) && isset( $wp_query->query_vars['lat_sw'] ) && ! empty( $wp_query->query_vars['lon_sw'] ) && GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			$lat_ne = $wp_query->query_vars['lat_ne'];
			$lon_ne = $wp_query->query_vars['lon_ne'];
			$lat_sw = $wp_query->query_vars['lat_sw'];
			$lon_sw = $wp_query->query_vars['lon_sw'];

			if ( $lon_ne > 0 && $lon_sw > 0 && $lon_ne < $lon_sw ) {
				$lon_not = 'NOT ';
			} elseif ( $lon_ne < 0 && $lon_sw < 0 && $lon_ne < $lon_sw ) {
				$lon_not = 'NOT ';
			} elseif ( $lon_ne < 0 && $lon_sw > 0 && ( $lon_ne + 360 - $lon_sw ) > 180 ) {
				$lon_not = 'NOT ';
			} elseif ( $lon_ne < 0 && $lon_sw > 0 && abs( $lon_ne ) + abs( $lon_sw ) > 180 ) {
				$lon_not = 'NOT ';
			} else {
				$lon_not = '';
			}

			// Private address
			if ( GeoDir_Post_types::supports( $post_type, 'private_address' ) ) {
				$where .= " AND ( `{$table}`.`private_address` IS NULL OR `{$table}`.`private_address` <> 1 )";
			}

			$where .= " AND ( {$table}.latitude BETWEEN LEAST( {$lat_sw}, {$lat_ne} ) AND GREATEST( {$lat_sw}, {$lat_ne} ) ) AND ( {$table}.longitude {$lon_not}BETWEEN LEAST( {$lon_sw}, {$lon_ne} ) AND GREATEST( {$lon_sw}, {$lon_ne} ) )";
		}

		return apply_filters( 'geodir_search_posts_where', $where, $wp_query, $post_type );
	}

	public static function posts_groupby( $groupby, $wp_query = array() ) {
		if ( ! geodir_is_page('search') ) {
			return $groupby;
		}

		if ( ! ( ! empty( $wp_query ) && $wp_query->is_main_query() ) ) {
			return $groupby;
		}

		// Current post type
		$post_type = geodir_get_search_post_type();

		return apply_filters( 'geodir_search_posts_groupby', $groupby, $post_type, $wp_query );
	}

	public static function posts_orderby( $orderby, $sortby, $table, $wp_query = array() ) {
		global $geodir_post_type;

		return $orderby;
	}

	public static function sory_by_distance( $orderby, $sort_by, $table, $query ) {
		global $geodir_post_type;

		if ( ! empty( $sort_by ) && ( $sort_by == 'nearest' || $sort_by == 'farthest' ) ) {
			$support_location = $geodir_post_type && GeoDir_Post_types::supports( $geodir_post_type, 'location' );

			if ( $support_location && ( ! empty( $_REQUEST['snear'] ) || ( get_query_var( 'user_lat' ) && get_query_var( 'user_lon' ) ) ) && geodir_is_page( 'search' ) ) {
				$orderby = $sort_by == 'nearest' ? "distance ASC" : "distance DESC";
				$_orderby = GeoDir_Query::search_sort( '', $sort_by, $query );
				if ( trim( $_orderby ) != '' ) {
					$orderby .= ", " . $_orderby;
				}
			}
		}

		return $orderby;
	}

	public static function filter_terms_children( $term_id, $taxonomy ) {
		if ( ! function_exists( 'geodir_get_term_children' ) ) {
			return NULL;
		}

		$children = geodir_get_term_children( $term_id, $taxonomy );

		if ( ! empty( $children ) ) {
			foreach ( $children as $id => $term ) {
				if ( ! empty( $term->count ) ) {
					$terms[] = $term->term_id;
				}
			}
		}

		return $terms;
	}

	public static function query_terms_children( $term_id, $post_type, $column = 'post_category', $alias = '' ) {
		global $wpdb;

		if ( empty( $term_id ) || empty( $post_type ) || $column != 'post_category' || ! geodir_get_option( 'advs_search_in_child_cats' ) ) {
			return NULL;
		}

		$taxonomy = $post_type . 'category';

		$terms = self::filter_terms_children( $term_id, $taxonomy );
		if ( $alias != '' ) {
			$alias .= '.';
		}

		$loops = array();
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $search_id ) {
				$loops[] = $wpdb->prepare( "FIND_IN_SET( %s, {$alias}{$column} )", array( $search_id ) );
			}
		}

		$loops = ! empty( $loops ) ? "( " . implode( " OR ", $loops ) . " )" : "";

		return $loops;
	}

	public static function filter_post_stati( $statuses, $context, $args ) {
		if ( ! empty( $args['post_type'] ) ) {
			// Search
			if ( $context == 'search' && ( $sale_status = GeoDir_Query::get_query_var( 'ssale_status' ) ) && ( $active_statuses = geodir_classified_active_statuses( $args['post_type'] ) ) ) {
				if ( ! is_array( $sale_status ) ) {
					$sale_status = array( $sale_status );
				}

				$_statuses = array();
				foreach ( $sale_status as $_sale_status ) {
					if ( in_array( $_sale_status, $active_statuses ) ) {
						$_statuses = array( strip_tags( $_sale_status ) );
					}
				}

				if ( ! empty( $_statuses ) ) {
					$statuses = $_statuses;
				}
			}

			if ( $context == 'search' && GeoDir_Query::get_query_var( 's_sold' ) ) {
				$statuses[] = 'gd-sold';
			}

			// Map
			if ( $context == 'map' && ! empty( $args['post'] ) && is_array( $args['post'] ) ) {
				$statuses[] = 'gd-sold';
			}
		}

		return $statuses;
	}

	/**
	 * AJAX search initialize.
	 *
	 * @since 2.2.2
	 */
	public static function ajax_search_init() {
		if ( ! ( ! empty( $_REQUEST['geodir_search'] ) && ! empty( $_REQUEST['_nonce'] ) && wp_doing_ajax() ) ) {
			return;
		}

		$is_ajax_search = (bool) geodir_get_option( 'advs_ajax_search' );

		if ( $is_ajax_search && ! wp_verify_nonce( sanitize_text_field( $_REQUEST['_nonce'] ), 'geodir_basic_nonce' ) ) {
			geodir_error_log( __( 'AJAX search check failed!' ), 'geodir_search_invalid_nonce' );
			$is_ajax_search = false;
		}

		$is_ajax_search = apply_filters( 'geodir_search_is_ajax_search', $is_ajax_search );

		if ( ! $is_ajax_search ) {
			return;
		}

		if ( ! defined( 'GEODIR_AJAX_SEARCH' ) ) {
			define( 'GEODIR_AJAX_SEARCH', true );
		}

		do_action( 'geodir_search_ajax_init' );
	}

	public static function ajax_search_set_request() {
		global $wp, $geodirectory;

		// Unset distance when no latitude/longitude set.
		if ( ! empty( $_REQUEST['dist'] ) && ( empty( $_REQUEST['sgeo_lat'] ) || empty( $_REQUEST['sgeo_lon'] ) ) ) {
			$_REQUEST['dist'] = '';
		}

		add_filter( 'query_vars', array( $geodirectory->query, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( __CLASS__, 'parse_request' ), 0 );
		add_action( 'pre_get_posts', array( $geodirectory->query, 'set_globals' ) );
		add_action( 'pre_get_posts', array( $geodirectory->query, 'pre_get_posts' ) );

		// Beaver Themer Plugin.
		if ( class_exists( 'FLThemeBuilderFieldConnections' ) ) {
			remove_filter( 'fl_builder_node_settings', array( 'FLThemeBuilderFieldConnections', 'fl_builder_node_settings' ) );
			add_filter( 'fl_builder_node_settings', array( __CLASS__, 'fl_builder_node_settings' ), 10, 2 );
		}

		$wp->parse_request();
		$wp->query_posts();
		$wp->register_globals();

		// Beaver Themer loads page data properties.
		if ( class_exists( 'FLPageData' ) ) {
			FLPageData::init_properties();
		}
	}

	/**
	 * Connects any settings that have a field connection for a node.
	 *
	 * @since 2.2.5
	 *
	 * @param object $settings The settings object for a node.
	 * @param object $node The node object.
	 * @return object
	 */
	public static function fl_builder_node_settings( $settings, $node ) {
		global $wp_the_query, $post, $geodir_connected_settings;

		$repeater = array();
		$nested   = array();

		// Get the connection cache key.
		if ( is_object( $wp_the_query->post ) && 'fl-theme-layout' === $wp_the_query->post->post_type ) {
			$cache_key = $node->node;
		} else {
			$cache_key = $post && isset( $post->ID ) ? $node->node . '_' . $post->ID : $node->node;
		}

		// Check for bb loop.
		if ( isset( FLThemeBuilderFieldConnections::$in_post_grid_loop ) ) {
			if ( FLThemeBuilderFieldConnections::$in_post_grid_loop && $post && isset( $post->ID ) ) {
				$cache_key = $node->node . '_' . $post->ID;
			}
		}

		$cache_key = apply_filters( 'fl_themer_builder_connect_node_settings_cache_key', $cache_key, $settings, $node );

		// Gather any repeater or nested settings.
		foreach ( $settings as $key => $value ) {
			if ( is_array( $value ) && count( $value ) && isset( $value[0]->connections ) ) {
				$repeater[] = $key;
			} elseif ( is_object( $value ) && isset( $value->connections ) ) {
				$nested[] = $key;
			}
		}

		// Return if we don't have connections.
		if ( ! isset( $settings->connections ) && empty( $repeater ) && empty( $nested ) ) {
			return $settings;
		}

		// Return cached connections?
		if ( isset( $geodir_connected_settings[ $cache_key ] ) ) {
			return $geodir_connected_settings[ $cache_key ];
		}

		// Connect the main settings object.
		$settings = FLThemeBuilderFieldConnections::connect_settings( $settings );

		// Connect any repeater settings.
		foreach ( $repeater as $key ) {
			for ( $i = 0; $i < count( $settings->$key ); $i++ ) {
				$settings->{ $key }[ $i ] = FLThemeBuilderFieldConnections::connect_settings( $settings->{ $key }[ $i ] );
			}
		}

		// Connect any nested settings.
		foreach ( $nested as $key ) {
			$settings->{ $key } = FLThemeBuilderFieldConnections::connect_settings( $settings->{ $key } );
		}

		// Cache the connected settings.
		$geodir_connected_settings[ $cache_key ] = $settings;

		return $settings;
	}

	public static function parse_request() {
		global $wp, $geodirectory;

		$wp->query_vars['gd_is_geodir_page'] = true;

		// Map query vars to their keys, or get them if endpoints are not supported
		foreach ( $geodirectory->query->get_query_vars() as $key => $var ) {
			if ( isset( $_REQUEST[ $var ] ) ) {
				$wp->query_vars[ $key ] = $_REQUEST[ $var ];
			} elseif ( isset( $wp->query_vars[ $var ] ) ) {
				$wp->query_vars[ $key ] = $wp->query_vars[ $var ];
			}
		}

		if ( (bool) geodir_get_option( 'advs_map_search' ) && isset( $_REQUEST['lat_ne'] ) && ! empty( $_REQUEST['lon_ne'] ) && isset( $_REQUEST['lat_sw'] ) && ! empty( $_REQUEST['lon_sw'] ) ) {
			$lat_ne = filter_var( sanitize_text_field( $_REQUEST['lat_ne'] ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
			$lon_ne = filter_var( sanitize_text_field( $_REQUEST['lon_ne'] ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
			$lat_sw = filter_var( sanitize_text_field( $_REQUEST['lat_sw'] ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
			$lon_sw = filter_var( sanitize_text_field( $_REQUEST['lon_sw'] ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );

			$wp->query_vars['lat_ne'] = $lat_ne;
			$wp->query_vars['lon_ne'] = $lon_ne;
			$wp->query_vars['lat_sw'] = $lat_sw;
			$wp->query_vars['lon_sw'] = $lon_sw;

			if ( ! defined( 'GEODIR_MAP_SEARCH' ) ) {
				define( 'GEODIR_MAP_SEARCH', true );
			}

			// Unset location filter.
			if ( function_exists( 'GeoDir_Location' ) ) {
				remove_filter( 'geodir_main_query_posts_where', 'geodir_location_main_query_posts_where', 0, 3 );
			}

			if ( geodir_get_option( 'advs_map_search_type' ) == 'all' ) {
				add_filter( 'posts_clauses', array( __CLASS__, 'map_search_posts_clauses' ),.99999, 2 );
				add_filter( 'posts_clauses_request', array( __CLASS__, 'map_search_posts_clauses_request' ),.99999, 2 );
			}

			if ( ! empty( $_REQUEST['zl'] ) ) {
				$wp->query_vars['zl'] = absint( $_REQUEST['zl'] );
			}
		}

		// Set current location
		$geodirectory->location->set_current();
	}

	public static function map_search_posts_clauses( $clauses, $wp_query ) {
		global $geodir_map_clauses;

		$geodir_map_clauses = $clauses;

		return $clauses;
	}

	public static function map_search_posts_clauses_request( $clauses, $wp_query ) {
		global $geodir_map_clauses_r;

		$geodir_map_clauses_r = $clauses;

		return $clauses;
	}

	public static function search_set_distance_unit( $value, $key, $default ) {
		if ( ! isset( $_REQUEST['_unit'] ) ) {
			return $value;
		}

		if ( $_REQUEST['_unit'] == 'km' ) {
			$value = 'km';
		} elseif ( $_REQUEST['_unit'] == 'mi' || $_REQUEST['_unit'] == 'miles' ) {
			$value = 'miles';
		}

		return $value;
	}

	public static function search_set_short_distance_unit( $value, $key, $default ) {
		if ( ! isset( $_REQUEST['_unit'] ) ) {
			return $value;
		}

		if ( $_REQUEST['_unit'] == 'km' ) {
			$value = 'meters';
		} elseif ( $_REQUEST['_unit'] == 'mi' || $_REQUEST['_unit'] == 'miles' ) {
			$value = 'feet';
		}

		return $value;
	}

	public static function et_builder_modules_load_hook( $hook ) {
		$hook = 'geodir_search_ajax_init';

		return $hook;
	}

	/**
	 * Skip field from detail table SQL part.
	 *
	 * @param bool   $skip Skip when true.
	 * @param object $field Field object.
	 * @return bool Skip or not.
	 */
	public static function search_posts_where_skip_field( $skip, $field ) {
		if ( ! empty( $field->htmlvar_name ) && $field->htmlvar_name == 'gdbdate' ) {
			$skip = true;
		}

		return $skip;
	}

	/**
	 * Add search posts SQL join.
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param string $join SQL join.
	 * @param string $post_type Post type.
	 * @param object $wp_query WP_Query object.
	 * @return string Search posts SQL join.
	 */
	public static function search_posts_join( $join, $post_type, $wp_query = array() ) {
		global $wpdb;

		// GD Booking availability search.
		if ( ! empty( $_REQUEST['gdbdate'] ) && geodir_search_booking_active() && GeoDir_Post_types::supports( $post_type, 'gdbooking' ) ) {
			$dates = geodir_search_sanitize_date_range( sanitize_text_field( $_REQUEST['gdbdate'] ) );

			if ( empty( $dates['days'] ) ) {
				return $join;
			}

			$table = geodir_db_cpt_table( $post_type );

			if ( $join ) {
				$join = rtrim( $join );
			}

			foreach ( $dates['days'] as $year => $days ) {
				$join .= " LEFT JOIN `{$wpdb->prefix}gdbc_availability` AS `ba{$year}` ON ( `ba{$year}`.`post_id` = `{$table}`.`post_id` AND `ba{$year}`.`year` = {$year} ) "; 
			}
		}

		return $join;
	}

	/**
	 * Add search posts SQL where.
	 *
	 * @param string $where SQL where.
	 * @param object $wp_query WP_Query object.
	 * @param string $post_type The post type.
	 * @return string Search posts SQL where.
	 */
	public static function search_posts_where( $where, $wp_query = array(), $post_type = '' ) {
		// GD Booking availability search.
		if ( ! empty( $_REQUEST['gdbdate'] ) && geodir_search_booking_active() && GeoDir_Post_types::supports( $post_type, 'gdbooking' ) ) {
			$dates = geodir_search_sanitize_date_range( sanitize_text_field( $_REQUEST['gdbdate'] ) );

			if ( ! empty( $dates['days'] ) ) {
				$table = geodir_db_cpt_table( $post_type );

				$_where = array();

				foreach ( $dates['days'] as $year => $days ) {
					$parts = array();

					foreach ( $days as $day ) {
						$parts[] = "`ba{$year}`.`d{$day}` IS NULL";
					}

					$_where[] = "( ( " . implode( " AND ", $parts ) . " ) OR `ba{$year}`.`year` IS NULL )";
				}

				if ( $where ) {
					$where = rtrim( $where );
				}

				$where .= " AND `{$table}`.`gdbooking` = 1 AND ( " . implode( " AND ", $_where ) . " ) ";
			}
		}

		return $where;
	}
}