<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Check GD_Duplicate_Alert class exists or not.
 */
if( ! class_exists( 'GD_Duplicate_Alert' ) ) {

	/**
	 * Main GD duplicate alert class.
	 *
	 * @class GD_Duplicate_Alert
	 *
	 * @since 1.2.1
	 */
	final class GD_Duplicate_Alert {

		/**
		 * GD Duplicate alert instance.
		 *
		 * @access private
		 * @since 1.2.0
		 *
		 * @var GD_Duplicate_Alert instance.
		 */
		private static $instance = null;

		/**
		 * GD Duplicate alert version.
		 *
		 * @var string $verion.
		 */
		public $version = GD_DUPLICATE_ALERT_VERSION;

		/**
		 * GD Duplicate alert Admin Object.
		 *
		 * @since  1.2.1
		 * @access public
		 *
		 * @var GD_Duplicate_Alert object.
		 */
		public $plugin_admin;

		/**
		 * GD Duplicate alert Public Object.
		 *
		 * @since  1.2.1
		 * @access public
		 *
		 * @var GD_Duplicate_Alert object.
		 */
		public $plugin_public;

		/**
		 * Get the instance and store the class inside it. This plugin utilises.
		 *
		 * @since 1.2.1
		 *
		 * @return object GD_Duplicate_Alert
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GD_Duplicate_Alert ) ) {
				self::$instance = new GD_Duplicate_Alert();
				self::$instance->setup_constants();
				self::$instance->load_text_domain();
				self::$instance->hooks();
				self::$instance->includes();
			}

			return self::$instance;
		}

		/**
		 * Set plugin constants.
		 *
		 * @since 1.2.1
		 * @access  private
		 */
		private function setup_constants() {
			// Define GD duplicate alert plugin textdomain.
			if( !defined( 'GD_DUPLICATE_ALERT_TEXTDOMAIN')) {
				define( 'GD_DUPLICATE_ALERT_TEXTDOMAIN', 'geodir-duplicate-alert' );
			}

			// Define GD duplicate alert plugin Directory.
			if( !defined( 'GD_DUPLICATE_ALERT_PLUGIN_DIR')) {
				define( 'GD_DUPLICATE_ALERT_PLUGIN_DIR', dirname(GD_DUPLICATE_ALERT_PLUGIN_FILE ) );
			}

			// Define GD duplicate alert plugin URL.
			if( !defined( 'GD_DUPLICATE_ALERT_PLUGIN_URL')) {
				define( 'GD_DUPLICATE_ALERT_PLUGIN_URL', plugin_dir_url(GD_DUPLICATE_ALERT_PLUGIN_FILE ) );
			}

			// Define GD duplicate alert plugin Directory path.
			if( !defined( 'GD_DUPLICATE_ALERT_PLUGIN_DIR_PATH')) {
				define( 'GD_DUPLICATE_ALERT_PLUGIN_DIR_PATH', plugin_dir_path(GD_DUPLICATE_ALERT_PLUGIN_FILE ) );
			}

			// Define GD duplicate alert plugin Basepath.
			if( !defined( 'GD_DUPLICATE_ALERT_PLUGIN_BASENAME')) {
				define( 'GD_DUPLICATE_ALERT_PLUGIN_BASENAME', plugin_basename(GD_DUPLICATE_ALERT_PLUGIN_FILE ) );
			}
		}

		/**
		 * Define Hooks.
		 *
		 * @since 1.2.1
		 */
		public function hooks() {
		}

		/**
		 * Includes.
		 *
		 * @since 1.2.1
		 */
		private function includes() {
			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */

			require_once( GD_DUPLICATE_ALERT_PLUGIN_DIR . '/includes/class-ajax-duplicate-alert-defaults.php' );
			require_once( GD_DUPLICATE_ALERT_PLUGIN_DIR . '/includes/admin/class-custom-ajax-duplicate-alert-admin.php' );
			require_once( GD_DUPLICATE_ALERT_PLUGIN_DIR . '/includes/admin/settings/class-ajax-duplicate-alert-settings.php' );

			/**
			 * The class responsible for defining all actions that occur in the public area.
			 */
			require_once( GD_DUPLICATE_ALERT_PLUGIN_DIR . '/includes/public/class-custom-ajax-duplicate-alert-public.php' );

			self::$instance->plugin_admin  = new GD_Duplicate_Alert_Admin();
			self::$instance->plugin_public = new GD_Duplicate_Alert_Public();
		}

		/**
		 * Load GD duplicate alert language file.
		 *
		 * @since 1.2.1
		 */
		public function load_text_domain() {
			// Determines the current locale.
			if ( function_exists( 'determine_locale' ) ) {
				$locale = determine_locale();
			} else if ( function_exists( 'get_user_locale' ) ) {
				$locale = get_user_locale();
			} else {
				$locale = get_locale();
			}

			$locale = apply_filters( 'plugin_locale', $locale, 'geodir-duplicate-alert' );

			unload_textdomain( 'geodir-duplicate-alert' );
			load_textdomain( 'geodir-duplicate-alert', WP_LANG_DIR . '/geodir-duplicate-alert/geodir-duplicate-alert-' . $locale . '.mo' );
			load_plugin_textdomain( 'geodir-duplicate-alert', false, basename( dirname( GD_DUPLICATE_ALERT_PLUGIN_FILE ) ) . '/languages/' );
		}
	}

}