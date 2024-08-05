<?php
/**
 * Post Expired Message
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/view/post-expired-text.php.
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
<div class="alert alert-info gd-has-expired" role="alert"><i class="fas fa-exclamation-circle"></i> <?php echo wp_sprintf( __( 'This %s appears to have expired.', 'geodir_pricing' ), $cpt_name ); ?></div>
