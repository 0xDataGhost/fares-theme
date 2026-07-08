<?php
/**
 * Woo display re-composition via hooks (no template overrides).
 *
 * Summary re-ordering, breadcrumb args, archive extras, badges — every
 * customization here considers hooks first per the project's architecture.
 * Populated across Phases 3–5.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumb defaults matched to the design (الرئيسية home label).
 *
 * @param array $args Breadcrumb args.
 * @return array
 */
function fares_breadcrumb_defaults( array $args ): array {
	$args['home']        = __( 'الرئيسية', 'fares-theme' );
	$args['delimiter']   = '<span class="fares-breadcrumb__sep" aria-hidden="true"></span>';
	$args['wrap_before'] = '<nav class="fares-breadcrumb" aria-label="' . esc_attr__( 'مسار التنقل', 'fares-theme' ) . '">';
	$args['wrap_after']  = '</nav>';
	return $args;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'fares_breadcrumb_defaults' );
