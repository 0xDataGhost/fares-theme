<?php
/**
 * Purchase box — price row, quantity + add-to-cart/buy-now form, payment
 * tiles (Figma 9:787).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}
?>
<div class="fares-purchase-box" id="fares-purchase-box">
	<div class="fares-purchase-box__price">
		<span class="fares-purchase-box__label"><?php esc_html_e( 'السعر', 'fares-theme' ); ?></span>
		<div class="fares-purchase-box__amount"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC price HTML. ?></div>
	</div>

	<div class="fares-purchase-box__form">
		<?php woocommerce_template_single_add_to_cart(); ?>
	</div>

	<div class="fares-purchase-box__payments">
		<?php get_template_part( 'template-parts/footer/payment-icons' ); ?>
	</div>
</div>
