<?php
/**
 * Product card ribbon badge (orange, corner-anchored, asymmetric radius).
 *
 * Shown when the product has ribbon text (meta `_fares_ribbon`, filterable).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

/**
 * Filter the card ribbon text (empty string = no ribbon).
 *
 * @param string     $text    Ribbon text.
 * @param WC_Product $product Product.
 */
$fares_ribbon = apply_filters( 'fares_product_ribbon_text', (string) $product->get_meta( '_fares_ribbon' ), $product );

if ( '' === $fares_ribbon ) {
	return;
}
?>
<span class="fares-card__ribbon"><?php echo esc_html( $fares_ribbon ); ?></span>
