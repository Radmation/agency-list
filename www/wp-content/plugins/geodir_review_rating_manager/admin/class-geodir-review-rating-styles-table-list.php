<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Geodir_Review_Rating_Styles_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'style',
			'plural'   => 'styles',
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
			'title'   	    => __( 'Style Title', 'geodir_reviewratings' ),
			'rating_text'   => __( 'Rating Text', 'geodir_reviewratings' ),
			'image'   	    => __( 'Icon / Image', 'geodir_reviewratings' ),
			'max_rating' 	=> __( 'Max Rating', 'geodir_reviewratings' ),
			'is_default' 	=> __( 'Default', 'geodir_reviewratings' ),
            'action' 	    => __( 'Actions', 'geodir_reviewratings' ),
		);

		return $columns;
	}

	public function column_cb( $item ) {
		$cb = '<input type="hidden" class="gd-has-id" data-delete-nonce="' . esc_attr( wp_create_nonce( 'geodir-delete-style-' . $item['id'] ) ) . '" data-set-default-nonce="' . esc_attr( wp_create_nonce( 'geodir-set-default-' . $item['id'] ) ) . '" data-style-id="' . absint( $item['id'] ) . '" value="' . absint( $item['id'] ) . '" />';
		$cb .= '<input type="checkbox" name="styles[]" value="' . absint( $item['id'] ) . '" />';
		return $cb;
	}

	public function column_title( $item ) {
        return $item['name'] . '<small class="gd-meta">' . wp_sprintf( __( 'ID: %d', 'geodir_reviewratings' ), absint( $item['id'] ) ) . '</small>';
	}
	
	public function column_rating_text( $item ) {
		return geodir_reviewrating_star_lables_to_str( $item['star_lables'], true );
	}

	public function column_image( $item ) {
        if('font-awesome' == $item['s_rating_type']){
            $value = '<i class="' . esc_attr( $item['s_rating_icon'] ) . '" style="color:' . esc_attr( $item['star_color'] ) . ';font-size: 30px;"></i>';
        } else {
            $img = wp_get_attachment_url($item['s_img_off']);
            $value = '<img style="background-color:' . esc_attr( $item['star_color'] ) . '" src="' . esc_url( $img ) . '" alt="' . esc_attr( __( 'Rating icon', 'geodir_reviewratings' ) ) . '"/>';
        }
		return $value;
	}

	public function column_max_rating( $item ) {
		return absint( $item['star_number'] );
	}

    public function column_is_default( $item ) {
        return '<input ' . checked( true, ! empty( $item['is_default'] ), false ) . ' value="' . absint( $item['id'] ) . '" name="default_style" id="gd_style_default" class="geodir-style-set-default" type="radio">';
    }

	public function column_action( $item ) {
		$actions = '<a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=review_rating&section=add_styles&id=' . $item['id'] ) ) . '" title="' . esc_attr__( 'Edit style', 'geodir_reviewratings' ) . '" class="geodir-edit-style"><i class="fas fa-edit"></i></a>';
		if ( empty( $item['is_default'] ) ) {
            $nonce = wp_create_nonce( 'geodir_delete_style_'.$item['id'] );
            $ajax_url = geodir_reviewrating_ajax_url();
            $delete_action = add_query_arg(array('ajax_action' => 'delete_style_category', 'style_id' => $item['id'], '_wpnonce' => $nonce), esc_url($ajax_url));
            $actions .= '&nbsp;&nbsp;&nbsp;<a href="' . esc_url( $delete_action ) . '" class="geodir-delete-style geodir-act-delete" title="' . esc_attr__( 'Delete style', 'geodir_reviewratings' ) . '" onclick="return geodir_reviewrating_delete_rating(this);"><i class="far fa-trash-alt"></i></a>';
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
			'delete' => __( 'Delete', 'geodir_reviewratings' ),
		);
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = apply_filters( 'geodir_review_ratings_styles_items_per_page', 10 );
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

		$search = '';

		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = "AND name LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( $_REQUEST['s'] ) ) ) . "%' ";
		}

        $styles = $wpdb->get_results(
			"SELECT * FROM " . GEODIR_REVIEWRATING_STYLE_TABLE . " WHERE 1 = 1 {$search}" .
			$wpdb->prepare( "ORDER BY id LIMIT %d OFFSET %d;", $per_page, $offset ), ARRAY_A
		);

		$count = $wpdb->get_var( "SELECT COUNT(id) FROM " . GEODIR_REVIEWRATING_STYLE_TABLE . " WHERE 1 = 1 {$search}" );

		$this->items = $styles;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}
}
