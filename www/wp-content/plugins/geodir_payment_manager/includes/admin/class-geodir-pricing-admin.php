<?php
/**
 * Pricing Manager Admin.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Admin class.
 */
class GeoDir_Pricing_Admin {
    
    /**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 10 );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_filter( 'geodir_get_settings_pages', array( $this, 'load_settings_page' ), 14, 1 );
		add_action( 'geodir_debug_tools' , 'geodir_pricing_diagnostic_tools', 10 );
		add_action( 'geodir_post_type_saved', 'geodir_pricing_post_type_saved', 10, 3 );

		add_filter( 'geodir_uninstall_options', 'geodir_pricing_uninstall_settings', 10, 1 );
		add_filter( 'geodir_diagnose_multisite_conversion', 'geodir_pricing_diagnose_multisite_conversion', 10, 1 );
		add_filter( 'geodir_gd_options_for_translation', 'geodir_pricing_settings_to_translation', 10, 1 );
		add_filter( 'geodir_load_db_language', 'geodir_pricing_load_db_text_translation', 10, 1 );

		// Fields
		add_filter( 'geodir_pricing_package_skip_exclude_field_post_images', '__return_false', 10, 3 );
		add_filter( 'geodir_cfa_after_show_in_field', 'geodir_pricing_cfa_field_packages', 10, 2 );
		add_filter( 'geodir_cpt_cf_sanatize_custom_field', 'geodir_pricing_cpt_cf_sanitize_custom_field', 10, 2 );
		add_filter( 'geodir_after_custom_fields_updated', 'geodir_pricing_onsave_custom_field', 10, 1 );
		add_filter( 'geodir_default_custom_fields', 'geodir_pricing_filter_default_fields', 100, 3 );
		add_filter( 'geodir_db_cpt_default_columns', 'geodir_pricing_cpt_db_columns', 10, 3 );
		add_filter( 'geodir_event_installed', 'geodir_pricing_event_check_default_package', 10 );
		add_action( 'geodir_clear_version_numbers' ,array( $this, 'clear_version_number'));

		// Import / Export
		add_filter( 'geodir_import_validate_post', array( __CLASS__, 'import_validate_post' ), 11, 2 );

		self::post_type_filters();
	}

	/**
	 * Deletes the version number from the DB so install functions will run again.
	 */
	public function clear_version_number(){
		delete_option( 'geodir_pricing_version' );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once( GEODIR_PRICING_PLUGIN_DIR . 'includes/admin/admin-functions.php' );
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Nonced plugin install redirects (whitelisted)
		if ( ! empty( $_GET['geodir-pricing-install-redirect'] ) ) {
			$plugin_slug = geodir_clean( $_GET['geodir-pricing-install-redirect'] );

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );

			wp_safe_redirect( $url );
			exit;
		}

		// Setup wizard redirect
		if ( get_transient( '_geodir_pricing_activation_redirect' ) ) {
			delete_transient( '_geodir_pricing_activation_redirect' );
		}
	}

	public static function load_settings_page( $settings_pages ) {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : 'gd_place';

		if (  ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type . '-settings' ) {
			$settings_pages[] = include( GEODIR_PRICING_PLUGIN_DIR . 'includes/admin/settings/class-geodir-pricing-settings-cpt-package.php' );
		} else {
			$settings_pages[] = include( GEODIR_PRICING_PLUGIN_DIR . 'includes/admin/settings/class-geodir-pricing-settings-pricing.php' );
		}

		return $settings_pages;
	}

	/**
	 * Enqueue styles.
	 */
	public static function admin_styles() {
		global $wp_query, $post, $pagenow;

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$gd_screen_id = sanitize_title( __( 'GeoDirectory', 'geodirectory' ) );
		$post_type    = isset($_REQUEST['post_type']) && $_REQUEST['post_type'] ? sanitize_text_field($_REQUEST['post_type']) : '';
		$page 		  = ! empty( $_GET['page'] ) ? $_GET['page'] : '';

		// Register styles
		wp_register_style( 'geodir-pricing-admin', GEODIR_PRICING_PLUGIN_URL . '/assets/css/admin.css', array( 'geodir-admin-css' ), GEODIR_PRICING_VERSION );

		// Admin styles for GD pages only
		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			wp_enqueue_style( 'geodir-pricing-admin' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public static function admin_scripts() {
		global $wp_query, $post, $pagenow;

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$gd_screen_id = sanitize_title( __( 'GeoDirectory', 'geodirectory' ) );
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$post_type    = isset($_REQUEST['post_type']) && $_REQUEST['post_type'] ? sanitize_text_field($_REQUEST['post_type']) : '';
		$page 		  = ! empty( $_GET['page'] ) ? $_GET['page'] : '';

		// Register scripts
		wp_register_script( 'geodir-pricing', GEODIR_PRICING_PLUGIN_URL . '/assets/js/common' . $suffix . '.js', array( 'jquery', 'geodir-admin-script' ), GEODIR_PRICING_VERSION );
		wp_register_script( 'geodir-pricing-admin', GEODIR_PRICING_PLUGIN_URL . '/assets/js/admin' . $suffix . '.js', array( 'jquery', 'geodir-pricing' ), GEODIR_PRICING_VERSION );

		// Admin scripts for GD pages only
		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			wp_enqueue_script( 'geodir-pricing' );
			wp_localize_script( 'geodir-pricing', 'geodir_pricing_params', geodir_pricing_params() );

			wp_enqueue_script( 'geodir-pricing-admin' );
			wp_localize_script( 'geodir-pricing-admin', 'geodir_pricing_admin_params', geodir_pricing_admin_params() );
		}
	}

	public static function post_type_filters() {
		if ( $post_types = geodir_get_posttypes() ) {
			foreach ( $post_types as $post_type ) {
				add_filter( "manage_edit-{$post_type}_columns", array( __CLASS__, 'posts_columns' ), 999, 1 );
				add_filter( "manage_edit-{$post_type}_sortable_columns", array( __CLASS__, 'posts_sortable_columns' ), 999, 1 );
				add_action( "manage_{$post_type}_posts_custom_column", array( __CLASS__, 'posts_custom_column' ), 999, 2 );
			}
		}
	}

	public static function posts_columns( $columns = array() ) {
		$columns['package_id'] = __( 'Package', 'geodir_pricing' );
		$columns['expire_date'] = __( 'Expires', 'geodir_pricing' );

		return $columns;
	}

	public static function posts_sortable_columns( $columns = array() ) {
		//$columns['package_id'] = 'package_id';
		//$columns['expire_date'] = array( 'expire_date', true );
		return $columns;
	}

	public static function posts_custom_column( $column, $post_id ) {
		if ( $column == 'package_id' ) {
			$package_id = geodir_get_post_meta( $post_id, 'package_id', true );

			if ( ! empty( $package_id ) && ( $name = geodir_pricing_package_name( (int) $package_id ) ) ) {
				$post_type = get_post_type( $post_id );
				$link = admin_url( 'edit.php?post_type=' . $post_type . '&page=' . $post_type . '-settings&tab=cpt-package&section=add-package&id=' . $package_id );

				$name = '<abbr class="meta package_name"><a title="' . esc_attr( $name ) . '" href="' . esc_url( $link ) . '">' . $name . '</a></abbr>';
				$name .= '<br><small>' . wp_sprintf( __( 'ID: %d', 'geodir_pricing' ), $package_id ) . '</small>';

				echo $name;
			} else {
				echo __( 'n/a', 'geodir_pricing' );
			}
		} elseif ( $column == 'expire_date' ) {
			$expire_date = geodir_get_post_meta( $post_id, 'expire_date', true );

			if ( ! geodir_pricing_date_never_expire( $expire_date ) ) {
				$current_date = date_i18n( 'Y-m-d' );
				$date_diff_text = '';
				$expire_class = 'expire_left';

				if ( $expire_date == $current_date ) {
					$expire_class = 'expire_today';
				} elseif ( strtotime( $expire_date ) < strtotime( $current_date ) ) {
					$expire_class = 'expire_over';
				}

				$date_diff_text = '(' . geodir_pricing_time_diff( $expire_date, $current_date ) . ')';
				$expire_date .= '<br />';
			} else {
				$expire_date = '';
				$date_diff_text = __( 'Never', 'geodir_pricing' );
				$expire_class = 'expire_never';
			}

			echo $expire_date . '<span class="' . esc_attr( $expire_class ) . '">' . $date_diff_text . '</span>';
		}
	}

	public static function import_validate_post( $post_info, $row ) {
		// Connvert date in mysql format
		if ( ! empty( $post_info['expire_date'] ) && strpos( $post_info['expire_date'], '/' ) !== false ) {
			$post_info['expire_date'] = geodir_date( $post_info['expire_date'], 'Y-m-d' );
		}

		return $post_info;
	}
}
