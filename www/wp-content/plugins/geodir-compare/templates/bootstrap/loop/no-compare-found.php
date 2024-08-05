<?php
/**
 * Compare listings empty results content
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/loop/no-compare-found.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDir_Compare
 * @version    2.1.0.0
 */

defined( 'ABSPATH' ) || exit;

$message = __( "There are no listings to compare.", 'geodir-compare' );
?>
<div class="geodir-compare-page-empty-list">
	<?php echo apply_filters( 'geodir_no_listings_found_to_compare', $message ); ?>
</div>
