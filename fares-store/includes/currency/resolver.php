<?php
/**
 * Active-currency resolution: request context rules, sticky visitor
 * cookies, and the geolocation chain.
 *
 * Resolution order (first match wins):
 *  1. Explicit context override (fares_currency_use_base()/use_active()).
 *  2. Cron / CLI / admin / wc-api / wc/v3 REST  -> base (SAR).
 *  3. Order-pay and payment-retry requests      -> the order's currency.
 *  4. Known crawlers without a cookie           -> base (SAR).
 *  5. Manual cookie (fares_currency_manual=1)   -> cookie, geolocation never runs.
 *  6. Plain cookie / WC session                 -> stored value.
 *  7. Geolocation chain                         -> CF header, WC_Geolocation, ipapi.co.
 *  8. Registry default market.
 *
 * No other file may read the currency cookies directly.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

const FARES_CURRENCY_COOKIE        = 'fares_currency';
const FARES_CURRENCY_MANUAL_COOKIE = 'fares_currency_manual';
const FARES_CURRENCY_COOKIE_TTL    = 365 * DAY_IN_SECONDS;

/**
 * The currency code every price on this request uses.
 */
function fares_currency_active(): string {
	static $resolved = null;

	$override = fares_currency_context_override();
	if ( null !== $override ) {
		return $override;
	}

	if ( null !== $resolved ) {
		return $resolved;
	}

	$resolved = fares_currency_resolve();

	return $resolved;
}

/**
 * Run a callable with the base currency forced, regardless of visitor
 * state. For admin tasks, exports, and custom REST endpoints.
 *
 * @param callable $callback Callback to run.
 * @return mixed The callback's return value.
 */
function fares_currency_use_base( callable $callback ) {
	return fares_currency_with_override( FARES_CURRENCY_BASE, $callback );
}

/**
 * Run a callable with a specific currency forced (defaults to the
 * visitor-resolved one). For endpoints that must ignore context rules.
 *
 * @param callable $callback Callback to run.
 * @param string   $code     Currency code, empty for the visitor currency.
 * @return mixed The callback's return value.
 */
function fares_currency_use_active( callable $callback, string $code = '' ) {
	$code = $code ? strtoupper( $code ) : fares_currency_resolve_visitor();

	return fares_currency_with_override( $code, $callback );
}

/**
 * Internal override stack shared by the helpers above.
 *
 * @param string|null $push Currency to push, or null to read the top.
 * @param bool        $pop  Pop the top entry.
 */
function fares_currency_context_override( ?string $push = null, bool $pop = false ): ?string {
	static $stack = array();

	if ( $pop ) {
		array_pop( $stack );
		return null;
	}

	if ( null !== $push ) {
		$stack[] = $push;
		return null;
	}

	return $stack ? end( $stack ) : null;
}

/**
 * Push an override, run the callback, always pop.
 *
 * @param string   $code     Currency code.
 * @param callable $callback Callback to run.
 * @return mixed
 */
function fares_currency_with_override( string $code, callable $callback ) {
	fares_currency_context_override( $code );

	try {
		return $callback();
	} finally {
		fares_currency_context_override( null, true );
	}
}

/**
 * Full context-aware resolution. Called once per request.
 */
function fares_currency_resolve(): string {
	// Merchant surfaces and machine contexts always run in base currency.
	if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return FARES_CURRENCY_BASE;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return fares_currency_admin_currency();
	}

	if ( wp_doing_ajax() && fares_currency_is_admin_ajax_action() ) {
		return FARES_CURRENCY_BASE;
	}

	// Gateway callbacks (wc-api) verify against order data which carries
	// its own currency; the ambient currency stays base.
	if ( ! empty( $_GET['wc-api'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return FARES_CURRENCY_BASE;
	}

	// WooCommerce REST (wc/v3) is a merchant/back-office surface: base
	// currency unless the consumer opts in via ?currency=.
	$rest_route = fares_currency_rest_route();
	if ( '' !== $rest_route && str_contains( $rest_route, '/wc/v' ) ) {
		$requested = isset( $_GET['currency'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['currency'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return fares_currency_is_supported( $requested ) ? $requested : FARES_CURRENCY_BASE;
	}

	// Pay-for-order / payment retry: lock to the order's stored currency
	// so the retry total always matches what the gateway will charge.
	$order_currency = fares_currency_order_context_currency();
	if ( null !== $order_currency ) {
		return $order_currency;
	}

	return fares_currency_resolve_visitor();
}

/**
 * Visitor-facing resolution: cookies, session, geolocation, default.
 */
function fares_currency_resolve_visitor(): string {
	$cookie = fares_currency_cookie_value();

	// Sticky manual choice: geolocation never overrides it.
	if ( null !== $cookie ) {
		return $cookie;
	}

	// Crawlers with no cookie get a stable, indexable currency.
	if ( fares_currency_is_crawler() ) {
		return FARES_CURRENCY_BASE;
	}

	$session = fares_currency_session_value();
	if ( null !== $session ) {
		return $session;
	}

	$country = fares_currency_geolocate();

	return fares_currency_for_country( $country );
}

/**
 * Validated currency from the visitor cookie, or null.
 */
function fares_currency_cookie_value(): ?string {
	if ( empty( $_COOKIE[ FARES_CURRENCY_COOKIE ] ) ) {
		return null;
	}

	$code = strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ FARES_CURRENCY_COOKIE ] ) ) );

	return fares_currency_is_supported( $code ) ? $code : null;
}

/**
 * Whether the visitor locked their currency manually via the switcher.
 */
function fares_currency_is_manual(): bool {
	return ! empty( $_COOKIE[ FARES_CURRENCY_MANUAL_COOKIE ] ) && null !== fares_currency_cookie_value();
}

/**
 * Validated currency mirrored in the WooCommerce session, or null.
 */
function fares_currency_session_value(): ?string {
	if ( ! function_exists( 'WC' ) || null === WC()->session ) {
		return null;
	}

	$code = (string) WC()->session->get( FARES_CURRENCY_COOKIE, '' );

	return fares_currency_is_supported( $code ) ? strtoupper( $code ) : null;
}

/**
 * Persist the visitor's currency: cookie now, WC session when available.
 */
function fares_currency_persist( string $code, bool $manual = false ): void {
	$code = strtoupper( $code );

	if ( ! fares_currency_is_supported( $code ) ) {
		return;
	}

	if ( ! headers_sent() ) {
		$args = array(
			'expires'  => time() + FARES_CURRENCY_COOKIE_TTL,
			'path'     => '/',
			'secure'   => is_ssl(),
			'httponly' => false, // The switcher JS reads it to mark the active option.
			'samesite' => 'Lax',
		);

		setcookie( FARES_CURRENCY_COOKIE, $code, $args );

		if ( $manual ) {
			setcookie( FARES_CURRENCY_MANUAL_COOKIE, '1', $args );
		}
	}

	$_COOKIE[ FARES_CURRENCY_COOKIE ] = $code;
	if ( $manual ) {
		$_COOKIE[ FARES_CURRENCY_MANUAL_COOKIE ] = '1';
	}

	if ( function_exists( 'WC' ) && null !== WC()->session ) {
		WC()->session->set( FARES_CURRENCY_COOKIE, $code );
	}
}

/**
 * Delete both currency cookies — back to full auto-detection.
 */
function fares_currency_reset(): void {
	if ( ! headers_sent() ) {
		$args = array(
			'expires'  => time() - HOUR_IN_SECONDS,
			'path'     => '/',
			'secure'   => is_ssl(),
			'samesite' => 'Lax',
		);
		setcookie( FARES_CURRENCY_COOKIE, '', $args );
		setcookie( FARES_CURRENCY_MANUAL_COOKIE, '', $args );
	}

	unset( $_COOKIE[ FARES_CURRENCY_COOKIE ], $_COOKIE[ FARES_CURRENCY_MANUAL_COOKIE ] );

	if ( function_exists( 'WC' ) && null !== WC()->session ) {
		WC()->session->set( FARES_CURRENCY_COOKIE, null );
	}
}

/**
 * Geolocation chain: Cloudflare header, WooCommerce/MaxMind, ipapi.co.
 * The external lookup is cached per IP for a day.
 *
 * @return string ISO country code ('' when everything misses).
 */
function fares_currency_geolocate(): string {
	if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) && 'XX' !== $_SERVER['HTTP_CF_IPCOUNTRY'] ) {
		return strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
	}

	$ip = class_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : '';

	if ( '' === $ip ) {
		return '';
	}

	$cache_key = 'fares_geo_' . sha1( $ip );
	$cached    = get_transient( $cache_key );

	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}

	$country = '';

	if ( class_exists( 'WC_Geolocation' ) ) {
		$geo     = WC_Geolocation::geolocate_ip( $ip, false, false );
		$country = strtoupper( (string) ( $geo['country'] ?? '' ) );
	}

	if ( '' === $country ) {
		$response = wp_remote_get( 'https://ipapi.co/' . rawurlencode( $ip ) . '/country/', array( 'timeout' => 2 ) );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = strtoupper( trim( wp_remote_retrieve_body( $response ) ) );

			if ( 2 === strlen( $body ) && ctype_alpha( $body ) ) {
				$country = $body;
			}
		}
	}

	// Cache misses too (as the default country) so bots can't force
	// repeated external lookups.
	set_transient( $cache_key, '' !== $country ? $country : fares_currency_default_country(), DAY_IN_SECONDS );

	return $country;
}

/**
 * Currency for wp-admin screens: always base, except when editing an
 * order placed in another currency — there the order's own currency
 * keeps line-item edits consistent with its totals.
 */
function fares_currency_admin_currency(): string {
	$order_id = 0;

	// Classic post editor and HPOS order screen both expose the id in the URL.
	if ( isset( $_GET['post'], $_GET['action'] ) && 'edit' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id = (int) $_GET['post']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( isset( $_GET['page'], $_GET['id'] ) && 'wc-orders' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id = (int) $_GET['id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );

		if ( $order instanceof WC_Order && fares_currency_is_supported( $order->get_currency() ) ) {
			return strtoupper( $order->get_currency() );
		}
	}

	return FARES_CURRENCY_BASE;
}

/**
 * The order currency for pay-for-order / payment-retry requests, or null.
 */
function fares_currency_order_context_currency(): ?string {
	$order_id = 0;

	if ( isset( $_GET['pay_for_order'], $_GET['key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( preg_match( '#/order-pay/(\d+)#', $uri, $m ) ) {
			$order_id = (int) $m[1];
		}
	}

	if ( $order_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
		return null;
	}

	$order = wc_get_order( $order_id );
	$key   = sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! $order instanceof WC_Order || ! hash_equals( $order->get_order_key(), $key ) ) {
		return null;
	}

	$currency = strtoupper( $order->get_currency() );

	return fares_currency_is_supported( $currency ) ? $currency : null;
}

/**
 * Whether the current admin-ajax action belongs to the merchant back
 * office (product editor, order editor, wc-admin) rather than the
 * storefront.
 */
function fares_currency_is_admin_ajax_action(): bool {
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( '' === $action ) {
		return false;
	}

	$admin_actions = array(
		'woocommerce_json_search_products',
		'woocommerce_json_search_products_and_variations',
		'woocommerce_add_variation',
		'woocommerce_bulk_edit_variations',
		'woocommerce_load_variations',
		'woocommerce_save_variations',
		'woocommerce_add_order_item',
		'woocommerce_calc_line_taxes',
		'woocommerce_get_order_details',
		'inline-save',
	);

	/**
	 * Filter the admin-ajax actions that run in the base currency.
	 *
	 * @param string[] $admin_actions Action names.
	 */
	$admin_actions = apply_filters( 'fares_currency_admin_ajax_actions', $admin_actions );

	return in_array( $action, $admin_actions, true );
}

/**
 * Current REST route, or '' outside REST requests.
 */
function fares_currency_rest_route(): string {
	if ( isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
		return (string) $GLOBALS['wp']->query_vars['rest_route'];
	}

	$prefix = '/' . rest_get_url_prefix() . '/';
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$pos    = strpos( $uri, $prefix );

	if ( false === $pos ) {
		return '';
	}

	return '/' . substr( (string) wp_parse_url( $uri, PHP_URL_PATH ), $pos + strlen( $prefix ) );
}

/**
 * Cheap crawler check — only consulted when no currency cookie exists.
 */
function fares_currency_is_crawler(): bool {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';

	return '' !== $ua && (bool) preg_match( '/bot|crawl|spider|slurp|bingpreview|duckduck|yandex|baidu/', $ua );
}

/**
 * Handle the no-JS switcher (?fares_currency=EGP) and the reset param
 * (?fares_currency_reset=1), then redirect to the clean URL.
 */
function fares_currency_handle_switch_request(): void {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	$redirect = false;

	if ( isset( $_GET['fares_currency_reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		fares_currency_reset();
		$redirect = true;
	} elseif ( isset( $_GET[ FARES_CURRENCY_COOKIE ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code = strtoupper( sanitize_text_field( wp_unslash( $_GET[ FARES_CURRENCY_COOKIE ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( fares_currency_is_supported( $code ) ) {
			fares_currency_persist( $code, true );
			$redirect = true;
		}
	}

	if ( $redirect && ! headers_sent() ) {
		wp_safe_redirect( remove_query_arg( array( FARES_CURRENCY_COOKIE, 'fares_currency_reset' ) ) );
		exit;
	}
}
add_action( 'init', 'fares_currency_handle_switch_request', 1 );

/**
 * First storefront hit with no cookie: persist the geo-resolved currency
 * so subsequent requests skip geolocation, and mirror into the WC
 * session once it exists.
 */
function fares_currency_prime_session(): void {
	if ( is_admin() || wp_doing_cron() || fares_currency_is_crawler() ) {
		return;
	}

	$active = fares_currency_active();

	if ( null === fares_currency_cookie_value() ) {
		fares_currency_persist( $active );
	} elseif ( function_exists( 'WC' ) && null !== WC()->session && null === fares_currency_session_value() ) {
		WC()->session->set( FARES_CURRENCY_COOKIE, $active );
	}
}
add_action( 'woocommerce_init', 'fares_currency_prime_session', 20 );
