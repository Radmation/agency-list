<?php
/**
 * GeoDirectory reCAPTCHA
 *
 * @package           Geodir_Recaptcha
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory reCAPTCHA
 * Plugin URI:        https://wpgeodirectory.com/downloads/gd-recaptcha/
 * Description:       Integrates Google reCAPTCHA anti-spam methods with GeoDirectory addon including login, registration, comments, add listing etc.
 * Version:           2.3.3
 * Requires at least: 4.5
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geodir-recaptcha
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65872
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GD_RECAPTCHA_VERSION' ) ) {
	define( 'GD_RECAPTCHA_VERSION', '2.3.3' );
}

if ( ! defined( 'GD_RECAPTCHA_MIN_CORE' ) ) {
	define( 'GD_RECAPTCHA_MIN_CORE', '2.3' );
}

// check user is_admin or not.
if ( is_admin() ) {
	// Check is_plugin_active function exists or not.
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	// Check geodirectory pluign is active or not.
	if ( ! is_plugin_active( 'geodirectory/geodirectory.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		function gd_recaptcha_requires_gd_plugin() {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( '%s requires to install the %sGeoDirectory%s plugin to be installed and active.', 'geodir-recaptcha' ), 'GeoDirectory Re-Captcha', '<a href="https://wpgeodirectory.com" target="_blank" title=" GeoDirectory">', '</a>' ) . '</strong></p></div>';
			echo '<div class="error notice-erroe is-dismissible"><p> ' . sprintf( __( 'Plugin', 'geodir-recaptcha' ) ) . ' <strong>' . sprintf( __( 'deactivated.', 'geodir-recaptcha' ) ) . '</strong></p></div>';
		}

		add_action( 'admin_notices', 'gd_recaptcha_requires_gd_plugin' );
		return;
	}

	// check ayecode_show_update_plugin_requirement function exists or not.
	if ( ! function_exists( 'ayecode_show_update_plugin_requirement' ) ) {
		function ayecode_show_update_plugin_requirement() {
			if ( ! defined( 'WP_EASY_UPDATES_ACTIVE' ) ) {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( 'The plugin %sWP Easy Updates%s is required to check for and update some installed plugins, please install it now.', 'gd-lists' ), '<a href="https://wpeasyupdates.com/" target="_blank" title="WP Easy Updates">', '</a>' ) . '</strong></p></div>';
			}
		}

		add_action( 'admin_notices', 'ayecode_show_update_plugin_requirement' );
	}
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/geodir_recaptcha_activate.php
 *
 * @since 2.0.0
 */
function activate_gd_recaptcha() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/geodir_recaptcha_activate.php';
	GD_Recaptcha_Activate::activate();
}

register_activation_hook( __FILE__, 'activate_gd_recaptcha' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/geodir_recaptcha_deactivate.php
 *
 * @since 2.0.0
 */
function deactivate_gd_recaptcha() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/geodir_recaptcha_deactivate.php';
	GD_Recaptcha_Deactivate::deactivate();
}

register_deactivation_hook( __FILE__, 'deactivate_gd_recaptcha' );

/**
 * Include GD Recaptcha main class file.
 *
 * @since 2.0.0
 */
include_once( dirname( __FILE__ ) . '/class_geodir_recaptcha.php' );

/**
 * Loads a single instance of GD Recaptcha.
 *
 * @since 2.0.0
 *
 * @see GD_Recaptcha::get_instance()
 *
 * @return object GD_Recaptcha Returns an instance of the class.
 */
function gd_recaptcha() {
		// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Re-Captcha', GD_RECAPTCHA_MIN_CORE ) ) {
		return '';
	}

	return GD_Recaptcha::get_instance();
}

add_action( 'plugins_loaded', 'gd_recaptcha', 10 );