<?php
/**
 * Product card image — square, radius-md, with ribbon badge and
 * out-of-stock stamp overlay.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}
?>
<div class="fares-card__media">
	<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'fares-card__image', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated img tag. ?>
	<?php get_template_part( 'template-parts/product/card/badge' ); ?>
	<?php if ( ! $product->is_in_stock() ) : ?>
		<span class="fares-card__stamp" aria-hidden="true"><?php esc_html_e( 'نفدت الكمية', 'fares-theme' ); ?></span>
	<?php endif; ?>
</div>
