// @ts-check
const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './tests',
	timeout: 30_000,
	// One shared WordPress backend — parallel workers contend on session/cart
	// state and Woo variation JS timing; serial is fast enough (<1 min).
	workers: 1,
	retries: 1,
	use: {
		baseURL: 'http://localhost:8888',
	},
	projects: [
		{ name: 'mobile', use: { viewport: { width: 375, height: 812 } } },
		{ name: 'tablet', use: { viewport: { width: 768, height: 1024 } } },
		{ name: 'desktop', use: { viewport: { width: 1440, height: 900 } } },
	],
} );
