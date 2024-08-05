<?php
/**
 * GeoDirectory Admin Cities Table List
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

class GeoDir_Location_Admin_Cities_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'city',
			'plural'   => 'cities',
			'ajax'     => false,
		) );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'city'   		=> __( 'City', 'geodirlocation' ),
			'city_slug'   	=> __( 'Slug', 'geodirlocation' ),
			'region'   		=> __( 'Region', 'geodirlocation' ),
			'country'   	=> __( 'Country', 'geodirlocation' ),
			'latitude' 		=> __( 'Latitude', 'geodirlocation' ),
			'longitude'     => __( 'Longitude', 'geodirlocation' ),
			'image'   		=> __( 'Image', 'geodirlocation' ),
			'total_posts'   => __( 'Listings', 'geodirlocation' )
		);
		if ( GeoDir_Location_Neighbourhood::is_active() ) {
			$columns['neighbourhoods'] = __( 'Neighbourhoods', 'geodirlocation' );
		}
		$columns['is_default'] 	= __( 'Default', 'geodirlocation' );
		$columns['action'] 		= '';

		return $columns;
	}

	public function column_cb( $item ) {
		$cb = '<input type="hidden" class="gd-has-id" data-delete-nonce="' . esc_attr( wp_create_nonce( 'geodir-delete-location-' . $item['location_id'] ) ) . '" data-set-default-nonce="' . esc_attr( wp_create_nonce( 'geodir-set-default-' . $item['location_id'] ) ) . '" data-location-id="' . $item['location_id'] . '" value="' . $item['location_id'] . '" />';
		$cb .= '<input type="checkbox" name="city[]" value="' . $item['location_id'] . '" />';
		return $cb;
	}

	public function column_city( $item ) {
		return $item['city'] . '<small class="gd-meta">' . wp_sprintf( __( 'ID: %d', 'geodirlocation' ), $item['location_id'] ) . '</small>';
	}

	public function column_latitude( $item ) {
		return $item['latitude'];
	}
	
	public function column_region( $item ) {
		return $item['region'];
	}

	public function column_city_slug( $item ) {
		return $item['city_slug'];
	}

	public function column_longitude( $item ) {
		return $item['longitude'];
	}

	public function column_image( $item ) {
		return GeoDir_Location_SEO::get_image( 'city', $item['city_slug'], $item['country_slug'], $item['region_slug'], 'thumbnail', true );
	}

	public function column_country( $item ) {
		return __( $item['country'], 'geodirectory' );
	}

	public function column_total_posts( $item ) {
		$total = GeoDir_Location_City::count_posts_by_name( $item['city'], $item['region'], $item['country'] );
		return $total;
	}

	public function column_neighbourhoods( $item ) {
		$value = GeoDir_Location_Neighbourhood::count_by_location_id( $item['location_id'] );

		if ( $value > 0 ) {
			$value = '<a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=neighbourhoods&location_id=' . $item['location_id'] ) ) . '" class="geodir-view-neighbourhood" title="' . esc_attr( __( 'View neighbourhoods', '' ) ) . '">' . $value . '</a>';
		}

		$value .= '<br><a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=neighbourhoods&add_neighbourhood=1&location_id=' . $item['location_id'] ) ) . '" class="geodir-add-neighbourhood" title="' . esc_attr( __( 'Add neighbourhood', '' ) ) . '"><i class="far fa-plus-square"></i></a>';

		return $value;
	}

	public function column_is_default( $item ) {
		$nonce = wp_create_nonce( 'location_action_' . $item['location_id'] );

		return '<input ' . checked( true, ! empty( $item['is_default'] ), false ) . ' value="' . $item['location_id'] . '" name="default_city" id="gd_loc_default" class="geodir-location-set-default" type="radio">';
	}

	public function column_action( $item ) {
		$location_link = geodir_location_get_url( array( 'gd_country' => $item['country_slug'], 'gd_region' => $item['region_slug'], 'gd_city' => $item['city_slug'] ), get_option( 'permalink_structure' ) );

		$actions = '<a href="' . esc_url( $location_link ) . '" title="' . esc_attr__( 'View city', 'geodirlocation' ) . '" class="geodir-view-location"><i class="fas fa-arrow-up-right-from-square"></i></a> ';
		$actions .= '<a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=add_location&location_id=' . $item['location_id'] ) ) . '" title="' . esc_attr__( 'Edit location', 'geodirlocation' ) . '" class="geodir-edit-location"><i class="far fa-edit"></i></a>';
		if ( empty( $item['is_default'] ) ) {
			$actions .= ' <a href="javascript:void(0);" onclick="geodir_location_delete_location('.$item['location_id'].',jQuery(this).parent().parent());" class="geodir-delete-location geodir-act-delete text-danger" title="' . esc_attr__( 'Delete location', 'geodirlocation' ) . '"><i class="far fa-trash-can"></i></a>';
		}

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
			'merge' => __( 'Merge', 'geodirlocation' ),
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

		do_action( 'geodir_location_restrict_manage_locations', 'city', $which );

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

		$per_page = absint( apply_filters( 'geodir_location_cities_settings_items_per_page', 10 ) );
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
			$where[] = "city LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( wp_unslash( $_REQUEST['s'] ) ) ) ) . "%'";
		}
		if ( ! empty( $_REQUEST['country'] ) ) {
			$where[] = "country LIKE '" . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['country'] ) ) ) . "'";
		}
		if ( ! empty( $_REQUEST['region'] ) ) {
			$where[] = "region LIKE '" . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['region'] ) ) ) . "'";
		}

		$where = ! empty( $where ) ? "WHERE " . implode( ' AND ', $where ) : '';

		// Get the cities
		$cities = $wpdb->get_results(
			"SELECT `location_id`, `country`, `region`, `city`, `country_slug`, `region_slug`, `city_slug`, `latitude`, `longitude`, `is_default` FROM " . GEODIR_LOCATIONS_TABLE . " {$where} " .
			$wpdb->prepare( "ORDER BY is_default DESC, city ASC, region ASC, country ASC LIMIT %d OFFSET %d;", $per_page, $offset ), ARRAY_A
		);

		$count = $wpdb->get_var( "SELECT COUNT(location_id) FROM " . GEODIR_LOCATIONS_TABLE . " {$where};" );

		$this->items = $cities;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}
}
