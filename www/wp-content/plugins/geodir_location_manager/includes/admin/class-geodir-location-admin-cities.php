<?php
/**
 * GeoDirectory Admin Cities Class
 *
 * @author   GeoDirectory
 * @category Admin
 * @package  GeoDirectory_Location_Manager/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Location_Admin_Cities.
 */
class GeoDir_Location_Admin_Cities {

	/**
	 * Initialize the cities admin actions.
	 */
	public function __construct() {
		$this->actions();
		$this->notices();
	}

	/**
	 * Check if is cities settings page.
	 * @return bool
	 */
	private function is_settings_page() {
		return isset( $_GET['page'] )
			&& 'gd-settings' === $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'locations' === $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'cities' === $_GET['section'];
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

		if ( ! empty( $_REQUEST['task'] ) && $_REQUEST['task'] == 'merge' && ! empty( $_REQUEST['ids'] ) ) {
			self::merge_location();
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {

		global $wpdb;

		echo '<h2>' . __( 'Cities', 'geodirlocation' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=add_location' ) ) . '" class="add-new-h2">' . __( 'Add New', 'geodirlocation' ) . '</a></h2>';

		GeoDir_Admin_Settings::show_messages();

		// Get the cities count
		$count = $wpdb->get_var( "SELECT COUNT(location_id) FROM " . GEODIR_LOCATIONS_TABLE . ";" );

		if ( absint( $count ) && $count > 0 ) {
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2', 'paged' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ); // WPCS: input var ok, CSRF ok, sanitization ok.

			$cities_table_list = new GeoDir_Location_Admin_Cities_Table_List();
			$cities_table_list->prepare_items();
			echo '<div class="geodir-locations-list geodir-cities-list">';
			echo '<input type="hidden" name="page" value="gd-settings" />';
			echo '<input type="hidden" name="tab" value="locations" />';
			echo '<input type="hidden" name="section" value="cities" />';

			$cities_table_list->views();
			$cities_table_list->search_box( __( 'Search location', 'geodirlocation' ), 'city' );
			$cities_table_list->display();
			echo '</div>';
		} else {
			?>
			<p><?php _e( 'No location found', 'geodirlocation' ); ?></p>
			<?php
		}
	}

	private static function merge_location() {
		$ids 		= ! empty( $_GET['ids'] ) ? explode( ',', trim( $_GET['ids'] ) ) : array();

		$items = GeoDir_Location_City::get_items_by_ids( $ids );

		echo '<h2>' . __( 'Merge Location', 'geodirlocation' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=cities' ) ) . '" class="add-new-h2">' . __( 'Back to Cities', 'geodirlocation' ) . '</a></h2>';

		include( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/views/html-merge-location.php' );
	}

	/**
	 * Cities admin actions.
	 */
	public function actions() {
		if ( $this->is_settings_page() ) {
			// Bulk actions
			if ( $this->current_action() && ! empty( $_GET['city'] ) ) {
				$this->bulk_actions();
			}
		}
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		if ( ! ( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'geodirectory-settings' ) ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'geodirlocation' ) );
		}

		$ids = array_map( 'absint', (array) $_GET['city'] );

		if ( 'delete' == $this->current_action() ) {
			$count = 0;
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( geodir_location_delete_by_id( (int) $id ) ) {
						$count++;
					}
				}
			}

			wp_redirect( admin_url( 'admin.php?page=gd-settings&tab=locations&section=cities&removed='.$count ) );
			exit;
		} else if ( 'merge' == $this->current_action() ) {
			if ( ! empty( $ids ) ) {
				$merge_ids = implode( ',', array_values( $ids ) );

				wp_redirect( admin_url( 'admin.php?page=gd-settings&tab=locations&section=cities&task=merge&ids=' . $merge_ids ) );
				exit;
			}
			wp_redirect( admin_url( 'admin.php?page=gd-settings&tab=locations&section=cities' ) );
			exit;
		}
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['removed'] ) ) {
			if ( ! empty( $_GET['removed'] ) ) {
				$count = absint( $_GET['removed'] );
				$message = wp_sprintf( _n( 'Item deleted successfully.', '%d items deleted successfully.', $count, 'geodirlocation' ), $count );
			} else {
				$message = __( 'No item deleted.', 'geodirlocation' );
			}
			GeoDir_Admin_Settings::add_message( $message );
		}
	}
}

new GeoDir_Location_Admin_Cities();
