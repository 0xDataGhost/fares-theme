<?php
/**
 * Home: best sellers section ("الأكثر مبيعا").
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

get_template_part(
	'template-parts/home/product-section',
	null,
	array(
		'title'    => __( 'الأكثر مبيعا', 'fares-theme' ),
		'products' => fares_get_best_sellers( 8 ),
		'view_all' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '',
	)
);
