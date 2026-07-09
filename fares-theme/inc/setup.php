<?php
/**
 * Theme setup: supports, menus, image sizes, i18n.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports and menus.
 */
function fares_setup(): void {
	load_theme_textdomain( 'fares-theme', FARES_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );

	// Custom logo. Registered at the asset's native size with flex sizing so
	// WordPress serves the original file rather than a cropped intermediate —
	// the store logo is an animated GIF and a resized copy would be a single
	// static frame. Display size is capped in CSS (.fares-branding img).
	add_theme_support(
		'custom-logo',
		array(
			'height'               => 534,
			'width'                => 1100,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => true,
		)
	);

	register_nav_menus(
		array(
			'footer-links' => __( 'روابط مهمة (الفوتر)', 'fares-theme' ),
		)
	);

	// Category card artwork renders at 226×301 on desktop grid; register 2x for retina.
	add_image_size( 'fares-category-card', 452, 602, true );
}
add_action( 'after_setup_theme', 'fares_setup' );

/**
 * Guarantee an explicit `dir` on the <html> tag.
 *
 * A multilingual plugin that rewrites `language_attributes` for a secondary
 * language (TranslatePress does this for /en/) can drop the `dir` attribute.
 * Re-add it from `is_rtl()` — which the plugin flips correctly per language —
 * so `[dir="rtl"]` CSS and JS stay deterministic in both directions.
 *
 * @param string $output The language attributes string.
 * @return string
 */
function fares_ensure_html_dir( string $output ): string {
	if ( false === stripos( $output, 'dir=' ) ) {
		$output = trim( $output . ' dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '"' );
	}
	return $output;
}
add_filter( 'language_attributes', 'fares_ensure_html_dir', 99999 );
