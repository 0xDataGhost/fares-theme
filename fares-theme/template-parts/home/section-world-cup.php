<?php
/**
 * Home: World Cup packages section ("باقات كأس العالم").
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_term = get_term_by( 'slug', 'world-cup', 'product_cat' );

get_template_part(
	'template-parts/home/product-section',
	null,
	array(
		'title'    => __( 'باقات كأس العالم', 'fares-theme' ),
		'products' => fares_get_category_products( 'world-cup', 8 ),
		'view_all' => $fares_term instanceof WP_Term ? get_term_link( $fares_term ) : '',
		'banner'   => 'divider-world-cup.png',
	)
);
