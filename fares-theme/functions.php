<?php
/**
 * Fares Theme bootstrap.
 *
 * Presentation only: display helpers, hooks re-composition, asset loading.
 * Business logic (orders, buy-now, checkout rules, serials) lives in the
 * fares-store companion plugin.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

define( 'FARES_THEME_VERSION', '0.1.0' );
define( 'FARES_THEME_DIR', get_template_directory() );
define( 'FARES_THEME_URI', get_template_directory_uri() );

require FARES_THEME_DIR . '/inc/setup.php';
require FARES_THEME_DIR . '/inc/template-tags.php';
require FARES_THEME_DIR . '/inc/assets/manifest.php';
require FARES_THEME_DIR . '/inc/assets/assets.php';
require FARES_THEME_DIR . '/inc/queries/products.php';
require FARES_THEME_DIR . '/inc/content/cpt-testimonials.php';
require FARES_THEME_DIR . '/inc/content/term-meta.php';

if ( class_exists( 'WooCommerce' ) ) {
	require FARES_THEME_DIR . '/inc/woocommerce/setup.php';
	require FARES_THEME_DIR . '/inc/woocommerce/hooks.php';
	require FARES_THEME_DIR . '/inc/woocommerce/fragments.php';
	require FARES_THEME_DIR . '/inc/woocommerce/strings.php';
	require FARES_THEME_DIR . '/inc/woocommerce/checkout.php';
	require FARES_THEME_DIR . '/inc/woocommerce/add-to-cart-modal.php';
}
