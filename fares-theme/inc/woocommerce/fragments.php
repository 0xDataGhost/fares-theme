<?php
/**
 * Cart count badge fragment (display concern).
 *
 * wc-cart-fragments is NOT auto-enqueued since WC 7.8 — the manifest
 * enqueues it where the badge renders. The payload is trimmed to the badge
 * markup only.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the cart count badge (shared by header + mobile nav).
 */
function fares_cart_count_badge(): void {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	printf(
		'<span class="fares-cart-badge%s" aria-label="%s">%s</span>',
		$count > 0 ? '' : ' fares-cart-badge--empty',
		/* translators: %d: number of items in cart. */
		esc_attr( sprintf( _n( '%d منتج في السلة', '%d منتجات في السلة', $count, 'fares-theme' ), $count ) ),
		esc_html( number_format_i18n( $count ) )
	);
}

/**
 * Keep the badge fresh on cached pages — badge-only payload.
 *
 * @param array $fragments Cart fragments.
 * @return array
 */
function fares_cart_badge_fragment( array $fragments ): array {
	ob_start();
	fares_cart_count_badge();
	$fragments['.fares-cart-badge'] = ob_get_clean();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'fares_cart_badge_fragment' );

/**
 * Enqueue wc-cart-fragments wherever the badge renders (site-wide chrome).
 */
function fares_enqueue_cart_fragments(): void {
	if ( ! is_cart() && ! is_checkout() ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', 'fares_enqueue_cart_fragments', 20 );
