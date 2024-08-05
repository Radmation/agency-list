<?php
/**
 * Packages widget.
 *
 * @since 2.6.0.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Widget_Pricing class.
 */
class GeoDir_Pricing_Widget_Pricing extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'     => 'money-alt',
			'block-category' => 'geodirectory',
			'block-keywords' => "['geodir','package','pricing']",
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_pricing',
			'name'           => __( 'GD > Pricing', 'geodir_pricing' ),
			'widget_ops'     => array(
				'classname'     => 'geodir-pricing-container' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'   => esc_html__( 'Displays pricing plans for the post type.', 'geodir_pricing' ),
				'geodirectory'  => true,
			)
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 */
	public function set_arguments() {
		$design_style = geodir_design_style();

		$arguments = array(
			'title' => array(
				'type' => 'text',
				'title' => __( 'Title:', 'geodir_pricing' ),
				'desc' => __( 'The widget title.', 'geodir_pricing' ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'post_type' => array(
				'type' => 'select',
				'title' => __( 'Post Type:', 'geodir_pricing' ),
				'desc' => __( 'Post type to show package. Select "Auto" to show packages for current or default post type.', 'geodir_pricing' ),
				'options' => array_merge( array( '' => __( 'Auto', 'geodir_pricing' ) ), geodir_get_posttypes( 'options-plural' ) ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			)
		);

		if ( $design_style ) {
			$arguments['cols'] = array(
				'type' => 'select',
				'title' => __( 'Layout:', 'geodir_pricing' ),
				'desc' => __( 'Layout to display pricing.', 'geodir_pricing' ),
				'options' => array(
					'' => __( 'Default (three columns)', 'geodir_pricing' ),
					'1' => __( 'One column', 'geodir_pricing' ),
					'2' => __( 'Two columns', 'geodir_pricing' ),
					'3' => __( 'Three columns', 'geodir_pricing' ),
					'4' => __( 'Four columns', 'geodir_pricing' ),
					'5' => __( 'Five columns', 'geodir_pricing' ),
					'6' => __( 'Six columns', 'geodir_pricing' ),
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['fa_tick']  = array(
				'type' => 'text',
				'title' => __( 'Tick Icon:', 'geodir_pricing' ),
				'desc' => __( 'FontAwesome icon for allowed feature. Ex: fas fa-check-circle', 'geodir_pricing' ),
				'default' => 'fas fa-check-circle',
				'placeholder' => 'fas fa-check-circle',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodir_pricing' )
			);

			$arguments['fa_untick']  = array(
				'type' => 'text',
				'title' => __( 'Untick Icon:', 'geodir_pricing' ),
				'desc' => __( 'FontAwesome icon for not allowed feature. Ex: fas fa-times-circle', 'geodir_pricing' ),
				'default' => 'fas fa-times-circle',
				'placeholder' => 'fas fa-times-circle',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodir_pricing' )
			);

			$arguments['color_default'] = array(
				'type' => 'select',
				'title' => __( 'Default Color:', 'geodir_pricing' ),
				'desc' => __( 'Color to highlight non-default packages.', 'geodir_pricing' ),
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'none' => __( 'None', 'geodirectory' ),
				) + geodir_aui_colors(),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['color_highlight'] = array(
				'type' => 'select',
				'title' => __( 'Highlight Color:', 'geodir_pricing' ),
				'desc' => __( 'Color to highlight a default package.', 'geodir_pricing' ),
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'none' => __( 'None', 'geodirectory' ),
				) + geodir_aui_colors(),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['row_gap'] = array(
				'title' => __( "Card Row Gap:", 'geodir_pricing' ),
				'desc' => __( 'This adjusts the spacing between the cards horizontally.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'0' => __( 'None', 'geodirectory' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( "Card Design", "geodirectory" )
			);

			$arguments['column_gap'] = array(
				'title' => __( "Card Column Gap:", 'geodir_pricing' ),
				'desc' => __('This adjusts the spacing between the cards vertically.','geodirectory'),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'0' => __( 'None', 'geodirectory' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( "Card Design", "geodirectory" )
			);

			$arguments['card_border'] = array(
				'title' => __( 'Card Border:', 'geodir_pricing' ),
				'desc' => __( 'Set the border style for the card.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( "Default","geodirectory" ),
					'none' => __( "None","geodirectory" ),
				) + geodir_aui_colors(),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( "Card Design","geodirectory" )
			);

			$arguments['card_shadow'] = array(
				'title' => __( 'Card Shadow:', 'geodir_pricing' ),
				'desc' => __( 'Set the card shadow style.', 'geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default (medium)', 'geodirectory' ),
					'none' => __( 'None', 'geodirectory' ),
					'small' => __( 'Small', 'geodirectory' ),
					'medium' => __( 'Medium', 'geodirectory' ),
					'large' => __( 'Large', 'geodirectory' ),
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			$padding_options = array(
				"" => __( 'Default', 'geodirectory' ),
				"0" => __( 'None', 'geodirectory' ),
				"1" => "1",
				"2" => "2",
				"3" => "3",
				"4" => "4",
				"5" => "5"
			);

			// Padding
			$arguments['pt'] = geodir_get_sd_padding_input( 'pt', array( 'options' => $padding_options ) );
			$arguments['pr'] = geodir_get_sd_padding_input( 'pr', array( 'options' => $padding_options ) );
			$arguments['pb'] = geodir_get_sd_padding_input( 'pb', array( 'options' => $padding_options ) );
			$arguments['pl'] = geodir_get_sd_padding_input( 'pl', array( 'options' => $padding_options ) );
		}

		return $arguments;
	}

	/**
	 * Outputs the packages on the front-end.
	 *
	 * @param array $instance Settings for the widget instance.
	 * @param array $args     Display arguments.
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $instance = array(), $args = array(), $content = '' ) {
		$html = $this->output_html( $instance, $args );

		return $html;
	}

	/**
	 * Output HTML.
	 *
	 * @param array $instance Settings for the widget instance.
	 * @param array $args     Display arguments.
	 * @return bool|string
	 */
	public function output_html( $instance = array(), $args = array() ) {
		$design_style = geodir_design_style();

		$defaults = array(
			'title' => '',
			'post_type' => '',
			// AUI
			'cols' => '3',
			'fa_tick' => 'fas fa-check-circle',
			'fa_untick' => 'fas fa-times-circle',
			'color_default' => 'secondary',
			'color_highlight' => 'primary',
			'row_gap' => '3',
			'column_gap' => '3',
			'card_border' => '1',
			'card_shadow' => '',
			'pt' => '0',
			'pr' => '3',
			'pb' => '0',
			'pl' => '3',
		);

		$instance = wp_parse_args( $instance, $defaults );

		if ( empty( $instance['post_type'] ) ) {
			$instance['post_type'] = geodir_get_current_posttype();
		}

		if ( empty( $instance['cols'] ) ) {
			$instance['cols'] = '3';
		}

		if ( empty( $instance['post_type'] ) ) {
			return;
		}

		$packages = geodir_pricing_get_packages( 
			array( 
				'post_type' => $instance['post_type'],
			) 
		);

		if ( ! empty( $packages ) ) {
			$new_packages = array();

			foreach ( $packages as $key => $package ) {
				// Filter private packages.
				if ( empty( $package->is_default ) && geodir_pricing_is_private( $package->id ) ) {
					continue;
				}

				$new_packages[] = $package;
			}

			$packages = $new_packages;
		}

		$packages = apply_filters( 'geodir_pricing_pricing_widget_packages', $packages, $instance, $args );

		if ( empty( $packages ) ) {
			return;
		}

		$color_default = sanitize_html_class( ( $instance['color_default'] === '' ? $defaults['color_default'] : $instance['color_default'] ) );
		$color_highlight = sanitize_html_class( ( $instance['color_highlight'] === '' ? $defaults['color_highlight'] : $instance['color_highlight'] ) );

		// Wrap class
		$cols = count( $packages ) < absint( $instance['cols'] ) ? count( $packages ) : absint( $instance['cols'] );
		$wrap_class = 'row-cols-md-' . $cols;
		$card_class = '';

		$card_border = '';
		$card_shadow_class = '';
		if ( $design_style ) {
			// Wrapper padding
			foreach ( array( 'pt', 'pr', 'pb', 'pl' ) as $arg ) {
				$wrap_class .= ' ' . $arg . '-' . sanitize_html_class( ( $instance[ $arg ] === '' ? $defaults[ $arg ] : $instance[ $arg ] ) );
			}

			// Card Border
			$card_border = $instance['card_border'] === '' ? '1' : ( $instance['card_border'] == 'none' ? '0' : sanitize_html_class( $instance['card_border'] ) );

			// Card Shadow
			if ( $instance['card_shadow'] == 'none' ) {
				$card_shadow_class = '';
			} elseif ( $instance['card_shadow'] == 'small' ) {
				$card_shadow_class = 'shadow-sm';
			} elseif ( $instance['card_shadow'] == 'large' ) {
				$card_shadow_class = 'shadow-lg';
			} else {
				$card_shadow_class = 'shadow';
			}


			// Card padding
			$card_class .= ' px-' . sanitize_html_class( ( $instance['row_gap'] === '' ? $defaults['row_gap'] : $instance['row_gap'] ) );
			$card_class .= ' py-' . sanitize_html_class( ( $instance['column_gap'] === '' ? $defaults['column_gap'] : $instance['column_gap'] ) );
		}

		$template = $design_style ? $design_style . '/pricing.php' : 'legacy/pricing.php';
		$template_args = array(
			'wrap_class' => $wrap_class,
			'card_class' => $card_class,
			'card_border' => $card_border,
			'border_class' => 'border-'  . $card_border,
			'card_shadow_class' => $card_shadow_class,
			'color_default' => $color_default,
			'color_highlight' => $color_highlight,
			'fa_icon_tick' => esc_attr( $instance['fa_tick'] ),
			'fa_icon_untick' => esc_attr( $instance['fa_untick'] )
		);
		$template_args = apply_filters( 'geodir_pricing_pricing_template_args', $template_args, $instance, $args );

		foreach ( $packages as $key => $package ) {
			$packages[ $key ]->features = geodir_pricing_package_features( $package, $template_args );
			$packages[ $key ]->display_name = __( $package->name, 'geodirectory' );
			$packages[ $key ]->display_price = geodir_pricing_price( ( $package->amount > 0 ? $package->amount : 0 ) );
			$packages[ $key ]->display_lifetime = geodir_pricing_table_display_lifetime( $package->time_interval, $package->time_unit );
			$packages[ $key ]->package_link = geodir_pricing_add_listing_url( $package->id );
		}
		$template_args['packages'] = $packages;

		$template_args['template_args'] = $template_args;

		$html = geodir_get_template_html( 
			$template, 
			$template_args,
			'',
			geodir_pricing_templates_path()
		);

		return $html;
	}
}
