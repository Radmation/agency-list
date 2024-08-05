<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Geodir_Reviews_Fields_Settings', false ) ) :

	class Geodir_Reviews_Fields_Settings extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'reviews_fields';
			$this->label = __( 'Reviews', 'geodir_reviewratings' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
//			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );

			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );

            add_action( 'geodir_admin_field_display_reviews', array( $this, 'display_reviews' ) );
		}

       

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
            $sections = array(
                ''          => __( 'All', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count().')',
                'pending'   => __( 'Pending', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count('pending').')',
                'approved'=> __( 'Approved', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count('approved').')',
                'spam' 	=> __( 'Spam', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count('spam').')',
                'trash' 	=> __( 'Trash', 'geodir_reviewratings' ).' ('.geodir_reviewrating_get_comments_count('trash').')',
            );

			return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {

            if( 'trash' == $current_section ) {
                $settings = apply_filters( 'geodir_reviews_trash_options', array(
                    array(
                        'name' => __( 'Trashed Reviews', 'geodir_reviewratings' ),
                        'type' => 'display_reviews',
                        'desc' => '',
                        'id' => 'trash_reviews'
                    ),

                    array( 'type' => 'sectionend', 'id' => 'trash_reviews' ),
                ));
            } elseif ( 'spam' == $current_section ) {
                $settings = apply_filters( 'geodir_reviews_spam_options',
                    array(
                        array(
                            'name' => __( 'Spammed Reviews', 'geodir_reviewratings' ),
                            'type' => 'display_reviews',
                            'desc' => '',
                            'id' => 'spam_reviews'
                        ),

                        array( 'type' => 'sectionend', 'id' => 'spam_reviews' ),
                    )
                );
            } elseif ( 'approved' == $current_section ) {
                $settings = apply_filters( 'geodir_reviews_approved_options',
                    array(
                        array(
                            'name' => __( 'Approved Reviews', 'geodir_reviewratings' ),
                            'type' => 'display_reviews',
                            'desc' => '',
                            'id' => 'approved_reviews'
                        ),

                        array( 'type' => 'sectionend', 'id' => 'approved_reviews' ),
                    )
                );
            } elseif ( 'pending' == $current_section ) {
                $settings = apply_filters( 'geodir_reviews_pending_options',
                    array(
                        array(
                            'name' => __( 'Pending Reviews', 'geodir_reviewratings' ),
                            'type' => 'display_reviews',
                            'desc' => '',
                            'id' => 'pending_reviews'
                        ),

                        array( 'type' => 'sectionend', 'id' => 'pending_reviews' ),
                    )
                );
            } else {
                $settings = apply_filters( 'geodir_reviews_all_options', array(
                    array(
                        'name' => __( 'All Reviews', 'geodir_reviewratings' ),
                        'type' => 'display_reviews',
                        'desc' => '',
                        'id' => 'all_reviews'
                    ),

                    array( 'type' => 'sectionend', 'id' => 'all_reviews' ),
                ));
            }

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}

        public function display_reviews() {
            // Hide the save button
            $GLOBALS['hide_save_button'] = true;

            $geodir_commentsearch = isset($_REQUEST['geodir_comment_search']) && $_REQUEST['geodir_comment_search'] != '' ? sanitize_text_field($_REQUEST['geodir_comment_search']) : '';
            $post_type = isset($_REQUEST['geodir_comment_posttype']) && $_REQUEST['geodir_comment_posttype'] != '' ? sanitize_text_field($_REQUEST['geodir_comment_posttype']) : '';
            $status = sanitize_text_field($_REQUEST['section']);

            $orderby = 'comment_date_gmt';
            $order = 'DESC';

            $geodir_comment_sort = isset($_REQUEST['geodir_comment_sort']) ? sanitize_text_field($_REQUEST['geodir_comment_sort']) : '';
            $paged = isset($_REQUEST['paged']) ? (int)$_REQUEST['paged'] : 1;

            if ($geodir_comment_sort == 'oldest') {
                $orderby = 'comment_date_gmt';
                $order = 'ASC';
            } else if ($geodir_comment_sort == 'lowest_rating') {
                $orderby = 'rating';
                $order = 'ASC';
            } else if($geodir_comment_sort == 'highest_rating') {
                $orderby = 'rating';
                $order = 'DESC';
            }

            if ($comments_per_page = get_option('comments_per_page'))
                $show_post = $comments_per_page;
            else
                $show_post = 20;

            $defaults = array(
                'paged' => $paged,
                'show_post' => $show_post,
                'orderby' => $orderby,
                'order' => $order,
                'post_type' => $post_type,
                'comment_approved' => $status,
                'user_id' => '',
                'search' => $geodir_commentsearch,
            );

            $comments = geodir_reviewrating_get_comments($defaults);
            $nonce = wp_create_nonce('geodir_review_action_nonce');

            $geodir_commentsearch = $geodir_commentsearch != '' ? esc_attr(stripslashes($geodir_commentsearch)) : ''
            ?>
            <div style="float:right;margin-top:0px;">
            <?php $this->geodir_reviewrating_pagination($comments['comment_count']);?></div>
            <div style="clear:both;"></div>
            <div class="gd-content-heading" style="display:block">
                <h3>
                    <div class="clearfix">
                        <input name="checkedall" type="checkbox" value="" style="float:left; margin-top:8px;" />
                        <div class="three-tab">
                            <ul class="clearfix">
                                <?php if ($_REQUEST['section'] == 'pending') { ?>
                                    <li action="approvecomment"><a href="javascript:void(0);"><?php _e('Approve', 'geodir_reviewratings');?></a></li>
                                    <li action="spamcomment"><a href="javascript:void(0);"><?php _e('Spam', 'geodir_reviewratings');?></a></li>
                                    <li action="trashcomment"><a href="javascript:void(0);"><?php _e('Trash', 'geodir_reviewratings');?></a></li>
                                <?php } else if ($_REQUEST['section'] == 'approved') { ?>
                                    <li action="unapprovecomment"><a href="javascript:void(0);"><?php _e('Unapprove', 'geodir_reviewratings');?></a></li>
                                    <li action="spamcomment"><a href="javascript:void(0);"><?php _e('Spam', 'geodir_reviewratings');?></a></li>
                                    <li action="trashcomment"><a href="javascript:void(0);"><?php _e('Trash', 'geodir_reviewratings');?></a></li>
                                <?php } else if ($_REQUEST['section'] == 'spam') { ?>
                                    <li action="unspamcomment"><a href="javascript:void(0);"><?php _e('Not Spam', 'geodir_reviewratings');?></a></li>
                                    <li action="deletecomment"><a href="javascript:void(0);"><?php _e('Delete Permanently', 'geodir_reviewratings');?></a></li>
                                <?php } else if ($_REQUEST['section'] == 'trash') { ?>
                                    <li action="untrashcomment"><a href="javascript:void(0);"><?php _e('Restore', 'geodir_reviewratings');?></a></li>
                                    <li action="deletecomment"><a href="javascript:void(0);"><?php _e('Delete Permanently', 'geodir_reviewratings');?></a></li>
                                <?php } else { ?>
                                    <li action="approvecomment"><a href="javascript:void(0);"><?php _e('Approve', 'geodir_reviewratings');?></a></li>
                                    <li action="unapprovecomment"><a href="javascript:void(0);"><?php _e('Unapprove', 'geodir_reviewratings');?></a></li>
                                    <li action="spamcomment"><a href="javascript:void(0);"><?php _e('Spam', 'geodir_reviewratings');?></a></li>
                                    <li action="trashcomment"><a href="javascript:void(0);"><?php _e('Trash', 'geodir_reviewratings');?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                        <form>
                            <input type="hidden" name="geodir_review_action_nonce_field" value="<?php echo esc_attr( $nonce ); ?>" />
                            <input type="hidden" name="review_url" value="<?php echo admin_url('admin.php?page=gd-settings');?>" />
                            <input type="hidden" name="geodir_review_paged" value="<?php echo esc_attr( $paged );?>" />
                            <input type="hidden" name="geodir_review_show_post" value="<?php echo absint( $show_post );?>" />
                            <input type="hidden" name="tab" value="reviews_fields" />
                            <input type="hidden" name="section" value="<?php echo esc_attr( $status );?>" />
                            <div class="gd-search"><input name="geodir_comment_search" value="<?php echo $geodir_commentsearch;?>" type="text" /></div>
                            <div class="gd-search">
                                <?php
                                $geodir_post_types = geodir_get_option('post_types');
                                $geodir_posttypes = geodir_get_posttypes();
                                ?>
                                <select name="geodir_comment_posttype" id="commentposttype">
                                    <option value=""><?php _e('Show all post types', 'geodir_reviewratings');?></option>
                                    <?php
                                    if (!empty($geodir_posttypes)) {
                                        foreach ($geodir_posttypes as $p_type) {
                                            $geodir_posttype_info = $geodir_post_types[$p_type];
                                            $listing_slug = $geodir_posttype_info['labels']['singular_name'];

                                            echo '<option value="' . esc_attr( $p_type ) . '" ' . selected( $p_type, $post_type, false ). '>' . esc_html( $listing_slug ) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="gd-sort">
                                <label><?php _e('Sort :');?></label>
                                <select name="geodir_comment_sort">
                                    <option <?php selected('newest', $geodir_comment_sort) ?> value="newest"><?php _e('Newest', 'geodir_reviewratings');?></option>
                                    <option <?php selected('oldest', $geodir_comment_sort) ?> value="oldest"><?php _e('Oldest', 'geodir_reviewratings');?></option>
                                    <option <?php selected('lowest_rating', $geodir_comment_sort) ?> value="lowest_rating"><?php _e('Lowest rating', 'geodir_reviewratings');?></option>
                                    <option <?php selected('highest_rating', $geodir_comment_sort) ?> value="highest_rating"><?php _e('Highest rating', 'geodir_reviewratings');?></option>
                                </select>
                            </div>
                            <div class="gd-search" style="padding-top:2px;">
                                <input id="gdcomment-filter_button" class="button-primary" type="button" name="searchfilter" value="<?php _e('Filter', 'geodir_reviewratings');?>" />
                            </div>
                        </form>
                    </div>
                </h3>
                <div class="comment-listing"><?php GeoDir_Review_Rating_Template::geodir_reviewrating_show_comments($comments['comments']);?></div>
            </div>
            <?php
        }

        /**GD settings 'reviews' tab pagination.
         *
         * @since 2.0.0
         * @package GeoDirectory_Review_Rating_Manager
         *
         * @param array $comments Comments array.
         */
        public function geodir_reviewrating_pagination($comments=array()){

            global $show_post, $paged;

            if($paged == 0)
            {
                $paged = 1;
            }

            if($show_post > 0 && $show_post < $comments && $paged > 0)
            {

                $total_pages_exp = explode('.', $comments/$show_post);

                $total_pages = $total_pages_exp[0];

                if(isset($total_pages_exp[1]) && $total_pages_exp[1] > 0)
                    $total_pages = $total_pages_exp[0]+1;

                $previous_link = 1;
                if($paged > 1)
                    $previous_link = $paged-1;


                $next_link = $paged+1;

                if($next_link > $total_pages)
                    $next_link = $paged;
                ?>

                <div id="gd_pagging">

                <span><?php echo $comments;?> <?php _e('Items', 'geodir_reviewratings');?></span>

                <span>
                    <a class="<?php if($paged == 1){echo "disabled";}?>" title="<?php _e('Go to the first page', 'geodir_reviewratings');?>" style="text-decoration:none;" href="<?php echo esc_url( remove_query_arg( 'paged', get_permalink() ));?>">&laquo;</a>
                </span>

                <span>
                    <a class="<?php if($paged == 1){echo "disabled";}?>" title="<?php _e('Go to the previous page', 'geodir_reviewratings');?>" style="text-decoration:none;" href="<?php echo esc_url( add_query_arg( 'paged', $previous_link, get_permalink() ) );?>"> &lt;</a>
                </span>

                <span>
                    <input type="text" value="<?php echo absint( $paged ); ?>" style="width:30px; text-align:center;" /> <?php _e('of', 'geodir_reviewratings');?> <?php echo absint( $total_pages );?>
                </span>

                <span>
                    <a class="<?php if($paged == $total_pages){echo "disabled";}?>" title="<?php _e('Go to the next page', 'geodir_reviewratings');?>" style="text-decoration:none;" href="<?php echo esc_url( add_query_arg( 'paged', $next_link, get_permalink() ));?>">&gt;</a>
                </span>

                <span>
                    <a class="<?php if($paged == $total_pages){echo "disabled";}?>" title="<?php _e('Go to the last page', 'geodir_reviewratings');?>" style="text-decoration:none;" href="<?php echo esc_url( add_query_arg( 'paged', $total_pages, get_permalink()) );?>">&raquo;</a>
                </span>

                </div><?php
            }
        }

	}

endif;

return new Geodir_Reviews_Fields_Settings();
