<?php
/**
* Check GD_Recaptcha class exists or not.
*/
if ( ! class_exists( 'GD_Recaptcha' ) ) {
	/**
	* Main GD Recaptcha class.
	*
	* @class GD_Recaptcha
	*
	* @since 2.0.0
	*/
	final class GD_Recaptcha {
		/**
		* GD Recaptcha instance.
		*
		* @access private
		* @since  2.0.0
		*
		* @var GD_Recaptcha instance.
		*/
		private static $instance = null;

		/**
		* GD Recaptcha version.
		*
		* @since  2.0.0
		*
		* @access public
		*
		* @var string $version .
		*/
		public $version = GD_RECAPTCHA_VERSION;

		/**
		* GD Recaptcha Admin Object.
		*
		* @since  2.0.0
		*
		* @access public
		*
		* @var GD_Recaptcha object.
		*/
		public $plugin_admin;

		/**
		* GD Recaptcha Public Object.
		*
		* @since  2.0.0
		*
		* @access public
		*
		* @var GD_Recaptcha object.
		*/
		public $plugin_public;

		/**
		* Get the instance and store the class inside it. This plugin utilises.
		*
		* @since 2.0.0
		*
		* @return object GD_Recaptcha
		*/
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GD_Recaptcha ) ) {
				self::$instance = new GD_Recaptcha();
				self::$instance->setup_constants();
				self::$instance->load_textdomain();
				self::$instance->includes();
				self::$instance->init_hooks();
			}

			return self::$instance;
		}

		/**
		* Set plugin constants.
		*
		* @since   2.0.0
		*
		* @access  public
		*/
		public function setup_constants() {
			if ( ! defined( 'GD_RECAPTCHA_TEXTDOMAIN' ) ) {
				define( 'GD_RECAPTCHA_TEXTDOMAIN', 'geodir-recaptcha' );
			}

			if ( ! defined( 'GD_RECAPTCHA_VERSION' ) ) {
				define( 'GD_RECAPTCHA_VERSION', $this->version );
			}

			if ( ! defined( 'GD_RECAPTCHA_PLUGIN_FILE' ) ) {
				define( 'GD_RECAPTCHA_PLUGIN_FILE', __FILE__ );
			}

			if ( ! defined( 'GD_RECAPTCHA_PLUGIN_DIR' ) ) {
				define( 'GD_RECAPTCHA_PLUGIN_DIR', dirname( GD_RECAPTCHA_PLUGIN_FILE ) );
			}

			if ( ! defined( 'GD_RECAPTCHA_PLUGIN_URL' ) ) {
				define( 'GD_RECAPTCHA_PLUGIN_URL', plugin_dir_url( GD_RECAPTCHA_PLUGIN_FILE ) );
			}

			if ( ! defined( 'GD_RECAPTCHA_PLUGIN_DIR_PATH' ) ) {
				define( 'GD_RECAPTCHA_PLUGIN_DIR_PATH', plugin_dir_path( GD_RECAPTCHA_PLUGIN_FILE ) );
			}

			if ( ! defined( 'GD_RECAPTCHA_PLUGIN_BASENAME' ) ) {
				define( 'GD_RECAPTCHA_PLUGIN_BASENAME', plugin_basename( GD_RECAPTCHA_PLUGIN_FILE ) );
			}
		}

		/**
		* Load GD Recaptcha language file.
		*
		* @since 2.0.0
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

			$locale = apply_filters( 'plugin_locale', $locale, 'geodir-recaptcha' );

			unload_textdomain( 'geodir-recaptcha' );
			load_textdomain( 'geodir-recaptcha', WP_LANG_DIR . '/geodir-recaptcha/geodir-recaptcha-' . $locale . '.mo' );
			load_plugin_textdomain( 'geodir-recaptcha', false, basename( dirname( GD_RECAPTCHA_PLUGIN_FILE ) ) . '/languages/' );
		}

		/**
		* Includes.
		*
		* @since 2.0.0
		*/
		public function includes() {
			/**
			 * The class responsible for defining all GD recaptcha general functions.
			 */
			if ( !class_exists( 'ReCaptcha' ) ) {
				require_once( GD_RECAPTCHA_PLUGIN_DIR . '/lib/recaptchalib.php' );
			}

			/**
			* The class responsible for defining all GD recaptcha general functions.
			*/
			require_once(GD_RECAPTCHA_PLUGIN_DIR . '/includes/class_geodir_recaptcha_general.php');

			/**
			* The class responsible for defining all GD recaptcha settings functions.
			*/
			require_once(GD_RECAPTCHA_PLUGIN_DIR . '/includes/admin/settings/class_geodir_recaptcha_admin_settings.php');

			/**
			* The class responsible for defining all actions that occur in the Admin area.
			*/
			require_once(GD_RECAPTCHA_PLUGIN_DIR . '/includes/admin/class_geodir_recaptcha_admin.php');

			self::$instance->plugin_admin = new GD_Recaptcha_Admin();

			/**
			* The class responsible for defining all actions that occur in the Public area.
			*/
			require_once(GD_RECAPTCHA_PLUGIN_DIR . '/includes/public/class_geodir_recaptcha_public.php');

			self::$instance->plugin_public = new GD_Recaptcha_Public();
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since  2.0.0
		 */
		private function init_hooks() {
		}
	}
}