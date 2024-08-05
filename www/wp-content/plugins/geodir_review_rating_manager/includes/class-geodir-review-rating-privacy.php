<?php
/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 *
 * @since 1.4.1
 * @package GeoDirectory_Review_Rating_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * GeoDir_Review_Rating_Privacy Class.
 */
class GeoDir_Review_Rating_Privacy extends GeoDir_Abstract_Privacy {

	public function __construct() {
		parent::__construct( __( 'GeoDirectory Review Rating Manager', 'geodir_reviewratings' ) );

		if ( self::allow_export_comment_likes_data() ) {
			$this->add_exporter( 'geodirectory-comment-likes', __( 'GeoDirectory Comment Likes', 'geodir_reviewratings' ), array( __CLASS__, 'comment_likes_data_exporter' ) );
		}

		if ( self::allow_erase_comment_likes_data() ) {
			$this->add_eraser( 'geodirectory-comment-likes', __( 'GeoDirectory Comment Likes', 'geodir_reviewratings' ), array( __CLASS__, 'comment_likes_data_eraser' ) );
		}

		add_filter( 'geodir_privacy_export_post_personal_data', array( __CLASS__, 'export_post_review_data' ), 9, 2 );
		add_filter( 'geodir_privacy_export_review_data', array( __CLASS__, 'export_review_data' ), 10, 3 );
	}

	public static function allow_export_comment_likes_data() {
		$allow = true;

		return apply_filters( 'geodir_privacy_allow_export_comment_likes_data', $allow );
	}

	public static function allow_erase_comment_likes_data() {
		$allow = true;

		return apply_filters( 'geodir_privacy_allow_erase_comment_likes_data', $allow );
	}

	/**
	 * Export post review data.
	 *
	 * @since 1.4.1
	 * @param array   $personal_data Array of name value pairs to expose in the export.
	 * @param object  $gd_post The post object.
	 * @return array  Filtered data.
	 */
	public static function export_post_review_data( $personal_data, $gd_post ) {
		$post_ratings = geodir_reviewrating_get_post_rating( $gd_post->ID );

		if ( ! empty( $post_ratings ) ) {
			if ( isset( $post_ratings['overall'] ) ) {
				unset( $post_ratings['overall'] );
			}

			$rating_ids = array_keys( $post_ratings );

			if ( ! empty( $rating_ids ) && ( $rating_styles = self::get_rating_styles( $rating_ids ) ) ) {
				foreach ( $rating_styles as $style ) {
					if ( ! empty( $post_ratings[ $style->id ] ) ) {
						$ratings = $post_ratings[ $style->id ];
						$rating = ! empty( $ratings['c'] ) && ! empty( $ratings['r'] ) ? ( (float)$ratings['r'] / (float)$ratings['c'] ) : 0;
						$value = $rating > 0 ? round( $rating, 1 ) . ' / ' . $style->total : __( 'Not rated', 'geodir_reviewratings' );
						$personal_data[] = array(
							'name'  => wp_sprintf( __( 'Rating (%s)', 'geodir_reviewratings' ), __( stripslashes_deep( $style->title ), 'geodirectory' ) ),
							'value' => $value,
						);
					}
				}
			}
		}

		return $personal_data;
	}

	/**
	 * Export review data.
	 *
	 * @since 1.4.1
	 * @param array   $data Array of name value pairs to expose in the export.
	 * @param object  $review The review object.
	 * @param string  $email_address The user email address.
	 * @return array  Filtered data.
	 */
	public static function export_review_data( $data, $review, $email_address ) {
		$item_ratings = ! empty( $review->ratings ) ? maybe_unserialize( $review->ratings ) : array();
		if ( ! empty( $item_ratings ) && is_array( $item_ratings ) ) {
			if ( isset( $item_ratings['overall'] ) ) {
				unset( $item_ratings['overall'] );
			}

			$rating_ids = array_keys( $item_ratings );
			if ( ! empty( $rating_ids ) && ( $rating_styles = self::get_rating_styles( $rating_ids ) ) ) {
				foreach ( $rating_styles as $style ) {
					if ( ! empty( $item_ratings[ $style->id ] ) ) {
						$data[] = array(
							'name'  => wp_sprintf( __( 'Rating (%s)', 'geodir_reviewratings' ), __( stripslashes_deep( $style->title ), 'geodirectory' ) ),
							'value' => $item_ratings[ $style->id ] . ' / ' . $style->total,
						);
					}
				}
			}
		}

		if ( ! empty( $review->attachments ) ) {
            $comment_images = array();
			$attachments = explode( ',', $review->attachments );
			foreach ($attachments as $attachment_id){
                $comment_images[] = wp_get_attachment_url($attachment_id);
            }
			$data[] = array(
				'name'  => __( 'Review Images', 'geodir_reviewratings' ),
				'value' => GeoDir_Privacy_Exporters::parse_files_value( $comment_images ),
			);
		}

		return $data;
	}

	public static function get_rating_styles( $rating_ids = array() ) {
		global $wpdb;

		if ( empty( $rating_ids ) ) {
			return array();
		}

		return $wpdb->get_results( "SELECT rc.id, rc.title, rs.star_number AS total FROM `" . GEODIR_REVIEWRATING_CATEGORY_TABLE . "` AS rc LEFT JOIN `" . GEODIR_REVIEWRATING_STYLE_TABLE . "` AS rs ON rs.id = rc.category_id WHERE rc.id IN(" . implode( ',', $rating_ids ) . ") AND rs.id IS NOT NULL ORDER BY rc.display_order ASC, rc.id ASC" );
	}

	/**
	 * Finds and exports data which could be used to identify a person from GeoDirectory data associated with an email address.
	 *
	 * @since 1.4.1
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function comment_likes_data_exporter( $email_address, $page ) {
		$number			= 10;
		$done           = false;
		$page           = (int) $page;
		$data_to_export = array();

		$items 			= self::comment_likes_by_user( $email_address, $page, $number );

		if ( 0 < count( $items ) ) {
			foreach ( $items as $item ) {
				$data_to_export[] = array(
					'group_id'    => 'geodirectory-comment-likes',
					'group_label' => __( 'GeoDirectory Comment Likes', 'geodir_reviewratings' ),
					'item_id'     => 'gd-comment-like-' . $item->like_id,
					'data'        => self::get_comment_like_personal_data( $item ),
				);
			}
			$done = $number > count( $items );
		} else {
			$done = true;
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Erases personal data associated with an email address from the commment likes table.
	 *
	 * @since 1.4.1
	 *
	 * @param  string $email_address The author email address.
	 * @param  int    $page          Page number.
	 * @return array
	 */
	public static function comment_likes_data_eraser( $email_address, $page ) {
		global $wpdb;

		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		if ( empty( $email_address ) ) {
			return $response;
		}

		$number 		= 10;
		$page           = (int) $page;
		$items_removed  = false;
		$items_retained = false;

		$items = self::comment_likes_by_user( $email_address, $page, $number );

		if ( empty( $items ) ) {
			return $response;
		}

		$messages    = array();

		foreach ( $items as $item ) {
			$anonymized_item                         		= array();
			$anonymized_item['user_id']               		= 0;
			$anonymized_item['ip']    						= wp_privacy_anonymize_data( 'ip', $item->ip );
			$anonymized_item['like_date']    				= wp_privacy_anonymize_data( 'date', $item->like_date );
			$anonymized_item['user_agent']    				= '';

			$item_id = (int) $item->like_id;

			/**
			 * Filters whether to anonymize the item.
			 *
			 * @since 1.4.1
			 *
			 * @param bool|string                    Whether to apply the liked comment anonymization (bool).
			 *                                       Custom prevention message (string). Default true.
			 * @param object 	 $item             	 Item object.
			 * @param array      $anonymized_item    Anonymized item data.
			 */
			$anon_message = apply_filters( 'geodir_anonymize_comment_like_item', true, $item, $anonymized_item );

			if ( true !== $anon_message ) {
				if ( $anon_message && is_string( $anon_message ) ) {
					$messages[] = esc_html( $anon_message );
				} else {
					/* translators: %d: ID */
					$messages[] = sprintf( __( 'Comment like item %d contains personal data but could not be anonymized.', 'geodir_reviewratings' ), $item_id );
				}

				$items_retained = true;

				continue;
			}

			$args = array(
				'like_id' => $item_id,
			);

			$updated = $wpdb->update( GEODIR_COMMENTS_REVIEWS_TABLE, $anonymized_item, $args );

			if ( $updated ) {
				$items_removed = true;
			} else {
				$items_retained = true;
			}
		}

		$done = count( $items ) < $number;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}

	public static function comment_likes_by_user( $email_address, $page, $posts_per_page = 10 ) {
		global $wpdb;

		if ( empty( $email_address ) || empty( $page ) ) {
			return array();
		}

		$user = get_user_by( 'email', $email_address );
		if ( empty( $user ) ) {
			return array();
		}

		if ( absint( $page ) < 1 ) {
			$page = 1;
		}

		$limit = absint( ( $page - 1 ) * $posts_per_page ) . ", " . $posts_per_page;
			
		$query = $wpdb->prepare( "SELECT * FROM " . GEODIR_COMMENTS_REVIEWS_TABLE . " WHERE user_id = %d ORDER BY like_id ASC LIMIT " . $limit, array( $user->ID ) );

		$items = $wpdb->get_results( $query );

		return apply_filters( 'geodir_privacy_comment_likes_data_get_items', $items, $email_address, $user, $page );
	}

	/**
	 * Get personal data (key/value pairs) for an comment like item object.
	 *
	 * @since 1.4.1
	 * @param object $item The item object.
	 * @return array
	 */
	protected static function get_comment_like_personal_data( $item ) {
		$fields = array(
			'comment_id' => __( 'Comment ID', 'geodir_reviewratings' ),
			'ip' => __( 'User IP', 'geodir_reviewratings' ),
			'like_date' => __( 'Like Date', 'geodir_reviewratings' ),
			'user_agent' => __( 'User Agent', 'geodir_reviewratings' )
		);

		$personal_data = array();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field => $name ) {
				$value = isset( $item->{$field} ) ? $item->{$field} : '';

				if ( $value !== '' ) {
					$personal_data[] = array(
						'name'  => $name,
						'value' => $value,
					);
				}
			}
		}

		/**
		 * Allow extensions to register their own personal data for this comment like item for the export.
		 *
		 * @since 1.4.1
		 * @param array    $personal_data Array of name value pairs to expose in the export.
		 * @param object $item The item object.
		 */
		$personal_data = apply_filters( 'geodir_privacy_export_comment_like_item_data', $personal_data, $item );

		return $personal_data;
	}
}

new GeoDir_Review_Rating_Privacy();
