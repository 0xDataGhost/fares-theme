<?php
/**
 * JSON-LD structured data: Organization + WebSite.
 *
 * Loaded and hooked by inc/seo/seo.php (only when no SEO plugin is active).
 * Product structured data is left to WooCommerce (WC_Structured_Data) — this
 * file deliberately does not emit Product/Offer markup to avoid duplicates.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Absolute URL of the site logo for Organization schema, or '' if none.
 */
function fares_seo_logo_url(): string {
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$src = wp_get_attachment_image_url( $logo_id, 'full' );
		if ( $src ) {
			return $src;
		}
	}

	$icon = get_site_icon_url( 512 );
	return $icon ? $icon : '';
}

/**
 * Social / contact profile URLs for schema sameAs (excludes bare email).
 *
 * @return string[]
 */
function fares_seo_same_as(): array {
	$channels = function_exists( 'fares_contact_channels' ) ? fares_contact_channels() : array();
	unset( $channels['email'] );

	return array_values( array_filter( array_map( 'esc_url_raw', $channels ) ) );
}

/**
 * Human-readable language name for a locale (the two languages the store
 * supports; empty for anything else).
 *
 * @param string $locale WordPress locale, e.g. 'ar' or 'en_US'.
 */
function fares_seo_language_name( string $locale ): string {
	$map = array(
		'ar' => 'Arabic',
		'en' => 'English',
	);
	return $map[ strtolower( substr( $locale, 0, 2 ) ) ] ?? '';
}

/**
 * Languages the site is offered in — derived from the active multilingual
 * plugin (TranslatePress) when present, else the current site locale.
 *
 * @return string[]
 */
function fares_seo_available_languages(): array {
	$languages = array();

	if ( class_exists( 'TRP_Translate_Press' ) ) {
		$settings = get_option( 'trp_settings', array() );
		$locales  = $settings['translation-languages'] ?? array();
		if ( is_array( $locales ) ) {
			foreach ( $locales as $locale ) {
				$name = fares_seo_language_name( (string) $locale );
				if ( '' !== $name ) {
					$languages[] = $name;
				}
			}
		}
	}

	if ( empty( $languages ) ) {
		$name        = fares_seo_language_name( get_locale() );
		$languages[] = '' !== $name ? $name : 'Arabic';
	}

	return array_values( array_unique( $languages ) );
}

/**
 * Emit Organization and WebSite JSON-LD in the document head.
 */
function fares_seo_schema(): void {
	$home      = home_url( '/' );
	$site_name = get_bloginfo( 'name', 'display' );

	$organization = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'name'     => $site_name,
		'url'      => $home,
	);

	$logo = fares_seo_logo_url();
	if ( '' !== $logo ) {
		$organization['logo'] = $logo;
	}

	$same_as = fares_seo_same_as();
	if ( ! empty( $same_as ) ) {
		$organization['sameAs'] = $same_as;
	}

	$channels = function_exists( 'fares_contact_channels' ) ? fares_contact_channels() : array();
	if ( ! empty( $channels['email'] ) ) {
		$organization['contactPoint'] = array(
			'@type'        => 'ContactPoint',
			'email'        => sanitize_email( $channels['email'] ),
			'contactType'  => 'customer support',
			'availableLanguage' => fares_seo_available_languages(),
		);
	}

	$website = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'name'            => $site_name,
		'url'             => $home,
		'inLanguage'      => get_bloginfo( 'language' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $home . '?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	$graph = apply_filters(
		'fares_seo_schema_graph',
		array(
			'@context' => 'https://schema.org',
			'@graph'   => array( $organization, $website ),
		)
	);

	echo '<script type="application/ld+json">'
		. wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
