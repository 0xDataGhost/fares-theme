<?php
/**
 * Single page template (renders editor content — cart/checkout shortcode
 * pages flow through here too).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	if ( fares_is_content_page() ) :
		// Designed editorial layout (legal, about, contact). Owns its container.
		?>
		<main id="primary" class="fares-page-main">
			<?php get_template_part( 'template-parts/page/content-page' ); ?>
		</main>
		<?php
	else :
		// Cart/checkout shortcode pages and anything else: plain content flow.
		?>
		<main id="primary" class="fares-container">
			<article <?php post_class(); ?>>
				<?php if ( ! is_cart() && ! is_checkout() ) : ?>
					<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php endif; ?>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
		</main>
		<?php
	endif;

endwhile;

get_footer();
