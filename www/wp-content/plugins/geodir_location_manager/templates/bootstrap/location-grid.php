<?php
/**
 * Display locations image grid view.
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/loop/location-grid.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://wpgeodirectory.com/docs-v2/faq/customizing/#templates
 * @package GeoDirectory/Templates
 * @version 2.2.9
 */


/**
 * Variables.
 *
 * @var string $base_location_link The location permalink.
 * @var string $grid_item_aspect The class for the item aspect.
 * @var integer $grid_per_row  The number of items per row.
 * @var string $grid_font_class The text classes.
 */
global $aui_bs5;
if ( ! empty( $container_wrapper ) ) {
	echo "<div class='geodir-location-grid-container row row-cols-1 row-cols-sm-2 row-cols-md-" . absint( $grid_per_row ) . " '>";
}

if ( ! empty( $locations ) ) {
	foreach ( $locations as $location ) {
		// Translate country
		if ( ! empty( $location->country ) ) {
			$location->country = __( $location->country, 'geodirectory' );
		}

		$location_args['image_class'] = "embed-item-cover-xy align-top  card-img";
		$image = GeoDir_Location_SEO::get_image_tag( $location, $location_args );
		?>
		<div class='col mb-4'>
			<div class="card h-100 shadow-sm p-0 card bg-dark text-white shadow-sm border-0 m-0">
				<a href="<?php echo geodir_location_permalink_url( $base_location_link . $location->location_link );?>" class="embed-has-action <?php echo esc_attr( $grid_item_aspect ); ?> stretched-link">
					<div class="gd-cptcat-cat-left border-0 m-0 overflow-hidden embed-responsive-item  d-inline-block mr-1 align-middle h1">
						<?php
						echo $image;
						?>
					</div>
				</a>
				<div class="card-img-overlay d-flex align-items-end text-center p-0 pb-3 <?php echo $aui_bs5 ? 'img-gradient-overlay' : 'bg-shadow-bottom'; ?>" style="z-index: 0;">
					<div class="card-body text-center btn btn-link  pb-2 overflow-hidden text-decoration-none">
						<h3 class="gd-cptcat-cat-right text-truncate text-white mb-0  <?php echo esc_attr($grid_font_class);?>">
							<?php

							echo $location->{$location_args['what']};


							?>
						</h3>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! empty( $container_wrapper ) ) {
	echo "</div>";
}
