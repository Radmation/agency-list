<?php
/**
 * CPT Listings widget.
 *
 * @since 2.0.0
 * @package Geodir_Custom_Posts
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_CP_Widget_CPT_Listings class.
 */
class GeoDir_CP_Widget_CPT_Listings extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'     => 'grid-view',
			'block-category' => 'geodirectory',
			'block-keywords' => "['cpt','geodir','geodirectory']",
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_cpt_listings',
			'name'           => __( 'GD > CPT Listings', 'geodir_custom_posts' ),
			'widget_ops'     => array(
				'classname'     => 'geodir-cpt-listings' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'   => esc_html__( 'Displays GeoDirectory post types.', 'geodir_custom_posts' ),
				'geodirectory'  => true,
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
				'desc' => __( 'The widget title.', 'geodir_custom_posts' ),
				'type' => 'text',
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'cpt_exclude' => array(
				'title' => __( 'Exclude CPT:', 'geodir_custom_posts' ),
				'desc' => __( 'Tick CPTs to hide from list.', 'geodir_custom_posts' ),
				'type' => 'select',
				'multiple' => true,
				'options' => geodir_get_posttypes( 'options-plural' ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => true
			),
			'cpt_display' => array(
				'title' => __( 'Display:', 'geodir_custom_posts' ),
				'desc' => __( 'Select display type.', 'geodir_custom_posts' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default (image & name)', 'geodir_custom_posts' ),
					'image' => __( 'Image only', 'geodir_custom_posts' ),
					'name' => __( 'Name only', 'geodir_custom_posts' )
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'cpt_img_width' => array(
				'title' => __( 'Image Width:', 'geodir_custom_posts' ),
				'desc' => __( 'CPT image width. Ex: 90px, 25%, auto', 'geodir_custom_posts' ),
				'type' => 'text',
				'default' => '',
				'desc_tip' => true,
				'advanced' => true,
				'element_require' => $design_style ? '"1"!="1"' : '"1"=="1"'
			),
			'cpt_img_height' => array(
				 'title' => __( 'Image Height:', 'geodir_custom_posts' ),
				'desc' => __( 'CPT image height. Ex: 90px, 25%, auto', 'geodir_custom_posts' ),
				'type' => 'text',
				'default' => '',
				'desc_tip' => true,
				'advanced' => true,
				'element_require' => $design_style ? '"1"!="1"' : '"1"=="1"'
			)
		);

		if ( $design_style ) {
			$arguments['card_height'] = array(
				 'title' => __( 'Card Height:', 'geodir_custom_posts' ),
				'desc' => __( 'Card height. Ex: 150', 'geodir_custom_posts' ),
				'type' => 'number',
				'default' => '',
				'placeholder' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			$arguments['row_cols']    = sd_get_row_cols_input(
				'row_cols',
				array(
					'device_type'     => 'Mobile',
					'group'           => __( 'Card Design', 'geodirectory' ),
					'element_require' => '',
				)
			);

			$arguments['row_cols_md'] = sd_get_row_cols_input(
				'row_cols',
				array(
					'device_type'     => 'Tablet',
					'group'           => __( 'Card Design', 'geodirectory' ),
					'element_require' => '',
				)
			);

			$arguments['columns'] = sd_get_row_cols_input(
				'row_cols',
				array(
					'device_type'     => 'Desktop',
					'group'           => __( 'Card Design', 'geodirectory' ),
					'element_require' => '',
					'default'  => 2,
				)
			);

			$arguments['row_gap'] = array(
				'title' => __( 'Card row gap', 'geodirectory' ),
				'desc' => __( 'This adjusts the spacing between the cards horizontally.','geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			$arguments['column_gap'] = array(
				'title' => __( 'Card column gap', 'geodirectory' ),
				'desc' => __( 'This adjusts the spacing between the cards vertically.','geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			$arguments['card_border'] = array(
				'title' => __( 'Card border', 'geodirectory' ),
				'desc' => __( 'Set the border style for the card.','geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'none' => __( 'None', 'geodirectory' ),
				) + geodir_aui_colors(),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			$arguments['card_shadow'] = array(
				'title' => __( 'Card shadow', 'geodirectory' ),
				'desc' => __( 'Set the card shadow style.','geodirectory' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'None', 'geodirectory' ),
					'small' => __( 'Small', 'geodirectory' ),
					'medium' => __( 'Medium', 'geodirectory' ),
					'large' => __( 'Large', 'geodirectory' ),
				),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			$arguments['color'] = array(
				'type' => 'select',
				'title' => __( 'Text color', 'geodir_custom_posts' ),
				'options' =>  $options = array(
					'' => __( 'None', 'geodirectory' ),
				) + geodir_aui_colors(),
				'default' => '',
				'desc_tip' => true,
				'group' => __( 'Card Design', 'geodirectory' )
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

		return $arguments;
	}

	/**
	 * Outputs the cpt listings on the front-end.
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		$html = $this->output_html( $widget_args, $args );

		return $html;
	}

	/**
	 * Generates widget HTML.
	 *
	 * @global object $post                    The current post object.
	 *
	 * @param array|string $args               Display arguments including before_title, after_title, before_widget, and
	 *                                         after_widget.
	 * @param array|string $instance           The settings for the particular instance of the widget.
	 *
	 * @return bool|string
	 */
	public function output_html( $args = array(), $instance = array() ) {
		$design_style = geodir_design_style();

		$post_types = geodir_get_posttypes( 'array' );

		if ( isset( $instance['columns'] ) && ! isset( $instance['row_cols_lg'] ) ) {
			$instance['row_cols_lg'] = $instance['columns'];
		}

		$defaults = array(
			'title' => '',
			'cpt_exclude' => '',
			'cpt_display' => '',
			'cpt_img_width' => '90px',
			'cpt_img_height' => '90px',
			// AUI settings
			'card_height' => '',
			'columns' => '2',
			'column_gap' => '2',
			'row_gap' => '2',
			'card_border' => '',
			'card_shadow' => '',
			'color' => '',
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
		);

		$instance = wp_parse_args( $instance, $defaults );

		if ( is_array( $instance['cpt_exclude'] ) ) {
			$exclude_cpts = $instance['cpt_exclude'];
		} else {
			$exclude_cpts = explode( ',', trim( $instance['cpt_exclude'] ) );
			if ( ! empty( $exclude_cpts ) ) {
				$exclude_cpts = array_map( 'trim', $exclude_cpts );
			}
		}

		$exclude_cpts = apply_filters( 'geodir_cp_widget_cpt_listings_cpt_exclude', $exclude_cpts, $instance, $args, $this->id_base );

		// Exclude CPT to hide from display.
		if ( ! empty( $exclude_cpts ) ) {
			foreach ( $exclude_cpts as $cpt ) {
				if ( isset( $post_types[ $cpt ] ) ) {
					unset( $post_types[ $cpt ] );
				}
			}
		}

		if ( empty( $post_types ) ) {
			return;
		}

		$cpt_display = apply_filters( 'geodir_cp_widget_cpt_listings_cpt_display', $instance['cpt_display'], $instance, $args, $this->id_base );
		$width = apply_filters( 'geodir_cp_widget_cpt_listings_cpt_img_width', $instance['cpt_img_width'], $instance, $args, $this->id_base );
		$height = apply_filters( 'geodir_cp_widget_cpt_listings_cpt_img_height', $instance['cpt_img_height'], $instance, $args, $this->id_base );

		if ( $width !== '' && strpos( $width, '%' ) !== false ) {
			$_width = 'calc( ' . $width . ' - 2px)';
		} else if ( strpos( $width, 'px' ) !== false ) {
			$_width = 'calc( ' . $width . ' + 24px)';
		} else {
			$_width = $width;
		}

		if ( $height !== '' && strpos( $height, '%' ) !== false ) {
			$_height = 'calc( ' . $height . ' - 2px)';
		} else {
			$_height = $height;
		}

		$style_width = $_width !== '' ? 'width:' . $_width . ';' : '';
		$style_height = $_height !== '' ? 'height:' . $_height . ';' : '';

		// wrap class
		$wrap_class = 'gd-wgt-cpt-list gd-wgt-cpt-list-' . $cpt_display;
		if ( $design_style ) {
			$wrap_class .= ' ' . sd_build_aui_class( $instance );
		}

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

		if ( empty( $instance['color'] ) && $cpt_display != 'name' ) {
			$instance['color'] = 'white';
		}

		$template = $design_style ? $design_style . '/cpt-listings.php' : 'legacy/cpt-listings.php';
		$template_args = array(
			'post_types' => $post_types,
			'display' => $cpt_display,
			'image_width' => $width,
			'image_height' => $height,
			'style_width' => $style_width,
			'style_height' => $style_height,
			'wrap_class' => $wrap_class,
			'columns' => ! empty( $instance['columns'] ) ? absint( $instance['columns'] ) : 2,
			'card_height' => absint( $instance['card_height'] ) > 0 || $cpt_display == 'name' ? absint( $instance['card_height'] ) : 150,
			'text_color_class' => ! empty( $instance['color'] ) ? 'text-' . sanitize_html_class( $instance['color'] ) : '',
			'column_gap_class' => $instance['column_gap'] ? 'mb-' . absint( $instance['column_gap'] ) : 'mb-4',
			'row_gap_class' => $instance['row_gap'] ? 'px-' . absint( $instance['row_gap'] ) : '',
			'card_border_class' => $card_border_class,
			'card_shadow_class' => $card_shadow_class,
		);
		$template_args = apply_filters( 'geodir_cp_cpt_listings_template_args', $template_args, $instance, $args );

		$html = geodir_get_template_html( 
			$template, 
			$template_args,
			'',
			geodir_cp_templates_path()
		);

		return $html;
	}
}
