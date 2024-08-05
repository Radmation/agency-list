<?php
/**
 * GeoDirectory Admin Neighbourhoods Table List
 *
 * @author   GeoDirectory
 * @category Admin
 * @package  GeoDirectory_Location_Manager/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class GeoDir_Location_Admin_Neighbourhoods_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'neighbourhood',
			'plural'   => 'neighbourhoods',
			'ajax'     => false,
		) );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'name'   		=> __( 'Name', 'geodirlocation' ),
			'slug'     		=> __( 'Slug', 'geodirlocation' ),
			'latitude' 		=> __( 'Latitude', 'geodirlocation' ),
			'longitude'     => __( 'Longitude', 'geodirlocation' ),
			'location'   	=> __( 'Location', 'geodirlocation' ),
			'image'   		=> __( 'Image', 'geodirlocation' ),
			'total_posts'   => __( 'Listings', 'geodirlocation' ),
			'action'   		=> '',
		);
	}

	/**
	 * Column cb.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_cb( $item ) {
		$cb = '<input type="hidden" class="gd-has-id" data-delete-nonce="' . esc_attr( wp_create_nonce( 'geodir-delete-neighbourhood-' . $item['id'] ) ) . '" data-hood-id="' . $item['id'] . '" value="' . $item['id'] . '" /><input type="checkbox" name="neighbourhood[]" value="' . $item['id'] . '" />';
		return $cb;
	}

	/**
	 * Column name.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_name( $item ) {
		return wp_unslash( $item['name'] ) . '<small class="gd-meta">' . wp_sprintf( __( 'ID: %d', 'geodirlocation' ), $item['id'] ) . '</small>';
	}

	/**
	 * Column slug.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_slug( $item ) {
		return $item['slug'];
	}

	/**
	 * Column latitude.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_latitude( $item ) {
		return $item['latitude'];
	}
	
	/**
	 * Column longitude.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_longitude( $item ) {
		return $item['longitude'];
	}

	/**
	 * Column location_id.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_location( $item ) {
		$location = geodir_get_location_by_id( '' , $item['location_id'] );
		if ( empty( $location ) ) {
			return NULL;
		}
		$value = $location->city . ', ' . $location->region . ', ' . __( $location->country, 'geodirectory' ) . '<small class="gd-meta">' . wp_sprintf( __( 'ID: %d', 'geodirlocation' ), $item['location_id'] ) . '</small>';
		return $value;
	}

	/**
	 * Column image.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_image( $item ) {
		return ! empty( $item['image'] ) ? wp_get_attachment_image( (int) $item['image'] ) : '';
	}

	public function column_total_posts( $item ) {
		$total = GeoDir_Location_Neighbourhood::count_posts_by_slug( $item['slug'] );
		return $total;
	}

	/**
	 * Column action.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_action( $item ) {
		$location_link = geodir_location_get_url( array( 'gd_country' => $item['country_slug'], 'gd_region' => $item['region_slug'], 'gd_city' => $item['city_slug'], 'gd_neighbourhood' => $item['slug'] ), get_option( 'permalink_structure' ) );

		$actions = '<a href="' . esc_url( $location_link ) . '" title="' . esc_attr__( 'View neighbourhood', 'geodirlocation' ) . '" class="geodir-view-location"><i class="fas fa-arrow-up-right-from-square"></i></a> ';
		$actions .= '<a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=neighbourhoods&add_neighbourhood=1&id=' . $item['id'] ) ) . '" title="' . esc_attr__( 'Edit neighbourhood', 'geodirlocation' ) . '" class="geodir-edit-neighbourhood"><i class="far fa-edit"></i></a> ';
		$actions .= '<a href="javascript:void(0);" class="geodir-delete-neighbourhood geodir-act-delete text-danger" title="' . esc_attr__( 'Delete neighbourhood', 'geodirlocation' ) . '"><i class="far fa-trash-can"></i></a>';

		return $actions;
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'geodirlocation' ),
		);
	}

	/**
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		ob_start();

		do_action( 'geodir_location_restrict_manage_locations', 'neighbourhood', $which );

		$actions = ob_get_clean();

		if ( trim( $actions ) == '' ) {
			return;
		}
		?>
		<div class="alignleft actions">
		<?php
			echo $actions;

			submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
		?>
		</div>
		<?php
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = absint( apply_filters( 'geodir_location_neighbourhoods_settings_items_per_page', 10 ) );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$where = array();

		if ( ! empty( $_REQUEST['s'] ) ) {
			$where[] = "h.hood_name LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( wp_unslash( $_REQUEST['s'] ) ) ) ) . "%'";
		}
		if ( ! empty( $_REQUEST['country'] ) ) {
			$where[] = "l.country LIKE '" . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['country'] ) ) ) . "'";
		}
		if ( ! empty( $_REQUEST['region'] ) ) {
			$where[] = "l.region LIKE '" . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['region'] ) ) ) . "'";
		}
		if ( ! empty( $_REQUEST['city'] ) ) {
			$where[] = "l.city LIKE '" . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['city'] ) ) ) . "'";
		}
		if ( ! empty( $_REQUEST['location_id'] ) ) {
			$where[] = "h.hood_location_id = '" . absint( $_REQUEST['location_id'] ) . "'";
		}

		$where = ! empty( $where ) ? "WHERE " . implode( ' AND ', $where ) : '';

		// Get the cities
		$cities = $wpdb->get_results(
			"SELECT h.`hood_id` AS id, h.`hood_location_id` AS location_id, h.`hood_name` AS name, h.`hood_latitude` AS latitude, h.`hood_longitude` AS longitude, h.`hood_slug` AS slug, h.image, l.country_slug, l.region_slug, l.city_slug FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " AS h LEFT JOIN " . GEODIR_LOCATIONS_TABLE . " AS l ON l.location_id = h.hood_location_id {$where} " .
			$wpdb->prepare( "ORDER BY hood_name ASC LIMIT %d OFFSET %d;", $per_page, $offset ), ARRAY_A
		);

		$count = $wpdb->get_var( "SELECT COUNT(h.hood_id) FROM " . GEODIR_NEIGHBOURHOODS_TABLE . " AS h LEFT JOIN " . GEODIR_LOCATIONS_TABLE . " AS l ON l.location_id = h.hood_location_id {$where};" );

		$this->items = $cities;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}
}
