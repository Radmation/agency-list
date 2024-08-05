<?php
/**
 * Pricing Manager Cart Functions.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_pricing_cart_options() {
	$cart_options = array();
	$cart_options[''] = __( 'No Cart', 'geodir_pricing' );

	// Invoicing
	if ( defined( 'WPINV_VERSION' ) && version_compare( WPINV_VERSION, '1.0.0', '>=' ) ) {
		$cart_options['invoicing'] = __( 'Invoicing', 'geodir_pricing' );
	}

	if ( defined( 'WPINV_VERSION' ) && version_compare( WPINV_VERSION, '1.1.0', '>=' ) ) {
		$cart_options['invoicing'] = __( 'GetPaid', 'geodir_pricing' );
	}

	// WooCommerce
	if ( class_exists( 'WooCommerce' ) ) {
		$cart_options['woocommerce'] = __( 'WooCommerce', 'geodir_pricing' );
	}

	return apply_filters( 'geodir_pricing_cart_options', $cart_options );
}

function geodir_pricing_currency_code() {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->currency_code();
}

function geodir_pricing_currency_sign( $currency = '' ) {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->currency_sign( $currency );
}

function geodir_pricing_currency_position() {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->currency_position();
}

function geodir_pricing_thousand_separator() {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->thousand_separator();
}

function geodir_pricing_decimal_separator() {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->decimal_separator();
}

function geodir_pricing_decimals() {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->decimals();
}

function geodir_pricing_price_format() {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->price_format();
}

function geodir_pricing_price( $price, $args = array() ) {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->price( $price, $args );
}

function geodir_pricing_get_product_id( $package ) {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->get_product_id( $package );
}

function geodir_pricing_get_package_id( $product ) {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->get_package_id( $product );
}

/**
 * Format decimal numbers ready for DB storage.
 *
 * Sanitize, remove decimals, and optionally round + trim off zeros.
 *
 * This function does not remove thousands - this should be done before passing a value to the function.
 *
 * @since 2.5.0
 *
 * @param  float|string $number Expects either a float or a string with a decimal separator only (no thousands)
 * @param  mixed $dp number of decimal points to use, blank to use geodir_get_price_decimals, or false to avoid all rounding.
 * @param  bool $trim_zeros from end of string
 * @return string
 */
function geodir_pricing_format_decimal( $number, $dp = false, $trim_zeros = false ) {
	global $geodir_pricing_manager;

	return $geodir_pricing_manager->cart->format_decimal( $number, $dp, $trim_zeros );
}

function geodir_pricing_sync_package_to_cart_item( $package_id ) {
	global $geodir_pricing_manager;

	if ( is_callable( array( $geodir_pricing_manager->cart, 'sync_item_to_package' ) ) ) {
		return $geodir_pricing_manager->cart->sync_item_to_package( $package_id );
	}
	
	return $geodir_pricing_manager->cart->sync_package_to_cart_item( $package_id );
}