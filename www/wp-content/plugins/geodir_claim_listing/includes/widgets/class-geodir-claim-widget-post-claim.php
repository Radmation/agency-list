<?php
/**
 * Claim Listing widget.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Claim_Widget_Post_Claim class.
 */
class GeoDir_Claim_Widget_Post_Claim extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'     => 'businessman',
			'block-category' => 'geodirectory',
			'block-keywords' => "['claim','geodir','geodirectory']",
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_claim_post',
			'name'           => __( 'GD > Post Claim', 'geodir-claim' ),
			'widget_ops'     => array(
				'classname'       => 'geodir-post-claim' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'     => esc_html__( 'Displays the button to claim post.', 'geodir-claim' ),
				'geodirectory'    => true,
				'gd_wgt_showhide' => 'show_on',
				'gd_wgt_restrict' => array( 'gd-detail' )
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
				'title' => __( 'Title:', 'geodir-claim' ),
				'desc' => __( 'The widget title.', 'geodir-claim' ),
				'type' => 'text',
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'text'  => array(
				'title' => __( 'Text:', 'geodir-claim' ),
				'desc' => __( 'The text shown than opens the lightbox.', 'geodir-claim' ),
				'type' => 'text',
				'default' => __( 'Claim Listing', 'geodir-claim' ),
				'desc_tip' => true,
				'advanced' => false
			),
			'output'  => array(
				'title' => __( 'Output Type:', 'geodir-claim' ),
				'desc' => __( 'How the link to open the lightbox is displayed.', 'geodir-claim' ),
				'type' => 'select',
				'options' =>  array(
					'button' => __('Button', 'geodir-claim' ),
					'link' => __( 'Link', 'geodir-claim' ),
				),
				'default' => 'button',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			)
		);

		if ( $design_style ) {
			$arguments['btn_size'] = array(
				'type' => 'select',
				'title' => __( 'Button Size:', 'geodir-claim' ),
				'desc' => __( 'Button size.', 'geodir-claim' ),
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'sm' => __( 'Small', 'geodirectory' ),
					'lg' => __( 'Large', 'geodirectory' ),
				),
				'default' => 'default',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '[%output%]=="button"',
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['btn_color'] = array(
				'type' => 'select',
				'title' => __( 'Button Color:', 'geodir-claim' ),
				'desc' => __( 'Button color.', 'geodir-claim' ),
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'none' => __( 'None', 'geodirectory' ),
				) + geodir_aui_colors( false, true ),
				'default' => 'primary',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '[%output%]=="button"',
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['text_color'] = array(
				'type' => 'select',
				'title' => __( 'Text Color:', 'geodir-claim' ),
				'desc' => __( 'Text color.', 'geodir-claim' ),
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'none' => __( 'None', 'geodirectory' ),
				) + geodir_aui_colors(),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);

			$arguments['alignment']  = array(
				'type' => 'select',
				'title' => __( 'Alignment:', 'geodir-claim' ),
				'desc' => __( 'How the item should be positioned on the page.', 'geodir-claim' ),
				'options' =>  array(
					"" => __( 'None', 'geodirectory' ),
					"left" => __( 'Left', 'geodirectory' ),
					"center" => __( 'Center', 'geodirectory' ),
					"right" => __( 'Right', 'geodirectory' ),
				),
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' )
			);
		}

		$arguments['css_class'] = array(
			'type' => 'text',
			'title' => __( 'Link CSS class: ', 'geodir-claim' ),
			'desc' => __( 'Give the wrapper an extra class so you can style things as you want.', 'geodir-claim' ),
			'placeholder' => '',
			'default' => '',
			'desc_tip' => true,
			'advanced' => false,
			'group' => __( 'Design', 'geodirectory' )
		);

		return $arguments;
	}

	/**
	 * Block output.
	 *
	 * @param array $instance Settings for the widget instance.
	 * @param array $args     Display arguments.
	 * @return bool|string
	 */
	public function output( $instance = array(), $args = array(), $content = '' ) {
		$output = $this->output_html( $instance, $args );

		return $output;
	}

	/**
	 * Generate block HTML.
	 *
	 * @param array $instance Settings for the widget instance.
	 * @param array $args     Display arguments.
	 * @return bool|string
	 */
	public function output_html( $instance = array(), $args = array() ) {
		global $aui_bs5, $post;

		if ( empty( $post ) ) {
			return false;
		}

		if ( ! geodir_claim_show_claim_link( $post->ID ) ) {
			return false;
		}

		$defaults = array(
			'title' => '',
			'text' => __( 'Claim Listing', 'geodir-claim' ),
			'output' => 'button',
			// AUI
			'btn_size' => '',
			'btn_color' => 'primary',
			'text_color' => '',
			'alignment' => '',
			'css_class' => '',
		);

		$instance = wp_parse_args( $instance, $defaults );

		$design_style = geodir_design_style();

		$button_text = apply_filters( 'geodir_claim_widget_button_text', $instance['text'], $post->ID, $instance );
		$button_text = __( $button_text, 'geodirectory' );

		if ( ! is_user_logged_in() ) {
			$current_url = remove_query_arg( array( 'gd_do','gd_go' ), geodir_curPageURL() );
			$current_url = geodir_login_url( add_query_arg( array( 'gd_do' => 'claim' ), $current_url ) );
			$current_url = add_query_arg( array( 'gd_go' => 'claim' ), $current_url ) ;
			$current_url = apply_filters( 'geodir_claim_login_to_claim_url', $current_url, $post->ID );
			$target = esc_attr( 'window.location="' . $current_url . '"' );
		} else {
			$target = 'gd_claim_ajax_lightbox(\'geodir_claim_post_form\',\'\',' . absint( $post->ID ) . ',\'\'); return false;';
		}

		$wrap_class = '';
		$action_class = ' geodir-claim-post-form-link';

		if ( $design_style ) {
			if ( $instance['alignment'] != '' ) {
				if ( $aui_bs5 ) {
					if ( $instance['alignment'] == 'left' ) {
						$instance['alignment'] = 'start';
					} else if ( $instance['alignment'] == 'right' ) {
						$instance['alignment'] = 'end';
					}
				}

				// Alignment
				$wrap_class .= " text-" . sanitize_html_class( $instance['alignment'] );
			}

			if ( $instance['text_color'] != '' ) {
				// Text color
				$action_class .= ' text-' . sanitize_html_class( $instance['text_color'] );
			}

			if ( $instance['output'] == 'button' ) {
				// Button size
				if ( $instance['btn_size'] != '' ) {
					$action_class .= ' btn-' . sanitize_html_class( $instance['btn_size'] );
				}

				// Button color
				if ( $instance['btn_color'] != '' ) {
					$action_class .= ' btn-' . sanitize_html_class( $instance['btn_color'] );
				}
			}
		}

		// CSS class
		if ( $instance['css_class'] != '' ) {
			$action_class .= ' ' . esc_attr( trim( $instance['css_class'] ) );
		}

		$output = '<div class="geodir_post_meta gd-post-claim-wrap' . $wrap_class . '">';

		if ( $design_style ) {
			if ( ! empty( $instance['output'] ) && $instance['output'] == 'button' ) {
				$action_class = 'btn ' . $action_class;
				$type = 'button';
				$href = '';
			} else {
				$type = 'a';
				$href = 'javascript:void(0);';
			}

			$output .= aui()->button(
				array(
					'type' => $type,
					'href' => $href,
					'class' => $action_class,
					'content' => $button_text,
					'onclick' => $target,
					'no_wrap' => true
				)
			);
		} else {
			if ( ! empty( $instance['output'] ) && $instance['output'] == 'button' ) {
				$output .= '<button class="btn ' . $action_class . '" onclick="' . $target . '">' . esc_attr( $button_text ) . '</button>';
			} else {
				$output .= '<a class="' . $action_class . '" href="javascript:void(0)" onclick="' . $target . '">' . esc_attr( $button_text ) . '</a>';
			}
		}

		// Fire on load
		if ( is_user_logged_in() && isset( $_REQUEST['gd_do'] ) && $_REQUEST['gd_do'] == 'claim' ) {
			$output .= "<script>jQuery(function() {if(!window.gdTriggerClaim){jQuery('.geodir-claim-post-form-link:first').trigger( \"click\" );window.gdTriggerClaim=true;}});</script>";
		}

		$output .= '</div>';

		return $output;
	}
}
