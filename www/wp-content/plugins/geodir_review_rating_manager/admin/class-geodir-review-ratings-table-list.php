<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Geodir_Review_Ratings_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'rating',
			'plural'   => 'ratings',
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
            'title'         => __( 'Rating Title', 'geodir_reviewratings' ),
            'style'   	    => __( 'Rating Style', 'geodir_reviewratings' ),
            'image'   	    => __( 'Rating Image', 'geodir_reviewratings' ),
            'post_types' 	=> __( 'Post Types', 'geodir_reviewratings' ),
			'categories' 	=> __( 'Categories', 'geodir_reviewratings' ),
			'display_order' => __( 'Display Order', 'geodir_reviewratings' ),
			'action' 	    => __( 'Actions', 'geodir_reviewratings' ),
		);

		return $columns;
	}

	public function column_cb( $item ) {
		$item['id'] = absint( $item['id'] );
		$cb = '<input type="hidden" class="gd-has-id" data-delete-nonce="' . esc_attr( wp_create_nonce( 'geodir-delete-rating-' . $item['id'] ) ) . '" data-set-default-nonce="' . esc_attr( wp_create_nonce( 'geodir-set-default-' . $item['id'] ) ) . '" data-rating-id="' . $item['id'] . '" value="' . $item['id'] . '" />';
		$cb .= '<input type="checkbox" name="ratings[]" value="' . $item['id'] . '" />';
		return $cb;
	}

    public function column_title( $item ) {
        return $item['title'] . '<small class="gd-meta">' . wp_sprintf( __( 'ID: %d', 'geodir_reviewratings' ), absint( $item['id'] ) ) . '</small>';
    }

	public function column_style( $item ) {
        $rating_style = geodir_get_style_by_id( absint( $item['category_id'] ) );
        return $rating_style->name;
	}

	public function column_image( $item ) {
        $rating_style = geodir_get_style_by_id( absint( $item['category_id'] ) );
        if('font-awesome' == $rating_style->s_rating_type){
            $value = '<i class="'.$rating_style->s_rating_icon.'" style="color:' . esc_attr( $rating_style->star_color ) . ';font-size: 30px;"></i>';
        } else {
            $img = wp_get_attachment_url($rating_style->s_img_off);
            $value = '<img style="background-color:' . esc_attr( $rating_style->star_color ) . '" src="' . esc_url( $img ) . '" alt="' . esc_attr( __( 'Rating icon', 'geodir_reviewratings' ) ) . '"/>';
        }

        return $value;
	}

    public function column_post_types( $item ) {
        $get_post_types = '';
        if ($item['post_type'] != '') {
            $post_types = explode(',', $item['post_type']);

            if (!empty($post_types)) {
                $j = 0;
                $comma = '';

                foreach ($post_types as $ptype) {
                    $post_typeinfo = get_post_type_object($ptype);

                    if ($j != 0)
                        $comma = ', ';

                    $get_post_types .= $comma . geodir_ucwords($post_typeinfo->labels->singular_name);
                    $j++;
                }
            }
            return $get_post_types;
        }
	    return $get_post_types;
    }

	public function column_categories( $item ) {
	    global $wpdb, $table_prefix;

        $category = trim($item['category'], ",");

        $terms = explode(",", $category);
        $rating_term = '';

        foreach ($terms as $termid)
            $rating_term .= $wpdb->get_var($wpdb->prepare("SELECT name FROM " . $table_prefix . "terms WHERE term_id = %d", array($termid))) . ',';

        $cats = trim($rating_term, ',');
		return $cats;
	}

	public function column_display_order( $item ) {
		return '<center>' . absint( $item['display_order'] ) . '</center>';
	}

	public function column_action( $item ) {
		$actions = '<a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=review_rating&section=create&id=' . absint( $item['id'] ) ) ) . '" title="' . esc_attr__( 'Edit rating', 'geodir_reviewratings' ) . '" class="geodir-edit-rating"><i class="fas fa-edit"></i></a>';
		if ( empty( $item['is_default'] ) ) {
            $nonce = wp_create_nonce( 'geodir_delete_rating_'.$item['id'] );
            $ajax_url = geodir_reviewrating_ajax_url();
            $delete_action = add_query_arg(array('ajax_action' => 'delete_rating_category', 'rating_cat_id' => $item['id'], '_wpnonce' => $nonce), esc_url($ajax_url));
			$actions .= '&nbsp;&nbsp;&nbsp;<a href="' . esc_url( $delete_action ) . '" class="geodir-delete-rating geodir-act-delete" title="' . esc_attr__( 'Delete rating', 'geodir_reviewratings' ) . '" onclick="return geodir_reviewrating_delete_rating(this);"><i class="far fa-trash-alt"></i></a>';
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

		$per_page = apply_filters( 'geodir_review_ratings_ratings_items_per_page', 10 );
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
			$search = "AND title LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( $_REQUEST['s'] ) ) ) . "%' ";
		}

        $ratings = $wpdb->get_results(
			"SELECT rt.id as id,
                    rt.title as title,
                    rt.post_type as post_type,
                    rt.category as category,
                    rt.category_id as category_id,
                    rt.check_text_rating_cond as check_text_rating_cond,
                    rt.display_order,
                    rs.s_rating_type  as s_rating_type,
                    rs.s_rating_icon  as s_rating_icon,
                    rs.s_img_off  as s_img_off,
                    rs.s_img_width as s_img_width,
                    rs.s_img_height as s_img_height,
                    rs.star_color as star_color,
                    rs.star_color_off as star_color_off,
                    rs.star_lables as star_lables,
                    rs.star_number as star_number FROM ".GEODIR_REVIEWRATING_CATEGORY_TABLE." rt,".GEODIR_REVIEWRATING_STYLE_TABLE." rs WHERE 1 = 1 AND rt.category_id = rs.id {$search}" .
			$wpdb->prepare( "ORDER BY rt.display_order ASC, rt.id LIMIT %d OFFSET %d;", $per_page, $offset ), ARRAY_A
		);
		
		$count = $wpdb->get_var( "SELECT COUNT(rt.id) FROM ".GEODIR_REVIEWRATING_CATEGORY_TABLE." rt,".GEODIR_REVIEWRATING_STYLE_TABLE." rs WHERE 1 = 1 AND rt.category_id = rs.id {$search}" );

		$this->items = $ratings;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}
}
