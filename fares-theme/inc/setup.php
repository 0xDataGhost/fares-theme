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

	register_nav_menus(
		array(
			'footer-links' => __( 'روابط مهمة (الفوتر)', 'fares-theme' ),
		)
	);

	// Category card artwork renders at 226×301 on desktop grid; register 2x for retina.
	add_image_size( 'fares-category-card', 452, 602, true );
}
add_action( 'after_setup_theme', 'fares_setup' );
