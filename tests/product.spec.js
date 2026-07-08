// @ts-check
const { test, expect } = require( '@playwright/test' );

const PRODUCT_URL = '/?p=11'; // بلس ايفون - الباقة الماسية vip (seeded)

test.describe( 'single product', () => {
	test( 'summary renders stock badge, purchase box, and reviews aggregate', async ( { page } ) => {
		await page.goto( PRODUCT_URL );

		await expect( page.locator( '.fares-stock-badge' ) ).toContainText( 'متوفر' );
		await expect( page.locator( '.fares-purchase-count' ) ).toBeVisible();
		await expect( page.locator( '.fares-purchase-box .single_add_to_cart_button' ) ).toContainText( 'أضف للسلة' );
		await expect( page.locator( '.fares-buy-now' ) ).toContainText( 'اشتري الان' );
		await expect( page.locator( '.fares-reviews__stat' ) ).toBeVisible();
		await expect( page.locator( '.fares-bought-together' ) ).toBeVisible();
	} );

	test( 'quantity stepper buttons adjust the input', async ( { page } ) => {
		await page.goto( PRODUCT_URL );

		const qty = page.locator( 'form.cart .qty' );
		await expect( qty ).toHaveValue( '1' );
		await page.locator( 'form.cart [data-fares-qty="up"]' ).click();
		await expect( qty ).toHaveValue( '2' );
		await page.locator( 'form.cart [data-fares-qty="down"]' ).click();
		await expect( qty ).toHaveValue( '1' );
	} );

	test( 'buy-now lands on checkout with the item', async ( { page } ) => {
		await page.goto( PRODUCT_URL );
		await page.locator( 'form.cart [data-fares-qty="up"]' ).click();
		await page.locator( '.fares-buy-now' ).click();

		await expect( page ).toHaveURL( /checkout/ );
		await expect( page.locator( 'body' ) ).toContainText( 'بلس ايفون' );
	} );

	test( 'add to cart updates the header badge', async ( { page } ) => {
		await page.goto( PRODUCT_URL );
		await page.locator( '.single_add_to_cart_button' ).click();
		await page.waitForLoadState( 'networkidle' );
		await expect( page.locator( '.fares-cart-badge' ).first() ).not.toHaveClass( /fares-cart-badge--empty/ );
	} );
} );
