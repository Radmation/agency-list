<?php
/**
 * AGENCYLIST Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package agencylist
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_AGENCYLIST_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {
	// wp_enqueue_style( 'agencylist-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_AGENCYLIST_VERSION, 'all' );
	wp_enqueue_style( 'agencylist-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), rand(111,9999), 'all' );
	
	wp_enqueue_script('agencylist-theme-js', get_stylesheet_directory_uri() . '/scripts.js', array('jquery'), CHILD_THEME_AGENCYLIST_VERSION);
}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

/**
 * @since   1.0.0
 * @version 1.0.0
 *
 * @global \WP_Post     $post The current post object.
 *
 * @param \WP_Comment   $comment    The comment object.
 * @param string|array  $args {
 *     Optional. Formatting options.
 *
 *      @type object $walker Instance of a Walker class to list comments. Default null.
 *      @type int $max_depth The maximum comments depth. Default empty.
 *      @type string $style The style of list ordering. Default 'ul'. Accepts 'ul', 'ol'.
 *      @type string $callback Callback function to use. Default null.
 *      @type string $end -callback      Callback function to use at the end. Default null.
 *      @type string $type Type of comments to list.
 *                                     Default 'all'. Accepts 'all', 'comment', 'pingback', 'trackback', 'pings'.
 *      @type int $page Page ID to list comments for. Default empty.
 *      @type int $per_page Number of comments to list per page. Default empty.
 *      @type int $avatar_size Height and width dimensions of the avatar size. Default 32.
 *      @type string $reverse_top_level Ordering of the listed comments. Default null. Accepts 'desc', 'asc'.
 *      @type bool $reverse_children Whether to reverse child comments in the list. Default null.
 *      @type string $format How to format the comments list.
 *                                     Default 'html5' if the theme supports it. Accepts 'html5', 'xhtml'.
 *      @type bool $short_ping Whether to output short pings. Default false.
 *      @type bool $echo Whether to echo the output or return it. Default true.
 * }
 *
 * @param int   $depth  Depth of comment.
 */
function dws_agency_comment($comment, $args, $depth) {
    global $post;

    if (in_array($comment->comment_type, array('pingback', 'trackback'))) {
        return; // ignore automatic comments, we're interested in bona fide reviews "only"
    }

    $GLOBALS['comment'] = $comment;
    $GLOBALS['current_user'] = new WP_User($comment->user_id); // shortcut for Elementor's widgets

    if (boolval($comment->comment_approved)): ?>

    <li <?php comment_class( 'dws-geodir-comment' ); ?> id="li-comment-<?php comment_ID(); ?>">
        <article id="comment-<?php comment_ID(); ?>" class="comment">
            <?php echo do_shortcode('[elementor-template id="4457"]'); ?>
        </article>
    </li>

    <?php endif;

    $GLOBALS['current_user'] = null;
}

add_filter('wp_list_comments_args', function($args) {
    $args['callback'] = 'dws_agency_comment';
    return $args;
}, PHP_INT_MAX); // doing this here instead of in the template lets GeoDirectory run it's argument parsing logic on hook priority 10

add_filter('comment_post_redirect', function($location, /** @var \WP_Comment $comment */ $comment) {
    return get_permalink( $comment->comment_post_ID );
}, 10, 2);

add_action('geodir_permalinks_post_rewrite_rule', function($cpt, $post_type, $gd_permalinks, $regex_part, $redirect, $after) {
    $gd_permalinks->add_rewrite_rule('^' . $post_type['rewrite']['slug'] . '/(.?.+?)/comment-page-([0-9]{1,})/?$',  'index.php?post_type=gd_place&gd_place=$matches[1]&cpage=$matches[2]', 'top');
}, 10, 6);


add_filter('geodir_custom_field_input_text', function ($html,$cf) {
    // only affect the front-end
    if (is_admin() || boolval($cf['for_admin_use'])) { return $html; }

    // ignore other fields too
    if (in_array($cf['name'], array('package_id'))) { return $html; }

    // start adjusted output
    ob_start();

    $value = geodir_get_cf_value($cf);
    $type = $cf['type'];
    $fix_html5_decimals = '';

    //number and float validation $validation_pattern
    if(isset($cf['data_type']) && $cf['data_type']=='INT'){$type = 'number'; $fix_html5_decimals =' lang="EN" ';}
    elseif(isset($cf['data_type']) && $cf['data_type']=='FLOAT'){$type = 'float';$fix_html5_decimals =' lang="EN" ';}

    //validation
    if (isset($cf['validation_pattern']) && $cf['validation_pattern']) {
        $validation = ' pattern="' . $cf['validation_pattern'] . '" ';
    } else{ $validation=''; }

    // validation message
    if (isset($cf['validation_msg']) && $cf['validation_msg']) {
        $validation_msg = 'title="' . $cf['validation_msg'] . '"';
    } else{$validation_msg='';}

    ?>

    <div id="<?php echo $cf['name'];?>_row"
         class="geodir_form_row clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_text <?php if ($cf['is_required']) echo 'required_field';?>">

        <label for="<?php echo esc_attr( $cf['name'] ); ?>">
            <?php
                $frontend_title = __($cf['frontend_title'], 'geodirectory');
                echo (trim($frontend_title)) ? $frontend_title : '&nbsp;';
            ?>

            <?php if ($cf['is_required']) echo '<span>*</span>';?>

            <?php if (!empty(($cf['desc']))): ?>
                <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php _e($cf['desc'], 'geodirectory');?>"></i>
            <?php endif; ?>
        </label>

        <div id="<?php echo $cf['name']; ?>_input_wrapper" class="dws_geodir_input_wrapper dws_geodir_<?php echo $cf['name']; ?>">
            <input type="<?php echo $type; ?>" field_type="<?php echo $type; ?>"
                   name="<?php echo $cf['name']; ?>" id="<?php echo $cf['name']; ?>" class="geodir_textfield dws_geodir_textfield"
                   value="<?php echo esc_attr(stripslashes($value));?>"
                   placeholder="<?php echo (!empty($cf['placeholder_value'])) ? esc_html__($cf['placeholder_value'], 'geodirectory') : 'Enter Data' ?>"
                <?php echo $fix_html5_decimals; ?>
                <?php echo $validation;echo $validation_msg;?> />

            <?php if ($cf['is_required']) { ?>
                <span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="<?php _e($cf['required_msg'], 'geodirectory'); ?>" data-toggle="tooltip" style="display: none;"></span>
            <?php } ?>
        </div>

    </div>

    <?php return ob_get_clean();
}, 100, 2);
add_filter('geodir_custom_field_input_url', function ($html, $cf) {
    // only affect the front-end
    if (is_admin() || boolval($cf['for_admin_use'])) { return $html; }

    // start adjusted output
    ob_start();

    $value = geodir_get_cf_value($cf);
    if ($value == $cf['default']) { $value = ''; }

    //validation
    if (isset($cf['validation_pattern']) && $cf['validation_pattern']) {
        $validation = ' pattern="'.$cf['validation_pattern'].'" ';
    } else{$validation='';}

    // validation message
    if (isset($cf['validation_msg']) && $cf['validation_msg']) {
        $validation_msg = $cf['validation_msg'];
    } else{$validation_msg = __('Please enter a valid URL including http://', 'geodirectory');}

    ?>

    <div id="<?php echo $cf['name'];?>_row"
         class="geodir_form_row clearfix gd-fieldset-details gd-fieldset-details dws_gd_form_row dws_gd_form_row_url <?php if ($cf['is_required']) echo 'required_field';?>">

        <label for="<?php echo esc_attr( $cf['name'] ); ?>">
            <?php
                $frontend_title = __($cf['frontend_title'], 'geodirectory');
                echo (trim($frontend_title)) ? $frontend_title : '&nbsp;';
            ?>

            <?php if ($cf['is_required']) echo '<span>*</span>';?>

            <?php if (!empty(($cf['desc']))): ?>
                <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php _e($cf['desc'], 'geodirectory');?>"></i>
            <?php endif; ?>
        </label>

        <div id="<?php echo $cf['name']; ?>_input_wrapper" class="dws_geodir_input_wrapper">
            <input type="url" field_type="<?php echo $cf['type'];?>"
                   name="<?php echo $cf['name'];?>" id="<?php echo $cf['name'];?>" class="geodir_textfield"
                   placeholder="<?php echo (!empty($cf['placeholder_value'])) ? esc_html__($cf['placeholder_value'], 'geodirectory') : 'Enter Data' ?>"
                   value="<?php echo esc_attr(stripslashes($value));?>"
                   oninvalid="setCustomValidity('<?php echo esc_attr($validation_msg); ?>')"
                   onchange="try{setCustomValidity('')}catch(e){}"
                   <?php echo $validation;echo $validation_msg; ?>
            />

            <?php if ($cf['is_required']) { ?>
                <span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="<?php _e($cf['required_msg'], 'geodirectory'); ?>" data-toggle="tooltip" style="display: none;"></span>
            <?php } ?>
        </div>

    </div>

    <?php return ob_get_clean();
}, 100, 2);
add_filter('geodir_custom_field_input_email', function ($html, $cf) {
    // only affect the front-end
    if (is_admin() || boolval($cf['for_admin_use'])) { return $html; }

    // start adjusted output
    ob_start();

    $value = geodir_get_cf_value($cf);
    if ($value == $cf['default']) { $value = ''; }

    //validation
    if (isset($cf['validation_pattern']) && $cf['validation_pattern']) {
        $validation = ' pattern="'.$cf['validation_pattern'].'" ';
    } else{$validation='';}

    // validation message
    if (isset($cf['validation_msg']) && $cf['validation_msg']) {
        $validation_msg = 'title="'.$cf['validation_msg'].'"';
    } else{$validation_msg='';}

    ?>

    <div id="<?php echo $cf['name'];?>_row"
         class="geodir_form_row clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_email <?php if ($cf['is_required']) echo 'required_field';?>">

        <label for="<?php echo esc_attr( $cf['name'] ); ?>">
            <?php
                $frontend_title = __($cf['frontend_title'], 'geodirectory');
                echo (trim($frontend_title)) ? $frontend_title : '&nbsp;';
            ?>

            <?php if ($cf['is_required']) echo '<span>*</span>';?>

            <?php if (!empty(($cf['desc']))): ?>
                <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php _e($cf['desc'], 'geodirectory');?>"></i>
            <?php endif; ?>
        </label>

        <div id="<?php echo $cf['name']; ?>_input_wrapper" class="dws_geodir_input_wrapper">
            <input type="email" field_type="<?php echo $cf['type'];?>"
                   name="<?php  echo $cf['name'];?>" id="<?php echo $cf['name'];?>" class="geodir_textfield"
                   placeholder="<?php echo (!empty($cf['placeholder_value'])) ? esc_html__($cf['placeholder_value'], 'geodirectory') : 'Enter Data' ?>"
                   value="<?php echo esc_attr(stripslashes($value));?>" <?php echo $validation; echo $validation_msg; ?> />

            <?php if ($cf['is_required']) { ?>
                <span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="<?php _e($cf['required_msg'], 'geodirectory'); ?>" data-toggle="tooltip" style="display: none;"></span>
            <?php } ?>
        </div>
    </div>

    <?php return ob_get_clean();
}, 100, 2);
add_filter('geodir_custom_field_input_phone', function ($html, $cf) {
    // only affect the front-end
    if (is_admin() || boolval($cf['for_admin_use'])) { return $html; }

    // start adjusted output
    ob_start();

    $value = geodir_get_cf_value($cf);
    if ($value == $cf['default']) { $value = ''; }

    //validation
    if (isset($cf['validation_pattern']) && $cf['validation_pattern']) {
        $validation = ' pattern="'.$cf['validation_pattern'].'" ';
    } else{$validation='';}

    // validation message
    if (isset($cf['validation_msg']) && $cf['validation_msg']) {
        $validation_msg = 'title="'.$cf['validation_msg'].'"';
    } else{$validation_msg='';}

    ?>

    <div id="<?php echo $cf['name'];?>_row"
         class="geodir_form_row clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_phone <?php if ($cf['is_required']) echo 'required_field';?>">

        <label for="<?php echo esc_attr( $cf['name'] ); ?>">
            <?php
                $frontend_title = __($cf['frontend_title'], 'geodirectory');
                echo (trim($frontend_title)) ? $frontend_title : '&nbsp;';
            ?>

            <?php if ($cf['is_required']) echo '<span>*</span>';?>

            <?php if (!empty(($cf['desc']))): ?>
                <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php _e($cf['desc'], 'geodirectory');?>"></i>
            <?php endif; ?>
        </label>

        <div id="<?php echo $cf['name']; ?>_input_wrapper" class="dws_geodir_input_wrapper">
            <input type="tel" field_type="<?php echo $cf['type'];?>"
                   name="<?php  echo $cf['name'];?>" id="<?php echo $cf['name'];?>" class="geodir_textfield"
                   placeholder="<?php echo (!empty($cf['placeholder_value'])) ? esc_html__($cf['placeholder_value'], 'geodirectory') : 'Enter Data' ?>"
                   value="<?php echo esc_attr(stripslashes($value));?>"
                   <?php echo $validation;echo $validation_msg;?> />

            <?php if ($cf['is_required']) { ?>
                <span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="<?php _e($cf['required_msg'], 'geodirectory'); ?>" data-toggle="tooltip" style="display: none;"></span>
            <?php } ?>
        </div>

    </div>

    <?php return ob_get_clean();
}, 100, 2);
add_filter('geodir_custom_field_input_textarea', function ($html, $cf) {
    // only affect the front-end
    if (is_admin() || boolval($cf['for_admin_use'])) { return $html; }

    // start adjusted output
    ob_start();

    // NOTE: we don't support the advanced editor here
    $value = geodir_get_cf_value($cf);
    ?>

    <div id="<?php echo $cf['name'];?>_row"
         class="geodir_form_row clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_textarea <?php if ($cf['is_required']) echo 'required_field';?>">

        <div class="dws_geodir_label_wrapper">
            <label for="<?php echo esc_attr( $cf['name'] ); ?>">
                <?php $frontend_title = __($cf['frontend_title'], 'geodirectory');
                echo (trim($frontend_title)) ? $frontend_title : '&nbsp;'; ?>
                <?php if ($cf['is_required']) echo '<span>*</span>';?>

                <?php if (!empty(($cf['desc']))): ?>
                    <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php _e($cf['desc'], 'geodirectory');?>"></i>
                <?php endif; ?>
            </label>

            <?php if ($cf['is_required']) { ?>
                <span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="<?php _e($cf['required_msg'], 'geodirectory'); ?>" data-toggle="tooltip" style="display: none;"></span>
            <?php } ?>
        </div>

        <?php
            $attributes = apply_filters( 'geodir_cfi_textarea_attributes', array(), $cf );
            $attributes = is_array( $attributes ) && ! empty( $attributes ) ? implode( ' ', $attributes ) : '';
        ?>

        <div id="<?php echo $cf['name']; ?>_textarea_wrapper" class="dws_geodir_input_wrapper">
            <textarea field_type="<?php echo $cf['type'];?>"
                      name="<?php echo $cf['name'];?>" id="<?php echo $cf['name'];?>" class="geodir_textarea"
                      <?php if(!empty($cf['placeholder_value'])){ echo 'placeholder="'.esc_html__( $cf['placeholder_value'], 'geodirectory').'"'; } ?>
                      <?php echo $attributes; ?>><?php echo stripslashes($value);?></textarea>
        </div>

    </div>

    <?php return ob_get_clean();
}, 100, 2);
add_filter('geodir_custom_field_input_select', function ($html, $cf) {
    // only affect the front-end
    if (is_admin() || boolval($cf['for_admin_use'])) { return $html; }

    // start adjusted output
    ob_start();

    $value = geodir_get_cf_value($cf);
    $frontend_title = __($cf['frontend_title'], 'geodirectory');
    $placeholder = ! empty( $cf['placeholder_value'] ) ? __( $cf['placeholder_value'], 'geodirectory' ) : '';

    ?>

    <div id="<?php echo $cf['name'];?>_row"
         class="geodir_form_row geodir_custom_fields clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_select <?php if ($cf['is_required']) echo 'required_field';?>">

        <label for="<?php echo esc_attr( $cf['name'] ); ?>">
            <?php echo (trim($frontend_title)) ? $frontend_title : '&nbsp;'; ?>
            <?php if ($cf['is_required']) echo '<span>*</span>';?>

            <?php if (!empty(($cf['desc']))): ?>
                <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php _e($cf['desc'], 'geodirectory');?>"></i>
            <?php endif; ?>
        </label>

        <div id="<?php echo $cf['name']; ?>_select_wrapper" class="dws_geodir_input_wrapper">
            <?php
                $option_values_arr = geodir_string_values_to_options($cf['option_values'], true);
                $select_options = ''; $selected_label = '';

                if (!empty($option_values_arr)) {
                    foreach ($option_values_arr as $key => $option_row) {
                        if (isset($option_row['optgroup']) && ($option_row['optgroup'] == 'start' || $option_row['optgroup'] == 'end')) {
                            $option_label = isset($option_row['label']) ? $option_row['label'] : '';

                            $select_options .= $option_row['optgroup'] == 'start' ? '<optgroup label="' . esc_attr($option_label) . '">' : '</optgroup>';
                        } else {
                            $option_label = isset($option_row['label']) ? $option_row['label'] : '';
                            $option_value = isset($option_row['value']) ? $option_row['value'] : '';
                            $selected = selected($option_value,stripslashes($value), false);

                            if (!empty($selected)) {
                                $selected_label = $option_label;
                            }

                            $select_options .= '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . $option_label . '</option>';
                        }

                        if ( $key == 0 && empty( $option_row['optgroup'] ) && ! empty( $option_label ) && isset( $option_row['value'] ) && $option_row['value'] === '' ) {
                            $placeholder = $option_label;
                        }
                    }
                }

                if ( empty( $placeholder ) ) {
                    $placeholder = wp_sprintf( __( 'Select %s&hellip;', 'geodirectory' ), $frontend_title );
                }
            ?>

            <div class="dws_gd_select_placeholder <?php echo empty($selected_label) ? '' : 'selected'; ?>">
                <span><?php echo empty($value) ? $placeholder : $selected_label; ?></span>
                <i aria-hidden="true" class="fas fa-caret-down"></i>
            </div>

            <select field_type="<?php echo $cf['type'];?>"
                    name="<?php echo $cf['name'];?>" id="<?php echo $cf['name'];?>" class="geodir_textfield textfield_x geodir-select enhanced dws_enhanced select2-hidden-accessible"
                    data-placeholder="<?php echo esc_attr( $placeholder ); ?>">
                <?php echo $select_options;?>
            </select>

            <?php if ($cf['is_required']) { ?>
                <span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="<?php _e($cf['required_msg'], 'geodirectory'); ?>" data-toggle="tooltip" style="display: none;"></span>
            <?php } ?>
        </div>
    </div>
    <ul id="<?php echo $cf['name'];?>_row_options" class="geodir_form_row geodir_custom_fields clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_select_options" data-field-name="<?php echo $cf['name']; ?>">
    </ul>

    <?php return ob_get_clean();
}, 100, 2);
add_filter('geodir_custom_field_input_categories', function ($html, $cf) {
    // only affect the front-end
    if (is_admin() || boolval($cf['for_admin_use'])) { return $html; }

    // start adjusted output
    ob_start();

    $value = geodir_get_cf_value($cf);
    if ($value == $cf['default']) { $value = ''; }

    $name = $cf['name'];
    $frontend_title = $cf['frontend_title'];
    $frontend_desc = $cf['desc'];
    $is_required = $cf['is_required'];
    $is_admin = $cf['for_admin_use'];
    $required_msg = $cf['required_msg'];
    $taxonomy = $cf['post_type']."category";
    $placeholder = ! empty( $cf['placeholder_value'] ) ? __( $cf['placeholder_value'], 'geodirectory' ) : __( 'Select Category', 'geodirectory' );

    ?>

    <div id="<?php echo $taxonomy;?>_row"
         class="geodir_form_row clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_categories <?php echo esc_attr( $cf['css_class'] ); ?> <?php if ($is_required) echo 'required_field';?>">

        <label for="cat_limit">
            <?php
                $frontend_title = __($frontend_title, 'geodirectory');
                echo (trim($frontend_title)) ? $frontend_title : '&nbsp;';

            ?>

            <?php if ($is_required) echo '<span>*</span>';?>

            <?php if (!empty(($frontend_desc))): ?>
                <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php _e($frontend_desc, 'geodirectory');?>"></i>
            <?php endif; ?>
        </label>

        <div id="<?php echo $cf['name']; ?>_input_wrapper" class="dws_geodir_input_wrapper">
            <div id="<?php echo $taxonomy;?>" class="geodir_taxonomy_field" style="float:left; width:70%;">
                <?php

                global $wpdb, $gd_post, $cat_display, $post_cat, $package_id, $exclude_cats;

                $exclude_cats = array();

                $package = geodir_get_post_package( $gd_post, $cf['post_type'] );
                //if ($is_admin == '1') {
                if ( ! empty( $package ) && isset( $package->exclude_category ) ) {
                    if ( is_array( $package->exclude_category ) ) {
                        $exclude_cats = $package->exclude_category;
                    } else {
                        $exclude_cats = $package->exclude_category != '' ? explode( ',', $package->exclude_category ) : array();
                    }
                }
                //}

                $extra_fields = maybe_unserialize( $cf['extra_fields'] );
                if ( is_array( $extra_fields ) && ! empty( $extra_fields['cat_display_type'] ) ) {
                    $cat_display = $extra_fields['cat_display_type'];
                } else {
                    $cat_display = 'select';
                }

                $post_cat = geodir_get_cf_value($cf);

                $category_limit = ! empty( $package ) && isset( $package->category_limit ) ? absint( $package->category_limit ) : 0;
                $category_limit = (int) apply_filters( 'geodir_cfi_post_categories_limit', $category_limit, $gd_post, $package );

                if ($cat_display != '') {

                    $required_limit_msg = '';
                    if ($category_limit > 0 && $cat_display != 'select' && $cat_display != 'radio') {

                        $required_limit_msg = wp_sprintf( __('Only select %d categories for this package.', 'geodirectory'), $category_limit );

                    } else {
                        $required_limit_msg = $required_msg;
                    }

                    echo '<input type="hidden" cat_limit="' . $category_limit . '" id="cat_limit" value="' . esc_attr($required_limit_msg) . '" name="cat_limit[' . $taxonomy . ']"  />';
                    echo '<input type="hidden" name="default_category" value="' . esc_attr( geodir_get_cf_default_category_value() ) . '">';

                    if ($cat_display == 'select' || $cat_display == 'multiselect') {
                        $multiple = '';
                        $default_field = '';
                        if ($cat_display == 'multiselect') {
                            $multiple = 'multiple="multiple"';
                            $default_field = 'data-cmultiselect="default_category"';
                        } else {
                            $default_field = 'data-cselect="default_category"';
                        }

                        echo '<select id="' .$taxonomy . '" ' . $multiple . ' type="' . $taxonomy . '" name="tax_input['.$taxonomy.'][]" alt="' . $taxonomy . '" field_type="' . $cat_display . '" class="geodir_textfield textfield_x geodir-select" data-placeholder="' . esc_attr( $placeholder ) . '" ' . $default_field . ' aria-label="' . esc_attr( $placeholder ) . '">';

                        if ($cat_display == 'select')
                            echo '<option value="">' . __('Select Category', 'geodirectory') . '</option>';

                    }

                    echo GeoDir_Admin_Taxonomies::taxonomy_walker($taxonomy);

                    if ($cat_display == 'select' || $cat_display == 'multiselect')
                        echo '</select>';

                }

                ?>
            </div>

            <?php if ($is_required) { ?>
                <span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="<?php _e($required_msg, 'geodirectory'); ?>" data-toggle="tooltip" style="display: none;"></span>
            <?php } ?>
        </div>

    </div>

    <?php return ob_get_clean();
},10,2);
add_filter('geodir_custom_field_input_address', function ($html, $cf) {
    // only affect the front-end
    if (is_admin() || boolval($cf['for_admin_use'])) { return $html; }

    global $post,$gd_post,$geodirectory;

    if(empty($gd_post)){
        $gd_post = geodir_get_post_info($post->ID);
    }

    $name = $cf['name'];
    $type = $cf['type'];
    $frontend_desc = $cf['desc'];
    $is_required = $cf['is_required'];
    $required_msg = $cf['required_msg'];
    $frontend_title = $cf['frontend_title'];
    $is_admin = $cf['for_admin_use'];
    $extra_fields = stripslashes_deep(unserialize($cf['extra_fields']));
    $prefix = $name . '_';

    // steet2
    if(!isset($extra_fields['street2_lable'])){$extra_fields['street2_lable'] = '';}

    ($frontend_title != '') ? $address_title = $frontend_title : $address_title = geodir_ucwords($prefix . ' street');
    ($extra_fields['street2_lable'] != '') ? $street2_title = $extra_fields['street2_lable'] : $zip_title = geodir_ucwords($prefix . ' street2');
    ($extra_fields['zip_lable'] != '') ? $zip_title = $extra_fields['zip_lable'] : $zip_title = geodir_ucwords($prefix . ' zip/post code ');
    ($extra_fields['map_lable'] != '') ? $map_title = $extra_fields['map_lable'] : $map_title = geodir_ucwords('set address on map');
    ($extra_fields['mapview_lable'] != '') ? $mapview_title = $extra_fields['mapview_lable'] : $mapview_title = geodir_ucwords($prefix . ' mapview');

    $street  = $gd_post->street;
    $street2     = isset( $gd_post->street2 ) ? $gd_post->street2 : '';
    $zip     = isset( $gd_post->zip ) ? $gd_post->zip : '';
    $lat     = isset( $gd_post->latitude) ? $gd_post->latitude : '';
    $lng     = isset( $gd_post->longitude) ? $gd_post->longitude : '';
    $mapview = isset( $gd_post->mapview ) ? $gd_post->mapview : '';
    $post_mapzoom = $mapzoom = isset( $gd_post->mapzoom ) ? $gd_post->mapzoom : '';

    $location = $geodirectory->location->get_default_location();
    if (empty($city)) $city = isset($location->city) ? $location->city : '';
    if (empty($region)) $region = isset($location->region) ? $location->region : '';
    if (empty($country)) $country = isset($location->country) ? $location->country : '';

    $lat_lng_blank = false;
    if (empty($lat) && empty($lng)) {
        $lat_lng_blank = true;
    }

    if (empty($lat)) $lat = isset($location->city_latitude) ? $location->city_latitude : '';
    if (empty($lng)) $lng = isset($location->city_longitude) ? $location->city_longitude : '';

    /**
     * Filter the default latitude.
     *
     * @since 1.0.0
     *
     * @param float $lat Default latitude.
     * @param bool $is_admin For admin use only?.
     */
    $lat = apply_filters('geodir_default_latitude', $lat, $is_admin);
    /**
     * Filter the default longitude.
     *
     * @since 1.0.0
     *
     * @param float $lat Default longitude.
     * @param bool $is_admin For admin use only?.
     */
    $lng = apply_filters('geodir_default_longitude', $lng, $is_admin);

    $locate_me = !empty($extra_fields['show_map']) && GeoDir_Maps::active_map() != 'none' ? true : false;
    $locate_me_class = $locate_me ? ' gd-form-control' : '';

    // start adjusted output
    ob_start(); ?>

    <div id="geodir_<?php echo $prefix . 'street';?>_row"
         class="geodir_form_row clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_address <?php if ($is_required) echo 'required_field';?>">

        <label for="<?php echo esc_attr( $prefix . 'street' ); ?>">
            <?php _e($address_title, 'geodirectory'); ?>

            <?php if ($is_required) echo '<span>*</span>';?>

            <?php if (!empty(($frontend_desc))): ?>
                <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php _e($frontend_desc, 'geodirectory');?>"></i>
            <?php endif; ?>
        </label>

        <div id="<?php echo $prefix . 'street'; ?>_input_wrapper" class="dws_geodir_input_wrapper">
            <?php if ($locate_me): ?><div class="gd-input-group gd-locate-me"><?php endif; ?>

            <!-- NOTE autocomplete="new-password" seems to be the only way to disable chrome autofill and others. -->
            <input type="text" field_type="<?php echo $type;?>" autocomplete="new-password"
                   name="<?php echo 'street';?>" id="<?php echo $prefix . 'street';?>" class="geodir_textfield<?php echo $locate_me_class;?>"
                   value="<?php echo esc_attr(stripslashes($street)); ?>"
                   placeholder="<?php echo (!empty($cf['placeholder_value'])) ? esc_html__($cf['placeholder_value'], 'geodirectory') : 'Enter Data' ?>" />

            <?php if ($locate_me): ?>
                <span class="gd-locate-me-btn gd-input-group-addon" title="<?php esc_attr_e('My location', 'geodirectory'); ?>"><i class="fas fa-crosshairs fa-fw" aria-hidden="true"></i></span>
            </div><?php endif; ?>

            <?php if ($is_required) { ?>
                <span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="<?php _e($required_msg, 'geodirectory'); ?>" data-toggle="tooltip" style="display: none;"></span>
            <?php } ?>
        </div>
    </div>

    <?php

    if (isset($extra_fields['show_street2']) && $extra_fields['show_street2']): ?>
        <div id="geodir_<?php echo $prefix . 'street2'; ?>_row"
             class="geodir_form_row clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_address">

            <label for="<?php echo esc_attr( $prefix . 'street2' ); ?>">
                <?php _e($street2_title, 'geodirectory'); ?>

                <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php echo sprintf( __('Please enter listing %s', 'geodirectory'), __($street2_title, 'geodirectory') );?>"></i>
            </label>

            <input type="text" field_type="<?php echo $type; ?>" name="<?php echo 'street2'; ?>"
                   id="<?php echo $prefix . 'street2'; ?>" class="geodir_textfield autofill"
                   value="<?php echo esc_attr(stripslashes($street2)); ?>" placeholder="Enter street address 2"/>

        </div>
    <?php endif;

    /**
     * Called after the address input on the add listings.
     *
     * This is used by the location manage to add further locations info etc.
     *
     * @since 1.0.0
     * @param array $cf The array of setting for the custom field. {@see geodir_custom_field_save()}.
     */
    do_action('geodir_address_extra_listing_fields', $cf);

    if (isset($extra_fields['show_zip']) && $extra_fields['show_zip']): ?>

        <div id="geodir_<?php echo $prefix . 'zip'; ?>_row"
             class="geodir_form_row clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_address <?php /*if($is_required) echo 'required_field';*/ ?>">

            <label for="<?php echo esc_attr( $prefix . 'zip' ); ?>">
                <?php _e($zip_title, 'geodirectory'); ?>
                <?php /*if($is_required) echo '<span>*</span>';*/ ?>

                <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php echo sprintf( __('Please enter listing %s', 'geodirectory'), __($zip_title, 'geodirectory') );?>"></i>
            </label>

            <input type="text" field_type="<?php echo $type; ?>" name="<?php echo 'zip'; ?>"
                   id="<?php echo $prefix . 'zip'; ?>" class="geodir_textfield autofill"
                   value="<?php echo esc_attr(stripslashes($zip)); ?>" placeholder="Enter Zip/Post Code"/>

        </div>

    <?php endif; ?>

    <?php  if (isset($extra_fields['show_map']) && $extra_fields['show_map']) { ?>

        <div id="geodir_<?php echo $prefix . 'map'; ?>_row" class="geodir_form_row clearfix gd-fieldset-details" style="display: none;">
            <?php
            /**
             * Contains add listing page map functions.
             *
             * @since 1.0.0
             */
            include( GEODIRECTORY_PLUGIN_DIR . 'templates/map.php' );
            if ($lat_lng_blank) {
                $lat = '';
                $lng = '';
            }
            ?>
            <span class="geodir_message_note"><?php echo stripslashes( __( 'Click on "Set Address on Map" and then you can also drag pinpoint to locate the correct address', 'geodirectory' ) ); ?></span>
        </div>
        <?php
        /* show lat lng */
        $style_latlng = ((isset($extra_fields['show_latlng']) && $extra_fields['show_latlng']) || is_admin()) ? '' : 'style="display:none"'; ?>
        <div id="geodir_<?php echo $prefix . 'latitude'; ?>_row"
             class="<?php if ($is_required) echo 'required_field'; ?> geodir_form_row clearfix gd-fieldset-details" <?php echo $style_latlng; ?>>
            <label for="<?php echo esc_attr( $prefix . 'latitude' ); ?>">
                <?php _e( 'Address Latitude', 'geodirectory' ); ?>
                <?php if ($is_required) echo '<span>*</span>'; ?>
            </label>
            <input type="number" field_type="<?php echo $type; ?>" name="<?php echo 'latitude'; ?>"
                   id="<?php echo $prefix . 'latitude'; ?>" class="geodir_textfield"
                   value="<?php echo esc_attr(stripslashes($lat)); ?>" size="25"
                   min="-90" max="90" step="any" lang='EN'

            />
            <span class="geodir_message_note"><?php _e( 'Please enter latitude for google map perfection. eg. : <b>39.955823048131286</b>', 'geodirectory' ); ?></span>
            <?php if ($is_required) { ?>
                <span class="geodir_message_error"><?php _e($required_msg, 'geodirectory'); ?></span>
            <?php } ?>
        </div>

        <div id="geodir_<?php echo $prefix . 'longitude'; ?>_row"
             class="<?php if ($is_required) echo 'required_field'; ?> geodir_form_row clearfix gd-fieldset-details" <?php echo $style_latlng; ?>>
            <label for="<?php echo esc_attr( $prefix . 'longitude' ); ?>">
                <?php _e( 'Address Longitude', 'geodirectory' ); ?>
                <?php if ($is_required) echo '<span>*</span>'; ?>
            </label>
            <input type="number" field_type="<?php echo $type; ?>" name="<?php echo 'longitude'; ?>"
                   id="<?php echo $prefix . 'longitude'; ?>" class="geodir_textfield"
                   value="<?php echo esc_attr(stripslashes($lng)); ?>" size="25"
                   min="-180" max="180" step="any" lang='EN'
            />
            <span class="geodir_message_note"><?php _e( 'Please enter longitude for google map perfection. eg. : <b>-75.14408111572266</b>', 'geodirectory' ); ?></span>
            <?php if ($is_required) { ?>
                <span class="geodir_message_error"><?php _e($required_msg, 'geodirectory'); ?></span>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (isset($extra_fields['show_mapview']) && $extra_fields['show_mapview']) { ?>
        <div id="geodir_<?php echo $prefix . 'mapview'; ?>_row" class="geodir_form_row clearfix gd-fieldset-details">
            <label for="<?php echo esc_attr( $prefix . 'mapview' ); ?>"><?php _e($mapview_title, 'geodirectory'); ?></label>

            <select  name="<?php echo 'mapview'; ?>" id="<?php echo $prefix . 'mapview'; ?>" class="geodir-select">
                <?php
                $mapview_options = array(
                    'ROADMAP'=>__('Default Map', 'geodirectory'),
                    'SATELLITE'=>__('Satellite Map', 'geodirectory'),
                    'HYBRID'=>__('Hybrid Map', 'geodirectory'),
                    'TERRAIN'=>__('Terrain Map', 'geodirectory'),
                );
                foreach($mapview_options as $val => $name){
                    echo "<option value='$val' ".selected($val,$mapview,false)." >$name</option>";
                }
                ?>
            </select>
            <span class="geodir_message_note"><?php _e('Please select listing map view to use', 'geodirectory');?></span>
        </div>
    <?php }?>

    <?php if (isset($post_mapzoom)): ?>
        <input type="hidden" value="<?php if (isset($post_mapzoom)) {
            echo esc_attr($post_mapzoom);
        } ?>" name="<?php echo 'mapzoom'; ?>" id="<?php echo $prefix . 'mapzoom'; ?>"/>
    <?php endif; ?>

    <?php return ob_get_clean();
}, 100, 2);
add_filter('geodir_custom_field_input_file', function ($html, $cf) {
    // only affect the front-end
    if (is_admin() || boolval($cf['for_admin_use'])) { return $html; }

    // start adjusted output
    ob_start();

    global $gd_post, $post;
    if ( empty( $gd_post ) && ! empty( $post ) ) {
        $gd_post = geodir_get_post_info( $post->ID );
    }

    $html_var = $cf['htmlvar_name'];
    $extra_fields = maybe_unserialize( $cf['extra_fields'] );
    $file_limit = ! empty( $extra_fields ) && ! empty( $extra_fields['file_limit'] ) ? absint( $extra_fields['file_limit'] ) : 0;
    $file_limit = apply_filters( "geodir_custom_field_file_limit", $file_limit, $cf, $gd_post );

    $allowed_file_types = isset( $extra_fields['gd_file_types'] ) ? maybe_unserialize( $extra_fields['gd_file_types'] ) : array( 'jpg','jpe','jpeg','gif','png','bmp','ico','webp');
    $display_file_types = $allowed_file_types != '' ? '.' . implode( ", .", $allowed_file_types ) : '';
    if ( ! empty( $allowed_file_types ) ) {
        $allowed_file_types = implode( ",", $allowed_file_types );
    }

    // adjust values here
    $id = $cf['htmlvar_name']; // this will be the name of form field. Image url(s) will be submitted in $_POST using this key. So if $id == �img1� then $_POST[�img1�] will have all the image urls

    $revision_id = isset( $gd_post->post_parent ) && $gd_post->post_parent ? $gd_post->ID : '';
    $post_id = isset( $gd_post->post_parent ) && $gd_post->post_parent ? $gd_post->post_parent : $gd_post->ID;

    // check for any auto save temp media values first
    $temp_media = get_post_meta( $post_id, "__" . $revision_id, true );
    if ( ! empty( $temp_media ) && isset( $temp_media[ $html_var ] ) ) {
        $files = $temp_media[ $html_var ];
    } else {
        $files = GeoDir_Media::get_field_edit_string( $post_id, $html_var, $revision_id );
    }

    if ( ! empty( $files ) ) {
        $total_files = count( explode( '::', $files ) );
    } else {
        $total_files = 0;
    }

    $image_limit = absint( $file_limit );
    $multiple = $image_limit == 1 ? false : true; // Allow multiple files upload
    $show_image_input_box = true;
    /**
     * Filter to be able to show/hide the image upload section of the add listing form.
     *
     * @since 1.0.0
     * @param bool $show_image_input_box Set true to show. Set false to not show.
     * @param string $listing_type The custom post type slug.
     */
    $show_image_input_box = apply_filters( 'geodir_file_uploader_on_add_listing', $show_image_input_box, $cf['post_type'] );

    if ( $show_image_input_box ):
        add_thickbox();
        ?>

        <div id="<?php echo $cf['name']; ?>_row"
             class="geodir_form_row clearfix gd-fieldset-details dws_gd_form_row dws_gd_form_row_file <?php if ( $cf['is_required'] ) {echo 'required_field';} ?>">

            <label for="<?php echo $id; ?>">
                <?php
                    $frontend_title = esc_attr__( $cf['frontend_title'], 'geodirectory' );
                    echo ( trim( $frontend_title ) ) ? $frontend_title : '&nbsp;';
                ?>

                <?php if ( $cf['is_required'] ) { echo '<span>*</span>'; } ?>

                <?php if (!empty(($cf['desc']))): ?>
                    <i class="fas fa-question-circle dws_geodir_message_note" data-toggle="tooltip" data-original-title="<?php _e($cf['desc'], 'geodirectory');?>"></i>
                <?php endif; ?>
            </label>

            <div id="<?php echo $cf['name']; ?>_input_wrapper" class="dws_geodir_input_wrapper">
                <?php
                    $is_required = $cf['is_required'];
                    if ( $multiple ) {
                        $drop_file_label = __( 'Select the files or drop them here', 'geodirectory' );

                        if ( $image_limit > 1 ) {
                            $file_limit_message = wp_sprintf( __( '(You can upload %d files)', 'geodirectory' ), $image_limit );
                        } else {
                            $file_limit_message = __( '(You can upload unlimited files with this package)', 'geodirectory' );
                        }
                    } else {
                        $drop_file_label = __( 'Select a file or drop one here', 'geodirectory' );

                        $file_limit_message = '';
                    }

                    $drop_file_button = __( 'Browse', 'geodirectory' );
                ?>
                <div class="geodir-add-files">
                    <div class="geodir_form_row clearfix geodir-files-dropbox" id="<?php echo $id; ?>dropbox">
                        <input type="hidden" name="<?php echo $id; ?>" id="<?php echo $id; ?>" value="<?php echo $files; ?>" class="<?php if ( $is_required ) { echo 'gd_image_required_field'; } ?>"/>
                        <input type="hidden" name="<?php echo $id; ?>image_limit" id="<?php echo $id; ?>image_limit" value="<?php echo $image_limit; ?>"/>
                        <input type="hidden" name="<?php echo $id; ?>totImg" id="<?php echo $id; ?>totImg" value="<?php echo $total_files; ?>"/>
                        <?php if ( $allowed_file_types != '' ) { ?>
                            <input type="hidden" name="<?php echo $id; ?>_allowed_types" id="<?php echo $id; ?>_allowed_types" value="<?php echo esc_attr( $allowed_file_types ); ?>" data-exts="<?php echo esc_attr( $display_file_types ); ?>"/>
                        <?php } ?>

                        <div>
                            <div class="plupload-thumbs <?php if ( $multiple ) { echo "plupload-thumbs-multiple"; } ?> clearfix" id="<?php echo $id; ?>plupload-thumbs"></div>

                            <?php if ( $multiple ) { ?>
                                <span id="upload-msg"><?php _e( 'Please drag &amp; drop the files to rearrange the order', 'geodirectory' ); ?></span>
                            <?php } ?>

                            <span><?php echo $drop_file_label; ?></span>

                            <span id="<?php echo $id; ?>upload-error" style="display:none"></span>
                            <span style="display: none" id="gd-image-meta-input" class="lity-hide lity-show"></span>
                        </div>

                        <div class="plupload-upload-uic hide-if-no-js <?php if ( $multiple ) { echo "plupload-upload-uic-multiple"; } ?>" id="<?php echo $id; ?>plupload-upload-ui">
                            <!--<div class="geodir-dropbox-title"><?php //echo $drop_file_label; ?></div>-->
                            <input id="<?php echo $id; ?>plupload-browse-button" type="button" value="<?php echo esc_attr( $drop_file_button ); ?>" class="geodir_button button "/>
                            <!--<div class="geodir-dropbox-file-types"><?php //echo( $display_file_types != '' ? __( 'Allowed file types:', 'geodirectory' ) . ' ' . $display_file_types : '' ); ?></div>-->
                            <div class="geodir-dropbox-file-limit geodir-msg-file-limit-<?php echo $image_limit; ?>"><?php echo $file_limit_message;?></div>
                            <span class="ajaxnonceplu" id="ajaxnonceplu<?php echo wp_create_nonce( $id . 'pluploadan' ); ?>"></span>
                            <div class="filelist"></div>
                        </div>
                    </div>
                </div>

                <?php if ($is_required) { ?>
                    <span class="fas fa-exclamation-circle geodir_message_error dws_geodir_message_error" title="<?php _e($cf['required_msg'], 'geodirectory'); ?>" data-toggle="tooltip" style="display: none;"></span>
                <?php } ?>
            </div>

        </div>

    <?php endif; return ob_get_clean();
},10,2);

add_action( 'init', function() {
    remove_filter('the_title',array(GeoDir_SEO::class,'output_title'),10); // otherwise the extra sections on the agency page will have wrong titles
}, 100 ); // priority must be higher than 10
add_filter( 'wpseo_frontend_presenters', function( $presenters ) {
    if ( is_singular( 'award' ) || is_singular( 'certificate' ) || is_singular( 'verified_client' ) || is_singular( 'portfolio' ) ) {
        return array(); // otherwise editing extra section entries won't work
    }

    return $presenters;
} );

add_filter( 'geodir_add_listing_btn_text', function( $text ) {
    if ( isset( $_GET['pid'] ) ) {
        return 'Update Listing';
    }

    return $text;
} );

/*
add_filter('geodir_import_validate_post', function($post_info, $row) {
    static $category_ids = array();

    $category_slug = $post_info['default_category'];
    if (!isset($category_ids[$category_slug]) && !empty($category_slug)) {
        $category_term = get_term_by('slug', $category_slug, 'gd_placecategory');
        $category_ids[$category_slug] = $category_term->term_id;
    }

    if (isset($category_ids[$category_slug]) && !empty($category_ids[$category_slug])) {
        $post_info['default_category'] = $category_ids[$category_slug];
        $post_info['tax_input']['gd_placecategory'] = array($category_ids[$category_slug]);
    }

    $post_info['post_date'] = null;
    $post_info['post_modified'] = null;

    $post_info['claimed'] = 0;
    $post_info['website'] = strtok($post_info['website'], '?');
    //$post_info['logo'] = strtok($post_info['logo'], '?');

    $post_info['country'] = ($post_info['country'] == 'US') ? 'United States' : $post_info['country'];
    $post_info['region'] = ($post_info['region'] === 'NEW') ? 'New York' : $post_info['region'];

    $post_info['country'] = ucwords(strtolower($post_info['country']));
    $post_info['region'] = ucwords(strtolower($post_info['region']));
    $post_info['city'] = ucwords(strtolower($post_info['city']));
    $post_info['street2'] = ucwords(strtolower($post_info['street2']));
    $post_info['street'] = ucwords(strtolower($post_info['street']));

    return $post_info;
}, 10, 2);

add_action('wp_ajax_test', function() {
    $upload_dir = wp_get_upload_dir();
    $csv_file = $upload_dir['path'] . '/dws-logos-import/originals.csv';
    $photos_folder = $upload_dir['baseurl'] . '/dws-logos-import/ALL-IMAGES';

    $handle = fopen($csv_file, 'r');
    if ( $handle !== false ) {
        $row = 0;
        while ( ($data = fgetcsv($handle) ) ) {
            if ( $row++ === 0 ) { continue; }
            var_dump($data);
            break;
        }
    }

    wp_die();
});
add_action( 'wp_ajax_dws_assign_logos', function() {
    as_schedule_single_action( time(), 'dws_continue_import', array( 'start' => 87700 ) );

    wp_die();
} );
add_action('dws_continue_import', function( $start ) {
    global $wpdb;

    $posts = $wpdb->get_results( "SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_title FROM {$wpdb->posts} WHERE post_type = 'gd_place' AND post_status = 'publish' ORDER BY {$wpdb->posts}.post_date DESC LIMIT $start, 50" );

    $agencies = array();
    foreach ($posts as $post) {
        $agencies[$post->post_title] = intval($post->ID);
    }

    $upload_dir = wp_get_upload_dir();
    $csv_file = $upload_dir['path'] . '/dws-logos-import/originals.csv';
    $photos_folder = $upload_dir['baseurl'] . '/dws-logos-import/ALL-IMAGES';

    $csv_data = array();

    $handle = fopen($csv_file, 'r');
    if ( $handle !== false ) {
        $row = 0;
        while ( ($data = fgetcsv($handle) ) ) {
            if ( $row++ === 0 ) { continue; }
            $csv_data[$data[0]] = $data[1];
        }
    }

    // now save logos
    GeoDir_Admin_Import_Export::set_php_limits();
    foreach ($agencies as $agency_name => $post_id) {
        $file_name = $csv_data[$agency_name] ?? '';
        if ( empty($file_name) ) { continue; }

        $file_path = $photos_folder . "/$file_name";

        GeoDir_Post_Data::save_files( $post_id, $file_path, 'logo' );
    }

    if ( $start < 96000) {
        as_schedule_single_action( time(), 'dws_continue_import', array( 'start' => $start + 50 ) );
    }
});
*/
/*
add_action( 'wp_ajax_dws_assign_logos', function() {
    as_schedule_single_action( time(), 'dws_continue_import', array( 'start' => 94800 ) );

    wp_die();
} );
add_action('dws_continue_import', function( $start ) {
    global $wpdb;
    $table = 'wp0n_geodir_gd_place_detail';

    $agencies = get_posts( array(
        'post_type'     => 'gd_place',
        'fields'        => 'ids',
        'numberposts'   => 50,
        'offset'        => $start
    ) );

    foreach ( $agencies as $agency_id ) {
        $logo = GeoDir_Media::get_attachments_by_type( $agency_id, 'logo' );
        if ( count( $logo ) === 0 || empty( $logo[0]->file ) || empty( $logo[0]-> ID ) ) {
            $wpdb->update(
                $table,
                array( 'logo' => '' ),
                array( 'post_id' => $agency_id ),
                array( '%s' )
            );
        } else {
            $wpdb->update(
                $table,
                array( 'logo' => 'https://agencylist.com/wp-content/uploads' . $logo[0]->file . "|{$logo[0]->ID}||" ),
                array( 'post_id' => $agency_id ),
                array( '%s' )
            );
        }
    }

    if ( $start < 96000) {
        as_schedule_single_action( time(), 'dws_continue_import', array( 'start' => $start + 50 ) );
    }
});
*/



add_shortcode( 'viewcounter', 'wp_shortcode' );
function wp_shortcode() {
    global $page;
    $spp = 4;
    static $i = 1;
    $ii = $i + ( ( $page - 1 ) * $spp );
    $return = $ii.'.';
    $i++;
    return $return;
}




function display_current_user_display_name () {
    $user = wp_get_current_user();
    $display_name = $user->user_login;
    return $user->user_login;
}
add_shortcode('current_user', 'display_current_user_display_name');



function check_user_posts () {
?>

<div class="elementor-element elementor-element-53aae03f elementor-widget elementor-widget-wp-widget-uwp_profile_tabs" data-id="53aae03f" data-element_type="widget" data-widget_type="wp-widget-uwp_profile_tabs.default">
    <div class="elementor-widget-container">
        <span class="uwp-profile-tabs bsui sdel-6fd8fb91">
            <nav class="navbar navbar-expand-xl navbar-light bg-white  mb-4 p-xl-0 ">
                <div class="w-100 justify-content-center p-xl-0 border-bottom">
                    <!--<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#uwp-profile-tabs-nav" aria-controls="navbarNavDropdown-1" aria-expanded="false" aria-label="Toggle navigation" style=""><span class="navbar-toggler-icon"></span></button>-->

                    <div class="" id="uwp-profile-tabs-nav">
                        <ul class="navbar-nav flex-wrap m-0 list-unstyled">
                            <li id="uwp-profile-dashboard" class="nav-item list-unstyled m-0 active">
                                <a href="<?php echo bloginfo('url'); ?>/add-listing/" class="nav-link"><i class="fas fa-cubes"></i> <span class="uwp-profile-tab-label uwp-profile-dashboard-label">Dashboard</span></a>
                            </li>

    <?php
    if ( 0 == count_user_posts( get_current_user_id(), "gd_place" ) && is_user_logged_in() ) {} elseif ( 0 !== count_user_posts( get_current_user_id(), "gd_place" ) && is_user_logged_in() ) { ?>    
        <li id="uwp-profile-listings" class="nav-item  list-unstyled m-0">
			<a href="<?php echo bloginfo('url'); ?>/profile/[current_user]/listings/#tab-content" class="nav-link"><i class="fas fa-globe-americas"></i> <span class="uwp-profile-tab-label uwp-profile-listings-label">Listings</span></a>
		</li>
        <li id="uwp-profile-badges" class="nav-item  list-unstyled m-0">
            <a href="<?php echo bloginfo('url'); ?>/profile/[current_user]/badges/#tab-content" class="nav-link"><i class="fas fa-certificate"></i> <span class="uwp-profile-tab-label uwp-profile-badges-label">Badges</span></a>
        </li>
    <?php }

    if ( 0 == count_user_posts( get_current_user_id(), "post" ) && is_user_logged_in() ) {} elseif ( 0 !== count_user_posts( get_current_user_id(), "post" ) && is_user_logged_in() ) { ?>    
        <li id="uwp-profile-posts" class="nav-item  list-unstyled m-0">
            <a href="<?php echo bloginfo('url'); ?>/profile/[current_user]/posts/#tab-content" class="nav-link"><i class="fas fa-certificate"></i> <span class="uwp-profile-tab-label uwp-profile-posts-label">Posts</span></a>
        </li>
    <?php }

    $user_id = get_current_user_id();
    $user_specific_comments = get_comments(
        array(
            'user_id' => $user_id,
        )
    );

    if (!empty($user_specific_comments)) {
    ?>
        <li id="uwp-profile-reviews" class="nav-item  list-unstyled m-0">
            <a href="<?php echo bloginfo('url'); ?>/profile/[current_user]/reviews/#tab-content" class="nav-link"><i class="fas fa-award"></i> <span class="uwp-profile-tab-label uwp-profile-reviews-label">Reviews</span></a>
        </li>
    <?php
        } ?>

                        </ul>
		            </div>

	            </div>
            </nav>
        </span>		
    </div>
</div>

<?php 

}

add_shortcode('check_posts', 'check_user_posts');