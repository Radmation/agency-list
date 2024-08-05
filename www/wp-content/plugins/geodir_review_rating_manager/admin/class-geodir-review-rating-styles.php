<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Geodir_Review_Rating_Styles {

	public function __construct() {
		$this->actions();
		$this->notices();
	}

	/**
	 * Check if is rating styles settings page.
	 * @return bool
	 */
	private function is_settings_page() {
		return isset( $_GET['page'] )
			&& 'gd-settings' === $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'review_rating' === $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'styles' === $_GET['section'];
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

		echo '<h2>' . __( 'Rating Styles', 'geodir_reviewratings' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=review_rating&section=add_styles' ) ) . '" class="add-new-h2">' . __( 'Add New', 'geodir_reviewratings' ) . '</a></h2>';

        $styles_table_list = new Geodir_Review_Rating_Styles_Table_List();
        $styles_table_list->prepare_items();
        echo '<div class="geodir-style-list">';
        echo '<input type="hidden" name="page" value="gd-settings" />';
        echo '<input type="hidden" name="tab" value="review_rating" />';
        echo '<input type="hidden" name="section" value="styles" />';

        $styles_table_list->views();
        $styles_table_list->search_box( __( 'Search style', 'geodir_reviewratings' ), 'style' );
        $styles_table_list->display();
        echo '</div>';
	}

	/**
	 * Cities admin actions.
	 */
	public function actions() {
		if ( $this->is_settings_page() ) {
			// Bulk actions
			if ( $this->current_action() && ! empty( $_GET['styles'] ) ) {
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

		$ids = array_map( 'absint', (array) $_GET['styles'] );

		if ( 'delete' == $this->current_action() ) {
			$count = 0;
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $this->delete_style( $id ) ) {
						$count++;
					}
				}
			}

			wp_redirect( esc_url_raw( add_query_arg( array( 'removed' => $count ), admin_url( 'admin.php?page=gd-settings&tab=review_rating&section=styles' ) ) ) );
			exit;
		}
	}

	/**
	 * Remove style.
	 *
	 * @param  int $style_id
	 * @return bool
	 */
	private function delete_style( $style_id ) {
        $style = $style_id ? geodir_get_style_by_id( $style_id ) : NULL;
		if ( ! empty( $style->is_default ) ) {
			return false;
		}

		$return = geodir_delete_style_by_id( $style_id );

		return $return;
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['removed']) && 'styles' == $_GET['section'] ) {
			if ( ! empty( $_GET['removed'] ) ) {
				$count = absint( $_GET['removed'] );
				$message = $count > 1 ? wp_sprintf( __( '%d rating styles deleted successfully.', 'geodir_reviewratings' ), $count ) : __( 'Rating style deleted successfully.', 'geodir_reviewratings' );
			} else {
				$message = __( 'No style deleted.', 'geodir_reviewratings' );
			}
			GeoDir_Admin_Settings::add_message( $message );
		}
	}
}

new Geodir_Review_Rating_Styles();
