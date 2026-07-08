<?php
/**
 * Purchase-count bar — "عدد مرات الشراء +N" (Figma 9:766).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$fares_sales = (int) $product->get_total_sales();

if ( $fares_sales < 1 ) {
	return;
}
?>
<div class="fares-purchase-count">
	<span><?php esc_html_e( 'عدد مرات الشراء', 'fares-theme' ); ?></span>
	<b>+<?php echo esc_html( number_format_i18n( $fares_sales ) ); ?></b>
</div>
