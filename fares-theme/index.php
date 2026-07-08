<?php
/**
 * Generic fallback template.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="primary" class="fares-container">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class(); ?>>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'لا يوجد محتوى.', 'fares-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
