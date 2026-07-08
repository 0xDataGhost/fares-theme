<?php
/**
 * Stock badge "متوفر" — pulsing green dot + label (Figma 9:234).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
	return;
}
?>
<span class="fares-stock-badge">
	<span class="fares-stock-badge__dot" aria-hidden="true"></span>
	<?php esc_html_e( 'متوفر', 'fares-theme' ); ?>
</span>
