<?php
/**
 * Production content provisioning — CLI wrapper.
 *
 * Thin entry point for `wp eval-file bin/provision-content.php` (or
 * `npm run provision`). The actual logic lives in the Fares Store plugin at
 * `fares-store/includes/provisioning.php` so the CLI and the automatic
 * on-deploy provisioner share one source of truth.
 *
 * Idempotent and production-SAFE: existing pages are preserved; only missing
 * pieces are created. Safe to re-run.
 *
 * @package fares-store
 */

defined( 'WP_CLI' ) || exit;

if ( ! function_exists( 'fares_store_provision_content' ) ) {
	WP_CLI::error( 'Fares Store plugin is not active — activate it first (it owns the provisioning routine).' );
}

fares_store_provision_content(
	static function ( string $message ): void {
		WP_CLI::log( $message );
	}
);

// Refresh rewrite rules so freshly created pages resolve.
flush_rewrite_rules();

WP_CLI::success( 'Content provisioned: legal pages, footer menu, homepage, and WooCommerce pages.' );
