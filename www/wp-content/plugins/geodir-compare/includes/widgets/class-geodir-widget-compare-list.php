<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GeoDir_Compare_Shortcodes
 *
 * @since 1.0.0
 */
class GeoDir_Widget_Compare_List extends WP_Super_Duper {

	public $arguments;

	/**
	 * Main class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$options = array(
			'textdomain'            => 'geodir-compare',
			'block-icon'            => 'admin-site',
			'block-category'        => 'common',
			'block-keywords'        => "['compare','listings','geodirectory']",
			'base_id'               => 'gd_compare_list',
			'class_name'            => __CLASS__,
			'name'                  => __('GD > Compare List','geodir-compare'),
			'widget_ops'            => array(
				'classname'         => 'geodir-compare-list' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'       => esc_html__('Displays a listings comparison table.','geodir-compare'),
			),
			'arguments'     => array(
			'items'  => array(
				'title' => __('Listing IDs:', 'geodir-compare'),
				'desc' => __('Enter listing IDs from the same Post Type to compare them.', 'geodir-compare'),
				'type' => 'text',
				'placeholder' => __( '11,22,33,44', 'geodir-compare' ),
				'default'  => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'allow_remove'      => array(
				'title'         => __('Allow users to remove items from the table', 'geodir-compare'),
				'type'          => 'checkbox',
				'default'       => '1',
				'desc_tip'      => true,
				'advanced'      => false
			)
		),
		);

		parent::__construct( $options );
	}


	/**
	 * Displays a comparison table
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Get Arguments.
	 * @param array $widget_args Get widget arguments.
	 * @param string $content Get widget content.
	 * @return string
	 *
	 */
	public function output( $args = array(), $widget_args = array(),$content = '' ){
		$design_style = geodir_design_style();
		$items          = '';
		$allow_remove   =  empty( $args['items'] );

		$is_preview = $this->is_preview();
		$block_preview = $this->is_block_content_call() || $is_preview;

		if ( $block_preview ) {
			return '';
		}

		//Display the remove button???
		if(! empty( $args['allow_remove'] )){
			$allow_remove  = ( '1' == $args['allow_remove'] );
		}

		//Get the items to compare from the url...
		if(! empty( $_GET['compareids'] )){
			$items = $_GET['compareids'];
		}

		//... unless they are hardcoded into the shortcode
		if(! empty( $args['items'] )){
			$items      = $args['items'];
		}

		//Next, convert the items into an array
		$items = array_unique( explode( ',', $items ) );

		//And then restrict items to the first gd post type. We don't want users
		//comparing items across CPTs since CPTs have different custom fields
		$post_type  = false;
		foreach( $items as $key=>$item ){

			$item = trim( $item );

			//Skip empty items
			if( empty( $item ) ) {
				unset( $items[$key] );
				continue;
			}

			$_post_type = get_post_type( $item );
			if( geodir_is_gd_post_type($_post_type) ){
				$post_type = $_post_type; //Break loop
				break;
			}

		}

		// Add required script
		if ( $design_style ) {
			add_action( 'wp_footer', 'geodir_compare_aui_script', 200 );
		}

		//Abort early if we don't have any gd items
		if( empty( $items ) || !$post_type ) {
			return geodir_no_compare_found();
		}

		//Fetch listings
		$listings   = $this->get_comparison_items( $items, $post_type );
		if ( empty( $listings ) ) {
			return geodir_no_compare_found();
		}

		//And comparison fields
		$fields = $this->get_comparison_fields( $post_type );

		$template = $design_style ? $design_style . "/compare-list.php" : "legacy/compare-list.php";

		// wrap class
		$wrap_class = geodir_build_aui_class( $args );

		$template_args = array(
			'args'  			=> $args,
			'wrap_class'    	=> $wrap_class,
			'listings'			=> $listings,
			'fields'			=> $fields,
			'post_type'			=> $post_type,
			'allow_remove'		=> $allow_remove,

		);

		//Then display them
		return geodir_get_template_html( $template, $template_args, '', plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ). "/templates/" );
	   
	}

	/**
	 * Returns an array of items to compare
	 *
	 * @param $items Array Required. An array of item ids. Will be truncated to the first 5
	 * @param $post_type String Required. The post type of the items
	 * 
	 * @since 1.0.0
	 * @return Array An array of objects of each item as saved in its CPT table
	 */
	public function get_comparison_items( $items, $post_type ) {
		global $wpdb;

		$table     = geodir_db_cpt_table( $post_type );

		//Sanitize the items
		foreach( $items as $key => $item ) {
			$items[$key] = $wpdb->prepare( '%d', $item );
		}

		//Then prepare the sql query, limiting it to published items that the user wants
		$items     = implode( ',', $items );

		$statuses = geodir_get_publish_statuses( array( 'post_type' => $post_type ) );

		$where = count( $statuses ) > 1 ? "post_status IN( '" . implode( "', '", $statuses ) . "' )" : "post_status = '" . $statuses[0] . "'";

		//Get maximum number of listings to compare
		$max = $wpdb->prepare( '%d', geodir_compare_maximum_listings() );
		$sql = "SELECT `{$table}`.* FROM $table  WHERE {$where} AND post_id IN ( $items ) LIMIT 0,$max";

		//Fetch and return the listings
		return $wpdb->get_results( $sql );
	}

	/**
	 * Returns an array of feature fields
	 *
	 *
	 * @since 1.0.0
	 * @return array An assosiative array of features in the form of  array( htmlvar_name => title )
	 */
	public function get_comparison_fields( $post_type ) {
		return geodir_post_custom_fields( '',  'all', $post_type , 'compare' );
	}

	/**
	 * Returns the comparison table html code
	 *
	 * @param $listings Array. The listings to display
	 * @param $fields Array. The fields to display
	 * @param $post_type String. The post type of the items being compared
	 * @param  $allow_remove Bool. Whether or not to allow users to remove items from the table
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_comparison_body( $listings, $fields, $post_type, $allow_remove = true ) {
		global $post;

		//Save a reference to the global post object
		$_post = $post;

		//Maybe abort early
		if( empty( $listings ) ){
			return;
		}

		$show_images = true;
		$show_title = true;
		$return = '';

		if(function_exists('geodir_show_hints') && geodir_show_hints()){
			$return .= geodir_output_hint(
				array(
					__("Set what fields are compared in each CPT field settings under `Show in extra output location`","geodir-compare"),
					__("You can share urls comparing listings","geodir-compare"),
					__("You can embed a shortcode comparing specific listings on any page.","geodir-compare"),
				),
				"https://docs.wpgeodirectory.com/article/56-compare-listings", // documentation url
				"", // video documentation url
				"compare_listings" // feedback id
			);
		}

		/*
		 * ______________| Image Item 1  | Image Item 2 | Image Item 3   |
		 * Feature Name  | Item 1 value  | Item 2 Value | Item 3 Value   |
		 * Feature2 Name | Item 1 value  | Item 2 Value | Item 3 Value   |
		 * 
		 */
		$return .= '<table class="geodir-compare-page-table"><thead><tr><th class="geodir-compare-listing-header-titles"></th>';

		//Print the items table headers, i.e image and title
		$has_ratings = ! geodir_cpt_has_rating_disabled( $post_type ); //Checking it here prevents checking it severally

		$width = count($listings)>0 ? 90/count($listings) : 90;

		foreach( $listings as $listing ) {


			//Css class
			$class = 'geodir-compare-listing-header geodir-compare-post geodir-compare-' . $listing->post_id;

			//Remove button
			$remove_button = $allow_remove ? sprintf(
				'<span onclick="geodir_compare_remove_from_table(\'%s\', \'%s\')" class="geodir-compare-table-remove-listing"><i title="' . esc_attr__( 'Remove', 'geodir-compare' ) . '" class="fas fa-times-circle" aria-hidden="true"></i></span>',
				$listing->post_id,
				$post_type
			) : '';

			$return .= "<th class='$class' style='width: $width%;'>$remove_button</th>";
		}



		$return .= '</tr></thead><tbody>';



		if(!empty($fields)) {
			foreach ( $fields as $field => $field_info ) {
				if(isset($field_info['htmlvar_name'])){
					if($field_info['htmlvar_name']=='post_title'){
						$show_title = false;
					}
					elseif($field_info['htmlvar_name']=='post_images'){
						$show_images = false;
					}
				}

			}
		}

		// title
		if($show_title){
			$return .= "<tr class='geodir-compare-field-title'><td></td>";
			foreach( $listings as $listing ) {
				//Switch to this as the global post object
				$post    = get_post( $listing->post_id );
				setup_postdata( $post );
				//Link to the post
				$link    = esc_url( get_the_permalink( $listing->post_id ) );
				$return .= "<td class='geodir-compare-images geodir-compare-{$listing->post_id}'><a href='$link'><h5>".esc_attr($listing->post_title)."</h5></a></td>";
			}
			$return .= '</tr>';
		}

		// images
		if($show_images){
			$return .= "<tr class='geodir-compare-field-images'><td>".__("Images","geodir-compare")."</td>";
			foreach( $listings as $listing ) {
				//Switch to this as the global post object
				$post    = get_post( $listing->post_id );
				setup_postdata( $post );
				$images   = do_shortcode("[gd_post_images show_title='1' slideshow='1' ajax_load='1 type='slider' cover='x' image_size='medium']");
				$return .= "<td class='geodir-compare-images geodir-compare-{$listing->post_id}'>$images</td>";
			}
			$return .= '</tr>';
		}

		// ratings
		if( $has_ratings ) {
			$return .= "<tr class='geodir-compare-field-ratings'><td>".__("Ratings","geodir-compare")."</td>";
			foreach( $listings as $listing ) {
				//Switch to this as the global post object
				$post    = get_post( $listing->post_id );
				setup_postdata( $post );
				$rating  = do_shortcode("[gd_post_rating]");
				$return .= "<td class='geodir-compare-ratings geodir-compare-{$listing->post_id}'>$rating</td>";
			}
			$return .= '</tr>';
		}


		//Finally, print the table body
		if(!empty($fields)){
			foreach( $fields as $field => $field_info ) {

				$field_info = stripslashes_deep( $field_info );

				$class   = esc_attr( 'geodir-compare-field-' . $field_info['type'] );
				$return .= "<tr class='$class'><td>{$field_info['frontend_title']}</td>";

				$key = 0;
				while( $key < count( $listings ) ) {

					//Display the content of the CF
					$class   = 'geodir-compare-' . $listings[$key]->post_id;

					//Temporarily change the global post object
					$post    = get_post( $listings[$key]->post_id );
					setup_postdata( $post );

					//Output the row col content
					if($field_info['htmlvar_name']=='post_title'){
						$link    = esc_url( get_the_permalink( $listings[$key]->post_id ) );
						$output  = "<a href='$link'><h5>".apply_filters( "geodir_custom_field_output_{$field_info['type']}", '', 'compare', $field_info, $listings[$key]->post_id, 'value')."</h5></a>";
					}
					elseif( $field_info['type'] == 'images' ) {
						$output  = do_shortcode("[gd_post_images show_title='1' slideshow='1' ajax_load='1 type='slider'  link_to='lightbox']");
					} else {
						$output  = apply_filters( "geodir_custom_field_output_{$field_info['type']}", '', 'compare', $field_info, $listings[$key]->post_id, 'value');
					}

					$return .= "<td class='$class'>$output</td>";

					//Revert to the original global post object
					$post    = $_post;
					setup_postdata( $post );

					$key ++;
				}

				$return .= '</tr>';
			}
		}

		//Revert to the original global post object
		$post    = $_post;
		setup_postdata( $post );

		if(wp_doing_ajax()){
			$return .= $this::ajax_js();
		}


		return $return . '</tbody></table>';

	}

	/**
	 * Load and call any JS needed to show the compare table via ajax.
	 *
	 * @return string
	 */
	public function ajax_js(){
		ob_start();
		?>
		<script type="text/javascript">/* <![CDATA[ */ 
			jQuery(function($) {
				// load flexslider if not loaded
				if (!$.flexslider) {
					$.getScript("<?php echo geodir_plugin_url(); ?>/assets/js/jquery.flexslider.min.js?ver=<?php echo GEODIRECTORY_VERSION; ?>", function(data, textStatus, jqxhr) {
						init_read_more();
						geodir_init_lazy_load();
						geodir_refresh_business_hours();
						try { 
							geodir_init_flexslider();
						} catch(e) {
							console.log(e.message);
						}
					});
				} else {
					init_read_more();
					geodir_init_lazy_load();
					geodir_refresh_business_hours();
					try { 
						geodir_init_flexslider();
					} catch(e) {
						console.log(e.message);
					}
				}
			});
		/* ]]> */</script>
		<?php
		return ob_get_clean();
	}
}