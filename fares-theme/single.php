<?php
/**
 * Single (non-product) post template.
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
			<h1 class="entry-title"><?php the_title(); ?></h1>
			<div class="entry-content"><?php the_content(); ?></div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
