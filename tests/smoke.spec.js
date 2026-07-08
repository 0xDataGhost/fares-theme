// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'foundation', () => {
	test( 'homepage is RTL Arabic with dark canvas and Kufam', async ( { page } ) => {
		await page.goto( '/' );

		await expect( page.locator( 'html' ) ).toHaveAttribute( 'dir', 'rtl' );
		await expect( page.locator( 'html' ) ).toHaveAttribute( 'lang', 'ar' );

		const body = page.locator( 'body' );
		await expect( body ).toHaveCSS( 'background-color', 'rgb(0, 0, 0)' );
		const fontFamily = await body.evaluate( ( el ) => getComputedStyle( el ).fontFamily );
		expect( fontFamily ).toContain( 'Kufam' );
	} );

	test( 'category archive renders product count and cards', async ( { page } ) => {
		await page.goto( '/product-category/plus-apps/' );
		await expect( page.locator( '.fares-result-count' ) ).toContainText( 'منتجات' );
		expect( await page.locator( 'ul.products li.product' ).count() ).toBeGreaterThan( 0 );
	} );

	test( 'cart page responds with shortcode cart', async ( { page } ) => {
		await page.goto( '/cart/' );
		await expect( page.locator( '.woocommerce' ).first() ).toBeVisible();
	} );
} );
