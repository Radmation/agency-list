<?php

class GeoDir_Review_Rating_Manager {

	private static $instance;

	private $version = GEODIR_REVIEWRATING_VERSION;

	/**
	 * Main GeoDir_Review_Rating_Manager Instance.
	 *
	 * Ensures only one instance of GeoDirectory Review Rating Manager is loaded or can be loaded.
	 *
	 * @since 2.0.0.0
	 * @static
	 * @see GeoDir()
	 * @return GeoDir_Review_Rating_Manager - Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Review_Rating_Manager ) ) {
			self::$instance = new GeoDir_Review_Rating_Manager;
			self::$instance->setup_globals();
			self::$instance->load_textdomain();
			self::$instance->includes();
			self::$instance->define_admin_hooks();
			self::$instance->define_public_hooks();

			do_action( 'geodir_review_rating_loaded' );
		}

		return self::$instance;
	}

	public static function clear_version_number(){
		update_option( 'geodir_reviewratings_db_version', '' );
	}

	private function __construct() {
		self::$instance = $this;
	}

	private function setup_globals() {
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new GeoDir_Review_Rating_Manager_Admin();

		add_filter( 'geodir_get_settings_pages', array( $plugin_admin, 'load_settings_page' ), 11, 1 );
		add_action('admin_init', array( $plugin_admin, 'geodir_reviewrating_activation_redirect'));
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'geodir_reviewrating_admin_scripts'), 11);
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'geodir_reviewrating_admin_styles'), 11);
		add_action('admin_head', array( $this, 'geodir_reviewrating_localize_all_js_msg'), 11);
		add_action( 'add_meta_boxes', array( $plugin_admin, 'geodir_reviewrating_comment_metabox'), 13 );
		add_action('admin_init', array( $plugin_admin, 'geodir_reviewrating_reviews_change_unread_to_read'));
		// Rating star labels translation
		add_filter('geodir_load_db_language', array( $plugin_admin, 'geodir_reviewrating_db_translation'));
		add_action('admin_init' , array( $plugin_admin, 'geodir_reviewrating_display_messages'));
		add_filter('geodir_diagnose_multisite_conversion' , array( $plugin_admin, 'geodir_diagnose_multisite_conversion_review_manager'), 10,1);
		do_action( 'gd_review_rating_setup_admin_actions' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		add_action( 'geodir_clear_version_numbers', array( __CLASS__, 'clear_version_number' ) );
		add_action('wp_footer', array( $this, 'geodir_reviewrating_localize_all_js_msg'));
		add_action('admin_head-media-upload-popup', array( $this, 'geodir_reviewrating_localize_all_js_msg'));

		$plugin_public = new GeoDir_Review_Rating_Manager_Public();

		add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
		add_filter('geodir_after_custom_detail_table_create', array( $plugin_public, 'geodir_reviewrating_after_custom_detail_table_create'),1,2);
		add_action( 'delete_comment', array( $plugin_public, 'geodir_reviewrating_delete_comments' ));
		add_action('wp_set_comment_status', array( $plugin_public, 'geodir_reviewrating_set_comment_status'),100,2);
		add_filter( 'wp_list_comments_args', array( $plugin_public, 'geodir_reviewrating_list_comments_args'), 10, 1 );
		add_filter( 'comments_template_query_args', array( $plugin_public, 'geodir_reviewrating_reviews_query_args'), 10, 1 );
		add_filter( 'comments_clauses', array( $plugin_public, 'geodir_reviewrating_reviews_clauses'), 10, 2 );
		add_action( 'geodir_create_new_post_type', array( $plugin_public, 'geodir_reviewrating_create_new_post_type'), 1, 1 );
		add_action( 'geodir_after_post_type_deleted', array( $plugin_public, 'geodir_reviewrating_delete_post_type'), 1, 1 );

		/* Show Comment Rating */
		if ( geodir_get_option( 'rr_enable_rating' ) || geodir_get_option( 'rr_enable_images' ) || geodir_get_option( 'rr_enable_rate_comment' ) || geodir_get_option( 'rr_enable_sorting' ) ) {
			add_filter( 'comment_text', array( $plugin_public, 'geodir_reviewrating_wrap_comment_text' ), 42, 2 );
		}

		/* Show Post Rating */
		if ( geodir_get_option( 'rr_enable_rating' ) && geodir_get_option( 'rr_enable_sorting' ) ) {
			add_action( "geodir_after_review_list_title", array( $plugin_public, 'sort_ratings_select' ), 15 );
		}

		if ( geodir_get_option( 'rr_enable_rating' ) ) {
			add_action( "geodir_comments_post_rating_counts_html", array( $plugin_public, 'show_overall_multiratings' ), 11 );
			add_action( "geodir_after_review_list_title", array( $plugin_public, 'show_overall_multiratings' ), 11 );
		}

		add_filter('geodir_reviews_rating_comment_shorting', array( $plugin_public, 'geodir_reviews_rating_update_comment_shorting_options'));
		add_action( 'comment_form_logged_in_after', array( $plugin_public, 'geodir_reviewrating_comment_rating_fields' ));
		add_action( 'comment_form_before_fields', array( $plugin_public, 'geodir_reviewrating_comment_rating_fields' ));
		add_filter('comment_reply_link', array( $plugin_public, 'geodir_reviewrating_comment_replylink'));/* Wrap Comment reply link */
		add_filter('cancel_comment_reply_link', array( $plugin_public, 'geodir_reviewrating_cancle_replylink'));/* Wrap Cancel reply link */
		add_filter('comment_save_pre', array( $plugin_public, 'geodir_reviewrating_update_comments'));/* update Comment Rating */
	   // add_filter('edit_comment', array( $plugin_public, 'geodir_reviewrating_update_comments_images'));/* update Comment Rating */
		add_action('comment_post', array( $plugin_public, 'geodir_reviewrating_save_rating'));/* Save Comment Rating */
		add_filter('geodir_review_rating_stars_on_infowindow', array( $plugin_public, 'geodir_reviewrating_advance_stars_on_infowindow'), 2, 3 ) ;
		add_filter( 'get_comments_pagenum_link', array( $plugin_public, 'geodir_reviewrating_comments_pagenum_link'), 10, 1 );
		do_action( 'gd_review_rating_setup_actions' );
	}

	/**
	 * Load the text domain.
	 */
	public function load_textdomain() {
		// Determines the current locale.
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else if ( function_exists( 'get_user_locale' ) ) {
			$locale = get_user_locale();
		} else {
			$locale = get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'geodir_reviewratings' );

		unload_textdomain( 'geodir_reviewratings' );
		load_textdomain( 'geodir_reviewratings', WP_LANG_DIR . '/geodir_reviewratings/geodir_reviewratings-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir_reviewratings', false, basename( dirname( GEODIR_REVIEWRATING_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Include the files.
	 */
	private function includes() {
		global $wp_version;

		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/includes/class-geodir-review-rating-template.php' );
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/includes/class-geodir-review-rating-like-unlike.php' );
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/includes/class-geodir-review-rating-ajax.php');
		if ( class_exists( 'GeoDir_Abstract_Privacy' ) && version_compare( $wp_version, '4.9.6', '>=' ) ) {
			require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/includes/class-geodir-review-rating-privacy.php' );
		}
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/includes/general-functions.php' );
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/admin/class-geodir-review-rating-manager-admin.php' );
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/admin/class-geodir-review-rating-styles.php');
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/admin/class-geodir-review-rating-styles-table-list.php');
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/admin/class-geodir-review-ratings.php');
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/admin/class-geodir-review-ratings-table-list.php');
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/public/class-geodir-review-rating-manager-public.php' );
		require_once( GEODIR_REVIEWRATING_PLUGINDIR_PATH . '/includes/class-geodir-review-rating-api.php');
	}

	/**
	 * Localize all javascript message strings.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory_Review_Rating_Manager
	 */
	public function geodir_reviewrating_localize_all_js_msg() {
		global $path_location_url;

		$arr_alert_msg = array(
			'geodir_reviewrating_admin_ajax_url' => geodir_reviewrating_ajax_url(),
			'geodir_reviewrating_please_enter' => __('Please enter', 'geodir_reviewratings'),
			'geodir_reviewrating_star_text' => __('Star Text', 'geodir_reviewratings'),
			'geodir_reviewrating_rating_delete_confirmation' => __('Are you sure you want to delete this?', 'geodir_reviewratings'),
			'geodir_reviewrating_please_select' => __('Please select', 'geodir_reviewratings'),
			'geodir_reviewrating_categories_text' => __('Categories.', 'geodir_reviewratings'),
			'geodir_reviewrating_select_post_type' => __('Please select Post Type.', 'geodir_reviewratings'),
			'geodir_reviewrating_enter_rating_title' => __('Please enter rating title.', 'geodir_reviewratings'),
			'geodir_reviewrating_select_multirating_style' => __('Please Select multirating style.', 'geodir_reviewratings'),
			'geodir_reviewrating_hide_images' => __('Hide Images', 'geodir_reviewratings'),
			'geodir_reviewrating_show_images' => __('Show Images', 'geodir_reviewratings'),
			'geodir_reviewrating_hide_ratings' => __('Hide Multi Ratings', 'geodir_reviewratings'),
			'geodir_reviewrating_show_ratings' => __('Show Multi Ratings', 'geodir_reviewratings'),
			'geodir_reviewrating_delete_image_confirmation' => __('Are you sure you want to delete this image?', 'geodir_reviewratings'),
			'geodir_reviewrating_please_enter_below' => __('Please enter below', 'geodir_reviewratings'),
			'geodir_reviewrating_please_enter_above' => __('Please enter above', 'geodir_reviewratings'),
			'geodir_reviewrating_numeric_validation' => __('Please enter only numeric value', 'geodir_reviewratings'),
			'geodir_reviewrating_maximum_star_rating_validation' => __('You are create maximum seven star rating', 'geodir_reviewratings'),
			'geodir_reviewrating_star_and_input_box_validation' => __('Your input box number and number of star is not same', 'geodir_reviewratings'),
			'geodir_reviewrating_star_and_score_text_validation' => __('Your input box number and number of Score text is not same', 'geodir_reviewratings'),
			'geodir_reviewrating_select_rating_off_img' => __('Please select rating off image.', 'geodir_reviewratings'),
			'geodir_reviewrating_optional_multirating' => (bool)geodir_get_option( 'rr_optional_multirating' ),
			'allow_empty_review' => (bool) apply_filters( 'geodir_allow_empty_review', false ),
			'err_empty_review' => __('Please type a review.', 'geodir_reviewratings'),
			'err_empty_reply' => __('Please type a reply.', 'geodir_reviewratings')
		);

		foreach ( $arr_alert_msg as $key => $value )
		{
			if ( !is_scalar($value) )
				continue;
			$arr_alert_msg[$key] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8');
		}

		$script = "var geodir_reviewrating_all_js_msg = " . json_encode($arr_alert_msg) . ';';
		echo '<script>';
		echo $script ;
		echo '</script>';
	}
}