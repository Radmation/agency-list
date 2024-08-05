<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Geodir_Review_Rating_Reviews_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'review',
			'plural'   => 'reviews',
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
			'author'   	    => __( 'Author', 'geodir_reviewratings' ),
			'comment'       => __( 'Comment', 'geodir_reviewratings' ),
			'response_to'   => __( 'In Response To', 'geodir_reviewratings' ),
			'submitted_on' 	=> __( 'Submitted On', 'geodir_reviewratings' ),
		);

		return $columns;
	}

	public function column_cb( $item ) {
		$item['id'] = absint( $item['id'] );
		$cb = '<input type="hidden" class="gd-has-id" data-delete-nonce="' . esc_attr( wp_create_nonce( 'geodir-delete-review-' . $item['id'] ) ) . '" data-set-default-nonce="' . esc_attr( wp_create_nonce( 'geodir-set-default-' . $item['id'] ) ) . '" data-review-id="' . $item['id'] . '" value="' . $item['id'] . '" />';
		$cb .= '<input type="checkbox" name="reviews[]" value="' . $item['id'] . '" />';
		return $cb;
	}

	public function column_author( $item ) {
        echo '<strong>'.get_avatar( $item->user_id, 32).$item->comment_author.'</strong><br>';
        echo $item->comment_author_email.'<br>';
        if(isset($item->comment_author_IP)){echo $item->comment_author_IP;}
	}
	
	public function column_comment( $item ) {
        echo '<div class="comment-author">';
        $this->column_author( $item );
        echo '</div>';
	}

	public function column_response_to( $item ) {
        $img = wp_get_attachment_url($item['s_img_off']);
		return '<img style="background-color:' . esc_attr( $item['star_color'] ) . '" src="' . esc_url( $img ) . '" alt="' . esc_attr( __( 'Rating icon', 'geodir_reviewratings' ) ) . '"/>';
	}

	public function column_submitted_on( $item ) {
		return absint( $item['star_number'] );
	}

	public function column_action( $item ) {
		$actions = '<a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=review_rating&section=add_styles&id=' . absint( $item['id'] ) ) ) . '" title="' . esc_attr__( 'Edit style', 'geodir_reviewratings' ) . '" class="geodir-edit-style"><i class="fas fa-edit"></i></a>';
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

		$per_page = apply_filters( 'geodir_review_ratings_items_per_page', 10 );
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

        $geodir_commentsearch = $post_type = '';

        if(isset($_REQUEST['s']))
            $geodir_commentsearch = $_REQUEST['s'];

        if(isset($_REQUEST['geodir_comment_posttype']))
            $post_type = $_REQUEST['geodir_comment_posttype'];

        $status = $_REQUEST['section'];

        $orderby = 'comment_date_gmt';
        $order = 'DESC';
        if(isset($_REQUEST['geodir_comment_sort']) )
        {
            if($_REQUEST['geodir_comment_sort'] == 'oldest'){
                $orderby = 'comment_date_gmt';
                $order = 'ASC';
            }
        }

        $show_post = $_REQUEST['show_post'];

        $defaults = array(
            'show_post' => $show_post,
            'orderby' => $orderby,
            'order' => $order,
            'post_type' => $post_type,
            'comment_approved' => $status,
            'user_id' => '',
            'search' => $geodir_commentsearch,
        );

        $comments = geodir_reviewrating_get_comments($defaults);

		$count = count($comments['comment_count']);

		$this->items = $comments['comments'];

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}

    /**
     * Get sections.
     *
     * @return array
     */
    public function get_statuses() {
        $sections = array(
            ''          => __( 'All', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count().')',
            'pending'   => __( 'Pending', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count('pending').')',
            'approved'=> __( 'Approved', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count('approved').')',
            'spam' 	=> __( 'Spam', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count('spam').')',
            'trash' 	=> __( 'Trash', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count('trash').')',
        );

        return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
    }

	protected function get_views()
    {
        global $current_section;
        $sections = $this->get_statuses();

        if ( empty( $sections ) || 1 === sizeof( $sections ) ) {
            return;
        }

        echo '<ul class="subsubsub">';

        $array_keys = array_keys( $sections );

        foreach ( $sections as $id => $label ) {
            echo '<li><a href="' . admin_url( 'admin.php?page=gd-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
        }

        echo '</ul><br class="clear" />';
    }
}
