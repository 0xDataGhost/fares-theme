<?php
/**
 * Product card price — sale price red, regular price struck through.
 * Markup comes from Woo's get_price_html() (<del>/<ins>), styled by CSS.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$fares_price_html = $product->get_price_html();

if ( ! $fares_price_html ) {
	return;
}
?>
<div class="fares-card__price"><?php echo $fares_price_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC price HTML. ?></div>
