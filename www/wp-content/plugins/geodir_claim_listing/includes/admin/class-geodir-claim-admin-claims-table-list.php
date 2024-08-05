<?php
/**
 * Claim Listings Claim Listings Table List.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * GeoDir_Claim_Admin_Claims_Table_List class.
 */
class GeoDir_Claim_Admin_Claims_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'claim',
			'plural'   => 'claims',
			'ajax'     => false,
		) );
	}
	
	public function is_nf_used(){
		$used = false;
		if(function_exists('Ninja_Forms')){
			$forms = Ninja_Forms()->form()->get_forms();
			if(!empty($forms)){
				foreach($forms as $form){
					$settings = $form->get_settings();
					if(isset($settings['key']) && $settings['key']=='geodirectory_claim'){
						$used = true;
					}
				}
			}
		}
		
		return $used;
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			//'cb'            => '<input type="checkbox" />', //@todo implement when we implement bulk features
			'post'   		=> __( 'Listing', 'geodir-claim' ),
			'status'   		=> __( 'Status', 'geodir-claim' ),
			'post_type'   	=> __( 'Post Type', 'geodir-claim' ),
			'user_id'   	=> __( 'User', 'geodir-claim' ),
			'user_fullname' => __( 'Full Name', 'geodir-claim' ),
			'user_number'   => __( 'Phone', 'geodir-claim' ),
			'user_position' => __( 'Position', 'geodir-claim' ),
			'author_id'   	=> __( 'Post Author', 'geodir-claim' ),
			'claim_date'   	=> __( 'Date', 'geodir-claim' ),
			//'id'   			=> __( 'ID', 'geodir-claim' ),
			'full_details'   	=> __( 'Full Details', 'geodir-claim' ),
		);

//		if(self::is_nf_used()){
//			$columns['ninja_forms'] = __( 'Ninja Forms', 'geodir-claim' );
//		}

		return apply_filters( 'geodir_claim_admin_list_claims_columns', $columns );
	}

	public function column_full_details( $item ) {
		$id = $item['id'];
		$nonce = wp_create_nonce( 'geodir-claim-' . $item['id'] );
		$full_detail_url = admin_url('admin-ajax.php?action=geodir_claim_view_request&id='.$id.'&security='.$nonce);
		//$html = "<a href='$full_detail_url' data-lity >".__( 'View', 'geodir-claim' )."</a>";
		$html = "<a href='javascript:void(0)' class='geodir-view-claim'>".__( 'View', 'geodir-claim' )."</a>";

		return $html;
	}

//	public function column_ninja_forms( $item ) {
//		$html = __( 'N/A', 'geodir-claim' );
//		$meta = maybe_unserialize($item['meta']);
//		if(isset($meta['ninja_sub_id'])){
//			$post_id = absint($meta['ninja_sub_id']);
//			$html = "<a href='".admin_url( 'post.php?post='.$post_id .'&action=edit')."' >".__( 'View', 'geodir-claim' )."</a>";
//		}
//		//return print_r($meta,true);
//		return $html;
//	}

	function get_sortable_columns() {
        $sortable_columns = array(
            'status' 			=> array( 'status', true ),
			'author_id' 		=> array( 'author_id', false ),
			'user_id' 			=> array( 'user_id', false ),
			'id' 				=> array( 'id', false ),
			'claim_date' 		=> array( 'claim_date', false )
        );
        return apply_filters( 'geodir_claim_admin_list_claims_sortable_columns', $sortable_columns );
    }

	public function column_cb( $item ) {
		$cb = '<input type="hidden" data-id="' . $item['id'] . '" value="' . $item['id'] . '" />';
		$cb .= '<input type="checkbox" name="claim[]" value="' . $item['id'] . '" />';
		return $cb;
	}

	public function column_id( $item ) {
		return $item['id'];
	}

	public function column_author_id( $item ) {
		$value = $item['author_id'];
		if ( ! empty( $value ) ) {
			$value .= "<br><small>( <a href='" . add_query_arg( array( 'user_id' => absint( $value ) ), admin_url( 'user-edit.php' ) ) . "'>" . esc_attr( geodir_get_client_name( absint( $value ) ) ) . "</a> )</small>";
		}
		return $value;
	}

	public function column_user_id( $item ) {
		$value = $item['user_id'];
		if ( ! empty( $value ) ) {
			$value .= "<br><small>( <a href='" . add_query_arg( array( 'user_id' => absint( $value ) ), admin_url( 'user-edit.php' ) ) . "'>" . esc_attr( geodir_get_client_name( absint( $value ) ) ) . "</a> )</small>";
		}
		return $value;
	}

	public function column_user_fullname( $item ) {
		return $item['user_fullname'];
	}

	public function column_user_number( $item ) {
		return $item['user_number'];
	}

	public function column_user_position( $item ) {
		return $item['user_position'];
	}

	public function column_claim_date( $item ) {
		$value = $item['claim_date'];
		if ( ! empty( $value ) && $value != '0000-00-00 00:00:00' ) {
			$value = '<abbr title="' . $value . '">' . mysql2date( geodir_date_format(), $value ) . '</abbr>';
		} else {
			$value = '-';
		}
		return $value;
	}

	public function column_post( $item ) {
		$edit_link = get_edit_post_link( $item['post_id'] );

		$approve = sprintf( '<a href="javascript:void(0)" class="geodir-approve-claim geodir-act-claim"><i class="fas fa-check" aria-hidden="true"></i> <span>%s</span></a>', __( 'Approve', 'geodir-claim' ) );
		$reject = sprintf( '<a href="javascript:void(0)" class="geodir-reject-claim geodir-act-claim"><i class="fas fa-times" aria-hidden="true"></i> <span>%s</span></a>', __( 'Reject', 'geodir-claim' ) );
		$undo = sprintf( '<a href="javascript:void(0)" class="geodir-undo-claim geodir-act-claim"><i class="fas fa-undo-alt" aria-hidden="true"></i> <span>%s</span></a>', __( 'Undo', 'geodir-claim' ) );
		$delete = sprintf( '<a href="javascript:void(0)" class="geodir-delete-claim geodir-act-claim"><i class="fas fa-trash" aria-hidden="true"></i> <span>%s</span></a>', __( 'Delete', 'geodir-claim' ) );
		
		$actions = array();
		switch ( absint( $item['status'] ) ) {
			case 0 :
				$actions[ 'approve' ] = $approve;
				$actions[ 'reject' ] = $reject;
			break;
			case 1 :
				$actions[ 'undo' ] = $undo;;
			break;
			case 2 :
				$actions[ 'approve' ] = $approve;
			break;
			
		}
		$actions[ 'delete' ] = $delete;
		
		return sprintf(
            '<strong><a href="%s" class="row-claim">%s</strong>%s',
            $edit_link,
            get_the_title( $item['post_id'] ),
            $this->row_actions( $actions )
        );
	}

	public function column_post_type( $item ) {
		return geodir_post_type_singular_name( $item['post_type'], true );
	}

	public function column_status( $item ) {
		$payment_status = '';

		if ( defined( 'GEODIR_PRICING_VERSION' ) && ! empty( $item['payment_id'] ) ) {
			$payment = GeoDir_Pricing_Post_Package::get_item( absint( $item['payment_id'] ) );

			if ( ! empty( $payment ) ) {
				if ( $payment->status == 'publish' || $payment->status == 'completed' ) {
					$payment_status = "<br /><small style='color:green;'>(" . __( 'paid', 'geodir-claim' ) . ")</small>";
				} else if ( strpos( $payment->status, 'pending' ) !== false ) {
					$payment_status = "<br /><small style='color:red;'>(" . __( 'pending payment', 'geodir-claim' ) . ")</small>";
				} else {
					$payment_status = "<br /><small style='color:red;'>(" . __( 'requires payment', 'geodir-claim' ) . ")</small>";
				}
			}
		}

		return '<span class="geodir-claim-status">' . geodir_claim_status_name( $item['status'] ) . $payment_status . '</span>';
	}

	public function column_default( $item, $column_name ) {
		ob_start();
		do_action( 'geodir_claim_admin_list_claims_column', $item, $column_name );
		$value = ob_get_contents();
		ob_end_clean();
		return $value;
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			//'delete' => __( 'Delete', 'geodir-claim' ),
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

		do_action( 'geodir_claim_restrict_manage_claims', $which );

		$actions = ob_get_clean();

		if ( trim( $actions ) == '' ) {
			return;
		}
		?>
		<div class="alignleft actions mb-2">
		<?php
			echo $actions;

			submit_button( __( 'Filter', 'geodir-claim' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
		?>
		</div>
		<?php
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		global $wpdb;

		$post_type = ! empty( $_REQUEST['_claim_post_type'] ) && geodir_is_gd_post_type( $_REQUEST['_claim_post_type'] ) ? sanitize_text_field( $_REQUEST['_claim_post_type'] ) : '';
		$per_page = apply_filters( 'geodir_claim_claims_settings_items_per_page', 10 );
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
		if ( ! empty( $post_type ) ) {
			$where[] = $wpdb->prepare( "post_type = %s", $post_type );
		}

		if ( ! empty( $_REQUEST['s'] ) ) {
			$where[] = "user_fullname LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( $_REQUEST['s'] ) ) ) . "%' OR user_comments LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( $_REQUEST['s'] ) ) ) . "%'";
		}

		if ( ! empty( $_REQUEST['claim_status'] ) ) {
			$claim_status = $_REQUEST['claim_status'];
			if($claim_status=='pending'){
				$where[] = "status=0";
			}elseif($claim_status=='approved'){
				$where[] = "status=1";
			}elseif($claim_status=='rejected'){
				$where[] = "status=2";
			}
		}else{
			$where[] = "status=0";
		}

		$where = ! empty( $where ) ? "WHERE " . implode( ' AND ', $where ) : '';

		$orderby = ! empty( $_REQUEST['orderby'] ) ? geodir_clean( $_REQUEST['orderby'] ) : '';
		if ( ! isset( $sortable[ $orderby ] ) ) {
			$orderby = 'id';
		}
		$order = ! empty( $_REQUEST['order'] ) && $_REQUEST['order'] == 'asc' ? 'ASC' : 'DESC';
		$orderby = "ORDER BY {$orderby} {$order}";

		$results = $wpdb->get_results(
			"SELECT * FROM " . GEODIR_CLAIM_TABLE . " {$where} {$orderby} " .
			$wpdb->prepare( "LIMIT %d OFFSET %d;", $per_page, $offset ), ARRAY_A
		);

		$items = $results;

		$count = $wpdb->get_var( "SELECT COUNT(id) FROM " . GEODIR_CLAIM_TABLE . " {$where};" );

		$this->items = $items;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}

	public function single_row( $item ) {
        static $row_class = '';
        $row_class = ( $row_class == '' ? 'alternate' : '' );

        printf('<tr class="%s geodir-claim-row geodir-claim-status-%s " data-status="' . $item['status'] . '" data-id="' . $item['id'] . '" data-post-type="' . $item['post_type'] . '" data-claim-nonce="' . esc_attr( wp_create_nonce( 'geodir-claim-' . $item['id'] ) ) . '">', $row_class, $item['status']);
        $this->single_row_columns( $item );
        echo '</tr>';
    }

	
	/**
	 *
	 * @global int $post_id
	 * @global string $comment_status
	 * @global string $comment_type
	 */
	protected function get_views() {
		global $post_id;
		$claim_status = isset($_REQUEST['claim_status']) ? esc_attr($_REQUEST['claim_status']) : 'pending';
		$status_links = array();
		$num_claims =  geodir_claim_count_claims();

//		print_r($num_claims);

		$stati = array(
			/* translators: %s: all comments count */
			'all' => _nx_noop(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				'claims',
				'geodir-claim'
			), // singular not used

			/* translators: %s: pending comments count */
			'pending' => _nx_noop(
				'Pending <span class="count">(%s)</span>',
				'Pending <span class="count">(%s)</span>',
				'claims',
				'geodir-claim'
			),

			/* translators: %s: approved comments count */
			'approved' => _nx_noop(
				'Approved <span class="count">(%s)</span>',
				'Approved <span class="count">(%s)</span>',
				'claims',
				'geodir-claim'
			),

			/* translators: %s: spam comments count */
			'rejected' => _nx_noop(
				'Rejected <span class="count">(%s)</span>',
				'Rejected <span class="count">(%s)</span>',
				'claims',
				'geodir-claim'
			),
		);

		if ( !EMPTY_TRASH_DAYS )
			unset($stati['trash']);

		$link = admin_url( 'admin.php?page=gd-settings&tab=claims' );
		if ( !empty($comment_type) && 'all' != $comment_type )
			$link = add_query_arg( 'comment_type', $comment_type, $link );

		foreach ( $stati as $status => $label ) {
			$current_link_attributes = '';

			if ( $status === $claim_status ) {
				$current_link_attributes = ' class="current" aria-current="page"';
			}

			if ( !isset( $num_claims->$status ) )
				$num_claims->$status = 10;
			$link = add_query_arg( 'claim_status', $status, $link );
			if ( $post_id )
				$link = add_query_arg( 'p', absint( $post_id ), $link );
			/*
			// I toyed with this, but decided against it. Leaving it in here in case anyone thinks it is a good idea. ~ Mark
			if ( !empty( $_REQUEST['s'] ) )
				$link = add_query_arg( 's', esc_attr( wp_unslash( $_REQUEST['s'] ) ), $link );
			*/
			$status_links[ $status ] = "<a href='$link'$current_link_attributes>" . sprintf(
					translate_nooped_plural( $label, $num_claims->$status ),
					sprintf( '<span class="%s-count">%s</span>',
						( 'moderated' === $status ) ? 'pending' : $status,
						number_format_i18n( $num_claims->$status )
					)
				) . '</a>';
		}

		/**
		 * Filters the comment status links.
		 *
		 * @since 2.5.0
		 *
		 * @param array $status_links An array of fully-formed status links. Default 'All'.
		 *                            Accepts 'All', 'Pending', 'Approved', 'Spam', and 'Trash'.
		 */
		return apply_filters( 'comment_status_links', $status_links );
	}

}
