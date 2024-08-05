<?php
/**
 * Business Hours integration class.
 *
 * @since 2.0.1.0
 * @package GeoDir_Advance_Search_Filters
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Adv_Search_Business_Hours class.
 */
class GeoDir_Adv_Search_Business_Hours {

	/**
	 * Setup.
	 */
	public static function init() {
		add_action( 'geodir_post_saved', array( __CLASS__, 'on_post_saved' ), 1, 4 );

		// Search filter
		add_filter( 'geodir_search_posts_join', array( __CLASS__, 'search_posts_join' ), 20, 3 );
		add_filter( 'geodir_search_posts_field_where', array( __CLASS__, 'search_posts_field_where' ), 20, 5 );
		add_filter( 'geodir_search_posts_groupby', array( __CLASS__, 'search_posts_groupby' ), 20, 3 );
		add_filter( 'geodir_search_filter_searched_params', array( __CLASS__, 'search_business_hours_searched_param' ), 20, 3 );

		// Widget filter
		add_filter( 'geodir_widget_listings_query_args', array( __CLASS__, 'widget_posts_query_args' ), 20, 2 );
		add_filter( 'geodir_filter_widget_listings_join', array( __CLASS__, 'widget_posts_join' ), 20, 2 );
		add_filter( 'geodir_filter_widget_listings_where', array( __CLASS__, 'widget_posts_where' ), 20, 2 );

		// Markers filter
		//add_filter( 'geodir_rest_markers_query_join', array( __CLASS__, 'rest_markers_query_join' ), 20, 2 );
		//add_filter( 'geodir_rest_markers_query_where', array( __CLASS__, 'rest_markers_query_where' ), 20, 2 );

		// Admin settings
		add_filter( 'geodir_debug_tools', array( __CLASS__, 'tool_merge_business_hours' ), 50, 1 );
		add_filter( 'wp_super_duper_arguments', array( __CLASS__, 'gd_listings_widget_business_hours' ), 10, 3 );

		// Cron schedule
		add_filter( 'geodir_search_schedule_adjust_business_hours_dst', array( __CLASS__, 'adjust_business_hours_dst' ) );

		// Delete Business Hours
		add_action( 'delete_post', array( __CLASS__, 'on_delete_post' ), 20, 1 );
	}

	public static function business_hours_options( $placeholder = false ) {
		$options = array(
			'now' => __( 'Open Now', 'geodiradvancesearch' ),
			'mon' => __( 'Monday' ),
			'tue' => __( 'Tuesday' ),
			'wed' => __( 'Wednesday' ),
			'thu' => __( 'Thursday' ),
			'fri' => __( 'Friday' ),
			'sat' => __( 'Saturday' ),
			'sun' => __( 'Sunday' ),
			'weekend' => __( 'Weekend', 'geodiradvancesearch' )
		);

		if ( $placeholder ) {
			$options = array_merge( array( '' => ( $placeholder !== true ? $placeholder : __( 'Open Hours', 'geodiradvancesearch' ) ) ), $options );
		}

		return apply_filters( 'geodir_business_hours_options', $options, $placeholder );
	}

	public static function on_post_saved( $data, $gd_post, $post, $update = false ) {
		if ( ! empty( $data ) && in_array( 'business_hours', array_keys( $data ) ) ) {
			if ( ! empty( $data['country'] ) ) {
				$country = $data['country'];
			} elseif ( ! empty( $gd_post['country'] ) ) {
				$country = $gd_post['country'];
			} elseif ( GeoDir_Post_types::supports( $post->post_type, 'location' ) ) {
				$country = geodir_get_post_meta( $post->ID, 'country', true );
			} else {
				$country = geodir_get_option( 'default_location_country' );
			}
			self::save_post_business_hours( $post->ID, $data['business_hours'], $country );
		}
	}

	public static function save_post_business_hours( $post_id, $value = NULL, $country = '' ) {
		global $wpdb;

		if ( $value === NULL ) {
			$value = geodir_get_post_meta( $post_id, 'business_hours', true );
		}

		// Delete existing business hours for the post.
		self::delete_post_business_hours( $post_id );

		$value = stripslashes_deep( $value );
		if ( empty( $value ) ) {
			return;
		}

		$business_hours = geodir_get_business_hours( $value, $country );
		if ( empty( $business_hours['days'] ) ) {
			return;
		}

		$timezone_string = ! empty( $business_hours['extra']['timezone_string'] ) ? $business_hours['extra']['timezone_string'] : geodir_timezone_string();
		$has_dst = ! empty( $business_hours['extra']['has_dst'] ) ? 1 : 0;
		$is_dst = $has_dst && ! empty( $business_hours['extra']['is_dst'] ) ? 1 : 0;
		$offset = ! empty( $business_hours['extra']['offset'] ) ? round( $business_hours['extra']['offset'] / 60 ) : 0;

		$rows = array();

		foreach ( $business_hours['days'] as $day => $info ) {
			if ( ! empty( $info['slots'] ) ) {
				foreach ( $info['slots'] as $slot ) {
					if ( ! empty( $slot['minutes'] ) ) {
						$open = $slot['minutes'][0];
						$close = $slot['minutes'][1];

						// UTC
						$open_utc = ! empty( $slot['utc_minutes'] ) ? $slot['utc_minutes'][0] : $open;
						$close_utc = ! empty( $slot['utc_minutes'] ) ? $slot['utc_minutes'][1] : $close;

						// UTC + DST
						$open_dst = $has_dst && ! empty( $slot['utc_minutes_dst'] ) ? $slot['utc_minutes_dst'][0] : $open_utc;
						$close_dst = $has_dst && ! empty( $slot['utc_minutes_dst'] ) ? $slot['utc_minutes_dst'][1] : $close_utc;

						$open = $is_dst ? $open_dst : $open_utc;
						$close = $is_dst ? $close_dst : $close_utc;

						// Offset
						$open_off = ! empty( $slot['minutes'][0] ) ? $slot['minutes'][0] : $open;
						$close_off = ! empty( $slot['minutes'][1] ) ? $slot['minutes'][1] : $close;

						$rows[] = array(
							'open' => $open,
							'close' => $close,
							'open_utc' => $open_utc,
							'close_utc' => $close_utc,
							'open_dst' => $open_dst,
							'close_dst' => $close_dst,
							'open_off' => $open_off,
							'close_off' => $close_off,
							'offset' => $offset
						);
					}
				}
			}
		}

		$return = false;

		if ( ! empty( $rows ) ) {
			usort( $rows, array( __CLASS__, 'sort_business_hours' ) );

			$sql_parts = array();

			foreach ( $rows as $row ) {
				$sql_parts[] = "(" . (int) $post_id . ", " . (int) $row['open'] . ", " . (int) $row['close'] . ", " . (int) $row['open_utc'] . ", " . (int) $row['close_utc'] . ", " . (int) $row['open_dst'] . ", " . (int) $row['close_dst'] . ", " . (int) $row['open_off'] . ", " . (int) $row['close_off'] . ", " . (int) $row['offset'] . ", '" . $timezone_string . "', " . (int) $has_dst . ", " . (int) $is_dst . ")";
			}

			$return = $wpdb->query( "INSERT INTO `" . GEODIR_BUSINESS_HOURS_TABLE . "` (`post_id`, `open`, `close`, `open_utc`, `close_utc`, `open_dst`, `close_dst`, `open_off`, `close_off`, `offset`, `timezone_string`, `has_dst`, `is_dst`) VALUES " . implode( ', ', $sql_parts ) );

			do_action( 'geodir_post_business_hours_saved', $post_id, $value );
		}

		return $return;
	}

	public static function on_delete_post( $post_id, $post_type = '' ) {
		global $wpdb;

		if ( empty( $post_id ) ) {
			return false;
		}

		if ( empty( $post_type ) ) {
			$post_type = get_post_type( $post_id );
		}

		if ( ! geodir_is_gd_post_type( $post_type ) ) {
			return false;
		}

		return self::delete_post_business_hours( $post_id );
	}

	public static function delete_post_business_hours( $post_id ) {
		global $wpdb;

		do_action( 'geodir_pre_delete_post_business_hours', $post_id );

		$return = $wpdb->delete( GEODIR_BUSINESS_HOURS_TABLE, array( 'post_id' => $post_id ), array( '%d' ) );

		do_action( 'geodir_post_business_hours_deleted', $post_id );

		return $return;
	}

	public static function sort_business_hours( $item1, $item2 ) {
		return ( $item1['open_utc'] <= $item2['open_utc'] ? -1 : 1 );
	}

	public static function search_posts_join( $posts_join, $post_type, $wp_query ) {
		if ( isset( $_REQUEST['sopen_now'] ) && $_REQUEST['sopen_now'] != '' && GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
			if ( $join = self::business_hours_join( $post_type ) ) {
				$posts_join .= $join;
			}
		}

		return $posts_join;
	}

	public static function search_posts_field_where( $field_where, $htmlvar_name, $field, $post_type, $wp_query ) {
		if ( $htmlvar_name == 'business_hours' && isset( $_REQUEST['sopen_now'] ) && $_REQUEST['sopen_now'] != '' && GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
			if ( $condition = self::business_hours_condition( sanitize_text_field( $_REQUEST['sopen_now'] ), NULL, '' ) ) {
				if ( $field_where != '' ) {
					$field_where .= " AND ";
				}

				$field_where .= trim( $condition );
			}
		}

		return $field_where;
	}

	public static function search_posts_groupby( $groupby, $post_type, $wp_query ) {
		global $wpdb;

		if ( isset( $_REQUEST['sopen_now'] ) && $_REQUEST['sopen_now'] != '' && GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
			if ( strpos( $groupby, "{$wpdb->posts}.ID" ) !== false ) {
				return $groupby;
			}

			if ( trim( $groupby ) != '' ) {
				$groupby .= ", ";
			} else {
				$groupby = "";
			}

			$groupby .= "{$wpdb->posts}.ID";
		}

		return $groupby;
	}

	public static function widget_posts_query_args( $query_args, $instance ) {
		if ( ! empty( $instance['is_open'] ) && $instance['is_open'] != '' ) {
			$query_args['is_open'] = $instance['is_open'];
		}

		return $query_args;
	}

	public static function widget_posts_join( $join, $post_type ) {
		global $gd_query_args_widgets;

		if ( ! empty( $gd_query_args_widgets ) && isset( $gd_query_args_widgets['is_open'] ) && $gd_query_args_widgets['is_open'] != '' && GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
			if ( $_join = self::business_hours_join( $post_type ) ) {
				$join .= $_join;
			}
		}

		return $join;
	}

	public static function widget_posts_where( $where, $post_type ) {
		global  $gd_query_args_widgets;

		if ( ! empty( $gd_query_args_widgets ) && isset( $gd_query_args_widgets['is_open'] ) && $gd_query_args_widgets['is_open'] != '' && GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
			if ( $condition = self::business_hours_condition( $gd_query_args_widgets['is_open'] ) ) {
				$where .= $condition;
			}
		}

		return $where;
	}

	public static function business_hours_join( $filter = 'now', $alias = NULL ) {
		global $wpdb;

		if ( $alias === NULL ) {
			$alias = GEODIR_BUSINESS_HOURS_TABLE;
		}

		$join = " LEFT JOIN {$alias} ON ( {$alias}.post_id = {$wpdb->posts}.ID ) ";

		return apply_filters( 'geodir_search_business_hours_join', $join, $filter, $alias );
	}

	public static function business_hours_condition( $filter = 'now', $alias = NULL, $operator = 'AND' ) {
		if ( $alias === NULL ) {
			$alias = GEODIR_BUSINESS_HOURS_TABLE;
		}

		if ( ! empty( $alias ) ) {
			$alias = $alias . '.';
		}

		$days = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );

		$condition = '';

		if ( in_array( $filter, $days ) ) {
			$day_no = array_search( $filter, array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ) ) + 1;

			$open = geodir_hhmm_to_bh_minutes( '00:00', $day_no );
			$close = $open + ( 24 * 60 ) - 1;

			$condition = "( ( {$open} <= {$alias}open_off AND {$alias}open_off <= {$close} ) OR ( {$open} <= {$alias}close_off AND {$alias}close_off <= {$close} ) )";
		} elseif ( $filter == 'weekend' ) {
			$condition .= "( ( ";
			$sat_open = geodir_hhmm_to_bh_minutes( '00:00', 6 );
			$sat_close = $sat_open + ( 24 * 60 ) - 1;

			$condition .= "( {$sat_open} <= {$alias}open_off AND {$alias}open_off <= {$sat_close} ) OR ( {$sat_open} <= {$alias}close_off AND {$alias}close_off <= {$sat_close} )";

			$condition .= " ) OR ( ";

			$sun_open = $sat_close + 1;
			$sun_close = $sun_open + ( 24 * 60 ) - 1;

			$condition .= "( {$sun_open} <= {$alias}open_off AND {$alias}open_off <= {$sun_close} ) OR ( {$sun_open} <= {$alias}close_off AND {$alias}close_off <= {$sun_close} )";

			$condition .= " ) )";
		} else {
			if ( $filter == 'now' ) {
				$time = geodir_hhmm_to_bh_minutes( gmdate( 'H:i' ), gmdate( 'N' ) );
			} else {
				$time = absint( $filter );
			}
			$condition = "( {$alias}open <= {$time} AND {$alias}close >= {$time} )";
		}

		$condition = apply_filters( 'geodir_search_business_hours_condition', $condition, $filter, $alias, $operator );
		$condition = trim( $condition );

		if ( $condition != '' ) {
			$condition = " {$operator} {$condition}";
		}

		return $condition;
	}

	public static function search_business_hours_searched_param( $params, $post_type, $fields ) {
		global $aui_bs5;

		if ( isset( $_REQUEST['sopen_now'] ) && $_REQUEST['sopen_now'] != '' && GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
			$options = GeoDir_Adv_Search_Business_Hours::business_hours_options();
			$field = GeoDir_Adv_Search_Fields::get_field_by_name( 'business_hours', $post_type );

			if ( ! empty( $field->frontend_title ) ) {
				$frontend_title = __( stripslashes( $field->frontend_title ), 'geodirectory' );
			} else {
				$frontend_title = __( 'Open Hours', 'geodiradvancesearch' );
			}
			$htmlvar_name = 'open_now';

			$this_search = '';
			if ( isset( $options[ $_REQUEST['sopen_now'] ] ) ) {
				$this_search = $options[ $_REQUEST['sopen_now'] ];

				if ( $_REQUEST['sopen_now'] == 'now' ) {
					$frontend_title = $this_search;
					$this_search = '';
				} else {
					$frontend_title .= ': ';
				}
			}

			$design_style = geodir_design_style();

			$label_class = 'gd-adv-search-label';
			$sublabel_class = 'gd-adv-search-label-t';
			if ( $design_style ) {
				$label_class .= ' badge c-pointer ' . ( $aui_bs5 ? 'bg-info me-2' : 'badge-info mr-2' );
				$sublabel_class .= ' mb-0 c-pointer ' . ( $aui_bs5 ? 'me-1' : 'mr-1' );
			}

			$params[] = '<label class="' . $label_class . ' gd-adv-search-range gd-adv-search-' . esc_attr( $htmlvar_name ) . '" data-name="s' . esc_attr( $htmlvar_name ) . '" data-value="' . esc_attr( sanitize_text_field( $_REQUEST['sopen_now'] ) ) . '"><i class="fas fa-times" aria-hidden="true"></i> <label class="' . $sublabel_class . '">' . $frontend_title . '</label>' . $this_search . '</label>';
		}

		return $params;
	}

	/**
	 * Merge keywords for CPT posts.
	 *
	 * @param string $post_type The post type.
	 * @param bool $force True to merge business hours for all posts. Default False.
	 * @return int No. of keywords merged.
	 */
	public static function cpt_merge_business_hours( $post_type, $force = false ) {
		global $wpdb;

		$merged = 0;
		if ( ! GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
			return $merged;
		}

		$table = geodir_db_cpt_table( $post_type );

		$force = apply_filters( 'geodir_tool_force_merge_cpt_business_hours', $force, $post_type );

		if ( $force ) {
			$country_field = GeoDir_Post_types::supports( $post_type, 'location' ) ? ", country" : "";
			$results = $wpdb->get_results( "SELECT post_id, business_hours{$country_field} FROM `{$table}` WHERE `business_hours` != '' AND `business_hours` IS NOT NULL" );
		} else {
			$country_field = GeoDir_Post_types::supports( $post_type, 'location' ) ? ", pd.country" : "";
			$results = $wpdb->get_results( "SELECT DISTINCT pd.post_id, pd.business_hours{$country_field} FROM `{$table}` AS pd LEFT JOIN `" . GEODIR_BUSINESS_HOURS_TABLE . "` AS bh ON bh.post_id = pd.post_id WHERE pd.`business_hours` != '' AND pd.`business_hours` IS NOT NULL AND ( bh.post_id IS NULL OR ( bh.open_off = 0 AND bh.close_off = 0 ) )" );
		}

		if ( ! empty( $results ) ) {
			$country = geodir_get_option( 'default_location_country' );

			foreach ( $results as $k => $row ) {
				$country = ! empty( $row->country ) ? $row->country : $country;
				$result = GeoDir_Adv_Search_Business_Hours::save_post_business_hours( $row->post_id, $row->business_hours, $country );

				if ( $result ) {
					$merged++;
				}
			}
		}

		return $merged;
	}

	public static function tool_merge_business_hours( $tools ) {
		global $wpdb;

		$post_types = geodir_get_posttypes();

		$merge = false;

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				if ( ! GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
					continue;
				}

				$table = geodir_db_cpt_table( $post_type );

				$force = apply_filters( 'geodir_tool_force_merge_cpt_business_hours', false, $post_type );

				if ( $force ) {
					$results = $wpdb->get_results( "SELECT post_id FROM `{$table}` WHERE `business_hours` != '' AND `business_hours` IS NOT NULL LIMIT 1" );
				} else {
					$results = $wpdb->get_results( "SELECT DISTINCT pd.post_id FROM `{$table}` AS pd LEFT JOIN `" . GEODIR_BUSINESS_HOURS_TABLE . "` AS bh ON bh.post_id = pd.post_id WHERE pd.`business_hours` != '' AND pd.`business_hours` IS NOT NULL AND ( bh.post_id IS NULL OR ( bh.open_off = 0 AND bh.close_off = 0 ) ) LIMIT 1" );
				}

				if ( ! empty( $results ) ) {
					$merge = true;
					break;
				}
			}
		}

		if ( $merge ) {
			$tools['merge_business_hours'] = array(
				'name' => __( 'Merge Business Hours', 'geodiradvancesearch' ),
				'button' => __( 'Run', 'geodiradvancesearch' ),
				'desc' => __( 'Merge post business hours to enhance Open Hours searching.', 'geodiradvancesearch' ),
				'callback' => 'geodir_search_tool_merge_business_hours'
			);
		}

		if ( self::get_dst_timezones() ) {
			$tools['adjust_business_hours_dst'] = array(
				'name' => __( 'Adjust Business Hours with DST', 'geodiradvancesearch' ),
				'button' => __( 'Run', 'geodiradvancesearch' ),
				'desc' => __( 'Adjust listing business open/close hours with daylight saving time(DST) offset for timezones which observes daylight saving time(DST).', 'geodiradvancesearch' ),
				'callback' => 'geodir_search_tool_adjust_business_hours_dst'
			);
		}

		return $tools;
	}

	public static function gd_listings_widget_business_hours( $arguments, $options, $instance ) {
		if ( isset( $options['base_id'] ) && $options['base_id'] == 'gd_listings' ) {
			$post_types = geodir_get_posttypes();

			$conditions = array();
			if ( ! empty( $post_types ) ) {
				foreach ( $post_types as $post_type ) {
					if ( GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
						$conditions[] = '[%post_type%]=="' . $post_type . '"';
					}
				}
			}

			if ( ! empty( $conditions ) ) {
				$conditions = implode( ' || ', $conditions );

				$arguments['is_open'] = array(
					'title' => __( 'Open Hours', 'geodiradvancesearch' ),
					'desc' => __( 'Filter posts by open hours.', 'geodiradvancesearch' ),
					'type' => 'select',
					'options' => GeoDir_Adv_Search_Business_Hours::business_hours_options( true ),
					'default' => '',
					'desc_tip' => true,
					'advanced' => true,
					'group' => __( 'Filters', 'geodirectory' ),
					'element_require' => $conditions,
				);
			}
		}

		return $arguments;
	}

	public static function get_dst_timezones() {
		global $wpdb;

		return $wpdb->get_col( "SELECT DISTINCT `timezone_string` FROM `" . GEODIR_BUSINESS_HOURS_TABLE . "` WHERE `has_dst` = 1" );
	}

	public static function dst_in_out_timezones( $timezones = array() ) {
		$_timezones = timezone_identifiers_list();

		if ( ! ( is_array( $timezones ) && ! empty( $timezones ) ) ) {
			$timezones = $_timezones;
		}

		$dsc_inout = array(
			'in' => array(),
			'out' => array(),
		);

		foreach ( $timezones as $key => $tzstring ) {
			if ( ! in_array( $tzstring, $_timezones ) ) {
				continue;
			}

			$transitions = timezone_transitions_get( timezone_open( $tzstring ), time() );

			// Don't have DST
			if ( count( $transitions ) > 1 && ( ! empty( $transitions[0]['isdst'] ) || ! empty( $transitions[1]['isdst'] ) ) ) {
				if ( ! empty( $transitions[0]['isdst'] ) ) {
					$dsc_inout['in'][] = $tzstring;
				} else {
					$dsc_inout['out'][] = $tzstring;
				}
			}
		}

		return $dsc_inout;
	}

	public static function adjust_business_hours_dst() {
		global $wpdb;

		$updated = 0;
		$timezones = self::get_dst_timezones();

		if ( empty( $timezones ) ) {
			return $updated;
		}

		$dst_data = self::dst_in_out_timezones( $timezones );
		if ( empty( $dst_data ) ) {
			return $updated;
		}

		// DST Out => In
		if ( ! empty( $dst_data['in'] ) ) {
			$_where = count( $dst_data['in'] ) > 1 ? "`timezone_string` IN ('" . implode( "','", $dst_data['in'] ). "')" : "`timezone_string` = '" . $dst_data['in'][0] . "'";

			$updated += (int) $wpdb->query( "UPDATE `" . GEODIR_BUSINESS_HOURS_TABLE . "` SET `open` = `open_dst`, `close` = `close_dst`, `is_dst` = 1 WHERE `has_dst` = 1 AND `is_dst` = 0 AND {$_where}" );
		}

		// DST In => Out
		if ( ! empty( $dst_data['out'] ) ) {
			$_where = count( $dst_data['out'] ) > 1 ? "`timezone_string` IN ('" . implode( "','", $dst_data['out'] ). "')" : "`timezone_string` = '" . $dst_data['out'][0] . "'";

			$updated += (int) $wpdb->query( "UPDATE `" . GEODIR_BUSINESS_HOURS_TABLE . "` SET `open` = `open_utc`, `close` = `close_utc`, `is_dst` = 0 WHERE `has_dst` = 1 AND `is_dst` = 1 AND {$_where}" );
		}

		if ( $updated ) {
			do_action( 'geodir_search_business_hours_dst_adjusted' );
		}

		return $updated;
	}
}
