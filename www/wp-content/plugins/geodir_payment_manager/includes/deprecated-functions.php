<?php
/**
 * Deprecated functions.
 *
 * Functions that no longer in use after v2.5.0.0.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_get_currency_sym() {
    _deprecated_function( __FUNCTION__, '2.5.0.0', 'geodir_pricing_currency_sign()' );
}

/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_get_currency_type() {
    _deprecated_function( __FUNCTION__, '2.5.0.0', 'geodir_pricing_currency_code()' );
}

/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_payment_get_currency_position() {
    _deprecated_function( __FUNCTION__, '2.5.0.0', 'geodir_pricing_currency_position()' );
}

/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_expire_check() {
    _deprecated_function( __FUNCTION__, '2.5.0.0', 'geodir_pricing_expire_check()' );
}

/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_payment_price( $amount, $display = true, $decimal_sep = '.', $thousand_sep = "," ) {
    _deprecated_function( __FUNCTION__, '2.5.0.0', 'geodir_pricing_price()' );
}

/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_get_package_info( $package_id ) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'geodir_pricing_get_package()' );

	return geodir_pricing_get_package( (int) $package_id );
}

/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_get_package_info_by_id( $package_id, $status = '1' ) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'geodir_pricing_get_package()' );

	return geodir_pricing_get_package( (int) $package_id );
}

/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_get_post_package_info( $package_id = '', $post_id = '' ) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'geodir_pricing_get_package()' );

	return geodir_pricing_get_package( (int) $package_id );
}


/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_get_invoice( $id = '' ) {
	_deprecated_function( __FUNCTION__, '2.5.0' );

	return array();
}

/**
 * @since 2.5.0
 *
 * @deprecated
 */
function geodir_payment_checkout_page_id(){
    _deprecated_function( __FUNCTION__, '2.5.0' );

	$gd_page_id = get_option( 'geodir_checkout_page' );

    return $gd_page_id;
}