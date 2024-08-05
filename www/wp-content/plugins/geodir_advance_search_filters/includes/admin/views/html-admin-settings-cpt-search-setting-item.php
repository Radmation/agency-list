<?php
/**
 * Admin custom field search form
 *
 * @since 2.0.0
 *
 * @package GeoDirectory
 */

global $aui_bs5;

?>
<li class="dd-item<?php echo ( $aui_bs5 ? ' mb-0' : '' ); ?>" data-id="<?php echo $field->id;?>" id="setName_<?php echo $field->id;?>" data-field-nonce="<?php echo esc_attr( $nonce ); ?>">
	<div class="dd-form hover-shadow d-flex justify-content-between rounded c-pointer list-group-item border rounded-smx bg-light <?php echo ( $aui_bs5 ? 'text-left' : 'text-start' ); ?>" onclick="gd_tabs_item_settings(this);">
		<div class="  flex-fill font-weight-bold fw-bold">
			<?php 
			echo $field_icon . " ";
			if ( $field->field_type == 'fieldset' ) {
				echo __( 'Fieldset:', 'geodiradvancesearch' ) . ' ' . esc_attr($field->frontend_title) ;
			}else{
				echo !empty( $field->admin_title ) ? esc_attr($field->admin_title) : esc_attr($field->frontend_title);
			}
			?>
		</div>
		<div class="dd-handle">
			<?php do_action( 'geodir_search_cfa_tab_header_icon', $field, $cf ); ?>
			<i class="far fa-trash-alt text-danger <?php echo ( $aui_bs5 ? ' ms-2' : 'ml-2' ); ?>" id="delete-16"  onclick="geodir_adv_search_delete_field(this);event.stopPropagation();return false;"></i>
			<i class="fas fa-grip-vertical text-muted <?php echo ( $aui_bs5 ? ' ms-2' : 'ml-2' ); ?>" style="cursor: move" aria-hidden="true" ></i>
		</div>

		<script type="text/template"  class="dd-setting <?php echo 'dd-type-'.esc_attr($field->field_type);?>">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>"/>
			<input type="hidden" name="post_type" id="post_type" value="<?php echo esc_attr( $field->post_type ); ?>"/>
			<input type="hidden" name="field_type" id="field_type" value="<?php echo esc_attr( $field->field_type ); ?>"/>
			<input type="hidden" name="field_id" id="field_id" value="<?php echo esc_attr( $field->id ); ?>"/>
			<input type="hidden" name="data_type" id="data_type" value="<?php echo esc_attr( $field->data_type ); ?>"/>
			<input type="hidden" name="input_type" id="input_type" value="<?php echo esc_attr( $field->input_type ); ?>"/>
			<input type="hidden" name="htmlvar_name" id="htmlvar_name" value="<?php echo esc_attr( $field->htmlvar_name ); ?>"/>
			<input type="hidden" name="admin_title" id="admin_title" value="<?php echo esc_attr( $field->admin_title ); ?>"/>
			<input type="hidden" name="search_condition" id="search_condition" value="<?php echo esc_attr( $field->search_condition ); ?>"/>

			<?php do_action( "geodir_search_cfa_hidden_fields", $field->field_type, $field, $cf ); ?>
	
			<?php

			do_action( "geodir_search_cfa_before_frontend_title", $field->field_type, $field, $cf );

			echo aui()->input(
				array(
					'id'                => 'gd_frontend_title'.esc_attr($key),
					'name'              => 'frontend_title',
					'label_type'        => 'top',
					'label'              => __( 'Frontend title', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Search field frontend title.', 'geodiradvancesearch' ) ),
					'type'              => 'text',
					'value' => $field->frontend_title
				)
			);

			do_action( "geodir_search_cfa_before_description", $field->field_type, $field, $cf );

			echo aui()->input(
				array(
					'id'                => 'gd_description'.esc_attr($key),
					'name'              => 'description',
					'label_type'        => 'top',
					'label'              => __( 'Description', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Search field frontend description.', 'geodiradvancesearch' ) ),
					'type'              => 'text',
					'value' => $field->description
				)
			);

			if ( apply_filters( 'geodir_advance_search_field_in_main_search_bar', false, $field, $cf ) ) { 
				$main_search = ! empty( $field->main_search ) ? true : false;
				$main_search_priority = empty( $field->main_search_priority ) && $field->main_search_priority != '0' ? 15 : (int) $field->main_search_priority;

			    do_action( "geodir_search_cfa_before_main_search", $field->field_type, $field, $cf );

				echo aui()->input(
					array(
						'id' => 'gd_main_search'.esc_attr($key),
						'name' => 'main_search',
						'label_type' => 'horizontal',
						'label_col' => '4',
						'label' => __( 'Main search bar?', 'geodiradvancesearch' ),
						'type' => 'checkbox',
						'checked' => $main_search,
						'value' => '1',
						'switch' => 'md',
						'label_force_left' => true,
						'help_text' => geodir_help_tip( __( 'This will show the filed in the main search bar as a select input, it will no longer show in the advanced search dropdown.', 'geodiradvancesearch' ) )
					)
				);

				echo aui()->input(
					array(
						'id'                => 'main_search_priority'.esc_attr($key),
						'name'              => 'main_search_priority',
						'label_type'        => 'top',
						'label'              => __( 'Search bar priority', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Where in the search bar you want it to be placed (recommended 15). CPT input: 10, Search input:20, Near input:30.', 'geodiradvancesearch' ) ),
						'type'              => 'number',
						'value' => $main_search_priority,
						'element_require' => '[%gd_main_search'.esc_attr($key).'%:checked]'
					)
				);
			}

			if ( $field->field_type == 'categories' || $field->field_type == 'select' || $field->field_type == 'radio' || $field->field_type == 'multiselect' ) {


				do_action( "geodir_search_cfa_before_input_type", $field->field_type, $field, $cf );

				echo aui()->select(
					array(
						'id'               => 'gd_input_type' . $key,
						'name'             =>  'input_type',
						'label_type'       => 'top',
						'multiple'         => false,
						'class'            => ' mw-100',
						'options'          => array(
							'SELECT' => __( 'SELECT', 'geodiradvancesearch' ),
							'CHECK'  => __( 'CHECK', 'geodiradvancesearch' ),
							'RADIO'  => __( 'RADIO', 'geodiradvancesearch' ),
							'LINK'   => __( 'LINK', 'geodiradvancesearch' ),
						),
						'label'            => __( 'Input type', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Select the field input type in the search bar.', 'geodiradvancesearch' ) ),
						'value'            => $field->input_type,
						'extra_attributes' => array(
							'onchange' => "geodir_adv_search_input_type_changed(this, '" . esc_attr( $key ) . "');"
						)
					)
				);

			} else if ( ( $field->data_type == 'INT' || $field->data_type == 'FLOAT' ) && $field->field_type != 'fieldset' ) {
				if ( $field->htmlvar_name != 'distance' ) {
					do_action( "geodir_search_cfa_before_data_type_change", $field->field_type, $field, $cf );

					echo aui()->select(
						array(
							'id'               => 'data_type_change',
							'name'             => 'data_type_change',
							'label_type'       => 'top',
							'multiple'         => false,
							'class'            => ' mw-100',
							'options'          => array(
								'SELECT' => __( 'Range in SELECT', 'geodiradvancesearch' ),
								'LINK'   => __( 'Range in LINK', 'geodiradvancesearch' ),
								'TEXT'   => __( 'Range in TEXT', 'geodiradvancesearch' ),
							),
							'label'            => __( 'Data Type', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Select the field input data type in the search bar.', 'geodiradvancesearch' ) ),
							'value'            => ( $field->search_condition == 'SINGLE' || $field->search_condition == 'FROM' ? 'TEXT' : $field->search_condition ),
							'extra_attributes' => array(
								'onchange' => "geodir_adv_search_type_changed(this, '" . esc_attr( $key ) . "');"
							)
						)
					);
				}

				do_action( "geodir_search_cfa_before_search_condition_select", $field->field_type, $field, $cf );

				echo aui()->select(
					array(
						'id'               => 'search_condition_select',
						'name'             => 'search_condition_select',
						'label_type'       => 'top',
						'multiple'         => false,
						'class'            => ' mw-100',
						'options'          => array(
							'SINGLE' => __( 'Range single', 'geodiradvancesearch' ),
							'FROM'   => __( 'Range from', 'geodiradvancesearch' ),
						),
						'label'            => __( 'Searching Type', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Select searching type.', 'geodiradvancesearch' ) ),
						'value'            => $field->search_condition,
						'element_require'  => '[%data_type_change%]=="TEXT"',
						'extra_attributes' => array(
							'onchange' => "geodir_adv_search_range_changed(this, '" . esc_attr( $key ) . "');"
						)
					)
				);

				if ( $field->htmlvar_name != 'distance' ) {
					do_action( "geodir_search_cfa_before_range_min", $field->field_type, $field, $cf );
					echo aui()->input(
						array(
							'id'                => 'gd_range_min'.esc_attr($key),
							'name'              => 'range_min',
							'label_type'        => 'top',
							'label'              => __( 'Starting Search Range', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Starting Search Range', 'geodiradvancesearch' ) ),
							'type'              => 'number',
							'value' => $field->range_min,
							'element_require' => '([%data_type_change%]=="SELECT" || [%data_type_change%]=="LINK")'
						)
					);
				}

				do_action( "geodir_search_cfa_before_range_max", $field->field_type, $field, $cf );

				echo aui()->input(
					array(
						'id'                => 'gd_range_max'.esc_attr($key),
						'name'              => 'range_max',
						'label_type'        => 'top',
						'label'              => __( 'Maximum Search Range', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Enter the maximum radius of the search zone you want to create, for example if you want your visitors to search any listing within 50 miles or kilometers from the current location, then you would enter 50.', 'geodiradvancesearch' ) ),
						'type'              => 'number',
						'value' => $field->range_max,
						'element_require' => $field->htmlvar_name != 'distance' ? '([%data_type_change%]=="SELECT" || [%data_type_change%]=="LINK")' : ''
					)
				);

				do_action( "geodir_search_cfa_before_range_step", $field->field_type, $field, $cf );

				$range_step_attr = $field->htmlvar_name != 'distance' ? 'onkeyup="geodir_adv_search_difference(this);" onchange="geodir_adv_search_difference(this);"' : '';

				echo aui()->input(
					array(
						'id'                => 'gd_range_step'.esc_attr($key),
						'name'              => 'range_step',
						'label_type'        => 'top',
						'label'              => __( 'Difference in Search Range', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Here you decide how many different search radii you make available to your visitors. If you enter a fifth of the Maximum Search Range, there will be 5 options; if you enter half of the Maximum Search Range, then there will be 2 options.', 'geodiradvancesearch' ) ),
						'type'              => 'number',
						'value' => $field->range_step,
						'element_require' => $field->htmlvar_name != 'distance' ? '([%data_type_change%]=="SELECT" || [%data_type_change%]=="LINK")' : ''
					)
				);
				echo '<input type="hidden" name="range_mode" value="1" />';

				if ( $field->htmlvar_name != 'distance' ) {
					do_action( "geodir_search_cfa_before_range_start", $field->field_type, $field, $cf );

					echo aui()->input(
						array(
							'id'                => 'gd_range_start'.esc_attr($key),
							'name'              => 'range_start',
							'label_type'        => 'top',
							'label'              => __( 'First Search Range', 'geodiradvancesearch' ) . geodir_help_tip( __( 'First Search Range.', 'geodiradvancesearch' ) ),
							'type'              => 'number',
							'value' => $field->range_start,
							'element_require' => '[%data_type_change%]=="SELECT" || [%data_type_change%]=="LINK"'
						)
					);

					do_action( "geodir_search_cfa_before_range_from_title", $field->field_type, $field, $cf );

					echo aui()->input(
						array(
							'id'                => 'gd_range_from_title'.esc_attr($key),
							'name'              => 'range_from_title',
							'label_type'        => 'top',
							'label'              => __( 'First Search Range Text', 'geodiradvancesearch' ) . geodir_help_tip( __( 'First search range text.', 'geodiradvancesearch' ) ),
							'type'              => 'text',
							'value' => $field->range_from_title,
							'placeholder'   => esc_attr__( 'Less than', 'geodiradvancesearch' ),
							'element_require' => '[%data_type_change%]=="SELECT" || [%data_type_change%]=="LINK"'
						)
					);

					do_action( "geodir_search_cfa_before_range_to_title", $field->field_type, $field, $cf );

					echo aui()->input(
						array(
							'id'                => 'gd_range_to_title'.esc_attr($key),
							'name'              => 'range_to_title',
							'label_type'        => 'top',
							'label'              => __( 'Last Search Range Text', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Last search range text.', 'geodiradvancesearch' ) ),
							'type'              => 'text',
							'value' => $field->range_to_title,
							'placeholder'   => esc_attr__( 'More than', 'geodiradvancesearch' ),
							'element_require' => '[%data_type_change%]=="SELECT" || [%data_type_change%]=="LINK"'
						)
					);

				}
			} else if ( $field->input_type == 'DATE' ) {
				do_action( "geodir_search_cfa_before_search_condition_select", $field->field_type, $field, $cf );

				echo aui()->select(
					array(
						'id'               => 'search_condition_select',
						'name'             => 'search_condition_select',
						'label_type'       => 'top',
						'multiple'         => false,
						'class'            => ' mw-100',
						'options'          => array(
							'SINGLE' => __( 'Range single', 'geodiradvancesearch' ),
							'FROM'   => __( 'Range from', 'geodiradvancesearch' ),
						),
						'label'            => __( 'Searching Type', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Select searching type.', 'geodiradvancesearch' ) ),
						'value'            => $field->search_condition,
						'extra_attributes' => array(
							'onchange' => "geodir_adv_search_range_changed(this, '');"
						)
					)
				);
			}

			do_action( "geodir_search_cfa_before_range_expand", $field->field_type, $field, $cf );

			echo aui()->input(
				array(
					'id'              => 'gd_range_expand'.esc_attr($key),
					'name'            => 'range_expand',
					'label_type'      => 'top',
					'label'           => __( 'Expand Search Range', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Set a show limit, after which a show more button will be used.', 'geodiradvancesearch' ) ),
					'type'            => 'number',
					'value' => $field->range_expand,
					//'placeholder'   => esc_attr__( 'More than', 'geodiradvancesearch' ),
					'element_require' => $field->htmlvar_name != 'distance' ? '([%gd_input_type'.esc_attr($key).'%]=="CHECK" || [%gd_input_type'.esc_attr($key).'%]=="LINK" || [%data_type_change%]=="LINK" || [%data_type_change%]=="search_condition")' : ''
				)
			);
			echo '<input type="hidden" name="expand_search" value="1" />';

			if ( $field->htmlvar_name == 'distance' ) {
				$search_is_sort = ! empty( $field->extra_fields['is_sort'] ) ? true : false;
				$search_asc = ! empty( $field->extra_fields['asc'] ) ? true : false;
				$search_asc_title = ! empty( $field->extra_fields['asc_title'] ) ? $field->extra_fields['asc_title'] : '';
				$search_desc = ! empty( $field->extra_fields['desc'] ) ? true : false;
				$search_desc_title = ! empty( $field->extra_fields['desc_title'] ) ? $field->extra_fields['desc_title'] : '';
				$distance_label = ! empty( $field->extra_fields['distance_label'] ) ? trim( $field->extra_fields['distance_label'] ) : '';

				echo aui()->input(
					array(
						'id'                => 'gd_geodir_distance_sorting'.esc_attr($key),
						'name'              => 'geodir_distance_sorting',
						'label_type'        => 'horizontal',
						'label_col'        => '4',
						'label'              => __( 'Show distance sorting', 'geodiradvancesearch' ),
						'type'              => 'checkbox',
						'checked' => $search_is_sort,
						'value' => '1',
						'switch'    => 'md',
						'label_force_left'  => true,
						'help_text' => geodir_help_tip( __( 'Select if you want to show option in distance sort (for advanced filters only).', 'geodiradvancesearch' )),
						'element_require' => '![%gd_main_search' . esc_attr( $key ) . '%:checked]'
					)
				);

				echo aui()->input(
					array(
						'id'                => 'gd_search_asc_title'.esc_attr($key),
						'name'              => 'search_asc_title',
						'label_type'        => 'top',
						'label'              => __( 'Select Nearest', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Set the distance sort nearest title (for advanced filters only).', 'geodiradvancesearch' ) ),
						'type'              => 'text',
						'value' => $search_asc_title,
						'placeholder'   => esc_attr__( 'Ascending title (set to enable)', 'geodiradvancesearch' ),
						'element_require' => '[%gd_geodir_distance_sorting'.esc_attr($key).'%:checked]'
					)
				);
				echo '<input type="hidden" name="search_asc" value="1" />';

				echo aui()->input(
					array(
						'id'                => 'gd_search_desc_title'.esc_attr($key),
						'name'              => 'search_desc_title',
						'label_type'        => 'top',
						'label'              => __( 'Select Farthest', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Set the distance sort farthest title (for advanced filters only).', 'geodiradvancesearch' ) ),
						'type'              => 'text',
						'value' => $search_desc_title,
						'placeholder'   => esc_attr__( 'Descending title (set to enable)', 'geodiradvancesearch' ),
						'element_require' => '[%gd_geodir_distance_sorting'.esc_attr($key).'%:checked]'
					)
				);
				echo '<input type="hidden" name="search_desc" value="1" />';

				echo aui()->input(
					array(
						'id' => 'distance_label' . esc_attr( $key ),
						'name' => 'distance_label',
						'type' => 'text',
						'label_type' => 'top',
						'label' => __( 'Distance Label', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Set the distance label to show in distance slider popup.', 'geodiradvancesearch' ) ),
						'value' => $distance_label,
						'placeholder' => esc_attr__( 'Distance', 'geodiradvancesearch' ),
						'element_require' => '[%gd_main_search' . esc_attr( $key ) . '%:checked]'
					)
				);
			}

			if ( $field->field_type == 'categories' || $field->field_type == 'multiselect' || $field->field_type == 'select' ) {
				$search_operator = ! empty( $field->extra_fields['search_operator'] ) ? $field->extra_fields['search_operator'] : 'AND';

				do_action( "geodir_search_cfa_before_search_operator", $field->field_type, $field, $cf );

				echo aui()->select(
					array(
						'id'                => 'gd_search_operator'.$key,
						'name'              =>  'search_operator',
						'label_type'        => 'top',
						'multiple'   => false,
						'class'             => ' mw-100',
						'options'       => array(
							'AND'   =>  __( 'AND', 'geodiradvancesearch' ),
							'OR'   =>  __( 'OR', 'geodiradvancesearch' ),
						),
						'label'              => __( 'Search Operator', 'geodiradvancesearch' ) . geodir_help_tip( __( 'Works with Checkbox type only. )  If AND is selected then the listing must contain all the selected options, if OR is selected then the listing must contain 1 selected item.', 'geodiradvancesearch' ) ),
						'value'         => $search_operator,
						'element_require' => '[%gd_input_type'.esc_attr($key).'%] == "CHECK"'
					)
				);
			}

			do_action( "geodir_search_cfa_before_save_button", $field->field_type, $field, $cf, $key ); ?>

			<div class="gd-tab-actions mb-0" data-setting="save_button">
				<a class="btn btn-link text-muted" href="javascript:void(0);" onclick="gd_tabs_close_settings(this); return false;"><?php _e("Close","geodiradvancesearch");?></a>
				<button type="button" class="btn btn-primary" name="save" id="save" data-save-text="<?php _e("Save","geodiradvancesearch");?>"  onclick="geodir_adv_search_save_field(this);jQuery(this).html('<span class=\'spinner-border spinner-border-sm\' role=\'status\'></span> <?php esc_attr_e( 'Saving', 'geodiradvancesearch' ); ?>').addClass('disabled');return false;">
					<?php _e("Save","geodiradvancesearch");?>
				</button>
			</div>
		</script>
	</div>
</li>