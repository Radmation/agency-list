<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Post Expired widget.
 *
 * @since 2.5.0
 */
class GeoDir_Pricing_Widget_Single_Expired_Text extends WP_Super_Duper {

	public function __construct() {

		$options = array(
			'textdomain'     => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'     => 'admin-site',
			'block-category' => 'widgets',
			'block-keywords' => "['expired','geodir','pricing']",
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_single_expired_text',
			'name'           => __( 'GD > Single Expired Text', 'geodir_pricing' ),
			'widget_ops'     => array(
				'classname'    => 'geodir-post-expired' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'  => esc_html__( 'Shows a expired warning text if a post has the expired status.', 'geodir_pricing' ),
				'geodirectory' => true,
			),
		);

		parent::__construct( $options );
	}

	/**
	 * The Super block output function.
	 *
	 * @param array $instance Settings for the widget instance.
	 * @param array $args     Display arguments.
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $instance = array(), $args = array(), $content = '' ) {
		global $post;

		ob_start();

		if ( geodir_pricing_post_is_expired( $post ) ) {
			geodir_pricing_post_expired_text( $post );
		}

		return ob_get_clean();
	}

}