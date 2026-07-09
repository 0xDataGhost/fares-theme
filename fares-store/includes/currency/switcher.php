<?php
/**
 * Visitor-facing currency switcher and its REST endpoint.
 *
 * The rendered control is plain links carrying ?fares_currency=XXX —
 * the resolver sets the sticky cookie and redirects to the clean URL,
 * so switching works with no JavaScript and never leaves the parameter
 * in a cacheable URL. The REST route exists for programmatic/JS use
 * (e.g. swapping mini-cart fragments without a reload).
 *
 * Hidden on cart and checkout: currency is locked once the visitor
 * enters the purchase flow so the charged total never shifts mid-flow.
 *
 * @package fares-store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the switcher. Themes call this (guarded by function_exists)
 * wherever the currency control belongs.
 */
function fares_currency_switcher(): void {
	if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
		return;
	}

	$active  = fares_currency_active();
	$markets = fares_currency_registry();
	$current = fares_currency_market_for( $active );

	if ( null === $current || count( $markets ) < 2 ) {
		return;
	}
	?>
	<details class="fares-currency-switcher">
		<summary class="fares-currency-switcher__toggle">
			<span class="screen-reader-text"><?php esc_html_e( 'تغيير العملة، العملة الحالية:', 'fares-store' ); ?></span>
			<?php echo esc_html( $current['symbol'] ); ?>
			<span class="fares-currency-switcher__code"><?php echo esc_html( $active ); ?></span>
		</summary>
		<ul class="fares-currency-switcher__list">
			<?php foreach ( $markets as $market ) : ?>
				<li>
					<a
						class="fares-currency-switcher__option<?php echo $market['currency'] === $active ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( FARES_CURRENCY_COOKIE, $market['currency'] ) ); ?>"
						<?php echo $market['currency'] === $active ? 'aria-current="true"' : ''; ?>
					>
						<?php echo esc_html( $market['symbol'] ); ?>
						<span class="fares-currency-switcher__code"><?php echo esc_html( $market['currency'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</details>
	<?php
}

/**
 * REST: POST /fares/v1/currency { "currency": "EGP" }.
 *
 * Sets the sticky manual cookie and returns refreshed cart fragments so
 * a JS caller can update the mini-cart in place. Full pages should
 * still reload — every price on the page changes, not just the cart.
 */
function fares_currency_register_rest_routes(): void {
	register_rest_route(
		'fares/v1',
		'/currency',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function (): WP_REST_Response {
					return new WP_REST_Response(
						array(
							'currency' => fares_currency_active(),
							'manual'   => fares_currency_is_manual(),
							'markets'  => array_keys( fares_currency_registry() ),
						)
					);
				},
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'args'                => array(
					'currency' => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => static function ( $value ): bool {
							return is_string( $value ) && fares_currency_is_supported( $value );
						},
					),
				),
				'callback'            => 'fares_currency_rest_switch',
			),
		)
	);
}
add_action( 'rest_api_init', 'fares_currency_register_rest_routes' );

/**
 * Handle the switch request.
 *
 * @param WP_REST_Request $request Request.
 */
function fares_currency_rest_switch( WP_REST_Request $request ): WP_REST_Response {
	$code = strtoupper( (string) $request['currency'] );

	fares_currency_persist( $code, true );

	// Re-render mini-cart fragments in the new currency for in-place swaps.
	$fragments = array();

	if ( function_exists( 'WC' ) && null !== WC()->cart ) {
		$fragments = fares_currency_use_active(
			static function (): array {
				return apply_filters( 'woocommerce_add_to_cart_fragments', array() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			},
			$code
		);
	}

	return new WP_REST_Response(
		array(
			'currency'  => $code,
			'manual'    => true,
			'fragments' => $fragments,
		)
	);
}
