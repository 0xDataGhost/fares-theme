<?php
/**
 * Display helper functions (template tags).
 *
 * Pure display helpers only — no queries, no business logic.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline an icon from the theme SVG sprite.
 *
 * @param string $name  Symbol id inside assets/images/icons.svg (without prefix).
 * @param string $label Accessible label; empty means decorative (aria-hidden).
 */
function fares_icon( string $name, string $label = '' ): void {
	$aria = '' === $label
		? ' aria-hidden="true" focusable="false"'
		: ' role="img" aria-label="' . esc_attr( $label ) . '"';

	printf(
		'<svg class="fares-icon fares-icon--%1$s"%2$s><use href="%3$s#icon-%1$s"></use></svg>',
		esc_attr( $name ),
		$aria, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
		esc_url( FARES_THEME_URI . '/assets/images/icons.svg' )
	);
}

/**
 * Render a decorative section title row (logo + flanking lines + optional view-all).
 *
 * @param string $title    Section heading text.
 * @param string $view_all Optional URL for the "عرض الكل" link.
 */
function fares_section_title( string $title, string $view_all = '' ): void {
	get_template_part(
		'template-parts/global/section-title',
		null,
		array(
			'title'    => $title,
			'view_all' => $view_all,
		)
	);
}
