<?php
/**
 * Manifest-driven asset loader.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register and enqueue everything declared in the manifest.
 */
function fares_enqueue_assets(): void {
	foreach ( fares_asset_manifest() as $handle => $asset ) {
		$when = $asset['when'] ?? null;
		if ( is_callable( $when ) && ! $when() ) {
			continue;
		}

		$src  = FARES_THEME_URI . '/' . ltrim( $asset['src'], '/' );
		$path = FARES_THEME_DIR . '/' . ltrim( $asset['src'], '/' );

		if ( ! file_exists( $path ) ) {
			continue; // Asset not built yet (early phases) — skip silently.
		}

		$version = (string) filemtime( $path );

		if ( 'script' === ( $asset['type'] ?? 'style' ) ) {
			wp_enqueue_script(
				$handle,
				$src,
				$asset['deps'] ?? array(),
				$version,
				array(
					'strategy'  => ! empty( $asset['defer'] ) ? 'defer' : false,
					'in_footer' => true,
				)
			);
			continue;
		}

		wp_enqueue_style( $handle, $src, $asset['deps'] ?? array(), $version );
	}
}
add_action( 'wp_enqueue_scripts', 'fares_enqueue_assets' );
