<?php
/**
 * Home: Plus apps section ("تطبيقات البلس").
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_term = get_term_by( 'slug', 'plus-apps', 'product_cat' );

get_template_part(
	'template-parts/home/product-section',
	null,
	array(
		'title'    => __( 'تطبيقات البلس', 'fares-theme' ),
		'products' => fares_get_category_products( 'plus-apps', 8 ),
		'view_all' => $fares_term instanceof WP_Term ? get_term_link( $fares_term ) : '',
		'banner'   => 'divider-plus-apps.png',
	)
);
