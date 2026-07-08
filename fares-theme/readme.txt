=== Fares Theme ===
Contributors: fares
Tags: rtl-language-support, dark, e-commerce
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.3
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Arabic-first, RTL, dark classic WooCommerce theme for digital code-delivery stores.

== Description ==

A pixel-focused classic WooCommerce theme built from a Figma design system:

* Design tokens (assets/css/base/tokens.css) are the single source of truth for every visual value.
* Hooks-first WooCommerce integration — only two template overrides ship (cart/cart.php and single-product-reviews.php); everything else, including the quantity stepper and the entire product card, is hook re-composition.
* RTL-first: all CSS uses logical properties; no rtl.css needed.
* Presentation only: business logic (buy-now, order automation, checkout rules, serial delivery glue) lives in the companion fares-store plugin.

== Requirements ==

* WooCommerce 10.x
* fares-store companion plugin (order automation, buy-now, checkout rules)
* Serial Numbers for WooCommerce (activation-code delivery)

== Development ==

See the repository root: wp-env for local development, `npm run build` for CSS/JS bundles, `npm run seed` for demo content, `npx playwright test` for the test matrix.

== Changelog ==

= 0.1.0 =
* Initial release: home, product archives, single product, cart, checkout, mobile bottom navigation.
