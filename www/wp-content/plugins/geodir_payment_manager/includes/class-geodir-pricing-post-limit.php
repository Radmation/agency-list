<?php
/**
 * Pricing Manager posts limit class.
 *
 * @since 2.6.1.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Post_Limit class.
 */
class GeoDir_Pricing_Post_Limit {

	/**
	 * Setup.
	 */
	public static function init() {
		add_filter( 'geodir_cpt_posts_limits', array( __CLASS__, 'cpt_posts_limits' ), 10, 2 );
		add_filter( 'geodir_check_user_posts_limits', array( __CLASS__, 'check_user_posts_limits' ), 10, 5 );
		add_filter( 'geodir_count_user_cpt_posts_where', array( __CLASS__, 'count_user_cpt_posts_where' ), 10, 3 );
		add_filter( 'geodir_validate_auto_save_post_data', array( __CLASS__, 'validate_auto_save_post_data' ), 11, 4 );

		// Hide packages from list
		add_filter( 'geodir_pricing_get_packages', array( __CLASS__, 'filter_packages' ), 10, 2 );
	}

	public static function cpt_posts_limits( $limits, $params ) {
		if ( ! empty( $params['package_id'] ) ) {
			$limits['package'] = (int) geodir_pricing_package_max_posts( (int) $params['package_id'] );
		}

		return $limits;
	}

	public static function check_user_posts_limits( $can_add, $limits, $params, $args, $wp_error = false ) {
		if ( geodir_is_page( 'add-listing' ) && ! empty( $params['group'] ) && $params['group'] == 'add' ) {
			return $can_add; // On add listing page checks on auto save.
		}

		if ( ! is_wp_error( $can_add ) && $can_add === true && ! empty( $params['package_id'] ) && ! empty( $limits['package'] ) ) {
			$message = '';
			$posts_count = 0;
			$posts_limit = (int) $limits['package'];

			if ( $posts_limit > 0 ) {
				$params['filter_package'] = true;
				$posts_count = (int) GeoDir_Post_Limit::count_user_cpt_posts( $params );

				// Limit exceed.
				if ( $posts_limit <= $posts_count ) {
					$can_add = false;
					$message = wp_sprintf( __( 'You have reached the limit of %s you can add at this time with package "%s".', 'geodir_pricing' ), geodir_strtolower( geodir_post_type_name( $params['post_type'], true ) ), strip_tags( geodir_pricing_package_title( (int) $params['package_id'] ) ) );
				}
			} else if ( $posts_limit < 0 ) {
				// Disabled from package
				$can_add = false;
				$message = wp_sprintf( __( 'You are not allowed to add %s with package "%s".', 'geodir_pricing' ), geodir_strtolower( geodir_post_type_singular_name( $params['post_type'], true ) ), strip_tags( geodir_pricing_package_title( (int) $params['package_id'] ) ) );
			}

			if ( $can_add === false && $wp_error && $message ) {
				$message = apply_filters( 'geodir_pricing_user_posts_limit_message', $message, $posts_limit, $posts_count, $limits, $params );

				if ( $message ) {
					if ( geodir_is_page( 'add-listing' ) ) {
						$message .= ' ' . __( 'Choose other package to add new listing.', 'geodir_pricing' );
					}

					$can_add = new WP_Error( 'geodir_user_posts_limit', $message );
				}
			}
		}

		return $can_add;
	}

	public static function count_user_cpt_posts_where( $where, $query_args, $args ) {
		global $wpdb;

		if ( ! empty( $query_args['package_id'] ) && ! empty( $query_args['filter_package'] ) ) {
			$where .= $wpdb->prepare( " AND `pd`.`package_id` = %d", absint( $query_args['package_id'] ) );
		}

		$current_post = 0;
		if ( ! empty( $args['current_post'] ) ) {
			$current_post = absint( $args['current_post'] );
		} else if ( ! empty( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'geodir_claim_post_form', 'geodir_ninja_forms' ) ) && ! empty( $_REQUEST['p'] ) && geodir_listing_belong_to_current_user( absint( $_REQUEST['p'] ), false ) ) {
			$current_post = absint( $_REQUEST['p'] );
		} else if ( ! empty( $_REQUEST['pid'] ) && geodir_is_page( 'edit-listing' ) ) {
			$current_post = absint( $_REQUEST['pid'] );
		}

		if ( $current_post ) {
			$where .= $wpdb->prepare( " AND `pd`.`post_id` != %d", $current_post );
		}

		return $where;
	}

	public static function validate_auto_save_post_data( $valid, $post_data, $update = false, $doing_autosave = true ) {
		if ( ! is_wp_error( $valid ) && ! empty( $post_data['package_id'] ) && ( geodir_pricing_is_upgrade( $post_data ) || geodir_pricing_is_upgrade( $post_data ) ) ) {
			$post_ID = absint( $post_data['ID'] );
			$post_type = $post_data['post_type'];

			if ( $post_type == 'revision' && ! empty( $post_data['post_parent'] ) ) {
				$post_ID = absint( $post_data['post_parent'] );
				$post_type = get_post_type( $post_data['post_parent'] );
			}

			$args = array( 
				'post_type' => $post_type,
				'post_author' => null,
				'package_id' => (int) $post_data['package_id'],
				'current_post' => $post_ID,
			);

			$can_add_post = GeoDir_Post_Limit::user_can_add_post( $args, true );

			if ( is_wp_error( $can_add_post ) ) {
				$valid = $can_add_post;
			}
		}

		return $valid;
	}

	public static function filter_packages( $packages, $args ) {
		if ( empty( $args['is_admin'] ) && ! empty( $args['group'] ) && ( $args['group'] == 'add' || $args['group'] == 'claim' ) ) {
			$_packages = array();

			foreach ( $packages as $k => $package ) {
				if ( ( ! empty( $args['must_include'] ) && (int) $package->id == (int) $args['must_include'] ) || ( ! empty( $args['current_package'] ) && (int) $package->id == (int) $args['current_package'] ) ) {
					$_packages[] = $package;

					continue;
				}

				$_args = array( 
					'post_type' => $package->post_type,
					'package_id' => (int) $package->id,
				);

				$can_add_post = GeoDir_Post_Limit::user_can_add_post( $_args );

				if ( $can_add_post ) {
					$_packages[] = $package;
				}
			}

			$packages = $_packages;
		}

		return $packages;
	}
}
