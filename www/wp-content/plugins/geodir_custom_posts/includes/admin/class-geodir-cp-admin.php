<?php
/**
 * GeoDirectory Custom Post Types Admin
 *
 * @class    GeoDir_CP_Admin
 * @author   AyeCode
 * @category Admin
 * @package  GeoDir_Custom_Posts/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GeoDir_CP_Admin class.
 */
class GeoDir_CP_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $pagenow;

		$post_action = ! empty( $_POST['action'] ) ? $_POST['action'] : '';

		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_filter( 'geodir_get_settings_pages', array( $this, 'load_settings_page' ), 1, 1 );
		add_filter( 'geodir_cfa_skip_item_output_address', 'geodir_cp_skip_address_field_output', 10, 4 );
		add_filter( 'admin_footer', 'geodir_cp_admin_footer' );
		add_filter( 'geodir_uninstall_options', 'geodir_cp_uninstall_settings', 10, 1 );
		add_action( 'geodir_clear_version_numbers' ,array( $this, 'clear_version_number'));

		// add tab items
		add_filter('geodir_cpt_settings_tabs_custom_fields',array( $this, 'add_tab_items'),10,2);


		self::post_type_filters();
	}

	/**
	 * Adds a tab item to display listings that are linking to the post.
	 * 
	 * @param $fields
	 * @param $post_type
	 *
	 * @return array
	 */
	public function add_tab_items( $fields, $post_type ) {
		$post_types = GeoDir_CP_Link_Posts::linked_from_post_types( $post_type );

		// Link from
		if ( ! empty( $post_types ) ) {
			foreach( $post_types as $pt ) {
				$sort_by = geodir_get_posts_default_sort( $pt );
				if ( empty( $sort_by ) ) {
					$sort_by = 'latest';
				}

				// shortcode
				$fields[] = array(
					'tab_type'   => 'shortcode',
					'tab_name'   => sprintf( __( 'Linked from: %s', 'geodir_custom_posts' ), $pt ),
					'tab_icon'   => 'fas fa-link',
					'tab_key'    => '',
					'tab_content'=> '[gd_listings post_type="' . $pt . '" linked_posts="from" sort_by="' . esc_attr( $sort_by ) . '" post_limit=5 layout=2 mb=3]'
				);
			}
		}

		$post_types = self::get_link_to_cpts( $post_type );

		// Link to
		if ( ! empty( $post_types ) ) {
			foreach( $post_types as $pt ) {
				$sort_by = geodir_get_posts_default_sort( $pt );
				if ( empty( $sort_by ) ) {
					$sort_by = 'latest';
				}

				// shortcode
				$fields[] = array(
					'tab_type'   => 'shortcode',
					'tab_name'   => sprintf( __( 'Linked to: %s', 'geodir_custom_posts' ), $pt ),
					'tab_icon'   => 'fas fa-link',
					'tab_key'    => '',
					'tab_content'=> '[gd_listings post_type="' . $pt . '" linked_posts="to" sort_by="' . esc_attr( $sort_by ) . '" post_limit=1 layout=1 mb=3]'
				);
			}
		}

		return $fields;
	}

	/**
	 * Get the CPTs that the CPT can link to.
	 *
	 * @param $post_type
	 *
	 * @return array
	 */
	public function get_link_to_cpts( $post_type ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare("SELECT htmlvar_name FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE field_type='link_posts' AND post_type = %s", $post_type ) );

		$cpts = array();
		if ( ! empty( $results ) ) {
			foreach( $results as $cpt ) {
				if ( geodir_is_gd_post_type( $cpt->htmlvar_name ) ) {
					$cpts[] = $cpt->htmlvar_name;
				}
			}
		}

		return $cpts;
	}

	/**
	 * Deletes the version number from the DB so install functions will run again.
	 */
	public function clear_version_number(){
		delete_option( 'geodir_cp_version' );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once( GEODIR_CP_PLUGIN_DIR . 'includes/admin/class-geodir-cp-admin-assets.php' );
		include_once( GEODIR_CP_PLUGIN_DIR . 'includes/admin/class-geodir-cp-admin-import-export.php' );
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Nonced plugin install redirects (whitelisted)
		if ( ! empty( $_GET['geodir-cp-install-redirect'] ) ) {
			$plugin_slug = geodir_clean( $_GET['geodir-cp-install-redirect'] );

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );

			wp_safe_redirect( $url );
			exit;
		}

		// Setup wizard redirect
		if ( get_transient( '_geodir_cp_activation_redirect' ) ) {
			delete_transient( '_geodir_cp_activation_redirect' );
		}
	}

	public static function load_settings_page( $settings_pages ) {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_title( $_REQUEST['post_type'] ) : 'gd_place';
		if ( !( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type.'-settings' ) ) {
			$settings_pages[] = include( GEODIR_CP_PLUGIN_DIR . 'includes/admin/settings/class-geodir-cp-settings-cpts.php' );
		}

		return $settings_pages;
	}

	public static function allow_delete_cpt( $post_type ) {
		$return = apply_filters( 'geodir_cp_allow_delete_cpt', (bool)( $post_type != 'gd_place' && $post_type != 'gd_event' ), $post_type );
		return $return;
	}

	public static function post_type_filters() {
		if ( $post_types = geodir_get_posttypes() ) {
			foreach ( $post_types as $post_type ) {
				add_filter( "manage_edit-{$post_type}_columns", array( __CLASS__, 'post_type_columns' ), 101 );
			}
		}
	}

	public static function post_type_columns( $columns = array() ) {
		if ( isset( $columns['location'] ) ) {
			if ( ! GeoDir_Post_types::supports( self::current_post_type(), 'location' ) ) {
				unset( $columns['location'] );
			}
		}
		return $columns;
	}

	public static function current_post_type() {
		global $post, $typenow, $current_screen;

		$post_type = null;
		if ( isset( $_REQUEST['post_type'] ) ) {
			$post_type = sanitize_key( $_REQUEST['post_type'] );
		} elseif ( isset( $_REQUEST['post'] ) && get_post_type( $_REQUEST['post'] ) ) {
			$post_type = get_post_type( $_REQUEST['post'] );
		} elseif ( $post && isset( $post->post_type ) ) {
			$post_type = $post->post_type;
		} elseif ( $typenow ) {
			$post_type = $typenow;
		} elseif ( $current_screen && isset( $current_screen->post_type ) ) {
			$post_type = $current_screen->post_type;
		}

		return $post_type;
	}
}