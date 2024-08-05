<?php
/**
 * GeoDirectory Admin Countries Class
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
 * GeoDir_Location_Admin_Countries.
 */
class GeoDir_Location_Admin_Countries {

	/**
	 * Initialize the countries admin actions.
	 */
	public function __construct() {
		$this->actions();
		$this->notices();
	}

	/**
	 * Check if is countries settings page.
	 * @return bool
	 */
	private function is_settings_page() {
		return isset( $_GET['page'] )
			&& 'gd-settings' === $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'locations' === $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'countries' === $_GET['section'];
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

		if ( ! empty( $_REQUEST['country_slug'] ) ) {
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


		GeoDir_Admin_Settings::show_messages();

		// Get the countries count
		$count = $wpdb->get_var( "SELECT COUNT(location_id) FROM " . GEODIR_LOCATIONS_TABLE . ";" );

		if ( absint( $count ) && $count > 0 ) {
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2', 'paged' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ); // WPCS: input var ok, CSRF ok, sanitization ok.

			$countries_table_list = new GeoDir_Location_Admin_Countries_Table_List();
			$countries_table_list->prepare_items();
			echo '<div class="geodir-locations-list geodir-countries-list">';
			echo '<input type="hidden" name="page" value="gd-settings" />';
			echo '<input type="hidden" name="tab" value="locations" />';
			echo '<input type="hidden" name="section" value="countries" />';

			$countries_table_list->views();
			$countries_table_list->search_box( __( 'Search country', 'geodirlocation' ), 'country' );
			$countries_table_list->display();

			echo '<div><p class="alert alert-info mt-3">' . __( 'COUNTRY SLUG TRANSLATION: Translate the countries via .po file that you want and then upload the .mo file to your server, then tick the countries you translated and click the Apply button. This will translate country slug in url.', 'geodirlocation' ) . '</p></div>';
			echo '</div>';
		} else {
			?>
			<p><?php _e( 'No item found', 'geodirlocation' ); ?></p>
			<?php
		}
	}

	private static function form_output() {
		$country_slug = sanitize_text_field( $_REQUEST['country_slug'] );

		$country = GeoDir_Location_Country::get_name_by_slug( $country_slug );

		echo '<h2>' . __( 'Country Meta', 'geodirlocation' ) . '</h2>';

		if ( ! empty( $country ) ) {
			$seo = self::get_seo_data( $country_slug );
			$seo->location_type = 'country';
			$seo->country = $country;
			$seo->country_slug = $country_slug;
			$seo->cpt_desc = ! empty( $seo->cpt_desc ) ? json_decode( $seo->cpt_desc, true ) : array();

			include( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/views/html-add-edit-seo-data.php' );
		} else {
			?>
			<p><?php _e( 'Requested country not found!', 'geodirlocation' ); ?></p>
			<?php
		}
	}

	private static function get_seo_data( $country_slug ) {
		$seo = GeoDir_Location_SEO::get_seo_by_slug( $country_slug, 'country' );

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
				'image_tagline' => '',
				'cpt_desc' => array()
			);
		}

		return $seo;
	}
	
	/**
	 * Countries admin actions.
	 */
	public function actions() {
		if ( $this->is_settings_page() ) {
			// Bulk actions
			if ( $this->current_action() && ! empty( $_GET['country'] ) ) {
				$this->bulk_actions();
			}
		}
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'geodirectory-settings' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'geodirlocation' ) );
		}

		$countries = (array)$_GET['country'];

		if ( 'translate' == $this->current_action() ) {
			$count = 0;
			if ( ! empty( $countries ) ) {
				foreach ( $countries as $slug ) {
					if ( $this->translate_country( $slug ) ) {
						$count++;
					}
				}
			}
			
			if ( $count > 0 ) {
				flush_rewrite_rules( false );
			}

			wp_redirect( esc_url_raw( add_query_arg( array( 'translated' => $count ), admin_url( 'admin.php?page=gd-settings&tab=locations&section=countries' ) ) ) );
			exit();
		}
	}

	/**
	 * Remove country.
	 *
	 * @param  int $slug
	 * @return bool
	 */
	private function translate_country( $slug ) {
		$return = GeoDir_Location_Country::translate( $slug );

		return $return;
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['translated'] ) ) {
			if ( ! empty( $_GET['translated'] ) ) {
				$count = absint( $_GET['translated'] );
				$message = wp_sprintf( _n( 'Country translated successfully.', '%d countries translated successfully.', $count, 'geodirlocation' ), $count );
			} else {
				$message = __( 'No item updated.', 'geodirlocation' );
			}
			GeoDir_Admin_Settings::add_message( $message );
		}
	}
}

new GeoDir_Location_Admin_Countries();
