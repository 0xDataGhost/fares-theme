<?php
/**
 * Currency registry — the single source of truth for supported markets.
 *
 * Merchants author all prices in the base currency (SAR). Every other
 * currency is derived at request time from an FX rate. Markets are keyed
 * by ISO 3166-1 alpha-2 country code; currencies by ISO 4217 code.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

const FARES_CURRENCY_BASE = 'SAR';

/**
 * Supported markets, filterable so new countries can be added without
 * editing plugin code.
 *
 * Registry entry shape:
 * - currency  ISO 4217 code charged and displayed.
 * - symbol    Display symbol.
 * - decimals  Price decimals for display and rounding.
 * - rounding  null for native rounding, or array with optional keys:
 *             'nearest' (round to multiple, e.g. 0.25) and
 *             'ending'  (whole-unit psychological ending, e.g. 9).
 * - gateways  Optional allowlist of gateway ids for this currency.
 *
 * @return array<string, array<string, mixed>>
 */
function fares_currency_registry(): array {
	$registry = array(
		'SA' => array(
			'currency' => 'SAR',
			'symbol'   => 'ر.س',
			'decimals' => 2,
			'rounding' => null,
			'gateways' => null,
		),
		'AE' => array(
			'currency' => 'AED',
			'symbol'   => 'د.إ',
			'decimals' => 2,
			'rounding' => array( 'nearest' => 0.25 ),
			'gateways' => null,
		),
		'EG' => array(
			'currency' => 'EGP',
			'symbol'   => 'ج.م',
			'decimals' => 0,
			'rounding' => array(
				'nearest' => 1,
				'ending'  => 9,
			),
			'gateways' => null,
		),
	);

	/**
	 * Filter the market registry.
	 *
	 * @param array $registry Country code => market config.
	 */
	return apply_filters( 'fares_currency_registry', $registry );
}

/**
 * Default market used when geolocation fails or resolves to an
 * unsupported country.
 */
function fares_currency_default_country(): string {
	return (string) apply_filters( 'fares_currency_default_country', 'SA' );
}

/**
 * All supported currency codes (base first, no duplicates).
 *
 * @return string[]
 */
function fares_currency_codes(): array {
	$codes = array( FARES_CURRENCY_BASE );

	foreach ( fares_currency_registry() as $market ) {
		$codes[] = $market['currency'];
	}

	return array_values( array_unique( $codes ) );
}

/**
 * Whether a currency code is supported.
 */
function fares_currency_is_supported( string $code ): bool {
	return in_array( strtoupper( $code ), fares_currency_codes(), true );
}

/**
 * Market config for a currency code (first market using it), or null.
 *
 * @return array<string, mixed>|null
 */
function fares_currency_market_for( string $code ): ?array {
	$code = strtoupper( $code );

	if ( FARES_CURRENCY_BASE === $code ) {
		// The base currency always exists even if no market maps to it.
		foreach ( fares_currency_registry() as $market ) {
			if ( $market['currency'] === $code ) {
				return $market;
			}
		}

		return array(
			'currency' => FARES_CURRENCY_BASE,
			'symbol'   => 'ر.س',
			'decimals' => 2,
			'rounding' => null,
			'gateways' => null,
		);
	}

	foreach ( fares_currency_registry() as $market ) {
		if ( $market['currency'] === $code ) {
			return $market;
		}
	}

	return null;
}

/**
 * Currency code for a country, falling back to the default market.
 */
function fares_currency_for_country( string $country ): string {
	$registry = fares_currency_registry();
	$country  = strtoupper( $country );

	if ( isset( $registry[ $country ] ) ) {
		return $registry[ $country ]['currency'];
	}

	$default = fares_currency_default_country();

	return $registry[ $default ]['currency'] ?? FARES_CURRENCY_BASE;
}
