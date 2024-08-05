<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Geodir_Review_Ratings {

	public function __construct() {
		$this->actions();
		$this->notices();
	}

	/**
	 * Check if is rating ratings settings page.
	 * @return bool
	 */
	private function is_settings_page() {
		return isset( $_GET['page'] )
			&& 'gd-settings' === $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'review_rating' === $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'ratings' === $_GET['section'];
	}

	public static function current_action() {
		if ( ! empty( $_GET['action'] ) && $_GET['action'] != -1 ) {
			return $_GET['action'];
		} else if ( ! empty( $_GET['action2'] ) ) {
			return $_GET['action2'];
		}
		return NULL;
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		// Hide the save button
		$GLOBALS['hide_save_button'] = true;
        self::table_list_output();
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {

		global $wpdb;

		echo '<h2>' . __( 'Ratings', 'geodir_reviewratings' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=review_rating&section=create' ) ) . '" class="add-new-h2">' . __( 'Add New', 'geodir_reviewratings' ) . '</a></h2>';

        $ratings_table_list = new Geodir_Review_Ratings_Table_List();
        $ratings_table_list->prepare_items();
        echo '<div class="geodir-rating-list">';
        echo '<input type="hidden" name="page" value="gd-settings" />';
        echo '<input type="hidden" name="tab" value="review_rating" />';
        echo '<input type="hidden" name="section" value="ratings" />';

        $ratings_table_list->views();
        $ratings_table_list->search_box( __( 'Search rating', 'geodir_reviewratings' ), 'rating' );
        $ratings_table_list->display();
        echo '</div>';

	}

	/**
	 * Cities admin actions.
	 */
	public function actions() {
		if ( $this->is_settings_page() ) {
			// Bulk actions
			if ( $this->current_action() && ! empty( $_GET['ratings'] ) ) {
				$this->bulk_actions();
			}
		}
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		if ( ! ( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'geodirectory-settings' ) ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'geodir_reviewratings' ) );
		}

		$ids = array_map( 'absint', (array) $_GET['ratings'] );

		if ( 'delete' == $this->current_action() ) {
			$count = 0;
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $this->delete_rating( $id ) ) {
						$count++;
					}
				}
			}

			wp_redirect( esc_url_raw( add_query_arg( array( 'removed' => $count ), admin_url( 'admin.php?page=gd-settings&tab=review_rating&section=ratings' ) ) ));
			exit;
		}
	}

	/**
	 * Remove rating.
	 *
	 * @param  int $rating_id
	 * @return bool
	 */
	private function delete_rating( $rating_id ) {
		$location = $rating_id ? geodir_get_rating_by_id( $rating_id ) : NULL;
		if ( ! empty( $location->is_default ) ) {
			return false;
		}

		$return = geodir_delete_rating_by_id( $rating_id );

		return $return;
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['removed'] ) && 'ratings' == $_GET['section'] ) {
			if ( ! empty( $_GET['removed'] ) ) {
				$count = absint( $_GET['removed'] );
				$message = $count > 1 ? wp_sprintf( __( '%d rating items deleted successfully.', 'geodir_reviewratings' ), $count ) : __( 'Rating item deleted successfully.', 'geodir_reviewratings' );
			} else {
				$message = __( 'No rating deleted.', 'geodir_reviewratings' );
			}
			GeoDir_Admin_Settings::add_message( $message );
		}
	}
}

new Geodir_Review_Ratings();
