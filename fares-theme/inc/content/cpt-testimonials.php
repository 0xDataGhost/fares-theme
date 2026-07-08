<?php
/**
 * Testimonial custom post type + rating meta.
 *
 * Site content structure (presentation domain) — store-level customer
 * testimonials shown on the homepage and archives ("آراء العملاء").
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the testimonial CPT.
 */
function fares_register_testimonial_cpt(): void {
	register_post_type(
		'fares_testimonial',
		array(
			'labels'       => array(
				'name'          => __( 'آراء العملاء', 'fares-theme' ),
				'singular_name' => __( 'رأي عميل', 'fares-theme' ),
				'add_new_item'  => __( 'إضافة رأي جديد', 'fares-theme' ),
				'edit_item'     => __( 'تعديل الرأي', 'fares-theme' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-format-quote',
			'supports'     => array( 'title', 'editor', 'custom-fields' ),
		)
	);

	register_post_meta(
		'fares_testimonial',
		'_fares_rating',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 5,
			'show_in_rest'      => true,
			'sanitize_callback' => static fn( $value ): int => max( 1, min( 5, absint( $value ) ) ),
			'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
		)
	);
}
add_action( 'init', 'fares_register_testimonial_cpt' );

/**
 * Show the rating in the admin list table.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function fares_testimonial_admin_columns( array $columns ): array {
	$columns['fares_rating'] = __( 'التقييم', 'fares-theme' );
	return $columns;
}
add_filter( 'manage_fares_testimonial_posts_columns', 'fares_testimonial_admin_columns' );

/**
 * Render the rating column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function fares_testimonial_admin_column_content( string $column, int $post_id ): void {
	if ( 'fares_rating' === $column ) {
		echo esc_html( str_repeat( '★', (int) get_post_meta( $post_id, '_fares_rating', true ) ) );
	}
}
add_action( 'manage_fares_testimonial_posts_custom_column', 'fares_testimonial_admin_column_content', 10, 2 );
