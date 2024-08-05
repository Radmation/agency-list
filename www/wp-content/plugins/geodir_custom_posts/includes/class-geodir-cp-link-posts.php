<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory Custom Post Types Link Posts class
 *
 * @class       GeoDir_CP_Link_Posts
 * @version     2.0.0
 * @package     GeoDir_Custom_Posts/Classes
 * @category    Class
 * @author      AyeCode Ltd
 */
class GeoDir_CP_Link_Posts {

	public static function init() {
		if ( is_admin() ) {
			add_filter( 'geodir_custom_fields_predefined', array( __CLASS__, 'predefined_custom_fields' ), 10, 2 );
			// Hide default value input
			add_filter( 'geodir_cfa_default_value_link_posts', '__return_empty_string', 10, 4 );
			// Hide sorting options
			add_filter( 'geodir_cfa_cat_sort_link_posts', '__return_empty_string', 10, 4 );
			// price fields
			add_filter( 'geodir_cfa_extra_fields_link_posts', array( __CLASS__, 'extra_fields_link_posts' ), 10, 4 );

			add_filter( 'geodir_cfa_skip_column_add', array( __CLASS__, 'cfa_skip_column_add' ), 10, 2 );
			add_filter( 'geodir_after_custom_fields_updated', array( __CLASS__, 'on_custom_field_updated' ), 99, 1 );
			add_filter( 'geodir_after_custom_field_deleted', array( __CLASS__, 'on_custom_field_deleted' ), 99, 3 );
		}

		// Input field: link_posts
		add_filter( 'geodir_custom_field_input_link_posts', array( __CLASS__, 'cfi_link_posts' ), 10, 2 );

		// Process link_posts value before save
		add_filter( 'geodir_custom_field_value_link_posts', array( __CLASS__, 'sanitize_link_posts_data' ), 10, 6 );

		// Save link posts data
		add_filter( 'geodir_save_post_data', array( __CLASS__, 'save_post_data' ), 10, 4 );

		//add_action( 'delete_post', 'codex_sync', 10 );

		// Get input value
		add_filter( 'geodir_get_cf_value', array( __CLASS__, 'link_posts_value' ), 10, 2 );

		add_filter( 'geodir_custom_field_output_link_posts', array( __CLASS__, 'cf_link_posts' ), 10, 5 );
		add_action( 'deleted_post', array( __CLASS__, 'delete_post_links' ), 10, 1 );

		// filter the meta value
		add_filter( 'geodir_pre_get_post_meta', array( __CLASS__, 'get_meta_value' ), 10, 5 );

		// copy the linked DB values
		add_action('_wp_put_post_revision',array( __CLASS__,'make_revision_db_entry'));

		// Filter GD post info object.
		add_filter( 'geodir_get_post_info', array( __CLASS__, 'filter_post_info' ), 20, 2 );
		add_filter( 'geodir_post_badge_match_value', array( __CLASS__, 'post_badge_match_value' ), 20, 5 );

		// Elementor render link posts value.
		add_filter( 'geodir_elementor_tag_text_render_value', array( __CLASS__, 'elementor_tag_text_render_value' ), 10, 3 );
	}

	/**
	 * Create the linked values for any new revision created.
	 *
	 * @param $revision_id
	 */
	public static function make_revision_db_entry($revision_id){
		$parent_id = wp_get_post_parent_id( $revision_id );
		$post_type = get_post_type( $parent_id );
		$all_postypes = geodir_get_posttypes();

		// check its a GD post type
		if(in_array( $post_type, $all_postypes ) && self::get_items( $parent_id  ) ){
			self::clone_linked_values($parent_id,$revision_id);
		}
	}

	/**
	 * Clone a post linked values to its revisions post id.
	 *
	 * @param $post_parent
	 * @param $post_child
	 */
	public static function clone_linked_values($post_parent,$post_child){
		global $wpdb;

		$wpdb->query("INSERT INTO ".GEODIR_CP_LINK_POSTS." (post_type,post_id,linked_id,linked_post_type)
SELECT
  post_type, $post_child, linked_id,linked_post_type
FROM
  ".GEODIR_CP_LINK_POSTS."
WHERE
  post_id = $post_parent");

	}

	/**
	 * Filter the meta value of a linked cpt.
	 *
	 * @param $value
	 * @param $post_id
	 * @param $meta_key
	 * @param $single
	 *
	 * @return string
	 */
	public static function get_meta_value($value, $post_id, $meta_key, $single){
		$all_postypes = geodir_get_posttypes();
		if(in_array( $meta_key, $all_postypes )){
			$value = '';
			$linked_ids = array();
			$items = self::get_items( $post_id, $meta_key );
			if(!empty($items)){
				foreach($items as $item){
					if(isset($item->linked_id) && $item->linked_id){
						$linked_ids[] = $item->linked_id;
					}
				}

				if(!empty($linked_ids)){
					$value = implode(",",$linked_ids);
				}
			}
		}
		return $value;
	}

	public static function predefined_custom_fields( $custom_fields, $post_type ) {
		$post_types = geodir_get_posttypes( 'array' );

		if ( empty( $post_types ) ) {
			return $custom_fields;
		}

		$cpt_name = geodir_post_type_singular_name( $post_type );

		foreach ( $post_types as $post_type => $args ) {
			$name = $args['labels']['name'];
			$singular_name = $args['labels']['singular_name'];
			$field_title = __( 'Link Posts:', 'geodir_custom_posts' ) . ' '. $singular_name;

			$custom_fields[ $post_type ] = array(
				'field_type'  => 'link_posts',
				'class'       => 'gd-post-link-' . $post_type,
				'icon'        => 'fas fa-link',
				'name'        => $field_title,
				'description' => wp_sprintf( __( 'Add a select input to link %s to the %s.', 'geodir_custom_posts' ), $name, $cpt_name ),
				'single_use'  => $post_type,
				'defaults'    => array(
					'data_type'          => 'TEXT',
					'admin_title'        => $field_title,
					'frontend_title'     => $field_title,
					'frontend_desc'      => wp_sprintf( __( 'Select your %s to link with this %s.', 'geodir_custom_posts' ), $singular_name, $cpt_name ),
					'htmlvar_name'       => $post_type,
					'is_active'          => true,
					'for_admin_use'      => false,
					'default_value'      => '',
					'show_in'            => '',
					'is_required'        => false,
					'option_values'      => '',
					'validation_pattern' => '',
					'validation_msg'     => '',
					'required_msg'       => '',
					'field_icon'         => 'fas fa-link',
					'css_class'          => '',
					'cat_sort'           => false,
					'cat_filter'         => false,
					'single_use'         => true,
					'extra_fields'       => array(
						'max_posts'      => 1, // Max no. of posts allowed to link to the post.
					),
				)

			);
		}

		return $custom_fields;
	}

	public static function extra_fields_link_posts( $output, $field_id, $cf, $field ) {
		global $aui_bs5;

		$extra_fields = ! empty( $field->extra_fields ) ? maybe_unserialize( $field->extra_fields ) : array();

		$max_posts = ! empty( $extra_fields ) && isset( $extra_fields['max_posts'] ) ? $extra_fields['max_posts'] : 1;
		$all_posts = ! empty( $extra_fields ) && ! empty( $extra_fields['all_posts'] ) ? absint( $extra_fields['all_posts'] ) : 0;

		$output .= aui()->input(
			array(
				'id' => 'gd_extras_max_posts_' . $field_id,
				'name' => 'extra[max_posts]',
				'type' => 'number',
				'label_type' => 'top',
				'label' => __( 'Max posts', 'geodir_custom_posts' ) . geodir_help_tip( __( 'Set max no. of posts allowed to linked to the post. Set 0 or blank to allow unlimited posts.', 'geodir_custom_posts' ) ),
				'value' => absint( $max_posts ),
				'wrap_class' => geodir_advanced_toggle_class(),
				'extra_attributes' => array(
					'step' => "1",
					'min' => "0"
				)
			)
		);

		$output .= aui()->input(
			array(
				'id' => 'gd_extras_all_posts_' . $field_id,
				'name' => 'extra[all_posts]',
				'type' =>  'checkbox',
				'label' => __( 'Link to all user\'s posts?', 'geodir_custom_posts' ) . geodir_help_tip( __( 'Tick to allow link to all posts. Un-tick to link to authors own posts only.', 'geodir_custom_posts' ) ),
				'value' => '1',
				'checked' => $all_posts,
				'switch' => 'md'
			)
		);

		return $output;
	}

	/**
	 * Get the html input for the custom field: link_posts
	 *
	 * @param string $html The html to be filtered.
	 * @param array $cf The custom field array details.
	 * @since 2.0.0
	 *
	 * @return string The html to output for the custom field.
	 */
	public static function cfi_link_posts( $html, $cf ) {
		global $aui_bs5, $gd_post, $geodir_label_type;

		$htmlvar_name = $cf['htmlvar_name'];

		// Check if there is a custom field specific filter.
		if ( has_filter( "geodir_custom_field_input_link_posts_{$htmlvar_name}" ) ) {
			/**
			 * Filter the select html by individual custom field.
			 *
			 * @param string $html The html to filter.
			 * @param array $cf The custom field array.
			 * @since 2.0.0
			 */
			$html = apply_filters( "geodir_custom_field_input_link_posts_{$htmlvar_name}", $html, $cf );
		}

		if ( empty( $html ) ) {
			$label = __( $cf['frontend_title'], 'geodirectory' );
			$description = __( $cf['desc'], 'geodirectory' );
			$extra_fields = ! empty( $cf['extra_fields'] ) ? maybe_unserialize( $cf['extra_fields'] ) : array();
			$max_posts = ! empty( $extra_fields ) && isset( $extra_fields['max_posts'] ) ? absint( $extra_fields['max_posts'] ) : 1;
			$value = geodir_get_cf_value( $cf );
			//$value = self::get_items( $gd_post->ID, $cf['name'] );

			$class = ' gd-link_posts-row';
			if ( ! empty( $cf['is_required'] ) ) {
				$class .= ' required_field';
			}

			$attrs = 'data-max-posts="' . $max_posts . '" data-source-cpt="' . $cf['post_type'] . '" data-dest-cpt="' . $htmlvar_name . '"';
			if ( $max_posts != 1 ) {
				$multiple = true;
				$field_name = $htmlvar_name . '[]';
				$select_type = 'multiselect';
				$attrs .= ' multiple="multiple"';
				$cpt_name = geodir_post_type_name( $htmlvar_name, true );
			} else {
				$multiple = false;
				$field_name = $htmlvar_name;
				$select_type = 'select';
				$cpt_name = geodir_post_type_singular_name( $htmlvar_name, true );
			}

			$placeholder = ! empty( $cf['placeholder_value'] ) ? __( $cf['placeholder_value'], 'geodirectory' ) : wp_sprintf( __( 'Choose %s &hellip;', 'geodir_custom_posts' ), $cpt_name );

			$options = '';
			$options_arr = array();
			if ( ! empty( $value ) ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $value_id ) {
						if ( $value_id > 0 ) {
							$options .= '<option value="' . $value_id . '" selected="selected">' . get_the_title( $value_id ) . '</option>';
							$options_arr[ $value_id ] = get_the_title( $value_id );
						}
					}
				} else {
					$options .= '<option value="' . $value . '" selected="selected">' . get_the_title( $value ) . '</option>';
					$options_arr[ $value ] = get_the_title( $value );
				}
			}

			$fill_fields = GeoDir_CP_Link_Posts::get_fields( $cf['post_type'] );
			if ( ! empty( $fill_fields ) ) {
				$from_post_type = geodir_post_type_singular_name( $cf['post_type'], true );
				$to_post_type = geodir_post_type_singular_name( $htmlvar_name, true );
				$link_confirm = wp_sprintf( __( 'Are you sure you want to fill %s listing data from selected linked %s data?', 'geodir_custom_posts' ), $from_post_type, $to_post_type );
				$link_title = wp_sprintf( __( 'Fill %s listing data from linked %s data.', 'geodir_custom_posts' ), $from_post_type, $to_post_type );
			}

			$html = '<input type="hidden" name="'. $field_name .'" value="" />';
			// AUI
			if ( geodir_design_style() ) {
				$extra_attributes = array();
				$extra_attributes['data-placeholder'] = esc_attr( $placeholder );
				$extra_attributes['data-source-cpt'] = $cf['post_type'];
				$extra_attributes['data-dest-cpt'] = $cf['name'];
				$extra_attributes['data-data-max-posts'] = $max_posts;
				$extra_attributes['data-nonce'] = esc_attr( wp_create_nonce( 'geodir-select-search-' . $cf['post_type'] . '-' . $cf['name'] ) );
				$extra_attributes['data-allow-clear'] = true;
				$extra_attributes['data-min-input-length'] = 2;

				if ( ! empty( $cf['validation_pattern'] ) ) {
					$extra_attributes['pattern'] = $cf['validation_pattern'];
				}

				// required
				$required = ! empty( $cf['is_required'] ) ? ' <span class="text-danger">*</span>' : '';

				// admin only
				$admin_only = geodir_cfi_admin_only( $cf );

				$pull_button = '';
				if ( ! empty( $fill_fields ) ) {
					$pull_button = '<span class="geodir-fill-data btn btn-secondary btn-sm ' . ( $aui_bs5 ? 'float-end' : 'float-right' ) .' c-pointer" data-toggle="tooltip" data-from-post-type="' . $cf['post_type'] . '" data-to-post-type="' . $htmlvar_name . '" data-from-post-id="' . ( ! empty( $gd_post->ID ) ? $gd_post->ID : '' ) . '" data-confirm="' . esc_attr( $link_confirm ) . '" title="' . esc_attr( $link_title ) . '"><i class="fas fa-sync-alt" aria-hidden="true"></i></span>';
					$description .= $pull_button;
				}

				$conditional_attrs = geodir_conditional_field_attrs( $cf, '', 'select' );

				$html .= aui()->select( array(
					'id' => $cf['name'],
					'name' => $cf['name'],
					'title' => $label,
					'placeholder' => $placeholder,
					'value' => $value,
					'required' => ! empty( $cf['is_required'] ) ? true : false,
					'label_show' => true,
					'label_type' => !empty( $geodir_label_type ) ? $geodir_label_type : 'horizontal',
					'label' => $label . $admin_only . $required,
					'validation_text' => ! empty( $cf['validation_msg'] ) ? $cf['validation_msg'] : '',
					'validation_pattern' => ! empty( $cf['validation_pattern'] ) ? $cf['validation_pattern'] : '',
					'help_text' => $description,
					'extra_attributes' => $extra_attributes,
					'options' => $options_arr,
					'multiple' => $multiple,
					'select2' => true,
					'class' => 'geodir-select-search-post',
					'wrap_attributes' => $conditional_attrs
				) );
			} else {
			ob_start();
			?>
			<div id="<?php echo $htmlvar_name; ?>_row" class="geodir_form_row clearfix geodir_custom_fields gd-fieldset-details<?php echo $class; ?>">
				<label for="<?php echo $htmlvar_name; ?>">
					<?php echo $label . ( ! empty( $cf['is_required'] ) ? ' <span>*</span>' : '' ); ?>
				</label>
				<select field_type="<?php echo $cf['type']; ?>" name="<?php echo $field_name; ?>" id="<?php echo $htmlvar_name; ?>" class="geodir_textfield textfield_x geodir-select-search-post" data-placeholder="<?php echo esc_attr( $placeholder ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'geodir-select-search-' . $cf['post_type'] . '-' . $htmlvar_name ) ); ?>" data-allow_clear="true" data-min-input-length="2" <?php echo $attrs; ?>>
					<?php echo $options;?>
				</select>
				<span class="geodir_message_note"><?php echo $description; ?><?php if ( ! empty( $fill_fields ) ) { ?><span class="geodir-fill-data" data-from-post-type="<?php echo $cf['post_type']; ?>" data-to-post-type="<?php echo $htmlvar_name; ?>" data-from-post-id="<?php echo ! empty( $gd_post->ID ? $gd_post->ID : '' ); ?>" data-confirm="<?php echo esc_attr( $link_confirm ); ?>" title="<?php echo esc_attr( $link_title ); ?>"><i class="fas fa-sync-alt" aria-hidden="true"></i></span><?php } ?></span>
				<?php if ( ! empty( $cf['is_required'] ) && ! empty( $cf['required_msg'] ) ) { ?>
					<span class="geodir_message_error"><?php _e( $cf['required_msg'], 'geodirectory' ); ?></span>
				<?php } ?>
			</div>
			<?php
			$html .= ob_get_clean();
			}
		}

		return $html;
	}

	public static function search_posts( $post_type = '', $search = '', $limit = 5, $custom_field = array() ) {
		global $wpdb, $current_user;

		$extra_fields = ! empty( $custom_field['extra_fields'] ) ? maybe_unserialize( $custom_field['extra_fields'] ) : array();

		if ( empty( $current_user->ID ) &&  ! isset( $extra_fields['all_posts'] ) ) {
			return NULL;
		}

		if ( current_user_can( 'manage_options' ) ) {
			$all_posts = 1; // Allow admin users to link posts from all the users.
		} else {
			$all_posts = ! empty( $extra_fields ) && ! empty( $extra_fields['all_posts'] ) ? absint( $extra_fields['all_posts'] ) : 0;
		}
		$all_posts = apply_filters( 'geodir_cp_link_all_users_posts', $all_posts, $post_type, $custom_field );
		
		$search = trim( $search );
		$table = geodir_db_cpt_table( $post_type );

		$fields = "p.ID, p.post_title";
		$fields = apply_filters( 'geodir_cp_search_posts_query_fields', $fields, $search, $post_type, $custom_field );

		$join = "LEFT JOIN {$table} AS pd ON pd.post_id = p.ID";
		$join = apply_filters( 'geodir_cp_search_posts_query_join', $join, $search, $post_type, $custom_field );

		$where = array( $wpdb->prepare( "p.post_status = 'publish' AND p.post_type = %s" , array( $post_type ) ) );
		if ( $search != '' ) {
			$_search = geodir_sanitize_keyword( $search, $post_type );
			$where[] = $wpdb->prepare( "( p.post_title LIKE %s OR p.post_title LIKE %s OR pd._search_title LIKE %s OR pd._search_title LIKE %s )", array( $wpdb->esc_like( $search ) . '%', '% ' . $wpdb->esc_like( $search ) . '%', $wpdb->esc_like( $_search ) . '%', '% ' . $wpdb->esc_like( $_search ) . '%' ) );
		}

		if ( ! $all_posts ) {
			$where[] = $wpdb->prepare( "p.post_author = %d" , array( (int)$current_user->ID ) );
		}

		$where = implode( " AND ", $where );

		$where = apply_filters( 'geodir_cp_search_posts_query_where', $where, $search, $post_type, $custom_field );

		if ( $where ) {
			$where = "WHERE {$where}";
		}

		$group_by = apply_filters( 'geodir_cp_search_posts_query_group_by', "", $search, $post_type, $custom_field );

		$order_by = "p.post_title ASC";
		$order_by = apply_filters( 'geodir_cp_search_posts_query_order_by', $order_by, $search, $post_type, $custom_field );
		if ( $order_by ) {
			$order_by = "ORDER BY {$order_by}";
		}

		$limit = apply_filters( 'geodir_cp_search_posts_query_limit', $limit, $search, $post_type, $custom_field );
		if ( $limit ) {
			$limit = "LIMIT {$limit}";
		}

		$sql = "SELECT {$fields} FROM {$wpdb->posts} AS p {$join} {$where} {$group_by} {$order_by} {$limit}";

		return $wpdb->get_results($sql);
	}

	public static function sanitize_link_posts_data( $value, $gd_post, $cf, $post_id, $post, $update ) {
		if ( empty( $cf->htmlvar_name ) ) {
			return $value;
		}

		if ( $cf->field_type != 'link_posts' ) {
			return $value;
		}

		$extra_fields = ! empty( $cf->extra_fields ) ? maybe_unserialize( $cf->extra_fields ) : array();
		$max_posts = ! empty( $extra_fields ) && isset( $extra_fields['max_posts'] ) ? absint( $extra_fields['max_posts'] ) : 1;

		$items = ! is_array( $value ) ? explode( ',', $value ) : $value;
		$posts = array();
		if ( ! empty( $items ) ) {
			foreach ( $items as $item_id ) {
				$item_id = $item_id ? absint( trim( $item_id ) ) : 0;

				if ( self::valid_link_post( $item_id, $cf->post_type, $post->post_author ) ) {
					$posts[] = $item_id;
				}

				if ( $max_posts > 0 && count( $posts ) >= $max_posts ) {
					break;
				}
			}
		}

		if ( $max_posts == 1 ) {
			$value = ! empty( $posts[0] ) ? $posts[0] : '';
		} else {
			$value = $posts;
		}

		return $value;
	}

	/**
	 * Save the link post data on post save.
	 *
	 * @param $postarr
	 * @param $gd_post
	 * @param $post
	 * @param $update
	 *
	 * @return mixed
	 */
	public static function save_post_data( $postarr, $gd_post, $post, $update ) {
		if ( $post->post_type == 'revision' ) {
			$p_type = get_post_type( $post->post_parent );
		} else {
			$p_type = $post->post_type;
		}

		$post_types = self::linked_to_post_types( $p_type );

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				if ( ! isset( $postarr[ $post_type ] ) ) {
					continue;
				}

				if ( ! empty( $postarr[ $post_type ] ) ) {
					$posts = $postarr[ $post_type ];
				} else {
					$posts = '';
				}

				self::save_link_posts( $posts, $post->ID, $post_type );

				unset( $postarr[ $post_type ] );
			}
		}

		// Unset disabled link posts fields.
		$post_types = self::linked_to_post_types( $p_type, 0 );
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				if ( isset( $postarr[ $post_type ] ) ) {
					unset( $postarr[ $post_type ] );
				}
			}
		}

		return $postarr;
	}

	public static function link_posts_value( $value, $cf ) {
		global $gd_post;

		if ( ! ( ! empty( $cf['field_type'] ) && $cf['field_type'] == 'link_posts' ) ) {
			return $value;
		}

		$items = self::get_items( $gd_post->ID, $cf['name'] );

		$value = array();
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$value[] = $item->linked_id;
			}
		}

		return apply_filters( 'geodir_cp_link_posts_cf_value', $value, $cf, $gd_post );
	}

	public static function linked_to_post_types( $post_type, $status = 1 ) {
		global $wpdb;

		$cache_key = 'geodir_cp_linked_to_post_types:' . $post_type . ':' . $status;
		$post_types = wp_cache_get( $cache_key, 'link_post_types' );

		if ( $post_types !== false ) {
			return $post_types;
		}

		$sql_parts = '';
		if ( $status === 0 ) {
			$sql_parts .= "AND is_active != 1";
		} else if ( $status === 1 ) {
			$sql_parts .= "AND is_active = 1";
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT htmlvar_name FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE post_type = %s AND field_type = %s {$sql_parts}", array( $post_type, 'link_posts' ) ) );

		$post_types = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $value ) {
				if ( geodir_is_gd_post_type( $value ) ) {
					$post_types[] = $value;
				}
			}
		}

		wp_cache_set( $cache_key, $post_types, 'link_post_types' );

		return $post_types;
	}

	public static function linked_from_post_types( $post_type, $status = 1 ) {
		global $wpdb;

		$cache_key = 'geodir_cp_linked_from_post_types:' . $post_type . ':' . $status;
		$post_types = wp_cache_get( $cache_key, 'link_post_types' );

		if ( $post_types !== false ) {
			return $post_types;
		}

		$sql_parts = '';
		if ( $status === 0 ) {
			$sql_parts .= "AND is_active != 1";
		} else if ( $status === 1 ) {
			$sql_parts .= "AND is_active = 1";
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT post_type FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE htmlvar_name = %s AND field_type = %s {$sql_parts}", array( $post_type, 'link_posts' ) ) );

		$post_types = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $value ) {
				if ( geodir_is_gd_post_type( $value ) ) {
					$post_types[] = $value;
				}
			}
		}

		wp_cache_set( $cache_key, $post_types, 'link_post_types' );

		return $post_types;
	}

	public static function valid_link_post( $post_id, $post_type, $post_author ) {
		global $wpdb;

		if ( empty( $post_id ) || empty( $post_type ) ) {
			return false;
		}

		$link_post_type = get_post_type( $post_id );

		$custom_field = geodir_get_field_infoby( 'htmlvar_name', $link_post_type, $post_type );
		if ( empty( $custom_field ) ) {
			return false;
		}

		$extra_fields = ! empty( $custom_field['extra_fields'] ) ? maybe_unserialize( $custom_field['extra_fields'] ) : array();
		if ( current_user_can( 'manage_options' ) ) {
			$all_posts = true; // Allow admin users to link posts from all the users.
		} else {
			$all_posts = ! empty( $extra_fields ) && ! empty( $extra_fields['all_posts'] ) ? true : false;
		}
		$all_posts = apply_filters( 'geodir_cp_link_all_users_posts', $all_posts, $post_type, $custom_field );

		$table = geodir_db_cpt_table( $link_post_type );
		$where = '';
		if ( ! $all_posts ) {
			$where .= $wpdb->prepare( "AND p.post_author = %d", $post_author );
		}

		$sql = $wpdb->prepare( "SELECT pd.post_id FROM {$wpdb->posts} AS p LEFT JOIN {$table} AS pd ON pd.post_id = p.ID WHERE p.ID = %d AND p.post_type = %s AND p.post_status = %s {$where} LIMIT 1", $post_id, $link_post_type, 'publish' );

		$sql = apply_filters( 'geodir_cp_check_link_post_query', $sql, $post_id, $link_post_type, $post_type, $post_author );
		if ( ! $sql ) {
			return false;
		}

		$valid = $wpdb->get_var( $sql );

		return apply_filters( 'geodir_cp_check_link_post', $valid, $post_id, $link_post_type, $post_type, $post_author );
	}

	public static function save_link_posts( $posts, $post_id, $linked_post_type ) {
		global $wpdb;

		if ( empty( $post_id ) || empty( $linked_post_type ) ) {
			return false;
		}

		self::delete_items( $post_id, $linked_post_type );

		if ( ! is_array( $posts ) ) {
			$items = ! empty( $posts ) ? explode( ',', $posts ) : array();
		} else {
			$items = $posts;
		}

		$post_type = get_post_type( $post_id );

		if ( ! empty( $items ) ) {
			foreach( $items as $linked_id ) {
				if ( empty( $linked_id ) ) {
					continue;
				}

				$data = array();
				$data['post_id'] = $post_id;
				$data['linked_id'] = $linked_id;
				$data['post_type'] = $post_type;
				$data['linked_post_type'] = $linked_post_type;

				$wpdb->insert( GEODIR_CP_LINK_POSTS, $data, array( '%d', '%d', '%s', '%s' ) );
			}
		}

		do_action( 'geodir_cp_link_posts_saved', $items, $post_id, $linked_post_type, $post_type  );

		return true;
	}

	public static function get_items( $post_id = 0, $linked_post_type = '', $linked_id = 0, $post_type = '' ) {
		global $wpdb;

		if ( empty( $post_id ) && empty( $linked_id ) && empty( $post_type ) && empty( $linked_post_type ) ) {
			return false;
		}

		$where = array();
		if ( ! empty( $post_id ) ) {
			if ( wp_is_post_revision( $post_id ) ) {
				$post_id = wp_get_post_parent_id( $post_id );
			}

			$where[] = $wpdb->prepare( "post_id = %d", $post_id );
		}
		if ( ! empty( $linked_id ) ) {
			if ( wp_is_post_revision( $linked_id ) ) {
				$linked_id = wp_get_post_parent_id( $linked_id );
			}
			$where[] = $wpdb->prepare( "linked_id = %d", $linked_id );
		}
		if ( ! empty( $post_type ) && $post_type != 'any' ) {
			$where[] = $wpdb->prepare( "post_type = %s", $post_type );
		}
		if ( ! empty( $linked_post_type ) && $linked_post_type != 'any' ) {
			$where[] = $wpdb->prepare( "linked_post_type = %s", $linked_post_type );
		}

		$where = ! empty( $where ) ? "WHERE " . implode( " AND ", $where ) : '';

		$results = $wpdb->get_results( "SELECT * FROM " . GEODIR_CP_LINK_POSTS . " {$where}" );

		return apply_filters( 'geodir_cp_link_get_items', $results, $post_id, $linked_id, $post_type, $linked_post_type );
	}

	public static function get_items_by( $item_key, $item_value = '' ) {
		global $wpdb;

		if ( empty( $item_key ) && empty( $item_value ) ) {
			return array();
		}

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . GEODIR_CP_LINK_POSTS . " WHERE {$item_key} = %s", $item_value ) );

		return apply_filters( 'geodir_cp_link_get_items_by', $results, $item_key, $item_value );
	}

	public static function delete_items( $post_id = 0, $linked_post_type = '', $linked_id = 0,  $post_type = '' ) {
		global $wpdb;

		if ( empty( $post_id ) && empty( $linked_id ) && empty( $post_type ) && empty( $linked_post_type ) ) {
			return false;
		}

		$where = array();
		if ( ! empty( $post_id ) ) {
			$where[] = $wpdb->prepare( "post_id = %d", $post_id );
		}
		if ( ! empty( $linked_id ) ) {
			$where[] = $wpdb->prepare( "linked_id = %d", $linked_id );
		}
		if ( ! empty( $post_type ) && $post_type != 'any' ) {
			$where[] = $wpdb->prepare( "post_type = %s", $post_type );
		}
		if ( ! empty( $linked_post_type ) && $linked_post_type != 'any' ) {
			$where[] = $wpdb->prepare( "linked_post_type = %s", $linked_post_type );
		}

		if ( empty( $where ) ) {
			return false;
		}
		$where = implode( " AND ", $where );

		if ( $wpdb->query( "DELETE FROM " . GEODIR_CP_LINK_POSTS . " WHERE {$where}" ) ) {
			do_action( 'geodir_cp_link_items_deleted', $post_id, $linked_id, $post_type, $linked_post_type );

			return true;
		}

		return false;
	}

	public static function delete_items_by( $item_key, $item_value = '' ) {
		global $wpdb;

		if ( empty( $item_key ) && empty( $item_value ) ) {
			return false;
		}

		if ( $wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_CP_LINK_POSTS . " WHERE {$item_key} = %s", $item_value ) ) ) {
			do_action( 'geodir_cp_link_items_deleted_by', $item_key, $item_value );

			return true;
		}

		return false;
	}

	public static function delete_post_links( $post_id ) {
		global $wpdb;

		if ( empty( $post_id ) ) {
			return false;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			$post_type = get_post_type( (int) wp_get_post_parent_id( $post_id ) );
		} else {
			$post_type = get_post_type( $post_id );
		}

		if ( ! geodir_is_gd_post_type( $post_type ) ) {
			return false;
		}

		if ( $wpdb->query( $wpdb->prepare( "DELETE FROM " . GEODIR_CP_LINK_POSTS . " WHERE post_id = %d OR linked_id = %d", array( $post_id, $post_id ) ) ) ) {
			do_action( 'geodir_cp_post_links_deleted', $post_id );

			return true;
		}

		return false;
	}

	public static function cf_link_posts( $html, $location, $cf, $p = '', $output = '' ) {
		// check we have the post value
		if ( is_numeric( $p ) ) {
			$gd_post = geodir_get_post_info( $p );
		} else {
			global $gd_post;
		}

		if ( empty( $gd_post ) ) {
			return $html;
		}

		if ( ! is_array( $cf ) && $cf != '' ) {
			$cf = geodir_get_field_infoby( 'htmlvar_name', $cf, $gd_post->post_type );
		}

		if ( empty( $cf['htmlvar_name'] ) ) {
			return $html;
		}

		$html_var = $cf['htmlvar_name'];

		// Check if there is a location specific filter.
		if ( has_filter( "geodir_custom_field_output_link_posts_loc_{$location}" ) ) {
			/**
			 * Filter the event field html by location.
			 *
			 * @param string $html The html to filter.
			 * @param array $cf The custom field array.
			 * @since 2.0.0
			 */
			$html = apply_filters( "geodir_custom_field_output_link_posts_loc_{$location}", $html, $cf, $gd_post );
		}

		// Check if there is a custom field specific filter.
		if ( has_filter( "geodir_custom_field_output_link_posts_var_{$html_var}" ) ) {
			/**
			 * Filter the event field  html by individual custom field.
			 *
			 * @param string $html The html to filter.
			 * @param string $location The location to output the html.
			 * @param array $cf The custom field array.
			 * @since 2.0.0
			 */
			$html = apply_filters( "geodir_custom_field_output_link_posts_var_{$html_var}", $html, $location, $cf, $gd_post );
		}

		// Check if there is a custom field key specific filter.
		if ( has_filter( "geodir_custom_field_output_link_posts_key_{$cf['field_type_key']}" ) ) {
			/**
			 * Filter the event field html by field type key.
			 *
			 * @param string $html The html to filter.
			 * @param string $location The location to output the html.
			 * @param array $cf The custom field array.
			 * @since 2.0.0
			 */
			$html = apply_filters( "geodir_custom_field_output_link_posts_key_{$cf['field_type_key']}", $html, $location, $cf, $gd_post );
		}

		if ( empty( $html ) ) {
			$title = $cf['frontend_title'] != '' ?  __( $cf['frontend_title'], 'geodirectory' ) : '';
			$output = geodir_field_output_process( $output );

			$items = self::get_items( $gd_post->ID, $cf['name'] );

			$posts = array();
			$_posts = array();
			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					if ( get_post_status( $item->linked_id ) == 'publish' ) {
						$posts[] = '<a href="' . get_permalink( $item->linked_id ) . '">' . get_the_title( $item->linked_id ) . '</a>';
					}
				}
				$_posts[] = $item->linked_id;
			}

			// Database value.
			if ( ! empty( $output ) && isset( $output['raw'] ) ) {
				return ( ! empty( $_posts ) ? implode( ',', $_posts ) : '' );
			}

			$posts = apply_filters( 'geodir_cp_cf_link_posts_' . $cf['name'], $posts, $gd_post, $location, $cf );

			if ( ! empty( $posts ) ) {
				$class = "geodir-i-link-posts";
				$value = implode( ', ', $posts );
				$value = apply_filters( 'geodir_cp_cf_link_posts_' . $cf['name'] . '_value', $value, $posts, $gd_post, $location, $cf );

				$field_icon = geodir_field_icon_proccess( $cf );

				if ( ! empty( $field_icon ) ) {
					if ( strpos( $field_icon, 'http' ) !== false ) {
						$field_icon_af = '';
					} else {
						$field_icon_af = $field_icon;
						$field_icon = '';
					}
				} else {
					$field_icon_af = '';
				}

				$html = '<div class="geodir_post_meta ' . $cf['css_class'] . ' geodir-field-' . $cf['htmlvar_name'] . '">';

				if ( $output == '' || isset( $output['icon'] ) ) {
					$html .= '<span class="geodir_post_meta_icon ' . $class . '" style="' . $field_icon . '">' . $field_icon_af;
				}
				if ( $output== '' || isset( $output['label'] ) ) {
					$html .= $title != '' ? '<span class="geodir_post_meta_title">' . $title . ': </span>' : '';
				}
				if ( $output == '' || isset( $output['icon'] ) ) {
					$html .= '</span>';
				}
				if ( $output == '' || isset( $output['value'] ) ) {
					$html .= $value;
				}

				$html .= '</div>';
			}
		}

		return $html;
	}

	public static function cfa_skip_column_add( $skip, $field ) {
		if ( $field->field_type == 'link_posts' ) {
			$skip = true;
		}

		return $skip;
	}

	public static function linked_post_condition( $linked_type, $linked_from = 0, $from_post_type = '', $linked_to = 0, $to_post_type = '', $alias = NULL ) {
		global $wpdb;

		if ( empty( $linked_from ) && empty( $from_post_type ) && empty( $linked_to ) && empty( $to_post_type ) ) {
			return '';
		}

		if ( $alias === NULL ) {
			$alias = $wpdb->posts;
		}

		if ( ! empty( $alias ) ) {
			$alias = $alias . '.';
		}

		$items = GeoDir_CP_Link_Posts::get_items( $linked_from, $to_post_type, $linked_to, $from_post_type );

		$posts = array();
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				if ( get_post_status( $item->post_id ) == 'publish' && get_post_status( $item->linked_id ) == 'publish' ) {
					if ( $linked_type == 'from' ) {
						$posts[] = $item->post_id;
					} else {
						$posts[] = $item->linked_id;
					}
				}
			}
		}

		if ( ! empty( $posts ) ) {
			$where = $wpdb->prepare("{$alias}ID IN(" . implode( ',', array_fill( 0, count( $posts ), '%d' ) ) . ")", $posts );
		} else {
			$where = "{$alias}ID = '-1'";
		}

		return $where;
	}

	public static function display_searched_params( $params, $post_type ) {
		global $aui_bs5;

		if ( ! empty( $_REQUEST['linked_to_post'] ) && ( $post_id = absint( $_REQUEST['linked_to_post'] ) ) ) {
			$htmlvar_name = 'linked_to_post';
			$value = wp_sprintf( __( 'Linked to: %s', 'geodir_custom_posts' ), get_the_title( $post_id ) );
		} elseif ( ! empty( $_REQUEST['linked_from_post'] ) && ( $post_id = absint( $_REQUEST['linked_from_post'] ) ) ) {
			$htmlvar_name = 'linked_from_post';
			$value = wp_sprintf( __( 'Linked from: %s', 'geodir_custom_posts' ), get_the_title( $post_id ) );
		} else {
			return $params;
		}

		$design_style = geodir_design_style();

		$label_class = 'gd-adv-search-label';
		if ( $design_style ) {
			if ( $aui_bs5 ) {
				$label_class .= ' badge bg-info me-2';
			} else {
				$label_class .= ' badge badge-info mr-2';
			}
		}

		if ( ! empty( $value ) ) {
			$params[] = '<label class="' . $label_class . ' gd-adv-search-default gd-adv-search-' . $htmlvar_name . '" data-name="s' . $htmlvar_name . '"><i class="fas fa-times" aria-hidden="true"></i> ' . $value . '</label>';
		}

		return $params;
	}

	public static function fill_data( $from_post_type, $from_post_id, $to_post_type, $to_post_id ) {
		if ( $from_post_id && wp_is_post_revision( $from_post_id ) ) {
			$from_post_id = wp_get_post_parent_id( $from_post_id );
		}

		if ( geodir_is_gd_post_type( $from_post_type ) && geodir_is_gd_post_type( $to_post_type ) && get_post_type( $to_post_id ) == $to_post_type ) {
			$post_types = self::linked_to_post_types( $from_post_type );

			if ( ! empty( $post_types ) && in_array( $to_post_type, $post_types ) ) {
				if ( self::valid_link_post( $to_post_id, $from_post_type, absint( get_current_user_id() ) ) ) {
					$response = self::populate_data( $from_post_type, $to_post_id );
				} else {
					$response = new WP_Error( 'geodir_cp_fill_data_error', __( 'You are now allowed to link this listing to the selected listing.', 'geodir_custom_posts' ), array( 'status' => 400 ) );
				}
			} else {
				$response = new WP_Error( 'geodir_cp_fill_data_error', wp_sprintf( __( '%s listing is not allowed to linked from %s listing.', 'geodir_custom_posts' ), geodir_post_type_singular_name( $to_post_type, true ), geodir_post_type_singular_name( $from_post_type, true ) ), array( 'status' => 400 ) );
			}
		} else {
			$response = new WP_Error( 'geodir_cp_fill_data_error', __( 'Invalid post type found to link posts.', 'geodir_custom_posts' ), array( 'status' => 404 ) );
		}

		return apply_filters( 'geodir_cp_fill_post_data', $response, $from_post_type, $from_post_id, $to_post_type, $to_post_id );
	}

	public static function populate_data( $from_post_type, $to_post_id ) {
		$data = array();

		$gd_post = geodir_get_post_info( $to_post_id );
		if ( empty( $gd_post ) ) {
			return $data;
		}

		$fields = self::get_fields( $from_post_type );
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( isset( $gd_post->{$field} ) && $gd_post->{$field} != '' ) {
					$data[ $field ] = stripslashes( $gd_post->{$field} );
				}
			}
		}

		return apply_filters( 'geodir_cp_populate_post_data', $data, $from_post_type, $to_post_id );
	}

	public static function get_fields( $post_type ) {
		$post_types = geodir_get_posttypes( 'array' );
		$post_type_array = ! empty( $post_types[ $post_type ] ) ? $post_types[ $post_type ] : array();
		$fields = isset( $post_type_array['fill_fields'] ) ? $post_type_array['fill_fields'] : self::default_fields( $post_type );

		if ( ! empty( $fields ) && in_array( 'address', $fields ) ) {
			$fields = array_merge( $fields, array( 'street', 'street2', 'neighbourhood', 'city', 'region', 'country', 'zip', 'latitude', 'longitude' ) ); // Address fields
		}

		return apply_filters( 'geodir_cp_fill_data_fields', $fields, $post_type );
	}

	public static function default_fields( $post_type ) {
		$fields = array( 'address', 'phone', 'website', 'facebook', 'twitter', 'email', 'instagram' );

		return apply_filters( 'geodir_cp_fill_default_fields', $fields, $post_type );
	}

	public static function get_field_options( $post_type ) {
		$fields = geodir_post_custom_fields( '', 'all', $post_type, 'none' );

		$options = array();
		if ( ! empty( $fields ) ) {
			foreach( $fields as $field ) {
				$skip = in_array( $field['field_type'], array( 'address', 'email', 'url', 'phone' ) ) ? false : true;

				if ( apply_filters( 'geodir_cp_fill_data_field_options_skip', $skip, $field ) ) {
					continue;
				}

				$label = ! empty( $field['admin_title'] ) ? $field['frontend_title'] : $field['admin_title'];
				$options[ $field['htmlvar_name'] ] = __( stripslashes( $label ), 'geodirectory' );
			}
		}

		return apply_filters( 'geodir_cp_fill_data_field_options', $options, $post_type );
	}

	public static function filter_post_info( $gd_post, $post_id ) {
		if ( ! empty( $gd_post ) && ! empty( $post_id ) && is_object( $gd_post ) && ! empty( $gd_post->post_type ) ) {
			$_post_types = self::linked_to_post_types( $gd_post->post_type );

			if ( ! empty( $_post_types ) ) {
				foreach ( $_post_types as $_post_type ) {
					if ( ! isset( $gd_post->{$_post_type} ) ) {
						$value = '';

						if ( $items = self::get_items( $post_id, $_post_type ) ) {
							$_value = array();
							foreach ( $items as $item ) {
								$_value[] = $item->linked_id;
							}
							$value = implode( ',', $_value );
						}

						$gd_post->{$_post_type} = $value;
					}
				}
			}
		}

		return $gd_post;
	}

	/**
	 * Filter post badge match value.
	 *
	 * @since 2.1.0.1
	 *
	 * @param string $match_value Match value.
	 * @param string $match_field Match field.
	 * @param array $args The badge parameters.
	 * @param array $find_post Post object.
	 * @param array $field The custom field array.
	 * @return string Filtered value.
	 */
	public static function post_badge_match_value( $match_value, $match_field, $args, $find_post, $field ) {
		if ( $match_field && $match_value && ! empty( $args['badge'] ) && strpos( $args['badge'], '%%input%%' ) !== false && geodir_is_gd_post_type( $match_field ) ) {
			$_match_value = ! is_array( $match_value ) ? explode( ',', $match_value ) : $match_value;

			if ( ! empty( $_match_value ) ) {
				$value = array();

				foreach ( $_match_value as $_value ) {
					$_value = trim( $_value );
					if ( (int) $_value > 0 ) {
						$value[] = '<a class="text-reset text-decoration-none" href="' . get_permalink( (int) $_value ) . '">' . get_the_title( (int) $_value ) . '</a>';
					} else {
						$value[] = $_value;
					}
				}

				$match_value = str_replace( '%%input%%', implode( '<span class="geodir-link-sep">, </span>', $value ), $args['badge'] );
			}
		}

		return $match_value;
	}

	public static function elementor_tag_text_render_value( $value, $key, $dynamic_tag ) {
		if ( $key && geodir_is_gd_post_type( $key ) ) {
			$show = $dynamic_tag->get_settings( 'show' );

			$value = do_shortcode( "[gd_post_meta key='" . $key . "' show='" . $show . "' no_wrap='1']" );
		}

		return $value;
	}

	public static function delete_cache( $post_type = '' ) {
		if ( ! empty( $post_type ) ) {
			$post_types = is_array( $post_type ) ? $post_type : array( $post_type );
		} else {
			$post_types = geodir_get_posttypes();
		}

		foreach ( $post_types as $post_type ) {
			wp_cache_delete( 'geodir_cp_linked_from_post_types:' . $post_type . ':1', 'link_post_types' );
			wp_cache_delete( 'geodir_cp_linked_from_post_types:' . $post_type . ':0', 'link_post_types' );
			wp_cache_delete( 'geodir_cp_linked_to_post_types:' . $post_type . ':1', 'link_post_types' );
			wp_cache_delete( 'geodir_cp_linked_to_post_types:' . $post_type . ':0', 'link_post_types' );
		}
	}

	public static function on_custom_field_updated( $field_id ) {
		$field = GeoDir_Settings_Cpt_Cf::get_item( $field_id );

		if ( ! empty( $field ) && geodir_is_gd_post_type( $field->htmlvar_name ) ) {
			self::delete_cache( array( $field->htmlvar_name, $field->post_type ) );
		}
	}

	public static function on_custom_field_deleted( $field_id, $htmlvar_name, $post_type ) {
		if ( geodir_is_gd_post_type( $htmlvar_name ) ) {
			self::delete_cache( array( $htmlvar_name, $post_type ) );
		}
	}
}