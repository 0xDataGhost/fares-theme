<?php
/**
 * Dashboard-managed homepage sections.
 *
 * Renders published "قسم رئيسي" (fares_home_section) posts, ordered by their
 * "Order" attribute, mapping each to the existing section renderers:
 *   - type "banner"   -> template-parts/home/section-banner
 *   - type "products" -> template-parts/home/product-section (banner + carousel)
 *
 * Renders nothing until the client adds sections in the dashboard.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_sections = fares_get_home_sections();

if ( empty( $fares_sections ) ) {
	return;
}

foreach ( $fares_sections as $fares_section ) {
	$fares_type        = get_post_meta( $fares_section->ID, FARES_SECTION_TYPE_META, true ) ?: 'products';
	$fares_banner_id   = absint( get_post_meta( $fares_section->ID, FARES_SECTION_BANNER_META, true ) );
	$fares_banner_link = (string) get_post_meta( $fares_section->ID, FARES_SECTION_BANNER_LINK_META, true );

	if ( 'banner' === $fares_type ) {
		get_template_part(
			'template-parts/home/section-banner',
			null,
			array(
				'title'     => get_the_title( $fares_section ),
				'banner_id' => $fares_banner_id,
				'link'      => $fares_banner_link,
			)
		);
		continue;
	}

	$fares_cat_id = absint( get_post_meta( $fares_section->ID, FARES_SECTION_CATEGORY_META, true ) );
	$fares_count  = absint( get_post_meta( $fares_section->ID, FARES_SECTION_COUNT_META, true ) ) ?: FARES_SECTION_DEFAULT_COUNT;
	$fares_term   = $fares_cat_id ? get_term( $fares_cat_id, 'product_cat' ) : null;

	if ( ! $fares_term instanceof WP_Term ) {
		continue;
	}

	$fares_view_all = (string) get_post_meta( $fares_section->ID, FARES_SECTION_VIEW_ALL_META, true );
	if ( '' === $fares_view_all ) {
		$fares_link     = get_term_link( $fares_term );
		$fares_view_all = is_wp_error( $fares_link ) ? '' : $fares_link;
	}

	get_template_part(
		'template-parts/home/product-section',
		null,
		array(
			'title'       => get_the_title( $fares_section ),
			'products'    => fares_get_category_products( $fares_term->slug, $fares_count ),
			'view_all'    => $fares_view_all,
			'banner_id'   => $fares_banner_id,
			'banner_link' => $fares_banner_link,
		)
	);
}
