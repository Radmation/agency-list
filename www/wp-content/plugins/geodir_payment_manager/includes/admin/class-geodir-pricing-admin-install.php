<?php
/**
 * Installation related functions and actions.
 *
 * @since 2.5.0.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Admin_Install class.
 */
class GeoDir_Pricing_Admin_Install {

	/** @var array DB updates and callbacks that need to be run per version */
	private static $db_updates = array(
		/*'2.5.0' => array(
			'geodir_update_200_file_paths',
			'geodir_update_200_permalinks',
		)*/
		/*'2.5.0.1-dev' => array(
			'geodir_update_2001_dev_db_version',
		),*/
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
			} else if ( get_option( 'geodir_pricing_version' ) !== GEODIR_PRICING_VERSION ) {
				self::install();
				do_action( 'geodir_pricing_manager_updated' );
			}
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_geodir_pricing'] ) ) {
			self::update();
		}
		if ( ! empty( $_GET['force_update_geodir_pricing'] ) ) {
			do_action( 'geodirpricing_updater_cron' );
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

		if ( ! defined( 'GEODIR_PRICING_INSTALLING' ) ) {
			define( 'GEODIR_PRICING_INSTALLING', true );
		}

		// Create tables
		self::create_tables();

		// Default options
		self::save_default_options();

		// Create Fields
		self::create_default_fields();

		// Create default packages
		self::create_default_packages();

		// Schedule cron jobs
		self::create_cron_jobs();

		// Update GD version
		self::update_gd_version();

		// Update DB version
		self::maybe_update_db_version();

		// Flush rules after install
		do_action( 'geodir_pricing_flush_rewrite_rules' );

		// Trigger action
		do_action( 'geodir_pricing_manager_installed' );
	}
	
	/**
	 * Is this a brand new install?
	 *
	 * @since 2.5.0
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'geodir_pricing_version', null ) ) && is_null( get_option( 'geodir_pricing_db_version', null ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since 2.5.0
	 * @return boolean
	 */
	private static function needs_db_update() {
		$current_db_version = get_option( 'geodir_pricing_db_version', null );
		$updates            = self::get_db_update_callbacks();

		return ! is_null( $current_db_version ) && ! empty( $updates ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since 2.5.0
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
		delete_option( 'geodir_pricing_version' );
		add_option( 'geodir_pricing_version', GEODIR_PRICING_VERSION );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since  2.5.0
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( 'geodir_pricing_db_version' );
		$update_queued      = false;

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
		delete_option( 'geodir_pricing_db_version' );
		add_option( 'geodir_pricing_db_version', is_null( $version ) ? GEODIR_PRICING_VERSION : $version );
	}

	/**
	 * Create cron jobs (clear them first).
     *
     * @since 2.5.0
	 */
	private static function create_cron_jobs() {
		wp_clear_scheduled_hook( 'geodir_task_hook' );
		wp_clear_scheduled_hook( 'geodir_pricing_schedule_event_expire_check' );
		wp_clear_scheduled_hook( 'geodir_pricing_schedule_event_pre_expiry_reminders' );
		wp_schedule_event( time(), apply_filters( 'geodir_pricing_schedule_expire_check', 'twicedaily' ), 'geodir_pricing_schedule_event_expire_check' ); // hourly, twicedaily, daily
		wp_schedule_event( time(), apply_filters( 'geodir_pricing_schedule_pre_expiry_reminders', 'twicedaily' ), 'geodir_pricing_schedule_event_pre_expiry_reminders' ); // hourly, twicedaily, daily
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function save_default_options() {
		$current_settings = geodir_get_settings();

		$settings = GeoDir_Pricing_Admin::load_settings_page( array() );

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

		// Packages table
		$tables = "CREATE TABLE " . GEODIR_PRICING_PACKAGES_TABLE . " (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `post_type` varchar(20) NOT NULL,
		  `name` varchar(255) NOT NULL,
		  `title` text NOT NULL,
		  `description` text NOT NULL,
		  `fa_icon` varchar(50) NOT NULL,
		  `amount` varchar(50) NOT NULL DEFAULT '0',
		  `time_interval` int(11) unsigned NOT NULL DEFAULT '0',
		  `time_unit` varchar(1) NOT NULL DEFAULT 'D',
		  `recurring` tinyint(1) NOT NULL DEFAULT '0',
		  `recurring_limit` int(11) unsigned NOT NULL DEFAULT '0',
		  `trial` tinyint(1) NOT NULL DEFAULT '0',
		  `trial_amount` varchar(50) NOT NULL DEFAULT '0',
		  `trial_interval` int(11) unsigned NOT NULL DEFAULT '1',
		  `trial_unit` varchar(1) NOT NULL DEFAULT 'M',
		  `downgrade_pkg` int(11) unsigned NOT NULL DEFAULT '0',
		  `is_default` tinyint(1) NOT NULL DEFAULT '0',
		  `display_order` int(11) unsigned NOT NULL DEFAULT '0',
		  `post_status` varchar(20) NOT NULL,
		  `status` tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (`id`)
		) $collate; ";

		// Package meta table
		$tables .= "CREATE TABLE " . GEODIR_PRICING_PACKAGE_META_TABLE . " (
		  `meta_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `package_id` int(11) unsigned NOT NULL DEFAULT '0',
		  `meta_key` varchar(255) DEFAULT NULL,
		  `meta_value` text,
		  PRIMARY KEY (`meta_id`),
		  KEY `package_id` (`package_id`),
		  KEY `meta_key` (`meta_key`({$max_index_length}))
		) $collate; ";

		// Post package relationship table
		$tables .= "CREATE TABLE " . GEODIR_PRICING_POST_PACKAGES_TABLE . " (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `post_id` int(11) unsigned NOT NULL DEFAULT '0',
		  `package_id` int(11) unsigned NOT NULL DEFAULT '0',
		  `cart` varchar(50) NOT NULL,
		  `invoice_id` int(11) unsigned NOT NULL DEFAULT '0',
		  `product_id` int(11) unsigned NOT NULL DEFAULT '0',
		  `task` varchar(50) NOT NULL,
		  `meta` text NOT NULL,
		  `date` datetime NOT NULL,
		  `status` varchar(20) NOT NULL,
		  PRIMARY KEY (`id`)
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
		if ( GEODIR_PRICING_PLUGIN_BASENAME == $file ) {
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

		$tables["{$gd_prefix}price"] = "{$db_prefix}{$gd_prefix}price";
		$tables["{$gd_prefix}pricemeta"] = "{$db_prefix}{$gd_prefix}pricemeta";
		$tables["{$gd_prefix}post_packages"] = "{$db_prefix}{$gd_prefix}post_packages";

		return $tables;
	}

	/**
	 * Is v1 to v2 upgrade.
	 *
	 * @since 2.5.0
	 * @return boolean
	 */
	private static function is_v2_upgrade() {
		if ( ( get_option( 'geodirectory_db_version' ) && version_compare( get_option( 'geodirectory_db_version' ), '2.0.0.0', '<' ) ) || ( get_option( 'geodir_payments_db_version' ) && version_compare( get_option( 'geodir_payments_db_version' ), '2.5.0.0', '<' ) && ( is_null( get_option( 'geodir_payments_db_version', null ) ) || ( get_option( 'geodir_pricing_db_version' ) && version_compare( get_option( 'geodir_pricing_db_version' ), '2.5.0.0', '<' ) ) ) ) ) {
			return true;
		}

		return false;
	}

	public static function create_default_fields() {
		$post_types = geodir_get_posttypes( 'names' );

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $key => $post_type ) {
				self::insert_default_fields( $post_type );
			}
		}
	}

	public static function insert_default_fields( $post_type = 'gd_place' ) {
		$fields = self::get_post_type_default_fields( $post_type );

		/**
		 * Filter the array of default custom fields DB table data.
		 *
		 * @since 2.0.0
		 * @param string $fields The default custom fields as an array.
		 */
		$fields = apply_filters( 'geodir_before_default_custom_fields_saved', $fields );

		foreach ( $fields as $field_index => $field ) {
			geodir_custom_field_save( $field );
		}
	}

	public static function get_post_type_default_fields( $post_type = 'gd_place' ) {
		$fields = array();
		$fields[] = array(
			'post_type' => $post_type,
			'data_type' => 'INT',
			'field_type' => 'text',
			'field_type_key' => 'package_id',
			'admin_title' => __('Package', 'geodirectory'),
			'frontend_desc' => __('Select your package.', 'geodirectory'),
			'frontend_title' => __('Package', 'geodirectory'),
			'htmlvar_name' => 'package_id',
			'default_value' => '',
			'sort_order' => '-2',
			'is_active' => '1',
			'option_values' => '',
			'is_default' => '1',
			'show_in' => '',
			'show_on_pkg' => '',
			'field_icon' => 'fas fa-dollar-sign',
			'clabels' => __('Package', 'geodirectory'),
			'add_column' => true
		);
		
		$fields[] = array(
			'post_type' => $post_type,
			'data_type' => 'DATE',
			'field_type' => 'datepicker',
			'field_type_key' => 'expire_date',
			'admin_title' => __('Expire Date', 'geodirectory'),
			'frontend_desc' => __('Post expire date, usually set automatically. Leave blank to set expire date "Never".', 'geodirectory'),
			'frontend_title' => __('Expire Date', 'geodirectory'),
			'htmlvar_name' => 'expire_date',
			'default_value' => '',
			'sort_order' => '-1',
			'is_active' => '1',
			'option_values' => '',
			'is_default' => '1',
			'show_in' => '',
			'show_on_pkg' => '',
			'field_icon' => 'fas fa-clock',
			'clabels' => __('Expire Date', 'geodirectory'),
			'for_admin_use' => true,
			'add_column' => true
		);

		return apply_filters( 'geodir_pricing_post_type_default_fields', $fields, $post_type );
	}

	public static function create_default_packages() {
		global $wpdb;
		$post_types = geodir_get_posttypes( 'names' );

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $key => $post_type ) {
				$table = geodir_db_cpt_table( $post_type );
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . GEODIR_PRICING_PACKAGES_TABLE . " WHERE post_type = %s LIMIT 1", $post_type ) );
				if ( ! $exists ) {
					self::create_default_package( $post_type );
				}
				$package_id = geodir_pricing_default_package_id( $post_type, true );
				$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET `package_id` = %d WHERE `package_id` = %d OR `package_id` IS NULL", array( $package_id, 0 ) ) );
			}
		}
	}

	public static function create_default_package( $post_type = 'gd_place' ) {
		global $wpdb;

		$data = self::get_default_package_data( $post_type );

		$data = GeoDir_Pricing_Package::prepare_data_for_save( $data );

		return GeoDir_Pricing_Package::insert_package( $data );
	}

	public static function get_default_package_data( $post_type = 'gd_place' ) {
		$data = array(
			'post_type' => $post_type,
			'name' => wp_sprintf( __( 'Free %s', 'geodir_pricing' ), geodir_post_type_singular_name( $post_type ) ),
			'amount' => 0,
			'is_default' => 1,
			'display_order' => GeoDir_Pricing_Package::default_sort_order( $post_type ),
			'post_status' => 'default',
			'status' => 1,
			'exclude_field' => array()
		);

		return apply_filters( 'geodir_pricing_post_type_default_package_data', $data, $post_type );
	}

}
