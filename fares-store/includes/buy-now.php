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

/**
 * Idempotent buy-now: if the product is already in the cart (repeat
 * click, browser refresh of a URL still carrying `add-to-cart`, or
 * back-button resubmit), skip Woo's add-to-cart handler entirely and
 * go straight to checkout.
 *
 * Without this, a limited-stock product (stock 1, cart 1) re-triggers
 * `WC_Cart::add_to_cart()` which fails with "you cannot add that
 * amount… you already have 1 in your cart" — an error that then blocks
 * the redirect and strands the customer on an alert instead of paying
 * for the item they already hold.
 *
 * Runs at wp_loaded:15 — after the cart session loads (10), before
 * WC_Form_Handler::add_to_cart_action (20).
 */
function fares_store_buy_now_short_circuit(): void {
	if ( ! fares_store_is_buy_now_request() || ! isset( $_REQUEST['add-to-cart'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$product_id = absint( wp_unslash( $_REQUEST['add-to-cart'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $product_id || ! WC()->cart ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $item ) {
		if ( $product_id === (int) $item['product_id'] || $product_id === (int) $item['variation_id'] ) {
			unset( $_REQUEST['add-to-cart'], $_GET['add-to-cart'], $_POST['add-to-cart'] );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}
	}
}
add_action( 'wp_loaded', 'fares_store_buy_now_short_circuit', 15 );
