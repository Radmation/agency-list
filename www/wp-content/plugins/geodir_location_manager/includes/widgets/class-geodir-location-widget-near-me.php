<?php

/**
 * GeoDir_Location_Widget_Near_Me class.
 *
 * @since 2.0.0
 */
class GeoDir_Location_Widget_Near_Me extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => 'geodirlocation',
			'block-icon'     => 'location-alt',
			'block-category' => 'geodirectory',
			'block-keywords' => "['geodirlocation','location','near me']",
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_location_near_me',
			'name'           => __( 'GD > Near Me Button', 'geodirlocation' ),
			'widget_ops'     => array(
				'classname'     => 'geodir-lm-popular-locations ' . geodir_bsui_class(),
				'description'   => esc_html__( 'Displays near me button to share geo position.', 'geodirlocation' ),
				'geodirectory'  => true,
				'gd_show_pages' => array(),
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
			'title'  => array(
                'title' => __('Title:', 'geodirlocation'),
                'desc' => __('The widget title.', 'geodirlocation'),
                'type' => 'text',
                'default'  => '',
                'desc_tip' => true,
                'advanced' => false
            ),
			'button_title'  => array(
                'title' => __('Button title:', 'geodirlocation'),
                'desc' => __('Near me button title.', 'geodirlocation'),
                'type' => 'text',
                'default'  => '',
				'placeholder' => __( 'Near Me', 'geodirlocation' ),
                'desc_tip' => true,
                'advanced' => false
            ),
			'button_class'  => array(
                'title' => __('Button css class:', 'geodirlocation'),
                'desc' => __('Near me button css class.', 'geodirlocation'),
                'type' => 'text',
                'default'  => '',
                'desc_tip' => true,
                'advanced' => true
            ),
		);

		if($design_style) {
		$arguments['icon_class']  = array(
			'type' => 'text',
			'title' => __('Icon class:', 'geodirectory'),
			'desc' => __('You can show a font-awesome icon here by entering the icon class.', 'geodirectory'),
			'placeholder' => 'fas fa-award',
			'default' => '',
			'desc_tip' => true,
			'group'     => __("Design","geodirectory")
		);
//		$arguments['badge']  = array(
//			'type' => 'text',
//			'title' => __('Badge:', 'geodirectory'),
//			'desc' => __('Badge text. Ex: FOR SALE. Leave blank to show field title as a badge, or use %%input%% to use the input value of the field or %%post_url%% for the post url, or the field key for any other info %%email%%.', 'geodirectory'),
//			'placeholder' => '',
//			'default' => '',
//			'desc_tip' => true,
//			'advanced' => false,
//		);
		$arguments['tooltip_text']  = array(
			'type' => 'text',
			'title' => __('Tooltip text:', 'geodirectory'),
			'desc' => __('Reveals some text on hover. Enter some text or use %%input%% to use the input value of the field or the field key for any other info %%email%%. (this can NOT be used with popover text)', 'geodirectory'),
			'placeholder' => '',
			'default' => '',
			'desc_tip' => true,
			'group'     => __("Hover Action","geodirectory")
		);
		$arguments['hover_content']  = array(
			'type' => 'text',
			'title' => __('Hover content:', 'geodirectory'),
			'desc' => __('Change the button text on hover. Enter some text or use %%input%% to use the input value of the field or the field key for any other info %%email%%.', 'geodirectory'),
			'placeholder' => '',
			'default' => '',
			'desc_tip' => true,
			'group'     => __("Hover Action","geodirectory")
		);
		$arguments['hover_icon']  = array(
			'type' => 'text',
			'title' => __('Hover icon:', 'geodirectory'),
			'desc' => __('Change the button icon on hover. You can show a font-awesome icon here by entering the icon class.', 'geodirectory'),
			'placeholder' => 'fas fa-bacon',
			'default' => '',
			'desc_tip' => true,
			'group'     => __("Hover Action","geodirectory")
		);

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
			'group'     => __("Design","geodirectory")
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
			'group'     => __("Design","geodirectory")
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
			'group'     => __("Design","geodirectory")
		);


		$arguments['bg_color']  = array(
			'type' => 'color',
			'title' => __('Background color:', 'geodirectory'),
			'desc' => __('Color for the background.', 'geodirectory'),
			'placeholder' => '',
			'default' => '#0073aa',
			'desc_tip' => true,
			'group'     => __("Design","geodirectory"),
			'element_require' => $design_style ?  '[%color%]==""' : '',
		);
		$arguments['txt_color']  = array(
			'type' => 'color',
//			'disable_alpha'=> true,
			'title' => __('Text color:', 'geodirectory'),
			'desc' => __('Color for the text.', 'geodirectory'),
			'placeholder' => '',
			'desc_tip' => true,
			'default'  => '#ffffff',
			'group'     => __("Design","geodirectory"),
			'element_require' => $design_style ?  '[%color%]==""' : '',
		);
		$arguments['size']  = array(
			'type' => 'select',
			'title' => __('Badge size', 'geodirectory'),
			'desc' => __('Size of the badge.', 'geodirectory'),
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
			'group'     => __("Design","geodirectory"),
		);

		$arguments['alignment']  = array(
			'type' => 'select',
			'title' => __('Alignment:', 'geodirectory'),
			'desc' => __('How the item should be positioned on the page.', 'geodirectory'),
			'options'   =>  array(
				"" => __('None', 'geodirectory'),
				"left" => __('Left', 'geodirectory'),
				"center" => __('Center', 'geodirectory'),
				"right" => __('Right', 'geodirectory'),
			),
			'desc_tip' => true,
			'group'     => __("Positioning","geodirectory")
		);

			$arguments['mt']  = geodir_get_sd_margin_input('mt');
			$arguments['mr']  = geodir_get_sd_margin_input('mr');
			$arguments['mb']  = geodir_get_sd_margin_input('mb');
			$arguments['ml']  = geodir_get_sd_margin_input('ml');
		}

		return $arguments;
	}

	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		$design_style = geodir_design_style();
		$output = '';

		if ( $design_style ) {
			$args['css_class'] = '';
			$args['css_class'] .= "geodir-location-near-me c-pointer";
			$args['css_class'] .= empty( $args['button_class'] ) ? $args['button_class'] : '';
			$args['badge'] = ! empty( $args['button_title'] ) ? stripslashes( trim( strip_tags( $args['button_title'] ) ) ) : __( 'Near Me', 'geodirlocation' );

			// margins
			if ( ! empty( $args['mt'] ) ) { $args['css_class'] .= " mt-" . sanitize_html_class( $args['mt'] ) . " "; }
			if ( ! empty( $args['mr'] ) ) { $args['css_class'] .= ( $aui_bs5 ? " me-" : " mr-" ) . sanitize_html_class( $args['mr'] ) . " "; }
			if ( ! empty( $args['mb'] ) ) { $args['css_class'] .= " mb-" . sanitize_html_class( $args['mb'] ) . " "; }
			if ( ! empty( $args['ml'] ) ) { $args['css_class'] .= ( $aui_bs5 ? " ms-" : " ml-" ) . sanitize_html_class( $args['ml'] ) . " "; }

			if ( ! empty( $args['size'] ) ) {
				switch ( $args['size'] ) {
					case 'small':
						$args['size'] = $design_style ? '' : 'small';
						break;
					case 'medium':
						$args['size'] = $design_style ? 'h4' : 'medium';
						break;
					case 'large':
						$args['size'] = $design_style ? 'h2' : 'large';
						break;
					case 'extra-large':
						$args['size'] = $design_style ? 'h1' : 'extra-large';
						break;
					case 'h6': $args['size'] = 'h6';break;
					case 'h5': $args['size'] = 'h5';break;
					case 'h4': $args['size'] = 'h4';break;
					case 'h3': $args['size'] = 'h3';break;
					case 'h2': $args['size'] = 'h2';break;
					case 'h1': $args['size'] = 'h1';break;
					case 'btn-lg': $args['size'] = ''; $args['css_class'] = 'btn-lg';break;
					case 'btn-sm':$args['size'] = '';  $args['css_class'] = 'btn-sm';break;
					default:
						$args['size'] = '';
				}
			}

			$args['onclick'] = "gd_get_user_position(gdlm_ls_near_me);";
			$args['link'] = "#near-me";

			if ( ! empty( $args['hover_content'] ) ) {
				$args['hover_content'] = geodir_sanitize_html_field( $args['hover_content'] );
			}

			$output .= geodir_get_post_badge( 0, $args );
		} else {
			extract( $widget_args, EXTR_SKIP );

			$args['button_title'] = stripslashes( trim( esc_html( $args['button_title'] ) ) );

			$button_title = empty( $args['button_title'] ) ? __( 'Near Me', 'geodirlocation' ) : apply_filters( 'geodir_location_widget_near_me_button_title', __( $args['button_title'], 'geodirlocation' ), $args, $this->id_base );
			$button_class = empty( $args['button_class'] ) ? '' : apply_filters( 'geodir_location_widget_near_me_button_class', $args['button_class'], $args, $this->id_base );

			$output .= '<button type="button" class="geodir-location-near-me ' . esc_attr( $button_class ) . '" onclick="gd_get_user_position(gdlm_ls_near_me);">' . $button_title . '</button>';
		}

		return $output;
	}
}
