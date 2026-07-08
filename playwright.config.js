// @ts-check
const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './tests',
	timeout: 30_000,
	use: {
		baseURL: 'http://localhost:8888',
	},
	projects: [
		{ name: 'mobile', use: { viewport: { width: 375, height: 812 } } },
		{ name: 'tablet', use: { viewport: { width: 768, height: 1024 } } },
		{ name: 'desktop', use: { viewport: { width: 1440, height: 900 } } },
	],
} );
