<?php
/**
 * Buy-now flow: a second submit button on the add-to-cart form sends the
 * customer straight to checkout.
 *
 * The theme renders the button (name="fares_buy_now"); this module owns the
 * behavior. Riding the native add-to-cart request means Woo's own
 * validation still applies: variable products without a chosen variation
 * error and stay on the product page, quantity is honoured, and existing
 * cart contents are preserved.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current add-to-cart request is a buy-now request.
 */
function fares_store_is_buy_now_request(): bool {
	// Nonce not required: this only changes the redirect target of Woo's own
	// validated add-to-cart request; it performs no privileged action.
	return isset( $_REQUEST['fares_buy_now'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Redirect to checkout after a successful buy-now add-to-cart.
 *
 * @param string|false $url Redirect URL (WooCommerce passes false when no
 *                          redirect is planned).
 * @return string|false
 */
function fares_store_buy_now_redirect( $url ) {
	if ( fares_store_is_buy_now_request() && 0 === wc_notice_count( 'error' ) ) {
		return wc_get_checkout_url();
	}

	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'fares_store_buy_now_redirect' );
