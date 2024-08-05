<?php
/**
 * Pricing Table
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/pricing.php.
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
<div class="row row-cols-xs-1 <?php echo $wrap_class; ?>">
<?php 
foreach ( $packages as $key => $package ) {
	$template_args['package'] = $package;
	$template_args['display_name'] = $package->display_name;
	$template_args['display_price'] = $package->display_price;
	$template_args['display_lifetime'] = $package->display_lifetime;
	$template_args['package_link'] = $package->package_link;
	$template_args['highlight_class'] = '';
	$template_args['card_header_border_class'] = 'border-' . $card_border;
	$template_args['is_default'] = false;

	if ( ! empty( $package->is_default ) ) {
		$template_args['color'] = $color_highlight;
		$template_args['highlight_class'] = ' card-highlight';
		$template_args['is_default'] = true;
	} else {
		$template_args['color'] = $color_default;
	}

	echo geodir_get_template_html( 
		'bootstrap/loop/package.php', 
		$template_args,
		'',
		geodir_pricing_templates_path()
	);
}
?>
</div> 