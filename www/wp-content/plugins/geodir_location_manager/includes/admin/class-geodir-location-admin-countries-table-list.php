<?php
/**
 * GeoDirectory Admin Countries Table List
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

class GeoDir_Location_Admin_Countries_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'country',
			'plural'   => 'countries',
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
			'cb'            			=> '<input type="checkbox" />',
			'country'   				=> __( 'Country', 'geodirlocation' ),
			'country_slug'  			=> __( 'Slug', 'geodirlocation' ),
			'translated_country' 		=> __( 'Country after translation', 'geodirlocation' ),
			'translated_country_slug'   => __( 'Slug after translation', 'geodirlocation' ),
			'image'   					=> __( 'Image', 'geodirlocation' ),
			'total_regions'   			=> __( 'Regions', 'geodirlocation' ),
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
		return sprintf( '<input type="checkbox" name="country[]" value="%1$s" />', $item->country_slug );
	}
	
	/**
	 * Column country.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_country( $item ) {
		return $item->country;
	}
	
	/**
	 * Column country slug.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_country_slug( $item ) {
		return $item->country_slug;
	}
	
	/**
	 * Column translated country.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_translated_country( $item ) {
		return __( $item->country, 'geodirectory');
	}
	
	/**
	 * Column translated country slug.
	 *
	 * @param  array $item
	 * @return string
	 */
	public function column_translated_country_slug( $item ) {
		$translated_country = __( $item->country, 'geodirectory');
		$translated_country = trim( wp_unslash( $translated_country ) );

		return geodir_create_location_slug( $translated_country );
	}

	public function column_image( $item ) {
		return GeoDir_Location_SEO::get_image( 'country', $item->country_slug, '', '', 'thumbnail', true );
	}

	public function column_total_regions( $item ) {
		$total = ! empty( $item->regions ) ? $item->regions : '';
		return $total;
	}

	public function column_total_cities( $item ) {
		$total = ! empty( $item->cities ) ? $item->cities : '';
		return $total;
	}

	public function column_total_posts( $item ) {
		$total = GeoDir_Location_Country::count_posts_by_name( $item->country );
		return $total;
	}

	public function column_action( $item ) {
		$location_link = geodir_location_get_url( array( 'gd_country' => $item->country_slug ), get_option( 'permalink_structure' ) );

		$actions = '<a href="' . esc_url( $location_link ) . '" title="' . esc_attr__( 'View country', 'geodirlocation' ) . '" class="geodir-view-location"><i class="fas fa-arrow-up-right-from-square"></i></a> ';
		$actions .= '<a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=countries&country_slug=' . $item->country_slug ) ) . '" title="' . esc_attr__( 'Update meta title & description', 'geodirlocation' ) . '" class="geodir-edit-meta"><i class="far fa-edit"></i></a>';

		return $actions;
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'translate' => __( 'Translate', 'geodirlocation' ),
		);
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = absint( apply_filters( 'geodir_location_countries_settings_items_per_page', 10 ) );
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
			$where[] = "country LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( wp_unslash( $_REQUEST['s'] ) ) ) ) . "%' ";
		}
		
		$where = ! empty( $where ) ? "WHERE " . implode( " AND ", $where ) : '';

		// Get the countries
		$countries = $wpdb->get_results(
			"SELECT `country_slug`, `country`, COUNT( DISTINCT CONCAT( country, ':', region ) ) AS regions, COUNT( DISTINCT CONCAT( country, ':', region, ':', city ) ) AS cities FROM " . GEODIR_LOCATIONS_TABLE . " {$where} GROUP BY country_slug " .
			$wpdb->prepare( "ORDER BY country ASC LIMIT %d OFFSET %d;", $per_page, $offset )
		);

		$count = $wpdb->get_var( "SELECT COUNT( DISTINCT `country_slug` ) FROM " . GEODIR_LOCATIONS_TABLE . " {$where}" );

		$this->items = $countries;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}
}
