<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Custom Post Types Widgets class
 *
 * @class       GeoDir_CP_Widgets
 * @version     2.0.0
 * @package     GeoDir_Custom_Posts/Widgets
 * @category    Class
 * @author      AyeCode Ltd
 */
class GeoDir_CP_Widgets {

	public static function init() {
		add_filter( 'wp_super_duper_arguments', array( __CLASS__, 'super_duper_arguments' ), 1, 3 );
		add_filter( 'geodir_widget_gd_listings_view_all_url', array( __CLASS__, 'gd_listings_view_all_url' ), 99, 5 );
	}

	public static function super_duper_arguments( $arguments, $options, $instance = array() ) {
		if ( ! empty( $options['textdomain'] ) && $options['textdomain'] == GEODIRECTORY_TEXTDOMAIN ) {
			if ( $options['base_id'] == 'gd_listings' || $options['base_id'] == 'gd_linked_posts' ) {
				if ( ! empty( $arguments['category'] ) && ! empty( $instance['post_type'] ) ) {
					$arguments['category']['options'] = geodir_category_options( $instance['post_type'] );
				}
				if ( ! empty( $arguments['sort_by'] ) && ! empty( $instance['post_type'] ) ) {
					$arguments['sort_by']['options'] = geodir_sort_by_options( $instance['post_type'] );
				}
			}

			// GD Listings linked posts option.
			if ( $options['base_id'] == 'gd_listings' ) {
				$arguments['linked_posts'] = array(
					'type' => 'select',
					'title' => __( 'Linked Posts', 'geodir_custom_posts' ),
					'desc' => __( 'Filter posts linked from or linked to current post.', 'geodir_custom_posts' ),
					'options' => array(
						'' => __( 'No filter', 'geodir_custom_posts' ),
						'from' => __( 'Linked from current post', 'geodir_custom_posts' ),
						'to' => __( 'Linked to current post', 'geodir_custom_posts' ),
					),
					'default' => '',
					'desc_tip' => true,
					'advanced' => true,
					'group' => __( 'Filters', 'geodirectory' ),
				);

				$arguments['linked_post_id'] = array(
					'type' => 'number',
					'title' => __( 'Linked Post ID', 'geodir_custom_posts' ),
					'desc' => __( 'Filter posts linked from or linked to this post id. Leave blank to use current post id.', 'geodir_custom_posts' ),
					'placeholder' => __( 'Leave blank to use current post id.', 'geodir_custom_posts' ),
					'default' => '',
					'desc_tip' => true,
					'advanced' => true,
					'group' => __( 'Filters', 'geodirectory' ),
					'element_require' => '[%linked_posts%]!=""',
				);
			}
		}
		return $arguments;
	}

	public static function gd_listings_view_all_url( $url, $query_args, $instance, $_args, $widget ) {
		global $geodirectory;

		if ( ! empty( $query_args['linked_from_post'] ) || ! empty( $query_args['linked_to_post'] ) ) {
			if ( ! empty( $query_args['linked_to_post'] ) ) {
				$link_type = 'linked_to_post';
				$post_id = absint( $query_args['linked_to_post'] );
			} else {
				$link_type = 'linked_from_post';
				$post_id = absint( $query_args['linked_from_post'] );
			}

			$args = array(
				'geodir_search' => 1,
				'stype' => $query_args['post_type'],
				's' => '',
				$link_type => $post_id
			);

			// @todo move to EM
			if ( ! empty( $query_args['event_type'] ) && GeoDir_Post_types::supports( $query_args['post_type'], 'events' ) ) {
				$args['etype'] = $query_args['event_type'];
			}

			// @todo move to LM
			if ( ! empty( $query_args['gd_location'] ) && GeoDir_Post_types::supports( $query_args['post_type'], 'location' ) ) {
				$location = ! empty( $geodirectory->location ) ? $geodirectory->location : array();

				// Country
				if ( ! empty( $location->country ) ) {
					$args['country'] = $location->country;
				} elseif ( ! empty( $query_args['country'] ) && ! empty( $location ) && ( $country = $location->get_country_name_from_slug( $query_args['country'] ) ) ) {
					$args['country'] = $country;
				}

				// Region
				if ( ! empty( $location->region ) ) {
					$args['region'] = $location->region;
				} elseif ( ! empty( $query_args['region'] ) && ! empty( $location ) && ( $region = $location->get_region_name_from_slug( $query_args['region'] ) ) ) {
					$args['region'] = $region;
				}

				// City
				if ( ! empty( $location->city ) ) {
					$args['city'] = $location->city;
				} elseif ( ! empty( $query_args['city'] ) && ! empty( $location ) && ( $city = $location->get_city_name_from_slug( $query_args['city'] ) ) ) {
					$args['city'] = $city;
				}

				// Neighbourhood
				if ( class_exists( 'GeoDir_Location_Neighbourhood' ) && GeoDir_Location_Neighbourhood::is_active() ) {
					if ( ! empty( $location->neighbourhood ) ) {
						$args['neighbourhood'] = $location->neighbourhood;
					} elseif ( ! empty( $query_args['neighbourhood'] ) ) {
						$args['neighbourhood'] = $query_args['neighbourhood'];
					}
				}
			}

			$args = apply_filters( 'geodir_cp_linked_posts_view_all_url_args', $args, $url, $query_args, $instance, $_args, $widget );

			$url = add_query_arg( $args, geodir_search_page_base_url() );
		}

		return $url;
	}
}
