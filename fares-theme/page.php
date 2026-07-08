<?php
/**
 * Single page template (renders editor content — cart/checkout shortcode
 * pages flow through here too).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="primary" class="fares-container">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class(); ?>>
			<?php if ( ! is_cart() && ! is_checkout() ) : ?>
				<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php endif; ?>
			<div class="entry-content"><?php the_content(); ?></div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
