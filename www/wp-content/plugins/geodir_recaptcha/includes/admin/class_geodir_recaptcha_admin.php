<?php
// check GD_Recaptcha_Admin class exists or not.
if ( !class_exists( 'GD_Recaptcha_Admin' ) ) {
	/**
	* The admin-specific functionality of the plugin.
	*
	* @since 2.0.0
	*
	* @package    GD_Recaptcha
	* @subpackage GD_Recaptcha/admin
	*
	* Class GD_Recaptcha_Admin
	*/
	class GD_Recaptcha_Admin {

		public $id;

		public $title;

		public $page;

		public $tab;

		public $section;

		/**
		* Constructor.
		*
		* @since 2.0.0
		*
		* GD_Recaptcha_Admin constructor.
		*/
		 public function __construct() {
			$this->id  = 'gd-recaptcha';
			$this->title = __( 'reCAPTCHA', 'geodir-recaptcha' );
			$this->page = !empty( $_REQUEST['page'] ) ? sanitize_title( $_REQUEST['page'] ) : '';
			$this->tab = !empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : '';
			$this->section = !empty( $_REQUEST['section'] ) ? sanitize_title( $_REQUEST['section'] ) : '';

			add_action( 'admin_init', array($this, 'recaptcha_activation_redirect'));

			 // add dev settings
			 add_filter('geodir_developer_options', array( $this, 'developer_options' ) );
			 add_filter( 'geodir_load_db_language', array( $this, 'load_db_language_strings' ), 50, 1 );
			 add_filter( 'geodir_uninstall_options', array( $this, 'uninstall_settings' ), 50, 1 );
		 }

		public function developer_options( $options = array() ) {
			$options[] = array(
					'title' => __( 'ReCaptcha', 'geodir-recaptcha' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'rc_developer_options',
			);

			$options[] = array(
					'name'     => __( 'Force recaptch fail', 'geodir-recaptcha' ),
					'desc'     => __( 'This will force recaptch to throw an error response to be able to test failure conditions (Never use on a production site). ', 'geodir-recaptcha' ),
					'id'       => 'rc_force_fail',
					'type'     => 'checkbox',
					'default'  => '0',
			);

			$options[] = array(
				'type' => 'sectionend',
				'id' => 'rc_developer_options'
			);

			return $options;
		}

		/**
		* Plugin activation redirection.
		*
		* When plugin activate then redirect to direct GD Recaptcha tab under the GD settings submenu.
		* GD Recaptcha settings tab.
		*
		* @since 2.0.0
		*
		*/
		 public function recaptcha_activation_redirect() {
			// if not transient set then return.
			if ( !get_transient( 'gd_recaptcha_redirect' ) ) {
				return false;
			}

			// Delete the redirect transient
			delete_transient( 'gd_recaptcha_redirect' );

			// Redirect the Social importer tab.
			wp_safe_redirect( admin_url( 'admin.php?page=gd-settings&tab='.$this->id ) );
			exit;
		 }

		 /**
		 * Set captcha title for translation.
		 *
		 * @since 2.2.1
		 *
		 * @param  array $strings Array of text strings.
		 * @return array
		 */
		public function load_db_language_strings( $strings = array() ) {
			if ( ! is_array( $strings ) ) {
				$strings = array();
			}

			if ( $captcha_title = geodir_get_option( 'rc_title' ) ) {
				$strings[] = stripslashes_deep( $captcha_title );
			}

			return $strings;
		}

		/**
		 * Add the plugin to uninstall settings.
		 *
		 * @since 2.3.2
		 *
		 * @return array $settings the settings array.
		 * @return array The modified settings.
		 */
		public function uninstall_settings( $settings ) {
			array_pop( $settings );

			$settings[] = array(
				'name' => __( 'reCAPTCHA', 'geodir-recaptcha' ),
				'desc' => __( 'Check this box if you would like to completely remove all of its data when reCAPTCHA is deleted.', 'geodir-recaptcha' ),
				'id' => 'uninstall_geodir_recaptcha',
				'type' => 'checkbox',
			);

			$settings[] = array( 
				'type' => 'sectionend',
				'id' => 'uninstall_options'
			);

			return $settings;
		}
	}
}