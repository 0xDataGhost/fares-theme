<?php
/**
 * Central product query layer.
 *
 * The ONLY place the theme queries products. Helpers are pure (args in →
 * products out) and cached; template parts consume these, never
 * wc_get_products() directly.
 *
 * Caching: results are stored as product IDs in transients backed by the
 * object cache when present. Keys embed a version salt bumped on product
 * changes (see fares_bump_cache_version) so invalidation is O(1).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Current cache version salt.
 */
function fares_cache_version(): string {
	return (string) get_option( 'fares_cache_ver', '1' );
}

/**
 * Invalidate all fares query caches.
 */
function fares_bump_cache_version(): void {
	update_option( 'fares_cache_ver', (string) time(), false );
}
add_action( 'save_post_product', 'fares_bump_cache_version' );
add_action( 'woocommerce_update_product', 'fares_bump_cache_version' );
add_action( 'woocommerce_product_set_stock_status', 'fares_bump_cache_version' );
add_action( 'created_product_cat', 'fares_bump_cache_version' );
add_action( 'edited_product_cat', 'fares_bump_cache_version' );
add_action( 'delete_product_cat', 'fares_bump_cache_version' );

/**
 * Cached wc_get_products wrapper — stores IDs, rehydrates objects.
 *
 * @param string $key  Cache key fragment (unique per helper+args).
 * @param array  $args wc_get_products args (return forced to ids).
 * @param int    $ttl  Cache lifetime in seconds.
 * @return WC_Product[]
 */
function fares_cached_products( string $key, array $args, int $ttl = HOUR_IN_SECONDS ): array {
	$cache_key = 'fares_q_' . md5( $key . '|' . fares_cache_version() );
	$ids       = get_transient( $cache_key );

	if ( false === $ids ) {
		$args['return'] = 'ids';
		$ids            = wc_get_products( $args );
		set_transient( $cache_key, $ids, $ttl );
	}

	return array_filter( array_map( 'wc_get_product', (array) $ids ) );
}

/**
 * Best-selling products (by total sales).
 *
 * @param int $limit Number of products.
 * @return WC_Product[]
 */
function fares_get_best_sellers( int $limit = 8 ): array {
	return fares_cached_products(
		"best_sellers:{$limit}",
		array(
			'status'   => 'publish',
			'limit'    => $limit,
			'orderby'  => 'popularity',
			'order'    => 'DESC',
		),
		15 * MINUTE_IN_SECONDS
	);
}

/**
 * Products in a category.
 *
 * @param string $category_slug product_cat slug.
 * @param int    $limit         Number of products.
 * @return WC_Product[]
 */
function fares_get_category_products( string $category_slug, int $limit = 8 ): array {
	return fares_cached_products(
		"category:{$category_slug}:{$limit}",
		array(
			'status'   => 'publish',
			'limit'    => $limit,
			'category' => array( $category_slug ),
		),
		15 * MINUTE_IN_SECONDS
	);
}

/**
 * Products currently on sale.
 *
 * @param int $limit Number of products.
 * @return WC_Product[]
 */
function fares_get_sale_products( int $limit = 8 ): array {
	return fares_cached_products(
		"sale:{$limit}",
		array(
			'status'  => 'publish',
			'limit'   => $limit,
			'include' => wc_get_product_ids_on_sale(),
		),
		15 * MINUTE_IN_SECONDS
	);
}

/**
 * Latest products.
 *
 * @param int $limit Number of products.
 * @return WC_Product[]
 */
function fares_get_latest_products( int $limit = 8 ): array {
	return fares_cached_products(
		"latest:{$limit}",
		array(
			'status'  => 'publish',
			'limit'   => $limit,
			'orderby' => 'date',
			'order'   => 'DESC',
		),
		15 * MINUTE_IN_SECONDS
	);
}

/**
 * Related products for a given product.
 *
 * @param WC_Product $product Product.
 * @param int        $limit   Number of products.
 * @return WC_Product[]
 */
function fares_get_related_products( WC_Product $product, int $limit = 8 ): array {
	$cache_key = 'related:' . $product->get_id() . ":{$limit}";
	$ids       = get_transient( 'fares_q_' . md5( $cache_key . '|' . fares_cache_version() ) );

	if ( false === $ids ) {
		$ids = wc_get_related_products( $product->get_id(), $limit );
		set_transient( 'fares_q_' . md5( $cache_key . '|' . fares_cache_version() ), $ids, HOUR_IN_SECONDS );
	}

	return array_filter( array_map( 'wc_get_product', (array) $ids ) );
}

/**
 * Published testimonials.
 *
 * @param int $limit Number of testimonials.
 * @return WP_Post[]
 */
function fares_get_testimonials( int $limit = 10 ): array {
	$cache_key = 'fares_q_' . md5( "testimonials:{$limit}|" . fares_cache_version() );
	$ids       = get_transient( $cache_key );

	if ( false === $ids ) {
		$ids = get_posts(
			array(
				'post_type'      => 'fares_testimonial',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'fields'         => 'ids',
			)
		);
		set_transient( $cache_key, $ids, 12 * HOUR_IN_SECONDS );
	}

	return array_filter( array_map( 'get_post', (array) $ids ) );
}
add_action( 'save_post_fares_testimonial', 'fares_bump_cache_version' );

/**
 * Published homepage sections, ordered by the "Order" attribute.
 *
 * @param int $limit Maximum number of sections.
 * @return WP_Post[]
 */
function fares_get_home_sections( int $limit = 50 ): array {
	$cache_key = 'fares_q_' . md5( "home_sections:{$limit}|" . fares_cache_version() );
	$ids       = get_transient( $cache_key );

	if ( false === $ids ) {
		$ids = get_posts(
			array(
				'post_type'      => 'fares_home_section',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				),
				'fields'         => 'ids',
			)
		);
		set_transient( $cache_key, $ids, 12 * HOUR_IN_SECONDS );
	}

	return array_filter( array_map( 'get_post', (array) $ids ) );
}
add_action( 'save_post_fares_home_section', 'fares_bump_cache_version' );
