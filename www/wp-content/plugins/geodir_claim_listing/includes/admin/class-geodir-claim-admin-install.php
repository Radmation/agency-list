<?php
/**
 * Installation related functions and actions.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Claim_Admin_Install class.
 */
class GeoDir_Claim_Admin_Install {

	/** @var array DB updates and callbacks that need to be run per version */
	private static $db_updates = array(
		/*'2.2.2' => array(
			'geodir_claim_update_222',
			'geodir_claim_update_db_version_222'
		)*/
	);

	private static $background_updater;

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
	}

	/**
	 * Init background updates
	 */
	public static function init_background_updater() {
		if ( ! class_exists( 'GeoDir_Background_Updater' ) ) {
			include_once( GEODIRECTORY_PLUGIN_DIR . 'includes/class-geodir-background-updater.php' );
		}
		self::$background_updater = new GeoDir_Background_Updater();
	}

	/**
	 * Check plugin version and run the updater as required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {
			if ( self::is_v2_upgrade() ) {
				// v2 upgrade
			} else if ( get_option( 'geodir_claim_version' ) !== GEODIR_CLAIM_VERSION ) {
				self::install();
				do_action( 'geodir_claim_manager_updated' );
			}
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_geodir_claim'] ) ) {
			self::update();
		}

		if ( ! empty( $_GET['force_update_geodir_claim'] ) ) {
			$blog_id = get_current_blog_id();

			// Used to fire an action added in WP_Background_Process::_construct() that calls WP_Background_Process::handle_cron_healthcheck().
			// This method will make sure the database updates are executed even if cron is disabled. Nothing will happen if the updates are already running.
			do_action( 'wp_' . $blog_id . '_geodir_claim_updater_cron' );

			wp_safe_redirect( admin_url( 'admin.php?page=gd-settings&tab=claims' ) );
			exit;
		}
	}

	/**
	 * Install plugin.
	 */
	public static function install() {
		global $wpdb;

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! defined( 'GEODIR_CLAIM_INSTALLING' ) ) {
			define( 'GEODIR_CLAIM_INSTALLING', true );
		}

		// Create tables
		self::create_tables();

		// Default options
		self::save_default_options();

		// Set cron schedules
		//self::create_cron_schedules();

		// Update GD version
		self::update_gd_version();

		// Update DB version
		self::maybe_update_db_version();

		// Flush rules after install
		do_action( 'geodir_claim_flush_rewrite_rules' );

		// Trigger action
		do_action( 'geodir_claim_listing_installed' );
	}
	
	/**
	 * Is this a brand new install?
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'geodir_claim_version', null ) ) && is_null( get_option( 'geodir_claim_db_version', null ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function needs_db_update() {
		$current_db_version = get_option( 'geodir_claim_db_version', null );
		$updates            = self::get_db_update_callbacks();

		return ! is_null( $current_db_version ) && ! empty( $updates ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since 2.0.0
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			self::update();
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Update GeoDirectory version to current.
	 */
	private static function update_gd_version() {
		delete_option( 'geodir_claim_version' );
		add_option( 'geodir_claim_version', GEODIR_CLAIM_VERSION );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( 'geodir_claim_db_version' );
		$update_queued      = false;

		if ( empty( self::$background_updater ) ) {
			self::init_background_updater();
		}

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					geodir_error_log( sprintf( 'Queuing %s - %s', $version, $update_callback ) );
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * Create cron schedule.
	 *
	 * @since 2.2.2
	 */
	public static function create_cron_schedules() {
		if ( ! wp_next_scheduled( 'geodir_claim_schedule_event_nudge_emails' ) ) {
			wp_schedule_event( time(), apply_filters( 'geodir_claim_schedule_nudge_emails', 'twicedaily' ), 'geodir_claim_schedule_event_nudge_emails' );
		}
	}

	/**
	 * Update DB version to current.
	 * @param string $version
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'geodir_claim_db_version' );
		add_option( 'geodir_claim_db_version', is_null( $version ) ? GEODIR_CLAIM_VERSION : $version );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function save_default_options() {
		$current_settings = geodir_get_settings();

		$settings = GeoDir_Claim_Admin::load_settings_page( array() );

		if ( ! empty( $settings ) ) {
			foreach ( $settings as $section ) {
				if ( ! method_exists( $section, 'get_settings' ) ) {
					continue;
				}
				$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

				foreach ( $subsections as $subsection ) {
					$options = $section->get_settings( $subsection );
					if ( empty( $options ) ) {
						continue;
					}

					foreach ( $options as $value ) {
						if ( ! isset( $current_settings[ $value['id'] ] ) && isset( $value['default'] ) && isset( $value['id'] ) ) {
							geodir_update_option($value['id'], $value['default']);
						}
					}
				}
			}
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( self::get_schema() );

	}

	/**
	 * Get Table schema.
	 *
	 * A note on indexes; Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of WordPress 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * Changing indexes may cause duplicate index notices in logs due to https://core.trac.wordpress.org/ticket/34870 but dropping
	 * indexes first causes too much load on some servers/larger DB.
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb, $plugin_prefix;

		/*
         * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
         * As of 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
         * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
         */
		$max_index_length = 191;

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		// Claim listing table
		$tables = "CREATE TABLE " . GEODIR_CLAIM_TABLE . " (
		  id int(11) unsigned NOT NULL AUTO_INCREMENT,
		  post_id int(11) unsigned NOT NULL DEFAULT '0',
		  post_type varchar(20) NOT NULL,
		  author_id int(11) unsigned NOT NULL DEFAULT '0',
		  user_id int(11) unsigned NOT NULL DEFAULT '0',
		  user_fullname varchar(255) NOT NULL,
		  user_number varchar(100) NOT NULL,
		  user_position varchar(100) NOT NULL,
		  user_ip varchar(100) NOT NULL,
		  user_comments text NOT NULL,
		  admin_comments text NOT NULL,
		  claim_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  status tinyint(1) NOT NULL DEFAULT '0',
		  rand_string varchar(50) NOT NULL,
		  payment_id int(11) unsigned NOT NULL DEFAULT '0',
		  old_package_id int(11) unsigned NOT NULL DEFAULT '0',
		  package_id int(11) unsigned NOT NULL DEFAULT '0',
		  meta text NOT NULL,
		  PRIMARY KEY (id)
		) $collate;";

		return $tables;
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 * @param  array $tables
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		global $wpdb;

		$db_prefix = $wpdb->prefix;
		$gd_prefix = 'geodir_';

		$tables["{$gd_prefix}claim"] = "{$db_prefix}{$gd_prefix}claim";

		return $tables;
	}

	/**
	 * Is v1 to v2 upgrade.
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function is_v2_upgrade() {
		if ( ( get_option( 'geodirectory_db_version' ) && version_compare( get_option( 'geodirectory_db_version' ), '2.0.0.0', '<' ) ) || ( get_option( 'geodirclaim_db_version' ) && version_compare( get_option( 'geodirclaim_db_version' ), '2.0.0.0', '<' ) && ( is_null( get_option( 'geodirclaim_db_version', null ) ) || ( get_option( 'geodir_claim_db_version' ) && version_compare( get_option( 'geodir_claim_db_version' ), '2.0.0.0', '<' ) ) ) ) ) {
			return true;
		}

		return false;
	}
}
