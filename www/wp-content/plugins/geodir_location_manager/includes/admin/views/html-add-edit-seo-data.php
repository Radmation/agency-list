<?php
/**
 * Admin View: Notice - Updating
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="geodir-save-seo-div">
	<table class="form-table">
		<tbody>
			<?php if ( $seo->location_type == 'region' ) { ?>
			<tr valign="top" class="formlabel">
				<th scope="row" class="titledesc">
					<label><?php _e( 'Region', 'geodirlocation' ); ?></label>
				</th>
				<td class="forminp forminp-text"><?php echo $seo->region; ?> ( <?php echo $seo->region_slug; ?> )<input type="hidden" name="region_slug" id="geodir_region_slug" value="<?php echo $seo->region_slug; ?>" /></td>
			</tr>
			<?php } ?>
			<tr valign="top" class="formlabel">
				<th scope="row" class="titledesc">
					<label><?php _e( 'Country', 'geodirlocation' ); ?></label>
				</th>
				<td class="forminp forminp-text"><?php echo $seo->country; ?> ( <?php echo $seo->country_slug; ?> )<input type="hidden" name="country_slug" id="geodir_country_slug" value="<?php echo $seo->country_slug; ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="location_meta_title"><?php _e( 'Meta Title', 'geodirlocation' ); ?></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The meta title.', 'geodirlocation' ); ?>"></span>	
				</th>
				<td class="forminp forminp-textarea">
					<input type="text" name="location_meta_title" id="location_meta_title" value="<?php echo esc_attr( $seo->meta_title ); ?>" class="regular-text">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="location_meta_description"><?php _e( 'Meta Description', 'geodirlocation' ); ?></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The meta description.', 'geodirlocation' ); ?>"></span>	
				</th>
				<td class="forminp forminp-textarea">
					<textarea name="location_meta_description" id="location_meta_description" class="regular-text code"><?php echo esc_attr( $seo->meta_desc ); ?></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="location_description"><?php _e( 'Location Description', 'geodirlocation' ); ?></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The location description of the city.', 'geodirlocation' ); ?>"></span>	
				</th>
				<td class="forminp forminp-textarea">
					<textarea name="location_description" id="location_description" class="regular-text code"><?php echo esc_attr( $seo->location_desc ); ?></textarea>
				</td>
			</tr>
			<?php
			$image = array();
			$image[] = array(
				'name' => __( 'Featured Image', 'geodirlocation' ),
				'desc' => __( 'This is implemented by some themes to show a location specific image.', 'geodirlocation' ),
				'id' => 'location_image',
				'type' => 'image',
				'default' => 0,
				'desc_tip' => true,
				'value' => $seo->image
			);
			GeoDir_Admin_Settings::output_fields( $image );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="location_image_tagline"><?php _e( 'Image Tagline', 'geodirlocation' ); ?></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The location image tagline.', 'geodirlocation' ); ?>"></span>	
				</th>
				<td class="forminp forminp-textarea">
					<textarea name="location_image_tagline" id="location_image_tagline" class="regular-text code"><?php echo esc_attr( $seo->image_tagline ); ?></textarea>
				</td>
			</tr>
			<?php 
			$post_types = geodir_get_posttypes();
			foreach ( $post_types as $post_type ) {
				if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					continue;
				}

				$id = 'location_cpt_description_' . $post_type;
				$name = 'location_cpt_description[' . $post_type .']';
				$post_type_name = geodir_post_type_name( $post_type, true );
				$_cpt_desc = ! empty( $seo->cpt_desc ) && isset( $seo->cpt_desc[ $post_type ] ) ? stripslashes( $seo->cpt_desc[ $post_type ] ) : '';

				$settings = apply_filters( 'geodir_location_cpt_desc_editor_settings', array( 'wpautop' => false, 'media_buttons' => false, 'editor_height' => 80, 'textarea_rows' => 5, 'textarea_name' => $name ), $id, $name );
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo $id; ?>"><?php echo wp_sprintf( __( '%s Description', 'geodirlocation' ), $post_type_name ); ?></label>
					</th>
					<td class="forminp forminp-textarea">
						<?php wp_editor( $_cpt_desc, $id, $settings ); ?>
						<?php if ( $seo->location_type == 'region' ) { ?>
						<p class="description"><?php echo wp_sprintf( __( '%s description to show for this region.', 'geodirlocation' ), $post_type_name ); ?></p>
						<?php } else { ?>
						<p class="description"><?php echo wp_sprintf( __( '%s description to show for this country.', 'geodirlocation' ), $post_type_name ); ?></p>
						<?php } ?>
					</td>
				</tr>
			<?php } ?>
			<tr valign="top">
				<th scope="row"></th>
				<td class="forminp">
					<input type="hidden" name="location_type" id="geodir_location_type" value="<?php echo $seo->location_type; ?>" />
					<input type="hidden" name="security" id="geodir_save_seo_nonce" value="<?php echo wp_create_nonce( 'geodir-save-seo' ); ?>" />
					<?php submit_button( __( 'Save', 'geodirlocation' ), 'primary', 'save_seo_button' ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>