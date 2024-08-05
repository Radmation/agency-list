<?php
/**
 * Location Meta widget
 *
 * @package GeoDir_Location_Manager
 * @since 2.0.0.25
 */

/**
 * GeoDir_Location_Widget_Location_Meta class.
 */
class GeoDir_Location_Widget_Location_Meta extends WP_Super_Duper {

	/**
	 * Sets up a widget instance.
	 */
	public function __construct() {
		$options = array(
			'textdomain'    => 'geodirlocation',
			'block-icon'    => 'location-alt',
			'block-category'=> 'geodirectory',
			'block-keywords'=> "['location meta','meta','description']",
			'class_name'    => __CLASS__,
			'base_id'       => 'gd_location_meta',
			'name'          => __( 'GD > Location Meta', 'geodirlocation' ),
			'widget_ops'    => array(
				'classname'   => 'geodir-location-meta-container ' . geodir_bsui_class(),
				'description' => esc_html__( 'Displays the meta title, meta description, location description, image for location.', 'geodirlocation' ),
				'geodirectory' => true,
				'gd_wgt_showhide' => 'show_on',
				'gd_wgt_restrict' => array(),
			),
			'arguments' => array(
				'title' => array(
					'type' => 'text',
					'title' => __( 'Title:', 'geodirlocation' ),
					'desc' => __( 'Extra main title if needed.', 'geodirlocation' ),
					'placeholder' => '',
					'default' => '',
					'desc_tip' => true,
					'advanced' => false
				),
				'type' => array(
					'type' => 'select',
					'title' => __( 'Location Type:', 'geodirlocation' ),
					'desc' => __( 'Select location type.', 'geodirlocation' ),
					'placeholder' => '',
					'options' => array(
						'' => __( 'Auto', 'geodirlocation' ),
						'country' => __( 'Country', 'geodirlocation' ),
						'region' => __( 'Region', 'geodirlocation' ),
						'city' => __( 'City', 'geodirlocation' ),
						'neighbourhood' => __( 'Neighbourhood', 'geodirlocation' ),
					),
					'desc_tip' => true,
					'default' => '',
					'advanced' => false
				),
				'key' => array(
					'type' => 'select',
					'title' => __( 'Key:', 'geodirlocation' ),
					'desc' => __( 'This is the location meta field key.', 'geodirlocation' ),
					'placeholder' => '',
					'options' => array(
						'location_name' => __( 'Location Name', 'geodirlocation' ),
						'location_slug' => __( 'Location Slug', 'geodirlocation' ),
						'location_url' => __( 'Location Url', 'geodirlocation' ),
						'location_link' => __( 'Location Link', 'geodirlocation' ),
						'location_cpt_url' => __( 'Location + CPT Url', 'geodirlocation' ),
						'location_cpt_link' => __( 'Location + CPT Link', 'geodirlocation' ),
						'location_description' => __( 'Location Description', 'geodirlocation' ),
						'location_meta_title' => __( 'Location Meta Title', 'geodirlocation' ),
						'location_meta_description' => __( 'Location Meta Description', 'geodirlocation' ),
						'location_image' => __( 'Location Image', 'geodirlocation' ),
						'location_image_tagline' => __( 'Location Image Tagline', 'geodirlocation' ),
					),
					'desc_tip' => true,
					'default' => 'location_name',
					'advanced' => false
				),
				'fallback_image' => array(
					'type' => 'checkbox',
					'title' => __( "Show post image as a fallback?", 'geodirlocation' ),
					'desc' => __( "If location image not available then show last post image added under this location.", 'geodirlocation' ),
					'desc_tip' => true,
					'value'  => '1',
					'default'  => '0',
					'advanced' => true,
					'element_require' => '[%key%]=="location_image"',
				),
				'image_size' => array(
					'type' => 'select',
					'title' => __( 'Image size:', 'geodirectory' ),
					'desc' => __( 'The WP image size as a text string.', 'geodirectory' ),
					'options' => self::get_image_sizes(),
					'desc_tip' => true,
					'value' => '',
					'default' => '',
					'advanced' => true,
					'element_require' => '[%key%]=="location_image"',
				),
				'no_wrap' => array(
					'type' => 'checkbox',
					'title' => __( 'No Wrap:', 'geodirectory' ),
					'desc' => __( 'Remove wrapping div.', 'geodirectory' ),
					'default' => '0',
					'advanced' => true
				),
				'use_post_location' => array(
					'type' => 'checkbox',
					'title' => __( 'Use Post Location:', 'geodirectory' ),
					'desc' => __( 'Use current post location.', 'geodirectory' ),
					'default' => '0',
					'advanced' => true
				),
				'alignment' => array(
					'type' => 'select',
					'title' => __( 'Alignment:', 'geodirectory' ),
					'desc' => __( 'How the item should be positioned on the page.', 'geodirectory' ),
					'options' => array(
						"" => __( 'None', 'geodirectory' ),
						"block" => __( 'Block', 'geodirectory' ),
						"left" => __( 'Left', 'geodirectory' ),
						"center" => __( 'Center', 'geodirectory' ),
						"right" => __( 'Right', 'geodirectory' ),
					),
					'desc_tip' => true,
					'advanced' => true
				),
				'text_alignment' => array(
					'type' => 'select',
					'title' => __( 'Text Align:', 'geodirectory' ),
					'desc' => __( 'How the text should be aligned.', 'geodirectory' ),
					'options' => array(
						"" => __( 'None', 'geodirectory' ),
						"left" => __( 'Left', 'geodirectory' ),
						"center" => __( 'Center', 'geodirectory' ),
						"right" => __( 'Right', 'geodirectory' ),
					),
					'desc_tip' => true,
					'advanced' => true
				),
				'css_class' => array(
					'type' => 'text',
					'title' => __( 'Extra class:', 'geodirectory' ),
					'desc' => __( 'Give the wrapper an extra class so you can style things as you want.', 'geodirectory' ),
					'placeholder' => '',
					'default' => '',
					'desc_tip' => true,
					'advanced' => true,
				),
			)
		);

		parent::__construct( $options );
	}


	/**
	 * The widget output.
	 *
	 * @param array $instance
	 * @param array $args
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $instance = array(), $args = array(), $content = '' ) {
		global $wp, $aui_bs5, $geodirectory, $post, $gd_post, $gd_post_type_archive_links;

		$instance = shortcode_atts( 
			array(
				'title' => '',
				'type' => '',
				'key' => 'location_name',
				'fallback_image' => '',
				'image_size' => '',
				'no_wrap' => '',
				'use_post_location' => '',
				'alignment' => '',
				'text_alignment' => '',
				'list_hide' => '',
				'list_hide_secondary' => '',
				'css_class' => '',
				'location' => 'none',
			), 
			$instance, 
			'gd_location_meta' 
		);
		if ( empty( $instance['image_size'] ) ) {
			$instance['image_size'] = 'thumbnail';
		}

		$output = '';
		if ( $this->is_preview() ) {
			return $output;
		}

		$location = $geodirectory->location;
		$country = isset( $location->country_slug ) ? $location->country_slug : '';
		$region = isset( $location->region_slug ) ? $location->region_slug : '';
		$city = isset( $location->city_slug ) ? $location->city_slug : '';
		$neighbourhood = isset( $location->neighbourhood_slug ) && GeoDir_Location_Neighbourhood::is_active() ? $location->neighbourhood_slug : '';

		// Private address
		$check_address = false;

		if ( ! empty( $instance['use_post_location'] ) ) {
			if ( ! empty( $gd_post ) && ! empty( $post ) && $gd_post->ID  == $post->ID && GeoDir_Post_types::supports( $gd_post->post_type, 'location' ) && ! empty( $gd_post->country ) && ! empty( $gd_post->region ) && ! empty( $gd_post->city ) ) {
				$check_address = GeoDir_Post_types::supports( $gd_post->post_type, 'private_address' ) ? true : false;

				if ( ! empty( $gd_post->neighbourhood ) && GeoDir_Location_Neighbourhood::is_active() && $instance['type'] == 'neighbourhood' ) {
					$location = GeoDir_Location_Neighbourhood::get_info_by_slug( $gd_post->neighbourhood );
					$neighbourhood = $gd_post->neighbourhood;
				} else {
					$location = GeoDir_Location_City::get_info_by_name( $gd_post->city, $gd_post->country, $gd_post->region );
					$neighbourhood = '';
				}

				if ( ! empty( $location ) ) {
					$country = isset( $location->country_slug ) ? $location->country_slug : '';
					$region = isset( $location->region_slug ) ? $location->region_slug : '';
					$city = isset( $location->city_slug ) ? $location->city_slug : '';
				}
			} else {
				return;
			}
		} else {
			$check_address = ! empty( $gd_post ) && GeoDir_Post_types::supports( $gd_post->post_type, 'private_address' ) ? true : false;
		}

		if ( empty( $country ) && empty( $region ) && empty( $city ) && empty( $neighbourhood ) ) {
			return;
		}

		$key = $instance['key'];
		$type = $instance['type'];

		$_type = '';
		$name = '';
		$info = array();

		if ( ( $neighbourhood && empty( $type ) ) || $type == 'neighbourhood' ) {
			if ( ! empty( $neighbourhood ) ) {
				$_type = 'neighbourhood';
				$info = GeoDir_Location_Neighbourhood::get_info_by_slug( $neighbourhood );
				$name = $neighbourhood;

				if ( ! empty( $info ) ) {
					$name = $info->neighbourhood;
					$info->location_desc = $info->description;
					$info->meta_desc = $info->meta_description;
				}
			} else {
				return;
			}
		} elseif ( ( $city && empty( $type ) ) || $type == 'city' ) {
			if ( ! empty( $city ) ) {
				$_type = 'city';
				$info = GeoDir_Location_SEO::get_seo_by_slug( $city, 'city', $country, $region );
				$name = geodir_location_get_name( 'city', $city );
			} else {
				return;
			}
		} elseif ( ( $region && empty( $type ) ) || $type == 'region' ) {
			if ( ! empty( $region ) ) {
				$_type = 'region';
				$info = GeoDir_Location_SEO::get_seo_by_slug( $region, 'region', $country );
				$name = geodir_location_get_name( 'region', $region );
			} else {
				return;
			}
		} elseif ( ( $country && empty( $type ) ) || $type == 'country' ) {
			if ( ! empty( $country ) ) {
				$_type = 'country';
				$info = GeoDir_Location_SEO::get_seo_by_slug( $country, 'country' );
				$name = geodir_location_get_name( 'country', $country );
			} else {
				return;
			}
		}

		if ( empty( $info ) ) {
			if ( ! in_array( $key, array( 'location_image', 'location_name', 'location_slug', 'location_url', 'location_link', 'location_cpt_url', 'location_cpt_link' ) ) ) {
				return;
			}
		} else {
			$info->name = $name;
		}

		if ( empty( $type ) ) {
			$type = $_type;
		}

		// CSS class
		$design_style = geodir_design_style();

		$css_class = 'geodir-location-meta geodir-meta-' . $key;

		if ( $instance['css_class'] != '' ) {
			$css_class .= " " . esc_attr( $instance['css_class'] );
		}

		if ( $instance['text_alignment'] != '' ) {
			$css_class .=  $design_style ? " text-".sanitize_html_class( $instance['text_alignment'] ) : " geodir-text-align" . sanitize_html_class( $instance['text_alignment'] );
		}

		// set alignment class
		if ( $instance['alignment'] != '' ) {
			if ( $design_style ) {
				if ( $instance['alignment'] == 'block' ) { $css_class .= " d-block "; }
				else if ( $instance['alignment'] == 'left' ) { $css_class .= ( $aui_bs5 ? " float-start me-2 " : " float-left mr-2 " ); }
				else if ( $instance['alignment'] == 'right' ) { $css_class .= ( $aui_bs5 ? " float-end ms-2 " : " float-right ml-2 " ); }
				else if ( $instance['alignment'] == 'center' ) { $css_class .= " mw-100 d-block mx-auto "; }
			} else {
				$css_class .= $instance['alignment'] == 'block' ? " gd-d-block gd-clear-both " : " geodir-align" . sanitize_html_class( $instance['alignment'] );
			}
		}

		$value = '';
		if ( 'location_name' == $key ) {
			if ( ! empty( $info->name ) ) {
				$value = __( stripslashes( $info->name ), 'geodirectory' );
			} elseif ( ! empty( $name ) ) {
				$value = __( stripslashes( $name ), 'geodirectory' );
			}
		} elseif ( 'location_slug' == $key ) {
			if ( ! empty( ${$type} ) ) {
				$value = ${$type};
			}
		} elseif ( 'location_url' == $key || 'location_link' == $key ) {
			$url_args = array( 'gd_country' => $country );

			if ( in_array( $_type, array( 'region', 'city', 'neighbourhood' ) ) ) {
				$url_args['gd_region'] = $region;

				if ( in_array( $_type, array( 'city', 'neighbourhood' ) ) ) {
					$url_args['gd_city'] = $city;

					if ( $_type == 'neighbourhood' ) {
						$url_args['gd_neighbourhood'] = $neighbourhood;
					}
				}
			}

			$location_url = geodir_location_get_url( $url_args, get_option( 'permalink_structure' ) );

			if ( 'location_link' == $key ) {
				$value = '<a href="' . esc_url( $location_url ) . '">' . __( stripslashes( $name ), 'geodirectory' ) . '</a>';
			} else {
				$value = $location_url;
			}
		} elseif ( 'location_cpt_url' == $key || 'location_cpt_link' == $key ) {
			$post_type = geodir_get_current_posttype();

			if ( ! empty( $instance['use_post_location'] ) && ! empty( $gd_post ) && ! empty( $gd_post->post_type ) ) {
				$post_type = $gd_post->post_type;
			}

			if ( $post_type && GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				if ( ! empty( $gd_post_type_archive_links ) && isset( $gd_post_type_archive_links[ $post_type ] ) ) {
					unset( $gd_post_type_archive_links[ $post_type ] );
				}

				$backup_wp = $wp;
				$backup_geodirectory = $geodirectory;

				$wp_query_vars = array();
				if ( ! empty( $wp->query_vars ) ) {
					foreach ( $wp->query_vars as $_key => $vars ) {
						$wp_query_vars[ $_key ] = $vars;
					}
				}

				$geodirectory_location = new stdClass();
				if ( ! empty( $geodirectory->location ) ) {
					foreach ( $geodirectory->location as $_key => $vars ) {
						if ( $_key && is_scalar( $_key ) ) {
							$geodirectory_location->{$_key} = $vars;
						}
					}
				}

				$wp->query_vars['country'] = ! empty( $country ) ? $country : '';
				$wp->query_vars['region'] = ! empty( $region ) && in_array( $_type, array( 'region', 'city', 'neighbourhood' ) ) ? $region : '';
				$wp->query_vars['city'] = ! empty( $city ) && in_array( $_type, array( 'city', 'neighbourhood' ) ) ? $city : '';
				$wp->query_vars['neighbourhood'] = ! empty( $neighbourhood ) && $_type == 'neighbourhood' ? $neighbourhood : '';

				$geodirectory->location->country_slug = '';
				$geodirectory->location->region_slug = '';
				$geodirectory->location->city_slug = '';
				$geodirectory->location->neighbourhood_slug = '';
				$geodirectory->location->set_current( $_type );

				$url = get_post_type_archive_link( $post_type );

				if ( 'location_cpt_link' == $key ) {
					$value = '<a href="' . esc_url( $url ) . '">' . wp_sprintf( _x( '%s in %s', 'cpt posts in location', 'geodirlocation'), geodir_post_type_name( $post_type, true ), __( stripslashes( $name ), 'geodirectory' ) ) . '</a>';
				} else {
					$value = $url;
				}

				$wp = $backup_wp;
				$geodirectory = $backup_geodirectory;

				if ( ! empty( $wp_query_vars ) ) {
					$wp->query_vars = $wp_query_vars;
				}

				if ( ! empty( $geodirectory_location ) ) {
					foreach ( $geodirectory_location as $_key => $vars ) {
						$geodirectory->location->{$_key} = $vars;
					}
				}

				if ( ! empty( $gd_post_type_archive_links ) && isset( $gd_post_type_archive_links[ $post_type ] ) ) {
					unset( $gd_post_type_archive_links[ $post_type ] );
				}
			}
		} elseif ( 'location_description' == $key ) {
			if ( ! empty( $info->location_desc ) ) {
				$value = __( stripslashes( $info->location_desc ), 'geodirectory' );
			}
		} elseif ( 'location_meta_title' == $key ) {
			if ( ! empty( $info->meta_title ) ) {
				$value = __( stripslashes( $info->meta_title ), 'geodirectory' );
			}
		} elseif ( 'location_meta_description' == $key ) {
			if ( ! empty( $info->meta_desc ) ) {
				$value = __( stripslashes( $info->meta_desc ), 'geodirectory' );
			}
		} elseif ( 'location_image' === $key ) {
			$image_class = 'img-responsive';
			if ( $design_style ) {
				$image_class .= ' mw-100 embed-responsive-item ration-item embed-item-cover-xy';
			}

			if ( ! empty( $info->image ) ) {
				$value = wp_get_attachment_image( $info->image, $instance['image_size'], "", array( "class" => $image_class ) );
			} elseif ( ! empty( $instance['fallback_image'] ) ) {
				$params = array(
					'country' => ( ! empty( $location->country ) ? $location->country : $country ),
					'region' => ( ! empty( $location->region ) ? $location->region : $region ),
					'city' => ( ! empty( $location->city ) ? $location->city : $city ),
					'neighbourhood' => $neighbourhood,
				);
				$attachment = GeoDir_Location_SEO::get_post_attachment( $params );

				if ( ! empty( $attachment ) ) {
					$value = geodir_get_image_tag( $attachment, $instance['image_size'], '', $image_class );
					$metadata = ! empty( $image->metadata ) ? maybe_unserialize( $image->metadata ) : '';

					if ( $instance['image_size'] != 'thumbnail' && ! empty( $instance['image_size'] ) ) {
						$value =  wp_image_add_srcset_and_sizes( $value, $metadata , 0 );
					}
				}
			}
		} elseif ( 'location_image_tagline' == $key ) {
			if ( ! empty( $info->image_tagline ) ) {
				$value = __( stripslashes( $info->image_tagline ), 'geodirectory' );
			}
		}

		$value = apply_filters( 'geodir_location_meta_value', $value, $_type, $info, $instance );
		if ( strpos( $value, '%%' ) !== false ) {
			$value = geodir_replace_location_variables( $value );
		}

		// Private address
		if ( ! empty( $value ) && ! empty( $gd_post ) && $check_address ) {
			$value = geodir_post_address( $value, 'gd_location_meta', $gd_post );
		}

		if ( empty( $value ) ) {
			return;
		}

		if ( empty( $instance['no_wrap'] ) ) {
			$output = '<div class="' . $css_class . '">' . $value . '</div>';
		} else {
			$output = $value;
		}

		return apply_filters( 'geodir_location_meta_output', $output, $value, $_type, $info, $instance );
	}

	public static function get_image_sizes() {
		$image_sizes = array( 
			'' => 'default (thumbnail)'
		);

		$available = get_intermediate_image_sizes();
		if ( ! empty( $available ) ) {
			foreach( $available as $size ) {
				$image_sizes[ $size ] = $size;
			}
		}

		$image_sizes['full'] = 'full';

		return $image_sizes;
	}
}
