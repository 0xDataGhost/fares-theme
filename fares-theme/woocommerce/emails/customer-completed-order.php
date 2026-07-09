<?php
/**
 * Customer completed-order email — Arabic RTL redesign for a virtual
 * code-delivery store. Bypasses the default order-details / customer-
 * details actions so the WP Serial Numbers plugin's own key injection
 * hook on `woocommerce_email_after_order_table` does not run for this
 * email (keys render inside the dedicated "بيانات التفعيل" block below
 * instead of duplicating under the totals table).
 *
 * HTML-email caveats respected: table-based layout, inline styles,
 * bulletproof light background so the message stays readable in Gmail
 * dark-mode and locked-down Outlook renderers even if colours get
 * overridden.
 *
 * @package fares-theme
 *
 * @var WC_Order $order              Order.
 * @var bool     $sent_to_admin      Admin recipient (always false here).
 * @var bool     $plain_text         Plain-text variant.
 * @var WC_Email $email              Email object.
 * @var string   $email_heading      Header heading.
 * @var string   $additional_content Optional admin-editable trailer.
 */

defined( 'ABSPATH' ) || exit;

if ( $plain_text ) {
	// Fall back to the shipped plain-text version so text-only clients
	// still get a legible message. Woo looks in emails/plain/ for it.
	wc_get_template(
		'emails/plain/customer-completed-order.php',
		array(
			'order'              => $order,
			'email_heading'      => $email_heading,
			'additional_content' => $additional_content,
			'sent_to_admin'      => $sent_to_admin,
			'plain_text'         => $plain_text,
			'email'              => $email,
		)
	);
	return;
}

/**
 * Fetch activation records for one product in the order.
 *
 * Primary path: WC Serial Numbers' public helper `wcsn_get_keys()`
 * (returns `Key` model objects; handles decryption for us). Fallback
 * for older builds: direct query against `{prefix}serial_numbers` +
 * `wcsn_decrypt_key()`. Fields the free plugin doesn't store (notably
 * `activation_email` — dropped from the schema in v1.2) come back as
 * empty strings and are hidden in the render below.
 *
 * @param int $order_id   Order ID.
 * @param int $product_id Product ID.
 * @return array<int,array<string,mixed>>
 */
$fares_get_serials = static function ( int $order_id, int $product_id ): array {
	$out = array();

	if ( function_exists( 'wcsn_get_keys' ) ) {
		$keys = wcsn_get_keys(
			array(
				'order_id'   => $order_id,
				'product_id' => $product_id,
				'per_page'   => -1,
			)
		);
		foreach ( (array) $keys as $key ) {
			$serial_key = is_object( $key ) && method_exists( $key, 'get_key' )
				? (string) $key->get_key()               // Model handles decryption.
				: (string) ( is_array( $key ) ? ( $key['serial_key'] ?? '' ) : '' );
			$out[]      = array(
				'key'              => $serial_key,
				'activation_limit' => is_object( $key ) && method_exists( $key, 'get_activation_limit' )
					? $key->get_activation_limit()
					: ( is_array( $key ) ? ( $key['activation_limit'] ?? null ) : null ),
				'activation_count' => is_object( $key ) && method_exists( $key, 'get_activation_count' )
					? $key->get_activation_count()
					: ( is_array( $key ) ? ( $key['activation_count'] ?? null ) : null ),
				'expire_date'      => is_object( $key ) && method_exists( $key, 'get_expire_date' )
					? (string) $key->get_expire_date()
					: (string) ( is_array( $key ) ? ( $key['expire_date'] ?? '' ) : '' ),
				'validity'         => is_object( $key ) && method_exists( $key, 'get_validity' )
					? (string) $key->get_validity()
					: (string) ( is_array( $key ) ? ( $key['validity'] ?? '' ) : '' ),
				'status'           => is_object( $key ) && method_exists( $key, 'get_status' )
					? (string) $key->get_status()
					: (string) ( is_array( $key ) ? ( $key['status'] ?? '' ) : '' ),
			);
		}
	}

	// Fallback: raw table read (older WC Serial Numbers builds that
	// didn't expose `wcsn_get_keys` yet).
	if ( empty( $out ) ) {
		global $wpdb;
		$table = $wpdb->prefix . 'serial_numbers';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists === $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT serial_key, activation_limit, activation_count, expire_date, validity, status
					FROM {$table} WHERE order_id = %d AND product_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$order_id,
					$product_id
				),
				ARRAY_A
			);
			foreach ( (array) $rows as $row ) {
				$raw_key = (string) ( $row['serial_key'] ?? '' );
				$out[]   = array(
					'key'              => function_exists( 'wcsn_decrypt_key' ) ? (string) wcsn_decrypt_key( $raw_key ) : $raw_key,
					'activation_limit' => $row['activation_limit'] ?? null,
					'activation_count' => $row['activation_count'] ?? null,
					'expire_date'      => (string) ( $row['expire_date'] ?? '' ),
					'validity'         => (string) ( $row['validity'] ?? '' ),
					'status'           => (string) ( $row['status'] ?? '' ),
				);
			}
		}
	}

	/**
	 * Filter the serials list per product/order — lets a site owner or
	 * a companion plugin plug into any custom serial store.
	 *
	 * @param array $out        Activation records.
	 * @param int   $order_id   Order ID.
	 * @param int   $product_id Product ID.
	 */
	return (array) apply_filters( 'fares_email_order_serials', $out, $order_id, $product_id );
};

/**
 * Render one Arabic label if a value is present.
 */
$fares_row = static function ( string $label, $value ): string {
	if ( null === $value || '' === $value ) {
		return '';
	}
	return sprintf(
		'<tr><td style="padding:6px 0;color:#6b7280;font-size:13px;width:40%%;">%s</td>'
		. '<td style="padding:6px 0;color:#111827;font-size:14px;">%s</td></tr>',
		esc_html( $label ),
		esc_html( (string) $value )
	);
};

/**
 * Translate raw status codes to a friendly Arabic label.
 */
// Status codes taken from WC Serial Numbers' schema: available / sold /
// cancelled / expired.
$fares_status_label = static function ( string $status ): string {
	$map = array(
		'available' => __( 'نشط', 'fares-theme' ),
		'sold'      => __( 'نشط', 'fares-theme' ),
		'active'    => __( 'نشط', 'fares-theme' ),
		'expired'   => __( 'منتهي الصلاحية', 'fares-theme' ),
		'cancelled' => __( 'ملغي', 'fares-theme' ),
	);
	return $map[ $status ] ?? __( 'نشط', 'fares-theme' );
};

/**
 * Format a validity/limit value for display or fall back to a sentinel.
 */
$fares_limit = static function ( $value ): string {
	if ( null === $value || '' === $value || '0' === (string) $value ) {
		return __( 'غير محدود', 'fares-theme' );
	}
	return (string) $value;
};

$fares_expiry = static function ( string $value ): string {
	if ( '' === $value || '0000-00-00' === $value || 'lifetime' === strtolower( $value ) ) {
		return __( 'مدى الحياة', 'fares-theme' );
	}
	$ts = strtotime( $value );
	return $ts ? date_i18n( _x( 'j F Y', 'email date', 'fares-theme' ), $ts ) : $value;
};

// Header: the shipped wrapper handles the WooCommerce chrome — we
// override the heading text but keep the hook so header plugins
// still get to inject.
do_action( 'woocommerce_email_header', esc_html__( 'تم تنفيذ طلبك بنجاح', 'fares-theme' ), $email );
?>

<div dir="rtl" style="direction:rtl;text-align:right;font-family:'Segoe UI',Tahoma,Arial,'Helvetica Neue',sans-serif;color:#111827;">

	<p style="margin:0 0 12px;font-size:16px;">
		<?php
		printf(
			/* translators: %s: Customer first name. */
			esc_html__( 'مرحبًا %s،', 'fares-theme' ),
			esc_html( $order->get_billing_first_name() )
		);
		?>
	</p>

	<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">
		<?php esc_html_e( 'يسعدنا إبلاغك بأنه تم تنفيذ طلبك بنجاح، وأصبحت جميع بيانات المنتج جاهزة للاستخدام.', 'fares-theme' ); ?>
	</p>

	<!-- ===================================================== ملخص الطلب -->
	<h2 style="margin:0 0 12px;font-size:18px;color:#111827;border-bottom:2px solid #2c6a96;padding-bottom:6px;">
		<?php esc_html_e( 'ملخص الطلب', 'fares-theme' ); ?>
	</h2>

	<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 12px;border-collapse:collapse;">
		<?php
		echo $fares_row( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			__( 'رقم الطلب:', 'fares-theme' ),
			'#' . $order->get_order_number()
		);
		echo $fares_row( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			__( 'تاريخ الطلب:', 'fares-theme' ),
			date_i18n( _x( 'j F Y', 'email date', 'fares-theme' ), $order->get_date_created()->getTimestamp() )
		);
		echo $fares_row( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			__( 'طريقة الدفع:', 'fares-theme' ),
			$order->get_payment_method_title()
		);
		?>
	</table>

	<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin-bottom:16px;">
		<thead>
			<tr style="background:#f3f4f6;">
				<th align="right" style="padding:10px 12px;font-size:13px;color:#374151;border-bottom:1px solid #e5e7eb;">
					<?php esc_html_e( 'المنتج', 'fares-theme' ); ?>
				</th>
				<th align="center" style="padding:10px 12px;font-size:13px;color:#374151;border-bottom:1px solid #e5e7eb;">
					<?php esc_html_e( 'الكمية', 'fares-theme' ); ?>
				</th>
				<th align="left" style="padding:10px 12px;font-size:13px;color:#374151;border-bottom:1px solid #e5e7eb;">
					<?php esc_html_e( 'السعر', 'fares-theme' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $order->get_items() as $item ) : ?>
				<tr>
					<td align="right" style="padding:10px 12px;font-size:14px;color:#111827;border-bottom:1px solid #e5e7eb;">
						<?php echo esc_html( $item->get_name() ); ?>
					</td>
					<td align="center" style="padding:10px 12px;font-size:14px;color:#111827;border-bottom:1px solid #e5e7eb;">
						<?php echo esc_html( (string) $item->get_quantity() ); ?>
					</td>
					<td align="left" style="padding:10px 12px;font-size:14px;color:#111827;border-bottom:1px solid #e5e7eb;">
						<?php echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<td colspan="2" align="right" style="padding:12px;font-size:15px;font-weight:bold;color:#111827;background:#f9fafb;">
					<?php esc_html_e( 'الإجمالي', 'fares-theme' ); ?>
				</td>
				<td align="left" style="padding:12px;font-size:15px;font-weight:bold;color:#111827;background:#f9fafb;">
					<?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
				</td>
			</tr>
		</tbody>
	</table>

	<!-- ================================================ بيانات التفعيل -->
	<?php
	$any_serials = false;
	$sections    = '';
	foreach ( $order->get_items() as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		$product_id = (int) $item->get_product_id();
		$serials    = $fares_get_serials( (int) $order->get_id(), $product_id );
		if ( empty( $serials ) ) {
			continue;
		}
		$any_serials = true;

		foreach ( $serials as $serial ) {
			ob_start();
			?>
			<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:16px;border-collapse:collapse;border:1px solid #e5e7eb;border-radius:6px;">
				<tr>
					<td style="padding:14px 16px;">
						<div style="font-size:13px;color:#6b7280;margin-bottom:4px;">
							<?php esc_html_e( 'المنتج', 'fares-theme' ); ?>
						</div>
						<div style="font-size:15px;font-weight:bold;color:#111827;margin-bottom:14px;">
							<?php echo esc_html( $item->get_name() ); ?>
						</div>

						<div style="font-size:13px;color:#6b7280;margin-bottom:4px;">
							<?php esc_html_e( 'مفتاح التفعيل', 'fares-theme' ); ?>
						</div>
						<div style="background:#0f172a;color:#e2e8f0;padding:12px 14px;border-radius:4px;font-family:'Courier New',Consolas,Menlo,monospace;font-size:15px;letter-spacing:0.5px;word-break:break-all;user-select:all;direction:ltr;text-align:left;">
							<?php echo esc_html( $serial['key'] ); ?>
						</div>

						<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:12px;border-collapse:collapse;">
							<?php
							// Activation email as a per-key field isn't in the free
							// WC Serial Numbers schema — surface the order's own
							// billing email instead so the customer sees what
							// address the key is tied to.
							echo $fares_row( __( 'بريد التفعيل:', 'fares-theme' ), $order->get_billing_email() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $fares_row( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								__( 'الحد الأقصى للتفعيل:', 'fares-theme' ),
								$fares_limit( $serial['activation_limit'] )
							);
							if ( null !== $serial['activation_count'] && '' !== $serial['activation_count'] ) {
								echo $fares_row( __( 'عدد مرات التفعيل المستخدمة:', 'fares-theme' ), (string) (int) $serial['activation_count'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							$validity = '' !== $serial['expire_date']
								? $fares_expiry( $serial['expire_date'] )
								: ( '' !== $serial['validity'] ? $fares_limit( $serial['validity'] ) : __( 'مدى الحياة', 'fares-theme' ) );
							echo $fares_row( __( 'مدة الصلاحية:', 'fares-theme' ), $validity ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $fares_row( __( 'الحالة:', 'fares-theme' ), $fares_status_label( $serial['status'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</table>
					</td>
				</tr>
			</table>
			<?php
			$sections .= ob_get_clean();
		}
	}

	if ( $any_serials ) :
		?>
		<h2 style="margin:24px 0 12px;font-size:18px;color:#111827;border-bottom:2px solid #2c6a96;padding-bottom:6px;">
			<?php esc_html_e( 'بيانات التفعيل', 'fares-theme' ); ?>
		</h2>
		<?php echo $sections; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>

	<!-- ==================================================== بيانات العميل -->
	<h2 style="margin:24px 0 12px;font-size:18px;color:#111827;border-bottom:2px solid #2c6a96;padding-bottom:6px;">
		<?php esc_html_e( 'بيانات العميل', 'fares-theme' ); ?>
	</h2>

	<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:24px;border-collapse:collapse;">
		<?php
		$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		echo $fares_row( __( 'الاسم:', 'fares-theme' ), $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $fares_row( __( 'الهاتف:', 'fares-theme' ), $order->get_billing_phone() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $fares_row( __( 'البريد الإلكتروني:', 'fares-theme' ), $order->get_billing_email() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $fares_row( __( 'الشركة:', 'fares-theme' ), $order->get_billing_company() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $fares_row( __( 'العنوان:', 'fares-theme' ), $order->get_billing_address_1() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $fares_row( __( 'المدينة:', 'fares-theme' ), $order->get_billing_city() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $fares_row( __( 'الدولة:', 'fares-theme' ), WC()->countries->countries[ $order->get_billing_country() ] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</table>

	<!-- ========================================================== الخاتمة -->
	<p style="margin:0 0 8px;font-size:15px;line-height:1.7;color:#111827;">
		<?php esc_html_e( 'شكرًا لاختيارك متجرنا.', 'fares-theme' ); ?>
	</p>
	<p style="margin:0 0 24px;font-size:14px;line-height:1.8;color:#374151;">
		<?php esc_html_e( 'إذا احتجت إلى أي مساعدة أو واجهتك أي مشكلة في استخدام المنتج، فلا تتردد في التواصل مع فريق الدعم، وسنكون سعداء بخدمتك في أسرع وقت.', 'fares-theme' ); ?>
	</p>

	<?php
	if ( $additional_content ) {
		echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	}
	?>
</div>

<?php
do_action( 'woocommerce_email_footer', $email );
