<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDirectory GeoDir_Review_Rating_Like_Unlike.
 *
 * Class for templates.
 *
 * @class    GeoDir_Review_Rating_Like_Unlike
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Review_Rating_Like_Unlike{

    public function __construct(){

    }

    /**
     * Adds CSS and JS for comments like / unlike
     *
     * @since 2.0.0
     *
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @global object $wpdb WordPress Database object.
     *
     * @param int $comment_id Comment ID.
     * @param bool $echo Print HTML? Default true.
     * @param bool $login_alert Whether to check and alert the user to login? Default false.
     * @param bool $ajax_action Is AJAX action? Default false.
     * @return void|string HTML.
     */
    public static function geodir_reviewrating_comments_like_unlike($comment_id, $echo = true, $login_alert = false, $ajax_action = false ) {
        $has_liked = self::geodir_reviewrating_has_comment_liked($comment_id);
        $get_total_likes = self::geodir_reviewrating_get_total_liked($comment_id);
        $design_style = geodir_design_style();

        $template = $design_style ? $design_style."/comment-like.php" : "legacy/comment-like.php";
        $args = array(
            'comment_id'    => $comment_id,
            'login_alert'  =>  $login_alert,
            'get_total_likes'  => $get_total_likes,
            'has_liked'  => $has_liked,
            'ajax_action' => $ajax_action
        );

        $html = geodir_get_template_html( $template , $args, '', plugin_dir_path( GEODIR_REVIEWRATING_PLUGIN_FILE ). "templates/");
        
        if ($echo)
            echo $html;
        else
            return $html;
    }

    /**
     * Check the comment is liked or not.
     *
     * @since 1.2.8
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @global object $wpdb WordPress Database object.
     *
     * @param int $comment_id The comment ID.
     * @return bool True if liked. Otherwise false.
     */
    public static function geodir_reviewrating_has_comment_liked($comment_id) {
        global $wpdb;

        if (!(int)$comment_id > 0) {
            return false;
        }

        $user_id = get_current_user_id();
        if (!$user_id > 0) {
            return false;
        }

        $query = $wpdb->prepare("SELECT COUNT(like_id) FROM `" . GEODIR_COMMENTS_REVIEWS_TABLE . "` WHERE comment_id = %d AND user_id = %d", array($comment_id, $user_id));
        $liked = $wpdb->get_var($query);

        if ($liked) {
            return true;
        }

        return false;
    }

    /**
     * Rating manager save comment like and dislike.
     *
     * @since 2.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @param int $comment_id The Comment ID.
     * @param string $like_action Like or dislike action.
     */
    public static function geodir_reviewrating_save_like_unlike($comment_id, $like_action) {
        if ($comment_id > 0 && ($like_action == 'like' || $like_action == 'unlike')) {
            $return = self::geodir_reviewrating_handle_like_unlike($comment_id, $like_action);
        }
        self::geodir_reviewrating_comments_like_unlike( $comment_id, true, true, true );
        exit;
    }

    /**
     * Handle and save like/unlike value.
     *
     * @since 2.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @global object $wpdb WordPress Database object.
     *
     * @param int $comment_id The comment ID.
     * @param string $action The comment usefull action (like or unlike). Default 'like'.
     * @return bool True on success. False on fail.
     */
    public static function geodir_reviewrating_handle_like_unlike($comment_id, $action = 'like') {
        global $wpdb;

        if (!(int)$comment_id > 0) {
            return false;
        }

        $user_id = get_current_user_id();
        if (!$user_id > 0) {
            return false;
        }
        $has_liked = self::geodir_reviewrating_has_comment_liked($comment_id);

        if ($action == 'like') {
            if (!$has_liked) {
                $ip = geodir_get_ip();
                $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                $like_date = date_i18n('Y-m-d H:i:s', current_time('timestamp'));

                $query = $wpdb->prepare("INSERT INTO `" . GEODIR_COMMENTS_REVIEWS_TABLE . "` SET comment_id = %d, ip = %s, user_id = %d, like_unlike = %d, user_agent = %s, like_date = %s", array($comment_id, $ip, $user_id, 1, $user_agent, $like_date));

                if ($wpdb->query($query)) {
                    $like_id = $wpdb->insert_id;

                    $query = $wpdb->prepare("UPDATE `" . GEODIR_REVIEWRATING_POSTREVIEW_TABLE . "` SET wasthis_review = wasthis_review + 1 WHERE comment_id = %d", array($comment_id));
                    $wpdb->query($query);

                    do_action('geodir_reviewrating_comment_liked', $like_id);

                    return true;
                }
            }
        } else {
            if ($has_liked) {
                $query = $wpdb->prepare("DELETE FROM `" . GEODIR_COMMENTS_REVIEWS_TABLE . "` WHERE comment_id = %d AND user_id = %d", array($comment_id, $user_id));

                if ($wpdb->query($query)) {
                    $query = $wpdb->prepare("UPDATE `" . GEODIR_REVIEWRATING_POSTREVIEW_TABLE . "` SET wasthis_review = wasthis_review - 1 WHERE comment_id = %d AND wasthis_review > 0", array($comment_id));
                    $wpdb->query($query);

                    do_action('geodir_reviewrating_comment_unliked', $comment_id, $user_id);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the total number of likes for comment.
     *
     * @since 2.0.0
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @global object $wpdb WordPress Database object.
     *
     * @param int $comment_id The comment ID.
     * @return int Total likes for comment.
     */
    public static function geodir_reviewrating_get_total_liked($comment_id) {
        global $wpdb;

        $liked = 0;

        if (!(int)$comment_id > 0) {
            return $liked;
        }

        $query = $wpdb->prepare("SELECT wasthis_review FROM `" . GEODIR_REVIEWRATING_POSTREVIEW_TABLE . "` WHERE comment_id = %d", array($comment_id));
        $liked = (int)$wpdb->get_var($query);

        return $liked;
    }

    /**
     * Format the like count.
     *
     * 1200 to 1.2.
     * 
     * @param $count
     *
     * @return string
     */
    public static function format_like_count($count){

        $count = absint($count);
        if ( $count ) {
            if($count > 999){
                $count = round(($count / 1000 ), 1)."k";
            }
        }
        
        return $count;
    }

}

new GeoDir_Review_Rating_Like_Unlike();