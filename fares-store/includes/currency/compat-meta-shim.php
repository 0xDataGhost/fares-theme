<?php
/**
 * Opt-in compatibility shim for third-party plugins that read product
 * prices from post meta (_price, _regular_price, _sale_price) directly
 * instead of going through WC_Product.
 *
 * Off by default — enable via the 'fares_currency_meta_shim' option or
 * filter. When active, frontend meta READS of the price keys return the
 * converted value; WRITES are never touched, so the stored SAR truth is
 * inviolable. WooCommerce's own data-store reads are excluded so its
 * product objects keep loading raw SAR values (which our getter filters
 * then convert exactly once).
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the shim is enabled for this site.
 */
function fares_currency_meta_shim_enabled(): bool {
	return (bool) apply_filters( 'fares_currency_meta_shim_enabled', 'yes' === get_option( 'fares_currency_meta_shim', 'no' ) );
}

/**
 * Intercept frontend reads of price meta keys and convert.
 *
 * @param mixed  $value     Short-circuit value (null = not handled).
 * @param int    $object_id Post id.
 * @param string $meta_key  Meta key.
 * @param bool   $single    Whether a single value was requested.
 * @return mixed
 */
function fares_currency_meta_shim_read( $value, $object_id, $meta_key, $single ) {
	static $handling = false;

	if ( $handling || null !== $value || ! in_array( $meta_key, array( '_price', '_regular_price', '_sale_price' ), true ) ) {
		return $value;
	}

	if ( is_admin() || FARES_CURRENCY_BASE === fares_currency_active() || ! fares_currency_meta_shim_enabled() ) {
		return $value;
	}

	// Woo's product data store reads meta while hydrating WC_Product;
	// those reads must stay raw or the getter filters would convert twice.
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 12 ) as $frame ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$class = $frame['class'] ?? '';

		if ( str_contains( $class, 'Data_Store' ) || str_contains( $class, 'WC_Product' ) ) {
			return $value;
		}

		if ( ! isset( $offender ) && '' !== $class && ! str_starts_with( $class, 'WP_' ) ) {
			$offender = $class;
		}
	}

	// Re-read the raw value with the shim disarmed, then convert.
	$handling = true;
	$raw      = get_post_meta( (int) $object_id, $meta_key, true );
	$handling = false;

	if ( '' === $raw || ! is_numeric( $raw ) ) {
		return $value;
	}

	fares_currency_meta_shim_log_once( $offender ?? ( $frame['function'] ?? 'unknown' ) );

	$converted = fares_currency_convert( (float) $raw, fares_currency_active() );

	return $single ? (string) $converted : array( (string) $converted );
}
add_filter( 'get_post_metadata', 'fares_currency_meta_shim_read', 99, 4 );

/**
 * Log the first raw-meta price consumer per day so the merchant knows
 * which integration should be fixed upstream.
 */
function fares_currency_meta_shim_log_once( string $caller ): void {
	if ( get_transient( 'fares_currency_meta_shim_logged' ) ) {
		return;
	}

	set_transient( 'fares_currency_meta_shim_logged', 1, DAY_IN_SECONDS );
	fares_currency_log(
		sprintf( 'Raw price-meta read intercepted by the compatibility shim (caller: %s). Consider updating that integration to use WC_Product::get_price().', $caller )
	);
}
