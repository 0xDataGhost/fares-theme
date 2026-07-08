<?php
/**
 * Checkout field rules for a virtual-only code-delivery store.
 *
 * Codes are delivered by email — the customer only needs to tell us who
 * they are and where to send the code: first name, email, phone.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trim billing to the essentials.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function fares_store_trim_checkout_fields( array $fields ): array {
	$keep = array( 'billing_first_name', 'billing_email', 'billing_phone' );

	foreach ( array_keys( $fields['billing'] ?? array() ) as $key ) {
		if ( ! in_array( $key, $keep, true ) ) {
			unset( $fields['billing'][ $key ] );
		}
	}

	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['required'] = true;
	}

	// No physical delivery — drop order notes too.
	unset( $fields['order']['order_comments'] );

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'fares_store_trim_checkout_fields' );

/**
 * Optional billing country/address requirements can trip validation for
 * virtual carts on some gateways — declare no address fields needed.
 */
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
