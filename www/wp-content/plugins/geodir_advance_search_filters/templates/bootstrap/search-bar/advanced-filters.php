<?php
/**
 * GD Search Advanced Filters
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/search-bar/advanced-filters.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDir_Advance_Search_Filters
 * @version    2.2.2
 *
 * @global object $geodirectory GeoDirectory object.
 *
 * @vars
 * @var string $wrap_class Main wrapper CSS class.
 * @var string $form_class Form CSS class.
 * @var array  $instance Widget instance.
 * @var array  $keep_args Keep args.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="geodir-search-form-wrapper <?php echo esc_attr( $wrap_class ); ?>">
	<form class="w-100 d-block <?php echo esc_attr( $form_class ); ?>" data-show="<?php echo esc_attr( $show ); ?>" name="geodir-listing-search" action="<?php echo geodir_search_page_base_url(); ?>" method="get" style="box-sizing:content-box;">
		<?php
		/**
		 * Called inside the search form but after all the input fields.
		 *
		 * @since 2.2.2
		 *
		 * @param array $instance Widget instance.
		 */
		do_action( 'geodir_search_form_advanced_filters', $instance );
		?>
	</form>
</div>