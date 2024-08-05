<?php
/**
 * Contains the main ajax handler class
 *
 *
 * @since 1.0.0
 *
 */


/**
 * Ajax Handler Class
 *
 */
class GeoDir_Compare_Ajax {

	/**
	 * GeoDir_Compare_Ajax Constructor.
	 *
	 *
	 * @since 1.0.0
	 * @return GeoDir_Compare_Ajax - Main instance.
	 */
	public function __construct() {

		//List comparison items html
		add_action( 'wp_ajax_geodir_compare_get_items', array( $this, 'get_items_html') );
		add_action( 'wp_ajax_nopriv_geodir_compare_get_items', array( $this, 'get_items_html') );

	}
	
	/**
	 * Returns an array of saved comparison items
	 *
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_items() {

		//Items are sent with the request
		$items = array();
		if( ! empty($_REQUEST['items']) ) {
			$items = json_decode( stripslashes ( $_REQUEST['items'] ) );
		}

		return (array) $items;

	}
	
	/**
	 * Prints the header in the popups
	 *
	 *
	 * @since 1.0.0
	 */
	public function print_header( $post_type, $items = false ) {
		global $aui_bs5;

		$post_types = get_post_types( array(), 'objects' );

		if ( is_object( $post_types[$post_type] ) ) {
			$post_type = $post_types[$post_type]->label;
		}

		$design_style = geodir_design_style();

		$link = '';
		if ( is_array( $items ) ) {
			$items = implode( ',', $items );
			$link  = get_the_permalink( geodir_get_option('geodir_compare_listings_page') );

			if($design_style){
				$link = sprintf(
					'<a href="%s" class="' . ( $aui_bs5 ? 'ms-2' : 'ml-2' ) . ' btn btn-sm btn-outline-primary">%s</a>',
					esc_url( add_query_arg( 'compareids', $items, $link ) ),
					__( 'View shareable link', 'geodir-compare' )
				);
			}else{
				$link = sprintf(
					'<a href="%s" class="geodir-compare-padded-left">%s</a>',
					esc_url( add_query_arg( 'compareids', $items, $link ) ),
					__( 'View shareable link', 'geodir-compare' )
				);
			}
		}

		if ( $design_style ) {
			printf( '<div class="modal-header pt-0 mt-0 mb-3 mx-n3 d-flex justify-content-start align-items-center"><h5 class="modal-title m-0">%s %s</h5> %s' . ( $aui_bs5 ? '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' . esc_attr__( 'Close', 'geodir-compare' ) . '"></button>' : '<button type="button" class="close" data-dismiss="modal" aria-label="' . esc_attr__( 'Close', 'geodir-compare' ) . '"><span aria-hidden="true">&times;</span></button>' ) . '</div>',
				__('Compare', 'geodir-compare'),
				esc_html( $post_type ),
				$link
			);
		}else{
			printf(
				'<div class="geodir-compare-flex geodir-compare-padded"><h2>%s %s</h2> %s</div>',
				__('Compare', 'geodir-compare'),
				esc_html( $post_type ),
				$link
			);
		}


		//Print additional messages
		if(! empty( $_REQUEST['removed'] ) ) {
			$id = absint( $_REQUEST['removed'] );

			if( $design_style ) {
				echo aui()->alert(array(
						'type'=> 'info',
						'content'=> sprintf(
							'<strong>%s</strong> %s',
							get_the_title( $id ),
							__( 'was successfully removed from the comparison list', 'geodir-compare' )
						),
						'dismissible'   => true
					)
				);
				;
			}else{
				printf(
					'<p class="geodir-compare-notice"><strong>%s</strong> %s</p>',
					get_the_title( $id ),
					__( 'was successfully removed from the comparison list', 'geodir-compare' )
				);
			}
		}

	}
	
	/**
	 * Prints the footer in the popups
	 *
	 *
	 * @since 1.0.0
	 */
	public function print_footer( $items ) {

		$items = implode( ',', $items );
		$link  = get_the_permalink( geodir_get_option('geodir_compare_listings_page') );
		$link  = esc_url( add_query_arg( 'compareids', $items, $link ) );
		
		printf( 
			'<a href="%s" class="geodir-compare-popup-button lity-button lity-button-primary">%s</a>',
			$link,
			__( 'Compare', 'geodir-compare' )
		);

	}
	
	/**
	 * Prints the list of items being compared
	 *
	 *
	 * @since 1.0.0
	 */
	public function print_list( $compare_with,  $post_type , $reached_maximum = false ) {

		$design_style = geodir_design_style();

		//Maximum notice
		if( $reached_maximum ) {

			//Header
			$this->print_header( $post_type );

			if($design_style){
				echo aui()->alert(array(
						'type'=> 'info',
						'content'=> __( 'You have reached the maximum number of items to compare. Remove some first.', 'geodir-compare' )
					)
				);
				echo '<ul class="geodir-compare-popup-list-container list-group p-0 m-0">';
			}else{
				_e( 'You have reached the maximum number of items to compare. Please remove some first.', 'geodir-compare' );
				echo '<ul class="geodir-compare-popup-list-container">';
			}

			$this->print_list_contents( $compare_with,  $post_type );
			echo '</ul>';

		} elseif( empty( $compare_with ) ){

			//Header
			$this->print_header( $post_type );
			echo '<ul class="geodir-compare-popup-list-container">';
			$this->print_list_empty();
			echo '</ul>';

		} else {
			//Header
			$this->print_header( $post_type, $compare_with );
			$items = implode( ',',$compare_with );
			echo do_shortcode( "[gd_compare_list items='$items' allow_remove='1']" );
		}

		//Footer
		/*if(! $reached_maximum && ! empty( $compare_with ) ) {
			$this->print_footer( $compare_with );
		}*/
	}
	
	/**
	 * Tell the user that their list is empty
	 *
	 *
	 * @since 1.0.0
	 */
	public function print_list_empty() {
		printf(
			'<li class="geodir-empty">%s</li>',
			__( 'Your comparison list is empty.', 'geodir-compare' )
		);
	}
	
	/**
	 * Print list contents to the user
	 *
	 *
	 * @since 1.0.0
	 */
	public function print_list_contents( $items,  $post_type ) {
		global $aui_bs5;

		$design_style = geodir_design_style();

		foreach( $items as $id ) {
			if ( $design_style ) {
				printf(
					'<li class="list-group-item list-group-item-action list-unstyled d-flex justify-content-between  align-items-center"><span><span style="height: 60px;width:60px;" class="d-inline-block ' . ( $aui_bs5 ? ' me-3' : ' mr-3' ) . '">%s</span> <h6 class="d-inline-block h4">%s</h6></span><div class="geodir-compare-popup-list-actions">%s</div></li>',
					geodir_compare_get_listing_image( $id ),
					get_the_title( $id ),
					sprintf(
						'<a href="#" class="geodir-compare-popup-remove btn btn-danger" onclick="geodir_compare_remove(\'%s\', \'%s\')">%s</a>',
						$id,
						$post_type,
						__( 'Remove', 'geodir-compare' )
					)

				);
			}else{
				printf(
					'<li>%s <div class="geodir-compare-popup-list-details"><h3>%s</h3><div class="geodir-compare-popup-list-actions">%s</div></div></li>',
					geodir_compare_get_listing_image( $id ),
					get_the_title( $id ),
					sprintf(
						'<a href="#" class="geodir-compare-popup-remove" onclick="geodir_compare_remove(\'%s\', \'%s\')">%s</a>',
						$id,
						$post_type,
						__( 'Remove', 'geodir-compare' )
					)

				);
			}


		}
	}

	/**
	 * Convert items array to html list
	 *
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 */
	public function get_items_html() {
		global $wpdb;

		//Prepare the item ids to compare with
		$compare_with = $this->get_items();
		$post_type    = esc_attr($_REQUEST['post_type']);
		if(!geodir_is_gd_post_type($post_type)){exit;}

		$items = array();
		if(! empty( $compare_with[$post_type] ) ) {
			$items = array_keys( (array) $compare_with[$post_type] );
		}

		//If there are some items, let's filter them to ensure all are published and belong to this post type
		if(! empty( $items ) ) {
			$items = $this->get_items_from_db( $items, $post_type );
		}

		/**
		 * Filters the comparison items
		 *
		 * @since 1.0.0
		 *
		*/
		$items = apply_filters( 'geodir_compare_ajax_items', $items, $post_type );

		//Get maximum number of listings to compare
		$max = geodir_compare_maximum_listings();

		//Have we reached the maximum # of items to compare?
		$reached_max = count( $items ) > $max;

		//List
		$this->print_list( $items, $post_type, $reached_max );

		exit;

	}

	/**
	 * Retrieves item ids from the db
	 *
	 *
	 * @since 1.0.0
	 */
	public function get_items_from_db( $items, $post_type ) {
		global $wpdb;

		//Sanitize the items
        foreach( $items as $key => $item ) {
            $items[$key] = $wpdb->prepare( '%d', $item );
		}
		
		$_post_type = $wpdb->prepare( '%s', $post_type );

		$statuses = geodir_get_publish_statuses( array( 'post_type' => $post_type ) );

		$where = count( $statuses ) > 1 ? "post_status IN( '" . implode( "', '", $statuses ) . "' )" : "post_status = '" . $statuses[0] . "'";

        //Then prepare the sql query, limiting it to published items that the user wants
		$items     = implode( ',', $items );
		$table 	   = $wpdb->posts;
        $sql       = "SELECT ID FROM {$table} WHERE {$where} AND ID IN ( $items ) AND post_type = $_post_type";

		//Get the items
		return $wpdb->get_col( $sql );
	}
}
