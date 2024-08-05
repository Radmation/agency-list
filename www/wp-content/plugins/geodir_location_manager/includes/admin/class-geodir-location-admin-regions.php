<?php
/**
 * GeoDirectory Admin Regions Class
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
 * GeoDir_Location_Admin_Regions.
 */
class GeoDir_Location_Admin_Regions {

	/**
	 * Initialize the regions admin actions.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
	}

	/**
	 * Check if is regions settings page.
	 * @return bool
	 */
	private function is_settings_page() {
		return isset( $_GET['page'] )
			&& 'gd-settings' === $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'locations' === $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'regions' === $_GET['section'];
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		if ( ! empty( $_REQUEST['country_slug'] ) && ! empty( $_REQUEST['region_slug'] ) ) {
			self::form_output();
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {

		global $wpdb;
		
		// Get the regions count
		$count = $wpdb->get_var( "SELECT COUNT(location_id) FROM " . GEODIR_LOCATIONS_TABLE . ";" );

		if ( absint( $count ) && $count > 0 ) {
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2', 'paged' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ); // WPCS: input var ok, CSRF ok, sanitization ok.

			$regions_table_list = new GeoDir_Location_Admin_Regions_Table_List();
			$regions_table_list->prepare_items();

			echo '<div class="geodir-locations-list geodir-regions-list">';
			echo '<input type="hidden" name="page" value="gd-settings" />';
			echo '<input type="hidden" name="tab" value="locations" />';
			echo '<input type="hidden" name="section" value="regions" />';

			$regions_table_list->views();
			$regions_table_list->search_box( __( 'Search region', 'geodirlocation' ), 'region' );
			$regions_table_list->display();
			echo '</div>';
		} else {
			?>
			<p><?php _e( 'No item found', 'geodirlocation' ); ?></p>
			<?php
		}
	}

	private static function form_output() {
		$country_slug = sanitize_text_field( $_REQUEST['country_slug'] );
		$region_slug = sanitize_text_field( $_REQUEST['region_slug'] );

		$info = GeoDir_Location_Region::get_info_by_slug( $region_slug, $country_slug );

		echo '<h2>' . __( 'Region Meta', 'geodirlocation' ) . '</h2>';

		if ( ! empty( $info ) ) {
			$seo = self::get_seo_data( $region_slug, $country_slug );
			$seo->location_type = 'region';
			$seo->country = $info->country;
			$seo->region = $info->region;
			$seo->country_slug = $info->country_slug;
			$seo->region_slug = $info->region_slug;
			$seo->cpt_desc = ! empty( $seo->cpt_desc ) ? json_decode( $seo->cpt_desc, true ) : array();

			include( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/views/html-add-edit-seo-data.php' );
		} else {
			?>
			<p><?php _e( 'Requested region not found!', 'geodirlocation' ); ?></p>
			<?php
		}
	}

	private static function get_seo_data( $region_slug, $country_slug = '' ) {
		$seo = GeoDir_Location_SEO::get_seo_by_slug( $region_slug, 'region', $country_slug );

		if ( empty( $seo ) ) {
			$seo = (object)array(
				'seo_id' => 0,
				'location_type' => '',
				'country_slug' => '',
				'region_slug' => '',
				'city_slug' => '',
				'meta_title' => '',
				'meta_desc' => '',
				'location_desc' => '',
				'image' => 0,
				'image_tagline'	=> '',
				'cpt_desc' => array()
			);
		}

		return $seo;
	}

	/**
	 * Get item data.
	 *
	 * @param  int $item_id
	 * @return array
	 */
	private static function get_item_data( $item_id ) {
		global $wpdb;

		$empty = array(
			//'location_id'	=> 0,
			'country'       => '',
			'region'   		=> '',
			//'city'   		=> '',
			//'country_slug' 	=> '',
			'region_slug'   => '',
			///'city_slug'   	=> '',
			//'latitude'   	=> '',
			//'longitude'   	=> '',
			//'is_default'   	=> 0,
		);

		if ( 0 == $item_id ) {
			return $empty;
		}

		$fields = implode( ', ', array_keys( $empty ) );
		$item = $wpdb->get_row( $wpdb->prepare( "
			SELECT {$fields}
			FROM " . GEODIR_LOCATIONS_TABLE . "
			WHERE region_slug LIKE %s
		", $item_id ), ARRAY_A );

		if ( is_null( $item ) ) {
			return $empty;
		}

		return $item;
	}

	/**
	 * Regions admin actions.
	 */
	public function actions() {
		if ( $this->is_settings_page() ) {
			// Actions
		}
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		// Notices
	}

	/**
	 * Remove item.
	 */
	private function remove_item() {
		// Remove item.

		wp_redirect( esc_url_raw( add_query_arg( array( 'deleted' => 1 ), admin_url( 'admin.php?page=gd-settings&tab=locations&section=regions' ) ) ) );
		exit();
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		// Bulk actions
	}

	/**
	 * Bulk delete item.
	 *
	 * @param array $regions
	 */
	private function bulk_delete_item( $regions ) {
		// Bulk remove item.
	}

	/**
	 * Remove item.
	 *
	 * @param  int $slug
	 * @return bool
	 */
	private function delete_item( $slug ) {
		// Delete item
	}
}

new GeoDir_Location_Admin_Regions();
