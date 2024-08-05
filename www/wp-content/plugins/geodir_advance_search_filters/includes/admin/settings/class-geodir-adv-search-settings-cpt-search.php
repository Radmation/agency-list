<?php
/**
 * GeoDirectory CPT Search Settings
 *
 * @author      AyeCode
 * @category    Admin
 * @package     GeoDir_Advance_Search_Filters/Admin
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'GeoDir_Adv_Search_Settings_Cpt_Search', false ) ) :

	/**
	 * GeoDir_Adv_Search_Settings_Cpt_Search class.
	 */
	class GeoDir_Adv_Search_Settings_Cpt_Search extends GeoDir_Settings_Page {

		/**
		 * Post type.
		 *
		 * @var string
		 */
		private static $post_type = '';

		/**
		 * Sub tab.
		 *
		 * @var string
		 */
		private static $sub_tab = '';

		/**
		 * Constructor.
		 */
		public function __construct() {

			self::$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_title( $_REQUEST['post_type'] ) : 'gd_place';
			self::$sub_tab   = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : 'general';


			$this->id    = 'cpt-search';
			$this->label = __( 'Search', 'geodiradvancesearch' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );

			add_action( 'geodir_adv_search_cpt_settings_search_fields', array( $this, 'output_standard_fields' ), 10, 1 );
			add_filter( 'geodir_search_fields_setting_allow_var_post_title', '__return_false' );
			add_filter( 'geodir_search_fields_setting_allow_var_post_content', '__return_false' );
			add_filter( 'geodir_search_fields_setting_allow_var_service_distance', '__return_false' );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				'' => __( 'Search', 'geodiradvancesearch' )
			);

			return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $hide_save_button;

			$hide_save_button = true;

			$listing_type = self::$post_type;

			$sub_tab = self::$sub_tab;

			include( GEODIRECTORY_PLUGIN_DIR . 'includes/admin/views/html-admin-settings-cpt-cf.php' );
		}


		/**
		 * Returns heading for the CPT settings left panel.
		 *
		 * @since 2.0.0
		 *
		 * @return string The page heading.
		 */
		public static function left_panel_title() {
			return sprintf( __( 'Fields', 'geodiradvancesearch' ), get_post_type_singular_label( self::$post_type, false, true ) );

		}

		/**
		 * Returns description for given sub tab - available fields box.
		 *
		 * @since 2.0.0
		 *
		 * @return string The box description.
		 */
		public function left_panel_note() {
			return sprintf( __( 'Select what fields to show in the search form.', 'geodiradvancesearch' ), get_post_type_singular_label( self::$post_type, false, true ) );
		}

		/**
		 * Output the admin settings cpt sorting left panel content.
		 *
		 * @since 2.0.0
		 */
		public function left_panel_content() {
			?>
			<div class="inside">

				<div id="gd-form-builder-tab" class="gd-form-builder-tab gd-tabs-panel">
					<?php
					/**
					 * Adds the available fields to the custom fields settings page per post type.
					 *
					 * @since 2.0.0
					 *
					 * @param string $sub_tab The current settings tab name.
					 */
					do_action( 'geodir_adv_search_cpt_settings_search_fields', self::$sub_tab ); ?>

					<div style="clear:both"></div>

				</div>
			</div>
			<?php

		}


		/**
		 * Returns heading for the CPT settings left panel.
		 *
		 * @since 2.0.0
		 *
		 * @return string The page heading.
		 */
		public static function right_panel_title() {
			return sprintf( __( 'Search fields', 'geodiradvancesearch' ), get_post_type_singular_label( self::$post_type, false, true ) );
		}

		/**
		 * Returns description for given sub tab - available fields box.
		 *
		 * @since 2.0.0
		 *
		 * @return string The box description.
		 */
		public function right_panel_note() {
			return sprintf( __( 'Click a field to change its settings. Drag and drop a filed to change its order.', 'geodiradvancesearch' ), get_post_type_singular_label( self::$post_type, false, true ) );
		}

		/**
		 * Output the admin cpt settings fields left panel content.
		 *
		 * @since 2.0.0
		 *
		 */
		public function right_panel_content() {
			global $aui_bs5;

			?>
			<form></form> <!-- chrome removes the first form inside a form for some reason so we need this ?> -->
			<div class="inside">
				<div id="gd-form-builder-tab" class="gd-form-builder-tab gd-tabs-panel">
					<div class="field_row_main">
						<div class="dd gd-tabs-layout" >
							<ul class="dd-list gd-tabs-sortable <?php echo ( $aui_bs5 ? 'ps-0 list-group ui-sortable' : '' ); ?>">
								<?php
								$fields = GeoDir_Adv_Search_Fields::get_search_fields( self::$post_type );

								if ( ! empty( $fields ) ) {
									echo self::loop_fields_output($fields);
								} else {
									echo "<li class='gd-sort-placeholder alert alert-info'><i class=\"fas fa-info-circle text-white\"></i> ".__( 'Select a field from the left to get started.', 'geodiradvancesearch' )."</li>";
								}
								?>
							</ul>
						</div>
					</div>
					<div style="clear:both"></div>
				</div>

			</div>
			<?php
		}

		public static function loop_fields_output( $fields ) {
			ob_start();

			foreach ( $fields as $field ) {
				echo GeoDir_Adv_Search_Settings_Cpt_Search::get_field( $field );
			}

			return ob_get_clean();
		}

		/**
		 * Check if the field already exists.
		 *
		 * @param $field
		 *
		 * @return WP_Error
		 */
		public static function field_exists( $htmlvar_name, $post_type ) {
			global $wpdb;

			$check_html_variable = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT htmlvar_name FROM " . GEODIR_ADVANCE_SEARCH_TABLE . " WHERE htmlvar_name = %s AND post_type = %s",
					array( $htmlvar_name, $post_type )
				)
			);

			return $check_html_variable;
		}

		/**
		 * Get the sort order if not set.
		 *
		 * @return int
		 */
		public static function default_sort_order() {
			global $wpdb;
			$last_order = $wpdb->get_var("SELECT MAX(sort_order) AS last_order FROM " . GEODIR_ADVANCE_SEARCH_TABLE);

			return (int)$last_order + 1;
		}

		/**
		 * Sanatize the custom field
		 *
		 * @param array/object $input {
		 *    Attributes of the request field array.
		 *
		 *    @type string $action Ajax Action name. Default "geodir_ajax_action".
		 *    @type string $manage_field_type Field type Default "custom_fields".
		 *    @type string $create_field Create field Default "true".
		 *    @type string $field_ins_upd Field ins upd Default "submit".
		 *    @type string $_wpnonce WP nonce value.
		 *    @type string $listing_type Listing type Example "gd_place".
		 *    @type string $field_type Field type Example "radio".
		 *    @type string $field_id Field id Example "12".
		 *    @type string $data_type Data type Example "VARCHAR".
		 *    @type string $is_active Either "1" or "0". If "0" is used then the field will not be displayed anywhere.
		 *    @type array $show_on_pkg Package list to display this field.
		 *    @type string $admin_title Personal comment, it would not be displayed anywhere except in custom field settings.
		 *    @type string $frontend_title Section title which you wish to display in frontend.
		 *    @type string $frontend_desc Section description which will appear in frontend.
		 *    @type string $htmlvar_name Html variable name. This should be a unique name.
		 *    @type string $clabels Section Title which will appear in backend.
		 *    @type string $default_value The default value (for "link" this will be used as the link text).
		 *    @type string $sort_order The display order of this field in backend. e.g. 5.
		 *    @type string $is_default Either "1" or "0". If "0" is used then the field will be displayed as main form field or additional field.
		 *    @type string $for_admin_use Either "1" or "0". If "0" is used then only site admin can edit this field.
		 *    @type string $is_required Use "1" to set field as required.
		 *    @type string $required_msg Enter text for error message if field required and have not full fill requirement.
		 *    @type string $show_in What locations to show the custom field in.
		 *    @type string $show_as_tab Want to display this as a tab on detail page? If "1" then "Show on detail page?" must be Yes.
		 *    @type string $option_values Option Values should be separated by comma.
		 *    @type string $field_icon Upload icon using media and enter its url path, or enter font awesome class.
		 *    @type string $css_class Enter custom css class for field custom style.
		 *    @type array $extra_fields An array of extra fields to store.
		 *
		 * }
		 */
		private static function sanatize_field( $input ) {
			// if object convert to array
			if ( is_object( $input ) ) {
				$input = json_decode( json_encode( $input ), true );
			}

			$field = new stdClass();

			// sanatize
			$field->field_id = isset( $input['field_id'] ) ? absint( $input['field_id'] ) : '';
			$field->post_type = isset( $input['post_type'] ) ? sanitize_text_field( $input['post_type'] ) : '';
			$field->htmlvar_name = isset( $input['htmlvar_name'] ) ? $input['htmlvar_name'] : '';
			$field->frontend_title = isset( $input['frontend_title'] ) ? sanitize_text_field( $input['frontend_title'] ) : '';
			$field->admin_title = isset( $input['admin_title'] ) ? sanitize_text_field( $input['admin_title'] ) : '';
			$field->description = isset( $input['description'] ) ? sanitize_text_field( $input['description'] ) : '';
			$field->field_type = isset( $input['field_type'] ) ? sanitize_text_field( $input['field_type'] ) : '';
			$field->input_type = isset( $input['input_type'] ) ? sanitize_text_field( $input['input_type'] ) : '';
			$field->data_type = isset( $input['data_type'] ) ? sanitize_text_field( $input['data_type'] ) : '';
			$field->search_condition = isset( $input['search_condition'] ) ? sanitize_text_field( $input['search_condition'] ) : '';
			$field->range_expand = isset( $input['range_expand'] ) ? sanitize_text_field( $input['range_expand'] ) : '';
			$field->range_mode = isset( $input['range_mode'] ) ? sanitize_text_field( $input['range_mode'] ) : '';
			$field->expand_search = isset( $input['expand_search'] ) ? sanitize_text_field( $input['expand_search'] ) : '';
			$field->range_start = isset( $input['range_start'] ) ? sanitize_text_field( $input['range_start'] ) : '';
			$field->range_min = isset( $input['range_min'] ) ? sanitize_text_field( $input['range_min'] ) : '';
			$field->range_max = isset( $input['range_max'] ) ? sanitize_text_field( $input['range_max'] ) : '';
			$field->range_step = isset( $input['range_step'] ) ? sanitize_text_field( $input['range_step'] ) : '';
			$field->range_from_title = isset( $input['range_from_title'] ) ? sanitize_text_field( $input['range_from_title'] ) : '';
			$field->range_to_title = isset( $input['range_to_title'] ) ? sanitize_text_field( $input['range_to_title'] ) : '';
			$field->main_search = isset( $input['main_search'] ) ? sanitize_text_field( $input['main_search'] ) : '';
			$field->main_search_priority = isset( $input['main_search_priority'] ) ? sanitize_text_field( $input['main_search_priority'] ) : '';
			$field->sort_order = isset( $input['sort_order'] ) ? absint( $input['sort_order'] ) : self::default_sort_order();

			// Extra fields
			$extra_fields = array();
			if ( isset( $input['search_asc_title'] ) ) {
				$extra_fields['is_sort'] = isset( $input['geodir_distance_sorting'] ) ? (int) $input['geodir_distance_sorting'] : '';
				$extra_fields['asc'] = isset( $input['search_asc'] ) ? (int) $input['search_asc'] : '';
				$extra_fields['asc_title'] = isset( $input['search_asc_title'] ) ? sanitize_text_field( $input['search_asc_title'] ) : '';
				$extra_fields['desc'] = isset( $input['search_desc'] ) ? (int) $input['search_desc'] : '';
				$extra_fields['desc_title'] = isset( $input['search_desc_title'] ) ? sanitize_text_field( $input['search_desc_title'] ) : '';
			}
			if ( isset( $input['search_operator'] ) ) {
				$extra_fields['search_operator'] = $input['search_operator'] == 'OR' ? 'OR' : 'AND';
			}
			if ( isset( $input['distance_label'] ) ) {
				$extra_fields['distance_label'] = sanitize_text_field( $input['distance_label'] );
			}

			$field->extra_fields = ! empty( $extra_fields ) ? maybe_serialize( $extra_fields ) : '';

            if ( isset( $input['data_type_change'] ) && ( $input['data_type_change'] == 'SELECT' || ( $input['data_type_change'] == 'TEXT' && $field->search_condition == 'FROM' ) ) ) {
				$field->input_type = 'RANGE';
			}

			if ( $field->range_step != 1 ) {
                $field->range_mode = 0;
            }
            if ( $field->htmlvar_name == 'distance' ) {
                $field->input_type = 'RANGE';
                $field->search_condition = 'RADIO';
            }
	
			$field->data_type = self::sanitize_data_type( $field->field_type, $field->data_type );
			$field->input_type = self::sanitize_data_type( $field->field_type, $field->input_type );
			if ( empty( $field->htmlvar_name ) ) {
				// we use original input so the special chars are no converted already
				$field->htmlvar_name = self::generate_html_var( $input['frontend_title'] );
			}

			if ( $field->field_type == 'fieldset' && $field->htmlvar_name == 'fieldset' ) {
				$field->htmlvar_name = 'fieldset_' . time();
			}

			return apply_filters( 'geodir_search_cf_sanatize_custom_field', $field, $input );
		}

		/**
		 * Sanatize data type.
		 *
		 * Sanatize option values.
		 * @param $value
		 *
		 * @return mixed
		 */
		private static function sanitize_data_type( $field_type, $data_type = '' ) {
			$value = 'VARCHAR';

			if ( $data_type == '') {
				switch ( $data_type ) {
					case 'checkbox':
						$value = 'TINYINT';
						break;
					case 'textarea':
					case 'html':
					case 'url':
					case 'file':
						$value = 'TEXT';
						break;
					default:
						$value = 'VARCHAR';
				}

			} else {
				// Strip X if first character, this is added as some servers will flag security rules if a data type is posted via form.
				$value = ltrim( $data_type, 'X' );
			}

			return sanitize_text_field( $value );
		}

		/**
		 * Save the custom field.
		 *
		 * @param array $field
		 *
		 * @return int|string
		 */
		public static function save_field( $field = array() ) {
			global $wpdb, $plugin_prefix;

			$field = self::sanatize_field( $field );

			$db_data = array(
				'post_type' => $field->post_type,
				'htmlvar_name' => $field->htmlvar_name,
				'frontend_title' => $field->frontend_title,
				'admin_title' => $field->admin_title,
				'description' => $field->description,
				'field_type' => $field->field_type,
				'data_type' => $field->data_type,
				'input_type' => $field->input_type,
				'search_condition' => $field->search_condition,
				'range_expand' => $field->range_expand,
				'range_mode' => $field->range_mode,
				'expand_search' => $field->expand_search,
				'range_start' => $field->range_start,
				'range_min' => $field->range_min,
				'range_max' => $field->range_max,
				'range_step' => $field->range_step,
				'range_from_title' => $field->range_from_title,
				'range_to_title' => $field->range_to_title,
				'main_search' => $field->main_search,
				'main_search_priority' => $field->main_search_priority,
				'sort_order' => $field->sort_order,
				'extra_fields' => $field->extra_fields,
			);

			$db_format = array(
				'%s', // post_type
				'%s', // htmlvar_name
				'%s', // frontend_title
				'%s', // admin_title
				'%s', // description
				'%s', // field_type
				'%s', // data_type
				'%s', // input_type
				'%s', // search_condition
				'%d', // range_expand
				'%d', // range_mode
				'%d', // expand_search
				'%d', // range_start
				'%d', // range_min
				'%d', // range_max
				'%d', // range_step
				'%s', // range_from_title
				'%s', // range_to_title
				'%d', // main_search
				'%d', // main_search_priority
				'%d', // sort_order
				'%s', // extra_fields
			);			

			if ( ! empty( $field->field_id ) ) {
				// Update the field settings.
				$result = $wpdb->update(
					GEODIR_ADVANCE_SEARCH_TABLE,
					$db_data,
					array( 'id' => $field->field_id ),
					$db_format
				);

				if ( $result === false ) {
					return new WP_Error( 'failed', __( "Field update failed.", "geodiradvancesearch" ) );
				}
			} else {
				// Insert the field settings.
				$result = $wpdb->insert(
					GEODIR_ADVANCE_SEARCH_TABLE,
					$db_data,
					$db_format
				);

				if ( $result === false ) {
					return new WP_Error( 'failed', __( "Field create failed.", "geodiradvancesearch" ) );
				} else {
					$field->field_id = $wpdb->insert_id;
				}
			}

			/**
			 * Called after all custom sort fields are saved for a post.
			 *
			 * @since 1.0.0
			 * @param int $lastid The post ID.
			 */
			do_action( 'geodir_after_custom_sort_fields_updated', $field->field_id );

			return $field->field_id;
		}

        /**
         * Blank all defaults for a post type.
         *
         * @since 2.0.0
         *
         * @global object $wpdb WordPress Database object.
         *
         * @param $post_type Post type value.
         */
		public static function blank_default($post_type){
			global $wpdb;

			$wpdb->query($wpdb->prepare("update " . GEODIR_ADVANCE_SEARCH_TABLE . " set is_default='0' where post_type = %s", array($post_type)));
		}

		/**
         * Delete a field.
         *
         * @since 2.0.0
         *
         * @global object $wpdb WordPress Database object.
         *
         * @param int $id Field id.
         * @param string $post_type posttype.
         * @return bool|WP_Error.
         */
		public static function delete_field( $id, $post_type = '' ) {
			global $wpdb;

			if ( ! empty( $id ) ) {
				$where = array(
					'id' => $id
				);
				$format = array( 
					'%d' 
				);

				if ( $post_type ) {
					$where['post_type'] = $post_type;
					$format[] = "%s";
				}

				$result = $wpdb->delete( GEODIR_ADVANCE_SEARCH_TABLE, $where, $format );

				if ( $result !== false ) {
					return true;
				} else {
					return new WP_Error( 'failed', __( "Failed to delete search item.", "geodiradvancesearch" ) );
				}
			} else {
				return new WP_Error( 'failed', __( "Failed to delete search item.", "geodiradvancesearch" ) );
			}
		}

		/**
		 * Set custom field order
		 *
		 * @since 2.0.0
		 *
		 * @global object $wpdb WordPress Database object.
		 * @param array $field_ids List of field ids.
		 * @return array|bool Returns field ids when success, else returns false.
		 */
		public static function set_fields_order( $fields = array() ){
			global $wpdb;

			$count = 0;
			if ( ! empty( $fields ) ) {
				$result = false;
				foreach ( $fields as $index => $info ) {
					$result = $wpdb->update(
						GEODIR_ADVANCE_SEARCH_TABLE,
						array(
							'sort_order' => $index,
							'tab_parent' => (int) $info['tab_parent'],
							'tab_level' => (int) $info['tab_level'],
						),
						array(
							'id' => absint( $info['id'] )
						),
						array(
							'%d',
							'%d',
							'%d'
						)
					);
					$count++;
				}

				if ( $result !== false ) {
					return true;
				} else {
					return new WP_Error( 'failed', __( "Failed to sort field items.", "geodiradvancesearch" ) );
				}
			} else {
				return new WP_Error( 'failed', __( "Failed to sort field items.", "geodiradvancesearch" ) );
			}
		}

		/**
		 * Adds admin html for custom fields available fields.
		 *
		 * @since 1.0.0
		 *
		 * @param string $type The custom field type, predefined, custom or blank for default
		 */
		public function output_standard_fields() {
			$cfs = self::get_standard_fields();

			self::output_fields($cfs);
		}

		/**
		 * Output the tab fields to be selected.
		 *
		 * @param $cfs
		 */
		public function output_fields( $fields ) {
			global $aui_bs5;

			if ( ! empty( $fields ) ) {
				echo '<ul class="row row-cols-2 px-2">';
				foreach ( $fields as $key => $field ) {
					$field = stripslashes_deep( $field );

					$display = $field['htmlvar_name'] != 'fieldset' && self::field_exists( $field['htmlvar_name'], self::$post_type ) ? ' style="display:none;"' : '';

					if ( isset( $field['field_icon'] ) && geodir_is_fa_icon( $field['field_icon'] ) ) {
						$field_icon = '<i class="' . esc_attr($field['field_icon']) . '" aria-hidden="true"></i>';
					} elseif ( isset( $field['field_icon'] ) && geodir_is_icon_url( $field['field_icon'] ) ) {
						$field_icon = '<b style="background-image: url("' . esc_attr($field['field_icon']) . '")"></b>';
					} else {
						$field_icon = '<i class="fas fa-cog" aria-hidden="true"></i>';
					}
					?>
					<li id="geodir-field-item-<?php echo $field['htmlvar_name']; ?>" class="col px-1" <?php echo $display; ?>>
						<a id="gd-<?php echo $field['htmlvar_name']; ?>"
						   class="gd-draggable-form-items gd-fieldset btn btn-sm d-block m-0 btn-outline-info text-dark <?php echo ( $aui_bs5 ? 'text-start' : 'text-left' ); ?>"
						   href="javascript:void(0);" 
						   data-htmlvar_name="<?php echo esc_attr( $field['htmlvar_name'] ); ?>" 
						   data-field_type="<?php echo esc_attr( $field['field_type'] ); ?>" 
						   data-data_type="<?php echo esc_attr( $field['data_type'] ); ?>" 
						   data-field_icon="<?php echo esc_attr( $field['field_icon'] ); ?>" 
						   onclick="geodir_adv_search_add_field(this);">
						   <?php echo $field_icon; ?> 
						   <?php echo ! empty( $field['admin_title'] ) ? $field['admin_title'] : $field['frontend_title']; ?>
						</a>
					</li>
					<?php
				}
				echo '</ul>';
			} else {
				_e( 'There are no custom fields here yet.', 'geodiradvancesearch' );
			}
		}

		/**
         * Get predefined fields.
         *
         * @since 2.0.0
         *
         * @return array $fields.
         */
		public static function get_predefined_fields( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = self::$post_type;
			}

			$fields = array();
			$fields[] = array(
				'field_type' => 'fieldset',
				'frontend_title' => '',
				'admin_title' => __( 'Fieldset (section separator)', 'geodiradvancesearch' ),
				'htmlvar_name' => 'fieldset',
				'data_type' => 'VARCHAR',
				'field_icon' => 'fas fa-arrows-alt-h'
			);
			$fields[] = array(
				'field_type' => 'distance',
				'frontend_title' => __( 'Search By Distance', 'geodiradvancesearch' ),
				'admin_title' => __( 'Search By Distance', 'geodiradvancesearch' ),
				'htmlvar_name' => 'distance',
				'input_type' => 'RANGE',
				'data_type' => 'FLOAT',
				'search_condition' => 'RADIO',
				'field_icon' => 'fas fa-map-marker-alt'
			);

			$classified_features = geodir_get_classified_statuses( $post_type );
			if ( isset( $classified_features['gd-sold'] ) ) {
				$fields[] = array(
					'field_type' => 'checkbox',
					'frontend_title' => __( 'Include Sold', 'geodiradvancesearch' ),
					'admin_title' => __( 'Include Sold', 'geodiradvancesearch' ),
					'htmlvar_name' => '_sold',
					'input_type' => 'SINGLE',
					'data_type' => 'TINYINT',
					'search_condition' => 'SINGLE',
					'field_icon' => 'fas fa-lock'
				);
			}

			return apply_filters( 'geodir_advance_search_cpt_predefined_fields', $fields, $post_type );
		}

		/**
         * Get standard fields.
         *
         * @since 2.0.0
         *
         * @global object $wpdb WordPress Database object.
         *
         * @return array $fields.
         */
		public function get_standard_fields() {
			$fields = self::get_predefined_fields();

			$search_fields = GeoDir_Adv_Search_Fields::get_search_custom_fields( self::$post_type );

			if ( ! empty( $search_fields ) ) {
				foreach( $search_fields as $key => $field ) {
					$fields[] = array(
						'field_type' => $field->field_type,
						'frontend_title' => $field->frontend_title,
						'admin_title' => $field->admin_title,
						'htmlvar_name' => $field->htmlvar_name,
						'data_type' => $field->data_type,
						'field_icon' => $field->field_icon,
					);
				}
			}

			return apply_filters( 'geodir_advance_search_cpt_standard_fields', $fields, self::$post_type );
		}

		/**
		 * Get the field item by id.
		 *
		 * @since 2.0.0
		 */
		public static function get_field( $field ) {
			global $wpdb;

			if ( empty( $field ) ) {
				return NULL;
			}

			if ( is_int( $field ) ) {
				$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_ADVANCE_SEARCH_TABLE . " WHERE id = %d", array( (int) $field ) ) );
			} else {
				$field = (object) $field;
			}

			if ( empty( $field->post_type ) ) {
				return NULL;
			}

			$cf = GeoDir_Adv_Search_Settings_Cpt_Search::get_custom_field_by_name( $field->htmlvar_name, $field->post_type );
			if ( ! empty( $cf ) && $cf->htmlvar_name == 'business_hours' ) {
				$cf->admin_title = __( 'Open Hours', 'geodiradvancesearch');
				$cf->frontend_title = __( 'Open Hours', 'geodiradvancesearch');
			}
			
			if ( empty( $cf ) ) {
				$predefined_fields = GeoDir_Adv_Search_Settings_Cpt_Search::get_predefined_fields( $field->post_type );

				if ( ! empty( $predefined_fields ) ) {
					foreach ( $predefined_fields as $predefined_field ) {
						if ( $predefined_field['htmlvar_name'] == $field->htmlvar_name ) {
							$cf = $predefined_field;
						}
					}
				}
			}

			return self::get_field_html( $field, (array) $cf );
		}

		/**
		 * Get the field html.
		 *
		 * @since 2.0.0
		 */
		public static function get_field_html( $field, $cf = array() ) {
			$defaults = array(
				'id' => '',
				'post_type' => self::$post_type,
				'htmlvar_name' => '',
				'frontend_title' => '',
				'admin_title' => '',
				'description' => '',
				'field_type' => '',
				'input_type' => '',
				'data_type' => '',
				'search_condition' => '',
				'range_expand' => '',
				'range_mode' => '',
				'expand_search' => '',
				'range_start' => '',
				'range_min' => '',
				'range_max' => '',
				'range_step' => '',
				'range_from_title' => '',
				'range_to_title' => '',
				'main_search' => '',
				'main_search_priority' => '',
				'sort_order' => '',
				'extra_fields' => '',
			);
			$field = (object) wp_parse_args( (array) $field, $defaults );

			if ( empty( $field->htmlvar_name ) && ! empty( $cf['htmlvar_name'] ) ) {
				$field->htmlvar_name = $cf['htmlvar_name'];
			}
			if ( empty( $field->frontend_title ) && ! empty( $cf['frontend_title'] ) ) {
				$field->frontend_title = $cf['frontend_title'];
			}
			if ( empty( $field->admin_title ) && ! empty( $cf['admin_title'] ) ) {
				$field->admin_title = $cf['admin_title'];
			}
			if ( empty( $field->field_type ) && ! empty( $cf['field_type'] ) ) {
				$field->field_type = $cf['field_type'];
			}
			if ( empty( $field->data_type ) && ! empty( $cf['data_type'] ) ) {
				$field->data_type = $cf['data_type'];
			}
			if ( empty( $field->input_type ) && ! empty( $cf['input_type'] ) ) {
				$field->input_type = $cf['input_type'];
			}
			if ( empty( $field->search_condition ) && ! empty( $cf['search_condition'] ) ) {
				$field->search_condition = $cf['search_condition'];
			}
			if ( ! empty( $field->extra_fields ) ) {
				$field->extra_fields = maybe_unserialize( $field->extra_fields );
			}

			if ( $field->data_type == 'DECIMAL' ) {
				$field->data_type = 'FLOAT';
			}
			if ( $field->field_type == 'datepicker' ) {
				$field->data_type = 'DATE';
			}
			if ( $field->field_type == 'time' ) {
				$field->data_type = 'TIME';
			}

			if ( empty( $field->search_condition ) ) {
				if ( $field->data_type == 'DATE' || $field->data_type == 'TIME') {
					$field->search_condition = "SINGLE";
					$field->input_type = "DATE";
				} else if ( $field->data_type == 'INT' ) {
					$field->search_condition = "SELECT";
					$field->input_type = "RANGE";
				} else if ( $field->field_type == 'categories' || $field->field_type == 'select' ) {
					$field->search_condition = "SINGLE";
					$field->input_type = "SELECT";
				} else {
					$field->search_condition = "SINGLE";
					$field->input_type = "SINGLE";
				}
			}

			$checkbox_fields = GeoDir_Adv_Search_Fields::checkbox_fields( $field->post_type );

			// Show special offers, video as a checkbox field.
			if ( ! empty( $checkbox_fields ) && in_array( $field->htmlvar_name, $checkbox_fields ) ) {
				$field->field_type = 'checkbox';
				$field->input_type = 'SINGLE';
				$field->data_type = 'TEXT';
				$field->search_condition = 'SINGLE';
			}

			if ( empty( $field->input_type ) ) {
				if ( $field->data_type == 'DATE' || $field->data_type == 'TIME') {
					$field->input_type = "DATE";
				} else if ( $field->data_type == 'INT' ) {
					$field->input_type = "RANGE";
				} else if ( $field->field_type == 'categories' || $field->field_type == 'select') {
					$field->input_type = "SELECT";
				} else {
					$field->input_type = "SINGLE";
				}
			}

			$field = stripslashes_deep( $field );
			$field = apply_filters( 'geodir_search_cpt_search_setting_field', $field, $cf );
			$nonce = wp_create_nonce( 'custom_fields_' . $field->id );

			if ( $field->field_type == 'fieldset' ) {
				$cf['field_icon'] = 'fas fa-arrows-alt-h';
			}

			if ( isset( $cf['field_icon'] ) && geodir_is_fa_icon( $cf['field_icon'] ) ) {
				$field_icon = '<i class="' . $cf['field_icon'] . '" aria-hidden="true"></i>';
			} elseif ( isset( $cf['field_icon'] ) && geodir_is_icon_url( $cf['field_icon'] ) ) {
				$field_icon = '<b style="background-image: url("' . $cf['field_icon'] . '")"></b>';
			} else {
				$field_icon = '<i class="fas fa-cog" aria-hidden="true"></i>';
			}

			$key = $field->htmlvar_name . rand( 5, 500 );

			ob_start();

			include( dirname( __FILE__ ) . '/../views/html-admin-settings-cpt-search-setting-item.php' );

			return ob_get_clean();
		}

		/**
         * Get predefined fields.
         *
         * @since 2.0.0
         *
         * @return array $fields.
         */
		public static function get_custom_field_by_name( $htmlvar_name, $post_type = '' ) {
			global $wpdb;

			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE htmlvar_name = %s AND post_type = %s LIMIT 1", array( $htmlvar_name, $post_type ) ) );
		}

		public static function generate_html_var( $title ) {
			return str_replace( array( '-', ' ', '"', "'" ), array( '_', '', '', '' ), sanitize_title_with_dashes( $title ) );
		}
	}

endif;

return new GeoDir_Adv_Search_Settings_Cpt_Search();
