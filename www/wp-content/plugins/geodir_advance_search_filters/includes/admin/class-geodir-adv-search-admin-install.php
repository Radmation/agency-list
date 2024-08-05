<?php
/**
 * Installation related functions and actions.
 *
 * @author   AyeCode
 * @category Admin
 * @package  GeoDirectory/Classes
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Adv_Search_Admin_Install Class.
 */
class GeoDir_Adv_Search_Admin_Install {

	/** @var array DB updates and callbacks that need to be run per version */
	private static $db_updates = array(
		'2.0.1.0' => array(
			'geodir_search_upgrade_2010',
			'geodir_search_update_2010_db_version'
		),
		'2.1.1.1' => array(
			'geodir_search_upgrade_2111',
			'geodir_search_update_2111_db_version'
		)
	);

	private static $background_updater;

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
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
			} else if ( get_option( 'geodir_advance_search_version' ) !== GEODIR_ADV_SEARCH_VERSION ) {
				self::install();
				do_action( 'geodir_advance_search_updated' );
			}
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_geodir_adv_search'] ) ) {
			self::update();
		}

		if ( ! empty( $_GET['force_update_geodir_adv_search'] ) ) {
			$blog_id = get_current_blog_id();

			// Used to fire an action added in WP_Background_Process::_construct() that calls WP_Background_Process::handle_cron_healthcheck().
			// This method will make sure the database updates are executed even if cron is disabled. Nothing will happen if the updates are already running.
			do_action( 'wp_' . $blog_id . '_geodir_adv_search_updater_cron' );

			wp_safe_redirect( admin_url( 'admin.php?page=gd-settings' ) );
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

		if ( ! defined( 'GEODIR_ADV_SEARCH_INSTALLING' ) ) {
			define( 'GEODIR_ADV_SEARCH_INSTALLING', true );
		}

		self::create_tables();
		self::save_default_options();

		// Schedule cron events
		self::schedule_cron_events();

		// Update GD version
		self::update_gd_version();

		// Update DB version
		self::maybe_update_db_version();

		// Flush rules after install
		do_action( 'geodir_advance_search_flush_rewrite_rules' );

		// Trigger action
		do_action( 'geodir_advance_search_installed' );
	}
	
	/**
	 * Is this a brand new GeoDirectory install?
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'geodir_advance_search_version', null ) ) && is_null( get_option( 'geodir_advance_search_db_version', null ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function needs_db_update() {
		$current_db_version = get_option( 'geodir_advance_search_db_version', null );
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
		delete_option( 'geodir_advance_search_version' );
		add_option( 'geodir_advance_search_version', GEODIR_ADV_SEARCH_VERSION );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since  3.0.0
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( 'geodir_advance_search_db_version' );
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
	 * Schedule cron events.
	 *
	 * @since 2.0.1.0
	 */
	private static function schedule_cron_events() {
		geodir_search_schedule_events();
	}

	/**
	 * Update DB version to current.
	 * @param string $version
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'geodir_advance_search_db_version' );
		add_option( 'geodir_advance_search_db_version', is_null( $version ) ? GEODIR_ADV_SEARCH_VERSION : $version );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function save_default_options() {
		$current_settings = geodir_get_settings();

		$settings = GeoDir_Adv_Search_Admin::load_settings_page( array() );

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
		
		// Search fields table
		$tables = "CREATE TABLE " . GEODIR_ADVANCE_SEARCH_TABLE . " (
				      id int(11) NOT NULL AUTO_INCREMENT,
				      post_type varchar(50) NOT NULL,
				      htmlvar_name varchar(255) NOT NULL,
				      frontend_title varchar(255) NULL DEFAULT NULL,
				      admin_title varchar(255) NULL DEFAULT NULL,
				      description text NULL DEFAULT NULL,
				      field_type varchar(100) NOT NULL COMMENT 'text,checkbox,radio,select,textarea',
				      input_type varchar(100) NULL DEFAULT NULL,
					  data_type varchar(100) NULL DEFAULT NULL,
					  search_condition varchar(100) NULL DEFAULT NULL,
					  range_expand int(11) NOT NULL,
					  range_mode tinyint(1) NOT NULL DEFAULT '0',
					  expand_search tinyint(1) NOT NULL DEFAULT '0',
					  range_start int(11) NOT NULL,
					  range_min int(11) NOT NULL,
					  range_max int(11) NOT NULL,
					  range_step int(11) NOT NULL,
					  range_from_title varchar(255) NULL DEFAULT NULL,
					  range_to_title varchar(255) NULL DEFAULT NULL,
					  main_search tinyint(1) NOT NULL DEFAULT '0',
					  main_search_priority int(11) NOT NULL,
					  sort_order int(11) NOT NULL,
					  tab_level int(11) NOT NULL,
					  tab_parent int(11) NOT NULL,
				      extra_fields text NULL DEFAULT NULL,
				      PRIMARY KEY (id),
				      KEY `post_type` (`post_type`)
				  ) $collate; ";

		// Business hours table
		$tables .= " CREATE TABLE " . GEODIR_BUSINESS_HOURS_TABLE . " (
			id bigint(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id bigint(11) UNSIGNED DEFAULT NULL,
			open int(9) UNSIGNED DEFAULT NULL,
			close int(9) UNSIGNED DEFAULT NULL,
			open_utc int(9) UNSIGNED NOT NULL,
			close_utc int(9) UNSIGNED NOT NULL,
			open_dst int(9) UNSIGNED NOT NULL,
			close_dst int(9) UNSIGNED NOT NULL,
			open_off int(9) UNSIGNED NOT NULL DEFAULT '0',
			close_off int(9) UNSIGNED NOT NULL DEFAULT '0',
			`offset` int(9) NOT NULL DEFAULT '0',
			timezone_string varchar(100) NOT NULL,
			has_dst tinyint(1) NOT NULL DEFAULT '0',
			is_dst tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (id),
			KEY post_id (post_id)
			) $collate; ";

		return $tables;
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( GEODIR_ADV_SEARCH_PLUGIN_BASENAME == $file ) {
			$row_meta = array();

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
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

		$tables["{$gd_prefix}custom_advance_search_fields"] = "{$db_prefix}{$gd_prefix}custom_advance_search_fields";
		$tables["{$gd_prefix}business_hours"] = "{$db_prefix}{$gd_prefix}business_hours";

		return $tables;
	}

	/**
	 * Get slug from path
	 * @param  string $key
	 * @return string
	 */
	private static function format_plugin_slug( $key ) {
		$slug = explode( '/', $key );
		$slug = explode( '.', end( $slug ) );
		return $slug[0];
	}

	/**
	 * Is v1 to v2 upgrade.
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function is_v2_upgrade() {
		if ( ( get_option( 'geodirectory_db_version' ) && version_compare( get_option( 'geodirectory_db_version' ), '2.0.0.0', '<' ) ) || ( get_option( 'geodiradvancesearch_db_version' ) && version_compare( get_option( 'geodiradvancesearch_db_version' ), '2.0.0.0', '<' ) && ( is_null( get_option( 'geodiradvancesearch_db_version', null ) ) || ( get_option( 'geodir_advance_search_db_version' ) && version_compare( get_option( 'geodir_advance_search_db_version' ), '2.0.0.0', '<' ) ) ) ) ) {
			return true;
		}

		return false;
	}
}
