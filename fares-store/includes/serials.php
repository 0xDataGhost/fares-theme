<?php
/**
 * Integration glue for the serial-numbers plugin (Serial Numbers for
 * WooCommerce — wc-serial-numbers).
 *
 * The serials plugin owns code pools and email delivery on order
 * completion; this module only smooths the seams. Populated further in
 * Phase 6 (Arabic email output, stock sync checks).
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Surface a clear admin notice if the serials plugin is missing — code
 * delivery is a core business requirement of this store.
 */
function fares_store_serials_dependency_notice(): void {
	if ( defined( 'WC_SERIAL_NUMBERS_VERSION' ) || class_exists( 'WCSerialNumbers\\Plugin' ) ) {
		return;
	}

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>'
		. esc_html__( 'Fares Store: إضافة الأرقام التسلسلية (Serial Numbers for WooCommerce) غير مفعّلة — لن يتم إرسال أكواد التفعيل للعملاء.', 'fares-store' )
		. '</p></div>';
}
add_action( 'admin_notices', 'fares_store_serials_dependency_notice' );

/**
 * Order-received message for a code-delivery store.
 *
 * @param string        $text  Default thank-you text.
 * @param WC_Order|null $order Order.
 * @return string
 */
function fares_store_order_received_text( string $text, $order ): string {
	unset( $order );
	return __( 'تم استلام طلبك بنجاح — سيصلك كود التفعيل على بريدك الإلكتروني خلال لحظات.', 'fares-store' );
}
add_filter( 'woocommerce_thankyou_order_received_text', 'fares_store_order_received_text', 10, 2 );
