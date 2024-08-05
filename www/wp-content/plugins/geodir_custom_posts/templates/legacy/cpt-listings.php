<?php
/**
 * GD Post Types List.
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/legacy/cpt-listings.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDir_Custom_Posts
 * @version    2.1.0.0
 */

defined( 'ABSPATH' ) || exit;

?>
<ul class="<?php echo $wrap_class; ?>">
<?php
foreach ( $post_types as $post_type => $post_type_arr ) {
	$name = geodir_post_type_name( $post_type, true );

	$display_image = '';
	$display_name = '';

	if ( $display != 'name' ) {
		$image_size = 'thumbnail';
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

		if ( ! empty( $image_src ) ) {
			$display_image = '<img src="' . $image_src . '" class="attachment-' . esc_attr( $image_size ) . ' size-' . esc_attr( $image_size ) . '" alt="' . esc_attr ( $name ) . '" style="' . esc_attr( $style_height ) . '">';
		}

		$display_image = apply_filters( 'geodir_cp_widget_cpt_listings_image', $display_image, $post_type );
	}

	$has_image = ! empty( $display_image ) ? '1' : '0';
	if ( $display == 'image' ) {
		if ( empty( $display_image ) ) {
			$display_image = $name;
		}
	} else {
		$display_name = $name;
	}
	?>
	<li class="gd-cpt-list-row gd-cpt-list-has-img-<?php echo $has_image; ?> gd-cpt-list-<?php echo $post_type; ?>" style="<?php echo esc_attr( $style_width ); ?>">
		<a class="gd-cpt-list-link" href="<?php echo esc_url( get_post_type_archive_link( $post_type ) ); ?>" title="<?php echo esc_attr ( $name ); ?>">
			<?php if ( $display_image ) { ?>
			<span class="gd-cpt-list-img" style="<?php echo esc_attr( $style_height ); ?>"><?php echo $display_image; ?></span>
			<?php } ?>
			<?php if ( $display_name ) { ?>
			<span class="gd-cpt-list-name"><?php echo $display_name; ?></span>
			<?php } ?>
		</a>
	</li>
	<?php
}
?></ul> 