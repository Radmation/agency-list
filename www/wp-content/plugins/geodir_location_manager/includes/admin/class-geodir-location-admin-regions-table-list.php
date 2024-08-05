<?php
/**
 * GeoDirectory Admin Regions Table List
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

class GeoDir_Location_Admin_Regions_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'region',
			'plural'   => 'regions',
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
			'cb'            			=> '',
			'region'   					=> __( 'Region', 'geodirlocation' ),
			'region_slug'  				=> __( 'Slug', 'geodirlocation' ),
			'country'   				=> __( 'Country', 'geodirlocation' ),
			'image'   					=> __( 'Image', 'geodirlocation' ),
			'total_cities'   			=> __( 'Cities', 'geodirlocation' ),
			'total_posts'   			=> __( 'Listings', 'geodirlocation' ),
			'action'   					=> '',
		);
	}

	/**
	 * Column cb.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_cb( $item ) {
		return '';
	}
	
	/**
	 * Column region.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_region( $item ) {
		return $item->region;
	}

	/**
	 * Column region slug.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_region_slug( $item ) {
		return $item->region_slug;
	}

	/**
	 * Column country.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_country( $item ) {
		return __( $item->country, 'geodirectory');
	}

	public function column_image( $item ) {
		return GeoDir_Location_SEO::get_image( 'region', $item->region_slug, $item->country_slug, '', 'thumbnail', true );
	}

	public function column_total_cities( $item ) {
		$total = ! empty( $item->cities ) ? $item->cities : '';
		return $total;
	}

	public function column_total_posts( $item ) {
		$total = GeoDir_Location_Region::count_posts_by_name( $item->region, $item->country );
		return $total;
	}

	public function column_action( $item ) {
		$location_link = geodir_location_get_url( array( 'gd_country' => $item->country_slug, 'gd_region' => $item->region_slug ), get_option( 'permalink_structure' ) );

		$actions = '<a href="' . esc_url( $location_link ) . '" title="' . esc_attr__( 'View region', 'geodirlocation' ) . '" class="geodir-view-location"><i class="fas fa-arrow-up-right-from-square"></i></a> ';
		$actions .= '<a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=regions&country_slug=' . $item->country_slug . '&region_slug=' . $item->region_slug ) ) . '" title="' . esc_attr__( 'Update meta title & description', 'geodirlocation' ) . '" class="geodir-edit-meta"><i class="far fa-edit"></i></a>';

		return $actions;
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array();
	}

	/**
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		ob_start();

		do_action( 'geodir_location_restrict_manage_locations', 'region', $which );

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

		$per_page = absint( apply_filters( 'geodir_location_regions_settings_items_per_page', 10 ) );
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
			$where[] = "region LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( wp_unslash( $_REQUEST['s'] ) ) ) ) . "%'";
		}
		if ( ! empty( $_REQUEST['country'] ) ) {
			$where[] = "country LIKE '" . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['country'] ) ) ) . "'";
		}

		$where = ! empty( $where ) ? "WHERE " . implode( " AND ", $where ) : '';

		// Get the items
		$items = $wpdb->get_results(
			"SELECT `region_slug`, `region`, `country`, `country_slug`, COUNT( DISTINCT CONCAT( country, ':', region, ':', city ) ) AS cities FROM " . GEODIR_LOCATIONS_TABLE . " {$where} GROUP BY region_slug " .
			$wpdb->prepare( "ORDER BY region ASC, country ASC LIMIT %d OFFSET %d;", $per_page, $offset )
		);

		$count = $wpdb->get_var( "SELECT COUNT( DISTINCT `region_slug` ) FROM " . GEODIR_LOCATIONS_TABLE . " {$where}" );

		$this->items = $items;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}
}
