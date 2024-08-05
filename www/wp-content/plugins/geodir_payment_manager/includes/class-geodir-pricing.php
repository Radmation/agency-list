<?php
/**
 * Pricing Manager plugin main class.
 *
 * @package    GeoDir_Pricing_Manager
 * @since      2.5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Pricing class.
 */
final class GeoDir_Pricing {

	/**
	 * The single instance of the class.
	 *
	 * @since 2.5
	 */
	private static $instance = null;

	/**
	 * Query instance.
	 *
	 * @var GeoDir_Pricing_Query
	 */
	public $query = null;

	/**
	 * Cart instance.
	 *
	 * @var GeoDir_Pricing_Cart
	 */
	public $cart;

	public $cart_class;

	/**
	 * Main Pricing Manager Instance.
	 *
	 * Ensures only one instance of Pricing Manager is loaded or can be loaded.
	 *
	 * @since 2.5
	 * @static
	 * @return Pricing Manager - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Pricing ) ) {
			self::$instance = new GeoDir_Pricing;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			if ( ! class_exists( 'GeoDirectory' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'geodirectory_notice' ) );

				return self::$instance;
			}

			if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_pricing_manager_loaded' );
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
			$plugin_path = dirname( GEODIR_PRICING_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_PRICING_PLUGIN_FILE );
		}

		$this->define( 'GEODIR_PRICING_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_PRICING_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_PRICING_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_PRICING_PLUGIN_BASENAME', plugin_basename( GEODIR_PRICING_PLUGIN_FILE ) );

		// Define database tables
		$this->define( 'GEODIR_PRICING_PACKAGES_TABLE', $plugin_prefix . 'price' );
		$this->define( 'GEODIR_PRICING_PACKAGE_META_TABLE', $plugin_prefix . 'pricemeta' );
		$this->define( 'GEODIR_PRICING_POST_PACKAGES_TABLE', $plugin_prefix . 'post_packages' );
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 2.5.0
	 * @return void
	 */
	private function includes() {
		global $wp_version;

		/**
		 * Class autoloader.
		 */
		include_once( GEODIR_PRICING_PLUGIN_DIR . 'includes/class-geodir-pricing-autoloader.php' );

		GeoDir_Pricing_Post::init();
		GeoDir_Pricing_Package::init();
		GeoDir_Pricing_Post_Limit::init();
		GeoDir_Pricing_Post_Package::init();
		GeoDir_Pricing_AJAX::init();
		GeoDir_Pricing_Classifieds::init();
		GeoDir_Pricing_Email::init();

		require_once( GEODIR_PRICING_PLUGIN_DIR . 'includes/deprecated-functions.php' );
		require_once( GEODIR_PRICING_PLUGIN_DIR . 'includes/core-functions.php' );
		require_once( GEODIR_PRICING_PLUGIN_DIR . 'includes/package-functions.php' );
		require_once( GEODIR_PRICING_PLUGIN_DIR . 'includes/cart-functions.php' );
		require_once( GEODIR_PRICING_PLUGIN_DIR . 'includes/template-functions.php' );

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			new GeoDir_Pricing_Admin();

			require_once( GEODIR_PRICING_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_Pricing_Admin_Install::init();

			require_once( GEODIR_PRICING_PLUGIN_DIR . 'upgrade.php' );
		}

		$this->query = new GeoDir_Pricing_Query();

		// If current WP Version >= 4.9.6.
		if ( class_exists( 'GeoDir_Abstract_Privacy' ) && version_compare( $wp_version, '4.9.6', '>=' ) ) {
			new GeoDir_Pricing_Privacy();
		}
	}

	/**
	 * Hook into actions and filters.
	 * @since  2.5.0
	 */
	private function init_hooks() {
		if ( $this->is_request( 'frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 10 );
			add_filter( 'body_class', 'geodir_pricing_body_class', 10, 1 );
			add_filter( 'template_redirect', 'geodir_pricing_set_post_expired' );
			add_filter( 'posts_results', 'geodir_pricing_check_post_expired', 10, 2 );
		}

		add_action( 'init', array( $this, 'init' ), 0 );
		add_filter( 'geodir_get_widgets', 'geodir_pricing_register_widgets', 10, 1 );
		add_filter( 'post_class', 'geodir_pricing_post_class', 10, 3 );

		add_action( 'geodir_pricing_schedule_event_expire_check', 'geodir_pricing_cron_expire_check' );
		add_action( 'geodir_pricing_schedule_event_pre_expiry_reminders', 'geodir_pricing_cron_pre_expiry_reminders' );
		add_filter( 'geodir_pricing_class_cart',array( $this, 'extend_cart' ) );
		add_filter( 'geodir_locate_template', 'geodir_pricing_locate_template', 10, 3 );
		add_filter( 'geodir_cfi_textarea_attributes', 'geodir_pricing_cfi_textarea_attributes', 10, 2 );
		add_filter( 'geodir_cfi_aui_textarea_attributes', 'geodir_pricing_cfi_aui_textarea_attributes', 10, 2 );
		add_filter( 'tiny_mce_before_init', 'geodir_pricing_tiny_mce_before_init', 10, 2 );
		add_filter( 'teeny_mce_before_init', 'geodir_pricing_tiny_mce_before_init', 10, 2 );
		add_filter( 'after_wp_tiny_mce', 'geodir_pricing_after_wp_tiny_mce', 10, 1 );
		add_filter( 'wp_super_duper_arguments', 'geodir_pricing_super_duper_arguments', 10, 3 );

		add_action( 'admin_notices', array( $this, 'check_for_cart' ), 10 );
	}

	/**
	 * Check that a cart is selected and active or show an admin error if not.
	 *
	 * @static
	 * @since 2.5.0
	 * @return void
	 */
	public static function check_for_cart() {
		if ( apply_filters( 'geodir_pricing_hide_no_cart_warning', false ) ) {
			return;
		}

		$cart = geodir_get_option( 'pm_cart' );

		if ( $cart == 'invoicing' && defined( 'WPINV_VERSION' ) ) {
			// GetPaid active
		} else if ( $cart == 'woocommerce' && class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			// WooCommerce active
		} else {
			// No cart
			$message = sprintf( __( '<b>Important:</b> Pricing Manager is in <b>No Cart</b> mode, try %sGetPaid%s (recommended) or %sWooCommerce%s to receive real payments. Set cart from %shere%s.', 'geodir_pricing' ),
				'<a href="https://wordpress.org/plugins/invoicing/" target="_blank">',
				'</a>',
				'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">',
				'</a>',
				'<a href="'.admin_url( 'admin.php?page=gd-settings&tab=pricing#pm_cart' ).'">',
				'</a>'
			);

			echo '<div class="error"><p>' . $message . '</p></div>';
		}
	}

	/**
	 * Initialise plugin when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'geodir_pricing_manager_before_init' );

		// Cart
		$cart_class_name = apply_filters( 'geodir_pricing_class_cart', 'GeoDir_Pricing_Cart' );
		$this->cart_class = $cart_class_name;
		$this->cart = new $cart_class_name;

		// Init action.
		do_action( 'geodir_pricing_manager_init' );
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 2.5.0
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

		$locale = apply_filters( 'plugin_locale', $locale, 'geodir_pricing' );

		unload_textdomain( 'geodir_pricing' );
		load_textdomain( 'geodir_pricing', WP_LANG_DIR . '/geodir_pricing/geodir_pricing-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir_pricing', false, basename( dirname( GEODIR_PRICING_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Check plugin compatibility and show warning.
	 *
	 * @static
	 * @access private
	 * @since 2.5.0
	 * @return void
	 */
	public static function geodirectory_notice() {
		echo '<div class="error"><p>' . __( 'GeoDirectory plugin is required for the GeoDirectory Pricing Manager plugin to work properly.', 'geodir_pricing' ) . '</p></div>';
	}

	/**
	 * Show a warning to sites running PHP < 5.3
	 *
	 * @static
	 * @access private
	 * @since 2.5.0
	 * @return void
	 */
	public static function php_version_notice() {
		echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by GeoDirectory Pricing Manager. Please contact your host and request that your version be upgraded to 5.3 or later.', 'geodir_pricing' ) . '</p></div>';
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
			wp_register_style( 'geodir-pricing', GEODIR_PRICING_PLUGIN_URL . '/assets/css/style.css', array(), GEODIR_PRICING_VERSION );

			wp_enqueue_style( 'geodir-pricing' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function add_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts
		wp_register_script( 'geodir-pricing', GEODIR_PRICING_PLUGIN_URL . '/assets/js/common' . $suffix . '.js', array( 'jquery', 'geodir' ), GEODIR_PRICING_VERSION );
		wp_register_script( 'geodir-pricing-front', GEODIR_PRICING_PLUGIN_URL . '/assets/js/front' . $suffix . '.js', array( 'jquery', 'geodir', 'geodir-pricing' ), GEODIR_PRICING_VERSION );

		wp_enqueue_script( 'geodir-pricing' );
		wp_localize_script( 'geodir-pricing', 'geodir_pricing_params', geodir_pricing_params() );
	}

	public function extend_cart( $class ) {
		$cart = geodir_get_option( 'pm_cart' );

		if ( $cart == 'invoicing' && defined( 'WPINV_VERSION' ) && version_compare( WPINV_VERSION, '1.1.0', '>=' ) ) {
			$class = 'GeoDir_Pricing_Cart_GetPaid';
		} else if ( $cart == 'invoicing' && defined( 'WPINV_VERSION' ) && version_compare( WPINV_VERSION, '1.0.0', '>=' ) ) {
			$class = 'GeoDir_Pricing_Cart_Invoicing';
		} else if ( $cart == 'woocommerce' && class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			$class = 'GeoDir_Pricing_Cart_WooCommerce';
		}

		return $class;
	}
}
