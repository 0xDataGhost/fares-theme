// @ts-check
const { test, expect } = require( '@playwright/test' );

/**
 * Location-aware multi-currency (fares-store currency module).
 *
 * The merchant base currency is SAR; visitors from Egypt and the UAE see
 * EGP/AED converted server-side. Geolocation is simulated with the
 * Cloudflare CF-IPCountry header, which is the resolver's first source.
 */

const SYMBOLS = { SAR: 'ر.س', EGP: 'ج.م', AED: 'د.إ' };
const PRICE_SYMBOL = '.woocommerce-Price-currencySymbol';

/** Assert the response header and the first rendered price agree on a currency. */
async function expectCurrency( page, code, path = '/shop/' ) {
	const response = await page.goto( path );
	expect( response.headers()[ 'x-fares-currency' ] ).toBe( code );
	await expect( page.locator( PRICE_SYMBOL ).first() ).toHaveText( SYMBOLS[ code ] );
}

test.describe( 'geolocation-based currency', () => {
	test( 'default visitor (no geo signal) sees the SAR base currency', async ( { page } ) => {
		await expectCurrency( page, 'SAR' );
	} );

	test( 'Egyptian visitor sees EGP with whole-pound psychological pricing', async ( { page } ) => {
		await page.setExtraHTTPHeaders( { 'CF-IPCountry': 'EG' } );
		await expectCurrency( page, 'EGP' );

		// EGP registry rule: zero decimals, whole units ending in 9.
		const amounts = await page
			.locator( '.fares-card__price bdi' )
			.allTextContents();
		expect( amounts.length ).toBeGreaterThan( 0 );
		for ( const text of amounts.slice( 0, 5 ) ) {
			const digits = text.replace( /[^0-9]/g, '' );
			expect( digits ).toMatch( /9$/ );
		}
	} );

	test( 'UAE visitor sees AED rounded to the quarter', async ( { page } ) => {
		await page.setExtraHTTPHeaders( { 'CF-IPCountry': 'AE' } );
		await expectCurrency( page, 'AED' );

		// The د.إ symbol contains a dot, so keep digits only: a 2-decimal
		// price expressed in fils is a multiple of 25 exactly when the
		// amount is quarter-rounded.
		const first = await page.locator( '.fares-card__price bdi' ).first().textContent();
		const fils = parseInt( ( first || '' ).replace( /[^0-9]/g, '' ), 10 );
		expect( fils % 25 ).toBe( 0 );
	} );

	test( 'unsupported country falls back to the default market', async ( { page } ) => {
		await page.setExtraHTTPHeaders( { 'CF-IPCountry': 'US' } );
		await expectCurrency( page, 'SAR' );
	} );
} );

test.describe( 'manual currency selection', () => {
	test( 'header switcher lists all markets and swaps the page currency', async ( { page } ) => {
		// The locale pill (and the switcher inside it) is desktop-only in
		// the current header design; mobile switching still works via the
		// ?fares_currency= link pattern covered below.
		const viewport = page.viewportSize();
		test.skip( !! viewport && viewport.width <= 781, 'switcher hidden on mobile header' );

		await page.goto( '/' );

		const switcher = page.locator( '.fares-currency-switcher' );
		await expect( switcher ).toBeVisible();
		await switcher.locator( 'summary' ).click();
		await expect( switcher.locator( '.fares-currency-switcher__option' ) ).toHaveCount( 3 );

		await switcher.getByRole( 'link', { name: /EGP/ } ).click();
		await expect( page.locator( PRICE_SYMBOL ).first() ).toHaveText( SYMBOLS.EGP );
	} );

	test( 'manual choice is sticky: geolocation never overrides it', async ( { page } ) => {
		// Choose AED manually…
		await page.goto( '/shop/?fares_currency=AED' );
		await expect( page.locator( PRICE_SYMBOL ).first() ).toHaveText( SYMBOLS.AED );

		// …then browse as if from Egypt: AED must survive.
		await page.setExtraHTTPHeaders( { 'CF-IPCountry': 'EG' } );
		await expectCurrency( page, 'AED' );
	} );

	test( 'reset param clears the override and re-runs detection', async ( { page } ) => {
		await page.goto( '/shop/?fares_currency=AED' );
		await page.setExtraHTTPHeaders( { 'CF-IPCountry': 'EG' } );
		await page.goto( '/shop/?fares_currency_reset=1' );
		await expectCurrency( page, 'EGP' );
	} );
} );

test.describe( 'currency through cart and checkout', () => {
	test( 'cart, checkout, and the stored order all use the visitor currency', async ( { page } ) => {
		await page.setExtraHTTPHeaders( { 'CF-IPCountry': 'EG' } );

		await page.goto( '/?add-to-cart=11' );
		await page.goto( '/cart/' );
		await expect( page.locator( `.cart_totals ${ PRICE_SYMBOL }` ).first() ).toHaveText( SYMBOLS.EGP );

		// The switcher is locked away inside the purchase flow.
		await expect( page.locator( '.fares-currency-switcher' ) ).toHaveCount( 0 );

		await page.goto( '/checkout/' );
		await expect( page.locator( `.order-total ${ PRICE_SYMBOL }` ).first() ).toHaveText( SYMBOLS.EGP );
		await expect( page.locator( '.fares-currency-switcher' ) ).toHaveCount( 0 );

		await page.fill( '#billing_first_name', 'اختبار عملة' );
		await page.fill( '#billing_email', `currency-${ Date.now() }@example.com` );
		await page.fill( '#billing_phone', '01000000000' );
		await page.locator( '#place_order' ).click();

		await expect( page ).toHaveURL( /order-received/, { timeout: 15_000 } );
		// The order confirmation renders from _order_currency — still EGP.
		await expect( page.locator( `.woocommerce-order ${ PRICE_SYMBOL }` ).first() ).toHaveText( SYMBOLS.EGP );
	} );
} );

test.describe( 'crawlers and cache safety', () => {
	test( 'Googlebot is pinned to the base currency regardless of geo', async ( { page } ) => {
		await page.setExtraHTTPHeaders( {
			'CF-IPCountry': 'EG',
			'User-Agent': 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
		} );
		const response = await page.goto( '/shop/' );
		expect( response.headers()[ 'x-fares-currency' ] ).toBe( 'SAR' );
	} );

	test( 'responses expose the currency header and vary on cookie', async ( { page } ) => {
		const response = await page.goto( '/shop/' );
		expect( response.headers()[ 'x-fares-currency' ] ).toBeTruthy();
		expect( response.headers().vary ).toContain( 'Cookie' );
	} );

	test( 'REST switch endpoint validates and sets the manual override', async ( { request } ) => {
		const bad = await request.post( '/wp-json/fares/v1/currency', { data: { currency: 'USD' } } );
		expect( bad.status() ).toBe( 400 );

		const ok = await request.post( '/wp-json/fares/v1/currency', { data: { currency: 'EGP' } } );
		expect( ok.status() ).toBe( 200 );
		const body = await ok.json();
		expect( body.currency ).toBe( 'EGP' );
		expect( body.manual ).toBe( true );
	} );
} );
