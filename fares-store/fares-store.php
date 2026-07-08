<?php
/**
 * Plugin Name: Fares Store
 * Plugin URI: https://github.com/0xDataGhost/fares-theme
 * Description: Store/business logic for the Fares shop — order automation for digital code products, buy-now flow, checkout rules, and serial-number integration. Theme-independent.
 * Version: 0.1.0
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 * Text Domain: fares-store
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

define( 'FARES_STORE_VERSION', '0.1.0' );
define( 'FARES_STORE_DIR', __DIR__ );

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		require FARES_STORE_DIR . '/includes/orders.php';
		require FARES_STORE_DIR . '/includes/buy-now.php';
		require FARES_STORE_DIR . '/includes/checkout-fields.php';
		require FARES_STORE_DIR . '/includes/serials.php';
	}
);
