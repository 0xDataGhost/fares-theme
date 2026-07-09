<?php
/**
 * FX rates: storage, multi-provider refresh with fallback, and the single
 * conversion function used by every price path.
 *
 * All rates are stored as TARGET-per-1-SAR at 8 decimal places. Conversion
 * is always a single multiply — never chained through a pivot currency
 * outside a provider adapter.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

const FARES_FX_OPTION          = 'fares_currency_fx_rates';
const FARES_FX_LAST_GOOD       = 'fares_currency_fx_last_good';
const FARES_FX_LAST_REFRESH    = 'fares_currency_fx_last_refresh';
const FARES_FX_FAILURES        = 'fares_currency_fx_failures';
const FARES_FX_MANUAL_OVERRIDE = 'fares_currency_fx_manual_override';
const FARES_FX_PRECISION       = 8;
const FARES_FX_SANITY_DRIFT    = 0.30; // Reject provider rates >±30% off stored.
const FARES_FX_STALE_WARN      = 3 * DAY_IN_SECONDS;
const FARES_FX_STALE_ALERT     = 7 * DAY_IN_SECONDS;

/**
 * Current rate table, manual overrides merged on top.
 *
 * Read once per request into a static so every price on a page uses the
 * same rate even if a cron refresh lands mid-request.
 *
 * @return array<string, float> Currency code => rate per 1 SAR.
 */
function fares_currency_rates(): array {
	static $rates = null;

	if ( null !== $rates ) {
		return $rates;
	}

	$stored    = get_option( FARES_FX_OPTION, array() );
	$overrides = get_option( FARES_FX_MANUAL_OVERRIDE, array() );
	$rates     = array( FARES_CURRENCY_BASE => 1.0 );

	foreach ( fares_currency_codes() as $code ) {
		if ( FARES_CURRENCY_BASE === $code ) {
			continue;
		}

		if ( isset( $overrides[ $code ] ) && (float) $overrides[ $code ] > 0 ) {
			$rates[ $code ] = (float) $overrides[ $code ];
		} elseif ( isset( $stored[ $code ] ) && (float) $stored[ $code ] > 0 ) {
			$rates[ $code ] = (float) $stored[ $code ];
		}
	}

	return $rates;
}

/**
 * Rate for one currency, or null when genuinely unavailable.
 */
function fares_currency_rate( string $code ): ?float {
	$rates = fares_currency_rates();
	$code  = strtoupper( $code );

	return isset( $rates[ $code ] ) ? (float) $rates[ $code ] : null;
}

/**
 * Convert a SAR amount into a target currency and apply the target's
 * rounding rule. This is the only place conversion or price rounding
 * happens.
 *
 * When the rate is missing the SAR amount is returned unchanged — a
 * missing rate must never zero a price.
 */
function fares_currency_convert( float $sar_amount, string $to ): float {
	$to = strtoupper( $to );

	if ( FARES_CURRENCY_BASE === $to || 0.0 === $sar_amount ) {
		return $sar_amount;
	}

	$rate = fares_currency_rate( $to );

	if ( null === $rate ) {
		return $sar_amount;
	}

	if ( function_exists( 'bcmul' ) ) {
		$raw = (float) bcmul(
			number_format( $sar_amount, FARES_FX_PRECISION, '.', '' ),
			number_format( $rate, FARES_FX_PRECISION, '.', '' ),
			FARES_FX_PRECISION
		);
	} else {
		$raw = $sar_amount * $rate;
	}

	return fares_currency_round( $raw, $to );
}

/**
 * Apply a currency's registry rounding rule. Sign-preserving so refunds
 * round symmetrically.
 */
function fares_currency_round( float $value, string $code ): float {
	$market = fares_currency_market_for( $code );

	if ( null === $market ) {
		return round( $value, 2 );
	}

	$sign  = $value < 0 ? -1.0 : 1.0;
	$value = abs( $value );
	$rule  = $market['rounding'];

	if ( is_array( $rule ) && ! empty( $rule['nearest'] ) ) {
		$nearest = (float) $rule['nearest'];
		$value   = round( $value / $nearest ) * $nearest;
	}

	if ( is_array( $rule ) && isset( $rule['ending'] ) ) {
		// Whole-unit psychological ending, e.g. 1247 -> 1249.
		$ending = (int) $rule['ending'];
		$value  = max( 0.0, floor( $value / 10 ) * 10 + $ending );
	}

	return $sign * round( $value, (int) $market['decimals'] );
}

/**
 * Ordered provider list. Each provider returns a complete
 * code => rate-per-SAR map for the requested codes, or null on failure.
 *
 * @return array<string, callable>
 */
function fares_currency_fx_providers(): array {
	$providers = array(
		'open.er-api.com'   => 'fares_currency_fx_fetch_erapi',
		'exchangerate.host' => 'fares_currency_fx_fetch_exchangerate_host',
		'frankfurter.app'   => 'fares_currency_fx_fetch_frankfurter',
	);

	/**
	 * Filter FX providers (ordered; first complete result wins).
	 *
	 * @param array $providers Provider name => callable.
	 */
	return apply_filters( 'fares_currency_fx_providers', $providers );
}

/**
 * Refresh rates from the provider chain. Never deletes the last known
 * good rates on failure.
 *
 * @return bool Whether any provider succeeded.
 */
function fares_currency_fx_refresh(): bool {
	$wanted = array_values( array_diff( fares_currency_codes(), array( FARES_CURRENCY_BASE ) ) );

	if ( empty( $wanted ) ) {
		return true;
	}

	foreach ( fares_currency_fx_providers() as $name => $callback ) {
		$rates = is_callable( $callback ) ? $callback( $wanted ) : null;

		if ( null === $rates || ! fares_currency_fx_is_sane( $rates, $wanted ) ) {
			fares_currency_log( sprintf( 'FX provider %s failed or returned an implausible payload.', $name ) );
			continue;
		}

		$rounded = array();
		foreach ( $rates as $code => $rate ) {
			$rounded[ $code ] = round( (float) $rate, FARES_FX_PRECISION );
		}

		update_option( FARES_FX_OPTION, $rounded, false );
		update_option(
			FARES_FX_LAST_GOOD,
			array(
				'rates'    => $rounded,
				'provider' => $name,
				'time'     => time(),
			),
			false
		);
		update_option( FARES_FX_LAST_REFRESH, time(), false );
		update_option( FARES_FX_FAILURES, 0, false );

		return true;
	}

	// Whole chain failed: keep last good, count and escalate.
	$failures = (int) get_option( FARES_FX_FAILURES, 0 ) + 1;
	update_option( FARES_FX_FAILURES, $failures, false );
	fares_currency_log( sprintf( 'All FX providers failed (consecutive failures: %d). Serving last known good rates.', $failures ), 'warning' );

	$age = time() - (int) get_option( FARES_FX_LAST_REFRESH, 0 );

	if ( $failures >= 3 || $age > FARES_FX_STALE_ALERT ) {
		fares_currency_fx_alert_admin( $failures, $age );
	}

	return false;
}

/**
 * Sanity-check a provider payload: complete, positive, and not wildly
 * drifted from what we already have.
 *
 * @param array<string, float> $rates  Provider result.
 * @param string[]             $wanted Required currency codes.
 */
function fares_currency_fx_is_sane( array $rates, array $wanted ): bool {
	$current = get_option( FARES_FX_OPTION, array() );

	foreach ( $wanted as $code ) {
		if ( empty( $rates[ $code ] ) || (float) $rates[ $code ] <= 0 ) {
			return false;
		}

		if ( isset( $current[ $code ] ) && (float) $current[ $code ] > 0 ) {
			$drift = abs( (float) $rates[ $code ] - (float) $current[ $code ] ) / (float) $current[ $code ];

			if ( $drift > FARES_FX_SANITY_DRIFT ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Provider: open.er-api.com — keyless, direct SAR base.
 *
 * @param string[] $wanted Currency codes.
 * @return array<string, float>|null
 */
function fares_currency_fx_fetch_erapi( array $wanted ): ?array {
	$body = fares_currency_fx_get_json( 'https://open.er-api.com/v6/latest/' . FARES_CURRENCY_BASE );

	if ( ! is_array( $body ) || 'success' !== ( $body['result'] ?? '' ) || empty( $body['rates'] ) ) {
		return null;
	}

	return fares_currency_fx_pick( $body['rates'], $wanted );
}

/**
 * Provider: exchangerate.host — direct SAR base; access key optional via
 * the FARES_EXCHANGERATE_HOST_KEY constant.
 *
 * @param string[] $wanted Currency codes.
 * @return array<string, float>|null
 */
function fares_currency_fx_fetch_exchangerate_host( array $wanted ): ?array {
	$url = add_query_arg(
		array(
			'base'    => FARES_CURRENCY_BASE,
			'symbols' => implode( ',', $wanted ),
		),
		'https://api.exchangerate.host/latest'
	);

	if ( defined( 'FARES_EXCHANGERATE_HOST_KEY' ) && FARES_EXCHANGERATE_HOST_KEY ) {
		$url = add_query_arg( 'access_key', FARES_EXCHANGERATE_HOST_KEY, $url );
	}

	$body = fares_currency_fx_get_json( $url );

	if ( ! is_array( $body ) || empty( $body['rates'] ) ) {
		return null;
	}

	return fares_currency_fx_pick( $body['rates'], $wanted );
}

/**
 * Provider: frankfurter.app — ECB data, no SAR base. The SAR anchor is
 * computed inside this adapter only (rate = EUR->target / EUR->SAR); the
 * returned table is still SAR-based and downstream code never chains.
 *
 * @param string[] $wanted Currency codes.
 * @return array<string, float>|null
 */
function fares_currency_fx_fetch_frankfurter( array $wanted ): ?array {
	$symbols = array_unique( array_merge( $wanted, array( FARES_CURRENCY_BASE ) ) );
	$body    = fares_currency_fx_get_json(
		add_query_arg( 'symbols', implode( ',', $symbols ), 'https://api.frankfurter.app/latest' )
	);

	if ( ! is_array( $body ) || empty( $body['rates'][ FARES_CURRENCY_BASE ] ) ) {
		return null;
	}

	$eur_to_sar = (float) $body['rates'][ FARES_CURRENCY_BASE ];
	$rates      = array();

	foreach ( $wanted as $code ) {
		if ( empty( $body['rates'][ $code ] ) ) {
			return null; // Incomplete pair set — treat provider as failed.
		}

		$rates[ $code ] = (float) $body['rates'][ $code ] / $eur_to_sar;
	}

	return $rates;
}

/**
 * Fetch and decode a JSON endpoint with a hard timeout.
 *
 * @return array<string, mixed>|null
 */
function fares_currency_fx_get_json( string $url ): ?array {
	$response = wp_remote_get( $url, array( 'timeout' => 5 ) );

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	return is_array( $body ) ? $body : null;
}

/**
 * Pick only wanted codes from a provider rate table; null if any missing.
 *
 * @param array<string, mixed> $table  Provider rates.
 * @param string[]             $wanted Currency codes.
 * @return array<string, float>|null
 */
function fares_currency_fx_pick( array $table, array $wanted ): ?array {
	$rates = array();

	foreach ( $wanted as $code ) {
		if ( empty( $table[ $code ] ) || (float) $table[ $code ] <= 0 ) {
			return null;
		}

		$rates[ $code ] = (float) $table[ $code ];
	}

	return $rates;
}

/**
 * Email the site admin about a sustained FX outage.
 */
function fares_currency_fx_alert_admin( int $failures, int $age ): void {
	$last_alert = (int) get_option( 'fares_currency_fx_last_alert', 0 );

	if ( time() - $last_alert < DAY_IN_SECONDS ) {
		return; // At most one alert email per day.
	}

	update_option( 'fares_currency_fx_last_alert', time(), false );

	wp_mail(
		get_option( 'admin_email' ),
		__( '[Fares Store] Exchange-rate refresh is failing', 'fares-store' ),
		sprintf(
			/* translators: 1: consecutive failure count, 2: hours since last successful refresh. */
			__( 'All exchange-rate providers have failed %1$d times in a row. Prices are being served from rates last refreshed %2$d hours ago. Set manual override rates in WooCommerce → Settings → Fares Currency if this persists.', 'fares-store' ),
			$failures,
			(int) floor( $age / HOUR_IN_SECONDS )
		)
	);
}

/**
 * Log through WooCommerce's logger when available.
 */
function fares_currency_log( string $message, string $level = 'notice' ): void {
	if ( function_exists( 'wc_get_logger' ) ) {
		wc_get_logger()->log( $level, $message, array( 'source' => 'fares-currency' ) );
	}
}
