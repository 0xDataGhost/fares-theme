<?php
/**
 * Declarative asset manifest.
 *
 * Every stylesheet/script the theme loads is declared here; the loader in
 * assets.php consumes this — never add enqueue code elsewhere.
 *
 * Entry shape:
 *   'handle' => array(
 *       'src'     => path relative to the theme root,
 *       'deps'    => array of handles,
 *       'type'    => 'style' | 'script',
 *       'when'    => callable returning bool (conditional loading; null = always),
 *       'defer'   => bool (scripts only — loading strategy),
 *       'preload' => bool (styles/fonts hinted in <head>),
 *   )
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * The asset manifest.
 *
 * @return array<string, array<string, mixed>>
 */
function fares_asset_manifest(): array {
	return array(
		'fares-tokens'     => array(
			'src'  => 'assets/css/base/tokens.css',
			'deps' => array(),
			'type' => 'style',
			'when' => null,
		),
		'fares-global'     => array(
			'src'  => 'assets/css/dist/global.css',
			'deps' => array( 'fares-tokens' ),
			'type' => 'style',
			'when' => null,
		),
		'fares-home'       => array(
			'src'  => 'assets/css/dist/home.css',
			'deps' => array( 'fares-tokens' ),
			'type' => 'style',
			'when' => static fn(): bool => is_front_page(),
		),
		'fares-global-js'  => array(
			'src'   => 'assets/js/global.js',
			'deps'  => array(),
			'type'  => 'script',
			'when'  => null,
			'defer' => true,
		),
		'fares-product'    => array(
			'src'  => 'assets/css/dist/product.css',
			'deps' => array( 'fares-tokens' ),
			'type' => 'style',
			'when' => static fn(): bool => is_product() || is_cart(),
		),
		'fares-cart'       => array(
			'src'  => 'assets/css/dist/cart.css',
			'deps' => array( 'fares-tokens' ),
			'type' => 'style',
			'when' => static fn(): bool => is_cart(),
		),
		'fares-checkout'   => array(
			'src'  => 'assets/css/dist/checkout.css',
			'deps' => array( 'fares-tokens' ),
			'type' => 'style',
			'when' => static fn(): bool => is_checkout(),
		),
		'fares-account'    => array(
			'src'  => 'assets/css/dist/account.css',
			'deps' => array( 'fares-tokens' ),
			'type' => 'style',
			'when' => static fn(): bool => function_exists( 'is_account_page' ) && is_account_page(),
		),
		'fares-cart-js'    => array(
			'src'   => 'assets/js/dist/cart.js',
			'deps'  => array(),
			'type'  => 'script',
			'when'  => static fn(): bool => is_cart(),
			'defer' => true,
		),
		'fares-carousel'   => array(
			'src'   => 'assets/js/dist/carousel.js',
			'deps'  => array(),
			'type'  => 'script',
			'when'  => static fn(): bool => is_front_page() || is_product() || is_woocommerce(),
			'defer' => true,
		),
		'fares-product-js' => array(
			'src'   => 'assets/js/dist/product.js',
			'deps'  => array(),
			'type'  => 'script',
			'when'  => static fn(): bool => is_product() || is_cart(),
			'defer' => true,
		),
		'fares-atc-js'     => array(
			// Depends on jQuery for WooCommerce's `added_to_cart` event.
			'src'   => 'assets/js/dist/add-to-cart-popup.js',
			'deps'  => array( 'jquery' ),
			'type'  => 'script',
			'when'  => static fn(): bool => is_front_page() || is_woocommerce(),
			'defer' => true,
		),
	);
}
