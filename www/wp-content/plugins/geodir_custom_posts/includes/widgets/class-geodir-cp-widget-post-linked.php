<?php
/**
 * GeoDir_CP_Widget_Post_Linked class.
 *
 * @since 2.0.0
 */
class GeoDir_CP_Widget_Post_Linked extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'       => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'       => 'admin-links',
			'block-category'   => 'geodirectory',
			'block-keywords'   => "['linked','geodir','geodirectory']",
			'class_name'       => __CLASS__,
			'base_id'          => 'gd_linked_posts',
			'name'             => __( 'GD > Linked Posts', 'geodir_custom_posts' ),
			'widget_ops'       => array(
				'classname'    => 'geodir-linked-posts' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'  => esc_html__( 'Displays the linked posts. Note: This widget will be removed in future, use GD > GD Listings widget to show linked posts.', 'geodir_custom_posts' ),
				'geodirectory' => true,
			)
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 *
	 */
	public function set_arguments() {
		$design_style = geodir_design_style();

		$arguments = array(
			'title' => array(
				'title' => __( 'Title:', 'geodir_custom_posts' ),
				'desc' => __( 'The widget title.', 'geodirectory' ),
				'type' => 'text',
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'id' => array(
				'type' => 'number',
				'title' => __( 'Post ID:', 'geodir_custom_posts' ),
				'desc' => __( 'Leave blank to use current post id.', 'geodir_custom_posts' ),
				'placeholder' => 'Leave blank to use current post id.',
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'link_type' => array(
				'title' => __( 'Link Type:', 'geodir_custom_posts' ),
				'desc' => __( 'The type of link to show.', 'geodir_custom_posts' ),
				'type' => 'select',
				'options' => array(
					'to' => __( 'Linked to', 'geodir_custom_posts' ),
					'from' => __( 'Linked from', 'geodir_custom_posts' )
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'post_type' => array(
				'title' => __( 'Post Type:', 'geodir_custom_posts' ),
				'desc' => __( 'The custom post type to show by default.', 'geodir_custom_posts' ),
				'type' => 'select',
				'options' => array_merge( array( '' => __( 'Auto (same as current post)', 'geodir_custom_posts' ) ), geodir_get_posttypes( 'options-plural' ) ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'sort_by' => array(
				'title' => __( 'Sort by:', 'geodir_custom_posts' ),
				'desc' => __( 'How the listings should be sorted.', 'geodir_custom_posts' ),
				'type' => 'select',
				'options' => $this->get_sort_options(),
				'default' => '',
				'desc_tip' => true,
				'advanced' => true
			),
			'title_tag' => array(
				'title' => __( 'Title tag:', 'geodir_custom_posts' ),
				'desc' => __( 'The title tag used for the listings.', 'geodir_custom_posts' ),
				'type' => 'select',
				'options' => array(
					"h3"        => __( 'h3 (default)', 'geodir_custom_posts' ),
					"h2"        => __( 'h2 (if main content of page)', 'geodir_custom_posts' ),
				),
				'default' => 'h3',
				'desc_tip' => true,
				'advanced' => true
			),
			'layout' => array(
				'title' => __( 'Layout:', 'geodir_custom_posts' ),
				'desc' => __( 'How the listings should laid out by default.', 'geodir_custom_posts' ),
				'type' => 'select',
				'options' => geodir_get_layout_options(),
				'default' => 'h3',
				'desc_tip' => true,
				'advanced' => true
			),
			'post_limit' => array(
				'title' => __( 'Posts to show:', 'geodir_custom_posts' ),
				'desc' => __( 'The number of posts to show by default.', 'geodir_custom_posts' ),
				'type' => 'number',
				'default' => '5',
				'desc_tip' => true,
				'advanced' => true
			),
			'view_all_link' => array(
				'title' => __( 'Show view all link?', 'geodir_custom_posts' ),
				'type' => 'checkbox',
				'desc_tip' => true,
				'value' => '1',
				'default' => '1',
				'advanced' => true
			)
		);

		if ( $design_style ) {
			$arguments['row_gap'] = array(
				'title' => __( "Card row gap", 'geodirectory' ),
				'desc' => __( 'This adjusts the spacing between the cards horizontally.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( "Default", "geodirectory" ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( "Card Design", "geodirectory" )
			);

			$arguments['column_gap'] = array(
				'title' => __( "Card column gap", 'geodirectory' ),
				'desc' => __( 'This adjusts the spacing between the cards vertically.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( "Default", "geodirectory" ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( "Card Design", "geodirectory" )
			);

			$arguments['card_border'] = array(
				'title' => __( "Card border", 'geodirectory' ),
				'desc' => __( 'Set the border style for the card.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
								  '' => __( "Default", "geodirectory" ),
								  'none' => __( "None", "geodirectory" ),
				) + geodir_aui_colors(),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( "Card Design", "geodirectory" )
			);

			$arguments['card_shadow'] = array(
				'title' => __( "Card shadow", 'geodirectory' ),
				'desc' => __( 'Set the card shadow style.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( "None", "geodirectory" ),
					'small' => __( "Small", "geodirectory" ),
					'medium' => __( "Medium", "geodirectory" ),
					'large' => __( "Large", "geodirectory" ),
				),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( "Card Design", "geodirectory" )
			);

			// background
			$arguments['bg'] = geodir_get_sd_background_input( 'mt' );

			// margins
			$arguments['mt'] = geodir_get_sd_margin_input( 'mt' );
			$arguments['mr'] = geodir_get_sd_margin_input( 'mr' );
			$arguments['mb'] = geodir_get_sd_margin_input( 'mb', array( 'default' => 3 ) );
			$arguments['ml'] = geodir_get_sd_margin_input( 'ml' );

			// padding
			$arguments['pt'] = geodir_get_sd_padding_input( 'pt' );
			$arguments['pr'] = geodir_get_sd_padding_input( 'pr' );
			$arguments['pb'] = geodir_get_sd_padding_input( 'pb' );
			$arguments['pl'] = geodir_get_sd_padding_input( 'pl' );

			// border
			$arguments['border'] = geodir_get_sd_border_input( 'border' );
			$arguments['rounded'] = geodir_get_sd_border_input( 'rounded' );
			$arguments['rounded_size'] = geodir_get_sd_border_input( 'rounded_size' );

			// shadow
			$arguments['shadow'] = geodir_get_sd_shadow_input( 'shadow' );
		}

		/*
		 * Elementor Pro features below here
		 */
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) && class_exists( 'GeoDir_Elementor' ) ) {
			$arguments['skin_id'] = array(
				'title' => __( "Elementor Skin", 'geodirectory' ),
				'desc' => '',
				'type' => 'select',
				'options' => GeoDir_Elementor::get_elementor_pro_skins(),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group'     => __( "Design", "geodirectory" )
			);

			$arguments['skin_column_gap'] = array(
				'title' => __( 'Skin column gap', 'geodirectory' ),
				'desc' => __( 'The px value for the column gap.', 'geodirectory' ),
				'type' => 'number',
				'default' => '30',
				'desc_tip' => true,
				'advanced' => false,
				'group'     => __( "Design", "geodirectory" )
			);
			$arguments['skin_row_gap'] = array(
				'title' => __( 'Skin row gap', 'geodirectory' ),
				'desc' => __( 'The px value for the row gap.', 'geodirectory' ),
				'type' => 'number',
				'default' => '35',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( "Design", "geodirectory" )
			);
		}

		return $arguments;
	}


	/**
	 * Outputs the linked posts on the front-end.
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $post;

		$args = wp_parse_args(
			(array)$args,
			array(
				'title' => '',
				'id' => '',
				'post_type' => '',
				'sort_by' => 'az',
				'title_tag' => 'h3',
				'list_order' => '',
				'post_limit' => '5',
				'display' => 'layout',
				'layout' => '2',
				'listing_width' => '',
				'character_count' => '20',
				'link_type' => 'to',
				'view_all_link' => '1',
				// Elementor settings
				'skin_id' => '',
				'skin_column_gap' => '',
				'skin_row_gap' => '',
				// AUI settings
				'column_gap' => '',
				'row_gap' => '',
				'card_border' => '',
				'card_shadow' => '',
				'bg' => '',
				'mt' => '',
				'mb' => '3',
				'mr' => '',
				'ml' => '',
				'pt' => '',
				'pb' => '',
				'pr' => '',
				'pl' => '',
				'border' => '',
				'rounded' => '',
				'rounded_size' => '',
				'shadow' => '',
            )
        );

		ob_start();

        $this->output_html( $widget_args, $args );

        return ob_get_clean();
	}

	/**
     * Generates popular postview HTML.
     *
     * @since   1.0.0
     * @since   1.6.24 View all link should go to search page with near me selected.
     * @package GeoDirectory
     * @global object $post                    The current post object.
     * @global string $gd_layout_class The girdview style of the listings for widget.
     * @global bool $geodir_is_widget_listing  Is this a widget listing?. Default: false.
     *
     * @param array|string $args               Display arguments including before_title, after_title, before_widget, and
     *                                         after_widget.
     * @param array|string $instance           The settings for the particular instance of the widget.
     */
    public function output_html( $args = '', $instance = '' ) {
		global $gd_post, $post;

		if ( empty( $instance['id'] ) && ! empty( $gd_post->ID ) ) {
			$instance['id'] = $gd_post->ID;
		}

		if ( empty( $instance['id'] ) ) {
			return;
		}

		if ( empty( $instance['post_type'] ) ) {
			if ( ! empty( $gd_post->post_type ) ) {
				$instance['post_type'] = $gd_post->post_type;
			} else {
				$instance['post_type'] = get_post_type( $instance['id'] );
			}
		}

		$post_id = $instance['id'];
		$post_type = $instance['post_type'];
		$link_type = isset( $instance['link_type'] ) && $instance['link_type'] ? $instance['link_type'] : 'to';

		extract( $args, EXTR_SKIP );

		/** This filter is documented in includes/widget/class-geodir-widget-advance-search.php.php */
		$title = empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', __( $instance['title'], 'geodirectory' ) );

		/**
		 * Filter the widget post type.
		 *
		 * @since 1.0.0
		 *
		 * @param string $instance ['post_type'] Post type of listing.
		 */
		$post_type = empty( $instance['post_type'] ) ? 'gd_place' : apply_filters( 'widget_post_type', $instance['post_type'] );

		/**
		 * Filter the widget listings limit.
		 *
		 * @since 1.0.0
		 *
		 * @param string $instance ['post_number'] Number of listings to display.
		 */
		$post_number = empty( $instance['post_limit'] ) ? '5' : apply_filters( 'widget_post_number', $instance['post_limit'] );

		/**
		 * Filter posts "display" type.
		 *
		 * @since 2.0.0
		 *
		 * @param string $instance ['display'] Widget layout type.
		 */
		$display = empty( $instance['display'] ) ? 'layout' : apply_filters( 'geodir_widget_link_posts_display', $instance['display'] );

		/**
		 * Filter widget's "layout" type.
		 *
		 * @since 1.0.0
		 *
		 * @param string $instance ['layout'] Widget layout type.
		 */
		$layout = empty( $instance['layout'] ) ? '2' : apply_filters( 'widget_layout', $instance['layout'] );

		/**
		 * Filter widget's listing width.
		 *
		 * @since 1.0.0
		 *
		 * @param string $instance ['listing_width'] Listing width.
		 */
		$listing_width = empty( $instance['listing_width'] ) ? '' : apply_filters( 'widget_listing_width', $instance['listing_width'] );

		/**
		 * Filter widget's "list_sort" type.
		 *
		 * @since 1.0.0
		 *
		 * @param string $instance ['list_sort'] Listing sort by type.
		 */
		$list_sort             = empty( $instance['sort_by'] ) ? 'latest' : apply_filters( 'widget_list_sort', $instance['sort_by'] );

		/**
		 * Filter widget's "title_tag" type.
		 *
		 * @since 1.6.26
		 *
		 * @param string $instance ['title_tag'] Listing title tag.
		 */
		$title_tag            = empty( $instance['title_tag'] ) ? 'h3' : apply_filters( 'widget_title_tag', $instance['title_tag'] );

		/**
		 * Filter the widget skin_id param.
		 *
		 * @since 2.0.0.86
		 *
		 * @param string $instance ['skin_id'] Filter skin_id.
		 */
		$skin_id = empty( $instance['skin_id'] ) ? '' : apply_filters( 'widget_skin_id', $instance['skin_id'], $instance, $this->id_base );

		$design_style = geodir_design_style();

		if ( ! geodir_is_gd_post_type( $post_type ) ) {
			return;
		}

		// Replace widget title dynamically
		$posttype_plural_label   = geodir_post_type_name( $post_type, true );
		$posttype_singular_label = geodir_post_type_singular_name( $post_type, true );

		$title = str_replace( "%posttype_plural_label%", $posttype_plural_label, $title );
		$title = str_replace( "%posttype_singular_label%", $posttype_singular_label, $title );

		if ( isset( $instance['character_count'] ) ) {
			/**
			 * Filter the widget's excerpt character count.
			 *
			 * @since 1.0.0
			 *
			 * @param int $instance ['character_count'] Excerpt character count.
			 */
			$character_count = apply_filters( 'widget_list_character_count', $instance['character_count'] );
		} else {
			$character_count = '';
		}

		if ( empty( $title ) || $title == 'All' ) {
			$title .= ' ' . __( get_post_type_plural_label( $post_type ), 'geodirectory' );
		}

		$location_allowed = GeoDir_Post_types::supports( $post_type, 'location' );

		$distance_to_post = $location_allowed && $list_sort == 'distance_asc' && ! empty( $gd_post->latitude ) && ! empty( $gd_post->longitude ) && geodir_is_page( 'detail' ) ? true : false;
		
		if ( $list_sort == 'distance_asc' && ! $distance_to_post ) {
			$list_sort = geodir_get_posts_default_sort( $post_type );
		}

		if ( $link_type == 'from' ) {
			$link_type_arg = 'linked_from_post';
		} else {
			$link_type_arg = 'linked_to_post';
		}

		$query_args = array(
			'posts_per_page' => $post_number,
			'is_geodir_loop' => true,
			'gd_location'    => false,
			'post_type'      => $post_type,
			'order_by'       => $list_sort,
			'distance_to_post' => $distance_to_post,
			$link_type_arg   => $post_id
		);

		if ( $character_count ) {
			$query_args['excerpt_length'] = $character_count;
		}

		global $gd_layout_class, $geodir_is_widget_listing;

		/*
		 * Filter widget listings query args.
		 */
		$query_args = apply_filters( 'geodir_widget_listings_query_args', $query_args, $instance );

		$widget_listings = geodir_get_widget_listings( $query_args );

		if ( empty( $widget_listings ) ) {
			return;
		}

		$gd_layout_class = geodir_convert_listing_view_class( $layout );
		
		// card border class
		$card_border_class = '';
		if ( ! empty( $instance['card_border'] ) ) {
			if ( $instance['card_border'] == 'none' ) {
				$card_border_class = 'border-0';
			} else {
				$card_border_class = 'border-' . sanitize_html_class( $instance['card_border'] );
			}
		}

		// card shadow
		$card_shadow_class = '';
		if ( ! empty( $instance['card_shadow'] ) ) {
			if ( $instance['card_shadow'] == 'small' ) {
				$card_shadow_class = 'shadow-sm';
			} elseif ( $instance['card_shadow'] == 'medium' ) {
				$card_shadow_class = 'shadow';
			} elseif ( $instance['card_shadow'] == 'large' ) {
				$card_shadow_class = 'shadow-lg';
			}
		}

		// Elementor
		$skin_active = false;
		$elementor_wrapper_class = '';
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) && $skin_id ) {
			if ( get_post_status( $skin_id ) == 'publish' ) {
				$skin_active = true;
			}

			if ( $skin_active ) {
				$columns = isset( $layout ) ? absint( $layout ) : 1;
				if ( $columns == '0' ) {
					$columns = 6; // We have no 6 row option to lets use list view.
				}
				$elementor_wrapper_class = ' elementor-element elementor-element-9ff57fdx elementor-posts--thumbnail-top elementor-grid-' . $columns . ' elementor-grid-tablet-2 elementor-grid-mobile-1 elementor-widget elementor-widget-posts ';
			}
		}

		if ( ! isset( $character_count ) ) {
			/**
			 * Filter the widget's excerpt character count.
			 *
			 * @since 1.0.0
			 *
			 * @param int $instance ['character_count'] Excerpt character count.
			 */
			$character_count = $character_count == '' ? 50 : apply_filters( 'widget_character_count', $character_count );
		}

		if ( isset( $post ) ) {
			$reset_post = $post;
		}
		if ( isset( $gd_post ) ) {
			$reset_gd_post = $gd_post;
		}
		$geodir_is_widget_listing = true;

		// Wrap class
		$class = $design_style ? geodir_build_aui_class( $instance ) : '';

		// Preview message
		$is_preview = $this->is_preview();
		if ( $is_preview && $design_style ) {
			echo aui()->alert( array(
					'type' => 'info',
					'content' => __( "This preview shows all content items to give an idea of layout. Dummy data is used in places.", "geodir_custom_posts" )
				)
			);
		}

		?>
		<div class="geodir_locations geodir_location_listing <?php echo $class . $elementor_wrapper_class; ?> position-relative">
			<?php

			if ( $skin_active ) {
				$column_gap = ! empty( $instance['skin_column_gap'] ) ? absint( $instance['skin_column_gap'] ) : '';
				$row_gap = ! empty( $instance['skin_row_gap'] ) ? absint( $instance['skin_row_gap'] ) : '';
				geodir_get_template( 'elementor/content-widget-listing.php', array( 'widget_listings' => $widget_listings, 'skin_id' => $skin_id, 'columns' => $columns, 'column_gap' => $column_gap, 'row_gap' => $row_gap ) );
			} else {
				$template = $design_style ? $design_style . "/content-widget-listing.php" : "content-widget-listing.php";

				echo geodir_get_template_html( 
					$template, 
					array(
						'widget_listings' => $widget_listings,
						'column_gap_class' => isset( $instance['column_gap'] ) && $instance['column_gap'] ? 'mb-'.absint( $instance['column_gap'] ) : 'mb-4',
						'row_gap_class' => isset( $instance['row_gap'] ) && $instance['row_gap'] ? 'px-'.absint( $instance['row_gap'] ) : '',
						'card_border_class' => $card_border_class,
						'card_shadow_class' => $card_shadow_class,
					) 
				);
			}

			if ( ! empty( $instance['view_all_link'] ) ) {
				$viewall_url = add_query_arg( array(
					'geodir_search' => 1,
					'stype' => $post_type,
					's' => '',
					$link_type_arg => $post_id
				), geodir_search_page_base_url() );

				/**
				 * Filter view all url.
				 *
				 * @since 2.0.0
				 *
				 * @param string $viewall_url View all url.
				 * @param array $query_args WP_Query args.
				 * @param array $instance Widget settings.
				 * @param array $args Widget arguments.
				 * @param object $this The GeoDir_CP_Widget_Post_Linked object.
				 */
				$viewall_url = apply_filters( 'geodir_cp_widget_gd_linked_posts_view_all_url', $viewall_url, $query_args, $instance, $args, $this );

				if ( $viewall_url ) {
					if ( $link_type_arg == 'linked_to_post' ) {
						$viewall_link_label = wp_sprintf( __( 'View %s\'s all %s', 'geodir_custom_posts' ), get_the_title( $post_id ), geodir_strtolower( geodir_post_type_name( $post_type, true ) ) );
					} else {
						$viewall_link_label = wp_sprintf( __( 'View all %s in %s', 'geodir_custom_posts' ), geodir_strtolower( geodir_post_type_name( $post_type, true ) ), get_the_title( $post_id ) );
					}
					$viewall_link_label = apply_filters( 'geodir_cp_' . $link_type_arg . '_view_all_link_label', $viewall_link_label, $query_args, $instance, $args, $this );

					$view_all_link = '<a href="' . esc_url( $viewall_url ) .'" class="geodir-all-link">' . $viewall_link_label . '</a>';

					/**
					 * Filter view all link content.
					 *
					 * @since 2.0.0
					 *
					 * @param string $view_all_link View all listings link content.
					 * @param string $viewall_url View all url.
					 * @param array $query_args WP_Query args.
					 * @param array $instance Widget settings.
					 * @param array $args Widget arguments.
					 * @param object $this The GeoDir_CP_Widget_Post_Linked object.
					 */
					$view_all_link = apply_filters( 'geodir_cp_widget_gd_linked_posts_view_all_link', $view_all_link, $viewall_url, $query_args, $instance, $args, $this );

					if ( $design_style ) {
						$view_all_link = str_replace( "geodir-all-link", "geodir-all-link btn btn-outline-primary", $view_all_link );
						echo '<div class="geodir-widget-bottom text-center">' . $view_all_link . '</div>';
					} else {
						echo '<div class="geodir-widget-bottom">' . $view_all_link . '</div>';
					}
				}
			}

			$geodir_is_widget_listing = false;

			if ( isset( $reset_post ) ) {
				if ( ! empty( $reset_post ) ) {
					setup_postdata( $reset_post );
				}
				$post = $reset_post;
			}
			if ( isset( $reset_gd_post ) ) {
				$gd_post = $reset_gd_post;
			}
			?>
		</div>
		<?php
	}

	/**
     * Get sort options.
     *
     * @since 2.0.0
     *
     * @param string $post_type Optional. Post type. Default gd_place.
     * @return array $options.
     */
    public function get_sort_options($post_type = 'gd_place' ) {
        $options = array(
            "az" => __( 'A-Z', 'geodirectory' ),
            "latest" => __( 'Latest', 'geodirectory' ),
            "high_review" => __( 'Most reviews', 'geodirectory' ),
            "high_rating" => __( 'Highest rating', 'geodirectory' ),
            "random" => __( 'Random', 'geodirectory' ),
			"distance_asc" => __( 'Distance to current post (details page only)', 'geodirectory' ),
        );

        $sort_options = geodir_get_sort_options( $post_type );
        if (!empty($sort_options)){
            foreach($sort_options as $sort_option){
                if (!empty($sort_option->sort_asc) && !empty($sort_option->asc_title)){
                    $options[$sort_option->htmlvar_name."_asc"] = __( $sort_option->asc_title, 'geodirectory' );
                }
                if (!empty($sort_option->sort_desc) && !empty($sort_option->desc_title)){
                    $options[$sort_option->htmlvar_name."_desc"] = __( $sort_option->desc_title, 'geodirectory' );
                }
            }
        }

        return $options;
    }
}

