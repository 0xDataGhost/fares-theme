<?php
/**
 * Homepage — fixed composition, intentionally NOT block-editor editable.
 *
 * A flat, readable sequence of section parts. Content comes from products,
 * categories, testimonials, and term meta — never from the editor.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="primary" class="fares-home">
	<?php get_template_part( 'template-parts/home/hero' ); ?>
	<?php get_template_part( 'template-parts/home/best-sellers' ); ?>
	<?php get_template_part( 'template-parts/home/categories' ); ?>
	<?php get_template_part( 'template-parts/home/section-world-cup' ); ?>
	<?php get_template_part( 'template-parts/home/section-sony' ); ?>
	<?php get_template_part( 'template-parts/home/section-plus-apps' ); ?>
	<?php get_template_part( 'template-parts/global/testimonials' ); ?>
	<?php get_template_part( 'template-parts/home/promo-banner-payments' ); ?>
</main>

<?php
get_footer();
