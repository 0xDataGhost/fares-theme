<?php
/**
 * Real catalogue importer. Reads bin/data/products.json (normalised from the
 * Salla store export) and creates the WooCommerce products idempotently.
 *
 * Run inside wp-env:
 *   npm run import                 # products only (fast, offline-safe)
 *   FARES_IMPORT_IMAGES=1 npm run import   # also sideload the Salla CDN images
 *
 * Idempotency: every product is keyed by SKU (`salla-{id}`). Re-running updates
 * price / sale / stock / status / categories in place and never duplicates.
 * Featured images are only fetched when missing, so image runs are resumable.
 *
 * This is additive to bin/seed.php — it does not touch the demo products,
 * pages, menus or serial keys the e2e tests depend on.
 *
 * @package fares-theme
 */

defined( 'WP_CLI' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

$data_file = __DIR__ . '/data/products.json';
if ( ! is_readable( $data_file ) ) {
	WP_CLI::error( "Data file not found: {$data_file}" );
}

$products = json_decode( (string) file_get_contents( $data_file ), true );
if ( ! is_array( $products ) ) {
	WP_CLI::error( 'Could not decode products.json.' );
}

$import_images = (bool) getenv( 'FARES_IMPORT_IMAGES' );

WP_CLI::log( sprintf( '— Fares catalogue import (%d products, images: %s) —', count( $products ), $import_images ? 'on' : 'off' ) );

/* ------------------------------------------------------------ categories */

/**
 * Resolve a category slug to a term id, creating the term on first sight.
 * Results are memoised so a run touches each term once.
 */
function fares_import_term( string $slug, string $name, array &$cache ): int {
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}
	$existing = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $existing instanceof WP_Term ) {
		return $cache[ $slug ] = (int) $existing->term_id;
	}
	$created = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
	if ( is_wp_error( $created ) ) {
		WP_CLI::warning( "Category {$slug}: " . $created->get_error_message() );
		return $cache[ $slug ] = 0;
	}
	WP_CLI::log( "Category created: {$name} ({$slug})" );
	return $cache[ $slug ] = (int) $created['term_id'];
}

/**
 * Sideload one remote image and return its attachment id (0 on failure).
 */
function fares_sideload( string $url, WC_Product $product ): int {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $url, $product->get_id(), $product->get_name(), 'id' );
	if ( is_wp_error( $attachment_id ) ) {
		WP_CLI::warning( 'Image ' . $product->get_sku() . ': ' . $attachment_id->get_error_message() );
		return 0;
	}
	return (int) $attachment_id;
}

/**
 * Sideload the featured image and any gallery images (each only once).
 */
function fares_import_images( WC_Product $product, string $image, array $gallery ): int {
	$fetched = 0;

	if ( '' !== $image && ! $product->get_image_id() ) {
		$id = fares_sideload( $image, $product );
		if ( $id ) {
			$product->set_image_id( $id );
			++$fetched;
		}
	}

	if ( $gallery && ! $product->get_gallery_image_ids() ) {
		$ids = array();
		foreach ( $gallery as $url ) {
			$id = fares_sideload( (string) $url, $product );
			if ( $id ) {
				$ids[] = $id;
				++$fetched;
			}
		}
		if ( $ids ) {
			$product->set_gallery_image_ids( $ids );
		}
	}

	if ( $fetched ) {
		$product->save();
	}
	return $fetched;
}

/* -------------------------------------------------------------- products */

$term_cache = array();
$created    = 0;
$updated    = 0;
$images     = 0;

foreach ( $products as $row ) {
	$sku  = (string) ( $row['sku'] ?? '' );
	$name = trim( (string) ( $row['name'] ?? '' ) );
	if ( '' === $sku || '' === $name ) {
		continue;
	}

	$existing_id = wc_get_product_id_by_sku( $sku );
	$product     = $existing_id ? wc_get_product( $existing_id ) : new WC_Product_Simple();
	if ( ! $product instanceof WC_Product ) {
		$product = new WC_Product_Simple();
	}

	// Category ids for this product.
	$cat_ids = array();
	foreach ( (array) ( $row['categories'] ?? array() ) as $cat ) {
		$tid = fares_import_term( (string) $cat['slug'], (string) $cat['name'], $term_cache );
		if ( $tid ) {
			$cat_ids[] = $tid;
		}
	}

	// Prices. Guard against a sale price that is not strictly below regular
	// (WooCommerce would silently drop it — better to skip the sale).
	$regular = (string) ( $row['price'] ?? '' );
	$sale    = (string) ( $row['sale'] ?? '' );
	if ( '' !== $sale && '' !== $regular && (float) $sale >= (float) $regular ) {
		$sale = '';
	}

	$product->set_name( $name );
	$product->set_sku( $sku );
	$product->set_regular_price( $regular );
	$product->set_sale_price( $sale );
	$product->set_virtual( true );
	$product->set_manage_stock( false );
	$product->set_stock_status( 'outofstock' === ( $row['stock'] ?? '' ) ? 'outofstock' : 'instock' );
	$product->set_status( 'draft' === ( $row['status'] ?? '' ) ? 'draft' : 'publish' );
	if ( $cat_ids ) {
		$product->set_category_ids( $cat_ids );
	}
	// Only set the description on first import so hand-edits survive re-runs.
	if ( ! $existing_id ) {
		$product->set_description( wp_kses_post( (string) ( $row['description'] ?? '' ) ) );
	}
	$product->save();

	if ( $existing_id ) {
		++$updated;
	} else {
		++$created;
	}

	if ( $import_images ) {
		$images += fares_import_images(
			$product,
			(string) ( $row['image'] ?? '' ),
			(array) ( $row['gallery'] ?? array() )
		);
	}
}

WP_CLI::success( sprintf( 'Import complete — %d created, %d updated, %d images.', $created, $updated, $images ) );
