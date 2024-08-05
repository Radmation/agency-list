<?php
/**
 * Admin View: Notice - Updating
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$merge_ids = array();
?>

<div class="geodir-locations-list geodir-cities-list geodir-merge-location-div">
	<table class="wp-list-table widefat fixed striped cities">
		<thead>
			<tr>
				<td class="manage-column column-cb check-column"></td>
				<th scope="col" id="city" class="manage-column column-city column-primary"><?php _e( 'City', 'geodirlocation' ); ?></th>
				<th scope="col" id="city_slug" class="manage-column column-city_slug"><?php _e( 'Slug', 'geodirlocation' ); ?></th>
				<th scope="col" id="region" class="manage-column column-region"><?php _e( 'Region', 'geodirlocation' ); ?></th>
				<th scope="col" id="country" class="manage-column column-country"><?php _e( 'Country', 'geodirlocation' ); ?></th>
				<th scope="col" id="latitude" class="manage-column column-latitude"><?php _e( 'Latitude', 'geodirlocation' ); ?></th>
				<th scope="col" id="longitude" class="manage-column column-longitude"><?php _e( 'Longitude', 'geodirlocation' ); ?></th>
				<th scope="col" id="is_default" class="manage-column column-is_default"><?php _e( 'Default', 'geodirlocation' ); ?></th>
			</tr>
		</thead>
		<tbody id="the-list" data-wp-lists="list:city">
			<?php foreach ( $items as $item ) { $merge_ids[] = $item->location_id; ?>
			<tr>
				<th scope="row" class="check-column"><input type="radio" class="geodir-primary-city" value="<?php echo $item->location_id; ?>" id="geodir-primary-id-<?php echo $item->location_id; ?>" data-id="<?php echo $item->location_id ;?>" name="geodir_primary_city" /></th>
				<td class="city column-city has-row-actions column-primary column-field" data-field="city" data-value="<?php echo esc_attr( $item->city ); ?>" data-colname="<?php esc_attr_e( 'City', 'geodirlocation' ); ?>"><label for="geodir-primary-id-<?php echo $item->location_id; ?>"><?php echo $item->city; ?></label></td>
				<td class="city_slug column-city_slug" data-colname="<?php esc_attr_e( 'Slug', 'geodirlocation' ); ?>"><?php echo $item->city_slug; ?></td>
				<td class="region column-region column-field" data-field="region" data-value="<?php echo esc_attr( $item->region ); ?>" data-colname="<?php esc_attr_e( 'Region', 'geodirlocation' ); ?>"><?php echo $item->region; ?></td>
				<td class="country column-country column-field" data-field="country" data-value="<?php echo esc_attr( $item->country ); ?>" data-colname="<?php esc_attr_e( 'Country', 'geodirlocation' ); ?>"><?php echo $item->country; ?></td>
				<td class="latitude column-latitude column-field" data-field="latitude" data-value="<?php echo $item->latitude; ?>" data-colname="<?php esc_attr_e( 'Latitude', 'geodirlocation' ); ?>"><?php echo $item->latitude; ?></td>
				<td class="longitude column-longitude column-field" data-field="longitude" data-value="<?php echo $item->longitude; ?>" data-colname="<?php esc_attr_e( 'Longitude', 'geodirlocation' ); ?>"><?php echo $item->longitude; ?></td>
				<td class="longitude column-is_default" data-colname="<?php esc_attr_e( 'Default', 'geodirlocation' ); ?>"><?php echo (int)$item->is_default; ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<div><p><?php _e( 'NOTE: Select city for set primary city. Country must have ISO country name.', 'geodirlocation' ); ?></p></div>
	<div class="geodir-merge-data geodir-is-primary" data-id="<?php echo $items[0]->location_id; ?>">
		<table class="wp-list-table widefat fixed striped">
			<tbody>
				<tr>
					<td class="column-primary"><?php _e( 'City', 'geodirlocation' ); ?></td>
					<td class="column-field"><input id="city" name="city" type="text"></td>
					<td class="column-value"><a href="javascript:void(0);" data-field="city" data-value="<?php echo esc_attr( $items[0]->city ); ?>" class="geodir-merge-value"><i class="fas fa-angle-double-left"></i> <span><?php echo $items[0]->city; ?></span></td>
				</tr>
				<tr>
					<td class="column-primary"><?php _e( 'Region', 'geodirlocation' ); ?></td>
					<td class="column-field"><input id="region" name="region" type="text"></td>
					<td class="column-value"><a href="javascript:void(0);" data-field="region" data-value="<?php echo esc_attr( $items[0]->region ); ?>" class="geodir-merge-value"><i class="fas fa-angle-double-left"></i> <span><?php echo $items[0]->region; ?></span></td>
				</tr>
				<tr>
					<td class="column-primary"><?php _e( 'Country', 'geodirlocation' ); ?></td>
					<td class="column-field"><input id="country" name="country" readonly type="text"></td>
					<td class="column-value"><a href="javascript:void(0);" data-field="country" data-value="<?php echo esc_attr( $items[0]->country ); ?>" class="geodir-merge-value"><i class="fas fa-angle-double-left"></i> <span><?php echo $items[0]->country; ?></span></td>
				</tr>
				<tr>
					<td class="column-primary"><?php _e( 'Latitude', 'geodirlocation' ); ?></td>
					<td class="column-field"><input id="latitude" name="latitude" size="25" min="-90" max="90" step="any" lang="EN" type="number"></td>
					<td class="column-value"><a href="javascript:void(0);" data-field="latitude" data-value="<?php echo esc_attr( $items[0]->latitude ); ?>" class="geodir-merge-value"><i class="fas fa-angle-double-left"></i> <span><?php echo $items[0]->latitude; ?></span></td>
				</tr>
				<tr>
					<td class="column-primary"><?php _e( 'Longitude', 'geodirlocation' ); ?></td>
					<td class="column-field"><input id="longitude" name="longitude" size="25" min="-90" max="90" step="any" lang="EN" type="number"></td>
					<td class="column-value"><a href="javascript:void(0);" data-field="longitude" data-value="<?php echo esc_attr( $items[0]->longitude ); ?>" class="geodir-merge-value"><i class="fas fa-angle-double-left"></i> <span><?php echo $items[0]->longitude; ?></span></td>
				</tr>
				<tr>
					<td class="column-primary"></td>
					<td class=""><?php submit_button( __( 'Set Primary', 'geodirlocation' ), 'primary', 'set_primary_button' ); ?></td><td class=""><input type="hidden" name="security" id="geodir_merge_location_nonce" value="<?php echo wp_create_nonce( 'geodir-merge-location' ); ?>" /><input type="hidden" id="geodir_merge_ids" name="merge_ids" value="<?php echo implode( ',', $merge_ids ); ?>"></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>