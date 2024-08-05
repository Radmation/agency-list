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
 * GeoDir_Admin_Install Class.
 */
class GeoDir_Location_Admin_Install {

	/** @var array DB updates and callbacks that need to be run per version */
	private static $db_updates = array(
		'2.0.1.0' => array(
			'geodir_location_upgrade_2010',
			'geodir_location_update_2010_db_version'
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
		add_action( 'admin_init', array( __CLASS__, 'on_plugin_activation' ), 11 );
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
	 * Check GeoDirectory location manager version and run the updater as required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {
			if ( self::is_v2_upgrade() ) {
				// v2 upgrade
			} else if ( get_option( 'geodir_location_version' ) !== GEODIRLOCATION_VERSION ) {
				self::install();
				do_action( 'geodir_location_updated' );
			}
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_geodir_location'] ) ) {
			self::update();
		}

		if ( ! empty( $_GET['force_update_geodir_location'] ) ) {
			$blog_id = get_current_blog_id();

			// Used to fire an action added in WP_Background_Process::_construct() that calls WP_Background_Process::handle_cron_healthcheck().
			// This method will make sure the database updates are executed even if cron is disabled. Nothing will happen if the updates are already running.
			do_action( 'wp_' . $blog_id . '_geodir_location_updater_cron' );

			wp_safe_redirect( admin_url( 'admin.php?page=gd-settings' ) );
			exit;
		}
	}

	/**
	 * Install GeoDirectory Location Manager.
	 */
	public static function install() {
		global $wpdb;

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! defined( 'GEODIR_LOCATION_INSTALLING' ) ) {
			define( 'GEODIR_LOCATION_INSTALLING', true );
		}

		self::create_tables();
		self::merge_default_location();
		self::save_default_options();

		// Update GD version
		self::update_gd_version();

		// Update DB version
		self::maybe_update_db_version();

		// Flush rules after install
		do_action( 'geodir_location_flush_rewrite_rules' );

		// Trigger action
		do_action( 'geodir_location_installed' );
	}
	
	/**
	 * Is this a brand new GeoDirectory install?
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'geodir_location_version', null ) ) && is_null( get_option( 'geodir_location_db_version', null ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function needs_db_update() {
		$current_db_version = get_option( 'geodir_location_db_version', null );
		$updates            = self::get_db_update_callbacks();

		return ! is_null( $current_db_version ) && ! empty( $updates ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}

	/**
	 * Insert the default countries if needed.
	 */
	public static function merge_default_location(){
		global $wpdb, $plugin_prefix,$geodirectory;
		
		$default_location = (array)$geodirectory->location->get_default_location();
		$location = $geodirectory->location->add_new_location( $default_location );
		geodir_location_set_default( $location->location_id );

		$post_types = geodir_get_posttypes();
		if ( ! empty( $post_types ) ) {
			$default_location = (array)$geodirectory->location->get_default_location();

			foreach ( $post_types as $post_type ) {
				$table = geodir_db_cpt_table( $post_type );
				// Add neighbourhood column
				geodir_add_column_if_not_exist( $table, 'neighbourhood', 'VARCHAR(50) NULL' );
			}
		}
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
		delete_option( 'geodir_location_version' );
		add_option( 'geodir_location_version', GEODIRLOCATION_VERSION );
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
		$current_db_version = get_option( 'geodir_location_db_version' );
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
	 * Update DB version to current.
	 * @param string $version
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'geodir_location_db_version' );
		add_option( 'geodir_location_db_version', is_null( $version ) ? GEODIRLOCATION_VERSION : $version );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function save_default_options() {
		$current_settings = geodir_get_settings();

		$settings = GeoDir_Location_Admin::load_settings_page( array() );

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
		
		// Locations table
		$tables = "CREATE TABLE " . GEODIR_LOCATIONS_TABLE . " (
				location_id int(11) NOT NULL AUTO_INCREMENT,
				country varchar(50) NOT NULL,
				region varchar(50) NOT NULL,
				city varchar(50) NOT NULL,
				country_slug varchar(50) NOT NULL,
				region_slug varchar(50) NOT NULL,
				city_slug varchar(50) NOT NULL,
				latitude varchar(22) NOT NULL,
				longitude varchar(22) NOT NULL,
				is_default TINYINT(1) NOT NULL DEFAULT '0',
				PRIMARY KEY (location_id)
			) $collate; ";

		// Neighbourhoods table
		$tables .= "CREATE TABLE " . GEODIR_NEIGHBOURHOODS_TABLE . " (
				hood_id int(11) NOT NULL AUTO_INCREMENT,
				hood_location_id int(11) NOT NULL,
				hood_name varchar(50) NOT NULL,
				hood_latitude varchar(22) NOT NULL,
				hood_longitude varchar(22) NOT NULL,
				hood_slug varchar(50) NOT NULL,
				hood_meta_title varchar(254) NOT NULL,
				hood_meta varchar(254) NOT NULL,
				hood_description text NOT NULL,
				cpt_desc longtext NOT NULL,
				image int(11) NOT NULL,
				PRIMARY KEY (hood_id)
			) $collate; ";

		// Location SEO table
		$tables .= "CREATE TABLE " . GEODIR_LOCATION_SEO_TABLE . " (
				seo_id int(11) NOT NULL AUTO_INCREMENT,
				location_type varchar(20) NOT NULL,
				country_slug varchar(50) NOT NULL,
				region_slug varchar(50) NOT NULL,
				city_slug varchar(50) NOT NULL,
				meta_title varchar(254) NOT NULL,
				meta_desc text NOT NULL,
				image varchar(254) NOT NULL,
				image_tagline varchar(140) NOT NULL,
				location_desc text NOT NULL,
				cpt_desc longtext NOT NULL,
				PRIMARY KEY (seo_id)
			) $collate; ";

		// Location term meta table
		$tables .= "CREATE TABLE " . GEODIR_LOCATION_TERM_META . " (
				id int NOT NULL AUTO_INCREMENT,
				location_type varchar(20) NULL DEFAULT NULL,
				location_name varchar(50) NULL DEFAULT NULL,
				region_slug varchar(50) NOT NULL,
				country_slug varchar(50) NOT NULL,
				term_count text NOT NULL,
				review_count text NOT NULL,
				PRIMARY KEY (id)
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
		if ( GEODIR_LOCATION_PLUGIN_BASENAME == $file ) {
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

		$tables["{$gd_prefix}post_locations"] = "{$db_prefix}{$gd_prefix}post_locations";
		$tables["{$gd_prefix}post_neighbourhood"] = "{$db_prefix}{$gd_prefix}post_neighbourhood";
		$tables["{$gd_prefix}location_seo"] = "{$db_prefix}{$gd_prefix}location_seo";
		$tables["{$gd_prefix}term_meta"] = "{$db_prefix}{$gd_prefix}term_meta";

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
		if ( ( get_option( 'geodirectory_db_version' ) && version_compare( get_option( 'geodirectory_db_version' ), '2.0.0.0', '<' ) ) || ( get_option( 'geodirlocation_db_version' ) && version_compare( get_option( 'geodirlocation_db_version' ), '2.0.0.0', '<' ) && ( is_null( get_option( 'geodirlocation_db_version', null ) ) || ( get_option( 'geodir_location_db_version' ) && version_compare( get_option( 'geodir_location_db_version' ), '2.0.0.0', '<' ) ) ) ) ) {
			return true;
		}

		return false;
	}

	/*
	 * Handle plugin activation.
	 *
	 * @since 2.1.0.6
	 */
	public static function on_plugin_activation() {
		if ( is_admin() && get_option( 'geodir_activate_location_manager' ) ) {
			delete_option( 'geodir_activate_location_manager' );

			do_action( 'geodir_location_manager_activated' );

			// Merge post locations on activation of plugin.
			if ( geodir_get_option( 'multi_city' ) ) {
				geodir_location_merge_post_locations();
			}
		}
	}
}


