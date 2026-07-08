<?php
/**
 * Urgency banner — "ليش تنتظر؟" + jump-to-purchase CTA (Figma 9:905).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
	return;
}
?>
<aside class="fares-urgency">
	<div>
		<h2 class="fares-urgency__title"><?php esc_html_e( 'ليش تنتظر؟', 'fares-theme' ); ?></h2>
		<p class="fares-urgency__subtitle"><?php esc_html_e( 'احصل على المنتج مباشرة بعد الدفع', 'fares-theme' ); ?></p>
	</div>
	<a class="fares-urgency__cta" href="#fares-purchase-box"><?php esc_html_e( 'اطلبه الان', 'fares-theme' ); ?></a>
</aside>
