<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var string $wrap_class The wrapper classes.
 *
 */

global $geodirectory;
$location = $geodirectory->location;
?>
<div class="geodir-location-search-input-wrap <?php echo $wrap_class;?>">

	<a class="w-100 border d-block py-2 px-3 rounded d-flex justify-content-between text-muted text-decoration-none" href="#location-switcher"
	   title="<?php _e( 'Change Location', 'geodirlocation' ); ?>">
		<span class="text-muted">
			<span class="geodir-search-input-label hover-swap "
			      onclick="var event = arguments[0] || window.event; geodir_cancelBubble(event); window.location = geodir_params.location_base_url;">
				<i class="fas fa-map-marker-alt hover-content-original"></i>
				<i class="fas fa-times geodir-search-input-label-clear hover-content c-pointer"	title="<?php echo esc_attr__( 'Clear Location', 'geodirlocation' );?>"></i>
			</span>
				<?php
				if ( $location->type == 'me' ) {
					echo " " . wp_sprintf( __( 'Near: %s', 'geodirlocation' ), __( 'My Location', 'geodirectory' ) );
				} elseif ( $location->type == 'gps' ) {
					echo " " . wp_sprintf( __( 'Near: %s', 'geodirlocation' ), __( 'GPS Location', 'geodirlocation' ) );
				} elseif ( ! empty( $location->type ) ) {
					echo " " . wp_sprintf( __( 'In: %s (%s)', 'geodirlocation' ), __( $location->{$location->type}, 'geodirlocation' ), __( ucfirst( $location->type ), 'geodirlocation' ) );
				} else {
					echo " " . wp_sprintf( __( 'In: %s', 'geodirlocation' ), __( 'Everywhere', 'geodirlocation' ) );
				}
				?>
		</span>

		<span class="text-right text-end d-inline-block">
			<i class="fas fa-caret-down"></i>
		</span>

	</a>
</div>