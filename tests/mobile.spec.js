// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'mobile chrome', () => {
	test( 'bottom nav shows only on mobile and search overlay works', async ( { page, viewport } ) => {
		await page.goto( '/' );

		const nav = page.locator( '.fares-mobile-nav' );
		const isMobile = ( viewport?.width ?? 1440 ) <= 781;

		if ( ! isMobile ) {
			await expect( nav ).toBeHidden();
			return;
		}

		await expect( nav ).toBeVisible();

		// Body reserves space for the fixed bar.
		const padding = await page.evaluate( () => getComputedStyle( document.body ).paddingBlockEnd );
		expect( parseInt( padding, 10 ) ).toBeGreaterThanOrEqual( 80 );

		// Search overlay toggles.
		await page.locator( '[data-fares-search-toggle]' ).click();
		await expect( page.locator( '#fares-search-overlay' ) ).toBeVisible();
		await page.keyboard.press( 'Escape' );
		await expect( page.locator( '#fares-search-overlay' ) ).toBeHidden();
	} );

	test( 'no horizontal overflow on key pages', async ( { page, viewport } ) => {
		test.skip( ( viewport?.width ?? 1440 ) > 781, 'mobile-only check' );

		for ( const path of [ '/', '/product-category/sony-5/', '/?p=11', '/cart/' ] ) {
			await page.goto( path );
			const overflow = await page.evaluate(
				() => document.documentElement.scrollWidth - document.documentElement.clientWidth
			);
			expect( overflow, `overflow on ${ path }` ).toBeLessThanOrEqual( 0 );
		}
	} );

	test( 'carousel scrolls horizontally', async ( { page } ) => {
		await page.goto( '/' );
		const track = page.locator( '.fares-carousel__viewport' ).first();
		const scrollable = await track.evaluate( ( el ) => el.scrollWidth >= el.clientWidth );
		expect( scrollable ).toBe( true );
	} );
} );
