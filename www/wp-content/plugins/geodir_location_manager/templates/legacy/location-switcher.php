<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Variables. (none)
 *
 */

global $geodirectory;
$location = $geodirectory->location;
?>
<div class="geodir-location-search-input-wrap">
	<a href="#location-switcher" title="<?php _e('Change Location','geodirlocation');?>">
		<div class="geodir-location-switcher-display">
			<?php
			echo '<span class="gd-icon-hover-swap geodir-search-input-label" onclick="var event = arguments[0] || window.event; geodir_cancelBubble(event); window.location = geodir_params.location_base_url;">';
			echo '<i class="fas fa-map-marker-alt gd-show"></i>';
			echo '<i class="fas fa-times geodir-search-input-label-clear gd-hide" title="' . esc_attr__( 'Clear Location', 'geodirlocation' ) . '"></i>';
			echo '</span>';
			if ( $location->type == 'me' ) {
				echo " " . wp_sprintf( __( 'Near: %s', 'geodirlocation' ), __( 'My Location', 'geodirectory' ) );
			} elseif ( $location->type == 'gps' ) {
				echo " " . wp_sprintf( __( 'Near: %s', 'geodirlocation' ), __( 'GPS Location', 'geodirlocation' ) );
			} elseif ( ! empty( $location->type ) ) {
				echo " " . wp_sprintf( __( 'In: %s (%s)', 'geodirlocation' ), __( $location->{$location->type}, 'geodirlocation' ), __( ucfirst( $location->type ),  'geodirlocation' ) );
			} else {
				echo " " . wp_sprintf( __( 'In: %s', 'geodirlocation' ),__( 'Everywhere', 'geodirlocation' ) );
			}
			?>
			<i class="fas fa-caret-down"></i>
		</div>
	</a>
</div>