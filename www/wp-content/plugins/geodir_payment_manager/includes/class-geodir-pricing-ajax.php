<?php
/**
 * Pricing Manager AJAX class.
 *
 * Pricing Manager AJAX Event Handler.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_AJAX class.
 */
class GeoDir_Pricing_AJAX {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		// geodirectory_EVENT => nopriv
		$ajax_events = array(
			'pricing_delete_package' => false,
			'pricing_set_default' => false,
			'pricing_sync_package' => false,
			'pricing_create_invoice' => false,
			'pricing_link_invoice' => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}

			// GeoDir AJAX can be used for frontend ajax requests.
			add_action( 'geodir_ajax_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	public static function pricing_delete_package() {
		$package_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		check_ajax_referer( 'geodir-delete-package-' . $package_id, 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			if ( ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to delete package.', 'geodir_pricing' ) );
			}

			$package = $package_id ? GeoDir_Pricing_Package::get_package( $package_id ) : NULL;
			if ( empty( $package ) ) {
				throw new Exception( __( 'Requested package does not exists.', 'geodir_pricing' ) );
			}

			if ( ! empty( $package->is_default ) ) {
				throw new Exception( __( 'Default package can not be deleted!', 'geodir_pricing' ) );
			}

			if ( GeoDir_Pricing_Package::delete( $package ) ) {
				$message = __( 'Package deleted successfully.', 'geodir_pricing' );
			} else {
				throw new Exception( __( 'Fail to delete package!', 'geodir_pricing' ) );
			}

			$data = array( 'message' => $message );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function pricing_set_default() {
		$package_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		check_ajax_referer( 'geodir-set-default-' . $package_id, 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$package = $package_id ? GeoDir_Pricing_Package::get_package( $package_id ) : NULL;
			if ( empty( $package ) ) {
				throw new Exception( __( 'Requested package does not exists!', 'geodir_pricing' ) );
			}

			GeoDir_Pricing_Package::set_default( $package_id );

			$message = __( 'Default package set successfully.', 'geodir_pricing' );

			$data = array( 'message' => $message );
			wp_send_json_success( $data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function pricing_sync_package() {
		$package_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		check_ajax_referer( 'geodir-sync-package-' . $package_id, 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		try {
			$package = $package_id ? GeoDir_Pricing_Package::get_package( $package_id ) : NULL;
			if ( empty( $package ) ) {
				throw new Exception( __( 'Requested package does not exists!', 'geodir_pricing' ) );
			}

			$return = geodir_pricing_sync_package_to_cart_item( $package_id );

			wp_send_json_success( array() );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function pricing_create_invoice() {
		$post_id = ! empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		check_ajax_referer( 'create-invoice-' . $post_id, 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( has_action( 'geodir_pricing_create_post_invoice' ) ) {
			try {
				do_action( 'geodir_pricing_create_post_invoice', $post_id );
			} catch ( Exception $e ) {
				wp_send_json_error( array( 'message' => $e->getMessage() ) );
			}
		} else {
			wp_send_json_success( array() );
		}
	}

	/**
	 * Handle link invoice request.
	 *
	 * @since 2.6.1.2
	 *
	 * @return string Json response.
	 */
	public static function pricing_link_invoice() {
		$post_id = ! empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$invoice_id = ! empty( $_POST['invoice_id'] ) ? sanitize_text_field( $_POST['invoice_id'] ) : 0;

		check_ajax_referer( 'link-invoice-' . $post_id, 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( has_action( 'geodir_pricing_link_post_invoice' ) ) {
			try {
				do_action( 'geodir_pricing_link_post_invoice', $post_id, $invoice_id );
			} catch ( Exception $e ) {
				wp_send_json_error( array( 'message' => $e->getMessage() ) );
			}
		} else {
			wp_send_json_success( array() );
		}
	}
}