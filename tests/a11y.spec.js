// @ts-check
const { test, expect } = require( '@playwright/test' );
const AxeBuilder = require( '@axe-core/playwright' ).default;

const PAGES = [ '/', '/product-category/sony-5/', '/?p=11', '/cart/' ];

test.describe( 'accessibility', () => {
	for ( const path of PAGES ) {
		test( `no critical axe violations on ${ path }`, async ( { page, viewport } ) => {
			test.skip( ( viewport?.width ?? 1440 ) !== 1440, 'desktop-only scan' );

			await page.goto( path );
			const results = await new AxeBuilder( { page } ).analyze();

			// Two contrast pairs are design-owned (extracted verbatim from the
			// Figma file) and flagged to the designer — see tokens.css:
			// white-on-#e97e57 ribbon and white-on-#3982b9 announcement bar.
			const DESIGN_FLAGGED = /fares-card__ribbon|fares-announcement__text/;

			const critical = results.violations
				.map( ( v ) => ( {
					...v,
					nodes: v.nodes.filter( ( n ) => ! DESIGN_FLAGGED.test( String( n.target[ 0 ] ) ) ),
				} ) )
				.filter( ( v ) => v.nodes.length > 0 && [ 'critical', 'serious' ].includes( v.impact ?? '' ) );

			expect(
				critical.map( ( v ) => `${ v.id }: ${ v.nodes.length } nodes — ${ v.help }` ),
				`violations on ${ path }`
			).toEqual( [] );
		} );
	}
} );
