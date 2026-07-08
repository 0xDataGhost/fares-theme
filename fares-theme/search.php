<?php
/**
 * Search results template.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="primary" class="fares-container">
	<h1 class="search-title">
		<?php
		/* translators: %s: search query. */
		printf( esc_html__( 'نتائج البحث عن: %s', 'fares-theme' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
		?>
	</h1>
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
		<p><?php esc_html_e( 'لا توجد نتائج مطابقة لبحثك.', 'fares-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
