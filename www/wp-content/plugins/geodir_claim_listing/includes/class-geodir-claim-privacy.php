<?php
/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Claim_Privacy Class.
 */
class GeoDir_Claim_Privacy extends GeoDir_Abstract_Privacy {

	public function __construct() {
		parent::__construct( __( 'GeoDirectory Claim Listings', 'geodir-claim' ) );

		if ( self::allow_export_claims_data() ) {
			$this->add_exporter( 'geodirectory-post-claimed', __( 'User Listing Claims', 'geodir-claim' ), array( __CLASS__, 'data_exporter' ) );
		}

		if ( self::allow_erase_claims_data() ) {
			$this->add_eraser( 'geodirectory-post-claimed', __( 'User Listing Claims', 'geodir-claim' ), array( __CLASS__, 'data_eraser' ) );
		}

		add_filter('geodir_privacy_policy_content', array(__CLASS__,'privacy_content'));
		add_filter( 'geodir_privacy_export_post_personal_data', array( __CLASS__, 'claimed_data' ), 10, 2 );

	}

	/**
	 * Add any needed Claim listing privacy policy texts.
	 *
	 * @param $content string The privacy policy text to be filtered.
	 *
	 * @return string The filtered privacy policy texts.
	 */
	public static function privacy_content( $content) {

		$content .= '<h2>' . __( 'Claim Listings', 'geodir-claim' ) . '</h2>' .
		           '<p>' . __( 'We collect information about you during the claim listing process on our site. This information may include, but is not limited to, your name, email address, phone number, position in business and any other details that might be requested from you for the purpose of verifying you have the authority to manage the listing.', 'geodir-claim' ) . '</p>' .
		           '<p>' . __( 'Handling this data also allows us to:', 'geodir-claim' ) . '</p>' .
		           '<ul>' .
		           '<li>' . __( '- Verify that you have the authority to manage the claimed listing.', 'geodir-claim' ) . '</li>' .
		           '<li>' . __( '- Make you the owner of the claimed listing which can make your name and avatar visible to the public.', 'geodir-claim' ) . '</li>' .
		           '<li>' . __( '- Contact you to inform you if your claim has been approved or rejected.', 'geodir-claim' ) . '</li>' .
		           '</ul>';
		return $content;
	}

	public static function allow_export_claims_data() {
		$allow = true;

		return apply_filters( 'geodir_claim_privacy_allow_export_claims_data', $allow );
	}

	public static function allow_erase_claims_data() {
		$allow = true;

		return apply_filters( 'geodir_claim_privacy_allow_erase_claims_data', $allow );
	}

	/**
	 * Finds and exports data which could be used to identify a person from GeoDirectory data associated with an email address.
	 *
	 * Claim items are exported in blocks of 10 to avoid timeouts.
	 *
	 * @since 1.3.23
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function data_exporter( $email_address, $page ) {
		$done           = false;
		$page           = (int) $page;
		$data_to_export = array();

		$items 			= self::claims_by_user( $email_address, $page );

		if ( 0 < count( $items ) ) {
			foreach ( $items as $item ) {
				if ( ! empty( $item->post_id ) && empty( $item->post_title ) ) {
					$item->post_title = get_the_title( $item->post_id );
				}
				$data_to_export[] = array(
					'group_id' => 'geodirectory-post-claimed',
					'group_label' => __( 'GeoDirectory: Listing Claims', 'geodir-claim' ),
					'group_description' => __( 'User&#8217;s claim listing data.', 'geodir-claim' ),
					'item_id' => 'gd-claim-' . $item->id,
					'data' => self::get_claim_personal_data( $item ),
				);
			}
			$done = 10 > count( $items );
		} else {
			$done = true;
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Erases personal data associated with an email address from the claims table.
	 *
	 * @since 1.3.23
	 *
	 * @param  string $email_address The author email address.
	 * @param  int    $page          Claim page.
	 * @return array
	 */
	public static function data_eraser( $email_address, $page ) {
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

		$items = self::claims_by_user( $email_address, $page, $number );

		if ( empty( $items ) ) {
			return $response;
		}

		$messages    = array();

		foreach ( $items as $item ) {
			$anonymized_item                         		= array();
			$anonymized_item['user_id']               		= 0;
			$anonymized_item['user_name']    				= __( 'Anonymous', 'geodir-claim' );
			$anonymized_item['user_fullname']    			= __( 'Anonymous', 'geodir-claim' );
			$anonymized_item['user_number']    				= wp_privacy_anonymize_data( 'phone', $item->user_number );
			$anonymized_item['user_position']    			= wp_privacy_anonymize_data( 'text', $item->user_position );
			$anonymized_item['user_comments']    			= wp_privacy_anonymize_data( 'longtext', $item->user_comments );
			$anonymized_item['user_ip']    					= wp_privacy_anonymize_data( 'ip', $item->user_ip );
			$anonymized_item['claim_date']    				= wp_privacy_anonymize_data( 'date', $item->claim_date );

			$claim_id = (int) $item->id;

			/**
			 * Filters whether to anonymize the item.
			 *
			 * @since 1.3.23
			 *
			 * @param bool|string                    Whether to apply the claim anonymization (bool).
			 *                                       Custom prevention message (string). Default true.
			 * @param object 	 $item             	 Claim item object.
			 * @param array      $anonymized_item    Anonymized claim data.
			 */
			$anon_message = apply_filters( 'geodir_claim_anonymize_post_item', true, $item, $anonymized_item );

			if ( true !== $anon_message ) {
				if ( $anon_message && is_string( $anon_message ) ) {
					$messages[] = esc_html( $anon_message );
				} else {
					/* translators: %d: Claim ID */
					$messages[] = sprintf( __( 'Claim item %d contains personal data but could not be anonymized.', 'geodir-claim' ), $claim_id );
				}

				$items_retained = true;

				continue;
			}

			$args = array(
				'id' => $claim_id,
			);

			$updated = $wpdb->update( GEODIR_CLAIM_TABLE, $anonymized_item, $args );

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

	public static function claims_by_user( $email_address, $page, $posts_per_page = 10 ) {
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
			
		$query = $wpdb->prepare( "SELECT * FROM " . GEODIR_CLAIM_TABLE . " WHERE user_id = %d ORDER BY id ASC LIMIT " . $limit, array( $user->ID ) );

		$items = $wpdb->get_results( $query );

		return apply_filters( 'geodir_claim_privacy_get_items', $items, $email_address, $user, $page );
	}

	/**
	 * Get personal data (key/value pairs) for an claim item object.
	 *
	 * @since 1.3.23
	 * @param object $item The claim item object.
	 * @return array
	 */
	protected static function get_claim_personal_data( $item ) {
		$fields = array(
			'id' => __( 'Claim ID', 'geodir-claim' ),
			'post_id' => __( 'Post ID', 'geodir-claim' ),
			'post_title' => __( 'Post Title', 'geodir-claim' ),
			'user_id' => __( 'User ID', 'geodir-claim' ),
			'user_name' => __( 'Username', 'geodir-claim' ),
			'user_fullname' => __( 'Full Name', 'geodir-claim' ),
			'user_number' => __( 'Contact Number', 'geodir-claim' ),
			'user_position' => __( 'User Position', 'geodir-claim' ),
			'user_comments' => __( 'User Comments', 'geodir-claim' ),
			'user_ip' => __( 'User IP', 'geodir-claim' ),
			'status' => __( 'Status', 'geodir-claim' ),
			'claim_date' => __( 'Claim Date', 'geodir-claim' )
		);

		$personal_data = array();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field => $name ) {
				$value = isset( $item->{$field} ) ? $item->{$field} : '';

				if ( $value !== '' ) {
					if ( $field == 'status' ) {
						$value = absint( $value );
						switch ( $value ) {
							case 1:
								$value = __( 'Approved', 'geodir-claim' );
								break;
							case 2:
								$value = __( 'Rejected', 'geodir-claim' );
								break;
							default:
								$value = __( 'No Decision', 'geodir-claim' );
								break;
						}
					}
					$personal_data[] = array(
						'name'  => $name,
						'value' => $value,
					);
				}
			}
		}

		/**
		 * Allow extensions to register their own personal data for this claim item for the export.
		 *
		 * @since 1.3.23
		 * @param array    $personal_data Array of name value pairs to expose in the export.
		 * @param object $item The claim item object.
		 */
		$personal_data = apply_filters( 'geodir_claim_privacy_export_personal_data', $personal_data, $item );

		return $personal_data;
	}

	/**
	 * Export claim data.
	 *
	 * @since 1.3.23
	 * @param array   $personal_data Array of name value pairs to expose in the export.
	 * @param object  $gd_post The post object.
	 * @return array  Filtered data.
	 */
	public static function claimed_data( $personal_data, $gd_post ) {
		if ( isset( $gd_post->claimed ) ) {
			$personal_data[] = array(
				'name'  => __( 'Post Claimed', 'geodir-claim' ),
				'value' => ( ! empty( $gd_post->claimed ) ? __( 'Yes', 'geodir-claim' ) : __( 'No', 'geodir-claim' ) ),
			);
		}

		return $personal_data;
	}
}

new GeoDir_Claim_Privacy();
