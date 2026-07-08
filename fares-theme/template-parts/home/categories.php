<?php
/**
 * Home category grid — art-directed category cards (Figma 1:2335, 5-col).
 *
 * Artwork: `_fares_card_artwork` term meta (attachment ID); falls back to
 * the numbered Figma export matched by term order, then the Woo thumbnail.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'term_order',
	)
);

if ( is_wp_error( $fares_terms ) || empty( $fares_terms ) ) {
	return;
}
?>
<section class="fares-categories fares-container" aria-label="<?php esc_attr_e( 'الأقسام', 'fares-theme' ); ?>">
	<ul class="fares-categories__grid">
		<?php foreach ( array_values( $fares_terms ) as $fares_i => $fares_term ) : ?>
			<?php
			$fares_artwork_id = fares_get_category_artwork_id( $fares_term->term_id );
			$fares_fallback   = sprintf( 'category-%02d.png', $fares_i + 1 );
			?>
			<li class="fares-categories__item">
				<a class="fares-categories__card" href="<?php echo esc_url( get_term_link( $fares_term ) ); ?>">
					<?php if ( $fares_artwork_id ) : ?>
						<?php echo wp_get_attachment_image( $fares_artwork_id, 'fares-category-card', false, array( 'class' => 'fares-categories__image', 'loading' => 'lazy' ) ); ?>
					<?php elseif ( file_exists( FARES_THEME_DIR . '/assets/images/figma/' . $fares_fallback ) ) : ?>
						<img class="fares-categories__image" src="<?php echo esc_url( FARES_THEME_URI . '/assets/images/figma/' . $fares_fallback ); ?>" alt="" loading="lazy" />
					<?php endif; ?>
					<span class="screen-reader-text"><?php echo esc_html( $fares_term->name ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
