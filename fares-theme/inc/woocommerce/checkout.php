<?php
/**
 * Checkout field re-composition — virtual-only store keeps only
 * name/email/phone; address and company drop when nothing ships.
 *
 * Handles both the classic shortcode checkout (via
 * `woocommerce_checkout_fields`) and the Blocks checkout (via the
 * `fares-virtual-checkout` body class + a required-flag reset so the
 * hidden block still submits).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * True when the current cart carries no shippable items.
 */
function fares_cart_is_virtual(): bool {
	return function_exists( 'WC' )
		&& WC()->cart instanceof WC_Cart
		&& ! WC()->cart->needs_shipping();
}

/**
 * Classic checkout — strip address/company fields from the billing block.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function fares_checkout_virtual_fields( array $fields ): array {
	if ( ! fares_cart_is_virtual() ) {
		return $fields;
	}

	$drop = array(
		'billing_country',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
		'billing_company',
	);

	foreach ( $drop as $key ) {
		unset( $fields['billing'][ $key ] );
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'fares_checkout_virtual_fields' );

/**
 * Blocks checkout — the address block is hidden via CSS on the
 * `fares-virtual-checkout` body class; drop `required` on billing
 * address fields so the empty submission still validates.
 *
 * @param array $fields Billing fields.
 * @return array
 */
function fares_billing_fields_virtual( array $fields ): array {
	if ( ! fares_cart_is_virtual() ) {
		return $fields;
	}

	$address_keys = array(
		'billing_country',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
		'billing_company',
	);

	foreach ( $address_keys as $key ) {
		if ( isset( $fields[ $key ] ) ) {
			$fields[ $key ]['required'] = false;
		}
	}

	return $fields;
}
add_filter( 'woocommerce_billing_fields', 'fares_billing_fields_virtual' );

/**
 * Same relaxation applied to the shared default address definition so
 * the Blocks Store API accepts an empty address on a virtual cart.
 *
 * @param array $fields Default address fields.
 * @return array
 */
function fares_default_address_fields_virtual( array $fields ): array {
	if ( ! fares_cart_is_virtual() ) {
		return $fields;
	}

	foreach ( array_keys( $fields ) as $key ) {
		if ( isset( $fields[ $key ]['required'] ) ) {
			$fields[ $key ]['required'] = false;
		}
	}

	return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'fares_default_address_fields_virtual' );

/**
 * Tag the <body> on virtual-cart checkout so the CSS can hide the
 * Blocks billing-address block cleanly.
 *
 * @param array $classes Body classes.
 * @return array
 */
function fares_virtual_checkout_body_class( array $classes ): array {
	if ( function_exists( 'is_checkout' ) && is_checkout() && fares_cart_is_virtual() ) {
		$classes[] = 'fares-virtual-checkout';
	}
	return $classes;
}
add_filter( 'body_class', 'fares_virtual_checkout_body_class' );
