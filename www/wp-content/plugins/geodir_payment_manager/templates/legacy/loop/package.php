<?php
/**
 * Pricing Single Package
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/legacy/loop/package.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDir_Pricing_Manager
 * @version    2.6.0.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="gdpm-col-lg-4">
	<div class="gdpm-card gdpm-mb-5 gdpm-mb-lg-0 gdpm-border-1 gdpm-shadow <?php echo $card_class; ?>">
		<div class="gdpm-card-header">
			<h4 class="gdpm-my-0 gdpm-mb-1 gdpm-text-base gdpm-subtitle gdpm-text-center gdpm-py-3 gdpm-text-nowrap gdpm-text-<?php echo $color; ?>"><?php echo $display_name; ?></h4>
			<p class="gdpm-text-muted gdpm-text-center gdpm-mb-3"><span class="gdpm-h2 gdpm-text-dark"><?php echo $display_price; ?></span><span class="gdpm-ml-2">/ <?php echo $display_lifetime; ?></span></p>
		</div>
		<div class="gdpm-card-body">
			<ul class="fa-ul gdpm-my-2">
			<?php if ( ! empty( $package->features ) ) { ?>
				<?php foreach( $package->features as $feature => $data ) { ?>
				<li class="gdpm-mb-3" data-geodir-feature="<?php echo esc_attr( $feature ); ?>"><span class="fa-li gdpm-text-<?php echo esc_attr( $data['color'] ); ?>"><i class="<?php echo esc_attr( $data['icon'] ); ?>"></i></span><?php echo $data['text']; ?></li>
				<?php } ?>
			<?php } ?>
			</ul>
			<div class="gdpm-text-center"><a class="gdpm-btn gdpm-btn-<?php echo $color; ?> gdpm-btn-block" href="<?php echo esc_url( $package_link ); ?>"><?php _e( 'Select Plan', 'geodir_pricing' ); ?></a></div>
		</div>
	</div>
</div>