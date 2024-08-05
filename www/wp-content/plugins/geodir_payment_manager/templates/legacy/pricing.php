<?php
/**
 * Pricing Table
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/legacy/pricing.php.
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
<div class="gdpmui geodir-pricing-container">
	<div class="gdpm-row gdpm-p-3">
<?php 
foreach ( $packages as $key => $package ) {
	$template_args['package'] = $package;
	$template_args['display_name'] = $package->display_name;
	$template_args['display_price'] = $package->display_price;
	$template_args['display_lifetime'] = $package->display_lifetime;
	$template_args['package_link'] = $package->package_link;
	$template_args['card_class'] = '';

	if ( ! empty( $package->is_default ) ) {
		$template_args['color'] = $color_highlight;
		$template_args['card_class'] = 'gdpm-card-highlight';
	} else {
		$template_args['color'] = $color_default;
		$template_args['card_class'] = '';
	}

	echo geodir_get_template_html( 
		'legacy/loop/package.php', 
		$template_args,
		'',
		geodir_pricing_templates_path()
	);
}
?>
	</div>
</div>