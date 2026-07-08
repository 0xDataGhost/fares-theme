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

// Design shows no page title, no sorting dropdown, and no term description
// on archives (breadcrumb + count only).
add_filter( 'woocommerce_show_page_title', '__return_false' );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );

/**
 * Archives close with the testimonials slider above the footer (Figma
 * 1:1145) — hooked after the main wrapper closes.
 */
function fares_archive_testimonials(): void {
	if ( is_shop() || is_product_taxonomy() ) {
		get_template_part( 'template-parts/global/testimonials' );
	}
}
add_action( 'woocommerce_after_main_content', 'fares_archive_testimonials', 20 );

/* -------------------------------------------------------------------------
 * Cart — design shows no cross-sells on the cart page.
 * ---------------------------------------------------------------------- */
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );

// Keep variation names clean so attributes render as cart item data
// (styled into the design's pills) instead of inside the product title.
add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );

/* -------------------------------------------------------------------------
 * Single product — summary re-composed via hooks (Figma 9:2).
 * Order: title → rating+count+stock → long description → purchase count →
 * purchase box (price/qty/CTAs/payments) → bought-together → urgency.
 * ---------------------------------------------------------------------- */

// No sale burst on the gallery — sale is expressed by the price.
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

// Stock badge right after the rating row.
add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		get_template_part( 'template-parts/product/stock-badge' );
	},
	7
);

// Long rich description inline (the design has no tabs).
add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		global $product;
		$description = $product instanceof WC_Product ? $product->get_description() : '';
		if ( '' !== $description ) {
			echo '<div class="fares-product-description">' . wp_kses_post( wpautop( $description ) ) . '</div>';
		}
	},
	15
);

add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		get_template_part( 'template-parts/product/purchase-count' );
	},
	25
);
add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		get_template_part( 'template-parts/product/purchase-box' );
	},
	30
);
add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		get_template_part( 'template-parts/product/bought-together' );
	},
	35
);
add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		get_template_part( 'template-parts/product/urgency-banner' );
	},
	40
);

// No tabs; replace default up-sells/related display (up-sells live in the
// bought-together box; related uses the theme carousel).
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

/**
 * Reviews section (design: aggregate % + list + load-more) — the reviews
 * layout itself is the single-product-reviews.php override.
 */
function fares_single_reviews(): void {
	comments_template();
}
add_action( 'woocommerce_after_single_product_summary', 'fares_single_reviews', 15 );

/**
 * Related products carousel ("منتجات قد تعجبك").
 */
function fares_related_products_carousel(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$related = fares_get_related_products( $product, 8 );
	if ( empty( $related ) ) {
		return;
	}

	echo '<section class="fares-related" data-fares-carousel>';
	get_template_part(
		'template-parts/global/section-title',
		null,
		array( 'title' => __( 'منتجات قد تعجبك', 'fares-theme' ) )
	);
	get_template_part( 'template-parts/global/product-carousel', null, array( 'products' => $related ) );
	echo '</section>';
}
add_action( 'woocommerce_after_single_product_summary', 'fares_related_products_carousel', 20 );

/**
 * Buy-now button beside add-to-cart (behavior lives in fares-store).
 */
function fares_buy_now_button(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
		return;
	}
	// Only the clicked submit's name is posted, and `add-to-cart` lives on
	// the main button — formaction carries both params for this button.
	$buy_now_url = add_query_arg(
		array(
			'add-to-cart'   => $product->get_id(),
			'fares_buy_now' => 1,
		),
		$product->get_permalink()
	);
	printf(
		'<button type="submit" formaction="%s" class="fares-button--outline fares-buy-now">%s</button>',
		esc_url( $buy_now_url ),
		esc_html__( 'اشتري الان', 'fares-theme' )
	);
}
add_action( 'woocommerce_after_add_to_cart_button', 'fares_buy_now_button' );

/**
 * Quantity stepper +/− buttons via the quantity-input hooks (no template
 * override needed). DOM-first button renders at inline-start (right in
 * RTL) = plus, per the Figma stepper.
 */
add_action(
	'woocommerce_before_quantity_input_field',
	static function (): void {
		printf(
			'<button type="button" class="fares-qty-btn" data-fares-qty="up" aria-label="%s">+</button>',
			esc_attr__( 'زيادة الكمية', 'fares-theme' )
		);
	}
);
add_action(
	'woocommerce_after_quantity_input_field',
	static function (): void {
		printf(
			'<button type="button" class="fares-qty-btn" data-fares-qty="down" aria-label="%s">−</button>',
			esc_attr__( 'تقليل الكمية', 'fares-theme' )
		);
	}
);
