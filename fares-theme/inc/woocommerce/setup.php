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
 * Woo-CSS migration complete (Phase 8): the theme owns all WooCommerce
 * styling — stars/notices/gallery replacements live in wc-core.css.
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

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

/**
 * Dark-theme image placeholder. Image-less products otherwise render
 * WooCommerce's white placeholder, which reads as a broken white box on the
 * dark cards; this swaps in a muted, surface-matched SVG.
 *
 * Covers both code paths: the `_img_src` filter handles direct-src callers,
 * while `_img` rewrites the final <img> — needed because a configured
 * placeholder *attachment* makes get_image() bypass the src filter entirely.
 */
add_filter(
	'woocommerce_placeholder_img_src',
	static fn(): string => FARES_THEME_URI . '/assets/images/placeholder.svg'
);

/**
 * Repoint the rendered placeholder <img> at the theme SVG, keeping any classes
 * (e.g. fares-card__image) and dropping the raster srcset/sizes.
 *
 * @param string $image The placeholder <img> HTML.
 * @return string
 */
function fares_placeholder_img_html( string $image ): string {
	$src   = esc_url( FARES_THEME_URI . '/assets/images/placeholder.svg' );
	$image = preg_replace( '/\ssrc="[^"]*"/', ' src="' . $src . '"', $image, 1 );
	return preg_replace( '/\s(?:srcset|sizes)="[^"]*"/', '', (string) $image );
}
add_filter( 'woocommerce_placeholder_img', 'fares_placeholder_img_html', 10 );
