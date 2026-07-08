<?php
/**
 * Generic (non-product) archive template.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="primary" class="fares-container">
	<h1 class="archive-title"><?php the_archive_title(); ?></h1>
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class(); ?>>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<?php the_excerpt(); ?>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'لا يوجد محتوى.', 'fares-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
