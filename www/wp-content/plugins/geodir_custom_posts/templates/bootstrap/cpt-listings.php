<?php
/**
 * GD Post Types List.
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/cpt-listings.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDir_Custom_Posts
 * @version    2.3.5
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;

?>
<div class="row position-relative <?php echo $wrap_class; ?>">
<?php
foreach ( $post_types as $post_type => $post_type_arr ) {
	$name = geodir_post_type_name( $post_type, true );

	$display_image = '';
	$display_name = '';

	if ( $display != 'name' ) {
		$image_size = 'medium';
		$attachment_src = array();

		if ( ! empty( $post_type_arr['default_image'] ) ) {
			$attachment_src = wp_get_attachment_image_src( (int) $post_type_arr['default_image'], $image_size );
		}

		if ( empty( $attachment_src ) && ( $attachment_id = absint( geodir_get_option( 'listing_default_image' ) ) ) ) {
			$attachment_src = wp_get_attachment_image_src( $attachment_id, $image_size );
		}

		$image_src = '';
		if ( ! empty( $attachment_src ) ) {
			list( $image_src, $_width, $_height ) = $attachment_src;
		}

		$image_src = apply_filters( 'geodir_cp_widget_cpt_listings_image_src', $image_src, $post_type );
		$display_image = $image_src ? 'background:url(' . $image_src . ') ' : '';
	}

	$has_image = ! empty( $display_image ) ? '1' : '0';
	if ( $display != 'image' ) {
		$display_name = $name;
	}

	$link = get_post_type_archive_link( $post_type );
	$card_image = $display_name ? 'card-img-top' : 'card-img';

	$item_class = 'gd-cpt-list-row gd-cpt-list-has-img-' . $has_image . ' gd-cpt-list-' . $post_type;
	$item_class .= ' col ' . $row_gap_class . ' ' . $column_gap_class;
	$item_class .= ' align-items-lg-stretch';
	if ( ! ( $aui_bs5 && $display != 'name' ) ) {
		$item_class .= ' d-flex';
	}
	$btn_class = $display == 'name' ? ' btn-outline-secondary' : '';
	?>
	<div class="<?php echo $item_class; ?>">
		<?php if ( $aui_bs5 && $display != 'name' ) { ?>
		<a class="card position-relative bg-size-cover bg-position-center overflow-hidden <?php echo sanitize_html_class( $card_border_class ) . ' ' . sanitize_html_class( $card_shadow_class ); ?>" href="<?php echo esc_url( $link ); ?>" style="<?php echo ( $card_height > 0 ? 'min-height:' . $card_height . 'px;' : '' ); ?><?php echo $display_image; ?>no-repeat center;">
			<span class="img-gradient-overlay"></span>
			<div class="card-body content-overlay pb-0"></div>
			<?php if ( $display_name ) { ?>
			<div class="card-footer content-overlay border-0 pt-0 pb-4 text-center">
				<h3 class="fs-base mb-0 <?php echo $text_color_class; ?>"><?php echo esc_attr ( $name ); ?></h3>
			</div>
			<?php } ?>
		</a>
		<?php } else { ?>
		<div class="card w-100 p-0 m-0 mw-100 btn <?php echo sanitize_html_class( $card_border_class ) . ' ' . sanitize_html_class( $card_shadow_class ); ?>" style="<?php echo ( $card_height > 0 ? 'min-height:' . $card_height . 'px' : '' ); ?>">
			<a class="card-link text-decoration-none d-block h-100 btn-link" href="<?php echo esc_url( $link ); ?>">
				<div style="<?php echo $display_image; ?>no-repeat center;background-size:cover;" class="w-100 h-100 hover-animate" title="<?php echo esc_attr ( $name ); ?>">
					<?php if ( $display_name ) { ?>
					<div class="d-flex align-items-center h-100 justify-content-center py-6 py-lg-7 <?php echo $btn_class; ?>"><span class="text-shadow text-uppercase m-4 <?php echo $text_color_class; ?>"><?php echo esc_attr ( $name ); ?></span></div>
					<?php } ?>
				</div>
			</a>
		</div>
		<?php } ?>
	</div>
	<?php
}
?></div> 