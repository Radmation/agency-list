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

$script = false;
if ($login_alert && !get_current_user_id()) {
	$script = '<script type="text/javascript">alert("' . esc_attr(__('You must be logged-in to like a review comment.', 'geodir_reviewratings')) . '")</script>';
}

$like_action = 'like';
$like_class = '';
$like_text = wp_sprintf(__('%d people like this.', 'geodir_reviewratings'), $get_total_likes);
if ($has_liked) {
	$like_action = 'unlike';
	$like_class = ' gdrr-liked';
	if ($get_total_likes > 1) {
		$like_text = $get_total_likes == 2 ? __('You and 1 other like this.', 'geodir_reviewratings') : wp_sprintf(__('You and %d others like this.', 'geodir_reviewratings'), ($get_total_likes - 1));
	} else {
		$like_text = __('You like this.', 'geodir_reviewratings');
	}
}

$like_button = '<i class="fas fa-thumbs-up gdrr-btn-like"></i>';
/**
 * Filter the like/useful button html.
 *
 * @since 1.2.8
 * @package GeoDirectory_Review_Rating_Manager
 *
 * @param string $like_button Like/useful button content.
 * @param string $like_action Action to handle on button click (like or unlike).
 * @param bool $has_liked True if current user has liked this, otherwise false.
 */
$like_button = apply_filters('geodir_reviewrating_like_unlike_button', $like_button, $like_action, $has_liked);

$html = '<div class="comments_review_likeunlike' . $like_class . '" data-comment-id="' . (int) $comment_id . '" data-like-action="' . $like_action . '" data-wpnonce="' . esc_attr(wp_create_nonce('gd-like-' . (int)$comment_id)) . '"><span class="like_count">' . $like_button . $like_text . '</span>' . $script . '</div>';
echo $html;