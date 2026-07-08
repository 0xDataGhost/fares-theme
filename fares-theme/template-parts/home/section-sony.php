<?php
/**
 * Home: Sony section ("قسم السوني").
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_term = get_term_by( 'slug', 'sony-5', 'product_cat' );

get_template_part(
	'template-parts/home/product-section',
	null,
	array(
		'title'    => __( 'قسم السوني', 'fares-theme' ),
		'products' => fares_get_category_products( 'sony-5', 8 ),
		'view_all' => $fares_term instanceof WP_Term ? get_term_link( $fares_term ) : '',
		'banner'   => 'divider-sony.png',
	)
);
