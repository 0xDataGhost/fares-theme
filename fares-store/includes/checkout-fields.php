<?php
/**
 * Checkout field rules for a virtual-only code-delivery store.
 *
 * Codes are delivered by email — the customer only needs to tell us who
 * they are and where to send the code: first name, email, phone.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trim billing to the essentials.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function fares_store_trim_checkout_fields( array $fields ): array {
	$keep = array( 'billing_first_name', 'billing_email', 'billing_phone' );

	foreach ( array_keys( $fields['billing'] ?? array() ) as $key ) {
		if ( ! in_array( $key, $keep, true ) ) {
			unset( $fields['billing'][ $key ] );
		}
	}

	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['required'] = true;
	}

	// No physical delivery — drop order notes too.
	unset( $fields['order']['order_comments'] );

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'fares_store_trim_checkout_fields' );

/**
 * Optional billing country/address requirements can trip validation for
 * virtual carts on some gateways — declare no address fields needed.
 */
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

/* -------------------------------------------------------------------------
 * Phone field with country picker (intl-tel-input).
 *
 * Classic checkout: `billing_phone` is already in the field set — tag it
 * with the `data-fares-intl-tel` attribute + Arabic label; the theme's
 * checkout.js progressively enhances it into the flag dropdown.
 *
 * Blocks checkout: registered via the additional-fields API in the
 * `contact` location, then copied to the order's `billing_phone` after
 * submission so it renders normally in order details / emails.
 * ---------------------------------------------------------------------- */

/**
 * Classic-checkout `billing_phone` — tag for JS enhancement, force Arabic
 * label + placeholder.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function fares_store_phone_field_meta( array $fields ): array {
	if ( ! isset( $fields['billing']['billing_phone'] ) ) {
		return $fields;
	}

	$fields['billing']['billing_phone']['label']       = __( 'رقم الجوال', 'fares-store' );
	$fields['billing']['billing_phone']['placeholder'] = '';
	$fields['billing']['billing_phone']['required']    = true;
	$fields['billing']['billing_phone']['class']       = array_merge(
		(array) ( $fields['billing']['billing_phone']['class'] ?? array() ),
		array( 'fares-phone-field' )
	);
	$fields['billing']['billing_phone']['custom_attributes'] = array_merge(
		(array) ( $fields['billing']['billing_phone']['custom_attributes'] ?? array() ),
		array(
			'data-fares-intl-tel'   => '1',
			'data-fares-intl-hidden' => 'billing_phone',
			'inputmode'             => 'tel',
			'autocomplete'          => 'tel',
		)
	);

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'fares_store_phone_field_meta', 20 );

/**
 * International-format phone check — one implementation shared between the
 * classic-checkout server-side validator and the Blocks additional-field
 * validate_callback. Accepts `+` followed by 8–15 digits per E.164.
 *
 * @param string $value Raw submitted value.
 * @return true|WP_Error
 */
function fares_store_validate_intl_phone( string $value ) {
	$value = trim( $value );
	if ( '' === $value ) {
		return new WP_Error(
			'fares_phone_required',
			__( 'من فضلك أدخل رقم الجوال.', 'fares-store' )
		);
	}
	if ( ! preg_match( '/^\+[1-9]\d{7,14}$/', $value ) ) {
		return new WP_Error(
			'fares_phone_invalid',
			__( 'صيغة رقم الجوال غير صحيحة. اختر الدولة وأدخل الرقم بدون أي فراغات.', 'fares-store' )
		);
	}
	return true;
}

/**
 * Classic checkout — server-side validation of the international format.
 */
function fares_store_validate_classic_phone(): void {
	$value  = isset( $_POST['billing_phone'] ) ? wp_unslash( (string) $_POST['billing_phone'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Woo handles the checkout nonce upstream.
	$result = fares_store_validate_intl_phone( sanitize_text_field( $value ) );

	if ( is_wp_error( $result ) ) {
		wc_add_notice( (string) $result->get_error_message(), 'error' );
	}
}
add_action( 'woocommerce_checkout_process', 'fares_store_validate_classic_phone' );

/**
 * Blocks checkout — register the phone field in the Contact section.
 * The API is only available on WC 8.9+; wrap in function_exists so older
 * stacks (or CLI early boot) don't fatal.
 */
function fares_store_register_blocks_phone_field(): void {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'                => 'fares-store/phone',
			'label'             => __( 'رقم الجوال', 'fares-store' ),
			'location'          => 'contact',
			'type'              => 'text',
			'required'          => true,
			'attributes'        => array(
				'data-fares-intl-tel'    => '1',
				'data-fares-intl-hidden' => '_fares_phone_intl',
				'inputmode'              => 'tel',
				'autocomplete'           => 'tel',
			),
			'validate_callback' => static function ( $value ) {
				$result = fares_store_validate_intl_phone( (string) $value );
				return is_wp_error( $result ) ? $result : true;
			},
		)
	);
}
add_action( 'woocommerce_init', 'fares_store_register_blocks_phone_field' );

/**
 * Blocks checkout — copy the additional field's value to the order's
 * standard `billing_phone` slot so it appears in order details / admin /
 * emails / the Contacts CPT below without any downstream changes.
 *
 * @param WC_Order        $order   Order.
 * @param WP_REST_Request $request REST request.
 */
function fares_store_sync_blocks_phone_to_order( $order, $request ): void {
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	$phone = $order->get_meta( '_wc_other/fares-store/phone' );
	if ( '' === $phone ) {
		// Older WC store the field under a different meta prefix; fall back.
		$phone = $order->get_meta( '_fares-store/phone' );
	}
	if ( '' !== $phone ) {
		$order->set_billing_phone( (string) $phone );
	}
}
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'fares_store_sync_blocks_phone_to_order', 10, 2 );
