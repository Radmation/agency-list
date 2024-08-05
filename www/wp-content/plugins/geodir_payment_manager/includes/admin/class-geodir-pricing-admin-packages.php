<?php
/**
 * Pricing Manager Admin Packages class.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Admin_Packages class.
 */
class GeoDir_Pricing_Admin_Packages {

	/**
	 * Initialize the packages admin actions.
	 */
	public function __construct() {
		$this->actions();
		$this->notices();
	}

	/**
	 * Check if is packages settings page.
	 * @return bool
	 */
	private function is_settings_page() {
		return isset( $_GET['page'] )
			&& 'gd-settings' === $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'pricing' === $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'packages' === $_GET['section'];
	}

	public static function current_action() {
		if ( ! empty( $_GET['action'] ) && $_GET['action'] != -1 ) {
			return $_GET['action'];
		} else if ( ! empty( $_GET['action2'] ) ) {
			return $_GET['action2'];
		}
		return NULL;
	}

	/**
	 * Cities admin actions.
	 */
	public function actions() {
		if ( $this->is_settings_page() ) {
			// Bulk actions
			if ( $this->current_action() && ! empty( $_GET['package'] ) ) {
				$this->bulk_actions();
			}
		}
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		if ( ! ( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'geodirectory-settings' ) ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'geodir_pricing' ) );
		}

		$ids = array_map( 'absint', (array) $_GET['package'] );

		if ( 'delete' == $this->current_action() ) {
			$count = 0;
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( GeoDir_Pricing_Package::delete( (int) $id ) ) {
						$count++;
					}
				}
			}

			wp_redirect( esc_url( add_query_arg( array( 'removed' => $count ), admin_url( 'admin.php?page=gd-settings&tab=pricing&section=packages' ) ) ) );
			exit;
		}
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['removed'] ) ) {
			if ( ! empty( $_GET['removed'] ) ) {
				$count = absint( $_GET['removed'] );
				$message = wp_sprintf( _n( 'Item deleted successfully.', '%d items deleted successfully.', $count, 'geodir_pricing' ), $count );
			} else {
				$message = __( 'No item deleted.', 'geodir_pricing' );
			}
			GeoDir_Admin_Settings::add_message( $message );
		}
	}
}

new GeoDir_Pricing_Admin_Packages();
