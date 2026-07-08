<?php
/**
 * Order automation for digital code products.
 *
 * All products in this store are virtual code-delivery items: once payment
 * completes there is nothing to fulfil manually, so paid orders go straight
 * to "completed" — which is also what triggers the serial-number email.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Auto-complete paid orders when every line item is virtual.
 *
 * @param string   $status   Default status after payment ("processing").
 * @param int      $order_id Order ID.
 * @param WC_Order $order    Order object.
 * @return string
 */
function fares_store_autocomplete_virtual_orders( string $status, int $order_id, WC_Order $order ): string {
	foreach ( $order->get_items() as $item ) {
		$product = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;
		if ( ! $product || ! $product->is_virtual() ) {
			return $status;
		}
	}

	return 'completed';
}
add_filter( 'woocommerce_payment_complete_order_status', 'fares_store_autocomplete_virtual_orders', 10, 3 );
