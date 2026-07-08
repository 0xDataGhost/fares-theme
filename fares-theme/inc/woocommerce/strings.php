<?php
/**
 * Arabic label filters (presentation strings).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add-to-cart label; out-of-stock products read "نفدت الكمية" per the design.
 *
 * @param string     $text    Button text.
 * @param WC_Product $product Product.
 * @return string
 */
function fares_add_to_cart_text( string $text, WC_Product $product ): string {
	if ( ! $product->is_in_stock() ) {
		return __( 'نفدت الكمية', 'fares-theme' );
	}

	if ( $product->is_type( 'simple' ) && $product->is_purchasable() ) {
		return __( 'أضف للسلة', 'fares-theme' );
	}

	return $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'fares_add_to_cart_text', 10, 2 );
add_filter( 'woocommerce_product_single_add_to_cart_text', 'fares_add_to_cart_text', 10, 2 );

/**
 * Archive result count: replace Woo's "Showing all X results" with the
 * design's "منتجات N" heading.
 */
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

/**
 * Render the product count heading.
 */
function fares_result_count(): void {
	global $wp_query;
	$total = (int) $wp_query->found_posts;
	printf(
		'<h1 class="fares-result-count">%s</h1>',
		/* translators: %s: number of products. */
		esc_html( sprintf( __( 'منتجات %s', 'fares-theme' ), number_format_i18n( $total ) ) )
	);
}
add_action( 'woocommerce_before_shop_loop', 'fares_result_count', 20 );
