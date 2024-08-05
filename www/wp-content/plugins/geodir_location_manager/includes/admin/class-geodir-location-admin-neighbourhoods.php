<?php
/**
 * GeoDirectory Admin Neighbourhoods Class
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
 * GeoDir_Location_Admin_Neighbourhoods.
 */
class GeoDir_Location_Admin_Neighbourhoods {

	/**
	 * Initialize the neighbourhoods admin actions.
	 */
	public function __construct() {
		$this->actions();
		$this->notices();
	}

	/**
	 * Check if is neighbourhoods settings page.
	 * @return bool
	 */
	private function is_settings_page() {
		return isset( $_GET['page'] )
			&& 'gd-settings' === $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'locations' === $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'neighbourhoods' === $_GET['section'];
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
			if ( $this->current_action() && ! empty( $_GET['neighbourhood'] ) ) {
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

		$ids = array_map( 'absint', (array) $_GET['neighbourhood'] );

		if ( 'delete' == $this->current_action() ) {
			$count = 0;
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $this->delete_item( $id ) ) {
						$count++;
					}
				}
			}

			wp_redirect( esc_url_raw( add_query_arg( array( 'removed' => $count ), admin_url( 'admin.php?page=gd-settings&tab=locations&section=neighbourhoods' ) ) ) );
			exit();
		}
	}

	private function delete_item( $id ) {
		$return = GeoDir_Location_Neighbourhood::delete_by_id( $id );

		return $return;
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

	/**
	 * Page output.
	 */
	public static function page_output() {
		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		if ( ! empty( $_REQUEST['add_neighbourhood'] ) ) {
			self::add_neighbourhood();
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {

		global $wpdb;

		GeoDir_Admin_Settings::show_messages();

		echo '<p>' . wp_sprintf( __( 'Add a new neighbourhood from Settings > Locations > Cities > Click %s button from Neighbourhoods column.', 'geodirlocation' ), '<i class="far fa-plus-square" aria-hidden="true"></i>' ) . '</p>';

		// Get the neighbourhoods count
		$count = $wpdb->get_var( "SELECT COUNT(hood_id) FROM " . GEODIR_NEIGHBOURHOODS_TABLE . ";" );

		if ( absint( $count ) && $count > 0 ) {
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2', 'paged' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ); // WPCS: input var ok, CSRF ok, sanitization ok.

			$table_list = new GeoDir_Location_Admin_Neighbourhoods_Table_List();
			$table_list->prepare_items();
			echo '<div class="geodir-locations-list geodir-neighbourhoods-list">';
			echo '<input type="hidden" name="page" value="gd-settings" />';
			echo '<input type="hidden" name="tab" value="locations" />';
			echo '<input type="hidden" name="section" value="neighbourhoods" />';

			$table_list->views();
			$table_list->search_box( __( 'Search neighbourhood', 'geodirlocation' ), 'neighbourhood' );
			$table_list->display();
			echo '</div>';
		} else {
			?>
			<p><?php _e( 'No item found', 'geodirlocation' ); ?></p>
			<?php
		}
	}

	private static function add_neighbourhood() {
		// Hide the save button
		$GLOBALS['hide_save_button'] 		= false;

		$location_id 		= isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0;
		$neighbourhood_id 	= isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$neighbourhood 		= self::get_data( $neighbourhood_id );
		$location			= GeoDir_Location_City::get_info_by_id( $location_id );
		if ( empty( $location_id ) && ! empty( $neighbourhood->location_id ) ) {
			$location_id = $neighbourhood->location_id;
		}
		if ( empty( $neighbourhood->id ) && ! empty( $location ) ) {
			$neighbourhood->city 			= $location->city;
			$neighbourhood->city_slug 		= $location->city_slug;
			$neighbourhood->region 			= $location->region;
			$neighbourhood->region_slug 	= $location->region_slug;
			$neighbourhood->country 		= $location->country;
			$neighbourhood->country_slug 	= $location->country_slug;
			$neighbourhood->latitude 		= $location->latitude;
			$neighbourhood->longitude 		= $location->longitude;
			$neighbourhood->image 			= 0;
			$neighbourhood->cpt_desc 		= array();
		}

		add_filter( 'geodir_add_listing_map_restrict', '__return_false' );

		include( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/views/html-add-edit-neighbourhood.php' );
	}

	/**
	 * Get data.
	 *
	 * @param  int $id
	 * @return array
	 */
	public static function get_data( $id ) {
		global $wpdb;

		$empty = (object)array(
			'id'				=> 0,
			'neighbourhood'		=> '',
			'slug'				=> '',
			'location_id'		=> 0,
			'latitude'			=> '',
			'longitude'			=> '',
			'meta_title'		=> '',
			'meta_description'	=> '',
			'description'		=> '',
			'city'				=> '',
			'city_slug'			=> '',
			'region'			=> '',
			'region_slug'		=> '',
			'country'			=> '',
			'country_slug'		=> '',
			'image'				=> 0,
			'cpt_desc'			=> array()
		);

		if ( empty( $id ) ) {
			return $empty;
		}

		$neighbourhood = GeoDir_Location_Neighbourhood::get_info_by_id( $id );

		if ( empty( $neighbourhood ) ) {
			return $empty;
		}

		$neighbourhood->cpt_desc = ! empty( $neighbourhood->cpt_desc ) ? json_decode( $neighbourhood->cpt_desc, true ) : array();

		return $neighbourhood;
	}
}

new GeoDir_Location_Admin_Neighbourhoods();
