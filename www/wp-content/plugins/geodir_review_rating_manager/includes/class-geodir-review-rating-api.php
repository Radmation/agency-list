<?php
/**
 * GeoDirectory Review Rating Manager API
 *
 * Handles GD-API endpoint requests.
 *
 * @author   GeoDirectory
 * @category API
 * @package  GeoDir_Review_Rating_Manager/API
 * @since    2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeoDir_Review_Rating_API {

	/**
	 * Setup class.
	 * @since 2.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'rest_api_init' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ), 102 );
	}

	public static function rest_api_init() {
		if ( ! GeoDir_API::is_rest() ) {
			return;
		}

		if ( ! geodir_get_option( 'rr_enable_rating' ) ) {
			return;
		}

		self::setup_additional_fields();

		remove_action( 'rest_insert_comment', array( 'GeoDir_API', 'rest_insert_comment' ), 1, 3 );
		add_action( 'rest_insert_comment', array( __CLASS__, 'rest_insert_comment' ), 1, 3 );
		add_filter( 'geodir_rest_review_schema', array( __CLASS__, 'rest_review_schema' ), 20, 1 );
		add_filter( 'geodir_rest_comment_collection_params', array( __CLASS__, 'rest_comment_collection_params' ), 20, 1 );
		add_filter( 'geodir_rest_reviews_normalize_query_param', array( __CLASS__, 'rest_reviews_normalize_query_param' ), 20, 3 );
		add_filter( 'comments_clauses', array( __CLASS__, 'comments_clauses' ), 20, 2 );
		add_filter( 'geodir_rest_prepare_review_data', array( __CLASS__, 'rest_prepare_review_data' ), 20, 5 );
	}

	public static function register_rest_routes() {
	}

	public static function rest_comment_collection_params( $query_params ) {
		if ( geodir_get_option( 'rr_enable_sorting' ) ) {
			$query_params['orderby']['enum'][] = 'rating';

			if ( geodir_get_option( 'rr_enable_images' ) ) {
				$query_params['orderby']['enum'][] = 'images';
			}

			if ( geodir_get_option( 'rr_enable_rate_comment' ) ) {
				$query_params['orderby']['enum'][] = 'likes';
			}

			$default_orderby = 'date';
			$default_order = 'desc';

			if ( get_option( 'page_comments' ) && ( $default_comments_page = get_option( 'default_comments_page' ) ) ) {
				if ( $default_comments_page == 'newest' ) {
					$default_order = 'desc';
				} else if ( $default_comments_page == 'oldest' ) {
					$default_order = 'asc';
				}
			}

			$default_order = apply_filters( 'geodir_review_rating_reviews_default_order', $default_order );
			$default_orderby = apply_filters( 'geodir_review_rating_reviews_default_orderby', $default_orderby );

			$query_params['order']['default'] = $default_order;
			$query_params['orderby']['default'] = $default_orderby;
		}

		return $query_params;
	}

	public static function review_additional_fields() {
		$fields = array();
		$fields['ratings'] = array(
			'schema' => array(
				'description'     => __( 'The ratings for the object.', 'geodir_reviewratings' ),
				'type'            => 'object',
				'context'         => array( 'view', 'edit' ),
				'arg_options'     => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database()
					'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database()
				),
				'properties'      => array(
					'raw'         => array(
						'description'     => __( 'Ratings for the object, as it exists in the database.', 'geodir_reviewratings' ),
						'type'            => 'string',
						'context'         => array( 'edit' ),
					),
					'rendered'    => array(
						'description'     => __( 'Ratings array for the object, transformed for display.', 'geodir_reviewratings' ),
						'type'            => 'array',
						'context'         => array( 'view', 'edit' ),
						'readonly'        => true,
					),
				),
			),
			'get_callback'      => array( __CLASS__, 'get_callback' ),
			'update_callback'   => null
		);

		if ( geodir_get_option( 'rr_enable_images' ) ) {
			$fields['images'] = array(
				'schema' => array(
					'description'     => __( 'The images for the object.', 'geodir_reviewratings' ),
					'type'            => 'object',
					'context'         => array( 'view', 'edit' ),
					'arg_options'     => array(
						'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database()
						'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database()
					),
					'properties'      => array(
						'raw'         => array(
							'description'     => __( 'Images for the object, as it exists in the database.', 'geodir_reviewratings' ),
							'type'            => 'string',
							'context'         => array( 'edit' ),
						),
						'rendered'    => array(
							'description'     => __( 'Images array for the object, transformed for display.', 'geodir_reviewratings' ),
							'type'            => 'array',
							'context'         => array( 'view', 'edit' ),
							'readonly'        => true,
						),
					),
				),
				'get_callback'      => array( __CLASS__, 'get_callback' ),
				'update_callback'   => null
			);

			$fields['total_images'] = array(
				'schema' => array(
					'description'  => __( 'Total number of images attached with the review.', 'geodir_reviewratings' ),
					'type'         => 'integer',
					'context'      => array( 'view' ),
					'default'      => 0,
				),
				'get_callback'      => array( __CLASS__, 'get_callback' ),
				'update_callback'   => null
			);
		}

		if ( geodir_get_option( 'rr_enable_rate_comment' ) ) {
			$fields['likes'] = array(
				'schema' => array(
					'description'  => __( 'Number of likes for the reviews.', 'geodir_revieratings' ),
					'type'         => 'integer',
					'context'      => array( 'view' ),
					'default'      => 0,
				),
				'get_callback'      => array( __CLASS__, 'get_callback' ),
				'update_callback'   => null
			);
		}

		return $fields;
	}

	public static function rest_review_schema( $schema ) {
		$additional_fields = self::review_additional_fields();

		foreach ( $additional_fields as $key => $field ) {
			$schema['properties'][ $key ] = $field['schema'];
		}

		return $schema;
	}

	public static function get_callback( $object, $field_name, $request, $object_type ) {
		$value = is_array( $object ) && isset( $object[ $field_name ] ) ? $object[ $field_name ] : null;

		return $value;
	}

	public static function setup_additional_fields() {
		global $wp_rest_additional_fields;

		$wp_rest_additional_fields[ 'geodir_review' ] = self::review_additional_fields();
	}

	public static function rest_prepare_review_data( $data, $review, $comment, $schema, $request ) {
		if ( ! empty( $schema['properties']['ratings'] ) ) {
			$ratings = ! empty( $review->ratings ) ? maybe_unserialize( $review->ratings ) : array();
			$_ratings = array();
			if ( ! empty( $ratings ) && is_array( $ratings ) ) {
				foreach ( $ratings as $id => $rating ) {
					$style = self::get_rating_style( (int)$id );

					if ( empty( $style ) ) {
						continue;
					}

					$label = __( stripslashes_deep( $style->label ), 'geodirectory' );
					$rating_labels = geodir_reviewrating_star_lables_to_arr( $style->star_lables, $style->max_rating, true );

					if ( ! empty( $style->rating_cond ) ) {
						$params = array(
							'rating_icon' => $style->rating_icon,
							'rating_color' => $style->star_color,
							'rating_color_off' => $style->star_color_off,
							'rating_label' => '',
							'rating_texts' => $rating_labels,
							'rating_image' => $style->img_off,
							'rating_type' => $style->rating_type,
							'rating_input_count' => $style->max_rating,
							'id' => "geodir_rating[" . $style->id . "]",
							'type' => 'output',
						);
						$html = GeoDir_Comments::rating_html( (float) $rating, 'output', $params );
					} else {
						$html = '<div class="clearfix gd-rate-cat-in"><select name="geodir_rating[' . $style->id . ']">';
						for ( $star = 1; $star <= absint( $style->max_rating ); $star++ ) {
							$rating_label = isset( $rating_labels[ $star ] ) ? stripslashes_deep ( $rating_labels[ $star ] ) : '';
							$html .= '<option value="' . absint( $star ) . '" ' . selected( (int) $rating, (int) $star, false ) . '>' . esc_html( $rating_label ) . '</option>';
						}
						$html .= '</select></div>';
					}

					$_ratings[ $id ] = array(
						'id' => $id,
						'label' => $label,
						'rating' => (float) $rating,
						'max_rating' => absint( $style->max_rating ),
						'html' => $html
					);
				}
			}
			$data['ratings'] = array(
				'raw' => ! empty( $review->ratings ) ? $review->ratings : '',
				'rendered' => $_ratings,
			);
		}

		if ( ! empty( $schema['properties']['images'] ) || ! empty( $schema['properties']['total_images'] ) ) {
			$comment_images = self::get_comment_attachments( (int) $data['id'], (int) $data['post'] );

			if ( ! empty( $schema['properties']['images'] ) ) {
				$data['images'] = array(
					'rendered' => ! empty( $comment_images ) ? $comment_images : array(),
					'raw'      => ! empty( $review->attachments ) ? $review->attachments : '',
				);
			}

			if ( ! empty( $schema['properties']['total_images'] ) ) {
				$data['total_images'] = ! empty( $comment_images ) ? count( $comment_images ) : 0;
			}
		}

		if ( ! empty( $schema['properties']['likes'] ) ) {
			$data['likes'] = ! empty( $review->wasthis_review ) ? absint( $review->wasthis_review ) : 0;
		}

		return $data;
	}

	public static function get_comment_attachments( $comment_ID, $post_ID ) {
		$attachments = GeoDir_Media::get_attachments_by_type( (int) $post_ID, 'comment_images', '', '', (int) $comment_ID );
		
		$images = array();
		if ( ! empty( $attachments ) ) {
			foreach ( $attachments as $attachment ) {
				$row = array();
				$row['id'] = $attachment->ID;
				$row['title'] = $attachment->title;
				$row['src'] = geodir_get_image_src( $attachment, 'original' );
				$row['thumbnail'] = geodir_get_image_src( $attachment, 'thumbnail' );
				$row['featured'] = (bool) $attachment->featured;
				$row['position'] = $attachment->menu_order;

				$images[] = $row;
			}
		}
		return $images;
	}

	public static function rest_reviews_normalize_query_param( $normalized, $query_param, $prefix ) {
		switch ( $query_param ) {
			case 'rating':
			case 'images':
			case 'likes':
				$normalized = $query_param;
				break;
		}

		return $normalized;
	}

	public static function comments_clauses( $clauses, $comment_query ) {
		if ( empty( $comment_query->query_vars['geodir_rest_review'] ) ) {
			return $clauses;
		}

		$page_comments = get_option( 'page_comments' );
		$comment_order = get_option( 'comment_order' );
		$default_comments_page = get_option( 'default_comments_page' );
		$reverse = false;

		$_orderby = $comment_query->query_vars['orderby'];
		$order = strtoupper( $comment_query->query_vars['order'] );

		if ( $page_comments ) {
			if ( $default_comments_page == 'newest' ) {
				$reverse = true;
			}
		}

		switch( $_orderby ) {
			case 'rating':
				$orderby = 'r.rating';
				break;
			case 'images':
				$orderby = 'r.total_images';
				break;
			case 'likes':
				$orderby = 'r.wasthis_review';
				break;
			default:
				$orderby = '';
				break;
		}

		if ( ! $orderby ) {
			return $clauses;
		}

		if ( $reverse && $order == 'DESC' ) {
			$order = 'ASC';
		}

		$clauses['orderby'] = $orderby . ' ' . $order;
		$clauses['order'] = $order;

		if ( $orderby != 'comment_date_gmt' ) {
			$clauses['orderby'] .= ", comment_date_gmt ";

			if ( $reverse ) {
				$clauses['orderby'] .= 'ASC';
			} else {
				$clauses['orderby'] .= 'DESC';
			}
		}

		return $clauses;
	}

	/**
	 * Save review is submitted via the REST API.
	 *
	 * @since 2.0.0.12
	 *
	 * @param WP_Comment      $comment  Inserted or updated comment object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating True when creating a comment, false
	 *                                  when updating.
	 */
	public static function rest_insert_comment( $comment, $request, $creating ) {
		if ( empty( $comment->comment_post_ID ) ) {
			return;
		}

		if ( ! geodir_is_gd_post_type( get_post_type( (int) $comment->comment_post_ID ) ) ) {
			return;
		}

		if ( isset( $request['rating'] ) || isset( $request['ratings'] ) ) {
			if ( isset( $request['rating'] ) ) {
				$_REQUEST['geodir_overallrating'] = absint( $request['rating'] );
			}

			if ( isset( $request['ratings'] ) ) {
				$_ratings = array();
				if ( is_array( $request['ratings'] ) && ! empty( $request['ratings'] ) ) {
					foreach ( $request['ratings'] as $key => $rating ) {
						$_ratings[ absint( $key ) ] = absint( $rating );
					}
				}
				$_REQUEST['geodir_rating'] = $_ratings;
			}

			if ( isset( $request['images'] ) && geodir_get_option( 'rr_enable_images' ) ) {
				$comment_images = '';
				if ( ! empty( $request['images'] ) ) {
					$comment_images = is_array( $request['images'] ) ? implode( '::', $request['images'] ) : $request['images'];
				}
				$_POST['comment_images'] = $comment_images;
			}

			$review_rating_public = new GeoDir_Review_Rating_Manager_Public();
			$review_rating_public->geodir_reviewrating_save_rating( $comment->comment_ID, $comment->comment_approved, (array) $comment );
		}
	}

	public static function get_rating_style( $rating_id ) {
		global $wpdb;

		$style = wp_cache_get( "geodir_rating_review_api_style:" . $rating_id );
		if ( $style !== false ) {
			return $style;
		}

		$sql = $wpdb->prepare( "SELECT rt.id AS `id`, rt.title AS `label`, rt.post_type AS `post_type`, rt.category AS `category`, rt.check_text_rating_cond AS `rating_cond`, `rt`.`display_order`, rs.s_rating_type AS `rating_type`, rs.s_rating_icon AS `rating_icon`, rs.s_img_off AS `img_off`, rs.s_img_width AS `img_width`, rs.s_img_height AS `img_height`, rs.star_color AS `star_color`, rs.star_color_off AS `star_color_off`, rs.star_lables AS `star_lables`, rs.star_number AS `max_rating` FROM " . GEODIR_REVIEWRATING_STYLE_TABLE . " AS rs JOIN " . GEODIR_REVIEWRATING_CATEGORY_TABLE . " AS rt ON rt.category_id = rs.id WHERE rs.id = %d", $rating_id );
		$style = $wpdb->get_row( $sql );
		
		wp_cache_set( "geodir_rating_review_api_style:" . $rating_id, $style );

		return $style;
	}
}

new GeoDir_Review_Rating_API();