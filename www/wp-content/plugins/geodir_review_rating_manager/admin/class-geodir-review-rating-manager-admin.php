<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com/
 * @since      1.0.0
 *
 * @package    Geodir_review_rating_manager
 * @subpackage Geodir_review_rating_manager/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Geodir_review_rating_manager
 * @subpackage Geodir_review_rating_manager/admin
 * @author     GeoDirectory <info@wpgeodirectory.com>
 */
class GeoDir_Review_Rating_Manager_Admin {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if(is_admin()){
            add_action('init',array(__CLASS__,'maybe_upgrade'));
        }

        // Add the required DB columns
        add_filter('geodir_db_cpt_default_columns', array(__CLASS__,'add_db_columns'),10,3);
    }

    /**
     * Add the ratings column to the CPTs tables if ratings are not disabled.
     *
     * @param $columns
     * @param $cpt
     *
     * @return mixed
     */
    public static function add_db_columns($columns,$cpt,$post_type){

        // check if ratings are disabled on the CPT first.
        if(!isset($cpt['disable_reviews']) || !$cpt['disable_reviews']){
            $columns['ratings'] = 'ratings text NULL DEFAULT NULL';
        }

        return $columns;
    }

    public static function maybe_upgrade(){
        if( version_compare( get_option( "geodir_reviewrating_db_version"), GEODIR_REVIEWRATING_VERSION, '<' ) ){
            require_once(GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/includes/activator.php');
            GeoDir_Review_Rating_Manager_Activator::activate();
        }
    }

    /**
     * Redirects user to review rating manager settings page after plugin activation.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     */
    public function geodir_reviewrating_activation_redirect() {
        if (get_option('geodir_reviewrating_activation_redirect_opt', false)) {
            delete_option('geodir_reviewrating_activation_redirect_opt');
            wp_redirect(admin_url('admin.php?page=gd-settings&tab=review_rating'));
        }
    }

    public function geodir_reviewrating_admin_scripts( $hook ) {
        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        if ( ( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'review_rating' ) ) {
            wp_register_script( 'geodir-reviewrating-rating-js', GEODIR_REVIEWRATING_PLUGINDIR_URL .'/assets/js/admin-rating-script' . $suffix . '.js' );
            wp_enqueue_script( 'geodir-reviewrating-rating-js' );
        }

        if ( ( isset($_REQUEST['tab'] ) && $_REQUEST['tab'] == 'reviews_fields' ) ) {
            wp_register_script( 'geodir-reviewrating-review-script', GEODIR_REVIEWRATING_PLUGINDIR_URL.'/assets/js/comments-script' . $suffix . '.js' );
            wp_enqueue_script( 'geodir-reviewrating-review-script' );
        }
    }

    public function geodir_reviewrating_admin_styles($hook) {

        if ( ( isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'review_rating') || 'comment.php' == $hook) {
            wp_register_style( 'geodir-reviewrating-rating-admin-css', GEODIR_REVIEWRATING_PLUGINDIR_URL .'/assets/css/admin_style.css' );
            wp_enqueue_style( 'geodir-reviewrating-rating-admin-css' );
        }

        if (( isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'reviews_fields') || 'comment.php' == $hook) {
            wp_register_style( 'geodir-reviewrating-comments-admin-css', GEODIR_REVIEWRATING_PLUGINDIR_URL.'/assets/css/admin_style.css' );
            wp_enqueue_style( 'geodir-reviewrating-comments-admin-css' );
        }

    }

    public function load_settings_page( $settings_pages ) {

        $post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_title( $_REQUEST['post_type'] ) : 'gd_place';
        if ( !( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type.'-settings' ) ) {
            $settings_pages[] = include( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/admin/settings/class-geodir-review-rating-manager-settings.php' );
            //$settings_pages[] = include( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/admin/settings/class-geodir-reviews-fields-settings.php' );
        }

        return $settings_pages;
    }

    /**
     * Review Rating module related Post Metabox function.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     */
    public function geodir_reviewrating_comment_metabox(){

        add_meta_box('geodir_comment_individual', __('Individual ratings', 'geodir_reviewratings'), array($this, 'geodir_reviewrating_comment_rating_box'), 'comment', 'normal','low');

        if(geodir_get_option('rr_enable_images')){
            add_meta_box('geodir_comment_images', __('Images', 'geodir_reviewratings'), array($this, 'image_attachments'), 'comment', 'normal','low');
        }
    }

    public function image_attachments(){
        $comment_id = isset($_REQUEST['c']) ? absint($_REQUEST['c']) : '';
        $comment = get_comment( $comment_id );
        $files = '';
        if(isset($comment->comment_post_ID)){
            $files = GeoDir_Media::get_field_edit_string($comment->comment_post_ID,'comment_images','',$comment_id);
        }
       // echo '###'.$files;
        GeoDir_Review_Rating_Template::geodir_reviewrating_rating_img_html($files);
    }

    /**
     * new comments change unread to read.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     */
    public function geodir_reviewrating_reviews_change_unread_to_read(){

        global $wpdb, $plugin_prefix;

        if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'reviews_fields'):

            $wpdb->query("update ".GEODIR_REVIEWRATING_POSTREVIEW_TABLE." set read_unread='1' where read_unread = ''");

        endif;
    }

    /**
     * Get the rating star labels for translation
     *
     * @since 1.1.6
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @global object $wpdb WordPress database abstraction object.
     *
     * @param  array $translation_texts Array of text strings.
     * @return array
     */
    public function geodir_reviewrating_db_translation($translation_texts = array()) {
        global $wpdb;

        $translation_texts = !empty( $translation_texts ) && is_array( $translation_texts ) ? $translation_texts : array();

        // Overall star labels
        $overall_labels = geodir_get_option('geodir_reviewrating_overall_rating_texts');
        if (!empty($overall_labels)) {
            foreach ( $overall_labels as $label ) {
                if ( $label != '' )
                    $translation_texts[] = stripslashes_deep($label);
            }
        }

        // Rating style table
        $sql = "SELECT name, star_lables FROM `" . GEODIR_REVIEWRATING_STYLE_TABLE . "`";
        $rows = $wpdb->get_results($sql);

        if (!empty($rows)) {
            foreach($rows as $row) {
                if (!empty($row->name))
                    $translation_texts[] = stripslashes_deep($row->name);

                if (!empty($row->star_lables)) {
                    $labels = geodir_reviewrating_star_lables_to_arr($row->star_lables);

                    if (!empty($labels)) {
                        foreach ( $labels as $label ) {
                            if ( $label != '' )
                                $translation_texts[] = stripslashes_deep($label);
                        }
                    }
                }
            }
        }

        // Rating category table
        $sql = "SELECT title FROM `" . GEODIR_REVIEWRATING_CATEGORY_TABLE . "`";
        $rows = $wpdb->get_results($sql);

        if (!empty($rows)) {
            foreach($rows as $row) {
                if (!empty($row->title))
                    $translation_texts[] = stripslashes_deep($row->title);
            }
        }
        $translation_texts = !empty($translation_texts) ? array_unique($translation_texts) : $translation_texts;

        return $translation_texts;
    }

    

    /**
     * function for display geodirectory review rating error and success messages.
     *
     * @since 1.0.0
     * @package GeoDirectory_Review_Rating_Manager
     */
    function geodir_reviewrating_display_messages() {
        if (isset($_REQUEST['gdrr_success']) && $_REQUEST['gdrr_success'] != '') {
            echo '<div id="message" class="updated fade"><p><strong>' . sanitize_text_field($_REQUEST['gdrr_success']) . '</strong></p></div>';
        }

        if (isset($_REQUEST['gdrr_error']) && $_REQUEST['gdrr_error'] != '') {
            echo '<div id="payment_message_error" class="updated fade"><p><strong>' . sanitize_text_field($_REQUEST['gdrr_error']) . '</strong></p></div>';
        }
    }

    /**
     * Adds rating box to admin comment edit page.
     *
     * @since 1.0.0
     * @since 1.3.6 Changes for disable review stars for certain post type.
     * @package GeoDirectory_Review_Rating_Manager
     *
     * @global object $wpdb WordPress Database object.
     *
     * @param object $comment Comment object.
     * @return bool
     */
    function geodir_reviewrating_comment_rating_box($comment) {

        global $comment,$wpdb,$is_geodir_loop;

        $post_type = get_post_type( $comment->comment_post_ID );

        $all_postypes = geodir_get_posttypes();

        if (!in_array($post_type, $all_postypes)) {
            return false;
        }

        if (!empty($post_type) && geodir_cpt_has_rating_disabled($post_type)) {
            return false;
        }

        $comment_ratings = geodir_reviewrating_get_comment_rating_by_id($comment->comment_ID);
        if (!empty($comment_ratings)) {
            ?>
            <div id="gd_ratings_module">
                <div id="rating_frm" style="margin-top:15px;">
                    <div class="gd-rating-box-in clearfix">
                        <div class="gd-rating-box-in-left">
                            <?php
                            $post_type = get_post_type( $comment->comment_post_ID );

                            $post_categories = wp_get_post_terms( $comment->comment_post_ID, $post_type.'category', array( 'fields' => 'ids') );

                            $ratings = geodir_reviewrating_rating_categories();
                            $old_ratings = @unserialize($comment_ratings->ratings);

                            if($ratings):?>
                                <div class="gd-rate-category clearfix">
                                    <div><?php

                                        foreach($ratings as $rating):

                                            $star_lable = geodir_reviewrating_star_lables_to_arr( $rating->star_lables, (int) $rating->star_number, true );

                                            $rating->title = isset( $rating->title ) && $rating->title != '' ? __( stripslashes_deep( $rating->title ), 'geodirectory' ) : '';

                                            $rating_cat = explode(",",$rating->category);

                                            $showing_cat = array_intersect($rating_cat,$post_categories);

                                            if(!empty($showing_cat)){

                                                if($rating->check_text_rating_cond):
                                                    ?>
                                                    <div class="clearfix gd-rate-cat-in rating-<?php echo $rating->id;?>">
                                                        <?php
                                                            $overrides = array(
                                                                'rating_icon' => esc_attr( $rating->s_rating_icon ),
                                                                'rating_color' => esc_attr( $rating->star_color ),
                                                                'rating_color_off' => esc_attr( $rating->star_color_off ),
                                                                'rating_label' => $rating->title,
                                                                'rating_texts' => $star_lable,
                                                                'rating_image' => $rating->s_img_off,
                                                                'rating_type' => esc_attr( $rating->s_rating_type ),
                                                                'rating_input_count' => $rating->star_number,
                                                                'id' => "geodir_rating[".$rating->id."]",
                                                                'type' => 'input',
                                                            );

                                                            echo GeoDir_Comments::rating_html($old_ratings[$rating->id], 'input', $overrides);
                                                        ?>
                                                    </div>
                                                    <?php
                                                else:
                                                    ?>
                                                    <div class="clearfix gd-rate-cat-in">
                                                        <span class="gd-rating-label"><?php _e($rating->title, 'geodir_reviewratings');?></span>
                                                        <select name="geodir_rating[<?php echo $rating->id;?>]" >
                                                            <?php for($star=1; $star <= $rating->star_number; $star++){
                                                                $star_lable_text = isset( $star_lable[$star] ) ? esc_attr( $star_lable[$star] ) : '';
                                                                $star_lable_text = stripslashes_deep( $star_lable_text );
                                                                ?>
                                                                <option value="<?php echo $star;?>" <?php if($old_ratings[$rating->id]) echo 'selected="selected"'; ?>  ><?php echo $star_lable_text;?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <?php
                                                endif;

                                            }
                                        endforeach;?>

                                    </div>
                                </div>
                            <?php endif;?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
