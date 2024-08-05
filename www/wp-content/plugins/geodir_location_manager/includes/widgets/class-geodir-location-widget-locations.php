<?php

/**
 * GeoDir_Location_Widget_Locations class.
 *
 * @since 2.0.0
 */
class GeoDir_Location_Widget_Locations extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => 'geodirlocation',
			'block-icon'     => 'location-alt',
			'block-category' => 'geodirectory',
			'block-keywords' => "['geodirlocation','location','locations']",
			'block-supports'   => array(
				'customClassName' => false,
			),
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_locations',
			'name'           => __( 'GD > Locations', 'geodirlocation' ),
			'widget_ops'     => array(
				'classname'     => 'geodir-lm-locations ' . geodir_bsui_class(),
				'description'   => esc_html__( 'Displays the locations.', 'geodirlocation' ),
				'gd_wgt_restrict' => '',
                'geodirectory' => true,
			),
			'block_group_tabs' => array(
				'content'  => array(
					'groups' => array(
						__( 'Title', 'geodirlocation' ),
						__( 'Filters', 'geodirlocation' ),
					),
					'tab'    => array(
						'title'     => __( 'Content', 'geodirlocation' ),
						'key'       => 'bs_tab_content',
						'tabs_open' => true,
						'open'      => true,
						'class'     => 'text-center flex-fill d-flex justify-content-center',
					),
				),
				'styles'   => array(
					'groups' => array(
						__( 'Design type', 'geodirlocation' ),
						__( 'Grid design', 'geodirlocation' ),
						__( 'List design', 'geodirlocation' ),
						__( 'Paging', 'geodirlocation' ),
					),
					'tab'    => array(
						'title'     => __( 'Styles', 'geodirlocation' ),
						'key'       => 'bs_tab_styles',
						'tabs_open' => true,
						'open'      => true,
						'class'     => 'text-center flex-fill d-flex justify-content-center',
					),
				),
				'advanced' => array(
					'groups' => array(
						__( 'Wrapper Styles', 'geodirlocation' ),
						__( 'Advanced', 'geodirlocation' ),
					),
					'tab'    => array(
						'title'     => __( 'Advanced', 'geodirlocation' ),
						'key'       => 'bs_tab_advanced',
						'tabs_open' => true,
						'open'      => true,
						'class'     => 'text-center flex-fill d-flex justify-content-center',
					),
				),
			),
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 *
	 */
	public function set_arguments() {
		$design_style = geodir_design_style();

		$arguments = array();



		$arguments = array(
			'title'  => array(
				'title' => __('Title:', 'geodirlocation'),
				'desc' => __('The widget title.', 'geodirlocation'),
				'type' => 'text',
				'default'  => '',
				'desc_tip' => true,
				'advanced' => false,
				'group'    => __( 'Title', 'geodirlocation' ),
			)
		);

		// title styles
		$title_args = geodir_get_sd_title_inputs();
		$arguments  = $arguments + $title_args;

        $arguments = $arguments + array(
			'what' => array(
				'type' => 'select',
				'title' => __( 'Show Locations:', 'geodirlocation' ),
				'desc' => __( 'Select which locations to show in a list. Default: Cities', 'geodirlocation' ),
				'placeholder' => '',
				'default' => 'city',
				'options' =>  array(
					"city" => __( 'Cities', 'geodirlocation' ),
					"region" => __( 'Regions', 'geodirlocation' ),
					"country" => __( 'Countries', 'geodirlocation' ),
					"neighbourhood" => __( 'Neighbourhoods', 'geodirlocation' ),
				),
				'desc_tip' => true,
				'advanced' => false,
				'group'    => __( 'Filters', 'geodirlocation' ),
			),
			'slugs'  => array(
				'type' => 'text',
				'title' => __( 'Location slugs:', 'geodirlocation' ),
				'desc' => __( 'To show specific locations, enter comma separated location slugs for the option selected in "Show Locations". Ex: new-york,london', 'geodirlocation' ),
				'placeholder' => '',
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group'    => __( 'Filters', 'geodirlocation' ),
			),
			'no_loc'  => array(
				'title' => __("Disable location filter?", 'geodirlocation'),
				'desc' => __("Don't filter results for current location.", 'geodirlocation'),
				'type' => 'checkbox',
				'desc_tip' => true,
				'value'  => '1',
				'default'  => '0',
				'advanced' => false,
				'element_require' => '![%slugs%]',
				'group'    => __( 'Filters', 'geodirlocation' ),
			),
			'show_current' => array(
				'title' => __( 'Show current location only', 'geodirlocation' ),
				'desc' => __( 'Tick to show only current country / region / city / neighbourhood when location filter is active & country / region / city / neighbourhood is set.', 'geodirlocation' ),
				'type' => 'checkbox',
				'desc_tip' => true,
				'value' => '1',
				'default' => '0',
				'advanced' => false,
				'element_require' => '( ! ( ( typeof form != "undefined" && jQuery( form ).find( "[data-argument=no_loc]" ).find( "input[type=checkbox]" ).is( ":checked" ) ) || ( typeof props == "object" && props.attributes && props.attributes.no_loc ) ) ) && ![%slugs%]',
				'group'    => __( 'Filters', 'geodirlocation' ),
			),
			'country' => array(
				'type' => 'text',
				'title' => __( 'Country slug', 'geodirlocation' ),
				'desc' => __( 'Filter the locations by country slug when location filter enabled. Default: current country.', 'geodirlocation' ),
				'placeholder' => '',
				'desc_tip' => true,
				'value' => '',
				'default' => '',
				'advanced' => true,
				'element_require' => '[%what%]!="country" && ![%slugs%]',
				'group'    => __( 'Filters', 'geodirlocation' ),
			),
			'region' => array(
				'type' => 'text',
				'title' => __( 'Region slug', 'geodirlocation' ),
				'desc' => __( 'Filter the locations by region slug when location filter enabled. Default: current region.', 'geodirlocation' ),
				'placeholder' => '',
				'desc_tip' => true,
				'value' => '',
				'default' => '',
				'advanced' => true,
				'element_require' => '( [%what%]=="city" || [%what%]=="neighbourhood" ) && ![%slugs%]',
				'group'    => __( 'Filters', 'geodirlocation' ),
			),
			'city' => array(
				'type' => 'text',
				'title' => __( 'City slug', 'geodirlocation' ),
				'desc' => __( 'Filter the locations by city slug when location filter enabled. Default: current city.', 'geodirlocation' ),
				'placeholder' => '',
				'desc_tip' => true,
				'value' => '',
				'default' => '',
				'advanced' => true,
				'element_require' => '[%what%]=="neighbourhood" && ![%slugs%]',
				'group'    => __( 'Filters', 'geodirlocation' ),
			),
			'output_type'  => array(
				'type' => 'select',
				'title' => __('Output type', 'geodirlocation'),
				'desc' => __('This determines the style of the output list.', 'geodirlocation'),
				'placeholder' => '',
				'default' => '',
				'options' =>  array(
					"" => __('List', 'geodirlocation'),
					"grid" => __('Image Grid', 'geodirlocation'),
				),
				'desc_tip' => true,
				'advanced' => false,
				'group'    => __( 'Design type', 'geodirlocation' ),
			),
			'per_page'  => array(
				'type' => 'number',
				'title' => __('Number of locations:', 'geodirlocation'),
				'desc' => __('Number of locations to be shown on each page. Use 0(zero) or ""(blank) to show all locations.', 'geodirlocation'),
				'placeholder' => '',
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '![%slugs%]',
				'group'    => __( 'Design type', 'geodirlocation' ),
			),
			'fallback_image' => array(
				'type' => 'checkbox',
				'title' => __( "Show post image as a fallback?", 'geodirlocation' ),
				'desc' => __( "If location image not available then show last post image added under this location.", 'geodirlocation' ),
				'desc_tip' => true,
				'value'  => '1',
				'default'  => '0',
				'advanced' => true,
				'element_require' => '[%output_type%]=="grid"',
				'group'    => __( 'Grid design', 'geodirlocation' ),
			),


		);

		if ( $design_style ) {

			$arguments['grid_per_row'] = array(
				'title' => __('Items per row', 'geodirlocation'),
				'type' => 'select',
				'options'   =>  array(
					"" => __('Default (3)', 'geodirlocation'),
					"1" => '1',
					"2" => '2',
					"3" => '3',
					"4" => '4',
					"5" => '5',
					"6" => '6',
					"7" => '7',
					"8" => '8',
				),
				'default'  => '3',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '[%output_type%]=="grid"',
				'group'     => __("Grid design","geodirlocation")
			);

			$arguments['grid_item_aspect'] = array(
				'type'     => 'select',
				'title'    => __( 'Item aspect', 'geodirlocation' ),
				'options'  => array(
					'4by3'  => '4x3',
					'16by9' => '16x9',
					'21by9' => '21x9',
					'1by1'  => '1x1',
				),
				'default'  => '4by3',
				'desc_tip' => true,
				'element_require' => '[%output_type%]=="grid"',
				'group'     => __("Grid design","geodirlocation" )
			);

			// text color
			//$arguments = $arguments + sd_get_text_color_input_group();

			// font size
//			$arguments = $arguments + sd_get_font_size_input_group();
			$arguments['grid_font_size']  = sd_get_font_size_input( 'font_size', array('group' => __("Grid design","geodirlocation" ), 'default' => 'h5' ) );

			// font case
			$arguments['grid_font_case'] = sd_get_font_case_input(
				'desc_font_case',
				array(
					'group' => __("Grid design","geodirlocation" ),
                    'default' => 'text-uppercase'
				)
			);

			// font size
			$arguments['grid_font_weight'] = sd_get_font_weight_input('grid_font_weight', array('group' => __("Grid design","geodirlocation" ), 'default' => 'font-weight-bold' ) );

			// text align
			$arguments['grid_text_align']    = sd_get_text_align_input(
				'text_align',
				array(
					'device_type'     => 'Mobile',
					'group' => __("Grid design","geodirlocation" )
				)
			);
			$arguments['grid_text_align_md'] = sd_get_text_align_input(
				'text_align',
				array(
					'device_type'     => 'Tablet',
					'group' => __("Grid design","geodirlocation" )
				)
			);
			$arguments['grid_text_align_lg'] = sd_get_text_align_input(
				'text_align',
				array(
					'device_type'     => 'Desktop',
					'group' => __("Grid design","geodirlocation" )
				)
			);

            // list design
			$arguments['type'] = array(
				'title' => __('Type', 'geodirectory'),
				'desc' => __('Select the badge type.', 'geodirectory'),
				'type' => 'select',
				'options'   =>  array(
					"" => __('Badge', 'geodirectory'),
					"pill" => __('Pill', 'geodirectory'),
					"link" => __('Button Link', 'geodirectory'),
					"button" => __('Button', 'geodirectory'),
				),
				'default'  => '',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '[%output_type%]!="grid"',
				'group'     => __("List design","geodirectory")
			);

			$arguments['icon_class']  = array(
				'type' => 'text',
				'title' => __('Icon class:', 'geodirectory'),
				'desc' => __('You can show a font-awesome icon here by entering the icon class.', 'geodirectory'),
				'placeholder' => 'fas fa-caret-right',
				'default' => '',
				'desc_tip' => true,
				'element_require' => '[%output_type%]!="grid"',
				'group'     => __("List design","geodirectory")
			);

			$arguments['shadow'] = array(
				'title' => __('Shadow', 'geodirectory'),
				'desc' => __('Select the shadow badge type.', 'geodirectory'),
				'type' => 'select',
				'options'   =>  array(
					"" => __('None', 'geodirectory'),
					"small" => __('small', 'geodirectory'),
					"medium" => __('medium', 'geodirectory'),
					"large" => __('large', 'geodirectory'),
				),
				'default'  => '',
				'desc_tip' => true,
				'advanced' => false,
				'group'     => __("List design","geodirectory")
			);

			$arguments['color'] = array(
				'title' => __('Color', 'geodirectory'),
				'desc' => __('Select the the color.', 'geodirectory'),
				'type' => 'select',
				'options'   =>  array(
					                "" => __('Custom colors', 'geodirectory'),
				                )+geodir_aui_colors(true, true, true),
				'default'  => '',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '[%output_type%]!="grid"',
				'group'     => __("List design","geodirectory")
			);


			$arguments['bg_color']  = array(
				'type' => 'color',
				'title' => __('Background color:', 'geodirectory'),
				'desc' => __('Color for the background.', 'geodirectory'),
				'placeholder' => '',
				'default' => '#0073aa',
				'desc_tip' => true,
				'group'     => __("List design","geodirectory"),
				'element_require' => $design_style ?  '( [%color%]=="" && [%output_type%]!="grid" )' : '',
			);
			$arguments['txt_color']  = array(
				'type' => 'color',
//			'disable_alpha'=> true,
				'title' => __('Text color:', 'geodirectory'),
				'desc' => __('Color for the text.', 'geodirectory'),
				'placeholder' => '',
				'desc_tip' => true,
				'default'  => '#ffffff',
				'group'     => __("List design","geodirectory"),
				'element_require' => $design_style ?  '( [%color%]=="" && [%output_type%]!="grid" )' : '',
			);
			$arguments['size']  = array(
				'type' => 'select',
				'title' => __('Size', 'geodirectory'),
				'desc' => __('Size of the item.', 'geodirectory'),
				'options' =>  array(
					"" => __('Default', 'geodirectory'),
					"h6" => __('XS (badge)', 'geodirectory'),
					"h5" => __('S (badge)', 'geodirectory'),
					"h4" => __('M (badge)', 'geodirectory'),
					"h3" => __('L (badge)', 'geodirectory'),
					"h2" => __('XL (badge)', 'geodirectory'),
					"h1" => __('XXL (badge)', 'geodirectory'),
					"btn-lg" => __('L (button)', 'geodirectory'),
					"btn-sm" => __('S (button)', 'geodirectory'),
				),
				'default' => '',
				'desc_tip' => true,
				'element_require' => '[%output_type%]!="grid"',
				'group'     => __("List design","geodirectory"),
			);

			$arguments['pagi_t']  = array(
				'title' => __("Show pagination on top?", 'geodirlocation'),
				'type' => 'checkbox',
				'desc_tip' => false,
				'value'  => '1',
				'default'  => '0',
				'advanced' => true,
				'element_require' => '![%slugs%]',
				'group'    => __( 'Paging', 'geodirlocation' ),
			);
			$arguments['pagi_b']  = array(
				'title' => __("Show pagination at bottom?", 'geodirlocation'),
				'type' => 'checkbox',
				'desc_tip' => false,
				'value'  => '1',
				'default'  => '0',
				'advanced' => true,
				'element_require' => '![%slugs%]',
				'group'    => __( 'Paging', 'geodirlocation' ),
			);
			$arguments['pagi_info']  = array(
				'type' => 'select',
				'title' => __('Show advanced pagination details:', 'geodirlocation'),
				'desc' => __('This will add extra pagination info like "Showing locations x-y of z" after/before pagination.', 'geodirlocation'),
				'placeholder' => '',
				'default' => '',
				'options' =>  array(
					"" => __('Never Display', 'geodirlocation'),
					"after" => __('After pagination', 'geodirlocation'),
					"before" => __('Before pagination', 'geodirlocation')
				),
				'desc_tip' => true,
				'advanced' => true,
				'element_require' => '![%slugs%]',
				'group'    => __( 'Paging', 'geodirlocation' ),
			);

			$arguments['mt']  = geodir_get_sd_margin_input('mt');
			$arguments['mr']  = geodir_get_sd_margin_input('mr');
			$arguments['mb']  = geodir_get_sd_margin_input('mb');
			$arguments['ml']  = geodir_get_sd_margin_input('ml');

			$arguments['css_class'] = sd_get_class_input();
		}

		return $arguments;
	}

	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $aui_bs5;

		extract( $widget_args, EXTR_SKIP );

		$params = $args;

		/**
		 * Filter the widget title.
		 *
		 * @since 1.0.0
		 *
		 * @param string $title The widget title. Default empty.
		 * @param array  $args An array of the widget's settings.
		 * @param mixed  $id_base The widget ID.
		 */
		$title = apply_filters('geodir_popular_location_widget_title', !empty($args['title']) ? $args['title'] : '', $args, $this->id_base);
		
		/**
		 * Filter the no. of locations to shows on each page.
		 *
		 * @since 1.5.0
		 *
		 * @param int   $per_page No. of locations to be displayed.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['per_page'] = apply_filters('geodir_popular_location_widget_per_page', !empty($args['per_page']) ? absint($args['per_page']) : '', $args, $this->id_base);
		
		/**
		 * Whether to show pagination on top of widget content.
		 *
		 * @since 1.5.0
		 *
		 * @param bool  $pagi_t If true then pagination displayed on top. Default false.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['pagi_t'] = apply_filters('geodir_popular_location_widget_pagi_top', !empty($args['pagi_t']) ? true : false, $args, $this->id_base);
		
		/**
		 * Whether to show pagination on bottom of widget content.
		 *
		 * @since 1.5.0
		 *
		 * @param bool  $pagi_b If true then pagination displayed on bottom. Default false.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['pagi_b'] = apply_filters('geodir_popular_location_widget_pagi_bottom', !empty($args['pagi_b']) ? true : false, $args, $this->id_base);
		
		/**
		 * Filter the position to display advanced pagination info.
		 *
		 * @since 1.5.0
		 *
		 * @param string  $pagi_info Position to display advanced pagination info.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['pagi_info'] = apply_filters('geodir_popular_location_widget_pagi_info', !empty($args['pagi_info']) ? $args['pagi_info'] : '', $args, $this->id_base);
		
		/**
		 * Whether to disable filter results for current location.
		 *
		 * @since 1.5.0
		 *
		 * @param bool  $no_loc If true then results not filtered for current location. Default false.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['no_loc'] = apply_filters('geodir_popular_location_widget_no_location_filter', !empty($args['no_loc']) ? true : false, $args, $this->id_base);

		/**
		 * Whether to show current country / region / city / neighbourhood only.
		 *
		 * @since 2.0.0.24
		 *
		 * @param bool  $show_current If true then it will show only current location. Default false.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['show_current'] = apply_filters( 'geodir_popular_location_widget_show_current_filter', ! empty( $args['show_current'] ) ? true : false, $args, $this->id_base );

		/**
		 * Whether to disable filter results for current location.
		 *
		 * @since 1.5.0
		 *
		 * @param bool  $output_type If true then results not filtered for current location. Default false.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['output_type'] = apply_filters('geodir_popular_location_widget_output_type_filter', !empty($args['output_type']) ? $args['output_type'] : 'list', $args, $this->id_base);

		/**
		 * Whether to show post image as a fallback image.
		 *
		 * @since 2.0.0.25
		 *
		 * @param bool  $fallback_image If true then show post image when location image not available. Default false.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['fallback_image'] = apply_filters( 'geodir_popular_location_widget_fallback_image_filter', ( ! empty( $args['fallback_image'] ) ? true : false ), $args, $this->id_base );
		
		$what = ! empty( $args['what'] ) && in_array( $args['what'], array( 'country', 'region', 'city', 'neighbourhood' ) ) ? $args['what'] : 'city';
		/**
		 * Filter which location to show in a list.
		 *
		 * @since 2.0.0.22
		 *
		 * @param string $what The locations to show. Default city.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['what'] = apply_filters( 'geodir_popular_location_widget_what_filter', $what, $args, $this->id_base );

		/**
		 * Filter location slugs.
		 *
		 * @since 2.1.0.4
		 *
		 * @param string $slugs Comma separated location slugs.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['slugs'] = apply_filters( 'geodir_popular_location_widget_slugs_filter', ( isset( $args['slugs'] ) ? trim( $args['slugs'] ) : '' ), $args, $this->id_base );

		/**
		 * Filter the locations by country.
		 *
		 * @since 2.0.0.22
		 *
		 * @param string $country The country.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['country'] = apply_filters( 'geodir_popular_location_widget_country_filter', ( ! empty( $args['country'] ) ? $args['country'] : '' ), $args, $this->id_base );

		/**
		 * Filter the locations by region.
		 *
		 * @since 2.0.0.22
		 *
		 * @param string $region The region.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['region'] = apply_filters( 'geodir_popular_location_widget_region_filter', ( ! empty( $args['region'] ) ? $args['region'] : '' ), $args, $this->id_base );

		/**
		 * Filter the locations by city.
		 *
		 * @since 2.0.0.22
		 *
		 * @param string $city The city.
		 * @param array $args An array of the widget's settings.
		 * @param mixed $id_base The widget ID.
		 */
		$params['city'] = apply_filters( 'geodir_popular_location_widget_city_filter', ( ! empty( $args['city'] ) ? $args['city'] : '' ), $args, $this->id_base );

		$design_style = geodir_design_style();

		if ( $design_style ) {
			$params['css_class'] = '';
			$params['css_class'] .= !empty( $params['button_class'] ) ? $params['button_class'] : '';
			// margins
			if ( ! empty( $params['mt'] ) ) { $params['css_class'] .= " mt-" . sanitize_html_class( $params['mt'] ) . " "; }
			if ( ! empty( $params['mr'] ) ) { $params['css_class'] .= ( $aui_bs5 ? " me-" : " mr-" ) . sanitize_html_class( $params['mr'] ) . " "; }
			if ( ! empty( $params['mb'] ) ) { $params['css_class'] .= " mb-" . sanitize_html_class( $params['mb'] ) . " "; }
			if ( ! empty( $params['ml'] ) ) { $params['css_class'] .= ( $aui_bs5 ? " ms-" : " ml-" ) . sanitize_html_class( $params['ml'] ) . " "; }

			if(!empty($params['size'])){
				switch ($params['size']) {
					case 'h6': $params['size'] = 'h6';break;
					case 'h5': $params['size'] = 'h5';break;
					case 'h4': $params['size'] = 'h4';break;
					case 'h3': $params['size'] = 'h3';break;
					case 'h2': $params['size'] = 'h2';break;
					case 'h1': $params['size'] = 'h1';break;
					case 'btn-lg': $params['size'] = ''; $params['css_class'] = 'btn-lg';break;
					case 'btn-sm':$params['size'] = '';  $params['css_class'] = 'btn-sm';break;
					default:
						$params['size'] = '';
				}
			}
		}

		$params['widget_atts'] = $params;

		ob_start();
		?>
		<div class="geodir-category-list-in clearfix geodir-location-lity-type-<?php echo esc_attr( $params['output_type'] ); ?>">
		    <?php geodir_popular_location_widget_output( $params ); ?>
		</div>
		<?php
		$output = ob_get_clean();

		return $output;
	}	
}

