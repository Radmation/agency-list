<?php

/**

 * The template for displaying Comments.

 *

 * The area of the page that contains both current comments

 * and the comment form.

 *

 * @since 1.0.0

 * @since 1.5.4 Modified to fix review sorting.

 *

 * @package GeoDirectory

 */



/*

 * If the current post is protected by a password and

 * the visitor has not yet entered the password we will

 * return early without loading the comments.

 */

global $preview;

if (post_password_required() || $preview)

    return;

?>



<div id="comments" class="comments-area geodir-comments-area dws-geodir-comments-area" style="margin-top: 0;">

    <div class="commentlist-wrap">



        <?php // You can start editing here -- including this comment! ?>



        <?php

        /**

         * Called before displaying reviews.

         *

         * If you would like to wrap reviews inside a div this is the place to print your open div. @see geodir_before_review_form to print your closing div.

         *

         * @since 1.5.7

         */

        do_action('geodir_before_review_list'); ?>



        <!--<h2 class="comments-title">

            <?php // echo 'Reviews&nbsp;&nbsp;<span class="dws_accent">' . number_format_i18n(get_comments_number()) . '</span>'; ?>

        </h2>-->



        <?php echo do_shortcode('[elementor-template id="4445"]'); ?>



        <?php if (have_comments()) : ?>



            <ul class="commentlist">

                <?php wp_list_comments(array('callback' => 'geodir_comment', 'reverse_top_level' => null, 'style' => 'ol')); ?>

            </ul><!-- .commentlist -->



            <?php $max_pages = get_comment_pages_count(); ?>

            <?php if (($GLOBALS['cpage'] ?? 1) < $max_pages): ?>

            <div id="dws_next_reviews" style="display: none;">

                <?php echo get_comments_pagenum_link(isset($GLOBALS['cpage']) ? ($GLOBALS['cpage'] + 1) : 2); ?>

            </div>

            <?php endif; ?>



            <?php

            /* If there are no comments and comments are closed, let's leave a note.

             * But we only want the note on posts and pages that had comments in the first place.

             */

            if (!comments_open() && get_comments_number()) : ?>

                <p class="nocomments"><?php _e('Reviews are closed.', 'geodirectory'); ?></p>

            <?php endif; ?>



        <?php else: ?>



        <?php endif; // have_comments() ?>



        <?php

        /**

         * Called before displaying "Leave a review form".

         *

         * If you would like to wrap "review form" inside a div this is the best place to hook your open div. @see geodir_after_review_form to print your closing div.

         * Also If you would like to wrap "reviews" inside a div this is the best place to print your closing div. @see geodir_before_review_list to print your open div.

         *

         * @since 1.5.7

         */

        do_action('geodir_before_review_form'); ?>

    </div>

    <?php

        /**

         * Filters comment form args

         *

         * If you would like to modify your comment form args, use this filter. @see https://codex.wordpress.org/Function_Reference/comment_form for accepted args.

         *

         * @since 1.0.0

         */

        $args = apply_filters('geodir_review_form_args', array(

            'title_reply' => '', 'logged_in_as' => '',

            'label_submit' => __('Leave a Review', 'geodirectory'),

            'comment_field' => '<p class="comment-form-comment"><label for="comment">' . __('Your review', 'geodirectory') . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true" placeholder="' . __('Describe your review', 'geodirectory') . '"></textarea></p>',

            'must_log_in' => '<p class="must-log-in">' . sprintf(__('You must be <a href="%s">logged in</a> to post a comment.', 'geodirectory'), geodir_login_url()) . '</p>'

        ));

        comment_form($args);

    ?>



    <?php

        /**

         * Called after displaying "Leave a review form".

         *

         * If you would like to wrap "review form" inside a div this is the best place to print your closing div. @see geodir_before_review_form to print your open div.

         *

         * @since 1.5.7

         */

        do_action('geodir_after_review_form');

    ?>



</div><!-- #comments .comments-area -->