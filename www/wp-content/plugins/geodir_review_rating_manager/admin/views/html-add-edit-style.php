<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $aui_bs5;

//GeoDir_Settings_Page::toggle_advanced_button();
?>
<div>
	<div>
		<div>
<div id="geodir-add-style-div" class="accordion ">
	<div class="card p-0 mw-100 border-0 shadow-sm" style="overflow: initial;">

		<div class="card-header bg-white rounded-top">
			<h2 class="gd-settings-title h5 mb-0 ">
				<?php if ( empty( $style['id'] ) ) {
					_e( 'Add Rating Style', 'geodir_reviewratings' );
				} else {
					echo __( 'Edit Rating Style:', 'geodir_reviewratings' ) . esc_attr($style['name']);
				} ?></h2>
		</div>
		<div class="card-body">

			<?php

			$default_star_lables = GeoDir_Comments::rating_texts_default();
			$default_star_lables = array_values( $default_star_lables );
			$star_lables         = ! empty( $style['star_lables'] ) ? $style['star_lables'] : serialize( $default_star_lables );
			$style_serialized    = $star_lables != '' && is_serialized( $star_lables ) ? 1 : 0;

			echo '<input type="hidden" id="hidden-style-text" value="' . esc_attr( $star_lables ) . '" />
                <input type="hidden" id="hidden-style-serialized" value="' . $style_serialized . '"  />';

			$options = array(
				array(
					'name'     => __( 'Title', 'geodir_reviewratings' ),
//                        'desc' => __('Show the search text box `placeholder` value on search form.', 'geodir_reviewratings'),
					'id'       => 'multi_rating_category',
					'type'     => 'text',
//                        'placeholder' => geodir_get_search_default_text(),
//                        'desc_tip' => true,
					'default'  => '',
					'value'    => $style['name'],
					'advanced' => false,
					'required' => true
				),
				array(
					'name'              => __( 'Rating score (default 5)', 'geodir_reviewratings' ),
//                        'desc' => __('Show the search text box `placeholder` value on search form.', 'geodir_reviewratings'),
					'id'                => 'style_count',
					'type'              => 'number',
//                        'placeholder' => geodir_get_search_default_text(),
//                        'desc_tip' => true,
					'default'           => 5,
					'value'             => $style['star_number'] ? $style['star_number'] : 5,
					'advanced'          => false,
					'required'          => true,
					'custom_attributes' => array(
						'min' => 3,
						'max' => 10
					)
				),
			);

			$values = isset( $style['star_lables'] ) ? $style['star_lables'] : '';
			$arr    = array();
			$arr    = geodir_reviewrating_star_lables_to_arr( $values, 0, true );

			// print_r($arr);
			$i = 1;
			for ( $k = 1; $k <= 10; $k ++ ) {

				$options[] = array(
					'name'            => wp_sprintf( __( '%d Star Text', 'geodir_reviewratings' ), $i ),
//                        'desc' => __('Show the search text box `placeholder` value on search form.', 'geodir_reviewratings'),
					'id'              => 'star_rating_text[]',
					'type'            => 'text',
//                        'placeholder' => geodir_get_search_default_text(),
//                        'desc_tip' => true,
					'default'         => '',
					'value'           => ! empty( $arr[ $i ] ) ? $arr[ $i ] : '',
					'advanced'        => false,
//					'required'        => true,
					'element_require' => '[%style_count%] >= ' . $i,
				);
				$i ++;
			}

			GeoDir_Admin_Settings::output_fields( $options );


			$options = array(
				array(
					'id'       => 's_rating_type',
					'type'     => 'select',
					'name'     => __( 'Rating type', 'geodir_reviewratings' ),
					'class'    => 'geodir-select',
					'options'  => array(
						'font-awesome' => __( 'Font Awesome', 'geodir_reviewratings' ),
						'image'        => __( 'Transparent Image', 'geodir_reviewratings' ),
					),
					'default'  => ! empty( $style['s_rating_type'] ) ? $style['s_rating_type'] : 'font-awesome',
					'desc_tip' => true,
					'advanced' => true,
				),
				array(
					'id'                => 's_rating_icon',
					'name'              => __( 'Rating icon', 'geodir_reviewratings' ),
//					'class'             => 'geodir-select',
					'default'           => 'fas fa-star',
					'value'             => ! empty( $style['s_rating_icon'] ) ? $style['s_rating_icon'] : 'fas fa-star',
					'type'              => 'font-awesome',
					'desc_tip'          => true,
					'advanced'          => true,
					'custom_attributes' => array(
						'data-fa-icons' => true,
						'data-fa-color' => ! empty( $style['star_color'] ) ? $style['star_color'] : '#ff9900',
					),
					'element_require' => '[%s_rating_type%] == "font-awesome" ',
				),
				array(
					'name'     => __( 'Rating image', 'geodir_reviewratings' ),
					'desc'     => '',
					'id'       => 's_file_off',
					'type'     => 'image',
					'default'  => $style['s_img_off'],
					'desc_tip' => true,
					'element_require' => '[%s_rating_type%] == "image" ',
				),
				array(
					'id'      => 'star_color',
					'name'    => __( 'Rating color', 'geodir_reviewratings' ),
					'desc'    => '',
					'value'   => ! empty( $style['star_color'] ) ? $style['star_color'] : '#ff9900',
					'default' => '#ff9900',
					'type'    => 'color',
				),
				array(
					'id'      => 'star_color_off',
					'name'    => __( 'Rating color off', 'geodir_reviewratings' ),
					'desc'    => '',
					'value'   => ! empty( $style['star_color_off'] ) ? $style['star_color_off'] : '#afafaf',
					'default' => '#afafaf',
					'type'    => 'color',
				)
			);

			GeoDir_Admin_Settings::output_fields( $options );
			?>

			<input type="hidden" name="style_id" id="geodir_style_id"
			       value="<?php echo absint( $style['id'] ); ?>"/>
			<input type="hidden" name="security" id="geodir_save_style_nonce"
			       value="<?php echo esc_attr( wp_create_nonce( 'geodir-save-style' ) ); ?>"/>
		</div>
	</div>

	<p class="submit mt-2 <?php echo ( $aui_bs5 ? 'text-end' : 'text-right' ); ?>">
		<input name="save" class="btn btn-primary geodir-save-button" type="submit" id="save_style" value="<?php esc_attr_e( 'Save Style', 'geodir_reviewratings' ); ?>"/>
	</p>
</div>
