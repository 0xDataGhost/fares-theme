<?php
/**
 * WooCommerce price pipeline filters.
 *
 * Conversion happens at the product getters — the seam every cart, tax,
 * coupon, gateway, and display path reads from — NOT at wc_price()
 * formatting time. Converting only at format time would show local
 * prices while charging SAR.
 *
 * Stored meta (_price, _regular_price, _sale_price) is never mutated;
 * it stays SAR, the merchant's single source of truth.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Convert a raw product price value (SAR) into the active currency.
 * Empty values pass through untouched — '' means "no price set".
 *
 * @param string|float $value Raw price.
 * @return string|float
 */
function fares_currency_convert_price( $value ) {
	if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
		return $value;
	}

	$active = fares_currency_active();

	if ( FARES_CURRENCY_BASE === $active ) {
		return $value;
	}

	return fares_currency_convert( (float) $value, $active );
}

// Active currency code + symbol + formatting.
add_filter(
	'pre_option_woocommerce_currency',
	static function () {
		return fares_currency_active();
	}
);

add_filter(
	'woocommerce_currency_symbol',
	static function ( string $symbol, string $currency ): string {
		$market = fares_currency_market_for( $currency );

		return null !== $market ? $market['symbol'] : $symbol;
	},
	10,
	2
);

add_filter(
	'wc_get_price_decimals',
	static function ( int $decimals ): int {
		$market = fares_currency_market_for( fares_currency_active() );

		return null !== $market ? (int) $market['decimals'] : $decimals;
	}
);

// Simple/parent product prices.
add_filter( 'woocommerce_product_get_price', 'fares_currency_convert_price', 99 );
add_filter( 'woocommerce_product_get_regular_price', 'fares_currency_convert_price', 99 );
add_filter( 'woocommerce_product_get_sale_price', 'fares_currency_convert_price', 99 );

// Variation prices used by cart and checkout.
add_filter( 'woocommerce_product_variation_get_price', 'fares_currency_convert_price', 99 );
add_filter( 'woocommerce_product_variation_get_regular_price', 'fares_currency_convert_price', 99 );
add_filter( 'woocommerce_product_variation_get_sale_price', 'fares_currency_convert_price', 99 );

// Variation price ranges shown on variable products.
add_filter( 'woocommerce_variation_prices_price', 'fares_currency_convert_price', 99 );
add_filter( 'woocommerce_variation_prices_regular_price', 'fares_currency_convert_price', 99 );
add_filter( 'woocommerce_variation_prices_sale_price', 'fares_currency_convert_price', 99 );

/**
 * Key WooCommerce's variation-price cache by currency and rate table.
 * Without this, one visitor's converted variation prices are cached and
 * served to visitors in other currencies — the classic multi-currency bug.
 *
 * @param array $hash Hash parts.
 * @return array
 */
add_filter(
	'woocommerce_get_variation_prices_hash',
	static function ( array $hash ): array {
		$hash['fares_currency'] = fares_currency_active();
		$hash['fares_fx']       = md5( wp_json_encode( fares_currency_rates() ) );

		return $hash;
	},
	99
);

/**
 * Expose the active currency to caches and post-deploy checks, and tell
 * intermediary caches responses vary by cookie.
 */
function fares_currency_send_headers(): void {
	if ( is_admin() || headers_sent() ) {
		return;
	}

	header( 'X-Fares-Currency: ' . fares_currency_active() );
	header( 'Vary: Cookie', false );
}
add_action( 'send_headers', 'fares_currency_send_headers' );
