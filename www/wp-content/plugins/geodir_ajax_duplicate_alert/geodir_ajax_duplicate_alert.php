<?php
/**
 * GeoDirectory Ajax Duplicate Alert
 *
 * @package           Geodir_Ajax_Duplicate_Alert
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Ajax Duplicate Alert
 * Plugin URI:        https://wpgeodirectory.com/downloads/ajax-duplicate-alert/
 * Description:       Allows to keep your database clean and free from duplicate listing entries.
 * Version:           2.3.1
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geodir-duplicate-alert
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65088
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GD_DUPLICATE_ALERT_VERSION' ) ) {
    define( 'GD_DUPLICATE_ALERT_VERSION', '2.3.1' );
}
if ( ! defined( 'GEODIR_DUPLICATE_ALERT_MIN_CORE' ) ) {
    define( 'GEODIR_DUPLICATE_ALERT_MIN_CORE', '2.3' );
}


if( is_admin() ) {

    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    if ( !is_plugin_active( 'geodirectory/geodirectory.php' ) ) {

        deactivate_plugins( plugin_basename( __FILE__ ) );

        function gd_duplicate_alert_requires_gd_plugin() {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( '%s requires to install the %sGeoDirectory%s plugin to be installed and active.', 'geodir-duplicate-alert' ), 'GeoDirectory Ajax Duplicate Alert', '<a href="https://wpgeodirectory.com" target="_blank" title=" GeoDirectory">', '</a>' ) . '</strong></p></div>';
        }

        add_action( 'admin_notices', 'gd_duplicate_alert_requires_gd_plugin' );
        return;

    }

    if (!function_exists('ayecode_show_update_plugin_requirement')) {

        function ayecode_show_update_plugin_requirement() {

            if ( !defined('WP_EASY_UPDATES_ACTIVE') ) {

                echo '<div class="notice notice-warning is-dismissible"><p><strong>'.sprintf( __( 'The plugin %sWP Easy Updates%s is required to check for and update some installed plugins, please install it now.', 'geodirectory' ), '<a href="https://wpeasyupdates.com/" target="_blank" title="WP Easy Updates">', '</a>' ).'</strong></p></div>';

            }
        }

        add_action( 'admin_notices', 'ayecode_show_update_plugin_requirement' );

    }

}

// Define GD duplicate alert plugin file.
if( !defined( 'GD_DUPLICATE_ALERT_PLUGIN_FILE')) {
    define( 'GD_DUPLICATE_ALERT_PLUGIN_FILE', __FILE__ );
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-geodir-ajax-duplicate-alert.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/geodir-ajax-duplicate-alert-activate.php
 *
 * @since 1.2.1
 */
function activate_gd_duplicate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/geodir-ajax-duplicate-alert-activate.php';
    GD_Duplicate_Alert_Activate::activate();
}

register_activation_hook( __FILE__, 'activate_gd_duplicate' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/geodir-ajax-duplicate-alert-deactivate.php
 *
 * @since 1.2.1
 */
function deactivate_gd_duplicate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/geodir-ajax-duplicate-alert-deactivate.php';
    GD_Duplicate_Alert_Deactivate::deactivate();
}

register_deactivation_hook( __FILE__, 'deactivate_gd_duplicate' );

/**
 * Loads a single instance of gd duplicate alert.
 *
 * @since 1.2.1
 *
 * @see GD_Duplicate_Alert::get_instance()
 *
 * @return object GD_Duplicate_Alert Returns an instance of the class
 */
function init_gd_duplicate_alert() {

    // min core version check
    if( !function_exists("geodir_min_version_check") || !geodir_min_version_check("AJAX Duplicate Alert",GEODIR_DUPLICATE_ALERT_MIN_CORE)){
        return '';
    }

    return GD_Duplicate_Alert::get_instance();
}

add_action( 'plugins_loaded', 'init_gd_duplicate_alert', apply_filters( 'gd_duplicate_alert_action_priority', 10 ) );