<?php
/**
 * Comment Images
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/legacy/comment-images.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    Geodir_Review_Rating_Manager/Templates
 * @version    2.1.0.5
 *
 * @var int  $comment_id The comment id.
 * @var array  $comment_imgs An array of image objects.
 * @var string $lightbox_attrs Link attributes.
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="place-gallery-<?php echo $comment_id; ?>" class="place-gallery geodir-image-container">
	<div class="clearfix reviews_rating_images_all_images">
		<ul class="geodir-gallery geodir-images clearfix">
			<?php
			$image_size = 'medium';
			$link_tag_open = "<a href='%s' class='geodir-lightbox-image' data-lity {$lightbox_attrs}>";
			$link_tag_close = "<i class=\"fas fa-search-plus\" aria-hidden=\"true\"></i></a>";
			foreach( $comment_imgs as $image ) {
				echo '<li>';
				$img_tag = geodir_get_image_tag($image,$image_size );
				$meta = isset($image->metadata) ? maybe_unserialize($image->metadata) : '';
				$img_tag =  wp_image_add_srcset_and_sizes( $img_tag, $meta , 0 );

				// image link
				$link = geodir_get_image_src($image, 'large');

				// ajaxify images
				$img_tag = geodir_image_tag_ajaxify($img_tag);

				// output image
				echo $link_tag_open ? sprintf($link_tag_open,esc_url($link)) : '';
				echo $img_tag;
				echo $link_tag_close;

				echo '</li>';
			}
			?>
		</ul>
	</div>
</div>