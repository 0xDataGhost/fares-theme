<?php
/**
 * Testimonials slider ("آراء العملاء") — reused on home + archives.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_testimonials = fares_get_testimonials( 10 );

if ( empty( $fares_testimonials ) ) {
	return;
}
?>
<section class="fares-testimonials fares-container" data-fares-carousel aria-label="<?php esc_attr_e( 'آراء العملاء', 'fares-theme' ); ?>">
	<h2 class="fares-testimonials__title"><?php esc_html_e( 'آراء العملاء', 'fares-theme' ); ?></h2>

	<div class="fares-carousel__viewport">
		<ul class="fares-carousel__track fares-testimonials__track">
			<?php foreach ( $fares_testimonials as $fares_t ) : ?>
				<?php $fares_rating = max( 1, min( 5, (int) get_post_meta( $fares_t->ID, '_fares_rating', true ) ) ); ?>
				<li class="fares-testimonial">
					<p class="fares-testimonial__name"><?php echo esc_html( get_the_title( $fares_t ) ); ?></p>
					<span class="fares-testimonial__stars" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: rating out of 5. */ __( 'التقييم %d من 5', 'fares-theme' ), $fares_rating ) ); ?>">
						<?php for ( $fares_s = 0; $fares_s < $fares_rating; $fares_s++ ) : ?>
							<?php fares_icon( 'star' ); ?>
						<?php endfor; ?>
					</span>
					<blockquote class="fares-testimonial__body"><?php echo esc_html( wp_strip_all_tags( $fares_t->post_content ) ); ?></blockquote>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="fares-testimonials__controls">
		<button type="button" class="fares-section-header__arrow" data-carousel-prev aria-label="<?php esc_attr_e( 'السابق', 'fares-theme' ); ?>"><?php fares_icon( 'arrow-start' ); ?></button>
		<button type="button" class="fares-section-header__arrow" data-carousel-next aria-label="<?php esc_attr_e( 'التالي', 'fares-theme' ); ?>"><?php fares_icon( 'arrow-end' ); ?></button>
	</div>
</section>
