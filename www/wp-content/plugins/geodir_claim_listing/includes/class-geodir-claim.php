<?php
/**
 * Claim Listings plugin main class.
 *
 * @package    Geodir_Claim_Listing
 * @since      2.0.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Claim class.
 */
final class GeoDir_Claim {

	/**
	 * The single instance of the class.
	 *
	 * @since 2.0.0
	 */
	private static $instance = null;

	/**
	 * Query instance.
	 *
	 * @var GeoDir_Claim_Query
	 */
	public $query = null;

	/**
	 * Claim Listings Main Instance.
	 *
	 * Ensures only one instance of Claim Listings is loaded or can be loaded.
	 *
	 * @since 2.0.0
	 * @static
	 * @return Claim Listings - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Claim ) ) {
			self::$instance = new GeoDir_Claim;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			if ( ! class_exists( 'GeoDirectory' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'geodirectory_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_claim_listing_loaded' );
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
			$plugin_path = dirname( GEODIR_CLAIM_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_CLAIM_PLUGIN_FILE );
		}

		$this->define( 'GEODIR_CLAIM_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_CLAIM_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_CLAIM_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_CLAIM_PLUGIN_BASENAME', plugin_basename( GEODIR_CLAIM_PLUGIN_FILE ) );

		// Define database tables
		$this->define( 'GEODIR_CLAIM_TABLE', $plugin_prefix . 'claim' );
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	private function includes() {
		global $wp_version;

		/**
		 * Class autoloader.
		 */
		include_once( GEODIR_CLAIM_PLUGIN_DIR . 'includes/class-geodir-claim-autoloader.php' );

		GeoDir_Claim_AJAX::init();
		GeoDir_Claim_Form::init();
		GeoDir_Claim_Email::init();
		GeoDir_Claim_Post::init();

		// If Pricing Manager is installed then fire the payment class
		if ( defined( 'GEODIR_PRICING_VERSION' ) ) {
			GeoDir_Claim_Payment::init();

			add_filter( 'ninja_forms_register_fields', function( $fields ) {
				$fields['geodir_packages'] = new GeoDir_Claim_Ninja_Forms_Packages_field;

				return $fields;
			} );
		}

		require_once( GEODIR_CLAIM_PLUGIN_DIR . 'includes/core-functions.php' );
		require_once( GEODIR_CLAIM_PLUGIN_DIR . 'includes/deprecated-functions.php' );
		require_once( GEODIR_CLAIM_PLUGIN_DIR . 'includes/template-functions.php' );		

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			new GeoDir_Claim_Admin();
			new GeoDir_Claim_Admin_Claims_Dashboard();

			include_once( GEODIR_CLAIM_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_Claim_Admin_Install::init();

			require_once( GEODIR_CLAIM_PLUGIN_DIR . 'update.php' );
		}

		$this->query = new GeoDir_Claim_Query();

		// If current WP Version >= 4.9.6.
		if ( class_exists( 'GeoDir_Abstract_Privacy' ) && version_compare( $wp_version, '4.9.6', '>=' ) ) {
			new GeoDir_Claim_Privacy();
		}
	}

	/**
	 * Hook into actions and filters.
	 * @since  2.0.0
	 */
	private function init_hooks() {
		if ( $this->is_request( 'frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 10 );
		}

		add_action( 'init', array( $this, 'init' ), 0 );
		add_filter( 'geodir_get_widgets', 'geodir_claim_register_widgets', 10, 1 );
		add_filter( 'wp', 'geodir_claim_check_verification', 10 );
		add_filter( 'geodir_locate_template', 'geodir_claim_locate_template', 30, 3 );
		add_filter( 'widget_display_callback', 'geodir_claim_widget_display_callback', 30, 3 );
		add_action( 'geodir_claim_post_form_hidden_fields', 'geodir_claim_post_form_hidden_fields', 10, 1 );
		//add_action( 'geodir_claim_schedule_event_nudge_emails', 'geodir_claim_event_nudge_emails', 10, 1 );
	}

	/**
	 * Initialise plugin when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'geodir_claim_listing_before_init' );

		// Init action.
		do_action( 'geodir_claim_listing_init' );
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 2.0.0
	 * @return void
	 */
	public function load_textdomain() {
		global $wp_version;
		
		$locale = $wp_version >= 4.7 ? get_user_locale() : get_locale();
		
		/**
		 * Filter the plugin locale.
		 *
		 * @since   1.0.0
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'geodir-claim' );

		unload_textdomain( 'geodir-claim' );
		load_textdomain( 'geodir-claim', WP_LANG_DIR . '/' . 'geodir-claim' . '/' . 'geodir-claim' . '-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir-claim', FALSE, basename( dirname( GEODIR_CLAIM_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Check plugin compatibility and show warning.
	 *
	 * @static
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	public static function geodirectory_notice() {
		echo '<div class="error"><p>' . __( 'GeoDirectory plugin is required for the Claim Listings plugin to work properly.', 'geodir-claim' ) . '</p></div>';
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
	 * Enqueue styles.
	 */
	public function add_styles() {
		// Register styles
		if ( ! geodir_design_style() ) {
			wp_register_style( 'geodir-claim', GEODIR_CLAIM_PLUGIN_URL . '/assets/css/style.css', array(), GEODIR_CLAIM_VERSION );

			wp_enqueue_style( 'geodir-claim' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function add_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts
		wp_register_script( 'geodir-claim-front', GEODIR_CLAIM_PLUGIN_URL . '/assets/js/script' . $suffix . '.js', array( 'jquery', 'geodir' ), GEODIR_CLAIM_VERSION );

		wp_enqueue_script( 'geodir-claim-front' );
		wp_localize_script( 'geodir-claim-front', 'geodir_claim_params', geodir_claim_params() );
	}
}