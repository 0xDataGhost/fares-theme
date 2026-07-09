<?php
/**
 * SEO meta tags: description, canonical, Open Graph, Twitter Card.
 *
 * Loaded and hooked by inc/seo/seo.php (only when no SEO plugin is active).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

const FARES_SEO_DESC_LENGTH = 160;

/**
 * Base canonical URL (page 1, default language) for the current request, or ''
 * when it can't be resolved.
 */
function fares_seo_canonical_base(): string {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		$permalink = get_permalink();
		return $permalink ? $permalink : '';
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );
			return is_wp_error( $link ) ? '' : $link;
		}
	}
	if ( is_post_type_archive() ) {
		$link = get_post_type_archive_link( get_query_var( 'post_type' ) );
		return $link ? $link : '';
	}
	if ( is_home() ) {
		$blog_id = (int) get_option( 'page_for_posts' );
		return $blog_id ? (string) get_permalink( $blog_id ) : home_url( '/' );
	}

	return '';
}

/**
 * Convert a default-language URL to the current language when a multilingual
 * plugin (TranslatePress) is active; otherwise a no-op.
 */
function fares_seo_localize_url( string $url ): string {
	if ( '' === $url || ! class_exists( 'TRP_Translate_Press' ) ) {
		return $url;
	}

	$trp = TRP_Translate_Press::get_trp_instance();
	if ( ! $trp ) {
		return $url;
	}

	$converter = $trp->get_component( 'url_converter' );
	if ( ! is_object( $converter ) || ! method_exists( $converter, 'get_url_for_language' ) ) {
		return $url;
	}

	global $TRP_LANGUAGE;
	$language  = $TRP_LANGUAGE ? $TRP_LANGUAGE : get_locale();
	$localized = (string) $converter->get_url_for_language( $language, $url );

	// TP appends a #TRPLINKPROCESSED marker it later strips from href attributes,
	// but not from <meta content> values — remove it so og:url stays clean.
	$localized = str_replace( '#TRPLINKPROCESSED', '', $localized );

	return '' !== $localized ? $localized : $url;
}

/**
 * Canonical URL for the current request — self-referencing, pagination- and
 * language-aware — or '' when it can't be resolved.
 */
function fares_seo_canonical_url(): string {
	$base = fares_seo_canonical_base();
	if ( '' === $base ) {
		return '';
	}

	// Self-reference paginated archives (shop/category/blog) to their own page,
	// not page 1, so deeper pages stay indexable.
	$paged = (int) get_query_var( 'paged' );
	if ( ! is_singular() && $paged > 1 ) {
		$paged_url = get_pagenum_link( $paged, false );
		if ( $paged_url ) {
			$base = $paged_url;
		}
	}

	return fares_seo_localize_url( $base );
}

/**
 * Truncate plain text to a meta-description-friendly length (Arabic-aware).
 */
function fares_seo_trim( string $text ): string {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $text ) ) ) );

	if ( '' === $text || mb_strlen( $text ) <= FARES_SEO_DESC_LENGTH ) {
		return $text;
	}

	$clipped = mb_substr( $text, 0, FARES_SEO_DESC_LENGTH );
	$space   = mb_strrpos( $clipped, ' ' );
	if ( false !== $space && $space > 0 ) {
		$clipped = mb_substr( $clipped, 0, $space );
	}

	return $clipped . '…';
}

/**
 * Best available meta description for the current context.
 */
function fares_seo_description(): string {
	$description = '';

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			if ( function_exists( 'wc_get_product' ) && 'product' === $post->post_type ) {
				$product = wc_get_product( $post );
				if ( $product ) {
					$description = $product->get_short_description()
						? $product->get_short_description()
						: $product->get_description();
				}
			}
			if ( '' === $description ) {
				$description = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
			}
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$description = term_description( $term );
		}
	}

	$description = fares_seo_trim( (string) $description );

	if ( '' === $description ) {
		$description = get_bloginfo( 'description', 'display' );
	}

	return (string) apply_filters( 'fares_seo_description', $description );
}

/**
 * Representative share image URL for the current context.
 */
function fares_seo_image(): string {
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_url( (int) get_post_thumbnail_id(), 'full' );
		if ( $src ) {
			return $src;
		}
	}

	$icon = get_site_icon_url( 512 );
	if ( $icon ) {
		return $icon;
	}

	$fallback = FARES_THEME_DIR . '/assets/images/figma/hero-banner.jpg';
	return file_exists( $fallback ) ? FARES_THEME_URI . '/assets/images/figma/hero-banner.jpg' : '';
}

/**
 * Open Graph object type for the current context.
 */
function fares_seo_og_type(): string {
	if ( is_singular( 'product' ) ) {
		return 'product';
	}
	if ( is_singular( array( 'post' ) ) ) {
		return 'article';
	}
	return 'website';
}

/**
 * Map the WordPress locale to an OG locale (e.g. ar -> ar_AR).
 */
function fares_seo_og_locale(): string {
	$locale = get_locale();
	if ( false === strpos( $locale, '_' ) ) {
		return $locale . '_' . strtoupper( $locale );
	}
	return $locale;
}

/**
 * Emit the meta description, canonical link, and Open Graph / Twitter tags.
 */
function fares_seo_meta_tags(): void {
	$title       = wp_get_document_title();
	$description = fares_seo_description();
	$canonical   = fares_seo_canonical_url();
	$image       = fares_seo_image();
	$site_name   = get_bloginfo( 'name', 'display' );

	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
	}

	if ( '' !== $canonical ) {
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
	}

	printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( fares_seo_og_type() ) );
	printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( $site_name ) );
	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:locale" content="%s" />' . "\n", esc_attr( fares_seo_og_locale() ) );

	if ( '' !== $description ) {
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
	}
	if ( '' !== $canonical ) {
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $canonical ) );
	}

	if ( '' !== $image ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
		printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image ) );
	}

	printf( '<meta name="twitter:card" content="%s" />' . "\n", '' !== $image ? 'summary_large_image' : 'summary' );
	printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );
	if ( '' !== $description ) {
		printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $description ) );
	}
}

/**
 * Keep thin / utility pages out of the index (search, cart, checkout, account).
 * Runs per-language, so both the Arabic and English variants are excluded.
 *
 * @param array $robots Directives passed by the core `wp_robots` filter.
 * @return array
 */
function fares_seo_robots( array $robots ): array {
	$is_account = function_exists( 'is_account_page' ) && is_account_page();

	if ( is_search() || is_cart() || is_checkout() || $is_account ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}

	return $robots;
}
