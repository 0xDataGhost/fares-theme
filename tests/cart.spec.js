// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'cart and checkout', () => {
	test( 'variable product flows to cart with variation pills and totals', async ( { page } ) => {
		// Add the GTA variable product (first variation) via its page.
		await page.goto( '/?p=21' );
		await page.locator( '.variations select' ).first().selectOption( { index: 1 } );
		// Deterministic wait: Woo's variation JS resolves the hidden id.
		await expect( page.locator( 'input[name="variation_id"]' ) ).not.toHaveValue( /^0?$/ );
		await page.locator( '.single_add_to_cart_button' ).click();

		await page.goto( '/cart/' );
		await expect( page.locator( '.fares-cart-item' ).first() ).toBeVisible();
		await expect( page.locator( '.fares-cart-item__meta dl.variation dd' ).first() ).toBeVisible();
		await expect( page.locator( '.cart_totals' ) ).toContainText( 'ملخص الطلب' );
		await expect( page.locator( '.checkout-button' ) ).toContainText( 'اتمام الطلب' );
	} );

	test( 'checkout shows no shipping and only trimmed billing fields', async ( { page } ) => {
		await page.goto( '/?add-to-cart=11' );
		await page.goto( '/checkout/' );

		await expect( page.locator( '#billing_first_name' ) ).toBeVisible();
		await expect( page.locator( '#billing_email' ) ).toBeVisible();
		await expect( page.locator( '#billing_phone' ) ).toBeVisible();
		await expect( page.locator( '#billing_last_name' ) ).toHaveCount( 0 );
		await expect( page.locator( '#billing_address_1' ) ).toHaveCount( 0 );
		await expect( page.locator( '.woocommerce-shipping-fields input' ) ).toHaveCount( 0 );
		await expect( page.locator( '#place_order' ) ).toBeVisible();
	} );

	test( 'placing an order auto-completes and delivers a serial code', async ( { page } ) => {
		await page.goto( '/?add-to-cart=11' );
		await page.goto( '/checkout/' );

		await page.fill( '#billing_first_name', 'اختبار' );
		await page.fill( '#billing_email', `test-${ Date.now() }@example.com` );
		await page.fill( '#billing_phone', '01000000000' );
		await page.locator( '#place_order' ).click();

		await expect( page ).toHaveURL( /order-received/, { timeout: 15_000 } );
		await expect( page.locator( '.woocommerce-thankyou-order-received' ) ).toContainText( 'كود التفعيل' );
	} );
} );
