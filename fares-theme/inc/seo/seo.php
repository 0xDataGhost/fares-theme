<?php
/**
 * SEO / meta layer bootstrap.
 *
 * Presentation-only head output: meta description, Open Graph, Twitter Card,
 * canonical URL, and Organization/WebSite JSON-LD. Product structured data is
 * intentionally NOT emitted here — WooCommerce already outputs Product JSON-LD
 * via WC_Structured_Data, and duplicating it would create conflicting markup.
 *
 * All output is suppressed when a dedicated SEO plugin is active
 * (see fares_seo_plugin_active), so the theme never fights Yoast / Rank Math /
 * AIOSEO / SEOPress / The SEO Framework / Slim SEO over the same tags.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

require FARES_THEME_DIR . '/inc/seo/meta-tags.php';
require FARES_THEME_DIR . '/inc/seo/schema.php';

/**
 * Is a full-featured SEO plugin handling meta/schema output?
 *
 * When true, the theme steps aside to avoid duplicate <meta>/<link>/JSON-LD.
 * Filterable so the behaviour can be forced either way if needed.
 */
function fares_seo_plugin_active(): bool {
	$active = defined( 'WPSEO_VERSION' )              // Yoast SEO.
		|| defined( 'RANK_MATH_VERSION' )             // Rank Math.
		|| defined( 'AIOSEO_VERSION' )                // All in One SEO.
		|| defined( 'SEOPRESS_VERSION' )              // SEOPress.
		|| defined( 'THE_SEO_FRAMEWORK_VERSION' )     // The SEO Framework.
		|| defined( 'SLIM_SEO_VER' );                 // Slim SEO.

	return (bool) apply_filters( 'fares_seo_plugin_active', $active );
}

/**
 * Register head output, unless a dedicated SEO plugin owns it.
 */
function fares_seo_boot(): void {
	if ( fares_seo_plugin_active() ) {
		return;
	}

	add_action( 'wp_head', 'fares_seo_meta_tags', 1 );
	add_action( 'wp_head', 'fares_seo_schema', 2 );
	add_filter( 'wp_robots', 'fares_seo_robots' );
}
add_action( 'wp', 'fares_seo_boot' );

/**
 * Fallback favicon: emit icon links only when the site has no core Site Icon
 * set AND the theme ships a favicon asset. A no-op until either exists, so it
 * never overrides a Customizer-configured Site Icon or an SEO plugin.
 */
function fares_seo_favicon_fallback(): void {
	if ( has_site_icon() ) {
		return;
	}

	$favicon = FARES_THEME_DIR . '/assets/images/favicon.png';
	if ( ! file_exists( $favicon ) ) {
		return;
	}

	$url = FARES_THEME_URI . '/assets/images/favicon.png';
	printf(
		'<link rel="icon" href="%1$s" sizes="any" />' . "\n" .
		'<link rel="apple-touch-icon" href="%1$s" />' . "\n",
		esc_url( $url )
	);
}
add_action( 'wp_head', 'fares_seo_favicon_fallback', 3 );
