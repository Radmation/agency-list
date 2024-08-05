<?php
/**
 * Comment Images
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/comment-images.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    Geodir_Review_Rating_Manager/Templates
 * @version    2.3.2
 *
 * @var int  $comment_id The comment id.
 * @var array  $comment_imgs An array of image objects.
 * @var string $lightbox_attrs Link attributes.
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;

$link_tag_open = "<a href='%s' class='geodir-lightbox-image embed-has-action embed-responsive embed-responsive-16by9 d-block' data-lity {$lightbox_attrs}>";
$link_tag_close = "<i class=\"fas fa-search-plus w-auto h-auto\" aria-hidden=\"true\"></i></a>";
?>
<div id="place-gallery-<?php echo $comment_id; ?>" class="geodir-images row row-cols-1 row-cols-md-3 mt-3 mb-n4 aui-gallery">
	<?php
	foreach( $comment_imgs as $image ) {
		echo '<div class="col mb-4">';
		echo '<div class="card m-0 p-0 overflow-hidden">';
		$img_tag = geodir_get_image_tag( $image, ( ! empty( $display_image_size ) ? $display_image_size : 'thumbnail' ), '', ' embed-responsive-item embed-item-cover-xy ' );
		$meta = ! empty( $image->metadata ) ? maybe_unserialize( $image->metadata ) : '';
		$img_tag =  wp_image_add_srcset_and_sizes( $img_tag, $meta , 0 );

		// image link
		$link = geodir_get_image_src( $image, ( ! empty( $link_image_size ) ? $link_image_size : 'thumbnail' ) );

		// ajaxify images
		if ( ! empty( $image_ajaxify ) ) {
			$img_tag = geodir_image_tag_ajaxify( $img_tag );
		}

		// output image
		echo sprintf( $link_tag_open, esc_url( $link ) );
		echo $img_tag;
		echo $link_tag_close;

		$title = '';
		$caption = '';

		if ( ! empty( $image->title ) ) {
			$title = esc_attr( stripslashes( $image->title ) );
		}

		if ( ! empty( $image->caption ) ) {
			$caption .= esc_attr( stripslashes( $image->caption ) );
		}

		if ( $title || $caption ) {
			?>
			<div class="carousel-caption d-none d-md-block p-0 m-0 py-1 w-100 rounded-bottom sr-only visually-hidden" style="bottom: 0;left:0;background: #00000060">
				<h5 class="m-0 p-0 h6 text-white <?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo $title; ?></h5>
				<p class="m-0 p-0 h6 text-white"><?php echo $caption; ?></p>
			</div>
			<?php
		}

		echo '</div>';
		echo '</div>';
	}
	?>
</div>