<?php
/**
 * Copyright (C) 2014-2020 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Ai1wmge_Main_Controller extends Ai1wmve_Main_Controller {

	/**
	 * Register plugin menus
	 *
	 * @return void
	 */
	public function admin_menu() {
		if ( ai1wmge_is_admin() ) {
			add_submenu_page(
				'ai1wm_export',
				__( 'Google Drive Settings', AI1WMGE_PLUGIN_NAME ),
				__( 'Google Drive Settings', AI1WMGE_PLUGIN_NAME ),
				'export',
				'ai1wmge_settings',
				'Ai1wmge_Settings_Controller::index'
			);
		}
	}

	/**
	 * Enqueue scripts and styles for Export Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_export_scripts_and_styles( $hook ) {
		if ( stripos( 'toplevel_page_ai1wm_export', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmge_export',
				Ai1wm_Template::asset_link( 'css/export.min.rtl.css', 'AI1WMGE' ),
				array( 'ai1wm_export' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmge_export',
				Ai1wm_Template::asset_link( 'css/export.min.css', 'AI1WMGE' ),
				array( 'ai1wm_export' )
			);
		}

		wp_enqueue_script(
			'ai1wmge_export',
			Ai1wm_Template::asset_link( 'javascript/export.min.js', 'AI1WMGE' ),
			array( 'ai1wm_export' )
		);

		wp_localize_script(
			'ai1wmge_export',
			'ai1wmge_dependencies',
			array( 'messages' => $this->get_missing_dependencies() )
		);
	}

	/**
	 * Enqueue scripts and styles for Import Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_import_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_import', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmge_import',
				Ai1wm_Template::asset_link( 'css/import.min.rtl.css', 'AI1WMGE' ),
				array( 'ai1wm_import' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmge_import',
				Ai1wm_Template::asset_link( 'css/import.min.css', 'AI1WMGE' ),
				array( 'ai1wm_import' )
			);
		}

		wp_enqueue_script(
			'ai1wmge_import',
			Ai1wm_Template::asset_link( 'javascript/import.min.js', 'AI1WMGE' ),
			array( 'ai1wm_import' )
		);

		wp_localize_script(
			'ai1wmge_import',
			'ai1wmge_import',
			array(
				'ajax' => array(
					'browser_url'     => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmge_gdrive_browser' ) ),
					'incremental_url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmge_gdrive_incremental' ) ),
					'root'            => ai1wmge_get_root_folder(),
				),
			)
		);

		wp_localize_script(
			'ai1wmge_import',
			'ai1wmge_dependencies',
			array( 'messages' => $this->get_missing_dependencies() )
		);
	}

	/**
	 * Enqueue scripts and styles for Settings Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_settings_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wmge_settings', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmge_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.rtl.css', 'AI1WMGE' ),
				array( 'ai1wm_servmask' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmge_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.css', 'AI1WMGE' ),
				array( 'ai1wm_servmask' )
			);
		}

		wp_enqueue_script(
			'ai1wmge_settings',
			Ai1wm_Template::asset_link( 'javascript/settings.min.js', 'AI1WMGE' ),
			array( 'ai1wm_settings' )
		);

		wp_localize_script(
			'ai1wmge_settings',
			'ai1wm_feedback',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wmge_settings',
			'ai1wmge_settings',
			array(
				'ajax'  => array(
					'folder_url'   => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmge_gdrive_folder' ) ),
					'account_url'  => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmge_gdrive_account' ) ),
					'selector_url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmge_gdrive_selector' ) ),
				),
				'token' => get_option( 'ai1wmge_gdrive_token' ),
			)
		);
	}

	/**
	 * Register listeners for actions
	 *
	 * @return void
	 */
	protected function activate_actions() {
		add_action( 'admin_init', array( $this, 'init' ) );

		add_action( 'plugins_loaded', array( $this, 'ai1wm_notification' ), 20 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_export_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts_and_styles' ), 20 );
	}

	/**
	 * Enable notifications
	 *
	 * @return void
	 */
	public function ai1wm_notification() {
		if ( ai1wmge_is_running() ) {
			add_filter( 'ai1wm_notification_ok_toggle', 'Ai1wmge_Settings_Controller::notify_ok_toggle' );
			add_filter( 'ai1wm_notification_ok_email', 'Ai1wmge_Settings_Controller::notify_email' );
			add_filter( 'ai1wm_notification_error_toggle', 'Ai1wmge_Settings_Controller::notify_error_toggle' );
			add_filter( 'ai1wm_notification_error_subject', 'Ai1wmge_Settings_Controller::notify_error_subject' );
			add_filter( 'ai1wm_notification_error_email', 'Ai1wmge_Settings_Controller::notify_email' );
		}
	}

	public function get_missing_dependencies() {
		$messages = array();
		if ( ! extension_loaded( 'curl' ) ) {
			$messages[] = __( 'Your PHP is missing cURL extension. <a href="https://help.servmask.com/knowledgebase/curl-missing-in-php-installation/" target="_blank">Technical details</a>', AI1WMGE_PLUGIN_NAME );
		}

		return $messages;
	}

	/**
	* Export and import buttons
	*/
	public function ai1wm_buttons() {
		// Add export button
		add_filter( 'ai1wm_export_gdrive', 'Ai1wmge_Export_Controller::button' );

		// Add import button
		add_filter( 'ai1wm_import_gdrive', 'Ai1wmge_Import_Controller::button' );
	}

	/**
	 * Export and import commands
	 *
	 * @return void
	 */
	public function ai1wm_commands() {
		if ( ai1wmge_is_running() ) {
			add_filter( 'ai1wm_export', 'Ai1wmge_Export_GDrive::execute', 250 );
			add_filter( 'ai1wm_export', 'Ai1wmge_Export_Upload::execute', 260 );
			add_filter( 'ai1wm_export', 'Ai1wmge_Export_Retention::execute', 280 );
			add_filter( 'ai1wm_export', 'Ai1wmge_Export_Done::execute', 290 );

			add_filter( 'ai1wm_import', 'Ai1wmge_Import_GDrive::execute', 20 );
			add_filter( 'ai1wm_import', 'Ai1wmge_Import_Download::execute', 30 );
			add_filter( 'ai1wm_import', 'Ai1wmge_Import_Settings::execute', 290 );
			add_filter( 'ai1wm_import', 'Ai1wmge_Import_Database::execute', 310 );

			remove_filter( 'ai1wm_export', 'Ai1wm_Export_Download::execute', 250 );
			remove_filter( 'ai1wm_import', 'Ai1wm_Import_Upload::execute', 5 );
		}

		if ( ai1wmge_is_incremental() ) {
			add_filter( 'ai1wm_export', 'Ai1wmge_Export_Incremental_Content::execute', 105 );
			add_filter( 'ai1wm_export', 'Ai1wmge_Export_Incremental_Media::execute', 115 );
			add_filter( 'ai1wm_export', 'Ai1wmge_Export_Incremental_Plugins::execute', 125 );
			add_filter( 'ai1wm_export', 'Ai1wmge_Export_Incremental_Themes::execute', 135 );
			add_filter( 'ai1wm_export', 'Ai1wmge_Export_Incremental_Backups::execute', 270 );

			add_filter( 'ai1wm_import', 'Ai1wmge_Import_Incremental_GDrive::execute', 20 );
			add_filter( 'ai1wm_import', 'Ai1wmge_Import_Incremental_Download::execute', 30 );

			remove_filter( 'ai1wm_export', 'Ai1wmge_Export_Retention::execute', 280 );
			remove_filter( 'ai1wm_import', 'Ai1wmge_Import_GDrive::execute', 20 );
			remove_filter( 'ai1wm_import', 'Ai1wmge_Import_Download::execute', 30 );
		}
	}

	/**
	 * Check whether All-in-One WP Migration has been loaded
	 *
	 * @return void
	 */
	public function ai1wm_loaded() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 20 );
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		}

		// Google Drive init cron
		add_action( 'init', 'Ai1wmge_Settings_Controller::init_cron' );

		// Google Drive settings
		if ( ai1wmge_is_admin() ) {
			add_action( 'admin_post_ai1wmge_gdrive_settings', 'Ai1wmge_Settings_Controller::settings' );
			add_action( 'admin_post_ai1wmge_gdrive_revoke', 'Ai1wmge_Settings_Controller::revoke' );
			add_action( 'ai1wmge_settings_left_end', 'Ai1wmge_Settings_Controller::picker' );
		}

		// Cron settings
		add_action( 'ai1wmge_gdrive_hourly_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'ai1wmge_gdrive_daily_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'ai1wmge_gdrive_weekly_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'ai1wmge_gdrive_monthly_export', 'Ai1wm_Export_Controller::export' );

		// File picker
		add_action( 'ai1wm_import_left_end', 'Ai1wmge_Import_Controller::picker' );
	}

	/**
	 * WP CLI commands: extension
	 *
	 * @return void
	 */
	public function wp_cli_extension() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command(
				'ai1wm gdrive',
				'Ai1wmge_Gdrive_WP_CLI_Command',
				array(
					'shortdesc'     => __( 'All-in-One WP Migration Command for Google Drive', AI1WMGE_PLUGIN_NAME ),
					'before_invoke' => array( $this, 'activate_extension_commands' ),
				)
			);
			WP_CLI::add_command(
				'ai1wm gdrive incremental',
				'Ai1wmge_Gdrive_WP_CLI_Incremental_Command',
				array(
					'shortdesc'     => __( 'All-in-One WP Migration Command for Google Drive incremental backups', AI1WMGE_PLUGIN_NAME ),
					'before_invoke' => array( $this, 'activate_extension_incremental_commands' ),
				)
			);
		}
	}

	/**
	 * Activates extension specific commands
	 *
	 * @return void
	 */
	public function activate_extension_commands() {
		$_GET['gdrive'] = 1;
		$this->ai1wm_commands();
	}

	/**
	 * Activates extension specific commands
	 *
	 * @return void
	 */
	public function activate_extension_incremental_commands() {
		$_GET['incremental'] = 1;
		$this->activate_extension_commands();
	}

	/**
	 * Display All-in-One WP Migration notice
	 *
	 * @return void
	 */
	public function ai1wm_notice() {
		?>
		<div class="error">
			<p>
				<?php
				_e(
					'Google Drive Extension requires <a href="https://wordpress.org/plugins/all-in-one-wp-migration/" target="_blank">All-in-One WP Migration plugin</a> to be activated. ' .
					'<a href="https://help.servmask.com/knowledgebase/install-instructions-for-google-drive-extension/" target="_blank">Google Drive Extension install instructions</a>',
					AI1WMGE_PLUGIN_NAME
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add links to plugin list page
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === AI1WMGE_PLUGIN_BASENAME ) {
			$links[] = __( '<a href="https://help.servmask.com/knowledgebase/google-drive-extension-user-guide/" target="_blank">User Guide</a>', AI1WMGE_PLUGIN_NAME );
			$links[] = __( '<a href="https://servmask.com/contact-support" target="_blank">Contact Support</a>', AI1WMGE_PLUGIN_NAME );
		}

		return $links;
	}

	/**
	 * Register initial parameters
	 *
	 * @return void
	 */
	public function init() {
		if ( isset( $_GET['ai1wmge_token'], $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'] ) && current_user_can( 'export' ) ) {
			update_option( 'ai1wmge_gdrive_token', urldecode( $_GET['ai1wmge_token'] ) );

			if ( isset( $_GET['ai1wmge_app_folder'] ) && $_GET['ai1wmge_app_folder'] === 'yes' ) {
				update_option( 'ai1wmge_gdrive_app_folder', true );
			} else {
				delete_option( 'ai1wmge_gdrive_app_folder' );
			}

			// Redirect to settings page
			wp_redirect( network_admin_url( 'admin.php?page=ai1wmge_settings' ) );
			exit;
		}
	}

	/**
	 * Register initial router
	 *
	 * @return void
	 */
	public function router() {
		if ( current_user_can( 'export' ) ) {
			if ( ai1wmge_is_admin() ) {
				add_action( 'wp_ajax_ai1wmge_gdrive_folder', 'Ai1wmge_Settings_Controller::folder' );
				add_action( 'wp_ajax_ai1wmge_gdrive_account', 'Ai1wmge_Settings_Controller::account' );
				add_action( 'wp_ajax_ai1wmge_gdrive_selector', 'Ai1wmge_Settings_Controller::selector' );
			}
		}

		if ( current_user_can( 'import' ) ) {
			add_action( 'wp_ajax_ai1wmge_gdrive_browser', 'Ai1wmge_Import_Controller::browser' );
			add_action( 'wp_ajax_ai1wmge_gdrive_incremental', 'Ai1wmge_Import_Controller::incremental' );
		}
	}
}
