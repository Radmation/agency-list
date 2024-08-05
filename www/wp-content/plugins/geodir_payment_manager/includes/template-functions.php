<?php
/**
 * Pricing Manager Template Functions.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register pricing manager widgets.
 *
 * @since 2.5.0.0
 *
 * @param array $widgets The list of available widgets.
 * @return array Available GD widgets.
 */
function geodir_pricing_register_widgets( $widgets ) {
	if ( get_option( 'geodir_pricing_version' ) ) {
		$widgets[] = 'GeoDir_Pricing_Widget_Pricing';

		// Non Widgets
		new GeoDir_Pricing_Widget_Single_Expired_Text();
	}

	return $widgets;
}

function geodir_pricing_params() {
	$params = array();

    return apply_filters( 'geodir_pricing_params', $params );
}

/**
 * Filters the list of body classes for the current post.
 *
 * @since 2.0.0
 *
 * @global object $post The current post object.
 * @global object $wp_query WP_Query object.
 * @global object $gd_post The current GeoDirectory post object.
 * 
 * @param array $classes Class array.
 * @return array Modified class array.
 */
function geodir_pricing_body_class( $classes ) {
    global $post, $wp_query, $gd_post;

    if ( !empty( $post->ID ) && !empty( $wp_query->is_expired ) && $post->ID == $wp_query->is_expired && is_single() ) {
        $classes[] = 'gd-expired';
    }

	// Add post package id to body class.
	if ( ! empty( $gd_post ) && isset( $gd_post->package_id ) && ( geodir_is_page( 'detail' ) || geodir_is_page( 'preview' ) ) ) {
		$classes[] = 'gd-pkg-id-' . $gd_post->package_id;
	}

    return $classes;
}

/**
 * Filters the list of CSS classes for the current post.
 *
 * @since 2.5.0
 *
 * @param array $classes An array of post classes.
 * @param array $class   An array of additional classes added to the post.
 * @param int   $post_id The post ID.
 */
function geodir_pricing_post_class( $classes, $class, $post_ID ) {
	global $gd_post;

	if ( ! empty( $post_ID ) && geodir_is_gd_post_type( get_post_type( $post_ID ) ) ) {
		if ( ! empty( $gd_post ) && ! empty( $gd_post->ID ) && ! empty( $gd_post->package_id ) && $gd_post->ID == $post_ID ) {
			$package_id = (int) $gd_post->package_id;
		} else {
			$package_id = (int) geodir_get_post_meta( $post_ID, 'package_id', true );
		}

		$classes[] = 'gd-post-pkg-' . $package_id;
	}

	return $classes;
}

/**
 * get_templates_dir function.
 *
 * The function is return templates dir path.
 *
 * @since 2.0.0
 *
 * @return string Templates dir path.
 */
function geodir_pricing_get_templates_dir() {
    return GEODIR_PRICING_PLUGIN_DIR . 'templates';
}

/**
 * get_templates_url function.
 *
 * The function is return templates dir url.
 *
 * @since 2.0.0
 *
 * @return string Templates dir url.
 */
function geodir_pricing_get_templates_url() {
    return GEODIR_PRICING_PLUGIN_URL . '/templates';
}

/**
 * get_theme_template_dir_name function.
 *
 * The function is return theme template dir name.
 *
 * @since 2.0.0
 *
 * @return string Theme template dir name.
 */
function geodir_pricing_theme_templates_dir() {
    return untrailingslashit( apply_filters( 'geodir_pricing_templates_dir', 'geodir_payment_manager' ) );
}

function geodir_pricing_locate_template( $template, $template_name, $template_path = '' ) {
	if ( file_exists( $template ) ) {
		return $template;
	}

	$template_path = geodir_pricing_theme_templates_dir();
	$default_path = geodir_pricing_get_templates_dir();
	$default_template = untrailingslashit( $default_path ) . '/' . $template_name;

	if ( ! file_exists( $default_template ) ) {
		return $template;
	}

    // Look within passed path within the theme - this is priority.
    $template = locate_template(
        array(
            untrailingslashit( $template_path ) . '/' . $template_name,
            $template_name,
        )
    );

    // Get default template
    if ( ! $template ) {
        $template = $default_template;
    }

	return $template;
}

/**
 * post_expired_text function.
 *
 * The function is use for display post expired text content.
 *
 * Check if $echo is true then echo post expired text html content
 * else return post expired text html content.
 *
 * @since 2.5.0
 *
 * @param object $post Post object.
 * @param bool $echo Optional. Default true.
 * @return string Post expired text.
 */
function geodir_pricing_post_expired_text( $post, $echo = true ) {
	$design_style = geodir_design_style();

	if ( ! empty( $post ) && ! empty( $post->post_type ) ) {
		$cpt_name = geodir_strtolower( geodir_post_type_singular_name( $post->post_type ) );
	} else {
		$cpt_name = __( 'business', 'geodir_pricing' );
	}

	$template = $design_style ? $design_style . '/view/post-expired-text.php' : 'view/post-expired-text.php';

	$template_args = array(
		'cpt_name' => $cpt_name
	);

	$template_args = apply_filters( 'geodir_pricing_post_expired_template_args', $template_args, $post );

	$output = geodir_get_template_html( 
		$template, 
		$template_args,
		'',
		geodir_pricing_templates_path()
	);

	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Filter the listing content.
 *
 * @since 1.0.0
 *
 * @global object $post The current post object.
 *
 * @param string $post_desc Post content text.
 * @retrun Post content.
 */
function geodir_pricing_the_content( $post_desc ) {
	global $post;

	if ( $post_desc === '' ) {
		return $post_desc;
	}

	if ( ! ( ! is_admin() && is_object( $post ) && ! empty( $post ) ) ) {
		return $post_desc;
	}

	$post_type = '';
	if ( ! empty( $post->ID ) ) {
		$post_type = get_post_type( $post->ID );
	} else if ( ! empty( $post->pid ) ) {
		$post_type = get_post_type( $post->pid );
	} else if ( ! empty( $post->post_type ) ) {
		$post_type = $post->post_type;
	} else if ( ! empty( $post->listing_type ) ) {
		$post_type = $post->listing_type;
	} else if ( ! empty( $_REQUEST['listing_type'] ) ) {
		$post_type = sanitize_text_field( $_REQUEST['listing_type'] );
	}

	if ( ! geodir_is_gd_post_type( $post_type ) ) {
		return $post_desc;
	}

	if ( isset( $post->ID ) && ! empty( $post->video ) ) {
		if ( strpos( $post_desc, $post->video ) !== false ) {
			return $post_desc;
		}
	}

	if ( ( isset( $post->ID ) || ( ! isset( $post->ID ) && isset( $post->preview ) ) ) && ( $package = geodir_get_post_package( $post ) ) ) {
		$desc_limit = geodir_pricing_package_desc_limit( $package );

		if ( $desc_limit !== NULL ) {
			$post_desc = geodir_excerpt( $post_desc, absint( $desc_limit ) );
		}
	}
	return $post_desc;
}

function geodir_pricing_detail_author_actions() {
	global $gd_post;

	if ( ! empty( $gd_post->ID ) ) {
		// Renew link
		echo geodir_pricing_post_renew_link( $gd_post->ID );
		// Upgrade link
		echo geodir_pricing_post_upgrade_link( $gd_post->ID );
	}
}

function geodir_pricing_post_renew_link( $post_id, $url_only = false ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	if ( ! geodir_is_gd_post_type( get_post_type( $post_id ) ) ) {
		return NULL;
	}

	$renew_link = '';

	if ( geodir_pricing_post_has_renew( $post_id ) ) {
		$renew_url = geodir_pricing_post_renew_url( $post_id );

		if ( $renew_url ) {
			if ( $url_only ) {
				$renew_link = $renew_url;
			} else {
				$renew_link .= '<span class="gd_user_action renew_link">';
					$renew_link .= '<i class="fas fa-sync" aria-hidden="true"></i> ';
					$renew_link .= '<a href="' . esc_url( $renew_url ) . '" title="' . esc_attr__( 'Renew Listing', 'geodir_pricing' ) . '">' . __( 'Renew', 'geodir_pricing' ) . '</a>';
				$renew_link .= '</span>';
			}
		}
	}

	return apply_filters( 'geodir_pricing_post_renew_link', $renew_link, $post_id, $url_only );
}

function geodir_pricing_post_upgrade_link( $post_id, $url_only = false ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	if ( ! geodir_is_gd_post_type( get_post_type( $post_id ) ) ) {
		return NULL;
	}

	$upgrade_link = '';

	if ( geodir_pricing_post_has_upgrade( $post_id ) ) {
		$upgrade_url = geodir_pricing_post_upgrade_url( $post_id );

		if ( $upgrade_url ) {
			if ( $url_only ) {
				$upgrade_link = $upgrade_url;
			} else {
				$upgrade_link = '<span class="gd_user_action upgrade_link">';
					$upgrade_link .= '<i class="fas fa-sync" aria-hidden="true"></i> ';
					$upgrade_link .= '<a href="' . esc_url( $upgrade_url ) . '" title="' . esc_attr__( 'Upgrade Listing', 'geodir_pricing' ) . '">' . __( 'Upgrade', 'geodir_pricing' ) . '</a>';
				$upgrade_link .= '</span>';
			}
		}
	}

	return apply_filters( 'geodir_pricing_post_upgrade_link', $upgrade_link, $post_id, $url_only );
}

function geodir_pricing_cfi_textarea_attributes( $attributes, $cf ) {
	global $gd_post;

	if ( $cf['name'] == 'post_content' ) {
		$package = geodir_get_post_package( $gd_post, $cf['post_type'] );
		$desc_limit = geodir_pricing_package_desc_limit( $package );

		if ( $desc_limit !== NULL && $desc_limit !== '' ) {
			$attributes[] = 'maxlength="' . $desc_limit . '"';
		}
	}

	return $attributes;
}

function geodir_pricing_cfi_aui_textarea_attributes( $attributes, $cf ) {
	global $gd_post;

	if ( $cf['name'] == 'post_content' ) {
		$package = geodir_get_post_package( $gd_post, $cf['post_type'] );
		$desc_limit = geodir_pricing_package_desc_limit( $package );

		if ( $desc_limit !== NULL && $desc_limit !== '' ) {
			$attributes['maxlength'] = $desc_limit;
		}
	}

	return $attributes;
}

function geodir_pricing_tiny_mce_before_init( $mceInit, $editor_id ) {
	global $aui_bs5, $gd_post, $post;

	$the_post = $gd_post;
	$description_field = 'post_content';
	$textarea_parent = geodir_design_style() ? "[data-argument='" . $description_field . "']" : '.geodir_form_row';

	if ( is_admin() && ! wp_doing_ajax() ) {
		$description_field = 'content';
		$textarea_parent = '#wp-content-wrap';

		if ( empty( $the_post ) ) {
			$the_post = $post;
		}
	}

	if ( $editor_id == $description_field && ! empty( $the_post->post_type ) && geodir_is_gd_post_type( $the_post->post_type ) ) {
		$package = geodir_get_post_package( $the_post, $the_post->post_type );
		$desc_limit = geodir_pricing_package_desc_limit( $package );

		if ( $desc_limit !== NULL && $desc_limit !== '' ) {
			$desc_msg = addslashes( wp_sprintf( __( 'For description you can use up to %d characters only for this package.', 'geodir_pricing' ), $desc_limit ) );

			$mceInit['setup'] = 'function(ed){ed.on("keydown",function(e){ob=this;if(ob.id=="' . $editor_id . '"){var content=ed.getContent().replace(/(<[a-zA-Z\/][^<>]*>|\[([^\]]+)\])|(\s+)/ig,"");if(parseInt('.(int)$desc_limit.')-parseInt(content.length)<1&&!(e.keyCode===8||e.keyCode===46))tinymce.dom.Event.cancel(e)}});ed.on("keyup",function(e){ob=this;if(ob.id=="' . $editor_id . '"){var content=ed.getContent();var text=content.replace(/(<[a-zA-Z\/][^<>]*>|\[([^\]]+)\])|(\s+)/ig,"");jQuery("#wp-post_content-wrap .geodir-desc-counter").text((text.length > '.(int)$desc_limit.' ? '.(int)$desc_limit.' : text.length) + " / '.(int)$desc_limit.'");if(parseInt('.(int)$desc_limit.')<parseInt(text.length)&&!(e.keyCode===8||e.keyCode===46))alert("'.$desc_msg.'")}});jQuery("' . $textarea_parent . ' #' . $editor_id . '").on("keydown",function(e){ob=this;var content=jQuery(ob).val();content=content.replace(/(<[a-zA-Z\/][^<>]*>|\[([^\]]+)\])|(\s+)/ig,"");if(parseInt('.(int)$desc_limit.')-parseInt(content.length)<1&&!(e.keyCode===8||e.keyCode===46)){return false;}});jQuery("' . $textarea_parent . ' #' . $editor_id . '").on("keyup",function(e){ob=this;var content=jQuery(ob).val();content=content.replace(/(<[a-zA-Z\/][^<>]*>|\[([^\]]+)\])|(\s+)/ig,"");jQuery("#wp-post_content-wrap .geodir-desc-counter").text((content.length > '.(int)$desc_limit.' ? '.(int)$desc_limit.' : content.length) + " / '.(int)$desc_limit.'");if(parseInt('.(int)$desc_limit.')<parseInt(content.length)&&!(e.keyCode===8||e.keyCode===46))alert("'.$desc_msg.'");});}';
			$mceInit['geodir_counter'] = 'function(){var content=jQuery("' . $textarea_parent . ' #' . $editor_id . '").val();content=content.replace(/(<[a-zA-Z\/][^<>]*>|\[([^\]]+)\])|(\s+)/ig,"");jQuery("#wp-post_content-wrap").find(".geodir-desc-counter").remove();jQuery("#wp-post_content-wrap").append("<span class=\'geodir-desc-counter form-text text-muted small ' . ( $aui_bs5 ? 'text-end d-block' : 'text-right' ) . '\'>" + (content.length > '.(int)$desc_limit.' ? '.(int)$desc_limit.' : content.length) + " / '.(int)$desc_limit.'</span>");jQuery("#post_content-tmce").trigger("click");}';
		}
	}

	return $mceInit;
}

function geodir_pricing_after_wp_tiny_mce( $mce_settings ) {
	if ( ! empty( $mce_settings['post_content']['geodir_counter'] ) ) {
		echo '<script type="text/javascript">jQuery(' . $mce_settings['post_content']['geodir_counter'] . ');</script>';
	}
}

function geodir_pricing_templates_path() {
	return GEODIR_PRICING_PLUGIN_DIR . '/templates/';
}

function geodir_pricing_package_features( $package, $args = array() ) {
	if ( empty( $package ) ) {
		return array();
	}
	
	if ( ! is_object( $package ) ) {
		$package = GeoDir_Pricing_Package::get_package( $package );
	}

	if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
		return array();
	}

	$defaults = array(
		'color_default' => 'secondary',
		'color_highlight' => 'primary',
		'fa_icon_tick' => 'fas fa-check-circle',
		'fa_icon_untick' => 'fas fa-times-circle'
	);

	$params = wp_parse_args( $args, $defaults );

	$features = array();

	// Auto renewal
	$recurring = array( 
		'order' => 1,
		'text' => __( 'Auto renewing', 'geodir_pricing' )
	);
	if ( geodir_pricing_is_recurring( $package->id ) ) {
		$recurring['icon'] = $params['fa_icon_tick'];
		$recurring['color'] = $params['color_highlight'];
	} else {
		$recurring['icon'] = $params['fa_icon_untick'];
		$recurring['color'] = $params['color_default'];
	}
	$features['recurring'] = $recurring;

	// Free trial
	$has_free_trial = false;
	if ( ! empty( $package->trial ) && geodir_pricing_is_recurring( $package->id ) ) {
		$has_free_trial = geodir_pricing_display_free_trial( $package->trial_interval, $package->trial_unit );
	}

	$free_trial = array( 
		'order' => 2
	);
	if ( $has_free_trial ) {
		$free_trial['text'] = wp_sprintf( __( '%s free trial', 'geodir_pricing' ), geodir_ucwords( $has_free_trial ) );
		$free_trial['icon'] = $params['fa_icon_tick'];
		$free_trial['color'] = $params['color_highlight'];
	} else {
		$free_trial['text'] = __( 'Free trial', 'geodir_pricing' );
		$free_trial['icon'] = $params['fa_icon_untick'];
		$free_trial['color'] = $params['color_default'];
	}
	$features['free_trial'] = $free_trial;

	if ( GeoDir_Post_types::supports( $package->post_type, 'featured' ) ) {
		$post_type_name = geodir_post_type_singular_name( $package->post_type );

		// Featured
		$featured = array( 
			'order' => 3,
			'text' => wp_sprintf( __( 'Featured %s', 'geodir_pricing' ), geodir_strtolower( $post_type_name ) )
		);
		if ( geodir_pricing_is_featured( $package->id ) ) {
			$featured['icon'] = $params['fa_icon_tick'];
			$featured['color'] = $params['color_highlight'];
		} else {
			$featured['icon'] = $params['fa_icon_untick'];
			$featured['color'] = $params['color_default'];
		}
		$features['featured'] = $featured;
	}

	// Images
	$images = array( 
		'order' => 4
	);
	if ( geodir_pricing_has_files( $package->id ) ) {
		$image_limit = (int) geodir_pricing_get_meta( $package->id, 'image_limit', true );

		if ( $image_limit > 0 ) {
			$images['text'] = wp_sprintf( _n( '%d photo', '%d photos', $image_limit, 'geodir_pricing' ), $image_limit );
		} else {
			$images['text'] = __( 'Unlimited photos', 'geodir_pricing' );
		}
		$images['icon'] = $params['fa_icon_tick'];
		$images['color'] = $params['color_highlight'];
	} else {
		$images['text'] = __( 'No photo', 'geodir_pricing' );
		$images['icon'] = $params['fa_icon_untick'];
		$images['color'] = $params['color_default'];
	}
	$features['images'] = $images;

	// Categories
	$categories_limit = (int) geodir_pricing_category_limit( $package->id );

	$categories = array( 
		'order' => 5,
		'icon' => $params['fa_icon_tick'],
		'color' => $params['color_highlight']
	);
	if ( $categories_limit > 0 ) {
		$categories['text'] = wp_sprintf( _n( '%d category', '%d categories', $categories_limit, 'geodir_pricing' ), $categories_limit );
	} else {
		$categories['text'] = __( 'Unlimited categories', 'geodir_pricing' );
	}
	$features['categories'] = $categories;

	// Tags
	$tags_limit = (int) geodir_pricing_tag_limit( $package->id );

	$tags = array( 
		'order' => 6,
		'icon' => $params['fa_icon_tick'],
		'color' => $params['color_highlight']
	);
	if ( $tags_limit > 0 ) {
		$tags['text'] = wp_sprintf( _n( '%d tag', '%d tags', $tags_limit, 'geodir_pricing' ), $tags_limit );
	} else {
		$tags['text'] = __( 'Unlimited tags', 'geodir_pricing' );
	}
	$features['tags'] = $tags;

	// Description limit
	$description = array( 
		'order' => 7,
		'text' => __( 'Unlimited description', 'geodir_pricing' )
	);
	if ( ! ( (int) geodir_pricing_package_desc_limit( $package->id ) > 0 ) ) {
		$description['icon'] = $params['fa_icon_tick'];
		$description['color'] = $params['color_highlight'];
	} else {
		$description['icon'] = $params['fa_icon_untick'];
		$description['color'] = $params['color_default'];
	}
	$features['description'] = $description;

	// HTML Editor
	$html_editor = array( 
		'order' => 8,
		'text' => __( 'HTML editor for description', 'geodir_pricing' )
	);
	if ( ! geodir_pricing_disable_html_editor( $package->id ) ) {
		$html_editor['icon'] = $params['fa_icon_tick'];
		$html_editor['color'] = $params['color_highlight'];
	} else {
		$html_editor['icon'] = $params['fa_icon_untick'];
		$html_editor['color'] = $params['color_default'];
	}
	$features['html_editor'] = $html_editor;

	$field_options = geodir_pricing_exclude_field_options( $package->post_type, (array) $package );
	$field_options = apply_filters( 'geodir_pricing_package_field_options', $field_options, $package );

	// Fields
	$exclude_fields = geodir_pricing_get_meta( $package->id, 'exclude_field', true );
	$_exclude_fields = array();
	if ( ! empty( $exclude_fields ) ) {
		foreach ( $exclude_fields as $exclude_field ) {
			if ( isset( $field_options[ $exclude_field ] ) ) {
				$_exclude_fields[] = $field_options[ $exclude_field ];
			}
		}
	}

	$fields = array( 
		'order' => 9
	);
	if ( empty( $_exclude_fields ) ) {
		$fields['text'] = __( 'All fields', 'geodir_pricing' );
		$fields['icon'] = $params['fa_icon_tick'];
		$fields['color'] = $params['color_highlight'];
	} else {
		$fields['text'] = __( 'No', 'geodir_pricing' ) . ' ' . geodir_strtolower( implode( ', ', $_exclude_fields ) );
		$fields['icon'] = $params['fa_icon_untick'];
		$fields['color'] = $params['color_default'];
	}
	$features['fields'] = $fields;

	// Review replies
	$can_reply_review = array( 
		'order' => 10
	);

	$_can_reply_review = geodir_pricing_get_meta( $package->id, 'can_reply_review', true );

	if ( $_can_reply_review != 'nobody' ) {
		if ( $_can_reply_review == 'admin' ) {
			$can_reply_review['text'] = __( 'Admin can reply reviews', 'geodir_pricing' );
		} else if ( $_can_reply_review == 'owner' ) {
			$can_reply_review['text'] = __( 'Owner can reply reviews', 'geodir_pricing' );
		} else {
			$can_reply_review['text'] = __( 'Everyone can reply reviews', 'geodir_pricing' );
		}
		$can_reply_review['icon'] = $params['fa_icon_tick'];
		$can_reply_review['color'] = $params['color_highlight'];
	} else {
		$can_reply_review['text'] = __( 'No review replies', 'geodir_pricing' );
		$can_reply_review['icon'] = $params['fa_icon_untick'];
		$can_reply_review['color'] = $params['color_default'];
	}
	$features['can_reply_review'] = $can_reply_review;

	$features = apply_filters( 'geodir_pricing_package_features', $features, $package, $params, $args );

	if ( ! empty( $features ) ) {
		usort( $features, 'geodir_pricing_package_sort_features' );
	}

	return $features;
}

function geodir_pricing_package_sort_features( $item1, $item2 ) {
	return ( ( isset( $item1['order'] ) && isset( $item1['order'] ) && (float) $item1['order'] <= (float) $item2['order'] ) ? -1 : 1 );
}

/**
 * GD > Listings widget add package ids filter.
 *
 * @since 2.6.0.4
 *
 * @param array $arguments Super duper widget arguments.
 * @param array $options Super duper widget options.
 * @param array $instance Super duper widget instance.
 * @return array Widget arguments.
 */
function geodir_pricing_super_duper_arguments( $arguments, $options, $instance = array() ) {
	// GD Listings linked posts option.
	if ( $options['base_id'] == 'gd_listings' ) {
		$_arguments = array();

		foreach ( $arguments as $key => $argument ) {
			$_arguments[ $key ] = $argument;

			if ( $key == 'post_ids' ) {
				$_arguments['package_ids'] = array(
					'type' => 'text',
					'title' => __( 'Package IDs:', 'geodir_pricing' ),
					'desc' => __( 'Enter a comma separated list of package ids (1,2,3) to limit the listing to these packages only, or a negative list (-1,-2,-3) to exclude those package ids (negative and positive ids can not be mixed) ', 'geodir_pricing'),
					'default' => '',
					'placeholder' => '1,2,3',
					'desc_tip' => true,
					'advanced' => true,
					'group' => __( 'Filters', 'geodirectory' )
				);
			}
		}

		$arguments = $_arguments;
	}

	return $arguments;
}