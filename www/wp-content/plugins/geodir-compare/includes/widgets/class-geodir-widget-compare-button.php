<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GeoDir_Widget_List_Compare
 *
 * @since 1.0.0
 */
class GeoDir_Widget_Compare_Button extends WP_Super_Duper {

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
			'block-keywords'        => "['compare', 'listings', 'geodirectory']",
			'class_name'            => __CLASS__,
			'base_id'               => 'gd_compare_button',
			'name'                  => __( 'GD > Compare Button', 'geodir-compare' ),
			'widget_ops'            => array(
				'classname'         => 'geodir-listing-compare-container' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'       => esc_html__( 'Allows the user to compare two or more listings.', 'geodir-compare' ),
				'geodirectory'      => true,
				'gd_wgt_showhide'   => 'show_on',
				'gd_wgt_restrict'   => array( 'gd-detail' ),
			),
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function set_arguments() {

		$design_style = geodir_design_style();

		$arguments                  = array(
			'badge'                 => array(
				'type'              => 'text',
				'title'             => __( 'Button Text', 'geodir-compare' ),
				'desc'              => __( 'The text used by the compare listing button.', 'geodir-compare' ),
				'placeholder'       => __( 'Compare', 'geodir-compare' ),
				'default'           => '',
				'desc_tip'          => true,
				'advanced'          => true
			),
			'icon_class'            => array(
				'type'              => 'text',
				'title'             => __( 'Button Icon', 'geodir-compare' ),
				'desc'              => __( 'Enter a FontAwesome icon class here and it will be displayed in the button.', 'geodir-compare' ),
				'placeholder'       => 'far fa-square',
				'default'           => '',
				'desc_tip'          => true,
				'advanced'          => true
			),
			'badge_after'           => array(
				'type'              => 'text',
				'title'             => __( 'Added To Compare Button Text', 'geodir-compare' ),
				'desc'              => __( 'The text used by the compare listing button when the listing has already been added to the comparison list.', 'geodir-compare' ),
				'placeholder'       => __( 'Added To Compare', 'geodir-compare' ),
				'default'           => '',
				'desc_tip'          => true,
				'advanced'          => true
			),
			'open_in'               => array(
				'type'              => 'select',
				'title'             => __( 'Open Compare List In:', 'geodir-compare' ),
				'desc'              => __( 'Open the compare list after click on button after item added to compare list.', 'geodir-compare' ),
				'options'           =>  array(
					""              => __( 'Popup', 'geodir-compare' ),
					"tab"           => __( 'Same Window Tab', 'geodir-compare' ),
					"window"        => __( 'New Window Tab', 'geodir-compare' )
				),
				'default'           => '',
				'desc_tip'          => true,
				'advanced'          => true
			),
			'icon_class_after'      => array(
				'type'              => 'text',
				'title'             => __( 'Added To Compare Button Icon', 'geodir-compare' ),
				'desc'              => __( 'Enter a FontAwesome icon class here and it will be displayed in the button after a user has added the listing to a comparison list.', 'geodir-compare' ),
				'placeholder'       => 'far fa-check-square',
				'default'           => '',
				'desc_tip'          => true,
				'advanced'          => true
			),
			'bg_color'              => array(
				'type'              => 'color',
				'title'             => __( 'Button Background Color:', 'geodir-compare' ),
				'desc'              => __( 'What color should be used as the button background?.', 'geodir-compare' ),
				'placeholder'       => '',
				'default'           => '#0073aa',
				'desc_tip'          => true,
				'advanced'          => true
			),
			'txt_color'             => array(
				'type'              => 'color',
				'title'             => __( 'Button Text Color:', 'geodir-compare' ),
				'desc'              => __( 'Color for the button text.', 'geodir-compare' ),
				'placeholder'       => '',
				'desc_tip'          => true,
				'default'           => '#ffffff',
				'advanced'          => true
			),
			'size'                  => array(
				'type'              => 'select',
				'title'             => __( 'Button size:', 'geodir-compare' ),
				'desc'              => __( 'Size of the button.', 'geodir-compare' ),
				'options'           =>  array(
					"small"         => __( 'Small', 'geodir-compare' ),
					""              => __( 'Normal', 'geodir-compare' ),
					"medium"        => __( 'Medium', 'geodir-compare' ),
					"large"         => __( 'Large', 'geodir-compare' ),
					"extra-large"   => __( 'Extra Large', 'geodir-compare' ),
				),
				'default'           => '',
				'desc_tip'          => true,
				'advanced'          => true
			),
			'alignment'             => array(
				'type'              => 'select',
				'title'             => __( 'Alignment:', 'geodir-compare' ),
				'desc'              => __( 'How the item should be positioned on the page.', 'geodir-compare' ),
				'options'           =>  array(
					""              => __( 'None', 'geodir-compare' ),
					"left"          => __( 'Left', 'geodir-compare' ),
					"center"        => __( 'Center', 'geodir-compare' ),
					"right"         => __( 'Right', 'geodir-compare' ),
				),
				'default'           => '',
				'desc_tip'          => true,
				'advanced'          => false,
				'group'             => __( 'Positioning', 'geodirectory' )
			),
		);

		if ( $design_style ) {
			$arguments['tooltip_text'] = array(
				'type' => 'text',
				'title' => __( 'Tooltip text', 'geodirectory' ),
				'desc' => __( 'Reveals some text on hover. Enter some text or use %%input%% to use the input value of the field or the field key for any other info %%email%%. (this can NOT be used with popover text)', 'geodirectory' ),
				'placeholder' => '',
				'default' => '',
				'desc_tip' => true,
				'group' => __( 'Hover Action', 'geodirectory' )
			);

			$arguments['tooltip_text_show'] = array(
				'type' => 'text',
				'title' => __( 'Tooltip text', 'geodirectory' ),
				'desc' => __( 'Reveals some text on hover. Enter some text or use %%input%% to use the input value of the field or the field key for any other info %%email%%. (this can NOT be used with popover text)', 'geodirectory' ),
				'placeholder' => '',
				'default' => '',
				'desc_tip' => true,
				'group' => __( 'Hover Action', 'geodirectory' )
			);

			$arguments['type'] = array(
				'title' => __( 'Type', 'geodirectory' ),
				'desc' => __( 'Select the badge type.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					"" => __( 'Badge', 'geodirectory' ),
					"pill" => __( 'Pill', 'geodirectory' ),
					"button" => __( 'Button', 'geodirectory' ),
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['shadow'] = array(
				'title' => __( 'Shadow', 'geodirectory' ),
				'desc' => __( 'Select the shadow badge type.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					"" => __( 'None', 'geodirectory' ),
					"small" => __( 'small', 'geodirectory' ),
					"medium" => __( 'medium', 'geodirectory' ),
					"large" => __( 'large', 'geodirectory' ),
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['color'] = array(
				'title' => __( 'Color', 'geodirectory' ),
				'desc' => __( 'Select the the color.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
						"" => __( 'Custom colors', 'geodirectory' ),
					) + geodir_aui_colors( true, true, true ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);


			$arguments['bg_color'] = array(
				'type' => 'color',
				'title' => __( 'Background color:', 'geodirectory' ),
				'desc' => __( 'Color for the background.', 'geodirectory' ),
				'placeholder' => '',
				'default' => '#0073aa',
				'desc_tip' => true,
				'element_require' => $design_style ? '[%color%]==""' : '',
				'group' => __( 'Design', 'geodirectory' )
			);
			$arguments['txt_color'] = array(
				'type' => 'color',
				'title' => __( 'Text color:', 'geodirectory' ),
				'desc' => __( 'Color for the text.', 'geodirectory' ),
				'placeholder' => '',
				'desc_tip' => true,
				'default' => '#ffffff',
				'element_require' => $design_style ? '[%color%]==""' : '',
				'group' => __( 'Design', 'geodirectory' )
			);
			$arguments['size'] = array(
				'type' => 'select',
				'title' => __( 'Badge size', 'geodirectory' ),
				'desc' => __( 'Size of the badge.', 'geodirectory' ),
				'options' => array(
					"" => __( 'Default', 'geodirectory' ),
					"h6" => __( 'XS (badge)', 'geodirectory' ),
					"h5" => __( 'S (badge)', 'geodirectory' ),
					"h4" => __( 'M (badge)', 'geodirectory' ),
					"h3" => __( 'L (badge)', 'geodirectory' ),
					"h2" => __( 'XL (badge)', 'geodirectory' ),
					"h1" => __( 'XXL (badge)', 'geodirectory' ),
					"btn-lg" => __( 'L (button)', 'geodirectory' ),
					"btn-sm" => __( 'S (button)', 'geodirectory' ),
				),
				'default' => '',
				'desc_tip' => true,
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['mt'] = geodir_get_sd_margin_input( 'mt' );
			$arguments['mr'] = geodir_get_sd_margin_input( 'mr' );
			$arguments['mb'] = geodir_get_sd_margin_input( 'mb' );
			$arguments['ml'] = geodir_get_sd_margin_input( 'ml' );
		}

		return $arguments;
	}

	/**
	 * Display Widget output.
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
		global $aui_bs5, $gd_post;

		//Set current listing id
		$post_id = ! empty( $gd_post->ID ) ? absint( $gd_post->ID ) : '';
		$is_preview = $this->is_preview();
		$block_preview = $this->is_block_content_call() || $is_preview;

		if ( ! empty( $post_id ) && ! $block_preview && ! is_admin() ) {
			if ( ! in_array( get_post_status( $post_id ), geodir_get_publish_statuses( array( 'post_type' => get_post_type( $post_id ) ) ) ) ) {
				return;
			}
		}

		//Button text
		$button = '';

		$defaults = array(
			'badge'             => __( 'Compare', 'geodir-compare' ),
			'icon_class'        => 'fas fa-square',
			'badge_after'       => __( 'Compare', 'geodir-compare' ),
			'open_in'           => '',
			'icon_class_after'  => 'fas fa-check-square',
			'bg_color'          => '#0073aa',
			'txt_color'         => '#ffffff',
			'type'              => 'badge',
			'tooltip_text'      => __( 'Add to compare list', 'geodir-compare' ),
			'tooltip_text_show' => __( 'View comparison list', 'geodir-compare' ),
			'shadow'            => '',
			'color'             => '',
			'size'              => '',
			'position'          => '',
			'mt'    => '',
			'mb'    => '',
			'mr'    => '',
			'ml'    => '',
		);
		$args = shortcode_atts($defaults, $args, 'gd_compare_button' );

		// set some defaults
		if(empty($args['badge'])){$args['badge'] = $defaults['badge'];}
		if(empty($args['icon_class'])){$args['icon_class'] = $defaults['icon_class'];}
		if(empty($args['badge_after'])){$args['badge_after'] = $defaults['badge_after'];}
		if(empty($args['icon_class_after'])){$args['icon_class_after'] = $defaults['icon_class_after'];}
		if(empty($args['bg_color'])){$args['bg_color'] = $defaults['bg_color'];}
		if(empty($args['txt_color'])){$args['txt_color'] = $defaults['txt_color'];}
		if(!$args['tooltip_text']){$args['tooltip_text'] = $defaults['tooltip_text'];}
		if(!$args['tooltip_text_show']){$args['tooltip_text_show'] = $defaults['tooltip_text_show'];}

		//If this is a listings page, display the button
		if ( $post_id || $block_preview ) {
			//Add custom css class
			$design_style = geodir_design_style();

			// Add required script
			if ( $design_style ) {
				add_action( 'wp_footer', 'geodir_compare_aui_script', 200 );
			}

			$args['css_class'] = $design_style ? 'geodir-compare-button c-pointer' : 'geodir-compare-button';
			$args['open_in'] = $args['open_in'] == 'tab' || $args['open_in'] == 'window' ? $args['open_in'] : '';

			//Ensure label is provided
			if( empty( $args['badge'] ) ) {
				$args['badge'] = __( 'Compare', 'geodir-compare' );
			}

			$post_type       = ! empty( $gd_post->post_type ) ? $gd_post->post_type : '';

			// Onclick handler
			if ( $block_preview ) {
				$args['onclick'] = "#";
			} else {
				$args['onclick'] = "geodir_compare_add('$post_id', '$post_type', '" . $args['open_in'] . "');return false;";
			}

			// make it act like a link
			$args['link'] = '#compare';

			//Extra attributes
			$compare_text  = !empty($args['badge'])                ? __( $args['badge'],'geodir-compare' )          : $defaults['badge'];
			$compare_icon  = !empty($args['icon_class'])           ? esc_attr( $args['icon_class'])                : $defaults['icon_class'];
			$compared_text = !empty($args['badge_after'])          ? __( $args['badge_after'],'geodir-compare' )    : $defaults['badge_after'];
			$compared_icon = !empty($args['icon_class_after'])     ? esc_attr( $args['icon_class_after'])          : $defaults['icon_class_after'];

			$args['extra_attributes']  = ' data-geodir-compare-text="'.$compare_text.'"';
			$args['extra_attributes'] .= ' data-geodir-compared-text="'.$compared_text.'"';
			$args['extra_attributes'] .= ' data-geodir-compare-icon="'.$compare_icon.'"';
			$args['extra_attributes'] .= ' data-geodir-compared-icon="'.$compared_icon.'"';
			$args['extra_attributes'] .= ' data-geodir-compare-post_type="'.$post_type.'"';
			$args['extra_attributes'] .= ' data-geodir-compare-post_id="'.$post_id.'"';

			// tooltips
			if ( $design_style ) {
				// margins
				if ( ! empty( $args['mt'] ) ) { $args['css_class'] .= " mt-" . sanitize_html_class( $args['mt'] ) . " "; }
				if ( ! empty( $args['mr'] ) ) { $args['css_class'] .= ( $aui_bs5 ? ' me-' : ' mr-' ) . sanitize_html_class( $args['mr'] ) . " "; }
				if ( ! empty( $args['mb'] ) ) { $args['css_class'] .= " mb-" . sanitize_html_class( $args['mb'] ) . " "; }
				if ( ! empty( $args['ml'] ) ) { $args['css_class'] .= ( $aui_bs5 ? ' ms-' : ' ml-' ) . sanitize_html_class( $args['ml'] ) . " "; }

				if(!empty($args['size'])){
					switch ($args['size']) {
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

				$args['extra_attributes'] .= ' data-' . ( $aui_bs5 ? 'bs-' : '' ) . 'toggle="tooltip" ';
				$args['extra_attributes'] .= ' title="'.esc_attr($args['tooltip_text']).'" ';
				$args['extra_attributes'] .= ' data-add-title="'.esc_attr($args['tooltip_text']).'" ';
				$args['extra_attributes'] .= ' data-view-title="'.esc_attr($args['tooltip_text_show']).'" ';
			}

			$button =  geodir_get_post_badge( $post_id, $args );
		}

		return $button;
	}
}