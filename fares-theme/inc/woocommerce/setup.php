<?php
/**
 * WooCommerce theme integration.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Declare WooCommerce support.
 */
function fares_wc_setup(): void {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'fares_wc_setup' );

/**
 * Archive grid: 4 columns on desktop (Figma), CSS handles responsive.
 */
add_filter( 'loop_shop_columns', static fn(): int => 4 );

/**
 * Staged Woo-CSS migration: drop the float/width layout stylesheets now
 * (they fight the theme's grid); woocommerce-general stays until the
 * Phase-8 visual QA completes.
 *
 * @param array $styles Woo style handles.
 * @return array
 */
function fares_wc_styles( array $styles ): array {
	unset( $styles['woocommerce-layout'], $styles['woocommerce-smallscreen'] );
	return $styles;
}
add_filter( 'woocommerce_enqueue_styles', 'fares_wc_styles' );

/**
 * Replace Woo's default content wrappers with the theme's; the design has
 * no sidebar anywhere.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

add_action(
	'woocommerce_before_main_content',
	static function (): void {
		echo '<main id="primary" class="fares-container fares-wc-main">';
	}
);
add_action(
	'woocommerce_after_main_content',
	static function (): void {
		echo '</main>';
	}
);
