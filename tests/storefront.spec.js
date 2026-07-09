// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'category drawer', () => {
	test( 'opens from the header hamburger and lists product categories', async ( { page } ) => {
		await page.goto( '/' );

		const toggle = page.locator( '[data-fares-menu-toggle]' );
		await expect( toggle ).toBeVisible();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );

		await toggle.click();

		const drawer = page.locator( '#fares-category-drawer' );
		await expect( drawer ).toBeVisible();
		await expect( drawer ).toHaveClass( /is-open/ );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );

		const links = page.locator( '.fares-drawer__link' );
		await expect( links.first() ).toBeVisible();
		expect( await links.count() ).toBeGreaterThan( 0 );
		// The default "Uncategorized" term is excluded from the menu.
		await expect( page.locator( '.fares-drawer__link', { hasText: 'Uncategorized' } ) ).toHaveCount( 0 );
	} );

	test( 'closes on Escape and returns focus to the toggle', async ( { page } ) => {
		await page.goto( '/' );
		const toggle = page.locator( '[data-fares-menu-toggle]' );
		await toggle.click();

		const drawer = page.locator( '#fares-category-drawer' );
		await expect( drawer ).toHaveClass( /is-open/ );

		await page.keyboard.press( 'Escape' );
		await expect( drawer ).toBeHidden();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( toggle ).toBeFocused();
	} );

	test( 'closes when the scrim is clicked', async ( { page } ) => {
		await page.goto( '/' );
		await page.locator( '[data-fares-menu-toggle]' ).click();

		const drawer = page.locator( '#fares-category-drawer' );
		await expect( drawer ).toHaveClass( /is-open/ );

		// The panel sits at the inline-start (right in RTL); the far-left is scrim.
		await drawer.click( { position: { x: 5, y: 400 } } );
		await expect( drawer ).toBeHidden();
	} );
} );

test.describe( 'add-to-cart popup', () => {
	test( 'confirming an add from a product card opens the popup', async ( { page } ) => {
		await page.goto( '/' );

		const button = page.locator( 'a.add_to_cart_button' ).first();
		await button.scrollIntoViewIfNeeded();
		await button.click();

		const modal = page.locator( '#fares-atc-modal' );
		await expect( modal ).toBeVisible();
		await expect( modal ).toHaveClass( /is-open/ );
		// The clicked product's name is surfaced in the confirmation.
		await expect( page.locator( '[data-fares-atc-name]' ) ).not.toBeEmpty();
		// Checkout / view-cart / continue actions are present.
		await expect( page.locator( '.fares-atc-modal__action' ).first() ).toBeVisible();
		expect( await page.locator( '.fares-atc-modal__action' ).count() ).toBe( 3 );
	} );

	test( 'closes on Escape', async ( { page } ) => {
		await page.goto( '/' );
		await page.locator( 'a.add_to_cart_button' ).first().click();

		const modal = page.locator( '#fares-atc-modal' );
		await expect( modal ).toBeVisible();

		await page.keyboard.press( 'Escape' );
		await expect( modal ).toBeHidden();
	} );
} );

test.describe( 'header account link', () => {
	test( 'account icon is mobile-only; login pill covers desktop', async ( { page, viewport } ) => {
		await page.goto( '/' );

		const account = page.locator( '.fares-header__account' );
		if ( ( viewport?.width ?? 1440 ) <= 781 ) {
			await expect( account ).toBeVisible();
			await expect( account ).toHaveAttribute( 'href', /my-account/ );
		} else {
			await expect( account ).toBeHidden();
			await expect( page.locator( '.fares-header-actions__login' ) ).toBeVisible();
		}
	} );
} );
