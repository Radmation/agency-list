<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    GeoDir_Custom_Posts
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    GeoDir_CP
 * @author     AyeCode Ltd
 */
final class GeoDir_CP {

	/**
	 * GeoDirectory Custom Post Types instance.
	 *
	 * @access private
	 * @since  2.0.0
	 */
	private static $instance = null;

	/**
	 * The settings instance variable
	 *
	 * @access public
	 * @since  2.0.0
	 * @var    GeoDir_CP_Settings
	 */
	public $settings;

	/**
	 * Query instance.
	 *
	 * @var GeoDir_CP_Query
	 */
	public $query = null;

	/**
	 * Main GeoDir_CP Instance.
	 *
	 * Ensures only one instance of GeoDirectory Custom Post Types is loaded or can be loaded.
	 *
	 * @since 2.0.0
	 * @static
	 * @see GeoDir()
	 * @return GeoDir_CP - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_CP ) ) {
			self::$instance = new GeoDir_CP;
			self::$instance->setup_constants();
			
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_custom_posts_loaded' );
		}
		
		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	private function setup_constants() {
		global $plugin_prefix;

		if ( $this->is_request( 'test' ) ) {
			$plugin_path = dirname( GEODIR_CP_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_CP_PLUGIN_FILE );
		}
		
		$this->define( 'GEODIR_CP_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_CP_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_CP_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_CP_PLUGIN_BASENAME', plugin_basename( GEODIR_CP_PLUGIN_FILE ) );

		// Database tables
		$this->define( 'GEODIR_CP_LINK_POSTS', $plugin_prefix . 'cp_link_posts' );
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 2.0.0
	 * @return void
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

		/**
		 * Filter the plugin locale.
		 *
		 * @since   1.0.0
		 * @package GeoDir_Custom_Posts
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'geodir_custom_posts' );

		unload_textdomain( 'geodir_custom_posts' );
		load_textdomain( 'geodir_custom_posts', WP_LANG_DIR . '/geodir_custom_posts/geodir_custom_posts-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir_custom_posts', false, basename( dirname( GEODIR_CP_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Show a warning to sites running PHP < 5.3
	 *
	 * @static
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	public static function php_version_notice() {
		echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by GeoDirectory Custom Post Types. Please contact your host and request that your version be upgraded to 5.3 or later.', 'geodir_custom_posts' ) . '</p></div>';
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	private function includes() {
		global $pagenow, $geodir_options, $geodirectory;

		/**
		 * Class autoloader.
		 */
		include_once( GEODIR_CP_PLUGIN_DIR . 'includes/class-geodir-cp-autoloader.php' );

		GeoDir_CP_Post_Types::init();
		GeoDir_CP_Link_Posts::init();
		GeoDir_CP_AJAX::init();
		GeoDir_CP_Widgets::init();
		GeoDir_CP_Compatibility::init();

		require_once( GEODIR_CP_PLUGIN_DIR . 'includes/deprecated-functions.php' );
		require_once( GEODIR_CP_PLUGIN_DIR . 'includes/core-functions.php' );
		require_once( GEODIR_CP_PLUGIN_DIR . 'includes/link-cpts-functions.php' );

		// API
		GeoDir_CP_API::init();

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			new GeoDir_CP_Admin();

			require_once( GEODIR_CP_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_CP_Admin_Install::init();
			GeoDir_CP_Import_Export::init();


			require_once( GEODIR_CP_PLUGIN_DIR . 'upgrade.php' );
		}

		$this->query = new GeoDir_CP_Query();
	}

	/**
	 * Hook into actions and filters.
	 * @since  2.3
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );

		if ( $this->is_request( 'frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 10 );
		}

		add_filter( 'geodir_get_widgets', 'goedir_cp_register_widgets', 10, 1 );
		add_filter( 'geodir_near_input_extra', 'geodir_cp_hide_near_search_input', 10, 2 );
		add_filter( 'geodir_search_near_class', 'geodir_cp_class_near_search_input', 10 );
		add_filter( 'geodir_listing_attrs', 'geodir_cp_listing_cusotm_attrs', 10, 2 );
		add_filter( 'geodir_current_location_terms', 'geodir_cp_current_location_terms', 9999, 3 );
		add_filter( 'geodir_loc_term_count', 'geodir_cp_loc_term_count', 10, 2 );
		add_action( 'wp_super_duper_widget_init', 'geodir_cp_super_duper_widget_init', 9, 2 );
		add_filter( 'geodir_search_filter_searched_params', array( 'GeoDir_CP_Link_Posts', 'display_searched_params' ), 11, 2 );
	}

	/**
	 * Init GeoDirectory Custom Post Types when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'geodir_cp_before_init' );

		// Init action.
		do_action( 'geodir_cp_init' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Request type.
	 *
	 * @param  string $type admin, frontend, ajax, cron, test or CLI.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
				break;
			case 'ajax' :
				return wp_doing_ajax();
				break;
			case 'cli' :
				return ( defined( 'WP_CLI' ) && WP_CLI );
				break;
			case 'cron' :
				return wp_doing_cron();
				break;
			case 'frontend' :
				return ( ! is_admin() || wp_doing_ajax() ) && ! wp_doing_cron();
				break;
			case 'test' :
				return defined( 'GD_TESTING_MODE' );
				break;
		}
		
		return null;
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return GEODIR_CP_PLUGIN_URL;
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( GEODIR_CP_PLUGIN_DIR );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Enqueue styles.
	 */
	public function add_styles() {
		$design_style = geodir_design_style();

		// Register stypes
		if ( ! $design_style ) {
			wp_register_style( 'geodir-cp', GEODIR_CP_PLUGIN_URL . '/assets/css/style.css', array(), GEODIR_CP_VERSION );

			wp_enqueue_style( 'geodir-cp' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function add_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts
		wp_register_script( 'geodir-cp', GEODIR_CP_PLUGIN_URL . '/assets/js/script' . $suffix . '.js', array( 'jquery', 'geodir' ), GEODIR_CP_VERSION );

		wp_enqueue_script( 'geodir-cp' );
		wp_localize_script( 'geodir-cp', 'geodir_cp_params', geodir_cp_params() );
	}
}
