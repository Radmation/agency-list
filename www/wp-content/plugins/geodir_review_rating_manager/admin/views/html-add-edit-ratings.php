<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb, $aui_bs5, $cat_display, $post_cat;
?>
<div>
	<div>
		<div>
			<div id="geodir-add-rating-div" class="accordion ">
				<div class="card p-0 mw-100 border-0 shadow-sm" style="overflow: initial;">

					<div class="card-header bg-white rounded-top">
						<h2 class="gd-settings-title h5 mb-0 ">
							<?php if ( empty( $style['id'] ) ) {
								_e( 'Add Rating', 'geodir_reviewratings' );
							} else {
								echo __( 'Edit Rating:', 'geodir_reviewratings' ) . esc_attr( $rating['name'] );
							} ?></h2>
					</div>
					<div class="card-body">

						<?php
						$options = array(
							array(
								'name'              => __( 'Select multirating style', 'geodir_reviewratings' ),
								'id'                => 'geodir_rating_style_dl',
								'type'              => 'select',
								'class'             => 'geodir-select',
								'default'           => $rating['category_id'],
								'options'           => geodir_review_rating_style_dl(),
								'custom_attributes' => array(
									'required' => 'required'
								),
							),
							array(
								'name'              => __( 'Rating title', 'geodir_reviewratings' ),
								'id'                => 'rating_title',
								'type'              => 'text',
								'default'           => $rating['title'],
								'custom_attributes' => array(
									'required' => 'required'
								),
							),
							array(
								'name'              => __( 'Showing method', 'geodir_reviewratings' ),
								'id'                => 'show_star',
								'type'              => 'select',
								'options'           => array(
									'1' => __( 'Show Stars', 'geodir_reviewratings' ),
									'0' => __( 'Show Dropdown', 'geodir_reviewratings' ),
								),
								'default'           => $rating['check_text_rating_cond'] == "" || $rating['check_text_rating_cond'] == 1 ? 1 : 0,
								'custom_attributes' => array(
									'required' => 'required'
								),
							),
							array(
								'type'              => 'number',
								'id'                => 'display_order',
								'name'              => __( 'Display Order', 'geodir_reviewratings' ),
								'default'           => ( isset( $rating['display_order'] ) ? absint( $rating['display_order'] ) : '' ),
								'custom_attributes' => array(
									'min'  => '0',
									'step' => '1',
								)
							)
						);

						GeoDir_Admin_Settings::output_fields( $options );

						?>
						<div data-argument="post_types" class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row">
							<label for="post_types" class="<?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?> col-sm-3 col-form-label">
								<?php _e( 'Select post type', 'geodir_reviewratings' ); ?>
							</label>
							<div class="col-sm-9">
						<?php

						$rating_cat_id = isset( $rating['id'] ) ? (int) $rating['id'] : '';
						if ( $rating_cat_id ) {
							$sqlquery   = $wpdb->prepare( "SELECT * FROM " . GEODIR_REVIEWRATING_CATEGORY_TABLE . " WHERE id = %d", array( $rating_cat_id ) );
							$qry_result = $wpdb->get_row( $sqlquery );
						}

						$post_arr = array();
						if ( isset( $qry_result->post_type ) && $qry_result->post_type != '' ) {
							$post_arr = explode( ',', $qry_result->post_type );
						}

						$geodir_post_types = geodir_get_option( 'post_types' );
						$geodir_posttypes  = geodir_get_posttypes();

						$i = 1;
						foreach ( $geodir_posttypes as $p_type ) {
							$geodir_posttype_info = $geodir_post_types[ $p_type ];
							$listing_slug         = $geodir_posttype_info['labels']['singular_name'];
							$checked              = ! empty( $post_arr ) && in_array( $p_type, $post_arr ) ? 1 : 0;
							$display              = ! $checked ? 'display:none' : '';
							?>
							<div class="d-flex align-items-center mt-3">
								<input type="checkbox" name="post_type<?php echo $i; ?>" id="_<?php echo $i; ?>" value="<?php echo esc_attr( $p_type ); ?>" class="rating_checkboxs" <?php checked( $checked, 1 ) ?> /><label for="_<?php echo $i; ?>" class="<?php echo ( $aui_bs5 ? 'me-3 ms-1' : 'mr-3 ml-1' ); ?>">&nbsp;<?php echo $listing_slug; ?>&nbsp;</label>
								<?php
								$cat_display = 'select';
								$post_cat    = isset( $qry_result->category ) ? $qry_result->category : '';
								$placeholder = wp_sprintf( __( 'Select %s Categories...', 'geodir_reviewratings' ), $listing_slug );
								?>
								<select id="categories_type_<?php echo $i; ?>" name="categories_type_<?php echo $i; ?>[]" multiple="multiple" class="aui-select2 w-100 <?php echo ( $aui_bs5 ? 'form-select ms-2' : 'custom-select ml-2' ); ?>" style="width:100%;<?php echo $display; ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" data-placeholder="<?php echo esc_attr( $placeholder ); ?>">
									<?php
									if ( class_exists( 'SitePress' ) ) {
										global $sitepress;
										$sitepress->switch_lang( 'all', true );
									}

									echo geodir_custom_taxonomy_walker( $p_type . 'category' );

									if ( class_exists( 'SitePress' ) ) {
										global $sitepress;
										$active_lang = ICL_LANGUAGE_CODE;
										$sitepress->switch_lang( $active_lang, true );
									}
									?>
								</select>
							</div>
							<?php
							$i ++;
						}
						?>

							</div>
						</div>

						<input type="hidden" value="<?php echo $i -= 1; ?>" name="number_of_post"/>

						<input type="hidden" name="rating_id" id="geodir_rating_id"
						       value="<?php echo absint( $rating['id'] ); ?>"/>
						<input type="hidden" name="security" id="geodir_save_rating_nonce"
						       value="<?php echo esc_attr( wp_create_nonce( 'geodir-save-rating' ) ); ?>"/>
					</div>
				</div>

				<p class="submit mt-2 <?php echo ( $aui_bs5 ? 'text-end' : 'text-right' ); ?>">
					<input name="save" class="btn btn-primary geodir-save-button" type="submit" id="save_rating" value="<?php esc_attr_e( 'Save Rating', 'geodir_reviewratings' ); ?>"/>
				</p>
			</div>