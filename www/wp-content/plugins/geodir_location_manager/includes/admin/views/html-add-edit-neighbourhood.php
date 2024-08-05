<?php
/**
 * Admin View: Notice - Updating
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $aui_bs5, $mapzoom;
$prefix 	= 'neighbourhood_';
$map_title 	= __( "Set Address On Map", 'geodirlocation' );
$mapzoom = 10;
if ( ! empty( $neighbourhood->latitude ) && ! empty( $neighbourhood->longitude ) ) {
	$country = $neighbourhood->country;
	$region = $neighbourhood->region;
	$city = $neighbourhood->city;
	$lat = $neighbourhood->latitude;
	$lng = $neighbourhood->longitude;
}

echo '<div class="' . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . '">';
include( GEODIRECTORY_PLUGIN_DIR . 'templates/map.php' );
echo '</div>';
return;
?>
<div id="geodir-save-neighbourhood-div">
	<?php if ( empty( $neighbourhood->id ) ) { ?>
	<h2 class="gd-settings-title "><?php _e( 'Add neighbourhood', 'geodirlocation' ); ?></h2>
	<?php } else { ?>
	<h2 class="gd-settings-title "><?php echo __( 'Edit neighbourhood:', 'geodirlocation' ) . ' #' . $neighbourhood->id; ?>&nbsp;&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=gd-settings&tab=locations&section=neighbourhoods&add_neighbourhood=1&location_id=' . (int)$neighbourhood->location_id ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'geodirlocation' ); ?></a></h2>
	<?php } ?>
	<table class="form-table">
		<tbody>
			<?php if ( ! empty( $neighbourhood->slug ) ) { ?>
			<tr valign="top" class="formlabel">
				<th scope="row" class="titledesc">
					<label><?php _e( 'Slug', 'geodirlocation' ); ?></label>
				</th>
				<td class="forminp forminp-text">
					<?php echo esc_attr( $neighbourhood->slug ); ?>
				</td>
			</tr>
			<?php } ?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $prefix; ?>name"><?php _e( 'Neighbourhood', 'geodirlocation' ); ?> <span>*</span></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The neighbourhood name.', 'geodirlocation' ); ?>"></span>
				</th>
				<td class="forminp forminp-text">
					<input name="<?php echo $prefix; ?>name" id="<?php echo $prefix; ?>name" value="<?php echo esc_attr( $neighbourhood->neighbourhood ); ?>" class="regular-text" type="text" required>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $prefix; ?>city"><?php _e( 'City', 'geodirlocation' ); ?></label>
				</th>
				<td class="forminp forminp-text">
					<input name="<?php echo $prefix; ?>city" id="<?php echo $prefix; ?>city" value="<?php echo esc_attr( $neighbourhood->city ); ?>" class="regular-text" type="text">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $prefix; ?>region"><?php _e( 'Region', 'geodirlocation' ); ?></label>
				</th>
				<td class="forminp forminp-text">
					<input name="<?php echo $prefix; ?>region" id="<?php echo $prefix; ?>region" value="<?php echo esc_attr( $neighbourhood->region ); ?>" class="regular-text" type="text" required>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $prefix; ?>country"><?php _e( 'Country', 'geodirlocation' ); ?></label>
				</th>
				<td class="forminp">
					<select id="<?php echo $prefix; ?>country" name="<?php echo $prefix; ?>country" data-placeholder="<?php esc_attr_e( 'Choose a country...', 'geodirlocation' ); ?>" class="regular-text geodir-select" required>
						<?php echo geodir_get_country_dl( $neighbourhood->country ); ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<td class="forminp" colspan="2">
					<?php include( GEODIRECTORY_PLUGIN_DIR . 'templates/map.php' ); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $prefix; ?>latitude"><?php _e( 'Latitude', 'geodirlocation' ); ?> <span>*</span></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The latitude of the location.', 'geodirlocation' ); ?>"></span>
				</th>
				<td class="forminp forminp-number">
					<input name="<?php echo $prefix; ?>latitude" id="<?php echo $prefix; ?>latitude" value="<?php echo esc_attr( $neighbourhood->latitude ); ?>" class="regular-text" min="-90" max="90" step="any" type="number" lang="EN" required>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $prefix; ?>longitude"><?php _e( 'Longitude', 'geodirlocation' ); ?> <span>*</span></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The longitude of the location.', 'geodirlocation' ); ?>"></span>
				</th>
				<td class="forminp forminp-number">
					<input name="<?php echo $prefix; ?>longitude" id="<?php echo $prefix; ?>longitude" value="<?php echo esc_attr( $neighbourhood->longitude ); ?>" class="regular-text" min="-180" max="180" step="any" type="number" lang="EN" required>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $prefix; ?>meta_title"><?php _e( 'Meta Title', 'geodirlocation' ); ?></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The meta title.', 'geodirlocation' ); ?>"></span>	
				</th>
				<td class="forminp forminp-textarea">
					<input type="text" name="<?php echo $prefix; ?>meta_title" id="<?php echo $prefix; ?>meta_title" value="<?php echo esc_attr( $neighbourhood->meta_title ); ?>" class="regular-text">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $prefix; ?>meta_description"><?php _e( 'Meta Description', 'geodirlocation' ); ?></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The meta description.', 'geodirlocation' ); ?>"></span>	
				</th>
				<td class="forminp forminp-textarea">
					<textarea name="<?php echo $prefix; ?>meta_description" id="<?php echo $prefix; ?>meta_description" class="regular-text code"><?php echo esc_attr( $neighbourhood->meta_description ); ?></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo $prefix; ?>description"><?php _e( 'Location Description', 'geodirlocation' ); ?></label><span class="gd-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'The neighbourhood description.', 'geodirlocation' ); ?>"></span>
				</th>
				<td class="forminp forminp-textarea">
					<textarea name="<?php echo $prefix; ?>description" id="<?php echo $prefix; ?>description" class="regular-text code"><?php echo esc_attr( $neighbourhood->description ); ?></textarea>
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
				'value' => $neighbourhood->image
			);
			GeoDir_Admin_Settings::output_fields( $image );

			$post_types = geodir_get_posttypes();

			foreach ( $post_types as $post_type ) {
				if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					continue;
				}

				$id = $prefix . 'cpt_description_' . $post_type;
				$name = $prefix . 'cpt_description[' . $post_type .']';
				$post_type_name = geodir_post_type_name( $post_type, true );
				$_cpt_desc = ! empty( $neighbourhood->cpt_desc ) && isset( $neighbourhood->cpt_desc[ $post_type ] ) ? stripslashes( $neighbourhood->cpt_desc[ $post_type ] ) : '';

				$settings = apply_filters( 'geodir_location_cpt_desc_editor_settings', array( 'wpautop' => false, 'media_buttons' => false, 'editor_height' => 80, 'textarea_rows' => 5, 'textarea_name' => $name ), $id, $name );
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo $id; ?>"><?php echo wp_sprintf( __( '%s Description', 'geodirlocation' ), $post_type_name ); ?></label>
					</th>
					<td class="forminp forminp-textarea">
						<?php wp_editor( $_cpt_desc, $id, $settings ); ?>
						<p class="description"><?php echo wp_sprintf( __( '%s description to show for this neighbourhood.', 'geodirlocation' ), $post_type_name ); ?></p>
					</td>
				</tr>
			<?php } ?>
			<tr valign="top">
				<th scope="row"></th>
				<td class="forminp">
					<input type="hidden" name="neighbourhood_id" id="neighbourhood_id" value="<?php echo $neighbourhood->id; ?>" />
					<input type="hidden" name="neighbourhood_location_id" id="neighbourhood_location_id" value="<?php echo $location_id; ?>" />
					<input type="hidden" name="security" id="geodir_save_neighbourhood_nonce" value="<?php echo wp_create_nonce( 'geodir-save-neighbourhood' ); ?>" />
					<?php submit_button( __( 'Save Neighbourhood', 'geodirlocation' ), 'primary', 'save_neighbourhood_button' ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>