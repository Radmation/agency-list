<?php
/**
 * Conditional Search Fields Admin class
 *
 * @package GeoDir_Advance_Search_Filters
 * @since 2.2.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GeoDir_Adv_Search_Admin_Conditional_Fields', false ) ) {

/**
 * GeoDir_Adv_Search_Admin_Conditional_Fields Class.
 */
class GeoDir_Adv_Search_Admin_Conditional_Fields {

	/**
	 * Current post type.
	 *
	 * @var string
	 */
	private static $post_type = '';

	/**
	 * Current field.
	 *
	 * @var object
	 */
	private static $field = array();

	public function __construct() {
		add_action( 'geodir_search_cfa_before_save_button', array( $this, 'conditional_fields_setting' ), 1, 4 );
		add_filter( 'geodir_search_cf_sanatize_custom_field', array( $this, 'save_conditional_fields' ), 11, 2 );
		add_filter( 'geodir_search_cf_show_conditional_fields_setting_desc', array( $this, 'show_extra_description' ), 11, 3 );
		add_filter( 'geodir_search_cfa_tab_header_icon', array( $this, 'show_conditional_icon' ), 10, 2 );
	}

	/**
	 * Conditional field settings.
	 *
	 * @since 2.2.5
	 *
	 * @param string $field_type Field type.
	 * @param object $field Current field object.
	 * @param array  $data Current field data.
	 * @param string  $data Field key.
	 * @return array The settings.
	 */
	public function conditional_fields_setting( $field_type, $field, $data, $key ) {
		global $aui_bs5;

		if ( empty( $field->post_type ) ) {
			return;
		}
		$post_type = $field->post_type;

		self::$post_type = $post_type;
		self::$field = $field;

		$hide = false;
		/**
		 * Filter to show/hide conditional field settings.
		 *
		 * @since 2.2.5
		 *
		 * @param bool   $hide True to hide.
		 * @param string $post_type Current post type.
		 * @param object $field Current field object.
		 * @param array  $data Current field data.
		 */
		$hide = apply_filters( 'geodir_search_cf_show_conditional_fields_setting', $hide, $post_type, $field, $data );

		if ( $hide ) {
			return;
		}

		$extra_fields = ! empty( $field->extra_fields ) ? $field->extra_fields : array();
		$conditions = geodir_parse_field_conditions( $extra_fields );

		$count = count($conditions);
		$count_badge = $count ?  '<span class="badge ' . ( $aui_bs5 ? 'bg-warning ms-2' : 'badge-warning ml-2' ) . '">' . absint( $count ) . '</span>' : '';
		?>
		<div class="aui-conditional-field geodir-con-fields-hidden border-top mt-4 pt-4" data-setting="conditional_fields_heading" data-element-require="!jQuery(form).find('#gd_main_search<?php echo esc_attr( $key ); ?>:checked').length">
			<a href="#geodir_conditional_fields" data-<?php echo $aui_bs5 ? 'bs-' : ''; ?>toggle="collapse" class="btn d-block btn-outline-primary"><span class="geodir-show-cf"><i class="fas fa-plus" aria-hidden="true"></i></span><span class="geodir-hide-cf"><i class="fas fa-minus" aria-hidden="true"></i></span> <?php _e( 'Conditional Fields', 'geodiradvancesearch' ); echo $count_badge; ?></a>
		</div>
		<div class="collapse" data-setting="conditional_fields" id="geodir_conditional_fields">
			<p data-setting="conditional_fields_desc" class="pt-2"><?php _e( 'Setup conditional logic to show/hide this field in the advance search filters form based on specific fields value or conditions.', 'geodiradvancesearch' ); ?></p>
			<?php 
			/**
			 * Show conditional field extra message.
			 *
			 * @since 2.2.5
			 *
			 * @param string $post_type Current post type.
			 * @param object $field Current field object.
			 * @param array  $data Current field data.
			 */
			do_action( 'geodir_search_cf_show_conditional_fields_setting_desc', $post_type, $field, $data );
			$bs5_class = $aui_bs5 ? 'rounded-0 border-start-0' : 'border-left-0';

			$if = '<div class="' . ( $aui_bs5 ? '' : 'input-group-prepend' ) . '"><span class="input-group-text px-2 ' . $bs5_class . '">'.__( 'if', 'geodiradvancesearch' ) . '</span></div>';
			?>
			<div class="geodir-conditional-template">
				<div class="geodir-conditional-row  input-group input-group-sm mb-2" data-condition-index="TEMP">
					<?php 
					echo $this->get_field( 'action' );
					echo $if;
					echo $this->get_field( 'field' );
					echo $this->get_field( 'condition' );
					echo $this->get_field( 'value' );
					echo $this->get_field( 'remove' );
					?>
				</div>
			</div>
			<div class="geodir-conditional-items">
				<?php if ( ! empty( $conditions ) ) { ?>
					<?php foreach ( $conditions as $k => $condition ) { ?>
						<div class="geodir-conditional-row  input-group input-group-sm mb-2" data-condition-index="<?php echo esc_attr( $k ); ?>">
							<?php 
							echo $this->get_field( 'action', $condition['action'], $k );
							echo $if;
							echo $this->get_field( 'field', $condition['field'], $k );
							echo $this->get_field( 'condition', $condition['condition'], $k );
							echo $this->get_field( 'value', $condition['value'], $k );
							echo $this->get_field( 'remove', '', $k );
							?>
						</div>
					<?php } ?>
				<?php } else { ?>
				<div class="geodir-conditional-row  input-group input-group-sm mb-2" data-condition-index="0">
					<?php 
					echo $this->get_field( 'action', '', 0 );
					echo $if;
					echo $this->get_field( 'field', '', 0 );
					echo $this->get_field( 'condition', '', 0 );
					echo $this->get_field( 'value', '', 0 );
					echo $this->get_field( 'remove', '', 0 );
					?>
				</div>
				<?php } ?>
			</div>
			<p data-setting="conditional_fields_add" class="pt-1 <?php echo ( $aui_bs5 ? 'text-end' : 'text-right' ); ?>">
				<a href="javascript:void(0);" class="btn btn-primary btn-sm geodir-conditional-add"><i class="fas fa-plus-circle"></i> <?php _e( 'Add Rule', 'geodiradvancesearch' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Get the conditional field HTML.
	 *
	 * @since 2.2.5
	 *
	 * @param string      $key Field key.
	 * @param string      $value Field value.
	 * @param string|int  $index Field index.
	 * @return string Conditional field HTML.
	 */
	public function get_field( $key, $value = '', $index = 'TEMP' ) {
		global $aui_bs5;

		ob_start();
		switch ( $key ) {
			case 'action':
				?><select class="geodir-conditional-el geodir-conditional-<?php echo $key; ?> form-control" name="conditional_fields[<?php echo $index; ?>][action]" id="conditional_action_<?php echo $index; ?>">
					<option value=""><?php _e( 'ACTION', 'geodiradvancesearch' ); ?></option>
					<option value="show" <?php selected( $value == 'show', true ); ?>><?php _e( 'show', 'geodiradvancesearch' ); ?></option>
					<option value="hide" <?php selected( $value == 'hide', true ); ?>><?php _e( 'hide', 'geodiradvancesearch' ); ?></option>
				</select><?php
				break;
			case 'field':
				$fields = $this->get_fields( self::$post_type );
				?><select class="geodir-conditional-el geodir-conditional-<?php echo $key; ?> form-control" name="conditional_fields[<?php echo $index; ?>][field]" id="conditional_field_<?php echo $index; ?>">
					<option value=""><?php _e( 'FIELD', 'geodiradvancesearch' ); ?></option>
					<?php if ( ! empty( $fields ) ) { ?>
						<?php foreach ( $fields as $name => $label ) {
							$skip = ! empty( self::$field ) && ! empty( self::$field->htmlvar_name ) && self::$field->htmlvar_name == $name ? true : false;

							/**
							 * Filter to skip field from conditional fields options.
							 *
							 * @since 2.2.5
							 *
							 * @param bool   $skip True to skip.
							 * @param string $name Field name.
							 * @param string $key Field key.
							 */
							if ( apply_filters( 'geodir_search_conditional_field_option_skip_cf', $skip, $name, $key ) ) {
								continue;
							} ?>
							<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $value == $name, true ); ?>><?php echo $label; ?></option>
						<?php } ?>
					<?php } ?>
				</select><?php
				break;
			case 'condition':
				?><select class="geodir-conditional-el geodir-conditional-<?php echo $key; ?> form-control" name="conditional_fields[<?php echo $index; ?>][condition]" id="conditional_condition_<?php echo $index; ?>">
					<option value=""><?php _e( 'CONDITION', 'geodiradvancesearch' ); ?></option>
					<option value="empty" <?php selected( $value == 'empty', true ); ?>><?php _e( 'empty', 'geodiradvancesearch' ); ?></option>
					<option value="not empty" <?php selected( $value == 'not empty', true ); ?>><?php _e( 'not empty', 'geodiradvancesearch' ); ?></option>
					<option value="equals to" <?php selected( $value == 'equals to', true ); ?>><?php _e( 'equals to', 'geodiradvancesearch' ); ?></option>
					<option value="not equals" <?php selected( $value == 'not equals', true ); ?>><?php _e( 'not equals', 'geodiradvancesearch' ); ?></option>
					<option value="greater than" <?php selected( $value == 'greater than', true ); ?>><?php _e( 'greater than', 'geodiradvancesearch' ); ?></option>
					<option value="less than" <?php selected( $value == 'less than', true ); ?>><?php _e( 'less than', 'geodiradvancesearch' ); ?></option>
					<option value="contains" <?php selected( $value == 'contains', true ); ?>><?php _e( 'contains', 'geodiradvancesearch' ); ?></option>
				</select><?php
				break;
			case 'value':
				?>
				<input class="geodir-conditional-el geodir-conditional-<?php echo $key; ?> form-control" type="text" name="conditional_fields[<?php echo $index; ?>][value]" id="conditional_value_<?php echo $index; ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'VALUE', 'geodiradvancesearch' ); ?>">
				<?php
				break;
			case 'remove':
				?>
				<div class="<?php echo $aui_bs5 ? '' : 'input-group-append'; ?>">
					<span class="input-group-text px-2 <?php echo $aui_bs5 ? 'rounded-end rounded-0' : ''; ?>">
						<a class="geodir-conditional-<?php echo $key; ?> text-danger" data-toggle="tooltip"  href="javascript:void(0);" title="<?php esc_attr_e( 'Remove', 'geodiradvancesearch' ); ?>"><i class="fas fa-minus-circle"></i></a>
					</span>
				</div>
				<?php
				break;
		}

		$content = ob_get_clean();

		return trim( $content );
	}

	/**
	 * Get conditional fields.
	 *
	 * @since 2.2.5
	 *
	 * @param string $post_type Current post type.
	 * @return array Conditional fields.
	 */
	public function get_fields( $post_type ) {
		$search_fields = GeoDir_Adv_Search_Fields::get_search_fields( self::$post_type );

		$fields = array();
		$fields['type'] = __( 'Post Type', 'geodiradvancesearch' );
		$fields['s'] = __( 'Search for', 'geodiradvancesearch' );
		$fields['near'] = __( 'Near', 'geodiradvancesearch' );

		if ( ! empty( $search_fields ) ) {
			foreach ( $search_fields as $key => $field ) {
				$skip = false;

				/**
				 * Filter to skip field from conditional fields.
				 *
				 * @since 2.2.5
				 *
				 * @param bool  $skip True to skip.
				 * @param array $name Field array.
				 */
				if ( apply_filters( 'geodir_search_conditional_field_skip_cf', $skip, $field ) ) {
					continue;
				}

				$field = stripslashes_deep( $field );
				if ( $field->htmlvar_name == 'business_hours' ) {
					$field->htmlvar_name = 'open_now';
				} else if ( $field->htmlvar_name == 'distance' ) {
					$field->htmlvar_name = 'dist';
				}
				$fields[ $field->htmlvar_name ] = ! empty( $field->admin_title ) ? __( $field->admin_title, 'geodirectory' ) : __( $field->frontend_title, 'geodirectory' );
			}
		}

		/**
		 * Filter the conditional fields.
		 *
		 * @since 2.2.5
		 *
		 * @param array  $fields Fields array.
		 * @param string $post_type Current post type.
		 */
		return apply_filters( 'geodir_search_conditional_fields_options', $fields, $post_type );
	}

	/**
	 * Get address fields.
	 *
	 * @since 2.2.5
	 *
	 * @param string $post_type Current post type.
	 * @return array Address fields.
	 */
	public function get_address_fields( $post_type ) {
		$address_fields = geodir_post_meta_address_fields( $post_type );

		$fields = array();
		if ( ! empty( $address_fields ) ) {
			foreach ( $address_fields as $key => $_field ) {
				if ( $key == 'map_directions' ) {
					continue;
				}
				$fields[ $key ] = $_field['frontend_title'];
			}
		}

		/**
		 * Filter the conditional address fields.
		 *
		 * @since 2.2.5
		 *
		 * @param array  $fields Address fields array.
		 * @param string $post_type Current post type.
		 */
		return apply_filters( 'geodir_search_conditional_fields_address_options', $fields, $post_type );
	}

	/**
	 * Save conditional fields.
	 *
	 * @since 2.2.5
	 *
	 * @param object $field_data Field data.
	 * @param array  $request Request data.
	 */
	public function save_conditional_fields( $field_data, $request ) {
		if ( ! empty( $request['conditional_fields'] ) && empty( $request['main_search'] ) ) {
			$fields = $request['conditional_fields'];

			if ( ! empty( $fields['TEMP'] ) ) {
				unset( $fields['TEMP'] );
			}

			$conditions = array();

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $_field ) {
					if ( ! empty( $_field['action'] ) && ! empty( $_field['field'] ) && ! empty( $_field['condition'] ) ) {
						$conditions[] = array(
							'action' => sanitize_text_field( $_field['action'] ),
							'field' => sanitize_text_field( $_field['field'] ),
							'condition' => sanitize_text_field( $_field['condition'] ),
							'value' => ( isset( $_field['value'] ) ? sanitize_text_field( stripslashes( $_field['value'] ) ) : '' ),
						);
					}
				}
			}

			$extra_fields = ! empty( $field_data->extra_fields ) ? $field_data->extra_fields : '';
			$is_serialized = is_serialized( $extra_fields );
			$is_changed = false;

			if ( ! empty( $conditions ) ) {
				$extra_fields = maybe_unserialize( $extra_fields );

				if ( ! is_array( $extra_fields ) ) {
					if ( $extra_fields != '' ) {
						$extra_fields = array( $extra_fields );
					} else {
						$extra_fields = array();
					}
				}

				if ( empty( $extra_fields ) ) {
					$is_serialized = true;
				}

				$is_changed = true;
				$extra_fields['conditions'] = $conditions;
			} else {
				if ( is_array( $extra_fields ) && isset( $extra_fields['conditions'] ) ) {
					unset( $extra_fields['conditions'] );
				}
			}

			if ( $is_changed ) {
				if ( $is_serialized ) {
					$extra_fields = ! empty( $extra_fields ) ? maybe_serialize( $extra_fields ) : '';
				}

				$field_data->extra_fields = $extra_fields;
			}
		}

		return $field_data;
	}

	/**
	 * Show conditional field extra message.
	 *
	 * @since 2.2.5
	 *
	 * @param string $post_type Current post type.
	 * @param object $field Current field object.
	 * @param array  $data Current field data.
	 * @return mixed
	 */
	public function show_extra_description( $post_type, $field, $data ) {
		$show_warning = ( ! empty( $field->htmlvar_name ) && in_array( $field->htmlvar_name, array( 'post_title' ) ) ) ? true : false;

		/**
		 * Show mandatory field warning in conditional field settings.
		 *
		 * @since 2.2.5
		 *
		 * @param bool  $show_warning True to show warning.
		 * @param array $field Field array.
		 */
		$show_warning = apply_filters( 'geodir_search_conditional_field_setting_show_warning', $show_warning, $field, $data );

		if ( $show_warning ) {
			echo '<div data-setting="conditional_fields_extra_desc" class="geodir-cond-warning bsui">';
			echo aui()->alert( array(
						'type'=> 'warning',
						'content'=> __( 'This is a mandatory field. if hidden when submitted, it will fail.', 'geodiradvancesearch' )
					)
				);
			echo '</div>';
		}
	}

	/**
	 * Show conditional field icon in tab header.
	 *
	 * @since 2.2.5
	 *
	 * @param object $field The field object settings.
	 * @param array  $cf The customs field default settings.
	 * @return mixed
	 */
	public function show_conditional_icon( $field, $cf ) {
		global $aui_bs5;

		$conditional_attrs = geodir_search_conditional_field_attrs( $field );
		$conditional_icon = geodir_conditional_field_icon( $conditional_attrs, $field );

		if ( $conditional_icon ) {
			echo ' <span class="dd-extra-icon' . ( $aui_bs5 ? ' ms-2 me-0' : ' ml-2 mr-0' ) . '">' . $conditional_icon . '</span> ';
		}
	}
} }

return new GeoDir_Adv_Search_Admin_Conditional_Fields();
