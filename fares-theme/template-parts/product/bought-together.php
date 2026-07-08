<?php
/**
 * "كمل طلبك" bought-together box — the product's up-sells with per-item
 * add-to-cart (Figma 9:862). Checkbox bundling is a planned v1.5
 * enhancement.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$fares_upsell_ids = $product->get_upsell_ids();

if ( empty( $fares_upsell_ids ) ) {
	return;
}

$fares_upsells = array_filter( array_map( 'wc_get_product', $fares_upsell_ids ) );

if ( empty( $fares_upsells ) ) {
	return;
}
?>
<div class="fares-bought-together">
	<h2 class="fares-bought-together__title"><?php esc_html_e( 'كمّل طلبك', 'fares-theme' ); ?></h2>
	<p class="fares-bought-together__subtitle"><?php esc_html_e( 'اكتشف ما يشتريه العملاء مع هذا المنتج', 'fares-theme' ); ?></p>

	<ul class="fares-bought-together__list">
		<?php foreach ( $fares_upsells as $fares_upsell ) : ?>
			<li class="fares-bought-together__row">
				<span class="fares-bought-together__thumb"><?php echo $fares_upsell->get_image( 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core img tag. ?></span>
				<a class="fares-bought-together__name" href="<?php echo esc_url( $fares_upsell->get_permalink() ); ?>"><?php echo esc_html( $fares_upsell->get_name() ); ?></a>
				<span class="fares-bought-together__price"><?php echo $fares_upsell->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC price HTML. ?></span>
				<?php if ( $fares_upsell->is_type( 'simple' ) && $fares_upsell->is_in_stock() ) : ?>
					<a
						class="fares-button fares-bought-together__add add_to_cart_button ajax_add_to_cart"
						href="<?php echo esc_url( $fares_upsell->add_to_cart_url() ); ?>"
						data-product_id="<?php echo esc_attr( (string) $fares_upsell->get_id() ); ?>"
						data-quantity="1"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( 'أضف %s للسلة', 'fares-theme' ), $fares_upsell->get_name() ) ); ?>"
					><?php esc_html_e( 'أضف للسلة', 'fares-theme' ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
