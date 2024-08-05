<?php
/**
 * GeoDirectory Custom Post Types API
 *
 * Handles GD-API endpoint requests.
 *
 * @author   GeoDirectory
 * @category API
 * @package  GeoDir_Custom_Posts/API
 * @since    2.0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeoDir_CP_API {

	/**
	 *
	 * @since 2.0.1.0
	 */
	public function __construct() {
	}

	/**
	 *
	 * @since 2.0.1.0
	 */
	public static function init() {
		$post_types = geodir_get_posttypes( 'names' );

		foreach ( $post_types as $post_type ) {
			add_filter( 'rest_' . $post_type . '_collection_params', array( __CLASS__, 'linked_posts_collection_params' ), 11, 2 );
			add_filter( 'rest_' . $post_type . '_query', array( __CLASS__, 'linked_posts_query' ), 11, 2 );
		}

		add_filter( 'geodir_rest_post_custom_fields_schema', array( __CLASS__, 'linked_posts_schema' ), 11, 6 );
		add_filter( 'geodir_listing_fields_args', array( __CLASS__, 'linked_posts_args' ), 11, 2 );
		add_filter( 'geodir_rest_get_post_data', array( __CLASS__, 'linked_posts_data' ), 11, 4 );
		add_action( 'pre_get_posts', array( __CLASS__, 'rest_hooks' ), 11, 1 );
	}

	public static function rest_hooks( $query ) {
		if ( GeoDir_API::is_rest( $query ) ) {
			add_filter( 'geodir_rest_posts_clauses_where', array( __CLASS__, 'linked_posts_where' ), 11, 3 );
		}
	}

	public static function linked_posts_collection_params( $params, $post_type_obj ) {
		if ( empty( $post_type_obj ) ) {
			return $params;
		}

		if ( ! empty( $post_type_obj->name ) && geodir_is_gd_post_type( $post_type_obj->name ) ) {
			$linked_to_post_types = GeoDir_CP_Link_Posts::linked_to_post_types( $post_type_obj->name );
			if ( ! empty( $linked_to_post_types ) ) {
				$params['linked_from_post'] = array(
					'description' => __( 'Limit the posts to those a post linked form.', 'geodir_custom_posts' ),
					'type' => 'string',
				);
			}

			$link_from_post_types = GeoDir_CP_Link_Posts::linked_from_post_types( $post_type_obj->name );
			if ( ! empty( $link_from_post_types ) ) {
				$params['linked_to_post'] = array(
					'description' => __( 'Limit the posts to those a post linked to.', 'geodir_custom_posts' ),
					'type' => 'string',
				);
			}
		}

		return $params;
	}

	public static function linked_posts_query( $args, $request ) {
		$mappings = array(
			'linked_from_post' => 'linked_from_post',
			'linked_to_post' => 'linked_to_post'
		);

		$post_type_obj = ! empty( $args['post_type'] ) ? get_post_type_object( $args['post_type'] ) : array();

		$collection_params = self::linked_posts_collection_params( array(), $post_type_obj ) ;

		foreach ( $collection_params as $key => $param ) {
			if ( isset( $request[ $key ] ) ) {
				$field = isset( $mappings ) ? $mappings[ $key ] : $key;
				$args[ $field ] = $request[ $key ];
			}
		}

		return $args;
	}

	/**
	 *
	 * @since 2.0.1.0
	 */
	public static function linked_posts_where( $where, $wp_query, $post_type ) {
		if ( ! GeoDir_API::is_rest( $wp_query ) ) {
			return $where;
		}

		$_where = '';
		if ( ! empty( $wp_query->query_vars['linked_to_post'] ) && ( $post_id = absint( $wp_query->query_vars['linked_to_post'] ) ) ) {
			$_where = GeoDir_CP_Link_Posts::linked_post_condition( 'to', $post_id, '', 0, $post_type );
		} elseif ( ! empty( $wp_query->query_vars['linked_from_post'] ) && ( $post_id = absint( $wp_query->query_vars['linked_from_post'] ) ) ) {
			$_where = GeoDir_CP_Link_Posts::linked_post_condition( 'from', 0, $post_type, $post_id, '' );
		}

		if ( $_where != '' ) {
			$where .= " AND {$_where}";
		}

		return $where;
	}

	/**
	 *
	 * @since 2.0.1.0
	 */
	public static function linked_posts_schema( $args, $post_type, $field, $custom_fields, $package_id, $default ) {
		if ( $field['type'] == 'link_posts' ) {
			if ( ! geodir_is_gd_post_type( $field['name'] ) ) {
				return array();
			}

			$extra_fields = ! empty( $field['extra_fields'] ) ? maybe_unserialize( $field['extra_fields'] ) : array();
			$max_posts = ! empty( $extra_fields ) && isset( $extra_fields['max_posts'] ) ? absint( $extra_fields['max_posts'] ) : 1;

			if ( $max_posts == 1 ) {
				$args['type'] = 'integer';
			} else {
				$args['type'] = 'array';
			}
		}

		return $args;
	}

	/**
	 *
	 * @since 2.0.1.0
	 */
	public static function linked_posts_args( $args, $field ) {
		if ( $field['type'] == 'link_posts' && geodir_is_gd_post_type( $field['name'] ) ) {
			$args['arg_options']['validate_callback'] = array( __CLASS__, 'validate_linked_posts' );
			$args['arg_options']['sanitize_callback'] = array( __CLASS__, 'sanitize_linked_posts' );
		}

		return $args;
	}

	/**
	 *
	 * @since 2.0.1.0
	 */
	public static function validate_linked_posts( $value, $request, $param ) {
		return true;
	}

	/**
	 *
	 * @since 2.0.1.0
	 */
	public static function sanitize_linked_posts( $value, $request, $param ) {
		return $value;
	}

	/**
	 *
	 * @since 2.0.1.0
	 */
	public static function linked_posts_data( $data, $gd_post, $request, $controller ) {
		$linked_to_post_types = GeoDir_CP_Link_Posts::linked_to_post_types( $gd_post->post_type );
		$link_from_post_types = GeoDir_CP_Link_Posts::linked_from_post_types( $gd_post->post_type );

		if ( ! empty( $linked_to_post_types ) || ! empty( $link_from_post_types ) ) {
			$linked_posts = array();

			// Post type linked to post types
			if ( ! empty( $linked_to_post_types ) ) {
				$linked_to = array();

				foreach ( $linked_to_post_types as $post_type ) {
					$_link_posts = array();

					$posts = GeoDir_CP_Link_Posts::get_items( $gd_post->ID, $post_type );

					if ( ! empty( $posts ) ) {
						foreach ( $posts as $_post ) {
							if ( get_post_status( $_post->linked_id ) == 'publish' ) {
								$_link_posts[] = array(
									'id' => $_post->linked_id,
									'title' => get_the_title( $_post->linked_id ),
									'href' => get_permalink( $_post->linked_id )
								);
							}
						}
					}

					$linked_to[ $post_type ] = $_link_posts;
				}
				$linked_posts['linked_to'] = $linked_to;
			}

			// Post type linked from post types
			if ( ! empty( $link_from_post_types ) ) {
				$linked_from = array();

				foreach ( $link_from_post_types as $post_type ) {
					$_link_posts = array();

					$posts = GeoDir_CP_Link_Posts::get_items( 0, $gd_post->post_type, $gd_post->ID, $post_type );

					if ( ! empty( $posts ) ) {
						foreach ( $posts as $_post ) {
							if ( get_post_status( $_post->post_id ) == 'publish' ) {
								$_link_posts[] = array(
									'id' => $_post->post_id,
									'title' => get_the_title( $_post->post_id ),
									'href' => get_permalink( $_post->post_id )
								);
							}
						}
					}

					$linked_from[ $post_type ] = $_link_posts;
				}
				$linked_posts['linked_from'] = $linked_from;
			}

			$data['linked_posts'] = $linked_posts;
		}

		return $data;
	}
}
