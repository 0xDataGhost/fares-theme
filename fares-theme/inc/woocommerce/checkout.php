<?php
/**
 * Checkout field re-composition — virtual-only store keeps only
 * name/email/phone; address and company drop when nothing ships.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Strip address/company fields when the cart carries no shippable items.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function fares_checkout_virtual_fields( array $fields ): array {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->needs_shipping() ) {
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
