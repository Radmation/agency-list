<?php
/**
 * Pricing Manager Package class.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Package class.
 */
class GeoDir_Pricing_Package {

	public static function init() {
		if ( is_admin() ) {
			add_action( 'geodir_pricing_package_updated', array( __CLASS__, 'update_is_default' ), 10, 3 );
			add_action( 'geodir_pricing_added_package_meta', array( __CLASS__, 'onsave_package_meta' ), 100, 4 );
			add_action( 'geodir_pricing_updated_package_meta', array( __CLASS__, 'onsave_package_meta' ), 100, 4 );
		}

		add_filter( 'geodir_check_field_visibility', array( __CLASS__, 'check_field_visibility' ), 10, 4 );
		add_filter( 'geodir_get_post_package', array( __CLASS__, 'get_post_package' ), 1, 3 );
	}

	public static function get_instance( $package_id ) {
		global $wpdb;

		$package_id = (int) $package_id;
		if ( ! $package_id ) {
			return false;
		}

		$_package = geodir_cache_get( $package_id, 'geodir_pricing_packages' );

		if ( ! $_package ) {
			$_package = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_PRICING_PACKAGES_TABLE . " WHERE id = %d LIMIT 1", $package_id ) );

			if ( ! $_package ) {
				return false;
			}

			$_package = self::sanitize( $_package );
			geodir_cache_set( $_package->id, $_package, 'geodir_pricing_packages' );
		}

		foreach ( get_object_vars( $_package ) as $key => $value ) {
			$_package->{$key} = $value;
		}

		return $_package;
	}
		
	public static function get_package( $package = null, $output = OBJECT, $filter = 'raw' ) {
		if ( is_object( $package ) ) {
			if ( empty( $package->filter ) ) {
				$_package = self::sanitize( $package, 'raw' );
			} elseif ( 'raw' == $package->filter ) {
				$_package = self::sanitize( $package, 'raw' );
			} else {
				$_package = self::get_instance( $package );
			}
		} else {
			$_package = self::get_instance( $package );
		}

		$_package = self::get_instance( $package );

		if ( ! $_package ) {
			return null;
		}

		$_package = self::filter( $_package, $filter );

		if ( $output == ARRAY_A ) {
			return self::to_array( $_package );
		} else if ( $output == ARRAY_N ) {
			return array_values( self::to_array( $_package ) );
		}

		return $_package;
	}

	public static function to_array( $package ) {
		$_package = array();

		foreach ( get_object_vars( $package ) as $key => $value ) {
			$_package[$key] = $value;
		}

		return $_package;
	}

	public static function sanitize( $package, $context = 'display' ) {
		if ( is_object( $package ) ) {
			if ( ! isset( $package->id ) )
				$package->id = 0;

			foreach ( array_keys( get_object_vars( $package ) ) as $field )
				$package->$field = self::sanitize_field( $field, $package->$field, $package->id, $context );

			$package->filter = $context;
		} elseif ( is_array( $package ) ) {
			if ( isset( $package['filter'] ) && $context == $package['filter'] )
				return $package;

			if ( ! isset( $package['id'] ) )
				$package['id'] = 0;

			foreach ( array_keys( $package ) as $field )
				$package[$field] = self::sanitize_field( $field, $package[$field], $package['id'], $context );

			$package['filter'] = $context;
		}
		return $package;
	}

	public static function prepare_data_for_save( $data ) {
		$package_id = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;

		if ( ! empty( $package_id ) ) {
			$package = GeoDir_Pricing_Package::get_package( absint( $package_id ), ARRAY_A, 'db' );

			if ( ! empty( $package ) && ( $meta = GeoDir_Pricing_Package::get_metas( absint( $package_id ), true, 'db' ) ) ) {
				$meta = GeoDir_Pricing_Package::get_metas( absint( $package_id ), true, 'db' );

				$package = array_merge( $package, $meta );
			}
		} else {
			$package = array();
		}

		$package_data = array();

		// Details
		$package_data['id'] = $package_id;
		if ( ! empty( $data['post_type'] ) ) {
			$package_data['post_type'] = geodir_clean( $data['post_type'] );
		} else if ( ! empty( $package['post_type'] ) ) {
			$package_data['post_type'] = $package['post_type'];
		} else {
			$package_data['post_type'] = 'gd_place';
		}
		if ( ! empty( $data['name'] ) ) {
			$package_data['name'] = geodir_clean( $data['name'] );
		} else if ( isset( $package['name'] ) ) {
			$package_data['name'] = $package['name'];
		} else {
			$package_data['name'] = '';
		}
		if ( ! empty( $data['title'] ) ) {
			$package_data['title'] = wp_kses_post( $data['title'] );
		} else if ( isset( $package['title'] ) ) {
			$package_data['title'] = $package['title'];
		} else {
			$package_data['title'] = '';
		}
		if ( isset( $data['description'] ) ) {
			$package_data['description'] = wp_kses_post( $data['description'] );
		} else if ( isset( $package['description'] ) ) {
			$package_data['description'] = $package['description'];
		} else {
			$package_data['description'] = '';
		}
		if ( isset( $data['fa_icon'] ) ) {
			$package_data['fa_icon'] = geodir_clean( $data['fa_icon'] );
		} else if ( isset( $package['fa_icon'] ) ) {
			$package_data['fa_icon'] = $package['fa_icon'];
		} else {
			$package_data['fa_icon'] = '';
		}
		if ( isset( $data['amount'] ) ) {
			$package_data['amount'] = geodir_pricing_format_decimal( $data['amount'] );
		} else if ( isset( $package['amount'] ) ) {
			$package_data['amount'] = $package['amount'];
		} else {
			$package_data['amount'] = '0';
		}
		if ( isset( $data['time_interval'] ) ) {
			$package_data['time_interval'] = absint( $data['time_interval'] );
		} else if ( isset( $package['time_interval'] ) ) {
			$package_data['time_interval'] = $package['time_interval'];
		} else {
			$package_data['time_interval'] = '0';
		}
		if ( ! empty( $package_data['time_interval'] ) ) {
			if ( ! empty( $data['time_unit'] ) ) {
				$package_data['time_unit'] = geodir_clean( $data['time_unit'] );
			} else if ( ! empty( $package['time_unit'] ) ) {
				$package_data['time_unit'] = $package['time_unit'];
			} else {
				$package_data['time_unit'] = 'M';
			}
		} else {
			$package_data['time_unit'] = '';
		}
		if ( isset( $data['recurring'] ) ) {
			$package_data['recurring'] = ! empty( $data['recurring'] ) ? 1 : 0;
		} else if ( isset( $package['recurring'] ) ) {
			$package_data['recurring'] = $package['recurring'];
		} else {
			$package_data['recurring'] = 0;
		}
		if ( isset( $data['recurring_limit'] ) && $package_data['recurring'] ) {
			$package_data['recurring_limit'] = absint( $data['recurring_limit'] );
		} else if ( isset( $package['recurring_limit'] ) && $package_data['recurring'] ) {
			$package_data['recurring_limit'] = $package['recurring_limit'];
		} else {
			$package_data['recurring_limit'] = 0;
		}
		if ( isset( $data['trial'] ) ) {
			$package_data['trial'] = ! empty( $data['trial'] ) && $package_data['recurring'] ? 1 : 0;
		} else if ( isset( $package['trial'] ) ) {
			$package_data['trial'] = $package['trial'];
		} else {
			$package_data['trial'] = 0;
		}
		if ( ! empty( $package_data['trial'] ) ) {
			if ( isset( $data['trial_amount'] ) ) {
				$package_data['trial_amount'] = geodir_pricing_format_decimal( $data['trial_amount'] );
			} else if ( isset( $package['trial_amount'] ) ) {
				$package_data['trial_amount'] = $package['trial_amount'];
			} else {
				$package_data['trial_amount'] = '0';
			}
			if ( isset( $data['trial_interval'] ) ) {
				$package_data['trial_interval'] = absint( $data['trial_interval'] );
			} else if ( isset( $package['trial_interval'] ) ) {
				$package_data['trial_interval'] = $package['trial_interval'];
			} else {
				$package_data['trial_interval'] = 0;
			}
		} else {
			$package_data['trial_amount'] = '';
			$package_data['trial_interval'] = 0;
		}
		if ( ! empty( $package_data['trial_interval'] ) ) {
			if ( ! empty( $data['trial_unit'] ) ) {
				$package_data['trial_unit'] = geodir_clean( $data['trial_unit'] );
			} else if ( ! empty( $package['trial_unit'] ) ) {
				$package_data['trial_unit'] = $package['trial_unit'];
			} else {
				$package_data['trial_unit'] = 'M';
			}
		} else {
			$package_data['trial_unit'] = '';
		}

		// Status
		if ( isset( $data['is_default'] ) ) {
			$package_data['is_default'] = ! empty( $data['is_default'] ) ? 1 : 0;
		} else if ( isset( $package['is_default'] ) ) {
			$package_data['is_default'] = $package['is_default'];
		} else {
			$package_data['is_default'] = 0;
		}
		if ( isset( $data['display_order'] ) ) {
			$package_data['display_order'] = $data['display_order'] != '' ? absint( $data['display_order'] ) : GeoDir_Pricing_Package::default_sort_order( $package_data['post_type'] );
		} else if ( isset( $package['display_order'] ) ) {
			$package_data['display_order'] = $package['display_order'];
		} else {
			$package_data['display_order'] = 0;
		}
		if ( isset( $data['downgrade_pkg'] ) ) {
			$package_data['downgrade_pkg'] = absint( $data['downgrade_pkg'] );
		} else if ( isset( $package['downgrade_pkg'] ) ) {
			$package_data['downgrade_pkg'] = $package['downgrade_pkg'];
		} else {
			$package_data['downgrade_pkg'] = 0;
		}
		if ( ! empty( $data['post_status'] ) ) {
			$package_data['post_status'] = geodir_clean( $data['post_status'] );
		} else if ( isset( $package['post_status'] ) ) {
			$package_data['post_status'] = $package['post_status'];
		} else {
			$package_data['post_status'] = 'default';
		}
		if ( isset( $data['status'] ) ) {
			$package_data['status'] = ! empty( $data['status'] ) ? 1 : 0;
		} else if ( isset( $package['status'] ) ) {
			$package_data['status'] = $package['status'];
		} else {
			$package_data['status'] = 0;
		}

		// Features / Meta
		$meta = array();
		if ( isset( $data['exclude_field'] ) ) {
			$meta['exclude_field'] = geodir_clean( $data['exclude_field'] );
		} else if ( isset( $package['exclude_field'] ) ) {
			$meta['exclude_field'] = $package['exclude_field'];
		} else {
			$meta['exclude_field'] = '';
		}
		if ( isset( $data['exclude_category'] ) ) {
			$meta['exclude_category'] = geodir_clean( $data['exclude_category'] );
		} else if ( isset( $package['exclude_category'] ) ) {
			$meta['exclude_category'] = $package['exclude_category'];
		} else {
			$meta['exclude_category'] = '';
		}
		if ( isset( $data['image_limit'] ) ) {
			$meta['image_limit'] = absint( $data['image_limit'] );
		} else if ( isset( $package['image_limit'] ) ) {
			$meta['image_limit'] = $package['image_limit'];
		} else {
			$meta['image_limit'] = 0;
		}
		if ( isset( $data['category_limit'] ) ) {
			$meta['category_limit'] = absint( $data['category_limit'] );
		} else if ( isset( $package['category_limit'] ) ) {
			$meta['category_limit'] = $package['category_limit'];
		} else {
			$meta['category_limit'] = 0;
		}
		if ( isset( $data['tag_limit'] ) ) {
			$meta['tag_limit'] = absint( $data['tag_limit'] );
		} else if ( isset( $package['tag_limit'] ) ) {
			$meta['tag_limit'] = $package['tag_limit'];
		} else {
			$meta['tag_limit'] = 0;
		}
		if ( isset( $data['use_desc_limit'] ) ) {
			$meta['use_desc_limit'] = ! empty( $data['use_desc_limit'] ) ? 1 : 0;
		} else if ( isset( $package['use_desc_limit'] ) ) {
			$meta['use_desc_limit'] = $package['use_desc_limit'];
		} else {
			$meta['use_desc_limit'] = 0;
		}
		if ( isset( $data['desc_limit'] ) && $meta['use_desc_limit'] ) {
			$meta['desc_limit'] = absint( $data['desc_limit'] );
		} else if ( isset( $package['desc_limit'] ) && $package['use_desc_limit'] ) {
			$meta['desc_limit'] = $package['desc_limit'];
		} else {
			$meta['desc_limit'] = 0;
		}

		// Max posts per user.
		if ( isset( $data['max_posts'] ) ) {
			$meta['max_posts'] = (int) $data['max_posts'];
		} else if ( isset( $package['max_posts'] ) ) {
			$meta['max_posts'] = $package['max_posts'];
		} else {
			$meta['max_posts'] = 0;
		}

		if ( isset( $data['has_upgrades'] ) ) {
			$meta['has_upgrades'] = ! empty( $data['has_upgrades'] ) ? 1 : 0;
		} else if ( isset( $package['has_upgrades'] ) ) {
			$meta['has_upgrades'] = ! empty( $package['has_upgrades'] ) ? 1 : 0;
		} else {
			$meta['has_upgrades'] = 0;
		}

		// Upgrade to packages.
		$meta['upgrade_packages'] = '';

		if ( ! empty( $meta['has_upgrades'] ) ) {
			$upgrade_packages = array();

			if ( isset( $data['upgrade_packages'] ) ) {
				$upgrade_packages = geodir_clean( $data['upgrade_packages'] );
			} else if ( isset( $package['upgrade_packages'] ) ) {
				$upgrade_packages = $package['upgrade_packages'];
			}

			if ( ! empty( $upgrade_packages ) ) {
				$meta['upgrade_packages'] = is_array( $upgrade_packages ) ? implode( ',', $upgrade_packages ) : $upgrade_packages;
			}
		}

		if ( empty( $package_data['is_default'] ) && isset( $data['is_private'] ) ) {
			$meta['is_private'] = ! empty( $data['is_private'] ) ? 1 : 0;
		} else if ( empty( $package_data['is_default'] ) && isset( $package['is_private'] ) ) {
			$meta['is_private'] = $package['is_private'];
		} else {
			$meta['is_private'] = 0;
		}

		if ( isset( $data['disable_editor'] ) ) {
			$meta['disable_editor'] = ! empty( $data['disable_editor'] ) ? 1 : 0;
		} else if ( isset( $package['disable_editor'] ) ) {
			$meta['disable_editor'] = $package['disable_editor'];
		} else {
			$meta['disable_editor'] = 0;
		}

		if ( isset( $data['disable_reviews'] ) ) {
			$meta['disable_reviews'] = ! empty( $data['disable_reviews'] ) ? 1 : 0;
		} else if ( isset( $package['disable_reviews'] ) ) {
			$meta['disable_reviews'] = $package['disable_reviews'];
		} else {
			$meta['disable_reviews'] = 0;
		}

		if ( isset( $data['can_reply_review'] ) ) {
			$meta['can_reply_review'] = geodir_clean( $data['can_reply_review'] );
		} else if ( isset( $package['can_reply_review'] ) ) {
			$meta['can_reply_review'] = $package['can_reply_review'];
		} else {
			$meta['can_reply_review'] = '';
		}

		if ( empty( $package_data['title'] ) ) {
			if ( $package_data['time_interval'] > 0 ) {
				$unit = geodir_pricing_unit_title( $package_data['time_unit'], true, false );
				$title = wp_sprintf( '%s: number of publish %s are %d', $package_data['name'], $unit, $package_data['time_interval'] );
			} else {
				$title = wp_sprintf( '%s: number of publish days are unlimited', $package_data['name'] );
			}

			if ( $package_data['time_interval'] > 0 ) {
				$title .= ' (' . strip_tags( geodir_pricing_price( $package_data['amount'] ) ) . ')';
			} else {
				$title .= ' ' . __( '(Free)', 'geodir_pricing' );
			}
			$package_data['title'] = $title;
		}
		
		$meta['exclude_field'] 			= is_array( $meta['exclude_field'] ) ? implode( ',', $meta['exclude_field'] ) : $meta['exclude_field'];
		$meta['exclude_category'] 			= is_array( $meta['exclude_category'] ) ? implode( ',', $meta['exclude_category'] ) : $meta['exclude_category'];
		
		$package_data['meta'] = $meta;

		$package_data = apply_filters( 'geodir_pricing_process_data_for_save', $package_data, $data, $package );

		foreach ( $package_data as $field => $value ) {
			if ( $field == 'meta' ) {
				if ( ! empty( $value ) ) {
					foreach ( $value as $meta_field => $meta_value ) {
						$value[ $meta_field ] = GeoDir_Pricing_Package::sanitize_field( $meta_field, $meta_value, $package_data['id'], 'db' );
					}
				}
				$package_data[ $field ] = $value;
			} else {
				$package_data[ $field ] = GeoDir_Pricing_Package::sanitize_field( $field, $value, $package_data['id'], 'db' );
			}
		}
		return apply_filters( 'geodir_pricing_prepare_data_for_save', $package_data, $data, $package );
	}

	public static function filter( $package, $filter ) {
		if ( $package->filter == $filter )
			return $package;

		if ( $filter == 'raw' )
			return self::get_instance( $package->id );

		return self::sanitize( $package, $filter );
	}

	public static function sanitize_field( $field, $value, $package_id, $context = 'display' ) {
		$int_fields = array('id', 'display_order');
		if ( in_array( $field, $int_fields ) )
			$value = (int) $value;

		if ( 'raw' == $context )
			return $value;

		if ( 'edit' == $context ) {
			$format_to_edit = array( 'name', 'title', 'description' );

			$value = apply_filters( "geodir_pricing_edit_package_{$field}", $value, $package_id );

			if ( in_array( $field, $format_to_edit ) ) {
				$value = format_to_edit( $value, true );
			} else if ( is_array( $value ) ) {
				$value = array_map( 'esc_attr', $value );
			} else {
				$value = esc_attr( $value );
			}
		} elseif ( 'db' == $context ) {
			$value = apply_filters( "geodir_pricing_pre_package_field_{$field}", $value );

			$value = apply_filters( "geodir_pricing_package_field_{$field}_pre", $value );
		} else {
			$value = apply_filters( "geodir_pricing_package_field_{$field}", $value, $package_id, $context );

			if ( 'attribute' == $context ) {
				$value = esc_attr( $value );
			} elseif ( 'js' == $context ) {
				$value = esc_js( $value );
			}
		}

		return $value;
	}

	public static function get_field( $field, $package = null ) {
		$package = self::get_package( $package );

		if ( !$package ) {
			return '';
		}

		if ( ! isset( $package->{$field} ) ) {
			return '';
		}

		return $package->{$field};
	}

	public static function insert_package( $package_arr, $wp_error = false ) {
		global $wpdb;

		$defaults = array(
			'post_type' => 'gd_place',
			'name' => '',
			'title' => '',
			'description' => '',
			'fa_icon' => '',
			'amount' => '',
			'time_interval' => '',
			'time_unit' => '',
			'recurring' => '',
			'recurring_limit' =>  '',
			'trial' => '',
			'trial_amount' => '',
			'trial_interval' => '',
			'trial_unit' => '',
			'is_default' => '',
			'display_order' => '',
			'downgrade_pkg' => '',
			'post_status' => '',
			'status' => '',
		);

		$package_arr = wp_parse_args( $package_arr, $defaults );
		
		extract( $package_arr, EXTR_SKIP );

		// Are we updating or creating?
		$package_id = 0;
		$update = false;

		if ( ! empty( $package_arr['id'] ) ) {
			$update = true;

			// Get the package id.
			$package_id = $package_arr['id'];
			$package_before = self::get_package( $package_id );
			if ( is_null( $package_before ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'geodir_pricing_invalid_pcajage', __( 'Invalid package id.', 'geodir_pricing' ) );
				}
				return 0;
			}

			$previous_status = self::get_field( 'status', $package_id );
		} else {
			$previous_status = 'new';
		}

		$post_type = empty( $package_arr['post_type'] ) ? 'gd_place' : $package_arr['post_type'];

		$name = $package_arr['name'];
		$title = $package_arr['title'];
		$description = $package_arr['description'];

		$post_status = empty( $package_arr['post_status'] ) ? 'default' : $package_arr['post_status'];

		if ( isset( $package_arr['display_order'] ) ) {
			$display_order = $package_arr['display_order'] != '' ? absint( $package_arr['display_order'] ) : GeoDir_Pricing_Package::default_sort_order( $post_type );
		} else {
			$display_order = 0;
		}

		$data = compact( 'post_type',  'name', 'title', 'description', 'fa_icon', 'amount', 'time_interval', 'time_unit', 'recurring', 'recurring_limit', 'trial', 'trial_amount', 'trial_interval', 'trial_unit', 'is_default', 'display_order', 'downgrade_pkg', 'post_status', 'status' );

		$emoji_fields = array( 'title', 'description' );

		foreach ( $emoji_fields as $emoji_field ) {
			if ( isset( $data[ $emoji_field ] ) ) {
				$charset = $wpdb->get_col_charset( GEODIR_PRICING_PACKAGES_TABLE, $emoji_field );
				if ( 'utf8' === $charset ) {
					$data[ $emoji_field ] = wp_encode_emoji( $data[ $emoji_field ] );
				}
			}
		}

		$data = apply_filters( 'geodir_pricing_insert_package_data', $data, $package_arr );

		$data = wp_unslash( $data );
		$where = array( 'id' => $package_id );

		if ( $update ) {
			do_action( 'geodir_pricing_pre_package_update', $package_id, $data );

			if ( false === $wpdb->update( GEODIR_PRICING_PACKAGES_TABLE, $data, $where ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'db_update_error', __( 'Could not update package in the database', 'geodir_pricing' ), $wpdb->last_error );
				} else {
					return 0;
				}
			}
		} else {
			if ( false === $wpdb->insert( GEODIR_PRICING_PACKAGES_TABLE, $data ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'db_insert_error', __( 'Could not insert package into the database', 'geodir_pricing' ), $wpdb->last_error );
				} else {
					return 0;
				}
			}
			$package_id = (int) $wpdb->insert_id;
		}

		if ( ! empty( $package_arr['meta'] ) ) {
			foreach ( $package_arr['meta'] as $field => $value ) {
				self::update_meta( $package_id, $field, $value );
			}
		}

		self::clean_cache( $package_id );

		$package = self::get_package( $package_id );

		self::transition_status( $data['status'], $previous_status, $package );

		if ( $update ) {
			do_action( 'geodir_pricing_edit_package', $package_id, $package );

			$package_after = self::get_package ($package_id );

			do_action( 'geodir_pricing_package_updated', $package_id, $package_after, $package_before );
		}

		do_action( 'geodir_pricing_save_package', $package_id, $package, $update );

		do_action( 'geodir_pricing_insert_package', $package_id, $package, $update );

		return $package_id;
	}

	public static function update_package( $package_arr = array(), $wp_error = false ) {
		if ( is_object( $package_arr ) ) {
			$package_arr = get_object_vars( $package_arr );
			$package_arr = wp_slash( $package_arr );
		}

		$package = self::get_package( $package_arr['id'], ARRAY_A );

		if ( is_null( $package ) ) {
			if ( $wp_error )
				return new WP_Error( 'geodir_pricing_invalid_package', __( 'Invalid package ID.', 'geodir_pricing' ) );
			return 0;
		}

		return self::insert_package( $package_arr, $wp_error );
	}

	public static function update_meta( $package_id, $meta_key, $meta_value, $prev_value = '' ) {
		global $wpdb;

		if ( ! $meta_key || ! is_numeric( $package_id ) ) {
			return false;
		}

		$package_id = absint( $package_id );
		if ( ! $package_id ) {
			return false;
		}

		$meta_key = wp_unslash( $meta_key );
		$passed_value = $meta_value;
		$meta_value = wp_unslash( $meta_value );
		$meta_value = self::sanitize_meta( $meta_key, $meta_value );

		$check = apply_filters( "geodir_pricing_update_package_metadata", null, $package_id, $meta_key, $meta_value, $prev_value );
		if ( null !== $check )
			return (bool) $check;

		if ( empty($prev_value) ) {
			$old_value = self::get_meta($package_id, $meta_key);
			if ( count($old_value) == 1 ) {
				if ( $old_value[0] === $meta_value )
					return false;
			}
		}

		$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT package_id FROM " . GEODIR_PRICING_PACKAGE_META_TABLE . " WHERE meta_key = %s AND package_id = %d", $meta_key, $package_id ) );
		if ( empty( $meta_ids ) ) {
			return self::add_metadata( $package_id, $meta_key, $passed_value );
		}
		
		$_meta_value = $meta_value;
		$meta_value = maybe_serialize( $meta_value );

		$data  = compact( 'meta_value' );
		$where = array( 'package_id' => $package_id, 'meta_key' => $meta_key );

		if ( !empty( $prev_value ) ) {
			$prev_value = maybe_serialize($prev_value);
			$where['meta_value'] = $prev_value;
		}

		foreach ( $meta_ids as $meta_id ) {
			do_action( "geodir_pricing_update_package_meta", $meta_id, $package_id, $meta_key, $_meta_value );
		}

		$result = $wpdb->update( GEODIR_PRICING_PACKAGE_META_TABLE, $data, $where );
		if ( ! $result )
			return false;

		geodir_cache_delete( $package_id, 'geodir_pricing_package_meta' );

		foreach ( $meta_ids as $meta_id ) {
			do_action( "geodir_pricing_updated_package_meta", $meta_id, $package_id, $meta_key, $_meta_value );
		}

		geodir_cache_set( 'last_changed', microtime(), 'geodir_pricing_packages' );

		return true;
	}

	public static function get_meta( $package_id, $meta_key = '', $single = false ) {
		if ( ! is_numeric( $package_id ) ) {
			return false;
		}

		$package_id = absint( $package_id );
		if ( ! $package_id ) {
			return false;
		}

		$check = apply_filters( "geodir_pricing_get_package_metadata", null, $package_id, $meta_key, $single );
		if ( null !== $check ) {
			if ( $single && is_array( $check ) )
				return $check[0];
			else
				return $check;
		}

		$meta_cache = geodir_cache_get($package_id, 'geodir_pricing_package_meta');

		if ( !$meta_cache ) {
			$meta_cache = self::update_meta_cache( array( $package_id ) );
			$meta_cache = $meta_cache[$package_id];
		}

		if ( ! $meta_key ) {
			return $meta_cache;
		}

		if ( isset($meta_cache[$meta_key]) ) {
			if ( $single )
				$meta_value = maybe_unserialize( $meta_cache[$meta_key][0] );
			else
				$meta_value = array_map('maybe_unserialize', $meta_cache[$meta_key]);

			if ( ! is_array( $meta_value ) && in_array( $meta_key, array( 'exclude_field', 'exclude_category', 'upgrade_packages' ) ) ) {
				$meta_value = $meta_value != '' ? explode( ',', $meta_value ) : array();
			}
			return $meta_value;
		}

		if ($single)
			return '';
		else
			return array();
	}

	public static function get_metas( $package_id, $single = false, $context = 'display' ) {
		$meta = self::get_meta( $package_id, '', $single );

		$metas = array();
		if ( ! empty( $meta ) ) {
			foreach ( $meta as $key => $data ) {
				$value = isset( $data[0] ) ? $data[0] : $data;
				if ( ! is_array( $value ) && in_array( $key, array( 'exclude_field', 'exclude_category', 'upgrade_packages' ) ) ) {
					$value = $value !== '' ? explode( ',', $value ) : array();
				}
				$metas[ $key ] = self::sanitize_field( $key, $value, $package_id, $context );
			}
		}
		return $metas;
	}

	public static function add_metadata( $package_id, $meta_key, $meta_value, $unique = false ) {
		global $wpdb;

		if ( ! $meta_key || ! is_numeric( $package_id ) ) {
			return false;
		}

		$package_id = absint( $package_id );
		if ( ! $package_id ) {
			return false;
		}

		$meta_key = wp_unslash( $meta_key );
		$meta_value = wp_unslash( $meta_value );
		$meta_value = self::sanitize_meta( $meta_key, $meta_value );

		$check = apply_filters( "geodir_pricing_add_package_metadata", null, $package_id, $meta_key, $meta_value, $unique );
		if ( null !== $check )
			return $check;

		if ( $unique && $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM " . GEODIR_PRICING_PACKAGE_META_TABLE . " WHERE meta_key = %s AND package_id = %d",
			$meta_key, $package_id ) ) )
			return false;

		$_meta_value = $meta_value;
		$meta_value = maybe_serialize( $meta_value );

		do_action( "geodir_pricing_add_package_meta", $package_id, $meta_key, $_meta_value );

		$result = $wpdb->insert( GEODIR_PRICING_PACKAGE_META_TABLE, array(
			'package_id' => $package_id,
			'meta_key' => $meta_key,
			'meta_value' => $meta_value
		) );

		if ( ! $result )
			return false;

		$mid = (int) $wpdb->insert_id;

		geodir_cache_delete( $package_id, 'geodir_pricing_package_meta' );

		do_action( "geodir_pricing_added_package_meta", $mid, $package_id, $meta_key, $_meta_value );

		return $mid;
	}

	public static function get_metadata_by_mid( $meta_id ) {
		global $wpdb;

		if ( ! is_numeric( $meta_id ) || floor( $meta_id ) != $meta_id ) {
			return false;
		}

		$meta_id = intval( $meta_id );
		if ( $meta_id <= 0 ) {
			return false;
		}

		$meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_PRICING_PACKAGE_META_TABLE . " WHERE meta_id = %d", $meta_id ) );

		if ( empty( $meta ) )
			return false;

		if ( isset( $meta->meta_value ) )
			$meta->meta_value = maybe_unserialize( $meta->meta_value );

		return $meta;
	}

	public static function delete_metadata_by_mid( $meta_id ) {
		global $wpdb;

		if ( ! is_numeric( $meta_id ) || floor( $meta_id ) != $meta_id ) {
			return false;
		}

		$meta_id = intval( $meta_id );
		if ( $meta_id <= 0 ) {
			return false;
		}

		if ( $meta = self::get_metadata_by_mid( $meta_id ) ) {
			$object_id = $meta->package_id;

			do_action( "geodir_pricing_delete_package_meta", (array) $meta_id, $object_id, $meta->meta_key, $meta->meta_value );

			$result = (bool) $wpdb->delete( GEODIR_PRICING_PACKAGE_META_TABLE, array( 'meta_id' => $meta_id ) );

			geodir_cache_delete( $object_id, 'geodir_pricing_package_meta' );

			do_action( "geodir_pricing_deleted_package_meta", (array) $meta_id, $object_id, $meta->meta_key, $meta->meta_value );

			return $result;
		}

		return false;
	}

	public static function sanitize_meta( $meta_key, $meta_value ) {

		return apply_filters( "geodir_pricing_sanitize_package_meta_{$meta_key}", $meta_value, $meta_key );
	}

	public static function transition_status( $new_status, $old_status, $package ) {
		do_action( 'geodir_pricing_transition_package_status', $new_status, $old_status, $package );

		do_action( "geodir_pricing_package_status_{$old_status}_to_{$new_status}", $package );

		do_action( "geodir_pricing_package_status_to_{$new_status}", $package->id, $package );
	}

	public static function clean_cache( $package ) {
		global $_wp_suspend_cache_invalidation;

		if ( ! empty( $_wp_suspend_cache_invalidation ) )
			return;

		if ( is_int( $package ) ) {
			$package_id = $package;
		} else if ( is_object( $package ) ) {
			$package_id = $package->id;
		} else {
			$package_id = 0;
		}

		$_package = ! is_object( $package ) && $package_id ? self::get_package( (int) $package_id ) : NULL;

		geodir_cache_delete( $package_id, 'geodir_pricing_packages' );
		geodir_cache_delete( $package_id, 'geodir_pricing_package_meta' );
		geodir_cache_delete( 'geodir_pricing_core_product_package_id-' . $package_id, 'geodir_pricing_core' );

		geodir_cache_flush_group( 'pricing_packages' );

		delete_transient( 'geodir_post_custom_fields' );

		do_action( 'geodir_pricing_clean_package_cache', $package_id, $_package );

		geodir_cache_set( 'last_changed', microtime(), 'geodir_pricing_packages' );
	}

	public static function update_meta_cache($package_ids) {
		global $wpdb;

		if ( ! $package_ids ) {
			return false;
		}

		if ( !is_array($package_ids) ) {
			$package_ids = preg_replace('|[^0-9,]|', '', $package_ids);
			$package_ids = explode(',', $package_ids);
		}

		$package_ids = array_map('intval', $package_ids);

		$cache_key = 'geodir_pricing_package_meta';
		$ids = array();
		$cache = array();
		foreach ( $package_ids as $id ) {
			$cached_object = geodir_cache_get( $id, $cache_key );
			if ( false === $cached_object )
				$ids[] = $id;
			else
				$cache[$id] = $cached_object;
		}

		if ( empty( $ids ) )
			return $cache;

		// Get meta info
		$id_list = join( ',', $ids );
		$meta_list = $wpdb->get_results( "SELECT package_id, meta_key, meta_value FROM " . GEODIR_PRICING_PACKAGE_META_TABLE . " WHERE package_id IN ($id_list) ORDER BY meta_id ASC", ARRAY_A );

		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow) {
				$mpid = intval($metarow['package_id']);
				$mkey = $metarow['meta_key'];
				$mval = $metarow['meta_value'];

				if ( in_array( $mkey, array( 'exclude_field', 'exclude_category', 'upgrade_packages' ) ) ) {
					$mval = $mval !== '' ? explode( ',', $mval ) : array();
				}

				// Force subkeys to be array type:
				if ( !isset($cache[$mpid]) || !is_array($cache[$mpid]) )
					$cache[$mpid] = array();
				if ( !isset($cache[$mpid][$mkey]) || !is_array($cache[$mpid][$mkey]) )
					$cache[$mpid][$mkey] = array();

				// Add a value to the current pid/key:
				$cache[$mpid][$mkey][] = $mval;
			}
		}

		foreach ( $ids as $id ) {
			if ( ! isset($cache[$id]) )
				$cache[$id] = array();
			geodir_cache_set( $id, $cache[$id], $cache_key );
		}

		return $cache;
	}

	public static function get_packages( $args = array() ) {
		global $wpdb;

		$cache_key = ! empty( $args ) ? sanitize_key( serialize( $args ) ) : 'args';
		$packages = geodir_cache_get( 'geodir_pricing_get_packages_' . $cache_key, 'pricing_packages' );

		if ( ! isset( $args['status'] ) ) {
			$args['status'] = '1';
		}

		if ( $packages !== false ) {
			$packages = apply_filters( 'geodir_pricing_get_packages', $packages, $args );

			return $packages;
		}

		$where_clauses = array();
		if ( ! empty( $args['post_type'] ) ) {
			$where_clauses[] = $wpdb->prepare( "post_type = %s", $args['post_type'] );
		}
		if ( $args['status'] != 'all' ) {
			$where_clauses[] = $wpdb->prepare( "status = %d", $args['status'] );
		}
		if ( ! empty( $args['package__in'] ) && ( $package__in = array_map( 'absint', $args['package__in'] ) ) ) {
			$where_clauses[] = "id IN (" . implode( ',', $package__in ) . ")";
		}
		$where_clauses = apply_filters( 'geodir_pricing_packages_query_where_clauses', $where_clauses, $args );

		if ( ! empty( $args['order_by'] ) ) {
			$order_by = $args['order_by'];
		} else {
			if ( ! empty( $args['order'] ) ) {
				$order_by = $args['order'] . ' ' . ( ! empty( $args['order_type'] ) ? $args['order_type'] : 'ASC' );
			} else {
				$order_by = 'display_order ASC';
			}
		}
		$order_by = apply_filters( 'geodir_pricing_packages_query_order_by', $order_by, $args );

		$fields = ! empty( $args['fields'] ) ? ( is_array( $args['fields'] ) ? implode( ', ', $args['fields'] ) : $args['fields'] ) : '*';
		$where = ! empty( $where_clauses ) ? implode( " AND ", $where_clauses ) : '';
		$order_by = ! empty( $order_by ) ? "ORDER BY {$order_by}" : '';

		//In case the user wants to always include a given package in the results
		if ( ! empty( $args['must_include'] ) && !empty( $where ) ) {
			$where = $wpdb->prepare( "( $where ) OR id = %d", $args['must_include'] );
		}

		if( !empty( $where ) ){
			$where = "WHERE " . $where;
		}

		$results = $wpdb->get_results( "SELECT {$fields} FROM " . GEODIR_PRICING_PACKAGES_TABLE . " {$where} {$order_by}" );

		$packages = apply_filters( 'geodir_pricing_get_packages', $results, $args );

		geodir_cache_set( 'geodir_pricing_get_packages_' . $cache_key, $packages, 'pricing_packages' );

		return $packages;

	}

	public static function get_name( $package ) {
		global $wpdb;

		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return NULL;
		}

		$name = geodir_cache_get( 'geodir_pricing_package_name:' . $package->id, 'pricing_packages' );

		if ( $name !== false ) {
			return $name;
		}

		if ( ! empty( $package->name ) ) {
			$name = $package->name;
		} else {
			$name = $package->id;
		}

		$name = apply_filters( 'geodir_pricing_package_name', $name, $package );

		geodir_cache_set( 'geodir_pricing_package_name:' . $package->id, $name, 'pricing_packages' );

		return $name;
	}

	public static function get_title( $package ) {
		global $wpdb;

		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return NULL;
		}

		$title = geodir_cache_get( 'geodir_pricing_package_title:' . $package->id, 'pricing_packages' );

		if ( $title !== false ) {
			return $title;
		}

		if ( ! empty( $package->title ) ) {
			$title = $package->title;
		} else {
			$title = $package->id;
		}

		$title = apply_filters( 'geodir_pricing_package_title', $title, $package );

		geodir_cache_set( 'geodir_pricing_package_title:' . $package->id, $title, 'pricing_packages' );

		return $title;
	}

	public static function get_post_type( $package ) {
		global $wpdb;

		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return NULL;
		}

		$post_type = geodir_cache_get( 'geodir_pricing_package_post_type' . $package->id, 'pricing_packages' );

		if ( $post_type !== false ) {
			return $post_type;
		}

		$post_type = $package->post_type;

		geodir_cache_set( 'geodir_pricing_package_post_type' . $package->id, $post_type, 'pricing_packages' );

		return $post_type;
	}

	public static function get_post_status( $package ) {
		global $wpdb;

		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return 'pending';
		}

		$post_status = geodir_cache_get( 'geodir_pricing_package_post_status:' . $package->id, 'pricing_packages' );

		if ( $post_status !== false ) {
			return $post_status;
		}
		
		if ( !empty( $package->post_status ) && $package->post_status != 'default' ) {
			$post_status = $package->post_status;
		} else if ( $paid_listing_status = geodir_get_option( 'pm_paid_listing_status' ) ) {
			$post_status = $paid_listing_status;
		} else {
			$post_status = 'publish';
		}

		$post_status = apply_filters( 'geodir_pricing_package_post_status', $post_status, $package );

		geodir_cache_set( 'geodir_pricing_package_post_status:' . $package->id, $post_status, 'pricing_packages' );

		return $post_status;
	}

	public static function get_alive_days( $package, $trial = false ) {
		global $wpdb;

		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return 0;
		}

		$alive_days = geodir_cache_get( 'geodir_pricing_package_alive_days:' . $package->id . ':' . (int) $trial, 'pricing_packages' );

		if ( $alive_days !== false ) {
			return $alive_days;
		}

		if ( $trial && ! empty( $package->trial ) && ! empty( $package->trial_interval ) ) {
			$alive_days = geodir_pricing_period_in_days( $package->trial_interval, $package->trial_unit );
		} else {
			$alive_days = geodir_pricing_period_in_days( $package->time_interval, $package->time_unit );
		}

		$alive_days = apply_filters( 'geodir_pricing_package_alive_days', $alive_days, $package, $trial );

		geodir_cache_set( 'geodir_pricing_package_alive_days:' . $package->id . ':' . (int) $trial, $alive_days, 'pricing_packages' );

		return $alive_days;
	}

	public static function get_desc_limit( $package ) {
		global $wpdb;

		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return NULL;
		}

		$desc_limit = geodir_cache_get( 'geodir_pricing_package_desc_limit:' . $package->id, 'pricing_packages' );

		if ( $desc_limit !== false ) {
			return $desc_limit;
		}

		if ( (bool) geodir_pricing_get_meta( $package->id, 'use_desc_limit', true ) ) {
			$desc_limit = absint( geodir_pricing_get_meta( $package->id, 'desc_limit', true ) );
		} else {
			$desc_limit = NULL;
		}

		$desc_limit = apply_filters( 'geodir_pricing_package_desc_limit', $desc_limit, $package );

		geodir_cache_set( 'geodir_pricing_package_desc_limit:' . $package->id, $desc_limit, 'pricing_packages' );

		return $desc_limit;
	}

	public static function get_max_posts( $package ) {
		global $wpdb;

		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return NULL;
		}

		$max_posts = geodir_cache_get( 'geodir_pricing_package_max_posts:' . $package->id, 'pricing_packages' );

		if ( $max_posts !== false ) {
			return $max_posts;
		}

		$max_posts = (int) geodir_pricing_get_meta( $package->id, 'max_posts', true );

		$max_posts = apply_filters( 'geodir_pricing_package_max_posts', $max_posts, $package );

		geodir_cache_set( 'geodir_pricing_package_max_posts:' . $package->id, $max_posts, 'pricing_packages' );

		return $max_posts;
	}

	public static function get_post_package( $package, $gd_post = '', $gd_post_type = '' ) {
		if ( is_array( $gd_post ) ) {
			$gd_post = json_decode( json_encode( $gd_post ), FALSE );
		} else if ( ! empty( $gd_post ) && is_scalar( $gd_post ) && absint( $gd_post ) > 1 ) {
			$gd_post = geodir_get_post_info( absint( $gd_post ) );
		}

		$post_type = isset( $_REQUEST['listing_type'] ) ? sanitize_text_field( $_REQUEST['listing_type'] ) : '';
		if ( ! empty( $gd_post->post_type ) ) {
			$post_type = $gd_post->post_type;
		}

		$package_id = ! empty( $gd_post->package_id ) ? $gd_post->package_id : 0;

		if ( ! empty( $_REQUEST['package_id'] ) ) {
			$package_id = absint( $_REQUEST['package_id'] );
		} else if ( isset( $gd_post->package_id ) || ( ( isset( $_REQUEST['post_type'] ) || isset( $gd_post->post_type ) ) && $package_id ) || ( isset( $gd_post->ID ) && ( $package_id = geodir_get_post_meta( $gd_post->ID, 'package_id' ) ) ) ) {
			if ( ! empty( $gd_post->post_type ) ) {
				$post_type = $gd_post->post_type;
			}
			if ( ! empty( $gd_post->package_id ) ) {
				$package_id = $gd_post->package_id;
			}
		} else if ( ( $post_type != '' && isset( $gd_post->pid ) && $gd_post->pid != '' ) || ( isset( $_REQUEST['pid'] ) && $_REQUEST['pid'] != '' && ! isset( $_REQUEST['post_type'] ) ) ) {
			$post_id = ! empty( $gd_post->pid ) ? $gd_post->pid : absint( $_REQUEST['pid'] );
			$package_id = geodir_get_post_meta( $post_id, 'package_id' );
		} else if ( ! empty( $gd_post->package_id ) ) {
			$package_id = $gd_post->package_id;
		}

		if ( empty( $package_id ) ) {
			if ( empty( $post_type ) && ! empty( $gd_post->post_type ) ) {
				$post_type = $gd_post->post_type;
			}
		
			if ( empty( $post_type ) && geodir_is_gd_post_type( $gd_post_type ) ) {
				$post_type = $gd_post_type;
			}

			if ( $default_package_id = self::get_default_package_id( $post_type ) ) {
				$package_id = $default_package_id;
			}
		}

		$package = $package_id ? self::get_package( $package_id ) : $package;
		if ( isset($package) && $package_metas = self::get_metas( $package_id, true ) ) {
			foreach ( $package_metas as $key => $value ) {
				$package->{$key} = $value;
			}
		}

		return apply_filters( 'geodir_pricing_get_post_package', $package, $post_type, $gd_post );
	}

	public static function get_field_packages( $field ) {
		global $wpdb;

		$packages = '';

		if ( is_object( $field ) && isset( $field->packages ) ) {
			$packages = $field->packages;
		} else {
			if ( is_object( $field ) && ! empty( $field->id ) ) {
				$field_id = $field->id;
			} else if ( is_int( $field ) && ! empty( $field ) ) {
				$field_id = $field;
			} else {
				$field_id = 0;
			}

			if ( ! empty( $field_id ) ) {
				$packages = $wpdb->get_var( $wpdb->prepare( "SELECT packages FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE id = %d", array( $field_id ) ) );
			}
		}

		return self::sanitize_package_ids( $packages, true );
	}

	public static function sanitize_package_ids( $value, $sort = false ) {
		if ( ! is_array( $value ) ) {
			$value = $value != '' ? explode( ',', $value ) : array();
		}

		if ( ! empty( $value ) ) {
			$value = array_map( 'trim', $value );
			$value = array_map( 'absint', $value );
			$value = array_filter( $value );
			$value = array_unique( $value );

			if ( ! empty( $sort ) ) {
				sort( $value );
			}
		}

		return $value;
	}

	public static function sanitize_package_fields( $value, $sort = false ) {
		if ( ! is_array( $value ) ) {
			$value = $value != '' ? explode( ',', $value ) : array();
		}

		if ( ! empty( $value ) ) {
			$value = array_map( 'trim', $value );
			$value = array_unique( $value );

			if ( ! empty( $sort ) ) {
				sort( $value );
			}
		}

		return $value;
	}

	public static function update_is_default( $package_id, $package_after, $package_before ) {
		global $wpdb;

		if ( absint( $package_after->is_default ) && absint( $package_after->is_default ) != absint( $package_before->is_default ) ) {
			if ( absint( $package_after->is_default ) ) {
				self::set_default( $package_after );
			}
		}
	}

	public static function delete( $package ) {
		global $wpdb;

		if ( is_int( $package ) ) {
			$package_id = $package;
		} else if ( is_object( $package ) ) {
			$package_id = $package->id;
		} else {
			$package_id = 0;
		}

		$package = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_PRICING_PACKAGES_TABLE . " WHERE id = %d AND is_default != 1", $package_id ) );

		if ( ! $package ) {
			return false;
		}

		$package = self::get_package( $package_id );

		// Don't allow to delete default package.
		if ( ! empty( $package->is_default ) ) {
			return false;
		}

		/**
		 * Filters whether a deletion should take place.
		 *
		 * @since 2.5.0
		 *
		 * @param bool    $delete       Whether to go forward with deletion.
		 * @param object  $package      Package object.
		 */
		$check = apply_filters( 'geodir_pricing_pre_delete_package', null, $package );
		if ( null !== $check ) {
			return $check;
		}

		/**
		 * Fires before a package is deleted.
		 *
		 * @since 2.5.0
		 *
		 * @param object $package Package object.
		 */
		do_action( 'geodir_pricing_before_delete_package', $package );
		
		$package_meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM " . GEODIR_PRICING_PACKAGE_META_TABLE . " WHERE package_id = %d ", $package_id ) );
		foreach ( $package_meta_ids as $mid ) {
			self::delete_metadata_by_mid( $mid );
		}

		/**
		 * Fires immediately before a package is deleted from the database.
		 *
		 * @param int $package_id Package ID.
		 */
		do_action( 'geodir_pricing_delete_package', $package_id );

		$result = $wpdb->delete( GEODIR_PRICING_PACKAGES_TABLE, array( 'id' => $package_id ) );
		if ( ! $result ) {
			return false;
		}

		/**
		 * Fires immediately after a package is deleted from the database.
		 *
		 * @param int $package_id Package ID.
		 */
		do_action( 'geodir_pricing_deleted_package', $package_id );

		self::clean_cache( $package_id );

		/**
		 * Fires after a package is deleted, at the conclusion.
		 *
		 *
		 * @param int $package_id Package ID.
		 */
		do_action( 'geodir_pricing_after_delete_package', $package_id );

		return $package;
	}

	public static function set_default( $package ) {
		global $wpdb;

		$package = is_int( $package ) ? self::get_package( $package ) : $package;
		if ( empty( $package->id ) ) {
			return false;
		}

		do_action( 'geodir_pricing_package_pre_set_default', $package );

		$wpdb->query( $wpdb->prepare( "UPDATE " . GEODIR_PRICING_PACKAGES_TABLE . " SET is_default = 0 WHERE post_type = %s", array( $package->post_type ) ) );
		$wpdb->query( $wpdb->prepare( "UPDATE " . GEODIR_PRICING_PACKAGES_TABLE . " SET is_default = 1 WHERE post_type = %s AND id = %d", array( $package->post_type, $package->id ) ) );

		do_action( 'geodir_pricing_package_set_default', $package );

		return true;
	}

	public static function get_default_package_id( $post_type = '', $create = true ) {
		global $wpdb;

		if ( ! geodir_is_gd_post_type( $post_type ) ) {
			return NULL;
		}

		$package_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . GEODIR_PRICING_PACKAGES_TABLE . "` WHERE `post_type` = %s AND `status` = %d ORDER BY `is_default` DESC, `display_order` ASC, `id` ASC LIMIT 1", $post_type, 1 ) );

		if ( empty( $package_id ) ) {
			$create = apply_filters('geodir_pricing_force_create_default_package', $create, $post_type );

			if ( $create ) {
				$package_id = GeoDir_Pricing_Admin_Install::create_default_package( $post_type );
			}
		}

		return apply_filters('geodir_pricing_default_package_id', $package_id, $post_type );
	}

	public static function get_default_package( $post_type = '', $create = true ) {
		$package_id = self::get_default_package_id( $post_type, $create );

		$package = self::get_package( $package_id );

		return apply_filters('geodir_pricing_default_package', $package, $post_type );
	}

	public static function onsave_package_meta( $meta_id, $package_id, $meta_key, $_meta_value ) {
		if ( $meta_key == 'exclude_field' ) {
			self::exclude_fields( $package_id, $_meta_value );
		}
	}

	public static function exclude_fields( $package_id, $fields ) {
		global $wpdb;

		if ( empty( $package_id ) ) {
			return false;
		}

		if ( ! is_array( $fields ) ) {
			$fields = $fields !== '' ? explode( ',', $fields ) : array();
		}

		$post_type = self::get_post_type( $package_id );

		if ( empty( $post_type ) ) {
			return false;
		}

		$fields = apply_filters( 'geodir_pricing_package_exclude_fields', $fields, $package_id );
		if ( null === $fields || $fields === false ) {
			return false;
		}

		do_action( 'geodir_pricing_package_before_exclude_fields', $fields, $package_id );

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT id, htmlvar_name, packages FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE post_type = %s", array( $post_type ) ) );

		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $row ) {
				$prev_value = self::sanitize_package_ids( $row->packages, true );

				$new_value = $prev_value;
				if ( ! empty( $fields ) && in_array( $row->htmlvar_name, $fields ) ) { // Exclude
					if ( ! empty( $new_value ) ) {
						foreach ( $new_value as $key => $value ) {
							if ( $value == $package_id ) {
								unset( $new_value[ $key ] );
							}
						}
					}
				} else { // Include
					$new_value[] = $package_id;
				}

				$new_value = self::sanitize_package_ids( $new_value, true );

				$value = ! empty( $new_value ) ? implode( ',', $new_value ) : '';
				if ( implode( ',', $prev_value ) != $value ) {
					$wpdb->update( GEODIR_CUSTOM_FIELDS_TABLE, array( 'packages' => $value ), array( 'id' => $row->id ) );

					do_action( 'geodir_pricing_field_packages_updated', $row->id, $prev_value, $new_value );
				}
			}
		}

		do_action( 'geodir_pricing_package_after_exclude_fields', $fields, $package_id );

		return true;
	}

	public static function check_field_visibility( $show, $field_name, $package_id, $post_type = '' ) {
		if ( $show && ( $exclude_fields = self::get_meta( $package_id, 'exclude_field', true ) ) ) {
			if ( is_array( $exclude_fields ) && in_array( $field_name, $exclude_fields ) ) {
				$show = false;
			}
		}
		return $show;
	}

	public static function default_sort_order( $post_type = '' ) {
		global $wpdb;

		$where = array();
		if ( ! empty( $post_type ) ) {
			$where[] = $wpdb->prepare( "post_type = %s", array( $post_type ) );
		}
		$where = ! empty( $where ) ? " WHERE " . implode( " AND ", $where ) : "";
		$sort_order = $wpdb->get_var( "SELECT MAX(display_order) AS sort_order FROM " . GEODIR_PRICING_PACKAGES_TABLE . " {$where} LIMIT 1" );

		return (int)$sort_order + 1;
	}

	/**
	 * @since 2.5.1.0
	 */
	public static function is_free( $package ) {
		global $wpdb;

		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return NULL;
		}

		$cache_key = 'geodir_pricing_package_is_free:' . $package->id;
		$is_free = geodir_cache_get( $cache_key, 'pricing_packages' );

		if ( $is_free !== false ) {
			return $is_free;
		}

		if ( ! ( (float) $package->amount > 0 ) ) {
			$is_free = true;
		}

		$is_free = apply_filters( 'geodir_pricing_package_is_free', $is_free, $package );

		geodir_cache_set( $cache_key, $is_free, 'pricing_packages' );

		return $is_free;
	}

	/**
	 * @since 2.5.1.0
	 */
	public static function is_recurring( $package ) {
		global $wpdb;

		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return NULL;
		}

		$cache_key = 'geodir_pricing_package_is_recurring:' . $package->id;
		$is_recurring = geodir_cache_get( $cache_key, 'pricing_packages' );

		if ( $is_recurring !== false ) {
			return $is_recurring;
		}

		if ( ! empty( $package->recurring ) ) {
			$is_recurring = true;
		}

		$is_recurring = apply_filters( 'geodir_pricing_package_is_recurring', $is_recurring, $package );

		geodir_cache_set( $cache_key, $is_recurring, 'pricing_packages' );

		return $is_recurring;
	}

	public static function add_listing_url( $package ) {
		if ( ! is_object( $package ) ) {
			$package = self::get_package( $package );
		}

		if ( ! ( is_object( $package ) && ! empty( $package->id ) ) ) {
			return NULL;
		}

		$add_listing_url = geodir_cache_get( 'geodir_pricing_package_add_listing_url' . $package->id, 'pricing_packages' );
		if ( $add_listing_url !== false ) {
			return $add_listing_url;
		}

		$add_listing_url = add_query_arg( array( 'package_id' => $package->id ), geodir_add_listing_page_url( $package->post_type ) );

		$add_listing_url = apply_filters( 'geodir_pricing_package_add_listing_url', $add_listing_url, $package );

		geodir_cache_set( 'geodir_pricing_package_add_listing_url' . $package->id, $add_listing_url, 'pricing_packages' );

		return $add_listing_url;
	}
}