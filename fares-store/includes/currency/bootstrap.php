<?php
/**
 * Location-aware multi-currency: bootstrap.
 *
 * Merchants author every price in SAR; visitors see and are charged in
 * their local currency (EGP / SAR / AED, extensible via the registry).
 * See each module for its slice of the architecture.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/registry.php';
require __DIR__ . '/rates.php';
require __DIR__ . '/resolver.php';
require __DIR__ . '/price-hooks.php';
require __DIR__ . '/cart-hooks.php';
require __DIR__ . '/compat-meta-shim.php';
require __DIR__ . '/switcher.php';
require __DIR__ . '/admin.php';
