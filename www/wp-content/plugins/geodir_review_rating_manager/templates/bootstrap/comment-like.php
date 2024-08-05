<?php
/**
 * Comment Like.
 *
 * @ver 1.0.0
 *
 * @var int $comment_id The comment id.
 * @var bool $login_alert If the login alert should show.
 * @var int $get_total_likes The total number of likes.
 * @var int $get_total_likes The total number of likes.
 * @var bool $has_liked If the current user has liked.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $gd_review_template, $aui_bs5;

$script = false;
if ($login_alert && !get_current_user_id()) {
	$script = '<script type="text/javascript">alert("' . esc_attr(__('You must be logged-in to like a review comment.', 'geodir_reviewratings')) . '")</script>';
}

$template = '';
$badge_class = '';
$like_class = '';
if ( $gd_review_template === 'clean' || ( isset( $_REQUEST['template'] ) && 'clean' === $_REQUEST['template'] ) ) {
	$like_class = ' btn-link';
	$template = 'clean';
	$badge_class = ' fs-xxs';
}else{
	$like_class = $has_liked ? ' btn btn-primary' : ' btn btn-outline-primary';
}

$like_action = $has_liked ? 'unlike' : 'like';
$get_total_likes = GeoDir_Review_Rating_Like_Unlike::format_like_count($get_total_likes);
$like_text = $has_liked ? __('Liked', 'geodir_reviewratings') : __('Like', 'geodir_reviewratings');
$like_text .= $get_total_likes ? ' <span class="badge ' . ( $has_liked ? 'badge-light bg-light' : 'badge-dark bg-dark' ) . $badge_class . '">' .$get_total_likes.'</span>' : '';

$like_button = $has_liked ? '<i class="fas fa-check gdrr-btn-like mr-1"></i>' : '<i class="fas fa-thumbs-up gdrr-btn-like mr-1"></i>';



$html = wp_doing_ajax() ? '' : '<span class="geodir-comment-like">';
$html .= '<div class="comments_review_likeunlike' . $like_class . $like_class . ' btn-sm d-inline-block c-pointer px-3" data-template="'.esc_attr( $template ) . '" data-comment-id="' . (int) $comment_id . '" data-like-action="' . $like_action . '" data-wpnonce="' . esc_attr(wp_create_nonce('gd-like-' . (int)$comment_id)) . '"><span class="like_count">' . $like_button .' '. $like_text . '</span>' . $script . '</div>';
$html .= wp_doing_ajax() ? '' : '</span>';

echo $html;