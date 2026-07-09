<?php
/**
 * Cart-level currency handling: coupons, fees, gateway availability,
 * and the order-currency snapshot at checkout.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fixed-amount coupons are authored in SAR; convert them into the
 * active currency. Percentage coupons need no conversion — they apply
 * to totals that are already converted.
 *
 * @param string|float $amount Coupon amount.
 * @param WC_Coupon    $coupon Coupon object.
 * @return string|float
 */
function fares_currency_convert_coupon_amount( $amount, $coupon ) {
	if ( ! $coupon instanceof WC_Coupon || ! in_array( $coupon->get_discount_type(), array( 'fixed_cart', 'fixed_product' ), true ) ) {
		return $amount;
	}

	return fares_currency_convert_price( $amount );
}
add_filter( 'woocommerce_coupon_get_amount', 'fares_currency_convert_coupon_amount', 99, 2 );

// Coupon spend thresholds are fixed SAR amounts regardless of type.
add_filter( 'woocommerce_coupon_get_minimum_amount', 'fares_currency_convert_price', 99 );
add_filter( 'woocommerce_coupon_get_maximum_amount', 'fares_currency_convert_price', 99 );

/**
 * Cart fees are authored in SAR by whatever code adds them. Convert
 * after every fee callback has run. Fees are rebuilt on each totals
 * calculation, so this runs exactly once per computed fee.
 *
 * @param WC_Cart $cart Cart object.
 */
function fares_currency_convert_cart_fees( $cart ): void {
	$active = fares_currency_active();

	if ( FARES_CURRENCY_BASE === $active || ! $cart instanceof WC_Cart ) {
		return;
	}

	foreach ( $cart->fees_api()->get_fees() as $fee ) {
		if ( is_numeric( $fee->amount ) && 0.0 !== (float) $fee->amount ) {
			$fee->amount = fares_currency_convert( (float) $fee->amount, $active );
		}
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'fares_currency_convert_cart_fees', PHP_INT_MAX );

/**
 * Hide gateways that can't charge the active currency. Restrictions
 * come from the registry's per-market 'gateways' allowlist; a null
 * allowlist means every gateway is fine.
 *
 * @param array $gateways Available gateway id => instance.
 * @return array
 */
function fares_currency_filter_gateways( array $gateways ): array {
	$market = fares_currency_market_for( fares_currency_active() );

	if ( null === $market || ! is_array( $market['gateways'] ?? null ) ) {
		return $gateways;
	}

	return array_intersect_key( $gateways, array_flip( $market['gateways'] ) );
}
add_filter( 'woocommerce_available_payment_gateways', 'fares_currency_filter_gateways', 99 );

/**
 * Snapshot the FX context on the order the moment it is created.
 * WooCommerce stores _order_currency itself (from the filtered
 * get_woocommerce_currency()); we add the rate used and the base
 * currency so bookkeeping can back-compute the SAR value at any time.
 *
 * @param WC_Order $order Order being created.
 */
function fares_currency_stamp_order( $order ): void {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$active = fares_currency_active();

	$order->update_meta_data( '_fares_base_currency', FARES_CURRENCY_BASE );
	$order->update_meta_data( '_fares_fx_rate_used', (string) ( fares_currency_rate( $active ) ?? 1.0 ) );
}
add_action( 'woocommerce_checkout_create_order', 'fares_currency_stamp_order', 10 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'fares_currency_stamp_order', 10 );

/**
 * Notify the cart page when the FX rate moved noticeably since the
 * visitor last saw it, so a changed total is explained rather than a
 * support ticket. Threshold is 2% by default.
 */
function fares_currency_notice_rate_change(): void {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() || ! WC()->session ) {
		return;
	}

	$active = fares_currency_active();

	if ( FARES_CURRENCY_BASE === $active ) {
		return;
	}

	$rate = fares_currency_rate( $active );
	$seen = (float) WC()->session->get( 'fares_currency_seen_rate_' . $active, 0.0 );

	if ( null === $rate ) {
		return;
	}

	$threshold = (float) apply_filters( 'fares_currency_rate_notice_threshold', 0.02 );

	if ( $seen > 0 && abs( $rate - $seen ) / $seen > $threshold && ! wc_has_notice( fares_currency_rate_notice_text(), 'notice' ) ) {
		wc_add_notice( fares_currency_rate_notice_text(), 'notice' );
	}

	WC()->session->set( 'fares_currency_seen_rate_' . $active, $rate );
}
add_action( 'template_redirect', 'fares_currency_notice_rate_change' );

/**
 * Copy for the rate-change notice.
 */
function fares_currency_rate_notice_text(): string {
	return __( 'تُحدَّث الأسعار يوميًا وفق أسعار الصرف الحالية.', 'fares-store' );
}
