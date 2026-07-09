# Fares Currency Module

Location-aware multi-currency for the Fares store. Merchants author every
price in **SAR** (the base currency); visitors see and are charged in their
local currency. Day-one markets: Saudi Arabia (SAR), UAE (AED), Egypt (EGP).

## How it works

- Product prices in the database are always SAR (`_regular_price`,
  `_sale_price` meta). Nothing ever writes converted values back.
- Conversion happens at the WooCommerce product getters
  (`woocommerce_product_get_price` and friends) so cart totals, taxes,
  coupons, gateway payloads, and order storage all see the converted value.
- The active currency is resolved per request in
  [resolver.php](resolver.php): manual cookie → session → Cloudflare
  `CF-IPCountry` → `WC_Geolocation` → ipapi.co → default market.
- Once a visitor picks a currency in the switcher, that choice is sticky
  (`fares_currency_manual` cookie); geolocation never overrides it.
- Orders snapshot `_order_currency` (Woo core), `_fares_fx_rate_used`, and
  `_fares_base_currency` at checkout. Refunds and pay-retry links always
  use the order's stored currency.
- wp-admin, cron, CLI, and the `wc/v3` REST API run in SAR. The Store API
  (`wc/store`) and everything visitor-facing runs in the active currency.

## Adding a market

Extend the registry — no other code changes:

```php
add_filter( 'fares_currency_registry', function ( array $registry ): array {
	$registry['KW'] = array(
		'currency' => 'KWD',
		'symbol'   => 'د.ك',
		'decimals' => 3,
		'rounding' => null,
		'gateways' => null, // or array of allowed gateway ids
	);
	return $registry;
} );
```

The daily FX cron picks up the new currency automatically. Providers are
tried in order (open.er-api.com → exchangerate.host → frankfurter.app);
if all fail, the last known good rates keep serving and the admin is
notified. Manual override rates: WooCommerce → Settings → عملات فارس.

## Rules for integrating code

1. **Read prices through WooCommerce**: `wc_get_product( $id )->get_price()`
   and `wc_price()`. Never `get_post_meta( $id, '_price' )` — raw meta is
   SAR. (An opt-in shim for legacy plugins exists in
   [compat-meta-shim.php](compat-meta-shim.php), off by default.)
2. **Author fixed amounts in SAR**: coupons, cart fees, and any threshold
   are converted automatically at read time.
3. **Custom REST endpoints** choose their currency explicitly:
   `fares_currency_use_base( $fn )` for merchant/back-office data,
   `fares_currency_use_active( $fn )` for visitor-facing data.
4. **Never read the currency cookies directly** — always
   `fares_currency_active()`.

## Caching

Cacheable pages vary by the `fares_currency` cookie. Origin sends
`Vary: Cookie` plus an `X-Fares-Currency` debug/verification header.

- **Cloudflare Free/Pro**: use a Worker keyed on `CF-IPCountry`, or bypass
  cache when the `fares_currency` cookie is present.
- **Cloudflare Enterprise**: add the cookie to the Custom Cache Key.
- **LiteSpeed**: add `fares_currency` under Cache → Advanced → Vary Cookies.
- **WP Rocket**: add `fares_currency` to "Never Cache Cookies".
- **Nginx/Varnish**: include `$cookie_fares_currency` in the cache key.

Post-deploy check: request `/shop/` with `CF-IPCountry: SA / AE / EG` and
assert three distinct `X-Fares-Currency` responses
(automated in `tests/currency.spec.js`).
