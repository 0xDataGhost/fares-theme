<?php
/**
 * Woo display re-composition via hooks (no template overrides).
 *
 * Summary re-ordering, breadcrumb args, archive extras, badges — every
 * customization here considers hooks first per the project's architecture.
 * Populated across Phases 3–5.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumb defaults matched to the design (الرئيسية home label).
 *
 * @param array $args Breadcrumb args.
 * @return array
 */
function fares_breadcrumb_defaults( array $args ): array {
	$args['home']        = __( 'الرئيسية', 'fares-theme' );
	$args['delimiter']   = '<span class="fares-breadcrumb__sep" aria-hidden="true"></span>';
	$args['wrap_before'] = '<nav class="fares-breadcrumb" aria-label="' . esc_attr__( 'مسار التنقل', 'fares-theme' ) . '">';
	$args['wrap_after']  = '</nav>';
	return $args;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'fares_breadcrumb_defaults' );

/* -------------------------------------------------------------------------
 * Product card (shop loop) — full re-composition via hooks; the default
 * content-product.php is pure hooks, so no template override is needed.
 * ---------------------------------------------------------------------- */

// Design has no "Sale!" flash (sale is expressed by the price) and no
// rating row on cards.
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );

// Replace default thumbnail/title/price renderers with the card parts.
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

add_action(
	'woocommerce_before_shop_loop_item_title',
	static function (): void {
		get_template_part( 'template-parts/product/card/image' );
	}
);
add_action(
	'woocommerce_shop_loop_item_title',
	static function (): void {
		get_template_part( 'template-parts/product/card/title' );
	}
);
add_action(
	'woocommerce_after_shop_loop_item_title',
	static function (): void {
		get_template_part( 'template-parts/product/card/price' );
	}
);

/**
 * Loop add-to-cart button: token classes (+ disabled OOS look).
 *
 * @param array      $args    Button args.
 * @param WC_Product $product Product.
 * @return array
 */
function fares_loop_add_to_cart_args( array $args, WC_Product $product ): array {
	$args['class'] .= $product->is_in_stock()
		? ' fares-button fares-card__button'
		: ' fares-button fares-button--disabled fares-card__button';
	return $args;
}
add_filter( 'woocommerce_loop_add_to_cart_args', 'fares_loop_add_to_cart_args', 10, 2 );

// Design shows no page title and no sorting dropdown on archives
// (breadcrumb + count only).
add_filter( 'woocommerce_show_page_title', '__return_false' );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
