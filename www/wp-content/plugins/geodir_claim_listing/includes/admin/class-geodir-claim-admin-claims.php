<?php
/**
 * Claim Listings Claims Admin class.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Claim_Admin_Claims class.
 */
class GeoDir_Claim_Admin_Claims {

	/**
	 * Initialize the claims admin actions.
	 */
	public function __construct() {
		$this->actions();
		$this->notices();
	}

	/**
	 * Check if is claim settings page.
	 * @return bool
	 */
	private function is_settings_page() {
		return isset( $_GET['page'] )
			&& 'gd-settings' === $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'claims' === $_GET['tab']
			&& isset( $_GET['section'] )
			&& ('claim-list' === $_GET['section'] || !isset($_GET['section']));
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
	 * Cities admin actions.
	 */
	public function actions() {
		if ( $this->is_settings_page() ) {
			// Bulk actions
			if ( $this->current_action() && ! empty( $_GET['claim'] ) ) {
				$this->bulk_actions();
			}
		}
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		if ( ! ( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'geodirectory-settings' ) ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'geodir-claim' ) );
		}

		$ids = array_map( 'absint', (array) $_GET['claim'] );

		if ( 'delete' == $this->current_action() ) {
			$count = 0;
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $this->delete_item( $id ) ) {
						$count++;
					}
				}
			}

			// clear cache
			delete_transient( 'geodir_claim_stats' );

			// redirect
			wp_redirect( esc_url( add_query_arg( array( 'removed' => $count ), admin_url( 'admin.php?page=gd-settings&tab=claims&section=claim-list' ) ) ) );
			exit;
		}
	}

	/**
	 * Remove claim item.
	 *
	 * @param  int $id
	 * @return bool
	 */
	private function delete_item( $id ) {
		// clear cache
		delete_transient( 'geodir_claim_stats' );
		
		$return = geodir_claim_delete_by_id( $id );

		return $return;
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['removed'] ) ) {
			if ( ! empty( $_GET['removed'] ) ) {
				$count = absint( $_GET['removed'] );
				$message = wp_sprintf( _n( 'Item deleted successfully.', '%d items deleted successfully.', $count, 'geodir-claim' ), $count );
			} else {
				$message = __( 'No item deleted.', 'geodir-claim' );
			}
			GeoDir_Admin_Settings::add_message( $message );
		}
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
		global $wpdb, $aui_bs5;

		ob_start();
		echo '<h2>' . __( 'Listing Claims', 'geodir-claim' ) . '</h2>';

		GeoDir_Admin_Settings::show_messages();

		$table_list = new GeoDir_Claim_Admin_Claims_Table_List();

		$table_list->prepare_items();

		$claim_status = isset($_REQUEST['claim_status']) ? esc_attr($_REQUEST['claim_status']) : '';
		if(!in_array($claim_status,array('pending','approved','rejected'))){
			$claim_status = '';
		}

		echo '</form>'; // end the main GD settings page form

		$table_list->views();

		echo '<form id="comments-form" method="get">';
		echo '<div class="geodir-claims-list">';
		echo '<input type="hidden" name="page" value="gd-settings" />';
		echo '<input type="hidden" name="tab" value="claims" />';
		//echo '<input type="hidden" name="section" value="" />';
		echo '<input type="hidden" name="claim_status" value="'.$claim_status.'" />';

		$table_list->search_box( __( 'Search listing claims', 'geodir-claim' ), 'claim' );
		$table_list->display();

		echo '</form></div>';

		echo '<form>'; // start a new form for the GD settings page form we closed above

		$output = ob_get_clean();

		if ( $aui_bs5 ) {
			$output = str_replace( array( '<ul class="subsubsub">', "<ul class='subsubsub'>" ), '<ul class="subsubsub ps-0 my-3">', $output );
		}

		echo $output;
	}
}

new GeoDir_Claim_Admin_Claims();
