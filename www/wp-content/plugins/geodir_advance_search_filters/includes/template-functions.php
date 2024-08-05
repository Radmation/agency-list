<?php
/**
 * Plugin template functions.
 *
 * @link       https://wpgeodirectory.com
 * @since      2.0.0
 *
 * @package    GeoDir_Advance_Search_Filters
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_search_templates_path() {
	return GEODIR_ADV_SEARCH_PLUGIN_DIR . 'templates/';
}

function geodir_adv_search_params() {
    $default_near_text = geodir_get_option( 'search_default_near_text' );
    if ( empty( $default_near_text ) ) {
        $default_near_text = geodir_get_search_default_near_text();
    }

    $redirect = geodir_search_onload_redirect();

    $params = array(
        'geodir_advanced_search_plugin_url' => GEODIR_ADV_SEARCH_PLUGIN_URL,
        'geodir_admin_ajax_url' => admin_url('admin-ajax.php'),
        'request_param' => geodir_search_get_request_param(),
        'msg_Near' => __("Near:", 'geodirectory'),
        'default_Near' => $default_near_text,
        'msg_Me' => __("Me", 'geodirectory'),
        'unom_dist' => geodir_adv_search_distance_unit() == 'km' ? __("km", 'geodiradvancesearch') : __("miles", 'geodiradvancesearch'),
        'autocomplete_field_name' => geodir_adv_search_autocomplete_field(),
        'geodir_enable_autocompleter' => geodir_get_option( 'advs_enable_autocompleter' ),
        'search_suggestions_with' => geodir_get_option( 'advs_suggestions_with' ),
        'geodir_location_manager_active' => defined( 'GEODIRLOCATION_VERSION' ) ? '1' : '0',
        'msg_User_defined' => __("User defined", 'geodiradvancesearch'),
        'ask_for_share_location' => false,//($redirect == 'nearest' && apply_filters('geodir_ask_for_share_location', false)), // move to LMv2
        'geodir_autolocate_ask' => ($redirect == 'nearest' && geodir_get_option( 'advs_autolocate_ask' )),
        'geodir_autolocate_ask_msg' => __('Do you wish to be geolocated to listings near you?', 'geodiradvancesearch'),
        'UNKNOWN_ERROR' => __('Unable to find your location.', 'geodiradvancesearch'),
        'PERMISSION_DENINED' => __('Permission denied in finding your location.', 'geodiradvancesearch'),
        'POSITION_UNAVAILABLE' => __('Your location is currently unknown.', 'geodiradvancesearch'),
        'BREAK' => __('Attempt to find location took too long.', 'geodiradvancesearch'),
        'GEOLOCATION_NOT_SUPPORTED' => __('Geolocation is not supported by this browser.', 'geodiradvancesearch'),
        // start not show alert msg
        'DEFAUTL_ERROR' => __('Browser unable to find your location.', 'geodiradvancesearch'),
        // end not show alert msg
        'text_more' => __('More', 'geodiradvancesearch'),
        'text_less' => __('Less', 'geodiradvancesearch'),
        'msg_In' => __('In:', 'geodirectory'),
        'txt_in_country' => __('(Country)', 'geodiradvancesearch'),
        'txt_in_region' => __('(Region)', 'geodiradvancesearch'),
        'txt_in_city' => __('(City)', 'geodiradvancesearch'),
        'txt_in_hood' => __('(Neighbourhood)', 'geodiradvancesearch'),
        'compass_active_color' => '#087CC9',
        'onload_redirect' => $redirect,
        'onload_askRedirect' => (bool)geodir_search_ask_onload_redirect(),
        'onload_redirectLocation' => defined( 'GEODIRLOCATION_VERSION' ) && $redirect == 'location' ? geodir_location_permalink_url( geodir_get_location_link() ) : '',
        'autocomplete_min_chars' => geodir_get_option( 'advs_autocompleter_min_chars', 3 ) ? geodir_get_option( 'advs_autocompleter_min_chars', 3 ) : 3,
        'autocompleter_max_results' => geodir_get_option( 'advs_autocompleter_max_results', 10 ) ? geodir_get_option( 'advs_autocompleter_max_results', 10 ) : 10,
        'autocompleter_filter_location' => defined( 'GEODIRLOCATION_VERSION' ) && (bool)geodir_get_option( 'advs_autocompleter_filter_location', true ),
        'time_format' => geodir_time_format(),
        'am_pm' => '["' . __( 'am' ) . '", "' . __( 'AM' ) . '", "' . __( 'pm' ) . '", "' . __( 'PM' ) . '"]',
        'open_now_format' => apply_filters( 'geodir_adv_search_open_now_format', '{label}, {time}' ), // Open Now, 10:00 AM
        'ajaxPagination' => geodir_search_ajax_pagi_type(),
        'txt_loadMore' => __( 'Load More', 'geodiradvancesearch' ),
        'txt_loading' => __( 'Loading...', 'geodiradvancesearch' )
    );

    return apply_filters( 'geodir_adv_search_params', $params );
}

function geodir_search_widget_options( $args, $options, $instance ) {

//	print_r($args);
//	print_r($options);
//	print_r($instance);
//    exit;

    if( isset($options['base_id']) && 'gd_search' === $options['base_id'] && !empty($args) ) {
	    $new_arr = array();

	    $design_style = geodir_design_style();

	    foreach ( $args as $key => $val ) {

		    if ( $key === 'hide_near_input' ) {
			    $new_arr[ $key ] = $val;
			    if ( $design_style ) {
				    $new_arr['show'] = array(
					    'type'     => 'select',
					    'name'     => 'show',
					    'title'    => __( 'Show:', 'geodiradvancesearch' ),
					    'desc'     => __( 'Show/hide main search bar & advanced filters in search form.', 'geodiradvancesearch' ),
					    'options'  => array(
						    ""         => __( 'Main search bar & Advanced filters', 'geodiradvancesearch' ),
						    "main"     => __( 'Main search bar only', 'geodiradvancesearch' ),
						    "advanced" => __( 'Advanced filters only', 'geodiradvancesearch' )
					    ),
					    'default'  => '',
					    'desc_tip' => true,
					    'advanced' => false,
					    'group'    => __( 'Content', 'geodiradvancesearch' ),
				    );

				    $new_arr['filters_pos'] = array(
					    'type'     => 'select',
					    'name'     => 'show',
					    'title'    => __( 'Filters Positioning', 'geodiradvancesearch' ),
					    'desc'     => __( 'How the filters container should be displayed.', 'geodiradvancesearch' ),
					    'options'  => array(
						    ""         => __( 'Default', 'geodiradvancesearch' ),
						    "float"     => __( 'Float', 'geodiradvancesearch' ),
						    "absolute" => __( 'Absolute', 'geodiradvancesearch' )
					    ),
					    'default'  => '',
					    'desc_tip' => true,
					    'advanced' => false,
					    'group'    => __( 'Content', 'geodiradvancesearch' ),
				    );
			    }

			    $new_arr['customize_filters'] = array(
				    'name'     => 'customize_filters',
				    'title'    => __( 'Open customize filters:', 'geodiradvancesearch' ),
				    'desc'     => __( 'Select when open / hide customize filters.', 'geodiradvancesearch' ),
				    'type'     => 'select',
				    'options'  => array(
					    "default"  => __( 'Default', 'geodiradvancesearch' ),
					    "searched" => __( 'Open when searched', 'geodiradvancesearch' ),
					    "always"   => __( 'Always open', 'geodiradvancesearch' )
				    ),
				    'default'  => 'default',
				    'desc_tip' => true,
				    'advanced' => false,
				    'group'    => __( 'Content', 'geodiradvancesearch' ),
			    );
		    } else {
			    $new_arr[ $key ] = $val;
		    }

	    }

	    $args = $new_arr;

//	print_r( $args );//exit;
//    echo '#####';
    }

	return $args;
}

/**
 * Register parameter in search block pattern.
 *
 * @since 2.1.0.7
 *
 * @param string $attrs Attributes.
 * @return string Attributes.
 */
function geodir_search_block_pattern_attrs( $attrs ) {
	$attrs .= "  customize_filters='default'";

	return $attrs;
}

function geodir_adv_search_autocomplete_field() {
    $field = 's';

    return apply_filters( 'geodir_adv_search_autocomplete_field', $field );
}

function geodir_search_get_field_search_param($htmlvar_name){
    $search_val = '';
    if ( isset( $_REQUEST[ 's' . $htmlvar_name ] ) && $_REQUEST[ 's' . $htmlvar_name ] != '' ) {

        $search_val = isset( $_REQUEST[ 's' . $htmlvar_name] ) ? stripslashes_deep( $_REQUEST[ 's' .$htmlvar_name ] ) : '';
        if ( is_array(  $search_val ) ) {
            $search_val = array_map( 'esc_attr', $search_val );
        } else {
            $search_val = esc_attr(  $search_val );
        }
    }

    return $search_val;
}

function geodir_show_filters_fields( $post_type, $instance = array() ) {
	global $as_fieldset_start, $geodir_search_advanced;

	$post_types = geodir_get_posttypes();
	$design_style = geodir_design_style();
	$geodir_search_advanced = $design_style && ! empty( $instance['show'] ) && $instance['show'] == 'advanced' ? true : false;
	$post_type = $post_type && in_array( $post_type, $post_types ) ? $post_type : $post_types[0];
	?>
	<script type="text/javascript">jQuery(function($){var gd_datepicker_loaded = $('body').hasClass('gd-multi-datepicker') ? true : false;if (!gd_datepicker_loaded){$('body').addClass('gd-multi-datepicker');}});</script>
	<?php
	$fields = GeoDir_Adv_Search_Fields::get_search_fields( $post_type );

	ob_start();
	if ( ! empty( $fields ) ) {
		$as_fieldset_start = 0;

		foreach ( $fields as $field ) {
			$field = stripslashes_deep( $field ); // Strip slashes

			$html = '';
			$htmlvar_name = $field->htmlvar_name;
			$field_type = $field->field_type;

			/**
			 * Filter the output for search custom fields by htmlvar_name.
			 *
			 * Here we can remove or add new functions depending on the htmlvar_name.
			 *
			 * @param string $html       The html to be filtered (blank).
			 * @param object $field      The field object info.
			 * @param string $post_type  The post type.
			 */
			$html = apply_filters( "geodir_search_filter_field_output_var_{$htmlvar_name}", $html, $field, $post_type );

			if ( $html == '' && ( ! isset( $field->main_search ) || ! $field->main_search ) ) {
				/**
				 * Filter the output for advance search custom field by htmlvar name.
				 *
				 * @since 2.2.11
				 *
				 * @param string $html The html to be filtered (blank).
				 * @param object $field The field object info.
				 * @param string $post_type  The post type.
				 */
				$html = apply_filters( 'geodir_search_output_to_advance_field_' . $htmlvar_name, $html, $field, $post_type );

				if ( empty( $html ) ) {
					/**
					 * Filter the output for search custom fields by $field_type.
					 *
					 * Here we can remove or add new functions depending on the $field_type.
					 *
					 * @param string $html       The html to be filtered (blank).
					 * @param object $field The field object info.
					 * @param string $post_type  The post type.
					 */
					$html = apply_filters( "geodir_search_filter_field_output_{$field_type}", $html, $field, $post_type );
				}
			}

			// @todo remove after Events 2.2.4 release.
			if ( $design_style && $field_type == 'event' ) {
				$html = str_replace( array( '</label><li ', '</li></div>' ), array( '</label><div ', '</div></div>' ), $html );
			}

			echo $html;
		}

		if ( $as_fieldset_start > 0 ) {
			echo $design_style ? '</div>' : '</ul></div>'; // End the prev fieldset.
		}
	}

	$html = ob_get_clean();

	echo $html;

	$geodir_search_advanced = '';
}

$geodir_search_main_array = array();
function geodir_search_add_to_main() {
    global $geodir_search_main_array, $geodir_search_post_type;

    $post_type = $geodir_search_post_type;
    if ( ! $post_type ) {
        $post_type = 'gd_place';
    }

    $search_fields = GeoDir_Adv_Search_Fields::get_main_search_fields( $post_type );

    if ( empty( $search_fields ) ) {
        return;
    }

    foreach( $search_fields as $key => $field ) {
        $htmlvar_name = $field->htmlvar_name;
        $priority = ( isset( $field->main_search_priority ) && $field->main_search_priority != '' ) ? $field->main_search_priority : 10;
        $geodir_search_main_array[ $priority ][] = $field;

        add_action( 'geodir_search_form_inputs', 'geodir_search_output_to_main', $priority );
    }
}
function geodir_search_output_to_main() {
	global $geodir_search_main_array, $geodir_search_post_type;

	$post_type = $geodir_search_post_type;
	if ( ! $post_type ) {
		$post_type = geodir_get_default_posttype();
	}

	if ( empty( $geodir_search_main_array ) ) {
		return;
	}

	$tmp = array_values( $geodir_search_main_array );
	$acf = array_shift ($tmp );
	$geodir_search_main_array = $tmp;
	if ( empty( $acf ) ) {
		return;
	}

	foreach( $acf as $cf ) {
		$output = '';

		if ( ! empty( $cf->htmlvar_name ) ) {
			$output = apply_filters( 'geodir_search_output_to_main_field_' . $cf->htmlvar_name, $output, $cf, $post_type );
		}

		if ( empty( $output ) ) {
			$output = apply_filters( 'geodir_search_output_to_main_' . $cf->field_type, '', $cf, $post_type );
		}

		echo $output;
	}
}

function geodir_advance_search_options_output( $terms, $taxonomy_obj, $post_type, $title = '' ) {
    global $wp_query, $as_fieldset_start;

    $design_style = geodir_design_style();

    $field_label = $taxonomy_obj->frontend_title ? __( $taxonomy_obj->frontend_title, 'geodirectory' ) : __( $taxonomy_obj->admin_title, 'geodirectory' );
    $has_fieldset = empty( $taxonomy_obj->main_search ) && $as_fieldset_start > 0 ? true : false;
    $display_label = $has_fieldset ? '<label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '">' . $field_label . '</label>' : '';
    $field_class = 'gd-search-li-' . (int) $has_fieldset . ' gd-search-li-' . $taxonomy_obj->htmlvar_name . ' gd-field-t' . $taxonomy_obj->field_type;
    $aria_label = ! $display_label || ! empty( $taxonomy_obj->main_search ) ? ' aria-label="' . esc_attr( $field_label ) . '"' : '';

    ob_start();

    $geodir_search_field_begin = '';
    $geodir_search_field_end   = '';

    $checkbox_fields = GeoDir_Adv_Search_Fields::checkbox_fields( $post_type );

    if ( $taxonomy_obj->input_type == 'SELECT' ) {
        if ( $title != '' ) {
            $select_default = __( $title, 'geodiradvancesearch' );
        } else {
            $select_default = ! empty( $taxonomy_obj->frontend_title ) ? stripslashes( __( $taxonomy_obj->frontend_title, 'geodirectory' ) ) : __( 'Select option', 'geodiradvancesearch' );
        }

        if ( ! empty( $checkbox_fields ) && in_array( $taxonomy_obj->htmlvar_name, $checkbox_fields ) ) {
            $htmlvar_name = 's' . $taxonomy_obj->htmlvar_name;
        } else {
            $htmlvar_name = 's' . $taxonomy_obj->htmlvar_name . '[]';
        }

        if ( empty( $taxonomy_obj->main_search ) ) {
            $geodir_search_field_begin = '<li class="' . esc_attr( $field_class ) . '">' . $display_label;
        }
        $geodir_search_field_begin .= '<select name="' . esc_attr( $htmlvar_name ) . '" class="cat_select" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '"' . $aria_label . '><option value="" >' . $select_default  . '</option>';

        $geodir_search_field_end = '</select>';
        if ( empty( $taxonomy_obj->main_search ) ) {
            $geodir_search_field_end .= '</li>';
        }
    } elseif ( ( $taxonomy_obj->input_type == 'CHECK' || $taxonomy_obj->input_type == 'RADIO' || $taxonomy_obj->input_type == 'LINK' || $taxonomy_obj->input_type == 'RANGE' ) && $has_fieldset ) {
        if ( $taxonomy_obj->htmlvar_name == 'distance' ) {
            //	$display_label = '';
        }
        $geodir_search_field_begin = '<li class="' . esc_attr( $field_class ) . '">' . $display_label . '<ul>';
        $geodir_search_field_end   = '</ul></li>';
    }

    if ( ! empty( $terms ) ) {
        $range_expand = $taxonomy_obj->range_expand;
        $input_type    = $taxonomy_obj->input_type;

        $expand_search = 0;
        if ( ! empty( $taxonomy_obj->expand_search ) && ( $input_type == 'LINK' || $input_type == 'CHECK' || $input_type == 'RADIO' || $input_type == 'RANGE' ) ) {
            $expand_search = (int) $taxonomy_obj->expand_search;
        }

        $moreoption = '';
        if ( ! empty( $expand_search ) && $expand_search > 0 ) {
            if ( $range_expand ) {
                $moreoption = $range_expand;
            } else {
                $moreoption = 5;
            }
        }


        $classname = '';
        $increment = 1;

        echo $geodir_search_field_begin;

        $count = 0;
        foreach ( $terms as $term ) {

            if(is_array( $term ) && isset($term['value']) && $term['value']==''){ continue;}
            $count++;
            $custom_term = is_array( $term ) && ! empty( $term ) && isset( $term['label'] ) ? true : false;

            $option_label = $custom_term ? $term['label'] : false;
            $option_value = $custom_term ? $term['value'] : false;
            $optgroup     = $custom_term && ( $term['optgroup'] == 'start' || $term['optgroup'] == 'end' ) ? $term['optgroup'] : null;

            if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                $classname = 'class="more"';
            }

            if ( $taxonomy_obj->field_type != 'categories' ) {
                if ( $custom_term ) {
                    $term          = (object) $option_value;
                    $term->term_id = $option_value;
                    $term->name    = $option_label;
                } else {
                    $select_arr = array();
                    if ( isset( $term ) && ! empty( $term ) ) {
                        $select_arr = explode( '/', $term );
                    }

                    $value         = $term;
                    $term          = (object) $term;
                    $term->term_id = $value;
                    $term->name    = $value;

                    if ( isset( $select_arr[0] ) && $select_arr[0] != '' && isset( $select_arr[1] ) && $select_arr[1] != '' ) {
                        $term->term_id = $select_arr[1];
                        $term->name    = $select_arr[0];

                    }
                }
            }

            $geodir_search_field_selected     = false;
            $geodir_search_field_selected_str = '';
            $geodir_search_custom_value_str   = '';
            if ( isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ) {
                if ( ! empty( $checkbox_fields ) && in_array( $taxonomy_obj->htmlvar_name, $checkbox_fields ) && ! empty( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ) {
                    $geodir_search_field_selected = true;
                } elseif ( isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) && is_array( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) && in_array( $term->term_id, $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ) {
                    $geodir_search_field_selected = true;
                }
            } elseif ( $taxonomy_obj->htmlvar_name == 'post_category' && ! isset( $_REQUEST['geodir_search'] ) && ! empty( $wp_query ) && ! empty( $wp_query->queried_object ) && ! empty( $wp_query->queried_object->term_id ) && $wp_query->queried_object->term_id == $term->term_id && $wp_query->is_main_query() ) {
                $geodir_search_field_selected = true;
            }
            if ( isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) && $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] != '' ) {

                $geodir_search_custom_value_str = isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ? stripslashes_deep( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) : '';
                if ( is_array( $geodir_search_custom_value_str ) ) {
                    $geodir_search_custom_value_str = array_map( 'esc_attr', $geodir_search_custom_value_str );
                } else {
                    $geodir_search_custom_value_str = esc_attr( $geodir_search_custom_value_str );
                }
            }
            switch ( $taxonomy_obj->input_type ) {
                case 'CHECK' :
                    if ( $custom_term && $optgroup != '' ) {
                        if ( $optgroup == 'start' ) {
                            echo '<li ' . $classname . '>' . __( $term->name, 'geodirectory' )  . '</li>';
                        }
                    } else {
                        if ( $geodir_search_field_selected ) {
                            $geodir_search_field_selected_str = ' checked="checked" ';
                        }
                        echo '<li ' . $classname . '><input type="checkbox" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'" class="cat_check" name="s' . esc_attr( $taxonomy_obj->htmlvar_name ) . '[]" ' . $geodir_search_field_selected_str . ' value="' . esc_attr( $term->term_id ) . '" /> <label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'">' . __( $term->name, 'geodirectory' ) . '</label></li>';
                        $increment ++;
                    }
                    break;
                case 'RADIO' :
                    if ( $custom_term && $optgroup != '' ) {
                        if ( $optgroup == 'start' ) {
                            echo '<li ' . $classname . '>' . __( $term->name, 'geodirectory' )  . '</li>';
                        }
                    } else {
                        if ( $geodir_search_field_selected ) {
                            $geodir_search_field_selected_str = ' checked="checked" ';
                        }
                        echo '<li ' . $classname . '><input type="radio" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'" class="cat_check" name="s' . esc_attr( $taxonomy_obj->htmlvar_name ) . '[]" ' . $geodir_search_field_selected_str . ' value="' . esc_attr( $term->term_id ) . '" /> <label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'">' . __( $term->name, 'geodirectory' ) . '</label></li>';
                        $increment ++;
                    }
                    break;
                case 'SELECT' :
                    if ( $custom_term && $optgroup != '' ) {
                        if ( $optgroup == 'start' ) {
                            echo '<optgroup label="' . esc_attr( __( $term->name, 'geodirectory' )  ) . '">';
                        } else {
                            echo '</optgroup>';
                        }
                    } else {
                        if ( $geodir_search_field_selected ) {
                            $geodir_search_field_selected_str = ' selected="selected" ';
                        }
                        if($term->term_id!=''){
                            echo '<option value="' . esc_attr( $term->term_id ) . '" ' . $geodir_search_field_selected_str . ' >' . __( $term->name, 'geodirectory' )  . '</option>';
                            $increment ++;
                        }

                    }
                    break;
                case 'LINK' :
                    if ( $custom_term && $optgroup != '' ) {
                        if ( $optgroup == 'start' ) {
                            echo '<li ' . $classname . '> ' . __( $term->name, 'geodirectory' )  . '</li>';
                        }
                    } else {
                        echo '<li ' . $classname . '><a href="' . geodir_search_field_page_url( $post_type, array( 's' . esc_attr( $taxonomy_obj->htmlvar_name ) . '[]' => urlencode( $term->term_id ) ) ) . '">' . __( $term->name, 'geodirectory' ) . '</a></li>';
                        $increment ++;
                    }
                    break;
                case 'RANGE': ############# RANGE VARIABLES ##########

                {
                    $search_starting_value_f = $taxonomy_obj->range_min;
                    $search_starting_value   = $taxonomy_obj->range_min;
                    $search_maximum_value    = $taxonomy_obj->range_max;
                    $search_diffrence        = $taxonomy_obj->range_step;

                    if ( empty( $search_starting_value ) ) {
                        $search_starting_value = 10;
                    }
                    if ( empty( $search_maximum_value ) ) {
                        $search_maximum_value = 50;
                    }
                    if ( empty( $search_diffrence ) ) {
                        $search_diffrence = 10;
                    }

                    $range_from_title  = $taxonomy_obj->range_from_title ? stripslashes( __( $taxonomy_obj->range_from_title, 'geodirectory' ) ) : '';
                    $range_to_title   = $taxonomy_obj->range_to_title ? stripslashes( __( $taxonomy_obj->range_to_title, 'geodirectory' ) ) : '';
                    $range_start = $taxonomy_obj->range_start;

                    if ( ! empty( $range_start ) ) {
                        $search_starting_value = $range_start;
                    }

                    if ( empty( $range_from_title ) ) {
                        $range_from_title = __( 'Less than', 'geodiradvancesearch' );
                    }
                    if ( empty( $range_to_title ) ) {
                        $range_to_title = __( 'More than', 'geodiradvancesearch' );
                    }

                    $j = $search_starting_value_f;
                    $k = 0;

                    $i                        = $search_starting_value_f;
                    $moreoption               = '';
                    $range_expand      = $taxonomy_obj->range_expand;
                    $expand_search            = $taxonomy_obj->expand_search;
                    if ( ! empty( $expand_search ) && $expand_search > 0 ) {
                        if ( $range_expand ) {
                            $moreoption = $range_expand;
                        } else {
                            $moreoption = 5;
                        }
                    }

                    if ( ! empty( $taxonomy_obj->main_search ) && $taxonomy_obj->search_condition == 'LINK' ) {
                         $taxonomy_obj->search_condition = 'SELECT';
                    }

                    switch ( $taxonomy_obj->search_condition ) {

                        case 'SINGLE':
                            $custom_value = isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ? stripslashes_deep( esc_attr( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ) : '';
                            ?>
                            <input type="text" class="cat_input"
                                   name="s<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>"
                                   value="<?php echo esc_attr( $custom_value ); ?>"/> <?php
                            break;

                        case 'FROM':
                            $smincustom_value = isset($_REQUEST[ 'smin' . $taxonomy_obj->htmlvar_name ]) ? esc_attr( $_REQUEST[ 'smin' . $taxonomy_obj->htmlvar_name ] ) : '';
                            $smaxcustom_value = isset($_REQUEST[ 'smax' . $taxonomy_obj->htmlvar_name ]) ? esc_attr( $_REQUEST[ 'smax' . $taxonomy_obj->htmlvar_name ] ) : '';

                            $start_placeholder = apply_filters( 'gd_adv_search_from_start_ph_text', esc_attr( __( 'Start search value', 'geodiradvancesearch' ) ), $taxonomy_obj );
                            $end_placeholder   = apply_filters( 'gd_adv_search_from_end_ph_text', esc_attr( __( 'End search value', 'geodiradvancesearch' ) ), $taxonomy_obj );
                            ?>
                            <div class='from-to'>
                            <input type='number' min="0" step="1"
                                   class='cat_input <?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>'
                                   placeholder='<?php echo esc_attr( $start_placeholder ); ?>'
                                   name='smin<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>'
                                   value='<?php echo $smincustom_value; ?>'>
                            <input type='number' min="0" step="1"
                                   class='cat_input <?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>'
                                   placeholder='<?php echo esc_attr( $end_placeholder ); ?>'
                                   name='smax<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>'
                                   value='<?php echo $smaxcustom_value; ?>'>
                            </div><?php
                            break;
                        case 'LINK':

                            $link_serach_value = @esc_attr( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] );
                            $increment        = 1;
                            while ( $i <= $search_maximum_value ) {
                                if ( $k == 0 ) {
                                    $value = $search_starting_value . '-Less';
                                    ?>
                                    <li class=" <?php if ( $link_serach_value == $value ) {
                                        echo 'active';
                                    } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                        echo 'more';
                                    } ?>"><a
                                            href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php echo $range_from_title . ' ' . $search_starting_value; ?></a>
                                    </li>
                                    <?php
                                    $k ++;
                                } else {
                                    if ( $i <= $search_maximum_value ) {
                                        $value = $j . '-' . $i;
                                        if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 ) {
                                            $display_value = $j;
                                            $value         = $j . '-Less';
                                        } else {
                                            $display_value = '';
                                        }
                                        ?>
                                        <li class=" <?php if ( $link_serach_value == $value ) {
                                            echo 'active';
                                        } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                            echo 'more';
                                        } ?>"><a
                                                href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php if ( $display_value ) {
                                                    echo $display_value;
                                                } else {
                                                    echo $value;
                                                } ?></a></li>
                                        <?php
                                    } else {


                                        $value = $j . '-' . $i;
                                        if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 ) {
                                            $display_value = $j;
                                            $value         = $j . '-Less';
                                        } else {
                                            $display_value = '';
                                        }

                                        ?>
                                        <li class=" <?php if ( $link_serach_value == $value ) {
                                            echo 'active';
                                        } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                            echo 'more';
                                        } ?>"><a
                                                href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php if ( $display_value ) {
                                                    echo $display_value;
                                                } else {
                                                    echo $value;
                                                } ?></a>
                                        </li>
                                        <?php
                                    }
                                    $j = $i;
                                }

                                $i = $i + $search_diffrence;

                                if ( $i > $search_maximum_value ) {
                                    if ( $j != $search_maximum_value ) {
                                        $value = $j . '-' . $search_maximum_value;
                                        ?>
                                    <li class=" <?php if ( $link_serach_value == $value ) {
                                        echo 'active';
                                    } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                        echo 'more';
                                    } ?>"><a
                                            href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php echo $value; ?></a>
                                        </li><?php }
                                    if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 && $j == $search_maximum_value ) {
                                        $display_value = $j;
                                        $value         = $j . '-Less';
                                        ?>
                                        <li class=" <?php if ( $link_serach_value == $value ) {
                                            echo 'active';
                                        } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                            echo 'more';
                                        } ?>"><a
                                                href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php if ( $display_value ) {
                                                    echo $display_value;
                                                } else {
                                                    echo $value;
                                                } ?></a>
                                        </li>
                                        <?php
                                    }

                                    $value = $search_maximum_value . '-More';

                                    ?>
                                    <li class=" <?php if ( $link_serach_value == $value ) {
                                        echo 'active';
                                    } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                        echo 'more';
                                    } ?>"><a
                                            href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php echo $range_to_title . ' ' . $search_maximum_value; ?></a>

                                    </li>

                                    <?php
                                }

                                $increment ++;

                            }
                            break;
                        case 'SELECT':

                            global $wpdb;
                            $cf =  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE post_type = %s and htmlvar_name=%s  ORDER BY sort_order", array(
                                $post_type,
                                $taxonomy_obj->htmlvar_name
                            ) ) ,ARRAY_A );

                            $is_price = false;
                            if($cf){
                                $extra_fields = maybe_unserialize($cf['extra_fields']);
                                if(isset($extra_fields['is_price']) && $extra_fields['is_price']){
                                    $is_price = true;
                                }
                            }

                            if ( $title != '' ) {
                                $select_default = __( $title, 'geodiradvancesearch' );
                            } else {
                                $select_default = ! empty( $taxonomy_obj->frontend_title ) ? stripslashes( __( $taxonomy_obj->frontend_title, 'geodirectory' ) ) : __( 'Select option', 'geodiradvancesearch' );
                            }

                            $custom_search_value = isset($_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ]) ? @esc_attr( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) : '';

                            ?>
                            <li><select name="s<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>"
                                        class="cat_select"
                                        id=""<?php echo $aria_label; ?>>
                                    <option
                                        value=""><?php echo esc_attr( $select_default); ?></option><?php

                                    if ( $search_maximum_value > 0 ) {
                                        while ( $i <= $search_maximum_value ) {
                                            if ( $k == 0 ) {
                                                $value = $search_starting_value . '-Less';
                                                ?>
                                                <option
                                                    value="<?php echo esc_attr( $value ); ?>" <?php if ( $custom_search_value == $value ) {
                                                    echo 'selected="selected"';
                                                } ?> ><?php echo $range_from_title . ' '; echo ($is_price ) ? geodir_currency_format_number($search_starting_value,$cf) :$search_starting_value; ?></option>
                                                <?php
                                                $k ++;
                                            } else {
                                                if ( $i <= $search_maximum_value ) {

                                                    $jo = ($is_price ) ? geodir_currency_format_number($j,$cf) : $j;
                                                    $io = ($is_price ) ? geodir_currency_format_number($i,$cf) : $i;
                                                    $value = $j . '-' . $i;
                                                    $valueo = $jo . '-' . $io;
                                                    if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 ) {
                                                        $display_value = $jo;
                                                        $value         = $j . '-Less';
                                                    } else {
                                                        $display_value = '';
                                                    }
                                                    ?>
                                                    <option
                                                        value="<?php echo esc_attr( $value ); ?>" <?php if ( $custom_search_value == $value ) {
                                                        echo 'selected="selected"';
                                                    } ?> ><?php if ( $display_value ) {
                                                            echo $display_value;
                                                        } else {
                                                            echo $valueo;
                                                        } ?></option>
                                                    <?php
                                                } else {
                                                    $jo = ($is_price ) ? geodir_currency_format_number($j,$cf) : $j;
                                                    $io = ($is_price ) ? geodir_currency_format_number($i,$cf) : $i;
                                                    $value = $j . '-' . $i;
                                                    $valueo = $jo . '-' . $io;
                                                    if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 ) {
                                                        $display_value = $jo;
                                                        $value         = $j . '-Less';
                                                    } else {
                                                        $display_value = '';
                                                    }
                                                    ?>
                                                    <option
                                                        value="<?php echo esc_attr( $value ); ?>" <?php if ( $custom_search_value == $value ) {
                                                        echo 'selected="selected"';
                                                    } ?> ><?php if ( $display_value ) {
                                                            echo $display_value;
                                                        } else {
                                                            echo $valueo;
                                                        } ?></option>
                                                    <?php
                                                }
                                                $j = $i;
                                            }


                                            $i = $i + $search_diffrence;

                                            if ( $i > $search_maximum_value ) {

                                                $jo = ($is_price ) ? geodir_currency_format_number($j,$cf) : $j;
                                                $io = ($is_price ) ? geodir_currency_format_number($i,$cf) : $i;
                                                $search_maximum_valueo = ($is_price ) ? geodir_currency_format_number($search_maximum_value,$cf) : $search_maximum_value;

                                                if ( $j != $search_maximum_value ) {
                                                    $value = $j . '-' . $search_maximum_value;
                                                    $valueo = $jo . '-' . $search_maximum_value;
                                                    ?>
                                                    <option
                                                        value="<?php echo esc_attr( $value ); ?>" <?php if ( $custom_search_value == $value ) {
                                                        echo 'selected="selected"';
                                                    } ?> ><?php echo $valueo; ?></option>
                                                    <?php
                                                }
                                                if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 && $j == $search_maximum_value ) {
                                                    $display_value = $j;
                                                    $value         = $j . '-Less';
                                                    $valueo         = $jo . '-Less';
                                                    ?>
                                                    <option
                                                        value="<?php echo esc_attr( $value ); ?>" <?php if ( $custom_search_value == $value ) {
                                                        echo 'selected="selected"';
                                                    } ?> ><?php if ( $display_value ) {
                                                            echo $display_value;
                                                        } else {
                                                            echo $valueo;
                                                        } ?></option>
                                                    <?php
                                                }
                                                $value = $search_maximum_value . '-More';

                                                ?>
                                                <option
                                                    value="<?php echo esc_attr( $value ); ?>" <?php if ( $custom_search_value == $value ) {
                                                    echo 'selected="selected"';
                                                } ?> ><?php echo $range_to_title . ' ' . $search_maximum_valueo; ?></option>
                                                <?php
                                            }

                                        }
                                    }
                                    ?>
                                </select></li>
                            <?php
                            break;
                        case 'RADIO':


                            $uom      = geodir_adv_search_distance_unit();
                            $dist_dif = $search_diffrence;

                            $htmlvar_name = $taxonomy_obj->htmlvar_name == 'distance' ? 'dist' : 's' . $taxonomy_obj->htmlvar_name;

                            for ( $i = $dist_dif; $i <= $search_maximum_value; $i = $i + $dist_dif ) :
                                $checked = '';
                                if ( isset( $_REQUEST[ $htmlvar_name ] ) && $_REQUEST[ $htmlvar_name ] == $i ) {
                                    $checked = 'checked="checked"';
                                }
                                if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                    $classname = 'class="more"';
                                }
                                echo '<li ' . $classname . '><input type="radio" class="cat_check" name="' . esc_attr( $htmlvar_name ) . '" ' . $checked . ' value="' . $i . '" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $i . '"  /> <label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $i .'">' . __( 'Within', 'geodiradvancesearch' ) . ' ' . $i . ' ' . __( $uom, 'geodirectory' ) . '</label></li>';
                                $increment ++;
                            endfor;
                            break;


                    }
                }
                    #############Range search###############
                    break;

                case "DATE":


                    break;

                default:
                    if ( isset( $taxonomy_obj->field_type ) && $taxonomy_obj->field_type == 'checkbox' ) {
                        $field_type = $taxonomy_obj->field_type;

                        $checked = '';
                        if ( $geodir_search_custom_value_str == '1' ) {
                            $checked = 'checked="checked"';
                        }

                        echo '<li><input ' . $checked . ' type="' . $field_type . '" for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'" class="cat_input" name="s' . esc_attr( $taxonomy_obj->htmlvar_name ) . '"  value="1" /> <label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'">' . __( 'Yes', 'geodiradvancesearch' ) . '</label></li>';

                    } elseif ( isset( $taxonomy_obj->field_type ) && $taxonomy_obj->field_type == 'business_hours' ) {
                        $options = GeoDir_Adv_Search_Business_Hours::business_hours_options( $field_label );

                        $field_value = geodir_search_get_field_search_param( 'open_now' );
                        $minutes = geodir_hhmm_to_bh_minutes( gmdate( 'H:i' ), gmdate( 'N' ) );

                        echo '<li><select data-minutes="' . $minutes . '" name="sopen_now" class="geodir-advs-open-now cat_select" id="geodir_search_open_now"' . $aria_label . '>';
                        foreach ( $options as $option_value => $option_label ) {
                            $selected = selected( $field_value == $option_value, true, false );

                            if ( $option_value == 'now' ) {
                                if ( ! $selected && ( $field_value === 0 || $field_value === '0' || ( ! empty( $field_value ) && $field_value > 0 ) ) && ! in_array( $field_value, array_keys( $options ) ) ) {
                                    $selected = selected( true, true, false );
                                }
                            }
                            echo '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>' . $option_label . '</option>';
                        }
                        echo '</select></li>';
                    } else {
                        if ( ! empty( $taxonomy_obj->main_search ) ) {
                            $display_label = '';
                        }
                        echo '<li>' . $display_label . '<input type="text" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '" class="cat_input' . ( $design_style ? ' form-control' : '' ) . '" name="s' . esc_attr( $taxonomy_obj->htmlvar_name ) . '" value="' . esc_attr( $geodir_search_custom_value_str ) . '" placeholder="' . esc_attr( $field_label ) . '"' . $aria_label . ' /></li>';
                    }
            }

        }

        if ( ! $has_fieldset ) {
            echo $geodir_search_field_end;
        }

        if ( ( $increment - 1 ) > $moreoption && ! empty( $moreoption ) && $moreoption > 0 ) {
            echo '<li class="bordernone"><span class="expandmore" onclick="javascript:geodir_search_expandmore(this);"> ' . __( 'More', 'geodiradvancesearch' ) . '</span></li>';
        }

        if ( $has_fieldset ) {
            echo $geodir_search_field_end;
        }
    }

    return ob_get_clean();
}

/* @todo move to LMv2 */
function geodir_set_near_me_range()
{
    global $gd_session;

    $near_me_range = geodir_adv_search_distance_unit() == 'km' ? (int)$_POST['range'] * 0.621371192 : (int)$_POST['range'];

    $gd_session->set('near_me_range', $near_me_range);

    $json = array();
    $json['near_me_range'] = $near_me_range;
    wp_send_json($json);
}

function geodir_search_get_request_param() {
    global $current_term, $wp_query;

    $request_param = array();

    if (is_tax() && geodir_get_taxonomy_posttype() && is_object($current_term)) {
        $request_param['geo_url'] = 'is_term';
        $request_param['geo_term_id'] = $current_term->term_id;
        $request_param['geo_taxonomy'] = $current_term->taxonomy;

    } elseif (is_post_type_archive() && in_array(get_query_var('post_type'), geodir_get_posttypes())) {
        $request_param['geo_url'] = 'is_archive';
        $request_param['geo_posttype'] = get_query_var('post_type');
    } elseif (is_author() && isset($_REQUEST['geodir_dashbord'])) {
        $request_param['geo_url'] = 'is_author';
        $request_param['geo_posttype'] = esc_attr($_REQUEST['stype']);
    } elseif (is_search() && isset($_REQUEST['geodir_search'])) {
        $request_param['geo_url'] = 'is_search';
        $request_param['geo_request_uri'] = esc_attr($_SERVER['QUERY_STRING']);
    } else {
        $request_param['geo_url'] = 'is_location';
    }

    return json_encode($request_param);
}

###########################################################
############# AUTOCOMPLETE FUNCTIONS END ##################
###########################################################

/**
 * @since 1.4.0
 */
/* @todo move to LMv2 */
function geodir_search_onload_redirect() {
    global $gd_first_redirect;

    if (defined('GEODIR_LOCATIONS_TABLE')) {
        if (empty($gd_first_redirect)) {
            $gd_first_redirect = geodir_get_option( 'advs_first_load_redirect', 'no' );
        }

        if (!in_array($gd_first_redirect, array('no', 'nearest', 'location'))) {
            $gd_first_redirect = 'no';
        }
    } else {
        $gd_first_redirect = 'no';
    }

    return $gd_first_redirect;
}

/**
 * @since 1.4.0
 */
/* @todo move to LMv2 */
function geodir_search_ask_onload_redirect() {
    $mode = false;
    //if (!defined('GEODIR_LOCATIONS_TABLE')) {
    return $mode;
    //}
    global $gd_session;

    $redirect = geodir_search_onload_redirect();
    if ($redirect == 'no') {
        $gd_session->set('gd_onload_redirect_done', 1);
    }

    if (!$gd_session->get('gd_onload_redirect_done')) {
        if ($redirect == 'location') {
            $default_location   = geodir_get_default_location();
            $gd_country         = isset($default_location->country_slug) ? $default_location->country_slug : '';
            $gd_region          = isset($default_location->region_slug) ? $default_location->region_slug : '';
            $gd_city            = isset($default_location->city_slug) ? $default_location->city_slug : '';

            $gd_session->set('gd_country', $gd_country);
            $gd_session->set('gd_region', $gd_region);
            $gd_session->set('gd_city', $gd_city);
            $gd_session->set('gd_multi_location', 1);
            $gd_session->set('gd_onload_redirect_done', 1); // Redirect done on first time load
            $gd_session->set('gd_location_default_loaded', 1); // Default location loaded on first time load
        }

        $mode = true;
    }

    return apply_filters('geodir_search_ask_onload_redirect', $mode, $redirect);
}

// SHARE LOCATION HOOKS START
///add_action('wp_ajax_geodir_share_location', "geodir_share_location");
///add_action( 'wp_ajax_nopriv_geodir_share_location', 'geodir_share_location' ); // call for not logged in ajax
///add_action('wp_ajax_geodir_do_not_share_location', "geodir_do_not_share_location");
///add_action( 'wp_ajax_nopriv_geodir_do_not_share_location', 'geodir_do_not_share_location' ); // call for not logged in ajax

function geodir_search_form_add_script() {
    global $gd_session;

    // update user position every 1 minute
    // @todo
    $my_location = '';//(int)$gd_session->get('my_location') === 1 ? 1 : '';
    ?>
    <script type="text/javascript">
        map_id_arr = [];
        gdUmarker = '';
        my_location = '<?php echo $my_location;?>';
        lat = '<?php //echo $gd_session->get('user_lat');?>';
        lon = '<?php //echo $gd_session->get('user_lon');?>';
        gdUmarker = '';
        userMarkerActive = false;
        gdLocationOptions = {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
        };


        jQuery("body").on("map_show", function(event, map_id) {
            map_id_arr.push(map_id);
            if (lat && lon) {
                setTimeout(function(map_id) {
                    geodir_search_setUserMarker(lat, lon, map_id);
                }, 1, map_id);
            }
        });
    </script>
    <?php
}

function geodir_search_before_widget_content( $before_widget, $args, $instance, $super_duper ) {
	$mode = ! empty( $instance['customize_filters'] ) ? $instance['customize_filters'] : 'default';
	if ( ! empty( $instance['show'] ) && ( $instance['show'] == 'main' || $instance['show'] == 'advanced' ) ) {
		$mode = 'always';
	}
	$calss = 'geodir-advance-search-' . sanitize_html_class( $mode );
	$before_widget = preg_replace( '/(?<=\sclass=["\'])/', $calss . ' ', $before_widget );

	if ( $mode == 'searched' && geodir_is_page( 'search' ) ) {
		$mode = 'search';
	}
	$attrs = ' data-show-adv="' . $mode . '"';
	$widget_id = ! empty( $args['widget_id'] ) ? $args['widget_id'] : ( ! empty( $super_duper ) && ! empty( $super_duper->id ) ? $super_duper->id : 'gd_search' );
	$before_widget = str_replace( 'id="' . $widget_id . '"', 'id="' . $widget_id . '"' . $attrs, $before_widget );

	return $before_widget;
}

function geodir_search_widget_add_class( $calss, $args, $super_duper ) {
	if ( is_admin() ) {
		return $calss;
	}

	$mode = ! empty( $super_duper ) && ! empty( $super_duper->instance['customize_filters'] ) ? $super_duper->instance['customize_filters'] : 'default';

	if ( ! empty( $super_duper->instance['show'] ) && ( $super_duper->instance['show'] == 'main' || $super_duper->instance['show'] == 'advanced' ) ) {
		$mode = 'always';
	}

	$calss .= ' geodir-advance-search-' . sanitize_html_class( $mode );

	return $calss;
}

function geodir_search_widget_add_attr( $attr, $args, $super_duper ) {
	if ( is_admin() ) {
		return $attr;
	}

	$mode = ! empty( $super_duper ) && ! empty( $super_duper->instance['customize_filters'] ) ? $super_duper->instance['customize_filters'] : 'default';

		if ( ! empty( $super_duper->instance['show'] ) && ( $super_duper->instance['show'] == 'main' || $super_duper->instance['show'] == 'advanced' ) ) {
		$mode = 'always';
	}

	if ( $mode == 'searched' && geodir_is_page( 'search' ) ) {
		$mode = 'search';
	}
	$attrs = ' data-show-adv="' . esc_attr( $mode ) . '"';

	return $attrs;
}


function geodir_search_body_class( $classes ) {
    global $wpdb;

    $post_type = geodir_get_search_post_type();
    if ( empty( $stype ) ) {
        $post_types = geodir_get_posttypes();
        $stype = $post_types[0];
    }

    if ( $wpdb->get_var( "SELECT COUNT(id) FROM " . GEODIR_ADVANCE_SEARCH_TABLE . " WHERE post_type= '" . $post_type . "'" ) > 0 ) {
        $classes[] = 'geodir_advance_search';
    }

    return $classes;
}

function geodir_search_show_searched_params( $post_type ) {
	global $aui_bs5, $geodirectory;

    if ( ! geodir_is_page( 'search' ) ) {
        return;
    }

    $fields = GeoDir_Adv_Search_Fields::get_search_fields( $post_type );

    $design_style = geodir_design_style();

    $label_class = 'gd-adv-search-label';
    $sublabel_class = 'gd-adv-search-label-t';
    if ( $design_style ) {
        $label_class .= ' badge c-pointer ' . ( $aui_bs5 ? 'bg-info me-2' : 'badge-info mr-2' );
        $sublabel_class .= ' mb-0 c-pointer ' . ( $aui_bs5 ? 'me-1' : 'mr-1' );
    }

    $params = array();
    if ( isset( $_REQUEST['s'] ) && sanitize_text_field( stripslashes( $_REQUEST['s'] ) ) != '' ) {
        $params[] = '<label class="' . $label_class . ' gd-adv-search-s" data-name="s"><i class="fas fa-times" aria-hidden="true"></i> ' . sanitize_text_field( stripslashes( $_REQUEST['s'] ) ) . '</label>';
    }

    if ( ! empty( $_REQUEST['snear'] ) || ( ! empty( $_REQUEST['near'] ) && $_REQUEST['near'] == 'me' ) ) {
        if ( ! empty( $_REQUEST['near'] ) && $_REQUEST['near'] == 'me' ) {
            $label_text = __( 'My Location', 'geodirectory' );
        } else {
            $label_text = sanitize_text_field( stripslashes( $_REQUEST['snear'] ) );
        }

        $params[] = '<label class="' . $label_class . ' gd-adv-search-near"><i class="fas fa-times" aria-hidden="true"></i> ' . wp_sprintf( __( 'Near: %s', 'geodiradvancesearch' ), $label_text ) . '</label>';
    } else if ( ! empty( $geodirectory->location->type ) ) {
        $_location = $geodirectory->location;

        if ( $_location->type != 'search' || ( $_location->type == 'search' && ! empty( $_location->latitude ) && ! empty( $_location->longitude ) ) ) {
            $params[] = '<label class="' . $label_class . ' gd-adv-search-near" data-location="' . esc_attr( $_location->type ) . '"><i class="fas fa-times" aria-hidden="true"></i> ' . apply_filters( 'geodir_search_near_text', '', '' ) . '</label>';
        }
    }

    if ( ! empty( $fields ) ) {
        foreach( $fields as $key => $field ) {
            $htmlvar_name = $field->htmlvar_name;
            $frontend_title = $field->frontend_title != '' ? $field->frontend_title : $field->admin_title;
            $frontend_title = stripslashes( __( $frontend_title, 'geodirectory' ) );

            switch( $field->input_type ) {
                case 'RANGE': {
                    switch( $field->search_condition ) {
                        case 'SINGLE': {
                            if ( isset( $_REQUEST['s' . $htmlvar_name] ) && $_REQUEST['s' . $htmlvar_name] !== '' ) {
                                $params[] = '<label class="' . $label_class . ' gd-adv-search-range gd-adv-search-' . esc_attr( $htmlvar_name ) . '" data-name="s' . esc_attr( $htmlvar_name ) . '"><i class="fas fa-times" aria-hidden="true"></i> ' . sanitize_text_field( stripslashes( $_REQUEST['s' . $htmlvar_name] ) ) . '</label>';
                            }
                        }
                            break;
                        case 'FROM': {
                            $minvalue = isset( $_REQUEST['smin' . $htmlvar_name] ) && $_REQUEST['smin' . $htmlvar_name] !== '' ? sanitize_text_field( $_REQUEST['smin' . $htmlvar_name] ) : '';
                            $maxvalue = isset( $_REQUEST['smax' . $htmlvar_name] ) && $_REQUEST['smax' . $htmlvar_name] !== '' ? sanitize_text_field( $_REQUEST['smax' . $htmlvar_name] ) : '';
                            $this_search = '';
                            if ( $minvalue != '' && $maxvalue != '' ) {
                                $this_search = $minvalue . ' - ' . $maxvalue;
                            } else if ( $minvalue != '' && $maxvalue == '' ) {
                                $this_search = wp_sprintf( __( 'from %s', 'geodiradvancesearch' ), $minvalue );
                            } else if ( $minvalue == '' && $maxvalue != '' ) {
                                $this_search = wp_sprintf( __( 'to %s', 'geodiradvancesearch' ), $maxvalue );
                            }

                            if ( $this_search != '' ) {
                                $extra_attrs = 'data-name="smin' . esc_attr( $htmlvar_name ) . '" data-names="smax' . esc_attr( $htmlvar_name ) . '"';
                                $params[] = '<label class="' . $label_class . ' gd-adv-search-range gd-adv-search-' . esc_attr( $htmlvar_name ) . '" ' . $extra_attrs . '><i class="fas fa-times" aria-hidden="true"></i> <label class="' . $sublabel_class . '">' . $frontend_title . ': </label>' . $this_search . '</label>';
                            }
                        }
                            break;
                        case 'RADIO': {
                            $htmlvar_name = $htmlvar_name == 'distance' ? 'dist' : 's' . $htmlvar_name;
                            if ( $htmlvar_name == 'dist' && ! empty( $_REQUEST[ $htmlvar_name ] ) && ( empty( $_REQUEST['sgeo_lat'] ) || empty( $_REQUEST['sgeo_lon'] ) ) ) {
                                $_REQUEST[ $htmlvar_name ] = '';
                            }

                            if (isset( $_REQUEST[ $htmlvar_name ] ) && $_REQUEST[ $htmlvar_name ] !== '' ) {
                                $uom = geodir_adv_search_distance_unit();
                                $extra_attrs = 'data-name="' . esc_attr( $htmlvar_name ) . '"';
                                $params[] = '<label class="' . $label_class . ' gd-adv-search-range gd-adv-search-' . $field->htmlvar_name . '" ' . $extra_attrs . '><i class="fas fa-times" aria-hidden="true"></i> '.__( 'Within', 'geodiradvancesearch' ) . ' ' . (int)$_REQUEST[ $htmlvar_name ].' '.__( $uom, 'geodirectory' ) . '</label>';
                            }
                        }
                            break;
                        default : {
                            if (isset( $_REQUEST['s' . $htmlvar_name] ) && $_REQUEST['s' . $htmlvar_name] !== '' ) {
                                $serchlist =  explode("-", sanitize_text_field( stripslashes( $_REQUEST['s' . $htmlvar_name] ) ) );
                                if (!empty( $serchlist) ) {
                                    $first_value = $serchlist[0];
                                    $second_value = isset( $serchlist[1] ) ? trim( $serchlist[1], ' ' ) : '';
                                    $rest = substr( $second_value, 0, 4);

                                    $this_search = '';
                                    if ( $rest == 'Less' ) {
                                        $this_search = __( 'less than', 'geodiradvancesearch' ) . ' ' . $first_value;
                                    } else if ( $rest == 'More' ) {
                                        $this_search = __( 'more than', 'geodiradvancesearch' ) . ' ' . $first_value;
                                    } else if ( $second_value != '' ) {
                                        $this_search = $first_value . ' - ' . $second_value;
                                    }

                                    if ( $this_search != '' ) {
                                        $extra_attrs = 'data-name="s' . esc_attr( $htmlvar_name ) . '"';
                                        $params[] = '<label class="' . $label_class . ' gd-adv-search-range gd-adv-search-' . esc_attr( $htmlvar_name ) . '" ' . $extra_attrs . '><i class="fas fa-times" aria-hidden="true"></i> <label class="' . $sublabel_class . '">' . $frontend_title . ': </label>' . $this_search . '</label>';
                                    }
                                }
                            }
                        }
                            break;
                    }
                }
                    break;
                case 'DATE': {
                    if ( ! empty( $_REQUEST[ $htmlvar_name ] ) && ( $field->data_type == 'DATE' || $field->data_type == 'TIME' ) ) {
                        $value = stripslashes_deep( $_REQUEST[ $htmlvar_name ] );
						// Range
						if ( ! is_array( $value ) && strpos( $value, ' to ' ) > 0 ) {
							$_value = explode( ' to ', $value, 2 );

							$value = array();
							if ( ! empty( $_value[0] ) ) {
								$value['from'] = trim( $_value[0] );
							}
							if ( ! empty( $_value[1] ) ) {
								$value['to'] = trim( $_value[1] );
							}
						}
                        $cf = geodir_get_field_infoby( 'htmlvar_name', $htmlvar_name, $post_type );
                        $extra_fields = ! empty( $cf->extra_fields ) ? maybe_unserialize( $cf->extra_fields ) : NULL;

                        if ( $field->data_type == 'DATE' ) {
                            $format = ! empty( $extra_fields['date_format'] ) ? $extra_fields['date_format'] : geodir_date_format();
                        } elseif ( $field->data_type == 'TIME' ) {
                            $format = ! empty( $extra_fields['time_format'] ) ? $extra_fields['time_format'] : geodir_time_format();
                        } else {
                            $format = '';
                        }

                        $filters = '';
						$extra_attrs = 'data-name="' . esc_attr( $htmlvar_name ) . '"';
                        if ( is_array( $value ) ) {
                            $value_from = isset( $value['from'] ) && $value['from'] != '' ? date_i18n( $format, strtotime( sanitize_text_field( $value['from'] ) ) ) : '';
                            $value_to = isset( $value['to'] ) && $value['to'] != '' ? date_i18n( $format, strtotime( sanitize_text_field( $value['to'] ) ) ) : '';

							if ( ! $design_style ) {
								$extra_attrs = 'data-name="' . esc_attr( $htmlvar_name ) . '[from]" data-names="' . esc_attr( $htmlvar_name ) . '[to]"';
							}
                            if ( $value_from != '' && $value_to == '' ) {
                                $filters .= wp_sprintf( __( 'from %s', 'geodiradvancesearch' ), $value_from );
                            } else if ( $value_from == '' && $value_to != '' ) {
                                $filters .= wp_sprintf( __( 'to %s', 'geodiradvancesearch' ), $value_to );
                            } else if ( $value_from != '' && $value_to != '' ) {
                                $filters .= wp_sprintf( __( '%s to %s', 'geodiradvancesearch' ), $value_from, $value_to );
                            }
                        } else {
                            $filters .= date_i18n( $format, strtotime( sanitize_text_field( $value ) ) );
                        }

                        if ( $filters != '' ) {
                            $params[] = '<label class="' . $label_class . ' gd-adv-search-date gd-adv-search-' . esc_attr( $htmlvar_name ) . '" ' . $extra_attrs . '><i class="fas fa-times" aria-hidden="true"></i> <label class="' . $sublabel_class . '">' . $frontend_title . ': </label>' . $filters . '</label>';
                        }
                    }
                }
                    break;
                default: {
                    if (isset( $_REQUEST['s' . $htmlvar_name] ) && ( ( is_array( $_REQUEST['s' . $htmlvar_name] ) && !empty( $_REQUEST['s' . $htmlvar_name] ) ) || ( ! is_array(  $_REQUEST['s' . $htmlvar_name] ) && $_REQUEST['s' . $htmlvar_name] !== '' ) ) ) {
                        if ( is_array( $_REQUEST['s' . $htmlvar_name] ) ) {
                            $extra_attrs = 'data-name="s' . esc_attr( $htmlvar_name ) . '[]"';
                            $values = array_map( 'sanitize_text_field', stripslashes_deep( $_REQUEST['s' . $htmlvar_name] ) );
                            if ( $htmlvar_name == 'post_category' ) {
                                $value = array();
                                foreach ( $values as $value_id) {
                                    $value_term = get_term( $value_id, $post_type . 'category' );
                                    if (!empty( $value_term) && isset( $value_term->name) ) {
                                        $value[] = $value_term->name;
                                    }
                                }
                                $value = !empty( $value ) ? implode(', ', $value ) : '';
                            } else {
                                $field_option_values = GeoDir_Adv_Search_Fields::get_custom_field_meta( 'option_values', $htmlvar_name, $post_type );
                                $field_option_values = geodir_string_values_to_options( stripslashes_deep( $field_option_values ) );
                                if (!empty( $field_option_values) ) {
                                    $value = array();
                                    foreach ( $field_option_values as $option_value ) {
                                        $option_label = isset( $option_value['label'] ) ? $option_value['label'] : '';
                                        $option_val = isset( $option_value['value'] ) ? $option_value['value'] : $option_label;
                                        if ( $option_label != '' && $option_val!='' && in_array( $option_val, stripslashes_deep( $_REQUEST['s' . $htmlvar_name] ) ) ) {
                                            $value[] = __( $option_label, 'geodirectory' );
                                        }
                                    }
                                    $value = !empty( $value ) ? implode( ', ', $value ) : '';
                                } else {
                                    $value = implode( ', ', $values );
                                }
                            }

                            if ( $value ) {
                                $value = '<label class="' . $sublabel_class . '">' . $frontend_title . ': </label>' . $value;
                            }
                        } else {
                            $value = sanitize_text_field( stripslashes_deep( $_REQUEST['s' . $htmlvar_name] ) );
                            $extra_attrs = 'data-name="s' . esc_attr( $htmlvar_name ) . '"';

                            if ( $htmlvar_name == 'post_category' ) {
                                $value = '';
                                $value_term = get_term( sanitize_text_field( stripslashes_deep( $_REQUEST['s' . $htmlvar_name] ) ), $post_type . 'category' );
                                if ( !empty( $value_term ) && isset( $value_term->name) ) {
                                    $value = $value_term->name;
                                }
                            } else {
                                $field_option_values = GeoDir_Adv_Search_Fields::get_custom_field_meta( 'option_values', $htmlvar_name, $post_type );
                                $field_option_values = geodir_string_values_to_options( stripslashes_deep( $field_option_values ) );
                                if (!empty( $field_option_values) ) {
                                    $value = array();
                                    foreach ( $field_option_values as $option_value ) {
                                        $option_label = isset( $option_value['label'] ) ? $option_value['label'] : '';
                                        $option_val = isset( $option_value['value'] ) ? $option_value['value'] : $option_label;

                                        if ( $option_label != '' && $option_val != '' && $option_val == stripslashes_deep( $_REQUEST['s' . $htmlvar_name] ) ) {
                                            $value[] = __( $option_label, 'geodirectory' );
                                        }
                                    }
                                    $value = !empty( $value ) ? implode(', ', $value ) : '';
                                }

                                if ( $field->field_type == 'checkbox' && (int) $_REQUEST['s' . $htmlvar_name] == 1 ) {
                                    $value = $frontend_title;
                                }
                            }

                            if ( ! ( $field->field_type == 'checkbox' && (int) $_REQUEST['s' . $htmlvar_name] == 1 ) && $value ) {
                                $value = '<label class="' . $sublabel_class . '">' . $frontend_title . ': </label>' . $value;
                            }
                        }

                        if ( $value != '' ) {
                            $params[] = '<label class="' . $label_class . ' gd-adv-search-default gd-adv-search-' . esc_attr( $htmlvar_name ) . '" ' . $extra_attrs . '><i class="fas fa-times" aria-hidden="true"></i> ' . $value . '</label>';
                        }
                    }
                }
                    break;
            }
        }
    }

    $params = apply_filters( 'geodir_search_filter_searched_params', $params, $post_type, $fields );
    if ( ! empty( $params && geodir_get_option( 'advs_show_clear_filters' ) ) ) {
        $label_class = str_replace( array( "badge-info", "bg-info" ), array( "badge-primary", "bg-primary" ), $label_class );
        $params = array_merge( array( '<label title="' . esc_attr__( 'Clear All Filters', 'geodiradvancesearch' ) . '" class="' . $label_class . ' geodir-clear-filters gd-adv-search-default gd-adv-search-clear-all"><i class="fas fa-times" aria-hidden="true"></i> ' . geodir_search_clear_filters_button_text() . '</label>' ), $params );
    }

    if ( ! empty( $params ) ) {
        $searched_params = '<div class="gd-adv-search-labels' . ( $design_style ? ' pt-3 pb-2' : '' ) . '">' . implode( '', $params ) . '</div>';
    } else {
        $searched_params = '';
    }

    echo apply_filters( 'geodir_search_show_searched_params', $searched_params, $post_type, $fields, $params );
}

function geodir_search_set_map_params( $params, $map_args = array() ) {
	if ( ! empty( $params['map_type'] ) && $params['map_type'] == 'archive' && ! empty( $params['posts'] ) && $params['posts'] == '-1' && geodir_search_has_ajax_search( true ) ) {
		$params['posts'] = 'geodir-loop-container';
	}

	return $params;
}

function geodir_advance_search_options_output_aui( $terms, $taxonomy_obj, $post_type, $title = '' ) {
    global $wp_query, $aui_bs5, $as_fieldset_start;

    $main_search = !empty($taxonomy_obj->main_search) ?  true : false;
    $field_label = $taxonomy_obj->frontend_title ? __( $taxonomy_obj->frontend_title, 'geodirectory' ) : __( $taxonomy_obj->admin_title, 'geodirectory' );
    $has_fieldset = empty( $taxonomy_obj->main_search ) && $as_fieldset_start > 0 ? true : false;
    $label_hide = $main_search ? ' sr-only visually-hidden' : '';
    $display_label = $has_fieldset ? '<label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '" class="text-muted form-field-label' . $label_hide . ( $aui_bs5 ? ' form-label' : '' ) . '">' . $field_label . '</label>' : '';
    $field_class = 'gd-search-li-' . (int) $has_fieldset . ' gd-search-li-' . $taxonomy_obj->htmlvar_name . ' gd-field-t' . $taxonomy_obj->field_type;
    $aria_label = ! $display_label || ! empty( $taxonomy_obj->main_search ) ? ' aria-label="' . esc_attr( $field_label ) . '"' : '';
    $wrap_attrs = '';

    ob_start();

    $geodir_search_field_begin = '';
    $geodir_search_field_end   = '';

    $checkbox_fields = GeoDir_Adv_Search_Fields::checkbox_fields( $post_type );

    if ( $taxonomy_obj->input_type == 'SELECT' ) {
        if ( $title != '' ) {
            $select_default = __( $title, 'geodiradvancesearch' );
        } else {
            $select_default = ! empty( $taxonomy_obj->frontend_title ) ? stripslashes( __( $taxonomy_obj->frontend_title, 'geodirectory' ) ) : __( 'Select option', 'geodiradvancesearch' );
        }

        if ( ! empty( $checkbox_fields ) && in_array( $taxonomy_obj->htmlvar_name, $checkbox_fields ) ) {
            $htmlvar_name = 's' . $taxonomy_obj->htmlvar_name;
        } else {
            $htmlvar_name = 's' . $taxonomy_obj->htmlvar_name . '[]';
        }

        if ( $main_search || $has_fieldset ) {
            $geodir_search_field_begin = '<div class="' . ( $aui_bs5 ? ( $main_search ? '' : 'mb-3' ) : 'form-group' ) . ' ' . $field_class . '">' . $display_label;
        }
        $geodir_search_field_begin .= '<select name="' . esc_attr( $htmlvar_name ) . '" class="cat_select form-control ' . ( $aui_bs5 ? 'form-select' : 'custom-select' ) . '" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '"' . $aria_label . '><option value="" >' . $select_default  . '</option>';

        $geodir_search_field_end = '</select>';
        if ( !empty( $taxonomy_obj->main_search || $has_fieldset ) ) {
            $geodir_search_field_end .= '</div>';
        }
    } elseif ( $taxonomy_obj->input_type == 'CHECK' || $taxonomy_obj->input_type == 'RADIO' || $taxonomy_obj->input_type == 'LINK' || $taxonomy_obj->input_type == 'RANGE' ) {
        if ( $has_fieldset ) {
			if ( $taxonomy_obj->htmlvar_name == 'distance' ) {
				//	$display_label = '';
			}
			$geodir_search_field_begin = '<div class="' . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . ' ' . $field_class . '">' . $display_label . '';
			$geodir_search_field_end   = '</div>';
		} else if ( $taxonomy_obj->input_type == 'RANGE' && ! empty( $taxonomy_obj->main_search ) ) {
			$field_class .= ' ' . ( $aui_bs5 ? ( $main_search ? '' : 'mb-3' ) : 'form-group' );
		}
    }

    if ( $has_fieldset ) {
        $field_class .= ' ' . ( $aui_bs5 ? 'mb-3' : 'form-group' );
        $wrap_attrs = geodir_search_conditional_field_attrs( $taxonomy_obj );
    }

    if ( ! empty( $terms ) ) {
        $range_expand = $taxonomy_obj->range_expand;
        $input_type    = $taxonomy_obj->input_type;

        $expand_search = 0;
        if ( ! empty( $taxonomy_obj->expand_search ) && ( $input_type == 'LINK' || $input_type == 'CHECK' || $input_type == 'RADIO' || $input_type == 'RANGE' ) ) {
            $expand_search = (int) $taxonomy_obj->expand_search;
        }

        $moreoption = '';
        if ( ! empty( $expand_search ) && $expand_search > 0 ) {
            if ( $range_expand ) {
                $moreoption = $range_expand;
            } else {
                $moreoption = 5;
            }
        }


        $classname = '';
        $increment = 1;

        echo $geodir_search_field_begin;

        $count = 0;
        foreach ( $terms as $term ) {

            if(is_array( $term ) && isset($term['value']) && $term['value']==''){ continue;}
            $count++;
            $custom_term = is_array( $term ) && ! empty( $term ) && isset( $term['label'] ) ? true : false;

            $option_label = $custom_term ? $term['label'] : false;
            $option_value = $custom_term ? $term['value'] : false;
            $optgroup     = $custom_term && ( $term['optgroup'] == 'start' || $term['optgroup'] == 'end' ) ? $term['optgroup'] : null;

            if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                $classname = 'collapse';
            }

            if ( $taxonomy_obj->field_type != 'categories' ) {
                if ( $custom_term ) {
                    $term          = (object) $option_value;
                    $term->term_id = $option_value;
                    $term->name    = $option_label;
                } else {
                    $select_arr = array();
                    if ( isset( $term ) && ! empty( $term ) ) {
                        $select_arr = explode( '/', $term );
                    }

                    $value         = $term;
                    $term          = (object) $term;
                    $term->term_id = $value;
                    $term->name    = $value;

                    if ( isset( $select_arr[0] ) && $select_arr[0] != '' && isset( $select_arr[1] ) && $select_arr[1] != '' ) {
                        $term->term_id = $select_arr[1];
                        $term->name    = $select_arr[0];

                    }
                }
            }

            $geodir_search_field_selected     = false;
            $geodir_search_field_selected_str = '';
            $geodir_search_custom_value_str   = '';
            if ( isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ) {
                if ( ! empty( $checkbox_fields ) && in_array( $taxonomy_obj->htmlvar_name, $checkbox_fields ) && ! empty( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ) {
                    $geodir_search_field_selected = true;
                } elseif ( isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) && is_array( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) && in_array( $term->term_id, stripslashes_deep( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ) ) {
                    $geodir_search_field_selected = true;
                }
            } elseif ( $taxonomy_obj->htmlvar_name == 'post_category' && ! isset( $_REQUEST['geodir_search'] ) && ! empty( $wp_query ) && ! empty( $wp_query->queried_object ) && ! empty( $wp_query->queried_object->term_id ) && $wp_query->queried_object->term_id == $term->term_id && $wp_query->is_main_query() ) {
                $geodir_search_field_selected = true;
            }
            if ( isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) && $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] != '' ) {

                $geodir_search_custom_value_str = isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ? stripslashes_deep( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) : '';
                if ( is_array( $geodir_search_custom_value_str ) ) {
                    $geodir_search_custom_value_str = array_map( 'esc_attr', $geodir_search_custom_value_str );
                } else {
                    $geodir_search_custom_value_str = esc_attr( $geodir_search_custom_value_str );
                }
            }

            $hierarchy_class = ' geodir-advs-v-' . sanitize_html_class( $term->term_id );
            if ( isset( $term->parent ) ) {
                $hierarchy_class .= ' geodir-advs-p-' . sanitize_html_class( $term->parent );

                if ( ! empty( $term->parent ) ) {
                    $hierarchy_class .= ' geodir-advs-child';
                }
            }

            switch ( $taxonomy_obj->input_type ) {
                case 'CHECK' :
                    if ( $custom_term && $optgroup != '' ) {
                        if ( $optgroup == 'start' ) {
                            echo '<li class="' . $classname . ' as-' . $classname . '-' . esc_attr( $taxonomy_obj->htmlvar_name ) . '">' . __( $term->name, 'geodirectory' )  . '</li>';
                        }
                    } else {
                        if ( $geodir_search_field_selected ) {
                            $geodir_search_field_selected_str = ' checked="checked" ';
                        }
                        echo '<div class="form-check mb-1 '.$classname.' as-'.$classname.'-'.$taxonomy_obj->htmlvar_name . $hierarchy_class . '"><input type="checkbox" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'" class="form-check-input" name="s' . esc_attr( $taxonomy_obj->htmlvar_name ) . '[]" ' . $geodir_search_field_selected_str . ' value="' . esc_attr( $term->term_id ) . '" /> <label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'" class="form-check-label text-muted">' . __( $term->name, 'geodirectory' ) . '</label></div>';
                        $increment ++;
                    }
                    break;
                case 'RADIO' :
                    if ( $custom_term && $optgroup != '' ) {
                        if ( $optgroup == 'start' ) {
                            echo '<li class="' . $classname . '">' . __( $term->name, 'geodirectory' )  . '</li>';
                        }
                    } else {
                        if ( $geodir_search_field_selected ) {
                            $geodir_search_field_selected_str = ' checked="checked" ';
                        }
                        echo '<div class="form-check mb-1 '.$classname.' as-'.$classname.'-'.$taxonomy_obj->htmlvar_name  . $hierarchy_class . '"><input type="radio" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'" class="form-check-input" name="s' . esc_attr( $taxonomy_obj->htmlvar_name ) . '[]" ' . $geodir_search_field_selected_str . ' value="' . esc_attr( $term->term_id ) . '" /> <label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'" class="form-check-label text-muted">' . __( $term->name, 'geodirectory' ) . '</label></div>';
                        $increment ++;
                    }
                    break;
                case 'SELECT' :
                    if ( $custom_term && $optgroup != '' ) {
                        if ( $optgroup == 'start' ) {
                            echo '<optgroup label="' . esc_attr( __( $term->name, 'geodirectory' )  ) . '">';
                        } else {
                            echo '</optgroup>';
                        }
                    } else {
                        if ( $geodir_search_field_selected ) {
                            $geodir_search_field_selected_str = ' selected="selected" ';
                        }
                        if($term->term_id!=''){
                            echo '<option value="' . esc_attr( $term->term_id ) . '" ' . $geodir_search_field_selected_str . ' >' . __( $term->name, 'geodirectory' )  . '</option>';
                            $increment ++;
                        }

                    }
                    break;
                case 'LINK' :
                    if ( $custom_term && $optgroup != '' ) {
                        if ( $optgroup == 'start' ) {
                            echo '<li ' . $classname . '> ' . __( $term->name, 'geodirectory' )  . '</li>';
                        }
                    } else {
                        echo '<div ' . $classname . $hierarchy_class . '><a href="' . geodir_search_field_page_url( $post_type, array( 's' . esc_attr( $taxonomy_obj->htmlvar_name ) . '[]' => urlencode( $term->term_id ) ) ) . '">' . __( $term->name, 'geodirectory' ) . '</a></div>';
                        $increment ++;
                    }
                    break;
                case 'RANGE': ############# RANGE VARIABLES ##########

                {
                    $search_starting_value_f = $taxonomy_obj->range_min;
                    $search_starting_value   = $taxonomy_obj->range_min;
                    $search_maximum_value    = $taxonomy_obj->range_max;
                    $search_diffrence        = $taxonomy_obj->range_step;

                    if ( empty( $search_starting_value ) ) {
                        $search_starting_value = 10;
                    }
                    if ( empty( $search_maximum_value ) ) {
                        $search_maximum_value = 50;
                    }
                    if ( empty( $search_diffrence ) ) {
                        $search_diffrence = 10;
                    }

                    $range_from_title  = $taxonomy_obj->range_from_title ? stripslashes( __( $taxonomy_obj->range_from_title, 'geodirectory' ) ) : '';
                    $range_to_title   = $taxonomy_obj->range_to_title ? stripslashes( __( $taxonomy_obj->range_to_title, 'geodirectory' ) ) : '';
                    $range_start = $taxonomy_obj->range_start;

                    if ( ! empty( $range_start ) ) {
                        $search_starting_value = $range_start;
                    }

                    if ( empty( $range_from_title ) ) {
                        $range_from_title = __( 'Less than', 'geodiradvancesearch' );
                    }
                    if ( empty( $range_to_title ) ) {
                        $range_to_title = __( 'More than', 'geodiradvancesearch' );
                    }

                    $j = $search_starting_value_f;
                    $k = 0;

                    $i                        = $search_starting_value_f;
                    $moreoption               = '';
                    $range_expand      = $taxonomy_obj->range_expand;
                    $expand_search            = $taxonomy_obj->expand_search;
                    if ( ! empty( $expand_search ) && $expand_search > 0 ) {
                        if ( $range_expand ) {
                            $moreoption = $range_expand;
                        } else {
                            $moreoption = 5;
                        }
                    }

                    if ( ! empty( $taxonomy_obj->main_search ) && $taxonomy_obj->search_condition == 'LINK' ) {
                         $taxonomy_obj->search_condition = 'SELECT';
                    }

                    switch ( $taxonomy_obj->search_condition ) {

                        case 'SINGLE':
                            $custom_value = isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ? stripslashes_deep( esc_attr( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ) : '';
                            ?>
                            <div class="<?php echo  esc_attr( $field_class ); ?>" data-argument="<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>"><?php echo $display_label; ?><input type="text" class="cat_input form-control w-100" name="s<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>" value="<?php echo esc_attr( $custom_value ); ?>" placeholder="<?php echo esc_attr( $field_label ); ?>"/></div> <?php
                            break;

                        case 'FROM':
                            $smincustom_value = isset($_REQUEST[ 'smin' . $taxonomy_obj->htmlvar_name ]) ? esc_attr( $_REQUEST[ 'smin' . $taxonomy_obj->htmlvar_name ] ) : '';
                            $smaxcustom_value = isset($_REQUEST[ 'smax' . $taxonomy_obj->htmlvar_name ]) ? esc_attr( $_REQUEST[ 'smax' . $taxonomy_obj->htmlvar_name ] ) : '';

                            $start_placeholder = apply_filters( 'gd_adv_search_from_start_ph_text', esc_attr( __( 'Start search value', 'geodiradvancesearch' ) ), $taxonomy_obj );
                            $end_placeholder   = apply_filters( 'gd_adv_search_from_end_ph_text', esc_attr( __( 'End search value', 'geodiradvancesearch' ) ), $taxonomy_obj );
                            ?>
                            <div class="<?php echo  esc_attr( $field_class ); ?>" data-argument="<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>"><?php echo $display_label; ?><div class='input-group'>
                            <input type='number' min="0" step="1"
                                   class='cat_input form-control <?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>'
                                   placeholder='<?php echo esc_attr( $start_placeholder ); ?>'
                                   name='smin<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>'
                                   value='<?php echo $smincustom_value; ?>'>
                            <input type='number' min="0" step="1"
                                   class='cat_input form-control <?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>'
                                   placeholder='<?php echo esc_attr( $end_placeholder ); ?>'
                                   name='smax<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>'
                                   value='<?php echo $smaxcustom_value; ?>'>
                            </div></div><?php
                            break;
                        case 'LINK':
                            echo '<div class="' . esc_attr( $field_class ) . '"><ul class="list-group">';
                            $link_serach_value = isset( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) ? @esc_attr( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) : '';
                            $increment        = 1;
                            while ( $i <= $search_maximum_value ) {
                                if ( $k == 0 ) {
                                    $value = $search_starting_value . '-Less';
                                    ?>
                                    <li class="list-group-item p-1 border-0 bg-transparent <?php if ( $link_serach_value == $value ) {
                                        echo 'active';
                                    } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                        echo 'more';
                                    } ?>"><a
                                            href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php echo $range_from_title . ' ' . $search_starting_value; ?></a>
                                    </li>
                                    <?php
                                    $k ++;
                                } else {
                                    if ( $i <= $search_maximum_value ) {
                                        $value = $j . '-' . $i;
                                        if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 ) {
                                            $display_value = $j;
                                            $value         = $j . '-Less';
                                        } else {
                                            $display_value = '';
                                        }
                                        ?>
                                        <li class="list-group-item p-1 border-0 bg-transparent <?php if ( $link_serach_value == $value ) {
                                            echo 'active';
                                        } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                            echo 'more';
                                        } ?>"><a
                                                href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php if ( $display_value ) {
                                                    echo $display_value;
                                                } else {
                                                    echo $value;
                                                } ?></a></li>
                                        <?php
                                    } else {


                                        $value = $j . '-' . $i;
                                        if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 ) {
                                            $display_value = $j;
                                            $value         = $j . '-Less';
                                        } else {
                                            $display_value = '';
                                        }

                                        ?>
                                        <li class="list-group-item p-1 border-0 bg-transparent <?php if ( $link_serach_value == $value ) {
                                            echo 'active';
                                        } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                            echo 'more';
                                        } ?>"><a
                                                href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php if ( $display_value ) {
                                                    echo $display_value;
                                                } else {
                                                    echo $value;
                                                } ?></a>
                                        </li>
                                        <?php
                                    }
                                    $j = $i;
                                }

                                $i = $i + $search_diffrence;

                                if ( $i > $search_maximum_value ) {
                                    if ( $j != $search_maximum_value ) {
                                        $value = $j . '-' . $search_maximum_value;
                                        ?>
                                    <li class="list-group-item p-1 border-0 bg-transparent <?php if ( $link_serach_value == $value ) {
                                        echo 'active';
                                    } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                        echo 'more';
                                    } ?>"><a
                                            href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php echo $value; ?></a>
                                        </li><?php }
                                    if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 && $j == $search_maximum_value ) {
                                        $display_value = $j;
                                        $value         = $j . '-Less';
                                        ?>
                                        <li class="list-group-item p-1 border-0 bg-transparent <?php if ( $link_serach_value == $value ) {
                                            echo 'active';
                                        } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                            echo 'more';
                                        } ?>"><a
                                                href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php if ( $display_value ) {
                                                    echo $display_value;
                                                } else {
                                                    echo $value;
                                                } ?></a>
                                        </li>
                                        <?php
                                    }

                                    $value = $search_maximum_value . '-More';

                                    ?>
                                    <li class="list-group-item p-1 border-0 bg-transparent <?php if ( $link_serach_value == $value ) {
                                        echo 'active';
                                    } ?><?php if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                        echo 'more';
                                    } ?>"><a
                                            href="<?php echo geodir_search_field_page_url( $post_type, array( 's' . $taxonomy_obj->htmlvar_name => urlencode( $value ) ) ); ?>"><?php echo $range_to_title . ' ' . $search_maximum_value; ?></a>

                                    </li>

                                    <?php
                                }

                                $increment ++;

                            }
                            echo '</ul></div>';
                            break;
                        case 'SELECT':

                            global $wpdb;
                            $cf =  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE post_type = %s and htmlvar_name=%s  ORDER BY sort_order", array(
                                $post_type,
                                $taxonomy_obj->htmlvar_name
                            ) ) ,ARRAY_A );

                            $is_price = false;
                            if($cf){
                                $extra_fields = maybe_unserialize($cf['extra_fields']);
                                if(isset($extra_fields['is_price']) && $extra_fields['is_price']){
                                    $is_price = true;
                                }
                            }

                            if ( $title != '' ) {
                                $select_default = __( $title, 'geodiradvancesearch' );
                            } else {
                                $select_default = ! empty( $taxonomy_obj->frontend_title ) ? stripslashes( __( $taxonomy_obj->frontend_title, 'geodirectory' ) ) : __( 'Select option', 'geodiradvancesearch' );
                            }

                            $custom_search_value = isset($_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ]) ? @esc_attr( $_REQUEST[ 's' . $taxonomy_obj->htmlvar_name ] ) : '';

                            ?>
                            <div class="<?php echo  esc_attr( $field_class ); ?>" data-argument="<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>"><?php echo $display_label; ?><select name="s<?php echo esc_attr( $taxonomy_obj->htmlvar_name ); ?>" class="cat_select form-control <?php echo ( $aui_bs5 ? 'form-select' : 'custom-select' ); ?>" id="<?php echo esc_attr( 'geodir_search_' . $taxonomy_obj->htmlvar_name ); ?>"<?php echo $aria_label; ?>>
                                    <option value=""><?php echo esc_attr( $select_default); ?></option><?php
                                    if ( $search_maximum_value > 0 ) {
                                        while ( $i <= $search_maximum_value ) {
                                            if ( $k == 0 ) {
                                                $value = $search_starting_value . '-Less';
                                                ?>
                                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $custom_search_value == $value, true ); ?>><?php echo $range_from_title . ' '; echo ($is_price ) ? geodir_currency_format_number($search_starting_value,$cf) :$search_starting_value; ?></option>
                                                <?php
                                                $k ++;
                                            } else {
                                                if ( $i <= $search_maximum_value ) {
                                                    $jo = ($is_price ) ? geodir_currency_format_number($j,$cf) : $j;
                                                    $io = ($is_price ) ? geodir_currency_format_number($i,$cf) : $i;
                                                    $value = $j . '-' . $i;
                                                    $valueo = $jo . '-' . $io;
                                                    if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 ) {
                                                        $display_value = $jo;
                                                        $value         = $j . '-Less';
                                                    } else {
                                                        $display_value = '';
                                                    }
                                                    ?>
                                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $custom_search_value == $value, true ); ?>><?php echo ( $display_value ? $display_value : $valueo ); ?></option>
                                                    <?php
                                                } else {
                                                    $jo = ($is_price ) ? geodir_currency_format_number($j,$cf) : $j;
                                                    $io = ($is_price ) ? geodir_currency_format_number($i,$cf) : $i;
                                                    $value = $j . '-' . $i;
                                                    $valueo = $jo . '-' . $io;
                                                    if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 ) {
                                                        $display_value = $jo;
                                                        $value         = $j . '-Less';
                                                    } else {
                                                        $display_value = '';
                                                    }
                                                    ?>
                                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $custom_search_value == $value, true ); ?>><?php echo ( $display_value ? $display_value : $valueo ); ?></option>
                                                    <?php
                                                }
                                                $j = $i;
                                            }

                                            $i = $i + $search_diffrence;

                                            if ( $i > $search_maximum_value ) {
                                                $jo = ($is_price ) ? geodir_currency_format_number($j,$cf) : $j;
                                                $io = ($is_price ) ? geodir_currency_format_number($i,$cf) : $i;
                                                $search_maximum_valueo = ($is_price ) ? geodir_currency_format_number($search_maximum_value,$cf) : $search_maximum_value;

                                                if ( $j != $search_maximum_value ) {
                                                    $value = $j . '-' . $search_maximum_value;
                                                    $valueo = $jo . '-' . $search_maximum_value;
                                                    ?>
                                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $custom_search_value == $value, true ); ?>><?php echo $valueo; ?></option>
                                                    <?php
                                                }
                                                if ( $search_diffrence == 1 && $taxonomy_obj->range_mode == 1 && $j == $search_maximum_value ) {
                                                    $display_value = $j;
                                                    $value         = $j . '-Less';
                                                    $valueo         = $jo . '-Less';
                                                    ?>
                                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $custom_search_value == $value, true ); ?>><?php echo ( $display_value ? $display_value : $valueo ); ?></option>
                                                    <?php
                                                }
                                                $value = $search_maximum_value . '-More';
                                                ?>
                                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $custom_search_value == $value, true ); ?>><?php echo $range_to_title . ' ' . $search_maximum_valueo; ?></option>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                </select></div>
                            <?php
                            break;
                        case 'RADIO':


                            $uom      = geodir_adv_search_distance_unit();
                            $dist_dif = $search_diffrence;

                            $htmlvar_name = $taxonomy_obj->htmlvar_name == 'distance' ? 'dist' : 's' . $taxonomy_obj->htmlvar_name;

                            for ( $i = $dist_dif; $i <= $search_maximum_value; $i = $i + $dist_dif ) :
                                $checked = '';
                                if ( isset( $_REQUEST[ $htmlvar_name ] ) && $_REQUEST[ $htmlvar_name ] == $i ) {
                                    $checked = 'checked="checked"';
                                }
                                if ( $increment > $moreoption && ! empty( $moreoption ) ) {
                                    $classname = 'collapse';
                                }
                                echo '<div class="form-check mb-1 '.$classname.' as-'.$classname.'-'.$taxonomy_obj->htmlvar_name.'"><input type="radio" class="form-check-input" name="' . esc_attr( $htmlvar_name ) . '" ' . $checked . ' value="' . $i . '" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $i . '"  /> <label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $i .'" class="form-check-label text-muted">' . __( 'Within', 'geodiradvancesearch' ) . ' ' . $i . ' ' . __( $uom, 'geodirectory' ) . '</label></div>';
                                $increment ++;
                            endfor;
                            break;


                    }
                }
                    #############Range search###############
                    break;

                case "DATE":


                    break;

                default:
                    if ( isset( $taxonomy_obj->field_type ) && $taxonomy_obj->field_type == 'checkbox' ) {
                        $field_type = $taxonomy_obj->field_type;

                        $checked = '';
                        if ( $geodir_search_custom_value_str == '1' ) {
                            $checked = 'checked="checked"';
                        }

                        echo '<li><input ' . $checked . ' type="' . $field_type . '" for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'" class="cat_input" name="s' . esc_attr( $taxonomy_obj->htmlvar_name ) . '"  value="1" /> <label for="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '-' . $count .'">' . __( 'Yes', 'geodiradvancesearch' ) . '</label></li>';

                    } elseif ( isset( $taxonomy_obj->field_type ) && $taxonomy_obj->field_type == 'business_hours' ) {
                        $options = GeoDir_Adv_Search_Business_Hours::business_hours_options( $field_label );

                        $field_value = geodir_search_get_field_search_param( 'open_now' );
                        $minutes = geodir_hhmm_to_bh_minutes( gmdate( 'H:i' ), gmdate( 'N' ) );

                        echo '<li><select data-minutes="' . $minutes . '" name="sopen_now" class="geodir-advs-open-now cat_select form-control ' . ( $aui_bs5 ? 'form-select' : 'custom-select' ) . '" id="geodir_search_open_now"' . $aria_label . '>';
                        foreach ( $options as $option_value => $option_label ) {
                            $selected = selected( $field_value == $option_value, true, false );

                            if ( $option_value == 'now' ) {
                                if ( ! $selected && ( $field_value === 0 || $field_value === '0' || ( ! empty( $field_value ) && $field_value > 0 ) ) && ! in_array( $field_value, array_keys( $options ) ) ) {
                                    $selected = selected( true, true, false );
                                }
                            }
                            echo '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>' . $option_label . '</option>';
                        }
                        echo '</select></li>';
                    } else {
                        if ( ! empty( $taxonomy_obj->main_search ) ) {
                            $display_label = '';
                        }
                        echo '<div class="' . esc_attr( $field_class ) . '"' . $wrap_attrs .'>' . $display_label . '<input type="text" id="geodir_search_' . esc_attr( $taxonomy_obj->htmlvar_name ) . '" class="cat_input form-control" name="s' . esc_attr( $taxonomy_obj->htmlvar_name ) . '" value="' . esc_attr( $geodir_search_custom_value_str ) . '" placeholder="' . esc_attr( $field_label ) . '"' . $aria_label . ' /></div>';
                    }
            }

        }

        if ( ! $has_fieldset ) {
            echo $geodir_search_field_end;
        }

        if ( ( $increment - 1 ) > $moreoption && ! empty( $moreoption ) && $moreoption > 0 ) {
            echo '<button onclick="if(jQuery(this).text()==\'' . __( 'More', 'geodiradvancesearch' ) . '\'){jQuery(this).text(\'' . __( 'Less', 'geodiradvancesearch' ) . '\')}else{jQuery(this).text(\'' . __( 'More', 'geodiradvancesearch' ) . '\')}" class="badge ' . ( $aui_bs5 ? 'bg-primary' : 'badge-primary' ) . '" type="button" data-' . ( $aui_bs5 ? 'bs-' : '' ) . 'toggle="collapse" data-' . ( $aui_bs5 ? 'bs-' : '' ) . 'target=".as-collapse-'.$taxonomy_obj->htmlvar_name.'" >' . __( 'More', 'geodiradvancesearch' ) . '</button>';
        }

        if ( $has_fieldset ) {
            echo $geodir_search_field_end;
        }
    }

    return ob_get_clean();
}

function geodir_search_field_page_url( $post_type, $args = array() ) {
	$_args = array( 'geodir_search' => 1 );
	if ( empty( $args['stype'] ) ) {
		$_args['stype'] = $post_type;
	}
	if ( ! isset( $args['s'] ) ) {
		$_args['s'] = '+';
	}
	$args = array_merge( $_args, $args );

	$search_args = apply_filters( 'geodir_search_field_page_url_args', $args, $post_type );

	$search_url = add_query_arg( $search_args , geodir_search_page_base_url() );

	return apply_filters( 'geodir_search_field_page_url', $search_url, $search_args, $args, $post_type );
}

/**
 * Search input time format.
 *
 * @since 2.2.1
 *
 * @param bool $picker If true returns in jQuery UI/Flatpickr format. Default False.
 * @return string Time format.
 */
function geodir_search_input_time_format( $picker = false ) {
	$time_format = geodir_time_format();

	$time_format = apply_filters( 'geodir_search_input_time_format', $time_format );

	if ( $picker ) {
		if ( geodir_design_style() ) {
			$time_format = geodir_date_format_php_to_aui( $time_format ); // AUI Flatpickr
		} else {
			$time_format = geodir_date_format_php_to_jqueryui( $time_format );
		}
	}

	return $time_format;
}

/**
 * Get the advance search filters default text.
 *
 * @since 2.2.2
 *
 * @return string Advance filters default text.
 */
function geodir_search_advance_filters_default_text() {
	return __( 'fas fa-cog', 'geodiradvancesearch' );
}

/**
 * Get the advance search filters button text.
 *
 * @since 2.2.2
 *
 * @return string Advance filters button text.
 */
function geodir_search_advance_filters_button_text() {
	$default_text = geodir_get_option( 'advs_search_filters_label' );

	if ( ! $default_text ) {
		$default_text = geodir_search_advance_filters_default_text();
	}

	/**
	 * Filter the advance filters button text in search form.
	 *
	 * @since 2.2.2
	 *
	 * @param string $default_text The current advance filters button text.
	 */
	$button_text = apply_filters( 'geodir_search_advance_filters_button_text', $default_text );

	return $button_text;
}

/**
 * Get the clear filters default text.
 *
 * @since 2.2.2
 *
 * @return string Clear filters default text.
 */
function geodir_search_clear_filters_default_text() {
	return __( 'Clear All', 'geodiradvancesearch' );
}

/**
 * Get the clear filters button text.
 *
 * @since 2.2.2
 *
 * @return string Clear filters button text.
 */
function geodir_search_clear_filters_button_text() {
	$default_text = geodir_get_option( 'advs_clear_filters_label' );

	if ( ! $default_text ) {
		$default_text = geodir_search_clear_filters_default_text();
	}

	/**
	 * Filter the clear filters button text in search form.
	 *
	 * @since 2.2.2
	 *
	 * @param string $default_text Clear filters button text.
	 */
	$button_text = apply_filters( 'geodir_search_clear_filters_button_text', $default_text );

	return $button_text;
}

/**
 * Check whether AJAX search request.
 *
 * @since 2.2.2
 */
function geodir_search_has_ajax_search( $check_page = false ) {
	$has_ajax_search = geodir_design_style() && (bool) geodir_get_option( 'advs_ajax_search' );

	if ( $has_ajax_search && $check_page && ! geodir_is_page( 'search' ) ) {
		$has_ajax_search = false;
	}

	return (bool) apply_filters( 'geodir_search_has_ajax_search', $has_ajax_search );
}

/**
 * Filter the search form action.
 *
 * @since 2.2.2
 */
function geodir_search_filter_form_action( $url ) {
	if ( geodir_design_style() && geodir_get_option( 'advs_ajax_search' ) && geodir_is_page( 'search' ) ) {
		$url = 'javascript:void(0);';
	}

	return $url;
}

/**
 * Handle AJAX search request.
 *
 * @since 2.2.2
 */
function geodir_search_handle_ajax_search_request() {
	global $wp_query, $geodir_query_object_id;

	if ( ! defined( 'GEODIR_AJAX_SEARCH' ) ) {
		return;
	}

	$post_type = geodir_get_current_posttype();

	if ( empty( $wp_query->posts ) ) {
		$wp_query->post_count = 1;
	}

	// Kadence : Force inline on AJAX request.
	if ( defined( 'KADENCE_BLOCKS_VERSION' ) ) {
		add_filter( 'kadence_blocks_force_render_inline_css_in_content', '__return_true', 10, 3 );
	}

	// Load Elementor bfi_thumb - WP Image Resizer in AJAX search.
	add_filter( 'image_downsize', function( $downsize, $id, $size ) {
		if ( ! empty( $size ) && is_array( $size ) && ! empty( $size['bfi_thumb'] ) && function_exists( 'bfi_image_resize_dimensions' ) && ! has_filter( 'image_resize_dimensions', 'bfi_image_resize_dimensions' ) ) {
			add_filter( 'image_resize_dimensions', 'bfi_image_resize_dimensions', 10, 5 );
		}
		return $downsize;
	}, 0, 3 );

	// gd_loop
	$loop = apply_filters( 'geodir_search_ajax_loop', '' );

	$loop_container = '';
	$loop_output = '';
	if ( geodir_search_ajax_pagi_type() && ! empty( $wp_query->query_vars['paged'] ) && $wp_query->query_vars['paged'] > 1 ) {
		if ( geodir_search_is_elementor_loop() ) {
			$element_id = ! empty( $_REQUEST['_ele_id'] ) ? sanitize_key( $_REQUEST['_ele_id'] ) : '';
			$loop_match = 'elementor-posts-container ';

			if ( $loop != '' && strpos( $loop, $loop_match ) !== false ) {
				$loop_container = '.elementor-element-' . $element_id . ' .elementor-posts-container';
				$loop_output = geodir_search_extract_container( trim( $loop ), $loop_match );
			}
		} else {
			$loop_match = 'geodir-listing-posts ';

			if ( $loop != '' && strpos( $loop, $loop_match ) !== false ) {
				$loop_container = '.geodir-loop-container .geodir-listing-posts';
				$loop_output = geodir_search_extract_container( trim( $loop ), $loop_match );
			}
		}
	} else {
		if ( geodir_search_is_elementor_loop() ) {
			$element_id = ! empty( $_REQUEST['_ele_id'] ) ? sanitize_key( $_REQUEST['_ele_id'] ) : '';
			$loop_match = 'elementor-posts-container ';

			if ( $loop != '' && strpos( $loop, $loop_match ) !== false ) {
				$loop_container = '.elementor-element-' . $element_id . ' .elementor-posts-container';
				$loop_output = geodir_search_extract_container( trim( $loop ), $loop_match );
			}
		} else {
			$loop_match = 'class="geodir-loop-container ';

			if ( $loop != '' && strpos( $loop, $loop_match ) !== false ) {
				$loop_container = '.geodir-loop-container';
				$loop_output = geodir_search_extract_container( trim( $loop ), $loop_match );
			}
		}
	}

	// gd_loop_paging
	$pagination = apply_filters( 'geodir_search_ajax_pagination', '' );

	$pagi_container = '.geodir-loop-paging-container';
	$pagi_match = 'class="geodir-loop-paging-container ';
	$has_pagination = ! empty( $_REQUEST['_max_pages'] ) && $_REQUEST['_max_pages'] > 1 ? true : false;

	if ( $has_pagination && $pagination != '' && strpos( $pagination, $pagi_match ) !== false ) {
		$pagi_output = geodir_search_extract_container( trim( $pagination ), $pagi_match );
	} else {
		$pagi_output = trim( $pagination );
	}

	$data = array(
		'loop' => array(
			'container' => $loop_container,
			'content' => $loop_output
		),
		'pagination' => array(
			'container' => $pagi_container,
			'content' => $pagi_output
		)
	);

	if ( geodir_get_option( 'advs_search_display_searched_params' ) ) {
		ob_start();
		geodir_search_show_searched_params( $post_type );
		$filters_output = ob_get_clean();
		$data['filters'] = array(
			'container' => 'gd-adv-search-labels',
			'content' => trim( $filters_output )
		);
	}

	// Prepare markers
	if ( ! empty( $_REQUEST['_gd_via'] ) && $_REQUEST['_gd_via'] == 'pagination' && ! empty( $wp_query->query_vars['paged'] ) && absint( $wp_query->query_vars['paged'] ) > 0 && (bool) geodir_get_option( 'advs_map_search' ) && geodir_get_option( 'advs_map_search_type' ) == 'all' ) {
		// Don't reload markers on pagination.
		$data['markers'] = '[]';
		$data['no_map_update'] = true;
	} else {
		$data['markers'] = geodir_search_prepare_markers( $wp_query, $post_type );
	}

	$data['posts'] = 0;
	$data['paged'] = 1;
	$data['max_num_pages'] = 1;
	if ( GeoDir_Query::is_gd_main_query( $wp_query ) ) {
		if ( ! empty( $wp_query->posts ) ) {
			$data['posts'] = count( $wp_query->posts );
		}
		if ( ! empty( $wp_query->query_vars['paged'] ) ) {
			$data['paged'] = absint( $wp_query->query_vars['paged'] );
		}
		if ( ! empty( $wp_query->max_num_pages ) ) {
			$data['max_num_pages'] = absint( $wp_query->max_num_pages );
		}
	}

	$geodir_query_object_id = (int) geodir_search_page_id();
	// Set SEO meta
	GeoDir_SEO::set_meta();

	$data['page_title_el'] = '.page .entry-title:first';
	$data['page_title'] = html_entity_decode( get_the_title( $geodir_query_object_id ), ENT_QUOTES, 'UTF-8' );
	$data['meta_title'] = esc_html( html_entity_decode( GeoDir_SEO::get_title(), ENT_QUOTES, 'UTF-8' ) );

	$data = apply_filters( 'geodir_search_ajax_search_data', $data );

	$geodir_query_object_id = NULL;

	wp_send_json_success( $data );
}

/**
 * Get the AJAX search data.
 *
 * @since 2.2.2
 */
function geodir_search_ajax_search_data( $data ) {
	$default_near_text = geodir_get_option('search_default_near_text');

	if ( ! $default_near_text ) {
		$default_near_text = geodir_get_search_default_near_text();
	}

	if ( isset( $_REQUEST['snear'] ) && $_REQUEST['snear'] != '' ) {
		$near = esc_attr( stripslashes( $_REQUEST['snear'] ) );
	} else {
		$near = '';
	}

	$near = apply_filters( 'geodir_search_near_text', $near, $default_near_text );

	$data['near'] = $near;

	return $data;
}

/**
 * Set the AJAX search loop.
 *
 * @since 2.2.2
 */
function geodir_search_ajax_loop() {
	// Elementor archive-posts
	if ( geodir_search_is_elementor_loop() ) {
		$output = '';
		$template_id = ! empty( $_REQUEST['_ele_tmpl'] ) ? absint( $_REQUEST['_ele_tmpl'] ) : 0;
		$element_id = ! empty( $_REQUEST['_ele_id'] ) ? geodir_clean( $_REQUEST['_ele_id'] ) : 0;
		$element_data = geodir_search_elementor_get_args( $template_id, $element_id );
		$element_args = array();
		$element_type = ElementorPro\Plugin::elementor()->widgets_manager->get_widget_types( 'archive-posts' );
		$args = array_merge( $element_type->get_default_args(), $element_args );
		$element_class = $element_type->get_class_name();

		try {
			$element = new $element_class( $element_data, $args );
			ob_start();
			$element->print_element();
			$output = ob_get_clean();
		} catch ( \Exception $e ) {
		}

		if ( ! empty( $output ) ) {
			return $output;
		}
	}

	// gd_loop
	$attrs = array( 'shadow', 'rounded_size', 'rounded', 'border', 'pl', 'pb', 'pr', 'pt', 'ml', 'mb', 'mr', 'mt', 'bg', 'card_shadow', 'card_border', 'column_gap', 'row_gap', 'layout', 'skin_id', 'skin_column_gap', 'skin_row_gap' );

	$_attrs = '';

	if ( ! empty( $_REQUEST['_gd_loop'] ) && is_array( $_REQUEST['_gd_loop'] ) ) {
		foreach ( $attrs as $attr ) {
			if ( isset( $_REQUEST['_gd_loop'][ $attr ] ) && $_REQUEST['_gd_loop'][ $attr ] !== '' ) {
				$_attrs .= ' ' . $attr . '="' . esc_attr( geodir_clean( $_REQUEST['_gd_loop'][ $attr ] ) ) . '"';
			}
		}
	}

	return do_shortcode('[gd_loop' . $_attrs . ']');
}

/**
 * AJAX search pagination.
 *
 * @since 2.2.2
 */
function geodir_search_ajax_pagination() {
	$attrs = array( 'shadow', 'rounded_size', 'rounded', 'border', 'pl', 'pb', 'pr', 'pt', 'ml', 'mb', 'mr', 'mt', 'bg', 'mid_size', 'show_advanced' );

	$_attrs = '';

	if ( ! empty( $_REQUEST['_gd_pagi'] ) && is_array( $_REQUEST['_gd_pagi'] ) ) {
		foreach ( $attrs as $attr ) {
			if ( isset( $_REQUEST['_gd_pagi'][ $attr ] ) && $_REQUEST['_gd_pagi'][ $attr ] !== '' ) {
				$_attrs .= ' ' . $attr . '="' . esc_attr( sanitize_text_field( $_REQUEST['_gd_pagi'][ $attr ] ) ) . '"';
			}
		}
	}

	add_filter( 'geodir_pagination_args', 'geodir_search_filter_pagination_args', 999999, 1 );

	$pagination = do_shortcode('[gd_loop_paging' . $_attrs . ']');

	return $pagination;
}

function geodir_search_filter_pagination_args( $pagination_args ) {
	$pagination_args['base'] = '%_%';
	$pagination_args['format'] = '#%#%#';

	return $pagination_args;
}

/**
 * Extract the container from HTML.
 *
 * @since 2.2.2
 */
function geodir_search_extract_container( $content, $match = 'class="geodir-loop-container ' ) {
	if ( strpos( $content, $match ) !== false ) {
		preg_match_all( '~<div[^>]*>(.*)</div>~is', $content, $matches );

		if ( ! empty( $matches[1][0] ) ) {
			$content = geodir_search_extract_container( $matches[1][0], $match );
		} else {
			$content = '';
		}
	}

	return $content;
}

function geodir_search_set_loop_params( $output, $instance, $args, $super_duper ) {
	if ( $output != '' && ! empty( $super_duper ) && ! empty( $super_duper->options['base_id'] ) && in_array( $super_duper->options['base_id'], array( 'gd_loop', 'gd_loop_paging' ) ) && ! empty( $instance ) ) {
		if ( ! defined( 'GEODIR_AJAX_SEARCH' ) && geodir_search_has_ajax_search() && geodir_is_page( 'search' ) ) {
			$class = $super_duper->options['base_id'] == 'gd_loop_paging' ? 'geodir-pagi-attrs' : 'geodir-loop-attrs';
			$div = '<div class="' . $class . '" style="display:none!important;"';
			foreach ( $instance as $key => $value ) {
				if ( $key && $value !== '' ) {
					$div .= ' data-gdl_' . strip_tags( $key ) . '="' . esc_attr( $value ) . '"';
				}
			}
			$div .= '></div>';
			$output .= $div;
		}
	}
	return $output;
}

/**
 * Get the update ajax search results button default text.
 *
 * @since 2.2.2
 *
 * @return string Update results button default text.
 */
function geodir_search_update_results_default_text() {
	return __( 'fas fa-sync', 'geodiradvancesearch' );;
}

/**
 * Get the update results button text.
 *
 * @since 2.2.2
 *
 * @return string Update results button text.
 */
function geodir_search_update_results_button_text() {
	$button_text = geodir_get_option( 'advs_update_results_label' );

	if ( ! $button_text ) {
		$button_text = geodir_search_update_results_default_text();
	}

	/**
	 * Filter the update results button text in search form.
	 *
	 * @since 2.2.2
	 *
	 * @param string $button_text Update results button text.
	 */
	$button_text = apply_filters( 'geodir_search_update_results_button_text', $button_text );

	return $button_text;
}

/**
 * Get the update results button content.
 *
 * @since 2.2.2
 *
 * @return string Update results button content.
 */
function geodir_search_update_results_button_content() {
	$button_text = geodir_search_update_results_button_text();

	if ( geodir_is_fa_icon( $button_text ) ) {
		$button_content = '<i class="' . esc_attr( $button_text ) . '" aria-hidden="true"></i><span class="sr-only visually-hidden">' . __( 'Update Results', 'geodiradvancesearch' ). '</span>';
	} else {
		$button_content = __( $button_text, 'geodiradvancesearch' );
	}

	/**
	 * Filter the update results button content in search form.
	 *
	 * @since 2.2.2
	 *
	 * @param string $button_content Update results button content.
	 */
	$button_content = apply_filters( 'geodir_search_update_results_button_content', $button_content, $button_text );

	return $button_content;
}

function geodir_search_ajax_search_type() {
	$search_type = geodir_get_option( 'advs_search_type' );

	if ( $search_type != 'auto' ) {
		$search_type = 'onchange';
	}

	/**
	 * Filter the AJAX update results type.
	 *
	 * @since 2.2.2
	 *
	 * @param string $search_type AJAX search type.
	 */
	$search_type = apply_filters( 'geodir_search_ajax_search_type', $search_type );

	return $search_type;
}

function geodir_search_ajax_search_button_text( $button_text ) {
	if ( geodir_search_has_ajax_search( true ) ) {
		$button_text = __( geodir_search_update_results_button_text(), 'geodiradvancesearch' );
	}

	return $button_text;
}

function geodir_search_prepare_markers( $_wp_query, $post_type ) {
	global $wpdb, $geodir_icon_basedir, $geodir_rest_cache_icons, $geodir_map_clauses, $geodir_map_clauses_r;

	if ( ! empty( $_wp_query->request ) && (bool) geodir_get_option( 'advs_map_search' ) && geodir_get_option( 'advs_map_search_type' ) == 'all' ) {
		$table =  geodir_db_cpt_table( $post_type );

		$sql = $_wp_query->request;

		$replace = "{$wpdb->posts}.ID, {$wpdb->posts}.post_title, {$wpdb->posts}.post_status, {$table}.default_category, {$table}.latitude, {$table}.longitude";

		if ( ! empty( $geodir_map_clauses_r ) ) {
			if ( ! empty( $geodir_map_clauses_r['limits'] ) ) {
				$sql = str_replace( $geodir_map_clauses_r['limits'], "", $sql );
			}

			if ( ! empty( $geodir_map_clauses_r['groupby'] ) ) {
				$sql = str_replace( "GROUP BY " . $geodir_map_clauses_r['groupby'], "GROUP BY {$wpdb->posts}.ID", $sql );
			}

			if ( ! empty( $geodir_map_clauses_r['orderby'] ) ) {
				$sql = str_replace( "ORDER BY " . $geodir_map_clauses_r['orderby'], "", $sql );
			}

			if ( ! empty( $geodir_map_clauses_r['fields'] ) ) {
				$sql = str_replace( "SQL_CALC_FOUND_ROWS " . $geodir_map_clauses_r['distinct'] . " " . $geodir_map_clauses_r['fields'], $replace, $sql );
			}
		}

		if ( ! empty( $geodir_map_clauses ) ) {
			if ( ! empty( $geodir_map_clauses['limits'] ) ) {
				$sql = str_replace( $geodir_map_clauses['limits'], "", $sql );
			}

			if ( ! empty( $geodir_map_clauses['groupby'] ) ) {
				$sql = str_replace( "GROUP BY " . $geodir_map_clauses['groupby'], "GROUP BY {$wpdb->posts}.ID", $sql );
			}

			if ( ! empty( $geodir_map_clauses['orderby'] ) ) {
				$sql = str_replace( "ORDER BY " . $geodir_map_clauses['orderby'], "", $sql );
			}

			if ( ! empty( $geodir_map_clauses['fields'] ) ) {
				$sql = str_replace( "SQL_CALC_FOUND_ROWS " . $geodir_map_clauses['distinct'] . " " . $geodir_map_clauses['fields'], $replace, $sql );
			}
		}

		// Remove limit to show all markers.
		if ( ( ! empty( $geodir_map_clauses_r ) || ! empty( $geodir_map_clauses ) ) && empty( $geodir_map_clauses_r['limits'] ) && empty( $geodir_map_clauses['limits'] ) && strpos( $sql, 'LIMIT ' ) !== false ) {
			$posts_per_page = ! empty( $_wp_query->query_vars['posts_per_page'] ) ? absint( $_wp_query->query_vars['posts_per_page'] ) : 0;

			if ( $posts_per_page > 0 ) {
				$paged = ! empty( $_wp_query->query_vars['paged'] ) ? absint( $_wp_query->query_vars['paged'] ) : 1;
				if ( $paged < 1 ) {
					$paged = 1;
				}
				$limit = "LIMIT " . ( ( $paged - 1 ) * $posts_per_page ) . ", " . $posts_per_page;
				$sql = str_replace( $limit, "", $sql );
			}
		}

		$sql = apply_filters( 'geodir_search_map_markers_sql', $sql, $_wp_query );

		$posts = $wpdb->get_results( $sql );
	} else {
		$posts = $_wp_query->posts;
	}
	$request = array_merge( $_wp_query->query_vars, array( 'post_type' => $post_type ) );

	$response = array();

	if ( ! empty( $posts ) ) {
		$wp_upload_dir = wp_upload_dir();
		$statuses = geodir_get_post_stati( 'map', $request );

		$geodir_icon_basedir = $wp_upload_dir['basedir'];
		if ( empty( $geodir_rest_cache_icons ) ) {
			$geodir_rest_cache_icons = array();
		}

		$items = array();
		$_items = array();
		foreach ( $posts as $item ) {
			if ( ! empty( $item->latitude ) && ! empty( $item->longitude ) ) {
				if ( is_array( $statuses ) && ! in_array( $item->post_status, $statuses ) || in_array( $item->ID, $_items ) ) {
					continue;
				}

				$_items[] = $item->ID;
				$items[] = geodir_search_prepare_marker( $item, $request );
			}
		}

		if ( ! empty( $items ) ) {
			$response[ 'total' ] = count( $items );
			$response[ 'baseurl' ] = $wp_upload_dir['baseurl'];
			$response[ 'content_url' ] = trailingslashit( WP_CONTENT_URL );
			$response[ 'icons' ] = $geodir_rest_cache_icons;
			$response[ 'items' ] = $items;
		}
	}

	if ( empty( $response ) ) {
		$response = '[]';
	}

	return $response;
}

function geodir_search_prepare_marker( $item, $request = array() ) {
	global $geodir_icon_basedir, $geodir_rest_cache_marker, $geodir_rest_cache_icons;

	if ( empty( $geodir_rest_cache_marker ) ) {
		$geodir_rest_cache_marker = array();
	}

	$default_category = ! empty( $item->default_category ) ? $item->default_category : '';

	$post_title = $item->post_title;

	$response = array();
	$response['m'] = (string) absint( $item->ID );
	$response['lt'] = $item->latitude;
	$response['ln'] = $item->longitude;
	$response['t'] = $post_title;

	$icon_id = ! empty( $default_category ) ? absint( $default_category ) : 'd'; // d = default

	if ( empty( $geodir_rest_cache_icons[ $icon_id ] ) ) {
		$icon_url 		= '';
		$icon_width 	= 36;
		$icon_height 	= 45;

		if ( ! empty( $geodir_rest_cache_marker ) && ! empty( $geodir_rest_cache_marker[ $default_category ]['i'] ) ) {
			$icon_url 	= $geodir_rest_cache_marker[ $default_category ]['i'];
		} else {
			$icon_url = geodir_get_cat_icon( $default_category, false, true );
			if ( empty( $icon_url ) ) {
				$icon_id = 'd';
				$icon_url = GeoDir_Maps::default_marker_icon( false );
			}

			if ( $default_category ) {
				$geodir_rest_cache_marker[ $default_category ]['i'] = $icon_url;
			}
		}

		if ( ! empty( $icon_url ) ) {
			if ( ! empty( $geodir_rest_cache_marker ) && ! empty( $geodir_rest_cache_marker[ $icon_url ]['w'] ) ) {
				$icon_width 	= $geodir_rest_cache_marker[ $icon_url ]['w'];
				$icon_height 	= $geodir_rest_cache_marker[ $icon_url ]['h'];
			} else {
				$icon_size = GeoDir_Maps::get_marker_size( trailingslashit( $geodir_icon_basedir ) . trim( $icon_url, '/\\' ) );
				if ( ! empty( $icon_size ) ) {
					$icon_width 	= $icon_size['w'];
					$icon_height 	= $icon_size['h'];
				}

				$geodir_rest_cache_marker[ $icon_url ]['w'] = $icon_width;
				$geodir_rest_cache_marker[ $icon_url ]['h'] = $icon_height;
			}
		}

		$geodir_rest_cache_icons[ $icon_id ] = array(
			'i' => $icon_url,
			'w' => $icon_width,
			'h' => $icon_height
		);
	}
	$response['i'] 	= $icon_id;

	/**
	 * Filters a marker data returned from the API.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param object           $item     The original marker data.
	 * @param WP_REST_Request  $request  Request used to generate the response.
	 */
	return apply_filters( 'geodir_rest_prepare_marker', $response, $item, $request );
}

function geodir_search_ajax_pagi_type() {
	$pagination = geodir_get_option( 'advs_pagination' );

	if ( $pagination == 'loadmore' || $pagination == 'infinite' ) {
		$pagi_type = $pagination;
	} else {
		$pagi_type = '';
	}

	/**
	 * Filter the AJAX pagination type.
	 *
	 * @since 2.2.2
	 *
	 * @param string $pagi_type AJAX pagination type.
	 */
	$pagi_type = apply_filters( 'geodir_search_ajax_pagi_type', $pagi_type );

	return $pagi_type;
}

/**
 * Filter the template parameters.
 *
 * @since 2.2.2
 *
 * @param array $template_params Template parameters.
 * @param array $instance Settings for the widget instance.
 * @param array $widget_args Widget display arguments.
 * @return array Template parameters.
 */
function geodir_search_form_template_params( $template_params, $instance, $widget_args ) {
	$design_style = geodir_design_style();

	if ( $design_style && ! empty( $instance['show'] ) && $instance['show'] == 'advanced' ) {
		$template_params['template'] = $design_style . "/search-bar/advanced-filters.php";
		$template_params['default_path'] = geodir_search_templates_path();
	}

	return $template_params;
}

function geodir_search_is_elementor_loop() {
	return ( defined( 'ELEMENTOR_PRO_VERSION' ) && ! empty( $_REQUEST['_ele_tmpl'] ) && ! empty( $_REQUEST['_ele_id'] ) );
}

function geodir_search_elementor_get_args( $template_id, $element_id ) {
	global $geodir_el_archive_posts;

	$elementor_data = array();
	$elementor_meta = get_post_meta( $template_id, '_elementor_data', true );

	if ( is_string( $elementor_meta ) && ! empty( $elementor_meta ) ) {
		$elementor_meta = json_decode( $elementor_meta, true );
	}

	if ( ! empty( $elementor_meta ) && ! is_scalar( $elementor_meta ) ) {
		$geodir_el_archive_posts = array();

		foreach ( $elementor_meta AS $k => $data ) {
			geodir_search_elementor_parse_args( $data );
		}

		if ( ! empty( $geodir_el_archive_posts ) ) {
			if ( isset( $geodir_el_archive_posts[ $element_id ] ) ) {
				$elementor_data = $geodir_el_archive_posts[ $element_id ];
			} else {
				$geodir_el_archive_posts = array_values( $geodir_el_archive_posts );
				$elementor_data = $geodir_el_archive_posts[0];
			}
		}
	}

	return $elementor_data;
}

function geodir_search_elementor_parse_args( $data ) {
	global $geodir_el_archive_posts;

	if ( ! empty( $data['widgetType'] ) && $data['widgetType'] == 'archive-posts' && ! empty( $data['settings']['_skin'] ) && $data['settings']['_skin'] == 'gd_archive_custom' ) {
		$geodir_el_archive_posts[ $data['id'] ] = $data;
	} elseif ( ! empty( $data['elements'] ) ) {
		foreach ( $data['elements'] as $k => $_data ) {
			geodir_search_elementor_parse_args( $_data );
		}
	}
}