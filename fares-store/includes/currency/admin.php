<?php
/**
 * Merchant-facing settings tab, FX refresh cron, and health notices.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

const FARES_FX_CRON_HOOK = 'fares_currency_refresh_fx_event';

/**
 * Keep the daily refresh scheduled, and fetch immediately on a site
 * that has no rates yet.
 */
function fares_currency_schedule_cron(): void {
	if ( ! wp_next_scheduled( FARES_FX_CRON_HOOK ) ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'daily', FARES_FX_CRON_HOOK );
	}

	if ( empty( get_option( FARES_FX_OPTION, array() ) ) && ! wp_next_scheduled( FARES_FX_CRON_HOOK . '_bootstrap' ) ) {
		wp_schedule_single_event( time() + 10, FARES_FX_CRON_HOOK . '_bootstrap' );
	}
}
add_action( 'init', 'fares_currency_schedule_cron' );
add_action( FARES_FX_CRON_HOOK, 'fares_currency_fx_refresh' );
add_action( FARES_FX_CRON_HOOK . '_bootstrap', 'fares_currency_fx_refresh' );

/**
 * Settings tab: WooCommerce → Settings → Fares Currency.
 *
 * @param array $tabs Settings tabs.
 * @return array
 */
function fares_currency_settings_tab( array $tabs ): array {
	$tabs['fares_currency'] = __( 'عملات فارس', 'fares-store' );

	return $tabs;
}
add_filter( 'woocommerce_settings_tabs_array', 'fares_currency_settings_tab', 60 );

/**
 * Settings fields: manual rate overrides and the raw-meta shim toggle.
 *
 * @return array
 */
function fares_currency_settings_fields(): array {
	$fields = array(
		array(
			'title' => __( 'الأسعار والتحويل', 'fares-store' ),
			'desc'  => __( 'جميع الأسعار تُدخل بالريال السعودي (SAR). تُحوَّل تلقائيًا لعملة الزائر حسب بلده.', 'fares-store' ),
			'type'  => 'title',
			'id'    => 'fares_currency_section',
		),
	);

	foreach ( fares_currency_codes() as $code ) {
		if ( FARES_CURRENCY_BASE === $code ) {
			continue;
		}

		$fields[] = array(
			/* translators: %s: currency code. */
			'title'             => sprintf( __( 'سعر صرف يدوي — %s', 'fares-store' ), $code ),
			'desc'              => sprintf(
				/* translators: 1: currency code, 2: base currency code. */
				__( '%1$s مقابل 1 %2$s. اتركه فارغًا لاستخدام السعر التلقائي.', 'fares-store' ),
				$code,
				FARES_CURRENCY_BASE
			),
			'id'                => 'fares_currency_manual_rate_' . strtolower( $code ),
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => '0.00000001',
				'min'  => '0',
			),
			'css'               => 'width:150px;',
			'desc_tip'          => true,
		);
	}

	$fields[] = array(
		'title'   => __( 'توافق الإضافات القديمة', 'fares-store' ),
		'desc'    => __( 'حوِّل قراءات الأسعار المباشرة من post meta (للإضافات التي لا تستخدم واجهات WooCommerce).', 'fares-store' ),
		'id'      => 'fares_currency_meta_shim',
		'type'    => 'checkbox',
		'default' => 'no',
	);

	$fields[] = array(
		'type' => 'sectionend',
		'id'   => 'fares_currency_section',
	);

	return $fields;
}

/**
 * Render the tab: status panel + fields.
 */
function fares_currency_settings_page(): void {
	fares_currency_render_status_panel();
	woocommerce_admin_fields( fares_currency_settings_fields() );
}
add_action( 'woocommerce_settings_tabs_fares_currency', 'fares_currency_settings_page' );

/**
 * Persist the fields; manual overrides collapse into one option so the
 * rates layer reads a single map.
 */
function fares_currency_settings_save(): void {
	woocommerce_update_options( fares_currency_settings_fields() );

	$overrides = array();

	foreach ( fares_currency_codes() as $code ) {
		if ( FARES_CURRENCY_BASE === $code ) {
			continue;
		}

		$value = get_option( 'fares_currency_manual_rate_' . strtolower( $code ), '' );

		if ( '' !== $value && (float) $value > 0 ) {
			$overrides[ $code ] = (float) $value;
		}
	}

	update_option( FARES_FX_MANUAL_OVERRIDE, $overrides, false );
}
add_action( 'woocommerce_update_options_fares_currency', 'fares_currency_settings_save' );

/**
 * Live status: current rates, source, age, and a refresh-now action.
 */
function fares_currency_render_status_panel(): void {
	$rates     = fares_currency_rates();
	$last_good = get_option( FARES_FX_LAST_GOOD, array() );
	$refreshed = (int) get_option( FARES_FX_LAST_REFRESH, 0 );
	$overrides = get_option( FARES_FX_MANUAL_OVERRIDE, array() );
	$refresh   = wp_nonce_url( admin_url( 'admin-post.php?action=fares_currency_refresh' ), 'fares_currency_refresh' );
	?>
	<h2><?php esc_html_e( 'حالة أسعار الصرف', 'fares-store' ); ?></h2>
	<table class="widefat striped" style="max-width:560px;margin-block-end:1em;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'العملة', 'fares-store' ); ?></th>
				<th><?php echo esc_html( sprintf( /* translators: %s: base currency. */ __( 'مقابل 1 %s', 'fares-store' ), FARES_CURRENCY_BASE ) ); ?></th>
				<th><?php esc_html_e( 'المصدر', 'fares-store' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rates as $code => $rate ) : ?>
				<?php
				if ( FARES_CURRENCY_BASE === $code ) {
					continue;
				}
				?>
				<tr>
					<td><?php echo esc_html( $code ); ?></td>
					<td><?php echo esc_html( number_format( (float) $rate, 6 ) ); ?></td>
					<td>
						<?php
						if ( isset( $overrides[ $code ] ) ) {
							esc_html_e( 'يدوي', 'fares-store' );
						} else {
							echo esc_html( (string) ( $last_good['provider'] ?? '—' ) );
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p>
		<?php
		if ( $refreshed > 0 ) {
			echo esc_html(
				sprintf(
					/* translators: %s: human time diff. */
					__( 'آخر تحديث ناجح: منذ %s.', 'fares-store' ),
					human_time_diff( $refreshed )
				)
			);
		} else {
			esc_html_e( 'لم يتم جلب الأسعار بعد.', 'fares-store' );
		}
		?>
		<a class="button" href="<?php echo esc_url( $refresh ); ?>"><?php esc_html_e( 'تحديث الآن', 'fares-store' ); ?></a>
	</p>
	<?php
}

/**
 * The refresh-now action.
 */
function fares_currency_handle_manual_refresh(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'غير مصرح.', 'fares-store' ) );
	}

	check_admin_referer( 'fares_currency_refresh' );
	fares_currency_fx_refresh();
	wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=fares_currency' ) );
	exit;
}
add_action( 'admin_post_fares_currency_refresh', 'fares_currency_handle_manual_refresh' );

/**
 * Surface FX staleness where the merchant will see it.
 */
function fares_currency_stale_rates_notice(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$refreshed = (int) get_option( FARES_FX_LAST_REFRESH, 0 );
	$failures  = (int) get_option( FARES_FX_FAILURES, 0 );

	if ( 0 === $refreshed && 0 === $failures ) {
		return; // Fresh install, bootstrap fetch pending.
	}

	$age = time() - $refreshed;

	if ( $failures < 2 && $age < FARES_FX_STALE_WARN ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: 1: hours since refresh, 2: consecutive failures. */
				__( 'أسعار الصرف لم تُحدَّث منذ %1$d ساعة (إخفاقات متتالية: %2$d). يستمر الموقع في استخدام آخر أسعار معروفة. يمكنك ضبط سعر يدوي من إعدادات عملات فارس.', 'fares-store' ),
				(int) floor( $age / HOUR_IN_SECONDS ),
				$failures
			)
		)
	);
}
add_action( 'admin_notices', 'fares_currency_stale_rates_notice' );
